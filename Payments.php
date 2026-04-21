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


// ===== ALL PHP PROCESSING IS DONE ABOVE =====
// ===== ALL HTML OUTPUT STARTS BELOW =====

// Determine the active tab label for allocation/analysis
$allocationTabLabel = $_SESSION['PaymentDetail' . $identifier]->SupplierID ? __('3. Allocation') : __('3. Analysis');

// --- OUTER PAGE & FORM OPEN ---
echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-money-check-alt"></i> ' . $PageTitleText . '
				</div>
				<div class="db-page-subtitle">' . (!empty($_SESSION['PaymentDetail' . $identifier]->SuppName) ? '<i class="fas fa-building" style="margin-right:6px;"></i>' . htmlspecialchars($_SESSION['PaymentDetail' . $identifier]->SuppName) : __('Bank account payment entry')) . '</div>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Payments.php?NewPayment=Yes" class="db-btn db-btn-secondary">
					<i class="fas fa-plus"></i> ' . __('New Payment') . '
				</a>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" id="PaymentForm">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// ===== TAB SWITCHING SCRIPT =====
echo '<style>
	#Header_SubBreadcrumb, .legacy-footer { display: none !important; }

	/* ---- Tab visibility ---- */
	.pay-tab-content { display: none; margin-top:20px; }
	.pay-tab-content.active { display: block; animation: db-fade-in 0.25s ease; }
	@keyframes db-fade-in { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

	/* ---- Architect Dashboard Overrides ---- */
	.db-card { 
		background: #ffffff; 
		border-radius: 16px !important; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-sm);
		overflow: hidden;
		margin-bottom: 24px;
	}
    .db-card-header { 
        background: #f9fafb; 
        border-bottom: 1px solid #f3f4f6; 
        padding: 16px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .db-card-title {
        font-size: 0.9rem; font-weight: 850; color: #064e3b; margin: 0;
        display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    .db-btn, .architect-btn, .db-btn-secondary, .db-btn-primary {
        border-radius: 8px !important;
        padding: 10px 20px;
        font-weight: 700;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        border: none;
        font-size: 0.85rem;
    }
    .db-btn-primary { background: #059669; color: #ffffff; }
    .db-btn-primary:hover { background: #065f46; }
    .db-btn-secondary { background: #f3f4f6; color: #4b5563; }
    .db-btn-secondary:hover { background: #e5e7eb; }

    /* ---- Form Standardization ---- */
    .db-form-group { margin-bottom: 24px; }
    .db-form-label {
        display: block;
        font-size: 0.72rem;
        text-transform: uppercase;
        font-weight: 900;
        letter-spacing: 0.08em;
        color: #065f46;
        margin-bottom: 8px;
    }
    .db-form-input, .db-form-select, .db-input {
        width: 100%;
        height: 48px;
        padding: 0 16px;
        border-radius: 8px !important;
        border: 1px solid #d1fae5;
        background: #ffffff;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    .db-form-input:focus, .db-form-select:focus {
        border-color: #059669;
        outline: none;
        box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
    }

	/* ---- Sidebar nav ---- */
	.db-nav-aside {
		display: flex;
		flex-direction: column;
		gap: 6px;
		padding: 4px 0;
	}
	.db-nav-item {
		display: flex;
		align-items: center;
		gap: 12px;
		width: 100%;
		padding: 14px 18px;
		border: none;
		background: transparent;
		color: #52635a;
		font-size: 0.88rem;
		font-weight: 750;
		border-radius: 8px !important;
		cursor: pointer;
		text-align: left;
		transition: all 0.2s ease;
		border: 1px solid transparent;
	}
	.db-nav-item i {
		width: 18px; text-align: center; font-size: 1rem; opacity: 0.5;
	}
	.db-nav-item.active {
		background: #059669;
		color: #ffffff;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.15);
	}
	.db-nav-item.active i { opacity: 1; }
	.db-nav-item:hover:not(.active) {
		background: #f0fdf4;
		color: #059669;
	}

	/* ---- Layout columns ---- */
	.db-col-aside {
		min-width: 280px;
		max-width: 300px;
		flex-shrink: 0;
		padding: 24px;
		background: #f9fafb;
		border-right: 1px solid #e5e7eb;
		overflow-y: auto;
	}
	.db-col-main {
		flex: 1;
		padding: 32px;
		overflow-y: auto;
		min-width: 0;
		background: #ffffff;
	}
	.db-bottom-layout {
		display: flex;
		height: calc(100vh - 120px);
		overflow: hidden;
	}

    /* ---- Responsive Logic ---- */
    @media (max-width: 1024px) {
        .db-col-aside { min-width: 250px; padding: 20px; }
        .db-col-main { padding: 24px; }
    }

    @media (max-width: 992px) {
        .db-bottom-layout { 
            flex-direction: column; 
            height: auto; 
            overflow: visible;
        }
        .db-col-aside { 
            max-width: 100% !important; 
            width: 100%; 
            border-right: none; 
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
            background: #ffffff;
            padding: 15px;
        }
        .db-col-main { 
            width: 100%; 
            overflow: visible;
            padding: 20px;
        }
        .db-nav-aside {
            flex-direction: row;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 5px;
            gap: 10px;
        }
        .db-nav-item {
            width: auto;
            padding: 10px 15px;
            font-size: 0.8rem;
        }
        .db-card[style*="margin-top: 16px"] { display: none; } /* Hide session summary in sidebar on mobile */
    }

    @media (max-width: 600px) {
        .db-page-header { flex-direction: column !important; align-items: flex-start !important; gap: 15px !important; }
        .db-header-actions { width: 100% !important; }
        .db-btn, .db-btn-secondary { width: 100% !important; justify-content: center !important; }
        .db-page-title { font-size: 1.1rem !important; }
    }
</style>
<script>
function payShowTab(tabId) {
	document.querySelectorAll(".pay-tab-content").forEach(function(el){ el.classList.remove("active"); });
	document.querySelectorAll(".db-nav-item").forEach(function(el){ el.classList.remove("active"); });
	var target = document.getElementById(tabId);
	if (target) target.classList.add("active");
	var btn = document.querySelector(".db-nav-item[data-tab=\"" + tabId + "\"]");
	if (btn) btn.classList.add("active");
	try { localStorage.setItem("payment_active_tab_v2", tabId); } catch(e) {}
}

function payVerify(amountId, totalId) {
    var amtInput = document.getElementById(amountId);
    var ttlInput = document.getElementById(totalId);
    if (!amtInput || !ttlInput) return true;

    var amt = parseFloat(amtInput.value.replace(/,/g, "")) || 0;
    var ttl = parseFloat(ttlInput.value.replace(/,/g, "")) || 0;

    if (ttl !== 0 && Math.abs(amt - ttl) > 0.01) {
        if (!confirm("' . __('The principal amount does not match the total allocation. Proceed anyway?') . '")) {
            if (window.event) window.event.preventDefault();
            return false;
        }
    }
    return true;
}

window.addEventListener("load", function() {
	var saved = "";
	try { saved = localStorage.getItem("payment_active_tab_v2") || ""; } catch(e) {}
	payShowTab(saved || "pay-tab-source");
});
</script>';

// ===== TWO-COLUMN LAYOUT =====
echo '<div class="db-bottom-layout">

	<!-- SIDEBAR NAVIGATOR -->
	<aside class="db-col-aside">';

// -- Sidebar: Status Card --
echo '	<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-tasks"></i> ' . __('Payment Phases') . '</div>
			</div>
			<div class="db-card-body" style="padding: 8px 0;">
				<nav class="db-nav-aside">
					<button type="button" class="db-nav-item" data-tab="pay-tab-source" onclick="payShowTab(\'pay-tab-source\')">
						<i class="fas fa-university"></i> ' . __('1. Source & Bank') . '
					</button>
					<button type="button" class="db-nav-item" data-tab="pay-tab-execution" onclick="payShowTab(\'pay-tab-execution\')">
						<i class="fas fa-file-invoice-dollar"></i> ' . __('2. Execution Details') . '
					</button>
					<button type="button" class="db-nav-item" data-tab="pay-tab-allocation" onclick="payShowTab(\'pay-tab-allocation\')">
						<i class="fas fa-tasks"></i> ' . $allocationTabLabel . '
					</button>
					<button type="button" class="db-nav-item" data-tab="pay-tab-finalize" onclick="payShowTab(\'pay-tab-finalize\')">
						<i class="fas fa-check-double"></i> ' . __('4. Review & Finalize') . '
					</button>
				</nav>
			</div>
		</div>';

// -- Sidebar: Session Summary Card --
echo '	<div class="db-card" style="margin-top: 16px;">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-wallet"></i> ' . __('Session Total') . '</div>
			</div>
			<div class="db-card-body" style="text-align: center; padding: 20px 16px;">
				<div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px;">' . __('Current Amount') . '</div>
				<div style="font-size: 1.75rem; font-weight: 900; color: var(--primary);">' . $_SESSION['PaymentDetail' . $identifier]->Currency . ' ' . locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</div>
				' . (($_SESSION['PaymentDetail' . $identifier]->SuppName) ? '<div style="margin-top:10px; font-size:0.8rem; color:var(--text-muted); font-weight:600;"><i class="fas fa-building" style="margin-right:4px;"></i>' . htmlspecialchars($_SESSION['PaymentDetail' . $identifier]->SuppName) . '</div>' : '') . '
				' . (($_SESSION['PaymentDetail' . $identifier]->DatePaid) ? '<div style="margin-top:6px; font-size:0.8rem; color:var(--text-muted);"><i class="fas fa-calendar" style="margin-right:4px;"></i>' . $_SESSION['PaymentDetail' . $identifier]->DatePaid . '</div>' : '') . '
			</div>
		</div>';

echo '	</aside>

	<!-- MAIN CONTENT AREA -->
	<main class="db-col-main">';

// ==========================================
// TAB 1: SOURCE & BANK SETTINGS
// ==========================================
echo '<div id="pay-tab-source" class="pay-tab-content">
	<div class="db-card">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-university"></i> ' . __('Bank & Header Settings') . '</div>
		</div>
		<div class="db-card-body">
			<div style="display: flex; flex-direction: column; gap: var(--space-5);">';



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

echo '<div class="db-alert db-alert-info" style="margin-bottom: var(--space-6); border-radius: var(--radius-lg); padding: var(--space-4); display: flex; align-items: center; gap: 12px;">
		<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="8"/></svg>
		<span>' . __('Use this screen to enter payments FROM your bank account. To enter a receipt from a supplier, use a negative payment amount.') . '</span>
	</div>';
;

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




$SQL = "SELECT bankaccountname, bankaccounts.accountcode, bankaccounts.currcode
		FROM bankaccounts
		INNER JOIN chartmaster ON bankaccounts.accountcode=chartmaster.accountcode
		INNER JOIN bankaccountusers ON bankaccounts.accountcode=bankaccountusers.accountcode
		WHERE bankaccountusers.userid = '" . $_SESSION['UserID'] . "'
		ORDER BY bankaccountname";

$ErrMsg = __('The bank accounts could not be retrieved because');
$AccountsResults = DB_query($SQL, $ErrMsg);

echo '<div class="db-form-group">
		<label class="db-form-label">', __('Bank Account') , '</label>
		<select class="db-form-select" autofocus="autofocus" name="BankAccount" onchange="ReloadForm(UpdateHeader)" required="required">';

if (DB_num_rows($AccountsResults) == 0) {
	echo '</select></div>';
	prnMsg(__('Bank Accounts have not yet been defined.') , 'warn');
} else {
	echo '<option value=""></option>';
	while ($MyRow = DB_fetch_array($AccountsResults)) {
		$selected = (isset($_POST['BankAccount']) AND $_POST['BankAccount'] == $MyRow['accountcode']) ? 'selected="selected" ' : '';
		echo '<option ' . $selected . ' value="', $MyRow['accountcode'], '">', $MyRow['bankaccountname'], ' - ', $MyRow['currcode'], '</option>';
	}
	echo '</select>';
	if ((in_array($CashSecurity, $_SESSION['AllowedPageSecurityTokens']) OR !isset($CashSecurity)) && isset($_SESSION['PaymentDetail' . $identifier]->Account)) {
		echo '<div style="margin-top: 8px; font-size: 0.8rem; color: var(--success); font-weight: 700;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' . __('Current Balance') . ': ' . locale_number_format($CurrBalanceRow['balance'], $_SESSION['CompanyRecord']['decimalplaces']) . '</div>';
	}
	echo '</div>';
}

echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
		<div class="db-form-group">
			<label class="db-form-label">', __('Date of Payment') , '</label>
			<input class="db-form-input" type="date" name="DatePaid" required="required" value="', FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid), '" />
		</div>
		<div class="db-form-group">
			<label class="db-form-label">', __('Payment Currency') , '</label>';

$Result = DB_query("SELECT currabrev FROM currencies");
if (DB_num_rows($Result) == 0) {
	prnMsg(__('No currencies defined') , 'error');
	echo '</div>';
} else {
	include(__DIR__ . '/includes/CurrenciesArray.php');
	if ($_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
		echo '<select class="db-form-select" name="Currency" onchange="ReloadForm(UpdateHeader)" required="required">';
		while ($MyRow = DB_fetch_array($Result)) {
			$selected = ($_SESSION['PaymentDetail' . $identifier]->Currency == $MyRow['currabrev']) ? 'selected="selected" ' : '';
			echo '<option ' . $selected . ' value="', $MyRow['currabrev'], '">', $CurrencyName[$MyRow['currabrev']], '</option>';
		}
		echo '</select>';
	} else {
		echo '<input name="Currency" type="hidden" value="', $_SESSION['PaymentDetail' . $identifier]->Currency, '" />';
		echo '<div style="padding: 10px; background: var(--surface-alt); border-radius: 8px; font-weight: 800; color: var(--primary); border: 1px solid var(--border-soft);">' . $CurrencyName[$_SESSION['PaymentDetail' . $identifier]->Currency] . '</div>';
	}
	echo '</div>';
}
echo '</div>'; // End inner grid

// Exchange Rates
if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency != $_SESSION['PaymentDetail' . $identifier]->Currency AND isset($_SESSION['PaymentDetail' . $identifier]->AccountCurrency)) {
	echo '<div class="db-form-group">
			<label class="db-form-label">', __('Exchange Rate (Bank vs Payment)') , '</label>
			<div style="display: flex; gap: var(--space-3); align-items: center;">
				<input class="db-form-input number" style="width: 140px;" name="ExRate" type="text" value="', $_POST['ExRate'], '" />
				<span style="font-size: 0.75rem; color: var(--text-muted); background: var(--surface-alt); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-soft);">' . __('Suggested') . ': ' . (isset($SuggestedExRate) ? locale_number_format($SuggestedExRate, 'Variable') : '1') . '</span>
			</div>
		</div>';
}

if ($_SESSION['PaymentDetail' . $identifier]->AccountCurrency != $_SESSION['CompanyRecord']['currencydefault'] AND isset($_SESSION['PaymentDetail' . $identifier]->AccountCurrency)) {
	echo '<div class="db-form-group">
			<label class="db-form-label">', __('Functional Rate (Functional vs Bank)') , '</label>
			<div style="display: flex; gap: var(--space-3); align-items: center;">
				<input class="db-form-input number" style="width: 140px;" name="FunctionalExRate" required="required" type="text" value="', $_POST['FunctionalExRate'], '" />
				<span style="font-size: 0.75rem; color: var(--text-muted); background: var(--surface-alt); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-soft);">' . __('Suggested') . ': ' . (isset($SuggestedFunctionalExRate) ? locale_number_format($SuggestedFunctionalExRate, 'Variable') : '1') . '</span>
			</div>
		</div>';
}

	echo '</div></div></div></div>'; // end inner-div, card-body, db-card, pay-tab-source

echo '<!-- TAB 2: EXECUTION & AUDIT -->
	<div id="pay-tab-execution" class="pay-tab-content">
		<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-file-invoice-dollar"></i> ' . __('Payment Execution Details') . '</div>
			</div>
			<div class="db-card-body">
				<div style="display: flex; flex-direction: column; gap: var(--space-5);">';

echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
		<div class="db-form-group">
			<label class="db-form-label">' . __('Payment Method') . '</label>
			<select class="db-form-select" name="Paymenttype" required="required">';
include(__DIR__ . '/includes/GetPaymentMethods.php');
array_unshift($PaytTypes, '');
foreach ($PaytTypes as $PaytType) {
	$selected = (isset($_POST['Paymenttype']) AND $_POST['Paymenttype'] == $PaytType) ? 'selected="selected" ' : '';
	echo '<option ' . $selected . ' value="' . $PaytType . '">' . $PaytType . '</option>';
}
echo '</select></div>
		<div class="db-form-group">
			<label class="db-form-label">' . __('Cheque/Ref Number') . '</label>
			<input class="db-form-input" type="text" name="ChequeNum" value="' . $_POST['ChequeNum'] . '" placeholder="' . __('e.g. 104523') . '" />
		</div>
	</div>';

echo '<div class="db-form-group">
		<label class="db-form-label">', __('Bank Statement Reference') , '</label>
		<input class="db-form-input" maxlength="50" name="BankTransRef" type="text" value="', stripslashes($_POST['BankTransRef']) , '" placeholder="' . __('Appears on bank reconcile') . '" />
	</div>';

echo '<div class="db-form-group">
		<label class="db-form-label">', __('General Ledger Narrative') , '</label>
		<input class="db-form-input" maxlength="200" name="Narrative" type="text" value="', stripslashes($_POST['Narrative']) , '" placeholder="' . __('Historical audit trail comment') . '" />
	</div>';

echo '<div style="margin-top: auto; display: flex; justify-content: flex-end; gap: 12px; padding-top: var(--space-4); border-top: 1px solid var(--border-soft);">
		<input name="PreviousCurrency" type="hidden" value="', $_POST['Currency'], '" />
		<input type="hidden" name="PreviousBankAccount" value="' . $_SESSION['PaymentDetail' . $identifier]->Account . '" />
		<button name="UpdateHeader" type="submit" class="db-btn db-btn-primary" style="height: 48px; padding: 0 32px; font-weight: 800;">
			<i class="fas fa-sync-alt" style="margin-right: 10px;"></i>
			' . __('Sync Header') . '
		</button>
	</div>
	</div></div></div></div>'; // end inner-div, card-body, db-card, pay-tab-execution



echo '<!-- TAB 3: ANALYSIS & ALLOCATION -->
	<div id="pay-tab-allocation" class="pay-tab-content">';

if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1 AND $_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-calculator"></i> ' . __('General Ledger Analysis') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-2" style="gap: var(--space-5);">
						<div class="db-form-group">
							<label class="db-form-label">', __('Select Tag') , '</label>
							<select class="db-form-select" name="Tag[]" multiple="multiple" style="height: 120px;">';
	$SQL = "SELECT tagref, tagdescription FROM tags ORDER BY tagref";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = (isset($_POST['Tag']) and $_POST['Tag'] == $MyRow['tagref']) ? 'selected="selected" ' : '';
		echo '<option ' . $selected . ' value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
	}
	echo '</select></div>';

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('GL Account Code') . '</label>
			<input class="db-form-input" type="text" name="GLManualCode" value="' . (isset($_POST['GLManualCode']) ? $_POST['GLManualCode'] : '') . '" onchange="return inArray(this, GLCode.options,\'' . __('Not found') . '\')" />
		</div>';

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Filter by GL Group') . '</label>
			<div style="display: flex; gap: 8px;">
				<select class="db-form-select" name="GLGroup" onchange="return ReloadForm(UpdateCodes)">';
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
				<button type="submit" name="UpdateCodes" class="db-btn db-btn-icon" style="flex: 0 0 44px; background: var(--surface-alt); border: 1px solid var(--border-soft);"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg></button>
			</div>
		</div>';

	$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1 " . (isset($_POST['GLGroup']) && $_POST['GLGroup'] != '' ? "WHERE chartmaster.group_='" . $_POST['GLGroup'] . "' " : "") . "ORDER BY chartmaster.accountcode";

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('GL Account Selection') . '</label>
			<select class="db-form-select" name="GLCode" onchange="return assignComboToInput(this,' . 'GLManualCode' . ')">';
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
			<label class="db-form-label">' . __('Voucher/Cheque Number') . '</label>
			<input class="db-form-input" type="text" name="Cheque" maxlength="12" placeholder="' . __('Voucher #') . '" />
		</div>';

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Local Amount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
			<input class="db-form-input number val-bold" type="text" required="required" name="GLAmount" value="' . (isset($_POST['GLAmount']) ? $_POST['GLAmount'] : '0') . '" style="color:var(--primary); font-size: 1.15rem;" />
		</div>';

	echo '</div>
		<div class="db-form-group" style="margin-top: var(--space-4);">
			<label class="db-form-label">' . __('Line Narrative') . '</label>
			<input class="db-form-input" maxlength="200" name="GLNarrative" type="text" value="' . (isset($_POST['GLNarrative']) ? stripslashes($_POST['GLNarrative']) : '') . '" placeholder="' . __('Notes for this line') . '" />
		</div>';


	echo '</div></div>
		<div class="db-card-footer">
			<button type="submit" name="Process" class="db-btn db-btn-primary" style="height: 44px; padding: 0 24px;">
				<i class="fas fa-plus-circle" style="margin-right: 8px;"></i>
				' . __('Analyze & Add Line') . '
			</button>
		</div>';

	if (sizeOf($_SESSION['PaymentDetail' . $identifier]->GLItems) > 0) {
		echo '<div class="db-card" style="margin-top: var(--space-6);">
				<div class="db-card-header">
					<div class="db-card-title" style="flex: 1;"><i class="fas fa-list-ul"></i> ' . __('Current Analysis Items') . '</div>
					<span class="db-badge" style="background: var(--primary-soft); color: var(--primary); font-weight: 700;">' . sizeOf($_SESSION['PaymentDetail' . $identifier]->GLItems) . ' ' . __('Lines') . '</span>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Voucher') . '</th>
								<th class="text-right">' . __('Amount') . '</th>
								<th>' . __('Account') . '</th>
								<th>' . __('Narrative') . '</th>
								<th class="noPrint"></th>
							</tr>
						</thead>
						<tbody>';

		$PaymentTotal = 0;
		foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {
			echo '<tr>
					<td style="font-weight: 800; color: var(--primary);">' . $PaymentItem->Cheque . '</td>
					<td class="text-right" style="font-weight: 800; color: var(--text-main);">' . locale_number_format($PaymentItem->Amount, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
					<td><div class="val-bold" style="font-size:0.85rem;">' . $PaymentItem->GLCode . '</div><div style="font-size:0.7rem; color:var(--text-muted);">' . $PaymentItem->GLActName . '</div></td>
					<td style="font-size: 0.8rem;">' . stripslashes($PaymentItem->Narrative) . '</td>
					<td class="noPrint text-center">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?identifier=' . $identifier) . '&amp;Delete=' . $PaymentItem->ID . '" onclick="return confirm(\'' . __('Confirm delete?') . '\');" class="db-btn-icon" style="color:var(--danger);"><i class="fas fa-trash-alt"></i></a>
					</td>
				</tr>';
			$PaymentTotal += $PaymentItem->Amount;
		}
		echo '</tbody>
				<tfoot style="background: var(--surface-alt);">
					<tr class="db-table-summary">
						<td style="font-weight: 800;">' . __('TOTAL') . '</td>
						<td class="text-right" style="font-weight: 900; color: var(--primary); font-size: 1.1rem;">' . locale_number_format($PaymentTotal, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
						<td colspan="3"></td>
					</tr>
				</tfoot>
			</table></div></div>';
	}
			echo '<div class="card-footer-v2" style="padding: var(--space-5); text-align: center; background: var(--surface-alt);">
				<button type="submit" name="CommitBatch" class="db-btn db-btn-primary" style="padding: var(--space-2) var(--space-8); height: 44px; font-weight: 800;">' . __('Accept and Process Payment') . '</button>
			</div></div>';
} else {
	// Supplier Payment Mode: List Invoices
	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-header">
				<div class="db-card-title" style="flex: 1;"><i class="fas fa-receipt"></i> ' . __('Outstanding Accounts Payable') . '</div>
				<div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">' . __('Selected Supplier') . ': <span style="color: var(--primary);">' . $_SESSION['PaymentDetail' . $identifier]->SuppName . '</span></div>
			</div>
			<div class="db-card-body">
';

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
					<th>' . __('Trade Date') . '</th>
					<th>' . __('Doc Type') . '</th>
					<th>' . __('Reference') . '</th>
					<th class="text-right">' . __('Balance Due') . '</th>
					<th style="text-align: center;">' . __('Action') . '</th>
					<th class="text-right">' . __('Amount to Apportion') . '</th>
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
		echo '<tr>
					<td><span class="db-badge" style="background: var(--surface-alt);">' . ConvertSQLDate($MyRow['trandate']) . '</span></td>
					<td style="font-size: 0.8rem; font-weight: 600; color: var(--text-muted);">' . $MyRow['typename'] . '</td>
					<td>
						<div style="font-weight: 800; color: var(--text-main);">' . $MyRow['transno'] . '</div>
						<div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow['suppreference'] . '</div>
					</td>
					<td class="text-right" style="font-weight: 700;">' . locale_number_format($MyRow['amount'], $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</td>
					<td style="text-align: center;">
						<label class="db-checkbox" style="padding: 4px 12px; background: var(--surface-alt); border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--border-soft);">
							<input onclick="AddAmount(this,' . $MyRow['id'] . ');" type="checkbox" name="check' . $MyRow['id'] . '" value="' . $MyRow['amount'] . '" />
							<span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase;">' . __('Apply') . '</span>
						</label>
					</td>
					<td class="text-right">
						<input type="text" class="db-form-input number" style="width: 140px; text-align: right; font-weight: 800; color: var(--primary);" id="' . $MyRow['id'] . '" name="paid' . $MyRow['id'] . '" value="' . $_POST['paid' . $MyRow['id']] . '" />
						<input type="hidden" name="remainamt' . $MyRow['id'] . '" value="' . $MyRow['amount'] . '" />
					</td>
				</tr>';
		$i++;
	}
	echo '</div><div class="db-card-footer">
			<div style="display: flex; justify-content: flex-end; align-items: center; gap: var(--space-5);">
				<div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 700;">' . __('Aggregated Allocation') . ': <input type="text" id="ttl" value="0" readonly style="width: 150px; text-align: right; border: none; background: transparent; font-weight: 900; color: var(--primary); font-size: 1.25rem;"></div>
				<button type="button" class="db-btn db-btn-secondary" onclick="update1(\'' . $ids . '\')" id="update" style="height: 40px;">
					<i class="fas fa-sync-alt" style="margin-right: 8px;"></i>
					' . __('Recalculate') . '
				</button>
			</div>
	</div></div></div></div>'; // end footer-row, card-body, db-card, pay-tab-allocation
}

echo '<!-- TAB 4: REVIEW & FINALIZE -->
	<div id="pay-tab-finalize" class="pay-tab-content">
		<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-file-invoice-dollar" style="color: var(--primary);"></i> ' . __('Review & Remittance Confirmation') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-2" style="gap: var(--space-5); mb: var(--space-6);">
					<div class="db-form-group">
						<label class="db-form-label">', __('Principal Payment Amount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
						<input class="db-form-input number val-bold" id="Amount" name="Amount" type="text" value="', $_SESSION['PaymentDetail' . $identifier]->Amount, '" style="color: var(--primary); font-size: 1.25rem;" />
					</div>

					<div class="db-form-group">
						<label class="db-form-label">', __('Settlement Discount') . ' (' . $_SESSION['PaymentDetail' . $identifier]->Currency . ')</label>
						<input class="db-form-input number" name="Discount" type="text" value="', $_SESSION['PaymentDetail' . $identifier]->Discount, '" />
					</div>
				</div>

				<div style="display: flex; flex-direction: column; gap: var(--space-5); margin-top: var(--space-6);">
					<div class="db-form-group">
						<label class="db-form-label">', __('Internal Audit Narrative') , '</label>
						<input class="db-form-input" maxlength="200" name="gltrans_narrative" type="text" value="', stripslashes($_POST['gltrans_narrative']) , '" placeholder="' . __('Comment for supplier record') . '" />
					</div>
					
					<div class="db-form-group">
						<label class="db-form-label">', __('External Supplier Reference') , '</label>
						<input class="db-form-input" maxlength="20" name="supptrans_suppreference" type="text" value="', stripslashes($_POST['supptrans_suppreference']) , '" placeholder="' . __('External invoice # reference') . '" />
					</div>

					<div class="db-form-group">
						<label class="db-form-label">', __('Transactional Comments') , '</label>
						<input class="db-form-input" maxlength="200" name="supptrans_transtext" type="text" value="', stripslashes($_POST['supptrans_transtext']) , '" placeholder="' . __('Internal notes') . '" />
						<input name="SuppName" type="hidden" value="', $_SESSION['PaymentDetail' . $identifier]->SuppName, '" />
					</div>
				</div>
			</div> <!-- end card-body -->
			<div class="db-card-footer">
				<button type="submit" name="CommitBatch" onClick="payVerify(\'Amount\',\'ttl\')" class="db-btn db-btn-primary" style="height: 48px; padding: 0 32px; font-size: 1.1rem;">
					<i class="fas fa-check-double" style="margin-right: 12px;"></i>
					' . __('Finalize & Process Payment') . '
				</button>
			</div>
		</div> <!-- end db-card -->
	</div> <!-- end pay-tab-finalize -->

</main> <!-- end db-col-main -->
</div> <!-- end db-bottom-layout -->
</div> <!-- end db-page -->
</form>';

include(__DIR__ . '/includes/footer.php');
