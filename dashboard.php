<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/head.php';

auth_require_login();
$user = auth_user();

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

<main>

  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Mitgliederbereich</span>
      <h1 class="mt-2">Dashboard</h1>
      <p>Willkommen zurück, <strong style="color:var(--cg-red)"><?= htmlspecialchars($user['username']) ?></strong>!</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row gy-4">

        <!-- Profil -->
        <div class="col-12 col-lg-4">
          <div class="cg-card h-100">
            <h5 class="mb-4" style="color:var(--cg-red);">
              <i class="fas fa-user-circle me-2"></i>Mein Profil
            </h5>
            <ul class="list-unstyled mb-0" style="display:flex;flex-direction:column;gap:1rem;">
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:.9rem;border-radius:.5rem;flex-shrink:0;">
                  <i class="fas fa-user"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Benutzername</div>
                  <div style="font-weight:600;"><?= htmlspecialchars($user['username']) ?></div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:.9rem;border-radius:.5rem;flex-shrink:0;">
                  <i class="fas fa-envelope"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">E-Mail</div>
                  <div style="font-weight:600;"><?= htmlspecialchars($user['email'] ?? '&ndash;') ?></div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:.9rem;border-radius:.5rem;flex-shrink:0;<?= $user['role']==='admin' ? 'background:var(--cg-red-subtle);color:var(--cg-red);' : '' ?>">
                  <i class="fas fa-shield-halved"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Rolle</div>
                  <div style="font-weight:600;"><?= $user['role']==='admin' ? '<span style="color:var(--cg-red)">Administrator</span>' : 'Mitglied' ?></div>
                </div>
              </li>
            </ul>
            <hr style="border-color:var(--cg-border);margin:1.5rem 0;">
            <div class="d-flex flex-column gap-2">
              <?php if ($user['role']==='admin'): ?>
              <a href="/admin/" class="cg-btn cg-btn-primary w-100" style="justify-content:center;">
                <i class="fas fa-shield-halved"></i>Admin-Panel
              </a>
              <?php endif; ?>
              <a href="/community.php" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
                <i class="fab fa-discord"></i>Zur Community
              </a>
              <a href="/logout.php" class="cg-btn cg-btn-outline w-100" style="justify-content:center;border-color:rgba(224,49,49,.4);color:#e03131;">
                <i class="fas fa-sign-out-alt"></i>Abmelden
              </a>
            </div>
          </div>
        </div>

        <!-- Rechte Spalte -->
        <div class="col-12 col-lg-8">
          <div style="display:flex;flex-direction:column;gap:1.5rem;">

            <!-- Discord Banner -->
            <div class="cg-card" style="background:linear-gradient(135deg,rgba(88,101,242,.12),rgba(88,101,242,.04));border-color:rgba(88,101,242,.3);">
              <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                  <span class="cg-feature-icon" style="background:rgba(88,101,242,.2);color:#7289da;min-width:44px;height:44px;flex-shrink:0;">
                    <i class="fab fa-discord"></i>
                  </span>
                  <div>
                    <h6 class="mb-0" style="font-weight:700;">Discord Community</h6>
                    <p class="mb-0" style="font-size:.82rem;color:var(--cg-muted);">Tritt dem Server bei und bleib auf dem Laufenden!</p>
                  </div>
                </div>
                <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener"
                   class="cg-btn-discord d-inline-flex align-items-center gap-2 flex-shrink-0"
                   style="padding:.55rem 1.25rem;font-size:.9rem;border-radius:.55rem;">
                  <i class="fab fa-discord"></i>Beitreten
                </a>
              </div>
            </div>

            <!-- Ankuendigungen -->
            <div>
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0"><i class="fas fa-bullhorn me-2" style="color:var(--cg-red);"></i>Neueste Ankündigungen</h5>
                <a href="/community.php" style="font-size:.82rem;color:var(--cg-red);text-decoration:none;">
                  Alle anzeigen <i class="fas fa-arrow-right ms-1"></i>
                </a>
              </div>

              <?php if (empty($announcements)): ?>
                <div class="cg-card text-center" style="padding:2rem;opacity:.65;">
                  <i class="fas fa-newspaper fa-2x mb-2" style="color:var(--cg-muted);"></i>
                  <p style="color:var(--cg-muted);margin:0;font-size:.88rem;">Noch keine Ankündigungen vorhanden.</p>
                </div>
              <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.85rem;">
                  <?php foreach ($announcements as $ann): ?>
                  <div class="cg-card" style="padding:1rem 1.1rem;">
                    <div class="d-flex gap-3 align-items-start">
                      <span class="cg-feature-icon" style="min-width:36px;height:36px;font-size:.8rem;border-radius:.4rem;flex-shrink:0;background:var(--cg-red-subtle);color:var(--cg-red);">
                        <i class="fas fa-bullhorn"></i>
                      </span>
                      <div class="flex-grow-1 min-w-0">
                        <div style="font-weight:600;margin-bottom:.2rem;"><?= htmlspecialchars($ann['title']) ?></div>
                        <div style="font-size:.75rem;color:var(--cg-muted);margin-bottom:.5rem;">
                          <?= htmlspecialchars($ann['author_name']) ?> &middot; <?= date('d.m.Y', strtotime($ann['created_at'])) ?>
                        </div>
                        <p style="color:var(--cg-text-2);font-size:.88rem;margin:0;">
                          <?= htmlspecialchars(mb_strimwidth($ann['body'], 0, 160, '...')) ?>
                        </p>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                <div class="text-end mt-2">
                  <a href="/community.php" class="cg-btn cg-btn-outline" style="font-size:.85rem;">
                    <i class="fas fa-newspaper me-1"></i>Alle Ankündigungen
                  </a>
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>