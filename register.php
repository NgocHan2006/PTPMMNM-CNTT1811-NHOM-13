<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$fullName = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $username === '' || $password === '' || $confirm === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (mb_strlen($fullName) > 150) {
        $error = 'Họ và tên không được vượt quá 150 ký tự.';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,80}$/', $username)) {
        $error = 'Tên đăng nhập cần có 3–80 ký tự, chỉ gồm chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
    } elseif (strlen($password) < 8) {
        $error = 'Mật khẩu cần có ít nhất 8 ký tự.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        try {
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password_hash, role) VALUES (?, ?, ?, 'designer')");
                $stmt->execute([$fullName, $username, $hash]);

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $pdo->lastInsertId();
                $_SESSION['full_name'] = $fullName;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'designer';

                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Không thể tạo tài khoản. Hãy kiểm tra cấu trúc bảng users trong cơ sở dữ liệu.';
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
<title>Đăng ký | Formly</title>
<link rel="stylesheet" href="assets/auth.css">
<link rel="stylesheet" href="assets/app.css">
</head>
<body>
<main class="auth-layout register-layout">
    <aside class="brand-panel">
        <a class="brand" href="index.php"><span class="brand-mark">F</span><span>Formly</span></a>
        <div class="brand-content">
            <span class="eyebrow">BẮT ĐẦU MIỄN PHÍ</span>
            <h1>Mọi câu trả lời<br><span>bắt đầu từ đây.</span></h1>
            <p>Tạo không gian làm việc của riêng bạn để xây dựng biểu mẫu và theo dõi dữ liệu dễ dàng hơn.</p>
            <div class="benefit-list">
                <div><span class="benefit-check">✓</span><span><b>Thiết kế linh hoạt</b><small>Tạo nhiều loại câu hỏi khác nhau</small></span></div>
                <div><span class="benefit-check">✓</span><span><b>Thu thập tập trung</b><small>Quản lý câu trả lời tại một nơi</small></span></div>
                <div><span class="benefit-check">✓</span><span><b>Dễ dàng sử dụng</b><small>Giao diện rõ ràng, trực quan</small></span></div>
            </div>
        </div>
        <div class="brand-bottom"><span>✦</span> Bắt đầu tạo biểu mẫu đầu tiên của bạn</div>
        <div class="decor decor-one"></div><div class="decor decor-two"></div>
    </aside>

    <section class="form-panel">
        <div class="mobile-brand"><span class="brand-mark">F</span> Formly</div>
        <div class="form-wrap register-wrap">
            <div class="form-heading">
                <div class="welcome-icon">＋</div>
                <p class="kicker">TẠO TÀI KHOẢN MỚI</p>
                <h2>Tham gia Formly</h2>
                <p class="subheading">Điền thông tin bên dưới để bắt đầu sử dụng.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert" role="alert"><span class="alert-icon">!</span><span><?= h($error) ?></span></div>
            <?php endif; ?>

            <form method="post" action="register.php" autocomplete="on">
                <label for="full_name">Họ và tên</label>
                <div class="input-wrap"><span class="input-icon">♙</span><input id="full_name" name="full_name" maxlength="150" value="<?= h($fullName) ?>" placeholder="Ví dụ: Nguyễn Minh Anh" autocomplete="name" required></div>

                <label for="username">Tên đăng nhập</label>
                <div class="input-wrap"><span class="input-icon">@</span><input id="username" name="username" value="<?= h($username) ?>" placeholder="Tạo tên đăng nhập" autocomplete="username" required minlength="3" maxlength="80"></div>

                <label for="password">Mật khẩu <span class="label-hint">Tối thiểu 8 ký tự</span></label>
                <div class="input-wrap"><span class="input-icon">♙</span><input id="password" type="password" name="password" placeholder="Tạo mật khẩu" autocomplete="new-password" minlength="8" required><button class="toggle-password" type="button" data-toggle="password">Hiện</button></div>

                <label for="confirm_password">Xác nhận mật khẩu</label>
                <div class="input-wrap"><span class="input-icon">♙</span><input id="confirm_password" type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" autocomplete="new-password" minlength="8" required><button class="toggle-password" type="button" data-toggle="confirm_password">Hiện</button></div>

                <label class="terms-row"><input type="checkbox" required> <span>Tôi đồng ý với các điều khoản sử dụng hệ thống.</span></label>
                <button class="submit-btn" type="submit">Tạo tài khoản <span>→</span></button>
            </form>
            <p class="switch-text register-switch">Bạn đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
            <p class="secure-note"><span>⌑</span> Mật khẩu được mã hóa trước khi lưu vào hệ thống.</p>
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
