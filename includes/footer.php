<footer class="cg-footer">
  <div class="container">
    <div class="row gy-4">

      <!-- Brand -->
      <div class="col-12 col-lg-4">
        <a href="index.php" class="d-inline-flex align-items-center gap-2 mb-3 text-decoration-none">
          <img src="assets/images/cg-logo-neu-128x128.webp" alt="CG Logo" class="cg-footer-logo">
          <span class="cg-footer-brand">Chris_Gaming445</span>
        </a>
        <p class="cg-footer-desc">Streamer · Gamer · IT-Systemtechniker aus Tirol 🎮</p>
        <div class="d-flex gap-3 cg-social-icons">
          <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
          <a href="https://www.twitch.tv/chris_gaming445"      target="_blank" rel="noopener" aria-label="Twitch"><i class="fab fa-twitch"></i></a>
          <a href="https://www.instagram.com/chris_gaming445/" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="https://discord.gg/2suYxHesKD"             target="_blank" rel="noopener" aria-label="Discord"><i class="fab fa-discord"></i></a>
        </div>
      </div>

      <!-- Navigation -->
      <div class="col-6 col-lg-2 offset-lg-1">
        <h6 class="cg-footer-heading">MENÜ</h6>
        <ul class="cg-footer-list">
          <li><a href="index.php">Startseite</a></li>
          <li><a href="vorstellung.php">Vorstellung</a></li>
          <li><a href="cgprojekte.php">Meine Projekte</a></li>
          <li><a href="equipment.php">Equipment</a></li>
        </ul>
      </div>

      <!-- Socials -->
      <div class="col-6 col-lg-2">
        <h6 class="cg-footer-heading">SOCIAL MEDIA</h6>
        <ul class="cg-footer-list">
          <li><a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener">YouTube</a></li>
          <li><a href="https://www.twitch.tv/chris_gaming445"      target="_blank" rel="noopener">Twitch</a></li>
          <li><a href="https://www.instagram.com/chris_gaming445/" target="_blank" rel="noopener">Instagram</a></li>
          <li><a href="https://discord.gg/2suYxHesKD"             target="_blank" rel="noopener">Discord</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-12 col-lg-3">
        <h6 class="cg-footer-heading">KONTAKT</h6>
        <ul class="cg-footer-list">
          <li><a href="mailto:info@chrisgaming.at"><i class="fas fa-envelope me-2"></i>info@chrisgaming.at</a></li>
        </ul>
        <div class="mt-3">
          <h6 class="cg-footer-heading">RECHTLICHES</h6>
          <ul class="cg-footer-list">
            <li><a href="impressum.php">Impressum</a></li>
            <li><a href="datenschutz.php">Datenschutzerklärung</a></li>
          </ul>
        </div>
      </div>

    </div>

    <hr class="cg-footer-divider">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <p class="cg-footer-copy mb-0">&copy; <?= date('Y') ?> Chris_Gaming445. Alle Rechte vorbehalten.</p>
      <p class="cg-footer-copy mb-0 text-muted" style="font-size:.75rem;">Made with ❤️ in Tirol</p>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="assets/js/custom.js?v=<?= filemtime(__DIR__ . '/../assets/js/custom.js') ?>"></script>
</body>
</html>
