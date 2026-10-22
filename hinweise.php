<?php
$pageTitle       = 'Allgemeine Hinweise';
$pageDescription = 'Allgemeine Hinweise zur Website von Chris_Gaming445.';
require_once __DIR__ . '/includes/head.php';
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main id="main">

  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Rechtliches</span>
      <h1 class="mt-2">Allgemeine Hinweise</h1>
      <p>Wichtige Hinweise zur Nutzung dieser Website.</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="cg-card cg-legal-content">

            <h2 style="margin-top:0;">Über diese Website</h2>
            <p>
              Diese Website ist ein privates, nicht-kommerzielles Angebot von Christian Tomeinschitz
              (Chris_Gaming445). Sie dient der Selbstdarstellung und dem Austausch mit der Community
              rund um Gaming, Streaming und IT.
            </p>

            <h2>Kein kommerzielles Angebot</h2>
            <p>
              Alle Inhalte dieser Website werden ohne gewerbliche Absicht bereitgestellt. Es werden
              keine Produkte oder Dienstleistungen verkauft. Affiliate-Links (z. B. auf der
              Equipment-Seite) werden als solche gekennzeichnet.
            </p>

            <h2>Verfügbarkeit</h2>
            <p>
              Der Betreiber bemüht sich um eine kontinuierliche Verfügbarkeit der Website, übernimmt
              jedoch keine Garantie für unterbrechungsfreien Betrieb. Wartungsarbeiten oder technische
              Störungen können zu vorübergehenden Ausfällen führen.
            </p>

            <h2>Externe Links</h2>
            <p>
              Diese Website enthält Links zu externen Inhalten (z. B. YouTube, Twitch, Discord,
              Amazon). Für die Inhalte dieser verlinkten Seiten ist ausschließlich der jeweilige
              Anbieter verantwortlich. Zum Zeitpunkt der Verlinkung waren keine rechtswidrigen Inhalte
              erkennbar; eine laufende Überprüfung ist ohne konkreten Hinweis nicht zumutbar.
            </p>

            <h2>Urheberrecht</h2>
            <p>
              Alle selbst erstellten Inhalte (Texte, Grafiken, Logos) unterliegen dem österreichischen
              Urheberrecht (UrhG). Eine Vervielfältigung oder Verwendung ohne ausdrückliche Zustimmung
              ist nicht gestattet. Werden fremde Inhalte verwendet, werden diese entsprechend
              gekennzeichnet.
            </p>

            <h2>Technische Hinweise</h2>
            <p>
              Diese Website setzt technisch notwendige Session-Cookies ein, die nach dem Schließen
              des Browsers gelöscht werden. Darüber hinaus wird Google Analytics zur anonymen
              Analyse des Nutzungsverhaltens nur nach Ihrer Einwilligung (Cookie-Hinweis) eingesetzt. Weitere Details finden Sie in der
              <a href="datenschutz.php">Datenschutzerklärung</a>.
            </p>

            <h2>Kontakt</h2>
            <p>
              Bei Fragen oder Anmerkungen können Sie uns jederzeit erreichen:<br>
              E-Mail: <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>
            </p>

            <p class="cg-legal-note">
              Stand: <?= date('d.m.Y') ?> &nbsp;·&nbsp;
              Weitere rechtliche Informationen: <a href="impressum.php">Impressum</a> &nbsp;·&nbsp;
              <a href="datenschutz.php">Datenschutzerklärung</a> &nbsp;·&nbsp;
              <a href="dsgvo.php">Ihre DSGVO-Rechte</a>
            </p>

          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
