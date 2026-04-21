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
	$_POST['TransAfterDate'] = date($_SESSION['DefaultDateFormat'],mktime(0,0,0,date('m')-24,date('d'),date('Y')));
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

include(__DIR__ . '/includes/CurrenciesArray.php'); // To get the currency name from the currency code.

echo '<style>
	#Header_SubBreadcrumb { display: none !important; }
	.db-page { 
		height: calc(100vh - 60px); 
		display: flex; 
		flex-direction: column; 
		overflow: hidden; 
		background: var(--bg-main);
	}
	.db-workspace { 
		flex: 1; 
		overflow-y: auto; 
		padding: var(--space-6);
		background: var(--bg-main);
	}
	.kpi-card-v3 {
		background: var(--surface);
		border: 1px solid var(--border-soft);
		border-radius: 16px;
		padding: var(--space-5);
		display: flex;
		align-items: center;
		gap: var(--space-4);
		transition: all 0.2s ease;
		box-shadow: var(--shadow-sm);
	}
	.kpi-card-v3:hover {
		border-color: var(--primary-soft);
		box-shadow: var(--shadow-md);
		transform: translateY(-2px);
	}
</style>';

$SupplierNameSQL = "SELECT suppname FROM suppliers WHERE supplierid = '" . $SupplierID . "'";
$SupplierNameResult = DB_query($SupplierNameSQL);
$SupplierNameRow = DB_fetch_array($SupplierNameResult);
$SupplierName = $SupplierNameRow['suppname'];

echo '<div class="db-page">';
echo '<div class="db-page-header" style="padding: var(--space-6) var(--space-6) var(--space-4); background: var(--surface); border-bottom: 1px solid var(--border-soft);">
		<div class="db-header-row">
			<div class="db-header-main">
				<h1 class="db-page-title" style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; color: var(--text-main);">' . __('Supplier Inquiry') . '</h1>
				<p class="db-page-subtitle" style="font-size: 0.875rem; color: var(--text-muted); margin-top: 4px;">' . __('Tracking account history and liquidity for') . ' <span style="color:var(--primary); font-weight: 700;">' . $SupplierID . ' — ' . $SupplierName . '</span></p>
			</div>
			<div class="db-header-actions" style="display: flex; gap: var(--space-3); align-items: center;">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" style="display:flex; align-items:center; gap:var(--space-3); background: var(--bg-main); padding: 6px 16px; border-radius: 12px; border: 1px solid var(--border-soft);">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div style="display:flex; align-items:center; gap:var(--space-3);">
						<span style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">' . __('Since') . '</span>
						<input type="date" name="TransAfterDate" value="' . FormatDateForSQL($_POST['TransAfterDate']) . '" style="background: transparent; border: none; font-size: 0.875rem; color: var(--text-main); font-weight: 600; outline: none;" />
					</div>
					<button type="submit" name="Refresh Inquiry" class="db-btn-icon" style="color: var(--primary);" title="' . __('Filter History') . '">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					</button>
				</form>
				<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary" style="height: 42px; padding: 0 16px;">' . __('Switch Supplier') . '</a>
			</div>
		</div>
	</div>';

echo '<div class="db-workspace">';

echo '<div class="db-grid db-grid-5" style="gap: var(--space-4); margin-bottom: var(--space-6);">';
$kpis = [
	['label' => __('Total Balance'), 'value' => $SupplierRecord['balance'], 'color' => 'var(--primary)', 'icon' => 'M12 1v22M19 5H5v14h14V5zM9 9h6M9 13h6M9 17h6'],
	['label' => __('Current'), 'value' => ($SupplierRecord['balance'] - $SupplierRecord['due']), 'color' => 'var(--success)', 'icon' => 'M22 11.08V12a10 10 0 1 1-5.93-9.14 M22 4L12 14.01 9 11.01'],
	['label' => __('Due Now'), 'value' => ($SupplierRecord['due']-$SupplierRecord['overdue1']), 'color' => 'var(--warning)', 'icon' => 'M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z M12 6v6l4 2'],
	['label' => __('30-60 Days'), 'value' => ($SupplierRecord['overdue1']-$SupplierRecord['overdue2']), 'color' => 'var(--danger)', 'icon' => 'M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z M12 9v4 M12 17h.01'],
	['label' => __('60+ Days'), 'value' => $SupplierRecord['overdue2'], 'color' => 'var(--danger)', 'icon' => 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9 M13.73 21a2 2 0 0 1-3.46 0']
];

foreach ($kpis as $kpi) {
	echo '<div class="kpi-card-v3">
			<div style="width: 48px; height: 48px; border-radius: 14px; background: ' . $kpi['color'] . '15; color: ' . $kpi['color'] . '; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="' . $kpi['icon'] . '"/></svg>
			</div>
			<div style="display: flex; flex-direction: column; gap: 2px;">
				<span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">' . $kpi['label'] . '</span>
				<span style="font-size: 1.25rem; font-weight: 900; color: var(--text-main); letter-spacing: -0.02em;">' . locale_number_format($kpi['value'], $SupplierRecord['currdecimalplaces']) . '</span>
			</div>
		</div>';
}
echo '</div>';
;

echo '<div class="card-v2" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; box-shadow: var(--shadow-md); border: 1px solid var(--border-soft);">
		<div class="card-header-v2" style="padding: var(--space-5) var(--space-6); background: var(--surface); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-soft); flex-shrink: 0;">
			<div style="display: flex; align-items: center; gap: 12px;">
				<div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
				</div>
				<h3 class="db-card-title" style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">' . __('Transaction History') . '</h3>
			</div>
			<div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; background: var(--surface-alt); padding: 4px 12px; border-radius: 20px; border: 1px solid var(--border-soft);">
				' . __('Functional Currency') . ': ' . $_SESSION['CompanyRecord']['currencydefault'] . '
			</div>
		</div>
		<div class="db-table-wrapper" style="flex: 1; overflow-y: auto; background: var(--surface);">
			<table class="db-table" style="border-collapse: separate; border-spacing: 0;">
				<thead style="position: sticky; top: 0; z-index: 10; background: var(--surface-alt); box-shadow: 0 1px 0 var(--border-soft);">
					<tr>
						<th style="padding: 16px 20px;">' . __('Date') . '</th>
						<th>' . __('Type') . '</th>
						<th>' . __('Identifier') . '</th>
						<th>' . __('Comments') . '</th>
						<th class="text-right">' . __('Amount') . '</th>
						<th class="text-right">' . __('Allocated') . '</th>
						<th class="text-right">' . __('Outstanding') . '</th>
						<th class="text-center noPrint">' . __('Actions') . '</th>
					</tr>
				</thead>
				<tbody>';

$SQL = "SELECT supptrans.id,
			supptrans.transno,
			supptrans.type,
			systypes.typename,
			supptrans.trandate,
			supptrans.suppreference,
			supptrans.transtext,
			supptrans.ovamount + supptrans.ovgst AS totalamount,
			supptrans.allocated,
			supptrans.hold
		FROM supptrans
		INNER JOIN systypes ON supptrans.type = systypes.typeid
		WHERE supptrans.supplierno = '" . $SupplierID . "'
		AND supptrans.trandate >= '" . FormatDateForSQL($_POST['TransAfterDate']) . "'
		ORDER BY supptrans.trandate DESC";

$TransResult = DB_query($SQL);

if (DB_num_rows($TransResult) == 0) {
	echo '<tr><td colspan="8" style="padding: var(--space-12); text-align: center; color: var(--text-muted); font-size: 0.875rem;">' . __('No transactions found for the selected period.') . '</td></tr>';
} else {
	while ($MyRow = DB_fetch_array($TransResult)) {
		$Outstanding = $MyRow['totalamount'] - $MyRow['allocated'];
		$IsOutstanding = ($Outstanding != 0);
		$StatusClass = ($MyRow['hold'] == 1) ? 'style="background: rgba(239, 68, 68, 0.03);"' : '';

		echo '<tr ' . $StatusClass . '>
				<td style="padding: 14px 20px; font-weight: 600; font-size: 0.8125rem;">' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td>
					<div style="display: flex; align-items: center; gap: 8px;">
						<div style="width: 6px; height: 6px; border-radius: 50%; background: ' . ($MyRow['type'] == 20 ? 'var(--primary)' : 'var(--success)') . ';"></div>
						<span style="font-weight: 700; font-size: 0.75rem; text-transform: uppercase; color: var(--text-main);">' . __($MyRow['typename']) . '</span>
					</div>
				</td>
				<td>
					<div style="font-weight: 800; font-size: 0.875rem;"><a href="' . $RootPath . '/SuppWhereAlloc.php?TransType=' . $MyRow['type'] . '&TransNo=' . $MyRow['transno'] . '" style="color: var(--primary); text-decoration: none;">#' . $MyRow['transno'] . '</a></div>
					<div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 1px; font-weight: 700; background: var(--bg-main); padding: 1px 6px; border-radius: 4px; display: inline-block;">' . $MyRow['suppreference'] . '</div>
				</td>
				<td style="font-size: 0.8125rem; color: var(--text-muted); max-width: 320px; font-weight: 500; line-height: 1.4;">' . (empty($MyRow['transtext']) ? '<span style="opacity:0.5; font-style:italic;">' . __('Ref') . ': ' . $MyRow['suppreference'] . '</span>' : htmlspecialchars($MyRow['transtext'], ENT_QUOTES, 'UTF-8')) . '</td>
				<td class="text-right" style="font-weight: 800; font-size: 0.875rem; color: var(--text-main);">' . locale_number_format($MyRow['totalamount'], $SupplierRecord['currdecimalplaces']) . '</td>
				<td class="text-right" style="font-size: 0.8125rem; color: var(--text-muted); font-weight: 600;">' . locale_number_format($MyRow['allocated'], $SupplierRecord['currdecimalplaces']) . '</td>
				<td class="text-right">';
		
		if ($IsOutstanding) {
			echo '<div class="db-badge" style="background: rgba(239, 68, 68, 0.08); color: var(--danger); font-weight: 800;">
					' . locale_number_format($Outstanding, $SupplierRecord['currdecimalplaces']) . '
				  </div>';
		} else {
			echo '<div class="db-badge" style="background: rgba(34, 197, 94, 0.08); color: var(--success); font-weight: 800;">
					<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>
					' . __('Settled') . '
				  </div>';
		}
		echo '</td>
				<td class="text-center noPrint">
					<div style="display: flex; gap: 6px; justify-content: center;">';
		
		if ($_SESSION['CompanyRecord']['gllink_creditors'] == true) {
			echo '<a href="' . $RootPath . '/GLTransInquiry.php?TypeID=' . $MyRow['type'] . '&amp;TransNo=' . $MyRow['transno'] . '" class="db-btn-icon" style="background: var(--surface-alt); border-radius: 8px; color: var(--text-muted);" title="' . __('GL Entries') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg></a>';
		}

		if ($MyRow['type'] == 20) { // Invoice
			if ($MyRow['totalamount'] == $MyRow['allocated']) {
				echo '<a href="' . $RootPath . '/PaymentAllocations.php?SuppID=' . $MyRow['supplierno'] . '&amp;InvID=' . $MyRow['suppreference'] . '" class="db-btn-icon" style="background: var(--surface-alt); border-radius: 8px; color: var(--success);" title="' . __('View Payments') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></a>';
			} else {
				$HoldValue = ($MyRow['hold'] == 1) ? __('Release') : __('Hold');
				$HoldColor = ($HoldValue == __('Release')) ? 'color: var(--danger); background: rgba(239, 68, 68, 0.1);' : 'color: var(--primary); background: var(--surface-alt);';
				echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES,'UTF-8') . '?HoldType=' . $MyRow['type'] . '&amp;HoldTrans=' . $MyRow['transno'] . '&amp;HoldStatus=' . $HoldValue . '&amp;FromDate=' . $_POST['TransAfterDate'] . '" class="db-btn-icon" style="' . $HoldColor . '; border-radius: 8px;" title="' . $HoldValue . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg></a>';
			}
		} else { // Credit Note or Payment
			echo '<a href="' . $RootPath . '/SupplierAllocations.php?AllocTrans=' . $MyRow['id'] . '" class="db-btn-icon" style="background: var(--surface-alt); border-radius: 8px; color: var(--primary);" title="' . __('Allocate') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 8 12 12 16 14"></polyline></svg></a>';
		}
		
		echo '</div></td></tr>';
	}
}

echo '</tbody></table></div></div>'; // End table, card
echo '</div></div>'; // End db-workspace, db-page

include(__DIR__ . '/includes/footer.php');
