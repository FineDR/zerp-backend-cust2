<?php

/* Shows the stock on hand together with outstanding sales orders and outstanding purchase orders by stock location for all items in the selected stock category */

require(__DIR__ . '/includes/session.php');

$Title = __('All Stock Status By Location/Category');
$ViewTopic = 'Inventory';
$BookMark = 'StockLocStatus';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme,
'/images/magnifier.png" title="',// Icon image.
$Title, '" /> ',// Icon title.
$Title, '</p>';// Page title.

if (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
}

	echo '<style>
		.modern-form-container {
			max-width: 900px;
			margin: 20px auto;
			padding: 30px;
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-md);
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 25px;
			margin-bottom: 30px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group label {
			font-weight: 600;
			color: var(--text-label);
			font-size: 0.9rem;
		}
		.form-group select {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			outline: none;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 15px;
			border-top: 1px solid var(--border-soft);
			padding-top: 25px;
		}
		.button-group input[type="submit"] {
			padding: 12px 30px;
			border-radius: var(--radius-sm);
			font-weight: 700;
			cursor: pointer;
			border: none;
			transition: all var(--transition-fast);
			background: var(--primary);
			color: white;
		}
		.button-group input[type="submit"]:hover {
			opacity: 0.9;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px var(--primary-glow);
			background: var(--primary-hover);
		}
		.report-table-wrapper {
			width: 100%;
			overflow-x: auto;
			margin-top: 20px;
			border-radius: var(--radius-md);
			border: 1px solid var(--border);
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="form-grid">';
	
	echo '<div class="form-group">
			<label>' . __('From Stock Location') . '</label>
			<select name="StockLocation">';
	$SQL = "SELECT locations.loccode, locationname FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);
	while($MyRow=DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockLocation']) && $_POST['StockLocation'] == $MyRow['loccode']) ? 'selected="selected"' : ((!isset($_POST['StockLocation']) && $MyRow['loccode']==$_SESSION['UserStockLocation']) ? 'selected="selected"' : '');
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('In Stock Category') . '</label>
			<select name="StockCat">
				<option value="All">' . __('All') . '</option>';
	$SQL="SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$Result1 = DB_query($SQL);
	while($MyRow1 = DB_fetch_array($Result1)) {
		$selected = (isset($_POST['StockCat']) && $_POST['StockCat'] == $MyRow1['categoryid']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Shown Only Items Where') . '</label>
			<select name="BelowReorderQuantity">
				<option value="All"' . (isset($_POST['BelowReorderQuantity']) && $_POST['BelowReorderQuantity'] == 'All' ? ' selected' : '') . '>' . __('All') . '</option>
				<option value="Below"' . (isset($_POST['BelowReorderQuantity']) && $_POST['BelowReorderQuantity'] == 'Below' ? ' selected' : '') . '>' . __('Only items below re-order quantity') . '</option>
				<option value="NotZero"' . (isset($_POST['BelowReorderQuantity']) && $_POST['BelowReorderQuantity'] == 'NotZero' ? ' selected' : '') . '>' . __('Only items where stock is available') . '</option>
				<option value="OnOrder"' . (isset($_POST['BelowReorderQuantity']) && $_POST['BelowReorderQuantity'] == 'OnOrder' ? ' selected' : '') . '>' . __('Only items currently on order') . '</option>
			</select>
		  </div>';
	
	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input name="ShowStatus" type="submit" value="', __('Show Stock Status'), '" />
		  </div>';
	echo '</form></div>';

	if (isset($_POST['ShowStatus'])) {
		if ($_POST['StockCat']=='All') {
			$SQL = "SELECT locstock.stockid, stockmaster.description, locstock.loccode, locstock.bin, locations.locationname, locstock.quantity, locstock.reorderlevel, stockmaster.decimalplaces, stockmaster.serialised, stockmaster.controlled
					FROM locstock, stockmaster, locations
					WHERE locstock.stockid=stockmaster.stockid AND locstock.loccode = '".$_POST['StockLocation']."' AND locstock.loccode=locations.loccode AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
					ORDER BY locstock.stockid";
		} else {
			$SQL = "SELECT locstock.stockid, stockmaster.description, locstock.loccode, locstock.bin, locations.locationname, locstock.quantity, locstock.reorderlevel, stockmaster.decimalplaces, stockmaster.serialised, stockmaster.controlled
					FROM locstock, stockmaster, locations
					WHERE locstock.stockid=stockmaster.stockid AND locstock.loccode = '" . $_POST['StockLocation'] . "' AND locstock.loccode=locations.loccode AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY locstock.stockid";
		}
		$LocStockResult = DB_query($SQL);

		echo '<div class="report-table-wrapper">
				<table class="selection">
				<thead>
					<tr><th colspan="9">', DisplayDateTime(), '</th></tr>
					<tr>
						<th>', __('StockID'), '</th>
						<th>', __('Description'), '</th>
						<th>', __('Quantity On Hand'), '</th>
						<th>', __('Bin Loc'), '</th>
						<th>', __('Re-Order Level'), '</th>
						<th>', __('Demand'), '</th>
						<th>', __('Available'), '</th>
						<th>', __('On Order'), '</th>
						<th>', __('Controlled'), '</th>
					</tr>
				</thead>
				<tbody>';

		while($MyRow=DB_fetch_array($LocStockResult)) {
			$StockID = $MyRow['stockid'];
			$DemandQty = GetDemand($StockID, $MyRow['loccode']);
			$QOO = GetQuantityOnOrder($StockID, $MyRow['loccode']);

			if (($_POST['BelowReorderQuantity']=='Below' AND ($MyRow['quantity']-$MyRow['reorderlevel']-$DemandQty)<0)
					OR $_POST['BelowReorderQuantity']=='All' OR $_POST['BelowReorderQuantity']=='NotZero'
					OR ($_POST['BelowReorderQuantity']=='OnOrder' AND $QOO != 0)) {

				if (($_POST['BelowReorderQuantity']=='NotZero') AND (($MyRow['quantity']-$DemandQty)>0)) {
					echo '<tr class="striped_row">
							<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=', mb_strtoupper($MyRow['stockid']), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
							<td class="text">', $MyRow['description'], '</td>
							<td class="number">', locale_number_format($MyRow['quantity'],$MyRow['decimalplaces']), '</td>
							<td>', $MyRow['bin'], '</td>
							<td class="number">', locale_number_format($MyRow['reorderlevel'],$MyRow['decimalplaces']), '</td>
							<td class="number">', locale_number_format($DemandQty,$MyRow['decimalplaces']), '</td>
							<td class="number"><a target="_blank" href="' . $RootPath . '/SelectProduct.php?StockID=', mb_strtoupper($MyRow['stockid']), '">', locale_number_format($MyRow['quantity'] - $DemandQty,$MyRow['decimalplaces']), '</a></td>
							<td class="number">', locale_number_format($QOO,$MyRow['decimalplaces']), '</td>';
					if ($MyRow['serialised'] ==1) {
						echo '<td><a target="_blank" href="' . $RootPath . '/StockSerialItems.php?Serialised=Yes&Location=' . $MyRow['loccode'] . '&StockID=' . $StockID . '">' . __('Serial Numbers') . '</a></td></tr>';
					} elseif ($MyRow['controlled']==1) {
						echo '<td><a target="_blank" href="' . $RootPath . '/StockSerialItems.php?Location=' . $MyRow['loccode'] . '&StockID=' . $StockID . '">' . __('Batches') . '</a></td></tr>';
					} else {
						echo '<td>' . __('Not Controlled') . '</td></tr>';
					}
				} elseif ($_POST['BelowReorderQuantity']!='NotZero') {
					echo '<tr class="striped_row">
							<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=', mb_strtoupper($MyRow['stockid']), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
							<td>', $MyRow['description'], '</td>
							<td class="number">', locale_number_format($MyRow['quantity'],$MyRow['decimalplaces']), '</td>
							<td>', $MyRow['bin'], '</td>
							<td class="number">', locale_number_format($MyRow['reorderlevel'],$MyRow['decimalplaces']), '</td>
							<td class="number">', locale_number_format($DemandQty,$MyRow['decimalplaces']), '</td>
							<td class="number"><a target="_blank" href="' . $RootPath . '/SelectProduct.php?StockID=', mb_strtoupper($MyRow['stockid']), '">', locale_number_format($MyRow['quantity'] - $DemandQty,$MyRow['decimalplaces']), '</a></td>
							<td class="number">', locale_number_format($QOO,$MyRow['decimalplaces']), '</td>';
					if ($MyRow['serialised'] ==1) {
						echo '<td><a target="_blank" href="' . $RootPath . '/StockSerialItems.php?Serialised=Yes&Location=' . $MyRow['loccode'] . '&StockID=' . $StockID . '">' . __('Serial Numbers') . '</a></td></tr>';
					} elseif ($MyRow['controlled']==1) {
						echo '<td><a target="_blank" href="' . $RootPath . '/StockSerialItems.php?Location=' . $MyRow['loccode'] . '&StockID=' . $StockID . '">' . __('Batches') . '</a></td></tr>';
					} else {
						echo '<td>' . __('Not Controlled') . '</td></tr>';
					}
				}
			}
		}
		echo '</tbody></table></div>';
	}

include(__DIR__ . '/includes/footer.php');

?>
