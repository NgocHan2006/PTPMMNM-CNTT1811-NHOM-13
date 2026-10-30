
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

$vcol = versionColumn($pdo);

// Hàm xử lý HTML
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
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

        <!-- Tạo biểu mẫu mới -->
        <a class="btn btn-primary" href="form_create.php">
            ＋ Tạo biểu mẫu
        </a>
    </div>

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
                            <!-- Thiết kế biểu mẫu -->
                            <a href="form_builder.php?id=<?= (int)$f['id'] ?>">
                                Thiết kế
                            </a>

                            <!-- Xem biểu mẫu -->
                            <a href="fill.php?id=<?= (int)$f['id'] ?>">
                                Mở
                            </a>

                            <!-- Xem kết quả -->
                            <a href="responses.php?form_id=<?= (int)$f['id'] ?>">
                                Kết quả
                            </a>

                            <!-- Tạo phiên bản mới -->
                            <a href="version_new.php?id=<?= (int)$f['id'] ?>"
                               onclick="return confirm('Bạn có muốn tạo phiên bản mới cho biểu mẫu này không?')">
                                Tạo phiên bản mới
                            </a>
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
