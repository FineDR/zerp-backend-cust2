<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Setup Regular Payments');
$ViewTopic = 'GeneralLedger';
$BookMark = 'RegularPayments';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['FirstPaymentDate'])){$_POST['FirstPaymentDate'] = ConvertSQLDate($_POST['FirstPaymentDate']);}
if (isset($_POST['LastPaymentDate'])){$_POST['LastPaymentDate'] = ConvertSQLDate($_POST['LastPaymentDate']);}

if (isset($_GET['Complete'])) {
	$SQL = "UPDATE regularpayments SET completed=1 WHERE id='" . $_GET['Payment'] . "'";
	if (DB_query($SQL)) prnMsg(__('Regular payment marked as complete.'), 'success');
}

if (isset($_GET['Edit'])) {
	$MyRow = DB_fetch_array(DB_query("SELECT * FROM regularpayments WHERE id='" . $_GET['Payment'] . "'"));
	$_POST['Frequency'] = $MyRow['frequency'];
	$_POST['Days'] = $MyRow['days'];
	$_POST['GLManualCode'] = $MyRow['glcode'];
	$_POST['BankAccount'] = $MyRow['bankaccountcode'];
	$_POST['Tag'] = explode(',', $MyRow['tag']);
	$_POST['GLAmount'] = $MyRow['amount'];
	$_POST['Currency'] = $MyRow['currabrev'];
	$_POST['GLNarrative'] = $MyRow['narrative'];
	$_POST['FirstPaymentDate'] = ConvertSQLDate($MyRow['firstpayment']);
	$_POST['LastPaymentDate'] = ConvertSQLDate($MyRow['finalpayment']);
}

if (isset($_POST['Add']) or isset($_POST['Update'])) {
	$Err = 0;
	if ($_POST['Frequency'] == '') { prnMsg(__('Select a frequency'), 'error'); $Err = 1; }
    if (!isset($_POST['BankAccount']) or $_POST['BankAccount'] == '') { prnMsg(__('Select bank account'), 'error'); $Err = 1; }
	if (!isset($_POST['GLManualCode']) or $_POST['GLManualCode'] == '') { prnMsg(__('Select GL code'), 'error'); $Err = 1; }
    
	if ($Err == 0) {
		$Tags = implode(',', $_POST['Tag']);
		if (isset($_POST['Update'])) {
			$SQL = "UPDATE regularpayments SET frequency='".$_POST['Frequency']."', days='".$_POST['Days']."', glcode='".$_POST['GLManualCode']."', bankaccountcode='".$_POST['BankAccount']."', tag='".$Tags."', amount='".$_POST['GLAmount']."', currabrev='".$_POST['Currency']."', narrative='".$_POST['GLNarrative']."', firstpayment='".FormatDateForSQL($_POST['FirstPaymentDate'])."', finalpayment='".FormatDateForSQL($_POST['LastPaymentDate'])."' WHERE id='".$_POST['ID']."'";
		} else {
			$SQL = "INSERT INTO regularpayments (frequency, days, glcode, bankaccountcode, tag, amount, currabrev, narrative, firstpayment, finalpayment, nextpayment) VALUES ('".$_POST['Frequency']."', '".$_POST['Days']."', '".$_POST['GLManualCode']."', '".$_POST['BankAccount']."', '".$Tags."', '".$_POST['GLAmount']."', '".$_POST['Currency']."', '".$_POST['GLNarrative']."', '".FormatDateForSQL($_POST['FirstPaymentDate'])."', '".FormatDateForSQL($_POST['LastPaymentDate'])."', '".FormatDateForSQL($_POST['FirstPaymentDate'])."')";
		}
		DB_query($SQL);
		prnMsg(__('Regular payment saved.'), 'success');
		unset($_POST['ID'], $_POST['Frequency'], $_POST['Days'], $_POST['GLManualCode'], $_POST['BankAccount'], $_POST['Tag'], $_POST['GLAmount'], $_POST['Currency'], $_POST['GLNarrative'], $_POST['FirstPaymentDate'], $_POST['LastPaymentDate']);
	}
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(145, 63%, 38%); --db-primary-hover: hsl(145, 63%, 32%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 420px; gap: 2rem; align-items: start; }
    @media (max-width: 1200px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; }
    .db-input, .db-select, .db-textarea { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.85rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.825rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 850; padding: 1rem; text-align: left; border-bottom: 1px solid var(--db-border); font-size: 0.65rem; text-transform: uppercase; }
    .db-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('Payments') . ' / ' . __('Schedules') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN: ACTIVE PAYMENTS
echo '<main class="db-main">';
echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-calendar-check" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Active Schedules') . '</h3></div>';
echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>Frequency</th><th>Accounts</th><th>Tag</th><th>Amount</th><th>Timeline</th><th style="text-align:right">Actions</th></tr></thead><tbody>';

$Frequencies = ['D'=>__('Daily'), 'W'=>__('Weekly'), 'F'=>__('Fortnightly'), 'M'=>__('Monthly'), 'Q'=>__('Quarterly'), 'Y'=>__('Annually')];
$SQL = "SELECT regularpayments.*, chartmaster.accountname, bankaccounts.bankaccountname FROM regularpayments INNER JOIN bankaccounts ON bankaccounts.accountcode=regularpayments.bankaccountcode INNER JOIN chartmaster ON chartmaster.accountcode=regularpayments.glcode WHERE completed=0";
$Result = DB_query($SQL);

if (DB_num_rows($Result) == 0 && !isset($_GET['Edit'])) {
    echo '<tr><td colspan="6" style="text-align:center; padding:3rem; opacity:0.6;">'.__('No active scheduled payments found.').'</td></tr>';
} else {
    while ($MyRow = DB_fetch_array($Result)) {
        $TagText = GetDescriptionsFromTagArray(explode(',', $MyRow['tag']));
        echo '<tr>
                <td><span class="db-badge">'.$Frequencies[$MyRow['frequency']].'</span><div style="font-size:0.7rem; opacity:0.6; margin-top:0.25rem;">Day '.$MyRow['days'].'</div></td>
                <td><div style="font-weight:700; color:var(--db-primary-dark);">'.$MyRow['bankaccountname'].'</div><div style="font-size:0.75rem; opacity:0.8;">&rarr; '.$MyRow['accountname'].'</div></td>
                <td><div style="font-size:0.7rem;">'.$TagText.'</div></td>
                <td><div style="font-weight:900; color:var(--db-primary-dark);">'.locale_number_format($MyRow['amount'], 2).'</div><div style="font-size:0.7rem; opacity:0.6;">'.$MyRow['currabrev'].'</div></td>
                <td><div style="font-size:0.7rem;"><span style="opacity:0.6">Next:</span> '.ConvertSQLDate($MyRow['nextpayment']).'</div><div style="font-size:0.7rem; opacity:0.6;">End: '.ConvertSQLDate($MyRow['finalpayment']).'</div></td>
                <td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                    <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; width:auto;" href="'.basename(__FILE__).'?Payment='.$MyRow['id'].'&Edit=1"><i class="fas fa-edit"></i></a>
                    <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; color:#16a34a; width:auto;" title="Mark as Complete" href="'.basename(__FILE__).'?Payment='.$MyRow['id'].'&Complete=1"><i class="fas fa-check-circle"></i></a>
                </div></td></tr>';
    }
}
echo '</tbody></table></div></div></main>';

// SIDEBAR: SETTINGS
echo '<aside class="db-aside">';
$_POST['Frequency'] = $_POST['Frequency'] ?? '';
$_POST['Days'] = $_POST['Days'] ?? 0;
$_POST['GLManualCode'] = $_POST['GLManualCode'] ?? '';
$_POST['FirstPaymentDate'] = $_POST['FirstPaymentDate'] ?? date($_SESSION['DefaultDateFormat']);
$_POST['LastPaymentDate'] = $_POST['LastPaymentDate'] ?? date($_SESSION['DefaultDateFormat']);
$_POST['Currency'] = $_POST['Currency'] ?? $_SESSION['CompanyRecord']['currencydefault'];
$_POST['Tag'] = $_POST['Tag'] ?? ['0'];

echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . (isset($_GET['Edit'])?__('Edit Schedule'):__('New Schedule')) . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="'.basename(__FILE__).'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" />';
if (isset($_GET['Edit'])) echo '<input type="hidden" name="ID" value="'.$_GET['Payment'].'" />';

echo '<div class="db-form-group"><label class="db-label">Frequency</label><select name="Frequency" class="db-select" required><option value=""></option>';
foreach ($Frequencies as $i => $n) echo '<option value="'.$i.'" '.($_POST['Frequency']==$i?'selected':'').'>'.$n.'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Day of Period</label><input type="number" name="Days" class="db-input" value="'.$_POST['Days'].'" /></div>';

echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem; margin-bottom:1.25rem;">';
echo '<div><label class="db-label">First Date</label><input type="date" name="FirstPaymentDate" class="db-input" value="'.FormatDateForSQL($_POST['FirstPaymentDate']).'" required /></div>';
echo '<div><label class="db-label">Last Date</label><input type="date" name="LastPaymentDate" class="db-input" value="'.FormatDateForSQL($_POST['LastPaymentDate']).'" required /></div>';
echo '</div>';

echo '<div class="db-form-group"><label class="db-label">From Bank Account</label><select name="BankAccount" class="db-select" required><option value=""></option>';
$Banks = DB_query("SELECT bankaccountname, bankaccounts.accountcode, bankaccounts.currcode FROM bankaccounts INNER JOIN bankaccountusers ON bankaccounts.accountcode=bankaccountusers.accountcode WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "' ORDER BY bankaccountname");
while($b = DB_fetch_array($Banks)) echo '<option value="'.$b['accountcode'].'" '.((($_POST['BankAccount']??'')==$b['accountcode'])?'selected':'').'>'.$b['bankaccountname'].' - '.$b['currcode'].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">To GL Account</label><select name="GLManualCode" class="db-select" required><option value="">Select Account...</option>';
$Accs = DB_query("SELECT chartmaster.accountcode, accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1 ORDER BY chartmaster.accountcode");
while($a = DB_fetch_array($Accs)) echo '<option value="'.$a['accountcode'].'" '.($_POST['GLManualCode']==$a['accountcode']?'selected':'').'>'.$a['accountcode'].' - '.$a['accountname'].'</option>';
echo '</select></div>';

echo '<div style="display:grid; grid-template-columns:1fr 2fr; gap:0.5rem; margin-bottom:1.25rem;">';
echo '<div><label class="db-label">Currency</label><select name="Currency" class="db-select">';
$Curs = DB_query("SELECT currabrev FROM currencies");
while($c = DB_fetch_array($Curs)) echo '<option value="'.$c[0].'" '.($_POST['Currency']==$c[0]?'selected':'').'>'.$c[0].'</option>';
echo '</select></div>';
echo '<div><label class="db-label">Amount</label><input type="number" step="0.01" name="GLAmount" class="db-input" value="'.$_POST['GLAmount'].'" required /></div>';
echo '</div>';

echo '<div class="db-form-group"><label class="db-label">Narrative</label><input name="GLNarrative" class="db-input" value="'.($_POST['GLNarrative']??'').'" maxlength="50" /></div>';

echo '<div class="db-form-group"><label class="db-label">GL Tags</label><select name="Tag[]" class="db-select" multiple style="height:80px;">';
$Tags = DB_query("SELECT tagref, tagdescription FROM tags ORDER BY tagref");
while($t = DB_fetch_array($Tags)) echo '<option value="'.$t['tagref'].'" '.(in_array($t['tagref'], $_POST['Tag'])?'selected':'').'>'.$t['tagref'].' - '.$t['tagdescription'].'</option>';
echo '</select></div>';

if (isset($_GET['Edit'])) {
    echo '<button type="submit" name="Update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> '. __('Update Schedule').'</button>';
    echo '<a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="margin-top:0.5rem; width:100%">Cancel Edit</a>';
} else {
    echo '<button type="submit" name="Add" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> '. __('Start Schedule').'</button>';
}
echo '</form></div></div></aside>';

echo '</div></div>';

include(__DIR__ . '/includes/footer.php');
?>
