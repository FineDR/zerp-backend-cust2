<?php

// Allows you to view all bank transactions for a selected date range
require(__DIR__ . '/includes/session.php');

$Title = __('Bank Transactions Inquiry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'DailyBankTransactions';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromTransDate'])){$_POST['FromTransDate'] = ConvertSQLDate($_POST['FromTransDate']);}
if (isset($_POST['ToTransDate'])){$_POST['ToTransDate'] = ConvertSQLDate($_POST['ToTransDate']);}

if (isset($_GET['BankAccount'])) {
	$_POST['BankAccount'] = $_GET['BankAccount'];
	$_POST['ShowType'] = 'All';
	$_POST['Show'] = true;
}
if (isset($_GET['FromTransDate'])) $_POST['FromTransDate'] = $_GET['FromTransDate'];
if (isset($_GET['ToTransDate'])) $_POST['ToTransDate'] = $_GET['ToTransDate'];

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --radius-lg: 12px;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1550px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.25rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; border: none; transition: 0.2s; text-decoration:none; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.6rem; border-bottom: 2px solid var(--db-border); position: sticky; top: 0; z-index: 10; }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .tr-total { background: #f1f5f9; font-weight: 800; }
    
    .db-badge { padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Banking</div>
    <h1 class="db-page-title">' . $Title . '</h1>
</header>';

if (!isset($_POST['Show'])) {
	echo '<div class="db-card" style="max-width: 600px;">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Inquiry Filters') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-field">
                    <label class="db-label">' . __('Bank Account') . '</label>
                    <select name="BankAccount" class="db-select" autofocus>';
                    $SQL = "SELECT bankaccounts.bankaccountname, bankaccounts.accountcode, bankaccounts.currcode FROM bankaccounts INNER JOIN chartmaster ON bankaccounts.accountcode=chartmaster.accountcode INNER JOIN bankaccountusers ON bankaccounts.accountcode=bankaccountusers.accountcode WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "' ORDER BY bankaccounts.bankaccountname";
                    $Res = DB_query($SQL);
                    while ($MyRow = DB_fetch_array($Res)) {
                        $sel = ((isset($_POST['BankAccount']) and $_POST['BankAccount'] == $MyRow['accountcode']) ? ' selected="selected"' : '');
                        echo '<option ' . $sel . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['bankaccountname'] . ' - ' . $MyRow['currcode'] . '</option>';
                    }
    echo '          </select></div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">From Date</label><input name="FromTransDate" type="date" class="db-input" value="' . date('Y-m-d') . '" required /></div>
                    <div class="db-field"><label class="db-label">To Date</label><input name="ToTransDate" type="date" class="db-input" value="' . date('Y-m-d') . '" required /></div>
                </div>
                <div class="db-field">
                    <label class="db-label">Filters</label>
                    <select name="ShowType" class="db-select">
                        <option value="All">Show All Transactions</option>
                        <option value="Unmatched">Unmatched Only</option>
                        <option value="Matched">Matched Only</option>
                    </select>
                </div>
                <button type="submit" name="Show" class="db-btn db-btn-primary" style="width:100%; margin-top:1rem;">' . __('Generate Inquiry') . '</button>
                </form>
            </div>
        </div>';
} else {
	$BankDetailRow = DB_fetch_array(DB_query("SELECT bankaccountname, bankaccounts.currcode, currencies.decimalplaces FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode = currencies.currabrev WHERE bankaccounts.accountcode='" . $_POST['BankAccount'] . "'"));
	$BalancesRow = DB_fetch_array(DB_query("SELECT SUM(amount) AS balance, SUM(amount/functionalexrate/exrate) AS fbalance FROM banktrans WHERE bankact='" . $_POST['BankAccount'] . "' AND transdate<'" . FormatDateForSQL($_POST['FromTransDate']) . "'"));
    
    echo '<div class="db-main-grid">
        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Account Summary') . '</h3></div>
                <div class="db-card-body">
                    <div style="margin-bottom:1.5rem;">
                        <span class="db-label">' . __('Account Name') . '</span>
                        <div style="font-weight:800; color:var(--db-primary-dark);">' . $BankDetailRow['bankaccountname'] . '</div>
                    </div>
                    <div style="margin-bottom:1.5rem;">
                        <span class="db-label">' . __('Period') . '</span>
                        <div style="font-size:0.8rem; font-weight:700;">' . $_POST['FromTransDate'] . ' to ' . $_POST['ToTransDate'] . '</div>
                    </div>
                    <div>
                        <span class="db-label">' . __('Opening Balance') . '</span>
                        <div style="font-size:1.1rem; font-weight:900; color:var(--db-primary);">' . locale_number_format($BalancesRow['balance'], $BankDetailRow['decimalplaces']) . ' ' . $BankDetailRow['currcode'] . '</div>
                    </div>
                </div>
            </div>
            <form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <button type="submit" name="Return" class="db-btn db-btn-ghost" style="width:100%">' . __('Update Search') . '</button>
            </form>
        </div>

        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Transaction History') . '</h3></div>
                <div class="db-card-body" style="padding:0;">
                    <div class="db-table-container">
                    <table class="db-table">
                        <thead><tr>
                            <th>Date</th>
                            <th>#</th>
                            <th>Reference / Narrative</th>
                            <th style="text-align:right;">Amount</th>
                            <th style="text-align:right;">Balance</th>';
                            if ($BankDetailRow['currcode'] != $_SESSION['CompanyRecord']['currencydefault']) echo '<th style="text-align:right;">Functional (' . $_SESSION['CompanyRecord']['currencydefault'] . ')</th>';
                            echo '<th style="text-align:right;">Clear</th>
                        </tr></thead>
                        <tbody>
                            <tr class="tr-total">
                                <td colspan="4">' . __('Brought Forward') . '</td>
                                <td style="text-align:right;">' . locale_number_format($BalancesRow['balance'], $BankDetailRow['decimalplaces']) . '</td>';
                                if ($BankDetailRow['currcode'] != $_SESSION['CompanyRecord']['currencydefault']) echo '<td style="text-align:right;">' . locale_number_format($BalancesRow['fbalance'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
                                echo '<td></td>
                            </tr>';

            $SQL = "SELECT banktrans.*, systypes.typename, systypes.typeid, gltrans.narrative FROM banktrans INNER JOIN bankaccounts ON banktrans.bankact=bankaccounts.accountcode INNER JOIN systypes ON banktrans.type=systypes.typeid INNER JOIN gltrans ON banktrans.type=gltrans.type AND banktrans.transno=gltrans.typeno AND banktrans.amount=gltrans.amount WHERE bankact='" . $_POST['BankAccount'] . "' AND transdate>='" . FormatDateForSQL($_POST['FromTransDate']) . "' AND transdate<='" . FormatDateForSQL($_POST['ToTransDate']) . "' ORDER BY banktrans.transdate ASC, banktrans.banktransid ASC";
            $Res = DB_query($SQL);
            $AccTotal = $BalancesRow['balance']; $LocTotal = $BalancesRow['fbalance'];
            
            while ($MyRow = DB_fetch_array($Res)) {
                $AccTotal += $MyRow['amount']; $LocTotal += $MyRow['amount'] / $MyRow['functionalexrate'] / $MyRow['exrate'];
                $isMatched = ($MyRow['amount'] == $MyRow['amountcleared']);
                if ($_POST['ShowType'] == 'All' or ($_POST['ShowType'] == 'Unmatched' and !$isMatched) or ($_POST['ShowType'] == 'Matched' and $isMatched)) {
                    echo '<tr>
                        <td>' . ConvertSQLDate($MyRow['transdate']) . '</td>
                        <td><a href="' . $RootPath . '/GLTransInquiry.php?TypeID=' . $MyRow['typeid'] . '&TransNo=' . $MyRow['transno'] . '" style="color:var(--db-primary); font-weight:700;">' . $MyRow['transno'] . '</a></td>
                        <td><div style="font-weight:700; font-size:0.7rem;">' . $MyRow['ref'] . '</div><small style="color:var(--db-text-muted);">' . $MyRow['narrative'] . '</small></td>
                        <td style="text-align:right;"><b>' . locale_number_format($MyRow['amount'], $BankDetailRow['decimalplaces']) . '</b></td>
                        <td style="text-align:right;">' . locale_number_format($AccTotal, $BankDetailRow['decimalplaces']) . '</td>';
                        if ($BankDetailRow['currcode'] != $_SESSION['CompanyRecord']['currencydefault']) {
                            echo '<td style="text-align:right;">' . locale_number_format($LocTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
                        }
                        echo '<td style="text-align:right;"><span class="db-badge" style="background:' . ($isMatched ? 'var(--db-primary-soft)' : '#fee2e2') . '; color:' . ($isMatched ? 'var(--db-primary)' : '#dc2626') . ';">' . ($isMatched ? 'YES' : 'NO') . '</span></td>
                    </tr>';
                }
            }
            echo '<tr class="tr-total">
                    <td colspan="4">' . __('Carried Forward') . '</td>
                    <td style="text-align:right;">' . locale_number_format($AccTotal, $BankDetailRow['decimalplaces']) . '</td>';
                    if ($BankDetailRow['currcode'] != $_SESSION['CompanyRecord']['currencydefault']) echo '<td style="text-align:right;">' . locale_number_format($LocTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
                    echo '<td></td>
                </tr>
            </tbody></table></div></div></div></div>';
}

echo '</div></div>';
include(__DIR__ . '/includes/footer.php');
?>
