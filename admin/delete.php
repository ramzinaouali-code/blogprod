<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BLOG_URL . '/admin/'); exit; }

$post = $db->prepare('SELECT id, title FROM posts WHERE id = ?');
$post->execute([$id]);
$post = $post->fetch();
if (!$post) { header('Location: ' . BLOG_URL . '/admin/'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Invalid form token.');
        header('Location: ' . BLOG_URL . '/admin/');
        exit;
    }
    $db->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    set_flash('success', 'Post "' . $post['title'] . '" deleted.');
    header('Location: ' . BLOG_URL . '/admin/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Post — <?= h(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/admin/admin.css">
</head>
<body>

<nav class="admin-nav">
  <a href="<?= h(BLOG_URL) ?>/admin/" class="brand">Health<span>Cyber</span> <small style="font-weight:400;font-size:12px">Admin</small></a>
  <div class="admin-nav-links">
    <a href="<?= h(BLOG_URL) ?>/admin/">Posts</a>
    <a href="<?= h(BLOG_URL) ?>/admin/?logout=1" class="danger">Logout</a>
  </div>
</nav>

<div class="admin-wrap" style="max-width:520px">
  <div class="card" style="text-align:center;padding:40px">
    <div style="font-size:48px;margin-bottom:16px">&#128465;</div>
    <h2 style="margin-bottom:10px">Delete Post?</h2>
    <p style="color:var(--text-muted);margin-bottom:24px;line-height:1.6">
      You are about to permanently delete:<br>
      <strong><?= h($post['title']) ?></strong><br>
      This action cannot be undone.
    </p>
    <form method="POST" action="" style="display:flex;gap:12px;justify-content:center">
      <?= csrf_field() ?>
      <a href="<?= h(BLOG_URL) ?>/admin/" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-danger">Yes, Delete</button>
    </form>
  </div>
</div>
</body>
</html>
