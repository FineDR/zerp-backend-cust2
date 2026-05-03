<?php

/* This is where the details specific to the recurring order are entered and the template committed to the database once the Process button is hit */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Recurring Orders');
$ViewTopic = 'SalesOrders';
$BookMark = 'RecurringSalesOrders';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['StartDate'])){$_POST['StartDate'] = ConvertSQLDate($_POST['StartDate']);}
if (isset($_POST['StopDate'])){$_POST['StopDate'] = ConvertSQLDate($_POST['StopDate']);}

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (isset($_GET['NewRecurringOrder']) or isset($_POST['NewRecurringOrder'])) {
	$NewRecurringOrder ='Yes';
} else {
	$NewRecurringOrder ='No';
	if (isset($_GET['ModifyRecurringSalesOrder'])) {

		$_POST['ExistingRecurrOrderNo'] = $_GET['ModifyRecurringSalesOrder'];

		/*Need to read in the existing recurring order template */

		$_SESSION['Items'.$identifier] = new Cart;

		/*read in all the guff from the selected order into the Items cart  */

		$OrderHeaderSQL = "SELECT recurringsalesorders.debtorno,
									debtorsmaster.name,
									recurringsalesorders.branchcode,
									recurringsalesorders.customerref,
									recurringsalesorders.comments,
									recurringsalesorders.orddate,
									recurringsalesorders.ordertype,
									salestypes.sales_type,
									recurringsalesorders.shipvia,
									recurringsalesorders.deliverto,
									recurringsalesorders.deladd1,
									recurringsalesorders.deladd2,
									recurringsalesorders.deladd3,
									recurringsalesorders.deladd4,
									recurringsalesorders.deladd5,
									recurringsalesorders.deladd6,
									recurringsalesorders.contactphone,
									recurringsalesorders.contactemail,
									recurringsalesorders.freightcost,
									debtorsmaster.currcode,
									recurringsalesorders.fromstkloc,
									recurringsalesorders.frequency,
									recurringsalesorders.stopdate,
									recurringsalesorders.lastrecurrence,
									recurringsalesorders.autoinvoice
								FROM recurringsalesorders
								INNER JOIN debtorsmaster
								ON recurringsalesorders.debtorno = debtorsmaster.debtorno
								INNER JOIN salestypes
								ON recurringsalesorders.ordertype=salestypes.typeabbrev
								WHERE recurringsalesorders.recurrorderno = '" . $_GET['ModifyRecurringSalesOrder'] . "'";

		$ErrMsg =  __('The order cannot be retrieved because');
		$GetOrdHdrResult = DB_query($OrderHeaderSQL, $ErrMsg);

		if (DB_num_rows($GetOrdHdrResult)==1) {

			$MyRow = DB_fetch_array($GetOrdHdrResult);

			$_SESSION['Items'.$identifier]->DebtorNo = $MyRow['debtorno'];
	/*CustomerID defined in header.php */
			$_SESSION['Items'.$identifier]->Branch = $MyRow['branchcode'];
			$_SESSION['Items'.$identifier]->CustomerName = $MyRow['name'];
			$_SESSION['Items'.$identifier]->CustRef = $MyRow['customerref'];
			$_SESSION['Items'.$identifier]->Comments = $MyRow['comments'];

			$_SESSION['Items'.$identifier]->DefaultSalesType =$MyRow['ordertype'];
			$_SESSION['Items'.$identifier]->SalesTypeName =$MyRow['sales_type'];
			$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currcode'];
			$_SESSION['Items'.$identifier]->ShipVia = $MyRow['shipvia'];
			$BestShipper = $MyRow['shipvia'];
			$_SESSION['Items'.$identifier]->DeliverTo = $MyRow['deliverto'];
			//$_SESSION['Items'.$identifier]->DeliveryDate = ConvertSQLDate($MyRow['deliverydate']);
			$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow['deladd1'];
			$_SESSION['Items'.$identifier]->DelAdd2 = $MyRow['deladd2'];
			$_SESSION['Items'.$identifier]->DelAdd3 = $MyRow['deladd3'];
			$_SESSION['Items'.$identifier]->DelAdd4 = $MyRow['deladd4'];
			$_SESSION['Items'.$identifier]->DelAdd5 = $MyRow['deladd5'];
			$_SESSION['Items'.$identifier]->DelAdd6 = $MyRow['deladd6'];
			$_SESSION['Items'.$identifier]->PhoneNo = $MyRow['contactphone'];
			$_SESSION['Items'.$identifier]->Email = $MyRow['contactemail'];
			$_SESSION['Items'.$identifier]->Location = $MyRow['fromstkloc'];
			$_SESSION['Items'.$identifier]->Quotation = 0;
			$FreightCost = $MyRow['freightcost'];
			$_SESSION['Items'.$identifier]->Orig_OrderDate = $MyRow['orddate'];
			$_POST['StopDate'] = ConvertSQLDate($MyRow['stopdate']);
			$_POST['StartDate'] = ConvertSQLDate($MyRow['lastrecurrence']);
			$_POST['Frequency'] = $MyRow['frequency'];
			$_POST['AutoInvoice'] = $MyRow['autoinvoice'];

	/*need to look up customer name from debtors master then populate the line items array with the sales order details records */
			$LineItemsSQL = "SELECT recurrsalesorderdetails.stkcode,
									stockmaster.description,
									stockmaster.longdescription,
									stockmaster.volume,
									stockmaster.grossweight,
									stockmaster.units,
									recurrsalesorderdetails.unitprice,
									recurrsalesorderdetails.quantity,
									recurrsalesorderdetails.discountpercent,
									recurrsalesorderdetails.narrative,
									locstock.quantity as qohatloc,
									stockmaster.mbflag,
									stockmaster.discountcategory,
									stockmaster.decimalplaces
									FROM recurrsalesorderdetails INNER JOIN stockmaster
									ON recurrsalesorderdetails.stkcode = stockmaster.stockid
									INNER JOIN locstock ON locstock.stockid = stockmaster.stockid
									WHERE  locstock.loccode = '" . $MyRow['fromstkloc'] . "'
									AND recurrsalesorderdetails.recurrorderno ='" . $_GET['ModifyRecurringSalesOrder'] . "'";

			$ErrMsg = __('The line items of the order cannot be retrieved because');
			$LineItemsResult = DB_query($LineItemsSQL, $ErrMsg);
			if (DB_num_rows($LineItemsResult)>0) {

				while ($MyRow=DB_fetch_array($LineItemsResult)) {
					$_SESSION['Items'.$identifier]->add_to_cart($MyRow['stkcode'],
																$MyRow['quantity'],
																$MyRow['description'],
																$MyRow['longdescription'],
																$MyRow['unitprice'],
																$MyRow['discountpercent'],
																$MyRow['units'],
																$MyRow['volume'],
																$MyRow['grossweight'],
																$MyRow['qohatloc'],
																$MyRow['mbflag'],
																'',
																0,
																$MyRow['discountcategory'],
																0,	/*Controlled*/
																0,	/*Serialised */
																$MyRow['decimalplaces'],
																$MyRow['narrative']);
					/*Just populating with existing order - no DBUpdates */

				} /* line items from sales order details */
			} //end of checks on returned data set
		}
	} else {
		/// @todo should we use a different error message?
		/// @todo why not use the same IF a few lines below, removing the $NewRecurringOrder == 'Yes' part?
		prnMsg(__('A new recurring order can only be created if an order template has already been created from the normal order entry screen') . '. ' . __('To enter an order template select sales order entry from the orders tab of the main menu'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}

if ((!isset($_SESSION['Items'.$identifier]) OR $_SESSION['Items'.$identifier]->ItemsOrdered == 0) AND $NewRecurringOrder == 'Yes') {
	prnMsg(__('A new recurring order can only be created if an order template has already been created from the normal order entry screen') . '. ' . __('To enter an order template select sales order entry from the orders tab of the main menu'),'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['DeleteRecurringOrder'])) {
	$SQL = "DELETE FROM recurrsalesorderdetails WHERE recurrorderno='" . $_POST['ExistingRecurrOrderNo'] . "'";
	$ErrMsg = __('Could not delete recurring sales order lines for the recurring order template') . ' ' . $_POST['ExistingRecurrOrderNo'];
	$Result = DB_query($SQL, $ErrMsg);

	$SQL = "DELETE FROM recurringsalesorders WHERE recurrorderno='" . $_POST['ExistingRecurrOrderNo'] . "'";
	$ErrMsg = __('Could not delete the recurring sales order template number') . ' ' . $_POST['ExistingRecurrOrderNo'];
	$Result = DB_query($SQL, $ErrMsg);

	prnMsg(__('Successfully deleted recurring sales order template number') . ' ' . $_POST['ExistingRecurrOrderNo'],'success');

	echo '<p><a href="'.$RootPath.'/SelectRecurringSalesOrder.php">' .  __('Select A Recurring Sales Order Template')  . '</a>';

	unset($_SESSION['Items'.$identifier]->LineItems);
	unset($_SESSION['Items'.$identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['Process'])) {
	DB_Txn_Begin();
	$InputErrors =0;
	if (!Is_Date($_POST['StartDate'])){
		$InputErrors =1;
		prnMsg(__('The last recurrence or start date of this recurring order must be a valid date in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
	}
	if (!Is_Date($_POST['StopDate'])){
		$InputErrors =1;
		prnMsg(__('The end date of this recurring order must be a valid date in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
	}
	if (Date1GreaterThanDate2 ($_POST['StartDate'],$_POST['StopDate'])){
		$InputErrors =1;
		prnMsg(__('The end date of this recurring order must be after the start date'),'error');
	}
	if (isset($_POST['MakeRecurringOrder']) AND $_POST['Quotation']==1){
		$InputErrors =1;
		prnMsg( __('A recurring order cannot be made from a quotation'),'error');
	}

	if ($InputErrors == 0 ){  /*Error checks above all passed ok so lets go*/


		if ($NewRecurringOrder=='Yes'){

			/* finally write the recurring order header to the database and then the line details*/
			$DelDate = FormatDateforSQL($_SESSION['Items'.$identifier]->DeliveryDate);

			$HeaderSQL = "INSERT INTO recurringsalesorders (
										debtorno,
										branchcode,
										customerref,
										comments,
										orddate,
										ordertype,
										deliverto,
										deladd1,
										deladd2,
										deladd3,
										deladd4,
										deladd5,
										deladd6,
										contactphone,
										contactemail,
										freightcost,
										fromstkloc,
										shipvia,
										lastrecurrence,
										stopdate,
										frequency,
										autoinvoice)
									values (
										'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
										'" . $_SESSION['Items'.$identifier]->Branch . "',
										'". $_SESSION['Items'.$identifier]->CustRef ."',
										'". $_SESSION['Items'.$identifier]->Comments ."',
										'" . date('Y-m-d H:i') . "',
										'" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
										'" . $_SESSION['Items'.$identifier]->DeliverTo . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd1 . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd2 . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd3 . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd4 . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd5 . "',
										'" . $_SESSION['Items'.$identifier]->DelAdd6 . "',
										'" . $_SESSION['Items'.$identifier]->PhoneNo . "',
										'" . $_SESSION['Items'.$identifier]->Email . "',
										'" . $_SESSION['Items'.$identifier]->FreightCost ."',
										'" . $_SESSION['Items'.$identifier]->Location ."',
										'" . $_SESSION['Items'.$identifier]->ShipVia ."',
										'" . FormatDateforSQL($_POST['StartDate']) . "',
										'" . FormatDateforSQL($_POST['StopDate']) . "',
										'" . $_POST['Frequency'] ."',
										'" . $_POST['AutoInvoice'] . "')";

			$ErrMsg = __('The recurring order cannot be added because');
			$InsertQryResult = DB_query($HeaderSQL, $ErrMsg, '', true);

			$RecurrOrderNo = DB_Last_Insert_ID('recurringsalesorders','recurrorderno');
			$StartOf_LineItemsSQL = "INSERT INTO recurrsalesorderdetails (recurrorderno,
																			stkcode,
																			unitprice,
																			quantity,
																			discountpercent,
																			narrative)
																		VALUES ('";

			foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {

				$LineItemsSQL = $StartOf_LineItemsSQL .
								$RecurrOrderNo . "',
								'" . $StockItem->StockID . "',
								'". filter_number_format($StockItem->Price) . "',
								'" . filter_number_format($StockItem->Quantity) . "',
								'" . filter_number_format($StockItem->DiscountPercent) . "',
								'" . $StockItem->Narrative . "')";
				$Ins_LineItemResult = DB_query($LineItemsSQL, $ErrMsg, '', true);

			} /* inserted line items into sales order details */

			DB_Txn_Commit();
			prnmsg(__('The new recurring order template has been added'),'success');

		} else { /* must be updating an existing recurring order */
			$HeaderSQL = "UPDATE recurringsalesorders SET
						stopdate =  '" . FormatDateforSQL($_POST['StopDate']) . "',
						frequency = '" . $_POST['Frequency'] . "',
						autoinvoice = '" . $_POST['AutoInvoice'] . "'
					WHERE recurrorderno = '" . $_POST['ExistingRecurrOrderNo'] . "'";

			$ErrMsg = __('The recurring order cannot be updated because');
			$UpdateQryResult = DB_query($HeaderSQL, $ErrMsg);
			prnmsg(__('The recurring order template has been updated'),'success');
		}

	echo '<p><a href="'.$RootPath.'/SelectOrderItems.php?NewOrder=Yes">' .  __('Enter New Sales Order')  . '</a>';

	echo '<p><a href="'.$RootPath.'/SelectRecurringSalesOrder.php">' .  __('Select A Recurring Sales Order Template')  . '</a>';

	unset($_SESSION['Items'.$identifier]->LineItems);
	unset($_SESSION['Items'.$identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();

	}
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-sync-alt"></i> ' . $Title . '
			</div>
			<div class="db-page-subtitle">' . __('Recurring Order for Customer') . ': <span class="db-font-bold">' . $_SESSION['Items'.$identifier]->CustomerName . '</span></div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/SelectRecurringSalesOrder.php" class="db-btn db-btn-outline"><i class="fas fa-arrow-left"></i> ' . __('Back to Templates') . '</a>
			</div>
		</div>

		<div class="db-page-content">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-card" style="margin-bottom: var(--space-6);">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-list"></i> ' . __('Order Line Details') . '</div>
		</div>
		<div class="db-card-body p-0">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Item Code') . '</th>
							<th>' . __('Description') . '</th>
							<th class="text-right">' . __('Quantity') . '</th>
							<th>' . __('Unit') . '</th>
							<th class="text-right">' . __('Price') . '</th>
							<th class="text-right">' . __('Discount %') . '</th>
							<th class="text-right">' . __('Total') . '</th>
						</tr>
					</thead>
					<tbody>';

$_SESSION['Items'.$identifier]->total = 0;
$_SESSION['Items'.$identifier]->totalVolume = 0;
$_SESSION['Items'.$identifier]->totalWeight = 0;

foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {

	$LineTotal = $StockItem->Quantity * $StockItem->Price * (1 - $StockItem->DiscountPercent);
	$DisplayLineTotal = locale_number_format($LineTotal,$_SESSION['Items'.$identifier]->CurrDecimalPlaces);
	$DisplayPrice = locale_number_format($StockItem->Price,$_SESSION['Items'.$identifier]->CurrDecimalPlaces);
	$DisplayQuantity = locale_number_format($StockItem->Quantity,$StockItem->DecimalPlaces);
	$DisplayDiscount = locale_number_format(($StockItem->DiscountPercent * 100),2);


	echo '<tr>
			<td><span class="db-badge db-badge-secondary">' . $StockItem->StockID . '</span></td>
			<td title="'. $StockItem->LongDescription . '">' . $StockItem->ItemDescription . '</td>
			<td class="text-right">' . $DisplayQuantity . '</td>
			<td>' . $StockItem->Units . '</td>
			<td class="text-right">' . $DisplayPrice . '</td>
			<td class="text-right">' . $DisplayDiscount . '</td>
			<td class="text-right db-font-bold">' . $DisplayLineTotal . '</td>
		  </tr>';

	$_SESSION['Items'.$identifier]->total += $LineTotal;
	$_SESSION['Items'.$identifier]->totalVolume += ($StockItem->Quantity * $StockItem->Volume);
	$_SESSION['Items'.$identifier]->totalWeight += ($StockItem->Quantity * $StockItem->Weight);
}

$DisplayTotal = locale_number_format($_SESSION['Items'.$identifier]->total,$_SESSION['Items'.$identifier]->CurrDecimalPlaces);
echo '</tbody>
		<tfoot>
			<tr>
				<td colspan="6" class="text-right db-font-bold">' . __('TOTAL Excl Tax/Freight') . '</td>
				<td class="text-right db-font-bold" style="font-size: 1.1rem; color: var(--primary);">' . $DisplayTotal . '</td>
			</tr>
		</tfoot>
	</table></div></div></div>';

echo '<div class="db-bottom-layout">
		<main class="db-col-main">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-calendar-alt"></i> ' . __('Template Settings') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-grid-2">';

if (!isset($_POST['StartDate'])){
	$_POST['StartDate'] = date($_SESSION['DefaultDateFormat']);
}

if ($NewRecurringOrder=='Yes'){
	echo '<div class="db-field">
			<label>' .  __('Start Date') . '</label>
			<input type="date" name="StartDate" class="db-input" value="' . FormatDateForSQL($_POST['StartDate']) .'" />
		</div>';
} else {
	echo '<div class="db-field">
			<label>' .  __('Last Recurrence') . '</label>
			<div class="db-input" style="background: var(--bg-soft);">' . $_POST['StartDate'] . '</div>
			<input type="hidden" name="StartDate" value="' . FormatDateForSQL($_POST['StartDate']) . '" />
		</div>';
}

if (!isset($_POST['StopDate'])){
   $_POST['StopDate'] = date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m'),date('d')+1,date('y')+1));
}

echo '<div class="db-field">
		<label>' .  __('Finish Date') . '</label>
		<input type="date" name="StopDate" class="db-input" value="' . FormatDateForSQL($_POST['StopDate']) .'" />
	</div>';

echo '<div class="db-field">
		<label>' .  __('Frequency of Recurrence') . '</label>
		<select name="Frequency" class="db-select">';

$FreqOptions = [
	52 => __('Weekly'),
	26 => __('Fortnightly'),
	12 => __('Monthly'),
	6 => __('Bi-monthly'),
	4 => __('Quarterly'),
	2 => __('Bi-Annually'),
	1 => __('Annually')
];

foreach ($FreqOptions as $val => $lab) {
	echo '<option ' . ((isset($_POST['Frequency']) and $_POST['Frequency']==$val) ? 'selected' : '') . ' value="' . $val . '">' . $lab . '</option>';
}
echo '  </select>
	</div>';

if ($_SESSION['Items'.$identifier]->AllDummyLineItems()==true){
	echo '<div class="db-field">
			<label>' . __('Invoice Automatically') . '</label>
			<select name="AutoInvoice" class="db-select">
				<option ' . ($_POST['AutoInvoice']==0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
				<option ' . ($_POST['AutoInvoice']==1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
			</select>
		</div>';
} else {
	echo '<input type="hidden" name="AutoInvoice" value="0" />';
}

echo '          </div> <!-- .db-grid-2 -->

				<div class="db-action-btn-row" style="margin-top: var(--space-6);">';
if ($NewRecurringOrder=='Yes'){
	echo '<input type="hidden" name="NewRecurringOrder" value="Yes" />';
	echo '<button type="submit" name="Process" class="db-btn db-btn-primary" style="width: 100%;">
			<i class="fas fa-plus-circle"></i> ' . __('Create Recurring Order') . '
		  </button>';
} else {
	echo '<input type="hidden" name="NewRecurringOrder" value="No" />';
	echo '<input type="hidden" name="ExistingRecurrOrderNo" value="' . $_POST['ExistingRecurrOrderNo'] . '" />';

	echo '<button type="submit" name="Process" class="db-btn db-btn-primary">
			<i class="fas fa-save"></i> ' . __('Update Template') . '
		  </button>';
	echo '<button type="submit" name="DeleteRecurringOrder" class="db-btn db-btn-danger" onclick="return confirm(\'' . __('Are you sure you wish to delete this recurring order template?') . '\');">
			<i class="fas fa-trash"></i> ' . __('Delete Template') . '
		  </button>';
}
echo '          </div>
				</div>
			</div>
		</main>

		<aside class="db-col-aside">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-truck"></i> ' . __('Delivery Details') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-field">
						<label>' . __('Deliver To') . '</label>
						<div class="db-font-bold">' . $_SESSION['Items'.$identifier]->DeliverTo . '</div>
					</div>
					<div class="db-field">
						<label>' . __('Location') . '</label>
						<div>' . $_SESSION['Items'.$identifier]->Location . '</div>
					</div>
					<div class="db-field">
						<label>' . __('Address') . '</label>
						<div style="font-size: 0.9rem; color: var(--text-muted);">
							' . $_SESSION['Items'.$identifier]->DelAdd1 . '<br />
							' . $_SESSION['Items'.$identifier]->DelAdd2 . '<br />
							' . $_SESSION['Items'.$identifier]->DelAdd3 . ' ' . $_SESSION['Items'.$identifier]->DelAdd4 . '
						</div>
					</div>
					<div class="db-field">
						<label>' . __('Reference') . '</label>
						<div>' . ($_SESSION['Items'.$identifier]->CustRef ?: '-') . '</div>
					</div>
				</div>
			</div>
		</aside>
	</div> <!-- .db-bottom-layout -->

	</form>
</div> <!-- .db-page-content -->
</div> <!-- .db-page -->';

include(__DIR__ . '/includes/footer.php');
