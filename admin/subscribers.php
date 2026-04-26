<?php
require_once dirname(__DIR__) . '/admin/auth.php';
require_once dirname(__DIR__) . '/db.php';

require_admin();

$db = get_db();

// ─── CSV export ───────────────────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $rows = $db->query(
        "SELECT email, status, created_at FROM subscribers ORDER BY created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "email,status,subscribed_at\r\n";
    foreach ($rows as $r) {
        echo '"' . str_replace('"', '""', $r['email']) . '",'
           . '"' . $r['status'] . '",'
           . '"' . $r['created_at'] . '"' . "\r\n";
    }
    exit;
}

// ─── Delete subscriber ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $db->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
    header('Location: ' . BLOG_URL . '/admin/subscribers.php?deleted=1');
    exit;
}

// ─── Stats ────────────────────────────────────────────────────────────────────
$stats = $db->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'confirmed') AS confirmed,
        SUM(status = 'pending') AS pending,
        SUM(status = 'unsubscribed') AS unsubscribed
     FROM subscribers"
)->fetch(PDO::FETCH_ASSOC);

// ─── Pagination ───────────────────────────────────────────────────────────────
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 30;
$total_n  = (int)($stats['total'] ?? 0);
$offset   = ($page - 1) * $per_page;
$total_pages = max(1, (int)ceil($total_n / $per_page));

$rows = $db->prepare(
    "SELECT * FROM subscribers ORDER BY created_at DESC LIMIT ? OFFSET ?"
);
$rows->execute([$per_page, $offset]);
$subscribers = $rows->fetchAll(PDO::FETCH_ASSOC);

$deleted = isset($_GET['deleted']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Subscribers — <?= htmlspecialchars(BLOG_NAME, ENT_QUOTES) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/admin.css">
  <style>
    .stat-cards { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .stat-card  { background:#fff; border:1px solid #e5e7eb; border-radius:8px; padding:18px 24px; flex:1; min-width:140px; }
    .stat-card .num { font-size:32px; font-weight:700; color:#1a1a2e; }
    .stat-card .lbl { font-size:13px; color:#6b7280; margin-top:2px; }
    .badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:600; }
    .badge-confirmed   { background:#d1fae5; color:#065f46; }
    .badge-pending     { background:#fef3c7; color:#92400e; }
    .badge-unsubscribed{ background:#f3f4f6; color:#6b7280; }
    .export-btn { background:#1a73e8; color:#fff; padding:8px 18px; border-radius:6px; text-decoration:none; font-weight:600; font-size:13px; }
    .delete-btn { background:none; border:1px solid #e5e7eb; color:#ef4444; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:12px; }
    .delete-btn:hover { background:#fef2f2; }
  </style>
</head>
<body>
<div class="admin-wrap">

  <header class="admin-header">
    <a href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/" class="admin-logo">
      HealthCyber <span>Admin</span>
    </a>
    <nav class="admin-nav">
      <a href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/">Dashboard</a>
      <a href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/subscribers.php" class="active">Subscribers</a>
      <a href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/settings.php">Settings</a>
      <a href="<?= htmlspecialchars(BLOG_URL, ENT_QUOTES) ?>/admin/login.php?logout=1" class="logout-link">Logout</a>
    </nav>
  </header>

  <main class="admin-main">

    <?php if ($deleted): ?>
      <div class="flash" style="background:#2e7d32;margin-bottom:16px">Subscriber deleted.</div>
    <?php endif; ?>

    <div class="admin-page-header">
      <h1 class="admin-title">Subscribers</h1>
      <a href="?export=csv" class="export-btn">&#8595; Export CSV</a>
    </div>

    <!-- Stats -->
    <div class="stat-cards">
      <div class="stat-card">
        <div class="num"><?= (int)($stats['total'] ?? 0) ?></div>
        <div class="lbl">Total</div>
      </div>
      <div class="stat-card">
        <div class="num" style="color:#065f46"><?= (int)($stats['confirmed'] ?? 0) ?></div>
        <div class="lbl">Confirmed</div>
      </div>
      <div class="stat-card">
        <div class="num" style="color:#92400e"><?= (int)($stats['pending'] ?? 0) ?></div>
        <div class="lbl">Pending</div>
      </div>
      <div class="stat-card">
        <div class="num" style="color:#6b7280"><?= (int)($stats['unsubscribed'] ?? 0) ?></div>
        <div class="lbl">Unsubscribed</div>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Email</th>
            <th>Status</th>
            <th>Subscribed</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($subscribers)): ?>
            <tr><td colspan="5" style="text-align:center;color:#6b7280;padding:32px">No subscribers yet.</td></tr>
          <?php else: ?>
            <?php foreach ($subscribers as $sub): ?>
              <tr>
                <td style="color:#9ca3af;font-size:12px"><?= (int)$sub['id'] ?></td>
                <td><?= htmlspecialchars($sub['email'], ENT_QUOTES) ?></td>
                <td>
                  <span class="badge badge-<?= htmlspecialchars($sub['status'], ENT_QUOTES) ?>">
                    <?= htmlspecialchars(ucfirst($sub['status']), ENT_QUOTES) ?>
                  </span>
                </td>
                <td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($sub['created_at'], ENT_QUOTES) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Delete this subscriber?')">
                    <input type="hidden" name="delete_id" value="<?= (int)$sub['id'] ?>">
                    <button type="submit" class="delete-btn">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav style="margin-top:20px;display:flex;gap:8px;justify-content:center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
          <a href="?page=<?= $i ?>"
             style="padding:6px 12px;border:1px solid <?= $i === $page ? '#1a73e8' : '#e5e7eb' ?>;border-radius:4px;font-size:13px;color:<?= $i === $page ? '#1a73e8' : '#374151' ?>;text-decoration:none">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </nav>
    <?php endif; ?>

  </main>
</div>
</body>
</html>
