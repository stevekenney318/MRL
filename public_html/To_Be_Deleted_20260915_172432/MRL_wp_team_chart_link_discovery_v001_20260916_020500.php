<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * WordPress Historical Team Chart Link Discovery
 *
 * VERSION: v001
 * CREATED: 9/16/2026 2:05:00 am EDT
 *
 * READ-ONLY.
 *
 * PURPOSE
 * -------
 * Find WordPress-stored references to the official 2017-2025 S1-S4 Team Chart
 * files that were moved from the site root into /team_charts/.
 *
 * This utility:
 * - uses WordPress' own database connection ($wpdb)
 * - makes NO database changes
 * - does NOT expose DB credentials
 * - searches post/page content, post meta, and options
 * - distinguishes plain text from serialized values
 * - reports exact old/new URL candidates
 * - can export a JSON report for the migration step
 */

declare(ticks=1);
date_default_timezone_set('America/New_York');

const MRL_DISCOVERY_VERSION = 'v001';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$wpLoad = $root . DIRECTORY_SEPARATOR . 'wp-load.php';

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function is_serialized_value($value): bool {
    if (!is_string($value)) return false;
    $value = trim($value);
    if ($value === 'N;') return true;
    if (strlen($value) < 4 || $value[1] !== ':') return false;
    $last = substr($value, -1);
    if ($last !== ';' && $last !== '}') return false;
    $token = $value[0];
    if (!in_array($token, ['s','a','O','b','i','d'], true)) return false;
    return @unserialize($value, ['allowed_classes' => false]) !== false || $value === 'b:0;';
}

function build_chart_names(): array {
    $names = [];
    for ($year = 2017; $year <= 2025; $year++) {
        for ($s = 1; $s <= 4; $s++) {
            $names[] = $year . '_S' . $s . '_Team_chart.php';
        }
    }
    return $names;
}

function make_variants(string $name): array {
    return [
        '/' . $name,
        'https://manliusracingleague.com/' . $name,
        'http://manliusracingleague.com/' . $name,
        'https://www.manliusracingleague.com/' . $name,
        'http://www.manliusracingleague.com/' . $name,
    ];
}

function new_url_for_variant(string $variant, string $name): string {
    if (strpos($variant, 'https://www.manliusracingleague.com/') === 0) {
        return 'https://www.manliusracingleague.com/team_charts/' . $name;
    }
    if (strpos($variant, 'http://www.manliusracingleague.com/') === 0) {
        return 'http://www.manliusracingleague.com/team_charts/' . $name;
    }
    if (strpos($variant, 'https://manliusracingleague.com/') === 0) {
        return 'https://manliusracingleague.com/team_charts/' . $name;
    }
    if (strpos($variant, 'http://manliusracingleague.com/') === 0) {
        return 'http://manliusracingleague.com/team_charts/' . $name;
    }
    return '/team_charts/' . $name;
}

$charts = build_chart_names();
$run = isset($_GET['scan']) || isset($_GET['export']);
$report = null;
$error = null;

if ($run) {
    if (!is_file($wpLoad)) {
        $error = 'wp-load.php was not found at production root.';
    } else {
        require_once $wpLoad;

        global $wpdb;

        if (!isset($wpdb) || !is_object($wpdb)) {
            $error = 'WordPress database object ($wpdb) was not available.';
        } else {
            $hits = [];
            $summary = [
                'official_chart_files' => count($charts),
                'posts_content_matches' => 0,
                'postmeta_matches' => 0,
                'options_matches' => 0,
                'serialized_matches' => 0,
                'unique_records' => 0,
            ];

            $seenRecords = [];

            foreach ($charts as $name) {
                $variants = make_variants($name);

                foreach ($variants as $old) {
                    $like = '%' . $wpdb->esc_like($old) . '%';
                    $new = new_url_for_variant($old, $name);

                    // wp_posts.post_content
                    $sql = $wpdb->prepare(
                        "SELECT ID, post_type, post_status, post_title, post_name, post_content
                         FROM {$wpdb->posts}
                         WHERE post_content LIKE %s
                         ORDER BY ID",
                        $like
                    );
                    $rows = $wpdb->get_results($sql, ARRAY_A);

                    foreach ($rows as $row) {
                        $count = substr_count((string)$row['post_content'], $old);
                        if ($count < 1) continue;

                        $key = 'posts:' . $row['ID'] . ':post_content:' . $old;
                        if (isset($seenRecords[$key])) continue;
                        $seenRecords[$key] = true;

                        $hits[] = [
                            'storage' => 'wp_posts',
                            'record_id' => (int)$row['ID'],
                            'field' => 'post_content',
                            'post_type' => $row['post_type'],
                            'post_status' => $row['post_status'],
                            'title' => $row['post_title'],
                            'slug' => $row['post_name'],
                            'chart_file' => $name,
                            'old' => $old,
                            'new' => $new,
                            'occurrences' => $count,
                            'serialized' => false,
                        ];
                        $summary['posts_content_matches'] += $count;
                    }

                    // wp_postmeta.meta_value
                    $sql = $wpdb->prepare(
                        "SELECT meta_id, post_id, meta_key, meta_value
                         FROM {$wpdb->postmeta}
                         WHERE meta_value LIKE %s
                         ORDER BY meta_id",
                        $like
                    );
                    $rows = $wpdb->get_results($sql, ARRAY_A);

                    foreach ($rows as $row) {
                        $value = (string)$row['meta_value'];
                        $count = substr_count($value, $old);
                        if ($count < 1) continue;

                        $serialized = is_serialized_value($value);
                        $key = 'postmeta:' . $row['meta_id'] . ':meta_value:' . $old;
                        if (isset($seenRecords[$key])) continue;
                        $seenRecords[$key] = true;

                        $hits[] = [
                            'storage' => 'wp_postmeta',
                            'record_id' => (int)$row['meta_id'],
                            'post_id' => (int)$row['post_id'],
                            'field' => 'meta_value',
                            'meta_key' => $row['meta_key'],
                            'chart_file' => $name,
                            'old' => $old,
                            'new' => $new,
                            'occurrences' => $count,
                            'serialized' => $serialized,
                        ];
                        $summary['postmeta_matches'] += $count;
                        if ($serialized) $summary['serialized_matches']++;
                    }

                    // wp_options.option_value
                    $sql = $wpdb->prepare(
                        "SELECT option_id, option_name, option_value, autoload
                         FROM {$wpdb->options}
                         WHERE option_value LIKE %s
                         ORDER BY option_id",
                        $like
                    );
                    $rows = $wpdb->get_results($sql, ARRAY_A);

                    foreach ($rows as $row) {
                        $value = (string)$row['option_value'];
                        $count = substr_count($value, $old);
                        if ($count < 1) continue;

                        $serialized = is_serialized_value($value);
                        $key = 'options:' . $row['option_id'] . ':option_value:' . $old;
                        if (isset($seenRecords[$key])) continue;
                        $seenRecords[$key] = true;

                        $hits[] = [
                            'storage' => 'wp_options',
                            'record_id' => (int)$row['option_id'],
                            'field' => 'option_value',
                            'option_name' => $row['option_name'],
                            'autoload' => $row['autoload'],
                            'chart_file' => $name,
                            'old' => $old,
                            'new' => $new,
                            'occurrences' => $count,
                            'serialized' => $serialized,
                        ];
                        $summary['options_matches'] += $count;
                        if ($serialized) $summary['serialized_matches']++;
                    }
                }
            }

            $summary['unique_records'] = count($hits);

            usort($hits, function ($a, $b) {
                $sa = $a['storage'];
                $sb = $b['storage'];
                if ($sa !== $sb) return strcmp($sa, $sb);
                if ($a['record_id'] !== $b['record_id']) return $a['record_id'] <=> $b['record_id'];
                return strcmp($a['chart_file'], $b['chart_file']);
            });

            $report = [
                'tool' => 'MRL WordPress Historical Team Chart Link Discovery',
                'version' => MRL_DISCOVERY_VERSION,
                'generated_at' => date(DATE_ATOM),
                'root' => $root,
                'read_only' => true,
                'table_prefix' => isset($wpdb->prefix) ? $wpdb->prefix : null,
                'summary' => $summary,
                'hits' => $hits,
            ];

            if (($_GET['export'] ?? '') === 'json') {
                header('Content-Type: application/json; charset=UTF-8');
                header(
                    'Content-Disposition: attachment; filename="MRL_wp_team_chart_link_discovery_' .
                    date('Ymd_His') . '.json"'
                );
                echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL WordPress Team Chart Link Discovery</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1280px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.readonly{padding:11px 13px;margin-bottom:11px;border:1px solid #2f9a61;border-radius:10px;background:#103b27;color:#effff5;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:9px}
.card{padding:10px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
.actions{display:flex;gap:9px;flex-wrap:wrap}
.btn,button{display:inline-block;min-height:36px;padding:8px 13px;border:0;border-radius:7px;color:#fff;font-weight:800;text-decoration:none;cursor:pointer}
.scan{background:#2674a8}.export{background:#bd8320}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
code{color:#f8d89a;overflow-wrap:anywhere}
.serial{color:#ffd391;font-weight:800}
.plain{color:#8fe0a9}
.err{border-color:#a65353;background:#3d1d1d}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL WordPress Team Chart Link Discovery</h1>

<div class="readonly">
READ ONLY — uses WordPress' own database connection. No rows are modified and no DB credentials are displayed.
</div>

<div class="panel">
<h2>Scope</h2>
<p>
Search the WordPress database for old root-level references to the 36 official Team Chart files:
<strong>2017–2025, S1–S4</strong>. The proposed replacement is the same filename under
<code>/team_charts/</code>.
</p>
<div class="actions">
<form method="get" style="margin:0">
<input type="hidden" name="scan" value="1">
<button class="scan" type="submit"><?php echo $report ? 'Rescan WordPress' : 'Run Read-Only Scan'; ?></button>
</form>
<?php if ($report): ?>
<a class="btn export" href="?export=json">Export JSON Report</a>
<?php endif; ?>
</div>
</div>

<?php if ($error): ?>
<div class="panel err">
<h2>Unable to Scan</h2>
<p><?php echo h($error); ?></p>
</div>
<?php endif; ?>

<?php if ($report): ?>
<div class="panel">
<h2>At a Glance</h2>
<div class="grid">
<div class="card"><span class="small">Official chart files</span><span class="value"><?php echo number_format($report['summary']['official_chart_files']); ?></span></div>
<div class="card"><span class="small">Post/Page content hits</span><span class="value"><?php echo number_format($report['summary']['posts_content_matches']); ?></span></div>
<div class="card"><span class="small">Post Meta hits</span><span class="value"><?php echo number_format($report['summary']['postmeta_matches']); ?></span></div>
<div class="card"><span class="small">Options hits</span><span class="value"><?php echo number_format($report['summary']['options_matches']); ?></span></div>
<div class="card"><span class="small">Serialized records</span><span class="value"><?php echo number_format($report['summary']['serialized_matches']); ?></span></div>
</div>
</div>

<div class="panel">
<h2>Discovered References</h2>
<?php if (!$report['hits']): ?>
<p>No old root-level references to the 36 official Team Chart URLs were found in posts, post meta, or options.</p>
<?php else: ?>
<table>
<tr>
<th>Storage</th>
<th>Record</th>
<th>Context</th>
<th>Old → New</th>
<th>Count</th>
<th>Serialized?</th>
</tr>
<?php foreach ($report['hits'] as $hit): ?>
<tr>
<td><code><?php echo h($hit['storage']); ?></code></td>
<td><?php echo h($hit['record_id']); ?></td>
<td>
<?php if ($hit['storage'] === 'wp_posts'): ?>
<strong><?php echo h($hit['title']); ?></strong><br>
<span class="small"><?php echo h($hit['post_type']); ?> / <?php echo h($hit['post_status']); ?> / slug: <?php echo h($hit['slug']); ?></span>
<?php elseif ($hit['storage'] === 'wp_postmeta'): ?>
<span class="small">post_id <?php echo h($hit['post_id']); ?></span><br>
<code><?php echo h($hit['meta_key']); ?></code>
<?php else: ?>
<code><?php echo h($hit['option_name']); ?></code><br>
<span class="small">autoload: <?php echo h($hit['autoload']); ?></span>
<?php endif; ?>
</td>
<td>
<code><?php echo h($hit['old']); ?></code><br>
→ <code><?php echo h($hit['new']); ?></code>
</td>
<td><?php echo number_format((int)$hit['occurrences']); ?></td>
<td class="<?php echo $hit['serialized'] ? 'serial' : 'plain'; ?>">
<?php echo $hit['serialized'] ? 'YES' : 'NO'; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</div>

<div class="panel small">
<strong>Next step:</strong> Export the JSON report and send it back here. The actual updater will be built from the exact records found.
If serialized values exist, the updater will use serialization-aware handling rather than a blind SQL replace.
</div>
<?php endif; ?>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 2:05:00 am EDT
</div>

</div></body></html>
