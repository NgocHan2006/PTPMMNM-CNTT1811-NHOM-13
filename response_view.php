
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Kiểm tra đăng nhập
if (currentUserId() <= 0) {
    http_response_code(401);
    exit('Hãy đăng nhập để xem câu trả lời.');
}

// Lấy mã lượt trả lời từ URL
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    exit('Mã lượt trả lời không hợp lệ.');
}

// Xác định cột phiên bản
$vcol = versionColumn($pdo);

// Lấy thông tin lượt trả lời
$stmt = $pdo->prepare(
    "SELECT
        r.id,
        r.form_id,
        r.submitted_at,
        f.title,
        v.`{$vcol}` AS version_no
     FROM responses r
     JOIN forms f ON f.id = r.form_id
     JOIN form_versions v ON v.id = r.version_id
     WHERE r.id = ?
     LIMIT 1"
);

$stmt->execute([$id]);
$response = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$response) {
    http_response_code(404);
    exit('Không tìm thấy lượt trả lời.');
}

// Kiểm tra quyền xem câu trả lời của biểu mẫu
$responseForm = (int)$response['form_id'];

if (!canViewFormResponses($pdo, $responseForm, false)) {
    http_response_code(403);
    exit('Bạn không có quyền xem câu trả lời này.');
}

// Lấy câu trả lời theo cấu trúc bảng answers
$answerCols = tableColumns($pdo, 'answers');
$answers = [];

if (
    in_array('answer_json', $answerCols, true)
    && in_array('field_id', $answerCols, true)
    && in_array('version_id', $answerCols, true)
) {
    // Cấu trúc lưu câu trả lời bằng answer_json
    $stmt = $pdo->prepare(
        'SELECT
            fi.label AS label_snapshot,
            a.answer_json
         FROM answers a
         JOIN fields fi
            ON fi.id = a.field_id
           AND fi.version_id = a.version_id
         WHERE a.response_id = ?
         ORDER BY a.id'
    );

    $stmt->execute([$id]);
    $rawAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawAnswers as $a) {
        $value = json_decode(
            $a['answer_json'] ?? 'null',
            true
        );

        if (is_array($value)) {
            $valueText = implode(', ', array_map(
                static function ($item) {
                    return is_scalar($item) || $item === null
                        ? (string)$item
                        : json_encode(
                            $item,
                            JSON_UNESCAPED_UNICODE
                        );
                },
                $value
            ));
        } elseif ($value === null) {
            $valueText = '';
        } elseif (is_bool($value)) {
            $valueText = $value ? 'Có' : 'Không';
        } else {
            $valueText = (string)$value;
        }

        $answers[] = [
            'label_snapshot' => $a['label_snapshot'] ?? '',
            'value_text' => $valueText
        ];
    }
} else {
    // Cấu trúc lưu câu trả lời bằng label_snapshot/value_text
    $selectCols = ['id'];

    if (in_array('label_snapshot', $answerCols, true)) {
        $selectCols[] = 'label_snapshot';
    }

    if (in_array('value_text', $answerCols, true)) {
        $selectCols[] = 'value_text';
    }

    if (in_array('value_json', $answerCols, true)) {
        $selectCols[] = 'value_json';
    }

    $sql = 'SELECT ' . implode(', ', $selectCols)
         . ' FROM answers WHERE response_id = ? ORDER BY id';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $rawAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rawAnswers as $a) {
        $valueText = $a['value_text'] ?? '';

        if (
            $valueText === ''
            && isset($a['value_json'])
            && $a['value_json'] !== ''
        ) {
            $decoded = json_decode($a['value_json'], true);

            if (is_array($decoded)) {
                $valueText = implode(', ', array_map(
                    static function ($item) {
                        return is_scalar($item) || $item === null
                            ? (string)$item
                            : json_encode(
                                $item,
                                JSON_UNESCAPED_UNICODE
                            );
                    },
                    $decoded
                ));
            } elseif ($decoded !== null) {
                $valueText = (string)$decoded;
            } else {
                $valueText = $a['value_json'];
            }
        }

        $answers[] = [
            'label_snapshot' => $a['label_snapshot'] ?? '',
            'value_text' => $valueText
        ];
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chi tiết câu trả lời</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <main class="standalone">
        <a class="text-link" href="responses.php">
            ← Quay lại câu trả lời
        </a>

        <div class="eyebrow">
            CHI TIẾT PHẢN HỒI #<?= (int)$response['id'] ?>
        </div>

        <h1><?= h($response['title']) ?></h1>

        <p class="muted">
            Phiên bản v<?= (int)$response['version_no'] ?>
            · Gửi lúc <?= h($response['submitted_at']) ?>
        </p>

        <section class="answer-list">
            <?php if (empty($answers)): ?>
                <p class="muted">Lượt trả lời này chưa có dữ liệu.</p>
            <?php else: ?>
                <?php foreach ($answers as $a): ?>
                    <article class="answer-item">
                        <b><?= h($a['label_snapshot']) ?></b>

                        <div>
                            <?php if (trim((string)$a['value_text']) !== ''): ?>
                                <?= nl2br(h($a['value_text'])) ?>
                            <?php else: ?>
                                <span class="muted">
                                    Không có câu trả lời
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
