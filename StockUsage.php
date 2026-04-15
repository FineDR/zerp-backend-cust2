<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock Usage');

if (isset($_GET['StockID'])){
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])){
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

if (isset($_POST['ShowGraphUsage'])) {
	echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/StockUsageGraph.php?StockLocation=' . $_POST['StockLocation']  . '&amp;StockID=' . $StockID . '">';
	prnMsg(__('You should automatically be forwarded to the usage graph') .
			'. ' . __('If this does not happen') .' (' . __('if the browser does not support META Refresh') . ') ' .
			'<a href="' . $RootPath . '/StockUsageGraph.php?StockLocation=' . $_POST['StockLocation'] .'&amp;StockID=' . $StockID . '">' . __('click here') . '</a> ' . __('to continue'),'info');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card" style="margin-bottom: 20px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Usage Filters') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Code') . '</label>
						<input type="text" name="StockID" class="db-input" value="' . $StockID . '" required="required" placeholder="' . __('e.g. ITEM-001') . '" autofocus />
					</div>
					
					<div class="db-form-group">
						<label class="db-label">' . __('Location') . '</label>
						<select name="StockLocation" class="db-select">';
$SQL_Loc = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
$ResStkLocs = DB_query($SQL_Loc);
while ($RowLoc = DB_fetch_array($ResStkLocs)) {
	$selected = (isset($_POST['StockLocation']) AND $_POST['StockLocation'] == $RowLoc['loccode']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $RowLoc['loccode'] . '">' . $RowLoc['locationname'] . '</option>';
}
$all_selected = (isset($_POST['StockLocation']) AND $_POST['StockLocation'] == 'All') ? 'selected="selected"' : '';
echo '						<option ' . $all_selected . ' value="All">' . __('All Locations') . '</option>
						</select>
					</div>

					<button type="submit" name="ShowUsage" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
						<i class="fas fa-list-ul"></i> ' . __('Show Usage') . '
					</button>
					<button type="submit" name="ShowGraphUsage" class="db-btn db-input-light" style="width: 100%; margin-top: 10px;">
						<i class="fas fa-chart-bar"></i> ' . __('Show Graph') . '
					</button>
				</div>
			</div>';

// ITEM CONTEXT CARD
if ($StockID != '' AND !isset($Its_A_KitSet_Assembly_Or_Dummy)) {
	$ResMaster = DB_query("SELECT description, units FROM stockmaster WHERE stockid='".$StockID."'");
	if (DB_num_rows($ResMaster) > 0) {
		$RowMaster = DB_fetch_array($ResMaster);
		echo '<div class="db-card" style="margin-bottom: 20px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Item Profile') . '</h3>
				</div>
				<div class="db-card-body">
					<div style="margin-bottom: 8px;">
						<label class="db-label" style="display:block; font-size: 0.7rem;">' . __('Description') . '</label>
						<div class="db-font-bold" style="font-size: 0.9rem;">' . $RowMaster['description'] . '</div>
					</div>
					<div>
						<label class="db-label" style="display:block; font-size: 0.7rem;">' . __('UOM') . '</label>
						<div class="db-badge db-badge-primary">' . $RowMaster['units'] . '</div>
					</div>
				</div>
			  </div>';
	}
}

// QUICK LINKS CARD
if ($StockID != '') {
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-external-link-alt"></i> ' . __('Related Actions') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 10px;">
				<a href="' . $RootPath . '/StockStatus.php?StockID=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-info-circle"></i> ' . __('Detailed Status') . '
				</a>
				<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-exchange-alt"></i> ' . __('Stock Movements') . '
				</a>
				<a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; font-size: 0.8rem;">
					<i class="fas fa-truck"></i> ' . __('Outstanding POs') . '
				</a>
			</div>
		  </div>';
}

echo '		</form>
	</aside>';

echo '<main class="db-col-main">';



/*HideMovt ==1 if the movement was only created for the purpose of a transaction but is not a physical movement eg. A price credit will create a movement record for the purposes of display on a credit note
but there is no physical stock movement - it makes sense honest ??? */

$CurrentPeriod = GetPeriod(date($_SESSION['DefaultDateFormat']));

if (isset($_POST['ShowUsage'])){
	if ($_POST['StockLocation']=='All'){
		$SQL = "SELECT periods.periodno,
				periods.lastdate_in_period,
				canview,
				SUM(CASE WHEN (stockmoves.type=10 OR stockmoves.type=11 OR stockmoves.type=17 OR stockmoves.type=28 OR stockmoves.type=38)
							AND stockmoves.hidemovt=0
							AND stockmoves.stockid = '" . $StockID . "'
						THEN -stockmoves.qty ELSE 0 END) AS qtyused
				FROM periods LEFT JOIN stockmoves
					ON periods.periodno=stockmoves.prd
				INNER JOIN locationusers ON locationusers.loccode=stockmoves.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE periods.periodno <='" . $CurrentPeriod . "'
				GROUP BY periods.periodno,
					periods.lastdate_in_period
				ORDER BY periodno DESC LIMIT " . $_SESSION['NumberOfPeriodsOfStockUsage'];
	} else {
		$SQL = "SELECT periods.periodno,
				periods.lastdate_in_period,
				SUM(CASE WHEN (stockmoves.type=10 OR stockmoves.type=11 OR stockmoves.type=17 OR stockmoves.type=28 OR stockmoves.type=38)
								AND stockmoves.hidemovt=0
								AND stockmoves.stockid = '" . $StockID . "'
								AND stockmoves.loccode='" . $_POST['StockLocation'] . "'
							THEN -stockmoves.qty ELSE 0 END) AS qtyused
				FROM periods LEFT JOIN stockmoves
					ON periods.periodno=stockmoves.prd
				WHERE periods.periodno <='" . $CurrentPeriod . "'
				GROUP BY periods.periodno,
					periods.lastdate_in_period
				ORDER BY periodno DESC LIMIT " . $_SESSION['NumberOfPeriodsOfStockUsage'];

	}
	$ErrMsg = __('The stock usage for the selected criteria could not be retrieved');
	$MovtsResult = DB_query($SQL, $ErrMsg);

if (isset($_POST['ShowUsage'])) {
	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-chart-area"></i> ' . __('Usage Analysis') . '</h3>
				<span class="db-badge db-badge-primary">' . ($_POST['StockLocation'] == 'All' ? __('All Locations') : $_POST['StockLocation']) . '</span>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm); margin-bottom: 20px;">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Period / Month') . '</th>
								<th class="text-right">' . __('Physical Usage') . '</th>
							</tr>
						</thead>
						<tbody>';

	$TotalUsage = 0;
	$PeriodsCounter = 0;

	while ($MyRow=DB_fetch_array($MovtsResult)) {
		$DisplayDate = MonthAndYearFromSQLDate($MyRow['lastdate_in_period']);
		$TotalUsage += $MyRow['qtyused'];
		$PeriodsCounter++;
		echo '			<tr class="striped_row">
							<td><div class="db-font-bold text-primary">' . $DisplayDate . '</div></td>
							<td class="text-right db-font-bold">' . locale_number_format($MyRow['qtyused'], $DecimalPlaces) . '</td>
						</tr>';
	}

	echo '				</tbody>
					</table>
				</div>';

	if ($TotalUsage > 0 AND $PeriodsCounter > 0) {
		echo '	<div class="db-status-bar db-status-success" style="border: none; padding: 15px 25px;">
					<div class="db-status-icon"><i class="fas fa-calculator"></i></div>
					<div class="db-status-text">
						<span style="font-size: 0.8rem; opacity: 0.8; display: block;">' . __('Calculated Strategic Metric') . '</span>
						<span style="font-size: 1.1rem; font-weight: bold;">' . __('Average Usage per month') . ': ' . locale_number_format($TotalUsage/$PeriodsCounter, $DecimalPlaces) . ' ' . $MyRowMaster['units'] . '</span>
					</div>
				</div>';
	}
	echo '	</div>
		  </div>';
} else {
	if ($StockID == '') {
		echo '<div class="db-status-bar db-status-info">
				<div class="db-status-icon"><i class="fas fa-arrow-left"></i></div>
				<div class="db-status-text">' . __('Please enter a stock code and select a location in the sidebar to view usage trends.') . '</div>
			  </div>';
	}
}


} /* end if Show Usage is clicked */


echo '	</main>
	</div>'; // end db-bottom-layout

include(__DIR__ . '/includes/footer.php');
