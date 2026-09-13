<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/legal-pages.php';
require_once __DIR__ . '/includes/feature-pages.php';
require_once __DIR__ . '/includes/solution-pages.php';
require_once __DIR__ . '/includes/about-page.php';
require_once __DIR__ . '/includes/contact-page.php';
header('Content-Type: application/xml; charset=utf-8');
$products = db()->query('SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
$blogPosts = blog_public_posts(PHP_INT_MAX);
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= e(APP_URL) ?>/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
    <url><loc><?= e(APP_URL) ?>/kurumsal-sayfalar</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>
<?php foreach (dijirota_legal_pages() as $slug => $page): ?>
    <url><loc><?= e(APP_URL) ?>/<?= e($slug) ?></loc><changefreq>yearly</changefreq><priority>0.4</priority></url>
<?php endforeach; ?>
<?php foreach (dijirota_feature_pages() as $slug => $page): ?>
    <url><loc><?= e(APP_URL) ?>/<?= e($slug) ?></loc><changefreq>monthly</changefreq><priority>0.5</priority></url>
<?php endforeach; ?>
<?php foreach (dijirota_solution_pages() as $slug => $page): ?>
    <url><loc><?= e(APP_URL) ?>/<?= e($slug) ?></loc><changefreq>monthly</changefreq><priority>0.6</priority></url>
<?php endforeach; ?>
    <url><loc><?= e(APP_URL) ?>/hakkimizda</loc><changefreq>yearly</changefreq><priority>0.5</priority></url>
    <url><loc><?= e(APP_URL) ?>/iletisim</loc><changefreq>yearly</changefreq><priority>0.6</priority></url>
<?php foreach ($products as $product): ?>
    <url><loc><?= e(APP_URL) ?>/kurumsal-sayfa/<?= e($product['slug']) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($product['updated_at']))) ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
<?php endforeach; ?>
    <url><loc><?= e(APP_URL) ?>/blog</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>
<?php foreach ($blogPosts as $post): ?>
    <url><loc><?= e(APP_URL) ?>/blog/<?= e($post['slug']) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($post['updated_at']))) ?></lastmod><changefreq>monthly</changefreq><priority>0.7</priority></url>
<?php endforeach; ?>
</urlset>
