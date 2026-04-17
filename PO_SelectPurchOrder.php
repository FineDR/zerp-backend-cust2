<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// ASIDE: Search Filters
echo '<aside class="db-col-aside">';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	if (isset($OrderNumber) AND $OrderNumber != '') {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('Order Number') . ': ' . $OrderNumber . '</div>';
	}
	if (isset($SelectedSupplier)) {
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />';
	}
	if (isset($SelectedStockItem)) {
		echo '<input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	}

	// Order Selection Options
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Order Options') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div class="db-field">
					<label class="db-label">' . __('Order Number') . '</label>
					<input type="text" name="OrderNumber" autofocus="autofocus" maxlength="8" placeholder="' . __('e.g. 1045') . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Stock Location') . '</label>
					<select name="StockLocation">';
	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockLocation']) && $MyRow['loccode'] == $_POST['StockLocation']) || (!isset($_POST['StockLocation']) && $MyRow['loccode'] == $_SESSION['UserStockLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '			</select>
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Status') . '</label>
					<select name="Status">';
	$Statuses = [
		'Pending_Authorised_Completed' => __('Pending/Authorised/Completed'),
		'Pending' => __('Pending'),
		'Authorised' => __('Authorised'),
		'Completed' => __('Completed'),
		'Cancelled' => __('Cancelled'),
		'Rejected' => __('Rejected')
	];
	foreach ($Statuses as $val => $label) {
		$selected = (isset($_POST['Status']) && $_POST['Status']==$val) || (!isset($_POST['Status']) && $val == 'Pending_Authorised_Completed') ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $val . '">' . $label . '</option>';
	}
	echo '			</select>
				</div>
				<button type="submit" name="SearchOrders" class="db-btn db-btn-primary" style="width: 100%; margin-top: 10px;">' . __('Search Orders') . '</button>
			</div>
		  </div>';

	// Category Search
	echo '<div class="db-card" style="margin-top: 20px;">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-boxes"></i> ' . __('Part Lookup') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div class="db-field">
					<label class="db-label">' . __('Category') . '</label>
					<select name="StockCat">';
	$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$CatRes = DB_query($SQL);
	while ($CatRow = DB_fetch_array($CatRes)) {
		$selected = (isset($_POST['StockCat']) && $CatRow['categoryid'] == $_POST['StockCat']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $CatRow['categoryid'] . '">' . $CatRow['categorydescription'] . '</option>';
	}
	echo '			</select>
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Keywords') . '</label>
					<input type="text" name="Keywords" placeholder="' . __('Description...') . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Code Extract') . '</label>
					<input type="text" name="StockCode" placeholder="' . __('Code...') . '" />
				</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px;">
					<button type="submit" name="SearchParts" class="db-btn db-btn-primary">' . __('Search') . '</button>
					<button type="submit" name="ResetPart" class="db-btn db-btn-secondary">' . __('All') . '</button>
				</div>
			</div>
		  </div>';
echo '</aside>';

// MAIN CONTENT
echo '<main class="db-col-main" style="flex: 1; min-width: 0;">';

if (isset($StockItemsResult)) {
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title">' . __('Stock Item Results') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<table class="registry-table">
					<thead>
						<tr>
							<th>' . __('Code') . '</th>
							<th>' . __('Description') . '</th>
							<th class="number">' . __('On Hand') . '</th>
							<th class="number">' . __('Outstanding') . '</th>
							<th>' . __('Units') . '</th>
						</tr>
					</thead>
					<tbody>';
	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-outline db-btn-sm">' . $MyRow['stockid'] . '</button></td>
				<td class="db-font-semibold">' . $MyRow['description'] . '</td>
				<td class="number">' . locale_number_format($MyRow['qoh'],$MyRow['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['qord'],$MyRow['decimalplaces']) . '</td>
				<td>' . $MyRow['units'] . '</td>
			</tr>';
	}
	echo '      </tbody>
				</table>
			</div>
		</div>';
} else {
	// Purchase Order Results
	if (!isset($_POST['Status']) OR $_POST['Status']=='Pending_Authorised_Completed'){
		$StatusCriteria = " AND (purchorders.status='Pending' OR purchorders.status='Authorised' OR purchorders.status='Printed' OR purchorders.status='Completed') ";
	} elseif ($_POST['Status']=='Authorised'){
		$StatusCriteria = " AND (purchorders.status='Authorised' OR purchorders.status='Printed')";
	} else {
		$StatusCriteria = " AND purchorders.status='" . $_POST['Status'] . "' ";
	}

	if (isset($OrderNumber) AND $OrderNumber != '') {
		$SQL = "SELECT purchorders.orderno, suppliers.suppname, purchorders.orddate, purchorders.deliverydate, purchorders.initiator, purchorders.requisitionno, purchorders.allowprint, purchorders.status, suppliers.currcode, currencies.decimalplaces AS currdecimalplaces, SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
				FROM purchorders
				INNER JOIN purchorderdetails ON purchorders.orderno = purchorderdetails.orderno
				INNER JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
				INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
				WHERE purchorders.orderno='" . filter_number_format($OrderNumber) . "'
				GROUP BY purchorders.orderno, suppliers.suppname, purchorders.orddate, purchorders.initiator, purchorders.requisitionno, purchorders.allowprint, purchorders.status, suppliers.currcode, currencies.decimalplaces";
	} else {
		if (empty($_POST['StockLocation'])) $_POST['StockLocation'] = $_SESSION['UserStockLocation'];
		
		$BaseSQL = "SELECT purchorders.orderno, suppliers.suppname, purchorders.orddate, purchorders.deliverydate, purchorders.initiator, purchorders.requisitionno, purchorders.allowprint, purchorders.status, suppliers.currcode, currencies.decimalplaces AS currdecimalplaces, SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
					FROM purchorders
					INNER JOIN purchorderdetails ON purchorders.orderno = purchorderdetails.orderno
					INNER JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
					INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
					WHERE purchorders.intostocklocation = '" . $_POST['StockLocation'] . "' " . $StatusCriteria;
		
		if (isset($SelectedSupplier)) $BaseSQL .= " AND purchorders.supplierno='" . $SelectedSupplier . "'";
		if (isset($SelectedStockItem)) $BaseSQL .= " AND purchorderdetails.itemcode='" . $SelectedStockItem . "'";
		
		$SQL = $BaseSQL . " GROUP BY purchorders.orderno, suppliers.suppname, purchorders.orddate, purchorders.initiator, purchorders.requisitionno, purchorders.allowprint, suppliers.currcode, currencies.decimalplaces";
	}

	$PurchOrdersResult = DB_query($SQL);

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title">' . __('Purchase Orders') . '</h3>
				<span class="db-badge db-badge-info">' . DB_num_rows($PurchOrdersResult) . ' ' . __('Found') . '</span>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<table class="registry-table">
					<thead>
						<tr>
							<th>' . __('Order') . '</th>
							<th>' . __('Supplier') . '</th>
							<th class="number">' . __('Value') . '</th>
							<th>' . __('Order Date') . '</th>
							<th>' . __('Status') . '</th>
							<th class="text-right">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

	if (DB_num_rows($PurchOrdersResult) == 0) {
		echo '<tr><td colspan="6" class="centre" style="padding: 40px; color: var(--text-muted);">' . __('No matching orders found') . '</td></tr>';
	} else {
		while ($MyRow = DB_fetch_array($PurchOrdersResult)) {
			$ViewPurchOrder = $RootPath . '/PO_OrderDetails.php?OrderNo=' . $MyRow['orderno'];
			$StatusClass = 'db-badge-info';
			if ($MyRow['status'] == 'Authorised') $StatusClass = 'db-badge-success';
			if ($MyRow['status'] == 'Cancelled') $StatusClass = 'db-badge-danger';

			echo '<tr>
					<td><span class="val-bold">' . $MyRow['orderno'] . '</span></td>
					<td><div>' . $MyRow['suppname'] . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $MyRow['currcode'] . '</div></td>
					<td class="number db-font-bold">' . locale_number_format($MyRow['ordervalue'], $MyRow['currdecimalplaces']) . '</td>
					<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
					<td><span class="db-badge ' . $StatusClass . '">' . __($MyRow['status']) . '</span></td>
					<td class="text-right">
						<a href="' . $ViewPurchOrder . '" class="db-btn db-btn-outline db-btn-sm">' . __('View Details') . '</a>
					</td>
				</tr>';
		}
	}
	echo '      </tbody>
				</table>
			</div>
		</div>';
}
echo '</main></div>'; // Close main and bottom layout
echo '</form></div>'; // Close form and db-page
include(__DIR__ . '/includes/footer.php');
