<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

require(__DIR__ . '/includes/session.php');


if (isset($_GET['NewOrder']) && empty($_GET['identifier'])) {
	$identifier = date('U');
	$RedirectURL = $RootPath . '/SelectOrderItems.php?NewOrder=Yes&identifier=' . $identifier;
	if (isset($_GET['SelectedCustomer'])) {
		$RedirectURL .= '&DebtorNo=' . urlencode($_GET['SelectedCustomer']);
	} elseif (isset($_GET['DebtorNo'])) {
		$RedirectURL .= '&DebtorNo=' . urlencode($_GET['DebtorNo']);
	}
	header('Location: ' . $RedirectURL);
	exit;
}

if (isset($_POST['identifier'])) {
	$identifier = $_POST['identifier'];
} elseif (isset($_GET['identifier'])) {
	$identifier = $_GET['identifier'];
} else {
	/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
	$identifier = date('U');
}

include(__DIR__ . '/includes/GetPrice.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

/* AJAX Endpoint for Search/Add to Cart/Update/Remove */
if (isset($_GET['Ajax'])) {
	header('Content-Type: application/json');

	function get_cart_data($identifier) {
		if (!isset($_SESSION['Items' . $identifier])) {
			return ['status' => 'inactive', 'error' => 'No branch selected'];
		}
		$cart = $_SESSION['Items' . $identifier];
		$items = [];
		$total = 0;
		
		foreach ($cart->LineItems as $line) {
			$lineTotal = $line->Quantity * $line->Price * (1 - $line->DiscountPercent);
			$items[] = [
				'LineNumber' => $line->LineNumber,
				'StockID' => $line->StockID,
				'ItemDescription' => $line->ItemDescription,
				'Quantity' => $line->Quantity,
				'Price' => $line->Price,
				'DiscountPercent' => $line->DiscountPercent,
				'Units' => $line->Units,
				'DecimalPlaces' => $line->DecimalPlaces,
				'LineTotal' => $lineTotal,
				'DisplayLineTotal' => locale_number_format($lineTotal, $cart->CurrDecimalPlaces),
				'Invoiced' => $cart->Some_Already_Delivered($line->LineNumber)
			];
			$total += $lineTotal;
		}

		return [
			'status' => 'active',
			'Items' => $items,
			'ItemsOrdered' => $cart->ItemsOrdered,
			'Subtotal' => $total,
			'DisplaySubtotal' => locale_number_format($total, $cart->CurrDecimalPlaces),
			'Currency' => $cart->DefaultCurrency,
			'DecimalPlaces' => $cart->CurrDecimalPlaces,
			'DebtorNo' => $cart->DebtorNo,
			'BranchCode' => $cart->Branch,
			'CustomerName' => $cart->CustomerName
		];
	}

	if ($_GET['Ajax'] == 'AddToCart') {
		$NewItem = $_GET['StockID'];
		$NewItemQty = filter_number_format($_GET['Qty']) ?? 1;
		$NewItemDue = date($_SESSION['DefaultDateFormat']);
		$NewPOLine = 0;
		
		ob_start();
		include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
		ob_end_clean();
		
		echo json_encode(['status' => 'success', 'cart' => get_cart_data($identifier)]);
		exit();
	}

	if ($_GET['Ajax'] == 'RemoveItem') {
		$LineNumber = $_GET['LineNumber'];
		$_SESSION['Items' . $identifier]->remove_from_cart($LineNumber, 'Yes', $identifier);
		echo json_encode(['status' => 'success', 'cart' => get_cart_data($identifier)]);
		exit();
	}

	if ($_GET['Ajax'] == 'UpdateQty') {
		$LineNumber = $_GET['LineNumber'];
		$Qty = filter_number_format($_GET['Qty']);
		$line = $_SESSION['Items' . $identifier]->LineItems[$LineNumber];
		
		$_SESSION['Items' . $identifier]->update_cart_item(
			$LineNumber,
			$Qty,
			$line->Price,
			$line->DiscountPercent,
			$line->Narrative,
			'Yes',
			$line->ItemDue,
			$line->POLine,
			$line->GPPercent,
			$identifier
		);
		echo json_encode(['status' => 'success', 'cart' => get_cart_data($identifier)]);
		exit();
	}

	if ($_GET['Ajax'] == 'GetCart') {
		echo json_encode(get_cart_data($identifier));
		exit();
	}

	if ($_GET['Ajax'] == 'SearchProducts') {
		$Keywords = mb_strtoupper($_GET['Keywords']);
		$StockCat = $_GET['StockCat'] ?? 'All';
		$SearchString = '%' . str_replace(' ', '%', $Keywords) . '%';
		
		$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.longdescription,
						stockmaster.units,
						stockmaster.decimalplaces,
						stockcategory.categorydescription
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
				AND stockmaster.mbflag <>'G'
				AND stockmaster.discontinued=0
				AND (stockmaster.description " . LIKE . " '" . $SearchString . "' OR stockmaster.stockid " . LIKE . " '" . $SearchString . "') ";
		
		if ($StockCat != 'All') {
			$SQL .= " AND stockmaster.categoryid='" . $StockCat . "' ";
		}
		
		$SQL .= " ORDER BY stockmaster.stockid LIMIT " . $_SESSION['DisplayRecordsMax'];
		
		$Result = DB_query($SQL);
		$products = [];
		while ($row = DB_fetch_array($Result)) {
			// Get Price & Stock
			$price = GetPrice($row['stockid'], $_SESSION['Items' . $identifier]->DebtorNo, $_SESSION['Items' . $identifier]->Branch);
			$qoh = GetQuantityOnHand($row['stockid'], $_SESSION['Items' . $identifier]->Location);
			
			$products[] = [
				'StockID' => $row['stockid'],
				'Description' => $row['description'],
				'LongDescription' => $row['longdescription'],
				'Units' => $row['units'],
				'Price' => $price,
				'DisplayPrice' => locale_number_format($price, $_SESSION['Items' . $identifier]->CurrDecimalPlaces),
				'QOH' => $qoh,
				'DisplayQOH' => locale_number_format($qoh, $row['decimalplaces'])
			];
		}
		
		echo json_encode(['status' => 'success', 'products' => $products]);
		exit();
	}
}

if (isset($_GET['ModifyOrderNumber'])) {
	$Title = __('Modifying Order') . ' ' . $_GET['ModifyOrderNumber'];
} else {
	$Title = __('Select Order Items');
}
$ViewTopic = 'SalesOrders';
$ExtraHeadContent = '<link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/styles.css">
					<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
					<script src="' . $RootPath . '/javascripts/SelectOrderItems.js"></script>';
include(__DIR__ . '/includes/header.php');

    echo '<div class="db-page">
        <div class="db-page-header">
            <div>
                <h2 class="db-page-title">' . $Title . '</h2>
                <p class="db-page-subtitle">' . __('Manage your sale order items') . '</p>
            </div>
            <div class="db-header-actions">
                <a href="' . $RootPath . '/CounterSales.php" class="db-btn db-btn-secondary"><i class="fas fa-cash-register"></i> ' . __('Switch to POS') . '</a>';
    
    if (isset($_SESSION['Items'.$identifier]) && $_SESSION['Items'.$identifier]->ItemsOrdered >= 1) {
        echo '<input name="CancelOrder" form="deleteform" type="submit" class="db-btn db-btn-danger" value="' . __('Cancel Order') . '" onclick="return confirm(\'' . __('Are you sure you wish to cancel this entire order?') . '\');" />';
    }
    
    echo '    </div>
        </div>';

    // Tab Navigation
    echo '<div class="db-tab-container">
            <div class="db-tabs">
                <button type="button" class="db-tab-btn active" data-tab="search"><i class="fas fa-search"></i> ' . __('Search Products') . '</button>
                <button type="button" class="db-tab-btn" data-tab="csv"><i class="fas fa-file-csv"></i> ' . __('Import CSV') . '</button>
    </div>
        </div>';

    // Simple Tab Switching JS
    echo '<script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const tabs = document.querySelectorAll(".db-tab-btn");
            const panes = document.querySelectorAll(".db-tab-pane");
            
            tabs.forEach(tab => {
                tab.addEventListener("click", () => {
                    tabs.forEach(t => t.classList.remove("active"));
                    panes.forEach(p => p.classList.remove("active"));
                    
                    tab.classList.add("active");
                    const target = tab.getAttribute("data-tab");
                    document.getElementById("tab-" + target).classList.add("active");
                    
                    // Store active tab in session storage to persist across reloads
                    sessionStorage.setItem("activeOrderTab", target);
                });
            });
            
            // Restore active tab
            const activeTab = sessionStorage.getItem("activeOrderTab");
            if (activeTab) {
                const tab = document.querySelector(`.db-tab-btn[data-tab="${activeTab}"]`);
                if (tab) tab.click();
            }
        });
    </script>';


if (isset($_POST['QuickEntry'])){
	unset($_POST['PartSearch']);
}

if (isset($_POST['SelectingOrderItems'])){
	foreach ($_POST as $FormVariable => $Quantity) {
		if (mb_strpos($FormVariable,'OrderQty')!==false) {
			$NewItemArray[$_POST['StockID' . mb_substr($FormVariable,8)]] = filter_number_format($Quantity);
		}
	}
}

if (isset($_POST['UploadFile'])) {
	if (isset($_FILES['CSVFile']) and $_FILES['CSVFile']['name']) {
		//check file info
		$FileName = $_FILES['CSVFile']['name'];
		$TempName = $_FILES['CSVFile']['tmp_name'];
		$FileSize = $_FILES['CSVFile']['size'];
		//get file handle
		$FileHandle = fopen($TempName, 'r');
		$Row = 0;
		$InsertNum = 0;
		while (($FileRow = fgetcsv($FileHandle, 10000, ",")) !== false) {
			/* Check the stock code exists */
			++$Row;
			$SQL = "SELECT stockid FROM stockmaster WHERE stockid='" . $FileRow[0] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) > 0) {
				$NewItemArray[$FileRow[0]] = filter_number_format($FileRow[1]);
				++$InsertNum;
			}
		}
	}
	$_POST['SelectingOrderItems'] = 1;
	if (sizeof($NewItemArray) == 0) {
		prnMsg(__('There are no items that can be imported'), 'error');
	} else {
		prnMsg($InsertNum . ' ' . __('of') . ' ' . $Row . ' ' . __('rows have been added to the order'), 'info');
	}
}

if (isset($_GET['NewItem'])){
	$NewItem = trim($_GET['NewItem']);
}

if (isset($_GET['NewOrder'])){
  /*New order entry - clear any existing order details from the Items object and initiate a newy*/
	 if (isset($_SESSION['Items'.$identifier])){
		unset ($_SESSION['Items'.$identifier]->LineItems);
		$_SESSION['Items'.$identifier]->ItemsOrdered=0;
		unset ($_SESSION['Items'.$identifier]);
	}

	$_SESSION['ExistingOrder' .$identifier]=0;
	$_SESSION['Items'.$identifier] = new Cart;

	if ($CustomerLogin==1){ //its a customer logon
		$_SESSION['Items'.$identifier]->DebtorNo=$_SESSION['CustomerID'];
		$_SESSION['Items'.$identifier]->BranchCode=$_SESSION['UserBranch'];
		$SelectedCustomer = $_SESSION['CustomerID'];
		$SelectedBranch = $_SESSION['UserBranch'];
		$_SESSION['RequireCustomerSelection'] = 0;
	} else {
		$_SESSION['Items'.$identifier]->DebtorNo='';
		$_SESSION['Items'.$identifier]->BranchCode='';
		$_SESSION['RequireCustomerSelection'] = 1;
	}

}

if (isset($_GET['ModifyOrderNumber'])
	AND $_GET['ModifyOrderNumber']!=''){

/* The delivery check screen is where the details of the order are either updated or inserted depending on the value of ExistingOrder */

	if (isset($_SESSION['Items'.$identifier])){
		unset ($_SESSION['Items'.$identifier]->LineItems);
		unset ($_SESSION['Items'.$identifier]);
	}
	$_SESSION['ExistingOrder'.$identifier]=$_GET['ModifyOrderNumber'];
	$_SESSION['RequireCustomerSelection'] = 0;
	$_SESSION['Items'.$identifier] = new Cart;

/*read in all the guff from the selected order into the Items cart  */

	$OrderHeaderSQL = "SELECT salesorders.debtorno,
			 				  debtorsmaster.name,
							  salesorders.branchcode,
							  salesorders.customerref,
							  salesorders.comments,
							  salesorders.orddate,
							  salesorders.ordertype,
							  salestypes.sales_type,
							  salesorders.shipvia,
							  salesorders.deliverto,
							  salesorders.deladd1,
							  salesorders.deladd2,
							  salesorders.deladd3,
							  salesorders.deladd4,
							  salesorders.deladd5,
							  salesorders.deladd6,
							  salesorders.contactphone,
							  salesorders.contactemail,
							  salesorders.salesperson,
							  salesorders.freightcost,
							  salesorders.deliverydate,
							  debtorsmaster.currcode,
							  currencies.decimalplaces,
							  paymentterms.terms,
							  salesorders.fromstkloc,
							  salesorders.printedpackingslip,
							  salesorders.datepackingslipprinted,
							  salesorders.quotation,
							  salesorders.quotedate,
							  salesorders.confirmeddate,
							  salesorders.deliverblind,
							  debtorsmaster.customerpoline,
							  locations.locationname,
							  custbranch.estdeliverydays,
							  custbranch.salesman
						FROM salesorders
						INNER JOIN debtorsmaster
						ON salesorders.debtorno = debtorsmaster.debtorno
						INNER JOIN salestypes
						ON salesorders.ordertype=salestypes.typeabbrev
						INNER JOIN custbranch
						ON salesorders.debtorno = custbranch.debtorno
						AND salesorders.branchcode = custbranch.branchcode
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN locations
						ON locations.loccode=salesorders.fromstkloc
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1
						WHERE salesorders.orderno = '" . $_GET['ModifyOrderNumber'] . "'";

	$ErrMsg =  __('The order cannot be retrieved because');
	$GetOrdHdrResult = DB_query($OrderHeaderSQL, $ErrMsg);

	if (DB_num_rows($GetOrdHdrResult)==1) {

		$MyRow = DB_fetch_array($GetOrdHdrResult);
		if ($_SESSION['SalesmanLogin']!='' AND $_SESSION['SalesmanLogin']!=$MyRow['salesman']){
			prnMsg(__('Your account is set up to see only a specific salespersons orders. You are not authorised to modify this order'),'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		if ($CustomerLogin == 1 AND $_SESSION['CustomerID'] != $MyRow['debtorno']) {
			echo '<p class="bad">' . __('This transaction is addressed to another customer and cannot be displayed for privacy reasons') . '. ' . __('Please select only transactions relevant to your company').'</p>';
			include(__DIR__ . '/includes/footer.php');
			exit();

		}
		$_SESSION['Items'.$identifier]->OrderNo = $_GET['ModifyOrderNumber'];
		$_SESSION['Items'.$identifier]->DebtorNo = $MyRow['debtorno'];
		$_SESSION['Items'.$identifier]->CreditAvailable = GetCreditAvailable($_SESSION['Items'.$identifier]->DebtorNo);
/*CustomerID defined in header.php */
		$_SESSION['Items'.$identifier]->Branch = $MyRow['branchcode'];
		$_SESSION['Items'.$identifier]->CustomerName = $MyRow['name'];
		$_SESSION['Items'.$identifier]->CustRef = $MyRow['customerref'];
		$_SESSION['Items'.$identifier]->Comments = stripcslashes($MyRow['comments']);
		$_SESSION['Items'.$identifier]->PaymentTerms =$MyRow['terms'];
		$_SESSION['Items'.$identifier]->DefaultSalesType =$MyRow['ordertype'];
		$_SESSION['Items'.$identifier]->SalesTypeName =$MyRow['sales_type'];
		$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currcode'];
		$_SESSION['Items'.$identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
		$_SESSION['Items'.$identifier]->ShipVia = $MyRow['shipvia'];
		$BestShipper = $MyRow['shipvia'];
		$_SESSION['Items'.$identifier]->DeliverTo = $MyRow['deliverto'];
		$_SESSION['Items'.$identifier]->DeliveryDate = ConvertSQLDate($MyRow['deliverydate']);
		$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow['deladd1'];
		$_SESSION['Items'.$identifier]->DelAdd2 = $MyRow['deladd2'];
		$_SESSION['Items'.$identifier]->DelAdd3 = $MyRow['deladd3'];
		$_SESSION['Items'.$identifier]->DelAdd4 = $MyRow['deladd4'];
		$_SESSION['Items'.$identifier]->DelAdd5 = $MyRow['deladd5'];
		$_SESSION['Items'.$identifier]->DelAdd6 = $MyRow['deladd6'];
		$_SESSION['Items'.$identifier]->PhoneNo = $MyRow['contactphone'];
		$_SESSION['Items'.$identifier]->Email = $MyRow['contactemail'];
		$_SESSION['Items'.$identifier]->SalesPerson = $MyRow['salesperson'];
		$_SESSION['Items'.$identifier]->Location = $MyRow['fromstkloc'];
		$_SESSION['Items'.$identifier]->LocationName = $MyRow['locationname'];
		$_SESSION['Items'.$identifier]->Quotation = $MyRow['quotation'];
		$_SESSION['Items'.$identifier]->QuoteDate = ConvertSQLDate($MyRow['quotedate']);
		$_SESSION['Items'.$identifier]->ConfirmedDate = ConvertSQLDate($MyRow['confirmeddate']);
		$_SESSION['Items'.$identifier]->FreightCost = $MyRow['freightcost'];
		$_SESSION['Items'.$identifier]->Orig_OrderDate = $MyRow['orddate'];
		$_SESSION['PrintedPackingSlip'] = $MyRow['printedpackingslip'];
		$_SESSION['DatePackingSlipPrinted'] = $MyRow['datepackingslipprinted'];
		$_SESSION['Items'.$identifier]->DeliverBlind = $MyRow['deliverblind'];
		$_SESSION['Items'.$identifier]->DefaultPOLine = $MyRow['customerpoline'];
		$_SESSION['Items'.$identifier]->DeliveryDays = $MyRow['estdeliverydays'];

		//Get The exchange rate used for GPPercent calculations on adding or amending items
		if ($_SESSION['Items'.$identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault']){
			$ExRateResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items'.$identifier]->DefaultCurrency . "'");
			if (DB_num_rows($ExRateResult)>0){
				$ExRateRow = DB_fetch_row($ExRateResult);
				$ExRate = $ExRateRow[0];
			} else {
				$ExRate =1;
			}
		} else {
			$ExRate = 1;
		}

/*need to look up customer name from debtors master then populate the line items array with the sales order details records */

			$LineItemsSQL = "SELECT salesorderdetails.orderlineno,
									salesorderdetails.stkcode,
									stockmaster.description,
									stockmaster.longdescription,
									stockmaster.volume,
									stockmaster.grossweight,
									stockmaster.units,
									stockmaster.serialised,
									stockmaster.nextserialno,
									stockmaster.eoq,
									salesorderdetails.unitprice,
									salesorderdetails.quantity,
									salesorderdetails.discountpercent,
									salesorderdetails.actualdispatchdate,
									salesorderdetails.qtyinvoiced,
									salesorderdetails.narrative,
									salesorderdetails.itemdue,
									salesorderdetails.poline,
									locstock.quantity as qohatloc,
									stockmaster.mbflag,
									stockmaster.discountcategory,
									stockmaster.decimalplaces,
									stockmaster.actualcost AS standardcost,
									salesorderdetails.completed
								FROM salesorderdetails INNER JOIN stockmaster
								ON salesorderdetails.stkcode = stockmaster.stockid
								INNER JOIN locstock ON locstock.stockid = stockmaster.stockid
								WHERE  locstock.loccode = '" . $MyRow['fromstkloc'] . "'
								AND salesorderdetails.orderno ='" . $_GET['ModifyOrderNumber'] . "'
								ORDER BY salesorderdetails.orderlineno";

		$ErrMsg = __('The line items of the order cannot be retrieved because');
		$LineItemsResult = DB_query($LineItemsSQL, $ErrMsg);
		if (DB_num_rows($LineItemsResult)>0) {

			while ($MyRow=DB_fetch_array($LineItemsResult)) {
					if ($MyRow['completed']==0){
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
																	$MyRow['actualdispatchdate'],
																	$MyRow['qtyinvoiced'],
																	$MyRow['discountcategory'],
																	0,	/*Controlled*/
																	$MyRow['serialised'],
																	$MyRow['decimalplaces'],
																	$MyRow['narrative'],
																	'No', /* Update DB */
																	$MyRow['orderlineno'],
																	0,
																	'',
																	ConvertSQLDate($MyRow['itemdue']),
																	$MyRow['poline'],
																	$MyRow['standardcost'],
																	$MyRow['eoq'],
																	$MyRow['nextserialno'],
																	$ExRate,
																	$identifier );

				/*Just populating with existing order - no DBUpdates */
					}
					$LastLineNo = $MyRow['orderlineno'];
			} /* line items from sales order details */
			 $_SESSION['Items'.$identifier]->LineCounter = $LastLineNo+1;
		} //end of checks on returned data set
	}
}


if (!isset($_SESSION['Items'.$identifier])){
	/* It must be a new order being created $_SESSION['Items'.$identifier] would be set up from the order
	modification code above if a modification to an existing order. Also $ExistingOrder would be
	set to 1. The delivery check screen is where the details of the order are either updated or
	inserted depending on the value of ExistingOrder */

	$_SESSION['ExistingOrder'.$identifier]=0;
	$_SESSION['Items'.$identifier] = new Cart;
	$_SESSION['PrintedPackingSlip'] = 0; /*Of course cos the order aint even started !!*/

	if (in_array($_SESSION['PageSecurityArray']['ConfirmDispatch_Invoice.php'], $_SESSION['AllowedPageSecurityTokens'])
		AND ($_SESSION['Items'.$identifier]->DebtorNo==''
		OR !isset($_SESSION['Items'.$identifier]->DebtorNo))){

	/* need to select a customer for the first time out if authorisation allows it and if a customer
	 has been selected for the order or not the session variable CustomerID holds the customer code
	 already as determined from user id /password entry  */
		$_SESSION['RequireCustomerSelection'] = 1;
	} else {
		$_SESSION['RequireCustomerSelection'] = 0;
	}
}

if (isset($_POST['ChangeCustomer']) AND $_POST['ChangeCustomer']!=''){

	if ($_SESSION['Items'.$identifier]->Any_Already_Delivered()==0){
		$_SESSION['RequireCustomerSelection']=1;
	} else {
		prnMsg(__('The customer the order is for cannot be modified once some of the order has been invoiced'),'warn');
	}
}

// Pick up DebtorNo from URL if we are in customer selection mode and starting a new flow
if (!isset($SelectedCustomer) AND isset($_GET['DebtorNo']) AND isset($_SESSION['Items' . $identifier]) AND ($_SESSION['Items' . $identifier]->DebtorNo == '' OR $_SESSION['RequireCustomerSelection'] == 1)) {
	$SelectedCustomer = $_GET['DebtorNo'];
	// Try to find the first branch for this customer if none specified
	$SQL = "SELECT branchcode FROM custbranch WHERE debtorno='" . DB_escape_string($SelectedCustomer) . "' AND disabletrans=0 LIMIT 1";
	$Result = DB_query($SQL);
	if ($MyRow = DB_fetch_array($Result)) {
		$SelectedBranch = $MyRow['branchcode'];
	}
}

//Customer logins are not allowed to select other customers hence in_array($_SESSION['PageSecurityArray']['ConfirmDispatch_Invoice.php'], $_SESSION['AllowedPageSecurityTokens'])
if (isset($_POST['SearchCust'])
	AND $_SESSION['RequireCustomerSelection']==1
	AND in_array($_SESSION['PageSecurityArray']['ConfirmDispatch_Invoice.php'], $_SESSION['AllowedPageSecurityTokens'])){

		$SQL = "SELECT custbranch.brname,
					custbranch.contactname,
					custbranch.phoneno,
					custbranch.faxno,
					custbranch.branchcode,
					custbranch.debtorno,
					debtorsmaster.name
				FROM custbranch
				LEFT JOIN debtorsmaster
				ON custbranch.debtorno=debtorsmaster.debtorno
				WHERE custbranch.disabletrans=0 ";

	if (($_POST['CustKeywords']=='') AND ($_POST['CustCode']=='')  AND ($_POST['CustPhone']=='')) {
		$SQL .= "";
	} else {
		//insert wildcard characters in spaces
		$_POST['CustKeywords'] = mb_strtoupper(trim($_POST['CustKeywords']));
		$SearchString = str_replace(' ', '%', $_POST['CustKeywords']) ;

		$SQL .= "AND custbranch.brname " . LIKE . " '%" . $SearchString . "%'
				AND custbranch.branchcode " . LIKE . " '%" . mb_strtoupper(trim($_POST['CustCode'])) . "%'
				AND custbranch.phoneno " . LIKE . " '%" . trim($_POST['CustPhone']) . "%'";

	} /*one of keywords or custcode was more than a zero length string */
	if ($_SESSION['SalesmanLogin']!=''){
		$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL .=	" ORDER BY custbranch.debtorno,
					custbranch.branchcode";

	$ErrMsg = __('The searched customer records requested cannot be retrieved because');
	$Result_CustSelect = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result_CustSelect)==1){
		$MyRow=DB_fetch_array($Result_CustSelect);
		$SelectedCustomer = $MyRow['debtorno'];
		$SelectedBranch = $MyRow['branchcode'];
	} elseif (DB_num_rows($Result_CustSelect)==0){
		prnMsg(__('No Customer Branch records contain the search criteria') . ' - ' . __('please try again') . ' - ' . __('Note a Customer Branch Name may be different to the Customer Name'),'info');
	}
} /*end of if search for customer codes/names */

if (isset($_POST['JustSelectedACustomer'])){

	/*Need to figure out the number of the form variable that the user clicked on */
	for ($i=0;$i<count($_POST);$i++){ //loop through the returned customers
		if (isset($_POST['SubmitCustomerSelection'.$i])){
			break;
		}
	}
	if ($i==count($_POST) AND !isset($SelectedCustomer)){//if there is ONLY one customer searched at above, the $SelectedCustomer already setup, then there is a wrong warning
		prnMsg(__('Unable to identify the selected customer'),'error');
	} elseif (!isset($SelectedCustomer)) {
		$SelectedCustomer = $_POST['SelectedCustomer'.$i];
		$SelectedBranch = $_POST['SelectedBranch'.$i];
	}
}

/* will only be true if page called from customer selection form or set because only one customer
 record returned from a search so parse the $SelectCustomer string into customer code and branch code */
if (isset($SelectedCustomer)) {

	$_SESSION['Items'.$identifier]->DebtorNo = trim($SelectedCustomer);
	$_SESSION['Items'.$identifier]->Branch = trim($SelectedBranch);

	// Now check to ensure this account is not on hold */
	$SQL = "SELECT debtorsmaster.name,
					holdreasons.dissallowinvoices,
					debtorsmaster.salestype,
					salestypes.sales_type,
					debtorsmaster.currcode,
					debtorsmaster.customerpoline,
					paymentterms.terms,
					currencies.decimalplaces
			FROM debtorsmaster INNER JOIN holdreasons
			ON debtorsmaster.holdreason=holdreasons.reasoncode
			INNER JOIN salestypes
			ON debtorsmaster.salestype=salestypes.typeabbrev
			INNER JOIN paymentterms
			ON debtorsmaster.paymentterms=paymentterms.termsindicator
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtorsmaster.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo. "'";

	$ErrMsg = __('The details of the customer selected') . ': ' .  $_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($Result);
	if ($MyRow[1] != 1){
		if ($MyRow[1]==2){
			prnMsg(__('The') . ' ' . htmlspecialchars($MyRow[0], ENT_QUOTES, 'UTF-8', false) . ' ' . __('account is currently flagged as an account that needs to be watched. Please contact the credit control personnel to discuss'),'warn');
		}

		$_SESSION['RequireCustomerSelection']=0;
		$_SESSION['Items'.$identifier]->CustomerName = $MyRow['name'];

# the sales type determines the price list to be used by default the customer of the user is
# defaulted from the entry of the userid and password.

		$_SESSION['Items'.$identifier]->DefaultSalesType = $MyRow['salestype'];
		$_SESSION['Items'.$identifier]->SalesTypeName = $MyRow['sales_type'];
		$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currcode'];
		$_SESSION['Items'.$identifier]->DefaultPOLine = $MyRow['customerpoline'];
		$_SESSION['Items'.$identifier]->PaymentTerms = $MyRow['terms'];
		$_SESSION['Items'.$identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];

# the branch was also selected from the customer selection so default the delivery details from the customer branches table CustBranch. The order process will ask for branch details later anyway
		$Result = GetCustBranchDetails($identifier);

		if (DB_num_rows($Result)==0){

			prnMsg(__('The branch details for branch code') . ': ' . $_SESSION['Items'.$identifier]->Branch . ' ' . __('against customer code') . ': ' . $_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('could not be retrieved') . '. ' . __('Check the set up of the customer and branch'),'error');

			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		// add echo
		echo '<br />';
		$MyRow = DB_fetch_array($Result);
		if ($_SESSION['SalesmanLogin']!=NULL AND $_SESSION['SalesmanLogin']!=$MyRow['salesman']){
			prnMsg(__('Your login is only set up for a particular salesperson. This customer has a different salesperson.'),'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		$_SESSION['Items'.$identifier]->DeliverTo = $MyRow['brname'];
		$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow['braddress1'];
		$_SESSION['Items'.$identifier]->DelAdd2 = $MyRow['braddress2'];
		$_SESSION['Items'.$identifier]->DelAdd3 = $MyRow['braddress3'];
		$_SESSION['Items'.$identifier]->DelAdd4 = $MyRow['braddress4'];
		$_SESSION['Items'.$identifier]->DelAdd5 = $MyRow['braddress5'];
		$_SESSION['Items'.$identifier]->DelAdd6 = $MyRow['braddress6'];
		$_SESSION['Items'.$identifier]->PhoneNo = $MyRow['phoneno'];
		$_SESSION['Items'.$identifier]->Email = $MyRow['email'];
		$_SESSION['Items'.$identifier]->Location = $MyRow['defaultlocation'];
		$_SESSION['Items'.$identifier]->ShipVia = $MyRow['defaultshipvia'];
		$_SESSION['Items'.$identifier]->DeliverBlind = $MyRow['deliverblind'];
		$_SESSION['Items'.$identifier]->SpecialInstructions = $MyRow['specialinstructions'];
		$_SESSION['Items'.$identifier]->DeliveryDays = $MyRow['estdeliverydays'];
		$_SESSION['Items'.$identifier]->LocationName = $MyRow['locationname'];
		if ($_SESSION['SalesmanLogin']!= NULL AND $_SESSION['SalesmanLogin']!=''){
			$_SESSION['Items'.$identifier]->SalesPerson = $_SESSION['SalesmanLogin'];
		} else {
			$_SESSION['Items'.$identifier]->SalesPerson = $MyRow['salesman'];
		}
		if ($_SESSION['Items'.$identifier]->SpecialInstructions)
		  prnMsg($_SESSION['Items'.$identifier]->SpecialInstructions,'warn');

		if ($_SESSION['CheckCreditLimits'] > 0){  /*Check credit limits is 1 for warn and 2 for prohibit sales */
			$_SESSION['Items'.$identifier]->CreditAvailable = GetCreditAvailable($_SESSION['Items'.$identifier]->DebtorNo);

			if ($_SESSION['CheckCreditLimits']==1 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0){
				prnMsg(__('The') . ' ' . htmlspecialchars($MyRow[0], ENT_QUOTES, 'UTF-8', false) . ' ' . __('account is currently at or over their credit limit'),'warn');
			} elseif ($_SESSION['CheckCreditLimits']==2 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0){
				prnMsg(__('No more orders can be placed by') . ' ' . htmlspecialchars($MyRow[0], ENT_QUOTES, 'UTF-8', false) . ' ' . __(' their account is currently at or over their credit limit'),'warn');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
		}

	} else {
		prnMsg(__('The') . ' ' . htmlspecialchars($MyRow[0], ENT_QUOTES, 'UTF-8', false) . ' ' . __('account is currently on hold please contact the credit control personnel to discuss'),'warn');
	}

} elseif (!$_SESSION['Items'.$identifier]->DefaultSalesType
			OR $_SESSION['Items'.$identifier]->DefaultSalesType=='')	{

#Possible that the check to ensure this account is not on hold has not been done
#if the customer is placing own order, if this is the case then
#DefaultSalesType will not have been set as above

	$SQL = "SELECT debtorsmaster.name,
					holdreasons.dissallowinvoices,
					debtorsmaster.salestype,
					debtorsmaster.currcode,
					currencies.decimalplaces,
					debtorsmaster.customerpoline
			FROM debtorsmaster
			INNER JOIN holdreasons
			ON debtorsmaster.holdreason=holdreasons.reasoncode
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtorsmaster.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

	$ErrMsg = __('The details for the customer selected') . ': ' .$_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) > 0) {
		$MyRow = DB_fetch_array($Result);

		if ($MyRow['dissallowinvoices'] == 0){

			$_SESSION['Items'.$identifier]->CustomerName = $MyRow[0];

# the sales type determines the price list to be used by default the customer of the user is
# defaulted from the entry of the userid and password.

			$_SESSION['Items'.$identifier]->DefaultSalesType = $MyRow['salestype'];
			$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currcode'];
			$_SESSION['Items'.$identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
			$_SESSION['Items'.$identifier]->Branch = $_SESSION['UserBranch'];
			$_SESSION['Items'.$identifier]->DefaultPOLine = $MyRow['customerpoline'];

	// the branch would be set in the user data so default delivery details as necessary. However,
	// the order process will ask for branch details later anyway

			$Result = GetCustBranchDetails($identifier);
			$MyRow = DB_fetch_array($Result);
			$_SESSION['Items'.$identifier]->DeliverTo = $MyRow['brname'];
			$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow['braddress1'];
			$_SESSION['Items'.$identifier]->DelAdd2 = $MyRow['braddress2'];
			$_SESSION['Items'.$identifier]->DelAdd3 = $MyRow['braddress3'];
			$_SESSION['Items'.$identifier]->DelAdd4 = $MyRow['braddress4'];
			$_SESSION['Items'.$identifier]->DelAdd5 = $MyRow['braddress5'];
			$_SESSION['Items'.$identifier]->DelAdd6 = $MyRow['braddress6'];
			$_SESSION['Items'.$identifier]->PhoneNo = $MyRow['phoneno'];
			$_SESSION['Items'.$identifier]->Email = $MyRow['email'];
			$_SESSION['Items'.$identifier]->Location = $MyRow['defaultlocation'];
			$_SESSION['Items'.$identifier]->DeliverBlind = $MyRow['deliverblind'];
			$_SESSION['Items'.$identifier]->DeliveryDays = $MyRow['estdeliverydays'];
			$_SESSION['Items'.$identifier]->LocationName = $MyRow['locationname'];
			if ($_SESSION['SalesmanLogin']!= NULL AND $_SESSION['SalesmanLogin']!=''){
				$_SESSION['Items'.$identifier]->SalesPerson = $_SESSION['SalesmanLogin'];
			} else {
			$_SESSION['Items'.$identifier]->SalesPerson = $MyRow['salesman'];
			}
		} else {
			prnMsg(__('Sorry, your account has been put on hold for some reason, please contact the credit control personnel.'),'warn');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	}
}

if ($_SESSION['RequireCustomerSelection'] ==1
	OR !isset($_SESSION['Items'.$identifier]->DebtorNo)
	OR $_SESSION['Items'.$identifier]->DebtorNo=='') {

	echo '<div class="db-pos-wrapper">
			<div class="db-pos-main">';

	echo '<div class="db-page-header">
			<h2 class="db-title"><i class="fas fa-user-plus db-icon-green"></i> ' . __('Select Customer') . '</h2>
			<p class="db-subtitle">' . __('Search for a customer branch to start your order.') . '</p>
		</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
			<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />
			<div class="db-global-search-container">
				<div class="db-search-input-group">
					<input type="text" name="CustKeywords" id="CustomerSearch" class="db-input" placeholder="' . __('Search by name, code or phone...') . '" value="' . (isset($_POST['CustKeywords']) ? $_POST['CustKeywords'] : '') . '" autofocus />
					<button type="submit" name="SearchCust" class="db-btn db-btn-primary">
						<i class="fas fa-search"></i> ' . __('Search Now') . '
					</button>
				</div>
				<div class="db-field-group" style="margin-top: var(--space-3); display: flex; gap: var(--space-4);">
					<input type="text" name="CustCode" class="db-input db-input-sm" placeholder="' . __('Branch Code') . '" value="' . (isset($_POST['CustCode']) ? $_POST['CustCode'] : '') . '" style="width: auto;" />
					<input type="text" name="CustPhone" class="db-input db-input-sm" placeholder="' . __('Phone Number') . '" value="' . (isset($_POST['CustPhone']) ? $_POST['CustPhone'] : '') . '" style="width: auto;" />
				</div>
			</div>';

	if (isset($Result_CustSelect)) {
        echo '<input name="JustSelectedACustomer" type="hidden" value="Yes" />
			<div class="db-customer-grid">';

		$j = 0;
		while ($MyRow=DB_fetch_array($Result_CustSelect)) {
			echo '<div class="db-customer-card">
					<div class="db-customer-header">
						<div class="db-customer-icon">
							<i class="fas fa-building"></i>
						</div>
						<div class="db-customer-details">
							<h4>' . htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8', false) . '</h4>
							<p>' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8', false). '</p>
						</div>
					</div>
					<div class="db-customer-meta">
						<div class="db-customer-meta-item">
							<i class="fas fa-id-badge"></i> <b>' . $MyRow['debtorno'] . ' / ' . $MyRow['branchcode'] . '</b>
						</div>
						<div class="db-customer-meta-item">
							<i class="fas fa-user"></i> ' . $MyRow['contactname'] . '
						</div>
						<div class="db-customer-meta-item">
							<i class="fas fa-phone"></i> ' . $MyRow['phoneno'] . '
						</div>
					</div>
					<input type="submit" name="SubmitCustomerSelection' . $j .'" value="' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8', false). '" />
					<input name="SelectedCustomer' . $j .'" type="hidden" value="'.$MyRow['debtorno'].'" />
					<input name="SelectedBranch' . $j .'" type="hidden" value="'. $MyRow['branchcode'].'" />
				  </div>';
			$j++;
		}
		echo '</div>'; // .db-customer-grid
	}
	echo '</form>';
	
	echo '</div><!-- .db-pos-main -->
		  <div class="db-pos-sidebar">
			<div class="db-card" style="height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: var(--text-muted); opacity: 0.6; min-height: 400px;">
				<i class="fas fa-shopping-cart" style="font-size: 3rem; margin-bottom: var(--space-4);"></i>
				<h3>' . __('Cart Inactive') . '</h3>
				<p>' . __('Please select a customer branch to start adding products.') . '</p>
			</div>
		  </div>
		  </div><!-- .db-pos-wrapper -->';

} else { //dont require customer selection
	$Msg ='';

	echo '<div class="db-pos-wrapper">
			<div class="db-pos-main">';

	if (!isset($_POST['CancelOrder'])) {
		echo '<div class="db-card db-customer-info-card">
				<div class="db-card-header">
					<h3 class="db-card-title">
						<i class="fas fa-file-invoice"></i> ' . 
						($_SESSION['Items'.$identifier]->Quotation==1 ? __('Quotation') : __('Sales Order')) . '
					</h3>
					<div class="db-card-actions">
						<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
							<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />
							<input type="submit" name="ChangeCustomer" class="db-btn db-btn-sm db-btn-secondary" value="' . __('Change Customer') . '" />
						</form>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-info-grid">
						<div class="db-info-item">
							<i class="fas fa-user db-icon-green"></i>
							<div>
								<label>' . __('Customer') . '</label>
								<span><b>' . $_SESSION['Items'.$identifier]->DebtorNo . '</b> - ' . htmlspecialchars($_SESSION['Items'.$identifier]->CustomerName, ENT_QUOTES, 'UTF-8', false) . '</span>
							</div>
						</div>
						<div class="db-info-item">
							<i class="fas fa-truck db-icon-green"></i>
							<div>
								<label>' . __('Deliver To') . '</label>
								<span>' . htmlspecialchars($_SESSION['Items'.$identifier]->DeliverTo, ENT_QUOTES, 'UTF-8', false) . '</span>
							</div>
						</div>
						<div class="db-info-item">
							<i class="fas fa-warehouse db-icon-green"></i>
							<div>
								<label>' . __('From Location') . '</label>
								<span>' . $_SESSION['Items'.$identifier]->LocationName . '</span>
							</div>
						</div>
						<div class="db-info-item">
							<i class="fas fa-tags db-icon-green"></i>
							<div>
								<label>' . __('Sales Type / Terms') . '</label>
								<span>' . $_SESSION['Items'.$identifier]->SalesTypeName . ' | ' . $_SESSION['Items'.$identifier]->PaymentTerms . '</span>
							</div>
						</div>
					</div>
				</div>
			</div>';
	}
	$Msg ='';
	if (isset($_POST['Search']) OR isset($_POST['Next']) OR isset($_POST['Previous'])){
		if (!empty($_POST['RawMaterialFlag'])){
			$RawMaterialSellable = " OR stockcategory.stocktype='M'";
		} else {
			$RawMaterialSellable = '';
		}
		if (!empty($_POST['CustItemFlag'])){
			$IncludeCustItem = " INNER JOIN custitem ON custitem.stockid=stockmaster.stockid
								AND custitem.debtorno='" .  $_SESSION['Items'.$identifier]->DebtorNo . "' ";
		} else {
			$IncludeCustItem = " LEFT OUTER JOIN custitem ON custitem.stockid=stockmaster.stockid
								AND custitem.debtorno='" .  $_SESSION['Items'.$identifier]->DebtorNo . "' ";
		}

		if ($_POST['Keywords']!='' AND $_POST['StockCode']=='') {
			$Msg='<div class="page_help_text">' . __('Order Item description has been used in search') . '.</div>';
		} elseif ($_POST['StockCode']!='' AND $_POST['Keywords']=='') {
			$Msg='<div class="page_help_text">' . __('Stock Code has been used in search') . '.</div>';
		} elseif ($_POST['Keywords']=='' AND $_POST['StockCode']=='') {
			$Msg='<div class="page_help_text">' . __('Stock Category has been used in search') . '.</div>';
		}
		$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.longdescription,
						stockmaster.units,
						stockmaster.decimalplaces,
						custitem.cust_part,
						custitem.cust_description
				FROM stockmaster INNER JOIN stockcategory
				ON stockmaster.categoryid=stockcategory.categoryid
				" . $IncludeCustItem . "
				WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L' " . $RawMaterialSellable . ")
				AND stockmaster.mbflag <>'G'
				AND stockmaster.discontinued=0 ";

		if (isset($_POST['Keywords']) AND mb_strlen($_POST['Keywords'])>0) {
			//insert wildcard characters in spaces
			$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

			if ($_POST['StockCat']=='All'){
				$SQL .= "AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					ORDER BY stockmaster.stockid";
			} else {
				$SQL .= "AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
			}

		} elseif (mb_strlen($_POST['StockCode'])>0){

			$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
			$SearchString = '%' . $_POST['StockCode'] . '%';

			if ($_POST['StockCat']=='All'){
				$SQL .= "AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					ORDER BY stockmaster.stockid";
			} else {
				$SQL .= "AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					 AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					 ORDER BY stockmaster.stockid";
			}

		} else {
			if ($_POST['StockCat']=='All'){
				$SQL .= "ORDER BY stockmaster.stockid";
			} else {
				$SQL .= "AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					 ORDER BY stockmaster.stockid";
			  }
		}

		if (isset($_POST['Next'])) {
			$Offset = $_POST['NextList'];
		}
		if (isset($_POST['Previous'])) {
			$Offset = $_POST['PreviousList'];
		}
		if (!isset($Offset) OR $Offset < 0) {
			$Offset=0;
		}

		$SQL = $SQL . " LIMIT " . $_SESSION['DisplayRecordsMax'] . " OFFSET " . strval($_SESSION['DisplayRecordsMax'] * $Offset);

		$ErrMsg = __('There is a problem selecting the part records to display because');

		$SearchResult = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($SearchResult)==0 ){
			prnMsg(__('There are no products available meeting the criteria specified'),'info');
		}
		if (DB_num_rows($SearchResult)==1){
			$MyRow=DB_fetch_array($SearchResult);
			$NewItem = $MyRow['stockid'];
			DB_data_seek($SearchResult,0);
		}
		if (DB_num_rows($SearchResult) < $_SESSION['DisplayRecordsMax']){
			$Offset=0;
		}
	} //end of if search

#Always do the stuff below if not looking for a customerid

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" id="SelectParts" method="post" enctype="multipart/form-data">';
	echo '<input type="hidden" name="DeliveryDetailsClicked" id="DeliveryDetailsClicked" value="0" />';
	echo '<input name="identifier" type="hidden" value="' . $identifier . '" />';
	echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="db-tab-content">';

	/* TAB 1: PRODUCT SEARCH */
	echo '<div id="tab-search" class="db-tab-pane active">
			<div class="db-global-search-container">
				<div class="db-search-input-group">
					<input type="text" name="Keywords" id="GlobalSearch" class="db-input" placeholder="' . __('Search products or enter stock code...') . '" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" autofocus />
					<button type="submit" name="Search" class="db-btn db-btn-primary">
						<i class="fas fa-search"></i> ' . __('Search') . '
					</button>
				</div>
				<div class="db-field-group" style="margin-top: var(--space-3); display: flex; gap: var(--space-4);">
					<select name="StockCat" class="db-input db-input-sm" style="width: auto;">
						<option value="All">' . __('All Categories') . '</option>';
	
	$CatResult = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype='F' OR stocktype='D' OR stocktype='L'");
	while ($CatRow = DB_fetch_array($CatResult)) {
		echo '<option value="' . $CatRow['categoryid'] . '" ' . (isset($_POST['StockCat']) && $_POST['StockCat'] == $CatRow['categoryid'] ? 'selected' : '') . '>' . $CatRow['categorydescription'] . '</option>';
	}
	
	echo '      </select>
			</div>
		  </div>';
	echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';

//Get The exchange rate used for GPPercent calculations on adding or amending items
	if ($_SESSION['Items'.$identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault']){
		$ExRateResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items'.$identifier]->DefaultCurrency . "'");
		if (DB_num_rows($ExRateResult)>0){
			$ExRateRow = DB_fetch_row($ExRateResult);
			$ExRate = $ExRateRow[0];
		} else {
			$ExRate =1;
		}
	} else {
		$ExRate = 1;
	}


	/*Process Quick Entry */
	/* If enter is pressed on the quick entry screen, the default button may be Recalculate */
	 if (isset($_POST['SelectingOrderItems'])
			OR isset($_POST['QuickEntry'])
			OR isset($_POST['Recalculate'])){

		 /* get the item details from the database and hold them in the cart object */

		 /*Discount can only be set later on  -- after quick entry -- so default discount to 0 in the first place */
		$Discount = 0;
		$AlreadyWarnedAboutCredit = false;
		 $i=1;
		  while ($i<=$_SESSION['QuickEntries'] AND isset($_POST['part_' . $i]) AND $_POST['part_' . $i]!='') {
			$QuickEntryCode = 'part_' . $i;
			$QuickEntryQty = 'qty_' . $i;
			$QuickEntryPOLine = 'poline_' . $i;
			$QuickEntryItemDue = 'itemdue_' . $i;
			$i++;

			if (isset($_POST[$QuickEntryCode])) {
				$NewItem = mb_strtoupper($_POST[$QuickEntryCode]);
			}
			if (isset($_POST[$QuickEntryQty])) {
				$NewItemQty = filter_number_format($_POST[$QuickEntryQty]);
			}
			  $NewItemDue = $_POST[$QuickEntryItemDue] ?? DateAdd(
				  date($_SESSION['DefaultDateFormat']),
				  'd',
				  $_SESSION['Items' . $identifier]->DeliveryDays
			  );
			  $NewPOLine = $_POST[$QuickEntryPOLine] ?? 0;

			if (!isset($NewItem)){
				unset($NewItem);
				break;	/* break out of the loop if nothing in the quick entry fields*/
			}

			if (!Is_Date($NewItemDue)) {
				prnMsg(__('An invalid date entry was made for ') . ' ' . $NewItem . ' ' . __('The date entry') . ' ' . $NewItemDue . ' ' . __('must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
				//Attempt to default the due date to something sensible?
				$NewItemDue = DateAdd (date($_SESSION['DefaultDateFormat']),'d', $_SESSION['Items'.$identifier]->DeliveryDays);
			}
			/*Now figure out if the item is a kit set - the field MBFlag='K'*/
			$SQL = "SELECT stockmaster.mbflag
					FROM stockmaster
					WHERE stockmaster.stockid='". $NewItem ."'";

			$ErrMsg = __('Could not determine if the part being ordered was a kitset or not because');
			$KitResult = DB_query($SQL, $ErrMsg);

			if (DB_num_rows($KitResult)==0){
				prnMsg( __('The item code') . ' ' . $NewItem . ' ' . __('could not be retrieved from the database and has not been added to the order'),'warn');
			} elseif ($MyRow=DB_fetch_array($KitResult)){
				if ($MyRow['mbflag']=='K'){	/*It is a kit set item */
					$SQL = "SELECT bom.component,
							bom.quantity
							FROM bom
							WHERE bom.parent='" . $NewItem . "'
                            AND bom.effectiveafter <= CURRENT_DATE
                            AND bom.effectiveto > CURRENT_DATE";

					$ErrMsg =  __('Could not retrieve kitset components from the database because') . ' ';
					$KitResult = DB_query($SQL, $ErrMsg);

					$ParentQty = $NewItemQty;
					while ($KitParts = DB_fetch_array($KitResult)){
						$NewItem = $KitParts['component'];
						$NewItemQty = $KitParts['quantity'] * $ParentQty;
						$NewPOLine = 0;
						include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
					}

				} elseif ($MyRow['mbflag']=='G'){
					prnMsg(__('Phantom assemblies cannot be sold, these items exist only as bills of materials used in other manufactured items. The following item has not been added to the order:') . ' ' . $NewItem, 'warn');
				} else { /*Its not a kit set item*/
					include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
				}
			}
		 }
		 unset($NewItem);
	 } /* end of if quick entry */

	if (isset($_POST['AssetDisposalEntered'])){ //its an asset being disposed of
		if ($_POST['AssetToDisposeOf'] == 'NoAssetSelected'){ //don't do anything unless an asset is disposed of
			prnMsg(__('No asset was selected to dispose of. No assets have been added to this customer order'),'warn');
		} else { //need to add the asset to the order
			/*First need to create a stock ID to hold the asset and record the sale - as only stock items can be sold
			 * 		and before that we need to add a disposal stock category - if not already created
			 * 		first off get the details about the asset being disposed of */
			 $AssetDetailsResult = DB_query("SELECT  fixedassets.description,
													fixedassets.longdescription,
													fixedassets.barcode,
													fixedassetcategories.costact,
													fixedassets.cost-fixedassets.accumdepn AS nbv
											FROM fixedassetcategories INNER JOIN fixedassets
											ON fixedassetcategories.categoryid=fixedassets.assetcategoryid
											WHERE fixedassets.assetid='" . $_POST['AssetToDisposeOf'] . "'");
			$AssetRow = DB_fetch_array($AssetDetailsResult);

			/* Check that the stock category for disposal "ASSETS" is defined already */
			$AssetCategoryResult = DB_query("SELECT categoryid FROM stockcategory WHERE categoryid='ASSETS'");
			if (DB_num_rows($AssetCategoryResult)==0){
				/*Although asset GL posting will come from the asset category - we should set the GL codes to something sensible
				 * based on the category of the asset under review at the moment - this may well change for any other assets sold subsequentely */

				/*OK now we can insert the stock category for this asset */
				$InsertAssetStockCatResult = DB_query("INSERT INTO stockcategory ( categoryid,
																				categorydescription,
																				stockact)
														VALUES ('ASSETS',
																'" . __('Asset Disposals') . "',
																'" . $AssetRow['costact'] . "')");
			}

			/*First check to see that it doesn't exist already assets are of the format "ASSET-" . $AssetID
			 */
			 $TestAssetExistsAlreadyResult = DB_query("SELECT stockid
														FROM stockmaster
														WHERE stockid ='ASSET-" . $_POST['AssetToDisposeOf']  . "'");
			 $j=0;
			while (DB_num_rows($TestAssetExistsAlreadyResult)==1) { //then it exists already ... bum
				$j++;
				$TestAssetExistsAlreadyResult = DB_query("SELECT stockid
														FROM stockmaster
														WHERE stockid ='ASSET-" . $_POST['AssetToDisposeOf']  . '-' . $j . "'");
			}
			if ($j>0){
				$AssetStockID = 'ASSET-' . $_POST['AssetToDisposeOf']  . '-' . $j;
			} else {
				$AssetStockID = 'ASSET-' . $_POST['AssetToDisposeOf'];
			}
			if ($AssetRow['nbv'] == 0){
				/* stock must have a cost to be invoiced if the flag is set so set to the base currency tolerance */
				$NBV = CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency);
			} else {
				$NBV = $AssetRow['nbv'];
			}
			/*OK now we can insert the item for this asset */
			$InsertAssetAsStockItemResult = DB_query("INSERT INTO stockmaster ( stockid,
																				description,
																				longdescription,
																				categoryid,
																				mbflag,
																				controlled,
																				serialised,
																				taxcatid,
																				materialcost)
										VALUES ('" . $AssetStockID . "',
												'" . DB_escape_string($AssetRow['description']) . "',
												'" . DB_escape_string($AssetRow['longdescription']) . "',
												'ASSETS',
												'D',
												'0',
												'0',
												'" . $_SESSION['DefaultTaxCategory'] . "',
												'". $NBV . "')");
			/*not forgetting the location records too */
			$InsertStkLocRecsResult = DB_query("INSERT INTO locstock (loccode,
																	stockid)
												SELECT loccode, '" . $AssetStockID . "'
												FROM locations");
			/*Now the asset has been added to the stock master we can add it to the sales order */
			$NewItemDue = date($_SESSION['DefaultDateFormat']);
			$NewPOLine = $_POST['POLine'] ?? 0;
			$NewItem = $AssetStockID;
			include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
		} //end if adding a fixed asset to the order
	} //end if the fixed asset selection box was set

	 /*Now do non-quick entry delete/edits/adds */

	if ((isset($_SESSION['Items'.$identifier])) OR isset($NewItem)){

		if (isset($_GET['Delete'])){
			//page called attempting to delete a line - GET['Delete'] = the line number to delete
			$QuantityAlreadyDelivered = $_SESSION['Items'.$identifier]->Some_Already_Delivered($_GET['Delete']);
			if ($QuantityAlreadyDelivered == 0){
				$_SESSION['Items'.$identifier]->remove_from_cart($_GET['Delete'], 'Yes', $identifier);  /*Do update DB */
			} else {
				$_SESSION['Items'.$identifier]->LineItems[$_GET['Delete']]->Quantity = $QuantityAlreadyDelivered;
			}
		}

		$AlreadyWarnedAboutCredit = false;

		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
			if (isset($_POST['ItemDue_' . $OrderLine->LineNumber])){
				// Pointless conversion removed
			}
			else {
				$_POST['ItemDue_' . $OrderLine->LineNumber] = DateAdd (date($_SESSION['DefaultDateFormat']),'d', $_SESSION['Items'.$identifier]->DeliveryDays);
			}

			if (isset($_POST['Quantity_' . $OrderLine->LineNumber])){

				$Quantity = round(filter_number_format($_POST['Quantity_' . $OrderLine->LineNumber]),$OrderLine->DecimalPlaces);

				if (ABS($OrderLine->Price - filter_number_format($_POST['Price_' . $OrderLine->LineNumber])) > CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)){
					/*There is a new price being input for the line item */
					$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
					if (isset($_POST['Discount_' . $OrderLine->LineNumber]) AND is_numeric(filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]))) {
							if ($_POST['Discount_' . $OrderLine->LineNumber] < 100) {//to avoid divided by zero error
								$_POST['GPPercent_' . $OrderLine->LineNumber] = (($Price*(1-(filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])/100))) - $OrderLine->StandardCost*$ExRate)/($Price *(1-filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])/100)/100);
							} else {
								$_POST['GPPercent_' . $OrderLine->LineNumber] = 0;
							}
					} else {
							$_POST['GPPercent_' . $OrderLine->LineNumber] = ($Price - $OrderLine->StandardCost*$ExRate)*100/$Price;
					}


				} elseif (isset($_POST['GPPercent_'.$OrderLine->LineNumber]) AND ABS($OrderLine->GPPercent - filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber])) >= CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
					/* A GP % has been input so need to do a recalculation of the price at this new GP Percentage */

					prnMsg(__('Recalculated the price from the GP % entered - the GP % was') . ' ' . $OrderLine->GPPercent . '  the new GP % is ' . filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]),'info');

					$Price = ($OrderLine->StandardCost*$ExRate)/(1 -((filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]) + filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]))/100));
				} else {
					$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
					if (isset($_POST['Discount_' . $OrderLine->LineNumber]) AND is_numeric(filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])) AND $Price != 0) {
							if ($_POST['Discount_' . $OrderLine->LineNumber] < 100) {//to avoid divided by zero error
								$_POST['GPPercent_' . $OrderLine->LineNumber] = (($Price*(1-(filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])/100))) - $OrderLine->StandardCost*$ExRate)/($Price *(1-filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])/100)/100);
							} elseif ($Price != 0) {
								$_POST['GPPercent_' . $OrderLine->LineNumber] = 0;
							}
					} elseif ($Price != 0) {
							$_POST['GPPercent_' . $OrderLine->LineNumber] = ($Price - $OrderLine->StandardCost*$ExRate)*100/$Price;
					}
				}
				$DiscountPercentage = isset($_POST['Discount_' . $OrderLine->LineNumber])?filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]):0;
				if ($_SESSION['AllowOrderLineItemNarrative'] == 1) {
					$Narrative = $_POST['Narrative_' . $OrderLine->LineNumber];
				} else {
					$Narrative = '';
				}

				if (!isset($OrderLine->DiscountPercent)) {
					$OrderLine->DiscountPercent = 0;
				}

				if (!Is_Date($_POST['ItemDue_' . $OrderLine->LineNumber])) {
					prnMsg(__('An invalid date entry was made for ') . ' ' . $NewItem . ' ' . __('The date entry') . ' ' . $ItemDue . ' ' . __('must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
					//Attempt to default the due date to something sensible?
					$_POST['ItemDue_' . $OrderLine->LineNumber] = DateAdd (date($_SESSION['DefaultDateFormat']),'d', $_SESSION['Items'.$identifier]->DeliveryDays);
				}
				if ($Quantity<0 OR $Price <0 OR $DiscountPercentage >100 OR $DiscountPercentage <0){
					prnMsg(__('The item could not be updated because you are attempting to set the quantity ordered to less than 0 or the price less than 0 or the discount more than 100% or less than 0%'),'warn');
				} elseif ($_SESSION['Items'.$identifier]->Some_Already_Delivered($OrderLine->LineNumber)!=0 AND $_SESSION['Items'.$identifier]->LineItems[$OrderLine->LineNumber]->Price != $Price) {
					prnMsg(__('The item you attempting to modify the price for has already had some quantity invoiced at the old price the items unit price cannot be modified retrospectively'),'warn');
				} elseif ($_SESSION['Items'.$identifier]->Some_Already_Delivered($OrderLine->LineNumber)!=0 AND $_SESSION['Items'.$identifier]->LineItems[$OrderLine->LineNumber]->DiscountPercent != ($DiscountPercentage/100)) {

					prnMsg(__('The item you attempting to modify has had some quantity invoiced at the old discount percent the items discount cannot be modified retrospectively'),'warn');

				} elseif ($_SESSION['Items'.$identifier]->LineItems[$OrderLine->LineNumber]->QtyInv > $Quantity){
					prnMsg( __('You are attempting to make the quantity ordered a quantity less than has already been invoiced') . '. ' . __('The quantity delivered and invoiced cannot be modified retrospectively'),'warn');
				} elseif ($OrderLine->Quantity !=$Quantity
							OR $OrderLine->Price != $Price
							OR ABS($OrderLine->DiscountPercent - $DiscountPercentage/100) > CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)
							OR $OrderLine->Narrative != $Narrative
							OR $OrderLine->ItemDue != $_POST['ItemDue_' . $OrderLine->LineNumber]
							OR $OrderLine->POLine != $_POST['POLine_' . $OrderLine->LineNumber]) {

					$WithinCreditLimit = true;

					if ($_SESSION['CheckCreditLimits'] > 0 AND $AlreadyWarnedAboutCredit==false){
						/*Check credit limits is 1 for warn breach their credit limit and 2 for prohibit sales */
						$DifferenceInOrderValue = ($Quantity*$Price*(1-$DiscountPercentage/100)) - ($OrderLine->Quantity*$OrderLine->Price*(1-$OrderLine->DiscountPercent));
						$_SESSION['Items'.$identifier]->CreditAvailable -= $DifferenceInOrderValue;

						if ($_SESSION['CheckCreditLimits']==1 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0){
							prnMsg(__('The customer account will breach their credit limit'),'warn');
							$AlreadyWarnedAboutCredit = true;
						} elseif ($_SESSION['CheckCreditLimits']==2 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0){
							prnMsg(__('This change would put the customer over their credit limit and is prohibited'),'warn');
							$WithinCreditLimit = false;
							$_SESSION['Items'.$identifier]->CreditAvailable += $DifferenceInOrderValue;
							$AlreadyWarnedAboutCredit = true;
						}
					}
					/* The database data will be updated at this step, it will make big mistake if users do not know this and change the quantity to zero, unfortuately, the appearance shows that this change not allowed but the sales order details' quantity has been changed to zero in database. Must to filter this out! A zero quantity order line means nothing */
					if ($WithinCreditLimit AND $Quantity >0){
						$_SESSION['Items'.$identifier]->update_cart_item($OrderLine->LineNumber,
																		$Quantity,
																		$Price,
																		($DiscountPercentage/100),
																		$Narrative,
																		'Yes', /*Update DB */
																		$_POST['ItemDue_' . $OrderLine->LineNumber],
																		$_POST['POLine_' . $OrderLine->LineNumber],
																		filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]),
																		$identifier);
					} //within credit limit so make changes
				} //there are changes to the order line to process
			} //page not called from itself - POST variables not set
		} // Loop around all items on the order


		/* Now Run through each line of the order again to work out the appropriate discount from the discount matrix */
		$DiscCatsDone = array();
		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {

			if ($OrderLine->DiscCat !='' AND ! in_array($OrderLine->DiscCat,$DiscCatsDone)){
				$DiscCatsDone[]=$OrderLine->DiscCat;
				$QuantityOfDiscCat = 0;

				foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine_2) {
					/* add up total quantity of all lines of this DiscCat */
					if ($OrderLine_2->DiscCat==$OrderLine->DiscCat){
						$QuantityOfDiscCat += $OrderLine_2->Quantity;
					}
				}
				$Result = DB_query("SELECT MAX(discountrate) AS discount
									FROM discountmatrix
									WHERE salestype='" .  $_SESSION['Items'.$identifier]->DefaultSalesType . "'
									AND discountcategory ='" . $OrderLine->DiscCat . "'
									AND quantitybreak <= '" . $QuantityOfDiscCat ."'");
				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0]==NULL){
					$DiscountMatrixRate = 0;
				} else {
					$DiscountMatrixRate = $MyRow[0];
				}
				if ($DiscountMatrixRate!=0){ /* need to update the lines affected */
					foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine_2) {
						if ($OrderLine_2->DiscCat==$OrderLine->DiscCat){
							$_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->DiscountPercent = $DiscountMatrixRate;
							$_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->GPPercent = (($_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->Price*(1-$DiscountMatrixRate)) - $_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->StandardCost*$ExRate)/($_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->Price *(1-$DiscountMatrixRate)/100);
						}
					}
				}
			}
		} /* end of discount matrix lookup code */
	} // the order session is started or there is a new item being added
	if (isset($_POST['DeliveryDetails']) || (isset($_POST['DeliveryDetailsClicked']) && $_POST['DeliveryDetailsClicked'] == '1')){
		$URL = $RootPath . '/DeliveryDetails.php?identifier='.$identifier;
		if (!headers_sent()) {
			header('Location: ' . $URL);
			exit;
		}
		echo '<meta http-equiv="refresh" content="0; url=' . $URL . '">';
		prnMsg(__('You should automatically be forwarded to the entry of the delivery details page') . '. ' . __('if this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' .
		   '<a href="' . $URL . '">' . __('click here') . '</a> ' . __('to continue'), 'info');
	   	include(__DIR__ . '/includes/footer.php');
		exit();
	}


	if (isset($NewItem)){
/* get the item details from the database and hold them in the cart object make the quantity 1 by default then add it to the cart */
/*Now figure out if the item is a kit set - the field MBFlag='K'*/
		$SQL = "SELECT stockmaster.mbflag
		   		FROM stockmaster
				WHERE stockmaster.stockid='". $NewItem ."'";

		$ErrMsg =  __('Could not determine if the part being ordered was a kitset or not because');

		$KitResult = DB_query($SQL, $ErrMsg);

		$NewItemQty = 1; /*By Default */
		$Discount = 0; /*By default - can change later or discount category override */

		if ($MyRow=DB_fetch_array($KitResult)){
		   	if ($MyRow['mbflag']=='K'){	/*It is a kit set item */
				$SQL = "SELECT bom.component,
							bom.quantity
						FROM bom
						WHERE bom.parent='" . $NewItem . "'
                        AND bom.effectiveafter <= CURRENT_DATE
                        AND bom.effectiveto > CURRENT_DATE";

				$ErrMsg = __('Could not retrieve kitset components from the database because');
				$KitResult = DB_query($SQL, $ErrMsg);

				$ParentQty = $NewItemQty;
				while ($KitParts = DB_fetch_array($KitResult)){
					$NewItem = $KitParts['component'];
					$NewItemQty = $KitParts['quantity'] * $ParentQty;
					$NewPOLine = 0;
					$NewItemDue = date($_SESSION['DefaultDateFormat']);
					include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
				}

			} else { /*Its not a kit set item*/
				$NewItemDue = date($_SESSION['DefaultDateFormat']);
				$NewPOLine = 0;

				include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
			}

		} /* end of if its a new item */

	} /*end of if its a new item */

	if (isset($NewItemArray) AND isset($_POST['SelectingOrderItems'])){
/* get the item details from the database and hold them in the cart object make the quantity 1 by default then add it to the cart */
/*Now figure out if the item is a kit set - the field MBFlag='K'*/
		$AlreadyWarnedAboutCredit = false;
		foreach($NewItemArray as $NewItem => $NewItemQty) {
			if ($NewItemQty > 0)	{
				$SQL = "SELECT stockmaster.mbflag
						FROM stockmaster
						WHERE stockmaster.stockid='". $NewItem ."'";

				$ErrMsg =  __('Could not determine if the part being ordered was a kitset or not because');

				$KitResult = DB_query($SQL, $ErrMsg);

				//$NewItemQty = 1; /*By Default */
				$Discount = 0; /*By default - can change later or discount category override */

				if ($MyRow=DB_fetch_array($KitResult)){
					if ($MyRow['mbflag']=='K'){	/*It is a kit set item */
						$SQL = "SELECT bom.component,
										bom.quantity
								FROM bom
								WHERE bom.parent='" . $NewItem . "'
                                AND bom.effectiveafter <= CURRENT_DATE
                                AND bom.effectiveto > CURRENT_DATE";

						$ErrMsg = __('Could not retrieve kitset components from the database because');
						$KitResult = DB_query($SQL, $ErrMsg);

						$ParentQty = $NewItemQty;
						while ($KitParts = DB_fetch_array($KitResult)){
							$NewItem = $KitParts['component'];
							$NewItemQty = $KitParts['quantity'] * $ParentQty;
							$NewItemDue = date($_SESSION['DefaultDateFormat']);
							$NewPOLine = 0;
							include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
						}

					} else { /*Its not a kit set item*/
						$NewItemDue = date($_SESSION['DefaultDateFormat']);
						$NewPOLine = 0;
						include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
					}
				} /* end of if its a new item */
			} /*end of if its a new item */
		}/* loop through NewItem array */
	} /* if the NewItem_array is set */

	/* Run through each line of the order and work out the appropriate discount from the discount matrix */
	$DiscCatsDone = array();
	$Counter =0;
	foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {

		if ($OrderLine->DiscCat !="" AND ! in_array($OrderLine->DiscCat,$DiscCatsDone)){
			$DiscCatsDone[$Counter]=$OrderLine->DiscCat;
			$QuantityOfDiscCat =0;

			foreach ($_SESSION['Items'.$identifier]->LineItems as $StkItems_2) {
				/* add up total quantity of all lines of this DiscCat */
				if ($StkItems_2->DiscCat==$OrderLine->DiscCat){
					$QuantityOfDiscCat += $StkItems_2->Quantity;
				}
			}
			$Result = DB_query("SELECT MAX(discountrate) AS discount
								FROM discountmatrix
								WHERE salestype='" .  $_SESSION['Items'.$identifier]->DefaultSalesType . "'
								AND discountcategory ='" . $OrderLine->DiscCat . "'
								AND quantitybreak <= '" . $QuantityOfDiscCat . "'");
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] == NULL){
				$DiscountMatrixRate = 0;
			} else {
				$DiscountMatrixRate = $MyRow[0];
			}
			if ($DiscountMatrixRate != 0) {
				foreach ($_SESSION['Items'.$identifier]->LineItems as $StkItems_2) {
					if ($StkItems_2->DiscCat==$OrderLine->DiscCat){
						$_SESSION['Items'.$identifier]->LineItems[$StkItems_2->LineNumber]->DiscountPercent = $DiscountMatrixRate;
					}
				}
			}
		}
	} /* end of discount matrix lookup code */

	if (isset($SearchResult)) {
		echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';
		$NumRows = DB_num_rows($SearchResult);
		if ($NumRows > 0) {
			echo '<div class="db-card-header" style="background: transparent; border: none; padding-left: 0; margin-top: var(--space-8);">
					<h3 class="db-card-title"><i class="fas fa-search db-icon-green"></i> ' . __('Search Results') . '</h3>
				  </div>';
			
			echo '<div class="db-product-grid">';
			$j = 0;
			DB_data_seek($SearchResult, 0); // Reset result pointer
			while ($MyRow = DB_fetch_array($SearchResult)) {
				$QOH = GetQuantityOnHand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);
				$DemandQty = GetDemand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);
				$OnOrder = GetQuantityOnOrder($MyRow['stockid'], 'ALL');
				$Available = $QOH - $DemandQty + $OnOrder;
				
				$Price = GetPrice($MyRow['stockid'], $_SESSION['Items' . $identifier]->DebtorNo, $_SESSION['Items' . $identifier]->Branch);

				echo '<div class="db-product-card">
						<div class="db-product-image" title="' . $MyRow['longdescription'] . '">
							<i class="fas fa-box"></i>
							' . ($QOH <= 0 ? '<span class="db-badge danger">' . __('Out of Stock') . '</span>' : '<span class="db-badge success" style="background: var(--primary); color: white; border:none;">' . __('In Stock') . '</span>') . '
						</div>
						<div class="db-product-content">
							<span class="db-product-id">' . $MyRow['stockid'] . '</span>
							<h4 class="db-product-name">' . $MyRow['description'] . '</h4>
							' . ( (!empty($MyRow['cust_part']) AND $MyRow['cust_part'] != '-') ? '<div class="db-product-id" style="color: var(--primary);">' . __('Cust Part: ') . $MyRow['cust_part'] . '</div>' : '' ) . '
							<div class="db-product-price">
								' . locale_number_format($Price, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . ' <small>' . $_SESSION['Items' . $identifier]->DefaultCurrency . '</small>
							</div>
							<div class="db-product-meta">
								<span><i class="fas fa-layer-group"></i> ' . locale_number_format($QOH, $MyRow['decimalplaces']) . ' ' . $MyRow['units'] . '</span>
								<span><i class="fas fa-check-circle"></i> ' . __('Avail: ') . locale_number_format($Available, $MyRow['decimalplaces']) . '</span>
							</div>
						</div>
						<div class="db-product-card-footer">
							<input class="db-input db-input-sm number" type="text" name="OrderQty' . $j . '" value="0" placeholder="Qty" />
							<input name="StockID' . $j . '" type="hidden" value="' . $MyRow['stockid'] . '" />
							<button type="submit" name="SelectingOrderItems" class="db-btn db-btn-primary db-btn-sm" style="padding: 0 var(--space-4); height: 32px;">
								<i class="fas fa-plus"></i> ' . __('Add') . '
							</button>
						</div>
					  </div>';
				$j++;
			}
			echo '</div>'; // .db-product-grid
		}
	}
	echo '</div>'; // End tab-search

	/* TAB 2: IMPORT CSV */
	echo '<div id="tab-csv" class="db-tab-pane">
			<div class="db-card">
				<div class="db-card-body centre" style="padding: var(--space-12);">
					<i class="fas fa-file-csv db-icon-blue" style="font-size: 3rem; margin-bottom: var(--space-4);"></i>
					<h3>' . __('Import Items from CSV') . '</h3>
					<p class="db-muted" style="margin-bottom: var(--space-6);">' . __('Upload a CSV file with ItemCode, Quantity in each row.') . '</p>
					
					<div class="db-field-group" style="max-width: 400px; margin: 0 auto;">
						<input type="file" name="CSVFile" id="CSVFile" class="db-input" accept=".csv" />
						<button type="submit" name="UploadFile" class="db-btn db-btn-primary" style="margin-top: var(--space-4); width: 100%;">
							<i class="fas fa-upload"></i> ' . __('Upload & Process CSV') . '
						</button>
					</div>
				</div>
			</div>
		  </div>';

	echo '</div>'; // End db-tab-content
	
	/* Close Main Content and Open Sidebar for the Cart */
	echo '</div><!-- .db-pos-main -->
		  <div class="db-pos-sidebar">';

	if (count($_SESSION['Items'.$identifier]->LineItems)>0){
		echo '<div class="db-sidebar-cart">
				<div class="db-sidebar-cart-header">
					<h3 class="db-card-title"><i class="fas fa-shopping-cart"></i> ' . __('Your Order') . '</h3>
					<span class="db-badge">' . $_SESSION['Items'.$identifier]->ItemsOrdered . ' ' . __('Items') . '</span>
				</div>
				<div class="db-sidebar-cart-body">';
		$_SESSION['Items'.$identifier]->total = 0;
		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
			$LineTotal = $OrderLine->Quantity * $OrderLine->Price * (1 - $OrderLine->DiscountPercent);
			$DisplayLineTotal = locale_number_format($LineTotal,$_SESSION['Items'.$identifier]->CurrDecimalPlaces);
			
			if ($_SESSION['Items'.$identifier]->Some_Already_Delivered($OrderLine->LineNumber)){
				$RemTxt = '<i class="fas fa-eraser"></i>';
			} else {
				$RemTxt = '<i class="fas fa-times"></i>';
			}

			echo '<div class="db-sidebar-item">
					<div class="db-sidebar-item-row">
						<div class="db-sidebar-item-name">' . $OrderLine->ItemDescription . '</div>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?identifier=' . $identifier . '&amp;Delete=' . $OrderLine->LineNumber . '" class="db-btn-icon db-btn-sm db-btn-danger" style="width:20px; height:20px; font-size:10px;">' . $RemTxt . '</a>
					</div>
					<div class="db-sidebar-item-meta">' . $OrderLine->StockID . '</div>
					<div class="db-sidebar-item-actions">
						<div class="db-sidebar-item-qty">
							<input type="text" name="Quantity_' . $OrderLine->LineNumber . '" class="db-input db-input-sm" value="' . locale_number_format($OrderLine->Quantity,$OrderLine->DecimalPlaces) . '" style="width:50px;" />
							<span class="db-muted">' . $OrderLine->Units . '</span>
						</div>
						<div class="db-sidebar-item-price">
							<b>' . $DisplayLineTotal . '</b>
						</div>
					</div>
				  </div>';

			$_SESSION['Items'.$identifier]->total += $LineTotal;
		}

		echo '</div><!-- .db-sidebar-cart-body -->
			  <div class="db-sidebar-cart-footer">
			  	<div class="db-sidebar-total-row">
					<span>' . __('Subtotal') . '</span>
					<span>' . locale_number_format($_SESSION['Items'.$identifier]->total,$_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
				</div>
				<div class="db-sidebar-total-main">
					<span>' . __('Total') . '</span>
					<span>' . locale_number_format($_SESSION['Items'.$identifier]->total,$_SESSION['Items'.$identifier]->CurrDecimalPlaces) . ' ' . $_SESSION['Items'.$identifier]->DefaultCurrency . '</span>
				</div>
				<div class="db-actions" style="margin-top: var(--space-6); display: flex; flex-direction: column; gap: 12px;">
					<input type="submit" name="Recalculate" class="db-btn db-btn-secondary" style="width: 100%;" value="' . __('Refresh Cart Quantities') . '" />
					<input type="submit" name="DeliveryDetails" class="db-btn db-btn-primary" style="width: 100%;" value="' . __('Proceed to Final Review') . '" />
				</div>
			  </div>
			</div>'; // end db-sidebar-cart

			// Sticky footer only for mobile/small screens or as a backup
			echo '<div class="db-sticky-footer">
					<div class="db-sticky-total">
						<span class="db-label">' . __('Order Total') . '</span>
						<span class="db-value">' . locale_number_format($_SESSION['Items'.$identifier]->total,$_SESSION['Items'.$identifier]->CurrDecimalPlaces) . ' ' . $_SESSION['Items'.$identifier]->DefaultCurrency . '</span>
					</div>
					<div class="db-actions">
						<input type="submit" form="SelectParts" name="DeliveryDetails" class="db-btn db-btn-primary" value="' . __('Confirm Order') . '" />
					</div>
				</div>';
	}
	
	echo '</div><!-- .db-pos-sidebar -->
		  </div><!-- .db-pos-wrapper -->
		</form>';

	if ($_SESSION['Items'.$identifier]->ItemsOrdered >=1){
		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" name="deleteform">
			<div class="db-card" style="margin-top: var(--space-6); border-color: var(--danger-soft);">
				<div class="db-card-body centre">
					<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />
					<input name="CancelOrder" type="submit" class="db-btn db-btn-secondary" style="color: var(--danger);" value="' . __('Cancel Whole Order') . '" onclick="return confirm(\'' . __('Are you sure you wish to cancel this entire order?') . '\');" />
				</div>
			</div>
			</form>';
	}
}#end of else not selecting a customer

echo '</div><!-- .db-page -->';

include(__DIR__ . '/includes/footer.php');

function GetCustBranchDetails($identifier) {
		$SQL = "SELECT custbranch.brname,
						custbranch.branchcode,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.phoneno,
						custbranch.email,
						custbranch.defaultlocation,
						custbranch.defaultshipvia,
						custbranch.deliverblind,
						custbranch.specialinstructions,
						custbranch.estdeliverydays,
						locations.locationname,
						custbranch.salesman
					FROM custbranch
					INNER JOIN locations
					ON custbranch.defaultlocation=locations.loccode
					WHERE custbranch.branchcode='" . $_SESSION['Items'.$identifier]->Branch . "'
					AND custbranch.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

		$ErrMsg = __('The customer branch record of the customer selected') . ': ' . $_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('cannot be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		return $Result;
}
