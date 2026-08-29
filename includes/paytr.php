<?php
declare(strict_types=1);

function paytr_is_configured(): bool
{
    return PAYTR_MERCHANT_ID !== '' && PAYTR_MERCHANT_KEY !== '' && PAYTR_MERCHANT_SALT !== '';
}

function paytr_token_for_order(array $order, array $items): array
{
    if (!paytr_is_configured()) {
        return ['ok' => false, 'message' => 'PayTR mağaza bilgileri henüz yapılandırılmadı.'];
    }

    $userIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $basket = [];
    foreach ($items as $item) {
        $basket[] = [$item['product_name'], number_format($item['price_kurus'] / 100, 2, '.', ''), (int) $item['quantity']];
    }

    // PayTR merchant_oid değeri benzersiz ve alfanümerik tutulur.
    $merchantOid = 'DJ' . preg_replace('/[^A-Za-z0-9]/', '', (string) $order['order_number']);
    $paymentAmount = (string) $order['total_kurus'];
    $userBasket = base64_encode(json_encode($basket, JSON_UNESCAPED_UNICODE));
    $noInstallment = '0';
    $maxInstallment = '0';
    $currency = 'TL';
    $testMode = PAYTR_TEST_MODE;
    $hashString = PAYTR_MERCHANT_ID . $userIp . $merchantOid . $order['customer_email'] . $paymentAmount . $userBasket . $noInstallment . $maxInstallment . $currency . $testMode;
    $hash = base64_encode(hash_hmac('sha256', $hashString . PAYTR_MERCHANT_SALT, PAYTR_MERCHANT_KEY, true));

    $paymentInsert = db()->prepare("INSERT INTO payments (order_id, merchant_oid, status, amount_kurus) VALUES (?, ?, 'pending', ?) ON DUPLICATE KEY UPDATE amount_kurus = VALUES(amount_kurus)");
    $paymentInsert->execute([(int) $order['id'], $merchantOid, (int) $paymentAmount]);

    $payload = [
        'merchant_id' => PAYTR_MERCHANT_ID,
        'user_ip' => $userIp,
        'merchant_oid' => $merchantOid,
        'email' => $order['customer_email'],
        'payment_amount' => $paymentAmount,
        'user_basket' => $userBasket,
        'debug_on' => '1',
        'no_installment' => $noInstallment,
        'max_installment' => $maxInstallment,
        'user_name' => $order['customer_name'],
        'user_address' => 'Dijirota dijital kurumsal sayfa kurulumu',
        'user_phone' => $order['customer_phone'] ?: '05000000000',
        'merchant_ok_url' => APP_URL . '/odeme/basarili?order=' . urlencode($order['order_number']),
        'merchant_fail_url' => APP_URL . '/odeme/basarisiz?order=' . urlencode($order['order_number']),
        'merchant_notify_url' => APP_URL . '/paytr/callback.php',
        'timeout_limit' => '30',
        'currency' => $currency,
        'test_mode' => $testMode,
        'lang' => 'tr',
        'iframe_v2' => '1',
        'iframe_v2_dark' => '0',
        'paytr_token' => $hash,
    ];

    $curl = curl_init('https://www.paytr.com/odeme/api/get-token');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($payload),
        CURLOPT_TIMEOUT => 20,
    ]);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);

    if ($response === false || $error !== '') {
        return ['ok' => false, 'message' => 'PayTR bağlantısı kurulamadı.'];
    }

    $result = json_decode((string) $response, true);
    if (!is_array($result)) {
        parse_str((string) $response, $result);
    }
    if (($result['status'] ?? '') !== 'success' || empty($result['token'])) {
        return ['ok' => false, 'message' => $result['reason'] ?? 'PayTR ödeme oturumu oluşturulamadı.'];
    }

    return ['ok' => true, 'token' => $result['token'], 'merchant_oid' => $merchantOid];
}
