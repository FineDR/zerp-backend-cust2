<?php

require(__DIR__ . '/includes/session.php');

$Title=__('Preferred Supplier Purchasing');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Review and create purchase orders for preferred supplier items') . '</p>
			</div>
		</div>';

if (isset($_POST['CreatePO']) AND isset($_POST['Supplier'])){
	include(__DIR__ . '/includes/SQL_CommonFunctions.php');
	$InputError =0; //Always hope for the best

	//Make an array of the Items to purchase
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

				$ErrMsg = __('The item details for') . ' ' . $StockID . ' ' . __('could not be retrieved because');
				$ItemResult = DB_query($SQL, $ErrMsg);
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

					$ErrMsg = __('The purchasing data for') . ' ' . $StockID . ' ' . __('could not be retrieved because');
					$PurchDataResult = DB_query($SQL, $ErrMsg);
					if (DB_num_rows($PurchDataResult)>0){ //the purchasing data is set up
						$PurchRow = DB_fetch_array($PurchDataResult);

						/* Now to get the applicable discounts */
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
						$ErrMsg = __('Could not retrieve the supplier discounts applicable to the item');
						$DiscountResult = DB_query($SQL, $ErrMsg);
						while ($DiscountRow = DB_fetch_array($DiscountResult)) {
							$ItemDiscountPercent += $DiscountRow['discountpercent'];
							$ItemDiscountAmount += $DiscountRow['discountamount'];
						}
						if ($ItemDiscountPercent != 0) {
							prnMsg(__('Taken accumulated supplier percentage discounts of') .  ' ' . locale_number_format($ItemDiscountPercent*100,2) . '%','info');
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
						/* Work out the delivery date based on today + lead time  */
						$PurchItems[$StockID]['DeliveryDate'] = DateAdd(date($_SESSION['DefaultDateFormat']),'d',$LeadTime);
					} else { // no purchasing data setup
						$PurchItems[$StockID]['Price'] = 0;
						$PurchItems[$StockID]['ConversionFactor'] = 1;
						$PurchItems[$StockID]['SupplierDescription'] = 	$ItemRow['description'];
						$PurchItems[$StockID]['UnitOfMeasure'] = $ItemRow['units'];
						$PurchItems[$StockID]['SuppliersPartNo'] = 'each';
						$LeadTime = 1;
						$PurchItems[$StockID]['DeliveryDate'] = date($_SESSION['DefaultDateFormat']);
					}
					$OrderValue += $PurchItems[$StockID]['Quantity']*$PurchItems[$StockID]['Price'];
				} else { //item could not be found
					$InputError =1;
					prnmsg(__('An item where a quantity was entered could not be retrieved from the database. The order cannot proceed. The item code was:') . ' ' . $StockID,'error');
				}
			} //end if the quantity entered into the form is positive
		} //end if the form variable name is OrderQtyXXX
	}//end loop around the form variables

	if ($InputError==0) { //only if all continues smoothly

		$SQL = "SELECT suppliers.suppname,
						suppliers.currcode,
						currencies.decimalplaces,
						currencies.rate,
						suppliers.paymentterms,
						suppliers.address1,
						suppliers.address2,
						suppliers.address3,
						suppliers.address4,
						suppliers.address5,
						suppliers.address6,
						suppliers.telephone
				FROM suppliers INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_POST['Supplier'] . "'";
		$SupplierResult = DB_query($SQL);
		$SupplierRow = DB_fetch_array($SupplierResult);

		$SQL = "SELECT deladd1,
							deladd2,
							deladd3,
							deladd4,
							deladd5,
							deladd6,
							tel,
							contact
						FROM locations
						WHERE loccode='" . $_SESSION['UserStockLocation'] . "'";
		$LocnAddrResult = DB_query($SQL);
		if (DB_num_rows($LocnAddrResult) == 1) {
			$LocnRow = DB_fetch_array($LocnAddrResult);
		} else {
			prnMsg(__('Your default inventory location is set to a non-existant inventory location. This purchase order cannot proceed'), 'error');
			$InputError =1;
		}
		if (IsEmailAddress($_SESSION['UserEmail'])){
			$UserDetails  = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName']. '</a>';
		} else {
			$UserDetails  = ' ' . $_SESSION['UsersRealName'] . ' ';
		}
		if ($_SESSION['AutoAuthorisePO']==1) {
			//if the user has authority to authorise the PO then it will automatically be authorised
			$AuthSQL ="SELECT authlevel
						FROM purchorderauth
						WHERE userid='" . $_SESSION['UserID'] . "'
						AND currabrev='" . $SupplierRow['currcode'] ."'";

			$AuthResult = DB_query($AuthSQL);
			$AuthRow=DB_fetch_array($AuthResult);

			if (DB_num_rows($AuthResult) > 0 AND $AuthRow['authlevel'] > $OrderValue) { //user has authority to authrorise as well as create the order
				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created and Authorised by') . $UserDetails;
				$AllowPrintPO=1;
				$Status = 'Authorised';
			} else { // no authority to authorise this order
				if (DB_num_rows($AuthResult) ==0){
					$AuthMessage = __('Your authority to approve purchase orders in') . ' ' . $SupplierRow['currcode'] . ' ' . __('has not yet been set up') . '<br />';
				} else {
					$AuthMessage = __('You can only authorise up to') . ' ' . $SupplierRow['currcode'] . ' '.$AuthRow['authlevel'] .'.<br />';
				}

				prnMsg( __('You do not have permission to authorise this purchase order').'.<br />' . __('This order is for') . ' ' . $SupplierRow['currcode'] . ' '. $OrderValue . ' ' .
					$AuthMessage .
					__('If you think this is a mistake please contact the systems administrator') . '<br />'.
					__('The order will be created with a status of pending and will require authorisation'), 'warn');

				$AllowPrintPO=0;
				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . ' ' . $UserDetails;
				$Status = 'Pending';
			}
		} else { //auto authorise is set to off
			$AllowPrintPO=0;
			$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . ' ' . $UserDetails;
			$Status = 'Pending';
		}

		/*Get the order number */
		$OrderNo = GetNextTransNo(18);

		/*Insert to purchase order header record */
		$SQL = "INSERT INTO purchorders ( orderno,
										supplierno,
										orddate,
										rate,
										initiator,
										intostocklocation,
										deladd1,
										deladd2,
										deladd3,
										deladd4,
										deladd5,
										deladd6,
										tel,
										suppdeladdress1,
										suppdeladdress2,
										suppdeladdress3,
										suppdeladdress4,
										suppdeladdress5,
										suppdeladdress6,
										supptel,
										contact,
										revised,
										deliveryby,
										status,
										stat_comment,
										deliverydate,
										paymentterms,
										allowprint)
						VALUES(	'" . $OrderNo . "',
								'" . $_POST['Supplier'] . "',
								CURRENT_DATE,
								'" . $SupplierRow['rate'] . "',
								'" . $_SESSION['UserID'] . "',
								'" . $_SESSION['UserStockLocation'] . "',
								'" . $LocnRow['deladd1'] . "',
								'" . $LocnRow['deladd2'] . "',
								'" . $LocnRow['deladd3'] . "',
								'" . $LocnRow['deladd4'] . "',
								'" . $LocnRow['deladd5'] . "',
								'" . $LocnRow['deladd6'] . "',
								'" . $LocnRow['tel'] . "',
								'" . $SupplierRow['address1'] . "',
								'" . $SupplierRow['address2']  . "',
								'" . $SupplierRow['address3'] . "',
								'" . $SupplierRow['address4'] . "',
								'" . $SupplierRow['address5'] . "',
								'" . $SupplierRow['address6'] . "',
								'" . $SupplierRow['telephone']. "',
								'" . $LocnRow['contact'] . "',
								CURRENT_DATE,
								'" . date('Y-m-d',mktime(0,0,0,date('m'),date('d')+1,date('Y'))) . "',
								'" . $Status . "',
								'" . htmlspecialchars($StatusComment,ENT_QUOTES,'UTF-8') . "',
								'" . date('Y-m-d',mktime(0,0,0,date('m'),date('d')+1,date('Y'))) . "',
								'" . $SupplierRow['paymentterms'] . "',
								'" . $AllowPrintPO . "' )";

		$ErrMsg =  __('The purchase order header record could not be inserted into the database because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

	    /*Insert the purchase order detail records */
		foreach ($PurchItems as $StockID=>$POLine) {

			//print_r($POLine);

			$SQL = "INSERT INTO purchorderdetails (orderno,
										itemcode,
										deliverydate,
										itemdescription,
										glcode,
										unitprice,
										quantityord,
										shiptref,
										jobref,
										suppliersunit,
										suppliers_partno,
										assetid,
										conversionfactor )
					VALUES ('" . $OrderNo . "',
							'" . $StockID . "',
							'" . FormatDateForSQL($POLine['DeliveryDate']) . "',
							'" . DB_escape_string($POLine['SupplierDescription']) . "',
							'" . $POLine['GLCode'] . "',
							'" . $POLine['Price'] . "',
							'" . $POLine['Quantity'] . "',
							'0',
							'0',
							'" . $POLine['UnitOfMeasure'] . "',
							'" . $POLine['SuppliersPartNo'] . "',
							'0',
							'" . $POLine['ConversionFactor'] . "')";
			$ErrMsg =__('One of the purchase order detail records could not be inserted into the database because');

			$Result = DB_query($SQL, $ErrMsg, '', true);
		} /* end of the loop round the detail line items on the order */
		echo '<p />';
		prnMsg(__('Purchase Order') . ' ' . $OrderNo . ' ' .  __('has been created.') . ' ' . __('Total order value of') . ': ' . locale_number_format($OrderValue,$SupplierRow['decimalplaces']) . ' ' . $SupplierRow['currcode']  ,'success');
		echo '<br /><a href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $OrderNo . '">' . __('Print Order') . '</a>
				<br /><a href="' . $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $OrderNo . '">' . __('Edit Order') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	} else {
		prnMsg(__('Unable to create the order'),'error');
	}
}


echo '<form id="SupplierPurchasing" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-card">
		<div class="db-card-title">' . __('Supplier Selection') . '</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">
				<div class="db-form-group">
					<label class="db-form-label">' . __('For Supplier') . ':</label>
					<select name="Supplier" class="db-form-select">';

$SQL = "SELECT supplierid, suppname FROM suppliers WHERE supptype<>7 ORDER BY suppname";
$SuppResult = DB_query($SQL);

echo '<option value="">' . __('Not Yet Selected') . '</option>';

while ($MyRow=DB_fetch_array($SuppResult)){
	if (isset($_POST['Supplier']) AND $_POST['Supplier']==$MyRow['supplierid']){
		echo '<option selected="selected" value="' . $MyRow['supplierid'] . '">' . $MyRow['suppname']  . '</option>';
	} else {
		echo '<option value="' . $MyRow['supplierid'] . '">' . $MyRow['suppname']  . '</option>';
	}
}
echo '				</select>
				</div>
				<div class="db-form-group" style="display: flex; align-items: flex-end;">
					<button type="submit" name="ShowItems" class="db-btn db-btn-primary" style="width: 100%;">' . __('Show Items to Purchase') . '</button>
				</div>
			</div>
		</div>
	</div>';

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

	$ErrMsg = __('The supplier inventory quantities could not be retrieved');
	$ItemsResult = DB_query($SQL, $ErrMsg, '', false, false);
	$ListCount = DB_num_rows($ItemsResult);

	//head up a new table
	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-body" style="padding: 0;">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Item') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Bin') . '</th>
								<th class="text-right">' . __('On Hand') . '</th>
								<th class="text-right">' . __('Demand') . '</th>
								<th class="text-right">' . __('Ostdg') . '</th>
								<th class="text-right">' . __('Prev') . '<br />' .__('Month') . '</th>
								<th class="text-right">' . __('Last') . '<br />' .__('Month') . '</th>
								<th class="text-right">' . __('Week') . '<br />' .__('3') . '</th>
								<th class="text-right">' . __('Week') . '<br />' .__('2') . '</th>
								<th class="text-right">' . __('Last') . '<br />' .__('Week') . '</th>
								<th style="width: 120px;">' . __('Order Qty') . '</th>
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

		$ErrMsg = __('The sales quantities could not be retrieved');
		$SalesResult = DB_query($SQL, $ErrMsg, '',false);
		$SalesRow = DB_fetch_array($SalesResult);

		// Get the demand
		$TotalDemand = GetDemand($ItemRow['stockid'], 'ALL');
		// Get the QOO
		$QOO = GetQuantityOnOrder($ItemRow['stockid'], 'ALL');

		if (!isset($_POST['OrderQty' . $i])){
			$_POST['OrderQty' . $i] =0;
		}
		echo '<tr>
				<td class="db-font-semibold">' . $ItemRow['stockid']  . '</td>
				<td>' . $ItemRow['description'] . '</td>
				<td class="db-text-muted">' . $ItemRow['bin'] . '</td>
				<td class="text-right">' . locale_number_format($ItemRow['qoh'],$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($TotalDemand,$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($QOO,$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($SalesRow['previousmonth'],$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($SalesRow['lastmonth'],$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($SalesRow['wk3'],$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($SalesRow['wk2'],$ItemRow['decimalplaces']) . '</td>
				<td class="text-right">' . locale_number_format($SalesRow['wk1'],$ItemRow['decimalplaces']) . '</td>
				<td>
					<input type="hidden" name="StockID' . $i . '" value="' . $ItemRow['stockid'] . '" />
					<input type="text" class="db-form-input text-right" name="OrderQty' . $i  . '" value="' . $_POST['OrderQty' . $i] . '" title="' . __('Enter the quantity to purchase of this item') . '" />
				</td>
			</tr>';
		$i++;
	} /*end preferred supplier items while loop */

	echo '					</tbody>
					</table>
				</div>
			</div>
			<div class="db-card-footer">
				<div class="db-form-actions">
					<button type="submit" name="CreatePO" class="db-btn db-btn-primary" onclick="return confirm(\'' . __('Clicking this button will create a purchase order for all the quantities in the grid above for immediate delivery. Are you sure?') . '\');">' . __('Create Purchase Order') . '</button>
				</div>
			</div>
		</div>';
}

echo '</div> <!-- End db-page -->
	  </form>';

include(__DIR__ . '/includes/footer.php');
