<?php
declare(strict_types=1);

/**
 * Manlius Racing League
 * WordPress Historical Team Chart Link Migration
 *
 * VERSION: v001
 * CREATED: 9/16/2026 2:35:00 am EDT
 *
 * SOURCE:
 * MRL_wp_team_chart_link_discovery_20260916_061820.json
 *
 * PURPOSE
 * -------
 * Update CURRENT/LIVE WordPress records that still point to official historical
 * Team Chart files at the old site root.
 *
 * Old:
 *   /2019_S2_Team_chart.php
 * New:
 *   /team_charts/2019_S2_Team_chart.php
 *
 * Absolute http/https links are handled by replacing only the root path segment,
 * so their original scheme/host is preserved automatically.
 *
 * IMPORTANT SCOPE DECISION
 * ------------------------
 * Historical WordPress revision rows are intentionally NOT rewritten. They are
 * revision history, not live page content. The postflight reports them as INFO.
 *
 * SAFETY
 * ------
 * - Uses WordPress' own DB connection.
 * - Exact record IDs from the read-only discovery report.
 * - Exact expected old-path occurrence counts before Install is enabled.
 * - Full JSON backup of every affected DB field before any update.
 * - Automatic restore attempt if any update fails.
 * - Rollback restores exact original values from the backup.
 * - No blind database-wide REPLACE.
 * - No serialized records are in the approved live set.
 * - WP caches are cleared for touched posts/meta.
 * - PHP 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

const MRL_INSTALLER_VERSION = 'v001';
const MRL_BACKUP_DIR = '_mrl_installer_backups/wp_team_chart_links_20260916_023500';
const MRL_BACKUP_FILE = MRL_BACKUP_DIR . '/affected_rows_backup.json';
const MRL_STATE_FILE = MRL_BACKUP_DIR . '/_state.json';

$root = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($root === '' || !is_dir($root)) $root = __DIR__;
$rr = realpath($root);
if ($rr !== false) $root = $rr;

$wpLoad = $root . DIRECTORY_SEPARATOR . 'wp-load.php';

$manifest = [
    ['storage'=>'wp_postmeta','record_id'=>921,'post_id'=>413,'meta_key'=>'_menu_item_url','expected'=>['2017_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>5,'post_type'=>'post','post_status'=>'publish','title'=>'2017 Segment 1 Team Chart','slug'=>'2017-segment-1-team-chart','expected'=>['2017_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>172,'post_type'=>'post','post_status'=>'publish','title'=>'2017 Week 1 revision & Team Chart revision','slug'=>'2017-week-1-revision-team-chart-revision','expected'=>['2017_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>525,'post_type'=>'post','post_status'=>'publish','title'=>'2017 Segment 2 Team Chart','slug'=>'2017-segment-2-team-chart','expected'=>['2017_S2_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>640,'post_type'=>'post','post_status'=>'publish','title'=>'2017 Segment 3 Team Chart','slug'=>'2017-segment-3-team-chart','expected'=>['2017_S3_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>783,'post_type'=>'post','post_status'=>'publish','title'=>'2017 Playoffs (Segment 4) Team Chart','slug'=>'2017-playoffs-segment-4-team-chart','expected'=>['2017_S4_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>920,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Segment 1 picks update 2','slug'=>'2018-segment-1-picks-update-2','expected'=>['2018_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>925,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Segment 1','slug'=>'2018-segment-1','expected'=>['2018_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1026,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Segment 2','slug'=>'2018-segment-2','expected'=>['2018_S2_Team_chart.php' => 2]],
    ['storage'=>'wp_posts','record_id'=>1049,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Segment 2 team chart (new link)','slug'=>'2018-segment-2-team-chart-new-link','expected'=>['2018_S2_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1152,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Segment 3','slug'=>'2018-segment-3','expected'=>['2018_S3_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1214,'post_type'=>'page','post_status'=>'publish','title'=>'2018 Teams and Updates','slug'=>'2018-teams-and-updates','expected'=>['2018_S1_Team_chart.php' => 1, '2018_S2_Team_chart.php' => 1, '2018_S3_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1329,'post_type'=>'post','post_status'=>'publish','title'=>'2018 Playoff TEAM CHART','slug'=>'2018-playoff-team-chart','expected'=>['2018_S4_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1487,'post_type'=>'post','post_status'=>'publish','title'=>'2019 Segment 1 team Chart','slug'=>'2019-segment-1-team-chart','expected'=>['2019_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1617,'post_type'=>'post','post_status'=>'publish','title'=>'2019 Segment 2 team Chart','slug'=>'2019-segment-2-team-chart','expected'=>['2019_S2_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>1750,'post_type'=>'post','post_status'=>'publish','title'=>'2019 Segment 3 team Chart','slug'=>'2019-segment-3-team-chart','expected'=>['2019_S3_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>2091,'post_type'=>'post','post_status'=>'publish','title'=>'2020 Segment 1 team Chart','slug'=>'2020-segment-1-team-chart','expected'=>['2020_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>2406,'post_type'=>'post','post_status'=>'publish','title'=>'2020 Segment 3 team Chart','slug'=>'2020-segment-3-team-chart','expected'=>['2020_S3_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>2424,'post_type'=>'post','post_status'=>'publish','title'=>'2020 Weeks 18 & 19','slug'=>'2020-weeks-18-19','expected'=>['2020_S3_Team_chart.php' => 2]],
    ['storage'=>'wp_posts','record_id'=>2566,'post_type'=>'post','post_status'=>'publish','title'=>'2020 Playoffs team Chart','slug'=>'2020-playoffs-team-chart','expected'=>['2020_S4_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>3261,'post_type'=>'post','post_status'=>'publish','title'=>'2022 Week 1 update','slug'=>'2022-week-1-update','expected'=>['2022_S1_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>4126,'post_type'=>'post','post_status'=>'publish','title'=>'6 teams missed the pick deadline','slug'=>'6-teams-missed-the-pick-deadline','expected'=>['2023_S4_Team_chart.php' => 1]],
    ['storage'=>'wp_posts','record_id'=>4331,'post_type'=>'post','post_status'=>'publish','title'=>'2024 Segment 1 Teams','slug'=>'2024-segment-1-teams','expected'=>['2024_S1_Team_chart.php' => 2]],
    ['storage'=>'wp_posts','record_id'=>5354,'post_type'=>'wp_navigation','post_status'=>'publish','title'=>'MRL3','slug'=>'mrl3','expected'=>['2017_S1_Team_chart.php' => 1]],
];

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function full_path(string $root, string $relative): string {
    return rtrim($root, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function ensure_dir(string $dir): bool {
    return is_dir($dir) || @mkdir($dir, 0755, true) || is_dir($dir);
}

function atomic_write(string $path, string $content): bool {
    if (!ensure_dir(dirname($path))) return false;
    $tmp = $path . '.tmp_' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) return false;
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function backup_path(string $root): string {
    return full_path($root, MRL_BACKUP_FILE);
}

function state_path(string $root): string {
    return full_path($root, MRL_STATE_FILE);
}

function load_state(string $root): ?array {
    $f = state_path($root);
    if (!is_file($f)) return null;
    $raw = @file_get_contents($f);
    if ($raw === false) return null;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : null;
}

function save_state(string $root, array $state): bool {
    $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $json !== false && atomic_write(state_path($root), $json . "\n");
}

function chart_old_path(string $name): string {
    return '/' . $name;
}

function chart_new_path(string $name): string {
    return '/team_charts/' . $name;
}

function is_serialized_like(string $value): bool {
    $t = trim($value);
    if ($t === 'N;') return true;
    if (strlen($t) < 4 || $t[1] !== ':') return false;
    $last = substr($t, -1);
    if ($last !== ';' && $last !== '}') return false;
    return in_array($t[0], ['s','a','O','b','i','d'], true);
}

function fetch_manifest_value($wpdb, array $row): ?array {
    if ($row['storage'] === 'wp_posts') {
        $db = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ID, post_type, post_status, post_title, post_name, post_content
                 FROM {$wpdb->posts}
                 WHERE ID = %d",
                $row['record_id']
            ),
            ARRAY_A
        );
        if (!is_array($db)) return null;

        return [
            'value' => (string)$db['post_content'],
            'post_id' => (int)$db['ID'],
            'post_type' => (string)$db['post_type'],
            'post_status' => (string)$db['post_status'],
            'title' => (string)$db['post_title'],
            'slug' => (string)$db['post_name'],
        ];
    }

    if ($row['storage'] === 'wp_postmeta') {
        $db = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT meta_id, post_id, meta_key, meta_value
                 FROM {$wpdb->postmeta}
                 WHERE meta_id = %d",
                $row['record_id']
            ),
            ARRAY_A
        );
        if (!is_array($db)) return null;

        return [
            'value' => (string)$db['meta_value'],
            'post_id' => (int)$db['post_id'],
            'meta_key' => (string)$db['meta_key'],
        ];
    }

    return null;
}

function expected_occurrences(array $row): int {
    return array_sum(array_map('intval', $row['expected']));
}

function scan_revision_count($wpdb): array {
    $countRows = 0;
    $occurrences = 0;

    $names = [];
    for ($year = 2017; $year <= 2025; $year++) {
        for ($s = 1; $s <= 4; $s++) {
            $names[] = $year . '_S' . $s . '_Team_chart.php';
        }
    }

    $seen = [];

    foreach ($names as $name) {
        $old = '/' . $name;
        $like = '%' . $wpdb->esc_like($old) . '%';

        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = 'revision'
                   AND post_content LIKE %s",
                $like
            )
        );

        foreach ($ids as $id) {
            $id = (int)$id;
            if (!isset($seen[$id])) {
                $content = (string)$wpdb->get_var(
                    $wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $id)
                );
                $seen[$id] = $content;
            }
        }
    }

    foreach ($seen as $content) {
        $rowCount = 0;
        foreach ($names as $name) {
            $rowCount += substr_count($content, '/' . $name);
        }
        if ($rowCount > 0) {
            $countRows++;
            $occurrences += $rowCount;
        }
    }

    return ['rows' => $countRows, 'occurrences' => $occurrences];
}

function preflight($wpdb, array $manifest, string $root): array {
    $rows = [];
    $blocking = 0;
    $matchedRows = 0;
    $matchedOccurrences = 0;
    $expectedTotal = 0;

    $rows[] = [
        'check' => 'WordPress database',
        'status' => is_object($wpdb) ? 'PASS' : 'FAIL',
        'detail' => is_object($wpdb) ? 'WordPress DB connection is available.' : 'WordPress DB connection is unavailable.'
    ];
    if (!is_object($wpdb)) $blocking++;

    foreach ($manifest as $m) {
        $expectedTotal += expected_occurrences($m);
        $db = fetch_manifest_value($wpdb, $m);

        if (!$db) {
            $rows[] = [
                'check' => 'Manifest record',
                'status' => 'FAIL',
                'detail' => $m['storage'] . ' record ' . $m['record_id'] . ' no longer exists.'
            ];
            $blocking++;
            continue;
        }

        if ($m['storage'] === 'wp_posts') {
            if ($db['post_type'] !== $m['post_type'] || $db['post_status'] !== $m['post_status']) {
                $rows[] = [
                    'check' => 'Manifest record',
                    'status' => 'FAIL',
                    'detail' => 'wp_posts ID ' . $m['record_id'] . ' type/status changed since discovery.'
                ];
                $blocking++;
                continue;
            }
        } else {
            if ($db['meta_key'] !== $m['meta_key'] || $db['post_id'] !== $m['post_id']) {
                $rows[] = [
                    'check' => 'Manifest record',
                    'status' => 'FAIL',
                    'detail' => 'wp_postmeta meta_id ' . $m['record_id'] . ' identity changed since discovery.'
                ];
                $blocking++;
                continue;
            }
            if (is_serialized_like($db['value'])) {
                $rows[] = [
                    'check' => 'Serialized safety',
                    'status' => 'FAIL',
                    'detail' => 'meta_id ' . $m['record_id'] . ' now appears serialized; install is blocked.'
                ];
                $blocking++;
                continue;
            }
        }

        $rowOK = true;
        $rowOccurrences = 0;

        foreach ($m['expected'] as $name => $expected) {
            $actual = substr_count($db['value'], chart_old_path($name));
            $alreadyNew = substr_count($db['value'], chart_new_path($name));

            if ($actual !== (int)$expected || $alreadyNew > 0) {
                $rowOK = false;
                break;
            }

            $rowOccurrences += $actual;
        }

        if (!$rowOK) {
            $rows[] = [
                'check' => 'Exact occurrence baseline',
                'status' => 'FAIL',
                'detail' => $m['storage'] . ' record ' . $m['record_id'] . ' no longer matches the discovery report.'
            ];
            $blocking++;
            continue;
        }

        $matchedRows++;
        $matchedOccurrences += $rowOccurrences;
    }

    $rows[] = [
        'check' => 'Approved live/current records',
        'status' => $matchedRows === count($manifest) ? 'PASS' : 'FAIL',
        'detail' => $matchedRows . ' of ' . count($manifest) . ' exact records match discovery baseline.'
    ];
    if ($matchedRows !== count($manifest)) $blocking++;

    $rows[] = [
        'check' => 'Approved path replacements',
        'status' => $matchedOccurrences === $expectedTotal ? 'PASS' : 'FAIL',
        'detail' => $matchedOccurrences . ' of ' . $expectedTotal . ' old root-path occurrences match exactly.'
    ];
    if ($matchedOccurrences !== $expectedTotal) $blocking++;

    $backup = backup_path($root);
    $state = load_state($root);

    $rows[] = [
        'check' => 'Backup destination',
        'status' => (!$state && !is_file($backup)) ? 'PASS' : 'INFO',
        'detail' => (!$state && !is_file($backup))
            ? MRL_BACKUP_FILE . ' is available.'
            : 'Prior migration backup/state exists; use Rollback rather than reinstalling.'
    ];

    $revs = scan_revision_count($wpdb);
    $rows[] = [
        'check' => 'Historical WordPress revisions',
        'status' => 'INFO',
        'detail' => $revs['rows'] . ' revision row(s) contain ' . $revs['occurrences'] .
                    ' old-path occurrence(s). They are intentionally preserved as revision history.'
    ];

    if ($state) $blocking++;

    return [
        'ok' => ($blocking === 0 && !$state && !is_file($backup)),
        'rows' => $rows,
        'expected_replacements' => $expectedTotal,
        'revision_info' => $revs,
    ];
}

function build_backup($wpdb, array $manifest): ?array {
    $backup = [
        'created_at' => date(DATE_ATOM),
        'installer_version' => MRL_INSTALLER_VERSION,
        'rows' => [],
    ];

    foreach ($manifest as $m) {
        $db = fetch_manifest_value($wpdb, $m);
        if (!$db) return null;

        $backup['rows'][] = [
            'storage' => $m['storage'],
            'record_id' => $m['record_id'],
            'post_id' => $db['post_id'] ?? null,
            'value' => $db['value'],
            'sha256' => hash('sha256', $db['value']),
        ];
    }

    return $backup;
}

function restore_backup($wpdb, string $root): array {
    $path = backup_path($root);
    if (!is_file($path)) {
        return ['ok'=>false, 'message'=>'Backup file is missing.', 'errors'=>[]];
    }

    $raw = @file_get_contents($path);
    if ($raw === false) {
        return ['ok'=>false, 'message'=>'Backup file could not be read.', 'errors'=>[]];
    }

    $backup = json_decode($raw, true);
    if (!is_array($backup) || !isset($backup['rows']) || !is_array($backup['rows'])) {
        return ['ok'=>false, 'message'=>'Backup JSON is invalid.', 'errors'=>[]];
    }

    $errors = [];

    foreach ($backup['rows'] as $row) {
        $value = (string)$row['value'];

        if ($row['storage'] === 'wp_posts') {
            $r = $wpdb->update(
                $wpdb->posts,
                ['post_content' => $value],
                ['ID' => (int)$row['record_id']],
                ['%s'],
                ['%d']
            );

            if ($r === false) {
                $errors[] = 'Could not restore wp_posts ID ' . $row['record_id'];
            } else {
                clean_post_cache((int)$row['record_id']);
            }
        } elseif ($row['storage'] === 'wp_postmeta') {
            $r = update_metadata_by_mid(
                'post',
                (int)$row['record_id'],
                $value
            );

            if ($r === false) {
                $errors[] = 'Could not restore wp_postmeta meta_id ' . $row['record_id'];
            } else {
                wp_cache_delete((int)$row['post_id'], 'post_meta');
            }
        }
    }

    return [
        'ok' => empty($errors),
        'message' => empty($errors)
            ? 'Exact original WordPress values restored from migration backup.'
            : 'Restore completed with errors.',
        'errors' => $errors,
    ];
}

function install_migration($wpdb, array $manifest, string $root): array {
    $pre = preflight($wpdb, $manifest, $root);
    if (!$pre['ok']) {
        return ['ok'=>false, 'message'=>'Install blocked: preflight is not fully green.', 'errors'=>[]];
    }

    $backup = build_backup($wpdb, $manifest);
    if (!$backup) {
        return ['ok'=>false, 'message'=>'Could not build exact row backup.', 'errors'=>[]];
    }

    $backupJson = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($backupJson === false || !atomic_write(backup_path($root), $backupJson . "\n")) {
        return ['ok'=>false, 'message'=>'Could not write exact row backup.', 'errors'=>[]];
    }

    $errors = [];
    $replacements = 0;
    $updatedRows = 0;

    foreach ($manifest as $m) {
        $db = fetch_manifest_value($wpdb, $m);
        if (!$db) {
            $errors[] = $m['storage'] . ' record ' . $m['record_id'] . ' disappeared during install.';
            break;
        }

        $newValue = $db['value'];
        $rowReplacements = 0;

        foreach ($m['expected'] as $name => $expected) {
            $newValue = str_replace(
                chart_old_path($name),
                chart_new_path($name),
                $newValue,
                $count
            );

            if ($count !== (int)$expected) {
                $errors[] = $m['storage'] . ' record ' . $m['record_id'] .
                            ' replacement count changed unexpectedly for ' . $name . '.';
                break 2;
            }

            $rowReplacements += $count;
        }

        if ($m['storage'] === 'wp_posts') {
            $r = $wpdb->update(
                $wpdb->posts,
                ['post_content' => $newValue],
                ['ID' => (int)$m['record_id']],
                ['%s'],
                ['%d']
            );

            if ($r === false) {
                $errors[] = 'DB update failed for wp_posts ID ' . $m['record_id'];
                break;
            }

            clean_post_cache((int)$m['record_id']);
        } else {
            $r = update_metadata_by_mid(
                'post',
                (int)$m['record_id'],
                $newValue
            );

            if ($r === false) {
                $errors[] = 'DB update failed for wp_postmeta meta_id ' . $m['record_id'];
                break;
            }

            wp_cache_delete((int)$m['post_id'], 'post_meta');
        }

        $replacements += $rowReplacements;
        $updatedRows++;
    }

    if ($errors) {
        $restore = restore_backup($wpdb, $root);
        if (!$restore['ok']) {
            $errors = array_merge($errors, $restore['errors']);
        }

        return [
            'ok'=>false,
            'message'=>'Migration failed; automatic restore from exact-row backup was attempted.',
            'errors'=>$errors,
        ];
    }

    $state = [
        'installed_at' => date(DATE_ATOM),
        'installer_version' => MRL_INSTALLER_VERSION,
        'updated_rows' => $updatedRows,
        'replacements' => $replacements,
        'historical_revisions_preserved' => true,
        'backup' => MRL_BACKUP_FILE,
    ];

    if (!save_state($root, $state)) {
        $restore = restore_backup($wpdb, $root);
        return [
            'ok'=>false,
            'message'=>'State-file write failed; exact original DB values were restored.',
            'errors'=>$restore['errors'],
        ];
    }

    return [
        'ok'=>true,
        'message'=>$updatedRows . ' live/current WordPress record(s) updated; ' .
                  $replacements . ' Team Chart path occurrence(s) migrated.',
        'errors'=>[],
    ];
}

function postflight($wpdb, array $manifest): array {
    $rows = [];
    $ok = true;
    $verifiedRows = 0;
    $newCount = 0;
    $oldCount = 0;

    foreach ($manifest as $m) {
        $db = fetch_manifest_value($wpdb, $m);
        if (!$db) {
            $ok = false;
            continue;
        }

        $rowOK = true;

        foreach ($m['expected'] as $name => $expected) {
            $old = substr_count($db['value'], chart_old_path($name));
            $new = substr_count($db['value'], chart_new_path($name));

            $oldCount += $old;
            $newCount += $new;

            if ($old !== 0 || $new !== (int)$expected) {
                $rowOK = false;
            }
        }

        if ($rowOK) $verifiedRows++;
        else $ok = false;
    }

    $rows[] = [
        'check'=>'Updated live/current records',
        'status'=>$verifiedRows === count($manifest) ? 'PASS' : 'FAIL',
        'detail'=>$verifiedRows . ' of ' . count($manifest) . ' exact rows verify.'
    ];

    $rows[] = [
        'check'=>'Old live/current Team Chart paths',
        'status'=>$oldCount === 0 ? 'PASS' : 'FAIL',
        'detail'=>$oldCount . ' approved old root-path occurrence(s) remain.'
    ];

    $rows[] = [
        'check'=>'New /team_charts/ paths',
        'status'=>$newCount > 0 ? 'PASS' : 'FAIL',
        'detail'=>$newCount . ' approved new-path occurrence(s) verify.'
    ];

    $revs = scan_revision_count($wpdb);
    $rows[] = [
        'check'=>'Historical WordPress revisions',
        'status'=>'INFO',
        'detail'=>$revs['rows'] . ' revision row(s) still contain ' . $revs['occurrences'] .
                 ' old-path occurrence(s), intentionally preserved as history.'
    ];

    return ['ok'=>$ok && $oldCount===0, 'rows'=>$rows];
}

$loadError = null;

if (!is_file($wpLoad)) {
    $loadError = 'wp-load.php was not found at production root.';
} else {
    require_once $wpLoad;
    global $wpdb;
    if (!isset($wpdb) || !is_object($wpdb)) {
        $loadError = 'WordPress DB object ($wpdb) is unavailable.';
    }
}

$action = (string)($_POST['action'] ?? '');
$result = null;

if (!$loadError && $action === 'install') {
    if (($_POST['confirm_install'] ?? '') !== 'yes') {
        $result = ['ok'=>false, 'message'=>'Install not started: confirmation box was not checked.', 'errors'=>[]];
    } else {
        $result = install_migration($wpdb, $manifest, $root);
    }
} elseif (!$loadError && $action === 'rollback') {
    $result = restore_backup($wpdb, $root);

    if ($result['ok']) {
        @unlink(state_path($root));
        $result['message'] .= ' Migration state cleared.';
    }
}

$state = $loadError ? null : load_state($root);
$pre = (!$loadError && !$state) ? preflight($wpdb, $manifest, $root) : null;
$post = (!$loadError && $state) ? postflight($wpdb, $manifest) : null;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL WordPress Team Chart Link Migration</title>
<style>
:root{color-scheme:dark;--bg:#0f1211;--panel:#1a1f1d;--panel2:#151917;--border:#45504b;--text:#f0eee8;--muted:#b7b7af;--gold:#f1c97f}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:min(1180px,96%);margin:14px auto 30px}
h1{margin:0 0 10px;color:var(--gold);font-size:27px}
h2{margin:0 0 9px;color:var(--gold);font-size:18px}
.panel{margin:0 0 11px;padding:12px 14px;border:1px solid var(--border);border-radius:11px;background:var(--panel)}
.banner{padding:11px 13px;margin-bottom:11px;border:1px solid #3f8bc2;border-radius:10px;background:#15354d;color:#e8f5ff;font-weight:800}
.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.card{padding:11px;border:1px solid var(--border);border-radius:9px;background:var(--panel2)}
.value{display:block;margin-top:3px;font-size:20px;font-weight:800}
.small{font-size:12px;color:var(--muted)}
table{width:100%;border-collapse:collapse}
th,td{padding:7px 8px;border-bottom:1px solid #343b38;text-align:left;vertical-align:top}
th{background:#202624;color:var(--gold)}
.status{display:inline-block;padding:3px 8px;border-radius:999px;font-size:12px;font-weight:800;white-space:nowrap}
.pass{background:#17613a;border:1px solid #55db8b;color:#e8fff1}
.info{background:#4a3813;border:1px solid #d8aa49;color:#ffe6a7}
.fail{background:#5b2323;border:1px solid #e77a7a;color:#ffe4e4}
.actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
button{min-height:38px;padding:8px 14px;border:0;border-radius:7px;color:#fff;font-weight:800;cursor:pointer}
.install{background:#2f7f53}.rollback{background:#b46d22}.refresh{background:#2c6f9e}
button:disabled{background:#5a5f5d;color:#b9b9b9;cursor:not-allowed;opacity:.7}
.confirm{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid #5a6a62;border-radius:8px;background:#151a18}
.result-ok{border-color:#2f9a61;background:#103b27}.result-bad{border-color:#a65353;background:#3d1d1d}
.err{border-color:#a65353;background:#3d1d1d}
code{color:#f8d89a}
@media(max-width:900px){.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
</head>
<body><div class="wrap">

<h1>MRL WordPress Team Chart Link Migration</h1>

<div class="banner">
Updates only the exact CURRENT/LIVE WordPress records discovered to contain old root-level links
to the 2017–2025 official S1–S4 Team Charts. Historical WordPress revision rows are deliberately preserved.
</div>

<div class="panel">
<h2>Plan</h2>
<div class="grid">
<div class="card"><span class="small">Approved live/current DB rows</span><span class="value">24</span></div>
<div class="card"><span class="small">Expected path replacements</span><span class="value">29</span></div>
<div class="card"><span class="small">Serialized live rows</span><span class="value">0</span></div>
<div class="card"><span class="small">Blind DB-wide replace</span><span class="value">NO</span></div>
</div>
</div>

<?php if ($loadError): ?>
<div class="panel err"><h2>Unable to Continue</h2><p><?php echo h($loadError); ?></p></div>
<?php endif; ?>

<?php if ($result): ?>
<div class="panel <?php echo !empty($result['ok'])?'result-ok':'result-bad'; ?>">
<h2>Result</h2>
<p><strong><?php echo !empty($result['ok'])?'PASS':'ATTENTION'; ?></strong> — <?php echo h($result['message']); ?></p>
<?php if (!empty($result['errors'])): ?><ul><?php foreach($result['errors'] as $e): ?><li><?php echo h($e); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$loadError): ?>
<div class="panel">
<h2><?php echo $state ? 'Postflight / Result' : 'Preflight / Result'; ?></h2>
<table><tr><th>Check</th><th>Status</th><th>Detail</th></tr>
<?php
$rows = $state ? $post['rows'] : $pre['rows'];
foreach ($rows as $r):
    $cls = $r['status']==='PASS'?'pass':($r['status']==='FAIL'?'fail':'info');
?>
<tr><td><?php echo h($r['check']); ?></td><td><span class="status <?php echo h($cls); ?>"><?php echo h($r['status']); ?></span></td><td><?php echo h($r['detail']); ?></td></tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel">
<h2>Action</h2>
<?php if ($state): ?>
<p>Migration state is active. Rollback restores the exact original DB field values from the row-level backup.</p>
<form method="post">
<input type="hidden" name="action" value="rollback">
<div class="actions">
<button class="rollback" type="submit">Rollback WordPress Link Migration</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Postflight</button>
</div>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="install">
<label class="confirm">
<input type="checkbox" name="confirm_install" value="yes" id="confirmInstall">
I reviewed the green preflight and want to migrate these exact 24 current/live WordPress records.
</label>
<div class="actions" style="margin-top:10px">
<button class="install" type="submit" id="installButton" disabled>Install WordPress Link Migration</button>
<button class="refresh" type="button" onclick="window.location.reload()">Refresh Preflight</button>
</div>
<p class="small"><?php echo $pre['ok'] ? 'Preflight is green. Check the confirmation box to enable Install.' : 'Install is blocked because one or more preflight checks failed.'; ?></p>
</form>
<?php endif; ?>
</div>

<div class="panel">
<h2>Approved Current/Live Records</h2>
<table>
<tr><th>Storage</th><th>ID</th><th>Context</th><th>Expected replacements</th></tr>
<?php foreach($manifest as $m): ?>
<tr>
<td><code><?php echo h($m['storage']); ?></code></td>
<td><?php echo h($m['record_id']); ?></td>
<td>
<?php if($m['storage']==='wp_posts'): ?>
<strong><?php echo h($m['title']); ?></strong><br>
<span class="small"><?php echo h($m['post_type']); ?> / <?php echo h($m['post_status']); ?> / <?php echo h($m['slug']); ?></span>
<?php else: ?>
<span class="small">post_id <?php echo h($m['post_id']); ?></span><br><code><?php echo h($m['meta_key']); ?></code>
<?php endif; ?>
</td>
<td><?php echo h(expected_occurrences($m)); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<div class="panel small">
Backup: <code><?php echo h(MRL_BACKUP_FILE); ?></code><br>
Historical WordPress revisions are intentionally not modified. They are audit/history, not live content.
</div>
<?php endif; ?>

<div class="panel small">
FILE: <?php echo h(basename(__FILE__)); ?> | VERSION: v001 | CREATED: 9/16/2026 2:35:00 am EDT
</div>

</div>
<script>
(function(){
'use strict';
var c=document.getElementById('confirmInstall');
var b=document.getElementById('installButton');
var ok=<?php echo (!$loadError && !$state && $pre && $pre['ok'])?'true':'false'; ?>;
function sync(){ if(b) b.disabled=!(ok && c && c.checked); }
if(c) c.addEventListener('change',sync);
sync();
}());
</script>
</body>
</html>
