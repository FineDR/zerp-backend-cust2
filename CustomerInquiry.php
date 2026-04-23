<?php

/* Shows the customers account transactions with balances outstanding, links available to drill down to invoice/credit note or email invoices/credit notes. */

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Inquiry');// Screen identification.
$ViewTopic = 'ARInquiries';// Filename's id in ManualContents.php's TOC.
$BookMark = 'CustomerInquiry';// Anchor's id in the manual's html document.
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';

if (isset($_POST['TransAfterDate'])){$_POST['TransAfterDate'] = ConvertSQLDate($_POST['TransAfterDate']);}

// always figure out the SQL required from the inputs available

if (!isset($_GET['CustomerID']) and !isset($_SESSION['CustomerID'])) {
	prnMsg(__('To display the enquiry a customer must first be selected from the customer selection screen'), 'info');
	echo '<br /><div class="centre"><a href="', $RootPath, '/SelectCustomer.php">', __('Select a Customer to Inquire On'), '</a></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {
	if (isset($_GET['CustomerID'])) {
		$_SESSION['CustomerID'] = stripslashes($_GET['CustomerID']);
	}
	$CustomerID = $_SESSION['CustomerID'];
}
//Check if the users have proper authority
if ($_SESSION['SalesmanLogin'] !=  '') {
	$ViewAllowed = false;
	$SQL = "SELECT salesman FROM custbranch WHERE debtorno = '" . $CustomerID . "'";
	echo 'SQL1 ->'.$SQL;
	$ErrMsg = __('Failed to retrieve sales data');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result)>0) {
		while($MyRow = DB_fetch_array($Result)) {
			if ($_SESSION['SalesmanLogin'] == $MyRow['salesman']){
				$ViewAllowed = true;
			}
		}
	} else {
		prnMsg(__('There is no salesman data set for this debtor'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (!$ViewAllowed){
		prnMsg(__('You have no authority to review this data'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}


if (isset($_GET['Status'])) {
	if (is_numeric($_GET['Status'])) {
		$_POST['Status'] = $_GET['Status'];
	}
} elseif (isset($_POST['Status'])) {
	if ($_POST['Status'] == '' or $_POST['Status'] == 1 or $_POST['Status'] == 0) {
		$Status = $_POST['Status'];
	} else {
		prnMsg(__('The balance status should be all or zero balance or not zero balance'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
} else {
	$_POST['Status'] = '';
}

if (!isset($_POST['TransAfterDate'])) {
	$_POST['TransAfterDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') - $_SESSION['NumberOfMonthMustBeShown'], date('d'), date('Y')));
}

$SQL = "SELECT debtorsmaster.name,
		currencies.currency,
		currencies.decimalplaces,
		paymentterms.terms,
		debtorsmaster.creditlimit,
		holdreasons.dissallowinvoices,
		holdreasons.reasondescription,
		SUM(debtortrans.balance) AS balance,
		SUM(CASE WHEN (paymentterms.daysbeforedue > 0) THEN
			CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue
			THEN debtortrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN debtortrans.balance ELSE 0 END
		END) AS due,
		SUM(CASE WHEN (paymentterms.daysbeforedue > 0) THEN
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
			AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
			THEN debtortrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . "
			THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount
			- debtortrans.alloc ELSE 0 END
		END) AS overdue1,
		SUM(CASE WHEN (paymentterms.daysbeforedue > 0) THEN
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue
			AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN debtortrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate),paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN debtortrans.balance ELSE 0 END
		END) AS overdue2
		FROM debtorsmaster,
	 			paymentterms,
	 			holdreasons,
	 			currencies,
	 			debtortrans
		WHERE  debtorsmaster.paymentterms = paymentterms.termsindicator
	 		AND debtorsmaster.currcode = currencies.currabrev
	 		AND debtorsmaster.holdreason = holdreasons.reasoncode
	 		AND debtorsmaster.debtorno = '" . $CustomerID . "'
	 		AND debtorsmaster.debtorno = debtortrans.debtorno
			GROUP BY debtorsmaster.name,
			currencies.currency,
			paymentterms.terms,
			paymentterms.daysbeforedue,
			paymentterms.dayinfollowingmonth,
			debtorsmaster.creditlimit,
			holdreasons.dissallowinvoices,
			holdreasons.reasondescription";
echo 'SQL2 ->'.$SQL;
$ErrMsg = __('The customer details could not be retrieved by the SQL because');
$CustomerResult = DB_query($SQL, $ErrMsg);

if (DB_num_rows($CustomerResult) == 0) {

	/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

	$NIL_BALANCE = true;

	$SQL = "SELECT debtorsmaster.name,
					debtorsmaster.currcode,
					currencies.currency,
					currencies.decimalplaces,
					paymentterms.terms,
					debtorsmaster.creditlimit,
					holdreasons.dissallowinvoices,
					holdreasons.reasondescription
			FROM debtorsmaster INNER JOIN paymentterms
			ON debtorsmaster.paymentterms = paymentterms.termsindicator
			INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
			INNER JOIN holdreasons
			ON debtorsmaster.holdreason = holdreasons.reasoncode
			WHERE debtorsmaster.debtorno = '" . $CustomerID . "'";
echo 'SQL3 ->'.$SQL;
	$ErrMsg = __('The customer details could not be retrieved by the SQL because');
	$CustomerResult = DB_query($SQL, $ErrMsg);

} else {
	$NIL_BALANCE = false;
}

$CustomerRecord = DB_fetch_array($CustomerResult);

if ($NIL_BALANCE == true) {
	$CustomerRecord['balance'] = 0;
	$CustomerRecord['due'] = 0;
	$CustomerRecord['overdue1'] = 0;
	$CustomerRecord['overdue2'] = 0;
}

	echo '<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('View detailed transaction history and account balance') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Select Customer') . '
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
				<div class="db-grid db-grid-4">
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
						<label class="db-label">' . __('Total Balance') . '</label>
						<div class="db-field-value" style="font-weight: 700; color: var(--primary);">' . locale_number_format($CustomerRecord['balance'], $CustomerRecord['decimalplaces']) . '</div>
					</div>
				</div>

				<div class="db-table-wrapper" style="margin-top: var(--space-4);">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Current') . '</th>
								<th>' . __('Now Due') . '</th>
								<th>' . $_SESSION['PastDueDays1'] . '-' . $_SESSION['PastDueDays2'] . ' Days</th>
								<th> > ' . $_SESSION['PastDueDays2'] . ' Days</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>' . locale_number_format(($CustomerRecord['balance'] - $CustomerRecord['due']), $CustomerRecord['decimalplaces']) . '</td>
								<td>' . locale_number_format(($CustomerRecord['due'] - $CustomerRecord['overdue1']), $CustomerRecord['decimalplaces']) . '</td>
								<td>' . locale_number_format(($CustomerRecord['overdue1'] - $CustomerRecord['overdue2']), $CustomerRecord['decimalplaces']) . '</td>
								<td class="text-danger">' . locale_number_format($CustomerRecord['overdue2'], $CustomerRecord['decimalplaces']) . '</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>';

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					' . __('Inquiry Filters') . '
				</h3>
			</div>
			<div class="db-card-body">
				<form onSubmit="return VerifyForm(this);" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="noPrint">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-field-group">
						<div class="db-field">
							<label class="db-label">' . __('Transactions After') . '</label>
							<input required="required" type="date" name="TransAfterDate" value="' . FormatDateForSQL($_POST['TransAfterDate']) . '" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Balance Status') . '</label>
							<select name="Status">
								<option ' . ($_POST['Status'] == '' ? 'selected="selected"' : '') . ' value="">' . __('All') . '</option>
								<option ' . ($_POST['Status'] == '1' ? 'selected="selected"' : '') . ' value="1">' . __('Invoices not fully allocated') . '</option>
								<option ' . ($_POST['Status'] == '0' ? 'selected="selected"' : '') . ' value="0">' . __('Invoices fully allocated') . '</option>
							</select>
						</div>
						<div class="db-field" style="display: flex; align-items: flex-end;">
							<button type="submit" name="Refresh Inquiry" class="db-btn db-btn-primary" style="width: 100%;">' . __('Refresh Inquiry') . '</button>
						</div>
					</div>
				</form>
			</div>
		</div>';

$DateAfterCriteria = FormatDateForSQL($_POST['TransAfterDate']);

$SQL = "SELECT systypes.typename,
				debtortrans.id,
				debtortrans.type,
				debtortrans.transno,
				debtortrans.branchcode,
				debtortrans.trandate,
				debtortrans.reference,
				debtortrans.invtext,
				debtortrans.order_,
				salesorders.customerref,
				debtortrans.rate,
				(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) AS totalamount,
				debtortrans.alloc AS allocated
			FROM debtortrans
			INNER JOIN systypes
				ON debtortrans.type = systypes.typeid
			LEFT JOIN salesorders
				ON salesorders.orderno=debtortrans.order_
			WHERE debtortrans.debtorno = '" . $CustomerID . "'
				AND debtortrans.trandate >= '" . $DateAfterCriteria . "'
				ORDER BY debtortrans.trandate,
					debtortrans.id";
echo 'SQL4 ->'.$SQL;
$ErrMsg = __('No transactions were returned by the SQL because');
$TransResult = DB_query($SQL, $ErrMsg);

if (DB_num_rows($TransResult) == 0) {
	echo '<div class="centre">', __('There are no transactions to display since'), ' ', $_POST['TransAfterDate'], '</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

/* Show a table of the invoices returned by the SQL. */

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M2 17V3a2 2 0 0 1 2-2h13.2a2 2 0 0 1 2 2v14m-12 4h12l1-4H3l1 4Z"></path></svg>
					' . __('Transaction History') . '
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
								<th class="number">' . __('Total') . '</th>
								<th class="number">' . __('Alloc') . '</th>
								<th class="number">' . __('Balance') . '</th>
								<th class="number noPrint">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($TransResult)) {

		$FormatedTranDate = ConvertSQLDate($MyRow['trandate']);

		if ($_SESSION['InvoicePortraitFormat'] == 1) { //Invoice/credits in portrait
			$Orientation = 'portrait';
		} else { //produce pdfs in landscape
			$Orientation = 'landscape';
		}

		// Define badge classes based on transaction type
		$BadgeClass = 'db-badge-secondary';
		if ($MyRow['type'] == 10) $BadgeClass = 'db-badge-success'; // Invoice
		if ($MyRow['type'] == 11) $BadgeClass = 'db-badge-danger';  // Credit Note
		if ($MyRow['type'] == 12) $BadgeClass = 'db-badge-info';    // Receipt

		$Actions = '';

		/* if the user is allowed to create credits for invoices */
		if (in_array($_SESSION['PageSecurityArray']['Credit_Invoice.php'], $_SESSION['AllowedPageSecurityTokens']) and $MyRow['type'] == 10) {
			$Actions .= '<a href="' . $RootPath . '/Credit_Invoice.php?InvoiceNumber=' . $MyRow['transno'] . '" title="' . __('Credit') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"></path></svg>
						</a>';
		}

		// Standard View (HTML) Action
		$Actions .= '<a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=' . ($MyRow['type'] == 11 ? 'Credit' : 'Invoice') . '&View=Yes" title="' . __('HTML') . '" target="_blank" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
					</a>';

		// PDF Action
		$Actions .= '<a href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=' . ($MyRow['type'] == 11 ? 'Credit' : 'Invoice') . '&amp;PrintPDF=True&orientation=' . $Orientation . '" title="' . __('PDF') . '" target="_blank" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					</a>';

		// Email Action
		$Actions .= '<a href="' . $RootPath . '/EmailCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&amp;InvOrCredit=' . ($MyRow['type'] == 11 ? 'Credit' : 'Invoice') . '" title="' . __('Email') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
					</a>';

		// GL Action - if allowed
		if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1 and in_array($_SESSION['PageSecurityArray']['GLTransInquiry.php'], $_SESSION['AllowedPageSecurityTokens'])) {
			$Actions .= '<a href="' . $RootPath . '/GLTransInquiry.php?TypeID=' . $MyRow['type'] . '&amp;TransNo=' . $MyRow['transno'] . '" title="' . __('GL') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
						</a>';
		}

		// Allocation Action for receipts and credits
		if (($MyRow['type'] == 12 or $MyRow['type'] == 11) and $MyRow['totalamount'] < 0) {
			$Actions .= '<a href="' . $RootPath . '/CustomerAllocations.php?AllocTrans=' . $MyRow['id'] . '" title="' . __('Allocation') . '" class="db-btn db-btn-secondary" style="padding: 4px 8px;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M2 12h20"></path></svg>
						</a>';
		}

		echo '<tr>
				<td><span class="db-badge ' . $BadgeClass . '">' . __($MyRow['typename']) . '</span></td>
				<td><a href="' . $RootPath . '/CustWhereAlloc.php?TransType=' . $MyRow['type'] . '&TransNo=' . $MyRow['transno'] . '" target="_blank">' . $MyRow['transno'] . '</a></td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td>' . $MyRow['branchcode'] . '</td>
				<td>' . $MyRow['reference'] . '</td>
				<td style="width:200px">' . $MyRow['invtext'] . '</td>
				<td class="number">' . locale_number_format($MyRow['totalamount'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['allocated'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['totalamount'] - $MyRow['allocated'], $CustomerRecord['decimalplaces']) . '</td>
				<td class="number noPrint">
					<div class="db-action-group" style="justify-content: flex-end;">
						' . $Actions . '
					</div>
				</td>
			</tr>';

	}

//end of while loop

	echo '</tbody></table></div></div></div></div>'; // Close db-table, db-table-wrapper, db-card-body, card-v2, db-page
	include(__DIR__ . '/includes/footer.php');
