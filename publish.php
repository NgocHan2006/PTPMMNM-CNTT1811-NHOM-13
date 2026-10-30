<?php
require_once __DIR__.'/config/database.php';require_once __DIR__.'/includes/schema_helpers.php';
if(session_status()===PHP_SESSION_NONE)session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Phương thức không hợp lệ.'); }
$formIdValue = $_POST['id'] ?? null;
$formId = is_scalar($formIdValue) ? filter_var($formIdValue, FILTER_VALIDATE_INT) : false;
if ($formId === false || $formId === null || $formId < 1) { http_response_code(400); exit('Mã biểu mẫu không hợp lệ hoặc không được gửi lên. Vui lòng tải lại trang thiết kế và thử lại.'); }
requireFormManager($pdo,$formId);
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) { http_response_code(400); exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.'); }
try{$q=$pdo->prepare('SELECT id,current_version_id FROM forms WHERE id=?');$q->execute([$formId]);$form=$q->fetch();if(!$form||!$form['current_version_id'])throw new RuntimeException('Không tìm thấy phiên bản hiện tại.');
$q=$pdo->prepare('SELECT * FROM fields WHERE version_id=? ORDER BY sort_order,id');$q->execute([$form['current_version_id']]);$fields=$q->fetchAll();if(!$fields)throw new RuntimeException('Thêm ít nhất một câu hỏi trước khi công bố.');
foreach($fields as $f){if(in_array($f['field_type'],['single_choice','multiple_choice'],true)){$opts=json_decode($f['options_json']??'[]',true)?:[];if(count($opts)<2)throw new RuntimeException('Câu hỏi lựa chọn “'.$f['label'].'” cần ít nhất 2 đáp án.');}}
$pdo->beginTransaction();$q=$pdo->prepare("UPDATE forms SET status='published' WHERE id=?");$q->execute([$formId]);if(hasColumn($pdo,'form_versions','status')){$q=$pdo->prepare("UPDATE form_versions SET status='published' WHERE id=?");$q->execute([$form['current_version_id']]);}if(hasColumn($pdo,'form_versions','published_at')){$q=$pdo->prepare('UPDATE form_versions SET published_at=NOW() WHERE id=?');$q->execute([$form['current_version_id']]);}$pdo->commit();header('Location: forms.php?published=1');exit;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(400);exit(htmlspecialchars($e->getMessage(),ENT_QUOTES,'UTF-8').' <p><a href="form_builder.php?id='.$formId.'">Quay lại thiết kế</a></p>');}
