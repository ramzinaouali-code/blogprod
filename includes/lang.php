<?php
/**
 * Language support — bilingual EN/FR cookie-based detection
 * Cookie name: hcb_lang — values: 'en' or 'fr'
 * TTL: 30 days
 */

function get_lang(): string {
    $lang = $_COOKIE['hcb_lang'] ?? 'en';
    return ($lang === 'fr') ? 'fr' : 'en';
}

function set_lang(string $lang): void {
    $lang = ($lang === 'fr') ? 'fr' : 'en';
    setcookie('hcb_lang', $lang, [
        'expires'  => time() + 30 * 86400,
        'path'     => '/',
        'samesite' => 'Lax',
        'secure'   => isset($_SERVER['HTTPS']),
    ]);
}

/**
 * Translate a UI string key to the given language.
 * Falls back to English if a French translation is missing.
 */
function t(string $key, string $lang = ''): string {
    if (!$lang) $lang = get_lang();
    static $map = null;
    if ($map === null) {
        $map = _lang_map();
    }
    if ($lang === 'fr') {
        return $map['fr'][$key] ?? ($map['en'][$key] ?? $key);
    }
    return $map['en'][$key] ?? $key;
}

function _lang_map(): array {
    return [
        'en' => [
            // Nav
            'nav.home'              => 'Home',
            'nav.admin'             => 'Admin',
            // Index
            'index.read_more'       => 'Read Article',
            'index.min_read'        => 'min read',
            'index.latest'          => 'Latest Articles',
            'index.articles_count'  => 'articles',
            'index.no_posts'        => 'No posts yet',
            'index.no_posts_desc'   => 'Posts are being generated. Check back soon, or trigger generation via the admin panel.',
            // Sidebar
            'sidebar.latest'        => 'Latest Posts',
            'sidebar.topics'        => 'Topics',
            'sidebar.about'         => 'About This Blog',
            'sidebar.articles_lbl'  => 'Articles',
            'sidebar.topics_lbl'    => 'Topics',
            'sidebar.about_desc'    => 'AI-assisted analysis for healthcare cybersecurity, privacy, and compliance professionals.',
            // Newsletter banner
            'news.title'            => 'Stay Informed',
            'news.desc'             => 'Get the latest healthcare cybersecurity insights delivered to your inbox. No spam — just practical guidance.',
            'news.placeholder'      => 'your@email.com',
            'news.button'           => 'Subscribe Free',
            'news.disclaimer'       => 'Unsubscribe at any time. We respect your privacy.',
            // Post
            'post.breadcrumb_home'  => 'Home',
            'post.min_read'         => 'min read',
            'post.books_title'      => '&#128218; Recommended Reading',
            'post.books_desc'       => 'Books our AI recommends to deepen your knowledge on this topic.',
            'post.books_by'         => 'by',
            'post.amazon_link'      => 'View on Amazon &rarr;',
            'post.nav_prev'         => '&larr; Previous',
            'post.nav_next'         => 'Next &rarr;',
            'post.no_older'         => 'No older posts',
            'post.no_newer'         => 'No newer posts',
            'post.not_found'        => 'Post not found.',
            'post.go_home'          => 'Go home',
            // Category
            'cat.article_single'    => 'article',
            'cat.article_plural'    => 'articles',
            'cat.insights_prefix'   => 'Healthcare cybersecurity insights on',
            'cat.all_topics'        => 'All Topics',
            'cat.no_posts'          => 'No posts yet',
            'cat.no_posts_desc'     => 'Posts in this category are coming soon.',
            // Subscribe page
            'sub.title'             => 'Subscribe to HealthCyber Insights',
            'sub.desc'              => 'Join healthcare cybersecurity professionals who rely on our newsletter for practical, actionable guidance.',
            'sub.email_label'       => 'Email address',
            'sub.email_ph'          => 'you@organization.com',
            'sub.btn'               => 'Subscribe',
            'sub.ok_title'          => 'Check your inbox!',
            'sub.ok_desc'           => 'We sent you a confirmation email. Click the link inside to activate your subscription.',
            'sub.already'           => 'This email address is already subscribed.',
            'sub.invalid'           => 'Please enter a valid email address.',
            'sub.confirmed_title'   => 'Subscription confirmed!',
            'sub.confirmed_desc'    => 'You are now subscribed to HealthCyber Insights. Welcome!',
            // Unsubscribe page
            'unsub.title'           => 'You have been unsubscribed',
            'unsub.desc'            => 'We have removed your email from our list. Sorry to see you go!',
            'unsub.back'            => 'Back to home',
            'unsub.invalid'         => 'Invalid or expired unsubscribe link.',
            // Footer
            'footer.disclaimer'     => 'AI-generated content reviewed for accuracy. Not legal or compliance advice.',
            'footer.rights'         => 'All rights reserved.',
            'footer.tagline'        => 'Practical guidance for healthcare security and compliance professionals.',
            'footer.topics_hd'      => 'Topics',
            'footer.about_hd'       => 'About',
            'footer.admin_link'     => 'Admin Panel',
            'footer.affiliate'      => '<strong>Affiliate Disclosure:</strong> This site participates in the Amazon Associates program. Book links are affiliate links — we may earn a small commission at no extra cost to you. We only recommend books we believe provide genuine value to healthcare security professionals.',
        ],
        'fr' => [
            // Nav
            'nav.home'              => 'Accueil',
            'nav.admin'             => 'Admin',
            // Index
            'index.read_more'       => "Lire l'article",
            'index.min_read'        => 'min de lecture',
            'index.latest'          => 'Derniers articles',
            'index.articles_count'  => 'articles',
            'index.no_posts'        => "Aucun article pour l'instant",
            'index.no_posts_desc'   => 'Les articles sont en cours de génération. Revenez bientôt.',
            // Sidebar
            'sidebar.latest'        => 'Derniers articles',
            'sidebar.topics'        => 'Sujets',
            'sidebar.about'         => 'À propos',
            'sidebar.articles_lbl'  => 'Articles',
            'sidebar.topics_lbl'    => 'Sujets',
            'sidebar.about_desc'    => "Analyses assistées par l'IA pour les professionnels de la cybersécurité, de la confidentialité et de la conformité en santé.",
            // Newsletter banner
            'news.title'            => 'Restez informé',
            'news.desc'             => 'Recevez les dernières analyses en cybersécurité de la santé directement dans votre boîte courriel. Pas de spam — uniquement des conseils pratiques.',
            'news.placeholder'      => 'votre@courriel.com',
            'news.button'           => "S'abonner gratuitement",
            'news.disclaimer'       => 'Désabonnez-vous à tout moment. Nous respectons votre vie privée.',
            // Post
            'post.breadcrumb_home'  => 'Accueil',
            'post.min_read'         => 'min de lecture',
            'post.books_title'      => '&#128218; Lectures recommandées',
            'post.books_desc'       => "Livres recommandés par notre IA pour approfondir vos connaissances sur ce sujet.",
            'post.books_by'         => 'par',
            'post.amazon_link'      => 'Voir sur Amazon &rarr;',
            'post.nav_prev'         => '&larr; Précédent',
            'post.nav_next'         => 'Suivant &rarr;',
            'post.no_older'         => "Pas d'articles plus anciens",
            'post.no_newer'         => "Pas d'articles plus récents",
            'post.not_found'        => 'Article introuvable.',
            'post.go_home'          => "Retour à l'accueil",
            // Category
            'cat.article_single'    => 'article',
            'cat.article_plural'    => 'articles',
            'cat.insights_prefix'   => 'Analyses en cybersécurité de la santé sur',
            'cat.all_topics'        => 'Tous les sujets',
            'cat.no_posts'          => "Aucun article pour l'instant",
            'cat.no_posts_desc'     => 'Les articles de cette catégorie arrivent bientôt.',
            // Subscribe page
            'sub.title'             => 'S\'abonner à HealthCyber Insights',
            'sub.desc'              => 'Rejoignez les professionnels de la cybersécurité de la santé qui comptent sur notre infolettre pour des conseils pratiques.',
            'sub.email_label'       => 'Adresse courriel',
            'sub.email_ph'          => 'vous@organisation.com',
            'sub.btn'               => "S'abonner",
            'sub.ok_title'          => 'Vérifiez votre boîte courriel !',
            'sub.ok_desc'           => 'Nous vous avons envoyé un courriel de confirmation. Cliquez sur le lien pour activer votre abonnement.',
            'sub.already'           => 'Cette adresse courriel est déjà abonnée.',
            'sub.invalid'           => 'Veuillez entrer une adresse courriel valide.',
            'sub.confirmed_title'   => 'Abonnement confirmé !',
            'sub.confirmed_desc'    => 'Vous êtes maintenant abonné à HealthCyber Insights. Bienvenue !',
            // Unsubscribe page
            'unsub.title'           => 'Vous avez été désabonné',
            'unsub.desc'            => 'Nous avons retiré votre courriel de notre liste. Dommage de vous voir partir !',
            'unsub.back'            => "Retour à l'accueil",
            'unsub.invalid'         => 'Lien de désabonnement invalide ou expiré.',
            // Footer
            'footer.disclaimer'     => "Contenu généré par IA, révisé pour exactitude. Pas un avis juridique ou de conformité.",
            'footer.rights'         => 'Tous droits réservés.',
            'footer.tagline'        => 'Conseils pratiques pour les professionnels de la sécurité et de la conformité en santé.',
            'footer.topics_hd'      => 'Sujets',
            'footer.about_hd'       => 'À propos',
            'footer.admin_link'     => 'Panneau admin',
            'footer.affiliate'      => '<strong>Divulgation d\'affiliation :</strong> Ce site participe au programme Amazon Associates. Les liens de livres sont des liens affiliés — nous pouvons recevoir une petite commission sans frais supplémentaires pour vous. Nous recommandons uniquement des livres qui apportent une vraie valeur aux professionnels de la santé.',
        ],
    ];
}
