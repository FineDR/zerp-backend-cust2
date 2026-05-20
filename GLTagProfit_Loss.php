<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/AccountSectionsDef.php');

$Title = __('Income and Expenditure by Tag');
$ViewTopic = 'GeneralLedger';
$BookMark = 'TagReports';

if (isset($_POST['PeriodFrom']) AND ($_POST['PeriodFrom'] > $_POST['PeriodTo'])) {
	prnMsg(__('Invalid period range'), 'error'); $_POST['NewReport'] = 'on';
}
if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$NumberOfMonths = $_POST['PeriodTo'] - $_POST['PeriodFrom'] + 1;
	if ($NumberOfMonths > 12) {
		prnMsg(__('Max duration is 12 months'), 'error'); include(__DIR__ . '/includes/footer.php'); exit();
	}
	$PeriodToDate = MonthAndYearFromSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodTo']));
	$TagInfo = DB_fetch_row(DB_query("SELECT tagdescription FROM tags WHERE tagref='" . $_POST['tag'] . "'"));

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>'; }

	$HTML .= '<div class="report-header" style="text-align:center; margin-bottom:2rem;">
                <h1 style="margin:0; color:#1e293b;">' . $_SESSION['CompanyRecord']['coyname'] . '</h1>
                <div style="font-weight:900; color:hsl(197, 92%, 47%); font-size:1.1rem; text-transform:uppercase;">' . $Title . '</div>
                <div style="color:#64748b; font-size:0.85rem; margin-top:5px;"><b>Tag:</b> ' . $_POST['tag'] . ' - ' . $TagInfo[0] . ' | <b>Period:</b> ' . $NumberOfMonths . ' months to ' . $PeriodToDate . '</div>
              </div>';

	$AccountList = DB_query("SELECT accountgroups.sectioninaccounts, accountgroups.groupname, accountgroups.parentgroupname, gltrans.account, chartmaster.accountname, Sum(CASE WHEN (gltrans.periodno>='" . $_POST['PeriodFrom'] . "' AND gltrans.periodno<='" . $_POST['PeriodTo'] . "') THEN gltrans.amount ELSE 0 END) AS TotalAllPeriods FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname INNER JOIN gltrans ON chartmaster.accountcode= gltrans.account INNER JOIN gltags ON gltags.counterindex=gltrans.counterindex WHERE accountgroups.pandl=1 AND gltags.tagref='" . $_POST['tag'] . "' GROUP BY accountgroups.sectioninaccounts, accountgroups.groupname, accountgroups.parentgroupname, gltrans.account, chartmaster.accountname ORDER BY accountgroups.sectioninaccounts, accountgroups.sequenceintb, accountgroups.groupname, gltrans.account");

	$HTML .= '<table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;"><thead><tr style="background:hsl(197, 75%, 22%); color:white;">';
	if ($_POST['Detail'] == 'Detailed') { $HTML .= '<th>' . __('Account') . '</th><th>' . __('Account Name') . '</th><th style="text-align:right;">' . __('Period Actual') . '</th>'; }
	else { $HTML .= '<th colspan="2"></th><th style="text-align:right;">' . __('Period Actual') . '</th>'; }
	$HTML .= '</tr></thead><tbody>';

	$Section = ''; $SectionPrdActual = 0; $PeriodProfitLoss = 0; $ActGrp = ''; $ParentGroups = array(); $Level = 0; $ParentGroups[$Level] = ''; $GrpPrdActual = array(0); $TotalIncome = 0;

	while ($MyRow = DB_fetch_array($AccountList)) {
		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] != $ActGrp AND $ActGrp != '') {
				while ($MyRow['groupname'] != $ParentGroups[$Level] AND $Level > 0) {
					$lbl = str_repeat('&nbsp;&nbsp;', $Level) . $ParentGroups[$Level] . ($_POST['Detail'] == 'Detailed' ? ' ' . __('total') : '');
                    $mul = ($Section == 4 ? -1 : 1);
					$HTML .= '<tr style="font-style:italic;"><td>' . $lbl . '</td><td></td><td style="text-align:right;">' . locale_number_format($GrpPrdActual[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
					$GrpPrdActual[$Level] = 0; $ParentGroups[$Level] = ''; $Level--;
				}
				$lbl = str_repeat('&nbsp;&nbsp;', $Level) . $ParentGroups[$Level] . ($_POST['Detail'] == 'Detailed' ? ' ' . __('total') : '');
                $mul = ($Section == 4 ? -1 : 1);
				$HTML .= '<tr style="font-weight:800; border-bottom:1px solid #cbd5e1;"><td>' . $lbl . '</td><td></td><td style="text-align:right;">' . locale_number_format($GrpPrdActual[$Level]*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
				$GrpPrdActual[$Level] = 0; $ParentGroups[$Level] = '';
			}
		}

		if ($MyRow['sectioninaccounts'] != $Section) {
			if ($SectionPrdActual != 0) {
                $mul = ($Section == 4 ? 1 : -1);
				$HTML .= '<tr style="background:hsl(197, 65%, 95%); font-weight:900;"><td>' . $Sections[$Section] . '</td><td></td><td style="text-align:right;">' . locale_number_format($SectionPrdActual*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
                if ($Section == 1) $TotalIncome = $SectionPrdActual;
				if ($Section == 2) { // Gross Profit
					$HTML .= '<tr style="background:#f1f5f9; font-weight:900;"><td>' . __('Gross Profit') . '</td><td></td><td style="text-align:right;">' . locale_number_format(($TotalIncome - $SectionPrdActual), $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
				}
			}
			$SectionPrdActual = 0; $Section = $MyRow['sectioninaccounts'];
			if ($_POST['Detail'] == 'Detailed') $HTML .= '<tr style="background:#f8fafc;"><td colspan="3"><b>' . $Sections[$MyRow['sectioninaccounts']] . '</b></td></tr>';
		}

		if ($MyRow['groupname'] != $ActGrp) {
			if ($MyRow['parentgroupname'] == $ActGrp AND $ActGrp != '') $Level++;
			$ParentGroups[$Level] = $MyRow['groupname']; $ActGrp = $MyRow['groupname'];
			if ($_POST['Detail'] == 'Detailed') $HTML .= '<tr><td colspan="3" style="padding-left:' . (20*$Level) . 'px; font-weight:700;">' . $MyRow['groupname'] . '</td></tr>';
		}

		$AccAct = $MyRow['TotalAllPeriods']; $PeriodProfitLoss -= $AccAct;
		for ($i = 0;$i <= $Level;$i++) { if (!isset($GrpPrdActual[$i])) $GrpPrdActual[$i] = 0; $GrpPrdActual[$i] += $AccAct; }
		$SectionPrdActual -= $AccAct;

		if ($_POST['Detail'] == 'Detailed') {
			$mul = ($Section == 4 ? -1 : 1);
			$HTML .= '<tr style="border-bottom:1px solid #f1f5f9; opacity:0.85;">
                <td style="padding-left:' . (25+($Level*15)) . 'px;">' . $MyRow['account'] . '</td>
                <td>' . htmlspecialchars($MyRow['accountname']) . '</td>
                <td style="text-align:right;">' . locale_number_format($AccAct*$mul, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
            </tr>';
		}
	}
	$HTML .= '<tr style="background:hsl(197, 92%, 47%); color:white; font-weight:900;"><td colspan="2">' . __('Surplus / (Deficit)') . '</td><td style="text-align:right;">' . locale_number_format($PeriodProfitLoss, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '</tbody></table>';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</body></html>';
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'portrait'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_TagReport_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('Financial Statement by Tag'); include(__DIR__ . '/includes/header.php');
		echo '<style>.report-grid { max-width: 1000px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); } table th, table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }</style>';
		echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;"><div class="report-grid">' . $HTML . '</div></div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(197, 92%, 47%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; overflow: hidden; }
        .db-card-header { padding: 1.25rem; border-bottom: 1px solid var(--db-border); }
        .db-card-title { font-size: 0.85rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
        .db-card-body { padding: 1.5rem; }
        .db-field { margin-bottom: 1.25rem; }
        .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
        .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.85rem; width: 100%; background:#fdfdfd; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 15px; }
        .db-btn-primary { background: var(--db-primary); color: #fff; }
    </style>';

    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Report by Accounting Tag') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div class="db-field"><label class="db-label">Accounting Tag</label><select name="tag" class="db-select">';
                $Tags = DB_query("SELECT tagref, tagdescription FROM tags ORDER BY tagref");
                while($R = DB_fetch_array($Tags)) echo '<option value="'.$R['tagref'].'">'.$R['tagref'].' - '.$R['tagdescription'].'</option>';
                echo '</select></div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Duration</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">To Period</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>

                <div class="db-field"><label class="db-label">Inquiry Depth</label><select name="Detail" class="db-select"><option value="Summary">Group Summary</option><option selected value="Detailed">G/L Account Detail</option></select></div>

                <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Generate PDF Report</button>
                <button type="submit" name="View" class="db-btn db-btn-primary" style="background:var(--db-primary-soft); color:var(--db-primary);">View Online Inquiry</button>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
