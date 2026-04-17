<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Invoice and GRN inquiry');
$ViewTopic = 'AccountsPayable';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';
	echo '<style>
		.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
		.registry-table th { background: #064e3b; padding: 12px 15px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #fff; letter-spacing: 1px; }
		.registry-table td { padding: 12px 15px; font-size: 0.88rem; color: var(--text-body); border-bottom: 1px solid var(--border-soft); }
		.registry-table tr:nth-child(even) td { background: var(--bg-workspace); }
		.registry-table tr:hover td { background: var(--primary-soft) !important; }
		.db-field { margin-bottom: var(--space-4); }
		.db-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
	</style>';

	echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . $Title . '</h2>
			<p class="db-page-subtitle">' . __('Inquiry for') . ' <span class="val-bold">' . $SupplierID . ' - ' . $SupplierName . '</span></p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Change Supplier') . '
			</a>
		</div>
	</div>';

if (isset($_GET['SelectedSupplier'])) {
	$SupplierID= $_GET['SelectedSupplier'];
} elseif (isset($_POST['SelectedSupplier'])){
	$SupplierID = $_POST['SelectedSupplier'];
} else {
	prnMsg(__('The page must be called from suppliers selected interface, please click following link to select the supplier'),'error');
	echo '<a href="' . $RootPath . '/SelectSupplier.php">'. __('Select Supplier') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}
if (isset($_GET['SupplierName'])) {
	$SupplierName = $_GET['SupplierName'];
}
if (!isset($_POST['SupplierRef']) OR trim($_POST['SupplierRef'])=='') {
	$_POST['SupplierRef'] = '';
	if (empty($_POST['GRNBatchNo']) AND empty($_POST['InvoiceNo'])) {
		$_POST['GRNBatchNo'] = '';
		$_POST['InvoiceNo'] = '';
	} elseif (!empty($_POST['GRNBatchNo']) AND !empty($_POST['InvoiceNo'])) {
		$_POST['InvoiceNo'] = '';
	}
} elseif (isset($_POST['GRNBatchNo']) OR isset($_POST['InvoiceNo'])) {
	$_POST['GRNBatchNo'] = '';
	$_POST['InvoiceNo'] = '';
}
echo '<div class="db-bottom-layout">';
	echo '<aside class="db-col-aside">';
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-search" style="margin-right: 8px;"></i> ' . __('Inquiry Criteria') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-help-text" style="font-size: 0.8rem; margin-bottom: var(--space-4); color: var(--text-muted);">' . __('Search logic: Delivery Note > GRN No > Invoice No') . '</div>
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<input type="hidden" name="SelectedSupplier" value="' . $SupplierID . '" />
					<input type="hidden" name="SupplierName" value="' . $SupplierName . '" />

					<div class="db-field">
						<label class="db-label">' . __('Supplier\'s Delivery Note') . '</label>
						<input type="text" name="SupplierRef" value="' . $_POST['SupplierRef'] . '" maxlength="30" />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('GRN Number') . '</label>
						<input type="text" name="GRNBatchNo" value="' . $_POST['GRNBatchNo'] . '" maxlength="6" />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Invoice Number') . '</label>
						<input type="text" name="InvoiceNo" value="' . $_POST['InvoiceNo'] . '" maxlength="11" />
					</div>

					<button type="submit" name="Submit" class="db-btn db-btn-primary" style="width: 100%; margin-top: 10px;">
						' . __('Search Transactions') . '
					</button>
				</form>
			</div>
		  </div>';
	echo '</aside>';

	echo '<main class="db-col-main">';

// Hidden forms are handled in sidebar
if (isset($_POST['Submit'])) {
	$Where = '';
	if (isset($_POST['SupplierRef']) AND trim($_POST['SupplierRef']) != '') {
		$SupplierRef = trim($_POST['SupplierRef']);
		$WhereSupplierRef = " AND grns.supplierref LIKE '%" . $SupplierRef . "%'";
		$Where .= $WhereSupplierRef;
	} elseif (isset($_POST['GRNBatchNo']) AND trim($_POST['GRNBatchNo']) != '') {
		$GRNBatchNo = trim($_POST['GRNBatchNo']);
		$WhereGRN = " AND grnbatch LIKE '%" . $GRNBatchNo . "%'";
		$Where .= $WhereGRN;
	} elseif (isset($_POST['InvoiceNo']) AND (trim($_POST['InvoiceNo']) != '')) {
		$InvoiceNo = trim($_POST['InvoiceNo']);
		$WhereInvoiceNo = " AND suppinv LIKE '%" . $InvoiceNo . "%'";
		$Where .= $WhereInvoiceNo;
	}
	$SQL = "SELECT grnbatch, grns.supplierref, suppinv,purchorderdetails.orderno
		FROM grns INNER JOIN purchorderdetails ON grns.podetailitem=purchorderdetails.podetailitem
		LEFT JOIN suppinvstogrn ON grns.grnno=suppinvstogrn.grnno
		WHERE supplierid='" . $SupplierID . "'" . $Where;
	$ErrMsg = __('Failed to retrieve supplier invoice and grn data');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result)>0) {
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Inquiry Results') . '</h3>
				</div>
				<div class="db-card-body" style="padding: 0;">
					<table class="registry-table">
						<thead>
						<tr>
							<th>' . __('Delivery Note') . '</th>
							<th>' . __('GRN Batch') . '</th>
							<th>' . __('PO No') . '</th>
							<th>' . __('Invoice No') . '</th>
						</tr>
						</thead>
						<tbody>';

		while ($MyRow = DB_fetch_array($Result)){
			echo '<tr class="striped_row">
				<td>' . $MyRow['supplierref'] . '</td>
				<td><a href="' . $RootPath .'/PDFGrn.php?GRNNo=' . $MyRow['grnbatch'] . '&amp;PONo=' . $MyRow['orderno'] . '">' . $MyRow['grnbatch']. '</td>
				<td>' . $MyRow['orderno'] . '</td>
				<td>' . $MyRow['suppinv'] . '</td>
				</tr>';

		}
		echo '</tbody></table></div></div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-body" style="text-align: center; padding: var(--space-8);">
					<i class="fas fa-search" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3; margin-bottom: var(--space-4);"></i>
					<h3 style="color: var(--text-muted);">' . __('No transactions found') . '</h3>
					<p class="db-muted">' . __('Adjust your criteria and search again.') . '</p>
				</div>
			  </div>';
	}
} else {
	echo '<div class="db-card">
			<div class="db-card-body" style="text-align: center; padding: var(--space-8);">
				<i class="fas fa-arrow-left" style="font-size: 3rem; color: var(--db-primary); opacity: 0.3; margin-bottom: var(--space-4);"></i>
				<h3>' . __('Ready for Inquiry') . '</h3>
				<p class="db-muted">' . __('Enter a delivery note, GRN, or invoice number in the sidebar search.') . '</p>
			</div>
		  </div>';
}

echo '</main></div><!-- .db-bottom-layout -->';
echo '</div><!-- .db-page -->';

include(__DIR__ . '/includes/footer.php');
