<?php

/* The supplier transaction uses the SuppTrans class to hold the information about the invoice
the SuppTrans class contains an array of GRNs objects - containing details of GRNs for invoicing
Also an array of GLCodes objects - only used if the AP - GL link is effective
Also an array of shipment charges for charges to shipments to be apportioned accross the cost of stock items */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineSuppTransClass.php');
include(__DIR__ . '/includes/DefinePOClass.php'); //needed for auto receiving code

require(__DIR__ . '/includes/session.php');

$Title = __('Enter Supplier Invoice');
/* webERP manual links before header.php */
$ViewTopic = 'AccountsPayable';
$BookMark = 'SupplierInvoice';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['TranDate'])){$_POST['TranDate'] = ConvertSQLDate($_POST['TranDate']);}

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

$SupplierID = '';
$SupplierName = '';

if (!isset($_SESSION['SuppTrans']->SupplierName) AND isset($_GET['SupplierID']) AND $_GET['SupplierID'] != '') {
	$SQL = "SELECT suppname FROM suppliers WHERE supplierid='" . DB_escape_string($_GET['SupplierID']) . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		$MyRow = DB_fetch_row($Result);
		$SupplierName = $MyRow[0];
		$SupplierID = $_GET['SupplierID'];
	}
} else {
	if (isset($_SESSION['SuppTrans'])) {
		$SupplierID = $_SESSION['SuppTrans']->SupplierID;
		$SupplierName = $_SESSION['SuppTrans']->SupplierName;
	}
}

echo '<div class="db-page">';
	echo '<style>
		.db-aside-btn {
			width: 100%;
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 10px 12px;
			border-radius: var(--radius-md);
			border: 1px solid transparent;
			background: transparent;
			color: var(--text-body);
			font-size: 0.875rem;
			font-weight: 500;
			cursor: pointer;
			transition: all var(--transition-fast);
			text-align: left;
		}
		.db-aside-btn:hover {
			background: var(--primary-soft);
			color: var(--primary);
			border-color: var(--primary-subtle);
		}
		.db-aside-btn i {
			width: 20px;
			text-align: center;
			color: var(--primary);
			font-size: 1rem;
		}
	</style>';

	echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Enter Supplier Invoice') . '</h2>
			<p class="db-page-subtitle">' . __('Invoicing') . ' <span class="val-bold">' . $SupplierID . ' - ' . $SupplierName . '</span></p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Change Supplier') . '
			</a>
		</div>
	</div>';
if (isset($_GET['SupplierID']) AND $_GET['SupplierID'] != '') {
	$EscapedSupplierID = DB_escape_string($_GET['SupplierID']);

	/*It must be a new invoice entry - clear any existing invoice details from the SuppTrans object and initiate a newy*/
	if (isset($_SESSION['SuppTrans'])) {
		unset($_SESSION['SuppTrans']->GRNs);
		unset($_SESSION['SuppTrans']->GLCodes);
		unset($_SESSION['SuppTrans']->Assets);
		unset($_SESSION['SuppTrans']);
	}

	if (isset($_SESSION['SuppTransTmp'])) {
		unset($_SESSION['SuppTransTmp']->GRNs);
		unset($_SESSION['SuppTransTmp']->GLCodes);
		unset($_SESSION['SuppTransTmp']);
	}
	$_SESSION['SuppTrans'] = new SuppTrans;

	/*Now retrieve supplier information - name, currency, default ex rate, terms, tax rate etc */

	$SQL = "SELECT suppliers.suppname,
					suppliers.supplierid,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					suppliers.currcode,
					currencies.rate AS exrate,
					currencies.decimalplaces,
					suppliers.taxgroupid,
					taxgroups.taxgroupdescription
				FROM suppliers,
					taxgroups,
					currencies,
					paymentterms,
					taxauthorities
				WHERE suppliers.taxgroupid=taxgroups.taxgroupid
				AND suppliers.currcode=currencies.currabrev
				AND suppliers.paymentterms=paymentterms.termsindicator
				AND suppliers.supplierid = '" . $EscapedSupplierID . "'";

	$ErrMsg = __('The supplier record selected') . ': ' . $_GET['SupplierID'] . ' ' . __('cannot be retrieved because');

	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0) {
		prnMsg(__('The supplier record selected') . ': ' . $_GET['SupplierID'] . ' ' . __('cannot be found or is missing currency, tax group, or payment terms setup') , 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$MyRow = DB_fetch_array($Result);

	$_SESSION['SuppTrans']->SupplierName = $MyRow['suppname'];
	$_SESSION['SuppTrans']->TermsDescription = $MyRow['terms'];
	$_SESSION['SuppTrans']->CurrCode = $MyRow['currcode'];
	$_SESSION['SuppTrans']->ExRate = $MyRow['exrate'];
	$_SESSION['SuppTrans']->CurrDecimalPlaces = $MyRow['decimalplaces'];
	$_SESSION['SuppTrans']->TaxGroup = $MyRow['taxgroupid'];
	$_SESSION['SuppTrans']->TaxGroupDescription = $MyRow['taxgroupdescription'];
	$_SESSION['SuppTrans']->SupplierID = $MyRow['supplierid'];

	if ($MyRow['daysbeforedue'] == 0) {
		$_SESSION['SuppTrans']->Terms = '1' . $MyRow['dayinfollowingmonth'];
	}
	else {
		$_SESSION['SuppTrans']->Terms = '0' . $MyRow['daysbeforedue'];
	}
	$_SESSION['SuppTrans']->SupplierID = $_GET['SupplierID'];

	$LocalTaxProvinceResult = DB_query("SELECT taxprovinceid
								FROM locations
								WHERE loccode = '" . $_SESSION['UserStockLocation'] . "'");

	if (DB_num_rows($LocalTaxProvinceResult) == 0) {
		prnMsg(__('The tax province associated with your user account has not been set up in this database. Tax calculations are based on the tax group of the supplier and the tax province of the user entering the invoice. The system administrator should redefine your account with a valid default stocking location and this location should refer to a valid tax province') , 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$LocalTaxProvinceRow = DB_fetch_row($LocalTaxProvinceResult);
	$_SESSION['SuppTrans']->LocalTaxProvince = $LocalTaxProvinceRow[0];

	$_SESSION['SuppTrans']->GetTaxes();

	$_SESSION['SuppTrans']->GLLink_Creditors = $_SESSION['CompanyRecord']['gllink_creditors'];
	$_SESSION['SuppTrans']->GRNAct = $_SESSION['CompanyRecord']['grnact'];
	$_SESSION['SuppTrans']->CreditorsAct = $_SESSION['CompanyRecord']['creditorsact'];

	$_SESSION['SuppTrans']->InvoiceOrCredit = 'Invoice';

} elseif (!isset($_SESSION['SuppTrans'])) {

	prnMsg(__('To enter a supplier invoice the supplier must first be selected from the supplier selection screen') , 'warn');
	echo '<br /><a href="' . $RootPath . '/SelectSupplier.php">' . __('Select A Supplier to Enter an Invoice For') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();

	/*It all stops here if there ain't no supplier selected */
}

/* The code below automatically receives the outstanding balances on the purchase order ReceivePO and adds all the GRNs from that purchase order onto the invoice
 * This is geared towards smaller businesses that have purchase orders that are automatically approved by users, and they want to enter the invoice directly based
 * on the details entered in the purchase order screen.
*/
if (isset($_GET['ReceivePO']) AND $_GET['ReceivePO'] != '') {

	/*Need to check that the user has permission to receive goods */

	if (!in_array($_SESSION['PageSecurityArray']['GoodsReceived.php'], $_SESSION['AllowedPageSecurityTokens'])) {
		prnMsg(__('Your permissions do not allow receiving of goods. Automatic receiving of purchase orders is restricted to those only users who are authorised to receive goods/services') , 'error');
	}
	else {
		/* The user has permission to receive goods then lets go */

		$_GET['ModifyOrderNumber'] = intval($_GET['ReceivePO']);
		include(__DIR__ . '/includes/PO_ReadInOrder.php');

		if ($_SESSION['PO' . $identifier]->Status == 'Authorised') {
			DB_Txn_Begin();
			/*Now Get the next GRN - function in SQL_CommonFunctions*/
			$GRN = GetNextTransNo(25);
			if (!isset($_GET['DeliveryDate'])) {
				$DeliveryDate = date($_SESSION['DefaultDateFormat']);
			}
			else {
				$DeliveryDate = $_GET['DeliveryDate'];
			}
			$_POST['ExRate'] = $_SESSION['SuppTrans']->ExRate;
			$_POST['TranDate'] = $DeliveryDate;

			$PeriodNo = GetPeriod($DeliveryDate);

			$OrderHasControlledItems = false; //assume the best
			foreach ($_SESSION['PO' . $identifier]->LineItems as $OrderLine) {
				//Set the quantity to receive with this auto delivery assuming all is well
				$_SESSION['PO' . $identifier]->LineItems[$OrderLine
					->LineNo]->ReceiveQty = $OrderLine->Quantity - $OrderLine->QtyReceived;

				if ($OrderLine->Controlled == 1) { // it's a controlled item - we can't deal with auto receiving controlled items!!!
					prnMsg(__('Auto receiving of controlled stock items that require serial number or batch number entry is not currently catered for. Only orders with normal non-serial numbered items can be received automatically') , 'error');
					$OrderHasControlledItems = true;
				}
			}
			if ($OrderHasControlledItems == false) {
				foreach ($_SESSION['PO' . $identifier]->LineItems as $OrderLine) {
					$LocalCurrencyPrice = ($OrderLine->Price / $_SESSION['SuppTrans']->ExRate);

					if ($OrderLine->StockID != '') { //Its a stock item line
						/*Need to get the current standard cost as it is now so we can process GL jorunals later*/
						$SQL = "SELECT actualcost as stdcost
									FROM stockmaster
									WHERE stockid='" . $OrderLine->StockID . "'";
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The standard cost of the item being received cannot be retrieved because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						$MyRow = DB_fetch_row($Result);
						$CurrentStandardCost = $MyRow[0];

						if ($OrderLine->QtyReceived == 0) { //its the first receipt against this line
							$_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost = $CurrentStandardCost;
						}

						/*Set the purchase order line stdcostunit = weighted average / standard cost used for all receipts of this line
						 This assures that the quantity received against the purchase order line multiplied by the weighted average of standard
						 costs received = the total of standard cost posted to GRN suspense*/
						$_SESSION['PO' . $identifier]->LineItems[$OrderLine
							->LineNo]->StandardCost = (($CurrentStandardCost * $OrderLine->ReceiveQty) + ($_SESSION['PO' . $identifier]->LineItems[$OrderLine
							->LineNo]->StandardCost * $OrderLine->QtyReceived)) / ($OrderLine->ReceiveQty + $OrderLine->QtyReceived);

					}
					elseif ($OrderLine->QtyReceived == 0 AND $OrderLine->StockID == '') {
						/*Its a nominal item being received */
						/*Need to record the value of the order per unit in the standard cost field to ensure GRN account entries clear */
						$_SESSION['PO' . $identifier]->LineItems[$OrderLine
							->LineNo]->StandardCost = $LocalCurrencyPrice;
					}

					if ($OrderLine->StockID == '') { /*Its a NOMINAL item line */
						$CurrentStandardCost = $_SESSION['PO' . $identifier]->LineItems[$OrderLine
							->LineNo]->StandardCost;
					}

					/*Now the SQL to do the update to the PurchOrderDetails */

					$SQL = "UPDATE purchorderdetails SET quantityrecd = quantityrecd + '" . $OrderLine->ReceiveQty . "',
														stdcostunit='" . $_SESSION['PO' . $identifier]->LineItems[$OrderLine
						->LineNo]->StandardCost . "',
														completed='1'
												WHERE podetailitem = '" . $OrderLine->PODetailRec . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The purchase order detail record could not be updated with the quantity received because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					if ($OrderLine->StockID != '') { /*Its a stock item so use the standard cost for the journals */
						$UnitCost = $CurrentStandardCost;
					}
					else { /*otherwise its a nominal PO item so use the purchase cost converted to local currency */
						$UnitCost = $OrderLine->Price / $_SESSION['SuppTrans']->ExRate;
					}

					/*Need to insert a GRN item */

					$SQL = "INSERT INTO grns (grnbatch,
											podetailitem,
											itemcode,
											itemdescription,
											deliverydate,
											qtyrecd,
											supplierid,
											stdcostunit)
									VALUES ('" . $GRN . "',
										'" . $OrderLine->PODetailRec . "',
										'" . $OrderLine->StockID . "',
										'" . DB_escape_string($OrderLine->ItemDescription) . "',
										'" . FormatDateForSQL($DeliveryDate) . "',
										'" . $OrderLine->ReceiveQty . "',
										'" . $_SESSION['PO' . $identifier]->SupplierID . "',
										'" . $CurrentStandardCost . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('A GRN record could not be inserted') . '. ' . __('This receipt of goods has not been processed because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					if ($OrderLine->StockID != '') { /* if the order line is in fact a stock item */

						/* Update location stock records - NB  a PO cannot be entered for a dummy/assembly/kit parts */

						/* Need to get the current location quantity will need it later for the stock movement */
						$SQL = "SELECT locstock.quantity
										FROM locstock
										WHERE locstock.stockid='" . $OrderLine->StockID . "'
										AND loccode= '" . $_SESSION['PO' . $identifier]->Location . "'";

						$Result = DB_query($SQL);
						if (DB_num_rows($Result) == 1) {
							$LocQtyRow = DB_fetch_row($Result);
							$QtyOnHandPrior = $LocQtyRow[0];
						}
						else {
							/*There must actually be some error this should never happen */
							$QtyOnHandPrior = 0;
						}

						$SQL = "UPDATE locstock
									SET quantity = locstock.quantity + '" . $OrderLine->ReceiveQty . "'
								WHERE locstock.stockid = '" . $OrderLine->StockID . "'
								AND loccode = '" . $_SESSION['PO' . $identifier]->Location . "'";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The location stock record could not be updated because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						/* Insert stock movements - with unit cost */

						$SQL = "INSERT INTO stockmoves (stockid,
														type,
														transno,
														loccode,
														trandate,
														userid,
														price,
														prd,
														reference,
														qty,
														standardcost,
														newqoh)
											VALUES (
												'" . $OrderLine->StockID . "',
												25,
												'" . $GRN . "',
												'" . $_SESSION['PO' . $identifier]->Location . "',
												'" . FormatDateForSQL($DeliveryDate) . "',
												'" . $_SESSION['UserID'] . "',
												'" . $LocalCurrencyPrice . "',
												'" . $PeriodNo . "',
												'" . $_SESSION['PO' . $identifier]->SupplierID . " (" . DB_escape_string($_SESSION['PO' . $identifier]->SupplierName) . ") - " . $_SESSION['PO' . $identifier]->OrderNo . "',
												'" . $OrderLine->ReceiveQty . "',
												'" . $_SESSION['PO' . $identifier]->LineItems[$OrderLine
							->LineNo]->StandardCost . "',
												'" . ($QtyOnHandPrior + $OrderLine->ReceiveQty) . "'
												)";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('stock movement records could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

					} /*end of its a stock item - updates to locations and insert movements*/

					/* Check to see if the line item was flagged as the purchase of an asset */
					if ($OrderLine->AssetID != '' AND $OrderLine->AssetID != '0') { //then it is an asset
						/*first validate the AssetID and if it doesn't exist treat it like a normal nominal item  */
						$CheckAssetExistsResult = DB_query("SELECT assetid,
																	datepurchased,
																	costact
															FROM fixedassets
															INNER JOIN fixedassetcategories
															ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
															WHERE assetid='" . $OrderLine->AssetID . "'");
						if (DB_num_rows($CheckAssetExistsResult) == 1) { //then work with the assetid provided
							/*Need to add a fixedassettrans for the cost of the asset being received */
							$SQL = "INSERT INTO fixedassettrans (assetid,
																transtype,
																transno,
																transdate,
																periodno,
																inputdate,
																fixedassettranstype,
																amount)
											VALUES ('" . $OrderLine->AssetID . "',
													25,
													'" . $GRN . "',
													'" . FormatDateForSQL($DeliveryDate) . "',
													'" . $PeriodNo . "',
													CURRENT_DATE,
													'" . __('cost') . "',
													'" . $CurrentStandardCost * $OrderLine->ReceiveQty . "')";
							$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
							$Result = DB_query($SQL, $ErrMsg, '', true);

							/*Now get the correct cost GL account from the asset category */
							$AssetRow = DB_fetch_array($CheckAssetExistsResult);
							/*Over-ride any GL account specified in the order with the asset category cost account */
							$_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->GLCode = $AssetRow['costact'];
							/*Now if there are no previous additions to this asset update the date purchased */
							if ($AssetRow['datepurchased'] == '1000-01-01') {
								/* it is a new addition as the date is set to 1000-01-01 when the asset record is created
								 * before any cost is added to the asset
								*/
								$SQL = "UPDATE fixedassets
											SET datepurchased='" . FormatDateForSQL($DeliveryDate) . "',
												cost = cost + " . ($CurrentStandardCost * $OrderLine->ReceiveQty) . "
											WHERE assetid = '" . $OrderLine->AssetID . "'";
							}
							else {
								$SQL = "UPDATE fixedassets SET cost = cost + " . ($CurrentStandardCost * $OrderLine->ReceiveQty) . "
											WHERE assetid = '" . $OrderLine->AssetID . "'";
							}
							$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
							$Result = DB_query($SQL, $ErrMsg, '', true);

						} //assetid provided doesn't exist so ignore it and treat as a normal nominal item

					} //assetid is set so the nominal item is an asset
					/* If GLLink_Stock then insert GLTrans to debit the GL Code  and credit GRN Suspense account at standard cost*/
					if ($_SESSION['PO' . $identifier]->GLLink == 1 AND $OrderLine->GLCode != 0) {
						/*GLCode is set to 0 when the GLLink is not activated this covers a situation where the GLLink is now active but it wasn't when this PO was entered */

						/*first the debit using the GLCode in the PO detail record entry*/
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
											VALUES (
												25,
												'" . $GRN . "',
												'" . FormatDateForSQL($DeliveryDate) . "',
												'" . $PeriodNo . "',
												'" . $OrderLine->GLCode . "',
												'" . mb_substr('PO: ' . $_SESSION['PO' . $identifier]->OrderNo . ' ' . $_SESSION['PO' . $identifier]->SupplierID . ' - ' . $OrderLine->StockID . ' - ' . DB_escape_string($OrderLine->ItemDescription) . ' x ' . $OrderLine->ReceiveQty . ' @ ' . locale_number_format($CurrentStandardCost, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
												'" . $CurrentStandardCost * $OrderLine->ReceiveQty . "'
												)";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The purchase GL posting could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						/* If the CurrentStandardCost != UnitCost (the standard at the time the first delivery was booked in,  and its a stock item, then the difference needs to be booked in against the purchase price variance account */

						/*now the GRN suspense entry*/
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
											VALUES (25,
												'" . $GRN . "',
												'" . FormatDateForSQL($DeliveryDate) . "',
												'" . $PeriodNo . "',
												'" . $_SESSION['CompanyRecord']['grnact'] . "',
												'" . mb_substr(__('PO' . $identifier) . ': ' . $_SESSION['PO' . $identifier]->OrderNo . ' ' . $_SESSION['PO' . $identifier]->SupplierID . ' - ' . $OrderLine->StockID . ' - ' . DB_escape_string($OrderLine->ItemDescription) . ' x ' . $OrderLine->ReceiveQty . ' @ ' . locale_number_format($UnitCost, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
												'" . -$UnitCost * $OrderLine->ReceiveQty . "'
												)";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The GRN suspense side of the GL posting could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

					} /* end of if GL and stock integrated and standard cost !=0 */
				} /*end of OrderLine loop */

				$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order Completed on entry of GRN') . '<br />' . $_SESSION['PO' . $identifier]->StatusComments;
				$SQL = "UPDATE purchorders
						SET status='Completed',
						stat_comment='" . $StatusComment . "'
						WHERE orderno='" . $_SESSION['PO' . $identifier]->OrderNo . "'";
				$Result = DB_query($SQL);

				if ($_SESSION['PO' . $identifier]->GLLink == 1) {
					EnsureGLEntriesBalance(25, $GRN);
				}

				DB_Txn_Commit();

				//Now add all these deliveries to this purchase invoice


				$SQL = "SELECT grnbatch,
								grnno,
								purchorderdetails.orderno,
								purchorderdetails.unitprice,
								grns.itemcode,
								grns.deliverydate,
								grns.itemdescription,
								grns.qtyrecd,
								grns.quantityinv,
								grns.stdcostunit,
								grns.supplierref,
								purchorderdetails.glcode,
								purchorderdetails.shiptref,
								purchorderdetails.jobref,
								purchorderdetails.podetailitem,
								purchorderdetails.assetid,
								stockmaster.decimalplaces
						FROM grns INNER JOIN purchorderdetails
							ON  grns.podetailitem=purchorderdetails.podetailitem
						LEFT JOIN stockmaster ON grns.itemcode=stockmaster.stockid
						WHERE grns.supplierid ='" . $_SESSION['SuppTrans']->SupplierID . "'
						AND purchorderdetails.orderno = '" . intval($_GET['ReceivePO']) . "'
						AND grns.qtyrecd - grns.quantityinv > 0
						ORDER BY grns.grnno";
				$GRNResults = DB_query($SQL);

				while ($MyRow = DB_fetch_array($GRNResults)) {

					if ($MyRow['decimalplaces'] == '') {
						$MyRow['decimalplaces'] = 2;
					}
					$_SESSION['SuppTrans']->Add_GRN_To_Trans($MyRow['grnno'], $MyRow['podetailitem'], $MyRow['itemcode'], $MyRow['itemdescription'], $MyRow['qtyrecd'], $MyRow['quantityinv'], $MyRow['qtyrecd'] - $MyRow['quantityinv'], $MyRow['unitprice'], $MyRow['unitprice'], true, $MyRow['stdcostunit'], $MyRow['shiptref'], $MyRow['jobref'], $MyRow['glcode'], $MyRow['orderno'], $MyRow['assetid'], 0, $MyRow['decimalplaces'], $MyRow['grnbatch'], $MyRow['supplierref']);
				}
			} //end if the order has no controlled items on it

		} //only allow auto receiving of all lines if the PO is authorised

	} //only allow auto receiving if the user has permission to receive goods

} // Page called with link to receive all the items on a PO


/* Set the session variables to the posted data from the form if the page has called itself */
if (isset($_POST['ExRate'])) {
	$_SESSION['SuppTrans']->ExRate = filter_number_format($_POST['ExRate']);
	$_SESSION['SuppTrans']->Comments = $_POST['Comments'];
	$_SESSION['SuppTrans']->TranDate = $_POST['TranDate'];

	if (mb_substr($_SESSION['SuppTrans']->Terms, 0, 1) == '1') { /*Its a day in the following month when due */
		$DayInFollowingMonth = (int)mb_substr($_SESSION['SuppTrans']->Terms, 1);
		$DaysBeforeDue = 0;
	}
	else { /*Use the Days Before Due to add to the invoice date */
		$DayInFollowingMonth = 0;
		$DaysBeforeDue = (int)mb_substr($_SESSION['SuppTrans']->Terms, 1);
	}

	$_SESSION['SuppTrans']->DueDate = CalcDueDate($_SESSION['SuppTrans']->TranDate, $DayInFollowingMonth, $DaysBeforeDue);

	$_SESSION['SuppTrans']->SuppReference = $_POST['SuppReference'];

	if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {

		/*The link to GL from creditors is active so the total should be built up from GLPostings and GRN entries
		 if the link is not active then OvAmount must be entered manually. */

		$_SESSION['SuppTrans']->OvAmount = 0; /* for starters */
		if (count($_SESSION['SuppTrans']->GRNs) > 0) {
			foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
				$_SESSION['SuppTrans']->OvAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
			}
		}
		if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
			foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
				$_SESSION['SuppTrans']->OvAmount += $GLLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Shipts) > 0) {
			foreach ($_SESSION['SuppTrans']->Shipts as $ShiptLine) {
				$_SESSION['SuppTrans']->OvAmount += $ShiptLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Contracts) > 0) {
			foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
				$_SESSION['SuppTrans']->OvAmount += $Contract->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Assets) > 0) {
			foreach ($_SESSION['SuppTrans']->Assets as $FixedAsset) {
				$_SESSION['SuppTrans']->OvAmount += $FixedAsset->Amount;
			}
		}
		$_SESSION['SuppTrans']->OvAmount = round($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
	else {
		/*OvAmount must be entered manually */
		$_SESSION['SuppTrans']->OvAmount = round(filter_number_format($_POST['OvAmount']) , $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
}

if (!isset($_POST['PostInvoice'])) {

	if (isset($_POST['GRNS']) AND $_POST['GRNS'] == __('Purchase Orders')) {
		/*This ensures that any changes in the page are stored in the session before calling the grn page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppInvGRNs.php">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against goods received page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppInvGRNs.php">' . __('click here') . '</a> ' . __('to continue') . '</div>
			<br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['Shipts']) AND $_POST['Shipts'] == __('Shipments')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppShiptChgs.php">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against shipments page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppShiptChgs.php">' . __('click here') . '</a> ' . __('to continue') . '.</div><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['GL']) AND $_POST['GL'] == __('General Ledger')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppTransGLAnalysis.php">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against the general ledger page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppTransGLAnalysis.php">' . __('click here') . '</a> ' . __('to continue') . '.</div><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['Contracts']) AND $_POST['Contracts'] == __('Contracts')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/SuppContractChgs.php">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against contracts page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppContractChgs.php">' . __('click here') . '</a> ' . __('to continue') . '.</div>
			<br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['FixedAssets']) AND $_POST['FixedAssets'] == __('Fixed Assets')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/SuppFixedAssetChgs.php">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoice amounts against fixed assets page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppFixedAssetChgs.php">' . __('click here') . '</a> ' . __('to continue') . '.</DIV><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	/* everything below here only do if a Supplier is selected
	 fisrt add a header to show who we are making an invoice for */

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="form1">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="db-bottom-layout">';

	// --- SIDEBAR START ---
	echo '<aside class="db-col-aside">';

	// Card 1: Active Supplier
	echo '<div class="db-card" style="margin-bottom: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-user-tag db-icon-green"></i> ' . __('Supplier Details') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="font-size: 1.1rem; font-weight: 700; color: var(--db-primary);">' . $_SESSION['SuppTrans']->SupplierName . '</div>
				<div style="font-family: monospace; color: var(--text-muted); margin-bottom: var(--space-3);">[' . $_SESSION['SuppTrans']->SupplierID . ']</div>
				<div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
					<div><span class="db-muted">' . __('Currency') . ':</span> <span class="val-bold">' . $_SESSION['SuppTrans']->CurrCode . '</span></div>
					<div><span class="db-muted">' . __('Terms') . ':</span> ' . $_SESSION['SuppTrans']->TermsDescription . '</div>
					<div><span class="db-muted">' . __('Tax Group') . ':</span> ' . $_SESSION['SuppTrans']->TaxGroupDescription . '</div>
				</div>
			</div>
		</div>';

	// Card 2: Invoice Source Actions
	echo '<div class="db-card" style="margin-bottom: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Add Charges From') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-2);">';
	
	$sourceTypes = [
		['name' => 'GRNS', 'label' => __('Purchase Orders'), 'icon' => 'shopping-cart'],
		['name' => 'Shipts', 'label' => __('Shipments'), 'icon' => 'truck'],
		['name' => 'Contracts', 'label' => __('Contracts'), 'icon' => 'file-text'],
		['name' => 'FixedAssets', 'label' => __('Fixed Assets'), 'icon' => 'briefcase'],
		['name' => 'GL', 'label' => __('General Ledger'), 'icon' => 'book', 'condition' => ($_SESSION['SuppTrans']->GLLink_Creditors == 1)]
	];

	foreach ($sourceTypes as $source) {
		if (isset($source['condition']) && !$source['condition']) continue;
		echo '<button type="submit" name="' . $source['name'] . '" value="' . $source['label'] . '" class="db-aside-btn">
				<i class="fas fa-' . $source['icon'] . '"></i>
				<span>' . $source['label'] . '</span>
			  </button>';
	}
	echo '  </div>
		  </div>';

	// Pre-calculate Summary for Sidebar
	$TaxTotal = 0;
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
		}
		if (!isset($_POST['OverRideTax']) OR $_POST['OverRideTax'] == 'Auto') {
			if ($Tax->TaxOnTax == 1) {
				$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * ($_SESSION['SuppTrans']->OvAmount + $TaxTotal);
			} else {
				$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * $_SESSION['SuppTrans']->OvAmount;
			}
		} else {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);
		}
		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount;
	}

	// Card 3: Live Summary (Sidebar BottomLine)
	echo '<div class="db-card" style="position: sticky; top: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Invoice Summary') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="display: flex; flex-direction: column; gap: var(--space-3);">
					<div style="display: flex; justify-content: space-between;">
						<span class="db-muted">' . __('Manual Amount') . ':</span>
						<span class="val-bold">' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
					</div>';
	
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		echo '<div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.9rem; margin-bottom: var(--space-2);">
				<div style="display: flex; justify-content: space-between;">
					<span class="db-muted" title="' . __('Tax Rate') . '">' . $Tax->TaxAuthDescription . ':</span>
					<span>' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
				</div>';
		
		if (isset($_POST['OverRideTax']) && $_POST['OverRideTax'] == 'Man') {
			echo '<div style="display: flex; gap: 8px; align-items: center;">
					<input type="text" class="number db-input-sm" name="TaxRate' . $Tax->TaxCalculationOrder . '" placeholder="' . __('Rate') . '" style="width: 60px;" value="' . locale_number_format($Tax->TaxRate * 100, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" /> <span class="db-muted">%</span>
					<input type="text" class="number db-input-sm" name="TaxAmount' . $Tax->TaxCalculationOrder . '" placeholder="' . __('Amount') . '" style="flex: 1;" value="' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />
				  </div>';
		} else {
			echo '<input type="hidden" name="TaxRate' . $Tax->TaxCalculationOrder . '" value="' . locale_number_format($Tax->TaxRate * 100, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />
				  <input type="hidden" name="TaxAmount' . $Tax->TaxCalculationOrder . '" value="' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />';
		}
		echo '</div>';
	}
	
	echo '			<div style="margin: var(--space-2) 0; height: 1px; background: var(--border-soft);"></div>
					<div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: var(--db-primary);">
						<span class="val-bold">' . __('Total') . ':</span>
						<span class="val-bold">' . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' ' . $_SESSION['SuppTrans']->CurrCode . '</span>
					</div>
				</div>
				<div style="margin-top: var(--space-6);">
					<label class="db-label">' . __('Tax Calculation') . '</label>
					<div style="display: flex; gap: 8px;">
						<select name="OverRideTax" class="db-input db-input-sm" onchange="this.form.submit()">
							<option value="Auto" ' . (!isset($_POST['OverRideTax']) || $_POST['OverRideTax'] == 'Auto' ? 'selected' : '') . '>' . __('Automatic') . '</option>
							<option value="Man" ' . (isset($_POST['OverRideTax']) && $_POST['OverRideTax'] == 'Man' ? 'selected' : '') . '>' . __('Manual') . '</option>
						</select>
					</div>
				</div>
				<div style="margin-top: var(--space-6);">
					<button type="submit" name="PostInvoice" class="db-btn db-btn-primary" style="width: 100%; height: 44px; justify-content: center; font-size: 1rem;">
						<i class="fas fa-check-double"></i> ' . __('Complete Invoice') . '
					</button>
				</div>
			</div>
		</div>';

	echo '</aside>';
	// --- SIDEBAR END ---

	// --- MAIN CONTENT START ---
	echo '<main class="db-col-main">';

	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header">
				<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> ' . __('Invoice Header Details') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-grid">';

	echo '<div class="db-form-group">
			<label for="SuppReference">' . __('Supplier Invoice Reference') . '</label>
			<input type="text" required="required" pattern=".{1,20}" placeholder="' . __('e.g. INV-12345') . '" name="SuppReference" value="' . $_SESSION['SuppTrans']->SuppReference . '" />
		</div>';

	if (!isset($_SESSION['SuppTrans']->TranDate)) {
		$_SESSION['SuppTrans']->TranDate = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') , date('d') - 1, date('y')));
	}
	echo '<div class="db-form-group">
			<label for="TranDate">' . __('Invoice Date') . '</label>
			<input type="date" name="TranDate" value="' . FormatDateForSQL($_SESSION['SuppTrans']->TranDate) . '" />
		</div>';

	echo '<div class="db-form-group">
			<label for="ExRate">' . __('Exchange Rate') . '</label>
			<input class="number" name="ExRate" type="text" value="' . locale_number_format($_SESSION['SuppTrans']->ExRate, 'Variable') . '" />
		</div>
	</div></div></div>'; // end grid, body, card

	$CanSubmit = false; //To avoid a empty submit
	$TotalGRNValue = 0;

		echo '<div class="db-card" style="margin-bottom: var(--space-6);">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-shopping-basket db-icon-green"></i> ' . __('Purchase Order Charges (GRNs)') . '</h3>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Seq') . '</th>
								<th>' . __('Batch') . '</th>
								<th>' . __('Ref') . '</th>
								<th>' . __('Item') . '</th>
								<th class="number">' . __('Quantity') . '</th>
								<th class="number">' . __('Price') . '</th>
								<th class="number">' . __('Total') . '</th>
							</tr>
						</thead>
						<tbody>';

		foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN) {
			echo '<tr>
					<td>' . $EnteredGRN->GRNNo . '</td>
					<td>' . $EnteredGRN->GRNBatchNo . '</td>
					<td>' . $EnteredGRN->SupplierRef . '</td>
					<td><div class="val-bold">' . $EnteredGRN->ItemCode . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $EnteredGRN->ItemDescription . '</div></td>
					<td class="number">' . locale_number_format($EnteredGRN->This_QuantityInv, $EnteredGRN->DecimalPlaces) . '</td>
					<td class="number">' . locale_number_format($EnteredGRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
					<td class="number val-bold">' . locale_number_format($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
				</tr>';
			$TotalGRNValue += ($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv);
		}

		echo '</tbody>
				<tfoot>
					<tr class="db-table-summary">
						<td colspan="6" style="text-align: right;">' . __('Subtotal Goods Charged') . ':</td>
						<td class="number">' . locale_number_format($TotalGRNValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
					</tr>
				</tfoot>
			</table></div></div>';

	$TotalShiptValue = 0;

	if (count($_SESSION['SuppTrans']->Shipts) > 0) { /*if there are any Shipment charges on the invoice*/
		$CanSubmit = true;

		echo '<br />
				<table class="selection">
				<tr>
					<th colspan="2">' . __('Shipment Charges') . '</th>
				</tr>';
		$TableHeader = '<tr>
							<th>' . __('Shipment') . '</th>
							<th>' . __('Amount') . '</th>
						</tr>';
		echo $TableHeader;

		$i = 0; //row counter
		foreach ($_SESSION['SuppTrans']->Shipts as $EnteredShiptRef) {

			echo '<tr>
					<td>' . $EnteredShiptRef->ShiptRef . '</td>
					<td class="number">' . locale_number_format($EnteredShiptRef->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
				</tr>';

			$TotalShiptValue += $EnteredShiptRef->Amount;

			$i++;
			if ($i > 15) {
				$i = 0;
				echo $TableHeader;
			}
		}

		echo '<tr>
				<td class="number" style="color:blue">' . __('Total shipment charges') . ':</td>
				<td class="number" style="color:blue">' . locale_number_format($TotalShiptValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
			</tr>
			</table>';
	}

	$TotalAssetValue = 0;

	if (count($_SESSION['SuppTrans']->Assets) > 0) { /*if there are any fixed assets on the invoice*/
		$CanSubmit = true;

		echo '<br />
			<table class="selection">
			<tr>
				<th colspan="3">' . __('Fixed Asset Additions') . '</th>
			</tr>';
		$TableHeader = '<tr>
							<th>' . __('Asset ID') . '</th>
							<th>' . __('Description') . '</th>
							<th>' . __('Amount') . ' ' . $_SESSION['SuppTrans']->CurrCode . '</th>
						</tr>';
		echo $TableHeader;

		foreach ($_SESSION['SuppTrans']->Assets as $EnteredAsset) {

			echo '<tr>
					<td>' . $EnteredAsset->AssetID . '</td>
					<td>' . $EnteredAsset->Description . '</td>
					<td class="number">' . locale_number_format($EnteredAsset->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
				</tr>';

			$TotalAssetValue += $EnteredAsset->Amount;

			$i++;
			if ($i > 15) {
				$i = 0;
				echo $TableHeader;
			}
		}

		echo '<tr>
				<td colspan="2" class="number" style="color:blue">' . __('Total asset additions') . ':</td>
				<td class="number" style="color:blue">' . locale_number_format($TotalAssetValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
			</tr>
			</table>';
	} //end loop around assets added to invocie
	$TotalContractsValue = 0;

	if (count($_SESSION['SuppTrans']->Contracts) > 0) { /*if there are any contract charges on the invoice*/
		$CanSubmit = true;

		echo '<br />
			<table class="selection">
			<tr>
				<th colspan="3">' . __('Contract Charges') . '</th>
			</tr>';
		$TableHeader = '<tr>
							<th>' . __('Contract') . '</th>
							<th>' . __('Narrative') . '</th>
							<th>' . __('Amount') . ' ' . $_SESSION['SuppTrans']->CurrCode . '</th>
						</tr>';
		echo $TableHeader;

		$i = 0;
		foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {

			echo '<tr>
					<td>' . $Contract->ContractRef . '</td>
					<td>' . $Contract->Narrative . '</td>
					<td class="number">' . locale_number_format($Contract->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
				</tr>';

			$TotalContractsValue += $Contract->Amount;

			$i++;
			if ($i == 15) {
				$i = 0;
				echo $TableHeader;
			}
		}

		echo '<tr>
				<td colspan="2" class="number" style="color:blue">' . __('Total contract charges') . ':</td>
				<td class="number" style="color:blue">' . locale_number_format($TotalContractsValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
			</tr>
			</table>';
	}

	$TotalGLValue = 0;

	if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {

		if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
			$CanSubmit = true;
			echo '<br />
					<table class="selection">
					<tr>
						<th colspan="5">' . __('General Ledger Analysis') . '</th>
					</tr>';
			$TableHeader = '<tr>
								<th>' . __('Account') . '</th>
								<th>' . __('Account Name') . '</th>
								<th>' . __('Narrative') . '</th>
								<th>' . __('Tag') . '</th>
								<th>' . __('Amount') . '<br />' . __('in') . ' ' . $_SESSION['SuppTrans']->CurrCode . '</th>
							</tr>';
			echo $TableHeader;

			foreach ($_SESSION['SuppTrans']->GLCodes as $EnteredGLCode) {

				$DescriptionTag = GetDescriptionsFromTagArray($EnteredGLCode->Tag);

				echo '<tr>
						<td>' . $EnteredGLCode->GLCode . '</td>
						<td>' . $EnteredGLCode->GLActName . '</td>
						<td>' . $EnteredGLCode->Narrative . '</td>
						<td>' . $DescriptionTag . '</td>
						<td class="number">' . locale_number_format($EnteredGLCode->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
					</tr>';

				$TotalGLValue += $EnteredGLCode->Amount;

			}

			echo '<tr>
					<td colspan="4" class="number" style="color:blue">' . __('Total GL Analysis') . ':</td>
					<td class="number" style="color:blue">' . locale_number_format($TotalGLValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
				</tr>
				</table>';
		}

		$_SESSION['SuppTrans']->OvAmount = ($TotalGRNValue + $TotalGLValue + $TotalAssetValue + $TotalShiptValue + $TotalContractsValue);

		echo '<fieldset>
				<legend>', __('Invoice Summary'), '</legend>
				<field>
					<label for="OvAmount">' . __('Amount in supplier currency') . ':</label>
					<fieldtext>' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</fieldtext>
				</field>';
	}
	else {
		echo '<fieldset>
				<legend>', __('Invoice Summary'), '</legend>
				<field>
					<label for="OvAmount">' . __('Amount in supplier currency') . ':</label>
					<input type="text" class="number" title="" size="12" maxlength="10" name="OvAmount" value="' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />
					<fieldhelp>' . __('The input must be numeric') . '</fieldhelp>
				</field>';
	}

	echo '<field>
			<label><input type="submit" name="ToggleTaxMethod" value="' . __('Update Tax Calculation') . '" /></label>
			<select name="OverRideTax" onchange="ReloadForm(form1.ToggleTaxMethod)">';

	if (isset($_POST['OverRideTax']) AND $_POST['OverRideTax'] == 'Man') {
		echo '<option value="Auto">' . __('Automatic') . '</option>
				<option selected="selected" value="Man">' . __('Manually') . '</option>';
	}
	else {
		echo '<option selected="selected" value="Auto">' . __('Automatic') . '</option>
				<option  value="Man">' . __('Manually') . '</option>';
	}

	echo '</select>
		</field>';
	$TaxTotal = 0; //initialise tax total
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {

		echo '<field>
				<label>' . $Tax->TaxAuthDescription . '</label>';

		/*Set the tax rate to what was entered */
		if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
		}

		/*If a tax rate is entered that is not the same as it was previously then recalculate automatically the tax amounts */

		if (!isset($_POST['OverRideTax']) OR $_POST['OverRideTax'] == 'Auto') {

			echo ' <input type="text" class="number" name="TaxRate' . $Tax->TaxCalculationOrder . '" maxlength="4" size="4" value="' . locale_number_format($_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxRate * 100, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />  %';

			/*Now recaluclate the tax depending on the method */
			if ($Tax->TaxOnTax == 1) {

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * ($_SESSION['SuppTrans']->OvAmount + $TaxTotal);

			}
			else { /*Calculate tax without the tax on tax */

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * $_SESSION['SuppTrans']->OvAmount;

			}

			echo '<input type="hidden" name="TaxAmount' . $Tax->TaxCalculationOrder . '"  value="' . locale_number_format(round($_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) , $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />';

			echo '<span>    =    ' . locale_number_format($_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);

		}
		else { /*Tax being entered manually accept the taxamount entered as is*/
			//			if (!isset($_POST['TaxAmount'  . $Tax->TaxCalculationOrder])) {
			//				$_POST['TaxAmount'  . $Tax->TaxCalculationOrder]=0;
			//		}
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);

			echo ' <input type="hidden" name="TaxRate' . $Tax->TaxCalculationOrder . '" value="' . locale_number_format($_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxRate * 100, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />';

			echo '<input type="text" class="number" size="12" maxlength="12" name="TaxAmount' . $Tax->TaxCalculationOrder . '"  value="' . locale_number_format(round($_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) , $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />';
		}

		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax
			->TaxCalculationOrder]->TaxOvAmount;
		echo '</field>';
	}

	$_SESSION['SuppTrans']->OvAmount = round($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);

	$DisplayTotal = locale_number_format(($_SESSION['SuppTrans']->OvAmount + $TaxTotal) , $_SESSION['SuppTrans']->CurrDecimalPlaces);

	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-comment-alt"></i> ' . __('Final Comments') . '</h3>
			</div>
			<div class="db-card-body">
				<textarea name="Comments" style="width: 100%; min-height: 100px;" placeholder="' . __('Enter any relevant comments...') . '">' . $_SESSION['SuppTrans']->Comments . '</textarea>
			</div>
		</div>';

	echo '</main></div><!-- .db-bottom-layout -->';
	echo '</form>';
} else { // $_POST['PostInvoice'] is set so do the postings -and dont show the button to process
	/*First do input reasonableness checks
	 then do the updates and inserts to process the invoice entered */
	$TaxTotal = 0;
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		/*Set the tax rate to what was entered */
		if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
		}
		if ($_POST['OverRideTax'] == 'Auto' OR !isset($_POST['OverRideTax'])) {
			/*Now recaluclate the tax depending on the method */
			/*Now recaluclate the tax depending on the method */
			if ($Tax->TaxOnTax == 1) {

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * ($_SESSION['SuppTrans']->OvAmount + $TaxTotal);

			}
			else { /*Calculate tax without the tax on tax */

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * $_SESSION['SuppTrans']->OvAmount;

			}
		}
		else { /*Tax being entered manually accept the taxamount entered as is*/
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);
		}
		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax
			->TaxCalculationOrder]->TaxOvAmount;
	}

	$InputError = false;
	if ($TaxTotal + $_SESSION['SuppTrans']->OvAmount < 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the total amount of the invoice is less than  0') . '. ' . __('Invoices are expected to have a positive charge') , 'error');
		echo '<p>' . __('The tax total is') . ' : ' . locale_number_format($TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces);
		echo '<p>' . __('The ovamount is') . ' : ' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);

	}
	elseif ($TaxTotal + $_SESSION['SuppTrans']->OvAmount == 0) {

		prnMsg(__('The invoice as entered will be processed but be warned the amount of the invoice is  zero!') . '. ' . __('Invoices are normally expected to have a positive charge') , 'warn');

	}
	elseif (mb_strlen($_SESSION['SuppTrans']->SuppReference) < 1) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the there is no suppliers invoice number or reference entered') . '. ' . __('The supplier invoice number must be entered') , 'error');

	}
	elseif (!Is_date($_SESSION['SuppTrans']->TranDate)) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the invoice date entered is not in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');

	}
	elseif (DateDiff(date($_SESSION['DefaultDateFormat']) , $_SESSION['SuppTrans']->TranDate, 'd') < 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the invoice date is after today') . '. ' . __('Purchase invoices are expected to have a date prior to or today') , 'error');

	}
	elseif ($_SESSION['SuppTrans']->ExRate <= 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the exchange rate for the invoice has been entered as a negative or zero number') . '. ' . __('The exchange rate is expected to show how many of the suppliers currency there are in 1 of the local currency') , 'error');

	}
	elseif ($_SESSION['SuppTrans']->OvAmount < round($_SESSION['SuppTrans']->Total_Shipts_Value() + $_SESSION['SuppTrans']->Total_GL_Value() + $_SESSION['SuppTrans']->Total_Contracts_Value() + $_SESSION['SuppTrans']->Total_Assets_Value() + $_SESSION['SuppTrans']->Total_GRN_Value() , $_SESSION['SuppTrans']->CurrDecimalPlaces)) {

		prnMsg(__('The invoice total as entered is less than the sum of the shipment charges, the general ledger entries (if any), the charges for goods received, contract charges and fixed asset charges. There must be a mistake somewhere, the invoice as entered will not be processed') , 'error');
		$InputError = true;

	}
	else {

		$SQL = "SELECT count(*)
				FROM supptrans
				WHERE supplierno='" . $_SESSION['SuppTrans']->SupplierID . "'
				AND supptrans.suppreference='" . $_POST['SuppReference'] . "'";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sql to check for the previous entry of the same invoice failed');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] == 1) { /*Transaction reference already entered */
			prnMsg(__('The invoice number') . ' : ' . $_POST['SuppReference'] . ' ' . __('has already been entered') . '. ' . __('It cannot be entered again') , 'error');
			$InputError = true;
		}
	}

	if ($InputError == false) {

		/* SQL to process the postings for purchase invoice */
		/*Start an SQL transaction */

		DB_Txn_Begin();

		/*Get the next transaction number for internal purposes and the period to post GL transactions in based on the invoice date*/
		$InvoiceNo = GetNextTransNo(20);
		$PeriodNo = GetPeriod($_SESSION['SuppTrans']->TranDate);
		$SQLInvoiceDate = FormatDateForSQL($_SESSION['SuppTrans']->TranDate);

		if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {
			/*Loop through the GL Entries and create a debit posting for each of the accounts entered */
			$LocalTotal = 0;

			/*the postings here are a little tricky, the logic goes like this:
			if its a shipment entry then the cost must go against the GRN suspense account defined in the company record

			if its a general ledger amount it goes straight to the account specified

			if its a GRN amount invoiced then there are two possibilities:

			1 The PO line is on a shipment.
			The whole charge goes to the GRN suspense account pending the closure of the
			shipment where the variance is calculated on the shipment as a whole and the clearing entry to the GRN suspense
			is created. Also, shipment records are created for the charges in local currency.

			2. The order line item is not on a shipment
			The cost as originally credited to GRN suspense on arrival of goods is debited to GRN suspense.
			Depending on the setting of WeightedAverageCosting:
			If the order line item is a stock item and WeightedAverageCosting set to OFF then use standard costing .....
				Any difference
				between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
				to the purchase price variance account applicable to the stock item being invoiced.
			Otherwise
				Recalculate the new weighted average cost of the stock and update the cost - post the difference to the appropriate stock code

			Or if its not a stock item
			but a nominal item then the GL account in the orignal order is used for the price variance account.
			*/

			foreach ($_SESSION['SuppTrans']->GLCodes as $EnteredGLCode) {

				/*GL Items are straight forward - just do the debit postings to the GL accounts specified -
				 the credit is to creditors control act  done later for the total invoice value + tax*/
				//skamnev added tag
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (20,
										'" . $InvoiceNo . "',
										'" . $SQLInvoiceDate . "',
										'" . $PeriodNo . "',
										'" . $EnteredGLCode->GLCode . "',
										'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . $EnteredGLCode->Narrative, 0, 200) . "',
										'" . $EnteredGLCode->Amount / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);
				InsertGLTags($EnteredGLCode->Tag);

				$LocalTotal += $EnteredGLCode->Amount / $_SESSION['SuppTrans']->ExRate;
			}

			foreach ($_SESSION['SuppTrans']->Shipts as $ShiptChg) {

				/*shipment postings are also straight forward - just do the debit postings to the GRN suspense account
				 these entries are reversed from the GRN suspense when the shipment is closed*/

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
							VALUES (20,
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->GRNAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Shipment charge against') . ' ' . $ShiptChg->ShiptRef, 0, 200) . "',
									'" . $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);

				$LocalTotal += $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate;

			}

			foreach ($_SESSION['SuppTrans']->Assets as $AssetAddition) {
				/* only the GL entries if the creditors/GL integration is enabled */
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
								VALUES ('20',
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $AssetAddition->CostAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' ' . __('Asset Addition') . ' ' . $AssetAddition->AssetID . ': ' . $AssetAddition->Description, 0, 200) . "',
									'" . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the asset addition could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$LocalTotal += ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate);
			}

			foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {

				/*contract postings need to get the WIP from the contract items stock category record
				 *  debit postings to this WIP account
				 * the WIP account is tidied up when the contract is closed*/
				$Result = DB_query("SELECT wipact FROM stockcategory
									INNER JOIN stockmaster ON
									stockcategory.categoryid=stockmaster.categoryid
									WHERE stockmaster.stockid='" . $Contract->ContractRef . "'");
				$WIPRow = DB_fetch_row($Result);
				$WIPAccount = $WIPRow[0];
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES ('20',
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $WIPAccount . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' ' . __('Contract charge against') . ' ' . $Contract->ContractRef, 0, 200) . "',
											'" . ($Contract->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				$LocalTotal += ($Contract->Amount / $_SESSION['SuppTrans']->ExRate);
			}

			foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN) {

				if (mb_strlen($EnteredGRN->ShiptRef) == 0 OR $EnteredGRN->ShiptRef == 0) {
					/*so its not a GRN shipment item
					 enter the GL entry to reverse the GRN suspense entry created on delivery
					 * at standard cost/or weighted average cost used on delivery */

					/*Always do this - for weighted average costing and also for standard costing */

					if ($EnteredGRN->StdCostUnit * ($EnteredGRN->This_QuantityInv) != 0) {
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
								VALUES ('20',
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->GRNAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @  ' . __('std cost of') . ' ' . $EnteredGRN->StdCostUnit, 0, 200) . "',
								 	'" . ($EnteredGRN->StdCostUnit * $EnteredGRN->This_QuantityInv) . "')";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}

					$PurchPriceVar = $EnteredGRN->This_QuantityInv * (($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit);

					/*Yes.... but where to post this difference to - if its a stock item the variance account must be retrieved from the stock category record
					if its a nominal purchase order item with no stock item then there will be no standard cost and it will all be variance so post it to the
					account specified in the purchase order detail record */

					if ($PurchPriceVar != 0) { /* don't bother with this lot if there is no difference ! */
						if (mb_strlen($EnteredGRN->ItemCode) > 0 OR $EnteredGRN->ItemCode != '') { /*so it is a stock item */

							/*need to get the stock category record for this stock item - this is function in SQL_CommonFunctions.php */
							$StockGLCode = GetStockGLCode($EnteredGRN->ItemCode);

							/*We have stock item and a purchase price variance need to see whether we are using Standard or WeightedAverageCosting */

							if ($_SESSION['WeightedAverageCosting'] == 1) { /*Weighted Average costing */

								/* First off figure out the new weighted average cost Need the following data:
								- How many in stock now
								- The quantity being invoiced here - $EnteredGRN->This_QuantityInv
								- The cost of these items - $EnteredGRN->ChgPrice  / $_SESSION['SuppTrans']->ExRate */

								$TotalQuantityOnHand = GetQuantityOnHand($EnteredGRN->ItemCode, 'ALL');

								/*The cost adjustment is the price variance / the total quantity in stock
								But that is only provided that the total quantity in stock is greater than the quantity charged on this invoice

								If the quantity on hand is less the amount charged on this invoice then some must have been sold and the price variance on these must be written off to price variances*/

								$WriteOffToVariances = 0;

								if ($EnteredGRN->This_QuantityInv > $TotalQuantityOnHand) {

									/*So we need to write off some of the variance to variances and only the balance of the quantity in stock to go to stock value */

									/*if the TotalQuantityOnHand is negative then this variance to write off is inflated by the negative quantity - which makes sense */

									$WriteOffToVariances = ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) * (($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit);

									$SQL = "INSERT INTO gltrans (type,
																typeno,
																trandate,
																periodno,
																account,
																narrative,
																amount)
														VALUES (20,
															'" . $InvoiceNo . "',
															'" . $SQLInvoiceDate . "',
															'" . $PeriodNo . "',
															'" . $StockGLCode['purchpricevaract'] . "',
															'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
															'" . $WriteOffToVariances . "')";

									$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

									$Result = DB_query($SQL, $ErrMsg, '', true);
								} // end if the quantity being invoiced here is greater than the current stock on hand
								/*Now post any remaining price variance to stock rather than price variances */

								$SQL = "INSERT INTO gltrans (type,
															typeno,
															trandate,
															periodno,
															account,
															narrative,
															amount)
													VALUES (20,
													'" . $InvoiceNo . "',
													'" . $SQLInvoiceDate . "',
													'" . $PeriodNo . "',
													'" . $StockGLCode['stockact'] . "',
													'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Average Cost Adj') . ' - ' . $EnteredGRN->ItemCode . ' x ' . $TotalQuantityOnHand . ' x ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
													'" . ($PurchPriceVar - $WriteOffToVariances) . "')";

								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

								$Result = DB_query($SQL, $ErrMsg, '', true);

							}
							else { //It must be Standard Costing
								$SQL = "INSERT INTO gltrans (type,
															typeno,
															trandate,
															periodno,
															account,
															narrative,
															amount)
													VALUES (20,
														'" . $InvoiceNo . "',
														'" . $SQLInvoiceDate . "',
														'" . $PeriodNo . "',
														'" . $StockGLCode['purchpricevaract'] . "',
														'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
														'" . $PurchPriceVar . "')";

								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
						}
						else {
							/* its a nominal purchase order item that is not on a shipment so post the whole lot to the GLCode specified in the order, the purchase price var is actually the diff between the
							order price and the actual invoice price since the std cost was made equal to the order price in local currency at the time
							the goods were received */
							$GLCode = $EnteredGRN->GLCode; //by default
							if ($EnteredGRN->AssetID != 0) { //then it is an asset
								/*Need to get the asset details  for posting */
								$Result = DB_query("SELECT costact
													FROM fixedassets INNER JOIN fixedassetcategories
													ON fixedassets.assetcategoryid= fixedassetcategories.categoryid
													WHERE assetid='" . $EnteredGRN->AssetID . "'");
								if (DB_num_rows($Result) != 0) { // the asset exists
									$AssetRow = DB_fetch_array($Result);
									$GLCode = $AssetRow['costact'];
								}
							} //the item was an asset received on a purchase order
							$SQL = "INSERT INTO gltrans (type,
														typeno,
														trandate,
														periodno,
														account,
														narrative,
														amount)
									VALUES (20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $GLCode . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemDescription . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var') . ' ' . locale_number_format(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['SuppTrans']->CurrDecimalPlaces), 0, 200) . "',
											'" . $PurchPriceVar . "')";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

							$Result = DB_query($SQL, $ErrMsg, '', true);
						}
					}

				}
				else {
					/*then its a purchase order item on a shipment - whole charge amount to GRN suspense pending closure of the shipment when the variance is calculated and the GRN act cleared up for the shipment */

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES (20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $_SESSION['SuppTrans']->GRNAct . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @ ' . $_SESSION['SuppTrans']->CurrCode . ' ' . $EnteredGRN->ChgPrice . ' @ ' . __('a rate of') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
											'" . (($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
				$LocalTotal += ($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate;
			} /* end of GRN postings */

			foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
				/* Now the TAX account */
				if ($Tax->TaxOvAmount <> 0) {
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES (20,
												'" . $InvoiceNo . "',
												'" . $SQLInvoiceDate . "',
												'" . $PeriodNo . "',
												'" . $Tax->TaxGLCode . "',
												'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Inv') . ' ' . $_SESSION['SuppTrans']->SuppReference . ' ' . $Tax->TaxAuthDescription . ' ' . locale_number_format($Tax->TaxRate * 100, 2) . '% ' . $_SESSION['SuppTrans']->CurrCode . $Tax->TaxOvAmount . ' @ ' . __('exch rate') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
												'" . ($Tax->TaxOvAmount / $_SESSION['SuppTrans']->ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the tax could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

			} /*end of loop to post the tax */
			/* Now the control account */

			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
								VALUES (20,
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->CreditorsAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Inv') . ' ' . $_SESSION['SuppTrans']->SuppReference . ' ' . $_SESSION['SuppTrans']->CurrCode . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' @ ' . __('a rate of') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
									'" . -($LocalTotal + ($TaxTotal / $_SESSION['SuppTrans']->ExRate)) . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the control total could not be added because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			EnsureGLEntriesBalance(20, $InvoiceNo);
		} /*Thats the end of the GL postings */

		/*Now insert the invoice into the SuppTrans table*/

		$SQL = "INSERT INTO supptrans (transno,
										type,
										supplierno,
										suppreference,
										trandate,
										duedate,
										ovamount,
										ovgst,
										rate,
										transtext,
										inputdate)
							VALUES (
								'" . $InvoiceNo . "',
								20 ,
								'" . $_SESSION['SuppTrans']->SupplierID . "',
								'" . $_SESSION['SuppTrans']->SuppReference . "',
								'" . $SQLInvoiceDate . "',
								'" . FormatDateForSQL($_SESSION['SuppTrans']->DueDate) . "',
								'" . $_SESSION['SuppTrans']->OvAmount . "',
								'" . $TaxTotal . "',
								'" . $_SESSION['SuppTrans']->ExRate . "',
								'" . $_SESSION['SuppTrans']->Comments . "',
								CURRENT_DATE)";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier invoice transaction could not be added to the database because');
		$Result = DB_query($SQL, $ErrMsg, '', true);
		$SuppTransID = DB_Last_Insert_ID('supptrans', 'id');

		/* Insert the tax totals for each tax authority where tax was charged on the invoice */
		foreach ($_SESSION['SuppTrans']->Taxes AS $TaxTotals) {

			$SQL = "INSERT INTO supptranstaxes (supptransid,
												taxauthid,
												taxamount)
									VALUES (
										'" . $SuppTransID . "',
										'" . $TaxTotals->TaxAuthID . "',
										'" . $TaxTotals->TaxOvAmount . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier transaction taxes records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		/* Now update the GRN and PurchOrderDetails records for amounts invoiced  - can't use the other loop through the GRNs as this was only where the GL link to credtors is active */

		foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN) {

			//in local currency
			$ActualCost = $EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate;
			$PurchPriceVar = $EnteredGRN->This_QuantityInv * ($ActualCost - $EnteredGRN->StdCostUnit);

			$SQL = "UPDATE purchorderdetails
					SET qtyinvoiced = qtyinvoiced + " . $EnteredGRN->This_QuantityInv . ",
						actprice = '" . $EnteredGRN->ChgPrice . "'
					WHERE podetailitem = '" . $EnteredGRN->PODetailItem . "'";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced of the purchase order line could not be updated because');

			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "UPDATE grns
					SET quantityinv = quantityinv + " . $EnteredGRN->This_QuantityInv . "
					WHERE grnno = '" . $EnteredGRN->GRNNo . "'";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced off the goods received record could not be updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "INSERT INTO suppinvstogrn VALUES ('" . $InvoiceNo . "',
									'" . $EnteredGRN->GRNNo . "')";
			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The invoice could not be mapped to the
					goods received record because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			if (mb_strlen($EnteredGRN->ShiptRef) > 0 AND $EnteredGRN->ShiptRef != '0') {
				/* insert the shipment charge records */
				$SQL = "INSERT INTO shipmentcharges (shiptref,
													transtype,
													transno,
													stockid,
													value)
										VALUES (
											'" . $EnteredGRN->ShiptRef . "',
											20,
											'" . $InvoiceNo . "',
											'" . $EnteredGRN->ItemCode . "',
											'" . ($EnteredGRN->This_QuantityInv * $EnteredGRN->ChgPrice) / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $EnteredGRN->ShiptRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

			} //end of adding GRN shipment charges
			else {
				/*so its not a GRN shipment item its a plain old stock item */

				if ($PurchPriceVar != 0) { /* don't bother with any of this lot if there is no difference ! */

					if (mb_strlen($EnteredGRN->ItemCode) > 0 OR $EnteredGRN->ItemCode != '') { /*so it is a stock item */

						/*We need to:
						 *
						 * a) update the stockmove for the delivery to reflect the actual cost of the delivery
						 *
						 * b) If a WeightedAverageCosting system and the stock quantity on hand now is negative then the cost that has gone to sales analysis and the cost of sales stock movement records will have been incorrect ... attempt to fix it retrospectively
						*/
						/*Get the location that the stock was booked into */
						$Result = DB_query("SELECT intostocklocation
											FROM purchorders
											WHERE orderno='" . $EnteredGRN->PONo . "'");
						$LocRow = DB_fetch_array($Result);
						$LocCode = $LocRow['intostocklocation'];

						/* First update the stockmoves delivery cost */
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movement record for the delivery could not have the cost updated to the actual cost');
						$SQL = "UPDATE stockmoves SET price = '" . $ActualCost . "'
											WHERE stockid='" . $EnteredGRN->ItemCode . "'
											AND type=25
											AND loccode='" . $LocCode . "'
											AND transno='" . $EnteredGRN->GRNBatchNo . "'";

						$Result = DB_query($SQL, $ErrMsg, '', true);

						if ($_SESSION['WeightedAverageCosting'] == 1) {
							/*
							 * 	How many in stock now?
							 *  The quantity being invoiced here - $EnteredGRN->This_QuantityInv
							 *  If the quantity in stock now is less than the quantity being invoiced
							 *  here then some items sold will not have had this cost factored in
							 * The cost of these items = $ActualCost
							*/

							$TotalQuantityOnHand = GetQuantityOnHand($EnteredGRN->ItemCode, 'ALL');

							/* If the quantity on hand is less the quantity charged on this invoice then some must have been sold and the price variance should be reflected in the cost of sales*/

							if ($EnteredGRN->This_QuantityInv > $TotalQuantityOnHand) {

								/* The variance to the extent of the quantity invoiced should also be written off against the sales analysis cost - as sales analysis would have been created using the cost at the time the sale was made... this was incorrect as hind-sight has shown here. However, how to determine when these were last sold? To update the sales analysis cost. Work through the last 6 months sales analysis from the latest period in which this invoice is being posted and prior.

								The assumption here is that the goods have been sold prior to the purchase invoice  being entered so it is necessary to back track on the sales analysis cost.
								* Note that this will mean that posting to GL COGS will not agree to the cost of sales from the sales analysis
								* Of course the price variances will need to be included in COGS as well
								* */

								$QuantityVarianceAllocated = $EnteredGRN->This_QuantityInv;
								$CostVarPerUnit = $ActualCost - $EnteredGRN->StdCostUnit;
								$PeriodAllocated = $PeriodNo;
								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales analysis records could not be updated for the cost variances on this purchase invoice');

								while ($QuantityVarianceAllocated > 0) {
									$SalesAnalResult = DB_query("SELECT cust,
																	custbranch,
																	typeabbrev,
																	periodno,
																	stkcategory,
																	area,
																	salesperson,
																	cost,
																	qty
																FROM salesanalysis
																WHERE salesanalysis.stockid = '" . $EnteredGRN->ItemCode . "'
																AND salesanalysis.budgetoractual=1
																AND periodno='" . $PeriodAllocated . "'");
									if (DB_num_rows($SalesAnalResult) > 0) {
										while ($SalesAnalRow = DB_fetch_array($SalesAnalResult) AND $QuantityVarianceAllocated > 0) {
											if ($SalesAnalRow['qty'] <= $QuantityVarianceAllocated) {
												$QuantityVarianceAllocated -= $SalesAnalRow['qty'];
												$QuantityAllocated = $SalesAnalRow['qty'];
											}
											else {
												$QuantityAllocated = $QuantityVarianceAllocated;
												$QuantityVarianceAllocated = 0;
											}
											$UpdSalAnalResult = DB_query("UPDATE salesanalysis
																			SET cost = cost + " . ($CostVarPerUnit * $QuantityAllocated) . "
																			WHERE cust ='" . $SalesAnalRow['cust'] . "'
																			AND stockid='" . $EnteredGRN->ItemCode . "'
																			AND custbranch='" . $SalesAnalRow['custbranch'] . "'
																			AND typeabbrev='" . $SalesAnalRow['typeabbrev'] . "'
																			AND periodno='" . $PeriodAllocated . "'
																			AND area='" . $SalesAnalRow['area'] . "'
																			AND salesperson='" . $SalesAnalRow['salesperson'] . "'
																			AND stkcategory='" . $SalesAnalRow['stkcategory'] . "'
																			AND budgetoractual=1", $ErrMsg, '', true);
										}
									} //end if there were sales in that period
									$PeriodAllocated--; //decrement the period
									if ($PeriodNo - $PeriodAllocated > 6) {
										/*if more than 6 months ago when sales were made then forget it */
										break;
									}
								} /*end loop around different periods to see which sales analysis records to update */

								/*now we need to work back through the sales stockmoves up to the quantity on this purchase invoice to update costs
								 * Only go back up to 6 months looking for stockmoves and
								 * Only in the stock location where the purchase order was received
								 * into - if the stock was transferred to another location then
								 * we cannot adjust for this */
								$Result = DB_query("SELECT stkmoveno,
															type,
															qty,
															standardcost
													FROM stockmoves
													WHERE loccode='" . $LocCode . "'
													AND qty < 0
													AND stockid='" . $EnteredGRN->ItemCode . "'
													AND trandate>='" . FormatDateForSQL(DateAdd($_SESSION['SuppTrans']->TranDate, 'm', -6)) . "'
													ORDER BY stkmoveno DESC");
								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movements for invoices cannot be updated for the cost variances on this purchase invoice');
								$QuantityVarianceAllocated = $EnteredGRN->This_QuantityInv;
								while ($StkMoveRow = DB_fetch_array($Result) AND $QuantityVarianceAllocated > 0) {
									if ($StkMoveRow['qty'] + $QuantityVarianceAllocated > 0) {
										if ($StkMoveRow['type'] == 10) { //its a sales invoice
											$Result = DB_query("UPDATE stockmoves
																SET standardcost = '" . $ActualCost . "'
																WHERE stkmoveno = '" . $StkMoveRow['stkmoveno'] . "'", $ErrMsg, '', true);
										}
									}
									else { //Only $QuantityVarianceAllocated left to allocate so need need to apportion cost using weighted average
										if ($StkMoveRow['type'] == 10) { //its a sales invoice
											$WACost = (((-$StkMoveRow['qty'] - $QuantityVarianceAllocated) * $StkMoveRow['standardcost']) + ($QuantityVarianceAllocated * $ActualCost)) / -$StkMoveRow['qty'];

											$UpdStkMovesResult = DB_query("UPDATE stockmoves
																SET standardcost = '" . $WACost . "'
																WHERE stkmoveno = '" . $StkMoveRow['stkmoveno'] . "'", $ErrMsg, '', true);
										}
									}
									$QuantityVarianceAllocated += $StkMoveRow['qty'];
								}
							} // end if the quantity being invoiced here is greater than the current stock on hand
							/*Now to update the stock cost with the new weighted average */

							/*Need to consider what to do if the cost has been changed manually between receiving the stock and entering the invoice - this code assumes there has been no cost updates made manually and all the price variance is posted to stock.

							A nicety or important?? */

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost could not be updated because');

							if ($TotalQuantityOnHand > 0) {

								$CostIncrement = ($PurchPriceVar - $WriteOffToVariances) / $TotalQuantityOnHand;

								$SQL = "UPDATE stockmaster
										SET lastcost=materialcost+overheadcost+labourcost,
										materialcost=materialcost+" . $CostIncrement . ",                                          lastcostupdate = CURRENT_DATE
										WHERE stockid='" . $EnteredGRN->ItemCode . "'";
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
							else {
								/* if stock is negative then update the cost to this cost */
								$SQL = "UPDATE stockmaster
										SET lastcost=materialcost+overheadcost+labourcost,
											materialcost='" . $ActualCost . "',
                                            lastcostupdate = CURRENT_DATE
										WHERE stockid='" . $EnteredGRN->ItemCode . "'";
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
						} /* End if it is weighted average costing we are working with */
					} /*Its a stock item */
				} /* There was a price variance */
			}
			if ($EnteredGRN->AssetID != 0) { //then it is an asset
				if ($PurchPriceVar != 0) {
					/*Add the fixed asset trans for the difference in the cost */
					$SQL = "INSERT INTO fixedassettrans (assetid,
														transtype,
														transno,
														transdate,
														periodno,
														inputdate,
														fixedassettranstype,
														amount)
											VALUES ('" . $EnteredGRN->AssetID . "',
													20,
													'" . $InvoiceNo . "',
													'" . $SQLInvoiceDate . "',
													'" . $PeriodNo . "',
													CURRENT_DATE,
													'cost',
													'" . ($PurchPriceVar) . "')";
					$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					/*Now update the asset cost in fixedassets table */
					$SQL = "UPDATE fixedassets SET cost = cost + " . ($PurchPriceVar) . "
							WHERE assetid = '" . $EnteredGRN->AssetID . "'";

					$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost could not be updated because:');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} //end if there was a difference in the cost

			} //the item was an asset received on a purchase order

		} /* end of the GRN loop to do the updates for the quantity of order items the supplier has invoiced */

		/*Add shipment charges records as necessary */
		foreach ($_SESSION['SuppTrans']->Shipts as $ShiptChg) {

			$SQL = "INSERT INTO shipmentcharges (shiptref,
												transtype,
												transno,
												value)
									VALUES ('" . $ShiptChg->ShiptRef . "',
												'20',
											'" . $InvoiceNo . "',
											'" . $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

			$Result = DB_query($SQL, $ErrMsg, '', true);

		}
		/*Add contract charges records as necessary */

		foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {

			if ($Contract->AnticipatedCost == true) {
				$Anticipated = 1;
			}
			else {
				$Anticipated = 0;
			}
			$SQL = "INSERT INTO contractcharges (contractref,
												transtype,
												transno,
												amount,
												narrative,
												anticipated)
									VALUES ('" . $Contract->ContractRef . "',
										'20',
										'" . $InvoiceNo . "',
										'" . $Contract->Amount / $_SESSION['SuppTrans']->ExRate . "',
										'" . $Contract->Narrative . "',
										'" . $Anticipated . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The contract charge record for contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		foreach ($_SESSION['SuppTrans']->Assets as $AssetAddition) {

			/*Asset additions need to have
			 * 	1. A fixed asset transaction inserted for the cost
			 * 	2. A general ledger transaction to fixed asset cost account if creditors linked
			 * 	3. The fixedasset table cost updated by the addition
			*/

			/* First the fixed asset transaction */
			$SQL = "INSERT INTO fixedassettrans (assetid,
												transtype,
												transno,
												transdate,
												periodno,
												inputdate,
												fixedassettranstype,
												amount)
									VALUES ('" . $AssetAddition->AssetID . "',
											20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											CURRENT_DATE,
											'" . __('cost') . "',
											'" . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
			$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Now update the asset cost in fixedassets table */
			$Result = DB_query("SELECT datepurchased
								FROM fixedassets
								WHERE assetid='" . $AssetAddition->AssetID . "'");
			$AssetRow = DB_fetch_array($Result);

			$SQL = "UPDATE fixedassets SET cost = cost + " . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate);
			if ($AssetRow['datepurchased'] == '1000-01-01') {
				$SQL .= ", datepurchased='" . $SQLInvoiceDate . "'";
			}
			$SQL .= " WHERE assetid = '" . $AssetAddition->AssetID . "'";
			$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} //end of non-gl fixed asset stuff
		DB_Txn_Commit();

		prnMsg(__('Supplier invoice number') . ' ' . $InvoiceNo . ' ' . __('has been processed') , 'success');
		echo '<br />
				<div class="centre">
					<a href="' . $RootPath . '/SupplierInvoice.php?&SupplierID=' . $_SESSION['SuppTrans']->SupplierID . '">' . __('Enter another Invoice for this Supplier') . '</a>
					<br />
					<a href="' . $RootPath . '/Payments.php?&SupplierID=' . $_SESSION['SuppTrans']->SupplierID . '&amp;Amount=' . ($_SESSION['SuppTrans']->OvAmount + $TaxTotal) . '">' . __('Enter payment') . '</a>
				</div>';
		unset($_SESSION['SuppTrans']->GRNs);
		unset($_SESSION['SuppTrans']->Shipts);
		unset($_SESSION['SuppTrans']->GLCodes);
		unset($_SESSION['SuppTrans']->Contracts);
		unset($_SESSION['SuppTrans']);
	}

} /*end of process invoice */

if (isset($InputError) AND $InputError == true) { //add a link to return if users make input errors.
	echo '<div class="centre"><a href="' . $RootPath . '/SupplierInvoice.php" >' . __('Back to Invoice Entry') . '</a></div>';
} //end of return link for input errors
include(__DIR__ . '/includes/footer.php');
