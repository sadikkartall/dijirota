<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/paytr.php';

function path_name(): string
{
    if (isset($_GET['path'])) {
        return trim((string) $_GET['path'], '/');
    }

    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $basePath = trim((string) parse_url(APP_URL, PHP_URL_PATH), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = trim(substr($path, strlen($basePath)), '/');
    }
    return $path;
}

function begin_page(string $title, string $description, bool $withNoIndex = false, ?array $structuredData = null): void
{
    global $page_title, $meta_description, $noindex, $structured_data;
    $page_title = $title;
    $meta_description = $description;
    $noindex = $withNoIndex;
    $structured_data = $structuredData;
    require __DIR__ . '/includes/header.php';
}

function end_page(): void
{
    require __DIR__ . '/includes/footer.php';
}

function product_card(array $product): void
{
    $features = json_decode((string) ($product['features_json'] ?? '[]'), true) ?: [];
    ?>
    <article class="product-card">
        <div class="product-visual">
            <img class="preview-image" src="<?= e(preview_image($product['slug'])) ?>" alt="<?= e($product['name']) ?> tanıtım görseli" loading="lazy">
            <div class="preview-shade"></div>
            <div class="preview-caption"><div class="preview-badge">DİJİROTA / HAZIR PAKET</div><span class="preview-title"><?= e(preview_title($product['name'])) ?></span><small><?= e($product['theme'] ?? 'Dijirota teması') ?></small></div>
        </div>
        <div class="product-body">
            <div class="eyebrow"><?= e($product['category']) ?></div>
            <h3><?= e($product['name']) ?></h3>
            <p><?= e($product['description']) ?></p>
            <div class="feature-row"><?php foreach (array_slice($features, 0, 3) as $feature): ?><span><?= e($feature) ?></span><?php endforeach; ?></div>
            <div class="product-footer"><div><small class="price-label">Tek paket fiyatı</small><strong><?= money((int) $product['price_kurus']) ?></strong></div><a class="card-detail-link" href="<?= e(APP_URL) ?>/kurumsal-sayfa/<?= e($product['slug']) ?>">İncele <span>↗</span></a></div>
        </div>
    </article>
    <?php
}

function get_order_by_number(string $number): ?array
{
    $statement = db()->prepare('SELECT * FROM orders WHERE order_number = ? LIMIT 1');
    $statement->execute([$number]);
    $order = $statement->fetch();
    return $order ?: null;
}

function user_owns_order(array $order): bool
{
    $user = current_user();
    return $user !== null && (int) $order['user_id'] === (int) $user['id'];
}

$path = path_name();
$segments = $path === '' ? [] : explode('/', $path);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'cart_add') {
            verify_csrf();
            $product = product_by_slug((string) ($_POST['slug'] ?? ''));
            if (!$product) {
                flash('error', 'Ürün bulunamadı.');
            } else {
                $_SESSION['cart'][] = (int) $product['id'];
                $_SESSION['cart'] = array_values(array_unique(array_map('intval', $_SESSION['cart'])));
                flash('success', 'Kurumsal sayfa sepetinize eklendi.');
            }
            redirect('sepet');
        }

        if ($action === 'cart_remove') {
            verify_csrf();
            $removeId = (int) ($_POST['product_id'] ?? 0);
            $_SESSION['cart'] = array_values(array_filter(cart_ids(), static fn (int $id): bool => $id !== $removeId));
            flash('success', 'Ürün sepetten çıkarıldı.');
            redirect('sepet');
        }

        if ($action === 'login' || $action === 'admin_login') {
            verify_csrf();
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            $statement = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
            $statement->execute([$email]);
            $user = $statement->fetch();
            $allowed = $action === 'admin_login' ? ($user && in_array($user['role'], ['admin', 'support'], true)) : ($user && $user['role'] === 'customer');
            if (!$allowed || !password_verify($password, $user['password_hash'])) {
                flash('error', 'E-posta veya şifre hatalı.');
                redirect($action === 'admin_login' ? 'admin' : 'giris');
            }
            login_user($user);
            flash('success', 'Hoş geldiniz, ' . $user['name'] . '.');
            redirect($action === 'admin_login' ? 'admin' : 'panel');
        }

        if ($action === 'register') {
            verify_csrf();
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = strtolower(trim((string) ($_POST['email'] ?? '')));
            $password = (string) ($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
                flash('error', 'Ad, geçerli bir e-posta ve en az 8 karakterli bir şifre girin.');
                redirect('kayit');
            }
            $exists = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $exists->execute([$email]);
            if ($exists->fetch()) {
                flash('error', 'Bu e-posta ile kayıtlı bir hesap zaten var.');
                redirect('giris');
            }
            $insert = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
            $insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $user = db()->query('SELECT * FROM users WHERE id = ' . (int) db()->lastInsertId())->fetch();
            login_user($user);
            flash('success', 'Hesabınız oluşturuldu.');
            redirect('panel');
        }

        if ($action === 'create_order') {
            verify_csrf();
            require_login();
            $items = cart_products();
            if (!$items) {
                flash('warning', 'Ödeme için sepetinizde ürün bulunmalı.');
                redirect('sepet');
            }
            $user = current_user();
            $total = array_sum(array_map(static fn (array $item): int => (int) $item['price_kurus'], $items));
            $number = 'DJ-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $insert = $pdo->prepare('INSERT INTO orders (order_number, user_id, total_kurus, customer_name, customer_email, customer_phone, customer_note) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $insert->execute([$number, (int) $user['id'], $total, $user['name'], $user['email'], trim((string) ($_POST['phone'] ?? '')), trim((string) ($_POST['note'] ?? ''))]);
                $orderId = (int) $pdo->lastInsertId();
                $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price_kurus, quantity) VALUES (?, ?, ?, ?, 1)');
                foreach ($items as $item) {
                    $itemInsert->execute([$orderId, (int) $item['id'], $item['name'], (int) $item['price_kurus']]);
                }
                $pdo->commit();
                $_SESSION['cart'] = [];
                redirect('odeme?order=' . urlencode($number));
            } catch (Throwable $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }

        if ($action === 'admin_order_status') {
            require_admin();
            verify_csrf();
            $allowed = ['awaiting_payment', 'paid', 'provisioning', 'active', 'failed', 'cancelled', 'refunded'];
            $status = (string) ($_POST['status'] ?? '');
            if (in_array($status, $allowed, true)) {
                $update = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $update->execute([$status, (int) $_POST['order_id']]);
                if ($status === 'paid') {
                    $orderStatement = db()->prepare('SELECT * FROM orders WHERE id = ?');
                    $orderStatement->execute([(int) $_POST['order_id']]);
                    $order = $orderStatement->fetch();
                    $items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
                    $items->execute([(int) $_POST['order_id']]);
                    foreach ($items->fetchAll() as $item) {
                        $exists = db()->prepare('SELECT id FROM customer_sites WHERE order_id = ? AND product_id = ? LIMIT 1');
                        $exists->execute([(int) $_POST['order_id'], (int) $item['product_id']]);
                        if (!$exists->fetch()) {
                            $site = db()->prepare("INSERT INTO customer_sites (user_id, order_id, product_id, site_name, status) VALUES (?, ?, ?, ?, 'pending')");
                            $site->execute([(int) $order['user_id'], (int) $order['id'], (int) $item['product_id'], $item['product_name']]);
                        }
                    }
                }
                flash('success', 'Sipariş durumu güncellendi.');
            }
            redirect('admin/siparisler');
        }

        if ($action === 'admin_site_update') {
            require_admin();
            verify_csrf();
            $allowed = ['pending', 'provisioning', 'waiting_customer', 'active', 'suspended'];
            $status = (string) ($_POST['status'] ?? 'pending');
            if (in_array($status, $allowed, true)) {
                $update = db()->prepare('UPDATE customer_sites SET status = ?, domain = ?, admin_url = ?, notes = ? WHERE id = ?');
                $update->execute([$status, trim((string) $_POST['domain']), trim((string) $_POST['admin_url']), trim((string) $_POST['notes']), (int) $_POST['site_id']]);
                flash('success', 'Kurulum kaydı güncellendi.');
            }
            redirect('admin/siteler');
        }
    }

    if ($path === 'cikis') {
        logout_user();
        flash('success', 'Güvenli çıkış yapıldı.');
        redirect('');
    }

    if ($path === '' || $path === 'index.php') {
        $products = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY is_featured DESC, id ASC')->fetchAll();
        begin_page('Dijirota | Profesyonel Kurumsal Sayfalar', 'İşletmenizi dijitalde profesyonel gösteren, yönetim panelli kurumsal sayfalar. 15.000 TL KDV dahil; domain ve hosting dahil.', false, ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => 'Dijirota', 'url' => APP_URL]);
        ?>
        <section class="hero-section"><div class="container hero-grid"><div><div class="eyebrow light">DİJİROTA KURUMSAL SAYFALAR</div><h1>İşletmeniz için profesyonel bir dijital başlangıç.</h1><p class="hero-copy">Sektörünüze özel tasarlanmış, yönetim panelli kurumsal sayfanızı seçin. Domain, hosting ve kurulum dahil; tek pakette hazır.</p><div class="hero-actions"><a class="button button-primary" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Kurumsal sayfaları keşfet <span>↗</span></a><a class="button button-ghost" href="#nasil-calisir">Nasıl çalışır?</a></div><div class="hero-proof"><span>15 sektör</span><span>Yönetim paneli</span><span>15.000 TL KDV dahil</span></div></div><div class="hero-showcase"><div class="floating-label">Dijital görünümünüzü güçlendirin</div><div class="showcase-back-card"><span>15 SEKTÖR</span><strong>15 sektöre<br>hazır çözüm.</strong><small>Panel · Domain · Hosting</small></div><div class="showcase-window"><div class="window-bar"><i></i><i></i><i></i></div><div class="showcase-content"><span>DİJİROTA</span><strong>Markanız için<br>güçlü bir vitrin.</strong><div class="showcase-lines"><b></b><b></b><b></b></div></div></div><div class="showcase-orbit"></div></div></div></section>
        <section class="section section-white"><div class="container"><div class="section-heading"><div><div class="eyebrow">ÖNE ÇIKANLAR</div><h2>İşletmenize uygun kurumsal sayfayı bulun.</h2></div><a class="text-link" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Tümünü gör →</a></div><div class="product-grid featured-grid"><?php foreach (array_slice($products, 0, 6) as $product) { product_card($product); } ?></div></div></section>
        <section class="section section-dark" id="nasil-calisir"><div class="container"><div class="section-heading"><div><div class="eyebrow light">SÜREÇ</div><h2>Fikrinizi yayına almanın kolay yolu.</h2></div><p class="section-intro">İhtiyacınız olan kurumsal sayfayı seçin, gerisini DİJİROTA sizin için tamamlasın.</p></div><div class="steps"><article class="step"><div class="step-header"><span>01</span><div class="step-icon">⌁</div></div><h3>Sayfanızı seçin</h3><p>15 farklı sektörel kurumsal sayfa arasından işletmenize en uygun tasarımı inceleyin.</p><a class="step-link" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Kataloğu incele <span>↗</span></a></article><article class="step"><div class="step-header"><span>02</span><div class="step-icon">＋</div></div><h3>Sipariş verin</h3><p>Sepetinizi oluşturun, hesabınızı açın ve güvenli ödeme adımını tamamlayın.</p><a class="step-link" href="<?= e(APP_URL) ?>/sepet">Sepete git <span>↗</span></a></article><article class="step"><div class="step-header"><span>03</span><div class="step-icon">✓</div></div><h3>Biz kuralım</h3><p>Domain, hosting ve kurulum süreçlerini DİJİROTA yöneticisi sizin için tamamlasın.</p><a class="step-link" href="<?= e(APP_URL) ?>/#iletisim">Kurulum detayları <span>↗</span></a></article></div></div></section>
        <section class="section section-accent"><div class="container cta-box"><div><div class="eyebrow">HAZIR MISINIZ?</div><h2>Markanız için doğru kurumsal sayfayı bugün seçin.</h2></div><a class="button button-dark" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Sayfaları incele ↗</a></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'kurumsal-sayfalar') {
        $category = trim((string) ($_GET['kategori'] ?? ''));
        if ($category === '') {
            $statement = db()->query('SELECT * FROM products WHERE is_active = 1 ORDER BY is_featured DESC, id ASC');
        } else {
            $statement = db()->prepare('SELECT * FROM products WHERE is_active = 1 AND category = ? ORDER BY is_featured DESC, id ASC');
            $statement->execute([$category]);
        }
        $products = $statement->fetchAll();
        $categories = db()->query('SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);
        begin_page('Kurumsal Sayfalar | Dijirota', 'Dijirota’nın 15 sektöre özel, yönetim panelli kurumsal sayfa paketlerini inceleyin.');
        ?>
        <section class="page-hero"><div class="container"><div class="eyebrow light">DİJİROTA KATALOĞU</div><h1>İşletmenize uygun kurumsal sayfayı keşfedin.</h1><p>Her paket 15.000 TL KDV dahil fiyatla domain, hosting, kurulum ve yönetim paneli içerir.</p></div></section>
        <section class="section section-white"><div class="container"><div class="catalog-toolbar"><div class="filters"><a class="filter <?= $category === '' ? 'active' : '' ?>" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Tümü</a><?php foreach ($categories as $item): ?><a class="filter <?= $category === $item ? 'active' : '' ?>" href="<?= e(APP_URL) ?>/kurumsal-sayfalar?kategori=<?= urlencode($item) ?>"><?= e($item) ?></a><?php endforeach; ?></div><span class="catalog-count"><?= count($products) ?> hazır kurumsal sayfa</span></div><div class="product-grid catalog-grid"><?php foreach ($products as $product) { product_card($product); } ?></div></div></section>
        <?php end_page();
        exit;
    }

    if ($segments[0] === 'kurumsal-sayfa' && !empty($segments[1])) {
        $product = product_by_slug($segments[1]);
        if (!$product) { http_response_code(404); throw new RuntimeException('Kurumsal sayfa bulunamadı.'); }
        $features = json_decode((string) $product['features_json'], true) ?: [];
        $structured = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product['name'], 'description' => $product['description'], 'brand' => ['@type' => 'Brand', 'name' => 'Dijirota'], 'offers' => ['@type' => 'Offer', 'priceCurrency' => 'TRY', 'price' => number_format(((int) $product['price_kurus']) / 100, 2, '.', ''), 'availability' => 'https://schema.org/InStock', 'url' => APP_URL . '/kurumsal-sayfa/' . $product['slug']]];
        begin_page($product['name'] . ' | Dijirota', $product['description'], false, $structured);
        ?>
        <section class="detail-hero"><div class="container detail-grid"><div><a class="back-link" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">← Kataloğa dön</a><div class="eyebrow light"><?= e($product['category']) ?></div><h1><?= e($product['name']) ?></h1><p><?= e($product['description']) ?></p><div class="detail-price"><?= money((int) $product['price_kurus']) ?><small>KDV dahil · Domain ve hosting dahil</small></div><div class="hero-actions"><form method="post" action="<?= e(APP_URL) ?>/kurumsal-sayfa/<?= e($product['slug']) ?>"><input type="hidden" name="action" value="cart_add"><?= csrf_field() ?><input type="hidden" name="slug" value="<?= e($product['slug']) ?>"><button class="button button-primary" type="submit">Sepete ekle <span>＋</span></button></form><?php if (!empty($product['demo_url'])): ?><a class="button button-ghost" href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener">Canlı demoyu incele ↗</a><?php endif; ?></div></div><div class="detail-preview"><img class="preview-image" src="<?= e(preview_image($product['slug'])) ?>" alt="<?= e($product['name']) ?> tanıtım görseli" loading="eager"><div class="preview-shade"></div><div class="preview-caption"><span class="preview-title"><?= e(preview_title($product['name'])) ?></span><small><?= e($product['theme'] ?? '') ?></small></div></div></div></section>
        <section class="section section-white"><div class="container two-column"><div><div class="eyebrow">PAKET İÇERİĞİ</div><h2>İşletmeniz için hazır bir dijital altyapı.</h2><p class="muted">DİJİROTA yöneticisi satın alma sonrasında domain, hosting ve kurulum sürecini sizinle birlikte tamamlar.</p></div><div class="feature-list"><?php foreach ($features as $feature): ?><div><span>✓</span><?= e($feature) ?></div><?php endforeach; ?><div><span>✓</span>Müşteriye özel yönetim paneli</div><div><span>✓</span>Mobil uyumlu tasarım ve temel SEO</div></div></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'sepet') {
        $items = cart_products();
        $total = array_sum(array_map(static fn (array $item): int => (int) $item['price_kurus'], $items));
        begin_page('Sepet | Dijirota', 'Seçtiğiniz kurumsal sayfaları kontrol edin ve sipariş adımına geçin.', true);
        ?>
        <section class="page-hero compact"><div class="container"><div class="eyebrow light">SİPARİŞ</div><h1>Sepetiniz</h1><p>Seçtiğiniz kurumsal sayfaları sipariş öncesi kontrol edin.</p></div></section>
        <section class="section section-white"><div class="container checkout-grid"><div><?php if (!$items): ?><div class="empty-state"><span class="empty-icon">＋</span><h2>Sepetiniz henüz boş.</h2><p>İşletmenize uygun kurumsal sayfayı seçerek başlayabilirsiniz.</p><a class="button button-dark" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Kataloğu incele</a></div><?php else: foreach ($items as $item): ?><div class="cart-item"><div class="mini-visual visual-<?= e($item['slug']) ?>"><?= e(mb_strtoupper(mb_substr($item['name'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div><div><h3><?= e($item['name']) ?></h3><p><?= e($item['category']) ?> · Domain, hosting ve kurulum dahil</p></div><strong><?= money((int) $item['price_kurus']) ?></strong><form method="post" action="<?= e(APP_URL) ?>/sepet"><input type="hidden" name="action" value="cart_remove"><?= csrf_field() ?><input type="hidden" name="product_id" value="<?= (int) $item['id'] ?>"><button class="icon-button" aria-label="Ürünü sil">×</button></form></div><?php endforeach; endif; ?></div><?php if ($items): ?><aside class="summary-card"><div class="eyebrow">SİPARİŞ ÖZETİ</div><div class="summary-line"><span>Ürünler</span><strong><?= count($items) ?></strong></div><div class="summary-line"><span>Toplam</span><strong><?= money($total) ?></strong></div><p class="summary-note">Fiyata KDV, domain, hosting ve temel kurulum dahildir.</p><a class="button button-primary full" href="<?= e(APP_URL) ?>/odeme">Siparişe devam et →</a></aside><?php endif; ?></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'giris' || $path === 'kayit') {
        $isRegister = $path === 'kayit';
        begin_page(($isRegister ? 'Kayıt Ol' : 'Giriş Yap') . ' | Dijirota', 'Dijirota müşteri hesabınıza erişin.', true);
        ?>
        <section class="auth-section"><div class="auth-card"><div class="eyebrow">DİJİROTA HESABI</div><h1><?= $isRegister ? 'Hesabınızı oluşturun.' : 'Tekrar hoş geldiniz.' ?></h1><p class="muted"><?= $isRegister ? 'Siparişlerinizi ve kurumsal sayfalarınızı tek yerden yönetin.' : 'Siparişlerinize ve yönetim panellerinize ulaşın.' ?></p><form method="post" class="form-stack"><input type="hidden" name="action" value="<?= $isRegister ? 'register' : 'login' ?>"><?= csrf_field() ?><?php if ($isRegister): ?><label>Ad soyad<input type="text" name="name" required autocomplete="name"></label><?php endif; ?><label>E-posta<input type="email" name="email" required autocomplete="email"></label><label>Şifre<input type="password" name="password" required minlength="8" autocomplete="<?= $isRegister ? 'new-password' : 'current-password' ?>"></label><button class="button button-primary full" type="submit"><?= $isRegister ? 'Hesap oluştur' : 'Giriş yap' ?></button></form><p class="form-foot"><?= $isRegister ? 'Zaten hesabınız var mı?' : 'Hesabınız yok mu?' ?> <a href="<?= e(APP_URL) ?>/<?= $isRegister ? 'giris' : 'kayit' ?>"><?= $isRegister ? 'Giriş yapın' : 'Kayıt olun' ?></a></p></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'odeme') {
        require_login();
        $number = trim((string) ($_GET['order'] ?? ''));
        $order = $number !== '' ? get_order_by_number($number) : null;
        if ($order && !user_owns_order($order)) { http_response_code(403); exit('Yetkisiz erişim'); }
        $orderItems = [];
        if ($order) {
            $itemsStatement = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
            $itemsStatement->execute([(int) $order['id']]);
            $orderItems = $itemsStatement->fetchAll();
        }
        begin_page('Ödeme | Dijirota', 'Dijirota siparişinizi güvenle tamamlayın.', true);
        ?>
        <section class="page-hero compact"><div class="container"><div class="eyebrow light">GÜVENLİ SİPARİŞ</div><h1><?= $order ? 'Siparişiniz hazır.' : 'Sipariş bilgileri' ?></h1><p><?= $order ? 'Ödeme adımını tamamlayarak kurulum sürecini başlatabilirsiniz.' : 'İletişim bilgilerinizi bırakın; sipariş kaydınızı oluşturalım.' ?></p></div></section>
        <section class="section section-white"><div class="container checkout-grid"><div><?php if (!$order): ?><form method="post" class="form-card"><input type="hidden" name="action" value="create_order"><?= csrf_field() ?><label>Telefon<input type="tel" name="phone" placeholder="05xx xxx xx xx" required></label><label>Kurulum notu<textarea name="note" rows="5" placeholder="Domain tercihiniz veya işletmenizle ilgili notlar..."></textarea></label><button class="button button-primary" type="submit">Siparişi oluştur →</button></form><?php elseif (in_array($order['status'], ['paid', 'provisioning', 'active'], true)): ?><div class="payment-card"><div class="status-icon">✓</div><div class="eyebrow"><?= e($order['order_number']) ?></div><h2>Bu sipariş için ödeme alındı.</h2><p class="muted">Kurulum durumunuzu müşteri panelinden takip edebilirsiniz.</p><a class="button button-dark" href="<?= e(APP_URL) ?>/panel">Müşteri paneline git</a></div><?php else: ?><div class="payment-card"><div class="status-icon">✓</div><div class="eyebrow"><?= e($order['order_number']) ?></div><h2>Ödeme adımına geçebilirsiniz.</h2><p class="muted">Güvenli ödeme formu PayTR tarafından açılır.</p><?php if (paytr_is_configured()): $payment = paytr_token_for_order($order, $orderItems); if ($payment['ok']): ?><div class="paytr-frame"><iframe src="https://www.paytr.com/odeme/guvenli/<?= e($payment['token']) ?>" id="paytriframe" frameborder="0" scrolling="no" style="width:100%;min-height:650px"></iframe><script src="https://www.paytr.com/js/iframeResizer.min.js?v2"></script><script>iFrameResize({}, '#paytriframe');</script></div><?php else: ?><div class="alert alert-error"><?= e($payment['message']) ?></div><?php endif; else: ?><div class="integration-note"><strong>PayTR entegrasyonu bekliyor</strong><span>Merchant bilgileri ortam değişkenlerine eklendiğinde ödeme ekranı aktifleşir.</span></div><?php endif; ?></div><?php endif; ?></div><aside class="summary-card"><div class="eyebrow">SİPARİŞ ÖZETİ</div><?php if ($order): ?><div class="summary-line"><span>Sipariş</span><strong><?= e($order['order_number']) ?></strong></div><div class="summary-line"><span>Toplam</span><strong><?= money((int) $order['total_kurus']) ?></strong></div><?php else: foreach (cart_products() as $item): ?><div class="summary-line"><span><?= e($item['name']) ?></span><strong><?= money((int) $item['price_kurus']) ?></strong></div><?php endforeach; ?><hr><div class="summary-line total"><span>Toplam</span><strong><?= money(array_sum(array_map(static fn (array $item): int => (int) $item['price_kurus'], cart_products()))) ?></strong></div><?php endif; ?></aside></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'odeme/basarili' || $path === 'odeme/basarisiz') {
        require_login();
        $order = get_order_by_number((string) ($_GET['order'] ?? ''));
        if (!$order || !user_owns_order($order)) { redirect('panel'); }
        begin_page(($path === 'odeme/basarili' ? 'Ödeme Alındı' : 'Ödeme Sonucu') . ' | Dijirota', 'Dijirota ödeme sonucu.', true);
        ?>
        <section class="auth-section"><div class="auth-card center"><div class="status-icon <?= $path === 'odeme/basarili' ? '' : 'error' ?>"><?= $path === 'odeme/basarili' ? '✓' : '!' ?></div><div class="eyebrow">SİPARİŞ <?= e($order['order_number']) ?></div><h1><?= $path === 'odeme/basarili' ? 'Teşekkürler, siparişiniz alındı.' : 'Ödeme tamamlanamadı.' ?></h1><p class="muted"><?= $path === 'odeme/basarili' ? 'Kurulum sürecini müşteri panelinizden takip edebilirsiniz.' : 'Ödeme işlemini tekrar deneyebilir veya bizimle iletişime geçebilirsiniz.' ?></p><a class="button button-dark" href="<?= e(APP_URL) ?>/panel">Müşteri paneline git</a></div></section>
        <?php end_page();
        exit;
    }

    if ($path === 'panel' || $path === 'panel/siteler' || $path === 'siparislerim') {
        require_login();
        $user = current_user();
        $sitesStatement = db()->prepare('SELECT * FROM customer_sites WHERE user_id = ? ORDER BY id DESC');
        $sitesStatement->execute([(int) $user['id']]);
        $sites = $sitesStatement->fetchAll();
        $ordersStatement = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC');
        $ordersStatement->execute([(int) $user['id']]);
        $orders = $ordersStatement->fetchAll();
        begin_page('Müşteri Paneli | Dijirota', 'Satın aldığınız kurumsal sayfaları ve siparişlerinizi yönetin.', true);
        ?>
        <section class="page-hero compact"><div class="container"><div class="eyebrow light">MÜŞTERİ PANELİ</div><h1>Merhaba, <?= e($user['name']) ?>.</h1><p>Kurumsal sayfalarınızın kurulum ve yayın durumunu buradan takip edin.</p></div></section>
        <section class="section section-white"><div class="container dashboard"><aside class="dashboard-nav"><a class="active" href="<?= e(APP_URL) ?>/panel">Genel bakış</a><a href="<?= e(APP_URL) ?>/panel/siteler">Kurumsal sayfalarım</a><a href="<?= e(APP_URL) ?>/siparislerim">Siparişlerim</a><a href="mailto:info@dijirota.com">Destek al</a></aside><div class="dashboard-content"><div class="dashboard-stats"><div><span>Kurumsal sayfalarım</span><strong><?= count($sites) ?></strong></div><div><span>Siparişlerim</span><strong><?= count($orders) ?></strong></div><div><span>Hesap durumu</span><strong>Aktif</strong></div></div><div class="section-heading small"><div><div class="eyebrow">SAYFALARIM</div><h2>Kurulum durumunuz</h2></div></div><?php if (!$sites): ?><div class="empty-state compact"><h3>Henüz aktif bir kurumsal sayfanız yok.</h3><a class="button button-primary" href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Sayfaları incele</a></div><?php else: foreach ($sites as $site): ?><div class="site-row"><div class="mini-visual visual-default">D</div><div><h3><?= e($site['site_name']) ?></h3><p><?= e($site['domain'] ?: 'Domain kurulumu bekliyor') ?></p></div><span class="badge status-<?= e($site['status']) ?>"><?= e(site_status_label($site['status'])) ?></span><a class="text-link" href="<?= e($site['admin_url'] ?: 'mailto:info@dijirota.com?subject=' . rawurlencode($site['site_name'] . ' kurulum')) ?>" target="_blank">Yönetim paneli →</a></div><?php endforeach; endif; ?><div class="section-heading small"><div><div class="eyebrow">SİPARİŞLER</div><h2>Son siparişleriniz</h2></div></div><?php if (!$orders): ?><p class="muted">Henüz siparişiniz bulunmuyor.</p><?php else: foreach ($orders as $order): ?><div class="site-row"><div><h3><?= e($order['order_number']) ?></h3><p><?= e(date('d.m.Y H:i', strtotime($order['created_at']))) ?></p></div><strong><?= money((int) $order['total_kurus']) ?></strong><span class="badge"><?= e(order_status_label($order['status'])) ?></span></div><?php endforeach; endif; ?></div></div></section>
        <?php end_page();
        exit;
    }

    if ($segments[0] === 'admin') {
        if (!current_user() || !in_array(current_user()['role'], ['admin', 'support'], true)) {
            begin_page('DİJİROTA Yönetim Girişi', 'Dijirota yönetim paneli.', true);
            ?>
            <section class="auth-section"><div class="auth-card"><div class="eyebrow">DİJİROTA YÖNETİMİ</div><h1>Yönetim paneline giriş.</h1><p class="muted">Ürün, sipariş ve kurulum süreçlerini yönetin.</p><form method="post" class="form-stack"><input type="hidden" name="action" value="admin_login"><?= csrf_field() ?><label>E-posta<input type="email" name="email" required></label><label>Şifre<input type="password" name="password" required></label><button class="button button-primary full">Giriş yap</button></form></div></section>
            <?php end_page();
            exit;
        }
        require_admin();
        $section = $segments[1] ?? '';
        if ($section === 'siparisler') {
            $orders = db()->query('SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON u.id = o.user_id ORDER BY o.id DESC')->fetchAll();
            begin_page('Siparişler | DİJİROTA Yönetim', 'Dijirota sipariş yönetimi.', true);
            ?>
            <section class="page-hero compact"><div class="container"><div class="eyebrow light">YÖNETİM PANELİ</div><h1>Siparişler</h1><p>Ödemeleri ve kurulum sürecini yönetin.</p></div></section><section class="section section-white"><div class="container admin-table-wrap"><table class="admin-table"><thead><tr><th>Sipariş</th><th>Müşteri</th><th>Tutar</th><th>Tarih</th><th>Durum</th><th>Güncelle</th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><strong><?= e($order['order_number']) ?></strong></td><td><?= e($order['user_name']) ?><small><?= e($order['customer_email']) ?></small></td><td><?= money((int) $order['total_kurus']) ?></td><td><?= e(date('d.m.Y H:i', strtotime($order['created_at']))) ?></td><td><span class="badge"><?= e(order_status_label($order['status'])) ?></span></td><td><form method="post" class="inline-form"><input type="hidden" name="action" value="admin_order_status"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><select name="status"><?php foreach (['awaiting_payment','paid','provisioning','active','failed','cancelled','refunded'] as $status): ?><option value="<?= $status ?>" <?= $status === $order['status'] ? 'selected' : '' ?>><?= e(order_status_label($status)) ?></option><?php endforeach; ?></select><button class="small-button">Kaydet</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
            <?php end_page();
            exit;
        }
        if ($section === 'siteler') {
            $sites = db()->query('SELECT s.*, u.name AS user_name FROM customer_sites s JOIN users u ON u.id = s.user_id ORDER BY s.id DESC')->fetchAll();
            begin_page('Kurulumlar | DİJİROTA Yönetim', 'Dijirota müşteri kurulumları.', true);
            ?>
            <section class="page-hero compact"><div class="container"><div class="eyebrow light">YÖNETİM PANELİ</div><h1>Kurulumlar</h1><p>Müşterilere atanmış kurumsal sayfaları yayına hazırlayın.</p></div></section><section class="section section-white"><div class="container admin-cards"><?php foreach ($sites as $site): ?><form method="post" class="site-admin-card"><input type="hidden" name="action" value="admin_site_update"><?= csrf_field() ?><input type="hidden" name="site_id" value="<?= (int) $site['id'] ?>"><div class="eyebrow"><?= e($site['user_name']) ?></div><h3><?= e($site['site_name']) ?></h3><label>Durum<select name="status"><?php foreach (['pending','provisioning','waiting_customer','active','suspended'] as $status): ?><option value="<?= $status ?>" <?= $status === $site['status'] ? 'selected' : '' ?>><?= e(site_status_label($status)) ?></option><?php endforeach; ?></select></label><label>Domain<input name="domain" value="<?= e($site['domain']) ?>" placeholder="www.musteri.com"></label><label>Yönetim paneli URL<input name="admin_url" value="<?= e($site['admin_url']) ?>" placeholder="https://musteri.com/admin"></label><label>Not<textarea name="notes" rows="3"><?= e($site['notes']) ?></textarea></label><button class="button button-primary full">Kurulumu güncelle</button></form><?php endforeach; ?></div></section>
            <?php end_page();
            exit;
        }
        if ($section === 'urunler') {
            $products = db()->query('SELECT * FROM products ORDER BY id ASC')->fetchAll();
            begin_page('Ürünler | DİJİROTA Yönetim', 'Dijirota kurumsal sayfa ürünleri.', true);
            ?>
            <section class="page-hero compact"><div class="container"><div class="eyebrow light">YÖNETİM PANELİ</div><h1>Kurumsal sayfalar</h1><p>Katalogdaki ürünleri ve demo bağlantılarını kontrol edin.</p></div></section><section class="section section-white"><div class="container admin-table-wrap"><table class="admin-table"><thead><tr><th>Ürün</th><th>Kategori</th><th>Fiyat</th><th>Demo</th><th>Durum</th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><strong><?= e($product['name']) ?></strong><small><?= e($product['slug']) ?></small></td><td><?= e($product['category']) ?></td><td><?= money((int) $product['price_kurus']) ?></td><td><?php if ($product['demo_url']): ?><a class="text-link" href="<?= e($product['demo_url']) ?>" target="_blank" rel="noopener">Demoyu aç ↗</a><?php else: ?>—<?php endif; ?></td><td><span class="badge"><?= $product['is_active'] ? 'Yayında' : 'Pasif' ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
            <?php end_page();
            exit;
        }
        $orderCount = (int) db()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $userCount = (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
        $pendingCount = (int) db()->query("SELECT COUNT(*) FROM customer_sites WHERE status <> 'active'")->fetchColumn();
        begin_page('Yönetim Paneli | DİJİROTA', 'Dijirota merkezi yönetim paneli.', true);
        ?>
        <section class="page-hero compact"><div class="container"><div class="eyebrow light">MERKEZİ YÖNETİM</div><h1>DİJİROTA kontrol merkezi.</h1><p>Ürünleri, müşterileri, siparişleri ve kurulumları tek yerden yönetin.</p></div></section><section class="section section-white"><div class="container dashboard"><aside class="dashboard-nav"><a class="active" href="<?= e(APP_URL) ?>/admin">Genel bakış</a><a href="<?= e(APP_URL) ?>/admin/urunler">Ürünler</a><a href="<?= e(APP_URL) ?>/admin/siparisler">Siparişler</a><a href="<?= e(APP_URL) ?>/admin/siteler">Kurulumlar</a><a href="<?= e(APP_URL) ?>/kurumsal-sayfalar">Mağazayı görüntüle</a></aside><div class="dashboard-content"><div class="dashboard-stats"><div><span>Toplam sipariş</span><strong><?= $orderCount ?></strong></div><div><span>Müşteri hesabı</span><strong><?= $userCount ?></strong></div><div><span>Bekleyen kurulum</span><strong><?= $pendingCount ?></strong></div></div><div class="admin-quick"><a href="<?= e(APP_URL) ?>/admin/urunler"><span>01</span><h3>Ürünleri yönet</h3><p>15 kurumsal sayfanın katalog bilgilerini kontrol edin.</p></a><a href="<?= e(APP_URL) ?>/admin/siparisler"><span>02</span><h3>Siparişleri yönet</h3><p>Ödeme durumlarını ve sipariş akışını takip edin.</p></a><a href="<?= e(APP_URL) ?>/admin/siteler"><span>03</span><h3>Kurulumları yönet</h3><p>Domain, hosting ve yönetim paneli bilgilerini tanımlayın.</p></a></div></div></div></section>
        <?php end_page();
        exit;
    }

    http_response_code(404);
    begin_page('Sayfa bulunamadı | Dijirota', 'Aradığınız sayfa bulunamadı.', true);
    ?>
    <section class="auth-section"><div class="auth-card center"><div class="eyebrow">404</div><h1>Bu sayfa bulunamadı.</h1><p class="muted">Aradığınız içerik kaldırılmış veya adresi değişmiş olabilir.</p><a class="button button-dark" href="<?= e(APP_URL) ?>/">Ana sayfaya dön</a></div></section>
    <?php end_page();
} catch (Throwable $exception) {
    http_response_code(500);
    begin_page('Dijirota | Sistem hazırlanıyor', 'Dijirota altyapısı hazırlanıyor.', true);
    ?>
    <section class="auth-section"><div class="auth-card center"><div class="status-icon error">!</div><h1>Altyapı bağlantısı hazır değil.</h1><p class="muted">Veritabanı konteynerini başlatıp sayfayı yenileyin. Yerel kurulum için docker compose up -d --build komutunu kullanabilirsiniz.</p></div></section>
    <?php end_page();
}
