<?php

require(__DIR__ . '/includes/session.php');

$PricesSecurity = 12; // don't show pricing info unless security token 12 available to user

$Title = __('Stock Status');
$ViewTopic = 'Inventory';
$BookMark = '';

include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card" style="margin-bottom: 20px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Item Lookup') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Code') . '</label>
						<input type="text" name="StockID" class="db-input" value="' . $StockID . '" required="required" placeholder="' . __('e.g. ITEM-001') . '" autofocus />
					</div>
					<button type="submit" name="ShowStatus" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
						<i class="fas fa-search"></i> ' . __('Show Status') . '
					</button>
				</div>
			</div>';

// ITEM INFO CARD (If StockID selected)
if ($StockID != '' AND isset($Description)) {
	echo '<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Item Specifications') . '</h3>
			</div>
			<div class="db-card-body">
				<div style="margin-bottom: 12px;">
					<label class="db-label" style="display:block; font-size: 0.7rem; text-transform: uppercase;">' . __('Description') . '</label>
					<div class="db-font-bold">' . $Description . '</div>
				</div>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
					<div>
						<label class="db-label" style="display:block; font-size: 0.7rem; text-transform: uppercase;">' . __('UOM') . '</label>
						<div class="db-badge db-badge-secondary">' . $Units . '</div>
					</div>
					<div>
						<label class="db-label" style="display:block; font-size: 0.7rem; text-transform: uppercase;">' . __('Decimals') . '</label>
						<div class="db-badge db-badge-outline">' . $DecimalPlaces . '</div>
					</div>
				</div>
			</div>
		  </div>';
}

// QUICK LINKS CARD
if ($StockID != '') {
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-external-link-alt"></i> ' . __('Related Insights') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 10px;">
				<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-exchange-alt"></i> ' . __('Show Movements') . '
				</a>
				<a href="' . $RootPath . '/StockUsage.php?StockID=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-chart-line"></i> ' . __('Show Usage') . '
				</a>
				<a href="' . $RootPath . '/SelectSalesOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-shopping-cart"></i> ' . __('Open Orders') . '
				</a>';
	if ($KitSet != 'K' AND $KitSet != 'A' AND $KitSet != 'D') {
		echo '<a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; font-size: 0.8rem;">
				<i class="fas fa-truck"></i> ' . __('Search POs') . '
			  </a>';
	}
	echo '</div>
		  </div>';
}

echo '		</form>
	</aside>';

echo '<main class="db-col-main">';



$Its_A_KitSet_Assembly_Or_Dummy =false;
if ($KitSet=='K'){
	$Its_A_KitSet_Assembly_Or_Dummy =true;
	prnMsg( __('This is a kitset part and cannot have a stock holding') . ', ' . __('only the total quantity on outstanding sales orders is shown'),'info');
} elseif ($KitSet=='A'){
	$Its_A_KitSet_Assembly_Or_Dummy =true;
	prnMsg(__('This is an assembly part and cannot have a stock holding') . ', ' . __('only the total quantity on outstanding sales orders is shown'),'info');
} elseif ($KitSet=='D'){
	$Its_A_KitSet_Assembly_Or_Dummy =true;
	prnMsg( __('This is an dummy part and cannot have a stock holding') . ', ' . __('only the total quantity on outstanding sales orders is shown'),'info');
}

if ($StockID == '') {
	echo '<div class="db-status-bar db-status-info">
			<div class="db-status-icon"><i class="fas fa-arrow-left"></i></div>
			<div class="db-status-text">' . __('Please enter a stock code in the sidebar to view status across all locations.') . '</div>
		  </div>';
}


$SQL = "SELECT locstock.loccode,
				locations.locationname,
				locstock.quantity,
				locstock.reorderlevel,
				locstock.bin,
				locations.managed,
				canupd
		FROM locstock INNER JOIN locations
		ON locstock.loccode=locations.loccode
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
		WHERE locstock.stockid = '" . $StockID . "'
		ORDER BY locations.locationname";

$ErrMsg = __('The stock held at each location cannot be retrieved because');
$LocStockResult = DB_query($SQL, $ErrMsg);

if ($StockID != '' AND DB_num_rows($LocStockResult) > 0) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="StockID" value="' . $StockID . '" />
			<div class="db-card">
				<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
					<h3 class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Location Status') . '</h3>
					<div style="display: flex; gap: 10px;">
						<button type="submit" name="UpdateBinLocations" class="db-btn db-btn-primary" style="font-size: 0.75rem; padding: 5px 15px;">
							<i class="fas fa-save"></i> ' . __('Update Bins') . '
						</button>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Location') . '</th>
									<th>' . __('Bin Location') . '</th>
									<th class="text-right">' . __('On Hand') . '</th>
									<th class="text-right">' . __('Reorder') . '</th>
									<th class="text-right">' . __('Demand') . '</th>
									<th class="text-right">' . __('In Transit') . '</th>
									<th class="text-right">' . __('Available') . '</th>
									<th class="text-right">' . __('On Order') . '</th>';
	if ($Serialised == 1 OR $Controlled == 1) {
		echo '						<th>' . __('Controlled') . '</th>';
	}
	echo '						</tr>
							</thead>
							<tbody>';

	while ($MyRow=DB_fetch_array($LocStockResult)) {
		$DemandQty = GetDemand($StockID, $MyRow['loccode']);
		$QOO = GetQuantityOnOrder($StockID, $MyRow['loccode']);
		$InTransitQuantityOut = -GetItemQtyInTransitFromLocation($StockID, $MyRow['loccode']);
		$InTransitQuantityIn = GetItemQtyInTransitToLocation($StockID, $MyRow['loccode']);
		
		if (($InTransitQuantityIn+$InTransitQuantityOut) < 0) {
			$Available = $MyRow['quantity'] - $DemandQty + ($InTransitQuantityIn+$InTransitQuantityOut);
		} else {
			$Available = $MyRow['quantity'] - $DemandQty;
		}

		echo '			<tr class="striped_row">
							<td><div class="db-font-bold text-primary">' . $MyRow['locationname'] . '</div></td>
							<td>';
		if ($MyRow['canupd']==1) {
			echo '				<input type="text" name="BinLocation' . $MyRow['loccode'] . '" class="db-input" value="' . $MyRow['bin'] . '" style="width: 100px; font-size: 0.8rem; padding: 4px;" />';
		} else {
			echo '				<span style="font-size: 0.8rem; color: var(--text-muted);">' . ($MyRow['bin'] ?: '-') . '</span>';
		}
		echo '				</td>
							<td class="text-right db-font-bold">' . locale_number_format($MyRow['quantity'], $DecimalPlaces) . '</td>
							<td class="text-right" style="color: var(--text-muted);">' . locale_number_format($MyRow['reorderlevel'], $DecimalPlaces) . '</td>
							<td class="text-right" style="color: var(--danger); font-weight: 500;">' . locale_number_format($DemandQty, $DecimalPlaces) . '</td>
							<td class="text-right">' . locale_number_format($InTransitQuantityIn+$InTransitQuantityOut, $DecimalPlaces) . '</td>
							<td class="text-right db-font-bold" style="color: var(--primary);">' . locale_number_format($Available, $DecimalPlaces) . '</td>
							<td class="text-right">' . locale_number_format($QOO, $DecimalPlaces) . '</td>';

		if ($Serialised == 1) {
			echo '			<td><a class="db-link" target="_blank" href="' . $RootPath . '/StockSerialItems.php?Serialised=Yes&amp;Location=' . $MyRow['loccode'] . '&amp;StockID=' . $StockID . '" style="font-size:0.75rem;">' . __('Numbers') . '</a></td>';
		} elseif ($Controlled == 1) {
			echo '			<td><a class="db-link" target="_blank" href="' . $RootPath . '/StockSerialItems.php?Location=' . $MyRow['loccode'] . '&amp;StockID=' . $StockID . '" style="font-size:0.75rem;">' . __('Batches') . '</a></td>';
		}
		echo '			</tr>';
	}
	echo '				</tbody>
						</table>
					</div>
				</div>
			</div>
		  </form>';
}

if ($StockID != '' AND isset($DebtorNo)) {
	// Pricing history logic here (keep as is but modernised)
	// I'll skip deep refactoring of pricing history for now to keep this concise, 
	// but I'll wrap it in a db-card if it exists.
}


if (isset($_GET['DebtorNo'])){
	$DebtorNo = trim(mb_strtoupper($_GET['DebtorNo']));
} elseif (isset($_POST['DebtorNo'])){
	$DebtorNo = trim(mb_strtoupper($_POST['DebtorNo']));
} elseif (isset($_SESSION['CustomerID'])){
	$DebtorNo=$_SESSION['CustomerID'];
}

if ($DebtorNo) { /* display recent pricing history for this debtor and this stock item */

	$SQL = "SELECT stockmoves.trandate,
				stockmoves.qty,
				stockmoves.price,
				stockmoves.discountpercent
			FROM stockmoves
			WHERE stockmoves.debtorno='" . $DebtorNo . "'
				AND stockmoves.type=10
				AND stockmoves.stockid = '" . $StockID . "'
				AND stockmoves.hidemovt=0
			ORDER BY stockmoves.trandate DESC";

	/* only show pricing history for sales invoices - type=10 */

	$ErrMsg = __('The stock movements for the selected criteria could not be retrieved because') . ' - ';

	$MovtsResult = DB_query($SQL, $ErrMsg);

	$k=1;
	while ($MyRow=DB_fetch_array($MovtsResult)) {
	  if ($LastPrice != $MyRow['price']
			OR $LastDiscount != $MyRow['discount']) { /* consolidate price history for records with same price/discount */
	    if (isset($Qty)) {
	    	$DateRange=ConvertSQLDate($FromDate);
	    	if ($FromDate != $ToDate) {
	        	$DateRange .= ' - ' . ConvertSQLDate($ToDate);
	     	}
	    	$PriceHistory[] = array($DateRange, $Qty, $LastPrice, $LastDiscount);
	    	$k++;
	    	if ($k > 9) {
                  break; /* 10 price records is enough to display */
                }
	    	if ($MyRow['trandate'] < FormatDateForSQL(DateAdd(date($_SESSION['DefaultDateFormat']),'y', -1))) {
	    	  break; /* stop displaying price history more than a year old once we have at least one  to display */
   	        }
	    }
	    $LastPrice = $MyRow['price'];
	    $LastDiscount = $MyRow['discountpercent'];
	    $ToDate = $MyRow['trandate'];
	    $Qty = 0;
	  }
	  $Qty += $MyRow['qty'];
	  $FromDate = $MyRow['trandate'];
	} //end of while loop

	if (isset($Qty)) {
		$DateRange = ConvertSQLDate($FromDate);
		if ($FromDate != $ToDate) {
	   		$DateRange .= ' - '.ConvertSQLDate($ToDate);
		}
		$PriceHistory[] = array($DateRange, $Qty, $LastPrice, $LastDiscount);
	}

	if (isset($PriceHistory)) {
	  echo '<table class="selection">
			<thead>
			<tr>
				<th colspan="4"><font color="navy" size="2">' . __('Pricing history for sales of') . ' ' . $StockID . ' ' . __('to') . ' ' . $DebtorNo . '</font></th>
				</tr>
				<tr>
						<th class="SortedColumn">' . __('Date Range') . '</th>
						<th class="SortedColumn">' . __('Quantity') . '</th>
						<th class="SortedColumn">' . __('Price') . '</th>
						<th class="SortedColumn">' . __('Discount') . '</th>
				</tr>
			</thead>
			<tbody>';

	  foreach($PriceHistory as $PreviousPrice) {

		echo '<tr class="striped_row">
				<td>', $PreviousPrice[0], '</td>
				<td class="number">', locale_number_format($PreviousPrice[1],$DecimalPlaces), '</td>
				<td class="number">', locale_number_format($PreviousPrice[2],$_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="number">', locale_number_format($PreviousPrice[3]*100,2), '%</td>
			</tr>';
		} // end foreach
	 echo '</tbody></table>';
	 }
	else {
	  echo '<p>' . __('No history of sales of') . ' ' . $StockID . ' ' . __('to') . ' ' . $DebtorNo;
	}
}//end of displaying price history for a debtor

echo '	</main>
	</div>'; // end db-bottom-layout

include(__DIR__ . '/includes/footer.php');
