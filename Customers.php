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
			<div class="db-page-title">
				<div class="db-page-icon">
					<i class="fas fa-user-edit"></i>
				</div>
				<h1>' . $Title . '</h1>
			</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<i class="fas fa-search"></i> ' . __('Back to Search') . '
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
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-id-card"></i> ', __('General Information'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-fieldset">';
	
	if ($_SESSION['AutoDebtorNo']==0)  {
		echo '<div class="db-field">
				<label class="db-label" for="DebtorNo">' . __('Customer Code') . ':</label>
				<input type="text" data-type="no-illegal-chars" tabindex="1" name="DebtorNo" required="required" autofocus="autofocus" placeholder="'.__('alpha-numeric').'" class="db-input" maxlength="10" />
				<span class="db-field-help">'.__('Up to 10 characters. Prohibited:') . ' \' &quot; + . &amp; \\ &gt; &lt;</span>
			</div>';
	}

	echo '<div class="db-field">
			<label class="db-label" for="CustName">' . __('Customer Name') . ':</label>
			<input tabindex="2" type="text" name="CustName" required="required" class="db-input" maxlength="40" />
		</div>';

	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type");
	if (DB_num_rows($Result)==0){
		$DataError = 1;
		echo '<div class="db-field">' . prnMsg(__('No sales types defined'),'error') . '</div>';
	} else {
		echo '<div class="db-field">
				<label class="db-label" for="SalesType">' . __('Sales Type') . '/' . __('Price List') . ':</label>
				<select tabindex="9" name="SalesType" required="required" class="db-input">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="'. $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
		}
		DB_data_seek($Result,0);
		echo '</select>
			</div>';
	}

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	if (DB_num_rows($Result)==0){
		$DataError = 1;
		echo '<div class="db-field">' . prnMsg(__('No customer types defined'),'error') . '</div>';
	} else {
		echo '<div class="db-field">
				<label class="db-label" for="typeid">' . __('Customer Type') . ':</label>
				<select tabindex="9" name="typeid" required="required" class="db-input">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="'. $MyRow['typeid'] . '">' . $MyRow['typename'] . '</option>';
		}
		DB_data_seek($Result,0);
		echo '</select>
			</div>';
	}

	echo '</div></div></div>';

	// Card 2: Address & Contact
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-map-marker-alt"></i> ', __('Address & Contact'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-fieldset">
					<div class="db-field">
						<label class="db-label" for="Address1">' . __('Address Line 1 (Street)') . ':</label>
						<input tabindex="3" type="text" name="Address1" required="required" class="db-input" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address2">' . __('Address Line 2 (Street)') . ':</label>
						<input tabindex="4" type="text" name="Address2" class="db-input" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address3">' . __('Address Line 3 (Suburb/City)') . ':</label>
						<input tabindex="5" type="text" name="Address3" class="db-input" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address4">' . __('Address Line 4 (State/Province)') . ':</label>
						<input tabindex="6" type="text" name="Address4" class="db-input" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address5">' . __('Address Line 5 (Postal Code)') . ':</label>
						<input tabindex="7" type="text" name="Address5" class="db-input" maxlength="20" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address6">' . __('Country') . ':</label>
						<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $CountryEntry => $CountryName){
		echo '<option value="' . $CountryName . '">' . $CountryName  . '</option>';
	}
	echo '</select>
					</div>
					<div class="db-field">
						<label class="db-label" for="ClientSince">' . __('Customer Since') . ' (' . $_SESSION['DefaultDateFormat'] . '):</label>
						<input tabindex="10" type="date" name="ClientSince" value="' . date('Y-m-d') . '" class="db-input" />
					</div>
				</div>
			</div>
		</div></div>';

	// Card 3: Financials & Settings (Full width)
	echo '<div class="db-card db-card-full">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-coins"></i> ', __('Financials & Settings'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="Discount">' . __('Discount Percent') . ':</label>
							<input tabindex="11" type="text" class="db-input db-number" name="Discount" value="0" maxlength="4" />
						</div>
						<div class="db-field">
							<label class="db-label" for="DiscountCode">' . __('Discount Code') . ':</label>
							<input tabindex="12" type="text" name="DiscountCode" class="db-input" maxlength="2" />
						</div>
						<div class="db-field">
							<label class="db-label" for="PymtDiscount">' . __('Payment Discount Percent') . ':</label>
							<input tabindex="13" type="text" class="db-input db-number" name="PymtDiscount" value="0" maxlength="4" />
						</div>
					</div>
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="CreditLimit">' . __('Credit Limit') . ':</label>
							<input tabindex="14" type="text" class="db-input db-number" name="CreditLimit" required="required" value="' . locale_number_format($_SESSION['DefaultCreditLimit'],0) . '" maxlength="14" />
						</div>
						<div class="db-field">
							<label class="db-label" for="TaxRef">' . __('Tax Reference') . ':</label>
							<input tabindex="15" type="text" name="TaxRef" class="db-input" maxlength="20" />
						</div>
						<div class="db-field">
							<label class="db-label" for="PaymentTerms">' . __('Payment Terms') . ':</label>
							<select tabindex="15" name="PaymentTerms" required="required" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
	}
	echo '</select>
						</div>
					</div>
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="HoldReason">' . __('Credit Status') . ':</label>
							<select tabindex="16" name="HoldReason" required="required" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['reasoncode'] . '">' . $MyRow['reasondescription'] . '</option>';
	}
	echo '</select>
						</div>
						<div class="db-field">
							<label class="db-label" for="CurrCode">' . __('Customer Currency') . ':</label>
							<select tabindex="17" name="CurrCode" required="required" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="'. $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '</select>
						</div>
						<div class="db-field">
							<label class="db-label" for="LanguageID">' . __('Language') . ':</label>
							<select name="LanguageID" required="required" class="db-input">';
	foreach ($LanguagesArray as $LanguageCode => $LanguageName){
		$selected = ($_SESSION['Language'] == $LanguageCode) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $LanguageCode . '">' . $LanguageName['LanguageName']  . '</option>';
	}
	echo '</select>
						</div>
					</div>
				</div>
				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label class="db-label" for="CustomerPOLine">' . __('Customer PO Line on SO') . ':</label>
						<select tabindex="18" name="CustomerPOLine" required="required" class="db-input">
							<option selected="selected" value="0">' . __('No') . '</option>
							<option value="1">' . __('Yes') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label" for="AddrInvBranch">' . __('Invoice Addressing') . ':</label>
						<select tabindex="19" name="AddrInvBranch" required="required" class="db-input">
							<option selected="selected" value="0">' . __('Address to HO') . '</option>
							<option value="1">' . __('Address to Branch') . '</option>
						</select>
					</div>
				</div>
			</div>';

	if ($DataError == 0){
		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem;">
				<button tabindex="20" type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<i class="fas fa-plus"></i> ' . __('Add New Customer') . '
				</button>
				<button tabindex="21" type="reset" class="db-btn db-btn-secondary db-btn-large">
					<i class="fas fa-undo"></i> ' . __('Reset') . '
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
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-user-circle"></i> ', __('General Information'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-fieldset">
					<div class="db-field">
						<label class="db-label">' . __('Customer Code') . ':</label>
						<input type="text" class="db-input" value="' . $DebtorNo . '" disabled />
					</div>
					<div class="db-field">
						<label class="db-label" for="CustName">' . __('Customer Name') . ':</label>
						<input type="text" name="CustName" class="db-input" required value="' . $_POST['CustName'] . '" maxlength="40" />
					</div>';
	
	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
	echo '<div class="db-field">
			<label class="db-label" for="SalesType">' . __('Sales Type') . ':</label>
			<select name="SalesType" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['SalesType']==$myr['typeabbrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['typeabbrev'] . '">' . $myr['sales_type'] . '</option>';
	}
	echo '</select></div>';

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<div class="db-field">
			<label class="db-label" for="typeid">' . __('Customer Type') . ':</label>
			<select name="typeid" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['typeid']==$myr['typeid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['typeid'] . '">' . $myr['typename'] . '</option>';
	}
	echo '</select></div>';
	
	echo '</div></div></div>';

	// Edit Card 2: Address & Contact
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-map-marker-alt"></i> ', __('Address & Contact'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-fieldset">
					<div class="db-field">
						<label class="db-label" for="Address1">' . __('Street Address') . ':</label>
						<input type="text" name="Address1" class="db-input" required value="' . $_POST['Address1'] . '" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address2">' . __('Address Line 2') . ':</label>
						<input type="text" name="Address2" class="db-input" value="' . $_POST['Address2'] . '" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address3">' . __('Suburb/City') . ':</label>
						<input type="text" name="Address3" class="db-input" value="' . $_POST['Address3'] . '" maxlength="40" />
					</div>
					<div class="db-field">
						<label class="db-label" for="Address6">' . __('Country') . ':</label>
						<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $cn){
		$sel = (strtoupper($_POST['Address6']) == strtoupper($cn)) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $cn . '">' . $cn  . '</option>';
	}
	echo '</select></div>
					<div class="db-field">
						<label class="db-label" for="ClientSince">' . __('Customer Since') . ':</label>
						<input type="date" name="ClientSince" class="db-input" value="' . FormatDateForSQL($_POST['ClientSince']) . '" />
					</div>
				</div>
			</div>
		</div></div>';

	// Edit Card 3: Financials & Settings
	echo '<div class="db-card db-card-full">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-coins"></i> ', __('Financials & Settings'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="Discount">' . __('Discount %') . ':</label>
							<input type="text" name="Discount" class="db-input db-number" value="' . $_POST['Discount'] . '" />
						</div>
						<div class="db-field">
							<label class="db-label" for="CreditLimit">' . __('Credit Limit') . ':</label>
							<input type="text" name="CreditLimit" class="db-input db-number" value="' . $_POST['CreditLimit'] . '" />
						</div>
					</div>
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="PaymentTerms">' . __('Payment Terms') . ':</label>
							<select name="PaymentTerms" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['PaymentTerms']==$myr['termsindicator']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['termsindicator'] . '">' . $myr['terms'] . '</option>';
	}
	echo '</select></div>
						<div class="db-field">
							<label class="db-label" for="HoldReason">' . __('Credit Status') . ':</label>
							<select name="HoldReason" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['HoldReason']==$myr['reasoncode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['reasoncode'] . '">' . $myr['reasondescription'] . '</option>';
	}
	echo '</select></div>
					</div>
					<div class="db-fieldset">
						<div class="db-field">
							<label class="db-label" for="CurrCode">' . __('Currency') . ':</label>
							<select name="CurrCode" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['CurrCode']==$myr['currabrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="'. $myr['currabrev'] . '">' . $myr['currency'] . '</option>';
	}
	echo '</select></div>
						<div class="db-field">
							<label class="db-label" for="LanguageID">' . __('Language') . ':</label>
							<select name="LanguageID" class="db-input">';
	foreach ($LanguagesArray as $lc => $ln){
		$sel = ($_POST['LanguageID'] == $lc) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $lc . '">' . $ln['LanguageName']  . '</option>';
	}
	echo '</select></div>
					</div>
				</div>
			</div>
		</div>';

	// Contacts Table
	echo '<div class="db-card db-card-full">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-address-book"></i> ', __('Customer Contacts'), '</div>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">';
	
  	$SQL = "SELECT * FROM custcontacts WHERE debtorno='".$DebtorNo."' ORDER BY contid";
	$Result = DB_query($SQL);
	echo '<table class="db-table">
			<thead>
				<tr>
					<th>' . __('Name') . '</th>
					<th>' . __('Role') . '</th>
					<th>' . __('Phone') . '</th>
					<th>' . __('Email') . '</th>
					<th>' . __('Actions') . '</th>
				</tr>
			</thead>
			<tbody>';
	while ($myr = DB_fetch_array($Result)) {
		echo '<tr>
				<td>' . $myr['contactname'] . '</td>
				<td>' . $myr['role'] . '</td>
				<td>' . $myr['phoneno'] . '</td>
				<td><a href="mailto:' . $myr['email'] . '">' . $myr['email'] . '</a></td>
				<td>
					<div class="db-table-actions">
						<a href="' . $RootPath . '/AddCustomerContacts.php?Id=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '" class="db-btn db-btn-sm db-btn-secondary"><i class="fas fa-edit"></i></a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?ID=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '&delete=1" class="db-btn db-btn-sm db-btn-danger" onclick="return confirm(\'' . __('Delete this contact?') . '\');"><i class="fas fa-trash"></i></a>
					</div>
				</td>
			</tr>';
	}
	echo '</tbody></table>
				</div>
				<div class="db-card-actions">
					<button type="submit" name="AddContact" class="db-btn db-btn-secondary"><i class="fas fa-user-plus"></i> ' . __('Add New Contact') . '</button>
				</div>
			</div>
		</div>';

	echo '<div class="db-card-actions" style="margin-top: 2rem; justify-content: center;">
			<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large"><i class="fas fa-save"></i> ' . __('Update Customer') . '</button>
			<button type="submit" name="delete" class="db-btn db-btn-danger db-btn-large" onclick="return confirm(\'' . __('Are You Sure?') . '\');"><i class="fas fa-trash-alt"></i> ' . __('Delete Customer') . '</button>
		</div>';

	echo '</form>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
?>
