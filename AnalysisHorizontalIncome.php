<?php

require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/GLFunctions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/AccountSectionsDef.php');

$Title = __('Horizontal Analysis: Income Statement');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AnalysisHorizontalIncome';
include(__DIR__ . '/includes/header.php');

// Merges gets into posts:
foreach(array('PeriodFrom','PeriodTo','Period','ShowDetail','ShowZeroBalance','NewReport') as $f) if(isset($_GET[$f])) $_POST[$f] = $_GET[$f];

if (isset($_POST['PeriodFrom']) and ($_POST['PeriodFrom'] > $_POST['PeriodTo'])) { prnMsg(__('Invalid period range'), 'error'); $_POST['NewReport'] = 'on'; }
if (isset($_POST['Period']) and $_POST['Period'] != '') { $_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From'); $_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To'); }

echo '<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); }
    .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.5rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
    .db-select { padding: 0.5rem; border-radius: 6px; border: 1px solid var(--db-border); width: 100%; font-size: 0.85rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; width: 100%; margin-top: 15px; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    .report-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .report-table th { background: hsl(197, 75%, 22%); color: white; padding: 10px; text-align: left; font-size: 0.7rem; text-transform: uppercase; }
    .report-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; }
    .change-pos { color: hsl(197, 92%, 47%); font-weight: 800; }
    .change-neg { color: #dc2626; font-weight: 800; }
    @media print { .noPrint { display: none; } .db-page { padding: 0; background: white; } }
</style>';

if ((isset($_POST['PeriodFrom']) and isset($_POST['PeriodTo'])) and (!isset($_POST['NewReport']) OR $_POST['NewReport']!='on')) {
    $NumberOfMonths = $_POST['PeriodTo'] - $_POST['PeriodFrom'] + 1;
	if ($NumberOfMonths > 12) { prnMsg(__('Select max 12 months'), 'error'); include(__DIR__ . '/includes/footer.php'); exit(); }
    $PeriodToDate = MonthAndYearFromSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodTo']));

    echo '<div class="db-page"><div class="db-card" style="max-width:1200px; margin:0 auto;">';
    echo '<div style="text-align:center; padding: 2rem; border-bottom: 1px solid var(--db-border);">';
    echo '<h1 style="margin:0; font-size:1.5rem; color:#1e293b;">' . stripslashes($_SESSION['CompanyRecord']['coyname']) . '</h1>';
    echo '<h2 style="font-weight:900; color:var(--db-primary); margin:5px 0; font-size:1rem; text-transform:uppercase;">' . $Title . '</h2>';
    echo '<div style="color:#64748b; font-size:0.85rem;">' . __('Months to') . ' ' . $PeriodToDate . '</div>';
    echo '</div>';

    echo '<div class="db-card-body" style="padding:0;"><table class="report-table"><thead><tr>';
    if ($_POST['ShowDetail'] == 'Detailed') echo '<th>' . __('Account') . '</th><th>' . __('Description') . '</th>';
    else echo '<th colspan="2">' . __('Summary View') . '</th>';
    echo '<th style="text-align:right;">Current</th><th style="text-align:right;">Last Year</th><th style="text-align:right;">Actual Var</th><th style="text-align:right;">% Var</th></tr></thead><tbody>';

	$SQL = "SELECT accountgroups.sectioninaccounts, accountgroups.parentgroupname, accountgroups.groupname, chartmaster.accountcode, chartmaster.accountname, SUM(CASE WHEN gltotals.period >= '" . $_POST['PeriodFrom'] . "' AND gltotals.period <= '" . $_POST['PeriodTo'] . "' THEN gltotals.amount ELSE 0 END) AS PeriodActual, SUM(CASE WHEN gltotals.period >= '" . ($_POST['PeriodFrom'] - 12) . "' AND gltotals.period <= '" . ($_POST['PeriodTo'] - 12) . "' THEN gltotals.amount ELSE 0 END) AS LYPeriodActual FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname INNER JOIN gltotals ON chartmaster.accountcode = gltotals.account INNER JOIN glaccountusers ON glaccountusers.accountcode = chartmaster.accountcode AND glaccountusers.userid = '" .  $_SESSION['UserID'] . "' AND glaccountusers.canview = 1 WHERE accountgroups.pandl = 1 GROUP BY accountgroups.sectioninaccounts, accountgroups.parentgroupname, accountgroups.groupname, chartmaster.accountcode, chartmaster.accountname ORDER BY accountgroups.sectioninaccounts, accountgroups.sequenceintb, accountgroups.groupname, chartmaster.accountcode";
	$Res = DB_query($SQL);

	$Section = ''; $SectionTotal = 0; $SectionTotalLY = 0; $PeriodTotal = 0; $PeriodTotalLY = 0; $ActGrp = ''; $Level = 0; $GrpTotal = array(0); $GrpTotalLY = array(0); $ParentGroups = array();

	while ($MyRow = DB_fetch_array($Res)) {
		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp AND $ActGrp != '') {
				while ($MyRow['groupname'] != $ParentGroups[$Level] AND $Level > 0) {
					$lbl = str_repeat('&nbsp;', $Level*4) . $ParentGroups[$Level];
					echo '<tr style="font-weight:700; background:#f8fafc;"><td colspan="2">' . $lbl . ' Subtotal</td><td style="text-align:right;">' . locale_number_format(-$GrpTotal[$Level], 2) . '</td><td style="text-align:right;">' . locale_number_format(-$GrpTotalLY[$Level], 2) . '</td><td style="text-align:right;">' . locale_number_format(-$GrpTotal[$Level]+$GrpTotalLY[$Level], 2) . '</td><td style="text-align:right;">' . RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]) . '</td></tr>';
					$GrpTotal[$Level] = 0; $GrpTotalLY[$Level] = 0; $Level--;
				}
                $lbl = str_repeat('&nbsp;', $Level*4) . $ParentGroups[$Level];
				echo '<tr style="font-weight:800; background:#f1f5f9;"><td colspan="2">' . $lbl . ' Total</td><td style="text-align:right;">' . locale_number_format(-$GrpTotal[$Level], 2) . '</td><td style="text-align:right;">' . locale_number_format(-$GrpTotalLY[$Level], 2) . '</td><td style="text-align:right;">' . locale_number_format(-$GrpTotal[$Level]+$GrpTotalLY[$Level], 2) . '</td><td style="text-align:right;">' . RelativeChange(-$GrpTotal[$Level], -$GrpTotalLY[$Level]) . '</td></tr>';
				$GrpTotalLY[$Level] = 0; $GrpTotal[$Level] = 0; $ParentGroups[$Level] = '';
			}
		}
		if ($MyRow['sectioninaccounts'] != $Section) {
			if ($SectionTotal != 0 OR $SectionTotalLY != 0) {
				echo '<tr style="background:hsl(197, 65%, 95%); font-weight:900;"><td colspan="2">' . $Sections[$Section] . ' Total</td><td style="text-align:right;">' . locale_number_format(-$SectionTotal, 2) . '</td><td style="text-align:right;">' . locale_number_format(-$SectionTotalLY, 2) . '</td><td style="text-align:right;">' . locale_number_format(-$SectionTotal+$SectionTotalLY, 2) . '</td><td style="text-align:right;">' . RelativeChange(-$SectionTotal, -$SectionTotalLY) . '</td></tr>';
				if ($Section == 1) { $GPInc = $SectionTotal; $GPIncLY = $SectionTotalLY; }
				if ($Section == 2) echo '<tr style="background:#e2e8f0; font-weight:900;"><td colspan="2">' . __('Gross Profit') . '</td><td style="text-align:right;">' . locale_number_format(-($GPInc + $SectionTotal), 2) . '</td><td style="text-align:right;">' . locale_number_format(-($GPIncLY + $SectionTotalLY), 2) . '</td><td style="text-align:right;">' . locale_number_format(-($GPInc + $SectionTotal) + ($GPIncLY + $SectionTotalLY), 2) . '</td><td style="text-align:right;">' . RelativeChange(-($GPInc + $SectionTotal), -($GPIncLY + $SectionTotalLY)) . '</td></tr>';
			}
			$Section = $MyRow['sectioninaccounts']; $SectionTotal = 0; $SectionTotalLY = 0;
			if ($_POST['ShowDetail'] == 'Detailed') echo '<tr class="section-header"><td colspan="6">' . $Sections[$Section] . '</td></tr>';
		}
		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp AND $ActGrp != '') $Level++;
			$ActGrp = $MyRow['groupname']; $ParentGroups[$Level] = $MyRow['groupname'];
			if ($_POST['ShowDetail'] == 'Detailed') echo '<tr><td colspan="6" style="padding-left:' . (20*$Level) . 'px; font-weight:700; color:var(--db-primary-dark);">' . $ActGrp . '</td></tr>';
		}
		$AccAct = $MyRow['PeriodActual']; $AccLY = $MyRow['LYPeriodActual'];
		for ($i = 0; $i <= $Level; $i++) { if(!isset($GrpTotal[$i]))$GrpTotal[$i]=0; $GrpTotal[$i]+=$AccAct; if(!isset($GrpTotalLY[$i]))$GrpTotalLY[$i]=0; $GrpTotalLY[$i]+=$AccLY; }
		$SectionTotal += $AccAct; $SectionTotalLY += $AccLY; $PeriodTotal += $AccAct; $PeriodTotalLY += $AccLY;

		if ($_POST['ShowDetail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalance']) OR $AccAct != 0 OR $AccLY != 0) {
				$var = -$AccAct + $AccLY; $rel = RelativeChange(-$AccAct, -$AccLY);
				echo '<tr><td style="padding-left:' . (25+($Level*15)) . 'px;">' . $MyRow['accountcode'] . '</td><td>' . htmlspecialchars($MyRow['accountname']) . '</td><td style="text-align:right;">' . locale_number_format(-$AccAct, 2) . '</td><td style="text-align:right;">' . locale_number_format(-$AccLY, 2) . '</td><td style="text-align:right;' . ($var < 0 ? 'color:#dc2626;' : 'color:hsl(197, 92%, 47%);') . ' font-weight:600;">' . locale_number_format($var, 2) . '</td><td style="text-align:right;">' . $rel . '</td></tr>';
			}
		}
	}
	echo '<tr style="background:var(--db-primary); color:white; font-weight:900;">' . '<td colspan="2">' . __('NET PROFIT') . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodTotal, 2) . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodTotalLY, 2) . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodTotal + $PeriodTotalLY, 2) . '</td><td style="text-align:right;">' . RelativeChange(-$PeriodTotal, -$PeriodTotalLY) . '</td></tr>';
	echo '</tbody></table></div></div>';
    echo '<div class="noPrint" style="display:flex; justify-content:center; gap:15px; margin-top:2rem;">
            <button type="button" class="db-btn" style="width:auto; background:var(--db-primary); color:white;" onclick="window.print()">Print Analysis</button>
            <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <button type="submit" name="NewReport" value="on" class="db-btn" style="width:auto; background:#f1f5f9; color:#475569;">New Analysis</button>
            </form>
          </div></div>';

} else {
    echo '<div class="db-page"><div class="db-card" style="max-width:600px; margin:0 auto;">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Horizontal Trend Analysis') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">Start Period</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">End Period</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>
                <div class="db-field"><label class="db-label">Report Detail</label><select name="ShowDetail" class="db-select"><option value="Detailed">Include G/L Accounts</option><option value="Summary">Group Totals Only</option></select></div>
                <div class="checkbox-item" style="margin-bottom:1rem; display:flex; align-items:center; gap:8px;"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label class="db-label" for="szb" style="margin:0;">Show Zero Balances</label></div>
                <button type="submit" class="db-btn db-btn-primary">Generate Trend Analysis</button>
                </form>
            </div>
        </div></div>';
}

include(__DIR__ . '/includes/footer.php');
?>
