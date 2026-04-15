<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock Cost Update');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['StockID'])){
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])){
	$StockID =trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

if (isset($_POST['UpdateData']) && $StockID != ''){

	$SQL = "SELECT materialcost, labourcost, overheadcost, mbflag, sum(quantity) as totalqoh
			FROM stockmaster INNER JOIN locstock ON stockmaster.stockid=locstock.stockid
			WHERE stockmaster.stockid='".$StockID."'
			GROUP BY materialcost, labourcost, overheadcost, mbflag";
	$OldResult = DB_query($SQL);
	$OldRow = DB_fetch_array($OldResult);
	
	$_POST['QOH'] = $OldRow['totalqoh'];
	$_POST['OldMaterialCost'] = $OldRow['materialcost'];
	if ($OldRow['mbflag']=='M') {
		$_POST['OldLabourCost'] = $OldRow['labourcost'];
		$_POST['OldOverheadCost'] = $OldRow['overheadcost'];
	} else {
		$_POST['OldLabourCost'] = 0;
		$_POST['OldOverheadCost'] = 0;
		$_POST['LabourCost'] = 0;
		$_POST['OverheadCost'] = 0;
	}

 	$OldCost = $_POST['OldMaterialCost'] + $_POST['OldLabourCost'] + $_POST['OldOverheadCost'];
   	$NewCost = filter_number_format($_POST['MaterialCost']) + filter_number_format($_POST['LabourCost']) + filter_number_format($_POST['OverheadCost']);

	if (abs($NewCost - $OldCost) > pow(10,-($_SESSION['StandardCostDecimalPlaces']+1))){
		DB_Txn_Begin();
		ItemCostUpdateGL($StockID, $NewCost, $OldCost, $_POST['QOH']);

		$SQL = "UPDATE stockmaster
				SET	materialcost='" . filter_number_format($_POST['MaterialCost']) . "',
					labourcost='" . filter_number_format($_POST['LabourCost']) . "',
					overheadcost='" . filter_number_format($_POST['OverheadCost']) . "',
					lastcost='" . $OldCost . "',
					lastcostupdate = CURRENT_DATE
				WHERE stockid='" . $StockID . "'";
		DB_query($SQL, '', '', true);
		DB_Txn_Commit();
		UpdateCost($StockID);
		prnMsg(__('Standard costs updated successfully'), 'success');
	}
}

$ErrMsg = __('The cost details for the stock item could not be retrieved because');
$Result = DB_query("SELECT description, units, lastcost, actualcost, materialcost, labourcost, overheadcost, mbflag, stocktype, lastcostupdate, sum(quantity) as totalqoh
					FROM stockmaster INNER JOIN locstock ON stockmaster.stockid=locstock.stockid
					INNER JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid
					WHERE stockmaster.stockid='" . $StockID . "'
					GROUP BY description, units, lastcost, actualcost, materialcost, labourcost, overheadcost, mbflag, stocktype, lastcostupdate", $ErrMsg);
$MyRow = DB_fetch_array($Result);

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title"><i class="fas fa-file-invoice-dollar"></i> ' . $Title . '</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/SelectProduct.php" class="db-btn db-btn-outline db-btn-small"><i class="fas fa-list"></i> ' . __('Back to Items') . '</a>
			</div>
		</div>

		<div class="db-card" style="margin-bottom: 25px;">
			<div class="db-card-body">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-grid db-grid-3" style="align-items: flex-end;">
						<div class="db-form-group">
							<label class="db-label">' . __('Standard Cost Target') . '</label>
							<input type="text" name="StockID" class="db-input" required value="' . $StockID . '" placeholder="' . __('Enter SKU...') . '" />
						</div>
						<div class="db-form-group">
							<button type="submit" name="Show" class="db-btn db-btn-primary"><i class="fas fa-money-check-alt"></i> ' . __('Load Cost Data') . '</button>
						</div>
						<div class="text-right">';
if ($StockID != '') {
	echo '				<div class="db-font-bold text-primary">' . $StockID . ' - ' . $MyRow['description'] . '</div>
						<div class="db-muted" style="font-size: 0.85rem;">' . __('Inventory Units') . ': ' . $MyRow['units'] . '</div>';
}
echo '					</div>
					</div>
				</form>
			</div>
		</div>';

if ($StockID != '') {
	if (($MyRow['mbflag']=='D' AND $MyRow['stocktype'] != 'L') OR $MyRow['mbflag']=='A' OR $MyRow['mbflag']=='K'){
	   $type = ($MyRow['mbflag']=='D' ? __('Service') : ($MyRow['mbflag']=='A' ? __('Assembly') : __('Kit')));
	   echo '<div class="db-card"><div class="db-card-body text-center p-5">';
	   echo '<i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>';
	   echo '<h3 class="db-font-bold">' . sprintf(__('%s Item Restricted'), $type) . '</h3>';
	   echo '<p class="db-muted">' . __('Standard costs cannot be modified directly for this item type.') . '</p>';
	   echo '</div></div>';
	} else {
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" name="StockID" value="' . $StockID . '" />
				<input type="hidden" name="OldMaterialCost" value="' . $MyRow['materialcost'] .'" />
				<input type="hidden" name="OldLabourCost" value="' . $MyRow['labourcost'] .'" />
				<input type="hidden" name="OldOverheadCost" value="' . $MyRow['overheadcost'] .'" />
				<input type="hidden" name="QOH" value="' . $MyRow['totalqoh'] .'" />';

		echo '<div class="db-grid db-grid-3">
				<div class="db-card">
					<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-history"></i> ' . __('Reference Costs') . '</h3></div>
					<div class="db-card-body">
						<div class="db-form-group">
							<label class="db-label">' . __('Total Stock On Hand') . '</label>
							<div class="db-font-bold">' . locale_number_format($MyRow['totalqoh'], 2) . ' ' . $MyRow['units'] . '</div>
						</div>
						<div class="db-form-group">
							<label class="db-label">' . __('Last Updated') . '</label>
							<div class="db-font-bold">' . ($MyRow['lastcostupdate'] != '0000-00-00' ? ConvertSQLDate($MyRow['lastcostupdate']) : __('Never')) . '</div>
						</div>
						<div class="db-form-group">
							<label class="db-label">' . __('Current Standard') . '</label>
							<div class="db-badge db-badge-primary" style="font-size: 1rem;">' . $_SESSION['CompanyRecord']['currencydefault'] . ' ' . locale_number_format($MyRow['materialcost']+$MyRow['labourcost']+$MyRow['overheadcost'], $_SESSION['StandardCostDecimalPlaces']) . '</div>
						</div>
					</div>
				</div>

				<div class="db-card" style="grid-column: span 2;">
					<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Update Valuation') . '</h3></div>
					<div class="db-card-body">';

		/* CostUpdate security check */
		if (! in_array($_SESSION['PageSecurityArray']['CostUpdate'], $_SESSION['AllowedPageSecurityTokens'])){
			echo '<div class="db-alert db-alert-info">' . __('You do not have the required security tokens to modify standard costs.') . '</div>';
			echo '<div class="db-grid db-grid-2">
					<div><label class="db-label">' . __('Material Cost') . '</label><div class="db-font-bold">' . locale_number_format($MyRow['materialcost'], $_SESSION['StandardCostDecimalPlaces']) . '</div></div>
					<div><label class="db-label">' . __('Labour/Overhead') . '</label><div class="db-font-bold">' . locale_number_format($MyRow['labourcost']+$MyRow['overheadcost'], $_SESSION['StandardCostDecimalPlaces']) . '</div></div>
				  </div>';
		} else {
			if ($MyRow['mbflag'] == 'M') {
				echo '<div class="db-grid db-grid-3">
						<div class="db-form-group">
							<label class="db-label">' . __('Material Cost') . '</label>
							<input type="text" name="MaterialCost" class="db-input text-right" value="' . locale_number_format($MyRow['materialcost'], $_SESSION['StandardCostDecimalPlaces']) . '" />
						</div>
						<div class="db-form-group">
							<label class="db-label">' . __('Labour Cost') . '</label>
							<input type="text" name="LabourCost" class="db-input text-right" value="' . locale_number_format($MyRow['labourcost'], $_SESSION['StandardCostDecimalPlaces']) . '" />
						</div>
						<div class="db-form-group">
							<label class="db-label">' . __('Overhead Cost') . '</label>
							<input type="text" name="OverheadCost" class="db-input text-right" value="' . locale_number_format($MyRow['overheadcost'], $_SESSION['StandardCostDecimalPlaces']) . '" />
						</div>
					  </div>';
			} else {
				echo '<input type="hidden" name="LabourCost" value="0" />
					  <input type="hidden" name="OverheadCost" value="0" />
					  <div class="db-form-group">
						<label class="db-label">' . __('Standard Purchase Cost') . '</label>
						<input type="text" name="MaterialCost" class="db-input text-right" style="max-width: 300px;" value="' . locale_number_format($MyRow['materialcost'], $_SESSION['StandardCostDecimalPlaces']) . '" />
					  </div>';
			}
			echo '<div style="margin-top: 20px; border-top: 1px dashed var(--border-soft); padding-top: 20px; text-align: right;">
					<button type="submit" name="UpdateData" class="db-btn db-btn-primary"><i class="fas fa-check-circle"></i> ' . __('Commit Cost Update') . '</button>
				  </div>';
		}
		echo '		</div>
				</div>
			  </div>';

		// Analytics Links
		echo '<div class="db-card" style="margin-top: 25px;">
				<div class="db-card-body">
					<div class="db-grid db-grid-4">
						<a href="' . $RootPath . '/StockStatus.php?StockID=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-info-circle"></i> ' . __('Inventory Status') . '</a>
						<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-exchange-alt"></i> ' . __('Movements') . '</a>
						<a href="' . $RootPath . '/StockUsage.php?StockID=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-chart-bar"></i> ' . __('Usage Trend') . '</a>
						<a href="' . $RootPath . '/SelectSalesOrder.php?SelectedStockItem=' . $StockID . '" class="db-btn db-btn-outline-primary db-btn-small"><i class="fas fa-shopping-basket"></i> ' . __('Show Demand') . '</a>
					</div>
				</div>
			  </div>';
	}
	echo '</form>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
