<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search Shipments');
$ViewTopic = 'Shipments';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Track and manage incoming shipments and their costings') . '</p>
			</div>
		</div>';

if (isset($_GET['SelectedStockItem'])){
	$SelectedStockItem=$_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])){
	$SelectedStockItem=$_POST['SelectedStockItem'];
}

if (isset($_GET['ShiptRef'])){
	$ShiptRef=$_GET['ShiptRef'];
} elseif (isset($_POST['ShiptRef'])){
	$ShiptRef=$_POST['ShiptRef'];
}

if (isset($_GET['SelectedSupplier'])){
	$SelectedSupplier=$_GET['SelectedSupplier'];
} elseif (isset($_POST['SelectedSupplier'])){
	$SelectedSupplier=$_POST['SelectedSupplier'];
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['ResetPart'])) {
     unset($SelectedStockItem);
}

if (isset($ShiptRef) AND $ShiptRef!='') {
	if (!is_numeric($ShiptRef)){
		  prnMsg( __('The Shipment Number entered MUST be numeric'), 'error' );
		  unset ($ShiptRef);
	} else {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('Shipment Number'). ' - '. $ShiptRef . '</div>';
	}
} else {
	if (isset($SelectedSupplier)) {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' .__('For supplier'). ': '. $SelectedSupplier . '</div>';
		echo '<input type="hidden" name="SelectedSupplier" value="'. $SelectedSupplier. '" />';
	}
	if (isset($SelectedStockItem)) {
		echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-4);">' . __('for the part'). ': ' . $SelectedStockItem . '</div>';
		echo '<input type="hidden" name="SelectedStockItem" value="'. $SelectedStockItem. '" />';
	}
}

if (isset($_POST['SearchParts'])) {

	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		echo '<br />';
		prnMsg( __('Stock description keywords have been used in preference to the Stock code extract entered'),'info');
	}
	$SQL = "SELECT stockmaster.stockid,
			description,
			decimalplaces,
			SUM(locstock.quantity) AS qoh,
			units,
			SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qord
		FROM stockmaster INNER JOIN locstock
			ON stockmaster.stockid = locstock.stockid
		INNER JOIN purchorderdetails
			ON stockmaster.stockid=purchorderdetails.itemcode";

	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL .= " WHERE purchorderdetails.shiptref IS NOT NULL
			AND purchorderdetails.shiptref<>0
			AND stockmaster.description " . LIKE . " '" . $SearchString . "'
			AND categoryid='" . $_POST['StockCat'] . "'";

	 } elseif ($_POST['StockCode']){

		$SQL .= " WHERE purchorderdetails.shiptref IS NOT NULL
			AND purchorderdetails.shiptref<>0
			AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
			AND categoryid='" . $_POST['StockCat'] ."'";

	 } elseif (!$_POST['StockCode'] AND !$_POST['Keywords']) {
		$SQL .= " WHERE purchorderdetails.shiptref IS NOT NULL
			AND purchorderdetails.shiptref<>0
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";

	 }
	$SQL .= "  GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.decimalplaces,
						stockmaster.units";

	$ErrMsg = __('No Stock Items were returned from the database because'). ' - '. DB_error_msg();
	$StockItemsResult = DB_query($SQL, $ErrMsg);

}

if (!isset($ShiptRef) or $ShiptRef==""){
	echo '<div class="db-card">
			<div class="db-card-title">' . __('Search Criteria') . '</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-form-group">
						<label class="db-form-label">', __('Shipment Number'). ':</label>
						<input type="text" name="ShiptRef" class="db-form-input" maxlength="10" />
					</div>
					<div class="db-form-group">
						<label class="db-form-label">', __('Into Stock Location').':</label>
						<select name="StockLocation" class="db-form-select"> ';
	$SQL = "SELECT loccode, locationname FROM locations";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		if (isset($_POST['StockLocation'])){
			if ($MyRow['loccode'] == $_POST['StockLocation']){
			echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			} else {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
			}
		} elseif ($MyRow['loccode']==$_SESSION['UserStockLocation']){
			$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
			echo '<option selected="selected" value="' . $MyRow['loccode'] . '">' . $MyRow['locationname']  . '</option>';
		} else {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname']  . '</option>';
		}
	}

	echo '				</select>
					</div>
					<div class="db-form-group">
						<label class="db-form-label">', __('Search For'), '</label>
						<select name="OpenOrClosed" class="db-form-select">';
	if (isset($_POST['OpenOrClosed']) AND $_POST['OpenOrClosed']==1){
		echo '<option selected="selected" value="1">' .  __('Closed Shipments Only')  . '</option>';
		echo '<option value="0">' .  __('Open Shipments Only')  . '</option>';
	} else {
		$_POST['OpenOrClosed']=0;
		echo '<option value="1">' .  __('Closed Shipments Only')  . '</option>';
		echo '<option selected="selected" value="0">' .  __('Open Shipments Only')  . '</option>';
	}
	echo '				</select>
					</div>
				</div> <!-- End Grid -->
			</div> <!-- End Card Body -->
			<div class="db-card-footer">
				<div class="db-form-actions">
					<button type="submit" name="SearchShipments" class="db-btn db-btn-primary">' . __('Search Shipments') . '</button>
				</div>
			</div>
		</div>';
}

$SQL="SELECT categoryid,
		categorydescription
	FROM stockcategory
	WHERE stocktype<>'D'
	ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '<div class="db-card" style="margin-top: var(--space-6);">
		<div class="db-card-title">' . __('Search by Part') . '</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-3">
				<div class="db-form-group">
					<label class="db-form-label">' . __('Stock Category') . ':</label>
					<select name="StockCat" class="db-form-select">';

while ($MyRow1 = DB_fetch_array($Result1)) {
	if (isset($_POST['StockCat']) and $MyRow1['categoryid']==$_POST['StockCat']){
		echo '<option selected="selected" value="'. $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription']  . '</option>';
	} else {
		echo '<option value="'. $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription']  . '</option>';
	}
}
echo '				</select>
				</div>
				<div class="db-form-group">
					<label class="db-form-label">' . __('Description Keywords') . ':</label>
					<input type="text" name="Keywords" class="db-form-input" placeholder="' . __('e.g. Widget') . '" maxlength="25" />
				</div>
				<div class="db-form-group">
					<label class="db-form-label">' . __('Stock Code Extract') . ':</label>
					<input type="text" name="StockCode" class="db-form-input" placeholder="' . __('e.g. W123') . '" maxlength="18" />
				</div>
			</div>
		</div>
		<div class="db-card-footer">
			<div class="db-form-actions">
				<button type="submit" name="SearchParts" class="db-btn db-btn-primary">' . __('Search Parts Now') . '</button>
				<button type="submit" name="ResetPart" class="db-btn db-btn-secondary">' . __('Show All') . '</button>
			</div>
		</div>
	</div>';

if (isset($StockItemsResult)) {

	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-body" style="padding: 0;">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>', __('Select'), '</th>
								<th>' .  __('Description') . '</th>
								<th class="text-right">' .  __('On Hand') . '</th>
								<th class="text-right">' .  __('Outstanding') . '</th>
								<th>' .  __('Units') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow=DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="', $MyRow['stockid'], '" class="db-btn db-btn-outline db-btn-sm">', $MyRow['stockid'], '</button></td>
				<td>', $MyRow['description'], '</td>
				<td class="text-right">', locale_number_format($MyRow['qoh'],$MyRow['decimalplaces']), '</td>
				<td class="text-right">', locale_number_format($MyRow['qord'],$MyRow['decimalplaces']), '</td>
				<td class="db-text-muted">', $MyRow['units'], '</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';

}
//end if stock search results to show
  else {

	//figure out the SQL required from the inputs available

	if (isset($ShiptRef) AND $ShiptRef !="") {
		$SQL = "SELECT shipments.shiptref,
				vessel,
				voyageref,
				suppliers.suppname,
				shipments.eta,
				shipments.closed
			FROM shipments INNER JOIN suppliers
				ON shipments.supplierid = suppliers.supplierid
			WHERE shipments.shiptref='". $ShiptRef . "'";
	} else {
		$SQL = "SELECT DISTINCT shipments.shiptref, vessel, voyageref, suppliers.suppname, shipments.eta, shipments.closed
			FROM shipments INNER JOIN suppliers
				ON shipments.supplierid = suppliers.supplierid
			INNER JOIN purchorderdetails
				ON purchorderdetails.shiptref=shipments.shiptref
			INNER JOIN purchorders
				ON purchorderdetails.orderno=purchorders.orderno";

		if (isset($SelectedSupplier)) {

			if (isset($SelectedStockItem)) {
					$SQL .= " WHERE purchorderdetails.itemcode='". $SelectedStockItem ."'
						AND shipments.supplierid='" . $SelectedSupplier ."'
						AND purchorders.intostocklocation = '". $_POST['StockLocation'] . "'
						AND shipments.closed='" . $_POST['OpenOrClosed'] . "'";
			} else {
				$SQL .= " WHERE shipments.supplierid='" . $SelectedSupplier ."'
					AND purchorders.intostocklocation = '". $_POST['StockLocation'] . "'
					AND shipments.closed='" . $_POST['OpenOrClosed'] ."'";
			}
		} else { //no supplier selected
			if (isset($SelectedStockItem)) {
				$SQL .= " WHERE purchorderdetails.itemcode='". $SelectedStockItem ."'
					AND purchorders.intostocklocation = '". $_POST['StockLocation'] . "'
					AND shipments.closed='" . $_POST['OpenOrClosed'] . "'";
			} else {
				$SQL .= " WHERE purchorders.intostocklocation = '". $_POST['StockLocation'] . "'
					AND shipments.closed='" . $_POST['OpenOrClosed'] . "'";
			}

		} //end selected supplier
	} //end not order number selected

	$ErrMsg = __('No shipments were returned by the SQL because');
	$ShipmentsResult = DB_query($SQL, $ErrMsg);


	if (DB_num_rows($ShipmentsResult)>0){
		/*show a table of the shipments returned by the SQL */

		echo '<div class="db-card" style="margin-top: var(--space-6);">
				<div class="db-card-body" style="padding: 0;">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' .  __('Shipment'). '</th>
									<th>' .  __('Supplier'). '</th>
									<th>' .  __('Vessel'). '</th>
									<th>' .  __('Voyage'). '</th>
									<th>' .  __('ETA'). '</th>
									<th class="text-center">' . __('Actions') . '</th>
								</tr>
							</thead>
							<tbody>';

		while ($MyRow=DB_fetch_array($ShipmentsResult)) {

			$URL_Modify_Shipment = $RootPath . '/Shipments.php?SelectedShipment=' . $MyRow['shiptref'];
			$URL_View_Shipment = $RootPath . '/ShipmentCosting.php?SelectedShipment=' . $MyRow['shiptref'];

			$FormatedETA = ConvertSQLDate($MyRow['eta']);
			/* ShiptRef   Supplier  Vessel  Voyage  ETA */

			if ($MyRow['closed']==0){
				$URL_Close_Shipment = $URL_View_Shipment . '&amp;Close=Yes';
				echo '<tr>
						<td class="db-font-semibold">', $MyRow['shiptref'], '</td>
						<td>', $MyRow['suppname'], '</td>
						<td>', $MyRow['vessel'], '</td>
						<td class="db-text-muted">', $MyRow['voyageref'], '</td>
						<td class="text-nowrap">', $FormatedETA, '</td>
						<td class="text-center">
							<div class="db-form-actions" style="justify-content: center; gap: var(--space-2);">
								<a href="', $URL_View_Shipment, '" class="db-btn db-btn-outline db-btn-sm">' . __('Costing') . '</a>
								<a href="', $URL_Modify_Shipment, '" class="db-btn db-btn-outline db-btn-sm">' . __('Modify') . '</a>
								<a href="', $URL_Close_Shipment, '" class="db-btn db-btn-danger db-btn-sm">' . __('Close') . '</a>
							</div>
						</td>
					</tr>';
			} else {
				echo '<tr>
						<td class="db-font-semibold">', $MyRow['shiptref'], '</td>
						<td>', $MyRow['suppname'], '</td>
						<td>', $MyRow['vessel'], '</td>
						<td class="db-text-muted">', (isset($MyRow['voyage']) ? $MyRow['voyage'] : $MyRow['voyageref']), '</td>
						<td class="text-nowrap">', $FormatedETA, '</td>
						<td class="text-center">
							<a href="', $URL_View_Shipment, '" class="db-btn db-btn-outline db-btn-sm">' . __('Costing') . '</a>
						</td>
					</tr>';
			}
		}
		echo '					</tbody>
						</table>
					</div>
				</div>
			</div>';
	} // end if shipments to show
}

echo '</div> <!-- End db-page -->';
      </form>';
include(__DIR__ . '/includes/footer.php');
