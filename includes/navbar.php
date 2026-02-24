<?php
// Determine active page from the current script name
$currentPage = basename($_SERVER['PHP_SELF']);

function isActive(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}
?>
<nav class="cg-navbar navbar navbar-expand-lg fixed-top" id="mainNav">
  <div class="container-fluid px-3 px-lg-5">

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <img src="assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-nav-logo">
      <span class="cg-brand-text">Chris_Gaming445</span>
    </a>

    <!-- Mobile Toggle -->
    <button class="navbar-toggler cg-toggler" type="button" data-bs-toggle="offcanvas"
            data-bs-target="#cgOffcanvas" aria-controls="cgOffcanvas" aria-label="Navigation öffnen">
      <span class="cg-hamburger">
        <span></span><span></span><span></span>
      </span>
    </button>

    <!-- Desktop Nav -->
    <div class="collapse navbar-collapse justify-content-end d-none d-lg-flex">
      <ul class="navbar-nav gap-1 align-items-center">
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('index.php', $currentPage) ?>" href="index.php">Startseite</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('vorstellung.php', $currentPage) ?>" href="vorstellung.php">Vorstellung</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('cgprojekte.php', $currentPage) ?>" href="cgprojekte.php">Projekte</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('equipment.php', $currentPage) ?>" href="equipment.php">Equipment</a>
        </li>
        <li class="nav-item dropdown">
          <a class="cg-nav-link dropdown-toggle
              <?= in_array($currentPage, ['impressum.php','datenschutz.php']) ? 'active' : '' ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Rechtliches
          </a>
          <ul class="dropdown-menu cg-dropdown">
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('impressum.php',   $currentPage) ?>" href="impressum.php">Impressum</a></li>
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('datenschutz.php', $currentPage) ?>" href="datenschutz.php">Datenschutzerklärung</a></li>
          </ul>
        </li>
        <li class="nav-item ms-2">
          <a class="cg-btn-discord" href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener">
            <i class="fab fa-discord me-1"></i>Discord
          </a>
        </li>
      </ul>
    </div>

  </div>
</nav>

<!-- Offcanvas Mobile Menu -->
<div class="offcanvas offcanvas-end cg-offcanvas" tabindex="-1" id="cgOffcanvas" aria-labelledby="cgOffcanvasLabel">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2">
      <img src="assets/images/cg-logo-neu.png-496x496.png" alt="Logo" class="cg-nav-logo">
      <span class="cg-brand-text" id="cgOffcanvasLabel">Chris_Gaming445</span>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <p class="cg-offcanvas-tagline">Willkommen auf der Seite von <strong>Chris_Gaming445</strong></p>

    <ul class="navbar-nav flex-column gap-1 mb-4">
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('index.php', $currentPage) ?>" href="index.php">Startseite</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('vorstellung.php', $currentPage) ?>" href="vorstellung.php">Vorstellung</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('cgprojekte.php', $currentPage) ?>" href="cgprojekte.php">Meine Projekte</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('equipment.php', $currentPage) ?>" href="equipment.php">Equipment</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('impressum.php', $currentPage) ?>" href="impressum.php">Impressum</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('datenschutz.php', $currentPage) ?>" href="datenschutz.php">Datenschutzerklärung</a>
      </li>
    </ul>

    <div class="mt-auto">
      <p class="cg-offcanvas-social-title">Besuche mich auf meinen Socials</p>
      <div class="d-flex gap-3 mb-4 cg-social-icons">
        <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="https://www.twitch.tv/chris_gaming445"      target="_blank" rel="noopener" aria-label="Twitch"><i class="fab fa-twitch"></i></a>
        <a href="https://www.instagram.com/chris_gaming445/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="https://discord.gg/2suYxHesKD"             target="_blank" rel="noopener" aria-label="Discord"><i class="fab fa-discord"></i></a>
      </div>
      <a class="cg-btn-discord w-100 text-center" href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener">
        <i class="fab fa-discord me-1"></i>Discord beitreten
      </a>
    </div>
  </div>
</div>
