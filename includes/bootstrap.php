<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';

try {
    ensure_seed_admin();
} catch (Throwable $exception) {
    // The first request can arrive before the database container is ready.
    // The actual error is shown by the page only when a query is needed.
}
