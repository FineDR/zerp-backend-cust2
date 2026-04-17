<?php

/* Entry of both customer receipts against accounts receivable and also general ledger or nominal receipts */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineReceiptClass.php');

require(__DIR__ . '/includes/session.php');

if (isset($_POST['DateBanked'])) {
	$_POST['DateBanked'] = ConvertSQLDate($_POST['DateBanked']);
}

include(__DIR__ . '/includes/GetPaymentMethods.php');

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-row">
				<div class="db-header-main">
					<h1 class="db-page-title">' . $Title . '</h1>
					<p class="db-page-subtitle">' . (isset($_GET['Type']) && $_GET['Type']=='GL' ? __('Process general ledger receipt entries') : __('Process customer payment receipts')) . '</p>
				</div>
				<div class="db-header-actions">';
if (isset($_SESSION['ReceiptBatch' . $identifier]) && count($_SESSION['ReceiptBatch' . $identifier]->Items) > 0) {
	echo '			<span class="db-badge db-badge-success" style="padding: 8px 16px; font-weight: 800;">' . count($_SESSION['ReceiptBatch' . $identifier]->Items) . ' ' . __('Items in Batch') . '</span>';
}
echo '				</div>
			</div>
		</div>';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

$Msg='';

if (isset($_GET['NewReceipt'])){
	unset($_SESSION['ReceiptBatch' . $identifier]->Items);
	unset($_SESSION['ReceiptBatch' . $identifier]);
	unset($_SESSION['CustomerRecord' . $identifier]);
}

if (isset($_POST['Cancel'])) {
	$Cancel=1;
}

if (isset($_GET['Type']) AND $_GET['Type']=='GL') {
	$_POST['GLEntry']=1;
}

if ((isset($_POST['BatchInput'])
	AND $_POST['BankAccount']=='')
	OR (isset($_POST['Process'])
	AND $_POST['BankAccount']=='')) {

	echo '<br />';
	prnMsg(__('A bank account must be selected for this receipt'), 'warn');
	$BankAccountEmpty=true;
} elseif (isset($_GET['NewReceipt'])) {
	$BankAccountEmpty=true;
} else {
	$BankAccountEmpty=false;
}

$Errors = array();

if (!isset($_GET['Delete']) AND isset($_SESSION['ReceiptBatch' . $identifier])){
	//always process a header update unless deleting an item

	$_SESSION['ReceiptBatch' . $identifier]->Account = $_POST['BankAccount'];
	/*Get the bank account currency and set that too */

	$SQL = "SELECT bankaccountname,
					currcode,
					decimalplaces
			FROM bankaccounts
			INNER JOIN currencies
			ON bankaccounts.currcode=currencies.currabrev
			WHERE accountcode='" . $_POST['BankAccount']."'";

	$ErrMsg =__('The bank account name cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result)==1){
		$MyRow = DB_fetch_array($Result);
		$_SESSION['ReceiptBatch' . $identifier]->BankAccountName = $MyRow['bankaccountname'];
		$_SESSION['ReceiptBatch' . $identifier]->AccountCurrency=$MyRow['currcode'];
		$_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces=$MyRow['decimalplaces'];
		unset($Result);
	} elseif (DB_num_rows($Result)==0 AND !$BankAccountEmpty){
		prnMsg( __('The bank account number') . ' ' . $_POST['BankAccount'] . ' ' . __('is not set up as a bank account'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	if (!Is_Date($_POST['DateBanked'])){
		$_POST['DateBanked'] = date($_SESSION['DefaultDateFormat']);
	}
	$_SESSION['ReceiptBatch' . $identifier]->DateBanked = $_POST['DateBanked'];
	if (isset($_POST['ExRate']) AND $_POST['ExRate']!= ''){
		if (is_numeric(filter_number_format($_POST['ExRate']))){
			$_SESSION['ReceiptBatch' . $identifier]->ExRate = filter_number_format($_POST['ExRate']);
		} else {
			prnMsg(__('The exchange rate entered should be numeric'),'warn');
		}
	}
	if (isset($_POST['FunctionalExRate']) AND $_POST['FunctionalExRate']!= ''){
		if (is_numeric(filter_number_format($_POST['FunctionalExRate']))){
			$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate=filter_number_format($_POST['FunctionalExRate']); //ex rate between receipt currency and account currency
		} else {
			prnMsg(__('The functional exchange rate entered should be numeric'),'warn');
		}
	}
	if (!isset($_POST['ReceiptType'])) {
		$_POST['ReceiptType'] = '';
	}
	$_SESSION['ReceiptBatch' . $identifier]->ReceiptType = $_POST['ReceiptType'];

	if (!isset($_POST['Currency'])){
		$_POST['Currency']=$_SESSION['CompanyRecord']['currencydefault'];
	}

	if ($_SESSION['ReceiptBatch' . $identifier]->Currency!= $_POST['Currency']){

		$_SESSION['ReceiptBatch' . $identifier]->Currency=$_POST['Currency']; //receipt currency
		/*Now customer receipts entered using the previous currency need to be ditched
		and a warning message displayed if there were some customer receipted entered */
		if (count($_SESSION['ReceiptBatch' . $identifier]->Items)>0){
			unset($_SESSION['ReceiptBatch' . $identifier]->Items);
			prnMsg(__('Changing the currency of the receipt means that existing entries need to be re-done - only customers trading in the selected currency can be selected'),'warn');
		}

	}

	if ($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency==$_SESSION['CompanyRecord']['currencydefault']){
		$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate = 1;
		$SuggestedFunctionalExRate =1;
	} elseif (!$BankAccountEmpty) {
		/*To illustrate the rates required
			Take an example functional currency NZD receipt in USD from an AUD bank account
			1 NZD = 0.80 USD
			1 NZD = 0.90 AUD
			The FunctionalExRate = 0.90 - the rate between the functional currency and the bank account currency
			The receipt ex rate is the rate at which one can sell the received currency and purchase the bank account currency
			or 0.8/0.9 = 0.88889
		*/

		/*Get suggested FunctionalExRate between the bank account currency and the home (functional) currency */
		$Result = DB_query("SELECT rate, decimalplaces FROM currencies WHERE currabrev='" . $_SESSION['ReceiptBatch' . $identifier]->AccountCurrency . "'");
		$MyRow = DB_fetch_array($Result);
		$SuggestedFunctionalExRate = $MyRow['rate'];
		$_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces = $MyRow['decimalplaces'];

	} //end else account currency !=  functional currency

	if ($_POST['Currency']==$_SESSION['ReceiptBatch' . $identifier]->AccountCurrency){
		$_SESSION['ReceiptBatch' . $identifier]->ExRate = 1; //ex rate between receipt currency and account currency
		$SuggestedExRate=1;
	} elseif (isset($_POST['Currency'])) {
		/*Get the exchange rate between the functional currency and the receipt currency*/
		$Result = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'");
		$MyRow = DB_fetch_array($Result);
		$TableExRate = $MyRow['rate']; //this is the rate of exchange between the functional currency and the receipt currency
		/*Calculate cross rate to suggest appropriate exchange rate between receipt currency and account currency */
		$SuggestedExRate = $TableExRate/$SuggestedFunctionalExRate;
	}

	$_SESSION['ReceiptBatch' . $identifier]->BankTransRef = $_POST['BankTransRef'];
	$_SESSION['ReceiptBatch' . $identifier]->Narrative = $_POST['BatchNarrative'];

} elseif (isset($_GET['Delete'])) {
	/* User hit delete the receipt entry from the batch */
	$_SESSION['ReceiptBatch' . $identifier]->remove_receipt_item($_GET['Delete']);
} else { //it must be a new receipt batch
	$_SESSION['ReceiptBatch' . $identifier] = new Receipt_Batch;
}


if (isset($_POST['Process'])){ //user hit submit a new entry to the receipt batch

	if (!isset($_POST['GLCode'])) {
		$_POST['GLCode']='';
	}
	if (!isset($_POST['tag'])) {
		$_POST['tag']='';
	}
	if (!isset($_POST['CustomerID'])) {
		$_POST['CustomerID']='';
	}
	if (!isset($_POST['CustomerName'])) {
		$_POST['CustomerName']='';
	}
	if ($_POST['Discount']==0 AND $ReceiptTypes[$_SESSION['ReceiptBatch' . $identifier]->ReceiptType]['percentdiscount']>0){
		if (isset($_GET['Type']) AND $_GET['Type'] == 'Customer') {
			$_POST['Discount'] = $_POST['Amount']*$ReceiptTypes[$_SESSION['ReceiptBatch' . $identifier]->ReceiptType]['percentdiscount'];
		}
	}

	if ($_POST['GLCode'] == '' AND $_GET['Type']=='GL') {
		prnMsg( __('No General Ledger code has been chosen') . ' - ' . __('so this GL analysis item could not be added'),'warn');

	} else {
		$AllowThisPosting = true;
 		if ($_SESSION['ProhibitJournalsToControlAccounts'] == 1) {
 			if ($_SESSION['CompanyRecord']['gllink_debtors'] == '1' AND $_POST['GLCode'] == $_SESSION['CompanyRecord']['debtorsact']) {
 				prnMsg(__('Payments involving the debtors control account cannot be entered. The general ledger debtors ledger (AR) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration'), 'warn');
 				$AllowThisPosting = false;
 			}
 			if ($_SESSION['CompanyRecord']['gllink_creditors'] == '1' AND
				($_POST['GLCode'] == $_SESSION['CompanyRecord']['creditorsact'] OR $_POST['GLCode'] == $_SESSION['CompanyRecord']['grnact'])) {
 				prnMsg(__('Payments involving the creditors control account or the GRN suspense account cannot be entered. The general ledger creditors ledger (AP) integration is enabled so control accounts are automatically maintained. This setting can be disabled in System Configuration'), 'warn');
 				$AllowThisPosting = false;
 			}
 			if ($_POST['GLCode'] == $_SESSION['CompanyRecord']['retainedearnings']) {
 				prnMsg(__('Payments involving the retained earnings control account cannot be entered. This account is automtically maintained.'), 'warn');
 				$AllowThisPosting = false;
 			}
 		}
 		if ($AllowThisPosting) {
 			$_SESSION['ReceiptBatch' . $identifier]->add_to_batch(filter_number_format($_POST['Amount']),
													$_POST['CustomerID'],
													filter_number_format($_POST['Discount']),
													$_POST['Narrative'],
													$_POST['GLCode'],
													$_POST['PayeeBankDetail'],
													$_POST['CustomerName'],
													$_POST['tag']);
			/*Make sure the same receipt is not double processed by a page refresh */
			$Cancel = 1;
		}
	}
}

if (isset($Cancel)){
	unset($_SESSION['CustomerRecord' . $identifier]);
	unset($_POST['CustomerID']);
	unset($_POST['CustomerName']);
	unset($_POST['Amount']);
	unset($_POST['Discount']);
	unset($_POST['Narrative']);
	unset($_POST['PayeeBankDetail']);
}


if (isset($_POST['CommitBatch'])){

 /* once all receipts items entered, process all the data in the
  session cookie into the DB creating a single banktrans for the whole amount
  of all receipts in the batch and DebtorTrans records for each receipt item
  all DebtorTrans will refer to a single banktrans. A GL entry is created for
  each GL receipt entry and one for the debtors entry and one for the bank
  account debit

  NB allocations against debtor receipts are a separate exercice

  first off run through the array of receipt items $_SESSION['ReceiptBatch']->Items and
  if GL integrated then create GL Entries for the GL Receipt items
  and add up the non-GL ones for posting to debtors later,
  also add the total discount total receipts*/

	$PeriodNo = GetPeriod($_SESSION['ReceiptBatch' . $identifier]->DateBanked);

	if ($_SESSION['CompanyRecord']==0){
		prnMsg(__('The company has not yet been set up properly') . ' - ' . __('this information is needed to process the batch') . '. ' . __('Processing has been cancelled'),'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	/*Make an array of the defined bank accounts */
	$SQL = "SELECT accountcode FROM bankaccounts";
	$Result = DB_query($SQL);
	$BankAccounts = array();
	$i=0;
	while ($Act = DB_fetch_row($Result)){
		$BankAccounts[$i]= $Act[0];
		$i++;
	}

	/*Start a transaction to do the whole lot inside */
	DB_Txn_Begin();
	$_SESSION['ReceiptBatch' . $identifier]->BatchNo = GetNextTransNo(12);


	$BatchReceiptsTotal = 0; //in functional currency
	$BatchDiscount = 0; //in functional currency
	$BatchDebtorTotal = 0; //in functional currency
	$CustomerReceiptCounter=1; //Count lines of customer receipts in this batch

	echo '<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Review and commit the gathered receipts') . '</p>
			</div>
		</div>';

	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2v20M2 12h20"></path></svg>
					' . __('Summary of Receipt Batch') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Batch No') . '</th>
								<th>' . __('Date Banked') . '</th>
								<th>' . __('Customer Name') . '</th>
								<th>' . __('GL Code') . '</th>
								<th class="number">' . __('Amount') . '</th>
								<th>' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	foreach ($_SESSION['ReceiptBatch' . $identifier]->Items as $ReceiptItem) {

		$SQL = "SELECT accountname FROM chartmaster WHERE accountcode='" . $ReceiptItem->GLCode . "'";
		$Result = DB_query($SQL);
		$MyRow=DB_fetch_array($Result);

		echo '<tr class="striped_row">
			<td>' . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . '</td>
			<td>' . $_SESSION['ReceiptBatch' . $identifier]->DateBanked . '</td>
			<td>' . $ReceiptItem->CustomerName . '</td>
			<td class="text">' . $ReceiptItem->GLCode . ' - ' . ($MyRow['accountname'] ?? '') . '</td>
			<td class="number">' . locale_number_format($ReceiptItem->Amount/$_SESSION['ReceiptBatch' . $identifier]->ExRate/$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate,$_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces)  . '</td>';

		if ($ReceiptItem->GLCode ==''){
			echo '<td class="noPrint"><a target="_blank" href="', $RootPath, '/PDFReceipt.php?BatchNumber=', $_SESSION['ReceiptBatch' . $identifier]->BatchNo, '&ReceiptNumber=', $CustomerReceiptCounter, '">', __('Print a Customer Receipt'), '</a></td></tr>';
			$CustomerReceiptCounter += 1;
		}

		if ($ReceiptItem->GLCode != ''){ //so its a GL receipt
			if ($_SESSION['CompanyRecord']['gllink_debtors']==1){ /* then enter a GLTrans record */
				 $SQL = "INSERT INTO gltrans (type,
								 			typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
					VALUES (
						12,
						'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
						'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
						'" . $PeriodNo . "',
						'" . $ReceiptItem->GLCode . "',
						'" . mb_substr($ReceiptItem->Narrative, 0, 200) . "',
						'" . -($ReceiptItem->Amount/$_SESSION['ReceiptBatch' . $identifier]->ExRate/$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate) . "'
					)";
				$ErrMsg = __('Cannot insert a GL entry for the receipt because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				InsertGLTags($ReceiptItem->tag);
			}

			/*check to see if this is a GL posting to another bank account (or the same one)
			if it is then a matching payment needs to be created for this account too */

			if (in_array($ReceiptItem->GLCode, $BankAccounts)) {

			/*Need to deal with the case where the payment from one bank account could be to a bank account in another currency */

				/*Get the currency and rate of the bank account transferring to*/
				$SQL = "SELECT currcode, rate
							FROM bankaccounts INNER JOIN currencies
							ON bankaccounts.currcode = currencies.currabrev
							WHERE accountcode='" . $ReceiptItem->GLCode."'";
				$TrfFromAccountResult = DB_query($SQL);
				$TrfFromBankRow = DB_fetch_array($TrfFromAccountResult) ;
				$TrfFromBankCurrCode = $TrfFromBankRow['currcode'];
				$TrfFromBankExRate = $TrfFromBankRow['rate'];

				if ($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency == $TrfFromBankCurrCode){
					/*Make sure to use the same rate if the transfer is between two bank accounts in the same currency */
					$TrfFromBankExRate = $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate;
				}

				/*Consider an example - had to be currencies I am familar with sorry so I could figure it out!!
					 functional currency NZD
					 bank account in AUD - 1 NZD = 0.90 AUD (FunctionalExRate)
					 receiving USD - 1 AUD = 0.85 USD  (ExRate)
					 from a bank account in EUR - 1 NZD = 0.52 EUR

					 oh yeah - now we are getting tricky!
					 Lets say we received USD 100 to the AUD bank account from the EUR bank account

					 To get the ExRate for the bank account we are transferring money from
					 we need to use the cross rate between the NZD-AUD/NZD-EUR
					 and apply this to the

					 the receipt record will read
					 exrate = 0.85 (1 AUD = USD 0.85)
					 amount = 100 (USD)
					 functionalexrate = 0.90 (1 NZD = AUD 0.90)

					 the payment record will read

					 amount 100 (USD)
					 exrate    (1 EUR =  (0.85 x 0.90)/0.52 USD  ~ 1.47
					  					(ExRate x FunctionalExRate) / USD Functional ExRate
					 Check this is 1 EUR = 1.47 USD
					 functionalexrate =  (1NZD = EUR 0.52)

				*/

				$PaymentTransNo = GetNextTransNo( 1 );
				$SQL="INSERT INTO banktrans (transno,
											type,
											bankact,
											ref,
											exrate,
											functionalexrate,
											transdate,
											banktranstype,
											amount,
											currcode)
						VALUES (
							'" . $PaymentTransNo . "',
							1,
							'" . $ReceiptItem->GLCode . "',
							'" . __('Act Transfer') ." - " . $ReceiptItem->Narrative . "',
							'" . (($_SESSION['ReceiptBatch' . $identifier]->ExRate * $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate)/$TrfFromBankExRate). "',
							'" . $TrfFromBankExRate . "',
							'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
							'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $identifier]->ReceiptType]['paymentname'] . "',
							'" . -$ReceiptItem->Amount . "',
							'" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'
						)";

				$ErrMsg = __('Cannot insert a bank transaction using the SQL');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			} //end if an item is a transfer between bank accounts

		} else { //its not a GL item - its a customer receipt then
			/*Accumulate the total debtors credit including discount */
			$BatchDebtorTotal += (($ReceiptItem->Discount + $ReceiptItem->Amount)/$_SESSION['ReceiptBatch' . $identifier]->ExRate/$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate);
			/*Create a DebtorTrans entry for each customer deposit */

			/*The rate of exchange required here is the rate between the functional (home) currency and the customer receipt currency
			 * We have the exchange rate between the bank account and the functional home currency  $_SESSION['ReceiptBatch']->ExRate
			 * and the exchange rate betwen the currency being paid and the bank account */

			$SQL = "INSERT INTO debtortrans (transno,
											type,
											debtorno,
											branchcode,
											order_,
											trandate,
											inputdate,
											prd,
											reference,
											tpe,
											rate,
											ovamount,
											ovdiscount,
											invtext,
											salesperson)
					VALUES (
						'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
						12,
						'" . $ReceiptItem->Customer . "',
						'',
						'" . $ReceiptItem->ID . "',
						'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
						'" . date('Y-m-d H-i-s') . "',
						'" . $PeriodNo . "',
						'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $identifier]->ReceiptType]['paymentname']  . ' ' . $ReceiptItem->PayeeBankDetail . "',
						'',
						'" . ($_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate*$_SESSION['ReceiptBatch' . $identifier]->ExRate) . "',
						'" . -$ReceiptItem->Amount . "',
						'" . -$ReceiptItem->Discount . "',
						'" . $ReceiptItem->Narrative. "',
						'" . $_SESSION['SalesmanLogin']. "'
					)";
			$ErrMsg = __('Cannot insert a receipt transaction against the customer because') ;
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "UPDATE debtorsmaster
						SET lastpaiddate = '" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
						lastpaid='" . $ReceiptItem->Amount ."'
					WHERE debtorsmaster.debtorno='" . $ReceiptItem->Customer . "'";

			$ErrMsg = __('Cannot update the customer record for the date of the last payment received because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		} //end of if its a customer receipt
		$BatchDiscount += ($ReceiptItem->Discount/$_SESSION['ReceiptBatch' . $identifier]->ExRate/$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate);
		$BatchReceiptsTotal += ($ReceiptItem->Amount/$_SESSION['ReceiptBatch' . $identifier]->ExRate/$_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate);

	} /*end foreach $ReceiptItem */
	echo '</tbody></table>';

	/*now enter the BankTrans entry */

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
		VALUES (
			12,
			'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
			'" . $_SESSION['ReceiptBatch' . $identifier]->Account . "',
			'" . $_SESSION['ReceiptBatch' . $identifier]->BankTransRef . "',
			'" . $_SESSION['ReceiptBatch' . $identifier]->ExRate . "',
			'" . $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate . "',
			'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
			'" . $ReceiptTypes[$_SESSION['ReceiptBatch' . $identifier]->ReceiptType]['paymentname'] . "',
			'" . ($BatchReceiptsTotal * $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate * $_SESSION['ReceiptBatch' . $identifier]->ExRate) . "',
			'" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'
		)";
	$ErrMsg = __('Cannot insert a bank transaction');
	$Result = DB_query($SQL, $ErrMsg, '', true);


	if ($_SESSION['CompanyRecord']['gllink_debtors']==1){ /* then enter GLTrans records for discount, bank and debtors */

		if ($BatchReceiptsTotal!= 0){
			/* Bank account entry first */
			$SQL="INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
				VALUES (
					12,
					'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
					'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
					'" . $PeriodNo . "',
					'" . $_SESSION['ReceiptBatch' . $identifier]->Account . "',
					'" . mb_substr($_SESSION['ReceiptBatch' . $identifier]->Narrative, 0, 200) . "',
					'" . $BatchReceiptsTotal . "'
				)";
			$ErrMsg = __('Cannot insert a GL transaction for the bank account debit');
			$Result = DB_query($SQL, $ErrMsg, '', true);


		}
		if ($BatchDebtorTotal!= 0){
			/* Now Credit Debtors account with receipts + discounts */
			$SQL="INSERT INTO gltrans ( type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES (
							12,
							'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
							'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
							'" . $PeriodNo . "',
							'". $_SESSION['CompanyRecord']['debtorsact'] . "',
							'" . mb_substr($_SESSION['ReceiptBatch' . $identifier]->Narrative, 0, 200) . "',
							'" . -$BatchDebtorTotal . "'
							)";
			$ErrMsg = __('Cannot insert a GL transaction for the debtors account credit');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		} //end if there are some customer deposits in this batch

		if ($BatchDiscount!= 0){
			/* Now Debit Discount account with discounts allowed*/
			$SQL="INSERT INTO gltrans ( type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES (
								12,
								'" . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . "',
								'" . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . "',
								'" . $PeriodNo . "',
								'" . $_SESSION['CompanyRecord']['pytdiscountact'] . "',
								'" . mb_substr($_SESSION['ReceiptBatch' . $identifier]->Narrative, 0, 200) . "',
								'" . $BatchDiscount . "'
							)";
			$ErrMsg = __('Cannot insert a GL transaction for the payment discount debit');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} //end if there is some discount

	} //end if there is GL work to be done - ie config is to link to GL
	EnsureGLEntriesBalance(12,$_SESSION['ReceiptBatch' . $identifier]->BatchNo);

	$ErrMsg = __('Cannot commit the changes');
	DB_Txn_Commit();
	prnMsg( __('Receipt batch') . ' ' . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . ' ' . __('has been successfully entered into the database'),'success');

	echo '<div class="centre noPrint">',
		'<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/printer.png" title="' . __('Print') . '" alt="" />' . ' ' . '<a href="' . $RootPath . '/PDFBankingSummary.php?BatchNo=' . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . '">' . __('Print PDF Batch Summary') . '</a></p>';
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/allocation.png" title="' . __('Allocate') . '" alt="" />' . ' ' . '<a href="' . $RootPath . '/CustomerAllocations.php">' . __('Allocate Receipts') . '</a></p>';
	echo '<p class="page_title_text"><img alt="" src="', $RootPath, '/css/', $Theme, '/images/transactions.png" title="', __('Enter Receipts'), '" /> ', '<a href="', $RootPath, '/CustomerReceipt.php?NewReceipt=Yes&Type=', urlencode($_GET['Type']), '">', __('Enter Receipts'), '</a></p>',
		'</div>';

	unset($_SESSION['ReceiptBatch' . $identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();

} /* End of commit batch */

if (isset($_POST['Search'])){
/*Will only be true if clicked to search for a customer code */

	if ($_POST['Keywords'] AND $_POST['CustCode']) {
		$Msg=__('Customer name keywords have been used in preference to the customer code extract entered');
	}
	if ($_POST['Keywords']==''
		AND $_POST['CustCode']==''
		AND $_POST['CustInvNo']=='') {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'";
	} else {
		if (mb_strlen($_POST['Keywords'])>0) {
			//insert wildcard characters in spaces
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.name " . LIKE . " '". $SearchString . "'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'";

		} elseif (mb_strlen($_POST['CustCode'])>0){
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name
					FROM debtorsmaster
					WHERE debtorsmaster.debtorno " . LIKE . " '%" . $_POST['CustCode'] . "%'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'";
		} elseif (mb_strlen($_POST['CustInvNo'])>0){
			$SQL = "SELECT debtortrans.debtorno,
						debtorsmaster.name
					FROM debtorsmaster LEFT JOIN debtortrans
					ON debtorsmaster.debtorno=debtortrans.debtorno
					WHERE debtortrans.transno " . LIKE . " '%" . $_POST['CustInvNo'] . "%'
					AND debtorsmaster.currcode= '" . $_SESSION['ReceiptBatch' . $identifier]->Currency . "'";
		}
	}
		if ($_SESSION['SalesmanLogin'] !=  '') {
			$SQL .= " AND EXISTS (
						SELECT *
						FROM 	custbranch
						WHERE 	custbranch.debtorno = debtorsmaster.debtorno
							AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "')";
		}
		$ErrMsg = __('The searched customer records requested cannot be retrieved');
		$CustomerSearchResult = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($CustomerSearchResult)==1){
			$MyRow=DB_fetch_array($CustomerSearchResult);
			$Select = $MyRow['debtorno'];
			unset($CustomerSearchResult);
		} elseif (DB_num_rows($CustomerSearchResult)==0){
			prnMsg( __('No customer records contain the selected text') . ' - ' . __('please alter your search criteria and try again'),'info');
		}

	 //one of keywords or custcode was more than a zero length string
} //end of if search

if (isset($_POST['Select'])){
	$Select = $_POST['Select'];
}

if (isset($Select)) {
/*will only be true if a customer has just been selected by clicking on the customer or only one
customer record returned by the search - this record is then auto selected */

	$_POST['CustomerID']=$Select;
	/*need to get currency sales type - payment discount percent and GL code
	as well as payment terms and credit status and hold the lot as session variables
	the receipt held entirely as session variables until the button clicked to process*/


	if (isset($_SESSION['CustomerRecord' . $identifier])){
	   unset($_SESSION['CustomerRecord' . $identifier]);
	}

	$SQL = "SELECT debtorsmaster.name,
				debtorsmaster.pymtdiscount,
				debtorsmaster.currcode,
				currencies.currency,
				currencies.rate,
				currencies.decimalplaces AS currdecimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription,
				SUM(debtortrans.balance) AS balance,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0  THEN
					CASE WHEN (TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate)) >= paymentterms.daysbeforedue  THEN debtortrans.balance ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= 0 THEN debtortrans.balance ELSE 0 END
				END) AS due,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue	AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ") THEN debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight - debtortrans.ovdiscount - debtortrans.alloc ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays1'] . " THEN debtortrans.balance ELSE 0 END
				END) AS overdue1,
				SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(debtortrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ") THEN debtortrans.balance ELSE 0 END
				ELSE
					CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(debtortrans.trandate), paymentterms.dayinfollowingmonth)) >= " . $_SESSION['PastDueDays2'] . " THEN debtortrans.balance ELSE 0 END
				END) AS overdue2
			FROM debtorsmaster INNER JOIN paymentterms
			ON debtorsmaster.paymentterms = paymentterms.termsindicator
			INNER JOIN holdreasons
			ON debtorsmaster.holdreason = holdreasons.reasoncode
			INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
			INNER JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
			WHERE debtorsmaster.debtorno = '" . $_POST['CustomerID'] . "'";
	if ($_SESSION['SalesmanLogin'] !=  '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL .= " GROUP BY debtorsmaster.name,
				debtorsmaster.pymtdiscount,
				debtorsmaster.currcode,
				currencies.currency,
				currencies.rate,
				currencies.decimalplaces,
				paymentterms.terms,
				debtorsmaster.creditlimit,
				paymentterms.daysbeforedue,
				paymentterms.dayinfollowingmonth,
				debtorsmaster.creditlimit,
				holdreasons.dissallowinvoices,
				holdreasons.reasondescription";


	$ErrMsg = __('The customer details could not be retrieved because');
	$CustomerResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($CustomerResult)==0){

		/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

		$NIL_BALANCE = true;

		$SQL = "SELECT debtorsmaster.name,
						debtorsmaster.pymtdiscount,
						currencies.currency,
						currencies.rate,
						currencies.decimalplaces AS currdecimalplaces,
						paymentterms.terms,
						debtorsmaster.creditlimit,
						debtorsmaster.currcode,
						holdreasons.dissallowinvoices,
						holdreasons.reasondescription
					FROM debtorsmaster INNER JOIN paymentterms
					ON debtorsmaster.paymentterms = paymentterms.termsindicator
					INNER JOIN holdreasons
					ON debtorsmaster.holdreason = holdreasons.reasoncode
					INNER JOIN currencies
					ON debtorsmaster.currcode = currencies.currabrev
					WHERE debtorsmaster.debtorno = '" . $_POST['CustomerID'] . "'";

		$ErrMsg = __('The customer details could not be retrieved because');
		$CustomerResult = DB_query($SQL, $ErrMsg);

	} else {
		$NIL_BALANCE = false;
	}

	$_SESSION['CustomerRecord' . $identifier] = DB_fetch_array($CustomerResult);

	if ($NIL_BALANCE==true){
		$_SESSION['CustomerRecord' . $identifier]['balance']=0;
		$_SESSION['CustomerRecord' . $identifier]['due']=0;
		$_SESSION['CustomerRecord' . $identifier]['overdue1']=0;
		$_SESSION['CustomerRecord' . $identifier]['overdue2']=0;
	}
} /*end of if customer has just been selected  all info required read into $_SESSION['CustomerRecord']*/

/*set up the form whatever */


echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?Type=' . urlencode($_GET['Type']) . '&amp;identifier=' . urlencode($identifier) . '" method="post" id="form1">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-bottom-layout">
		<aside class="db-col-aside">';

/* 1. Batch Control (Always in Sidebar) */
$SQL = "SELECT bankaccountname,
				bankaccounts.accountcode,
				bankaccounts.currcode
		FROM bankaccounts
		INNER JOIN chartmaster
			ON bankaccounts.accountcode=chartmaster.accountcode
		INNER JOIN bankaccountusers
			ON bankaccounts.accountcode=bankaccountusers.accountcode
		WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] ."'
		ORDER BY bankaccountname";

$ErrMsg = __('The bank accounts could not be retrieved because');
$AccountsResults = DB_query($SQL, $ErrMsg);

	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
					' . __('Batch Control') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-field-group">
					<div class="db-field">
						<label class="db-label">' . __('Bank Account') . '</label>
						<select name="BankAccount" onchange="ReloadForm(form1.BatchInput)">
							<option value=""></option>';
	if (DB_num_rows($AccountsResults) == 0) {
		echo '			</select>
					</div>
				</div>';
		prnMsg(__('Bank Accounts have not yet been defined'), 'info');
	} else {
		while ($MyRow = DB_fetch_array($AccountsResults)) {
			echo '<option ' . ($_SESSION['ReceiptBatch' . $identifier]->Account == $MyRow['accountcode'] ? 'selected="selected"' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['bankaccountname'] . ' - ' . $MyRow['currcode'] . '</option>';
		}
		echo '			</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Date Banked') . '</label>
						<input type="date" name="DateBanked" required="required" value="' . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked) . '" />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Currency') . '</label>
						<select name="Currency" onchange="ReloadForm(form1.BatchInput)">';
		$SQL = "SELECT currency, currabrev FROM currencies";
		$Result = DB_query($SQL);
		include(__DIR__ . '/includes/CurrenciesArray.php');
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option ' . ($_SESSION['ReceiptBatch' . $identifier]->Currency == $MyRow['currabrev'] ? 'selected="selected"' : '') . ' value="' . $MyRow['currabrev'] . '">' . $CurrencyName[$MyRow['currabrev']] . '</option>';
		}
		echo '			</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Receipt Type') . '</label>
						<select name="ReceiptType" onchange="ReloadForm(form1.BatchInput)">';
		foreach ($ReceiptTypes as $RcptType) {
			echo '<option ' . (isset($_POST['ReceiptType']) && $_POST['ReceiptType'] == $RcptType['paymentid'] ? 'selected="selected"' : '') . ' value="' . $RcptType['paymentid'] . '">' . $RcptType['paymentname'] . '</option>';
		}
		echo '			</select>
					</div>
				</div>

				<div class="db-field-group" style="margin-top: var(--space-4);">
					<div class="db-field">
						<label class="db-label">' . __('Reference') . '</label>
						<input type="text" name="BankTransRef" maxlength="50" value="' . ($_SESSION['ReceiptBatch' . $identifier]->BankTransRef ?? '') . '" />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Batch Narrative') . '</label>
						<input type="text" name="BatchNarrative" maxlength="200" value="' . ($_SESSION['ReceiptBatch' . $identifier]->Narrative ?? '') . '" />
					</div>';

		if ($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency != $_SESSION['ReceiptBatch' . $identifier]->Currency AND isset($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency)) {
			echo '	<div class="db-field">
						<label class="db-label">' . __('Receipt Ex-Rate') . '</label>
						<input type="text" name="ExRate" class="number" step="any" value="' . locale_number_format($_SESSION['ReceiptBatch' . $identifier]->ExRate, 8) . '" />
						<div class="db-field-hint">' . ($SuggestedExRateText ?? '') . '</div>
					</div>';
		}

		if ($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency != $_SESSION['CompanyRecord']['currencydefault'] AND isset($_SESSION['ReceiptBatch' . $identifier]->AccountCurrency)) {
			echo '	<div class="db-field">
						<label class="db-label">' . __('Functional Ex-Rate') . '</label>
						<input type="text" name="FunctionalExRate" class="number" step="any" value="' . $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate . '" />
						<div class="db-field-hint">' . ($SuggestedFunctionalExRateText ?? '') . '</div>
					</div>';
		}

		echo '	</div>
				<div style="margin-top: var(--space-4); display: flex; justify-content: flex-end; gap: var(--space-3);">
					<input name="PreviousCurrency" type="hidden" value="' . ($_POST['Currency'] ?? $_SESSION['ReceiptBatch' . $identifier]->Currency) . '" />
					<button type="submit" name="BatchInput" class="db-btn db-btn-success" style="width: 100%;">' . __('Accept Batch Changes') . '</button>
				</div>
			</div>
		</div>';
	}

	/* 2. Selection or Entry Form (Also in Sidebar) */
	if (isset($_SESSION['ReceiptBatch' . $identifier]) AND ((isset($_POST['CustomerID']) && $_POST['CustomerID'] != '') || isset($_POST['GLEntry']))) {
		echo '<div class="card-v2" style="margin-top: var(--space-4);">
				<div class="card-header-v2">
					<h3 style="font-size: 0.95rem;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2v20M2 12h20"></path></svg>
						' . (isset($_POST['GLEntry']) ? __('GL Entry Details') : __('Receipt Details')) . '
					</h3>
				</div>
				<div class="db-card-body">
					<input type="hidden" name="CustomerID" value="' . ($_POST['CustomerID'] ?? '') . '" />
					<input type="hidden" name="CustomerName" value="' . ($_SESSION['CustomerRecord' . $identifier]['name'] ?? '') . '" />';

		if (isset($_POST['GLEntry'])) {
			echo '	<div class="db-field" style="margin-bottom: var(--space-3);">
						<label class="db-label">' . __('GL Account') . '</label>
						<select name="GLCode" style="font-size: 0.85rem;">';
			$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1 ORDER BY chartmaster.accountcode";
			$Result = DB_query($SQL);
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<option ' . (isset($_POST['GLCode']) && $_POST['GLCode'] == $MyRow['accountcode'] ? 'selected="selected"' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . $MyRow['accountname'] . '</option>';
			}
			echo '			</select>
					</div>';
		}

		echo '		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
						<div class="db-field">
							<label class="db-label">' . __('Amount') . '</label>
							<input type="text" name="Amount" class="number" required="required" style="padding: 4px 8px;" value="' . ($_POST['Amount'] ?? 0) . '" />
						</div>';
		if (!isset($_POST['GLEntry'])) {
			echo '		<div class="db-field">
							<label class="db-label">' . __('Discount') . '</label>
							<input type="text" name="Discount" class="number" style="padding: 4px 8px;" value="' . ($_POST['Discount'] ?? 0) . '" />
						</div>';
		}
		echo '		</div>
					<div class="db-field" style="margin-top: 8px;">
						<label class="db-label">' . __('Bank Details / Payee') . '</label>
						<input type="text" name="PayeeBankDetail" maxlength="22" style="padding: 4px 8px;" value="' . ($_POST['PayeeBankDetail'] ?? '') . '" />
					</div>
					<div class="db-field" style="margin-top: 8px;">
						<label class="db-label">' . __('Narrative') . '</label>
						<textarea name="Narrative" rows="2" style="padding: 4px 8px; font-size: 0.85rem;">' . ($_POST['Narrative'] ?? '') . '</textarea>
					</div>
					<div style="margin-top: var(--space-4); display: flex; gap: var(--space-2);">
						<button type="submit" name="Process" class="db-btn db-btn-success" style="flex: 2;">' . __('Add to Batch') . '</button>
						<button type="submit" name="Cancel" class="db-btn db-btn-secondary" style="flex: 1;">' . __('Cancel') . '</button>
					</div>
				</div>
			</div>';
	} elseif (isset($_SESSION['ReceiptBatch' . $identifier]) AND !isset($_POST['GLEntry'])) {
		echo '<div class="card-v2" style="margin-top: var(--space-4);">
				<div class="card-header-v2">
					<h3 style="font-size: 0.95rem;">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						' . __('Discovery') . '
					</h3>
					<div class="db-header-actions">
						<button type="submit" name="GLEntry" class="db-btn db-btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;">' . __('GL Mode') . '</button>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-field" style="margin-bottom: 8px;">
						<label class="db-label">' . __('Name Extract') . '</label>
						<input type="text" name="Keywords" placeholder="' . __('e.g. Acme') . '" style="padding: 4px 8px;" />
					</div>
					<div class="db-field" style="margin-bottom: 8px;">
						<label class="db-label">' . __('OR Code') . '</label>
						<input type="text" name="CustCode" style="padding: 4px 8px;" />
					</div>
					<div class="db-field">
						<label class="db-label">' . __('OR Invoice #') . '</label>
						<input type="text" name="CustInvNo" class="integer" style="padding: 4px 8px;" />
					</div>
					<button type="submit" name="Search" class="db-btn db-btn-primary" style="width: 100%; margin-top: var(--space-4);">' . __('Search Customers') . '</button>
				</div>
			</div>';
	}

	echo '	</aside>
			<main class="db-col-main">';

	/* 3. Main Data Area (Basket & KPIs) */

	if (isset($_SESSION['CustomerRecord' . $identifier])) {
		echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
						' . $_SESSION['CustomerRecord' . $identifier]['name'] . '
					</h3>
					<div class="db-header-actions">';
		if ($_SESSION['CustomerRecord' . $identifier]['dissallowinvoices'] != 0) {
			echo '		<span class="db-badge db-badge-danger">' . __('ACCOUNT ON HOLD') . '</span>';
		}
		echo '			<span class="db-badge db-badge-info">' . $_SESSION['CustomerRecord' . $identifier]['currency'] . '</span>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Total Balance') . '</th>
									<th>' . __('Current') . '</th>
									<th>' . __('Now Due') . '</th>
									<th>' . $_SESSION['PastDueDays1'] . '-' . $_SESSION['PastDueDays2'] . ' Days</th>
									<th> > ' . $_SESSION['PastDueDays2'] . ' Days</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td style="font-weight: 700;">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['balance'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</td>
									<td>' . locale_number_format(($_SESSION['CustomerRecord' . $identifier]['balance'] - $_SESSION['CustomerRecord' . $identifier]['due']), $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</td>
									<td style="color: var(--primary); font-weight: 600;">' . locale_number_format(($_SESSION['CustomerRecord' . $identifier]['due'] - $_SESSION['CustomerRecord' . $identifier]['overdue1']), $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</td>
									<td>' . locale_number_format(($_SESSION['CustomerRecord' . $identifier]['overdue1'] - $_SESSION['CustomerRecord' . $identifier]['overdue2']), $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</td>
									<td class="text-danger" style="font-weight: 700;">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['overdue2'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>';
	}


	if (isset($_SESSION['ReceiptBatch' . $identifier]) AND count($_SESSION['ReceiptBatch' . $identifier]->Items) > 0) {
		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2v20M2 12h20"></path></svg>
						' . __('Entries in Current Batch') . '
					</h3>
					<div class="db-header-actions">
						<button type="submit" name="CommitBatch" class="db-btn db-btn-success" style="padding: 6px 14px; font-weight: 700;">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><polyline points="20 6 9 17 4 12"></polyline></svg>
							' . __('Process Entire Batch') . '
						</button>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Received') . '</th>
									<th>' . ($_GET['Type'] == 'Customer' ? __('Customer') : __('GL Code')) . '</th>
									<th>' . __('Narrative') . '</th>
									<th class="number">' . __('Actions') . '</th>
								</tr>
							</thead>
							<tbody>';
		$BatchTotal = 0;
		foreach ($_SESSION['ReceiptBatch' . $identifier]->Items as $ReceiptItem) {
			echo '<tr>
					<td style="font-weight: 600;">' . locale_number_format($ReceiptItem->Amount, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . '</td>
					<td>';
			if ($_GET['Type'] == 'Customer') {
				echo '<div style="font-weight: 700;">' . stripslashes($ReceiptItem->CustomerName) . '</div>';
				echo '<div style="font-size: 0.75rem; color: var(--text-muted);">' . $ReceiptItem->Customer . '</div>';
			} else {
				$SQL = "SELECT accountname FROM chartmaster WHERE accountcode='" . $ReceiptItem->GLCode . "'";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_array($Result);
				echo '<div style="font-weight: 700;">' . $ReceiptItem->GLCode . '</div>';
				echo '<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow['accountname'] . '</div>';
			}
			echo '	</td>
					<td>' . stripslashes($ReceiptItem->Narrative) . '</td>
					<td class="number">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . urlencode($ReceiptItem->ID) . '&Type=' . urlencode($_GET['Type']) . '&identifier=' . urlencode($identifier) . '" class="db-btn db-btn-danger" style="padding: 6px; min-width: auto;" onclick="return confirm(\'' . __('Remove this item from the batch?') . '\');">
							<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</a>
					</td>
				</tr>';
			$BatchTotal += $ReceiptItem->Amount;
		}
		echo '		</tbody>
					<tfoot>
						<tr style="background: var(--surface-alt);">
							<td colspan="4" class="number" style="font-weight: 800; font-size: 1.25rem; color: var(--primary); padding: var(--space-4);">' . __('Batch Total') . ': ' . locale_number_format($BatchTotal, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . ' ' . $_SESSION['ReceiptBatch' . $identifier]->Currency . '</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>';
	}

	if (isset($CustomerSearchResult)) {
		echo '<div class="card-v2" style="margin-top: var(--space-6);">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
						' . __('Discovery Results') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th style="width: 150px;">' . __('Action') . '</th>
									<th>' . __('Code') . '</th>
									<th>' . __('Customer Name') . '</th>
								</tr>
							</thead>
							<tbody>';
		while ($MyRow = DB_fetch_array($CustomerSearchResult)) {
			echo '<tr>
					<td>
						<button type="submit" name="Select" value="' . $MyRow['debtorno'] . '" class="db-btn db-btn-secondary" style="padding: 6px 12px; font-size: 0.85rem; width: 100%;">
							' . __('Select Customer') . '
						</button>
					</td>
					<td style="font-weight: 700;">' . $MyRow['debtorno'] . '</td>
					<td>' . $MyRow['name'] . '</td>
				</tr>';
		}
		echo '		</tbody>
					</table>
				</div>
			</div>
		</div>';
	}

	echo '	</main>
		</div>'; // Close db-bottom-layout
	echo '</form>';
	echo '</div>'; // Close db-page


include(__DIR__ . '/includes/footer.php');
