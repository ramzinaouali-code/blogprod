<?php
/**
 * PHP built-in server router — used by Railway (and local dev).
 * Blocks direct web access to sensitive directories/files,
 * then falls through to normal file serving.
 *
 * Start command: php -S 0.0.0.0:$PORT -t . router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ── Block sensitive paths ─────────────────────────────────────────────────────
$blocked_prefixes = ['/data/', '/API/', '/logs/'];
$blocked_files    = ['/config.php', '/db.php'];

foreach ($blocked_prefixes as $prefix) {
    if (str_starts_with($uri, $prefix)) {
        http_response_code(403);
        header('Content-Type: text/plain');
        die('403 Forbidden');
    }
}
foreach ($blocked_files as $file) {
    if ($uri === $file) {
        http_response_code(403);
        header('Content-Type: text/plain');
        die('403 Forbidden');
    }
}

// ── Serve static assets directly ─────────────────────────────────────────────
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // let PHP built-in server handle it
}

// ── Route directory requests to index.php ────────────────────────────────────
if (is_dir($file)) {
    $index = rtrim($file, '/') . '/index.php';
    if (file_exists($index)) {
        require $index;
        return true;
    }
}

// ── Fall through to normal PHP routing ───────────────────────────────────────
return false;
