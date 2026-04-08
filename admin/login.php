<?php
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/auth.php';

admin_session_start();

if (is_admin_logged_in()) {
    header('Location: ' . BLOG_URL . '/admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    $failures = $_SESSION['login_failures'] ?? 0;
    $last_fail = $_SESSION['last_fail_time'] ?? 0;

    if ($failures >= 5 && (time() - $last_fail) < 900) {
        $error = 'Too many failed attempts. Please wait 15 minutes.';
    } else {
        if ($failures >= 5) {
            $_SESSION['login_failures'] = 0;
        }
        $password = $_POST['password'] ?? '';
        if (password_verify($password, ADMIN_PASSWORD_HASH)) {
            $_SESSION['admin_logged_in']  = true;
            $_SESSION['login_failures']   = 0;
            session_regenerate_id(true);
            header('Location: ' . BLOG_URL . '/admin/');
            exit;
        } else {
            $_SESSION['login_failures']  = ($failures + 1);
            $_SESSION['last_fail_time']  = time();
            $remaining = 5 - $_SESSION['login_failures'];
            $error = 'Incorrect password.' . ($remaining > 0 ? " {$remaining} attempts remaining." : ' Account locked for 15 minutes.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — <?= htmlspecialchars(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BLOG_URL) ?>/admin/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">Health<span>Cyber</span> Insights</div>
    <h2>Admin Access</h2>

    <?php if ($error): ?>
      <div class="flash" style="background:#c62828;margin-bottom:16px"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input type="password" id="password" name="password" class="form-control"
               autocomplete="current-password" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
    </form>

    <p style="text-align:center;margin-top:16px;font-size:13px">
      <a href="<?= htmlspecialchars(BLOG_URL) ?>/">&larr; Back to blog</a>
    </p>
  </div>
</div>
</body>
</html>
