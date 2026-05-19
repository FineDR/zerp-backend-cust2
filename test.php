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

	if (isset($_POST['SuppReference'])) {
		$_SESSION['SuppTrans']->SuppReference = $_POST['SuppReference'];
	}

	if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {
		/* Recalculate OvAmount from session components */
		$calcAmount = 0;
		if (count($_SESSION['SuppTrans']->GRNs) > 0) {
			foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
				$calcAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
			}
		}
		if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
			foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
				$calcAmount += $GLLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Shipts) > 0) {
			foreach ($_SESSION['SuppTrans']->Shipts as $ShiptLine) {
				$calcAmount += $ShiptLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Contracts) > 0) {
			foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
				$calcAmount += $Contract->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Assets) > 0) {
			foreach ($_SESSION['SuppTrans']->Assets as $FixedAsset) {
				$calcAmount += $FixedAsset->Amount;
			}
		}
		$_SESSION['SuppTrans']->OvAmount = round($calcAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
	elseif (isset($_POST['OvAmount'])) {
		/*OvAmount must be entered manually */
		$_SESSION['SuppTrans']->OvAmount = round(filter_number_format($_POST['OvAmount']) , $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
}

if (!isset($_POST['PostInvoice'])) {

	if (isset($_POST['GRNS']) AND $_POST['GRNS'] == __('Purchase Orders')) {
		/*This ensures that any changes in the page are stored in the session before calling the grn page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppInvGRNs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against goods received page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppInvGRNs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">' . __('click here') . '</a> ' . __('to continue') . '</div>
			<br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['Shipts']) AND $_POST['Shipts'] == __('Shipments')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppShiptChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against shipments page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppShiptChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">' . __('click here') . '</a> ' . __('to continue') . '.</div><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['GL']) AND $_POST['GL'] == __('General Ledger')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/SuppTransGLAnalysis.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against the general ledger page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppTransGLAnalysis.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">' . __('click here') . '</a> ' . __('to continue') . '.</div><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['Contracts']) AND $_POST['Contracts'] == __('Contracts')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/SuppContractChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoices against contracts page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppContractChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">' . __('click here') . '</a> ' . __('to continue') . '.</div>
			<br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['FixedAssets']) AND $_POST['FixedAssets'] == __('Fixed Assets')) {
		/*This ensures that any changes in the page are stored in the session before calling the shipments page */
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/SuppFixedAssetChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">';
		echo '<div class="centre">' . __('You should automatically be forwarded to the entry of invoice amounts against fixed assets page') . '. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/SuppFixedAssetChgs.php' . (isset($identifier) ? '?identifier=' . $identifier : '') . '">' . __('click here') . '</a> ' . __('to continue') . '.</DIV><br />';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	/* everything below here only do if a Supplier is selected
	 fisrt add a header to show who we are making an invoice for */


// ===== TAB HANDLING LOGIC =====
$ActiveTab = isset($_POST['ActiveTab']) ? $_POST['ActiveTab'] : 'tab-header';

if (isset($_POST['GoToCharges'])) $ActiveTab = 'tab-charges';
if (isset($_POST['GoToHeader'])) $ActiveTab = 'tab-header';
if (isset($_POST['GoToGL'])) $ActiveTab = 'tab-gl';
if (isset($_POST['GoToReview'])) $ActiveTab = 'tab-review';

// Integrate GL Line Addition logic from SuppTransGLAnalysis.php
if (isset($_POST['AddGLCodeToTrans'])) {
	$InputError = false;
	if ($_POST['GLCode'] == '') { $_POST['GLCode'] = $_POST['AcctSelection']; }
	if ($_POST['GLCode'] == '') {
		prnMsg(__('You must select a general ledger code'), 'warn');
		$InputError = true;
	}
	$SQL = "SELECT accountcode, accountname FROM chartmaster WHERE accountcode='" . $_POST['GLCode'] . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0 AND $_POST['GLCode'] != '') {
		prnMsg(__('Invalid account code'), 'error');
		$InputError = true;
	} elseif ($_POST['GLCode'] != '') {
		$MyRow = DB_fetch_row($Result);
		$GLActName = $MyRow[1];
		if (!is_numeric(filter_number_format($_POST['GLAmount']))) {
			prnMsg(__('Amount must be numeric'), 'error');
			$InputError = true;
		}
	}
	if ($InputError == false) {
		$_SESSION['SuppTrans']->Add_GLCodes_To_Trans($_POST['GLCode'], $GLActName, filter_number_format($_POST['GLAmount']), $_POST['GLNarrative'], $_POST['tag']);
		$ActiveTab = 'tab-charges'; // Go back to charges after adding
	} else {
		$ActiveTab = 'tab-gl'; // Stay on GL tab if error
	}
}

if (isset($_GET['DeleteGLCode'])) {
	$_SESSION['SuppTrans']->Remove_GLCodes_From_Trans($_GET['DeleteGLCode']);
	$ActiveTab = 'tab-charges';
}

echo '<div class="db-page">';
	echo '<style>
		.step-indicator {
			display: flex;
			justify-content: space-between;
			margin-bottom: 40px;
			position: relative;
			max-width: 800px;
			margin-left: auto;
			margin-right: auto;
		}
		.step-indicator::before {
			content: "";
			position: absolute;
			top: 15px;
			left: 0;
			right: 0;
			height: 2px;
			background: #e5e7eb;
			z-index: 1;
		}
		.step-item {
			position: relative;
			z-index: 2;
			background: #f8fafc;
			padding: 0 15px;
			text-align: center;
		}
		.step-dot {
			width: 32px;
			height: 32px;
			border-radius: 50%;
			background: #fff;
			border: 2px solid #e5e7eb;
			display: flex;
			align-items: center;
			justify-content: center;
			margin: 0 auto 8px;
			font-weight: 800;
			color: #94a3b8;
			transition: all 0.3s ease;
		}
		.step-item.active .step-dot {
			border-color: #059669;
			background: #059669;
			color: #fff;
			box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
		}
		.step-label {
			font-size: 0.75rem;
			font-weight: 700;
			text-transform: uppercase;
			color: #64748b;
			letter-spacing: 0.05em;
		}
		.step-item.active .step-label { color: #059669; }

		.invoice-tabs-content { display: none; }
		.invoice-tabs-content.active { display: block; animation: fadeIn 0.4s ease; }
		@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

		.db-aside-btn {
			width: 100%;
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 12px 16px;
			border-radius: 12px;
			border: 1px solid #e5e7eb;
			background: #fff;
			color: #374151;
			font-size: 0.9rem;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s ease;
			margin-bottom: 8px;
		}
		.db-aside-btn:hover {
			border-color: #059669;
			background: #f0fdf4;
			color: #059669;
			transform: translateX(4px);
		}
		.db-aside-btn i { color: #059669; width: 20px; }
		
		.charge-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 16px;
			background: #fff;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			margin-bottom: 12px;
		}
		.charge-info { display: flex; flex-direction: column; }
		.charge-title { font-weight: 700; color: #1e293b; }
		.charge-sub { font-size: 0.8rem; color: #64748b; }
		.charge-amt { font-weight: 800; color: #059669; font-size: 1.1rem; }
	</style>';

	echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><i class="fas fa-file-invoice" style="margin-right:12px; color:#059669;"></i> ' . __('Supplier Invoice Entry') . '</h2>
			<p class="db-page-subtitle">' . __('Processing invoice for') . ' <span class="val-bold" style="color:#064e3b;">' . $SupplierID . ' - ' . $SupplierName . '</span></p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSupplier.php" class="architect-btn secondary">
				<i class="fas fa-exchange-alt"></i> ' . __('Change Supplier') . '
			</a>
		</div>
	</div>';

	// Wizard Steps
	echo '<div class="step-indicator">
			<div class="step-item ' . ($ActiveTab == 'tab-header' ? 'active' : '') . '">
				<div class="step-dot">1</div>
				<div class="step-label">' . __('Header') . '</div>
			</div>
			<div class="step-item ' . ($ActiveTab == 'tab-charges' || $ActiveTab == 'tab-gl' ? 'active' : '') . '">
				<div class="step-dot">2</div>
				<div class="step-label">' . __('Charges') . '</div>
			</div>
			<div class="step-item ' . ($ActiveTab == 'tab-review' ? 'active' : '') . '">
				<div class="step-dot">3</div>
				<div class="step-label">' . __('Review') . '</div>
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

	if (isset($_POST['SuppReference'])) {
		$_SESSION['SuppTrans']->SuppReference = $_POST['SuppReference'];
	}

	if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {
		/* Recalculate OvAmount from session components */
		$calcAmount = 0;
		if (count($_SESSION['SuppTrans']->GRNs) > 0) {
			foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
				$calcAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
			}
		}
		if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
			foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
				$calcAmount += $GLLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Shipts) > 0) {
			foreach ($_SESSION['SuppTrans']->Shipts as $ShiptLine) {
				$calcAmount += $ShiptLine->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Contracts) > 0) {
			foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
				$calcAmount += $Contract->Amount;
			}
		}
		if (count($_SESSION['SuppTrans']->Assets) > 0) {
			foreach ($_SESSION['SuppTrans']->Assets as $FixedAsset) {
				$calcAmount += $FixedAsset->Amount;
			}
		}
		$_SESSION['SuppTrans']->OvAmount = round($calcAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
	elseif (isset($_POST['OvAmount'])) {
		/*OvAmount must be entered manually */
		$_SESSION['SuppTrans']->OvAmount = round(filter_number_format($_POST['OvAmount']) , $_SESSION['SuppTrans']->CurrDecimalPlaces);
	}
}

if (!isset($_POST['PostInvoice'])) {
	/* everything below here only do if a Supplier is selected
	 fisrt add a header to show who we are making an invoice for */

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="form1">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="ActiveTab" id="ActiveTab" value="' . $ActiveTab . '" />';

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
				</div>
			</div>
		</div>';

	// Card 2: Quick Actions
	echo '<div class="db-card" style="margin-bottom: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-bolt"></i> ' . __('Navigation') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-2);">';
		echo '<button type="submit" name="GoToHeader" class="db-aside-btn"><i class="fas fa-edit"></i> ' . __('Edit Header') . '</button>';
		echo '<button type="submit" name="GoToCharges" class="db-aside-btn"><i class="fas fa-plus-circle"></i> ' . __('Add Charges') . '</button>';
		echo '<button type="submit" name="GoToReview" class="db-aside-btn"><i class="fas fa-check-double"></i> ' . __('Review & Post') . '</button>';
	echo '  </div>
		  </div>';

	// Pre-calculate Summary
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

	// Card 3: Live Summary
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
		echo '<div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
				<span class="db-muted">' . $Tax->TaxAuthDescription . ':</span>
				<span>' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
			  </div>';
	}
	
	echo '			<div style="margin: var(--space-2) 0; height: 1px; background: var(--border-soft);"></div>
					<div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: #059669;">
						<span class="val-bold">' . __('Grand Total') . ':</span>
}
