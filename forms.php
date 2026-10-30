<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$vcol = versionColumn($pdo);
if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$deleteError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form'])) {
    $formId = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
    try {
        if (!$formId || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
            throw new RuntimeException('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
        }
        requireFormManager($pdo, $formId);
        $pdo->beginTransaction();

        // Xóa các bản ghi con trước để tôn trọng khóa ngoại.
        $q = $pdo->prepare("DELETE a FROM answers a JOIN responses r ON r.id=a.response_id WHERE r.form_id=?");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM responses WHERE form_id=?");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM conditions WHERE version_id IN (SELECT id FROM form_versions WHERE form_id=?)");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM fields WHERE version_id IN (SELECT id FROM form_versions WHERE form_id=?)");
        $q->execute([$formId]);
        $q = $pdo->prepare("UPDATE forms SET current_version_id=NULL WHERE id=?");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM form_permissions WHERE form_id=?");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM form_versions WHERE form_id=?");
        $q->execute([$formId]);
        $q = $pdo->prepare("DELETE FROM forms WHERE id=?");
        $q->execute([$formId]);
        if (!$q->rowCount()) throw new RuntimeException('Không tìm thấy biểu mẫu cần xóa.');
        $pdo->commit();
        header('Location: forms.php?deleted=1');
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $deleteError = $e->getMessage();
    }
}

$sql = "SELECT f.*, COALESCE(v.{$vcol},0) AS version_no,
        (SELECT COUNT(*) FROM responses r WHERE r.form_id=f.id) AS response_count
        FROM forms f LEFT JOIN form_versions v ON v.id=f.current_version_id
        ORDER BY f.id DESC";
$forms = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Biểu mẫu — Formly</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/app.css">
<script src="assets/app.js" defer></script>
</head>
<body class="forms-page">
<aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-icon">F</span> formly</a>
    <div class="workspace">KHÔNG GIAN LÀM VIỆC <span>⌄</span></div>
    <nav>
        <a class="nav-item" href="index.php">▦ <span>Tổng quan</span></a>
        <a class="nav-item active" href="forms.php">▤ <span>Biểu mẫu</span></a>
        <a class="nav-item" href="responses.php">☷ <span>Câu trả lời</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="avatar"><?= h(strtoupper(substr($_SESSION['username'] ?? 'F',0,1))) ?></div>
        <div><b><?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Tài khoản') ?></b><small><?= h($_SESSION['role'] ?? 'Thành viên') ?></small></div>
        <a href="logout.php" style="margin-left:auto;color:#ef4444;font-size:12px">Thoát</a>
    </div>
</aside>

<main class="main">
<header class="topbar">
    <div><span class="muted">Không gian làm việc</span><span class="crumb"> / Biểu mẫu</span></div>
    <a class="help-link" href="README.txt">Hướng dẫn</a>
</header>

<section class="page-title-row">
    <div><div class="eyebrow">WORKSPACE / FORMS</div><h1>Quản lý biểu mẫu</h1>
    <p>Tạo, chỉnh sửa và theo dõi các biểu mẫu của bạn.</p></div>
    <a class="btn btn-primary" href="form_create.php">＋ Tạo biểu mẫu</a>
</section>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success toast-notice" role="status">Biểu mẫu đã được xóa thành công.</div><?php endif; ?>
<?php if (isset($_GET['published'])): ?><div class="alert alert-success toast-notice" role="status">Biểu mẫu đã được công bố. Bạn có thể mở để trả lời câu hỏi.</div><?php endif; ?>
<?php if ($deleteError !== ''): ?><div class="alert alert-error"><?= h($deleteError) ?></div><?php endif; ?>

<form class="filter-bar" onsubmit="return false">
    <label for="formSearch">Tìm biểu mẫu</label>
    <input id="formSearch" type="search" placeholder="Nhập tên biểu mẫu..." style="min-width:230px;flex:1">
    <label for="statusFilter">Trạng thái</label>
    <select id="statusFilter">
        <option value="">Tất cả trạng thái</option>
        <option value="published">Đã công bố</option>
        <option value="draft">Bản nháp</option>
        <option value="archived">Đã lưu trữ</option>
    </select>
    <span class="muted" id="formCount"><?= count($forms) ?> biểu mẫu</span>
</form>

<div class="table-wrap">
<table>
<thead><tr><th>Biểu mẫu</th><th>Trạng thái</th><th>Ngày tạo</th><th>Phiên bản</th><th>Phản hồi</th><th>Thao tác</th></tr></thead>
<tbody id="formsTable">
<?php if (!$forms): ?>
<tr><td colspan="6"><div class="empty-state">
    <div class="empty-icon">▤</div><h3>Chưa có biểu mẫu nào</h3>
    <p>Bắt đầu bằng cách tạo biểu mẫu đầu tiên để thu thập dữ liệu.</p>
    <a class="btn btn-primary" href="form_create.php">＋ Tạo biểu mẫu</a>
</div></td></tr>
<?php else: foreach ($forms as $f):
    $status = (string)$f['status'];
    $statusLabel = $status === 'published' ? 'Đã công bố' : ($status === 'archived' ? 'Đã lưu trữ' : 'Bản nháp');
?>
<tr class="form-row" data-title="<?= h(mb_strtolower($f['title'])) ?>" data-status="<?= h($status) ?>">
    <td><b><?= h($f['title']) ?></b><div class="muted small"><?= h($f['description'] ?? 'Chưa có mô tả') ?></div></td>
    <td><span class="status <?= $status === 'published' ? 'published' : ($status === 'archived' ? 'archived' : 'draft') ?>"><?= h($statusLabel) ?></span></td>
    <td><?= !empty($f['created_at']) ? h(date('d/m/Y', strtotime($f['created_at']))) : '—' ?></td>
    <td>v<?= (int)$f['version_no'] ?></td>
    <td><?= (int)$f['response_count'] ?></td>
    <td class="actions">
        <a class="row-primary-action" href="form_builder.php?id=<?= (int)$f['id'] ?>">Mở thiết kế <span aria-hidden="true">→</span></a>
        <details class="action-menu">
            <summary aria-label="Thao tác với <?= h($f['title']) ?>">•••</summary>
            <div class="action-menu-panel">
                <a href="fill.php?id=<?= (int)$f['id'] ?><?= $status === 'published' ? '' : '&amp;preview=1' ?>"><?= $status === 'published' ? 'Mở biểu mẫu' : 'Xem trước' ?></a>
                <a href="responses.php?form_id=<?= (int)$f['id'] ?>">Xem câu trả lời</a>
                <a href="permissions.php?id=<?= (int)$f['id'] ?>">Phân quyền</a>
                <a href="version_new.php?id=<?= (int)$f['id'] ?>" data-confirm="Tạo phiên bản mới cho biểu mẫu này?">Tạo phiên bản mới</a>
                <form method="post" action="forms.php" data-confirm="Xóa biểu mẫu này cùng toàn bộ phản hồi và dữ liệu liên quan?">
                    <input type="hidden" name="form_id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="delete_form" value="1">Xóa biểu mẫu</button>
                </form>
            </div>
        </details>
    </td>
</tr>
<?php endforeach; endif; ?>
<tr id="noMatch" hidden><td colspan="6" style="text-align:center;padding:28px;color:#64748b">Không tìm thấy biểu mẫu phù hợp.</td></tr>
</tbody></table></div>
<footer>Formly · Quản lý biểu mẫu và dữ liệu thu thập</footer>
</main>
<script>
(() => {
 const search = document.getElementById('formSearch');
 const status = document.getElementById('statusFilter');
 const rows = [...document.querySelectorAll('.form-row')];
 const noMatch = document.getElementById('noMatch');
 const count = document.getElementById('formCount');
 function filterRows() {
   const q = search.value.trim().toLocaleLowerCase('vi');
   let visible = 0;
   rows.forEach(row => {
     const match = row.dataset.title.includes(q) && (!status.value || row.dataset.status === status.value);
     row.hidden = !match;
     if (match) visible++;
   });
   if (noMatch) noMatch.hidden = visible !== 0 || rows.length === 0;
   count.textContent = visible + ' biểu mẫu';
 }
 search.addEventListener('input', filterRows);
 status.addEventListener('change', filterRows);
})();
</script>
</body></html>
