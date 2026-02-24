<?php
$pageTitle = 'Admin &ndash; Ankündigungen';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo     = db();
$message = '';
$msgType = 'success';
$editItem = null;

// ── POST actions ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create' || $action === 'update') {
            $title = trim((string) ($_POST['title'] ?? ''));
            $body  = trim((string) ($_POST['body']  ?? ''));
            if ($title === '' || $body === '') {
                $message = 'Titel und Text dürfen nicht leer sein.';
                $msgType = 'danger';
            } elseif ($action === 'create') {
                $pdo->prepare(
                    'INSERT INTO `announcements` (`title`,`body`,`author_id`,`is_active`) VALUES (?,?,?,1)'
                )->execute([$title, $body, auth_user()['id']]);
                cg_log(LOG_ADMIN_ANN_CREATED, ['title' => $title]);
                $message = 'Ankündigung erstellt.';
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $pdo->prepare(
                    'UPDATE `announcements` SET `title`=?,`body`=? WHERE `id`=?'
                )->execute([$title, $body, $id]);
                cg_log(LOG_ADMIN_ANN_UPDATED, ['id' => $id, 'title' => $title]);
                $message = 'Ankündigung aktualisiert.';
            }
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE `announcements` SET `is_active`=1-`is_active` WHERE `id`=?')->execute([$id]);
            cg_log(LOG_ADMIN_ANN_TOGGLED, ['id' => $id]);
            $message = 'Status geändert.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $tRow = $pdo->prepare('SELECT `title` FROM `announcements` WHERE `id`=?');
            $tRow->execute([$id]);
            $tTitle = $tRow->fetchColumn();
            $pdo->prepare('DELETE FROM `announcements` WHERE `id`=?')->execute([$id]);
            cg_log(LOG_ADMIN_ANN_DELETED, ['id' => $id, 'title' => $tTitle ?: '']);
            $message = 'Ankündigung gelöscht.';
        }
    } catch (Throwable $e) {
        $message = 'Fehler: ' . htmlspecialchars($e->getMessage());
        $msgType = 'danger';
    }
}

// Load edit item
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0 && $pdo !== null) {
    try {
        $editItem = $pdo->prepare('SELECT * FROM `announcements` WHERE `id`=?');
        $editItem->execute([$editId]);
        $editItem = $editItem->fetch() ?: null;
    } catch (Throwable) {}
}

// ── Load all ─────────────────────────────────────────────────────
$items = [];
if ($pdo !== null) {
    try {
        $items = $pdo->query(
            'SELECT a.*,u.`username` AS author_name
             FROM `announcements` a
             JOIN `users` u ON u.`id`=a.`author_id`
             ORDER BY a.`created_at` DESC'
        )->fetchAll();
    } catch (Throwable) {}
}
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label" style="color:var(--cg-red);">Administrator</span>
      <h1 class="mt-2">Ankündigungen</h1>
      <p>Erstelle exklusive Neuigkeiten für eingeloggte Mitglieder.</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container">

      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= $msgType ?> mb-4" style="border-radius:.65rem;"><?= $message ?></div>
      <?php endif; ?>

      <div class="row gy-5">

        <!-- Form -->
        <div class="col-12 col-lg-5">
          <div class="cg-card">
            <h5 class="mb-4" style="color:var(--cg-red);">
              <i class="fas fa-<?= $editItem ? 'pen' : 'plus' ?> me-2"></i>
              <?= $editItem ? 'Ankündigung bearbeiten' : 'Neue Ankündigung' ?>
            </h5>

            <form method="POST">
              <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
              <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
              <?php endif; ?>

              <div class="cg-form-group">
                <label class="cg-form-label">Titel</label>
                <div class="cg-input-wrap">
                  <i class="fas fa-heading cg-input-icon"></i>
                  <input type="text" name="title" class="cg-form-input"
                         value="<?= htmlspecialchars($editItem['title'] ?? '') ?>"
                         placeholder="Ankündigungs-Titel" required>
                </div>
              </div>

              <div class="cg-form-group">
                <label class="cg-form-label">Text</label>
                <textarea name="body" class="cg-form-input" rows="8"
                          style="padding-left:.9rem;resize:vertical;"
                          placeholder="Hier den Text der Ankündigung eingeben..."
                          required><?= htmlspecialchars($editItem['body'] ?? '') ?></textarea>
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="cg-login-btn flex-grow-1" style="margin-top:0;">
                  <i class="fas fa-<?= $editItem ? 'floppy-disk' : 'plus' ?> me-2"></i>
                  <?= $editItem ? 'Speichern' : 'Erstellen' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="/admin/announcements.php" class="cg-btn cg-btn-outline" style="padding:.7rem 1rem;">
                    <i class="fas fa-xmark"></i>
                  </a>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>

        <!-- List -->
        <div class="col-12 col-lg-7">
          <h4 class="mb-4" style="color:var(--cg-muted);font-size:.85rem;text-transform:uppercase;letter-spacing:.1em;">
            Alle Ankündigungen (<?= count($items) ?>)
          </h4>

          <?php if (empty($items)): ?>
            <div class="cg-card text-center" style="opacity:.7;padding:2.5rem;">
              <i class="fas fa-newspaper fa-2x mb-3" style="color:var(--cg-muted);"></i>
              <p style="color:var(--cg-muted);margin:0;">Noch keine Ankündigungen.</p>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
              <?php foreach ($items as $item): ?>
              <div class="cg-card" style="padding:1rem 1.1rem;<?= !(bool)$item['is_active'] ? 'opacity:.5;' : '' ?>">
                <div class="d-flex align-items-start justify-content-between gap-2">
                  <div class="flex-grow-1 min-w-0">
                    <div style="font-weight:600;margin-bottom:.25rem;"><?= htmlspecialchars($item['title']) ?></div>
                    <div style="font-size:.78rem;color:var(--cg-muted);">
                      <?= htmlspecialchars($item['author_name']) ?> &middot; <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?>
                      &middot; <?= (bool)$item['is_active'] ? '<span style="color:#37b24d">Aktiv</span>' : '<span style="color:#e03131">Versteckt</span>' ?>
                    </div>
                    <div style="font-size:.85rem;color:var(--cg-text-2);margin-top:.4rem;overflow:hidden;max-height:3.2em;-webkit-line-clamp:2;display:-webkit-box;-webkit-box-orient:vertical;">
                      <?= htmlspecialchars(mb_strimwidth($item['body'], 0, 120, '&hellip;')) ?>
                    </div>
                  </div>
                  <div class="d-flex gap-1 flex-shrink-0">
                    <a href="/admin/announcements.php?edit=<?= (int)$item['id'] ?>" class="admin-btn" title="Bearbeiten">
                      <i class="fas fa-pen" style="color:#339af0;"></i>
                    </a>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id"     value="<?= (int)$item['id'] ?>">
                      <button class="admin-btn" title="<?= (bool)$item['is_active'] ? 'Verstecken' : 'Anzeigen' ?>">
                        <i class="fas fa-<?= (bool)$item['is_active'] ? 'eye-slash' : 'eye' ?>" style="color:#f59f00;"></i>
                      </button>
                    </form>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id"     value="<?= (int)$item['id'] ?>">
                      <button class="admin-btn" title="Löschen"
                              onclick="return confirm('Ankündigung &quot;<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>&quot; wirklich löschen?')">
                        <i class="fas fa-trash" style="color:#e03131;"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </section>
</main>

<?= admin_ann_styles() ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
function admin_ann_styles(): string { return <<<'CSS'
<style>
.cg-form-group  { margin-bottom: 1.1rem; }
.cg-form-label  { display:block;color:#c0c0cc;font-size:.85rem;font-weight:600;margin-bottom:.4rem; }
.cg-input-wrap  { position:relative; }
.cg-input-icon  { position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:#5a5a6e;font-size:.9rem;pointer-events:none; }
.cg-form-input  {
  width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
  border-radius:.5rem;padding:.65rem .9rem .65rem 2.4rem;color:#e8e8ed;
  font-size:.95rem;transition:border-color .2s;outline:none;
}
textarea.cg-form-input { padding-left:.9rem; }
.cg-form-input:focus { border-color:var(--cg-red);background:rgba(255,255,255,.06); }
.cg-login-btn {
  width:100%;padding:.7rem;background:var(--cg-red);color:#fff;border:none;
  border-radius:.5rem;font-weight:700;font-size:1rem;cursor:pointer;margin-top:.5rem;transition:background .2s;
}
.cg-login-btn:hover { background:#c92a2a; }
.admin-btn {
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
  border-radius:.4rem;padding:.3rem .5rem;font-size:.8rem;cursor:pointer;
  text-decoration:none;display:inline-flex;align-items:center;
  transition:background .2s;
}
.admin-btn:hover { background:rgba(255,255,255,.1); }
</style>
CSS; }
?>
