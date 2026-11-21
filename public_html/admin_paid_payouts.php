<?php
declare(strict_types=1);

/**
 * admin_paid_payouts.php
 *
 * VERSION: v012
 * LAST MODIFIED: 11/20/2026 9:30 pm ET
 *
 * PURPOSE:
 * - Compact admin financial dashboard with IN / EARNED / OUT / BALANCE groups.
 * - Whole team row is clickable; multiple team editors may remain open.
 * - Transaction Edit/Void/Delete controls are available for transaction rows.
 * - Expanded editors stay width-neutral and reflow vertically as needed.
 * - Supports multiple payment transactions, payout transactions, and rollover credits.
 * - Preserves legacy Financial/user_teams payment data as an opening amount.
 * - Prize winnings remain calculated/read-only from race_results/payouts_helper.php.
 * - POST actions use Redirect-after-POST so browser refresh cannot replay transactions.
 * - Expanded editor favors the left side: Payments above Payout/Rollover, Team Notes on the right.
 * - Balance shortcut fills payout/payment amount; dirty-form warning only checks other teams.
 * - Rollover supports both positive credits and negative debts carried into a later year.
 * - Long payout notes wrap without hiding Edit/Void/Delete; focused fields use stronger contrast.
 *
 * PHP: 7.3 compatible.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/New_York');

function app_page_fatal(Throwable $e): void
{
    http_response_code(500);
    echo '<!doctype html><html><head><meta charset="UTF-8"><title>MRL Paid + Payouts Admin - Error</title>';
    echo '<style>body{background:#11161c;color:#eee;font-family:Tahoma;padding:24px}.box{max-width:1000px;margin:auto;background:#1b232c;border:1px solid #8f424b;border-radius:10px;padding:18px}code{color:#ffbec4;word-break:break-word}</style>';
    echo '</head><body><div class="box"><h1>MRL Paid + Payouts Admin</h1>';
    echo '<p>The page hit a runtime error.</p>';
    echo '<p><strong>Message:</strong> <code>'.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</code></p>';
    echo '<p><strong>File:</strong> <code>'.htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8').'</code></p>';
    echo '<p><strong>Line:</strong> <code>'.(int)$e->getLine().'</code></p>';
    echo '</div></body></html>';
}

try {
    $_SESSION['return_to'] = $_SERVER['REQUEST_URI'] ?? '/admin_paid_payouts.php';

    require_once $_SERVER['DOCUMENT_ROOT'].'/config.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/config_mrl.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/class.user.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/mrl_team/mrl_theme_helper.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/race_results/payouts_helper.php';
    require_once $_SERVER['DOCUMENT_ROOT'].'/admin_paid_payouts_helper.php';

    $user_home = new USER();

    if (!$user_home->is_logged_in()) {
        $user_home->redirect('/login.php');
        exit;
    }

    $uid = (int)($_SESSION['userSession'] ?? 0);

    if (!isAdmin($uid)) {
        http_response_code(403);
        exit('Not authorized.');
    }

    if (!isset($_SESSION['app_csrf'])) {
        $_SESSION['app_csrf'] = bin2hex(random_bytes(24));
    }

    $flashMessage = (string)($_SESSION['app_flash_message'] ?? '');
    $flashError = (string)($_SESSION['app_flash_error'] ?? '');
    unset($_SESSION['app_flash_message'], $_SESSION['app_flash_error']);

    $payoutYears = mrlp_available_years($_SERVER['DOCUMENT_ROOT'].'/race_results');
    $years = array_map('strval', app_years($dbo, $payoutYears, (string)($raceYear ?? '')));

    $defaultYear = (string)($raceYear ?? '');
    if (!preg_match('/^\d{4}$/', $defaultYear) || !in_array($defaultYear, $years, true)) {
        $defaultYear = (string)($years[0] ?? date('Y'));
    }

    $year = preg_replace(
        '/[^0-9]/',
        '',
        (string)($_POST['year'] ?? $_GET['year'] ?? $defaultYear)
    );

    if (!in_array($year, $years, true)) {
        $year = $defaultYear;
    }

    $msg = $flashMessage;
    $err = $flashError;

    $currentFeeRow = app_current_fee($dbo, $year);
    $currentFee = (float)($currentFeeRow['annual_fee'] ?? 0);

    $score = mrlp_compute_year($year, $dbo, $dbconnect);
    $yearCfg = mrlp_year_config(mrlp_load_config(), $year);
    $payoutModel = mrlp_build_payout_model($score, $yearCfg);
    $prizes = app_prizes_by_team($payoutModel);
    $teams = app_team_rows($dbo, $year);

    $buildRows = function () use ($dbo, $year, $currentFee, $prizes, $teams) {
        $rows = [];
        $summary = [
            'expected' => 0.0,
            'received' => 0.0,
            'prizes' => 0.0,
            'due_league' => 0.0,
            'adjustments' => 0.0,
            'paid_out' => 0.0,
            'rollover_out' => 0.0,
            'remaining' => 0.0,
        ];

        foreach ($teams as $team) {
            $teamUid = (int)$team['userID'];
            $legacy = app_legacy_finance($dbo, $year, $teamUid);
            $finance = app_load_team_summary($dbo, $year, $teamUid);
            $transactions = app_transactions($dbo, $year, $teamUid);
            $totals = app_transaction_totals($transactions);

            $cashPaid = round(
                (float)$legacy['amount_paid'] + (float)$totals['payment'],
                2
            );

            $prize = (float)($prizes[$team['teamName']] ?? 0);

            $calc = app_calc(
                $currentFee,
                $cashPaid,
                (float)$totals['rollover_in'],
                $prize,
                (float)$finance['manual_adjustment'],
                (float)$totals['payout'],
                (float)$totals['rollover_out']
            );

            $latestMethod = app_latest_method(
                $transactions,
                'payment',
                (string)$legacy['paid_method']
            );

            $latestPayoutMethod = app_latest_transaction_value(
                $transactions,
                'payout',
                'method',
                ''
            );

            $latestPayoutSource = app_latest_transaction_value(
                $transactions,
                'payout',
                'source_account',
                ''
            );

            $row = [
                'team' => $team,
                'legacy' => $legacy,
                'finance' => $finance,
                'transactions' => $transactions,
                'totals' => $totals,
                'cash_paid' => $cashPaid,
                'prize' => $prize,
                'calc' => $calc,
                'latest_method' => $latestMethod,
                'latest_payout_method' => $latestPayoutMethod,
                'latest_payout_source' => $latestPayoutSource,
            ];

            $rows[] = $row;

            $summary['expected'] += $currentFee;
            $summary['received'] += $cashPaid;
            $summary['prizes'] += $prize;
            $summary['due_league'] += (float)$calc['fee_due'];
            $summary['adjustments'] += (float)$finance['manual_adjustment'];
            $summary['paid_out'] += (float)$totals['payout'];
            $summary['rollover_out'] += (float)$totals['rollover_out'];

            if ((float)$calc['remaining'] > 0) {
                $summary['remaining'] += (float)$calc['remaining'];
            }
        }

        return [$rows, $summary];
    };

    list($rows, $summary) = $buildRows();

    $rowByUid = [];
    foreach ($rows as $r) {
        $rowByUid[(int)$r['team']['userID']] = $r;
    }

    $postRedirect = function (string $redirectYear, string $message = '', string $error = ''): void {
        if ($message !== '') {
            $_SESSION['app_flash_message'] = $message;
        }

        if ($error !== '') {
            $_SESSION['app_flash_error'] = $error;
        }

        header(
            'Location: /admin_paid_payouts.php?year='.rawurlencode($redirectYear),
            true,
            303
        );
        exit;
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals((string)$_SESSION['app_csrf'], (string)($_POST['csrf'] ?? ''))) {
            $postRedirect($year, '', 'Security token mismatch.');
        } else {
            $action = (string)($_POST['action'] ?? '');

            try {
                if ($action === 'save_fee') {
                    $fee = max(0, app_money_value($_POST['annual_fee'] ?? 0));
                    $date = trim((string)($_POST['effective_date'] ?? ''));
                    $reason = trim((string)($_POST['fee_reason'] ?? ''));

                    $d = DateTime::createFromFormat('Y-m-d', $date);

                    if (!$d || $d->format('Y-m-d') !== $date) {
                        throw new RuntimeException('Effective date is required.');
                    }

                    if ($reason === '') {
                        throw new RuntimeException('A reason is required for every annual-fee change.');
                    }

                    app_add_fee($dbo, $year, $fee, $date, $reason, $uid);
                    $msg = 'Annual fee saved for '.$year.'.';
                }

                if (in_array($action, ['save_summary', 'add_payment', 'add_payout', 'add_rollover', 'edit_transaction', 'void_transaction', 'delete_transaction'], true)) {
                    $teamUid = (int)($_POST['userID'] ?? 0);
                    $teamName = trim((string)($_POST['teamName'] ?? ''));
                    $userName = trim((string)($_POST['userName'] ?? ''));

                    if ($teamUid <= 0 || $teamName === '' || !isset($rowByUid[$teamUid])) {
                        throw new RuntimeException('Invalid team row.');
                    }

                    if ($action === 'save_summary') {
                        $adjustment = app_money_value($_POST['manual_adjustment'] ?? 0);
                        $adjustmentReason = trim((string)($_POST['adjustment_reason'] ?? ''));

                        if (abs($adjustment) > 0.004 && $adjustmentReason === '') {
                            throw new RuntimeException('Adjustment reason is required when Manual Adjustment is not $0.00.');
                        }

                        app_save_team_summary($dbo, [
                            'raceYear' => $year,
                            'userID' => $teamUid,
                            'teamName' => $teamName,
                            'userName' => $userName,
                            'manual_adjustment' => $adjustment,
                            'adjustment_reason' => $adjustmentReason,
                            'notes' => trim((string)($_POST['notes'] ?? '')),
                            'recipient_url' => trim((string)($_POST['recipient_url'] ?? '')),
                            'payoff_notes' => trim((string)($_POST['payoff_notes'] ?? '')),
                        ], $uid);

                        $msg = 'Saved '.$teamName.' notes/adjustment.';
                    }

                    if ($action === 'add_payment' || $action === 'add_payout') {
                        $amount = app_money_value($_POST['txn_amount'] ?? 0);
                        $dateText = trim((string)($_POST['txn_datetime'] ?? ''));
                        $dateInfo = app_parse_datetime_or_now($dateText);
                        $occurredAt = $dateInfo['sql'];
                        $dateText = $dateInfo['display'];

                        if ($amount <= 0) {
                            throw new RuntimeException('Transaction amount must be greater than $0.00.');
                        }

                        app_add_transaction($dbo, [
                            'raceYear' => $year,
                            'userID' => $teamUid,
                            'teamName' => $teamName,
                            'transaction_type' => $action === 'add_payment' ? 'payment' : 'payout',
                            'amount' => $amount,
                            'method' => trim((string)($_POST['txn_method'] ?? '')),
                            'source_account' => trim((string)($_POST['txn_source'] ?? '')),
                            'occurred_at' => $occurredAt,
                            'entered_date_text' => $dateText,
                            'note' => trim((string)($_POST['txn_note'] ?? '')),
                            'linked_transaction_id' => null,
                        ], $uid);

                        $msg = ($action === 'add_payment' ? 'Payment' : 'Payout').' added for '.$teamName.'.';
                    }

                    if ($action === 'add_rollover') {
                        $amount = app_money_value($_POST['rollover_amount'] ?? 0);
                        $toYear = preg_replace('/[^0-9]/', '', (string)($_POST['rollover_year'] ?? ((int)$year + 1)));
                        $dateText = trim((string)($_POST['rollover_datetime'] ?? ''));
                        $dateInfo = app_parse_datetime_or_now($dateText);
                        $occurredAt = $dateInfo['sql'];
                        $dateText = $dateInfo['display'];
                        $available = (float)$rowByUid[$teamUid]['calc']['remaining'];

                        if (abs($amount) < 0.005) {
                            throw new RuntimeException('Rollover - Credit/Debt amount cannot be $0.00.');
                        }

                        if (abs($available) < 0.005) {
                            throw new RuntimeException('There is no balance available to roll over.');
                        }

                        if (($amount > 0 && $available < 0) || ($amount < 0 && $available > 0)) {
                            throw new RuntimeException('Rollover - Credit/Debt must have the same sign as the current balance.');
                        }

                        if (abs($amount) - abs($available) > 0.005) {
                            throw new RuntimeException('Rollover - Credit/Debt cannot exceed the current balance.');
                        }

                        if (!preg_match('/^\d{4}$/', $toYear) || (int)$toYear <= (int)$year) {
                            throw new RuntimeException('Rollover year must be later than the current year.');
                        }

                        app_add_rollover_pair(
                            $dbo,
                            $year,
                            $toYear,
                            $teamUid,
                            $teamName,
                            $amount,
                            $occurredAt,
                            $dateText,
                            trim((string)($_POST['rollover_note'] ?? '')),
                            $uid
                        );

                        $msg = app_money($amount).' rolled from '.$year.' to '.$toYear.' for '.$teamName.'.';
                    }

                    if ($action === 'edit_transaction') {
                        $txnId = (int)($_POST['transaction_id'] ?? 0);
                        $txn = app_get_transaction($dbo, $txnId);

                        if (empty($txn)) {
                            throw new RuntimeException('Transaction not found.');
                        }

                        if ((int)$txn['userID'] !== $teamUid) {
                            throw new RuntimeException('Transaction does not belong to this team.');
                        }

                        $amount = app_money_value($_POST['edit_amount'] ?? 0);
                        $dateText = trim((string)($_POST['edit_datetime'] ?? ''));
                        $occurredAt = app_parse_datetime($dateText);

                        if ($occurredAt === null) {
                            throw new RuntimeException('Enter a valid date/time.');
                        }

                        $type = (string)($txn['transaction_type'] ?? '');

                        if ($type === 'rollover_out' || $type === 'rollover_in') {
                            if (abs($amount) < 0.005) {
                                throw new RuntimeException('Rollover - Credit/Debt amount cannot be $0.00.');
                            }

                            $toYear = preg_replace('/[^0-9]/', '', (string)($_POST['edit_to_year'] ?? ''));
                            $currentAmount = (float)($txn['amount'] ?? 0);
                            $available = round(
                                (float)$rowByUid[$teamUid]['calc']['remaining'] + $currentAmount,
                                2
                            );

                            if (abs($available) < 0.005) {
                                throw new RuntimeException('There is no balance available to roll over.');
                            }

                            if (($amount > 0 && $available < 0) || ($amount < 0 && $available > 0)) {
                                throw new RuntimeException('Rollover - Credit/Debt must have the same sign as the available balance.');
                            }

                            if (abs($amount) - abs($available) > 0.005) {
                                throw new RuntimeException('Rollover - Credit/Debt cannot exceed the available balance.');
                            }

                            if (!preg_match('/^\d{4}$/', $toYear)) {
                                throw new RuntimeException('Enter a valid rollover year.');
                            }

                            app_update_transaction($dbo, $txnId, [
                                'amount' => $amount,
                                'method' => 'Rollover',
                                'source_account' => '',
                                'occurred_at' => $occurredAt,
                                'entered_date_text' => $dateText,
                                'note' => trim((string)($_POST['edit_note'] ?? '')),
                                'to_year' => (int)$toYear,
                            ], $uid);
                        } else {
                            if ($amount <= 0) {
                                throw new RuntimeException('Transaction amount must be greater than $0.00.');
                            }

                            app_update_transaction($dbo, $txnId, [
                                'amount' => $amount,
                                'method' => trim((string)($_POST['edit_method'] ?? '')),
                                'source_account' => trim((string)($_POST['edit_source'] ?? '')),
                                'occurred_at' => $occurredAt,
                                'entered_date_text' => $dateText,
                                'note' => trim((string)($_POST['edit_note'] ?? '')),
                                'to_year' => 0,
                            ], $uid);
                        }

                        $msg = 'Transaction updated for '.$teamName.'.';
                    }

                    if ($action === 'void_transaction' || $action === 'delete_transaction') {
                        $txnId = (int)($_POST['transaction_id'] ?? 0);
                        $txn = app_get_transaction($dbo, $txnId);

                        if (empty($txn)) {
                            throw new RuntimeException('Transaction not found.');
                        }

                        if ((int)$txn['userID'] !== $teamUid) {
                            throw new RuntimeException('Transaction does not belong to this team.');
                        }

                        if ($action === 'void_transaction') {
                            $reason = trim((string)($_POST['void_reason'] ?? ''));
                            app_void_transaction($dbo, $txnId, $uid, $reason);
                            $msg = 'Transaction voided for '.$teamName.'.';
                        } else {
                            app_delete_transaction($dbo, $txnId);
                            $msg = 'Transaction permanently deleted for '.$teamName.'.';
                        }
                    }
                }
            } catch (Throwable $e) {
                $postRedirect($year, '', $e->getMessage());
            }

            $postRedirect($year, $msg, '');
        }
    }

    $feeHistory = app_fee_history($dbo, $year);

    $owedRows = $rows;
    usort($owedRows, function ($a, $b) {
        $cmp = (float)$b['calc']['remaining'] <=> (float)$a['calc']['remaining'];
        if ($cmp !== 0) {
            return $cmp;
        }
        return strnatcasecmp((string)$a['team']['teamName'], (string)$b['team']['teamName']);
    });

    $yearIndex = array_search($year, $years, true);
    $newerYear = null;
    $olderYear = null;

    if ($yearIndex !== false) {
        if ($yearIndex > 0) {
            $newerYear = (string)$years[$yearIndex - 1];
        }
        if ($yearIndex < count($years) - 1) {
            $olderYear = (string)$years[$yearIndex + 1];
        }
    }

    $liveYear = (string)($raceYear ?? $year);
    if (!preg_match('/^\d{4}$/', $liveYear)) {
        $liveYear = $year;
    }

?>
<!doctype html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRL Paid + Payouts Admin</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#11161c;color:#e9edf2;font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:14px}
.wrap{width:98%;max-width:1720px;margin:14px auto 32px}
.card{background:#1b232c;border:1px solid #394655;border-radius:10px;padding:12px 16px;margin-bottom:10px}
.header{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
h1{margin:0;color:#fff2dc;font-size:26px}
h2{margin:0 0 8px;color:#ffd08a;font-size:19px}
.sub,.muted{color:#9eacba}
.toolbar{display:flex;gap:7px;align-items:center;flex-wrap:wrap}
input,textarea,select{background:#f4f4f4;color:#111;border:1px solid #777;border-radius:5px;padding:5px 7px;font:13px Tahoma,Verdana,sans-serif}
textarea{resize:vertical}
.btn{border:1px solid #53697f;border-radius:6px;padding:6px 10px;text-decoration:none;cursor:pointer;font:bold 13px Tahoma,Verdana,sans-serif}
.save{background:#173927;color:#7de5aa;border-color:#2c7550}
.blue{background:#173653;color:#a7cfff;border-color:#3977b0}
.warnbtn{background:#4b3417;color:#ffd08a;border-color:#8a6328}
.notice{padding:9px 11px;border-radius:7px;margin-bottom:9px}
.ok{background:#173927;color:#7de5aa;border:1px solid #2c7550}
.err{background:#452226;color:#ff9ca5;border:1px solid #8f424b}

.summary{display:grid;grid-template-columns:repeat(8,minmax(130px,1fr));gap:7px}
.stat{background:#151c24;border:1px solid #33414f;border-radius:8px;padding:8px 10px}
.stat .k{color:#99a8b7;font-size:11px}
.stat .v{font-size:18px;font-weight:bold;margin-top:2px}

.feeform{display:grid;grid-template-columns:165px 190px minmax(280px,1fr) auto;gap:7px;align-items:end}
.history{width:100%;border-collapse:collapse;margin-top:9px}
.history th,.history td{border-bottom:1px solid #33414f;padding:6px 8px;text-align:left}

.tablewrap{overflow-x:auto}
.finance-table{width:100%;min-width:1280px;border-collapse:collapse;table-layout:auto}
.finance-table th{background:#18212a;color:#ffd7a0;font-size:12px;white-space:nowrap}
.finance-table th,.finance-table td{padding:6px 7px;border-bottom:1px solid #34414e;text-align:left}
.finance-table td.num,.finance-table th.num{text-align:right;white-space:nowrap}
.finance-table .team-col{width:150px;max-width:180px}
.finance-table .owner-col{width:125px;max-width:150px}
.finance-table .status-col{width:70px}
.finance-table .money-col{width:82px}
.finance-table .method-col{width:82px}
.finance-table .source-col{width:88px}
.finance-table .group-head{text-align:center;background:#121a22;color:#9ecaff;border-left:1px solid #3c4a58;border-right:1px solid #3c4a58}
.finance-table .group-in{color:#79e6a5}
.finance-table .group-earned{color:#ffd08a}
.finance-table .group-out{color:#a7cfff}
.finance-table .group-balance{color:#f0c2ff}
.year-nav{display:flex;align-items:center;gap:5px}
.year-nav .navbtn{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:30px;padding:0 8px}
.year-nav .disabled{opacity:.28;pointer-events:none}
.txn-action{display:flex;gap:4px;justify-content:flex-end;white-space:nowrap;flex-wrap:wrap}
.edit-btn{background:#173653;color:#a7cfff;border:1px solid #3977b0}
.txn-edit-row{display:none;background:#10171e}
.txn-edit-row.open{display:table-row}
.txn-edit-wrap{padding:7px 3px}
.txn-edit-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:6px;align-items:end}
.txn-edit-actions{display:flex;gap:6px;align-items:end;flex-wrap:wrap}
.cancel-btn{background:#2b3138;color:#d7dde3;border:1px solid #59636d}
.reading-space-control{display:flex;align-items:center;gap:8px;justify-content:center;margin:10px 0 0}
.reading-space{height:0;transition:height .15s ease}
.reading-space.open{height:38vh;min-height:220px}
.mini-btn{border-radius:5px;padding:3px 6px;font:bold 11px Tahoma;cursor:pointer}
.void-btn{background:#4b3417;color:#ffd08a;border:1px solid #8a6328}
.delete-btn{background:#4a1f25;color:#ffadb5;border:1px solid #8e3b47}
.txn-voided{opacity:.55;text-decoration:line-through}
.void-reason{font-size:10px;color:#d0a56b;text-decoration:none!important;display:block;margin-top:2px}
.year-end-table{width:100%;border-collapse:collapse}
.year-end-table th,.year-end-table td{padding:6px 8px;border-bottom:1px solid #33414f;text-align:left}
.year-end-table td.num,.year-end-table th.num{text-align:right;white-space:nowrap}
.disposition{font-weight:bold}
.finance-row{cursor:pointer}
.finance-row:hover{background:#202c37}
.finance-row.open{background:#1e2b36}
.finance-row .teamlink{color:#dcecff;font-weight:bold}
.status-paid{color:#78e4a2;font-weight:bold}
.status-partial{color:#ffd17e;font-weight:bold}
.status-unpaid{color:#ff9ba4;font-weight:bold}
.money-positive{color:#78e4a2;font-weight:bold}
.money-negative{color:#ff9ba4;font-weight:bold}
.money-neutral{color:#e9edf2}

.detail-row{display:none}
.detail-row.open{display:table-row}
.detail-cell{padding:0!important;background:#10171e;min-width:0;max-width:0;overflow:hidden}
.team-editor{padding:10px 12px 12px;border-left:4px solid #355b78;min-width:0;max-width:100%;width:100%;box-sizing:border-box;overflow:hidden}
.team-editor.dirty{border-left-color:#e3a640;background:#171b1f}
.editor-title{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:8px}
.editor-title strong{font-size:16px;color:#fff2dc}
.dirty-note{display:none;color:#ffd17e;font-weight:bold}
.team-editor.dirty .dirty-note{display:inline}
.editor-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(0,.92fr);gap:10px;align-items:start}
.editor-stack{display:flex;flex-direction:column;gap:10px;min-width:0}
.editor-box{background:#151d25;border:1px solid #33414f;border-radius:8px;padding:9px;min-width:0;overflow:hidden}
.editor-balance{margin-left:10px;font-size:16px;white-space:nowrap}
.balance-fill{background:transparent;border:0;padding:0;font:inherit;font-weight:bold;cursor:pointer}
.balance-fill:hover{text-decoration:underline}
.balance-fill.money-neutral{cursor:default;text-decoration:none}
.editor-box h3{margin:0 0 7px;color:#bdd6ed;font-size:14px}
.form-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px;min-width:0}
.field label{display:block;color:#aeb9c5;font-size:11px;margin-bottom:2px}
.field{min-width:0}.field input,.field textarea,.field select{width:100%;max-width:100%;min-width:0;box-sizing:border-box}
.field input:focus,
.field textarea:focus,
.field select:focus{
    outline:3px solid #78e4a2!important;
    outline-offset:1px!important;
    border-color:#1f6fb2!important;
    background:#fff6d8!important;
    color:#111!important;
}
.field textarea{min-height:48px}
.span2{grid-column:span 2}
.span3{grid-column:span 3}
.span4{grid-column:span 4}
.txn-list{width:100%;border-collapse:collapse;margin-bottom:7px}
.txn-list th,.txn-list td{padding:4px 5px;border-bottom:1px solid #2e3944;font-size:12px;text-align:left}
.txn-list th.num,.txn-list td.num{text-align:right}
.payout-txn-list{table-layout:fixed}
.payout-txn-list th:nth-child(1),.payout-txn-list td:nth-child(1){width:58px}
.payout-txn-list th:nth-child(2),.payout-txn-list td:nth-child(2){width:132px}
.payout-txn-list th:nth-child(3),.payout-txn-list td:nth-child(3){width:72px}
.payout-txn-list th:nth-child(4),.payout-txn-list td:nth-child(4){width:78px}
.payout-txn-list th:nth-child(5),.payout-txn-list td:nth-child(5){width:88px}
.payout-txn-list th:nth-child(6),.payout-txn-list td:nth-child(6){white-space:normal;overflow-wrap:anywhere;word-break:break-word}
.payout-txn-list th:nth-child(7),.payout-txn-list td:nth-child(7){width:145px}
.payout-txn-list .txn-action{justify-content:flex-end}
.balance-choice{display:none;gap:6px;align-items:center;margin:6px 0 8px;padding:7px 8px;border:1px solid #7a4b51;border-radius:6px;background:#25191c}
.balance-choice.open{display:flex;flex-wrap:wrap}
.balance-choice-label{color:#ffb0b7;font-weight:bold;font-size:12px}
.choice-collect{background:#173927;color:#78e4a2;border:1px solid #2c7550}
.choice-rollover{background:#4b3417;color:#ffd08a;border:1px solid #8a6328}
.legacy{background:#202018;color:#e8d79a}
.rollover{background:#182a22}
.small-note{font-size:11px;color:#94a3b1;margin-top:4px}

.owed-table{width:100%;border-collapse:collapse}
.owed-table th,.owed-table td{padding:6px 8px;border-bottom:1px solid #33414f;text-align:left}
.owed-table td.num,.owed-table th.num{text-align:right}

@media(max-width:1250px){
  .editor-grid{grid-template-columns:1fr}
}
@media(max-width:1100px){
  .summary{grid-template-columns:repeat(4,1fr)}
  .feeform{grid-template-columns:1fr 1fr}
  .txn-edit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>
</head>
<body>
<div class="wrap">

<div class="card header">
    <div>
        <h1>MRL Paid + Payouts Admin</h1>
        <div class="sub">Annual fees, payments, adjustments, payoff tracking, rollover credit, and calculated prize winnings.</div>
    </div>
    <div class="toolbar">
        <div class="year-nav">
            <a class="btn blue navbtn <?php echo $olderYear === null ? 'disabled' : ''; ?>" href="<?php echo $olderYear !== null ? '/admin_paid_payouts.php?year='.app_h($olderYear) : '#'; ?>" title="Previous year">&#9664;</a>

            <form method="get">
                <select name="year" onchange="this.form.submit()">
                    <?php foreach ($years as $y): ?>
                    <option value="<?php echo app_h($y); ?>" <?php echo $y === $year ? 'selected' : ''; ?>>
                        <?php echo app_h($y); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a class="btn blue navbtn <?php echo $newerYear === null ? 'disabled' : ''; ?>" href="<?php echo $newerYear !== null ? '/admin_paid_payouts.php?year='.app_h($newerYear) : '#'; ?>" title="Next year">&#9654;</a>
            <a class="btn save navbtn" href="/admin_paid_payouts.php?year=<?php echo app_h($liveYear); ?>">Live</a>
        </div>

        <a class="btn blue" target="_blank" href="/race_results/payouts.php?year=<?php echo app_h($year); ?>&preview=1">Open Payouts</a>
    </div>
</div>

<?php if ($msg !== ''): ?><div class="notice ok"><?php echo app_h($msg); ?></div><?php endif; ?>
<?php if ($err !== ''): ?><div class="notice err"><?php echo app_h($err); ?></div><?php endif; ?>

<div class="card">
    <h2><?php echo app_h($year); ?> Year Summary</h2>
    <div class="summary">
        <?php
        $stats = [
            'Annual Fees Expected' => $summary['expected'],
            'Fees Received' => $summary['received'],
            'Prize Money Awarded' => $summary['prizes'],
            'Due to League' => $summary['due_league'],
            'Manual Adjustments' => $summary['adjustments'],
            'Payouts Already Paid' => $summary['paid_out'],
            'Rolled Forward' => $summary['rollover_out'],
            'Still Owed to Members' => $summary['remaining'],
        ];
        foreach ($stats as $label => $value):
        ?>
        <div class="stat">
            <div class="k"><?php echo app_h($label); ?></div>
            <div class="v"><?php echo app_money($value); ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <h2>Annual Fee / Donation</h2>
    <div class="muted">The latest fee applies to every competitive team for the selected year. Earlier fee entries remain in history.</div>

    <form method="post" class="feeform" style="margin-top:8px">
        <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
        <input type="hidden" name="action" value="save_fee">
        <input type="hidden" name="year" value="<?php echo app_h($year); ?>">

        <label>Annual Fee<br><input name="annual_fee" value="<?php echo number_format($currentFee, 2, '.', ''); ?>"></label>
        <label>Effective Date<br><input type="date" name="effective_date" value="<?php echo app_h(date('Y-m-d')); ?>"></label>
        <label>Reason<br><input style="width:100%" name="fee_reason" placeholder="Required — why the fee changed"></label>
        <button class="btn save" type="submit">Save Fee Change</button>
    </form>

    <table class="history">
        <tr><th>Effective</th><th>Fee</th><th>Reason</th><th>Changed By</th><th>Recorded</th></tr>
        <?php if (empty($feeHistory)): ?>
        <tr><td colspan="5" class="muted">No fee history yet.</td></tr>
        <?php else: ?>
            <?php foreach ($feeHistory as $fh): ?>
            <tr>
                <td><?php echo app_h($fh['effective_date'] ?? ''); ?></td>
                <td><?php echo app_money($fh['annual_fee'] ?? 0); ?></td>
                <td><?php echo app_h($fh['reason'] ?? ''); ?></td>
                <td><?php echo app_h($fh['changed_by'] ?? ''); ?></td>
                <td><?php echo app_h($fh['created_at'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>
</div>

<div class="card">
    <h2>Team Payment + Payout Status</h2>
    <div class="muted" style="margin-bottom:8px">
        Click anywhere on a team row to open its editor. Multiple teams can remain open.
        Net owed = Prize Winnings − signed fee balance ± adjustment − payouts − rollover.
    </div>

    <div class="tablewrap">
        <table class="finance-table">
            <thead>
            <tr>
                <th class="team-col" rowspan="2">Team</th>
                <th class="owner-col" rowspan="2">Owner</th>
                <th class="status-col" rowspan="2">Status</th>
                <th class="group-head group-in" colspan="3">IN</th>
                <th class="group-head group-earned" colspan="2">EARNED</th>
                <th class="group-head group-out" colspan="4">OUT</th>
                <th class="group-head group-balance" colspan="1">BALANCE</th>
            </tr>
            <tr>
                <th class="num money-col">Paid</th>
                <th class="method-col">How</th>
                <th class="num money-col">Fee +/-</th>
                <th class="num money-col">Prize</th>
                <th class="num money-col">Adj.</th>
                <th class="num money-col">Paid</th>
                <th class="method-col">How</th>
                <th class="source-col">From</th>
                <th class="num money-col">Rollover</th>
                <th class="num money-col">I Owe</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row):
                $team = $row['team'];
                $finance = $row['finance'];
                $legacy = $row['legacy'];
                $totals = $row['totals'];
                $calc = $row['calc'];
                $teamUid = (int)$team['userID'];

                $feeText = $calc['fee_credit'] > 0
                    ? '+'.app_money($calc['fee_credit']).' credit'
                    : app_money($calc['fee_due']);

                $statusClass = $calc['status'] === 'Paid'
                    ? 'status-paid'
                    : ($calc['status'] === 'Partial' ? 'status-partial' : 'status-unpaid');
            ?>
            <tr class="finance-row" data-team="<?php echo $teamUid; ?>" tabindex="0">
                <td class="teamlink team-col"><?php echo app_h($team['teamName']); ?></td>
                <td class="owner-col"><?php echo app_h($team['userName']); ?></td>
                <td class="status-col <?php echo $statusClass; ?>"><?php echo app_h($calc['status']); ?></td>
                <td class="num money-col"><?php echo app_money($row['cash_paid']); ?></td>
                <td class="method-col"><?php echo app_h($row['latest_method']); ?></td>
                <td class="num money-col <?php echo $calc['fee_credit'] > 0 ? 'money-positive' : ($calc['fee_due'] > 0 ? 'money-negative' : 'money-neutral'); ?>">
                    <?php echo app_h($feeText); ?>
                </td>
                <td class="num money-col"><?php echo app_money($row['prize']); ?></td>
                <td class="num money-col"><?php echo app_money($finance['manual_adjustment']); ?></td>
                <td class="num money-col"><?php echo app_money($totals['payout']); ?></td>
                <td class="method-col"><?php echo app_h($row['latest_payout_method']); ?></td>
                <td class="source-col"><?php echo app_h($row['latest_payout_source']); ?></td>
                <td class="num money-col <?php echo (float)$totals['rollover_out'] > 0.004 ? 'money-positive' : ((float)$totals['rollover_out'] < -0.004 ? 'money-negative' : 'money-neutral'); ?>"><?php echo app_money($totals['rollover_out']); ?></td>
                <td class="num money-col <?php echo $calc['remaining'] > 0 ? 'money-positive' : ($calc['remaining'] < 0 ? 'money-negative' : 'money-neutral'); ?>">
                    <?php echo app_money($calc['remaining']); ?>
                </td>
            </tr>

            <tr class="detail-row" data-detail="<?php echo $teamUid; ?>">
                <td colspan="13" class="detail-cell">
                    <div class="team-editor" data-editor="<?php echo $teamUid; ?>">
                        <div class="editor-title">
                            <div>
                                <strong><?php echo app_h($team['teamName']); ?></strong>
                                <span class="muted"> — <?php echo app_h($team['userName']); ?></span>
                            </div>
                            <span class="dirty-note">Unsaved Changes</span>
                        </div>

                        <div class="editor-grid">

                            <div class="editor-stack">

                            <div class="editor-box">
                                <h3>Payments Received</h3>
                                <table class="txn-list payout-txn-list">
                                    <tr><th>Date / Time</th><th class="num">Amount</th><th>Method</th><th>Note</th><th></th></tr>
                                    <?php if ((float)$legacy['amount_paid'] > 0): ?>
                                    <tr class="legacy">
                                        <td><?php echo app_h($legacy['paid_comment']); ?></td>
                                        <td class="num"><?php echo app_money($legacy['amount_paid']); ?></td>
                                        <td><?php echo app_h($legacy['paid_method']); ?></td>
                                        <td>Legacy / opening payment</td>
                                        <td></td>
                                    </tr>
                                    <?php endif; ?>

                                    <?php
                                    $paymentCount = 0;
                                    foreach ($row['transactions'] as $t):
                                        if ((string)$t['transaction_type'] !== 'payment') {
                                            continue;
                                        }
                                        $paymentCount++;
                                    ?>
                                    <tr class="<?php echo !empty($t['voided_at']) ? 'txn-voided' : ''; ?>">
                                        <td>
                                            <?php echo app_h(app_format_datetime($t['occurred_at'])); ?>
                                            <?php if (!empty($t['voided_at'])): ?><span class="void-reason">VOID: <?php echo app_h($t['void_reason']); ?></span><?php endif; ?>
                                        </td>
                                        <td class="num"><?php echo app_money($t['amount']); ?></td>
                                        <td><?php echo app_h($t['method']); ?></td>
                                        <td><?php echo app_h($t['note']); ?></td>
                                        <td>
                                            <?php if (empty($t['voided_at'])): ?>
                                            <div class="txn-action">
                                                <button type="button" class="mini-btn edit-btn" data-edit-target="txn-edit-<?php echo (int)$t['id']; ?>">Edit</button>
                                                <form method="post" class="txn-action-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="void_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">
                                                    <input type="hidden" name="void_reason" value="">
                                                    <button type="submit" class="mini-btn void-btn" data-label="<?php echo app_h(app_money($t['amount']).' payment'); ?>">Void</button>
                                                </form>
                                                <form method="post" class="txn-action-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="delete_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">
                                                    <button type="submit" class="mini-btn delete-btn" data-label="<?php echo app_h(app_money($t['amount']).' payment'); ?>">Delete</button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (empty($t['voided_at'])): ?>
                                    <tr class="txn-edit-row" id="txn-edit-<?php echo (int)$t['id']; ?>">
                                        <td colspan="5">
                                            <div class="txn-edit-wrap">
                                                <form method="post" class="edit-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="edit_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">

                                                    <div class="txn-edit-grid">
                                                        <div class="field"><label>Amount</label><input name="edit_amount" value="<?php echo number_format((float)$t['amount'], 2, '.', ''); ?>"></div>
                                                        <div class="field"><label>Method</label><input name="edit_method" value="<?php echo app_h($t['method']); ?>"></div>
                                                        <div class="field span2"><label>Date &amp; Time</label><input name="edit_datetime" class="date-text" value="<?php echo app_h(app_format_datetime($t['occurred_at'])); ?>"></div>
                                                        <div class="field span2"><label>Note</label><input name="edit_note" value="<?php echo app_h($t['note']); ?>"></div>
                                                        <div class="field"><label>Date/Time Picker</label><input type="datetime-local" class="date-picker"></div>
                                                        <div class="txn-edit-actions">
                                                            <button class="btn save" type="submit">Save Edit</button>
                                                            <button class="mini-btn cancel-btn" type="button" data-edit-cancel="txn-edit-<?php echo (int)$t['id']; ?>">Cancel</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($paymentCount === 0 && (float)$legacy['amount_paid'] <= 0): ?>
                                    <tr><td colspan="5" class="muted">No payments recorded.</td></tr>
                                    <?php endif; ?>
                                </table>

                                <form method="post" class="edit-form">
                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                    <input type="hidden" name="action" value="add_payment">
                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">

                                    <div class="form-grid">
                                        <div class="field"><label>Amount</label><input name="txn_amount" class="payment-amount-input" placeholder="0.00"></div>
                                        <div class="field"><label>Method</label><input name="txn_method" placeholder="Venmo, PayPal, Cash..."></div>
                                        <div class="field span2"><label>Date &amp; Time — blank = now; paste email style or use picker</label><input name="txn_datetime" class="date-text" placeholder="Apr 13, 2026, 12:54 PM"></div>
                                        <div class="field"><label>Date/Time Picker</label><input type="datetime-local" class="date-picker"></div>
                                        <div class="field span2"><label>Note</label><input name="txn_note"></div>
                                        <div class="field" style="align-self:end"><button class="btn save" type="submit">Add Payment</button></div>
                                    </div>
                                </form>
                            </div>

                            <div class="editor-box">
                                <h3>
                                    Payout / Rollover
                                    <button
                                        type="button"
                                        class="editor-balance balance-fill <?php echo $calc['remaining'] > 0 ? 'money-positive' : ($calc['remaining'] < 0 ? 'money-negative' : 'money-neutral'); ?>"
                                        data-balance="<?php echo app_h(number_format((float)$calc['remaining'], 2, '.', '')); ?>"
                                        title="<?php echo $calc['remaining'] > 0 ? 'Fill Payout Amount' : ($calc['remaining'] < 0 ? 'Fill Payment Amount' : 'No balance to fill'); ?>"
                                    >
                                        Balance <?php echo app_money($calc['remaining']); ?>
                                    </button>
                                </h3>

                                <?php if ((float)$calc['remaining'] < -0.004): ?>
                                <div class="balance-choice" data-balance-choice>
                                    <span class="balance-choice-label">Negative balance:</span>
                                    <button type="button" class="mini-btn choice-collect">Collect Now</button>
                                    <button type="button" class="mini-btn choice-rollover">Rollover - Credit/Debt</button>
                                </div>
                                <?php endif; ?>

                                <table class="txn-list payout-txn-list">
                                    <tr><th>Type</th><th>Date / Time</th><th class="num">Amount</th><th>Method</th><th>Paid From</th><th>Note</th><th></th></tr>
                                    <?php
                                    $outCount = 0;
                                    foreach ($row['transactions'] as $t):
                                        $type = (string)$t['transaction_type'];
                                        if (!in_array($type, ['payout', 'rollover_out', 'rollover_in'], true)) {
                                            continue;
                                        }
                                        $outCount++;
                                    ?>
                                    <tr class="<?php echo (strpos($type, 'rollover') === 0 ? 'rollover ' : '').(!empty($t['voided_at']) ? 'txn-voided' : ''); ?>">
                                        <td>
                                            <?php echo app_h(ucwords(str_replace('_', ' ', $type))); ?>
                                            <?php if (!empty($t['voided_at'])): ?><span class="void-reason">VOID: <?php echo app_h($t['void_reason']); ?></span><?php endif; ?>
                                        </td>
                                        <td><?php echo app_h(app_format_datetime($t['occurred_at'])); ?></td>
                                        <td class="num"><?php echo app_money($t['amount']); ?></td>
                                        <td><?php echo app_h($t['method']); ?></td>
                                        <td><?php echo app_h($t['source_account']); ?></td>
                                        <td><?php echo app_h($t['note']); ?></td>
                                        <td>
                                            <?php if (empty($t['voided_at'])): ?>
                                            <div class="txn-action">
                                                <button type="button" class="mini-btn edit-btn" data-edit-target="txn-edit-<?php echo (int)$t['id']; ?>">Edit</button>
                                                <form method="post" class="txn-action-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="void_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">
                                                    <input type="hidden" name="void_reason" value="">
                                                    <button type="submit" class="mini-btn void-btn" data-label="<?php echo app_h(app_money($t['amount']).' '.str_replace('_', ' ', $type)); ?>">Void</button>
                                                </form>
                                                <form method="post" class="txn-action-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="delete_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">
                                                    <button type="submit" class="mini-btn delete-btn" data-label="<?php echo app_h(app_money($t['amount']).' '.str_replace('_', ' ', $type)); ?>">Delete</button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (empty($t['voided_at'])):
                                        $isRollover = strpos($type, 'rollover') === 0;
                                        $editToYear = '';
                                        if ($isRollover) {
                                            if ($type === 'rollover_out') {
                                                $linkedTxn = app_get_transaction($dbo, (int)($t['linked_transaction_id'] ?? 0));
                                                $editToYear = (string)($linkedTxn['raceYear'] ?? ((int)$year + 1));
                                            } else {
                                                $editToYear = (string)$t['raceYear'];
                                            }
                                        }
                                    ?>
                                    <tr class="txn-edit-row" id="txn-edit-<?php echo (int)$t['id']; ?>">
                                        <td colspan="7">
                                            <div class="txn-edit-wrap">
                                                <form method="post" class="edit-form">
                                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                                    <input type="hidden" name="action" value="edit_transaction">
                                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">
                                                    <input type="hidden" name="transaction_id" value="<?php echo (int)$t['id']; ?>">

                                                    <div class="txn-edit-grid">
                                                        <div class="field"><label>Amount</label><input name="edit_amount" value="<?php echo number_format((float)$t['amount'], 2, '.', ''); ?>"></div>

                                                        <?php if ($isRollover): ?>
                                                        <input type="hidden" name="edit_method" value="Rollover">
                                                        <input type="hidden" name="edit_source" value="">
                                                        <div class="field"><label>To Year</label><input name="edit_to_year" value="<?php echo app_h($editToYear); ?>"></div>
                                                        <?php else: ?>
                                                        <div class="field"><label>Method</label><input name="edit_method" value="<?php echo app_h($t['method']); ?>"></div>
                                                        <div class="field"><label>Paid From</label><input name="edit_source" value="<?php echo app_h($t['source_account']); ?>"></div>
                                                        <?php endif; ?>

                                                        <div class="field span2"><label>Date &amp; Time</label><input name="edit_datetime" class="date-text" value="<?php echo app_h(app_format_datetime($t['occurred_at'])); ?>"></div>
                                                        <div class="field span2"><label>Note</label><input name="edit_note" value="<?php echo app_h($t['note']); ?>"></div>
                                                        <div class="field"><label>Date/Time Picker</label><input type="datetime-local" class="date-picker"></div>
                                                        <div class="txn-edit-actions">
                                                            <button class="btn save" type="submit">Save Edit</button>
                                                            <button class="mini-btn cancel-btn" type="button" data-edit-cancel="txn-edit-<?php echo (int)$t['id']; ?>">Cancel</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if ($outCount === 0): ?>
                                    <tr><td colspan="7" class="muted">No payout or rollover transactions recorded.</td></tr>
                                    <?php endif; ?>
                                </table>

                                <form method="post" class="edit-form">
                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                    <input type="hidden" name="action" value="add_payout">
                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">

                                    <div class="form-grid">
                                        <div class="field"><label>Payout Amount</label><input name="txn_amount" class="payout-amount-input" placeholder="0.00"></div>
                                        <div class="field"><label>Method</label><input name="txn_method" placeholder="Venmo, PayPal, Cash..."></div>
                                        <div class="field"><label>Paid From</label><input name="txn_source" placeholder="M&T, KeyBank, Pocket..."></div>
                                        <div class="field"><label>Date &amp; Time — blank = now</label><input name="txn_datetime" class="date-text" placeholder="Apr 13, 2026, 12:54 PM"></div>
                                        <div class="field"><label>Date/Time Picker</label><input type="datetime-local" class="date-picker"></div>
                                        <div class="field span2"><label>Note</label><input name="txn_note"></div>
                                        <div class="field" style="align-self:end"><button class="btn save" type="submit">Record Payout</button></div>
                                    </div>
                                </form>

                                <form method="post" class="edit-form" style="margin-top:9px">
                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                    <input type="hidden" name="action" value="add_rollover">
                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">

                                    <div class="form-grid">
                                        <div class="field"><label>Rollover - Credit/Debt</label><input name="rollover_amount" class="rollover-amount-input" value="<?php echo abs((float)$calc['remaining']) > 0.004 ? number_format((float)$calc['remaining'], 2, '.', '') : ''; ?>"></div>
                                        <div class="field"><label>To Year</label><input name="rollover_year" value="<?php echo (int)$year + 1; ?>"></div>
                                        <div class="field span2"><label>Date &amp; Time — blank = now</label><input name="rollover_datetime" class="date-text" placeholder="Nov 16, 2026, 4:23 PM"></div>
                                        <div class="field"><label>Date/Time Picker</label><input type="datetime-local" class="date-picker"></div>
                                        <div class="field span2"><label>Note</label><input name="rollover_note" placeholder="Credit/debt carried into next year"></div>
                                        <div class="field" style="align-self:end"><button class="btn warnbtn" type="submit">Rollover - Credit/Debt</button></div>
                                    </div>
                                </form>
                            </div>

                            </div>

                            <div class="editor-box">
                                <h3>Adjustment / Team Notes</h3>

                                <form method="post" class="edit-form">
                                    <input type="hidden" name="csrf" value="<?php echo app_h($_SESSION['app_csrf']); ?>">
                                    <input type="hidden" name="action" value="save_summary">
                                    <input type="hidden" name="year" value="<?php echo app_h($year); ?>">
                                    <input type="hidden" name="userID" value="<?php echo $teamUid; ?>">
                                    <input type="hidden" name="teamName" value="<?php echo app_h($team['teamName']); ?>">
                                    <input type="hidden" name="userName" value="<?php echo app_h($team['userName']); ?>">

                                    <div class="form-grid">
                                        <div class="field"><label>Manual Adjustment</label><input name="manual_adjustment" value="<?php echo number_format((float)$finance['manual_adjustment'], 2, '.', ''); ?>"></div>
                                        <div class="field span3"><label>Adjustment Reason</label><input name="adjustment_reason" value="<?php echo app_h($finance['adjustment_reason']); ?>"></div>
                                        <div class="field span4"><label>General Notes</label><textarea name="notes"><?php echo app_h($finance['notes']); ?></textarea></div>
                                        <div class="field span2"><label>Recipient / Payment URL</label><input name="recipient_url" value="<?php echo app_h($finance['recipient_url']); ?>"></div>
                                        <div class="field span2"><label>Payoff Notes</label><textarea name="payoff_notes"><?php echo app_h($finance['payoff_notes']); ?></textarea></div>
                                        <div class="field span4"><button class="btn save" type="submit">Save Team Data</button></div>
                                    </div>
                                </form>

                                <div class="small-note">
                                    Legacy payment data above remains read-only here for now. New payments are added as separate transactions.
                                </div>
                            </div>

                        </div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2>Year-End Status</h2>
    <div class="muted" style="margin-bottom:8px">Simplified final financial status for every team in the selected season.</div>

    <table class="year-end-table">
        <tr>
            <th>Team</th>
            <th>Owner</th>
            <th>Status</th>
            <th class="num">Final Balance</th>
            <th>Disposition</th>
        </tr>

        <?php foreach ($owedRows as $row):
            $balance = (float)$row['calc']['remaining'];
            $rollover = (float)$row['totals']['rollover_out'];
            $paidOut = (float)$row['totals']['payout'];

            if ($balance > 0.004) {
                $finalStatus = 'Owed';
                $balanceClass = 'money-positive';
                $disposition = 'Pay';
            } elseif ($balance < -0.004) {
                $finalStatus = 'Owes League';
                $balanceClass = 'money-negative';
                $disposition = 'Due';
            } else {
                $finalStatus = 'Settled';
                $balanceClass = 'money-neutral';

                if (abs($rollover) > 0.004 && $paidOut > 0.004) {
                    $disposition = $rollover > 0
                        ? 'Paid + Rollover Credit '.app_money($rollover)
                        : 'Paid + Rollover Debt '.app_money(abs($rollover));
                } elseif ($rollover > 0.004) {
                    $disposition = 'Rollover Credit '.app_money($rollover);
                } elseif ($rollover < -0.004) {
                    $disposition = 'Rollover Debt '.app_money(abs($rollover));
                } elseif ($paidOut > 0.004) {
                    $disposition = 'Paid '.app_money($paidOut);
                } else {
                    $disposition = 'No Balance';
                }
            }
        ?>
        <tr>
            <td><?php echo app_h($row['team']['teamName']); ?></td>
            <td><?php echo app_h($row['team']['userName']); ?></td>
            <td class="disposition <?php echo $balanceClass; ?>"><?php echo app_h($finalStatus); ?></td>
            <td class="num <?php echo $balanceClass; ?>"><?php echo app_money($balance); ?></td>
            <td><?php echo app_h($disposition); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="reading-space-control">
        <button type="button" class="btn blue" id="reading-space-toggle">Expand Bottom Reading Space</button>
    </div>
</div>

<div class="reading-space" id="reading-space" aria-hidden="true"></div>

</div>

<script>
(function () {
    'use strict';

    var storageKey = 'mrlPaidPayoutsOpen_<?php echo app_h($year); ?>';

    function getOpenIds() {
        var ids = [];
        document.querySelectorAll('.detail-row.open').forEach(function (row) {
            ids.push(row.getAttribute('data-detail'));
        });
        return ids;
    }

    function saveOpenIds() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(getOpenIds()));
        } catch (e) {
        }
    }

    function editorFor(id) {
        return document.querySelector('[data-editor="' + id + '"]');
    }

    function isEditorDirty(id) {
        var editor = editorFor(id);
        return editor && editor.classList.contains('dirty');
    }

    function setOpen(id, open) {
        var row = document.querySelector('.finance-row[data-team="' + id + '"]');
        var detail = document.querySelector('.detail-row[data-detail="' + id + '"]');

        if (!row || !detail) {
            return;
        }

        row.classList.toggle('open', open);
        detail.classList.toggle('open', open);
        saveOpenIds();
    }

    document.querySelectorAll('.finance-row').forEach(function (row) {
        var id = row.getAttribute('data-team');

        function toggle() {
            var detail = document.querySelector('.detail-row[data-detail="' + id + '"]');
            var isOpen = detail && detail.classList.contains('open');

            if (isOpen && isEditorDirty(id)) {
                if (!window.confirm('This team has unsaved changes. Close without saving?')) {
                    return;
                }
                var editor = editorFor(id);
                if (editor) {
                    editor.classList.remove('dirty');
                    editor.querySelectorAll('form').forEach(function (form) {
                        form.setAttribute('data-dirty', '0');
                    });
                }
            }

            setOpen(id, !isOpen);
        }

        row.addEventListener('click', toggle);
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggle();
            }
        });
    });

    document.querySelectorAll('.edit-form').forEach(function (form) {
        form.setAttribute('data-dirty', '0');

        form.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(function (el) {
            el.addEventListener('input', function () {
                form.setAttribute('data-dirty', '1');
                var editor = form.closest('.team-editor');
                if (editor) {
                    editor.classList.add('dirty');
                }
            });
        });

        form.addEventListener('submit', function (e) {
            var currentEditor = form.closest('.team-editor');
            var otherDirty = false;

            document.querySelectorAll('.team-editor.dirty').forEach(function (editor) {
                if (editor !== currentEditor) {
                    otherDirty = true;
                }
            });

            if (otherDirty && !window.confirm('Another open team has unsaved changes. Continue and lose those unsaved changes?')) {
                e.preventDefault();
                return;
            }

            form.setAttribute('data-dirty', '0');
            if (currentEditor) {
                currentEditor.classList.remove('dirty');
            }
            saveOpenIds();
        });
    });

    document.querySelectorAll('.balance-fill').forEach(function (button) {
        button.addEventListener('click', function () {
            var editor = button.closest('.team-editor');

            if (!editor) {
                return;
            }

            var balance = parseFloat(button.getAttribute('data-balance') || '0');

            if (!isFinite(balance) || Math.abs(balance) < 0.005) {
                return;
            }

            if (balance > 0) {
                var payoutTarget = editor.querySelector('.payout-amount-input');

                if (!payoutTarget) {
                    return;
                }

                payoutTarget.value = balance.toFixed(2);
                payoutTarget.dispatchEvent(new Event('input', { bubbles: true }));
                payoutTarget.focus();
                payoutTarget.select();
                return;
            }

            var choice = editor.querySelector('[data-balance-choice]');
            if (choice) {
                choice.classList.toggle('open');
            }
        });
    });

    document.querySelectorAll('.choice-collect').forEach(function (button) {
        button.addEventListener('click', function () {
            var editor = button.closest('.team-editor');
            var balanceButton = editor ? editor.querySelector('.balance-fill') : null;
            var target = editor ? editor.querySelector('.payment-amount-input') : null;

            if (!editor || !balanceButton || !target) {
                return;
            }

            var balance = parseFloat(balanceButton.getAttribute('data-balance') || '0');

            if (!isFinite(balance) || balance >= -0.004) {
                return;
            }

            target.value = Math.abs(balance).toFixed(2);
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.focus();
            target.select();

            var choice = editor.querySelector('[data-balance-choice]');
            if (choice) {
                choice.classList.remove('open');
            }
        });
    });

    document.querySelectorAll('.choice-rollover').forEach(function (button) {
        button.addEventListener('click', function () {
            var editor = button.closest('.team-editor');
            var balanceButton = editor ? editor.querySelector('.balance-fill') : null;
            var target = editor ? editor.querySelector('.rollover-amount-input') : null;

            if (!editor || !balanceButton || !target) {
                return;
            }

            var balance = parseFloat(balanceButton.getAttribute('data-balance') || '0');

            if (!isFinite(balance) || balance >= -0.004) {
                return;
            }

            target.value = balance.toFixed(2);
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.focus();
            target.select();

            var choice = editor.querySelector('[data-balance-choice]');
            if (choice) {
                choice.classList.remove('open');
            }
        });
    });

    document.querySelectorAll('.edit-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.getAttribute('data-edit-target');
            var row = id ? document.getElementById(id) : null;

            if (!row) {
                return;
            }

            document.querySelectorAll('.txn-edit-row.open').forEach(function (other) {
                if (other !== row) {
                    other.classList.remove('open');
                }
            });

            row.classList.toggle('open');
        });
    });

    document.querySelectorAll('[data-edit-cancel]').forEach(function (button) {
        button.addEventListener('click', function () {
            var id = button.getAttribute('data-edit-cancel');
            var row = id ? document.getElementById(id) : null;

            if (!row) {
                return;
            }

            var form = row.querySelector('form');

            if (form && form.getAttribute('data-dirty') === '1') {
                if (!window.confirm('Discard these unsaved transaction edits?')) {
                    return;
                }
                form.reset();
                form.setAttribute('data-dirty', '0');
            }

            row.classList.remove('open');

            var editor = button.closest('.team-editor');
            if (editor && !editor.querySelector('.edit-form[data-dirty="1"]')) {
                editor.classList.remove('dirty');
            }
        });
    });

    var readingSpace = document.getElementById('reading-space');
    var readingToggle = document.getElementById('reading-space-toggle');
    var readingKey = 'mrlPaidPayoutsReadingSpace';

    function setReadingSpace(open) {
        if (!readingSpace || !readingToggle) {
            return;
        }

        readingSpace.classList.toggle('open', open);
        readingSpace.setAttribute('aria-hidden', open ? 'false' : 'true');
        readingToggle.textContent = open ? 'Collapse Bottom Reading Space' : 'Expand Bottom Reading Space';

        try {
            localStorage.setItem(readingKey, open ? '1' : '0');
        } catch (e) {
        }
    }

    if (readingToggle) {
        readingToggle.addEventListener('click', function () {
            setReadingSpace(!readingSpace.classList.contains('open'));
        });
    }

    try {
        setReadingSpace(localStorage.getItem(readingKey) === '1');
    } catch (e) {
        setReadingSpace(false);
    }

    document.querySelectorAll('.void-btn').forEach(function (button) {
        button.addEventListener('click', function (e) {
            var label = button.getAttribute('data-label') || 'transaction';
            var reason = window.prompt('Void ' + label + '?\n\nEnter the reason for voiding it:');

            if (reason === null) {
                e.preventDefault();
                return;
            }

            reason = reason.trim();

            if (!reason) {
                window.alert('A void reason is required.');
                e.preventDefault();
                return;
            }

            var form = button.closest('form');
            var reasonInput = form ? form.querySelector('input[name="void_reason"]') : null;

            if (reasonInput) {
                reasonInput.value = reason;
            }
        });
    });

    document.querySelectorAll('.delete-btn').forEach(function (button) {
        button.addEventListener('click', function (e) {
            var label = button.getAttribute('data-label') || 'transaction';

            if (!window.confirm(
                'Permanently delete ' + label + '?\n\n' +
                'This cannot be undone. If this is a rollover, both the OUT and IN transactions will be deleted.'
            )) {
                e.preventDefault();
            }
        });
    });

    document.querySelectorAll('.date-picker').forEach(function (picker) {
        picker.addEventListener('change', function () {
            if (!picker.value) {
                return;
            }

            var d = new Date(picker.value);

            if (isNaN(d.getTime())) {
                return;
            }

            var text = d.toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });

            var form = picker.closest('form');
            var textInput = form ? form.querySelector('.date-text') : null;

            if (textInput) {
                textInput.value = text;
                textInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    });

    window.addEventListener('beforeunload', function (e) {
        var dirty = document.querySelector('.edit-form[data-dirty="1"]');

        if (dirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    try {
        var saved = JSON.parse(sessionStorage.getItem(storageKey) || '[]');

        if (Array.isArray(saved)) {
            saved.forEach(function (id) {
                setOpen(String(id), true);
            });
        }
    } catch (e) {
    }
})();
</script>

</body>
</html>
<?php
} catch (Throwable $e) {
    app_page_fatal($e);
}
