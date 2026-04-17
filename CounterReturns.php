<?php

// This script allows credits and refunds from the default Counter Sale account for an inventory location.

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

require(__DIR__ . '/includes/session.php');


if (isset($_POST['identifier'])) {
	$identifier = $_POST['identifier'];
} elseif (isset($_GET['identifier'])) {
	$identifier = $_GET['identifier'];
} else {
	$identifier = date('U');
}

if (!isset($_SESSION['Items' . $identifier])) {
	$_SESSION['Items' . $identifier] = new Cart;
	$_SESSION['ExistingOrder' . $identifier] = 0;
}

$ExtraHeadContent = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
                    <link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/pos.css">
                    <script type="text/javascript" src="' . $RootPath . '/javascripts/CounterReturnsFunctions.js"></script>';

include(__DIR__ . '/includes/header.php');

echo '<script type="text/javascript">
        window.addEventListener("DOMContentLoaded", function() {
            CounterReturns.SetIdentifier("' . $identifier . '");
            CounterReturns.SetFormId("' . $_SESSION['FormID'] . '");
            CounterReturns.SetDecimal(' . (isset($_SESSION['Items' . $identifier]->CurrDecimalPlaces) ? $_SESSION['Items' . $identifier]->CurrDecimalPlaces : 2) . ');
        });
      </script>';

include(__DIR__ . '/includes/GetPrice.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/GetSalesTransGLCodes.php');

if (isset($_GET['NewReturn']) OR !isset($_SESSION['Items' . $identifier])) {
	$_SESSION['Items' . $identifier] = new Cart;
	$_SESSION['ExistingOrder' . $identifier] = 0;

	// Initializing default customer/branch for Counter Return
	$SQL = "SELECT cashsalecustomer, cashsalebranch, locationname, taxprovinceid
			FROM locations WHERE loccode='" . $_SESSION['UserStockLocation'] . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		$LocRow = DB_fetch_array($Result);
		$_SESSION['Items' . $identifier]->DebtorNo = $LocRow['cashsalecustomer'];
		$_SESSION['Items' . $identifier]->Branch = $LocRow['cashsalebranch'];
		$_SESSION['Items' . $identifier]->Location = $_SESSION['UserStockLocation'];
		$_SESSION['Items' . $identifier]->DispatchTaxProvince = $LocRow['taxprovinceid'];
		$_SESSION['Items' . $identifier]->DefaultCurrency = $_SESSION['CompanyRecord']['currencydefault'];
		$_SESSION['Items' . $identifier]->ReturnDate = date($_SESSION['DefaultDateFormat']);
		
		// Get currency details
		$CurrResult = DB_query("SELECT decimalplaces FROM currencies WHERE currabrev='" . $_SESSION['Items' . $identifier]->DefaultCurrency . "'");
		if ($CurrRow = DB_fetch_array($CurrResult)) {
			$_SESSION['Items' . $identifier]->CurrDecimalPlaces = $CurrRow['decimalplaces'];
		}
	}
}

$ExRate = 1; // Counter returns usually in base currency

// Logic for processing the Return Credit Note
if (isset($_POST['ProcessReturn']) AND $_POST['ProcessReturn'] != '') {

	$InputError = false; //always assume the best
	//but check for the worst
	if ($_SESSION['Items' . $identifier]->LineCounter == 0) {
		prnMsg(__('There are no lines on this return. Please enter lines to return first'), 'error');
		$InputError = true;
	}
	if (abs(filter_number_format($_POST['AmountPaid']) - round($_SESSION['Items' . $identifier]->total + filter_number_format($_POST['TaxTotal']), $_SESSION['Items' . $identifier]->CurrDecimalPlaces)) >= CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
		prnMsg(__('The amount entered as payment to the customer does not equal the amount of the return. Please correct amount and re-enter'), 'error');
		$InputError = true;
	}

	if (!$InputError) { //all good so let's get on with the processing

		/* Now Get the area where the sale is to from the branches table */

		$SQL = "SELECT 	area,
						defaultshipvia
				FROM custbranch
				WHERE custbranch.debtorno ='" . $_SESSION['Items' . $identifier]->DebtorNo . "'
				AND custbranch.branchcode = '" . $_SESSION['Items' . $identifier]->Branch . "'";

		$ErrMsg = __('We were unable to load the area where the sale is to from the custbranch table');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		$Area = $MyRow[0];
		$DefaultShipVia = $MyRow[1];
		DB_free_result($Result);

		/*company record read in on login with info on GL Links and debtors GL account*/

		if ($_SESSION['CompanyRecord'] == 0) {
			/*The company data and preferences could not be retrieved for some reason */
			prnMsg(__('The company information and preferences could not be retrieved. See your system administrator'), 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}

		// *************************************************************************
		//   S T A R T   O F   C R E D I T  N O T E   S Q L   P R O C E S S I N G
		// *************************************************************************
		DB_Txn_Begin();

		/*Now Get the next invoice number - GetNextTransNo() function in SQL_CommonFunctions
		 * GetPeriod() in includes/DateFunctions.php */

		$CreditNoteNo = GetNextTransNo(11);
		$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));

		$ReturnDate = date('Y-m-d');

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
										ovamount,
										ovgst,
										rate,
										invtext,
										shipvia,
										alloc,
										salesperson )
			VALUES ('" . $CreditNoteNo . "',
					11,
					'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
					'" . $_SESSION['Items' . $identifier]->Branch . "',
					'" . $ReturnDate . "',
					'" . date('Y-m-d H-i-s') . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['Items' . $identifier]->CustRef . "',
					'" . $_SESSION['Items' . $identifier]->DefaultSalesType . "',
					'" . -$_SESSION['Items' . $identifier]->total . "',
					'" . filter_number_format(-$_POST['TaxTotal']) . "',
					'" . $ExRate . "',
					'" . $_SESSION['Items' . $identifier]->Comments . "',
					'" . $_SESSION['Items' . $identifier]->ShipVia . "',
					'" . (-$_SESSION['Items' . $identifier]->total - filter_number_format($_POST['TaxTotal'])) . "',
					'" . $_SESSION['Items' . $identifier]->SalesPerson . "' )";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction record could not be inserted because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		$DebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');

		/* Insert the tax totals for each tax authority where tax was charged on the invoice */
		foreach ($_SESSION['Items' . $identifier]->TaxTotals as $TaxAuthID => $TaxAmount) {

			$SQL = "INSERT INTO debtortranstaxes (debtortransid,
													taxauthid,
													taxamount)
										VALUES ('" . $DebtorTransID . "',
											'" . $TaxAuthID . "',
											'" . -$TaxAmount / $ExRate . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction taxes records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		//Loop around each item on the sale and process each in turn
		foreach ($_SESSION['Items' . $identifier]->LineItems as $ReturnItemLine) {
			/* Update location stock records if not a dummy stock item
			need the MBFlag later too so save it to $MBFlag */
			$Result = DB_query("SELECT mbflag FROM stockmaster WHERE stockid = '" . $ReturnItemLine->StockID . "'");
			$MyRow = DB_fetch_row($Result);
			$MBFlag = $MyRow[0];
			if ($MBFlag == 'B' OR $MBFlag == 'M') {
				$Assembly = false;

				/* Need to get the current location quantity
				will need it later for the stock movement */
				$SQL = "SELECT locstock.quantity
								FROM locstock
								WHERE locstock.stockid='" . $ReturnItemLine->StockID . "'
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

				$SQL = "UPDATE locstock
							SET quantity = locstock.quantity + " . $ReturnItemLine->Quantity . "
						WHERE locstock.stockid = '" . $ReturnItemLine->StockID . "'
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
						FROM bom,
							stockmaster
						WHERE bom.component=stockmaster.stockid
						AND bom.parent='" . $ReturnItemLine->StockID . "'
                        AND bom.effectiveafter <= CURRENT_DATE
                        AND bom.effectiveto > CURRENT_DATE";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Could not retrieve assembly components from the database for') . ' ' . $ReturnItemLine->StockID . __('because') . ' ';
				$AssResult = DB_query($SQL, $ErrMsg, '', true);

				while ($AssParts = DB_fetch_array($AssResult)) {

					$StandardCost += ($AssParts['standard'] * $AssParts['quantity']);
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
													newqoh
						) VALUES (
													'" . $AssParts['component'] . "',
													 11,
													'" . $CreditNoteNo . "',
													'" . $_SESSION['Items' . $identifier]->Location . "',
													'" . $ReturnDate . "',
													'" . $_SESSION['UserID'] . "',
													'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
													'" . $_SESSION['Items' . $identifier]->Branch . "',
													'" . $PeriodNo . "',
													'" . __('Assembly') . ': ' . $ReturnItemLine->StockID . "',
													'" . $AssParts['quantity'] * $ReturnItemLine->Quantity . "',
													'" . $AssParts['standard'] . "',
													0,
													newqoh + " . ($AssParts['quantity'] * $ReturnItemLine->Quantity) . " )";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records for the assembly components of') . ' ' . $ReturnItemLine->StockID . ' ' . __('could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);


					$SQL = "UPDATE locstock
							SET quantity = locstock.quantity + " . ($AssParts['quantity'] * $ReturnItemLine->Quantity) . "
							WHERE locstock.stockid = '" . $AssParts['component'] . "'
							AND loccode = '" . $_SESSION['Items' . $identifier]->Location . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Location stock record could not be updated for an assembly component because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /* end of assembly explosion and updates */

				/*Update the cart with the recalculated standard cost from the explosion of the assembly's components*/
				$_SESSION['Items' . $identifier]->LineItems[$ReturnItemLine->LineNumber]->StandardCost = $StandardCost;
				$ReturnItemLine->StandardCost = $StandardCost;
			} /* end of its an assembly */

			// Insert stock movements - with unit cost
			$LocalCurrencyPrice = ($ReturnItemLine->Price / $ExRate);

			if (empty($ReturnItemLine->StandardCost)) {
				$ReturnItemLine->StandardCost = 0;
			}
			if ($MBFlag == 'B' OR $MBFlag == 'M') {
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
						VALUES ('" . $ReturnItemLine->StockID . "',
								11,
								'" . $CreditNoteNo . "',
								'" . $_SESSION['Items' . $identifier]->Location . "',
								'" . $ReturnDate . "',
								'" . $_SESSION['UserID'] . "',
								'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
								'" . $_SESSION['Items' . $identifier]->Branch . "',
								'" . $LocalCurrencyPrice . "',
								'" . $PeriodNo . "',
								'" . $OrderNo . "',
								'" . $ReturnItemLine->Quantity . "',
								'" . $ReturnItemLine->DiscountPercent . "',
								'" . $ReturnItemLine->StandardCost . "',
								'" . ($QtyOnHandPrior + $ReturnItemLine->Quantity) . "',
								'" . $ReturnItemLine->Narrative . "' )";
			} else {
				// its an assembly or dummy and assemblies/dummies always have nil stock (by definition they are made up at the time of dispatch  so new qty on hand will be nil
				if (empty($ReturnItemLine->StandardCost)) {
					$ReturnItemLine->StandardCost = 0;
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
												qty,
												discountpercent,
												standardcost,
												narrative )
						VALUES ('" . $ReturnItemLine->StockID . "',
								'11',
								'" . $CreditNoteNo . "',
								'" . $_SESSION['Items' . $identifier]->Location . "',
								'" . $ReturnDate . "',
								'" . $_SESSION['UserID'] . "',
								'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
								'" . $_SESSION['Items' . $identifier]->Branch . "',
								'" . $LocalCurrencyPrice . "',
								'" . $PeriodNo . "',
								'" . $ReturnItemLine->Quantity . "',
								'" . $ReturnItemLine->DiscountPercent . "',
								'" . $ReturnItemLine->StandardCost . "',
								'" . $ReturnItemLine->Narrative . "')";
			}

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID('stockmoves', 'stkmoveno');

			/*Insert the taxes that applied to this line */
			foreach ($ReturnItemLine->Taxes as $Tax) {

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
			} //end for each tax for the line


			/*Insert Sales Analysis records */
			$SalesValue = 0;
			if ($ExRate > 0) {
				$SalesValue = $ReturnItemLine->Price * $ReturnItemLine->Quantity / $ExRate;
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
					FROM salesanalysis,
						custbranch,
						stockmaster
					WHERE salesanalysis.stkcategory=stockmaster.categoryid
					AND salesanalysis.stockid=stockmaster.stockid
					AND salesanalysis.cust=custbranch.debtorno
					AND salesanalysis.custbranch=custbranch.branchcode
					AND salesanalysis.area=custbranch.area
					AND salesanalysis.salesperson='" . $_SESSION['Items' . $identifier]->SalesPerson . "'
					AND salesanalysis.typeabbrev ='" . $_SESSION['Items' . $identifier]->DefaultSalesType . "'
					AND salesanalysis.periodno='" . $PeriodNo . "'
					AND salesanalysis.cust " . LIKE . " '" . $_SESSION['Items' . $identifier]->DebtorNo . "'
					AND salesanalysis.custbranch " . LIKE . " '" . $_SESSION['Items' . $identifier]->Branch . "'
					AND salesanalysis.stockid " . LIKE . " '" . $ReturnItemLine->StockID . "'
					AND salesanalysis.budgetoractual=1
					GROUP BY salesanalysis.stockid,
						salesanalysis.stkcategory,
						salesanalysis.cust,
						salesanalysis.custbranch,
						salesanalysis.area,
						salesanalysis.periodno,
						salesanalysis.typeabbrev,
						salesanalysis.salesperson";

			$ErrMsg = __('The count of existing Sales analysis records could not run because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$MyRow = DB_fetch_row($Result);

			if ($MyRow[0] > 0) {  /*Update the existing record that already exists */

				$SQL = "UPDATE salesanalysis
							SET amt=amt-" . ($SalesValue) . ",
								cost=cost-" . ($ReturnItemLine->StandardCost * $ReturnItemLine->Quantity) . ",
								qty=qty -" . $ReturnItemLine->Quantity . ",
								disc=disc-" . ($ReturnItemLine->DiscountPercent * $SalesValue) . "
							WHERE salesanalysis.area='" . $MyRow[5] . "'
								AND salesanalysis.salesperson='" . $_SESSION['Items' . $identifier]->SalesPerson . "'
								AND typeabbrev ='" . $_SESSION['Items' . $identifier]->DefaultSalesType . "'
								AND periodno = '" . $PeriodNo . "'
								AND cust " . LIKE . " '" . $_SESSION['Items' . $identifier]->DebtorNo . "'
								AND custbranch " . LIKE . " '" . $_SESSION['Items' . $identifier]->Branch . "'
								AND stockid " . LIKE . " '" . $ReturnItemLine->StockID . "'
								AND salesanalysis.stkcategory ='" . $MyRow[2] . "'
								AND budgetoractual=1";

			} else { /* insert a new sales analysis record */

				$SQL = "INSERT INTO salesanalysis (	typeabbrev,
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
													stkcategory	)
					SELECT '" . $_SESSION['Items' . $identifier]->DefaultSalesType . "',
						'" . $PeriodNo . "',
						'" . -($SalesValue) . "',
						'" . -($ReturnItemLine->StandardCost * $ReturnItemLine->Quantity) . "',
						'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
						'" . $_SESSION['Items' . $identifier]->Branch . "',
						'" . -$ReturnItemLine->Quantity . "',
						'" . -($ReturnItemLine->DiscountPercent * $SalesValue) . "',
						'" . $ReturnItemLine->StockID . "',
						custbranch.area,
						1,
						'" . $_SESSION['Items' . $identifier]->SalesPerson . "',
						stockmaster.categoryid
					FROM stockmaster,
						custbranch
					WHERE stockmaster.stockid = '" . $ReturnItemLine->StockID . "'
					AND custbranch.debtorno = '" . $_SESSION['Items' . $identifier]->DebtorNo . "'
					AND custbranch.branchcode='" . $_SESSION['Items' . $identifier]->Branch . "'";
			}

			$ErrMsg = __('Sales analysis record could not be added or updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/* If GLLink_Stock then insert GLTrans to credit stock and debit cost of sales at standard cost*/

			if ($_SESSION['CompanyRecord']['gllink_stock'] == 1 AND $ReturnItemLine->StandardCost != 0) {

				/*first the cost of sales entry*/

				$SQL = "INSERT INTO gltrans (	type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES ( 11,
												'" . $CreditNoteNo . "',
												'" . $ReturnDate . "',
												'" . $PeriodNo . "',
												'" . GetCOGSGLAccount($Area, $ReturnItemLine->StockID, $_SESSION['Items' . $identifier]->DefaultSalesType) . "',
												'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $ReturnItemLine->StockID . " x " . -$ReturnItemLine->Quantity . " @ " . $ReturnItemLine->StandardCost, 0, 200) . "',
												'" . $ReturnItemLine->StandardCost * -$ReturnItemLine->Quantity . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/*now the stock entry*/
				$StockGLCode = GetStockGLCode($ReturnItemLine->StockID);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount )
										VALUES ( 11,
											'" . $CreditNoteNo . "',
											'" . $ReturnDate . "',
											'" . $PeriodNo . "',
											'" . $StockGLCode['stockact'] . "',
											'" . mb_substr($_SESSION['Items' . $identifier]->DebtorNo . " - " . $ReturnItemLine->StockID . " x " . -$ReturnItemLine->Quantity . " @ " . $ReturnItemLine->StandardCost, 0, 200) . "',
											'" . ($ReturnItemLine->StandardCost * $ReturnItemLine->Quantity) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock side of the cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			} /* end of if GL and stock integrated and standard cost !=0 */

			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1 AND $ReturnItemLine->Price != 0) {

				//Post sales transaction to GL credit sales
				$SalesGLAccounts = GetSalesGLAccount($Area, $ReturnItemLine->StockID, $_SESSION['Items' . $identifier]->DefaultSalesType);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount )
										VALUES ( 11,
											'" . $CreditNoteNo . "',
											'" . $ReturnDate . "',
											'" . $PeriodNo . "',
											'" . $SalesGLAccounts['salesglcode'] . "',
											'" . $_SESSION['Items' . $identifier]->DebtorNo . " - " . $ReturnItemLine->StockID . " x " . -$ReturnItemLine->Quantity . " @ " . $ReturnItemLine->Price . "',
											'" . ($ReturnItemLine->Price * $ReturnItemLine->Quantity / $ExRate) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				if ($ReturnItemLine->DiscountPercent != 0) {

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount )
												VALUES ( 11,
													'" . $CreditNoteNo . "',
													'" . $ReturnDate . "',
													'" . $PeriodNo . "',
													'" . $SalesGLAccounts['discountglcode'] . "',
													'" . $_SESSION['Items' . $identifier]->DebtorNo . " - " . $ReturnItemLine->StockID . " @ " . ($ReturnItemLine->DiscountPercent * 100) . "%',
													'" . -($ReturnItemLine->Price * $ReturnItemLine->Quantity * $ReturnItemLine->DiscountPercent / $ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales discount GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /*end of if discount !=0 */
			} /*end of if sales integrated with debtors */
		} /*end of OrderLine loop */

		if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {

			/*Post debtors transaction to GL debit debtors, credit freight re-charged and credit sales */
			if (($_SESSION['Items' . $identifier]->total + filter_number_format($_POST['TaxTotal'])) != 0) {
				$SQL = "INSERT INTO gltrans (	type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount	)
											VALUES ( 11,
												'" . $CreditNoteNo . "',
												'" . $ReturnDate . "',
												'" . $PeriodNo . "',
												'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
												'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
												'" . -(($_SESSION['Items' . $identifier]->total + filter_number_format($_POST['TaxTotal'])) / $ExRate) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The total debtor GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}


			foreach ($_SESSION['Items' . $identifier]->TaxTotals as $TaxAuthID => $TaxAmount) {
				if ($TaxAmount != 0) {
					$SQL = "INSERT INTO gltrans (	type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount	)
												VALUES ( 11,
													'" . $CreditNoteNo . "',
													'" . $ReturnDate . "',
													'" . $PeriodNo . "',
													'" . $_SESSION['Items' . $identifier]->TaxGLCodes[$TaxAuthID] . "',
													'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
													'" . ($TaxAmount / $ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The tax GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
			}

			EnsureGLEntriesBalance(11, $CreditNoteNo);

			/*Also if GL is linked to debtors need to process the debit to bank and credit to debtors for the payment */
			/*Need to figure out the cross rate between customer currency and bank account currency */

			if ($_POST['AmountPaid'] != 0) {
				$PaymentNumber = GetNextTransNo(12);
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
						VALUES (12,
							'" . $PaymentNumber . "',
							'" . $ReturnDate . "',
							'" . $PeriodNo . "',
							'" . $_POST['BankAccount'] . "',
							'" . $_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Return') . ' ' . $CreditNoteNo . "',
							'" . -(filter_number_format($_POST['AmountPaid']) / $ExRate) . "')";
				$ErrMsg = __('Cannot insert a GL transaction for the bank account debit');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/* Now Debit Debtors account with negative receipt/payment to customer */
				$SQL = "INSERT INTO gltrans ( type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
						VALUES (12,
							'" . $PaymentNumber . "',
							'" . $ReturnDate . "',
							'" . $PeriodNo . "',
							'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
							'" . $_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Return') . ' ' . $CreditNoteNo . "',
							'" . (filter_number_format($_POST['AmountPaid']) / $ExRate) . "')";
				$ErrMsg = __('Cannot insert a GL transaction for the debtors account credit');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}//amount paid was not zero

			EnsureGLEntriesBalance(12, $PaymentNumber);

		} /*end of if Sales and GL integrated */

		if ($_POST['AmountPaid'] != 0) {
			if (!isset($PaymentNumber)) {
				$PaymentNumber = GetNextTransNo(12);
			}
			//Now need to add the receipt banktrans record
			//First get the account currency that it has been banked into
			$Result = DB_query("SELECT rate FROM currencies
								INNER JOIN bankaccounts
								ON currencies.currabrev=bankaccounts.currcode
								WHERE bankaccounts.accountcode='" . $_POST['BankAccount'] . "'");
			$MyRow = DB_fetch_row($Result);
			$BankAccountExRate = $MyRow[0];

			/*
			 * Some interesting exchange rate conversion going on here
			 * Say :
			 * The business's functional currency is NZD
			 * Customer location counter sales are in AUD - 1 NZD = 0.80 AUD
			 * Banking money into a USD account - 1 NZD = 0.68 USD
			 *
			 * Customer sale is for $100 AUD
			 * GL entries  conver the AUD 100 to NZD  - 100 AUD / 0.80 = $125 NZD
			 * Banktrans entries convert the AUD 100 to USD using 100/0.8 * 0.68
			 */

			//insert the banktrans record in the currency of the bank account

			$SQL = "INSERT INTO banktrans (type,
										transno,
										bankact,
										ref,
										exrate,
										functionalexrate,
										transdate,
										banktranstype,
										amount,
										currcode)
					VALUES (12,
						'" . $PaymentNumber . "',
						'" . $_POST['BankAccount'] . "',
						'" . $_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $CreditNoteNo . "',
						'" . $ExRate . "',
						'" . $BankAccountExRate . "',
						'" . $ReturnDate . "',
						'" . $_POST['PaymentMethod'] . "',
						'" . -filter_number_format($_POST['AmountPaid']) * $BankAccountExRate . "',
						'" . $_SESSION['Items' . $identifier]->DefaultCurrency . "')";

			$ErrMsg = __('Cannot insert a bank transaction');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			//insert a new debtortrans for the receipt

			$SQL = "INSERT INTO debtortrans (transno,
											type,
											debtorno,
											trandate,
											inputdate,
											prd,
											reference,
											rate,
											ovamount,
											alloc,
											invtext)
					VALUES ('" . $PaymentNumber . "',
						12,
						'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
						'" . $ReturnDate . "',
						'" . date('Y-m-d H-i-s') . "',
						'" . $PeriodNo . "',
						'" . $CreditNoteNo . "',
						'" . $ExRate . "',
						'" . filter_number_format($_POST['AmountPaid']) . "',
						'" . filter_number_format($_POST['AmountPaid']) . "',
						'" . $_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Sale') . "')";

			$ErrMsg = __('Cannot insert a receipt transaction against the customer because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$ReceiptDebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');


			//and finally add the allocation record between receipt and invoice

			$SQL = "INSERT INTO custallocns (	amt,
												datealloc,
												transid_allocfrom,
												transid_allocto )
									VALUES  ('" . filter_number_format($_POST['AmountPaid']) . "',
											'" . $ReturnDate . "',
											 '" . $DebtorTransID . "',
											 '" . $ReceiptDebtorTransID . "')";
			$ErrMsg = __('Cannot insert the customer allocation of the receipt to the invoice because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} //end if $_POST['AmountPaid']!= 0

		DB_Txn_Commit();
		// *************************************************************************
		//   E N D   O F   C R E D I T  N O T E   S Q L   P R O C E S S I N G
		// *************************************************************************

		unset($_SESSION['Items' . $identifier]->LineItems);
		unset($_SESSION['Items' . $identifier]);

		prnMsg(__('Credit Note number') . ' ' . $CreditNoteNo . ' ' . __('processed'), 'success');

		echo '<br /><div class="centre">';

		if ($_SESSION['InvoicePortraitFormat'] == 0) {
			echo '<img src="' . $RootPath . '/css/' . $Theme . '/images/printer.png" title="' . __('Print') . '" alt="" />' . ' ' . '<a target="_blank" href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $CreditNoteNo . '&InvOrCredit=Credit&PrintPDF=True&orientation=landscape">' . __('Print this credit note') . ' (' . __('Landscape') . ')</a><br /><br />';
		} else {
			echo '<img src="' . $RootPath . '/css/' . $Theme . '/images/printer.png" title="' . __('Print') . '" alt="" />' . ' ' . '<a target="_blank" href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $CreditNoteNo . '&InvOrCredit=Credit&PrintPDF=True&orientation=portrait" onClick="return window.location=\'index.php\'">' . __('Print this credit note') . ' (' . __('Portrait') . ')</a><br /><br />';
		}
		echo '<br /><br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Start a new Counter Return') . '</a></div>';

	}
	// There were input errors so don't process nuffin
} else {
	//pretend the user never tried to commit the sale
	unset($_POST['ProcessReturn']);
}

/*******************************
 * end of Credit Note Processing
 * *****************************
 */

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-undo-alt" style="color: var(--danger-color);"></i> ' . $Title . '
			</div>
			<div class="db-page-actions">
				<a href="CounterReturns.php?NewReturn=Yes" class="db-btn db-btn-danger"><i class="fas fa-plus"></i> ' . __('New Return') . '</a>
			</div>
		</div>

		<div class="db-bottom-layout">
			<aside class="db-col-aside">
				<div class="db-card">
					<div class="db-card-header">
						<div class="db-card-title"><i class="fas fa-search"></i> ' . __('Add Items') . '</div>
					</div>
					<div class="db-card-body">
						<div class="db-field">
							<label for="ItemSearch">' . __('Search by Code or Name') . '</label>
							<div class="db-search-wrapper">
								<input type="text" id="ItemSearch" class="db-input" autocomplete="off" placeholder="' . __('Start typing...') . '" />
								<div id="SearchResults" class="db-search-results" style="display:none"></div>
							</div>
						</div>
					</div>
				</div>

				<div class="db-card" style="margin-top: 20px;">
					<div class="db-card-header">
						<div class="db-card-title"><i class="fas fa-user"></i> ' . __('Return Details') . '</div>
					</div>
					<div class="db-card-body">
						<form id="ReturnDetailsForm" method="post">
							<div class="db-field">
								<label>' . __('Customer Reference') . '</label>
								<input type="text" name="CustRef" class="db-input" value="' . $_SESSION['Items' . $identifier]->CustRef . '">
							</div>
							<div class="db-field">
								<label>' . __('Returned By') . '</label>
								<input type="text" name="DeliverTo" class="db-input" value="' . $_SESSION['Items' . $identifier]->DeliverTo . '">
							</div>
							<div class="db-field">
								<label>' . __('Contact Phone') . '</label>
								<input type="tel" name="PhoneNo" class="db-input" value="' . $_SESSION['Items' . $identifier]->PhoneNo . '">
							</div>
						</form>
					</div>
				</div>
			</aside>

			<main class="db-col-main">
				<div class="db-card">
					<div class="db-card-header">
						<div class="db-card-title"><i class="fas fa-shopping-cart"></i> ' . __('Return Cart') . '</div>
					</div>
					<div class="db-card-body" style="padding: 0;">
						<div id="CartItemsContainer">';
							$cart = $_SESSION['Items' . $identifier];
							if (count($cart->LineItems) == 0) {
								echo '<div class="centre" style="padding: 40px; color: var(--text-muted); font-style: italic;">' . __('Your return cart is empty') . '</div>';
							} else {
								// initial cart table render handled by cart_html pattern
							}
echo '					</div>
					</div>
				</div>

				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" onsubmit="return CounterReturns.OnSubmitReturn(this);">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-card" style="margin-top: 20px; border-top: 3px solid var(--danger-color);">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-money-check-alt"></i> ' . __('Refund Summary') . '</div>
						</div>
						<div class="db-card-body">
							<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
								<div>
									<div class="db-field">
										<label>' . __('Refund via') . '</label>
										<select name="PaymentMethod" class="db-select">';
											$PMResult = DB_query("SELECT paymentid, paymentname FROM paymentmethods");
											while ($PMRow = DB_fetch_array($PMResult)) {
												echo '<option value="' . $PMRow['paymentid'] . '">' . $PMRow['paymentname'] . '</option>';
											}
echo '									</select>
									</div>
									<div class="db-field">
										<label>' . __('Bank Account') . '</label>
										<select name="BankAccount" class="db-select">';
											$BAResult = DB_query("SELECT bankaccountname, accountcode FROM bankaccounts ORDER BY bankaccountname");
											while ($BARow = DB_fetch_array($BAResult)) {
												echo '<option value="' . $BARow['accountcode'] . '">' . $BARow['bankaccountname'] . '</option>';
											}
echo '									</select>
									</div>
								</div>
								<div>
									<div class="pos-summary-row">
										<span>' . __('Subtotal') . '</span>
										<span id="SummarySubtotal">' . locale_number_format($cart->total, $cart->CurrDecimalPlaces) . '</span>
									</div>
									<div class="pos-summary-row" id="TaxRow" ', (array_sum($cart->TaxTotals ?? []) == 0 ? 'style="display:none"' : ''), '>
										<span>' . __('Tax') . '</span>
										<span id="SummaryTax">' . locale_number_format(array_sum($cart->TaxTotals ?? []), $cart->CurrDecimalPlaces) . '</span>
									</div>
									<div class="pos-summary-total">
										<span id="SummaryGrandTotal">' . $cart->DefaultCurrency . ' ' . locale_number_format($cart->total + array_sum($cart->TaxTotals ?? []), $cart->CurrDecimalPlaces) . '</span>
									</div>
								</div>
							</div>

							<div class="db-field" style="margin-top: 20px;">
								<label>' . __('Reason for Return') . '</label>
								<textarea name="Comments" class="db-input" style="height: 80px;" required>' . stripslashes($cart->Comments) . '</textarea>
							</div>

							<input type="hidden" name="TaxTotal" id="HiddenTaxTotal" value="' . array_sum($cart->TaxTotals ?? []) . '" />
							<input type="hidden" name="AmountPaid" id="TotalAmountToRefund" value="' . ($cart->total + array_sum($cart->TaxTotals ?? [])) . '" />

							<div class="centre" style="margin-top: 20px;">
								<button type="submit" name="ProcessReturn" class="db-btn db-btn-primary" style="background: var(--danger-color); border-color: var(--danger-color); width: 100%; font-weight: 700; padding: 15px;">
									<i class="fas fa-check-circle"></i> ' . __('Process Refund') . '
								</button>
							</div>
						</div>
					</div>
				</form>
			</main>
		</div>
	  </div>
	  <script>
		window.addEventListener("DOMContentLoaded", function() {
			// Trigger initial cart render
			fetch("CounterReturns_Ajax.php", {
				method: "POST",
				headers: { "Content-Type": "application/x-www-form-urlencoded" },
				body: new URLSearchParams({ action: "initial", identifier: "' . $identifier . '", FormID: "' . $_SESSION['FormID'] . '" })
			})
			.then(response => response.json())
			.then(data => { if(data.success) CounterReturns._refreshCartUI(data); });
		});
	  </script>';

include(__DIR__ . '/includes/footer.php');
exit;

