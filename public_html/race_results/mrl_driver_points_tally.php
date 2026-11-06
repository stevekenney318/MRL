<?php
/**
 * MRL Driver Points Tally
 * VERSION: v001
 * LAST MODIFIED: 11/4/2026 11:55:27 pm
 * CHANGELOG:
 * v001 - Initial read-only annual MRL driver race-points audit and XLSX/CSV export.
 *
 * INSTALL: Place this file directly in public_html/race_results/ and open it in a browser.
 * It reads existing year/race folders only; it never writes to the MRL archive.
 * Works without optional PHP DOM or ZipArchive extensions.
 */
declare(strict_types=1);
date_default_timezone_set('America/New_York');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, private');

$baseDir = __DIR__;
$yearDirs = [];
foreach (glob($baseDir . '/[12][0-9][0-9][0-9]', GLOB_ONLYDIR) ?: [] as $dir) {
    $year = basename($dir);
    if (preg_match('/^(19|20)\d{2}$/', $year)) $yearDirs[$year] = $dir;
}
krsort($yearDirs, SORT_NUMERIC);
$selectedYear = isset($_GET['year']) && is_string($_GET['year']) ? $_GET['year'] : (string)(array_key_first($yearDirs) ?? '');
if (!isset($yearDirs[$selectedYear])) $selectedYear = (string)(array_key_first($yearDirs) ?? '');
$export = isset($_GET['export']) && is_string($_GET['export']) ? strtolower($_GET['export']) : '';
$errors = [];
$warnings = [];
$races = [];
$drivers = [];

function cleanText(string $text): string {
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? $text);
}
function cellNumber(string $value): ?int {
    $v = str_replace(',', '', trim($value));
    return preg_match('/^-?\d+$/', $v) ? (int)$v : null;
}
function parseRaceFile(string $path): array {
    if (!class_exists('DOMDocument')) return parseRaceFileWithoutDom($path);
    $html = @file_get_contents($path);
    if ($html === false) throw new RuntimeException('Unable to read snapshot.');
    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $ok = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$ok) throw new RuntimeException('Unable to parse HTML.');
    $xpath = new DOMXPath($dom);
    $tables = $xpath->query('//table');
    if ($tables === false) throw new RuntimeException('No readable tables.');
    foreach ($tables as $table) {
        $rows = $xpath->query('.//tr', $table);
        if ($rows === false) continue;
        $columns = null;
        $results = [];
        foreach ($rows as $row) {
            $cells = $xpath->query('./th|./td', $row);
            if ($cells === false) continue;
            $values = [];
            foreach ($cells as $cell) $values[] = cleanText($cell->textContent);
            if ($columns === null) {
                $normalized = array_map('strtoupper', $values);
                if (in_array('DRIVER', $normalized, true) && in_array('PTS', $normalized, true)) {
                    $columns = array_flip($normalized);
                }
                continue;
            }
            $name = $values[$columns['DRIVER']] ?? '';
            $points = cellNumber($values[$columns['PTS']] ?? '');
            if ($name === '' || $points === null) continue;
            $id = '';
            $class = $row->getAttribute('class');
            if (preg_match('/\bplayer-2020-(\d+)\b/', $class, $m)) $id = $m[1];
            $key = $id !== '' ? 'id:' . $id : 'name:' . strtolower($name);
            if (isset($results[$key])) throw new RuntimeException('Duplicate driver in snapshot: ' . $name);
            $results[$key] = [
                'key' => $key, 'id' => $id, 'name' => $name,
                'car' => $values[$columns['CAR']] ?? '',
                'points' => $points,
                'bonus' => isset($columns['BONUS']) ? cellNumber($values[$columns['BONUS']] ?? '') : null,
                'penalty' => isset($columns['PENALTY']) ? cellNumber($values[$columns['PENALTY']] ?? '') : null,
                'position' => isset($columns['POS']) ? cellNumber($values[$columns['POS']] ?? '') : null
            ];
        }
        if ($columns !== null && count($results) > 0) return $results;
    }
    throw new RuntimeException('Could not find a driver table with DRIVER and PTS columns.');
}
function parseRaceFileWithoutDom(string $path): array {
    $html = @file_get_contents($path);
    if ($html === false) throw new RuntimeException('Unable to read snapshot.');
    preg_match_all('~<tr\b([^>]*)>(.*?)</tr>~is', $html, $matches, PREG_SET_ORDER);
    $columns = null; $results = [];
    foreach ($matches as $match) {
        preg_match_all('~<(?:td|th)\b[^>]*>(.*?)</(?:td|th)>~is', $match[2], $cellMatches);
        $values = array_map(static function ($v) { return cleanText(strip_tags($v)); }, $cellMatches[1]);
        if ($columns === null) {
            $normalized = array_map('strtoupper', $values);
            if (in_array('DRIVER', $normalized, true) && in_array('PTS', $normalized, true)) $columns = array_flip($normalized);
            continue;
        }
        $name = $values[$columns['DRIVER']] ?? '';
        $points = cellNumber($values[$columns['PTS']] ?? '');
        if ($name === '' || $points === null) continue;
        $id = preg_match('/\bplayer-2020-(\d+)\b/', $match[1], $m) ? $m[1] : '';
        $key = $id !== '' ? 'id:' . $id : 'name:' . strtolower($name);
        if (isset($results[$key])) throw new RuntimeException('Duplicate driver in snapshot: ' . $name);
        $results[$key] = [ 'key' => $key, 'id' => $id, 'name' => $name,
            'car' => $values[$columns['CAR']] ?? '', 'points' => $points,
            'bonus' => isset($columns['BONUS']) ? cellNumber($values[$columns['BONUS']] ?? '') : null,
            'penalty' => isset($columns['PENALTY']) ? cellNumber($values[$columns['PENALTY']] ?? '') : null,
            'position' => isset($columns['POS']) ? cellNumber($values[$columns['POS']] ?? '') : null ];
    }
    if (!$results) throw new RuntimeException('No readable driver points table found.');
    return $results;
}
function raceNumber(string $folder): ?int {
    return preg_match('/^R(\d+)(?:_|\b)/i', $folder, $m) ? (int)$m[1] : null;
}
function snapshotStamp(string $filename): string {
    return preg_match('/^snapshot_(\d{8}_\d{9})_mrl\.html$/i', $filename, $m) ? $m[1] : '';
}
function xmlEscape(string $s): string { return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function excelColumn(int $index): string {
    $s = '';
    while ($index > 0) { $index--; $s = chr(65 + ($index % 26)) . $s; $index = intdiv($index, 26); }
    return $s;
}
function sheetXml(array $rows): string {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="15"/><sheetData>';
    foreach ($rows as $ri => $row) {
        $r = $ri + 1;
        $xml .= '<row r="' . $r . '">';
        foreach (array_values($row) as $ci => $value) {
            if ($value === null || $value === '') continue;
            $ref = excelColumn($ci + 1) . $r;
            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '"><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . xmlEscape((string)$value) . '</t></is></c>';
            }
        }
        $xml .= '</row>';
    }
    return $xml . '</sheetData></worksheet>';
}
function zipStore(array $files): string {
    // ZIP store format; does not require PHP ZipArchive or filesystem writes.
    $local = ''; $central = ''; $offset = 0; $count = 0;
    foreach ($files as $name => $content) {
        $size = strlen($content); $crc = crc32($content);
        $header = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0);
        $local .= $header . $name . $content;
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, strlen($name), 0, 0, 0, 0, 0, $offset) . $name;
        $offset += strlen($header) + strlen($name) + $size; $count++;
    }
    return $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, strlen($central), strlen($local), 0);
}
function downloadXlsx(string $filename, array $sheets): void {
    $files = [];
    $types = '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    $workbook = '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
    $relationships = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $i = 0;
    foreach ($sheets as $name => $rows) {
        $i++;
        $types .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $workbook .= '<sheet name="' . xmlEscape($name) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
        $relationships .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        $files['xl/worksheets/sheet' . $i . '.xml'] = sheetXml($rows);
    }
    $files['[Content_Types].xml'] = $types . '</Types>';
    $files['_rels/.rels'] = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    $files['xl/workbook.xml'] = $workbook . '</sheets></workbook>';
    $files['xl/_rels/workbook.xml.rels'] = $relationships . '</Relationships>';
    $bytes = zipStore($files);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($bytes));
    echo $bytes;
    exit;
}
function csvLine($stream, array $values): void {
    $clean = [];
    foreach ($values as $value) {
        $s = (string)($value ?? '');
        // Prevent spreadsheet formula execution in text fields.
        if (preg_match('/^[\s]*[=+@\-]/u', $s) && !is_numeric($s)) $s = "'" . $s;
        $clean[] = $s;
    }
    fputcsv($stream, $clean);
}

if ($selectedYear !== '') {
    $folders = glob($yearDirs[$selectedYear] . '/*', GLOB_ONLYDIR) ?: [];
    foreach ($folders as $folder) {
        $number = raceNumber(basename($folder));
        if ($number === null) continue;
        $matches = glob($folder . '/snapshot_*_mrl.html') ?: [];
        $valid = [];
        foreach ($matches as $file) {
            $stamp = snapshotStamp(basename($file));
            if ($stamp !== '') $valid[$stamp] = $file;
        }
        if (!$valid) { $warnings[] = 'R' . $number . ' (' . basename($folder) . '): no MRL year-driver snapshot found.'; continue; }
        krsort($valid, SORT_STRING);
        $chosen = reset($valid);
        try {
            $rows = parseRaceFile($chosen);
        } catch (Throwable $e) {
            $errors[] = 'R' . $number . ': ' . $e->getMessage() . ' [' . basename($chosen) . ']';
            continue;
        }
        if (isset($races[$number])) {
            $errors[] = 'Duplicate race number R' . $number . ' in multiple folders; skipped ' . basename($folder) . '.';
            continue;
        }
        $races[$number] = [ 'number' => $number, 'folder' => basename($folder), 'file' => basename($chosen), 'count' => count($rows), 'rows' => $rows ];
    }
    ksort($races, SORT_NUMERIC);
    if ($races) {
        $maxRace = max(array_keys($races));
        for ($n = 1; $n <= $maxRace; $n++) if (!isset($races[$n])) $warnings[] = 'R' . $n . ': no usable snapshot included.';
    }
    foreach ($races as $race) {
        foreach ($race['rows'] as $entry) {
            $key = $entry['key'];
            if (!isset($drivers[$key])) $drivers[$key] = ['name' => $entry['name'], 'id' => $entry['id'], 'total' => 0, 'count' => 0, 'by_race' => []];
            $drivers[$key]['name'] = $entry['name'];
            $drivers[$key]['total'] += $entry['points'];
            $drivers[$key]['count']++;
            $drivers[$key]['by_race'][$race['number']] = $entry['points'];
        }
    }
    uasort($drivers, static function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcasecmp($a['name'], $b['name']); });
}

if ($export !== '' && $selectedYear !== '') {
    $rank = 0;
    $summary = [['Rank', 'Driver', 'ESPN Driver ID', 'Races Counted', 'MRL Snapshot PTS Total']];
    $detailHeader = ['Rank', 'Driver', 'ESPN Driver ID', 'Races Counted', 'Total PTS'];
    foreach ($races as $race) $detailHeader[] = 'R' . $race['number'];
    $detail = [$detailHeader];
    foreach ($drivers as $driver) {
        $rank++;
        $summary[] = [$rank, $driver['name'], $driver['id'], $driver['count'], $driver['total']];
        $line = [$rank, $driver['name'], $driver['id'], $driver['count'], $driver['total']];
        foreach ($races as $race) $line[] = $driver['by_race'][$race['number']] ?? null;
        $detail[] = $line;
    }
    $audit = [['Year', $selectedYear], ['Generated (ET)', date('Y-m-d H:i:s')], ['Race folders included', count($races)], ['Drivers', count($drivers)], ['Note', 'Totals are sums of the PTS column from the latest *_mrl.html snapshot per race. No championship reset or external penalty adjustments.'], [], ['Race', 'Folder', 'Snapshot', 'Driver rows']];
    foreach ($races as $race) $audit[] = ['R' . $race['number'], $race['folder'], $race['file'], $race['count']];
    $audit[] = [];
    $audit[] = ['Issues / warnings'];
    foreach (array_merge($errors, $warnings) as $message) $audit[] = [$message];
    if (!$errors && !$warnings) $audit[] = ['None'];
    $baseName = 'MRL_Driver_Points_' . $selectedYear . '_' . date('Ymd_his') . strtolower(date('a'));
    if ($export === 'xlsx') {
        try { downloadXlsx($baseName . '.xlsx', ['Driver Totals' => $summary, 'Race Breakdown' => $detail, 'Audit' => $audit]); }
        catch (Throwable $e) { $errors[] = 'Excel export failed: ' . $e->getMessage(); }
    } elseif ($export === 'csv') {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $baseName . '.csv"');
        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        foreach ($detail as $row) csvLine($out, $row);
        fclose($out);
        exit;
    }
}
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>MRL Driver Points Tally</title>
<style>
:root{color-scheme:dark;font-family:Arial,Helvetica,sans-serif}*{box-sizing:border-box}body{margin:0;background:#111820;color:#e8edf3;font-size:15px}.wrap{max-width:1280px;margin:30px auto;padding:0 20px}h1{margin:0 0 8px;font-size:29px}p{color:#a9b9c9;line-height:1.5}.toolbar{display:flex;flex-wrap:wrap;align-items:end;gap:12px;padding:18px;background:#1b2733;border:1px solid #34485a;border-radius:10px;margin:20px 0}label{display:block;color:#b8c9da;font-size:12px;margin-bottom:6px}select,.btn{background:#293a4c;color:white;border:1px solid #58718b;border-radius:6px;padding:10px 13px;font-size:15px;text-decoration:none;cursor:pointer}.btn.primary{background:#2366a3;border-color:#448ac8}.btn:hover,select:hover{filter:brightness(1.15)}.stats{display:flex;flex-wrap:wrap;gap:12px;margin:15px 0}.stat{background:#1b2733;border:1px solid #34485a;padding:12px 18px;border-radius:8px;min-width:150px}.stat strong{display:block;font-size:24px;margin-top:5px}.stat span{color:#b8c9da;font-size:12px}.panel{background:#1b2733;border:1px solid #34485a;border-radius:10px;margin:18px 0;overflow:hidden}.panel h2{font-size:18px;margin:0;padding:16px 18px;border-bottom:1px solid #34485a}.scroll{overflow-x:auto}table{border-collapse:collapse;width:100%}th,td{padding:10px 12px;border-bottom:1px solid #34485a;text-align:left;white-space:nowrap}th{background:#233447;color:#d8e9fa;position:sticky;top:0}tbody tr:hover{background:#26394b}.num{text-align:right;font-variant-numeric:tabular-nums}.muted{color:#a9b9c9}.notice{border-left:4px solid #e4ad49;background:#332a1c;padding:10px 14px;margin:10px 0}.error{border-left-color:#ee7777;background:#3a2025}.footer{font-size:12px;margin:25px 0;color:#9cb0c3}details{padding:12px 18px}summary{cursor:pointer;color:#c8ddf0}code{color:#c5d9f0}
</style></head><body><div class="wrap">
<h1>MRL Driver Points Tally</h1><p>Read-only season totals from the <strong>latest MRL year-driver snapshot</strong> in each race folder. No ESPN requests, no changes to scoring, and no modifications to existing snapshots.</p>
<form class="toolbar" method="get"><div><label for="year">Race year</label><select name="year" id="year" onchange="this.form.submit()">
<?php foreach ($yearDirs as $year => $dir): ?><option value="<?=h($year)?>" <?=$selectedYear === $year ? 'selected' : ''?>><?=h($year)?></option><?php endforeach; ?>
</select></div><div><button class="btn" type="submit">View tally</button></div>
<?php if ($selectedYear !== '' && $races): ?><div><a class="btn primary" href="?year=<?=h(rawurlencode($selectedYear))?>&amp;export=xlsx">Export Excel (.xlsx)</a></div><div><a class="btn" href="?year=<?=h(rawurlencode($selectedYear))?>&amp;export=csv">Export CSV</a></div><?php endif; ?></form>
<?php if (!$yearDirs): ?><div class="notice error">No year directories found. Place this PHP file in <code>public_html/race_results/</code>.</div><?php endif; ?>
<?php foreach ($errors as $message): ?><div class="notice error"><?=h($message)?></div><?php endforeach; ?>
<?php if ($selectedYear !== ''): ?>
<div class="stats"><div class="stat"><span>Year</span><strong><?=h($selectedYear)?></strong></div><div class="stat"><span>Races included</span><strong><?=count($races)?></strong></div><div class="stat"><span>Drivers found</span><strong><?=count($drivers)?></strong></div><div class="stat"><span>Issues / warnings</span><strong><?=count($errors) + count($warnings)?></strong></div></div>
<?php if ($warnings): ?><div class="panel"><details open><summary><?=count($warnings)?> archive warning(s) — review before trusting totals</summary><?php foreach ($warnings as $message): ?><p><?=h($message)?></p><?php endforeach; ?></details></div><?php endif; ?>
<div class="panel"><h2><?=h($selectedYear)?> — MRL Driver Race Points</h2><div class="scroll"><table><thead><tr><th class="num">Rank</th><th>Driver</th><th class="num">Races counted</th><th class="num">PTS total</th></tr></thead><tbody>
<?php $rank = 0; foreach ($drivers as $driver): $rank++; ?><tr><td class="num"><?=$rank?></td><td><?=h($driver['name'])?></td><td class="num"><?=$driver['count']?></td><td class="num"><strong><?=number_format($driver['total'])?></strong></td></tr><?php endforeach; ?>
<?php if (!$drivers): ?><tr><td colspan="4">No usable MRL snapshots found for this year.</td></tr><?php endif; ?></tbody></table></div></div>
<div class="panel"><h2>Race Snapshot Audit</h2><div class="scroll"><table><thead><tr><th>Race</th><th>Folder</th><th>Latest snapshot used</th><th class="num">Drivers</th></tr></thead><tbody>
<?php foreach ($races as $race): ?><tr><td>R<?=h($race['number'])?></td><td><?=h($race['folder'])?></td><td><?=h($race['file'])?></td><td class="num"><?=h($race['count'])?></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php endif; ?>
<p class="footer">PTS is counted once per driver per race. BONUS and PENALTY columns are not added or subtracted again. This report does not apply NASCAR championship resets or external championship penalties. Excel includes Driver Totals, Race Breakdown, and Audit worksheets. v001.</p>
</div></body></html>
