<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Track and manage all purchase orders in the system') . '</p>
			</div>
		</div>';

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = $_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = $_POST['SelectedStockItem'];
}
if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
} elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
}
if (isset($_GET['SelectedSupplier'])) {
	$SelectedSupplier = $_GET['SelectedSupplier'];
} elseif (isset($_POST['SelectedSupplier'])) {
	$SelectedSupplier = $_POST['SelectedSupplier'];
}
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}
if (isset($OrderNumber) AND $OrderNumber != '') {
	if (!is_numeric($OrderNumber)) {
		prnMsg(__('The Order Number entered MUST be numeric'), 'error');
		unset($OrderNumber);
	} else {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('Order Number') . ' - ' . $OrderNumber . '</div>';
	}
} else {
	if (isset($SelectedSupplier)) {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('For supplier') . ': ' . $SelectedSupplier . '</div>';
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />';
	}
}
if (isset($_POST['SearchParts'])) {
	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg(__('Stock description keywords have been used in preference to the Stock code extract entered'), 'info');
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) as qoh,
				stockmaster.units,
				SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
			FROM stockmaster INNER JOIN locstock
			ON stockmaster.stockid = locstock.stockid INNER JOIN purchorderdetails
			ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE purchorderdetails.completed=1
			AND stockmaster.description " . LIKE  . " '" . $SearchString ."'
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";
	} elseif ($_POST['StockCode']) {
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) AS qoh,
				SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord,
				stockmaster.units
			FROM stockmaster INNER JOIN locstock
				ON stockmaster.stockid = locstock.stockid
				INNER JOIN purchorderdetails ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE purchorderdetails.completed=1
			AND stockmaster.stockid " . LIKE  . " '%" . $_POST['StockCode'] . "%'
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";
	} elseif (!$_POST['StockCode'] AND !$_POST['Keywords']) {
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) AS qoh,
				stockmaster.units,
				SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
			FROM stockmaster INNER JOIN locstock ON stockmaster.stockid = locstock.stockid
				INNER JOIN purchorderdetails ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE purchorderdetails.completed=1
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";
	}
	$ErrMsg = __('No stock items were returned by the SQL because');
	$StockItemsResult = DB_query($SQL, $ErrMsg);
}
/* Not appropriate really to restrict search by date since user may miss older
* ouststanding orders
* $OrdersAfterDate = date("d/m/Y",mktime(0,0,0,date("m")-2,date("d"),date("Y")));
*/
if (!isset($OrderNumber) or $OrderNumber == "") {
	echo '<div class="db-grid db-grid-2" style="margin-top: var(--space-4);">
			<div class="db-card">
				<div class="db-card-title">
					<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> ' . __('Order Selection Options') . '</span>
				</div>
				<div class="db-card-body">';
	if (isset($SelectedStockItem)) {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('For the part') . ': <strong>' . $SelectedStockItem . '</strong></div>
			  <input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	}
	echo '		<div class="db-form-group">
					<label class="db-form-label">' . __('Order Number') . ':</label>
					<input type="text" name="OrderNumber" class="db-form-input" autofocus="autofocus" maxlength="8" placeholder="' . __('Enter order #...') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-form-label">' . __('Into Stock Location') . ':</label>
					<select name="StockLocation" class="db-form-select">';

	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		if (isset($_POST['StockLocation'])) {
			if ($MyRow['loccode'] == $_POST['StockLocation']) {
				echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			} else {
				echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			}
		} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
			echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		} else {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		}
	}
	echo '      </select>
				</div>
				<div class="db-form-group">
					<label class="db-form-label">' . __('Order Status') . ':</label>
					<select name="Status" class="db-form-select">';
	
	$Statuses = [
		'Pending_Authorised_Completed' => __('Pending/Authorised/Completed'),
		'Pending' => __('Pending'),
		'Authorised' => __('Authorised'),
		'Completed' => __('Completed'),
		'Cancelled' => __('Cancelled'),
		'Rejected' => __('Rejected')
	];

	foreach ($Statuses as $val => $label) {
		$selected = (isset($_POST['Status']) && $_POST['Status']==$val) ? 'selected="selected"' : '';
		if (!isset($_POST['Status']) && $val == 'Pending_Authorised_Completed') $selected = 'selected="selected"';
		echo '<option ' . $selected . ' value="' . $val . '">' . $label . '</option>';
	}

	echo '		</select>
				</div>
				<div class="db-form-actions">
					<button type="submit" name="SearchOrders" class="db-btn db-btn-primary">' . __('Search Purchase Orders') . '</button>
				</div>
			</div>
		</div>';
}

$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '	<div class="db-card">
			<div class="db-card-title">
				<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 21-4.3-4.3"/><circle cx="11" cy="11" r="8"/><path d="M11 8v6"/><path d="M8 11h6"/></svg> ' . __('Search By Stock Item') . '</span>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-form-label">' . __('Select a stock category') . ':</label>
					<select name="StockCat" class="db-form-select">';

while ($MyRow1 = DB_fetch_array($Result1)) {
	$selected = (isset($_POST['StockCat']) and $MyRow1['categoryid'] == $_POST['StockCat']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
}

echo '		</select>
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Description Keywords') . ':</label>
				<input type="text" name="Keywords" class="db-form-input" placeholder="' . __('Enter keywords...') . '" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('OR Stock Code Extract') . ':</label>
				<input type="text" name="StockCode" class="db-form-input" placeholder="' . __('Enter code...') . '" />
			</div>
			<div class="db-form-actions">
				<button type="submit" name="SearchParts" class="db-btn db-btn-primary">' . __('Search Parts Now') . '</button>
				<button type="submit" name="ResetPart" class="db-btn db-btn-secondary">' . __('Show All') . '</button>
			</div>
		</div>
	</div>
</div> <!-- End Grid -->';

if (isset($StockItemsResult)) {
	echo '<div class="db-card" style="margin-top: var(--space-4);">
			<div class="db-card-title">' . __('Stock Item Results') . '</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('On Hand') . '</th>
								<th class="text-right">' . __('Outstanding') . '</th>
								<th>' . __('Units') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-outline db-btn-sm">' . $MyRow['stockid'] . '</button></td>
				<td>' . $MyRow['description'] . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qoh'],$MyRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qord'],$MyRow['decimalplaces']) . '</td>
				<td>' . $MyRow['units'] . '</td>
			</tr>';
	}
	echo '      </tbody>
					</table>
				</div>
			</div>
		</div>';
}
//end if stock search results to show
else {
	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) OR $_POST['Status']=='Pending_Authorised_Completed'){
		$StatusCriteria = " AND (purchorders.status='Pending' OR purchorders.status='Authorised' OR purchorders.status='Printed' OR purchorders.status='Completed') ";
	} elseif ($_POST['Status']=='Authorised'){
		$StatusCriteria = " AND (purchorders.status='Authorised' OR purchorders.status='Printed')";
	} elseif ($_POST['Status']=='Pending'){
		$StatusCriteria = " AND purchorders.status='Pending' ";
	} elseif ($_POST['Status']=='Rejected'){
		$StatusCriteria = " AND purchorders.status='Rejected' ";
	} elseif ($_POST['Status']=='Cancelled'){
		$StatusCriteria = " AND purchorders.status='Cancelled' ";
	} elseif ($_POST['Status']=='Completed'){
		$StatusCriteria = " AND purchorders.status='Completed' ";
	}
	if (isset($OrderNumber) AND $OrderNumber != '') {
		$SQL = "SELECT purchorders.orderno,
						suppliers.suppname,
						purchorders.orddate,
						purchorders.deliverydate,
						purchorders.initiator,
						purchorders.requisitionno,
						purchorders.allowprint,
						purchorders.status,
						suppliers.currcode,
						currencies.decimalplaces AS currdecimalplaces,
						SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
					FROM purchorders
					INNER JOIN purchorderdetails
					ON purchorders.orderno = purchorderdetails.orderno
					INNER JOIN suppliers
					ON purchorders.supplierno = suppliers.supplierid
					INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
					WHERE purchorders.orderno='" . filter_number_format($OrderNumber) . "'
					GROUP BY purchorders.orderno,
						suppliers.suppname,
						purchorders.orddate,
						purchorders.initiator,
						purchorders.requisitionno,
						purchorders.allowprint,
						purchorders.status,
						suppliers.currcode,
						currencies.decimalplaces";
	} else {
		/* $DateAfterCriteria = FormatDateforSQL($OrdersAfterDate); */
		if (empty($_POST['StockLocation'])) {
			$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
		}
		if (isset($SelectedSupplier)) {
			if (isset($SelectedStockItem)) {
				$SQL = "SELECT purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.deliverydate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								purchorders.status,
								suppliers.currcode,
								currencies.decimalplaces AS currdecimalplaces,
								SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
							FROM purchorders
							INNER JOIN purchorderdetails
							ON purchorders.orderno = purchorderdetails.orderno
							INNER JOIN suppliers
							ON purchorders.supplierno = suppliers.supplierid
							INNER JOIN currencies
							ON suppliers.currcode=currencies.currabrev
							WHERE  purchorderdetails.itemcode='" . $SelectedStockItem . "'
							AND purchorders.supplierno='" . $SelectedSupplier . "'
							AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'
							" . $StatusCriteria . "
							GROUP BY purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								suppliers.currcode,
								currencies.decimalplaces";
			} else {
				$SQL = "SELECT purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.deliverydate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								purchorders.status,
								suppliers.currcode,
								currencies.decimalplaces AS currdecimalplaces,
								SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
							FROM purchorders
							INNER JOIN purchorderdetails
							ON purchorders.orderno = purchorderdetails.orderno
							INNER JOIN suppliers
							ON purchorders.supplierno = suppliers.supplierid
							INNER JOIN currencies
							ON suppliers.currcode=currencies.currabrev
							WHERE purchorders.supplierno='" . $SelectedSupplier . "'
							AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'
							" . $StatusCriteria . "
							GROUP BY purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								suppliers.currcode,
								currencies.decimalplaces";
			}
		} else { //no supplier selected
			if (isset($SelectedStockItem)) {
				$SQL = "SELECT purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.deliverydate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								purchorders.status,
								suppliers.currcode,
								currencies.decimalplaces AS currdecimalplaces,
								SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
							FROM purchorders
							INNER JOIN purchorderdetails
							ON purchorders.orderno = purchorderdetails.orderno
							INNER JOIN suppliers
							ON purchorders.supplierno = suppliers.supplierid
							INNER JOIN currencies
							ON suppliers.currcode=currencies.currabrev
							WHERE purchorderdetails.itemcode='" . $SelectedStockItem . "'
							AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'
							" . $StatusCriteria . "
							GROUP BY purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								suppliers.currcode,
								currencies.decimalplaces";
			} else {
				$SQL = "SELECT purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.deliverydate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								purchorders.status,
								suppliers.currcode,
								currencies.decimalplaces AS currdecimalplaces,
								SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
							FROM purchorders
							INNER JOIN purchorderdetails
							ON purchorders.orderno = purchorderdetails.orderno
							INNER JOIN suppliers
							ON purchorders.supplierno = suppliers.supplierid
							INNER JOIN currencies
							ON suppliers.currcode=currencies.currabrev
							WHERE purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'
							" . $StatusCriteria . "
							GROUP BY purchorders.orderno,
								suppliers.suppname,
								purchorders.orddate,
								purchorders.initiator,
								purchorders.requisitionno,
								purchorders.allowprint,
								suppliers.currcode,
								currencies.decimalplaces";
			}
		} //end selected supplier

	} //end not order number selected
	$ErrMsg = __('No orders were returned by the SQL because');
	$PurchOrdersResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PurchOrdersResult) > 0) {
		echo '<div class="db-card" style="margin-top: var(--space-4);">
				<div class="db-card-title">' . __('Purchase Order Results') . '</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('View') . '</th>
									<th>' . __('Supplier') . '</th>
									<th>' . __('Currency') . '</th>
									<th>' . __('Requisition') . '</th>
									<th>' . __('Order Date') . '</th>
									<th>' . __('Delivery Date') . '</th>
									<th>' . __('Initiator') . '</th>
									<th class="text-right">' . __('Order Total') . '</th>
									<th class="text-center">' . __('Status') . '</th>
								</tr>
							</thead>
							<tbody>';

		while ($MyRow = DB_fetch_array($PurchOrdersResult)) {
			$ViewPurchOrder = $RootPath . '/PO_OrderDetails.php?OrderNo=' . $MyRow['orderno'];
			$FormatedOrderDate = ConvertSQLDate($MyRow['orddate']);
			$FormatedDeliveryDate = ConvertSQLDate($MyRow['deliverydate']);
			$FormatedOrderValue = locale_number_format($MyRow['ordervalue'], $MyRow['currdecimalplaces']);

			$StatusClass = 'db-badge-info';
			if ($MyRow['status'] == 'Authorised') $StatusClass = 'db-badge-success';
			if ($MyRow['status'] == 'Completed') $StatusClass = 'db-badge-info';
			if ($MyRow['status'] == 'Cancelled') $StatusClass = 'db-badge-danger';
			if ($MyRow['status'] == 'Rejected') $StatusClass = 'db-badge-warning';

			echo '<tr>
					<td><a href="' . $ViewPurchOrder . '" class="db-btn db-btn-outline db-btn-sm">' . $MyRow['orderno'] . '</a></td>
					<td class="db-font-semibold">' . $MyRow['suppname'] . '</td>
					<td><span class="db-badge db-badge-info">' . $MyRow['currcode'] . '</span></td>
					<td>' . $MyRow['requisitionno'] . '</td>
					<td class="text-nowrap">' . $FormatedOrderDate . '</td>
					<td class="text-nowrap">' . $FormatedDeliveryDate . '</td>
					<td class="db-text-muted">' . $MyRow['initiator'] . '</td>
					<td class="text-right db-font-bold">' . $FormatedOrderValue . '</td>
					<td class="text-center"><span class="db-badge ' . $StatusClass . '">' . __($MyRow['status']) .  '</span></td>
				  </tr>';
				//$MyRow['status'] is a string which has gettext translations from PO_Header.php script
		}
		//end of while loop
		echo '						</tbody>
								</table>
							</div>
						</div>
					</div>';
	} // end if purchase orders to show
}
echo '</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
