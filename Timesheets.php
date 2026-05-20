<?php

/* Timesheet Entry */

require(__DIR__ . '/includes/session.php');

$Title = __('Timesheet Entry');
$ViewTopic = 'Labour';
$BookMark = 'Timesheets';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$MaxHours = 15; // perhaps this should be a configuration option??

// Architectural Workspace Design System v2 - High Density
echo '
<style>
	:root {
		--primary: hsl(197, 92%, 47%); 
		--primary-hover: hsl(197, 92%, 38%);
		--primary-dark: hsl(197, 75%, 22%);
		--primary-soft: hsl(197, 65%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(197, 15%, 12%);
		--text-muted: hsl(197, 8%, 50%);
		--card-bg: #ffffff;
		--radius: 12px;
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); }
	.aw-container { padding: 2px 10px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
	.MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1200px) { 
		.aw-grid-layout { grid-template-columns: 1fr 350px; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .aw-btn-success:hover { background: #d1fae5; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; }
    .aw-badge-pending { background: #fef3c7; color: #d97706; }
    .aw-badge-submitted { background: #e0f2fe; color: #0284c7; }
    .aw-badge-approved { background: #d1fae5; color: #059669; }

    .aw-stat-box { background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
	.aw-stat-label { font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
	.aw-stat-val { font-size: 1.25rem; font-weight: 950; color: var(--primary-dark); }
</style>
<div class="aw-container">';

$LatestWeekEndingDate = date($_SESSION['DefaultDateFormat'],mktime(0,0,0,date('n'),date('j')-(date('w')+$_SESSION['LastDayOfWeek'])+7,date('Y')));
if (isset($_GET['SelectedEmployee'])) { if ($_GET['SelectedEmployee']=='NewSelection'){ unset($SelectedEmployee); } else { $SelectedEmployee = $_GET['SelectedEmployee']; } } elseif (isset($_POST['SelectedEmployee'])) { $SelectedEmployee = $_POST['SelectedEmployee']; } else { $CheckUserResult = DB_query("SELECT id FROM employees WHERE userid='" . $_SESSION['UserID'] . "'"); if (DB_num_rows($CheckUserResult)>0) { $LoggedInEmployeeRow = DB_fetch_array($CheckUserResult); $SelectedEmployee = $LoggedInEmployeeRow['id']; } }
if (isset($_GET['WeekEnding'])) { $_POST['WeekEnding'] = $_GET['WeekEnding']; } elseif (!isset($_POST['WeekEnding'])) { $_POST['WeekEnding'] = $LatestWeekEndingDate; }

if (isset($SelectedEmployee)) { $EmployeeRow = DB_fetch_array(DB_query("SELECT id, surname, firstname, employees.stockid, manager, normalhours, userid, email, decimalplaces FROM employees INNER JOIN stockmaster ON employees.stockid=stockmaster.stockid WHERE employees.id='" . $SelectedEmployee . "'")); if ($EmployeeRow['userid']!='') { $EmployeeLocationRow = DB_fetch_array(DB_query("SELECT defaultlocation FROM www_users WHERE userid='" . $EmployeeRow['userid'] ."'")); $EmployeeLocation = $EmployeeLocationRow['defaultlocation']; } else { $EmployeeLocation =''; } }

if ((isset($_POST['Enter']) OR isset($_POST['ApproveTimesheet']) OR isset($_POST['SubmitForApproval'])) AND isset($SelectedEmployee) AND isset($_POST['WeekEnding'])) {
	if (isset($_POST['Rows']) AND $_POST['Rows'] > 0) {
		for ($Row=0; $Row < $_POST['Rows']; $Row++) {
			$InputError = 0;
			for($d=1;$d<=7;$d++) { if (!is_numeric($_POST['Day'.$d.'_' . $Row])){ $_POST['Day'.$d.'_' . $Row] = 0; } if ($_POST['Day'.$d.'_' . $Row] > $MaxHours OR $_POST['Day'.$d.'_' . $Row] < -$MaxHours) { $InputError = 1; prnMsg(__('The hours entered look to be too high'),'error'); } }
			if (($_POST['Day1_' . $Row]+$_POST['Day2_' . $Row]+$_POST['Day3_' . $Row]+$_POST['Day4_' . $Row]+$_POST['Day5_' . $Row]+$_POST['Day6_' . $Row]+$_POST['Day7_' . $Row]) == 0){ $InputError = 1; }
			if ($InputError == 0 ) { DB_query("UPDATE timesheets SET day1 ='" . $_POST['Day1_' . $Row] . "', day2 ='" . $_POST['Day2_' . $Row] . "', day3 ='" . $_POST['Day3_' . $Row] . "', day4 ='" . $_POST['Day4_' . $Row] . "', day5 ='" . $_POST['Day5_' . $Row] . "', day6 ='" . $_POST['Day6_' . $Row] . "', day7 ='" . $_POST['Day7_' . $Row] . "' WHERE id='" . $_POST['id_' . $Row] . "'"); }
		}
	}
	$InputError = 0; if ($_POST['WO'] == '0' AND $_POST['WorkCentre'] != '0') { prnMsg(__('Invalid WO/WC combination'),'error'); $InputError = 1; }
	for($d=1;$d<=7;$d++){ if (!is_numeric(filter_number_format($_POST['Day'.$d]))){ $_POST['Day'.$d] = 0; } if (filter_number_format($_POST['Day'.$d]) > $MaxHours OR filter_number_format($_POST['Day'.$d]) < -$MaxHours) { $InputError = 1; } }
	if ((filter_number_format($_POST['Day1'])+filter_number_format($_POST['Day2'])+filter_number_format($_POST['Day3'])+filter_number_format($_POST['Day4'])+filter_number_format($_POST['Day5'])+filter_number_format($_POST['Day6'])+filter_number_format($_POST['Day7'])) == 0 ){ $InputError = 1; }

	if ($InputError==0) {
		$CheckResult = DB_query("SELECT id FROM timesheets WHERE employeeid='" . $SelectedEmployee . "' AND wo='" . $_POST['WO'] . "' AND weekending='" . FormatDateForSQL($_POST['WeekEnding']) . "' AND workcentre='" . $_POST['WorkCentre'] . "'");
		if (DB_num_rows($CheckResult)==1) { $ETRow = DB_fetch_array($CheckResult); DB_query("UPDATE timesheets SET day1=day1+" . filter_number_format($_POST['Day1']) .", day2=day2+" . filter_number_format($_POST['Day2']) .", day3=day3+" . filter_number_format($_POST['Day3']) .", day4=day4+" . filter_number_format($_POST['Day4']) .", day5=day5+" . filter_number_format($_POST['Day5']) .", day6=day6+" . filter_number_format($_POST['Day6']) .", day7=day7+" . filter_number_format($_POST['Day7']) ." WHERE id ='" . $ETRow['id'] . "'"); prnMsg(__('Timesheet updated'),'info'); }
		else { DB_query("INSERT INTO timesheets (wo, employeeid, workcentre, weekending, day1, day2, day3, day4, day5, day6, day7) VALUES ('" . $_POST['WO'] . "', '" . $SelectedEmployee . "', '" . $_POST['WorkCentre'] . "', '" . FormatDateForSQL($_POST['WeekEnding']) . "', '" . filter_number_format($_POST['Day1']) . "', '" . filter_number_format($_POST['Day2']) . "', '" . filter_number_format($_POST['Day3']) . "', '" . filter_number_format($_POST['Day4']) . "', '" . filter_number_format($_POST['Day5']) . "', '" . filter_number_format($_POST['Day6']) . "', '" . filter_number_format($_POST['Day7']) . "')"); prnMsg(__('Timesheet record added'),'info'); }
		unset($_POST['WO'], $_POST['WorkCentre'], $_POST['Day1'], $_POST['Day2'], $_POST['Day3'], $_POST['Day4'], $_POST['Day5'], $_POST['Day6'], $_POST['Day7']);
	}
}

if (isset($_POST['SubmitForApproval'])) {
	$WTHRow = DB_fetch_array(DB_query("SELECT SUM(day1+day2+day3+day4+day5+day6+day7) as totalweekhours FROM timesheets WHERE employeeid ='" . $SelectedEmployee . "' AND weekending ='" . FormatDateForSQL($_POST['WeekEnding']) ."' GROUP BY employeeid"));
	if ($WTHRow['totalweekhours'] < $EmployeeRow['normalhours']) { prnMsg(__('Full working weeks hours must be accounted for'),'error'); }
	else {
		DB_query("UPDATE timesheets SET status=1 WHERE employeeid='" . $SelectedEmployee . "' AND status=0 AND weekending='" . FormatDateForSQL($_POST['WeekEnding']) . "'");
		$ManagerRow = DB_fetch_array(DB_query("SELECT email FROM employees WHERE employees.id='" . $EmployeeRow['manager'] . "'"));
		$EmailSubject = $EmployeeRow['firstname'] . ' ' . $EmployeeRow['surname'] . ' ' . __('timesheet submitted') . ' ' . $_POST['WeekEnding'];
		SendEmailFromWebERP($_SESSION['CompanyRecord']['email'], array($ManagerRow['email']), $EmailSubject, '<p>' . $EmailSubject . '</p><p><a href="' . $RootPath . '/Timesheets.php?SelectedEmployee=' . $SelectedEmployee  . '&WeekEnding=' . $_POST['WeekEnding'] . '">' . __('Review and approve') . '</a></p>', '', true);
		prnMsg(__('Timesheet submitted for approval'),'success');
	}
}

if (isset($_POST['ApproveTimesheet'])) {
	$WTHRow = DB_fetch_array(DB_query("SELECT actualcost AS labourcost, SUM(day1+day2+day3+day4+day5+day6+day7) as totalweekhours FROM timesheets INNER JOIN employees ON timesheets.employeeid=employees.id INNER JOIN stockmaster ON employees.stockid=stockmaster.stockid WHERE employeeid ='" . $SelectedEmployee . "' AND weekending ='" . FormatDateForSQL($_POST['WeekEnding']) ."' GROUP BY employeeid, employees.stockid, labourcost"));
	if ($WTHRow['totalweekhours'] < $EmployeeRow['normalhours']) { prnMsg(__('Full hours must be entered'),'error'); } elseif ($WTHRow['labourcost']==0) { prnMsg(__('Labour cost must be set'),'error'); }
	else {
		$WeekTimeResult = DB_query("SELECT timesheets.wo, timesheets.workcentre, employees.stockid as issueitem, employees.surname, employees.firstname, actualcost AS labourcost, workorders.loccode, SUM(day1+day2+day3+day4+day5+day6+day7) as totalweekhours FROM timesheets INNER JOIN employees ON timesheets.employeeid=employees.id INNER JOIN stockmaster ON employees.stockid=stockmaster.stockid INNER JOIN workorders ON timesheets.wo=workorders.wo WHERE employeeid ='" . $SelectedEmployee . "' AND weekending ='" . FormatDateForSQL($_POST['WeekEnding']) ."' AND workorders.closed = '0' AND timesheets.status <> '2' GROUP BY wo, workcentre, issueitem, surname, firstname, labourcost, loccode");
		if (DB_num_rows($WeekTimeResult)>0) {
			$WOIssueNo = GetNextTransNo(28); $PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat'])); DB_Txn_Begin();
			while ($WTRow = DB_fetch_array($WeekTimeResult)) {
				DB_query("INSERT INTO stockmoves (stockid, type, transno, loccode, trandate, userid, price, prd, reference, qty, standardcost, newqoh, narrative) VALUES ('" . $WTRow['issueitem'] . "', 28, '" . $WOIssueNo . "', '" . $WTRow['loccode'] . "', '" . FormatDateForSQL($_POST['WeekEnding']) . "', '" . $_SESSION['UserID'] . "', '" . $WTRow['labourcost'] . "', '" . $PeriodNo . "', '" . $WTRow['wo'] . "', '" . -$WTRow['totalweekhours'] . "', '" . $WTRow['labourcost'] . "', '0', '" . $WTRow['firstname'] . " " . $WTRow['surname'] . "')");
				if ($_SESSION['CompanyRecord']['gllink_stock']==1) {
					$WIPAccRow = DB_fetch_array(DB_query("SELECT wipact FROM stockcategory INNER JOIN stockmaster ON stockcategory.categoryid=stockmaster.categoryid INNER JOIN woitems ON stockmaster.stockid=woitems.stockid WHERE woitems.wo='" . $WTRow['wo'] . "'"));
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (28, '" . $WOIssueNo . "', '" . FormatDateForSQL($_POST['WeekEnding']) . "', '" . $PeriodNo . "', '" . $WIPAccRow['wipact'] . "', 'WO:" . $WTRow['wo'] . "', '" . ($WTRow['labourcost'] * $WTRow['totalweekhours']) . "')");
					$ItemGL = GetStockGLCode($WTRow['issueitem']); DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES (28, '" . $WOIssueNo . "', '" . FormatDateForSQL($_POST['WeekEnding']) . "', '" . $PeriodNo . "', '" . $ItemGL['stockact'] . "', 'Recov WO:" . $WTRow['wo'] . "', '" . -($WTRow['labourcost'] * $WTRow['totalweekhours']) . "')");
				}
				DB_query("UPDATE workorders SET costissued=costissued+" . ($WTRow['labourcost'] * $WTRow['totalweekhours']) . " WHERE wo='" . $WTRow['wo'] . "'");
			}
			DB_query("UPDATE timesheets SET status=2 WHERE employeeid='" . $SelectedEmployee . "' AND weekending='" . FormatDateForSQL($_POST['WeekEnding']) . "'");
			DB_Txn_Commit(); prnMsg(__('Timesheet posted to Work Orders'),'success');
		}
	}
}

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Time Tracking</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

if (!isset($SelectedEmployee) AND in_array(20, $_SESSION['AllowedPageSecurityTokens'])) {
	$SQL = "SELECT employees.id, employees.surname, employees.firstname, employees.stockid, employees2.firstname as managerfirstname, employees2.surname as managersurname, employees.email FROM employees LEFT JOIN employees AS employees2 ON employees.manager=employees2.id";
	$Result = DB_query($SQL);
	echo '<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Select Employee for Entry') . '</h3></div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead><tr><th>' . __('ID') . '</th><th>' . __('Name') . '</th><th>' . __('Team Manager') . '</th><th>' . __('Labor Code') . '</th><th style="text-align:right;">' . __('Action') . '</th></tr></thead>
					<tbody>';
	while ($R = DB_fetch_array($Result)) {
		echo '<tr><td>' . $R['id'] . '</td><td style="font-weight:700;">' . $R['firstname'] . ' ' . $R['surname'] . '</td><td>' . $R['managerfirstname'] . ' ' . $R['managersurname'] . '</td><td>' . $R['stockid'] . '</td><td style="text-align:right;"><a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?SelectedEmployee='.$R['id'].'" class="aw-btn aw-btn-primary aw-btn-sm">' . __('Select') . '</a></td></tr>';
	}
	echo '</tbody></table></div></div></div>'; include(__DIR__ . '/includes/footer.php'); exit();
}

if (isset($SelectedEmployee)) {
	echo '<form id="TimesheetForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" /><input type="hidden" name="SelectedEmployee" value="' . $SelectedEmployee . '" />';

	echo '<div class="aw-grid aw-grid-layout">';
	
	// MAIN CONTENT (Left)
	echo '<main class="aw-main-side">';
	
	echo '<div class="aw-card">
			<div class="aw-card-header">
				<h3 class="aw-card-title">' . __('Weekly Time Distribution') . '</h3>
				<div style="display:flex; align-items:center; gap:8px;">
					<select name="WeekEnding" class="aw-select" style="width:180px; padding:4px;">';
					echo '<option value="' . $LatestWeekEndingDate . '" '.($_POST['WeekEnding']==$LatestWeekEndingDate?'selected':'').'>' . $LatestWeekEndingDate . '</option>';
					for ($i=-1;$i>-26;$i--) { $PWeek = DateAdd($LatestWeekEndingDate,'w',$i); echo '<option ' . ($_POST['WeekEnding']==$PWeek?'selected':'') . ' value="' . $PWeek . '">' . $PWeek . '</option>'; }
	echo '			</select>
					<button type="submit" name="RefreshWeek" class="aw-btn-secondary aw-btn-sm" style="border-radius:8px;">' . __('Switch Week') . '</button>
				</div>
			</div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('Work Order / Objective') . '</th>
							<th style="width:140px;">' . __('Process') . '</th>';
							$FirstDayNum = ($_SESSION['LastDayOfWeek']==6) ? 0 : $_SESSION['LastDayOfWeek']+1;
							for ($i=0;$i<7;$i++) { $DayNum = ($FirstDayNum + $i > 6) ? $FirstDayNum + $i - 7 : $FirstDayNum + $i; echo '<th style="width:60px; text-align:center;">' . mb_substr(GetWeekDayText($DayNum),0,1) . '</th>'; }
	echo '					<th style="width:70px; text-align:right;">' . __('Total') . '</th>
							<th style="width:50px;"></th>
						</tr>
					</thead>
					<tbody>';

	$DayTotals = array(0,0,0,0,0,0,0); $EditableRowNo = 0; $PostedRowNo = 0;
	$TSRes = DB_query("SELECT id, wo, workcentre, workcentres.description as workcentrename, day1, day2, day3, day4, day5, day6, day7, status FROM timesheets LEFT JOIN workcentres ON timesheets.workcentre=workcentres.code WHERE employeeid ='" . $SelectedEmployee . "' AND weekending ='" . FormatDateForSQL($_POST['WeekEnding']) ."'");
	while ($TSRow = DB_fetch_array($TSRes)) {
		$row_total = $TSRow['day1']+$TSRow['day2']+$TSRow['day3']+$TSRow['day4']+$TSRow['day5']+$TSRow['day6']+$TSRow['day7'];
		echo '<tr>';
		if ($TSRow['status'] == 2) { 
			echo '<td><div style="font-weight:700;">' . ($TSRow['wo']=='0' ? __('Non-chargable') : $TSRow['wo']) . '</div></td><td>' . $TSRow['workcentrename'] . '</td>';
			for($d=1;$d<=7;$d++){ echo '<td style="text-align:center; color:var(--text-muted);">' . locale_number_format($TSRow['day'.$d],$EmployeeRow['decimalplaces']) . '</td>'; $DayTotals[$d-1] += $TSRow['day'.$d]; }
			echo '<td style="text-align:right; font-weight:800;">' . locale_number_format($row_total,$EmployeeRow['decimalplaces']) . '</td><td><span class="aw-badge aw-badge-approved">'.__('Posted').'</span></td>';
			$PostedRowNo++;
		} else {
			echo '<td><input type="hidden" name="id_' . $EditableRowNo . '" value="' . $TSRow['id'] . '" /><div style="font-weight:800; color:var(--primary);">' . ($TSRow['wo']=='0' ? __('Non-chargable') : $TSRow['wo']) . '</div></td><td>' . $TSRow['workcentrename'] . '</td>';
			for($d=1;$d<=7;$d++){ echo '<td><input type="text" name="Day'.$d.'_'.$EditableRowNo.'" class="aw-input" style="text-align:center; padding:4px;" value="' . locale_number_format($TSRow['day'.$d],$EmployeeRow['decimalplaces']) . '" /></td>'; $DayTotals[$d-1] += $TSRow['day'.$d]; }
			echo '<td style="text-align:right; font-weight:800;">' . locale_number_format($row_total,$EmployeeRow['decimalplaces']) . '</td><td style="text-align:center;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?Delete=' . $TSRow['id'] . '&SelectedEmployee=' . $SelectedEmployee . '&WeekEnding=' . $_POST['WeekEnding']) . '" class="aw-btn-danger aw-btn-sm" onclick="return confirm(\'Delete this row?\');">&times;</a></td>';
			$EditableRowNo++;
		}
		echo '</tr>';
	}
	echo '<tr><td colspan="11" style="padding:0; height:1px; background:var(--border-color);"></td></tr>';
	echo '<tr style="background:#fbfcfd;">
			<td><select name="WO" class="aw-select" style="padding:4px;">';
				echo '<option value="0">' . __('Non-chargable') . '</option>';
				$WORes = DB_query("SELECT woitems.wo, stockmaster.description FROM workorders INNER JOIN woitems ON workorders.wo=woitems.wo INNER JOIN stockmaster ON stockmaster.stockid=woitems.stockid WHERE workorders.closed=0");
				while ($WOR = DB_fetch_array($WORes)) { echo '<option value="' . $WOR['wo'] . '">' . $WOR['wo'] . ' - ' . $WOR['description'] . '</option>'; }
	echo '	</select></td><td><select name="WorkCentre" class="aw-select" style="padding:4px;">';
				echo '<option value="0">N/A</option>';
				$WCRSQL = "SELECT code, description FROM workcentres " . ($EmployeeLocation!='' ? " WHERE location='" . $EmployeeLocation . "'" : "");
				$WCRRes = DB_query($WCRSQL);
				while ($WCR = DB_fetch_array($WCRRes)) { echo '<option value="' . $WCR['code'] . '">' . $WCR['description'] . '</option>'; }
	echo '	</select></td>';
			for($d=1;$d<=7;$d++){ echo '<td><input type="text" name="Day'.$d.'" class="aw-input" style="text-align:center; padding:4px; font-weight:800; border-color:var(--primary);" value="0" /></td>'; }
	echo '	<td colspan="2" style="text-align:right;"><button type="submit" name="Enter" class="aw-btn aw-btn-primary aw-btn-sm" style="width:100%;">' . __('Add Row') . '</button></td>
		  </tr>
		  <tr style="background:var(--primary-soft); font-weight:950;">
			<td colspan="2" style="text-align:right; font-size:0.7rem;">' . __('WEEKLY TOTALS') . '</td>';
			$week_total = 0; foreach($DayTotals as $dt){ echo '<td style="text-align:center;">' . locale_number_format($dt,$EmployeeRow['decimalplaces']) . '</td>'; $week_total += $dt; }
	echo '	<td style="text-align:right; color:var(--primary-dark);">' . locale_number_format($week_total,$EmployeeRow['decimalplaces']) . '</td><td></td>
		  </tr>';
	echo '</tbody></table></div>';
	if ($EditableRowNo > 0) { echo '<input type="hidden" name="Rows" value="' . $EditableRowNo . '" />'; }
	echo '</div></main>';

	// SIDEBAR
	echo '<aside class="aw-sidebar-side">
			<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Employee Status') . '</h3></div>
				<div class="aw-card-body">
					<div style="font-size:1.1rem; font-weight:950; color:var(--primary-dark);">' . $EmployeeRow['firstname'] . ' ' . $EmployeeRow['surname'] . '</div>
					<div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:12px;">' . $EmployeeRow['stockid'] . '</div>
					
					<div class="aw-stat-box" style="margin-bottom:12px;">
						<div style="display:flex; justify-content:space-between; align-items:flex-end;">
							<div><div class="aw-stat-label">Actual Hours</div><div class="aw-stat-val">' . locale_number_format($week_total, 1) . '</div></div>
							<div style="text-align:right;"><div class="aw-stat-label">Goal</div><div style="font-weight:800; font-size:0.9rem;">' . $EmployeeRow['normalhours'] . '</div></div>
						</div>
						<div style="width:100%; height:6px; background:#e2e8f0; border-radius:3px; margin-top:8px; overflow:hidden;">
							<div style="width:'.min(100, ($week_total/$EmployeeRow['normalhours']*100)).'%; height:100%; background:var(--primary);"></div>
						</div>
					</div>';
					
					if ($EditableRowNo > 0) {
						echo '<button type="submit" name="SubmitForApproval" class="aw-btn aw-btn-primary" style="width:100%; height:44px; font-weight:800;">' . __('Submit Timesheet') . '</button>';
						if (in_array(20, $_SESSION['AllowedPageSecurityTokens'])) {
							echo '<button type="submit" name="ApproveTimesheet" class="aw-btn aw-btn-success" style="width:100%; margin-top:8px;">' . __('Approve & Post') . '</button>';
						}
					}
echo '			</div>
			</div>
			<div class="aw-card">
				<div class="aw-card-body" style="font-size:0.75rem; color:var(--text-muted); line-height:1.5;">
					' . __('Submission is only permitted once goal hours are reached. Posting a timesheet will generate Work Order Issue logs (Type 28) and GL journals if integration is enabled.') . '
				</div>
			</div>';
			if (in_array(20, $_SESSION['AllowedPageSecurityTokens'])) {
				echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?SelectedEmployee=NewSelection" class="aw-btn aw-btn-secondary" style="width:100%">' . __('Switch Employee') . '</a>';
			}
echo '	  </aside>';
	echo '</div>'; // End aw-grid-layout
	echo '</form>';
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
