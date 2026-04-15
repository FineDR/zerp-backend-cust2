<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock Re-Order Level Maintenance');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['StockID'])){
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])){
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

$Result = DB_query("SELECT description, units, decimalplaces FROM stockmaster WHERE stockid='" . $StockID . "'");
$ItemDetails = DB_fetch_array($Result);

if (isset($_POST['UpdateData']) && $StockID != '') {
	foreach ($_POST as $key => $val) {
		if (substr($key, 0, 4) == 'lvl_') {
			$loc = substr($key, 4);
			$newLevel = filter_number_format($val);
			if (is_numeric($newLevel) && $newLevel >= 0) {
				$SQL = "UPDATE locstock SET reorderlevel = '" . $newLevel . "' WHERE stockid = '" . $StockID . "' AND loccode = '"  . $loc ."'";
				DB_query($SQL);
			}
		}
	}
	prnMsg(__('Re-order levels updated successfully'), 'success');
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title"><i class="fas fa-layer-group"></i> ' . $Title . '</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/SelectProduct.php" class="db-btn db-btn-outline db-btn-small"><i class="fas fa-arrow-left"></i> ' . __('Search Items') . '</a>
			</div>
		</div>

		<div class="db-card" style="margin-bottom: 25px;">
			<div class="db-card-body">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-grid db-grid-3" style="align-items: flex-end;">
						<div class="db-form-group">
							<label class="db-label">' . __('Inventory Item Code') . '</label>
							<input type="text" name="StockID" class="db-input" required value="' . $StockID . '" placeholder="' . __('Enter Code...') . '" />
						</div>
						<div class="db-form-group">
							<button type="submit" name="Show" class="db-btn db-btn-primary"><i class="fas fa-search"></i> ' . __('Find Re-order Levels') . '</button>
						</div>
						<div class="text-right">';
if ($StockID != '') {
	echo '				<div class="db-font-bold text-primary">' . $StockID . ' - ' . $ItemDetails['description'] . '</div>
						<div class="db-muted" style="font-size: 0.85rem;">' . __('Units') . ': ' . $ItemDetails['units'] . '</div>';
}
echo '					</div>
					</div>
				</form>
			</div>
		</div>';

if ($StockID != '') {
	$SQL = "SELECT locstock.loccode, locations.locationname, locstock.quantity, locstock.reorderlevel, locationusers.canupd
			FROM locstock INNER JOIN locations ON locstock.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE locstock.stockid = '" . $StockID . "'
			ORDER BY locations.locationname";
	$LocStockResult = DB_query($SQL);

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="StockID" value="' . $StockID . '" />
			<div class="db-card">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-map-marker-alt"></i> ' . __('Safety Stock by Location') . '</h3></div>
				<div class="db-card-body p-0">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Location Hub') . '</th>
									<th class="text-right">' . __('Physical QOH') . '</th>
									<th class="text-right" style="width: 200px;">' . __('Re-Order Point') . '</th>
								</tr>
							</thead>
							<tbody>';
	while ($MyRow = DB_fetch_array($LocStockResult)) {
		echo '<tr>
				<td>
					<div class="db-font-bold">' . $MyRow['locationname'] . '</div>
					<div class="db-muted" style="font-size: 0.75rem;">' . $MyRow['loccode'] . '</div>
				</td>
				<td class="text-right db-font-bold">' . locale_number_format($MyRow['quantity'], $ItemDetails['decimalplaces']) . '</td>
				<td class="text-right">';
		if ($MyRow['canupd'] == 1) {
			echo '<input type="number" step="any" name="lvl_' . $MyRow['loccode'] . '" class="db-input text-right" style="max-width: 150px; display: inline-block;" value="' . $MyRow['reorderlevel'] . '" />';
		} else {
			echo '<div class="db-badge db-badge-secondary">' . $MyRow['reorderlevel'] . '</div>';
		}
		echo '	</td>
			  </tr>';
	}
	echo '				</tbody>
						</table>
					</div>
				</div>
				<div class="db-card-footer text-center p-4 bg-light">
					<button type="submit" name="UpdateData" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . __('Commit Updated Levels') . '</button>
				</div>
			</div>
		  </form>';

	// Quick Links Card
	echo '<div class="db-card" style="margin-top: 25px;">
			<div class="db-card-body">
				<div class="db-grid db-grid-4">
					<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-exchange-alt"></i> ' . __('Movements') . '</a>
					<a href="' . $RootPath . '/StockUsage.php?StockID=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-chart-line"></i> ' . __('Usage') . '</a>
					<a href="' . $RootPath . '/SelectSalesOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-shopping-cart"></i> ' . __('Open Orders') . '</a>
					<a href="' . $RootPath . '/SelectCompletedOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-history"></i> ' . __('History') . '</a>
				</div>
			</div>
		  </div>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
