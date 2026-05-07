<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        set_flash('error', 'Invalid form token.');
    } else {
        $test_mode = isset($_POST['test_mode']) ? '1' : '0';
        set_setting('test_mode', $test_mode);
        set_flash('success', 'Settings saved. Mode: ' . ($test_mode === '1' ? 'Test (every 5 min)' : 'Production (daily)'));
    }
    header('Location: ' . BLOG_URL . '/admin/settings.php');
    exit;
}

$test_mode    = get_setting('test_mode', '1') === '1';
$topic_index  = (int)get_setting('last_topic_index', '0');

// Generation log (all)
$logs = $db->query('SELECT * FROM generation_log ORDER BY id DESC LIMIT 50')->fetchAll();

// Log stats
$total_attempts = count($logs);
$successes      = count(array_filter($logs, fn($l) => $l['status'] === 'success'));
$errors         = count(array_filter($logs, fn($l) => $l['status'] === 'error'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings — <?= h(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/admin/admin.css">
</head>
<body>

<nav class="admin-nav">
  <a href="<?= h(BLOG_URL) ?>/admin/" class="brand">Health<span>Cyber</span> <small style="font-weight:400;font-size:12px">Admin</small></a>
  <div class="admin-nav-links">
    <a href="<?= h(BLOG_URL) ?>/admin/">Posts</a>
    <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php">Generate</a>
    <a href="<?= h(BLOG_URL) ?>/admin/settings.php" class="active">Settings</a>
    <a href="<?= h(BLOG_URL) ?>/" target="_blank">View Blog</a>
    <a href="<?= h(BLOG_URL) ?>/admin/?logout=1" class="danger">Logout</a>
  </div>
</nav>

<div class="admin-wrap">
  <?= flash_html() ?>

  <div class="page-hdr"><h1>Settings</h1></div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
    <div class="stat-card">
      <div class="num"><?= $successes ?></div>
      <div class="lbl">Successful Generations</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= $errors ?></div>
      <div class="lbl">Failed Generations</div>
    </div>
  </div>

  <!-- Schedule Settings -->
  <div class="card">
    <div class="card-title">Schedule Mode</div>
    <form method="POST" action="">
      <?= csrf_field() ?>

      <div class="form-group">
        <div class="toggle-wrap">
          <label class="toggle">
            <input type="checkbox" name="test_mode" <?= $test_mode ? 'checked' : '' ?>>
            <span class="toggle-slider"></span>
          </label>
          <div>
            <strong>Test Mode</strong>
            <div class="form-hint">Test: generate every 5 minutes &nbsp;|&nbsp; Production: generate once per day</div>
          </div>
        </div>
      </div>

      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);padding:14px;margin-bottom:16px;font-size:13px">
        <strong>Current mode:</strong>
        <span class="badge <?= $test_mode ? 'badge-warning' : 'badge-success' ?>" style="margin-left:6px">
          <?= $test_mode ? 'TEST — every 5 minutes' : 'PRODUCTION — once per day' ?>
        </span>
        <br><br>
        <strong>cron-job.org URL:</strong><br>
        <code style="font-size:12px;word-break:break-all"><?= h(BLOG_URL) ?>/generate.php?token=<?= h(CRON_TOKEN) ?></code>
        <br><br>
        <strong>Topic index:</strong> <?= $topic_index ?> (next topic: #<?= $topic_index + 1 ?>)
      </div>

      <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
  </div>

  <!-- cron-job.org Setup Instructions -->
  <div class="card">
    <div class="card-title">cron-job.org Setup</div>
    <ol style="font-size:14px;line-height:2.2;margin-left:18px;color:var(--text)">
      <li>Go to <strong>cron-job.org</strong> and create a free account</li>
      <li>Click <strong>Create Cronjob</strong></li>
      <li>Set the URL to:<br>
        <code style="font-size:12px;background:var(--bg);padding:4px 8px;border-radius:4px;border:1px solid var(--border);word-break:break-all">
          <?= h(BLOG_URL) ?>/generate.php?token=<?= h(CRON_TOKEN) ?>
        </code>
      </li>
      <li>Schedule: Every <strong><?= $test_mode ? '5 minutes' : '24 hours' ?></strong></li>
      <li>Save and enable the job</li>
    </ol>
    <p style="margin-top:12px;font-size:13px;color:var(--text-muted)">
      <strong>Important:</strong> Update <code>BLOG_URL</code> in <code>config.php</code> to your actual domain before configuring cron-job.org.
      Change <code>CRON_TOKEN</code> to a secure random string in production.
    </p>
  </div>

  <!-- Generation Log -->
  <div class="card">
    <div class="card-title">Generation Log (last 50)</div>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th>Status</th><th>Topic</th><th>Message</th><th>Time</th></tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr><td colspan="4" style="color:var(--text-muted);text-align:center;padding:20px">No log entries yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($logs as $log): ?>
          <tr>
            <td>
              <span class="badge <?= $log['status'] === 'success' ? 'badge-success' : ($log['status'] === 'error' ? 'badge-danger' : 'badge-warning') ?>">
                <?= h($log['status']) ?>
              </span>
            </td>
            <td class="log-entry"><?= h(mb_strimwidth($log['topic'] ?? '', 0, 60, '...')) ?></td>
            <td class="log-entry <?= $log['status'] === 'error' ? 'log-error' : '' ?>" title="<?= h($log['message'] ?? '') ?>"><?= h(mb_strimwidth($log['message'] ?? '', 0, 300, '...')) ?></td>
            <td style="white-space:nowrap;color:var(--text-muted);font-size:12px"><?= h(date('M j H:i:s', strtotime($log['created_at']))) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
