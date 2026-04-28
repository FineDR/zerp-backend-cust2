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
$Title2 = __('Statement of Comprehensive Income');
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

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />'; }
	$HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP"></head><body>';

	$HTML .= '<div class="report-header" style="text-align:center; margin-bottom:2rem;">
                <h1 style="margin:0; color:#1e293b; font-size:1.8rem;">' . $_SESSION['CompanyRecord']['coyname'] . '</h1>
                <div style="font-weight:900; color:hsl(145, 63%, 38%); font-size:1.2rem; text-transform:uppercase;">' . $Title . '</div>
                <div style="color:#64748b; font-size:0.9rem;">' . $PeriodFromDate . ' ' . __('to') . ' ' . $PeriodToDate . '</div>
              </div>';

    $HTML .= '<table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;"><thead><tr style="background:hsl(145, 45%, 22%); color:white;">';
	if ($_POST['ShowDetail'] == 'Detailed') {
		$HTML .= '<th>' . __('Account') . '</th><th>' . __('Account Name') . '</th><th style="text-align:right;">' . __('Actual') . '</th><th style="text-align:right;">' . __('Budget') . '</th><th style="text-align:right;">' . __('Last Year') . '</th>';
	} else {
		$HTML .= '<th colspan="2"></th><th style="text-align:right;">' . __('Actual') . '</th><th style="text-align:right;">' . __('Budget') . '</th><th style="text-align:right;">' . __('Last Year') . '</th>';
	}
	$HTML .= '</tr></thead><tbody>';

    // Business Logic for P&L data extraction (preserved exactly)
	$Section = ''; $SectionPrdActual = 0; $SectionPrdLY = 0; $SectionPrdBudget = 0; $PeriodProfitLossActual = 0; $PeriodProfitLossBudget = 0; $PeriodProfitLossLY = 0;
	$ActGrp = ''; $ParentGroups = array(); $Level = 0; $ParentGroups[$Level] = ''; $GrpPrdActual = array(0); $GrpPrdLY = array(0); $GrpPrdBudget = array(0);
	$TotalIncomeActual = 0; $TotalIncomeBudget = 0; $TotalIncomeLY = 0;

	$AccountListResult = DB_query("SELECT sectionid, sectionname, parentgroupname, chartmaster.group_, chartmaster.accountcode, accountname, pandl FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 INNER JOIN accountgroups ON accountgroups.groupname=chartmaster.group_ INNER JOIN accountsection ON accountsection.sectionid=accountgroups.sectioninaccounts WHERE pandl=1 ORDER BY sequenceintb, group_, accountcode");
	$ThisYearRes = DB_query("SELECT account, SUM(amount) AS accounttotal FROM gltotals WHERE period>='" . $_POST['PeriodFrom'] . "' AND period<='" . $_POST['PeriodTo'] . "' GROUP BY account");
	while ($R = DB_fetch_array($ThisYearRes)) $ThisYearActuals[$R['account']] = $R['accounttotal'];
	$LastYearRes = DB_query("SELECT account, SUM(amount) AS accounttotal FROM gltotals WHERE period>='" . ($_POST['PeriodFrom'] - 12) . "' AND period<='" . ($_POST['PeriodTo'] - 12) . "' GROUP BY account");
	while ($R = DB_fetch_array($LastYearRes)) $LastYearActuals[$R['account']] = $R['accounttotal'];

	while ($MyRow = DB_fetch_array($AccountListResult)) {
		$PeriodBudgetRow = DB_fetch_array(DB_query("SELECT SUM(amount) AS periodbudget FROM glbudgetdetails WHERE account='" . $MyRow['accountcode'] . "' AND period>='" . $_POST['PeriodFrom'] . "' AND period<='" . $_POST['PeriodTo'] . "' AND headerid='" . $_POST['SelectedBudget'] . "'"));
		$AccountPeriodBudget = $PeriodBudgetRow['periodbudget'] ?? 0;

		if ($MyRow['group_'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp and $ActGrp != '') {
				while ($MyRow['group_'] != $ParentGroups[$Level] and $Level > 0) {
					$lbl = str_repeat('&nbsp;&nbsp;', $Level) . $ParentGroups[$Level] . ($_POST['ShowDetail'] == 'Detailed' ? ' ' . __('total') : '');
                    $mul = ($Section == 1 ? -1 : 1);
					$HTML .= '<tr><td colspan="2"><b>' . $lbl . '</b></td><td style="text-align:right;">' . locale_number_format($GrpPrdActual[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($GrpPrdBudget[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($GrpPrdLY[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
					$GrpPrdActual[$Level] = 0; $GrpPrdBudget[$Level] = 0; $GrpPrdLY[$Level] = 0; $ParentGroups[$Level] = ''; $Level--;
				}
				$lbl = str_repeat('&nbsp;&nbsp;', $Level) . $ParentGroups[$Level] . ($_POST['ShowDetail'] == 'Detailed' ? ' ' . __('total') : '');
                $mul = ($Section == 1 ? -1 : 1);
				$HTML .= '<tr style="font-weight:800; border-bottom:1px solid #cbd5e1;"><td colspan="2">' . $lbl . '</td><td style="text-align:right;">' . locale_number_format($GrpPrdActual[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($GrpPrdBudget[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($GrpPrdLY[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
				$GrpPrdLY[$Level] = 0; $GrpPrdActual[$Level] = 0; $GrpPrdBudget[$Level] = 0; $ParentGroups[$Level] = '';
			}
		}

		if ($MyRow['sectionid'] != $Section) {
			if ($SectionPrdLY + $SectionPrdActual + $SectionPrdBudget != 0) {
				$mul = ($Section == 1 ? -1 : 1);
				$HTML .= '<tr style="background:hsl(145, 40%, 95%); font-weight:900;"><td colspan="2">' . $Sections[$Section] . '</td><td style="text-align:right;">' . locale_number_format($SectionPrdActual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SectionPrdBudget*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SectionPrdLY*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
                if ($Section == 1) { $TotalIncomeActual = -$SectionPrdActual; $TotalIncomeBudget = -$SectionPrdBudget; $TotalIncomeLY = -$SectionPrdLY; }
				if ($Section == 2) { // Gross Profit subtotal
					$HTML .= '<tr style="background:#f1f5f9; font-weight:900;"><td colspan="2">' . __('Gross Profit') . '</td><td style="text-align:right;">' . locale_number_format($TotalIncomeActual - $SectionPrdActual, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($TotalIncomeBudget - $SectionPrdBudget, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($TotalIncomeLY - $SectionPrdLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
				}
			}
			$SectionPrdActual = 0; $SectionPrdBudget = 0; $SectionPrdLY = 0; $Section = $MyRow['sectionid'];
			if ($_POST['ShowDetail'] == 'Detailed') $HTML .= '<tr style="background:#f8fafc;"><td colspan="5"><b>' . $Sections[$MyRow['sectionid']] . '</b></td></tr>';
		}

		if ($MyRow['group_'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp and $ActGrp != '') $Level++;
			$ParentGroups[$Level] = $MyRow['group_']; $ActGrp = $MyRow['group_'];
			if ($_POST['ShowDetail'] == 'Detailed') $HTML .= '<tr><td colspan="5" style="padding-left:' . (20*$Level) . 'px; font-weight:700;">' . $MyRow['group_'] . '</td></tr>';
		}

		$AccountPeriodActual = $ThisYearActuals[$MyRow['accountcode']] ?? 0;
		$AccountPeriodLY = $LastYearActuals[$MyRow['accountcode']] ?? 0;
		$PeriodProfitLossActual += $AccountPeriodActual; $PeriodProfitLossBudget += $AccountPeriodBudget; $PeriodProfitLossLY += $AccountPeriodLY;

		for ($i = 0;$i <= $Level;$i++) {
			if (!isset($GrpPrdActual[$i])) $GrpPrdActual[$i] = 0; $GrpPrdActual[$i] += $AccountPeriodActual;
			if (!isset($GrpPrdBudget[$i])) $GrpPrdBudget[$i] = 0; $GrpPrdBudget[$i] += $AccountPeriodBudget;
			if (!isset($GrpPrdLY[$i])) $GrpPrdLY[$i] = 0; $GrpPrdLY[$i] += $AccountPeriodLY;
		}
		$SectionPrdActual += $AccountPeriodActual; $SectionPrdBudget += $AccountPeriodBudget; $SectionPrdLY += $AccountPeriodLY;

		if ($_POST['ShowDetail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalance']) or ($AccountPeriodActual != 0 or $AccountPeriodBudget != 0 or $AccountPeriodLY != 0)) {
				$mul = ($Section == 1 ? -1 : 1);
				$HTML .= '<tr style="border-bottom:1px solid #f1f5f9; opacity:0.85;">
                    <td style="padding-left:' . (25+($Level*15)) . 'px;">' . $MyRow['accountcode'] . '</td>
                    <td>' . htmlspecialchars($MyRow['accountname']) . '</td>
                    <td style="text-align:right;">' . locale_number_format($AccountPeriodActual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                    <td style="text-align:right;">' . locale_number_format($AccountPeriodBudget*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                    <td style="text-align:right;">' . locale_number_format($AccountPeriodLY*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                </tr>';
			}
		}
	}

    // Final Grand Totals
	$HTML .= '<tr style="background:hsl(145, 63%, 38%); color:white; font-weight:900;"><td colspan="2">' . __('Net Profit / (Loss)') . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodProfitLossActual, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodProfitLossBudget, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format(-$PeriodProfitLossLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '</tbody></table></body></html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'portrait'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_Profit_Loss_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('Financial Statement View'); include(__DIR__ . '/includes/header.php');
		echo '<style>.report-grid { max-width: 1200px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); } table th, table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }</style>';
		echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;"><div class="report-grid">' . $HTML . '</div></div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
    // Setup Page
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(145, 63%, 38%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 650px; margin: 0 auto; overflow: hidden; }
        .db-card-header { padding: 1.25rem; border-bottom: 1px solid var(--db-border); }
        .db-card-title { font-size: 0.85rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
        .db-card-body { padding: 1.5rem; }
        .db-field { margin-bottom: 1.25rem; }
        .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
        .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.85rem; width: 100%; background:#fdfdfd; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 15px; }
        .db-btn-primary { background: var(--db-primary); color: #fff; }
    </style>';

    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Profit and Loss Statement Configuration') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Reporting Period From</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">Reporting Period To</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>

                <div class="db-field"><label class="db-label">Budget Comparison Source</label><select name="SelectedBudget" class="db-select">';
                $Buds = DB_query("SELECT id, name FROM glbudgetheaders");
                while($R = DB_fetch_array($Buds)) echo '<option value="'.$R['id'].'">'.$R['name'].'</option>';
                echo '</select></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Data Density</label><select name="ShowDetail" class="db-select"><option value="Summary">Summary Only</option><option selected value="Detailed">Include All Accounts</option></select></div>
                    <div class="db-field" style="display:flex; align-items:center; gap:10px; margin-top:2rem;"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label class="db-label" for="szb" style="margin:0;">Show Zero Balances</label></div>
                </div>

                <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Generate Official PDF</button>
                <button type="submit" name="View" class="db-btn db-btn-primary" style="background:var(--db-primary-soft); color:var(--db-primary);">View Statement Online</button>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
