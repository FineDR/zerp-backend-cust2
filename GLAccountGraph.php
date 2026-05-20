<?php

require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/GLFunctions.php');

$Title = __('GL Account Graph');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountGraph';
include(__DIR__ . '/includes/header.php');

$SelectedAccount = $_POST['Account'] ?? $_GET['Account'] ?? '';
if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

$NewReport = '';
if (isset($_POST['PeriodFrom']) and isset($_POST['PeriodTo'])) {
	if ($_POST['PeriodFrom'] > $_POST['PeriodTo']) { prnMsg(__('Invalid period range'), 'error'); $NewReport = 'on'; }
}

echo '<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 700px; margin: 0 auto; overflow: hidden; }
    .db-card-header { padding: 1.25rem; border-bottom: 1px solid var(--db-border); }
    .db-card-title { font-size: 0.85rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
    .db-card-body { padding: 1.5rem; }
    .db-field { margin-bottom: 1.25rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
    .db-select, .db-input { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.85rem; width: 100%; background:#fdfdfd; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 15px; }
    .db-btn-primary { background: var(--db-primary); color: #fff; }
    .graph-img { width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    @media print { .noPrint { display: none; } }
</style>';

if ((!isset($_POST['PeriodFrom']) or !isset($_POST['PeriodTo'])) or $NewReport == 'on') {
    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Analytics & Graphing Criteria') . '</h3></div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div class="db-field"><label class="db-label">Target GL Account</label><select name="Account" class="db-select">';
                $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode = chartmaster.accountcode AND glaccountusers.userid = '" . $_SESSION['UserID'] . "' AND glaccountusers.canview = 1 ORDER BY chartmaster.accountcode";
                $Res = DB_query($SQL);
                while ($R = DB_fetch_array($Res)) echo '<option '.($R['accountcode']==$SelectedAccount?'selected':'').' value="'.$R['accountcode'].'">'.$R['accountcode'].' - '.$R['accountname'].'</option>';
                echo '</select></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Graph Style</label><select name="GraphType" class="db-select"><option value="bars">Bar Chart</option><option value="lines">Line Chart</option><option value="area">Area Chart</option><option value="pie">Pie Chart</option></select></div>
                    <div class="db-field"><label class="db-label">Data Metric</label><select name="DisplayType" class="db-select"><option value="variation">Periodic Variation</option><option value="value">Cumulative Balance</option></select></div>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">From Period</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno");
                    while ($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">To Period</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while ($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>

                <div style="display:flex; align-items:center; gap:10px; margin-bottom:1rem;"><input type="checkbox" name="InvertGraph" id="ig" /> <label class="db-label" for="ig" style="margin:0;">Invert Graph Direction</label></div>

                <button type="submit" name="ShowGraph" class="db-btn db-btn-primary">Visualize GL Data</button>
                </form>
            </div>
        </div></div>';

} else {
	$AccountName = GetGLAccountName($SelectedAccount);
	$GraphTitle = $AccountName . ' ' . ($_POST['DisplayType'] == 'value' ? __('Account Value') : __('Transactions'));
    
    $PRes = DB_query("SELECT YEAR(lastdate_in_period) as y, MONTHNAME(lastdate_in_period) as m FROM periods WHERE periodno IN ('".$_POST['PeriodFrom']."','".$_POST['PeriodTo']."')");
    $P1 = DB_fetch_array($PRes); $P2 = DB_fetch_array($PRes);
    $GraphTitle .= "\n" . __('From') . ' ' . $P1['m'] . ' ' . $P1['y'] . ' ' . __('to') . ' ' . $P2['m'] . ' ' . $P2['y'];

	if ($_POST['DisplayType'] == 'value') {
		$SQL = "SELECT p.periodno, p.lastdate_in_period, (SELECT SUM(amount) FROM gltotals WHERE account = '" . $SelectedAccount . "' AND period <= p.periodno) AS val FROM periods p WHERE p.periodno >= '" . $_POST['PeriodFrom'] . "' AND p.periodno <= '" . $_POST['PeriodTo'] . "' ORDER BY p.periodno";
		$DataCol = 'val'; $Leg = __('Value');
	} else {
		$SQL = "SELECT periods.periodno, periods.lastdate_in_period, COALESCE(gltotals.amount, 0) AS val FROM periods LEFT JOIN gltotals ON periods.periodno = gltotals.period AND gltotals.account = '" . $SelectedAccount . "' WHERE periods.periodno >= '" . $_POST['PeriodFrom'] . "' AND periods.periodno <= '" . $_POST['PeriodTo'] . "' ORDER BY periods.periodno";
		$DataCol = 'val'; $Leg = __('Actual');
	}

	$Graph = new Phplot\Phplot\phplot(1200,600);
	$Graph->SetTitle($GraphTitle);
	$Graph->SetOutputFile('companies/' . $_SESSION['DatabaseName'] . '/reports/glaccountgraph.png');
	$Graph->SetXTitle(__('Period')); $Graph->SetXLabelAngle(90); $Graph->SetBackgroundColor('white'); $Graph->SetPlotType($_POST['GraphType']);
	$Graph->SetIsInline('1'); $Graph->SetDataType('text-data'); $Graph->SetNumberFormat($DecimalPoint, $ThousandsSeparator);

	$Res = DB_query($SQL);
	if (DB_num_rows($Res) == 0) { prnMsg(__('No data found'), 'info'); include(__DIR__ . '/includes/footer.php'); exit(); }

	$GraphArray = array(); $i = 0;
	while ($MyRow = DB_fetch_array($Res)) {
		$Val = isset($_POST['InvertGraph']) ? -$MyRow[$DataCol] : $MyRow[$DataCol];
		$GraphArray[$i++] = array(MonthAndYearFromSQLDate($MyRow['lastdate_in_period']), $Val);
	}
	$Graph->SetDataValues($GraphArray); $Graph->SetDataColors(array('hsl(197, 92%, 47%)'), array('black')); $Graph->SetLegend(array($Leg));
	$Graph->DrawGraph();

    echo '<div class="db-page"><div class="db-card" style="max-width:1100px;">
            <div class="db-card-header"><h3 class="db-card-title">' . $AccountName . ' ' . __('Performance Visualization') . '</h3></div>
            <div class="db-card-body" style="text-align:center;">
                <img class="graph-img" src="companies/' . $_SESSION['DatabaseName'] . '/reports/glaccountgraph.png" alt="Graph" />
                <div class="noPrint" style="margin-top:2rem;">
                    <a href="'.basename(__FILE__).'" class="db-btn db-btn-primary" style="text-decoration:none; width:auto;">Configure New View</a>
                </div>
            </div>
        </div></div>';
}

include(__DIR__ . '/includes/footer.php');
?>
