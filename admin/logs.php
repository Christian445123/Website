<?php
$noindex = true;
$pageTitle = 'Admin &ndash; Activity Log';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo = db();

// ── Filter & Pagination ──────────────────────────────────────────
$filterEvent = trim((string) ($_GET['event'] ?? ''));
$filterIp    = trim((string) ($_GET['ip']    ?? ''));
$page        = max(1, (int) ($_GET['page'] ?? 1));
$perPage     = 50;
$offset      = ($page - 1) * $perPage;

// Event-Kategorien für das Filter-Dropdown
$eventGroups = [
    'Auth'          => ['login.success','login.failed','login.unverified','login.inactive','login.throttled','logout','session.expired','account.password_changed'],
    'Registrierung' => ['register.success','register.email_taken','register.failed'],
    'Verifizierung' => ['verify.success','verify.failed','verify.expired','verify.already'],
    'Sicherheit'    => ['rate_limit.hit','access.denied'],
    'Admin Users'   => ['admin.user.activated','admin.user.deactivated','admin.user.role_changed','admin.user.deleted','admin.user.verified'],
    'Admin Projekte'=> ['admin.project.created','admin.project.updated','admin.project.deleted','admin.project.toggled'],
    'Admin Ankündig.'=> ['admin.announcement.created','admin.announcement.updated','admin.announcement.deleted','admin.announcement.toggled'],
];

$entries    = [];
$totalCount = 0;

if ($pdo !== null) {
    try {
        $where  = '1=1';
        $params = [];
        if ($filterEvent !== '') {
            $where   .= ' AND `event` = ?';
            $params[] = $filterEvent;
        }
        if ($filterIp !== '') {
            $where   .= ' AND `ip` LIKE ?';
            $params[] = '%' . $filterIp . '%';
        }

        // Re-count cleanly
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `activity_log` WHERE {$where}");
        $countStmt->execute($params);
        $totalCount = (int) $countStmt->fetchColumn();

        $listStmt = $pdo->prepare(
            "SELECT l.*, u.`username`
             FROM `activity_log` l
             LEFT JOIN `users` u ON u.`id` = l.`user_id`
             WHERE {$where}
             ORDER BY l.`id` DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $listStmt->execute($params);
        $entries = $listStmt->fetchAll();
    } catch (Throwable $e) {}
}

$totalPages = max(1, (int) ceil($totalCount / $perPage));

// Badge-Farbe per Event
function log_badge_class(string $event): string
{
    return match (true) {
        str_starts_with($event, 'rate_limit') || $event === 'access.denied'
            => 'danger',
        in_array($event, ['login.failed','login.inactive','login.unverified','session.expired',
                           'register.email_taken','register.failed','verify.failed','verify.expired',
                           'admin.user.deactivated','admin.project.deleted','admin.announcement.deleted',
                           'admin.user.deleted'], true)
            => 'warning',
        in_array($event, ['login.success','logout','verify.success','register.success'], true)
            => 'success',
        str_starts_with($event, 'admin.')
            => 'primary',
        default
            => 'secondary',
    };
}
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main id="main">
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label" style="color:var(--cg-red);">Administrator</span>
      <h1 class="mt-2">Activity Log</h1>
      <p><?= number_format($totalCount) ?> Einträge gesamt</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">

      <!-- Filter-Leiste -->
      <form method="GET" action="/admin/logs.php" class="row g-2 mb-4 align-items-end">
        <div class="col-md-4">
          <label class="form-label" style="color:#8b8b9a;font-size:.85rem;">Event-Typ</label>
          <select name="event" class="form-select form-select-sm" style="background:#1e1e22;color:#e8e8ed;border-color:rgba(255,255,255,.1)">
            <option value="">— Alle Events —</option>
            <?php foreach ($eventGroups as $group => $events): ?>
              <optgroup label="<?= e($group) ?>">
                <?php foreach ($events as $ev): ?>
                  <option value="<?= e($ev) ?>" <?= $filterEvent === $ev ? 'selected' : '' ?>>
                    <?= e($ev) ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" style="color:#8b8b9a;font-size:.85rem;">IP-Adresse</label>
          <input type="text" name="ip" class="form-control form-control-sm"
                 placeholder="z.B. 192.168." value="<?= e($filterIp) ?>"
                 style="background:#1e1e22;color:#e8e8ed;border-color:rgba(255,255,255,.1)">
        </div>
        <div class="col-md-auto d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-danger">
            <i class="fas fa-filter me-1"></i>Filtern
          </button>
          <a href="/admin/logs.php" class="btn btn-sm" style="background:rgba(255,255,255,.07);color:#e8e8ed">
            <i class="fas fa-times me-1"></i>Zurücksetzen
          </a>
        </div>
      </form>

      <!-- Tabelle -->
      <div style="background:#161619;border:1px solid rgba(255,255,255,.07);border-radius:.75rem;overflow:hidden">
        <div style="overflow-x:auto">
          <table class="table table-dark table-hover mb-0" style="font-size:.85rem;--bs-table-bg:#161619;--bs-table-hover-bg:rgba(255,255,255,.04)">
            <thead>
              <tr style="border-bottom:1px solid rgba(255,255,255,.1)">
                <th style="padding:.75rem 1rem;color:#8b8b9a;font-weight:500;white-space:nowrap">Zeitpunkt</th>
                <th style="padding:.75rem 1rem;color:#8b8b9a;font-weight:500">Event</th>
                <th style="padding:.75rem 1rem;color:#8b8b9a;font-weight:500">IP-Adresse</th>
                <th style="padding:.75rem 1rem;color:#8b8b9a;font-weight:500">Benutzer</th>
                <th style="padding:.75rem 1rem;color:#8b8b9a;font-weight:500">Kontext</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($entries) === 0): ?>
              <tr>
                <td colspan="5" class="text-center py-5" style="color:#8b8b9a">
                  <i class="fas fa-scroll fa-2x mb-3 d-block"></i>
                  Keine Einträge gefunden.
                </td>
              </tr>
              <?php else: ?>
              <?php foreach ($entries as $e): ?>
              <tr>
                <td style="padding:.65rem 1rem;color:#8b8b9a;white-space:nowrap">
                  <?= e(date('d.m.Y H:i:s', strtotime($e['created_at']))) ?>
                </td>
                <td style="padding:.65rem 1rem">
                  <span class="badge bg-<?= log_badge_class($e['event']) ?>"
                        style="font-size:.75rem;font-weight:500">
                    <?= e($e['event']) ?>
                  </span>
                </td>
                <td style="padding:.65rem 1rem">
                  <code style="color:#a9b1d6;background:rgba(255,255,255,.05);
                               padding:.15rem .4rem;border-radius:.25rem;font-size:.8rem">
                    <?= e($e['ip']) ?>
                  </code>
                </td>
                <td style="padding:.65rem 1rem">
                  <?php if ($e['username']): ?>
                    <span style="color:#e8e8ed">
                      <i class="fas fa-user-circle me-1" style="color:var(--cg-red);font-size:.8rem"></i>
                      <?= e($e['username']) ?>
                      <span style="color:#555;font-size:.75rem"> #<?= (int) $e['user_id'] ?></span>
                    </span>
                  <?php else: ?>
                    <span style="color:#555">—</span>
                  <?php endif; ?>
                </td>
                <td style="padding:.65rem 1rem">
                  <?php if ($e['context']): ?>
                    <?php $ctx = json_decode($e['context'], true); ?>
                    <?php if (is_array($ctx)): ?>
                      <span style="font-size:.78rem;color:#8b8b9a">
                        <?php foreach ($ctx as $k => $v): ?>
                          <span style="background:rgba(255,255,255,.05);border-radius:.25rem;
                                       padding:.1rem .35rem;margin-right:.25rem;display:inline-block;
                                       margin-bottom:.15rem">
                            <span style="color:#555"><?= e($k) ?>:</span>
                            <span style="color:#a9b1d6"><?= e(is_array($v) ? json_encode($v) : (string)$v) ?></span>
                          </span>
                        <?php endforeach; ?>
                      </span>
                    <?php endif; ?>
                  <?php else: ?>
                    <span style="color:#555">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="d-flex align-items-center justify-content-between px-3 py-2"
             style="border-top:1px solid rgba(255,255,255,.07)">
          <span style="color:#555;font-size:.8rem">
            Seite <?= $page ?> / <?= $totalPages ?>
            &nbsp;·&nbsp; <?= number_format($totalCount) ?> Einträge
          </span>
          <div class="d-flex gap-1">
            <?php if ($page > 1): ?>
              <a href="?event=<?= urlencode($filterEvent) ?>&ip=<?= urlencode($filterIp) ?>&page=<?= $page - 1 ?>"
                 class="btn btn-sm" style="background:rgba(255,255,255,.07);color:#e8e8ed">
                <i class="fas fa-chevron-left"></i>
              </a>
            <?php endif; ?>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
              <a href="?event=<?= urlencode($filterEvent) ?>&ip=<?= urlencode($filterIp) ?>&page=<?= $i ?>"
                 class="btn btn-sm <?= $i === $page ? 'btn-danger' : '' ?>"
                 style="<?= $i !== $page ? 'background:rgba(255,255,255,.07);color:#e8e8ed' : '' ?>">
                <?= $i ?>
              </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?event=<?= urlencode($filterEvent) ?>&ip=<?= urlencode($filterIp) ?>&page=<?= $page + 1 ?>"
                 class="btn btn-sm" style="background:rgba(255,255,255,.07);color:#e8e8ed">
                <i class="fas fa-chevron-right"></i>
              </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
