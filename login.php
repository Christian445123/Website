<?php
$pageTitle = 'Login';
require_once __DIR__ . '/includes/head.php';

// Already logged in → redirect home
if (auth_check()) {
    header('Location: /');
    exit;
}

$redirect = isset($_GET['redirect']) ? filter_var($_GET['redirect'], FILTER_SANITIZE_URL) : '/';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (auth_login($username, $password)) {
        header('Location: ' . $redirect);
        exit;
    }

    $error = 'Benutzername oder Passwort falsch.';
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
      <h1 class="cg-login-title mt-3">Login</h1>
      <p class="cg-login-sub">Melde dich mit deinem Account an</p>
    </div>

    <?php if ($error !== ''): ?>
      <div class="cg-login-error" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/login.php<?= $redirect !== '/' ? '?redirect=' . urlencode($redirect) : '' ?>" novalidate>

      <div class="cg-form-group">
        <label for="username" class="cg-form-label">Benutzername</label>
        <div class="cg-input-wrap">
          <i class="fas fa-user cg-input-icon"></i>
          <input type="text" id="username" name="username" class="cg-form-input"
                 placeholder="Dein Benutzername"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" required autofocus>
        </div>
      </div>

      <div class="cg-form-group">
        <label for="password" class="cg-form-label">Passwort</label>
        <div class="cg-input-wrap">
          <i class="fas fa-lock cg-input-icon"></i>
          <input type="password" id="password" name="password" class="cg-form-input"
                 placeholder="Dein Passwort"
                 autocomplete="current-password" required>
        </div>
      </div>

      <button type="submit" class="cg-login-btn">
        <i class="fas fa-sign-in-alt me-2"></i>Anmelden
      </button>

    </form>

    <p class="text-center mt-4 mb-0" style="color:#8b8b9a;font-size:.9rem;">
      Noch kein Konto?
      <a href="/register.php" style="color:var(--cg-red);text-decoration:none;font-weight:600;">Jetzt registrieren</a>
    </p>

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
.cg-login-sub {
  color: #8b8b9a;
  font-size: .9rem;
  margin: 0;
}
.cg-login-error {
  background: rgba(224,49,49,.12);
  border: 1px solid rgba(224,49,49,.35);
  border-radius: .5rem;
  padding: .75rem 1rem;
  color: #e03131;
  font-size: .88rem;
  margin-bottom: 1.25rem;
}
.cg-form-group {
  margin-bottom: 1.1rem;
}
.cg-form-label {
  display: block;
  color: #c0c0cc;
  font-size: .85rem;
  font-weight: 600;
  margin-bottom: .4rem;
}
.cg-input-wrap {
  position: relative;
}
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
.cg-form-input::placeholder {
  color: #4a4a5a;
}
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
  transition: background .2s, transform .15s, box-shadow .2s;
}
.cg-login-btn:hover {
  background: #c92a2a;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
