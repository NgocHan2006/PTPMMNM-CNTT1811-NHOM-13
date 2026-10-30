
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

$vcol = versionColumn($pdo);

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Lấy mã biểu mẫu từ URL, ví dụ fill.php?id=1
$formId = isset($_GET['id']) ? filter_var(
    $_GET['id'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) : false;
$previewRequested = isset($_GET['preview']) && $_GET['preview'] === '1';

if ($formId === false || $formId === null) {
    http_response_code(400);
    exit('Thiếu hoặc sai mã biểu mẫu. Vui lòng mở đường dẫn dạng fill.php?id=1.');
}

// Lấy thông tin biểu mẫu và phiên bản hiện tại
$stmt = $pdo->prepare("
    SELECT
        f.id,
        f.title,
        f.description,
        f.status,
        f.current_version_id,
        v.{$vcol} AS version_no
    FROM forms f
    JOIN form_versions v ON v.id = f.current_version_id
    WHERE f.id = ?
");
$stmt->execute([$formId]);
$form = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$form) {
    http_response_code(404);
    exit('Không tìm thấy biểu mẫu hoặc biểu mẫu chưa có phiên bản hiện tại.');
}

// Bản nháp chỉ được xem trước bởi người quản lý, không thể gửi câu trả lời.
$isPreview = $form['status'] !== 'published';
if ($isPreview && (!$previewRequested || !canManageForm($pdo, (int)$formId))) {
    http_response_code(403);
    exit('Biểu mẫu này chưa được công bố.');
}

// Lấy danh sách câu hỏi
$stmt = $pdo->prepare("
    SELECT *
    FROM fields
    WHERE version_id = ?
    ORDER BY sort_order, id
");
$stmt->execute([$form['current_version_id']]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy các điều kiện hiển thị, ẩn và bắt buộc
$stmt = $pdo->prepare("
    SELECT
        c.*,
        t.field_key AS target_key,
        s.field_key AS source_key
    FROM conditions c
    JOIN fields t ON t.id = c.target_field_id
    JOIN fields s ON s.id = c.source_field_id
    WHERE c.version_id = ?
");
$stmt->execute([$form['current_version_id']]);
$conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($form['title']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="fill-page">
<main class="standalone form-standalone">
    <a class="text-link" href="forms.php">← Tất cả biểu mẫu</a>

    <div class="public-form-head">
        <div class="brand-icon">F</div>
        <div class="eyebrow">
            BIỂU MẪU · PHIÊN BẢN v<?= (int)$form['version_no'] ?>
        </div>
        <h1><?= h($form['title']) ?></h1>
        <p><?= h($form['description']) ?></p>
    </div>

        <?php if ($isPreview): ?>
            <div class="success">
                Đây là bản xem trước. Hãy công bố biểu mẫu để cho phép gửi câu trả lời.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['sent'])): ?>
        <div class="success">
            Gửi biểu mẫu thành công! Cảm ơn bạn đã cung cấp thông tin.
        </div>
    <?php endif; ?>

    <form action="submit.php" method="post"
          class="dynamic-form" id="dynamic-form"<?= $isPreview ? ' onsubmit="return false;"' : '' ?>>

        <input type="hidden" name="form_id"
               value="<?= (int)$form['id'] ?>">

        <input type="hidden" name="version_id"
               value="<?= (int)$form['current_version_id'] ?>">

        <?php foreach ($fields as $field):
            $opts = json_decode($field['options_json'] ?? '[]', true) ?: [];
            $validation = json_decode($field['validation_json'] ?? '{}', true) ?: [];
        ?>
            <div class="field-block"
                 data-field-key="<?= h($field['field_key']) ?>"
                 data-field-type="<?= h($field['field_type']) ?>"
                 data-required="<?= (int)$field['is_required'] ?>">

                <label>
                    <?= h($field['label']) ?>
                    <?= $field['is_required'] ? '<span class="required"> *</span>' : '' ?>
                </label>

                <?php if (!empty($field['help_text'])): ?>
                    <small class="field-help"><?= h($field['help_text']) ?></small>
                <?php endif; ?>

                <?php if ($field['field_type'] === 'text'): ?>
                    <input type="text"
                           name="answers[<?= h($field['field_key']) ?>]"
                        <?= isset($validation['maxLength']) ? 'maxlength="' . (int)$validation['maxLength'] . '"' : '' ?>
                        <?= isset($validation['minLength']) ? 'minlength="' . (int)$validation['minLength'] . '"' : '' ?>>

                <?php elseif ($field['field_type'] === 'number'): ?>
                    <input type="number"
                           name="answers[<?= h($field['field_key']) ?>]"
                        <?= isset($validation['min']) ? 'min="' . h($validation['min']) . '"' : '' ?>
                        <?= isset($validation['max']) ? 'max="' . h($validation['max']) . '"' : '' ?>>

                <?php elseif ($field['field_type'] === 'date'): ?>
                    <input type="date"
                           name="answers[<?= h($field['field_key']) ?>]">

                <?php elseif ($field['field_type'] === 'single_choice'): ?>
                    <select name="answers[<?= h($field['field_key']) ?>]">
                        <option value="">-- Chọn một đáp án --</option>
                        <?php foreach ($opts as $opt): ?>
                            <option value="<?= h($opt) ?>"><?= h($opt) ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ($field['field_type'] === 'multiple_choice'): ?>
                    <div class="checkbox-options">
                        <?php foreach ($opts as $opt): ?>
                            <label class="checkbox-label">
                                <input type="checkbox"
                                       name="answers[<?= h($field['field_key']) ?>][]"
                                       value="<?= h($opt) ?>">
                                <?= h($opt) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="field-error" aria-live="polite"></div>
            </div>
        <?php endforeach; ?>

        <?php if (!$isPreview): ?>
            <button class="btn btn-primary btn-submit" type="submit">
                Gửi câu trả lời →
            </button>
        <?php endif; ?>
    </form>

    <p class="privacy-note">
        Không chia sẻ thông tin nhạy cảm trong biểu mẫu thử nghiệm.
    </p>
</main>

<script>
const conditions = <?= json_encode(array_map(function ($c) {
    $raw = $c['comparison_value'] ?? ($c['compare_value'] ?? null);
    $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

    if (json_last_error() !== JSON_ERROR_NONE || $decoded === null) {
        $decoded = (string)($raw ?? '');
    }

    return [
        'target' => $c['target_key'],
        'source' => $c['source_key'],
        'operator' => $c['operator'],
        'value' => $decoded,
        'action' => $c['action']
    ];
}, $conditions), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function getValue(key) {
    const block = [...document.querySelectorAll('.field-block')]
        .find(b => b.dataset.fieldKey === key);

    if (!block) return '';

    const checkboxes = [...block.querySelectorAll('input[type="checkbox"]')];

    if (checkboxes.length) {
        return checkboxes.filter(x => x.checked).map(x => x.value);
    }

    const el = block.querySelector('input, select, textarea');
    return el ? el.value : '';
}

function conditionMatches(c) {
    const a = getValue(c.source);
    const b = c.value;
    const empty = Array.isArray(a)
        ? a.length === 0
        : String(a ?? '').trim() === '';

    switch (c.operator) {
        case 'equals':
            return Array.isArray(a)
                ? a.map(String).includes(String(b))
                : String(a) === String(b);

        case 'not_equals':
            return Array.isArray(a)
                ? !a.map(String).includes(String(b))
                : String(a) !== String(b);

        case 'contains':
            return Array.isArray(a)
                ? a.map(String).includes(String(b))
                : String(a).includes(String(b));

        case 'greater_than':
            return !empty && Number(a) > Number(b);

        case 'less_than':
            return !empty && Number(a) < Number(b);

        case 'is_empty':
            return empty;

        case 'is_not_empty':
            return !empty;

        default:
            return false;
    }
}

function updateConditions() {
    const blocks = [...document.querySelectorAll('.field-block')];
    const visibility = {};

    blocks.forEach(b => {
        visibility[b.dataset.fieldKey] = true;
    });

    // Xử lý điều kiện hiển thị
    conditions.forEach(c => {
        if (c.action === 'show' && !conditionMatches(c)) {
            visibility[c.target] = false;
        }

        if (c.action === 'hide' && conditionMatches(c)) {
            visibility[c.target] = false;
        }
    });

    // Nếu có nhiều quy tắc SHOW cho cùng một trường,
    // trường chỉ hiện khi tất cả quy tắc đều đúng.
    const showTargets = [
        ...new Set(
            conditions
                .filter(c => c.action === 'show')
                .map(c => c.target)
        )
    ];

    showTargets.forEach(target => {
        const rules = conditions.filter(
            c => c.action === 'show' && c.target === target
        );

        visibility[target] =
            rules.every(conditionMatches) &&
            visibility[target] !== false;
    });

    blocks.forEach(b => {
        const key = b.dataset.fieldKey;
        const hidden = visibility[key] === false;
        b.hidden = hidden;

        const controls = [...b.querySelectorAll('input, select, textarea')];
        const baseRequired = b.dataset.required === '1';

        const conditionalRequired = conditions.some(
            c => c.target === key &&
                 c.action === 'require' &&
                 conditionMatches(c)
        );

        const required = !hidden && (baseRequired || conditionalRequired);

        controls.forEach(el => {
            el.disabled = hidden;
            el.required = required && el.type !== 'checkbox';
        });

        const checkboxes = controls.filter(el => el.type === 'checkbox');

        if (checkboxes.length) {
            checkboxes.forEach((el, i) => {
                el.required = required && i === 0;
            });
        }

        const label = b.querySelector(':scope > label');

        if (label) {
            let star = label.querySelector('.dynamic-required');

            if (required && !star) {
                star = document.createElement('span');
                star.className = 'required dynamic-required';
                star.textContent = ' *';
                label.appendChild(star);
            } else if (!required && star) {
                star.remove();
            }
        }
    });
}

document.querySelectorAll(
    '#dynamic-form input, #dynamic-form select, #dynamic-form textarea'
).forEach(el => {
    el.addEventListener('change', updateConditions);
    el.addEventListener('input', updateConditions);
});

updateConditions();
</script>
</body>
</html>
