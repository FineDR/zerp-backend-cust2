<?php

// Imports bank transactions.
include(__DIR__ . '/includes/DefineImportBankTransClass.php');
require(__DIR__ . '/includes/session.php');

$Title = __('Import Bank Transactions');
$ViewTopic = 'GeneralLedger';
$BookMark = 'ImportBankTrans';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/CurrenciesArray.php');

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
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.25rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: 0.2s; }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: 0.2s; border: none; width: 100%; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .tr-analyzed { background-color: hsla(145, 63%, 38%, 0.1); }
    .tr-matched { background-color: hsla(45, 100%, 50%, 0.1); }
    
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Banking</div>
    <h1 class="db-page-title">' . $Title . '</h1>
</header>';

if (isset($_POST['ProcessBankTrans'])) {
	$InputError = false;
	if ($_SESSION['Statement']->CurrCode != $_SESSION['CompanyRecord']['currencydefault'] AND $_POST['ExchangeRate']==1) {
		prnMsg(__('Appropriate exchange rate required for foreign currency bank account'),'error'); $InputError = true;
	}
	if (!is_numeric($_POST['ExchangeRate'])) {
		prnMsg(__('Invalid exchange rate entered'),'error'); $InputError = true;
	}
    
	if ($InputError == false) {
		for($i=1;$i<=count($_SESSION['Trans']);$i++) {
			DB_Txn_Begin();
			if ($_SESSION['Trans'][$i]->DebtorNo!='' OR $_SESSION['Trans'][$i]->SupplierID!='' OR $_SESSION['Trans'][$i]->GLTotal == $_SESSION['Trans'][$i]->Amount) {
				$PeriodNo = GetPeriod($_SESSION['Trans'][$i]->ValueDate);
				$InsertBankTrans = true;
			} elseif ($_SESSION['Trans'][$i]->BankTransID!=0) {
				DB_query("UPDATE banktrans SET amountcleared=amount WHERE banktransid = '" . $_SESSION['Trans'][$i]->BankTransID . "'", '', '', true);
				$InsertBankTrans = false;
			} else { $InsertBankTrans = false; }

			if ($_SESSION['Trans'][$i]->Amount >0) {
				if ($_SESSION['Trans'][$i]->DebtorNo!='') {
					$TransType = 12; $TransNo = GetNextTransNo(12);
					DB_query("INSERT INTO debtortrans (transno, type, debtorno, trandate, inputdate, prd, rate, reference, invtext, ovamount) VALUES ('" . $TransNo . "', '12', '" . $_SESSION['Trans'][$i]->DebtorNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . date('Y-m-d H:i:s') . "', '" . $PeriodNo . "', '" . $_POST['ExchangeRate'] . "', '" . DB_escape_string($_SESSION['Trans'][$i]->Description) . "', '" . DB_escape_string($_SESSION['Trans'][$i]->Description) . "', '" . -$_SESSION['Trans'][$i]->Amount . "')", '', '', true);
					DB_query("UPDATE debtorsmaster SET lastpaiddate = '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', lastpaid='" . $_SESSION['Trans'][$i]->Amount ."' WHERE debtorno='" . $_SESSION['Trans'][$i]->DebtorNo . "'", '', '', true);
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (12, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['CompanyRecord']['debtorsact'] . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . -round($_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (12, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['Statement']->BankGLAccount . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . round($_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
				} elseif ($_SESSION['Trans'][$i]->GLTotal == $_SESSION['Trans'][$i]->Amount) {
					$TransType=2; $TransNo = GetNextTransNo(2);
					foreach ($_SESSION['Trans'][$i]->GLEntries as $GLAnalysis) {
						DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (2, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $GLAnalysis->GLCode . "', '" . mb_substr(DB_escape_string($GLAnalysis->Narrative . ' ' . $_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . -round($GLAnalysis->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
					}
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (2, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['Statement']->BankGLAccount . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . round($_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
				}
			} else { // Payments
				if ($_SESSION['Trans'][$i]->SupplierID!='') {
					$TransType = 22; $TransNo = GetNextTransNo(22);
					DB_query("INSERT INTO supptrans (transno, type, supplierno, trandate, inputdate, duedate, rate, suppreference, transtext, ovamount) VALUES ('" . $TransNo . "', '22', '" . $_SESSION['Trans'][$i]->SupplierID . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . date('Y-m-d H:i:s') . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $_POST['ExchangeRate'] . "', '" . DB_escape_string($_SESSION['Trans'][$i]->Description) . "', '" . DB_escape_string($_SESSION['Trans'][$i]->Description) . "', '" . $_SESSION['Trans'][$i]->Amount . "')", '', '', true);
					DB_query("UPDATE suppliers SET lastpaiddate = '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', lastpaid='" . $_SESSION['Trans'][$i]->Amount ."' WHERE supplierid='" . $_SESSION['Trans'][$i]->SupplierID . "'", '', '', true);
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (22, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['CompanyRecord']['creditorsact'] . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . round(-$_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (22, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['Statement']->BankGLAccount . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . round($_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
				} elseif ($_SESSION['Trans'][$i]->GLTotal == $_SESSION['Trans'][$i]->Amount) {
					$TransType = 1; $TransNo = GetNextTransNo(1);
					foreach ($_SESSION['Trans'][$i]->GLEntries as $GLAnalysis) {
						DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (1, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $GLAnalysis->GLCode . "', '" . mb_substr(DB_escape_string($GLAnalysis->Narrative . ' ' . $_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . -round($GLAnalysis->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
					}
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (1, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . $PeriodNo . "', '" . $_SESSION['Statement']->BankGLAccount . "', '" . mb_substr(DB_escape_string($_SESSION['Trans'][$i]->Description), 0, 200) . "', '" . round($_SESSION['Trans'][$i]->Amount/$_POST['ExchangeRate'],$_SESSION['CompanyRecord']['decimalplaces']+1) . "')", '', '', true);
				}
			}
			if ($InsertBankTrans == true) {
				DB_query("INSERT INTO banktrans (transno, type, bankact, ref, exrate, functionalexrate, transdate, banktranstype, amount, currcode, amountcleared) VALUES ('" . $TransNo . "', '" . $TransType . "', '" . $_SESSION['Statement']->BankGLAccount . "', '" . DB_escape_string($_SESSION['Trans'][$i]->Description) . "', '1', '" . $_POST['ExchangeRate'] . "', '" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "', '" . __('Imported') . "', '" . $_SESSION['Trans'][$i]->Amount . "', '" . $_SESSION['Statement']->CurrCode . "', '" . $_SESSION['Trans'][$i]->Amount . "')", '', '', true);
			}
			DB_Txn_Commit();
		}
		prnMsg(__('Successfully processed all analysed transactions.'),'success');
		unset($_SESSION['Trans'], $_SESSION['Statement']);
	}
}

if (!isset($_FILES['ImportFile']) AND !isset($_SESSION['Statement'])) {
	$Res = DB_query("SELECT bankaccountname, bankaccountnumber, currcode, importformat FROM bankaccounts WHERE importformat <>'' ORDER BY bankaccountname");
	if (DB_num_rows($Res) == 0) {
		prnMsg(__('No bank accounts are configured for importation. Configure import formats in Bank Accounts setup.'),'error');
		include(__DIR__ . '/includes/footer.php'); exit();
	}
    
    echo '<div class="db-main-grid">
        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">Instructions</h3></div>
                <div class="db-card-body" style="font-size:0.8rem; line-height:1.5;">
                    ' . __('Upload your bank statement file (MT940 or supported CSV). After uploading, you will be able to analyze each transaction and link it to Customer, Supplier, or GL accounts.') . '
                </div>
            </div>
        </div>
        <div class="db-column">
            <form enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Import Sources') . '</h3></div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label class="db-label">' . __('Target Bank Account') . '</label>
                        <select name="ImportFormat" class="db-select">';
                        while ($Row = DB_fetch_array($Res)) echo '<option value="'.$Row['importformat'].'">'.$Row['bankaccountname'].'</option>';
                    echo '</select></div>
                    <div class="db-field">
                        <label class="db-label">' . __('Statement File') . '</label>
                        <input type="file" name="ImportFile" class="db-input" required />
                    </div>
                    <button type="submit" name="Import" class="db-btn db-btn-primary">' . __('Upload & Analyze') . '</button>
                </div>
            </div></form>
        </div>
    </div>';

} elseif (isset($_POST['Import'])) {
    if ($_FILES['ImportFile']['size'] > (1024*1024)) { prnMsg(__('File too large'),'error'); }
    else {
        $fp = fopen($_FILES['ImportFile']['tmp_name'], 'r');
        $_SESSION['Statement'] = new BankStatement; $_SESSION['Trans'] = array();
        $_SESSION['Statement']->FileName = $_FILES['ImportFile']['tmp_name'];
        while ($LineText = fgets($fp)) {
            switch ($_POST['ImportFormat']) {
                case 'MT940-SCB': include(__DIR__ . '/includes/ImportBankTrans_MT940_SCB.php'); break;
                case 'MT940-ING': include(__DIR__ . '/includes/ImportBankTrans_MT940_ING.php'); break;
                case 'GIFTS': include(__DIR__ . '/includes/ImportBankTrans_GIFTS.php'); break;
            }
        }
        if (!isset($_SESSION['Statement']->CurrCode)) $_SESSION['Statement']->CurrCode = $_SESSION['CompanyRecord']['currencydefault'];
        $SQL = "SELECT accountcode, bankaccountname, decimalplaces, rate FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode=currencies.currabrev WHERE bankaccountnumber " . LIKE . " '" . $_SESSION['Statement']->AccountNumber ."' AND currcode = '" . $_SESSION['Statement']->CurrCode . "'";
        $Res = DB_query($SQL);
        if (DB_num_rows($Res)==0) { prnMsg(__('Account not recognized.'), 'warn'); }
        else {
            $Row = DB_fetch_array($Res);
            $_SESSION['Statement']->BankGLAccount = $Row['accountcode'];
            $_SESSION['Statement']->BankAccountName = $Row['bankaccountname'];
            $_SESSION['Statement']->CurrDecimalPlaces = $Row['decimalplaces'];
            $_SESSION['Statement']->ExchangeRate = $Row['rate'];
            for($i=1;$i<=count($_SESSION['Trans']);$i++) {
                $SQL = "SELECT banktransid FROM banktrans WHERE transdate='" . FormatDateForSQL($_SESSION['Trans'][$i]->ValueDate) . "' AND amount='" . $_SESSION['Trans'][$i]->Amount . "' AND bankact='" . $_SESSION['Statement']->BankGLAccount . "'";
                $MRes = DB_query($SQL); if (DB_num_rows($MRes)>0) $_SESSION['Trans'][$i]->BankTransID = DB_fetch_array($MRes)['banktransid'];
            }
        }
    }
}

if (isset($_SESSION['Statement'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" >';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	$AllowImport = isset($_SESSION['Statement']->BankGLAccount);

	echo '<div class="db-card">
            <div class="db-card-header" style="justify-content:space-between;">
                <h3 class="db-card-title">' . __('Review Statement') . ': ' . $_SESSION['Statement']->BankAccountName . ' (' . $_SESSION['Statement']->StatementNumber . ')</h3>
                <div class="db-badge">' . $_SESSION['Statement']->CurrCode . '</div>
            </div>
            <div class="db-card-body" style="padding:0;">
                <table class="db-table">
                    <thead><tr><th>Date</th><th>Description</th><th>Deposits</th><th>Payments</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr style="background:#f1f5f9; font-weight:800;">
                            <td colspan="2">' . __('Opening Balance') . ' (' . $_SESSION['Statement']->OpeningDate . ')</td>';
                            if($_SESSION['Statement']->OpeningBalance >= 0) echo '<td>' . number_format($_SESSION['Statement']->OpeningBalance,$_SESSION['Statement']->CurrDecimalPlaces) . '</td><td></td>';
                            else echo '<td></td><td>' . number_format($_SESSION['Statement']->OpeningBalance,$_SESSION['Statement']->CurrDecimalPlaces) . '</td>';
                            echo '<td></td>
                        </tr>';
                        
                        for ($i=1; $i<=count($_SESSION['Trans']); $i++) {
                            $trClass = '';
                            $status = '<span style="color:#aaa;">' . __('Unanalysed') . '</span>';
                            if ($_SESSION['Trans'][$i]->DebtorNo!='' OR $_SESSION['Trans'][$i]->SupplierID!='' OR $_SESSION['Trans'][$i]->GLTotal == $_SESSION['Trans'][$i]->Amount) {
                                $trClass = 'tr-analyzed'; $status = '<b style="color:var(--db-primary);">' . __('Analysed') . '</b>';
                            } elseif ($_SESSION['Trans'][$i]->BankTransID!=0) {
                                $trClass = 'tr-matched'; $status = '<b style="color:hsl(45, 80%, 40%);">' . __('Existing') . '</b>';
                            }

                            echo '<tr class="'.$trClass.'">
                                    <td>' . $_SESSION['Trans'][$i]->ValueDate . '</td>
                                    <td>' . $_SESSION['Trans'][$i]->Description . '</td>';
                            if ($_SESSION['Trans'][$i]->Amount>=0) echo '<td><b>' . number_format($_SESSION['Trans'][$i]->Amount,$_SESSION['Statement']->CurrDecimalPlaces) . '</b></td><td></td>';
                            else echo '<td></td><td><b>' . number_format($_SESSION['Trans'][$i]->Amount,$_SESSION['Statement']->CurrDecimalPlaces) . '</b></td>';
                            
                            echo '<td>' . $status . ' | <a href="' . $RootPath . '/ImportBankTransAnalysis.php?TransID=' . $i .'" class="link-action">' . __('Map') . '</a></td></tr>';
                        }
                        
                        echo '<tr style="background:#f1f5f9; font-weight:800;">
                            <td colspan="2">' . __('Closing Balance') . ' (' . $_SESSION['Statement']->ClosingDate . ')</td>';
                            if($_SESSION['Statement']->ClosingBalance >= 0) echo '<td>' . number_format($_SESSION['Statement']->ClosingBalance,$_SESSION['Statement']->CurrDecimalPlaces) . '</td><td></td>';
                            else echo '<td></td><td>' . number_format($_SESSION['Statement']->ClosingBalance,$_SESSION['Statement']->CurrDecimalPlaces) . '</td>';
                            echo '<td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="db-card-body" style="background: #fafafa; border-top: 1px solid var(--db-border);">
                <div style="display:flex; justify-content:space-between; align-items:center;">';
                if ($_SESSION['Statement']->CurrCode!=$_SESSION['CompanyRecord']['currencydefault']) {
                    echo '<div style="display:flex; align-items:center; gap:10px;">
                            <label class="db-label" style="margin:0;">' . __('Exchange Rate') . '</label>
                            <input type="text" class="db-input" name="ExchangeRate" value="' . $_SESSION['Statement']->ExchangeRate . '" style="width:100px;" />
                          </div>';
                } else { echo '<input type="hidden" name="ExchangeRate" value="1" />'; }
                
                if ($AllowImport) echo '<button type="submit" name="ProcessBankTrans" class="db-btn db-btn-primary" style="width:auto;" onclick="return confirm(\'' . __('Submit analysed transactions?') . '\');">' . __('Commit Analysed Transactions') . '</button>';
                echo '</div>
            </div>
        </div></form>';
}

echo '</div></div>';
include(__DIR__ . '/includes/footer.php');
?>
