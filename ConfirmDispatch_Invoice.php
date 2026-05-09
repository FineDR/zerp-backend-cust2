<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* Creates sales invoices from entered sales orders based on the quantities dispatched that can be modified */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');
include(__DIR__ . '/includes/DefineSerialItems.php');

require(__DIR__ . '/includes/session.php');
require 'vendor/autoload.php';

global $Title;

$Title = __('Confirm Dispatches and Invoice An Order');
$ViewTopic = 'ARTransactions';
$BookMark = 'ConfirmInvoice';
$ExtraHeadContent = '
<style>
	:root {
		--primary: #059669;
		--primary-hover: #047857;
		--primary-light: #ecfdf5;
		--secondary: #0ea5e9;
		--text-main: #111827;
		--text-secondary: #4b5563;
		--bg-main: #f9fafb;
		--border-soft: #f3f4f6;
		--card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
	}
	
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	
	.db-page { padding: 40px; background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 32px; position: relative; }
	.breadcrumb-container { font-size: 0.75rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 0.5px; }
	.breadcrumb-item { color: var(--text-secondary); text-decoration: none; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
	.breadcrumb-item:hover { color: var(--primary); }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.3; margin: 0 10px; }
	
	.dashboard-title { font-size: 2.25rem; font-weight: 900; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1.1; }
	.dashboard-subtitle { font-size: 1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.7; }
	
	.architect-grid { display: grid; grid-template-columns: 360px 1fr; gap: 32px; align-items: start; }
	
	.db-card { background: #fff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: var(--card-shadow); overflow: hidden; margin-bottom: 0; }
	.db-card-header { background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); border-bottom: 1px solid #f3f4f6; padding: 24px; display: flex; justify-content: space-between; align-items: center; }
	.db-card-title { font-size: 0.85rem; font-weight: 800; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1.5px; }
	
	.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.registry-table th { background: #f9fafb; padding: 16px 24px; text-align: left; font-size: 0.7rem; text-transform: uppercase; font-weight: 900; color: #065f46; letter-spacing: 1px; border-bottom: 1px solid #f3f4f6; }
	.registry-table td { padding: 16px 24px; font-size: 0.85rem; color: #374151; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
	.registry-table tr:last-child td { border-bottom: none; }
	.registry-table tr:hover td { background: #f8fafc; }
	
	.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 32px; }
	.kpi-card-v2 { background: #fff; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 20px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
	.kpi-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
	.kpi-data { display: flex; flex-direction: column; }
	.kpi-data .label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; margin-bottom: 4px; }
	.kpi-data .value { font-size: 1.15rem; font-weight: 900; color: #111827; }
	
	.db-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; font-size: 0.9rem; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
	.db-input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); outline: none; }
	
	.primary-btn-modern { 
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 18px 32px; border-radius: 16px; background: var(--primary); color: #fff;
		border: none; font-weight: 800; font-size: 0.95rem; cursor: pointer;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
	}
	.primary-btn-modern:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 10px 20px rgba(5, 150, 105, 0.3); }
	.primary-btn-modern:active { transform: translateY(0); }
	
	.action-pill { padding: 6px 14px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; background: #f0fdf4; color: #166534; text-decoration: none; border: 1px solid #dcfce7; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
	.action-pill:hover { background: #dcfce7; transform: translateY(-1px); }

	/* MODAL STYLES (Modernized) */
	.pos-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center; z-index: 9999; }
	.pos-modal-content { background: #fff; border-radius: 32px; padding: 48px; width: 100%; max-width: 560px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
	.pos-modal-icon { width: 80px; height: 80px; background: var(--primary-light); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 32px; }
	.pos-modal-title { font-size: 2rem; font-weight: 900; color: #064e3b; margin-bottom: 12px; letter-spacing: -1px; }
	.pos-modal-subtitle { font-size: 1.1rem; color: #4b5563; margin-bottom: 40px; font-weight: 500; }
	.pos-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
	.pos-btn-primary { background: var(--primary); color: #fff; padding: 16px; border-radius: 16px; font-weight: 800; text-decoration: none; transition: all 0.2s; }
	.pos-btn-outline { border: 2px solid #e5e7eb; color: #374151; padding: 16px; border-radius: 16px; font-weight: 800; text-decoration: none; transition: all 0.2s; }
	.pos-btn-ghost { grid-column: span 2; padding: 16px; color: #6b7280; font-weight: 700; text-decoration: none; margin-top: 8px; font-size: 0.9rem; }
	.pos-btn-primary:hover { background: var(--primary-hover); }
	.pos-btn-outline:hover { background: #f9fafb; border-color: #d1d5db; }

	/* PRNMSG ALERT STYLES (Modernized) */
	.error, .warn, .success, .info { 
		padding: 16px 24px; border-radius: 16px; margin: 24px 0; 
		font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 16px;
		border: 1px solid transparent; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
	}
	.error { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
	.warn { background: #fffbeb; color: #b45309; border-color: #fef3c7; }
	.success { background: #f0fdf4; color: #15803d; border-color: #dcfce7; }
	.info { background: #f0f9ff; color: #0369a1; border-color: #e0f2fe; }
	
	.error::before { content: "\f06a"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
	.warn::before { content: "\f071"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
	.success::before { content: "\f058"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
	.info::before { content: "\f05a"; font-family: "Font Awesome 5 Free"; font-weight: 900; }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/CurrenciesArray.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/FreightCalculation.php');
include(__DIR__ . '/includes/GetSalesTransGLCodes.php');
include(__DIR__ . '/includes/CommissionFunctions.php');

if (isset($_POST['identifier'])) {
	$identifier = $_POST['identifier'];
} elseif (isset($_GET['identifier'])) {
	$identifier = $_GET['identifier'];
} else {
	/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
	$identifier = date('U');
}
if (isset($_POST['DispatchDate'])) {
    // Pointless conversion removed
}
if (isset($_GET['SuccessInvoiceNo'])) {
	$InvoiceNo = $_GET['SuccessInvoiceNo'];
	if ($_SESSION['InvoicePortraitFormat'] == 0) {
		$orientation = 'landscape';
	} else {
		$orientation = 'portrait';
	}
	$PrintURL = $RootPath . '/PrintCustTrans.php?FromTransNo=' . urlencode($InvoiceNo) . '&InvOrCredit=Invoice&PrintPDF=True&orientation=' . $orientation;
	$DownloadURL = $PrintURL . '&Download=True';
	
	echo '<div class="pos-modal-overlay">
			<div class="pos-modal-content">
				<div class="pos-modal-icon">
					<i class="fas fa-check"></i>
				</div>
				<h2 class="pos-modal-title">' . __('Invoice Processed Successfully') . '</h2>
				<p class="pos-modal-subtitle">' . __('Invoice #') . $InvoiceNo . ' ' . __('has been generated and accounts updated.') . '</p>
				
				<div class="pos-modal-actions">
					<a href="' . htmlspecialchars($PrintURL, ENT_QUOTES, 'UTF-8') . '" target="_blank" class="pos-btn-primary">
						<i class="fas fa-print"></i> ' . __('Print Invoice') . '
					</a>
					<a href="' . htmlspecialchars($DownloadURL, ENT_QUOTES, 'UTF-8') . '" class="pos-btn-outline">
						<i class="fas fa-download"></i> ' . __('Download PDF') . '
					</a>
					<a href="' . $RootPath . '/SelectSalesOrder.php" class="pos-btn-outline">
						' . __('Return to Orders') . '
					</a>
					<a href="' . $RootPath . '/SelectOrderItems.php?NewOrder=Yes" class="pos-btn-ghost">
						' . __('Start New Order') . '
					</a>
				</div>
			</div>
		  </div>';
	include(__DIR__ . '/includes/footer.php');
	exit;
}

if (!isset($_GET['OrderNumber']) and !isset($_SESSION['ProcessingOrder'])) {
	/* This page can only be called with an order number for invoicing*/
	echo '<div class="centre">
			<a href="' . $RootPath . '/SelectSalesOrder.php">' . __('Select a sales order to invoice') . '</a>
		</div>
		<br />';
	prnMsg(__('This page can only be opened if an order has been selected Please select an order first from the delivery details screen click on Confirm for invoicing'), 'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

elseif (isset($_GET['OrderNumber']) and $_GET['OrderNumber'] > 0 and (!isset($_SESSION['ProcessingOrder']) or $_SESSION['ProcessingOrder'] != $_GET['OrderNumber'] or !isset($_SESSION['Items' . $identifier]))) {

	unset($_SESSION['Items' . $identifier]->LineItems);
	unset($_SESSION['Items' . $identifier]);

	$_SESSION['ProcessingOrder'] = (int)$_GET['OrderNumber'];
	$_GET['OrderNumber'] = (int)$_GET['OrderNumber'];
	$_SESSION['Items' . $identifier] = new Cart;

	/*read in all the guff from the selected order into the Items cart */

	$OrderHeaderSQL = "SELECT salesorders.orderno,
								salesorders.debtorno,
								debtorsmaster.name,
								salesorders.branchcode,
								salesorders.customerref,
								salesorders.comments,
								salesorders.internalcomment,
								salesorders.orddate,
								salesorders.ordertype,
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
								salesorders.fromstkloc,
								locations.taxprovinceid,
								custbranch.taxgroupid,
								currencies.rate as currency_rate,
								currencies.decimalplaces,
								custbranch.defaultshipvia,
								custbranch.specialinstructions,
								pickreq.consignment,
								pickreq.packages
						FROM salesorders
						INNER JOIN debtorsmaster
							ON salesorders.debtorno = debtorsmaster.debtorno
						INNER JOIN custbranch
							ON salesorders.branchcode = custbranch.branchcode
							AND salesorders.debtorno = custbranch.debtorno
						INNER JOIN currencies
							ON debtorsmaster.currcode = currencies.currabrev
						INNER JOIN locations
							ON locations.loccode=salesorders.fromstkloc
						INNER JOIN locationusers
							ON locationusers.loccode=salesorders.fromstkloc
							AND locationusers.userid='" . $_SESSION['UserID'] . "'
							AND locationusers.canupd=1
						LEFT OUTER JOIN pickreq
							ON pickreq.orderno=salesorders.orderno
							AND pickreq.closed=0
						WHERE salesorders.orderno = '" . $_GET['OrderNumber'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$OrderHeaderSQL.= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$ErrMsg = __('The order cannot be retrieved because');
	$GetOrdHdrResult = DB_query($OrderHeaderSQL, $ErrMsg);


	if (DB_num_rows($GetOrdHdrResult) == 0) {
		error_log("Order not found. SQL: " . $OrderHeaderSQL);
	}

	if (DB_num_rows($GetOrdHdrResult) == 1) {
		$MyRow = DB_fetch_array($GetOrdHdrResult);

		$_SESSION['Items' . $identifier]->DebtorNo = $MyRow['debtorno'];
		$_SESSION['Items' . $identifier]->OrderNo = $MyRow['orderno'];
		$_SESSION['Items' . $identifier]->Branch = $MyRow['branchcode'];
		$_SESSION['Items' . $identifier]->CustomerName = $MyRow['name'];
		$_SESSION['Items' . $identifier]->CustRef = $MyRow['customerref'];
		$_SESSION['Items' . $identifier]->Comments = $MyRow['comments'];
		$_SESSION['Items' . $identifier]->DefaultSalesType = $MyRow['ordertype'];
		$_SESSION['Items' . $identifier]->DefaultCurrency = $MyRow['currcode'];
		$_SESSION['Items' . $identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
		$BestShipper = $MyRow['shipvia'];
		$_SESSION['Items' . $identifier]->ShipVia = $MyRow['shipvia'];
		$_SESSION['Items' . $identifier]->InternalComments = reverse_escape($MyRow['internalcomment']);
		$_SESSION['Items' . $identifier]->Consignment = $MyRow['consignment'];
		$_SESSION['Items' . $identifier]->Packages = $MyRow['packages'];

		if (is_null($BestShipper)) {
			$BestShipper = 0;
		}
		$_SESSION['Items' . $identifier]->DeliverTo = $MyRow['deliverto'];
		$_SESSION['Items' . $identifier]->DeliveryDate = (isset($MyRow['deliverydate']) && $MyRow['deliverydate'] != '') ? ConvertSQLDate($MyRow['deliverydate']) : date($_SESSION['DefaultDateFormat']);
		$_SESSION['Items' . $identifier]->BrAdd1 = $MyRow['deladd1'];
		$_SESSION['Items' . $identifier]->BrAdd2 = $MyRow['deladd2'];
		$_SESSION['Items' . $identifier]->BrAdd3 = $MyRow['deladd3'];
		$_SESSION['Items' . $identifier]->BrAdd4 = $MyRow['deladd4'];
		$_SESSION['Items' . $identifier]->BrAdd5 = $MyRow['deladd5'];
		$_SESSION['Items' . $identifier]->BrAdd6 = $MyRow['deladd6'];
		$_SESSION['Items' . $identifier]->PhoneNo = $MyRow['contactphone'];
		$_SESSION['Items' . $identifier]->Email = $MyRow['contactemail'];
		$_SESSION['Items' . $identifier]->SalesPerson = $MyRow['salesperson'];

		$_SESSION['Items' . $identifier]->Location = $MyRow['fromstkloc'];

		$_SESSION['Items' . $identifier]->FreightCost = $MyRow['freightcost'];
		$_SESSION['Old_FreightCost'] = $MyRow['freightcost'];

		//		$_POST['ChargeFreightCost'] = $_SESSION['Old_FreightCost'];
		$_SESSION['Items' . $identifier]->Orig_OrderDate = (isset($MyRow['orddate']) && $MyRow['orddate'] != '') ? ConvertSQLDate($MyRow['orddate']) : date($_SESSION['DefaultDateFormat']);
		$_SESSION['CurrencyRate'] = $MyRow['currency_rate'];
		$_SESSION['Items' . $identifier]->TaxGroup = $MyRow['taxgroupid'];
		$_SESSION['Items' . $identifier]->DispatchTaxProvince = $MyRow['taxprovinceid'];

		$_SESSION['Items' . $identifier]->GetFreightTaxes();

		$_SESSION['Items' . $identifier]->SpecialInstructions = $MyRow['specialinstructions'];


		DB_free_result($GetOrdHdrResult);

		/*now populate the line items array with the sales order details records */

		$LineItemsSQL = "SELECT stkcode,
								stockmaster.description,
								stockmaster.longdescription,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.volume,
								stockmaster.grossweight,
								stockmaster.units,
								stockmaster.decimalplaces,
								stockmaster.mbflag,
								stockmaster.taxcatid,
								stockmaster.discountcategory,
								salesorderdetails.unitprice,
								salesorderdetails.quantity,
								salesorderdetails.discountpercent,
								salesorderdetails.actualdispatchdate,
								salesorderdetails.qtyinvoiced,
								salesorderdetails.narrative,
								salesorderdetails.orderlineno,
								salesorderdetails.poline,
								salesorderdetails.itemdue,
								stockmaster.actualcost AS standardcost
							FROM salesorderdetails INNER JOIN stockmaster
							 	ON salesorderdetails.stkcode = stockmaster.stockid
							WHERE salesorderdetails.orderno ='" . $_GET['OrderNumber'] . "'
							AND salesorderdetails.quantity - salesorderdetails.qtyinvoiced >0
							ORDER BY salesorderdetails.orderlineno";

		$ErrMsg = __('The line items of the order cannot be retrieved because');
		$LineItemsResult = DB_query($LineItemsSQL, $ErrMsg);


		if (DB_num_rows($LineItemsResult) > 0) {

			while ($MyRow = DB_fetch_array($LineItemsResult)) {
				$QOHSQL = "SELECT quantity FROM locstock WHERE stockid='" . $MyRow['stkcode'] . "' and loccode='" . $_SESSION['Items' . $identifier]->Location . "'";
				$QOHResult = DB_query($QOHSQL);
				$QOHRow = DB_fetch_array($QOHResult);

				$_SESSION['Items' . $identifier]->add_to_cart($MyRow['stkcode'], $MyRow['quantity'], $MyRow['description'], $MyRow['longdescription'], $MyRow['unitprice'], $MyRow['discountpercent'], $MyRow['units'], $MyRow['volume'], $MyRow['grossweight'], $QOHRow['quantity'], $MyRow['mbflag'], $MyRow['actualdispatchdate'], $MyRow['qtyinvoiced'], $MyRow['discountcategory'], $MyRow['controlled'], $MyRow['serialised'], $MyRow['decimalplaces'], htmlspecialchars_decode($MyRow['narrative']), 'No', $MyRow['orderlineno'], $MyRow['taxcatid'], '', $MyRow['itemdue'], $MyRow['poline'], $MyRow['standardcost']); /*NB NO Updates to DB */

				/*Calculate the taxes applicable to this line item from the customer branch Tax Group and Item Tax Category */

				$_SESSION['Items' . $identifier]->GetTaxes($MyRow['orderlineno']);
				$SerialItemsSQL = "SELECT pickreqdetails.qtypicked,
										pickserialdetails.stockid,
										serialno,
										moveqty
									FROM pickreq
									INNER JOIN pickreqdetails
										ON pickreqdetails.prid=pickreq.prid
									LEFT OUTER JOIN pickserialdetails
										ON pickserialdetails.detailno=pickreqdetails.detailno
									WHERE pickreq.orderno ='" . $_GET['OrderNumber'] . "'
										AND pickreq.closed=0
										AND pickreqdetails.orderlineno='" . $MyRow['orderlineno'] . "'";

				$ErrMsg = __('The serial items of the pick list cannot be retrieved because');
				$SerialItemsResult = DB_query($SerialItemsSQL, $ErrMsg);

				if (DB_num_rows($SerialItemsResult) > 0) {
					$InOutModifier = 1;
					while ($MySerial = DB_fetch_array($SerialItemsResult)) {
						if (isset($MySerial['serialno'])) {
							/*$_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->SerialItems[$MySerial['serialno']] = new SerialItem($MySerial['serialno'], ($InOutModifier > 0 ? 1 : 1) * filter_number_format($MySerial['moveqty']));*/
							$_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->SerialItems[$MySerial['serialno']] = new SerialItem($MySerial['serialno'], filter_number_format($MySerial['moveqty']));
						} else {
							if ($_SESSION['RequirePickingNote'] == 1) {
								$_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->QtyDispatched = $MySerial['qtypicked'];
							}
						}
					}
				}
			} /* line items from sales order details */
		} else { /* there are no line items that have a quantity to deliver */
			echo '<br />';
			prnMsg(__('There are no ordered items with a quantity left to deliver. There is nothing left to invoice'));
			include(__DIR__ . '/includes/footer.php');
			exit();

		} //end of checks on returned data set
		DB_free_result($LineItemsResult);

	} else { // End if the order was returned successfully.
		echo '<br />';
		prnMsg(__('This order item could not be retrieved. Please select another order. (Order #') . $_GET['OrderNumber'] . ')', 'warn');
		if (isset($ErrMsg)) {
			prnMsg($ErrMsg, 'error');
		}
		include(__DIR__ . '/includes/footer.php');
		exit();
	} //valid order returned from the entered order number
}

// Security: If the Cart object is still not initialized, we cannot proceed. 
// This avoids "Attempt to assign property on null" Fatal Errors.
if (!isset($_SESSION['Items' . $identifier]) || !is_object($_SESSION['Items' . $identifier])) {
	prnMsg(__('The order session has expired or was not initialized. Please select an order to invoice.'), 'error');

	echo '<br /><div class="centre"><a href="' . $RootPath . '/SelectSalesOrder.php">' . __('Back to Order Selection') . '</a></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {

	/* if processing, a dispatch page has been called and ${$StkItm->LineNumber} would have been set from the post
	 set all the necessary session variables changed by the POST */
	if (isset($_POST['ShipVia'])) {
		$_SESSION['Items' . $identifier]->ShipVia = $_POST['ShipVia'];
	}
	if (isset($_POST['Location'])) {
		$_SESSION['Items' . $identifier]->Location = $_POST['Location'];
	}
	if (isset($_POST['ChargeFreightCost'])) {
		$_SESSION['Items' . $identifier]->FreightCost = filter_number_format($_POST['ChargeFreightCost']);
	}
	if (isset($_POST['InternalComments'])) {
		$_SESSION['Items' . $identifier]->InternalComments = $_POST['InternalComments'];
	}
	$i = 1;
	foreach ($_SESSION['Items' . $identifier]->FreightTaxes as $FreightTaxLine) {
		if (isset($_POST['FreightTaxRate' . $i])) {
			$_SESSION['Items' . $identifier]->FreightTaxes[$i]->TaxRate = filter_number_format($_POST['FreightTaxRate' . $i]) / 100;
		}
		$i++;
	}

	foreach ($_SESSION['Items' . $identifier]->LineItems as $Itm) {
		if (sizeOf($Itm->SerialItems) > 0) {
			$_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyDispatched = 0; //initialise QtyDispatched
			foreach ($Itm->SerialItems as $SerialItem) { //calculate QtyDispatched from bundle quantities
				$_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyDispatched+= $SerialItem->BundleQty;
			}
			//Preventing from dispatched more than ordered. Since it's controlled items, users must select the batch/lot again.
			if ($_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyDispatched > ($_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->Quantity - $_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyInv)) {
				prnMsg(__('Dispatched Quantity should not be more than order balanced quantity') . '. ' . __('To dispatch quantity is') . ' ' . $_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyDispatched . ' ' . __('And the order balance is ') . ' ' . ($_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->Quantity - $_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyInv), 'error');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
		} elseif (isset($_POST[$Itm->LineNumber . '_QtyDispatched'])) {
			if (is_numeric(filter_number_format($_POST[$Itm->LineNumber . '_QtyDispatched'])) and filter_number_format($_POST[$Itm->LineNumber . '_QtyDispatched']) <= ($_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->Quantity - $_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyInv)) {

				$_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->QtyDispatched = round(filter_number_format($_POST[$Itm->LineNumber . '_QtyDispatched']), $Itm->DecimalPlaces);
			}
		}
		$i = 1;
		foreach ($Itm->Taxes as $TaxLine) {
			if (isset($_POST[$Itm->LineNumber . $i . '_TaxRate'])) {
				$_SESSION['Items' . $identifier]->LineItems[$Itm->LineNumber]->Taxes[$i]->TaxRate = filter_number_format($_POST[$Itm->LineNumber . $i . '_TaxRate']) / 100;
			}
			$i++;
		}
	} //end foreach lineitem

}

/* Always display dispatch quantities and recalc freight for items being dispatched */

if ($_SESSION['Items' . $identifier]->SpecialInstructions) {
	prnMsg($_SESSION['Items' . $identifier]->SpecialInstructions, 'warn');
}

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div class="breadcrumb-container">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('fulfillment & invoicing') . '</span>
					</div>
					<div>
						<h1 class="dashboard-title">' . __('Order Fulfillment') . '</h1>
						<p class="dashboard-subtitle">' . __('Confirm dispatch quantities and generate the customer invoice for order #') . $_SESSION['Items' . $identifier]->OrderNo . '</p>
					</div>
				</div>
				<div style="margin-bottom: 5px;">
					<div style="background: #ecfdf5; border: 1px solid #d1fae5; padding: 10px 20px; border-radius: 12px; display: flex; align-items: center; gap: 12px;">
						<div style="width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);"></div>
						<span style="font-size: 0.85rem; font-weight: 800; color: #065f46; text-transform: uppercase; letter-spacing: 0.5px;">' . __('Processing Order') . '</span>
					</div>
				</div>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '&OrderNumber=' . urlencode($_SESSION['Items' . $identifier]->OrderNo) . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="architect-grid">';

// We start buffering the right column (table) first to calculate totals
ob_start();

/* ORDER INFO KPI ROW */
echo '<div class="kpi-grid">
		<div class="kpi-card-v2">
			<div class="kpi-icon" style="background: #eff6ff; color: #1d4ed8;"><i class="fas fa-user-tie"></i></div>
			<div class="kpi-data">
				<span class="label">' . __('Customer') . '</span>
				<span class="value" style="font-size: 1rem;">' . $_SESSION['Items' . $identifier]->CustomerName . '</span>
			</div>
		</div>
		<div class="kpi-card-v2">
			<div class="kpi-icon" style="background: #fff7ed; color: #ea580c;"><i class="fas fa-coins"></i></div>
			<div class="kpi-data">
				<span class="label">' . __('Currency') . '</span>
				<span class="value">' . $_SESSION['Items' . $identifier]->DefaultCurrency . '</span>
			</div>
		</div>
		<div class="kpi-card-v2">
			<div class="kpi-icon" style="background: #fdf2f8; color: #db2777;"><i class="fas fa-hashtag"></i></div>
			<div class="kpi-data">
				<span class="label">' . __('Line Items') . '</span>
				<span class="value">' . count($_SESSION['Items' . $identifier]->LineItems) . '</span>
			</div>
		</div>
		<div class="kpi-card-v2">
			<div class="kpi-icon" style="background: #f0fdf4; color: #10b981;"><i class="fas fa-calendar-check"></i></div>
			<div class="kpi-data">
				<span class="label">' . __('Dispatch Date') . '</span>
				<input type="date" name="DispatchDate" class="db-input" style="padding: 2px 8px; border: none; background: transparent; font-size: 1rem; font-weight: 850; width: 140px; color: #111827;" value="' . (isset($_POST['DispatchDate']) ? $_POST['DispatchDate'] : date('Y-m-d')) . '" />
			</div>
		</div>
	  </div>';

/***************************************************************
	Line Item Registry Table
***************************************************************/
echo '<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-boxes"></i> ' . __('Inventory Dispatch Registry') . '</h3>
		</div>
		<div style="overflow-x: auto;">
			<table class="registry-table">
				<thead>
					<tr>
						<th>' . __('Item Details') . '</th>
						<th style="text-align: right;">' . __('Ordered') . '</th>
						<th style="text-align: right;">' . __('Invoiced') . '</th>
						<th style="width: 140px; text-align: center;">' . __('Dispatch Qty') . '</th>
						<th style="text-align: right;">' . __('Unit Price') . '</th>
						<th style="text-align: right;">' . __('Tax Amount') . '</th>
						<th style="text-align: right;">' . __('Line Total') . '</th>
					</tr>
				</thead>
				<tbody>';

$_SESSION['Items' . $identifier]->total = 0;
$_SESSION['Items' . $identifier]->totalVolume = 0;
$_SESSION['Items' . $identifier]->totalWeight = 0;
$TaxTotals = array();
$TaxGLCodes = array();
$TaxTotal = 0;

$j = 0; 
foreach ($_SESSION['Items' . $identifier]->LineItems as $LnItm) {
	$Shortage = ($LnItm->QOHatLoc < $LnItm->Quantity and ($LnItm->MBflag == 'B' or $LnItm->MBflag == 'M'));
	
	if (sizeOf($LnItm->SerialItems) > 0) {
		$_SESSION['Items' . $identifier]->LineItems[$LnItm->LineNumber]->QtyDispatched = 0;
		foreach ($LnItm->SerialItems as $SerialItem) {
			$_SESSION['Items' . $identifier]->LineItems[$LnItm->LineNumber]->QtyDispatched+= $SerialItem->BundleQty;
		}
	} elseif (isset($_POST[$LnItm->LineNumber . '_QtyDispatched'])) {
		$PostedQty = filter_number_format($_POST[$LnItm->LineNumber . '_QtyDispatched']);
		if (is_numeric($PostedQty) and $PostedQty <= ($LnItm->Quantity - $LnItm->QtyInv)) {
			$_SESSION['Items' . $identifier]->LineItems[$LnItm->LineNumber]->QtyDispatched = round($PostedQty, $LnItm->DecimalPlaces);
		}
	}

	$LineTotal = $LnItm->QtyDispatched * $LnItm->Price * (1 - $LnItm->DiscountPercent);
	$_SESSION['Items' . $identifier]->total+= $LineTotal;
	$_SESSION['Items' . $identifier]->totalVolume+= ($LnItm->QtyDispatched * $LnItm->Volume);
	$_SESSION['Items' . $identifier]->totalWeight+= ($LnItm->QtyDispatched * $LnItm->Weight);

	$TaxLineTotal = 0; 
	if (isset($LnItm->Taxes) && (is_array($LnItm->Taxes) || is_object($LnItm->Taxes))) {
		foreach ($LnItm->Taxes AS $Tax) {
			$TaxAmount = ($Tax->TaxRate * $LineTotal);
			$TaxLineTotal += $TaxAmount;
			$TaxTotal += $TaxAmount; // Update global tax total
			if (!isset($TaxTotals[$Tax->TaxAuthID])) $TaxTotals[$Tax->TaxAuthID] = 0;
			$TaxTotals[$Tax->TaxAuthID] += $TaxAmount;
			$TaxGLCodes[$Tax->TaxAuthID] = $Tax->TaxGLCode;
		}
	}

	echo '<tr>
			<td>
				<div style="font-weight: 700; color: #111827;">' . $LnItm->StockID . '</div>
				<div style="font-size: 0.75rem; color: #6b7280; margin-top: 2px;">' . $LnItm->ItemDescription . '</div>';
	
	if ($Shortage) {
		echo '<div style="margin-top: 6px;"><span class="badge" style="background: #fef2f2; color: #dc2626; padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; border: 1px solid #fee2e2;"><i class="fas fa-exclamation-triangle"></i> ' . __('Stock Shortage') . '</span></div>';
	} else {
		echo '<div style="margin-top: 6px;"><span class="badge" style="background: #f0fdf4; color: #166534; padding: 2px 8px; border-radius: 6px; font-size: 0.65rem; font-weight: 800; border: 1px solid #dcfce7;"><i class="fas fa-check-circle"></i> ' . __('Available') . '</span></div>';
	}
	
	echo '  </td>
			<td style="text-align: right; font-weight: 600;">' . locale_number_format($LnItm->Quantity, $LnItm->DecimalPlaces) . ' <span style="font-size: 0.7rem; color: #9ca3af;">' . $LnItm->Units . '</span></td>
			<td style="text-align: right; color: #6b7280;">' . locale_number_format($LnItm->QtyInv, $LnItm->DecimalPlaces) . '</td>';

	echo '<td style="text-align: center;">';
	if ($LnItm->Controlled == 1) {
		if (isset($_POST['ProcessInvoice'])) {
			echo '<span style="font-weight: 800; color: #059669;">' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '</span>';
		} else {
			echo '<input type="hidden" name="' . $LnItm->LineNumber . '_QtyDispatched" value="' . $LnItm->QtyDispatched . '" />
				  <a href="' . $RootPath . '/ConfirmDispatchControlled_Invoice.php?identifier=' . urlencode($identifier) . '&LineNo=' . urlencode($LnItm->LineNumber) . '" class="action-pill">
					<i class="fas fa-barcode"></i> ' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '
				  </a>';
		}
	} else {
		if (isset($_POST['ProcessInvoice'])) {
			echo '<span style="font-weight: 800; color: #059669;">' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '</span>';
		} else {
			echo '<input ' . (++$j == 1 ? 'autofocus ' : '') . ' class="db-input" style="text-align: center; height: 38px; border-color: #d1fae5; background: #f0fdf4;" name="' . $LnItm->LineNumber . '_QtyDispatched" type="text" value="' . locale_number_format($LnItm->QtyDispatched, $LnItm->DecimalPlaces) . '" />';
		}
	}
	echo '</td>';
	
	echo '<td style="text-align: right; font-weight: 600;">' . locale_number_format($LnItm->Price, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</td>
		  <td style="text-align: right; color: #059669; font-weight: 600;">' . locale_number_format($TaxLineTotal, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</td>
		  <td style="text-align: right; font-weight: 800; color: #064e3b;">' . locale_number_format($LineTotal + $TaxLineTotal, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</td>
		</tr>';

	if (mb_strlen($LnItm->Narrative) > 1) {
		echo '<tr><td colspan="7" style="padding: 10px 20px; background: #f8fafc; font-size: 0.8rem; color: #4b5563; font-style: italic; border-bottom: 1px solid #f3f4f6;">
				<i class="fas fa-comment-dots" style="margin-right: 8px; opacity: 0.5;"></i>' . stripslashes(str_replace('\r\n', ' ', $LnItm->Narrative)) . '
			  </td></tr>';
	}
}

echo '</tbody></table></div></div>';

if (!isset($_SESSION['Items' . $identifier]->FreightCost) or $_SESSION['Items' . $identifier]->FreightCost == 0) {
	if ($_SESSION['DoFreightCalc']) {
		[$FreightCost, $BestShipper] = CalcFreightCost($_SESSION['Items' . $identifier]->total, $_SESSION['Items' . $identifier]->BrAdd2, $_SESSION['Items' . $identifier]->BrAdd3, $_SESSION['Items' . $identifier]->BrAdd4, $_SESSION['Items' . $identifier]->BrAdd5, $_SESSION['Items' . $identifier]->BrAdd6, $_SESSION['Items' . $identifier]->totalVolume, $_SESSION['Items' . $identifier]->totalWeight, $_SESSION['Items' . $identifier]->Location, $_SESSION['Items' . $identifier]->DefaultCurrency);
		$_SESSION['Items' . $identifier]->ShipVia = $BestShipper;
	}
	if (isset($FreightCost) and is_numeric($FreightCost)) {
		$FreightCost = $FreightCost / $_SESSION['CurrencyRate'];
	} else {
		$FreightCost = 0;
	}
} else {
	$FreightCost = $_SESSION['Items' . $identifier]->FreightCost;
}

	if (isset($_POST['ChargeFreightCost']) and !is_numeric(filter_number_format($_POST['ChargeFreightCost']))) {
		$_POST['ChargeFreightCost'] = 0;
	}

	echo '</tbody></table></div></div>'; // end table card

$TableContent = ob_get_clean();

// Now we render the LEFT column (Setup Sidebar)
/* CALCULATE FREIGHT TAXES BEFORE SIDEBAR RENDERING */
$FreightTaxTotal = 0; 
foreach ($_SESSION['Items' . $identifier]->FreightTaxes as $FreightTaxLine) {
	if ($FreightTaxLine->TaxOnTax == 1) {
		$TaxAmount = ($FreightTaxLine->TaxRate * ($_SESSION['Items' . $identifier]->FreightCost + $FreightTaxTotal));
		if (!isset($TaxTotals[$FreightTaxLine->TaxAuthID])) $TaxTotals[$FreightTaxLine->TaxAuthID] = 0;
		$TaxTotals[$FreightTaxLine->TaxAuthID] += $TaxAmount;
		$FreightTaxTotal += $TaxAmount;
	} else {
		$TaxAmount = ($FreightTaxLine->TaxRate * $_SESSION['Items' . $identifier]->FreightCost);
		if (!isset($TaxTotals[$FreightTaxLine->TaxAuthID])) $TaxTotals[$FreightTaxLine->TaxAuthID] = 0;
		$TaxTotals[$FreightTaxLine->TaxAuthID] += $TaxAmount;
		$FreightTaxTotal += $TaxAmount;
	}
	$TaxGLCodes[$FreightTaxLine->TaxAuthID] = $FreightTaxLine->TaxGLCode;
}
$TaxTotal += $FreightTaxTotal; // All taxes combined
$GrandTotal = $_SESSION['Items' . $identifier]->total + $_SESSION['Items' . $identifier]->FreightCost + $TaxTotal;

// Sidebar Column
echo '<div style="display: flex; flex-direction: column; gap: 24px;">';

/* ORDER SUMMARY CARD */
echo '<div class="db-card" style="border-color: #d1fae5; background: #f0fdf4;">
		<div class="db-card-header" style="background: #ecfdf5; border-bottom: 1px solid #d1fae5;">
			<h3 class="db-card-title"><i class="fas fa-receipt"></i> ' . __('Order Summary') . '</h3>
		</div>
		<div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
			<div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #4b5563;">
				<span>' . __('Net Total') . '</span>
				<span style="font-weight: 700;">' . locale_number_format($_SESSION['Items' . $identifier]->total, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</span>
			</div>
			<div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #4b5563;">
				<span>' . __('Tax Total') . '</span>
				<span style="font-weight: 700;">' . locale_number_format($TaxTotal, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</span>
			</div>
			<div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: #4b5563; align-items: center;">
				<span>' . __('Freight') . '</span>
				<input class="db-input" name="ChargeFreightCost" type="text" style="width: 100px; height: 32px; padding: 4px 8px; text-align: right;" value="' . locale_number_format($FreightCost, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '" />
			</div>
			<div style="height: 1px; background: #d1fae5; margin: 8px 0;"></div>
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<span style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; color: #065f46; letter-spacing: 1px;">' . __('Grand Total') . '</span>
				<span style="font-size: 1.75rem; font-weight: 950; color: #064e3b; letter-spacing: -1px;">' . locale_number_format($GrandTotal, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</span>
			</div>
		</div>
	  </div>';

/* LOGISTICS & SETTINGS CARDS */
echo '<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Logistics & Billing') . '</h3>
		</div>
		<div style="padding: 24px; display: flex; flex-direction: column; gap: 20px;">
			<div>
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #6b7280; display: block; margin-bottom: 8px;">' . __('Consignment Ref') . '</label>
				<input type="text" name="Consignment" value="' . (isset($_POST['Consignment']) ? $_POST['Consignment'] : $_SESSION['Items' . $identifier]->Consignment) . '" class="db-input" placeholder="' . __('Ref #') . '" />
			</div>
			<div>
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #6b7280; display: block; margin-bottom: 8px;">' . __('Packages') . '</label>
				<input type="text" name="Packages" value="' . (isset($_POST['Packages']) ? $_POST['Packages'] : $_SESSION['Items' . $identifier]->Packages) . '" class="db-input" style="width: 80px;" />
			</div>
			<div>
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #6b7280; display: block; margin-bottom: 8px;">' . __('Dispensing From') . '</label>
				<select name="Location" class="db-input">
					<option value="">' . __('Select Location') . '...</option>';
					$locsql = "SELECT loccode, locationname FROM locations";
					$locrs = DB_query($locsql);
					while ($locrow = DB_fetch_array($locrs)) {
						echo '<option ' . ($_SESSION['Items' . $identifier]->Location == $locrow['loccode'] ? 'selected' : '') . ' value="' . $locrow['loccode'] . '">' . $locrow['locationname'] . ' (' . $locrow['loccode'] . ')</option>';
					}
echo '			</select>
			</div>
			<div>
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #6b7280; display: block; margin-bottom: 8px;">' . __('Ship Via') . '</label>
				<select name="ShipVia" class="db-input">';
					$shippersql = "SELECT shipper_id, shippername FROM shippers";
					$shipperrs = DB_query($shippersql);
					while ($shipperrrow = DB_fetch_array($shipperrs)) {
						echo '<option ' . ($_SESSION['Items' . $identifier]->ShipVia == $shipperrrow['shipper_id'] ? 'selected' : '') . ' value="' . $shipperrrow['shipper_id'] . '">' . $shipperrrow['shippername'] . '</option>';
					}
echo '			</select>
			</div>
			<div>
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #6b7280; display: block; margin-bottom: 8px;">' . __('Internal Workspace') . '</label>
				<textarea name="InternalComments" rows="3" class="db-input" style="font-size: 0.8rem; font-style: italic;">' . reverse_escape($_SESSION['Items' . $identifier]->InternalComments) . '</textarea>
			</div>
		</div>
	  </div>';

/* SIDEBAR ACTIONS CARD */
echo '<div class="db-card" style="border: none; background: transparent; box-shadow: none;">
		<div style="display: flex; flex-direction: column; gap: 12px; padding: 0;">
			<button name="Update" type="submit" value="Update" class="primary-btn-modern" style="background: #10b981; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); width: 100%;">
				<i class="fas fa-sync-alt"></i> ' . __('Update Computation') . '
			</button>
			<button name="ProcessInvoice" type="submit" value="ProcessInvoice" class="primary-btn-modern" style="width: 100%;">
				<i class="fas fa-file-invoice-dollar"></i> ' . __('Finalize & Invoice') . '
			</button>
		</div>
	  </div>';

echo '</div>'; // End left column


// Main Content Column
echo '<div style="display: flex; flex-direction: column; gap: 32px;">';
echo $TableContent;
echo '</div>'; // End Main Column
echo '</div>'; // End architect-grid
echo '</div>'; // End db-page
echo '</form>';


if (!isset($_POST['DispatchDate']) or !Is_Date($_POST['DispatchDate'])) {
	$DefaultDispatchDate = date($_SESSION['DefaultDateFormat'], CalcEarliestDispatchDate());
} else {
	$DefaultDispatchDate = $_POST['DispatchDate'];
}

if (isset($_POST['ProcessInvoice']) and $_POST['ProcessInvoice'] != '') {

	/* SQL to process the postings for sales invoices...

	/*First check there are lines on the dipatch with quantities to invoice
	invoices can have a zero amount but there must be a quantity to invoice */

	$QuantityInvoicedIsPositive = false;

	foreach ($_SESSION['Items' . $identifier]->LineItems as $OrderLine) {
		if ($OrderLine->QtyDispatched > 0) {
			$QuantityInvoicedIsPositive = true;
		}
	}
	if (!$QuantityInvoicedIsPositive) {
		prnMsg(__('There are no lines on this order with a quantity to invoice') . '. ' . __('No further processing has been done'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	if ($_SESSION['ProhibitNegativeStock'] == 1) { // checks for negative stock after processing invoice
		//sadly this check does not combine quantities occuring twice on and order and each line is considered individually :-(
		$NegativesFound = false;
		foreach ($_SESSION['Items' . $identifier]->LineItems as $OrderLine) {
			$SQL = "SELECT stockmaster.description,
							locstock.quantity,
					 		stockmaster.mbflag
		 			FROM locstock
		 			INNER JOIN stockmaster
					ON stockmaster.stockid=locstock.stockid
					WHERE stockmaster.stockid='" . $OrderLine->StockID . "'
					AND locstock.loccode='" . $_SESSION['Items' . $identifier]->Location . "'";

			$ErrMsg = __('Could not retrieve the quantity left at the location once this order is invoiced (for the purposes of checking that stock will not go negative because)');
			$Result = DB_query($SQL, $ErrMsg);
			$CheckNegRow = DB_fetch_array($Result);
			if (($CheckNegRow['mbflag'] == 'B' or $CheckNegRow['mbflag'] == 'M') and mb_substr($OrderLine->StockID, 0, 4) != 'ASSET') {
				if ($CheckNegRow['quantity'] < $OrderLine->QtyDispatched) {
					prnMsg(__('Invoicing the selected order would result in negative stock. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'), 'error', $OrderLine->StockID . ' ' . $CheckNegRow['description'] . ' - ' . __('Negative Stock Prohibited'));
					$NegativesFound = true;
				}
			} elseif ($CheckNegRow['mbflag'] == 'A') {

				/*Now look for assembly components that would go negative */
				$SQL = "SELECT bom.component,
							stockmaster.description,
							locstock.quantity-(" . $OrderLine->QtyDispatched . "*bom.quantity) AS qtyleft
						FROM bom
						INNER JOIN locstock
						ON bom.component=locstock.stockid
						INNER JOIN stockmaster
						ON stockmaster.stockid=bom.component
						WHERE bom.parent='" . $OrderLine->StockID . "'
						AND locstock.loccode='" . $_SESSION['Items' . $identifier]->Location . "'
						AND effectiveafter <= CURRENT_DATE
						AND effectiveto > CURRENT_DATE";

				$ErrMsg = __('Could not retrieve the component quantity left at the location once the assembly item on this order is invoiced (for the purposes of checking that stock will not go negative because)');
				$Result = DB_query($SQL, $ErrMsg);
				while ($NegRow = DB_fetch_array($Result)) {
					if ($NegRow['qtyleft'] < 0) {
						prnMsg(__('Invoicing the selected order would result in negative stock for a component of an assembly item on the order. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'), 'error', $NegRow['component'] . ' ' . $NegRow['description'] . ' - ' . __('Negative Stock Prohibited'));
						$NegativesFound = true;
					} // end if negative would result

				} //loop around the components of an assembly item

			} //end if its an assembly item - check component stock

		} //end of loop around items on the order for negative check
		if ($NegativesFound) {
			echo '</div>';
			echo '<div class="centre">
					<input type="submit" name="Update" value="' . __('Update') . '" /></div>';
			include(__DIR__ . '/includes/footer.php');
			exit();
		}

	} //end of testing for negative stocks


	/* Now Get the area where the sale is to from the branches table */

	$SQL = "SELECT area,
					defaultshipvia
			FROM custbranch
			WHERE custbranch.debtorno ='" . $_SESSION['Items' . $identifier]->DebtorNo . "'
			AND custbranch.branchcode = '" . $_SESSION['Items' . $identifier]->Branch . "'";

	$ErrMsg = __('We were unable to load Area where the Sale is to from the BRANCHES table') . '. ' . __('Please remedy this');
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow = DB_fetch_row($Result);
	$Area = $MyRow[0];
	$DefaultShipVia = $MyRow[1];
	DB_free_result($Result);

	/*company record read in on login with info on GL Links and debtors GL account*/

	if ($_SESSION['CompanyRecord'] == 0) {
		/*The company data and preferences could not be retrieved for some reason */
		prnMsg(__('The company information and preferences could not be retrieved') . ' - ' . __('see your system administrator'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	/*Now need to check that the order details are the same as they were when they were read into the Items array. If they've changed then someone else may have invoiced them */

	$SQL = "SELECT stkcode,
					quantity,
					qtyinvoiced,
					orderlineno
				FROM salesorderdetails
				WHERE completed=0 AND quantity-qtyinvoiced > 0
				AND orderno = '" . $_SESSION['ProcessingOrder'] . "'";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) != count($_SESSION['Items' . $identifier]->LineItems)) {

		/*there should be the same number of items returned from this query as there are lines on the invoice - if not 	then someone has already invoiced or credited some lines */

		echo '<br />';
		prnMsg(__('This order has been changed or invoiced since this delivery was started to be confirmed') . '. ' . __('Processing halted') . '. ' . __('To enter and confirm this dispatch') . '/' . __('invoice the order must be re-selected and re-read again to update the changes made by the other user'), 'error');

		unset($_SESSION['Items' . $identifier]->LineItems);
		unset($_SESSION['Items' . $identifier]);
		unset($_SESSION['ProcessingOrder']);
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$Changes = 0;

	while ($MyRow = DB_fetch_array($Result)) {

		if ($_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->Quantity != $MyRow['quantity'] or $_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->QtyInv != $MyRow['qtyinvoiced']) {

			echo '<br />' . __('Orig order for') . ' ' . $MyRow['orderlineno'] . ' ' . __('has a quantity of') . ' ' . $MyRow['quantity'] . ' ' . __('and an invoiced qty of') . ' ' . $MyRow['qtyinvoiced'] . ' ' . __('the session shows quantity of') . ' ' . $_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->Quantity . ' ' . __('and quantity invoice of') . ' ' . $_SESSION['Items' . $identifier]->LineItems[$MyRow['orderlineno']]->QtyInv;

			prnMsg(__('This order has been changed or invoiced since this delivery was started to be confirmed') . ' ' . __('Processing halted.') . ' ' . __('To enter and confirm this dispatch, it must be re-selected and re-read again to update the changes made by the other user'), 'error');

			echo '<br />';

			echo '<div class="centre"><a href="' . $RootPath . '/SelectSalesOrder.php">' . __('Select a sales order for confirming deliveries and invoicing') . '</a></div>';

			unset($_SESSION['Items' . $identifier]->LineItems);
			unset($_SESSION['Items' . $identifier]);
			unset($_SESSION['ProcessingOrder']);
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	} /*loop through all line items of the order to ensure none have been invoiced since started looking at this order*/

	DB_free_result($Result);

	// *************************************************************************
	//   S T A R T   O F   I N V O I C E   S Q L   P R O C E S S I N G
	// *************************************************************************
	/*Now Get the next invoice number - function in SQL_CommonFunctions*/

	$InvoiceNo = GetNextTransNo(10);
	$PeriodNo = GetPeriod($DefaultDispatchDate);

	$_SESSION['Items' . $identifier]->total = round($_SESSION['Items' . $identifier]->total, $_SESSION['Items' . $identifier]->CurrDecimalPlaces);
	$TaxTotal = round($TaxTotal, $_SESSION['Items' . $identifier]->CurrDecimalPlaces);

	/*Start an SQL transaction */
	DB_Txn_Begin();

	if ($DefaultShipVia != $_SESSION['Items' . $identifier]->ShipVia) {
		$SQL = "UPDATE custbranch
				SET defaultshipvia ='" . $_SESSION['Items' . $identifier]->ShipVia . "'
				WHERE debtorno='" . $_SESSION['Items' . $identifier]->DebtorNo . "'
				AND branchcode='" . $_SESSION['Items' . $identifier]->Branch . "'";
		$ErrMsg = __('Could not update the default shipping carrier for this branch because');
		$Result = DB_query($SQL, $ErrMsg, '', true);
	}

	$DefaultDispatchDate = FormatDateForSQL($DefaultDispatchDate);

	/*Update order header for invoice charged on */
	$SQL = "UPDATE salesorders
			SET comments = CONCAT(comments,' Inv ','" . $InvoiceNo . "'),
			internalcomment = '" . $_POST['InternalComments'] . "',
			printedpackingslip=0
			WHERE orderno= '" . $_SESSION['ProcessingOrder'] . "'";

	$ErrMsg = __('CRITICAL ERROR') . ' ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales order header could not be updated with the invoice number');
	$Result = DB_query($SQL, $ErrMsg, '', true);

	/*Now insert the DebtorTrans */

	$SQL = "INSERT INTO debtortrans (transno,
									type,
									debtorno,
									branchcode,
									trandate,
									inputdate,
									prd,
									reference,
									tpe,
									order_,
									ovamount,
									ovgst,
									ovfreight,
									rate,
									invtext,
									shipvia,
									consignment,
									packages,
									salesperson )
								VALUES (
									'" . $InvoiceNo . "',
									10,
									'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
									'" . $_SESSION['Items' . $identifier]->Branch . "',
									'" . $DefaultDispatchDate . "',
									'" . date('Y-m-d H-i-s') . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['Items' . $identifier]->CustRef . "',
									'" . $_SESSION['Items' . $identifier]->DefaultSalesType . "',
									'" . $_SESSION['ProcessingOrder'] . "',
									'" . $_SESSION['Items' . $identifier]->total . "',
									'" . $TaxTotal . "',
									'" . filter_number_format($_POST['ChargeFreightCost']) . "',
									'" . $_SESSION['CurrencyRate'] . "',
									'" . (isset($_POST['InvoiceText']) ? $_POST['InvoiceText'] : '') . "',
									'" . $_SESSION['Items' . $identifier]->ShipVia . "',
									'" . (isset($_POST['Consignment']) ? $_POST['Consignment'] : '') . "',
									'" . (isset($_POST['Packages']) && $_POST['Packages'] != '' ? $_POST['Packages'] : 1) . "',
									'" . $_SESSION['Items' . $identifier]->SalesPerson . "' )";

	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction record could not be inserted because');
	$Result = DB_query($SQL, $ErrMsg, '', true);
	$DebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');

	/* Insert the tax totals for each tax authority where tax was charged on the invoice */
	foreach ($TaxTotals AS $TaxAuthID => $TaxAmount) {

		$SQL = "INSERT INTO debtortranstaxes (debtortransid,
											taxauthid,
											taxamount)
								VALUES ('" . $DebtorTransID . "',
									'" . $TaxAuthID . "',
									'" . $TaxAmount / $_SESSION['CurrencyRate'] . "')";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction taxes records could not be inserted because');
		$Result = DB_query($SQL, $ErrMsg, '', true);
	}

	/* If balance of the order cancelled update sales order details quantity. Also insert log records for OrderDeliveryDifferencesLog */

	foreach ($_SESSION['Items' . $identifier]->LineItems as $OrderLine) {

		/*Test to see if the item being sold is an asset */
		if (mb_substr($OrderLine->StockID, 0, 6) == 'ASSET-') {
			$IsAsset = true;
			$HyphenOccursAt = mb_strpos($OrderLine->StockID, '-', 6);
			if (!$HyphenOccursAt) {
				$AssetNumber = intval(mb_substr($OrderLine->StockID, 6));
			} else {
				$AssetNumber = intval(mb_substr($OrderLine->StockID, 6, mb_strlen($OrderLine->StockID) - $HyphenOccursAt - 1));
			}
			prnMsg(__('The asset number being disposed of is:') . ' ' . $AssetNumber, 'info');
		} else {
			$IsAsset = false;
			$AssetNumber = 0;
		}

		if (isset($_POST['BOPolicy']) && $_POST['BOPolicy'] == 'CAN') {

			$SQL = "UPDATE salesorderdetails
					SET quantity = quantity - " . ($OrderLine->Quantity - $OrderLine->QtyDispatched - $OrderLine->QtyInv) . "
					WHERE orderno = '" . $_SESSION['ProcessingOrder'] . " '
						AND orderlineno = '" . $OrderLine->LineNumber . "'";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales order detail record could not be updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			if (($OrderLine->Quantity - $OrderLine->QtyDispatched) > 0) {

				$SQL = "INSERT INTO orderdeliverydifferenceslog (orderno,
															invoiceno,
															stockid,
															quantitydiff,
															debtorno,
															branch,
															can_or_bo)
														VALUES (
															'" . $_SESSION['ProcessingOrder'] . "',
															'" . $InvoiceNo . "',
															'" . $OrderLine->StockID . "',
															'" . ($OrderLine->Quantity - $OrderLine->QtyDispatched - $OrderLine->QtyInv) . "',
															'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
															'" . $_SESSION['Items' . $identifier]->Branch . "',
															'CAN')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The order delivery differences log record could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}

		} elseif (($OrderLine->Quantity - $OrderLine->QtyDispatched) > 0 and DateDiff(ConvertSQLDate($DefaultDispatchDate), $_SESSION['Items' . $identifier]->DeliveryDate, 'd') > 0) {

			/*The order is being short delivered after the due date - need to insert a delivery differnce log */

			$SQL = "INSERT INTO orderdeliverydifferenceslog (orderno,
															invoiceno,
															stockid,
															quantitydiff,
															debtorno,
															branch,
															can_or_bo
														)
												VALUES (
													'" . $_SESSION['ProcessingOrder'] . "',
													'" . $InvoiceNo . "',
													'" . $OrderLine->StockID . "',
													'" . ($OrderLine->Quantity - $OrderLine->QtyDispatched - $OrderLine->QtyInv) . "',
													'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
													'" . $_SESSION['Items' . $identifier]->Branch . "',
													'BO'
												)";

			$ErrMsg = '<br />' . __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The order delivery differences log record could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} /*end of order delivery differences log entries */

		/*Now update SalesOrderDetails for the quantity invoiced and the actual dispatch dates. */

		if ($OrderLine->QtyDispatched != 0 and $OrderLine->QtyDispatched != '' and $OrderLine->QtyDispatched) {

			// Test above to see if the line is completed or not
			if ($OrderLine->QtyDispatched >= ($OrderLine->Quantity - $OrderLine->QtyInv) or $_POST['BOPolicy'] == 'CAN') {
				$SQL = "UPDATE salesorderdetails
							SET qtyinvoiced = qtyinvoiced + " . $OrderLine->QtyDispatched . ",
								actualdispatchdate = '" . $DefaultDispatchDate . "',
								completed=1
							WHERE orderno = '" . $_SESSION['ProcessingOrder'] . "'
							AND orderlineno = '" . $OrderLine->LineNumber . "'";
			} else {
				$SQL = "UPDATE salesorderdetails
							SET qtyinvoiced = qtyinvoiced + " . $OrderLine->QtyDispatched . ",
								actualdispatchdate = '" . $DefaultDispatchDate . "'
							WHERE orderno = '" . $_SESSION['ProcessingOrder'] . "'
							AND orderlineno = '" . $OrderLine->LineNumber . "'";

			}

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales order detail record could not be updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*update any open pickreqdetails*/
			$LineItemsSQL = "SELECT pickreqdetails.detailno
							FROM pickreqdetails INNER JOIN pickreq ON pickreq.prid=pickreqdetails.prid
							INNER JOIN salesorderdetails
								ON salesorderdetails.orderno = pickreq.orderno
								AND salesorderdetails.orderlineno=pickreqdetails.orderlineno
							WHERE pickreq.orderno ='" . $_SESSION['ProcessingOrder'] . "'
							AND pickreq.closed=0
							AND salesorderdetails.orderlineno='" . $OrderLine->LineNumber . "'";

			$ErrMsg = __('The line items of the pick list cannot be retrieved because');
			$LineItemsResult = DB_query($LineItemsSQL, $ErrMsg);

			if (DB_num_rows($LineItemsResult) > 0) {

				$MyLine = DB_fetch_array($LineItemsResult);
				$DetailNo = $MyLine['detailno'];
				$SQL = "UPDATE pickreqdetails
						SET invoicedqty='" . $OrderLine->QtyDispatched . "'
						WHERE detailno='" . $DetailNo . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The pickreqdetail record could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}
			/* Update location stock records if not a dummy stock item
			 need the MBFlag later too so save it to $MBFlag */
			$Result = DB_query("SELECT mbflag
								FROM stockmaster
								WHERE stockid = '" . $OrderLine->StockID . "'", __('Cannot retrieve the mbflag'));

			$MyRow = DB_fetch_row($Result);
			$MBFlag = $MyRow[0];

			if ($MBFlag == 'B' or $MBFlag == 'M') {
				$Assembly = false;

				/* Need to get the current location quantity
				 will need it later for the stock movement */
				$SQL = "SELECT locstock.quantity
						FROM locstock
						WHERE locstock.stockid='" . $OrderLine->StockID . "'
						AND loccode= '" . $_SESSION['Items' . $identifier]->Location . "'";
				$ErrMsg = __('WARNING') . ': ' . __('Could not retrieve current location stock');
				$Result = DB_query($SQL, $ErrMsg);

				if (DB_num_rows($Result) == 1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					/* There must be some error this should never happen */
					$QtyOnHandPrior = 0;
				}

				error_log("antigravity_trace: Updating locstock for item [" . $OrderLine->StockID . "] at location [" . $_SESSION['Items' . $identifier]->Location . "] by qty [" . $OrderLine->QtyDispatched . "]");
				$SQL = "UPDATE locstock
						SET quantity = locstock.quantity - " . $OrderLine->QtyDispatched . "
						WHERE locstock.stockid = '" . $OrderLine->StockID . "'
						AND loccode = '" . $_SESSION['Items' . $identifier]->Location . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Location stock record could not be updated because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

			} elseif ($MBFlag == 'A') { /* its an assembly */
				/*Need to get the BOM for this part and make
				 stock moves for the components then update the Location stock balances */
				$Assembly = true;
				$StandardCost = 0; /*To start with - accumulate the cost of the comoponents for use in journals later on */
				$SQL = "SELECT bom.component,
								bom.quantity,
								stockmaster.actualcost AS standard
							FROM bom INNER JOIN stockmaster
							ON bom.component=stockmaster.stockid
							WHERE bom.parent='" . $OrderLine->StockID . "'
								AND bom.effectiveto > CURRENT_DATE
								AND bom.effectiveafter <= CURRENT_DATE";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Could not retrieve assembly components from the database for') . ' ' . $OrderLine->StockID . __('because') . ' ';
				$AssResult = DB_query($SQL, $ErrMsg, '', true);

				while ($AssParts = DB_fetch_array($AssResult)) {

					$StandardCost+= ($AssParts['standard'] * $AssParts['quantity']);
					/* Need to get the current location quantity
					 will need it later for the stock movement */
					$SQL = "SELECT locstock.quantity
							FROM locstock
							WHERE locstock.stockid='" . $AssParts['component'] . "'
							AND loccode= '" . $_SESSION['Items' . $identifier]->Location . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Can not retrieve assembly components location stock quantities because ');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					if (DB_num_rows($Result) == 1) {
						$LocQtyRow = DB_fetch_row($Result);
						$QtyOnHandPrior = $LocQtyRow[0];
					} else {
						/*There must be some error this should never happen */
						$QtyOnHandPrior = 0;
					}
					if (empty($AssParts['standard'])) {
						$AssParts['standard'] = 0;
					}
					$SQL = "INSERT INTO stockmoves (stockid,
													type,
													transno,
													loccode,
													trandate,
													userid,
													debtorno,
													branchcode,
													prd,
													reference,
													qty,
													standardcost,
													show_on_inv_crds,
													newqoh)
										VALUES ('" . $AssParts['component'] . "',
												 10,
												 '" . $InvoiceNo . "',
												 '" . $_SESSION['Items' . $identifier]->Location . "',
												 '" . $DefaultDispatchDate . "',
												 '" . $_SESSION['UserID'] . "',
												 '" . $_SESSION['Items' . $identifier]->DebtorNo . "',
												 '" . $_SESSION['Items' . $identifier]->Branch . "',
												 '" . $PeriodNo . "',
												 '" . __('Assembly') . ': ' . $OrderLine->StockID . ' ' . __('Order') . ': ' . $_SESSION['ProcessingOrder'] . "',
												 '" . -$AssParts['quantity'] * $OrderLine->QtyDispatched . "',
												 '" . $AssParts['standard'] . "',
												 0,
												 '" . ($QtyOnHandPrior - $AssParts['quantity'] * $OrderLine->QtyDispatched) . "'	)";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records for the assembly components of') . ' ' . $OrderLine->StockID . ' ' . __('could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					$SQL = "UPDATE locstock
							SET quantity = locstock.quantity - " . ($AssParts['quantity'] * $OrderLine->QtyDispatched) . "
							WHERE locstock.stockid = '" . $AssParts['component'] . "'
							AND loccode = '" . $_SESSION['Items' . $identifier]->Location . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Location stock record could not be updated for an assembly component because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /* end of assembly explosion and updates */

				/*Update the cart with the recalculated standard cost from the explosion of the assembly's components*/
				$_SESSION['Items' . $identifier]->LineItems[$OrderLine->LineNumber]->StandardCost = $StandardCost;
				$OrderLine->StandardCost = $StandardCost;
			} /* end of its an assembly */

			// Insert stock movements - with unit cost
			//$LocalCurrencyPrice = round(($OrderLine->Price / $_SESSION['CurrencyRate']),$_SESSION['CompanyRecord']['decimalplaces']); change decimalplaces to 5 to avoid price or lines total variance on invoice. And the decimal places should not be over 5 since the stockmoves table defined it as decimal(21,5) now.
			$LocalCurrencyPrice = round(($OrderLine->Price / $_SESSION['CurrencyRate']), 5);

			if (empty($OrderLine->StandardCost)) {
				$OrderLine->StandardCost = 0;
			}
			if ($MBFlag == 'B' or $MBFlag == 'M') {
				$SQL = "INSERT INTO stockmoves (stockid,
														type,
														transno,
														loccode,
														trandate,
														userid,
														debtorno,
														branchcode,
														price,
														prd,
														reference,
														qty,
														discountpercent,
														standardcost,
														newqoh,
														narrative )
													VALUES ('" . $OrderLine->StockID . "',
														10,
														'" . $InvoiceNo . "',
														'" . $_SESSION['Items' . $identifier]->Location . "',
														'" . $DefaultDispatchDate . "',
														'" . $_SESSION['UserID'] . "',
														'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
														'" . $_SESSION['Items' . $identifier]->Branch . "',
														'" . $LocalCurrencyPrice . "',
														'" . $PeriodNo . "',
														'" . DB_escape_string($_SESSION['ProcessingOrder']) . "',
														'" . -$OrderLine->QtyDispatched . "',
														'" . $OrderLine->DiscountPercent . "',
														'" . $OrderLine->StandardCost . "',
														'" . ($QtyOnHandPrior - $OrderLine->QtyDispatched) . "',
														'" . DB_escape_string($OrderLine->Narrative) . "' )";
			} else {
				// its an assembly or dummy and assemblies/dummies always have nil stock (by definition they are made up at the time of dispatch so new qty on hand will be nil
				if (empty($OrderLine->StandardCost)) {
					$OrderLine->StandardCost = 0;
				}
				$SQL = "INSERT INTO stockmoves (stockid,
												type,
												transno,
												loccode,
												trandate,
												userid,
												debtorno,
												branchcode,
												price,
												prd,
												reference,
												qty,
												discountpercent,
												standardcost,
												narrative )
											VALUES ('" . $OrderLine->StockID . "',
												10,
												'" . $InvoiceNo . "',
												'" . $_SESSION['Items' . $identifier]->Location . "',
												'" . $DefaultDispatchDate . "',
												'" . $_SESSION['UserID'] . "',
												'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
												'" . $_SESSION['Items' . $identifier]->Branch . "',
												'" . $LocalCurrencyPrice . "',
												'" . $PeriodNo . "',
												'" . DB_escape_string($_SESSION['ProcessingOrder']) . "',
												'" . -$OrderLine->QtyDispatched . "',
												'" . $OrderLine->DiscountPercent . "',
												'" . $OrderLine->StandardCost . "',
												'" . DB_escape_string($OrderLine->Narrative) . "')";
			}

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

			$Commission = CalculateCommission($_SESSION['Items' . $identifier]->SalesPerson, $_SESSION['Items' . $identifier]->DebtorNo, $_SESSION['Items' . $identifier]->Branch, $OrderLine->StockID, $_SESSION['Items' . $identifier]->DefaultCurrency, ($OrderLine->QtyDispatched * $OrderLine->Price), $PeriodNo);
			if ($Commission != 0) {

				$TransNo = GetNextTransNo(39);
				$SQL = "INSERT INTO salescommissions (commissionno,
													  type,
													  transno,
													  stkmoveno,
													  salespersoncode,
													  paid,
													  amount,
													  currency,
													  exrate
													) VALUES (
													  '" . $TransNo . "',
													  10,
													  '" . $InvoiceNo . "',
													  '" . $StkMoveNo . "',
													  '" . $_SESSION['Items' . $identifier]->SalesPerson . "',
													  0,
													  '" . round($Commission, $_SESSION['CompanyRecord']['decimalplaces']) . "',
													  '" . $_SESSION['Items' . $identifier]->DefaultCurrency . "',
													  '" . $_SESSION['CurrencyRate'] . "'
													)";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales commission accrual record could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$SalesPersonSQL = "SELECT salesmanname, glaccount FROM salesman WHERE salesmancode='" . $_SESSION['Items' . $identifier]->SalesPerson . "'";
				$SalesPersonResult = DB_query($SalesPersonSQL);
				$SalesPersonRow = DB_fetch_array($SalesPersonResult);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (
										39,
										'" . $TransNo . "',
										'" . $DefaultDispatchDate . "',
										'" . $PeriodNo . "',
										'" . $SalesPersonRow['glaccount'] . "',
										'" . mb_substr(__('Sales Commission') . " - " . $SalesPersonRow['salesmanname'] . " - " . $_SESSION['Items' . $identifier]->DebtorNo . " - " . __('Invoice No') . $InvoiceNo, 0, 200) . "',
										'" . round($Commission / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The expenses side of the sales commission posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (
										39,
										'" . $TransNo . "',
										'" . $DefaultDispatchDate . "',
										'" . $PeriodNo . "',
										'" . $_SESSION['CompanyRecord']['commissionsact'] . "',
										'" . mb_substr(__('Sales Commission') . " - " . $SalesPersonRow['salesmanname'] . " - " . $_SESSION['Items' . $identifier]->DebtorNo . " - " . __('Invoice No') . $InvoiceNo, 0, 200) . "',
										'" . round(-$Commission / $_SESSION['CurrencyRate'], $_SESSION['CompanyRecord']['decimalplaces']) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The accruals side of the sales commission posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}

			/*Insert the taxes that applied to this line */
			foreach ($OrderLine->Taxes as $Tax) {

				$SQL = "INSERT INTO stockmovestaxes (stkmoveno,
													taxauthid,
													taxrate,
													taxcalculationorder,
													taxontax)
										VALUES ('" . $StkMoveNo . "',
											'" . $Tax->TaxAuthID . "',
											'" . $Tax->TaxRate . "',
											'" . $Tax->TaxCalculationOrder . "',
											'" . $Tax->TaxOnTax . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Taxes and rates applicable to this invoice line item could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}

			/* Insert the StockSerialMovements and update the StockSerialItems  for controlled items*/

			if ($OrderLine->Controlled == 1) {
				foreach ($OrderLine->SerialItems as $Item) {
					/*We need to add the StockSerialItem record and the StockSerialMoves as well */

					$SQL = "UPDATE stockserialitems	SET quantity= quantity - " . $Item->BundleQty . "
							WHERE stockid='" . $OrderLine->StockID . "'
							AND loccode='" . $_SESSION['Items' . $identifier]->Location . "'
							AND serialno='" . $Item->BundleRef . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock item record could not be updated because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					/* now insert the serial stock movement */

					$SQL = "INSERT INTO stockserialmoves (stockmoveno,
														stockid,
														serialno,
														moveqty)
									VALUES ('" . $StkMoveNo . "',
											'" . $OrderLine->StockID . "',
											'" . $Item->BundleRef . "',
											'" . -$Item->BundleQty . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock movement record could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /* foreach controlled item in the serialitems array */
			} /*end if the orderline is a controlled item */

			/*Insert Sales Analysis records */

			$SalesValue = 0;
			if ($_SESSION['CurrencyRate'] > 0) {
				$SalesValue = $OrderLine->Price * $OrderLine->QtyDispatched / $_SESSION['CurrencyRate'];
			}

			$SQL = "SELECT COUNT(*),
						salesanalysis.stockid,
						salesanalysis.stkcategory,
						salesanalysis.cust,
						salesanalysis.custbranch,
						salesanalysis.area,
						salesanalysis.periodno,
						salesanalysis.typeabbrev,
						salesanalysis.salesperson
					FROM salesanalysis
					INNER JOIN custbranch
						ON salesanalysis.cust=custbranch.debtorno
						AND salesanalysis.custbranch=custbranch.branchcode
						AND salesanalysis.area=custbranch.area
					INNER JOIN stockmaster
					ON salesanalysis.stkcategory=stockmaster.categoryid
					WHERE salesanalysis.salesperson='" . $_SESSION['Items' . $identifier]->SalesPerson . "'
						AND salesanalysis.typeabbrev ='" . $_SESSION['Items' . $identifier]->DefaultSalesType . "'
						AND salesanalysis.periodno='" . $PeriodNo . "'
						AND salesanalysis.cust='" . $_SESSION['Items' . $identifier]->DebtorNo . "'
						AND salesanalysis.custbranch='" . $_SESSION['Items' . $identifier]->Branch . "'
						AND salesanalysis.stockid='" . $OrderLine->StockID . "'
						AND salesanalysis.budgetoractual=1
					GROUP BY salesanalysis.stockid,
						salesanalysis.stkcategory,
						salesanalysis.cust,
						salesanalysis.custbranch,
						salesanalysis.area,
						salesanalysis.periodno,
						salesanalysis.typeabbrev,
						salesanalysis.salesperson,
						salesanalysis.budgetoractual";

			$ErrMsg = __('The count of existing Sales analysis records could not run because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$MyRow = DB_fetch_row($Result);

			if ($MyRow && $MyRow[0] > 0) { /*Update the existing record that already exists */

				$SQL = "UPDATE salesanalysis SET amt=amt+" . round(($SalesValue), $_SESSION['CompanyRecord']['decimalplaces']) . ",
												cost=cost+" . round(($OrderLine->StandardCost * $OrderLine->QtyDispatched), $_SESSION['CompanyRecord']['decimalplaces']) . ",
												qty=qty +" . $OrderLine->QtyDispatched . ",
												disc=disc+" . round(($OrderLine->DiscountPercent * $SalesValue), $_SESSION['CompanyRecord']['decimalplaces']) . "
								WHERE salesanalysis.area='" . $MyRow[5] . "'
								AND salesanalysis.salesperson='" . $MyRow[8] . "'
								AND typeabbrev ='" . $_SESSION['Items' . $identifier]->DefaultSalesType . "'
								AND periodno = '" . $PeriodNo . "'
								AND cust " . LIKE . " '" . $_SESSION['Items' . $identifier]->DebtorNo . "'
								AND custbranch " . LIKE . " '" . $_SESSION['Items' . $identifier]->Branch . "'
								AND stockid " . LIKE . " '" . $OrderLine->StockID . "'
								AND salesanalysis.stkcategory ='" . $MyRow[2] . "'
								AND budgetoractual=1";

			} else { /* insert a new sales analysis record */

				$SQL = "INSERT INTO salesanalysis (typeabbrev,
												periodno,
												amt,
												cost,
												cust,
												custbranch,
												qty,
												disc,
												stockid,
												area,
												budgetoractual,
												salesperson,
												stkcategory )
								SELECT '" . $_SESSION['Items' . $identifier]->DefaultSalesType . "',
										'" . $PeriodNo . "',
										'" . round(($SalesValue), $_SESSION['CompanyRecord']['decimalplaces']) . "',
										'" . round(($OrderLine->StandardCost * $OrderLine->QtyDispatched), $_SESSION['CompanyRecord']['decimalplaces']) . "',
										'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
										'" . $_SESSION['Items' . $identifier]->Branch . "',
										'" . ($OrderLine->QtyDispatched) . "',
										'" . round(($OrderLine->DiscountPercent * $SalesValue), $_SESSION['CompanyRecord']['decimalplaces']) . "',
										'" . $OrderLine->StockID . "',
										custbranch.area,
										1,
										'" . $_SESSION['Items' . $identifier]->SalesPerson . "',
										stockmaster.categoryid
								FROM stockmaster, custbranch
								WHERE stockmaster.stockid = '" . $OrderLine->StockID . "'
								AND custbranch.debtorno = '" . $_SESSION['Items' . $identifier]->DebtorNo . "'
								AND custbranch.branchcode='" . $_SESSION['Items' . $identifier]->Branch . "'";
			}

			$ErrMsg = __('Sales analysis record could not be added or updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/* If GLLink_Stock then insert GLTrans to credit stock and debit cost of sales at standard cost*/

			if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 and $OrderLine->StandardCost != 0 and !$IsAsset) {

				/*first the cost of sales entry - GL accounts are retrieved using the function GetCOGSGLAccount from includes/GetSalesTransGLCodes.php */
				$AccountCOGS = GetCOGSGLAccount($Area, $OrderLine->StockID, $_SESSION['Items' . $identifier]->DefaultSalesType);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (
										10,
										'" . $InvoiceNo . "',
										'" . $DefaultDispatchDate . "',
										'" . $PeriodNo . "',
										'" . $AccountCOGS . "',
										'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->QtyDispatched . " @ " . $OrderLine->StandardCost, 0, 200) . "',
										'" . round(($OrderLine->StandardCost * $OrderLine->QtyDispatched), $_SESSION['CompanyRecord']['decimalplaces']) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/*now the stock entry - this is set to the cost act in the case of a fixed asset disposal */
				$StockGLCode = GetStockGLCode($OrderLine->StockID);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (
										10,
										'" . $InvoiceNo . "',
										'" . $DefaultDispatchDate . "',
										'" . $PeriodNo . "',
										'" . $StockGLCode['stockact'] . "',
										'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->QtyDispatched . " @ " . $OrderLine->StandardCost, 0, 200) . "',
										'" . round((-$OrderLine->StandardCost * $OrderLine->QtyDispatched), $_SESSION['CompanyRecord']['decimalplaces']) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock side of the cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			} /* end of if GL and stock integrated and standard cost !=0 and not an asset */

			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1 and $OrderLine->Price != 0) {

				if (!$IsAsset) { // its a normal stock item
					//Post sales transaction to GL credit sales
					$SalesGLAccounts = GetSalesGLAccount($Area, $OrderLine->StockID, $_SESSION['Items' . $identifier]->DefaultSalesType);

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount )
										VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $SalesGLAccounts['salesglcode'] . "',
											'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->QtyDispatched . " @ " . $OrderLine->Price, 0, 200) . "',
											'" . (-$OrderLine->Price * $OrderLine->QtyDispatched / $_SESSION['CurrencyRate']) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					if ($OrderLine->DiscountPercent != 0) {

						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
												VALUES (
													10,
													'" . $InvoiceNo . "',
													'" . $DefaultDispatchDate . "',
													'" . $PeriodNo . "',
													'" . $SalesGLAccounts['discountglcode'] . "',
													'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . " @ " . ($OrderLine->DiscountPercent * 100) . "%", 0, 200) . "',
													'" . ($OrderLine->Price * $OrderLine->QtyDispatched * $OrderLine->DiscountPercent / $_SESSION['CurrencyRate']) . "')";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales discount GL posting could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					} /*end of if discount !=0 */

				} else {
					/* then the item being sold is an asset disposal
					 * the cost of sales account will be the gain or loss on disposal account
					 * from the fixed asset categories table */
					$SQL = "SELECT cost,
									accumdepn,
									costact,
									accumdepnact,
									disposalact
						FROM fixedassetcategories INNER JOIN fixedassets
						ON fixedassetcategories.categoryid = fixedassets.assetcategoryid
						WHERE assetid ='" . $AssetNumber . "'";
					$ErrMsg = __('The asset disposal GL posting details could not be retrieved because');
					$DisposalResult = DB_query($SQL, $ErrMsg);
					$DisposalRow = DB_fetch_array($DisposalResult);

					/* Need to :
					 * 1.) Debit the accumulated depreciation account with whole amount of accumulated depreciation
					 * 2.) Credit the cost account with the whole amount of the cost
					 * 3.) Debit the disposal account with the NBV
					 * 4.) Credit the disposal account with the sale proceeds net of discounts */

					// 1.) Debit the accumulated depreciation account:
					if ($DisposalRow['accumdepn'] != 0) {
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
											VALUES (
												10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . $DisposalRow['accumdepnact'] . "',
												'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . ' - ' . $OrderLine->StockID . ' ' . __('accumulated depreciation disposal'), 0, 200) . "',
												'" . $DisposalRow['accumdepn'] . "')";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The reversal of accumulated depreciation GL posting on disposal could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}
					// 2.) Credit the cost account:
					if ($DisposalRow['cost'] != 0) {
						$SQL = "INSERT INTO gltrans (
									type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount
								) VALUES (
									10,'" . $InvoiceNo . "','" . $DefaultDispatchDate . "','" . $PeriodNo . "','" . $DisposalRow['costact'] . "','" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . ' ' . __('cost disposal'), 0, 200) . "','" . -$DisposalRow['cost'] . "')";
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The reversal of asset cost on disposal GL posting could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}
					// 3.) Debit the disposal account with the NBV:
					if ($DisposalRow['cost'] - $DisposalRow['accumdepn'] != 0) {
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount )
											VALUES (
												10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . $DisposalRow['disposalact'] . "',
												'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . ' ' . __('net book value disposal'), 0, 200) . "',
												'" . ($DisposalRow['cost'] - $DisposalRow['accumdepn']) . "')";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The disposal net book value GL posting could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}
					//4. Credit the disposal account with the proceeds
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount )
										VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $DisposalRow['disposalact'] . "',
											'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $OrderLine->StockID . ' ' . __('asset disposal proceeds'), 0, 200) . "',
											'" . (-$OrderLine->Price * $OrderLine->QtyDispatched * (1 - $OrderLine->DiscountPercent) / $_SESSION['CurrencyRate']) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The disposal proceeds GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} // End if the item being sold was an asset.

			} /*end of if sales integrated with debtors */

			if ($IsAsset) {
				/* then the item being sold is an asset disposal
				 * need to create fixedassettrans
				 * set disposal date and proceeds
				*/
				$SQL = "INSERT INTO fixedassettrans (assetid,
													transtype,
													transno,
													periodno,
													inputdate,
													fixedassettranstype,
													amount,
													transdate)
										VALUES ('" . $AssetNumber . "',
												10,
												'" . $InvoiceNo . "',
												'" . $PeriodNo . "',
												CURRENT_DATE,
												'disposal',
												'" . round(($OrderLine->Price * $OrderLine->QtyDispatched * (1 - $OrderLine->DiscountPercent) / $_SESSION['CurrencyRate']), $_SESSION['CompanyRecord']['decimalplaces']) . "',
												'" . $DefaultDispatchDate . "')";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The fixed asset transaction could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$SQL = "UPDATE fixedassets
						SET disposalproceeds ='" . round(($OrderLine->Price * $OrderLine->QtyDispatched * (1 - $OrderLine->DiscountPercent) / $_SESSION['CurrencyRate']), $_SESSION['CompanyRecord']['decimalplaces']) . "',
							disposaldate ='" . $DefaultDispatchDate . "'
						WHERE assetid ='" . $AssetNumber . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The fixed asset record could not be updated for the disposal because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

			}
		} /*Quantity dispatched is more than 0 */
	} /*end of OrderLine loop */

	/*update any open pick list*/
	$SQL = "UPDATE pickreq
			SET status = 'Invoiced',
				closed='1'
			WHERE orderno= '" . $_SESSION['ProcessingOrder'] . "'
			AND closed=0";
	$ErrMsg = __('CRITICAL ERROR') . ' ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The pick list header could not be updated');
	$Result = DB_query($SQL, $ErrMsg, '', true);

	if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {

		/*Post debtors transaction to GL debit debtors, credit freight re-charged and credit sales */
		if (($_SESSION['Items' . $identifier]->total + $_SESSION['Items' . $identifier]->FreightCost + $TaxTotal) != 0) {
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
									VALUES (
										10,
										'" . $InvoiceNo . "',
										'" . $DefaultDispatchDate . "',
										'" . $PeriodNo . "',
										'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
										'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
										'" . (($_SESSION['Items' . $identifier]->total + $_SESSION['Items' . $identifier]->FreightCost + $TaxTotal) / $_SESSION['CurrencyRate']) . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The total debtor GL posting could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		/*Could do with setting up a more flexible freight posting schema that looks at the sales type and area of the customer branch to determine where to post the freight recovery */

		if ($_SESSION['Items' . $identifier]->FreightCost != 0) {
			$SQL = "INSERT INTO gltrans (
						type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount	)
				VALUES (
					10,
					'" . $InvoiceNo . "',
					'" . $DefaultDispatchDate . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['CompanyRecord']['freightact'] . "',
					'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
					'" . (-$_SESSION['Items' . $identifier]->FreightCost / $_SESSION['CurrencyRate']) . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The freight GL posting could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}
		foreach ($TaxTotals as $TaxAuthID => $TaxAmount) {
			if ($TaxAmount != 0) {
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
										VALUES (
											10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $TaxGLCodes[$TaxAuthID] . "',
											'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
											'" . (-$TaxAmount / $_SESSION['CurrencyRate']) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The tax GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}
		}
	} /*end of if Sales and GL integrated */

	DB_Txn_Commit();
	EnsureGLEntriesBalance(10, $InvoiceNo);
	// *************************************************************************
	//   E N D   O F   I N V O I C E   S Q L   P R O C E S S I N G
	// *************************************************************************
	unset($_SESSION['Items' . $identifier]->LineItems);
	unset($_SESSION['Items' . $identifier]);
	unset($_SESSION['ProcessingOrder']);

	prnMsg(__('Invoice number') . ' ' . $InvoiceNo . ' ' . __('processed'), 'success');

	$RedirectURL = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SuccessInvoiceNo=' . $InvoiceNo . '&identifier=' . $identifier;

	if (!headers_sent()) {
		header('Location: ' . $RedirectURL);
		exit();
	}

	echo '<script>window.location.href="' . $RedirectURL . '";</script>';
	exit();
} // End if ProcessInvoice
 
include(__DIR__ . '/includes/footer.php');

