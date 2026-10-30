<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, full_name, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: index.php');
                exit;
            }
            $error = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
        } catch (PDOException $e) {
            $error = 'Không thể đăng nhập. Vui lòng kiểm tra kết nối cơ sở dữ liệu.';
        }
    }
}
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Đăng nhập | Formly</title>
<link rel="stylesheet" href="assets/auth.css">
</head>
<body>
<main class="auth-layout">
    <aside class="brand-panel">
        <a class="brand" href="index.php"><span class="brand-mark">F</span><span>Formly</span></a>
        <div class="brand-content">
            <span class="eyebrow">QUẢN LÝ BIỂU MẪU THÔNG MINH</span>
            <h1>Ý tưởng của bạn.<br><span>Biểu mẫu của bạn.</span></h1>
            <p>Tạo biểu mẫu, thu thập câu trả lời và quản lý dữ liệu trong cùng một không gian làm việc.</p>
            <div class="preview-card">
                <div class="preview-top"><span class="preview-dot"></span><span class="preview-dot"></span><span class="preview-dot"></span><span class="preview-label">BẢN XEM TRƯỚC</span></div>
                <div class="preview-title">Khảo sát mức độ hài lòng</div>
                <div class="preview-line wide"></div><div class="preview-line"></div>
                <div class="preview-option"><span></span> Rất hài lòng</div>
                <div class="preview-option"><span></span> Hài lòng</div>
                <div class="preview-option"><span></span> Bình thường</div>
            </div>
        </div>
        <div class="brand-bottom"><span>✦</span> Đơn giản hóa việc thu thập thông tin</div>
        <div class="decor decor-one"></div><div class="decor decor-two"></div>
    </aside>

    <section class="form-panel">
        <div class="mobile-brand"><span class="brand-mark">F</span> Formly</div>
        <div class="form-wrap">
            <div class="form-heading">
                <div class="welcome-icon">↗</div>
                <p class="kicker">CHÀO MỪNG TRỞ LẠI</p>
                <h2>Đăng nhập tài khoản</h2>
                <p class="subheading">Nhập thông tin để tiếp tục công việc của bạn.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert" role="alert"><span class="alert-icon">!</span><span><?= h($error) ?></span></div>
            <?php endif; ?>

            <form method="post" action="login.php" autocomplete="on">
                <label for="username">Tên đăng nhập</label>
                <div class="input-wrap"><span class="input-icon">♙</span><input id="username" name="username" value="<?= h($username) ?>" placeholder="Nhập tên đăng nhập" autocomplete="username" required autofocus></div>

                <div class="label-row"><label for="password">Mật khẩu</label></div>
                <div class="input-wrap"><span class="input-icon">♙</span><input id="password" type="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required><button class="toggle-password" type="button" data-toggle="password" aria-label="Hiện hoặc ẩn mật khẩu">Hiện</button></div>

                <button class="submit-btn" type="submit">Đăng nhập <span>→</span></button>
            </form>
            <div class="divider"><span>HOẶC</span></div>
            <p class="switch-text">Bạn chưa có tài khoản? <a href="register.php">Tạo tài khoản mới</a></p>
            <p class="secure-note"><span>⌑</span> Thông tin tài khoản của bạn được bảo vệ.</p>
        </div>
        <footer class="form-footer"><span>© <?= date('Y') ?> Formly</span><a href="index.php">Về trang chủ</a></footer>
    </section>
</main>
<script>
document.querySelectorAll('[data-toggle]').forEach(function(button) {
    button.addEventListener('click', function() {
        const input = document.getElementById(button.dataset.toggle);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        button.textContent = show ? 'Ẩn' : 'Hiện';
    });
});
</script>
</body>
</html>
