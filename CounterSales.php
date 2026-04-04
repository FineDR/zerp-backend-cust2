<?php

// Allows sales to be entered against a cash sale customer account defined in the users location record.

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Counter Sales');
$ViewTopic = 'SalesOrders';
$BookMark = 'SalesOrderCounterSales';
$ExtraHeadContent = '<link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/pos.css">';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/GetPrice.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/GetSalesTransGLCodes.php');

$AlreadyWarnedAboutCredit = false;
$TaxTotal = 0;

if (empty($_GET['identifier'])) {
	$identifier=date('U');
} else {
	$identifier=$_GET['identifier'];
}
if (isset($_SESSION['Items'.$identifier]) AND isset($_POST['CustRef'])) {
	//update the Items object variable with the data posted from the form
	$_SESSION['Items'.$identifier]->CustRef = $_POST['CustRef'];
	$_SESSION['Items'.$identifier]->Comments = $_POST['Comments'];
	$_SESSION['Items'.$identifier]->DeliverTo = $_POST['DeliverTo'];
	$_SESSION['Items'.$identifier]->PhoneNo = $_POST['PhoneNo'];
	$_SESSION['Items'.$identifier]->Email = $_POST['Email'];
	if ($_SESSION['SalesmanLogin'] != '') {
		$_SESSION['Items' . $identifier]->SalesPerson = $_SESSION['SalesmanLogin'];
	} else {
		$_SESSION['Items' . $identifier]->SalesPerson = $_POST['SalesPerson'];
	}
}

if (isset($_POST['QuickEntry'])) {
	unset($_POST['PartSearch']);
}

if (isset($_POST['SelectingOrderItems'])) {
	foreach ($_POST as $FormVariable => $Quantity) {
		if (mb_strpos($FormVariable,'OrderQty')!==false) {
			$NewItemArray[$_POST['StockID' . mb_substr($FormVariable,8)]] = filter_number_format($Quantity);
		}
	}
}

if (isset($_GET['NewItem'])) {
	$NewItem = trim($_GET['NewItem']);
}

if (isset($_GET['CompletedInvoiceNo'])) {
	$CompletedInvoiceNo = $_GET['CompletedInvoiceNo'];
	$CompletedInvoiceOrientation = $_GET['CompletedInvoiceOrientation'] ?? 'portrait';
	if ($CompletedInvoiceOrientation != 'landscape') {
		$CompletedInvoiceOrientation = 'portrait';
	}
	$CompletedInvoiceURL = $RootPath . '/PrintCustTrans.php?FromTransNo=' . urlencode($CompletedInvoiceNo) . '&InvOrCredit=Invoice&PrintPDF=True&orientation=' . urlencode($CompletedInvoiceOrientation);
}

if (isset($_GET['NewOrder'])) {
	/*New order entry - clear any existing order details from the Items object and initiate a newy*/
	 if (isset($_SESSION['Items'.$identifier])) {
		unset ($_SESSION['Items'.$identifier]->LineItems);
		$_SESSION['Items'.$identifier]->ItemsOrdered=0;
		unset ($_SESSION['Items'.$identifier]);
	}
}


if (!isset($_SESSION['Items'.$identifier])) {
	/* It must be a new order being created $_SESSION['Items'.$identifier] would be set up from the order
	modification code above if a modification to an existing order. Also $ExistingOrder would be
	set to 1. The delivery check screen is where the details of the order are either updated or
	inserted depending on the value of ExistingOrder */

	$_SESSION['ExistingOrder'. $identifier] = 0;
	$_SESSION['Items'.$identifier] = new Cart;
	$_SESSION['PrintedPackingSlip'] = 0; /*Of course 'cos the order ain't even started !!*/
	/*Get the default customer-branch combo from the user's default location record */
	$SQL = "SELECT cashsalecustomer,
				cashsalebranch,
				locationname,
				taxprovinceid
			FROM locations
			WHERE loccode='" . $_SESSION['UserStockLocation'] ."'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)==0) {
		prnMsg(__('Your user account does not have a valid default inventory location set up. Please see the system administrator to modify your user account.'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	} else {
		$MyRow = DB_fetch_array($Result); //get the only row returned

		if ($MyRow['cashsalecustomer']=='' OR $MyRow['cashsalebranch']=='') {
			prnMsg(__('To use this script it is first necessary to define a cash sales customer for the location that is your default location. The default cash sale customer is defined under set up ->Inventory Locations Maintenance. The customer should be entered using the customer code and a valid branch code of the customer entered.'),'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		if (isset($_GET['DebtorNo'])) {
			$_SESSION['Items'.$identifier]->DebtorNo = $_GET['DebtorNo'];
			$_SESSION['Items'.$identifier]->Branch = $_GET['BranchNo'];
		} else {
			$_SESSION['Items'.$identifier]->Branch = $MyRow['cashsalebranch'];
			$_SESSION['Items'.$identifier]->DebtorNo = $MyRow['cashsalecustomer'];
		}

		$_SESSION['Items'.$identifier]->LocationName = $MyRow['locationname'];
		$_SESSION['Items'.$identifier]->Location = $_SESSION['UserStockLocation'];
		$_SESSION['Items'.$identifier]->DispatchTaxProvince = $MyRow['taxprovinceid'];

		// Now check to ensure this account exists and set defaults */
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
				WHERE debtorsmaster.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

		$ErrMsg = __('The details of the customer selected') . ': ' .  $_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('cannot be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);

		$MyRow = DB_fetch_array($Result);
		if ($MyRow['dissallowinvoices'] != 1) {
			if ($MyRow['dissallowinvoices']==2) {
				prnMsg($MyRow['name'] . ' ' . __('Although this account is defined as the cash sale account for the location.  The account is currently flagged as an account that needs to be watched. Please contact the credit control personnel to discuss'),'warn');
			}

			$_SESSION['RequireCustomerSelection']=0;
			$_SESSION['Items'.$identifier]->CustomerName = $MyRow['name'];
			// the sales type is the price list to be used for this sale
			$_SESSION['Items'.$identifier]->DefaultSalesType = $MyRow['salestype'];
			$_SESSION['Items'.$identifier]->SalesTypeName = $MyRow['sales_type'];
			$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currcode'];
			$_SESSION['Items'.$identifier]->DefaultPOLine = $MyRow['customerpoline'];
			$_SESSION['Items'.$identifier]->PaymentTerms = $MyRow['terms'];
			$_SESSION['Items'.$identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
			/* now get the branch defaults from the customer branches table CustBranch. */

			$SQL = "SELECT custbranch.brname,
				       custbranch.braddress1,
				       custbranch.defaultshipvia,
				       custbranch.deliverblind,
				       custbranch.specialinstructions,
				       custbranch.estdeliverydays,
				       custbranch.salesman,
				       custbranch.taxgroupid,
				       custbranch.defaultshipvia
				FROM custbranch
				WHERE custbranch.branchcode='" . $_SESSION['Items'.$identifier]->Branch . "'
				AND custbranch.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'";
            $ErrMsg = __('The customer branch record of the customer selected') . ': ' . $_SESSION['Items'.$identifier]->Branch . ' ' . __('cannot be retrieved because');
			$Result = DB_query($SQL, $ErrMsg);

			if (DB_num_rows($Result)==0) {

				prnMsg(__('The branch details for branch code') . ': ' . $_SESSION['Items'.$identifier]->Branch . ' ' . __('against customer code') . ': ' . $_SESSION['Items'.$identifier]->DebtorNo . ' ' . __('could not be retrieved') . '. ' . __('Check the set up of the customer and branch'),'error');

				include(__DIR__ . '/includes/footer.php');
				exit();
			}
			// add echo
			echo '<br />';
			$MyRow = DB_fetch_array($Result);

			$_SESSION['Items'.$identifier]->DeliverTo = '';
			$_SESSION['Items'.$identifier]->DelAdd1 = $MyRow['braddress1'];
			$_SESSION['Items'.$identifier]->ShipVia = $MyRow['defaultshipvia'];
			$_SESSION['Items'.$identifier]->DeliverBlind = $MyRow['deliverblind'];
			$_SESSION['Items'.$identifier]->SpecialInstructions = $MyRow['specialinstructions'];
			$_SESSION['Items'.$identifier]->DeliveryDays = $MyRow['estdeliverydays'];
			$_SESSION['Items'.$identifier]->TaxGroup = $MyRow['taxgroupid'];
			$_SESSION['Items'.$identifier]->SalesPerson = $MyRow['salesman'];

			if ($_SESSION['Items'.$identifier]->SpecialInstructions) {
				prnMsg($_SESSION['Items'.$identifier]->SpecialInstructions,'warn');
			}

			if ($_SESSION['CheckCreditLimits'] > 0 AND $AlreadyWarnedAboutCredit==false) {  /*Check credit limits is 1 for warn and 2 for prohibit sales */
				$_SESSION['Items'.$identifier]->CreditAvailable = GetCreditAvailable($_SESSION['Items'.$identifier]->DebtorNo);

				if ($_SESSION['CheckCreditLimits']==1 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0) {
					prnMsg(__('The') . ' ' . $MyRow['brname'] . ' ' . __('account is currently at or over their credit limit'),'warn');
					$AlreadyWarnedAboutCredit = true;
				} elseif ($_SESSION['CheckCreditLimits']==2 AND $_SESSION['Items'.$identifier]->CreditAvailable <=0) {
					prnMsg(__('No more orders can be placed by') . ' ' . $MyRow[0] . ' ' . __(' their account is currently at or over their credit limit'),'warn');
					$AlreadyWarnedAboutCredit = true;
					include(__DIR__ . '/includes/footer.php');
					exit();
				}
			}

		} else {
			prnMsg($MyRow['brname'] . ' ' . __('Although the account is defined as the cash sale account for the location  the account is currently on hold. Please contact the credit control personnel to discuss'),'warn');
		}

	}
} // end if its a new sale to be set up ...

if (isset($_POST['CancelOrder'])) {


	unset($_SESSION['Items'.$identifier]->LineItems);
	$_SESSION['Items'.$identifier]->ItemsOrdered = 0;
	unset($_SESSION['Items'.$identifier]);
	$_SESSION['Items'.$identifier] = new Cart;

	echo '<br /><br />';
	prnMsg(__('This sale has been cancelled as requested'),'success');
	echo '<br /><br /><a href="' .htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">' . __('Start a new Counter Sale') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();

} else { /*Not cancelling the order */

	echo '<div class="pos-shell-title">
		<p class="page_title_text">
			<strong>' . __('Point of Sale') . '</strong>
			<span>' . $_SESSION['Items'.$identifier]->CustomerName . ' ' . __('Counter Sale') . '</span>
			<small>' . $_SESSION['Items'.$identifier]->LocationName . ' • ' . $_SESSION['Items'.$identifier]->DefaultCurrency . ' • ' . date('l, F j, Y') . '</small>
		</p>
	</div>';

	if (isset($CompletedInvoiceNo)) {
		prnMsg('<a class="pos-success-link" target="_blank" rel="noopener" href="' . htmlspecialchars($CompletedInvoiceURL, ENT_QUOTES, 'UTF-8') . '">' . __('Invoice number') . ' ' . $CompletedInvoiceNo . ' ' . __('processed') . '</a>', 'success');
		echo '<div class="centre">';
		echo '<p><a target="_blank" rel="noopener" href="' . htmlspecialchars($CompletedInvoiceURL, ENT_QUOTES, 'UTF-8') . '">' . __('Open invoice PDF in a new tab') . '</a></p>';
		echo '<script>window.open(' . json_encode($CompletedInvoiceURL) . ', "_blank", "noopener");</script>';
		echo '</div>';
	}
}

if (isset($_POST['Search']) or isset($_POST['Next']) or isset($_POST['Previous']) or !isset($SearchResult)) {
	if (!isset($_POST['Keywords'])) $_POST['Keywords'] = '';
	if (!isset($_POST['StockCode'])) $_POST['StockCode'] = '';
	if (!isset($_POST['StockCat'])) $_POST['StockCat'] = 'All';

	if ($_POST['Keywords']!='' AND $_POST['StockCode']=='') {
		$Msg = __('Item description has been used in search');
	} elseif ($_POST['StockCode']!='' AND $_POST['Keywords']=='') {
		$Msg = __('Item Code has been used in search');
	} elseif ($_POST['Keywords']=='' AND $_POST['StockCode']=='') {
		$Msg = __('Stock Category has been used in search');
	}
	if (isset($_POST['Keywords']) AND mb_strlen($_POST['Keywords'])>0) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat']=='All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON  stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} elseif (mb_strlen($_POST['StockCode'])>0) {

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat']=='All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					  ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					AND (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat']=='All') {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON  stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					ORDER BY stockmaster.stockid";
			} else {
			$SQL = "SELECT stockmaster.stockid,
						stockmaster.description,
						stockmaster.units,
						stockmaster.decimalplaces
					FROM stockmaster INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
					AND stockmaster.mbflag <>'G'
					AND stockmaster.controlled <> 1
					AND stockmaster.discontinued=0
					AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
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
		$Offset = 0;
	}
	$ErrMsg = __('There is a problem selecting the part records to display because');
	$SearchResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($SearchResult)==0 ) {
		prnMsg(__('There are no products available meeting the criteria specified'),'info');
	}
	if (DB_num_rows($SearchResult)==1) {
		$MyRow=DB_fetch_array($SearchResult);
		$NewItem = $MyRow['stockid'];
		DB_data_seek($SearchResult,0);
	}
	if (DB_num_rows($SearchResult)< $_SESSION['DisplayRecordsMax']) {
		$Offset=0;
	}

} //end of if search


/* Always do the stuff below */

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" id="SelectParts" method="post">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" id="AutoFillCashReceived" name="AutoFillCashReceived" value="0" />';

echo '<div class="pos-container">';
echo '<div class="pos-catalog">';
echo '<div class="pos-search-box">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
		<input type="text" name="Keywords" class="pos-search-input" placeholder="' . __('Search products by name or code...') . '" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" />
		<input type="submit" name="Search" value="' . __('Search') . '" class="db-btn db-btn-primary" />
	</div>';

// Category Filters
$CatSQL = "SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype='F' OR stocktype='D' OR stocktype='L'";
$CatResult = DB_query($CatSQL);
echo '<div class="pos-categories">';
echo '<button type="submit" name="StockCat" value="All" class="pos-category-pill ' . ((!isset($_POST['StockCat']) || $_POST['StockCat'] == 'All') ? 'active' : '') . '">' . __('All Categories') . '</button>';
while ($CatRow = DB_fetch_array($CatResult)) {
	$active = (isset($_POST['StockCat']) && $_POST['StockCat'] == $CatRow['categoryid']) ? 'active' : '';
	echo '<button type="submit" name="StockCat" value="' . $CatRow['categoryid'] . '" class="pos-category-pill ' . $active . '">' . htmlspecialchars($CatRow['categorydescription']) . '</button>';
}
echo '</div>';

//Get The exchange rate used for GPPercent calculations on adding or amending items
if ($_SESSION['Items'.$identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault']) {
	$ExRateResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items'.$identifier]->DefaultCurrency . "'");
	if (DB_num_rows($ExRateResult)>0) {
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
		OR isset($_POST['Recalculate'])) {

	/* get the item details from the database and hold them in the cart object */

	/*Discount can only be set later on  -- after quick entry -- so default discount to 0 in the first place */
	$Discount = 0;
	$AlreadyWarnedAboutCredit = false;
	$i=1;
	if (!isset($_POST['TotalQuickEntryRows'])) {
		$_POST['TotalQuickEntryRows'] = 0;
	}
	while ($i<=max($_SESSION['QuickEntries'], $_POST['TotalQuickEntryRows'])
			AND isset($_POST['part_' . $i])
			AND $_POST['part_' . $i]!='') {

		$QuickEntryCode = 'part_' . $i;
		$QuickEntryQty = 'qty_' . $i;
		$QuickEntryPOLine = 'poline_' . $i;
		$QuickEntryItemDue = 'ItemDue_' . $i;

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

		if (!isset($NewItem)) {
			unset($NewItem);
			break;	/* break out of the loop if nothing in the quick entry fields*/
		}

		if (!Is_Date($NewItemDue)) {
			prnMsg(__('An invalid date entry was made for ') . ' ' . $NewItem . ' ' . __('The date entry') . ' ' . $NewItemDue . ' ' . __('must be in the format') . ' ' . $_SESSION['DefaultDateFormat'],'warn');
			//Attempt to default the due date to something sensible?
			$NewItemDue = DateAdd (date($_SESSION['DefaultDateFormat']),'d', $_SESSION['Items'.$identifier]->DeliveryDays);
		}
		/*Now figure out if the item is a kit set - the field MBFlag='K'*/
		$SQL = "SELECT stockmaster.mbflag,
						stockmaster.controlled
				FROM stockmaster
				WHERE stockmaster.stockid='". $NewItem ."'";

		$ErrMsg = __('Could not determine if the part being ordered was a kitset or not because');
		$KitResult = DB_query($SQL, $ErrMsg);


		if (DB_num_rows($KitResult)==0) {
			prnMsg( __('The item code') . ' ' . $NewItem . ' ' . __('could not be retrieved from the database and has not been added to the order'),'warn');
		} elseif ($MyRow=DB_fetch_array($KitResult)) {
			if ($MyRow['mbflag']=='K') {	/*It is a kit set item */
				$SQL = "SELECT bom.component,
							bom.quantity
						FROM bom
						WHERE bom.parent='" . $NewItem . "'
                        AND bom.effectiveafter <= CURRENT_DATE
                        AND bom.effectiveto > CURRENT_DATE";

				$ErrMsg =  __('Could not retrieve kitset components from the database because') . ' ';
				$KitResult = DB_query($SQL, $ErrMsg);

				$ParentQty = $NewItemQty;
				while ($KitParts = DB_fetch_array($KitResult)) {
					$NewItem = $KitParts['component'];
					$NewItemQty = $KitParts['quantity'] * $ParentQty;
					$NewPOLine = 0;
					include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
					$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
				}

			} elseif ($MyRow['mbflag']=='G') {
				prnMsg(__('Phantom assemblies cannot be sold, these items exist only as bills of materials used in other manufactured items. The following item has not been added to the order:') . ' ' . $NewItem, 'warn');
			} elseif ($MyRow['controlled']==1) {
				prnMsg(__('The system does not currently cater for counter sales of lot controlled or serialised items'),'warn');
			} elseif ($NewItemQty<=0) {
				prnMsg(__('Only items entered with a positive quantity can be added to the sale'),'warn');
			} else { /*Its not a kit set item*/
				include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
				$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
			}
		}
	 }
	 unset($NewItem);
 } /* end of if quick entry */

 /*Now do non-quick entry delete/edits/adds */

if ((isset($_SESSION['Items'.$identifier])) OR isset($NewItem)) {

	if (isset($_GET['Delete'])) {
		$_SESSION['Items'.$identifier]->remove_from_cart($_GET['Delete']);  /*Don't do any DB updates*/
	}
	$AlreadyWarnedAboutCredit = false;
	foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {

		if (isset($_POST['Quantity_' . $OrderLine->LineNumber])) {

			$Quantity = round(filter_number_format($_POST['Quantity_' . $OrderLine->LineNumber]),$OrderLine->DecimalPlaces);

				if (ABS($OrderLine->Price - filter_number_format($_POST['Price_' . $OrderLine->LineNumber])) > CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
					/*There is a new price being input for the line item */

					$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
					$_POST['GPPercent_' . $OrderLine->LineNumber] = (($Price*(1-(filter_number_format($_POST['Discount_' . $OrderLine->LineNumber])/100))) - $OrderLine->StandardCost*$ExRate)/($Price *(1-filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]))/100);

				} elseif (ABS($OrderLine->GPPercent - filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber])) >= CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
					/* A GP % has been input so need to do a recalculation of the price at this new GP Percentage */

					prnMsg(__('Recalculated the price from the GP % entered - the GP % was') . ' ' . $OrderLine->GPPercent . '  the new GP % is ' . filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]),'info');

					$Price = ($OrderLine->StandardCost*$ExRate)/(1 -((filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]) + filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]))/100));
				} else {
					$Price = filter_number_format($_POST['Price_' . $OrderLine->LineNumber]);
				}
				$DiscountPercentage = filter_number_format($_POST['Discount_' . $OrderLine->LineNumber]);
				if ($_SESSION['AllowOrderLineItemNarrative'] == 1) {
					$Narrative = $_POST['Narrative_' . $OrderLine->LineNumber];
				} else {
					$Narrative = '';
				}

				if (!isset($OrderLine->DiscountPercent)) {
					$OrderLine->DiscountPercent = 0;
				}

			if ($Quantity<0 OR $Price < 0 OR $DiscountPercentage >100 OR $DiscountPercentage <0) {
				prnMsg(__('The item could not be updated because you are attempting to set the quantity ordered to less than 0 or the price less than 0 or the discount more than 100% or less than 0%'),'warn');
			} elseif ($OrderLine->Quantity !=$Quantity
						OR $OrderLine->Price != $Price
						OR abs($OrderLine->DiscountPercent -$DiscountPercentage/100) > CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)
						OR $OrderLine->Narrative != $Narrative
						OR $OrderLine->ItemDue != $_POST['ItemDue_' . $OrderLine->LineNumber]
						OR $OrderLine->POLine != $_POST['POLine_' . $OrderLine->LineNumber]) {

				$_SESSION['Items'.$identifier]->update_cart_item($OrderLine->LineNumber,
																$Quantity,
																$Price,
																$DiscountPercentage/100,
																$Narrative,
																'Yes', /*Update DB */
																$_POST['ItemDue_' . $OrderLine->LineNumber],
																$_POST['POLine_' . $OrderLine->LineNumber],
																filter_number_format($_POST['GPPercent_' . $OrderLine->LineNumber]),
																$identifier);
			}
		} //page not called from itself - POST variables not set
	}
}

if (isset($_POST['Recalculate'])) {
	foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
		$NewItem=$OrderLine->StockID;
		$SQL = "SELECT stockmaster.mbflag,
						stockmaster.controlled
				FROM stockmaster
				WHERE stockmaster.stockid='". $OrderLine->StockID."'";

		$ErrMsg = __('Could not determine if the part being ordered was a kitset or not because');
		$KitResult = DB_query($SQL, $ErrMsg);
		if ($MyRow=DB_fetch_array($KitResult)) {
			if ($MyRow['mbflag']=='K') {	/*It is a kit set item */
				$SQL = "SELECT bom.component,
								bom.quantity
							FROM bom
							WHERE bom.parent='" . $OrderLine->StockID. "'
                            AND bom.effectiveafter <= CURRENT_DATE
                            AND bom.effectiveto > CURRENT_DATE";

				$ErrMsg = __('Could not retrieve kitset components from the database because');
				$KitResult = DB_query($SQL, $ErrMsg);

				$ParentQty = $NewItemQty;
				while ($KitParts = DB_fetch_array($KitResult)) {
					$NewItem = $KitParts['component'];
					$NewItemQty = $KitParts['quantity'] * $ParentQty;
					$NewPOLine = 0;
					$NewItemDue = date($_SESSION['DefaultDateFormat']);
					$_SESSION['Items'.$identifier]->GetTaxes($OrderLine->LineNumber);
				}

			} else { /*Its not a kit set item*/
				$NewItemDue = date($_SESSION['DefaultDateFormat']);
				$NewPOLine = 0;
				$_SESSION['Items'.$identifier]->GetTaxes($OrderLine->LineNumber);
			}
		}
		unset($NewItem);
	} /* end of if its a new item */
}

if (isset($NewItem)) {
/* get the item details from the database and hold them in the cart object make the quantity 1 by default then add it to the cart
Now figure out if the item is a kit set - the field MBFlag='K'
* controlled items and ghost/phantom items cannot be selected because the SQL to show items to select doesn't show 'em
* */
	$AlreadyWarnedAboutCredit = false;

	$SQL = "SELECT stockmaster.mbflag,
				stockmaster.taxcatid
			FROM stockmaster
			WHERE stockmaster.stockid='". $NewItem ."'";

	$ErrMsg =  __('Could not determine if the part being ordered was a kitset or not because');

	$KitResult = DB_query($SQL, $ErrMsg);

	$NewItemQty = 1; /*By Default */
	$Discount = 0; /*By default - can change later or discount category override */

	if ($MyRow=DB_fetch_array($KitResult)) {
	   	if ($MyRow['mbflag']=='K') {	/*It is a kit set item */
			$SQL = "SELECT bom.component,
						bom.quantity
					FROM bom
					WHERE bom.parent='" . $NewItem . "'
                    AND bom.effectiveafter <= CURRENT_DATE
                    AND bom.effectiveto > CURRENT_DATE";

			$ErrMsg = __('Could not retrieve kitset components from the database because');
			$KitResult = DB_query($SQL, $ErrMsg);

			$ParentQty = $NewItemQty;
			while ($KitParts = DB_fetch_array($KitResult)) {
				$NewItem = $KitParts['component'];
				$NewItemQty = $KitParts['quantity'] * $ParentQty;
				$NewPOLine = 0;
				$NewItemDue = date($_SESSION['DefaultDateFormat']);
				include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
				$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
			}

		} else { /*Its not a kit set item*/
			$NewItemDue = date($_SESSION['DefaultDateFormat']);
			$NewPOLine = 0;

			include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
			$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
		}

	} /* end of if its a new item */

} /*end of if its a new item */

if (isset($NewItemArray) AND isset($_POST['SelectingOrderItems'])) {
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

			if ($MyRow=DB_fetch_array($KitResult)) {
				if ($MyRow['mbflag']=='K') {	/*It is a kit set item */
					$SQL = "SELECT bom.component,
	        					bom.quantity
		          			FROM bom
							WHERE bom.parent='" . $NewItem . "'
                            AND bom.effectiveafter <= CURRENT_DATE
                            AND bom.effectiveto > CURRENT_DATE";

					$ErrMsg = __('Could not retrieve kitset components from the database because');
					$KitResult = DB_query($SQL, $ErrMsg);

					$ParentQty = $NewItemQty;
					while ($KitParts = DB_fetch_array($KitResult)) {
						$NewItem = $KitParts['component'];
						$NewItemQty = $KitParts['quantity'] * $ParentQty;
						$NewItemDue = date($_SESSION['DefaultDateFormat']);
						$NewPOLine = 0;
						include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
						$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
					}

				} else { /*Its not a kit set item*/
					$NewItemDue = date($_SESSION['DefaultDateFormat']);
					$NewPOLine = 0;
					include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
					$_SESSION['Items'.$identifier]->GetTaxes(($_SESSION['Items'.$identifier]->LineCounter - 1));
				}
			} /* end of if its a new item */
		} /*end of if its a new item */
	}
}


/* Now Run through each line of the order again to work out the appropriate discount from the discount matrix */
$DiscCatsDone = array();
foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {

	if ($OrderLine->DiscCat !='' AND ! in_array($OrderLine->DiscCat,$DiscCatsDone)) {
		$DiscCatsDone[]=$OrderLine->DiscCat;
		$QuantityOfDiscCat = 0;

		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine_2) {
			/* add up total quantity of all lines of this DiscCat */
			if ($OrderLine_2->DiscCat==$OrderLine->DiscCat) {
				$QuantityOfDiscCat += $OrderLine_2->Quantity;
			}
		}
		$Result = DB_query("SELECT MAX(discountrate) AS discount
							FROM discountmatrix
							WHERE salestype='" .  $_SESSION['Items'.$identifier]->DefaultSalesType . "'
							AND discountcategory ='" . $OrderLine->DiscCat . "'
							AND quantitybreak <= '" . $QuantityOfDiscCat ."'");
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]==NULL) {
			$DiscountMatrixRate = 0;
		} else {
			$DiscountMatrixRate = $MyRow[0];
		}
		if ($MyRow[0]!=0) { /* need to update the lines affected */
			foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine_2) {
				if ($OrderLine_2->DiscCat==$OrderLine->DiscCat) {
					$_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->DiscountPercent = $DiscountMatrixRate;
					$_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->GPPercent = (($_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->Price*(1-$DiscountMatrixRate)) - $_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->StandardCost*$ExRate)/($_SESSION['Items'.$identifier]->LineItems[$OrderLine_2->LineNumber]->Price *(1-$DiscountMatrixRate)/100);
				}
			}
		}
	}
} /* end of discount matrix lookup code */


if (count($_SESSION['Items'.$identifier]->LineItems)>0 ) { /*only show order lines if there are any */
/*
// *************************************************************************
//   T H I S   W H E R E   T H E   S A L E  I S   D I S P L A Y E D
// *************************************************************************
*/
	echo '<aside class="pos-sidebar">';
	echo '<div class="pos-cart-header">
			<div>
				<h3>' . __('Your Cart') . '</h3>
				<p>' . __('Manage items and process payment.') . '</p>
			</div>
			<div class="pos-badge">' . count($_SESSION['Items'.$identifier]->LineItems) . ' ' . __('items') . '</div>
		</div>';
    echo '<div class="pos-cart-items">';
	foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
		$SubTotal = round($OrderLine->Quantity * $OrderLine->Price * (1 - $OrderLine->DiscountPercent),$_SESSION['Items'.$identifier]->CurrDecimalPlaces);
		$TaxLineTotal = 0;
		foreach ($OrderLine->Taxes AS $Tax) {
			$TaxLineTotal += ($Tax->TaxOnTax == 1) ? ($Tax->TaxRate * ($SubTotal + $TaxLineTotal)) : ($Tax->TaxRate * $SubTotal);
		}

		echo '<div class="pos-cart-item">
				<div class="pos-cart-item-info">
					<h4>' . htmlspecialchars($OrderLine->ItemDescription) . '</h4>
					<div class="pos-cart-item-qty">
						<button type="button" class="qty-btn" onclick="this.nextElementSibling.value--; this.form.submit();">-</button>
						<input type="text" name="Quantity_' . $OrderLine->LineNumber . '" value="' . locale_number_format($OrderLine->Quantity,$OrderLine->DecimalPlaces) . '" size="3" style="text-align:center; border:none; background:transparent; font-weight:700;" />
						<button type="button" class="qty-btn" onclick="this.previousElementSibling.value++; this.form.submit();">+</button>
						<span style="margin-left: 10px; color: var(--text-muted); font-size: 0.8rem;">× ' . locale_number_format($OrderLine->Price,$_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
					</div>
				</div>
				<div style="text-align: right;">
					<div style="font-weight: 700; color: var(--text-main);">' . locale_number_format($SubTotal + $TaxLineTotal ,$_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</div>
					<a class="pos-delete-link" href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?identifier='.$identifier . '&amp;Delete=' . $OrderLine->LineNumber . '" onclick="return confirm(\'' . __('Are You Sure?') . '\');" style="font-size: 1.2rem; text-decoration: none;">&times;</a>
				</div>
				<input type="hidden" name="Price_' . $OrderLine->LineNumber . '" value="' . $OrderLine->Price . '" />
				<input type="hidden" name="Discount_' . $OrderLine->LineNumber . '" value="' . ($OrderLine->DiscountPercent * 100) . '" />
			  </div>';

		$_SESSION['Items'.$identifier]->total += $SubTotal;
		$TaxTotal += $TaxLineTotal;
	}
	echo '</div>'; // end pos-cart-items

	echo '<div class="pos-cart-footer">
			<div class="pos-summary-line">
				<span>' . __('Subtotal') . '</span>
				<span>' . locale_number_format($_SESSION['Items'.$identifier]->total, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
			</div>
			<div class="pos-summary-line">
				<span>' . __('Tax') . '</span>
				<span>' . locale_number_format($TaxTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
			</div>
			<div class="pos-total-line" style="border-top: 2px dashed var(--border); padding-top: 15px; margin-top: 15px;">
				<span>' . __('Total Amount') . '</span>
				<span>' . $_SESSION['Items'.$identifier]->DefaultCurrency . ' ' . locale_number_format($_SESSION['Items'.$identifier]->total + $TaxTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
			</div>';

    // Payment Section
    echo '<div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border);">
            <div style="font-weight: 700; margin-bottom: 10px;">' . __('Payment Method') . '</div>';
    $PaymentMethodsResult = DB_query("SELECT paymentid, paymentname FROM paymentmethods");
    echo '<select name="PaymentMethod" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border); margin-bottom: 10px;">';
    while ($PaymentMethodRow = DB_fetch_array($PaymentMethodsResult)) {
        $selected = (isset($_POST['PaymentMethod']) && $_POST['PaymentMethod'] == $PaymentMethodRow['paymentid']) ? 'selected' : '';
        echo '<option ' . $selected . ' value="' . $PaymentMethodRow['paymentid'] . '">' . $PaymentMethodRow['paymentname'] . '</option>';
    }
    echo '</select>';

    if (!isset($_POST['CashReceived'])) $_POST['CashReceived'] = 0;
    if (!isset($_POST['AmountPaid'])) $_POST['AmountPaid'] = 0;
    if (!isset($_POST['ChangeDue'])) $_POST['ChangeDue'] = 0;

    echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">' . __('Cash Received') . '</label>
                <input type="text" class="number" id="CashReceived" name="CashReceived" value="' . $_POST['CashReceived'] . '" onblur="CounterSales.CalculateChangeDue()" style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px; font-weight: 700;" />
            </div>
            <div>
                <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">' . __('Change Due') . '</label>
                <input type="text" class="number" id="ChangeDue" name="ChangeDue" value="' . $_POST['ChangeDue'] . '" readonly style="width: 100%; padding: 8px; border: 1px solid var(--border); border-radius: 6px; background: var(--bg-main); color: var(--primary); font-weight: 800;" />
            </div>
          </div>
          <input type="hidden" id="AmountPaid" name="AmountPaid" value="' . $_POST['AmountPaid'] . '" />
        </div>';

    // Quick Actions
    echo '<div class="pos-quick-actions">
            <button type="button" class="pos-action-btn" onclick="window.open(\'CounterSales.php?identifier=' . $identifier . '&NewOrder=Yes\', \'_self\')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                ' . __('New') . '
            </button>
            <button type="submit" name="Recalculate" class="pos-action-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                ' . __('Sync') . '
            </button>
            <button type="reset" name="CancelOrder" class="pos-action-btn" onclick="return confirm(\'' . __('Cancel sale?') . '\')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                ' . __('Cancel') . '
            </button>
        </div>';

	echo '<input type="submit" name="ProcessSale" value="' . __('Complete Payment') . '" class="pos-pay-btn" />';
	echo '</div>'; // end pos-cart-footer
	echo '</aside>';

    // Customer details as hidden inputs to preserve state if not edited
    echo '<input type="hidden" name="DeliverTo" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->DeliverTo) . '" />';
    echo '<input type="hidden" name="PhoneNo" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->PhoneNo) . '" />';
    echo '<input type="hidden" name="Email" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->Email) . '" />';
    echo '<input type="hidden" name="CustRef" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->CustRef) . '" />';
    echo '<input type="hidden" name="SalesPerson" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->SalesPerson) . '" />';
    echo '<input type="hidden" name="Comments" value="' . htmlspecialchars($_SESSION['Items' . $identifier]->Comments) . '" />';
	echo '<input type="hidden" name="TaxTotal" value="'.$TaxTotal.'" />';
    echo '</div>'; // end pos-catalog (closing the catalog div opened at line 778)

} else {
	echo '<section class="pos-cart-card pos-empty-cart">
			<div class="empty-icon"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" alt="" /></div>
			<h3>' . __('No items added yet') . '</h3>
			<p>' . __('Start by scanning a barcode, typing a product code, or using product search to add items into the sale.') . '</p>
		</section>';

} # end of if lines

/* **********************************
 * Invoice Processing Here
 * **********************************
 * */
if (isset($_POST['ProcessSale']) AND $_POST['ProcessSale'] != '') {
	$InputError = false; //always assume the best
	//but check for the worst
	if ($_SESSION['Items'.$identifier]->LineCounter == 0) {
		prnMsg(__('There are no lines on this sale. Please enter lines to invoice first'),'error');
		$InputError = true;
	}
	if (abs(filter_number_format($_POST['AmountPaid']) -(round($_SESSION['Items'.$identifier]->total+filter_number_format($_POST['TaxTotal']),$_SESSION['Items'.$identifier]->CurrDecimalPlaces))) >= CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
		prnMsg(__('The amount entered as payment does not equal the amount of the invoice. Please ensure the customer has paid the correct amount and re-enter'),'error');
		$InputError = true;
	}

	if ($_SESSION['ProhibitNegativeStock']==1) { // checks for negative stock after processing invoice
	    //sadly this check does not combine quantities occuring twice on and order and each line is considered individually :-(
		$NegativesFound = false;
		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
			$SQL = "SELECT stockmaster.description,
					   		locstock.quantity,
					   		stockmaster.mbflag
		 			FROM locstock
		 			INNER JOIN stockmaster
					ON stockmaster.stockid=locstock.stockid
					WHERE stockmaster.stockid='" . $OrderLine->StockID . "'
					AND locstock.loccode='" . $_SESSION['Items'.$identifier]->Location . "'";

			$ErrMsg = __('Could not retrieve the quantity left at the location once this order is invoiced (for the purposes of checking that stock will not go negative because)');
			$Result = DB_query($SQL, $ErrMsg);
			$CheckNegRow = DB_fetch_array($Result);
			if ($CheckNegRow['mbflag']=='B' OR $CheckNegRow['mbflag']=='M') {
				if ($CheckNegRow['quantity'] < $OrderLine->Quantity) {
					prnMsg( __('Invoicing the selected order would result in negative stock. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'),'error',$OrderLine->StockID . ' ' . $CheckNegRow['description'] . ' - ' . __('Negative Stock Prohibited'));
					$NegativesFound = true;
				}
			} elseif ($CheckNegRow['mbflag']=='A') {

				/*Now look for assembly components that would go negative */
				$SQL = "SELECT bom.component,
							   stockmaster.description,
							   locstock.quantity-(" . $OrderLine->Quantity  . "*bom.quantity) AS qtyleft
						FROM bom
						INNER JOIN locstock
						ON bom.component=locstock.stockid
						INNER JOIN stockmaster
						ON stockmaster.stockid=bom.component
						WHERE bom.parent='" . $OrderLine->StockID . "'
						AND locstock.loccode='" . $_SESSION['Items'.$identifier]->Location . "'
                        AND bom.effectiveafter <= CURRENT_DATE
                        AND bom.effectiveto > CURRENT_DATE";

				$ErrMsg = __('Could not retrieve the component quantity left at the location once the assembly item on this order is invoiced (for the purposes of checking that stock will not go negative because)');
				$Result = DB_query($SQL, $ErrMsg);
				while ($NegRow = DB_fetch_array($Result)) {
					if ($NegRow['qtyleft']<0) {
						prnMsg(__('Invoicing the selected order would result in negative stock for a component of an assembly item on the order. The system parameters are set to prohibit negative stocks from occurring. This invoice cannot be created until the stock on hand is corrected.'),'error',$NegRow['component'] . ' ' . $NegRow['description'] . ' - ' . __('Negative Stock Prohibited'));
						$NegativesFound = true;
					} // end if negative would result
				} //loop around the components of an assembly item
			}//end if its an assembly item - check component stock

		} //end of loop around items on the order for negative check

		if ($NegativesFound) {
			prnMsg(__('The parameter to prohibit negative stock is set and invoicing this sale would result in negative stock. No futher processing can be performed. Alter the sale first changing quantities or deleting lines which do not have sufficient stock.'),'error');
			$InputError = true;
		}

	}//end of testing for negative stocks

	if ($InputError == true ) { //allow the error to be fixed and then resubmit buttone needs to show
		echo '<br />
				<div class="centre">
					<input type="submit" name="Recalculate" value="' . __('Re-Calculate') . '" />
					<input type="submit" name="ProcessSale" value="' . __('Process The Sale') . '" />
				</div>
				<hr />';
	} else { //all good so let's get on with the processing

	/* Now Get the area where the sale is to from the branches table */

		$SQL = "SELECT area,
						defaultshipvia
				FROM custbranch
				WHERE custbranch.debtorno ='". $_SESSION['Items'.$identifier]->DebtorNo . "'
				AND custbranch.branchcode = '" . $_SESSION['Items'.$identifier]->Branch . "'";

		$ErrMsg = __('We were unable to load the area from the custbranch table where the sale is to ');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		$Area = $MyRow[0];
		$DefaultShipVia = $MyRow[1];
		DB_free_result($Result);

	/*company record read in on login with info on GL Links and debtors GL account*/

		if ($_SESSION['CompanyRecord']==0) {
			/*The company data and preferences could not be retrieved for some reason */
			prnMsg( __('The company information and preferences could not be retrieved. See your system administrator'), 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}

	// *************************************************************************
	//   S T A R T   O F   I N V O I C E   S Q L   P R O C E S S I N G
	// *************************************************************************
		DB_Txn_Begin();
	/*First add the order to the database - it only exists in the session currently! */
		$OrderNo = GetNextTransNo(30);
		$InvoiceNo = GetNextTransNo(10);
		$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));

		$HeaderSQL = "INSERT INTO salesorders (	orderno,
												debtorno,
												branchcode,
												customerref,
												comments,
												orddate,
												ordertype,
												shipvia,
												deliverto,
												deladd1,
												contactphone,
												contactemail,
												fromstkloc,
												deliverydate,
												confirmeddate,
												deliverblind,
												salesperson)
											VALUES (
												'" . $OrderNo . "',
												'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
												'" . $_SESSION['Items'.$identifier]->Branch . "',
												'". $_SESSION['Items'.$identifier]->CustRef ."',
												'". $_SESSION['Items'.$identifier]->Comments ."',
												'" . date('Y-m-d H:i') . "',
												'" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
												'" . $_SESSION['Items'.$identifier]->ShipVia . "',
												'". $_SESSION['Items'.$identifier]->DeliverTo . "',
												'" . __('Counter Sale') . "',
												'" . $_SESSION['Items'.$identifier]->PhoneNo . "',
												'" . $_SESSION['Items'.$identifier]->Email . "',
												'" . $_SESSION['Items'.$identifier]->Location ."',
												CURRENT_DATE,
												CURRENT_DATE,
												0,
												'" . $_SESSION['Items'.$identifier]->SalesPerson . "')";
		$ErrMsg = __('The order cannot be added because');
		$InsertQryResult = DB_query($HeaderSQL, $ErrMsg, '', true);

		$StartOf_LineItemsSQL = "INSERT INTO salesorderdetails (orderlineno,
																orderno,
																stkcode,
																unitprice,
																quantity,
																discountpercent,
																narrative,
																itemdue,
																actualdispatchdate,
																qtyinvoiced,
																completed)
															VALUES (";

		foreach ($_SESSION['Items'.$identifier]->LineItems as $StockItem) {

			$LineItemsSQL = $StartOf_LineItemsSQL .
					"'".$StockItem->LineNumber . "',
					'" . $OrderNo . "',
					'" . $StockItem->StockID . "',
					'". $StockItem->Price . "',
					'" . $StockItem->Quantity . "',
					'" . floatval($StockItem->DiscountPercent) . "',
					'" . $StockItem->Narrative . "',
					CURRENT_DATE,
					CURRENT_DATE,
					'" . $StockItem->Quantity . "',
					1)";

			$ErrMsg = __('Unable to add the sales order line');
			$Ins_LineItemResult = DB_query($LineItemsSQL, $ErrMsg, '', true);

			/*Now check to see if the item is manufactured
			 * 			and AutoCreateWOs is on
			 * 			and it is a real order (not just a quotation)*/

			if ($StockItem->MBflag=='M'
				AND $_SESSION['AutoCreateWOs']==1) { //oh yeah its all on!

				//now get the data required to test to see if we need to make a new WO
				$QOH = GetQuantityOnHand($StockItem->StockID, 'ALL');
				$QuantityDemand = GetDemand($StockItem->StockID, 'ALL');
				$QuantityOnOrder= GetQuantityOnOrder($StockItem->StockID, 'ALL');

				//Now we have the data - do we need to make any more?
				$ShortfallQuantity = $QOH-$QuantityDemand + $QuantityOnOrder;

				if ($ShortfallQuantity < 0) { //then we need to make a work order
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
											CURRENT_DATE)",
											$ErrMsg,
											'',
											true);
					//Need to get the latest BOM to roll up cost
					$CostResult = DB_query("SELECT SUM((materialcost+labourcost+overheadcost)*bom.quantity) AS cost
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
									 VALUES ('" . $WONo . "',
											 '" . $StockItem->StockID . "',
											 '" . $WOQuantity . "',
											 '" . $Cost . "')";
					$ErrMsg = __('The work order item could not be added');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					//Recursively insert real component requirements - see includes/SQL_CommonFunctions.in for function WoRealRequirements
					WoRealRequirements($WONo, $_SESSION['DefaultFactoryLocation'], $StockItem->StockID);

					$FactoryManagerEmail = __('A new work order has been created for') .
										":\n" . $StockItem->StockID . ' - ' . $StockItem->ItemDescription . ' x ' . $WOQuantity . ' ' . $StockItem->Units .
										"\n" . __('These are for') . ' ' . $_SESSION['Items'.$identifier]->CustomerName . ' ' . __('there order ref') . ': '  . $_SESSION['Items'.$identifier]->CustRef . ' ' .__('our order number') . ': ' . $OrderNo;

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
																'" . ($StockItem->NextSerialNo + $i)	 . "')";
								$ErrMsg = __('The serial number for the work order item could not be added');
								$Result = DB_query($SQL, $ErrMsg, '', true);
								$FactoryManagerEmail .= "\n" . ($StockItem->NextSerialNo + $i);
							}
						} //end loop around creation of woserialnos
						$NewNextSerialNo = ($StockItem->NextSerialNo + $WOQuantity +1);
						$ErrMsg = __('Could not update the new next serial number for the item');
						$UpdateSQL="UPDATE stockmaster SET nextserialno='" . $NewNextSerialNo . "' WHERE stockid='" . $StockItem->StockID . "'";
						$UpdateNextSerialNoResult = DB_query($UpdateSQL, $ErrMsg, '', true);
					} // end if the item is serialised and nextserialno is set
					// Send email to the Factory Manager
					$EmailSubject = __('New Work Order Number') . ' ' . $WONo . ' ' . __('for') . ' ' . $StockItem->StockID . ' x ' . $WOQuantity;
					SendEmailFromWebERP($SysAdminEmail,
										$_SESSION['FactoryManagerEmail'],
										$EmailSubject,
										$FactoryManagerEmail,
										'',
										false);

				} //end if with this sales order there is a shortfall of stock - need to create the WO
			}//end if auto create WOs in on
		} /* end inserted line items into sales order details */

		prnMsg(__('Order Number') . ' ' . $OrderNo . ' ' . __('has been entered'),'success');

	/* End of insertion of new sales order */

	/*Now Get the next invoice number - GetNextTransNo() function in SQL_CommonFunctions
	 * GetPeriod() in includes/DateFunctions.php */

		$DefaultDispatchDate = date('Y-m-d');

	/*Update order header for invoice charged on */
		$SQL = "UPDATE salesorders SET comments = CONCAT(comments,'" . ' ' . __('Invoice') . ': ' . "','" . $InvoiceNo . "') WHERE orderno= '" . $OrderNo."'";

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
										rate,
										invtext,
										shipvia,
										alloc,
										settled,
										salesperson )
			VALUES (
				'". $InvoiceNo . "',
				10,
				'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
				'" . $_SESSION['Items'.$identifier]->Branch . "',
				'" . $DefaultDispatchDate . "',
				'" . date('Y-m-d H-i-s') . "',
				'" . $PeriodNo . "',
				'" . $_SESSION['Items'.$identifier]->CustRef  . "',
				'" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
				'" . $OrderNo . "',
				'" . $_SESSION['Items'.$identifier]->total . "',
				'" . filter_number_format($_POST['TaxTotal']) . "',
				'" . $ExRate . "',
				'" . $_SESSION['Items'.$identifier]->Comments . "',
				'" . $_SESSION['Items'.$identifier]->ShipVia . "',
				'" . ($_SESSION['Items'.$identifier]->total + filter_number_format($_POST['TaxTotal'])) . "',
				'1',
				'" . $_SESSION['Items'.$identifier]->SalesPerson . "')";

		$ErrMsg =__('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction record could not be inserted because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		$DebtorTransID = DB_Last_Insert_ID('debtortrans','id');

	/* Insert the tax totals for each tax authority where tax was charged on the invoice */
		foreach ($_SESSION['Items'.$identifier]->TaxTotals AS $TaxAuthID => $TaxAmount) {

			$SQL = "INSERT INTO debtortranstaxes (debtortransid,
													taxauthid,
													taxamount)
										VALUES ('" . $DebtorTransID . "',
											'" . $TaxAuthID . "',
											'" . $TaxAmount/$ExRate . "')";

			$ErrMsg =__('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The debtor transaction taxes records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		//Loop around each item on the sale and process each in turn
		foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
			 /* Update location stock records if not a dummy stock item
			 need the MBFlag later too so save it to $MBFlag */
			$Result = DB_query("SELECT mbflag FROM stockmaster WHERE stockid = '" . $OrderLine->StockID . "'");
			$MyRow = DB_fetch_row($Result);
			$MBFlag = $MyRow[0];
			if ($MBFlag=='B' OR $MBFlag=='M') {
				$Assembly = false;

				/* Need to get the current location quantity
				will need it later for the stock movement */
				$SQL="SELECT locstock.quantity
								FROM locstock
								WHERE locstock.stockid='" . $OrderLine->StockID . "'
								AND loccode= '" . $_SESSION['Items'.$identifier]->Location . "'";
				$ErrMsg = __('WARNING') . ': ' . __('Could not retrieve current location stock');
				$Result = DB_query($SQL, $ErrMsg);

				if (DB_num_rows($Result)==1) {
					$LocQtyRow = DB_fetch_row($Result);
					$QtyOnHandPrior = $LocQtyRow[0];
				} else {
					/* There must be some error this should never happen */
					$QtyOnHandPrior = 0;
				}

				$SQL = "UPDATE locstock
							SET quantity = locstock.quantity - " . $OrderLine->Quantity . "
							WHERE locstock.stockid = '" . $OrderLine->StockID . "'
							AND loccode = '" . $_SESSION['Items'.$identifier]->Location . "'";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Location stock record could not be updated because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

			} elseif ($MBFlag=='A') { /* its an assembly */
				/*Need to get the BOM for this part and make
				stock moves for the components then update the Location stock balances */
				$Assembly=true;
				$StandardCost =0; /*To start with - accumulate the cost of the comoponents for use in journals later on */
				$SQL = "SELECT bom.component,
						bom.quantity,
						stockmaster.actualcost AS standard
						FROM bom,
							stockmaster
						WHERE bom.component=stockmaster.stockid
						AND bom.parent='" . $OrderLine->StockID . "'
                        AND bom.effectiveafter <= CURRENT_DATE
                        AND bom.effectiveto > CURRENT_DATE";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Could not retrieve assembly components from the database for'). ' '. $OrderLine->StockID . __('because').' ';
				$AssResult = DB_query($SQL, $ErrMsg, '', true);

				while ($AssParts = DB_fetch_array($AssResult)) {

					$StandardCost += ($AssParts['standard'] * $AssParts['quantity']) ;
					/* Need to get the current location quantity
					will need it later for the stock movement */
					$SQL="SELECT locstock.quantity
									FROM locstock
									WHERE locstock.stockid='" . $AssParts['component'] . "'
									AND loccode= '" . $_SESSION['Items'.$identifier]->Location . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Can not retrieve assembly components location stock quantities because ');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					if (DB_num_rows($Result)==1) {
						$LocQtyRow = DB_fetch_row($Result);
						$QtyOnHandPrior = $LocQtyRow[0];
					} else {
						/*There must be some error this should never happen */
						$QtyOnHandPrior = 0;
					}
					if (empty($AssParts['standard'])) {
						$AssParts['standard']=0;
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
												'" . $_SESSION['Items'.$identifier]->Location . "',
												'" . $DefaultDispatchDate . "',
												'" . $_SESSION['UserID'] . "',
												'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
												'" . $_SESSION['Items'.$identifier]->Branch . "',
												'" . $PeriodNo . "',
												'" . __('Assembly') . ': ' . $OrderLine->StockID . ' ' . __('Order') . ': ' . $OrderNo . "',
												'" . -$AssParts['quantity'] * $OrderLine->Quantity . "',
												'" . $AssParts['standard'] . "',
												0,
												newqoh-" . ($AssParts['quantity'] * $OrderLine->Quantity) . " )";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records for the assembly components of'). ' '. $OrderLine->StockID . ' ' . __('could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					$SQL = "UPDATE locstock
							SET quantity = locstock.quantity - " . $AssParts['quantity'] * $OrderLine->Quantity . "
							WHERE locstock.stockid = '" . $AssParts['component'] . "'
							AND loccode = '" . $_SESSION['Items'.$identifier]->Location . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Location stock record could not be updated for an assembly component because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /* end of assembly explosion and updates */

				/*Update the cart with the recalculated standard cost from the explosion of the assembly's components*/
				$_SESSION['Items'.$identifier]->LineItems[$OrderLine->LineNumber]->StandardCost = $StandardCost;
				$OrderLine->StandardCost = $StandardCost;
			} /* end of its an assembly */

			// Insert stock movements - with unit cost
			$LocalCurrencyPrice = ($OrderLine->Price / $ExRate);

			if (empty($OrderLine->StandardCost)) {
				$OrderLine->StandardCost=0;
			}
			if ($MBFlag=='B' OR $MBFlag=='M') {
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
								'" . $_SESSION['Items'.$identifier]->Location . "',
								'" . $DefaultDispatchDate . "',
								'" . $_SESSION['UserID'] . "',
								'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
								'" . $_SESSION['Items'.$identifier]->Branch . "',
								'" . $LocalCurrencyPrice . "',
								'" . $PeriodNo . "',
								'" . $OrderNo . "',
								'" . -$OrderLine->Quantity . "',
								'" . $OrderLine->DiscountPercent . "',
								'" . $OrderLine->StandardCost . "',
								'" . ($QtyOnHandPrior - $OrderLine->Quantity) . "',
								'" . $OrderLine->Narrative . "' )";
			} else {
			// its an assembly or dummy and assemblies/dummies always have nil stock (by definition they are made up at the time of dispatch  so new qty on hand will be nil
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
										'" . $_SESSION['Items'.$identifier]->Location . "',
										'" . $DefaultDispatchDate . "',
										'" . $_SESSION['UserID'] . "',
										'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
										'" . $_SESSION['Items'.$identifier]->Branch . "',
										'" . $LocalCurrencyPrice . "',
										'" . $PeriodNo . "',
										'" . $OrderNo . "',
										'" . -$OrderLine->Quantity . "',
										'" . $OrderLine->DiscountPercent . "',
										'" . $OrderLine->StandardCost . "',
										'" . $OrderLine->Narrative . "')";
			}

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('Stock movement records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		/*Get the ID of the StockMove... */
			$StkMoveNo = DB_Last_Insert_ID('stockmoves','stkmoveno');

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
			} //end for each tax for the line

			/* Controlled stuff not currently handled by counter orders

			Insert the StockSerialMovements and update the StockSerialItems  for controlled items

			if ($OrderLine->Controlled ==1) {
				foreach($OrderLine->SerialItems as $Item) {
								//We need to add the StockSerialItem record and the StockSerialMoves as well

					$SQL = "UPDATE stockserialitems
							SET quantity= quantity - " . $Item->BundleQty . "
							WHERE stockid='" . $OrderLine->StockID . "'
							AND loccode='" . $_SESSION['Items'.$identifier]->Location . "'
							AND serialno='" . $Item->BundleRef . "'";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock item record could not be updated because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					// now insert the serial stock movement

					$SQL = "INSERT INTO stockserialmoves (stockmoveno,
										stockid,
										serialno,
										moveqty)
						VALUES (" . $StkMoveNo . ",
							'" . $OrderLine->StockID . "',
							'" . $Item->BundleRef . "',
							" . -$Item->BundleQty . ")";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The serial stock movement record could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}// foreach controlled item in the serialitems array
			} //end if the orderline is a controlled item

			End of controlled stuff not currently handled by counter orders
			*/
			$SalesValue = 0;
			if ($ExRate>0) {
				$SalesValue = $OrderLine->Price * $OrderLine->Quantity / $ExRate;
			}

		/*Insert Sales Analysis records */

			$SQL="SELECT COUNT(*),
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
				AND salesanalysis.salesperson='" . $_SESSION['Items'.$identifier]->SalesPerson . "'
				AND salesanalysis.typeabbrev ='" . $_SESSION['Items'.$identifier]->DefaultSalesType . "'
				AND salesanalysis.periodno='" . $PeriodNo . "'
				AND salesanalysis.cust " . LIKE . " '" . $_SESSION['Items'.$identifier]->DebtorNo . "'
				AND salesanalysis.custbranch " . LIKE . " '" . $_SESSION['Items'.$identifier]->Branch . "'
				AND salesanalysis.stockid " . LIKE . " '" . $OrderLine->StockID . "'
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
			if ($MyRow[0]>0) {  /*Update the existing record that already exists */

				$SQL = "UPDATE salesanalysis
							SET amt=amt+" . ($SalesValue) . ",
								cost=cost+" . ($OrderLine->StandardCost * $OrderLine->Quantity) . ",
								qty=qty +" . $OrderLine->Quantity . ",
								disc=disc+" . ($OrderLine->DiscountPercent * $SalesValue) . "
							WHERE salesanalysis.area='" . $MyRow[5] . "'
							AND salesanalysis.salesperson='" . $_SESSION['Items'.$identifier]->SalesPerson . "'
							AND typeabbrev ='" . $_SESSION['Items'.$identifier]->DefaultSalesType . "'
							AND periodno = '" . $PeriodNo . "'
							AND cust " . LIKE . " '" . $_SESSION['Items'.$identifier]->DebtorNo . "'
							AND custbranch " . LIKE . " '" . $_SESSION['Items'.$identifier]->Branch . "'
							AND stockid " . LIKE . " '" . $OrderLine->StockID . "'
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
					SELECT '" . $_SESSION['Items'.$identifier]->DefaultSalesType . "',
						'" . $PeriodNo . "',
						'" . ($SalesValue) . "',
						'" . ($OrderLine->StandardCost * $OrderLine->Quantity) . "',
						'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
						'" . $_SESSION['Items'.$identifier]->Branch . "',
						'" . $OrderLine->Quantity . "',
						'" . ($OrderLine->DiscountPercent * $SalesValue) . "',
						'" . $OrderLine->StockID . "',
						custbranch.area,
						1,
						'" . $_SESSION['Items'.$identifier]->SalesPerson . "',
						stockmaster.categoryid
					FROM stockmaster,
						custbranch
					WHERE stockmaster.stockid = '" . $OrderLine->StockID . "'
					AND custbranch.debtorno = '" . $_SESSION['Items'.$identifier]->DebtorNo . "'
					AND custbranch.branchcode='" . $_SESSION['Items'.$identifier]->Branch . "'";
			}

			$ErrMsg = __('Sales analysis record could not be added or updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		/* If GLLink_Stock then insert GLTrans to credit stock and debit cost of sales at standard cost*/

			if ($_SESSION['CompanyRecord']['gllink_stock']==1 AND $OrderLine->StandardCost !=0) {

		/*first the cost of sales entry*/

				$SQL = "INSERT INTO gltrans (	type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES ( 10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . GetCOGSGLAccount($Area, $OrderLine->StockID, $_SESSION['Items'.$identifier]->DefaultSalesType) . "',
												'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->StandardCost, 0, 200) . "',
												'" . $OrderLine->StandardCost * $OrderLine->Quantity . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

		/*now the stock entry*/
				$StockGLCode = GetStockGLCode($OrderLine->StockID);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount )
										VALUES ( 10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $StockGLCode['stockact'] . "',
											'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->StandardCost, 0, 200) . "',
											'" . (-$OrderLine->StandardCost * $OrderLine->Quantity) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock side of the cost of sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			} /* end of if GL and stock integrated and standard cost !=0 */

			if ($_SESSION['CompanyRecord']['gllink_debtors']==1 AND $OrderLine->Price !=0) {

		//Post sales transaction to GL credit sales
				$SalesGLAccounts = GetSalesGLAccount($Area, $OrderLine->StockID, $_SESSION['Items'.$identifier]->DefaultSalesType);

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount )
										VALUES ( 10,
											'" . $InvoiceNo . "',
											'" . $DefaultDispatchDate . "',
											'" . $PeriodNo . "',
											'" . $SalesGLAccounts['salesglcode'] . "',
											'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo . " - " . $OrderLine->StockID . " x " . $OrderLine->Quantity . " @ " . $OrderLine->Price, 0, 200) . "',
											'" . (-$OrderLine->Price * $OrderLine->Quantity/$ExRate) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				if ($OrderLine->DiscountPercent !=0) {

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount )
												VALUES ( 10,
													'" . $InvoiceNo . "',
													'" . $DefaultDispatchDate . "',
													'" . $PeriodNo . "',
													'" . $SalesGLAccounts['discountglcode'] . "',
													'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo . " - " . $OrderLine->StockID . " @ " . ($OrderLine->DiscountPercent * 100) . "%", 0, 200) . "',
													'" . ($OrderLine->Price * $OrderLine->Quantity * $OrderLine->DiscountPercent/$ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales discount GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /*end of if discount !=0 */
			} /*end of if sales integrated with debtors */
		} /*end of OrderLine loop */

		if ($_SESSION['CompanyRecord']['gllink_debtors']==1) {

	/*Post debtors transaction to GL debit debtors, credit freight re-charged and credit sales */
			if (($_SESSION['Items'.$identifier]->total + filter_number_format($_POST['TaxTotal'])) !=0) {
				$SQL = "INSERT INTO gltrans (	type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount	)
											VALUES ( 10,
												'" . $InvoiceNo . "',
												'" . $DefaultDispatchDate . "',
												'" . $PeriodNo . "',
												'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
												'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo, 0, 200) . "',
												'" . (($_SESSION['Items'.$identifier]->total + filter_number_format($_POST['TaxTotal']))/$ExRate) . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The total debtor GL posting could not be inserted because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}


			foreach ( $_SESSION['Items'.$identifier]->TaxTotals as $TaxAuthID => $TaxAmount) {
				if ($TaxAmount !=0 ) {
					$SQL = "INSERT INTO gltrans (	type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount	)
												VALUES ( 10,
													'" . $InvoiceNo . "',
													'" . $DefaultDispatchDate . "',
													'" . $PeriodNo . "',
													'" . $_SESSION['Items'.$identifier]->TaxGLCodes[$TaxAuthID] . "',
													'" . mb_substr($_SESSION['Items'.$identifier]->DebtorNo, 0, 200) . "',
													'" . (-$TaxAmount/$ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The tax GL posting could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
			}

			EnsureGLEntriesBalance(10,$InvoiceNo);

			/*Also if GL is linked to debtors need to process the debit to bank and credit to debtors for the payment */
			/*Need to figure out the cross rate between customer currency and bank account currency */

			if ($_POST['AmountPaid']!=0) {
				$ReceiptNumber = GetNextTransNo(12);
				$SQL="INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
						VALUES (12,
							'" . $ReceiptNumber . "',
							'" . $DefaultDispatchDate . "',
							'" . $PeriodNo . "',
							'" . $_POST['BankAccount'] . "',
							'" . mb_substr($_SESSION['Items'.$identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 200) . "',
							'" . (filter_number_format($_POST['AmountPaid'])/$ExRate) . "')";
				$ErrMsg = __('Cannot insert a GL transaction for the bank account debit');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				/* Now Credit Debtors account with receipt */
				$SQL="INSERT INTO gltrans ( type,
						typeno,
						trandate,
						periodno,
						account,
						narrative,
						amount)
				VALUES (12,
					'" . $ReceiptNumber . "',
					'" . $DefaultDispatchDate . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
					'" . mb_substr($_SESSION['Items'.$identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 200) . "',
					'" . -(filter_number_format($_POST['AmountPaid'])/$ExRate) . "')";
				$ErrMsg = __('Cannot insert a GL transaction for the debtors account credit');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}//amount paid we not zero

			EnsureGLEntriesBalance(12,$ReceiptNumber);

		} /*end of if Sales and GL integrated */
		if ($_POST['AmountPaid']!=0) {
			if (!isset($ReceiptNumber)) {
				$ReceiptNumber = GetNextTransNo(12);
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

			$SQL="INSERT INTO banktrans (type,
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
						'" . $ReceiptNumber . "',
						'" . $_POST['BankAccount'] . "',
						'" . mb_substr($_SESSION['Items'.$identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 50) . "',
						'" . $ExRate . "',
						'" . $BankAccountExRate . "',
						'" . $DefaultDispatchDate . "',
						'" . $_POST['PaymentMethod'] . "',
						'" . filter_number_format($_POST['AmountPaid']) * $BankAccountExRate/$ExRate . "',
						'" . $_SESSION['Items'.$identifier]->DefaultCurrency . "')";

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
							invtext,
							settled,
							salesperson)
					VALUES ('" . $ReceiptNumber . "',
						12,
						'" . $_SESSION['Items'.$identifier]->DebtorNo . "',
						'" . $DefaultDispatchDate . "',
						'" . date('Y-m-d H-i-s') . "',
						'" . $PeriodNo . "',
						'" . $InvoiceNo . "',
						'" . $ExRate . "',
						'" . -filter_number_format($_POST['AmountPaid']) . "',
						'" . -filter_number_format($_POST['AmountPaid']) . "',
						'" . $_SESSION['Items'.$identifier]->LocationName . ' ' . __('Counter Sale') ."',
						'1',
						'" . $_SESSION['Items'.$identifier]->SalesPerson . "')";

			$ErrMsg = __('Cannot insert a receipt transaction against the customer because') ;
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$ReceiptDebtorTransID = DB_Last_Insert_ID('debtortrans','id');
			$SQL = "UPDATE debtorsmaster SET lastpaiddate = '" . $DefaultDispatchDate . "',
											lastpaid='" . filter_number_format($_POST['AmountPaid']) . "'
									WHERE debtorsmaster.debtorno='" . $_SESSION['Items'.$identifier]->DebtorNo . "'";

			$ErrMsg = __('Cannot update the customer record for the date of the last payment received because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			//and finally add the allocation record between receipt and invoice


			$SQL = "INSERT INTO custallocns (	amt,
												datealloc,
												transid_allocfrom,
												transid_allocto )
									VALUES  ('" . filter_number_format($_POST['AmountPaid']) . "',
											'" . $DefaultDispatchDate . "',
											 '" . $ReceiptDebtorTransID . "',
											 '" . $DebtorTransID . "')";
			$ErrMsg = __('Cannot insert the customer allocation of the receipt to the invoice because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} //end if $_POST['AmountPaid']!= 0

		DB_Txn_Commit();
	// *************************************************************************
	//   E N D   O F   I N V O I C E   S Q L   P R O C E S S I N G
	// *************************************************************************

		unset($_SESSION['Items'.$identifier]->LineItems);
		unset($_SESSION['Items'.$identifier]);

		if ($_SESSION['InvoicePortraitFormat']==0) {
			$CompletedInvoiceOrientation = 'landscape';
		} else {
			$CompletedInvoiceOrientation = 'portrait';
		}
		$CompletedSaleURL = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')
			. '?CompletedInvoiceNo=' . urlencode($InvoiceNo)
			. '&CompletedInvoiceOrientation=' . urlencode($CompletedInvoiceOrientation);

		if (!headers_sent()) {
			header('Location: ' . $CompletedSaleURL, true, 303);
			exit();
		}

		echo '<script>window.location.replace(' . json_encode($CompletedSaleURL) . ');</script>';
		echo '<noscript><meta http-equiv="Refresh" content="0; url=' . htmlspecialchars($CompletedSaleURL, ENT_QUOTES, 'UTF-8') . '" /></noscript>';
		exit();

	}
	// There were input errors so don't process nuffin
} else {
	//pretend the user never tried to commit the sale
	unset($_POST['ProcessSale']);
}
/*******************************
 * end of Invoice Processing
 * *****************************
*/

/* Now show the stock item selection search stuff below */
if (!isset($_POST['ProcessSale'])) {
	 if (isset($_POST['PartSearch']) and $_POST['PartSearch']!='') {

		echo '<section class="pos-entry-card">';
		echo '<div class="pos-card-header">
				<div>
					<h3>' . __('Find Products') . '</h3>
					<p>' . __('Search the catalog, check availability, and add products directly into this cash sale.') . '</p>
				</div>
				<div class="pos-badge">' . __('Search Mode') . '</div>
			</div>';
		echo '<input type="hidden" name="PartSearch" value="' .  __('Yes Please') . '" />';

		if ($_SESSION['FrequentlyOrderedItems']>0) { //show the Frequently Order Items selection where configured to do so

	// Select the most recently ordered items for quick select
			$SixMonthsAgo = DateAdd (date($_SESSION['DefaultDateFormat']),'m',-6);

			$SQL="SELECT stockmaster.units,
						stockmaster.description,
						stockmaster.stockid,
						salesorderdetails.stkcode,
						SUM(qtyinvoiced) Sales
				  FROM salesorderdetails INNER JOIN stockmaster
				  ON salesorderdetails.stkcode = stockmaster.stockid
				  WHERE ActualDispatchDate >= '" . FormatDateForSQL($SixMonthsAgo) . "'
				  AND stockmaster.controlled=0
				  GROUP BY stkcode
				  ORDER BY sales DESC
				  LIMIT " . $_SESSION['FrequentlyOrderedItems'];
			$Result2 = DB_query($SQL);
			echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/magnifier.png" title="' . __('Search') . '" alt="" />' . ' ';
			echo __('Frequently Ordered Items') . '</p><br />';
			echo '<div class="page_help_text">' . __('Frequently Ordered Items') . __(', shows the most frequently ordered items in the last 6 months.  You can choose from this list, or search further for other items') . '.</div><br />';
			echo '<div class="pos-search-results-card">';
			echo '<table class="table1">';
			$TableHeader = '<tr><th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Units') . '</th>
								<th>' . __('On Hand') . '</th>
								<th>' . __('On Demand') . '</th>
								<th>' . __('On Order') . '</th>
								<th>' . __('Available') . '</th>
								<th>' . __('Quantity') . '</th></tr>';
			echo $TableHeader;
			$i = 0;
			$j = 1;

			while ($MyRow=DB_fetch_array($Result2)) {
				$ImageSource = __('No Image');
				// Find the quantity in stock at location
				$QOH = GetQuantityOnHand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);

				// Find the demand
				$DemandQty = GetDemand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);

				// Get the QOO
				$QOO = GetQuantityOnOrder($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);

				$Available = $QOH - $DemandQty + $QOO;

				echo '<tr class="striped_row">
						<td>', $MyRow['stockid'], '</td>
						<td>', $MyRow['description'], '</td>
						<td>', $MyRow['units'], '</td>
						<td class="number">', $QOH, '</td>
						<td class="number">', $DemandQty, '</td>
						<td class="number">', $QOO, '</td>
						<td class="number">', $Available, '</td>
						<td><input class="number" tabindex="'.strval($j+7).'" type="text" ' . ($i==0?'autofocus="autofocus"':'') . ' size="6" required="required" name="OrderQty', $i, '" value="0" />
							<input type="hidden" name="StockID', $i, '" value="', $MyRow['stockid'], '" />
						</td>
					</tr>';
				$j++;//counter for paging
				$i++;//index for controls
	#end of page full new headings if
			}
	#end of while loop for Frequently Ordered Items
			echo '<td class="centre" colspan="8"><input type="hidden" name="SelectingOrderItems" value="1" /><input tabindex="'.strval($j+8).'" type="submit" value="'.__('Add to Sale').'" /></td>';
			echo '</table>';
			echo '</div>';
		} //end of if Frequently Ordered Items > 0
		if (isset($Msg)) {
			echo '<div class="page_help_text"><p><b>' . $Msg . '</b></p></div>';
		}
		echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/magnifier.png" title="' . __('Search') . '" alt="" />' . ' ' . __('Search for Items') . '</p>
			<div class="page_help_text">' . __('Search for Items') . __(', Searches the database for items, you can narrow the results by selecting a stock category, or just enter a partial item description or partial item code') . '.</div>';
		echo '<fieldset class="pos-quick-entry-wrap">
				<legend>', __('Item Search Criteria'), '</legend>';

		$SQL = "SELECT categoryid,
					categorydescription
				FROM stockcategory
				WHERE stocktype='F' OR stocktype='D'
				ORDER BY categorydescription";
		$Result1 = DB_query($SQL);
		echo '<field>
				<label for="StockCat">', __('Select a Stock Category'), ': </label>
				<select name="StockCat">';

		if (!isset($_POST['StockCat']) or $_POST['StockCat'] == 'All') {
			echo '<option selected="selected" value="All">', __('All'), '</option>';
			$_POST['StockCat'] = 'All';
		} else {
			echo '<option value="All">', __('All'), '</option>';
		}
		while ($MyRow1 = DB_fetch_array($Result1)) {
			if ($_POST['StockCat'] == $MyRow1['categoryid']) {
				echo '<option selected="selected" value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
			} else {
				echo '<option value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
			}
		}
		echo '</select>
			</field>';

		if (!isset($_POST['Keywords'])) {
			$_POST['Keywords'] = '';
		}
		echo '<field>
				<label for="Keywords">', __('Enter partial Description'), ':</label>
				<input type="search" name="Keywords" size="20" maxlength="25" value="', $_POST['Keywords'], '" />
			</field>';

		if (!isset($_POST['StockCode'])) {
			$_POST['StockCode'] = '';
		}
		echo '<field>
				<label for="StockCode"> ', __('OR'), ' ', __('Enter extract of the Stock Code'), ':</label>
				<input type="search" autofocus="autofocus" name="StockCode" size="15" maxlength="18" value="', $_POST['StockCode'], '" />
			</field>
		</fieldset>';
		echo '<div class="centre pos-mini-actions">
				<input type="submit" name="Search" value="', __('Search Now'), '" />
				<input type="submit" name="QuickEntry" value="', __('Use Quick Entry'), '" />
			</div>';
	// Add some useful help as the order progresses
		if (isset($SearchResult)) {
			echo '<br />
					<div class="page_help_text">' . __('Select an item by entering the quantity required.  Click Order when ready.') . '</div>
				<br />';
		}


		if (isset($SearchResult)) {
			echo '<div class="pos-product-grid">';
			echo '<input type="hidden" name="SelectingOrderItems" value="1" />';
			$i=0;

			while ($MyRow=DB_fetch_array($SearchResult)) {
				$QOH = GetQuantityOnHand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);
				$stockStatus = ($QOH > 10) ? 'stock-high' : (($QOH > 0) ? 'stock-low' : 'stock-out');
				$stockLabel = ($QOH > 0) ? locale_number_format($QOH, $MyRow['decimalplaces']) . ' ' . $MyRow['units'] . ' ' . __('in stock') : __('Out of Stock');

				echo '<div class="pos-product-card" onclick="CounterSales.AddProductToGrid(\'' . $MyRow['stockid'] . '\', ' . $i . ')">
						<div class="pos-stock-badge ' . $stockStatus . '">' . $stockLabel . '</div>
						<div class="pos-product-img">
							<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2"></path><path d="M21 12v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6"></path><path d="M3 8h18"></path><path d="M3 12h18"></path></svg>
						</div>
						<div class="pos-product-name">' . htmlspecialchars($MyRow['description']) . '</div>
						<div class="pos-product-price">' . $_SESSION['Items'.$identifier]->DefaultCurrency . ' ' . locale_number_format(GetPrice($MyRow['stockid'], $_SESSION['Items' . $identifier]->DebtorNo, $_SESSION['Items' . $identifier]->Branch), $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</div>
						<input type="hidden" name="OrderQty' . $i . '" id="OrderQty' . $i . '" value="0" />
						<input type="hidden" name="StockID' . $i . '" value="' . $MyRow['stockid'] . '" />
					</div>';
				$i++;
			}
			echo '</div>';
            echo '<div class="centre" style="margin-top: 24px; padding: 20px; background: var(--bg-main); border-radius: var(--radius-md);">
					<input type="submit" name="SelectingOrderItems" value="' . __('Update Cart with Selected Quantities') . '" class="db-btn db-btn-primary" />
				  </div>';
		}
		echo '</section>';
	} /*end of PartSearch options to be displayed */
		else { /* show the quick entry form variable */

		echo '<section class="pos-entry-card">';
		echo '<div class="pos-card-header">
				<div>
					<h3>' . __('Add Products') . '</h3>
					<p>' . __('Use barcode scan, quick code entry, or search to build the sale fast.') . '</p>
				</div>
				<div class="pos-badge">' . __('Ready') . '</div>
			</div>';
		echo '<div class="page_help_text"><b>' . __('Use this form to add items quickly if the item codes are already known') . '</b></div>';
        if (count($_SESSION['Items'.$identifier]->LineItems)==0) {
            echo '<input type="hidden" name="CustRef" value="' . $_SESSION['Items'.$identifier]->CustRef . '" />';
            echo '<input type="hidden" name="Comments" value="' . $_SESSION['Items'.$identifier]->Comments . '" />';
            echo '<input type="hidden" name="DeliverTo" value="' . $_SESSION['Items'.$identifier]->DeliverTo . '" />';
            echo '<input type="hidden" name="PhoneNo" value="' . $_SESSION['Items'.$identifier]->PhoneNo . '" />';
            echo '<input type="hidden" name="Email" value="' . $_SESSION['Items'.$identifier]->Email . '" />';
            echo '<input type="hidden" name="SalesPerson" value="' . $_SESSION['Items'.$identifier]->SalesPerson . '" />';
		}

		$SQL = "SELECT stockmaster.stockid,
					stockmaster.description
				FROM stockmaster
				INNER JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid
				WHERE stockmaster.controlled = 0
				AND (stockcategory.stocktype='F' OR stockcategory.stocktype='D' OR stockcategory.stocktype='L')
				AND stockmaster.mbflag <> 'G'
				AND stockmaster.discontinued = 0
				ORDER BY stockmaster.description";
		$ErrMsg = __('Could not fetch items list because');
		$ItemsResult = DB_query($SQL, $ErrMsg);
		$ItemCount = DB_num_rows($ItemsResult);

		if ($ItemCount==0)
		{
			prnMsg( __('There are no available item(s) retrieved from the database'),'warn');
		}
		elseif (!isset($_SESSION['ItemList']) || $ItemCount != count($_SESSION['ItemList']))
		{
			unset($_SESSION['ItemList']);

			$_SESSION['ItemList'] = array();
			while($MyRow=DB_fetch_array($ItemsResult))
			{
				$_SESSION['ItemList'][$MyRow['stockid']] = $MyRow['description'];
			}
		}

		// Output product datalist for dropdown/autocomplete support
		echo '<datalist id="ProductList">';
		foreach ($_SESSION['ItemList'] as $stockid => $description) {
			echo '<option value="' . htmlspecialchars($stockid, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($stockid . ' - ' . $description, ENT_QUOTES, 'UTF-8') . '</option>';
		}
		echo '</datalist>';

		echo '<div class="pos-entry-tools">';
		echo '<fieldset class="pos-quick-entry-wrap">
				<legend>', __('Scan Barcode / Select Product'), '</legend>
				<field>
					<label for="BarcodeInput">', __('Scan Barcode or Search Product'), ':</label>
					<div class="search-inline">
					<input type="text" id="BarcodeInput" list="ProductList" autocomplete="off"
						   autofocus="autofocus" size="40" maxlength="40"
						   placeholder="', __('Scan barcode or type product name / code...'), '"
						   title="', __('Scan a barcode or start typing to search. Press Enter to add to cart.'), '"
						   onkeydown="if(event.key===\'Enter\'){event.preventDefault();CounterSales.AddBarcodeItem(this);}" />
					<input type="button" value="', __('Add Item'), '"
						   onclick="CounterSales.AddBarcodeItem(document.getElementById(\'BarcodeInput\'))" />
					</div>
				</field>
			</fieldset>';
		echo '<div class="pos-quick-entry-wrap">
				<div class="pos-card-header">
					<div>
						<h3>' . __('Shortcuts') . '</h3>
						<p>' . __('Switch between quick entry and full product lookup as needed.') . '</p>
					</div>
				</div>
				<div class="pos-mini-actions">
					<input type="submit" name="QuickEntry" value="' . __('Quick Entry') . '" />
					<input type="submit" name="PartSearch" value="' . __('Search Parts') . '" />
				</div>
			</div>';
		echo '</div>';

		echo '<div class="pos-quick-entry-wrap">';
		echo '<table id="QuickEntryTable" class="pos-quick-entry-table" border="1">
				<tr>
					<th>' . __('Item Code') . '</th>
					<th>' . __('Quantity') . '</th>
				</tr>';
		$DefaultDeliveryDate = DateAdd(date($_SESSION['DefaultDateFormat']),'d',$_SESSION['Items'.$identifier]->DeliveryDays);
		for ($i=1;$i<=$_SESSION['QuickEntries'];$i++) {

	 		echo '<tr class="striped_row">';
	 		/* Do not display column unless customer requires po line number by sales order line*/
	 		echo '<td><input type="text" name="part_' . $i . '" list="ProductList" data-type="no-illegal-chars" title="' . __('Select a product from the dropdown or type a product code / name.') . '" size="21" maxlength="20" /></td>
					<td><input type="text" class="number" name="qty_' . $i . '" size="6" maxlength="6" />
						<input type="hidden" type="date" name="ItemDue_' . $i . '" value="' . $DefaultDeliveryDate . '" /></td></tr>';
   		}
	 	echo '</table>
				<div class="centre pos-mini-actions">
					<input type="hidden" id="TotalQuickEntryRows" name="TotalQuickEntryRows" value="' .$_SESSION['QuickEntries'] . '" />
					<input type="submit" name="QuickEntry" value="' . __('Quick Entry') . '" />
					<input type="submit" name="PartSearch" value="' . __('Search Parts') . '" />
				</div>
			</div>';
		echo '</section>';

  	}
	if ($_SESSION['Items'.$identifier]->ItemsOrdered >=1) {
  		echo '<aside class="pos-sidebar pos-sidebar-secondary"><div class="pos-sidebar-actions"><input type="reset" name="CancelOrder" value="' . __('Cancel Sale') . '" onclick="return confirm(\'' . __('Are you sure you wish to cancel this sale?') . '\');" /></div></aside>';
	}
	echo '</div>';
	echo '</form>';
}

?>
<script src="<?=$RootPath?>/javascripts/CounterSalesFunctions.js?v=<?=filemtime(__DIR__ . '/javascripts/CounterSalesFunctions.js')?>"></script>
<script defer="defer">
	CounterSales.SetTotalDue(<?=$_SESSION['Items'.$identifier]->total+$TaxTotal?>);
	CounterSales.SetItemList(<?php echo json_encode($_SESSION['ItemList']); ?>);
	CounterSales.SetQuickEntryTableId('QuickEntryTable');
	CounterSales.SetRowCounter(<?php echo empty($i) ? 0 : $i; ?>);
	CounterSales.SetDefaultDeliveryDate(<?php echo json_encode(empty($DefaultDeliveryDate) ? '' : $DefaultDeliveryDate); ?>);
	CounterSales.SetTotalQuickEntryRowsId('TotalQuickEntryRows');

	CounterSales.SetDecimal(<?php echo $_SESSION['Items'.$identifier]->CurrDecimalPlaces; ?>);
	CounterSales.SetCashReceivedId('CashReceived');
	CounterSales.SetAmountPaidId('AmountPaid');
	CounterSales.SetChangeDueId('ChangeDue');
	CounterSales.SetAutoFillCashReceived(<?php echo !empty($_POST['AutoFillCashReceived']) ? 'true' : 'false'; ?>);
	CounterSales.ApplyAutoPaymentDefaults();
</script>
<?php
include(__DIR__ . '/includes/footer.php');
