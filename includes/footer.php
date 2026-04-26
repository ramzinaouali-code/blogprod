<?php
// $lang is set by header.php (which must be included before footer.php)
$lang       = $lang ?? get_lang();
$categories = get_categories($lang);
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= h(BLOG_URL) ?>/" class="site-logo"><span class="logo-health">Health</span><span class="logo-cyber">Cyber</span><span class="logo-insights">Insights</span></a>
        <p><?= h(BLOG_TAGLINE) ?>. <?= t('footer.tagline', $lang) ?></p>
      </div>
      <div>
        <div class="footer-heading"><?= t('footer.topics_hd', $lang) ?></div>
        <ul class="footer-links">
          <?php foreach ($categories as $cat): ?>
            <li><a href="<?= h(BLOG_URL) ?>/category.php?slug=<?= h($cat['slug']) ?>"><?= h($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <div class="footer-heading"><?= t('footer.about_hd', $lang) ?></div>
        <ul class="footer-links">
          <li><a href="<?= h(BLOG_URL) ?>/"><?= t('nav.home', $lang) ?></a></li>
          <li><a href="<?= h(BLOG_URL) ?>/admin/"><?= t('footer.admin_link', $lang) ?></a></li>
        </ul>
      </div>
    </div>

    <div class="affiliate-disclaimer">
      <?= t('footer.affiliate', $lang) ?>
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= h(BLOG_NAME) ?>. <?= t('footer.rights', $lang) ?></span>
      <span><?= t('footer.disclaimer', $lang) ?></span>
    </div>
  </div>
</footer>
</body>
</html>
