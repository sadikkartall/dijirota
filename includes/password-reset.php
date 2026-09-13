<?php
declare(strict_types=1);

const PASSWORD_RESET_TTL_MINUTES = 60;
const PASSWORD_RESET_RATE_LIMIT = 3;

function ensure_password_reset_table(): void
{
    db()->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            requested_ip VARCHAR(45) NULL,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_password_resets_user_created (user_id, created_at),
            INDEX idx_password_resets_ip_created (requested_ip, created_at),
            INDEX idx_password_resets_expires (expires_at)
        ) ENGINE=InnoDB'
    );
}

function request_password_reset(string $email, string $ipAddress): void
{
    ensure_password_reset_table();

    $pdo = db();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer' AND is_active = 1 LIMIT 1");
    $statement->execute([$email]);
    $user = $statement->fetch();

    $ipLimit = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE requested_ip = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $ipLimit->execute([$ipAddress]);
    if (!$user || (int) $ipLimit->fetchColumn() >= PASSWORD_RESET_RATE_LIMIT) {
        return;
    }

    $userLimit = $pdo->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
    $userLimit->execute([(int) $user['id']]);
    if ((int) $userLimit->fetchColumn() >= PASSWORD_RESET_RATE_LIMIT) {
        return;
    }

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (PASSWORD_RESET_TTL_MINUTES * 60));
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
        ->execute([(int) $user['id']]);
    $insert = $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, requested_ip, expires_at) VALUES (?, ?, ?, ?)');
    $insert->execute([(int) $user['id'], $tokenHash, $ipAddress, $expiresAt]);

    $resetUrl = APP_URL . '/sifre-sifirla?token=' . rawurlencode($token);
    $subject = 'DİJİROTA şifre sıfırlama bağlantısı';
    $body = implode("\n", [
        'Merhaba ' . $user['name'] . ',',
        '',
        'DİJİROTA hesabınızın şifresini yenilemek için aşağıdaki bağlantıyı kullanın:',
        $resetUrl,
        '',
        'Bu bağlantı ' . PASSWORD_RESET_TTL_MINUTES . ' dakika geçerlidir ve yalnızca bir kez kullanılabilir.',
        'Bu isteği siz yapmadıysanız e-postayı dikkate almayabilirsiniz.',
    ]);
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: DİJİROTA <' . PASSWORD_RESET_FROM_EMAIL . '>',
        'Reply-To: ' . CONTACT_EMAIL,
    ]);

    $sent = @mail($user['email'], mb_encode_mimeheader($subject, 'UTF-8'), $body, $headers);
    if (!$sent) {
        $pdo->prepare('DELETE FROM password_resets WHERE token_hash = ?')->execute([$tokenHash]);
        error_log('DİJİROTA password reset email could not be sent.');
    }
}

function valid_password_reset(string $token, bool $lock = false): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    if (!$lock) {
        ensure_password_reset_table();
    }
    $sql = "SELECT pr.*, u.email, u.name
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token_hash = ?
          AND pr.used_at IS NULL
          AND pr.expires_at > NOW()
          AND u.is_active = 1
          AND u.role = 'customer'
        LIMIT 1" . ($lock ? ' FOR UPDATE' : '');
    $statement = db()->prepare($sql);
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();
    return $reset ?: null;
}

function complete_password_reset(string $token, string $password): bool
{
    ensure_password_reset_table();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $reset = valid_password_reset($token, true);
        if (!$reset) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([(int) $reset['user_id']]);
        $pdo->commit();
        return true;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}
