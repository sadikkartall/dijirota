<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$merchantOid = (string) ($_POST['merchant_oid'] ?? '');
$status = (string) ($_POST['status'] ?? '');
$totalAmount = (string) ($_POST['total_amount'] ?? '');
$receivedHash = (string) ($_POST['hash'] ?? '');

if ($merchantOid === '' || $receivedHash === '') {
    http_response_code(400);
    exit('INVALID');
}

$expectedHash = base64_encode(hash_hmac('sha256', $merchantOid . PAYTR_MERCHANT_SALT . $status . $totalAmount, PAYTR_MERCHANT_KEY, true));
if (!hash_equals($expectedHash, $receivedHash)) {
    http_response_code(400);
    exit('INVALID');
}

$paymentLookup = db()->prepare('SELECT * FROM payments WHERE merchant_oid = ? LIMIT 1');
$paymentLookup->execute([$merchantOid]);
$payment = $paymentLookup->fetch();

if (!$payment) {
    http_response_code(404);
    exit('NOT_FOUND');
}

$orderStatement = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$orderStatement->execute([(int) $payment['order_id']]);
$order = $orderStatement->fetch();

if (!$order) {
    http_response_code(404);
    exit('NOT_FOUND');
}

$receivedAmount = (int) $totalAmount;
if ($receivedAmount < (int) $order['total_kurus']) {
    http_response_code(400);
    exit('INVALID_AMOUNT');
}

$newStatus = $status === 'success' ? 'paid' : 'failed';
$update = db()->prepare('UPDATE payments SET status = ?, amount_kurus = ?, raw_payload = ? WHERE merchant_oid = ?');
$update->execute([$newStatus, (int) $totalAmount, json_encode($_POST, JSON_UNESCAPED_UNICODE), $merchantOid]);

if ($order['status'] === 'awaiting_payment') {
    $updateOrder = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $updateOrder->execute([$newStatus, (int) $order['id']]);

    if ($newStatus === 'paid') {
        $siteItems = db()->prepare('SELECT product_id, product_name FROM order_items WHERE order_id = ?');
        $siteItems->execute([(int) $order['id']]);
        foreach ($siteItems->fetchAll() as $item) {
            $siteExists = db()->prepare('SELECT id FROM customer_sites WHERE order_id = ? AND product_id = ? LIMIT 1');
            $siteExists->execute([(int) $order['id'], (int) $item['product_id']]);
            if ($siteExists->fetch()) {
                continue;
            }
            $siteInsert = db()->prepare('INSERT INTO customer_sites (user_id, order_id, product_id, site_name, status) VALUES (?, ?, ?, ?, \'pending\')');
            $siteInsert->execute([(int) $order['user_id'], (int) $order['id'], (int) $item['product_id'], $item['product_name']]);
        }
    }
}

echo 'OK';
