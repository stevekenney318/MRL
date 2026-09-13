<?php
declare(strict_types=1);

/**
 * MRL_install_canonical_short_race_labels_v001.php
 *
 * VERSION: v001
 * GENERATED: 9/13/2026 6:34:43 am ET
 *
 * PURPOSE:
 * - Make the Weekly Standings compact race-name rules the shared canonical source.
 * - Persist canonical mrl_race_name values into every scheduled monitor refresh.
 * - Keep full ESPN race_name values intact.
 * - Repair today's generated schedule/state data immediately.
 *
 * CHANGES:
 * - race_results/race_schedule_helper.php v003 -> v004
 * - race_results/race_results_monitor.php v140 -> v141
 * - race_results/weekly_standings.php v068 -> v069
 * - Runtime data repair: _race_results_schedule.json and compact race_status name only.
 *
 * NO DATABASE WRITES.
 */

date_default_timezone_set('America/New_York');

$installerVersion = 'v001';
$generatedStamp = '20260913_063443';
$generatedDisplay = '9/13/2026 6:34:43 am ET';

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
if ($docRoot === '' || !is_dir($docRoot)) {
    $docRoot = __DIR__;
}

$targets = [
    'schedule_helper' => $docRoot . '/race_results/race_schedule_helper.php',
    'monitor'         => $docRoot . '/race_results/race_results_monitor.php',
    'weekly'          => $docRoot . '/race_results/weekly_standings.php',
    'schedule_json'   => $docRoot . '/race_results/_race_results_schedule.json',
    'monitor_state'   => $docRoot . '/race_results/_race_results_monitor_state.json',
];

function inst_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function inst_read(string $path): string
{
    $raw = @file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Could not read: ' . $path);
    }
    return $raw;
}

function inst_atomic_write(string $path, string $content): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        throw new RuntimeException('Directory missing: ' . $dir);
    }

    $tmp = $path . '.tmp_' . str_replace('.', '', uniqid('', true));
    $bytes = @file_put_contents($tmp, $content, LOCK_EX);
    if ($bytes === false) {
        @unlink($tmp);
        throw new RuntimeException('Could not write temporary file for: ' . $path);
    }

    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException('Could not replace: ' . $path);
    }
}

function inst_replace_once(string $content, string $from, string $to, string $label): string
{
    $count = substr_count($content, $from);
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 match, found ' . $count . '.');
    }
    return str_replace($from, $to, $content);
}

function inst_regex_replace_once(string $content, string $pattern, string $replacement, string $label): string
{
    $count = 0;
    $out = preg_replace($pattern, $replacement, $content, 1, $count);
    if ($out === null) {
        throw new RuntimeException($label . ': regex error.');
    }
    if ($count !== 1) {
        throw new RuntimeException($label . ': expected exactly 1 regex match, found ' . $count . '.');
    }
    return $out;
}

/**
 * Installer-side copy of the canonical compact-label rule.
 * This mirrors race_schedule_helper.php v004 so runtime JSON can be repaired
 * immediately without executing the Race Monitor.
 */
function inst_short_race_name(string $raceName): string
{
    $name = html_entity_decode(trim($raceName), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $name = str_replace(['/', '\\', ':', '|'], ' ', $name);
    $name = preg_replace('/[^A-Za-z0-9 _-]+/', '', $name);
    $name = preg_replace('/\s+/', ' ', (string)$name);
    $name = trim((string)$name);
    $slug = str_replace([' ', '-'], '_', $name);
    $slug = preg_replace('/_+/', '_', (string)$slug);
    $slug = trim((string)$slug, '_');

    $map = [
        'EchoPark_Automotive_Grand_Prix' => 'COTA',
        'NASCAR_Cup_Series_at_Circuit_of_the_Americas' => 'COTA',
        'NASCAR_CUP_SERIES_AT_CIRCUIT_OF_THE_AMERICAS' => 'COTA',

        'World_Wide_Technology_Raceway' => 'World Wide Tech',
        'NASCAR_Cup_Series_at_World_Wide_Technology_Raceway' => 'World Wide Tech',
        'NASCAR_CUP_SERIES_AT_WORLD_WIDE_TECHNOLOGY_RACEWAY' => 'World Wide Tech',
        'World_Wide_Technology' => 'World Wide Tech',
        'World_Wide_Tech' => 'World Wide Tech',
        'World' => 'World Wide Tech',
        'NASCAR_Cup_Series_at_World' => 'World Wide Tech',
        'NASCAR_CUP_SERIES_AT_WORLD' => 'World Wide Tech',

        'Indianapolis_Road_Course' => 'Indianapolis RC',
        'NASCAR_Cup_Series_at_Indianapolis_Road_Course' => 'Indianapolis RC',
        'NASCAR_CUP_SERIES_AT_INDIANAPOLIS_ROAD_COURSE' => 'Indianapolis RC',

        'Charlotte_Road_Course' => 'Charlotte RC',
        'NASCAR_Cup_Series_at_Charlotte_Road_Course' => 'Charlotte RC',
        'NASCAR_CUP_SERIES_AT_CHARLOTTE_ROAD_COURSE' => 'Charlotte RC',
    ];

    if (isset($map[$slug])) {
        return $map[$slug];
    }

    $slug = preg_replace('/^MONSTER_ENERGY_NASCAR_CUP_SERIES_AT_/i', '', (string)$slug);
    $slug = preg_replace('/^NASCAR_CUP_SERIES_AT_/i', '', (string)$slug);
    $slug = preg_replace('/^NASCAR_Cup_Series_at_/i', '', (string)$slug);
    $slug = preg_replace('/^NASCAR_CUP_SERIES_/i', '', (string)$slug);
    $slug = preg_replace('/^NASCAR_Cup_Series_/i', '', (string)$slug);
    $slug = preg_replace('/^AT_/i', '', (string)$slug);

    if (strcasecmp((string)$slug, 'World') === 0
        || stripos((string)$slug, 'World_Wide_Technology') !== false
        || stripos((string)$slug, 'World_Wide_Tech') !== false) {
        return 'World Wide Tech';
    }

    $slug = str_replace('Indianapolis_Road_Course', 'Indianapolis_RC', (string)$slug);
    $slug = str_replace('Charlotte_Road_Course', 'Charlotte_RC', (string)$slug);
    $slug = str_replace('Road_Course', 'RC', (string)$slug);
    $slug = trim((string)$slug, '_');

    if ($slug === '') {
        return 'Race';
    }

    return str_replace('_', ' ', (string)$slug);
}

function inst_preflight(array $targets): array
{
    $checks = [];

    $defs = [
        [
            'label' => 'race_schedule_helper.php',
            'path' => $targets['schedule_helper'],
            'needles' => [
                'VERSION: v003',
                "function mrl_schedule_helper_race_number(array \$race): int",
            ],
        ],
        [
            'label' => 'race_results_monitor.php',
            'path' => $targets['monitor'],
            'needles' => [
                'VERSION: v140',
                'function rr_monitor_short_race_label(string $raceName): string',
                'function rr_monitor_short_schedule_name(array $race): string',
                'function rr_monitor_fetch_and_store_schedule(',
            ],
        ],
        [
            'label' => 'weekly_standings.php',
            'path' => $targets['weekly'],
            'needles' => [
                'VERSION: v068',
                'function rrsg_short_race_label(string $raceName): string',
                "require_once __DIR__ . '/race_results_engine.php';",
            ],
        ],
        [
            'label' => '_race_results_schedule.json',
            'path' => $targets['schedule_json'],
            'needles' => [],
        ],
    ];

    $allOk = true;

    foreach ($defs as $def) {
        $ok = is_file($def['path']) && is_readable($def['path']);
        $detail = $ok ? 'Readable' : 'Missing/unreadable';

        if ($ok && !empty($def['needles'])) {
            $raw = (string)@file_get_contents($def['path']);
            foreach ($def['needles'] as $needle) {
                if (strpos($raw, $needle) === false) {
                    $ok = false;
                    $detail = 'Expected baseline signature not found: ' . $needle;
                    break;
                }
            }
        }

        if (!$ok) {
            $allOk = false;
        }

        $checks[] = [
            'label' => $def['label'],
            'ok' => $ok,
            'detail' => $detail,
        ];
    }

    $scheduleR28 = '';
    if (is_file($targets['schedule_json'])) {
        $raw = @file_get_contents($targets['schedule_json']);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($data)) {
            $lists = [];
            if (isset($data['mrl_points_races']) && is_array($data['mrl_points_races'])) {
                $lists[] = $data['mrl_points_races'];
            }
            if (isset($data['races']) && is_array($data['races'])) {
                $lists[] = $data['races'];
            }
            foreach ($lists as $list) {
                foreach ($list as $race) {
                    if (!is_array($race)) continue;
                    $n = (int)($race['mrl_race_number'] ?? $race['race_number'] ?? 0);
                    if ($n === 28) {
                        $scheduleR28 = (string)($race['mrl_race_name'] ?? $race['short_name'] ?? $race['race_name'] ?? '');
                        break 2;
                    }
                }
            }
        }
    }

    return [
        'ok' => $allOk,
        'checks' => $checks,
        'r28_before' => $scheduleR28,
    ];
}

function inst_patch_schedule_helper(string $src): string
{
    $src = inst_replace_once($src, 'VERSION: v003', 'VERSION: v004', 'schedule helper version');

    $changeNeedle = " * CHANGELOG:\n *\n";
    $changeText =
        " * CHANGELOG:\n *\n"
        . " * v004 (9/13/2026 6:34:43 am ET)\n"
        . " * - NEW: Central canonical compact MRL race-name helper shared by Weekly Standings, Race Monitor status, Team Chart schedule consumers, and generated schedule data.\n"
        . " * - PRESERVE: Existing compact names including COTA, Indianapolis RC, Charlotte RC, and World Wide Tech.\n"
        . " * - FIX: ESPN's truncated 2026 R28 value World now normalizes durably to World Wide Tech.\n"
        . " * - PRESERVE: Full ESPN race_name data remains unchanged.\n"
        . " *\n";
    $src = inst_replace_once($src, $changeNeedle, $changeText, 'schedule helper changelog');

    $anchor = <<<'PHP'
if (!function_exists('mrl_schedule_helper_race_datetime')) {
PHP;

    $helper = <<<'PHP'
if (!function_exists('mrl_schedule_helper_short_race_name')) {
    /**
     * Canonical compact MRL race label.
     *
     * Origin: Weekly Standings compact-label rules.
     * Full ESPN race_name values remain untouched; this helper is only for
     * compact MRL labels used in controls/status/footnotes/generated schedule data.
     */
    function mrl_schedule_helper_short_race_name(string $raceName): string
    {
        $name = html_entity_decode(trim($raceName), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $name = str_replace(['/', '\\', ':', '|'], ' ', $name);
        $name = preg_replace('/[^A-Za-z0-9 _-]+/', '', $name);
        $name = preg_replace('/\s+/', ' ', (string)$name);
        $name = trim((string)$name);

        $slug = str_replace([' ', '-'], '_', $name);
        $slug = preg_replace('/_+/', '_', (string)$slug);
        $slug = trim((string)$slug, '_');

        $map = [
            'EchoPark_Automotive_Grand_Prix' => 'COTA',
            'NASCAR_Cup_Series_at_Circuit_of_the_Americas' => 'COTA',
            'NASCAR_CUP_SERIES_AT_CIRCUIT_OF_THE_AMERICAS' => 'COTA',

            'World_Wide_Technology_Raceway' => 'World Wide Tech',
            'NASCAR_Cup_Series_at_World_Wide_Technology_Raceway' => 'World Wide Tech',
            'NASCAR_CUP_SERIES_AT_WORLD_WIDE_TECHNOLOGY_RACEWAY' => 'World Wide Tech',
            'World_Wide_Technology' => 'World Wide Tech',
            'World_Wide_Tech' => 'World Wide Tech',
            'World' => 'World Wide Tech',
            'NASCAR_Cup_Series_at_World' => 'World Wide Tech',
            'NASCAR_CUP_SERIES_AT_WORLD' => 'World Wide Tech',

            'Indianapolis_Road_Course' => 'Indianapolis RC',
            'NASCAR_Cup_Series_at_Indianapolis_Road_Course' => 'Indianapolis RC',
            'NASCAR_CUP_SERIES_AT_INDIANAPOLIS_ROAD_COURSE' => 'Indianapolis RC',

            'Charlotte_Road_Course' => 'Charlotte RC',
            'NASCAR_Cup_Series_at_Charlotte_Road_Course' => 'Charlotte RC',
            'NASCAR_CUP_SERIES_AT_CHARLOTTE_ROAD_COURSE' => 'Charlotte RC',
        ];

        if (isset($map[$slug])) {
            return $map[$slug];
        }

        $slug = preg_replace('/^MONSTER_ENERGY_NASCAR_CUP_SERIES_AT_/i', '', (string)$slug);
        $slug = preg_replace('/^NASCAR_CUP_SERIES_AT_/i', '', (string)$slug);
        $slug = preg_replace('/^NASCAR_Cup_Series_at_/i', '', (string)$slug);
        $slug = preg_replace('/^NASCAR_CUP_SERIES_/i', '', (string)$slug);
        $slug = preg_replace('/^NASCAR_Cup_Series_/i', '', (string)$slug);
        $slug = preg_replace('/^AT_/i', '', (string)$slug);

        if (
            strcasecmp((string)$slug, 'World') === 0
            || stripos((string)$slug, 'World_Wide_Technology') !== false
            || stripos((string)$slug, 'World_Wide_Tech') !== false
        ) {
            return 'World Wide Tech';
        }

        $slug = str_replace('Indianapolis_Road_Course', 'Indianapolis_RC', (string)$slug);
        $slug = str_replace('Charlotte_Road_Course', 'Charlotte_RC', (string)$slug);
        $slug = str_replace('Road_Course', 'RC', (string)$slug);
        $slug = trim((string)$slug, '_');

        if ($slug === '') {
            return 'Race';
        }

        return str_replace('_', ' ', (string)$slug);
    }
}

PHP;

    $src = inst_replace_once($src, $anchor, $helper . $anchor, 'insert canonical short-name helper');
    return $src;
}

function inst_patch_monitor(string $src): string
{
    $src = inst_replace_once($src, 'VERSION: v140', 'VERSION: v141', 'monitor version');

    $changeNeedle = " * CHANGELOG:\n *\n";
    $changeText =
        " * CHANGELOG:\n *\n"
        . " * v141 (9/13/2026 6:34:43 am ET)\n"
        . " *   - FIX: Schedule refresh now writes canonical compact mrl_race_name values on every run instead of allowing ESPN short-name changes to overwrite MRL display names.\n"
        . " *   - CHANGE: Race status and subject labels use the shared race_schedule_helper compact-name rule.\n"
        . " *   - FIX: 2026 R28 World remains World Wide Tech after scheduled monitor refreshes.\n"
        . " *   - PRESERVE: Full ESPN race_name, race identity, snapshots, scoring, notifications, scheduler ownership, and RD behavior.\n"
        . " *\n";
    $src = inst_replace_once($src, $changeNeedle, $changeText, 'monitor changelog');

    $requireAnchor = "require_once __DIR__ . '/race_results_engine.php';\n";
    $requireReplacement =
        "require_once __DIR__ . '/race_results_engine.php';\n"
        . "require_once __DIR__ . '/race_schedule_helper.php';\n";
    $src = inst_replace_once($src, $requireAnchor, $requireReplacement, 'monitor schedule helper require');

    $shortPattern = '~function rr_monitor_short_race_label\(string \$raceName\): string\s*\{.*?\n\}\n\nfunction rr_monitor_subject_token~s';
    $shortReplacement = <<<'PHP'
function rr_monitor_short_race_label(string $raceName): string
{
    return mrl_schedule_helper_short_race_name($raceName);
}

function rr_monitor_subject_token
PHP;
    $src = inst_regex_replace_once($src, $shortPattern, $shortReplacement, 'monitor short-race wrapper');

    $scheduleNamePattern = '~function rr_monitor_short_schedule_name\(array \$race\): string\s*\{.*?\n\}\n\nfunction rr_monitor_format_schedule_start~s';
    $scheduleNameReplacement = <<<'PHP'
function rr_monitor_short_schedule_name(array $race): string
{
    $name = trim((string)($race['mrl_race_name'] ?? ''));
    if ($name === '') {
        $name = trim((string)($race['short_name'] ?? ''));
    }
    if ($name === '') {
        $name = trim((string)($race['race_name'] ?? ''));
    }

    return mrl_schedule_helper_short_race_name($name);
}

function rr_monitor_format_schedule_start
PHP;
    $src = inst_regex_replace_once($src, $scheduleNamePattern, $scheduleNameReplacement, 'monitor schedule-name wrapper');

    $annotationAnchor = <<<'PHP'
    if ($ok && function_exists('rr_schedule_annotate_mrl_race_numbers')) {
        $races = rr_schedule_annotate_mrl_race_numbers($races, $yearIndex);
        if (is_array($debug)) {
            $debug['mrl_identity_source'] = 'year_index';
        }
    }
    $mrlPointsRaces =
PHP;

    $annotationReplacement = <<<'PHP'
    if ($ok && function_exists('rr_schedule_annotate_mrl_race_numbers')) {
        $races = rr_schedule_annotate_mrl_race_numbers($races, $yearIndex);
        if (is_array($debug)) {
            $debug['mrl_identity_source'] = 'year_index';
        }
    }

    // v141: Persist the canonical compact MRL label into every generated
    // schedule row. Full ESPN race_name remains unchanged.
    if ($ok) {
        foreach ($races as &$raceRow) {
            if (!is_array($raceRow)) {
                continue;
            }

            $sourceName = trim((string)($raceRow['race_name'] ?? ''));
            if ($sourceName === '') {
                $sourceName = trim((string)($raceRow['short_name'] ?? ''));
            }

            $raceRow['mrl_race_name'] = mrl_schedule_helper_short_race_name($sourceName);
        }
        unset($raceRow);
    }

    $mrlPointsRaces =
PHP;

    $src = inst_replace_once($src, $annotationAnchor, $annotationReplacement, 'monitor schedule-row canonical annotation');

    return $src;
}

function inst_patch_weekly(string $src): string
{
    $src = inst_replace_once($src, 'VERSION: v068', 'VERSION: v069', 'weekly standings version');

    $changeNeedle = " * CHANGELOG:\n *\n";
    $changeText =
        " * CHANGELOG:\n *\n"
        . " * v069 (9/13/2026 6:34:43 am ET)\n"
        . " *   - CHANGE: Weekly Standings compact race labels now use the shared canonical race_schedule_helper rule.\n"
        . " *   - PRESERVE: Existing compact naming including COTA, Indianapolis RC, Charlotte RC, World Wide Tech, and normal track labels.\n"
        . " *   - PRESERVE: Scoring, snapshots, validation, audit, release history, navigation, print, spreadsheet, and UI behavior are unchanged.\n"
        . " *\n";
    $src = inst_replace_once($src, $changeNeedle, $changeText, 'weekly standings changelog');

    $requireAnchor = "require_once __DIR__ . '/race_results_engine.php';\n";
    $requireReplacement =
        "require_once __DIR__ . '/race_results_engine.php';\n"
        . "require_once __DIR__ . '/race_schedule_helper.php';\n";
    $src = inst_replace_once($src, $requireAnchor, $requireReplacement, 'weekly schedule helper require');

    $pattern = '~function rrsg_short_race_label\(string \$raceName\): string\s*\{.*?\n\}\n\nfunction rrsg_add_validation~s';
    $replacement = <<<'PHP'
function rrsg_short_race_label(string $raceName): string
{
    return mrl_schedule_helper_short_race_name($raceName);
}

function rrsg_add_validation
PHP;

    $src = inst_regex_replace_once($src, $pattern, $replacement, 'weekly short-race wrapper');

    return $src;
}

function inst_repair_schedule_json(string $path): array
{
    $raw = inst_read($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Schedule JSON is invalid.');
    }

    $changed = 0;
    $r28Before = '';
    $r28After = '';

    foreach (['races', 'mrl_points_races'] as $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            continue;
        }

        foreach ($data[$key] as &$race) {
            if (!is_array($race)) continue;

            $num = (int)($race['mrl_race_number'] ?? $race['race_number'] ?? 0);
            $source = trim((string)($race['race_name'] ?? ''));
            if ($source === '') {
                $source = trim((string)($race['short_name'] ?? ''));
            }

            $before = (string)($race['mrl_race_name'] ?? '');
            $after = inst_short_race_name($source);

            if ($num === 28) {
                if ($r28Before === '') $r28Before = $before !== '' ? $before : (string)($race['short_name'] ?? $source);
                $r28After = $after;
            }

            if ($before !== $after) {
                $race['mrl_race_name'] = $after;
                $changed++;
            }
        }
        unset($race);
    }

    if (isset($data['next_race']) && is_array($data['next_race'])) {
        $source = trim((string)($data['next_race']['race_name'] ?? ''));
        if ($source === '') {
            $source = trim((string)($data['next_race']['short_name'] ?? ''));
        }
        $before = (string)($data['next_race']['mrl_race_name'] ?? '');
        $after = inst_short_race_name($source);
        if ($before !== $after) {
            $data['next_race']['mrl_race_name'] = $after;
            $changed++;
        }
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Could not encode repaired schedule JSON.');
    }

    inst_atomic_write($path, $json . "\n");

    return [
        'changed' => $changed,
        'r28_before' => $r28Before,
        'r28_after' => $r28After,
    ];
}

function inst_repair_monitor_state(string $path): array
{
    if (!is_file($path)) {
        return ['changed' => 0, 'detail' => 'State file not present; skipped.'];
    }

    $raw = inst_read($path);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Monitor state JSON is invalid.');
    }

    $changed = 0;

    if (isset($data['byYear']['2026']) && is_array($data['byYear']['2026'])) {
        $y =& $data['byYear']['2026'];

        if (isset($y['race_status']) && is_array($y['race_status'])) {
            $status =& $y['race_status'];
            $full = trim((string)($status['full_race_name'] ?? ''));
            $compact = trim((string)($status['race_name'] ?? ''));

            if (
                strcasecmp($compact, 'World') === 0
                || stripos($full, 'World') !== false
            ) {
                if ($compact !== 'World Wide Tech') {
                    $status['race_name'] = 'World Wide Tech';
                    $changed++;
                }
            }
            unset($status);
        }

        if (isset($y['schedule_status']['next_race']) && is_array($y['schedule_status']['next_race'])) {
            $race =& $y['schedule_status']['next_race'];
            $source = trim((string)($race['race_name'] ?? ''));
            if ($source === '') {
                $source = trim((string)($race['short_name'] ?? ''));
            }
            $after = inst_short_race_name($source);
            $before = (string)($race['mrl_race_name'] ?? '');
            if ($before !== $after) {
                $race['mrl_race_name'] = $after;
                $changed++;
            }
            unset($race);
        }

        unset($y);
    }

    if ($changed > 0) {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Could not encode repaired monitor state JSON.');
        }
        inst_atomic_write($path, $json . "\n");
    }

    return [
        'changed' => $changed,
        'detail' => $changed > 0 ? 'Compact R28 state repaired.' : 'No state repair required.',
    ];
}

$preflight = inst_preflight($targets);
$action = strtolower(trim((string)($_GET['action'] ?? '')));
$result = null;

if ($action === 'install') {
    if (!$preflight['ok']) {
        $result = [
            'ok' => false,
            'messages' => ['INSTALL BLOCKED: preflight did not pass. No files changed.'],
        ];
    } else {
        $backups = [];
        $written = [];
        $messages = [];

        try {
            $filesToBackup = [
                $targets['schedule_helper'],
                $targets['monitor'],
                $targets['weekly'],
                $targets['schedule_json'],
            ];
            if (is_file($targets['monitor_state'])) {
                $filesToBackup[] = $targets['monitor_state'];
            }

            foreach ($filesToBackup as $path) {
                $backup = $path . '.bak_' . $generatedStamp;
                if (!@copy($path, $backup)) {
                    throw new RuntimeException('Could not create backup: ' . $backup);
                }
                $backups[$path] = $backup;
            }

            $scheduleHelperNew = inst_patch_schedule_helper(inst_read($targets['schedule_helper']));
            $monitorNew = inst_patch_monitor(inst_read($targets['monitor']));
            $weeklyNew = inst_patch_weekly(inst_read($targets['weekly']));

            // Sanity checks before touching production source files.
            $sanity = [
                ['content' => $scheduleHelperNew, 'needle' => 'VERSION: v004', 'label' => 'schedule helper v004'],
                ['content' => $scheduleHelperNew, 'needle' => 'function mrl_schedule_helper_short_race_name', 'label' => 'shared short-name helper'],
                ['content' => $monitorNew, 'needle' => 'VERSION: v141', 'label' => 'monitor v141'],
                ['content' => $monitorNew, 'needle' => "\$raceRow['mrl_race_name'] = mrl_schedule_helper_short_race_name(\$sourceName);", 'label' => 'monitor persistent mrl_race_name'],
                ['content' => $weeklyNew, 'needle' => 'VERSION: v069', 'label' => 'weekly v069'],
                ['content' => $weeklyNew, 'needle' => 'return mrl_schedule_helper_short_race_name($raceName);', 'label' => 'weekly shared short-name wrapper'],
            ];

            foreach ($sanity as $check) {
                if (strpos($check['content'], $check['needle']) === false) {
                    throw new RuntimeException('Generated-source sanity check failed: ' . $check['label']);
                }
            }

            inst_atomic_write($targets['schedule_helper'], $scheduleHelperNew);
            $written[] = $targets['schedule_helper'];

            inst_atomic_write($targets['monitor'], $monitorNew);
            $written[] = $targets['monitor'];

            inst_atomic_write($targets['weekly'], $weeklyNew);
            $written[] = $targets['weekly'];

            $scheduleRepair = inst_repair_schedule_json($targets['schedule_json']);
            $written[] = $targets['schedule_json'];

            $stateRepair = inst_repair_monitor_state($targets['monitor_state']);
            if (is_file($targets['monitor_state'])) {
                $written[] = $targets['monitor_state'];
            }

            // Post-install source verification.
            $post = [
                ['path' => $targets['schedule_helper'], 'needle' => 'VERSION: v004'],
                ['path' => $targets['monitor'], 'needle' => 'VERSION: v141'],
                ['path' => $targets['weekly'], 'needle' => 'VERSION: v069'],
            ];
            foreach ($post as $check) {
                $raw = inst_read($check['path']);
                if (strpos($raw, $check['needle']) === false) {
                    throw new RuntimeException('Post-install verification failed: ' . basename($check['path']));
                }
            }

            // Confirm R28 in repaired canonical JSON.
            $afterRaw = inst_read($targets['schedule_json']);
            $afterData = json_decode($afterRaw, true);
            $r28Ok = false;
            if (is_array($afterData)) {
                foreach (($afterData['mrl_points_races'] ?? []) as $race) {
                    if (!is_array($race)) continue;
                    $num = (int)($race['mrl_race_number'] ?? $race['race_number'] ?? 0);
                    if ($num === 28 && (string)($race['mrl_race_name'] ?? '') === 'World Wide Tech') {
                        $r28Ok = true;
                        break;
                    }
                }
            }
            if (!$r28Ok) {
                throw new RuntimeException('Post-install verification failed: R28 canonical schedule label is not World Wide Tech.');
            }

            $messages[] = 'race_schedule_helper.php v003 → v004';
            $messages[] = 'race_results_monitor.php v140 → v141';
            $messages[] = 'weekly_standings.php v068 → v069';
            $messages[] = 'Runtime schedule rows repaired: ' . (string)$scheduleRepair['changed'];
            $messages[] = 'R28: ' . ($scheduleRepair['r28_before'] !== '' ? $scheduleRepair['r28_before'] : '(blank)') . ' → ' . $scheduleRepair['r28_after'];
            $messages[] = 'Monitor state: ' . $stateRepair['detail'];
            $messages[] = 'No database writes performed.';

            $result = [
                'ok' => true,
                'messages' => $messages,
            ];
        } catch (Throwable $e) {
            // Roll back anything touched during this run.
            foreach ($backups as $original => $backup) {
                if (is_file($backup)) {
                    @copy($backup, $original);
                }
            }

            $result = [
                'ok' => false,
                'messages' => [
                    'INSTALL FAILED — rollback attempted for every backed-up file.',
                    $e->getMessage(),
                    'No database writes were performed.',
                ],
            ];
        }
    }
}

$postPreflight = inst_preflight($targets);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>MRL Canonical Short Race Labels Installer <?= inst_h($installerVersion) ?></title>
<style>
:root{color-scheme:dark}
*{box-sizing:border-box}
body{margin:0;background:#111;color:#eee;font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.35}
.wrap{width:min(980px,96vw);margin:18px auto}
.card{background:#1c1c1c;border:1px solid #454545;border-radius:12px;padding:14px 16px;margin:0 0 12px}
h1{font-size:22px;margin:0 0 4px;color:#ffd07a}
h2{font-size:17px;margin:0 0 10px;color:#ffd07a}
.small{font-size:14px;color:#cfcfcf}
.ok{color:#6ee7a8;font-weight:700}
.bad{color:#ff8f8f;font-weight:700}
.warn{color:#ffd07a;font-weight:700}
table{width:100%;border-collapse:collapse}
th,td{text-align:left;padding:7px 8px;border-bottom:1px solid #3c3c3c;vertical-align:top}
th{color:#ffd07a}
.btn{display:inline-block;background:#59420f;border:1px solid #a77b18;color:#ffd07a;font-weight:700;text-decoration:none;padding:9px 14px;border-radius:9px}
.btn:hover{background:#6c5012}
.code{font-family:Consolas,monospace;background:#111;border:1px solid #333;padding:2px 5px;border-radius:4px}
ul{margin:7px 0 0 20px;padding:0}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>MRL Canonical Short Race Labels <?= inst_h($installerVersion) ?></h1>
    <div class="small">Generated <?= inst_h($generatedDisplay) ?> · Production-safe source/data repair · No DB writes</div>
  </div>

  <div class="card">
    <h2>What this installs</h2>
    <ul>
      <li>Centralizes the compact-name rules that originated in Weekly Standings.</li>
      <li>Makes every Race Monitor schedule refresh persist <span class="code">mrl_race_name</span>.</li>
      <li>Keeps full ESPN <span class="code">race_name</span> untouched.</li>
      <li>Repairs R28 immediately to <b>World Wide Tech</b> in generated schedule/status data.</li>
    </ul>
  </div>

  <div class="card">
    <h2>Preflight</h2>
    <table>
      <tr><th>Target</th><th>Status</th><th>Detail</th></tr>
      <?php foreach ($preflight['checks'] as $check): ?>
      <tr>
        <td><?= inst_h($check['label']) ?></td>
        <td class="<?= $check['ok'] ? 'ok' : 'bad' ?>"><?= $check['ok'] ? 'PASS' : 'FAIL' ?></td>
        <td><?= inst_h($check['detail']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <div style="margin-top:10px">R28 label seen by preflight:
      <b><?= inst_h($preflight['r28_before'] !== '' ? $preflight['r28_before'] : '(not found)') ?></b>
    </div>
  </div>

  <?php if ($result !== null): ?>
  <div class="card">
    <h2 class="<?= $result['ok'] ? 'ok' : 'bad' ?>"><?= $result['ok'] ? 'INSTALL COMPLETE' : 'INSTALL NOT COMPLETE' ?></h2>
    <ul>
      <?php foreach ($result['messages'] as $message): ?>
      <li><?= inst_h($message) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <div class="card">
    <?php if ($result === null): ?>
      <?php if ($preflight['ok']): ?>
        <a class="btn" href="?action=install" onclick="return confirm('Install canonical short race-label fix now?');">Install Fix</a>
      <?php else: ?>
        <span class="bad">Install blocked until preflight passes.</span>
      <?php endif; ?>
    <?php elseif ($result['ok']): ?>
      <span class="ok">Done. Reload Team Chart, Race Countdown, and the MRL dashboard to verify World Wide Tech.</span>
    <?php else: ?>
      <span class="bad">Review the failure above before doing anything else.</span>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
