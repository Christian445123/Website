<?php
$pageTitle = 'Passwort vergessen';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';

if (auth_check()) {
    header('Location: ' . cg_url('/dashboard.php') . '#passwort');
    exit;
}

$error = '';
$sent  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Die Sitzung ist abgelaufen. Bitte versuche es erneut.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        $sent = true; // Honeypot → Bot bekommt dieselbe Antwort, es passiert nichts
    } else {
        $res = auth_request_password_reset((string) ($_POST['email'] ?? ''));
        if ($res === true) {
            $sent = true;
        } else {
            $error = $res;
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">
<section class="cg-login-section d-flex align-items-center justify-content-center">
  <div class="cg-login-box">

    <div class="text-center mb-4">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-login-logo" width="64" height="64">
      <h1 class="cg-login-title mt-3">Passwort vergessen</h1>
      <p class="cg-login-sub">Wir schicken dir einen Link zum Zurücksetzen</p>
    </div>

    <?php if ($sent): ?>
      <div class="cg-login-success" role="alert">
        <i class="fas fa-envelope-circle-check me-2"></i>Wenn ein Konto mit dieser E-Mail-Adresse existiert, haben wir dir einen Link zum Zurücksetzen geschickt. Er ist 1 Stunde gültig.
      </div>
      <p class="cg-form-hint">Keine E-Mail erhalten? Schau auch im Spam-Ordner nach.</p>
      <a href="/login.php" class="cg-login-btn text-decoration-none mt-3">
        <i class="fas fa-arrow-left me-2"></i>Zurück zum Login
      </a>
    <?php else: ?>

      <?php if ($error !== ''): ?>
        <div class="cg-login-error" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/forgot.php" novalidate>
        <?= csrf_field() ?>

        <div class="cg-form-group">
          <label for="email" class="cg-form-label">E-Mail-Adresse</label>
          <div class="cg-input-wrap">
            <i class="fas fa-envelope cg-input-icon"></i>
            <input type="email" id="email" name="email" class="cg-form-input"
                   placeholder="Deine E-Mail-Adresse"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   maxlength="254" autocomplete="email" required autofocus>
          </div>
        </div>

        <!-- Honeypot -->
        <div class="cg-hp" aria-hidden="true">
          <label for="website">Website</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <button type="submit" class="cg-login-btn">
          <i class="fas fa-paper-plane me-2"></i>Link anfordern
        </button>
      </form>

      <p class="text-center mt-4 mb-0 cg-text-muted" style="font-size:.9rem;">
        <a href="/login.php" style="font-weight:600;">Zurück zum Login</a>
      </p>
    <?php endif; ?>

  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
