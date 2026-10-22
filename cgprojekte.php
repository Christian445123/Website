<?php
$pageTitle       = 'Meine Projekte';
$pageDescription = 'Alle Projekte von Chris_Gaming445 – von Streaming über IT bis kreative Eigenentwicklungen.';
require_once __DIR__ . '/includes/head.php';

// Load admin-managed DB projects
$dbProjects = [];
$pdo = db();
if ($pdo !== null) {
    try {
        $dbProjects = $pdo->query(
            'SELECT * FROM `projects` WHERE `is_active`=1 ORDER BY `sort_order` ASC, `id` ASC'
        )->fetchAll();
    } catch (Throwable) {}
}
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Portfolio</span>
      <h1 class="mt-2">Meine Projekte</h1>
      <p>Hier findest du alles, woran ich arbeite oder gearbeitet habe.</p>
    </div>
  </section>

  <!-- Projects Grid -->
  <section class="cg-section">
    <div class="container">

      <!-- Info Banner -->
      <div class="cg-card mb-5" style="background:var(--cg-red-subtle);border-color:var(--cg-red-mid);">
        <div class="d-flex align-items-start gap-3">
          <span class="cg-feature-icon" style="min-width:48px;height:48px;font-size:1.2rem;border-radius:.5rem;flex-shrink:0;">
            <i class="fas fa-circle-info"></i>
          </span>
          <div>
            <h5 class="mb-1">Diese Seite wird kontinuierlich erweitert</h5>
            <p class="mb-0 cg-text-muted" style="font-size:.95rem;">
              Neue Projekte werden laufend hinzugefügt. Schau regelmäßig vorbei oder
              folge mir auf meinen Socials für Updates!
            </p>
          </div>
        </div>
      </div>

      <div class="row gy-4">

        <?php if (!empty($dbProjects)): ?>
        <?php foreach ($dbProjects as $proj):
          $tags = $proj['tags'] ? json_decode($proj['tags'], true) : [];
          if (!is_array($tags)) $tags = [];
          // Werte aus der DB nur bereinigt in style=""/class=""/href="" ausgeben
          $iconBg    = cg_clean_css_color((string) $proj['icon_bg'], 'rgba(232,53,31,.15)');
          $iconColor = cg_clean_css_color((string) $proj['icon_color'], '#e8351f');
          $iconClass = cg_clean_icon_class((string) $proj['icon_class']);
          $linkOk    = $proj['link_url'] && $proj['link_label'] && $proj['link_style'] !== 'disabled' && cg_is_http_url((string) $proj['link_url']);
        ?>
        <!-- DB Project: <?= e($proj['title']) ?> -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon"
                 style="background:<?= e($iconBg) ?>;color:<?= e($iconColor) ?>;">
              <i class="<?= e($iconClass) ?>"></i>
            </div>
            <h5 class="mb-2"><?= e($proj['title']) ?></h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;"><?= nl2br(e($proj['description'])) ?></p>
            <?php if (count($tags)): ?>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php $first = true; foreach ($tags as $tag):
                $tagStyle = $first
                  ? 'background:' . $iconBg . ';color:' . $iconColor . ';'
                  : 'background:var(--cg-surface-2);color:var(--cg-muted);';
                $first = false;
              ?>
              <span style="<?= e($tagStyle) ?>padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">
                <?= e($tag) ?>
              </span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($linkOk): ?>
              <?php if ($proj['link_style'] === 'discord'): ?>
                <a href="<?= e($proj['link_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="cg-btn-discord w-100" style="justify-content:center;">
                  <i class="fab fa-discord"></i><?= e($proj['link_label']) ?>
                </a>
              <?php elseif ($proj['link_style'] === 'primary'): ?>
                <a href="<?= e($proj['link_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="cg-btn cg-btn-primary w-100" style="justify-content:center;">
                  <i class="fas fa-arrow-up-right-from-square"></i><?= e($proj['link_label']) ?>
                </a>
              <?php else: ?>
                <a href="<?= e($proj['link_url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="cg-btn cg-btn-outline w-100"
                   style="justify-content:center;border-color:<?= e($iconColor) ?>;color:<?= e($iconColor) ?>;">
                  <i class="fas fa-arrow-up-right-from-square"></i><?= e($proj['link_label']) ?>
                </a>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Static projects (always shown) -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon" style="background:rgba(145,71,255,.15);color:#9147ff;">
              <i class="fab fa-twitch"></i>
            </div>
            <h5 class="mb-2">Twitch Streaming</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">
              Seit ca. 2 Jahren streame ich regelmäßig auf Twitch. Hauptfokus liegt auf
              PC-Gaming mit verschiedenen Genres.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span style="background:rgba(145,71,255,.15);color:#9147ff;padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Live Streaming</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Gaming</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Community</span>
            </div>
            <a href="https://www.twitch.tv/chris_gaming445" target="_blank" rel="noopener noreferrer"
               class="cg-btn cg-btn-outline w-100" style="justify-content:center;border-color:#9147ff;color:#9147ff;">
              <i class="fab fa-twitch"></i>Zum Stream
            </a>
          </div>
        </div>

        <!-- Project: YouTube -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon" style="background:rgba(255,68,68,.15);color:#ff4444;">
              <i class="fab fa-youtube"></i>
            </div>
            <h5 class="mb-2">YouTube Channel</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">
              Auf meinem YouTube-Kanal findest du Highlights, Let's Plays und
              andere Gaming-Videos.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span style="background:rgba(255,68,68,.15);color:#ff4444;padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Videos</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Highlights</span>
            </div>
            <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener noreferrer"
               class="cg-btn cg-btn-outline w-100" style="justify-content:center;border-color:#ff4444;color:#ff4444;">
              <i class="fab fa-youtube"></i>Zum Channel
            </a>
          </div>
        </div>

        <!-- Project: Website -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon" style="background:rgba(112,72,232,.15);color:#9775fa;">
              <i class="fas fa-globe"></i>
            </div>
            <h5 class="mb-2">Diese Website</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">
              Die offizielle Chris_Gaming445-Website – selbst entwickelt als PHP-Projekt
              mit modernem Design und responsivem Layout.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span style="background:rgba(112,72,232,.15);color:#9775fa;padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">PHP</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">HTML/CSS</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Bootstrap</span>
            </div>
            <span class="cg-btn cg-btn-primary w-100" style="justify-content:center;cursor:default;opacity:.8;">
              <i class="fas fa-check"></i>Du bist hier!
            </span>
          </div>
        </div>

        <!-- Project: Discord Community -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon" style="background:rgba(88,101,242,.15);color:#7289da;">
              <i class="fab fa-discord"></i>
            </div>
            <h5 class="mb-2">Discord Community</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">
              Mein Discord-Server ist der Treffpunkt für alle Fans und Mitglieder
              meiner Community – tritt jetzt bei!
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span style="background:rgba(88,101,242,.15);color:#7289da;padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Community</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Events</span>
            </div>
            <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener noreferrer"
               class="cg-btn-discord w-100" style="justify-content:center;">
              <i class="fab fa-discord"></i>Server beitreten
            </a>
          </div>
        </div>

        <!-- Project: IT Ausbildung -->
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100">
            <div class="cg-feature-icon">
              <i class="fas fa-laptop-code"></i>
            </div>
            <h5 class="mb-2">IT-Ausbildung</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">
              Ausbildung zum IT-Systemtechniker – Netzwerke, Server, Hardware und mehr.
              Parallel zu meiner Streaming-Karriere.
            </p>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <span style="background:var(--cg-red-subtle);color:var(--cg-red);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">IT</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Netzwerke</span>
              <span style="background:var(--cg-surface-2);color:var(--cg-muted);padding:.2rem .6rem;border-radius:2rem;font-size:.75rem;font-weight:600;">Laufend</span>
            </div>
            <a href="vorstellung.php" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
              <i class="fas fa-user"></i>Mehr über mich
            </a>
          </div>
        </div>

        <!-- Coming soon / Admin add button -->
        <?php if (auth_check() && auth_user()['role'] === 'admin'): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <a href="/admin/projects.php" class="cg-card h-100 d-block text-decoration-none"
             style="border-style:dashed;border-color:var(--cg-red);opacity:.8;transition:opacity .2s;"
             onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='.8'">
            <div class="cg-feature-icon" style="background:var(--cg-red-subtle);color:var(--cg-red);">
              <i class="fas fa-plus"></i>
            </div>
            <h5 class="mb-2" style="color:var(--cg-red);">Projekt hinzufügen</h5>
            <p style="color:var(--cg-muted);margin:0;font-size:.88rem;">
              Als Admin kannst du hier neue Projektkarten erstellen.
            </p>
          </a>
        </div>
        <?php else: ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="cg-card h-100" style="border-style:dashed;opacity:.6;">
            <div class="cg-feature-icon" style="background:rgba(255,255,255,.07);color:var(--cg-muted);">
              <i class="fas fa-plus"></i>
            </div>
            <h5 class="mb-2" style="color:var(--cg-muted);">Mehr kommt bald&hellip;</h5>
            <p class="cg-text-muted mb-0" style="font-size:.9rem;">
              Neue Projekte sind in Planung. Bleib gespannt!
            </p>
          </div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
