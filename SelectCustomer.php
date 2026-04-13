<?php

/* Selection of customer - from where all customer related maintenance, transactions and inquiries start */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Customers');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'SelectCustomer';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Identify and manage customer accounts') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Customers.php" class="db-btn db-btn-primary">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
					' . __('Add New Customer') . '
				</a>
			</div>
		</div>';

if (isset($_GET['Select'])) {
	$_SESSION['CustomerID'] = $_GET['Select'];
}
if (!isset($_SESSION['CustomerID'])) {
	$_SESSION['CustomerID'] = '';
}
if (isset($_GET['Area'])) {
	$_POST['Area'] = $_GET['Area'];
	$_POST['Search'] = 'Search';
	$_POST['Keywords'] = '';
	$_POST['CustCode'] = '';
	$_POST['CustPhone'] = '';
	$_POST['CustAdd'] = '';
	$_POST['CustType'] = '';
}
if (!isset($_SESSION['CustomerType'])) {
	$_SESSION['CustomerType'] = '';
}
if (isset($_POST['JustSelectedACustomer'])) {
	if (isset($_POST['SubmitCustomerSelection'])) {
		foreach ($_POST['SubmitCustomerSelection'] as $CustomerID => $BranchCode)
			$_SESSION['CustomerID'] = $CustomerID;
		$_SESSION['BranchCode'] = $BranchCode;
	} elseif (!isset($_POST['Search'])) {
		prnMsg(__('Unable to identify the selected customer'), 'error');
	}
}

$Msg = '';

if (isset($_POST['Go1']) or isset($_POST['Go2'])) {
	$_POST['PageOffset'] = (isset($_POST['Go1']) ? $_POST['PageOffset1'] : $_POST['PageOffset2']);
	$_POST['Go'] = '';
}
if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}

}

if (isset($_POST['Search']) or isset($_POST['CSV']) or isset($_POST['Go']) or isset($_POST['Next']) or isset($_POST['Previous'])) {
	unset($_POST['JustSelectedACustomer']);
	if (isset($_POST['Search'])) {
		$_POST['PageOffset'] = 1;
	}
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
			$SQL .= " AND debtortype.typename = '" . $_POST['CustType'] . "'";
		}
		if (isset($_POST['Area']) && $_POST['Area'] != 'ALL') {
			$SQL .= " AND custbranch.area = '" . $_POST['Area'] . "'";
		}
	} else {
		$SQL .= " WHERE debtorsmaster.name " . LIKE . " '%" . mb_strtoupper($_POST['Keywords']) . "%'
				AND debtorsmaster.debtorno " . LIKE . " '%" . mb_strtoupper($_POST['CustCode']) . "%'
				AND (custbranch.phoneno " . LIKE . " '%" . $_POST['CustPhone'] . "%' OR custbranch.phoneno IS NULL)
				AND (debtorsmaster.address1 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address2 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address3 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address4 " . LIKE . " '%" . $_POST['CustAdd'] . "%')";

		if ($_POST['CustType'] != 'ALL') {
			$SQL .= " AND debtortype.typename = '" . $_POST['CustType'] . "'";
		}
		if ($_POST['Area'] != 'ALL') {
			$SQL .= " AND custbranch.area = '" . $_POST['Area'] . "'";
		}
	}

	if (isset($_SESSION['SalesmanLogin']) and $_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtorsmaster.name";

	$SearchResult = DB_query($SQL);
	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('No customers were identified matching the search criteria'), 'warn');
	}
}

if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '' and !isset($_POST['Search']) and !isset($_POST['CSV'])) {
	$SQL = "SELECT debtorsmaster.name,
					custbranch.brname,
					custbranch.phoneno
			FROM debtorsmaster
			INNER JOIN custbranch
				ON debtorsmaster.debtorno = custbranch.debtorno
			WHERE debtorsmaster.debtorno = '" . $_SESSION['CustomerID'] . "'
				AND custbranch.branchcode = '" . $_SESSION['BranchCode'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$CustomerName = $MyRow['name'];
	$BranchName = $MyRow['brname'];
	$PhoneNo = $MyRow['phoneno'];

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<div style="display: flex; align-items: center; gap: var(--space-4);">
					<div style="width: 48px; height: 48px; border-radius: 12px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					</div>
					<div>
						<h3 style="margin: 0; font-size: 1.1rem;">' . $CustomerName . '</h3>
						<span style="font-size: 0.85rem; color: var(--text-muted);">' . __('Account') . ': <span class="db-ref">#' . stripslashes($_SESSION['CustomerID']) . '</span> &bull; ' . $BranchName . '</span>
					</div>
				</div>
				<div class="db-header-actions">
					<span class="db-badge db-badge-success">' . __('Active Selection') . '</span>
				</div>
			</div>
			
			<div class="db-card-body">
				<div class="db-grid db-grid-6">
					<a href="' . $RootPath . '/SelectOrderItems.php?NewOrder=Yes&SelectedCustomer=' . urlencode($_SESSION['CustomerID']) . '" class="db-action-tile">
						<div class="db-action-icon db-icon-blue">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
						</div>
						<span class="db-action-text">' . __('New Order') . '</span>
					</a>

					<a href="' . $RootPath . '/CustomerReceipt.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '&NewReceipt=Yes&Type=Customer" class="db-action-tile">
						<div class="db-action-icon db-icon-green">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
						</div>
						<span class="db-action-text">' . __('Receipt') . '</span>
					</a>
					<a href="' . $RootPath . '/CustomerInquiry.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="db-action-tile">
						<div class="db-action-icon db-icon-neutral">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						</div>
						<span class="db-action-text">' . __('Inquiry') . '</span>
					</a>
					<a href="' . $RootPath . '/Customers.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-action-tile">
						<div class="db-action-icon db-icon-neutral">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</div>
						<span class="db-action-text">' . __('Edit Profile') . '</span>
					</a>
					<a href="' . $RootPath . '/CustomerAccount.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="db-action-tile">
						<div class="db-action-icon db-icon-neutral">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
						</div>
						<span class="db-action-text">' . __('Statement') . '</span>
					</a>
					<a href="' . $RootPath . '/CounterSales.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '&amp;BranchNo=' . $_SESSION['BranchCode'] . '" class="db-action-tile">
						<div class="db-action-icon db-icon-red">
							<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect></svg>
						</div>
						<span class="db-action-text">' . __('POS') . '</span>
					</a>
				</div>
			</div>
		</div>';
}

// Search Card
echo '<div class="card-v2" style="background: var(--surface-alt); border: 1px solid var(--border-soft);">
		<div class="card-header-v2">
			<h3>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Find Customer') . '
			</h3>
		</div>
		<div class="db-card-body">
			<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
				
				<div style="display: flex; gap: var(--space-3); margin-bottom: var(--space-4);">
					<div style="flex: 1; position: relative;">
						<input type="text" name="SmartSearch" style="height: 44px; padding-left: var(--space-3); font-size: 1rem;" placeholder="' . __('Search by Name, Code, Phone, or Address...') . '" ', (isset($_POST['SmartSearch']) ? 'value="' . $_POST['SmartSearch'] . '"' : ''), ' autofocus />
					</div>
					<button type="submit" name="Search" class="db-btn db-btn-primary" style="height: 44px; min-width: 140px;">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						' . __('Search Now') . '
					</button>
				</div>

				<details class="db-accordion" style="margin-top: var(--space-4);">
					<summary style="font-size: 0.85rem; color: var(--text-muted);">' . __('Advanced Filtering Options') . '</summary>
					<div class="db-grid db-grid-4" style="margin-top: var(--space-4);">
						<div class="db-field">
							<label class="db-label">' . __('Description Keywords') . '</label>
							<input type="text" name="Keywords" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Customer Code') . '</label>
							<input type="text" name="CustCode" value="' . (isset($_POST['CustCode']) ? $_POST['CustCode'] : '') . '" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Phone Number') . '</label>
							<input type="text" name="CustPhone" value="' . (isset($_POST['CustPhone']) ? $_POST['CustPhone'] : '') . '" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Address Extract') . '</label>
							<input type="text" name="CustAdd" value="' . (isset($_POST['CustAdd']) ? $_POST['CustAdd'] : '') . '" />
						</div>
					</div>
					<div class="db-grid db-grid-2" style="margin-top: var(--space-3);">
						<div class="db-field">
							<label class="db-label">' . __('Customer Type') . '</label>';
$Result2 = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
echo '<select name="CustType">
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
echo '<select name="Area">
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
		</div>
	</div>';

if (isset($SearchResult)) {
	$ListCount = DB_num_rows($SearchResult);
	$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);

	if (!isset($_POST['CSV']) && $ListCount > 0) {
		if (isset($_POST['Next']) && $_POST['PageOffset'] < $ListPageMax)
			$_POST['PageOffset']++;
		if (isset($_POST['Previous']) && $_POST['PageOffset'] > 1)
			$_POST['PageOffset']--;

		echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" name="PageOffset" value="' . $_POST['PageOffset'] . '" />';

		if ($ListPageMax > 1) {
			echo '<div class="db-pagination" style="margin-top: var(--space-6); margin-bottom: var(--space-4);">
					<span class="db-pagination-info">' . __('Page') . ' ' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . '</span>
					<div class="db-pagination-controls">
						<select name="PageOffset1" style="width: auto;">';
			for ($i = 1; $i <= $ListPageMax; $i++) {
				echo '<option value="' . $i . '" ' . ($i == $_POST['PageOffset'] ? 'selected' : '') . '>' . $i . '</option>';
			}
			echo '</select>
						<button type="submit" name="Go1" class="db-btn db-btn-secondary">' . __('Go') . '</button>
						<button type="submit" name="Previous" class="db-btn db-btn-secondary" ' . ($_POST['PageOffset'] == 1 ? 'disabled' : '') . '>' . __('Previous') . '</button>
						<button type="submit" name="Next" class="db-btn db-btn-secondary" ' . ($_POST['PageOffset'] == $ListPageMax ? 'disabled' : '') . '>' . __('Next') . '</button>
					</div>
				</div>';
		}

		echo '<div class="db-grid db-grid-3" style="margin-top: var(--space-4);">';
		DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		$RowIndex = 0;
		while (($MyRow = DB_fetch_array($SearchResult)) and ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			echo '<div class="card-v2" style="display: flex; flex-direction: column;">
					<div style="flex: 1; padding: var(--space-4); border-bottom: 1px solid var(--border-soft);">
						<div style="display: flex; align-items: flex-start; gap: var(--space-3); margin-bottom: var(--space-4);">
							<div style="width: 40px; height: 40px; border-radius: 8px; background: var(--surface-alt); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; flex-shrink: 0;">
								' . mb_substr($MyRow['name'] ?? 'C', 0, 1) . '
							</div>
							<div style="overflow: hidden;">
								<div style="font-weight: 700; color: var(--text-main); line-height: 1.2; margin-bottom: 2px;">' . htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8') . '</div>
								<div class="db-ref" style="font-size: 0.75rem;">#' . $MyRow['debtorno'] . '</div>
							</div>
						</div>
						<div class="db-info-list" style="font-size: 0.85rem;">
							<div class="db-info-item"><span class="db-muted" style="width: 20px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg></span> ' . $MyRow['phoneno'] . '</div>
							<div class="db-info-item"><span class="db-muted" style="width: 20px;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg></span> ' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8') . '</div>
							<div class="db-info-item" style="margin-top: var(--space-2);"><span class="db-badge db-badge-info">' . $MyRow['typename'] . '</span></div>
						</div>
					</div>
					<div style="padding: var(--space-4); background: var(--surface-alt); border-bottom-left-radius: var(--radius-lg); border-bottom-right-radius: var(--radius-lg);">
						<button type="submit" name="SubmitCustomerSelection[' . htmlspecialchars($MyRow['debtorno'], ENT_QUOTES, 'UTF-8') . ']" value="' . htmlspecialchars($MyRow['branchcode'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-primary" style="width: 100%;">
							' . __('Select Account') . '
						</button>
					</div>
				</div>';
			$RowIndex++;
		}
		echo '</div>
				<input type="hidden" name="JustSelectedACustomer" value="Yes" />
			  </form>';
	}
}

// Geocode Mapping
if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '' && $_SESSION['geocode_integration'] == 1) {
	$SQL = "SELECT * FROM geocode_param";
	$ResMap = DB_query($SQL);
	if (DB_num_rows($ResMap) > 0) {
		$MapRow = DB_fetch_array($ResMap);
		$SQL = "SELECT lat, lng, brname, braddress1, braddress2, braddress3, braddress4 
				FROM custbranch 
				WHERE debtorno = '" . $_SESSION['CustomerID'] . "' AND branchcode = '" . $_SESSION['BranchCode'] . "'";
		$ResBranch = DB_query($SQL);
		$BRow = DB_fetch_array($ResBranch);

		if ($BRow && $BRow['lat'] != 0) {
			echo '<div class="card-v2" style="margin-top: var(--space-6);">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
							' . __('Visual Location') . '
						</h3>
					</div>
					<div class="db-card-body">
						<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
						<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
						<div id="map" style="height:' . $MapRow['map_height'] . 'px; width: 100%; border-radius: var(--radius-md);"></div>
						<script>
							var map = L.map(\'map\').setView([' . $BRow['lat'] . ', ' . $BRow['lng'] . '], 14);
							L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\').addTo(map);
							L.marker([' . $BRow['lat'] . ', ' . $BRow['lng'] . ']).addTo(map).bindPopup(\'<b>' . htmlspecialchars($BRow['brname'], ENT_QUOTES, 'UTF-8') . '</b>\').openPopup();
						</script>
					</div>
				</div>';
		}
	}
}

// Extended Customer Info
if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '' && $_SESSION['Extended_CustomerInfo'] == 1) {
	$SQL = "SELECT clientsince, lastpaid, lastpaiddate, currencies.decimalplaces, currencies.currency
			FROM debtorsmaster INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtorsmaster.debtorno ='" . $_SESSION['CustomerID'] . "'";
	$DataRes = DB_query($SQL);
	$MyRow = DB_fetch_array($DataRes);

	$SQL = "SELECT SUM(ovamount+ovgst) as total FROM debtortrans WHERE debtorno = '" . $_SESSION['CustomerID'] . "' AND type != 12";
	$TotalRes = DB_query($SQL);
	$TRow = DB_fetch_array($TotalRes);

	echo '<div class="db-grid db-grid-2" style="margin-top: var(--space-6);">
			<div class="card-v2">
				<div class="card-header-v2">
					<h3>' . __('Account Insights') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-info-list">';
	if ($MyRow['lastpaiddate'] != 0) {
		echo '<div class="db-info-item"><span>' . __('Last Payment') . ':</span> <b>' . ConvertSQLDate($MyRow['lastpaiddate']) . '</b></div>';
		echo '<div class="db-info-item"><span>' . __('Amount') . ':</span> <b>' . $MyRow['currency'] . ' ' . locale_number_format($MyRow['lastpaid'], $MyRow['decimalplaces']) . '</b></div>';
	}
	echo '<div class="db-info-item"><span>' . __('Member Since') . ':</span> <b>' . ConvertSQLDate($MyRow['clientsince']) . '</b></div>';
	echo '<div class="db-info-item"><span>' . __('Lifetime Value') . ':</span> <b style="color: var(--success);">' . $MyRow['currency'] . ' ' . locale_number_format($TRow['total'], $MyRow['decimalplaces']) . '</b></div>';
	echo '</div></div></div>';

	// Customer Contacts Card
	$SQL = "SELECT * FROM custcontacts WHERE debtorno='" . $_SESSION['CustomerID'] . "' ORDER BY contid";
	$ConRes = DB_query($SQL);
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>' . __('Key Contacts') . '</h3>
				<a href="' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline db-btn-small">' . __('Manage') . '</a>
			</div>
			<div class="db-card-body">';
	if (DB_num_rows($ConRes) > 0) {
		echo '<div class="db-table-wrapper"><table class="db-table"><thead><tr><th>' . __('Name') . '</th><th>' . __('Role') . '</th><th>' . __('Email') . '</th></tr></thead><tbody>';
		while ($CR = DB_fetch_array($ConRes)) {
			echo '<tr><td>' . $CR[2] . '</td><td>' . $CR[3] . '</td><td><a href="mailto:' . $CR[6] . '" class="db-link">' . $CR[6] . '</a></td></tr>';
		}
		echo '</tbody></table></div>';
	} else {
		echo '<p class="db-muted" style="text-align:center;">' . __('No contacts listed') . '</p>';
	}
	echo '</div></div></div>';

	// Notes Section
	$SQL = "SELECT * FROM custnotes WHERE debtorno='" . $_SESSION['CustomerID'] . "' ORDER BY date DESC LIMIT 5";
	$NoteRes = DB_query($SQL);
	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>' . __('Customer Notes') . '</h3>
				<a href="' . $RootPath . '/AddCustomerNotes.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="db-btn db-btn-outline db-btn-small">' . __('Add Note') . '</a>
			</div>
			<div class="db-card-body">';
	if (DB_num_rows($NoteRes) > 0) {
		echo '<div class="db-table-wrapper"><table class="db-table"><thead><tr><th>' . __('Date') . '</th><th>' . __('Priority') . '</th><th>' . __('Note') . '</th></tr></thead><tbody>';
		while ($NR = DB_fetch_array($NoteRes)) {
			echo '<tr><td>' . ConvertSQLDate($NR['date']) . '</td><td>' . $NR['priority'] . '</td><td>' . $NR['note'] . '</td></tr>';
		}
		echo '</tbody></table></div>';
	} else {
		echo '<p class="db-muted" style="text-align:center;">' . __('No recent notes') . '</p>';
	}
	echo '</div></div>';
}

echo '</div>'; // Close .db-page
include(__DIR__ . '/includes/footer.php');
?>