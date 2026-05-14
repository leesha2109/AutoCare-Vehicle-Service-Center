<?php
// includes/auth.php

function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Call at top of any page that needs login
function require_login() {
    start_session();
    if (empty($_SESSION['user_id'])) {
        header('Location: /autocare/views/auth/login.php');
        exit;
    }
}

// Call at top of admin-only pages
function require_role(string $role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        die('<h2>403 — Access denied.</h2>');
    }
}

// Check if logged in (returns true/false, no redirect)
function is_logged_in(): bool {
    start_session();
    return !empty($_SESSION['user_id']);
}

// Get current user's role
function current_role(): string {
    start_session();
    return $_SESSION['role'] ?? '';
}

// Logout — destroy everything
function logout() {
    start_session();
    $_SESSION = [];
    session_destroy();
    // Also clear remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    header('Location: /autocare/views/auth/login.php');
    exit;
}

// Generate CSRF token — call at top of every form page
function csrf_token(): string {
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token — call at top of every POST handler
function csrf_verify(): void {
    if (empty($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        http_response_code(419);
        die('<h2>Invalid request. Please go back and try again.</h2>');
    }
}
?>