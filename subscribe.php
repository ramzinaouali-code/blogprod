<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/email.php';

$lang = get_lang();

// ─── Confirmation link handler (?confirm=TOKEN) ───────────────────────────────
if (!empty($_GET['confirm'])) {
    $token = trim($_GET['confirm']);
    $db    = get_db();

    $stmt = $db->prepare("UPDATE subscribers SET status = 'confirmed' WHERE token = ? AND status = 'pending'");
    $stmt->execute([$token]);

    if ($stmt->rowCount() > 0) {
        $result = 'confirmed';
    } else {
        // Check if already confirmed (idempotent)
        $check = $db->prepare("SELECT status FROM subscribers WHERE token = ?");
        $check->execute([$token]);
        $row    = $check->fetch();
        $result = ($row && $row['status'] === 'confirmed') ? 'confirmed' : 'invalid';
    }

    $page_title = t('sub.title', $lang);
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container" style="padding:60px 16px;max-width:620px;margin:0 auto;text-align:center">
      <?php if ($result === 'confirmed'): ?>
        <div class="sub-status-box sub-ok">
          <div class="sub-status-icon">&#10003;</div>
          <h1><?= t('sub.confirmed_title', $lang) ?></h1>
          <p><?= t('sub.confirmed_desc', $lang) ?></p>
          <a href="<?= h(BLOG_URL) ?>/" class="btn-primary" style="margin-top:20px;display:inline-block"><?= t('nav.home', $lang) ?></a>
        </div>
      <?php else: ?>
        <div class="sub-status-box sub-err">
          <h2><?= t('unsub.invalid', $lang) ?></h2>
          <a href="<?= h(BLOG_URL) ?>/" style="margin-top:16px;display:inline-block"><?= t('nav.home', $lang) ?></a>
        </div>
      <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ─── POST handler — subscribe form submission ─────────────────────────────────
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = t('sub.invalid', $lang);
        } else {
            $db    = get_db();
            $token = bin2hex(random_bytes(32));
            try {
                $db->prepare(
                    "INSERT INTO subscribers (email, token, status) VALUES (?, ?, 'pending')"
                )->execute([$email, $token]);
                send_confirmation_email($email, $token);
                // PRG — redirect to avoid double-submit on refresh
                header('Location: ' . BLOG_URL . '/subscribe.php?status=pending');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 || str_contains((string)$e->getMessage(), 'UNIQUE')) {
                    $error = t('sub.already', $lang);
                } else {
                    $error = 'An error occurred. Please try again.';
                    error_log('subscribe.php DB error: ' . $e->getMessage());
                }
            }
        }
    }
}

// ─── GET status=pending — shown after successful form submission ──────────────
$status_param = trim($_GET['status'] ?? '');
$prefill      = h(trim($_GET['prefill'] ?? ''));
$page_title   = t('sub.title', $lang);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding:60px 16px;max-width:560px;margin:0 auto">

  <?php if ($status_param === 'pending'): ?>
    <!-- After successful form submission -->
    <div class="sub-status-box sub-ok" style="text-align:center">
      <div class="sub-status-icon" style="font-size:48px;margin-bottom:16px">&#9993;</div>
      <h1><?= t('sub.ok_title', $lang) ?></h1>
      <p style="color:var(--text-muted);margin:12px 0 24px"><?= t('sub.ok_desc', $lang) ?></p>
      <a href="<?= h(BLOG_URL) ?>/" class="btn-primary"><?= t('nav.home', $lang) ?></a>
    </div>

  <?php else: ?>
    <!-- Subscribe form -->
    <h1 style="font-size:26px;color:var(--navy);margin-bottom:8px"><?= t('sub.title', $lang) ?></h1>
    <p style="color:var(--text-muted);margin-bottom:28px;line-height:1.6"><?= t('sub.desc', $lang) ?></p>

    <?php if ($error): ?>
      <div class="sub-error-msg" role="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= h(BLOG_URL) ?>/subscribe.php" class="subscribe-form-page">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="sub_email" style="display:block;font-weight:600;margin-bottom:6px;font-size:14px">
          <?= t('sub.email_label', $lang) ?>
        </label>
        <input type="email" id="sub_email" name="email" required autocomplete="email"
               placeholder="<?= h(t('sub.email_ph', $lang)) ?>"
               value="<?= $prefill ?>"
               style="width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:6px;font-size:15px;margin-bottom:14px">
      </div>
      <button type="submit" class="btn-primary" style="width:100%;padding:13px;font-size:15px">
        <?= t('sub.btn', $lang) ?>
      </button>
      <p style="font-size:12px;color:var(--text-muted);margin-top:12px;text-align:center">
        <?= t('news.disclaimer', $lang) ?>
      </p>
    </form>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
