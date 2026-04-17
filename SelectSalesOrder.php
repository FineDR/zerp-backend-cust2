<?php

require(__DIR__ . '/includes/session.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$PricesSecurity = 12;

$Title = __('Search Outstanding Sales Orders');
$ViewTopic = 'SalesOrders';
$BookMark = 'SelectSalesOrder';

$ExtraHeadContent = '<link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/sales-orders.css">
                    <script type="text/javascript" src="' . $RootPath . '/javascripts/SalesOrderFunctions.js"></script>';

if (!isset($_POST['AjaxSearch'])) {
	include(__DIR__ . '/includes/header.php');
} else {
	// Start buffering immediately for AJAX to capture all output
	ob_start();
}

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['DueDateFrom'])) {
	$_POST['DueDateFrom'] = ConvertSQLDate($_POST['DueDateFrom']);
}
if (isset($_POST['DueDateTo'])) {
	$_POST['DueDateTo'] = ConvertSQLDate($_POST['DueDateTo']);
}
if (isset($_POST['OrderDateFrom'])) {
	$_POST['OrderDateFrom'] = ConvertSQLDate($_POST['OrderDateFrom']);
}
if (isset($_POST['OrderDateTo'])) {
	$_POST['OrderDateTo'] = ConvertSQLDate($_POST['OrderDateTo']);
}

if (isset($_POST['Reset'])) {
	unset($_POST);
}

if (isset($_POST['Reset'])) {
	unset($_POST);
}

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = $_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = $_POST['SelectedStockItem'];
} else {
	unset($SelectedStockItem);
}

if (isset($_GET['SelectedCustomer'])) {
	$SelectedCustomer = $_GET['SelectedCustomer'];
} elseif (isset($_POST['SelectedCustomer'])) {
	$SelectedCustomer = $_POST['SelectedCustomer'];
} else {
	unset($SelectedCustomer);
}

if (isset($_GET['Quotations'])) {
	$_POST['Quotations'] = $_GET['Quotations'];
} elseif (!isset($_POST['Quotations'])) {
	$_POST['Quotations'] = '';
}

if (isset($_POST['PlacePO'])) { /*user hit button to place PO for selected orders */

	/*Note the button would not have been displayed if the user had no authority to create purchase orders */
	$OrdersToPlacePOFor = '';
	if (isset($_POST['PlacePO_']) && is_array($_POST['PlacePO_'])) {
		for ($i = 0; $i < count($_POST['PlacePO_']); $i++) {
			if ($OrdersToPlacePOFor == '') {
				$OrdersToPlacePOFor .= " orderno= '" . $_POST['PlacePO_'][$i] . "'";
			} else {
				$OrdersToPlacePOFor .= " OR orderno= '" . $_POST['PlacePO_'][$i] . "'";
			}
		}
	}
	if (mb_strlen($OrdersToPlacePOFor) == '') {
		prnMsg(__('There were no sales orders checked to place purchase orders for. No purchase orders will be created.'), 'info');
	} else {
		/*  Now build SQL of items to purchase with purchasing data and preferred suppliers - sorted by preferred supplier */
		$SQL = "SELECT purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact,
						SUM(salesorderdetails.quantity-salesorderdetails.qtyinvoiced) AS orderqty
				FROM purchdata
				INNER JOIN salesorderdetails
					ON purchdata.stockid = salesorderdetails.stkcode
				INNER JOIN stockmaster
					ON purchdata.stockid = stockmaster.stockid
				INNER JOIN stockcategory
					ON stockmaster.categoryid = stockcategory.categoryid
				WHERE purchdata.preferred = 1
					AND purchdata.effectivefrom <= CURRENT_DATE
					AND (" . $OrdersToPlacePOFor . ")
				GROUP BY purchdata.supplierno,
					purchdata.stockid,
					purchdata.price,
					purchdata.suppliers_partno,
					purchdata.supplierdescription,
					purchdata.conversionfactor,
					purchdata.leadtime,
					purchdata.suppliersuom,
					stockmaster.grossweight,
					stockmaster.volume,
					stockcategory.stockact
				ORDER BY purchdata.supplierno,
					 purchdata.stockid";

		$ErrMsg = __('Unable to retrieve the items on the selected orders for creating purchase orders for');
		$ItemResult = DB_query($SQL, $ErrMsg);

		$ItemArray = array();

		while ($MyRow = DB_fetch_array($ItemResult)) {
			$ItemArray[$MyRow['stockid']] = $MyRow;
		}

		/* Now figure out if there are any components of Assembly items that  need to be ordered too */
		$SQL = "SELECT purchdata.supplierno,
						purchdata.stockid,
						purchdata.price,
						purchdata.suppliers_partno,
						purchdata.supplierdescription,
						purchdata.conversionfactor,
						purchdata.leadtime,
						purchdata.suppliersuom,
						stockmaster.grossweight,
						stockmaster.volume,
						stockcategory.stockact,
						SUM(bom.quantity *(salesorderdetails.quantity-salesorderdetails.qtyinvoiced)) AS orderqty
				FROM purchdata
				INNER JOIN bom
					ON purchdata.stockid = bom.component
				INNER JOIN salesorderdetails
					ON bom.parent = salesorderdetails.stkcode
				INNER JOIN stockmaster
					ON purchdata.stockid = stockmaster.stockid
				INNER JOIN stockmaster AS stockmaster2
					ON stockmaster2.stockid = salesorderdetails.stkcode
				INNER JOIN stockcategory
					ON stockmaster.categoryid = stockcategory.categoryid
				WHERE purchdata.preferred = 1
					AND stockmaster2.mbflag = 'A'
					AND bom.loccode = '" . $_SESSION['UserStockLocation'] . "'
					AND purchdata.effectivefrom <= CURRENT_DATE
					AND bom.effectiveafter <= CURRENT_DATE
					AND bom.effectiveto > CURRENT_DATE
					AND (" . $OrdersToPlacePOFor . ")
				GROUP BY purchdata.supplierno,
					purchdata.stockid,
					purchdata.price,
					purchdata.suppliers_partno,
					purchdata.supplierdescription,
					purchdata.conversionfactor,
					purchdata.leadtime,
					purchdata.suppliersuom,
					stockmaster.grossweight,
					stockmaster.volume,
					stockcategory.stockact
				ORDER BY purchdata.supplierno,
					 purchdata.stockid";
		$ErrMsg = __('Unable to retrieve the items on the selected orders for creating purchase orders for');
		$ItemResult = DB_query($SQL, $ErrMsg);

		/* add any assembly item components from salesorders to the ItemArray */
		while ($MyRow = DB_fetch_array($ItemResult)) {
			if (isset($ItemArray[$MyRow['stockid']])) {
				/* if the item is already in the ItemArray then just add the quantity to the existing item */
				$ItemArray[$MyRow['stockid']]['orderqty'] += $MyRow['orderqty'];
			} else { /*it is not already in the ItemArray so add it */
				$ItemArray[$MyRow['stockid']] = $MyRow;
			}
		}


		/* We need the items to order to be in supplier order so that only a single order is created for a supplier - so need to sort the multi-dimensional array to ensure it is listed by supplier sequence. To use array_multisort we need to get arrays of supplier with the same keys as the main array of rows
		 */
		//to make the Supplier array with the keys of the $ItemArray
		$SupplierArray = array_map(function ($Row) {
			return $Row['supplierno']; }, $ItemArray);

		/* Use array_multisort to Sort the ItemArray with supplierno SortedColumn
		Add $ItemArray as the last parameter, to sort by the common key
		*/
		if (count($SupplierArray) > 1) {
			array_multisort($SupplierArray, SORT_ASC, $ItemArray);
		}

		if (count($ItemArray) == 0) {
			prnMsg(__('There might be no supplier purchasing data set up for any items on the selected sales order(s). No purchase orders have been created'), 'warn');
		} else {
			/*Now get the default delivery address details from the users default stock location */
			$SQL = "SELECT locationname,
							deladd1,
							deladd2,
							deladd3,
							deladd4,
							deladd5,
							deladd6,
							tel,
							contact
						FROM locations
						INNER JOIN locationusers
							ON locationusers.loccode = locations.loccode
							AND locationusers.userid = '" . $_SESSION['UserID'] . "'
							AND locationusers.canupd = 1
						WHERE locations.loccode = '" . $_SESSION['UserStockLocation'] . "'";
			$ErrMsg = __('The delivery address for the order could not be obtained from the user default stock location');
			$DelAddResult = DB_query($SQL, $ErrMsg);
			$DelAddRow = DB_fetch_array($DelAddResult);

			$SupplierID = '';

			if (IsEmailAddress($_SESSION['UserEmail'])) {
				$UserDetails = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName'] . '</a>';
			} else {
				$UserDetails = ' ' . $_SESSION['UsersRealName'] . ' ';
			}

			foreach ($ItemArray as $ItemRow) {

				if ($SupplierID != $ItemRow['supplierno']) {
					/* This order item is purchased from a different supplier so need to finish off the authorisation of the previous order and start a new order */

					if ($SupplierID != '' AND $_SESSION['AutoAuthorisePO'] == 1) {
						/* if an order is/has been created already and the supplier of this item has changed - so need to finish off the order */
						//if the user has authority to authorise the PO then it should be created as authorised
						$AuthSQL = "SELECT authlevel
					 				FROM purchorderauth
									WHERE userid = '" . $_SESSION['UserID'] . "'
									AND currabrev = '" . $SuppRow['currcode'] . "'";

						$AuthResult = DB_query($AuthSQL);
						$AuthRow = DB_fetch_array($AuthResult);
						if ($AuthRow['authlevel'] == '') {
							$AuthRow['authlevel'] = 0;
						}

						if (DB_num_rows($AuthResult) > 0 AND $AuthRow['authlevel'] > $Order_Value) { //user has authority to authrorise as well as create the order
							$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order Created and Authorised by') . ' ' . $UserDetails . ' - ' . __('Auto created from sales orders') . '<br />';
							$ErrMsg = __('Could not update purchase order status to Authorised');
							$Result = DB_query("UPDATE purchorders SET allowprint = 1,
												   status = 'Authorised',
												   stat_comment = '" . $StatusComment . "'
												WHERE orderno = '" . $PO_OrderNo . "'",
								$ErrMsg,
								'',
								true
							);
						} else { // no authority to authorise this order
							if (DB_num_rows($AuthResult) == 0) {
								$AuthMessage = __('Your authority to approve purchase orders in') . ' ' . $SuppRow['currcode'] . ' ' . __('has not yet been set up') . '<br />';
							} else {
								$AuthMessage = __('You can only authorise up to') . ' ' . $SuppRow['currcode'] . ' ' . $AuthRow['authlevel'] . '.<br />';
							}

							prnMsg(__('You do not have permission to authorise this purchase order') . '.<br />' . __('This order is for') . ' ' .
								$SuppRow['currcode'] . ' ' . $Order_Value . '. ' .
								$AuthMessage . __('If you think this is a mistake please contact the systems administrator') . '<br />' .
								__('The order has been created with a status of pending and will require authorisation'), 'warn');
						}
					} //end of authorisation status settings

					if ($SupplierID != '') { //then we have just added a purchase order
						echo '<br />';
						prnMsg(__('Purchase Order') . ' ' . $PO_OrderNo . ' ' . __('on') . ' ' . $SupplierID . ' ' . __('has been created'), 'success');
						DB_Txn_Commit();
					}

					/*Starting a new purchase order with a different supplier */
					DB_Txn_Begin();

					$PO_OrderNo = GetNextTransNo(18); //get the next PO number

					$SupplierID = $ItemRow['supplierno'];
					$Order_Value = 0;
					/*Now get all the required details for the supplier */
					$SQL = "SELECT address1,
	 							address2,
	 							address3,
	 							address4,
	 							address5,
	 							address6,
	 							telephone,
	 							paymentterms,
	 							currcode,
	 							rate
						 FROM suppliers INNER JOIN currencies
							ON suppliers.currcode = currencies.currabrev
							WHERE supplierid = '" . $SupplierID . "'";

					$ErrMsg = __('Could not get the supplier information for the order');
					$SuppResult = DB_query($SQL, $ErrMsg);
					$SuppRow = DB_fetch_array($SuppResult);

					$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order Created by') . ' ' . $UserDetails . ' - ' . __('Auto created from sales orders') . '<br />';
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
		  									  version,
		  									  revised,
		  									  deliveryby,
		  									  status,
		  									  stat_comment,
		  									  deliverydate,
		  									  paymentterms,
		  									  allowprint)
		  									VALUES(	'" . $PO_OrderNo . "',
		  										'" . $SupplierID . "',
		  										CURRENT_DATE,
		  										'" . $SuppRow['rate'] . "',
		  										'" . $_SESSION['UserID'] . "',
		  										'" . $_SESSION['UserStockLocation'] . "',
		  										'" . $DelAddRow['deladd1'] . "',
		  										'" . $DelAddRow['deladd2'] . "',
		  										'" . $DelAddRow['deladd3'] . "',
		  										'" . $DelAddRow['deladd4'] . "',
		  										'" . $DelAddRow['deladd5'] . "',
		  										'" . $DelAddRow['deladd6'] . "',
		  										'" . $DelAddRow['tel'] . "',
		  										'" . $SuppRow['address1'] . "',
		  										'" . $SuppRow['address2'] . "',
		  										'" . $SuppRow['address3'] . "',
		  										'" . $SuppRow['address4'] . "',
		  										'" . $SuppRow['address5'] . "',
		  										'" . $SuppRow['address6'] . "',
		  										'" . $SuppRow['telephone'] . "',
		  										'" . $SuppRow['contact'] . "',
		  										'1.0',
		  										CURRENT_DATE,
		  										'" . $_SESSION['Default_Shipper'] . "',
		  										'Pending',
		  										'" . $StatusComment . "',
		  										CURRENT_DATE,
		  										'" . $SuppRow['paymentterms'] . "',
		  										0)";

					$ErrMsg = __('The purchase order header record could not be inserted into the database because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} //end if it's a new supplier and PO to create

				/*reminder we are in a loop of the total of each item to place a purchase order for based on a selection of sales orders */
				$DeliveryDate = DateAdd(date($_SESSION['DefaultDateFormat']), 'd', $ItemRow['leadtime']);
				$SQL = "INSERT INTO purchorderdetails ( orderno,
			  									itemcode,
			  									deliverydate,
			  									itemdescription,
			  									glcode,
			  									unitprice,
			  									quantityord,
			  									suppliersunit,
			  									suppliers_partno,
			  									conversionfactor )
						  VALUES ('" . $PO_OrderNo . "',
			  							 '" . $ItemRow['stockid'] . "',
			  							 '" . FormatDateForSQL($DeliveryDate) . "',
			  							 '" . $ItemRow['suppliers_partno'] . '  ' . $ItemRow['supplierdescription'] . "',
			  							 '" . $ItemRow['stockact'] . "',
			  							 '" . $ItemRow['price'] / $ItemRow['conversionfactor'] . "',
			  							 '" . $ItemRow['orderqty'] . "',
			  							 '" . $ItemRow['suppliersuom'] . "',
			  							 '" . $ItemRow['suppliers_partno'] . "',
			  							 '" . $ItemRow['conversionfactor'] . "')";
				$ErrMsg = __('One of the purchase order detail records could not be inserted into the database because');

				$Result = DB_query($SQL, $ErrMsg, '', true);
				$Order_Value += ($ItemRow['price'] * $ItemRow['orderqty'] / $ItemRow['conversionfactor']);
			} /* end of the loop round the items on the sales order  that we wish to place purchase orders for */


			/* The last line to be purchase ordered was reach so there will be an order which is not yet completed in progress now to completed it */

			if ($SupplierID != '' AND $_SESSION['AutoAuthorisePO'] == 1) {
				//if the user has authority to authorise the PO then it should be created as authorised
				$AuthSQL = "SELECT authlevel
							FROM purchorderauth
							WHERE userid = '" . $_SESSION['UserID'] . "'
							AND currabrev = '" . $SuppRow['currcode'] . "'";

				$AuthResult = DB_query($AuthSQL);
				$AuthRow = DB_fetch_array($AuthResult);
				if ($AuthRow['authlevel'] == '') {
					$AuthRow['authlevel'] = 0;
				}

				if (DB_num_rows($AuthResult) > 0 AND $AuthRow['authlevel'] > $Order_Value) { //user has authority to authrorise as well as create the order
					$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order Created and Authorised by') . $UserDetails . ' - ' . __('Auto created from sales orders') . '<br />';
					$ErrMsg = __('Could not update purchase order status to Authorised');
					$Result = DB_query("UPDATE purchorders SET allowprint = 1,
															status = 'Authorised',
															stat_comment = '" . $StatusComment . "'
												 WHERE orderno = '" . $PO_OrderNo . "'",
						$ErrMsg,
						'',
						true
					);
				} else { // no authority to authorise this order
					if (DB_num_rows($AuthResult) == 0) {
						$AuthMessage = __('Your authority to approve purchase orders in') . ' ' . $SuppRow['currcode'] . ' ' . __('has not yet been set up') . '<br />';
					} else {
						$AuthMessage = __('You can only authorise up to') . ' ' . $SuppRow['currcode'] . ' ' . $AuthRow['authlevel'] . '.<br />';
					}

					prnMsg(__('You do not have permission to authorise this purchase order') . '.<br />' . __('This order is for') . ' ' . $SuppRow['currcode'] . ' ' . $Order_Value . '. ' . $AuthMessage . __('If you think this is a mistake please contact the systems administrator') . '<br />' . __('The order has been created with a status of pending and will require authorisation'), 'warn');
				}
			} //end of authorisation status settings

			if ($SupplierID != '') { //then we have just added a purchase order irrespective of autoauthorise status
				echo '<br />';
				prnMsg(__('Purchase Order') . ' ' . $PO_OrderNo . ' ' . __('on') . ' ' . $SupplierID . ' ' . __('has been created'), 'success');
				DB_Txn_Commit();
			}
			$Result = DB_query("UPDATE salesorders SET poplaced = 1 WHERE " . $OrdersToPlacePOFor);
		}/*There were items that had purchasing data set up to create POs for */
	} /* there were sales orders checked to place POs for */
}/*end of purchase order creation code */
/* ******************************************************************************************* */

/* To the sales order selection form */

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . __('Outstanding Sales Orders') . '</h2>
				<p class="db-page-subtitle">' . __('Real-time search and management dashboard') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php?NewOrder=Yes' . (isset($SelectedCustomer) ? '&SelectedCustomer=' . urlencode($SelectedCustomer) : '') . '" class="db-btn db-btn-primary">+ ' . __('New Sales Order') . '</a>
			</div>
		</div>
		
		<div class="db-page-content">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="SalesOrderForm">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <!-- Modern Search Dashboard -->
                <div class="search-dashboard card-v2">
                    <div class="smart-search-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="SmartSearch" id="SmartSearch" 
                               class="smart-search-input" 
                               placeholder="' . __('Search by Order #, Customer Name, or Part Code...') . '" 
                               value="' . (isset($_POST['SmartSearch']) ? $_POST['SmartSearch'] : '') . '" />
                    </div>
                    
                    <button type="button" id="ToggleFilters" class="advanced-filters-trigger">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2L10 12.46V19L14 21V12.46L22 3Z"></path></svg>
                        ' . __('Advanced Filters') . '
                    </button>
                    
                    <div id="AdvancedFiltersPanel" class="advanced-filters-panel" style="display: none;">
                        <div class="form-group">
                            <label>' . __('Stock Location') . '</label>
                            <select name="StockLocation" class="filter-input">';
$SQL = "SELECT locationname, locations.loccode FROM locations 
                                        INNER JOIN locationusers ON locationusers.loccode = locations.loccode 
                                        AND locationusers.userid = '" . $_SESSION['UserID'] . "' AND locationusers.canview = 1";
$ResultStkLocs = DB_query($SQL);
while ($MyRow = DB_fetch_array($ResultStkLocs)) {
	$selected = ($MyRow['loccode'] == ($_POST['StockLocation'] ?? $_SESSION['UserStockLocation'])) ? 'selected' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
}
echo '              </select>
                        </div>
                        <div class="form-group">
                            <label>' . __('Show Only') . '</label>
                            <select name="Quotations" class="filter-input">
                                <option value="Orders_Only" ' . ($_POST['Quotations'] == 'Orders_Only' ? 'selected' : '') . '>' . __('Orders') . '</option>
                                <option value="Quotes_Only" ' . ($_POST['Quotations'] == 'Quotes_Only' ? 'selected' : '') . '>' . __('Quotations') . '</option>
                                <option value="Overdue_Only" ' . ($_POST['Quotations'] == 'Overdue_Only' ? 'selected' : '') . '>' . __('Overdue') . '</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>' . __('Due Date From') . '</label>
                            <input type="date" name="DueDateFrom" class="filter-input" value="' . (isset($_POST['DueDateFrom']) ? FormatDateForSQL($_POST['DueDateFrom']) : '') . '" />
                        </div>
                        <div class="form-group">
                            <label>' . __('Due Date To') . '</label>
                            <input type="date" name="DueDateTo" class="filter-input" value="' . (isset($_POST['DueDateTo']) ? FormatDateForSQL($_POST['DueDateTo']) : '') . '" />
                        </div>
                    </div>
                </div>

                <div id="BulkActionsBar" class="bulk-actions-bar">
                    <span id="SelectedCount">0 items selected</span>
                    <div class="db-action-group">
                        <button type="submit" name="PlacePO" class="db-btn db-btn-light" style="color:var(--so-primary)">
                            ' . __('Place Purchase Orders') . '
                        </button>
                    </div>
                </div>';


if (isset($_POST['SearchParts'])) {
	$StockItemsResult = GetSearchItems();
}

if (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} elseif (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
}

// Result fetching logic
if (true) {
	if (isset($StockItemsResult) AND DB_num_rows($StockItemsResult) == 1) {
		$MyStkRow = DB_fetch_array($StockItemsResult);
		$SelectedStockItem = $MyStkRow['stockid'];
	}

	// Figure out the SQL required from the inputs available
	if ($_POST['Quotations'] == 'Quotes_Only') {
		$Quotations = 1;
		$QuotationFilter = " AND salesorders.quotation = 1 ";
	} elseif ($_POST['Quotations'] == 'Overdue_Only') {
		$Quotations = 0;
		$QuotationFilter = " AND salesorders.quotation = 0 AND salesorders.deliverydate < CURRENT_DATE ";
	} else {
		$_POST['Quotations'] = 'Orders_Only';
		$Quotations = 0;
		$QuotationFilter = " AND salesorders.quotation = 0 ";
	}

	$SQL = "SELECT salesorders.orderno,
					debtorsmaster.name,
					custbranch.brname,
					salesorders.customerref,
					salesorders.orddate,
					salesorders.deliverydate,
					salesorders.deliverto,
					salesorders.printedpackingslip,
					salesorders.poplaced,
					SUM(salesorderdetails.unitprice*(salesorderdetails.quantity-salesorderdetails.qtyinvoiced)*(1-salesorderdetails.discountpercent)/currencies.rate) AS ordervalue
				FROM salesorders
				INNER JOIN salesorderdetails
					ON salesorders.orderno = salesorderdetails.orderno
				INNER JOIN debtorsmaster
					ON salesorders.debtorno = debtorsmaster.debtorno
				INNER JOIN custbranch
					ON debtorsmaster.debtorno = custbranch.debtorno
					AND salesorders.branchcode = custbranch.branchcode
				INNER JOIN currencies
					ON debtorsmaster.currcode = currencies.currabrev
				WHERE salesorderdetails.completed = 0 " . $QuotationFilter;

	if (isset($_POST['DueDateFrom']) AND is_date($_POST['DueDateFrom'])) {
		$SQL .= " AND salesorders.deliverydate >= '" . FormatDateForSQL($_POST['DueDateFrom']) . "' ";
	}
	if (isset($_POST['DueDateTo']) AND is_date($_POST['DueDateTo'])) {
		$SQL .= " AND salesorders.deliverydate <= '" . FormatDateForSQL($_POST['DueDateTo']) . "' ";
	}
	if (isset($_POST['OrderDateFrom']) AND is_date($_POST['OrderDateFrom'])) {
		$SQL .= " AND salesorders.orddate >= '" . FormatDateForSQL($_POST['OrderDateFrom']) . "' ";
	}
	if (isset($_POST['OrderDateTo']) AND is_date($_POST['OrderDateTo'])) {
		$SQL .= " AND salesorders.orddate <= '" . FormatDateForSQL($_POST['OrderDateTo']) . "' ";
	}

	if (!isset($_POST['StockLocation'])) {
		$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
	}
	if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != 'All') {
		$SQL .= " AND salesorders.fromstkloc = '" . $_POST['StockLocation'] . "' ";
	}

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND salesorders.salesperson = '" . $_SESSION['SalesmanLogin'] . "' ";
	}

	// Smart Search Logic
	if (isset($_POST['SmartSearch']) && !empty($_POST['SmartSearch'])) {
		$SearchTerm = DB_escape_string($_POST['SmartSearch']);
		$SQL .= " AND (salesorders.orderno LIKE '%$SearchTerm%' 
					OR salesorders.customerref LIKE '%$SearchTerm%' 
					OR debtorsmaster.name LIKE '%$SearchTerm%' 
					OR salesorderdetails.stkcode LIKE '%$SearchTerm%') ";
	}

	// Context-aware filters (e.g. from links)
	if (isset($SelectedCustomer) AND $SelectedCustomer != '') {
		$SQL .= " AND salesorders.debtorno = '" . $SelectedCustomer . "' ";
	}
	if (isset($SelectedStockItem) AND $SelectedStockItem != '') {
		$SQL .= " AND salesorderdetails.stkcode = '" . $SelectedStockItem . "' ";
	}

	$SQL .= ' GROUP BY salesorders.orderno,
					debtorsmaster.name,
					custbranch.brname,
					salesorders.customerref,
					salesorders.orddate,
					salesorders.deliverydate,
					salesorders.deliverto,
					salesorders.printedpackingslip,
					salesorders.poplaced
				ORDER BY salesorders.orderno';
}

$ErrMsg = __('No orders or quotations were returned by the SQL because');
$SalesOrdersResult = DB_query($SQL, $ErrMsg);

/*show a table of the orders returned by the SQL */
if (DB_num_rows($SalesOrdersResult) > 0) {

	/* Get users authority to place POs */
	$AuthSQL = "SELECT cancreate
					FROM purchorderauth
					WHERE userid = '" . $_SESSION['UserID'] . "'";

	/*we don't know what currency these orders might be in but if no authority at all then don't show option*/
	$AuthResult = DB_query($AuthSQL);

	$AuthRow = DB_fetch_array($AuthResult);

	$StatusBadge = '';
	if ($_POST['Quotations'] == 'Quotes_Only') {
		$StatusBadge = '<span class="db-badge db-badge-success">' . __('Quotation') . '</span>';
	} elseif ($_POST['Quotations'] == 'Overdue_Only') {
		$StatusBadge = '<span class="db-badge db-badge-danger">' . __('Overdue') . '</span>';
	} else {
		$StatusBadge = '<span class="db-badge db-badge-success">' . __('Order') . '</span>';
	}

	echo '<div class="card-v2" style="margin-top: var(--space-6);">
				<div class="card-header-v2">
					<h3>' . ($_POST['Quotations'] == 'Quotes_Only' ? __('Quotations') : __('Outstanding Orders')) . ' ' . $StatusBadge . '</h3>
					<span class="tag">' . DB_num_rows($SalesOrdersResult) . ' ' . __('Found') . '</span>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">';
	if (is_null($AuthRow)) {
		$canCreate = 1;
	} else {
		$canCreate = $AuthRow['cancreate'];
	}
	if (is_null($canCreate)) {
		$canCreate = 1;
	}

	echo '<thead>
                <tr>
                    <th width="40"><input type="checkbox" class="so-check-all custom-checkbox"></th>
                    <th>' . __('Order') . '</th>
                    <th>' . __('Customer') . '</th>
                    <th>' . __('Ref') . '</th>
                    <th>' . __('Dates') . '</th>
                    <th class="text-right">' . __('Value') . ' (' . $_SESSION['CompanyRecord']['currencydefault'] . ')</th>
                    <th>' . __('Status') . '</th>
                    <th class="text-right">' . __('Actions') . '</th>
                </tr>
            </thead>
			<tbody id="OrdersTableBody">';

	$OrdersTotal = 0;

	while ($MyRow = DB_fetch_array($SalesOrdersResult)) {
		if (isset($MyRow['orderno'])) {
			$ModifyPage = $RootPath . '/SelectOrderItems.php?ModifyOrderNumber=' . urlencode((string) $MyRow['orderno']);
			$ConfirmDispatch = $RootPath . '/ConfirmDispatch_Invoice.php?OrderNumber=' . urlencode((string) $MyRow['orderno']);
			$PrintPickNote = $RootPath . '/GeneratePickingList.php?TransNo=' . urlencode((string) $MyRow['orderno']);
			$PrintAcknowledge = $RootPath . '/PDFAck.php?AcknowledgementNo=' . urlencode((string) $MyRow['orderno']);

			if ($_SESSION['PackNoteFormat'] == 1) { /*Laser printed A4 default */
				$PrintDispatchNote = $RootPath . '/PrintCustOrder_generic.php?TransNo=' . urlencode((string) $MyRow['orderno']);
			} else { /*pre-printed stationery default */
				$PrintDispatchNote = $RootPath . '/PrintCustOrder.php?TransNo=' . urlencode((string) $MyRow['orderno']);
			}
			$PrintQuotation = $RootPath . '/PDFQuotation.php?QuotationNo=' . urlencode((string) $MyRow['orderno']) . '&orientation=landscape';
			$PrintQuotationPortrait = $RootPath . '/PDFQuotation.php?QuotationNo=' . urlencode((string) $MyRow['orderno']) . '&orientation=portrait';
			$FormatedDelDate = isset($MyRow['deliverydate']) && $MyRow['deliverydate'] != '' ? ConvertSQLDate($MyRow['deliverydate']) : '';
			$FormatedOrderDate = isset($MyRow['orddate']) && $MyRow['orddate'] != '' ? ConvertSQLDate($MyRow['orddate']) : '';
			$FormatedOrderValue = locale_number_format($MyRow['ordervalue'], $_SESSION['CompanyRecord']['decimalplaces']);

			$OrdersTotal += $MyRow['ordervalue'];

			if (!isset($PricesSecurity) or !in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
				$FormatedOrderValue = '---------';
			}

			if ($_POST['Quotations'] == 'Orders_Only' OR $_POST['Quotations'] == 'Overdue_Only') {
				$StatusText = ($MyRow['poplaced'] == 1) ? __('PO Placed') : __('Pending');
				$StatusClass = ($MyRow['poplaced'] == 1) ? 'badge-blue' : 'badge-orange';
				if ($MyRow['printedpackingslip'] == 1) {
					$StatusText = __('Printed');
					$StatusClass = 'badge-green';
				}

				echo '<tr>
								<td><input type="checkbox" name="PlacePO_[]" value="', $MyRow['orderno'], '" class="so-row-check custom-checkbox"></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--so-primary);">#', $MyRow['orderno'], '</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">', $FormatedOrderDate, '</div>
                                </td>
								<td>
                                    <div class="cust-name" style="font-weight: 600;">', $MyRow['name'], '</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">', $MyRow['brname'], '</div>
                                </td>
								<td>', $MyRow['customerref'], '</td>
								<td>
                                    <div style="font-size: 0.75rem; color: #64748b;">' . __('Due') . ':</div>
                                    <div style="font-weight: 500;">', $FormatedDelDate, '</div>
                                </td>
								<td class="text-right val-bold" style="font-family: monospace; font-size: 1rem;">', $FormatedOrderValue, '</td>
                                <td><span class="badge ', $StatusClass, '">', $StatusText, '</span></td>
								<td class="text-right">
									<div class="db-action-group" style="justify-content: flex-end; gap: 4px;">
										<a href="', $ModifyPage, '" title="' . __('Modify') . '" class="db-btn db-btn-secondary" style="padding: 6px;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
										</a>
										<a href="', $ConfirmDispatch, '" title="' . __('Confirm') . '" class="db-btn db-btn-secondary" style="padding: 6px; background: #ecfdf5; color: #059669; border: none;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
										</a>
                                        <div class="db-dropdown" style="position: relative; display: inline-block;">
                                            <button type="button" class="db-btn db-btn-secondary" style="padding: 6px;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === \'none\' ? \'block\' : \'none\'">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"></circle><circle cx="12" cy="5" r="1"></circle><circle cx="12" cy="19" r="1"></circle></svg>
                                            </button>
                                            <div class="db-dropdown-content" style="display: none; position: absolute; right: 0; background: white; border: 1px solid var(--so-border); border-radius: 8px; box-shadow: var(--shadow-lg); z-index: 100; min-width: 150px; text-align: left;">
                                                <a href="', $PrintAcknowledge, '" style="display: block; padding: 8px 12px; font-size: 0.8rem;">' . __('Acknowledgment') . '</a>
                                                <a href="', $PrintDispatchNote, '" style="display: block; padding: 8px 12px; font-size: 0.8rem;">' . __('Dispatch Note') . '</a>';
				if ($_SESSION['RequirePickingNote'] == 1) {
					echo '              <a href="', $PrintPickNote, '" style="display: block; padding: 8px 12px; font-size: 0.8rem;">' . __('Pick List') . '</a>';
				}
				echo '              </div>
                                        </div>
									</div>
								</td>
							</tr>';
			} else { /*Quotations*/
				echo '<tr>
								<td><input type="checkbox" disabled class="custom-checkbox"></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--so-primary);">Q#', $MyRow['orderno'], '</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">', $FormatedOrderDate, '</div>
                                </td>
								<td class="cust-name">
                                    <div style="font-weight: 600;">', $MyRow['name'], '</div>
                                    <div style="font-size: 0.75rem; color: #64748b;">', $MyRow['brname'], '</div>
                                </td>
								<td>', $MyRow['customerref'], '</td>
								<td>', $FormatedDelDate, '</td>
								<td class="text-right val-bold" style="font-family: monospace; font-size: 1rem;">', $FormatedOrderValue, '</td>
                                <td><span class="badge badge-orange">' . __('Quotation') . '</span></td>
								<td class="text-right">
									<div class="db-action-group" style="justify-content: flex-end; gap: 4px;">
										<a href="', $ModifyPage, '" title="' . __('Modify') . '" class="db-btn db-btn-secondary" style="padding: 6px;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
										</a>
										<a href="', $PrintQuotation, '" target="_blank" title="' . __('Quotation') . '" class="db-btn db-btn-secondary" style="padding: 6px;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m4-10l5 5 5-5m-5 5V3"></path></svg>
										</a>
									</div>
								</td>
							</tr>';
			}
		}
	}//end while loop through orders to display

	echo '</tbody>
			<tfoot>
				<tr>
					<td colspan="5" class="text-right"><b>';

	if ($_POST['Quotations'] == 'Orders_Only') {
		echo __('Total Order(s) Value in');
	} else {
		echo __('Total Quotation(s) Value in');
	}
	if (!isset($PricesSecurity) or !in_array($PricesSecurity, $_SESSION['AllowedPageSecurityTokens'])) {
		$OrdersTotal = '---------';
	}

	echo ' ' . $_SESSION['CompanyRecord']['currencydefault'] . ':</b></td>
			<td class="text-right val-bold" style="font-size: 1.1rem; color: var(--so-primary);">' . locale_number_format($OrdersTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
            <td colspan="2"></td>
        </tr>
			</tfoot>
		</table>';

	if (isset($_POST['AjaxSearch'])) {
		$TableRows = ob_get_clean();
		echo $TableRows;
		exit();
	}

	echo '</div>
		</div>';
} //end if there are some orders to show

echo '		</div> <!-- End MainBody -->
		</div> <!-- End db-page-content -->
	</div> <!-- End db-page -->
	  </form>
	</div> <!-- End dashboard-shell-container -->';



include(__DIR__ . '/includes/footer.php');

function GetSearchItems($SqlConstraint = '')
{

	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		echo __('Stock description keywords have been used in preference to the Stock code extract entered');
	}

	$SQL = "SELECT stockmaster.stockid,
				   stockmaster.description,
				   stockmaster.decimalplaces,
				   SUM(locstock.quantity) AS qoh,
				   stockmaster.units
			FROM salesorderdetails INNER JOIN stockmaster
				ON salesorderdetails.stkcode = stockmaster.stockid AND completed = 0
			INNER JOIN locstock
			  ON stockmaster.stockid = locstock.stockid";

	if (
		isset($_POST['StockCat'])
		AND ((trim($_POST['StockCat']) == '') OR $_POST['StockCat'] == 'All')
	) {
		$WhereStockCat = '';
	} else {
		$WhereStockCat = " AND stockmaster.categoryid = '" . $_POST['StockCat'] . "' ";
	}

	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL .= " WHERE stockmaster.description " . LIKE . " '" . $SearchString . "' " . $WhereStockCat;

	} elseif (isset($_POST['StockCode'])) {
		$SQL .= " WHERE stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'" . $WhereStockCat;

	} elseif (!isset($_POST['StockCode']) AND !isset($_POST['Keywords'])) {
		$SQL .= " WHERE stockmaster.categoryid = '" . $_POST['StockCat'] . "'";

	}

	$SQL .= $SqlConstraint;
	$SQL .= " GROUP BY stockmaster.stockid,
						stockmaster.description,
						stockmaster.decimalplaces,
						stockmaster.units
						ORDER BY stockmaster.stockid";

	$ErrMsg = __('No stock items were returned by the SQL because');
	$StockItemsResult = DB_query($SQL, $ErrMsg);

	return $StockItemsResult;
}
