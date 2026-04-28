<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Budget Header Maintenance');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLBudgets';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['Submit']) or isset($_POST['Update'])) {
	$InputError = 0;
	if ($_POST['StartPeriod'] > $_POST['EndPeriod']) {
		prnMsg(__('The end period cannot be before the start period'), 'error');
		$InputError = 1;
	}

	if ($InputError == 0) {
		if ($_POST['Primary'] == 1) DB_query("UPDATE glbudgetheaders SET `current`=0");
        
		if (isset($_POST['Submit'])) {
			DB_query("INSERT INTO glbudgetheaders (owner, name, description, startperiod, endperiod, current) VALUES ('" . $_POST['Owner'] . "', '" . $_POST['Name'] . "', '" . $_POST['Description'] . "', '" . $_POST['StartPeriod'] . "', '" . $_POST['EndPeriod'] . "', '" . $_POST['Primary'] . "')");
			$HeaderNo = DB_Last_Insert_ID('glbudgetheaders', 'id');

			$Periods = DB_query("SELECT periodno FROM periods");
			$Accounts = DB_query("SELECT accountcode FROM chartmaster");
			$PA = array(); while ($r = DB_fetch_array($Periods)) $PA[] = $r['periodno'];
			$AA = array(); while ($r = DB_fetch_array($Accounts)) $AA[] = $r['accountcode'];

			foreach ($AA as $Account) {
				foreach ($PA as $Period) {
					DB_query("INSERT INTO glbudgetdetails (headerid, account, period, amount) VALUES ('".$HeaderNo."', '".$Account."', '".$Period."', 0)");
				}
			}
		} elseif (isset($_POST['Update'])) {
			DB_query("UPDATE glbudgetheaders SET `owner`='" . $_POST['Owner'] . "', `name`='" . $_POST['Name'] . "', `description`='" . $_POST['Description'] . "', `startperiod`='" . $_POST['StartPeriod'] . "', `endperiod`='" . $_POST['EndPeriod'] . "', `current`='" . $_POST['Primary'] . "' WHERE `id`='" . $_POST['ID'] . "'");
		}
		if (DB_error_no() == 0) prnMsg(__('Budget header saved successfully'), 'success');
	}
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(145, 63%, 38%); --db-primary-hover: hsl(145, 63%, 32%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start; }
    @media (max-width: 1200px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-input, .db-select, .db-textarea { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-textarea { min-height: 100px; resize: vertical; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 850; padding: 1rem; text-align: left; border-bottom: 1px solid var(--db-border); font-size: 0.7rem; text-transform: uppercase; }
    .db-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-green { background: #dcfce7; color: #166534; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Budget Setup') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN: BUDGET LIST
echo '<main class="db-main">';
echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-layer-group" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Budget Cycles') . '</h3></div>';
echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>Cycle ID</th><th>Owner</th><th>Cycle Name</th><th>Duration</th><th>Primary</th><th style="text-align:right">Actions</th></tr></thead><tbody>';

$Result = DB_query("SELECT id, owner, name, startperiod, endperiod, current FROM glbudgetheaders ORDER BY id DESC");
while ($MyRow = DB_fetch_array($Result)) {
    $primary = ($MyRow['current'] == 1 ? '<span class="db-badge db-badge-green">'.__('Yes').'</span>' : '<span class="db-badge">'.__('No').'</span>');
    echo '<tr>
            <td style="font-weight:700;">#'.$MyRow['id'].'</td>
            <td>'.$MyRow['owner'].'</td>
            <td style="font-weight:600; color:var(--db-primary-dark);">'.$MyRow['name'].'</td>
            <td><div style="font-size:0.75rem;">'.MonthAndYearFromPeriodNo($MyRow['startperiod']).'</div>
                <div style="font-size:0.75rem; opacity:0.6;">to '.MonthAndYearFromPeriodNo($MyRow['endperiod']).'</div></td>
            <td>'.$primary.'</td>
            <td style="text-align:right;"><a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; width:auto;" href="'.basename(__FILE__).'?Edit='.$MyRow['id'].'"><i class="fas fa-edit"></i></a></td></tr>';
}
echo '</tbody></table></div></div></main>';

// SIDEBAR: FORM
echo '<aside class="db-aside">';
if (isset($_GET['Edit'])) {
    $MyRow = DB_fetch_array(DB_query("SELECT * FROM glbudgetheaders WHERE id='".$_GET['Edit']."'"));
    $_POST['Owner'] = $MyRow['owner'];
    $_POST['Name'] = $MyRow['name'];
    $_POST['Description'] = $MyRow['description'];
    $_POST['StartPeriod'] = $MyRow['startperiod'];
    $_POST['EndPeriod'] = $MyRow['endperiod'];
    $_POST['Primary'] = $MyRow['current'];
} else {
    $_POST['Owner'] = $_POST['Owner'] ?? $_SESSION['UserID'];
    $_POST['Name'] = $_POST['Name'] ?? '';
    $_POST['Description'] = $_POST['Description'] ?? '';
    $_POST['StartPeriod'] = $_POST['StartPeriod'] ?? ReportPeriod(__('This Financial Year'), 'From');
    $_POST['EndPeriod'] = $_POST['EndPeriod'] ?? ReportPeriod(__('This Financial Year'), 'To');
    $_POST['Primary'] = $_POST['Primary'] ?? 0;
}

echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . (isset($_GET['Edit'])?__('Edit Cycle'):__('Create Cycle')) . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="'.basename(__FILE__).'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" />';
if (isset($_GET['Edit'])) echo '<input type="hidden" name="ID" value="'.$_GET['Edit'].'" />';

echo '<div class="db-form-group"><label class="db-label">Budget Owner</label><select name="Owner" class="db-select">';
$Users = DB_query("SELECT userid, realname FROM www_users");
while($u = DB_fetch_array($Users)) echo '<option value="'.$u['userid'].'" '.($_POST['Owner']==$u['userid']?'selected':'').'>'.$u['realname'].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Cycle Name</label><input class="db-input" name="Name" required value="'.$_POST['Name'].'" /></div>';
echo '<div class="db-form-group"><label class="db-label">Extended Description</label><textarea class="db-textarea" name="Description">'.$_POST['Description'].'</textarea></div>';

$Periods = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno");
echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">';
echo '<div class="db-form-group"><label class="db-label">Start Period</label><select name="StartPeriod" class="db-select">';
DB_data_seek($Periods, 0);
while($p = DB_fetch_array($Periods)) echo '<option value="'.$p['periodno'].'" '.($_POST['StartPeriod']==$p['periodno']?'selected':'').'>'.MonthAndYearFromSQLDate($p['lastdate_in_period']).'</option>';
echo '</select></div>';
echo '<div class="db-form-group"><label class="db-label">End Period</label><select name="EndPeriod" class="db-select">';
DB_data_seek($Periods, 0);
while($p = DB_fetch_array($Periods)) echo '<option value="'.$p['periodno'].'" '.($_POST['EndPeriod']==$p['periodno']?'selected':'').'>'.MonthAndYearFromSQLDate($p['lastdate_in_period']).'</option>';
echo '</select></div>';
echo '</div>';

echo '<div class="db-form-group"><label class="db-label">Primary Budget?</label><select name="Primary" class="db-select">
    <option value="0" '.($_POST['Primary']==0?'selected':'').'>No</option>
    <option value="1" '.($_POST['Primary']==1?'selected':'').'>Yes</option>
</select></div>';

if (isset($_GET['Edit'])) {
    echo '<button type="submit" name="Update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> '. __('Update Cycle').'</button>';
    echo '<a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="margin-top:0.5rem; width:100%">Cancel Edit</a>';
} else {
    echo '<button type="submit" name="Submit" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> '. __('Initialize Cycle').'</button>';
}
echo '</form></div></div>';
echo '<a class="db-btn db-btn-outline" style="width:100%" href="GLBudgets.php"><i class="fas fa-pen-fancy"></i> ' . __('Enter Budget Data') . '</a>';
echo '</aside></div></div>';

include(__DIR__ . '/includes/footer.php');
?>
