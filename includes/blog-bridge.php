<?php
declare(strict_types=1);
// Every private request uses Dijirota's existing PHP administrator session.
$user = current_user();
$suffix = substr($path, strlen('admin/blog')) ?: '/';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (!$user || !in_array($user['role'], ['admin', 'support'], true)) {
    if ($suffix === '/' && $_SERVER['REQUEST_METHOD'] === 'GET') redirect('admin');
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Yönetici oturumu gerekli. /admin üzerinden giriş yap.']);
    exit;
}
$method = $_SERVER['REQUEST_METHOD'];
$origin = rtrim(APP_URL, '/');
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site' ||
    ($method !== 'GET' && ($_SERVER['HTTP_ORIGIN'] ?? '') !== $origin)) {
    http_response_code(403); header('Content-Type: application/json');
    echo json_encode(['error' => 'İstek kaynağı geçersiz.']); exit;
}
if (!in_array($method, ['GET', 'POST', 'PUT'], true)) {http_response_code(405); exit;}
if ($suffix === '/logout' && $method === 'POST') {
    logout_user(); header('Content-Type: application/json'); echo '{"ok":true}'; exit;
}
// Render the editor inside the real Dijirota layout. These UI files are
// served from the PHP bind mount so visual updates do not restart generation.
if ($method === 'GET' && in_array($suffix, ['/', '/admin.css', '/admin.js'], true)) {
    $adminRoot = __DIR__ . '/../blog-engine/admin/';
    if ($suffix !== '/') {
        header('Content-Type: ' . ($suffix === '/admin.css' ? 'text/css' : 'text/javascript') . '; charset=utf-8');
        readfile($adminRoot . basename($suffix)); exit;
    }
    $page_title = 'Blog Atölyesi | DİJİROTA';
    $meta_description = 'DİJİROTA blog içerik yönetimi';
    $noindex = true;
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-src 'self'; object-src 'none'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
    require __DIR__ . '/header.php';
    echo '<link rel="stylesheet" href="/admin/blog/admin.css?v=20260913-light">';
    readfile($adminRoot . 'panel.html');
    echo '<script src="/admin/blog/admin.js?v=20260913-light" defer></script>';
    require __DIR__ . '/footer.php';
    exit;
}
$secret = getenv('BLOG_BRIDGE_SECRET') ?: '';
if (strlen($secret) < 32) {
    http_response_code(503); header('Content-Type: application/json');
    echo json_encode(['error' => 'Blog bağlantısı yapılandırılmamış.']); exit;
}
// Release the session lock so polling and editing are never blocked by another request.
session_write_close();
$input = file_get_contents('php://input', false, null, 0, 150001);
if (strlen((string) $input) > 150000) {http_response_code(413); exit;}
$headers = [
    'Host: localhost:4180', 'Origin: http://localhost:4180',
    'X-Blog-Bridge: ' . $secret, 'Content-Type: application/json',
    'X-Blog-Token: ' . preg_replace('/[^a-f0-9]/', '', $_SERVER['HTTP_X_BLOG_TOKEN'] ?? ''),
    'Connection: close',
];
$context = stream_context_create(['http' => [
    'method' => $method, 'header' => implode("\r\n", $headers),
    'content' => $input, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 30,
]]);
// Only this fixed internal service can receive the request; no user-selected host.
$url = 'http://blog:4180/' . ltrim($suffix, '/');
$response = @file_get_contents($url, false, $context);
if ($response === false) {
    http_response_code(503); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Blog servisine ulaşılamıyor. Docker blog servisinin durumunu kontrol et.']); exit;
}
foreach ($http_response_header ?? [] as $line) {
    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $line, $match)) http_response_code((int) $match[1]);
    elseif (preg_match('/^(Content-Type|Content-Security-Policy|X-Frame-Options|Referrer-Policy):/i', $line)) header($line);
}
echo $response;
