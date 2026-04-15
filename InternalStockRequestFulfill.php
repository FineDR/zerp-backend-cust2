<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fulfill Stock Requests');
$ViewTopic = 'Inventory';
$BookMark = 'FulfilRequest';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">';

// CARD 1: LOCATION CONTEXT
echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Issuing Location') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-label">' . __('Source Warehouse') . '</label>
					<select name="Location" class="db-select db-input-light" onchange="this.form.submit();">
						<option value="">' . __('Select a Location') . '</option>';
$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE internalrequest = 1 ORDER BY locationname";
$ResStkLocs = DB_query($SQL);
while ($RowLoc = DB_fetch_array($ResStkLocs)) {
	$selected = (isset($_POST['Location']) AND $_POST['Location'] == $RowLoc['loccode']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $RowLoc['loccode'] . '">' . $RowLoc['locationname'] . '</option>';
}
echo '				</select>
				</div>
				<p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 10px;">
					' . __('Pick the warehouse from which you are issuing these requests.') . '
				</p>
			</div>
		</div>
	  </form>';

// CARD 2: ACTION DASHBOARD (Only show if location is selected)
if (isset($_POST['Location'])) {
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-check-double"></i> ' . __('Fulfillment Actions') . '</h3>
			</div>
			<div class="db-card-body">
				<p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
					' . __('Enter delivered quantities and serial numbers below, then click to process all updates.') . '
				</p>
				<button type="submit" form="fulfillment_form" name="UpdateAll" class="db-btn db-btn-primary" style="width: 100%;">
					<i class="fas fa-save"></i> ' . __('Process Fulfillment') . '
				</button>
			</div>
		</div>';
}

echo '</aside>';
// SIDEBAR END

echo '<main class="db-col-main">';


if (isset($_POST['UpdateAll'])) {
	foreach ($_POST as $key => $Value) {
		if (mb_strpos($key, 'Qty')) {
			$RequestID = mb_substr($key, 0, mb_strpos($key, 'Qty'));
			$LineID = mb_substr($key, mb_strpos($key, 'Qty') + 3);
			$Quantity = filter_number_format($_POST[$RequestID . 'Qty' . $LineID]);
			$StockID = $_POST[$RequestID . 'StockID' . $LineID];
			$Location = $_POST[$RequestID . 'Location' . $LineID];
			$Department = $_POST[$RequestID . 'Department' . $LineID];
			$Tags = $_POST[$RequestID . 'Tag' . $LineID];
			$RequestedQuantity = filter_number_format($_POST[$RequestID . 'RequestedQuantity' . $LineID]);
			$Controlled = $_POST[$RequestID . 'Controlled' . $LineID];
			$SerialNo = $_POST[$RequestID . 'Ser' . $LineID];
			if (isset($_POST[$RequestID . 'Completed' . $LineID])) {
				$Completed = true;
			}
			else {
				$Completed = false;
			}

			$SQL = "SELECT actualcost, decimalplaces FROM stockmaster WHERE stockid='" . $StockID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$StandardCost = $MyRow['actualcost'];
			$DecimalPlaces = $MyRow['decimalplaces'];

			$Narrative = __('Issue') . ' ' . $Quantity . ' ' . __('of') . ' ' . $StockID . ' ' . __('to department') . ' ' . $Department . ' ' . __('from') . ' ' . $Location;

			$AdjustmentNumber = GetNextTransNo(17);
			$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));
			$SQLAdjustmentDate = FormatDateForSQL(date($_SESSION['DefaultDateFormat']));

			DB_Txn_Begin();

			// Need to get the current location quantity will need it later for the stock movement
			$SQL = "SELECT locstock.quantity
					FROM locstock
					WHERE locstock.stockid='" . $StockID . "'
						AND loccode= '" . $Location . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 1) {
				$LocQtyRow = DB_fetch_row($Result);
				$QtyOnHandPrior = $LocQtyRow[0];
			}
			else {
				// There must actually be some error this should never happen
				$QtyOnHandPrior = 0;
			}

			if ($_SESSION['ProhibitNegativeStock'] == 0 OR ($_SESSION['ProhibitNegativeStock'] == 1 AND $QtyOnHandPrior >= $Quantity)) {

				$SQL = "INSERT INTO stockmoves (
									stockid,
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
									'" . $StockID . "',
									17,
									'" . $AdjustmentNumber . "',
									'" . $Location . "',
									'" . $SQLAdjustmentDate . "',
									'" . $_SESSION['UserID'] . "',
									'" . $PeriodNo . "',
									'" . $Narrative . "',
									'" . -$Quantity . "',
									'" . ($QtyOnHandPrior - $Quantity) . "'
								)";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movement record cannot be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/*Get the ID of the StockMove... */
				$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

				if ($Controlled == 1) {
					/*We need to add the StockSerialItem record and the StockSerialMoves as well */

					$SQL = "UPDATE stockserialitems	SET quantity= quantity - " . $Quantity . "
							WHERE stockid='" . $StockID . "'
							AND loccode='" . $Location . "'
							AND serialno='" . $SerialNo . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock item record could not be updated because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					/* now insert the serial stock movement */

					$SQL = "INSERT INTO stockserialmoves (stockmoveno,
											stockid,
											serialno,
											moveqty)
									VALUES ('" . $StkMoveNo . "',
											'" . $StockID . "',
											'" . $SerialNo . "',
											'" . -$Quantity . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock movement record could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /*end if the orderline is a controlled item */

				$SQL = "UPDATE stockrequestitems
						SET qtydelivered=qtydelivered+" . $Quantity . "
						WHERE dispatchid='" . $RequestID . "'
							AND dispatchitemsid='" . $LineID . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The location stock record could not be updated because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$SQL = "UPDATE locstock SET quantity = quantity - '" . $Quantity . "'
									WHERE stockid='" . $StockID . "'
										AND loccode='" . $Location . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The location stock record could not be updated because');

				$Result = DB_query($SQL, $ErrMsg, '', true);

				if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 AND $StandardCost > 0) {

					$StockGLCodes = GetStockGLCode($StockID);

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												amount,
												narrative)
											VALUES (17,
												'" . $AdjustmentNumber . "',
												'" . $SQLAdjustmentDate . "',
												'" . $PeriodNo . "',
												'" . $StockGLCodes['issueglact'] . "',
												'" . $StandardCost * ($Quantity) . "',
												'" . mb_substr($Narrative, 0, 200) . "'
											)";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction entries could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					InsertGLTags($Tags);

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												amount,
												narrative)
											VALUES (17,
												'" . $AdjustmentNumber . "',
												'" . $SQLAdjustmentDate . "',
												'" . $PeriodNo . "',
												'" . $StockGLCodes['stockact'] . "',
												'" . $StandardCost * -$Quantity . "',
												'" . mb_substr($Narrative, 0, 200) . "'
											)";

					$Errmsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction entries could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

				if (($Quantity >= $RequestedQuantity) OR $Completed == true) {
					$SQL = "UPDATE stockrequestitems
								SET completed=1
							WHERE dispatchid='" . $RequestID . "'
								AND dispatchitemsid='" . $LineID . "'";
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

				DB_Txn_Commit();

				$ConfirmationText = __('An internal stock request for') . ' ' . $StockID . ' ' . __('has been fulfilled from location') . ' ' . $Location . ' ' . __('for a quantity of') . ' ' . locale_number_format($Quantity, $DecimalPlaces);
				prnMsg($ConfirmationText, 'success');

				if ($_SESSION['InventoryManagerEmail'] != '') {
					$ConfirmationText = $ConfirmationText . ' ' . __('by user') . ' ' . $_SESSION['UserID'] . ' ' . __('at') . ' ' . date('Y-m-d H:i:s');
					$EmailSubject = __('Internal Stock Request Fulfillment for') . ' ' . $StockID;
					SendEmailFromWebERP($SysAdminEmail,
										$_SESSION['InventoryManagerEmail'],
										$EmailSubject,
										$ConfirmationText,
										'',
										false);
				}
			}
			else {
				$ConfirmationText = __('An internal stock request for') . ' ' . $StockID . ' ' . __('has been fulfilled from location') . ' ' . $Location . ' ' . __('for a quantity of') . ' ' . locale_number_format($Quantity, $DecimalPlaces) . ' ' . __('cannot be created as there is insufficient stock and your system is configured to not allow negative stocks');
				prnMsg($ConfirmationText, 'warn');
			}

			// Check if request can be closed and close if done.
			if (isset($RequestID)) {
				$SQL = "SELECT dispatchid
						FROM stockrequestitems
						WHERE dispatchid='" . $RequestID . "'
							AND completed=0";
				$Result = DB_query($SQL);
				if (DB_num_rows($Result) == 0) {
					$SQL = "UPDATE stockrequest
						SET closed=1
					WHERE dispatchid='" . $RequestID . "'";
					$Result = DB_query($SQL);
				}
			}
		}
	}
}

if (!isset($_POST['Location'])) {
	echo '<div class="db-status-bar db-status-info">
			<div class="db-status-icon"><i class="fas fa-arrow-left"></i></div>
			<div class="db-status-text">' . __('Please select an issuing location from the sidebar to begin fulfilling requests.') . '</div>
		  </div>';
}


/* Retrieve the requisition header information
*/
if (isset($_POST['Location'])) {
	$SQL = "SELECT stockrequest.dispatchid,
			locations.locationname,
			stockrequest.despatchdate,
			stockrequest.narrative,
			departments.description,
			www_users.realname,
			www_users.email
		FROM stockrequest
		LEFT JOIN departments
			ON stockrequest.departmentid=departments.departmentid
		LEFT JOIN locations
			ON stockrequest.loccode=locations.loccode
		LEFT JOIN www_users
			ON www_users.userid=departments.authoriser
	WHERE stockrequest.authorised=1
		AND stockrequest.closed=0
		AND stockrequest.loccode='" . $_POST['Location'] . "'";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {
		prnMsg(__('There are no outstanding authorised requests for this location') , 'info');
		echo '<br />';
		echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Select another location') . '</a></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" id="fulfillment_form">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="Location" value="' . $_POST['Location'] . '" />';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<div class="db-card" style="margin-bottom: 25px;">
				<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
					<div style="display: flex; align-items: center; gap: 15px;">
						<span class="db-badge db-badge-primary">#' . $MyRow['dispatchid'] . '</span>
						<h3 class="db-card-title">' . $MyRow['description'] . '</h3>
					</div>
					<div class="db-font-bold" style="font-size: 0.8rem; color: var(--text-muted);">
						<span style="color: var(--primary);"><i class="fas fa-calendar-alt"></i> ' . ConvertSQLDate($MyRow['despatchdate']) . '</span>
					</div>
				</div>
				
				<div class="db-card-body">
					<div class="db-status-bar db-status-info" style="margin-bottom: 20px; border: none; padding: 10px 15px;">
						<div class="db-status-icon"><i class="fas fa-quote-left"></i></div>
						<div class="db-status-text" style="font-style: italic;">' . ($MyRow['narrative'] ?: __('No context provided for this request.')) . '</div>
					</div>';

		$LineSQL = "SELECT stockrequestitems.dispatchitemsid,
						stockrequestitems.dispatchid,
						stockrequestitems.stockid,
						stockrequestitems.decimalplaces,
						stockrequestitems.uom,
						stockmaster.description,
						stockrequestitems.quantity,
						stockrequestitems.qtydelivered,
						stockmaster.controlled
				FROM stockrequestitems
				LEFT JOIN stockmaster
				ON stockmaster.stockid=stockrequestitems.stockid
			WHERE dispatchid='" . $MyRow['dispatchid'] . "'
				AND completed=0";
		$LineResult = DB_query($LineSQL);

		echo '		<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Product / Unit') . '</th>
									<th class="text-right">' . __('Required') . '</th>
									<th class="text-right">' . __('To Issue') . '</th>
									<th>' . __('Batch/Lot/Serial') . '</th>
									<th class="text-center">' . __('Done') . '</th>
									<th style="width: 200px;">' . __('GL Tag') . '</th>
								</tr>
							</thead>
							<tbody>';

		while ($LineRow = DB_fetch_array($LineResult)) {
			echo '			<tr>
								<td>
									<div class="db-font-bold text-primary">' . $LineRow['stockid'] . '</div>
									<div style="font-size: 0.8rem; color: var(--text-muted);">' . $LineRow['description'] . '</div>
									<div class="db-badge db-badge-secondary" style="font-size: 0.7rem; margin-top: 5px;">' . $LineRow['uom'] . '</div>
								</td>
								<td class="text-right db-font-bold" style="color: var(--text-muted);">' . locale_number_format($LineRow['quantity'] - $LineRow['qtydelivered'], $LineRow['decimalplaces']) . '</td>
								<td class="text-right">
									<input type="text" class="db-input number" name="' . $LineRow['dispatchid'] . 'Qty' . $LineRow['dispatchitemsid'] . '" value="' . locale_number_format($LineRow['quantity'] - $LineRow['qtydelivered'], $LineRow['decimalplaces']) . '" style="width: 100px; text-align: right;" />
								</td>
								<td>';
			if ($LineRow['controlled'] == 1) {
				echo '				<input type="text" class="db-input" name="' . $LineRow['dispatchid'] . 'Ser' . $LineRow['dispatchitemsid'] . '" placeholder="' . __('Serial Number') . '" />';
			} else {
				echo '				<span style="font-size: 0.75rem; color: var(--text-muted);">' . __('Not Controlled') . '</span>';
			}
			echo '				</td>
								<td class="text-center">
									<input type="checkbox" name="' . $LineRow['dispatchid'] . 'Completed' . $LineRow['dispatchitemsid'] . '" style="width: 18px; height: 18px; cursor: pointer;" />
								</td>
								<td>';

			// Select GL tags
			$SQLTag = "SELECT tagref, tagdescription FROM tags ORDER BY tagref";
			$ResultTag = DB_query($SQLTag);
			echo '					<select name="' . $LineRow['dispatchid'] . 'Tag' . $LineRow['dispatchitemsid'] . '[]" class="db-select" style="font-size: 0.8rem; padding: 4px;">';
			while ($MyRowTag = DB_fetch_array($ResultTag)) {
				echo '<option value="' . $MyRowTag['tagref'] . '">' . $MyRowTag['tagref'] . ' - ' . $MyRowTag['tagdescription'] . '</option>';
			}
			echo '					</select>
								</td>
							</tr>';
			
			echo '<input type="hidden" name="' . $LineRow['dispatchid'] . 'StockID' . $LineRow['dispatchitemsid'] . '" value="' . $LineRow['stockid'] . '" />';
			echo '<input type="hidden" name="' . $LineRow['dispatchid'] . 'Location' . $LineRow['dispatchitemsid'] . '" value="' . $_POST['Location'] . '" />';
			echo '<input type="hidden" name="' . $LineRow['dispatchid'] . 'RequestedQuantity' . $LineRow['dispatchitemsid'] . '" value="' . ($LineRow['quantity'] - $LineRow['qtydelivered']) . '" />';
			echo '<input type="hidden" name="' . $LineRow['dispatchid'] . 'Department' . $LineRow['dispatchitemsid'] . '" value="' . $MyRow['description'] . '" />';
			echo '<input type="hidden" name="' . $LineRow['dispatchid'] . 'Controlled' . $LineRow['dispatchitemsid'] . '" value="' . $LineRow['controlled'] . '" />';
		}
		echo '				</tbody>
						</table>
					</div>
				</div>
			  </div>';
	}
	echo '</form>';

}
	echo '	</main>
	</div>'; // End db-bottom-layout
include(__DIR__ . '/includes/footer.php');
