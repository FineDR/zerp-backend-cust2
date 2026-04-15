<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Reorder Level Location Reporting');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/StockFunctions.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR: Selection Criteria
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-filter"></i> ' . __('Report Criteria') . '</h3>
		</div>
		<div class="db-card-body">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// Location Selection
$SQL = "SELECT locations.loccode, locationname
		FROM locations
		INNER JOIN locationusers ON locationusers.loccode = locations.loccode
		WHERE locationusers.userid = '" . $_SESSION['UserID'] . "'
		AND locationusers.canview = 1";
$ResultStkLocs = DB_query($SQL);
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Stock Location') . '</label>
					<select name="StockLocation" class="db-select">';
while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	echo '				<option ' . ((isset($_POST['StockLocation']) && $_POST['StockLocation'] == $MyRow['loccode']) ? 'selected' : '') . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
}
echo '				</select>
				</div>';

// Category Selection
$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$ResultCats = DB_query($SQL);
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Stock Category') . '</label>
					<select name="StockCat" class="db-select">';
while ($MyRow1 = DB_fetch_array($ResultCats)) {
	echo '				<option ' . ((isset($_POST['StockCat']) && $_POST['StockCat'] == $MyRow1['categoryid']) ? 'selected' : '') . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
}
echo '				</select>
				</div>';

// Number of Days
if (!isset($_POST['NumberOfDays'])) $_POST['NumberOfDays'] = 0;
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Number Of Days Sales') . '</label>
					<input type="text" class="db-input text-right" name="NumberOfDays" maxlength="3" size="4" value="' . $_POST['NumberOfDays'] . '" />
				</div>';

// Sequence
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Order Records By') . '</label>
					<select name="Sequence" class="db-select">
						<option ' . ((isset($_POST['Sequence']) && $_POST['Sequence'] == 1) ? 'selected' : '') . ' value="1">' . __('Total Invoiced Quantities') . '</option>
						<option ' . ((isset($_POST['Sequence']) && $_POST['Sequence'] == 2) ? 'selected' : '') . ' value="2">' . __('Item Stock Code / Description') . '</option>
					</select>
				</div>';

echo '			<button type="submit" name="Update" class="db-btn db-btn-primary w-100">
					<i class="fas fa-search"></i> ' . __('Search Items') . '
				</button>
			</form>
		</div>
	  </div>
	</aside>';

// MAIN: Report Results
echo '<main class="db-col-main">';

//update database if update pressed
if (isset($_POST['submit'])) {
	for ($i = 1; $i < count($_POST); $i++) { //loop through the returned customers
		if (isset($_POST['StockID' . $i]) AND is_numeric(filter_number_format($_POST['ReorderLevel' . $i]))) {
			$SQLUpdate = "UPDATE locstock
						SET reorderlevel = '" . filter_number_format($_POST['ReorderLevel' . $i]) . "',
							bin = '" . strtoupper($_POST['BinLocation' . $i]) . "'
						WHERE loccode = '" . $_POST['StockLocation'] . "'
							AND stockid = '" . $_POST['StockID' . $i] . "'";
			$Result = DB_query($SQLUpdate);
		}
	}
}

if (isset($_POST['submit']) OR isset($_POST['Update'])) {

	if ($_POST['NumberOfDays'] == '') {
		header('Location: ' . htmlspecialchars_decode($RootPath) . '/ReorderLevelLocation.php');
		exit();
	}

	if ($_POST['Sequence'] == 1) {
		$Sequence = "qtyinvoice DESC, locstock.stockid ASC";
	} else {
		$Sequence = "locstock.stockid ASC";
	}

	$SQL = "SELECT locstock.stockid,
				description,
				reorderlevel,
				(SELECT SUM(salesorderdetails.qtyinvoiced)
					FROM salesorderdetails
					INNER JOIN salesorders
						ON salesorderdetails.orderno = salesorders.orderno
					WHERE stockmaster.stockid = salesorderdetails.stkcode
						AND salesorders.fromstkloc = '" . $_POST['StockLocation'] . "'
						AND salesorderdetails.ActualDispatchDate >= DATE_SUB(CURDATE(), INTERVAL " . filter_number_format($_POST['NumberOfDays']) . " DAY)) AS qtyinvoice,
				bin,
				quantity,
				decimalplaces,
				canupd
			FROM locstock
			INNER JOIN stockmaster
				ON locstock.stockid = stockmaster.stockid
			INNER JOIN locationusers
				ON locationusers.loccode = locstock.loccode
				AND locationusers.userid = '" . $_SESSION['UserID'] . "'
				AND locationusers.canview = 1
			WHERE stockmaster.categoryid = '" . $_POST['StockCat'] . "'
				AND locstock.loccode = '" . $_POST['StockLocation'] . "'
				AND stockmaster.discontinued = 0
			ORDER BY " . $Sequence;

	$Result = DB_query($SQL);

	$SQLLoc = "SELECT locationname FROM locations WHERE loccode='" . $_POST['StockLocation'] . "'";
	$ResultLocation = DB_query($SQLLoc);
	$Location = DB_fetch_array($ResultLocation);

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Stock Reorder Levels') . ': <span class="text-primary">' . $Location['locationname'] . '</span></h3>
				<div style="display: flex; gap: 8px;">
					<div class="db-badge db-badge-info">' . locale_number_format($_POST['NumberOfDays'], 0) . ' ' . __('Days Sales') . '</div>
					<div class="db-badge db-badge-secondary">' . DB_num_rows($Result) . ' ' . __('Items') . '</div>
				</div>
			</div>
			<div class="db-card-body p-0">';

	if (DB_num_rows($Result) > 0) {
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="Update">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" value="' . $_POST['Sequence'] . '" name="Sequence" />
				<input type="hidden" value="' . $_POST['StockLocation'] . '" name="StockLocation" />
				<input type="hidden" value="' . $_POST['StockCat'] . '" name="StockCat" />
				<input type="hidden" value="' . $_POST['NumberOfDays'] . '" name="NumberOfDays" />

				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Stock Item') . '</th>
								<th class="text-right">' . __('Invoiced') . '<br/><span class="db-muted" style="font-size: 0.7rem;">(At Location)</span></th>
								<th class="text-right">' . __('Total QOH') . '<br/><span class="db-muted" style="font-size: 0.7rem;">(All Locs)</span></th>
								<th class="text-right">' . __('Local QOH') . '<br/><span class="db-muted" style="font-size: 0.7rem;">(On Hand)</span></th>
								<th style="width: 130px;">' . __('Reorder Level') . '</th>
								<th style="width: 130px;">' . __('Bin Location') . '</th>
							</tr>
						</thead>
						<tbody>';

		$i = 1;
		while ($MyRow = DB_fetch_array($Result)) {
			// find the quantity on hand for the item in all locations
			$QOH = GetQuantityOnHand($MyRow['stockid'], 'USER_CAN_VIEW');

			echo '<tr>
					<td>
						<div class="db-font-bold text-primary">' . $MyRow['stockid'] . '</div>
						<div class="db-muted" style="font-size: 0.75rem;">' . htmlspecialchars($MyRow['description']) . '</div>
					</td>
					<td class="text-right db-font-medium">' . locale_number_format($MyRow['qtyinvoice'], $MyRow['decimalplaces']) . '</td>
					<td class="text-right">' . locale_number_format($QOH, $MyRow['decimalplaces']) . '</td>
					<td class="text-right db-font-bold ' . ($MyRow['quantity'] <= $MyRow['reorderlevel'] ? 'text-danger' : 'text-success') . '">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>';

			if ($MyRow['canupd'] == 1) {
				echo '<td>
						<input type="text" class="db-input text-right p-1" name="ReorderLevel' . $i . '" maxlength="10" style="width: 100%; height: 32px;" value="' . locale_number_format($MyRow['reorderlevel'], 0) . '" />
						<input type="hidden" name="StockID' . $i . '" value="' . $MyRow['stockid'] . '" />
					  </td>
					  <td>
						<input type="text" class="db-input p-1" name="BinLocation' . $i . '" maxlength="10" style="width: 100%; height: 32px;" value="' . $MyRow['bin'] . '" />
					  </td>';
			} else {
				echo '<td class="text-right">' . locale_number_format($MyRow['reorderlevel'], 0) . '</td>
					  <td><div class="db-badge db-badge-secondary">' . ($MyRow['bin'] ?: '-') . '</div></td>';
			}

			echo '</tr>';
			$i++;
		}
		echo '			</tbody>
					</table>
				</div>
				<div class="db-card-body border-top" style="display: flex; justify-content: flex-end; gap: 10px;">
					<button type="submit" name="submit" value="' . __('Update') . '" class="db-btn db-btn-primary">
						<i class="fas fa-save"></i> ' . __('Update Levels & Bins') . '
					</button>
				</div>
			  </form>';
	} else {
		echo '<div class="text-center db-muted" style="padding: 60px;">
				<i class="fas fa-exclamation-triangle fa-3x" style="margin-bottom: 20px; opacity: 0.3;"></i>
				<h4 class="db-font-bold">' . __('No Stock Items Found') . '</h4>
				<p>' . __('No active items match the selected category and location filters.') . '</p>
			  </div>';
	}
	echo '	</div>
		  </div>';
} else {
	echo '<div class="db-card">
			<div class="db-card-body text-center" style="padding: 80px;">
				<div style="width: 80px; height: 80px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px;">
					<i class="fas fa-boxes-stacked fa-3x"></i>
				</div>
				<h3 class="db-font-bold">' . __('Reorder Level Maintenance') . '</h3>
				<p class="db-muted" style="max-width: 500px; margin: 0 auto 25px;">' . __('Review inventory reorder levels and bin assignments across different locations. Configure your report parameters in the sidebar to generate the data grid.') . '</p>
				<div style="display: flex; justify-content: center; gap: 10px;">
					<div class="db-badge db-badge-secondary">' . __('Step 1: Select Location') . '</div>
					<i class="fas fa-chevron-right db-muted" style="align-self: center;"></i>
					<div class="db-badge db-badge-secondary">' . __('Step 2: Filter Category') . '</div>
					<i class="fas fa-chevron-right db-muted" style="align-self: center;"></i>
					<div class="db-badge db-badge-secondary">' . __('Step 3: Update Levels') . '</div>
				</div>
			</div>
		  </div>';
}

echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
