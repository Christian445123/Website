<?php
$pageTitle       = 'Startseite';
$pageDescription = 'Willkommen auf der offiziellen Website von Chris_Gaming445 – Streamer, Gamer und IT-Systemtechniker aus Tirol.';
require_once __DIR__ . '/includes/head.php';
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- ============================================================
       Hero Section
       ============================================================ -->
  <section class="cg-hero" id="home">
    <div class="container">
      <div class="row align-items-center gy-5">

        <!-- Logo -->
        <div class="col-12 col-lg-5 text-center">
          <a href="index.php">
            <img src="assets/images/cg-logo-neu.png-1-800x800.png"
                 alt="Chris_Gaming445 Logo"
                 class="cg-hero-logo">
          </a>
        </div>

        <!-- Text -->
        <div class="col-12 col-lg-7">
          <span class="cg-section-label">Willkommen</span>
          <h1 class="cg-hero-title">
            Hey, ich bin<br>
            <span class="cg-gradient-text">Chris_Gaming445</span>
          </h1>
          <p class="cg-hero-subtitle">
            Streamer · Gamer · IT-Systemtechniker aus Tirol.<br>
            Hier findest du alles über mich, meine Projekte und mein Equipment.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a href="cgprojekte.php" class="cg-btn cg-btn-primary">
              <i class="fas fa-folder-open"></i>Meine Projekte
            </a>
            <a href="vorstellung.php" class="cg-btn cg-btn-outline">
              <i class="fas fa-user"></i>Über mich
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================================================
       Quick Info Cards
       ============================================================ -->
  <section class="cg-section">
    <div class="container">

      <div class="text-center mb-5">
        <span class="cg-section-label">Was ich mache</span>
        <h2 class="mt-2">Entdecke meinen Content</h2>
        <p class="cg-text-muted mt-2">Von Gaming-Streams bis IT-Projekten – hier ist immer was los.</p>
      </div>

      <div class="row gy-4">

        <div class="col-12 col-md-6 col-lg-3" data-cg-animate="animate__fadeInUp" data-cg-delay="0s">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto">
              <i class="fab fa-twitch"></i>
            </div>
            <h5 class="mb-2">Streaming</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Schau mir live auf Twitch beim Zocken zu.</p>
            <a href="https://www.twitch.tv/chris_gaming445" target="_blank" rel="noopener" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
              Zum Stream
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3" data-cg-animate="animate__fadeInUp" data-cg-delay=".1s">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(255,0,0,.12);color:#ff4444;">
              <i class="fab fa-youtube"></i>
            </div>
            <h5 class="mb-2">YouTube</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Videos, Highlights und Gameplays auf meinem Channel.</p>
            <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
              Zum Channel
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3" data-cg-animate="animate__fadeInUp" data-cg-delay=".2s">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(112,72,232,.15);color:#9775fa;">
              <i class="fas fa-code"></i>
            </div>
            <h5 class="mb-2">Projekte</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Von IT bis Kreativprojekten – sieh dir meine Arbeit an.</p>
            <a href="cgprojekte.php" class="cg-btn cg-btn-outline w-100" style="justify-content:center;">
              Projekte ansehen
            </a>
          </div>
        </div>

        <div class="col-12 col-md-6 col-lg-3" data-cg-animate="animate__fadeInUp" data-cg-delay=".3s">
          <div class="cg-card h-100 text-center">
            <div class="cg-feature-icon mx-auto" style="background:rgba(88,101,242,.15);color:#7289da;">
              <i class="fab fa-discord"></i>
            </div>
            <h5 class="mb-2">Community</h5>
            <p class="cg-text-muted mb-3" style="font-size:.9rem;">Tritt meinem Discord bei und werde Teil der Community.</p>
            <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener" class="cg-btn-discord w-100" style="justify-content:center;">
              <i class="fab fa-discord me-1"></i>Discord
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ============================================================
       CTA Strip
       ============================================================ -->
  <section class="cg-section-sm" style="background:var(--cg-surface); border-top:1px solid var(--cg-border); border-bottom:1px solid var(--cg-border);">
    <div class="container">
      <div class="row align-items-center gy-3">
        <div class="col-12 col-md-8">
          <h3 class="mb-1">Neugierig geworden?</h3>
          <p class="cg-text-muted mb-0">Erfahre mehr über mich und meine Geschichte.</p>
        </div>
        <div class="col-12 col-md-4 text-md-end">
          <a href="vorstellung.php" class="cg-btn cg-btn-primary">
            <i class="fas fa-arrow-right"></i>Zur Vorstellung
          </a>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
