<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Preferred Supplier Purchasing');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2
echo '
<style>
	:root {
		--primary: hsl(197, 92%, 47%); 
		--primary-hover: hsl(197, 92%, 38%);
		--primary-dark: hsl(197, 75%, 22%);
		--primary-bg: hsl(197, 65%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--text-main: hsl(197, 15%, 12%);
		--text-muted: hsl(197, 8%, 50%);
		--card-bg: #ffffff;
		--border-color: hsl(220, 15%, 88%);
		--radius: 12px;
		--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
	}

	body {
		background-color: var(--bg-workspace);
		font-family: "Inter", -apple-system, sans-serif;
		color: var(--text-main);
	}

	.aw-container {
		padding: 24px;
	}

	.aw-page-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 24px;
	}

	.aw-breadcrumb {
		font-size: 0.72rem;
		font-weight: 800;
		color: var(--primary);
		text-transform: uppercase;
		letter-spacing: 0.1em;
		margin-bottom: 4px;
	}

	.aw-page-title {
		font-size: 1.85rem;
		font-weight: 950;
		letter-spacing: -0.04em;
		color: var(--primary-dark);
		margin: 0;
	}

	.aw-grid-search {
		display: grid;
		gap: 24px;
		grid-template-columns: 1fr;
	}

	@media (min-width: 1024px) {
		.aw-grid-search {
			grid-template-columns: 350px 1fr;
			align-items: start;
		}
	}

	.aw-card {
		background: var(--card-bg);
		border-radius: var(--radius);
		border: 1px solid var(--border-color);
		box-shadow: var(--shadow-sm);
		overflow: hidden;
		margin-bottom: 24px;
	}

	.aw-card-header {
		padding: 12px 16px;
		border-bottom: 1px solid var(--border-color);
		background-color: #ffffff;
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.aw-card-title {
		font-size: 0.82rem;
		font-weight: 850;
		color: var(--primary-dark);
		text-transform: uppercase;
		letter-spacing: 0.05em;
		margin: 0;
	}

	.aw-card-body {
		padding: 16px;
	}

	.aw-table-wrapper {
		overflow-x: auto;
		width: 100%;
	}

	.aw-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 0.8rem;
	}

	.aw-table th {
		text-align: left;
		padding: 12px 16px;
		background: #fbfcfd;
		color: var(--text-muted);
		font-weight: 800;
		text-transform: uppercase;
		font-size: 0.62rem;
		letter-spacing: 0.05em;
		border-bottom: 2px solid var(--border-color);
		white-space: nowrap;
	}

	.aw-table td {
		padding: 12px 16px;
		border-bottom: 1px solid #f1f5f9;
		vertical-align: middle;
	}

	.aw-table tr:hover td {
		background-color: var(--primary-bg);
	}

	.aw-label {
		display: block;
		font-size: 0.72rem;
		font-weight: 850;
		color: var(--primary-dark);
		text-transform: uppercase;
		margin-bottom: 8px;
		letter-spacing: 0.025em;
	}

	.aw-input, .aw-select {
		width: 100%;
		padding: 10px 12px;
		border-radius: 8px;
		border: 1px solid var(--border-color);
		font-size: 0.85rem;
		font-weight: 500;
		outline: none;
		transition: all 0.2s;
		background: #fff;
	}

	.aw-input:focus, .aw-select:focus {
		border-color: var(--primary);
		box-shadow: 0 0 0 3px var(--primary-bg);
	}

	.aw-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 10px 20px;
		border-radius: 8px;
		font-weight: 750;
		font-size: 0.85rem;
		cursor: pointer;
		transition: all 0.2s;
		border: none;
		text-decoration: none;
	}

	.aw-btn-primary {
		background: var(--primary);
		color: white;
	}

	.aw-btn-primary:hover {
		background: var(--primary-hover);
		transform: translateY(-1px);
	}

	.aw-btn-secondary {
		background: #f8fafc;
		border: 1px solid var(--border-color);
		color: var(--text-main);
	}

	.aw-btn-secondary:hover {
		background: #f1f5f9;
	}

	.aw-hist-col {
		font-size: 0.68rem;
		line-height: 1.2;
		color: var(--text-muted);
	}
	
	.aw-hist-val {
		font-weight: 800;
		color: var(--text-main);
	}
</style>
<div class="aw-container">';

if (isset($_POST['CreatePO']) AND isset($_POST['Supplier'])){
	include(__DIR__ . '/includes/SQL_CommonFunctions.php');
	$InputError =0; 

	$PurchItems = array();
	$OrderValue =0;
	foreach ($_POST as $FormVariable => $Quantity) {
		if (mb_strpos($FormVariable,'OrderQty')!==false) {
			if ($Quantity > 0) {
				$StockID = $_POST['StockID' . mb_substr($FormVariable,8)];
				$PurchItems[$StockID]['Quantity'] = filter_number_format($Quantity);

				$SQL = "SELECT description,
							units,
							stockact
						FROM stockmaster INNER JOIN stockcategory
						ON stockcategory.categoryid = stockmaster.categoryid
						WHERE  stockmaster.stockid = '". $StockID . "'";

				$ItemResult = DB_query($SQL);
				if (DB_num_rows($ItemResult)==1){
					$ItemRow = DB_fetch_array($ItemResult);

					$SQL = "SELECT price,
								conversionfactor,
								supplierdescription,
								suppliersuom,
								suppliers_partno,
								leadtime,
								MAX(purchdata.effectivefrom) AS latesteffectivefrom
							FROM purchdata
							WHERE purchdata.supplierno = '" . $_POST['Supplier'] . "'
								AND purchdata.effectivefrom <= CURRENT_DATE
								AND purchdata.stockid = '". $StockID . "'
							GROUP BY purchdata.price,
									purchdata.conversionfactor,
									purchdata.supplierdescription,
									purchdata.suppliersuom,
									purchdata.suppliers_partno,
									purchdata.leadtime
							ORDER BY latesteffectivefrom DESC";

					$PurchDataResult = DB_query($SQL);
					if (DB_num_rows($PurchDataResult)>0){ 
						$PurchRow = DB_fetch_array($PurchDataResult);

						$SQL = "SELECT discountpercent,
										discountamount
								FROM supplierdiscounts
								WHERE supplierno= '" . $_POST['Supplier'] . "'
									AND effectivefrom <= CURRENT_DATE
									AND (effectiveto >= CURRENT_DATE
										OR effectiveto ='1000-01-01')
									AND stockid = '". $StockID . "'";

						$ItemDiscountPercent = 0;
						$ItemDiscountAmount = 0;
						$DiscountResult = DB_query($SQL);
						while ($DiscountRow = DB_fetch_array($DiscountResult)) {
							$ItemDiscountPercent += $DiscountRow['discountpercent'];
							$ItemDiscountAmount += $DiscountRow['discountamount'];
						}
						
						$PurchItems[$StockID]['Price'] = ($PurchRow['price']*(1-$ItemDiscountPercent) - $ItemDiscountAmount)/$PurchRow['conversionfactor'];
						$PurchItems[$StockID]['ConversionFactor'] = $PurchRow['conversionfactor'];
						$PurchItems[$StockID]['GLCode'] = $ItemRow['stockact'];
						$PurchItems[$StockID]['SupplierDescription'] = $PurchRow['suppliers_partno'] .' - ';
						if (mb_strlen($PurchRow['supplierdescription'])>2){
							$PurchItems[$StockID]['SupplierDescription'] .= $PurchRow['supplierdescription'];
						} else {
							$PurchItems[$StockID]['SupplierDescription'] .= $ItemRow['description'];
						}
						$PurchItems[$StockID]['UnitOfMeasure'] = $PurchRow['suppliersuom'];
						$PurchItems[$StockID]['SuppliersPartNo'] = $PurchRow['suppliers_partno'];
						$LeadTime = $PurchRow['leadtime'];
						$PurchItems[$StockID]['DeliveryDate'] = DateAdd(date($_SESSION['DefaultDateFormat']),'d',$LeadTime);
					} else { 
						$PurchItems[$StockID]['Price'] = 0;
						$PurchItems[$StockID]['ConversionFactor'] = 1;
						$PurchItems[$StockID]['SupplierDescription'] = 	$ItemRow['description'];
						$PurchItems[$StockID]['UnitOfMeasure'] = $ItemRow['units'];
						$PurchItems[$StockID]['SuppliersPartNo'] = 'each';
						$LeadTime = 1;
						$PurchItems[$StockID]['DeliveryDate'] = date($_SESSION['DefaultDateFormat']);
					}
					$OrderValue += $PurchItems[$StockID]['Quantity']*$PurchItems[$StockID]['Price'];
				} else { 
					$InputError =1;
					prnmsg(__('An error occurred while creating order lines for item') . ' ' . $StockID,'error');
				}
			} 
		} 
	}

	if ($InputError==0) { 
		$SQL = "SELECT suppliers.suppname, suppliers.currcode, currencies.decimalplaces, currencies.rate, suppliers.paymentterms, suppliers.address1, suppliers.address2, suppliers.address3, suppliers.address4, suppliers.address5, suppliers.address6, suppliers.telephone
				FROM suppliers INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_POST['Supplier'] . "'";
		$SupplierResult = DB_query($SQL);
		$SupplierRow = DB_fetch_array($SupplierResult);
		$SQL = "SELECT deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, contact FROM locations WHERE loccode='" . $_SESSION['UserStockLocation'] . "'";
		$LocnAddrResult = DB_query($SQL);
		$LocnRow = DB_fetch_array($LocnAddrResult);
		$OrderNo = GetNextTransNo(18);

		$SQL = "INSERT INTO purchorders ( orderno, supplierno, orddate, rate, initiator, intostocklocation, deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, suppdeladdress1, suppdeladdress2, suppdeladdress3, suppdeladdress4, suppdeladdress5, suppdeladdress6, supptel, contact, revised, deliveryby, status, stat_comment, deliverydate, paymentterms, allowprint)
				VALUES(	'" . $OrderNo . "', '" . $_POST['Supplier'] . "', CURRENT_DATE, '" . $SupplierRow['rate'] . "', '" . $_SESSION['UserID'] . "', '" . $_SESSION['UserStockLocation'] . "', '" . $LocnRow['deladd1'] . "', '" . $LocnRow['deladd2'] . "', '" . $LocnRow['deladd3'] . "', '" . $LocnRow['deladd4'] . "', '" . $LocnRow['deladd5'] . "', '" . $LocnRow['deladd6'] . "', '" . $LocnRow['tel'] . "', '" . $SupplierRow['address1'] . "', '" . $SupplierRow['address2'] . "', '" . $SupplierRow['address3'] . "', '" . $SupplierRow['address4'] . "', '" . $SupplierRow['address5'] . "', '" . $SupplierRow['address6'] . "', '" . $SupplierRow['telephone']. "', '" . $LocnRow['contact'] . "', CURRENT_DATE, 'Standard', 'Pending', '" . date($_SESSION['DefaultDateFormat']) . " - Order Created', '" . date('Y-m-d') . "', '" . $SupplierRow['paymentterms'] . "', '0' )";
		DB_query($SQL);

		foreach ($PurchItems as $StockID=>$POLine) {
			$SQL = "INSERT INTO purchorderdetails (orderno, itemcode, deliverydate, itemdescription, glcode, unitprice, quantityord, shiptref, jobref, suppliersunit, suppliers_partno, assetid, conversionfactor )
					VALUES ('" . $OrderNo . "', '" . $StockID . "', '" . FormatDateForSQL($POLine['DeliveryDate']) . "', '" . DB_escape_string($POLine['SupplierDescription']) . "', '" . $POLine['GLCode'] . "', '" . $POLine['Price'] . "', '" . $POLine['Quantity'] . "', '0', '0', '" . $POLine['UnitOfMeasure'] . "', '" . $POLine['SuppliersPartNo'] . "', '0', '" . $POLine['ConversionFactor'] . "')";
			DB_query($SQL);
		} 
		
		echo '<div class="aw-card">
				<div class="aw-card-body" style="text-align: center; padding: 40px;">
					<div style="background: var(--primary-bg); color: var(--primary); width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
					</div>
					<h2 style="font-weight: 950; color: var(--primary-dark); letter-spacing: -1px;">' . __('Order Success') . '</h2>
					<p style="color: var(--text-muted); margin-bottom: 24px;">' . __('Purchase Order') . ' <b>#' . $OrderNo . '</b> ' . __('has been generated.') . '</p>
					<div style="display: flex; gap: 12px; justify-content: center;">
						<a href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $OrderNo . '" class="aw-btn aw-btn-primary">' . __('Print Order') . '</a>
						<a href="' . $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $OrderNo . '" class="aw-btn aw-btn-secondary">' . __('Modify') . '</a>
					</div>
				</div>
			  </div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Purchasing / Batch Operations</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

echo '<form id="SupplierPurchasing" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="aw-grid-search">';

// LEFT: Sidebar Selection
echo '<aside class="aw-selection-sidebar">
		<div class="aw-card">
			<div class="aw-card-header">
				<h2 class="aw-card-title">' . __('Search Filter') . '</h2>
			</div>
			<div class="aw-card-body">
				<div class="aw-form-group">
					<label class="aw-label">' . __('Select Preferred Supplier') . '</label>
					<select name="Supplier" class="aw-select">';
					$SQL = "SELECT supplierid, suppname FROM suppliers WHERE supptype<>7 ORDER BY suppname";
					$SuppResult = DB_query($SQL);
					echo '<option value="">' . __('Not Selected') . '</option>';
					while ($MyRow=DB_fetch_array($SuppResult)){
						$selected = (isset($_POST['Supplier']) AND $_POST['Supplier']==$MyRow['supplierid']) ? 'selected="selected"' : '';
						echo '<option ' . $selected . ' value="' . $MyRow['supplierid'] . '">' . $MyRow['suppname']  . '</option>';
					}
echo '				</select>
				</div>
				<button type="submit" name="ShowItems" class="aw-btn aw-btn-primary" style="width: 100%; margin-top: 16px;">' . __('Fetch Items') . '</button>
			</div>
		</div>
		
		<div class="aw-card" style="background: var(--primary-bg); border-color: var(--primary-subtle);">
			<div class="aw-card-body" style="font-size: 0.75rem; color: var(--primary-dark); line-height: 1.5;">
				<p style="font-weight: 850; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">' . __('Analysis Tool') . '</p>
				<p style="opacity: 0.8;">' . __('This logic identifies items where the selected supplier is marked as "Preferred". Use the historical sales data to determine replenishment quantities.') . '</p>
			</div>
		</div>
	  </aside>';

// RIGHT: Main Content
echo '<main class="aw-main-content">';

if (isset($_POST['Supplier']) AND isset($_POST['ShowItems']) AND $_POST['Supplier']!=''){

		$SQL = "SELECT stockmaster.description,
						stockmaster.eoq,
						stockmaster.decimalplaces,
						locstock.stockid,
						purchdata.supplierno,
						suppliers.suppname,
						purchdata.leadtime/30 AS monthsleadtime,
						locstock.bin,
						SUM(locstock.quantity) AS qoh
					FROM locstock,
						stockmaster,
						purchdata,
						suppliers
					WHERE locstock.stockid=stockmaster.stockid
					AND purchdata.supplierno=suppliers.supplierid
					AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M')
					AND purchdata.stockid=stockmaster.stockid
					AND purchdata.preferred=1
					AND purchdata.supplierno='" . $_POST['Supplier'] . "'
					AND locstock.loccode='" . $_SESSION['UserStockLocation'] . "'
					GROUP BY
						purchdata.supplierno,
						stockmaster.description,
						stockmaster.eoq,
						locstock.stockid,
						purchdata.leadtime/30
					ORDER BY purchdata.supplierno,
						stockmaster.stockid";

	$ItemsResult = DB_query($SQL);
	$ListCount = DB_num_rows($ItemsResult);

	if ($ListCount > 0) {
		echo '<div class="aw-card">
				<div class="aw-card-header">
					<h3 class="aw-card-title">' . __('Purchase Recommendations') . '</h3>
				</div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description / Bin') . '</th>
								<th style="text-align: right;">' . __('Stats') . '</th>
								<th style="text-align: right;">' . __('Sales History (Last 30d / Prev 30d)') . '</th>
								<th style="text-align: right;">' . __('Weekly Trends') . '</th>
								<th style="width: 120px; text-align: right;">' . __('Order Qty') . '</th>
							</tr>
						</thead>
						<tbody>';

		$i=0;
		while ($ItemRow = DB_fetch_array($ItemsResult)){
			$SQL = "SELECT SUM(CASE WHEN (trandate>='" . date('Y-m-d',mktime(0,0,0, date('m')-2, date('d'), date('Y'))) . "' AND
								trandate<='" . date('Y-m-d',mktime(0,0,0, date('m')-1, date('d'), date('Y'))) . "') THEN -qty ELSE 0 END) AS previousmonth,
						SUM(CASE WHEN (trandate>='" . date('Y-m-d',mktime(0,0,0, date('m')-1, date('d'), date('Y'))) . "' AND
								trandate<= CURRENT_DATE) THEN -qty ELSE 0 END) AS lastmonth,
						SUM(CASE WHEN (trandate>='" . date('Y-m-d',mktime(0,0,0, date('m'), date('d')-(3*7), date('Y'))) . "' AND
								trandate<='" . date('Y-m-d',mktime(0,0,0, date('m'), date('d')-(2*7), date('Y'))) . "') THEN -qty ELSE 0 END) AS wk3,
						SUM(CASE WHEN (trandate>='" . date('Y-m-d',mktime(0,0,0, date('m'), date('d')-(2*7), date('Y'))) . "' AND
								trandate<='" . date('Y-m-d',mktime(0,0,0, date('m'), date('d')-7, date('Y'))) . "') THEN -qty ELSE 0 END) AS wk2,
						SUM(CASE WHEN (trandate>='" . date('Y-m-d',mktime(0,0,0, date('m'), date('d')-7, date('Y'))) . "' AND
								trandate<= CURRENT_DATE) THEN -qty ELSE 0 END) AS wk1
					FROM stockmoves
					WHERE stockid='" . $ItemRow['stockid'] . "'
					AND (type=10 OR type=11)";

			$SalesResult = DB_query($SQL);
			$SalesRow = DB_fetch_array($SalesResult);
			$TotalDemand = GetDemand($ItemRow['stockid'], 'ALL');
			$QOO = GetQuantityOnOrder($ItemRow['stockid'], 'ALL');

			if (!isset($_POST['OrderQty' . $i])) $_POST['OrderQty' . $i] = 0;

			echo '<tr>
					<td style="font-weight: 800; color: var(--primary-dark);">' . $ItemRow['stockid']  . '</td>
					<td>
						<div style="font-weight: 650;">' . $ItemRow['description'] . '</div>
						<div style="font-size: 0.72rem; color: var(--text-muted);">' . __('Bin') . ': ' . ($ItemRow['bin'] ?: 'N/A') . '</div>
					</td>
					<td style="text-align: right;">
						<div class="aw-hist-col">' . __('QOH') . ': <span class="aw-hist-val">' . locale_number_format($ItemRow['qoh'],$ItemRow['decimalplaces']) . '</span></div>
						<div class="aw-hist-col">' . __('Req') . ': <span class="aw-hist-val">' . locale_number_format($TotalDemand,$ItemRow['decimalplaces']) . '</span></div>
						<div class="aw-hist-col">' . __('OO') . ': <span class="aw-hist-val">' . locale_number_format($QOO,$ItemRow['decimalplaces']) . '</span></div>
					</td>
					<td style="text-align: right;">
						<div class="aw-hist-col"><span class="aw-hist-val" style="color: var(--primary);">' . locale_number_format($SalesRow['lastmonth'],$ItemRow['decimalplaces']) . '</span> / ' . locale_number_format($SalesRow['previousmonth'],$ItemRow['decimalplaces']) . '</div>
					</td>
					<td style="text-align: right;">
						<div class="aw-hist-col">W1: <span class="aw-hist-val">' . locale_number_format($SalesRow['wk1'],$ItemRow['decimalplaces']) . '</span></div>
						<div class="aw-hist-col">W2: <span class="aw-hist-val">' . locale_number_format($SalesRow['wk2'],$ItemRow['decimalplaces']) . '</span></div>
						<div class="aw-hist-col">W3: <span class="aw-hist-val">' . locale_number_format($SalesRow['wk3'],$ItemRow['decimalplaces']) . '</span></div>
					</td>
					<td>
						<input type="hidden" name="StockID' . $i . '" value="' . $ItemRow['stockid'] . '" />
						<input type="text" class="aw-input" style="text-align: right; font-weight: 800;" name="OrderQty' . $i  . '" value="' . $_POST['OrderQty' . $i] . '" />
					</td>
				</tr>';
			$i++;
		}
		echo '					</tbody>
						</table>
					</div>
					<div class="aw-card-body" style="background: #fbfcfd; border-top: 1px solid var(--border-color); text-align: right; padding: 12px 16px;">
						<button type="submit" name="CreatePO" class="aw-btn aw-btn-primary" onclick="return confirm(\'' . __('Confirm create PO?') . '\');">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Generate Purchase Order') . '
						</button>
					</div>
				</div>';
	} else {
		echo '<div class="aw-card">
				<div class="aw-card-body" style="text-align: center; padding: 60px; color: var(--text-muted);">
					<p>' . __('No preferred supplier matches found for this criteria.') . '</p>
				</div>
			  </div>';
	}
} else {
	echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background: transparent; box-shadow: none;">
			<div class="aw-card-body" style="text-align: center; padding: 80px; color: var(--text-muted);">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="opacity: 0.3; margin-bottom: 20px;"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
				<h3 style="font-weight: 850; color: var(--primary-dark);">' . __('No Supplier Selected') . '</h3>
				<p style="font-size: 0.9rem; max-width: 300px; margin: 0 auto;">' . __('Please select a supplier from the sidebar filter to begin reordering preferred items.') . '</p>
			</div>
		  </div>';
}

echo '</main>';
echo '</div>'; // End aw-grid-search
echo '</form></div>'; // End aw-container

include(__DIR__ . '/includes/footer.php');
?>
