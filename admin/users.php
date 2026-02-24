<?php
$pageTitle = 'Admin &ndash; Benutzerverwaltung';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo     = db();
$message = '';
$msgType = 'success';

// ── POST actions ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = (string) ($_POST['action'] ?? '');
    $uid    = (int)    ($_POST['uid']    ?? 0);
    $me     = auth_user()['id'];

    try {
        if ($uid === $me) {
            $message = 'Du kannst deinen eigenen Account nicht bearbeiten.';
            $msgType = 'warning';
        } elseif ($action === 'toggle_active') {
            // Status vor Toggle lesen, dann toggeln, dann korrekt loggen
            $cur = $pdo->prepare('SELECT `is_active`, `username` FROM `users` WHERE `id` = ?');
            $cur->execute([$uid]);
            $curRow  = $cur->fetch();
            $pdo->prepare('UPDATE `users` SET `is_active` = 1 - `is_active` WHERE `id` = ?')->execute([$uid]);
            $newActive = !(bool) ($curRow['is_active'] ?? false);
            cg_log(
                $newActive ? LOG_ADMIN_USER_ACTIVATED : LOG_ADMIN_USER_DEACTIVATED,
                ['target_id' => $uid, 'target_user' => $curRow['username'] ?? '']
            );
            $message = 'Status wurde geändert.';
        } elseif ($action === 'set_role') {
            $role = (string) ($_POST['role'] ?? '');
            if (!in_array($role, ['user','admin'], true)) {
                $message = 'Ungültige Rolle.';
                $msgType = 'danger';
            } else {
                $tRow = $pdo->prepare('SELECT `username`, `role` FROM `users` WHERE `id` = ?');
                $tRow->execute([$uid]);
                $tData = $tRow->fetch();
                $pdo->prepare('UPDATE `users` SET `role` = ? WHERE `id` = ?')->execute([$role, $uid]);
                cg_log(LOG_ADMIN_USER_ROLE, [
                    'target_id'   => $uid,
                    'target_user' => $tData['username'] ?? '',
                    'old_role'    => $tData['role'] ?? '',
                    'new_role'    => $role,
                ]);
                $message = 'Rolle wurde aktualisiert.';
            }
        } elseif ($action === 'delete') {
            $tRow = $pdo->prepare('SELECT `username`, `email` FROM `users` WHERE `id` = ?');
            $tRow->execute([$uid]);
            $tData = $tRow->fetch();
            $pdo->prepare('DELETE FROM `users` WHERE `id` = ?')->execute([$uid]);
            cg_log(LOG_ADMIN_USER_DELETED, [
                'target_id'   => $uid,
                'target_user' => $tData['username'] ?? '',
                'email'       => $tData['email'] ?? '',
            ]);
            $message = 'Benutzer wurde gelöscht.';
        } elseif ($action === 'verify') {
            $tRow = $pdo->prepare('SELECT `username` FROM `users` WHERE `id` = ?');
            $tRow->execute([$uid]);
            $targetUser = $tRow->fetchColumn();
            $pdo->prepare('UPDATE `users` SET `email_verified`=1, `is_active`=1, `email_verify_token`=NULL WHERE `id`=?')->execute([$uid]);
            cg_log(LOG_ADMIN_USER_VERIFIED, ['target_id' => $uid, 'target_user' => $targetUser ?: '']);
            $message = 'E-Mail manuell verifiziert &amp; Account aktiviert.';
        }
    } catch (Throwable $e) {
        $message = 'Fehler: ' . htmlspecialchars($e->getMessage());
        $msgType = 'danger';
    }
}

// ── Load users ────────────────────────────────────────────────────
$users = [];
if ($pdo !== null) {
    try {
        $users = $pdo->query(
            'SELECT `id`,`username`,`first_name`,`last_name`,`email`,
                    `role`,`is_active`,`email_verified`,`created_at`,`last_login`
             FROM `users`
             ORDER BY `created_at` DESC'
        )->fetchAll();
    } catch (Throwable) {}
}
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label" style="color:var(--cg-red);">Administrator</span>
      <h1 class="mt-2">Benutzerverwaltung</h1>
      <p><?= count($users) ?> Benutzer in der Datenbank</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">

      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $msgType ?> mb-4" style="border-radius:.65rem;"><?= $message ?></div>
      <?php endif; ?>

      <?php if (empty($users)): ?>
        <div class="cg-card text-center" style="padding:3rem;opacity:.7;">
          <i class="fas fa-users fa-2x mb-3" style="color:var(--cg-muted);"></i>
          <p style="color:var(--cg-muted);margin:0;">Noch keine Benutzer.</p>
        </div>
      <?php else: ?>
        <div class="cg-card p-0" style="overflow:hidden;">
          <div class="table-responsive">
            <table class="admin-table w-100">
              <thead>
                <tr>
                  <th>Benutzer</th>
                  <th>E-Mail</th>
                  <th>Rolle</th>
                  <th>Status</th>
                  <th>Registriert</th>
                  <th>Aktionen</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u):
                  $isMe = ((int)$u['id'] === (int)auth_user()['id']);
                  $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                ?>
                <tr<?= $isMe ? ' style="background:rgba(232,53,31,.06)"' : '' ?>>
                  <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div>
                    <?php if ($fullName !== ''): ?>
                      <div style="font-size:.78rem;color:var(--cg-muted);"><?= htmlspecialchars($fullName) ?></div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.85rem;">
                    <?= htmlspecialchars($u['email'] ?? '&ndash;') ?>
                    <?php if (!(bool)$u['email_verified']): ?>
                      <span class="admin-badge" style="background:rgba(245,159,0,.15);color:#f59f00;">unverifiziert</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($u['role'] === 'admin'): ?>
                      <span class="admin-badge" style="background:var(--cg-red-subtle);color:var(--cg-red);">Admin</span>
                    <?php else: ?>
                      <span class="admin-badge" style="background:rgba(255,255,255,.08);color:var(--cg-muted);">Member</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((bool)$u['is_active']): ?>
                      <span class="admin-badge" style="background:rgba(55,178,77,.15);color:#37b24d;">Aktiv</span>
                    <?php else: ?>
                      <span class="admin-badge" style="background:rgba(224,49,49,.12);color:#e03131;">Inaktiv</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.8rem;color:var(--cg-muted);"><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                  <td>
                    <?php if (!$isMe): ?>
                    <div class="d-flex flex-wrap gap-1">

                      <!-- Toggle active -->
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="toggle_active">
                        <button type="submit" class="admin-btn"
                                style="<?= (bool)$u['is_active'] ? 'color:#f59f00' : 'color:#37b24d' ?>"
                                title="<?= (bool)$u['is_active'] ? 'Sperren' : 'Aktivieren' ?>"
                                onclick="return confirm('Status wirklich ändern?')">
                          <i class="fas fa-<?= (bool)$u['is_active'] ? 'ban' : 'check' ?>"></i>
                        </button>
                      </form>

                      <!-- Set role -->
                      <form method="POST" class="d-inline d-flex align-items-center gap-1">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="set_role">
                        <select name="role" class="admin-select" onchange="this.form.submit()">
                          <option value="user"  <?= $u['role']==='user'  ? 'selected':'' ?>>Member</option>
                          <option value="admin" <?= $u['role']==='admin' ? 'selected':'' ?>>Admin</option>
                        </select>
                      </form>

                      <!-- Manual verify -->
                      <?php if (!(bool)$u['email_verified']): ?>
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="verify">
                        <button type="submit" class="admin-btn" style="color:#37b24d;" title="E-Mail manuell verifizieren">
                          <i class="fas fa-envelope-circle-check"></i>
                        </button>
                      </form>
                      <?php endif; ?>

                      <!-- Delete -->
                      <form method="POST" class="d-inline">
                        <input type="hidden" name="uid" value="<?= (int)$u['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="admin-btn" style="color:#e03131;" title="Benutzer löschen"
                                onclick="return confirm('Benutzer &quot;<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>&quot; wirklich löschen? Dieser Vorgang ist unwiderruflich!')">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>

                    </div>
                    <?php else: ?>
                      <span style="font-size:.78rem;color:var(--cg-muted);">Du selbst</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<?= admin_table_styles() ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
function admin_table_styles(): string { return <<<'CSS'
<style>
.admin-table { border-collapse: collapse; }
.admin-table thead tr { border-bottom: 1px solid var(--cg-border); }
.admin-table th {
  padding: .85rem 1.1rem;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--cg-muted);
  white-space: nowrap;
  background: rgba(255,255,255,.025);
}
.admin-table td {
  padding: .8rem 1.1rem;
  color: #e8e8ed;
  vertical-align: middle;
  border-bottom: 1px solid rgba(255,255,255,.05);
  font-size: .9rem;
}
.admin-table tr:last-child td { border-bottom: none; }
.admin-badge {
  display: inline-block;
  padding: .15rem .55rem;
  border-radius: 2rem;
  font-size: .72rem;
  font-weight: 700;
  margin-top: .15rem;
}
.admin-btn {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: .4rem;
  padding: .3rem .5rem;
  font-size: .8rem;
  cursor: pointer;
  transition: background .2s, border-color .2s;
}
.admin-btn:hover { background: rgba(255,255,255,.1); }
.admin-select {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: .4rem;
  padding: .28rem .5rem;
  color: #e8e8ed;
  font-size: .8rem;
  cursor: pointer;
}
.admin-select:focus { outline: 1px solid var(--cg-red); }
</style>
CSS; }
?>
