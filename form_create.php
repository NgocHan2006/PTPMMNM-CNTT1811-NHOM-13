<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$error = '';
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$label = trim($_POST['label'] ?? '');
$type = $_POST['field_type'] ?? 'text';
$help = trim($_POST['help_text'] ?? '');
$required = isset($_POST['required']) ? 1 : 0;
$optionsText = trim($_POST['options'] ?? '');
$allowed = ['text', 'number', 'date', 'single_choice', 'multiple_choice'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($title === '') {
        $error = 'Vui lòng nhập tên biểu mẫu.';
    } elseif (mb_strlen($title) > 255) {
        $error = 'Tên biểu mẫu tối đa 255 ký tự.';
    } elseif (!in_array($type, $allowed, true)) {
        $error = 'Kiểu trường không hợp lệ.';
    } elseif ($label !== '' && in_array($type, ['single_choice', 'multiple_choice'], true)
        && count(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $optionsText)))) < 2) {
        $error = 'Câu hỏi lựa chọn cần ít nhất 2 đáp án, mỗi đáp án một dòng.';
    } else {
        try {
            $pdo->beginTransaction();

            // Tạo biểu mẫu và phiên bản đầu tiên
            $ownerId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 2;
            if (hasColumn($pdo, 'forms', 'owner_id')) {
                $stmt = $pdo->prepare("INSERT INTO forms (owner_id, title, description, status) VALUES (?, ?, ?, 'draft')");
                $stmt->execute([$ownerId, $title, $description !== '' ? $description : null]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO forms (title, description, status) VALUES (?, ?, 'draft')");
                $stmt->execute([$title, $description !== '' ? $description : null]);
            }
            $formId = (int)$pdo->lastInsertId();

            $vcol = versionColumn($pdo);
            $versionCols = tableColumns($pdo, 'form_versions');
            $insertCols = ['form_id', $vcol, 'status'];
            $insertVals = [$formId, 1, 'draft'];
            if (in_array('title_snapshot', $versionCols, true)) { $insertCols[]='title_snapshot'; $insertVals[]=$title; }
            if (in_array('description_snapshot', $versionCols, true)) { $insertCols[]='description_snapshot'; $insertVals[]=$description !== '' ? $description : null; }
            $quoted = implode(',', array_map(fn($c)=>'`'.$c.'`', $insertCols));
            $marks = implode(',', array_fill(0, count($insertCols), '?'));
            $stmt = $pdo->prepare("INSERT INTO form_versions ($quoted) VALUES ($marks)");
            $stmt->execute($insertVals);
            $versionId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("UPDATE forms SET current_version_id = ? WHERE id = ?");
            $stmt->execute([$versionId, $formId]);

            // Nếu người dùng đã nhập câu hỏi đầu tiên thì lưu luôn
            if ($label !== '') {
                $optionsJson = null;
                if (in_array($type, ['single_choice', 'multiple_choice'], true)) {
                    $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $optionsText)), fn($v) => $v !== ''));
                    $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
                }

                $key = 'field_' . bin2hex(random_bytes(5));
                $stmt = $pdo->prepare("INSERT INTO fields (version_id, field_key, label, field_type, help_text, is_required, sort_order, options_json) VALUES (?, ?, ?, ?, ?, ?, 1, ?)");
                $stmt->execute([$versionId, $key, $label, $type, $help !== '' ? $help : null, $required, $optionsJson]);
            }

            $pdo->commit();
            header('Location: form_builder.php?id=' . $formId . '&created=1');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Không tạo được biểu mẫu. Hãy kiểm tra kết nối cơ sở dữ liệu và bảng users/forms/form_versions/fields.';
        }
    }
}

$typeNames = ['text' => 'Văn bản', 'number' => 'Số', 'date' => 'Ngày', 'single_choice' => 'Một lựa chọn', 'multiple_choice' => 'Nhiều lựa chọn'];
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tạo biểu mẫu mới - Formly</title>
<link rel="stylesheet" href="assets/form_builder.css">
<link rel="stylesheet" href="assets/app.css">
<style>
.form-meta-panel .panel-body{padding:24px}
.builder-form input[type="text"], .builder-form input:not([type]), .builder-form select, .builder-form textarea{width:100%}
.hero .hero-copy{width:100%}
.hero .hero-copy h1{margin:5px 0 8px}
.hero .hero-copy p{margin:0;color:rgba(255,255,255,.88)}
.layout{align-items:start}
.form-actions{display:flex;gap:10px;align-items:center;margin-top:20px;flex-wrap:wrap}
.btn-wide{width:100%}
.intro-note{color:var(--muted);font-size:13px;margin-top:8px}
@media(max-width:850px){.layout{grid-template-columns:1fr!important}.hero{padding:22px}}
</style>
</head>
<body class="create-form-page">
<div class="page">
  <header class="topbar">
    <a class="back-link" href="forms.php">← Danh sách biểu mẫu</a>
    <span class="version-pill">Phiên bản 1 · Bản nháp</span>
  </header>

  <section class="hero">
    <div class="hero-icon">▤</div>
    <div class="hero-copy">
      <div class="eyebrow">TRÌNH THIẾT KẾ BIỂU MẪU</div>
      <h1>Tạo biểu mẫu mới</h1>
      <p>Nhập thông tin biểu mẫu và có thể tạo luôn câu hỏi đầu tiên ngay tại đây.</p>
    </div>
  </section>

  <?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>

  <form method="post" class="layout">
    <main class="panel questions-panel form-meta-panel">
      <div class="panel-heading">
        <div><h2>Thông tin biểu mẫu</h2><p>Đặt tên và mô tả cho biểu mẫu của bạn</p></div>
        <span class="count-badge">1</span>
      </div>
      <div class="panel-body">
        <div class="builder-form">
          <label for="title">Tên biểu mẫu <span>*</span></label>
          <input id="title" name="title" type="text" maxlength="255" required value="<?= h($title) ?>" placeholder="Ví dụ: Khảo sát mức độ hài lòng">

          <label for="description">Mô tả</label>
          <textarea id="description" name="description" rows="5" placeholder="Mô tả mục đích của biểu mẫu..."><?= h($description) ?></textarea>

          <div class="intro-note">Biểu mẫu sẽ được tạo ở trạng thái bản nháp. Bạn có thể tiếp tục chỉnh sửa và công bố sau.</div>
          <div class="form-actions">
            <button class="btn btn-primary" type="submit">＋ Tạo biểu mẫu</button>
            <a class="btn btn-light" href="forms.php">Hủy</a>
          </div>
        </div>
      </div>
    </main>

    <aside class="panel add-panel">
      <div class="panel-heading"><div><h2>Câu hỏi đầu tiên</h2><p>Không bắt buộc · Có thể thêm sau</p></div></div>
      <div class="panel-body">
        <div class="builder-form">
          <label for="label">Nội dung câu hỏi</label>
          <input id="label" name="label" type="text" maxlength="255" value="<?= h($label) ?>" placeholder="Ví dụ: Bạn đánh giá dịch vụ thế nào?">

          <label for="field_type">Kiểu trường</label>
          <select id="field_type" name="field_type" onchange="toggleOptions()">
            <?php foreach ($typeNames as $k => $v): ?>
              <option value="<?= h($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= h($v) ?></option>
            <?php endforeach; ?>
          </select>

          <div id="options-box" class="options-box" hidden>
            <label for="options">Các đáp án</label>
            <textarea id="options" name="options" rows="5" placeholder="Rất hài lòng&#10;Hài lòng&#10;Bình thường&#10;Không hài lòng"><?= h($optionsText) ?></textarea>
            <small>Mỗi đáp án một dòng, tối thiểu 2 đáp án.</small>
          </div>

          <label for="help_text">Mô tả hướng dẫn <small>(không bắt buộc)</small></label>
          <textarea id="help_text" name="help_text" rows="3" placeholder="Gợi ý giúp người trả lời hiểu câu hỏi..."><?= h($help) ?></textarea>

          <label class="check-row"><input type="checkbox" name="required" <?= $required ? 'checked' : '' ?>> <span>Yêu cầu bắt buộc nhập</span></label>
          <p class="intro-note">Khi nhấn “Tạo biểu mẫu”, câu hỏi này sẽ được lưu cùng phiên bản đầu tiên.</p>
        </div>
      </div>
    </aside>
  </form>

  <footer class="footer">Formly · Công cụ tạo biểu mẫu có điều kiện</footer>
</div>
<script>
function toggleOptions() {
  const type = document.getElementById('field_type').value;
  document.getElementById('options-box').hidden = !['single_choice', 'multiple_choice'].includes(type);
}
toggleOptions();
</script>
</body>
</html>
