<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/auth.php';
require_admin();

if (isset($_GET['logout'])) admin_logout();

$db = get_db();

// Stats
$total_posts  = (int)$db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
$published    = (int)$db->query('SELECT COUNT(*) FROM posts WHERE status="published"')->fetchColumn();
$this_week    = (int)$db->query('SELECT COUNT(*) FROM posts WHERE created_at >= datetime("now", "-7 days")')->fetchColumn();
$log_success  = (int)$db->query('SELECT COUNT(*) FROM generation_log WHERE status="success"')->fetchColumn();
$log_total    = (int)$db->query('SELECT COUNT(*) FROM generation_log')->fetchColumn();
$success_rate = $log_total > 0 ? round(($log_success / $log_total) * 100) : 0;
$test_mode    = get_setting('test_mode', '1') === '1';

// Posts (paginated)
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;
$posts  = $db->prepare(
    'SELECT p.*, c.name AS cat_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id
     ORDER BY p.created_at DESC LIMIT ? OFFSET ?'
);
$posts->execute([$limit, $offset]);
$posts = $posts->fetchAll();
$total_for_pag = $total_posts;

// Recent log entries
$logs = $db->query(
    'SELECT * FROM generation_log ORDER BY id DESC LIMIT 10'
)->fetchAll();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — <?= h(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/admin/admin.css">
</head>
<body>

<nav class="admin-nav">
  <a href="<?= h(BLOG_URL) ?>/admin/" class="brand">Health<span>Cyber</span> <small style="font-weight:400;font-size:12px">Admin</small></a>
  <div class="admin-nav-links">
    <a href="<?= h(BLOG_URL) ?>/admin/" class="active">Posts</a>
    <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php">Generate</a>
    <a href="<?= h(BLOG_URL) ?>/admin/settings.php">Settings</a>
    <a href="<?= h(BLOG_URL) ?>/" target="_blank">View Blog</a>
    <a href="<?= h(BLOG_URL) ?>/admin/?logout=1" class="danger">Logout</a>
  </div>
</nav>

<div class="admin-wrap">
  <?= flash_html() ?>

  <div class="page-hdr">
    <h1>Dashboard</h1>
    <div style="display:flex;gap:8px;align-items:center">
      <span class="badge <?= $test_mode ? 'badge-warning' : 'badge-success' ?>">
        <?= $test_mode ? 'TEST MODE' : 'PRODUCTION' ?>
      </span>
      <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php" class="btn btn-primary">+ Generate Post Now</a>
    </div>
  </div>

  <!-- Stats Bar -->
  <div class="stats-bar">
    <div class="stat-card">
      <div class="num"><?= $total_posts ?></div>
      <div class="lbl">Total Posts</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= $published ?></div>
      <div class="lbl">Published</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= $this_week ?></div>
      <div class="lbl">This Week</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= $success_rate ?>%</div>
      <div class="lbl">API Success Rate</div>
    </div>
  </div>

  <!-- Posts Table -->
  <div class="card">
    <div class="card-title">All Posts</div>
    <?php if (empty($posts)): ?>
      <p style="color:var(--text-muted);text-align:center;padding:30px">No posts yet. <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php">Generate the first one.</a></p>
    <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Title</th>
            <th>Category</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($posts as $p): ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $p['id'] ?></td>
            <td>
              <a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($p['slug']) ?>" target="_blank" style="font-weight:500">
                <?= h(mb_strimwidth($p['title'], 0, 70, '...')) ?>
              </a>
            </td>
            <td><?= h($p['cat_name'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $p['status'] === 'published' ? 'badge-success' : 'badge-warning' ?>">
                <?= h($p['status']) ?>
              </span>
            </td>
            <td style="white-space:nowrap;color:var(--text-muted);font-size:13px">
              <?= h(date('M j, Y', strtotime($p['created_at']))) ?>
            </td>
            <td style="white-space:nowrap">
              <a href="<?= h(BLOG_URL) ?>/admin/edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
              <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php?replace=<?= $p['id'] ?>" class="btn btn-sm btn-warning" style="margin-left:4px">Regen</a>
              <a href="<?= h(BLOG_URL) ?>/admin/delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" style="margin-left:4px">Delete</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Generation Log -->
  <div class="card">
    <div class="card-title">Recent Generation Log</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Topic</th><th>Message</th><th>Time</th></tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="4" style="color:var(--text-muted);text-align:center">No log entries yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td>
              <span class="badge <?= $log['status'] === 'success' ? 'badge-success' : ($log['status'] === 'error' ? 'badge-danger' : 'badge-warning') ?>">
                <?= h($log['status']) ?>
              </span>
            </td>
            <td class="log-entry"><?= h(mb_strimwidth($log['topic'] ?? '', 0, 50, '...')) ?></td>
            <td class="log-entry <?= $log['status'] === 'error' ? 'log-error' : '' ?>"><?= h(mb_strimwidth($log['message'] ?? '', 0, 80, '...')) ?></td>
            <td style="white-space:nowrap;color:var(--text-muted);font-size:12px"><?= h(date('M j H:i', strtotime($log['created_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="margin-top:12px"><a href="<?= h(BLOG_URL) ?>/admin/settings.php">View full log &rarr;</a></p>
  </div>

</div>
</body>
</html>
