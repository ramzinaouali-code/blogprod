<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang  = get_lang();
$token = trim($_GET['token'] ?? '');

if (!$token) {
    $result = 'invalid';
} else {
    $db   = get_db();
    $stmt = $db->prepare("UPDATE subscribers SET status = 'unsubscribed' WHERE token = ? AND status != 'unsubscribed'");
    $stmt->execute([$token]);
    $result = ($stmt->rowCount() > 0) ? 'ok' : 'already';
}

$page_title = t('unsub.title', $lang);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding:80px 16px;max-width:560px;margin:0 auto;text-align:center">
  <?php if ($result === 'ok' || $result === 'already'): ?>
    <div style="font-size:48px;margin-bottom:20px">&#128075;</div>
    <h1 style="font-size:26px;color:var(--navy);margin-bottom:10px"><?= t('unsub.title', $lang) ?></h1>
    <p style="color:var(--text-muted);margin-bottom:28px;line-height:1.6"><?= t('unsub.desc', $lang) ?></p>
  <?php else: ?>
    <div style="font-size:48px;margin-bottom:20px">&#10060;</div>
    <h1 style="font-size:22px;color:var(--navy);margin-bottom:10px"><?= t('unsub.invalid', $lang) ?></h1>
  <?php endif; ?>
  <a href="<?= h(BLOG_URL) ?>/" class="btn-primary"><?= t('unsub.back', $lang) ?></a>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
