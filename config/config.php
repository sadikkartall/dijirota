<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost:8100'), '/'));
define('DB_HOST', env_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', env_value('DB_PORT', '3306'));
define('DB_NAME', env_value('DB_NAME', 'dijirota'));
define('DB_USER', env_value('DB_USER', 'dijirota_user'));
define('DB_PASSWORD', env_value('DB_PASSWORD', 'local_password'));
define('ADMIN_SEED_EMAIL', env_value('ADMIN_SEED_EMAIL', 'admin@dijirota.com'));
define('ADMIN_SEED_PASSWORD', env_value('ADMIN_SEED_PASSWORD', 'DijirotaAdmin!2026'));
define('PAYTR_MERCHANT_ID', env_value('PAYTR_MERCHANT_ID'));
define('PAYTR_MERCHANT_KEY', env_value('PAYTR_MERCHANT_KEY'));
define('PAYTR_MERCHANT_SALT', env_value('PAYTR_MERCHANT_SALT'));
define('PAYTR_TEST_MODE', env_value('PAYTR_TEST_MODE', '1'));

date_default_timezone_set('Europe/Istanbul');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}
