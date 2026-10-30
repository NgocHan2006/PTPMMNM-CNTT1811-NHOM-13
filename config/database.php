<?php
// Cấu hình kết nối MySQL cho XAMPP.
// Nếu MySQL của bạn có mật khẩu, hãy điền vào $password.
$host = '127.0.0.1';
$dbname = 'quanlybieumau';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Không thể kết nối MySQL. Hãy kiểm tra config/database.php và trạng thái MySQL trong XAMPP.');
}
