<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$products = db()->query('SELECT slug, updated_at FROM products WHERE is_active = 1 ORDER BY id')->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?>';
?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url><loc><?= e(APP_URL) ?>/</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>
    <url><loc><?= e(APP_URL) ?>/kurumsal-sayfalar</loc><changefreq>weekly</changefreq><priority>0.9</priority></url>
<?php foreach ($products as $product): ?>
    <url><loc><?= e(APP_URL) ?>/kurumsal-sayfa/<?= e($product['slug']) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($product['updated_at']))) ?></lastmod><changefreq>monthly</changefreq><priority>0.8</priority></url>
<?php endforeach; ?>
</urlset>
