<?php
declare(strict_types=1);

/**
 * install_admin_db_backup_manager_v002.php
 *
 * VERSION: v002
 * LAST MODIFIED: 8/28/2026 11:21:21 pm
 *
 * PURPOSE:
 * Upgrade /admin_db_backup.php v1.4 into a safer backup file manager while
 * preserving the existing backup creation and SQL restore engine.
 *
 * NEW:
 * - Active / Archive / To Be Deleted areas
 * - single-file radio target for Restore / Rename
 * - multi-file checkboxes for Archive / Move / Restore-to-Active / Delete
 * - rename automatically preserves .sql
 * - 30-day delete quarantine; no automatic purging
 * - permanent delete only from To Be Deleted and only after 30 days
 * - archive restore can execute SQL directly without first moving the file
 * - conflict protection: never overwrite an existing file
 * - path confinement: only validated .sql basenames inside managed folders
 *
 * TARGET:
 *   /admin_db_backup.php v1.4 -> v1.5
 *
 * SAFETY:
 * - exact Git blob SHA-1 gate for the uploaded/current production v1.4 file
 * - CRLF/LF normalization for deterministic patch matching
 * - patch anchors must match exactly
 * - backup before replacement
 * - replacement signature checks
 * - rollback on failure
 * - existing DB backup creation and SQL parser/execution logic preserved
 *
 * LOCATION:
 * Put this installer in public_html/.
 */

date_default_timezone_set('America/New_York');

const BASE_GIT_BLOB_SHA1 = '0cf48b094b3c02e5efe43cf16ae165e894fe27b1';

$target = __DIR__ . '/admin_db_backup.php';

function ih(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function row(string $label, bool $ok, string $detail=''): void {
    echo '<tr><td>'.ih($label).'</td><td class="'.($ok?'ok':'bad').'">'.($ok?'PASS':'FAIL').'</td><td>'.ih($detail).'</td></tr>';
}

function git_blob_sha1(string $data): string {
    return sha1('blob ' . strlen($data) . "\0" . $data);
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

function replace_section(string $source, string $start, string $end, string $replacement, string $label, array &$log): ?string {
    $startPos = strpos($source, $start);
    if ($startPos === false) {
        $log[] = 'PATCH FAIL: ' . $label . ' start marker not found.';
        return null;
    }
    if (strpos($source, $start, $startPos + 1) !== false) {
        $log[] = 'PATCH FAIL: ' . $label . ' start marker was not unique.';
        return null;
    }

    $endPos = strpos($source, $end, $startPos + strlen($start));
    if ($endPos === false) {
        $log[] = 'PATCH FAIL: ' . $label . ' end marker not found.';
        return null;
    }

    $log[] = 'PATCH PASS: ' . $label . '.';
    return substr($source, 0, $startPos) . $replacement . substr($source, $endPos);
}

function build_v15(string $source, array &$log): ?string {
    // Production file uses CRLF line endings; normalize only for deterministic patching.
    $s = str_replace(["\r\n", "\r"], "\n", $source);

    $old = "    v1.4  (2026-02-21)  changed time zone +00:00 to SYSTEM                  \n"
         . "    ============================================================";
    $new = "    v1.4  (2026-02-21)  changed time zone +00:00 to SYSTEM\n\n"
         . "    v1.5  (8/28/2026 11:21:21 pm)  Safer backup file manager.\n"
         . "                       - Active / Archive / To Be Deleted areas\n"
         . "                       - Radio = single-file Restore / Rename target\n"
         . "                       - Checkbox = multi-file management target\n"
         . "                       - Archive / move back / quarantine operations\n"
         . "                       - Rename preserves .sql and refuses overwrites\n"
         . "                       - 30-day delete quarantine; manual final delete only\n"
         . "                       - Restore directly from Active or Archive\n"
         . "                       - Permanent delete only from To Be Deleted after 30 days\n"
         . "    ============================================================";
    $next = replace_once($s, $old, $new, 'v1.5 version history', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "// Backups live in a folder right next to this script.\n"
         . "\$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'db_backups';";
    $new = "// Backups live in a folder right next to this script.\n"
         . "\$backupDir = __DIR__ . DIRECTORY_SEPARATOR . 'db_backups';\n"
         . "\$archiveDir = \$backupDir . DIRECTORY_SEPARATOR . 'Archive';\n"
         . "\$deleteDir = \$backupDir . DIRECTORY_SEPARATOR . 'To_Be_Deleted';\n"
         . "\$deleteRetentionDays = 30;";
    $next = replace_once($s, $old, $new, 'managed backup directories', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "function safeFileBase(string \$filename): string {\n"
         . "    \$filename = basename(\$filename);\n"
         . "    \$filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', \$filename);\n"
         . "    return \$filename;\n"
         . "}\n";
    $new = $old . <<<'PHP'

function isValidSqlBackupName(string $filename): bool {
    $filename = trim($filename);
    if ($filename === '' || basename($filename) !== $filename) return false;
    if (strpos($filename, '..') !== false) return false;
    if (!preg_match('/^[A-Za-z0-9 _().-]+\.sql$/i', $filename)) return false;
    return true;
}

function normalizeSqlRename(string $requested): string {
    $requested = trim($requested);
    if ($requested === '') return '';

    $requested = preg_replace('/\.sql$/i', '', $requested);
    $requested = trim((string)$requested);

    if ($requested === '' || strpos($requested, '..') !== false) return '';
    if (!preg_match('/^[A-Za-z0-9 _().-]+$/', $requested)) return '';

    return $requested . '.sql';
}

function ensureManagedDir(string $dir): array {
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            return [false, "Could not create managed backup directory: $dir"];
        }
    }
    if (!is_writable($dir)) {
        return [false, "Managed backup directory is not writable: $dir"];
    }
    return [true, ''];
}

function managedAreaDir(string $area, string $backupDir, string $archiveDir, string $deleteDir): string {
    if ($area === 'archive') return $archiveDir;
    if ($area === 'delete') return $deleteDir;
    return $backupDir;
}

function selectedSqlFiles($raw): array {
    if (!is_array($raw)) return [];

    $out = [];
    foreach ($raw as $item) {
        $name = trim((string)$item);
        if (!isValidSqlBackupName($name)) continue;
        if (!in_array($name, $out, true)) $out[] = $name;
    }
    return $out;
}

function destinationNameExists(string $dir, string $filename): bool {
    if (!is_dir($dir)) return false;
    $target = strtolower($filename);
    $items = scandir($dir);
    if (!is_array($items)) return is_file($dir . DIRECTORY_SEPARATOR . $filename);

    foreach ($items as $item) {
        if (strtolower((string)$item) === $target) return true;
    }
    return false;
}

function moveManagedSqlFile(string $srcDir, string $dstDir, string $filename, bool $markMoveTime = false): array {
    if (!isValidSqlBackupName($filename)) return [false, "Invalid SQL backup filename: $filename"];

    $src = $srcDir . DIRECTORY_SEPARATOR . $filename;
    $dst = $dstDir . DIRECTORY_SEPARATOR . $filename;

    if (!is_file($src)) return [false, "File not found: $filename"];
    if (destinationNameExists($dstDir, $filename)) return [false, "Destination already contains: $filename"];

    $ok = @rename($src, $dst);
    if (!$ok) {
        $ok = @copy($src, $dst);
        if ($ok) {
            if (!@unlink($src)) {
                @unlink($dst);
                return [false, "Could not remove source after copying: $filename"];
            }
        }
    }

    if (!$ok) return [false, "Could not move: $filename"];

    if ($markMoveTime) {
        @touch($dst);
    }

    return [true, ''];
}

function renameManagedSqlFile(string $dir, string $oldName, string $newRequested): array {
    if (!isValidSqlBackupName($oldName)) return [false, 'Invalid source filename.'];

    $newName = normalizeSqlRename($newRequested);
    if ($newName === '') {
        return [false, 'New filename is invalid. Use letters, numbers, spaces, underscore, dash, periods or parentheses.'];
    }

    $src = $dir . DIRECTORY_SEPARATOR . $oldName;
    $dst = $dir . DIRECTORY_SEPARATOR . $newName;

    if (!is_file($src)) return [false, "Source file not found: $oldName"];
    if (strcasecmp($oldName, $newName) !== 0 && destinationNameExists($dir, $newName)) {
        return [false, "A file named $newName already exists."];
    }
    if ($oldName === $newName) return [true, "Filename already is $newName"];

    if (!@rename($src, $dst)) return [false, "Could not rename $oldName to $newName"];
    return [true, "Renamed $oldName to $newName"];
}

function quarantineEligibleInfo(string $path, int $retentionDays): array {
    $mtime = @filemtime($path);
    if (!$mtime) $mtime = time();

    $eligibleTs = $mtime + ($retentionDays * 86400);
    $remainingSeconds = max(0, $eligibleTs - time());
    $remainingDays = (int)ceil($remainingSeconds / 86400);

    return [
        'moved_ts' => $mtime,
        'eligible_ts' => $eligibleTs,
        'eligible' => time() >= $eligibleTs,
        'remaining_days' => $remainingDays,
    ];
}
PHP;
    $next = replace_once($s, $old, $new, 'managed-file helper functions', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "[\$dirOk, \$dirErr] = ensureBackupDir(\$backupDir);\n"
         . "if (!\$dirOk) {\n"
         . "    \$errors[] = \$dirErr;\n"
         . "}\n";
    $new = $old
         . "\n[\$archiveOk, \$archiveErr] = ensureManagedDir(\$archiveDir);\n"
         . "if (!\$archiveOk) {\n"
         . "    \$errors[] = \$archiveErr;\n"
         . "}\n"
         . "[\$deleteOk, \$deleteErr] = ensureManagedDir(\$deleteDir);\n"
         . "if (!\$deleteOk) {\n"
         . "    \$errors[] = \$deleteErr;\n"
         . "}\n";
    $next = replace_once($s, $old, $new, 'managed-folder preflight', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "\$restoreFile         = safeFileBase((string)(\$_POST['restore_file'] ?? ''));\n";
    $new = $old
         . "\$restoreArea         = (string)(\$_POST['restore_area'] ?? 'active');\n"
         . "if (!in_array(\$restoreArea, ['active', 'archive'], true)) \$restoreArea = 'active';\n"
         . "\$singleFile          = trim((string)(\$_POST['single_file'] ?? ''));\n"
         . "\$renameTo           = trim((string)(\$_POST['rename_to'] ?? ''));\n";
    $next = replace_once($s, $old, $new, 'management POST fields', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "\$createdFile = '';\$didPostAction = false;\n";
    $new = $old . <<<'PHP'

$managementActions = [
    'archive_selected',
    'trash_selected',
    'unarchive_selected',
    'archive_to_trash_selected',
    'trash_restore_active_selected',
    'permanent_delete_selected',
    'rename_file',
];

if (in_array($action, $managementActions, true) && count($errors) === 0) {
    $didPostAction = true;

    if ($action === 'archive_selected' || $action === 'trash_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No backup files selected.';
        } else {
            foreach ($selected as $name) {
                $destination = $action === 'archive_selected' ? $archiveDir : $deleteDir;
                $markMoveTime = $action === 'trash_selected';
                [$ok, $msg] = moveManagedSqlFile($backupDir, $destination, $name, $markMoveTime);
                if ($ok) {
                    $messages[] = ($action === 'archive_selected' ? 'Archived: ' : 'Moved to To Be Deleted: ') . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'unarchive_selected' || $action === 'archive_to_trash_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No archive files selected.';
        } else {
            foreach ($selected as $name) {
                $destination = $action === 'unarchive_selected' ? $backupDir : $deleteDir;
                $markMoveTime = $action === 'archive_to_trash_selected';
                [$ok, $msg] = moveManagedSqlFile($archiveDir, $destination, $name, $markMoveTime);
                if ($ok) {
                    $messages[] = ($action === 'unarchive_selected' ? 'Moved back to Active: ' : 'Moved Archive file to To Be Deleted: ') . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'trash_restore_active_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No To Be Deleted files selected.';
        } else {
            foreach ($selected as $name) {
                [$ok, $msg] = moveManagedSqlFile($deleteDir, $backupDir, $name, false);
                if ($ok) {
                    $messages[] = 'Restored to Active: ' . $name;
                } else {
                    $errors[] = $msg;
                }
            }
        }
    }

    if ($action === 'permanent_delete_selected') {
        $selected = selectedSqlFiles($_POST['selected_files'] ?? []);
        if (count($selected) === 0) {
            $errors[] = 'No To Be Deleted files selected.';
        } else {
            foreach ($selected as $name) {
                $path = $deleteDir . DIRECTORY_SEPARATOR . $name;
                if (!is_file($path)) {
                    $errors[] = 'File not found: ' . $name;
                    continue;
                }

                $info = quarantineEligibleInfo($path, $deleteRetentionDays);
                if (!$info['eligible']) {
                    $errors[] = $name . ' is not yet eligible for permanent deletion (' . $info['remaining_days'] . ' day(s) remaining).';
                    continue;
                }

                if (@unlink($path)) {
                    $messages[] = 'Permanently deleted: ' . $name;
                } else {
                    $errors[] = 'Could not permanently delete: ' . $name;
                }
            }
        }
    }

    if ($action === 'rename_file') {
        $area = (string)($_POST['rename_area'] ?? 'active');
        if (!in_array($area, ['active', 'archive'], true)) $area = 'active';

        $oldName = trim((string)($_POST['single_file'] ?? ''));
        $dir = $area === 'archive' ? $archiveDir : $backupDir;

        if (!isValidSqlBackupName($oldName)) {
            $errors[] = 'Select one file with the radio button before renaming.';
        } else {
            [$ok, $msg] = renameManagedSqlFile($dir, $oldName, $renameTo);
            if ($ok) $messages[] = $msg;
            else $errors[] = $msg;
        }
    }
}
PHP;
    $next = replace_once($s, $old, $new, 'file-management actions', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "        \$path = \$backupDir . DIRECTORY_SEPARATOR . \$restoreFile;\n";
    $new = "        \$restoreDir = managedAreaDir(\$restoreArea, \$backupDir, \$archiveDir, \$deleteDir);\n"
         . "        \$path = \$restoreDir . DIRECTORY_SEPARATOR . \$restoreFile;\n";
    $next = replace_once($s, $old, $new, 'restore source supports Active or Archive', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "\$files = listBackupFiles(\$backupDir);\n";
    $new = "\$files = listBackupFiles(\$backupDir);\n"
         . "\$archiveFiles = listBackupFiles(\$archiveDir);\n"
         . "\$deleteFiles = listBackupFiles(\$deleteDir);\n";
    $next = replace_once($s, $old, $new, 'three file lists', $log);
    if ($next === null) return null;
    $s = $next;

    $old = "    .btn-primary{ background:#2f6feb !important; color:#fff !important; }\n"
         . "    .btn-danger{ background:#b82b2b !important; color:#fff !important; }\n";
    $new = $old
         . "    .btn-secondary{ background:#555f6d !important; color:#fff !important; }\n"
         . "    .btn-warning{ background:#9b6a12 !important; color:#fff !important; }\n"
         . "    .btn-safe{ background:#237a45 !important; color:#fff !important; }\n"
         . "    .manager-note{ color:#c9bfa9 !important; font-size:13px !important; line-height:1.45 !important; }\n"
         . "    .eligible{ color:#ff8f8f !important; font-weight:bold !important; }\n"
         . "    .waiting{ color:#ffd88a !important; }\n"
         . "    input[type=text]{ background:#121212 !important; color:#fff !important; border:1px solid #444 !important; border-radius:6px !important; padding:9px 10px !important; min-width:330px !important; }\n";
    $next = replace_once($s, $old, $new, 'manager UI styles', $log);
    if ($next === null) return null;
    $s = $next;

    $restoreStart = "    <div class=\"card\">\n"
                  . "        <div style=\"font-size:18px; font-weight:bold; color:#d8c08a;\">Restore Backup</div>";
    $footerMarker = "    <div style=\"font-size:10px; color:#999; text-align:right; margin:14px 10px 8px 10px; padding:0;\">\n"
                  . "        admin_db_backup.php\n"
                  . "    </div>";

    $managerUi = <<<'HTML'
    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">Active Backups</div>
        <div class="manager-note" style="margin-top:6px;">
            Radio button = one-file action (Restore or Rename). Checkbox = multi-file action (Archive or Move to To Be Deleted).
        </div>

        <?php if (count($files) === 0): ?>
            <div class="muted" style="margin-top:10px;">No active backup files found.</div>
        <?php else: ?>
            <form method="post" action="">
                <input type="hidden" name="restore_area" value="active">
                <input type="hidden" name="rename_area" value="active">

                <table>
                    <thead>
                        <tr>
                            <th style="width:54px;">One</th>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:160px;">Modified</th>
                            <th style="width:120px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($files as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $mtime = @filemtime($fPath);
                            $size = @filesize($fPath);
                        ?>
                        <tr>
                            <td><input type="radio" name="restore_file" value="<?php echo h($base); ?>"></td>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h($mtime ? date('Y-m-d H:i:s', $mtime) : ''); ?></td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <label class="chk"><input type="checkbox" name="restore_dryrun"> Dry-run (do not execute SQL)</label>
                    <label class="chk"><input type="checkbox" name="restore_strip_definer" checked> Strip DEFINER during restore</label>
                    <label class="chk"><input type="checkbox" name="restore_disable_fk" checked> Disable foreign key checks during restore</label>
                </div>

                <div class="row">
                    <button class="btn btn-danger" type="submit" name="action" value="restore_backup"
                            onclick="return confirm('Restore can overwrite schema/data. Continue?');">Restore Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <input type="text" name="rename_to" placeholder="New filename (the .sql extension is automatic)">
                    <button class="btn btn-secondary" type="submit" name="action" value="rename_file">Rename Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <button class="btn btn-safe" type="submit" name="action" value="archive_selected"
                            onclick="return confirm('Archive all checked backup files?');">Archive Checked</button>
                    <button class="btn btn-warning" type="submit" name="action" value="trash_selected"
                            onclick="return confirm('Move all checked files to To Be Deleted? They will be held at least 30 days before permanent deletion is allowed.');">Move Checked to To Be Deleted</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($restoreReport['file'] !== ''): ?>
            <div class="muted" style="margin-top:14px;">
                Restore report:
                <span class="mono"><?php echo h($restoreReport['file']); ?></span>
                | bytes: <?php echo h((string)$restoreReport['bytes']); ?>
                | statements: <?php echo h((string)$restoreReport['statements']); ?>
            </div>
            <?php if (count($restoreReport['errors']) > 0): ?>
                <textarea readonly class="mono"><?php echo h(implode("\n", $restoreReport['errors'])); ?></textarea>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">Archive</div>
        <div class="manager-note" style="margin-top:6px;">
            Long-term keepers. Restore can run directly from Archive. Checked files can be moved back to Active or sent to To Be Deleted.
        </div>

        <?php if (count($archiveFiles) === 0): ?>
            <div class="muted" style="margin-top:10px;">Archive is empty.</div>
        <?php else: ?>
            <form method="post" action="">
                <input type="hidden" name="restore_area" value="archive">
                <input type="hidden" name="rename_area" value="archive">

                <table>
                    <thead>
                        <tr>
                            <th style="width:54px;">One</th>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:160px;">Modified</th>
                            <th style="width:120px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($archiveFiles as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $mtime = @filemtime($fPath);
                            $size = @filesize($fPath);
                        ?>
                        <tr>
                            <td><input type="radio" name="restore_file" value="<?php echo h($base); ?>"></td>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h($mtime ? date('Y-m-d H:i:s', $mtime) : ''); ?></td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <label class="chk"><input type="checkbox" name="restore_dryrun"> Dry-run (do not execute SQL)</label>
                    <label class="chk"><input type="checkbox" name="restore_strip_definer" checked> Strip DEFINER during restore</label>
                    <label class="chk"><input type="checkbox" name="restore_disable_fk" checked> Disable foreign key checks during restore</label>
                </div>

                <div class="row">
                    <button class="btn btn-danger" type="submit" name="action" value="restore_backup"
                            onclick="return confirm('Restore this archived backup into the database?');">Restore Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <input type="text" name="rename_to" placeholder="New filename (the .sql extension is automatic)">
                    <button class="btn btn-secondary" type="submit" name="action" value="rename_file">Rename Selected Radio</button>
                </div>

                <div class="row" style="margin-top:18px !important;">
                    <button class="btn btn-primary" type="submit" name="action" value="unarchive_selected">Move Checked to Active</button>
                    <button class="btn btn-warning" type="submit" name="action" value="archive_to_trash_selected"
                            onclick="return confirm('Move checked Archive files to To Be Deleted?');">Move Checked to To Be Deleted</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="card">
        <div style="font-size:18px; font-weight:bold; color:#d8c08a;">To Be Deleted</div>
        <div class="manager-note" style="margin-top:6px;">
            Safety quarantine. Moving a file here starts a new 30-day hold. Nothing is deleted automatically.
            Permanent deletion is refused until the hold expires.
        </div>

        <?php if (count($deleteFiles) === 0): ?>
            <div class="muted" style="margin-top:10px;">Nothing is waiting to be deleted.</div>
        <?php else: ?>
            <form method="post" action="">
                <table>
                    <thead>
                        <tr>
                            <th style="width:68px;">Select</th>
                            <th>File</th>
                            <th style="width:150px;">Moved Here</th>
                            <th style="width:150px;">Delete Eligible</th>
                            <th style="width:120px;">Status</th>
                            <th style="width:110px;">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($deleteFiles as $fPath): ?>
                        <?php
                            $base = basename($fPath);
                            $size = @filesize($fPath);
                            $info = quarantineEligibleInfo($fPath, $deleteRetentionDays);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="selected_files[]" value="<?php echo h($base); ?>"></td>
                            <td class="mono"><?php echo h($base); ?></td>
                            <td><?php echo h(date('Y-m-d H:i:s', $info['moved_ts'])); ?></td>
                            <td><?php echo h(date('Y-m-d H:i:s', $info['eligible_ts'])); ?></td>
                            <td class="<?php echo $info['eligible'] ? 'eligible' : 'waiting'; ?>">
                                <?php echo $info['eligible'] ? 'Eligible now' : h((string)$info['remaining_days']) . ' day(s)'; ?>
                            </td>
                            <td><?php echo h($size !== false ? number_format((int)$size) : ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <button class="btn btn-primary" type="submit" name="action" value="trash_restore_active_selected">Restore Checked to Active</button>
                    <button class="btn btn-danger" type="submit" name="action" value="permanent_delete_selected"
                            onclick="return confirm('PERMANENTLY DELETE the checked eligible files? This cannot be undone. Files younger than 30 days will be refused.');">Permanently Delete Checked Eligible Files</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

HTML;

    $next = replace_section($s, $restoreStart, $footerMarker, $managerUi, 'replace Restore card with three-area Backup Manager', $log);
    if ($next === null) return null;
    $s = $next;

    return $s;
}

$exists = is_file($target);
$current = $exists ? (string)file_get_contents($target) : '';
$currentBlob = $exists ? git_blob_sha1($current) : '';

$baselineOk =
    $exists
    && $currentBlob === BASE_GIT_BLOB_SHA1
    && strpos($current, 'v1.4  (2026-02-21)') !== false
    && strpos($current, '$backupDir = __DIR__ . DIRECTORY_SEPARATOR . \'db_backups\';') !== false
    && strpos($current, 'Restore Backup</div>') !== false;

$patchLog = [];
$replacement = $baselineOk ? build_v15($current, $patchLog) : null;

$replacementOk =
    is_string($replacement)
    && strpos($replacement, 'v1.5  (8/28/2026 11:21:21 pm)') !== false
    && strpos($replacement, '$archiveDir = $backupDir . DIRECTORY_SEPARATOR . \'Archive\';') !== false
    && strpos($replacement, '$deleteDir = $backupDir . DIRECTORY_SEPARATOR . \'To_Be_Deleted\';') !== false
    && strpos($replacement, '$deleteRetentionDays = 30;') !== false
    && strpos($replacement, 'function renameManagedSqlFile') !== false
    && strpos($replacement, 'function quarantineEligibleInfo') !== false
    && strpos($replacement, 'Archive Checked') !== false
    && strpos($replacement, 'Move Checked to To Be Deleted') !== false
    && strpos($replacement, 'Permanently Delete Checked Eligible Files') !== false
    && strpos($replacement, 'No backup files found yet.') === false;

$preflightOk = $baselineOk && $replacementOk;

$apply = isset($_POST['apply']) && $_POST['apply'] === '1';
$messages = [];
$success = false;

if ($apply && $preflightOk) {
    $backupDir = __DIR__ . '/_migration_backups/admin_db_backup_manager_' . date('Ymd_His');
    $ok = is_dir($backupDir) || mkdir($backupDir, 0755, true);

    if (!$ok) {
        $messages[] = 'FAIL: Could not create installer backup directory.';
    }

    if ($ok && !copy($target, $backupDir . '/admin_db_backup.php')) {
        $ok = false;
        $messages[] = 'FAIL: Could not back up admin_db_backup.php v1.4.';
    } elseif ($ok) {
        $messages[] = 'PASS: Backed up admin_db_backup.php v1.4.';
    }

    if ($ok && !atomic_write($target, (string)$replacement)) {
        $ok = false;
        $messages[] = 'FAIL: Could not install admin_db_backup.php v1.5.';
    } elseif ($ok) {
        $messages[] = 'PASS: Installed admin_db_backup.php v1.5.';
    }

    if ($ok) {
        $installed = (string)file_get_contents($target);
        $checks = [
            'v1.5 version history' => strpos($installed, 'v1.5  (8/28/2026 11:21:21 pm)') !== false,
            'Archive directory' => strpos($installed, "'Archive'") !== false,
            'To Be Deleted directory' => strpos($installed, "'To_Be_Deleted'") !== false,
            '30-day retention' => strpos($installed, '$deleteRetentionDays = 30;') !== false,
            '.sql rename preservation' => strpos($installed, "return \$requested . '.sql';") !== false,
            'overwrite refusal' => strpos($installed, 'destinationNameExists') !== false,
            'multi-file archive action' => strpos($installed, "value=\"archive_selected\"") !== false,
            'multi-file quarantine action' => strpos($installed, "value=\"trash_selected\"") !== false,
            'Archive direct restore' => strpos($installed, 'name="restore_area" value="archive"') !== false,
            'restore from quarantine to Active' => strpos($installed, "value=\"trash_restore_active_selected\"") !== false,
            'permanent-delete age gate' => strpos($installed, "if (!\$info['eligible'])") !== false,
            'no automatic purge loop' => strpos($installed, 'unlink($path)') !== false,
            'existing backup creator retained' => strpos($installed, "if (\$action === 'create_backup'") !== false,
            'existing restore parser retained' => strpos($installed, 'splitSqlStatements') !== false,
            'NO_AUTO_VALUE_ON_ZERO retained' => strpos($installed, 'NO_AUTO_VALUE_ON_ZERO') !== false,
        ];

        foreach ($checks as $label => $pass) {
            $messages[] = ($pass ? 'PASS: ' : 'FAIL: ') . $label;
            if (!$pass) $ok = false;
        }
    }

    if (!$ok && is_file($backupDir . '/admin_db_backup.php')) {
        if (copy($backupDir . '/admin_db_backup.php', $target)) {
            $messages[] = 'ROLLBACK: Restored admin_db_backup.php v1.4.';
        } else {
            $messages[] = 'ROLLBACK ERROR: Could not restore admin_db_backup.php v1.4.';
        }
    } else {
        $success = true;
    }
}
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Install Admin DB Backup Manager v1.5 - Installer v002</title>
<style>
*{box-sizing:border-box}html{background:#111}body{margin:0;color:#eee;font-family:Tahoma,Verdana,"Segoe UI",sans-serif}
.wrap{width:94%;max-width:1180px;margin:20px auto}.card{background:#202020;border:1px solid #555;border-radius:14px;padding:20px;margin-bottom:16px}
h1,h2{color:#efc982}table{width:100%;border-collapse:collapse}td{padding:9px;border-bottom:1px solid #444;vertical-align:top}
.ok{color:#61e493}.bad{color:#ff7777}button{padding:11px 20px;background:#1466c9;color:#fff;border:1px solid #5a7fb5;border-radius:9px;font-weight:800;cursor:pointer}
li{line-height:1.45;margin-bottom:5px}a,code{color:#76cfff}.small{color:#bbb;font-size:13px}
</style>
</head>
<body><div class="wrap">

<div class="card">
<h1>Admin DB Backup Manager v1.5</h1>
<p><strong>Installer v002:</strong> rebuilt against the exact production file you uploaded; CRLF line endings are normalized during patching.</p>
<p>Safer file management around the existing backup + restore engine.</p>
</div>

<div class="card"><h2>Preflight</h2><table>
<?php row('Exact production admin_db_backup.php v1.4 baseline', $baselineOk, $currentBlob); ?>
<?php row('v1.5 manager patch builds cleanly', $replacementOk, implode(' | ', $patchLog)); ?>
</table></div>

<?php if ($preflightOk): ?>
<div class="card"><h2>What changes</h2><ul>
<li><strong>Radio = single:</strong> Restore or Rename one backup.</li>
<li><strong>Checkbox = multiple:</strong> Archive, move, restore-to-Active, or delete multiple backups.</li>
<li>Adds <code>db_backups/Archive</code> and <code>db_backups/To_Be_Deleted</code> management.</li>
<li>Rename automatically keeps/appends <code>.sql</code>, validates names, and never overwrites another file.</li>
<li>Moving to To Be Deleted starts a fresh 30-day hold.</li>
<li>Nothing is automatically purged. Permanent Delete is a manual action and is refused until 30 days have elapsed.</li>
<li>Archive files can be restored directly into the database without moving them first.</li>
<li>The existing backup creator, restore SQL parser, DEFINER stripping, dry-run, FK handling, and NO_AUTO_VALUE_ON_ZERO behavior are preserved.</li>
</ul>
<p class="small">This installer changes only <code>admin_db_backup.php</code>. Existing .sql backups are not moved during installation.</p>
<?php if (!$apply): ?><form method="post"><input type="hidden" name="apply" value="1"><button>Install DB Backup Manager v1.5</button></form><?php endif; ?>
</div>
<?php endif; ?>

<?php if ($apply): ?>
<div class="card"><h2>Apply Result</h2>
<p class="<?php echo $success?'ok':'bad'; ?>"><strong><?php echo $success?'SUCCESS':'FAILED / ROLLED BACK'; ?></strong></p>
<ul><?php foreach($messages as $m): ?><li><?php echo ih($m); ?></li><?php endforeach; ?></ul>
<?php if ($success): ?><p><a href="/admin_db_backup.php" target="_blank">Open Admin DB Backup Manager v1.5</a></p><?php endif; ?>
</div>
<?php endif; ?>

</div></body></html>
