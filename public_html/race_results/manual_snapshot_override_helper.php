<?php
declare(strict_types=1);

/**
 * manual_snapshot_override_helper.php
 *
 * VERSION: v001
 * CREATED: 11/9/2026
 *
 * PURPOSE:
 * - Keep manual scoring snapshots completely separate from ESPN canonical snapshots.
 * - Store manual snapshot state in a year-level manifest.
 * - Allow Weekly Standings to use one active manual override for a race without
 *   changing ESPN canonical snapshots, final_table_hash.txt, revision_meta.json,
 *   or the revision monitor's canonical snapshot chain.
 * - Supply an in-memory synthetic Weekly Standings release row for the active
 *   manual snapshot. The real release-history JSON is not modified.
 *
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

if (!function_exists('mrlm_load_json_file')) {
function mrlm_load_json_file(string $path): array
{
    if (!is_file($path)) return [];
    $raw = @file_get_contents($path);
    if ($raw === false || trim($raw) === '') return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
}

if (!function_exists('mrlm_manifest_path')) {
function mrlm_manifest_path(string $yearFolder): string
{
    return rtrim($yearFolder, '/\\') . '/_manual_snapshot_overrides.json';
}
}

if (!function_exists('mrlm_default_manifest')) {
function mrlm_default_manifest(string $year): array
{
    return [
        'signature' => 'MRL_MANUAL_SNAPSHOT_OVERRIDES v001',
        'version' => 'v001',
        'year' => $year,
        'timezone' => 'America/New_York',
        'updated_at' => date('Y-m-d H:i:s'),
        'notes' => [
            'Manual snapshots are MRL scoring overrides only.',
            'ESPN canonical snapshots and final_table_hash.txt remain untouched.',
            'The revision monitor must continue watching ESPN canonical snapshots only.',
            'Deactivating a manual override restores normal ESPN-based scoring without deleting the manual audit files.'
        ],
        'active_by_race' => [],
        'records' => [],
    ];
}
}

if (!function_exists('mrlm_load_manifest')) {
function mrlm_load_manifest(string $yearFolder, string $year = ''): array
{
    $path = mrlm_manifest_path($yearFolder);
    $data = mrlm_load_json_file($path);
    if ($year === '') $year = basename(rtrim($yearFolder, '/\\'));
    $base = mrlm_default_manifest($year);
    foreach ($base as $k => $v) {
        if (!array_key_exists($k, $data)) $data[$k] = $v;
    }
    if (!isset($data['active_by_race']) || !is_array($data['active_by_race'])) $data['active_by_race'] = [];
    if (!isset($data['records']) || !is_array($data['records'])) $data['records'] = [];
    return $data;
}
}

if (!function_exists('mrlm_write_manifest')) {
function mrlm_write_manifest(string $yearFolder, array $manifest): array
{
    $result = ['ok' => false, 'message' => '', 'path' => mrlm_manifest_path($yearFolder), 'backup' => ''];
    $path = $result['path'];
    $manifest['updated_at'] = date('Y-m-d H:i:s');
    $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        $result['message'] = 'Could not encode manifest JSON.';
        return $result;
    }
    $json .= "\n";
    $dir = dirname($path);
    if (!is_dir($dir) || !is_writable($dir)) {
        $result['message'] = 'Year folder is not writable.';
        return $result;
    }
    if (is_file($path)) {
        $backup = $path . '.previous';
        if (@copy($path, $backup)) $result['backup'] = $backup;
    }
    $tmp = $path . '.tmp_' . getmypid() . '_' . date('Ymd_His');
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        $result['message'] = 'Could not write temporary manifest.';
        return $result;
    }
    @chmod($tmp, 0644);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        $result['message'] = 'Could not replace manifest.';
        return $result;
    }
    @chmod($path, 0644);
    $verify = @file_get_contents($path);
    if ($verify === false || hash('sha256', $verify) !== hash('sha256', $json)) {
        $result['message'] = 'Manifest read-back verification failed.';
        return $result;
    }
    $result['ok'] = true;
    $result['message'] = 'Manual snapshot manifest saved.';
    return $result;
}
}

if (!function_exists('mrlm_find_record')) {
function mrlm_find_record(array $manifest, string $manualId): array
{
    foreach ($manifest['records'] ?? [] as $row) {
        if (is_array($row) && (string)($row['manual_id'] ?? '') === $manualId) return $row;
    }
    return [];
}
}

if (!function_exists('mrlm_active_record_for_race')) {
function mrlm_active_record_for_race(string $yearFolder, string $raceCode): array
{
    $manifest = mrlm_load_manifest($yearFolder);
    $manualId = (string)($manifest['active_by_race'][$raceCode] ?? '');
    if ($manualId === '') return [];
    $row = mrlm_find_record($manifest, $manualId);
    if (empty($row)) return [];
    $row['manual_override_active'] = true;
    return $row;
}
}

if (!function_exists('mrlm_record_snapshot_path')) {
function mrlm_record_snapshot_path(string $yearFolder, array $record): string
{
    $raceFolderRel = trim((string)($record['race_folder_rel'] ?? ''), '/\\');
    $snapshotRel = trim((string)($record['snapshot_rel_path'] ?? ''), '/\\');
    if ($raceFolderRel === '' || $snapshotRel === '') return '';
    $path = rtrim($yearFolder, '/\\') . '/' . basename($raceFolderRel) . '/' . str_replace('\\', '/', $snapshotRel);
    return is_file($path) ? $path : '';
}
}

if (!function_exists('mrlm_active_snapshot_path')) {
function mrlm_active_snapshot_path(string $yearFolder, string $raceCode, string $raceFolder): string
{
    $record = mrlm_active_record_for_race($yearFolder, $raceCode);
    if (empty($record)) return '';
    $snapshotRel = trim((string)($record['snapshot_rel_path'] ?? ''), '/\\');
    if ($snapshotRel === '') return '';
    $path = rtrim($raceFolder, '/\\') . '/' . str_replace('\\', '/', $snapshotRel);
    return is_file($path) ? $path : '';
}
}

if (!function_exists('mrlm_active_records_through_race')) {
function mrlm_active_records_through_race(string $yearFolder, int $maxRaceNumber): array
{
    $manifest = mrlm_load_manifest($yearFolder);
    $out = [];
    foreach ($manifest['active_by_race'] ?? [] as $raceCode => $manualId) {
        $manualId = (string)$manualId;
        if ($manualId === '') continue;
        $row = mrlm_find_record($manifest, $manualId);
        if (empty($row)) continue;
        $raceNumber = (int)($row['race_number'] ?? 0);
        if ($raceNumber <= 0 || $raceNumber > $maxRaceNumber) continue;
        $row['manual_override_active'] = true;
        $out[] = $row;
    }
    usort($out, function ($a, $b) {
        return ((int)($a['race_number'] ?? 0)) <=> ((int)($b['race_number'] ?? 0));
    });
    return $out;
}
}

if (!function_exists('mrlm_short_display_time')) {
function mrlm_short_display_time(string $raw): string
{
    try {
        $dt = new DateTime($raw, new DateTimeZone('America/New_York'));
        return $dt->format('n/j/y g:ia');
    } catch (Exception $e) {
        return $raw;
    }
}
}

if (!function_exists('mrlm_public_display_time')) {
function mrlm_public_display_time(string $raw): string
{
    try {
        $dt = new DateTime($raw, new DateTimeZone('America/New_York'));
        return $dt->format('M j, Y g:i A');
    } catch (Exception $e) {
        return $raw;
    }
}
}

if (!function_exists('mrlm_append_active_manual_release')) {
function mrlm_append_active_manual_release(array $history, string $yearFolder, string $raceCode): array
{
    $record = mrlm_active_record_for_race($yearFolder, $raceCode);
    if (empty($record)) return $history;

    if (!isset($history['releases']) || !is_array($history['releases'])) $history['releases'] = [];
    $manualId = (string)($record['manual_id'] ?? '');
    foreach ($history['releases'] as $existing) {
        if (is_array($existing) && (string)($existing['release_id'] ?? '') === $manualId) return $history;
    }

    $version = trim((string)($record['display_version'] ?? ''));
    if ($version === '') {
        $count = 0;
        foreach ($history['releases'] as $existing) {
            if (is_array($existing) && (string)($existing['race_code'] ?? '') === $raceCode) $count++;
        }
        $version = 'v' . (string)($count + 1);
    }

    $effectiveAt = (string)($record['effective_at'] ?? $record['created_at'] ?? '');
    $stamp = (string)($record['effective_stamp'] ?? '');
    $reason = trim((string)($record['reason'] ?? ''));
    if ($reason === '') $reason = 'Manual scoring correction based on official information not yet reflected by the ESPN source snapshot.';

    $history['releases'][] = [
        'release_id' => $manualId,
        'generated_id' => $manualId,
        'race_code' => $raceCode,
        'race_number' => (int)($record['race_number'] ?? 0),
        'race_name' => (string)($record['race_name'] ?? ''),
        'race_folder' => (string)($record['race_folder_rel'] ?? ''),
        'release_type' => 'manual',
        'source_type' => 'manual_override',
        'status' => 'released',
        'public_status' => 'Manual scoring override',
        'snapshot_file' => basename((string)($record['snapshot_rel_path'] ?? '')),
        'snapshot_rel_path' => (string)($record['snapshot_rel_path'] ?? ''),
        'snapshot_id' => $stamp,
        'source_snapshot_id' => (string)($record['source_snapshot_id'] ?? ''),
        'released_at' => $effectiveAt,
        'released_at_display' => mrlm_public_display_time($effectiveAt),
        'released_at_short' => mrlm_short_display_time($effectiveAt),
        'display_version' => $version . ' MANUAL',
        'version_label' => $version . ' MANUAL',
        'version_dropdown_label' => $version . ' MANUAL (' . mrlm_short_display_time($effectiveAt) . ')',
        'is_current_release' => true,
        'is_current_snapshot' => true,
        'manual_override_active' => true,
        'mrl_impact' => true,
        'change_status' => 'manual_scoring_override',
        'change_status_label' => 'Manual scoring override',
        'reason' => $reason,
        'reason_public' => $reason,
        'builder_note' => 'Synthetic in-memory Weekly Standings release row. Real release-history JSON is unchanged.',
    ];

    return $history;
}
}
