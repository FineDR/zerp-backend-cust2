<?php

require(__DIR__ . '/includes/session.php');

$Title=__('Reprint a GRN');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card" style="margin-bottom: 20px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Receipt Lookup') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label" for="PONumber">' . __('Purchase Order #') . '</label>
						<input type="text" name="PONumber" class="db-input" value="' . ($_POST['PONumber'] ?? '') . '" placeholder="' . __('e.g. 1234') . '" autofocus />
					</div>
					<button type="submit" name="Show" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
						<i class="fas fa-file-invoice"></i> ' . __('Show GRNs') . '
					</button>
				</div>
			</div>

			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Search Tips') . '</h3>
				</div>
				<div class="db-card-body">
					<p style="font-size: 0.8rem; color: var(--text-muted);">
						' . __('Enter the internal Purchase Order number to retrieve all associated Goods Received Notes.') . '
					</p>
				</div>
			</div>
		</form>
	</aside>';

echo '<main class="db-col-main">';


if (isset($_POST['Show'])) {
	if ($_POST['PONumber']=='') {
		echo '<br />';
		prnMsg( __('You must enter a purchase order number in the box above'), 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	$SQL="SELECT count(orderno)
				FROM purchorders
				WHERE orderno='" . $_POST['PONumber'] ."'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);
	if ($MyRow[0]==0) {
		echo '<br />';
		prnMsg( __('This purchase order does not exist on the system. Please try again.'), 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	$SQL="SELECT grnbatch,
				grns.grnno,
				grns.podetailitem,
				grns.itemcode,
				grns.itemdescription,
				grns.deliverydate,
				grns.qtyrecd,
				suppinvstogrn.suppinv,
				suppliers.suppname,
				stockmaster.decimalplaces
			FROM grns INNER JOIN suppliers
			ON grns.supplierid=suppliers.supplierid
			LEFT JOIN suppinvstogrn ON grns.grnno=suppinvstogrn.grnno
			INNER JOIN purchorderdetails
			ON grns.podetailitem=purchorderdetails.podetailitem
			INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
			INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			LEFT JOIN stockmaster
			ON grns.itemcode=stockmaster.stockid
			WHERE purchorderdetails.orderno='" . $_POST['PONumber'] ."'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)==0) {
		echo '<br />';
		prnMsg( __('There are no GRNs for this purchase order that can be reprinted.'), 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

if (isset($_POST['Show']) AND DB_num_rows($Result) > 0) {
	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-receipt"></i> ' . __('Associated GRNs') . '</h3>
				<span class="db-badge db-badge-primary">' . __('PO') . ' #' . $_POST['PONumber'] . '</span>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('GRN / Batch') . '</th>
								<th>' . __('Supplier') . '</th>
								<th>' . __('Item Code / Description') . '</th>
								<th>' . __('Delivery Date') . '</th>
								<th class="text-right">' . __('Qty Received') . '</th>
								<th>' . __('Invoice No') . '</th>
								<th>' . __('Print Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow=DB_fetch_array($Result)) {
		echo '			<tr class="striped_row">
							<td>
								<div class="db-font-bold text-primary">' . $MyRow['grnbatch'] . '</div>
								<div style="font-size: 0.75rem; color: var(--text-muted);">' . __('Ref') . ': ' . $MyRow['grnno'] . '</div>
							</td>
							<td style="font-size: 0.9rem;">' . $MyRow['suppname'] . '</td>
							<td>
								<div class="db-font-bold">' . $MyRow['itemcode'] . '</div>
								<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow['itemdescription'] . '</div>
							</td>
							<td>' . ConvertSQLDate($MyRow['deliverydate']) . '</td>
							<td class="text-right db-font-bold">' . locale_number_format($MyRow['qtyrecd'], $MyRow['decimalplaces']) . '</td>
							<td>' . ($MyRow['suppinv'] ?: '<span style="color:var(--text-muted); font-style:italic;">' . __('Not Invoiced') . '</span>') . '</td>
							<td>
								<div style="display: flex; gap: 8px;">
									<a href="' . $RootPath . '/PDFGrn.php?GRNNo=' . $MyRow['grnbatch'] .'&PONo=' . $_POST['PONumber'] . '" target="_blank" class="db-btn db-btn-primary" style="font-size: 0.7rem; padding: 4px 10px;">
										<i class="fas fa-print"></i> ' . __('GRN') . '
									</a>
									<a href="' . $RootPath . '/PDFQALabel.php?GRNNo=' . $MyRow['grnbatch'] .'&PONo=' . $_POST['PONumber'] . '" target="_blank" class="db-btn db-input-light" style="font-size: 0.7rem; padding: 4px 10px;">
										<i class="fas fa-tag"></i> ' . __('Labels') . '
									</a>
								</div>
							</td>
						</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';
} else {
	if (!isset($_POST['Show'])) {
		echo '<div class="db-status-bar db-status-info">
				<div class="db-status-icon"><i class="fas fa-arrow-left"></i></div>
				<div class="db-status-text">' . __('Enter a Purchase Order number in the sidebar to search for associated receipts.') . '</div>
			  </div>';
	}
}

}
echo '	</main>
	</div>'; // end db-bottom-layout

include(__DIR__ . '/includes/footer.php');
