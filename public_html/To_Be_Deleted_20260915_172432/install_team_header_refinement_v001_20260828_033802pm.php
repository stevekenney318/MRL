<?php
declare(strict_types=1);

/**
 * install_team_header_refinement_v001.php
 *
 * VERSION: v001
 * LAST MODIFIED: 8/28/2026 3:38:02 pm
 *
 * PURPOSE:
 * Refine only the production team.php sticky masthead:
 * - change the user icon from person to checkered flag
 * - restore the banner close to its original compact height
 * - use a traditional serif masthead font across the banner
 * - use normal (not bold) weight throughout the banner text
 *
 * TARGET:
 *   /team.php v036 -> v037
 *
 * SAFETY:
 * - strict v036/version/signature preflight
 * - every patch anchor must exist exactly once
 * - backup before replacement
 * - postflight signature checks
 * - rollback on failure
 * - no announcement, panel-memory, menu, theme, pick, LP, RP/RD,
 *   scoring, chart, scheduler, profile, JSON, or DB changes
 *
 * LOCATION:
 * Put this installer in public_html/.
 */

date_default_timezone_set('America/New_York');

$target = __DIR__ . '/team.php';

function ih(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function row(string $label, bool $ok, string $detail=''): void {
    echo '<tr><td>'.ih($label).'</td><td class="'.($ok?'ok':'bad').'">'.($ok?'PASS':'FAIL').'</td><td>'.ih($detail).'</td></tr>';
}

function atomic_write(string $path, string $data): bool {
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $data, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function replace_once(string $source, string $old, string $new, string $label, array &$log): ?string {
    $count = substr_count($source, $old);
    if ($count !== 1) {
        $log[] = 'PATCH FAIL: ' . $label . ' expected 1 anchor, found ' . $count . '.';
        return null;
    }
    $log[] = 'PATCH PASS: ' . $label . '.';
    return str_replace($old, $new, $source);
}

function build_v037(string $source, array &$log): ?string {
    $s = $source;

    $pairs = [
        [
            " * VERSION: v036\n * LAST MODIFIED: 8/28/2026 3:09:01 pm",
            " * VERSION: v037\n * LAST MODIFIED: 8/28/2026 3:38:02 pm",
            'version header'
        ],
        [
            " * CHANGELOG:\n *\n",
            " * CHANGELOG:\n *\n"
            . " * v037 (8/28/2026 3:38:02 pm)\n"
            . " * - UI: Replaces the user/person icon with a checkered flag.\n"
            . " * - UI: Returns the sticky masthead to approximately its original compact height.\n"
            . " * - UI: Uses Georgia / Times-style serif typography across the masthead.\n"
            . " * - UI: Uses normal font weight across user, title, subtitle, clock and date text.\n"
            . " * - PRESERVE: v036 panel-state memory, announcement/news panel, themes, menus, charts,\n"
            . " *             normal picks, LP, RP/RD, scoring, View-As, profile, scheduler and DB behavior.\n"
            . " *\n",
            'v037 changelog'
        ],
        [
            "            min-height:70px;\n            display:grid;",
            "            min-height:58px;\n            display:grid;",
            'compact masthead height'
        ],
        [
            "            padding:10px 16px;",
            "            padding:8px 14px;",
            'compact masthead padding'
        ],
        [
            "            font:600 18px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:400 18px/1.15 Georgia,\"Times New Roman\",serif;",
            'user button typography'
        ],
        [
            "            font:800 24px/1.15 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:400 24px/1.15 Georgia,\"Times New Roman\",serif;",
            'center title typography'
        ],
        [
            "            font-size:14px;\n            font-weight:700;",
            "            font-size:14px;\n            font-weight:400;\n            font-family:Georgia,\"Times New Roman\",serif;",
            'center subtitle typography'
        ],
        [
            "            font:700 17px/1.2 Tahoma,Verdana,Segoe UI,sans-serif;",
            "            font:400 17px/1.2 Georgia,\"Times New Roman\",serif;",
            'clock typography'
        ],
        [
            "            font-size:13px;\n            font-weight:600;",
            "            font-size:13px;\n            font-weight:400;\n            font-family:Georgia,\"Times New Roman\",serif;",
            'date typography'
        ],
        [
            "                👤 <?php echo teampage_h($first_name); ?> ▾",
            "                🏁 <?php echo teampage_h($first_name); ?> ▾",
            'checkered flag icon'
        ],
    ];

    foreach ($pairs as $pair) {
        [$old, $new, $label] = $pair;
        $next = replace_once($s, $old, $new, $label, $log);
        if ($next === null) return null;
        $s = $next;
    }

    return $s;
}

$exists = is_file($target);
$current = $exists ? (string)file_get_contents($target) : '';

$baselineOk =
    $exists
    && strpos($current, 'VERSION: v036') !== false
    && strpos($current, 'LAST MODIFIED: 8/28/2026 3:09:01 pm') !== false
    && strpos($current, 'min-height:70px;') !== false
    && strpos($current, 'padding:10px 16px;') !== false
    && strpos($current, '👤 <?php echo teampage_h($first_name); ?> ▾') !== false
    && strpos($current, 'mrl.team.adminMenu') !== false
    && strpos($current, 'mrl-rd-announcement') !== false;

$patchLog = [];
$replacement = $baselineOk ? build_v037($current, $patchLog) : null;

$replacementOk =
    is_string($replacement)
    && strpos($replacement, 'VERSION: v037') !== false
    && strpos($replacement, 'min-height:58px;') !== false
    && strpos($replacement, 'padding:8px 14px;') !== false
    && strpos($replacement, '🏁 <?php echo teampage_h($first_name); ?> ▾') !== false
    && strpos($replacement, 'font:400 24px/1.15 Georgia,"Times New Roman",serif;') !== false
    && strpos($replacement, 'font:400 17px/1.2 Georgia,"Times New Roman",serif;') !== false
    && strpos($replacement, 'mrl.team.adminMenu') !== false
    && strpos($replacement, 'mrl-rd-announcement') !== false;

$preflightOk = $baselineOk && $replacementOk;

$apply = isset($_POST['apply']) && $_POST['apply'] === '1';
$messages = [];
$success = false;

if ($apply && $preflightOk) {
    $backupDir = __DIR__ . '/_migration_backups/team_header_refinement_' . date('Ymd_His');
    $ok = is_dir($backupDir) || mkdir($backupDir, 0755, true);

    if (!$ok) {
        $messages[] = 'FAIL: Could not create backup directory.';
    }

    if ($ok && !copy($target, $backupDir . '/team.php')) {
        $ok = false;
        $messages[] = 'FAIL: Could not back up team.php v036.';
    } elseif ($ok) {
        $messages[] = 'PASS: Backed up team.php v036.';
    }

    if ($ok && !atomic_write($target, (string)$replacement)) {
        $ok = false;
        $messages[] = 'FAIL: Could not install team.php v037.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed team.php v037.';
    }

    if ($ok) {
        $installed = (string)file_get_contents($target);
        $checks = [
            'v037 header' => strpos($installed, 'VERSION: v037') !== false,
            'compact 58px masthead height' => strpos($installed, 'min-height:58px;') !== false,
            'compact 8px/14px masthead padding' => strpos($installed, 'padding:8px 14px;') !== false,
            'checkered flag user icon' => strpos($installed, '🏁 <?php echo teampage_h($first_name); ?> ▾') !== false,
            'normal-weight serif user text' => strpos($installed, 'font:400 18px/1.15 Georgia,"Times New Roman",serif;') !== false,
            'normal-weight serif center title' => strpos($installed, 'font:400 24px/1.15 Georgia,"Times New Roman",serif;') !== false,
            'normal-weight serif clock' => strpos($installed, 'font:400 17px/1.2 Georgia,"Times New Roman",serif;') !== false,
            'v036 Admin panel memory retained' => strpos($installed, 'mrl.team.adminMenu') !== false,
            'v036 Previous Years memory retained' => strpos($installed, 'mrl.team.previousYears') !== false,
            'v036 announcement panel retained' => strpos($installed, 'mrl-rd-announcement') !== false,
        ];

        foreach ($checks as $label => $pass) {
            $messages[] = ($pass ? 'PASS: ' : 'FAIL: ') . $label;
            if (!$pass) $ok = false;
        }
    }

    if (!$ok && is_file($backupDir . '/team.php')) {
        if (copy($backupDir . '/team.php', $target)) {
            $messages[] = 'ROLLBACK: Restored team.php v036.';
        } else {
            $messages[] = 'ROLLBACK ERROR: Could not restore team.php v036.';
        }
    } else {
        $success = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install Team Header Refinement</title>
<style>
*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,"Segoe UI",sans-serif}
.wrap{width:94%;max-width:1100px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444;vertical-align:top}
.ok{color:#61e493}.bad{color:#ff7777}button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800;cursor:pointer}
li{line-height:1.45;margin-bottom:5px}a,code{color:#76cfff}
</style>
</head>
<body><div class="wrap">

<div class="card">
<h1>Team Header Refinement</h1>
<p>Compact masthead + checkered flag + normal-weight serif typography.</p>
</div>

<div class="card"><h2>Preflight</h2><table>
<?php row('Expected production team.php v036 baseline', $baselineOk, $exists ? 'v036 convenience upgrade signatures found.' : 'team.php missing'); ?>
<?php row('v037 patch builds cleanly', $replacementOk, implode(' | ', $patchLog)); ?>
</table></div>

<?php if ($preflightOk): ?>
<div class="card"><h2>What changes</h2><ul>
<li>👤 becomes 🏁.</li>
<li>Banner height returns close to the original 58px presentation.</li>
<li>Banner padding returns to the original 8px vertical / 14px horizontal spacing.</li>
<li>User name, MRL title, My Team Page subtitle, clock and date use Georgia / Times-style serif text.</li>
<li>All of that masthead text uses normal weight instead of bold.</li>
<li>No other Team-page behavior is changed.</li>
</ul>
<?php if (!$apply): ?><form method="post"><input type="hidden" name="apply" value="1"><button>Install Header Refinement</button></form><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($apply): ?>
<div class="card"><h2>Apply Result</h2>
<p class="<?php echo $success?'ok':'bad'; ?>"><strong><?php echo $success?'SUCCESS':'FAILED / ROLLED BACK'; ?></strong></p>
<ul><?php foreach($messages as $m): ?><li><?php echo ih($m); ?></li><?php endforeach; ?></ul>
<?php if ($success): ?><p><a href="/team.php" target="_blank">Open Team Page v037</a></p><?php endif; ?>
</div>
<?php endif; ?>

</div></body></html>
