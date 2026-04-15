<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock Movements');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryMovement';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">
			<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
			
			<div class="db-card" style="margin-bottom: 20px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-filter"></i> ' . __('Search Criteria') . '</h3>
				</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Code') . '</label>
						<input type="text" name="StockID" class="db-input" value="', $StockID, '" required="required" placeholder="' . __('e.g. ITEM-001') . '" />
					</div>
					
					<div class="db-form-group">
						<label class="db-label">' . __('Location') . '</label>
						<select name="StockLocation" class="db-select">';
$SQL_Loc = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY locationname";
$ResStkLocs = DB_query($SQL_Loc);
while ($RowLoc = DB_fetch_array($ResStkLocs)) {
	$selected = (isset($_POST['StockLocation']) AND $_POST['StockLocation'] == $RowLoc['loccode']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $RowLoc['loccode'] . '">' . $RowLoc['locationname'] . '</option>';
}
echo '					</select>
					</div>

					<div class="db-form-group">
						<label class="db-label">' . __('From Date') . '</label>
						<input name="AfterDate" type="date" class="db-input" value="', FormatDateForSQL($_POST['AfterDate']), '" />
					</div>
					
					<div class="db-form-group">
						<label class="db-label">' . __('To Date') . '</label>
						<input name="BeforeDate" type="date" class="db-input" value="', FormatDateForSQL($_POST['BeforeDate']), '" />
					</div>

					<button type="submit" name="ShowMoves" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
						<i class="fas fa-sync"></i> ' . __('Show Movements') . '
					</button>
				</div>
			</div>';

// QUICK LINKS CARD
if ($StockID != '') {
	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-external-link-alt"></i> ' . __('Related Inquiries') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 10px;">
				<a href="', $RootPath, '/StockStatus.php?StockID=', urlencode($StockID), '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-info-circle"></i> ' . __('Show Stock Status') . '
				</a>
				<a href="', $RootPath, '/StockUsage.php?StockID=', urlencode($StockID), '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; margin-bottom: 8px; font-size: 0.8rem;">
					<i class="fas fa-chart-line"></i> ' . __('Show Stock Usage') . '
				</a>
				<a href="', $RootPath, '/SelectSalesOrder.php?SelectedStockItem=', urlencode($StockID), '" class="db-btn db-input-light" style="width: 100%; justify-content: flex-start; font-size: 0.8rem;">
					<i class="fas fa-shopping-cart"></i> ' . __('Search Orders') . '
				</a>
			</div>
		  </div>';
}

echo '		</form>
	</aside>';

echo '<main class="db-col-main">';


$SQLBeforeDate = FormatDateForSQL($_POST['BeforeDate']);
$SQLAfterDate = FormatDateForSQL($_POST['AfterDate']);

$SQL = "SELECT stockmoves.stockid,
				systypes.typename,
				stockmoves.stkmoveno,
				stockmoves.type,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.userid,
				stockmoves.debtorno,
				stockmoves.branchcode,
				custbranch.brname,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.price,
				stockmoves.discountpercent,
				stockmoves.newqoh,
				stockmoves.narrative,
				stockmaster.decimalplaces,
				stockmaster.controlled,
				stockmaster.serialised
		FROM stockmoves
		INNER JOIN systypes
			ON stockmoves.type=systypes.typeid
		INNER JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
		LEFT JOIN custbranch
			ON stockmoves.debtorno=custbranch.debtorno
			AND stockmoves.branchcode = custbranch.branchcode
		WHERE  stockmoves.loccode='" . $_POST['StockLocation'] . "'
			AND stockmoves.trandate >= '" . $SQLAfterDate . "'
			AND stockmoves.stockid = '" . $StockID . "'
			AND stockmoves.trandate <= '" . $SQLBeforeDate . "'
			AND hidemovt=0
		ORDER BY stkmoveno DESC";

$ErrMsg = __('The stock movements for the selected criteria could not be retrieved because') . ' - ';

$MovtsResult = DB_query($SQL, $ErrMsg);

if (DB_num_rows($MovtsResult) > 0) {
	$MyRow = DB_fetch_array($MovtsResult);

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-exchange-alt"></i> ' . __('Movement History') . '</h3>
				<span class="db-badge db-badge-primary">' . $StockID . '</span>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
					<table class="db-table">
						<thead>
							<tr>
								<th>', __('Type / Number'), '</th>
								<th>', __('Date'), '</th>
								<th>', __('Customer / User'), '</th>
								<th class="text-right">', __('Quantity'), '</th>
								<th>', __('Reference'), '</th>
								<th class="text-right">', __('Price'), '</th>
								<th class="text-right">', __('New Qty'), '</th>
								<th>', __('Narrative'), '</th>';
	if ($MyRow['controlled'] == 1) {
		echo '					<th>', __('Serial No.'), '</th>';
	}
	echo '					</tr>
						</thead>
						<tbody>';

	DB_data_seek($MovtsResult, 0);

	while ($MyRow = DB_fetch_array($MovtsResult)) {

		$DisplayTranDate = ConvertSQLDate($MyRow['trandate']);

		$SerialSQL = "SELECT serialno, moveqty FROM stockserialmoves WHERE stockmoveno='" . $MyRow['stkmoveno'] . "'";
		$SerialResult = DB_query($SerialSQL);

		$SerialText = '';
		while ($SerialRow = DB_fetch_array($SerialResult)) {
			if ($MyRow['serialised'] == 1) {
				$SerialText.= $SerialRow['serialno'] . '<br />';
			} else {
				$SerialText.= $SerialRow['serialno'] . ' Qty- ' . $SerialRow['moveqty'] . '<br />';
			}
		}

		$link = '';
		if ($MyRow['type'] == 10) {
			$link = '<a class="db-link" target="_blank" href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . urlencode($MyRow['transno']) . '&amp;InvOrCredit=Invoice&View=Yes">' . $MyRow['typename'] . '</a>';
		} elseif ($MyRow['type'] == 11) {
			$link = '<a class="db-link" target="_blank" href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . urlencode($MyRow['transno']) . '&amp;InvOrCredit=Credit">' . $MyRow['typename'] . '</a>';
		} else {
			$link = $MyRow['typename'];
		}

		echo '			<tr class="striped_row">
							<td>
								<div class="db-font-bold">' . $link . '</div>
								<div style="font-size: 0.75rem; color: var(--text-muted);">' . __('Trans #') . ' ' . $MyRow['transno'] . '</div>
							</td>
							<td>' . $DisplayTranDate . '</td>
							<td>
								<div class="db-font-bold">' . ($MyRow['brname'] ?: $MyRow['debtorno']) . '</div>
								<div style="font-size: 0.75rem; color: var(--text-muted);">' . __('User') . ': ' . $MyRow['userid'] . '</div>
							</td>
							<td class="text-right db-font-bold" style="color: var(--primary);">' . locale_number_format($MyRow['qty'], $MyRow['decimalplaces']) . '</td>
							<td>' . $MyRow['reference'] . '</td>
							<td class="text-right">
								<div class="db-font-bold">' . locale_number_format($MyRow['price'], $_SESSION['CompanyRecord']['decimalplaces']) . '</div>
								<div style="font-size: 0.75rem; color: var(--text-muted);">' . locale_number_format($MyRow['discountpercent'] * 100, 2) . '% Disc</div>
							</td>
							<td class="text-right db-font-bold" style="color: var(--text-muted);">' . locale_number_format($MyRow['newqoh'], $MyRow['decimalplaces']) . '</td>
							<td style="font-size: 0.8rem;">' . $MyRow['narrative'] . '</td>';
		if ($MyRow['controlled'] == 1) {
			echo '			<td style="font-size: 0.75rem;">' . $SerialText . '</td>';
		}
		echo '			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';
} else {
	if ($StockID != '') {
		echo '<div class="db-status-bar db-status-info">
				<div class="db-status-icon"><i class="fas fa-info-circle"></i></div>
				<div class="db-status-text">' . __('No stock movements found for the selected criteria.') . '</div>
			  </div>';
	} else {
		echo '<div class="db-status-bar db-status-info">
				<div class="db-status-icon"><i class="fas fa-arrow-left"></i></div>
				<div class="db-status-text">' . __('Please enter a stock code and select filters in the sidebar to view movements.') . '</div>
			  </div>';
	}
}

echo '	</main>
	</div>'; // end db-bottom-layout


include(__DIR__ . '/includes/footer.php');
