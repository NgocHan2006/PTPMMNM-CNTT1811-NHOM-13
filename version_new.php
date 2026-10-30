
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/schema_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$formId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$formId || $formId < 1) {
    exit('Thiếu mã biểu mẫu.');
}

requireFormManager($pdo, $formId);

try {
    $pdo->beginTransaction();

    // 1. Lấy biểu mẫu và khóa bản ghi
    $stmt = $pdo->prepare(
        'SELECT * FROM forms WHERE id = ? FOR UPDATE'
    );
    $stmt->execute([$formId]);
    $form = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$form) {
        throw new RuntimeException('Không tìm thấy biểu mẫu.');
    }

    $oldVersionId = (int)($form['current_version_id'] ?? 0);

    if (!$oldVersionId) {
        throw new RuntimeException('Biểu mẫu chưa có phiên bản để sao chép.');
    }

    // 2. Xác định số phiên bản tiếp theo
    $versionColumn = versionColumn($pdo);

    $stmt = $pdo->prepare(
        "SELECT MAX(`$versionColumn`)
         FROM form_versions
         WHERE form_id = ?"
    );
    $stmt->execute([$formId]);

    $nextVersion = (int)$stmt->fetchColumn() + 1;

    // 3. Tạo phiên bản mới
    $versionColumns = tableColumns($pdo, 'form_versions');

    $insertColumns = ['form_id', $versionColumn];
    $values = [$formId, $nextVersion];

    if (in_array('status', $versionColumns, true)) {
        $insertColumns[] = 'status';
        $values[] = 'draft';
    }

    if (in_array('title_snapshot', $versionColumns, true)) {
        $insertColumns[] = 'title_snapshot';
        $values[] = $form['title'];
    }

    if (in_array('description_snapshot', $versionColumns, true)) {
        $insertColumns[] = 'description_snapshot';
        $values[] = $form['description'] ?? null;
    }

    $columnSql = implode(
        ', ',
        array_map(fn($c) => "`$c`", $insertColumns)
    );

    $placeholders = implode(
        ', ',
        array_fill(0, count($values), '?')
    );

    $stmt = $pdo->prepare(
        "INSERT INTO form_versions ($columnSql)
         VALUES ($placeholders)"
    );
    $stmt->execute($values);

    $newVersionId = (int)$pdo->lastInsertId();

    // 4. Sao chép các câu hỏi
    $fieldColumns = tableColumns($pdo, 'fields');

    $stmt = $pdo->prepare(
        'SELECT * FROM fields
         WHERE version_id = ?
         ORDER BY sort_order, id'
    );
    $stmt->execute([$oldVersionId]);

    $oldFields = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ánh xạ ID câu hỏi cũ sang ID câu hỏi mới
    $fieldMap = [];

    $copyableFields = [
        'field_key',
        'label',
        'field_type',
        'help_text',
        'is_required',
        'options_json',
        'validation_json',
        'sort_order'
    ];

    foreach ($oldFields as $field) {
        $columns = ['version_id'];
        $params = [$newVersionId];

        foreach ($copyableFields as $column) {
            if (in_array($column, $fieldColumns, true)) {
                $columns[] = $column;
                $params[] = $field[$column] ?? null;
            }
        }

        $columnSql = implode(
            ', ',
            array_map(fn($c) => "`$c`", $columns)
        );

        $placeholders = implode(
            ', ',
            array_fill(0, count($params), '?')
        );

        $stmt = $pdo->prepare(
            "INSERT INTO fields ($columnSql)
             VALUES ($placeholders)"
        );
        $stmt->execute($params);

        $fieldMap[(int)$field['id']] =
            (int)$pdo->lastInsertId();
    }

    // 5. Sao chép các điều kiện
    $conditionColumns = tableColumns($pdo, 'conditions');

    $stmt = $pdo->prepare(
        'SELECT * FROM conditions WHERE version_id = ?'
    );
    $stmt->execute([$oldVersionId]);

    $oldConditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($oldConditions as $condition) {
        $oldTarget = (int)$condition['target_field_id'];
        $oldSource = (int)$condition['source_field_id'];

        // Chỉ sao chép nếu cả hai câu hỏi đều tồn tại
        if (
            !isset($fieldMap[$oldTarget]) ||
            !isset($fieldMap[$oldSource])
        ) {
            continue;
        }

        $columns = [
            'version_id',
            'target_field_id',
            'source_field_id'
        ];

        $params = [
            $newVersionId,
            $fieldMap[$oldTarget],
            $fieldMap[$oldSource]
        ];

        foreach (
            ['operator', 'compare_value', 'comparison_value',
             'action', 'logic_group'] as $column
        ) {
            if (in_array($column, $conditionColumns, true)) {
                $columns[] = $column;
                $params[] = $condition[$column] ?? null;
            }
        }

        $columnSql = implode(
            ', ',
            array_map(fn($c) => "`$c`", $columns)
        );

        $placeholders = implode(
            ', ',
            array_fill(0, count($params), '?')
        );

        $stmt = $pdo->prepare(
            "INSERT INTO conditions ($columnSql)
             VALUES ($placeholders)"
        );
        $stmt->execute($params);
    }

    // 6. Chuyển biểu mẫu sang phiên bản mới
    $formColumns = tableColumns($pdo, 'forms');

    $updates = ['current_version_id = ?'];
    $updateParams = [$newVersionId];

    if (in_array('status', $formColumns, true)) {
        $updates[] = "status = 'draft'";
    }

    $updateParams[] = $formId;

    $stmt = $pdo->prepare(
        'UPDATE forms SET ' . implode(', ', $updates) .
        ' WHERE id = ?'
    );
    $stmt->execute($updateParams);

    $pdo->commit();

    header(
        'Location: form_builder.php?id=' .
        $formId . '&version_created=1'
    );
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);

    exit(
        htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ) .
        ' <p><a href="form_builder.php?id=' .
        (int)$formId .
        '">Quay lại</a></p>'
    );
}
