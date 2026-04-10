<?php

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/CurrenciesArray.php'); // To get the currency name from the currency code.
if (isset($_POST['ClientSince'])){$_POST['ClientSince'] = ConvertSQLDate($_POST['ClientSince']);}

if (isset($_POST['Edit']) or isset($_GET['Edit']) or isset($_GET['DebtorNo'])) {
	$ViewTopic = 'AccountsReceivable';
	$BookMark = 'AmendCustomer';
} else {
	$ViewTopic = 'AccountsReceivable';
	$BookMark = 'NewCustomer';
}

$Title = __('Customer Maintenance');
/* webERP manual links before header.php */
$ViewTopic = 'AccountsReceivable';
$BookMark = 'NewCustomer';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/CountriesArray.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Create or manage customer account profiles') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Search') . '
				</a>
			</div>
		</header>';

$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	$i=1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$_POST['DebtorNo'] = mb_strtoupper($_POST['DebtorNo']);

	$SQL="SELECT COUNT(debtorno) FROM debtorsmaster WHERE debtorno='".$_POST['DebtorNo']."'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);
	if ($MyRow[0]>0 AND isset($_POST['New'])) {
		$InputError = 1;
		prnMsg( __('The customer number already exists in the database'),'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif (mb_strlen($_POST['CustName']) > 40 OR mb_strlen($_POST['CustName'])==0) {
		$InputError = 1;
		prnMsg( __('The customer name must be entered and be forty characters or less long'),'error');
		$Errors[$i] = 'CustName';
		$i++;
	} elseif ($_SESSION['AutoDebtorNo']==0 AND mb_strlen($_POST['DebtorNo']) ==0) {
		$InputError = 1;
		prnMsg( __('The debtor code cannot be empty'),'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif ($_SESSION['AutoDebtorNo']==0 AND (ContainsIllegalCharacters($_POST['DebtorNo']) OR mb_strpos($_POST['DebtorNo'], ' '))) {
		$InputError = 1;
		prnMsg( __('The customer code cannot contain any of the following characters') . " . - ' &amp; + \" " . __('or a space'),'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif (mb_strlen($_POST['Address1']) >40) {
		$InputError = 1;
		prnMsg( __('The Line 1 of the address must be forty characters or less long'),'error');
		$Errors[$i] = 'Address1';
		$i++;
	} elseif (mb_strlen($_POST['Address2']) >40) {
		$InputError = 1;
		prnMsg( __('The Line 2 of the address must be forty characters or less long'),'error');
		$Errors[$i] = 'Address2';
		$i++;
	} elseif (mb_strlen($_POST['Address3']) >40) {
		$InputError = 1;
		prnMsg( __('The Line 3 of the address must be forty characters or less long'),'error');
		$Errors[$i] = 'Address3';
		$i++;
	} elseif (mb_strlen($_POST['Address4']) >50) {
		$InputError = 1;
		prnMsg( __('The Line 4 of the address must be fifty characters or less long'),'error');
		$Errors[$i] = 'Address4';
		$i++;
	} elseif (mb_strlen($_POST['Address5']) >20) {
		$InputError = 1;
		prnMsg( __('The Line 5 of the address must be twenty characters or less long'),'error');
		$Errors[$i] = 'Address5';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['CreditLimit']))) {
		$InputError = 1;
		prnMsg( __('The credit limit must be numeric'),'error');
		$Errors[$i] = 'CreditLimit';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['PymtDiscount']))) {
		$InputError = 1;
		prnMsg( __('The payment discount must be numeric'),'error');
		$Errors[$i] = 'PymtDiscount';
		$i++;
	} elseif (!Is_Date($_POST['ClientSince'])) {
		$InputError = 1;
		prnMsg( __('The customer since field must be a date in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
		$Errors[$i] = 'ClientSince';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['Discount']))) {
		$InputError = 1;
		prnMsg( __('The discount percentage must be numeric'),'error');
		$Errors[$i] = 'Discount';
		$i++;
	} elseif (filter_number_format($_POST['CreditLimit']) <0) {
		$InputError = 1;
		prnMsg( __('The credit limit must be a positive number'),'error');
		$Errors[$i] = 'CreditLimit';
		$i++;
	} elseif ((filter_number_format($_POST['PymtDiscount'])> 10) OR (filter_number_format($_POST['PymtDiscount']) <0)) {
		$InputError = 1;
		prnMsg( __('The payment discount is expected to be less than 10% and greater than or equal to 0'),'error');
		$Errors[$i] = 'PymtDiscount';
		$i++;
	} elseif ((filter_number_format($_POST['Discount'])> 100) OR (filter_number_format($_POST['Discount']) <0)) {
		$InputError = 1;
		prnMsg( __('The discount is expected to be less than 100% and greater than or equal to 0'),'error');
		$Errors[$i] = 'Discount';
		$i++;
	}

	if ($InputError != 1){

		$SQL_ClientSince = FormatDateForSQL($_POST['ClientSince']);

		if (!isset($_POST['New'])) {

			$SQL = "SELECT count(id)
					  FROM debtortrans
					where debtorno = '" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			if ($MyRow[0] == 0) {
				$SQL = "UPDATE debtorsmaster SET name='" . $_POST['CustName'] . "',
												address1='" . $_POST['Address1'] . "',
												address2='" . $_POST['Address2'] . "',
												address3='" . $_POST['Address3'] ."',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												currcode='" . $_POST['CurrCode'] . "',
												clientsince='" . $SQL_ClientSince. "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount'])/100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount'])/100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "',
												language_id='" . $_POST['LanguageID'] . "'
					  WHERE debtorno = '" . $_POST['DebtorNo'] . "'";
			} else {

				$CurrSQL = "SELECT currcode
					  		FROM debtorsmaster
							where debtorno = '" . $_POST['DebtorNo'] . "'";
				$CurrResult = DB_query($CurrSQL);
				$CurrRow = DB_fetch_array($CurrResult);
				$OldCurrency = $CurrRow[0];

				$SQL = "UPDATE debtorsmaster SET	name='" . $_POST['CustName'] . "',
												address1='" . $_POST['Address1'] . "',
												address2='" . $_POST['Address2'] . "',
												address3='" . $_POST['Address3'] ."',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												clientsince='" . $SQL_ClientSince . "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount'])/100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount'])/100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "',
												language_id='" . $_POST['LanguageID'] . "'
						WHERE debtorno = '" . $_POST['DebtorNo'] . "'";

				if ($OldCurrency !=  $_POST['CurrCode']) {
					prnMsg( __('The currency code cannot be updated as there are already transactions for this customer'),'info');
				}
			}

			$ErrMsg = __('The customer could not be updated because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg( __('Customer updated'),'success');
			echo '<br />';

		} else { //it is a new customer
			/* set the DebtorNo if $AutoDebtorNo in config.php has been set to
			something greater 0 */
			if ($_SESSION['AutoDebtorNo'] > 0) {
				/* system assigned, sequential, numeric */
				if ($_SESSION['AutoDebtorNo']== 1) {
					$_POST['DebtorNo'] = GetNextTransNo(500);
				}
			}

			$SQL = "INSERT INTO debtorsmaster (
							debtorno,
							name,
							address1,
							address2,
							address3,
							address4,
							address5,
							address6,
							currcode,
							clientsince,
							holdreason,
							paymentterms,
							discount,
							discountcode,
							pymtdiscount,
							creditlimit,
							salestype,
							invaddrbranch,
							taxref,
							customerpoline,
							typeid,
							language_id)
				VALUES ('" . $_POST['DebtorNo'] ."',
						'" . $_POST['CustName'] ."',
						'" . $_POST['Address1'] ."',
						'" . $_POST['Address2'] ."',
						'" . $_POST['Address3'] . "',
						'" . $_POST['Address4'] . "',
						'" . $_POST['Address5'] . "',
						'" . $_POST['Address6'] . "',
						'" . $_POST['CurrCode'] . "',
						'" . $SQL_ClientSince . "',
						'" . $_POST['HoldReason'] . "',
						'" . $_POST['PaymentTerms'] . "',
						'" . filter_number_format($_POST['Discount'])/100 . "',
						'" . $_POST['DiscountCode'] . "',
						'" . filter_number_format($_POST['PymtDiscount'])/100 . "',
						'" . filter_number_format($_POST['CreditLimit']) . "',
						'" . $_POST['SalesType'] . "',
						'" . $_POST['AddrInvBranch'] . "',
						'" . $_POST['TaxRef'] . "',
						'" . $_POST['CustomerPOLine'] . "',
						'" . $_POST['typeid'] . "',
						'" . $_POST['LanguageID'] . "')";

			$ErrMsg = __('This customer could not be added because');
			$Result = DB_query($SQL, $ErrMsg);

			echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath .'/CustomerBranches.php?DebtorNo=' . $_POST['DebtorNo'] . '">';

			echo '<div class="centre">' . __('You should automatically be forwarded to the entry of a new Customer Branch page') .
			'. ' . __('If this does not happen') .' (' . __('if the browser does not support META Refresh') . ') ' .
			'<a href="' . $RootPath . '/CustomerBranches.php?DebtorNo=' . $_POST['DebtorNo']  . '"></a></div>';

			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	} else {
		prnMsg( __('Validation failed') . '. ' . __('No updates or deletes took place'),'error');
	}

} elseif (isset($_POST['delete'])) {

//the link to delete a selected record was clicked instead of the submit button

	$CancelDelete = 0;

// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'

	$SQL= "SELECT COUNT(*) FROM debtortrans WHERE debtorno='" . $_POST['DebtorNo'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		$CancelDelete = 1;
		prnMsg( __('This customer cannot be deleted because there are transactions that refer to it'),'warn');
		echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('transactions against this customer');

	} else {
		$SQL= "SELECT COUNT(*) FROM salesorders WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			$CancelDelete = 1;
			prnMsg( __('Cannot delete the customer record because orders have been created against it'),'warn');
			echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('orders against this customer');
		} else {
			$SQL= "SELECT COUNT(*) FROM salesanalysis WHERE cust='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0]>0) {
				$CancelDelete = 1;
				prnMsg( __('Cannot delete this customer record because sales analysis records exist for it'),'warn');
				echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('sales analysis records against this customer');
			} else {

				// Check if there are any users that refer to this CUSTOMER code
				$SQL= "SELECT COUNT(*) FROM www_users WHERE www_users.customerid = '" . $_POST['DebtorNo'] . "'";

				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);

				if ($MyRow[0]>0) {
					prnMsg(__('Cannot delete this customer because users exist that refer to it') . '. ' . __('Purge old users first'),'warn');
					echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' '.__('users referring to this Branch/customer');
				} else {
						// Check if there are any contract that refer to this branch code
					$SQL = "SELECT COUNT(*) FROM contracts WHERE contracts.debtorno = '" . $_POST['DebtorNo'] . "'";

					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);

					if ($MyRow[0]>0) {
						prnMsg(__('Cannot delete this customer because contracts have been created that refer to it') . '. ' . __('Purge old contracts first'),'warn');
						echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' '.__('contracts referring to this customer');
					}
				}
			}
		}

	}
	if ($CancelDelete==0) { //ie not cancelled the delete as a result of above tests
		$SQL="DELETE FROM custbranch WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL, $ErrMsg);
		$SQL="DELETE FROM custcontacts WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$SQL="DELETE FROM debtorsmaster WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		prnMsg( __('Customer') . ' ' . $_POST['DebtorNo'] . ' ' . __('has been deleted - together with all the associated branches and contacts'),'success');
		unset($_SESSION['CustomerID']);
		include(__DIR__ . '/includes/footer.php');
		exit();
	} //end if Delete Customer
}

if (isset($_POST['Reset'])){
	unset($_POST['CustName']);
	unset($_POST['Address1']);
	unset($_POST['Address2']);
	unset($_POST['Address3']);
	unset($_POST['Address4']);
	unset($_POST['Address5']);
	unset($_POST['Address6']);
	unset($_POST['HoldReason']);
	unset($_POST['PaymentTerms']);
	unset($_POST['Discount']);
	unset($_POST['DiscountCode']);
	unset($_POST['PymtDiscount']);
	unset($_POST['CreditLimit']);
	unset($_POST['DebtorNo']);
	unset($_POST['InvAddrBranch']);
	unset($_POST['TaxRef']);
	unset($_POST['CustomerPOLine']);
	unset($_POST['LanguageID']);
}

/*DebtorNo could be set from a post or a get when passed as a parameter to this page */

if (isset($_POST['DebtorNo'])){
	$DebtorNo = $_POST['DebtorNo'];
} elseif (isset($_GET['DebtorNo'])){
	$DebtorNo = $_GET['DebtorNo'];
}

if (isset($_POST['AddContact']) AND (isset($_POST['AddContact'])!= '')){
	echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' .$DebtorNo.'">';
}

if (!isset($DebtorNo)) {

	$SetupErrors=0; //Count errors
	$SQL="SELECT COUNT(typeabbrev) FROM salestypes";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);
	if ($MyRow[0]==0) {
		prnMsg( __('In order to create a new customer you must first set up at least one sales type/price list'),'warning');
		$SetupErrors += 1;
	}
	$SQL="SELECT COUNT(typeid) FROM debtortype";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);
	if ($MyRow[0]==0) {
		prnMsg( __('In order to create a new customer you must first set up at least one customer type'),'warning');
		$SetupErrors += 1;
	}

	if ($SetupErrors>0) {
		echo '<br /><div class="centre"><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" >' . __('Click here to continue') . '</a></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="New" value="Yes" />
		<div class="db-grid db-grid-2">';

	$DataError = 0;

	// Card 1: General Info
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					' . __('General Information') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid">';
	
	if ($_SESSION['AutoDebtorNo']==0)  {
		echo '<div class="db-field">
				<label class="db-label">' . __('Customer Code') . '</label>
				<input type="text" name="DebtorNo" required="required" autofocus="autofocus" class="db-input" maxlength="10" placeholder="' . __('alpha-numeric') . '" />
				<p class="db-field-help">' . __('Up to 10 characters. Avoid special characters.') . '</p>
			</div>';
	}

	echo '<div class="db-field">
			<label class="db-label">' . __('Customer Name') . '</label>
			<input type="text" name="CustName" required="required" class="db-input" maxlength="40" />
		</div>';

	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type");
	if (DB_num_rows($Result)==0){
		$DataError = 1;
		prnMsg(__('No sales types defined'),'error');
	} else {
		echo '<div class="db-field">
				<label class="db-label">' . __('Sales Type / Price List') . '</label>
				<select name="SalesType" required="required" class="db-input">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="'. $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
		}
		echo '</select>
			</div>';
	}

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	if (DB_num_rows($Result)==0){
		$DataError = 1;
		prnMsg(__('No customer types defined'),'error');
	} else {
		echo '<div class="db-field">
				<label class="db-label">' . __('Customer Type') . '</label>
				<select name="typeid" required="required" class="db-input">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="'. $MyRow['typeid'] . '">' . $MyRow['typename'] . '</option>';
		}
		echo '</select>
			</div>';
	}

	echo '</div></div></div>';

	// Card 2: Address & Contact
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
					' . __('Address & Location') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid">
					<div class="db-field">
						<label class="db-label">' . __('Street Address') . '</label>
						<input type="text" name="Address1" required="required" class="db-input" maxlength="40" placeholder="' . __('Line 1') . '" />
						<input type="text" name="Address2" class="db-input" maxlength="40" placeholder="' . __('Line 2') . '" style="margin-top: 8px;" />
					</div>
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('City / Suburb') . '</label>
							<input type="text" name="Address3" class="db-input" maxlength="40" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Province / State') . '</label>
							<input type="text" name="Address4" class="db-input" maxlength="40" />
						</div>
					</div>
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Postal Code') . '</label>
							<input type="text" name="Address5" class="db-input" maxlength="20" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Country') . '</label>
							<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $CountryEntry => $CountryName){
		echo '<option value="' . $CountryName . '">' . $CountryName  . '</option>';
	}
	echo '			</select>
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Customer Since') . '</label>
						<input type="date" name="ClientSince" value="' . date('Y-m-d') . '" class="db-input" />
					</div>
				</div>
			</div>
		</div></div>';

	// Card 3: Financials & Settings (Full width)
	echo '<div class="card-v2 db-card-full" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 1v22M17 5H9.5a4.5 4.5 0 1 0 0 9h5a4.5 4.5 0 1 1 0 9H6"></path></svg>
					' . __('Financials & System Settings') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Discount Percent') . '</label>
							<input type="text" name="Discount" value="0" class="db-input db-number" maxlength="4" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Discount Code') . '</label>
							<input type="text" name="DiscountCode" class="db-input" maxlength="2" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Payment Discount (%)') . '</label>
							<input type="text" name="PymtDiscount" value="0" class="db-input db-number" maxlength="4" />
						</div>
					</div>
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Credit Limit') . '</label>
							<input type="text" name="CreditLimit" required="required" value="' . locale_number_format($_SESSION['DefaultCreditLimit'],0) . '" class="db-input db-number" maxlength="14" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Tax Reference') . '</label>
							<input type="text" name="TaxRef" class="db-input" maxlength="20" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Payment Terms') . '</label>
							<select name="PaymentTerms" required="required" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
	}
	echo '			</select>
						</div>
					</div>
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Credit Status') . '</label>
							<select name="HoldReason" required="required" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['reasoncode'] . '">' . $MyRow['reasondescription'] . '</option>';
	}
	echo '			</select>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Customer Currency') . '</label>
							<select name="CurrCode" required="required" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '			</select>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Language') . '</label>
							<select name="LanguageID" required="required" class="db-input">';
	foreach ($LanguagesArray as $LanguageCode => $LanguageName){
		$selected = ($_SESSION['Language'] == $LanguageCode) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $LanguageCode . '">' . $LanguageName['LanguageName']  . '</option>';
	}
	echo '			</select>
						</div>
					</div>
				</div>
				<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />
				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label class="db-label">' . __('Show PO Line on Sales Orders') . '</label>
						<select name="CustomerPOLine" required="required" class="db-input">
							<option selected="selected" value="0">' . __('No') . '</option>
							<option value="1">' . __('Yes') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Invoicing Address Preference') . '</label>
						<select name="AddrInvBranch" required="required" class="db-input">
							<option selected="selected" value="0">' . __('Address to Head Office') . '</option>
							<option value="1">' . __('Address to Branch') . '</option>
						</select>
					</div>
				</div>
			</div>';

	if ($DataError == 0){
		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M12 5v14M5 12h14"></path></svg>
					' . __('Add New Customer') . '
				</button>
				<button type="reset" name="Reset" class="db-btn db-btn-secondary db-btn-large" style="margin-left: 1rem;">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path><path d="M3 3v5h5"></path></svg>
					' . __('Reset Form') . '
				</button>
			</div>';
	}
	echo '</div></form>';

} else {
	// EDIT MODE
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (!isset($_POST['New'])) {
		$SQL = "SELECT * FROM debtorsmaster WHERE debtorno = '" . $DebtorNo . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		
		$_POST['CustName'] = $MyRow['name'];
		$_POST['Address1'] = $MyRow['address1'];
		$_POST['Address2'] = $MyRow['address2'];
		$_POST['Address3'] = $MyRow['address3'];
		$_POST['Address4'] = $MyRow['address4'];
		$_POST['Address5'] = $MyRow['address5'];
		$_POST['Address6'] = $MyRow['address6'];
		$_POST['SalesType'] = $MyRow['salestype'];
		$_POST['CurrCode'] = $MyRow['currcode'];
		$_POST['ClientSince'] = ConvertSQLDate($MyRow['clientsince']);
		$_POST['HoldReason'] = $MyRow['holdreason'];
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['Discount'] = locale_number_format($MyRow['discount'] * 100,2);
		$_POST['DiscountCode'] = $MyRow['discountcode'];
		$_POST['PymtDiscount'] = locale_number_format($MyRow['pymtdiscount'] * 100,2);
		$_POST['CreditLimit'] = locale_number_format($MyRow['creditlimit'],0);
		$_POST['InvAddrBranch'] = $MyRow['invaddrbranch'];
		$_POST['TaxRef'] = $MyRow['taxref'];
		$_POST['CustomerPOLine'] = $MyRow['customerpoline'];
		$_POST['typeid'] = $MyRow['typeid'];
		$_POST['LanguageID'] = $MyRow['language_id'];

		echo '<input type="hidden" name="DebtorNo" value="' . $DebtorNo . '" />';
	}

	echo '<div class="db-grid db-grid-2">';

	// Edit Card 1: General Info
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					' . __('General Information') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid">
					<div class="db-field">
						<label class="db-label">' . __('Customer Code') . '</label>
						<input type="text" class="db-input" value="' . $DebtorNo . '" disabled />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Customer Name') . '</label>
						<input type="text" name="CustName" class="db-input" required value="' . $_POST['CustName'] . '" maxlength="40" />
					</div>';
	
	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type");
	echo '<div class="db-field">
			<label class="db-label">' . __('Sales Type / Price List') . '</label>
			<select name="SalesType" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['SalesType']==$myr['typeabbrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['typeabbrev'] . '">' . $myr['sales_type'] . '</option>';
	}
	echo '</select></div>';

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<div class="db-field">
			<label class="db-label">' . __('Customer Type') . '</label>
			<select name="typeid" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['typeid']==$myr['typeid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['typeid'] . '">' . $myr['typename'] . '</option>';
	}
	echo '</select></div>';
	
	echo '</div></div></div>';

	// Edit Card 2: Address & Contact
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
					' . __('Address & Location') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid">
					<div class="db-field">
						<label class="db-label">' . __('Street Address') . '</label>
						<input type="text" name="Address1" class="db-input" required value="' . $_POST['Address1'] . '" maxlength="40" placeholder="' . __('Line 1') . '" />
						<input type="text" name="Address2" class="db-input" value="' . $_POST['Address2'] . '" maxlength="40" placeholder="' . __('Line 2') . '" style="margin-top: 8px;" />
					</div>
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('City / Suburb') . '</label>
							<input type="text" name="Address3" class="db-input" value="' . $_POST['Address3'] . '" maxlength="40" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Province / State') . '</label>
							<input type="text" name="Address4" class="db-input" value="' . $_POST['Address4'] . '" maxlength="40" />
						</div>
					</div>
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Postal Code') . '</label>
							<input type="text" name="Address5" class="db-input" value="' . $_POST['Address5'] . '" maxlength="20" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Country') . '</label>
							<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $cn){
		$sel = (strtoupper($_POST['Address6']) == strtoupper($cn)) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $cn . '">' . $cn  . '</option>';
	}
	echo '			</select>
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Customer Since') . '</label>
						<input type="date" name="ClientSince" class="db-input" value="' . FormatDateForSQL($_POST['ClientSince']) . '" />
					</div>
				</div>
			</div>
		</div></div>';

	// Edit Card 3: Financials & Settings
	echo '<div class="card-v2 db-card-full" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 1v22M17 5H9.5a4.5 4.5 0 1 0 0 9h5a4.5 4.5 0 1 1 0 9H6"></path></svg>
					' . __('Financials & System Settings') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Discount (%)') . '</label>
							<input type="text" name="Discount" class="db-input db-number" value="' . $_POST['Discount'] . '" />
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Credit Limit') . '</label>
							<input type="text" name="CreditLimit" class="db-input db-number" value="' . $_POST['CreditLimit'] . '" />
						</div>
					</div>
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Payment Terms') . '</label>
							<select name="PaymentTerms" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['PaymentTerms']==$myr['termsindicator']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['termsindicator'] . '">' . $myr['terms'] . '</option>';
	}
	echo '			</select>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Credit Status') . '</label>
							<select name="HoldReason" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['HoldReason']==$myr['reasoncode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['reasoncode'] . '">' . $myr['reasondescription'] . '</option>';
	}
	echo '			</select>
						</div>
					</div>
					<div class="db-grid">
						<div class="db-field">
							<label class="db-label">' . __('Currency') . '</label>
							<select name="CurrCode" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['CurrCode']==$myr['currabrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['currabrev'] . '">' . $myr['currency'] . '</option>';
	}
	echo '			</select>
						</div>
						<div class="db-field">
							<label class="db-label">' . __('Language') . '</label>
							<select name="LanguageID" class="db-input">';
	foreach ($LanguagesArray as $lc => $ln){
		$sel = ($_POST['LanguageID'] == $lc) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $lc . '">' . $ln['LanguageName']  . '</option>';
	}
	echo '			</select>
						</div>
					</div>
				</div>
				<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />
				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label class="db-label">' . __('Show PO Line on Sales Orders') . '</label>
						<select name="CustomerPOLine" class="db-input">
							<option ' . ($_POST['CustomerPOLine']==0 ? 'selected="selected"' : '') . ' value="0">' . __('No') . '</option>
							<option ' . ($_POST['CustomerPOLine']==1 ? 'selected="selected"' : '') . ' value="1">' . __('Yes') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Invoicing Address Preference') . '</label>
						<select name="AddrInvBranch" class="db-input">
							<option ' . ($_POST['InvAddrBranch']==0 ? 'selected="selected"' : '') . ' value="0">' . __('Address to Head Office') . '</option>
							<option ' . ($_POST['InvAddrBranch']==1 ? 'selected="selected"' : '') . ' value="1">' . __('Address to Branch') . '</option>
						</select>
					</div>
				</div>
			</div>
		</div>';

	// Contacts Table
	echo '<div class="card-v2 db-card-full" style="margin-top: var(--space-6);">
			<div class="card-header-v2" style="display:flex; justify-content:space-between; align-items:center;">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
					' . __('Customer Contacts') . '
				</h3>
				<button type="submit" name="AddContact" class="db-btn db-btn-sm db-btn-secondary">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:6px;"><path d="M12 5v14M5 12h14"></path></svg>
					' . __('Add Contact') . '
				</button>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">

					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Name') . '</th>
								<th>' . __('Role') . '</th>
								<th>' . __('Phone') . '</th>
								<th>' . __('Email') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	
	$SQL = "SELECT * FROM custcontacts WHERE debtorno='" . $DebtorNo . "' ORDER BY contid";
	$Result = DB_query($SQL);
	while ($myr = DB_fetch_array($Result)) {
		echo '<tr>
				<td>' . $myr['contactname'] . '</td>
				<td>' . $myr['role'] . '</td>
				<td>' . $myr['phoneno'] . '</td>
				<td><a href="mailto:' . $myr['email'] . '">' . $myr['email'] . '</a></td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . $RootPath . '/AddCustomerContacts.php?Id=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?ID=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '&delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this contact?') . '\');">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</a>
					</div>
				</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';

	echo '<div class="db-card-actions" style="margin-top: 3rem; justify-content: center; padding-bottom: 2rem;">
			<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
				' . __('Update Customer Details') . '
			</button>
			<button type="submit" name="delete" class="db-btn db-btn-danger db-btn-large" style="margin-left: 1.5rem;" onclick="return confirm(\'' . __('Are you sure you wish to delete this customer record?') . '\');">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
				' . __('Delete Customer') . '
			</button>
		</div>';

	echo '</form>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
?>
