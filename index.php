<?php
$pageTitle       = 'Startseite';
$pageDescription = 'Willkommen auf der offiziellen Website von Chris_Gaming445 – Streamer, Gamer und IT-Systemtechniker aus Tirol.';
require_once __DIR__ . '/includes/head.php';
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">

  <!-- ============================================================
       Hero Section
       ============================================================ -->
  <section class="cg-hero" id="home">
    <div class="container">
      <div class="row align-items-center gy-5">

        <!-- Text -->
        <div class="col-12 col-lg-7 order-2 order-lg-1">
          <span class="cg-section-label">Willkommen</span>
          <h1 class="cg-hero-title">
            Hey, ich bin<br>
            <span class="cg-gradient-text">Chris_Gaming445</span>
          </h1>
          <p class="cg-hero-subtitle">
            Streamer · Gamer · IT-Systemtechniker aus Tirol.<br>
            Hier findest du alles über mich, meine Projekte und mein Equipment.
          </p>
          <div class="cg-hero-badges">
            <span class="cg-badge"><i class="fab fa-twitch"></i>Live Streaming</span>
            <span class="cg-badge"><i class="fas fa-gamepad"></i>PC-Gaming</span>
            <span class="cg-badge"><i class="fas fa-network-wired"></i>IT &amp; Technik</span>
            <span class="cg-badge"><i class="fas fa-mountain-sun"></i>Tirol</span>
          </div>
          <div class="d-flex flex-wrap gap-3">
            <a href="/cgprojekte.php" class="cg-btn cg-btn-primary">
              <i class="fas fa-folder-open"></i>Meine Projekte
            </a>
            <a href="/vorstellung.php" class="cg-btn cg-btn-outline">
              <i class="fas fa-user"></i>Über mich
            </a>
          </div>
        </div>

        <!-- Logo -->
        <div class="col-12 col-lg-5 text-center order-1 order-lg-2">
          <div class="cg-hero-visual">
            <img src="/assets/images/cg-logo-neu.webp-1-800x800.webp"
                 alt="Chris_Gaming445 Logo"
                 class="cg-hero-logo" width="749" height="421">
          </div>
        </div>

      </div>
    </div>
    <a href="#content" class="cg-scroll-hint" aria-label="Nach unten scrollen"><i class="fas fa-chevron-down"></i></a>
  </section>

  <!-- ============================================================
       Quick Info Cards
       ============================================================ -->
  <section class="cg-section" id="content">
    <div class="container">

      <div class="text-center mb-5 cg-reveal">
        <span class="cg-section-label">Was ich mache</span>
        <h2 class="mt-2">Entdecke meinen Content</h2>
        <p class="cg-text-muted mt-2">Von Gaming-Streams bis IT-Projekten – hier ist immer was los.</p>
      </div>

      <div class="row gy-4">

        <div class="col-12 col-md-6 col-lg-3 cg-reveal">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(145,71,255,.15);color:#9147ff;">
              <i class="fab fa-twitch"></i>
            </div>
            <h5 class="mb-2">Streaming</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Schau mir live auf Twitch beim Zocken zu.</p>
            <a href="https://www.twitch.tv/chris_gaming445" target="_blank" rel="noopener noreferrer" class="cg-btn cg-btn-outline w-100">
              Zum Stream
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 cg-reveal">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(255,0,0,.12);color:#ff4444;">
              <i class="fab fa-youtube"></i>
            </div>
            <h5 class="mb-2">YouTube</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Videos, Highlights und Gameplays auf meinem Channel.</p>
            <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener noreferrer" class="cg-btn cg-btn-outline w-100">
              Zum Channel
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 cg-reveal">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(112,72,232,.15);color:#9775fa;">
              <i class="fas fa-code"></i>
            </div>
            <h5 class="mb-2">Projekte</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Von IT bis Kreativprojekten – sieh dir meine Arbeit an.</p>
            <a href="/cgprojekte.php" class="cg-btn cg-btn-outline w-100">
              Projekte ansehen
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3 cg-reveal">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(88,101,242,.15);color:#7289da;">
              <i class="fab fa-discord"></i>
            </div>
            <h5 class="mb-2">Community</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Tritt meinem Discord bei und werde Teil der Community.</p>
            <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener noreferrer" class="cg-btn-discord w-100">
              <i class="fab fa-discord me-1"></i>Discord
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================================================
       CTA
       ============================================================ -->
  <section class="cg-section-sm">
    <div class="container cg-reveal">
      <div class="cg-cta">
        <div class="row align-items-center gy-3">
          <div class="col-12 col-md-8">
            <h3 class="mb-1">Neugierig geworden?</h3>
            <p class="cg-text-muted mb-0">Erfahre mehr über mich und meine Geschichte.</p>
          </div>
          <div class="col-12 col-md-4 text-md-end">
            <a href="/vorstellung.php" class="cg-btn cg-btn-primary">
              <i class="fas fa-arrow-right"></i>Zur Vorstellung
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
