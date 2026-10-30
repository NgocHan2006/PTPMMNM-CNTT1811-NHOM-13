<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
$vcol = versionColumn($pdo);
$formId = isset($_GET['form_id']) ? max(0,(int)$_GET['form_id']) : 0;
if(session_status()===PHP_SESSION_NONE)session_start(); if(currentUserId()<=0){http_response_code(401);exit('Hãy đăng nhập để xuất dữ liệu.');} if($formId&&!canViewFormResponses($pdo,$formId,true)){http_response_code(403);exit('Bạn không có quyền xuất dữ liệu của biểu mẫu này.');}
if($formId){
    $where=' WHERE r.form_id=?'; $params=[$formId];
} else {
    $where=" WHERE (f.owner_id=? OR ?='admin' OR EXISTS (SELECT 1 FROM form_permissions px WHERE px.form_id=f.id AND px.user_id=? AND px.can_export=1))";
    $params=[currentUserId(),currentUserRole(),currentUserId()];
}
$answerCols=tableColumns($pdo,'answers');
if(in_array('answer_json',$answerCols,true)){$labelExpr='fi.label';$valueExpr='a.answer_json';$join=' LEFT JOIN answers a ON a.response_id=r.id LEFT JOIN fields fi ON fi.id=a.field_id AND fi.version_id=a.version_id';}
else {$labelExpr='a.label_snapshot';$valueExpr='a.value_text';$join=' LEFT JOIN answers a ON a.response_id=r.id';}
$sql="SELECT r.id AS response_id,f.title AS form_title,v.{$vcol} AS version_no,r.submitted_at,{$labelExpr} AS label_snapshot,{$valueExpr} AS value_text FROM responses r JOIN forms f ON f.id=r.form_id JOIN form_versions v ON v.id=r.version_id".$join.$where." ORDER BY r.id,a.id";
$stmt=$pdo->prepare($sql);$stmt->execute($params);
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="cau-tra-loi-'.date('Ymd-His').'.csv"');
$out=fopen('php://output','w'); fwrite($out, "\xEF\xBB\xBF");
fputcsv($out,['Ma luot','Bieu mau','Phien ban','Thoi gian gui','Truong','Cau tra loi']);
while($row=$stmt->fetch(PDO::FETCH_ASSOC)){$val=$row['value_text']??''; $decoded=json_decode((string)$val,true); if(is_array($decoded))$val=implode(', ',$decoded); fputcsv($out,[$row['response_id'],$row['form_title'],'v'.$row['version_no'],$row['submitted_at'],$row['label_snapshot'],$val]);}
fclose($out); exit;
