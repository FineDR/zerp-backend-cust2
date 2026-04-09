<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search All Sales Orders');
$ViewTopic = 'SalesOrders';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['OrdersAfterDate'])) {
	$_POST['OrdersAfterDate'] = ConvertSQLDate($_POST['OrdersAfterDate']);
}

echo '<div class="db-page">';

if (isset($_POST['completed'])) {
	$Completed = "=1";
	$ShowChecked = "checked='checked'";
} else {
	$Completed = ">=0";
	$ShowChecked = '';
}

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = $_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = $_POST['SelectedStockItem'];
}
if (isset($_GET['OrderNumber'])) {
	$OrderNumber = filter_number_format($_GET['OrderNumber']);
} elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = filter_number_format($_POST['OrderNumber']);
}
if (isset($_GET['CustomerRef'])) {
	$CustomerRef = $_GET['CustomerRef'];
	$CustomerGet = 1;
} elseif (isset($_POST['CustomerRef'])) {
	$CustomerRef = $_POST['CustomerRef'];
}
if (isset($_GET['SelectedCustomer'])) {
	$SelectedCustomer = $_GET['SelectedCustomer'];
} elseif (isset($_POST['SelectedCustomer'])) {
	$SelectedCustomer = $_POST['SelectedCustomer'];
}

if ($CustomerLogin == 1) {
	$SelectedCustomer = $_SESSION['CustomerID'];
}

if (isset($SelectedStockItem) and $SelectedStockItem == '') {
	unset($SelectedStockItem);
}
if (isset($OrderNumber) and $OrderNumber == '') {
	unset($OrderNumber);
}
if (isset($CustomerRef) and $CustomerRef == '') {
	unset($CustomerRef);
}
if (isset($SelectedCustomer) and $SelectedCustomer == '') {
	unset($SelectedCustomer);
}
if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

// Header
echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title">' . $Title . '</h2>
			<p class="db-page-subtitle">' . __('Search and analyze historic sales orders and completed transactions') . '</p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSalesOrder.php" class="db-btn db-btn-secondary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
				' . __('Outstanding Orders') . '
			</a>
		</div>
	</div>';

if (isset($OrderNumber)) {
	prnMsg(__('Order Number') . ' - ' . $OrderNumber, 'info');
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="card-v2">
		<div class="card-header-v2">
			<h3>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Search Filters') . '
			</h3>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-3">
				<div class="db-field">
					<label class="db-label">' . __('Order Number') . '</label>
					<input type="text" name="OrderNumber" maxlength="8" value="' . (isset($_POST['OrderNumber']) ? $_POST['OrderNumber'] : '') . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Placed After') . '</label>
					<input type="date" name="OrdersAfterDate" value="' . (isset($_POST['OrdersAfterDate']) ? FormatDateForSQL($_POST['OrdersAfterDate']) : date('Y-m-d', mktime(0, 0, 0, date('m') - 2, date('d'), date('Y')))) . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Customer Ref') . '</label>
					<input type="text" name="CustomerRef" value="' . (isset($_POST['CustomerRef']) ? $_POST['CustomerRef'] : '') . '" />
				</div>
				<div class="db-field" style="display:flex; align-items:center; gap:8px;">
					<input type="checkbox" ' . $ShowChecked . ' name="completed" id="completed_check" />
					<label class="db-label" for="completed_check" style="margin-bottom:0;">' . __('Show Completed orders only') . '</label>
				</div>
			</div>
			<div class="form-footer-actions" style="margin-top:var(--space-6);">
				<button type="submit" name="SearchOrders" class="db-btn db-btn-primary">' . __('Search Orders') . '</button>
			</div>
		</div>
	</div>';

echo '<div class="card-v2" style="margin-top: var(--space-6);">
		<div class="card-header-v2">
			<h3>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
				' . __('Search by Item') . '
			</h3>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-3">
				<div class="db-field">
					<label class="db-label">' . __('Stock Category') . '</label>
					<select name="StockCat">';
$Result1 = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
while ($MyRow1 = DB_fetch_array($Result1)) {
	if (isset($_POST['StockCat']) and $MyRow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	}
}
echo '				</select>
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Keywords') . '</label>
					<input type="text" name="Keywords" placeholder="' . __('Enter part description...') . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Stock Code Extract') . '</label>
					<input type="text" name="StockCode" placeholder="' . __('Enter code extract...') . '" />
				</div>
			</div>
			<div class="form-footer-actions" style="margin-top:var(--space-6);">
				<button type="submit" name="SearchParts" class="db-btn db-btn-primary">' . __('Search Parts') . '</button>
				<button type="submit" name="ResetPart" class="db-btn db-btn-secondary">' . __('Show All Cells') . '</button>
			</div>
		</div>
	</div>';

// Logic for Part Search Results
if (isset($_POST['SearchParts'])) {
	// ... (SQL Building logic from original file)
	if ($_POST['Keywords'] != '' and $_POST['StockCode'] != '') {
		prnMsg(__('Stock description keywords have been used in preference to the Stock code extract entered'), 'info');
	}
	if ($_POST['Keywords'] != '') {
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, stockmaster.units, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem
				FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
				WHERE " . (isset($_POST['completed']) ? "salesorderdetails.completed = 1 AND" : "") . " stockmaster.description " . LIKE . " '" . $SearchString . "' AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
	} elseif ($_POST['StockCode'] != '') {
		$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem, stockmaster.units
				FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
				WHERE " . (isset($_POST['completed']) ? "salesorderdetails.completed = 1 AND" : "") . " stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%' AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
	} elseif ($_POST['StockCat'] != '') {
		$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem, stockmaster.units
				FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
				WHERE " . (isset($_POST['completed']) ? "salesorderdetails.completed = 1 AND" : "") . " stockmaster.categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
	}

	if (isset($SQL)) {
		$StockItemsResult = DB_query($SQL);
		if (DB_num_rows($StockItemsResult) == 1) {
			$MyRow = DB_fetch_row($StockItemsResult);
			$SelectedStockItem = $MyRow[0];
			$_POST['SearchOrders'] = 'true';
		}
	}
}

if (isset($StockItemsResult)) {
	echo '<div class="card-v2" style="margin-top:var(--space-6);">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Code') . '</th>
							<th>' . __('Description') . '</th>
							<th class="number">' . __('On Hand') . '</th>
							<th class="number">' . __('PO Items') . '</th>
							<th class="number">' . __('Back Orders') . '</th>
							<th>' . __('Units') . '</th>
						</tr>
					</thead>
					<tbody>';
	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-secondary" style="padding:4px 8px;">' . $MyRow['stockid'] . '</button></td>
				<td>' . $MyRow['description'] . '</td>
				<td class="number">' . locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['qoo'], $MyRow['decimalplaces']) . '</td>
				<td class="number">' . locale_number_format($MyRow['qdem'], $MyRow['decimalplaces']) . '</td>
				<td><span class="db-badge db-badge-info">' . $MyRow['units'] . '</span></td>
			</tr>';
	}
	echo '			</tbody>
				</table>
			</div>
		</div>';
}

// Logic for Order Search Results
if ((isset($_POST['SearchOrders']) and Is_Date($_POST['OrdersAfterDate']) == 1) or (isset($CustomerGet))) {
	$DateAfterCriteria = FormatDateforSQL($_POST['OrdersAfterDate']);
	// ... (Refactored SQL Logic consolidated for cleaner output)
	if (isset($OrderNumber)) {
		$SQL = "SELECT salesorders.orderno, debtorsmaster.name, custbranch.brname, salesorders.customerref, salesorders.orddate, salesorders.deliverydate, salesorders.deliverto, currencies.decimalplaces AS currdecimalplaces, SUM(salesorderdetails.linenetprice) AS ordervalue
				FROM salesorders INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno INNER JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno INNER JOIN custbranch ON salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno INNER JOIN currencies ON debtorsmaster.currcode = currencies.currabrev
				WHERE salesorders.orderno='" . $OrderNumber . "' AND salesorders.quotation=0 AND salesorderdetails.completed " . $Completed;
	} else {
		$SQL = "SELECT salesorders.orderno, debtorsmaster.name, currencies.decimalplaces AS currdecimalplaces, custbranch.brname, salesorders.customerref, salesorders.orddate, salesorders.deliverydate, salesorders.deliverto, SUM(salesorderdetails.linenetprice) AS ordervalue
				FROM salesorders INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno INNER JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno INNER JOIN custbranch ON salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno INNER JOIN currencies ON debtorsmaster.currcode = currencies.currabrev
				WHERE salesorders.quotation=0 AND salesorderdetails.completed" . $Completed;
		if (isset($CustomerRef)) $SQL .= " AND salesorders.customerref LIKE '%" . $CustomerRef . "%'";
		if (isset($SelectedCustomer)) $SQL .= " AND salesorders.debtorno='" . $SelectedCustomer . "'";
		if (isset($SelectedStockItem)) $SQL .= " AND salesorderdetails.stkcode='" . $SelectedStockItem . "'";
		$SQL .= " AND salesorders.orddate >= '" . $DateAfterCriteria . "'";
	}

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL .= " GROUP BY salesorders.orderno, debtorsmaster.name, currencies.decimalplaces, custbranch.brname, salesorders.customerref, salesorders.orddate, salesorders.deliverydate, salesorders.deliverto ORDER BY salesorders.orderno";

	$SalesOrdersResult = DB_query($SQL);
	if (DB_error_no() != 0) {
		prnMsg(__('No orders were returned by the SQL because') . ' ' . DB_error_msg(), 'info');
	}
}

if (isset($SalesOrdersResult)) {
	if (DB_num_rows($SalesOrdersResult) == 1) {
		$OrdRow = DB_fetch_array($SalesOrdersResult);
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/OrderDetails.php?OrderNumber=' . $OrdRow['orderno'] . '">';
	} else {
		echo '<div class="card-v2" style="margin-top:var(--space-6);">
				<div class="card-header-v2">
					<h3>' . __('Historic Order Results') . '</h3>
					<span class="tag">' . DB_num_rows($SalesOrdersResult) . ' ' . __('Orders Found') . '</span>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Order') . ' #</th>
								<th>' . __('Customer') . '</th>
								<th>' . __('Branch') . '</th>
								<th>' . __('Cust Order') . ' #</th>
								<th>' . __('Order Date') . '</th>
								<th>' . __('Req Del Date') . '</th>
								<th>' . __('Delivery To') . '</th>
								<th class="number">' . __('Order Total') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($MyRow = DB_fetch_array($SalesOrdersResult)) {
			echo '<tr>
					<td><a href="' . $RootPath . '/OrderDetails.php?OrderNumber=' . $MyRow['orderno'] . '" class="ref-badge">' . $MyRow['orderno'] . '</a></td>
					<td class="cust-name">' . $MyRow['name'] . '</td>
					<td>' . $MyRow['brname'] . '</td>
					<td>' . $MyRow['customerref'] . '</td>
					<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
					<td>' . ConvertSQLDate($MyRow['deliverydate']) . '</td>
					<td>' . $MyRow['deliverto'] . '</td>
					<td class="number val-bold">' . locale_number_format($MyRow['ordervalue'], $MyRow['currdecimalplaces']) . '</td>
				</tr>';
		}
		echo '			</tbody>
					</table>
				</div>
			</div>';
	}
}

echo '</form></div>';
include(__DIR__ . '/includes/footer.php');
