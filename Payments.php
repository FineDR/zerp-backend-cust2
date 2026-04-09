<?php

/* Entry of bank account payments either against an AP account or a general ledger payment - if the AP-GL link in company preferences is set */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefinePaymentClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Entry');
if (isset($_GET['SupplierID'])) { // Links to Manual before header.php
	$ViewTopic = 'AccountsPayable';
	$BookMark = 'SupplierPayments';
	$PageTitleText = __('Enter a Payment to, or Receipt from the Supplier');
} else {
	$ViewTopic = 'GeneralLedger';
	$BookMark = 'BankAccountPayments';
	$PageTitleText = __('Bank Account Payments Entry');
}
include(__DIR__ . '/includes/header.php');

if (isset($_POST['DatePaid'])) {
	$_POST['DatePaid'] = ConvertSQLDate($_POST['DatePaid']);
}

echo '<div class="db-page">';
echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg> ' . $PageTitleText . '</h2>
			<p class="db-page-subtitle">' . __('Record payments from bank accounts to suppliers or general ledger') . '</p>
		</div>
	</div>';
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['PaymentCancelled'])) {
	prnMsg(__('Payment Cancelled since cheque was not printed') , 'warning');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (empty($_GET['identifier'])) {
	$identifier = date('U'); // Unique session identifier to ensure that there is no conflict with other order entry session on the same machine.

} else {
	$identifier = $_GET['identifier'];
}

if (isset($_GET['NewPayment']) AND $_GET['NewPayment'] == 'Yes') {
	unset($_SESSION['PaymentDetail' . $identifier]->GLItems);
	unset($_SESSION['PaymentDetail' . $identifier]);
}

if (!isset($_SESSION['PaymentDetail' . $identifier])) {
	$_SESSION['PaymentDetail' . $identifier] = new Payment;
	$_SESSION['PaymentDetail' . $identifier]->GLItemCounter = 1;
}

if ((isset($_POST['UpdateHeader']) AND $_POST['BankAccount'] == '') OR (isset($_POST['Process']) AND $_POST['BankAccount'] == '')) {

	prnMsg(__('A bank account must be selected to make this payment from') , 'warn');
	$BankAccountEmpty = true;
} else {
	$BankAccountEmpty = false;
}

	<div class="db-card" style="margin-top: var(--space-6); background: var(--surface-alt); border-left: 4px solid var(--primary);">
		<div class="db-card-body" style="padding: var(--space-4); font-size: 0.875rem; color: var(--text-muted);">
			<svg width="18" height="18" viewBox="0 0 24 24" stroke="var(--primary)" fill="none" stroke-width="2.5" style="margin-right: 8px; vertical-align: middle;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
			' . __('Use this screen to enter payments FROM your bank account. To enter a receipt from a supplier, use a negative payment amount.') . '
		</div>
	</div><br />';

$SQL = "SELECT pagesecurity
		  FROM scripts
		 WHERE scripts.script = 'BankAccountBalances.php'";

$ErrMsg = __('The security for G/L Accounts cannot be retrieved because');
$Security2Result = DB_query($SQL, $ErrMsg);
$MyUserRow = DB_fetch_array($Security2Result);
$CashSecurity = $MyUserRow['pagesecurity'];

if (isset($_GET['SupplierID'])) {
	/*The page was called with a supplierID check it is valid and default the inputs for Supplier Name and currency of payment */

	unset($_SESSION['PaymentDetail' . $identifier]->GLItems);
	unset($_SESSION['PaymentDetail' . $identifier]);
	$_SESSION['PaymentDetail' . $identifier] = new Payment;
	$_SESSION['PaymentDetail' . $identifier]->GLItemCounter = 1;

	$SQL = "SELECT suppname,
				address1,
				address2,
				address3,
				address4,
				address5,
				address6,
				currcode,
				factorcompanyid
			FROM suppliers
			WHERE supplierid='" . $_GET['SupplierID'] . "'";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {

		prnMsg(__('The supplier code that this payment page was called with is not a currently defined supplier code') . '. ' . __('If this page is called from the selectSupplier page then this assures that a valid supplier is selected') , 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();

	}
	else {

		$MyRow = DB_fetch_array($Result);
		if ($MyRow['factorcompanyid'] == 0) {
			$_SESSION['PaymentDetail' . $identifier]->SuppName = $MyRow['suppname'];
			$_SESSION['PaymentDetail' . $identifier]->Address1 = $MyRow['address1'];
			$_SESSION['PaymentDetail' . $identifier]->Address2 = $MyRow['address2'];
			$_SESSION['PaymentDetail' . $identifier]->Address3 = $MyRow['address3'];
			$_SESSION['PaymentDetail' . $identifier]->Address4 = $MyRow['address4'];
			$_SESSION['PaymentDetail' . $identifier]->Address5 = $MyRow['address5'];
			$_SESSION['PaymentDetail' . $identifier]->Address6 = $MyRow['address6'];
			$_SESSION['PaymentDetail' . $identifier]->SupplierID = $_GET['SupplierID'];
			$_SESSION['PaymentDetail' . $identifier]->Currency = $MyRow['currcode'];
			$_POST['Currency'] = $_SESSION['PaymentDetail' . $identifier]->Currency;

		}
		else {

			$FactorSQL = "SELECT coyname,
							address1,
							address2,
							address3,
							address4,
							address5,
							address6
						FROM factorcompanies
						WHERE id='" . $MyRow['factorcompanyid'] . "'";

			$FactorResult = DB_query($FactorSQL);
			$MyFactorRow = DB_fetch_array($FactorResult);
			$_SESSION['PaymentDetail' . $identifier]->SuppName = $MyRow['suppname'] . ' ' . __('care of') . ' ' . $MyFactorRow['coyname'];
			$_SESSION['PaymentDetail' . $identifier]->Address1 = $MyFactorRow['address1'];
			$_SESSION['PaymentDetail' . $identifier]->Address2 = $MyFactorRow['address2'];
			$_SESSION['PaymentDetail' . $identifier]->Address3 = $MyFactorRow['address3'];
			$_SESSION['PaymentDetail' . $identifier]->Address4 = $MyFactorRow['address4'];
			$_SESSION['PaymentDetail' . $identifier]->Address5 = $MyFactorRow['address5'];
			$_SESSION['PaymentDetail' . $identifier]->Address6 = $MyFactorRow['address6'];
			$_SESSION['PaymentDetail' . $identifier]->SupplierID = $_GET['SupplierID'];
			$_SESSION['PaymentDetail' . $identifier]->Currency = $MyRow['currcode'];
			$_POST['Currency'] = $_SESSION['PaymentDetail' . $identifier]->Currency;
		}

		if (isset($_GET['Amount']) AND is_numeric($_GET['Amount'])) {
			$_SESSION['PaymentDetail' . $identifier]->Amount = filter_number_format($_GET['Amount']);
		}
	}
}

if (isset($_POST['BankAccount']) AND $_POST['BankAccount'] != '') {

	$_SESSION['PaymentDetail' . $identifier]->Account = $_POST['BankAccount'];
	// Get the bank account currency and set that too
	$ErrMsg = __('Could not get the currency of the bank account');

	$Result = DB_query("SELECT currcode,
								decimalplaces
						FROM bankaccounts
						INNER JOIN currencies
						ON bankaccounts.currcode = currencies.currabrev
						WHERE accountcode ='" . $_POST['BankAccount'] . "'", $ErrMsg);

	$MyRow = DB_fetch_array($Result);
	if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency != $MyRow['currcode']) {
		//then we'd better update the functional exchange rate
		$DefaultFunctionalRate = true;
		$_SESSION['PaymentDetail' . $identifier]->AccountCurrency = $MyRow['currcode'];
		$_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];
	}
	else {
		$DefaultFunctionalRate = false;
	}

} else {

	$_SESSION['PaymentDetail' . $identifier]->AccountCurrency = $_SESSION['CompanyRecord']['currencydefault'];
	$_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces = $_SESSION['CompanyRecord']['decimalplaces'];

}
if (isset($_POST['DatePaid']) AND $_POST['DatePaid'] != '' AND Is_Date($_POST['DatePaid'])) {
	$_SESSION['PaymentDetail' . $identifier]->DatePaid = $_POST['DatePaid'];
}
if (isset($_POST['ExRate']) AND $_POST['ExRate'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->ExRate = filter_number_format($_POST['ExRate']); //ex rate between payment currency and account currency

}
if (isset($_POST['FunctionalExRate']) AND $_POST['FunctionalExRate'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->FunctionalExRate = filter_number_format($_POST['FunctionalExRate']); //ex rate between bank account currency and functional (business home) currency

}
if (isset($_POST['Paymenttype']) AND $_POST['Paymenttype'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->Paymenttype = $_POST['Paymenttype'];
	//lets validate the paymenttype here
	$SQL = "SELECT usepreprintedstationery
			FROM paymentmethods
			WHERE paymentname='" . $_SESSION['PaymentDetail' . $identifier]->Paymenttype . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 1) {
		if (empty($_POST['ChequeNum'])) {
			prnMsg(__('The cheque number should not be empty') , 'warn');
			$Errors[] = 'ChequeNum';
		}
		else {
			$ChequeSQL = "SELECT count(chequeno) FROM supptrans WHERE chequeno='" . $_POST['ChequeNum'] . "'";
			$ErrMsg = __('Failed to retrieve cheque number data');
			$ChequeResult = DB_query($ChequeSQL, $ErrMsg);
			$ChequeRow = DB_fetch_row($ChequeResult);
			if ($ChequeRow[0] > 0) {
				prnMsg(__('The cheque has already been used') , 'warn');
				$Errors[] = 'ChequeNum';
			}
		}
	}
}

if (isset($_POST['Currency']) AND $_POST['Currency'] != '') {
	/* Payment currency is the currency that is being paid */
	$_SESSION['PaymentDetail' . $identifier]->Currency = $_POST['Currency']; // Payment currency
	if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency == $_SESSION['CompanyRecord']['currencydefault']) {
		$_POST['FunctionalExRate'] = 1;
		$_SESSION['PaymentDetail' . $identifier]->FunctionalExRate = 1;
		$SuggestedFunctionalExRate = 1;

	}
	else {
		/*To illustrate the rates required
			Take an example functional currency NZD payment in USD from an AUD bank account
			1 NZD = 0.80 USD
			1 NZD = 0.90 AUD
			The FunctionalExRate = 0.90 - the rate between the functional currency and the bank account currency
			The payment ex rate is the rate at which one can purchase the payment currency in the bank account currency
			or 0.8/0.9 = 0.88889
		*/

		/*Get suggested FunctionalExRate - between bank account and home functional currency */
		$Result = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['PaymentDetail' . $identifier]->AccountCurrency . "'");
		$MyRow = DB_fetch_row($Result);
		$SuggestedFunctionalExRate = $MyRow[0];
		if ($DefaultFunctionalRate) {
			$_SESSION['PaymentDetail' . $identifier]->FunctionalExRate = $SuggestedFunctionalExRate;
		}
	}

	if ($_POST['Currency'] == $_SESSION['PaymentDetail' . $identifier]->AccountCurrency) {
		/* if the currency being paid is the same as the bank account currency then default ex rate to 1 */
		$_POST['ExRate'] = 1;
		$_SESSION['PaymentDetail' . $identifier]->ExRate = 1; //ex rate between payment currency and account currency is 1 if they are the same!!
		$SuggestedExRate = 1;
	}
	elseif (isset($_POST['Currency'])) {
		/*Get the exchange rate between the bank account currency and the payment currency*/
		$Result = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['PaymentDetail' . $identifier]->Currency . "'");
		$MyRow = DB_fetch_row($Result);
		$TableExRate = $MyRow[0]; //this is the rate of exchange between the functional currency and the payment currency
		/*Calculate cross rate to suggest appropriate exchange rate between payment currency and account currency */
		$SuggestedExRate = $TableExRate / $SuggestedFunctionalExRate;
	}
}

// Reference in banking transactions:
if (isset($_POST['BankTransRef']) AND $_POST['BankTransRef'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->BankTransRef = $_POST['BankTransRef'];
}
// Narrative in general ledger transactions:
if (isset($_POST['Narrative']) AND $_POST['Narrative'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->Narrative = $_POST['Narrative'];
}
// Supplier narrative in general ledger transactions:
if (isset($_POST['gltrans_narrative'])) {
	if ($_POST['gltrans_narrative'] == '') {
		$_SESSION['PaymentDetail' . $identifier]->GLTransNarrative = $_POST['Narrative']; // If blank, it uses the bank narrative.

	}
	else {
		$_SESSION['PaymentDetail' . $identifier]->GLTransNarrative = $_POST['gltrans_narrative'];
	}
}
// Supplier reference in supplier transactions:
if (isset($_POST['supptrans_suppreference'])) {
	if ($_POST['supptrans_suppreference'] == '') {
		$_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference = $_POST['Paymenttype']; // If blank, it uses the payment type.

	}
	else {
		$_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference = $_POST['supptrans_suppreference'];
	}
}
// Transaction text in supplier transactions:
if (isset($_POST['supptrans_transtext'])) {
	if ($_POST['supptrans_transtext'] == '') {
		$_SESSION['PaymentDetail' . $identifier]->SuppTransTransText = $_POST['Narrative']; // If blank, it uses the narrative.

	}
	else {
		$_SESSION['PaymentDetail' . $identifier]->SuppTransTransText = $_POST['supptrans_transtext'];
	}
}

if (isset($_POST['Amount']) AND $_POST['Amount'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->Amount = filter_number_format($_POST['Amount']);
} else {
	if (!isset($_SESSION['PaymentDetail' . $identifier]->Amount)) {
		$_SESSION['PaymentDetail' . $identifier]->Amount = 0;
	}
}

if (isset($_POST['Discount']) AND $_POST['Discount'] != '') {
	$_SESSION['PaymentDetail' . $identifier]->Discount = filter_number_format($_POST['Discount']);
} else {
	if (!isset($_SESSION['PaymentDetail' . $identifier]->Discount)) {
		$_SESSION['PaymentDetail' . $identifier]->Discount = 0;
	}
}

if (isset($_POST['CommitBatch']) AND empty($Errors)) {

	/* once the GL analysis of the payment is entered (if the Creditors_GLLink is active),
	process all the data in the session cookie into the DB creating a banktrans record for
	the payment in the batch and SuppTrans record for the supplier payment if a supplier was selected
	A GL entry is created for each GL entry (only one for a supplier entry) and one for the bank
	account credit.

	NB allocations against supplier payments are a separate exercise

	if GL integrated then
	first off run through the array of payment items $_SESSION['Payment']->GLItems and
	create GL Entries for the GL payment items
	*/

	/*First off check we have an amount entered as paid ?? */
	$TotalAmount = 0;
	foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems AS $PaymentItem) {
		$TotalAmount += $PaymentItem->Amount;
	}

	if ($TotalAmount == 0 AND ($_SESSION['PaymentDetail' . $identifier]->Discount + $_SESSION['PaymentDetail' . $identifier]->Amount) / $_SESSION['PaymentDetail' . $identifier]->ExRate == 0) {
		prnMsg(__('This payment has no amounts entered and will not be processed') , 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	if ($_POST['BankAccount'] == '') {
		prnMsg(__('No bank account has been selected so this payment cannot be processed') , 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	/*Make an array of the defined bank accounts */
	$SQL = "SELECT bankaccounts.accountcode
			FROM bankaccounts,
				chartmaster
			WHERE bankaccounts.accountcode=chartmaster.accountcode";
	$Result = DB_query($SQL);
	$BankAccounts = array();
	$i = 0;

	while ($Act = DB_fetch_row($Result)) {
		$BankAccounts[$i] = $Act[0];
		$i++;
	}

	$PeriodNo = GetPeriod($_SESSION['PaymentDetail' . $identifier]->DatePaid);

	$SQL = "SELECT usepreprintedstationery
			FROM paymentmethods
			WHERE paymentname='" . $_SESSION['PaymentDetail' . $identifier]->Paymenttype . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	// first time through commit if supplier cheque then print it first
	if ((!isset($_POST['ChequePrinted'])) AND (!isset($_POST['PaymentCancelled'])) AND ($MyRow[0] == 1)) {
		// it is a supplier payment by cheque and haven't printed yet so print cheque
		//check the cheque number
		if (empty($_POST['ChequeNum'])) {
			prnMsg(__('There are no Check Number input') , 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		elseif (!is_numeric($_POST['ChequeNum'])) { //check if this cheque no has been used
			prnMsg(__('The cheque no should be numeric') , 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		else {
			$ChequeSQL = "SELECT count(chequeno) FROM  supptrans WHERE chequeno='" . $_POST['ChequeNum'] . "'";
			$ErrMsg = __('Failed to retrieve cheque number data');
			$ChequeResult = DB_query($ChequeSQL, $ErrMsg);
			$ChequeRow = DB_fetch_row($ChequeResult);
			if ($ChequeRow[0] > 0) {
				prnMsg(__('The cheque has already been used') , 'error');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
		}
		//store the paid array here;
		$PaidArray = array();
		foreach ($_POST as $Name => $Value) {
			if (substr($Name, 0, 4) == 'paid' AND $Value > 0) {
				$PaidArray[substr($Name, 4) ] = $Value;
			}
		}
		if (!empty($PaidArray)) {
			$PaidJ = base64_encode(serialize($PaidArray));
			$PaidInput = '<input type="hidden" name="PaidArray" value="' . $PaidJ . '" />';
		}
		else {
			$PaidInput = '';
		}

		echo '<br />
			<a href="' . $RootPath . '/PrintCheque.php?ChequeNum=' . $_POST['ChequeNum'] . '&amp;identifier=' . $identifier . '" target="_blank">' . __('Print Cheque using pre-printed stationery') . '</a>
			<br />
			<br />
			<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') , '?identifier=', urlencode($identifier) , '">
			<div>
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />', __('Has the cheque been printed') , '?
			<br />
			<br />', $PaidInput, '
			<input type="hidden" name="BankTransRef" value="', $_POST['BankTransRef'], '" />
			<input type="hidden" name="ChequeNum" value="', $_POST['ChequeNum'], '" />
			<input type="hidden" name="CommitBatch" value="', $_POST['CommitBatch'], '" />
			<input type="hidden" name="BankAccount" value="', $_POST['BankAccount'], '" />
			<input type="submit" name="ChequePrinted" value="', __('Yes / Continue') , '" />&nbsp;&nbsp;
			<input type="submit" name="PaymentCancelled" value="', __('No / Cancel Payment') , '" />
			<br />', __('Payment amount') , ' = ', $_SESSION['PaymentDetail' . $identifier]->Amount, '</div>
			</form>';

	}
	else {

		//Start a transaction to do the whole lot inside
		DB_Txn_Begin();

		if ($_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {

			//its a nominal bank transaction type 1
			$TransNo = GetNextTransNo(1);
			$TransType = 1;

			if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1) { /* then enter GLTrans */
				$TotalAmount = 0;
				foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {

					/*The functional currency amount will be the
					 payment currenct amount / the bank account currency exchange rate - to get to the bank account currency
					 then / the functional currency exchange rate to get to the functional currency */
					if ($PaymentItem->Cheque == '') {
						$PaymentItem->Cheque = 0;
					}
					$SQL = "INSERT INTO gltrans (
								type,
								typeno,
								trandate,
								periodno,
								account,
								narrative,
								amount,
								chequeno
							) VALUES (
								1,'" .
								$TransNo . "','" .
								FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
								$PeriodNo . "','" .
								$PaymentItem->GLCode . "','" .
								mb_substr($PaymentItem->Narrative, 0, 200) . "','" .
								($PaymentItem->Amount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "','" .
								$PaymentItem->Cheque . "'
							)";
					$ErrMsg = __('Cannot insert a GL entry for the payment using the SQL');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					InsertGLTags($PaymentItem->Tag);
					$TotalAmount += $PaymentItem->Amount;
				}
				$_SESSION['PaymentDetail' . $identifier]->Amount = $TotalAmount;
				$_SESSION['PaymentDetail' . $identifier]->Discount = 0;
			}

			//Run through the GL postings to check to see if there is a posting to another bank account (or the same one) if there is then a receipt needs to be created for this account too
			foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {

				if (in_array($PaymentItem->GLCode, $BankAccounts)) {

					/*Need to deal with the case where the payment from one bank account could be to a bank account in another currency */

					/*Get the currency and rate of the bank account transferring to*/
					$SQL = "SELECT currcode, rate
							FROM bankaccounts INNER JOIN currencies
							ON bankaccounts.currcode = currencies.currabrev
							WHERE accountcode='" . $PaymentItem->GLCode . "'";
					$TrfToAccountResult = DB_query($SQL);
					$TrfToBankRow = DB_fetch_array($TrfToAccountResult);
					$TrfToBankCurrCode = $TrfToBankRow['currcode'];
					$TrfToBankExRate = $TrfToBankRow['rate'];
                    
                    $SQL = "SELECT currcode, rate
                            FROM bankaccounts INNER JOIN currencies
                            ON bankaccounts.currcode = currencies.currabrev
                            WHERE accountcode='" . $_SESSION['PaymentDetail' . $identifier]->Account . "'";
                    $TrfFromAccountResult = DB_query($SQL);
                    $TrfFromBankRow = DB_fetch_array($TrfFromAccountResult);
                    $TrfFromBankCurrCode = $TrfFromBankRow['currcode'];
                    $TrfFromBankExRate = $TrfFromBankRow['rate'];

					if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency == $TrfToBankCurrCode) {
						/*Make sure to use the same rate if the transfer is between two bank accounts in the same currency */
						$TrfToBankExRate = $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate;
					}
					if ($_SESSION['PaymentDetail' . $identifier]->Currency == $TrfToBankCurrCode) {
						$ExRate = 1;
						$TrfToBankExRate = $_SESSION['PaymentDetail' . $identifier]->ExRate;
					}
					else {
						$ExRate = ($_SESSION['PaymentDetail' . $identifier]->ExRate * $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) / $TrfFromBankExRate;
					}

					/*Consider an example
					 functional currency NZD
					 bank account in AUD - 1 NZD = 0.90 AUD (FunctionalExRate)
					 paying USD - 1 AUD = 0.85 USD (ExRate)
					 to a bank account in EUR - 1 NZD = 0.52 EUR

					 oh yeah - now we are getting tricky!
					 Lets say we pay USD 100 from the AUD bank account to the EUR bank account

					 To get the ExRate for the bank account we are transferring money to
					 we need to use the cross rate between the NZD-AUD/NZD-EUR
					 and apply this to the

					 the payment record will read
					 exrate = 0.85 (1 AUD = USD 0.85)
					 amount = 100 (USD)
					 functionalexrate = 0.90 (1 NZD = AUD 0.90)

					 the receipt record will read

					 amount 100 (USD)
					 exrate (1 EUR = (0.85 x 0.90)/0.52 USD)
					 					(ExRate x FunctionalExRate) / USD Functional ExRate
					 functionalexrate = (1NZD = EUR 0.52)

					*/

					$ReceiptTransNo = GetNextTransNo(2);
					$SQL = "INSERT INTO banktrans (
								transno,
								type,
								bankact,
								ref,
								exrate,
								functionalexrate,
								transdate,
								banktranstype,
								amount,
								currcode
							) VALUES ('" .
								$ReceiptTransNo . "',
								2,'" .
								$PaymentItem->GLCode . "','" .
								'@' . $TransNo . ' ' . __('Act Transfer From') . ' ' . $_SESSION['PaymentDetail' . $identifier]->Account . ' - ' . $PaymentItem->Narrative . "','" .
								$ExRate . "','" .
								$TrfToBankExRate . "','" .
								FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
								$_SESSION['PaymentDetail' . $identifier]->Paymenttype . "','" .
								$PaymentItem->Amount . "','" .
								$_SESSION['PaymentDetail' . $identifier]->Currency . "'
							)";
					$ErrMsg = __('Cannot insert a bank transaction because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
			}
		}
		else {

			/* Get an array of supptans id fields that were paid */
			if (!isset($_POST['PaidArray'])) {
				$PaidArray = array();
				foreach ($_POST as $Name => $Value) {
					if (substr($Name, 0, 4) == 'paid' AND $Value > 0) {
						$PaidArray[substr($Name, 4) ] = $Value;
					}
				}
			}
			else {
				$PaidArray = unserialize(base64_decode($_POST['PaidArray']));
			}

			/*Its a supplier payment type 22 */
			$CreditorTotal = (($_SESSION['PaymentDetail' . $identifier]->Discount + $_SESSION['PaymentDetail' . $identifier]->Amount) / $_SESSION['PaymentDetail' . $identifier]->ExRate) / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate;

			$TransNo = GetNextTransNo(22);
			$TransType = 22;

			/* Create a SuppTrans entry for the supplier payment */
			$SQL = "INSERT INTO supptrans (
							transno,
							type,
							supplierno,
							trandate,
							inputdate,
							suppreference,
							rate,
							ovamount,
							transtext,
							chequeno
						) VALUES ('" .
							$TransNo . "',
							22,'" .
							$_SESSION['PaymentDetail' . $identifier]->SupplierID . "','" .
							FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
							date('Y-m-d H-i-s') . "','" .
							$_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference . "','" .
							($_SESSION['PaymentDetail' . $identifier]->FunctionalExRate * $_SESSION['PaymentDetail' . $identifier]->ExRate) . "','" .
							(-$_SESSION['PaymentDetail' . $identifier]->Amount - $_SESSION['PaymentDetail' . $identifier]->Discount) . "','" .
							$_SESSION['PaymentDetail' . $identifier]->SuppTransTransText . "','" .
							$_POST['ChequeNum'] . "'
						)";
			$ErrMsg = __('Cannot insert a payment transaction against the supplier because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
			$SQL = "SELECT id FROM supptrans WHERE transno='" . $TransNo . "' AND type=22";
			$Result = DB_query($SQL, '', '', true);
			$MyRow = DB_fetch_array($Result);
			$PaymentID = $MyRow['id'];
			if (sizeof($PaidArray) > 0) {
				foreach ($PaidArray as $PaidID => $PaidAmount) {
					/* Firstly subtract from the payment the amount of the invoice  */
					$SQL = "UPDATE supptrans SET alloc=alloc-" . $PaidAmount . " WHERE id='" . $PaymentID . "'";
					$ErrMsg = __('Cannot update an allocation against the supplier because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					/* Then add theamount of the invoice to the invoice allocation */
					$SQL = "UPDATE supptrans SET alloc=alloc+" . $PaidAmount . " WHERE id='" . $PaidID . "'";
					$ErrMsg = __('Cannot update an allocation against the supplier because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					/* Finally update the supplier allocations table */
					$SQL = "INSERT INTO suppallocs (amt,
													datealloc,
													transid_allocfrom,
													transid_allocto
												) VALUES (
													'" . $PaidAmount . "',
													'" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "',
													'" . $PaymentID . "',
													'" . $PaidID . "'
												)";
					$ErrMsg = __('Cannot update an allocation against the supplier because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
			}

			/*Update the supplier master with the date and amount of the last payment made */
			$SQL = "UPDATE suppliers
					SET	lastpaiddate = '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "',
						lastpaid='" . $_SESSION['PaymentDetail' . $identifier]->Amount . "'
					WHERE suppliers.supplierid='" . $_SESSION['PaymentDetail' . $identifier]->SupplierID . "'";
			$ErrMsg = __('Cannot update the supplier record for the date of the last payment made because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$_SESSION['PaymentDetail' . $identifier]->GLTransNarrative = $_SESSION['PaymentDetail' . $identifier]->SupplierID . ' - ' . $_SESSION['PaymentDetail' . $identifier]->GLTransNarrative;

			if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1) { /* then do the supplier control GLTrans */
				/* Now debit creditors account with payment + discount */

				$SQL = "INSERT INTO gltrans (
							type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount
						) VALUES (
							22,'" .
							$TransNo . "','" .
							FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
							$PeriodNo . "','" .
							$_SESSION['CompanyRecord']['creditorsact'] . "','" .
							mb_substr($_SESSION['PaymentDetail' . $identifier]->GLTransNarrative, 0, 200) . "','" .
							$CreditorTotal . "'
						)";
				$ErrMsg = __('Cannot insert a GL transaction for the creditors account debit because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				if ($_SESSION['PaymentDetail' . $identifier]->Discount != 0) {
					/* Now credit Discount received account with discounts */
					$SQL = "INSERT INTO gltrans (
								type,
								typeno,
								trandate,
								periodno,
								account,
								narrative,
								amount
							) VALUES (
								22,'" .
								$TransNo . "','" .
								FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
								$PeriodNo . "','" .
								$_SESSION['CompanyRecord']['pytdiscountact'] . "','" .
								mb_substr($_SESSION['PaymentDetail' . $identifier]->GLTransNarrative, 0, 200) . "','" .
								(-$_SESSION['PaymentDetail' . $identifier]->Discount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "'
							)";
					$ErrMsg = __('Cannot insert a GL transaction for the payment discount credit because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} // end if discount

			} // end if gl creditors

		} // end if supplier
		if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1) { /* then do the common GLTrans */

			if ($_SESSION['PaymentDetail' . $identifier]->Amount != 0) {
				/* Bank account entry first */
				$SQL = "INSERT INTO gltrans (
							type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount
						) VALUES ('" .
							$TransType . "','" .
							$TransNo . "','" .
							FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
							$PeriodNo . "','" .
							$_SESSION['PaymentDetail' . $identifier]->Account . "','" .
							mb_substr($_SESSION['PaymentDetail' . $identifier]->Narrative, 0, 200) . "','" .
							(-$_SESSION['PaymentDetail' . $identifier]->Amount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "'
						)";
				$ErrMsg = __('Cannot insert a GL transaction for the bank account credit because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				EnsureGLEntriesBalance($TransType, $TransNo);
			}
		}

		/*now enter the BankTrans entry */
		$SQL = "INSERT INTO banktrans (
					transno,
					type,
					bankact,
					ref,
					exrate,
					functionalexrate,
					transdate,
					banktranstype,
					amount,
					currcode,
					chequeno
				) VALUES ('" .
					$TransNo . "','" .
					$TransType . "','" .
					$_SESSION['PaymentDetail' . $identifier]->Account . "','" .
					$_SESSION['PaymentDetail' . $identifier]->BankTransRef . "','" .
					$_SESSION['PaymentDetail' . $identifier]->ExRate . "','" .
					$_SESSION['PaymentDetail' . $identifier]->FunctionalExRate . "','" .
					FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "','" .
					$_SESSION['PaymentDetail' . $identifier]->Paymenttype . "','" .
					-$_SESSION['PaymentDetail' . $identifier]->Amount . "','" .
					$_SESSION['PaymentDetail' . $identifier]->Currency . "','" .
					$_POST['ChequeNum'] . "'
				)";
		$ErrMsg = __('Cannot insert a bank transaction because');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		DB_Txn_Commit();
		prnMsg(__('Payment') . ' ' . $TransNo . ' ' . __('has been successfully entered') , 'success');

		$LastSupplier = ($_SESSION['PaymentDetail' . $identifier]->SupplierID);

		unset($_POST['BankAccount']);
		unset($_POST['DatePaid']);
		unset($_POST['ExRate']);
		unset($_POST['Paymenttype']);
		unset($_POST['Currency']);
		unset($_POST['Narrative']);
		unset($_POST['gltrans_narrative']);
		unset($_POST['supptrans_suppreference']);
		unset($_POST['supptrans_transtext']);
		unset($_POST['Amount']);
		unset($_POST['Discount']);
		unset($_POST['FunctionalExRate']);
		unset($_SESSION['PaymentDetail' . $identifier]->GLItems);
		unset($_SESSION['PaymentDetail' . $identifier]->SupplierID);
		unset($_SESSION['PaymentDetail' . $identifier]);

		/*Set up a newy in case user wishes to enter another */
		if (isset($LastSupplier) and $LastSupplier != '') {
			$SupplierSQL = "SELECT suppname FROM suppliers
					WHERE supplierid='" . $LastSupplier . "'";
			$SupplierResult = DB_query($SupplierSQL);
			$SupplierRow = DB_fetch_array($SupplierResult);
			$TransSQL = "SELECT id FROM supptrans WHERE type=22 AND transno='" . $TransNo . "'";
			$TransResult = DB_query($TransSQL);
			$TransRow = DB_fetch_array($TransResult);
			echo '<br /><a href="' . $RootPath . '/SupplierAllocations.php?AllocTrans=' . $TransRow['id'] . '">' . __('Allocate this payment') . '</a>';
			echo '<br /><a href="' . $RootPath . '/Payments.php?SupplierID=' . $LastSupplier . '">' . __('Enter another Payment for') . ' ' . $SupplierRow['suppname'] . '</a>';
		}
		else {
			echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Enter another General Ledger Payment') . '</a><br />';
		}
	}

	include(__DIR__ . '/includes/footer.php');
	exit();

} elseif (isset($_GET['Delete'])) {
	/* User hit delete the receipt entry from the batch */
	$_SESSION['PaymentDetail' . $identifier]->Remove_GLItem($_GET['Delete']);
	//recover the bank account relative setting
	$_POST['BankAccount'] = $_SESSION['PaymentDetail' . $identifier]->Account;
	$_POST['DatePaid'] = $_SESSION['PaymentDetail' . $identifier]->DatePaid;
	$_POST['Currency'] = $_SESSION['PaymentDetail' . $identifier]->Currency;
	$_POST['ExRate'] = $_SESSION['PaymentDetail' . $identifier]->ExRate;
	$_POST['FunctionalExRate'] = $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate;
	$_POST['PaymentType'] = $_SESSION['PaymentDetail' . $identifier]->Paymenttype;
	$_POST['BankTransRef'] = $_SESSION['PaymentDetail' . $identifier]->BankTransRef;
	$_POST['Narrative'] = $_SESSION['PaymentDetail' . $identifier]->Narrative;

} elseif (isset($_POST['Process']) AND !$BankAccountEmpty) { //user hit submit a new GL Analysis line into the payment
	if (!empty($_POST['Cheque'])) {
		$ChequeNoSQL = "SELECT transno FROM supptrans WHERE chequeno='" . $_POST['Cheque'] . "'";
		$ChequeNoResult = DB_query($ChequeNoSQL);
	}

	if (!isset($_POST['Tag'])) {
		$_POST['Tag'] = array();
	}

	if (is_numeric($_POST['GLManualCode'])) {

		$SQL = "SELECT accountname
				FROM chartmaster
				WHERE accountcode='" . $_POST['GLManualCode'] . "'";

		$Result = DB_query($SQL);

		if (DB_num_rows($Result) == 0) {
			prnMsg(__('The manual GL code entered does not exist in the database') . ' - ' . __('so this GL analysis item could not be added') , 'warn');
			unset($_POST['GLManualCode']);
		}
		elseif (isset($ChequeNoResult) AND DB_num_rows($ChequeNoResult) != 0 AND $_POST['Cheque'] != '') {
			prnMsg(__('The Cheque/Voucher number has already been used') . ' - ' . __('This GL analysis item could not be added') , 'error');
		}
		else {
			$MyRow = DB_fetch_array($Result);
			$AllowThisPosting = true;
			if ($_SESSION['ProhibitJournalsToControlAccounts'] == 1) {
				if ($_SESSION['CompanyRecord']['gllink_debtors'] == '1' AND $_POST['GLManualCode'] == $_SESSION['CompanyRecord']['debtorsact']) {
					prnMsg(__('Payments involving the debtors control account cannot be entered. The general ledger debtors ledger (AR) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration') , 'warn');
					$AllowThisPosting = false;
				}
				if ($_SESSION['CompanyRecord']['gllink_creditors'] == '1' AND ($_POST['GLManualCode'] == $_SESSION['CompanyRecord']['creditorsact'] OR $_POST['GLManualCode'] == $_SESSION['CompanyRecord']['grnact'])) {
					prnMsg(__('Payments involving the creditors control account or the GRN suspense account cannot be entered. The general ledger creditors ledger (AP) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration') , 'warn');
					$AllowThisPosting = false;
				}
				if ($_POST['GLManualCode'] == $_SESSION['CompanyRecord']['retainedearnings']) {
					prnMsg(__('Payments involving the retained earnings control account cannot be entered. This account is automtically maintained.') , 'warn');
					$AllowThisPosting = false;
				}
			}
			if ($AllowThisPosting) {
				$_SESSION['PaymentDetail' . $identifier]->add_to_glanalysis(filter_number_format($_POST['GLAmount']) , $_POST['GLNarrative'], $_POST['GLManualCode'], $MyRow['accountname'], $_POST['Tag'], $_POST['Cheque']);
				unset($_POST['GLManualCode']);
			}
		}
	}
	elseif (isset($ChequeNoResult) AND DB_num_rows($ChequeNoResult) != 0 AND $_POST['Cheque'] != '') {
		prnMsg(__('The cheque number has already been used') . ' - ' . __('This GL analysis item could not be added') , 'error');
	}
	elseif ($_POST['GLCode'] == '') {
		prnMsg(__('No General Ledger code has been chosen') . ' - ' . __('so this GL analysis item could not be added') , 'warn');
	}
	else {
		$SQL = "SELECT accountname FROM chartmaster WHERE accountcode='" . $_POST['GLCode'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_SESSION['PaymentDetail' . $identifier]->add_to_glanalysis(filter_number_format($_POST['GLAmount']) , $_POST['GLNarrative'], $_POST['GLCode'], $MyRow['accountname'], $_POST['Tag'], $_POST['Cheque']);
	}

	/*Make sure the same receipt is not double processed by a page refresh */
	$_POST['Cancel'] = 1;
}

if (isset($_POST['Cancel'])) {
	unset($_POST['GLAmount']);
	unset($_POST['GLNarrative']);
	unset($_POST['GLCode']);
	unset($_POST['AccountName']);
}

/*set up the form whatever */
if (!isset($_POST['DatePaid'])) {
	$_POST['DatePaid'] = '';
}

if (isset($_POST['DatePaid']) AND ($_POST['DatePaid'] == '' OR !Is_Date($_SESSION['PaymentDetail' . $identifier]->DatePaid))) {

	$_POST['DatePaid'] = date($_SESSION['DefaultDateFormat']);
	$_SESSION['PaymentDetail' . $identifier]->DatePaid = $_POST['DatePaid'];
}

if ($_SESSION['PaymentDetail' . $identifier]->Currency == '' AND $_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
	$_SESSION['PaymentDetail' . $identifier]->Currency = $_SESSION['CompanyRecord']['currencydefault'];
}

if (isset($_POST['BankAccount']) AND $_POST['BankAccount'] != '') {
	$SQL = "SELECT bankaccountname
			FROM bankaccounts,
				chartmaster
			WHERE bankaccounts.accountcode= chartmaster.accountcode
			AND chartmaster.accountcode='" . $_POST['BankAccount'] . "'";

	$ErrMsg = __('The bank account name cannot be retrieved because');

	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 1) {
		$MyRow = DB_fetch_row($Result);
		$_SESSION['PaymentDetail' . $identifier]->BankAccountName = $MyRow[0];
		unset($Result);
	}
	elseif (DB_num_rows($Result) == 0) {
		prnMsg(__('The bank account number') . ' ' . $_POST['BankAccount'] . ' ' . __('is not set up as a bank account with a valid general ledger account') , 'error');
	}
}

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') , '?identifier=', urlencode($identifier) , '" method="post">
	<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
	
	<div class="db-card">
		<div class="db-card-header">
			<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg> ' . __('Payment Header') . '</h3>
		</div>
		<div class="db-card-body">
			<div class="db-form-grid">';

$SQL = "SELECT bankaccountname,
				bankaccounts.accountcode,
				bankaccounts.currcode
		FROM bankaccounts
		INNER JOIN chartmaster
			ON bankaccounts.accountcode=chartmaster.accountcode
		INNER JOIN bankaccountusers
			ON bankaccounts.accountcode=bankaccountusers.accountcode
		WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "'
		ORDER BY bankaccountname";

$ErrMsg = __('The bank accounts could not be retrieved because');
$AccountsResults = DB_query($SQL, $ErrMsg);

echo '<div class="db-form-group">
		<label for="BankAccount">', __('Bank Account') , '</label>
		<select autofocus="autofocus" name="BankAccount" onchange="ReloadForm(UpdateHeader)" required="required">';

if (DB_num_rows($AccountsResults) == 0) {
	echo '</select></div>';
	prnMsg(__('Bank Accounts have not yet been defined. You must first') . ' <a href="' . $RootPath . '/BankAccounts.php">' . __('define the bank accounts') . '</a> ' . __('and general ledger accounts to be affected') , 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($AccountsResults)) {
		$selected = (isset($_POST['BankAccount']) AND $_POST['BankAccount'] == $MyRow['accountcode']) ? 'selected="selected" ' : '';
		echo '<option ' . $selected . ' value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], ' - ', $MyRow['currcode'], '</option>';
	}
	echo '</select>';
	if (in_array($CashSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($CashSecurity)) {
		if (isset($_SESSION['PaymentDetail' . $identifier]->Account)) {
			echo '<span class="db-field-help" style="color: var(--success); font-weight: 600;">' . __('Current Balance') . ': ' . locale_number_format($CurrBalanceRow['balance'], $_SESSION['CompanyRecord']['decimalplaces']) . '</span>';
		}
	}
	echo '</div>';
}

echo '<div class="db-form-group">
		<label for="DatePaid">', __('Date Paid') , '</label>
		<input type="date" name="DatePaid" required="required" value="', FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid), '" />
	</div>';

// Currency of payment:
echo '<div class="db-form-group">
		<label for="Currency">', __('Currency') , '</label>';
$Result = DB_query("SELECT currabrev FROM currencies");
if (DB_num_rows($Result) == 0) {
	prnMsg(__('No currencies defined') , 'error');
	echo '</div>';
} else {
	include(__DIR__ . '/includes/CurrenciesArray.php');
	if ($_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
		echo '<select name="Currency" onchange="ReloadForm(UpdateHeader)" required="required">';
		while ($MyRow = DB_fetch_array($Result)) {
			$selected = ($_SESSION['PaymentDetail' . $identifier]->Currency == $MyRow['currabrev']) ? 'selected="selected" ' : '';
			echo '<option ' . $selected . ' value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], '</option>';
		}
		echo '</select>';
	} else {
		echo '<input name="Currency" type="hidden" value="', $_SESSION['PaymentDetail' . $identifier]->Currency, '" />';
		echo '<div class="val-bold" style="padding: 8px; background: var(--surface-alt); border: 1px solid var(--border); border-radius: 4px;">' . $CurrencyName[$_SESSION['PaymentDetail' . $identifier]->Currency] . '</div>';
		if (!isset($_POST['ExRate']) OR $_POST['ExRate'] == '') {
			$SQL = "SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['PaymentDetail' . $identifier]->Currency . "'";
			$CurrResult = DB_query($SQL);
			$MyRow = DB_fetch_row($CurrResult);
			$_POST['ExRate'] = locale_number_format($MyRow[0], 'Variable');
		}
	}
	echo '</div>';
}

if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency != $_SESSION['PaymentDetail' . $identifier]->Currency AND isset($_SESSION['PaymentDetail' . $identifier]->AccountCurrency)) {
	if (isset($SuggestedExRate) AND ($_POST['ExRate'] == 1 OR $_POST['Currency'] != $_POST['PreviousCurrency'] OR $_POST['PreviousBankAccount'] != $_SESSION['PaymentDetail' . $identifier]->Account)) {
		$_POST['ExRate'] = locale_number_format($SuggestedExRate, 'Variable');
	}

	$SuggestedExRateText = isset($SuggestedExRate) ? '1 ' . $_SESSION['PaymentDetail' . $identifier]->AccountCurrency . ' = ' . locale_number_format($SuggestedExRate, 'Variable') . ' ' . $_SESSION['PaymentDetail' . $identifier]->Currency : 'Rate unknown';
	echo '<div class="db-form-group">
			<label for="ExRate">', __('Ex Rate') , '</label>
			<input class="number" maxlength="12" name="ExRate" type="text" value="', $_POST['ExRate'], '" />
			<span class="db-field-help">' . __('Suggested') . ': ' . $SuggestedExRateText . '</span>
		</div>';
}

if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency != $_SESSION['CompanyRecord']['currencydefault'] AND isset($_SESSION['PaymentDetail' . $identifier]->AccountCurrency)) {
	if (isset($SuggestedFunctionalExRate) AND ($_POST['FunctionalExRate'] == 1 OR $_POST['Currency'] != $_POST['PreviousCurrency'] OR $_POST['PreviousBankAccount'] != $_SESSION['PaymentDetail' . $identifier]->Account)) {
		$_POST['FunctionalExRate'] = locale_number_format($SuggestedFunctionalExRate, 'Variable');
	}

	$SuggestedFuncText = isset($SuggestedFunctionalExRate) ? '1 ' . $_SESSION['CompanyRecord']['currencydefault'] . ' = ' . locale_number_format($SuggestedFunctionalExRate, 'Variable') . ' ' . $_SESSION['PaymentDetail' . $identifier]->AccountCurrency : 'Rate unknown';
	echo '<div class="db-form-group">
			<label for="FunctionalExRate">', __('Functional Ex Rate') , '</label>
			<input class="number" name="FunctionalExRate" required="required" type="text" value="', $_POST['FunctionalExRate'], '" />
			<span class="db-field-help">' . __('Suggested') . ': ' . $SuggestedFuncText . '</span>
		</div>';
}
echo '<div class="db-form-group">
		<label for="Paymenttype">' . __('Payment Type') . '</label>
		<select name="Paymenttype" required="required">';
include(__DIR__ . '/includes/GetPaymentMethods.php');
array_unshift($PaytTypes, '');
foreach ($PaytTypes as $PaytType) {
	$selected = (isset($_POST['Paymenttype']) AND $_POST['Paymenttype'] == $PaytType) ? 'selected="selected" ' : '';
	echo '<option ' . $selected . ' value="' . $PaytType . '">' . $PaytType . '</option>';
}
echo '</select></div>';

echo '<div class="db-form-group">
		<label for="ChequeNum">' . __('Cheque Number') . '</label>
		<input type="text" name="ChequeNum" value="' . $_POST['ChequeNum'] . '" ' . $ErrClass . ' placeholder="' . __('If applicable') . '" />
	</div>';

// Info to be inserted on `banktrans`.`ref` varchar(50):
echo '<div class="db-form-group">
		<label for="BankTransRef">', __('Bank Reference') , '</label>
		<input maxlength="50" name="BankTransRef" type="text" value="', stripslashes($_POST['BankTransRef']) , '" placeholder="' . __('Reference for bank records') . '" />
	</div>';

// Info to be inserted on `gltrans`.`narrative` varchar(200):
echo '<div class="db-form-group">
		<label for="Narrative">', __('GL Narrative') , '</label>
		<input maxlength="200" name="Narrative" type="text" value="', stripslashes($_POST['Narrative']) , '" placeholder="' . __('Narrative for ledger records') . '" />
	</div>';
echo '</div></div>'; // end db-form-grid, db-card-body

echo '<div class="db-card-footer" style="padding: var(--space-4); text-align: right; background: var(--surface-alt);">
		<input name="PreviousCurrency" type="hidden" value="', $_POST['Currency'], '" />
		<input type="hidden" name="PreviousBankAccount" value="' . $_SESSION['PaymentDetail' . $identifier]->Account . '" />
		<button name="UpdateHeader" type="submit" class="db-btn db-btn-primary">' . __('Update Header') . '</button>
	</div></div>'; // end footer, db-card

if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1 AND $_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header">
				<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> ' . __('General Ledger Analysis') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-grid">';

	echo '<div class="db-form-group">
			<label for="Tag">', __('Select Tag') , '</label>
			<select name="Tag[]" multiple="multiple" style="height: 100px;">';

	$SQL = "SELECT tagref,
				tagdescription
			FROM tags
			ORDER BY tagref";

	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['Tag']) and $_POST['Tag'] == $MyRow['tagref']) {
			echo '<option selected="selected" value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
		} //isset($_POST['Tag']) and $_POST['Tag'] == $MyRow['tagref']
		else {
			echo '<option value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
		}
	} //$MyRow = DB_fetch_array($Result)	}
	echo '</select></div>';
	// End select Tag
	/*now set up a GLCode field to select from avaialble GL accounts */
	echo '<div class="db-form-group">
			<label for="GLManualCode">' . __('GL Account Code') . '</label>
			<input type="text" name="GLManualCode" value="' . (isset($_POST['GLManualCode']) ? $_POST['GLManualCode'] : '') . '" onchange="return inArray(this, GLCode.options,\'' . __('Not found') . '\')" />
		</div>';

	echo '<div class="db-form-group">
			<label for="GLGroup">' . __('GL Group Filter') . '</label>
			<div style="display: flex; gap: 4px;">
				<select name="GLGroup" onchange="return ReloadForm(UpdateCodes)">';
	$SQL = "SELECT groupname FROM accountgroups ORDER BY sequenceintb";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		echo '<option value=""></option>';
		while ($MyRow = DB_fetch_array($Result)) {
			$selected = (isset($_POST['GLGroup']) AND $_POST['GLGroup'] == $MyRow['groupname']) ? 'selected="selected" ' : '';
			echo '<option ' . $selected . ' value="' . $MyRow['groupname'] . '">' . $MyRow['groupname'] . '</option>';
		}
	}
	echo '</select>
				<button type="submit" name="UpdateCodes" class="db-btn-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg></button>
			</div>
		</div>';

	if (isset($_POST['GLGroup']) AND $_POST['GLGroup'] != '') {
		$SQL = "SELECT chartmaster.accountcode,
						chartmaster.accountname
				FROM chartmaster
					INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1
				WHERE chartmaster.group_='" . $_POST['GLGroup'] . "'
				ORDER BY chartmaster.accountcode";
	}
	else {
		$SQL = "SELECT chartmaster.accountcode,
						chartmaster.accountname
				FROM chartmaster
					INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1
				ORDER BY chartmaster.accountcode";
	}

	echo '<div class="db-form-group">
			<label for="GLCode">' . __('GL Account Selection') . '</label>
			<select name="GLCode" onchange="return assignComboToInput(this,' . 'GLManualCode' . ')">';
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		echo '<option value=""></option>';
		while ($MyRow = DB_fetch_array($Result)) {
			$selected = (isset($_POST['GLCode']) AND $_POST['GLCode'] == $MyRow['accountcode']) ? 'selected="selected" ' : '';
			echo '<option ' . $selected . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
		}
	}
	echo '</select></div>';

	echo '<div class="db-form-group">
			<label for="Cheque">' . __('Voucher/Cheque Number') . '</label>
			<input type="text" name="Cheque" maxlength="12" />
		</div>';

	echo '<div class="db-form-group">
			<label for="GLNarrative">' . __('Line Narrative') . '</label>
			<input maxlength="200" name="GLNarrative" type="text" value="' . (isset($_POST['GLNarrative']) ? stripslashes($_POST['GLNarrative']) : '') . '" />
		</div>';

	echo '<div class="db-form-group">
			<label for="GLAmount">' . __('Amount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
			<input type="text" required="required" name="GLAmount" class="number" value="' . (isset($_POST['GLAmount']) ? $_POST['GLAmount'] : '0') . '" />
		</div>';

	echo '</div></div>'; // end db-form-grid, db-card-body
	echo '<div class="db-card-footer" style="padding: var(--space-4); text-align: right; background: var(--surface-alt);">
			<button type="submit" name="Process" class="db-btn db-btn-primary">' . __('Add to Analysis') . '</button>
		</div></div>';

	if (sizeOf($_SESSION['PaymentDetail' . $identifier]->GLItems) > 0) {
		echo '<div class="db-card" style="margin-top: var(--space-6);">
				<div class="db-card-header">
					<h3 class="db-card-title">' . __('Current Analysis Items') . '</h3>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Voucher') . '</th>
								<th class="number">' . __('Amount') . '</th>
								<th>' . __('Account') . '</th>
								<th>' . __('Narrative') . '</th>
								<th>' . __('Tags') . '</th>
								<th class="noPrint"></th>
							</tr>
						</thead>
						<tbody>';

		$PaymentTotal = 0;
		foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {
			$TagDescriptions = GetDescriptionsFromTagArray($PaymentItem->Tag);
			echo '<tr class="striped_row">
					<td>' . $PaymentItem->Cheque . '</td>
					<td class="number val-bold">' . locale_number_format($PaymentItem->Amount, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
					<td><div class="val-bold">' . $PaymentItem->GLCode . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $PaymentItem->GLActName . '</div></td>
					<td>' . stripslashes($PaymentItem->Narrative) . '</td>
					<td>' . $TagDescriptions . '</td>
					<td class="noPrint"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?identifier=' . $identifier) . '&amp;Delete=' . $PaymentItem->ID . '" onclick="return confirm(\'' . __('Confirm delete?') . '\');" class="db-btn-icon" style="color:var(--danger);"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg></a></td>
				</tr>';
			$PaymentTotal += $PaymentItem->Amount;
		}
		echo '</tbody>
				<tfoot>
					<tr class="db-table-summary">
						<td>' . __('TOTAL') . '</td>
						<td class="number val-bold">' . locale_number_format($PaymentTotal, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
						<td colspan="4"></td>
					</tr>
				</tfoot>
			</table></div>
			<div class="db-card-footer" style="padding: var(--space-5); text-align: center; background: var(--surface-alt);">
				<button type="submit" name="CommitBatch" class="db-btn db-btn-primary" style="padding: var(--space-3) var(--space-8);">' . __('Accept and Process Payment') . '</button>
			</div></div>';
	}

} else {
	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Outstanding Invoices to Pay') . '</h3>
				<div style="font-size:0.875rem; color:var(--text-muted);">' . __('Supplier') . ': <span class="val-bold">' . $_SESSION['PaymentDetail' . $identifier]->SuppName . '</span></div>
			</div>';

	$SQL = "SELECT systypes.typename,
				supptrans.id,
				supptrans.transno,
				supptrans.suppreference,
				supptrans.trandate,
				supptrans.balance + supptrans.diffonexch AS amount
			FROM supptrans
			INNER JOIN systypes
				ON systypes.typeid=supptrans.type
			WHERE settled=0 AND (systypes.typeid=20 OR systypes.typeid=21 OR (systypes.typeid=22 AND (supptrans.balance + supptrans.diffonexch)>0))
				AND supplierno='" . $_SESSION['PaymentDetail' . $identifier]->SupplierID . "'
				AND (supptrans.balance + supptrans.diffonexch)<>0
			ORDER BY supptrans.trandate,
				supptrans.transno";
	$Result = DB_query($SQL);

	echo '<div class="db-table-wrapper">
			<table class="db-table">
			<thead>
				<tr>
					<th>' . __('Date') . '</th>
					<th>' . __('Type') . '</th>
					<th>' . __('Reference') . '</th>
					<th class="number">' . __('Balance') . '</th>
					<th style="text-align: center;">' . __('Pay?') . '</th>
					<th class="number">' . __('Amount to Pay') . '</th>
				</tr>
			</thead>
			<tbody>';
	$ids = '';
	$i = 0;
	while ($MyRow = DB_fetch_array($Result)) {
		$ids .= $i > 0 ? ';' . $MyRow['id'] : $MyRow['id'];
		if (!isset($_POST['paid' . $MyRow['id']])) {
			$_POST['paid' . $MyRow['id']] = 0;
		}
		echo '<tr class="striped_row">
					<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
					<td>' . $MyRow['typename'] . '</td>
					<td><div class="val-bold">' . $MyRow['transno'] . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $MyRow['suppreference'] . '</div></td>
					<td class="number">' . locale_number_format($MyRow['amount'], $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
					<td style="text-align: center;">
						<label class="db-checkbox">
							<input onclick="AddAmount(this,' . $MyRow['id'] . ');" type="checkbox" name="check' . $MyRow['id'] . '" value="' . $MyRow['amount'] . '" />
							<span>' . __('Pay') . '</span>
						</label>
					</td>
					<td class="number">
						<input type="text" class="number" style="width: 120px;" id="' . $MyRow['id'] . '" name="paid' . $MyRow['id'] . '" value="' . $_POST['paid' . $MyRow['id']] . '" />
						<input type="hidden" name="remainamt' . $MyRow['id'] . '" value="' . $MyRow['amount'] . '" />
					</td>
				</tr>';
		$i++;
	}
	echo '</tbody></table></div>';
	
	echo '<div class="db-card-footer" style="padding: var(--space-4); background: var(--surface-alt); border-top: 1px solid var(--border);">
			<div style="display: flex; justify-content: flex-end; align-items: center; gap: var(--space-4);">
				<div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600;">' . __('Total Allocation') . ': <input type="text" id="ttl" value="0" readonly style="width: 120px; text-align: right; border: none; background: transparent; font-weight: 700; color: var(--primary); font-size: 1rem;"></div>
				<button type="button" data-ids="' . $ids . '" class="db-btn db-btn-secondary" onclick="update1(\'' . $ids . '\')" id="update">' . __('Recalculate Total') . '</button>
			</div>
		</div></div>';

	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header">
				<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg> ' . __('Payment & Discount Summary') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-grid">';

	echo '<div class="db-form-group">
			<label for="gltrans_narrative">', __('Supplier Narrative') , '</label>
			<input maxlength="200" name="gltrans_narrative" type="text" value="', stripslashes($_POST['gltrans_narrative']) , '" />
		</div>';
	
	echo '<div class="db-form-group">
			<label for="supptrans_suppreference">', __('Supplier Reference') , '</label>
			<input maxlength="20" name="supptrans_suppreference" type="text" value="', stripslashes($_POST['supptrans_suppreference']) , '" />
		</div>';

	echo '<div class="db-form-group">
			<label for="supptrans_transtext">', __('Transaction Comments') , '</label>
			<input maxlength="200" name="supptrans_transtext" type="text" value="', stripslashes($_POST['supptrans_transtext']) , '" />
		</div>';

	echo '<div class="db-form-group">
			<label for="Amount">', __('Payment Amount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
			<input class="number val-bold" id="Amount" name="Amount" type="text" value="', $_SESSION['PaymentDetail' . $identifier]->Amount, '" style="color: var(--primary); font-size: 1.125rem;" />
		</div>';

	echo '<div class="db-form-group">
			<label for="Discount">', __('Discount Amount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
			<input class="number" name="Discount" type="text" value="', $_SESSION['PaymentDetail' . $identifier]->Discount, '" />
			<input name="SuppName" type="hidden" value="', $_SESSION['PaymentDetail' . $identifier]->SuppName, '" />
		</div>';

	echo '</div></div>'; // end grid, body
	echo '<div class="db-card-footer" style="padding: var(--space-5); text-align: center; background: var(--surface-alt);">
			<button type="submit" name="CommitBatch" onClick="payVerify(\'Amount\',\'ttl\')" class="db-btn db-btn-primary" style="padding: var(--space-3) var(--space-8);">' . __('Accept and Process Payment') . '</button>
		</div></div>';
}
echo '</div></form>'; // end db-page, form

include(__DIR__ . '/includes/footer.php');
