<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Phương thức không hợp lệ.');
}

$formIdValue = $_POST['id'] ?? null;
$formId = is_scalar($formIdValue)
    ? filter_var($formIdValue, FILTER_VALIDATE_INT)
    : false;

if ($formId === false || $formId === null || $formId < 1) {
    http_response_code(400);
    exit('Mã biểu mẫu không hợp lệ.');
}

if (
    empty($_SESSION['csrf_token'])
    || !hash_equals(
        $_SESSION['csrf_token'],
        (string)($_POST['csrf_token'] ?? '')
    )
) {
    http_response_code(400);
    exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
}

requireFormManager($pdo, $formId);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'SELECT status FROM forms WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$formId]);
    $status = $stmt->fetchColumn();

    if ($status === false) {
        throw new RuntimeException('Không tìm thấy biểu mẫu.');
    }

    if ($status !== 'published') {
        throw new RuntimeException('Biểu mẫu này hiện chưa được công bố.');
    }

    $stmt = $pdo->prepare(
        "UPDATE forms SET status = 'draft' WHERE id = ?"
    );
    $stmt->execute([$formId]);

    $pdo->commit();
    header('Location: forms.php?unpublished=1');
    exit;
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    exit(
        htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
        . ' <p><a href="forms.php">Quay lại danh sách biểu mẫu</a></p>'
    );
}
