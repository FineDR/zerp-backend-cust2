<?php

require(__DIR__ . '/includes/session.php');

$PricesSecurity = 12; // don't show pricing info unless security token 12 available to user
$CanViewCost = in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']);

$Today = time();
$Title = __('Aged Controlled Inventory');
$ViewTopic = 'Inventory';
$BookMark = 'AgedControlled';
include(__DIR__ . '/includes/header.php');

// --- FILTER LOGIC ---
$SelectedCategory = (isset($_POST['StockCat']) ? $_POST['StockCat'] : 'All');
$SelectedLocation = (isset($_POST['StockLocation']) ? $_POST['StockLocation'] : 'All');

// --- DATA FETCHING ---
$SQL = "SELECT stockserialitems.stockid,
				stockmaster.description,
				stockserialitems.serialno,
				stockserialitems.quantity,
				stockserialitems.loccode,
				locations.locationname,
				stockmaster.units,
				stockmaster.actualcost AS cost,
				createdate,
				decimalplaces
			FROM stockserialitems
			INNER JOIN stockmaster ON stockmaster.stockid = stockserialitems.stockid
			INNER JOIN locations ON locations.loccode = stockserialitems.loccode
			INNER JOIN locationusers ON locationusers.loccode=stockserialitems.loccode
				AND locationusers.userid='" .  $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE quantity > 0";

if ($SelectedCategory != 'All') $SQL .= " AND stockmaster.categoryid = '" . $SelectedCategory . "'";
if ($SelectedLocation != 'All') $SQL .= " AND stockserialitems.loccode = '" . $SelectedLocation . "'";

$SQL .= " ORDER BY createdate ASC, stockid ASC";

$LocStockResult = DB_query($SQL);
$NumRows = DB_num_rows($LocStockResult);

// --- KPI CALCULATION ---
$TotalBatches = 0;
$TotalQty = 0;
$TotalVal = 0;
$TotalDays = 0;
$Batches = [];

while ($row = DB_fetch_array($LocStockResult)) {
	$DaysOld = floor(($Today - strtotime($row['createdate'])) / (60 * 60 * 24));
	$TotalQty += $row['quantity'];
	if ($CanViewCost) $TotalVal += ($row['quantity'] * $row['cost']);
	$TotalDays += $DaysOld;
	$TotalBatches++;
	$Batches[] = array_merge($row, ['DaysOld' => $DaysOld]);
}
$AvgAge = ($TotalBatches > 0) ? round($TotalDays / $TotalBatches) : 0;

// --- UI RENDER ---
echo '<div class="db-bottom-layout">';

// Sidebar - Filters
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card mb-4">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-filter mr-2"></i>' . __('Report Filters') . '</h3></div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Category') . '</label>
						<select name="StockCat" class="db-select">
							<option value="All">' . __('All Categories') . '</option>';
							$CatResult = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
							while ($CatRow = DB_fetch_array($CatResult)) {
								$sel = ($SelectedCategory == $CatRow['categoryid'] ? 'selected' : '');
								echo '<option ' . $sel . ' value="' . $CatRow['categoryid'] . '">' . $CatRow['categorydescription'] . '</option>';
							}
echo '					</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Inventory Location') . '</label>
						<select name="StockLocation" class="db-select">
							<option value="All">' . __('All Authorized Locations') . '</option>';
							$LocRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND userid='" . $_SESSION['UserID'] . "' AND canview=1 ORDER BY locationname");
							while ($LocRow = DB_fetch_array($LocRes)) {
								$sel = ($SelectedLocation == $LocRow['loccode'] ? 'selected' : '');
								echo '<option ' . $sel . ' value="' . $LocRow['loccode'] . '">' . $LocRow['locationname'] . '</option>';
							}
echo '					</select>
					</div>
					<button type="submit" name="Refresh" class="db-btn db-btn-primary w-100 mt-4">' . __('Update Report') . '</button>
				</div>
			</div>

			<div class="db-card text-center p-4">
				<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('As Of Date') . '</div>
				<div class="db-font-bold text-lg">' . date($_SESSION['DefaultDateFormat']) . '</div>
				<div class="db-badge db-badge-secondary mt-3">' . __('Real-time Data') . '</div>
			</div>
		</form>
	  </aside>';

// Main Dashboard
echo '<main class="db-col-main">';

// Header info
echo '<div class="d-flex justify-content-between align-items-center mb-4">
		<div>
			<h1 class="db-font-bold" style="font-size: 1.75rem; color: var(--text-heading);">' . $Title . '</h1>
			<p class="db-muted">' . __('Deep analysis of inventory aging for controlled batches and serial items.') . '</p>
		</div>
		<div class="db-badge db-badge-outline-primary" style="padding: 0.75rem 1.25rem;">
			<i class="fas fa-calendar-check mr-2"></i>' . date('H:i') . ' ' . __('Current Run') . '
		</div>
	  </div>';

// KPI Scorecard
echo '<div class="db-kpi-container mb-5" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
		<div class="db-card p-4">
			<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Active Batches') . '</div>
			<div class="d-flex align-items-baseline">
				<div class="db-font-bold text-2xl mr-2">' . number_format($TotalBatches) . '</div>
				<div class="text-xs text-primary"><i class="fas fa-layer-group"></i></div>
			</div>
		</div>
		<div class="db-card p-4">
			<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Total Quantity') . '</div>
			<div class="d-flex align-items-baseline">
				<div class="db-font-bold text-2xl mr-2">' . number_format($TotalQty) . '</div>
				<div class="db-muted text-xs">' . __('Units') . '</div>
			</div>
		</div>
		<div class="db-card p-4">
			<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Inventory Value') . '</div>
			<div class="d-flex align-items-baseline">
				<div class="db-font-bold text-2xl mr-2">' . ($CanViewCost ? locale_number_format($TotalVal, 2) : '---') . '</div>
				<div class="db-muted text-xs">' . $_SESSION['CompanyDefaults']['CurrencyRate'] . '</div>
			</div>
		</div>
		<div class="db-card p-4">
			<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Avg. Aging') . '</div>
			<div class="d-flex align-items-baseline">
				<div class="db-font-bold text-2xl mr-2">' . $AvgAge . '</div>
				<div class="db-muted text-xs">' . __('Days') . '</div>
			</div>
		</div>
	  </div>';

// Results Grid
echo '<div class="db-card overflow-hidden">
		<div class="db-card-header d-flex justify-content-between align-items-center">
			<h2 class="db-card-title"><i class="fas fa-table mr-2"></i>' . __('Aged Inventory Breakdown') . '</h2>
			<div class="db-badge db-badge-secondary">' . $NumRows . ' ' . __('Rows') . '</div>
		</div>
		<div class="db-card-body p-0">
			<table class="db-table">
				<thead>
					<tr>
						<th>' . __('Controlled Item') . '</th>
						<th>' . __('Batch / Serial #') . '</th>
						<th>' . __('Location') . '</th>
						<th class="text-right">' . __('Qty Remaining') . '</th>
						<th>' . __('Value') . '</th>
						<th>' . __('Founded Date') . '</th>
						<th class="text-right">' . __('Aging') . '</th>
					</tr>
				</thead>
				<tbody>';

foreach ($Batches as $row) {
	// Aging Badge Logic
	if ($row['DaysOld'] >= 180) {
		$AgeClass = 'db-badge-danger';
		$RowStyle = 'style="background: rgba(220, 53, 69, 0.05);"';
	} elseif ($row['DaysOld'] >= 90) {
		$AgeClass = 'db-badge-warning';
		$RowStyle = '';
	} else {
		$AgeClass = 'db-badge-success';
		$RowStyle = '';
	}

	echo '<tr ' . $RowStyle . '>
			<td>
				<div class="db-font-medium">' . mb_strtoupper($row['stockid']) . '</div>
				<div class="db-muted text-xs">' . $row['description'] . '</div>
			</td>
			<td class="db-font-mono text-primary">' . $row['serialno'] . '</td>
			<td>' . $row['locationname'] . '</td>
			<td class="text-right db-font-bold">' . locale_number_format($row['quantity'], $row['decimalplaces']) . ' <span class="db-muted text-xs">' . $row['units'] . '</span></td>
			<td>' . ($CanViewCost ? locale_number_format($row['quantity'] * $row['cost'], 2) : '<span class="db-muted">---</span>') . '</td>
			<td class="db-muted">' . ConvertSQLDate($row['createdate']) . '</td>
			<td class="text-right">
				<div class="db-badge ' . $AgeClass . '" style="min-width: 80px;">' . $row['DaysOld'] . ' ' . __('Days') . '</div>
			</td>
		  </tr>';
}

if (empty($Batches)) {
	echo '<tr><td colspan="7" class="text-center p-5 db-muted">' . __('No aged controlled inventory found matching current filters.') . '</td></tr>';
}

echo '				</tbody>
			</table>
		</div>
	  </div>';

echo '</main></div>'; // Close layout

include(__DIR__ . '/includes/footer.php');
