<?php

/* Inventory Transfer - Receive */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineSerialItems.php');
include(__DIR__ . '/includes/DefineStockTransfers.php');

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$Title = __('Inventory Transfer') . ' - ' . __('Receiving');
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page-header">
		<div class="db-page-header-icon"><i class="fas fa-dolly-flatbed"></i></div>
		<div class="db-page-header-content">
			<div class="db-page-header-title">' . $Title . '</div>
			<div class="db-page-header-subtitle">' . __('Manage and process incoming stock shipments from other locations.') . '</div>
		</div>
	</div>';

if (isset($_GET['NewTransfer'])) {
	unset($_SESSION['Transfer']);
}
if (isset($_SESSION['Transfer']) and $_SESSION['Transfer']->TrfID == '') {
	unset($_SESSION['Transfer']);
}


if (isset($_POST['ProcessTransfer'])) {
	/*Ok Time To Post transactions to Inventory Transfers, and Update Posted variable & received Qty's  to LocTransfers */

	$PeriodNo = GetPeriod($_SESSION['Transfer']->TranDate);
	$SQLTransferDate = FormatDateForSQL($_SESSION['Transfer']->TranDate);

	$InputError = false; /*Start off hoping for the best */
	$i = 0;
	$TotalQuantity = 0;
	foreach ($_SESSION['Transfer']->TransferItem AS $TrfLine) {
		if (is_numeric(filter_number_format($_POST['Qty' . $i]))) {
		/*Update the quantity received from the inputs */
			$_SESSION['Transfer']->TransferItem[$i]->Quantity = round(filter_number_format($_POST['Qty' . $i]), $_SESSION['Transfer']->TransferItem[$i]->DecimalPlaces);
		} elseif ($_POST['Qty' . $i] == '') {
			$_SESSION['Transfer']->TransferItem[$i]->Quantity = 0;
		} else {
			prnMsg(__('The quantity entered for') . ' ' . $TrfLine->StockID . ' ' . __('is not numeric') . '. ' .
				   __('All quantities must be numeric'), 'error');
			$InputError = true;
		}
		if (filter_number_format($_POST['Qty' . $i]) < 0) {
			prnMsg(__('The quantity entered for') . ' ' . $TrfLine->StockID . ' ' . __('is negative') . '. ' .
				   __('All quantities must be for positive numbers greater than zero'), 'error');
			$InputError = true;
		}
		if ($TrfLine->PrevRecvQty + $TrfLine->Quantity > $TrfLine->ShipQty) {
			prnMsg(__('The Quantity entered plus the Quantity Previously Received can not be greater than the Total Quantity shipped for') .
				   ' ' . $TrfLine->StockID, 'error');
			$InputError = true;
		}
		if (isset($_POST['CancelBalance' . $i]) and $_POST['CancelBalance' . $i] == 1) {
			$_SESSION['Transfer']->TransferItem[$i]->CancelBalance = 1;
		} else {
			 $_SESSION['Transfer']->TransferItem[$i]->CancelBalance = 0;
		}
		$TotalQuantity += $TrfLine->Quantity;
		$i++;
	} /*end loop to validate and update the SESSION['Transfer'] data */
	if ($TotalQuantity < 0) {
		prnMsg(__('All quantities entered are less than zero') . '. ' . __('Please correct that and try again'), 'error');
		$InputError = true;
	}

	if (!$InputError) {
		/*All inputs must be sensible so make the stock movement records and update the locations stocks */

		DB_Txn_Begin(); // The Txn should affect the full transfer

		foreach ($_SESSION['Transfer']->TransferItem AS $TrfLine) {
			if ($TrfLine->Quantity >= 0) {
				/* Need to get the current location quantity will need it later for the stock movement */
				$SQL = "SELECT locstock.quantity
						FROM locstock
						WHERE locstock.stockid='" . $TrfLine->StockID . "'
							AND loccode= '" . $_SESSION['Transfer']->StockLocationFrom . "'";

				$Result = DB_query($SQL, __('Could not retrieve the stock quantity at the dispatch stock location prior to this transfer being processed'));
				if (DB_num_rows($Result) == 1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					/* There must actually be some error this should never happen */
					$QtyOnHandPrior = 0;
				}

				/* Insert the stock movement for the stock going out of the from location */
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												userid,
												prd,
												reference,
												qty,
												newqoh)
					VALUES (
						'" . $TrfLine->StockID . "',
						16,
						'" . $_SESSION['Transfer']->TrfID . "',
						'" . $_SESSION['Transfer']->StockLocationFrom . "',
						'" . $SQLTransferDate . "',
						'" . $_SESSION['UserID'] . "',
						'" . $PeriodNo . "',
						'" . __('To') . ' ' . DB_escape_string($_SESSION['Transfer']->StockLocationToName) . "',
						'" . round(-$TrfLine->Quantity, $TrfLine->DecimalPlaces) . "',
						'" . round($QtyOnHandPrior - $TrfLine->Quantity, $TrfLine->DecimalPlaces) . "'
					)";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
						  __('The stock movement record cannot be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/*Get the ID of the StockMove... */
				$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

				/*Insert the StockSerialMovements and update the StockSerialItems  for controlled items*/
				if ($TrfLine->Controlled == 1) {
					foreach($TrfLine->SerialItems as $Item) {
						/*We need to add or update the StockSerialItem record and the StockSerialMoves as well */
						/*First need to check if the serial items already exists or not in the location from */
						$SQL = "SELECT COUNT(*)
								FROM stockserialitems
								WHERE stockid = '" . $TrfLine->StockID . "'
									AND loccode='" . $_SESSION['Transfer']->StockLocationFrom . "'
									AND serialno='" . $Item->BundleRef . "'";

						$Result = DB_query($SQL, __('Could not determine if the serial item exists'));
						$SerialItemExistsRow = DB_fetch_row($Result);

						if ($SerialItemExistsRow[0] == 1) {

							$SQL = "UPDATE stockserialitems
									SET quantity= quantity - " . $Item->BundleQty . "
									WHERE stockid='" . $TrfLine->StockID . "'
										AND loccode='" . $_SESSION['Transfer']->StockLocationFrom . "'
										AND serialno='" . $Item->BundleRef . "'";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
									  __('The serial stock item record could not be updated because');
							$Result = DB_query($SQL, $ErrMsg, '', true);
						} else {
							/*Need to insert a new serial item record */
							$SQL = "INSERT INTO stockserialitems (stockid,
												loccode,
												serialno,
												quantity,
												qualitytext)
								VALUES ('" . $TrfLine->StockID . "',
								'" . $_SESSION['Transfer']->StockLocationFrom . "',
								'" . $Item->BundleRef . "',
								'" . -$Item->BundleQty . "',
								'')";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
									  __('The serial stock item for the stock being transferred out of the existing location could not be inserted because');
							$Result = DB_query($SQL, $ErrMsg, '', true);
						}

						/* now insert the serial stock movement */
						$SQL = "INSERT INTO stockserialmoves (
								stockmoveno,
								stockid,
								serialno,
								moveqty
							) VALUES (
								'" . $StkMoveNo . "',
								'" . $TrfLine->StockID . "',
								'" . $Item->BundleRef . "',
								'" . -$Item->BundleQty . "'
							)";
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
								  __('The serial stock movement record could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

					} /* foreach controlled item in the serialitems array */
				} /*end if the transferred item is a controlled item */


				/* Need to get the current location quantity will need it later for the stock movement */
				$SQL = "SELECT locstock.quantity
					FROM locstock
					WHERE locstock.stockid='" . $TrfLine->StockID . "'
						AND loccode= '" . $_SESSION['Transfer']->StockLocationTo . "'";

				$Result = DB_query($SQL, __('Could not retrieve the quantity on hand at the location being transferred to'));
				if (DB_num_rows($Result) == 1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					// There must actually be some error this should never happen
					$QtyOnHandPrior = 0;
				}

				// Insert outgoing inventory GL transaction if any of the locations has a GL account code:
				if (($_SESSION['Transfer']->StockLocationFromAccount != '' OR $_SESSION['Transfer']->StockLocationToAccount != '') AND
					($_SESSION['Transfer']->StockLocationFromAccount != $_SESSION['Transfer']->StockLocationToAccount)) {
					// Get the account code:
					if ($_SESSION['Transfer']->StockLocationFromAccount != '') {
						$AccountCode = $_SESSION['Transfer']->StockLocationFromAccount;
					} else {
						$StockGLCode = GetStockGLCode($TrfLine->StockID);// Get Category's account codes.
						$AccountCode = $StockGLCode['stockact'];// Select account code for stock.
					}
					// Get the item cost:
					$SQLstandardcost = "SELECT stockmaster.actualcost AS standardcost
										FROM stockmaster
										WHERE stockmaster.stockid ='" . $TrfLine->StockID . "'";
					$ErrMsg = __('The standard cost of the item cannot be retrieved because');
					$MyRow = DB_fetch_array(DB_query($SQLstandardcost, $ErrMsg));
					$StandardCost = $MyRow['standardcost'];// QUESTION: Standard cost for: Assembly (value="A") and Manufactured (value="M") items ?
					// Insert record:
					$SQL = "INSERT INTO gltrans (
							periodno,
							trandate,
							type,
							typeno,
							account,
							narrative,
							amount)
						VALUES ('" .
							$PeriodNo . "','" .
							$SQLTransferDate .
							"',16,'" .
							$_SESSION['Transfer']->TrfID . "','" .
							$AccountCode . "','" .
							mb_substr($_SESSION['Transfer']->StockLocationFrom . ' - ' . $TrfLine->StockID . ' x ' .
								$TrfLine->Quantity . ' @ ' . $StandardCost, 0, 200) . "','" .
							-$TrfLine->Quantity * $StandardCost . "')";
					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
							  __('The outgoing inventory GL transacction record could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

				// Insert the stock movement for the stock coming into the to location
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												userid,
												prd,
												reference,
												qty,
												newqoh)
					VALUES (
						'" . $TrfLine->StockID . "',
						16,
						'" . $_SESSION['Transfer']->TrfID . "',
						'" . $_SESSION['Transfer']->StockLocationTo . "',
						'" . $SQLTransferDate . "',
						'" . $_SESSION['UserID'] . "',
						'" . $PeriodNo . "',
						'" . __('From') . ' ' . DB_escape_string($_SESSION['Transfer']->StockLocationFromName) . "',
						'" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "',
						'" . round($QtyOnHandPrior + $TrfLine->Quantity, $TrfLine->DecimalPlaces) . "'
						)";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
						  __('The stock movement record for the incoming stock cannot be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/*Get the ID of the StockMove... */
				$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

				/*Insert the StockSerialMovements and update the StockSerialItems  for controlled items*/
				if ($TrfLine->Controlled == 1) {
					foreach($TrfLine->SerialItems as $Item) {
					/*We need to add or update the StockSerialItem record and the StockSerialMoves as well */

						/*First need to check if the serial items already exists or not in the location to */
						$SQL = "SELECT COUNT(*)
							FROM stockserialitems
							WHERE
							stockid='" . $TrfLine->StockID . "'
							AND loccode='" . $_SESSION['Transfer']->StockLocationTo . "'
							AND serialno='" . $Item->BundleRef . "'";

						$Result = DB_query($SQL, '<br />' . __('Could not determine if the serial item exists'));
						$SerialItemExistsRow = DB_fetch_row($Result);


						if ($SerialItemExistsRow[0] == 1) {

							$SQL = "UPDATE stockserialitems SET
								quantity= quantity + '" . $Item->BundleQty . "'
								WHERE
								stockid='" . $TrfLine->StockID . "'
								AND loccode='" . $_SESSION['Transfer']->StockLocationTo . "'
								AND serialno='" . $Item->BundleRef . "'";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
									  __('The serial stock item record could not be updated for the quantity coming in because');
							$Result = DB_query($SQL, $ErrMsg, '', true);
						} else {
							/*Need to insert a new serial item record */
							$SQL = "INSERT INTO stockserialitems (stockid,
											loccode,
											serialno,
											quantity,
											qualitytext)
								VALUES ('" . $TrfLine->StockID . "',
								'" . $_SESSION['Transfer']->StockLocationTo . "',
								'" . $Item->BundleRef . "',
								'" . $Item->BundleQty . "',
								'')";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
									  __('The serial stock item record for the stock coming in could not be added because');
							$Result = DB_query($SQL, $ErrMsg, '', true);
						}

						/* now insert the serial stock movement */
						$SQL = "INSERT INTO stockserialmoves (
											stockmoveno,
											stockid,
											serialno,
											moveqty)
								VALUES (" . $StkMoveNo . ",
									'" . $TrfLine->StockID . "',
									'" . $Item->BundleRef . "',
									'" . $Item->BundleQty . "')";
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
								  __('The serial stock movement record could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

					}/* foreach controlled item in the serialitems array */
				} /*end if the transfer item is a controlled item */

				$SQL = "UPDATE locstock
						SET quantity = quantity - '" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "'
						WHERE stockid='" . $TrfLine->StockID . "'
							AND loccode='" . $_SESSION['Transfer']->StockLocationFrom . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
						  __('The location stock record could not be updated because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$SQL = "UPDATE locstock
						SET quantity = quantity + '" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "'
						WHERE stockid='" . $TrfLine->StockID . "'
							AND loccode='" . $_SESSION['Transfer']->StockLocationTo . "'";

				$Result = DB_query($SQL, $ErrMsg, '', true);

				// Insert incoming inventory GL transaction if any of the locations has a GL account code:
				if (($_SESSION['Transfer']->StockLocationFromAccount != '' OR $_SESSION['Transfer']->StockLocationToAccount != '') AND
					($_SESSION['Transfer']->StockLocationFromAccount != $_SESSION['Transfer']->StockLocationToAccount)) {
					// Get the account code:
					if ($_SESSION['Transfer']->StockLocationToAccount != '') {
						$AccountCode = $_SESSION['Transfer']->StockLocationToAccount;
					} else {
						$StockGLCode = GetStockGLCode($TrfLine->StockID);// Get Category's account codes.
						$AccountCode = $StockGLCode['stockact'];// Select account code for stock.
					}
					// Get the item cost:
					$SQLstandardcost = "SELECT stockmaster.actualcost AS standardcost
										FROM stockmaster
										WHERE stockmaster.stockid ='" . $TrfLine->StockID . "'";
					$ErrMsg = __('The standard cost of the item cannot be retrieved because');
					$MyRow = DB_fetch_array(DB_query($SQLstandardcost, $ErrMsg));
					$StandardCost = $MyRow['standardcost'];// QUESTION: Standard cost for: Assembly (value="A") and Manufactured (value="M") items ?
					// Insert record:
					$SQL = "INSERT INTO gltrans (
							periodno,
							trandate,
							type,
							typeno,
							account,
							narrative,
							amount)
						VALUES ('" .
							$PeriodNo . "','" .
							$SQLTransferDate . "',
							16,'" .
							$_SESSION['Transfer']->TrfID . "','" .
							$AccountCode . "','" .
							mb_substr($_SESSION['Transfer']->StockLocationTo . ' - ' . $TrfLine->StockID . ' x ' .
								$TrfLine->Quantity . ' @ ' . $StandardCost, 0, 200) . "','" .
							$TrfLine->Quantity * $StandardCost . "')";
					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
							  __('The incoming inventory GL transacction record could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

				prnMsg(__('A stock transfer for item code') . ' - ' . $TrfLine->StockID . ' ' . $TrfLine->ItemDescription . ' ' .
					   __('has been created from') . ' ' . $_SESSION['Transfer']->StockLocationFromName . ' ' . __('to') . ' ' .
					   $_SESSION['Transfer']->StockLocationToName . ' ' . __('for a quantity of') . ' ' . $TrfLine->Quantity, 'success');

				if ($TrfLine->CancelBalance == 1) {
					RecordItemCancelledInTransfer($_SESSION['Transfer']->TrfID, $TrfLine->StockID, $TrfLine->Quantity);
					$SQL = "UPDATE loctransfers SET recqty = recqty + '" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "',
						shipqty = recqty + '" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "',
								recdate = '" . date('Y-m-d H:i:s') . "'
						WHERE reference = '" . $_SESSION['Transfer']->TrfID . "'
						AND stockid = '" . $TrfLine->StockID . "'";
				} else {
					$SQL = "UPDATE loctransfers SET recqty = recqty + '" . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) . "',
								recdate = '" . date('Y-m-d H:i:s') . "'
						WHERE reference = '" . $_SESSION['Transfer']->TrfID . "'
						AND stockid = '" . $TrfLine->StockID . "'";
				}
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('Unable to update the Location Transfer Record');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				unset($_SESSION['Transfer']->LineItem[$i]);
				unset($_POST['Qty' . $i]);
			} /*end if Quantity >= 0 */
			if ($TrfLine->CancelBalance == 1) {
				$SQL = "UPDATE loctransfers SET shipqty = recqty
						WHERE reference = '" . $_SESSION['Transfer']->TrfID . "'
						AND stockid = '" . $TrfLine->StockID . "'";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('Unable to set the quantity received to the quantity shipped to cancel the balance on this transfer line');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				// send an email to the inventory manager about this cancellation (as can lead to employee fraud)
				if ($_SESSION['InventoryManagerEmail']!='') {
					$ConfirmationText = __('Cancelled balance of transfer') . ': ' . $_SESSION['Transfer']->TrfID .
										"\r\n" . __('From Location') . ': ' . $_SESSION['Transfer']->StockLocationFrom .
										"\r\n" . __('To Location') . ': ' . $_SESSION['Transfer']->StockLocationTo .
										"\r\n" . __('Stock code') . ': ' . $TrfLine->StockID .
										"\r\n" . __('Qty received') . ': ' . round($TrfLine->Quantity, $TrfLine->DecimalPlaces) .
										"\r\n" . __('By user') . ': ' . $_SESSION['UserID'] .
										"\r\n" . __('At') . ': ' . date('Y-m-d H:i:s');
					$EmailSubject = __('Cancelled balance of transfer') . ' ' . $_SESSION['Transfer']->TrfID;
					SendEmailFromWebERP($SysAdminEmail,
										$_SESSION['InventoryManagerEmail'],
										$EmailSubject,
										$ConfirmationText,
										'',
										false);
				}
			}
			$i++;
		} /*end of foreach TransferItem */

		DB_Txn_Commit();

		unset($_SESSION['Transfer']->LineItem);
		unset($_SESSION['Transfer']);
	} /* end of if no input errors */

} /*end of PRocess Transfer */

if (isset($_GET['Trf_ID'])) {

	unset($_SESSION['Transfer']);

	$SQL = "SELECT loctransfers.stockid,
				stockmaster.description,
				stockmaster.units,
				stockmaster.controlled,
				stockmaster.serialised,
				stockmaster.perishable,
				stockmaster.decimalplaces,
				loctransfers.shipqty,
				loctransfers.recqty,
				locations.locationname as shiplocationname,
				locations.glaccountcode as shipaccountcode,
				reclocations.locationname as reclocationname,
				reclocations.glaccountcode as recaccountcode,
				loctransfers.shiploc,
				loctransfers.recloc
			FROM loctransfers INNER JOIN locations
			ON loctransfers.shiploc=locations.loccode
			INNER JOIN locations as reclocations
			ON loctransfers.recloc = reclocations.loccode
			INNER JOIN locationusers ON locationusers.loccode=reclocations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1
			INNER JOIN stockmaster
			ON loctransfers.stockid=stockmaster.stockid
			WHERE reference ='" . $_GET['Trf_ID'] . "' ORDER BY loctransfers.stockid";


	$ErrMsg = __('The details of transfer number') . ' ' . $_GET['Trf_ID'] . ' ' . __('could not be retrieved because') . ' ';
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0) {
		echo '<h3>' . __('Transfer') . ' #' . $_GET['Trf_ID'] . ' ' . __('Does Not Exist') . '</h3><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$MyRow = DB_fetch_array($Result);

	$_SESSION['Transfer'] = new StockTransfer($_GET['Trf_ID'],
											$MyRow['shiploc'],
											$MyRow['shiplocationname'],
											$MyRow['shipaccountcode'],
											$MyRow['recloc'],
											$MyRow['reclocationname'],
											$MyRow['recaccountcode'],
											date($_SESSION['DefaultDateFormat']));
	/*Populate the StockTransfer TransferItem s array with the lines to be transferred */
	$i = 0;
	do {
		$_SESSION['Transfer']->TransferItem[$i] = new LineItem($MyRow['stockid'],
																$MyRow['description'],
																$MyRow['shipqty'],
																$MyRow['units'],
																$MyRow['controlled'],
																$MyRow['serialised'],
																$MyRow['perishable'],
																$MyRow['decimalplaces']);
		$_SESSION['Transfer']->TransferItem[$i]->PrevRecvQty = $MyRow['recqty'];
		$_SESSION['Transfer']->TransferItem[$i]->Quantity = $MyRow['shipqty'] - $MyRow['recqty'];

		$i++; /*numerical index for the TransferItem[] array of LineItem s */

	} while ($MyRow = DB_fetch_array($Result));

} /* $_GET['Trf_ID'] is set */
echo '<div class="db-bottom-layout">';


if (isset($_SESSION['Transfer'])) {
	echo '<aside class="db-col-aside">';
	
	// Selection Card (Moved to Sidebar)
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="form1">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-map-marker-alt"></i> ' . __('Receiving Into') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Warehouse') . '</label>
						<select name="RecLocation" class="db-select" style="height: 40px;">';
	
	if (!isset($_POST['RecLocation'])) {
		$_POST['RecLocation'] = $_SESSION['UserStockLocation'];
	}
	
	$LocResult = DB_query("SELECT locationname, locations.loccode FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY locationname");
	while ($MyRow = DB_fetch_array($LocResult)) {
		$selected = ($MyRow['loccode'] == $_POST['RecLocation']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '				</select>
					</div>
					<button type="submit" name="RefreshTransferList" class="db-btn db-btn-secondary" style="width: 100%; margin-top: 10px;">
						<i class="fas fa-sync-alt"></i> ' . __('Refresh List') . '
					</button>
				</div>
			</div>
		</form>';

	// Pending List (Condensed for Sidebar)
	$SQL = "SELECT DISTINCT reference,
				locations.locationname as trffromloc,
				shipdate
			FROM loctransfers
			INNER JOIN locations ON loctransfers.shiploc=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=loctransfers.recloc AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1
			WHERE recloc='" . $_POST['RecLocation'] . "'
				AND pendingqty > 0
			ORDER BY reference";

	$TrfResult = DB_query($SQL);
	if (DB_num_rows($TrfResult) > 0) {
		echo '<div class="db-card" style="margin-top: 20px;">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Pending') . '</div>
				</div>
				<div class="db-card-body" style="padding: 0;">';
		while ($MyRow = DB_fetch_array($TrfResult)) {
			$isActive = (isset($_SESSION['Transfer']) && $_SESSION['Transfer']->TrfID == $MyRow['reference']) ? 'db-status-active' : '';
			echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Trf_ID=' . $MyRow['reference'] . '" 
					 class="db-search-item ' . $isActive . '" style="display: block; text-decoration: none; padding: 12px 15px; border-bottom: 1px solid var(--border-soft);">
						<div class="db-font-bold text-primary" style="font-size: 0.9rem;">#' . $MyRow['reference'] . '</div>
						<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow['trffromloc'] . '</div>
						<div style="font-size: 0.7rem; opacity: 0.6; margin-top: 4px;">' . ConvertSQLDateTime($MyRow['shipdate']) . '</div>
					 </a>';
		}
		echo '	</div>
			  </div>';
	}

	echo '</aside>

	<main class="db-col-main">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />

			
			<div class="db-card" style="margin-bottom: 24px;">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Shipment Details') . '</div>
					<div class="db-badge db-badge-primary">#' . $_SESSION['Transfer']->TrfID . '</div>
				</div>
				<div class="db-card-body">
					<div style="display: flex; gap: 30px; align-items: center; justify-content: space-around; padding: 10px 0;">
						<div style="text-align: center;">
							<div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">' . __('Dispatching From') . '</div>
							<div class="db-badge db-badge-secondary" style="padding: 10px 20px;">
								<i class="fas fa-warehouse" style="margin-right: 8px;"></i>' . $_SESSION['Transfer']->StockLocationFromName . '
							</div>
						</div>
						
						<div style="color: var(--border); font-size: 1.5rem; opacity: 0.5;">
							<i class="fas fa-arrow-right"></i>
						</div>
						
						<div style="text-align: center;">
							<div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 8px;">' . __('Receiving Into') . '</div>
							<div class="db-badge db-badge-primary" style="padding: 10px 20px;">
								<i class="fas fa-map-marker-alt" style="margin-right: 8px;"></i>' . $_SESSION['Transfer']->StockLocationToName . '
							</div>
						</div>
					</div>
				</div>
			</div>


			<div class="db-status-bar db-status-active" style="margin-bottom: 24px;">
				<div class="db-status-icon"><i class="fas fa-check-circle"></i></div>
				<div class="db-status-text">' . __('Please verify shipment quantities received below.') . '</div>
			</div>


			<!-- Card 3: Receiving Items -->
			<div class="db-card">
				<div class="db-card-body" style="padding: 0;">
					<div class="table-responsive">
						<table class="db-table db-table-hover">
							<thead>
								<tr>
									<th>' . __('Item Code') . '</th>
									<th>' . __('Description') . '</th>
									<th class="text-right">' . __('Dispatched') . '</th>
									<th class="text-right">' . __('Prev. Recv') . '</th>
									<th class="text-center" style="width: 140px;">' . __('To Receive') . '</th>
									<th>' . __('Units') . '</th>
									<th class="text-center">' . __('Cancel Bal.') . '</th>
									<th class="text-center">' . __('Serial/Batch') . '</th>
								</tr>
							</thead>
							<tbody>';

	$i = 0;
	foreach ($_SESSION['Transfer']->TransferItem AS $TrfLine) {
		if (isset($_POST['Qty' . $i]) AND is_numeric(filter_number_format($_POST['Qty' . $i]))) {
			$_SESSION['Transfer']->TransferItem[$i]->Quantity = round(filter_number_format($_POST['Qty' . $i]), $TrfLine->DecimalPlaces);
			$Qty = round(filter_number_format($_POST['Qty' . $i]), $TrfLine->DecimalPlaces);
		} elseif ($TrfLine->Controlled == 1) {
			$Qty = (sizeOf($TrfLine->SerialItems) == 0) ? 0 : $TrfLine->Quantity;
		} else {
			$Qty = $TrfLine->Quantity;
		}

		echo '<tr>
				<td class="font-weight-bold" style="color: var(--db-primary);">' . $TrfLine->StockID . '</td>
				<td>' . $TrfLine->ItemDescription . '</td>
				<td class="text-right">' . locale_number_format($TrfLine->ShipQty, $TrfLine->DecimalPlaces) . '</td>
				<td class="text-right">' . locale_number_format($TrfLine->PrevRecvQty, $TrfLine->DecimalPlaces) . '</td>
				<td class="text-center">';

		if ($TrfLine->Controlled == 1) {
			echo '<input type="hidden" name="Qty' . $i . '" value="' . locale_number_format($Qty, $TrfLine->DecimalPlaces) . '" />
				  <span class="db-badge db-badge-info" style="font-size: 1rem; padding: 6px 12px; min-width: 60px;">' . $Qty . '</span>';
		} else {
			echo '<input type="text" class="db-input db-input-light text-center" name="Qty' . $i . '" maxlength="10" style="height: 36px; border-radius: 4px;" value="' . locale_number_format($Qty, $TrfLine->DecimalPlaces) . '" />';
		}

		echo '</td>
				<td>' . $TrfLine->PartUnit . '</td>
				<td class="text-center">
					<label class="db-checkbox-container" style="margin: 0; display: inline-block;">
						<input type="checkbox" name="CancelBalance' . $i . '" value="1" />
						<span class="db-checkbox-label" style="padding-left: 25px;"></span>
					</label>
				</td>
				<td class="text-center">';

		if ($TrfLine->Controlled == 1) {
			$icon = ($TrfLine->Serialised == 1) ? 'fa-barcode' : 'fa-boxes';
			$label = ($TrfLine->Serialised == 1) ? __('Serial #s') : __('Batch Refs');
			echo '<a href="' . $RootPath . '/StockTransferControlled.php?TransferItem=' . $i . '" class="db-btn db-btn-sm db-btn-outline-primary" style="white-space: nowrap;">
					<i class="fas ' . $icon . '"></i> ' . $label . '
				  </a>';
		} else {
			echo '<span style="opacity: 0.2;">-</span>';
		}

		echo '</td>
			</tr>';
		$i++;
	}

	echo '				</tbody>
						</table>
					</div>
				</div>
			</div>
			
			<div class="db-form-actions" style="margin-top: 30px; display: flex; align-items: center; justify-content: flex-end;">
				<button type="submit" name="ProcessTransfer" class="db-btn db-btn-primary db-btn-lg" style="padding-left: 40px; padding-right: 40px; font-weight: 600;">
					<i class="fas fa-check-double"></i> ' . __('Process Inventory Transfer') . '
				</button>
			</div>
		</main>';
} else {
	// Empty State - No transfer selected
	echo '	<main class="db-col-main">
				<div class="db-card" style="height: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center;">
					<div class="db-card-body">
						<div style="width: 80px; height: 80px; background: var(--db-bg-alt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--db-text-muted);">
							<i class="fas fa-dolly-flatbed" style="font-size: 2.5rem; opacity: 0.3;"></i>
						</div>
						<h3 class="db-font-bold" style="color: var(--text-main); margin-bottom: 8px;">' . __('Select a Transfer') . '</h3>
						<p style="max-width: 300px; margin: 0 auto; color: var(--text-muted);">' . __('Choose an incoming shipment from the pending list in the sidebar to begin receiving stock.') . '</p>
					</div>
				</div>
			</main>';
}

echo '</div>'; // End db-bottom-layout




include(__DIR__ . '/includes/footer.php');

function RecordItemCancelledInTransfer($TransferReference, $StockID, $CancelQty) {
	$SQL = "INSERT INTO loctransfercancellations (
			reference,
			stockid,
			cancelqty,
			canceldate,
			canceluserid)
		VALUES ('" . $TransferReference . "',
			'" . $StockID . "',
			(SELECT (l2.shipqty-l2.recqty)
				FROM loctransfers AS l2
				WHERE l2.reference = '" . $TransferReference . "'
					AND l2.stockid ='" . $StockID . "') - " . $CancelQty . ",
			'" . date('Y-m-d H:i:s') . "',
			'" . $_SESSION['UserID'] . "')";
	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' .
			  __('The transfer cancellation record could not be inserted because');
	DB_query($SQL, $ErrMsg, '', true);
}
