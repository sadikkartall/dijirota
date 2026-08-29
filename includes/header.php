<?php
declare(strict_types=1);
$page_title = $page_title ?? 'Dijirota | Profesyonel Kurumsal Sayfalar';
$meta_description = $meta_description ?? 'Sektörünüze özel, yönetim panelli profesyonel kurumsal sayfalar.';
$meta_keywords = $meta_keywords ?? 'kurumsal sayfa, hazır kurumsal web, yönetim panelli site, dijirota';
$user = current_user();
$flash_items = flashes();
?><!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($meta_description) ?>">
    <meta name="keywords" content="<?= e($meta_keywords) ?>">
    <meta name="robots" content="<?= !empty($noindex) ? 'noindex,nofollow' : 'index,follow' ?>">
    <link rel="canonical" href="<?= e(APP_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Dijirota">
    <meta property="og:title" content="<?= e($page_title) ?>">
    <meta property="og:description" content="<?= e($meta_description) ?>">
    <meta property="og:url" content="<?= e(APP_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <link rel="icon" href="<?= e(APP_URL) ?>/assets/images/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/app.css?v=20260829-5">
    <?php if (!empty($structured_data)) { render_json_ld($structured_data); } ?>
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?= e(APP_URL) ?>/"><span class="brand-mark">D</span><span>Dijirota</span></a>
        <button class="nav-toggle" aria-label="Menüyü aç/kapat" data-nav-toggle>☰</button>
        <nav class="main-nav" data-nav>
            <a href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Kurumsal Sayfalar</a>
            <a href="<?= e(APP_URL) ?>/#nasil-calisir">Nasıl Çalışır?</a>
            <a href="<?= e(APP_URL) ?>/#iletisim">İletişim</a>
            <a class="cart-link" href="<?= e(APP_URL) ?>/sepet">Sepet <span><?= cart_count() ?></span></a>
            <?php if ($user): ?>
                <a class="nav-account" href="<?= e(APP_URL) ?>/panel">Hesabım</a>
                <?php if (in_array($user['role'], ['admin', 'support'], true)): ?><a href="<?= e(APP_URL) ?>/admin">Yönetim</a><?php endif; ?>
                <a href="<?= e(APP_URL) ?>/cikis">Çıkış</a>
            <?php else: ?>
                <a class="nav-account" href="<?= e(APP_URL) ?>/giris">Giriş Yap</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php foreach ($flash_items as $flash): ?><div class="container"><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div></div><?php endforeach; ?>
