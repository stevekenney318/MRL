<?php
declare(strict_types=1);

/**
 * admin_paid_payouts_helper.php
 *
 * VERSION: v009
 * LAST MODIFIED: 11/18/2026 9:15 am ET
 *
 * PURPOSE:
 * - Helper/data layer for admin_paid_payouts.php.
 * - Preserves legacy Financial/user_teams payment data as an opening amount.
 * - Adds transaction-based payments, payouts, and year-to-year rollovers.
 * - Keeps payout winnings calculated from race_results/payouts_helper.php.
 *
 * PHP: 7.3 compatible.
 */

date_default_timezone_set('America/New_York');

function app_h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function app_money($v): string
{
    return '$'.number_format((float)$v, 2);
}

function app_money_value($v): float
{
    $s = str_replace(['$', ','], '', trim((string)$v));
    return is_numeric($s) ? round((float)$s, 2) : 0.0;
}

function app_years(PDO $dbo, array $payoutYears, string $raceYear): array
{
    $years = [];

    foreach ($payoutYears as $y) {
        if (preg_match('/^\d{4}$/', (string)$y)) {
            $years[(string)$y] = true;
        }
    }

    try {
        $q = $dbo->query("SELECT year FROM years WHERE year > 0 ORDER BY year DESC");
        foreach ($q as $r) {
            $y = (string)($r['year'] ?? '');
            if (preg_match('/^\d{4}$/', $y)) {
                $years[$y] = true;
            }
        }
    } catch (Throwable $e) {
    }

    foreach (['mrl_financial_fee_history', 'mrl_financial_team_year'] as $table) {
        try {
            $q = $dbo->query("SELECT DISTINCT raceYear FROM {$table} WHERE raceYear > 0");
            foreach ($q as $r) {
                $y = (string)($r['raceYear'] ?? '');
                if (preg_match('/^\d{4}$/', $y)) {
                    $years[$y] = true;
                }
            }
        } catch (Throwable $e) {
        }
    }

    try {
        $q = $dbo->query(
            "SELECT DISTINCT raceYear
             FROM mrl_financial_transactions
             WHERE raceYear > 0 AND voided_at IS NULL"
        );
        foreach ($q as $r) {
            $y = (string)($r['raceYear'] ?? '');
            if (preg_match('/^\d{4}$/', $y)) {
                $years[$y] = true;
            }
        }
    } catch (Throwable $e) {
    }

    if (preg_match('/^\d{4}$/', $raceYear)) {
        $years[$raceYear] = true;
    }

    if (empty($years)) {
        $years[date('Y')] = true;
    }

    $out = array_map('strval', array_keys($years));
    $out = array_values(array_unique($out));
    rsort($out, SORT_STRING);
    return $out;
}

function app_current_fee(PDO $dbo, string $year): array
{
    try {
        $s = $dbo->prepare(
            "SELECT * FROM mrl_financial_fee_history
             WHERE raceYear=:y
             ORDER BY effective_date DESC, id DESC
             LIMIT 1"
        );
        $s->execute([':y' => (int)$year]);
        $r = $s->fetch(PDO::FETCH_ASSOC);
        if (is_array($r)) {
            return $r;
        }
    } catch (Throwable $e) {
    }

    return [
        'annual_fee' => 0.00,
        'effective_date' => '',
        'reason' => '',
        'changed_by' => 0,
    ];
}

function app_fee_history(PDO $dbo, string $year): array
{
    try {
        $s = $dbo->prepare(
            "SELECT * FROM mrl_financial_fee_history
             WHERE raceYear=:y
             ORDER BY effective_date DESC, id DESC"
        );
        $s->execute([':y' => (int)$year]);
        $r = $s->fetchAll(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : [];
    } catch (Throwable $e) {
        return [];
    }
}

function app_add_fee(PDO $dbo, string $year, float $fee, string $date, string $reason, int $uid): bool
{
    $s = $dbo->prepare(
        "INSERT INTO mrl_financial_fee_history
         (raceYear, annual_fee, effective_date, reason, changed_by, created_at)
         VALUES (:y, :f, :d, :r, :u, NOW())"
    );

    return $s->execute([
        ':y' => (int)$year,
        ':f' => $fee,
        ':d' => $date,
        ':r' => $reason,
        ':u' => $uid,
    ]);
}

function app_team_rows(PDO $dbo, string $year): array
{
    $rows = [];

    if (function_exists('mrlp_year_roster')) {
        try {
            $r = mrlp_year_roster($year, $dbo);
            foreach ($r as $team => $x) {
                $uid = (int)($x['userID'] ?? 0);
                if ($uid <= 0 || in_array($uid, [998, 999], true)) {
                    continue;
                }
                $rows[$uid] = [
                    'userID' => $uid,
                    'teamName' => (string)($x['teamName'] ?? $team),
                    'userName' => (string)($x['userName'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
        }
    }

    try {
        $s = $dbo->prepare(
            "SELECT userID, teamName, COALESCE(userName,'') AS userName
             FROM Financial
             WHERE raceYear=:y AND userID NOT IN (0,998,999)"
        );
        $s->execute([':y' => (int)$year]);

        foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $x) {
            $uid = (int)($x['userID'] ?? 0);
            if ($uid > 0 && !isset($rows[$uid])) {
                $rows[$uid] = [
                    'userID' => $uid,
                    'teamName' => (string)($x['teamName'] ?? ''),
                    'userName' => (string)($x['userName'] ?? ''),
                ];
            }
        }
    } catch (Throwable $e) {
    }

    uasort($rows, function ($a, $b) {
        return strnatcasecmp((string)$a['teamName'], (string)$b['teamName']);
    });

    return array_values($rows);
}

function app_legacy_finance(PDO $dbo, string $year, int $uid): array
{
    try {
        $s = $dbo->prepare(
            "SELECT *
             FROM Financial
             WHERE raceYear=:y AND userID=:u
             LIMIT 1"
        );
        $s->execute([
            ':y' => (int)$year,
            ':u' => $uid,
        ]);
        $r = $s->fetch(PDO::FETCH_ASSOC);

        if (is_array($r)) {
            return [
                'amount_paid' => (float)($r['paidAmount'] ?? 0),
                'paid_status' => (string)($r['paidStatus'] ?? ''),
                'paid_method' => (string)($r['paidHow'] ?? ''),
                'paid_comment' => (string)($r['paidComment'] ?? ''),
            ];
        }
    } catch (Throwable $e) {
    }

    return [
        'amount_paid' => 0.0,
        'paid_status' => '',
        'paid_method' => '',
        'paid_comment' => '',
    ];
}

function app_load_team_summary(PDO $dbo, string $year, int $uid): array
{
    try {
        $s = $dbo->prepare(
            "SELECT *
             FROM mrl_financial_team_year
             WHERE raceYear=:y AND userID=:u
             LIMIT 1"
        );
        $s->execute([
            ':y' => (int)$year,
            ':u' => $uid,
        ]);
        $r = $s->fetch(PDO::FETCH_ASSOC);

        if (is_array($r)) {
            return [
                'manual_adjustment' => (float)($r['manual_adjustment'] ?? 0),
                'adjustment_reason' => (string)($r['adjustment_reason'] ?? ''),
                'notes' => (string)($r['notes'] ?? ''),
                'recipient_url' => (string)($r['recipient_url'] ?? ''),
                'payoff_notes' => (string)($r['payoff_notes'] ?? ''),
            ];
        }
    } catch (Throwable $e) {
    }

    return [
        'manual_adjustment' => 0.0,
        'adjustment_reason' => '',
        'notes' => '',
        'recipient_url' => '',
        'payoff_notes' => '',
    ];
}

function app_save_team_summary(PDO $dbo, array $d, int $adminUid): bool
{
    $sql =
        "INSERT INTO mrl_financial_team_year
         (raceYear, userID, teamName, userName, manual_adjustment, adjustment_reason,
          notes, recipient_url, payoff_notes, updated_by, created_at, updated_at)
         VALUES
         (:y, :u, :t, :n, :ma, :ar, :no, :ru, :pn, :ub, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            teamName=VALUES(teamName),
            userName=VALUES(userName),
            manual_adjustment=VALUES(manual_adjustment),
            adjustment_reason=VALUES(adjustment_reason),
            notes=VALUES(notes),
            recipient_url=VALUES(recipient_url),
            payoff_notes=VALUES(payoff_notes),
            updated_by=VALUES(updated_by),
            updated_at=NOW()";

    $s = $dbo->prepare($sql);

    return $s->execute([
        ':y' => (int)$d['raceYear'],
        ':u' => (int)$d['userID'],
        ':t' => (string)$d['teamName'],
        ':n' => (string)$d['userName'],
        ':ma' => (float)$d['manual_adjustment'],
        ':ar' => (string)$d['adjustment_reason'],
        ':no' => (string)$d['notes'],
        ':ru' => (string)$d['recipient_url'],
        ':pn' => (string)$d['payoff_notes'],
        ':ub' => $adminUid,
    ]);
}

function app_parse_datetime($value)
{
    $s = trim((string)$value);
    if ($s === '') {
        return null;
    }

    $formats = [
        'M j, Y, g:i A',
        'M d, Y, g:i A',
        'n/j/Y, g:i A',
        'n/j/Y g:i A',
        'm/d/Y, g:i A',
        'm/d/Y g:i A',
        'Y-m-d H:i:s',
        'Y-m-d\TH:i',
    ];

    foreach ($formats as $format) {
        $d = DateTime::createFromFormat('!'.$format, $s);
        if ($d instanceof DateTime) {
            return $d->format('Y-m-d H:i:s');
        }
    }

    $ts = strtotime($s);
    if ($ts !== false) {
        return date('Y-m-d H:i:s', $ts);
    }

    return null;
}

function app_format_datetime($value): string
{
    $s = trim((string)$value);
    if ($s === '') {
        return '';
    }

    $ts = strtotime($s);
    if ($ts === false) {
        return $s;
    }

    return date('M j, Y, g:i A', $ts);
}

function app_add_transaction(PDO $dbo, array $d, int $adminUid): int
{
    $sql =
        "INSERT INTO mrl_financial_transactions
         (raceYear, userID, teamName, transaction_type, amount, method, source_account,
          occurred_at, entered_date_text, note, linked_transaction_id, created_by, created_at)
         VALUES
         (:y, :u, :t, :ty, :a, :m, :s, :od, :dt, :no, :li, :cb, NOW())";

    $s = $dbo->prepare($sql);
    $s->execute([
        ':y' => (int)$d['raceYear'],
        ':u' => (int)$d['userID'],
        ':t' => (string)$d['teamName'],
        ':ty' => (string)$d['transaction_type'],
        ':a' => (float)$d['amount'],
        ':m' => (string)($d['method'] ?? ''),
        ':s' => (string)($d['source_account'] ?? ''),
        ':od' => $d['occurred_at'] ?? null,
        ':dt' => (string)($d['entered_date_text'] ?? ''),
        ':no' => (string)($d['note'] ?? ''),
        ':li' => $d['linked_transaction_id'] ?? null,
        ':cb' => $adminUid,
    ]);

    return (int)$dbo->lastInsertId();
}

function app_link_transactions(PDO $dbo, int $a, int $b): void
{
    $s = $dbo->prepare(
        "UPDATE mrl_financial_transactions
         SET linked_transaction_id=:b
         WHERE id=:a"
    );
    $s->execute([':a' => $a, ':b' => $b]);

    $s->execute([':a' => $b, ':b' => $a]);
}

function app_add_rollover_pair(
    PDO $dbo,
    string $fromYear,
    string $toYear,
    int $uid,
    string $teamName,
    float $amount,
    string $occurredAt,
    string $enteredDateText,
    string $note,
    int $adminUid
): array {
    $dbo->beginTransaction();

    try {
        $outId = app_add_transaction($dbo, [
            'raceYear' => $fromYear,
            'userID' => $uid,
            'teamName' => $teamName,
            'transaction_type' => 'rollover_out',
            'amount' => $amount,
            'method' => 'Rollover',
            'source_account' => '',
            'occurred_at' => $occurredAt,
            'entered_date_text' => $enteredDateText,
            'note' => $note,
            'linked_transaction_id' => null,
        ], $adminUid);

        $inId = app_add_transaction($dbo, [
            'raceYear' => $toYear,
            'userID' => $uid,
            'teamName' => $teamName,
            'transaction_type' => 'rollover_in',
            'amount' => $amount,
            'method' => 'Rollover',
            'source_account' => '',
            'occurred_at' => $occurredAt,
            'entered_date_text' => $enteredDateText,
            'note' => 'Rolled over from '.$fromYear.($note !== '' ? ' — '.$note : ''),
            'linked_transaction_id' => null,
        ], $adminUid);

        app_link_transactions($dbo, $outId, $inId);
        $dbo->commit();

        return [$outId, $inId];
    } catch (Throwable $e) {
        if ($dbo->inTransaction()) {
            $dbo->rollBack();
        }
        throw $e;
    }
}

function app_transactions(PDO $dbo, string $year, int $uid): array
{
    try {
        $s = $dbo->prepare(
            "SELECT *
             FROM mrl_financial_transactions
             WHERE raceYear=:y AND userID=:u
             ORDER BY COALESCE(occurred_at, created_at) DESC, id DESC"
        );
        $s->execute([
            ':y' => (int)$year,
            ':u' => $uid,
        ]);
        $r = $s->fetchAll(PDO::FETCH_ASSOC);
        return is_array($r) ? $r : [];
    } catch (Throwable $e) {
        return [];
    }
}

function app_transaction_totals(array $transactions): array
{
    $totals = [
        'payment' => 0.0,
        'payout' => 0.0,
        'rollover_in' => 0.0,
        'rollover_out' => 0.0,
    ];

    foreach ($transactions as $t) {
        if (!empty($t['voided_at'])) {
            continue;
        }

        $type = (string)($t['transaction_type'] ?? '');
        if (isset($totals[$type])) {
            $totals[$type] = round(
                $totals[$type] + (float)($t['amount'] ?? 0),
                2
            );
        }
    }

    return $totals;
}

function app_latest_method(array $transactions, string $type, string $fallback = ''): string
{
    foreach ($transactions as $t) {
        if (!empty($t['voided_at'])) {
            continue;
        }

        if ((string)($t['transaction_type'] ?? '') === $type) {
            $method = trim((string)($t['method'] ?? ''));
            if ($method !== '') {
                return $method;
            }
        }
    }

    return $fallback;
}


function app_latest_transaction_value(
    array $transactions,
    string $type,
    string $field,
    string $fallback = ''
): string {
    foreach ($transactions as $t) {
        if (!empty($t['voided_at'])) {
            continue;
        }

        if ((string)($t['transaction_type'] ?? '') !== $type) {
            continue;
        }

        $value = trim((string)($t[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return $fallback;
}

function app_get_transaction(PDO $dbo, int $id): array
{
    $s = $dbo->prepare(
        "SELECT *
         FROM mrl_financial_transactions
         WHERE id=:id
         LIMIT 1"
    );
    $s->execute([':id' => $id]);
    $r = $s->fetch(PDO::FETCH_ASSOC);

    return is_array($r) ? $r : [];
}

function app_transaction_pair_ids(PDO $dbo, int $id): array
{
    $t = app_get_transaction($dbo, $id);
    if (empty($t)) {
        return [];
    }

    $ids = [$id];
    $linked = (int)($t['linked_transaction_id'] ?? 0);

    if ($linked > 0 && $linked !== $id) {
        $ids[] = $linked;
    }

    return array_values(array_unique($ids));
}



function app_parse_datetime_or_now(string $text): array
{
    $text = trim($text);

    if ($text === '') {
        $now = new DateTime('now', new DateTimeZone('America/New_York'));

        return [
            'sql' => $now->format('Y-m-d H:i:s'),
            'display' => $now->format('M j, Y, g:i A'),
        ];
    }

    $parsed = app_parse_datetime($text);

    if ($parsed === null) {
        throw new RuntimeException(
            'Enter a valid date/time, for example: Apr 13, 2026, 12:54 PM.'
        );
    }

    return [
        'sql' => $parsed,
        'display' => $text,
    ];
}

function app_update_transaction(PDO $dbo, int $id, array $d, int $adminUid): void
{
    $txn = app_get_transaction($dbo, $id);

    if (empty($txn)) {
        throw new RuntimeException('Transaction not found.');
    }

    if (!empty($txn['voided_at'])) {
        throw new RuntimeException('Voided transactions cannot be edited.');
    }

    $type = (string)($txn['transaction_type'] ?? '');

    if ($type === 'rollover_out' || $type === 'rollover_in') {
        $linkedId = (int)($txn['linked_transaction_id'] ?? 0);

        if ($linkedId <= 0) {
            throw new RuntimeException('Linked rollover transaction not found.');
        }

        $linked = app_get_transaction($dbo, $linkedId);

        if (empty($linked)) {
            throw new RuntimeException('Linked rollover transaction not found.');
        }

        $amount = (float)$d['amount'];
        $occurredAt = (string)$d['occurred_at'];
        $enteredDateText = (string)$d['entered_date_text'];
        $note = (string)$d['note'];
        $toYear = (int)$d['to_year'];

        if ($type === 'rollover_out') {
            $out = $txn;
            $in = $linked;
        } else {
            $out = $linked;
            $in = $txn;
        }

        $fromYear = (int)$out['raceYear'];

        if ($toYear <= $fromYear) {
            throw new RuntimeException('Rollover year must be later than the source year.');
        }

        $dbo->beginTransaction();

        try {
            $s = $dbo->prepare(
                "UPDATE mrl_financial_transactions
                 SET amount=:a,
                     occurred_at=:od,
                     entered_date_text=:dt,
                     note=:no
                 WHERE id=:id"
            );

            $s->execute([
                ':a' => $amount,
                ':od' => $occurredAt,
                ':dt' => $enteredDateText,
                ':no' => $note,
                ':id' => (int)$out['id'],
            ]);

            $s = $dbo->prepare(
                "UPDATE mrl_financial_transactions
                 SET raceYear=:y,
                     amount=:a,
                     occurred_at=:od,
                     entered_date_text=:dt,
                     note=:no
                 WHERE id=:id"
            );

            $s->execute([
                ':y' => $toYear,
                ':a' => $amount,
                ':od' => $occurredAt,
                ':dt' => $enteredDateText,
                ':no' => 'Rolled over from '.$fromYear.($note !== '' ? ' — '.$note : ''),
                ':id' => (int)$in['id'],
            ]);

            $dbo->commit();
        } catch (Throwable $e) {
            if ($dbo->inTransaction()) {
                $dbo->rollBack();
            }
            throw $e;
        }

        return;
    }

    if (!in_array($type, ['payment', 'payout'], true)) {
        throw new RuntimeException('This transaction type cannot be edited here.');
    }

    $s = $dbo->prepare(
        "UPDATE mrl_financial_transactions
         SET amount=:a,
             method=:m,
             source_account=:s,
             occurred_at=:od,
             entered_date_text=:dt,
             note=:no
         WHERE id=:id"
    );

    $s->execute([
        ':a' => (float)$d['amount'],
        ':m' => (string)$d['method'],
        ':s' => (string)$d['source_account'],
        ':od' => (string)$d['occurred_at'],
        ':dt' => (string)$d['entered_date_text'],
        ':no' => (string)$d['note'],
        ':id' => $id,
    ]);
}

function app_void_transaction(PDO $dbo, int $id, int $adminUid, string $reason): int
{
    $reason = trim($reason);
    if ($reason === '') {
        throw new RuntimeException('Void reason is required.');
    }

    $ids = app_transaction_pair_ids($dbo, $id);
    if (empty($ids)) {
        throw new RuntimeException('Transaction not found.');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql =
        "UPDATE mrl_financial_transactions
         SET voided_at=NOW(), voided_by=?, void_reason=?
         WHERE id IN ({$placeholders}) AND voided_at IS NULL";

    $params = [$adminUid, $reason];
    foreach ($ids as $txnId) {
        $params[] = $txnId;
    }

    $s = $dbo->prepare($sql);
    $s->execute($params);

    return $s->rowCount();
}

function app_delete_transaction(PDO $dbo, int $id): int
{
    $ids = app_transaction_pair_ids($dbo, $id);
    if (empty($ids)) {
        throw new RuntimeException('Transaction not found.');
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $s = $dbo->prepare(
        "DELETE FROM mrl_financial_transactions
         WHERE id IN ({$placeholders})"
    );
    $s->execute($ids);

    return $s->rowCount();
}

function app_prizes_by_team(array $model): array
{
    $out = [];

    foreach (['S1', 'S2', 'S3', 'S4'] as $seg) {
        foreach (($model['sections'][$seg] ?? []) as $r) {
            if (!empty($r['future'])) {
                continue;
            }

            $team = trim((string)($r['winner'] ?? ''));
            if ($team === '') {
                continue;
            }

            if (!isset($out[$team])) {
                $out[$team] = 0.0;
            }

            $out[$team] += round((float)($r['amount'] ?? 0), 2);
        }
    }

    foreach (($model['yearly'] ?? []) as $r) {
        if (!empty($r['future'])) {
            continue;
        }

        $team = trim((string)($r['winner'] ?? ''));
        if ($team === '') {
            continue;
        }

        if (!isset($out[$team])) {
            $out[$team] = 0.0;
        }

        $out[$team] += round((float)($r['amount'] ?? 0), 2);
    }

    return $out;
}

function app_calc(
    float $fee,
    float $cashPaid,
    float $rolloverIn,
    float $prize,
    float $adjustment,
    float $paidOut,
    float $rolloverOut
): array {
    $effectivePaid = round($cashPaid + $rolloverIn, 2);
    $signedFeeBalance = round($fee - $effectivePaid, 2);

    $remaining = round(
        $prize - $signedFeeBalance + $adjustment - $paidOut - $rolloverOut,
        2
    );

    if ($fee <= 0) {
        $status = 'No Fee';
    } elseif ($effectivePaid + 0.005 >= $fee) {
        $status = 'Paid';
    } elseif ($effectivePaid > 0) {
        $status = 'Partial';
    } else {
        $status = 'Unpaid';
    }

    return [
        'effective_paid' => $effectivePaid,
        'signed_fee_balance' => $signedFeeBalance,
        'fee_due' => max(0.0, $signedFeeBalance),
        'fee_credit' => max(0.0, -$signedFeeBalance),
        'remaining' => $remaining,
        'status' => $status,
    ];
}
