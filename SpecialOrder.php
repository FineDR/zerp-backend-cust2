<?php

// Allows for a sales order to be created and an indent order to be created on a supplier for a one off item that may never be purchased again. A dummy part is created based on the description and cost details given.

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineSpecialOrderClass.php');

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$ViewTopic = 'SalesOrders';/* ?????????? */
$BookMark = 'SpecialOrder';
$ExtraHeadContent = '<link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/special-orders.css">';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['ReqDelDate'])){$_POST['ReqDelDate'] = ConvertSQLDate($_POST['ReqDelDate']);}

if (empty($_GET['identifier'])) {
	$identifier=date('U');
} else {
	$identifier=$_GET['identifier'];
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-magic" style="color: var(--warning-color);"></i> ' . $Title . '
			</div>
			<div class="db-page-actions">
				<a href="SpecialOrder.php?identifier=' . $identifier . '&NewSpecial=yes" class="db-btn db-btn-outline"><i class="fas fa-plus"></i> ' . __('New Special') . '</a>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8' ) . '?identifier=' . urlencode($identifier) . '" method="post" id="SpecialOrderForm">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_GET['NewSpecial']) and $_GET['NewSpecial']=='yes'){
	unset($_SESSION['SPL'.$identifier]);
}

if (!isset($_SESSION['SupplierID'])){
	echo '<div class="db-page-content">
            <div class="db-card" style="max-width: 600px; margin: 40px auto;">
                <div class="db-card-body text-center p-8">
                    <i class="fas fa-truck-loading fa-3x mb-4" style="color: var(--primary);"></i>
                    <h3>' . __('Supplier Required') . '</h3>
                    <p class="mb-6">' . __('To set up a special order, you must first select a supplier.') . '</p>
                    <a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-primary">' . __('Select Supplier Now') . '</a>
                </div>
            </div>
          </div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (!isset($_SESSION['CustomerID']) or $_SESSION['CustomerID']==''){
	echo '<div class="db-page-content">
            <div class="db-card" style="max-width: 600px; margin: 40px auto;">
                <div class="db-card-body text-center p-8">
                    <i class="fas fa-user-tag fa-3x mb-4" style="color: var(--success);"></i>
                    <h3>' . __('Customer Required') . '</h3>
                    <p class="mb-6">' . __('To set up a special order, you must first select a customer.') . '</p>
                    <a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-primary">' . __('Select Customer Now') . '</a>
                </div>
            </div>
          </div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['Cancel'])){
	unset($_SESSION['SPL'.$identifier]);
}


if (!isset($_SESSION['SPL'.$identifier])){
	/* It must be a new special order being created $_SESSION['SPL'.$identifier] would be set up from the order modification code above if a modification to an existing order.  */

	$_SESSION['SPL'.$identifier] = new SpecialOrder;

}

/*if not already done populate the SPL object with supplier data */
if (!isset($_SESSION['SPL'.$identifier]->SupplierID)){
	$SQL = "SELECT suppliers.suppname,
					suppliers.currcode,
					currencies.rate,
					currencies.decimalplaces
				FROM suppliers INNER JOIN currencies
					ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_SESSION['SupplierID'] . "'";
	$ErrMsg = __('The supplier record of the supplier selected') . ": " . $_SESSION['SupplierID']  . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($Result);
	$_SESSION['SPL'.$identifier]->SupplierID = $_SESSION['SupplierID'];
	$_SESSION['SPL'.$identifier]->SupplierName = $MyRow['suppname'];
	$_SESSION['SPL'.$identifier]->SuppCurrCode = $MyRow['currcode'];
	$_SESSION['SPL'.$identifier]->SuppCurrExRate = $MyRow['rate'];
	$_SESSION['SPL'.$identifier]->SuppCurrDecimalPlaces = $MyRow['decimalplaces'];
}
if (!isset($_SESSION['SPL'.$identifier]->CustomerID)){
	// Now check to ensure this account is not on hold */
	$SQL = "SELECT debtorsmaster.name,
					holdreasons.dissallowinvoices,
					debtorsmaster.currcode,
					currencies.rate,
					currencies.decimalplaces
			FROM debtorsmaster INNER JOIN holdreasons
			ON debtorsmaster.holdreason=holdreasons.reasoncode
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtorsmaster.debtorno = '" . $_SESSION['CustomerID'] . "'";

	$ErrMsg = __('The customer record for') . ' : ' . $_SESSION['CustomerID']  . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($Result);
	if ($MyRow['dissallowinvoices'] != 1){
		if ($MyRow['dissallowinvoices']==2){
			prnMsg(__('The') . ' ' . $MyRow['name'] . ' ' . __('account is currently flagged as an account that needs to be watched. Please contact the credit control personnel to discuss'),'warn');
		}
	}
	$_SESSION['SPL'.$identifier]->CustomerID = $_SESSION['CustomerID'];
	$_SESSION['SPL'.$identifier]->CustomerName = $MyRow['name'];
	$_SESSION['SPL'.$identifier]->CustCurrCode = $MyRow['currcode'];
	$_SESSION['SPL'.$identifier]->CustCurrExRate = $MyRow['rate'];
	$_SESSION['SPL'.$identifier]->CustCurrDecimalPlaces = $MyRow['decimalplaces'];
}

if (isset($_POST['SelectBranch'])){

	$SQL = "SELECT brname
			FROM custbranch
			WHERE debtorno='" . $_SESSION['SPL'.$identifier]->CustomerID . "'
			AND branchcode='" . $_POST['SelectBranch'] . "'";
	$BranchResult = DB_query($SQL);
	$MyRow=DB_fetch_array($BranchResult);
	$_SESSION['SPL'.$identifier]->BranchCode = $_POST['SelectBranch'];
	$_SESSION['SPL'.$identifier]->BranchName = $MyRow['brname'];
}
echo '<div class="centre">';
echo '</h2></div>';
/*if the branch details and delivery details have not been entered then select them from the list */
if (!isset($_SESSION['SPL'.$identifier]->BranchCode)){

	$SQL = "SELECT branchcode,
					brname
			FROM custbranch
			WHERE debtorno='" . $_SESSION['CustomerID'] . "'";
	$BranchResult = DB_query($SQL);

	if (DB_num_rows($BranchResult)>1) {
		echo '<div class="db-page-content">
                <div class="db-card" style="max-width: 700px; margin: 0 auto;">
                    <div class="db-card-header">
                        <div class="db-card-title"><i class="fas fa-map-marker-alt"></i> ' . __('Select Delivery Branch') . '</div>
                    </div>
                    <div class="db-card-body">
                        <div class="db-grid-2" style="gap: 15px;">';
		while ($MyRow=DB_fetch_array($BranchResult)) {
			echo '<button type="submit" name="SelectBranch" value="' . $MyRow['branchcode'] . '" class="db-btn db-btn-outline" style="justify-content: flex-start; padding: 15px;">
                    <div class="text-left">
                        <div style="font-weight: 700;">' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8') . '</div>
                        <div style="font-size: 0.75rem; opacity: 0.7;">' . $MyRow['branchcode'] . '</div>
                    </div>
                  </button>';
		}
		echo '          </div>
                    </div>
                </div>
              </div>';
		echo '  </form>
              </div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	} elseif (DB_num_rows($BranchResult)==1) {
        $MyRow = DB_fetch_array($BranchResult);
        $_SESSION['SPL'.$identifier]->BranchCode = $MyRow['branchcode'];
        $_SESSION['SPL'.$identifier]->BranchName = $MyRow['brname'];
    } else {
		prnMsg( __('There are no branches defined for the customer selected'),'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}


if (isset($_GET['Delete'])){  /*User hit the delete link on a line */
	$_SESSION['SPL'.$identifier]->remove_from_order($_GET['Delete']);
}


if (isset($_POST['EnterLine'])){

/*Add the header info to the session variable in any event */

	if (mb_strlen($_POST['QuotationRef'])<3){
		prnMsg(__('The reference for this order is less than 3 characters') . ' - ' . __('a reference more than 3 characters is required before the order can be added'),'warn');
	}
	if ($_POST['Initiator']==''){
		prnMsg( __('The person entering this order must be specified in the initiator field') . ' - ' . __('a blank initiator is not allowed'),'warn');
	}

	$AllowAdd = true; /*always assume the best */

	/*THEN CHECK FOR THE WORST */

	if (!is_numeric(filter_number_format($_POST['Qty']))){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The quantity of the order item must be numeric'),'warn');
	}

	if (filter_number_format($_POST['Qty'])<0){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The quantity of the ordered item entered must be a positive amount'),'warn');
	}

	if (!is_numeric(filter_number_format($_POST['Price']))){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The price entered must be numeric'),'warn');
	}

	if (!is_numeric(filter_number_format($_POST['Cost']))){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The cost entered must be numeric'),'warn');
	}

	if (((filter_number_format($_POST['Price'])/$_SESSION['SPL'.$identifier]->CustCurrExRate)-(filter_number_format($_POST['Cost'])/$_SESSION['SPL'.$identifier]->SuppCurrExRate))<0){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The sale is at a lower price than the cost'),'warn');
	}

	if (!Is_Date($_POST['ReqDelDate'])){
		$AllowAdd = false;
		prnMsg( __('Cannot Enter this order line') . '<br />' . __('The date entered must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
	}
	if ($AllowAdd == true){

		$_SESSION['SPL'.$identifier]->add_to_order ($_POST['LineNo'],
										filter_number_format($_POST['Qty']),
										$_POST['ItemDescription'],
										filter_number_format($_POST['Price']),
										filter_number_format($_POST['Cost']),
										$_POST['StkCat'],
										$_POST['ReqDelDate']);

		unset($_POST['Price']);
		unset($_POST['Cost']);
		unset($_POST['ItemDescription']);
		unset($_POST['StkCat']);
		unset($_POST['ReqDelDate']);
		unset($_POST['Qty']);
	}
}

if (isset($_POST['StkLocation'])) {
	$_SESSION['SPL'.$identifier]->StkLocation = $_POST['StkLocation'];
}
if (isset($_POST['Initiator'])) {
	$_SESSION['SPL'.$identifier]->Initiator = $_POST['Initiator'];
}
if (isset($_POST['QuotationRef'])) {
	$_SESSION['SPL'.$identifier]->QuotationRef = $_POST['QuotationRef'];
}
if (isset($_POST['Comments'])) {
	$_SESSION['SPL'.$identifier]->Comments = $_POST['Comments'];
}
if (isset($_POST['CustRef'])) {
	$_SESSION['SPL'.$identifier]->CustRef = $_POST['CustRef'];
}

if (isset($_POST['Commit'])){ /*User wishes to commit the order to the database */

 /*First do some validation
	  Is the delivery information all entered*/
	$InputError=0; /*Start off assuming the best */
	if ($_SESSION['SPL'.$identifier]->StkLocation==''
		or ! isset($_SESSION['SPL'.$identifier]->StkLocation)){
		prnMsg( __('The purchase order can not be committed to the database because there is no stock location specified to book any stock items into'),'error');
		$InputError=1;
	} elseif ($_SESSION['SPL'.$identifier]->LinesOnOrder <=0){
		$InputError=1;
		prnMsg(__('The purchase order can not be committed to the database because there are no lines entered on this order'),'error');
	} elseif (mb_strlen($_POST['QuotationRef'])<3){
		$InputError=1;
		prnMsg( __('The reference for this order is less than 3 characters') . ' - ' . __('a reference more than 3 characters is required before the order can be added'),'error');
	} elseif ($_POST['Initiator']==''){
		$InputError=1;
		prnMsg( __('The person entering this order must be specified in the initiator field') . ' - ' . __('a blank initiator is not allowed'),'error');
	}

	if ($InputError!=1){

		if (IsEmailAddress($_SESSION['UserEmail'])){
			$UserDetails  = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName']. '</a>';
		} else {
			$UserDetails  = ' ' . $_SESSION['UsersRealName'] . ' ';
		}

		if ($_SESSION['AutoAuthorisePO']==1) {
			//if the user has authority to authorise the PO then it will automatically be authorised
			$AuthSQL ="SELECT authlevel
						FROM purchorderauth
						WHERE userid='".$_SESSION['UserID']."'
						AND currabrev='".$_SESSION['SPL'.$identifier]->SuppCurrCode."'";

			$AuthResult = DB_query($AuthSQL);
			$AuthRow=DB_fetch_array($AuthResult);

			if (DB_num_rows($AuthResult) > 0
				and $AuthRow['authlevel'] > $_SESSION['SPL'.$identifier]->Order_Value()) { //user has authority to authrorise as well as create the order
				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created and Authorised by') . $UserDetails . '<br />';
				$_SESSION['SPL'.$identifier]->AllowPrintPO=1;
				$_SESSION['SPL'.$identifier]->Status = 'Authorised';
			} else { // no authority to authorise this order
				if (DB_num_rows($AuthResult) ==0){
					$AuthMessage = __('Your authority to approve purchase orders in') . ' ' . $_SESSION['SPL'.$identifier]->SuppCurrCode . ' ' . __('has not yet been set up') . '<br />';
				} else {
					$AuthMessage = __('You can only authorise up to').' '.$_SESSION['SPL'.$identifier]->SuppCurrCode.' '.$AuthRow['authlevel'] .'.<br />';
				}

				prnMsg( __('You do not have permission to authorise this purchase order').'.<br />' .  __('This order is for').' '. $_SESSION['SPL'.$identifier]->SuppCurrCode . ' '. $_SESSION['SPL'.$identifier]->Order_Value() .'. '. $AuthMessage . __('If you think this is a mistake please contact the systems administrator') . '<br />' .  __('The order will be created with a status of pending and will require authorisation'), 'warn');

				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . $UserDetails;
				$_SESSION['SPL'.$identifier]->Status = 'Pending';
			}
		} else { //auto authorise is set to off
			$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . $UserDetails;
			$_SESSION['SPL'.$identifier]->Status = 'Pending';
		}

		$SQL = "SELECT contact,
						deladd1,
						deladd2,
						deladd3,
						deladd4,
						deladd5,
						deladd6
				FROM locations
				WHERE loccode='" . $_SESSION['SPL'.$identifier]->StkLocation . "'";

		$StkLocAddResult = DB_query($SQL);
		$StkLocAddress = DB_fetch_array($StkLocAddResult);

		 DB_Txn_Begin();

		 /*Insert to purchase order header record */
		 $SQL = "INSERT INTO purchorders (supplierno,
					 					comments,
										orddate,
										rate,
										initiator,
										requisitionno,
										intostocklocation,
										deladd1,
										deladd2,
										deladd3,
										deladd4,
										deladd5,
										deladd6,
										contact,
										status,
										stat_comment,
										allowprint,
										revised,
										deliverydate)
							VALUES ('" . $_SESSION['SPL'.$identifier]->SupplierID . "',
							 		'" . $_SESSION['SPL'.$identifier]->Comments . "',
									CURRENT_DATE,
									'" . $_SESSION['SPL'.$identifier]->SuppCurrExRate . "',
									'" . $_SESSION['SPL'.$identifier]->Initiator . "',
									'" . $_SESSION['SPL'.$identifier]->QuotationRef . "',
									'" . $_SESSION['SPL'.$identifier]->StkLocation . "',
									'" . $StkLocAddress['deladd1'] . "',
									'" . $StkLocAddress['deladd2'] . "',
									'" . $StkLocAddress['deladd3'] . "',
									'" . $StkLocAddress['deladd4'] . "',
									'" . $StkLocAddress['deladd5'] . "',
									'" . $StkLocAddress['deladd6'] . "',
									'" . $StkLocAddress['contact'] . "',
									'" . $_SESSION['SPL'.$identifier]->Status . "',
									'" . htmlspecialchars($StatusComment, ENT_QUOTES,'UTF-8')  . "',
									'" . $_SESSION['SPL'.$identifier]->AllowPrintPO . "',
									CURRENT_DATE,
									CURRENT_DATE)";


		$ErrMsg = __('The purchase order header record could not be inserted into the database because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

 		$_SESSION['SPL'.$identifier]->PurchOrderNo = GetNextTransNo(18);

		/*Insert the purchase order detail records */
		foreach ($_SESSION['SPL'.$identifier]->LineItems as $SPLLine) {

			/*Set up the part codes required for this order */

			$PartCode = "*" . $_SESSION['SPL'.$identifier]->PurchOrderNo . "_" . $SPLLine->LineNo;

			$PartAlreadyExists =true; /*assume the worst */
			$Counter = 0;
			while ($PartAlreadyExists==true) {
				$SQL = "SELECT COUNT(*) FROM stockmaster WHERE stockid = '" . $PartCode . "'";
				$PartCountResult = DB_query($SQL);
				$PartCount = DB_fetch_row($PartCountResult);
				if ($PartCount[0]!=0){
					$PartAlreadyExists =true;
					if (mb_strlen($PartCode)==20){
						$PartCode = '*' . mb_strtoupper(mb_substr($_SESSION['SPL'.$identifier]->PurchOrderNo,0,13)) . '_' . $SPLLine->LineNo;
					}
					$PartCode = $PartCode . $Counter;
					$Counter++;
				} else {
					$PartAlreadyExists =false;
				}
			}

			$_SESSION['SPL'.$identifier]->LineItems[$SPLLine->LineNo]->PartCode = $PartCode;

			$SQL = "INSERT INTO stockmaster (stockid,
							categoryid,
							description,
							longdescription,
							materialcost)
					VALUES ('" . $PartCode . "',
						'" . $SPLLine->StkCat . "',
						'" . $SPLLine->ItemDescription . "',
						'" .  $SPLLine->ItemDescription . "',
						'" . $SPLLine->Cost . "')";


			$ErrMsg = __('The item record for line') . ' ' . $SPLLine->LineNo . ' ' . __('could not be created because');

			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "INSERT INTO locstock (loccode, stockid)
					SELECT loccode,'" . $PartCode . "' FROM locations";
			$ErrMsg = __('The item stock locations for the special order line') . ' ' . $SPLLine->LineNo . ' ' .__('could not be created because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*need to get the stock category GL information */
			$SQL = "SELECT stockact FROM stockcategory WHERE categoryid = '" . $SPLLine->StkCat . "'";
			$ErrMsg = __('The item stock category information for the special order line') . ' ' . $SPLLine->LineNo . ' ' . __('could not be retrieved because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$StkCatGL=DB_fetch_row($Result);
			$GLCode = $StkCatGL[0];

			$OrderDate = FormatDateForSQL($SPLLine->ReqDelDate);

			$SQL = "INSERT INTO purchorderdetails (orderno,
								itemcode,
								deliverydate,
								itemdescription,
								glcode,
								unitprice,
								quantityord)
					VALUES ('";
			$SQL = $SQL . $_SESSION['SPL'.$identifier]->PurchOrderNo . "',
					'" . $PartCode . "',
					'" . $OrderDate . "',
					'" . $SPLLine->ItemDescription . "',
					'" . $GLCode . "',
					'" . $SPLLine->Cost . "',
					'" . $SPLLine->Quantity . "')";

			$ErrMsg = __('One of the purchase order detail records could not be inserted into the database because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		} /* end of the loop round the detail line items on the order */

		echo '<br /><br />' . __('Purchase Order') . ' ' . $_SESSION['SPL'.$identifier]->PurchOrderNo . ' ' . __('on') . ' ' . $_SESSION['SPL'.$identifier]->SupplierName . ' ' . __('has been created');
		echo '<br /><a href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $_SESSION['SPL'.$identifier]->PurchOrderNo . '">' . __('Print Purchase Order') . '</a>';

/*Now insert the sales order too */

		/*First get the customer delivery information */
		$SQL = "SELECT salestype,
					brname,
					braddress1,
					braddress2,
					braddress3,
					braddress4,
					braddress5,
					braddress6,
					defaultshipvia,
					email,
					phoneno
				FROM custbranch INNER JOIN debtorsmaster
					ON custbranch.debtorno=debtorsmaster.debtorno
				WHERE custbranch.debtorno='" . $_SESSION['SPL'.$identifier]->CustomerID . "'
				AND custbranch.branchcode = '" . $_SESSION['SPL'.$identifier]->BranchCode . "'";

		$ErrMsg = __('The delivery and sales type for the customer could not be retrieved for this special order') . ' ' . $SPLLine->LineNo . ' ' . __('because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		$BranchDetails=DB_fetch_array($Result);
		$SalesOrderNo=GetNextTransNo (30);
		$HeaderSQL = "INSERT INTO salesorders (orderno,
											debtorno,
											branchcode,
											customerref,
											orddate,
											ordertype,
											shipvia,
											deliverto,
											deladd1,
											deladd2,
											deladd3,
											deladd4,
											deladd5,
											deladd6,
											contactphone,
											contactemail,
											fromstkloc,
											deliverydate)
					VALUES ('" . $SalesOrderNo."',
							'" . $_SESSION['SPL'.$identifier]->CustomerID . "',
							'" . $_SESSION['SPL'.$identifier]->BranchCode . "',
							'" . $_SESSION['SPL'.$identifier]->CustRef ."',
							CURRENT_DATE,
							'" . $BranchDetails['salestype'] . "',
							'" . $BranchDetails['defaultshipvia'] ."',
							'" . $BranchDetails['brname'] . "',
							'" . $BranchDetails['braddress1'] . "',
							'" . $BranchDetails['braddress2'] . "',
							'" . $BranchDetails['braddress3'] . "',
							'" . $BranchDetails['braddress4'] . "',
							'" . $BranchDetails['braddress5'] . "',
							'" . $BranchDetails['braddress6'] . "',
							'" . $BranchDetails['phoneno'] . "',
							'" . $BranchDetails['email'] . "',
							'" . $_SESSION['SPL'.$identifier]->StkLocation ."',
							'" . $OrderDate . "')";

		$ErrMsg = __('The sales order cannot be added because');
		$InsertQryResult = DB_query($HeaderSQL, $ErrMsg);

		$StartOf_LineItemsSQL = "INSERT INTO salesorderdetails (orderno,
																stkcode,
																unitprice,
																quantity,
																orderlineno)
						VALUES ('" .  $SalesOrderNo . "'";

		$ErrMsg = __('There was a problem inserting a line into the sales order because');

		foreach ($_SESSION['SPL'.$identifier]->LineItems as $StockItem) {

			$LineItemsSQL = $StartOf_LineItemsSQL . ",
							'" . $StockItem->PartCode . "',
							'". $StockItem->Price . "',
							'" . $StockItem->Quantity . "',
							'" . $StockItem->LineNo . "')";
			$Ins_LineItemResult = DB_query($LineItemsSQL, $ErrMsg);

		} /* inserted line items into sales order details */

		unset($_SESSION['SPL'.$identifier]);
		prnMsg(__('Sales Order Number') . ' ' . $SalesOrderNo . ' ' . __('has been entered') . '. <br />' .
			__('Orders created on a cash sales account may need the delivery details for the order to be modified') . '. <br /><br />' .
				__('A freight charge may also be applicable'),'success');

		if (count($_SESSION['AllowedPageSecurityTokens'])>1){

			/* Only allow print of packing slip for internal staff - customer logon's cannot go here */
			echo '<p><a href="' . $RootPath . '/PrintCustOrder.php?TransNo=' . $SalesOrderNo . '">' . __('Print packing slip') . ' (' . __('Preprinted stationery') . ')</a></p>';
			echo '<p><a href="' . $RootPath . '/PrintCustOrder_generic.php?TransNo=' . $SalesOrderNo . '">' . __('Print packing slip') . ' (' . __('Laser') . ')</a></p>';

		}

		DB_Txn_Commit();
		unset($_SESSION['SPL'.$identifier]); /*Clear the PO data to allow a newy to be input*/
		echo '<br /><br /><a href="' . $RootPath . '/SpecialOrder.php">' . __('Enter A New Special Order') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	} /*end if there were no input errors trapped */
} /* end of the code to do transfer the SPL object to the database  - user hit the place Order*/


echo '<div class="db-page-content">';

// Calculate Totals for KPIs
$TotalSales = 0;
$TotalCostSupp = 0;
foreach ($_SESSION['SPL'.$identifier]->LineItems as $SPLLine) {
    $TotalSales += ($SPLLine->Price * $SPLLine->Quantity);
    $TotalCostSupp += ($SPLLine->Cost * $SPLLine->Quantity);
}
$MarginAmt = $TotalSales - ($TotalCostSupp * $_SESSION['SPL'.$identifier]->SuppCurrExRate / $_SESSION['SPL'.$identifier]->CustCurrExRate);
$MarginPct = $TotalSales > 0 ? ($MarginAmt / $TotalSales) * 100 : 0;

echo '<!-- KPI Metrics Row -->
    <div class="kpi-grid" style="margin-bottom: var(--space-6);">
        <div class="kpi-card-v2">
            <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="kpi-data">
                <span class="label">' . __('Total Sales') . ' (' . $_SESSION['SPL'.$identifier]->CustCurrCode . ')</span>
                <span class="value">' . locale_number_format($TotalSales, $_SESSION['SPL'.$identifier]->CustCurrDecimalPlaces) . '</span>
            </div>
        </div>
        
        <div class="kpi-card-v2">
            <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);">
                <i class="fas fa-tags"></i>
            </div>
            <div class="kpi-data">
                <span class="label">' . __('Total Cost') . ' (' . $_SESSION['SPL'.$identifier]->SuppCurrCode . ')</span>
                <span class="value">' . locale_number_format($TotalCostSupp, $_SESSION['SPL'.$identifier]->SuppCurrDecimalPlaces) . '</span>
            </div>
        </div>

        <div class="kpi-card-v2">
            <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="kpi-data">
                <span class="label">' . __('Margin Amount') . '</span>
                <span class="value" style="color: ' . ($MarginAmt >= 0 ? 'var(--success-color)' : 'var(--danger-color)') . ';">' . locale_number_format($MarginAmt, $_SESSION['SPL'.$identifier]->CustCurrDecimalPlaces) . '</span>
            </div>
        </div>

        <div class="kpi-card-v2">
            <div class="kpi-icon" style="background: ' . ($MarginPct >= 15 ? 'var(--success-soft)' : 'var(--warning-soft)') . '; color: ' . ($MarginPct >= 15 ? 'var(--success)' : 'var(--warning)') . ';">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="kpi-data">
                <span class="label">' . __('Margin %') . '</span>
                <span class="value">' . locale_number_format($MarginPct, 1) . '%</span>
            </div>
        </div>
    </div>';

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="db-card aside-info-card supplier">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-truck"></i> ' . __('Supplier') . '</div>
                </div>
                <div class="db-card-body">
                    <div class="info-label">' . __('Name') . '</div>
                    <div class="info-value">' . $_SESSION['SPL'.$identifier]->SupplierName . '</div>
                    <div class="currency-meta">' . __('Currency') . ': ' . $_SESSION['SPL'.$identifier]->SuppCurrCode . '</div>
                </div>
            </div>

            <div class="db-card aside-info-card customer" style="margin-top: 20px;">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-user-tie"></i> ' . __('Customer') . '</div>
                </div>
                <div class="db-card-body">
                    <div class="info-label">' . __('Name') . '</div>
                    <div class="info-value">' . $_SESSION['SPL'.$identifier]->CustomerName . '</div>
                    <div class="info-label" style="margin-top: 10px;">' . __('Branch') . '</div>
                    <div class="info-value">' . $_SESSION['SPL'.$identifier]->BranchName . '</div>
                </div>
            </div>

            <div class="db-card" style="margin-top: 20px;">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Fulfillment') . '</div>
                </div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label>' . __('Stock Location') . '</label>
                        <select name="StkLocation" class="db-select">';
                        $SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
                        $LocnResult = DB_query($SQL);
                        while ($LocnRow=DB_fetch_array($LocnResult)){
                            $selected = ($_SESSION['SPL'.$identifier]->StkLocation == $LocnRow['loccode']) ? 'selected' : '';
                            echo '<option ' . $selected . ' value="' . $LocnRow['loccode'] . '">' . $LocnRow['locationname'] . '</option>';
                        }
echo '                  </select>
                    </div>
                </div>
            </div>

            <div class="db-card" style="margin-top: 20px; background: var(--surface-alt);">
                <div class="db-card-body" style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="submit" name="Commit" class="db-btn db-btn-primary" style="width: 100%;">
                        <i class="fas fa-check-circle"></i> ' . __('Process Order') . '
                    </button>
                    <button type="submit" name="Cancel" class="db-btn db-btn-outline-danger" style="width: 100%;">
                        <i class="fas fa-undo"></i> ' . __('Start Again') . '
                    </button>
                </div>
            </div>
        </aside>

        <main class="db-col-main">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Order References') . '</div>
                </div>
                <div class="db-card-body">
                    <div class="db-grid-3">
                        <div class="db-field">
                            <label>' . __('Initiated By') . '</label>
                            <input type="text" name="Initiator" class="db-input" value="' . $_SESSION['SPL'.$identifier]->Initiator . '" />
                        </div>
                        <div class="db-field">
                            <label>' . __('Special Ref') . '</label>
                            <input type="text" name="QuotationRef" class="db-input" value="' . $_SESSION['SPL'.$identifier]->QuotationRef . '" />
                        </div>
                        <div class="db-field">
                            <label>' . __('Customer Ref') . '</label>
                            <input type="text" name="CustRef" class="db-input" value="' . $_SESSION['SPL'.$identifier]->CustRef . '" />
                        </div>
                    </div>
                    <div class="db-field" style="margin-top: 15px;">
                        <label>' . __('Comments') . '</label>
                        <textarea name="Comments" class="db-input" rows="2">' . $_SESSION['SPL'.$identifier]->Comments . '</textarea>
                    </div>
                </div>
            </div>';

if (count($_SESSION['SPL'.$identifier]->LineItems)>0){
    echo '<div class="db-card" style="margin-top: 20px;">
            <div class="db-card-header">
                <div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Current Lines') . '</div>
            </div>
            <div class="db-card-body p-0">
                <div class="table-container">
                    <table class="db-table">';
    echo '<thead>
            <tr>
                <th>' . __('Item Description') . '</th>
                <th>' . __('Delivery') . '</th>
                <th class="text-right">' . __('Qty') . '</th>
                <th class="text-right">' . __('Cost') . ' (' . $_SESSION['SPL'.$identifier]->SuppCurrCode . ')</th>
                <th class="text-right">' . __('Price') . ' (' . $_SESSION['SPL'.$identifier]->CustCurrCode . ')</th>
                <th class="text-right">' . __('Actions') . '</th>
            </tr>
          </thead>
          <tbody>';

    foreach ($_SESSION['SPL'.$identifier]->LineItems as $SPLLine) {
        echo '<tr>
                <td><div style="font-weight:600;">' . $SPLLine->ItemDescription . '</div></td>
                <td>' . $SPLLine->ReqDelDate . '</td>
                <td class="text-right">' . locale_number_format($SPLLine->Quantity, 'Variable') . '</td>
                <td class="text-right">' . locale_number_format($SPLLine->Cost, $_SESSION['SPL'.$identifier]->SuppCurrDecimalPlaces) . '</td>
                <td class="text-right">' . locale_number_format($SPLLine->Price, $_SESSION['SPL'.$identifier]->CustCurrDecimalPlaces) . '</td>
                <td class="text-right">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '&Delete=' . $SPLLine->LineNo . '" class="db-btn db-btn-outline-danger" style="padding: 4px 10px;">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </td>
              </tr>';
    }
    echo '</tbody></table></div></div></div>';
}

echo '<div class="db-card" style="margin-top: 20px;">
        <div class="db-card-header">
            <div class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Add Special Item') . '</div>
        </div>
        <div class="db-card-body">
            <input type="hidden" name="LineNo" value="' . ($_SESSION['SPL'.$identifier]->LinesOnOrder + 1) .'" />';
            
            echo '<div class="db-field">
                    <label>' . __('Item Description') . '</label>
                    <input type="text" name="ItemDescription" class="db-input" placeholder="' . __('Detailed description of the non-stock item...') . '" value="' . ($_POST['ItemDescription'] ?? '') . '" />
                  </div>';

            echo '<div class="db-grid-2" style="margin-top: 15px;">
                    <div class="db-field">
                        <label>' . __('Stock Category') . '</label>
                        <select name="StkCat" class="db-select">';
                        $SQL = "SELECT categoryid, categorydescription FROM stockcategory";
                        $Result = DB_query($SQL);
                        while ($MyRow=DB_fetch_array($Result)){
                            $selected = (isset($_POST['StkCat']) and $MyRow['categoryid']==$_POST['StkCat']) ? 'selected' : '';
                            echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
                        }
echo '                  </select>
                    </div>
                    <div class="db-field">
                        <label>' . __('Required Delivery Date') . '</label>
                        <input type="date" name="ReqDelDate" class="db-input" value="' . FormatDateForSQL($_POST['ReqDelDate'] ?? date($_SESSION['DefaultDateFormat'], strtotime('+1 day'))) . '" />
                    </div>
                  </div>';

            echo '<div class="db-grid-3" style="margin-top: 15px;">
                    <div class="db-field">
                        <label>' . __('Quantity') . '</label>
                        <input type="text" name="Qty" class="db-input text-right" value="' . locale_number_format($_POST['Qty'] ?? 1, 'Variable') . '" />
                    </div>
                    <div class="db-field">
                        <label>' . __('Unit Cost') . ' (' . $_SESSION['SPL'.$identifier]->SuppCurrCode . ')</label>
                        <input type="text" name="Cost" class="db-input text-right" value="' . locale_number_format($_POST['Cost'] ?? 0, $_SESSION['SPL'.$identifier]->SuppCurrDecimalPlaces) . '" />
                    </div>
                    <div class="db-field">
                        <label>' . __('Unit Price') . ' (' . $_SESSION['SPL'.$identifier]->CustCurrCode . ')</label>
                        <input type="text" name="Price" class="db-input text-right" value="' . locale_number_format($_POST['Price'] ?? 0, $_SESSION['SPL'.$identifier]->CustCurrDecimalPlaces) . '" />
                    </div>
                  </div>';

            echo '<div style="margin-top: 20px; text-align: right;">
                    <button type="submit" name="EnterLine" class="db-btn db-btn-primary">
                        <i class="fas fa-cart-plus"></i> ' . __('Add Item to Order') . '
                    </button>
                  </div>';
echo '  </div>
      </div>
    </main>
  </div> <!-- End Layout -->
</div> <!-- End Content -->
</div> <!-- End Page -->
</form>';

include(__DIR__ . '/includes/footer.php');
