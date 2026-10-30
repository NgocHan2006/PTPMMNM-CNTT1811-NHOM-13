<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
if(session_status()===PHP_SESSION_NONE)session_start();
if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Phương thức không hợp lệ.'); }
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$form_id = filter_input(INPUT_POST, 'form', FILTER_VALIDATE_INT) ?: 0;
$check = $pdo->prepare("SELECT fi.id FROM fields fi JOIN forms f ON f.current_version_id = fi.version_id WHERE fi.id = ? AND f.id = ?");
$check->execute([$id, $form_id]);
if (!$check->fetchColumn()) { header('Location: form_builder.php?id='.$form_id); exit; }
requireFormManager($pdo,$form_id); $qv=$pdo->prepare('SELECT status FROM form_versions WHERE id=(SELECT version_id FROM fields WHERE id=?)');$qv->execute([$id]);if($qv->fetchColumn()==='published'){http_response_code(409);exit('Phiên bản đã công bố không thể xóa câu hỏi trực tiếp. Hãy tạo phiên bản mới trước. <a href="form_builder.php?id='.$form_id.'">Quay lại</a>');}
try {
  $pdo->beginTransaction();
  $cd=$pdo->prepare('DELETE FROM conditions WHERE version_id=(SELECT version_id FROM fields WHERE id=?) AND (target_field_id=? OR source_field_id=?)'); $cd->execute([$id,$id,$id]);
  $del = $pdo->prepare("DELETE FROM fields WHERE id = ?");
  $del->execute([$id]); $pdo->commit();
  header('Location: form_builder.php?id='.$form_id.'&deleted=1'); exit;
} catch (PDOException $e) {
  if($pdo->inTransaction())$pdo->rollBack();
  http_response_code(409);
  exit('Không thể xóa câu hỏi này vì dữ liệu khác đang tham chiếu đến nó. Để giữ an toàn dữ liệu, hãy kiểm tra các câu trả lời/điều kiện liên quan trước khi xóa. <a href="form_builder.php?id='.$form_id.'">Quay lại</a>');
}
