<?php
$pageTitle = 'E-Mail bestätigen';
require_once __DIR__ . '/includes/head.php';

// If already logged in, go home
if (auth_check()) {
    header('Location: /');
    exit;
}

$token   = trim((string) ($_GET['token'] ?? ''));
$status  = 'invalid'; // 'invalid' | 'expired' | 'already' | 'success'
$username = '';

if ($token !== '' && strlen($token) === 64 && ctype_xdigit($token)) {
    $pdo = db();

    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare(
                'SELECT `id`, `username`, `email_verified`, `created_at`
                 FROM `users`
                 WHERE `email_verify_token` = ?
                 LIMIT 1'
            );
            $stmt->execute([$token]);
            $row = $stmt->fetch();

            if ($row !== false) {
                if ((bool) $row['email_verified']) {
                    $status   = 'already';
                    $username = $row['username'];
                } else {
                    // Token valid for 24 hours
                    $createdAt = new DateTimeImmutable($row['created_at']);
                    $expires   = $createdAt->modify('+24 hours');
                    $now       = new DateTimeImmutable('now');

                    if ($now > $expires) {
                        $status = 'expired';
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
                    }
                }
            }
        } catch (Throwable) {
            $status = 'invalid';
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<section class="cg-login-section d-flex align-items-center justify-content-center"
         style="min-height:100vh;padding:6rem 1rem 3rem;">
  <div class="cg-login-box text-center">

    <img src="assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo"
         style="width:64px;height:64px;border-radius:50%;border:2px solid var(--cg-red);margin-bottom:1.5rem;">

    <?php if ($status === 'success'): ?>

      <div class="cg-verify-icon cg-verify-success">
        <i class="fas fa-circle-check"></i>
      </div>
      <h1 class="cg-login-title mt-3">E-Mail bestätigt!</h1>
      <p style="color:#8b8b9a;margin:.5rem 0 1.5rem;">
        Dein Account <strong style="color:#e8e8ed;"><?= htmlspecialchars($username) ?></strong>
        ist jetzt aktiv. Du kannst dich jetzt einloggen.
      </p>
      <a href="/login.php" class="cg-login-btn d-block text-decoration-none">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Jetzt einloggen
      </a>

    <?php elseif ($status === 'already'): ?>

      <div class="cg-verify-icon cg-verify-info">
        <i class="fas fa-circle-info"></i>
      </div>
      <h1 class="cg-login-title mt-3">Bereits bestätigt</h1>
      <p style="color:#8b8b9a;margin:.5rem 0 1.5rem;">
        Die E-Mail-Adresse für
        <strong style="color:#e8e8ed;"><?= htmlspecialchars($username) ?></strong>
        wurde bereits verifiziert.
      </p>
      <a href="/login.php" class="cg-login-btn d-block text-decoration-none">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Zum Login
      </a>

    <?php elseif ($status === 'expired'): ?>

      <div class="cg-verify-icon cg-verify-warn">
        <i class="fas fa-clock-rotate-left"></i>
      </div>
      <h1 class="cg-login-title mt-3">Link abgelaufen</h1>
      <p style="color:#8b8b9a;margin:.5rem 0 1.5rem;">
        Der Bestätigungslink ist abgelaufen (gültig 24 Stunden).
        Bitte registriere dich erneut oder kontaktiere uns.
      </p>
      <a href="/register.php" class="cg-login-btn d-block text-decoration-none">
        <i class="fas fa-user-plus me-2"></i>Erneut registrieren
      </a>

    <?php else: ?>

      <div class="cg-verify-icon cg-verify-error">
        <i class="fas fa-circle-xmark"></i>
      </div>
      <h1 class="cg-login-title mt-3">Ungültiger Link</h1>
      <p style="color:#8b8b9a;margin:.5rem 0 1.5rem;">
        Dieser Bestätigungslink ist ungültig oder wurde bereits verwendet.
      </p>
      <a href="/register.php" class="cg-login-btn d-block text-decoration-none">
        <i class="fas fa-user-plus me-2"></i>Registrieren
      </a>

    <?php endif; ?>

  </div>
</section>

<style>
.cg-login-box {
  background: var(--cg-surface, #161619);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 1rem;
  padding: 2.5rem 2rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 8px 40px rgba(0,0,0,.5);
}
.cg-login-title {
  font-family: 'Tilt Warp', sans-serif;
  font-size: 1.8rem;
  color: #fff;
  margin-bottom: .25rem;
}
.cg-verify-icon {
  font-size: 3rem;
  line-height: 1;
  margin-bottom: .25rem;
}
.cg-verify-success { color: #37b24d; }
.cg-verify-info    { color: #339af0; }
.cg-verify-warn    { color: #f59f00; }
.cg-verify-error   { color: #e03131; }
.cg-login-btn {
  display: block;
  width: 100%;
  padding: .7rem;
  background: var(--cg-red, #e03131);
  color: #fff;
  border: none;
  border-radius: .5rem;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  margin-top: .5rem;
  transition: background .2s;
  text-align: center;
}
.cg-login-btn:hover { background: #c92a2a; color: #fff; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
