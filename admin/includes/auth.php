<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['role'],
    ];
}

function requireLogin(): array {
    $user = currentUser();
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    return $user;
}

function requireRole(array $roles): array {
    $user = requireLogin();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden: insufficient permissions.';
        exit;
    }
    return $user;
}

function loginUser(array $row): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['role'] = $row['role'];
}

function logoutUser(): void {
    $_SESSION = [];
    session_destroy();
}
