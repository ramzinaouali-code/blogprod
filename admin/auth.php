<?php
require_once dirname(__DIR__) . '/config.php';

function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
}

function is_admin_logged_in(): bool {
    admin_session_start();
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        header('Location: ' . BLOG_URL . '/admin/login.php');
        exit;
    }
}

function admin_logout(): void {
    admin_session_start();
    $_SESSION = [];
    session_destroy();
    header('Location: ' . BLOG_URL . '/admin/login.php');
    exit;
}

function set_flash(string $type, string $msg): void {
    admin_session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array {
    admin_session_start();
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function flash_html(): string {
    $f = get_flash();
    if (!$f) return '';
    $color = $f['type'] === 'success' ? '#2e7d32' : ($f['type'] === 'error' ? '#c62828' : '#1a73e8');
    return '<div class="flash" style="background:' . $color . '">' . htmlspecialchars($f['msg'], ENT_QUOTES, 'UTF-8') . '</div>';
}
