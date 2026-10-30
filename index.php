
<?php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formCount = (int)$pdo->query("SELECT COUNT(*) FROM forms")->fetchColumn();
$activeFormCount = (int)$pdo->query("SELECT COUNT(*) FROM forms WHERE status='published'")->fetchColumn();
$responseCount = (int)$pdo->query("SELECT COUNT(*) FROM responses WHERE response_status='submitted'")->fetchColumn();
$fieldCount = (int)$pdo->query("SELECT COUNT(*) FROM fields")->fetchColumn();
$monthlyResponses = $pdo->query(
    "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS response_month, COUNT(*) AS total
     FROM responses
     WHERE response_status = 'submitted'
       AND submitted_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
     GROUP BY response_month"
)->fetchAll(PDO::FETCH_KEY_PAIR);
$responseMonths = [];
$monthCursor = new DateTimeImmutable('first day of -5 months');
$monthMaximum = 1;
for ($monthIndex = 0; $monthIndex < 6; $monthIndex++) {
    $monthKey = $monthCursor->format('Y-m');
    $monthTotal = (int)($monthlyResponses[$monthKey] ?? 0);
    $responseMonths[] = [
        'label' => $monthCursor->format('m/Y'),
        'total' => $monthTotal
    ];
    $monthMaximum = max($monthMaximum, $monthTotal);
    $monthCursor = $monthCursor->modify('+1 month');
}

$sql = "SELECT f.id, f.title, f.description, f.status, f.created_at, f.updated_at,
               COALESCE(v.version_no, 0) AS version_no,
               (SELECT COUNT(*) FROM responses r WHERE r.form_id=f.id AND r.response_status='submitted') AS response_count
        FROM forms f
        LEFT JOIN form_versions v ON v.id=f.current_version_id
        ORDER BY f.updated_at DESC, f.id DESC";
$forms = $pdo->query($sql)->fetchAll();

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Formly — Quản lý biểu mẫu</title>
<link rel="stylesheet" href="assets/style.css">
<link rel="stylesheet" href="assets/app.css">
<script src="assets/app.js" defer></script>
</head>
<body class="dashboard-page">

<aside class="sidebar">
  <a class="brand" href="index.php">
    <span class="brand-icon">F</span> formly
  </a>

  <div class="workspace">KHÔNG GIAN LÀM VIỆC <span>⌄</span></div>

  <nav>
    <a class="nav-item active" href="index.php">▦ <span>Tổng quan</span></a>
    <a class="nav-item" href="forms.php">▤ <span>Tất cả biểu mẫu</span></a>
    <a class="nav-item" href="responses.php">☷ <span>Câu trả lời</span></a>
  </nav>

  <div class="sidebar-bottom">
    <div class="avatar"><?= h(strtoupper(mb_substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'F', 0, 1))) ?></div>
    <div>
      <b><?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Tài khoản') ?></b>
      <small><?= h($_SESSION['role'] ?? 'Thành viên') ?></small>
    </div>
    <a href="logout.php"
       onclick="return confirm('Bạn có chắc chắn muốn đăng xuất?')"
       style="margin-left:auto; color:#dc2626; text-decoration:none; font-size:13px;">
      Đăng xuất
    </a>
  </div>
</aside>

<main class="main">
  <header class="topbar">
    <div>
      <span class="muted">Không gian làm việc</span>
      <span class="crumb"> / Tổng quan</span>
    </div>
    <a class="help-link" href="README.txt">Hướng dẫn</a>
  </header>

  <?php if (($_GET['response'] ?? '') === 'sent'): ?>
    <div class="alert alert-success toast-notice" role="status">
      Gửi câu trả lời thành công. Cảm ơn bạn đã chia sẻ thông tin.
    </div>
  <?php endif; ?>

  <section class="welcome">
    <div>
      <div class="eyebrow">TRUNG TÂM BIỂU MẪU</div>
      <h1>Chào mừng bạn trở lại 👋</h1>
      <p>Tạo biểu mẫu, thu thập câu trả lời và quản lý dữ liệu ở cùng một nơi.</p>
    </div>
    <a class="btn btn-primary" href="form_create.php">＋ Tạo biểu mẫu mới</a>
  </section>

  <section class="stats">
    <article class="stat-card">
      <div class="stat-label">Tổng biểu mẫu</div>
      <div class="stat-value"><?= $formCount ?></div>
      <div class="stat-note">Biểu mẫu trong hệ thống</div>
    </article>

    <article class="stat-card">
      <div class="stat-label">Đang hoạt động</div>
      <div class="stat-value"><?= $activeFormCount ?></div>
      <div class="stat-note">Biểu mẫu đã công bố</div>
    </article>

    <article class="stat-card">
      <div class="stat-label">Tổng phản hồi</div>
      <div class="stat-value"><?= $responseCount ?></div>
      <div class="stat-note">Lượt đã gửi thành công</div>
    </article>
  </section>

  <section class="dashboard-chart panel">
    <div class="section-heading">
      <div>
        <h2>Hoạt động phản hồi</h2>
        <p>Số câu trả lời đã gửi trong 6 tháng gần nhất</p>
      </div>
      <a class="text-link" href="responses.php">Xem câu trả lời →</a>
    </div>
    <div class="response-chart" role="img" aria-label="Biểu đồ số phản hồi theo tháng">
      <?php foreach ($responseMonths as $month): ?>
        <?php $barHeight = $month['total'] > 0 ? max(8, (int)round($month['total'] / $monthMaximum * 100)) : 3; ?>
        <div class="chart-column" aria-label="<?= h($month['label']) ?>: <?= $month['total'] ?> phản hồi">
          <span class="chart-value"><?= $month['total'] ?></span>
          <div class="chart-track"><span class="chart-bar" style="height:<?= $barHeight ?>%"></span></div>
          <span class="chart-label"><?= h($month['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section-heading">
    <div>
      <h2>Biểu mẫu gần đây</h2>
      <p>Tiếp tục quản lý những biểu mẫu của bạn.</p>
    </div>
    <a class="text-link" href="forms.php">Xem tất cả →</a>
  </section>

  <section class="form-grid">
  <?php foreach (array_slice($forms, 0, 6) as $i => $form): ?>
    <article class="form-card">

      <div class="form-card-top">
        <div class="form-icon icon-<?= $i % 4 ?>">▤</div>
        <span class="status <?= $form['status'] === 'published' ? 'published' : ($form['status'] === 'archived' ? 'archived' : 'draft') ?>">
          <?= $form['status'] === 'published'
              ? 'Đã công bố'
              : ($form['status'] === 'archived' ? 'Đã lưu trữ' : 'Bản nháp') ?>
        </span>
      </div>

      <h3><?= h($form['title']) ?></h3>

      <p class="form-description">
        <?= h($form['description'] ?: 'Chưa có mô tả cho biểu mẫu này.') ?>
      </p>

      <div class="card-meta">
        <span>Tạo <?= !empty($form['created_at']) ? h(date('d/m/Y', strtotime($form['created_at']))) : '—' ?></span>
        <span>Phiên bản <?= (int)$form['version_no'] ?></span>
        <span>↳ <?= (int)$form['response_count'] ?> câu trả lời</span>
      </div>

      <div class="card-actions">
        <a href="form_builder.php?id=<?= (int)$form['id'] ?>">
          ✏️ Thiết kế
        </a>

        <a href="fill.php?id=<?= (int)$form['id'] ?><?= $form['status'] === 'published' ? '' : '&amp;preview=1' ?>">
          <?= $form['status'] === 'published' ? 'Xem biểu mẫu' : 'Xem trước' ?>
        </a>

        <a href="responses.php?form_id=<?= (int)$form['id'] ?>">
          Kết quả →
        </a>
      </div>

    </article>
  <?php endforeach; ?>
  <?php if (!$forms): ?>
    <article class="dashboard-empty">
      <div class="empty-icon">▤</div>
      <h3>Không gian làm việc của bạn đang trống</h3>
      <p>Tạo biểu mẫu đầu tiên để bắt đầu thu thập câu trả lời.</p>
      <a class="btn btn-primary" href="form_create.php">＋ Tạo biểu mẫu</a>
    </article>
  <?php endif; ?>
  </section>

  <footer>
    Formly · Công cụ tạo biểu mẫu có điều kiện · Dữ liệu được lưu trong MySQL
  </footer>
</main>

</body>
</html>
