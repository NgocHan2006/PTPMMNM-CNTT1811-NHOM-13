```php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function failSubmit(string $m, int $code = 400): never {
    http_response_code($code);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Thông báo</title><body>';
    echo '<p>' . h($m) . '</p>';
    echo '<p><a href="javascript:history.back()">Quay lại biểu mẫu</a></p>';
    echo '</body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    failSubmit('Phương thức không hợp lệ.', 405);
}

$formId = filter_input(INPUT_POST, 'form_id', FILTER_VALIDATE_INT);
$versionId = filter_input(INPUT_POST, 'version_id', FILTER_VALIDATE_INT);
$posted = $_POST['answers'] ?? [];

if (!$formId || !$versionId || !is_array($posted)) {
    failSubmit('Dữ liệu gửi lên không hợp lệ.');
}

/* 1. Kiểm tra biểu mẫu và phiên bản hiện tại */
$stmt = $pdo->prepare(
    'SELECT id, current_version_id, status
     FROM forms
     WHERE id = ?'
);
$stmt->execute([$formId]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (
    !$form ||
    $form['status'] !== 'published' ||
    (int)$form['current_version_id'] !== (int)$versionId
) {
    failSubmit(
        'Biểu mẫu không còn được công bố hoặc đã đổi phiên bản. Hãy tải lại trang.'
    );
}

/* 2. Lấy các trường thuộc phiên bản biểu mẫu */
$stmt = $pdo->prepare(
    'SELECT *
     FROM fields
     WHERE version_id = ?
     ORDER BY sort_order, id'
);
$stmt->execute([$versionId]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

$fieldByKey = [];

foreach ($fields as $field) {
    $fieldByKey[$field['field_key']] = $field;
}

/* Không chấp nhận trường không thuộc biểu mẫu */
foreach ($posted as $key => $value) {
    if (!isset($fieldByKey[$key])) {
        failSubmit('Có trường không hợp lệ trong dữ liệu gửi lên.');
    }
}

/* 3. Lấy điều kiện của phiên bản */
$stmt = $pdo->prepare(
    'SELECT c.*, t.field_key AS target_key, s.field_key AS source_key
     FROM conditions c
     JOIN fields t ON t.id = c.target_field_id
     JOIN fields s ON s.id = c.source_field_id
     WHERE c.version_id = ?'
);
$stmt->execute([$versionId]);
$conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* 4. Chuẩn hóa câu trả lời */
$values = [];

foreach ($fields as $field) {
    $key = $field['field_key'];
    $value = $posted[$key] ?? '';

    if ($field['field_type'] === 'multiple_choice') {
        if (!is_array($value)) {
            $value = ($value === '') ? [] : [$value];
        }

        foreach ($value as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                failSubmit('Dữ liệu lựa chọn không hợp lệ.');
            }
        }
    } elseif (is_array($value)) {
        failSubmit('Kiểu dữ liệu câu trả lời không hợp lệ.');
    }

    $values[$key] = $value;
}

/* 5. So sánh câu trả lời với điều kiện */
function conditionMatches(array $c, array $values): bool {
    $a = $values[$c['source_key']] ?? '';
    $raw = $c['comparison_value'] ?? ($c['compare_value'] ?? '');

    $decoded = json_decode((string)$raw, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        $b = $decoded;
    } else {
        $b = (string)$raw;
    }

    if (is_array($b)) {
        $b = (string)($b['value'] ?? '');
    }

    $empty = is_array($a)
        ? count($a) === 0
        : trim((string)$a) === '';

    $items = is_array($a)
        ? array_map('strval', $a)
        : [];

    $scalar = is_array($a)
        ? implode(',', $items)
        : (string)$a;

    $compare = (string)$b;

    switch ($c['operator']) {
        case 'equals':
            return is_array($a)
                ? in_array($compare, $items, true)
                : $scalar === $compare;

        case 'not_equals':
            return is_array($a)
                ? !in_array($compare, $items, true)
                : $scalar !== $compare;

        case 'contains':
            return is_array($a)
                ? in_array($compare, $items, true)
                : str_contains($scalar, $compare);

        case 'greater_than':
            return !$empty
                && is_numeric($scalar)
                && is_numeric($b)
                && (float)$scalar > (float)$b;

        case 'less_than':
            return !$empty
                && is_numeric($scalar)
                && is_numeric($b)
                && (float)$scalar < (float)$b;

        case 'is_empty':
            return $empty;

        case 'is_not_empty':
            return !$empty;

        default:
            return false;
    }
}

/* 6. Tính trạng thái hiển thị và bắt buộc */
$visible = [];
$required = [];
$showRules = [];

foreach ($fields as $field) {
    $key = $field['field_key'];
    $visible[$key] = true;
    $required[$key] = (bool)$field['is_required'];
}

foreach ($conditions as $condition) {
    $target = $condition['target_key'];
    $matched = conditionMatches($condition, $values);

    if ($condition['action'] === 'show') {
        $showRules[$target][] = $condition;
    } elseif ($condition['action'] === 'hide' && $matched) {
        $visible[$target] = false;
    } elseif ($condition['action'] === 'require' && $matched) {
        $required[$target] = true;
    }
}

/*
 * Nếu một trường có nhiều điều kiện show:
 * tất cả điều kiện show của trường đó phải đúng.
 * Điều kiện hide khớp sẽ tiếp tục ẩn trường.
 */
foreach ($showRules as $target => $rules) {
    $allMatched = true;

    foreach ($rules as $rule) {
        if (!conditionMatches($rule, $values)) {
            $allMatched = false;
            break;
        }
    }

    $visible[$target] = $visible[$target] && $allMatched;
}

/* 7. Kiểm tra câu trả lời trước khi lưu */
foreach ($fields as $field) {
    $key = $field['field_key'];
    $value = $values[$key];

    $empty = is_array($value)
        ? count($value) === 0
        : trim((string)$value) === '';

    /*
     * Trường bị ẩn sẽ không được kiểm tra bắt buộc
     * và không lưu câu trả lời.
     */
    if (!$visible[$key]) {
        $values[$key] = (
            $field['field_type'] === 'multiple_choice'
        ) ? [] : '';

        continue;
    }

    if ($required[$key] && $empty) {
        failSubmit('Vui lòng nhập trường bắt buộc: ' . $field['label']);
    }

    if ($empty) {
        continue;
    }

    $rules = json_decode(
        $field['validation_json'] ?? '{}',
        true
    ) ?: [];

    /* Kiểm tra kiểu số */
    if ($field['field_type'] === 'number') {
        if (!is_numeric($value)) {
            failSubmit('Trường ' . $field['label'] . ' phải là số.');
        }

        if (
            isset($rules['min']) &&
            (float)$value < (float)$rules['min']
        ) {
            failSubmit('Giá trị ' . $field['label'] . ' nhỏ hơn mức cho phép.');
        }

        if (
            isset($rules['max']) &&
            (float)$value > (float)$rules['max']
        ) {
            failSubmit('Giá trị ' . $field['label'] . ' lớn hơn mức cho phép.');
        }
    }

    /* Kiểm tra độ dài và email */
    if ($field['field_type'] === 'text') {
        if (isset($rules['minLength']) &&
            mb_strlen((string)$value) < (int)$rules['minLength']) {
            failSubmit('Trường ' . $field['label'] . ' quá ngắn.');
        }

        if (isset($rules['maxLength']) &&
            mb_strlen((string)$value) > (int)$rules['maxLength']) {
            failSubmit('Trường ' . $field['label'] . ' quá dài.');
        }

        if (
            ($rules['format'] ?? '') === 'email' &&
            !filter_var($value, FILTER_VALIDATE_EMAIL)
        ) {
            failSubmit('Email không hợp lệ tại trường ' . $field['label'] . '.');
        }
    }

    /* Kiểm tra ngày */
    if ($field['field_type'] === 'date') {
        $date = DateTime::createFromFormat('!Y-m-d', (string)$value);

        if (
            !$date ||
            $date->format('Y-m-d') !== (string)$value
        ) {
            failSubmit('Ngày không hợp lệ tại trường ' . $field['label'] . '.');
        }
    }

    /* Kiểm tra lựa chọn */
    if (
        $field['field_type'] === 'single_choice' &&
        is_array($value)
    ) {
        failSubmit('Chỉ được chọn một đáp án tại trường ' . $field['label'] . '.');
    }

    if (in_array(
        $field['field_type'],
        ['single_choice', 'multiple_choice'],
        true
    )) {
        $options = json_decode(
            $field['options_json'] ?? '[]',
            true
        ) ?: [];

        foreach ((array)$value as $choice) {
            if (!in_array($choice, $options, true)) {
                failSubmit('Lựa chọn không hợp lệ tại trường ' . $field['label'] . '.');
            }
        }
    }
}

/* 8. Lưu phản hồi và câu trả lời vào MySQL */
try {
    $pdo->beginTransaction();

    $respondent = isset($_SESSION['user_id'])
        ? (int)$_SESSION['user_id']
        : null;

    if (hasColumn($pdo, 'responses', 'respondent_id')) {
        $stmt = $pdo->prepare(
            "INSERT INTO responses
                (form_id, version_id, respondent_id, response_status)
             VALUES (?, ?, ?, 'submitted')"
        );

        $stmt->execute([$formId, $versionId, $respondent]);
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO responses
                (form_id, version_id, response_status)
             VALUES (?, ?, 'submitted')"
        );

        $stmt->execute([$formId, $versionId]);
    }

    $responseId = (int)$pdo->lastInsertId();
    $answerCols = tableColumns($pdo, 'answers');

    foreach ($fields as $field) {
        $key = $field['field_key'];

        if (!$visible[$key]) {
            continue;
        }

        $value = $values[$key];

        $empty = is_array($value)
            ? count($value) === 0
            : trim((string)$value) === '';

        if ($empty) {
            continue;
        }

        if (in_array('answer_json', $answerCols, true)) {
            $stmt = $pdo->prepare(
                'INSERT INTO answers
                    (response_id, version_id, field_id, answer_json)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->execute([
                $responseId,
                $versionId,
                $field['id'],
                json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ]);
        } else {
            $columns = ['response_id', 'field_id'];
            $params = [$responseId, $field['id']];

            $optional = [
                'field_key_snapshot' => $field['field_key'],
                'label_snapshot' => $field['label'],
                'field_type_snapshot' => $field['field_type'],
                'value_text' => is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : (string)$value,
                'value_json' => is_array($value)
                    ? json_encode($value, JSON_UNESCAPED_UNICODE)
                    : null
            ];

            foreach ($optional as $column => $val) {
                if (in_array($column, $answerCols, true)) {
                    $columns[] = $column;
                    $params[] = $val;
                }
            }

            if (in_array('version_id', $answerCols, true)) {
                $columns[] = 'version_id';
                $params[] = $versionId;
            }

            $columnNames = implode(
                ',',
                array_map(
                    fn($column) => '`' . $column . '`',
                    $columns
                )
            );

            $placeholders = implode(
                ',',
                array_fill(0, count($columns), '?')
            );

            $stmt = $pdo->prepare(
                "INSERT INTO answers ($columnNames)
                 VALUES ($placeholders)"
            );

            $stmt->execute($params);
        }
    }

    $pdo->commit();

    header('Location: index.php?response=sent');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('submit.php: ' . $e->getMessage());

    failSubmit(
        'Không thể lưu câu trả lời. Kiểm tra cấu trúc bảng answers và responses.',
        500
    );
}
