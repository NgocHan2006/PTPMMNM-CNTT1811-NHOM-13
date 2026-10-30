
<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
// Khởi tạo session trước khi đọc thông tin đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

$vcol = versionColumn($pdo);

// Lấy ID biểu mẫu từ URL
$form_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;

if ($form_id <= 0) {
    http_response_code(400);
    exit('ID biểu mẫu không hợp lệ.');
}

// Kiểm tra đăng nhập
$uid = currentUserId();
$role = currentUserRole();

if ($uid <= 0) {
    exit(
        'Bạn chưa đăng nhập hoặc hệ thống chưa nhận diện được tài khoản. '
        . '<a href="login.php">Đăng nhập lại</a>.'
    );
}

// Kiểm tra quyền quản lý biểu mẫu
requireFormManager($pdo, $form_id);

// Lấy thông tin biểu mẫu và phiên bản hiện tại
$stmt = $pdo->prepare(
    "SELECT f.*, v.id AS version_id, v.{$vcol} AS version_no
     FROM forms f
     LEFT JOIN form_versions v ON v.id = f.current_version_id
     WHERE f.id = ?"
);
$stmt->execute([$form_id]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    http_response_code(404);
    exit('Không tìm thấy biểu mẫu.');
}

$version_id = (int)($form['version_id'] ?? 0);

if (!$version_id) {
    exit(
        'Biểu mẫu chưa có phiên bản hiện tại. '
        . 'Vui lòng kiểm tra current_version_id.'
    );
}

// Xử lý thêm hoặc xóa câu hỏi
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_field') {
            $label = trim($_POST['label'] ?? '');
            $field_type = $_POST['field_type'] ?? 'text';
            $help_text = trim($_POST['help_text'] ?? '');
            $is_required = isset($_POST['is_required']) ? 1 : 0;

            $allowed_types = [
                'text',
                'number',
                'date',
                'single_choice',
                'multiple_choice'
            ];

            if ($label === '') {
                throw new Exception('Vui lòng nhập nội dung câu hỏi.');
            }

            if (!in_array($field_type, $allowed_types, true)) {
                throw new Exception('Loại câu hỏi không hợp lệ.');
            }

            $options_json = null;

            if (in_array($field_type, ['single_choice', 'multiple_choice'], true)) {
                $options_text = trim($_POST['options'] ?? '');
                $options = array_values(array_filter(
                    array_map('trim', preg_split('/\r\n|\r|\n/', $options_text)),
                    function ($value) {
                        return $value !== '';
                    }
                ));

                if (count($options) < 2) {
                    throw new Exception(
                        'Câu hỏi lựa chọn cần ít nhất 2 phương án, mỗi phương án một dòng.'
                    );
                }

                $options_json = json_encode(
                    $options,
                    JSON_UNESCAPED_UNICODE
                );
            }

            // Tạo mã câu hỏi duy nhất
            $field_key = 'field_' . bin2hex(random_bytes(6));

            // Xác định thứ tự câu hỏi tiếp theo
            $stmt = $pdo->prepare(
                'SELECT COALESCE(MAX(sort_order), 0) + 1
                 FROM fields WHERE version_id = ?'
            );
            $stmt->execute([$version_id]);
            $sort_order = (int)$stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'INSERT INTO fields
                (version_id, field_key, label, field_type,
                 help_text, is_required, sort_order, options_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $version_id,
                $field_key,
                $label,
                $field_type,
                $help_text !== '' ? $help_text : null,
                $is_required,
                $sort_order,
                $options_json
            ]);

            $message = 'Đã thêm câu hỏi thành công.';
        } elseif ($action === 'delete_field') {
            $field_id = filter_var(
                $_POST['field_id'] ?? null,
                FILTER_VALIDATE_INT
            );

            if (!$field_id || $field_id <= 0) {
                throw new Exception('ID câu hỏi không hợp lệ.');
            }

            // Chỉ xóa câu hỏi thuộc phiên bản đang mở
            $stmt = $pdo->prepare(
                'SELECT id FROM fields WHERE id = ? AND version_id = ?'
            );
            $stmt->execute([$field_id, $version_id]);

            if (!$stmt->fetchColumn()) {
                throw new Exception('Không tìm thấy câu hỏi cần xóa.');
            }

            // Xóa điều kiện liên quan đến câu hỏi này
            $stmt = $pdo->prepare(
                'DELETE FROM conditions
                 WHERE version_id = ?
                   AND (source_field_id = ? OR target_field_id = ?)'
            );
            $stmt->execute([$version_id, $field_id, $field_id]);

            $stmt = $pdo->prepare(
                'DELETE FROM fields WHERE id = ? AND version_id = ?'
            );
            $stmt->execute([$field_id, $version_id]);

            $message = 'Đã xóa câu hỏi.';
        }
    } catch (Throwable $e) {
        $error = $e instanceof PDOException
            ? 'Không thể lưu dữ liệu. Vui lòng kiểm tra cấu trúc bảng và thử lại.'
            : $e->getMessage();
    }
}

// Lấy danh sách câu hỏi
$stmt = $pdo->prepare(
    'SELECT * FROM fields
     WHERE version_id = ?
     ORDER BY sort_order ASC, id ASC'
);
$stmt->execute([$version_id]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$field_type_labels = [
    'text' => 'Văn bản',
    'number' => 'Số',
    'date' => 'Ngày',
    'single_choice' => 'Trắc nghiệm một lựa chọn',
    'multiple_choice' => 'Trắc nghiệm nhiều lựa chọn'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thiết kế biểu mẫu</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 24px;
            background: #f3f5f9;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header, .panel, .field-card {
            background: #fff;
            border-radius: 12px;
            padding: 22px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
        }

        h1, h2 {
            margin-top: 0;
        }

        .muted {
            color: #64748b;
            font-size: 14px;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-weight: 600;
        }

        input[type="text"], textarea, select {
            width: 100%;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        .required {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 16px 0;
        }

        .required label {
            margin: 0;
        }

        button {
            border: 0;
            border-radius: 7px;
            padding: 11px 16px;
            cursor: pointer;
            font-size: 14px;
        }

        .primary {
            background: #2563eb;
            color: #fff;
        }

        .danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .notice {
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 16px;
        }

        .success {
            background: #dcfce7;
            color: #166534;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
        }

        .field-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .field-top h3 {
            margin: 0 0 8px;
        }

        .option-list {
            margin: 10px 0;
            padding-left: 22px;
        }

        .actions {
            margin-top: 15px;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .field-top {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
<div class="container">

    <div class="header">
        <p class="muted">CÔNG CỤ TẠO BIỂU MẪU</p>
        <h1><?= h($form['title']) ?></h1>

        <?php if (!empty($form['description'])): ?>
            <p><?= nl2br(h($form['description'])) ?></p>
        <?php endif; ?>

        <p class="muted">
            Phiên bản: <?= h($form['version_no']) ?>
            · Trạng thái: <?= h($form['status']) ?>
        </p>
        <div class="actions" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
            <a class="btn btn-primary" href="conditions.php?id=<?= (int)$formId ?>">⚙ Thiết lập điều kiện</a>
            <a class="btn btn-secondary" href="fill.php?id=<?= (int)$formId ?>" target="_blank" rel="noopener">Xem trước biểu mẫu</a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div class="notice success"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="panel">
        <h2>Thêm câu hỏi</h2>

        <form method="post">
            <input type="hidden" name="action" value="add_field">

            <label for="label">Nội dung câu hỏi *</label>
            <input
                type="text"
                id="label"
                name="label"
                maxlength="255"
                required
                placeholder="Ví dụ: Bạn đánh giá dịch vụ như thế nào?"
            >

            <label for="field_type">Loại câu hỏi</label>
            <select id="field_type" name="field_type">
                <option value="text">Văn bản</option>
                <option value="number">Số</option>
                <option value="date">Ngày</option>
                <option value="single_choice">Trắc nghiệm một lựa chọn</option>
                <option value="multiple_choice">Trắc nghiệm nhiều lựa chọn</option>
            </select>

            <div id="options_box" hidden>
                <label for="options">
                    Các phương án trả lời (mỗi phương án một dòng)
                </label>
                <textarea
                    id="options"
                    name="options"
                    placeholder="Rất hài lòng&#10;Hài lòng&#10;Bình thường&#10;Không hài lòng"
                ></textarea>
            </div>

            <label for="help_text">Hướng dẫn trả lời (không bắt buộc)</label>
            <input
                type="text"
                id="help_text"
                name="help_text"
                maxlength="500"
                placeholder="Ví dụ: Chọn phương án phù hợp nhất"
            >

            <div class="required">
                <input type="checkbox" id="is_required" name="is_required">
                <label for="is_required">Bắt buộc trả lời</label>
            </div>

            <button type="submit" class="primary">+ Thêm câu hỏi</button>
        </form>
    </div>

    <div class="panel">
        <h2>Danh sách câu hỏi (<?= count($fields) ?>)</h2>

        <?php if (count($fields) === 0): ?>
            <p class="muted">
                Chưa có câu hỏi nào. Hãy sử dụng biểu mẫu phía trên để thêm câu hỏi đầu tiên.
            </p>
        <?php else: ?>
            <?php foreach ($fields as $index => $field): ?>
                <div class="field-card">
                    <div class="field-top">
                        <div>
                            <h3>
                                <?= $index + 1 ?>.
                                <?= h($field['label']) ?>
                                <?= (int)$field['is_required'] === 1 ? '*' : '' ?>
                            </h3>

                            <p class="muted">
                                Loại:
                                <?= h($field_type_labels[$field['field_type']]
                                    ?? $field['field_type']) ?>
                            </p>

                            <?php if (!empty($field['help_text'])): ?>
                                <p><?= h($field['help_text']) ?></p>
                            <?php endif; ?>

                            <?php
                            $options = json_decode(
                                $field['options_json'] ?? '',
                                true
                            );
                            ?>

                            <?php if (is_array($options)): ?>
                                <ul class="option-list">
                                    <?php foreach ($options as $option): ?>
                                        <li><?= h($option) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <form method="post"
                              onsubmit="return confirm('Bạn có chắc muốn xóa câu hỏi này?');">
                            <input type="hidden" name="action" value="delete_field">
                            <input
                                type="hidden"
                                name="field_id"
                                value="<?= (int)$field['id'] ?>"
                            >
                            <button type="submit" class="danger">Xóa</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<script>
    const typeSelect = document.getElementById('field_type');
    const optionsBox = document.getElementById('options_box');
    const optionsInput = document.getElementById('options');

    function updateOptionsVisibility() {
        const isChoice = [
            'single_choice',
            'multiple_choice'
        ].includes(typeSelect.value);

        optionsBox.hidden = !isChoice;
        optionsInput.required = isChoice;
    }

    typeSelect.addEventListener('change', updateOptionsVisibility);
    updateOptionsVisibility();
</script>
</body>
</html>
