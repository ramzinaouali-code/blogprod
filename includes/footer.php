<?php
$categories = get_categories();
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= h(BLOG_URL) ?>/" class="site-logo">Health<span style="color:var(--accent)">Cyber</span> Insights</a>
        <p><?= h(BLOG_TAGLINE) ?>. Practical guidance for healthcare security and compliance professionals.</p>
      </div>
      <div>
        <div class="footer-heading">Topics</div>
        <ul class="footer-links">
          <?php foreach ($categories as $cat): ?>
            <li><a href="<?= h(BLOG_URL) ?>/category.php?slug=<?= h($cat['slug']) ?>"><?= h($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <div class="footer-heading">About</div>
        <ul class="footer-links">
          <li><a href="<?= h(BLOG_URL) ?>/">Home</a></li>
          <li><a href="<?= h(BLOG_URL) ?>/admin/">Admin Panel</a></li>
        </ul>
      </div>
    </div>

    <div class="affiliate-disclaimer">
      <strong>Affiliate Disclosure:</strong> This site participates in the Amazon Associates program.
      Book links are affiliate links — we may earn a small commission at no extra cost to you.
      We only recommend books we believe provide genuine value to healthcare security professionals.
    </div>

    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= h(BLOG_NAME) ?>. All rights reserved.</span>
      <span>AI-generated content reviewed for accuracy. Not legal or compliance advice.</span>
    </div>
  </div>
</footer>
</body>
</html>
