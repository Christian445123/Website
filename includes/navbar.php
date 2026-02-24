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
        <!-- Login / User -->
        <li class="nav-item ms-1 cg-user-dropdown">
          <?php if (auth_check()): ?>
            <?php $__u = auth_user(); ?>
            <div class="dropdown">
              <button class="cg-btn-user dropdown-toggle" type="button"
                      data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($__u['username'] ?? 'Account') ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <?php if (($__u['role'] ?? '') === 'admin'): ?>
                <li><a class="dropdown-item" href="/admin.php"><i class="fas fa-tools me-2"></i>Admin</a></li>
                <li><hr class="dropdown-divider" style="border-color:rgba(255,255,255,.1)"></li>
                <?php endif; ?>
                <li><a class="dropdown-item text-danger" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Abmelden</a></li>
              </ul>
            </div>
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
      <!-- Login / User (Mobile) -->
      <div class="mb-3">
        <?php if (auth_check()): ?>
          <?php $__u = auth_user(); ?>
          <div class="d-flex align-items-center justify-content-between p-2 rounded"
               style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1)">
            <span style="font-size:.9rem;color:#e8e8ed">
              <i class="fas fa-user-circle me-2" style="color:var(--cg-red)"></i>
              <?= htmlspecialchars($__u['username'] ?? 'Account') ?>
            </span>
            <a href="/logout.php" class="cg-btn-login" style="padding:.3rem .75rem;font-size:.8rem">
              <i class="fas fa-sign-out-alt"></i>Logout
            </a>
          </div>
        <?php else: ?>
          <a class="cg-btn-login w-100 justify-content-center" href="/login.php">
            <i class="fas fa-sign-in-alt"></i>Login
          </a>
        <?php endif; ?>
      </div>
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
