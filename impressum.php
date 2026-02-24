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

            <h2 style="margin-top:0;">Angaben gemäß § 5 ECG (E-Commerce-Gesetz)</h2>
            <p>
              Christian Tomeinschitz<br>
              Klammstraße 44<br>
              6020 Innsbruck<br>
              Österreich
            </p>

            <h2>Hinweis</h2>
            <p>
              Bei dieser Website handelt es sich um ein privates, nicht-kommerzielles Angebot
              einer Privatperson. Es werden keine gewerblichen Zwecke verfolgt.
            </p>

            <h2>Kontakt</h2>
            <p>
              Telefon: <a href="tel:+4366488865322">+43 664 88865322</a><br>
              E-Mail: <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>
            </p>

            <h2>EU-Streitschlichtung</h2>
            <p>
              Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
              <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener">
                https://ec.europa.eu/consumers/odr/
              </a><br>
              Unsere E-Mail-Adresse finden Sie oben im Impressum.
            </p>

            <h2>Verbraucherstreitbeilegung</h2>
            <p>
              Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer
              Verbraucherschlichtungsstelle teilzunehmen, da es sich um eine private,
              nicht-kommerzielle Website handelt.
            </p>

            <h2>Haftung für Inhalte</h2>
            <p>
              Als Diensteanbieter sind wir gemäß §§ 13 bis 17 ECG (E-Commerce-Gesetz) für eigene
              Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Als
              Diensteanbieter sind wir jedoch nicht verpflichtet, übermittelte oder gespeicherte
              fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine
              rechtswidrige Tätigkeit hinweisen.<br><br>
              Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den
              allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch
              erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei
              Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend
              entfernen.
            </p>

            <h2>Haftung für Links</h2>
            <p>
              Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen
              Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen.
              Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der
              Seiten verantwortlich. Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf
              mögliche Rechtsverstöße überprüft. Rechtswidrige Inhalte waren zum Zeitpunkt der
              Verlinkung nicht erkennbar. Eine permanente inhaltliche Kontrolle der verlinkten Seiten ist
              jedoch ohne konkrete Anhaltspunkte einer Rechtsverletzung nicht zumutbar. Bei Bekanntwerden
              von Rechtsverletzungen werden wir derartige Links umgehend entfernen.
            </p>

            <h2>Urheberrecht</h2>
            <p>
              Die durch den Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen
              dem österreichischen Urheberrecht (UrhG). Die Vervielfältigung, Bearbeitung, Verbreitung
              und jede Art der Verwertung außerhalb der Grenzen des Urheberrechts bedürfen der
              schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers. Downloads und Kopien
              dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet. Soweit
              die Inhalte auf dieser Seite nicht vom Betreiber erstellt wurden, werden die
              Urheberrechte Dritter beachtet. Sollten Sie trotzdem auf eine Urheberrechtsverletzung
              aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden von
              Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.
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
