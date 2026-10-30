<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
if(session_status()===PHP_SESSION_NONE)session_start();
if (session_status() === PHP_SESSION_NONE) session_start();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$form_id = filter_input(INPUT_GET, 'form', FILTER_VALIDATE_INT) ?: 0;
$stmt = $pdo->prepare("SELECT fi.* FROM fields fi JOIN forms f ON f.current_version_id = fi.version_id WHERE fi.id = ? AND f.id = ?");
$stmt->execute([$id, $form_id]);
$field = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$field) { http_response_code(404); exit('Không tìm thấy câu hỏi thuộc phiên bản hiện tại của biểu mẫu.'); }
$qv=$pdo->prepare('SELECT form_id,status FROM form_versions WHERE id=?'); $qv->execute([$field['version_id']]); $ver=$qv->fetch(); if(!$ver){exit('Không tìm thấy phiên bản.');} requireFormManager($pdo,(int)$ver['form_id']); if(($ver['status']??'draft')==='published'){http_response_code(409);exit('Phiên bản đã công bố không thể sửa trực tiếp. Hãy tạo phiên bản mới trước. <a href="form_builder.php?id='.$form_id.'">Quay lại</a>');}
$error = '';
$allowed = ['text','number','date','single_choice','multiple_choice'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $label = trim($_POST['label'] ?? '');
  $type = $_POST['field_type'] ?? 'text';
  $required = isset($_POST['required']) ? 1 : 0;
  $help = trim($_POST['help_text'] ?? '');
  $min = trim($_POST['min'] ?? ''); $max = trim($_POST['max'] ?? ''); $minLength=trim($_POST['minLength']??''); $maxLength=trim($_POST['maxLength']??''); $format=$_POST['format']??'';
  $rules=[]; if($type==='number'){if($min!==''){if(!is_numeric($min))$error='Giá trị tối thiểu phải là số.';else $rules['min']=(float)$min;} if($max!==''){if(!is_numeric($max))$error='Giá trị tối đa phải là số.';else $rules['max']=(float)$max;} if(isset($rules['min'],$rules['max'])&&$rules['min']>$rules['max'])$error='Giá trị tối thiểu không được lớn hơn tối đa.';} if($type==='text'){if($minLength!=='')$rules['minLength']=max(0,(int)$minLength);if($maxLength!=='')$rules['maxLength']=max(0,(int)$maxLength);if($format==='email')$rules['format']='email';}
  $options_json = null;
  if ($label === '') $error = 'Vui lòng nhập nội dung câu hỏi.';
  elseif (!in_array($type, $allowed, true)) $error = 'Kiểu trường không hợp lệ.';
  else {
    if (in_array($type, ['single_choice','multiple_choice'], true)) {
      $options = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $_POST['options'] ?? '')), fn($v) => $v !== ''));
      if (count($options) < 2) $error = 'Cần ít nhất 2 đáp án.';
      else $options_json = json_encode($options, JSON_UNESCAPED_UNICODE);
    }
    if ($error === '') {
      $rulesJson=$rules?json_encode($rules,JSON_UNESCAPED_UNICODE):null;
      if(hasColumn($pdo,'fields','validation_json')){$up = $pdo->prepare("UPDATE fields SET label=?, field_type=?, help_text=?, is_required=?, options_json=?, validation_json=? WHERE id=?");$up->execute([$label,$type,$help?:null,$required,$options_json,$rulesJson,$id]);}else{$up = $pdo->prepare("UPDATE fields SET label=?, field_type=?, help_text=?, is_required=?, options_json=? WHERE id=?");$up->execute([$label, $type, $help ?: null, $required, $options_json, $id]);}
      header('Location: form_builder.php?id='.$form_id.'&updated=1'); exit;
    }
  }
  $field['label']=$label; $field['field_type']=$type; $field['help_text']=$help; $field['is_required']=$required;
  $field['options_json']=$options_json;
}
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$opts = json_decode($field['options_json'] ?? '[]', true);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sửa câu hỏi</title><link rel="stylesheet" href="assets/form_builder.css"><link rel="stylesheet" href="assets/app.css"></head><body class="field-edit-page">
<div class="page narrow"><header class="topbar"><a class="back-link" href="form_builder.php?id=<?= $form_id ?>">← Quay lại thiết kế</a></header>
<section class="panel edit-panel"><div class="panel-heading"><div><h1>Sửa câu hỏi</h1><p>Cập nhật nội dung và quy tắc của trường dữ liệu.</p></div></div>
<?php if ($error): ?><div class="notice error"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="builder-form">
<label for="label">Nội dung câu hỏi <span>*</span></label><input id="label" name="label" value="<?= h($field['label']) ?>" required maxlength="255">
<label for="field_type">Kiểu trường <span>*</span></label><select id="field_type" name="field_type" onchange="toggleOptions()">
<?php foreach (['text'=>'Văn bản','number'=>'Số','date'=>'Ngày','single_choice'=>'Một lựa chọn','multiple_choice'=>'Nhiều lựa chọn'] as $k=>$v): ?><option value="<?= $k ?>" <?= $field['field_type']===$k?'selected':'' ?>><?= h($v) ?></option><?php endforeach; ?>
</select>
<div id="options-box"><label for="options">Các đáp án (mỗi dòng một đáp án)</label><textarea id="options" name="options" rows="5"><?= h(is_array($opts)?implode("\n",$opts):'') ?></textarea></div>
<label for="help_text">Mô tả hướng dẫn</label><textarea id="help_text" name="help_text" rows="3"><?= h($field['help_text'] ?? '') ?></textarea>
<label class="check-row"><input type="checkbox" name="required" <?= !empty($field['is_required'])?'checked':'' ?>> <span>Yêu cầu bắt buộc nhập</span></label>
<div class="form-actions"><h3>Quy tắc kiểm tra dữ liệu</h3><?php $rulesNow=json_decode($field['validation_json']??'{}',true)?:[]; ?><div class="validation-grid"><label>Số tối thiểu<input type="number" step="any" name="min" value="<?= h($rulesNow['min']??'') ?>"></label><label>Số tối đa<input type="number" step="any" name="max" value="<?= h($rulesNow['max']??'') ?>"></label><label>Độ dài tối thiểu<input type="number" min="0" name="minLength" value="<?= h($rulesNow['minLength']??'') ?>"></label><label>Độ dài tối đa<input type="number" min="0" name="maxLength" value="<?= h($rulesNow['maxLength']??'') ?>"></label><label>Định dạng văn bản<select name="format"><option value="">Không yêu cầu</option><option value="email" <?= ($rulesNow['format']??'')==='email'?'selected':'' ?>>Email</option></select></label></div><button class="btn btn-primary" type="submit">Lưu thay đổi</button><a class="btn btn-light" href="form_builder.php?id=<?= $form_id ?>">Hủy</a></div>
</form></section></div>
<script>function toggleOptions(){const t=document.getElementById('field_type').value;document.getElementById('options-box').hidden=!['single_choice','multiple_choice'].includes(t)}toggleOptions();</script>
</body></html>
