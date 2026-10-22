<?php
$pageTitle = 'Registrieren';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';

// Already logged in → redirect home
if (auth_check()) {
    header('Location: ' . cg_url('/'));
    exit;
}

$error      = '';
$registered = null; // array with 'email' on success

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Die Sitzung ist abgelaufen. Bitte versuche es erneut.';
    } elseif (trim((string) ($_POST['website'] ?? '')) !== '') {
        // Honeypot gefüllt → Bot. Gleiche Erfolgsmeldung, aber nichts passiert.
        $registered = ['email' => trim((string) ($_POST['email'] ?? ''))];
    } else {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName  = trim((string) ($_POST['last_name']  ?? ''));
        $email     = trim((string) ($_POST['email']      ?? ''));

        $result = auth_register($firstName, $lastName, $email);

        if (is_array($result)) {
            $registered = $result; // success
        } else {
            $error = $result; // error string
        }
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">
<section class="cg-login-section d-flex align-items-center justify-content-center">
  <div class="cg-login-box cg-login-wide">

    <!-- Logo -->
    <div class="text-center mb-4">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-login-logo" width="64" height="64">
      <h1 class="cg-login-title mt-3">Registrieren</h1>
      <p class="cg-login-sub">Erstelle deinen Account</p>
    </div>

    <?php if ($registered !== null): ?>
      <!-- ── Success: Zugangsdaten gehen NUR per E-Mail raus ── -->
      <div class="cg-login-success" role="alert">
        <i class="fas fa-envelope-circle-check me-2"></i>
        <div>Wenn die folgende Adresse noch nicht registriert war, haben wir dir jetzt eine E-Mail mit deinen Zugangsdaten und einem Bestätigungslink geschickt. Der Link ist 24 Stunden gültig.</div>
        <div class="mt-2"><strong><?= e($registered['email']) ?></strong></div>
      </div>
      <p class="cg-text-muted" style="font-size:.85rem;">
        <i class="fas fa-circle-info me-1"></i>
        Keine E-Mail erhalten? Schau auch im Spam-Ordner nach oder schreib uns an
        <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>.
      </p>

      <a href="/login.php" class="cg-login-btn text-decoration-none mt-3">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Zum Login
      </a>

    <?php else: ?>
      <!-- ── Registration form ── -->
      <?php if ($error !== ''): ?>
        <div class="cg-login-error" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/register.php" novalidate>
        <?= csrf_field() ?>

        <div class="cg-form-row">
          <div class="cg-form-group">
            <label for="first_name" class="cg-form-label">Vorname</label>
            <div class="cg-input-wrap">
              <i class="fas fa-user cg-input-icon"></i>
              <input type="text" id="first_name" name="first_name" class="cg-form-input"
                     placeholder="Vorname"
                     value="<?= e($_POST['first_name'] ?? '') ?>"
                     maxlength="64" autocomplete="given-name" required autofocus>
            </div>
          </div>

          <div class="cg-form-group">
            <label for="last_name" class="cg-form-label">Nachname</label>
            <div class="cg-input-wrap">
              <i class="fas fa-user cg-input-icon"></i>
              <input type="text" id="last_name" name="last_name" class="cg-form-input"
                     placeholder="Nachname"
                     value="<?= e($_POST['last_name'] ?? '') ?>"
                     maxlength="64" autocomplete="family-name" required>
            </div>
          </div>
        </div>

        <div class="cg-form-group">
          <label for="email" class="cg-form-label">E-Mail</label>
          <div class="cg-input-wrap">
            <i class="fas fa-envelope cg-input-icon"></i>
            <input type="email" id="email" name="email" class="cg-form-input"
                   placeholder="Deine E-Mail-Adresse"
                   value="<?= e($_POST['email'] ?? '') ?>"
                   maxlength="254" autocomplete="email" required>
          </div>
        </div>

        <!-- Honeypot: für Menschen unsichtbar, Bots füllen es aus -->
        <div class="cg-hp" aria-hidden="true">
          <label for="website">Website</label>
          <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <p class="cg-form-hint" style="margin:.25rem 0 1rem;">
          <i class="fas fa-circle-info me-1 cg-red"></i>
          Benutzername &amp; Passwort werden automatisch generiert und <strong>ausschließlich per E-Mail</strong> an dich gesendet.
        </p>

        <button type="submit" class="cg-login-btn">
          <i class="fas fa-user-plus me-2"></i>Account erstellen
        </button>

      </form>

      <p class="text-center mt-4 mb-0 cg-text-muted" style="font-size:.9rem;">
        Bereits ein Konto?
        <a href="/login.php" style="font-weight:600;">Anmelden</a>
      </p>

    <?php endif; ?>

  </div>
</section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
