<?php

// Displays the bank reconciliation for a selected bank account.

require(__DIR__ . '/includes/session.php');

$Title = __('Bank Reconciliation');
$ViewTopic = 'GeneralLedger';
$BookMark = 'BankAccounts';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/GLFunctions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['Account'])) {
	$_POST['BankAccount'] = $_GET['Account'];
	$_POST['ShowRec'] = true;
}

if (isset($_POST['BankStatementBalance'])) {
	$_POST['BankStatementBalance'] = filter_number_format($_POST['BankStatementBalance']);
}

if (isset($_POST['PostExchangeDifference']) AND is_numeric(filter_number_format($_POST['DoExchangeDifference']))) {
	if (!is_numeric($_POST['BankStatementBalance'])) {
		prnMsg(__('Bank statement balance must be numeric'), 'warn');
	} else {
		$SQL = "SELECT rate, bankaccountname, decimalplaces AS currdecimalplaces FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode = currencies.currabrev WHERE bankaccounts.accountcode = '" . $_POST['BankAccount'] . "'";
		$CurrencyRow = DB_fetch_array(DB_query($SQL));
		$CalculatedBalance = filter_number_format($_POST['DoExchangeDifference']);
		$ExchangeDifference = ($CalculatedBalance - filter_number_format($_POST['BankStatementBalance'])) / $CurrencyRow['rate'];
		$ExDiffTransNo = GetNextTransNo(36);
		$PostingDate = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 0, date('Y')));
		$PeriodNo = GetPeriod($PostingDate);
		DB_Txn_Begin();
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (36, '" . $ExDiffTransNo . "', '" . FormatDateForSQL($PostingDate) . "', '" . $PeriodNo . "', '" . $_SESSION['CompanyRecord']['currencyexchangediffact'] . "', '" . mb_substr($CurrencyRow['bankaccountname'] . ' ' . __('reconciliation on') . " " . date($_SESSION['DefaultDateFormat']), 0, 200) . "', '" . $ExchangeDifference . "')", '', '', true);
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (36, '" . $ExDiffTransNo . "', '" . FormatDateForSQL($PostingDate) . "', '" . $PeriodNo . "', '" . $_POST['BankAccount'] . "', '" . mb_substr($CurrencyRow['bankaccountname'] . ' ' . __('reconciliation on') . ' ' . date($_SESSION['DefaultDateFormat']), 0, 200) . "', '" . (-$ExchangeDifference) . "')", '', '', true);
		DB_Txn_Commit();
		prnMsg(__('Exchange difference posted: ') . locale_number_format($ExchangeDifference, $_SESSION['CompanyRecord']['decimalplaces']), 'success');
	}
}

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
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .db-summary-card { background: #fff; padding: 1rem; border-radius: var(--radius-lg); border: 1px solid var(--db-border); }
    .db-summary-label { font-size: 0.65rem; font-weight: 800; color: var(--db-text-muted); text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
    .db-summary-value { font-size: 1.25rem; font-weight: 900; color: var(--db-primary-dark); }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; }
    .db-card-body { padding: 1rem; }
    
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; border: none; transition: 0.2s; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.6rem; }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .db-table tr:hover td { background: #f8fafc; }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Banking</div>
    <h1 class="db-page-title">' . $Title . '</h1>
</header>';

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-card" style="max-width: 600px; margin-bottom: 2rem;">
    <div class="db-card-body" style="display:flex; gap:10px; align-items:flex-end;">
        <div style="flex:1;">
            <label class="db-label" style="font-size:0.6rem; font-weight:900; text-transform:uppercase; color:var(--db-primary-dark); margin-bottom:4px; display:block;">' . __('Target Bank Account') . '</label>
            <select name="BankAccount" class="db-select">';
            $SQL = "SELECT bankaccounts.accountcode, bankaccounts.bankaccountname, bankaccounts.currcode FROM bankaccounts, bankaccountusers WHERE bankaccounts.accountcode = bankaccountusers.accountcode AND bankaccountusers.userid = '" . $_SESSION['UserID'] . "' ORDER BY bankaccounts.bankaccountname";
            $AccountsResults = DB_query($SQL);
            while ($ARow = DB_fetch_array($AccountsResults)) {
                $sel = ((isset($_POST['BankAccount']) and $_POST['BankAccount'] == $ARow['accountcode']) ? ' selected="selected"' : '');
                echo '<option ' . $sel . ' value="' . $ARow['accountcode'] . '">' . $ARow['bankaccountname'] . ' - ' . $ARow['currcode'] . '</option>';
            }
echo '      </select>
        </div>
        <button type="submit" name="ShowRec" class="db-btn db-btn-primary" style="width:auto;">' . __('Load Reconciliation') . '</button>
    </div>
</div>';

if (isset($_POST['ShowRec']) OR isset($_POST['DoExchangeDifference'])) {
	$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));
	$Balance = GetGLAccountBalance($_POST['BankAccount'], $PeriodNo);
	$SQL = "SELECT rate, bankaccounts.currcode, bankaccounts.bankaccountname, currencies.decimalplaces AS currdecimalplaces FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode = currencies.currabrev WHERE bankaccounts.accountcode = '" . $_POST['BankAccount'] . "'";
	$CurrencyRow = DB_fetch_array(DB_query($SQL));
    $accBalance = $Balance * $CurrencyRow['rate'];

    // Unpresented Cheques
    $SQL_UP = "SELECT amount / exrate AS amt, amountcleared, (amount / exrate) - amountcleared AS outstanding, ref, transdate, systypes.typename, transno FROM banktrans, systypes WHERE banktrans.type = systypes.typeid AND banktrans.bankact = '" . $_POST['BankAccount'] . "' AND amount < 0 AND ABS((amount / exrate) - amountcleared) > " . CurrencyTolerance($CurrencyRow['currcode']) . " ORDER BY transdate";
    $UPRes = DB_query($SQL_UP);
    $totalUP = 0; $upRows = []; while($R = DB_fetch_array($UPRes)) { $totalUP += $R['outstanding']; $upRows[] = $R; }

    // Uncleared Deposits
    $SQL_UD = "SELECT amount / exrate AS amt, amountcleared, (amount / exrate) - amountcleared AS outstanding, ref, transdate, systypes.typename, transno FROM banktrans INNER JOIN systypes ON banktrans.type = systypes.typeid WHERE banktrans.bankact = '" . $_POST['BankAccount'] . "' AND amount > 0 AND ABS((amount / exrate) - amountcleared) > " . CurrencyTolerance($CurrencyRow['currcode']) . " ORDER BY transdate";
    $UDRes = DB_query($SQL_UD);
    $totalUD = 0; $udRows = []; while($R = DB_fetch_array($UDRes)) { $totalUD += $R['outstanding']; $udRows[] = $R; }

    $targetStatementBalance = ($accBalance - $totalUP - $totalUD);

    echo '<div class="db-summary-grid">
        <div class="db-summary-card"><span class="db-summary-label">Ledger Balance</span><div class="db-summary-value">' . locale_number_format($accBalance, $CurrencyRow['currdecimalplaces']) . '</div></div>
        <div class="db-summary-card"><span class="db-summary-label">Unpresented Cheques</span><div class="db-summary-value" style="color:#dc2626;">(' . locale_number_format($totalUP, $CurrencyRow['currdecimalplaces']) . ')</div></div>
        <div class="db-summary-card"><span class="db-summary-label">Uncleared Deposits</span><div class="db-summary-value" style="color:var(--db-primary);">' . locale_number_format($totalUD, $CurrencyRow['currdecimalplaces']) . '</div></div>
        <div class="db-summary-card" style="background:var(--db-primary-soft); border-color:var(--db-primary);"><span class="db-summary-label">Target Statement Balance</span><div class="db-summary-value">' . locale_number_format($targetStatementBalance, $CurrencyRow['currdecimalplaces']) . '</div></div>
    </div>';

    // Section: Unpresented Cheques
    echo '<div class="db-card">
        <div class="db-card-header"><h3 class="db-card-title">Unpresented Cheques</h3><div style="font-weight:800; font-size:0.75rem; color:#dc2626;">TOTAL: ' . locale_number_format($totalUP, $CurrencyRow['currdecimalplaces']) . '</div></div>
        <div class="db-card-body" style="padding:0;">
            <table class="db-table">
                <thead><tr><th>Date</th><th>Type</th><th>#</th><th>Reference</th><th>Orig Amount</th><th>Outstanding</th></tr></thead>
                <tbody>';
                foreach($upRows as $R) echo '<tr><td>'.ConvertSQLDate($R['transdate']).'</td><td>'.$R['typename'].'</td><td>'.$R['transno'].'</td><td>'.$R['ref'].'</td><td>'.locale_number_format($R['amt'], $CurrencyRow['currdecimalplaces']).'</td><td style="color:#dc2626;"><b>'.locale_number_format($R['outstanding'], $CurrencyRow['currdecimalplaces']).'</b></td></tr>';
    echo '      </tbody></table></div></div>';

    // Section: Uncleared Deposits
    echo '<div class="db-card">
        <div class="db-card-header"><h3 class="db-card-title">Uncleared Deposits</h3><div style="font-weight:800; font-size:0.75rem; color:var(--db-primary);">TOTAL: ' . locale_number_format($totalUD, $CurrencyRow['currdecimalplaces']) . '</div></div>
        <div class="db-card-body" style="padding:0;">
            <table class="db-table">
                <thead><tr><th>Date</th><th>Type</th><th>#</th><th>Reference</th><th>Orig Amount</th><th>Outstanding</th></tr></thead>
                <tbody>';
                foreach($udRows as $R) echo '<tr><td>'.ConvertSQLDate($R['transdate']).'</td><td>'.$R['typename'].'</td><td>'.$R['transno'].'</td><td>'.$R['ref'].'</td><td>'.locale_number_format($R['amt'], $CurrencyRow['currdecimalplaces']).'</td><td style="color:var(--db-primary);"><b>'.locale_number_format($R['outstanding'], $CurrencyRow['currdecimalplaces']).'</b></td></tr>';
    echo '      </tbody></table></div></div>';

    // Section: Exchange Differences
    if (isset($_POST['DoExchangeDifference']) OR ($_SESSION['CompanyRecord']['currencydefault'] != $CurrencyRow['currcode'] AND !isset($_POST['DoExchangeDifference']))) {
        echo '<div class="db-card" style="border-left: 4px solid var(--db-primary);">
                <div class="db-card-header"><h3 class="db-card-title">Exchange Adjustment</h3></div>
                <div class="db-card-body">';
                if (isset($_POST['DoExchangeDifference'])) {
                    echo '<input type="hidden" name="DoExchangeDifference" value="' . $targetStatementBalance . '" />';
                    echo '<div style="display:flex; gap:15px; align-items:center; margin-bottom:1rem;">
                            <label class="db-label" style="margin:0;">' . __('Actual Statement Balance') . ' (' . $CurrencyRow['currcode'] . ')</label>
                            <input type="text" name="BankStatementBalance" class="db-input" style="width:200px;" value="' . locale_number_format($_POST['BankStatementBalance']??0, $CurrencyRow['currdecimalplaces']) . '" />
                          </div>
                          <button type="submit" name="PostExchangeDifference" class="db-btn db-btn-primary" style="width:auto;">Post Exchange Adjustment</button>';
                } else {
                    echo '<p style="font-size:0.8rem; line-height:1.4; color:var(--db-text-muted); margin-bottom:1rem;">' . __('Foreign currency accounts often exhibit exchange differences. Re-evaluating the balance at the current currency rate will correct the reconciliation to match the actual bank statement.') . '</p>';
                    echo '<button type="submit" name="DoExchangeDifference" class="db-btn db-btn-ghost" style="width:auto;">Calculate Exchange Difference</button>';
                }
        echo '</div></div>';
    }

    echo '<div style="display:flex; justify-content:center; gap:1rem; margin-top:2rem;">
            <a href="' . $RootPath . '/BankMatching.php?Type=Payments&Account=' . $_POST['BankAccount'] . '" class="db-btn db-btn-ghost" style="width:auto;">Match Payments</a>
            <a href="' . $RootPath . '/BankMatching.php?Type=Receipts&Account=' . $_POST['BankAccount'] . '" class="db-btn db-btn-ghost" style="width:auto;">Match Deposits</a>
          </div>';
}

echo '</form></div></div>';
include(__DIR__ . '/includes/footer.php');
?>
