
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xóa toàn bộ dữ liệu đăng nhập trong session
$_SESSION = [];

// Xóa cookie session nếu đang sử dụng cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hủy session đăng nhập
session_destroy();

// Chuyển về trang chủ
header('Location: index.php?logout=success');
exit;
