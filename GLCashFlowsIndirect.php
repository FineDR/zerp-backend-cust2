<?php

if (!isset($IsIncluded)) {
	require(__DIR__ . '/includes/session.php');
}

$Title = __('Statement of Cash Flows (Indirect Method)');
if (!isset($IsIncluded)) {
	$ViewTopic = 'GeneralLedger';
	$BookMark = 'GLCashFlowsIndirect';
	include(__DIR__ . '/includes/header.php');
}
include(__DIR__ . '/includes/GLFunctions.php');

// Helper for UI columns
function colUI($val) {
    if (round($val, 2) == 0) return '<td></td><td style="text-align:right; color:#cbd5e1;">-</td>';
    if ($val < 0) return '<td style="text-align:right; color:#dc2626; font-weight:600;">(' . locale_number_format(abs($val), $_SESSION['CompanyRecord']['decimalplaces']) . ')</td><td></td>';
    return '<td></td><td style="text-align:right; color:hsl(145, 63%, 38%); font-weight:600;">' . locale_number_format($val, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
}

if (isset($_GET['PeriodFrom'])) $_POST['PeriodFrom'] = $_GET['PeriodFrom'];
if (isset($_GET['PeriodTo'])) $_POST['PeriodTo'] = $_GET['PeriodTo'];
if (isset($_GET['ShowZeroBalance'])) $_POST['ShowZeroBalance'] = $_GET['ShowZeroBalance'];
if (isset($_GET['ShowCash'])) $_POST['ShowCash'] = $_GET['ShowCash'];

if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

if (isset($_POST['PeriodFrom']) and isset($_POST['PeriodTo']) and !isset($_POST['NewReport'])) {
    
    $PFN = EndDateSQLFromPeriodNo($_POST['PeriodFrom']); $PTN = EndDateSQLFromPeriodNo($_POST['PeriodTo']);
    
    echo '<style>
        :root { --db-primary: hsl(145, 63%, 38%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .report-wrapper { max-width: 1200px; margin: 0 auto; background: white; padding: 3rem; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .report-title { text-align: center; margin-bottom: 2.5rem; }
        .report-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
        .report-table th { background: hsl(145, 45%, 22%); color: white; padding: 12px; text-align: left; }
        .report-table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .section-header { background: #f8fafc; font-weight: 900; text-transform: uppercase; font-size: 0.75rem; color: #475569; }
        .total-row { font-weight: 800; border-top: 2px solid #334155 !important; }
        @media print { .noPrint { display: none; } }
    </style>';

    echo '<div style="background:var(--db-bg); padding:2rem; min-height:100vh;"><div class="report-wrapper">';
    echo '<div class="report-title">
            <h1 style="margin:0; font-size:1.75rem; color:#1e293b;">' . stripslashes($_SESSION['CompanyRecord']['coyname']) . '</h1>
            <h2 style="margin:5px 0; color:var(--db-primary); text-transform:uppercase; font-size:1.1rem; letter-spacing:1px;">' . $Title . '</h2>
            <div style="color:#64748b; font-size:0.9rem;">Period: ' . MonthAndYearFromSQLDate($PFN) . ' to ' . MonthAndYearFromSQLDate($PTN) . '</div>
          </div>';

    echo '<table class="report-table">
            <thead>
                <tr><th>Statement Line</th><th>#</th><th style="text-align:right;">Actual Outflow</th><th style="text-align:right;">Actual Inflow</th><th style="text-align:right;">LY Outflow</th><th style="text-align:right;">LY Inflow</th></tr>
            </thead><tbody>';

    $RetEarnAct = $_SESSION['CompanyRecord']['retainedearnings'];
    $ProfAct = DB_fetch_row(DB_query("SELECT confvalue FROM config WHERE confname ='PeriodProfitAccount'"))[0];

	$MyRow1 = DB_fetch_array(DB_query("SELECT Sum(CASE WHEN (period >= '" . $_POST['PeriodFrom'] . "' AND period <= '" . $_POST['PeriodTo'] . "') THEN -amount ELSE 0 END) AS ActualProfit, Sum(CASE WHEN (period >= '" . ($_POST['PeriodFrom']-12) . "' AND period <= '" . ($_POST['PeriodTo']-12) . "') THEN -amount ELSE 0 END) AS LastProfit FROM gltotals INNER JOIN chartmaster ON chartmaster.accountcode=gltotals.account INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1"));
	$MyRow2 = DB_fetch_array(DB_query("SELECT Sum(CASE WHEN (period >= '" . $_POST['PeriodFrom'] . "' AND period <= '" . $_POST['PeriodTo'] . "') THEN amount ELSE 0 END) AS ActualRetained, Sum(CASE WHEN (period >= '" . ($_POST['PeriodFrom']-12) . "' AND period <= '" . ($_POST['PeriodTo']-12) . "') THEN amount ELSE 0 END) AS LastRetained FROM gltotals INNER JOIN chartmaster ON chartmaster.accountcode = gltotals.account INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname WHERE accountgroups.pandl=0 AND gltotals.account != '" . $ProfAct . "' AND gltotals.account != '" . $RetEarnAct . "'"));

	echo '<tr class="section-header"><td colspan="6">' . __('Net Profit and Dividends') . '</td></tr>';
	echo '<tr><td></td><td>' . __('Net profit for the period') . '</td>' . colUI($MyRow1['ActualProfit']) . colUI($MyRow1['LastProfit']) . '</tr>';
	echo '<tr><td></td><td>' . __('Dividends Paid') . '</td>' . colUI($MyRow2['ActualRetained'] - $MyRow1['ActualProfit']) . colUI($MyRow2['LastRetained'] - $MyRow1['LastProfit']) . '</tr>';
	echo '<tr class="total-row"><td></td><td>' . __('Retained Earnings Flow') . '</td>' . colUI($MyRow2['ActualRetained']) . colUI($MyRow2['LastRetained']) . '</tr>';

    $ActualTotal = $MyRow2['ActualRetained']; $LastTotal = $MyRow2['LastRetained']; $ActualSection = 0; $LastSection = 0;

	$ResFlows = DB_query("SELECT chartmaster.cashflowsactivity, gltotals.account, chartmaster.accountname, Sum(CASE WHEN (gltotals.period >= '" . $_POST['PeriodFrom'] . "' AND gltotals.period <= '" . $_POST['PeriodTo'] . "') THEN -gltotals.amount ELSE 0 END) AS ActualAmount, Sum(CASE WHEN (gltotals.period >= '" . ($_POST['PeriodFrom']-12) . "' AND gltotals.period <= '" . ($_POST['PeriodTo']-12) . "') THEN -gltotals.amount ELSE 0 END) AS LastAmount FROM chartmaster INNER JOIN gltotals ON chartmaster.accountcode=gltotals.account INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 AND chartmaster.cashflowsactivity!=4 GROUP BY gltotals.account, chartmaster.accountname, chartmaster.cashflowsactivity ORDER BY chartmaster.cashflowsactivity, gltotals.account");
	
    $IdSection = -1;
	while($MyRow = DB_fetch_array($ResFlows)) {
		if ($IdSection != $MyRow['cashflowsactivity']) {
			if ($IdSection != -1) echo '<tr class="total-row"><td></td><td>' . CashFlowsActivityName($IdSection) . ' Subtotal</td>' . colUI($ActualSection) . colUI($LastSection) . '</tr>';
			$ActualSection = 0; $LastSection = 0; $IdSection = $MyRow['cashflowsactivity'];
			echo '<tr class="section-header"><td colspan="6">' . CashFlowsActivityName($IdSection) . '</td></tr>';
		}
		if ($MyRow['ActualAmount']!=0 OR $MyRow['LastAmount']!=0 OR (isset($_POST['ShowZeroBalance']) && $_POST['ShowZeroBalance'])) {
			echo '<tr><td>' . $MyRow['account'] . '</td><td>' . $MyRow['accountname'] . '</td>' . colUI($MyRow['ActualAmount']) . colUI($MyRow['LastAmount']) . '</tr>';
			$ActualSection += $MyRow['ActualAmount']; $ActualTotal += $MyRow['ActualAmount']; $LastSection += $MyRow['LastAmount']; $LastTotal += $MyRow['LastAmount'];
		}
	}
	echo '<tr class="total-row"><td></td><td>' . CashFlowsActivityName($IdSection) . ' Subtotal</td>' . colUI($ActualSection) . colUI($LastSection) . '</tr>';

	echo '<tr style="background:#f1f5f9; font-weight:900; font-size:1rem; border-top:3px double #334155;"><td></td><td>' . __('NET INCREASE IN CASH') . '</td>' . colUI($ActualTotal) . colUI($LastTotal) . '</tr>';

    $ActBeg = DB_fetch_array(DB_query("SELECT Sum(CASE WHEN (period < '" . $_POST['PeriodFrom'] . "') THEN amount ELSE 0 END) AS Actual, Sum(CASE WHEN (period < '" . ($_POST['PeriodFrom']-12) . "') THEN amount ELSE 0 END) AS Last FROM gltotals INNER JOIN chartmaster ON chartmaster.accountcode=gltotals.account INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 AND chartmaster.cashflowsactivity=4"));
	echo '<tr><td></td><td>' . __('Cash at Beginning of Period') . '</td>' . colUI($ActBeg['Actual']) . colUI($ActBeg['Last']) . '</tr>';
	echo '<tr class="total-row" style="background:var(--db-primary); color:white;"><td></td><td>' . __('CASH AT END OF PERIOD') . '</td>' . colUI($ActualTotal + $ActBeg['Actual']) . colUI($LastTotal + $ActBeg['Last']) . '</tr>';

	echo '</tbody></table>';
    echo '<div style="margin-top:2rem; padding:1.5rem; background:#f8fafc; border-radius:8px; font-size:0.75rem; color:#64748b;">
            <b>Notes:</b> Positive numbers indicate cash inflow; negative bracketed numbers indicate cash outflow.
          </div>';
    
    if (!isset($IsIncluded)) {
        echo '<div class="noPrint" style="display:flex; justify-content:center; gap:15px; margin-top:2rem;">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <button type="button" class="db-btn" style="background:var(--db-primary); color:white; padding:0.6rem 1.5rem; border-radius:8px; border:none; cursor:pointer;" onclick="window.print()">Print Statement</button>
                <button type="submit" name="NewReport" value="on" style="background:#f1f5f9; color:#475569; padding:0.6rem 1.5rem; border-radius:8px; border:none; cursor:pointer;">Change Criteria</button>
                </form>
              </div>';
    }
    echo '</div></div>';

} else {
	echo '<style>
        :root { --db-primary: hsl(145, 63%, 38%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; overflow: hidden; }
        .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); }
        .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
        .db-card-body { padding: 1.5rem; }
        .db-field { margin-bottom: 1.25rem; }
        .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
        .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.8rem; width: 100%; background:#fdfdfd; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 10px; }
        .db-btn-primary { background: var(--db-primary); color: #fff; }
    </style>';

    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Cash Flow Statement (Indirect Method)') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">Period From</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">Period To</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>
                
                <div class="checkbox-grid" style="display:flex; gap:20px; margin-bottom:1rem;">
                    <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label class="db-label" for="szb" style="margin:0;">Show Zero Bal</label></div>
                    <div style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="ShowCash" id="sc" checked /> <label class="db-label" for="sc" style="margin:0;">Show Cash Accounts</label></div>
                </div>

                <button type="submit" name="View" class="db-btn db-btn-primary">Generate Cash Flow Statement</button>
                </form>
            </div>
        </div></div>';
}

if (!isset($IsIncluded)) include(__DIR__ . '/includes/footer.php');
?>
