
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lấy danh sách cột của bảng.
 */
function tableColumns(PDO $pdo, string $table): array {
    static $cache = [];

    if (!isset($cache[$table])) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Tên bảng không hợp lệ.');
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");

        $cache[$table] = array_column(
            $stmt->fetchAll(PDO::FETCH_ASSOC),
            'Field'
        );
    }

    return $cache[$table];
}

/**
 * Kiểm tra một cột có tồn tại hay không.
 */
function hasColumn(PDO $pdo, string $table, string $column): bool {
    return in_array($column, tableColumns($pdo, $table), true);
}

/**
 * Xác định tên cột số phiên bản.
 */
function versionColumn(PDO $pdo): string {
    return hasColumn($pdo, 'form_versions', 'version_number')
        ? 'version_number'
        : 'version_no';
}

/**
 * Hàm thoát HTML.
 */
if (!function_exists('h')) {
    function h($v): string {
        return htmlspecialchars(
            (string)$v,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/**
 * Lấy ID người dùng từ session.
 */
function currentUserId(): int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $id = $_SESSION['user_id']
        ?? $_SESSION['id']
        ?? $_SESSION['user']['id']
        ?? 0;

    return is_numeric($id) ? (int)$id : 0;
}

/**
 * Lấy vai trò người dùng từ session.
 */
function currentUserRole(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    return strtolower(trim((string)(
        $_SESSION['role']
        ?? $_SESSION['user_role']
        ?? $_SESSION['user']['role']
        ?? ''
    )));
}

/**
 * Kiểm tra quyền quản lý biểu mẫu.
 * Chỉ admin hoặc chủ sở hữu được quản lý.
 */
function canManageForm(PDO $pdo, int $formId): bool {
    $uid = currentUserId();

    if ($uid <= 0 || $formId <= 0) {
        return false;
    }

    // Chỉ cấp quyền toàn hệ thống cho vai trò admin.
    if (currentUserRole() === 'admin') {
        return true;
    }

    $stmt = $pdo->prepare(
        'SELECT owner_id
         FROM forms
         WHERE id = ?
         LIMIT 1'
    );

    $stmt->execute([$formId]);
    $ownerId = $stmt->fetchColumn();

    return $ownerId !== false
        && $ownerId !== null
        && (int)$ownerId === $uid;
}

/**
 * Kiểm tra quyền xem hoặc xuất câu trả lời.
 */
function canViewFormResponses(
    PDO $pdo,
    int $formId,
    bool $export = false
): bool {
    if (canManageForm($pdo, $formId)) {
        return true;
    }

    $uid = currentUserId();

    if ($uid <= 0 || $formId <= 0) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT can_view_responses, can_export
             FROM form_permissions
             WHERE form_id = ?
               AND user_id = ?
             LIMIT 1'
        );

        $stmt->execute([$formId, $uid]);
        $permission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$permission) {
            return false;
        }

        $column = $export
            ? 'can_export'
            : 'can_view_responses';

        return isset($permission[$column])
            && (int)$permission[$column] === 1;

    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Chặn người không có quyền quản lý.
 */
function requireFormManager(PDO $pdo, int $formId): void {
    if (!canManageForm($pdo, $formId)) {
        http_response_code(403);

        exit(
            'Bạn không có quyền quản lý biểu mẫu này. '
            . 'Hãy đăng nhập bằng tài khoản chủ sở hữu '
            . 'hoặc quản trị viên.'
        );
    }
}
