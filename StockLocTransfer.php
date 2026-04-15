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
				<div class="db-header-left">
					<div class="db-page-title">
						<i class="fas fa-shipping-fast"></i> ' . $Title . '
					</div>
					<div class="db-page-subtitle">' . __('Bulk stock shipment between warehouse locations') . '</div>
				</div>
				<div class="db-header-actions">
					<a href="' . $RootPath . '/StockLocTransfer.php" class="db-btn db-btn-secondary">
						<i class="fas fa-sync"></i> ' . __('Reset Form') . '
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
	echo '<div class="db-bottom-layout">
			<aside class="db-col-aside">';
	
	// Card 1: Transfer Configuration
	echo '			<div class="db-card">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-cog"></i> ' . __('Configuration') . '</div>
						</div>
						<div class="db-card-body">
							<input type="hidden" name="Trf_ID" value="' . $Trf_ID . '" />
							<div class="db-form-group" style="margin-bottom: 20px;">
								<label class="db-label" style="text-transform: uppercase; font-size: 0.7rem; opacity: 0.6;">' . __('Ref #') . '</label>
								<div class="db-badge db-badge-primary" style="font-family: monospace; font-size: 1rem; width: 100%; justify-content: center;">#' . $Trf_ID . '</div>
							</div>
							
							<div class="db-form-group">
								<label class="db-label">' . __('From Location') . ':</label>
								<select name="FromStockLocation" id="FromStockLocation" class="db-select">';

	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1 ORDER BY locationname";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		$selected = (isset($_POST['FromStockLocation']) && $MyRow['loccode'] == $_POST['FromStockLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname']. '</option>';
	}
	echo '						</select>
							</div>

							<div class="db-form-group" style="margin-top: 15px;">
								<label class="db-label">' . __('To Location') . ':</label>
								<select name="ToStockLocation" id="ToStockLocation" class="db-select">';

	DB_data_seek($ResultStkLocs, 0);
	while ($MyRow=DB_fetch_array($ResultStkLocs)){
		$selected = (isset($_POST['ToStockLocation']) && $MyRow['loccode'] == $_POST['ToStockLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '						</select>
							</div>
						</div>
					</div>';

	// Card 2: Add Items
	echo '			<div class="db-card" style="margin-top: 24px;">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Add Items') . '</div>
						</div>
						<div class="db-card-body">
							<div class="db-form-group">
								<label class="db-label">' . __('Search Inventory') . ':</label>
								<div style="position: relative;">
									<input type="text" id="ItemSearch" class="db-input" placeholder="' . __('Code or name...') . '" autocomplete="off" />
									<div id="SearchResults" class="db-search-results" style="display: none; position: absolute; z-index: 1000; top: 100%; left: 0; right: 0; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-sm); box-shadow: var(--shadow-lg); max-height: 250px; overflow-y: auto;"></div>
								</div>
							</div>
							
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px;">
								<div class="db-form-group">
									<label class="db-label">' . __('Qty') . ':</label>
									<input type="number" id="QuickQty" class="db-input" style="text-align: center;" value="1" step="any" />
								</div>
								<div style="display: flex; align-items: flex-end;">
									<button type="button" id="AddItemBtn" class="db-btn db-btn-primary" style="width: 100%; height: 42px; justify-content: center;">
										<i class="fas fa-plus"></i> ' . __('Add') . '
									</button>
								</div>
							</div>

							<div id="PendingItemInfo" class="db-alert db-alert-info" style="display: none; margin-top: 16px; padding: 10px; font-size: 0.8rem;">
								<div id="PendingItemText" style="word-break: break-all;"></div>
							</div>
						</div>
					</div>
				</aside>


				<main class="db-col-main">

		<!-- Card 3: Transfer List -->
		<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-header">
				<div class="db-card-title">
					<div style="display: flex; align-items: center; gap: 10px;">
						<i class="fas fa-list-ul"></i> ' . __('Transfer List') . '
					</div>
					<span class="db-badge" id="ItemCountBadge" style="font-size: 0.7rem;">' . $LinesCounter . ' ' . __('Items') . '</span>
				</div>
			</div>
			<div class="db-card-body">
				<div class="table-responsive">
					<table class="db-table" id="TransferTable">
						<thead>
							<tr>
								<th style="width: 150px;">' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right" style="width: 100px;">' . __('Qty') . '</th>
								<th class="text-center" style="width: 80px;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody id="TransferListBody">';

	
	// Pre-populate if post data exists
	$LinesCounter = 0;
	if (isset($_POST['LinesCounter'])){
		for ($i=0; $i < $_POST['LinesCounter']; $i++){
			if (isset($_POST['StockID' . $i]) && $_POST['StockID' . $i] != ''){
				echo '<tr data-index="' . $LinesCounter . '">
						<td class="db-font-bold">' . $_POST['StockID' . $i] . '<input type="hidden" name="StockID' . $LinesCounter . '" value="' . $_POST['StockID' . $i] . '" /></td>
						<td class="db-text-muted"><small>' . __('Imported or manual entry') . '</small></td>
						<td class="text-right db-font-bold text-primary">' . $_POST['StockQTY' . $i] . '<input type="hidden" name="StockQTY' . $LinesCounter . '" value="' . $_POST['StockQTY' . $i] . '" /></td>
						<td class="text-center">
							<button type="button" class="db-btn db-btn-sm db-btn-danger" onclick="removeRow(this)">
								<i class="fas fa-trash-alt"></i> ' . __('Remove') . '
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
				<div id="EmptyState" style="' . ($LinesCounter > 0 ? 'display: none;' : '') . ' text-align: center; padding: 100px 40px; border: 2px dashed var(--border); border-radius: var(--radius-md); margin: 20px;">
					<div style="width: 64px; height: 64px; background: var(--bg-workspace); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
						<i class="fas fa-box-open" style="font-size: 2rem; opacity: 0.3;"></i>
					</div>
					<h3 class="db-font-bold" style="color: var(--text-main); margin-bottom: 8px;">' . __('List is empty') . '</h3>
					<p style="max-width: 250px; margin: 0 auto; line-height: 1.5;">' . __('Start by adding items from the sidebar or using bulk upload.') . '</p>
				</div>
			</div>

			
			<div class="db-card-footer" style="padding: 20px; background: var(--surface-alt); border-top: 1px solid var(--border-soft);">
				<div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
					<label class="db-checkbox-container" style="color: var(--text-muted); font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 8px;">
						<input type="checkbox" name="ClearAll" />
						<span>' . __('Clear all items after successful transfer') . '</span>
					</label>
					<input type="hidden" name="LinesCounter" id="LinesCounter" value="' . $LinesCounter . '" />
					<button type="submit" name="Submit" class="db-btn db-btn-primary" style="padding: 12px 30px; font-weight: 700;">
						<i class="fas fa-check-double"></i> ' . __('Execute Bulk Shipment') . '
					</button>
				</div>
			</div>
		</div>

		<div class="db-card" style="margin-top: 24px;">
			<div class="db-card-header" style="background: var(--surface-alt);">
				<div class="db-card-title" style="font-size: 0.85rem;"><i class="fas fa-file-csv"></i> ' . __('CSV Bulk Import') . '</div>
			</div>
			<div class="db-card-body" style="padding: 24px;">
				<div style="display: flex; gap: 24px; align-items: flex-end; flex-wrap: wrap;">
					<div style="flex: 1; min-width: 250px;">
						<label class="db-label" style="margin-bottom: 8px;">' . __('Select CSV File') . ':</label>
						<div style="position: relative;">
							<input name="SelectedTransferFile" type="file" id="CSVFile" class="db-input" style="padding: 8px 12px; font-size: 0.85rem;" />
						</div>
						<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">
							<i class="fas fa-info-circle"></i> ' . __('Required format') . ': <code>[Item Code], [Quantity]</code>
						</div>
					</div>
					<button type="submit" name="Submit" class="db-btn db-btn-secondary" style="height: 42px;">
						<i class="fas fa-upload"></i> ' . __('Upload Items') . '
					</button>
				</div>
			</div>
		</div>

	</main>
</div>';

echo '<style>
.db-btn-danger {
	background: #ef4444;
	color: #ffffff;
	border-color: #ef4444;
}
.db-btn-danger:hover {
	background: #dc2626;
	border-color: #dc2626;
	color: #ffffff;
	box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
	transform: translateY(-1px);
}
.db-search-item {
	padding: 12px 16px;
	cursor: pointer;
	border-bottom: 1px solid var(--border-soft);
	transition: all 0.2s;
}
.db-search-item:hover {
	background: var(--primary-soft);
	padding-left: 20px;
}
</style>';






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
				<td class="db-font-bold">${selectedItem.id}<input type="hidden" name="StockID${idx}" value="${selectedItem.id}" /></td>
				<td>${selectedItem.description}</td>
				<td class="text-right db-font-bold text-primary">${qty}<input type="hidden" name="StockQTY${idx}" value="${qty}" /></td>
				<td class="text-center">
					<button type="button" class="db-btn db-btn-sm db-btn-danger" onclick="removeRow(this)">
						<i class="fas fa-trash-alt"></i> ' . __('Remove') . '
					</button>
				</td>
			`;






			
			transferBody.appendChild(row);
			linesCounter.value = idx + 1;
			document.getElementById("ItemCountBadge").innerText = `${idx + 1} ' . __('Items') . '`;
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
		document.getElementById("ItemCountBadge").innerText = `${rows.length} ' . __('Items') . '`;
		if (rows.length === 0) {
			document.getElementById("EmptyState").style.display = "block";
		}
	}

	</script>';
	include(__DIR__ . '/includes/footer.php');
}
