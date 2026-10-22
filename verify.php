<?php
$pageTitle = 'E-Mail bestätigen';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';

// If already logged in, go home
if (auth_check()) {
    header('Location: /');
    exit;
}

$token   = trim((string) ($_GET['token'] ?? ''));
$status  = 'invalid'; // 'invalid' | 'expired' | 'already' | 'success'
$username = '';

if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    cg_log(LOG_VERIFY_FAILED, ['reason' => 'invalid_token_format', 'token_len' => strlen($token)]);
}

if ($token !== '' && strlen($token) === 64 && ctype_xdigit($token)) {
    $pdo = db();

    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare(
                'SELECT `id`, `username`, `email_verified`, `created_at`
                 FROM `users`
                 WHERE `email_verify_token` IN (?, ?)
                 LIMIT 1'
            );
            // Tokens werden als SHA-256 gespeichert; Altbestand (Klartext) wird weiterhin akzeptiert
            $stmt->execute([hash('sha256', $token), $token]);
            $row = $stmt->fetch();

            if ($row !== false) {
                if ((bool) $row['email_verified']) {
                    $status   = 'already';
                    $username = $row['username'];
                    cg_log(LOG_VERIFY_ALREADY, ['username' => $username], (int) $row['id']);
                } else {
                    // Token valid for 24 hours
                    $createdAt = new DateTimeImmutable($row['created_at']);
                    $expires   = $createdAt->modify('+24 hours');
                    $now       = new DateTimeImmutable('now');

                    if ($now > $expires) {
                        $status = 'expired';
                        cg_log(LOG_VERIFY_EXPIRED, ['username' => $row['username']], (int) $row['id']);
                    } else {
                        // Activate the account
                        $upd = $pdo->prepare(
                            'UPDATE `users`
                             SET `email_verified`     = 1,
                                 `is_active`          = 1,
                                 `email_verify_token` = NULL
                             WHERE `id` = ?'
                        );
                        $upd->execute([$row['id']]);
                        $status   = 'success';
                        $username = $row['username'];
                        cg_log(LOG_VERIFY_SUCCESS, ['username' => $username], (int) $row['id']);
                    }
                }
            } else {
                // Token-Format korrekt, aber nicht in der DB gefunden
                cg_log(LOG_VERIFY_FAILED, ['reason' => 'token_not_found', 'token_preview' => substr($token, 0, 8) . '...']);
            }
        } catch (Throwable) {
            $status = 'invalid';
            cg_log(LOG_VERIFY_FAILED, ['token_preview' => substr($token, 0, 8) . '...']);
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">
<section class="cg-login-section d-flex align-items-center justify-content-center">
  <div class="cg-login-box text-center">

    <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-login-logo mb-4" width="64" height="64">

    <?php if ($status === 'success'): ?>

      <div class="cg-verify-icon cg-verify-success">
        <i class="fas fa-circle-check"></i>
      </div>
      <h1 class="cg-login-title mt-3">E-Mail bestätigt!</h1>
      <p class="cg-text-muted" style="margin:.5rem 0 1.5rem;">
        <bdi>Dein Account</bdi> <strong style="color:var(--cg-text);"><?= e($username) ?></strong>
        <bdi>ist jetzt aktiv. Du kannst dich jetzt einloggen.</bdi>
      </p>
      <a href="/login.php" class="cg-login-btn text-decoration-none">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Jetzt einloggen
      </a>

    <?php elseif ($status === 'already'): ?>

      <div class="cg-verify-icon cg-verify-info">
        <i class="fas fa-circle-info"></i>
      </div>
      <h1 class="cg-login-title mt-3">Bereits bestätigt</h1>
      <p class="cg-text-muted" style="margin:.5rem 0 1.5rem;">
        <bdi>Die E-Mail-Adresse für</bdi>
        <strong style="color:var(--cg-text);"><?= e($username) ?></strong>
        <bdi>wurde bereits verifiziert.</bdi>
      </p>
      <a href="/login.php" class="cg-login-btn text-decoration-none">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Zum Login
      </a>

    <?php elseif ($status === 'expired'): ?>

      <div class="cg-verify-icon cg-verify-warn">
        <i class="fas fa-clock-rotate-left"></i>
      </div>
      <h1 class="cg-login-title mt-3">Link abgelaufen</h1>
      <p class="cg-text-muted" style="margin:.5rem 0 1.5rem;">
        Der Bestätigungslink ist abgelaufen (gültig 24 Stunden).
        Bitte registriere dich erneut oder kontaktiere uns.
      </p>
      <a href="/register.php" class="cg-login-btn text-decoration-none">
        <i class="fas fa-user-plus me-2"></i>Erneut registrieren
      </a>

    <?php else: ?>

      <div class="cg-verify-icon cg-verify-error">
        <i class="fas fa-circle-xmark"></i>
      </div>
      <h1 class="cg-login-title mt-3">Ungültiger Link</h1>
      <p class="cg-text-muted" style="margin:.5rem 0 1.5rem;">
        Dieser Bestätigungslink ist ungültig oder wurde bereits verwendet.
      </p>
      <a href="/register.php" class="cg-login-btn text-decoration-none">
        <i class="fas fa-user-plus me-2"></i>Registrieren
      </a>

    <?php endif; ?>

  </div>
</section>
</main>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
