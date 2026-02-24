<?php
$pageTitle       = 'Impressum';
$pageDescription = 'Impressum der Website Chris_Gaming445 gemäß § 5 ECG (E-Commerce-Gesetz).';
require_once __DIR__ . '/includes/head.php';
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Rechtliches</span>
      <h1 class="mt-2">Impressum</h1>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="cg-card cg-legal-content">

            <h2 style="margin-top:0;">Angaben gemäß § 5 ECG</h2>
            <p>
              Christian Tomeinschitz<br>
              Klammstraße 44<br>
              6020 Innsbruck<br>
              Österreich
            </p>

            <h2>Art der Website</h2>
            <p>
              Privates, nicht-kommerzielles Angebot einer Privatperson ohne gewerbliche Zwecke.
            </p>

            <h2>Kontakt</h2>
            <p>
              Telefon: <a href="tel:+4366488865322">+43 664 88865322</a><br>
              E-Mail: <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>
            </p>

            <h2>Hosting</h2>
            <p>
              Diese Website wird gehostet von:<br>
              <strong>Living Bots</strong>
            </p>

            <h2>EU-Streitschlichtung</h2>
            <p>
              Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
              <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener">ec.europa.eu/consumers/odr</a>.<br>
              Da es sich um eine private, nicht-kommerzielle Website handelt, nehmen wir nicht
              an Streitbeilegungsverfahren teil.
            </p>

            <h2>Haftung für Inhalte</h2>
            <p>
              Als Diensteanbieter sind wir gemäß §§ 13–17 ECG für eigene Inhalte nach den
              allgemeinen Gesetzen verantwortlich. Wir sind nicht verpflichtet, übermittelte
              oder gespeicherte fremde Informationen zu überwachen. Bei Bekanntwerden von
              Rechtsverletzungen entfernen wir betreffende Inhalte umgehend.
            </p>

            <h2>Haftung für Links</h2>
            <p>
              Unser Angebot enthält Links zu externen Websites. Für deren Inhalte sind die
              jeweiligen Betreiber verantwortlich. Die verlinkten Seiten wurden zum Zeitpunkt
              der Verlinkung auf Rechtsverstöße geprüft; eine permanente Kontrolle ist ohne
              konkreten Hinweis nicht zumutbar.
            </p>

            <h2>Urheberrecht</h2>
            <p>
              Selbst erstellte Inhalte unterliegen dem österreichischen Urheberrecht (UrhG).
              Die Vervielfältigung oder Verwendung außerhalb des Urheberrechts bedarf der
              schriftlichen Zustimmung des Betreibers.
            </p>

            <p class="cg-legal-note">
              Stand: <?= date('d.m.Y') ?> &nbsp;·&nbsp;
              Rechtsgrundlage: ECG (BGBl. I Nr. 152/2001 idgF), DSGVO, DSG 2018, UrhG
            </p>

          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
