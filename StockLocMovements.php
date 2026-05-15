<?php

require(__DIR__ . '/includes/session.php');

$Title = __('All Stock Movements By Location');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['BeforeDate'])){$_POST['BeforeDate'] = ConvertSQLDate($_POST['BeforeDate']);}
if (isset($_POST['AfterDate'])){$_POST['AfterDate'] = ConvertSQLDate($_POST['AfterDate']);}

echo '<p class="page_title_text">
		<img src="', $RootPath, '/css/', $_SESSION['Theme'], '/images/magnifier.png" title="', __('Search'), '" alt="" />', ' ', $Title, '
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
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
		.form-group select, .form-group input {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus, .form-group input:focus {
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
	echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	
	echo '<div class="form-grid">';
	
	echo '<div class="form-group">
			<label>', __('From Stock Location'), '</label>
			<select required="required" name="StockLocation">';
	$SQL = "SELECT locationname, locations.loccode FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
			ORDER BY locationname";
	echo '<option selected="selected" value="All">', __('All Locations'), '</option>';
	$ResultStkLocs = DB_query($SQL);
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockLocation']) && $_POST['StockLocation'] == $MyRow['loccode']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
	}
	echo '  </select>
		  </div>';

	if (!isset($_POST['BeforeDate']) or !Is_date($_POST['BeforeDate'])) { $_POST['BeforeDate'] = date($_SESSION['DefaultDateFormat']); }
	if (!isset($_POST['AfterDate']) or !Is_date($_POST['AfterDate'])) { $_POST['AfterDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') - 1, date('d'), date('y'))); }

	echo '<div class="form-group">
			<label>', __('Show Movements before'), '</label>
			<input type="date" name="BeforeDate" required="required" value="', FormatDateForSQL($_POST['BeforeDate']), '" />
		  </div>';

	echo '<div class="form-group">
			<label>', __('But after'), '</label>
			<input type="date" name="AfterDate" required="required" value="', FormatDateForSQL($_POST['AfterDate']), '" />
		  </div>';
	
	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input type="submit" name="ShowMoves" value="', __('Show Stock Movements'), '" />
		  </div>';

	if ($_POST['StockLocation'] == 'All') { $_POST['StockLocation'] = '%%'; }

	$SQLBeforeDate = FormatDateForSQL($_POST['BeforeDate']);
	$SQLAfterDate = FormatDateForSQL($_POST['AfterDate']);

	$SQL = "SELECT stockmoves.stockid, stockmoves.stkmoveno, systypes.typename, stockmoves.type, stockmoves.transno, stockmoves.trandate, stockmoves.debtorno, stockmoves.branchcode, stockmoves.qty, stockmoves.reference, stockmoves.price, stockmoves.discountpercent, stockmoves.newqoh, stockmaster.controlled, stockmaster.serialised, stockmaster.decimalplaces
			FROM stockmoves
			INNER JOIN systypes ON stockmoves.type=systypes.typeid
			INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid
			WHERE stockmoves.loccode = '" . $_POST['StockLocation'] . "'
				AND stockmoves.trandate >= '" . $SQLAfterDate . "'
				AND stockmoves.trandate <= '" . $SQLBeforeDate . "'
				AND hidemovt=0
			ORDER BY stkmoveno DESC";
	$MovtsResult = DB_query($SQL);

	if (DB_num_rows($MovtsResult) > 0) {
		echo '<div class="report-table-wrapper">
				<table class="selection">
				<thead>
				<tr>
					<th>', __('Item Code'), '</th>
					<th>', __('Type'), '</th>
					<th>', __('Trans No'), '</th>
					<th>', __('Date'), '</th>
					<th>', __('Customer'), '</th>
					<th>', __('Quantity'), '</th>
					<th>', __('Reference'), '</th>
					<th>', __('Price'), '</th>
					<th>', __('Discount'), '</th>
					<th>', __('Quantity on Hand'), '</th>
					<th>', __('Serial No.'), '</th>
				</tr>
				</thead>
				<tbody>';

		while ($MyRow = DB_fetch_array($MovtsResult)) {
			$DisplayTranDate = ConvertSQLDate($MyRow['trandate']);
			$SerialSQL = "SELECT serialno, moveqty FROM stockserialmoves WHERE stockmoveno='" . $MyRow['stkmoveno'] . "'";
			$SerialResult = DB_query($SerialSQL);
			$SerialText = '';
			while ($SerialRow = DB_fetch_array($SerialResult)) {
				$SerialText .= ($MyRow['serialised'] == 1) ? $SerialRow['serialno'] . '<br />' : $SerialRow['serialno'] . ' Qty- ' . $SerialRow['moveqty'] . '<br />';
			}

			echo '<tr class="striped_row">
					<td><a target="_blank" href="', $RootPath, '/StockStatus.php?StockID=', mb_strtoupper(urlencode($MyRow['stockid'])), '">', mb_strtoupper($MyRow['stockid']), '</a></td>
					<td>', $MyRow['typename'], '</td>
					<td>', $MyRow['transno'], '</td>
					<td>', $DisplayTranDate, '</td>
					<td>', $MyRow['debtorno'], '</td>
					<td class="number">', locale_number_format($MyRow['qty'], $MyRow['decimalplaces']), '</td>
					<td>', $MyRow['reference'], '</td>
					<td class="number">', locale_number_format($MyRow['price'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
					<td class="number">', locale_number_format($MyRow['discountpercent'] * 100, 2), '%</td>
					<td class="number">', locale_number_format($MyRow['newqoh'], $MyRow['decimalplaces']), '</td>
					<td>', $SerialText, '</td>
				</tr>';
		}
		echo '</tbody></table></div>';
	}
	echo '</form></div>';

include(__DIR__ . '/includes/footer.php');
?>
