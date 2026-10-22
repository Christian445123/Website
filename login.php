<?php
$pageTitle = 'Login';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';

// Nur lokale Pfade als Redirect-Ziel (verhindert Open Redirect via ?redirect=//evil.com)
$redirect = cg_safe_redirect($_GET['redirect'] ?? '/');

// Already logged in → redirect
if (auth_check()) {
    header('Location: ' . $redirect);
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Die Sitzung ist abgelaufen. Bitte versuche es erneut.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $result = auth_login($username, $password);
        if ($result === true) {
            header('Location: ' . $redirect);
            exit;
        }

        $error = is_string($result) ? $result : 'Benutzername oder Passwort falsch.';
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">
<section class="cg-login-section d-flex align-items-center justify-content-center">
  <div class="cg-login-box">

    <!-- Logo -->
    <div class="text-center mb-4">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-login-logo" width="64" height="64">
      <h1 class="cg-login-title mt-3">Login</h1>
      <p class="cg-login-sub">Melde dich mit deinem Account an</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="cg-login-error" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login.php<?= $redirect !== '/' ? '?redirect=' . urlencode($redirect) : '' ?>" novalidate>
      <?= csrf_field() ?>

      <div class="cg-form-group">
        <label for="username" class="cg-form-label">Benutzername</label>
        <div class="cg-input-wrap">
          <i class="fas fa-user cg-input-icon"></i>
          <input type="text" id="username" name="username" class="cg-form-input"
                 placeholder="Dein Benutzername"
                 value="<?= e($_POST['username'] ?? '') ?>"
                 maxlength="64" autocomplete="username" required autofocus>
        </div>
      </div>

      <div class="cg-form-group">
        <label for="password" class="cg-form-label">Passwort</label>
        <div class="cg-input-wrap">
          <i class="fas fa-lock cg-input-icon"></i>
          <input type="password" id="password" name="password" class="cg-form-input"
                 placeholder="Dein Passwort"
                 maxlength="1024" autocomplete="current-password" required>
        </div>
      </div>

      <button type="submit" class="cg-login-btn">
        <i class="fas fa-sign-in-alt me-2"></i>Anmelden
      </button>

    </form>

    <p class="text-center mt-4 mb-0 cg-text-muted" style="font-size:.9rem;">
      Noch kein Konto?
      <a href="/register.php" style="font-weight:600;">Jetzt registrieren</a>
    </p>

  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
