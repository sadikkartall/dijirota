<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money(int $kurus): string
{
    return number_format($kurus / 100, 0, ',', '.') . ' TL';
}

function preview_image(string $slug): string
{
    $images = [
        'ajans' => 'assets/images/demos/ajans.jpg',
        'avukat' => 'assets/images/demos/avukat.jpg',
        'diyetisyen' => 'assets/images/demos/diyetisyen.jpg',
        'dis' => 'assets/images/demos/dis.png',
        'guzellik' => 'assets/images/demos/guzellik.jpg',
        'kuafor' => 'assets/images/demos/kuafor.jpg',
        'kurumsal' => 'assets/images/demos/kurumsal.jpg',
        'lojistik' => 'assets/images/demos/lojistik.jpg',
        'psikolog' => 'assets/images/demos/psikolog.jpg',
        'sigorta' => 'assets/images/demos/sigorta.jpg',
        'teknik-servis' => 'assets/images/demos/teknik-servis.webp',
        'temizlik' => 'assets/images/demos/temizlik.jpg',
        'veteriner' => 'assets/images/demos/veteriner.jpg',
        'ilaclama' => 'assets/images/demos/ilaclama.jpg',
        'insaat' => 'assets/images/demos/insaat.jpg',
    ];

    return APP_URL . '/' . ($images[$slug] ?? 'assets/images/favicon.svg');
}

function whatsapp_url(string $message = 'Merhaba, Dijirota kurumsal sayfaları hakkında bilgi almak istiyorum.'): string
{
    return 'https://wa.me/905446201621?text=' . rawurlencode($message);
}

function preview_title(string $name): string
{
    $shortName = preg_replace('/\s+Kurumsal Sayfası$/u', '', $name);
    return trim($shortName ?: $name);
}

function redirect(string $path): never
{
    header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Geçersiz güvenlik anahtarı. Lütfen sayfayı yenileyip tekrar deneyin.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

function cart_ids(): array
{
    $ids = $_SESSION['cart'] ?? [];
    return array_values(array_unique(array_map('intval', is_array($ids) ? $ids : [])));
}

function cart_count(): int
{
    return count(cart_ids());
}

function cart_products(): array
{
    $ids = cart_ids();
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = db()->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_active = 1 ORDER BY id DESC");
    $statement->execute($ids);
    return $statement->fetchAll();
}

function product_by_slug(string $slug): ?array
{
    $statement = db()->prepare('SELECT * FROM products WHERE slug = ? AND is_active = 1 LIMIT 1');
    $statement->execute([$slug]);
    $product = $statement->fetch();
    return $product ?: null;
}

function order_status_label(string $status): string
{
    return [
        'awaiting_payment' => 'Ödeme bekliyor',
        'paid' => 'Ödeme alındı',
        'provisioning' => 'Kurulum hazırlanıyor',
        'active' => 'Yayında',
        'failed' => 'Ödeme başarısız',
        'cancelled' => 'İptal edildi',
        'refunded' => 'İade edildi',
    ][$status] ?? $status;
}

function site_status_label(string $status): string
{
    return [
        'pending' => 'Kurulum bekliyor',
        'provisioning' => 'Kurulum hazırlanıyor',
        'waiting_customer' => 'Müşteri bilgileri bekleniyor',
        'active' => 'Aktif',
        'suspended' => 'Askıya alındı',
    ][$status] ?? $status;
}

function render_json_ld(array $data): void
{
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
