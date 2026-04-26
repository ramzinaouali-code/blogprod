<?php
/**
 * Email functions — Brevo Transactional API v3
 * Requires env vars: BREVO_API_KEY, BREVO_FROM_EMAIL, BREVO_FROM_NAME
 * Never hardcode credentials here — always read from config constants.
 */
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/db.php';

// ─── Core sender ──────────────────────────────────────────────────────────────

/**
 * Send a single transactional email via Brevo API v3.
 * Returns true on success, false on any failure.
 */
function send_email(string $to_email, string $to_name, string $subject, string $html): bool {
    if (!BREVO_API_KEY || !BREVO_FROM_EMAIL) {
        error_log('Brevo: BREVO_API_KEY or BREVO_FROM_EMAIL not set — email not sent');
        return false;
    }

    $payload = json_encode([
        'sender'      => ['email' => BREVO_FROM_EMAIL, 'name' => BREVO_FROM_NAME],
        'to'          => [['email' => $to_email, 'name' => $to_name ?: $to_email]],
        'subject'     => $subject,
        'htmlContent' => $html,
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_USERAGENT      => 'HealthCyberInsights/1.0',
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code >= 200 && $code < 300) {
        return true;
    }
    error_log("Brevo send_email failed (HTTP {$code}): {$body}");
    return false;
}

// ─── Confirmation email ───────────────────────────────────────────────────────

/**
 * Send a subscription confirmation email with a one-click confirm button.
 * The token links to subscribe.php?confirm=TOKEN which activates the subscription.
 */
function send_confirmation_email(string $email, string $token): bool {
    $confirm_url = BLOG_URL . '/subscribe.php?confirm=' . urlencode($token);
    $unsub_url   = BLOG_URL . '/unsubscribe.php?token=' . urlencode($token);

    $subject = 'Confirm your subscription — HealthCyber Insights';
    $html    = '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
  <tr><td style="background:#1a1a2e;padding:20px 24px;text-align:center">
    <span style="font-size:22px;font-weight:800;color:#fff">Health<span style="color:#1a73e8;font-style:italic">Cyber</span><span style="color:rgba(255,255,255,.65);font-weight:500;font-size:.72em"> Insights</span></span>
  </td></tr>
  <tr><td style="padding:32px 28px">
    <h2 style="color:#1a1a2e;margin:0 0 14px">Confirm your subscription</h2>
    <p style="color:#4b5563;line-height:1.6;margin:0 0 16px">
      Thank you for subscribing to <strong>HealthCyber Insights</strong> — practical cybersecurity,
      privacy, and compliance guidance for healthcare professionals.
    </p>
    <p style="color:#4b5563;line-height:1.6;margin:0 0 28px">
      Click the button below to confirm your email address and start receiving our newsletter.
    </p>
    <div style="text-align:center;margin-bottom:28px">
      <a href="' . $confirm_url . '"
         style="background:#1a73e8;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:700;font-size:16px;display:inline-block">
        &#10003; Confirm Subscription
      </a>
    </div>
    <p style="font-size:13px;color:#9ca3af;line-height:1.5;margin:0">
      If you did not request this subscription, you can safely ignore this email — no action needed.<br><br>
      <a href="' . $unsub_url . '" style="color:#9ca3af;text-decoration:underline">Unsubscribe</a>
    </p>
  </td></tr>
  <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 28px;text-align:center">
    <p style="font-size:11px;color:#9ca3af;margin:0">
      HealthCyber Insights &middot; AI-assisted healthcare cybersecurity analysis
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';

    return send_email($email, $email, $subject, $html);
}

// ─── Newsletter ───────────────────────────────────────────────────────────────

/**
 * Send a newsletter to all confirmed subscribers announcing a new post.
 * Called from generate.php after the EN post is inserted.
 * Rate-limited to 100 ms between sends to respect Brevo's API limits.
 */
function send_newsletter(int $post_id): void {
    if (!BREVO_API_KEY || !BREVO_FROM_EMAIL) {
        error_log('Brevo: newsletter skipped — API key or sender email not configured');
        return;
    }

    $db   = get_db();
    $stmt = $db->prepare(
        'SELECT p.*, c.name AS category_name, c.color AS category_color
         FROM posts p LEFT JOIN categories c ON p.category_id = c.id
         WHERE p.id = ?'
    );
    $stmt->execute([$post_id]);
    $p = $stmt->fetch();
    if (!$p) {
        error_log("Brevo: newsletter skipped — post {$post_id} not found");
        return;
    }

    $subs = $db->query(
        "SELECT email, token FROM subscribers WHERE status = 'confirmed'"
    )->fetchAll(PDO::FETCH_ASSOC);

    if (empty($subs)) {
        error_log("Brevo: newsletter skipped — no confirmed subscribers");
        return;
    }

    $post_url   = BLOG_URL . '/post.php?slug=' . urlencode($p['slug']);
    $unsub_base = BLOG_URL . '/unsubscribe.php?token=';
    $subject    = 'New article: ' . $p['title'];

    // Build photo block
    $photo_html = $p['photo_url']
        ? '<img src="' . htmlspecialchars($p['photo_url'], ENT_QUOTES) . '" alt="" style="width:100%;height:200px;object-fit:cover;display:block">'
        : '';

    // Build category badge
    $cat_html = $p['category_name']
        ? '<div style="margin-bottom:12px"><span style="background:' . htmlspecialchars($p['category_color'] ?? '#1a73e8', ENT_QUOTES) . ';color:#fff;font-size:11px;font-weight:700;padding:3px 10px;border-radius:3px;text-transform:uppercase;letter-spacing:.5px">'
          . htmlspecialchars($p['category_name'], ENT_QUOTES) . '</span></div>'
        : '';

    $html_template = '<!DOCTYPE html><html lang="en"><body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)">
  <tr><td style="background:#1a1a2e;padding:20px 24px;text-align:center">
    <span style="font-size:22px;font-weight:800;color:#fff">Health<span style="color:#1a73e8;font-style:italic">Cyber</span><span style="color:rgba(255,255,255,.65);font-weight:500;font-size:.72em"> Insights</span></span>
  </td></tr>
  ' . ($photo_html ? "<tr><td>{$photo_html}</td></tr>" : '') . '
  <tr><td style="padding:28px">
    ' . $cat_html . '
    <h1 style="font-size:22px;color:#1a1a2e;margin:0 0 12px;line-height:1.3">'
      . htmlspecialchars($p['title'], ENT_QUOTES) . '</h1>
    <p style="color:#4b5563;line-height:1.6;margin:0 0 24px">'
      . htmlspecialchars($p['excerpt'], ENT_QUOTES) . '</p>
    <a href="' . $post_url . '"
       style="background:#1a73e8;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;display:inline-block">
      Read Full Article &rarr;
    </a>
  </td></tr>
  <tr><td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 28px">
    <p style="font-size:12px;color:#9ca3af;margin:0;line-height:1.5">
      You are receiving this because you subscribed to HealthCyber Insights.<br>
      <a href="' . $unsub_base . '{{TOKEN}}" style="color:#9ca3af;text-decoration:underline">Unsubscribe</a> at any time.
    </p>
  </td></tr>
</table>
</td></tr>
</table>
</body></html>';

    $sent = 0;
    foreach ($subs as $sub) {
        $html = str_replace('{{TOKEN}}', urlencode($sub['token']), $html_template);
        if (send_email($sub['email'], $sub['email'], $subject, $html)) {
            $sent++;
        }
        usleep(100000); // 100 ms between sends — respect Brevo rate limits
    }
    error_log("Newsletter sent to {$sent}/" . count($subs) . " subscribers for post {$post_id}");
}
