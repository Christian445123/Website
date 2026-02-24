<?php
$pageTitle = 'Registrieren';
require_once __DIR__ . '/includes/head.php';

// Already logged in → redirect home
if (auth_check()) {
    header('Location: /');
    exit;
}

$error      = '';
$registered = null; // array with 'username', 'password', 'email_sent'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<section class="cg-login-section d-flex align-items-center justify-content-center"
         style="min-height:100vh;padding:6rem 1rem 3rem;">
  <div class="cg-login-box">

    <!-- Logo -->
    <div class="text-center mb-4">
      <img src="assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo"
           style="width:64px;height:64px;border-radius:50%;border:2px solid var(--cg-red)">
      <h1 class="cg-login-title mt-3">Registrieren</h1>
      <p class="cg-login-sub">Erstelle deinen Account</p>
    </div>

    <?php if ($registered !== null): ?>
      <!-- â”€â”€ Success: show credentials â”€â”€ -->
      <?php if ($registered['email_sent']): ?>
        <div class="cg-login-success" role="alert">
          <i class="fas fa-envelope-circle-check me-2"></i>
          Wir haben dir eine E-Mail an <strong><?= htmlspecialchars($_POST['email'] ?? '') ?></strong> geschickt.
          Bitte klicke auf den Bestätigungslink, um deinen Account zu aktivieren.
        </div>
      <?php else: ?>
        <div class="cg-login-warn" role="alert">
          <i class="fas fa-triangle-exclamation me-2"></i>
          Die Bestätigungs-E-Mail konnte nicht zugestellt werden.
          Bitte notiere dir deine Zugangsdaten unten – du erhältst keinen weiteren Hinweis per Mail.
        </div>
      <?php endif; ?>

      <div class="cg-credentials-box">
        <p class="cg-credentials-title"><i class="fas fa-key me-2"></i>Deine Zugangsdaten</p>
        <div class="cg-cred-row">
          <span class="cg-cred-label">Benutzername</span>
          <span class="cg-cred-value" id="credUser"><?= htmlspecialchars($registered['username']) ?></span>
          <button type="button" class="cg-copy-btn" onclick="cgCopy('credUser',this)" title="Kopieren">
            <i class="fas fa-copy"></i>
          </button>
        </div>
        <div class="cg-cred-row">
          <span class="cg-cred-label">Passwort</span>
          <span class="cg-cred-value" id="credPass"><?= htmlspecialchars($registered['password']) ?></span>
          <button type="button" class="cg-copy-btn" onclick="cgCopy('credPass',this)" title="Kopieren">
            <i class="fas fa-copy"></i>
          </button>
        </div>
      </div>

      <?php if ($registered['email_sent']): ?>
        <p style="color:#8b8b9a;font-size:.82rem;text-align:center;margin-top:.75rem;">
          <i class="fas fa-circle-info me-1"></i>
          Kein Link erhalten?
          <a href="mailto:noreply@chrisgaming.at" style="color:var(--cg-red);text-decoration:none;">Kontakt</a>
        </p>
      <?php endif; ?>

      <a href="/login.php" class="cg-login-btn d-block text-center text-decoration-none mt-3">
        <i class="fas fa-arrow-right-to-bracket me-2"></i>Zum Login
      </a>

    <?php else: ?>
      <!-- â”€â”€ Registration form â”€â”€ -->
      <?php if ($error !== ''): ?>
        <div class="cg-login-error" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/register.php" novalidate>

        <div class="cg-form-row">
          <div class="cg-form-group">
            <label for="first_name" class="cg-form-label">Vorname</label>
            <div class="cg-input-wrap">
              <i class="fas fa-user cg-input-icon"></i>
              <input type="text" id="first_name" name="first_name" class="cg-form-input"
                     placeholder="Vorname"
                     value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                     autocomplete="given-name" required autofocus>
            </div>
          </div>

          <div class="cg-form-group">
            <label for="last_name" class="cg-form-label">Nachname</label>
            <div class="cg-input-wrap">
              <i class="fas fa-user cg-input-icon"></i>
              <input type="text" id="last_name" name="last_name" class="cg-form-input"
                     placeholder="Nachname"
                     value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                     autocomplete="family-name" required>
            </div>
          </div>
        </div>

        <div class="cg-form-group">
          <label for="email" class="cg-form-label">E-Mail</label>
          <div class="cg-input-wrap">
            <i class="fas fa-envelope cg-input-icon"></i>
            <input type="email" id="email" name="email" class="cg-form-input"
                   placeholder="Deine E-Mail-Adresse"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   autocomplete="email" required>
          </div>
        </div>

        <p style="color:#8b8b9a;font-size:.82rem;margin:.25rem 0 1rem;">
          <i class="fas fa-circle-info me-1" style="color:var(--cg-red)"></i>
          Benutzername &amp; Passwort werden automatisch generiert und dir per E-Mail zugesendet.
        </p>

        <button type="submit" class="cg-login-btn">
          <i class="fas fa-user-plus me-2"></i>Account erstellen
        </button>

      </form>

      <p class="text-center mt-4 mb-0" style="color:#8b8b9a;font-size:.9rem;">
        Bereits ein Konto?
        <a href="/login.php" style="color:var(--cg-red);text-decoration:none;font-weight:600;">Anmelden</a>
      </p>

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
  max-width: 460px;
  box-shadow: 0 8px 40px rgba(0,0,0,.5);
}
.cg-login-title {
  font-family: 'Tilt Warp', sans-serif;
  font-size: 1.8rem;
  color: #fff;
  margin-bottom: .25rem;
}
.cg-login-sub { color: #8b8b9a; font-size: .9rem; margin: 0; }
.cg-login-error {
  background: rgba(224,49,49,.12);
  border: 1px solid rgba(224,49,49,.35);
  border-radius: .5rem;
  padding: .75rem 1rem;
  color: #e03131;
  font-size: .88rem;
  margin-bottom: 1.25rem;
}
.cg-login-success {
  background: rgba(55,178,77,.1);
  border: 1px solid rgba(55,178,77,.3);
  border-radius: .5rem;
  padding: .75rem 1rem;
  color: #37b24d;
  font-size: .88rem;
  margin-bottom: 1rem;
}
.cg-login-warn {
  background: rgba(245,159,0,.1);
  border: 1px solid rgba(245,159,0,.3);
  border-radius: .5rem;
  padding: .75rem 1rem;
  color: #f59f00;
  font-size: .88rem;
  margin-bottom: 1rem;
}
.cg-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem;
}
.cg-form-group { margin-bottom: 1.1rem; }
.cg-form-label {
  display: block;
  color: #c0c0cc;
  font-size: .85rem;
  font-weight: 600;
  margin-bottom: .4rem;
}
.cg-input-wrap { position: relative; }
.cg-input-icon {
  position: absolute;
  left: .85rem;
  top: 50%;
  transform: translateY(-50%);
  color: #5a5a6e;
  font-size: .9rem;
  pointer-events: none;
}
.cg-form-input {
  width: 100%;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: .5rem;
  padding: .65rem .9rem .65rem 2.4rem;
  color: #e8e8ed;
  font-size: .95rem;
  transition: border-color .2s;
  outline: none;
}
.cg-form-input:focus {
  border-color: var(--cg-red, #e03131);
  background: rgba(255,255,255,.06);
}
.cg-form-input::placeholder { color: #4a4a5a; }
.cg-login-btn {
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
}
.cg-login-btn:hover { background: #c92a2a; }
/* Credentials box */
.cg-credentials-box {
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: .65rem;
  padding: 1.1rem 1.1rem .85rem;
  margin-top: .5rem;
}
.cg-credentials-title {
  color: #c0c0cc;
  font-weight: 700;
  font-size: .9rem;
  margin: 0 0 .75rem;
}
.cg-cred-row {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .45rem 0;
  border-top: 1px solid rgba(255,255,255,.06);
}
.cg-cred-label {
  color: #8b8b9a;
  font-size: .82rem;
  min-width: 110px;
}
.cg-cred-value {
  color: #e8e8ed;
  font-family: monospace;
  font-size: .95rem;
  flex: 1;
  word-break: break-all;
}
.cg-copy-btn {
  background: none;
  border: 1px solid rgba(255,255,255,.12);
  border-radius: .35rem;
  color: #8b8b9a;
  padding: .2rem .45rem;
  cursor: pointer;
  font-size: .8rem;
  flex-shrink: 0;
  transition: border-color .2s, color .2s;
}
.cg-copy-btn:hover { border-color: var(--cg-red); color: var(--cg-red); }
</style>

<script>
function cgCopy(id, btn) {
  const text = document.getElementById(id)?.textContent?.trim() ?? '';
  navigator.clipboard.writeText(text).then(() => {
    const icon = btn.querySelector('i');
    icon.className = 'fas fa-check';
    setTimeout(() => { icon.className = 'fas fa-copy'; }, 1800);
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
