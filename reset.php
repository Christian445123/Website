<?php
$pageTitle = 'Neues Passwort festlegen';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';
// Der Token steht in der URL: nie als Referrer weitergeben
header('Referrer-Policy: no-referrer');

if (auth_check()) {
    header('Location: /dashboard.php#passwort');
    exit;
}

// Token aus GET (Link aus der E-Mail) oder POST (Formular)
$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
$valid = auth_find_reset_token($token) !== null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $res = auth_complete_password_reset(
        $token,
        (string) ($_POST['new_password'] ?? ''),
        (string) ($_POST['confirm_password'] ?? '')
    );
    if ($res === true) {
        header('Location: /login.php?reset=1');
        exit;
    }
    $error = $res;
    $valid = auth_find_reset_token($token) !== null; // Token evtl. verbraucht/abgelaufen
} elseif (!$valid && $token !== '') {
    cg_log(LOG_PASSWORD_RESET_FAIL, ['reason' => 'invalid_token']);
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">
<section class="cg-login-section d-flex align-items-center justify-content-center">
  <div class="cg-login-box">

    <div class="text-center mb-4">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-login-logo" width="64" height="64">
      <h1 class="cg-login-title mt-3">Neues Passwort</h1>
      <p class="cg-login-sub">Lege jetzt dein neues Passwort fest</p>
    </div>

    <?php if (!$valid): ?>
      <div class="cg-login-error" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= e($error !== '' ? $error : 'Der Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.') ?>
      </div>
      <a href="/forgot.php" class="cg-login-btn text-decoration-none mt-3">
        <i class="fas fa-paper-plane me-2"></i>Neuen Link anfordern
      </a>
    <?php else: ?>

      <?php if ($error !== ''): ?>
        <div class="cg-login-error" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/reset.php" autocomplete="off" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="cg-form-group">
          <label for="new_password" class="cg-form-label">Neues Passwort</label>
          <div class="cg-input-wrap">
            <i class="fas fa-key cg-input-icon"></i>
            <input type="password" id="new_password" name="new_password" class="cg-form-input"
                   minlength="<?= (int) CG_PASSWORD_MIN ?>" maxlength="128" autocomplete="new-password" required autofocus>
          </div>
          <p class="cg-form-hint">Mindestens <?= (int) CG_PASSWORD_MIN ?> Zeichen.</p>
        </div>

        <div class="cg-form-group">
          <label for="confirm_password" class="cg-form-label">Passwort wiederholen</label>
          <div class="cg-input-wrap">
            <i class="fas fa-key cg-input-icon"></i>
            <input type="password" id="confirm_password" name="confirm_password" class="cg-form-input"
                   minlength="<?= (int) CG_PASSWORD_MIN ?>" maxlength="128" autocomplete="new-password" required>
          </div>
        </div>

        <button type="submit" class="cg-login-btn">
          <i class="fas fa-floppy-disk me-2"></i>Passwort speichern
        </button>
      </form>
    <?php endif; ?>

  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
