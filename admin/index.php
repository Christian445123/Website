<?php
$pageTitle = 'Admin &ndash; Übersicht';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo = db();
$stats = ['users' => 0, 'active_users' => 0, 'projects' => 0, 'announcements' => 0];

if ($pdo !== null) {
    try {
        $stats['users']         = (int) $pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
        $stats['active_users']  = (int) $pdo->query('SELECT COUNT(*) FROM `users` WHERE `is_active`=1')->fetchColumn();
        $stats['projects']      = (int) $pdo->query('SELECT COUNT(*) FROM `projects` WHERE `is_active`=1')->fetchColumn();
        $stats['announcements'] = (int) $pdo->query('SELECT COUNT(*) FROM `announcements` WHERE `is_active`=1')->fetchColumn();
    } catch (Throwable) {}
}
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label" style="color:var(--cg-red);">Administrator</span>
      <h1 class="mt-2">Admin-Übersicht</h1>
      <p>Verwaltung von Benutzern, Projekten und Ankündigungen.</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">

      <!-- Stat Cards -->
      <div class="row gy-4 mb-5">
        <?php
        $statCards = [
            ['icon'=>'fas fa-users',    'color'=>'#339af0', 'bg'=>'rgba(51,154,240,.15)',  'value'=>$stats['users'],         'label'=>'Benutzer gesamt'],
            ['icon'=>'fas fa-user-check','color'=>'#37b24d','bg'=>'rgba(55,178,77,.15)',   'value'=>$stats['active_users'],  'label'=>'Aktive Benutzer'],
            ['icon'=>'fas fa-folder-open','color'=>'#9775fa','bg'=>'rgba(151,117,250,.15)','value'=>$stats['projects'],      'label'=>'Aktive Projekte'],
            ['icon'=>'fas fa-newspaper', 'color'=>'var(--cg-red)','bg'=>'var(--cg-red-subtle)', 'value'=>$stats['announcements'],'label'=>'Ankündigungen'],
        ];
        foreach ($statCards as $c):
        ?>
        <div class="col-6 col-lg-3">
          <div class="cg-card text-center">
            <div class="cg-feature-icon mx-auto mb-3" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;">
              <i class="<?= $c['icon'] ?>"></i>
            </div>
            <div style="font-size:2rem;font-weight:800;color:#e8e8ed;line-height:1;"><?= $c['value'] ?></div>
            <div style="font-size:.8rem;color:var(--cg-muted);margin-top:.35rem;"><?= $c['label'] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Quick Links -->
      <h4 class="mb-4" style="color:var(--cg-muted);font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;">Schnellzugriff</h4>
      <div class="row gy-4">
        <?php
        $links = [
            ['href'=>'/admin/users.php',         'icon'=>'fas fa-users',      'color'=>'#339af0','bg'=>'rgba(51,154,240,.15)', 'title'=>'Benutzerverwaltung',  'desc'=>'Benutzer verwalten, Rollen vergeben, Accounts aktivieren/sperren'],
            ['href'=>'/admin/announcements.php',  'icon'=>'fas fa-newspaper',  'color'=>'#e8351f','bg'=>'rgba(232,53,31,.15)',  'title'=>'Ankündigungen',       'desc'=>'Exklusive Neuigkeiten für Mitglieder erstellen und verwalten'],
            ['href'=>'/admin/projects.php',       'icon'=>'fas fa-folder-open','color'=>'#9775fa','bg'=>'rgba(151,117,250,.15)','title'=>'Projekte verwalten',  'desc'=>'Projektkarten auf der Projekte-Seite erstellen und bearbeiten'],
        ];
        foreach ($links as $l):
        ?>
        <div class="col-12 col-md-4">
          <a href="<?= $l['href'] ?>" class="cg-card d-block text-decoration-none h-100 admin-quick-card">
            <div class="cg-feature-icon mb-3" style="background:<?= $l['bg'] ?>;color:<?= $l['color'] ?>;">
              <i class="<?= $l['icon'] ?>"></i>
            </div>
            <h5 class="mb-2"><?= $l['title'] ?></h5>
            <p style="color:var(--cg-muted);font-size:.88rem;margin:0;"><?= $l['desc'] ?></p>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </section>
</main>

<style>
.admin-quick-card { transition: border-color .2s; }
.admin-quick-card:hover { border-color: var(--cg-red); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
