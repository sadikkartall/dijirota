<?php
declare(strict_types=1);
$posts = blog_public_posts(PHP_INT_MAX);
if (preg_match('~^blog/covers/([a-f0-9-]{36}\.(png|jpg))$~', $path, $match)) {
    $cover = $match[1];
    $allowed = array_filter($posts, static fn(array $post): bool => ($post['cover'] ?? '') === $cover);
    $file = __DIR__ . '/../blog-engine/content/blog/covers/' . $cover;
    if (!$allowed || !is_file($file)) {http_response_code(404); exit;}
    header('Content-Type: ' . (str_ends_with($cover, '.jpg') ? 'image/jpeg' : 'image/png'));
    header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-cache');
    readfile($file); exit;
}
if ($path === 'blog/feed.xml') {
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>DİJİROTA Blog</title><link>' . e(APP_URL) . '/blog</link><description>Dijital rehberler</description><language>tr</language>';
    foreach ($posts as $post) echo '<item><title>' . e($post['title']) . '</title><link>' . e(APP_URL . '/blog/' . $post['slug']) . '</link><guid>' . e(APP_URL . '/blog/' . $post['slug']) . '</guid><description>' . e($post['description']) . '</description><pubDate>' . e(date(DATE_RSS, strtotime($post['publishedAt']))) . '</pubDate></item>';
    echo '</channel></rss>'; exit;
}
$post = null;
if ($path !== 'blog') {
    foreach ($posts as $item) if ($path === 'blog/' . $item['slug']) {$post = $item; break;}
    if (!$post) {
        http_response_code(404); begin_page('Yazı bulunamadı | DİJİROTA', 'Bu yazı bulunamadı.', true);
        echo '<section class="section"><div class="container"><h1>Yazı bulunamadı.</h1><a href="/blog">Bloga dön →</a></div></section>'; end_page(); exit;
    }
}
$schema = $post ? ['@context'=>'https://schema.org','@type'=>'BlogPosting',
    'headline'=>$post['title'],'description'=>$post['description'],
    'datePublished'=>$post['publishedAt'],'dateModified'=>$post['approvedAt'],
    'author'=>['@type'=>'Organization','name'=>'DİJİROTA','url'=>APP_URL],
    'mainEntityOfPage'=>APP_URL . '/blog/' . $post['slug'],
] : null;
if ($post && $post['cover']) $schema['image'] = APP_URL . '/blog/covers/' . $post['cover'];
begin_page($post ? $post['title'] . ' | DİJİROTA Blog' : 'Blog | DİJİROTA', $post['description'] ?? 'İşletmeler için web, yazılım ve dijital görünürlük rehberleri.', false, $schema);
echo '<link rel="stylesheet" href="/assets/css/blog-v2.css"><section class="section"><div class="container blog-v2">';
if ($post) {
    echo '<a class="text-link" href="/blog">← Tüm yazılar</a>' . $post['html'];
} else {
    echo '<div class="section-heading"><div><div class="eyebrow">DİJİROTA BLOG</div><h1>Dijital dünyaya rehberin.</h1><p>İşletmeniz için fikirler, teknik rehberler ve uygulanabilir bilgiler.</p></div><a class="text-link" href="/blog/feed.xml">RSS ↗</a></div>';
    if (!$posts) echo '<div class="blog-empty"><h2>İlk yazı için yer hazır.</h2><p>Onaylanan yazılar burada yer alacak.</p></div>';
    echo '<div class="blog-grid">';
    foreach ($posts as $item) {
        echo '<article class="blog-card"><a href="/blog/' . e($item['slug']) . '">';
        if ($item['cover']) echo '<img src="/blog/covers/' . e($item['cover']) . '" alt="' . e($item['coverAlt']) . '" loading="lazy">';
        echo '<div class="blog-card-content"><span class="eyebrow">' . e(date('d.m.Y', strtotime($item['publishedAt']))) . '</span><h2>' . e($item['title']) . '</h2><p>' . e($item['description']) . '</p></div></a></article>';
    }
    echo '</div>';
}
echo '</div></section>'; end_page();
