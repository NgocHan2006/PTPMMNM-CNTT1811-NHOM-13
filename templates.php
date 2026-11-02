<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';
require_once __DIR__ . '/auth.php';

if (currentUserId() <= 0) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$templates = [
    'co-quan' => [
        'label' => 'Cơ quan',
        'description' => 'Biểu mẫu tiếp nhận yêu cầu và phản hồi cho cơ quan, đơn vị.',
        'items' => [
            'event-feedback' => [
                'title' => 'Phản hồi về sự kiện',
                'description' => 'Ghi nhận ý kiến sau hội nghị, buổi họp hoặc sự kiện.',
                'icon' => '✦',
                'fields' => [
                    ['Mức độ hài lòng của bạn', 'single_choice', ['Rất hài lòng', 'Hài lòng', 'Bình thường', 'Chưa hài lòng']],
                    ['Nội dung góp ý', 'text'],
                    ['Bạn có muốn tham gia sự kiện tiếp theo?', 'single_choice', ['Có', 'Không']],
                ],
            ],
            'leave-request' => [
                'title' => 'Yêu cầu nghỉ phép',
                'description' => 'Thu thập thông tin và thời gian xin nghỉ phép.',
                'icon' => '▣',
                'fields' => [
                    ['Họ và tên', 'text'],
                    ['Ngày bắt đầu nghỉ', 'date'],
                    ['Ngày kết thúc nghỉ', 'date'],
                    ['Lý do xin nghỉ', 'text'],
                ],
            ],
            'service-request' => [
                'title' => 'Yêu cầu hỗ trợ công việc',
                'description' => 'Gửi yêu cầu xử lý công việc hoặc hỗ trợ nội bộ.',
                'icon' => '↗',
                'fields' => [
                    ['Họ và tên người yêu cầu', 'text'],
                    ['Nội dung yêu cầu', 'text'],
                    ['Mức độ ưu tiên', 'single_choice', ['Thấp', 'Bình thường', 'Cao', 'Khẩn cấp']],
                ],
            ],
        ],
    ],
    'giao-duc' => [
        'label' => 'Giáo dục',
        'description' => 'Mẫu khảo sát, đánh giá và kiểm tra phục vụ hoạt động giáo dục.',
        'items' => [
            'course-feedback' => [
                'title' => 'Đánh giá khóa học',
                'description' => 'Khảo sát chất lượng nội dung và hoạt động giảng dạy.',
                'icon' => '▤',
                'fields' => [
                    ['Tên khóa học', 'text'],
                    ['Đánh giá nội dung khóa học', 'single_choice', ['Rất tốt', 'Tốt', 'Đạt yêu cầu', 'Cần cải thiện']],
                    ['Góp ý cho giảng viên', 'text'],
                ],
            ],
            'student-quiz' => [
                'title' => 'Bài kiểm tra nhanh',
                'description' => 'Tạo khung bài kiểm tra nhanh với câu hỏi mẫu để chỉnh sửa.',
                'icon' => '✓',
                'fields' => [
                    ['Họ và tên học viên', 'text'],
                    ['Lớp học', 'text'],
                    ['Câu hỏi 1: Nội dung cần kiểm tra', 'text'],
                    ['Câu hỏi 2: Chọn đáp án', 'single_choice', ['Đáp án A', 'Đáp án B', 'Đáp án C']],
                ],
            ],
            'course-registration' => [
                'title' => 'Đăng ký khóa học',
                'description' => 'Tiếp nhận thông tin đăng ký tham gia khóa học.',
                'icon' => '＋',
                'fields' => [
                    ['Họ và tên', 'text'],
                    ['Email liên hệ', 'text'],
                    ['Khóa học muốn đăng ký', 'text'],
                    ['Ngày có thể bắt đầu', 'date'],
                ],
            ],
        ],
    ],
    'truong-hoc' => [
        'label' => 'Trường học',
        'description' => 'Biểu mẫu dùng trong lớp học và công tác quản lý nhà trường.',
        'items' => [
            'school-enrollment' => [
                'title' => 'Đăng ký nhập học',
                'description' => 'Thu thập thông tin học sinh và phụ huynh.',
                'icon' => '⌂',
                'fields' => [
                    ['Họ và tên học sinh', 'text'],
                    ['Ngày sinh', 'date'],
                    ['Lớp đăng ký', 'text'],
                    ['Họ tên phụ huynh', 'text'],
                    ['Số điện thoại liên hệ', 'text'],
                ],
            ],
            'parent-feedback' => [
                'title' => 'Ý kiến phụ huynh',
                'description' => 'Ghi nhận góp ý của phụ huynh về hoạt động nhà trường.',
                'icon' => '☷',
                'fields' => [
                    ['Họ tên phụ huynh', 'text'],
                    ['Lớp của học sinh', 'text'],
                    ['Nội dung góp ý', 'text'],
                    ['Nhà trường có thể liên hệ lại?', 'single_choice', ['Có', 'Không']],
                ],
            ],
            'absence-request' => [
                'title' => 'Đơn xin nghỉ học',
                'description' => 'Tiếp nhận thông tin xin nghỉ học của học sinh.',
                'icon' => '▧',
                'fields' => [
                    ['Họ và tên học sinh', 'text'],
                    ['Lớp', 'text'],
                    ['Ngày xin nghỉ', 'date'],
                    ['Lý do xin nghỉ', 'text'],
                ],
            ],
        ],
    ],
];

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = (string)($_POST['category'] ?? '');
    $templateKey = (string)($_POST['template'] ?? '');
    $csrfToken = (string)($_POST['csrf_token'] ?? '');

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(400);
        $error = 'Phiên làm việc không hợp lệ. Hãy tải lại trang và thử lại.';
    } elseif (!isset($templates[$category]['items'][$templateKey])) {
        http_response_code(400);
        $error = 'Mẫu biểu không hợp lệ.';
    } else {
        $template = $templates[$category]['items'][$templateKey];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "INSERT INTO forms (owner_id, title, description, status)
                 VALUES (?, ?, ?, 'draft')"
            );
            $stmt->execute([
                currentUserId(),
                $template['title'],
                $template['description'],
            ]);
            $formId = (int)$pdo->lastInsertId();

            $versionColumn = versionColumn($pdo);
            $versionColumns = tableColumns($pdo, 'form_versions');
            $insertColumns = ['form_id', $versionColumn, 'status'];
            $insertValues = [$formId, 1, 'draft'];
            if (in_array('title_snapshot', $versionColumns, true)) {
                $insertColumns[] = 'title_snapshot';
                $insertValues[] = $template['title'];
            }
            if (in_array('description_snapshot', $versionColumns, true)) {
                $insertColumns[] = 'description_snapshot';
                $insertValues[] = $template['description'];
            }

            $quotedColumns = implode(',', array_map(
                static fn($column) => '`' . $column . '`',
                $insertColumns
            ));
            $placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
            $stmt = $pdo->prepare(
                "INSERT INTO form_versions ($quotedColumns) VALUES ($placeholders)"
            );
            $stmt->execute($insertValues);
            $versionId = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'UPDATE forms SET current_version_id = ? WHERE id = ?'
            );
            $stmt->execute([$versionId, $formId]);

            $fieldColumns = tableColumns($pdo, 'fields');
            $fieldInsertColumns = ['version_id', 'field_key', 'label', 'field_type', 'sort_order'];
            foreach (['options_json', 'validation_json', 'help_text', 'is_required'] as $optionalColumn) {
                if (in_array($optionalColumn, $fieldColumns, true)) {
                    $fieldInsertColumns[] = $optionalColumn;
                }
            }
            $fieldSqlColumns = implode(',', array_map(
                static fn($column) => '`' . $column . '`',
                $fieldInsertColumns
            ));
            $fieldPlaceholders = implode(',', array_fill(0, count($fieldInsertColumns), '?'));
            $fieldStatement = $pdo->prepare(
                "INSERT INTO fields ($fieldSqlColumns) VALUES ($fieldPlaceholders)"
            );

            foreach ($template['fields'] as $index => $field) {
                [$label, $type] = $field;
                $options = $field[2] ?? null;
                $values = [
                    $versionId,
                    'field_' . ($index + 1),
                    $label,
                    $type,
                    $index + 1,
                ];
                foreach (array_slice($fieldInsertColumns, 5) as $optionalColumn) {
                    $values[] = match ($optionalColumn) {
                        'options_json' => $options === null
                            ? null
                            : json_encode($options, JSON_UNESCAPED_UNICODE),
                        'validation_json', 'help_text' => null,
                        'is_required' => 0,
                    };
                }
                $fieldStatement->execute($values);
            }

            $pdo->commit();
            header('Location: form_builder.php?id=' . $formId . '&created=1');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Unable to create form from template: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'Không thể tạo biểu mẫu từ mẫu này. Vui lòng thử lại.';
        }
    }
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mẫu biểu mẫu | Formly</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/app.css">
    <script src="assets/app.js" defer></script>
</head>
<body class="templates-page">
<aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-icon">F</span> formly</a>
    <div class="workspace">KHÔNG GIAN LÀM VIỆC <span>⌄</span></div>
    <nav>
        <a class="nav-item" href="index.php">▦ <span>Tổng quan</span></a>
        <a class="nav-item" href="forms.php">▤ <span>Tất cả biểu mẫu</span></a>
        <a class="nav-item active" href="templates.php">✦ <span>Mẫu biểu mẫu</span></a>
        <a class="nav-item" href="responses.php">☷ <span>Câu trả lời</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="avatar"><?= h(strtoupper(mb_substr($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'F', 0, 1))) ?></div>
        <div><b><?= h($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Tài khoản') ?></b><small><?= h($_SESSION['role'] ?? 'Thành viên') ?></small></div>
        <a href="logout.php" style="margin-left:auto;color:#dc2626;font-size:12px">Đăng xuất</a>
    </div>
</aside>
<main class="main">
    <header class="topbar">
        <div><span class="muted">Không gian làm việc</span><span class="crumb"> / Mẫu biểu mẫu</span></div>
        <a class="help-link" href="forms.php">Biểu mẫu của tôi</a>
    </header>
    <section class="page-title-row">
        <div>
            <div class="eyebrow">THƯ VIỆN MẪU</div>
            <h1>Chọn mẫu để bắt đầu</h1>
            <p>Mỗi mẫu sẽ tạo một biểu mẫu nháp để bạn tùy chỉnh trước khi công bố.</p>
        </div>
    </section>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= h($error) ?></div>
    <?php endif; ?>
    <?php foreach ($templates as $categoryKey => $category): ?>
        <section class="template-category" id="<?= h($categoryKey) ?>">
            <div class="section-heading">
                <div>
                    <h2><?= h($category['label']) ?></h2>
                    <p><?= h($category['description']) ?></p>
                </div>
            </div>
            <div class="template-gallery">
                <?php foreach ($category['items'] as $templateKey => $template): ?>
                    <article class="template-card">
                        <div class="template-preview template-preview-<?= h($categoryKey) ?>">
                            <span class="template-preview-brand">F</span>
                            <div class="template-preview-paper">
                                <span class="template-preview-icon"><?= h($template['icon']) ?></span>
                                <strong><?= h($template['title']) ?></strong>
                                <span class="template-preview-line"></span>
                                <span class="template-preview-line short"></span>
                                <span class="template-preview-choice"></span>
                                <span class="template-preview-choice"></span>
                            </div>
                        </div>
                        <div class="template-card-content">
                            <div>
                                <h3><?= h($template['title']) ?></h3>
                                <p><?= h($template['description']) ?></p>
                            </div>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                <input type="hidden" name="category" value="<?= h($categoryKey) ?>">
                                <input type="hidden" name="template" value="<?= h($templateKey) ?>">
                                <button class="btn btn-primary" type="submit">Dùng mẫu <span>→</span></button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    <?php require __DIR__ . '/includes/site_footer.php'; ?>
</main>
</body>
</html>
