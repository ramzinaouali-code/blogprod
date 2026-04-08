<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

$db         = get_db();
$replace_id = (int)($_GET['replace'] ?? 0);
$replace_post = null;

if ($replace_id) {
    $s = $db->prepare('SELECT id, title FROM posts WHERE id = ?');
    $s->execute([$replace_id]);
    $replace_post = $s->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Invalid form token.');
        header('Location: ' . BLOG_URL . '/admin/');
        exit;
    }

    // If replacing, delete the old post first
    if ($replace_id && $replace_post) {
        $db->prepare('DELETE FROM posts WHERE id = ?')->execute([$replace_id]);
    }

    // Run generate.php as a subprocess so it has clean scope
    $php      = PHP_BINARY ?: 'php';
    $script   = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'generate.php';
    $cmd      = escapeshellarg($php) . ' ' . escapeshellarg($script) . ' --local --force 2>&1';
    $output   = shell_exec($cmd);
    $last_log = $db->query('SELECT * FROM generation_log ORDER BY id DESC LIMIT 1')->fetch();

    if ($last_log && $last_log['status'] === 'success') {
        $new_post = $db->query('SELECT title FROM posts ORDER BY id DESC LIMIT 1')->fetch();
        set_flash('success', 'Post generated: "' . ($new_post['title'] ?? 'New Post') . '"');
    } else {
        $err = $last_log['message'] ?? $output ?? 'Unknown error';
        set_flash('error', 'Generation failed: ' . $err);
    }

    header('Location: ' . BLOG_URL . '/admin/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Generate Post — <?= h(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/admin/admin.css">
</head>
<body>

<nav class="admin-nav">
  <a href="<?= h(BLOG_URL) ?>/admin/" class="brand">Health<span>Cyber</span> <small style="font-weight:400;font-size:12px">Admin</small></a>
  <div class="admin-nav-links">
    <a href="<?= h(BLOG_URL) ?>/admin/">Posts</a>
    <a href="<?= h(BLOG_URL) ?>/admin/settings.php">Settings</a>
    <a href="<?= h(BLOG_URL) ?>/" target="_blank">View Blog</a>
    <a href="<?= h(BLOG_URL) ?>/admin/?logout=1" class="danger">Logout</a>
  </div>
</nav>

<div class="admin-wrap" style="max-width:560px">
  <?= flash_html() ?>

  <div class="card" style="text-align:center;padding:40px">
    <div style="font-size:48px;margin-bottom:16px">&#129302;</div>

    <?php if ($replace_post): ?>
      <h2 style="margin-bottom:10px">Regenerate Post</h2>
      <p style="color:var(--text-muted);margin-bottom:6px">This will delete the current post and generate a new one on the next available topic:</p>
      <p style="font-weight:600;margin-bottom:24px"><?= h($replace_post['title']) ?></p>
    <?php else: ?>
      <h2 style="margin-bottom:10px">Generate New Post</h2>
      <p style="color:var(--text-muted);margin-bottom:24px">
        Claude AI will write a new post on the next topic in the rotation.<br>
        <small>This bypasses the schedule guard.</small>
      </p>
    <?php endif; ?>

    <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:24px;font-size:13px;text-align:left">
      <strong>What happens:</strong>
      <ul style="margin:8px 0 0 18px;line-height:2">
        <li>Claude API is called (~30–60 seconds)</li>
        <li>A new 700–900 word post is created</li>
        <li>3 Amazon affiliate book links are added</li>
        <li>Post is published immediately</li>
      </ul>
    </div>

    <form method="POST" action="<?= h(BLOG_URL) ?>/admin/regenerate.php<?= $replace_id ? '?replace=' . $replace_id : '' ?>">
      <?= csrf_field() ?>
      <div style="display:flex;gap:12px;justify-content:center">
        <a href="<?= h(BLOG_URL) ?>/admin/" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">&#129302; Generate Now</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.querySelector('form').addEventListener('submit', function() {
    var btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Generating... (please wait ~60s)';
  });
</script>
</body>
</html>
