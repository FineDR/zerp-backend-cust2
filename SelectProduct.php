<?php

/* Selection of items. All item maintenance, transactions and inquiries start with this script. */

$PricesSecurity = 12; //don't show pricing info unless security token 12 available to user
$SuppliersSecurity = 9; //don't show supplier purchasing info unless security token 9 available to user
$CostSecurity = 18; //don't show cost info unless security token 18 available to user

require(__DIR__ . '/includes/session.php');

$Title = __('Search Inventory Items');
$ViewTopic = 'Inventory';
$BookMark = 'SelectingInventory';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_GET['StockID'])) {
	$_GET['StockID'] = trim(mb_strtoupper($_GET['StockID']));
	$_POST['Select'] = trim(mb_strtoupper($_GET['StockID']));
}

if (isset($_GET['NewSearch']) or isset($_POST['Next']) or isset($_POST['Previous']) or isset($_POST['Go'])) {
	unset($StockID);
	unset($_SESSION['SelectedStockItem']);
	unset($_POST['Select']);
}
if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}
}
if (isset($_POST['StockCode'])) {
	$_POST['StockCode'] = trim(mb_strtoupper($_POST['StockCode']));
}

// Always show the search facilities
$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);
if (DB_num_rows($Result1) == 0) {
	prnMsg(__('There are no stock categories currently defined. Please use the link below to set them up'), 'warn');
	echo '<a class="toplink" href="' . $RootPath . '/StockCategories.php">' . __('Define Stock Categories') . '</a><br /><br />';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

echo '<div class="db-bottom-layout">';

// 1. RUN SEARCH LOGIC (Move up)
if (isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	$_POST['Search']='Search';
}
if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) {
		$_POST['PageOffset'] = 1;
	}
	$SQL = GenerateStockmasterQuery($_POST);
	$SearchResult = DB_query($SQL);
}

// 2. SIDEBAR: PERSISTENT SEARCH
echo '<aside class="db-col-aside">';
echo '<div class="db-card mb-4">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Search Items') . '</h3>
		</div>
		<div class="db-card-body">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-form-group">
					<label class="db-label">' . __('Category') . '</label>
					<select name="StockCat" class="db-select">';
if (!isset($_POST['StockCat'])) $_POST['StockCat'] = 'All';
echo '<option value="All" ' . ($_POST['StockCat'] == 'All' ? 'selected' : '') . '>' . __('All Categories') . '</option>';
DB_data_seek($Result1, 0);
while ($MyRow1 = DB_fetch_array($Result1)) {
	echo '<option value="' . $MyRow1['categoryid'] . '" ' . ($_POST['StockCat'] == $MyRow1['categoryid'] ? 'selected' : '') . '>' . $MyRow1['categorydescription'] . '</option>';
}
echo '				</select>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Keyword/Code') . '</label>
					<input type="text" name="Keywords" class="db-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="' . __('e.g. Pump, 1001') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Stock Code') . '</label>
					<input type="text" name="StockCode" class="db-input" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" />
				</div>
				<button type="submit" name="Search" class="db-btn db-btn-primary w-100">' . __('Search Now') . '</button>
			</form>
		</div>
	  </div>';

// 3. SIDEBAR: RESULTS LIST
if (isset($SearchResult)) {
	$ListCount = DB_num_rows($SearchResult);
	echo '<div class="db-card overflow-hidden">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Results') . ' (' . $ListCount . ')</h3>
			</div>
			<div class="db-card-body p-0" style="max-height: 500px; overflow-y: auto;">';
	
	if ($ListCount > 0) {
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		$RowIndex = 0;
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" name="Keywords" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" />
				<input type="hidden" name="StockCat" value="' . $_POST['StockCat'] . '" />
				<input type="hidden" name="StockCode" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" />';

		while (($MyRow = DB_fetch_array($SearchResult)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			$QOH = ($MyRow['mbflag'] == 'D' ? 'N/A' : locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']));
			echo '<button type="submit" name="Select" value="' . $MyRow['stockid'] . '" class="w-100 text-left p-3 border-bottom db-row-hover" style="background: none; border: none; border-bottom: 1px solid var(--border-soft) !important;">
					<div class="db-font-bold text-primary" style="font-size: 0.85rem;">' . $MyRow['stockid'] . '</div>
					<div class="db-muted" style="font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' . $MyRow['description'] . '</div>
					<div style="font-size: 0.7rem; margin-top: 4px;">' . __('QOH') . ': <span class="db-font-bold">' . $QOH . '</span> ' . $MyRow['units'] . '</div>
				  </button>';
			$RowIndex++;
		}
		
		if ($ListPageMax > 1) {
			echo '<div class="p-3 bg-light border-top d-flex justify-content-between align-items: center;">
					<button type="submit" name="Previous" class="db-btn db-btn-sm" ' . ($_POST['PageOffset'] <= 1 ? 'disabled' : '') . '><i class="fas fa-chevron-left"></i></button>
					<span class="db-font-medium" style="font-size: 0.75rem;">' . $_POST['PageOffset'] . ' / ' . $ListPageMax . '</span>
					<button type="submit" name="Next" class="db-btn db-btn-sm" ' . ($_POST['PageOffset'] >= $ListPageMax ? 'disabled' : '') . '><i class="fas fa-chevron-right"></i></button>
					<input type="hidden" name="PageOffset" value="' . $_POST['PageOffset'] . '" />
				  </div>';
		}
		echo '</form>';
	} else {
		echo '<div class="p-4 text-center db-muted" style="font-size: 0.8rem;">' . __('No items found') . '</div>';
	}
	echo '	</div>
		  </div>';
}
echo '</aside>';

// MAIN: ITEM DASHBOARD
echo '<main class="db-col-main">';

// end of showing search facilities
/* displays item options if there is one and only one selected */
$TableHead =
	'<table cellpadding="4" width="90%" class="selection">
		<thead>
			<tr>
				<th style="width:33%">' .
					'<img alt="" src="' . $RootPath . '/css/' . $Theme . '/images/reports.png" title="' . __('Inquiries and Reports') . '" />' .
					__('Item Inquiries') . '</th>
				<th style="width:33%">' .
					'<img alt="" src="' . $RootPath . '/css/' . $Theme . '/images/transactions.png" title="' . __('Transactions') . '" />' .
					__('Item Transactions') . '</th>
				<th style="width:33%">' .
					'<img alt="" src="' . $RootPath . '/css/' . $Theme . '/images/maintenance.png" title="' . __('Maintenance') . '" />' .
					__('Item Maintenance') . '</th>
			</tr>
		</thead>
		<tbody>';
if (!isset($_POST['Search']) AND (isset($_POST['Select']) OR isset($_SESSION['SelectedStockItem']))) {
	if (isset($_POST['Select'])) {
		$_SESSION['SelectedStockItem'] = $_POST['Select'];
		$StockID = $_POST['Select'];
		unset($_POST['Select']);
	} else {
		$StockID = $_SESSION['SelectedStockItem'];
	}

	$Result = DB_query("SELECT stockmaster.description,
								stockmaster.longdescription,
								stockmaster.mbflag,
								stockcategory.stocktype,
								stockmaster.units,
								stockmaster.decimalplaces,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.actualcost AS cost,
								stockmaster.discontinued,
								stockmaster.eoq,
								stockmaster.volume,
								stockmaster.grossweight,
								stockcategory.categorydescription,
								stockmaster.categoryid
						FROM stockmaster INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
						WHERE stockid='" . $StockID . "'");
	$MyRow = DB_fetch_array($Result);
	
	$Its_A_Kitset_Assembly_Or_Dummy = in_array($MyRow['mbflag'], ['A', 'G', 'K', 'D']);
	$Its_A_Dummy = ($MyRow['mbflag'] == 'D');
	$Its_A_Kitset = in_array($MyRow['mbflag'], ['K', 'G']);
	$Its_A_Labour_Item = ($MyRow['mbflag'] == 'D' && $MyRow['stocktype'] == 'L');

	// --- DASHBOARD HEADER ---
	echo '<div class="mb-4" style="display: flex; justify-content: space-between; align-items: flex-start;">
			<div>
				<h1 class="db-title mb-1">' . $StockID . ' - ' . $MyRow['description'] . '</h1>
				<div class="db-muted" style="font-size: 0.9rem;">' . $MyRow['categorydescription'] . '</div>
			</div>';
	if ($MyRow['discontinued'] == 1) {
		echo '<div class="db-badge db-badge-danger" style="font-size: 0.9rem; padding: 6px 14px;">' . __('Obsolete') . '</div>';
	} else {
		echo '<div class="db-badge db-badge-success" style="font-size: 0.9rem; padding: 6px 14px;">' . __('Active') . '</div>';
	}
	echo '</div>';

	// --- KPI ROW ---
	$QOH = ($Its_A_Kitset_Assembly_Or_Dummy ? 0 : GetQuantityOnHand($StockID, 'ALL'));
	$Demand = GetDemand($StockID, 'ALL');
	$QOO = ($Its_A_Kitset_Assembly_Or_Dummy ? 0 : GetQuantityOnOrder($StockID, 'ALL'));

	echo '<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">';
	// QOH
	echo '<div class="db-card p-4">
			<div class="db-muted mb-1" style="font-size: 0.75rem;">' . __('Quantity On Hand') . '</div>
			<div class="db-font-bold text-primary" style="font-size: 1.5rem;">' . locale_number_format($QOH, $MyRow['decimalplaces']) . ' <span style="font-size: 0.9rem;">' . $MyRow['units'] . '</span></div>
		  </div>';
	// Demand
	echo '<div class="db-card p-4">
			<div class="db-muted mb-1" style="font-size: 0.75rem;">' . __('Current Demand') . '</div>
			<div class="db-font-bold" style="font-size: 1.5rem;">' . locale_number_format($Demand, $MyRow['decimalplaces']) . '</div>
		  </div>';
	// On Order
	echo '<div class="db-card p-4">
			<div class="db-muted mb-1" style="font-size: 0.75rem;">' . __('On Order') . '</div>
			<div class="db-font-bold" style="font-size: 1.5rem;">' . locale_number_format($QOO, $MyRow['decimalplaces']) . '</div>
		  </div>';
	// GP % (if authorized)
	if (in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens']) && in_array($CostSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
		$PriceResult = DB_query("SELECT price FROM prices WHERE currabrev ='" . $_SESSION['CompanyRecord']['currencydefault'] . "' AND typeabbrev = '" . $_SESSION['DefaultPriceList'] . "' AND debtorno='' AND branchcode='' AND startdate <= CURRENT_DATE AND enddate >= CURRENT_DATE AND stockid='" . $StockID . "'");
		$Price = (DB_num_rows($PriceResult) > 0 ? DB_fetch_row($PriceResult)[0] : 0);
		$Cost = ($Its_A_Kitset ? DB_fetch_row(DB_query("SELECT SUM(bom.quantity * stockmaster.actualcost) FROM bom INNER JOIN stockmaster ON bom.component=stockmaster.stockid WHERE bom.parent='" . $StockID . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE"))[0] : $MyRow['cost']);
		$GP = ($Price > 0 ? ($Price - $Cost) * 100 / $Price : 0);
		echo '<div class="db-card p-4">
				<div class="db-muted mb-1" style="font-size: 0.75rem;">' . __('Gross Profit') . ' %</div>
				<div class="db-font-bold ' . ($GP > 25 ? 'text-success' : 'text-warning') . '" style="font-size: 1.5rem;">' . locale_number_format($GP, 1) . '%</div>
			  </div>';
	}
	echo '</div>';

	// --- MAIN GRID ---
	echo '<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">';
	
	// Left Column: Details
	echo '<div>';
	
	// Card: Specification
	echo '<div class="db-card mb-4">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Product Specification') . '</h3></div>
			<div class="db-card-body">
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
					<div><label class="db-muted" style="font-size: 0.75rem; display: block;">' . __('Item Type') . '</label><div class="db-font-medium">';
	switch ($MyRow['mbflag']) {
		case 'A': echo __('Assembly'); break;
		case 'G': echo __('Phantom'); break;
		case 'K': echo __('Kitset'); break;
		case 'D': echo __('Service'); break;
		case 'B': echo __('Purchased'); break;
		default: echo __('Manufactured'); break;
	}
	echo '</div></div>
					<div><label class="db-muted" style="font-size: 0.75rem; display: block;">' . __('Control') . '</label><div class="db-font-medium">' . ($MyRow['serialised'] ? __('Serialised') : ($MyRow['controlled'] ? __('Batched') : __('N/A'))) . '</div></div>
					<div><label class="db-muted" style="font-size: 0.75rem; display: block;">' . __('Volume') . '</label><div class="db-font-medium">' . locale_number_format($MyRow['volume'], 3) . '</div></div>
					<div><label class="db-muted" style="font-size: 0.75rem; display: block;">' . __('Weight') . '</label><div class="db-font-medium">' . locale_number_format($MyRow['grossweight'], 3) . '</div></div>
				</div>';
	
	// Properties
	$SQLProps = "SELECT stkcatpropid, label, controltype, defaultvalue FROM stockcatproperties WHERE categoryid ='" . $MyRow['categoryid'] . "' AND reqatsalesorder = 0 ORDER BY stkcatpropid";
	$PropsResult = DB_query($SQLProps);
	if (DB_num_rows($PropsResult) > 0) {
		echo '<div class="mt-4 pt-4 border-top">
				<div class="db-font-bold mb-3" style="font-size: 0.8rem;">' . __('Category Properties') . '</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">';
		while ($Prop = DB_fetch_array($PropsResult)) {
			$PVal = DB_query("SELECT value FROM stockitemproperties WHERE stockid='" . $StockID . "' AND stkcatpropid ='" . $Prop['stkcatpropid'] . "'");
			$Val = (DB_num_rows($PVal) > 0 ? DB_fetch_row($PVal)[0] : __('Not Set'));
			echo '<div><span class="db-muted" style="font-size: 0.7rem;">' . $Prop['label'] . ':</span> <span class="db-font-medium ml-1">' . $Val . '</span></div>';
		}
		echo '</div></div>';
	}
	echo '</div></div>';

	// Card: Supplier Data
	if (($MyRow['mbflag'] == 'B' OR ($MyRow['mbflag'] == 'M')) AND (in_array($SuppliersSecurity, $_SESSION['AllowedPageSecurityTokens']))) {
		$SuppResult = DB_query("SELECT suppliers.suppname, suppliers.currcode, suppliers.supplierid, purchdata.price, purchdata.suppliers_partno, purchdata.leadtime, purchdata.conversionfactor, purchdata.minorderqty, purchdata.preferred, currencies.decimalplaces FROM purchdata INNER JOIN suppliers ON purchdata.supplierno=suppliers.supplierid INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE purchdata.stockid = '" . $StockID . "' AND purchdata.effectivefrom=(SELECT max(a.effectivefrom) FROM purchdata a WHERE purchdata.supplierno=a.supplierno and a.stockid=purchdata.stockid) ORDER BY purchdata.preferred DESC");
		if (DB_num_rows($SuppResult) > 0) {
			echo '<div class="db-card mb-4">
					<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Supplier Sourcing') . '</h3></div>
					<div class="db-card-body p-0">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Supplier') . '</th>
									<th class="text-right">' . __('Part No.') . '</th>
									<th class="text-right">' . __('Cost') . '</th>
									<th>' . __('Order') . '</th>
								</tr>
							</thead>
							<tbody>';
			while ($SuppRow = DB_fetch_array($SuppResult)) {
				echo '<tr>
						<td class="db-font-medium">' . $SuppRow['suppname'] . '</td>
						<td class="text-right db-muted" style="font-size: 0.75rem;">' . $SuppRow['suppliers_partno'] . '</td>
						<td class="text-right db-font-bold">' . locale_number_format($SuppRow['price'] / $SuppRow['conversionfactor'], $SuppRow['decimalplaces']) . ' <span style="font-size: 0.7rem;">' . $SuppRow['currcode'] . '</span></td>
						<td class="text-center"><a href="' . $RootPath . '/PO_Header.php?NewOrder=Yes&SelectedSupplier=' . $SuppRow['supplierid'] . '&StockID=' . urlencode($StockID) . '&Quantity=' . $SuppRow['minorderqty'] . '&LeadTime=' . $SuppRow['leadtime'] . '" class="db-btn db-btn-sm db-btn-primary"><i class="fas fa-shopping-cart"></i></a></td>
					  </tr>';
			}
			echo '</tbody></table></div></div>';
		}
	}
	
	echo '</div>'; // End Left Column
	
	// Right Column: Image & Quick Actions
	echo '<div>';
	
	// Image Card
	$PossibleImageFiles = glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{png,jpg,jpeg}', GLOB_BRACE);
	$ImageFile = (count($PossibleImageFiles) > 0 ? $PossibleImageFiles[0] : '');
	$StockImgLink = GetImageLink($ImageFile, $StockID, 300, 300, "db-card mb-4 p-2", "");
	echo '<div class="db-card mb-4 p-2 text-center">' . $StockImgLink . '</div>';

	// Card: Quick Actions
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-bolt"></i> ' . __('Quick Actions') . '</h3></div>
			<div class="db-card-body">
				<div style="display: grid; grid-template-columns: 1fr; gap: 8px;">';
	// Inquiries
	echo '<div class="db-font-bold mt-2" style="font-size: 0.75rem; color: var(--primary);"><i class="fas fa-search-plus mr-1"></i> ' . __('INQUIRIES') . '</div>';
	echo '<a href="' . $RootPath . '/StockMovements.php?StockID=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-exchange-alt mr-2"></i> ' . __('Stock Movements') . '</a>';
	if (!$Its_A_Kitset_Assembly_Or_Dummy) {
		echo '<a href="' . $RootPath . '/StockStatus.php?StockID=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-info mr-2"></i> ' . __('Stock Status') . '</a>';
	}
	echo '<a href="' . $RootPath . '/SelectSalesOrder.php?SelectedStockItem=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-file-invoice mr-2"></i> ' . __('Open Sales Orders') . '</a>';

	// Transactions
	if (!$Its_A_Kitset_Assembly_Or_Dummy) {
		echo '<div class="db-font-bold mt-3" style="font-size: 0.75rem; color: var(--primary);"><i class="fas fa-truck mr-1"></i> ' . __('TRANSACTIONS') . '</div>';
		echo '<a href="' . $RootPath . '/StockAdjustments.php?StockID=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-adjust mr-2"></i> ' . __('Adjust Quantity') . '</a>';
		echo '<a href="' . $RootPath . '/StockTransfers.php?StockID=' . urlencode($StockID) . '&NewTransfer=true" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-random mr-2"></i> ' . __('Location Transfer') . '</a>';
	}

	// Maintenance
	echo '<div class="db-font-bold mt-3" style="font-size: 0.75rem; color: var(--primary);"><i class="fas fa-tools mr-1"></i> ' . __('MAINTENANCE') . '</div>';
	echo '<a href="' . $RootPath . '/Stocks.php?StockID=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-edit mr-2"></i> ' . __('Edit Item Details') . '</a>';
	if (!$Its_A_Kitset) {
		echo '<a href="' . $RootPath . '/Prices.php?Item=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-tag mr-2"></i> ' . __('Maintain Pricing') . '</a>';
	}
	if (!$Its_A_Kitset_Assembly_Or_Dummy) {
		echo '<a href="' . $RootPath . '/StockCostUpdate.php?StockID=' . urlencode($StockID) . '" class="db-btn db-btn-sm db-btn-secondary text-left"><i class="fas fa-dollar-sign mr-2"></i> ' . __('Update Costs') . '</a>';
	}

	echo '		</div>
			</div>
		  </div>';
	
	echo '</div>'; // End Right Column
	echo '</div>'; // End Main Grid

} else {
	// --- ZERO STATE ---
	echo '<div class="db-card mt-4">
			<div class="db-card-body text-center" style="padding: 100px;">
				<div style="width: 100px; height: 100px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 30px;">
					<i class="fas fa-inventory fa-4x"></i>
				</div>
				<h2 class="db-font-bold mb-2">' . __('Inventory Selection Hub') . '</h2>
				<p class="db-muted" style="max-width: 500px; margin: 0 auto 30px;">' . __('Search for an item in the sidebar to view detailed inventory statuses, inquiries, and maintenance options.') . '</p>
				<div class="db-badge db-badge-secondary">' . __('Search by Category, Model, or ID') . '</div>
			</div>
		  </div>';
} // End of Dashboard/Zero-State Dispatcher

echo '</main></div>'; // Close main and db-bottom-layout

include(__DIR__ . '/includes/footer.php');

/**
 * Code mostly generated by Gemini 2.0
 * Generates an SQL query for stockmaster data based on user-provided filters.
 *
 * The function constructs a SELECT query with JOINs to retrieve stock information,
 * including quantity on hand (qoh).  It supports filtering by keywords,
 * stock code, supplier stock code, and stock category.  The query is ordered
 * by discontinued status and stock ID.
 *
 * @param array $post An array containing user input, typically from $_POST.
 * Expected keys:
 * - 'Keywords':  String to search for in stock descriptions.
 * - 'StockCode': String to search for in stock IDs.
 * - 'SupplierStockCode': String to search for in supplier part numbers.
 * - 'StockCat':  Category ID to filter by, or 'All' for all categories.
 *
 * @return string The generated SQL query string.  Returns an empty string if
 * no valid search criteria are provided.
 */
function GenerateStockmasterQuery(array $post): string {

    // Helper function to sanitize and prepare search strings.
    function PrepareSearchString(string $InputString): string {
        $InputString = mb_strtoupper($InputString); // Consistent case for comparisons.
        return '%' . str_replace(' ', '%', $InputString) . '%'; // Add wildcards.
    }

    // Initialize the SQL query.
    $SQL = "SELECT stockmaster.stockid,
                   stockmaster.description,
                   stockmaster.longdescription,
                   SUM(locstock.quantity) AS qoh,
                   stockmaster.units,
                   stockmaster.mbflag,
                   stockmaster.discontinued,
                   stockmaster.decimalplaces
            FROM stockmaster ";

    // Common JOIN and WHERE clauses.
    $JoinsSQL = "";
    $WhereSQL = " WHERE stockmaster.stockid = locstock.stockid "; // Corrected initial where clause

    // Determine the filter and build the query.
    if (isset($post['Keywords']) && mb_strlen($post['Keywords']) > 0) {
        $SearchString = PrepareSearchString($post['Keywords']);
        $JoinsSQL .= "LEFT JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid
					LEFT JOIN locstock
						ON stockmaster.stockid = locstock.stockid "; // Added locstock to the join.
        $WhereSQL .= "AND stockmaster.description LIKE '$SearchString' ";
    } elseif (isset($post['StockCode']) && mb_strlen($post['StockCode']) > 0) {
        $SearchString = PrepareSearchString($post['StockCode']);
        $JoinsSQL .= "INNER JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid
					INNER JOIN locstock
						ON stockmaster.stockid = locstock.stockid "; //Added locstock join
        $WhereSQL .= "AND stockmaster.stockid LIKE '$SearchString' ";
    } elseif (isset($post['SupplierStockCode']) && mb_strlen($post['SupplierStockCode']) > 0) {
        $SearchString = PrepareSearchString($post['SupplierStockCode']);
        $JoinsSQL .= "INNER JOIN purchdata
						ON stockmaster.stockid = purchdata.stockid
					INNER JOIN locstock
						ON stockmaster.stockid = locstock.stockid
					LEFT JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid"; // Added locstock join
        $WhereSQL .= "AND purchdata.suppliers_partno LIKE '$SearchString' ";
    } else {
        $JoinsSQL .= "LEFT JOIN stockcategory
						ON stockmaster.categoryid = stockcategory.categoryid
					LEFT JOIN locstock
						ON stockmaster.stockid = locstock.stockid "; // Added locstock to the join.
    }

    // Category filter.
    if ($post['StockCat'] != 'All') {
        $WhereSQL .= "AND stockmaster.categoryid = '" . $post['StockCat'] . "' ";
    }

    // Complete the query.
    $SQL .= $JoinsSQL;
    $SQL .= $WhereSQL;
    $SQL .= "GROUP BY stockmaster.stockid,
                    stockmaster.description,
                    stockmaster.longdescription,
                    stockmaster.units,
                    stockmaster.mbflag,
                    stockmaster.discontinued,
                    stockmaster.decimalplaces
             ORDER BY stockmaster.discontinued,
			 		stockmaster.stockid";

    return $SQL;
}
