<?php
$noindex   = true;
$pageTitle = 'Admin &ndash; Entwicklung';
require_once __DIR__ . '/../includes/head.php';
auth_require_role('admin');

$root = dirname(__DIR__);

/**
 * Führt git OHNE Shell aus (feste Argumente, keine Benutzereingaben) mit Zeitlimit.
 *
 * @param  list<string> $args
 * @return array{0:int,1:string} [Exit-Code, Ausgabe]
 */
function dev_git(array $args, string $root, int $timeout = 60): array
{
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (!function_exists('proc_open') || in_array('proc_open', $disabled, true)) {
        return [127, 'proc_open ist auf diesem Server deaktiviert – Update per SSH: git pull'];
    }

    $env = array_merge(
        array_map('strval', getenv() ?: []),
        ['GIT_TERMINAL_PROMPT' => '0', 'LC_ALL' => 'C.UTF-8']
    );
    $cmd = array_merge(['git', '-c', 'safe.directory=' . $root], $args);
    $proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
    if (!is_resource($proc)) {
        return [127, 'git konnte nicht gestartet werden (ist git auf dem Server installiert?)'];
    }

    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $out   = '';
    $start = time();
    while (true) {
        $out .= (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
        $status = proc_get_status($proc);
        if (!$status['running']) {
            break;
        }
        if (time() - $start > $timeout) {
            proc_terminate($proc);
            $out .= "\n[Abbruch: Zeitlimit von {$timeout}s überschritten]";
            break;
        }
        usleep(100000);
    }
    $out .= (string) stream_get_contents($pipes[1]) . (string) stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($proc);
    if (isset($status) && !$status['running'] && $status['exitcode'] >= 0) {
        $code = $status['exitcode'];
    }

    return [$code, trim($out)];
}

$output  = '';
$msgType = 'success';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'check') {
        [$c1, $o1] = dev_git(['fetch', '--prune', 'origin'], $root);
        if ($c1 !== 0) {
            $message = 'Abruf von GitHub fehlgeschlagen.';
            $msgType = 'danger';
            $output  = $o1;
        } else {
            [, $log] = dev_git(['log', '--oneline', '--no-decorate', 'HEAD..@{u}'], $root);
            if ($log === '') {
                $message = 'Die Seite ist auf dem neuesten Stand.';
            } else {
                $message = 'Es gibt neue Änderungen auf GitHub:';
                $msgType = 'warning';
                $output  = $log;
            }
        }
    } elseif ($action === 'pull') {
        [$code, $o] = dev_git(['pull', '--ff-only', 'origin'], $root, 120);
        $output = $o;
        if ($code === 0) {
            $message = 'Updates wurden eingespielt.';
            // Schema-Marker löschen, damit neue DB-Migrationen laufen; PHP-Cache leeren
            foreach (glob($root . '/tmp/.schema_v*') ?: [] as $f) {
                @unlink($f);
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
            cg_log(LOG_ADMIN_DEPLOY, ['result' => 'ok', 'output' => mb_substr($o, 0, 300)]);
        } else {
            $message = 'Update fehlgeschlagen (Details unten). Lokale Änderungen auf dem Server oder fehlende Zugangsdaten für GitHub sind häufige Ursachen.';
            $msgType = 'danger';
            cg_log(LOG_ADMIN_DEPLOY, ['result' => 'failed', 'output' => mb_substr($o, 0, 300)]);
        }
    }
}

[, $branch] = dev_git(['rev-parse', '--abbrev-ref', 'HEAD'], $root, 10);
[, $head]   = dev_git(['log', '-1', '--format=%h  %s  (%cd)', '--date=format:%d.%m.%Y %H:%M'], $root, 10);
?>
<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<main id="main">
  <section class="cg-page-hero">
    <div class="container">
      <span class="cg-section-label">Administrator</span>
      <h1 class="mt-2">Entwicklung</h1>
      <p>Neueste Änderungen von GitHub auf diesen Server übernehmen.</p>
    </div>
  </section>

  <section class="cg-section">
    <div class="container" style="max-width:820px;">

      <?php if ($message !== ''): ?>
        <div class="alert alert-<?= e($msgType) ?> mb-4" style="border-radius:.65rem;"><?= e($message) ?></div>
      <?php endif; ?>

      <div class="cg-card cg-card-static mb-4">
        <h5 class="mb-3 cg-red"><i class="fab fa-github me-2"></i>Aktueller Stand</h5>
        <div class="cg-meta-label">Branch</div>
        <div class="cg-meta-value mb-3"><?= e($branch !== '' ? $branch : 'unbekannt (kein Git-Repository?)') ?></div>
        <div class="cg-meta-label">Letzter Commit</div>
        <div class="cg-meta-value" style="word-break:break-word;"><?= e($head !== '' ? $head : '–') ?></div>

        <hr style="border-color:var(--cg-border);margin:1.5rem 0;opacity:1;">

        <div class="d-flex flex-wrap gap-2">
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="check">
            <button type="submit" class="cg-btn cg-btn-outline">
              <i class="fas fa-magnifying-glass"></i>Auf Updates prüfen
            </button>
          </form>
          <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="pull">
            <button type="submit" class="cg-btn cg-btn-primary"
                    data-confirm="Neueste Änderungen jetzt von GitHub einspielen?">
              <i class="fas fa-cloud-arrow-down"></i>Updates von GitHub einspielen
            </button>
          </form>
        </div>
        <p class="cg-form-hint mt-3 mb-0">
          Es wird nur <code>git pull --ff-only</code> ausgeführt (keine Eingaben, kein Überschreiben lokaler Änderungen).
          Jede Aktion wird im Activity Log protokolliert.
        </p>
      </div>

      <?php if ($output !== ''): ?>
        <div class="cg-card cg-card-static">
          <h6 class="mb-3">Ausgabe</h6>
          <pre style="margin:0;white-space:pre-wrap;word-break:break-word;color:var(--cg-text-2);font-size:.82rem;"><?= e($output) ?></pre>
        </div>
      <?php endif; ?>

    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
