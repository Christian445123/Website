<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/head.php';

// Only accessible when logged in
auth_require_login();

$user = auth_user();
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Mitgliederbereich</span>
      <h1 class="mt-2">Dashboard</h1>
      <p>Willkommen zurück, <strong style="color:var(--cg-red)"><?= htmlspecialchars($user['username']) ?></strong>!</p>
    </div>
  </section>

  <!-- Dashboard Content -->
  <section class="cg-section">
    <div class="container">

      <div class="row gy-4">

        <!-- Profile Card -->
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
                  <div style="font-weight:600;"><?= htmlspecialchars($user['email'] ?? '–') ?></div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:.9rem;border-radius:.5rem;flex-shrink:0;">
                  <i class="fas fa-shield-halved"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Rolle</div>
                  <div style="font-weight:600;"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
                </div>
              </li>
            </ul>

            <hr style="border-color:var(--cg-border);margin:1.5rem 0;">

            <a href="/logout.php" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
              <i class="fas fa-sign-out-alt"></i>Abmelden
            </a>
          </div>
        </div>

        <!-- Members-only content -->
        <div class="col-12 col-lg-8">
          <div class="cg-card mb-4">
            <h5 class="mb-3"><i class="fas fa-lock-open me-2" style="color:var(--cg-red);"></i>Exklusiver Bereich</h5>
            <p style="color:var(--cg-text-2);margin-bottom:0;">
              Du bist eingeloggt und hast Zugriff auf diesen Bereich. Hier können exklusive
              Inhalte, Ankündigungen oder Community-Features hinzugefügt werden.
            </p>
          </div>

          <div class="row gy-3">
            <div class="col-12 col-sm-6">
              <div class="cg-card">
                <div class="cg-feature-icon mb-3">
                  <i class="fab fa-twitch"></i>
                </div>
                <h6 class="mb-1">Stream-Updates</h6>
                <p style="font-size:.88rem;color:var(--cg-muted);margin:0;">Aktuelle Infos zu kommenden Streams.</p>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="cg-card">
                <div class="cg-feature-icon mb-3" style="background:rgba(88,101,242,.15);color:#7289da;">
                  <i class="fab fa-discord"></i>
                </div>
                <h6 class="mb-1">Community</h6>
                <p style="font-size:.88rem;color:var(--cg-muted);margin:0;">Tritt dem Discord bei und bleib dabei.</p>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="cg-card">
                <div class="cg-feature-icon mb-3" style="background:rgba(112,72,232,.15);color:#9775fa;">
                  <i class="fas fa-newspaper"></i>
                </div>
                <h6 class="mb-1">Neuigkeiten</h6>
                <p style="font-size:.88rem;color:var(--cg-muted);margin:0;">Exklusive Ankündigungen für Mitglieder.</p>
              </div>
            </div>
            <div class="col-12 col-sm-6">
              <div class="cg-card">
                <div class="cg-feature-icon mb-3" style="background:rgba(255,68,68,.15);color:#ff4444;">
                  <i class="fab fa-youtube"></i>
                </div>
                <h6 class="mb-1">YouTube</h6>
                <p style="font-size:.88rem;color:var(--cg-muted);margin:0;">Früher Zugang zu neuen Videos.</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
