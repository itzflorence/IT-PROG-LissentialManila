<?php

declare(strict_types=1);

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function is_authenticated(): bool
{
    ensure_session_started();

    if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
        return false;
    }

    $userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $username = trim((string) $_SESSION['username']);
    $role = strtolower((string) $_SESSION['role']);

    return $userId !== false
        && $username !== ''
        && in_array($role, ['student', 'official', 'admin'], true);
}

function require_login(string $loginPath = '../auth/login.php'): void
{
    ensure_session_started();

    if (!is_authenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
}

//returns current session role, null when not logged in
function current_role(): ?string
{
    ensure_session_started();
 
    if (!is_authenticated()) {
        return null;
    }
 
    $role = (string) $_SESSION['role'];
    $canonical = ['student' => 'Student', 'official' => 'Official', 'admin' => 'Admin'];
 
    return $canonical[strtolower($role)] ?? null;
}

// guards official/admin only pages. if no permission, redirect to login or public feed
function require_role(array $allowedRoles, string $loginPath = '../auth/login.php', string $forbiddenPath = '../../index.php'): void
{
    ensure_session_started();
 
    if (!is_authenticated()) {
        header('Location: ' . $loginPath);
        exit;
    }
 
    $role = current_role();

    if ($role === 'Admin') {
        return;
    }
 
    if ($role === null || !in_array($role, $allowedRoles, true)) {
        header('Location: ' . $forbiddenPath);
        exit;
    }
}