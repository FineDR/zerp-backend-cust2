<?php
/* Shows customer account/statement on screen rather than PDF. */

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Account');// Screen identification.
$ViewTopic = 'ARInquiries';// Filename in ManualContents.php's TOC.
$BookMark = 'CustomerAccount';// Anchor's id in the manual's html document.
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';

if (isset($_POST['TransAfterDate'])) {$_POST['TransAfterDate'] = ConvertSQLDate($_POST['TransAfterDate']);}

// always figure out the SQL required from the inputs available

if (!isset($_GET['CustomerID']) and !isset($_SESSION['CustomerID'])) {
	prnMsg(__('To display the account a customer must first be selected from the customer selection screen'), 'info');
	echo '<br /><div class="centre"><a href="', $RootPath, '/SelectCustomer.php">', __('Select a Customer Account to Display'), '</a></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {
	if (isset($_GET['CustomerID'])) {
		$_SESSION['CustomerID'] = stripslashes($_GET['CustomerID']);
	}
	$CustomerID = $_SESSION['CustomerID'];
}
//Check if the users have proper authority
if ($_SESSION['SalesmanLogin'] != '') {
	$ViewAllowed = false;
	$SQL = "SELECT salesman FROM custbranch WHERE debtorno = '" . $CustomerID . "'";
	$ErrMsg = __('Failed to retrieve sales data');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result)>0) {
		while($MyRow = DB_fetch_array($Result)) {
			if ($_SESSION['SalesmanLogin'] == $MyRow['salesman']) {
				$ViewAllowed = true;
			}
		}
	} else {
		prnMsg(__('There is no salesman data set for this customer'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (!$ViewAllowed) {
		prnMsg(__('You have no authority to review this customer account'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}


if (!isset($_POST['TransAfterDate'])) {
	$_POST['TransAfterDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') - $_SESSION['NumberOfMonthMustBeShown'], date('d'), date('Y')));
}

$Transactions = array();

/*now get all the settled transactions which were allocated this month */
$ErrMsg = __('There was a problem retrieving the transactions that were settled over the course of the last month for'). ' ' . $CustomerID . ' ' . __('from the database');
if ($_SESSION['Show_Settled_LastMonth']==1) {
	$SQL = "SELECT DISTINCT debtortrans.id,
						debtortrans.type,
						systypes.typename,
						debtortrans.branchcode,
						debtortrans.reference,
						debtortrans.invtext,
						debtortrans.order_,
						debtortrans.transno,
						debtortrans.trandate,
						debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst AS totalamount,
						debtortrans.alloc,
						debtortrans.balance AS balance,
						debtortrans.settled
				FROM debtortrans INNER JOIN systypes
					ON debtortrans.type=systypes.typeid
				INNER JOIN custallocns
					ON (debtortrans.id=custallocns.transid_allocfrom
						OR debtortrans.id=custallocns.transid_allocto)
				WHERE custallocns.datealloc >='" . FormatDateForSQL($_POST['TransAfterDate']) . "'
				AND debtortrans.debtorno='" . $CustomerID . "'
				AND debtortrans.settled=1
				ORDER BY debtortrans.id";
	$SetldTrans=DB_query($SQL, $ErrMsg);
	$NumberOfRecordsReturned = DB_num_rows($SetldTrans);
	while ($MyRow=DB_fetch_array($SetldTrans)) {
		$Transactions[] =  $MyRow;
	}
} else {
	$NumberOfRecordsReturned=0;
}

/*now get all the outstanding transaction ie Settled=0 */
$ErrMsg =  __('There was a problem retrieving the outstanding transactions for') . ' ' .	$CustomerID . ' '. __('from the database') . '.';
$SQL = "SELECT debtortrans.id,
			debtortrans.type,
			systypes.typename,
			debtortrans.branchcode,
			debtortrans.reference,
			debtortrans.invtext,
			debtortrans.order_,
			debtortrans.transno,
			debtortrans.trandate,
			debtortrans.ovamount+debtortrans.ovdiscount+debtortrans.ovfreight+debtortrans.ovgst as totalamount,
			debtortrans.alloc,
			debtortrans.balance as balance,
			debtortrans.settled
		FROM debtortrans INNER JOIN systypes
			ON debtortrans.type=systypes.typeid
		WHERE debtortrans.debtorno='" . $CustomerID . "'
		AND debtortrans.settled=0";
if ($_SESSION['SalesmanLogin'] != '') {
	$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$SQL .= " ORDER BY debtortrans.id";

$OstdgTrans=DB_query($SQL, $ErrMsg);
while ($MyRow=DB_fetch_array($OstdgTrans)) {
	$Transactions[] =  $MyRow;
}

$NumberOfRecordsReturned += DB_num_rows($OstdgTrans);

$SQL = "SELECT debtorsmaster.name,
			debtorsmaster.address1,
			debtorsmaster.address2,
			debtorsmaster.address3,
			debtorsmaster.address4,
			debtorsmaster.address5,
			debtorsmaster.address6,
			currencies.currency,
			currencies.decimalplaces,
			paymentterms.terms,
			debtorsmaster.creditlimit,
			holdreasons.dissallowinvoices,
			holdreasons.reasondescription,
			SUM(debtortrans.balance) AS balance,
			SUM(CASE WHEN (debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) > 0 THEN (debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) ELSE 0 END) AS total_invoices,
			SUM(CASE WHEN (debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) < 0 THEN (debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) ELSE 0 END) AS total_receipts,
			SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >=
				paymentterms.daysbeforedue
				THEN debtortrans.balance
				ELSE 0 END
			ELSE
				CASE WHEN TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1', 'MONTH') . "), " . interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))','DAY') . ")) >= 0
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
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1','MONTH') . "), " . interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))','DAY') .")) >= " . $_SESSION['PastDueDays1'] . ")
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
				CASE WHEN (TO_DAYS(Now()) - TO_DAYS(DATE_ADD(DATE_ADD(debtortrans.trandate, " . interval('1','MONTH') . "), " .
				interval('(paymentterms.dayinfollowingmonth - DAYOFMONTH(debtortrans.trandate))','DAY') . "))
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
		LEFT JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
		WHERE
			debtorsmaster.debtorno = '" . $CustomerID . "'";
if ($_SESSION['SalesmanLogin'] != '') {
	$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
}

$SQL .= " GROUP BY
			debtorsmaster.name,
			debtorsmaster.address1,
			debtorsmaster.address2,
			debtorsmaster.address3,
			debtorsmaster.address4,
			debtorsmaster.address5,
			debtorsmaster.address6,
			currencies.decimalplaces,
			currencies.currency,
			paymentterms.terms,
			paymentterms.daysbeforedue,
			paymentterms.dayinfollowingmonth,
			debtorsmaster.creditlimit,
			holdreasons.dissallowinvoices,
			holdreasons.reasondescription";
$ErrMsg = __('The customer details could not be retrieved by the SQL because');
$CustomerResult = DB_query($SQL, $ErrMsg);

$CustomerRecord = DB_fetch_array($CustomerResult);

	echo '<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Account statement and outstanding balance summary') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Select Customer') . '
				</a>
				<a href="' . $RootPath . '/PrintCustStatements.php?FromCust=' . $CustomerID . '&ToCust=' . $CustomerID . '&PrintPDF=Yes&EmailOrPrint=print&TransAfterDate=' . FormatDateForSQL($_POST['TransAfterDate']) . '" target="_blank" class="db-btn db-btn-primary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
					' . __('Print Statement') . '
				</a>
			</div>
		</div>';

	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					' . $CustomerRecord['name'] . ' (' . stripslashes($CustomerID) . ')
				</h3>
				<div class="db-header-actions">';
	if ($CustomerRecord['dissallowinvoices'] != 0) {
		echo '		<span class="db-badge db-badge-danger">' . __('ACCOUNT ON HOLD') . '</span>';
	}
	echo '			<span class="db-badge db-badge-info">' . $CustomerRecord['currency'] . '</span>
				</div>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label class="db-label">' . __('Billing Address') . '</label>
						<div class="db-field-value" style="white-space: pre-wrap;">' .
							$CustomerRecord['address1'] .
							($CustomerRecord['address2'] != '' ? "\n" . $CustomerRecord['address2'] : '') .
							($CustomerRecord['address3'] != '' ? "\n" . $CustomerRecord['address3'] : '') .
							"\n" . $CustomerRecord['address4'] .
							"\n" . $CustomerRecord['address5'] . ' ' . $CustomerRecord['address6'] .
						'</div>
					</div>
					<div>
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Payment Terms') . '</label>
								<div class="db-field-value">' . $CustomerRecord['terms'] . '</div>
							</div>
							<div class="db-field">
								<label class="db-label">' . __('Credit Limit') . '</label>
								<div class="db-field-value">' . locale_number_format($CustomerRecord['creditlimit'], 0) . '</div>
							</div>
							<div class="db-field">
								<label class="db-label">' . __('Credit Status') . '</label>
								<div class="db-field-value">' . $CustomerRecord['reasondescription'] . '</div>
							</div>
							<div class="db-field">
								<label class="db-label">' . __('Current Balance') . '</label>
								<div class="db-field-value" style="font-weight: 700; color: var(--primary);">' . locale_number_format($CustomerRecord['balance'], $CustomerRecord['decimalplaces']) . '</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>';

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					' . __('Statement Filters') . '
				</h3>
			</div>
			<div class="db-card-body">
				<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-field-group">
						<div class="db-field">
							<label class="db-label">' . __('Transactions After') . '</label>
							<input type="date" name="TransAfterDate" required="required" value="' . FormatDateForSQL($_POST['TransAfterDate']) . '" />
						</div>
						<div class="db-field" style="display: flex; align-items: flex-end;">
							<button type="submit" name="Refresh Inquiry" class="db-btn db-btn-primary" style="width: 100%;">' . __('Refresh Inquiry') . '</button>
						</div>
					</div>
				</form>
			</div>
		</div>';

/* Show a table of the invoices returned by the SQL. */

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-10l5 5 5-5m-5 5V3"></path></svg>
					' . __('Account Statement Transactions') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Type') . '</th>
								<th>' . __('No') . '</th>
								<th>' . __('Date') . '</th>
								<th>' . __('Branch') . '</th>
								<th>' . __('Reference') . '</th>
								<th>' . __('Comments') . '</th>
								<th class="number">' . __('Charges') . '</th>
								<th class="number">' . __('Credits') . '</th>
								<th class="number">' . __('Allocated') . '</th>
								<th class="number">' . __('Balance') . '</th>
								<th class="noPrint">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

$OutstandingOrSettled = '';
if ($_SESSION['InvoicePortraitFormat'] == 1) { //Invoice/credits in portrait
	$Orientation = 'portrait';
} else { //produce pdfs in landscape
	$Orientation = 'landscape';
}
foreach ($Transactions as $MyRow) {

	if ($MyRow['settled']==1 AND $OutstandingOrSettled=='') {
		echo '<tr style="background: var(--surface-alt); font-weight: 700;">
				<td colspan="11">' . __('Settled Transactions Since') . ' ' . $_POST['TransAfterDate'] . '</td>
			</tr>';
		$OutstandingOrSettled='Settled';
	} elseif (($OutstandingOrSettled=='Settled' OR $OutstandingOrSettled=='') AND $MyRow['settled']==0) {
		echo '<tr style="background: var(--surface-alt); font-weight: 700;">
				<td colspan="11">' . __('Outstanding Transactions') . '</td>
			</tr>';
		$OutstandingOrSettled='Outstanding';
	}

	$FormatedTranDate = ConvertSQLDate($MyRow['trandate']);

	if ($MyRow['type']==10) { //its an invoice
		echo '<tr>
			<td>' . __($MyRow['typename']) . '</td>
			<td>' . $MyRow['transno'] . '</td>
			<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
			<td>' . $MyRow['branchcode'] . '</td>
			<td>' . $MyRow['reference'] . '</td>
			<td style="width:200px">' . $MyRow['invtext'] . '</td>
			<td class="number">' . locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']) . '</td>
			<td>&nbsp;</td>
			<td class="number">' . locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']) . '</td>
			<td class="number">' . locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']) . '</td>
			<td class="number noPrint">
				<div class="db-action-group" style="justify-content: flex-end;">
					<a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Invoice&View=Yes" title="' . __('HTML') . '" target="_blank" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
					</a>
					<a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Invoice&amp;PrintPDF=True&orientation=' . $Orientation . '" title="' . __('PDF') . '" target="_blank" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					</a>
					<a href="' . $RootPath . '/EmailCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Invoice" title="' . __('Email') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
					</a>
				</div>
			</td>
		</tr>';

	} elseif ($MyRow['type'] == 11) {
		echo '<tr>
				<td>' . __($MyRow['typename']) . '</td>
				<td>' . $MyRow['transno'] . '</td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td>' . $MyRow['branchcode'] . '</td>
				<td>' . $MyRow['reference'] . '</td>
				<td style="width:200px">' . $MyRow['invtext'] . '</td>
				<td>&nbsp;</td>
				<td class="number">' . locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number noPrint">
					<div class="db-action-group" style="justify-content: flex-end;">
						<a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Credit" title="' . __('HTML') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
						</a>
						<a href="' . $RootPath . '/' . $PrintCustomerTransactionScript . '?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Credit&amp;PrintPDF=True" title="' . __('PDF') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
						</a>
						<a href="' . $RootPath . '/EmailCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=Credit" title="' . __('Email') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
						</a>
						<a href="' . $RootPath . '/CustomerAllocations.php?AllocTrans=' . $MyRow['id'] . '" title="' . __('Allocation') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M2 12h20"></path></svg>
						</a>
					</div>
				</td>
			</tr>';

	} elseif ($MyRow['type'] == 12 and $MyRow['totalamount'] < 0) {
		echo '<tr>
				<td>' . __($MyRow['typename']) . '</td>
				<td>' . $MyRow['transno'] . '</td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td>' . $MyRow['branchcode'] . '</td>
				<td>' . $MyRow['reference'] . '</td>
				<td style="width:200px">' . $MyRow['invtext'] . '</td>
				<td>&nbsp;</td>
				<td class="number">' . locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number noPrint">
					<div class="db-action-group" style="justify-content: flex-end;">
						<a href="' . $RootPath . '/CustomerAllocations.php?AllocTrans=' . $MyRow['id'] . '" title="' . __('Allocation') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M2 12h20"></path></svg>
						</a>
					</div>
				</td>
			</tr>';

	} elseif ($MyRow['type'] == 12 and $MyRow['totalamount'] > 0) {
		echo '<tr>
				<td>' . __($MyRow['typename']) . '</td>
				<td>' . $MyRow['transno'] . '</td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td>' . $MyRow['branchcode'] . '</td>
				<td>' . $MyRow['reference'] . '</td>
				<td style="width:200px">' . $MyRow['invtext'] . '</td>
				<td class="number">' . locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']) . '</td>
				<td>&nbsp;</td>
				<td class="number">' . locale_number_format($MyRow['alloc'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['balance'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number noPrint">&nbsp;</td>
			</tr>';
	}
}

	echo '</tbody></table></div></div></div>'; // Close db-table, db-table-wrapper, db-card-body, card-v2

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
					' . __('Aging Summary') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th class="number">' . __('Total Balance') . '</th>
								<th class="number">' . __('Current') . '</th>
								<th class="number">' . __('Now Due') . '</th>
								<th class="number">' . $_SESSION['PastDueDays1'] . '-' . $_SESSION['PastDueDays2'] . ' Days</th>
								<th class="number">' . __('Over') . ' ' . $_SESSION['PastDueDays2'] . ' Days</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="number">' . locale_number_format($CustomerRecord['balance'], $CustomerRecord['decimalplaces']) . '</td>
								<td class="number">' . locale_number_format(($CustomerRecord['balance'] - $CustomerRecord['due']), $CustomerRecord['decimalplaces']) . '</td>
								<td class="number">' . locale_number_format(($CustomerRecord['due'] - $CustomerRecord['overdue1']), $CustomerRecord['decimalplaces']) . '</td>
								<td class="number">' . locale_number_format(($CustomerRecord['overdue1'] - $CustomerRecord['overdue2']), $CustomerRecord['decimalplaces']) . '</td>
								<td class="number text-danger">' . locale_number_format($CustomerRecord['overdue2'], $CustomerRecord['decimalplaces']) . '</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>';

		echo '</div>'; // Close db-page
include(__DIR__ . '/includes/footer.php');
