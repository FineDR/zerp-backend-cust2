<?php

// This script defines the general ledger code for bank accounts and specifies that bank transactions be created for these accounts for the purposes of reconciliation.

require(__DIR__ . '/includes/session.php');

$Title = __('Bank Accounts Maintenance');
$ViewTopic = 'GeneralLedger';
$BookMark = 'BankAccounts';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedBankAccount'])) {
	$SelectedBankAccount=$_GET['SelectedBankAccount'];
} elseif (isset($_POST['SelectedBankAccount'])) {
	$SelectedBankAccount=$_POST['SelectedBankAccount'];
}

$Errors = array();

if (isset($_POST['submit'])) {
	$InputError = 0; $i=1;

	$SQL="SELECT count(accountcode) FROM bankaccounts WHERE accountcode='".$_POST['AccountCode']."'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0]!=0 and !isset($SelectedBankAccount)) {
		$InputError = 1; prnMsg( __('Account code already exists'),'error');
		$Errors[$i++] = 'AccountCode';
	}
	if (mb_strlen($_POST['BankAccountName']) >50) { $InputError = 1; prnMsg(__('Name too long'),'error'); $Errors[$i++] = 'AccountName'; }
	if (trim($_POST['BankAccountName']) == '') { $InputError = 1; prnMsg(__('Name required'),'error'); $Errors[$i++] = 'AccountName'; }
	if (trim($_POST['BankAccountNumber']) == '') { $InputError = 1; prnMsg(__('Account number required'),'error'); $Errors[$i++] = 'AccountNumber'; }

	if (isset($SelectedBankAccount) AND $InputError !=1) {
		$SQL = "SELECT banktransid FROM banktrans WHERE bankact='" . $SelectedBankAccount . "'";
		if (DB_num_rows(DB_query($SQL))>0) {
			$SQL = "UPDATE bankaccounts SET bankaccountname='" . $_POST['BankAccountName'] . "', bankaccountcode='" . $_POST['BankAccountCode'] . "', bankaccountnumber='" . $_POST['BankAccountNumber'] . "', bankaddress='" . $_POST['BankAddress'] . "', invoice ='" . $_POST['DefAccount'] . "', importformat='" . $_POST['ImportFormat'] . "' WHERE accountcode = '" . $SelectedBankAccount . "'";
			prnMsg(__('Currency change blocked: transactions exist.'),'warn');
		} else {
			$SQL = "UPDATE bankaccounts SET bankaccountname='" . $_POST['BankAccountName'] . "', bankaccountcode='" . $_POST['BankAccountCode'] . "', bankaccountnumber='" . $_POST['BankAccountNumber'] . "', bankaddress='" . $_POST['BankAddress'] . "', currcode ='" . $_POST['CurrCode'] . "', invoice ='" . $_POST['DefAccount'] . "', importformat='" . $_POST['ImportFormat'] . "' WHERE accountcode = '" . $SelectedBankAccount . "'";
		}
		$Msg = __('Bank account updated');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO bankaccounts (accountcode, bankaccountname, bankaccountcode, bankaccountnumber, bankaddress, currcode, invoice, importformat) VALUES ('" . $_POST['AccountCode'] . "', '" . $_POST['BankAccountName'] . "', '" . $_POST['BankAccountCode'] . "', '" . $_POST['BankAccountNumber'] . "', '" . $_POST['BankAddress'] . "', '" . $_POST['CurrCode'] . "', '" . $_POST['DefAccount'] . "', '" . $_POST['ImportFormat'] . "' )";
		$Msg = __('New bank account created');
	}

	if ($InputError !=1) {
		DB_query($SQL);
		prnMsg($Msg,'success');
		unset($_POST['AccountCode'], $_POST['BankAccountName'], $_POST['BankAccountCode'], $_POST['BankAccountNumber'], $_POST['BankAddress'], $_POST['CurrCode'], $_POST['DefAccount'], $SelectedBankAccount);
	}
} elseif (isset($_GET['delete'])) {
	$MyRow = DB_fetch_array(DB_query("SELECT COUNT(bankact) AS accounts FROM banktrans WHERE bankact='" . $SelectedBankAccount . "'"));
	if ($MyRow['accounts']>0) {
		prnMsg(__('Cannot delete: transactions exist.'),'warn');
	} else {
		DB_query("DELETE FROM bankaccounts WHERE accountcode='" . $SelectedBankAccount . "'");
		prnMsg(__('Bank account deleted'),'success');
	}
	unset($_GET['delete'], $SelectedBankAccount);
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 420px; gap: 2rem; align-items: start; }
    @media (max-width: 1200px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-input, .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 850; padding: 1rem; text-align: left; border-bottom: 1px solid var(--db-border); font-size: 0.7rem; text-transform: uppercase; }
    .db-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-green { background: #dcfce7; color: #166534; }
    .db-badge-blue { background: #dbeafe; color: #1e40af; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('Banking') . ' / ' . __('Setup') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN: ACCOUNT LIST
echo '<main class="db-main">';
echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-university" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Defined Bank Accounts') . '</h3></div>';
echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>GL Account</th><th>Bank Details</th><th>Identity</th><th>Config</th><th style="text-align:right">Actions</th></tr></thead><tbody>';

$Result = DB_query("SELECT bankaccounts.*, chartmaster.accountname FROM bankaccounts LEFT JOIN chartmaster ON bankaccounts.accountcode = chartmaster.accountcode ORDER BY bankaccounts.accountcode");
while ($MyRow = DB_fetch_array($Result)) {
    $def = match($MyRow['invoice']) { '1' => 'Fall Back', '2' => 'Currency Default', default => 'No' };
    $defBadge = ($MyRow['invoice'] > 0 ? 'db-badge-green' : 'db-badge');
    
    echo '<tr>
            <td><div style="font-weight:700; color:var(--db-primary-dark);">'.$MyRow['accountcode'].'</div><div style="font-size:0.75rem; opacity:0.8;">'.$MyRow['accountname'].'</div></td>
            <td><div style="font-weight:600;">'.$MyRow['bankaccountname'].'</div><div style="font-size:0.75rem;">'.$MyRow['bankaddress'].'</div></td>
            <td><div style="font-size:0.8rem;"><span style="opacity:0.6">#</span> '.$MyRow['bankaccountnumber'].'</div><div style="font-size:0.75rem;"><span style="opacity:0.6">Code: </span>'.$MyRow['bankaccountcode'].'</div></td>
            <td><span class="db-badge db-badge-blue">'.$MyRow['currcode'].'</span><div style="margin-top:0.3rem;"><span class="db-badge '.$defBadge.'">'.$def.'</span></div></td>
            <td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; width:auto;" href="'.basename(__FILE__).'?SelectedBankAccount='.urlencode($MyRow['accountcode']).'"><i class="fas fa-edit"></i></a>
                <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; color:#dc2626; width:auto;" href="'.basename(__FILE__).'?SelectedBankAccount='.urlencode($MyRow['accountcode']).'&delete=1" onclick="return confirm(\''.__('Delete bank account?').'\');"><i class="fas fa-trash"></i></a>
            </div></td></tr>';
}
echo '</tbody></table></div></div></main>';

// SIDEBAR: FORM
echo '<aside class="db-aside">';
if (isset($SelectedBankAccount)) {
    $MyRow = DB_fetch_array(DB_query("SELECT * FROM bankaccounts WHERE accountcode='".$SelectedBankAccount."'"));
    $_POST['AccountCode'] = $MyRow['accountcode'];
    $_POST['BankAccountName']  = $MyRow['bankaccountname'];
    $_POST['BankAccountCode']  = $MyRow['bankaccountcode'];
    $_POST['BankAccountNumber'] = $MyRow['bankaccountnumber'];
    $_POST['BankAddress'] = $MyRow['bankaddress'];
    $_POST['CurrCode'] = $MyRow['currcode'];
    $_POST['DefAccount'] = $MyRow['invoice'];
    $_POST['ImportFormat'] = $MyRow['importformat'];
}

echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . (isset($SelectedBankAccount)?__('Edit Account'):__('New Account')) . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($SelectedBankAccount)) echo '<input type="hidden" name="SelectedBankAccount" value="' . $SelectedBankAccount . '" />';

echo '<div class="db-form-group"><label class="db-label">GL Account Code</label>';
if (isset($SelectedBankAccount)) {
    echo '<div class="db-input" style="background:#f8fafc; border-color:#e2e8f0;">'.$_POST['AccountCode'].'</div><input type="hidden" name="AccountCode" value="'.$_POST['AccountCode'].'" />';
} else {
    echo '<select name="AccountCode" class="db-select" required>';
    $CM = DB_query("SELECT chartmaster.accountcode, accountname FROM chartmaster LEFT JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname WHERE accountgroups.pandl = 0 ORDER BY accountcode");
    while($r = DB_fetch_array($CM)) echo '<option value="'.$r[0].'">'.$r[0].' - '.$r[1].'</option>';
    echo '</select>';
}
echo '</div>';

echo '<div class="db-form-group"><label class="db-label">Bank Account Name</label><input class="db-input" name="BankAccountName" required value="'.($_POST['BankAccountName']??'').'" /></div>';
echo '<div class="db-form-group"><label class="db-label">Bank Branch Code</label><input class="db-input" name="BankAccountCode" value="'.($_POST['BankAccountCode']??'').'" /></div>';
echo '<div class="db-form-group"><label class="db-label">Bank Account Number</label><input class="db-input" name="BankAccountNumber" required value="'.($_POST['BankAccountNumber']??'').'" /></div>';
echo '<div class="db-form-group"><label class="db-label">Bank Address</label><input class="db-input" name="BankAddress" value="'.($_POST['BankAddress']??'').'" /></div>';

echo '<div class="db-form-group"><label class="db-label">Import Format</label><select name="ImportFormat" class="db-select">
    <option value="" '.(!isset($_POST['ImportFormat']) || $_POST['ImportFormat']==''?'selected':'').'>N/A</option>
    <option value="MT940-SCB" '.((isset($_POST['ImportFormat']) && $_POST['ImportFormat']=='MT940-SCB')?'selected':'').'>MT940 - Siam Comercial (TH)</option>
    <option value="MT940-ING" '.((isset($_POST['ImportFormat']) && $_POST['ImportFormat']=='MT940-ING')?'selected':'').'>MT940 - ING Bank (NL)</option>
    <option value="GIFTS" '.((isset($_POST['ImportFormat']) && $_POST['ImportFormat']=='GIFTS')?'selected':'').'>GIFTS - BNZ (NZ)</option>
</select></div>';

echo '<div class="db-form-group"><label class="db-label">Currency</label><select name="CurrCode" class="db-select">';
$CUR = DB_query("SELECT currabrev FROM currencies");
$defCurr = $_SESSION['CompanyRecord']['currencydefault'];
while($c = DB_fetch_array($CUR)) echo '<option value="'.$c[0].'" '.((($_POST['CurrCode']??$defCurr)==$c[0])?'selected':'').'>'.$c[0].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Def for Invoices</label><select name="DefAccount" class="db-select">
    <option value="0" '.((($_POST['DefAccount']??0)==0)?'selected':'').'>No</option>
    <option value="1" '.((($_POST['DefAccount']??0)==1)?'selected':'').'>Fall Back Default</option>
    <option value="2" '.((($_POST['DefAccount']??0)==2)?'selected':'').'>Currency Default</option>
</select></div>';

echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-save"></i> Save Account Details</button>';
if (isset($SelectedBankAccount)) echo '<a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="margin-top:0.5rem; text-decoration:none;">Cancel Edit</a>';
echo '</form></div></div></aside>';

echo '</div></div>';

include(__DIR__ . '/includes/footer.php');
?>
