<?php
$pageTitle = 'Admin &ndash; Übersicht';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo = db();
$stats = [
    'users' => 0, 'active_users' => 0, 'projects' => 0, 'announcements' => 0,
    'logins_today' => 0, 'failed_today' => 0, 'rate_hits_today' => 0,
];
$recentLogs = [];

if ($pdo !== null) {
    try {
        $stats['users']         = (int) $pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
        $stats['active_users']  = (int) $pdo->query('SELECT COUNT(*) FROM `users` WHERE `is_active`=1')->fetchColumn();
        $stats['projects']      = (int) $pdo->query('SELECT COUNT(*) FROM `projects` WHERE `is_active`=1')->fetchColumn();
        $stats['announcements'] = (int) $pdo->query('SELECT COUNT(*) FROM `announcements` WHERE `is_active`=1')->fetchColumn();
        // Heute-Stats aus Activity Log
        $today = date('Y-m-d');
        $stats['logins_today']  = (int) $pdo->prepare("SELECT COUNT(*) FROM `activity_log` WHERE `event`='login.success' AND DATE(`created_at`)=?")->execute([$today]) ? $pdo->prepare("SELECT COUNT(*) FROM `activity_log` WHERE `event`='login.success' AND DATE(`created_at`)=?")->execute([$today]) || 0 : 0;
        $q = $pdo->prepare("SELECT COUNT(*) FROM `activity_log` WHERE `event`=? AND DATE(`created_at`)=?");
        $q->execute(['login.success',  $today]); $stats['logins_today']   = (int) $q->fetchColumn();
        $q->execute(['login.failed',   $today]); $stats['failed_today']   = (int) $q->fetchColumn();
        $q->execute(['rate_limit.hit', $today]); $stats['rate_hits_today']= (int) $q->fetchColumn();
        // Letzte 8 Log-Einträge
        $recentLogs = $pdo->query(
            "SELECT l.`event`, l.`ip`, l.`created_at`, u.`username`
             FROM `activity_log` l
             LEFT JOIN `users` u ON u.`id` = l.`user_id`
             ORDER BY l.`id` DESC LIMIT 8"
        )->fetchAll();
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
            ['icon'=>'fas fa-users',     'color'=>'#339af0', 'bg'=>'rgba(51,154,240,.15)',   'value'=>$stats['users'],          'label'=>'Benutzer gesamt'],
            ['icon'=>'fas fa-user-check','color'=>'#37b24d', 'bg'=>'rgba(55,178,77,.15)',    'value'=>$stats['active_users'],   'label'=>'Aktive Benutzer'],
            ['icon'=>'fas fa-folder-open','color'=>'#9775fa','bg'=>'rgba(151,117,250,.15)',  'value'=>$stats['projects'],       'label'=>'Aktive Projekte'],
            ['icon'=>'fas fa-newspaper', 'color'=>'var(--cg-red)','bg'=>'var(--cg-red-subtle)','value'=>$stats['announcements'],'label'=>'Ankündigungen'],
            ['icon'=>'fas fa-right-to-bracket','color'=>'#37b24d','bg'=>'rgba(55,178,77,.12)','value'=>$stats['logins_today'],   'label'=>'Logins heute'],
            ['icon'=>'fas fa-triangle-exclamation','color'=>'#fab005','bg'=>'rgba(250,176,5,.12)','value'=>$stats['failed_today'],'label'=>'Fehlgeschl. Logins heute'],
            ['icon'=>'fas fa-ban',       'color'=>'#e03131','bg'=>'rgba(224,49,49,.12)',     'value'=>$stats['rate_hits_today'],'label'=>'Rate-Limit-Treffer heute'],
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
      <div class="row gy-4 mb-5">
        <?php
        $links = [
            ['href'=>'/admin/users.php',        'icon'=>'fas fa-users',      'color'=>'#339af0','bg'=>'rgba(51,154,240,.15)', 'title'=>'Benutzerverwaltung', 'desc'=>'Benutzer verwalten, Rollen vergeben, Accounts aktivieren/sperren'],
            ['href'=>'/admin/announcements.php', 'icon'=>'fas fa-newspaper',  'color'=>'#e8351f','bg'=>'rgba(232,53,31,.15)', 'title'=>'Ankündigungen',      'desc'=>'Exklusive Neuigkeiten für Mitglieder erstellen und verwalten'],
            ['href'=>'/admin/projects.php',      'icon'=>'fas fa-folder-open','color'=>'#9775fa','bg'=>'rgba(151,117,250,.15)','title'=>'Projekte verwalten', 'desc'=>'Projektkarten auf der Projekte-Seite erstellen und bearbeiten'],
            ['href'=>'/admin/logs.php',          'icon'=>'fas fa-scroll',     'color'=>'#20c997','bg'=>'rgba(32,201,151,.15)', 'title'=>'Activity Log',       'desc'=>'Alle Systemereignisse, Logins, Admin-Aktionen und Sicherheitswarnungen'],
        ];
        foreach ($links as $l):
        ?>
        <div class="col-12 col-md-6 col-lg-3">
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

      <!-- Letzte Log-Einträge -->
      <?php if (count($recentLogs) > 0): ?>
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 style="color:var(--cg-muted);font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;margin:0;">Letzte Aktivitäten</h4>
        <a href="/admin/logs.php" style="font-size:.85rem;color:var(--cg-red);text-decoration:none">
          Alle anzeigen <i class="fas fa-arrow-right ms-1"></i>
        </a>
      </div>
      <div style="background:#161619;border:1px solid rgba(255,255,255,.07);border-radius:.75rem;overflow:hidden">
        <table class="table table-dark mb-0" style="font-size:.83rem;--bs-table-bg:#161619">
          <thead><tr style="border-bottom:1px solid rgba(255,255,255,.1)">
            <th style="padding:.6rem 1rem;color:#8b8b9a;font-weight:500">Zeit</th>
            <th style="padding:.6rem 1rem;color:#8b8b9a;font-weight:500">Event</th>
            <th style="padding:.6rem 1rem;color:#8b8b9a;font-weight:500">IP</th>
            <th style="padding:.6rem 1rem;color:#8b8b9a;font-weight:500">Benutzer</th>
          </tr></thead>
          <tbody>
          <?php foreach ($recentLogs as $log): ?>
          <?php
          $badgeClass = match(true) {
              in_array($log['event'], ['rate_limit.hit','access.denied','admin.user.deleted','admin.project.deleted','admin.announcement.deleted'], true) => 'danger',
              in_array($log['event'], ['login.failed','login.inactive','login.unverified'], true) => 'warning',
              $log['event'] === 'login.success' => 'success',
              str_starts_with($log['event'], 'admin.') => 'primary',
              default => 'secondary',
          };
          ?>
          <tr>
            <td style="padding:.55rem 1rem;color:#8b8b9a;white-space:nowrap">
              <?= htmlspecialchars(date('d.m. H:i:s', strtotime($log['created_at']))) ?>
            </td>
            <td style="padding:.55rem 1rem">
              <span class="badge bg-<?= $badgeClass ?>" style="font-size:.73rem">
                <?= htmlspecialchars($log['event']) ?>
              </span>
            </td>
            <td style="padding:.55rem 1rem">
              <code style="color:#a9b1d6;background:rgba(255,255,255,.05);padding:.1rem .3rem;border-radius:.2rem;font-size:.78rem">
                <?= htmlspecialchars($log['ip']) ?>
              </code>
            </td>
            <td style="padding:.55rem 1rem;color:#e8e8ed">
              <?= $log['username'] ? htmlspecialchars($log['username']) : '<span style="color:#555">—</span>' ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<style>
.admin-quick-card { transition: border-color .2s; }
.admin-quick-card:hover { border-color: var(--cg-red); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
