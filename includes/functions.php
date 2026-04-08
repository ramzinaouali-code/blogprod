<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';

// ─── Output Escaping ──────────────────────────────────────────────────────────
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── Thumbnail ────────────────────────────────────────────────────────────────
function post_thumbnail_css(string $slug, string $color = '#1a73e8'): string {
    $hue = abs(crc32($slug)) % 360;
    $hue2 = ($hue + 40) % 360;
    return "linear-gradient(135deg, {$color}, hsl({$hue2},55%,28%))";
}

// ─── Amazon Affiliate URL ────────────────────────────────────────────────────
function amazon_url(string $search_query): string {
    return 'https://www.amazon.com/s?k=' . urlencode($search_query) . '&tag=' . AMAZON_TAG;
}

// ─── Pagination ───────────────────────────────────────────────────────────────
function paginate(int $total, int $per_page, int $current_page): array {
    $total_pages = max(1, (int)ceil($total / $per_page));
    $current_page = max(1, min($current_page, $total_pages));
    return [
        'total'        => $total,
        'per_page'     => $per_page,
        'current_page' => $current_page,
        'total_pages'  => $total_pages,
        'offset'       => ($current_page - 1) * $per_page,
    ];
}

function pagination_html(array $p, string $base_url): string {
    if ($p['total_pages'] <= 1) return '';
    $html = '<nav class="pagination" aria-label="Pagination"><ul>';
    if ($p['current_page'] > 1) {
        $prev = $p['current_page'] - 1;
        $html .= '<li><a href="' . h($base_url . '?page=' . $prev) . '">&laquo; Prev</a></li>';
    }
    for ($i = 1; $i <= $p['total_pages']; $i++) {
        $active = $i === $p['current_page'] ? ' class="active"' : '';
        $html .= '<li' . $active . '><a href="' . h($base_url . '?page=' . $i) . '">' . $i . '</a></li>';
    }
    if ($p['current_page'] < $p['total_pages']) {
        $next = $p['current_page'] + 1;
        $html .= '<li><a href="' . h($base_url . '?page=' . $next) . '">Next &raquo;</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $token = $_POST['csrf_token'] ?? '';
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

// ─── Post Queries ─────────────────────────────────────────────────────────────
function get_posts(int $limit = POSTS_PER_PAGE, int $offset = 0, ?int $category_id = null): array {
    $db = get_db();
    if ($category_id !== null) {
        $stmt = $db->prepare(
            'SELECT p.*, c.name AS category_name, c.color AS category_color, c.slug AS category_slug
             FROM posts p LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = "published" AND p.category_id = ?
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$category_id, $limit, $offset]);
    } else {
        $stmt = $db->prepare(
            'SELECT p.*, c.name AS category_name, c.color AS category_color, c.slug AS category_slug
             FROM posts p LEFT JOIN categories c ON p.category_id = c.id
             WHERE p.status = "published"
             ORDER BY p.created_at DESC LIMIT ? OFFSET ?'
        );
        $stmt->execute([$limit, $offset]);
    }
    return $stmt->fetchAll();
}

function count_posts(?int $category_id = null): int {
    $db = get_db();
    if ($category_id !== null) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE status = "published" AND category_id = ?');
        $stmt->execute([$category_id]);
    } else {
        $stmt = $db->query('SELECT COUNT(*) FROM posts WHERE status = "published"');
    }
    return (int)$stmt->fetchColumn();
}

function get_post_by_slug(string $slug): ?array {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT p.*, c.name AS category_name, c.color AS category_color, c.slug AS category_slug
         FROM posts p LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.slug = ? AND p.status = "published"'
    );
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_post_books(int $post_id): array {
    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT * FROM affiliate_books WHERE post_id = ? ORDER BY position ASC'
    );
    $stmt->execute([$post_id]);
    return $stmt->fetchAll();
}

function get_adjacent_posts(int $post_id): array {
    $db = get_db();
    $prev = $db->prepare(
        'SELECT id, slug, title FROM posts WHERE status="published" AND id < ? ORDER BY id DESC LIMIT 1'
    );
    $prev->execute([$post_id]);
    $next = $db->prepare(
        'SELECT id, slug, title FROM posts WHERE status="published" AND id > ? ORDER BY id ASC LIMIT 1'
    );
    $next->execute([$post_id]);
    return ['prev' => $prev->fetch() ?: null, 'next' => $next->fetch() ?: null];
}

function get_categories(): array {
    return get_db()->query(
        'SELECT c.*, COUNT(p.id) AS post_count
         FROM categories c LEFT JOIN posts p ON p.category_id = c.id AND p.status = "published"
         GROUP BY c.id ORDER BY c.name ASC'
    )->fetchAll();
}

function get_recent_posts(int $limit = 5): array {
    $stmt = get_db()->prepare(
        'SELECT id, slug, title, created_at FROM posts WHERE status = "published" ORDER BY created_at DESC LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function format_date(string $datetime): string {
    return date('M j, Y', strtotime($datetime));
}

function reading_time(string $body): int {
    $words = str_word_count(strip_tags($body));
    return max(1, (int)round($words / 200));
}
