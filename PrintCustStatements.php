<?php
require (__DIR__ . '/includes/session.php');
include (__DIR__ . '/includes/SQL_CommonFunctions.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
use Dompdf\Options;

$ViewTopic = 'ARReports';
$BookMark = 'CustomerStatements';
$Title = __('Print Customer Statements');

// If this file is called from another script, set POST variables from GET
if (isset($_POST['PrintPDF'])) {
	$PaperSize = 'A4_Landscape';
}

if (isset($_GET['PrintPDF'])) {
	$FromCust = $_GET['FromCust'];
	$ToCust = $_GET['ToCust'];
	$PrintPDF = $_GET['PrintPDF'];
	$_POST['FromCust'] = $FromCust;
	$_POST['ToCust'] = $ToCust;
	$_POST['PrintPDF'] = $PrintPDF;
	$PaperSize = 'A4_Landscape';
}

if (isset($_GET['FromCust'])) {
	$_POST['FromCust'] = $_GET['FromCust'];
}

if (isset($_GET['ToCust'])) {
	$_POST['ToCust'] = $_GET['ToCust'];
}

if (isset($_GET['EmailOrPrint'])) {
	$_POST['EmailOrPrint'] = $_GET['EmailOrPrint'];
}

if (isset($_POST['PrintPDF']) and isset($_POST['FromCust']) and $_POST['FromCust'] != '') {

	$_POST['FromCust'] = mb_strtoupper($_POST['FromCust']);

	if (!isset($_POST['ToCust'])) {
		$_POST['ToCust'] = $_POST['FromCust'];
	}
	else {
		$_POST['ToCust'] = mb_strtoupper($_POST['ToCust']);
	}

	// Settle old transactions
	$ErrMsg = __('There was a problem settling the old transactions.');
	$SQL = "UPDATE debtortrans 
			SET settled=1 
			WHERE ABS(debtortrans.balance) < " . CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) . "
			AND debtortrans.settled = 0";
	$SettleAsNec = DB_query($SQL, $ErrMsg);

	// Get customers in range
	$ErrMsg = __('There was a problem retrieving the customer information for the statements from the database');
	$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				debtorsmaster.address1,
				debtorsmaster.address2,
				debtorsmaster.address3,
				debtorsmaster.address4,
				debtorsmaster.address5,
				debtorsmaster.address6,
				debtorsmaster.lastpaid,
				debtorsmaster.lastpaiddate,
				currencies.currency,
				currencies.decimalplaces AS currdecimalplaces,
				paymentterms.terms
			FROM debtorsmaster INNER JOIN currencies
				ON debtorsmaster.currcode=currencies.currabrev
			INNER JOIN paymentterms
				ON debtorsmaster.paymentterms=paymentterms.termsindicator
			WHERE debtorsmaster.debtorno >='" . $_POST['FromCust'] . "'
			AND debtorsmaster.debtorno <='" . $_POST['ToCust'] . "'
			ORDER BY debtorsmaster.debtorno";
	$StatementResults = DB_query($SQL, $ErrMsg);

	if (DB_Num_Rows($StatementResults) == 0) {
		$Title = __('Print Statements') . ' - ' . __('No Customers Found');
		require (__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/printer.png" title="' . __('Print') . '" alt="" />' . ' ' . __('Print Customer Account Statements') . '</p>';
		prnMsg(__('There were no Customers matching your selection of ') . $_POST['FromCust'] . ' - ' . $_POST['ToCust'] . '.', 'error');
		include (__DIR__ . '/includes/footer.php');
		exit();
	}

	// Prepare HTML for all statements
	$HTML = '<!DOCTYPE html><html><head>';
	$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	$HTML .= '<style>
		body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; }
		.header { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
		.company { font-size: 13px; font-weight: bold;}
		.small { font-size: 10px; }
		table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
		th, td { border: 1px solid #333; padding: 4px; vertical-align: top; }
		.section-title { font-size: 13px; font-weight: bold; margin: 18px 0 7px 0; }
		.footer { margin-top: 18px; font-size: 10px;}
		.right { text-align: right; }
		.left { text-align: left; }
		.center { text-align: center; }
		.page-break { page-break-after: always; }
	</style></head><body>';

	// Get default bank account if any
	$SQL = "SELECT bankaccounts.invoice, bankaccounts.bankaccountnumber
			FROM bankaccounts
			WHERE bankaccounts.invoice = '1'";
	$Result = DB_query($SQL, '', '', false, false);
	$DefaultBankAccountNumber = '';
	if (DB_error_no() != 1) {
		if (DB_num_rows($Result) == 1) {
			$MyRow = DB_fetch_array($Result);
			$DefaultBankAccountNumber = $MyRow['bankaccountnumber'];
		}
	}

	$FirstStatement = true;
	while ($StmtHeader = DB_fetch_array($StatementResults)) {

		if (isset($RecipientArray)) {
			unset($RecipientArray);
		}
		$RecipientArray = array();
		$RecipientsResult = DB_query("SELECT email FROM custcontacts WHERE statement=1 AND debtorno='" . $StmtHeader['debtorno'] . "'");
		while ($RecipientRow = DB_fetch_row($RecipientsResult)) {
			if (IsEmailAddress($RecipientRow[0])) {
				$RecipientArray[] = $RecipientRow[0];
			}
		}

		// Only print if Print, or Email and there are recipients
		if (($_POST['EmailOrPrint'] == 'print' and count($RecipientArray) == 0) or ($_POST['EmailOrPrint'] == 'email' and count($RecipientArray) > 0)) {

			// Header
			$HTML .= '<div class="company"><img class="logo" src="' . $_SESSION['LogoFile'] . '" /></div>';
			$HTML .= '<div class="company">' . $_SESSION['CompanyRecord']['coyname'] . '</div>';
			$HTML .= '<div class="header">' . __('Customer Statement') . '</div>';
			$HTML .= '<div class="small">' . __('For customer') . ': ' . $StmtHeader['name'] . ' (' . $StmtHeader['debtorno'] . ')</div>';
			$HTML .= '<div class="small">' . implode(', ', array_filter([$StmtHeader['address1'], $StmtHeader['address2'], $StmtHeader['address3'], $StmtHeader['address4'], $StmtHeader['address5'], $StmtHeader['address6']])) . '</div>';

			// Outstanding Transactions
			$ErrMsg = __('There was a problem retrieving the outstanding transactions for') . ' ' . $StmtHeader['name'] . ' ' . __('from the database') . '.';
			$SQL = "SELECT systypes.typename,
						debtortrans.transno,
						debtortrans.trandate,
						debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst as total,
						debtortrans.alloc,
						debtortrans.balance as ostdg
					FROM debtortrans INNER JOIN systypes
						ON debtortrans.type=systypes.typeid
					WHERE debtortrans.debtorno='" . $StmtHeader['debtorno'] . "'
					AND debtortrans.settled=0";
			if ($_SESSION['SalesmanLogin'] != '') {
				$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
			}
			$SQL .= " ORDER BY debtortrans.id";
			$OstdgTrans = DB_query($SQL, $ErrMsg);
			$NumberOfRecordsReturned = DB_num_rows($OstdgTrans);

			// Settled Transactions Last Month
			$SetldTrans = false;
			if ($_SESSION['Show_Settled_LastMonth'] == 1) {
				$ErrMsg = __('There was a problem retrieving the transactions that were settled over the course of the last month for') . ' ' . $StmtHeader['name'] . ' ' . __('from the database');
				$SQL = "SELECT DISTINCT debtortrans.id,
									systypes.typename,
									debtortrans.transno,
									debtortrans.trandate,
									debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst AS total,
									debtortrans.alloc,
									debtortrans.balance AS ostdg
							FROM debtortrans INNER JOIN systypes
								ON debtortrans.type=systypes.typeid
							INNER JOIN custallocns
								ON (debtortrans.id=custallocns.transid_allocfrom
									OR debtortrans.id=custallocns.transid_allocto)
							WHERE custallocns.datealloc >='" . date('Y-m-d', mktime(0, 0, 0, date('m') - 1, date('d'), date('y'))) . "'
							AND debtortrans.debtorno='" . $StmtHeader['debtorno'] . "'
							AND debtortrans.settled=1";
				if ($_SESSION['SalesmanLogin'] != '') {
					$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
				}
				$SQL .= " ORDER BY debtortrans.id";
				$SetldTrans = DB_query($SQL, $ErrMsg);
				$NumberOfRecordsReturned += DB_num_rows($SetldTrans);
			}

			if ($NumberOfRecordsReturned >= 1) {

				// Settled Transactions Table
				if ($_SESSION['Show_Settled_LastMonth'] == 1 && DB_num_rows($SetldTrans) >= 1) {
					$HTML .= '<div class="section-title">' . __('Settled Transactions') . '</div>';
					$HTML .= '<table>
						<tr>
							<th>' . __('Type') . '</th>
							<th>' . __('Trans No') . '</th>
							<th>' . __('Date') . '</th>
							<th class="right">' . __('Total') . '</th>
							<th class="right">' . __('Alloc') . '</th>
							<th class="right">' . __('Outstanding') . '</th>
						</tr>';
					while ($MyRow = DB_fetch_array($SetldTrans)) {
						$DisplayAlloc = locale_number_format($MyRow['alloc'], $StmtHeader['currdecimalplaces']);
						$DisplayOutstanding = locale_number_format($MyRow['ostdg'], $StmtHeader['currdecimalplaces']);
						$DisplayTotal = locale_number_format(abs($MyRow['total']), $StmtHeader['currdecimalplaces']);

						$HTML .= '<tr>
							<td>' . __($MyRow['typename']) . '</td>
							<td>' . $MyRow['transno'] . '</td>
							<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
							<td class="right">' . $DisplayTotal . '</td>
							<td class="right">' . $DisplayAlloc . '</td>
							<td class="right">' . $DisplayOutstanding . '</td>
						</tr>';
					}
					$HTML .= '</table>';
				}

				// Outstanding Transactions Table
				if (DB_num_rows($OstdgTrans) >= 1) {
					$HTML .= '<div class="section-title">' . __('Outstanding Transactions') . '</div>';
					$HTML .= '<table>
						<tr>
							<th>' . __('Type') . '</th>
							<th>' . __('Trans No') . '</th>
							<th>' . __('Date') . '</th>
							<th class="right">' . __('Total') . '</th>
							<th class="right">' . __('Alloc') . '</th>
							<th class="right">' . __('Outstanding') . '</th>
						</tr>';
					while ($MyRow = DB_fetch_array($OstdgTrans)) {
						$DisplayAlloc = locale_number_format($MyRow['alloc'], $StmtHeader['currdecimalplaces']);
						$DisplayOutstanding = locale_number_format($MyRow['ostdg'], $StmtHeader['currdecimalplaces']);
						$DisplayTotal = locale_number_format(abs($MyRow['total']), $StmtHeader['currdecimalplaces']);
						$HTML .= '<tr>
							<td>' . __($MyRow['typename']) . '</td>
							<td>' . $MyRow['transno'] . '</td>
							<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
							<td class="right">' . $DisplayTotal . '</td>
							<td class="right">' . $DisplayAlloc . '</td>
							<td class="right">' . $DisplayOutstanding . '</td>
						</tr>';
					}
					$HTML .= '</table>';
				}

				// Aged Analysis
				$SQL = "SELECT debtorsmaster.name,
							currencies.currency,
							paymentterms.terms,
							debtorsmaster.creditlimit,
							holdreasons.dissallowinvoices,
							holdreasons.reasondescription,
							SUM(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight +
							debtortrans.ovdiscount - debtortrans.alloc) AS balance,
							SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >=
								paymentterms.daysbeforedue
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= 0
								THEN debtortrans.balance
								ELSE 0 END
							END) AS due,
							Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >=
								(paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							END) AS overdue1,
							Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
								CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
								AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue +
								" . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							ELSE
								CASE WHEN (TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth))
								>= " . $_SESSION['PastDueDays2'] . ")
								THEN debtortrans.balance
								ELSE 0 END
							END) AS overdue2
						FROM debtorsmaster INNER JOIN paymentterms
							ON debtorsmaster.paymentterms = paymentterms.termsindicator
						INNER JOIN currencies
							ON debtorsmaster.currcode = currencies.currabrev
						INNER JOIN holdreasons
							ON debtorsmaster.holdreason = holdreasons.reasoncode
						INNER JOIN debtortrans
							ON debtorsmaster.debtorno = debtortrans.debtorno
						WHERE
							debtorsmaster.debtorno = '" . $StmtHeader['debtorno'] . "'";
				if ($_SESSION['SalesmanLogin'] != '') {
					$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
				}
				$SQL .= " GROUP BY
							debtorsmaster.name,
							currencies.currency,
							paymentterms.terms,
							paymentterms.daysbeforedue,
							paymentterms.dayinfollowingmonth,
							debtorsmaster.creditlimit,
							holdreasons.dissallowinvoices,
							holdreasons.reasondescription";
				$CustomerResult = DB_query($SQL);
				$AgedAnalysis = DB_fetch_array($CustomerResult);

				$DisplayDue = locale_number_format($AgedAnalysis['due'] - $AgedAnalysis['overdue1'], $StmtHeader['currdecimalplaces']);
				$DisplayCurrent = locale_number_format($AgedAnalysis['balance'] - $AgedAnalysis['due'], $StmtHeader['currdecimalplaces']);
				$DisplayBalance = locale_number_format($AgedAnalysis['balance'], $StmtHeader['currdecimalplaces']);
				$DisplayOverdue1 = locale_number_format($AgedAnalysis['overdue1'] - $AgedAnalysis['overdue2'], $StmtHeader['currdecimalplaces']);
				$DisplayOverdue2 = locale_number_format($AgedAnalysis['overdue2'], $StmtHeader['currdecimalplaces']);

				$HTML .= '<div class="section-title">' . __('Aged Analysis') . '</div>';
				$HTML .= '<table>
					<tr>
						<th>' . __('Current') . '</th>
						<th>' . __('Past Due') . '</th>
						<th>' . $_SESSION['PastDueDays1'] . '-' . $_SESSION['PastDueDays2'] . ' ' . __('days') . '</th>
						<th>' . __('Over') . ' ' . $_SESSION['PastDueDays2'] . ' ' . __('days') . '</th>
						<th>' . __('Total Balance') . '</th>
					</tr>
					<tr>
						<td class="right">' . $DisplayCurrent . '</td>
						<td class="right">' . $DisplayDue . '</td>
						<td class="right">' . $DisplayOverdue1 . '</td>
						<td class="right">' . $DisplayOverdue2 . '</td>
						<td class="right">' . $DisplayBalance . '</td>
					</tr>
				</table>';

				if (mb_strlen($StmtHeader['lastpaiddate']) > 1 and $StmtHeader['lastpaid'] != 0) {
					$HTML .= '<div class="footer">' . __('Last payment received') . ': ' . ConvertSQLDate($StmtHeader['lastpaiddate']) . ' | ' . __('Amount received was') . ': ' . locale_number_format($StmtHeader['lastpaid'], $StmtHeader['currdecimalplaces']) . '</div>';
				}

				$HTML .= '<div class="footer">' . __('Please make payments to our account:') . ' ' . $DefaultBankAccountNumber . '</div>';
				$HTML .= '<div class="footer">' . __('Quoting your account reference') . ' ' . $StmtHeader['debtorno'] . '</div>';
				$HTML .= '<div class="page-break"></div>';
			}

			// Email Option: Send the PDF to recipients (handled after PDF generation)

		}
	}
	$HTML .= '</body>
		</html>';

	// Generate PDF with DomPDF
	$PdfFileName = $_SESSION['DatabaseName'] . '_CustomerStatements_' . date('Y-m-d') . '.pdf';
	// Display PDF in browser
	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);

	// (Optional) Setup the paper size and orientation
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');

	// Render the HTML as PDF
	$DomPDF->render();

	// Output the generated PDF to Browser
	$DomPDF->stream($PdfFileName, array("Attachment" => false));

} else { // The option to print PDF was not hit
	$Result = DB_query("SELECT debtorno FROM debtorsmaster ORDER BY debtorno");
	while ($MyRow = DB_fetch_array($Result)) {
		$DebtorsArray[] = $MyRow['debtorno'];
	}
	reset($DebtorsArray);
	$FirstDebtor = current($DebtorsArray);
	$LastDebtor = end($DebtorsArray);

	$Title = __('Select Statements to Print');

	// Inject premium styles for the Architect workspace
	$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
	/* Architect Workspace Overrides */
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; gap: 10px;
		padding: 12px 28px; border-radius: 50px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	.custom-range-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 20px;
		margin-bottom: 24px;
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
</style>';

	include (__DIR__ . '/includes/header.php');
	
	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('Home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('Receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('Statements Generation') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Generate pixel-perfect PDF statements for customer accounts') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	if (!isset($_POST['FromCust']) or $_POST['FromCust'] == '') {
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank" style="display: contents;">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		
		echo '<div class="custom-bottom-layout">
				<aside class="db-sidebar">';

		echo '<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
				<div class="db-card-header">
					<h3 class="db-card-title">
						<i class="fas fa-sliders-h" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Delivery Settings') . '
					</h3>
				</div>
				<div style="padding: 24px;">

					<div class="db-form-group" style="margin-bottom: 24px;">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Output Method') . '</label>
						<select name="EmailOrPrint" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option selected="selected" value="print">' . __('Print to Screen') . '</option>
							<option value="email">' . __('Email to Contacts') . '</option>
						</select>
					</div>

					<div style="display: flex; flex-direction: column; gap: 12px;">
						<button type="submit" name="PrintPDF" class="db-btn" style="width: 100%; justify-content: center; font-weight: 700; padding: 18px; border-radius: 14px; background: #059669; color: white; border: none; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3); cursor: pointer;">
							<i class="fas fa-paper-plane" style="margin-right: 8px;"></i> ' . __('Execute Delivery') . '
						</button>
					</div>

				</div>
			</div>
			</aside>';

		echo '<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-users" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Account Coverage Selection') . '
						</h3>
					</div>
					<div style="padding: 30px;">
						
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Start Customer (Code)') . '</label>
								<input type="text" class="db-input" maxlength="10" name="FromCust" value="' . $FirstDebtor . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('End Customer (Code)') . '</label>
								<input type="text" class="db-input" maxlength="10" name="ToCust" value="' . $LastDebtor . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>
						</div>
						
						<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 16px; display: flex; align-items: flex-start; gap: 12px; margin-top: 10px;">
							<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
							<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
								<strong>' . __('Note on Ranges:') . '</strong> ' . __('Leave the default values to process all customers. To generate a statement for a single customer, place their code in both the start and end fields.') . '
							</div>
						</div>
						
					</div>
				</div>
			</main>
		</div>'; // End custom-bottom-layout
		
		echo '</form>';
	}
	
	echo '</div>'; // End db-page
	include (__DIR__ . '/includes/footer.php');
}
