<?php

require(__DIR__ . '/includes/session.php');

$PricesSecurity = 12; // don't show pricing info unless security token 12 available to user
$CanViewCost = in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']);

$Title = __('Inventory Negatives Listing');
$ViewTopic = 'Inventory';
$BookMark = 'StockNegatives';

use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

// --- FILTER LOGIC ---
$SelectedCategory = (isset($_POST['StockCat']) ? $_POST['StockCat'] : 'All');
$SelectedLocation = (isset($_POST['StockLocation']) ? $_POST['StockLocation'] : 'All');

// --- DATA FETCHING ---
$SQL = "SELECT stockmaster.stockid,
			   stockmaster.description,
			   stockmaster.categoryid,
			   stockmaster.decimalplaces,
			   locstock.loccode,
			   locations.locationname,
			   locstock.quantity,
			   stockmaster.actualcost AS cost,
			   stockmaster.units
		FROM stockmaster INNER JOIN locstock
		ON stockmaster.stockid=locstock.stockid
		INNER JOIN locations
		ON locstock.loccode = locations.loccode
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
		WHERE locstock.quantity < 0";

if ($SelectedCategory != 'All') $SQL .= " AND stockmaster.categoryid = '" . $SelectedCategory . "'";
if ($SelectedLocation != 'All') $SQL .= " AND locstock.loccode = '" . $SelectedLocation . "'";

$SQL .= " ORDER BY locstock.loccode, stockmaster.categoryid, stockmaster.stockid";

$Result = DB_query($SQL);
$NumRows = DB_num_rows($Result);

// --- PDF GENERATION TRIGGER ---
if (isset($_POST['PrintPDF'])) {
	$HTML = '<html><head><meta charset="UTF-8"><style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 0; }
		.container { padding: 30px; }
		.layout-table { width: 100%; border: none; margin-bottom: 20px; }
		.logo { max-height: 50px; }
		.doc-title { font-size: 20px; font-weight: bold; color: #d32f2f; margin: 0; }
		.metadata { font-size: 9px; color: #666; margin-top: 5px; }
		
		.item-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		.item-table th { background: #f8f9fa; text-align: left; padding: 8px; border-bottom: 2px solid #ddd; text-transform: uppercase; font-size: 9px; }
		.item-table td { padding: 8px; border-bottom: 1px solid #eee; vertical-align: top; }
		.number { text-align: right; font-weight: bold; color: #d32f2f; }
	</style></head><body>
	<div class="container">
		<table class="layout-table">
			<tr>
				<td><img class="logo" src="' . $_SESSION['LogoFile'] . '" /></td>
				<td style="text-align: right;">
					<h1 class="doc-title">' . __('Negative Stock Report') . '</h1>
					<div class="metadata">' . __('Generated on') . ': ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '</div>
				</td>
			</tr>
		</table>
		<table class="item-table">
			<thead>
				<tr>
					<th>' . __('Location') . '</th>
					<th>' . __('Item Code') . '</th>
					<th>' . __('Description') . '</th>
					<th class="number">' . __('Quantity') . '</th>
				</tr>
			</thead>
			<tbody>';
	
	while ($row = DB_fetch_array($Result)) {
		$HTML .= '<tr>
					<td>' . htmlspecialchars($row['loccode'] . ' - ' . $row['locationname']) . '</td>
					<td>' . htmlspecialchars($row['stockid']) . '</td>
					<td>' . htmlspecialchars($row['description']) . '</td>
					<td class="number">' . locale_number_format($row['quantity'], $row['decimalplaces']) . '</td>
				  </tr>';
	}
	$HTML .= '</tbody></table></div></body></html>';

	$DomPDF = new Dompdf($DomPDFOptions);
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
	$DomPDF->render();
	$DomPDF->stream($_SESSION['DatabaseName'] . '_NegativeStocks_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	exit();
}

// --- KPI CALCULATION ---
$TotalLines = 0;
$TotalNegativeQty = 0;
$TotalValueImpact = 0;
$Data = [];
while ($row = DB_fetch_array($Result)) {
	$TotalLines++;
	$TotalNegativeQty += abs($row['quantity']);
	if ($CanViewCost) $TotalValueImpact += (abs($row['quantity']) * $row['cost']);
	$Data[] = $row;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card mb-4">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-filter mr-2"></i>' . __('Refine Report') . '</h3></div>
				<div class="db-card-body">
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
					<button type="submit" name="Refresh" class="db-btn db-btn-primary w-100 mt-4">' . __('Update View') . '</button>
				</div>
			</div>

			<div class="db-card">
				<div class="db-card-body">
					<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary w-100 mb-2">
						<i class="fas fa-file-pdf mr-2" style="color: var(--text-muted);"></i>' . __('Export to PDF') . '
					</button>
					<div class="db-muted text-center text-xs px-2">' . __('Generate a formal audit-ready document of current inventory discrepancies.') . '</div>
				</div>
			</div>
		</form>
	  </aside>';

// MAIN
echo '<main class="db-col-main">';

// Header
echo '<div class="d-flex justify-content-between align-items-center mb-4">
		<div>
			<h1 class="db-font-bold" style="font-size: 1.75rem; color: var(--text-main);">' . __('Inventory Discrepancy Dashboard') . '</h1>
			<p class="db-muted">' . __('Identifying and analyzing stock lines with critical negative balances across all locations.') . '</p>
		</div>
		<div class="db-badge shadow-sm" style="padding: 10px 15px; background: var(--bg-workspace); color: var(--text-body); border: 1px solid var(--border);">
			<i class="fas fa-exclamation-triangle mr-2" style="color: var(--danger);"></i>' . __('Attention Required') . '
		</div>
	  </div>';

if ($TotalLines == 0) {
	echo '<div class="db-card" style="background: var(--success-bg); border-color: var(--success);">
			<div class="db-card-body text-center p-5">
				<div class="mb-3" style="color: var(--success);"><i class="fas fa-check-circle fa-4x"></i></div>
				<h2 class="db-font-bold" style="color: var(--success);">' . __('Clean Inventory!') . '</h2>
				<p class="db-muted">' . __('No negative stock balances were found matching your current filters. All stock locations are maintaining healthy or zero levels.') . '</p>
			</div>
		  </div>';
} else {
	// KPIs
	echo '<div class="db-kpi-container mb-5" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
			<div class="db-card p-4">
				<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Negative Lines') . '</div>
				<div class="d-flex align-items-baseline">
					<div class="db-font-bold text-2xl mr-2" style="color: var(--text-main);">' . number_format($TotalLines) . '</div>
					<div class="text-xs" style="color: var(--text-muted);"><i class="fas fa-list-ul"></i></div>
				</div>
			</div>
			<div class="db-card p-4">
				<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Total Neg. Quantity') . '</div>
				<div class="d-flex align-items-baseline">
					<div class="db-font-bold text-2xl mr-2">' . number_format($TotalNegativeQty) . '</div>
					<div class="db-muted text-xs">' . __('Units') . '</div>
				</div>
			</div>
			<div class="db-card p-4">
				<div class="db-muted text-xs uppercase tracking-wider mb-2">' . __('Value Adjustment') . '</div>
				<div class="d-flex align-items-baseline">
					<div class="db-font-bold text-2xl mr-2">' . ($CanViewCost ? locale_number_format($TotalValueImpact, 2) : '---') . '</div>
					<div class="db-muted text-xs">' . $_SESSION['CompanyDefaults']['CurrencyRate'] . '</div>
				</div>
			</div>
		  </div>';

	// Table
	echo '<div class="db-card overflow-hidden">
			<div class="db-card-header d-flex justify-content-between align-items-center">
				<h3 class="db-card-title"><i class="fas fa-list mr-2"></i>' . __('Critical Negative Stocks') . '</h3>
				<div class="db-badge db-badge-secondary">' . $TotalLines . ' ' . __('Lines Flagged') . '</div>
			</div>
			<div class="db-card-body p-0">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Stock Item') . '</th>
							<th>' . __('Location') . '</th>
							<th class="text-right">' . __('Quantity') . '</th>
							<th class="text-right">' . __('Value Impact') . '</th>
						</tr>
					</thead>
					<tbody>';
	
	foreach ($Data as $row) {
		$value = ($CanViewCost ? locale_number_format(abs($row['quantity']) * $row['cost'], 2) : '<span class="db-muted">---</span>');
		echo '<tr>
				<td>
					<div class="db-font-bold">' . htmlspecialchars($row['stockid']) . '</div>
					<div class="db-muted text-xs">' . htmlspecialchars($row['description']) . '</div>
				</td>
				<td>
					<div class="db-font-medium">' . htmlspecialchars($row['loccode']) . '</div>
					<div class="db-muted text-xs">' . htmlspecialchars($row['locationname']) . '</div>
				</td>
				<td class="text-right">
					<div class="db-badge" style="font-size: 1rem; padding: 4px 12px; background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger);">' . locale_number_format($row['quantity'], $row['decimalplaces']) . ' <span class="text-xs">' . $row['units'] . '</span></div>
				</td>
				<td class="text-right db-font-mono font-bold">' . $value . '</td>
			  </tr>';
	}
	
	echo '</tbody></table></div></div>';
}

echo '</main></div>'; // Close Layout

include(__DIR__ . '/includes/footer.php');
