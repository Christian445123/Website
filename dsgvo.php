<?php
$pageTitle       = 'Ihre DSGVO-Rechte';
$pageDescription = 'Übersicht Ihrer Rechte gemäß DSGVO – Chris_Gaming445.';
require_once __DIR__ . '/includes/head.php';
?>
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main>

  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Rechtliches</span>
      <h1 class="mt-2">Ihre DSGVO-Rechte</h1>
      <p>Übersicht Ihrer Rechte gemäß Datenschutz-Grundverordnung (DSGVO).</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="cg-card cg-legal-content">

            <h2 style="margin-top:0;">Ihre Rechte auf einen Blick</h2>
            <p>
              Als betroffene Person haben Sie nach der DSGVO folgende Rechte gegenüber dem
              Verantwortlichen dieser Website. Alle Anfragen können per E-Mail an
              <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a> gestellt werden.
            </p>

            <div class="accordion cg-accordion mb-4" id="dsgvoAccordion">

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r1" aria-expanded="false">
                    <i class="fas fa-eye me-2" style="color:var(--cg-red);"></i>
                    Auskunftsrecht (Art. 15 DSGVO)
                  </button>
                </h2>
                <div id="r1" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, jederzeit eine unentgeltliche Auskunft über die zu Ihrer
                    Person gespeicherten personenbezogenen Daten, deren Herkunft, Empfänger sowie
                    den Zweck der Datenverarbeitung zu erhalten.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r2" aria-expanded="false">
                    <i class="fas fa-pen-to-square me-2" style="color:var(--cg-red);"></i>
                    Recht auf Berichtigung (Art. 16 DSGVO)
                  </button>
                </h2>
                <div id="r2" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, die unverzügliche Berichtigung unrichtiger oder
                    Vervollständigung unvollständiger personenbezogener Daten zu verlangen.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r3" aria-expanded="false">
                    <i class="fas fa-trash me-2" style="color:var(--cg-red);"></i>
                    Recht auf Löschung (Art. 17 DSGVO)
                  </button>
                </h2>
                <div id="r3" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, die Löschung Ihrer personenbezogenen Daten zu verlangen
                    (sog. „Recht auf Vergessenwerden"), sofern die Daten nicht mehr für die Zwecke
                    notwendig sind, für die sie erhoben wurden, oder die Verarbeitung unrechtmäßig
                    erfolgte.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r4" aria-expanded="false">
                    <i class="fas fa-pause me-2" style="color:var(--cg-red);"></i>
                    Recht auf Einschränkung der Verarbeitung (Art. 18 DSGVO)
                  </button>
                </h2>
                <div id="r4" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, die Einschränkung der Verarbeitung Ihrer personenbezogenen
                    Daten zu verlangen, wenn Sie die Richtigkeit der Daten bestreiten, die Verarbeitung
                    unrechtmäßig ist oder Sie Widerspruch gegen die Verarbeitung eingelegt haben.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r5" aria-expanded="false">
                    <i class="fas fa-file-export me-2" style="color:var(--cg-red);"></i>
                    Recht auf Datenübertragbarkeit (Art. 20 DSGVO)
                  </button>
                </h2>
                <div id="r5" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, Daten, die wir auf Grundlage Ihrer Einwilligung oder zur
                    Vertragserfüllung automatisiert verarbeiten, in einem gängigen, maschinenlesbaren
                    Format zu erhalten oder die direkte Übertragung an einen anderen Verantwortlichen
                    zu verlangen.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r6" aria-expanded="false">
                    <i class="fas fa-hand me-2" style="color:var(--cg-red);"></i>
                    Widerspruchsrecht (Art. 21 DSGVO)
                  </button>
                </h2>
                <div id="r6" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie haben das Recht, aus Gründen, die sich aus Ihrer besonderen Situation ergeben,
                    jederzeit Widerspruch gegen die Verarbeitung Ihrer personenbezogenen Daten einzulegen,
                    sofern diese auf Art. 6 Abs. 1 lit. e oder f DSGVO beruht.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r7" aria-expanded="false">
                    <i class="fas fa-undo me-2" style="color:var(--cg-red);"></i>
                    Widerruf einer Einwilligung
                  </button>
                </h2>
                <div id="r7" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Sie können eine erteilte Einwilligung zur Datenverarbeitung jederzeit mit Wirkung
                    für die Zukunft widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten
                    Verarbeitung bleibt unberührt.
                  </div>
                </div>
              </div>

              <div class="accordion-item">
                <h2 class="accordion-header">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse" data-bs-target="#r8" aria-expanded="false">
                    <i class="fas fa-flag me-2" style="color:var(--cg-red);"></i>
                    Beschwerderecht bei der Aufsichtsbehörde
                  </button>
                </h2>
                <div id="r8" class="accordion-collapse collapse" data-bs-parent="#dsgvoAccordion">
                  <div class="accordion-body">
                    Im Falle von DSGVO-Verstößen haben Sie das Recht, eine Beschwerde bei der
                    zuständigen Datenschutzbehörde einzureichen. In Österreich ist dies:<br><br>
                    <strong>Österreichische Datenschutzbehörde</strong><br>
                    Barichgasse 40–42, 1030 Wien<br>
                    Tel.: +43 1 52 152-0<br>
                    E-Mail: <a href="mailto:dsb@dsb.gv.at">dsb@dsb.gv.at</a><br>
                    Web: <a href="https://www.dsb.gv.at" target="_blank" rel="noopener">www.dsb.gv.at</a>
                  </div>
                </div>
              </div>

            </div>

            <h2>Verantwortliche Stelle</h2>
            <p>
              Christian Tomeinschitz &nbsp;·&nbsp; Klammstraße 44, 6020 Innsbruck<br>
              E-Mail: <a href="mailto:info@chrisgaming.at">info@chrisgaming.at</a>
            </p>

            <p class="cg-legal-note">
              Stand: <?= date('d.m.Y') ?> &nbsp;·&nbsp;
              Rechtsgrundlage: DSGVO (EU) 2016/679, DSG 2018
            </p>

          </div>
        </div>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
