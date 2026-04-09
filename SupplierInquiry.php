<?php

/* Inquiry showing invoices, credit notes and payments made to suppliers together with the amounts outstanding. */

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Inquiry');
$ViewTopic = 'AccountsPayable';// RChacon: Is there any content for Supplier Inquiry?
$BookMark = 'AccountsPayable';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['TransAfterDate'])){$_POST['TransAfterDate'] = ConvertSQLDate($_POST['TransAfterDate']);}

// always figure out the SQL required from the inputs available

if (!isset($_GET['SupplierID']) AND !isset($_SESSION['SupplierID'])) {
	echo '<br />' . __('To display the enquiry a Supplier must first be selected from the Supplier selection screen') .
		 '<br />
			<div class="centre">
				<a href="' . $RootPath . '/SelectSupplier.php">' . __('Select a Supplier to Inquire On') . '</a>
			</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {
	if (isset($_GET['SupplierID'])) {
		$_SESSION['SupplierID'] = $_GET['SupplierID'];
	}
	$SupplierID = $_SESSION['SupplierID'];
}

if (isset($_GET['FromDate'])) {
	$_POST['TransAfterDate']=$_GET['FromDate'];
}
if (!isset($_POST['TransAfterDate']) OR !Is_Date($_POST['TransAfterDate'])) {
	$_POST['TransAfterDate'] = date($_SESSION['DefaultDateFormat'],mktime(0,0,0,date('m')-12,date('d'),date('Y')));
}

$SQL = "SELECT suppliers.suppname,
		suppliers.currcode,
		currencies.currency,
		currencies.decimalplaces AS currdecimalplaces,
		paymentterms.terms,
		SUM(supptrans.balance) AS balance,
		SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
			CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue
			THEN supptrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN supptrans.balance ELSE 0 END
		END) AS due,
		SUM(CASE WHEN paymentterms.daysbeforedue > 0  THEN
			CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) > paymentterms.daysbeforedue
					AND (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
			THEN supptrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= '" . $_SESSION['PastDueDays1'] . "'
			THEN supptrans.balance ELSE 0 END
		END) AS overdue1,
		Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
			THEN supptrans.balance ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= '" . $_SESSION['PastDueDays2'] . "'
			THEN supptrans.balance ELSE 0 END
		END ) AS overdue2
		FROM suppliers INNER JOIN paymentterms
		ON suppliers.paymentterms = paymentterms.termsindicator
     	INNER JOIN currencies
     	ON suppliers.currcode = currencies.currabrev
     	INNER JOIN supptrans
     	ON suppliers.supplierid = supptrans.supplierno
		WHERE suppliers.supplierid = '" . $SupplierID . "'
		GROUP BY suppliers.suppname,
      			currencies.currency,
      			currencies.decimalplaces,
      			paymentterms.terms,
      			paymentterms.daysbeforedue,
      			paymentterms.dayinfollowingmonth";
$ErrMsg = __('The supplier details could not be retrieved by the SQL because');
$SupplierResult = DB_query($SQL, $ErrMsg);

if (DB_num_rows($SupplierResult) == 0) {

	/*Because there is no balance - so just retrieve the header information about the Supplier - the choice is do one query to get the balance and transactions for those Suppliers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

	$NIL_BALANCE = true;

	$SQL = "SELECT suppliers.suppname,
					suppliers.currcode,
					currencies.currency,
					currencies.decimalplaces AS currdecimalplaces,
					paymentterms.terms
			FROM suppliers INNER JOIN paymentterms
		    ON suppliers.paymentterms = paymentterms.termsindicator
		    INNER JOIN currencies
		    ON suppliers.currcode = currencies.currabrev
			WHERE suppliers.supplierid = '" . $SupplierID . "'";

	$ErrMsg = __('The supplier details could not be retrieved by the SQL because');

	$SupplierResult = DB_query($SQL, $ErrMsg);

} else {
	$NIL_BALANCE = false;
}

$SupplierRecord = DB_fetch_array($SupplierResult);

if ($NIL_BALANCE == true) {
	$SupplierRecord['balance'] = 0;
	$SupplierRecord['due'] = 0;
	$SupplierRecord['overdue1'] = 0;
	$SupplierRecord['overdue2'] = 0;
}
include(__DIR__ . '/includes/CurrenciesArray.php'); // To get the currency name from the currency code.

echo '<div class="db-page">';
echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg> ' . __('Supplier Inquiry') . '</h2>
			<p class="db-page-subtitle">' . __('Inquiry for') . ' <span class="val-bold">' . $SupplierID . ' - ' . $SupplierRecord['suppname'] . '</span> &mdash; ' . $SupplierRecord['currcode'] . ' (' . $CurrencyName[$SupplierRecord['currcode']] . ')</p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Change Supplier') . '
			</a>
		</div>
	</div>';

if (isset($_GET['HoldType']) AND isset($_GET['HoldTrans'])) {
	if ($_GET['HoldStatus'] == __('Hold')) {
		$SQL = "UPDATE supptrans SET hold=1
				WHERE type='" . $_GET['HoldType'] . "'
				AND transno='" . $_GET['HoldTrans'] . "'";
	} elseif ($_GET['HoldStatus'] == __('Release')) {
		$SQL = "UPDATE supptrans SET hold=0
				WHERE type='" . $_GET['HoldType'] . "'
				AND transno='" . $_GET['HoldTrans'] . "'";
	}
	$ErrMsg = __('The Supplier Transactions could not be updated because');
	$UpdateResult = DB_query($SQL, $ErrMsg);
}

// Balance KPI Cards
echo '<div class="db-grid db-grid-5" style="margin-top: var(--space-6);">';
$kpis = [
	['label' => __('Total Balance'), 'value' => $SupplierRecord['balance'], 'color' => 'var(--primary)', 'icon' => 'bank'],
	['label' => __('Current'), 'value' => ($SupplierRecord['balance'] - $SupplierRecord['due']), 'color' => 'var(--success)', 'icon' => 'check-circle'],
	['label' => __('Due Now'), 'value' => ($SupplierRecord['due']-$SupplierRecord['overdue1']), 'color' => 'var(--warning)', 'icon' => 'clock'],
	['label' => __('30-60 Days'), 'value' => ($SupplierRecord['overdue1']-$SupplierRecord['overdue2']), 'color' => 'var(--danger)', 'icon' => 'alert-circle'],
	['label' => __('60+ Days'), 'value' => $SupplierRecord['overdue2'], 'color' => 'var(--danger)', 'icon' => 'alert-triangle']
];

foreach ($kpis as $kpi) {
	echo '<div class="db-card kpi-card">
			<div class="db-card-body" style="padding: var(--space-4); display: flex; flex-direction: column; gap: var(--space-2);">
				<span style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em;">' . $kpi['label'] . '</span>
				<span style="font-size: 1.25rem; font-weight: 700; color: ' . $kpi['color'] . ';">' . locale_number_format($kpi['value'], $SupplierRecord['currdecimalplaces']) . '</span>
			</div>
		</div>';
}
echo '</div>';

echo '<div class="db-card" style="margin-top: var(--space-6);">
		<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
			<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Transaction History') . '</h3>
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" style="display:flex; align-items:center; gap:var(--space-2);">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<label style="font-size:0.875rem; color:var(--text-muted);">' . __('From') . ':</label>
				<input type="date" name="TransAfterDate" value="' . FormatDateForSQL($_POST['TransAfterDate']) . '" style="padding:var(--space-1) var(--space-2); font-size:0.875rem;" />
				<button type="submit" name="Refresh Inquiry" class="db-btn db-btn-secondary" style="padding:var(--space-1) var(--space-3); font-size:0.875rem;">' . __('Refresh') . '</button>
			</form>
		</div>
		<div class="db-table-wrapper">
			<table class="db-table">
				<thead>
					<tr>
						<th>' . __('Date') . '</th>
						<th>' . __('Type') . '</th>
						<th>' . __('Ref/Num') . '</th>
						<th>' . __('Comments') . '</th>
						<th class="number">' . __('Total') . '</th>
						<th class="number">' . __('Allocated') . '</th>
						<th class="number">' . __('Outstanding') . '</th>
						<th class="noPrint" style="text-align: center;">' . __('Actions') . '</th>
					</tr>
				</thead>
				<tbody>';

$AuthSQL = "SELECT offhold
			FROM purchorderauth
			WHERE userid='" . $_SESSION['UserID'] . "'
			AND currabrev='" . $SupplierRecord['currcode']."'";
$AuthResult = DB_query($AuthSQL);
$AuthRow = DB_fetch_array($AuthResult);

$j = 1;

	if ($MyRow['hold'] == 1) {
		echo '<tr style="background-color: rgba(239, 68, 68, 0.05);">';
	} else {
		echo '<tr class="striped_row">';
	}

	$Outstanding = $MyRow['totalamount'] - $MyRow['allocated'];
	$BalanceStyle = $Outstanding > 0 ? 'font-weight: 700; color: var(--danger);' : '';

	echo '<td class="date">' . ConvertSQLDate($MyRow['trandate']) . '</td>
		<td>' . __($MyRow['typename']) . '</td>
		<td><div class="val-bold"><a href="' . $RootPath . '/SuppWhereAlloc.php?TransType=' . $MyRow['type'] . '&TransNo=' . $MyRow['transno'] . '">' . $MyRow['transno'] . '</a></div><div style="font-size:0.75rem; color:var(--text-muted);">' . $MyRow['suppreference'] . '</div></td>
		<td style="font-size: 0.875rem;">' . $MyRow['transtext'] . '</td>
		<td class="number">' . locale_number_format($MyRow['totalamount'], $SupplierRecord['currdecimalplaces']) . '</td>
		<td class="number">' . locale_number_format($MyRow['allocated'], $SupplierRecord['currdecimalplaces']) . '</td>
		<td class="number" style="' . $BalanceStyle . '">' . locale_number_format($Outstanding, $SupplierRecord['currdecimalplaces']) . '</td>';

	echo '<td class="noPrint" style="white-space: nowrap; text-align: center;">';
	
	// Actions Group
	echo '<div style="display: flex; gap: var(--space-1); justify-content: center;">';
	
	// GL Entries
	if ($_SESSION['CompanyRecord']['gllink_creditors'] == true) {
		echo '<a href="' . $RootPath . '/GLTransInquiry.php?TypeID=' . $MyRow['type'] . '&amp;TransNo=' . $MyRow['transno'] . '" class="db-btn-icon" title="' . __('GL Entries') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg></a>';
	}

	if ($MyRow['type'] == 20) { // Invoice
		if ($MyRow['totalamount'] == $MyRow['allocated']) {
			echo '<a href="' . $RootPath . '/PaymentAllocations.php?SuppID=' . $MyRow['supplierno'] . '&amp;InvID=' . $MyRow['suppreference'] . '" class="db-btn-icon" title="' . __('View Payments') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></a>';
		} else {
			$HoldColor = ($HoldValue == __('Release')) ? 'color: var(--danger);' : 'color: var(--primary);';
			echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES,'UTF-8') . '?HoldType=' . $MyRow['type'] . '&amp;HoldTrans=' . $MyRow['transno'] . '&amp;HoldStatus=' . $HoldValue . '&amp;FromDate=' . $_POST['TransAfterDate'] . '" class="db-btn-icon" style="' . $HoldColor . '" title="' . $HoldValue . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></a>';
		}
	} else { // Credit Note or Payment
		echo '<a href="' . $RootPath . '/SupplierAllocations.php?AllocTrans=' . $MyRow['id'] . '" class="db-btn-icon" title="' . __('Allocate') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 12 12 16 14"></polyline></svg></a>';
	}
	
	echo '</div></td>';
	echo '</tr>';
}
// End of while loop

echo '</tbody></table></div></div></div>'; // End db-table-wrapper, db-card, db-page
include(__DIR__ . '/includes/footer.php');
