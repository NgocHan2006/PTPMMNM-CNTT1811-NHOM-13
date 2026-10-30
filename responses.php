<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
$vcol = versionColumn($pdo);
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$formId = isset($_GET['form_id']) ? max(0, (int)$_GET['form_id']) : 0;
$searchId=trim($_GET['response_id']??''); $from=trim($_GET['from']??''); $to=trim($_GET['to']??'');
if(session_status()===PHP_SESSION_NONE) session_start();
if(currentUserId()<=0){http_response_code(401);exit('Hãy đăng nhập để xem câu trả lời.');}
$uid=currentUserId(); $role=currentUserRole();
$formsQ=$pdo->prepare("SELECT f.id,f.title FROM forms f LEFT JOIN form_permissions p ON p.form_id=f.id AND p.user_id=? WHERE f.owner_id=? OR ?='admin' OR p.can_view_responses=1 ORDER BY f.title");
$formsQ->execute([$uid,$uid,$role]); $forms=$formsQ->fetchAll();
$params=[$uid,$uid,$role];
$hasRespondentId = hasColumn($pdo, 'responses', 'respondent_id');
$respondentSelect = $hasRespondentId ? ", u.full_name AS respondent_name" : ", NULL AS respondent_name";
$respondentJoin = $hasRespondentId ? " LEFT JOIN users u ON u.id = r.respondent_id" : "";
$sql="SELECT r.id,r.form_id,r.version_id,r.submitted_at,r.response_status,f.title,v.{$vcol} AS version_no{$respondentSelect} FROM responses r JOIN forms f ON f.id=r.form_id JOIN form_versions v ON v.id=r.version_id LEFT JOIN form_permissions p ON p.form_id=f.id AND p.user_id=?{$respondentJoin} WHERE (f.owner_id=? OR ?='admin' OR p.can_view_responses=1)";
if($formId){if(!canViewFormResponses($pdo,$formId,false)){http_response_code(403);exit('Bạn không có quyền xem câu trả lời của biểu mẫu này.');}$sql.=' AND r.form_id=?';$params[]=$formId;}
if($searchId!=='' && ctype_digit($searchId)){$sql.=' AND r.id=?';$params[]=(int)$searchId;}
if($from!==''){$sql.=' AND DATE(r.submitted_at)>=?';$params[]=$from;}
if($to!==''){$sql.=' AND DATE(r.submitted_at)<=?';$params[]=$to;}
$sql.=' ORDER BY r.submitted_at DESC LIMIT 300';
$stmt = $pdo->prepare($sql); $stmt->execute($params); $responses = $stmt->fetchAll();
$responseTotal = count($responses);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Câu trả lời</title><link rel="stylesheet" href="assets/style.css"><link rel="stylesheet" href="assets/app.css"></head><body class="responses-page">
<aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">F</span> formly</a><div class="workspace">KHÔNG GIAN LÀM VIỆC <span>⌄</span></div><nav><a class="nav-item" href="index.php">▦ <span>Tổng quan</span></a><a class="nav-item" href="forms.php">▤ <span>Tất cả biểu mẫu</span></a><a class="nav-item active" href="responses.php">☷ <span>Câu trả lời</span></a></nav><div class="sidebar-bottom"><div class="avatar">AD</div><div><b>Quản trị viên</b><small>Không gian cá nhân</small></div></div></aside>
<main class="main"><header class="topbar"><div><span class="muted">Không gian làm việc</span><span class="crumb"> / Câu trả lời</span></div><a class="help-link" href="README.txt">Hướng dẫn</a></header>
<div class="page-title-row"><div><div class="eyebrow">DỮ LIỆU THU THẬP</div><h1>Câu trả lời</h1><p>Mỗi lượt trả lời được gắn với phiên bản biểu mẫu đã tiếp nhận.</p></div><a class="btn btn-primary" href="export.php<?= $formId ? '?form_id='.$formId : '' ?>">↓ Xuất CSV</a></div>
<section class="response-summary"><div class="response-summary-icon">↗</div><div><span>Đang hiển thị</span><strong><?= $responseTotal ?></strong><small>phản hồi phù hợp với bộ lọc · tối đa 300 gần nhất</small></div></section>
<form class="filter-bar" method="get"><label for="form_id">Lọc theo biểu mẫu</label><select id="form_id" name="form_id"><option value="0">Tất cả biểu mẫu</option><?php foreach($forms as $f):?><option value="<?=(int)$f['id']?>" <?=$formId===(int)$f['id']?'selected':''?>><?=h($f['title'])?></option><?php endforeach;?></select><label>Mã lượt <input type="number" min="1" name="response_id" value="<?=h($searchId)?>" placeholder="VD: 12"></label><label>Từ ngày <input type="date" name="from" value="<?=h($from)?>"></label><label>Đến ngày <input type="date" name="to" value="<?=h($to)?>"></label><button class="btn btn-secondary" type="submit">Lọc</button><?php if($formId || $searchId!=='' || $from!=='' || $to!==''):?><a class="btn btn-secondary" href="responses.php">Xóa bộ lọc</a><?php endif;?></form>
<div class="table-wrap"><table><thead><tr><th>Mã lượt</th><th>Biểu mẫu</th><th>Người gửi</th><th>Phiên bản</th><th>Thời gian gửi</th><th>Trạng thái</th><th>Chi tiết</th></tr></thead><tbody>
<?php foreach($responses as $r):?><tr><td><span class="response-id">#<?= (int)$r['id']?></span></td><td><b><?=h($r['title'])?></b></td><td><?=h($r['respondent_name'] ?: 'Khách')?></td><td>v<?= (int)$r['version_no']?></td><td><?=h(date('d/m/Y H:i', strtotime($r['submitted_at'])))?></td><td><span class="status <?= $r['response_status']==='submitted'?'published':'cancelled' ?>"><?= $r['response_status']==='submitted'?'Đã gửi':'Đã hủy'?></span></td><td><a class="response-detail-link" href="response_view.php?id=<?=(int)$r['id']?>">Xem đáp án →</a></td></tr><?php endforeach;?>
<?php if(!$responses):?><tr><td colspan="7"><div class="response-empty"><span class="empty-icon">☷</span><b>Chưa có phản hồi phù hợp</b><span>Thử điều chỉnh bộ lọc hoặc chia sẻ biểu mẫu đã công bố.</span></div></td></tr><?php endif;?>
</tbody></table></div><footer>Hiển thị tối đa 300 lượt gần nhất.</footer></main></body></html>
