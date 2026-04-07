<?php
// VERSION: 2026-04-06-FINAL-V1

// Allows sales to be entered against a cash sale customer account defined in the users location record.

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCartClass.php');

$PageSecurity = 1;
require(__DIR__ . '/includes/session.php');

$Title = __('Counter Sales');
$ViewTopic = 'SalesOrders';
$BookMark = 'SalesOrderCounterSales';

if (empty($_GET['identifier'])) {
	$identifier=date('U');
} else {
	$identifier=$_GET['identifier'];
}

$ExtraHeadContent = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
                    <link rel="stylesheet" href="' . $RootPath . '/css/modern-zerp/pos.css">
                    <script type="text/javascript" src="' . $RootPath . '/javascripts/CounterSalesFunctions.js?v=' . filemtime(__DIR__ . '/javascripts/CounterSalesFunctions.js') . '"></script>';
include(__DIR__ . '/includes/header.php');

echo '<script type="text/javascript">
        window.addEventListener("DOMContentLoaded", function() {
            CounterSales.SetIdentifier("' . $identifier . '");
            CounterSales.SetFormId("' . $_SESSION['FormID'] . '");
            CounterSales.SetDecimal(' . ($_SESSION['Items'.$identifier]->CurrDecimalPlaces ?? 2) . ');
        });
      </script>';

include(__DIR__ . '/includes/GetPrice.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/GetSalesTransGLCodes.php');

$AlreadyWarnedAboutCredit = false;
$TaxTotal = 0;



// Ensure products load by default for modern POS
if (!isset($_POST['PartSearch']) && !isset($_POST['SelectingOrderItems']) && !isset($_GET['Delete'])) {
    $_POST['PartSearch'] = 'Yes';
    $_POST['StockCat'] = 'All';
    $_POST['Keywords'] = '';
    $_POST['StockCode'] = '';
}
if (isset($_POST['SwitchCustomer'])) {
	$SQL = "SELECT name, 
				currencies.currency, 
				currencies.decimalplaces, 
				custbranch.taxgroupid, 
				custbranch.defaultshipvia
			FROM debtorsmaster
			INNER JOIN custbranch ON debtorsmaster.debtorno=custbranch.debtorno
			INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtorsmaster.debtorno='" . DB_escape_string($_POST['SwitchCustomer']) . "'";
	$Result = DB_query($SQL);
	if ($MyRow = DB_fetch_array($Result)) {
		$_SESSION['Items'.$identifier]->DebtorNo = $_POST['SwitchCustomer'];
		$_SESSION['Items'.$identifier]->CustomerName = $MyRow['name'];
		$_SESSION['Items'.$identifier]->DefaultCurrency = $MyRow['currency'];
		$_SESSION['Items'.$identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
	}
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
echo '<!-- POS CHECKPOINT 2: INITIALIZATION COMPLETE -->';

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
echo '<div class="pos-layout">'; // Start Grid

// 1. Column 1: Categories (Icons)
$CatSQL = "SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype IN ('F', 'D', 'L')";
$CatResult = DB_query($CatSQL);
echo '<aside class="pos-categories" id="PosCategoryCol">';
$activeAll = (!isset($_POST['StockCat']) || $_POST['StockCat'] == 'All') ? 'active' : '';
echo '<button type="button" onclick="document.getElementById(\'StockCatInput\').value=\'All\'; this.form.submit();" class="pos-category-btn ' . $activeAll . '" title="' . __('All Items') . '">
        <i class="fas fa-th-large"></i>
        <span>' . __('All') . '</span>
      </button>';
while ($MyRow = DB_fetch_array($CatResult)) {
    $active = (isset($_POST['StockCat']) && $_POST['StockCat'] == $MyRow['categoryid']) ? 'active' : '';
    echo '<button type="button" onclick="document.getElementById(\'StockCatInput\').value=\'' . $MyRow['categoryid'] . '\'; this.form.submit();" class="pos-category-btn ' . $active . '" title="' . htmlspecialchars($MyRow['categorydescription']) . '">
            <i class="fas fa-folder"></i>
            <span>' . substr($MyRow['categorydescription'], 0, 8) . '</span>
          </button>';
}
echo '</aside>';

// 2. Column 2: Product Catalog
echo '<section class="pos-catalog" id="PosCatalogCol">';

    echo '<div class="pos-search-container">
            <div class="pos-search-box">
                <i class="fas fa-barcode" style="color: var(--primary);"></i>
                <input type="text" name="UnifiedSearch" id="UnifiedSearch" 
                       class="pos-search-input"
                       placeholder="' . __('Scan barcode or search products...') . '" 
                       onkeyup="CounterSales.HandleUnifiedSearch(this)" 
                       onkeypress="if(event.keyCode==13){event.preventDefault(); CounterSales.HandleUnifiedSearch(this);}" 
                       autocomplete="off" autofocus />
                <div class="pos-search-hint" style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600;">' . __('F4 to focus') . '</div>
            </div>
          </div>';

        echo '<input type="hidden" name="StockCat" id="StockCatInput" value="' . ($_POST['StockCat'] ?? 'All') . '" />';
        echo '<input type="hidden" name="PartSearch" value="Yes" />';
	// Add some useful help as the order progresses
		if (isset($SearchResult)) {
			echo '<br />
					<div class="page_help_text">' . __('Select an item by entering the quantity required.  Click Order when ready.') . '</div>
				<br />';
		

			echo '<div class="pos-product-grid">';
			echo '<input type="hidden" name="SelectingOrderItems" value="1" />';
			$i=0;

			while ($MyRow=DB_fetch_array($SearchResult)) {
				$Price = GetPrice($MyRow['stockid'], $_SESSION['Items' . $identifier]->DebtorNo, $_SESSION['Items' . $identifier]->Branch);
				$QOH = GetQuantityOnHand($MyRow['stockid'], $_SESSION['Items' . $identifier]->Location);
				$stockStatus = ($QOH > 10) ? 'stock-high' : (($QOH > 0) ? 'stock-low' : 'stock-out');
				$stockLabel = ($QOH > 0) ? locale_number_format($QOH, $MyRow['decimalplaces']) : __('0');

				echo '<div class="pos-product-card" data-stockid="' . $MyRow['stockid'] . '" onclick="CounterSales.AddItem(\'' . $MyRow['stockid'] . '\', 1)">
						<div class="pos-stock-badge ' . $stockStatus . '"><i class="fas fa-layer-group"></i> ' . $stockLabel . '</div>
						<div class="pos-product-img">
							<i class="fas fa-box" style="opacity: 0.5;"></i>
						</div>
						<div class="pos-product-info">
                            <div class="pos-product-name" title="' . htmlspecialchars($MyRow['description']) . '">' . htmlspecialchars($MyRow['description']) . '</div>
                            <div class="pos-product-price">' . $_SESSION['Items'.$identifier]->DefaultCurrency . ' ' . locale_number_format($Price, $_SESSION['Items' . $identifier]->CurrDecimalPlaces) . '</div>
                        </div>
					</div>';
				$i++;
			}
			echo '</div>'; // end pos-product-grid
		} // end if $SearchResult

echo "</section>"; // end pos-catalog (Main Column)

echo '<aside class="pos-sidebar" id="PosSidebarCol">';

// 1. Customer Section (TOP)
echo '<section class="pos-sidebar-card pos-customer-section">
        <div class="pos-card-header">
            <h3 style="margin:0; font-size: 0.9rem;"><i class="fas fa-user-circle"></i> ' . __('Customer') . '</h3>
            <span class="pos-badge" style="font-size: 0.75rem;">' . (($_SESSION['Items'.$identifier]->DebtorNo == $_SESSION['CompanyRecord']['cashsalecustomer']) ? __('Walk-in') : __('Account')) . '</span>
        </div>
        <div class="pos-customer-display" id="CustomerDisplay">
            <div style="font-weight: 700; color: var(--text-dark); font-size: 0.9rem;">' . htmlspecialchars($_SESSION['Items'.$identifier]->CustomerName) . '</div>
            <div style="font-size: 0.75rem; color: var(--text-muted);">' . htmlspecialchars($_SESSION['Items'.$identifier]->DebtorNo) . '</div>
        </div>
        <div class="pos-customer-search-container">
            <div class="pos-search-input-wrapper" style="position: relative;">
                <i class="fas fa-search" style="position: absolute; left: 10px; top: 10px; color: var(--text-muted); font-size: 0.8rem;"></i>
                <input type="text" id="CustSearchInput" class="pos-input-sm" style="width: 100%; padding-left: 30px;" placeholder="' . __('Search customer...') . '" onkeyup="CounterSales.SearchCustomers(this.value)" autocomplete="off" />
            </div>
            <div id="CustSearchResults" class="pos-search-results-dropdown"></div>
        </div>
    </section>';

// 2. Cart Section (MIDDLE)
echo '<section class="pos-cart-container" style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
        <div class="pos-cart-header">
            <h3><i class="fas fa-shopping-basket"></i> ' . __('Current Sale') . '</h3>
            <button type="button" class="pos-badge-btn" style="background: var(--danger-bg); color: var(--danger); border-radius: 8px;" onclick="CounterSales.ClearCart()">
                <i class="fas fa-trash-alt"></i> ' . __('Clear All') . '
            </button>
        </div>
        <div class="pos-cart-items" id="CartItemsContainer">';

// Initial Cart Render (Same logic as AJAX will use)
if (count($_SESSION['Items'.$identifier]->LineItems) == 0) {
    echo '<div class="pos-empty-cart">
            <i class="fas fa-shopping-basket"></i>
            <p>' . __('Cart is empty') . '</p>
          </div>';
} else {
    foreach ($_SESSION['Items'.$identifier]->LineItems as $OrderLine) {
        $SubTotal = $OrderLine->Quantity * $OrderLine->Price * (1 - $OrderLine->DiscountPercent);
        echo '<div class="pos-cart-item" data-line-id="' . $OrderLine->LineNumber . '">
                <div class="pos-cart-item-info">
                    <h4 style="font-size: 0.95rem; margin: 0 0 6px 0;">' . htmlspecialchars($OrderLine->ItemDescription) . '</h4>
                    <div class="pos-cart-item-meta" style="margin-bottom: 8px;">
                        <span class="pos-item-code" style="font-weight: 600;">' . $OrderLine->StockID . '</span>
                        <span style="margin: 0 6px; opacity: 0.5;">|</span>
                        <span style="font-weight: 600; color: var(--primary);">@ ' . locale_number_format($OrderLine->Price, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
                    </div>
                    <div class="pos-cart-item-qty">
                        <button type="button" class="pos-tool-btn" title="' . __('Decrease Quantity') . '" onclick="CounterSales.UpdateQty(' . $OrderLine->LineNumber . ', ' . ($OrderLine->Quantity - 1) . ')"><i class="fas fa-minus"></i></button>
                        <input type="text" class="pos-qty-input" value="' . $OrderLine->Quantity . '" onchange="CounterSales.UpdateQty(' . $OrderLine->LineNumber . ', this.value)" />
                        <button type="button" class="pos-tool-btn" title="' . __('Increase Quantity') . '" onclick="CounterSales.UpdateQty(' . $OrderLine->LineNumber . ', ' . ($OrderLine->Quantity + 1) . ')"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="DiscRow' . $OrderLine->LineNumber . '" class="pos-disc-row" style="display: ' . ($OrderLine->DiscountPercent > 0 ? 'flex' : 'none') . '; margin-top: 8px; align-items: center; gap: 8px;">
                        <input type="text" class="pos-input-sm" style="width: 50px;" value="' . ($OrderLine->DiscountPercent * 100) . '" onchange="CounterSales.UpdateDiscount(' . $OrderLine->LineNumber . ', this.value)" />
                        <small style="font-size: 0.75rem; color: var(--text-muted);">' . __('% Disc') . '</small>
                    </div>
                </div>
                <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);">
                        <small style="font-size: 0.7rem; vertical-align: middle; opacity: 0.7; margin-right: 2px;">' . $_SESSION['Items'.$identifier]->DefaultCurrency . '</small>
                        ' . locale_number_format($SubTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '
                    </div>
                    <div class="pos-item-actions" style="display: flex; gap: 4px; justify-content: flex-end;">
                        <button type="button" onclick="CounterSales.ToggleDiscount(' . $OrderLine->LineNumber . ')" class="pos-tool-btn" title="' . __('Add Discount') . '"><i class="fas fa-tag"></i></button>
                        <button type="button" onclick="CounterSales.RemoveItem(' . $OrderLine->LineNumber . ')" class="pos-tool-btn delete" title="' . __('Remove Item') . '"><i class="fas fa-times"></i></button>
                    </div>
                </div>
              </div>';
    }
}
echo '  </div>
    </section>';

// 3. Payment Section (BOTTOM)
$DisplayTaxTotal = 0;
if (isset($_SESSION['Items'.$identifier]->TaxTotals) && is_array($_SESSION['Items'.$identifier]->TaxTotals)) {
    foreach ($_SESSION['Items'.$identifier]->TaxTotals as $TaxAmount) {
        $DisplayTaxTotal += $TaxAmount;
    }
}
$DisplayGrandTotal = $_SESSION['Items'.$identifier]->total + $DisplayTaxTotal;

echo '<div class="pos-cart-footer">
        <div class="pos-payment-card">
            <div class="pos-summary-group">
                <div class="pos-summary-line">
                    <span>' . __('Subtotal (Net)') . '</span>
                    <span id="SummarySubtotal">' . locale_number_format($_SESSION['Items'.$identifier]->total, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
                </div>';
if ($DisplayTaxTotal != 0) {
    echo '      <div class="pos-summary-line" id="TaxRow">
                    <span>' . __('Tax') . '</span>
                    <span id="SummaryTax">' . locale_number_format($DisplayTaxTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
                </div>';
} else {
    echo '      <div class="pos-summary-line" id="TaxRow" style="display: none;">
                    <span>' . __('Tax') . '</span>
                    <span id="SummaryTax">0.00</span>
                </div>';
}
echo '      </div>
            
            <div class="pos-total-row">
                <span class="pos-total-label">' . __('Total Amount') . '</span>
                <span class="pos-total-amount" id="SummaryGrandTotal">' . $_SESSION['Items'.$identifier]->DefaultCurrency . ' ' . locale_number_format($DisplayGrandTotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '</span>
            </div>

            <div class="pos-payment-group">
                <div class="pos-summary-line" style="margin-bottom: var(--space-3); color: var(--text-main); font-weight: 700;">
                    <span><i class="fas fa-credit-card"></i> ' . __('Payment Methods') . '</span>
                    <button type="button" class="pos-badge-btn" onclick="CounterSales.AddPaymentRow()"><i class="fas fa-plus"></i> ' . __('Split') . '</button>
                </div>
                <div id="PaymentRowsContainer">';

// Re-use logic for payment rows but keep it clean
$PaymentMethodsResult = DB_query("SELECT paymentid, paymentname FROM paymentmethods");
$PaymentMethods = [];
while ($row = DB_fetch_array($PaymentMethodsResult)) { $PaymentMethods[] = $row; }

if (!isset($_POST['PaymentAmounts'])) {
    $_POST['PaymentAmounts'] = [0 => 0];
    $_POST['PaymentMethods'] = [0 => ($PaymentMethods[0]['paymentid'] ?? '')];
    $_POST['BankAccounts'] = [0 => ''];
}

foreach ($_POST['PaymentAmounts'] as $i => $amount) {
    echo '<div class="pos-payment-row" id="PaymentRow' . $i . '">
            <select name="PaymentMethods[' . $i . ']" class="pos-input-sm" style="flex: 1;" onchange="CounterSales.OnPaymentMethodChange(this, ' . $i . ')">';
    foreach ($PaymentMethods as $pm) {
        $selected = ($_POST['PaymentMethods'][$i] == $pm['paymentid']) ? 'selected' : '';
        echo '<option ' . $selected . ' value="' . $pm['paymentid'] . '" data-bank="">' . $pm['paymentname'] . '</option>';
    }
    echo '</select>
            <input type="hidden" name="BankAccounts[' . $i . ']" value="" />
            <input type="text" name="PaymentAmounts[' . $i . ']" class="pos-input-sm number" style="width: 100px; font-weight: 700; flex-shrink: 0;" value="' . $amount . '" placeholder="0.00" onchange="CounterSales.CalculateTotals()" />
          </div>';
}

echo '      </div>
            <div class="pos-payment-summary">
                <div class="pos-summary-line" style="font-size: 0.85rem;"><span>' . __('Paid') . '</span><strong id="TotalPaidDisplay" style="color: var(--primary);">0.00</strong></div>
                <div class="pos-summary-line" id="RemainingBalanceRow" style="font-size: 0.85rem;"><span>' . __('Balance') . '</span><strong id="RemainingBalanceDisplay">0.00</strong></div>
            </div>
            
            <div class="pos-cash-calc">
                <div class="pos-summary-line" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                    <label style="font-size: 0.65rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">' . __('Cash Received') . '</label>
                    <input type="text" class="pos-input-sm number" style="width: 100%; font-size: 1.1rem; border-color: var(--primary-light);" id="CashReceived" name="CashReceived" value="' . ($_POST['CashReceived'] ?? 0) . '" onkeyup="CounterSales.CalculateChangeDue()" />
                </div>
                <div class="pos-summary-line" style="flex-direction: column; align-items: flex-start; gap: 2px;">
                    <label style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">' . __('Change Due') . '</label>
                    <input type="text" class="pos-input-sm number" style="width: 100%; font-size: 1.1rem; background: var(--bg-workspace);" id="ChangeDue" name="ChangeDue" value="' . ($_POST['ChangeDue'] ?? 0) . '" readonly />
                </div>
            </div>
            
            </div>
            
            <input type="hidden" id="TotalAmountToPay" name="TaxTotal" value="' . $DisplayGrandTotal . '" />
        </div>'; // End pos-payment-card

echo '  <button type="submit" name="ProcessSale" value="1" class="pos-pay-btn">
    <i class="fas fa-check-circle"></i> ' . __('Complete Payment') . '
  </button>';
echo '</div>'; // end pos-cart-footer

echo '</aside>';
echo '</div>'; // end pos-layout

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
	$TotalAmountPaid = 0;
	if (isset($_POST['PaymentAmounts'])) {
		foreach ($_POST['PaymentAmounts'] as $Amount) {
			$TotalAmountPaid += filter_number_format($Amount);
		}
	}

	if (abs($TotalAmountPaid - (round($_SESSION['Items'.$identifier]->total + filter_number_format($_POST['TaxTotal']), $_SESSION['Items'.$identifier]->CurrDecimalPlaces))) >= CurrencyTolerance($_SESSION['Items' . $identifier]->DefaultCurrency)) {
		prnMsg(__('The total amount entered as payment') . ' (' . $TotalAmountPaid . ') ' . __('does not equal the amount of the invoice') . ' (' . round($_SESSION['Items'.$identifier]->total + filter_number_format($_POST['TaxTotal']), $_SESSION['Items'.$identifier]->CurrDecimalPlaces) . '). ' . __('Please ensure the customer has paid the correct amount and re-enter'),'error');
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

			if ($TotalAmountPaid != 0) {
				foreach ($_POST['PaymentAmounts'] as $i => $Amount) {
					$Amount = filter_number_format($Amount);
					if ($Amount == 0) continue;

					$ReceiptNumber = GetNextTransNo(12);
					$PaymentMethod = $_POST['PaymentMethods'][$i];
					$BankAccount = $_POST['BankAccounts'][$i];

					if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
						$SQL = "INSERT INTO gltrans (type,
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
									'" . $BankAccount . "',
									'" . mb_substr($_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 200) . "',
									'" . ($Amount / $ExRate) . "')";
						$ErrMsg = __('Cannot insert a GL transaction for the bank account debit');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						/* Now Credit Debtors account with receipt */
						$SQL = "INSERT INTO gltrans ( type,
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
							'" . mb_substr($_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 200) . "',
							'" . -($Amount / $ExRate) . "')";
						$ErrMsg = __('Cannot insert a GL transaction for the debtors account credit');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						EnsureGLEntriesBalance(12, $ReceiptNumber);
					}

					//Now need to add the receipt banktrans record
					$Result = DB_query("SELECT rate FROM currencies
										INNER JOIN bankaccounts ON currencies.currabrev=bankaccounts.currcode
										WHERE bankaccounts.accountcode='" . $BankAccount . "'");
					$MyRow = DB_fetch_row($Result);
					$BankAccountExRate = $MyRow[0];

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
								'" . $ReceiptNumber . "',
								'" . $BankAccount . "',
								'" . mb_substr($_SESSION['Items' . $identifier]->LocationName . ' ' . __('Counter Sale') . ' ' . $InvoiceNo, 0, 50) . "',
								'" . $ExRate . "',
								'" . $BankAccountExRate . "',
								'" . $DefaultDispatchDate . "',
								'" . $PaymentMethod . "',
								'" . $Amount * $BankAccountExRate / $ExRate . "',
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
									invtext,
									settled,
									salesperson)
							VALUES ('" . $ReceiptNumber . "',
								12,
								'" . $_SESSION['Items' . $identifier]->DebtorNo . "',
								'" . $DefaultDispatchDate . "',
								'" . date('Y-m-d H-i-s') . "',
								'" . $PeriodNo . "',
								'" . $InvoiceNo . "',
								'" . $ExRate . "',
								'" . -$Amount . "',
								'" . -$Amount . "',
								'" . $_SESSION['Items' . $identifier]->Comments . "',
								1,
								'" . $_SESSION['Items' . $identifier]->SalesPerson . "')";
					$ErrMsg = __('Cannot insert a debtor transaction record for the receipt');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					$ReceiptDebtorTransID = DB_Last_Insert_ID('debtortrans', 'id');

					// and finally add the allocation record between receipt and invoice
					$SQL = "INSERT INTO custallocns (amt,
													datealloc,
													transid_allocfrom,
													transid_allocto)
							VALUES ('" . $Amount . "',
									'" . $DefaultDispatchDate . "',
									'" . $ReceiptDebtorTransID . "',
									'" . $DebtorTransID . "')";
					$ErrMsg = __('Cannot insert the customer allocation of the receipt to the invoice because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
				
				// Update last paid date for customer
				$SQL = "UPDATE debtorsmaster SET lastpaiddate = '" . $DefaultDispatchDate . "',
												lastpaid='" . $TotalAmountPaid . "'
										WHERE debtorsmaster.debtorno='" . $_SESSION['Items' . $identifier]->DebtorNo . "'";
				DB_query($SQL);
			} //end if $TotalAmountPaid != 0

		} /*end of if Sales and GL integrated */

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
	if ($_SESSION['Items'.$identifier]->ItemsOrdered >=1) {
  		echo '<aside class="pos-sidebar pos-sidebar-secondary"><div class="pos-sidebar-actions"><input type="reset" name="CancelOrder" value="' . __('Cancel Sale') . '" onclick="return confirm(\'' . __('Are you sure you wish to cancel this sale?') . '\');" /></div></aside>';
	}
	echo '</form>';

?>

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
