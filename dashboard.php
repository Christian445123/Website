<?php
$pageTitle = 'Dashboard';
$noindex   = true;
require_once __DIR__ . '/includes/head.php';

auth_require_login();
$user = auth_user();

// ── Passwort ändern ──────────────────────────────────────────────
$pwMessage = '';
$pwType    = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    csrf_require();
    $res = auth_change_password(
        (int) $user['id'],
        (string) ($_POST['current_password'] ?? ''),
        (string) ($_POST['new_password'] ?? ''),
        (string) ($_POST['confirm_password'] ?? '')
    );
    if ($res === true) {
        $pwMessage = 'Dein Passwort wurde geändert.';
    } else {
        $pwMessage = $res;
        $pwType    = 'error';
    }
}

// Load latest 3 announcements
$announcements = [];
$pdo = db();
if ($pdo !== null) {
    try {
        $st = $pdo->query(
            'SELECT a.`id`,a.`title`,a.`body`,a.`created_at`,u.`username` AS author_name
             FROM `announcements` a
             JOIN `users` u ON u.`id`=a.`author_id`
             WHERE a.`is_active`=1
             ORDER BY a.`created_at` DESC
             LIMIT 3'
        );
        $announcements = $st->fetchAll();
    } catch (Throwable) {}
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">

  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Mitgliederbereich</span>
      <h1 class="mt-2">Dashboard</h1>
      <p>Willkommen zurück, <strong class="cg-red"><?= e($user['username']) ?></strong>!</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row gy-4">

        <!-- Profil -->
        <div class="col-12 col-lg-4">
          <div class="cg-card cg-card-static h-100">
            <h5 class="mb-4 cg-red">
              <i class="fas fa-user-circle me-2"></i>Mein Profil
            </h5>
            <ul class="cg-meta-list mb-0">
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="width:40px;height:40px;font-size:.9rem;margin:0;flex-shrink:0;">
                  <i class="fas fa-user"></i>
                </span>
                <div class="min-w-0">
                  <div class="cg-meta-label">Benutzername</div>
                  <div class="cg-meta-value"><?= e($user['username']) ?></div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="width:40px;height:40px;font-size:.9rem;margin:0;flex-shrink:0;">
                  <i class="fas fa-envelope"></i>
                </span>
                <div class="min-w-0">
                  <div class="cg-meta-label">E-Mail</div>
                  <div class="cg-meta-value" style="word-break:break-all;"><?= e($user['email'] ?? '–') ?></div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="width:40px;height:40px;font-size:.9rem;margin:0;flex-shrink:0;">
                  <i class="fas fa-shield-halved"></i>
                </span>
                <div>
                  <div class="cg-meta-label">Rolle</div>
                  <div class="cg-meta-value"><?= $user['role'] === 'admin' ? '<span class="cg-red">Administrator</span>' : 'Mitglied' ?></div>
                </div>
              </li>
            </ul>
            <hr style="border-color:var(--cg-border);margin:1.5rem 0;opacity:1;">
            <div class="d-flex flex-column gap-2">
              <?php if ($user['role'] === 'admin'): ?>
              <a href="/admin/" class="cg-btn cg-btn-primary w-100">
                <i class="fas fa-shield-halved"></i>Admin-Panel
              </a>
              <?php endif; ?>
              <a href="/community.php" class="cg-btn cg-btn-outline w-100">
                <i class="fab fa-discord"></i>Zur Community
              </a>
              <form method="POST" action="/logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="cg-btn cg-btn-outline cg-btn-danger-outline w-100">
                  <i class="fas fa-sign-out-alt"></i>Abmelden
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Rechte Spalte -->
        <div class="col-12 col-lg-8">
          <div style="display:flex;flex-direction:column;gap:1.5rem;">

            <!-- Discord Banner -->
            <div class="cg-card cg-card-static" style="background:linear-gradient(135deg,rgba(88,101,242,.14),rgba(88,101,242,.04));border-color:rgba(88,101,242,.3);">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                  <span class="cg-feature-icon" style="background:rgba(88,101,242,.2);color:#7289da;width:46px;height:46px;margin:0;flex-shrink:0;">
                    <i class="fab fa-discord"></i>
                  </span>
                  <div>
                    <h6 class="mb-0" style="font-family:var(--cg-font-body);font-weight:700;">Discord Community</h6>
                    <p class="mb-0 cg-text-muted" style="font-size:.82rem;">Tritt dem Server bei und bleib auf dem Laufenden!</p>
                  </div>
                </div>
                <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener noreferrer" class="cg-btn-discord flex-shrink-0">
                  <i class="fab fa-discord"></i>Beitreten
                </a>
              </div>
            </div>

            <!-- Ankuendigungen -->
            <div>
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0"><i class="fas fa-bullhorn me-2 cg-red"></i>Neueste Ankündigungen</h5>
                <a href="/community.php" style="font-size:.82rem;">
                  Alle anzeigen <i class="fas fa-arrow-right ms-1"></i>
                </a>
              </div>

              <?php if (empty($announcements)): ?>
                <div class="cg-card cg-card-static text-center" style="padding:2rem;opacity:.7;">
                  <i class="fas fa-newspaper fa-2x mb-2 cg-text-muted"></i>
                  <p class="cg-text-muted mb-0" style="font-size:.88rem;">Noch keine Ankündigungen vorhanden.</p>
                </div>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.85rem;">
                  <?php foreach ($announcements as $ann): ?>
                  <div class="cg-card" style="padding:1rem 1.1rem;">
                    <div class="d-flex gap-3 align-items-start">
                      <span class="cg-feature-icon" style="width:38px;height:38px;font-size:.8rem;margin:0;flex-shrink:0;">
                        <i class="fas fa-bullhorn"></i>
                      </span>
                      <div class="flex-grow-1 min-w-0">
                        <div style="font-weight:600;margin-bottom:.2rem;"><?= e($ann['title']) ?></div>
                        <div class="cg-text-muted" style="font-size:.75rem;margin-bottom:.5rem;">
                          <?= e($ann['author_name']) ?> &middot; <?= e(date('d.m.Y', strtotime($ann['created_at']))) ?>
                        </div>
                        <p style="color:var(--cg-text-2);font-size:.88rem;margin:0;">
                          <?= e(mb_strimwidth($ann['body'], 0, 160, '...')) ?>
                        </p>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Passwort ändern -->
            <div class="cg-card cg-card-static" id="passwort">
              <h5 class="mb-1"><i class="fas fa-key me-2 cg-red"></i>Passwort ändern</h5>
              <p class="cg-text-muted" style="font-size:.85rem;">
                Mindestens <?= (int) CG_PASSWORD_MIN ?> Zeichen. Nutze ein Passwort, das du nirgendwo sonst verwendest.
              </p>

              <?php if ($pwMessage !== ''): ?>
                <div class="<?= $pwType === 'success' ? 'cg-login-success' : 'cg-login-error' ?>" role="alert">
                  <i class="fas fa-<?= $pwType === 'success' ? 'circle-check' : 'exclamation-circle' ?> me-2"></i><?= e($pwMessage) ?>
                </div>
              <?php endif; ?>

              <form method="POST" action="/dashboard.php#passwort" autocomplete="off" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="cg-form-group">
                  <label for="current_password" class="cg-form-label">Aktuelles Passwort</label>
                  <div class="cg-input-wrap">
                    <i class="fas fa-lock cg-input-icon"></i>
                    <input type="password" id="current_password" name="current_password" class="cg-form-input"
                           maxlength="1024" autocomplete="current-password" required>
                  </div>
                </div>

                <div class="cg-form-row">
                  <div class="cg-form-group">
                    <label for="new_password" class="cg-form-label">Neues Passwort</label>
                    <div class="cg-input-wrap">
                      <i class="fas fa-key cg-input-icon"></i>
                      <input type="password" id="new_password" name="new_password" class="cg-form-input"
                             minlength="<?= (int) CG_PASSWORD_MIN ?>" maxlength="128" autocomplete="new-password" required>
                    </div>
                  </div>
                  <div class="cg-form-group">
                    <label for="confirm_password" class="cg-form-label">Wiederholen</label>
                    <div class="cg-input-wrap">
                      <i class="fas fa-key cg-input-icon"></i>
                      <input type="password" id="confirm_password" name="confirm_password" class="cg-form-input"
                             minlength="<?= (int) CG_PASSWORD_MIN ?>" maxlength="128" autocomplete="new-password" required>
                    </div>
                  </div>
                </div>

                <button type="submit" class="cg-btn cg-btn-primary">
                  <i class="fas fa-floppy-disk"></i>Passwort speichern
                </button>
              </form>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
