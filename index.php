<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang        = get_lang();
$page        = max(1, (int)($_GET['page'] ?? 1));
$total       = count_posts(null, $lang);
$pag         = paginate($total, POSTS_PER_PAGE, $page);
$posts       = get_posts(POSTS_PER_PAGE, $pag['offset'], null, $lang);
$recent      = get_recent_posts(6, $lang);
$categories  = get_categories($lang);

$page_title       = null; // Use blog name only
$meta_description = BLOG_TAGLINE;

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <div class="page-layout">

    <!-- Main Column -->
    <main>

      <?php if (empty($posts)): ?>
        <div class="empty-state">
          <h3><?= t('index.no_posts', $lang) ?></h3>
          <p><?= t('index.no_posts_desc', $lang) ?></p>
        </div>

      <?php else: ?>

        <?php $hero = array_shift($posts); ?>

        <!-- Hero Post -->
        <article class="hero-card">
          <div class="hero-thumb" style="background: <?= h($hero['thumbnail_css'] ?: 'linear-gradient(135deg,#1a73e8,#0d47a1)') ?>">
            <?php if (!empty($hero['photo_url'])): ?>
              <img src="<?= h($hero['photo_url']) ?>" alt="<?= h($hero['title']) ?>" loading="eager">
            <?php endif; ?>
          </div>
          <div class="hero-body">
            <?php if ($hero['category_name']): ?>
              <span class="cat-badge" style="background:<?= h($hero['category_color'] ?? '#1a73e8') ?>">
                <?= h($hero['category_name']) ?>
              </span>
            <?php endif; ?>
            <h2><a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($hero['slug']) ?>"><?= h($hero['title']) ?></a></h2>
            <p class="hero-excerpt"><?= h($hero['excerpt']) ?></p>
            <div class="hero-meta">
              <?= h(format_date($hero['created_at'])) ?>
              &middot; <?= reading_time($hero['body']) ?> <?= t('index.min_read', $lang) ?>
            </div>
            <a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($hero['slug']) ?>" class="read-more"><?= t('index.read_more', $lang) ?></a>
          </div>
        </article>

        <!-- Newsletter Banner -->
        <div class="newsletter-banner">
          <div class="newsletter-banner-inner">
            <div class="newsletter-banner-text">
              <h3><?= t('news.title', $lang) ?></h3>
              <p><?= t('news.desc', $lang) ?></p>
            </div>
            <form class="subscribe-form" action="<?= h(BLOG_URL) ?>/subscribe.php" method="GET">
              <input type="email" name="prefill" placeholder="<?= h(t('news.placeholder', $lang)) ?>" aria-label="Email address">
              <a href="<?= h(BLOG_URL) ?>/subscribe.php" class="subscribe-btn"><?= t('news.button', $lang) ?></a>
            </form>
            <p class="newsletter-disclaimer"><?= t('news.disclaimer', $lang) ?></p>
          </div>
        </div>

        <!-- Section Header -->
        <?php if (!empty($posts)): ?>
        <div class="section-header">
          <h2 class="section-title">
            <span class="section-title-bar"></span><?= t('index.latest', $lang) ?>
          </h2>
          <span class="text-muted" style="font-size:13px"><?= $total ?> <?= t('index.articles_count', $lang) ?></span>
        </div>

        <!-- Post Grid -->
        <div class="post-grid">
          <?php foreach ($posts as $post): ?>
            <article class="card">
              <a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($post['slug']) ?>">
                <div class="card-thumb" style="background: <?= h($post['thumbnail_css'] ?: 'linear-gradient(135deg,#1a73e8,#0d47a1)') ?>">
                  <?php if (!empty($post['photo_url'])): ?>
                    <img src="<?= h($post['photo_url']) ?>" alt="<?= h($post['title']) ?>" loading="lazy">
                  <?php endif; ?>
                </div>
              </a>
              <div class="card-body">
                <?php if ($post['category_name']): ?>
                  <a class="cat-badge" href="<?= h(BLOG_URL) ?>/category.php?slug=<?= h($post['category_slug']) ?>"
                     style="background:<?= h($post['category_color'] ?? '#1a73e8') ?>">
                    <?= h($post['category_name']) ?>
                  </a>
                <?php endif; ?>
                <h3><a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($post['slug']) ?>"><?= h($post['title']) ?></a></h3>
                <p class="card-excerpt"><?= h($post['excerpt']) ?></p>
                <div class="card-meta">
                  <span><?= h(format_date($post['created_at'])) ?></span>
                  <span class="read-time"><?= reading_time($post['body']) ?> <?= t('index.min_read', $lang) ?></span>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?= pagination_html($pag, BLOG_URL . '/index.php') ?>

      <?php endif; ?>
    </main>

    <!-- Sidebar -->
    <aside class="sidebar">

      <!-- Recent Posts -->
      <div class="sidebar-widget">
        <div class="widget-title"><?= t('sidebar.latest', $lang) ?></div>
        <div class="widget-body">
          <ul class="recent-posts-list">
            <?php foreach ($recent as $rp): ?>
              <li>
                <a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($rp['slug']) ?>"><?= h($rp['title']) ?></a>
                <span class="rp-date"><?= h(format_date($rp['created_at'])) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Categories -->
      <div class="sidebar-widget">
        <div class="widget-title"><?= t('sidebar.topics', $lang) ?></div>
        <div class="widget-body">
          <ul class="cat-list">
            <?php foreach ($categories as $cat): ?>
              <?php if ($cat['post_count'] > 0): ?>
              <li>
                <a href="<?= h(BLOG_URL) ?>/category.php?slug=<?= h($cat['slug']) ?>">
                  <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= h($cat['color']) ?>;margin-right:6px"></span>
                  <?= h($cat['name']) ?>
                </a>
                <span class="count"><?= $cat['post_count'] ?></span>
              </li>
              <?php endif; ?>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Stats -->
      <div class="sidebar-widget">
        <div class="widget-title"><?= t('sidebar.about', $lang) ?></div>
        <div class="widget-body">
          <div class="stat-grid">
            <div class="stat-item">
              <div class="num"><?= $total ?></div>
              <div class="lbl"><?= t('sidebar.articles_lbl', $lang) ?></div>
            </div>
            <div class="stat-item">
              <div class="num"><?= count($categories) ?></div>
              <div class="lbl"><?= t('sidebar.topics_lbl', $lang) ?></div>
            </div>
          </div>
          <p style="font-size:12px;color:var(--text-muted);margin-top:12px;line-height:1.5">
            <?= t('sidebar.about_desc', $lang) ?>
          </p>
        </div>
      </div>

      <!-- Subscribe Widget -->
      <div class="sidebar-widget sidebar-subscribe">
        <div class="widget-title"><?= t('news.title', $lang) ?></div>
        <div class="widget-body">
          <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;line-height:1.5">
            <?= t('news.desc', $lang) ?>
          </p>
          <a href="<?= h(BLOG_URL) ?>/subscribe.php" class="subscribe-btn subscribe-btn-full"><?= t('news.button', $lang) ?></a>
        </div>
      </div>

    </aside>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
