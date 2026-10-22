<?php
// Determine active page from the current script name
$currentPage  = basename($_SERVER['PHP_SELF']);
$currentPath  = $_SERVER['PHP_SELF'] ?? '';
$isAdminPage  = str_contains($currentPath, '/admin/');
$navUser      = auth_user();
$navLoggedIn  = $navUser !== null;
$navIsAdmin   = $navLoggedIn && ($navUser['role'] ?? '') === 'admin';

function isActive(string $page, string $current): string {
    return $page === $current ? 'active' : '';
}

/** Logout als POST-Formular (CSRF-geschützt) – ein simpler Link könnte von Fremdseiten ausgelöst werden. */
function cg_logout_button(string $class = 'cg-btn-login', string $extra = ''): string {
    return '<form method="POST" action="/logout.php" class="cg-inline-form">'
         . csrf_field()
         . '<button type="submit" class="' . $class . '"' . $extra . '><i class="fas fa-sign-out-alt"></i>Logout</button>'
         . '</form>';
}
?>
<nav class="cg-navbar navbar navbar-expand-lg fixed-top" id="mainNav" aria-label="Hauptnavigation">
  <div class="container-fluid px-3 px-lg-5">

    <!-- Brand -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="CG Logo" class="cg-nav-logo" width="42" height="42">
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
          <a class="cg-nav-link <?= isActive('index.php', $currentPage) ?>" href="/index.php">Startseite</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('vorstellung.php', $currentPage) ?>" href="/vorstellung.php">Vorstellung</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('cgprojekte.php', $currentPage) ?>" href="/cgprojekte.php">Projekte</a>
        </li>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('equipment.php', $currentPage) ?>" href="/equipment.php">Equipment</a>
        </li>
        <?php if ($navLoggedIn): ?>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('community.php', $currentPage) ?>" href="/community.php">
            <i class="fab fa-discord me-1"></i>Community
          </a>
        </li>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="cg-nav-link dropdown-toggle
              <?= in_array($currentPage, ['impressum.php','datenschutz.php','hinweise.php','dsgvo.php']) ? 'active' : '' ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Rechtliches
          </a>
          <ul class="dropdown-menu cg-dropdown">
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('hinweise.php',    $currentPage) ?>" href="/hinweise.php">Allgemeine Hinweise</a></li>
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('impressum.php',   $currentPage) ?>" href="/impressum.php">Impressum</a></li>
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('datenschutz.php', $currentPage) ?>" href="/datenschutz.php">Datenschutzerklärung</a></li>
            <li><a class="dropdown-item cg-dropdown-item <?= isActive('dsgvo.php',       $currentPage) ?>" href="/dsgvo.php">Ihre DSGVO-Rechte</a></li>
          </ul>
        </li>
        <li class="nav-item ms-2">
          <a class="cg-btn-discord" href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener noreferrer">
            <i class="fab fa-discord me-1"></i>Discord
          </a>
        </li>
        <?php if ($navLoggedIn): ?>
        <li class="nav-item">
          <a class="cg-nav-link <?= isActive('dashboard.php', $currentPage) ?>" href="/dashboard.php">
            <i class="fas fa-user-circle me-1"></i>Dashboard
          </a>
        </li>
        <?php if ($navIsAdmin): ?>
        <li class="nav-item dropdown">
          <a class="cg-nav-link dropdown-toggle
              <?= $isAdminPage ? 'active' : '' ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-shield-halved me-1 cg-red"></i>Admin
          </a>
          <ul class="dropdown-menu cg-dropdown">
            <li><a class="dropdown-item cg-dropdown-item" href="/admin/"><i class="fas fa-gauge-high me-2"></i>Übersicht</a></li>
            <li><a class="dropdown-item cg-dropdown-item" href="/admin/users.php"><i class="fas fa-users me-2"></i>Benutzerverwaltung</a></li>
            <li><a class="dropdown-item cg-dropdown-item" href="/admin/projects.php"><i class="fas fa-folder-open me-2"></i>Projekte</a></li>
            <li><a class="dropdown-item cg-dropdown-item" href="/admin/announcements.php"><i class="fas fa-newspaper me-2"></i>Ankündigungen</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item cg-dropdown-item" href="/admin/logs.php"><i class="fas fa-scroll me-2"></i>Activity Log</a></li>
          </ul>
        </li>
        <?php endif; ?>
        <?php endif; ?>
        <!-- Login / User -->
        <li class="nav-item ms-1">
          <?php if ($navLoggedIn): ?>
            <?= cg_logout_button() ?>
          <?php else: ?>
            <a class="cg-btn-login" href="/login.php">
              <i class="fas fa-sign-in-alt"></i>Login
            </a>
          <?php endif; ?>
        </li>
      </ul>
    </div>

  </div>
</nav>

<!-- Offcanvas Mobile Menu -->
<div class="offcanvas offcanvas-end cg-offcanvas" tabindex="-1" id="cgOffcanvas" aria-labelledby="cgOffcanvasLabel">
  <div class="offcanvas-header">
    <div class="d-flex align-items-center gap-2">
      <img src="/assets/images/cg-logo-neu.png-496x496.png" alt="Logo" class="cg-nav-logo" width="42" height="42">
      <span class="cg-brand-text" id="cgOffcanvasLabel">Chris_Gaming445</span>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column">
    <p class="cg-offcanvas-tagline">Willkommen auf der Seite von <strong>Chris_Gaming445</strong></p>

    <ul class="navbar-nav flex-column gap-1 mb-4">
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('index.php', $currentPage) ?>" href="/index.php">Startseite</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('vorstellung.php', $currentPage) ?>" href="/vorstellung.php">Vorstellung</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('cgprojekte.php', $currentPage) ?>" href="/cgprojekte.php">Meine Projekte</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('equipment.php', $currentPage) ?>" href="/equipment.php">Equipment</a>
      </li>
      <?php if ($navLoggedIn): ?>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('community.php', $currentPage) ?>" href="/community.php">
          <i class="fab fa-discord me-1"></i>Community
        </a>
      </li>
      <?php endif; ?>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('impressum.php', $currentPage) ?>" href="/impressum.php">Impressum</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('datenschutz.php', $currentPage) ?>" href="/datenschutz.php">Datenschutzerklärung</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('hinweise.php', $currentPage) ?>" href="/hinweise.php">Allg. Hinweise</a>
      </li>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('dsgvo.php', $currentPage) ?>" href="/dsgvo.php">DSGVO-Rechte</a>
      </li>
      <?php if ($navLoggedIn): ?>
      <li class="nav-item">
        <a class="cg-nav-link <?= isActive('dashboard.php', $currentPage) ?>" href="/dashboard.php">
          <i class="fas fa-user-circle me-1"></i>Dashboard
        </a>
      </li>
      <?php if ($navIsAdmin): ?>
      <li class="nav-item"><a class="cg-nav-link" href="/admin/"><i class="fas fa-shield-halved me-1 cg-red"></i>Admin-Übersicht</a></li>
      <li class="nav-item"><a class="cg-nav-link" href="/admin/users.php"><i class="fas fa-users me-1"></i>Benutzerverwaltung</a></li>
      <li class="nav-item"><a class="cg-nav-link" href="/admin/projects.php"><i class="fas fa-folder-open me-1"></i>Projekte verwalten</a></li>
      <li class="nav-item"><a class="cg-nav-link" href="/admin/announcements.php"><i class="fas fa-newspaper me-1"></i>Ankündigungen</a></li>
      <li class="nav-item"><a class="cg-nav-link" href="/admin/logs.php"><i class="fas fa-scroll me-1"></i>Activity Log</a></li>
      <?php endif; ?>
      <?php endif; ?>
    </ul>

    <div class="mt-auto">
      <!-- Login / User (Mobile) -->
      <div class="mb-3">
        <?php if ($navLoggedIn): ?>
          <div class="cg-offcanvas-user">
            <span>
              <i class="fas fa-user-circle me-2 cg-red"></i><?= e($navUser['username'] ?? 'Account') ?>
            </span>
            <?= cg_logout_button('cg-btn-login cg-btn-sm') ?>
          </div>
        <?php else: ?>
          <a class="cg-btn-login w-100 justify-content-center" href="/login.php">
            <i class="fas fa-sign-in-alt"></i>Login
          </a>
        <?php endif; ?>
      </div>
      <p class="cg-offcanvas-social-title">Besuche mich auf meinen Socials</p>
      <div class="d-flex gap-3 mb-4 cg-social-icons">
        <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="https://www.twitch.tv/chris_gaming445"      target="_blank" rel="noopener noreferrer" aria-label="Twitch"><i class="fab fa-twitch"></i></a>
        <a href="https://www.instagram.com/chris_gaming445/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="https://discord.gg/2suYxHesKD"             target="_blank" rel="noopener noreferrer" aria-label="Discord"><i class="fab fa-discord"></i></a>
      </div>
      <a class="cg-btn-discord w-100 justify-content-center" href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener noreferrer">
        <i class="fab fa-discord me-1"></i>Discord beitreten
      </a>
    </div>
  </div>
</div>
