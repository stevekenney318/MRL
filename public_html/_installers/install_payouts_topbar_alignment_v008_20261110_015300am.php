<?php
declare(strict_types=1);

date_default_timezone_set('America/New_York');
session_start();

$docRoot = dirname(__DIR__);
$target = $docRoot . '/race_results/payouts.php';
$backupRoot = $docRoot . '/_installer_backups';
$sessionFile = $backupRoot . '/install_payouts_topbar_alignment_v008_last.json';
$expectedBeforeSha = 'f2d2311eb03ba3d670f567b62330a921789a961847227ad1be0e55bac06cbf60';

function ih($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function ij(array $v): string { return (string)json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); }
function isha(string $p): string { return is_file($p) ? (string)hash_file('sha256', $p) : ''; }

function ilint(string $p): array {
    $o = shell_exec('php -l ' . escapeshellarg($p) . ' 2>&1');
    $o = is_string($o) ? trim($o) : '';
    return [
        'ok' => strpos($o, 'No syntax errors detected') !== false,
        'detail' => $o,
    ];
}

function iatomic(string $p, string $c): bool {
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

function icheck(array &$c, string $l, bool $ok, bool $r, string $d = ''): void {
    $c[] = [
        'label' => $l,
        'status' => $ok ? 'PASS' : 'FAIL',
        'required' => $r,
        'detail' => $d,
    ];
}

function ipatches(): array {
    return [
        [
            'label' => 'version',
            'old' => " * VERSION: v007\n * LAST MODIFIED: 11/10/2026 1:15:00 am ET",
            'new' => " * VERSION: v008\n * LAST MODIFIED: 11/10/2026 1:53:00 am ET",
        ],
        [
            'label' => 'changelog',
            'old' => " * CHANGELOG:\n * v007 (11/10/2026 1:15:00 am ET)",
            'new' => " * CHANGELOG:\n * v008 (11/10/2026 1:53:00 am ET)\n * - LAYOUT: Treats the year/Weekly Standings controls, UNOFFICIAL badge, and export actions as one top-aligned row.\n * - LAYOUT: Uses the export block's current vertical position as the shared reference so all three groups begin on the same line.\n * - PRESERVE: v007 status behavior, conditional Team Payouts note, export filenames, payout calculations, winners, ties, colors, widths, admin/config/scoring/data sources unchanged.\n *\n * v007 (11/10/2026 1:15:00 am ET)",
        ],
        [
            'label' => 'shared top alignment',
            'old' => ".topbar{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:nowrap;margin-bottom:8px}.top-status{display:flex;align-items:center;justify-content:center;flex:0 0 auto}",
            'new' => ".topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-wrap:nowrap;margin-bottom:8px}.controls,.top-status,.export-actions{position:relative;top:3px}.top-status{display:flex;align-items:center;justify-content:center;flex:0 0 auto}",
        ],
        [
            'label' => 'remove export-only offset',
            'old' => ".export-actions{display:grid;grid-template-columns:max-content max-content;gap:7px;position:relative;top:3px}.title-row h1",
            'new' => ".export-actions{display:grid;grid-template-columns:max-content max-content;gap:7px}.title-row h1",
        ],
    ];
}

function icandidate(string $src, array &$checks): string {
    foreach (ipatches() as $p) {
        $n = substr_count($src, $p['old']);
        icheck($checks, 'Patch anchor: ' . $p['label'], $n === 1, true, 'matches=' . $n);
        if ($n !== 1) return '';

        $src = str_replace($p['old'], $p['new'], $src, $r);
        if ($r !== 1) return '';
    }
    return $src;
}

function ipre(string $target, string $sha): array {
    $c = [];

    $okDir = (basename(__DIR__) === '_installers' && basename(dirname(__DIR__)) === 'public_html');
    icheck($c, 'Installer running from /public_html/_installers', $okDir, true, __DIR__);
    icheck($c, 'Target exists: race_results/payouts.php', is_file($target), true, $target);

    $before = isha($target);
    icheck($c, 'Current payouts.php SHA-256 matches expected v007', $before === $sha, true, $before);
    icheck($c, 'race_results folder writable', is_writable(dirname($target)), true, dirname($target));

    $cand = '';
    if (is_file($target)) {
        $src = (string)file_get_contents($target);
        $cand = icandidate($src, $c);

        if ($cand !== '') {
            $tmp = tempnam(sys_get_temp_dir(), 'mrlpo_');
            file_put_contents($tmp, $cand);
            $l = ilint($tmp);
            icheck($c, 'Patched payouts.php php -l', $l['ok'], true, $l['detail']);
            @unlink($tmp);

            icheck($c, 'Candidate payouts.php SHA-256', true, false, hash('sha256', $cand));
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
        'sha_before' => $before,
    ];
}

function iapply(string $target, string $backupRoot, string $sessionFile, string $sha): array {
    $pre = ipre($target, $sha);
    if (!$pre['ok']) {
        return [
            'ok' => false,
            'stage' => 'PRECHECK',
            'checks' => $pre['checks'],
        ];
    }

    $stamp = date('Ymd_His');
    $bd = $backupRoot . '/install_payouts_topbar_alignment_v008_' . $stamp;
    $bp = $bd . '/race_results/payouts.php';

    if (!is_dir(dirname($bp)) && !mkdir(dirname($bp), 0775, true)) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not create backup directory.'];
    }

    if (!copy($target, $bp)) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not create backup.'];
    }

    if (!iatomic($target, $pre['candidate'])) {
        return ['ok' => false, 'stage' => 'APPLY', 'message' => 'Could not write patched payouts.php.'];
    }

    $after = isha($target);
    $l = ilint($target);

    $s = [
        'installer' => 'install_payouts_topbar_alignment_v008',
        'applied_at' => date('c'),
        'backup_dir' => $bd,
        'backup_path' => $bp,
        'target' => $target,
        'sha_before' => $pre['sha_before'],
        'sha_after' => $after,
    ];

    if (!is_dir($backupRoot)) @mkdir($backupRoot, 0775, true);
    iatomic($sessionFile, ij($s) . "\n");

    $c = [];
    icheck($c, 'Installed payouts.php exists', is_file($target), true, $target);
    icheck($c, 'Installed payouts.php SHA changed', $after !== '' && $after !== $sha, true, $after);
    icheck($c, 'Installed payouts.php php -l', $l['ok'], true, $l['detail']);
    icheck($c, 'Backup exists', is_file($bp), true, $bp);

    $installed = is_file($target) ? (string)file_get_contents($target) : '';
    icheck(
        $c,
        'Shared top-alignment rule installed',
        strpos($installed, '.controls,.top-status,.export-actions{position:relative;top:3px}') !== false,
        true,
        'controls + status + exports share top:3px'
    );
    icheck(
        $c,
        'Top bar uses flex-start alignment',
        strpos($installed, '.topbar{display:flex;align-items:flex-start;') !== false,
        true,
        'align-items:flex-start'
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
        'modified_files' => ['race_results/payouts.php'],
        'new_files' => [],
        'session' => $s,
    ];
}

function irollback(string $sessionFile): array {
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
    icheck($c, 'Rollback target path valid', $t !== '' && basename($t) === 'payouts.php', true, $t);

    if (is_file($bp) && $t !== '') {
        $w = iatomic($t, (string)file_get_contents($bp));
        icheck($c, 'Previous payouts.php restored', $w, true, isha($t));

        $l = ilint($t);
        icheck($c, 'Restored payouts.php php -l', $l['ok'], true, $l['detail']);
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
    $result = iapply($target, $backupRoot, $sessionFile, $expectedBeforeSha);
} elseif ($a === 'rollback') {
    $result = irollback($sessionFile);
} else {
    $pre = ipre($target, $expectedBeforeSha);
    $result = [
        'ok' => $pre['ok'],
        'stage' => 'PRECHECK',
        'checks' => $pre['checks'],
        'modified_files' => ['race_results/payouts.php'],
        'new_files' => [],
    ];
}

$report = [
    'installer' => 'install_payouts_topbar_alignment_v008',
    'installer_version' => 'v008',
    'report_stage' => $result['stage'] ?? 'PRECHECK',
    'report_generated_et' => date('c'),
    'scope' => [
        'changes' => [
            'patch race_results/payouts.php only',
            'top-align year/Weekly Standings, UNOFFICIAL, and export actions as one row',
            'preserve export block current vertical position as shared reference',
        ],
        'does_not_change' => [
            'payout calculations or amounts',
            'v007 official/unofficial behavior',
            'v007 conditional Team Payouts note',
            'v007 export filenames',
            'payouts_helper.php',
            'admin_payouts.php',
            'weekly_standings.php',
            'database',
            'cron/scheduler',
            'race snapshots',
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
<title>MRL Payouts v008 Installer</title>
<style>
body{margin:0;background:#0f0f0f;color:#eee;font:18px Arial;padding:28px}
h1{font-size:34px}
code{color:#ffd34d}
.box{border:1px solid #444;background:#191919;padding:18px;border-radius:12px;margin:14px 0}
.actions{display:flex;gap:14px;align-items:center;flex-wrap:wrap}
button,a.btn{border:1px solid #777;border-radius:9px;padding:10px 18px;color:#fff;font-size:18px;text-decoration:none;cursor:pointer}
.apply{background:#138a3d}.rollback{background:#b63838}.open{background:#2868b2}.copy{background:#555}
pre{white-space:pre-wrap;background:#050505;border:1px solid #444;padding:14px;max-height:580px;overflow:auto;font-size:13px}
.pass{color:#77e59c}.fail{color:#ff7777}
</style>
</head>
<body>
<h1>MRL Payouts v008 Installer</h1>
<p>Upload/run from <code>/public_html/_installers/</code>.</p>

<div class="box">
  <div class="actions">
    <form method="post"><input type="hidden" name="action" value="apply"><button class="apply">Apply</button></form>
    <form method="post"><input type="hidden" name="action" value="rollback"><button class="rollback">Rollback</button></form>
    <a class="btn open" href="/race_results/payouts.php" target="_blank">Open Payouts</a>
  </div>
  <h2 class="<?php echo $status === 'PASS' ? 'pass' : 'fail'; ?>"><?php echo ih($banner); ?></h2>
</div>

<div class="box">
  <button class="copy" type="button" onclick="navigator.clipboard.writeText(document.getElementById('rep').innerText)">Copy Report</button>
  <pre id="rep"><?php echo ih(ij($report)); ?></pre>
</div>
</body>
</html>
