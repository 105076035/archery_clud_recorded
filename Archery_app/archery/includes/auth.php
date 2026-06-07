<?php
// includes/auth.php

require_once __DIR__ . '/config.php';

function sessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function currentUser(): ?array {
    sessionStart();
    return $_SESSION['user'] ?? null;
}

function requireAuth(): array {
    $user = currentUser();
    if (!$user) {
        jsonResponse(['error' => 'Not authenticated'], 401);
    }
    return $user;
}

/**
 * Attempt login. Returns user array on success, null on failure.
 */
function attemptLogin(string $username, string $password): ?array {
    $stmt = getDB()->prepare(
        'SELECT ua.username, ua.password_hash, ua.role, ua.is_active,
                a.archer_id, a.first_name, a.last_name, a.club_id, a.gender
         FROM user_accounts ua
         LEFT JOIN archers a ON a.username = ua.username
         WHERE ua.username = ?'
    );
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row) return null;
    if (!$row['is_active']) return null;
    if (!password_verify($password, $row['password_hash'])) return null;

    return $row;
}

function loginUser(array $user): void {
    sessionStart();
    $_SESSION['user'] = [
        'username'   => $user['username'],
        'role'       => $user['role'],
        'archer_id'  => $user['archer_id'],
        'first_name' => $user['first_name'],
        'last_name'  => $user['last_name'],
        'club_id'    => $user['club_id'],
        'gender'     => $user['gender'],
    ];
}

function logoutUser(): void {
    sessionStart();
    session_destroy();
}
