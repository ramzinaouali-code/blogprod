<?php
require_once dirname(__DIR__) . '/includes/functions.php';
$categories = get_categories();
$current_url = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
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
    <a href="<?= h(BLOG_URL) ?>/admin/">Admin</a>
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
      <a href="<?= h(BLOG_URL) ?>/" <?= str_ends_with($current_url, '/') || str_contains($current_url, 'index') ? 'class="active"' : '' ?>>Home</a>
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
