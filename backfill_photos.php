<?php
/**
 * Backfill photo_url for existing posts using Pexels API.
 * HTTP: https://yourdomain.com/backfill_photos.php?token=CRON_TOKEN
 * CLI:  php backfill_photos.php --local
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    if (!hash_equals(CRON_TOKEN, $_GET['token'] ?? '')) {
        http_response_code(403); die('Forbidden');
    }
    header('Content-Type: text/plain');
}

$db    = get_db();
// ?force=1 replaces ALL photos (including existing Picsum ones)
$force = isset($_GET['force']) || in_array('--force', $argv ?? []);
$sql   = $force
    ? "SELECT id, slug, title, tags FROM posts"
    : "SELECT id, slug, title, tags FROM posts WHERE photo_url = '' OR photo_url IS NULL";
$posts = $db->query($sql)->fetchAll();

if (empty($posts)) {
    echo "All posts already have photos.\n";
    exit;
}

echo "Backfilling photos for " . count($posts) . " posts...\n";
echo "Using: " . (PEXELS_API_KEY ? "Pexels API" : "Picsum fallback") . "\n\n";

foreach ($posts as $post) {
    // Use title + tags for the most relevant Pexels search
    $search    = $post['title'] . ' ' . $post['tags'];
    $photo_url = fetch_photo($search);

    $db->prepare("UPDATE posts SET photo_url = ? WHERE id = ?")
       ->execute([$photo_url, $post['id']]);

    echo "✓ Post #{$post['id']}: {$post['title']}\n  → {$photo_url}\n\n";

    sleep(1); // Respect Pexels rate limits
}

echo "Done.\n";

// ─── Pexels + Picsum fetcher ──────────────────────────────────────────────────
function fetch_photo(string $keywords): string {
    if (PEXELS_API_KEY) {
        $photo = fetch_pexels_photo($keywords);
        if ($photo) return $photo;
    }
    $seed = substr(preg_replace('/[^a-z0-9]/', '', strtolower($keywords)), 0, 40);
    return "https://picsum.photos/seed/{$seed}/1200/630";
}

function fetch_pexels_photo(string $keywords): string {
    $clean = trim(preg_replace('/[^a-z0-9,\- ]/i', ' ', $keywords));
    $query = urlencode(preg_replace('/\s+/', ' ', str_replace(',', ' ', $clean)));

    $ch = curl_init("https://api.pexels.com/v1/search?query={$query}&per_page=1&orientation=landscape");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . PEXELS_API_KEY],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'HealthCyberInsights/1.0',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return '';
    $data = json_decode($body, true);
    $url  = $data['photos'][0]['src']['large2x'] ?? '';

    if (!$url) {
        // Retry with broader terms
        return fetch_pexels_photo('healthcare technology security');
    }
    return $url;
}
