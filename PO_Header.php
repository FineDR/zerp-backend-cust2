<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefinePOClass.php');

require(__DIR__ . '/includes/session.php');

if (isset($_GET['ModifyOrderNumber'])) {
	$Title = __('Modify Purchase Order') . ' ' . $_GET['ModifyOrderNumber'];
} else {
	$Title = __('Purchase Order Entry');
}
$ViewTopic = 'PurchaseOrdering';
$BookMark = 'PurchaseOrdering';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['DeliveryDate'])){$_POST['DeliveryDate'] = ConvertSQLDate($_POST['DeliveryDate']);}

if (isset($_GET['SupplierID'])) {
	$_POST['Select'] = $_GET['SupplierID'];
}

/*If the page is called without an identifier being set then
 * it must be either a new order, or the start of a modification of an
 * order, and so we must create a new identifier.
 *
 * The identifier only needs to be unique for this php session, so a
 * unix timestamp will be sufficient.
*/

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

/*Page is called with NewOrder=Yes when a new order is to be entered
 * the session variable that holds all the PO data $_SESSION['PO'][$identifier]
 * is unset to allow all new details to be created */

if (isset($_GET['NewOrder']) and isset($_SESSION['PO' . $identifier])) {
	unset($_SESSION['PO' . $identifier]);
	$_SESSION['ExistingOrder'] = 0;
}

if (isset($_POST['Select']) and empty($_POST['SupplierContact'])) {
	$SQL = "SELECT contact
				FROM suppliercontacts
				WHERE supplierid='" . $_POST['Select'] . "'";

	$SuppCoResult = DB_query($SQL);
	if (DB_num_rows($SuppCoResult) > 0) {
		$MyRow = DB_fetch_row($SuppCoResult);
		$_POST['SupplierContact'] = $MyRow[0];
	} else {
		$_POST['SupplierContact'] = '';
	}
}

if ((isset($_POST['UpdateStatus']) and $_POST['UpdateStatus'] != '')) {

	if ($_SESSION['ExistingOrder'] == 0) {
		prnMsg(__('This is a new order. It must be created before you can change the status'), 'warn');
		$OKToUpdateStatus = 0;
	} elseif ($_SESSION['PO' . $identifier]->Status != $_POST['Status']) { //the old status  != new status
		$OKToUpdateStatus = 1;
		$AuthSQL = "SELECT authlevel
					FROM purchorderauth
					WHERE userid='" . $_SESSION['UserID'] . "'
					AND currabrev='" . $_SESSION['PO' . $identifier]->CurrCode . "'";

		$AuthResult = DB_query($AuthSQL);
		$MyRow = DB_fetch_array($AuthResult);
		$AuthorityLevel = $MyRow['authlevel'];
		$OrderTotal = $_SESSION['PO' . $identifier]->Order_Value();

		if ($_POST['StatusComments'] != '') {
			$_POST['StatusComments'] = ' - ' . $_POST['StatusComments'];
		}
		if (IsEmailAddress($_SESSION['UserEmail'])) {
			$UserChangedStatus = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName'] . '</a>';
		} else {
			$UserChangedStatus = ' ' . $_SESSION['UsersRealName'] . ' ';
		}

		if ($_POST['Status'] == 'Authorised') {
			if ($AuthorityLevel > $OrderTotal) {
				$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Authorised by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');
				$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
			} else {
				$OKToUpdateStatus = 0;
				prnMsg(__('You do not have permission to authorise this purchase order') . '.<br />' . __('This order is for') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $OrderTotal . '. ' . __('You can only authorise up to') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $AuthorityLevel . '.<br />' . __('If you think this is a mistake please contact the systems administrator'), 'warn');
			}
		}

		if ($_POST['Status'] == 'Rejected' or $_POST['Status'] == 'Cancelled') {
			if (!isset($_SESSION['ExistingOrder']) or $_SESSION['ExistingOrder'] != 0) {
				/* need to check that not already dispatched or invoiced by the supplier */
				if ($_SESSION['PO' . $identifier]->Any_Already_Received() == 1) {
					$OKToUpdateStatus = 0; //not ok to update the status
					prnMsg(__('This order cannot be cancelled or rejected because some of it has already been received') . '. ' . __('The line item quantities may be modified to quantities more than already received') . '. ' . __('Prices cannot be altered for lines that have already been received') . ' ' . __('and quantities cannot be reduced below the quantity already received'), 'warn');
				}
				$ShipmentExists = $_SESSION['PO' . $identifier]->Any_Lines_On_A_Shipment();
				if ($ShipmentExists != false) {
					$OKToUpdateStatus = 0; //not ok to update the status
					prnMsg(__('This order cannot be cancelled or rejected because there is at least one line that is allocated to a shipment') . '. ' . __('See shipment number') . ' ' . $ShipmentExists, 'warn');
				}
			} //!isset($_SESSION['ExistingOrder']) OR $_SESSION['ExistingOrder'] != 0
			if ($OKToUpdateStatus == 1) { // none of the order has been received
				if ($AuthorityLevel > $OrderTotal) {
					$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . $_POST['Status'] . ' ' . __('by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');
				} else {
					$OKToUpdateStatus = 0;
					prnMsg(__('You do not have permission to reject this purchase order') . '.<br />' . __('This order is for') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $OrderTotal . '. ' . __('Your authorisation limit is set at') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $AuthorityLevel . '.<br />' . __('If you think this is a mistake please contact the systems administrator'), 'warn');
				}
			} //$OKToUpdateStatus == 1

		} //$_POST['Status'] == 'Rejected' OR $_POST['Status'] == 'Cancelled'
		if ($_POST['Status'] == 'Pending') {

			if ($_SESSION['PO' . $identifier]->Any_Already_Received() == 1) {
				$OKToUpdateStatus = 0; //not OK to update status
				prnMsg(__('This order could not have the status changed back to pending because some of it has already been received. Quantities received will need to be returned to change the order back to pending.'), 'warn');
			}

			if (($AuthorityLevel > $OrderTotal or $_SESSION['UserID'] == $_SESSION['PO' . $identifier]->Initiator) and $OKToUpdateStatus == 1) {
				$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order set to pending status by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');

			} elseif ($AuthorityLevel < $OrderTotal and $_SESSION['UserID'] != $_SESSION['PO' . $identifier]->Initiator) {
				$OKToUpdateStatus = 0;
				prnMsg(__('You do not have permission to change the status of this purchase order') . '.<br />' . __('This order is for') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $OrderTotal . '. ' . __('Your authorisation limit is set at') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . ' ' . $AuthorityLevel . '.<br />' . __('If you think this is a mistake please contact the systems administrator'), 'warn');
			} //$AuthorityLevel < $OrderTotal AND $_SESSION['UserID'] != $_SESSION['PO' . $identifier]->Initiator

		} //$_POST['Status'] == 'Pending'
		if ($OKToUpdateStatus == 1) {
			$_SESSION['PO' . $identifier]->Status = $_POST['Status'];
			if ($_SESSION['PO' . $identifier]->Status == 'Authorised') {
				$AllowPrint = 1;
			} //$_SESSION['PO' . $identifier]->Status == 'Authorised'
			else {
				$AllowPrint = 0;
			}
			$SQL = "UPDATE purchorders SET status='" . $_POST['Status'] . "',
							stat_comment='" . $_SESSION['PO' . $identifier]->StatusComments . "',
							allowprint='" . $AllowPrint . "'
					WHERE purchorders.orderno ='" . $_SESSION['ExistingOrder'] . "'";

			$ErrMsg = __('The order status could not be updated because');
			$UpdateResult = DB_query($SQL, $ErrMsg);

			if ($_POST['Status'] == 'Completed' or $_POST['Status'] == 'Cancelled' or $_POST['Status'] == 'Rejected') {
				$SQL = "UPDATE purchorderdetails SET completed=1 WHERE orderno='" . $_SESSION['ExistingOrder'] . "'";
				$UpdateResult = DB_query($SQL, $ErrMsg);
			} else { //To ensure that the purchorderdetails status is correct when it is recovered from a cancelled orders
				$SQL = "UPDATE purchorderdetails SET completed=0 WHERE orderno='" . $_SESSION['ExistingOrder'] . "'";
				$UpdateResult = DB_query($SQL, $ErrMsg);
			}
		} //$OKToUpdateStatus == 1

	} //end if there is actually a status change the class Status != the POST['Status']

} //End if user hit Update Status
if (isset($_GET['NewOrder']) and isset($_GET['StockID']) and isset($_GET['SelectedSupplier'])) {
	/*
	 * initialise a new order
	*/
	$_SESSION['ExistingOrder'] = 0;
	unset($_SESSION['PO' . $identifier]);
	/* initialise new class object */
	$_SESSION['PO' . $identifier] = new PurchOrder;
	/*
	 * and fill it with essential data
	*/
	$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
	/* Of course 'cos the order aint even started !!*/
	$_SESSION['PO' . $identifier]->GLLink = $_SESSION['CompanyRecord']['gllink_stock'];
	/* set the SupplierID we got */
	$_SESSION['PO' . $identifier]->SupplierID = $_GET['SelectedSupplier'];
	$_SESSION['PO' . $identifier]->DeliveryDate = date($_SESSION['DefaultDateFormat']);
	$_SESSION['PO' . $identifier]->Initiator = $_SESSION['UserID'];
	$_SESSION['RequireSupplierSelection'] = 0;
	$_POST['Select'] = $_GET['SelectedSupplier'];

	/*
	 * the item (it's item code) that should be purchased
	*/
	$Purch_Item = $_GET['StockID'];

} //End if it's a new order sent with supplier code and the item to order
if (isset($_POST['EnterLines']) or isset($_POST['AllowRePrint'])) {
	/*User hit the button to enter line items -
	 *  ensure session variables updated then meta refresh to PO_Items.php*/

	$_SESSION['PO' . $identifier]->Location = $_POST['StkLocation'];
	$_SESSION['PO' . $identifier]->SupplierContact = $_POST['SupplierContact'] ?? '';
	$_SESSION['PO' . $identifier]->DelAdd1 = $_POST['DelAdd1'];
	$_SESSION['PO' . $identifier]->DelAdd2 = $_POST['DelAdd2'];
	$_SESSION['PO' . $identifier]->DelAdd3 = $_POST['DelAdd3'];
	$_SESSION['PO' . $identifier]->DelAdd4 = $_POST['DelAdd4'];
	$_SESSION['PO' . $identifier]->DelAdd5 = $_POST['DelAdd5'];
	$_SESSION['PO' . $identifier]->DelAdd6 = $_POST['DelAdd6'];
	$_SESSION['PO' . $identifier]->SuppDelAdd1 = $_POST['SuppDelAdd1'];
	$_SESSION['PO' . $identifier]->SuppDelAdd2 = $_POST['SuppDelAdd2'];
	$_SESSION['PO' . $identifier]->SuppDelAdd3 = $_POST['SuppDelAdd3'];
	$_SESSION['PO' . $identifier]->SuppDelAdd4 = $_POST['SuppDelAdd4'];
	$_SESSION['PO' . $identifier]->SuppDelAdd5 = $_POST['SuppDelAdd5'];
	$_SESSION['PO' . $identifier]->SuppTel = $_POST['SuppTel'];
	$_SESSION['PO' . $identifier]->Initiator = $_POST['Initiator'];
	$_SESSION['PO' . $identifier]->RequisitionNo = $_POST['Requisition'];
	$_SESSION['PO' . $identifier]->Version = $_POST['Version'];
	$_SESSION['PO' . $identifier]->DeliveryDate = $_POST['DeliveryDate'];
	$_SESSION['PO' . $identifier]->Revised = $_POST['Revised'];
	$_SESSION['PO' . $identifier]->ExRate = filter_number_format($_POST['ExRate']);
	$_SESSION['PO' . $identifier]->Comments = $_POST['Comments'];
	$_SESSION['PO' . $identifier]->DeliveryBy = $_POST['DeliveryBy'];
	if (isset($_POST['StatusComments'])) {
		$_SESSION['PO' . $identifier]->StatusComments = $_POST['StatusComments'];
	}
	$_SESSION['PO' . $identifier]->PaymentTerms = $_POST['PaymentTerms'];
	$_SESSION['PO' . $identifier]->Contact = $_POST['Contact'];
	$_SESSION['PO' . $identifier]->Tel = $_POST['Tel'];
	$_SESSION['PO' . $identifier]->Port = $_POST['Port'];

	if (isset($_POST['RePrint']) and $_POST['RePrint'] == 1) {
		$_SESSION['PO' . $identifier]->AllowPrintPO = 1;

		$SQL = "UPDATE purchorders
				SET purchorders.allowprint='1'
				WHERE purchorders.orderno='" . $_SESSION['PO' . $identifier]->OrderNo . "'";

		$ErrMsg = __('An error occurred updating the purchase order to allow reprints') . '. ' . __('The error says');
		$UpdateResult = DB_query($SQL, $ErrMsg);
	} //end if change to allow reprint
	else {
		$_POST['RePrint'] = 0;
	}
	if (!isset($_POST['AllowRePrint'])) { // user only hit update not "Enter Lines"
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PO_Items.php?identifier=' . $identifier . '">';
		echo '<p>';
		prnMsg(__('You should automatically be forwarded to the entry of the purchase order line items page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/PO_Items.php?identifier=' . $identifier . '">' . __('click here') . '</a> ' . __('to continue'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	} // end if reprint not allowed

} //isset($_POST['EnterLines']) OR isset($_POST['AllowRePrint'])
/* end of if isset _POST'EnterLines' */

echo '<div class="db-page-header">
		<div>
			<h1 class="db-page-title">' . $Title . '</h1>
			<p class="db-page-subtitle">' . __('Manage purchase order header information and supplier details') . '</p>
		</div>
		<div class="db-page-actions">
			<a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?identifier=' . $identifier . '" class="db-btn db-btn-secondary">
				<svg class="db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
				' . __('Back to Purchase Orders') . '
			</a>
		</div>
	</div>';

/*The page can be called with ModifyOrderNumber=x where x is a purchase
 * order number. The page then looks up the details of order x and allows
 * these details to be modified */

if (isset($_GET['ModifyOrderNumber'])) {
	include(__DIR__ . '/includes/PO_ReadInOrder.php');
}

if (!isset($_SESSION['PO' . $identifier])) {
	/* It must be a new order being created
	 * $_SESSION['PO'.$identifier] would be set up from the order modification
	 * code above if a modification to an existing order. Also
	 * $ExistingOrder would be set to 1. The delivery check screen
	 * is where the details of the order are either updated or
	 * inserted depending on the value of ExistingOrder
	 * */

	$_SESSION['ExistingOrder'] = 0;
	$_SESSION['PO' . $identifier] = new PurchOrder;
	$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
	/*Of course cos the order aint even started !!*/
	$_SESSION['PO' . $identifier]->GLLink = $_SESSION['CompanyRecord']['gllink_stock'];

	if ($_SESSION['PO' . $identifier]->SupplierID == '' or !isset($_SESSION['PO' . $identifier]->SupplierID)) {
		/* a session variable will have to maintain if a supplier
		 * has been selected for the order or not the session
		 * variable supplierID holds the supplier code already
		 * as determined from user id /password entry  */
		$_SESSION['RequireSupplierSelection'] = 1;
	} else {
		$_SESSION['RequireSupplierSelection'] = 0;
	}

} //end if initiating a new PO
if (isset($_POST['ChangeSupplier'])) {
	if ($_SESSION['PO' . $identifier]->Status == 'Pending' and $_SESSION['UserID'] == $_SESSION['PO' . $identifier]->Initiator) {

		if ($_SESSION['PO' . $identifier]->Any_Already_Received() == 0) {

			$_SESSION['RequireSupplierSelection'] = 1;
			$_SESSION['PO' . $identifier]->Status = 'Pending';
			$_SESSION['PO' . $identifier]->StatusComments == date($_SESSION['DefaultDateFormat']) . ' - ' . __('Supplier changed by') . ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UserID'] . '</a> - ' . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');

		} else {

			echo '<br /><br />';
			prnMsg(__('Cannot modify the supplier of the order once some of the order has been received'), 'warn');
		}
	}
} //user hit ChangeSupplier
if (isset($_POST['SearchSuppliers'])) {
	if (mb_strlen($_POST['Keywords']) > 0 and mb_strlen($_SESSION['PO' . $identifier]->SupplierID) > 0) {
		prnMsg(__('Supplier name keywords have been used in preference to the supplier code extract entered'), 'warn');
	}
	if (mb_strlen($_POST['Keywords']) > 0) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		$SQL = "SELECT suppliers.supplierid,
							suppliers.suppname,
							suppliers.address1,
							suppliers.address2,
							suppliers.address3,
							suppliers.address4,
							suppliers.address5,
							suppliers.address6,
							suppliers.currcode
						FROM suppliers
						WHERE suppliers.suppname " . LIKE . " '" . $SearchString . "'
						ORDER BY suppliers.suppname";

	} elseif (mb_strlen($_POST['SuppCode']) > 0) {

		$SQL = "SELECT suppliers.supplierid,
							suppliers.suppname,
							suppliers.address1,
							suppliers.address2,
							suppliers.address3,
							suppliers.address4,
							suppliers.address5,
							suppliers.address6,
							suppliers.currcode
						FROM suppliers
						WHERE suppliers.supplierid " . LIKE . " '%" . $_POST['SuppCode'] . "%'
						ORDER BY suppliers.supplierid";
	} else {

		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						suppliers.address1,
						suppliers.address2,
						suppliers.address3,
						suppliers.address4,
						suppliers.address5,
						suppliers.address6,
						suppliers.currcode
					FROM suppliers
					ORDER BY suppliers.supplierid";
	}

	$ErrMsg = __('The searched supplier records requested cannot be retrieved because');
	$Result_SuppSelect = DB_query($SQL, $ErrMsg);
	$SuppliersReturned = DB_num_rows($Result_SuppSelect);
	if (DB_num_rows($Result_SuppSelect) == 1) {
		$MyRow = DB_fetch_array($Result_SuppSelect);
		$_POST['Select'] = $MyRow['supplierid'];
	} elseif (DB_num_rows($Result_SuppSelect) == 0) {
		prnMsg(__('No supplier records contain the selected text') . ' - ' . __('please alter your search criteria and try again'), 'info');
	}
} /*end of if search for supplier codes/names */

if ((!isset($_POST['SearchSuppliers']) or $_POST['SearchSuppliers'] == '') and (isset($_SESSION['PO' . $identifier]->SupplierID) and $_SESSION['PO' . $identifier]->SupplierID != '')) {
	/*	The session variables are set but the form variables could have been lost
	 need to restore the form variables from the session */
	$_POST['SupplierID'] = $_SESSION['PO' . $identifier]->SupplierID;
	$_POST['SupplierName'] = $_SESSION['PO' . $identifier]->SupplierName;
	$_POST['CurrCode'] = $_SESSION['PO' . $identifier]->CurrCode;
	$_POST['ExRate'] = $_SESSION['PO' . $identifier]->ExRate;
	$_POST['PaymentTerms'] = $_SESSION['PO' . $identifier]->PaymentTerms;
	$_POST['DelAdd1'] = $_SESSION['PO' . $identifier]->DelAdd1;
	$_POST['DelAdd2'] = $_SESSION['PO' . $identifier]->DelAdd2;
	$_POST['DelAdd3'] = $_SESSION['PO' . $identifier]->DelAdd3;
	$_POST['DelAdd4'] = $_SESSION['PO' . $identifier]->DelAdd4;
	$_POST['DelAdd5'] = $_SESSION['PO' . $identifier]->DelAdd5;
	$_POST['DelAdd6'] = $_SESSION['PO' . $identifier]->DelAdd6;
	$_POST['SuppDelAdd1'] = $_SESSION['PO' . $identifier]->SuppDelAdd1;
	$_POST['SuppDelAdd2'] = $_SESSION['PO' . $identifier]->SuppDelAdd2;
	$_POST['SuppDelAdd3'] = $_SESSION['PO' . $identifier]->SuppDelAdd3;
	$_POST['SuppDelAdd4'] = $_SESSION['PO' . $identifier]->SuppDelAdd4;
	$_POST['SuppDelAdd5'] = $_SESSION['PO' . $identifier]->SuppDelAdd5;
	$_POST['SuppDelAdd6'] = $_SESSION['PO' . $identifier]->SuppDelAdd6;
	if (!isset($_POST['DeliveryDate'])) {
		$_POST['DeliveryDate'] = $_SESSION['PO' . $identifier]->DeliveryDate;
	}

}

if (isset($_POST['Select'])) {
	/* will only be true if page called from supplier selection form or item purchasing data order link
	 * or set because only one supplier record returned from a search
	*/

	$SQL = "SELECT suppliers.suppname,
					suppliers.currcode,
					currencies.rate,
					currencies.decimalplaces,
					suppliers.paymentterms,
					suppliers.address1,
					suppliers.address2,
					suppliers.address3,
					suppliers.address4,
					suppliers.address5,
					suppliers.address6,
					suppliers.telephone,
					suppliers.port,
					suppliers.defaultshipper
				FROM suppliers INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_POST['Select'] . "'";

	$ErrMsg = __('The supplier record of the supplier selected') . ': ' . $_POST['Select'] . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow = DB_fetch_array($Result);
	// added for suppliers lookup fields
	$AuthSql = "SELECT cancreate
				FROM purchorderauth
				WHERE userid='" . $_SESSION['UserID'] . "'
				AND currabrev='" . $MyRow['currcode'] . "'";

	$AuthResult = DB_query($AuthSql);

	if (($AuthRow = DB_fetch_array($AuthResult) and $AuthRow['cancreate'] == 0)) {
		$_POST['SupplierName'] = $MyRow['suppname'];
		$_POST['CurrCode'] = $MyRow['currcode'];
		$_POST['CurrDecimalPlaces'] = $MyRow['decimalplaces'];
		$_POST['ExRate'] = $MyRow['rate'];
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['SuppDelAdd1'] = $MyRow['address1'];
		$_POST['SuppDelAdd2'] = $MyRow['address2'];
		$_POST['SuppDelAdd3'] = $MyRow['address3'];
		$_POST['SuppDelAdd4'] = $MyRow['address4'];
		$_POST['SuppDelAdd5'] = $MyRow['address5'];
		$_POST['SuppDelAdd6'] = $MyRow['address6'];
		$_POST['SuppTel'] = $MyRow['telephone'];
		$_POST['Port'] = $MyRow['port'];
		$_POST['DeliveryBy'] = $MyRow['defaultshipper'];

		$_SESSION['PO' . $identifier]->SupplierID = $_POST['Select'];
		$_SESSION['RequireSupplierSelection'] = 0;
		$_SESSION['PO' . $identifier]->SupplierName = $_POST['SupplierName'];
		$_SESSION['PO' . $identifier]->CurrCode = $_POST['CurrCode'];
		$_SESSION['PO' . $identifier]->CurrDecimalPlaces = $_POST['CurrDecimalPlaces'];
		$_SESSION['PO' . $identifier]->ExRate = $_POST['ExRate'];
		$_SESSION['PO' . $identifier]->PaymentTerms = $_POST['PaymentTerms'];
		$_SESSION['PO' . $identifier]->SuppDelAdd1 = $_POST['SuppDelAdd1'];
		$_SESSION['PO' . $identifier]->SuppDelAdd2 = $_POST['SuppDelAdd2'];
		$_SESSION['PO' . $identifier]->SuppDelAdd3 = $_POST['SuppDelAdd3'];
		$_SESSION['PO' . $identifier]->SuppDelAdd4 = $_POST['SuppDelAdd4'];
		$_SESSION['PO' . $identifier]->SuppDelAdd5 = $_POST['SuppDelAdd5'];
		$_SESSION['PO' . $identifier]->SuppDelAdd6 = $_POST['SuppDelAdd6'];
		$_SESSION['PO' . $identifier]->SuppTel = $_POST['SuppTel'];
		$_SESSION['PO' . $identifier]->Port = $_POST['Port'];
		$_SESSION['PO' . $identifier]->DeliveryBy = $_POST['DeliveryBy'];

	} else {

		prnMsg(__('You do not have the authority to raise Purchase Orders for') . ' ' . $MyRow['suppname'] . '. ' . __('Please Consult your system administrator for more information.') . '<br />' . __('You can setup authorisations') . ' ' . '<a href="' . $RootPath . '/PO_AuthorisationLevels.php">' . __('here') . '</a>', 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	// end of added for suppliers lookup fields

} /* isset($_POST['Select'])  will only be true if page called from supplier selection form or item purchasing data order link
 * or set because only one supplier record returned from a search
*/
else {
	$_POST['Select'] = $_SESSION['PO' . $identifier]->SupplierID;
	$SQL = "SELECT suppliers.suppname,
					suppliers.currcode,
					currencies.decimalplaces,
					suppliers.paymentterms,
					suppliers.address1,
					suppliers.address2,
					suppliers.address3,
					suppliers.address4,
					suppliers.address5,
					suppliers.address6,
					suppliers.telephone,
					suppliers.port,
					suppliers.defaultshipper
			FROM suppliers INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
			WHERE supplierid='" . $_POST['Select'] . "'";

	$ErrMsg = __('The supplier record of the supplier selected') . ': ' . $_POST['Select'] . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_array($Result);

	// added for suppliers lookup fields
	if (!isset($_SESSION['PO' . $identifier])) {
		$_POST['SupplierName'] = $MyRow['suppname'];
		$_POST['CurrCode'] = $MyRow['currcode'];
		$_POST['CurrDecimalPlaces'] = $MyRow['decimalplaces'];
		$_POST['ExRate'] = $MyRow['rate'];
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['SuppDelAdd1'] = $MyRow['address1'];
		$_POST['SuppDelAdd2'] = $MyRow['address2'];
		$_POST['SuppDelAdd3'] = $MyRow['address3'];
		$_POST['SuppDelAdd4'] = $MyRow['address4'];
		$_POST['SuppDelAdd5'] = $MyRow['address5'];
		$_POST['SuppDelAdd6'] = $MyRow['address6'];
		$_POST['SuppTel'] = $MyRow['telephone'];
		$_POST['Port'] = $MyRow['port'];
		$_POST['DeliveryBy'] = $MyRow['defaultshipper'];

		$_SESSION['PO' . $identifier]->SupplierID = $_POST['Select'];
		$_SESSION['RequireSupplierSelection'] = 0;
		$_SESSION['PO' . $identifier]->SupplierName = $_POST['SupplierName'];
		$_SESSION['PO' . $identifier]->CurrCode = $_POST['CurrCode'];
		$_SESSION['PO' . $identifier]->CurrDecimalPlaces = $_POST['CurrDecimalPlaces'];
		$_SESSION['PO' . $identifier]->ExRate = filter_number_format($_POST['ExRate']);
		$_SESSION['PO' . $identifier]->PaymentTerms = $_POST['PaymentTerms'];
		$_SESSION['PO' . $identifier]->SuppDelAdd1 = $_POST['SuppDelAdd1'];
		$_SESSION['PO' . $identifier]->SuppDelAdd2 = $_POST['SuppDelAdd2'];
		$_SESSION['PO' . $identifier]->SuppDelAdd3 = $_POST['SuppDelAdd3'];
		$_SESSION['PO' . $identifier]->SuppDelAdd4 = $_POST['SuppDelAdd4'];
		$_SESSION['PO' . $identifier]->SuppDelAdd5 = $_POST['SuppDelAdd5'];
		$_SESSION['PO' . $identifier]->SuppDelAdd6 = $_POST['SuppDelAdd6'];
		$_SESSION['PO' . $identifier]->SuppTel = $_POST['SuppTel'];
		$_SESSION['PO' . $identifier]->Port = $_POST['Port'];
		$_SESSION['PO' . $Identifier]->DeliveryBy = $_POST['DeliveryBy'];
		// end of added for suppliers lookup fields

	}
} // NOT isset($_POST['Select']) - not called with supplier selection so update variables
// part of step 1
if ($_SESSION['RequireSupplierSelection'] == 1 or !isset($_SESSION['PO' . $identifier]->SupplierID) or $_SESSION['PO' . $identifier]->SupplierID == '') {
	echo '<div class="db-card">
			<div class="db-card-title">
				<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> ' . __('Purchase Order: Select Supplier') . '</span>
			</div>
			<div class="db-card-body">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" id="choosesupplier">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
					if (isset($SuppliersReturned)) {
						echo '<input type="hidden" name="SuppliersReturned" value="' . $SuppliersReturned . '" />';
					}
					echo '<div class="db-grid db-grid-2">
						<div class="db-form-group">
							<label class="db-form-label">' . __('Search by Name') . ':</label>
							<input type="text" name="Keywords" class="db-form-input" placeholder="' . __('Enter keywords...') . '" autofocus="autofocus" size="20" maxlength="25" />
						</div>
						<div class="db-form-group">
							<label class="db-form-label">' . __('Search by Code') . ':</label>
							<input type="text" name="SuppCode" class="db-form-input" placeholder="' . __('Enter supplier code...') . '" size="15" maxlength="18" />
						</div>
					</div>
					<div class="db-form-actions" style="margin-top: var(--space-4);">
						<button type="submit" name="SearchSuppliers" class="db-btn db-btn-primary">' . __('Search Now') . '</button>
						<button type="submit" class="db-btn db-btn-secondary">' . __('Reset') . '</button>
					</div>
				</form>
			</div>
		</div>';

	if (isset($Result_SuppSelect)) {
		echo '<div class="db-card" style="margin-top: var(--space-4);">
				<div class="db-card-title">' . __('Search Results') . '</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Supplier Name') . '</th>
								<th>' . __('Address') . '</th>
								<th>' . __('Currency') . '</th>
							</tr>
						</thead>
						<tbody>';

		while ($MyRow = DB_fetch_array($Result_SuppSelect)) {
			echo '<tr>
					<td>
						<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
							<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
							<input type="submit" name="Select" class="db-btn db-btn-secondary db-btn-sm" value="' . $MyRow['supplierid'] . '" />
						</form>
					</td>
					<td class="db-font-bold">' . $MyRow['suppname'] . '</td>
					<td>';
			for ($i = 1; $i <= 6; $i++) {
				if ($MyRow['address' . $i] != '') {
					echo $MyRow['address' . $i] . '<br />';
				}
			}
			echo '</td>
					<td><span class="db-badge db-badge-info">' . $MyRow['currcode'] . '</span></td>
				</tr>';
		}
		echo '</tbody>
					</table>
				</div>
			</div>';
	}
} else {
	/* everything below here only do if a supplier is selected */

	echo '<form id="form1" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="db-card">
			<div class="db-card-title">
				<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg> ' . $_SESSION['PO' . $identifier]->SupplierName . '</span>
				<span class="db-badge db-badge-info">' . __('All amounts in') . ' ' . $_SESSION['PO' . $identifier]->CurrCode . '</span>
			</div>
			<div class="db-card-body">';

	if (isset($Purch_Item)) {
		/*This is set if the user hits the link from the supplier purchasing info shown on SelectProduct.php */
		prnMsg(__('Purchase Item(s) with this code') . ': ' . $Purch_Item, 'info');

		echo '<div class="centre">';
		echo '<table class="table_index">
				<tr>
					<td class="menu_group_item">';

		/* the link */
		echo '<a href="' . $RootPath . '/PO_Items.php?NewItem=' . $Purch_Item . '&identifier=' . $identifier . '">' . __('Enter Line Item to this purchase order') . '</a>';

		echo '</td>
			</tr>
			</table>
			</div>';

		$Qty = $_GET['Quantity'] ?? 1;

		$SQL = "SELECT stockmaster.controlled,
						stockmaster.serialised,
						stockmaster.description,
						stockmaster.units ,
						stockmaster.decimalplaces,
						b.price,
						b.suppliersuom,
						b.suppliers_partno,
						b.conversionfactor,
						b.leadtime,
						stockcategory.stockact
				FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
				LEFT JOIN (SELECT purchdata.price,purchdata.leadtime,purchdata.supplierno,purchdata.stockid,purchdata.suppliersuom,purchdata.suppliers_partno,purchdata.conversionfactor,purchdata.effectivefrom FROM purchdata INNER JOIN (SELECT max(a.effectivefrom) as eff,a.supplierno,a.stockid from purchdata a   GROUP BY a.stockid,a.supplierno) as c ON purchdata.supplierno=c.supplierno AND purchdata.stockid=c.stockid AND purchdata.effectivefrom=c.eff)  as b

					ON stockmaster.stockid = b.stockid
					AND b.effectivefrom <= CURRENT_DATE
				WHERE stockmaster.stockid='" . $Purch_Item . "'
				AND b.supplierno ='" . $_GET['SelectedSupplier'] . "'";
		$Result = DB_query($SQL);
		$PurchItemRow = DB_fetch_array($Result);

		if (!isset($PurchItemRow['conversionfactor'])) {
			$PurchItemRow['conversionfactor'] = 1;
		}

		if (!isset($PurchItemRow['leadtime'])) {
			$PurchItemRow['leadtime'] = 1;
		}
		$_SESSION['PO' . $identifier]->Version = '1.0';
		$_SESSION['PO' . $identifier]->add_to_order(1,
													$Purch_Item,
													$PurchItemRow['serialised'],
													$PurchItemRow['controlled'],
													$Qty * $PurchItemRow['conversionfactor'],
													$PurchItemRow['description'],
													$PurchItemRow['price'] / $PurchItemRow['conversionfactor'],
													$PurchItemRow['units'],
													$PurchItemRow['stockact'],
													$_SESSION['PO' . $identifier]->DeliveryDate,
													0,
													0,
													'',
													0,
													0,
													'',
													$PurchItemRow['decimalplaces'],
													$PurchItemRow['suppliersuom'],
													$PurchItemRow['conversionfactor'],
													$PurchItemRow['leadtime'],
													$PurchItemRow['suppliers_partno']);

		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/PO_Items.php?identifier=' . $identifier . '">';
	}

	/*Set up form for entry of order header stuff */

	if (!isset($_POST['LookupDeliveryAddress']) and (!isset($_POST['StkLocation']) or $_POST['StkLocation']) and (isset($_SESSION['PO' . $identifier]->Location) and $_SESSION['PO' . $identifier]->Location != '')) {
		/* The session variables are set but the form variables have
		 * been lost --
		 * need to restore the form variables from the session */
		 if (!isset($_SESSION['PO' . $identifier]->Initiator)) {
			 $_SESSION['PO' . $identifier]->Initiator = $_SESSION['UserID'];
		 }
		$_POST['StkLocation'] = $_SESSION['PO' . $identifier]->Location;
		$_POST['SupplierContact'] = $_SESSION['PO' . $identifier]->SupplierContact;
		$_POST['DelAdd1'] = $_SESSION['PO' . $identifier]->DelAdd1;
		$_POST['DelAdd2'] = $_SESSION['PO' . $identifier]->DelAdd2;
		$_POST['DelAdd3'] = $_SESSION['PO' . $identifier]->DelAdd3;
		$_POST['DelAdd4'] = $_SESSION['PO' . $identifier]->DelAdd4;
		$_POST['DelAdd5'] = $_SESSION['PO' . $identifier]->DelAdd5;
		$_POST['DelAdd6'] = $_SESSION['PO' . $identifier]->DelAdd6;
		$_POST['Initiator'] = $_SESSION['PO' . $identifier]->Initiator;
		$_POST['Requisition'] = $_SESSION['PO' . $identifier]->RequisitionNo;
		$_POST['Version'] = $_SESSION['PO' . $identifier]->Version;
		$_POST['DeliveryDate'] = $_SESSION['PO' . $identifier]->DeliveryDate;
		$_POST['Revised'] = $_SESSION['PO' . $identifier]->Revised;
		$_POST['ExRate'] = $_SESSION['PO' . $identifier]->ExRate;
		$_POST['Comments'] = $_SESSION['PO' . $identifier]->Comments;
		$_POST['DeliveryBy'] = $_SESSION['PO' . $identifier]->DeliveryBy;
		$_POST['PaymentTerms'] = $_SESSION['PO' . $identifier]->PaymentTerms;
		$SQL = "SELECT realname FROM www_users WHERE userid='" . $_POST['Initiator'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_POST['InitiatorName'] = $MyRow['realname'];
	}

	// Order Header Details Grid
	echo '<div class="db-grid db-grid-3">
			<div class="db-card db-card-sub">
				<div class="db-card-title">' . __('Order Initiation') . '</div>
				<div class="db-card-body">';

	//Purchase Order Date
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('PO Date') . ':</label>
			<div class="db-form-text">';
	if ($_SESSION['ExistingOrder'] != 0) {
		echo ConvertSQLDate($_SESSION['PO' . $identifier]->Orig_OrderDate);
	} else {
		echo date($_SESSION['DefaultDateFormat']);
	}
	echo '</div>
		</div>';

	//Version number for this PO
	if (isset($_GET['ModifyOrderNumber']) and $_GET['ModifyOrderNumber'] != '') {
		$_SESSION['PO' . $identifier]->Version+= 1;
		$_POST['Version'] = $_SESSION['PO' . $identifier]->Version;
	} elseif (isset($_SESSION['PO' . $identifier]->Version) and $_SESSION['PO' . $identifier]->Version != '') {
		$_POST['Version'] = $_SESSION['PO' . $identifier]->Version;
	} else {
		$_POST['Version'] = '1';
	}
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Version') . ' #:</label>
			<input type="hidden" name="Version" value="' . $_POST['Version'] . '" />
			<div class="db-form-text">' . $_POST['Version'] . '</div>
		</div>';

	//Revision date for this PO
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Revised') . ':</label>
			<input type="hidden" name="Revised" value="' . date($_SESSION['DefaultDateFormat']) . '" />
			<div class="db-form-text">' . date($_SESSION['DefaultDateFormat']) . '</div>
		</div>';

	//Delivery Date for this PO
	if (!isset($_POST['DeliveryDate'])) {
		$_POST['DeliveryDate'] = date($_SESSION['DefaultDateFormat']);
	}
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Delivery Date') . ':</label>
			<input required="required" autofocus="autofocus" type="date" name="DeliveryDate" class="db-form-input" value="' . FormatDateForSQL($_POST['DeliveryDate']) . '" />
		</div>';

	// Initiator name
	if (!isset($_POST['Initiator'])) {
		$_POST['Initiator'] = $_SESSION['UserID'];
		$_POST['InitiatorName'] = $_SESSION['UsersRealName'];
		$_POST['Requisition'] = '';
	}
	if (!isset($_POST['InitiatorName'])) {
		$_POST['InitiatorName'] = $_SESSION['UsersRealName'];
	}
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Initiated By') . ':</label>
			<input type="hidden" name="Initiator" value="' . $_POST['Initiator'] . '" />
			<div class="db-form-text">' . $_POST['InitiatorName'] . '</div>
		</div>';

	//Requisition Reference
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Requisition Ref') . ':</label>
			<input type="text" name="Requisition" class="db-form-input" maxlength="15" value="' . $_POST['Requisition'] . '" />
		</div>';

	//Order Printed Date
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Date Printed') . ':</label>';
	if (isset($_SESSION['PO' . $identifier]->DatePurchaseOrderPrinted) and mb_strlen($_SESSION['PO' . $identifier]->DatePurchaseOrderPrinted) > 6) {
		echo '<div class="db-form-text">' . ConvertSQLDate($_SESSION['PO' . $identifier]->DatePurchaseOrderPrinted) . '</div>';
		$Printed = true;
	} else {
		$Printed = false;
		echo '<div class="db-form-text text-muted">' . __('Not yet printed') . '</div>';
	}
	echo '</div>
		</div> <!-- End Initiation Card Body -->
	</div> <!-- End Initiation Card -->';

	//Allow order reprint
	//Allow order reprint
	if (isset($_POST['AllowRePrint'])) {
		$SQL = "UPDATE purchorders SET allowprint=1 WHERE orderno='" . $_SESSION['PO' . $identifier]->OrderNo . "'";
		$Result = DB_query($SQL);
	}
	if ($_SESSION['PO' . $identifier]->AllowPrintPO == 0 and empty($_POST['RePrint'])) {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Allow Reprint') . ':</label>
				<div class="db-input-group">
					<select name="RePrint" class="db-form-select">
						<option selected="selected" value="0">' . __('No') . '</option>
						<option value="1">' . __('Yes') . '</option>
					</select>
					<button type="submit" name="AllowRePrint" class="db-btn db-btn-secondary db-btn-sm">' . __('Update') . '</button>
				</div>
			</div>';
	} elseif ($Printed) {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Allow Reprint') . ':</label>
				<div class="db-form-text">
					<a target="_blank" href="' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=' . $_SESSION['ExistingOrder'] . '&amp;identifier=' . $identifier . '" class="db-link-primary" style="font-weight: 600;">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 4px; vertical-align: middle;"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
						' . __('Reprint Now') . '
					</a>
				</div>
			</div>';
	} //$Printed

	// Order Status Card
	echo '<div class="db-card db-card-sub">
			<div class="db-card-title">' . __('Order Status') . '</div>
			<div class="db-card-body">';

	if ($_SESSION['ExistingOrder'] != 0 and $_SESSION['PO' . $identifier]->Status == 'Printed') {
		echo '<div class="db-form-group">
				<a href="' . $RootPath . '/GoodsReceived.php?PONumber=' . $_SESSION['PO' . $identifier]->OrderNo . '&amp;identifier=' . $identifier . '" class="db-btn db-btn-info db-btn-sm">' . __('Receive this order') . '</a>
			</div>';
	}

	if ($_SESSION['PO' . $identifier]->Status == '') {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Order Status') . ':</label>
				<input type="hidden" name="Status" value="NewOrder" />
				<div class="db-form-text"><span class="db-badge db-badge-info">' . __('New Purchase Order') . '</span></div>
			</div>';
	} else {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Status') . ':</label>
				<select name="Status" class="db-form-select" onchange="ReloadForm(form1.UpdateStatus)">';
				switch ($_SESSION['PO' . $identifier]->Status) {
					case 'Pending':
						echo '<option selected="selected" value="Pending">' . __('Pending') . '</option>
								<option value="Authorised">' . __('Authorised') . '</option>
								<option value="Rejected">' . __('Rejected') . '</option>';
					break;
					case 'Authorised':
						echo '<option value="Pending">' . __('Pending') . '</option>
								<option selected="selected" value="Authorised">' . __('Authorised') . '</option>
								<option value="Cancelled">' . __('Cancelled') . '</option>';
					break;
					case 'Printed':
						echo '<option value="Pending">' . __('Pending') . '</option>
								<option selected="selected" value="Printed">' . __('Printed') . '</option>
								<option value="Cancelled">' . __('Cancelled') . '</option>
								<option value="Completed">' . __('Completed') . '</option>';
					break;
					case 'Completed':
						echo '<option selected="selected" value="Completed">' . __('Completed') . '</option>';
					break;
					case 'Rejected':
						echo '<option selected="selected" value="Rejected">' . __('Rejected') . '</option>
								<option value="Pending">' . __('Pending') . '</option>
								<option value="Authorised">' . __('Authorised') . '</option>';
					break;
					case 'Cancelled':
						echo '<option selected="selected" value="Cancelled">' . __('Cancelled') . '</option>
								<option value="Authorised">' . __('Authorised') . '</option>
								<option value="Pending">' . __('Pending') . '</option>';
					break;
				}
		echo '  </select>
			</div>';

		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Status Comment') . ':</label>
				<input type="text" name="StatusComments" class="db-form-input" placeholder="' . __('Add a comment...') . '" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Status History') . ':</label>
				<div class="db-form-text db-history-text" style="max-height: 100px; overflow-y: auto; font-size: 0.85rem; padding: 8px; background: var(--bg-workspace); border-radius: 4px;">' . html_entity_decode($_SESSION['PO' . $identifier]->StatusComments, ENT_QUOTES, 'UTF-8') . '</div>
			</div>
			<input type="hidden" name="StatusCommentsComplete" value="' . htmlspecialchars($_SESSION['PO' . $identifier]->StatusComments, ENT_QUOTES, 'UTF-8') . '" />
			<div class="db-form-group" style="margin-top: 10px;">
				<button type="submit" name="UpdateStatus" class="db-btn db-btn-secondary db-btn-sm">' . __('Status Update') . '</button>
			</div>';
	}
	echo '  </div> <!-- End Status Card Body -->
		</div> <!-- End Status Card -->';

	// Warehouse info fieldset refactored
	echo '</div> <!-- End Column 1 Grid Row (Initiation + Status) -->';
	
	echo '<div class="db-grid db-grid-3"> <!-- Next Grid Row -->
			<div class="db-card db-card-sub">
				<div class="db-card-title">' . __('Warehouse Info') . '</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Warehouse') . ':</label>
						<div class="db-input-group">
							<select required="required" name="StkLocation" class="db-form-select" onchange="ReloadForm(form1.LookupDeliveryAddress)">';
							$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1";
							$LocnResult = DB_query($SQL);
							while ($LocnRow = DB_fetch_array($LocnResult)) {
								if (isset($_POST['StkLocation']) and ($_POST['StkLocation'] == $LocnRow['loccode']) or (empty($_POST['StkLocation']) and $LocnRow['loccode'] == $_SESSION['UserStockLocation'])) {
									echo '<option selected="selected" value="' . $LocnRow['loccode'] . '">' . $LocnRow['locationname'] . '</option>';
								} else {
									echo '<option value="' . $LocnRow['loccode'] . '">' . $LocnRow['locationname'] . '</option>';
								}
							}
							echo '</select>
							<button type="submit" name="LookupDeliveryAddress" class="db-btn db-btn-secondary db-btn-sm">' . __('Select') . '</button>
						</div>
					</div>';

	// Warehouse Address Details
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Delivery Contact') . ':</label>
			<input type="text" name="Contact" class="db-form-input" value="' . $_SESSION['PO' . $identifier]->Contact . '" />
		</div>';

	for ($i = 1; $i <= 6; $i++) {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Address') . ' ' . $i . ':</label>
				<input type="text" name="DelAdd' . $i . '" class="db-form-input" value="' . $_POST['DelAdd' . $i] . '" />
			</div>';
	}

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Phone') . ':</label>
			<input type="tel" name="Tel" class="db-form-input" value="' . $_SESSION['PO' . $identifier]->Tel . '" />
		</div>
		<div class="db-form-group">
			<label class="db-form-label">' . __('Delivery By') . ':</label>
			<select name="DeliveryBy" class="db-form-select">';
			$ShipperResult = DB_query("SELECT shipper_id, shippername FROM shippers ORDER BY shippername");
			while ($ShipperRow = DB_fetch_array($ShipperResult)) {
				if (isset($_POST['DeliveryBy']) and ($_POST['DeliveryBy'] == $ShipperRow['shipper_id'])) {
					echo '<option selected="selected" value="' . $ShipperRow['shipper_id'] . '">' . $ShipperRow['shippername'] . '</option>';
				} else {
					echo '<option value="' . $ShipperRow['shipper_id'] . '">' . $ShipperRow['shippername'] . '</option>';
				}
			}
	echo '  </select>
		</div>
				</div> <!-- End Warehouse Card Body -->
			</div> <!-- End Warehouse Card -->';

	// Supplier info card
	echo '	<div class="db-card db-card-sub">
				<div class="db-card-title">' . __('Supplier Info') . '</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Supplier') . ':</label>
						<div class="db-input-group">
							<select name="Keywords" class="db-form-select" onchange="ReloadForm(form1.SearchSuppliers)">';
							$SuppCoResult = DB_query("SELECT supplierid, suppname FROM suppliers ORDER BY suppname");
							while ($SuppCoRow = DB_fetch_array($SuppCoResult)) {
								if ($SuppCoRow['suppname'] == $_SESSION['PO' . $identifier]->SupplierName) {
									echo '<option selected="selected" value="' . $SuppCoRow['suppname'] . '">' . $SuppCoRow['suppname'] . '</option>';
								} else {
									echo '<option value="' . $SuppCoRow['suppname'] . '">' . $SuppCoRow['suppname'] . '</option>';
								}
							}
							echo '  </select>
							<button type="submit" name="SearchSuppliers" class="db-btn db-btn-secondary db-btn-sm">' . __('Select') . '</button>
						</div>
					</div>';

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Supplier Contact') . ':</label>
			<select name="SupplierContact" class="db-form-select">';
			$SQL = "SELECT contact FROM suppliercontacts WHERE supplierid='" . $_POST['Select'] . "'";
			$SuppCoResult = DB_query($SQL);
			while ($SuppCoRow = DB_fetch_array($SuppCoResult)) {
				if ($_POST['SupplierContact'] == $SuppCoRow['contact'] or ($_POST['SupplierContact'] == '' and $SuppCoRow['contact'] == $_SESSION['PO' . $identifier]->SupplierContact)) {
					echo '<option selected="selected" value="' . $SuppCoRow['contact'] . '">' . $SuppCoRow['contact'] . '</option>';
				} else {
					echo '<option value="' . $SuppCoRow['contact'] . '">' . $SuppCoRow['contact'] . '</option>';
				}
			}
	echo '  </select>
		</div>';

	for ($i = 1; $i <= 6; $i++) {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Address') . ' ' . $i . ':</label>
				<input type="text" name="SuppDelAdd' . $i . '" class="db-form-input" value="' . $_POST['SuppDelAdd' . $i] . '" />
			</div>';
	}

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Phone') . ':</label>
			<input type="tel" name="SuppTel" class="db-form-input" value="' . $_SESSION['PO' . $identifier]->SuppTel . '" />
		</div>
		<div class="db-form-group">
			<label class="db-form-label">' . __('Payment Terms') . ':</label>
			<select name="PaymentTerms" class="db-form-select">';
			$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
			while ($MyRow = DB_fetch_array($Result)) {
				if ($MyRow['termsindicator'] == $_SESSION['PO' . $identifier]->PaymentTerms) {
					echo '<option selected="selected" value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
				} else {
					echo '<option value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
				}
			}
	echo '  </select>
		</div>
		<div class="db-form-group">
			<label class="db-form-label">' . __('Delivery To (Port)') . ':</label>
			<input type="text" name="Port" class="db-form-input" value="' . $_POST['Port'] . '" />
		</div>';

	if ($_SESSION['PO' . $identifier]->CurrCode != $_SESSION['CompanyRecord']['currencydefault']) {
		echo '<div class="db-form-group">
				<label class="db-form-label">' . __('Exchange Rate') . ':</label>
				<input type="text" name="ExRate" class="db-form-input text-right" value="' . locale_number_format($_POST['ExRate'], 5) . '" />
			</div>';
	} else {
		echo '<input type="hidden" name="ExRate" value="1" />';
	}
	
	echo '    </div> <!-- End Supplier Card Body -->
			</div> <!-- End Supplier Card -->';

	// Comments card
	echo '	<div class="db-card db-card-sub">
				<div class="db-card-title">' . __('Order Comments') . '</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<textarea name="Comments" class="db-form-input" style="min-height: 120px;" placeholder="' . __('Enter order comments here...') . '">' . stripcslashes($_POST['Comments']) . '</textarea>
					</div>
				</div>
			</div>
		</div> <!-- End Bottom Grid Row -->';
	
	echo '</div> <!-- End Main Form Body (db-card-body) -->
		  <div class="db-card-footer db-form-actions">
			<button type="submit" name="EnterLines" class="db-btn db-btn-primary">' . __('Enter Line Items') . '</button>
		  </div>
		</div> <!-- End Main Order Card (db-card) -->';

}
/*end of if supplier selected */

echo '</div> <!-- End db-page -->';
/*end of if supplier selected */

echo '</form>';
include(__DIR__ . '/includes/footer.php');
