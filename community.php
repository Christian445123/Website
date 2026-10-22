<?php
$pageTitle       = 'Community';
$noindex         = true;
$pageDescription = 'Exklusiver Mitgliederbereich – Discord, Ankündigungen und Community-Updates.';
require_once __DIR__ . '/includes/head.php';

auth_require_login();

$pdo = db();
$announcements = [];

if ($pdo !== null) {
    try {
        $stmt = $pdo->query(
            'SELECT a.`id`, a.`title`, a.`body`, a.`created_at`,
                    u.`username` AS author_name
             FROM `announcements` a
             JOIN `users` u ON u.`id` = a.`author_id`
             WHERE a.`is_active` = 1
             ORDER BY a.`created_at` DESC
             LIMIT 20'
        );
        $announcements = $stmt->fetchAll();
    } catch (Throwable) {
        $announcements = [];
    }
}
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Mitglieder</span>
      <h1 class="mt-2">Community</h1>
      <p>Willkommen im exklusiven Bereich &ndash; nur für registrierte Mitglieder.</p>
    </div>
  </section>

  <!-- Discord -->
  <section class="cg-section" style="padding-bottom:2rem;">
    <div class="container">
      <div class="cg-card" style="background:linear-gradient(135deg,rgba(88,101,242,.12),rgba(88,101,242,.04));border-color:rgba(88,101,242,.3);">
        <div class="row align-items-center gy-4">
          <div class="col-12 col-md-8">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span class="cg-feature-icon" style="background:rgba(88,101,242,.2);color:#7289da;min-width:56px;height:56px;font-size:1.4rem;flex-shrink:0;">
                <i class="fab fa-discord"></i>
              </span>
              <div>
                <h2 class="mb-1" style="font-size:1.6rem;">Discord Community</h2>
                <p class="mb-0" style="color:var(--cg-muted);font-size:.95rem;">Der offizielle Server von Chris_Gaming445</p>
              </div>
            </div>
            <p style="color:var(--cg-text-2);margin-bottom:0;">
              Tritt jetzt dem Discord-Server bei und sei direkt dabei &ndash; für Ankündigungen,
              Events, Giveaways und lockere Gespräche mit der Community!
            </p>
          </div>
          <div class="col-12 col-md-4 text-md-end">
            <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener"
               class="cg-btn-discord d-inline-flex align-items-center justify-content-center gap-2"
               style="padding:.75rem 2rem;font-size:1rem;border-radius:.65rem;width:100%;max-width:260px;">
              <i class="fab fa-discord"></i>Server beitreten
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Announcements -->
  <section class="cg-section">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-4 gap-3 flex-wrap">
        <div>
          <span class="cg-section-label">Exklusiv</span>
          <h2 class="mt-1 mb-0">Neuigkeiten &amp; Ankündigungen</h2>
        </div>
      </div>

      <?php if (empty($announcements)): ?>
        <div class="cg-card text-center" style="padding:3rem 2rem;opacity:.7;">
          <div class="cg-feature-icon mx-auto mb-3" style="background:rgba(255,255,255,.05);color:var(--cg-muted);">
            <i class="fas fa-newspaper"></i>
          </div>
          <h5 style="color:var(--cg-muted);">Noch keine Ankündigungen</h5>
          <p style="color:var(--cg-muted);margin:0;font-size:.9rem;">
            Hier erscheinen bald exklusive Neuigkeiten für Mitglieder.
          </p>
        </div>
      <?php else: ?>
        <div class="row gy-4">
          <?php foreach ($announcements as $ann): ?>
          <div class="col-12">
            <article class="cg-card">
              <div class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:44px;height:44px;font-size:.95rem;border-radius:.5rem;flex-shrink:0;background:var(--cg-red-subtle);color:var(--cg-red);">
                  <i class="fas fa-bullhorn"></i>
                </span>
                <div class="flex-grow-1 min-w-0">
                  <h5 class="mb-1" style="font-size:1.05rem;"><?= e($ann['title']) ?></h5>
                  <div class="d-flex gap-3 mb-3" style="font-size:.78rem;color:var(--cg-muted);">
                    <span><i class="fas fa-user me-1"></i><?= e($ann['author_name']) ?></span>
                    <span><i class="fas fa-calendar me-1"></i><?= e(date('d.m.Y', strtotime($ann['created_at'])) . ' – ' . date('H:i', strtotime($ann['created_at']))) ?> Uhr</span>
                  </div>
                  <div class="cg-announcement-body">
                    <?= nl2br(e($ann['body'])) ?>
                  </div>
                </div>
              </div>
            </article>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>

</main>

<style>
.cg-announcement-body {
  color: var(--cg-text-2);
  font-size: .95rem;
  line-height: 1.7;
  white-space: pre-wrap;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
