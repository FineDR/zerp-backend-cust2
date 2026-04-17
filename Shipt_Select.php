<?php

// Shipt_Select.php - Architect Dashboard Modernization (Restored Design)

require(__DIR__ . '/includes/session.php');

$Title = __('Search Shipments');
$ViewTopic = 'Shipments';
$BookMark = '';

// Include Standard UI Assets
$ExtraHeadContent = '<style>
						body, html { overflow: hidden !important; height: 100vh !important; background: var(--surface-alt); }
						.ScriptTitle { display: none !important; }
						.db-page { height: calc(100vh - 64px); overflow: hidden; display: flex; flex-direction: column; padding: var(--space-6) !important; width: 100% !important; max-width: 100% !important; }
						.db-workspace { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: var(--space-6); padding-bottom: var(--space-6); }
                        .card-v2 { background: var(--surface); border: 1px solid var(--border-soft); box-shadow: var(--shadow-sm); }
					</style>';

include(__DIR__ . '/includes/header.php');

// Input Handling
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

if (isset($_POST['ResetPart'])) {
     unset($SelectedStockItem);
}

// Logic for Stock Search
if (isset($_POST['SearchParts'])) {
	$SQL = "SELECT stockmaster.stockid,
			description,
			decimalplaces,
			SUM(locstock.quantity) AS qoh,
			units,
			SUM(purchorder_details.quantityord-purchorder_details.quantityrecd) AS qord
		FROM stockmaster INNER JOIN locstock
			ON stockmaster.stockid = locstock.stockid
		INNER JOIN purchorder_details
			ON stockmaster.stockid=purchorder_details.itemcode";

	if ($_POST['Keywords']) {
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		$SQL .= " WHERE purchorder_details.shiptref IS NOT NULL
			AND purchorder_details.shiptref<>0
			AND stockmaster.description " . LIKE . " '" . $SearchString . "'
			AND categoryid='" . $_POST['StockCat'] . "'";
	 } elseif ($_POST['StockCode']){
		$SQL .= " WHERE purchorder_details.shiptref IS NOT NULL
			AND purchorder_details.shiptref<>0
			AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
			AND categoryid='" . $_POST['StockCat'] ."'";
	 } elseif (!$_POST['StockCode'] AND !$_POST['Keywords']) {
		$SQL .= " WHERE purchorder_details.shiptref IS NOT NULL
			AND purchorder_details.shiptref<>0
			AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
	 }
	$SQL .= "  GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units";
	$StockItemsResult = DB_query($SQL);
}

// -- MAIN PAGE CONTAINER --
echo '<div class="db-page">';

// -- HEADER SECTION --
echo '<div class="db-page-header" style="margin-bottom: var(--space-6); flex-shrink: 0;">
		<div class="db-header-row">
			<div class="db-header-main">
				<h1 class="db-page-title" style="font-size: 1.75rem; font-weight: 800; letter-spacing: -0.02em; color: var(--text-main);">' . __('Shipment Selection Console') . '</h1>
				<p class="db-page-subtitle" style="font-size: 0.9375rem; color: var(--text-muted); margin-top: 4px;">' . __('Locate, aggregate and manage incoming shipments with precision') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Shipments.php?NewShipment=Yes" class="db-btn db-btn-primary" style="height: 44px; padding: 0 24px;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 10px;"><path d="M12 5v14M5 12h14"></path></svg>
					' . __('Register New Shipment') . '
				</a>
			</div>
		</div>
	</div>';

echo '<div class="db-workspace">';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" style="display: contents;">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// -- SEARCH CRITERIA GRID (2 COLUMNS) --
echo '<div class="db-grid db-grid-2" style="gap: var(--space-6);">';

// Block 1: Shipment Search
echo '<div class="card-v2" style="padding: var(--space-5);">
		<div style="display: flex; align-items: center; gap: 12px; margin-bottom: var(--space-4); padding-bottom: var(--space-3); border-bottom: 1px solid var(--border-soft);">
			<div style="width: 32px; height: 32px; border-radius: 8px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
			</div>
			<h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-main);">' . __('Shipment Search Parameters') . '</h3>
		</div>
		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
			<div class="db-form-group">
				<label class="db-form-label">' . __('Shipment #') . '</label>
				<input type="text" name="ShiptRef" class="db-form-input" placeholder="e.g. 1024" maxlength="10" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Intake Location') . '</label>
				<select name="StockLocation" class="db-form-select">';
$SQL = "SELECT loccode, locationname FROM locations";
$ResultStkLocs = DB_query($SQL);
while ($MyRow=DB_fetch_array($ResultStkLocs)){
	$Selected = (isset($_POST['StockLocation']) && $MyRow['loccode'] == $_POST['StockLocation']) || (!isset($_POST['StockLocation']) && $MyRow['loccode']==$_SESSION['UserStockLocation']) ? 'selected="selected"' : '';
	echo '<option ' . $Selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
}
echo '			</select>
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Status Visibility') . '</label>
				<select name="OpenOrClosed" class="db-form-select">';
$SelectedClosed = (isset($_POST['OpenOrClosed']) && $_POST['OpenOrClosed']==1) ? 'selected="selected"' : '';
$SelectedOpen = (!isset($_POST['OpenOrClosed']) || $_POST['OpenOrClosed']==0) ? 'selected="selected"' : '';
echo '<option ' . $SelectedOpen . ' value="0">' . __('Open Shipments Only') . '</option>';
echo '<option ' . $SelectedClosed . ' value="1">' . __('Archive / Closed Only') . '</option>';
echo '			</select>
			</div>
			<div class="db-form-group" style="display: flex; align-items: flex-end;">
				<button type="submit" name="SearchShipments" class="db-btn db-btn-primary" style="width: 100%; height: 44px; justify-content: center;">' . __('Apply Filters') . '</button>
			</div>
		</div>
	</div>';

// Block 2: Part Search
$SQL="SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype<>'D' ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '<div class="card-v2" style="padding: var(--space-5);">
		<div style="display: flex; align-items: center; gap: 12px; margin-bottom: var(--space-4); padding-bottom: var(--space-3); border-bottom: 1px solid var(--border-soft);">
			<div style="width: 32px; height: 32px; border-radius: 8px; background: var(--success-soft); color: var(--success); display: flex; align-items: center; justify-content: center;">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
			</div>
			<h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-main);">' . __('Search by Stock Item') . '</h3>
		</div>
		<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-4);">
			<div class="db-form-group">
				<label class="db-form-label">' . __('Stock Category') . '</label>
				<select name="StockCat" class="db-form-select">';
while ($MyRow1 = DB_fetch_array($Result1)) {
	$Selected = (isset($_POST['StockCat']) && $MyRow1['categoryid']==$_POST['StockCat']) ? 'selected="selected"' : '';
	echo '<option ' . $Selected . ' value="'. $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription']  . '</option>';
}
echo '			</select>
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Part Code') . '</label>
				<input type="text" name="StockCode" class="db-form-input" placeholder="W123" maxlength="18" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Keywords') . '</label>
				<input type="text" name="Keywords" class="db-form-input" placeholder="e.g. Zinc Widget" maxlength="25" />
			</div>
			<div style="display: flex; gap: var(--space-2); align-items: flex-end;">
				<button type="submit" name="SearchParts" class="db-btn db-btn-primary" style="flex: 2; height: 44px; justify-content: center;">' . __('Find Items') . '</button>
				<button type="submit" name="ResetPart" value="Show All" class="db-btn db-btn-secondary" style="flex: 1; height: 44px; justify-content: center; background: var(--surface);">' . __('Reset') . '</button>
			</div>
		</div>
	</div>';

echo '</div>'; // End Search Grid

echo '</form>';

// Status Alerts (Vibrant Architect Style)
if (isset($ShiptRef) AND $ShiptRef!='') {
	echo '<div class="db-alert db-alert-info" style="border-radius: var(--radius-lg); padding: var(--space-4); display: flex; align-items: center; gap: 12px;">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="8"/></svg>
			<span>' . __('Filtering workspace for target shipment') . ': <strong style="color:var(--primary);">' . $ShiptRef . '</strong></span>
		  </div>';
}

// -- RESULTS WORKSPACE --
if (isset($StockItemsResult)) {
	echo '<div class="card-v2" style="overflow: hidden;">
			<div class="card-header-v2" style="padding: var(--space-4) var(--space-6); background: var(--surface-alt); border-bottom: 2px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center;">
				<h3 style="margin: 0; font-size: 1rem; font-weight: 800; color: var(--text-main);">' . __('Inventory Item Matches') . '</h3>
				<span class="db-badge" style="background: var(--primary-soft); color: var(--primary);">' . DB_num_rows($StockItemsResult) . ' ' . __('Matches') . '</span>
			</div>
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Select') . '</th>
							<th>' . __('Description') . '</th>
							<th class="text-right">' . __('On Hand') . '</th>
							<th class="text-right">' . __('Outstanding') . '</th>
							<th>' . __('Units') . '</th>
						</tr>
					</thead>
					<tbody>';
	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" /><button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-outline db-btn-sm">' . $MyRow['stockid'] . '</button></form></td>
				<td style="font-weight: 600;">' . $MyRow['description'] . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qoh'],$MyRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($MyRow['qord'],$MyRow['decimalplaces']) . '</td>
				<td class="db-text-muted">' . $MyRow['units'] . '</td>
			</tr>';
	}
	echo '				</tbody>
				</table>
			</div>
		</div>';
} else {
	// Shipment Results Table
	if (isset($ShiptRef) AND $ShiptRef !="") {
		$SQL = "SELECT shiptref, vessel, voyageref, suppliers.suppname, eta, closed
				FROM shipments INNER JOIN suppliers ON shipments.supplierid = suppliers.supplierid
				WHERE shipments.shiptref='". $ShiptRef . "'";
	} else {
		$SQL = "SELECT DISTINCT shipments.shiptref, vessel, voyageref, suppliers.suppname, shipments.eta, shipments.closed
				FROM shipments INNER JOIN suppliers ON shipments.supplierid = suppliers.supplierid
				INNER JOIN purchorder_details ON purchorder_details.shiptref=shipments.shiptref
				INNER JOIN purchorders ON purchorder_details.orderno=purchorders.orderno";

		$Where = " WHERE purchorders.intostocklocation = '". (isset($_POST['StockLocation']) ? $_POST['StockLocation'] : $_SESSION['UserStockLocation']) . "'
					AND shipments.closed='" . (isset($_POST['OpenOrClosed']) ? $_POST['OpenOrClosed'] : 0) . "'";

		if (isset($SelectedSupplier)) $Where .= " AND shipments.supplierid='" . $SelectedSupplier ."'";
		if (isset($SelectedStockItem)) $Where .= " AND purchorder_details.itemcode='". $SelectedStockItem ."'";

		$SQL .= $Where;
	}

	$ShipmentsResult = DB_query($SQL);

	if (DB_num_rows($ShipmentsResult) > 0) {
		echo '<div class="card-v2" style="overflow: hidden;">
				<div class="card-header-v2" style="padding: var(--space-4) var(--space-6); background: var(--surface-alt); border-bottom: 2px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center;">
					<div style="display: flex; align-items: center; gap: 12px;">
						<div style="width: 8px; height: 24px; background: var(--primary); border-radius: 4px;"></div>
						<h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; color: var(--text-main);">' . __('Primary Shipment Registry') . '</h3>
					</div>
					<span class="db-badge" style="background: var(--primary-soft); color: var(--primary); font-weight: 700;">' . DB_num_rows($ShipmentsResult) . ' ' . __('Records') . '</span>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Ref') . '</th>
								<th>' . __('Supplier') . '</th>
								<th>' . __('Vessel / Voyage') . '</th>
								<th>' . __('ETA Date') . '</th>
								<th class="text-center noPrint">' . __('Operational Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($MyRow = DB_fetch_array($ShipmentsResult)) {
			$URL_Modify = $RootPath . '/Shipments.php?SelectedShipment=' . $MyRow['shiptref'];
			$URL_Costing = $RootPath . '/ShipmentCosting.php?SelectedShipment=' . $MyRow['shiptref'];

			echo '<tr>
					<td style="font-weight: 800; color: var(--primary); font-size: 1.05rem;">' . $MyRow['shiptref'] . '</td>
					<td><span style="font-weight: 600;">' . $MyRow['suppname'] . '</span></td>
					<td>
						<div style="display: flex; flex-direction: column;">
							<span style="font-weight: 600;">' . htmlspecialchars($MyRow['vessel']) . '</span>
							<span style="font-size: 0.75rem; color: var(--text-muted);">' . __('Voyage') . ': ' . htmlspecialchars($MyRow['voyageref']) . '</span>
						</div>
					</td>
					<td><span class="db-badge" style="background: var(--surface-alt);">' . ConvertSQLDate($MyRow['eta']) . '</span></td>
					<td class="text-center noPrint">
						<div style="display: flex; gap: 8px; justify-content: center;">
							<a href="' . $URL_Costing . '" class="db-btn db-btn-outline db-btn-sm" style="padding: 0 16px;">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><path d="M12 1v22M17 5H9.5a4.5 4.5 0 1 0 0 9h5a4.5 4.5 0 1 1 0 9H6"></path></svg>
								' . __('Costing') . '
							</a>
							<a href="' . $URL_Modify . '" class="db-btn db-btn-outline db-btn-sm" style="padding: 0 16px;">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
								' . __('Modify') . '
							</a>';
			if ($MyRow['closed'] == 0) {
				echo '<a href="' . $URL_Costing . '&amp;Close=Yes" class="db-btn db-btn-outline db-btn-sm" style="color: var(--danger); border-color: var(--danger-subtle); padding: 0 16px;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 6px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						' . __('Finalize') . '
					  </a>';
			}
			echo '		</div>
					</td>
				</tr>';
		}
		echo '					</tbody>
					</table>
				</div>
			</div>';
	} else {
		echo '<div class="card-v2" style="padding: var(--space-10); display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted);">
				<div style="width: 80px; height: 80px; border-radius: 50%; background: var(--surface-alt); display: flex; align-items: center; justify-content: center; margin-bottom: 24px; box-shadow: inset var(--shadow-sm);">
					<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="opacity: 0.3;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				</div>
				<h3 style="margin: 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);">' . __('No Results Found') . '</h3>
				<p style="text-align: center; max-width: 400px; line-height: 1.6; margin-top: 12px;">' . __('Refine your search parameters in the panels above to locate specific shipments or stock items in the registry.') . '</p>
			  </div>';
	}
}

echo '</div>'; // End Workspace
echo '</div>'; // End Page

include(__DIR__ . '/includes/footer.php');
