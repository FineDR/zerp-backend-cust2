<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock On Hand By Date');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['OnHandDate'])){$_POST['OnHandDate'] = ConvertSQLDate($_POST['OnHandDate']);}

echo '<p class="page_title_text" >
		<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/inventory.png" title="' . __('Inventory') . '" alt="" /><b>' . $Title . '</b>
	</p>';

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
		.form-group select, .form-group input:not([type="checkbox"]) {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus, .form-group input:not([type="checkbox"]):focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			outline: none;
		}
		.checkbox-group {
			flex-direction: row;
			align-items: center;
			gap: 12px;
			padding: 12px;
			background: var(--primary-soft);
			border-radius: var(--radius-sm);
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 15px;
			border-top: 1px solid var(--border-soft);
			padding-top: 25px;
		}
		.button-group input[type="submit"] {
			padding: 12px 35px;
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
	echo '<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="form-grid">';
	
	echo '<div class="form-group">
			<label>' . __('Stock Category') . '</label>
			<select required="required" name="StockCategory">
				<option value="All">' . __('All') . '</option>';
	$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockCategory']) && $_POST['StockCategory'] == $MyRow['categoryid']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Stock Location') . '</label>
			<select required="required" name="StockLocation">';
	$SQL = "SELECT locationname, locations.loccode FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockLocation']) && $_POST['StockLocation'] == $MyRow['loccode']) ? 'selected="selected"' : (!isset($_POST['StockLocation']) && $MyRow['loccode']==$_SESSION['UserStockLocation'] ? 'selected="selected"' : '');
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '  </select>
		  </div>';

	if (!isset($_POST['OnHandDate'])) { $_POST['OnHandDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 0, date('y'))); }
	echo '<div class="form-group">
			<label>' . __('On-Hand On Date') . '</label>
			<input type="date" name="OnHandDate" required="required" value="' . FormatDateForSQL($_POST['OnHandDate']) . '" />
		  </div>';

	echo '<div class="form-group checkbox-group">
			<input type="checkbox" name="ShowZeroStocks" id="ShowZeroStocks" ' . (isset($_POST['ShowZeroStocks']) ? 'checked' : '') . ' />
			<label for="ShowZeroStocks">' . __('Include zero stocks') . '</label>
		  </div>';
	
	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input type="submit" name="ShowStatus" value="' . __('Show Stock Status') . '" />
		  </div>';
	echo '</form></div>';

$TotalQuantity = 0;

if (isset($_POST['ShowStatus']) and is_date($_POST['OnHandDate'])) {
	if ($_POST['StockCategory'] == 'All') {
		$SQL = "SELECT stockid,
						 description,
						 decimalplaces,
						 controlled
					 FROM stockmaster
					 WHERE (mbflag='M' OR mbflag='B')";
	} else {
		$SQL = "SELECT stockid,
						description,
						decimalplaces,
						controlled
					 FROM stockmaster
					 WHERE categoryid = '" . $_POST['StockCategory'] . "'
					 AND (mbflag='M' OR mbflag='B')";
	}

	$ErrMsg = __('The stock items in the category selected cannot be retrieved because');

	$StockResult = DB_query($SQL, $ErrMsg);

	$SQLOnHandDate = FormatDateForSQL($_POST['OnHandDate']);

	echo '<div class="report-table-wrapper">
			<table class="selection">
			<thead>
			<tr>
				<th>' . __('Item Code') . '</th>
				<th>' . __('Description') . '</th>
				<th>' . __('Quantity On Hand') . '</th>
				<th>' . __('Controlled') . '</th>
			</tr>
			</thead>
			<tbody>';

	while ($MyRow = DB_fetch_array($StockResult)) {

		if (isset($_POST['ShowZeroStocks'])) {
			$SQL = "SELECT stockid,
							newqoh
						FROM stockmoves
						WHERE stockmoves.trandate <= '" . $SQLOnHandDate . "'
							AND stockid = '" . $MyRow['stockid'] . "'
							AND loccode = '" . $_POST['StockLocation'] . "'
						ORDER BY stkmoveno DESC LIMIT 1";
		} else {
			$SQL = "SELECT stockid,
							newqoh
						FROM stockmoves
						WHERE stockmoves.trandate <= '" . $SQLOnHandDate . "'
							AND stockid = '" . $MyRow['stockid'] . "'
							AND loccode = '" . $_POST['StockLocation'] . "'
							AND newqoh > 0
						ORDER BY stkmoveno DESC LIMIT 1";
		}

		$ErrMsg = __('The stock held as at') . ' ' . $_POST['OnHandDate'] . ' ' . __('could not be retrieved because');

		$LocStockResult = DB_query($SQL, $ErrMsg);

		$NumRows = DB_num_rows($LocStockResult);

		while ($LocQtyRow = DB_fetch_array($LocStockResult)) {

			if ($MyRow['controlled'] == 1) {
				$Controlled = __('Yes');
			} else {
				$Controlled = __('No');
			}

			if ($NumRows == 0) {
				echo '<tr class="striped_row">
						<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=' . mb_strtoupper($MyRow['stockid']) . '>' . mb_strtoupper($MyRow['stockid']) . '</a></td>
						<td>' . $MyRow['description'] . '</td>
						<td class="number">0</td>
					</tr>';
			} else {
				echo '<tr class="striped_row">
						<td><a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=', mb_strtoupper($MyRow['stockid']), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
						<td>', $MyRow['description'], '</td>
						<td class="number">', locale_number_format($LocQtyRow['newqoh'], $MyRow['decimalplaces']), '</td>
						<td class="number">', $Controlled, '</td>
					</tr>';

				$TotalQuantity+= $LocQtyRow['newqoh'];
			}
			//end of page full new headings if

		}

	} //end of while loop
	echo '</tbody><tfoot>
			<tr class="total_row">
				<td colspan="2" class="number"><strong>' . __('Total Quantity') . ':</strong></td>
				<td class="number"><strong>' . locale_number_format($TotalQuantity, 2) . '</strong></td>
				<td></td>
			</tr>
			</tfoot>
			</table></div>';
}

include(__DIR__ . '/includes/footer.php');
