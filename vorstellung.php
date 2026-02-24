<?php
$pageTitle       = 'Vorstellung';
$pageDescription = 'Lerne Christian aka Chris_Gaming445 kennen – Streamer, Gamer und IT-Systemtechniker aus Tirol.';
require_once __DIR__ . '/includes/head.php';
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Über mich</span>
      <h1 class="mt-2">Vorstellung</h1>
      <p>Hallo! Ich bin Christian – hier ist ein bisschen mehr über mich.</p>
    </div>
  </section>

  <!-- About Content -->
  <section class="cg-section">
    <div class="container">
      <div class="row gy-5 align-items-start">

        <!-- Quick Facts -->
        <div class="col-12 col-lg-4">
          <div class="cg-card" data-cg-animate="animate__fadeInLeft">
            <h5 class="mb-4" style="color:var(--cg-red);">
              <i class="fas fa-id-card me-2"></i>Quick Facts
            </h5>
            <ul class="list-unstyled mb-0" style="display:flex;flex-direction:column;gap:1rem;">
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;">
                  <i class="fas fa-user"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Name</div>
                  <div style="font-weight:600;">Christian</div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;">
                  <i class="fas fa-mountain-sun"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Herkunft</div>
                  <div style="font-weight:600;">Tirol, Österreich</div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;">
                  <i class="fas fa-briefcase"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Beruf</div>
                  <div style="font-weight:600;">IT-Systemtechniker</div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;">
                  <i class="fas fa-tools"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Ausbildung</div>
                  <div style="font-weight:600;">Mechatroniker (abgeschl.)<br>IT-Systemtechniker</div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;">
                  <i class="fas fa-gamepad"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Hobbys</div>
                  <div style="font-weight:600;">Streamen, Programmieren, Gaming</div>
                </div>
              </li>
              <li class="d-flex align-items-start gap-3">
                <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:1rem;border-radius:.5rem;background:rgba(88,101,242,.15);color:#7289da;">
                  <i class="fab fa-twitch"></i>
                </span>
                <div>
                  <div style="font-size:.75rem;color:var(--cg-muted);text-transform:uppercase;letter-spacing:.08em;">Streaming seit</div>
                  <div style="font-weight:600;">~2 Jahren</div>
                </div>
              </li>
            </ul>
          </div>

          <!-- Social links -->
          <div class="cg-card mt-4" data-cg-animate="animate__fadeInLeft" data-cg-delay=".1s">
            <h6 style="color:var(--cg-muted);font-size:.75rem;text-transform:uppercase;letter-spacing:.1em;margin-bottom:1rem;">Meine Kanäle</h6>
            <div class="d-flex flex-column gap-2">
              <a href="https://www.youtube.com/@chris_gaming4452" target="_blank" rel="noopener"
                 class="cg-btn cg-btn-outline" style="border-color:#ff4444;color:#ff4444;">
                <i class="fab fa-youtube"></i>YouTube
              </a>
              <a href="https://www.twitch.tv/chris_gaming445" target="_blank" rel="noopener"
                 class="cg-btn cg-btn-outline" style="border-color:#9147ff;color:#9147ff;">
                <i class="fab fa-twitch"></i>Twitch
              </a>
              <a href="https://www.instagram.com/chris_gaming445/" target="_blank" rel="noopener"
                 class="cg-btn cg-btn-outline" style="border-color:#e1306c;color:#e1306c;">
                <i class="fab fa-instagram"></i>Instagram
              </a>
              <a href="https://discord.gg/2suYxHesKD" target="_blank" rel="noopener"
                 class="cg-btn-discord">
                <i class="fab fa-discord"></i>Discord
              </a>
            </div>
          </div>
        </div>

        <!-- Text -->
        <div class="col-12 col-lg-8" data-cg-animate="animate__fadeInRight">
          <div class="cg-about-text">
            <span class="cg-section-label">Meine Geschichte</span>
            <h2 class="mt-2 mb-4">Hallo, ich bin Christian!</h2>
            <p>
              Hallo zusammen,<br><br>
              Ich heiße Christian, bin 21 Jahre alt und komme aus dem malerischen Tirol.
              Schon seit meiner Kindheit habe ich eine Leidenschaft für Technologie und Computerspiele
              entwickelt, die mich bis heute begleitet.
            </p>
            <p>
              Zu meinen Hauptinteressen zählen die IT und PC-Spiele. Diese Interessen haben mich dazu
              gebracht, meine Freizeit mit Aktivitäten zu füllen, die sowohl kreativ als auch technisch
              anspruchsvoll sind. Meine Hobbys umfassen das Streamen, Programmieren und Entwickeln.
              Es bereitet mir große Freude, meine Fähigkeiten in diesen Bereichen stetig zu verbessern
              und neue Herausforderungen anzunehmen.
            </p>
            <p>
              Ich habe bereits eine Lehre als Mechatroniker erfolgreich abgeschlossen und befinde mich
              derzeit in einer Ausbildung zum IT-Systemtechniker. Diese Ausbildung eröffnet mir viele
              spannende Möglichkeiten, tiefer in die Welt der Informationstechnologie einzutauchen und
              mein Wissen weiter zu vertiefen.
            </p>

            <hr class="cg-divider">

            <p>
              Neben meiner Ausbildung habe ich das Streamen für mich entdeckt und betreibe es nun seit
              etwa zwei Jahren mit großer Begeisterung. Das Streamen ermöglicht es mir, meine Leidenschaft
              für Spiele und Technik mit anderen zu teilen und gleichzeitig eine tolle Community aufzubauen.
              Ich freue mich immer sehr über neue Zuschauer und hoffe, dass ihr genauso viel Spaß an
              meinen Streams habt wie ich.
            </p>
            <p>
              Falls ihr Lust habt, mehr von meinen Aktivitäten mitzubekommen und Teil meiner Community
              zu werden, schaut doch gerne auf meinem Discord-Server vorbei. Ich freue mich über jeden
              neuen Besucher.
            </p>
            <p>
              Ich wünsche euch viel Spaß bei meinen Streams und hoffe, dass ihr euch gut unterhalten fühlt.
            </p>
            <p style="font-weight:700;color:var(--cg-white);">Herzliche Grüße,<br>Chris_Gaming445</p>

            <div class="d-flex flex-wrap gap-3 mt-4">
              <a href="cgprojekte.php" class="cg-btn cg-btn-primary">
                <i class="fas fa-folder-open"></i>Meine Projekte
              </a>
              <a href="equipment.php" class="cg-btn cg-btn-outline">
                <i class="fas fa-desktop"></i>Mein Equipment
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
