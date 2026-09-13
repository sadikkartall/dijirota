<?php
declare(strict_types=1);

function env_value(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_URL', rtrim(env_value('APP_URL', 'http://localhost:8100'), '/'));
define('ORDER_NOTIFICATION_EMAIL', env_value('ORDER_NOTIFICATION_EMAIL', 'sdkkrtll@gmail.com'));
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
define('OPENAI_API_KEY', env_value('OPENAI_API_KEY'));
define('OPENAI_BLOG_MODEL', env_value('OPENAI_BLOG_MODEL', 'gpt-5.6-terra'));
define('OPENAI_BLOG_WEB_SEARCH', env_value('OPENAI_BLOG_WEB_SEARCH', '1'));
define('OPENAI_BLOG_VECTOR_STORE_ID', env_value('OPENAI_BLOG_VECTOR_STORE_ID'));
define('OPENAI_BLOG_MAX_OUTPUT_TOKENS', (int) env_value('OPENAI_BLOG_MAX_OUTPUT_TOKENS', '12000'));
define('BLOG_AI_PROVIDER', strtolower(trim(env_value('BLOG_AI_PROVIDER', 'nvidia'))));
define('NVIDIA_API_KEY', env_value('NVIDIA_API_KEY'));
define('NVIDIA_API_BASE_URL', rtrim(env_value('NVIDIA_API_BASE_URL', 'https://integrate.api.nvidia.com/v1'), '/'));
define('NVIDIA_BLOG_MODEL', env_value('NVIDIA_BLOG_MODEL', 'nvidia/nemotron-3.5-lightning-30b-a3b'));
define('NVIDIA_BLOG_MAX_OUTPUT_TOKENS', (int) env_value('NVIDIA_BLOG_MAX_OUTPUT_TOKENS', '5000'));
define('NVIDIA_IMAGE_API_URL', rtrim(env_value('NVIDIA_IMAGE_API_URL', 'https://ai.api.nvidia.com/v1/genai/stabilityai/stable-diffusion-3-medium'), '/'));
define('NVIDIA_IMAGE_MODEL', env_value('NVIDIA_IMAGE_MODEL', 'sd3'));

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
