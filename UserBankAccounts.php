<?php

// Maintains table bankaccountusers (Authorized users to work with a bank account in webERP).

require(__DIR__ . '/includes/session.php');

$Title = __('User Authorised Bank Accounts');
$ViewTopic = 'GeneralLedger';
$BookMark = 'UserBankAccounts';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedUser'])) {
	$SelectedUser = $_POST['SelectedUser'];
} elseif (isset($_GET['SelectedUser'])) {
	$SelectedUser = $_GET['SelectedUser'];
}

if (isset($_POST['SelectedBankAccount'])) {
	$SelectedBankAccount = mb_strtoupper($_POST['SelectedBankAccount']);
} elseif (isset($_GET['SelectedBankAccount'])) {
	$SelectedBankAccount = mb_strtoupper($_GET['SelectedBankAccount']);
}

if (isset($_POST['Cancel'])) {
	unset($SelectedUser, $SelectedBankAccount);
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
</style>';

echo '<div class="db-page">';

if (isset($_POST['submit'])) {
	if ($_POST['SelectedBankAccount'] == '') {
		prnMsg(__('No bank account selected'), 'error');
	} else {
		$CheckRow = DB_fetch_row(DB_query("SELECT count(*) FROM bankaccountusers WHERE accountcode= '".$_POST['SelectedBankAccount']."' AND userid = '".$_POST['SelectedUser']."'"));
		if ($CheckRow[0] > 0) {
			prnMsg(__('Bank account already authorised'), 'error');
		} else {
			DB_query("INSERT INTO bankaccountusers (accountcode, userid) VALUES ('".$_POST['SelectedBankAccount']."', '".$_POST['SelectedUser']."')");
			prnMsg(__('Bank account access granted.'), 'success');
			unset($_POST['SelectedBankAccount']);
		}
	}
} elseif (isset($_GET['delete'])) {
	DB_query("DELETE FROM bankaccountusers WHERE accountcode='".$SelectedBankAccount."' AND userid='".$SelectedUser."'");
	prnMsg(__('Bank account access revoked.'), 'success');
	unset($_GET['delete']);
}

if (!isset($SelectedUser)) {
	echo '<header class="db-header"><div class="db-breadcrumb">' . __('Banking') . ' / ' . __('Security') . '</div><h1 class="db-title">' . $Title . '</h1></header>';
	echo '<div class="db-card" style="max-width:600px; margin:0 auto;"><div class="db-card-header"><i class="fas fa-user-shield" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('User Selection') . '</h3></div>';
    echo '<div class="db-card-body"><form method="post" action="'.basename(__FILE__).'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" />';
    echo '<div class="db-form-group"><label class="db-label">Select User to Authorise</label><select name="SelectedUser" class="db-select" onchange="this.form.submit()"><option value="">' . __('Select user...') . '</option>';
    $Result = DB_query("SELECT userid, realname FROM www_users ORDER BY userid");
    while ($MyRow = DB_fetch_array($Result)) echo '<option value="'.$MyRow['userid'].'">'.$MyRow['userid'].' - '.$MyRow['realname'].'</option>';
    echo '</select></div><button name="Process" type="submit" class="db-btn db-btn-primary"><i class="fas fa-lock-open"></i> ' . __('Manage Bank Access') . '</button></form></div></div>';
} else {
	$MyRow = DB_fetch_array(DB_query("SELECT realname FROM www_users WHERE userid='".$SelectedUser."'"));
	$SelectedUserName = $MyRow['realname'];

	echo '<header class="db-header"><div class="db-breadcrumb"><a href="'.basename(__FILE__).'" style="color:inherit">'.__('Banking Security').'</a> / '.$SelectedUser.'</div><h1 class="db-title">'.$SelectedUserName.'</h1></header>';
    
    echo '<div class="db-layout">';
    
    // MAIN: Bank Accounts
    echo '<main class="db-main">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-university" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Authorised Bank Accounts') . '</h3></div>';
    echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>Bank Code</th><th>Account Name</th><th style="text-align:right">Actions</th></tr></thead><tbody>';
    $Result = DB_query("SELECT bankaccountusers.accountcode, bankaccounts.bankaccountname FROM bankaccountusers INNER JOIN bankaccounts ON bankaccountusers.accountcode=bankaccounts.accountcode WHERE bankaccountusers.userid='".$SelectedUser."' ORDER BY bankaccounts.bankaccountname ASC");
    if (DB_num_rows($Result) > 0) {
        while ($MyRow = DB_fetch_array($Result)) {
            echo '<tr><td style="font-weight:700;">'.$MyRow['accountcode'].'</td><td>'.$MyRow['bankaccountname'].'</td><td style="text-align:right;"><a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; color:#dc2626; width:auto;" href="'.basename(__FILE__).'?SelectedBankAccount='.$MyRow['accountcode'].'&delete=yes&SelectedUser='.$SelectedUser.'" onclick="return confirm(\''.__('Revoke account access?').'\');"><i class="fas fa-ban"></i></a></td></tr>';
        }
    } else {
        echo '<tr><td colspan="3" style="text-align:center; padding:2rem; color:#64748b;">'.__('No bank accounts authorised for this user yet.').'</td></tr>';
	}
    echo '</tbody></table></div></div></main>';

    // SIDEBAR: Add Bank
    echo '<aside class="db-aside">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-university" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Authorise Account') . '</h3></div>';
    echo '<div class="db-card-body"><form method="post" action="'.basename(__FILE__).'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" /><input type="hidden" name="SelectedUser" value="'.$SelectedUser.'" />';
    echo '<div class="db-form-group"><label class="db-label">Select Bank Account</label><select name="SelectedBankAccount" class="db-select"><option value="">' . __('Select bank...') . '</option>';
    $BR = DB_query("SELECT accountcode, bankaccountname, currcode FROM bankaccounts WHERE NOT EXISTS (SELECT accountcode FROM bankaccountusers WHERE userid='".$SelectedUser."' AND bankaccountusers.accountcode=bankaccounts.accountcode) ORDER BY bankaccountname");
    while ($r = DB_fetch_array($BR)) echo '<option value="'.$r['accountcode'].'">'.$r['accountcode'].' - '.$r['bankaccountname'].' ('.$r['currcode'].')</option>';
    echo '</select></div><button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> ' . __('Grant Access') . '</button></form></div></div>';
    
    echo '<a class="db-btn db-btn-outline" href="'.basename(__FILE__).'" style="width:100%"><i class="fas fa-users"></i> Switch User</a>';
    echo '<a class="db-btn db-btn-outline" style="margin-top:0.5rem; width:100%" href="index.php?Application=GL"><i class="fas fa-home"></i> Return to GL</a>';
    echo '</aside></div>';
}

echo '</div>'; // db-page

include(__DIR__ . '/includes/footer.php');
?>
