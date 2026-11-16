<?php
/**
 * admin_paid_payouts_runtime_check_v001.php
 *
 * VERSION: v001
 * LAST MODIFIED: 11/15/2026 8:35 pm ET
 *
 * PURPOSE:
 * - Read-only runtime diagnostic for admin_paid_payouts.php.
 * - Executes the same startup dependencies one step at a time.
 * - Reports the exact step, file, line, and message when something fails.
 * - Does not INSERT, UPDATE, DELETE, ALTER, CREATE, rename, move, or remove anything.
 *
 * PHP: 7.3 compatible.
 */

declare(strict_types=1);

date_default_timezone_set('America/New_York');

$results = [];
$currentStep = 'startup';
$finishedNormally = false;

function rtc_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rtc_add(array &$results, string $step, bool $ok, string $detail = ''): void
{
    $results[] = [
        'step' => $step,
        'ok' => $ok,
        'detail' => $detail,
    ];
}

function rtc_error_detail(Throwable $e): string
{
    return get_class($e) . ': ' . $e->getMessage()
        . ' | ' . $e->getFile() . ':' . $e->getLine();
}

register_shutdown_function(function () use (&$results, &$currentStep, &$finishedNormally) {
    if ($finishedNormally) {
        return;
    }

    $last = error_get_last();

    header('Content-Type: text/html; charset=UTF-8');

    echo '<!doctype html><html><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>MRL Paid/Payouts Runtime Check</title>';
    echo '<style>
        *{box-sizing:border-box}
        body{margin:0;background:#11161c;color:#e9edf2;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
        .wrap{width:96%;max-width:1200px;margin:24px auto}
        .card{background:#1b232c;border:1px solid #394655;border-radius:12px;padding:18px;margin-bottom:12px}
        h1{margin:0 0 8px;color:#fff2dc}
        h2{color:#ffd08a}
        table{width:100%;border-collapse:collapse}
        th,td{padding:9px 10px;border-bottom:1px solid #34414e;text-align:left;vertical-align:top}
        th{color:#b9c9d9}
        .pass{color:#72e3a3;font-weight:bold}
        .fail{color:#ff939d;font-weight:bold}
        code{white-space:pre-wrap;word-break:break-word;color:#d7e7f7}
        .fatal{background:#452226;border:1px solid #8f424b;border-radius:9px;padding:12px;color:#ffb0b7}
    </style></head><body><div class="wrap">';
    echo '<div class="card"><h1>MRL Paid/Payouts Runtime Check</h1>';
    echo '<div class="fatal"><strong>Runtime stopped during:</strong> ' . rtc_h($currentStep) . '<br>';

    if (is_array($last)) {
        echo '<strong>Fatal:</strong> ' . rtc_h($last['message'] ?? '') . '<br>';
        echo '<strong>File:</strong> ' . rtc_h($last['file'] ?? '') . '<br>';
        echo '<strong>Line:</strong> ' . rtc_h($last['line'] ?? '');
    } else {
        echo 'The request ended unexpectedly, but PHP did not provide a final error record.';
    }

    echo '</div></div>';

    if (!empty($results)) {
        echo '<div class="card"><h2>Completed steps</h2><table>';
        echo '<tr><th>Status</th><th>Step</th><th>Detail</th></tr>';
        foreach ($results as $r) {
            echo '<tr><td class="' . ($r['ok'] ? 'pass' : 'fail') . '">'
                . ($r['ok'] ? 'PASS' : 'FAIL') . '</td><td>'
                . rtc_h($r['step']) . '</td><td><code>'
                . rtc_h($r['detail']) . '</code></td></tr>';
        }
        echo '</table></div>';
    }

    echo '</div></body></html>';
});

function rtc_step(array &$results, string &$currentStep, string $label, callable $fn)
{
    $currentStep = $label;
    try {
        $detail = $fn();
        if (is_array($detail) || is_object($detail)) {
            $detail = json_encode($detail, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($detail === null) {
            $detail = '';
        } elseif (is_bool($detail)) {
            $detail = $detail ? 'true' : 'false';
        }
        rtc_add($results, $label, true, (string)$detail);
        return true;
    } catch (Throwable $e) {
        rtc_add($results, $label, false, rtc_error_detail($e));
        return false;
    }
}

$root = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/\\') : '';

rtc_step($results, $currentStep, 'PHP runtime', function () {
    return 'PHP ' . PHP_VERSION . ' | SAPI ' . PHP_SAPI;
});

rtc_step($results, $currentStep, 'Document root', function () use ($root) {
    if ($root === '') {
        throw new RuntimeException('DOCUMENT_ROOT is empty.');
    }
    return $root;
});

$requiredFiles = [
    'config.php' => $root . '/config.php',
    'config_mrl.php' => $root . '/config_mrl.php',
    'class.user.php' => $root . '/class.user.php',
    'mrl_theme_helper.php' => $root . '/mrl_team/mrl_theme_helper.php',
    'payouts_helper.php' => $root . '/race_results/payouts_helper.php',
    'admin_paid_payouts_helper.php' => $root . '/admin_paid_payouts_helper.php',
];

foreach ($requiredFiles as $name => $path) {
    rtc_step($results, $currentStep, 'File exists: ' . $name, function () use ($path) {
        if (!is_file($path)) {
            throw new RuntimeException('Missing file: ' . $path);
        }
        if (!is_readable($path)) {
            throw new RuntimeException('File is not readable: ' . $path);
        }
        return $path;
    });
}

if (!rtc_step($results, $currentStep, 'require config.php', function () use ($root) {
    require_once $root . '/config.php';
    return 'loaded';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'require config_mrl.php', function () use ($root) {
    require_once $root . '/config_mrl.php';
    return 'loaded';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'PDO connection available', function () {
    global $dbo;
    if (!isset($dbo) || !($dbo instanceof PDO)) {
        throw new RuntimeException('$dbo is not an active PDO connection.');
    }
    return 'PDO ready';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'require class.user.php', function () use ($root) {
    require_once $root . '/class.user.php';
    return 'loaded';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'require mrl_theme_helper.php', function () use ($root) {
    require_once $root . '/mrl_team/mrl_theme_helper.php';
    return 'loaded';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'require race_results/payouts_helper.php', function () use ($root) {
    require_once $root . '/race_results/payouts_helper.php';
    return 'loaded';
})) {
    goto render_results;
}

if (!rtc_step($results, $currentStep, 'require admin_paid_payouts_helper.php', function () use ($root) {
    require_once $root . '/admin_paid_payouts_helper.php';
    return 'loaded';
})) {
    goto render_results;
}

rtc_step($results, $currentStep, 'Expected functions available', function () {
    $need = [
        'isAdmin',
        'mrlp_available_years',
        'mrlp_compute_year',
        'mrlp_load_config',
        'mrlp_year_config',
        'mrlp_build_payout_model',
        'app_years',
        'app_current_fee',
        'app_fee_history',
        'app_team_rows',
        'app_load_team_finance',
        'app_prizes_by_team',
        'app_calc',
    ];

    $missing = [];
    foreach ($need as $fn) {
        if (!function_exists($fn)) {
            $missing[] = $fn;
        }
    }

    if (!empty($missing)) {
        throw new RuntimeException('Missing functions: ' . implode(', ', $missing));
    }

    return 'all expected functions found';
});

rtc_step($results, $currentStep, 'USER object can be created', function () {
    if (!class_exists('USER')) {
        throw new RuntimeException('USER class is missing.');
    }
    $u = new USER();
    return 'USER object created';
});

$payoutYears = [];
rtc_step($results, $currentStep, 'Read payout years', function () use ($root, &$payoutYears) {
    $payoutYears = mrlp_available_years($root . '/race_results');
    return 'count=' . count($payoutYears) . ' | ' . implode(', ', $payoutYears);
});

$years = [];
rtc_step($results, $currentStep, 'Build admin year list', function () use (&$years, &$payoutYears) {
    global $dbo, $raceYear;
    $years = app_years($dbo, $payoutYears, (string)($raceYear ?? ''));
    return 'count=' . count($years) . ' | ' . implode(', ', $years);
});

$year = !empty($years) ? (string)$years[0] : date('Y');

rtc_step($results, $currentStep, 'Read current annual fee', function () use ($year) {
    global $dbo;
    $r = app_current_fee($dbo, $year);
    return $r;
});

rtc_step($results, $currentStep, 'Read annual fee history', function () use ($year) {
    global $dbo;
    $r = app_fee_history($dbo, $year);
    return 'rows=' . count($r);
});

$score = [];
if (!rtc_step($results, $currentStep, 'Compute payout scoring model', function () use ($year, &$score) {
    global $dbo, $dbconnect;
    $score = mrlp_compute_year($year, $dbo ?? null, $dbconnect ?? null);
    return 'year=' . $year . ' | races=' . count($score['races'] ?? []);
})) {
    goto render_results;
}

$yearCfg = [];
rtc_step($results, $currentStep, 'Load payout configuration', function () use ($year, &$yearCfg) {
    $yearCfg = mrlp_year_config(mrlp_load_config(), $year);
    return 'loaded for ' . $year;
});

$payoutModel = [];
if (!rtc_step($results, $currentStep, 'Build payout model', function () use (&$score, &$yearCfg, &$payoutModel) {
    $payoutModel = mrlp_build_payout_model($score, $yearCfg);
    return 'awarded_total=' . (string)($payoutModel['awarded_total'] ?? '');
})) {
    goto render_results;
}

rtc_step($results, $currentStep, 'Build prize totals by team', function () use (&$payoutModel) {
    $p = app_prizes_by_team($payoutModel);
    return 'teams with prizes=' . count($p);
});

$teams = [];
rtc_step($results, $currentStep, 'Read team rows', function () use ($year, &$teams) {
    global $dbo;
    $teams = app_team_rows($dbo, $year);
    return 'teams=' . count($teams);
});

rtc_step($results, $currentStep, 'Read team financial records', function () use ($year, &$teams) {
    global $dbo;
    $count = 0;
    foreach ($teams as $t) {
        $uid = (int)($t['userID'] ?? 0);
        if ($uid <= 0) {
            continue;
        }
        app_load_team_finance($dbo, $year, $uid);
        $count++;
    }
    return 'records checked=' . $count;
});

render_results:

$finishedNormally = true;
$currentStep = 'complete';

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Paid/Payouts Runtime Check</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#11161c;color:#e9edf2;font-family:Tahoma,Verdana,Segoe UI,sans-serif}
.wrap{width:96%;max-width:1200px;margin:24px auto}
.card{background:#1b232c;border:1px solid #394655;border-radius:12px;padding:18px;margin-bottom:12px}
h1{margin:0 0 8px;color:#fff2dc}
.sub{color:#aeb9c5;margin-bottom:8px}
table{width:100%;border-collapse:collapse}
th,td{padding:9px 10px;border-bottom:1px solid #34414e;text-align:left;vertical-align:top}
th{color:#b9c9d9}
.pass{color:#72e3a3;font-weight:bold}
.fail{color:#ff939d;font-weight:bold}
code{white-space:pre-wrap;word-break:break-word;color:#d7e7f7}
.note{background:#152334;border:1px solid #315274;border-radius:8px;padding:10px 12px;color:#b9d9f5}
</style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>MRL Paid/Payouts Runtime Check</h1>
        <div class="sub">Read-only diagnostic. Nothing on the site or in the database is changed.</div>
        <div class="note">Open this page once, then send a screenshot of the first FAIL row and its Detail.</div>
    </div>

    <div class="card">
        <table>
            <tr>
                <th>Status</th>
                <th>Step</th>
                <th>Detail</th>
            </tr>
            <?php foreach ($results as $r): ?>
            <tr>
                <td class="<?php echo $r['ok'] ? 'pass' : 'fail'; ?>">
                    <?php echo $r['ok'] ? 'PASS' : 'FAIL'; ?>
                </td>
                <td><?php echo rtc_h($r['step']); ?></td>
                <td><code><?php echo rtc_h($r['detail']); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
