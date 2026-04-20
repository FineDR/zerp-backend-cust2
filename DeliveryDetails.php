<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
/* Used during order entry to allow the entry of delivery addresses other than the defaulted branch delivery address and information about carrier/shipping method etc. */

/*
This is where the delivery details are confirmed/entered/modified and the order committed to the database once the place order/modify order button is hit.
*/

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Order Delivery Details');// Screen identification.
$ViewTopic = 'SalesOrders';// Filename's id in ManualContents.php's TOC.
$BookMark = 'DeliveryDetails';// Anchor's id in the manual's html document.
include(__DIR__ . '/includes/header.php');

/* Redundant date conversion removed to prevent double-formatting issues with modern inputs */

include(__DIR__ . '/includes/FreightCalculation.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/CountriesArray.php');

if (isset($_POST['identifier'])) {
	$identifier = $_POST['identifier'];
} elseif (isset($_GET['identifier'])) {
	$identifier = $_GET['identifier'];
} else {
	$identifier = date('U');
}

unset($_SESSION['WarnOnce']);
if (!isset($_SESSION['Items'.$identifier]) OR !isset($_SESSION['Items'.$identifier]->DebtorNo)) {
	prnMsg(__('This page can only be read if an order has been entered') . '. ' . __('To enter an order select customer transactions then sales order entry'),'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if ($_SESSION['Items'.$identifier]->ItemsOrdered == 0) {
	prnMsg(__('This page can only be read if an there are items on the order') . '. ' . __('To enter an order select customer transactions then sales order entry'),'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

/*Calculate the earliest dispacth date in DateFunctions.php */

$EarliestDispatch = CalcEarliestDispatchDate();

if (isset($_POST['ProcessOrder']) OR isset($_POST['MakeRecurringOrder'])) {

	/*need to check for input errors in any case before order processed */
	$_POST['Update']='Yes rerun the validation checks';//no need for gettext!

	/*store the old freight cost before it is recalculated to ensure that there has been no change - test for change after freight recalculated and get user to re-confirm if changed */

	$OldFreightCost = round($_POST['FreightCost'],2);

}

if (isset($_POST['Update'])
	OR isset($_POST['BackToLineDetails'])
	OR isset($_POST['MakeRecurringOrder'])) {

	$InputErrors =0;
	if (mb_strlen($_POST['DeliverTo'])<=1) {
		$InputErrors =1;
		prnMsg(__('You must enter the person or company to whom delivery should be made'),'error');
	}
	if (mb_strlen($_POST['BrAdd1'])<=1) {
		$InputErrors =1;
		prnMsg(__('You should enter the street address in the box provided') . '. ' . __('Orders cannot be accepted without a valid street address'),'error');
	}
//	if (mb_strpos($_POST['BrAdd1'],__('Box'))>0) {
//		prnMsg(__('You have entered the word') . ' "' . __('Box') . '" ' . __('in the street address') . '. ' . __('Items cannot be delivered to') . ' ' .__('box') . ' ' . __('addresses'),'warn');
//	}
	if (!is_numeric($_POST['FreightCost'])) {
		$InputErrors =1;
		prnMsg( __('The freight cost entered is expected to be numeric'),'error');
	}
	if (isset($_POST['MakeRecurringOrder']) AND $_POST['Quotation']==1) {
		$InputErrors =1;
		prnMsg( __('A recurring order cannot be made from a quotation'),'error');
	}
	if (($_POST['DeliverBlind'])<=0) {
		$InputErrors =1;
		prnMsg(__('You must select the type of packlist to print'),'error');
	}

/*	if (mb_strlen($_POST['BrAdd3'])==0 OR !isset($_POST['BrAdd3'])) {
		$InputErrors =1;
		echo "<br />A region or city must be entered.<br />";
	}

	Maybe appropriate in some installations but not here
	if (mb_strlen($_POST['BrAdd2'])<=1) {
		$InputErrors =1;
		echo "<br />You should enter the suburb in the box provided. Orders cannot be accepted without a valid suburb being entered.<br />";
	}

*/
// Check the date is OK
	if (isset($_POST['DeliveryDate']) and !Is_Date($_POST['DeliveryDate'])) {
		$InputErrors =1;
		prnMsg(__('An invalid date entry was made') . '. ' . __('The date entry must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
	}
// Check the date is OK
	if (isset($_POST['QuoteDate']) and !Is_Date($_POST['QuoteDate'])) {
		$InputErrors =1;
		prnMsg(__('An invalid date entry was made') . '. ' . __('The date entry must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
	}
// Check the date is OK
	if (isset($_POST['ConfirmedDate']) and !Is_Date($_POST['ConfirmedDate'])) {
		$InputErrors =1;
		 prnMsg(__('An invalid date entry was made') . '. ' . __('The date entry must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
	}

	 /* This check is not appropriate where orders need to be entered in retrospectively in some cases this check will be appropriate and this should be uncommented

	 elseif (Date1GreaterThanDate2(date($_SESSION['DefaultDateFormat'],$EarliestDispatch), $_POST['DeliveryDate'])) {
		$InputErrors =1;
		echo '<br /><b>' . __('The delivery details cannot be updated because you are attempting to set the date the order is to be dispatched earlier than is possible. No dispatches are made on Saturday and Sunday. Also, the dispatch cut off time is') . $_SESSION['DispatchCutOffTime'] . __(':00 hrs. Orders placed after this time will be dispatched the following working day.');
	}

	*/

	if ($InputErrors==0) {

		if ($_SESSION['DoFreightCalc']==true) {
			list ($_POST['FreightCost'], $BestShipper) = CalcFreightCost($_SESSION['Items'.$identifier]->total,
																		$_POST['BrAdd2'],
																		$_POST['BrAdd3'],
																		$_POST['BrAdd4'],
																		$_POST['BrAdd5'],
																		$_POST['BrAdd6'],
																		$_SESSION['Items'.$identifier]->totalVolume,
																		$_SESSION['Items'.$identifier]->totalWeight,
																		$_SESSION['Items'.$identifier]->Location,
																		$_SESSION['Items'.$identifier]->DefaultCurrency);
			if ( !empty($BestShipper) ) {
				$_POST['FreightCost'] = round($_POST['FreightCost'],2);
				$_POST['ShipVia'] = $BestShipper;
			} else {
				prnMsg(__($_POST['FreightCost']),'warn');
			}
		}
		$SQL = "SELECT custbranch.brname,
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
					custbranch.salesman
				FROM custbranch
				WHERE custbranch.branchcode='" . $_SESSION['Items'.$identifier]->Branch . "'
				AND custbranch.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

		$ErrMsg = __('The customer branch record of the customer selected') . ': ' . $_SESSION['Items'.$identifier]->CustomerName . ' ' . __('cannot be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		if (DB_num_rows($Result)==0) {

			prnMsg(__('The branch details for branch code') . ': ' . $_SESSION['Items'.$identifier]->Branch . ' ' . __('against customer code') . ': ' . $_POST['Select'] . ' ' . __('could not be retrieved') . '. ' . __('Check the set up of the customer and branch'),'error');

			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		if (!isset($_POST['SpecialInstructions'])) {
			$_POST['SpecialInstructions']='';
		}
		if (!isset($_POST['DeliveryDays'])) {
			$_POST['DeliveryDays']=0;
		}
		if (!isset($_SESSION['Items'.$identifier])) {
			$MyRow = DB_fetch_row($Result);
			$_SESSION['Items'.$identifier]->DeliverTo = $MyRow[0];
			$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow[1];
			$_SESSION['Items'.$identifier]->DelAdd2 = $MyRow[2];
			$_SESSION['Items'.$identifier]->DelAdd3 = $MyRow[3];
			$_SESSION['Items'.$identifier]->DelAdd4 = $MyRow[4];
			$_SESSION['Items'.$identifier]->DelAdd5 = $MyRow[5];
			$_SESSION['Items'.$identifier]->DelAdd6 = $MyRow[6];
			$_SESSION['Items'.$identifier]->PhoneNo = $MyRow[7];
			$_SESSION['Items'.$identifier]->Email = $MyRow[8];
			$_SESSION['Items'.$identifier]->Location = $MyRow[9];
			$_SESSION['Items'.$identifier]->ShipVia = $MyRow[10];
			$_SESSION['Items'.$identifier]->DeliverBlind = $MyRow[11];
			$_SESSION['Items'.$identifier]->SpecialInstructions = $MyRow[12];
			$_SESSION['Items'.$identifier]->DeliveryDays = $MyRow[13];
			$_SESSION['Items'.$identifier]->SalesPerson = $MyRow[14];
			$_SESSION['Items'.$identifier]->DeliveryDate = $_POST['DeliveryDate'];
			$_SESSION['Items'.$identifier]->QuoteDate = $_POST['QuoteDate'];
			$_SESSION['Items'.$identifier]->ConfirmedDate = $_POST['ConfirmedDate'];
			$_SESSION['Items'.$identifier]->CustRef = $_POST['CustRef'];
			$_SESSION['Items'.$identifier]->Comments = $_POST['Comments'];
			$_SESSION['Items'.$identifier]->FreightCost = round($_POST['FreightCost'],2);
			$_SESSION['Items'.$identifier]->Quotation = $_POST['Quotation'];
		} else {
			$_SESSION['Items'.$identifier]->DeliverTo = $_POST['DeliverTo'];
			$_SESSION['Items'.$identifier]->DelAdd1 = $_POST['BrAdd1'];
			$_SESSION['Items'.$identifier]->DelAdd2 = $_POST['BrAdd2'];
			$_SESSION['Items'.$identifier]->DelAdd3 = $_POST['BrAdd3'];
			$_SESSION['Items'.$identifier]->DelAdd4 = $_POST['BrAdd4'];
			$_SESSION['Items'.$identifier]->DelAdd5 = $_POST['BrAdd5'];
			$_SESSION['Items'.$identifier]->DelAdd6 = $_POST['BrAdd6'];
			$_SESSION['Items'.$identifier]->PhoneNo = $_POST['PhoneNo'];
			$_SESSION['Items'.$identifier]->Email = $_POST['Email'];
			$_SESSION['Items'.$identifier]->Location = $_POST['Location'];
			$_SESSION['Items'.$identifier]->ShipVia = $_POST['ShipVia'];
			$_SESSION['Items'.$identifier]->DeliverBlind = $_POST['DeliverBlind'];
			$_SESSION['Items'.$identifier]->SpecialInstructions = $_POST['SpecialInstructions'];
			$_SESSION['Items'.$identifier]->DeliveryDays = $_POST['DeliveryDays'];
			$_SESSION['Items'.$identifier]->DeliveryDate = $_POST['DeliveryDate'];
			$_SESSION['Items'.$identifier]->QuoteDate = $_POST['QuoteDate'];
			$_SESSION['Items'.$identifier]->ConfirmedDate = $_POST['ConfirmedDate'];
			$_SESSION['Items'.$identifier]->CustRef = $_POST['CustRef'];
			$_SESSION['Items'.$identifier]->Comments = $_POST['Comments'];
			$_SESSION['Items'.$identifier]->SalesPerson = $_POST['SalesPerson'];
			$_SESSION['Items'.$identifier]->FreightCost = round(floatval($_POST['FreightCost']),2);
			$_SESSION['Items'.$identifier]->Quotation = $_POST['Quotation'];
		}
		/*$_SESSION['DoFreightCalc'] is a setting in the config.php file that the user can set to false to turn off freight calculations if necessary */


		/* What to do if the shipper is not calculated using the system
		- first check that the default shipper defined in config.php is in the database
		if so use this
		- then check to see if any shippers are defined at all if not report the error
		and show a link to set them up
		- if shippers defined but the default shipper is bogus then use the first shipper defined
		*/
		if ((isset($BestShipper) AND $BestShipper=='') AND ($_POST['ShipVia']=='' OR !isset($_POST['ShipVia']))) {
			$SQL = "SELECT shipper_id
						FROM shippers
						WHERE shipper_id='" . $_SESSION['Default_Shipper']."'";
			$ErrMsg = __('There was a problem testing for the default shipper');
			$TestShipperExists = DB_query($SQL, $ErrMsg);

			if (DB_num_rows($TestShipperExists)==1) {

				$BestShipper = $_SESSION['Default_Shipper'];

			} else {

				$SQL = "SELECT shipper_id
							FROM shippers";
				$TestShipperExists = DB_query($SQL, $ErrMsg);

				if (DB_num_rows($TestShipperExists)>=1) {
					$ShipperReturned = DB_fetch_row($TestShipperExists);
					$BestShipper = $ShipperReturned[0];
				} else {
					prnMsg(__('We have a problem') . ' - ' . __('there are no shippers defined'). '. ' . __('Please use the link below to set up shipping or freight companies') . ', ' . __('the system expects the shipping company to be selected or a default freight company to be used'),'error');
					echo '<a href="' . $RootPath . 'Shippers.php">' . __('Enter') . '/' . __('Amend Freight Companies') . '</a>';
				}
			}
			if (isset($_SESSION['Items'.$identifier]->ShipVia) AND $_SESSION['Items'.$identifier]->ShipVia!='') {
				$_POST['ShipVia'] = $_SESSION['Items'.$identifier]->ShipVia;
			} else {
				$_POST['ShipVia']=$BestShipper;
			}
		}
	}
}

if (isset($_POST['MakeRecurringOrder']) AND ! $InputErrors) {

	echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/RecurringSalesOrders.php?identifier='.$identifier  . '&amp;NewRecurringOrder=Yes">';
	prnMsg(__('You should automatically be forwarded to the entry of recurring order details page') . '. ' . __('If this does not happen') . '(' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/RecurringOrders.php?identifier='.$identifier . '&amp;NewRecurringOrder=Yes">' . __('click here') . '</a> '. __('to continue'),'info');
	include(__DIR__ . '/includes/footer.php');
	exit();
}


if (isset($_POST['BackToLineDetails']) and $_POST['BackToLineDetails']==__('Modify Order Lines')) {

	echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SelectOrderItems.php?identifier='.$identifier  . '">';
	prnMsg(__('You should automatically be forwarded to the entry of the order line details page') . '. ' . __('If this does not happen') . '(' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SelectOrderItems.php?identifier='.$identifier . '">' . __('click here') . '</a> '. __('to continue'),'info');
	include(__DIR__ . '/includes/footer.php');
	exit();

}

if (isset($_POST['ProcessOrder'])) {
	/*Default OK_to_PROCESS to 1 change to 0 later if hit a snag */
	if ($InputErrors ==0) {
		$OK_to_PROCESS = 1;
	}
	if ($_POST['FreightCost'] != $OldFreightCost AND $_SESSION['DoFreightCalc']==true) {
		$OK_to_PROCESS = 0;
		prnMsg(__('The freight charge has been updated') . '. ' . __('Please reconfirm that the order and the freight charges are acceptable and then confirm the order again if OK') .' <br /> '. __('The new freight cost is') .' ' . $_POST['FreightCost'] . ' ' . __('and the previously calculated freight cost was') .' '. $OldFreightCost,'warn');
	} else {

/*check the customer's payment terms */
		$SQL = "SELECT daysbeforedue,
				dayinfollowingmonth
			FROM debtorsmaster,
				paymentterms
			WHERE debtorsmaster.paymentterms=paymentterms.termsindicator
			AND debtorsmaster.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

		$ErrMsg = __('The customer terms cannot be determined') . '. ' . __('This order cannot be processed because');
		$TermsResult = DB_query($SQL, $ErrMsg);

		$MyRow = DB_fetch_array($TermsResult);
		if ($MyRow['daysbeforedue']==0 AND $MyRow['dayinfollowingmonth']==0) {

/* THIS IS A CASH SALE NEED TO GO OFF TO 3RD PARTY SITE SENDING MERCHANT ACCOUNT DETAILS AND CHECK FOR APPROVAL FROM 3RD PARTY SITE BEFORE CONTINUING TO PROCESS THE ORDER

UNTIL ONLINE CREDIT CARD PROCESSING IS PERFORMED ASSUME OK TO PROCESS

		NOT YET CODED   */

			$OK_to_PROCESS =1;


		} #end if cash sale detected

	} #end if else freight charge not altered
} #end if process order

if (isset($OK_to_PROCESS) AND $OK_to_PROCESS == 1 AND $_SESSION['ExistingOrder'.$identifier]==0) {

/* finally write the order header to the database and then the order line details */

	$DelDate = FormatDateforSQL($_SESSION['Items'.$identifier]->DeliveryDate);
	$QuotDate = FormatDateforSQL($_SESSION['Items'.$identifier]->QuoteDate);
	$ConfDate = FormatDateforSQL($_SESSION['Items'.$identifier]->ConfirmedDate);

	DB_Txn_Begin();

	$OrderNo = GetNextTransNo(30);

	$HeaderSQL = "INSERT INTO salesorders (
								orderno,
								debtorno,
								branchcode,
								customerref,
								comments,
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
								salesperson,
								freightcost,
								fromstkloc,
								deliverydate,
								quotedate,
								confirmeddate,
								quotation,
								deliverblind)
							VALUES (
								'". $OrderNo . "',
								'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
								'" . $_SESSION['Items'.$identifier]->Branch . "',
								'". DB_escape_string($_SESSION['Items'.$identifier]->CustRef) ."',
								'". DB_escape_string($_SESSION['Items'.$identifier]->Comments) ."',
								CURRENT_DATE,
								'" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
								'" . $_POST['ShipVia'] ."',
								'". DB_escape_string($_SESSION['Items'.$identifier]->DeliverTo) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd1) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd2) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd3) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd4) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd5) . "',
								'" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd6) . "',
								'" . $_SESSION['Items'.$identifier]->PhoneNo . "',
								'" . $_SESSION['Items'.$identifier]->Email . "',
								'" . $_SESSION['Items'.$identifier]->SalesPerson . "',
								'" . $_SESSION['Items'.$identifier]->FreightCost ."',
								'" . $_SESSION['Items'.$identifier]->Location ."',
								'" . $DelDate . "',
								'" . $QuotDate . "',
								'" . $ConfDate . "',
								'" . $_SESSION['Items'.$identifier]->Quotation . "',
								'" . $_SESSION['Items'.$identifier]->DeliverBlind ."'
								)";

	$ErrMsg = __('The order cannot be added because');
	$InsertQryResult = DB_query($HeaderSQL, $ErrMsg);

	$StartOf_LineItemsSQL = "INSERT INTO salesorderdetails (
											orderlineno,
											orderno,
											stkcode,
											unitprice,
											quantity,
											discountpercent,
											narrative,
											poline,
											itemdue)
										VALUES (";
	foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {

		$LineItemsSQL = $StartOf_LineItemsSQL ."
					'" . $StockItem->LineNumber . "',
					'" . $OrderNo . "',
					'" . $StockItem->StockID . "',
					'" . $StockItem->Price . "',
					'" . $StockItem->Quantity . "',
					'" . floatval($StockItem->DiscountPercent) . "',
					'" . DB_escape_string($StockItem->Narrative) . "',
					'" . $StockItem->POLine . "',
					'" . FormatDateForSQL($StockItem->ItemDue) . "'
				)";
	
		$ErrMsg = __('Unable to add the sales order line');
		$Ins_LineItemResult = DB_query($LineItemsSQL, $ErrMsg,'',true);

		/*Now check to see if the item is manufactured
		 * 			and AutoCreateWOs is on
		 * 			and it is a real order (not just a quotation)*/

		if ($StockItem->MBflag=='M'
			AND $_SESSION['AutoCreateWOs']==1
			AND $_SESSION['Items'.$identifier]->Quotation!=1) {//oh yeah its all on!

			echo '<br />';

			//now get the data required to test to see if we need to make a new WO
			$QOH = GetQuantityOnHand($StockItem->StockID, 'ALL');

			$QuantityDemand = GetDemand($StockItem->StockID, 'ALL');

			$QuantityOnOrder = GetQuantityOnOrder($StockItem->StockID, 'ALL');

			//Now we have the data - do we need to make any more?
			$ShortfallQuantity = $QOH-$QuantityDemand+$QuantityOnOrder;

			if ($ShortfallQuantity < 0) {//then we need to make a work order
				//How many should the work order be for??
				if ($ShortfallQuantity + $StockItem->EOQ < 0) {
					$WOQuantity = -$ShortfallQuantity;
				} else {
					$WOQuantity = $StockItem->EOQ;
				}

				$WONo = GetNextTransNo(40);
				$ErrMsg = __('Unable to insert a new work order for the sales order item');
				$InsWOResult = DB_query("INSERT INTO workorders (wo,
												 loccode,
												 requiredby,
												 startdate)
								 VALUES ('" . $WONo . "',
										'" . $_SESSION['DefaultFactoryLocation'] . "',
										CURRENT_DATE,
										'" . FormatDateForSQL($StockItem->ItemDue) . "')",
										$ErrMsg,
										'',
										true);
				//Need to get the latest BOM to roll up cost
				$CostResult = DB_query("SELECT SUM((actualcost)*bom.quantity) AS cost
													FROM stockmaster INNER JOIN bom
													ON stockmaster.stockid=bom.component
													WHERE bom.parent='" . $StockItem->StockID . "'
													AND bom.loccode='" . $_SESSION['DefaultFactoryLocation'] . "'");
				$CostRow = DB_fetch_row($CostResult);
				if (is_null($CostRow[0]) OR $CostRow[0]==0) {
					$Cost =0;
					prnMsg(__('In automatically creating a work order for') . ' ' . $StockItem->StockID . ' ' . __('an item on this sales order, the cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'),'warn');
				} else {
					$Cost = $CostRow[0];
				}

				// insert parent item info
				$SQL = "INSERT INTO woitems (wo,
											 stockid,
											 qtyreqd,
											 stdcost)
								 VALUES ( '" . $WONo . "',
										 '" . $StockItem->StockID . "',
										 '" . $WOQuantity . "',
										 '" . $Cost . "')";
				$ErrMsg = __('The work order item could not be added');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
				WoRealRequirements($WONo, $_SESSION['DefaultFactoryLocation'], $StockItem->StockID);

				$FactoryManagerEmail = __('A new work order has been created for') .
									":\n" . $StockItem->StockID . ' - ' . $StockItem->ItemDescription . ' x ' . $WOQuantity . ' ' . $StockItem->Units .
									"\n" . __('These are for') . ' ' . $_SESSION['Items'.$identifier]->CustomerName . ' ' . __('there order ref') . ': ' . $_SESSION['Items'.$identifier]->CustRef . ' ' .__('our order number') . ': ' . $OrderNo;

				if ($StockItem->Serialised AND $StockItem->NextSerialNo>0) {
						//then we must create the serial numbers for the new WO also
						$FactoryManagerEmail .= "\n" . __('The following serial numbers have been reserved for this work order') . ':';

						for ($i=0;$i<$WOQuantity;$i++) {

							$Result = DB_query("SELECT serialno FROM stockserialitems
												WHERE serialno='" . ($StockItem->NextSerialNo + $i) . "'
												AND stockid='" . $StockItem->StockID ."'");
							if (DB_num_rows($Result)!=0) {
								$WOQuantity++;
								prnMsg(($StockItem->NextSerialNo + $i) . ': ' . __('This automatically generated serial number already exists - it cannot be added to the work order'),'error');
							} else {
								$SQL = "INSERT INTO woserialnos (wo,
																stockid,
																serialno)
													VALUES ('" . $WONo . "',
															'" . $StockItem->StockID . "',
															'" . ($StockItem->NextSerialNo + $i) . "')";
								$ErrMsg = __('The serial number for the work order item could not be added');
								$Result = DB_query($SQL, $ErrMsg, '', true);
								$FactoryManagerEmail .= "\n" . ($StockItem->NextSerialNo + $i);
							}
						}//end loop around creation of woserialnos
						$NewNextSerialNo = ($StockItem->NextSerialNo + $WOQuantity +1);
						$ErrMsg = __('Could not update the new next serial number for the item');
						$UpdateNextSerialNoResult = DB_query("UPDATE stockmaster SET nextserialno='" . $NewNextSerialNo . "' WHERE stockid='" . $StockItem->StockID . "'", $ErrMsg, '', true);
				}// end if the item is serialised and nextserialno is set

				$EmailSubject = __('New Work Order Number') . ' ' . $WONo . ' ' . __('for') . ' ' . $StockItem->StockID . ' x ' . $WOQuantity;
				//Send email to the Factory Manager
				SendEmailFromWebERP($SysAdminEmail,
									$_SESSION['FactoryManagerEmail'],
									$EmailSubject,
									$FactoryManagerEmail,
									'',
									false);

			}//end if with this sales order there is a shortfall of stock - need to create the WO
		}//end if auto create WOs in on
	} /* end inserted line items into sales order details */

	 DB_Txn_Commit();
	 
	 // Modern Success Modal
	 echo '<div class="db-modal-overlay">
	 		<div class="db-modal">
				<div class="db-modal-header">
					<div class="db-success-icon">
						<i class="fas fa-check"></i>
					</div>
					<h2 class="db-modal-title">' . __('Order Placed successfully!') . '</h2>
					<p class="db-page-subtitle">' . __('Your ' . ($_SESSION['Items'.$identifier]->Quotation == 1 ? 'quotation' : 'order') . ' has been recorded.') . '</p>
				</div>
				
				<div class="db-modal-body">
					<div class="db-order-number-box">
						<span style="display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 5px;">' . ($_SESSION['Items'.$identifier]->Quotation == 1 ? __('Quotation Number') : __('Order Number')) . '</span>
						<span style="display: block; font-size: 2.5rem; font-weight: 900; color: var(--primary);">' . $OrderNo . '</span>
					</div>
				</div>

				<div class="db-modal-footer">
					<div class="db-grid-actions">';

	if (count($_SESSION['AllowedPageSecurityTokens']) > 1 AND $_POST['Quotation'] == 0) {
		echo '<a href="' . $RootPath . '/ConfirmDispatch_Invoice.php?identifier='.$identifier . '&amp;OrderNumber=' . $OrderNo .'" class="db-btn db-btn-primary" style="justify-content: center;">
				<i class="fas fa-file-invoice-dollar"></i> ' . __('Create Invoice Now') . '
			  </a>';
		echo '<a target="_blank" href="' . $RootPath . '/PrintCustOrder_generic.php?identifier='.$identifier . '&amp;TransNo=' . $OrderNo . '" class="db-btn db-btn-secondary" style="justify-content: center;">
				<i class="fas fa-print"></i> ' . __('Print Packing Slip') . '
			  </a>';
	} elseif ($_POST['Quotation'] == 1) {
		echo '<a target="_blank" href="' . $RootPath . '/PDFQuotation.php?identifier='.$identifier . '&amp;QuotationNo=' . $OrderNo . '&orientation=portrait" class="db-btn db-btn-primary" style="justify-content: center; grid-column: span 2;">
				<i class="fas fa-file-pdf"></i> ' . __('Print Quotation') . '
			  </a>';
	}
	
	echo '      </div>
				<a href="'. $RootPath .'/SelectOrderItems.php?identifier='.$identifier . '&amp;NewOrder=Yes" class="db-btn db-btn-secondary" style="justify-content: center; width: 100%;">
					<i class="fas fa-plus-circle"></i> ' . __('Begin New Transaction') . '
				</a>
			</div>
		</div>
	 </div>';

	unset($_SESSION['Items'.$identifier]->LineItems);
	unset($_SESSION['Items'.$identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();

} elseif (isset($OK_to_PROCESS) AND ($OK_to_PROCESS == 1 AND $_SESSION['ExistingOrder'.$identifier]!=0)) {

/* update the order header then update the old order line details and insert the new lines */

	$DelDate = FormatDateforSQL($_SESSION['Items'.$identifier]->DeliveryDate);
	$QuotDate = FormatDateforSQL($_SESSION['Items'.$identifier]->QuoteDate);
	$ConfDate = FormatDateforSQL($_SESSION['Items'.$identifier]->ConfirmedDate);

	DB_Txn_Begin();

	/*see if this is a contract quotation being changed to an order? */
	if ($_SESSION['Items'.$identifier]->Quotation==0) {//now its being changed? to an order
		$ContractResult = DB_query("SELECT contractref,
											requireddate
									FROM contracts WHERE orderno='" .  $_SESSION['ExistingOrder'.$identifier] ."'
									AND status=1");
		if (DB_num_rows($ContractResult)==1) {//then it is a contract quotation being changed to an order
			$ContractRow = DB_fetch_array($ContractResult);
			$WONo = GetNextTransNo(40);
			$ErrMsg = __('Could not update the contract status');
			$UpdContractResult = DB_query("UPDATE contracts SET status=2,
															wo='" . $WONo . "'
										WHERE orderno='" .$_SESSION['ExistingOrder'.$identifier] . "'",
										$ErrMsg,
										'',
										true);
			$ErrMsg = __('Could not insert the contract bill of materials');
			$InsContractBOM = DB_query("INSERT INTO bom (parent,
														 component,
														 workcentreadded,
														 loccode,
														 effectiveafter,
														 effectiveto,
													 	 quantity)
											SELECT contractref,
													stockid,
													workcentreadded,
													'" . $_SESSION['Items'.$identifier]->Location ."',
													CURRENT_DATE,
													'2099-12-31',
													quantity
											FROM contractbom
											WHERE contractref='" . $ContractRow['contractref'] . "'",
											$ErrMsg);

			$ErrMsg = __('Unable to insert a new work order for the sales order item');
			$InsWOResult = DB_query("INSERT INTO workorders (wo,
															 loccode,
															 requiredby,
															 startdate)
											 VALUES ('" . $WONo . "',
													'" . $_SESSION['Items'.$identifier]->Location ."',
													'" . $ContractRow['requireddate'] . "',
													CURRENT_DATE)",
										$ErrMsg);
			//Need to get the latest BOM to roll up cost but also add the contract other requirements
			$CostResult = DB_query("SELECT SUM((actualcost)*contractbom.quantity) AS cost
									FROM stockmaster INNER JOIN contractbom
									ON stockmaster.stockid=contractbom.stockid
									WHERE contractbom.contractref='" .  $ContractRow['contractref'] . "'");
			$CostRow = DB_fetch_row($CostResult);
			if (is_null($CostRow[0]) OR $CostRow[0]==0) {
				$Cost =0;
				prnMsg(__('In automatically creating a work order for') . ' ' . $ContractRow['contractref'] . ' ' . __('an item on this sales order, the cost of this item as accumulated from the sum of the component costs is nil. This could be because there is no bill of material set up ... you may wish to double check this'),'warn');
			} else {
				$Cost = $CostRow[0];//cost of contract BOM
			}
			$CostResult = DB_query("SELECT SUM(costperunit*quantity) AS cost
									FROM contractreqts
									WHERE contractreqts.contractref='" .  $ContractRow['contractref'] . "'");
			$CostRow = DB_fetch_row($CostResult);
			//add other requirements cost to cost of contract BOM
			$Cost += $CostRow[0];

			// insert parent item info
			$SQL = "INSERT INTO woitems (wo,
										 stockid,
										 qtyreqd,
										 stdcost)
							 VALUES ( '" . $WONo . "',
									 '" . $ContractRow['contractref'] . "',
									 '1',
									 '" . $Cost . "')";
			$ErrMsg = __('The work order item could not be added');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
			WoRealRequirements($WONo, $_SESSION['Items'.$identifier]->Location, $ContractRow['contractref']);

		}//end processing if the order was a contract quotation being changed to an order
	}//end test to see if the order was a contract quotation being changed to an order


	$HeaderSQL = "UPDATE salesorders SET debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "',
										branchcode = '" . $_SESSION['Items'.$identifier]->Branch . "',
										customerref = '". DB_escape_string($_SESSION['Items'.$identifier]->CustRef) ."',
										comments = '". DB_escape_string($_SESSION['Items'.$identifier]->Comments) ."',
										ordertype = '" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
										shipvia = '" . $_POST['ShipVia'] . "',
										deliverydate = '" . FormatDateForSQL(DB_escape_string($_SESSION['Items'.$identifier]->DeliveryDate)) . "',
										quotedate = '" . FormatDateForSQL(DB_escape_string($_SESSION['Items'.$identifier]->QuoteDate)) . "',
										confirmeddate = '" . FormatDateForSQL(DB_escape_string($_SESSION['Items'.$identifier]->ConfirmedDate)) . "',
										deliverto = '" . DB_escape_string($_SESSION['Items'.$identifier]->DeliverTo) . "',
										deladd1 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd1) . "',
										deladd2 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd2) . "',
										deladd3 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd3) . "',
										deladd4 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd4) . "',
										deladd5 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd5) . "',
										deladd6 = '" . DB_escape_string($_SESSION['Items'.$identifier]->DelAdd6) . "',
										contactphone = '" . $_SESSION['Items'.$identifier]->PhoneNo . "',
										contactemail = '" . $_SESSION['Items'.$identifier]->Email . "',
										salesperson = '" .  $_SESSION['Items'.$identifier]->SalesPerson . "',
										freightcost = '" . $_SESSION['Items'.$identifier]->FreightCost ."',
										fromstkloc = '" . $_SESSION['Items'.$identifier]->Location ."',
										printedpackingslip = '" . $_POST['ReprintPackingSlip'] . "',
										quotation = '" . $_SESSION['Items'.$identifier]->Quotation . "',
										deliverblind = '" . $_SESSION['Items'.$identifier]->DeliverBlind . "'
						WHERE salesorders.orderno='" . $_SESSION['ExistingOrder'.$identifier] ."'";

	$ErrMsg = __('The order cannot be updated because');
	$InsertQryResult = DB_query($HeaderSQL, $ErrMsg, '', true);

	foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {

		/* Check to see if the quantity reduced to the same quantity
		as already invoiced - so should set the line to completed */
		if ($StockItem->Quantity == $StockItem->QtyInv) {
			$Completed = 1;
		} else {  /* order line is not complete */
			$Completed = 0;
		}

		$LineItemsSQL = "UPDATE salesorderdetails SET unitprice='"  . $StockItem->Price . "',
													quantity='" . $StockItem->Quantity . "',
													discountpercent='" . floatval($StockItem->DiscountPercent) . "',
													completed='" . $Completed . "',
													poline='" . $StockItem->POLine . "',
													itemdue='" . FormatDateForSQL($StockItem->ItemDue) . "'
						WHERE salesorderdetails.orderno='" . $_SESSION['ExistingOrder'.$identifier] . "'
						AND salesorderdetails.orderlineno='" . $StockItem->LineNumber . "'";

		$ErrMsg = __('The updated order line cannot be modified because');
		$Upd_LineItemResult = DB_query($LineItemsSQL, $ErrMsg, '', true);

	} /* updated line items into sales order details */

	DB_Txn_Commit();
	$Quotation = $_SESSION['Items'.$identifier]->Quotation;
	unset($_SESSION['Items'.$identifier]->LineItems);
	unset($_SESSION['Items'.$identifier]);

	if ($Quotation) {//handle Quotations and Orders print after modification
		prnMsg(__('Quotation Number') .' ' . $_SESSION['ExistingOrder'.$identifier] . ' ' . __('has been updated'),'success');

		/*link to print the quotation */
		echo '<fieldset>
				<tr>
					<td><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . __('Order') . '" alt=""></td>
					<td>' . ' ' . '<a href="' . $RootPath . '/PDFQuotation.php?identifier='.$identifier . '&amp;QuotationNo=' . $_SESSION['ExistingOrder'.$identifier] . '&orientation=landscape" target="_blank">' .  __('Print Quotation (Landscape)')  . '</a></td>
				</tr>
				</fieldset>';
		echo '<fieldset>
				<tr>
					<td><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . __('Order') . '" alt="" /></td>
					<td>' . ' ' . '<a href="' . $RootPath . '/PDFQuotation.php?identifier='.$identifier . '&amp;QuotationNo=' . $_SESSION['ExistingOrder'.$identifier] . '&orientation=portrait" target="_blank">' .  __('Print Quotation (Portrait)')  . '</a></td>
				</tr>
				</fieldset>';
	} else {

	prnMsg(__('Order Number') .' ' . $_SESSION['ExistingOrder'.$identifier] . ' ' . __('has been updated'),'success');

	echo '<fieldset>
			<tr>
			<td><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . __('Print') . '" alt="" /></td>
			<td><a target="_blank" href="' . $RootPath . '/PrintCustOrder.php?identifier='.$identifier  . '&amp;TransNo=' . $_SESSION['ExistingOrder'.$identifier] . '">' .  __('Print packing slip - pre-printed stationery')  . '</a></td>
			</tr>';
	echo '<tr>
			<td><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . __('Print') . '" alt="" /></td>
			<td><a  target="_blank" href="' . $RootPath . '/PrintCustOrder_generic.php?identifier='.$identifier  . '&amp;TransNo=' . $_SESSION['ExistingOrder'.$identifier] . '">' .  __('Print packing slip') . ' (' . __('Laser') . ')'  . '</a></td>
		</tr>';
	echo '<tr>
			<td><img src="'.$RootPath.'/css/'.$Theme.'/images/reports.png" title="' . __('Invoice') . '" alt="" /></td>
			<td><a href="' . $RootPath .'/ConfirmDispatch_Invoice.php?identifier='.$identifier  . '&amp;OrderNumber=' . $_SESSION['ExistingOrder'.$identifier] . '">' .  __('Confirm Order Delivery Quantities and Produce Invoice')  . '</a></td>
		</tr>';
	echo '<tr>
			<td><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . __('Order') . '" alt="" /></td>
			<td><a href="' . $RootPath .'/SelectSalesOrder.php?identifier='.$identifier   . '">' .  __('Select A Different Order')  . '</a></td>
		</tr>
		</fieldset>';
	}//end of print orders
	include(__DIR__ . '/includes/footer.php');
	exit();
}


if (isset($_SESSION['Items'.$identifier]->SpecialInstructions) and mb_strlen($_SESSION['Items'.$identifier]->SpecialInstructions)>0) {
	prnMsg($_SESSION['Items'.$identifier]->SpecialInstructions,'info');
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . __('Final Review & Delivery') . '</h2>
				<p class="db-page-subtitle">' . __('Last step! Review your order items and confirm delivery details.') . '</p>
			</div>
			<div class="db-header-actions">
				<p class="db-page-subtitle">' . __('Customer') . ': <strong>' . $_SESSION['Items'.$identifier]->CustomerName . '</strong> (' . $_SESSION['Items'.$identifier]->DebtorNo . ')</p>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="db-pos-wrapper">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="identifier" value="' . htmlspecialchars($identifier) . '" />';

echo '<div class="db-pos-main">';

/* SHIPPING ADDRESS CARD */
echo '<div class="db-card">
		<div class="db-section-header">
			<h3 class="db-section-title"><i class="fas fa-truck db-icon-green" style="padding: 8px; border-radius: 5px;"></i> ' . __('Shipping Address') . '</h3>
		</div>
		<div class="db-form-grid">
			<div class="db-field-group col-12">
				<label class="db-label">' . __('Deliver To') . '</label>
				<input type="text" autofocus="autofocus" required="required" name="DeliverTo" class="db-input" value="' . stripslashes($_SESSION['Items'.$identifier]->DeliverTo) . '" />
			</div>
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Street Address 1') . '</label>
				<input type="text" name="BrAdd1" class="db-input" value="' . $_SESSION['Items'.$identifier]->DelAdd1 . '" />
			</div>
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Street Address 2') . '</label>
				<input type="text" name="BrAdd2" class="db-input" value="' . $_SESSION['Items'.$identifier]->DelAdd2 . '" />
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('Suburb/City') . '</label>
				<input type="text" name="BrAdd3" class="db-input" value="' . $_SESSION['Items'.$identifier]->DelAdd3 . '" />
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('State/Province') . '</label>
				<input type="text" name="BrAdd4" class="db-input" value="' . $_SESSION['Items'.$identifier]->DelAdd4 . '" />
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('Country') . '</label>
				<select name="BrAdd6" class="db-input">';
foreach ($CountriesArray as $CountryEntry => $CountryName) {
	$selected = (isset($_POST['BrAdd6']) && strtoupper($_POST['BrAdd6']) == strtoupper($CountryName)) || (!isset($_POST['BrAdd6']) && $CountryName == $_SESSION['Items'.$identifier]->DelAdd6) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $CountryName . '">' . $CountryName . '</option>';
}
echo '			</select>
			</div>
		</div>
	  </div>';

/* DELIVERY SETTINGS CARD */
echo '<div class="db-card">
		<div class="db-section-header">
			<h3 class="db-section-title"><i class="fas fa-cog db-icon-blue" style="padding: 8px; border-radius: 5px;"></i> ' . __('Delivery Settings') . '</h3>
		</div>
		<div class="db-form-grid">
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Dispatch From Warehouse') . '</label>
				<select name="Location" class="db-input">';
$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE locations.allowinvoicing='1' ORDER BY locations.locationname";
$StkLocsResult = DB_query($SQL);
while($MyRow=DB_fetch_array($StkLocsResult)) {
	echo '<option ' . ($_SESSION['Items'.$identifier]->Location==$MyRow['loccode'] ? 'selected="selected"' : '') . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
}
echo '			</select>
			</div>
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Shipper / Freight Method') . '</label>
				<select name="ShipVia" class="db-input">';
$ShipperResults = DB_query("SELECT shipper_id, shippername FROM shippers ORDER BY shippername");
while ($MyRow=DB_fetch_array($ShipperResults)) {
	$selected = (isset($_POST['ShipVia']) && $MyRow['shipper_id']==$_POST['ShipVia']) || (!isset($_POST['ShipVia']) && isset($_SESSION['Items'.$identifier]->ShipVia) && $MyRow['shipper_id']==$_SESSION['Items'.$identifier]->ShipVia) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['shipper_id'] . '">' . $MyRow['shippername'] . '</option>';
}
echo '			</select>
			</div>
			<div class="db-field-group col-12">
				<label class="db-label">' . __('Packlist Type') . '</label>
				<select name="DeliverBlind" class="db-input">
					<option value="1" ' . ($_SESSION['Items'.$identifier]->DeliverBlind == 1 ? 'selected="selected"' : '') . '>' . __('Show Prices') . ' (' . __('Standard') . ')</option>
					<option value="2" ' . ($_SESSION['Items'.$identifier]->DeliverBlind == 2 ? 'selected="selected"' : '') . '>' . __('Hide Prices') . ' (' . __('Blind') . ')</option>
				</select>
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('Delivery Date') . ' <small>(' . $_SESSION['DefaultDateFormat'] . ')</small></label>
				<input type="text" name="DeliveryDate" class="db-input date" placeholder="' . $_SESSION['DefaultDateFormat'] . '" value="' . $_SESSION['Items'.$identifier]->DeliveryDate . '" />
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('Quote Date') . ' <small>(' . $_SESSION['DefaultDateFormat'] . ')</small></label>
				<input type="text" name="QuoteDate" class="db-input date" placeholder="' . $_SESSION['DefaultDateFormat'] . '" value="' . $_SESSION['Items'.$identifier]->QuoteDate . '" />
			</div>
			<div class="db-field-group col-4">
				<label class="db-label">' . __('Confirm Date') . ' <small>(' . $_SESSION['DefaultDateFormat'] . ')</small></label>
				<input type="text" name="ConfirmedDate" class="db-input date" placeholder="' . $_SESSION['DefaultDateFormat'] . '" value="' . $_SESSION['Items'.$identifier]->ConfirmedDate . '" />
			</div>';
			
if ($CustomerLogin != 1) {
	echo '	<div class="db-field-group col-6">
				<label class="db-label">' . __('Sales Person') . '</label>
				<select name="SalesPerson" class="db-input">';
	$SalesPeopleResult = DB_query("SELECT salesmancode, salesmanname FROM salesman WHERE current=1");
	while ($SalesPersonRow = DB_fetch_array($SalesPeopleResult)) {
		echo '<option ' . ($SalesPersonRow['salesmancode']==$_SESSION['Items'.$identifier]->SalesPerson ? 'selected="selected"' : '') . ' value="' . $SalesPersonRow['salesmancode'] . '">' . $SalesPersonRow['salesmanname'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Quotation Only?') . '</label>
				<select name="Quotation" class="db-input">
					<option ' . ($_SESSION['Items'.$identifier]->Quotation==1 ? 'selected="selected"' : '') . ' value="1">' . __('Yes') . '</option>
					<option ' . ($_SESSION['Items'.$identifier]->Quotation==0 ? 'selected="selected"' : '') . ' value="0">' . __('No') . '</option>
				</select>
			</div>';
}
echo '		</div>
	  </div>';

/* CONTACT & COMMENTS CARD */
echo '<div class="db-card">
		<div class="db-section-header">
			<h3 class="db-section-title"><i class="fas fa-info-circle db-icon-neutral" style="padding: 8px; border-radius: 5px;"></i> ' . __('Contact & Comments') . '</h3>
		</div>
		<div class="db-form-grid">
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Contact Phone') . '</label>
				<input type="tel" required="required" name="PhoneNo" class="db-input" value="' . $_SESSION['Items'.$identifier]->PhoneNo . '" />
			</div>
			<div class="db-field-group col-6">
				<label class="db-label">' . __('Contact Email') . '</label>
				<input type="email" name="Email" class="db-input" value="' . $_SESSION['Items'.$identifier]->Email . '" />
			</div>
			<div class="db-field-group col-12">
				<label class="db-label">' . __('Customer Reference / PO #') . '</label>
				<input type="text" name="CustRef" class="db-input" value="' . $_SESSION['Items'.$identifier]->CustRef . '" />
			</div>
			<div class="db-field-group col-12">
				<label class="db-label">' . __('Internal Comments') . '</label>
				<textarea name="Comments" class="db-input">' . $_SESSION['Items'.$identifier]->Comments . '</textarea>
			</div>
		</div>
	  </div>';

echo '</div>'; // End db-pos-main

/* SIDEBAR: ORDER SUMMARY */
echo '<div class="db-pos-sidebar db-sticky-sidebar">';
echo '<div class="db-sidebar-cart">
		<div class="db-sidebar-cart-header">
			<h3 class="db-section-title">' . __('Order Summary') . '</h3>
			<span class="db-badge">' . count($_SESSION['Items'.$identifier]->LineItems) . ' ' . __('Items') . '</span>
		</div>
		<div class="db-sidebar-cart-body" style="max-height: 400px;">';

$_SESSION['Items'.$identifier]->total = 0;
$_SESSION['Items'.$identifier]->totalVolume = 0;
$_SESSION['Items'.$identifier]->totalWeight = 0;

foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {
	$LineTotal = $StockItem->Quantity * $StockItem->Price * (1 - $StockItem->DiscountPercent);
	$_SESSION['Items'.$identifier]->total += $LineTotal;
	$_SESSION['Items'.$identifier]->totalVolume += ($StockItem->Quantity * $StockItem->Volume);
	$_SESSION['Items'.$identifier]->totalWeight += ($StockItem->Quantity * $StockItem->Weight);
	
	echo '<div class="db-sidebar-item">
			<div class="db-sidebar-item-row">
				<span class="db-sidebar-item-name">' . $StockItem->ItemDescription . '</span>
				<span class="db-sidebar-item-name">' . locale_number_format($LineTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
			</div>
			<div class="db-sidebar-item-meta">
				' . locale_number_format($StockItem->Quantity, $StockItem->DecimalPlaces) . ' ' . $StockItem->Units . ' @ ' . locale_number_format($StockItem->Price, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '
			</div>
		  </div>';
}

echo '</div>
		<div class="db-sidebar-cart-footer">
			<div class="db-kpi-body" style="margin-bottom: 20px;">
				<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
					<span class="db-kpi-label">' . __('Subtotal') . '</span>
					<span class="db-kpi-value" style="font-size: 1.2rem;">' . locale_number_format($_SESSION['Items'.$identifier]->total, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
				</div>';
if ($CustomerLogin != 1) {
	echo '		<div style="display: flex; justify-content: space-between; align-items: center;">
					<span class="db-kpi-label">' . __('Freight') . '</span>
					<div style="display: flex; gap: 5px; align-items: center;">
						<input type="text" name="FreightCost" class="db-input" style="width: 80px; padding: 4px 8px; font-size: 0.8rem; text-align: right;" value="' . $_SESSION['Items'.$identifier]->FreightCost . '" />
						<button type="submit" name="Update" class="db-btn db-btn-secondary" style="padding: 4px 8px;"><i class="fas fa-sync-alt"></i></button>
					</div>
				</div>';
}
echo '		</div>
			<div class="db-sidebar-cart-footer" style="padding: 15px 0 0; border-top: 1px solid var(--border-soft); display: flex; flex-direction: column; gap: 10px;">';

if ($_SESSION['ExistingOrder'.$identifier]==0) {
	echo '<button type="submit" name="ProcessOrder" class="db-btn db-btn-primary" style="width: 100%; justify-content: center; height: 48px; font-size: 1rem;">
			<i class="fas fa-check-circle"></i> ' . __('Place Order Now') . '
		  </button>';
} else {
	echo '<button type="submit" name="ProcessOrder" class="db-btn db-btn-primary" style="width: 100%; justify-content: center; height: 48px; font-size: 1rem;">
			<i class="fas fa-save"></i> ' . __('Save Order Changes') . '
		  </button>';
}

echo '<button type="submit" name="BackToLineDetails" class="db-btn db-btn-secondary" style="width: 100%; justify-content: center;">
		<i class="fas fa-shopping-cart"></i> ' . __('Return to Cart') . '
	  </button>';

if ($_SESSION['ExistingOrder'.$identifier]==0) {
	echo '<button type="submit" name="MakeRecurringOrder" class="db-btn db-btn-secondary" style="width: 100%; justify-content: center;">
			<i class="fas fa-redo"></i> ' . __('Make Recurring') . '
		  </button>';
}

echo '		</div>
		</div>
	  </div>';
echo '</div>'; // End sidebar

echo '</form>';
echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
