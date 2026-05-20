<?php

require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$Title = __('GL Budget Entry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLBudgets';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedBudget'])) {
	$SelectedBudget = $_POST['SelectedBudget'];
} elseif (isset($_GET['SelectedBudget'])) {
	$SelectedBudget = $_GET['SelectedBudget'];
}

if (isset($_POST['Update'])) {
	$UpdateSQL = array();
	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 6) == 'Period') {
			$Period = mb_substr($Key, 6);
			$UpdateSQL[] = "UPDATE glbudgetdetails SET amount='" . $Value . "' WHERE headerid='" . $SelectedBudget . "' AND account='" . $_POST['SelectedAccount'] . "' AND period='" . $Period . "'";
		}
	}
	$Errors = 0;
	foreach ($UpdateSQL as $SQL) { $UpdateResult = DB_query($SQL); $Errors+= DB_error_no(); }
	if ($Errors == 0) {
		prnMsg(__('Budget figures updated successfully'), 'success');
	} else {
		prnMsg(__('Problem updating budget figures'), 'error');
	}
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
    .db-input, .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-btn-nav { width: 48%; }
</style>';

echo '<div class="db-page">';

if (!isset($SelectedBudget)) {
	echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Budgeting') . '</div><h1 class="db-title">' . __('Budget Selection') . '</h1></header>';
	echo '<div class="db-card" style="max-width:600px; margin:0 auto;"><div class="db-card-header"><i class="fas fa-chart-line" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Select Target Budget') . '</h3></div>';
    echo '<div class="db-card-body"><form action="'.basename(__FILE__).'" method="post"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" />';
    echo '<div class="db-form-group"><label class="db-label">Budget Cycle</label><select name="SelectedBudget" class="db-select">';
    $Result = DB_query("SELECT id, name FROM glbudgetheaders");
    while ($MyRow = DB_fetch_array($Result)) echo '<option value="'.$MyRow['id'].'">'.$MyRow['name'].'</option>';
    echo '</select></div><button type="submit" name="Select" class="db-btn db-btn-primary"><i class="fas fa-edit"></i> ' . __('Manage Amounts') . '</button></form></div></div>';
} else {
    $Header = DB_fetch_array(DB_query("SELECT * FROM glbudgetheaders WHERE id='".$SelectedBudget."'"));
    
	echo '<header class="db-header"><div class="db-breadcrumb"><a href="'.basename(__FILE__).'" style="color:inherit">'.__('Budgets').'</a> / '.$Header['name'].'</div><h1 class="db-title">'.__('Enter Budget Amounts').'</h1></header>';

	$Accounts = DB_query("SELECT accountcode FROM chartmaster INNER JOIN accountgroups ON accountgroups.groupname=chartmaster.group_ WHERE pandl=1 ORDER BY accountcode");
	$AccountList = array();
	while ($r = DB_fetch_array($Accounts)) $AccountList[] = $r['accountcode'];

	foreach ($_POST as $Key => $Value) {
		if (mb_substr($Key, 0, 8) == 'Previous') $_POST['SelectedAccount'] = $AccountList[mb_substr($Key, 8) - 1];
		if (mb_substr($Key, 0, 4) == 'Next') $_POST['SelectedAccount'] = $AccountList[mb_substr($Key, 4) + 1];
	}

	if (!isset($_POST['SelectedAccount'])) $_POST['SelectedAccount'] = $AccountList[0];
	$AccountIndex = array_search($_POST['SelectedAccount'], $AccountList);

	if (isset($_POST['Update']) && isset($AccountList[$AccountIndex+1])) {
		$AccountIndex++;
		$_POST['SelectedAccount'] = $AccountList[$AccountIndex];
	}

    $AccInfo = DB_fetch_array(DB_query("SELECT accountname FROM chartmaster WHERE accountcode='".$_POST['SelectedAccount']."'"));

    echo '<form action="'.basename(__FILE__).'" method="post" id="budgetform"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" /><input type="hidden" name="SelectedBudget" value="'.$SelectedBudget.'" /><input type="hidden" name="SelectedAccount" value="'.$_POST['SelectedAccount'].'" />';
    
    echo '<div class="db-layout">';
    
    // MAIN: Amounts Entry
    echo '<main class="db-main">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-list-ol" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Period Distribution') . '</h3></div>';
    echo '<div class="db-card-body" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:1rem;">';
	$Total = 0;
	for ($P = $Header['startperiod']; $P <= $Header['endperiod']; $P++) {
		$AmountRow = DB_fetch_array(DB_query("SELECT amount FROM glbudgetdetails WHERE headerid='".$SelectedBudget."' AND account='".$_POST['SelectedAccount']."' AND period='".$P."'"));
		if (!isset($AmountRow['amount'])) {
			$AmountRow['amount'] = 0;
			DB_query("INSERT INTO glbudgetdetails (headerid, account, period, amount) VALUES ('".$SelectedBudget."', '".$_POST['SelectedAccount']."', '".$P."', '0')");
		}
		$Total+= $AmountRow['amount'];
		echo '<div class="db-form-group" style="margin:0;">
                <label class="db-label" style="font-size:0.65rem;">'.MonthAndYearFromSQLDate(EndDateSQLFromPeriodNo($P)).'</label>
                <input type="text" class="db-input number" name="Period'.$P.'" id="Period'.$P.'" value="'.$AmountRow['amount'].'" onkeyup="UpdateTotal('.$Header['startperiod'].', '.$Header['endperiod'].')" />
              </div>';
	}
    echo '</div></div></main>';

    // SIDEBAR: Summary & Navigation
    echo '<aside class="db-aside">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-info-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Account Context') . '</h3></div>';
    echo '<div class="db-card-body">';
    echo '<div class="db-form-group"><label class="db-label">Target Account</label><select name="SelectedAccount" class="db-select" onchange="this.form.submit()">';
    $AuthAcc = DB_query("SELECT chartmaster.accountcode, accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='".$_SESSION['UserID']."' AND glaccountusers.canupd=1 ORDER BY chartmaster.accountcode");
    while ($r = DB_fetch_array($AuthAcc)) echo '<option '.($_POST['SelectedAccount']==$r['accountcode']?'selected':'').' value="'.$r['accountcode'].'">'.$r['accountcode'].' - '.$r['accountname'].'</option>';
    echo '</select></div>';
    echo '<div style="background:var(--db-primary-soft); padding:1rem; border-radius:8px; border:1px solid var(--db-border); margin-bottom:1.5rem;">';
    echo '<label class="db-label" style="margin-bottom:0.25rem;">Cumulative Total</label>';
    echo '<input readonly id="Total" name="Total" value="'.$Total.'" style="font-size:1.5rem; font-weight:900; color:var(--db-primary-dark); background:transparent; border:none; width:100%; text-align:right;" />';
    echo '</div>';
    echo '<button type="submit" name="Update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> Save & Continue</button>';
    echo '<div style="display:flex; justify-content:space-between; margin-top:0.75rem;">';
    if ($AccountIndex > 0) echo '<button type="submit" class="db-btn db-btn-outline db-btn-nav" name="Previous'.$AccountIndex.'"><i class="fas fa-chevron-left"></i> Prev</button>';
    else echo '<button disabled class="db-btn db-btn-outline db-btn-nav"><i class="fas fa-chevron-left"></i> Prev</button>';
    if ($AccountIndex < count($AccountList)-1) echo '<button type="submit" class="db-btn db-btn-outline db-btn-nav" name="Next'.$AccountIndex.'">Next <i class="fas fa-chevron-right"></i></button>';
    else echo '<button disabled class="db-btn db-btn-outline db-btn-nav">Next <i class="fas fa-chevron-right"></i></button>';
    echo '</div>';
    echo '</div></div>';
    echo '<a class="db-btn db-btn-outline" style="width:100%" href="'.basename(__FILE__).'"><i class="fas fa-reply-all"></i> Selection Menu</a>';
    echo '</aside></div></form>';
}

echo '</div>';

echo '<script>
	function UpdateTotal(start, end) {
		let total = 0;
		for (let i = start; i <= end; i++) {
			let val = document.getElementById("Period" + i).value;
			total += Number(val);
		}
		document.getElementById("Total").value = total.toLocaleString();
	}
</script>';

include(__DIR__ . '/includes/footer.php');
?>
