<?php
$pageTitle = 'Admin &ndash; Projekte';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$pdo     = db();
$message = '';
$msgType = 'success';
$editItem = null;

// ── POST ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo !== null) {
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create' || $action === 'update') {
            $title      = trim((string) ($_POST['title']       ?? ''));
            $desc       = trim((string) ($_POST['description'] ?? ''));
            $iconClass  = trim((string) ($_POST['icon_class']  ?? 'fas fa-star'));
            $iconColor  = trim((string) ($_POST['icon_color']  ?? '#e8351f'));
            $iconBg     = trim((string) ($_POST['icon_bg']     ?? 'rgba(232,53,31,.15)'));
            $linkUrl    = trim((string) ($_POST['link_url']    ?? ''));
            $linkLabel  = trim((string) ($_POST['link_label']  ?? ''));
            $linkStyle  = trim((string) ($_POST['link_style']  ?? 'outline'));
            $sortOrder  = (int)         ($_POST['sort_order']  ?? 0);
            $tagsRaw    = trim((string) ($_POST['tags']        ?? ''));

            // Build tags JSON
            $tagsArr = array_filter(array_map('trim', explode(',', $tagsRaw)));
            $tagsJson = count($tagsArr) > 0 ? json_encode(array_values($tagsArr), JSON_UNESCAPED_UNICODE) : null;

            if ($title === '' || $desc === '') {
                $message = 'Titel und Beschreibung sind Pflichtfelder.'; $msgType = 'danger';
            } elseif ($action === 'create') {
                $pdo->prepare(
                    'INSERT INTO `projects` (`title`,`description`,`icon_class`,`icon_color`,`icon_bg`,
                     `tags`,`link_url`,`link_label`,`link_style`,`sort_order`,`is_active`)
                     VALUES (?,?,?,?,?,?,?,?,?,?,1)'
                )->execute([$title,$desc,$iconClass,$iconColor,$iconBg,$tagsJson,
                             $linkUrl ?: null, $linkLabel ?: null, $linkStyle, $sortOrder]);
                $message = 'Projekt erstellt.';
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                $pdo->prepare(
                    'UPDATE `projects` SET `title`=?,`description`=?,`icon_class`=?,`icon_color`=?,
                     `icon_bg`=?,`tags`=?,`link_url`=?,`link_label`=?,`link_style`=?,`sort_order`=?
                     WHERE `id`=?'
                )->execute([$title,$desc,$iconClass,$iconColor,$iconBg,$tagsJson,
                             $linkUrl ?: null, $linkLabel ?: null, $linkStyle, $sortOrder, $id]);
                $message = 'Projekt aktualisiert.';
            }
        } elseif ($action === 'toggle') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('UPDATE `projects` SET `is_active`=1-`is_active` WHERE `id`=?')->execute([$id]);
            $message = 'Sichtbarkeit geändert.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $pdo->prepare('DELETE FROM `projects` WHERE `id`=?')->execute([$id]);
            $message = 'Projekt gelöscht.';
        }
    } catch (Throwable $e) {
        $message = 'Fehler: ' . htmlspecialchars($e->getMessage()); $msgType = 'danger';
    }
}

// Load edit item
$editId = (int) ($_GET['edit'] ?? 0);
if ($editId > 0 && $pdo !== null) {
    try {
        $s = $pdo->prepare('SELECT * FROM `projects` WHERE `id`=?');
        $s->execute([$editId]);
        $editItem = $s->fetch() ?: null;
    } catch (Throwable) {}
}

// Load all projects
$projects = [];
if ($pdo !== null) {
    try {
        $projects = $pdo->query('SELECT * FROM `projects` ORDER BY `sort_order` ASC, `id` ASC')->fetchAll();
    } catch (Throwable) {}
}

// Convert tags JSON to comma string for edit form
$editTagsStr = '';
if ($editItem && $editItem['tags']) {
    $arr = json_decode($editItem['tags'], true);
    if (is_array($arr)) $editTagsStr = implode(', ', $arr);
}

// Preset icon options
$iconPresets = [
    ['class'=>'fab fa-twitch',       'color'=>'#9147ff', 'bg'=>'rgba(145,71,255,.15)', 'label'=>'Twitch'],
    ['class'=>'fab fa-youtube',      'color'=>'#ff4444', 'bg'=>'rgba(255,68,68,.15)',  'label'=>'YouTube'],
    ['class'=>'fab fa-discord',      'color'=>'#7289da', 'bg'=>'rgba(88,101,242,.15)', 'label'=>'Discord'],
    ['class'=>'fas fa-globe',        'color'=>'#9775fa', 'bg'=>'rgba(112,72,232,.15)', 'label'=>'Website'],
    ['class'=>'fas fa-laptop-code',  'color'=>'#e8351f', 'bg'=>'rgba(232,53,31,.15)',  'label'=>'IT/Code'],
    ['class'=>'fas fa-gamepad',      'color'=>'#37b24d', 'bg'=>'rgba(55,178,77,.15)',  'label'=>'Gaming'],
    ['class'=>'fas fa-star',         'color'=>'#f59f00', 'bg'=>'rgba(245,159,0,.15)',  'label'=>'Sonstig'],
];
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main>
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label" style="color:var(--cg-red);">Administrator</span>
      <h1 class="mt-2">Projekte verwalten</h1>
      <p>Projektkarten auf der öffentlichen Projekte-Seite erstellen und bearbeiten.</p>
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
              <?= $editItem ? 'Projekt bearbeiten' : 'Neues Projekt' ?>
            </h5>

            <form method="POST">
              <input type="hidden" name="action" value="<?= $editItem ? 'update' : 'create' ?>">
              <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">
              <?php endif; ?>

              <div class="pg-group">
                <label class="pg-label">Titel *</label>
                <input type="text" name="title" class="pg-input"
                       value="<?= htmlspecialchars($editItem['title'] ?? '') ?>"
                       placeholder="z.B. Twitch Streaming" required>
              </div>

              <div class="pg-group">
                <label class="pg-label">Beschreibung *</label>
                <textarea name="description" class="pg-input" rows="4"
                          placeholder="Kurze Projektbeschreibung..."
                          required><?= htmlspecialchars($editItem['description'] ?? '') ?></textarea>
              </div>

              <div class="pg-group">
                <label class="pg-label">Tags <small style="color:var(--cg-muted)">(kommagetrennt)</small></label>
                <input type="text" name="tags" class="pg-input"
                       value="<?= htmlspecialchars($editTagsStr) ?>"
                       placeholder="z.B. PHP, Bootstrap, MySQL">
              </div>

              <!-- Icon Presets -->
              <div class="pg-group">
                <label class="pg-label">Icon-Preset wählen</label>
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <?php foreach ($iconPresets as $p): ?>
                  <button type="button" class="pg-preset-btn"
                          onclick="pgApplyPreset('<?= htmlspecialchars($p['class'],ENT_QUOTES) ?>','<?= $p['color'] ?>','<?= htmlspecialchars($p['bg'],ENT_QUOTES) ?>')"
                          title="<?= $p['label'] ?>">
                    <span style="width:28px;height:28px;border-radius:.35rem;display:inline-flex;align-items:center;justify-content:center;background:<?= $p['bg'] ?>;color:<?= $p['color'] ?>;font-size:.85rem;">
                      <i class="<?= $p['class'] ?>"></i>
                    </span>
                    <span style="font-size:.75rem;color:var(--cg-muted);"><?= $p['label'] ?></span>
                  </button>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-4">
                  <label class="pg-label">Icon-Klasse</label>
                  <input type="text" id="pg_icon_class" name="icon_class" class="pg-input"
                         value="<?= htmlspecialchars($editItem['icon_class'] ?? 'fas fa-star') ?>">
                </div>
                <div class="col-4">
                  <label class="pg-label">Icon-Farbe</label>
                  <input type="text" id="pg_icon_color" name="icon_color" class="pg-input"
                         value="<?= htmlspecialchars($editItem['icon_color'] ?? '#e8351f') ?>">
                </div>
                <div class="col-4">
                  <label class="pg-label">Icon-BG</label>
                  <input type="text" id="pg_icon_bg" name="icon_bg" class="pg-input"
                         value="<?= htmlspecialchars($editItem['icon_bg'] ?? 'rgba(232,53,31,.15)') ?>">
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-7">
                  <label class="pg-label">Link-URL</label>
                  <input type="url" name="link_url" class="pg-input"
                         value="<?= htmlspecialchars($editItem['link_url'] ?? '') ?>"
                         placeholder="https://...">
                </div>
                <div class="col-5">
                  <label class="pg-label">Link-Text</label>
                  <input type="text" name="link_label" class="pg-input"
                         value="<?= htmlspecialchars($editItem['link_label'] ?? '') ?>"
                         placeholder="Zum Projekt">
                </div>
              </div>

              <div class="row g-2 mb-3">
                <div class="col-6">
                  <label class="pg-label">Button-Stil</label>
                  <select name="link_style" class="pg-input">
                    <?php foreach (['outline'=>'Outline','primary'=>'Gefüllt','discord'=>'Discord','disabled'=>'Kein Link'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= ($editItem['link_style'] ?? 'outline')===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-6">
                  <label class="pg-label">Reihenfolge</label>
                  <input type="number" name="sort_order" class="pg-input"
                         value="<?= (int)($editItem['sort_order'] ?? 0) ?>" min="0" max="999">
                </div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <button type="submit" class="cg-login-btn flex-grow-1" style="margin-top:0;">
                  <i class="fas fa-<?= $editItem ? 'floppy-disk' : 'plus' ?> me-2"></i>
                  <?= $editItem ? 'Speichern' : 'Erstellen' ?>
                </button>
                <?php if ($editItem): ?>
                  <a href="/admin/projects.php" class="cg-btn cg-btn-outline" style="padding:.7rem 1rem;">
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
            Alle Projekte (<?= count($projects) ?>)
          </h4>

          <?php if (empty($projects)): ?>
            <div class="cg-card text-center" style="opacity:.7;padding:2.5rem;">
              <i class="fas fa-folder-open fa-2x mb-3" style="color:var(--cg-muted);"></i>
              <p style="color:var(--cg-muted);margin:0;">Noch keine Projekte angelegt.</p>
            </div>
          <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
              <?php foreach ($projects as $proj):
                $tags = $proj['tags'] ? json_decode($proj['tags'], true) : [];
              ?>
              <div class="cg-card" style="padding:1rem 1.1rem;<?= !(bool)$proj['is_active'] ? 'opacity:.5;' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                  <span class="cg-feature-icon" style="min-width:40px;height:40px;font-size:.9rem;flex-shrink:0;background:<?= htmlspecialchars($proj['icon_bg']) ?>;color:<?= htmlspecialchars($proj['icon_color']) ?>;">
                    <i class="<?= htmlspecialchars($proj['icon_class']) ?>"></i>
                  </span>
                  <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                      <div style="font-weight:600;"><?= htmlspecialchars($proj['title']) ?></div>
                      <div style="font-size:.75rem;color:var(--cg-muted);">
                        #<?= (int)$proj['id'] ?> &middot; Reihenfolge: <?= (int)$proj['sort_order'] ?>
                        &middot; <?= (bool)$proj['is_active'] ? '<span style="color:#37b24d">Sichtbar</span>' : '<span style="color:#e03131">Versteckt</span>' ?>
                      </div>
                    </div>
                    <?php if (is_array($tags) && count($tags)): ?>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                      <?php foreach ($tags as $tag): ?>
                        <span style="background:rgba(255,255,255,.08);color:var(--cg-muted);padding:.1rem .45rem;border-radius:2rem;font-size:.7rem;"><?= htmlspecialchars($tag) ?></span>
                      <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex gap-1 flex-shrink-0">
                    <a href="/admin/projects.php?edit=<?= (int)$proj['id'] ?>" class="admin-btn" title="Bearbeiten">
                      <i class="fas fa-pen" style="color:#339af0;"></i>
                    </a>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id"     value="<?= (int)$proj['id'] ?>">
                      <button class="admin-btn" title="<?= (bool)$proj['is_active'] ? 'Verstecken' : 'Anzeigen' ?>">
                        <i class="fas fa-<?= (bool)$proj['is_active'] ? 'eye-slash' : 'eye' ?>" style="color:#f59f00;"></i>
                      </button>
                    </form>
                    <form method="POST" class="d-inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id"     value="<?= (int)$proj['id'] ?>">
                      <button class="admin-btn" title="Löschen"
                              onclick="return confirm('Projekt &quot;<?= htmlspecialchars($proj['title'], ENT_QUOTES) ?>&quot; wirklich löschen?')">
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

<style>
.pg-group  { margin-bottom: 1rem; }
.pg-label  { display:block;color:#c0c0cc;font-size:.82rem;font-weight:600;margin-bottom:.35rem; }
.pg-input  {
  width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
  border-radius:.5rem;padding:.6rem .85rem;color:#e8e8ed;font-size:.88rem;
  transition:border-color .2s;outline:none;
}
.pg-input:focus  { border-color:var(--cg-red); }
textarea.pg-input { resize:vertical; }
.pg-preset-btn {
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);
  border-radius:.5rem;padding:.4rem .6rem;cursor:pointer;
  display:inline-flex;align-items:center;gap:.4rem;transition:border-color .2s;
}
.pg-preset-btn:hover { border-color:var(--cg-red); }
.cg-login-btn {
  width:100%;padding:.7rem;background:var(--cg-red);color:#fff;border:none;
  border-radius:.5rem;font-weight:700;font-size:1rem;cursor:pointer;transition:background .2s;
}
.cg-login-btn:hover { background:#c92a2a; }
.admin-btn {
  background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);
  border-radius:.4rem;padding:.3rem .5rem;font-size:.8rem;cursor:pointer;
  text-decoration:none;display:inline-flex;align-items:center;transition:background .2s;
}
.admin-btn:hover { background:rgba(255,255,255,.1); }
</style>

<script>
function pgApplyPreset(iconClass, iconColor, iconBg) {
  document.getElementById('pg_icon_class').value = iconClass;
  document.getElementById('pg_icon_color').value = iconColor;
  document.getElementById('pg_icon_bg').value    = iconBg;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
