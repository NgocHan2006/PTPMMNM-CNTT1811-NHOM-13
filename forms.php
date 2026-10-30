
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$vcol = versionColumn($pdo);

// Hàm xử lý HTML
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$deleteError = '';

// XỬ LÝ XÓA BIỂU MẪU NGAY TRONG FILE NÀY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_form'])) {
    $formId = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
    $csrfToken = $_POST['csrf_token'] ?? '';

    try {
        // Kiểm tra dữ liệu gửi lên
        if (!$formId || $formId <= 0) {
            throw new Exception('Biểu mẫu không hợp lệ.');
        }

        // Kiểm tra CSRF
        if (
            !is_string($csrfToken) ||
            !hash_equals($_SESSION['csrf_token'], $csrfToken)
        ) {
            throw new Exception('Phiên xác thực không hợp lệ. Vui lòng tải lại trang.');
        }

        // Kiểm tra quyền quản lý biểu mẫu
        requireFormManager($pdo, $formId);

        $pdo->beginTransaction();

        // 1. Xóa câu trả lời chi tiết của các lượt trả lời
        $stmt = $pdo->prepare("
            DELETE a
            FROM answers a
            INNER JOIN responses r ON r.id = a.response_id
            WHERE r.form_id = ?
        ");
        $stmt->execute([$formId]);

        // 2. Xóa các lượt trả lời
        $stmt = $pdo->prepare("
            DELETE FROM responses
            WHERE form_id = ?
        ");
        $stmt->execute([$formId]);

        // 3. Xóa điều kiện của các phiên bản
        $stmt = $pdo->prepare("
            DELETE FROM conditions
            WHERE version_id IN (
                SELECT id
                FROM form_versions
                WHERE form_id = ?
            )
        ");
        $stmt->execute([$formId]);

        // 4. Xóa các trường của biểu mẫu
        $stmt = $pdo->prepare("
            DELETE FROM fields
            WHERE version_id IN (
                SELECT id
                FROM form_versions
                WHERE form_id = ?
            )
        ");
        $stmt->execute([$formId]);

        // 5. Bỏ liên kết phiên bản hiện tại trước khi xóa phiên bản
        $stmt = $pdo->prepare("
            UPDATE forms
            SET current_version_id = NULL
            WHERE id = ?
        ");
        $stmt->execute([$formId]);

        // 6. Xóa quyền được cấp cho biểu mẫu
        $stmt = $pdo->prepare("
            DELETE FROM form_permissions
            WHERE form_id = ?
        ");
        $stmt->execute([$formId]);

        // 7. Xóa các phiên bản
        $stmt = $pdo->prepare("
            DELETE FROM form_versions
            WHERE form_id = ?
        ");
        $stmt->execute([$formId]);

        // 8. Xóa biểu mẫu
        $stmt = $pdo->prepare("
            DELETE FROM forms
            WHERE id = ?
        ");
        $stmt->execute([$formId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Không tìm thấy biểu mẫu cần xóa.');
        }

        $pdo->commit();

        // Tải lại trang sau khi xóa thành công
        header('Location: forms.php?deleted=1');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $deleteError = $e->getMessage();
    }
}

// Lấy danh sách biểu mẫu, phiên bản hiện tại và số lượt trả lời
$sql = "
    SELECT
        f.*,
        COALESCE(v.{$vcol}, 0) AS version_no,
        (
            SELECT COUNT(*)
            FROM responses r
            WHERE r.form_id = f.id
        ) AS response_count
    FROM forms f
    LEFT JOIN form_versions v
        ON v.id = f.current_version_id
    ORDER BY f.id DESC
";

$forms = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tất cả biểu mẫu - Formly</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<aside class="sidebar">
    <a class="brand" href="index.php">
        <span class="brand-icon">F</span> formly
    </a>

    <div class="workspace">
        KHÔNG GIAN LÀM VIỆC <span>⌄</span>
    </div>

    <nav>
        <a class="nav-item" href="index.php">
            ▦ <span>Tổng quan</span>
        </a>

        <a class="nav-item active" href="forms.php">
            ▤ <span>Tất cả biểu mẫu</span>
        </a>

        <a class="nav-item" href="responses.php">
            ☷ <span>Câu trả lời</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <div class="avatar">AD</div>
        <div>
            <b>Quản trị viên</b>
            <small>Không gian cá nhân</small>
        </div>
    </div>
</aside>

<main class="main">

    <header class="topbar">
        <div>
            <span class="muted">Không gian làm việc</span>
            <span class="crumb"> / Tất cả biểu mẫu</span>
        </div>

        <a class="help-link" href="README.txt">Hướng dẫn</a>
    </header>

    <div class="page-title-row">
        <div>
            <div class="eyebrow">THƯ VIỆN</div>
            <h1>Tất cả biểu mẫu</h1>
            <p>Quản lý và xem trước các biểu mẫu hiện có.</p>
        </div>

        <a class="btn btn-primary" href="form_create.php">
            ＋ Tạo biểu mẫu
        </a>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">
            Xóa biểu mẫu thành công!
        </div>
    <?php endif; ?>

    <?php if ($deleteError !== ''): ?>
        <div class="alert alert-error">
            <?= h($deleteError) ?>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tên biểu mẫu</th>
                    <th>Trạng thái</th>
                    <th>Phiên bản</th>
                    <th>Câu trả lời</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>
            <?php if (empty($forms)): ?>

                <tr>
                    <td colspan="5" style="text-align:center;padding:35px">
                        Chưa có biểu mẫu nào.
                        <a href="form_create.php">Tạo biểu mẫu đầu tiên</a>
                    </td>
                </tr>

            <?php else: ?>

                <?php foreach ($forms as $f): ?>
                    <tr>
                        <td>
                            <b><?= h($f['title']) ?></b>
                            <div class="muted small">
                                <?= h($f['description'] ?? '') ?>
                            </div>
                        </td>

                        <td>
                            <span class="status <?= 
                                $f['status'] === 'published'
                                    ? 'published'
                                    : 'draft'
                            ?>">
                                <?=
                                    $f['status'] === 'published'
                                        ? 'Đã công bố'
                                        : ($f['status'] === 'archived'
                                            ? 'Đã lưu trữ'
                                            : 'Bản nháp')
                                ?>
                            </span>
                        </td>

                        <td>
                            v<?= (int)$f['version_no'] ?>
                        </td>

                        <td>
                            <?= (int)$f['response_count'] ?>
                        </td>

                        <td class="actions">
                            <a href="form_builder.php?id=<?= (int)$f['id'] ?>">
                                Thiết kế
                            </a>

                            <a href="fill.php?id=<?= (int)$f['id'] ?>">
                                Mở
                            </a>

                            <a href="responses.php?form_id=<?= (int)$f['id'] ?>">
                                Kết quả
                            </a>

                            <a href="version_new.php?id=<?= (int)$f['id'] ?>"
                               onclick="return confirm('Bạn có muốn tạo phiên bản mới cho biểu mẫu này không?')">
                                Tạo phiên bản mới
                            </a>

                            <!-- Nút xóa biểu mẫu -->
                            <form method="POST"
                                  action="forms.php"
                                  style="display:inline"
                                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa biểu mẫu này không? Tất cả câu trả lời và dữ liệu liên quan cũng sẽ bị xóa!')">

                                <input type="hidden"
                                       name="form_id"
                                       value="<?= (int)$f['id'] ?>">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= h($_SESSION['csrf_token']) ?>">

                                <button type="submit"
                                        name="delete_form"
                                        value="1"
                                        style="border:0;background:none;color:#dc2626;cursor:pointer;font:inherit;padding:0">
                                    Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer>
        Formly · Công cụ tạo biểu mẫu có điều kiện
    </footer>

</main>

</body>
</html>
