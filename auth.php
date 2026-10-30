<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id' => (int) $_SESSION['user_id'],
        'full_name' => (string) ($_SESSION['full_name'] ?? ''),
        'username' => (string) ($_SESSION['username'] ?? ''),
        'role' => (string) ($_SESSION['role'] ?? 'respondent'),
    ];
}
