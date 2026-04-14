<?php

/* Inventory Transfer - Bulk Dispatch */

require(__DIR__ . '/includes/session.php');

$Title = __('Inventory Location Transfer Shipment');
$BookMark = "LocationTransfers";
$ViewTopic = "Inventory";
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

if (isset($_POST['Submit'])){
/*Trap any errors in input */

	$InputError = false; /*Start off hoping for the best */
	$TotalItems = 0;
	//Make sure this Transfer has not already been entered... aka one way around the refresh & insert new records problem
	$Result = DB_query("SELECT * FROM loctransfers WHERE reference='" . $_POST['Trf_ID'] . "'");
	if (DB_num_rows($Result)!=0){
		$InputError = true;
		$ErrorMessage = __('This transaction has already been entered') . '. ' . __('Please start over now') . '<br />';
		unset($_POST['submit']);
	}  else {
	  if ($_FILES['SelectedTransferFile']['name']) { //start file processing
	  	//initialize
	   	$InputError = false;
		$ErrorMessage='';
		//get file handle
		$FileHandle = fopen($_FILES['SelectedTransferFile']['tmp_name'], 'r');
		$TotalItems=0;
		//loop through file rows
		while ( ($MyRow = fgetcsv($FileHandle, 10000, ',')) !== false ) {

			if (count($MyRow) != 2){
				prnMsg(__('File contains') . ' '. count($MyRow) . ' ' . __('columns, but only 2 columns are expected. The comma separated file should have just two columns the first for the item code and the second for the quantity to transfer'),'error');
				fclose($FileHandle);
				include(__DIR__ . '/includes/footer.php');
				exit();
			}

			// cleanup the data (csv files often import with empty strings and such)
			$StockID='';
			$Quantity=0;
			for ($i=0; $i<count($MyRow);$i++) {
				switch ($i) {
					case 0:
						$StockID = trim(mb_strtoupper($MyRow[$i]));
						$Result = DB_query("SELECT COUNT(stockid) FROM stockmaster WHERE stockid='" . $StockID . "'");
						$StockIDCheck = DB_fetch_row($Result);
						if ($StockIDCheck[0]==0){
							$InputError = true;
							$ErrorMessage .= __('The part code entered of'). ' ' . $StockID . ' '. __('is not set up in the database') . '. ' . __('Only valid parts can be entered for transfers'). '<br />';
						}
						break;
					case 1:
						$Quantity = filter_number_format($MyRow[$i]);
						if (!is_numeric($Quantity)){
						   $InputError = true;
						   $ErrorMessage .= __('The quantity entered for'). ' ' . $StockID . ' ' . __('of') . $Quantity . ' '. __('is not numeric.') . __('The quantity entered for transfers is expected to be numeric');
						}
						break;
				} // end switch statement
				if ($_SESSION['ProhibitNegativeStock']==1){
					$InTransitQuantity = GetItemQtyInTransitFromLocation($StockID, $_POST['FromStockLocation']);
					// Only if stock exists at this location
					$Result = DB_query("SELECT quantity
										FROM locstock
										WHERE stockid='" . $StockID . "'
										AND loccode='".$_POST['FromStockLocation']."'");
					$CheckStockRow = DB_fetch_array($Result);
					if (($CheckStockRow['quantity']-$InTransitQuantity) < $Quantity){
						$InputError = true;
						$ErrorMessage .= __('The item'). ' ' . $StockID . ' ' . __('does not have enough stock available (') . ' ' . $CheckStockRow['quantity'] . ')' . ' ' . __('The quantity required to transfer was') .  ' ' . $Quantity . '.<br />';
					}
				}
			} // end for loop through the columns on the row being processed
			if ($StockID!='' AND $Quantity!=0){
				$_POST['StockID' . $TotalItems] = $StockID;
				$_POST['StockQTY' . $TotalItems] = $Quantity;
				$StockID='';
				$Quantity=0;
				$TotalItems++;
			}
		  } //end while there are lines in the CSV file
		  $_POST['LinesCounter']=$TotalItems;
	   } //end if there is a CSV file to import
		  else { // process the manually input lines
			$ErrorMessage='';

			if (isset($_POST['ClearAll'])){
				$_POST['LinesCounter'] = 0;
			}
			$StockIDAccQty = array(); //set an array to hold all items' quantity
			for ($i=0; $i < $_POST['LinesCounter']; $i++){
				if (isset($_POST['StockID' . $i]) AND $_POST['StockID' . $i]!=''){
					$_POST['StockID' . $i]=trim(mb_strtoupper($_POST['StockID' . $i]));
					$Result = DB_query("SELECT COUNT(stockid) FROM stockmaster WHERE stockid='" . $_POST['StockID' . $i] . "'");
					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0]==0){
						$InputError = true;
						$ErrorMessage .= __('The part code entered of'). ' ' . $_POST['StockID' . $i] . ' '. __('is not set up in the database') . '. ' . __('Only valid parts can be entered for transfers'). '<br />';
					}
					DB_free_result( $Result );
					if (!is_numeric(filter_number_format($_POST['StockQTY' . $i]))){
						$InputError = true;
						$ErrorMessage .= __('The quantity entered of'). ' ' . $_POST['StockQTY' . $i] . ' '. __('for part code'). ' ' . $_POST['StockID' . $i] . ' '. __('is not numeric') . '. ' . __('The quantity entered for transfers is expected to be numeric') . '<br />';
					}
					if (filter_number_format($_POST['StockQTY' . $i]) <= 0){
						$InputError = true;
						$ErrorMessage .= __('The quantity entered for').' '. $_POST['StockID' . $i] . ' ' . __('is less than or equal to 0') . '. ' . __('Please correct this or remove the item') . '<br />';
					}
					if ($_SESSION['ProhibitNegativeStock']==1){
						$InTransitQuantity = GetItemQtyInTransitFromLocation($_POST['StockID' . $i], $_POST['FromStockLocation']);
						// Only if stock exists at this location
						$Result = DB_query("SELECT quantity
											FROM locstock
											WHERE stockid='" . $_POST['StockID' . $i] . "'
											AND loccode='".$_POST['FromStockLocation']."'");

						$MyRow = DB_fetch_array($Result);
						if (($MyRow['quantity']-$InTransitQuantity) < filter_number_format($_POST['StockQTY' . $i])){
							$InputError = true;
							$ErrorMessage .= __('The part code entered of'). ' ' . $_POST['StockID' . $i] . ' '. __('does not have enough stock available for transfer.') . '.<br />';
						}
					}
					// Check the accumulated quantity for each item
					if (isset($StockIDAccQty[$_POST['StockID'.$i]])){
						$StockIDAccQty[$_POST['StockID'.$i]] += filter_number_format($_POST['StockQTY' . $i]);
						if ($MyRow[0] < $StockIDAccQty[$_POST['StockID'.$i]]){
							$InputError = true;
							$ErrorMessage .=__('The part code entered of'). ' ' . $_POST['StockID'.$i] . ' '.__('does not have enough stock available for transter due to accumulated quantity is over quantity on hand.') . '<br />';
						}
					} else {
						$StockIDAccQty[$_POST['StockID'.$i]] = filter_number_format($_POST['StockQTY' . $i]);
					} //end of accumulated check

					$TotalItems++;
				}
			}//for all LinesCounter
		}

		if ($TotalItems == 0){
			$InputError = true;
			$ErrorMessage .= __('You must enter at least 1 Stock Item to transfer') . '<br />';
		}

	/*Ship location and Receive location are different */
		if ($_POST['FromStockLocation']==$_POST['ToStockLocation']){
			$InputError=true;
			$ErrorMessage .= __('The transfer must have a different location to receive into and location sent from');
		}
	 } //end if the transfer is not a duplicated
}

if (isset($_POST['Submit']) AND $InputError==false){

	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('Unable to BEGIN Location Transfer transaction');

	DB_Txn_Begin();

	for ($i=0;$i < $_POST['LinesCounter'];$i++){

		if ($_POST['StockID' . $i] != ''){
			$DecimalsSql = "SELECT decimalplaces
							FROM stockmaster
							WHERE stockid='" . $_POST['StockID' . $i] . "'";
			$DecimalResult = DB_query($DecimalsSql);
			$DecimalRow = DB_fetch_array($DecimalResult);
			$SQL = "INSERT INTO loctransfers (reference,
								stockid,
								shipqty,
								shipdate,
								shiploc,
								recloc)
						VALUES ('" . $_POST['Trf_ID'] . "',
							'" . $_POST['StockID' . $i] . "',
							'" . round(filter_number_format($_POST['StockQTY' . $i]), $DecimalRow['decimalplaces']) . "',
							'" . date('Y-m-d H-i-s') . "',
							'" . $_POST['FromStockLocation']  ."',
							'" . $_POST['ToStockLocation'] . "')";
			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('Unable to enter Location Transfer record for'). ' '.$_POST['StockID' . $i];
			$ResultLocShip = DB_query($SQL, $ErrMsg);
		}
	}

	DB_Txn_Commit();

	prnMsg( __('The inventory transfer records have been created successfully'),'success');
	echo '<p><a href="'.$RootPath.'/PDFStockLocTransfer.php?TransferNo=' . $_POST['Trf_ID'] . '" target="_blank">' .  __('Print the Transfer Docket'). '</a></p>';
	include(__DIR__ . '/includes/footer.php');

} else {
	//Get next Inventory Transfer Shipment Reference Number
	if (isset($_GET['Trf_ID'])){
		$Trf_ID = $_GET['Trf_ID'];
	} elseif (isset($_POST['Trf_ID'])){
		$Trf_ID = $_POST['Trf_ID'];
	}

	if (!isset($Trf_ID)){
		$Trf_ID = GetNextTransNo(16);
	}

	echo '<div class="db-page">
			<div class="db-page-header">
				<div class="db-page-title">
					<i class="fas fa-shipping-fast"></i> ' . $Title . '
				</div>
				<div class="db-page-actions">
					<a href="' . $RootPath . '/StockLocTransfer.php" class="db-btn db-btn-outline db-btn-sm">
						<i class="fas fa-sync"></i> ' . __('Start Over') . '
					</a>
				</div>
			</div>';

	if (isset($InputError) and $InputError == true) {
		echo '<div class="db-status-bar db-status-danger" style="margin-bottom: 20px;">
				<div class="db-status-icon"><i class="fas fa-exclamation-circle"></i></div>
				<div class="db-status-text">' . $ErrorMessage . '</div>
			  </div>';
	}

	echo '<form enctype="multipart/form-data" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
	echo '<div class="db-grid db-grid-2">
			<!-- Card 1: Transfer Configuration -->
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-cog"></i> ' . __('Transfer Configuration') . '</div>
				</div>
				<div class="db-card-body">
					<input type="hidden" name="Trf_ID" value="' . $Trf_ID . '" />
					<div class="db-form-row" style="margin-bottom: 30px;">
						<div class="db-form-group">
							<label class="db-label" style="text-transform: uppercase; letter-spacing: 1px; opacity: 0.5; font-size: 0.7rem; font-weight: 700;">' . __('Transfer Reference') . '</label>
							<div class="db-badge db-badge-primary" style="font-size: 1.1rem; padding: 8px 16px; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; background: var(--db-primary-light); color: var(--db-primary); border: 1px solid rgba(var(--db-primary-rgb), 0.1);">
								<i class="fas fa-hashtag" style="font-size: 0.8rem; opacity: 0.6;"></i>
								<span style="font-family: monospace; font-weight: 700;">' . $Trf_ID . '</span>
							</div>
						</div>
					</div>
					
					<div style="display: flex; gap: 24px; margin-bottom: 5px; flex-wrap: nowrap;">
						<div class="db-form-group" style="flex: 1; min-width: 0;">
							<label class="db-label" style="margin-bottom: 8px; font-weight: 600; opacity: 0.8;">' . __('Source Warehouse') . '</label>
							<div class="db-input-wrapper">
								<i class="fas fa-warehouse db-input-icon" style="top: 13px;"></i>
								<select name="FromStockLocation" id="FromStockLocation" class="db-select db-input-light" style="padding-left: 40px; height: 44px; width: 100%;">Line 250: ';

	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1 ORDER BY locationname";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		$selected = (isset($_POST['FromStockLocation']) && $MyRow['loccode'] == $_POST['FromStockLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname']. '</option>';
	}
	echo '				</select>
							</div>
						</div>
						<div class="db-form-group" style="flex: 1; min-width: 0;">
							<label class="db-label" style="margin-bottom: 8px; font-weight: 600; opacity: 0.8;">' . __('Destination Warehouse') . '</label>
							<div class="db-input-wrapper">
								<i class="fas fa-map-marker-alt db-input-icon" style="top: 13px;"></i>
								<select name="ToStockLocation" id="ToStockLocation" class="db-select db-input-light" style="padding-left: 40px; height: 44px; width: 100%;">';

	DB_data_seek($ResultStkLocs, 0);
	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		$selected = (isset($_POST['ToStockLocation']) && $MyRow['loccode'] == $_POST['ToStockLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '				</select>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Card 2: Add Items -->
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Add Items to Transfer') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-form-row">
						<label class="db-label" style="margin-bottom: 10px; opacity: 0.6; font-size: 0.8rem;">' . __('Item Lookup & Quick Add') . '</label>
						<div style="display: flex; gap: 10px; align-items: flex-start;">
							<!-- Search Container -->
							<div class="db-search-container" style="flex: 1; position: relative;">
								<i class="fas fa-search" style="position: absolute; left: 15px; top: 13px; opacity: 0.4; z-index: 5;"></i>
								<input type="text" id="ItemSearch" class="db-input db-input-light" style="padding-left: 45px; height: 44px; border-radius: 6px 0 0 6px; border-right: none;" placeholder="' . __('Enter item code or name...') . '" autocomplete="off" />
								<div id="SearchResults" class="db-search-results"></div>
							</div>
							
							<!-- Quantity -->
							<div class="db-input-wrapper" style="width: 100px;">
								<input type="number" id="QuickQty" class="db-input db-input-light" style="text-align: center; height: 44px; border-radius: 0; border-right: none;" value="1" step="any" placeholder="Qty" />
							</div>
							
							<!-- Add Button -->
							<button type="button" id="AddItemBtn" class="db-btn db-btn-primary" style="height: 44px; padding: 0 20px; font-weight: 600; border-radius: 0 6px 6px 0; display: flex; align-items: center; gap: 8px;">
								<i class="fas fa-plus"></i> ' . __('Add Item') . '
							</button>
						</div>
					</div>
					<div id="PendingItemInfo" class="db-status-bar db-status-active" style="display: none; margin-top: 15px; border-radius: var(--db-radius-md);">
						<div class="db-status-icon"><i class="fas fa-barcode"></i></div>
						<div class="db-status-text" id="PendingItemText"></div>
					</div>
				</div>
			</div>
		</div> <!-- End Grid -->

		<!-- Card 3: Transfer List -->
		<div class="db-card" style="margin-top: 20px;">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Transfer List') . '</div>
			</div>
			<div class="db-card-body">
				<div class="table-responsive">
					<table class="db-table" id="TransferTable">
						<thead>
							<tr>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('Quantity') . '</th>
								<th class="text-center">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody id="TransferListBody">';
	
	// Pre-populate if post data exists
	$LinesCounter = 0;
	if (isset($_POST['LinesCounter'])){
		for ($i=0; $i < $_POST['LinesCounter']; $i++){
			if (isset($_POST['StockID' . $i]) && $_POST['StockID' . $i] != ''){
				echo '<tr data-index="' . $LinesCounter . '">
						<td>' . $_POST['StockID' . $i] . '<input type="hidden" name="StockID' . $LinesCounter . '" value="' . $_POST['StockID' . $i] . '" /></td>
						<td><small>' . __('Existing Item') . '</small></td>
						<td class="text-right">' . $_POST['StockQTY' . $i] . '<input type="hidden" name="StockQTY' . $LinesCounter . '" value="' . $_POST['StockQTY' . $i] . '" /></td>
						<td class="text-center">
							<button type="button" class="db-btn db-btn-outline db-btn-sm" onclick="removeRow(this)">
								<i class="fas fa-times" style="color: var(--db-danger);"></i>
							</button>
						</td>
					  </tr>';
				$LinesCounter++;
			}
		}
	}

	echo '				</tbody>
					</table>
				</div>
				<div id="EmptyState" style="' . ($LinesCounter > 0 ? 'display: none;' : '') . ' text-align: center; padding: 40px; color: var(--db-text-muted);">
					<i class="fas fa-box-open" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
					<p>' . __('No items added yet. Use the search above to start building your transfer.') . '</p>
				</div>
				<div class="db-form-actions" style="margin-top: 25px; border-top: 1px solid var(--db-border); padding-top: 25px;">
					<input type="hidden" name="LinesCounter" id="LinesCounter" value="' . $LinesCounter . '" />
					<div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
						<label class="db-checkbox-container" style="color: var(--db-text-muted); font-size: 0.9rem;">
							<input type="checkbox" name="ClearAll" />
							<span class="db-checkbox-label">' . __('Clear All on Submit') . '</span>
						</label>
						<button type="submit" name="Submit" class="db-btn db-btn-primary db-btn-lg" style="padding-left: 40px; padding-right: 40px; font-weight: 600;">
							<i class="fas fa-check-double"></i> ' . __('Complete Transfer') . '
						</button>
					</div>
				</div>
			</div>
		</div>

		<!-- Card 4: CSV Upload -->
		<details class="db-card" style="margin-top: 20px; border: 1px solid var(--db-border-light);">
			<summary class="db-card-header" style="cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: space-between; padding: 15px 20px;">
				<div class="db-card-title" style="font-size: 0.95rem; opacity: 0.8;"><i class="fas fa-file-csv"></i> ' . __('Bulk Upload via CSV') . '</div>
				<i class="fas fa-chevron-down" style="font-size: 0.8rem; opacity: 0.4;"></i>
			</summary>
			<div class="db-card-body" style="background: var(--db-bg-alt); border-top: 1px solid var(--db-border-light);">
				<div class="db-status-bar db-status-info" style="margin-bottom: 20px; font-size: 0.85rem;">
					<div class="db-status-icon"><i class="fas fa-question-circle"></i></div>
					<div class="db-status-text">' . __('Upload a comma separated file with two columns: [Item Code] and [Quantity].') . '</div>
				</div>
				<div class="db-form-row">
					<div class="db-form-group">
						<label class="db-label">' . __('Select File') . '</label>
						<input name="SelectedTransferFile" type="file" id="CSVFile" class="db-input db-input-light" style="padding: 10px;" />
					</div>
				</div>
			</div>
		</details>';

	echo '</form>
		</div>'; // End db-page

	echo '<script>
	let selectedItem = null;

	document.addEventListener("DOMContentLoaded", function() {
		const itemSearch = document.getElementById("ItemSearch");
		const searchResults = document.getElementById("SearchResults");
		const addItemBtn = document.getElementById("AddItemBtn");
		const quickQty = document.getElementById("QuickQty");
		const transferBody = document.getElementById("TransferListBody");
		const emptyState = document.getElementById("EmptyState");
		const linesCounter = document.getElementById("LinesCounter");

		itemSearch.addEventListener("input", function() {
			const query = this.value.trim();
			if (query.length < 2) {
				searchResults.style.display = "none";
				return;
			}

			fetch("StockSearch_Ajax.php?term=" + encodeURIComponent(query))
				.then(response => response.json())
				.then(data => {
					searchResults.innerHTML = "";
					if (data.length > 0) {
						data.forEach(item => {
							const div = document.createElement("div");
							div.className = "db-search-item";
							div.innerHTML = `<strong>${item.id}</strong> - ${item.description}`;
							div.onclick = function() {
								selectedItem = item;
								itemSearch.value = item.id;
								document.getElementById("PendingItemText").innerHTML = `<strong>${item.id}</strong>: ${item.description}`;
								document.getElementById("PendingItemInfo").style.display = "flex";
								searchResults.style.display = "none";
								quickQty.focus();
							};
							searchResults.appendChild(div);
						});
						searchResults.style.display = "block";
					} else {
						searchResults.style.display = "none";
					}
				});
		});

		addItemBtn.addEventListener("click", function() {
			if (!selectedItem) {
				alert("' . __('Please select an item first') . '");
				itemSearch.focus();
				return;
			}
			
			const qty = parseFloat(quickQty.value);
			if (isNaN(qty) || qty <= 0) {
				alert("' . __('Please enter a valid quantity') . '");
				quickQty.focus();
				return;
			}

			const idx = parseInt(linesCounter.value);
			const row = document.createElement("tr");
			row.innerHTML = `
				<td>${selectedItem.id}<input type="hidden" name="StockID${idx}" value="${selectedItem.id}" /></td>
				<td>${selectedItem.description}</td>
				<td class="text-right">${qty}<input type="hidden" name="StockQTY${idx}" value="${qty}" /></td>
				<td class="text-center">
					<button type="button" class="db-btn db-btn-outline db-btn-sm" onclick="removeRow(this)">
						<i class="fas fa-times" style="color: var(--db-danger);"></i>
					</button>
				</td>
			`;
			
			transferBody.appendChild(row);
			linesCounter.value = idx + 1;
			emptyState.style.display = "none";

			// Reset
			selectedItem = null;
			itemSearch.value = "";
			quickQty.value = "1";
			document.getElementById("PendingItemInfo").style.display = "none";
			itemSearch.focus();
		});

		document.addEventListener("click", function(e) {
			if (!itemSearch.contains(e.target) && !searchResults.contains(e.target)) {
				searchResults.style.display = "none";
			}
		});
	});

	function removeRow(btn) {
		const row = btn.closest("tr");
		row.remove();
		renumberRows();
	}

	function renumberRows() {
		const rows = document.querySelectorAll("#TransferListBody tr");
		const counter = document.getElementById("LinesCounter");
		rows.forEach((row, i) => {
			const idInput = row.querySelector("input[name^=\'StockID\']");
			const qtyInput = row.querySelector("input[name^=\'StockQTY\']");
			idInput.name = `StockID${i}`;
			qtyInput.name = `StockQTY${i}`;
		});
		counter.value = rows.length;
		if (rows.length === 0) {
			document.getElementById("EmptyState").style.display = "block";
		}
	}
	</script>';
	include(__DIR__ . '/includes/footer.php');
}
