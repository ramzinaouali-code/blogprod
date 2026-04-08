<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once __DIR__ . '/auth.php';
require_admin();

$db  = get_db();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BLOG_URL . '/admin/'); exit; }

$post = $db->prepare('SELECT * FROM posts WHERE id = ?');
$post->execute([$id]);
$post = $post->fetch();
if (!$post) { header('Location: ' . BLOG_URL . '/admin/'); exit; }

$books = $db->prepare('SELECT * FROM affiliate_books WHERE post_id = ? ORDER BY position ASC');
$books->execute([$id]);
$books = $books->fetchAll();
while (count($books) < 3) {
    $books[] = ['title' => '', 'author' => '', 'reason' => '', 'search_query' => '', 'position' => count($books) + 1];
}

$categories = get_categories();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) { $errors[] = 'Invalid form token. Please try again.'; }

    if (empty($errors)) {
        $slug = make_slug($_POST['slug'] ?? $post['slug']);
        // Check slug uniqueness (excluding this post)
        $dup = $db->prepare('SELECT id FROM posts WHERE slug = ? AND id != ?');
        $dup->execute([$slug, $id]);
        if ($dup->fetch()) { $slug = $slug . '-' . $id; }

        $db->prepare(
            'UPDATE posts SET slug=?, title=?, excerpt=?, meta_description=?, body=?, tags=?,
             category_id=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?'
        )->execute([
            $slug,
            trim($_POST['title'] ?? ''),
            trim($_POST['excerpt'] ?? ''),
            trim($_POST['meta_description'] ?? ''),
            trim($_POST['body'] ?? ''),
            trim($_POST['tags'] ?? ''),
            ($_POST['category_id'] ?? '') ?: null,
            $_POST['status'] ?? 'published',
            $id,
        ]);

        // Replace books
        $db->prepare('DELETE FROM affiliate_books WHERE post_id = ?')->execute([$id]);
        $bs = $db->prepare(
            'INSERT INTO affiliate_books (post_id, position, title, author, reason, search_query) VALUES (?,?,?,?,?,?)'
        );
        for ($i = 1; $i <= 3; $i++) {
            $bt = trim($_POST["book{$i}_title"]   ?? '');
            $ba = trim($_POST["book{$i}_author"]  ?? '');
            $br = trim($_POST["book{$i}_reason"]  ?? '');
            $bq = trim($_POST["book{$i}_search"]  ?? '');
            if ($bt) $bs->execute([$id, $i, $bt, $ba, $br, $bq]);
        }

        set_flash('success', 'Post updated successfully.');
        header('Location: ' . BLOG_URL . '/admin/');
        exit;
    }
}

function make_slug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Post — <?= h(BLOG_NAME) ?></title>
  <link rel="stylesheet" href="<?= h(BLOG_URL) ?>/admin/admin.css">
</head>
<body>

<nav class="admin-nav">
  <a href="<?= h(BLOG_URL) ?>/admin/" class="brand">Health<span>Cyber</span> <small style="font-weight:400;font-size:12px">Admin</small></a>
  <div class="admin-nav-links">
    <a href="<?= h(BLOG_URL) ?>/admin/">Posts</a>
    <a href="<?= h(BLOG_URL) ?>/admin/regenerate.php">Generate</a>
    <a href="<?= h(BLOG_URL) ?>/admin/settings.php">Settings</a>
    <a href="<?= h(BLOG_URL) ?>/" target="_blank">View Blog</a>
    <a href="<?= h(BLOG_URL) ?>/admin/?logout=1" class="danger">Logout</a>
  </div>
</nav>

<div class="admin-wrap">
  <?= flash_html() ?>
  <?php foreach ($errors as $e): ?>
    <div class="flash" style="background:#c62828"><?= h($e) ?></div>
  <?php endforeach; ?>

  <div class="page-hdr">
    <h1>Edit Post #<?= $id ?></h1>
    <div style="display:flex;gap:8px">
      <a href="<?= h(BLOG_URL) ?>/post.php?slug=<?= h($post['slug']) ?>" class="btn btn-secondary" target="_blank">View Post</a>
      <a href="<?= h(BLOG_URL) ?>/admin/" class="btn btn-secondary">Back</a>
    </div>
  </div>

  <form method="POST" action="">
    <?= csrf_field() ?>

    <div class="card">
      <div class="card-title">Post Details</div>

      <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" value="<?= h($post['title']) ?>" required>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="form-group">
          <label class="form-label">Slug</label>
          <input type="text" name="slug" class="form-control" value="<?= h($post['slug']) ?>">
          <div class="form-hint">URL-friendly identifier</div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Published</option>
            <option value="draft"     <?= $post['status'] === 'draft'     ? 'selected' : '' ?>>Draft</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Category</label>
        <select name="category_id" class="form-control">
          <option value="">— None —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $post['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Tags</label>
        <input type="text" name="tags" class="form-control" value="<?= h($post['tags']) ?>">
        <div class="form-hint">Comma-separated: hipaa, risk-assessment, compliance</div>
      </div>

      <div class="form-group">
        <label class="form-label">Excerpt</label>
        <textarea name="excerpt" class="form-control" style="min-height:80px"><?= h($post['excerpt']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label">Meta Description <small style="font-weight:400;color:var(--text-muted)">(max 160 chars)</small></label>
        <input type="text" name="meta_description" class="form-control" maxlength="160" value="<?= h($post['meta_description']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Body (HTML)</label>
        <textarea name="body" class="form-control" style="min-height:400px"><?= h($post['body']) ?></textarea>
      </div>
    </div>

    <!-- Books -->
    <div class="card">
      <div class="card-title">Affiliate Book Recommendations</div>
      <div class="books-form-grid">
        <?php for ($i = 1; $i <= 3; $i++): $b = $books[$i - 1]; ?>
        <div class="book-form-card">
          <div class="book-num">Book <?= $i ?></div>
          <div class="form-group">
            <label class="form-label">Title</label>
            <input type="text" name="book<?= $i ?>_title" class="form-control" value="<?= h($b['title']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Author</label>
            <input type="text" name="book<?= $i ?>_author" class="form-control" value="<?= h($b['author']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Why Relevant</label>
            <textarea name="book<?= $i ?>_reason" class="form-control" style="min-height:80px"><?= h($b['reason']) ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Amazon Search Query</label>
            <input type="text" name="book<?= $i ?>_search" class="form-control" value="<?= h($b['search_query']) ?>">
            <div class="form-hint">Used to build the Amazon affiliate link</div>
          </div>
        </div>
        <?php endfor; ?>
      </div>
    </div>

    <div style="display:flex;gap:12px;justify-content:flex-end">
      <a href="<?= h(BLOG_URL) ?>/admin/" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</div>
</body>
</html>
