<?php

// Allows payments and receipts to be matched off against bank statements.

require(__DIR__ . '/includes/session.php');

$Title = __('Bank Matching');
$ViewTopic = 'GeneralLedger';
$BookMark = 'BankMatching';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['AfterDate'])){$_POST['AfterDate'] = ConvertSQLDate($_POST['AfterDate']);}
if (isset($_POST['BeforeDate'])){$_POST['BeforeDate'] = ConvertSQLDate($_POST['BeforeDate']);}

if ((isset($_GET['Type']) AND $_GET['Type']=='Receipts')
		OR (isset($_POST['Type']) AND $_POST['Type']=='Receipts')) {

	$Type = 'Receipts';
	$TypeName =__('Receipts');
} elseif ((isset($_GET['Type']) AND $_GET['Type']=='Payments')
			OR (isset($_POST['Type']) AND $_POST['Type']=='Payments')) {

	$Type = 'Payments';
	$TypeName =__('Payments');
} else {
	prnMsg(__('This page must be called with a bank transaction type') . '. ' . __('It should not be called directly'),'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['Account'])) {
	$_POST['BankAccount']=$_GET['Account'];
	$_POST['ShowTransactions']=true;
	$_POST['Ostg_or_All']='Ostg';
	$_POST['First20_or_All']='All';
}

if (isset($_POST['Update']) AND $_POST['RowCounter']>1) {
	for ($Counter=1;$Counter <= $_POST['RowCounter']; $Counter++) {
		if (isset($_POST['Clear_' . $Counter]) AND $_POST['Clear_' . $Counter]) {
			$SQL = "SELECT amount, exrate FROM banktrans WHERE banktransid='" . $_POST['BankTrans_' . $Counter]."'";
			$Result = DB_query($SQL);
			$MyRow=DB_fetch_array($Result);
			$AmountCleared = round($MyRow[0] / $MyRow[1],2);
			DB_query("UPDATE banktrans SET amountcleared= ". $AmountCleared . " WHERE banktransid='" . $_POST['BankTrans_' . $Counter] . "'");
		} elseif ((isset($_POST['AmtClear_' . $Counter]) AND filter_number_format($_POST['AmtClear_' . $Counter])<0 AND $Type=='Payments')
					OR ($Type=='Receipts' AND isset($_POST['AmtClear_' . $Counter]) AND filter_number_format($_POST['AmtClear_' . $Counter])>0)) {
			DB_query("UPDATE banktrans SET amountcleared=" .  filter_number_format($_POST['AmtClear_' . $Counter]) . " WHERE banktransid='" . $_POST['BankTrans_' . $Counter]."'");
		} elseif (isset($_POST['Unclear_' . $Counter]) AND $_POST['Unclear_' . $Counter]) {
			DB_query("UPDATE banktrans SET amountcleared = 0 WHERE banktransid='" . $_POST['BankTrans_' . $Counter]."'");
		}
	}
	$_POST['ShowTransactions'] = true;
    prnMsg(__('Matching updated successfully'), 'success');
}

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1550px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; letter-spacing: -0.02em; }
    .db-page-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; }
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.25rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: 0.2s; }
    .db-input:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: 0.2s; border: none; width: 100%; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); position: sticky; top: 0; z-index: 10; }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
    .db-table tr:hover td { background: #f8fafc; }
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div>
        <div class="db-breadcrumb">General Ledger / Banking</div>
        <h1 class="db-page-title">' . __('Bank Matching - ') . $TypeName . '</h1>
    </div>
    <div style="display:flex; gap:10px;">
        <a href="' . $RootPath . '/BankReconciliation.php' . (isset($_POST['BankAccount']) ? '?Account='.$_POST['BankAccount'] : '') . '" class="db-btn db-btn-ghost" style="width:auto;">Show Reconciliation</a>
        <a href="' . $_SERVER['PHP_SELF'] . '?Type=' . ($Type=='Receipts' ? 'Payments' : 'Receipts') . '" class="db-btn db-btn-ghost" style="width:auto;">Switch to ' . ($Type=='Receipts' ? 'Payments' : 'Receipts') . '</a>
    </div>
</header>';

echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
    <input type="hidden" name="Type" value="' . $Type . '" />
    
    <div class="db-main-grid">
        <!-- LEFT Sidebar: Filters -->
        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Search Criteria') . '</h3></div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label class="db-label">' . __('Bank Account') . '</label>
                        <select name="BankAccount" class="db-select" autofocus>';
                        $SQL = "SELECT bankaccounts.accountcode, bankaccounts.bankaccountname, bankaccounts.currcode FROM bankaccounts, bankaccountusers WHERE bankaccounts.accountcode=bankaccountusers.accountcode AND bankaccountusers.userid = '" . $_SESSION['UserID'] ."' ORDER BY bankaccounts.bankaccountname";
                        $ResultBankActs = DB_query($SQL);
                        while ($MyRow=DB_fetch_array($ResultBankActs)) {
                            $sel = ((isset($_POST['BankAccount']) and $_POST['BankAccount'] == $MyRow['accountcode']) ? ' selected="selected"' : '' );
                            echo '<option ' . $sel . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['bankaccountname'] . ' - ' . $MyRow['currcode'] . '</option>';
                        }
                    echo '</select>
                    </div>';

                    if (!isset($_POST['BeforeDate'])) $_POST['BeforeDate'] = date($_SESSION['DefaultDateFormat']);
                    if (!isset($_POST['AfterDate'])) $_POST['AfterDate'] = date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m')-3,date('d'),date('y')));

                    echo '<div class="db-field">
                        <label class="db-label">' . __('From Date') . '</label>
                        <input name="AfterDate" type="date" class="db-input" value="' . FormatDateForSQL($_POST['AfterDate']) . '" required />
                    </div>';
                    echo '<div class="db-field">
                        <label class="db-label">' . __('To Date') . '</label>
                        <input name="BeforeDate" type="date" class="db-input" value="' . FormatDateForSQL($_POST['BeforeDate']) . '" required />
                    </div>';
                    echo '<div class="db-field">
                        <label class="db-label">' . __('Status') . '</label>
                        <select name="Ostg_or_All" class="db-select">
                            <option ' . (($_POST['Ostg_or_All']??'')=='All'?'selected':'') . ' value="All">Show All Transactions</option>
                            <option ' . (($_POST['Ostg_or_All']??'')!='All'?'selected':'') . ' value="Ostdg">Show Unmatched Only</option>
                        </select>
                    </div>';
                    echo '<div class="db-field">
                        <label class="db-label">' . __('Results Limit') . '</label>
                        <select name="First20_or_All" class="db-select">
                            <option ' . (($_POST['First20_or_All']??'')=='All'?'selected':'') . ' value="All">All Transactions</option>
                            <option ' . (($_POST['First20_or_All']??'')!='All'?'selected':'') . ' value="First20">First 20 Only</option>
                        </select>
                    </div>';
                    echo '<button type="submit" name="ShowTransactions" class="db-btn db-btn-primary">' . __('Search Transactions') . '</button>
                </div>
            </div>
            
            <div class="db-card" style="background:var(--db-primary-soft); border-color:var(--db-primary);">
                <div class="db-card-body" style="font-size:0.75rem; color:var(--db-primary-dark); font-weight:600;">
                    <i class="fas fa-info-circle"></i> ' . __('Match webERP transactions to your bank statement by checking the boxes or entering amounts.') . '
                </div>
            </div>
        </div>

        <!-- RIGHT Main: Transactions Table -->
        <div class="db-column">';
        
        if (isset($_POST['ShowTransactions']) && !empty($_POST['BankAccount'])) {
            $SQLBeforeDate = FormatDateForSQL($_POST['BeforeDate']);
            $SQLAfterDate = FormatDateForSQL($_POST['AfterDate']);
            $BankResult = DB_query("SELECT decimalplaces, currcode FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode=currencies.currabrev WHERE accountcode='" . $_POST['BankAccount'] . "'");
            $BankRow = DB_fetch_array($BankResult);
            $CurrDecimalPlaces = $BankRow['decimalplaces'];
            $CurrCode = $BankRow['currcode'];

            $whereStr = " AND transdate >= '". $SQLAfterDate . "' AND transdate <= '" . $SQLBeforeDate . "' AND bankact='" . $_POST['BankAccount'] . "'";
            if ($Type == 'Payments') $whereStr .= " AND amount < 0"; else $whereStr .= " AND amount > 0";
            if ($_POST['Ostg_or_All'] != 'All') $whereStr .= " AND ABS(amountcleared - (amount / exrate)) > " . CurrencyTolerance($CurrCode);

            $SQL = "SELECT banktransid, ref, amountcleared, transdate, amount/exrate as amt, banktranstype FROM banktrans WHERE 1=1 $whereStr ORDER BY transdate";
            if ($_POST['First20_or_All'] != 'All') $SQL .= " LIMIT 20";

            $PaymentsResult = DB_query($SQL);
            $totalFound = DB_num_rows($PaymentsResult);

            echo '<div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Matching Results') . ' (' . $totalFound . ')</h3></div>
                <div class="db-card-body" style="padding:0;">
                    <div class="db-table-container">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Outstanding</th>
                                    <th style="text-align:right;">' . __('Action') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

            $i = 1;
            while ($MyRow=DB_fetch_array($PaymentsResult)) {
                $Outstanding = $MyRow['amt']- $MyRow['amountcleared'];
                $isCleared = (ABS($Outstanding) < CurrencyTolerance($CurrCode));
                
                echo '<tr>
                    <td>' . $MyRow['ref'] . '</td>
                    <td><small class="db-badge">' . $MyRow['banktranstype'] . '</small></td>
                    <td>' . ConvertSQLDate($MyRow['transdate']) . '</td>
                    <td><b>' . locale_number_format($MyRow['amt'],$CurrDecimalPlaces) . '</b></td>
                    <td>' . locale_number_format($Outstanding,$CurrDecimalPlaces) . '</td>
                    <td style="text-align:right;">';
                    
                if ($isCleared) {
                    echo '<div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                        <span style="color:var(--db-primary); font-weight:800; font-size:0.6rem;">CLEARED</span>
                        <input type="checkbox" name="Unclear_' . $i . '" />
                        <input type="hidden" name="BankTrans_' . $i . '" value="' . $MyRow['banktransid'] . '" />
                    </div>';
                } else {
                    echo '<div style="display:flex; align-items:center; justify-content:flex-end; gap:8px;">
                        <input type="text" maxlength="15" size="10" class="db-input" name="AmtClear_' . $i . '" placeholder="Amt" style="padding:4px; font-size:0.7rem; width:70px;" />
                        <input type="checkbox" title="Full Clear" name="Clear_' . $i . '" />
                        <input type="hidden" name="BankTrans_' . $i . '" value="' . $MyRow['banktransid'] . '" />
                    </div>';
                }
                echo '</td></tr>';
                $i++;
            }
            echo '</tbody></table></div>';
            
            if ($totalFound > 0) {
                echo '<div class="db-card-body" style="background:#f8fafc; border-top:1px solid var(--db-border);">
                    <input type="hidden" name="RowCounter" value="' . $i . '" />
                    <button type="submit" name="Update" class="db-btn db-btn-primary" style="width:auto; float:right;">' . __('Update Matching') . '</button>
                    <div style="clear:both;"></div>
                </div>';
            } else {
                echo '<div class="db-card-body">' . __('No transactions found meeting the criteria.') . '</div>';
            }
            echo '</div>';
        } else {
            echo '<div class="db-card"><div class="db-card-body" style="text-align:center; padding:3rem; color:var(--db-text-muted);">
                <i class="fas fa-search" style="font-size:2rem; margin-bottom:1rem; display:block;"></i>
                ' . __('Configure search criteria to view transactions.') . '
            </div></div>';
        }
        echo '</div>
    </div>
</form></div></div>';

include(__DIR__ . '/includes/footer.php');
?>
