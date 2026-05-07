<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

// Send a minimal Claude API request and show the FULL raw response
$payload = json_encode([
    'model'      => CLAUDE_MODEL,
    'max_tokens' => 64,
    'messages'   => [['role' => 'user', 'content' => 'Say hello in one word.']],
]);

$ch = curl_init(CLAUDE_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . CLAUDE_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_TIMEOUT => 30,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);
?>
<!DOCTYPE html>
<html>
<head><title>API Test</title>
<style>body{font-family:monospace;padding:30px;background:#111;color:#eee}
pre{background:#222;padding:20px;border-radius:6px;white-space:pre-wrap;word-break:break-all}
.ok{color:#4caf50}.err{color:#f44336}</style>
</head>
<body>
<h2>Claude API Diagnostic</h2>
<p>Model: <strong><?= h(CLAUDE_MODEL) ?></strong></p>
<p>API Key (first 8 chars): <strong><?= h(substr(CLAUDE_API_KEY, 0, 8)) ?>...</strong></p>
<p>HTTP Status: <strong class="<?= $code === 200 ? 'ok' : 'err' ?>"><?= $code ?></strong></p>
<?php if ($err): ?>
<p class="err">cURL error: <?= h($err) ?></p>
<?php endif; ?>
<h3>Full Response Body:</h3>
<pre><?= h($body) ?></pre>
<p><a href="settings.php" style="color:#90caf9">← Back to Settings</a></p>
</body>
</html>
