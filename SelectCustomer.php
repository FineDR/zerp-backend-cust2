<?php

/* Selection of customer - from where all customer related maintenance, transactions and inquiries start */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Customers');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'SelectCustomer';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div class="db-page-title">
				<div class="db-page-icon">
					<i class="fas fa-users-cog"></i>
				</div>
				<h1>' . $Title . '</h1>
			</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/Customers.php" class="db-btn db-btn-primary">
					<i class="fas fa-plus"></i> ' . __('Add New Customer') . '
				</a>
			</div>
		</header>';

if (isset($_GET['Select'])) {
	$_SESSION['CustomerID'] = $_GET['Select'];
} // isset($_GET['Select'])
if (!isset($_SESSION['CustomerID'])) { // initialise if not already done
	$_SESSION['CustomerID'] = '';
} // !isset($_SESSION['CustomerID'])
if (isset($_GET['Area'])) {
	$_POST['Area'] = $_GET['Area'];
	$_POST['Search'] = 'Search';
	$_POST['Keywords'] = '';
	$_POST['CustCode'] = '';
	$_POST['CustPhone'] = '';
	$_POST['CustAdd'] = '';
	$_POST['CustType'] = '';
} // isset($_GET['Area'])
if (!isset($_SESSION['CustomerType'])) { // initialise if not already done
	$_SESSION['CustomerType'] = '';
} // !isset($_SESSION['CustomerType'])
if (isset($_POST['JustSelectedACustomer'])) {
	if (isset($_POST['SubmitCustomerSelection'])) {
		foreach ($_POST['SubmitCustomerSelection'] as $CustomerID => $BranchCode) $_SESSION['CustomerID'] = $CustomerID;
		$_SESSION['BranchCode'] = $BranchCode;
	} elseif (!isset($_POST['Search'])){
		prnMsg(__('Unable to identify the selected customer'), 'error');
	}
}

$Msg = '';

if (isset($_POST['Go1']) or isset($_POST['Go2'])) {
	$_POST['PageOffset'] = (isset($_POST['Go1']) ? $_POST['PageOffset1'] : $_POST['PageOffset2']);
	$_POST['Go'] = '';
} // isset($_POST['Go1']) or isset($_POST['Go2'])
if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	} // $_POST['PageOffset'] == 0

}

if (isset($_POST['Search']) or isset($_POST['CSV']) or isset($_POST['Go']) or isset($_POST['Next']) or isset($_POST['Previous'])) {
	unset($_POST['JustSelectedACustomer']);
	if (isset($_POST['Search'])) {
		$_POST['PageOffset'] = 1;
	} // isset($_POST['Search'])
	$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				debtorsmaster.address1,
				debtorsmaster.address2,
				debtorsmaster.address3,
				debtorsmaster.address4,
				custbranch.branchcode,
				custbranch.brname,
				custbranch.contactname,
				debtortype.typename,
				custbranch.phoneno,
				custbranch.faxno,
				custbranch.email
			FROM debtorsmaster
			LEFT JOIN custbranch
				ON debtorsmaster.debtorno = custbranch.debtorno
			INNER JOIN debtortype
				ON debtorsmaster.typeid = debtortype.typeid";
	if (isset($_POST['SmartSearch']) && mb_strlen($_POST['SmartSearch']) > 0) {
		$SearchKeywords = mb_strtoupper(trim(str_replace(' ', '%', $_POST['SmartSearch'])));
		$SQL .= " WHERE (debtorsmaster.name " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.debtorno " . LIKE . " '%" . $SearchKeywords . "%'
						OR custbranch.phoneno " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address1 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address2 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address3 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address4 " . LIKE . " '%" . $SearchKeywords . "%')";
		
		if (isset($_POST['CustType']) && $_POST['CustType'] != 'ALL') {
			$SQL.= " AND debtortype.typename = '" . $_POST['CustType'] . "'";
		}
		if (isset($_POST['Area']) && $_POST['Area'] != 'ALL') {
			$SQL.= " AND custbranch.area = '" . $_POST['Area'] . "'";
		}
	}
	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL.= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	} // $_SESSION['SalesmanLogin'] != ''
	$SQL.= " ORDER BY debtorsmaster.name";
	$ErrMsg = __('The searched customer records requested cannot be retrieved because');

	$SearchResult = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($SearchResult) == 1) {
		$MyRow = DB_fetch_array($SearchResult);
		$_SESSION['CustomerID'] = $MyRow['debtorno'];
		$_SESSION['BranchCode'] = $MyRow['branchcode'];
		unset($SearchResult);
		unset($_POST['Search']);
	} elseif (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('No customer records contain the selected text') . ' - ' . __('please alter your search criteria and try again'), 'info');
	} // DB_num_rows($Result) == 0

} // end of if search
if ($_SESSION['CustomerID'] != '' and !isset($_POST['Search']) and !isset($_POST['CSV'])) {
	$SQL = "SELECT debtorsmaster.name,
				custbranch.phoneno,
				custbranch.brname
			FROM debtorsmaster
			INNER JOIN custbranch
			ON debtorsmaster.debtorno=custbranch.debtorno
			WHERE custbranch.debtorno='" . $_SESSION['CustomerID'] . "'";

	if (isset($_SESSION['BranchCode'])) {
		$SQL .= " AND custbranch.branchcode='" . $_SESSION['BranchCode'] . "'";
	} // isset($_SESSION['BranchCode'])

	$ErrMsg = __('The customer name requested cannot be retrieved because');
	$CustomerResult = DB_query($SQL, $ErrMsg);
	if ($MyRow = DB_fetch_array($CustomerResult)) {
		$CustomerName = htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $MyRow['phoneno'];
		$BranchName = $MyRow['brname'];
	} // $MyRow = DB_fetch_array($Result)
	unset($CustomerResult);

	echo '<div class="db-card db-card-full">
			<div class="db-card-header db-card-header-tabs">
				<div class="db-card-title">
					<i class="fas fa-user-check"></i> ' . $CustomerName . ' <small>(' . stripslashes($_SESSION['CustomerID']) . ')</small>
				</div>
				<nav class="db-card-nav">
					<button type="button" class="db-nav-link active" onclick="switchCustTab(event, \'tab-actions\')">' . __('Quick Actions') . '</button>
					<button type="button" class="db-nav-link" onclick="switchCustTab(event, \'tab-details\')">' . __('View Details') . '</button>
				</nav>
			</div>
			<div class="db-card-body">
				<div class="db-tab-content active" id="tab-actions">
					<div class="db-action-toolbar">
						<a href="' . $RootPath . '/SelectSalesOrder.php?SelectedCustomer=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline"><i class="fas fa-shopping-cart"></i> ' . __('New Order') . '</a>
						<a href="' . $RootPath . '/CustomerReceipt.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '&NewReceipt=Yes&Type=Customer" class="db-btn db-btn-outline"><i class="fas fa-money-bill-wave"></i> ' . __('Receipt') . '</a>
						<a href="' . $RootPath . '/CustomerInquiry.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline"><i class="fas fa-history"></i> ' . __('Inquiry') . '</a>
						<a href="' . $RootPath . '/Customers.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline"><i class="fas fa-edit"></i> ' . __('Edit Profile') . '</a>
						<a href="' . $RootPath . '/CustomerAccount.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline"><i class="fas fa-file-invoice"></i> ' . __('Statement') . '</a>
						<a href="' . $RootPath . '/CounterSales.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '&amp;BranchNo=' . $_SESSION['BranchCode'] . '" class="db-btn db-btn-outline"><i class="fas fa-cash-register"></i> ' . __('POS') . '</a>
					</div>
				</div>
				<div class="db-tab-content" id="tab-details" style="display:none">
					<div class="db-grid db-grid-2">
						<div class="db-info-list">
							<div class="db-info-item"><span>' . __('Phone') . ':</span> <b>' . $PhoneNo . '</b></div>
							<div class="db-info-item"><span>' . __('Branch') . ':</span> <b>' . $BranchName . '</b></div>
							<div class="db-info-item"><span>' . __('Terms') . ':</span> <a href="' . $RootPath . '/CustomerBranches.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-link">' . __('Manage Branches') . ' →</a></div>
						</div>
						<div class="db-info-list">
							<div class="db-info-item"><span>' . __('Related Links') . ':</span></div>
							<a href="' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-link"><i class="fas fa-address-book"></i> ' . __('Add Contact') . '</a>
							<a href="' . $RootPath . '/AddCustomerNotes.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-link"><i class="fas fa-sticky-note"></i> ' . __('Add Note') . '</a>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script>
		function switchCustTab(evt, tabName) {
			var i, tabcontent, tablinks;
			tabcontent = document.getElementsByClassName("db-tab-content");
			for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; }
			tablinks = document.getElementsByClassName("db-nav-link");
			for (i = 0; i < tablinks.length; i++) { tablinks[i].className = tablinks[i].className.replace(" active", ""); }
			document.getElementById(tabName).style.display = "block";
			evt.currentTarget.className += " active";
		}
		</script><br />';

}

// Search for customers:
	echo '<div class="db-search-hero">
			<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post" class="db-search-form">
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
				<div class="db-search-input-wrap">
					<i class="fas fa-search db-search-icon"></i>
					<input type="text" name="SmartSearch" class="db-search-input" placeholder="' . __('Search by Name, Code, Phone, or Address...') . '" ', (isset($_POST['SmartSearch']) ? 'value="' . $_POST['SmartSearch'] . '"' : ''), ' autofocus />
					<button type="submit" name="Search" class="db-search-btn">' . __('Search Now') . '</button>
				</div>
				<details class="db-advanced-filters">
					<summary class="db-filters-summary"><i class="fas fa-filter"></i> ' . __('Advanced Filters') . '</summary>
					<div class="db-filters-grid">
						<div class="db-field">
							<label class="db-label">' . __('Customer Type') . '</label>';
	$Result2 = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<select name="CustType" class="db-input">
			<option value="ALL">' . __('Any Type') . '</option>';
	while ($MyRow = DB_fetch_array($Result2)) {
		$selected = (isset($_POST['CustType']) AND $_POST['CustType'] == $MyRow['typename']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['typename'] . '">' . $MyRow['typename'] . '</option>';
	}
	echo '</select>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Sales Area') . '</label>';
	$Result2 = DB_query("SELECT areacode, areadescription FROM areas");
	echo '<select name="Area" class="db-input">
			<option value="ALL">' . __('Any Area') . '</option>';
	while ($MyRow = DB_fetch_array($Result2)) {
		$selected = (isset($_POST['Area']) AND $_POST['Area'] == $MyRow['areacode']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription'] . '</option>';
	}
	echo '</select>
						</div>
					</div>
				</details>
			</form>
		</div>';

// End search for customers.
if (isset($_SESSION['SalesmanLogin']) and $_SESSION['SalesmanLogin'] != '') {
	prnMsg(__('Your account enables you to see only customers allocated to you'), 'warn', __('Note: Sales-person Login'));
} // isset($_SESSION['SalesmanLogin']) and $_SESSION['SalesmanLogin'] != ''
if (isset($SearchResult)) {
	unset($_SESSION['CustomerID']);
	$ListCount = DB_num_rows($SearchResult);
	$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
	if (!isset($_POST['CSV'])) {
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) {
				$_POST['PageOffset'] = $_POST['PageOffset'] + 1;
			} // $_POST['PageOffset'] < $ListPageMax

		} // isset($_POST['Next'])
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) {
				$_POST['PageOffset'] = $_POST['PageOffset'] - 1;
			} // $_POST['PageOffset'] > 1

		} // isset($_POST['Previous'])
		echo '<input type="hidden" name="PageOffset" value="', $_POST['PageOffset'], '" />';
		if ($ListPageMax > 1) {
			echo '<div class="db-pagination">
					<span>' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . ' ' . __('pages') . '</span>
					<div class="db-pagination-controls">
						<select name="PageOffset1" class="db-input db-input-small">';
			$ListPage = 1;
			while ($ListPage <= $ListPageMax) {
				$selected = ($ListPage == $_POST['PageOffset']) ? 'selected="selected"' : '';
				echo '<option value="', $ListPage, '" ' . $selected . '>', $ListPage, '</option>';
				$ListPage++;
			}
			echo '</select>
						<button type="submit" name="Go1" class="db-btn db-btn-secondary db-btn-small">' . __('Go') . '</button>
						<button type="submit" name="Previous" class="db-btn db-btn-secondary db-btn-small">' . __('Previous') . '</button>
						<button type="submit" name="Next" class="db-btn db-btn-secondary db-btn-small">' . __('Next') . '</button>
					</div>
				</div>';
		}
		$RowIndex = 0;
	} // !isset($_POST['CSV'])
	if (DB_num_rows($SearchResult) <> 0) {
		echo '<div class="db-grid db-grid-3">';

		if (!isset($_POST['CSV'])) {
			DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}

		$RowIndex = 0;
		while (($MyRow = DB_fetch_array($SearchResult)) and ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			echo '<div class="db-card db-customer-card">
					<div class="db-card-body">
						<div class="db-cust-card-head">
							<div class="db-cust-initials">' . mb_substr($MyRow['name'], 0, 1) . '</div>
							<div class="db-cust-info">
								<div class="db-cust-name">' . htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8', false) . '</div>
								<div class="db-cust-code">#' . $MyRow['debtorno'] . '</div>
							</div>
						</div>
						<div class="db-cust-details">
							<div class="db-cust-detail"><i class="fas fa-phone-alt"></i> ' . $MyRow['phoneno'] . '</div>
							<div class="db-cust-detail"><i class="fas fa-map-marker-alt"></i> ' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8', false) . '</div>
							<div class="db-cust-detail"><span class="db-badge db-badge-info">' . $MyRow['typename'] . '</span></div>
						</div>
					</div>
					<div class="db-card-footer">
						<button type="submit" class="db-btn db-btn-primary db-btn-full" name="SubmitCustomerSelection[' . htmlspecialchars($MyRow['debtorno'], ENT_QUOTES, 'UTF-8', false) . ']" value="' . htmlspecialchars($MyRow['branchcode'], ENT_QUOTES, 'UTF-8', false) . '">
							<i class="fas fa-check"></i> ' . __('Select Customer') . '
						</button>
					</div>
				</div>';
			$RowIndex++;
		}
		echo '</div>';
		echo '<input type="hidden" name="JustSelectedACustomer" value="Yes" />';
	}

} // isset($Result)
// end if results to show
if (!isset($_POST['CSV'])) {
	if (isset($ListPageMax) and $ListPageMax > 1) {
		echo '<div class="db-pagination" style="margin-top: 1rem;">
				<span>' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . ' ' . __('pages') . '</span>
				<div class="db-pagination-controls">
					<select name="PageOffset2" class="db-input db-input-small">';
		$ListPage = 1;
		while ($ListPage <= $ListPageMax) {
			$selected = ($ListPage == $_POST['PageOffset']) ? 'selected="selected"' : '';
			echo '<option value="', $ListPage, '" ' . $selected . '>', $ListPage, '</option>';
			$ListPage++;
		}
		echo '</select>
					<button type="submit" name="Go2" class="db-btn db-btn-secondary db-btn-small">' . __('Go') . '</button>
					<button type="submit" name="Previous" class="db-btn db-btn-secondary db-btn-small">' . __('Previous') . '</button>
					<button type="submit" name="Next" class="db-btn db-btn-secondary db-btn-small">' . __('Next') . '</button>
				</div>
			</div>';
	}
	// end if results to show

} // !isset($_POST['CSV'])
echo '</form>';

// Only display the geocode map if the integration is turned on, and there is a latitude/longitude to display
if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '') {
	if ($_SESSION['geocode_integration'] == 1) {

		$SQL = "SELECT * FROM geocode_param";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			prnMsg(__('You must first setup the geocode parameters') . ' ' . '<a href="' . $RootPath . '/GeocodeSetup.php">' . __('here') . '</a>', 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		$MyRow = DB_fetch_array($Result);
		$map_height = $MyRow['map_height'];
		$map_width = $MyRow['map_width'];

		$SQL = "SELECT
					debtorsmaster.debtorno,
					debtorsmaster.name,
					custbranch.branchcode,
					custbranch.brname,
					custbranch.lat,
					custbranch.lng,
					custbranch.braddress1,
					custbranch.braddress2,
					custbranch.braddress3,
					custbranch.braddress4
				FROM debtorsmaster
				LEFT JOIN custbranch
					ON debtorsmaster.debtorno = custbranch.debtorno
				WHERE debtorsmaster.debtorno = '" . $_SESSION['CustomerID'] . "'
					AND custbranch.branchcode = '" . $_SESSION['BranchCode'] . "'
				ORDER BY debtorsmaster.debtorno";
		$Result2 = DB_query($SQL);
		$MyRow2 = DB_fetch_array($Result2);
		$Lat = $MyRow2['lat'];
		$Lng = $MyRow2['lng'];

		// Use OpenStreetMap Nominatim for geocoding if no coordinates exist
		if ($Lat == 0 and $MyRow2['braddress1'] != '' and $_SESSION['BranchCode'] != '') {
			$delay = 1000000; // 1 second delay for Nominatim usage policy
			$base_url = 'https://nominatim.openstreetmap.org/search?format=json&q=';

			$geocode_pending = true;
			while ($geocode_pending) {
				$address = urlencode($MyRow2['braddress1'] . ',' . $MyRow2['braddress2'] . ',' . $MyRow2['braddress3'] . ',' . $MyRow2['braddress4']);
				$id = $MyRow2['branchcode'];
				$debtorno = $MyRow2['debtorno'];
				$request_url = $base_url . $address . '&limit=1';

				$opts = array(
					'http'=>array(
						'method'=>"GET",
						'header'=>"User-Agent: webERP-geocoding\r\n"
					)
				);
				$context = stream_context_create($opts);
				$buffer = @file_get_contents($request_url, false, $context);

				if ($buffer !== false) {
					$json = json_decode($buffer, true);
					if (!empty($json) && isset($json[0]['lat']) && isset($json[0]['lon'])) {
						$geocode_pending = false;

						$Lat = $json[0]['lat'];
						$Lng = $json[0]['lon'];

						$query = sprintf("UPDATE custbranch " . " SET lat = '%s', lng = '%s' " . " WHERE branchcode = '%s' " . " AND debtorno = '%s' LIMIT 1;", ($Lat), ($Lng), ($id), ($debtorno));
						$update_result = DB_query($query);

						if ($update_result == 1) {
							prnMsg(__('GeoCode has been updated for CustomerID') . ': ' . $id . ' - ' . __('Latitude') . ': ' . $Lat . ' ' . __('Longitude') . ': ' . $Lng, 'info');
						}
					} else {
						$geocode_pending = false;
						prnMsg(__('Unable to update GeoCode for CustomerID') . ': ' . $id . ' - ' . __('No results found'), 'error');
					}
				} else {
					$geocode_pending = false;
					prnMsg(__('Unable to update GeoCode for CustomerID') . ': ' . $id . ' - ' . __('Connection failed'), 'error');
				}
				usleep($delay);
			}
		}

		if ($Lat == 0) {
			echo '<div class="centre">', __('Mapping is enabled, but no Mapping data to display for this Customer.'), '</div>';
		} // $Lattitude == 0
		else {
			echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>';
			echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>';

			echo '<details class="db-accordion">
					<summary><i class="fas fa-map-marked-alt"></i> ' . __('Customer Mapping (Visual Location)') . '</summary>
					<div class="db-card-body">
						<div class="center" id="map" style="height:', $map_height . 'px; margin: 0 auto; width:', $map_width, 'px;"></div>
					</div>
				</details>';

			// OpenStreetMap with Leaflet
			echo '<script>
			var map = L.map(\'map\').setView([' . $Lat . ', ' . $Lng . '], 14);

			L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\', {
				attribution: \'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors\',
				maxZoom: 19
			}).addTo(map);

			var marker = L.marker([' . $Lat . ', ' . $Lng . ']).addTo(map);
			marker.bindPopup(\'<div style="overflow: auto;"><div><b>' . htmlspecialchars($BranchName, ENT_QUOTES, 'UTF-8') . '</b></div><div>' .
				htmlspecialchars($MyRow2['braddress1'], ENT_QUOTES, 'UTF-8') . '</div><div>' .
				htmlspecialchars($MyRow2['braddress2'], ENT_QUOTES, 'UTF-8') . '</div><div>' .
				htmlspecialchars($MyRow2['braddress3'], ENT_QUOTES, 'UTF-8') . '</div><div>' .
				htmlspecialchars($MyRow2['braddress4'], ENT_QUOTES, 'UTF-8') . '</div></div>\').openPopup();
			</script>';
		}

	} // $_SESSION['geocode_integration'] == 1
	// Extended Customer Info only if selected in Configuration
	if ($_SESSION['Extended_CustomerInfo'] == 1) {
		if ($_SESSION['CustomerID'] != '') {
			$SQL = "SELECT debtortype.typeid,
							debtortype.typename
					FROM debtorsmaster
					INNER JOIN debtortype
						ON debtorsmaster.typeid = debtortype.typeid
					WHERE debtorsmaster.debtorno = '" . $_SESSION['CustomerID'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$CustomerType = $MyRow['typeid'];
			$CustomerTypeName = $MyRow['typename'];
			// Customer Data
			echo '<br />';
			// Select some basic data about the Customer
			$SQL = "SELECT debtorsmaster.clientsince,
						(TO_DAYS(date(now())) - TO_DAYS(date(debtorsmaster.clientsince))) as customersincedays,
						(TO_DAYS(date(now())) - TO_DAYS(date(debtorsmaster.lastpaiddate))) as lastpaiddays,
						debtorsmaster.paymentterms,
						debtorsmaster.lastpaid,
						debtorsmaster.lastpaiddate,
						currencies.decimalplaces AS currdecimalplaces
					FROM debtorsmaster
					INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
					WHERE debtorsmaster.debtorno ='" . $_SESSION['CustomerID'] . "'";
			$DataResult = DB_query($SQL);
			$MyRow = DB_fetch_array($DataResult);
			// Select some more data about the customer
			$SQL = "SELECT sum(ovamount+ovgst) as total
					FROM debtortrans
					WHERE debtorno = '" . $_SESSION['CustomerID'] . "'
						AND type !=12";
			$Total1Result = DB_query($SQL);
			$row = DB_fetch_array($Total1Result);
			echo '<details class="db-accordion">
					<summary><i class="fas fa-info-circle"></i> ' . __('Extended Customer Metrics') . '</summary>
					<div class="db-card-body">
						<div class="db-table-container">
							<table class="db-table db-table-vertical">';
			if ($MyRow['lastpaiddate'] == 0) {
				echo '<tr>
						<th>' . __('Status') . '</th>
						<td>' . __('No receipts from this customer.') . '</td>
					</tr>';
			} else {
				echo '<tr>
						<th>' . __('Last Paid Date') . '</th>
						<td><b>' . ConvertSQLDate($MyRow['lastpaiddate']) . '</b> (' . $MyRow['lastpaiddays'] . ' ' . __('days') . ')</td>
					</tr>';
			}
			echo '<tr>
					<th>' . __('Last Paid Amount (inc tax)') . '</th>
					<td><b>' . locale_number_format($MyRow['lastpaid'], $MyRow['currdecimalplaces']) . '</b></td>
				</tr>';
			echo '<tr>
					<th>' . __('Customer since') . '</th>
					<td><b>' . ConvertSQLDate($MyRow['clientsince']) . '</b> (' . $MyRow['customersincedays'] . ' ' . __('days') . ')</td>
				</tr>';
			if ($row['total'] != 0) {
				echo '<tr>
						<th>' . __('Total Spend (inc tax)') . '</th>
						<td><b>' . locale_number_format($row['total'], $MyRow['currdecimalplaces']) . '</b></td>
					</tr>';
			}
			echo '<tr>
					<th>' . __('Customer Type') . '</th>
					<td><b>' . $CustomerTypeName . '</b></td>
				</tr>';
			echo '</table>
						</div>
					</div>
				</details><br />';
		} // $_SESSION['CustomerID'] != ''
		// Customer Contacts
		$SQL = "SELECT * FROM custcontacts
				WHERE debtorno='" . $_SESSION['CustomerID'] . "'
				ORDER BY contid";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) <> 0) {
			echo '<details class="db-accordion">
					<summary><i class="fas fa-address-book"></i> ' . __('Customer Contacts') . '</summary>
					<div class="db-card-body">
						<div class="db-card-actions centre mb-20">
							<a href="' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-primary db-btn-small"><i class="fas fa-plus"></i> ' . __('Add New Contact') . '</a>
						</div>
						<div class="db-table-container">
							<table class="db-table">
								<thead>
									<tr>
										<th>', __('Name'), '</th>
										<th>', __('Role'), '</th>
										<th>', __('Phone'), '</th>
										<th>', __('Email'), '</th>
										<th>', __('Statement'), '</th>
										<th>', __('Actions'), '</th>
									</tr>
								</thead>
								<tbody>';
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<tr>
						<td>', $MyRow[2], '</td>
						<td>', $MyRow[3], '</td>
						<td>', $MyRow[4], '</td>
						<td><a href="mailto:', $MyRow[6], '" class="db-link">', $MyRow[6], '</a></td>
						<td>', ($MyRow[7] == 0) ? __('No') : __('Yes'), '</td>
						<td>
							<a href="' . $RootPath . '/AddCustomerContacts.php?Id=' . urlencode($MyRow[0]) . '&DebtorNo=' . urlencode($MyRow[1]) . '" class="db-btn db-btn-outline db-btn-small" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
							<a href="' . $RootPath . '/AddCustomerContacts.php?Id=' . urlencode($MyRow[0]) . '&DebtorNo=' . urlencode($MyRow[1]) . '&delete=1" class="db-btn db-btn-danger db-btn-small" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
						</td>
					</tr>';
			}
			// Branch Contacts
			if (isset($_SESSION['BranchCode']) and $_SESSION['BranchCode'] != '') {
				$SQL = "SELECT branchcode, brname, contactname, phoneno, email FROM custbranch WHERE debtorno='" . $_SESSION['CustomerID'] . "' AND branchcode='" . $_SESSION['BranchCode'] . "'";
				$Result2 = DB_query($SQL);
				if ($BranchContact = DB_fetch_row($Result2)) {
					echo '<tr class="db-table-highlight">
							<td>', $BranchContact[2], '</td>
							<td>', __('Branch Contact'), ' (', $BranchContact[0], ')</td>
							<td>', $BranchContact[3], '</td>
							<td><a href="mailto:', $BranchContact[4], '" class="db-link">', $BranchContact[4], '</a></td>
							<td colspan="2"></td>
						</tr>';
				}
			}
			echo '</tbody>
						</table>
					</div>
				</div>
			</details><br />';
		}
		else {
			if ($_SESSION['CustomerID'] != '') {
				echo '<p class="page_title_text">
						<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/group_add.png" title="', __('Customer Contacts'), '" alt="" />
						<a href="' . $RootPath . '/AddCustomerContacts.php?DebtorNo=', urlencode($_SESSION['CustomerID']), '">', ' ', __('Add New Contact'), '</a>
					</p>';
			} // $_SESSION['CustomerID'] != ''

		}
		// Customer Notes
		$SQL = "SELECT
					noteid,
					debtorno,
					href,
					note,
					date,
					priority
				FROM custnotes
				WHERE debtorno='" . $_SESSION['CustomerID'] . "'
				ORDER BY date DESC";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) <> 0) {
			echo '<details class="db-accordion">
					<summary><i class="fas fa-sticky-note"></i> ' . __('Specific Customer Notes') . '</summary>
					<div class="db-card-body">
						<div class="db-card-actions centre mb-20">
							<a href="' . $RootPath . '/AddCustomerNotes.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-primary db-btn-small"><i class="fas fa-plus"></i> ' . __('Add New Note') . '</a>
						</div>
						<div class="db-table-container">
							<table class="db-table">
								<thead>
									<tr>
										<th>', __('Date'), '</th>
										<th>', __('Note'), '</th>
										<th>', __('Link'), '</th>
										<th>', __('Priority'), '</th>
										<th>', __('Actions'), '</th>
									</tr>
								</thead>
								<tbody>';
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<tr>
						<td>', ConvertSQLDate($MyRow['date']), '</td>
						<td>', $MyRow['note'], '</td>
						<td><a href="', $MyRow['href'], '" class="db-link">', $MyRow['href'], '</a></td>
						<td>', $MyRow['priority'], '</td>
						<td>
							<a href="' . $RootPath . '/AddCustomerNotes.php?Id=' . urlencode($MyRow['noteid']) . '&amp;DebtorNo=' . urlencode($MyRow['debtorno']) . '" class="db-btn db-btn-outline db-btn-small" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
							<a href="' . $RootPath . '/AddCustomerNotes.php?Id=' . urlencode($MyRow['noteid']) . '&amp;DebtorNo=' . urlencode($MyRow['debtorno']) . '&amp;delete=1" class="db-btn db-btn-danger db-btn-small" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
						</td>
					</tr>';
			}
			echo '</tbody>
							</table>
						</div>
					</div>
				</details><br />';
		} else {
			if ($_SESSION['CustomerID'] != '') {
				echo '<div class="db-card">
						<div class="db-card-body centre">
							<a href="' . $RootPath . '/AddCustomerNotes.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> ' . __('Add New Note') . '</a>
						</div>
					</div><br />';
			}
		}
		// Custome Type Notes
		$SQL = "SELECT * FROM debtortypenotes
				WHERE typeid='" . $CustomerType . "'
				ORDER BY date DESC";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) <> 0) {
			echo '<div class="db-card">
					<div class="db-card-header">
						<div class="db-card-title"><i class="fas fa-folder-open"></i> ' . __('Customer Type Notes') . ' for: ' . $CustomerTypeName . '</div>
						<div class="db-card-actions">
							<a href="' . $RootPath . '/AddCustomerTypeNotes.php?DebtorType=' . $CustomerType . '" class="db-btn db-btn-primary db-btn-small"><i class="fas fa-plus"></i> ' . __('Add New') . '</a>
						</div>
					</div>
					<div class="db-card-body">
						<div class="db-table-container">
							<table class="db-table">
								<thead>
									<tr>
										<th>', __('Date'), '</th>
										<th>', __('Note'), '</th>
										<th>', __('Link'), '</th>
										<th>', __('Priority'), '</th>
										<th>', __('Actions'), '</th>
									</tr>
								</thead>
								<tbody>';
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<tr>
						<td>', $MyRow[4], '</td>
						<td>', $MyRow[3], '</td>
						<td><a href="', $MyRow[2], '" class="db-link">', $MyRow[2], '</a></td>
						<td>', $MyRow[5], '</td>
						<td>
							<a href="' . $RootPath . '/AddCustomerTypeNotes.php?Id=' . urlencode($MyRow[0]) . '&amp;DebtorType=' . urlencode($MyRow[1]) . '" class="db-btn db-btn-outline db-btn-small" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
							<a href="' . $RootPath . '/AddCustomerTypeNotes.php?Id=' . urlencode($MyRow[0]) . '&amp;DebtorType=' . urlencode($MyRow[1]) . '&amp;delete=1" class="db-btn db-btn-danger db-btn-small" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
						</td>
					</tr>';
			}
			echo '</tbody>
							</table>
						</div>
					</div>
				</div><br />';
		} else {
			if ($_SESSION['CustomerID'] != '') {
				echo '<div class="db-card">
						<div class="db-card-body centre">
							<a href="' . $RootPath . '/AddCustomerTypeNotes.php?DebtorType=' . urlencode($CustomerType) . '" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> ' . __('Add New Group Note') . '</a>
						</div>
					</div><br />';
			}
		}
	} // $_SESSION['Extended_CustomerInfo'] == 1

} // isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != ''

echo '</div>'; // Close .db-page
include(__DIR__ . '/includes/footer.php');
