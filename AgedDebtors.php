<?php

/* Lists customer account balances in detail or summary in selected currency */

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['PrintPDF']) or isset($_POST['View'])
	and isset($_POST['FromCriteria'])
	and mb_strlen($_POST['FromCriteria'])>=1
	and isset($_POST['ToCriteria'])
	and mb_strlen($_POST['ToCriteria'])>=1) {

	/*Now figure out the aged analysis for the customer range under review */
	if ($_SESSION['SalesmanLogin'] !=  '') {
		$_POST['Salesman'] = $_SESSION['SalesmanLogin'];
	}
	if (trim($_POST['Salesman'])!= '') {
		$SalesLimit = " AND debtorsmaster.debtorno IN (SELECT DISTINCT debtorno FROM custbranch WHERE salesman = '".$_POST['Salesman']."') ";
	} else {
		$SalesLimit = "";
	}
	if ($_POST['All_Or_Overdues']=='All') {
		$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
				SUM(debtortrans.balance) AS balance,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue
						THEN debtortrans.balance
						ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
						THEN debtortrans.balance
						ELSE 0 END
					END
				) AS due,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
						THEN debtortrans.balance ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
						THEN debtortrans.balance
						ELSE 0 END
					END
				) AS overdue1,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
					THEN
						CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
						THEN debtortrans.balance ELSE 0 END
					ELSE
						CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
						THEN debtortrans.balance
						ELSE 0 END
					END
				) AS overdue2
				FROM debtorsmaster,
					paymentterms,
					holdreasons,
					currencies,
					debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
					AND debtorsmaster.currcode = currencies.currabrev
					AND debtorsmaster.holdreason = holdreasons.reasoncode
					AND debtorsmaster.debtorno = debtortrans.debtorno
					AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
					AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
					AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
					" . $SalesLimit . "
				GROUP BY debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription
				HAVING
					ROUND(ABS(SUM(debtortrans.balance)),currencies.decimalplaces) > " . CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) . "";

	} elseif ($_POST['All_Or_Overdues']=='OverduesOnly') {
		$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
				SUM(debtortrans.balance) AS balance,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= paymentterms.daysbeforedue
								THEN debtortrans.balance
								ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.balance ELSE 0 END
					END
				) AS due,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.balance
								ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.balance
								ELSE 0 END
					END
				) AS overdue1,
				SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.balance
								ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
								THEN debtortrans.balance
								ELSE 0 END
					END
				) AS overdue2
			FROM debtorsmaster,
					paymentterms,
					holdreasons,
					currencies,
					debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
				AND debtorsmaster.currcode = currencies.currabrev
				AND debtorsmaster.holdreason = holdreasons.reasoncode
				AND debtorsmaster.debtorno = debtortrans.debtorno
				AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
				AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
				AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
				" . $SalesLimit . "
				GROUP BY debtorsmaster.debtorno,
						debtorsmaster.name,
						currencies.currency,
						paymentterms.terms,
						paymentterms.daysbeforedue,
						paymentterms.dayinfollowingmonth,
						debtorsmaster.creditlimit,
						holdreasons.dissallowinvoices,
						holdreasons.reasondescription
				HAVING SUM(
					CASE WHEN (paymentterms.daysbeforedue > 0)
						THEN
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.balance
								ELSE 0 END
						ELSE
							CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.balance
								ELSE 0 END
					END
				) > " . CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) . "";

	} elseif ($_POST['All_Or_Overdues']=='HeldOnly') {

		$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					currencies.decimalplaces,
					paymentterms.terms,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription,
					SUM(debtortrans.balance) AS balance,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= paymentterms.daysbeforedue
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.balance
								ELSE 0 END
						END
					) AS due,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.balance ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.balance
							ELSE 0 END
						END
					) AS overdue1,
					SUM(
						CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
								THEN debtortrans.balance
							ELSE 0 END
						END
					) AS overdue2
				FROM debtorsmaster,
					paymentterms,
					holdreasons,
					currencies,
					debtortrans
				WHERE debtorsmaster.paymentterms = paymentterms.termsindicator
					AND debtorsmaster.currcode = currencies.currabrev
					AND debtorsmaster.holdreason = holdreasons.reasoncode
					AND debtorsmaster.debtorno = debtortrans.debtorno
					AND holdreasons.dissallowinvoices=1
					AND debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
					AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
					AND debtorsmaster.currcode ='" . $_POST['Currency'] . "'
					" . $SalesLimit . "
				GROUP BY debtorsmaster.debtorno,
					debtorsmaster.name,
					currencies.currency,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription
				HAVING ABS(SUM(debtortrans.balance)) >" . CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) . "";
	}
	$ErrMsg = __('The customer details could not be retrieved');
	$CustomerResult = DB_query($SQL, $ErrMsg);

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>
						<link href="css/reports.css" rel="stylesheet" type="text/css" />
						<meta name="author" content="WebERP ' . $Version . '">
						<meta name="Creator" content="webERP https://www.weberp.org">
					</head>
					<body>';
	}

	$HTML .= '<div class="db-page">
				<div class="db-page-header">
					<div>
						<h2 class="db-page-title">' . __('Aged Customer Balances') . '</h2>
						<p class="db-page-subtitle">' . __('Customers from') . ' ' . $_POST['FromCriteria'] . ' ' . __('to') . ' ' . $_POST['ToCriteria'] . ' &mdash; ' . $_POST['Currency'] . '</p>
					</div>
					<div class="db-header-actions">
						<button onclick="window.print()" class="db-btn db-btn-secondary">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
							' . __('Print Page') . '
						</button>
					</div>
				</div>
				<div class="card-v2">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Customer') . '</th>
									<th class="text-right">' . __('Balance') . '</th>
									<th class="text-right">' . __('Current') . '</th>
									<th class="text-right">' . __('Due Now') . '</th>
									<th class="text-right">' . $_SESSION['PastDueDays1'] . ' ' . __('Days Over') . '</th>
									<th class="text-right">' . $_SESSION['PastDueDays2'] . ' ' . __('Days Over') . '</th>
								</tr>
							</thead>
							<tbody>';

	$TotBal=0;
	$TotCurr=0;
	$TotDue=0;
	$TotOD1=0;
	$TotOD2=0;

	$ListCount = DB_num_rows($CustomerResult);
	$CurrDecimalPlaces =2; //by default

	while ($AgedAnalysis = DB_fetch_array($CustomerResult)) {
		$CurrDecimalPlaces = $AgedAnalysis['decimalplaces'];
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

		$HTML .= '<tr>
					<td class="cust-name">' . $AgedAnalysis['debtorno'] . ' - ' . $AgedAnalysis['name'] . '</td>
					<td class="text-right val-bold">' . $DisplayBalance . '</td>
					<td class="text-right">' . $DisplayCurrent . '</td>
					<td class="text-right">' . $DisplayDue . '</td>
					<td class="text-right">' . $DisplayOverdue1 . '</td>
					<td class="text-right">' . $DisplayOverdue2 . '</td>
				</tr>';

		if ($_POST['DetailedReport']=='Yes') {

			$SQL = "SELECT systypes.typename,
						debtortrans.transno,
						debtortrans.trandate,
						(debtortrans.balance) as balance,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.balance
								ELSE 0 END
						END) AS due,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN debtortrans.balance ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
								THEN debtortrans.balance
								ELSE 0 END
						END) AS overdue1,
						(CASE WHEN (paymentterms.daysbeforedue > 0)
							THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . "
								THEN debtortrans.balance
								ELSE 0 END
						END) AS overdue2
				   FROM debtorsmaster,
						paymentterms,
						debtortrans,
						systypes
				   WHERE systypes.typeid = debtortrans.type
						AND debtorsmaster.paymentterms = paymentterms.termsindicator
						AND debtorsmaster.debtorno = debtortrans.debtorno
						AND debtortrans.debtorno = '" . $AgedAnalysis['debtorno'] . "'
						AND ABS(debtortrans.balance)> " . CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) . "";

			if ($_SESSION['SalesmanLogin'] !=  '') {
				$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
			}

			$ErrMsg = __('The details of outstanding transactions for customer') . ' - ' . $AgedAnalysis['debtorno'] . ' ' . __('could not be retrieved');
			$DetailResult = DB_query($SQL, $ErrMsg);

			$HTML .= '<tr class="sub-report-row">
						<td colspan="6">
							<div class="sub-table-wrapper">
								<table class="db-table-sub">
									<thead>
										<tr class="sub-header-row">
											<th colspan="2">' . __('Transaction Detail') . '</th>
											<th>' . __('Date') . '</th>
											<th colspan="3"></th>
										</tr>
									</thead>
									<tbody>';

			while ($DetailTrans = DB_fetch_array($DetailResult)) {

				$DisplayTranDate = ConvertSQLDate($DetailTrans['trandate']);
				$HTML .= '<tr class="sub-data-row-header">
							<td class="val-bold">' . $DetailTrans['typename'] . '</td>
							<td class="val-bold">' . $DetailTrans['transno'] . '</td>
							<td>' . $DisplayTranDate . '</td>
							<td colspan="3"></td>
						</tr>';

				$DisplayDue = locale_number_format($DetailTrans['due']-$DetailTrans['overdue1'],$CurrDecimalPlaces);
				$DisplayCurrent = locale_number_format($DetailTrans['balance']-$DetailTrans['due'],$CurrDecimalPlaces);
				$DisplayBalance = locale_number_format($DetailTrans['balance'],$CurrDecimalPlaces);
				$DisplayOverdue1 = locale_number_format($DetailTrans['overdue1']-$DetailTrans['overdue2'],$CurrDecimalPlaces);
				$DisplayOverdue2 = locale_number_format($DetailTrans['overdue2'],$CurrDecimalPlaces);

				$HTML .= '<tr class="sub-data-row">
							<td class="text-right val-bold">' . $DisplayBalance . '</td>
							<td class="text-right">' . $DisplayCurrent . '</td>
							<td class="text-right">' . $DisplayDue . '</td>
							<td class="text-right">' . $DisplayOverdue1 . '</td>
							<td class="text-right">' . $DisplayOverdue2 . '</td>
							<td></td>
						</tr>';

			} /*end while there are detail transactions to show */
			$HTML .= '		</tbody>
								</table>
							</div>
						</td>
					</tr>';

			$FontSize=8;
		} /*Its a detailed report */
	} /*end customer aged analysis while loop */

	$DisplayTotBalance = locale_number_format($TotBal,$CurrDecimalPlaces);
	$DisplayTotDue = locale_number_format($TotDue,$CurrDecimalPlaces);
	$DisplayTotCurrent = locale_number_format($TotCurr,$CurrDecimalPlaces);
	$DisplayTotOverdue1 = locale_number_format($TotOD1,$CurrDecimalPlaces);
	$DisplayTotOverdue2 = locale_number_format($TotOD2,$CurrDecimalPlaces);

	$HTML .= '</tbody>
						<tfoot>
							<tr class="total_row">
								<td class="text-right val-bold">' . __('TOTALS') . '</td>
								<td class="text-right val-bold">' . $DisplayTotBalance . '</td>
								<td class="text-right val-bold">' . $DisplayTotCurrent . '</td>
								<td class="text-right val-bold">' . $DisplayTotDue . '</td>
								<td class="text-right val-bold">' . $DisplayTotOverdue1 . '</td>
								<td class="text-right val-bold">' . $DisplayTotOverdue2 . '</td>
							</tr>
						</tfoot>';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</table>
					</div>
				</div>
			</div>
		</body>
		</html>';
	} else {
		$HTML .= '</table>
					</div>
				</div>
				<div class="form-footer-actions centre">
					<form><button type="submit" name="close" class="btn-secondary" onclick="window.close()">' . __('Close') . '</button></form>
				</div>
			</div>';
	}

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_AgedDebtors_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Aged Debtor Analysis');
		include(__DIR__ . '/includes/header.php');
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit */

	$Title = __('Aged Debtor Analysis');

	$ViewTopic = 'ARReports';
	$BookMark = 'AgedDebtors';

	include(__DIR__ . '/includes/header.php');

		echo '<div class="db-page">
				<div class="db-page-header">
					<div>
						<h2 class="db-page-title">' . $Title . '</h2>
						<p class="db-page-subtitle">' . __('Analyze aged customer balances and credit status') . '</p>
					</div>
				</div>

				<div class="card-v2">
					<div class="card-header-v2">
						<h3>' . __('Report Criteria') . '</h3>
					</div>
					<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						
						<div class="db-grid db-grid-3">
							<div class="db-field">
								<label class="db-label" for="FromCriteria">' . __('From Customer Code') . '</label>
								<input tabindex="1" autofocus="autofocus" required="required" type="text" maxlength="6" name="FromCriteria" value="0" />
							</div>
							<div class="db-field">
								<label class="db-label" for="ToCriteria">' . __('To Customer Code') . '</label>
								<input tabindex="2" type="text" required="required" maxlength="6" name="ToCriteria" value="zzzzzz" />
							</div>
							<div class="db-field">
								<label class="db-label" for="All_Or_Overdues">' . __('Report Type') . '</label>
								<select tabindex="3" name="All_Or_Overdues">
									<option selected="selected" value="All">' . __('All customers with balances') . '</option>
									<option value="OverduesOnly">' . __('Overdue accounts only') . '</option>
									<option value="HeldOnly">' . __('Held accounts only') . '</option>
								</select>
							</div>
							<div class="db-field">
								<label class="db-label" for="Salesman">' . __('Salesperson') . '</label>';
		if ($_SESSION['SalesmanLogin'] !=  '') {
			echo '<input type="text" readonly value="' . $_SESSION['UsersRealName'] . '" />
				  <input type="hidden" name="Salesman" value="' . $_SESSION['SalesmanLogin'] . '" />';
		} else {
			echo '<select tabindex="4" name="Salesman">';
			$SQL = "SELECT salesmancode, salesmanname FROM salesman";
			$Result = DB_query($SQL);
			echo '<option value="">' . __('All Salespeople') . '</option>';
			while ($MyRow=DB_fetch_array($Result)) {
				echo '<option value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
			}
			echo '</select>';
		}
		echo '			</div>
							<div class="db-field">
								<label class="db-label" for="Currency">' . __('Currency') . '</label>
								<select tabindex="5" name="Currency">';
		$SQL = "SELECT currency, currabrev FROM currencies";
		$Result = DB_query($SQL);
		while ($MyRow=DB_fetch_array($Result)) {
			if ($MyRow['currabrev'] == $_SESSION['CompanyRecord']['currencydefault']) {
				echo '<option selected="selected" value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
			} else {
				echo '<option value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
			}
		}
		echo '					</select>
							</div>
							<div class="db-field">
								<label class="db-label" for="DetailedReport">' . __('Detail Level') . '</label>
								<select tabindex="6" name="DetailedReport">
									<option selected="selected" value="No">' . __('Summary Report') . '</option>
									<option value="Yes">' . __('Detailed Report') . '</option>
								</select>
							</div>
						</div>

						<div class="form-footer-actions">
							<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
								' . __('Print PDF') . '
							</button>
							<button type="submit" name="View" class="db-btn db-btn-primary">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
								' . __('View Online') . '
							</button>
						</div>
					</form>
				</div>
			</div>';
	include(__DIR__ . '/includes/footer.php');
} /*end of else not PrintPDF */
