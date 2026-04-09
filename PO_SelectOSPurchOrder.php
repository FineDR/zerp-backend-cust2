<?php

$PricesSecurity = 12;

require(__DIR__ . '/includes/session.php');

$Title = __('Search Outstanding Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';

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

echo '<div class="db-page-header">
		<div>
			<h1 class="db-page-title">' . $Title . '</h1>
			<p class="db-page-subtitle">' . __('Manage and track outstanding purchase orders') . '</p>
		</div>
		<div class="db-page-actions">';
	
	$AddPOUrl = $RootPath . '/PO_Header.php?NewOrder=Yes' . (isset($SelectedSupplier) ? '&amp;SupplierID=' . $SelectedSupplier : '');
	echo '	<a href="' . $AddPOUrl . '" class="db-btn db-btn-primary">
				<svg class="db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
				' . __('Add Purchase Order') . '
			</a>
		</div>
	</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

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


/* Not appropriate really to restrict search by date since user may miss older ouststanding orders
$OrdersAfterDate = date("d/m/Y",mktime(0,0,0,date("m")-2,date("d"),date("Y")));
*/

if (!isset($OrderNumber) or $OrderNumber == '') {
	echo '<div class="db-grid db-grid-2" style="margin-top: var(--space-4);">
			<div class="db-card">
				<div class="db-card-title">
					<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg> ' . __('Order Search Filters') . '</span>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Order Number') . ':</label>
						<input type="text" name="OrderNumber" class="db-form-input" autofocus="autofocus" maxlength="8" placeholder="' . __('Enter order #...') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('Into Stock Location') . ':</label>
						<select name="StockLocation" class="db-form-select">';

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
		if (isset($_POST['StockLocation'])) {//The user has selected location
			if ($_POST['StockLocation'] == 'ALLLOC'){//user have selected all locations
				if ($AllListed === false) {//it's the first loop
					echo '<option selected="selected" value="ALLLOC">' . __('All') . '</option>';
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
					$AllListed = true;
				} else { //it's not the first loop
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				}

			} else {//user have not selected all locations; There are two possibilities that users have right, but not choose all; or vice visa
				if ($MyRow['total'] == $UserLocations) { //user have allloc right
					if ($AllListed === false){//first loop
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
		} else {//users have not selected locations
			if ($MyRow['total'] == $UserLocations){//users have right to submit All locations
				if ($AllListed === false){//first loop
					echo '<option selected="selected" value="ALLLOC">' . __('All') . '</option>';//default value is all
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
					$AllListed = true;
				} else {//not first loop
					echo '<option value="' . $MyRow['loccode'] . '" >' . $MyRow['locationname'] . '</option>';
				}
			} else {//no right to submit all locations
				if ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
					echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				} else {
					echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
				}
			}

		}
	}
	echo '      </select>
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('Order Status') . ':</label>
						<select name="Status" class="db-form-select">';
	if (!isset($_POST['Status']) OR $_POST['Status'] == 'Pending_Authorised') {
		echo '<option selected="selected" value="Pending_Authorised">' . __('Pending and Authorised') . '</option>';
	} else {
		echo '<option value="Pending_Authorised">' . __('Pending and Authorised') . '</option>';
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
	echo '      </select>
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('Orders Between') . ':</label>
						<div class="db-input-group">
							<input name="DateFrom" value="' . date('Y-m-d',strtotime($DateFrom)) . '" class="db-form-input" type="date" />
							<span class="db-input-group-text">' . __('to') . '</span>
							<input name="DateTo" value="' . date('Y-m-d',strtotime($DateTo)) . '" class="db-form-input" type="date" />
						</div>
					</div>
					<div class="db-form-group">
						<label class="db-checkbox-container">
							<input type="checkbox" name="PODetails" ' . $Checked . ' />
							<span class="db-checkbox-label">' . __('Show PO Details') . '</span>
						</label>
					</div>
					<div class="db-form-actions">
						<button type="submit" name="SearchOrders" class="db-btn db-btn-primary">' . __('Search Orders') . '</button>
					</div>
				</div>
			</div>';
}

$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '		<div class="db-card">
				<div class="db-card-title">
					<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 21-4.3-4.3"/><circle cx="11" cy="11" r="8"/><path d="M11 8v6"/><path d="M8 11h6"/></svg> ' . __('Search By Stock Item') . '</span>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Select a stock category') . ':</label>
						<select name="StockCat" class="db-form-select">';
if (DB_num_rows($Result1)>0){
	echo '<option value="All">' . __('All') . '</option>';
}
while ($MyRow1 = DB_fetch_array($Result1)) {
	if (isset($_POST['StockCat']) and $MyRow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	}
} //end loop through categories
echo '						</select>';

					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('Description Keywords') . ':</label>
						<input type="text" name="Keywords" class="db-form-input" placeholder="' . __('Enter text extracts...') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('OR Stock Code Extract') . ':</label>
						<input type="text" name="StockCode" class="db-form-input" placeholder="' . __('Enter code extract...') . '" />
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

	$StocksStr = '(';
	$q = 0;
	while ($MyRow = DB_fetch_array($StockItemsResult)){
		if ($q>0) {
			$StockStr .=',';
		}
		$StockStr .="'".$MyRow['stockid']."'";
	}
	$StockStr .=')';
	$QOHSQL = "SELECT stockid, sum(quantity) FROM locstock INNER JOIN locationusers ON locationusers.loccode=locationusers.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' GROUP BY stockid";
	$QOHResult = DB_query($QOHSQL);
	$QOH = array();
	while ($MyRow=DB_fetch_array($QOHResult)){
		$QOH[$MyRow['stockid']] = $MyRow[1];
	}
	DB_data_seek($StockItemsResult,0);

	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		$MyRow['qoh'] = isset($QOH[$MyRow['stockid']]) ? $QOH[$MyRow['stockid']] : 0;
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-outline db-btn-sm">' . $MyRow['stockid'] . '</button></td>
				<td>' . $MyRow['description'] . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qord'], $MyRow['decimalplaces']) . '</td>
				<td>' . $MyRow['units'] . '</td>
			</tr>';
	}
	echo '      </tbody>
					</table>
				</div>
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
		echo '<div class="db-card" style="margin-top: var(--space-4);">
				<div class="db-card-title">' . __('Purchase Order Results') . '</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Order #') . '</th>
									<th>' . __('Order Date') . '</th>
									<th>' . __('Delivery Date') . '</th>
									<th>' . __('Initiator') . '</th>
									<th>' . __('Supplier') . '</th>';
	
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
				<td><a href="' . $ModifyPage . '" class="db-font-bold text-primary">' . $MyRow['orderno'] . '</a></td>
				<td class="text-nowrap">' . $FormatedOrderDate . '</td>
				<td class="text-nowrap">' . $FormatedDeliveryDate . '</td>
				<td class="db-text-muted">' . $InitiatorName . '</td>
				<td class="db-font-semibold">' . $MyRow['suppname'] . '</td>
				' . (isset($_POST['PODetails']) ? '<td>' . $Bal . '</td>' : '') . '
				<td><span class="db-badge db-badge-info">' . $MyRow['currcode'] . '</span></td>';
		
		if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($PricesSecurity)) {
			echo '<td class="text-right db-font-bold">' . $FormatedOrderValue . '</td>';
		}

		$StatusClass = 'db-badge-info';
		if ($MyRow['status'] == 'Authorised') $StatusClass = 'db-badge-success';
		if ($MyRow['status'] == 'Cancelled') $StatusClass = 'db-badge-danger';
		if ($MyRow['status'] == 'Rejected') $StatusClass = 'db-badge-warning';
		if ($MyRow['status'] == 'Printed') $StatusClass = 'db-badge-info';

		echo '<td class="text-center"><span class="db-badge ' . $StatusClass . '">' . __($MyRow['status']) . '</span></td>
				<td class="text-center">
					<div class="db-form-actions" style="justify-content: center; gap: var(--space-2);">';
		
		if ($PrintPurchOrder != __('N/A') && $PrintPurchOrder != __('Printed')) {
			echo preg_replace('/<a /', '<a class="db-btn db-btn-outline db-btn-sm" ', $PrintPurchOrder);
		} else {
			echo '<span class="db-text-muted" style="font-size: 0.75rem;">' . $PrintPurchOrder . '</span>';
		}

		if ($ReceiveOrder != '') {
			echo preg_replace('/<a /', '<a class="db-btn db-btn-secondary db-btn-sm" ', $ReceiveOrder);
		}
		
		echo '		</div>
				</td>
			</tr>';
	} 

	echo '			</tbody>
						</table>
					</div>
				</div>
			</div>';
	}
}
echo '</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
