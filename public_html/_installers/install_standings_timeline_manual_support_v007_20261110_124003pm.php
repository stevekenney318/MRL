<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');
session_start();

$docRoot = dirname(__DIR__);
$target = $docRoot . '/race_results/standings_timeline.php';
$backupRoot = $docRoot . '/_installer_backups';
$sessionFile = $backupRoot . '/install_standings_timeline_manual_support_v007_last.json';
$expectedGitBlobSha = 'a7b4c4d8d9c40091f0ddb9e9b6d7aa217a37093c';

function ih($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ij(array $v): string { return (string)json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); }
function isha(string $p): string { return is_file($p) ? (string)hash_file('sha256', $p) : ''; }

function igit_blob_sha_from_content(string $content): string
{
    return sha1('blob ' . strlen($content) . "\0" . $content);
}

function ilint(string $p): array
{
    $o = shell_exec('php -l ' . escapeshellarg($p) . ' 2>&1');
    $o = is_string($o) ? trim($o) : '';
    return [
        'ok' => strpos($o, 'No syntax errors detected') !== false,
        'detail' => $o,
    ];
}

function iatomic(string $p, string $c): bool
{
    $t = $p . '.tmp_' . getmypid() . '_' . date('Ymd_His');
    if (file_put_contents($t, $c, LOCK_EX) === false) return false;
    @chmod($t, 0644);
    if (!@rename($t, $p)) {
        @unlink($t);
        return false;
    }
    @chmod($p, 0644);
    return true;
}

function icheck(array &$c, string $l, bool $ok, bool $r, string $d = ''): void
{
    $c[] = [
        'label' => $l,
        'status' => $ok ? 'PASS' : 'FAIL',
        'required' => $r,
        'detail' => $d,
    ];
}

function ipatches(): array
{
    return [
        [
            'label' => 'version and changelog',
            'old' => <<<'TXT'
 * VERSION: v006
 * LAST MODIFIED: 6/27/2026 1:12:11 am
 *
 * CHANGELOG:
 *
 * v006 (6/27/2026 1:12:11 am)
TXT,
            'new' => <<<'TXT'
 * VERSION: v007
 * LAST MODIFIED: 11/10/2026 12:40:03 pm ET
 *
 * CHANGELOG:
 *
 * v007 (11/10/2026 12:40:03 pm ET)
 *   - NEW: Timeline now recognizes manual snapshot releases stored in _manual_snapshot_overrides.json.
 *   - NEW: Manual releases appear as first-class timeline snapshots using their effective timestamp and stored MANUAL version label.
 *   - FIX: Selecting a manual release substitutes the manual snapshot for that race while earlier races continue using the latest canonical ESPN snapshot available at or before the manual effective time.
 *   - FIX: Races that did not yet exist at the manual effective time remain unavailable in that as-of timeline.
 *   - SAFETY: Invalid snapshot IDs no longer silently fall back to the newest snapshot; they stop with a clear Snapshot not found response.
 *   - PRESERVE: ESPN canonical version numbering remains independent; manual releases do not renumber later ESPN v1/v2/v3 labels.
 *   - PRESERVE: Revision monitor, canonical snapshot chain, Weekly Standings scoring logic, database, scheduler, and manual activation state are unchanged.
 *
 * v006 (6/27/2026 1:12:11 am)
TXT,
        ],
        [
            'label' => 'manual helper include',
            'old' => "require_once __DIR__ . '/race_results_engine.php';",
            'new' => "require_once __DIR__ . '/race_results_engine.php';\nrequire_once __DIR__ . '/manual_snapshot_override_helper.php';",
        ],
        [
            'label' => 'manual snapshot filename key support',
            'old' => <<<'TXT'
function st_snapshot_key_from_file(string $path): string
{
    $base = basename($path);
    if (!preg_match('/^snapshot_(\d{8}_\d{6}\d*)\.html$/', $base, $m)) {
        return '';
    }
    return (string)$m[1];
}
TXT,
            'new' => <<<'TXT'
function st_snapshot_key_from_file(string $path): string
{
    $base = basename($path);
    if (!preg_match('/^(?:manual_)?snapshot_(\d{8}_\d{6}\d*)\.html$/', $base, $m)) {
        return '';
    }
    return (string)$m[1];
}
TXT,
        ],
        [
            'label' => 'manual snapshot collection helpers',
            'old' => "\n\nfunction st_recursive_audit_records(array \$node): array",
            'new' => <<<'TXT'


function st_collect_manual_snapshots(string $yearFolder, array $races): array
{
    if (!function_exists('mrlm_load_manifest') || !function_exists('mrlm_record_snapshot_path')) {
        return [];
    }

    $manifest = mrlm_load_manifest($yearFolder);
    $records = isset($manifest['records']) && is_array($manifest['records']) ? $manifest['records'] : [];
    if (empty($records)) {
        return [];
    }

    $raceByCode = [];
    foreach ($races as $race) {
        $code = (string)($race['race_code'] ?? '');
        if ($code !== '') {
            $raceByCode[$code] = $race;
        }
    }

    $items = [];

    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        $manualId = trim((string)($record['manual_id'] ?? ''));
        $raceCode = trim((string)($record['race_code'] ?? ''));
        $snapshotKey = trim((string)($record['effective_stamp'] ?? ''));

        if ($manualId === '' || $raceCode === '' || !isset($raceByCode[$raceCode])) {
            continue;
        }

        if (preg_match('/^\d{8}_\d{6}\d*$/', $snapshotKey) !== 1) {
            continue;
        }

        $file = mrlm_record_snapshot_path($yearFolder, $record);
        if ($file === '' || !is_file($file)) {
            continue;
        }

        $race = $raceByCode[$raceCode];
        $storedVersion = trim((string)($record['display_version'] ?? ''));
        $activeId = (string)($manifest['active_by_race'][$raceCode] ?? '');

        $items[] = [
            'value' => $manualId,
            'snapshot_key' => $snapshotKey,
            'seconds_key' => st_snapshot_seconds_key($snapshotKey),
            'race_code' => $raceCode,
            'race_number' => (int)($race['number'] ?? 0),
            'race_label' => st_short_race_label((string)($race['race_name'] ?? '')),
            'file' => $file,
            'display_time' => st_snapshot_display($snapshotKey),
            'is_manual' => true,
            'manual_display_version' => $storedVersion,
            'manual_active' => ($activeId !== '' && $activeId === $manualId),
            'audit' => [
                'release' => 'Manual scoring release',
                'status' => 'Manual scoring override',
                'mrl_impact' => 'YES',
                'change' => 'Manual scoring override',
            ],
        ];
    }

    return $items;
}

function st_sort_snapshot_items_desc(array $items): array
{
    usort($items, function ($a, $b): int {
        $cmp = strcmp((string)($b['snapshot_key'] ?? ''), (string)($a['snapshot_key'] ?? ''));
        if ($cmp !== 0) {
            return $cmp;
        }

        $cmp = ((int)($b['race_number'] ?? 0)) <=> ((int)($a['race_number'] ?? 0));
        if ($cmp !== 0) {
            return $cmp;
        }

        return strcmp((string)($b['value'] ?? ''), (string)($a['value'] ?? ''));
    });

    return $items;
}

function st_recursive_audit_records(array $node): array
TXT,
        ],
        [
            'label' => 'preserve canonical version numbering and manual labels',
            'old' => <<<'TXT'
function st_enrich_snapshot_items(array $items, array $auditContextByReleaseId): array
{
    $ascending = $items;
    usort($ascending, function ($a, $b): int {
        $cmp = ((int)($a['race_number'] ?? 0)) <=> ((int)($b['race_number'] ?? 0));
        if ($cmp !== 0) return $cmp;
        return strcmp((string)($a['snapshot_key'] ?? ''), (string)($b['snapshot_key'] ?? ''));
    });

    $versionByValue = [];
    $countsByRace = [];

    foreach ($ascending as $item) {
        $raceCode = (string)($item['race_code'] ?? '');
        $value = (string)($item['value'] ?? '');
        if ($raceCode === '' || $value === '') {
            continue;
        }
        if (!isset($countsByRace[$raceCode])) {
            $countsByRace[$raceCode] = 0;
        }
        $countsByRace[$raceCode]++;
        $versionByValue[$value] = $countsByRace[$raceCode];
    }

    foreach ($items as $idx => $item) {
        $value = (string)($item['value'] ?? '');
        $versionNumber = (int)($versionByValue[$value] ?? 0);
        $items[$idx]['version_number'] = $versionNumber;
        $items[$idx]['version_label'] = $versionNumber > 0 ? ('v' . $versionNumber) : '';
        $items[$idx]['audit'] = isset($auditContextByReleaseId[$value]) && is_array($auditContextByReleaseId[$value]) ? $auditContextByReleaseId[$value] : [];
    }

    return $items;
}
TXT,
            'new' => <<<'TXT'
function st_enrich_snapshot_items(array $items, array $auditContextByReleaseId): array
{
    $ascending = $items;
    usort($ascending, function ($a, $b): int {
        $cmp = ((int)($a['race_number'] ?? 0)) <=> ((int)($b['race_number'] ?? 0));
        if ($cmp !== 0) return $cmp;
        return strcmp((string)($a['snapshot_key'] ?? ''), (string)($b['snapshot_key'] ?? ''));
    });

    $versionByValue = [];
    $countsByRace = [];

    foreach ($ascending as $item) {
        if (!empty($item['is_manual'])) {
            continue;
        }

        $raceCode = (string)($item['race_code'] ?? '');
        $value = (string)($item['value'] ?? '');
        if ($raceCode === '' || $value === '') {
            continue;
        }

        if (!isset($countsByRace[$raceCode])) {
            $countsByRace[$raceCode] = 0;
        }

        $countsByRace[$raceCode]++;
        $versionByValue[$value] = $countsByRace[$raceCode];
    }

    foreach ($items as $idx => $item) {
        $value = (string)($item['value'] ?? '');
        $existingAudit = isset($item['audit']) && is_array($item['audit']) ? $item['audit'] : [];

        if (!empty($item['is_manual'])) {
            $storedVersion = trim((string)($item['manual_display_version'] ?? ''));
            $versionNumber = 0;

            if (preg_match('/^v(\d+)$/i', $storedVersion, $m) === 1) {
                $versionNumber = (int)$m[1];
            }

            $items[$idx]['version_number'] = $versionNumber;
            $items[$idx]['version_label'] = $storedVersion !== '' ? ($storedVersion . ' MANUAL') : 'MANUAL';
            $items[$idx]['audit'] = $existingAudit;
            continue;
        }

        $versionNumber = (int)($versionByValue[$value] ?? 0);
        $items[$idx]['version_number'] = $versionNumber;
        $items[$idx]['version_label'] = $versionNumber > 0 ? ('v' . $versionNumber) : '';
        $items[$idx]['audit'] = isset($auditContextByReleaseId[$value]) && is_array($auditContextByReleaseId[$value])
            ? $auditContextByReleaseId[$value]
            : $existingAudit;
    }

    return $items;
}
TXT,
        ],
        [
            'label' => 'no silent invalid-snapshot fallback',
            'old' => <<<'TXT'
function st_find_snapshot_item(array $items, string $value): array
{
    foreach ($items as $item) {
        if ((string)($item['value'] ?? '') === $value) {
            return $item;
        }
    }
    return !empty($items) ? $items[0] : [];
}
TXT,
            'new' => <<<'TXT'
function st_find_snapshot_item(array $items, string $value): array
{
    foreach ($items as $item) {
        if ((string)($item['value'] ?? '') === $value) {
            return $item;
        }
    }
    return [];
}
TXT,
        ],
        [
            'label' => 'merge canonical and manual timeline entries',
            'old' => <<<'TXT'
$auditContextByReleaseId = st_audit_context_by_release_id($yearFolder);
$snapshotItems = st_enrich_snapshot_items(st_collect_snapshots($pointRaces), $auditContextByReleaseId);
TXT,
            'new' => <<<'TXT'
$auditContextByReleaseId = st_audit_context_by_release_id($yearFolder);
$canonicalSnapshotItems = st_collect_snapshots($pointRaces);
$manualSnapshotItems = st_collect_manual_snapshots($yearFolder, $pointRaces);
$snapshotItems = st_sort_snapshot_items_desc(array_merge($canonicalSnapshotItems, $manualSnapshotItems));
$snapshotItems = st_enrich_snapshot_items($snapshotItems, $auditContextByReleaseId);
TXT,
        ],
        [
            'label' => 'explicit snapshot selection failure handling',
            'old' => <<<'TXT'
$selectedSnapshotValue = isset($_GET['snapshot']) ? trim((string)$_GET['snapshot']) : '';
$selectedSnapshot = st_find_snapshot_item($snapshotItems, $selectedSnapshotValue);
$selectedSnapshotValue = (string)($selectedSnapshot['value'] ?? '');
TXT,
            'new' => <<<'TXT'
$requestedSnapshotValue = isset($_GET['snapshot']) ? trim((string)$_GET['snapshot']) : '';
if ($requestedSnapshotValue === '') {
    $selectedSnapshot = !empty($snapshotItems) ? $snapshotItems[0] : [];
} else {
    $selectedSnapshot = st_find_snapshot_item($snapshotItems, $requestedSnapshotValue);
    if (empty($selectedSnapshot)) {
        http_response_code(404);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>MRL Snapshot Not Found</title></head><body style="font-family:Arial,sans-serif;padding:24px">';
        echo '<h1>Snapshot not found</h1>';
        echo '<p>The requested standings snapshot could not be found: <code>' . st_h($requestedSnapshotValue) . '</code></p>';
        echo '</body></html>';
        exit;
    }
}
$selectedSnapshotValue = (string)($selectedSnapshot['value'] ?? '');
TXT,
        ],
        [
            'label' => 'manual snapshot substitution at effective time',
            'old' => <<<'TXT'
    $snapshotFile = st_latest_snapshot_for_race_asof((string)($race['race_folder'] ?? ''), $selectedSnapshotKey);
    if ($snapshotFile === '') {
        continue;
    }
TXT,
            'new' => <<<'TXT'
    $snapshotFile = st_latest_snapshot_for_race_asof((string)($race['race_folder'] ?? ''), $selectedSnapshotKey);

    if (
        !empty($selectedSnapshot['is_manual'])
        && (string)($race['race_code'] ?? '') === (string)($selectedSnapshot['race_code'] ?? '')
    ) {
        $manualFile = (string)($selectedSnapshot['file'] ?? '');
        if ($manualFile !== '' && is_file($manualFile)) {
            $snapshotFile = $manualFile;
        }
    }

    if ($snapshotFile === '') {
        continue;
    }
TXT,
        ],
    ];
}

function icandidate(string $src, array &$checks): string
{
    foreach (ipatches() as $p) {
        $n = substr_count($src, $p['old']);
        icheck($checks, 'Patch anchor: ' . $p['label'], $n === 1, true, 'matches=' . $n);
        if ($n !== 1) return '';

        $src = str_replace($p['old'], $p['new'], $src, $r);
        if ($r !== 1) return '';
    }

    return $src;
}

function ipre(string $target, string $expectedGitBlobSha): array
{
    $c = [];

    $okDir = (basename(__DIR__) === '_installers' && basename(dirname(__DIR__)) === 'public_html');
    icheck($c, 'Installer running from /public_html/_installers', $okDir, true, __DIR__);
    icheck($c, 'Target exists: race_results/standings_timeline.php', is_file($target), true, $target);
    icheck($c, 'manual_snapshot_override_helper.php exists', is_file(dirname($target) . '/manual_snapshot_override_helper.php'), true, dirname($target) . '/manual_snapshot_override_helper.php');
    icheck($c, 'standings_timeline_lite.php exists', is_file(dirname($target) . '/standings_timeline_lite.php'), true, dirname($target) . '/standings_timeline_lite.php');
    icheck($c, 'race_results folder writable', is_writable(dirname($target)), true, dirname($target));

    $src = is_file($target) ? (string)file_get_contents($target) : '';
    $blobSha = $src !== '' ? igit_blob_sha_from_content($src) : '';
    icheck($c, 'Current standings_timeline.php matches current GitHub blob', $blobSha === $expectedGitBlobSha, true, $blobSha);

    $cand = '';
    if ($src !== '') {
        $cand = icandidate($src, $c);

        if ($cand !== '') {
            $tmp = tempnam(sys_get_temp_dir(), 'mrltl_');
            file_put_contents($tmp, $cand);
            $l = ilint($tmp);
            icheck($c, 'Patched standings_timeline.php php -l', $l['ok'], true, $l['detail']);
            @unlink($tmp);

            icheck($c, 'Candidate standings_timeline.php SHA-256', true, false, hash('sha256', $cand));

            icheck(
                $c,
                'Candidate includes manual helper',
                strpos($cand, "require_once __DIR__ . '/manual_snapshot_override_helper.php';") !== false,
                true,
                'manual helper include present'
            );
            icheck(
                $c,
                'Candidate recognizes manual snapshot filenames',
                strpos($cand, "/^(?:manual_)?snapshot_") !== false,
                true,
                'manual_snapshot_*.html supported'
            );
            icheck(
                $c,
                'Candidate has explicit invalid-snapshot failure',
                strpos($cand, 'Snapshot not found') !== false && strpos($cand, 'http_response_code(404)') !== false,
                true,
                '404 guard present'
            );
        }
    }

    $ok = true;
    foreach ($c as $x) {
        if ($x['required'] && $x['status'] !== 'PASS') {
            $ok = false;
            break;
        }
    }

    return [
        'ok' => $ok,
        'checks' => $c,
        'candidate' => $cand,
        'sha_before' => isha($target),
        'git_blob_sha_before' => $blobSha,
    ];
}

function iapply(string $target, string $backupRoot, string $sessionFile, string $expectedGitBlobSha): array
{
    $pre = ipre($target, $expectedGitBlobSha);
    if (!$pre['ok']) {
        return [
            'ok' => false,
            'stage' => 'PRECHECK',
            'checks' => $pre['checks'],
        ];
    }

    $stamp = date('Ymd_His');
    $bd = $backupRoot . '/install_standings_timeline_manual_support_v007_' . $stamp;
    $bp = $bd . '/race_results/standings_timeline.php';

    if (!is_dir(dirname($bp)) && !mkdir(dirname($bp), 0775, true)) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not create backup directory.'];
    }

    if (!copy($target, $bp)) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not create backup.'];
    }

    if (!iatomic($target, $pre['candidate'])) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not write patched standings_timeline.php.'];
    }

    $after = isha($target);
    $l = ilint($target);

    $s = [
        'installer' => 'install_standings_timeline_manual_support_v007',
        'applied_at' => date('c'),
        'backup_dir' => $bd,
        'backup_path' => $bp,
        'target' => $target,
        'sha_before' => $pre['sha_before'],
        'sha_after' => $after,
        'git_blob_sha_before' => $pre['git_blob_sha_before'],
    ];

    if (!is_dir($backupRoot)) @mkdir($backupRoot, 0775, true);
    iatomic($sessionFile, ij($s) . "\n");

    $c = [];
    icheck($c, 'Installed standings_timeline.php exists', is_file($target), true, $target);
    icheck($c, 'Installed standings_timeline.php SHA changed', $after !== '' && $after !== $pre['sha_before'], true, $after);
    icheck($c, 'Installed standings_timeline.php php -l', $l['ok'], true, $l['detail']);
    icheck($c, 'Backup exists', is_file($bp), true, $bp);

    $installed = is_file($target) ? (string)file_get_contents($target) : '';

    icheck(
        $c,
        'Installed version is v007',
        strpos($installed, ' * VERSION: v007') !== false,
        true,
        'v007'
    );
    icheck(
        $c,
        'Manual timeline collection installed',
        strpos($installed, 'function st_collect_manual_snapshots') !== false,
        true,
        'manual snapshot collection present'
    );
    icheck(
        $c,
        'Manual substitution logic installed',
        strpos($installed, "\$manualFile = (string)(\$selectedSnapshot['file'] ?? '');") !== false,
        true,
        'manual race substitution present'
    );
    icheck(
        $c,
        'Invalid snapshot no longer silently falls back',
        strpos($installed, 'http_response_code(404)') !== false && strpos($installed, 'Snapshot not found') !== false,
        true,
        'explicit 404 behavior present'
    );

    $ok = true;
    foreach ($c as $x) {
        if ($x['required'] && $x['status'] !== 'PASS') {
            $ok = false;
            break;
        }
    }

    return [
        'ok' => $ok,
        'stage' => 'POSTCHECK',
        'checks' => $c,
        'modified_files' => ['race_results/standings_timeline.php'],
        'new_files' => [],
        'session' => $s,
    ];
}

function irollback(string $sessionFile): array
{
    $c = [];

    if (!is_file($sessionFile)) {
        icheck($c, 'Rollback session available', false, true, 'No last installer session found.');
        return ['ok' => false, 'stage' => 'ROLLBACK', 'checks' => $c];
    }

    $s = json_decode((string)file_get_contents($sessionFile), true);
    if (!is_array($s)) $s = [];

    $bp = (string)($s['backup_path'] ?? '');
    $t = (string)($s['target'] ?? '');

    icheck($c, 'Rollback backup exists', is_file($bp), true, $bp);
    icheck($c, 'Rollback target path valid', $t !== '' && basename($t) === 'standings_timeline.php', true, $t);

    if (is_file($bp) && $t !== '') {
        $w = iatomic($t, (string)file_get_contents($bp));
        icheck($c, 'Previous standings_timeline.php restored', $w, true, isha($t));

        $l = ilint($t);
        icheck($c, 'Restored standings_timeline.php php -l', $l['ok'], true, $l['detail']);
    }

    $ok = true;
    foreach ($c as $x) {
        if ($x['required'] && $x['status'] !== 'PASS') {
            $ok = false;
            break;
        }
    }

    return ['ok' => $ok, 'stage' => 'ROLLBACK', 'checks' => $c];
}

$a = (string)($_POST['action'] ?? '');

if ($a === 'apply') {
    $result = iapply($target, $backupRoot, $sessionFile, $expectedGitBlobSha);
} elseif ($a === 'rollback') {
    $result = irollback($sessionFile);
} else {
    $pre = ipre($target, $expectedGitBlobSha);
    $result = [
        'ok' => $pre['ok'],
        'stage' => 'PRECHECK',
        'checks' => $pre['checks'],
        'modified_files' => ['race_results/standings_timeline.php'],
        'new_files' => [],
    ];
}

$report = [
    'installer' => 'install_standings_timeline_manual_support_v007',
    'installer_version' => 'v007',
    'report_stage' => $result['stage'] ?? 'PRECHECK',
    'report_generated_et' => date('c'),
    'scope' => [
        'changes' => [
            'patch race_results/standings_timeline.php only',
            'recognize manual snapshot releases as timeline entries',
            'use manual effective timestamp as timeline cutoff',
            'substitute manual snapshot for its race',
            'preserve canonical ESPN version numbering independently',
            'stop silently falling back when a requested snapshot ID is invalid',
        ],
        'does_not_change' => [
            'weekly_standings.php',
            'standings_timeline_lite.php rendering',
            'manual_snapshot_override_helper.php',
            'admin_manual_snapshot.php',
            'race result snapshots or manual snapshot files',
            'manual activation state',
            'revision monitor',
            'database',
            'cron/scheduler',
            'payouts',
        ],
    ],
    'result' => $result,
];

$status = !empty($result['ok']) ? 'PASS' : 'FAIL';
$banner = ($result['stage'] ?? 'PRECHECK') . ': ' . $status;
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Timeline Manual Support v007 Installer</title>
<style>
body{margin:0;background:#0f0f0f;color:#eee;font:18px Arial;padding:28px}
h1{font-size:34px}
code{color:#ffd34d}
.box{border:1px solid #444;background:#191919;padding:18px;border-radius:12px;margin:14px 0}
.actions{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
button,a.btn{border:1px solid #777;border-radius:9px;padding:10px 18px;color:#fff;font-size:18px;text-decoration:none;cursor:pointer}
.apply{background:#138a3d}.rollback{background:#b63838}.open{background:#2868b2}.copy{background:#555;color:#fff}
pre{white-space:pre-wrap;background:#050505;border:1px solid #444;padding:14px;max-height:580px;overflow:auto;font-size:13px}
.pass{color:#77e59c}.fail{color:#ff7777}
</style>
</head>
<body>
<h1>MRL Timeline Manual Support v007 Installer</h1>
<p>Upload/run from <code>/public_html/_installers/</code>.</p>

<div class="box">
  <div class="actions">
    <form method="post"><input type="hidden" name="action" value="apply"><button class="apply">Apply</button></form>
    <form method="post"><input type="hidden" name="action" value="rollback"><button class="rollback">Rollback</button></form>
    <a class="btn open" href="/race_results/weekly_standings.php?year=2026&race=R35" target="_blank">Open Weekly Standings</a>
  </div>
  <h2 class="<?php echo $status === 'PASS' ? 'pass' : 'fail'; ?>"><?php echo ih($banner); ?></h2>
</div>

<div class="box">
  <button class="copy" type="button" onclick="navigator.clipboard.writeText(document.getElementById('rep').innerText)">Copy Report</button>
  <pre id="rep"><?php echo ih(ij($report)); ?></pre>
</div>
</body>
</html>
