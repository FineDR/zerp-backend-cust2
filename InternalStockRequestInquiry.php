<?php

// Token 19 is used as the authority overwritten token to ensure that all internal request can be viewed.

require(__DIR__ . '/includes/session.php');

$Title = __('Internal Stock Request Inquiry');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryRequests';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
	unset($StockID);
}
$RequestNo = (isset($_POST['RequestNo']) ? $_POST['RequestNo'] : '');
if (isset($_POST['StockID'])) $StockID = trim(mb_strtoupper($_POST['StockID']));
if (isset($_POST['SelectedStockItem'])) $StockID = $_POST['SelectedStockItem'];

// --- AUTHORITY & DATA PREP ---
// 1. Locations
$SQL = "SELECT locations.loccode, locationname FROM locations
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode
		AND locationusers.userid='" . $_SESSION['UserID'] . "'
		AND locationusers.canview=1 AND locations.internalrequest=1";
$LocResult = DB_query($SQL);
$Locations = array();
$LocOptions = '';
while ($LocRow = DB_fetch_array($LocResult)) {
	$Locations[] = $LocRow['loccode'];
	$Selected = (isset($_POST['StockLocation']) && $_POST['StockLocation'] == $LocRow['loccode'] ? 'selected' : '');
	$LocOptions .= '<option ' . $Selected . ' value="' . $LocRow['loccode'] . '">' . $LocRow['locationname'] . '</option>';
}

// 2. Departments
$SQL = "SELECT departments.departmentid, departments.description
		FROM departments LEFT JOIN stockrequest ON departments.departmentid = stockrequest.departmentid
		AND (departments.authoriser = '" . $_SESSION['UserID'] . "' OR stockrequest.initiator = '" . $_SESSION['UserID'] . "')
		WHERE stockrequest.dispatchid IS NOT NULL GROUP BY stockrequest.departmentid";
$DepResult = DB_query($SQL);
$Departments = array();
$DepOptions = '';
while ($DepRow = DB_fetch_array($DepResult)) {
	$Departments[] = $DepRow['departmentid'];
	$Selected = (isset($_POST['Department']) && $_POST['Department'] == $DepRow['departmentid'] ? 'selected' : '');
	$DepOptions .= '<option ' . $Selected . ' value="' . $DepRow['departmentid'] . '">' . $DepRow['description'] . '</option>';
}

// 3. Search Result Prep (If requested)
if (isset($_POST['SearchPart'])) {
	$StockItemsResult = GetSearchItems();
}

echo '<div class="db-bottom-layout">';

// --- SIDEBAR SEARCH ---
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="Locations" value="' . serialize($Locations) . '" />
			<input type="hidden" name="Departments" value="' . base64_encode(serialize($Departments)) . '" />
			
			<div class="db-card mb-4">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Inquiry Filters') . '</h3></div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Request No.') . '</label>
						<input type="text" name="RequestNo" class="db-input" value="' . $RequestNo . '" placeholder="' . __('All') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Location') . '</label>
						<select name="StockLocation" class="db-select">
							<option value="All" ' . (isset($_POST['StockLocation']) && $_POST['StockLocation'] == 'All' ? 'selected' : '') . '>' . __('All Authorized') . '</option>
							' . $LocOptions . '
						</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Authorization') . '</label>
						<select name="Authorized" class="db-select">
							<option value="All" ' . (!isset($_POST['Authorized']) || $_POST['Authorized'] == 'All' ? 'selected' : '') . '>' . __('All') . '</option>
							<option value="0" ' . (isset($_POST['Authorized']) && $_POST['Authorized'] === '0' ? 'selected' : '') . '>' . __('Unauthorized') . '</option>
							<option value="1" ' . (isset($_POST['Authorized']) && $_POST['Authorized'] === '1' ? 'selected' : '') . '>' . __('Authorized') . '</option>
						</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Department') . '</label>
						<select name="Department" class="db-select">
							<option value="All" ' . (isset($_POST['Department']) && $_POST['Department'] == 'All' ? 'selected' : '') . '>' . __('All Authorized') . '</option>
							' . $DepOptions . '
						</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('From Date') . '</label>
						<input type="date" name="FromDate" class="db-input" value="' . (isset($_POST['FromDate']) ? FormatDateForSQL($_POST['FromDate']) : date('Y-m-d')) . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('To Date') . '</label>
						<input type="date" name="ToDate" class="db-input" value="' . (isset($_POST['ToDate']) ? FormatDateForSQL($_POST['ToDate']) : date('Y-m-d')) . '" />
					</div>
					<div class="db-form-group d-flex align-items-center">
						<input type="checkbox" name="ShowDetails" id="ShowDetails" ' . (!isset($_POST['ShowDetails']) || $_POST['ShowDetails'] ? 'checked' : '') . ' />
						<label for="ShowDetails" class="ml-2 mb-0">' . __('Show Detailed Items') . '</label>
					</div>
					<button type="submit" name="Search" class="db-btn db-btn-primary w-100 mt-2">' . __('Search Requests') . '</button>
				</div>
			</div>

			<div class="db-card mt-4">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-boxes"></i> ' . __('Search by Part') . '</h3></div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Description Keywords') . '</label>
						<input type="text" name="Keywords" class="db-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Or Stock Code') . '</label>
						<input type="text" name="StockCode" class="db-input" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" />
					</div>
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
						<button type="submit" name="SearchPart" class="db-btn db-btn-secondary db-btn-sm">' . __('Find Part') . '</button>
						<button type="submit" name="ResetPart" class="db-btn db-btn-sm">' . __('Reset') . '</button>
					</div>
				</div>
			</div>
		</form>
	  </aside>';

echo '<main class="db-col-main">';

	if ($Cats == 0) {

		echo '<p class="bad">' . __('Problem Report') . ':<br />' . __('There are no stock categories currently defined please use the link below to set them up') . '</p>';
		echo '<br />
			<a href="' . $RootPath . '/StockCategories.php">' . __('Define Stock Categories') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}


	if (isset($StockItemsResult)) {
		$Count = DB_num_rows($StockItemsResult);
		echo '<div class="db-card overflow-hidden">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Matching Parts') . ' (' . $Count . ')</h3></div>
				<div class="db-card-body p-0">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('Total Applied') . '</th>
								<th>' . __('Units') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($MyRow = DB_fetch_array($StockItemsResult)) {
			echo '<tr>
					<td>
						<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
							<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
							<button type="submit" name="SelectedStockItem" value="' . $MyRow['stockid'] . '" class="db-btn db-btn-sm db-btn-secondary">' . $MyRow['stockid'] . '</button>
						</form>
					</td>
					<td class="db-font-medium">' . $MyRow['description'] . '</td>
					<td class="text-right db-font-bold">' . locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']) . '</td>
					<td class="db-muted">' . $MyRow['units'] . '</td>
				  </tr>';
		}
		echo '</tbody></table></div></div>';

	} elseif (isset($_POST['Search']) OR isset($StockID)) {
		if (isset($StockItemsResult) AND DB_num_rows($StockItemsResult) == 1) {
			$StockID = DB_fetch_array($StockItemsResult)[0];
		}

		// Prepare Query
		$Detailed = (isset($_POST['ShowDetails']) || isset($StockID));
		$SQL = "SELECT stockrequest.dispatchid, stockrequest.loccode, stockrequest.departmentid, departments.description, locations.locationname, despatchdate, authorised, closed, narrative, initiator" . 
			   ($Detailed ? ", stockrequestitems.stockid, stockmaster.description as stkdescription, quantity, stockrequestitems.decimalplaces, uom, completed" : "") .
			   " FROM stockrequest " . 
			   ($Detailed ? "INNER JOIN stockrequestitems ON stockrequest.dispatchid=stockrequestitems.dispatchid INNER JOIN stockmaster ON stockrequestitems.stockid=stockmaster.stockid " : "") .
			   "INNER JOIN departments ON stockrequest.departmentid=departments.departmentid 
			   INNER JOIN locations ON locations.loccode=stockrequest.loccode";

		// Filters
		if (isset($_POST['RequestNo']) && $_POST['RequestNo'] !== '') {
			$SQL .= " WHERE stockrequest.dispatchid = '" . $_POST['RequestNo'] . "'";
		} else {
			if ($_POST['StockLocation'] != 'All') {
				$SQL .= " WHERE stockrequest.loccode='" . $_POST['StockLocation'] . "'";
			} else {
				if (!in_array(19, $_SESSION['AllowedPageSecurityTokens'])) {
					$LocationsStr = implode("','", unserialize($_POST['Locations']));
					$SQL .= " WHERE stockrequest.loccode in ('" . $LocationsStr . "')";
				} else {
					$SQL .= " WHERE 1=1 ";
				}
			}
			if ($_POST['Authorized'] != 'All') $SQL .= " AND authorised = '" . $_POST['Authorized'] . "'";
			if ($_POST['Department'] == 'All') {
				if (!in_array(19, $_SESSION['AllowedPageSecurityTokens']) && isset($_POST['Departments'])) {
					$DepartmentsStr = implode("','", unserialize(base64_decode($_POST['Departments'])));
					$SQL .= " AND stockrequest.departmentid IN ('" . $DepartmentsStr . "')";
				}
			} else {
				$SQL .= " AND stockrequest.departmentid='" . $_POST['Department'] . "'";
			}
			if (isset($_POST['FromDate']) && is_date($_POST['FromDate'])) $SQL .= " AND despatchdate>='" . FormatDateForSQL($_POST['FromDate']) . "'";
			if (isset($_POST['ToDate']) && is_date($_POST['ToDate'])) $SQL .= " AND despatchdate<='" . FormatDateForSQL($_POST['ToDate']) . "'";
			if (isset($StockID)) $SQL .= " AND stockrequestitems.stockid='" . $StockID . "'";
		}
		if (!in_array(19, $_SESSION['AllowedPageSecurityTokens'])) {
			$SQL .= " AND (authoriser='" . $_SESSION['UserID'] . "' OR initiator='" . $_SESSION['UserID'] . "')";
		}
		
		$Result = DB_query($SQL);
		$Count = DB_num_rows($Result);

		if ($Count > 0) {
			// --- KPI BAR ---
			echo '<div class="db-kpi-container mb-4" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
					<div class="db-card p-3 d-flex align-items-center">
						<div class="bg-primary text-white p-3 rounded mr-3"><i class="fas fa-file-invoice fa-lg"></i></div>
						<div><div class="db-muted" style="font-size: 0.75rem;">' . __('Total Found') . '</div><div class="db-font-bold" style="font-size: 1.25rem;">' . $Count . '</div></div>
					</div>';
			// We'd need another query for pending if we really wanted to be rich, but for parity we'll stick to this.
			echo '</div>';

			echo '<div class="db-card overflow-hidden">
					<div class="db-card-header d-flex justify-content-between align-items-center">
						<h3 class="db-card-title"><i class="fas fa-table"></i> ' . __('Inquiry Results') . '</h3>
						<a href="' . $RootPath . '/InternalStockRequestInquiry.php" class="db-btn db-btn-sm">' . __('Clear Results') . '</a>
					</div>
					<div class="db-card-body p-0">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('ID') . '</th>
									<th>' . __('Location / Dept') . '</th>
									<th>' . __('Status') . '</th>
									<th>' . __('Dispatch') . '</th>';
			if ($Detailed) echo '<th>' . __('Stock Items') . '</th><th class="text-right">' . __('Qty') . '</th><th>' . __('Status') . '</th>';
			echo '				</tr>
							</thead>
							<tbody>';
			
			$CurrentID = '';
			while ($MyRow = DB_fetch_array($Result)) {
				$IsNewRow = ($CurrentID != $MyRow['dispatchid']);
				$CurrentID = $MyRow['dispatchid'];
				
				$AuthBadge = ($MyRow['authorised'] ? '<span class="db-badge db-badge-success">' . __('Authorized') . '</span>' : '<span class="db-badge db-badge-warning">' . __('Pending') . '</span>');
				$DispDate = ($MyRow['despatchdate'] == '1000-01-01' ? '<span class="db-muted">' . __('Not Yet') . '</span>' : ConvertSQLDate($MyRow['despatchdate']));
				
				echo '<tr ' . ($IsNewRow ? 'class="striped_row"' : 'style="border-top: none;"') . '>';
				if ($IsNewRow) {
					echo '<td class="db-font-bold text-primary">' . $MyRow['dispatchid'] . '</td>
						  <td>
							<div class="db-font-medium">' . $MyRow['locationname'] . '</div>
							<div class="db-muted text-xs">' . $MyRow['description'] . '</div>
						  </td>
						  <td>' . $AuthBadge . '</td>
						  <td>' . $DispDate . '</td>';
				} else {
					echo '<td colspan="4"></td>';
				}

				if ($Detailed) {
					$CompBadge = ($MyRow['completed'] ? '<i class="fas fa-check-circle text-success" title="' . __('Completed') . '"></i>' : '<i class="fas fa-clock text-warning" title="' . __('Open') . '"></i>');
					echo '<td>
							<div class="db-font-medium">' . $MyRow['stockid'] . '</div>
							<div class="db-muted text-xs">' . $MyRow['stkdescription'] . '</div>
						  </td>
						  <td class="text-right db-font-bold">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . ' <span class="text-xs">' . $MyRow['uom'] . '</span></td>
						  <td class="text-center">' . $CompBadge . '</td>';
				}
				echo '</tr>';
			}
			echo '</tbody></table></div></div>';
		} else {
			echo '<div class="db-card mt-4"><div class="db-card-body text-center p-5"><div class="db-muted mb-2"><i class="fas fa-search fa-3x"></i></div><h3 class="db-muted">' . __('No requisitions found matching criteria') . '</h3></div></div>';
		}
	} else {
		// --- ZERO STATE ---
		echo '<div class="db-card mt-4">
				<div class="db-card-body text-center" style="padding: 100px;">
					<div style="width: 100px; height: 100px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
						<i class="fas fa-chart-line fa-4x"></i>
					</div>
					<h2 class="db-font-bold mb-2">' . __('Requisition Intelligence') . '</h2>
					<p class="db-muted" style="max-width: 500px; margin: 0 auto 30px;">' . __('Configure your inquiry filters in the sidebar to analyze store requisitions, track authorization progress, and monitor item fulfillment.') . '</p>
					<div class="db-badge db-badge-secondary">' . __('Use Detailed View for item-level analysis') . '</div>
				</div>
			  </div>';
	}

echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
exit();

function GetSearchItems ($SQLConstraint='') {
	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		 echo __('Stock description keywords have been used in preference to the Stock code extract entered');
	}
	$SQL =  "SELECT stockmaster.stockid,
				   stockmaster.description,
				   stockmaster.decimalplaces,
				   SUM(stockrequestitems.quantity) AS qoh,
				   stockmaster.units
			FROM stockrequestitems INNER JOIN stockrequest ON stockrequestitems.dispatchid=stockrequest.dispatchid
			INNER JOIN departments ON stockrequest.departmentid = departments.departmentid

				INNER JOIN stockmaster ON stockrequestitems.stockid = stockmaster.stockid";
	if (isset($_POST['StockCat'])
		AND ((trim($_POST['StockCat']) == '') OR $_POST['StockCat'] == 'All')){
		 $WhereStockCat = '';
	} else {
		 $WhereStockCat = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "' ";
	}
	if ($_POST['Keywords']) {
		 //insert wildcard characters in spaces
		 $SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		 $SQL .= " WHERE stockmaster.description " . LIKE . " '" . $SearchString . "'
			  " . $WhereStockCat ;


	 } elseif (isset($_POST['StockCode'])){
		 $SQL .= " WHERE stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'" . $WhereStockCat;

	 } elseif (!isset($_POST['StockCode']) AND !isset($_POST['Keywords'])) {
		 $SQL .= " WHERE stockmaster.categoryid='" . $_POST['StockCat'] ."'";

	 }
	$SQL .= " AND (departments.authoriser='" . $_SESSION['UserID'] . "' OR initiator='" . $_SESSION['UserID'] . "') ";
	$SQL .= $SQLConstraint;
	$SQL .= " GROUP BY stockmaster.stockid,
					    stockmaster.description,
					    stockmaster.decimalplaces,
					    stockmaster.units
					    ORDER BY stockmaster.stockid";
	$ErrMsg =  __('No stock items were returned by the SQL because');
	$StockItemsResult = DB_query($SQL, $ErrMsg);
	return $StockItemsResult;
}
