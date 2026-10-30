<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
$vcol = versionColumn($pdo);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$formId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$formId) { http_response_code(400); exit('Thiếu mã biểu mẫu.'); }

$stmt = $pdo->prepare("SELECT f.id, f.title, f.description, f.status, f.current_version_id, v.{$vcol} AS version_no FROM forms f LEFT JOIN form_versions v ON v.id=f.current_version_id WHERE f.id=?");
$stmt->execute([$formId]);
$form = $stmt->fetch();
if (!$form) { http_response_code(404); exit('Không tìm thấy biểu mẫu.'); }
if (empty($form['current_version_id'])) { exit('Biểu mẫu chưa có phiên bản.'); }
$versionId = (int)$form['current_version_id'];
$error = '';
$success = '';
$choiceTypes = ['single_choice', 'multiple_choice'];

function make_field_key(string $label): string {
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    $value = strtolower((string)$value);
    $value = preg_replace('/[^a-z0-9]+/', '_', $value);
    $value = trim((string)$value, '_');
    if ($value === '') $value = 'question';
    return substr($value, 0, 45);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    try {
        if ($action === 'delete') {
            $fieldId = filter_input(INPUT_POST, 'field_id', FILTER_VALIDATE_INT);
            if (!$fieldId) throw new RuntimeException('Mã câu hỏi không hợp lệ.');
            $stmt = $pdo->prepare('SELECT id FROM fields WHERE id=? AND version_id=?');
            $stmt->execute([$fieldId, $versionId]);
            if (!$stmt->fetch()) throw new RuntimeException('Không tìm thấy câu hỏi cần xóa.');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM conditions WHERE version_id=? AND (source_field_id=? OR target_field_id=?)');
            $stmt->execute([$versionId, $fieldId, $fieldId]);
            if ((int)$stmt->fetchColumn() > 0) throw new RuntimeException('Câu hỏi đang được dùng trong điều kiện. Hãy xóa điều kiện liên quan trước.');
            $stmt = $pdo->prepare('DELETE FROM fields WHERE id=? AND version_id=?');
            $stmt->execute([$fieldId, $versionId]);
            $success = 'Đã xóa câu hỏi.';
        } else {
            if ($form['status'] !== 'draft') throw new RuntimeException('Biểu mẫu đã xuất bản. Hãy tạo phiên bản nháp mới trước khi chỉnh sửa.');
            $label = trim($_POST['label'] ?? '');
            $fieldType = $_POST['field_type'] ?? 'text';
            $helpText = trim($_POST['help_text'] ?? '');
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            $optionsRaw = trim($_POST['options'] ?? '');
            $fieldKeyInput = trim($_POST['field_key'] ?? '');
            $allowedTypes = ['text', 'number', 'date', 'single_choice', 'multiple_choice'];
            if ($label === '') throw new RuntimeException('Vui lòng nhập nội dung câu hỏi.');
            if (mb_strlen($label) > 255) throw new RuntimeException('Câu hỏi tối đa 255 ký tự.');
            if (!in_array($fieldType, $allowedTypes, true)) throw new RuntimeException('Loại câu hỏi không hợp lệ.');
            $fieldKey = $fieldKeyInput !== '' ? $fieldKeyInput : make_field_key($label);
            $fieldKey = strtolower($fieldKey);
            if (!preg_match('/^[a-z][a-z0-9_]{0,59}$/', $fieldKey)) throw new RuntimeException('Mã câu hỏi chỉ gồm chữ thường, số, dấu gạch dưới và bắt đầu bằng chữ.');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM fields WHERE version_id=? AND field_key=?');
            $stmt->execute([$versionId, $fieldKey]);
            if ((int)$stmt->fetchColumn() > 0) throw new RuntimeException('Mã câu hỏi đã tồn tại. Hãy nhập mã khác.');
            $options = null;
            if (in_array($fieldType, $choiceTypes, true)) {
                $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $optionsRaw)), fn($v) => $v !== ''));
                if (count($options) < 2) throw new RuntimeException('Câu hỏi lựa chọn cần ít nhất 2 đáp án, mỗi đáp án một dòng.');
                $options = json_encode($options, JSON_UNESCAPED_UNICODE);
            }
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM fields WHERE version_id=?');
            $stmt->execute([$versionId]);
            $sortOrder = (int)$stmt->fetchColumn();
            $stmt = $pdo->prepare('INSERT INTO fields (version_id, field_key, label, field_type, help_text, is_required, sort_order, validation_json, options_json) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?)');
            $stmt->execute([$versionId, $fieldKey, $label, $fieldType, $helpText !== '' ? $helpText : null, $isRequired, $sortOrder, $options]);
            $success = 'Đã thêm câu hỏi vào biểu mẫu.';
        }
    } catch (Throwable $e) {
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Có lỗi khi lưu dữ liệu. Hãy kiểm tra cấu trúc bảng MySQL.';
    }
}
$stmt = $pdo->prepare('SELECT * FROM fields WHERE version_id=? ORDER BY sort_order, id');
$stmt->execute([$versionId]);
$fields = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Thiết kế biểu mẫu - <?=h($form['title'])?></title><link rel="stylesheet" href="assets/style.css"><style>
.builder-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(300px,.8fr);gap:22px;align-items:start}.panel{background:#fff;border:1px solid var(--line);border-radius:13px;padding:22px;margin-bottom:18px}.panel h2{font-size:17px;margin:0 0 7px}.panel p{font-size:12px;color:var(--muted);margin:0 0 16px}.builder-form{display:grid;gap:9px}.builder-form label{font-size:12px;font-weight:700;margin-top:7px}.builder-form input,.builder-form select,.builder-form textarea{width:100%}.builder-form textarea{resize:vertical}.check-row{display:flex;align-items:center;gap:8px;font-weight:500!important}.check-row input{width:auto}.field-card{border:1px solid var(--line);border-radius:10px;padding:15px;margin-top:11px}.field-card-top{display:flex;justify-content:space-between;gap:12px;align-items:start}.field-card h3{font-size:14px;margin:0 0 6px}.field-meta{font-size:11px;color:var(--muted)}.option-list{font-size:12px;color:#475467;margin-top:8px}.delete-button{border:1px solid #f3c7c7;background:#fff;color:#b42318;border-radius:7px;padding:7px 10px;cursor:pointer}.empty-state{padding:24px;border:1px dashed #d8dce5;border-radius:10px;text-align:center;color:var(--muted);font-size:12px}.notice{font-size:12px;padding:12px 14px;border-radius:9px;margin-bottom:16px}.notice.info{background:#eef4ff;color:#234b91}.notice.error{background:#fff0f0;color:#b42318}.notice.success{background:#e7f8ef;color:#087f5b}@media(max-width:900px){.builder-layout{grid-template-columns:1fr}}
</style></head><body>
<aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">F</span> formly</a><div class="workspace">KHÔNG GIAN LÀM VIỆC <span>⌄</span></div><nav><a class="nav-item" href="index.php">▦ <span>Tổng quan</span></a><a class="nav-item active" href="forms.php">▤ <span>Tất cả biểu mẫu</span></a><a class="nav-item" href="responses.php">☷ <span>Câu trả lời</span></a></nav><div class="sidebar-bottom"><div class="avatar">AD</div><div><b>Quản trị viên</b><small>Không gian cá nhân</small></div></div></aside>
<main class="main"><header class="topbar"><div><span class="muted">Tất cả biểu mẫu</span><span class="crumb"> / Thiết kế biểu mẫu</span></div><a class="help-link" href="README.txt">Hướng dẫn</a></header>
<div class="page-title-row"><div><div class="eyebrow">FORM BUILDER · PHIÊN BẢN v<?= (int)$form['version_no'] ?></div><h1><?=h($form['title'])?></h1><p><?=h($form['description'])?></p></div><a class="btn btn-secondary" href="fill.php?id=<?=$formId?>">Xem biểu mẫu ↗</a></div>
<?php if($error):?><div class="notice error"><?=h($error)?></div><?php endif;?><?php if($success):?><div class="notice success"><?=h($success)?></div><?php endif;?>
<?php if($form['status'] !== 'draft'):?><div class="notice info">Biểu mẫu này đang ở trạng thái <?=h($form['status'])?>. Để tránh sửa trực tiếp phiên bản đã công bố, hiện tại chỉ cho phép thêm câu hỏi vào biểu mẫu bản nháp.</div><?php endif;?>
<div class="builder-layout"><section class="panel"><h2>＋ Thêm câu hỏi</h2><p>Chọn loại câu hỏi và nhập thông tin cần thu thập.</p><form method="post" class="builder-form" id="add-field-form"><input type="hidden" name="action" value="add"><label for="label">Nội dung câu hỏi <span class="required">*</span></label><input id="label" name="label" maxlength="255" required placeholder="Ví dụ: Bạn đánh giá dịch vụ thế nào?"><label for="field_key">Mã câu hỏi (không bắt buộc)</label><input id="field_key" name="field_key" maxlength="60" pattern="[a-z][a-z0-9_]{0,59}" placeholder="Ví dụ: service_rating"><small class="muted">Để trống để hệ thống tự tạo mã từ câu hỏi.</small><label for="field_type">Loại câu hỏi</label><select id="field_type" name="field_type"><option value="text">Văn bản ngắn</option><option value="number">Số</option><option value="date">Ngày tháng</option><option value="single_choice">Chọn một đáp án</option><option value="multiple_choice">Chọn nhiều đáp án</option></select><div id="options-area" hidden><label for="options">Các đáp án (mỗi đáp án một dòng)</label><textarea id="options" name="options" rows="5" placeholder="Rất hài lòng&#10;Bình thường&#10;Chưa hài lòng"></textarea></div><label for="help_text">Mô tả / hướng dẫn (không bắt buộc)</label><textarea id="help_text" name="help_text" rows="2" maxlength="500" placeholder="Hướng dẫn người trả lời"></textarea><label class="check-row"><input type="checkbox" name="is_required" value="1"> Bắt buộc trả lời</label><button class="btn btn-primary" type="submit">Lưu câu hỏi</button></form></section>
<section class="panel"><h2>Câu hỏi trong biểu mẫu</h2><p><?=count($fields)?> câu hỏi · Thứ tự hiển thị từ trên xuống dưới.</p><?php if(!$fields):?><div class="empty-state">Chưa có câu hỏi nào. Hãy thêm câu hỏi đầu tiên ở bên trái.</div><?php else: foreach($fields as $i=>$field):?><div class="field-card"><div class="field-card-top"><div><h3><?=($i+1)?>. <?=h($field['label'])?><?=$field['is_required']?' <span class="required">*</span>':''?></h3><div class="field-meta">Mã: <?=h($field['field_key'])?> · Loại: <?=h($field['field_type'])?></div><?php if($field['help_text']):?><div class="field-meta" style="margin-top:5px"><?=h($field['help_text'])?></div><?php endif;?><?php $opts=json_decode($field['options_json']??'[]',true)?:[];if($opts):?><div class="option-list">Đáp án: <?=h(implode(' · ',$opts))?></div><?php endif;?></div><form method="post" onsubmit="return confirm('Bạn có chắc muốn xóa câu hỏi này?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="field_id" value="<?=(int)$field['id']?>"><button class="delete-button" type="submit">Xóa</button></form></div></div><?php endforeach; endif;?></section></div><footer>Formly · Công cụ tạo biểu mẫu có điều kiện</footer></main>
<script>
const typeSelect=document.getElementById('field_type');const optionsArea=document.getElementById('options-area');const optionsInput=document.getElementById('options');function updateOptions(){const isChoice=['single_choice','multiple_choice'].includes(typeSelect.value);optionsArea.hidden=!isChoice;optionsInput.required=isChoice;}typeSelect.addEventListener('change',updateOptions);updateOptions();
</script></body></html>
