<?php
// ─── Paths ────────────────────────────────────────────────────────────────────
define('BASE_DIR',     __DIR__);
define('API_KEY_FILE', BASE_DIR . '/API/api.txt');

// ─── SQLite path — use env var on Railway, local default otherwise ────────────
// On Railway: set DB_PATH=/data/blog.db (persistent volume mounted at /data)
// Locally: defaults to data/blog.db inside project
define('DB_PATH',  getenv('DB_PATH')  ?: BASE_DIR . '/data/blog.db');
define('LOG_PATH', getenv('LOG_PATH') ?: BASE_DIR . '/logs/generate.log');

// ─── Ensure log directory exists ──────────────────────────────────────────────
if (!is_dir(dirname(LOG_PATH))) {
    @mkdir(dirname(LOG_PATH), 0755, true);
}
if (!is_dir(dirname(DB_PATH))) {
    @mkdir(dirname(DB_PATH), 0755, true);
}

// ─── Claude API Key ───────────────────────────────────────────────────────────
// On Railway: set CLAUDE_API_KEY environment variable in the dashboard
// Locally: reads from API/api.txt
$api_key = getenv('CLAUDE_API_KEY') ?: trim(@file_get_contents(API_KEY_FILE));
if (!$api_key) {
    die('ERROR: CLAUDE_API_KEY env var not set and API/api.txt not found.');
}
define('CLAUDE_API_KEY',    $api_key);
define('CLAUDE_API_URL',    'https://api.anthropic.com/v1/messages');
define('CLAUDE_MODEL',      'claude-opus-4-6');
define('CLAUDE_MAX_TOKENS', 4096);

// ─── Scheduling ───────────────────────────────────────────────────────────────
define('TEST_MODE',          getenv('TEST_MODE') === 'false' ? false : true);
define('TEST_INTERVAL_SECS', 5 * 60);    // 5 minutes
define('PROD_INTERVAL_SECS', 23 * 3600); // 23 hours

// ─── Cron Token ───────────────────────────────────────────────────────────────
// On Railway: set CRON_TOKEN environment variable in the dashboard
define('CRON_TOKEN', getenv('CRON_TOKEN') ?: '74f6eb802bb190b5141413cf3a2f9e84');

// ─── Blog Settings ────────────────────────────────────────────────────────────
define('BLOG_NAME',    'HealthCyber Insights');
define('BLOG_TAGLINE', 'Cybersecurity, Privacy & AI in Healthcare');
define('POSTS_PER_PAGE', 9);

// Auto-detect BLOG_URL from env (Railway) or DOCUMENT_ROOT (local)
(function() {
    // On Railway: set BLOG_URL=https://yourapp.railway.app in the dashboard
    if ($url = getenv('BLOG_URL')) {
        define('BLOG_URL', rtrim($url, '/'));
        return;
    }
    if (php_sapi_name() === 'cli') {
        define('BLOG_URL', 'http://localhost:8000');
        return;
    }
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $doc_root = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $base     = str_replace('\\', '/', BASE_DIR);
    $sub      = str_starts_with($base, $doc_root) ? substr($base, strlen($doc_root)) : '';
    define('BLOG_URL', $scheme . '://' . $host . $sub);
})();

// ─── Amazon Affiliate ─────────────────────────────────────────────────────────
define('AMAZON_TAG', 'rncyberhealth-20');

// ─── Admin ────────────────────────────────────────────────────────────────────
// On Railway: set ADMIN_PASSWORD_HASH environment variable
// Generate hash: php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);"
define('ADMIN_PASSWORD_HASH',
    getenv('ADMIN_PASSWORD_HASH') ?:
    '$2y$10$36sGPBlYg4BFQuHX.8.PJuO3QzxujwMSxpc.ZYsCEYLucj99H/tqu' // local default: admin1234
);
define('SESSION_NAME', 'hcblog_admin');
