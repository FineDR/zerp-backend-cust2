<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$ViewTopic = 'AccountsPayable';
$BookMark = 'AgedCreditors';

if (isset($_POST['PrintPDF']) or isset($_POST['View'])
	and isset($_POST['FromCriteria'])
	and mb_strlen($_POST['FromCriteria'])>=1
	and isset($_POST['ToCriteria'])
	and mb_strlen($_POST['ToCriteria'])>=1){

	/*Now figure out the aged analysis for the Supplier range under review */

	if ($_POST['All_Or_Overdues']=='All'){
		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						currencies.decimalplaces AS currdecimalplaces,
						paymentterms.terms,
						SUM(supptrans.balance) as balance,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue THEN supptrans.balance ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN supptrans.balance ELSE 0 END
						END) AS due,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.balance ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN supptrans.balance ELSE 0 END
						END) AS overdue1,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.balance ELSE 0 END
						ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN supptrans.balance ELSE 0 END
						END) AS overdue2
				FROM suppliers INNER JOIN paymentterms
				ON suppliers.paymentterms = paymentterms.termsindicator
				INNER JOIN currencies
				ON suppliers.currcode = currencies.currabrev
				INNER JOIN supptrans
				ON suppliers.supplierid = supptrans.supplierno
				WHERE suppliers.supplierid >= '" . $_POST['FromCriteria'] . "'
				AND suppliers.supplierid <= '" . $_POST['ToCriteria'] . "'
				AND  suppliers.currcode ='" . $_POST['Currency'] . "'
				GROUP BY suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						paymentterms.terms,
						paymentterms.daysbeforedue,
						paymentterms.dayinfollowingmonth
				HAVING ROUND(ABS(SUM(supptrans.balance)), currencies.decimalplaces) > " . CurrencyTolerance($_POST['Currency']) . "";

	} else {

		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						currencies.decimalplaces AS currdecimalplaces,
						paymentterms.terms,
						SUM(supptrans.balance) AS balance,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue  THEN supptrans.balance ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN supptrans.balance ELSE 0 END
						END) AS due,
						Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.balance ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN supptrans.balance ELSE 0 END
						END) AS overdue1,
						SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.balance ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN supptrans.balance ELSE 0 END
						END) AS overdue2
				FROM suppliers INNER JOIN paymentterms
				ON suppliers.paymentterms = paymentterms.termsindicator
				INNER JOIN currencies
				ON suppliers.currcode = currencies.currabrev
				INNER JOIN supptrans
				ON suppliers.supplierid = supptrans.supplierno
				WHERE suppliers.supplierid >= '" . $_POST['FromCriteria'] . "'
				AND suppliers.supplierid <= '" . $_POST['ToCriteria'] . "'
				AND suppliers.currcode ='" . $_POST['Currency'] . "'
				GROUP BY suppliers.supplierid,
						suppliers.suppname,
						currencies.currency,
						paymentterms.terms,
						paymentterms.daysbeforedue,
						paymentterms.dayinfollowingmonth
				HAVING SUM(IF (paymentterms.daysbeforedue > 0,
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.balance ELSE 0 END,
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN supptrans.balance ELSE 0 END)) > 0";

	}

	$SupplierResult = DB_query($SQL, '', '', false, false); /*dont trap errors */


	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Aged Supplier Balances For Suppliers from') . ' ' . $_POST['FromCriteria'] . ' ' . __('to') . ' ' . $_POST['ToCriteria'] . '<br />
					' . __('And Trading in') . ' ' . $_POST['Currency'] . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>';

	$HTML .= '<table>
					<thead>
						<tr>
							<th>' . __('Supplier') . '</th>
							<th>' . __('Balance') . '</th>
							<th>' . __('Current') . '</th>
							<th>' . __('Due Now') . '</th>
							<th>' . $_SESSION['PastDueDays1'] . ' ' . __('Days Over') . '</th>
							<th>' . $_SESSION['PastDueDays2'] . ' ' . __('Days Over') . '</th>
						</tr>
					</thead>
					<tbody>';

	$TotBal = 0;
	$TotDue = 0;
	$TotCurr = 0;
	$TotOD1 = 0;
	$TotOD2 = 0;
	$CurrDecimalPlaces =0;

	$ListCount = DB_num_rows($SupplierResult); // UldisN

	while ($AgedAnalysis = DB_fetch_array($SupplierResult)){

		$CurrDecimalPlaces = $AgedAnalysis['currdecimalplaces'];

		$DisplayDue = locale_number_format($AgedAnalysis['due']-$AgedAnalysis['overdue1'],$CurrDecimalPlaces);
		$DisplayCurrent = locale_number_format($AgedAnalysis['balance']-$AgedAnalysis['due'],$CurrDecimalPlaces);
		$DisplayBalance = locale_number_format($AgedAnalysis['balance'],$CurrDecimalPlaces);
		$DisplayOverdue1 = locale_number_format($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2'],$CurrDecimalPlaces);
		$DisplayOverdue2 = locale_number_format($AgedAnalysis['overdue2'],$CurrDecimalPlaces);

		$TotBal += $AgedAnalysis['balance'];
		$TotDue += ($AgedAnalysis['due']-$AgedAnalysis['overdue1']);
		$TotCurr += ($AgedAnalysis['balance']-$AgedAnalysis['due']);
		$TotOD1 += ($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2']);
		$TotOD2 += $AgedAnalysis['overdue2'];

		$HTML .= '<tr class="striped_row">
					<td>' . $AgedAnalysis['supplierid'] . ' - ' . $AgedAnalysis['suppname'] . '</td>
					<td class="number">' . $DisplayBalance . '</td>
					<td class="number">' . $DisplayCurrent . '</td>
					<td class="number">' . $DisplayDue . '</td>
					<td class="number">' . $DisplayOverdue1 . '</td>
					<td class="number">' . $DisplayOverdue2 . '</td>
				</tr>';

		if ($_POST['DetailedReport']=='Yes'){

		   $SQL = "SELECT systypes.typename,
							supptrans.suppreference,
							supptrans.trandate,
							(supptrans.balance) as balance,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue  THEN supptrans.balance ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN supptrans.balance ELSE 0 END
							END AS due,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN supptrans.balance ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN supptrans.balance ELSE 0 END
							END AS overdue1,
							CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN supptrans.balance ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN supptrans.balance ELSE 0 END
							END AS overdue2
						FROM suppliers
						LEFT JOIN paymentterms
							ON suppliers.paymentterms = paymentterms.termsindicator
						LEFT JOIN supptrans
							ON suppliers.supplierid = supptrans.supplierno
						LEFT JOIN systypes
							ON systypes.typeid = supptrans.type
						WHERE ABS(supptrans.balance) > " . CurrencyTolerance($_POST['Currency']) . "
							AND supptrans.settled = 0
							AND supptrans.supplierno = '" . $AgedAnalysis["supplierid"] . "'";

			$DetailResult = DB_query($SQL, '', '', false, false); /*dont trap errors - trapped below*/

			$HTML .= '<tr>
						<td colspan="6">
							<table>';

			while ($DetailTrans = DB_fetch_array($DetailResult)){

				$DisplayTranDate = ConvertSQLDate($DetailTrans['trandate']);
				$HTML .= '<tr>
							<th>' . $DetailTrans['typename'] . '</th>
							<th>' . $DetailTrans['suppreference'] . '</th>
							<th>' . $DisplayTranDate . '</th>
							<th></th>
							<th></th>
							<th></th>
						</tr>';

				$DisplayDue = locale_number_format($DetailTrans['due']-$DetailTrans['overdue1'],$CurrDecimalPlaces);
				$DisplayCurrent = locale_number_format($DetailTrans['balance']-$DetailTrans['due'],$CurrDecimalPlaces);
				$DisplayBalance = locale_number_format($DetailTrans['balance'],$CurrDecimalPlaces);
				$DisplayOverdue1 = locale_number_format($DetailTrans['overdue1']-$DetailTrans['overdue2'],$CurrDecimalPlaces);
				$DisplayOverdue2 = locale_number_format($DetailTrans['overdue2'],$CurrDecimalPlaces);

				$HTML .= '<tr class="striped_row">
							<td class="number">' . $DisplayBalance . '</td>
							<td class="number">' . $DisplayCurrent . '</td>
							<td class="number">' . $DisplayDue . '</td>
							<td class="number">' . $DisplayOverdue1 . '</td>
							<td class="number">' . $DisplayOverdue2 . '</td>
						</tr>';

			} /*end while there are detail transactions to show */
			$HTML .= '</table>
					</td>
				</tr>';
		} /*Its a detailed report */
	} /*end Supplier aged analysis while loop */

	$DisplayTotBalance = locale_number_format($TotBal,$CurrDecimalPlaces);
	$DisplayTotDue = locale_number_format($TotDue,$CurrDecimalPlaces);
	$DisplayTotCurrent = locale_number_format($TotCurr,$CurrDecimalPlaces);
	$DisplayTotOverdue1 = locale_number_format($TotOD1,$CurrDecimalPlaces);
	$DisplayTotOverdue2 = locale_number_format($TotOD2,$CurrDecimalPlaces);

	$HTML .= '<tr class="total_row">
				<td></td>
				<td class="number">' . $DisplayTotBalance . '</td>
				<td class="number">' . $DisplayTotCurrent . '</td>
				<td class="number">' . $DisplayTotDue . '</td>
				<td class="number">' . $DisplayTotOverdue1 . '</td>
				<td class="number">' . $DisplayTotOverdue2 . '</td>
			</tr>';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_AgedCreditors_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Aged Creditor Analysis');
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page">';
		echo '<div class="db-page-header">
				<div>
					<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> ' . $Title . '</h2>
					<p class="db-page-subtitle">' . __('Viewing aged balances from') . ' <span class="val-bold">' . $_POST['FromCriteria'] . '</span> ' . __('to') . ' <span class="val-bold">' . $_POST['ToCriteria'] . '</span></p>
				</div>
				<div class="db-header-actions">
					<button onclick="window.print()" class="db-btn db-btn-secondary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
						' . __('Print Report') . '
					</button>
				</div>
			</div>';
		
		echo '<div class="db-card" style="margin-top: var(--space-6);">
				<div class="db-card-header">
					<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Report Summary') . '</h3>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Supplier') . '</th>
								<th class="number">' . __('Total Balance') . '</th>
								<th class="number">' . __('Current') . '</th>
								<th class="number">' . __('Due Now') . '</th>
								<th class="number">' . $_SESSION['PastDueDays1'] . ' ' . __('Days+') . '</th>
								<th class="number">' . $_SESSION['PastDueDays2'] . ' ' . __('Days+') . '</th>
							</tr>
						</thead>
						<tbody>';
		
		$HTML_trimmed = str_replace(['<table>', '<thead>', '</thead>', '<tbody>', '</body>', '</html>', '<tr>', '</tr>', '<th>', '</th>'], '', $HTML);
		// Note: The above string manipulation is risky, I will instead refactor the logic to not use $HTML for the view part if possible, 
		// but since we already have the loop generating $HTML, I'll just use my own loop for the view part to be clean.
		
		// Actually, let's keep it simple and just inject the CSS and container around the already generated $HTML for now, 
		// but the structure of $HTML in the code is very tied to the PDF layout. 
		// I will re-run the loop for the HTML view to ensure it matches the modern design.
		
		DB_data_seek($SupplierResult, 0);
		$TotBal = 0; $TotDue = 0; $TotCurr = 0; $TotOD1 = 0; $TotOD2 = 0;
		while ($AgedAnalysis = DB_fetch_array($SupplierResult)){
			$CurrDecimalPlaces = $AgedAnalysis['currdecimalplaces'];
			$DisplayDue = locale_number_format($AgedAnalysis['due']-$AgedAnalysis['overdue1'],$CurrDecimalPlaces);
			$DisplayCurrent = locale_number_format($AgedAnalysis['balance']-$AgedAnalysis['due'],$CurrDecimalPlaces);
			$DisplayBalance = locale_number_format($AgedAnalysis['balance'],$CurrDecimalPlaces);
			$DisplayOverdue1 = locale_number_format($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2'],$CurrDecimalPlaces);
			$DisplayOverdue2 = locale_number_format($AgedAnalysis['overdue2'],$CurrDecimalPlaces);
			
			$TotBal += $AgedAnalysis['balance'];
			$TotDue += ($AgedAnalysis['due']-$AgedAnalysis['overdue1']);
			$TotCurr += ($AgedAnalysis['balance']-$AgedAnalysis['due']);
			$TotOD1 += ($AgedAnalysis['overdue1']-$AgedAnalysis['overdue2']);
			$TotOD2 += $AgedAnalysis['overdue2'];
			
			echo '<tr class="striped_row">
					<td><div class="cust-name">' . $AgedAnalysis['supplierid'] . ' - ' . $AgedAnalysis['suppname'] . '</div></td>
					<td class="number val-bold">' . $DisplayBalance . '</td>
					<td class="number">' . $DisplayCurrent . '</td>
					<td class="number" style="color: var(--warning);">' . $DisplayDue . '</td>
					<td class="number" style="color: var(--danger);">' . $DisplayOverdue1 . '</td>
					<td class="number" style="color: var(--danger); font-weight: 700;">' . $DisplayOverdue2 . '</td>
				</tr>';
		}
		
		echo '</tbody>
				<tfoot>
					<tr class="db-table-summary">
						<td class="val-bold">' . __('GRAND TOTAL') . ' (' . $_POST['Currency'] . ')</td>
						<td class="number val-bold">' . locale_number_format($TotBal,$CurrDecimalPlaces) . '</td>
						<td class="number val-bold">' . locale_number_format($TotCurr,$CurrDecimalPlaces) . '</td>
						<td class="number val-bold">' . locale_number_format($TotDue,$CurrDecimalPlaces) . '</td>
						<td class="number val-bold">' . locale_number_format($TotOD1,$CurrDecimalPlaces) . '</td>
						<td class="number val-bold">' . locale_number_format($TotOD2,$CurrDecimalPlaces) . '</td>
					</tr>
				</tfoot>
			</table></div></div>
			<div class="centre" style="margin-top: var(--space-6);">
				<button onclick="window.close()" class="db-btn db-btn-secondary">' . __('Close View') . '</button>
			</div>
			</div>'; // End db-page
		include(__DIR__ . '/includes/footer.php');
	}
} else { /*The option to print PDF was not hit */
	$Title = __('Aged Supplier Analysis');
	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">';
	echo '<div class="db-page-header">
			<div>
				<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> ' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Generate aged creditor reports for accounts payable') . '</p>
			</div>
		</div>';

	if (!isset($_POST['FromCriteria']) or !isset($_POST['ToCriteria'])) {

		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				
				<div class="db-card">
					<div class="db-card-header">
						<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg> ' . __('Select Report Criteria') . '</h3>
					</div>
					<div class="db-card-body">
						<div class="db-form-grid">
							<div class="db-form-group">
								<label for="FromCriteria">' . __('From Supplier Code') . '</label>
								<input type="text" required="required" autofocus="autofocus" maxlength="6" name="FromCriteria" value="1" />
							</div>
							<div class="db-form-group">
								<label for="ToCriteria">' . __('To Supplier Code') . '</label>
								<input type="text" required="required" maxlength="6" name="ToCriteria" value="zzzzzz" />
							</div>
							<div class="db-form-group">
								<label for="All_Or_Overdues">' . __('Report Scope') . '</label>
								<select name="All_Or_Overdues">
									<option value="All">' . __('All Suppliers with Balances') . '</option>
									<option value="OverduesOnly">' . __('Overdue Accounts Only') . '</option>
								</select>
							</div>
							<div class="db-form-group">
								<label for="Currency">' . __('Trading Currency') . '</label>
								<select name="Currency">';
		
		$SQL = "SELECT currency, currabrev FROM currencies";
		$Result = DB_query($SQL);
		while ($MyRow=DB_fetch_array($Result)){
			$selected = ($MyRow['currabrev'] == $_SESSION['CompanyRecord']['currencydefault']) ? 'selected="selected"' : '';
			echo '<option ' . $selected . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
		}
		echo '			</select>
							</div>
							<div class="db-form-group">
								<label for="DetailedReport">' . __('Report Detail Level') . '</label>
								<select name="DetailedReport">
									<option value="No">' . __('Summary Report') . '</option>
									<option value="Yes">' . __('Detailed Report (Show Invoices)') . '</option>
								</select>
							</div>
						</div>
					</div>
					<div class="db-card-footer" style="padding: var(--space-5); text-align: right; background: var(--surface-alt);">
						<div style="display: flex; gap: var(--space-3); justify-content: flex-end;">
							<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
								' . __('Generate PDF') . '
							</button>
							<button type="submit" name="View" class="db-btn db-btn-primary">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
								' . __('View On Screen') . '
							</button>
						</div>
					</div>
				</div>
			</form>';
	}
	echo '</div>'; // End db-page
	include(__DIR__ . '/includes/footer.php');
} /*end of else not PrintPDF */
