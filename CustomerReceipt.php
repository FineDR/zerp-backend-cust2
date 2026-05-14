<?php

/* Entry of both customer receipts against accounts receivable and also general ledger or nominal receipts */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineReceiptClass.php');

require(__DIR__ . '/includes/session.php');

if (isset($_POST['DateBanked'])) {
	$_POST['DateBanked'] = ConvertSQLDate($_POST['DateBanked']);
}

include(__DIR__ . '/includes/GetPaymentMethods.php');

if (!isset($_POST['Keywords'])) $_POST['Keywords'] = '';
if (!isset($_POST['CustCode'])) $_POST['CustCode'] = '';
if (!isset($_POST['CustInvNo'])) $_POST['CustInvNo'] = '';
if (!isset($_POST['CustomerID'])) $_POST['CustomerID'] = '';
if (!isset($_POST['CustomerName'])) $_POST['CustomerName'] = '';

if (!isset($_GET['Type']) && isset($_POST['Type'])) {
    $_GET['Type'] = $_POST['Type'];
}
if (!isset($_GET['Type'])) {
    $_GET['Type'] = 'Customer';
}

include(__DIR__ . '/includes/header.php');

$Title = (isset($_GET['Type']) && $_GET['Type']=='GL' ? __('Process GL Receipt') : __('Customer Payment Entry'));

echo '<style>
	.rcpt-tabs-nav {
		display: flex;
		gap: 4px;
		background: #f1f5f9;
		padding: 6px;
		border-radius: 12px;
		margin-bottom: 24px;
		border: 1px solid #e2e8f0;
	}
	.rcpt-tab-btn {
		flex: 1;
		padding: 12px 16px;
		border: none;
		background: transparent;
		color: #64748b;
		font-weight: 700;
		font-size: 0.85rem;
		border-radius: 8px;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		transition: 0.2s;
	}
	.rcpt-tab-btn.active {
		background: #ffffff;
		color: var(--primary);
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
	}
	.rcpt-tab-content { display: none; }
	.rcpt-tab-content.active { display: block; }
	
	.rcpt-summary-bar {
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: var(--primary-dark);
		color: white;
		padding: 16px 24px;
		border-radius: 12px;
		margin-bottom: 24px;
	}
	.rcpt-stat-item { display: flex; flex-direction: column; }
	.rcpt-stat-label { font-size: 0.65rem; text-transform: uppercase; opacity: 0.7; font-weight: 800; letter-spacing: 0.05em; }
	.rcpt-stat-value { font-size: 1.1rem; font-weight: 900; }

    /* Stepper UI */
    .rcpt-stepper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        position: relative;
        padding: 0 40px;
    }
    .rcpt-stepper::before {
        content: "";
        position: absolute;
        top: 24px;
        left: 80px;
        right: 80px;
        height: 2px;
        background: #e2e8f0;
        z-index: 1;
    }
    .rcpt-step-item {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        cursor: not-allowed;
        opacity: 0.5;
        transition: 0.3s;
    }
    .rcpt-step-item.active, .rcpt-step-item.completed {
        opacity: 1;
        cursor: pointer;
    }
    .rcpt-step-circle {
        width: 48px;
        height: 48px;
        background: white;
        border: 2px solid #cbd5e1;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        color: #64748b;
        transition: 0.3s;
    }
    .rcpt-step-item.active .rcpt-step-circle {
        border-color: var(--primary);
        color: var(--primary);
        box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
    }
    .rcpt-step-item.completed .rcpt-step-circle {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    .rcpt-step-label {
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .rcpt-step-item.active .rcpt-step-label { color: var(--primary); }
</style>';

echo '<script>
function rcptShowStep(stepId) {
	const steps = ["tab-header", "tab-entry", "tab-batch"];
	const currentIdx = steps.indexOf(stepId);
	
	const contents = document.querySelectorAll(".rcpt-tab-content");
	if (contents.length > 0) {
		contents.forEach(el => el.classList.remove("active"));
		const target = document.getElementById(stepId);
		if (target) target.classList.add("active");
	}
	
	document.querySelectorAll(".rcpt-step-item").forEach(el => el.classList.remove("active", "completed"));
	
	steps.forEach((id, idx) => {
		const el = document.querySelector(`.rcpt-step-item[data-step="${id}"]`);
		if (el) {
			if (idx < currentIdx) el.classList.add("completed");
			if (idx === currentIdx) el.classList.add("active");
		}
	});

	localStorage.setItem("rcpt_active_step", stepId);
}
window.addEventListener("load", function() {
	var saved = localStorage.getItem("rcpt_active_step") || "tab-header";
	rcptShowStep(saved);
});
function confirmProcess(btn) {
    if (confirm("' . addslashes(__('Are you sure you want to process this entire receipt batch?')) . '")) {
        var hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "CommitBatch";
        hidden.value = "1";
        btn.form.appendChild(hidden);
        
        btn.innerHTML = \'<i class="fas fa-spinner fa-spin"></i> \' + "' . addslashes(__('Processing...')) . '";
        // Use a slight timeout to disable so the submit event still has a trigger
        setTimeout(function() { btn.disabled = true; }, 50);
        return true;
    }
    return false;
}
</script>';

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (isset($_POST['BatchInput'])) {
    echo '<script>localStorage.setItem("rcpt_active_step", "tab-entry");</script>';
}
if (isset($_POST['Process']) || isset($_POST['Search']) || isset($_POST['Select'])) {
    echo '<script>localStorage.setItem("rcpt_active_step", "tab-entry");</script>';
}
if (isset($_POST['CommitBatch'])) {
    file_put_contents(__DIR__ . "/scratch/payment_debug.log", date("Y-m-d H:i:s") . " - COMMIT TRIGGERED\n", FILE_APPEND);
    echo '<script>localStorage.setItem("rcpt_active_step", "tab-header");</script>';
}

echo '<div class="db-page">
	<div class="db-page-header">
		<div class="db-header-row">
			<div class="db-header-main">
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Record incoming funds and allocate to invoices') . '</p>
			</div>
			<div class="db-header-actions">';

if (isset($_SESSION['ReceiptBatch' . $identifier]) && is_array($_SESSION['ReceiptBatch' . $identifier]->Items ?? null) && count($_SESSION['ReceiptBatch' . $identifier]->Items) > 0) {
	echo '			<span class="db-badge db-badge-success" style="padding: 8px 16px; font-weight: 800;">' . count($_SESSION['ReceiptBatch' . $identifier]->Items) . ' ' . __('Items in Batch') . '</span>';
}
echo '				</div>
			</div>
		</div>';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

$Msg='';

if (isset($_GET['NewReceipt'])){
	if (isset($_SESSION['ReceiptBatch' . $identifier])) {
		$_SESSION['ReceiptBatch' . $identifier]->Items = array();
	}
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

	if (isset($_POST['BankAccount']) && $_POST['BankAccount'] != '') {
		$_SESSION['ReceiptBatch' . $identifier]->Account = $_POST['BankAccount'];
	}

	if (isset($_SESSION['ReceiptBatch' . $identifier]->Account) && $_SESSION['ReceiptBatch' . $identifier]->Account != '') {
		/*Get the bank account currency and set that too */
		$SQL = "SELECT bankaccountname,
						currcode,
						decimalplaces
				FROM bankaccounts
				INNER JOIN currencies
				ON bankaccounts.currcode=currencies.currabrev
				WHERE accountcode='" . $_SESSION['ReceiptBatch' . $identifier]->Account . "'";

		$ErrMsg =__('The bank account name cannot be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($Result)==1){
			$MyRow = DB_fetch_array($Result);
			$_SESSION['ReceiptBatch' . $identifier]->BankAccountName = $MyRow['bankaccountname'];
			$_SESSION['ReceiptBatch' . $identifier]->AccountCurrency=$MyRow['currcode'];
			$_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces=$MyRow['decimalplaces'];
			unset($Result);
		} elseif (DB_num_rows($Result)==0 AND !$BankAccountEmpty){
			prnMsg( __('The bank account number') . ' ' . ($_POST['BankAccount'] ?? $_SESSION['ReceiptBatch' . $identifier]->Account) . ' ' . __('is not set up as a bank account'),'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	}

	if (isset($_POST['DateBanked']) && Is_Date($_POST['DateBanked'])){
		$_SESSION['ReceiptBatch' . $identifier]->DateBanked = $_POST['DateBanked'];
	}
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
	if (isset($_POST['ReceiptType']) && $_POST['ReceiptType'] != '') {
		$_SESSION['ReceiptBatch' . $identifier]->ReceiptType = $_POST['ReceiptType'];
	}
	if (isset($_POST['Currency']) && $_POST['Currency'] != '') {
		$_SESSION['ReceiptBatch' . $identifier]->Currency = $_POST['Currency'];
	}
	if (isset($_POST['BatchNarrative']) && $_POST['BatchNarrative'] != '') {
		$_SESSION['ReceiptBatch' . $identifier]->Narrative = $_POST['BatchNarrative'];
	}

	if (!isset($_POST['Currency'])){
		$_POST['Currency']=$_SESSION['CompanyRecord']['currencydefault'];
	}

	if ($_SESSION['ReceiptBatch' . $identifier]->Currency!= $_POST['Currency']){

		$_SESSION['ReceiptBatch' . $identifier]->Currency=$_POST['Currency']; //receipt currency
		/*Now customer receipts entered using the previous currency need to be ditched
		and a warning message displayed if there were some customer receipted entered */
		if (is_array($_SESSION['ReceiptBatch' . $identifier]->Items ?? null) && count($_SESSION['ReceiptBatch' . $identifier]->Items)>0){
			$_SESSION['ReceiptBatch' . $identifier]->Items = array();
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
		$SuggestedExRate = $TableExRate/($SuggestedFunctionalExRate != 0 ? $SuggestedFunctionalExRate : 1);
	}

	$_SESSION['ReceiptBatch' . $identifier]->BankTransRef = $_POST['BankTransRef'] ?? '';
	$_SESSION['ReceiptBatch' . $identifier]->Narrative = $_POST['BatchNarrative'] ?? '';

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
	if (isset($_POST['Amount']) && $_POST['Amount'] != 0) {
        $discount = isset($_POST['Discount']) ? filter_number_format($_POST['Discount']) : 0;
        $batchReceiptType = $_SESSION['ReceiptBatch' . $identifier]->ReceiptType;
        
        if ($discount == 0 && isset($ReceiptTypes[$batchReceiptType]) && $ReceiptTypes[$batchReceiptType]['percentdiscount'] > 0){
            if (isset($_GET['Type']) AND $_GET['Type'] == 'Customer') {
                $discount = filter_number_format($_POST['Amount']) * $ReceiptTypes[$batchReceiptType]['percentdiscount'];
            }
        }
    } else {
        $discount = 0;
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
 			$_SESSION["ReceiptBatch" . $identifier]->add_to_batch(filter_number_format($_POST["Amount"]),
															$_POST["CustomerID"] ?? "",
															$discount,
															$_POST["Narrative"] ?? "",
															$_POST["GLCode"] ?? "",
															$_POST["PayeeBankDetail"] ?? "",
															$_POST["CustomerName"] ?? "",
															$_POST["tag"] ?? "");
			/*Make sure the same receipt is not double processed by a page refresh */
			$Cancel = 1;
		}
	}
}

	unset($_POST['PayeeBankDetail']);

$batchTotal = 0;
if (isset($_SESSION['ReceiptBatch' . $identifier]) && is_array($_SESSION['ReceiptBatch' . $identifier]->Items ?? null)) {
	foreach ($_SESSION['ReceiptBatch' . $identifier]->Items as $item) {
		$batchTotal += $item->Amount;
	}
}

echo '<div class="rcpt-summary-bar">
		<div class="rcpt-stat-item">
			<div class="rcpt-stat-label">' . __('Selected Bank') . '</div>
			<div class="rcpt-stat-value">' . ($_SESSION['ReceiptBatch' . $identifier]->BankAccountName ?? __('Not Selected')) . '</div>
		</div>
		<div style="display:flex; gap:40px;">
			<div class="rcpt-stat-item" style="text-align:right;">
				<div class="rcpt-stat-label">' . __('Batch Total') . '</div>
				<div class="rcpt-stat-value">' . ($_SESSION['ReceiptBatch' . $identifier]->Currency ?? '') . ' ' . locale_number_format($batchTotal, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces ?? 2) . '</div>
			</div>
		</div>
	</div>';

echo '<div class="rcpt-stepper">
		<div class="rcpt-step-item active" data-step="tab-header" onclick="rcptShowStep(\'tab-header\')">
			<div class="rcpt-step-circle"><i class="fas fa-university"></i></div>
			<div class="rcpt-step-label">' . __('1. Bank & Settings') . '</div>
		</div>
		<div class="rcpt-step-item" data-step="tab-entry" onclick="rcptShowStep(\'tab-entry\')">
			<div class="rcpt-step-circle"><i class="fas fa-user-plus"></i></div>
			<div class="rcpt-step-label">' . __('2. Entry Detail') . '</div>
		</div>
		<div class="rcpt-step-item" data-step="tab-batch" onclick="rcptShowStep(\'tab-batch\')">
			<div class="rcpt-step-circle"><i class="fas fa-list-check"></i></div>
			<div class="rcpt-step-label">' . __('3. Review Batch') . '</div>
		</div>
	</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?Type=' . urlencode($_GET['Type']) . '&amp;identifier=' . urlencode($identifier) . '" method="post" id="form1">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<input type="hidden" name="Type" value="' . htmlspecialchars($_GET['Type']) . '" />';

echo '<div id="tab-header" class="rcpt-tab-content active">
		<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-cog"></i> ' . __('Receipt Header Settings') . '</h3></div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3" style="gap:24px;">';

// Bank Selection
$SQL = "SELECT bankaccountname, bankaccounts.accountcode, bankaccounts.currcode FROM bankaccounts INNER JOIN chartmaster ON bankaccounts.accountcode=chartmaster.accountcode INNER JOIN bankaccountusers ON bankaccounts.accountcode=bankaccountusers.accountcode WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "' ORDER BY bankaccountname";
$AccountsResults = DB_query($SQL);

echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Target Bank Account') . '</label>
		<select class="db-form-select" name="BankAccount" onchange="this.form.submit()">';
echo '<option value=""></option>';
while ($MyRow = DB_fetch_array($AccountsResults)) {
	$selected = (isset($_SESSION['ReceiptBatch' . $identifier]->Account) && $_SESSION['ReceiptBatch' . $identifier]->Account == $MyRow['accountcode']) ? 'selected="selected" ' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['bankaccountname'] . ' - ' . $MyRow['currcode'] . '</option>';
}
echo '</select></div>';

echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Date Received') . '</label>
		<input class="db-form-input" type="date" name="DateBanked" value="' . FormatDateForSQL($_SESSION['ReceiptBatch' . $identifier]->DateBanked ?? date($_SESSION['DefaultDateFormat'])) . '" />
	</div>';

// Currency
echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Receipt Currency') . '</label>
		<select class="db-form-select" name="Currency" onchange="this.form.submit()">';
$SQL = "SELECT currabrev, currency FROM currencies";
$Result = DB_query($SQL);
while ($MyRow = DB_fetch_array($Result)) {
	$selected = ($_SESSION['ReceiptBatch' . $identifier]->Currency == $MyRow['currabrev']) ? 'selected="selected" ' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
}
echo '</select></div>';

// Payment Type
echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Payment Method') . '</label>
		<select class="db-form-select" name="ReceiptType">';
foreach ($ReceiptTypes as $type) {
	$selected = ($_SESSION['ReceiptBatch' . $identifier]->ReceiptType == $type['paymentid']) ? 'selected="selected" ' : '';
	echo '<option ' . $selected . ' value="' . $type['paymentid'] . '">' . $type['paymentname'] . '</option>';
}
echo '</select></div>';

echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Bank Reference') . '</label>
		<input class="db-form-input" type="text" name="BankTransRef" value="' . ($_SESSION['ReceiptBatch' . $identifier]->BankTransRef ?? '') . '" />
	</div>';

echo '</div>'; // End Grid

echo '<div class="db-form-group" style="margin-top:20px;">
		<label class="db-form-label">' . __('Batch Narrative / Reference') . '</label>
		<input class="db-form-input" type="text" name="BatchNarrative" value="' . ($_SESSION['ReceiptBatch' . $identifier]->Narrative ?? '') . '" placeholder="' . __('Internal batch description') . '" />
	</div>';

echo '</div><div class="db-card-footer" style="text-align:right;">
			<button type="submit" name="BatchInput" class="db-btn db-btn-primary">' . __('Save & Next Step') . ' <i class="fas fa-chevron-right" style="margin-left:8px;"></i></button>
		</div></div></div>';


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

    
    // Safety check for exchange rates to prevent DivisionByZero errors
    if (($_SESSION['ReceiptBatch' . $identifier]->ExRate ?? 0) == 0) $_SESSION['ReceiptBatch' . $identifier]->ExRate = 1;
    if (($_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate ?? 0) == 0) $_SESSION['ReceiptBatch' . $identifier]->FunctionalExRate = 1;

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
	
    echo '<div id="success-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 9999; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(8px); animation: fadeIn 0.3s ease-out;">
            <div class="db-card" style="width: 100%; max-width: 420px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); border: none; overflow: hidden; animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
                <div class="db-card-body" style="text-align: center; padding: 48px 32px 40px;">
                    <div style="width: 72px; height: 72px; background: #f0fdf4; color: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 24px; box-shadow: 0 0 0 8px #f0fdf4; border: 2px solid #bbf7d0;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 8px; letter-spacing: -0.025em;">' . __('Payment Received') . '</h2>
                    <p style="color: #64748b; font-size: 0.935rem; margin-bottom: 32px;">' . __('The transaction has been successfully committed to the general ledger.') . '</p>
                    
                    <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; padding: 20px; margin-bottom: 32px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: left;">
                        <div>
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 4px;">' . __('Reference') . '</div>
                            <div style="font-weight: 700; color: #1e293b; font-family: monospace; font-size: 1.1rem;">#' . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . '</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.05em; margin-bottom: 4px;">' . __('Total Amount') . '</div>
                            <div style="font-weight: 800; color: var(--primary); font-size: 1.1rem;">' . $_SESSION['ReceiptBatch' . $identifier]->Currency . ' ' . locale_number_format($batchTotal, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . '</div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <a href="' . $RootPath . '/PDFBankingSummary.php?BatchNo=' . $_SESSION['ReceiptBatch' . $identifier]->BatchNo . '" target="_blank" class="db-btn db-btn-primary" style="width: 100%; height: 52px; display: flex; align-items: center; justify-content: center; font-weight: 700; border-radius: 12px;">
                            <i class="fas fa-print" style="margin-right: 10px;"></i> ' . __('Print Receipt Summary') . '
                        </a>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                            <a href="' . $RootPath . '/CustomerReceipt.php?NewReceipt=Yes&Type=' . urlencode($_GET['Type']) . '" class="db-btn db-btn-secondary" style="height: 48px; border-radius: 12px; font-weight: 600;">
                                <i class="fas fa-plus" style="margin-right: 8px;"></i> ' . __('New') . '
                            </a>
                            <a href="' . $RootPath . '/CustomerAllocations.php" class="db-btn db-btn-secondary" style="height: 48px; border-radius: 12px; font-weight: 600;">
                                <i class="fas fa-random" style="margin-right: 8px;"></i> ' . __('Allocate') . '
                            </a>
                        </div>
                        <a href="' . $RootPath . '/index.php" style="margin-top: 16px; color: #94a3b8; font-size: 0.8rem; font-weight: 600; text-decoration: none; transition: 0.2s;" onmouseover="this.style.color=\'#64748b\'" onmouseout="this.style.color=\'#94a3b8\'">
                             ' . __('Back to Dashboard') . '
                        </a>
                    </div>
                </div>
            </div>
          </div>
          <style>
            @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
          </style>';

	unset($_SESSION['ReceiptBatch' . $identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();

} /* End of commit batch */

if (isset($_POST['Search'])){
/*Will only be true if clicked to search for a customer code */

	if ($_POST['Keywords'] AND $_POST['CustCode']) {
		$Msg=__('Customer name keywords have been used in preference to the customer code extract entered');
	}
	if (($_POST['Keywords'] ?? '') == ''
		AND ($_POST['CustCode'] ?? '') == ''
		AND ($_POST['CustInvNo'] ?? '') == '') {
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

	$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
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
			LEFT JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
			WHERE debtorsmaster.debtorno = '" . $_POST['CustomerID'] . "'";
	if ($_SESSION['SalesmanLogin'] !=  '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$SQL .= " GROUP BY debtorsmaster.debtorno,
				debtorsmaster.name,
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

		$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
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


echo '<div id="tab-entry" class="rcpt-tab-content">
		<div class="db-card">';

if (isset($_GET['Type']) && $_GET['Type'] == 'Customer') {
	echo '<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-user-check"></i> ' . __('Customer Selection') . '</h3></div>';
	echo '<div class="db-card-body">';
	
	// Customer Search
    echo '<p style="font-size:0.8rem; color:var(--text-muted); margin-bottom:12px; background:var(--blue-50); padding:8px 12px; border-radius:8px; border:1px solid var(--blue-100);">
            <i class="fas fa-info-circle" style="color:var(--blue-600); margin-right:6px;"></i> ' . __('Note: Only customers trading in') . ' <b>' . $_SESSION['ReceiptBatch' . $identifier]->Currency . '</b> ' . __('are searchable here.') . '
          </p>';
	echo '<div class="db-grid db-grid-4" style="gap:16px; margin-bottom:24px; padding:16px; background:var(--surface-alt); border-radius:12px;">
			<div class="db-form-group">
				<label class="db-form-label">' . __('Name Keywords') . '</label>
				<input class="db-form-input" type="text" name="Keywords" value="' . ($_POST['Keywords'] ?? '') . '" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Code Fragment') . '</label>
				<input class="db-form-input" type="text" name="CustCode" value="' . ($_POST['CustCode'] ?? '') . '" />
			</div>
			<div class="db-form-group">
				<label class="db-form-label">' . __('Invoice No') . '</label>
				<input class="db-form-input" type="text" name="CustInvNo" value="' . ($_POST['CustInvNo'] ?? '') . '" />
			</div>
			<div class="db-form-group" style="display:flex; align-items:flex-end;">
				<button type="submit" name="Search" class="db-btn db-btn-secondary" style="width:100%;">' . __('Search Customers') . '</button>
			</div>
		  </div>';

	if (isset($CustomerSearchResult)) {
		echo '<div class="db-table-wrapper" style="margin-bottom:24px;">
				<table class="db-table">
					<thead><tr><th>' . __('Code') . '</th><th>' . __('Customer Name') . '</th><th>' . __('Action') . '</th></tr></thead>
					<tbody>';
		while ($myrow = DB_fetch_array($CustomerSearchResult)) {
			echo '<tr><td>' . $myrow['debtorno'] . '</td><td>' . $myrow['name'] . '</td><td><button type="submit" name="Select" value="' . $myrow['debtorno'] . '" class="db-btn db-btn-sm db-btn-primary">' . __('Select') . '</button></td></tr>';
		}
		echo '</tbody></table></div>';
	}

if (isset($_SESSION['CustomerRecord' . $identifier])) {
    echo '<input type="hidden" name="CustomerID" value="' . $_SESSION['CustomerRecord' . $identifier]['debtorno'] . '" />';
    echo '<input type="hidden" name="CustomerName" value="' . $_SESSION['CustomerRecord' . $identifier]['name'] . '" />';
    
		echo '<div style="padding:20px; border:2px solid var(--primary-soft); border-radius:12px; margin-bottom:24px; background:white;">
				<div style="display:flex; justify-content:space-between; align-items:flex-start;">
					<div>
						<div style="font-size:1.2rem; font-weight:950; color:var(--primary-dark);">' . $_SESSION['CustomerRecord' . $identifier]['name'] . '</div>
						<div style="font-size:0.8rem; color:var(--text-muted);">' . $_POST['CustomerID'] . '</div>
					</div>
					<div style="text-align:right;">
						<div class="db-badge db-badge-info">' . __('Terms') . ': ' . $_SESSION['CustomerRecord' . $identifier]['terms'] . '</div>
					</div>
				</div>
				<div class="db-grid db-grid-4" style="margin-top:16px; gap:16px;">
					<div><label class="db-form-label">' . __('Balance') . '</label><div style="font-weight:800;">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['balance'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</div></div>
					<div><label class="db-form-label">' . __('Due Now') . '</label><div style="font-weight:800; color:var(--danger);">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['due'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</div></div>
					<div><label class="db-form-label">' . __('Overdue 1') . '</label><div style="font-weight:800; color:var(--danger);">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['overdue1'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</div></div>
					<div><label class="db-form-label">' . __('Overdue 2') . '</label><div style="font-weight:800; color:var(--danger);">' . locale_number_format($_SESSION['CustomerRecord' . $identifier]['overdue2'], $_SESSION['CustomerRecord' . $identifier]['currdecimalplaces']) . '</div></div>
				</div>
			  </div>';
	}
} else {
	// GL Entry Header
	echo '<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('General Ledger Analysis') . '</h3></div>';
	echo '<div class="db-card-body">';
}

// Receipt Details Row
echo '<div class="db-grid db-grid-3" style="gap:24px;">';

if (isset($_GET['Type']) && $_GET['Type'] == 'GL') {
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('GL Account') . '</label>
			<select class="db-form-select" name="GLCode">';
	$SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
	$Result = DB_query($SQL);
	while ($myrow = DB_fetch_array($Result)) {
		echo '<option value="' . $myrow['accountcode'] . '">' . $myrow['accountcode'] . ' - ' . $myrow['accountname'] . '</option>';
	}
	echo '</select></div>';
}

echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Amount Received') . ' (' . ($_SESSION['ReceiptBatch' . $identifier]->Currency ?? '') . ')</label>
		<input class="db-form-input number" type="text" name="Amount" style="font-size:1.15rem; font-weight:900; color:var(--primary);" />
	</div>';

if (isset($_GET['Type']) && $_GET['Type'] == 'Customer') {
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Settlement Discount') . '</label>
			<input class="db-form-input number" type="text" name="Discount" value="0" />
		</div>';
}

echo '<div class="db-form-group">
		<label class="db-form-label">' . __('Narrative') . '</label>
		<input class="db-form-input" type="text" name="Narrative" />
	</div>';

echo '</div>'; // End Grid

echo '</div><div class="db-card-footer" style="display:flex; justify-content:space-between; align-items:center;">
            <button type="button" class="db-btn db-btn-secondary" onclick="rcptShowStep(\'tab-header\')"><i class="fas fa-chevron-left" style="margin-right:8px;"></i> ' . __('Previous Step') . '</button>
			<div style="display:flex; gap:12px;">
                <button type="submit" name="Process" class="db-btn db-btn-primary"><i class="fas fa-plus" style="margin-right:8px;"></i> ' . __('Add to Batch') . '</button>
                <button type="button" class="db-btn db-btn-success" onclick="rcptShowStep(\'tab-batch\')">' . __('Review Batch') . ' <i class="fas fa-chevron-right" style="margin-left:8px;"></i></button>
            </div>
		</div></div></div>';

echo '<div id="tab-batch" class="rcpt-tab-content">
		<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Batch Review & Commitment') . '</h3>
			</div>
			<div class="db-card-body">';

if (isset($_SESSION['ReceiptBatch' . $identifier]) && is_array($_SESSION['ReceiptBatch' . $identifier]->Items ?? null) && count($_SESSION['ReceiptBatch' . $identifier]->Items) > 0) {
	echo '<div class="db-table-wrapper">
			<table class="db-table">
				<thead>
					<tr>
						<th>' . __('Target') . '</th>
						<th>' . __('Narrative') . '</th>
						<th class="text-right">' . __('Amount') . '</th>
						<th class="text-right">' . __('Discount') . '</th>
						<th class="text-center">' . __('Action') . '</th>
					</tr>
				</thead>
				<tbody>';
	foreach ($_SESSION['ReceiptBatch' . $identifier]->Items as $id => $item) {
		echo '<tr>
				<td style="font-weight:700;">' . ($item->CustomerName ?: $item->GLCode) . '</td>
				<td style="font-size:0.85rem;">' . $item->Narrative . '</td>
				<td class="text-right" style="font-weight:800;">' . locale_number_format($item->Amount, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . '</td>
				<td class="text-right">' . locale_number_format($item->Discount, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . '</td>
				<td class="text-center"><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?Delete=' . $id . '&Type=' . $_GET['Type'] . '&identifier=' . $identifier . '" class="db-btn-icon" style="color:var(--danger);"><i class="fas fa-trash"></i></a></td>
			</tr>';
	}
	echo '</tbody></table></div>';
	
	echo '<div style="margin-top:32px; padding:24px; background:var(--surface-alt); border-radius:12px; display:flex; justify-content:space-between; align-items:center;">
			<div>
				<div style="font-size:0.75rem; color:var(--text-muted); text-transform:uppercase; font-weight:800;">' . __('Total to be Deposited') . '</div>
				<div style="font-size:1.5rem; font-weight:950; color:var(--primary);">' . $_SESSION['ReceiptBatch' . $identifier]->Currency . ' ' . locale_number_format($batchTotal, $_SESSION['ReceiptBatch' . $identifier]->CurrDecimalPlaces) . '</div>
			</div>
			<button type="submit" name="CommitBatch" value="1" class="db-btn db-btn-primary" style="height:56px; padding:0 40px; font-size:1.1rem;">
				<i class="fas fa-check-double" style="margin-right:12px;"></i> ' . __('Finalize & Post Batch') . '
			</button>
		  </div>';
} else {
	echo '<div style="padding:40px; text-align:center; color:var(--text-muted);">' . __('No receipts added to this batch yet.') . '</div>';
}

echo '</div><div class="db-card-footer" style="text-align:left;">
            <button type="button" class="db-btn db-btn-secondary" onclick="rcptShowStep(\'tab-entry\')"><i class="fas fa-chevron-left" style="margin-right:8px;"></i> ' . __('Back to Entry') . '</button>
        </div></div></div>';

echo '</form></div>'; // Close form and db-page
include(__DIR__ . '/includes/footer.php');
?>
