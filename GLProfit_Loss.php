<?php

if (!isset($IsIncluded)) {
	require(__DIR__ . '/includes/session.php');
}
use Dompdf\Dompdf;
include_once(__DIR__ . '/includes/SetDomPDFOptions.php');
include_once(__DIR__ . '/includes/SQL_CommonFunctions.php');
include_once(__DIR__ . '/includes/AccountSectionsDef.php');
include_once(__DIR__ . '/includes/CurrenciesArray.php');

$Title = __('Profit and Loss');
$ViewTopic = 'GeneralLedger';
$BookMark = 'ProfitAndLoss';

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	if (!isset($_POST['SelectedBudget'])) $_POST['SelectedBudget'] = 0;
	if (isset($_POST['Period']) and $_POST['Period'] != '') {
		$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
		$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
	}
	$NumberOfMonths = $_POST['PeriodTo'] - $_POST['PeriodFrom'] + 1;
	$PeriodToDate = MonthAndYearFromSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodTo']));
	$PeriodFromDate = MonthAndYearFromSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodFrom']));

    // 1. DATA PRE-CALCULATION (Refactored for KPI Cards)
    $SectionsData = array();
    $TotalRevenue = 0; $TotalCostOfSales = 0; $TotalExpenses = 0;
    $TotalRevenueBudget = 0; $TotalCostOfSalesBudget = 0; $TotalExpensesBudget = 0;
    $TotalRevenueLY = 0; $TotalCostOfSalesLY = 0; $TotalExpensesLY = 0;

    $AccountListResult = DB_query("SELECT sectionid, sectionname, parentgroupname, chartmaster.group_, chartmaster.accountcode, accountname, pandl 
                                   FROM chartmaster 
                                   INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 
                                   INNER JOIN accountgroups ON accountgroups.groupname=chartmaster.group_ 
                                   INNER JOIN accountsection ON accountsection.sectionid=accountgroups.sectioninaccounts 
                                   WHERE pandl=1 ORDER BY sequenceintb, group_, accountcode");
    
    $ThisYearRes = DB_query("SELECT account, SUM(amount) AS accounttotal FROM gltotals WHERE period>='" . $_POST['PeriodFrom'] . "' AND period<='" . $_POST['PeriodTo'] . "' GROUP BY account");
    while ($R = DB_fetch_array($ThisYearRes)) $ThisYearActuals[$R['account']] = $R['accounttotal'];
    
    $LastYearRes = DB_query("SELECT account, SUM(amount) AS accounttotal FROM gltotals WHERE period>='" . ($_POST['PeriodFrom'] - 12) . "' AND period<='" . ($_POST['PeriodTo'] - 12) . "' GROUP BY account");
    while ($R = DB_fetch_array($LastYearRes)) $LastYearActuals[$R['account']] = $R['accounttotal'];

    // Collect all data first to calculate KPI totals
    $FullReportData = array();
    while ($MyRow = DB_fetch_array($AccountListResult)) {
        $BudgetRes = DB_query("SELECT SUM(amount) AS periodbudget FROM glbudgetdetails WHERE account='" . $MyRow['accountcode'] . "' AND period>='" . $_POST['PeriodFrom'] . "' AND period<='" . $_POST['PeriodTo'] . "' AND headerid='" . $_POST['SelectedBudget'] . "'");
        $BudgetRow = DB_fetch_array($BudgetRes);
        
        $Actual = $ThisYearActuals[$MyRow['accountcode']] ?? 0;
        $Budget = $BudgetRow['periodbudget'] ?? 0;
        $LY = $LastYearActuals[$MyRow['accountcode']] ?? 0;

        $FullReportData[] = array(
            'sectionid' => $MyRow['sectionid'],
            'sectionname' => $MyRow['sectionname'],
            'group' => $MyRow['group_'],
            'parent' => $MyRow['parentgroupname'],
            'code' => $MyRow['accountcode'],
            'name' => $MyRow['accountname'],
            'actual' => $Actual,
            'budget' => $Budget,
            'ly' => $LY
        );

        // Accumulate for KPIs
        if ($MyRow['sectionid'] == 1) { // Revenue
            $TotalRevenue -= $Actual; $TotalRevenueBudget -= $Budget; $TotalRevenueLY -= $LY;
        } elseif ($MyRow['sectionid'] == 2) { // COGS
            $TotalCostOfSales += $Actual; $TotalCostOfSalesBudget += $Budget; $TotalCostOfSalesLY += $LY;
        } else { // Expenses
            $TotalExpenses += $Actual; $TotalExpensesBudget += $Budget; $TotalExpensesLY += $LY;
        }
    }

    $GrossProfit = $TotalRevenue - $TotalCostOfSales;
    $NetProfit = $GrossProfit - $TotalExpenses;
    $NetProfitBudget = ($TotalRevenueBudget - $TotalCostOfSalesBudget) - $TotalExpensesBudget;

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { 
        $HTML .= '<html><head><style>
            body { font-family: "Helvetica", sans-serif; color: #334155; }
            .report-header { text-align: center; margin-bottom: 20px; }
            .kpi-row { display: table; width: 100%; margin-bottom: 20px; border-spacing: 10px; }
            .kpi-card { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; width: 25%; }
            .kpi-label { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
            .kpi-value { font-size: 16px; font-weight: bold; color: hsl(145, 63%, 38%); }
            .report-table { width: 100%; border-collapse: collapse; font-size: 11px; }
            .report-table th { background: hsl(145, 45%, 22%); color: white; padding: 8px; text-align: left; }
            .report-table td { padding: 6px 8px; border-bottom: 1px solid #f1f5f9; }
            .section-header { background: hsl(145, 40%, 95%); font-weight: bold; }
            .group-header { font-weight: bold; color: hsl(145, 45%, 22%); }
            .text-right { text-align: right; }
            .variance-pos { color: #166534; font-weight: bold; }
            .variance-neg { color: #991b1b; font-weight: bold; }
        </style></head><body>'; 
    } else {
        $HTML .= '<div class="aw-report-container">';
    }

	$HTML .= '<div class="report-header">
                <h1 style="margin:0; font-size:1.8rem;">' . $_SESSION['CompanyRecord']['coyname'] . '</h1>
                <div style="font-weight:900; color:hsl(145, 63%, 38%); font-size:1.2rem; text-transform:uppercase;">' . $Title . '</div>
                <div style="color:#64748b; font-size:0.9rem;">' . $PeriodFromDate . ' ' . __('to') . ' ' . $PeriodToDate . '</div>
              </div>';

    // 2. EXECUTIVE KPI CARDS
    $HTML .= '<div class="kpi-row">';
    $HTML .= '<div class="kpi-card"><div class="kpi-label">' . __('Total Revenue') . '</div><div class="kpi-value">' . locale_number_format($TotalRevenue, 0) . '</div></div>';
    $HTML .= '<div class="kpi-card"><div class="kpi-label">' . __('Gross Profit') . '</div><div class="kpi-value">' . locale_number_format($GrossProfit, 0) . ' <small>(' . ($TotalRevenue > 0 ? round($GrossProfit*100/$TotalRevenue,1) : 0) . '%)</small></div></div>';
    $HTML .= '<div class="kpi-card"><div class="kpi-label">' . __('Total Expenses') . '</div><div class="kpi-value" style="color:#ef4444;">' . locale_number_format($TotalExpenses, 0) . '</div></div>';
    $HTML .= '<div class="kpi-card"><div class="kpi-label">' . __('Net Profit') . '</div><div class="kpi-value" style="color:'.($NetProfit < 0 ? '#ef4444' : 'hsl(145, 63%, 38%)').';">' . locale_number_format($NetProfit, 0) . '</div></div>';
    $HTML .= '</div>';

    // 3. MAIN DATA TABLE
    $HTML .= '<table class="report-table"><thead><tr>';
	if ($_POST['ShowDetail'] == 'Detailed') {
		$HTML .= '<th>' . __('Account') . '</th><th>' . __('Account Name') . '</th><th class="text-right">' . __('Actual') . '</th><th class="text-right">' . __('Budget') . '</th><th class="text-right">' . __('Var %') . '</th><th class="text-right">' . __('Last Year') . '</th>';
	} else {
		$HTML .= '<th colspan="2"></th><th class="text-right">' . __('Actual') . '</th><th class="text-right">' . __('Budget') . '</th><th class="text-right">' . __('Var %') . '</th><th class="text-right">' . __('Last Year') . '</th>';
	}
	$HTML .= '</tr></thead><tbody>';

    $CurrentSection = ''; $CurrentGroup = ''; $Level = 0;
    $SecActual = 0; $SecBudget = 0; $SecLY = 0;
    $GrpActual = array(0,0,0,0,0); $GrpBudget = array(0,0,0,0,0); $GrpLY = array(0,0,0,0,0); $GrpNames = array();

    foreach ($FullReportData as $row) {
        // Section Break
        if ($row['sectionid'] != $CurrentSection) {
            if ($CurrentSection != '') {
                // Show Section Total
                $mul = ($CurrentSection == 1 ? -1 : 1);
                $HTML .= '<tr class="section-header"><td colspan="2">' . __('Total') . ' ' . $Sections[$CurrentSection] . '</td><td class="text-right">' . locale_number_format($SecActual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td class="text-right">' . locale_number_format($SecBudget*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td><td class="text-right">' . locale_number_format($SecLY*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
                if ($CurrentSection == 2) {
                    $HTML .= '<tr style="background:#cbd5e1; font-weight:900;"><td colspan="2">' . __('Gross Profit') . '</td><td class="text-right">' . locale_number_format($GrossProfit, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td class="text-right">' . locale_number_format($TotalRevenueBudget - $TotalCostOfSalesBudget, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td><td class="text-right">' . locale_number_format($TotalRevenueLY - $TotalCostOfSalesLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
                }
            }
            $CurrentSection = $row['sectionid'];
            $SecActual = 0; $SecBudget = 0; $SecLY = 0;
            $HTML .= '<tr style="background:#f8fafc;"><td colspan="6" style="font-weight:900; text-transform:uppercase; color:hsl(145, 63%, 38%);">' . $row['sectionname'] . '</td></tr>';
        }

        // Grouping logic (simplified)
        if ($row['group'] != $CurrentGroup) {
            $CurrentGroup = $row['group'];
            if ($_POST['ShowDetail'] == 'Detailed') {
                $HTML .= '<tr><td colspan="6" style="font-weight:700; background:#f1f5f9;">' . $row['group'] . '</td></tr>';
            }
        }

        $Actual = $row['actual']; $Budget = $row['budget']; $LY = $row['ly'];
        $SecActual += $Actual; $SecBudget += $Budget; $SecLY += $LY;

        if ($_POST['ShowDetail'] == 'Detailed') {
            if (isset($_POST['ShowZeroBalance']) or ($Actual != 0 or $Budget != 0 or $LY != 0)) {
                $mul = ($row['sectionid'] == 1 ? -1 : 1);
                $Var = ($Budget != 0 ? round(($Actual - $Budget) / abs($Budget) * 100, 1) : 0);
                $VarClass = ($Var > 0 ? ($row['sectionid'] == 1 ? 'variance-pos' : 'variance-neg') : ($row['sectionid'] == 1 ? 'variance-neg' : 'variance-pos'));
                
                $HTML .= '<tr>
                    <td style="padding-left:20px;">' . $row['code'] . '</td>
                    <td>' . $row['name'] . '</td>
                    <td class="text-right">' . locale_number_format($Actual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                    <td class="text-right">' . locale_number_format($Budget*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                    <td class="text-right ' . $VarClass . '">' . ($Budget != 0 ? $Var . '%' : '-') . '</td>
                    <td class="text-right">' . locale_number_format($LY*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                </tr>';
            }
        }
    }

    // Final Section Total
    $mul = ($CurrentSection == 1 ? -1 : 1);
    $HTML .= '<tr class="section-header"><td colspan="2">' . __('Total') . ' ' . $Sections[$CurrentSection] . '</td><td class="text-right">' . locale_number_format($SecActual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td class="text-right">' . locale_number_format($SecBudget*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td><td class="text-right">' . locale_number_format($SecLY*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';

    // Grand Bottom Line
	$HTML .= '<tr style="background:hsl(145, 63%, 38%); color:white; font-weight:900;"><td colspan="2" style="font-size:1.1rem;">' . __('NET PROFIT / (LOSS)') . '</td><td class="text-right" style="font-size:1.1rem;">' . locale_number_format($NetProfit, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td class="text-right" style="font-size:1.1rem;">' . locale_number_format($NetProfitBudget, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td><td class="text-right" style="font-size:1.1rem;">' . locale_number_format($TotalRevenueLY - $TotalCostOfSalesLY - $TotalExpensesLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '</tbody></table>';
    
    if (isset($_POST['PrintPDF'])) { $HTML .= '</body></html>'; } else { $HTML .= '</div>'; }

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'portrait'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_Profit_Loss_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('Financial Statement View'); include(__DIR__ . '/includes/header.php');
		echo '<style>
            .aw-report-container { max-width: 1200px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .kpi-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-bottom: 2.5rem; }
            .kpi-card { background: var(--white); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-soft); box-shadow: var(--shadow); text-align: center; }
            .kpi-label { font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; }
            .kpi-value { font-size: 1.5rem; font-weight: 900; color: hsl(145, 63%, 38%); }
            .report-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
            .report-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; text-align: left; }
            .report-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-soft); }
            .section-header { background: var(--primary-soft); font-weight: 800; color: var(--primary-dark); }
            .variance-pos { color: #166534; font-weight: 700; }
            .variance-neg { color: #991b1b; font-weight: 700; }
        </style>';
		echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;">' . $HTML . '</div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
    // SETUP PAGE (Architect v3 Card)
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --primary: hsl(145, 63%, 38%); --primary-dark: hsl(145, 45%, 22%); --primary-soft: hsl(145, 40%, 95%); --bg: hsl(210, 20%, 97%); --border: #e2e8f0; }
        .aw-page { background: var(--bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; display: flex; align-items: flex-start; justify-content: center; }
        .aw-card { background: #fff; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 100%; max-width: 600px; overflow: hidden; }
        .aw-card-header { padding: 1.5rem; border-bottom: 1px solid var(--border); background: #fff; }
        .aw-card-title { font-size: 1rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; margin:0; display: flex; align-items: center; gap: 0.75rem; }
        .aw-card-body { padding: 2rem; }
        .aw-field { margin-bottom: 1.5rem; }
        .aw-label { font-size: 0.75rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
        .aw-select { padding: 0.75rem; border-radius: 8px; border: 1px solid var(--border); font-size: 0.9rem; width: 100%; transition: border-color 0.2s; }
        .aw-select:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px var(--primary-soft); }
        .aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.85rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 10px; }
        .aw-btn-primary { background: var(--primary); color: #fff; }
        .aw-btn-primary:hover { background: hsl(145, 63%, 32%); transform: translateY(-1px); }
    </style>';

    echo '<div class="aw-page"><div class="aw-card">
            <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-file-invoice-dollar"></i> ' . __('Profit and Loss Statement') . '</h3></div>
            <div class="aw-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <div class="aw-field"><label class="aw-label">' . __('From Period') . '</label><select name="PeriodFrom" class="aw-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="aw-field"><label class="aw-label">' . __('To Period') . '</label><select name="PeriodTo" class="aw-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>

                <div class="aw-field"><label class="aw-label">' . __('Budget Source') . '</label><select name="SelectedBudget" class="aw-select">';
                $Buds = DB_query("SELECT id, name FROM glbudgetheaders");
                while($R = DB_fetch_array($Buds)) echo '<option value="'.$R['id'].'">'.$R['name'].'</option>';
                echo '</select></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                    <div class="aw-field"><label class="aw-label">' . __('Detail Level') . '</label><select name="ShowDetail" class="aw-select"><option value="Summary">' . __('Summary Only') . '</option><option selected value="Detailed">' . __('Full Details') . '</option></select></div>
                    <div class="aw-field" style="display:flex; align-items:center; gap:10px; margin-top:1.5rem;"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label class="aw-label" for="szb" style="margin:0;">' . __('Show Zero Balances') . '</label></div>
                </div>

                <button type="submit" name="View" class="aw-btn aw-btn-primary"><i class="fas fa-eye" style="margin-right:8px;"></i> ' . __('View Statement Online') . '</button>
                <button type="submit" name="PrintPDF" class="aw-btn aw-btn-outline" style="margin-top:1rem;"><i class="fas fa-file-pdf" style="margin-right:8px; color:#ef4444;"></i> ' . __('Download Official PDF') . '</button>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
