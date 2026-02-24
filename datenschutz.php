<?php
$pageTitle       = 'DatenschutzerklÃ¤rung';
$pageDescription = 'DatenschutzerklÃ¤rung der Website Chris_Gaming445.';
require_once __DIR__ . '/includes/head.php';
?>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <!-- Page Hero -->
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Rechtliches</span>
      <h1 class="mt-2">DatenschutzerklÃ¤rung</h1>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="cg-card cg-legal-content">

            <h2 style="margin-top:0;">1. Verantwortliche Stelle</h2>
            <p>
              Christian Tomeinschitz<br>
              KlammstraÃŸe 44, 6020 Innsbruck<br>
              E-Mail: <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>
            </p>

            <h2>2. Hosting</h2>
            <p>
              Diese Website wird bei <strong>Living Bots</strong> gehostet. Bei jedem Seitenbesuch
              werden serverseitig technische Daten erfasst (IP-Adresse, Browser, Betriebssystem,
              Zeitpunkt des Zugriffs). Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes
              Interesse am sicheren Betrieb).
            </p>

            <h2>3. Cookies &amp; Session</h2>
            <p>
              Diese Website setzt ausschlieÃŸlich technisch notwendige Session-Cookies ein, die
              nach dem SchlieÃŸen des Browsers automatisch gelÃ¶scht werden. Sie dienen dem Betrieb
              des Login-Systems und erfordern keine Einwilligung (Art. 6 Abs. 1 lit. b DSGVO).
            </p>

            <h2>4. Registrierung &amp; Login</h2>
            <p>
              Wenn Sie sich auf dieser Website registrieren, werden folgende Daten gespeichert:
              Benutzername, E-Mail-Adresse sowie ein sicheres Passwort-Hash (bcrypt). Diese Daten
              werden ausschlieÃŸlich fÃ¼r den Betrieb des Benutzerkontos verwendet. Rechtsgrundlage:
              Art. 6 Abs. 1 lit. b DSGVO. Auf Anfrage kÃ¶nnen Ihr Konto und alle zugehÃ¶rigen Daten
              gelÃ¶scht werden.
            </p>

            <h2>5. Google Analytics</h2>
            <p>
              Diese Website nutzt Google Analytics, einen Webanalysedienst der Google Ireland
              Limited, Gordon House, Barrow Street, Dublin 4, Irland. Google Analytics setzt
              Cookies ein, die eine Analyse der Benutzung der Website ermÃ¶glichen. Die dadurch
              erzeugten Informationen Ã¼ber Ihre Nutzung der Website werden Ã¼blicherweise an einen
              Server von Google in den USA Ã¼bertragen und dort gespeichert.<br><br>
              Grundlage ist Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Analyse
              des Nutzungsverhaltens zur Optimierung des Angebots).<br><br>
              Sie kÃ¶nnen die Erfassung durch Google Analytics verhindern, indem Sie ein Browser-Add-on
              installieren: <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">tools.google.com/dlpage/gaoptout</a>.<br>
              DatenschutzerklÃ¤rung Google: <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">policies.google.com/privacy</a>
            </p>

            <h2>6. Ihre Rechte</h2>
            <p>
              Sie haben das Recht auf Auskunft, Berichtigung, LÃ¶schung, EinschrÃ¤nkung der
              Verarbeitung sowie DatenÃ¼bertragbarkeit. AuÃŸerdem steht Ihnen ein Widerspruchsrecht
              und ein Beschwerderecht bei der Ã¶sterreichischen DatenschutzbehÃ¶rde
              (<a href="https://www.dsb.gv.at" target="_blank" rel="noopener">www.dsb.gv.at</a>) zu.
              Eine vollstÃ¤ndige Ãœbersicht finden Sie auf der
              <a href="dsgvo.php">DSGVO-Rechte-Seite</a>.
            </p>

            <h2>7. Externe Links</h2>
            <p>
              Diese Website enthÃ¤lt Links zu Drittanbieter-Diensten (YouTube, Twitch, Discord,
              Instagram, Amazon). FÃ¼r die Datenschutzpraktiken dieser Anbieter sind die jeweiligen
              Anbieter selbst verantwortlich.
            </p>

            <p class="cg-legal-note">
              Stand: <?= date('d.m.Y') ?> &nbsp;&middot;&nbsp;
              Rechtsgrundlage: DSGVO (EU) 2016/679, DSG 2018, ECG, TKG 2021
            </p>

          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
