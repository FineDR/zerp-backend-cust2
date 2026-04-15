<?php

$PricesSecurity = 12;

require(__DIR__ . '/includes/session.php');

$Title = __('Search Outstanding Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/DefinePOClass.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = trim($_GET['SelectedStockItem']);
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = trim($_POST['SelectedStockItem']);
}

if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
} elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
}

if (isset($_GET['SelectedSupplier'])) {
	$SelectedSupplier = trim($_GET['SelectedSupplier']);
} elseif (isset($_POST['SelectedSupplier'])) {
	$SelectedSupplier = trim($_POST['SelectedSupplier']);
}

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

if (isset($_POST['SearchParts'])) {
	if (isset($_POST['Keywords']) AND isset($_POST['StockCode'])) {
		echo '<div class="page_help_text">' . __('Stock description keywords have been used in preference to the Stock code extract entered') . '.</div>';
	}
	if (isset($_POST['StockCat']) AND $_POST['StockCat'] == 'All'){
		$WhereStockCat = ' ';
	} else {
		$WhereStockCat = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					stockmaster.units,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
				FROM stockmaster INNER JOIN purchorderdetails
						ON stockmaster.stockid=purchorderdetails.itemcode
					INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				AND stockmaster.description " . LIKE . " '" . $SearchString . "'
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";


	} elseif ($_POST['StockCode']) {

		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord,
					stockmaster.units
				FROM stockmaster INNER JOIN purchorderdetails
				ON stockmaster.stockid=purchorderdetails.itemcode
				INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";

	} elseif (!$_POST['StockCode'] AND !$_POST['Keywords']) {
		$SQL = "SELECT stockmaster.stockid,
					stockmaster.decimalplaces,
					stockmaster.description,
					stockmaster.units,
					SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
				FROM stockmaster INNER JOIN purchorderdetails
				ON stockmaster.stockid=purchorderdetails.itemcode
				INNER JOIN purchorders on purchorders.orderno=purchorderdetails.orderno
				WHERE purchorderdetails.completed=0
				AND purchorders.status NOT IN ('Completed','Cancelled','Rejected')
				" . $WhereStockCat . "
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.units
				ORDER BY stockmaster.stockid";
	}

	$ErrMsg = __('No stock items were returned by the SQL because');
	$StockItemsResult = DB_query($SQL, $ErrMsg);
} //isset($_POST['SearchParts'])

$AddPOUrl = $RootPath . '/PO_Header.php?NewOrder=Yes' . (isset($SelectedSupplier) ? '&amp;SupplierID=' . $SelectedSupplier : '');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-shopping-basket"></i> ' . $Title . '
			</div>
			<div class="db-header-actions">
				<a href="' . $AddPOUrl . '" class="db-btn db-btn-primary">
					<i class="fas fa-plus"></i> ' . __('Add Purchase Order') . '
				</a>
			</div>
		</div>

		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($OrderNumber) AND $OrderNumber != '') {
	if (!is_numeric($OrderNumber)) {
		prnMsg( __('The Order Number entered MUST be numeric'), 'error' );
		unset($OrderNumber);
	} else {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('Order Number') . ' - ' . $OrderNumber . '</div>';
	}
} else {
	if (isset($SelectedSupplier)) {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('For supplier') . ': ' . $SelectedSupplier . '</div>';
		echo '<input type="hidden" name="SelectedSupplier" value="' . $SelectedSupplier . '" />';
	}
	if (isset($SelectedStockItem)) {
		echo '<input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />';
	}
}

echo '		<div class="db-bottom-layout">
				<aside class="db-col-aside">';

if (!isset($OrderNumber) or $OrderNumber == '') {
	echo '			<div class="db-card">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Order Filters') . '</div>
						</div>
						<div class="db-card-body">
							<div class="db-form-group">
								<label class="db-label">' . __('Order Number') . ':</label>
								<input type="text" name="OrderNumber" class="db-input" autofocus="autofocus" maxlength="8" placeholder="' . __('Enter order #...') . '" />
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('Location') . ':</label>
								<select name="StockLocation" class="db-select">';

	if (!isset($_POST['DateFrom'])) {
		$DateSQL = "SELECT min(orddate) as fromdate,
							max(orddate) as todate
						FROM purchorders";
		$DateResult = DB_query($DateSQL);
		$DateRow = DB_fetch_array($DateResult);
		if ($DateRow['fromdate'] != null) {
			$DateFrom = $DateRow['fromdate'];
			$DateTo = $DateRow['todate'];
		} else {
			$DateFrom = date('Y-m-d');
			$DateTo = date('Y-m-d');
		}
	} else {
		$DateFrom = FormatDateForSQL($_POST['DateFrom']);
		$DateTo = FormatDateForSQL($_POST['DateTo']);
	}

	$SQL = "SELECT locations.loccode, locationname,(SELECT count(*) FROM locations) AS total FROM locations
				INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ErrMsg = __('Failed to retrieve location data');
	$ResultStkLocs = DB_query($SQL, $ErrMsg);
	$UserLocations = DB_num_rows($ResultStkLocs);
	$AllListed = false;
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		if (!isset($LocQty)){
			$LocQty = $MyRow['total'];
		}
		if (isset($_POST['StockLocation'])) {
			if ($_POST['StockLocation'] == 'ALLLOC'){
				if ($AllListed === false) {
					echo '<option selected="selected" value="ALLLOC">' . __('All') . '</option>';
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
					$AllListed = true;
				} else {
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				}
			} else {
				if ($MyRow['total'] == $UserLocations) {
					if ($AllListed === false){
						echo '<option value="ALLLOC">' . __('All') . '</option>';
						$AllListed = true;
					}
				}
				if ($MyRow['loccode'] == $_POST['StockLocation']){
					echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				} else {
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				}
			}
		} else {
			if ($MyRow['total'] == $UserLocations){
				if ($AllListed === false){
					echo '<option selected="selected" value="ALLLOC">' . __('All') . '</option>';
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
					$AllListed = true;
				} else {
					echo '<option value="' . $MyRow['loccode'] . '" >' . $MyRow['locationname'] . '</option>';
				}
			} else {
				if ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
					echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				} else {
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				}
			}
		}
	}
	echo '				</select>
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('Status') . ':</label>
								<select name="Status" class="db-select">';
	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		echo '<option selected="selected" value="Pending_Authorised">' . __('Pending & Authorised') . '</option>';
	} else {
		echo '<option value="Pending_Authorised">' . __('Pending & Authorised') . '</option>';
	}
	if (isset($_POST['Status'])){
		if ($_POST['Status'] == 'Pending') {
			echo '<option selected="selected" value="Pending">' . __('Pending') . '</option>';
		} else {
			echo '<option value="Pending">' . __('Pending') . '</option>';
		}
		if ($_POST['Status'] == 'Authorised') {
			echo '<option selected="selected" value="Authorised">' . __('Authorised') . '</option>';
		} else {
			echo '<option value="Authorised">' . __('Authorised') . '</option>';
		}
		if ($_POST['Status'] == 'Cancelled') {
			echo '<option selected="selected" value="Cancelled">' . __('Cancelled') . '</option>';
		} else {
			echo '<option value="Cancelled">' . __('Cancelled') . '</option>';
		}
		if ($_POST['Status'] == 'Rejected') {
			echo '<option selected="selected" value="Rejected">' . __('Rejected') . '</option>';
		} else {
			echo '<option value="Rejected">' . __('Rejected') . '</option>';
		}
	}
	$Checked = (isset($_POST['PODetails']))?'checked="checked"':'';
	echo '				</select>
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('Dates') . ':</label>
								<div style="display: flex; flex-direction: column; gap: 8px;">
									<input name="DateFrom" value="' . date('Y-m-d',strtotime($DateFrom)) . '" class="db-input" type="date" />
									<input name="DateTo" value="' . date('Y-m-d',strtotime($DateTo)) . '" class="db-input" type="date" />
								</div>
							</div>
							<div class="db-form-group">
								<label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
									<input type="checkbox" name="PODetails" ' . $Checked . ' />
									<span style="font-size: 0.85rem;">' . __('Show Details') . '</span>
								</label>
							</div>
							<div style="margin-top: 10px;">
								<button type="submit" name="SearchOrders" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
									<i class="fas fa-search"></i> ' . __('Search Orders') . '
								</button>
							</div>
						</div>
					</div>';
}

$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '				<div class="db-card">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-boxes"></i> ' . __('Item Search') . '</div>
						</div>
						<div class="db-card-body">
							<div class="db-form-group">
								<label class="db-label">' . __('Category') . ':</label>
								<select name="StockCat" class="db-select">';
if (DB_num_rows($Result1)>0){
	echo '<option value="All">' . __('All Categories') . '</option>';
}
while ($MyRow1 = DB_fetch_array($Result1)) {
	if (isset($_POST['StockCat']) and $MyRow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	}
}
echo '						</select>
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('Keywords') . ':</label>
								<input type="text" name="Keywords" class="db-input" placeholder="' . __('e.g. Widget...') . '" />
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('Stock Code') . ':</label>
								<input type="text" name="StockCode" class="db-input" placeholder="' . __('Code extract...') . '" />
							</div>
							<div style="display: flex; gap: 10px; margin-top: 10px;">
								<button type="submit" name="ResetPart" class="db-btn db-btn-secondary" style="flex: 1; justify-content: center;">
									<i class="fas fa-sync"></i>
								</button>
								<button type="submit" name="SearchParts" class="db-btn db-btn-primary" style="flex: 2; justify-content: center;">
									<i class="fas fa-search"></i> ' . __('Search') . '
								</button>
							</div>
						</div>
					</div>
				</aside> <!-- End Sidebar -->

				<main class="db-col-main">';


	if (isset($StockItemsResult)) {
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">
						<div style="display: flex; align-items: center; gap: 10px;">
							<i class="fas fa-list"></i> ' . __('Stock Item Results') . '
						</div>
						<span class="db-badge db-badge-info">' . DB_num_rows($StockItemsResult) . ' ' . __('Found') . '</span>
					</div>
				</div>
				<div class="db-card-body">
					<div class="table-responsive">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Item Code') . '</th>
									<th>' . __('Description') . '</th>
									<th class="text-right">' . __('On Hand') . '</th>
									<th class="text-right">' . __('Outstanding') . '</th>
									<th>' . __('Units') . '</th>
								</tr>
							</thead>
							<tbody>';



	$StockStr = '(';
	$q = 0;
	while ($MyRow = DB_fetch_array($StockItemsResult)){
		if ($q>0) {
			$StockStr .=',';
		}
		$StockStr .="'".$MyRow['stockid']."'";
		$q++;
	}
	$StockStr .=')';
	$QOHSQL = "SELECT stockid, sum(quantity) FROM locstock INNER JOIN locationusers ON locstock.loccode=locationusers.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' GROUP BY stockid";
	$QOHResult = DB_query($QOHSQL);
	$QOH = array();
	while ($MyRow=DB_fetch_array($QOHResult)){
		$QOH[$MyRow['stockid']] = $MyRow[1];
	}
	DB_data_seek($StockItemsResult,0);

		while ($MyRow = DB_fetch_array($StockItemsResult)) {
			$MyRow['qoh'] = isset($QOH[$MyRow['stockid']]) ? $QOH[$MyRow['stockid']] : 0;
			echo '<tr>
					<td><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-outline db-btn-sm" style="font-family: monospace;">' . $MyRow['stockid'] . '</button></td>
					<td class="db-font-semibold">' . $MyRow['description'] . '</td>
					<td class="text-right">' . locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']) . '</td>
					<td class="text-right db-font-bold text-primary">' . locale_number_format($MyRow['qord'], $MyRow['decimalplaces']) . '</td>
					<td><span class="db-badge">' . $MyRow['units'] . '</span></td>
				</tr>';
		}

		echo '      </tbody>
						</table>
					</div>
				</div>
			</div>';
	} elseif (isset($_POST['SearchParts'])) {
		echo '<div class="db-card">
				<div class="db-card-body" style="text-align: center; padding: 40px;">
					<i class="fas fa-search" style="font-size: 3rem; color: var(--db-border); margin-bottom: 20px;"></i>
					<div class="db-font-bold" style="font-size: 1.1rem; color: var(--db-text-muted);">' . __('No items found matching your criteria') . '</div>
				</div>
			</div>';
	}

else {
	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		$StatusCriteria = " AND (purchorders.status='Pending' OR purchorders.status='Authorised' OR purchorders.status='Printed') ";
	} elseif ($_POST['Status'] == 'Authorised') {
		$StatusCriteria = " AND (purchorders.status='Authorised' OR purchorders.status='Printed')";
	} elseif ($_POST['Status'] == 'Pending') {
		$StatusCriteria = " AND purchorders.status='Pending' ";
	} elseif ($_POST['Status'] == 'Rejected') {
		$StatusCriteria = " AND purchorders.status='Rejected' ";
	} elseif ($_POST['Status'] == 'Cancelled') {
		$StatusCriteria = " AND purchorders.status='Cancelled' ";
	}
	if (isset($OrderNumber) AND $OrderNumber != '') {
		$SQL = "SELECT purchorders.orderno,
						purchorders.realorderno,
						suppliers.suppname,
						purchorders.orddate,
						purchorders.deliverydate,
						purchorders.initiator,
						purchorders.status,
						purchorders.requisitionno,
						purchorders.allowprint,
						suppliers.currcode,
						currencies.decimalplaces AS currdecimalplaces,
						group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
						SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
				FROM purchorders INNER JOIN purchorderdetails
				ON purchorders.orderno=purchorderdetails.orderno
				INNER JOIN locationusers
				ON purchorders.intostocklocation=locationusers.loccode
				AND userid='" . $_SESSION['UserID'] . "' AND canview = 1
				INNER JOIN suppliers
				ON purchorders.supplierno = suppliers.supplierid
				INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				WHERE purchorderdetails.completed=0
				AND purchorders.orderno='" . $OrderNumber . "'
				GROUP BY purchorders.orderno,
					suppliers.suppname,
					purchorders.orddate,
					purchorders.status,
					purchorders.initiator,
					purchorders.requisitionno,
					purchorders.allowprint,
					suppliers.currcode
				ORDER BY purchorders.orderno ASC";
	} else {
		//$OrderNumber is not set
		if (isset($SelectedSupplier)) {
			if (!isset($_POST['StockLocation'])) {
				if (isset($UserLocations) AND isset($LocQty) AND $UserLocations == $LocQty) {
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				} else {
					$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				}
			} else {
				if ($_POST['StockLocation'] == 'ALLLOC'){
					$WhereStockLocation = ' ';
				} else {
					$WhereStockLocation = " AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "' ";
				}
			}

			if (isset($SelectedStockItem)) {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorderdetails.itemcode='" . $SelectedStockItem . "'
						AND purchorders.supplierno='" . $SelectedSupplier . "'
						" . $WhereStockLocation
						 . $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			} else {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorders.supplierno='" . $SelectedSupplier . "'
						" . $WhereStockLocation
						 . $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			}
		} //isset($SelectedSupplier)
		else { //no supplier selected
			if (!isset($_POST['StockLocation'])) {
				if (isset($UserLocations) AND isset($LocQty) AND $UserLocations == $LocQty) {
					$WhereStockLocation = " ";
					$_POST['StockLocation'] = 'ALLLOC';
				} else {
					$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
					$WhereStockLocation = " AND purchorders.intostocklocation ='" . $_POST['StockLocation'] . "' ";
				}
			} else {
				if ($_POST['StockLocation'] == 'ALLLOC'){
					$WhereStockLocation = ' ';
				} else {
					$WhereStockLocation = " AND purchorders.intostocklocation = '" . $_POST['StockLocation'] . "'";
				}
			}
			if (isset($SelectedStockItem) AND isset($_POST['StockLocation'])) {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						AND purchorderdetails.itemcode='" . $SelectedStockItem . "'
						" . $WhereStockLocation .
						 $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			} else {
				$SQL = "SELECT purchorders.realorderno,
							purchorders.orderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.deliverydate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces AS currdecimalplaces,
							group_concat(CASE WHEN quantityord>quantityrecd THEN CONCAT(itemcode,'--',round(quantityord-quantityrecd)) ELSE '' END) as bal,
							SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue
						FROM purchorders INNER JOIN purchorderdetails
						ON purchorders.orderno = purchorderdetails.orderno
						INNER JOIN suppliers
						ON  purchorders.supplierno = suppliers.supplierid
						INNER JOIN currencies
						ON suppliers.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						WHERE purchorderdetails.completed=0
						AND orddate>='" . $DateFrom . "'
						AND orddate<='" . $DateTo . "'
						" . $WhereStockLocation .
						  $StatusCriteria . "
						GROUP BY purchorders.orderno,
							purchorders.realorderno,
							suppliers.suppname,
							purchorders.orddate,
							purchorders.status,
							purchorders.initiator,
							purchorders.requisitionno,
							purchorders.allowprint,
							suppliers.currcode,
							currencies.decimalplaces
						ORDER BY purchorders.orderno ASC";
			}
		} //end selected supplier
	} //end not order number selected

	$ErrMsg = __('No orders were returned by the SQL because');
	$PurchOrdersResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PurchOrdersResult) > 0) {
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">
						<div style="display: flex; align-items: center; gap: 10px;">
							<i class="fas fa-clipboard-list"></i> ' . __('Purchase Order Results') . '
						</div>
						<span class="db-badge db-badge-success">' . DB_num_rows($PurchOrdersResult) . ' ' . __('Orders') . '</span>
					</div>
				</div>
				<div class="db-card-body">
					<div class="table-responsive">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Order #') . '</th>
									<th>' . __('Date Info') . '</th>
									<th>' . __('Initiator') . '</th>
									<th>' . __('Supplier Details') . '</th>';


	
	if (isset($_POST['PODetails'])) {
		echo '<th>' . __('PO Details') . '</th>';
	}

	echo '							<th>' . __('Currency') . '</th>';

	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
		echo '<th class="text-right">' . __('Order Total') . '</th>';
	}
	echo '							<th class="text-center">' . __('Status') . '</th>
									<th class="text-center">' . __('Actions') . '</th>
								</tr>
							</thead>
							<tbody>';

	while ($MyRow = DB_fetch_array($PurchOrdersResult)) {
		$Bal = '';
		if (isset($_POST['PODetails'])) {
			//lets retrieve the PO balance here to make it a standard sql query.
			$BalSql = "SELECT itemcode, quantityord - quantityrecd as balance FROM purchorderdetails WHERE orderno = '" . $MyRow['orderno'] . "'";
			$ErrMsg = __('Failed to retrieve purchorder details');
			$BalResult = DB_query($BalSql, $ErrMsg);
			if (DB_num_rows($BalResult)>0) {
				while ($BalRow = DB_fetch_array($BalResult)) {
					$Bal .= '<br/>' . $BalRow['itemcode'] . ' -- ' . $BalRow['balance'];
				}
			}
		}
		if (isset($_POST['PODetails'])) {
			$BalRow = '<td width="250" style="word-break:break-all">' . $Bal . '</td>';
		} else {
			$BalRow = '';
		}

		$ModifyPage = $RootPath . '/PO_Header.php?identifier=' . $identifier . '&ModifyOrderNumber=' . $MyRow['orderno'];
		if ($MyRow['status'] == 'Printed') {
			$ReceiveOrder = '<a href="' . $RootPath . '/GoodsReceived.php?PONumber=' . $MyRow['orderno'] . '">' . __('Receive') . '</a>';
		} else {
			$ReceiveOrder = '';
		}
		if ($MyRow['status'] == 'Authorised' AND $MyRow['allowprint'] == 1) {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $MyRow['orderno'] . '">' . __('Print') . '</a>';
		} elseif ($MyRow['status'] == 'Authorisied' AND $MyRow['allowprint'] == 0) {
			$PrintPurchOrder = __('Printed');
		} elseif ($MyRow['status'] == 'Printed') {
			$PrintPurchOrder = '<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $MyRow['orderno'] . '&amp;realorderno=' . $MyRow['realorderno'] . '&amp;ViewingOnly=2">
				' . __('Print Copy') . '</a>';
		} else {
			$PrintPurchOrder = __('N/A');
		}


		$FormatedOrderDate = ConvertSQLDate($MyRow['orddate']);
		$FormatedDeliveryDate = ConvertSQLDate($MyRow['deliverydate']);
		$FormatedOrderValue = locale_number_format($MyRow['ordervalue'], $MyRow['currdecimalplaces']);
		$SQL = "SELECT realname FROM www_users WHERE userid='" . $MyRow['initiator'] . "'";
		$UserResult = DB_query($SQL);
		$MyUserRow = DB_fetch_array($UserResult);
		$InitiatorName = $MyUserRow['realname'] ?? '';

		echo '<tr>
				<td>
					<a href="' . $ModifyPage . '" class="db-font-bold text-primary" style="font-size: 1.1rem;">#' . $MyRow['orderno'] . '</a>
					<div style="font-size: 0.75rem; color: var(--db-text-muted); margin-top: 4px;">
						<i class="fas fa-hashtag"></i> ' . $MyRow['realorderno'] . '
					</div>
				</td>
				<td>
					<div class="db-font-semibold">' . $FormatedOrderDate . '</div>
					<div style="font-size: 0.75rem; color: var(--db-text-muted);">
						<i class="fas fa-truck"></i> ' . $FormatedDeliveryDate . '
					</div>
				</td>
				<td>
					<div class="db-font-medium">' . $InitiatorName . '</div>
					<div style="font-size: 0.7rem; color: var(--db-text-muted); text-transform: uppercase;">' . $MyRow['initiator'] . '</div>
				</td>
				<td>
					<div class="db-font-bold" style="color: var(--db-text-main);">' . $MyRow['suppname'] . '</div>
					<div style="display: flex; gap: 5px; margin-top: 4px;">
						<span class="db-badge db-badge-info">' . $MyRow['currcode'] . '</span>
						' . (isset($_POST['PODetails']) ? '<span class="db-badge db-badge-secondary">' . __('Details Enabled') . '</span>' : '') . '
					</div>
				</td>
				' . (isset($_POST['PODetails']) ? '<td><div style="font-size: 0.8rem; border-left: 2px solid var(--db-border); padding-left: 8px;">' . $Bal . '</div></td>' : '');
		
		if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
			echo '<td class="text-right">
					<div class="db-font-bold text-main" style="font-size: 1rem;">' . $FormatedOrderValue . '</div>
					<div style="font-size: 0.7rem; color: var(--db-text-muted); text-align: right;">' . __('Total Value') . '</div>
				  </td>';
		}

		$StatusClass = 'db-badge-info';
		if ($MyRow['status'] == 'Authorised') $StatusClass = 'db-badge-success';
		if ($MyRow['status'] == 'Cancelled') $StatusClass = 'db-badge-danger';
		if ($MyRow['status'] == 'Rejected') $StatusClass = 'db-badge-warning';
		if ($MyRow['status'] == 'Printed') $StatusClass = 'db-badge-info';

		echo '<td class="text-center"><span class="db-badge ' . $StatusClass . '" style="padding: 4px 12px; font-size: 0.75rem;">' . strtolower(__($MyRow['status'])) . '</span></td>
				<td class="text-right">
					<div style="display: flex; justify-content: flex-end; gap: 8px;">';

		
		if ($PrintPurchOrder != __('N/A') && $PrintPurchOrder != __('Printed')) {
			echo preg_replace('/<a /', '<a class="db-btn db-btn-outline db-btn-sm" ', $PrintPurchOrder);
		}

		if ($ReceiveOrder != '') {
			echo preg_replace('/<a /', '<a class="db-btn db-btn-primary db-btn-sm" ', $ReceiveOrder);
		}
		
		echo '		</div>
				</td>
			</tr>';
		}

					echo '</tbody>
						</table>
					</div>
				</div>
			</div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-body" style="text-align: center; padding: 60px;">
					<div style="width: 80px; height: 80px; background: var(--db-bg-workspace); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
						<i class="fas fa-clipboard-list" style="font-size: 2rem; color: var(--db-text-muted);"></i>
					</div>
					<h3 class="db-font-bold" style="color: var(--db-text-main); font-size: 1.25rem;">' . __('No orders found') . '</h3>
					<p style="color: var(--db-text-muted); max-width: 300px; margin: 10px auto;">' . __('Adjust your filters or try searching for a different order number.') . '</p>
				</div>
			</div>';
	}
}
echo '					</main>
				</div> <!-- End db-bottom-layout -->
			</form>
		</div> <!-- End db-page -->';

include(__DIR__ . '/includes/footer.php');
