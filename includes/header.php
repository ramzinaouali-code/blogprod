<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/lang.php';

// ─── Language switcher redirect ───────────────────────────────────────────────
// Must run before any output. ?setlang=en or ?setlang=fr
if (!empty($_GET['setlang'])) {
    $new_lang = $_GET['setlang'] === 'fr' ? 'fr' : 'en';
    set_lang($new_lang);
    // Redirect back to the same page without the setlang param
    $redirect = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    header('Location: ' . $redirect);
    exit;
}

// ─── Language-sensitive pages must not be shared across users ─────────────────
header('Cache-Control: private, no-cache');

$lang        = get_lang();
$categories  = get_categories($lang);
$current_url = $_SERVER['REQUEST_URI'] ?? '/';

// Build lang-toggle URL helpers
function lang_url(string $target_lang): string {
    $uri  = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $qs   = $_GET;
    unset($qs['setlang']);
    $qs['setlang'] = $target_lang;
    return $uri . '?' . http_build_query($qs);
}
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($page_title ?? BLOG_NAME) ?><?= isset($page_title) ? ' — ' . BLOG_NAME : '' ?></title>
  <meta name="description" content="<?= h($meta_description ?? BLOG_TAGLINE) ?>">
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/assets/css/style.css">
  <meta name="theme-color" content="#1a1a2e">
</head>
<body>

<div class="top-bar">
  <div class="container">
    <span><?= h(date('l, F j, Y')) ?></span>
    <div style="display:flex;align-items:center;gap:16px">
      <!-- Language toggle -->
      <div class="lang-toggle" role="group" aria-label="Language">
        <a href="<?= h(lang_url('en')) ?>" class="lang-btn<?= $lang === 'en' ? ' active' : '' ?>" hreflang="en">EN</a>
        <a href="<?= h(lang_url('fr')) ?>" class="lang-btn<?= $lang === 'fr' ? ' active' : '' ?>" hreflang="fr">FR</a>
      </div>
      <a href="<?= h(BLOG_URL) ?>/admin/"><?= t('nav.admin', $lang) ?></a>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="container header-inner">
    <a href="<?= h(BLOG_URL) ?>/" class="site-logo">
      <span class="logo-health">Health</span><span class="logo-cyber">Cyber</span><span class="logo-insights">Insights</span>
    </a>
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="site-nav" id="siteNav">
      <a href="<?= h(BLOG_URL) ?>/" <?= str_ends_with($current_url, '/') || str_contains($current_url, 'index') ? 'class="active"' : '' ?>><?= t('nav.home', $lang) ?></a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= h(BLOG_URL) ?>/category.php?slug=<?= h($cat['slug']) ?>"
           <?= str_contains($current_url, 'slug=' . $cat['slug']) ? 'class="active"' : '' ?>>
          <?= h($cat['name']) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</header>

<script>
  (function() {
    var btn = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (btn && nav) {
      btn.addEventListener('click', function() {
        var open = nav.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    }
  })();
</script>
