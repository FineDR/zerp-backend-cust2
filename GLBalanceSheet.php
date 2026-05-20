<?php

if (!isset($IsIncluded)) {
	require(__DIR__ . '/includes/session.php');
}
use Dompdf\Dompdf;
include_once(__DIR__ . '/includes/SetDomPDFOptions.php');
include_once(__DIR__ . '/includes/SQL_CommonFunctions.php');
include_once(__DIR__ . '/includes/AccountSectionsDef.php');
include_once(__DIR__ . '/includes/CurrenciesArray.php');

$Title = __('Balance Sheet');
$Title2 = __('Statement of Financial Position');
$ViewTopic = 'GeneralLedger';
$BookMark = 'BalanceSheet';

if (isset($_GET['PeriodTo'])) $_POST['PeriodTo'] = $_GET['PeriodTo'];
if (isset($_GET['ShowDetail'])) $_POST['ShowDetail'] = $_GET['ShowDetail'];
if (isset($_GET['ShowZeroBalance'])) $_POST['ShowZeroBalance'] = $_GET['ShowZeroBalance'];

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];
	$BalanceDate = ConvertSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodTo']));

	$ThisYearRetainedEarningsRow = DB_fetch_array(DB_query("SELECT ROUND(SUM(amount), " . $_SESSION['CompanyRecord']['decimalplaces'] . " +1) AS retainedearnings FROM gltotals INNER JOIN chartmaster ON gltotals.account=chartmaster.accountcode INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE period<='" . $_POST['PeriodTo'] . "' AND pandl=1"));
	$LastYearRetainedEarningsRow = DB_fetch_array(DB_query("SELECT ROUND(SUM(amount), " . $_SESSION['CompanyRecord']['decimalplaces'] . " +1) AS retainedearnings FROM gltotals INNER JOIN chartmaster ON gltotals.account=chartmaster.accountcode INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE period<='" . ($_POST['PeriodTo'] - 12) . "' AND pandl=1"));

	$AccountListResult = DB_query("SELECT sectionid, sectionname, sectioninaccounts, parentgroupname, chartmaster.accountcode, group_, accountname, pandl FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 INNER JOIN accountgroups ON accountgroups.groupname=chartmaster.group_ INNER JOIN accountsection ON accountsection.sectionid=accountgroups.sectioninaccounts WHERE pandl=0 ORDER BY sequenceintb, group_, accountcode");
	
    $ResultActual = DB_query("SELECT account, ROUND(SUM(amount), " . $_SESSION['CompanyRecord']['decimalplaces'] . " +1) AS accounttotal FROM gltotals WHERE period<='" . $_POST['PeriodTo'] . "' GROUP BY account");
	while ($R = DB_fetch_array($ResultActual)) $ThisYearActuals[$R['account']] = $R['accounttotal'];
	$ResultLY = DB_query("SELECT account, ROUND(SUM(amount), " . $_SESSION['CompanyRecord']['decimalplaces'] . " +1) AS accounttotal FROM gltotals WHERE period<='" . ($_POST['PeriodTo'] - 12) . "' GROUP BY account");
	while ($R = DB_fetch_array($ResultLY)) $LastYearActuals[$R['account']] = $R['accounttotal'];

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />'; }
	$HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP"></head><body>';

	$HTML .= '<div class="report-header" style="text-align:center; margin-bottom:2rem;">
                <h1 style="margin:0; color:#1e293b; font-size:1.8rem;">' . $_SESSION['CompanyRecord']['coyname'] . '</h1>
                <div style="font-weight:900; color:hsl(197, 92%, 47%); font-size:1.2rem; text-transform:uppercase;">' . $Title . '</div>
                <div style="color:#64748b; font-size:0.9rem;">' . __('As at') . ' ' . $BalanceDate . '</div>
              </div>';

	$HTML .= '<table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;"><thead><tr style="background:hsl(197, 75%, 22%); color:white;">';
	if ($_POST['ShowDetail'] == 'Detailed') {
		$HTML .= '<th>' . __('Account') . '</th><th>' . __('Account Name') . '</th><th style="text-align:right;">' . $BalanceDate . '</th><th style="text-align:right;">' . __('Last Year') . '</th>';
	} else {
		$HTML .= '<th colspan="2"></th><th style="text-align:right;">' . $BalanceDate . '</th><th style="text-align:right;">' . __('Last Year') . '</th>';
	}
	$HTML .= '</tr></thead><tbody>';

	$Section = ''; $SectionBalance = 0; $SectionBalanceLY = 0; $LYCheckTotal = 0; $CheckTotal = 0; $ActGrp = ''; $Level = 0; $ParentGroups = array(); $ParentGroups[$Level] = ''; $GroupTotal = array(0); $LYGroupTotal = array(0);

	while ($MyRow = DB_fetch_array($AccountListResult)) {
		$AccountBalance = $ThisYearActuals[$MyRow['accountcode']] ?? 0;
		$LYAccountBalance = $LastYearActuals[$MyRow['accountcode']] ?? 0;
		if ($MyRow['accountcode'] == $RetainedEarningsAct) { $AccountBalance = $ThisYearRetainedEarningsRow['retainedearnings']; $LYAccountBalance = $LastYearRetainedEarningsRow['retainedearnings']; }

		if ($MyRow['group_'] != $ActGrp and $ActGrp != '') {
			if ($MyRow['parentgroupname'] != $ActGrp) {
				while ($MyRow['group_'] != $ParentGroups[$Level] and $Level > 0) {
					$lbl = str_repeat('&nbsp;&nbsp;', $Level) . $ParentGroups[$Level];
					$HTML .= '<tr><td colspan="2"><i>' . $lbl . '</i></td><td style="text-align:right;">' . locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
					$GroupTotal[$Level] = 0; $LYGroupTotal[$Level] = 0; $ParentGroups[$Level] = ''; $Level--;
				}
				$HTML .= '<tr style="font-weight:800; border-bottom:1px solid #cbd5e1;"><td colspan="2">' . $ParentGroups[$Level] . '</td><td style="text-align:right;">' . locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
				$GroupTotal[$Level] = 0; $LYGroupTotal[$Level] = 0; $ParentGroups[$Level] = '';
			}
		}

		if ($MyRow['sectionid'] != $Section) {
			if ($Section != '') {
				$HTML .= '<tr style="background:hsl(197, 65%, 95%); font-weight:900;"><td colspan="2">' . $Sections[$Section] . '</td><td style="text-align:right;">' . locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
			}
			$SectionBalanceLY = 0; $SectionBalance = 0; $Section = $MyRow['sectionid'];
			if ($_POST['ShowDetail'] == 'Detailed') $HTML .= '<tr style="background:#f8fafc;"><td colspan="4"><b>' . $Sections[$MyRow['sectionid']] . '</b></td></tr>';
		}

		if ($MyRow['group_'] != $ActGrp) {
			if ($ActGrp != '' and $MyRow['parentgroupname'] == $ActGrp) $Level++;
			$ActGrp = $MyRow['group_']; $ParentGroups[$Level] = $MyRow['group_'];
			if ($_POST['ShowDetail'] == 'Detailed') $HTML .= '<tr><td colspan="4" style="padding-left:' . (20*$Level) . 'px; font-weight:700;">' . $MyRow['group_'] . '</td></tr>';
		}

		$SectionBalanceLY+= $LYAccountBalance; $SectionBalance+= $AccountBalance;
		for ($i = 0;$i <= $Level;$i++) { $LYGroupTotal[$i]+= $LYAccountBalance; $GroupTotal[$i]+= $AccountBalance; }
		$LYCheckTotal+= $LYAccountBalance; $CheckTotal+= $AccountBalance;

		if ($_POST['ShowDetail'] == 'Detailed') {
			if (isset($_POST['ShowZeroBalance']) or (round($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) != 0 or round($LYAccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) != 0)) {
				$HTML .= '<tr style="border-bottom:1px solid #f1f5f9; opacity:0.85;">
                    <td style="padding-left:' . (25+($Level*15)) . 'px;">' . $MyRow['accountcode'] . '</td>
                    <td>' . htmlspecialchars($MyRow['accountname']) . '</td>
                    <td style="text-align:right;">' . locale_number_format($AccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                    <td style="text-align:right;">' . locale_number_format($LYAccountBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                </tr>';
			}
		}
		$Group = $MyRow['group_']; $SectionInAccounts = $MyRow['sectioninaccounts'];
	}
	
	while ($Group != $ParentGroups[$Level] and $Level > 0) {
		$HTML .= '<tr><td colspan="2"><i>' . $ParentGroups[$Level] . '</i></td><td style="text-align:right;">' . locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
		$Level--;
	}
	$HTML .= '<tr style="font-weight:800; border-bottom:1px solid #cbd5e1;"><td colspan="2">' . $ParentGroups[$Level] . '</td><td style="text-align:right;">' . locale_number_format($GroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($LYGroupTotal[$Level], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '<tr style="background:hsl(197, 65%, 95%); font-weight:900;"><td colspan="2">' . $Sections[$Section] . '</td><td style="text-align:right;">' . locale_number_format($SectionBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SectionBalanceLY, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '<tr style="background:hsl(197, 92%, 47%); color:white; font-weight:900;"><td colspan="2">' . __('Check Total') . '</td><td style="text-align:right;">' . locale_number_format($CheckTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($LYCheckTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
	$HTML .= '</tbody></table></body></html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'portrait'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_Balance_Sheet_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('Financial Statement View'); include(__DIR__ . '/includes/header.php');
		echo '<style>.report-grid { max-width: 1100px; margin: 0 auto; background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); } table th, table td { padding: 10px; border-bottom: 1px solid #f1f5f9; }</style>';
		echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;"><div class="report-grid">' . $HTML . '</div></div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(197, 92%, 47%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
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
            <div class="db-card-header"><h3 class="db-card-title">' . __('Balance Sheet Generator') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-field"><label class="db-label">Balance As At Date</label><select name="PeriodTo" class="db-select">';
                $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                while($R = DB_fetch_array($Pers)) echo '<option value="'.$R['periodno'].'">'.ConvertSQLDate($R['lastdate_in_period']).'</option>';
                echo '</select></div>
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Report Type</label><select name="ShowDetail" class="db-select"><option value="Summary">Summary Only</option><option selected value="Detailed">Detailed Trial Balance</option></select></div>
                    <div class="db-field" style="display:flex; align-items:center; gap:10px; margin-top:2rem;"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label class="db-label" for="szb" style="margin:0;">Show Zero Balances</label></div>
                </div>

                <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Generate Official PDF</button>
                <button type="submit" name="View" class="db-btn db-btn-primary" style="background:var(--db-primary-soft); color:var(--db-primary);">View Balance Sheet Online</button>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
