<?php

/* This page is called from SupplierInquiry.php when the 'view payments' button is selected
   to show which payments were allocated to a specific invoice. */

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Allocations');
$ViewTopic = 'AccountsPayable';
$BookMark = 'PaymentAllocations';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (!isset($_GET['SuppID'])) {
	prnMsg(__('Supplier ID Number is not set, cannot display result'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (!isset($_GET['InvID'])) {
	prnMsg(__('Invoice Number is not set, cannot display result'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$SuppID = $_GET['SuppID'];
$InvID = $_GET['InvID'];

// 1. Fetch Invoice Details
$InvSQL = "SELECT supptrans.id,
                  supptrans.trandate,
                  supptrans.suppreference,
                  supptrans.ovamount + supptrans.ovgst as total,
                  supptrans.alloc,
                  suppliers.suppname,
                  currencies.currency,
                  currencies.decimalplaces
           FROM supptrans 
           INNER JOIN suppliers ON supptrans.supplierno = suppliers.supplierid
           INNER JOIN currencies ON suppliers.currcode = currencies.currabrev
           WHERE supptrans.supplierno = '" . DB_escape_string($SuppID) . "' 
           AND supptrans.suppreference = '" . DB_escape_string($InvID) . "'
           AND supptrans.type = 20";

$InvResult = DB_query($InvSQL);

if (DB_num_rows($InvResult) == 0) {
	prnMsg(__('The invoice details could not be found.'), 'warn');
	echo '<div class="centre" style="margin-top: 20px;"><a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . $SuppID . '" class="db-btn db-btn-secondary">' . __('Back to Supplier Inquiry') . '</a></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$InvRow = DB_fetch_array($InvResult);

// 2. Fetch Allocations (Payments to this Invoice)
$SQL = "SELECT p.supplierno,
               p.suppreference as payment_ref,
               p.trandate as payment_date,
               p.transno,
               a.amt as allocated_amt,
               a.datealloc,
               c.decimalplaces
        FROM suppallocs a
        INNER JOIN supptrans p ON a.transid_allocfrom = p.id
        INNER JOIN supptrans i ON a.transid_allocto = i.id
        INNER JOIN suppliers s ON i.supplierno = s.supplierid
        INNER JOIN currencies c ON s.currcode = c.currabrev
        WHERE i.id = '" . $InvRow['id'] . "'
        ORDER BY a.datealloc DESC";

$Result = DB_query($SQL);

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-row">
				<div class="db-header-main">
					<h1 class="db-page-title">' . $Title . '</h1>
					<p class="db-page-subtitle">' . __('Viewing payments allocated to') . ' <span style="color:var(--primary); font-weight: 700;">' . $InvID . '</span> — ' . $InvRow['suppname'] . '</p>
				</div>
				<div class="db-header-actions">
					<a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . $SuppID . '" class="db-btn db-btn-secondary">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
						' . __('Supplier Inquiry') . '
					</a>
				</div>
			</div>
		</div>

		<div class="db-workspace">
			<div class="card-v2" style="margin-bottom: var(--space-6);">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14 M22 4L12 14.01 9 11.01"></path></svg>
						' . __('Invoice Summary') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-4">
						<div class="db-field">
							<label class="db-label">' . __('Reference') . '</label>
							<div class="db-field-value">' . $InvRow['suppreference'] . '</div>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Date') . '</label>
							<div class="db-field-value">' . ConvertSQLDate($InvRow['trandate']) . '</div>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Total Amount') . '</label>
							<div class="db-field-value">' . locale_number_format($InvRow['total'], $InvRow['decimalplaces']) . ' ' . $InvRow['currency'] . '</div>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Allocated Amount') . '</label>
							<div class="db-field-value" style="font-weight: 700; color: var(--primary);">' . locale_number_format($InvRow['alloc'], $InvRow['decimalplaces']) . ' ' . $InvRow['currency'] . '</div>
						</div>
					</div>
				</div>
			</div>

			<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
						' . __('Allocated Payments') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">';

if (DB_num_rows($Result) == 0) {
	echo '				<div style="padding: var(--space-8); text-align: center; color: var(--text-muted); font-size: 0.875rem;">
							<p>' . __('No payments have been allocated to this invoice yet.') . '</p>
						</div>';
} else {
	echo '				<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Payment Trans #') . '</th>
									<th>' . __('Payment Reference') . '</th>
									<th>' . __('Payment Date') . '</th>
									<th>' . __('Allocation Date') . '</th>
									<th class="number">' . __('Amount Allocated') . '</th>
								</tr>
							</thead>
							<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '					<tr>
									<td><a href="' . $RootPath . '/SuppWhereAlloc.php?TransType=22&TransNo=' . $MyRow['transno'] . '" style="font-weight: 700; color: var(--primary);">#' . $MyRow['transno'] . '</a></td>
									<td>' . $MyRow['payment_ref'] . '</td>
									<td class="date">' . ConvertSQLDate($MyRow['payment_date']) . '</td>
									<td class="date">' . ConvertSQLDate($MyRow['datealloc']) . '</td>
									<td class="number" style="font-weight: 700; color: var(--text-main);">' . locale_number_format($MyRow['allocated_amt'], $MyRow['decimalplaces']) . '</td>
								</tr>';
	}

	echo '					</tbody>
						</table>';
}

echo '				</div>
				</div>
			</div>
		</div>
	</div>';

include(__DIR__ . '/includes/footer.php');
?>
