<?php

/* Selects a supplier. A supplier is required to be selected before any AP transactions and before any maintenance or inquiry of the supplier */

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['SupplierID'])) {
	$_SESSION['SupplierID']=$_GET['SupplierID'];
}
if (isset($_POST['Select'])) { /*User has hit the button selecting a supplier */
	$_SESSION['SupplierID'] = $_POST['Select'];
	unset($_POST['Select']);
	unset($_POST['Keywords']);
	unset($_POST['SupplierCode']);
	unset($_POST['Search']);
	unset($_POST['Go']);
	unset($_POST['Next']);
	unset($_POST['Previous']);
}
// only get geocode information if integration is on, and supplier has been selected
if ($_SESSION['geocode_integration'] == 1 AND isset($_SESSION['SupplierID'])) {
	$SQL = "SELECT * FROM geocode_param";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$SQL = "SELECT suppliers.supplierid,
				suppliers.lat,
				suppliers.lng,
				suppliers.suppname,
				suppliers.address1,
				suppliers.address2,
				suppliers.address3,
				suppliers.address4
			FROM suppliers
			WHERE suppliers.supplierid = '" . $_SESSION['SupplierID'] . "'
			ORDER BY suppliers.supplierid";
	$Result2 = DB_query($SQL);
	$MyRow2 = DB_fetch_array($Result2);
	$lat = $MyRow2['lat'];
	$lng = $MyRow2['lng'];
	$suppname = $MyRow2['suppname'];
	$address1 = $MyRow2['address1'];
	$address2 = $MyRow2['address2'];
	$address3 = $MyRow2['address3'];
	$address4 = $MyRow2['address4'];
	$map_height = $MyRow['map_height'];
	$map_width = $MyRow['map_width'];
	$ExtraHeadContent = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>' . "\n";
	$ExtraHeadContent .= '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>' . "\n";
}

$Title = __('Search Suppliers');
$ViewTopic = 'AccountsPayable';
$BookMark = 'SelectSupplier';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page" style="max-width: 1400px; margin: 0 auto;">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Select a supplier to manage their account, orders, and transactions') . '</p>
			</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/Suppliers.php" class="db-btn db-btn-primary">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg>
					' . __('Add New Supplier') . '
				</a>
			</div>
		</div>';

if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}
}
if (isset($_POST['Search'])
	OR isset($_POST['Go'])
	OR isset($_POST['Next'])
	OR isset($_POST['Previous'])) {

	if (mb_strlen($_POST['Keywords']) > 0 AND mb_strlen($_POST['SupplierCode']) > 0) {
		prnMsg( __('Supplier name keywords have been used in preference to the Supplier code extract entered'), 'info' );
	}
	if ($_POST['Keywords'] == '' AND $_POST['SupplierCode'] == '') {
		$SQL = "SELECT supplierid,
					suppname,
					currcode,
					address1,
					address2,
					address3,
					address4,
					telephone,
					email,
					url
				FROM suppliers
				ORDER BY suppname";
	} else {
		if (mb_strlen($_POST['Keywords']) > 0) {
			$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
			//insert wildcard characters in spaces
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
			$SQL = "SELECT supplierid,
							suppname,
							currcode,
							address1,
							address2,
							address3,
							address4,
							telephone,
							email,
							url
						FROM suppliers
						WHERE suppname " . LIKE . " '" . $SearchString . "'
						ORDER BY suppname";
		} elseif (mb_strlen($_POST['SupplierCode']) > 0) {
			$_POST['SupplierCode'] = mb_strtoupper($_POST['SupplierCode']);
			$SQL = "SELECT supplierid,
							suppname,
							currcode,
							address1,
							address2,
							address3,
							address4,
							telephone,
							email,
							url
						FROM suppliers
						WHERE supplierid " . LIKE . " '%" . $_POST['SupplierCode'] . "%'
						ORDER BY supplierid";
		}
	} //one of keywords or SupplierCode was more than a zero length string
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 1) {
		$MyRow = DB_fetch_row($Result);
		$SingleSupplierReturned = $MyRow[0];
	}
	if (isset($SingleSupplierReturned)) { /*there was only one supplier returned */
		$_SESSION['SupplierID'] = $SingleSupplierReturned;
		unset($_POST['Keywords']);
		unset($_POST['SupplierCode']);
		unset($_POST['Search']);
	}
} //end of if search

if (isset($_SESSION['SupplierID'])) {
	// A supplier is selected
	$SupplierName = '';
	$SQL = "SELECT suppliers.suppname
			FROM suppliers
			WHERE suppliers.supplierid ='" . $_SESSION['SupplierID'] . "'";
	$SupplierNameResult = DB_query($SQL);
	if (DB_num_rows($SupplierNameResult) == 1) {
		$MyRow = DB_fetch_row($SupplierNameResult);
		$SupplierName = $MyRow[0];
	}
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="db-bottom-layout">';

// START SIDEBAR
echo '<aside class="db-col-aside">';

// CARD 1: ACTIVE SUPPLIER (If selected)
if (isset($_SESSION['SupplierID'])) {
	echo '<div class="db-card" style="margin-bottom: 20px; background: var(--primary-soft); border: 1px solid var(--primary-light);">
			<div class="db-card-body">
				<div style="font-size: 0.75rem; text-transform: uppercase; color: var(--db-primary); font-weight: 700; margin-bottom: 8px; opacity: 0.7;">' . __('Active Supplier') . '</div>
				<div class="db-font-bold text-primary" style="font-size: 1.1rem; line-height: 1.2;">' . $SupplierName . '</div>
				<div style="font-family: monospace; font-size: 0.85rem; margin-top: 5px; color: var(--text-muted);">[' . $_SESSION['SupplierID'] . ']</div>
			</div>
		  </div>';
}

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-search" style="margin-right: 8px;"></i> ' . __('Find Supplier') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-label">' . __('Supplier Name') . '</label>
					<input type="text" name="Keywords" class="db-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="' . __('Keywords...') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Supplier Code') . '</label>
					<input type="text" name="SupplierCode" class="db-input" value="' . (isset($_POST['SupplierCode']) ? $_POST['SupplierCode'] : '') . '" placeholder="' . __('Code...') . '" />
				</div>
				<button type="submit" name="Search" class="db-btn db-btn-primary" style="width: 100%; margin-top: 10px;">
					<i class="fas fa-search" style="margin-right: 8px;"></i> ' . __('Search Now') . '
				</button>
			</div>
		</div>';

echo '</aside>';
// END SIDEBAR

echo '<main class="db-col-main">';

if (isset($_SESSION['SupplierID'])) {
	echo '<div class="db-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-6); margin-bottom: var(--space-8);">
			
			<!-- Inquiries & Reports -->
			<div class="db-card">
				<div class="db-card-header" style="border-bottom: 1px solid var(--border-soft); padding: var(--space-4);">
					<h3 class="db-card-title"><i class="fas fa-file-alt" style="margin-right: 8px; color: var(--db-primary);"></i> ' . __('Inquiries & Reports') . '</h3>
				</div>
				<div class="db-card-body" style="padding: var(--space-3);">
					<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px;">
						<li><a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Account Inquiry') . '</a></li>
						<li><a href="' . $RootPath . '/SupplierGRNAndInvoiceInquiry.php?SelectedSupplier=' . $_SESSION['SupplierID'] . '&SupplierName='.urlencode($SupplierName).'" class="db-menu-link">' . __('Delivery & GRN Inquiry') . '</a></li>
						<li><a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?SelectedSupplier=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Outstanding Purchase Orders') . '</a></li>
						<li><a href="' . $RootPath . '/PO_SelectPurchOrder.php?SelectedSupplier=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('All Purchase Orders') . '</a></li>
						<li><a href="' . $RootPath . '/ShiptsList.php?SupplierID=' . $_SESSION['SupplierID'] . '&SupplierName=' . urlencode($SupplierName) . '" class="db-menu-link">' . __('Open Shipments') . '</a></li>
						<li><a href="' . $RootPath . '/Shipt_Select.php?SelectedSupplier=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Search/Manage Shipments') . '</a></li>
						<li><a href="' . $RootPath . '/SuppPriceList.php?SelectedSupplier=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Supplier Price List') . '</a></li>
					</ul>
				</div>
			</div>

			<!-- Transactions -->
			<div class="db-card">
				<div class="db-card-header" style="border-bottom: 1px solid var(--border-soft); padding: var(--space-4);">
					<h3 class="db-card-title"><i class="fas fa-exchange-alt" style="margin-right: 8px; color: var(--db-primary);"></i> ' . __('Transactions') . '</h3>
				</div>
				<div class="db-card-body" style="padding: var(--space-3);">
					<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px;">
						<li><a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes&SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('New Purchase Order') . '</a></li>
						<li><a href="' . $RootPath . '/SupplierInvoice.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Enter Supplier Invoice') . '</a></li>
						<li><a href="' . $RootPath . '/SupplierCredit.php?New=true&SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Enter Credit Note') . '</a></li>
						<li><a href="' . $RootPath . '/Payments.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Enter Payment / Receipt') . '</a></li>
						<li><a href="' . $RootPath . '/ReverseGRN.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Reverse GRN') . '</a></li>
					</ul>
				</div>
			</div>

			<!-- Maintenance -->
			<div class="db-card">
				<div class="db-card-header" style="border-bottom: 1px solid var(--border-soft); padding: var(--space-4);">
					<h3 class="db-card-title"><i class="fas fa-tools" style="margin-right: 8px; color: var(--db-primary);"></i> ' . __('Maintenance') . '</h3>
				</div>
				<div class="db-card-body" style="padding: var(--space-3);">
					<ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px;">
						<li><a href="' . $RootPath . '/Suppliers.php" class="db-menu-link">' . __('Add New Supplier') . '</a></li>
						<li><a href="' . $RootPath . '/Suppliers.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Modify/Delete Supplier') . '</a></li>
						<li><a href="' . $RootPath . '/SupplierContacts.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Manage Contacts') . '</a></li>
						<li><a href="' . $RootPath . '/SellThroughSupport.php?SupplierID=' . $_SESSION['SupplierID'] . '" class="db-menu-link">' . __('Sell Through Support') . '</a></li>
						<li><a href="' . $RootPath . '/Shipments.php?NewShipment=Yes" class="db-menu-link">' . __('New Shipment Setup') . '</a></li>
						<li><a href="' . $RootPath . '/SuppLoginSetup.php" class="db-menu-link">' . __('Login Configuration') . '</a></li>
					</ul>
				</div>
			</div>

		</div>';
}

if (!isset($_SESSION['SupplierID']) && !isset($_POST['Search'])) {
	// Empty State
	echo '<div class="db-card" style="height: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center;">
			<div class="db-card-body">
				<div style="width: 80px; height: 80px; background: var(--db-bg-alt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--db-text-muted);">
					<i class="fas fa-user-friends" style="font-size: 2.5rem; opacity: 0.3;"></i>
				</div>
				<h3 class="db-font-bold" style="color: var(--text-main); margin-bottom: 8px;">' . __('Find a Supplier') . '</h3>
				<p style="max-width: 300px; margin: 0 auto; color: var(--text-muted);">' . __('Use the search form in the sidebar to find and select a supplier to manage.') . '</p>
			</div>
		</div>';
}




//if (isset($Result) AND !isset($SingleSupplierReturned)) {
if (isset($_POST['Search'])) {
	$ListCount = DB_num_rows($Result);
	$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
	if (isset($_POST['Next'])) {
		if ($_POST['PageOffset'] < $ListPageMax) {
			$_POST['PageOffset'] = $_POST['PageOffset'] + 1;
		}
	}
	if (isset($_POST['Previous'])) {
		if ($_POST['PageOffset'] > 1) {
			$_POST['PageOffset'] = $_POST['PageOffset'] - 1;
		}
	}
	if ($ListPageMax > 1) {
		echo '<div class="db-pagination" style="display: flex; align-items: center; gap: var(--space-4); margin-bottom: var(--space-4); background: var(--surface-alt); padding: var(--space-3) var(--space-4); border-radius: var(--radius-md); border: 1px solid var(--border-soft);">
				<span style="font-size: 0.85rem; font-weight: 600;">' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . ' ' . __('pages') . '</span>
				<div style="flex: 1;"></div>
				<label style="display: inline; margin-right: 8px;">' . __('Go to Page') . ':</label>
				<select name="PageOffset" style="width: auto; padding: 4px 8px;">';
		$ListPage = 1;
		while ($ListPage <= $ListPageMax) {
			$selected = ($ListPage == $_POST['PageOffset']) ? 'selected="selected"' : '';
			echo '<option value="' . $ListPage . '" ' . $selected . '>' . $ListPage . '</option>';
			$ListPage++;
		}
		echo '</select>
				<button type="submit" name="Go" class="db-btn db-btn-secondary" style="padding: 4px 12px;">' . __('Go') . '</button>
				<button type="submit" name="Previous" class="db-btn db-btn-secondary" style="padding: 4px 12px;">' . __('Previous') . '</button>
				<button type="submit" name="Next" class="db-btn db-btn-secondary" style="padding: 4px 12px;">' . __('Next') . '</button>
			</div>';
	}
	echo '<input type="hidden" name="Search" value="' . __('Search Now') . '" />';

	echo '<div class="db-card">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Select') . '</th>
							<th>' . __('Code') . '</th>
							<th>' . __('Supplier Name') . '</th>
							<th>' . __('Currency') . '</th>
							<th>' . __('Address') . '</th>
							<th>' . __('Contact Info') . '</th>
						</tr>
					</thead>
					<tbody>';

	$RowIndex = 0;
	if (DB_num_rows($Result) <> 0) {
		DB_data_seek($Result, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
	}
	while (($MyRow = DB_fetch_array($Result)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
		echo '<tr class="striped_row">
				<td style="width: 80px;">
					<button type="submit" name="Select" value="'.$MyRow['supplierid'].'" class="db-btn db-btn-primary" style="padding: 4px 12px; font-size: 0.75rem;">' . __('Select') . '</button>
				</td>
				<td><span class="ref-badge">' . $MyRow['supplierid'] . '</span></td>
				<td><div class="cust-name">' . $MyRow['suppname'] . '</div></td>
				<td><span class="tag">' . $MyRow['currcode'] . '</span></td>
				<td>
					<div style="font-size: 0.8rem; line-height: 1.4;">
						' . $MyRow['address1'] . (empty($MyRow['address2']) ? '' : ', ' . $MyRow['address2']) . '<br>
						<span style="color: var(--text-muted);">' . $MyRow['address3'] . (empty($MyRow['address4']) ? '' : ' ' . $MyRow['address4']) . '</span>
					</div>
				</td>
				<td>
					<div style="display: flex; flex-direction: column; gap: 2px; font-size: 0.8rem;">
						' . (empty($MyRow['telephone']) ? '' : '<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>' . $MyRow['telephone'] . '</span>') . '
						' . (empty($MyRow['email']) ? '' : '<a href="mailto:'.$MyRow['email'].'" style="color: var(--primary);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>' . $MyRow['email'] . '</a>') . '
						' . (empty($MyRow['url']) ? '' : '<a href="'.$MyRow['url'].'" target="_blank" style="color: var(--primary);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px; vertical-align: middle;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>' . __('Website') . '</a>') . '
					</div>
				</td>
			</tr>';
		$RowIndex = $RowIndex + 1;
	}
	echo '</tbody></table></div></div>';
	}
// Only display the geocode map if the integration is turned on, and there is a latitude/longitude to display
if (isset($_SESSION['SupplierID']) and $_SESSION['SupplierID'] != '') {
	if ($_SESSION['geocode_integration'] == 1) {
		if ($lat == 0) {
			echo '<div class="db-alert db-alert-info" style="margin-top: var(--space-6);">' . __('Mapping is enabled, but no Mapping data to display for this Supplier.') . '</div>';
		} else {

			echo '<div class="db-card" style="margin-top: var(--space-6);">
					<div class="db-card-header">
						<h3 class="db-card-title"><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: var(--db-primary);"></i> ' . __('Supplier Location Mapping') . '</h3>
					</div>

					<div class="db-card-body">
						<div class="centre" id="map" style="width: 100%; height: ' . $map_height . 'px; border-radius: 0 0 var(--radius-lg) var(--radius-lg);"></div>
					</div>
				</div>';
			
			// OpenStreetMap with Leaflet
			echo '<script>
			var map = L.map(\'map\').setView([' . $lat . ', ' . $lng . '], 13);
			
			L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\', {
				attribution: \'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\',
				maxZoom: 19
			}).addTo(map);
			
			var marker = L.marker([' . $lat . ', ' . $lng . ']).addTo(map);
			marker.bindPopup(\'<b>' . htmlspecialchars($suppname, ENT_QUOTES, 'UTF-8') . '</b><br>' . 
				htmlspecialchars($address1, ENT_QUOTES, 'UTF-8') . '<br>' . 
				htmlspecialchars($address2, ENT_QUOTES, 'UTF-8') . '<br>' . 
				htmlspecialchars($address3, ENT_QUOTES, 'UTF-8') . '<br>' . 
				htmlspecialchars($address4, ENT_QUOTES, 'UTF-8') . '\').openPopup();
			</script>';
		}
	}
	// Extended Info only if selected in Configuration
	if ($_SESSION['Extended_SupplierInfo'] == 1) {
		if ($_SESSION['SupplierID'] != '') {
			$SQL = "SELECT suppliers.suppname,
							suppliers.lastpaid,
							suppliers.lastpaiddate,
							suppliersince,
							currencies.decimalplaces AS currdecimalplaces
					FROM suppliers INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
					WHERE suppliers.supplierid ='" . $_SESSION['SupplierID'] . "'";
			$DataResult = DB_query($SQL);
			$MyRow = DB_fetch_array($DataResult);
			// Select some more data about the supplier
			$SQL = "SELECT SUM(ovamount) AS total FROM supptrans WHERE supplierno = '" . $_SESSION['SupplierID'] . "' AND (type = '20' OR type='21')";
			$Total1Result = DB_query($SQL);
			$Row = DB_fetch_array($Total1Result);

			echo '<div class="db-card" style="margin-top: var(--space-6);">
					<div class="db-card-header">
						<h3 class="db-card-title"><i class="fas fa-info-circle" style="margin-right: 8px; color: var(--db-primary);"></i> ' . __('Supplier Extended Insights') . '</h3>
					</div>

					<div class="db-table-wrapper">
						<table class="db-table">
							<tbody>
								<tr>
									<td class="val-bold" style="width: 250px;">' . __('Supplier Since') . '</td>
									<td>' . ConvertSQLDate($MyRow['suppliersince']) . '</td>
								</tr>
								<tr>
									<td class="val-bold">' . __('Last Payment Activity') . '</td>
									<td>' . ($MyRow['lastpaiddate'] == 0 ? __('No payments recorded') : '<strong>' . locale_number_format($MyRow['lastpaid'], $MyRow['currdecimalplaces']) . '</strong> ' . __('on') . ' ' . ConvertSQLDate($MyRow['lastpaiddate'])) . '</td>
								</tr>
								<tr>
									<td class="val-bold">' . __('Total Cumulative Spend') . '</td>
									<td style="color: var(--primary); font-weight: 800; font-size: 1.1rem;">' . locale_number_format($Row['total'], $MyRow['currdecimalplaces']) . '</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>';
		}
	}
}
echo '	</main>
	</div>
</form>'; // End db-bottom-layout
echo '</div>'; // End db-page


include(__DIR__ . '/includes/footer.php');
