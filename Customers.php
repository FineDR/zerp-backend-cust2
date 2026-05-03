<?php

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/CurrenciesArray.php'); // To get the currency name from the currency code.
if (isset($_POST['ClientSince'])) {
	$_POST['ClientSince'] = ConvertSQLDate($_POST['ClientSince']);
}

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
$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { 
		margin-bottom: 40px; 
		padding: 40px 50px;
		background: #ffffff;
		border-radius: 24px;
		border: 1px solid #e5e7eb;
		box-shadow: var(--shadow-sm);
	}
	.premium-header::before { display: none !important; }
	
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 28px; border-radius: 12px;
		background: #059669; color: #ffffff !important; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; width: 100%;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	.architect-btn.secondary { background: #e5e7eb; color: #374151 !important; box-shadow: none; }
	.architect-btn.secondary:hover { background: #d1d5db; color: #111827 !important; }
	.architect-btn.secondary i { color: #374151 !important; }
	.architect-btn.danger { background: #fee2e2; color: #dc2626 !important; box-shadow: none; }
	.architect-btn.danger:hover { background: #fecaca; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(220, 38, 38, 0.1); }
	.architect-btn.danger i { color: #dc2626 !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	
	/* Tab Bar Styling */
	.db-tab-bar {
		display: flex;
		gap: 8px;
		margin-bottom: 24px;
		background: #f1f5f9;
		padding: 6px;
		border-radius: 16px;
		border: 1px solid #e2e8f0;
	}
	.db-tab {
		padding: 12px 24px;
		border-radius: 12px;
		font-size: 0.85rem;
		font-weight: 700;
		color: #64748b;
		cursor: pointer;
		transition: all 0.2s;
		display: flex;
		align-items: center;
		gap: 8px;
	}
	.db-tab:hover { background: #fff; color: #059669; }
	.db-tab.active { background: #fff; color: #059669; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
	
	.db-tab.has-error { color: #dc2626 !important; position: relative; }
	.db-tab.has-error::after { content: \'\'; position: absolute; top: 12px; right: 8px; width: 6px; height: 6px; background: #dc2626; border-radius: 50%; }

	.db-tab-panel { display: none; }
	.db-tab-panel.active { display: block; animation: slideIn 0.3s ease-out; }
	
	@keyframes slideIn {
		from { opacity: 0; transform: translateY(10px); }
		to { opacity: 1; transform: translateY(0); }
	}

	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; font-size: 0.72rem; font-weight: 700; text-transform: lowercase; letter-spacing: 1px; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	.db-card-body { padding: 30px; }
	.db-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
	.db-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
	.db-form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
	.db-label { font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; }
	.db-input { width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border: 1px solid #d1fae5; padding: 0 16px; box-sizing: border-box; background: #fff; transition: all 0.2s; }
	.db-input:focus { border-color: #059669; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }
	
	.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.registry-table th { background: #f9fafb; padding: 16px 20px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 900; color: #065f46; letter-spacing: 1.2px; border-bottom: 1px solid #f3f4f6; }
	.registry-table td { padding: 16px 20px; font-size: 0.88rem; color: #374151; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
	.registry-table tr:hover td { background: #f0fdf4; }

	@media (max-width: 1100px) {
		.custom-bottom-layout { display: flex; flex-direction: column; }
		.db-sidebar { width: 100%; }
		.db-grid-2, .db-grid-3 { grid-template-columns: 1fr; }
		.db-tab-bar { overflow-x: auto; white-space: nowrap; }
	}
</style>
<script>
	function switchTab(tabId) {
		document.querySelectorAll(".db-tab").forEach(t => t.classList.remove("active"));
		document.querySelectorAll(".db-tab-panel").forEach(p => p.classList.remove("active"));
		const tabBtn = document.querySelector(`[onclick^="switchTab(\'${tabId}\')"]`);
		if (tabBtn) tabBtn.classList.add("active");
		const panel = document.getElementById(tabId);
		if (panel) panel.classList.add("active");
		
		// Update hidden input for persistence
		let persistInput = document.getElementById("ActiveTabPersist");
		if (!persistInput) {
			persistInput = document.createElement("input");
			persistInput.type = "hidden";
			persistInput.name = "ActiveTab";
			persistInput.id = "ActiveTabPersist";
			document.querySelector("form").appendChild(persistInput);
		}
		persistInput.value = tabId;
	}
	
	window.addEventListener(\'load\', () => {
		const urlParams = new URLSearchParams(window.location.search);
		const tabFromUrl = urlParams.get(\'ActiveTab\');
		if (tabFromUrl) switchTab(tabFromUrl);
	});
</script>';

include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/CountriesArray.php');
include(__DIR__ . '/includes/LanguagesArray.php');

// Initialize numeric separators from language preferences with global scope
global $ThousandsSeparator, $DecimalPoint;
$ThousandsSeparator = $LanguagesArray[$_SESSION['Language']]['ThousandsSeparator'] ?? (isset($_SESSION['DefaultThousandsSeparator']) ? $_SESSION['DefaultThousandsSeparator'] : ',');
$DecimalPoint = $LanguagesArray[$_SESSION['Language']]['DecimalPoint'] ?? (isset($_SESSION['DefaultDecimalPoint']) ? $_SESSION['DefaultDecimalPoint'] : '.');

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
					<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<a href="SelectCustomer.php" class="breadcrumb-item">' . __('search customers') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<span style="color: #064e3b; opacity: 0.9;">' . __('customer maintenance') . '</span>
				</div>
					<div>
						<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
						<p style="font-size: 1.1rem; margin-top: 12px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Build and maintain high-value customer profiles and credit configurations') . '</p>
					</div>
				</div>
			</div>
		</div>';

$Errors = array();
$ErrorTabs = array();
$ActiveTab = $_GET['ActiveTab'] ?? $_POST['ActiveTab'] ?? 'tab-identity';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	$i = 1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$_POST['DebtorNo'] = mb_strtoupper($_POST['DebtorNo']);

	$SQL = "SELECT COUNT(debtorno) FROM debtorsmaster WHERE debtorno='" . $_POST['DebtorNo'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0 AND isset($_POST['New'])) {
		$InputError = 1;
		prnMsg(__('The customer number already exists in the database'), 'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif (mb_strlen($_POST['CustName']) > 40 OR mb_strlen($_POST['CustName']) == 0) {
		$InputError = 1;
		prnMsg(__('The customer name must be entered and be forty characters or less long'), 'error');
		$Errors[$i] = 'CustName';
		$i++;
	} elseif ($_SESSION['AutoDebtorNo'] == 0 AND mb_strlen($_POST['DebtorNo']) == 0) {
		$InputError = 1;
		prnMsg(__('The debtor code cannot be empty'), 'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif ($_SESSION['AutoDebtorNo'] == 0 AND (ContainsIllegalCharacters($_POST['DebtorNo']) OR mb_strpos($_POST['DebtorNo'], ' '))) {
		$InputError = 1;
		prnMsg(__('The customer code cannot contain any of the following characters') . " . - ' &amp; + \" " . __('or a space'), 'error');
		$Errors[$i] = 'DebtorNo';
		$i++;
	} elseif (mb_strlen($_POST['Address1']) > 40) {
		$InputError = 1;
		prnMsg(__('The Line 1 of the address must be forty characters or less long'), 'error');
		$Errors[$i] = 'Address1';
		$i++;
	} elseif (mb_strlen($_POST['Address2']) > 40) {
		$InputError = 1;
		prnMsg(__('The Line 2 of the address must be forty characters or less long'), 'error');
		$Errors[$i] = 'Address2';
		$i++;
	} elseif (mb_strlen($_POST['Address3']) > 40) {
		$InputError = 1;
		prnMsg(__('The Line 3 of the address must be forty characters or less long'), 'error');
		$Errors[$i] = 'Address3';
		$i++;
	} elseif (mb_strlen($_POST['Address4']) > 50) {
		$InputError = 1;
		prnMsg(__('The Line 4 of the address must be fifty characters or less long'), 'error');
		$Errors[$i] = 'Address4';
		$i++;
	} elseif (mb_strlen($_POST['Address5']) > 20) {
		$InputError = 1;
		prnMsg(__('The Line 5 of the address must be twenty characters or less long'), 'error');
		$Errors[$i] = 'Address5';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['CreditLimit']))) {
		$InputError = 1;
		prnMsg(__('The credit limit must be numeric'), 'error');
		$Errors[$i] = 'CreditLimit';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['PymtDiscount']))) {
		$InputError = 1;
		prnMsg(__('The payment discount must be numeric'), 'error');
		$Errors[$i] = 'PymtDiscount';
		$i++;
	} elseif (!Is_Date($_POST['ClientSince'])) {
		$InputError = 1;
		prnMsg(__('The customer since field must be a date in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
		$Errors[$i] = 'ClientSince';
		$i++;
	} elseif (!is_numeric(filter_number_format($_POST['Discount']))) {
		$InputError = 1;
		prnMsg(__('The discount percentage must be numeric'), 'error');
		$Errors[$i] = 'Discount';
		$i++;
	} elseif (filter_number_format($_POST['CreditLimit']) < 0) {
		$InputError = 1;
		prnMsg(__('The credit limit must be a positive number'), 'error');
		$Errors[$i] = 'CreditLimit';
		$i++;
	} elseif ((filter_number_format($_POST['PymtDiscount']) > 10) OR (filter_number_format($_POST['PymtDiscount']) < 0)) {
		$InputError = 1;
		prnMsg(__('The payment discount is expected to be less than 10% and greater than or equal to 0'), 'error');
		$Errors[$i] = 'PymtDiscount';
		$i++;
	} elseif ((filter_number_format($_POST['Discount']) > 100) OR (filter_number_format($_POST['Discount']) < 0)) {
		$InputError = 1;
		prnMsg(__('The discount is expected to be less than 100% and greater than or equal to 0'), 'error');
		$Errors[$i] = 'Discount';
		$i++;
	}
	// Identify which tab has errors for automatic switching
	$ErrorTabs = array();
	foreach ($Errors as $ErrorField) {
		if (in_array($ErrorField, array('DebtorNo', 'CustName', 'SalesType', 'typeid'))) $ErrorTabs['tab-identity'] = true;
		if (preg_match('/Address/', $ErrorField)) $ErrorTabs['tab-location'] = true;
		if (in_array($ErrorField, array('CreditLimit', 'PymtDiscount', 'Discount', 'TaxRef', 'CurrCode', 'PaymentTerms'))) $ErrorTabs['tab-financial'] = true;
		if (in_array($ErrorField, array('ClientSince', 'HoldReason'))) $ErrorTabs['tab-settings'] = true;
	}
	
	// Error tabs take precedence over previous activity
	if (!empty($ErrorTabs)) {
		$ActiveTab = array_key_first($ErrorTabs);
	} else {
		$ActiveTab = $_POST['ActiveTab'] ?? 'tab-identity';
	}

	if ($InputError != 1) {

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
												address3='" . $_POST['Address3'] . "',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												currcode='" . $_POST['CurrCode'] . "',
												clientsince='" . $SQL_ClientSince . "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount']) / 100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "'
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
												address3='" . $_POST['Address3'] . "',
												address4='" . $_POST['Address4'] . "',
												address5='" . $_POST['Address5'] . "',
												address6='" . $_POST['Address6'] . "',
												clientsince='" . $SQL_ClientSince . "',
												holdreason='" . $_POST['HoldReason'] . "',
												paymentterms='" . $_POST['PaymentTerms'] . "',
												discount='" . filter_number_format($_POST['Discount']) / 100 . "',
												discountcode='" . $_POST['DiscountCode'] . "',
												pymtdiscount='" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
												creditlimit='" . filter_number_format($_POST['CreditLimit']) . "',
												salestype = '" . $_POST['SalesType'] . "',
												invaddrbranch='" . $_POST['AddrInvBranch'] . "',
												taxref='" . $_POST['TaxRef'] . "',
												customerpoline='" . $_POST['CustomerPOLine'] . "',
												typeid='" . $_POST['typeid'] . "'
						WHERE debtorno = '" . $_POST['DebtorNo'] . "'";

				if ($OldCurrency != $_POST['CurrCode']) {
					prnMsg(__('The currency code cannot be updated as there are already transactions for this customer'), 'info');
				}
			}

			$ErrMsg = __('The customer could not be updated because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg(__('Customer updated'), 'success');
			echo '<br />';

		} else { //it is a new customer
			/* set the DebtorNo if $AutoDebtorNo in config.php has been set to
			something greater 0 */
			if ($_SESSION['AutoDebtorNo'] > 0) {
				/* system assigned, sequential, numeric */
				if ($_SESSION['AutoDebtorNo'] == 1) {
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
							typeid)
				VALUES ('" . $_POST['DebtorNo'] . "',
						'" . $_POST['CustName'] . "',
						'" . $_POST['Address1'] . "',
						'" . $_POST['Address2'] . "',
						'" . $_POST['Address3'] . "',
						'" . $_POST['Address4'] . "',
						'" . $_POST['Address5'] . "',
						'" . $_POST['Address6'] . "',
						'" . $_POST['CurrCode'] . "',
						'" . $SQL_ClientSince . "',
						'" . $_POST['HoldReason'] . "',
						'" . $_POST['PaymentTerms'] . "',
						'" . filter_number_format($_POST['Discount']) / 100 . "',
						'" . $_POST['DiscountCode'] . "',
						'" . filter_number_format($_POST['PymtDiscount']) / 100 . "',
						'" . filter_number_format($_POST['CreditLimit']) . "',
						'" . $_POST['SalesType'] . "',
						'" . $_POST['AddrInvBranch'] . "',
						'" . $_POST['TaxRef'] . "',
						'" . $_POST['CustomerPOLine'] . "',
						'" . $_POST['typeid'] . "')";

			$ErrMsg = __('This customer could not be added because');
			$Result = DB_query($SQL, $ErrMsg);

			echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/CustomerBranches.php?DebtorNo=' . $_POST['DebtorNo'] . '">';

			echo '<div class="centre">' . __('You should automatically be forwarded to the entry of a new Customer Branch page') .
				'. ' . __('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' .
				'<a href="' . $RootPath . '/CustomerBranches.php?DebtorNo=' . $_POST['DebtorNo'] . '"></a></div>';

			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	} else {
		prnMsg(__('Validation failed') . '. ' . __('No updates or deletes took place'), 'error');
	}

} elseif (isset($_POST['delete'])) {

	//the link to delete a selected record was clicked instead of the submit button

	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'

	$SQL = "SELECT COUNT(*) FROM debtortrans WHERE debtorno='" . $_POST['DebtorNo'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		$CancelDelete = 1;
		prnMsg(__('This customer cannot be deleted because there are transactions that refer to it'), 'warn');
		echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('transactions against this customer');

	} else {
		$SQL = "SELECT COUNT(*) FROM salesorders WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete the customer record because orders have been created against it'), 'warn');
			echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('orders against this customer');
		} else {
			$SQL = "SELECT COUNT(*) FROM salesanalysis WHERE cust='" . $_POST['DebtorNo'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				$CancelDelete = 1;
				prnMsg(__('Cannot delete this customer record because sales analysis records exist for it'), 'warn');
				echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('sales analysis records against this customer');
			} else {

				// Check if there are any users that refer to this CUSTOMER code
				$SQL = "SELECT COUNT(*) FROM www_users WHERE www_users.customerid = '" . $_POST['DebtorNo'] . "'";

				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);

				if ($MyRow[0] > 0) {
					prnMsg(__('Cannot delete this customer because users exist that refer to it') . '. ' . __('Purge old users first'), 'warn');
					echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('users referring to this Branch/customer');
				} else {
					// Check if there are any contract that refer to this branch code
					$SQL = "SELECT COUNT(*) FROM contracts WHERE contracts.debtorno = '" . $_POST['DebtorNo'] . "'";

					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);

					if ($MyRow[0] > 0) {
						prnMsg(__('Cannot delete this customer because contracts have been created that refer to it') . '. ' . __('Purge old contracts first'), 'warn');
						echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('contracts referring to this customer');
					}
				}
			}
		}

	}
	if ($CancelDelete == 0) { //ie not cancelled the delete as a result of above tests
		$SQL = "DELETE FROM custbranch WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL, $ErrMsg);
		$SQL = "DELETE FROM custcontacts WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		$SQL = "DELETE FROM debtorsmaster WHERE debtorno='" . $_POST['DebtorNo'] . "'";
		$Result = DB_query($SQL);
		prnMsg(__('Customer') . ' ' . $_POST['DebtorNo'] . ' ' . __('has been deleted - together with all the associated branches and contacts'), 'success');
		unset($_SESSION['CustomerID']);
		include(__DIR__ . '/includes/footer.php');
		exit();
	} //end if Delete Customer
}

if (isset($_POST['Reset'])) {
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
}

/*DebtorNo could be set from a post or a get when passed as a parameter to this page */

if (isset($_POST['DebtorNo'])) {
	$DebtorNo = $_POST['DebtorNo'];
} elseif (isset($_GET['DebtorNo'])) {
	$DebtorNo = $_GET['DebtorNo'];
}

if (isset($_POST['AddContact']) AND (isset($_POST['AddContact']) != '')) {
	echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . $DebtorNo . '">';
}

if (!isset($DebtorNo)) {

	$SetupErrors = 0; //Count errors
	$SQL = "SELECT COUNT(typeabbrev) FROM salestypes";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 0) {
		prnMsg(__('In order to create a new customer you must first set up at least one sales type/price list'), 'warning');
		$SetupErrors += 1;
	}
	$SQL = "SELECT COUNT(typeid) FROM debtortype";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 0) {
		prnMsg(__('In order to create a new customer you must first set up at least one customer type'), 'warning');
		$SetupErrors += 1;
	}

	if ($SetupErrors > 0) {
		echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" >' . __('Click here to continue') . '</a></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display: contents;">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="New" value="Yes" />
		
		<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-cog" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Actions') . '
						</h3>
					</div>
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px; background: #fff;">
						<button type="submit" name="submit" class="architect-btn">
							<i class="fas fa-plus-circle"></i> ' . __('Create Customer') . '
						</button>
						<button type="reset" name="Reset" class="architect-btn secondary">
							<i class="fas fa-undo"></i> ' . __('Reset Form') . '
						</button>
						<a href="' . $RootPath . '/SelectCustomer.php" class="architect-btn secondary">
							<i class="fas fa-arrow-left"></i> ' . __('Back to Search') . '
						</a>
					</div>
				</div>

				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 20px; display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
					<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
					<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
						' . __('Enter the customer details below. After creation, you will be redirected to add at least one branch for this customer.') . '
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column;">
				<div class="db-tab-bar">
					<div class="db-tab' . ($ActiveTab == 'tab-identity' ? ' active' : '') . (isset($ErrorTabs['tab-identity']) ? ' has-error' : '') . '" onclick="switchTab(\'tab-identity\')">
						<i class="fas fa-id-card"></i> ' . __('Identity') . '
					</div>
					<div class="db-tab' . ($ActiveTab == 'tab-location' ? ' active' : '') . (isset($ErrorTabs['tab-location']) ? ' has-error' : '') . '" onclick="switchTab(\'tab-location\')">
						<i class="fas fa-map-marker-alt"></i> ' . __('Location') . '
					</div>
					<div class="db-tab' . ($ActiveTab == 'tab-financial' ? ' active' : '') . (isset($ErrorTabs['tab-financial']) ? ' has-error' : '') . '" onclick="switchTab(\'tab-financial\')">
						<i class="fas fa-money-bill-wave"></i> ' . __('Financial') . '
					</div>
					<div class="db-tab' . ($ActiveTab == 'tab-settings' ? ' active' : '') . (isset($ErrorTabs['tab-settings']) ? ' has-error' : '') . '" onclick="switchTab(\'tab-settings\')">
						<i class="fas fa-cogs"></i> ' . __('Settings') . '
					</div>
				</div>

				<input type="hidden" name="ActiveTab" id="ActiveTabPersist" value="' . $ActiveTab . '" />

				<!-- Tab 1: Identity -->
				<div id="tab-identity" class="db-tab-panel' . ($ActiveTab == 'tab-identity' ? ' active' : '') . '">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('General Profile') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-grid-2">';

	if ($_SESSION['AutoDebtorNo'] == 0) {
		echo '<div class="db-form-group">
				<label class="db-label">' . __('Customer Code') . '</label>
				<input type="text" name="DebtorNo" required="required" autofocus="autofocus" class="db-input" maxlength="10" placeholder="' . __('e.g. CUST001') . '" />
			</div>';
	}

	echo '<div class="db-form-group">
			<label class="db-label">' . __('Customer Name') . '</label>
			<input type="text" name="CustName" required="required" class="db-input" maxlength="40" placeholder="' . __('Company or Individual Name') . '" />
		</div>';

	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type");
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Sales Type / Price List') . '</label>
			<select name="SalesType" required="required" class="db-input">';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
	}
	echo '</select>
		</div>';

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Customer Category') . '</label>
			<select name="typeid" required="required" class="db-input">';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['typeid'] . '">' . $MyRow['typename'] . '</option>';
	}
	echo '</select>
		</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 2: Location -->
				<div id="tab-location" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('Address Details') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-form-group" style="margin-bottom: 24px;">
								<label class="db-label">' . __('Street Address') . '</label>
								<input type="text" name="Address1" required="required" class="db-input" maxlength="40" placeholder="' . __('Building/Street Line 1') . '" />
								<input type="text" name="Address2" class="db-input" maxlength="40" placeholder="' . __('Area/Street Line 2') . '" style="margin-top: 12px;" />
							</div>
							<div class="db-grid-2">
								<div class="db-form-group">
									<label class="db-label">' . __('City / Town') . '</label>
									<input type="text" name="Address3" class="db-input" maxlength="40" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Province / State') . '</label>
									<input type="text" name="Address4" class="db-input" maxlength="40" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Postal Code') . '</label>
									<input type="text" name="Address5" class="db-input" maxlength="20" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Country') . '</label>
									<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $CountryName) {
		echo '<option value="' . $CountryName . '">' . $CountryName . '</option>';
	}
	echo '				</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 3: Configuration -->
				<div id="tab-financial" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('Financial & System Details') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-grid-3">
								<div class="db-form-group">
									<label class="db-label">' . __('Credit Limit') . '</label>
									<input type="text" name="CreditLimit" required="required" value="' . locale_number_format($_SESSION['DefaultCreditLimit'], 0) . '" class="db-input" maxlength="14" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Base Discount %') . '</label>
									<input type="text" name="Discount" value="0" class="db-input" maxlength="4" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Payment Discount %') . '</label>
									<input type="text" name="PymtDiscount" value="0" class="db-input" maxlength="4" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Target Currency') . '</label>
									<select name="CurrCode" required="required" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Payment Terms') . '</label>
									<select name="PaymentTerms" required="required" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Tax Reference') . '</label>
									<input type="text" name="TaxRef" class="db-input" maxlength="20" />
								</div>
							</div>

							</div>

							<div class="db-grid-3" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
								<div class="db-form-group">
									<label class="db-label">' . __('Invoice Branch') . '</label>
									<select name="AddrInvBranch" class="db-input">
										<option value="0">' . __('None') . '</option>';
	$Result = DB_query("SELECT branchcode, brname FROM custbranch");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['branchcode'] . '">' . $MyRow['brname'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Customer PO Line') . '</label>
									<select name="CustomerPOLine" class="db-input">
										<option value="0">' . __('No') . '</option>
										<option value="1">' . __('Yes') . '</option>
									</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Discount Code') . '</label>
									<input type="text" name="DiscountCode" class="db-input" maxlength="2" />
								</div>
							</div>

							<div class="db-grid-2" style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
								<div class="db-form-group">
									<label class="db-label">' . __('Client Since') . '</label>
									<input type="date" name="ClientSince" value="' . date('Y-m-d') . '" class="db-input" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Credit Status') . '</label>
									<select name="HoldReason" required="required" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['reasoncode'] . '">' . $MyRow['reasondescription'] . '</option>';
	}
	echo '				</select>
								</div>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>
	</form>';


} else {
	// EDIT MODE
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display: contents;">
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
		$_POST['Discount'] = locale_number_format($MyRow['discount'] * 100, 2);
		$_POST['DiscountCode'] = $MyRow['discountcode'];
		$_POST['PymtDiscount'] = locale_number_format($MyRow['pymtdiscount'] * 100, 2);
		$_POST['CreditLimit'] = locale_number_format($MyRow['creditlimit'], 0);
		$_POST['InvAddrBranch'] = $MyRow['invaddrbranch'];
		$_POST['TaxRef'] = $MyRow['taxref'];
		$_POST['CustomerPOLine'] = $MyRow['customerpoline'];
		$_POST['typeid'] = $MyRow['typeid'];

		echo '<input type="hidden" name="DebtorNo" value="' . $DebtorNo . '" />';
	}

	echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-cog" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Control Panel') . '
						</h3>
					</div>
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px; background: #fff;">
						<button type="submit" name="submit" class="architect-btn">
							<i class="fas fa-save"></i> ' . __('Save Changes') . '
						</button>
						<a href="' . $RootPath . '/CustomerBranches.php?DebtorNo=' . $DebtorNo . '" class="architect-btn secondary">
							<i class="fas fa-code-branch"></i> ' . __('View Branches') . '
						</a>
						<a href="' . $RootPath . '/SelectCustomer.php" class="architect-btn secondary">
							<i class="fas fa-arrow-left"></i> ' . __('Back to Search') . '
						</a>
						<div style="margin-top: 12px; padding-top: 24px; border-top: 1px solid #f3f4f6;">
							<button type="submit" name="delete" class="architect-btn danger" onclick="return confirm(\'' . __('Are you sure you wish to delete this customer record?') . '\');">
								<i class="fas fa-trash-alt"></i> ' . __('Delete Account') . '
							</button>
						</div>
					</div>
				</div>

				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 20px; display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
					<i class="fas fa-shield-alt" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
					<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
						' . __('You are currently in edit mode for customer') . ' <strong>' . $DebtorNo . '</strong>. ' . __('Any changes saved will take effect across the entire system immediately.') . '
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column;">
				<!-- Tab Bar -->
				<div class="db-tab-bar">
					<div class="db-tab active" onclick="switchTab(\'tab-identity\')">
						<i class="fas fa-id-card"></i> ' . __('Identity') . '
					</div>
					<div class="db-tab" onclick="switchTab(\'tab-location\')">
						<i class="fas fa-map-marker-alt"></i> ' . __('Location') . '
					</div>
					<div class="db-tab" onclick="switchTab(\'tab-financial\')">
						<i class="fas fa-money-bill-wave"></i> ' . __('Financial') . '
					</div>
					<div class="db-tab" onclick="switchTab(\'tab-settings\')">
						<i class="fas fa-sliders-h"></i> ' . __('Settings') . '
					</div>
					<div class="db-tab" onclick="switchTab(\'tab-contacts\')">
						<i class="fas fa-users"></i> ' . __('Contacts') . '
					</div>
				</div>

				<!-- Tab 1: Identity -->
				<div id="tab-identity" class="db-tab-panel active">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('Company Profile') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-grid-2">
								<div class="db-form-group">
									<label class="db-label">' . __('Customer Code') . '</label>
									<input type="text" class="db-input" value="' . $DebtorNo . '" disabled style="background: #f9fafb; color: #6b7280; font-family: monospace;" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Customer Name') . '</label>
									<input type="text" name="CustName" class="db-input" required value="' . $_POST['CustName'] . '" maxlength="40" />
								</div>';

	$Result = DB_query("SELECT typeabbrev, sales_type FROM salestypes ORDER BY sales_type");
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Sales Type / Price List') . '</label>
			<select name="SalesType" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['SalesType'] == $myr['typeabbrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['typeabbrev'] . '">' . $myr['sales_type'] . '</option>';
	}
	echo '</select></div>';

	$Result = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Customer Category') . '</label>
			<select name="typeid" class="db-input">';
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['typeid'] == $myr['typeid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['typeid'] . '">' . $myr['typename'] . '</option>';
	}
	echo '</select></div>';

	echo '		</div>
						</div>
					</div>
				</div>

				<!-- Tab 2: Location -->
				<div id="tab-location" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('Address Details') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-form-group" style="margin-bottom: 24px;">
								<label class="db-label">' . __('Street Address') . '</label>
								<input type="text" name="Address1" class="db-input" required value="' . $_POST['Address1'] . '" maxlength="40" placeholder="' . __('Line 1') . '" />
								<input type="text" name="Address2" class="db-input" value="' . $_POST['Address2'] . '" maxlength="40" placeholder="' . __('Line 2') . '" style="margin-top: 12px;" />
							</div>
							<div class="db-grid-2">
								<div class="db-form-group">
									<label class="db-label">' . __('City') . '</label>
									<input type="text" name="Address3" class="db-input" value="' . $_POST['Address3'] . '" maxlength="40" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Province / State') . '</label>
									<input type="text" name="Address4" class="db-input" value="' . $_POST['Address4'] . '" maxlength="40" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Postal Code') . '</label>
									<input type="text" name="Address5" class="db-input" value="' . $_POST['Address5'] . '" maxlength="20" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Country') . '</label>
									<select name="Address6" class="db-input">';
	foreach ($CountriesArray as $cn) {
		$sel = (strtoupper($_POST['Address6']) == strtoupper($cn)) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $cn . '">' . $cn . '</option>';
	}
	echo '				</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 3: Financial -->
				<div id="tab-financial" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('Financial Configuration') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-grid-3">
								<div class="db-form-group">
									<label class="db-label">' . __('Credit Limit') . '</label>
									<input type="text" name="CreditLimit" class="db-input" value="' . $_POST['CreditLimit'] . '" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Base Discount %') . '</label>
									<input type="text" name="Discount" class="db-input" value="' . $_POST['Discount'] . '" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Settlement Discount %') . '</label>
									<input type="text" name="PymtDiscount" class="db-input" value="' . $_POST['PymtDiscount'] . '" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Currency') . '</label>
									<select name="CurrCode" class="db-input">';
	$Result = DB_query("SELECT currency, currabrev FROM currencies");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['CurrCode'] == $myr['currabrev']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['currabrev'] . '">' . $myr['currency'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Payment Terms') . '</label>
									<select name="PaymentTerms" class="db-input">';
	$Result = DB_query("SELECT terms, termsindicator FROM paymentterms");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['PaymentTerms'] == $myr['termsindicator']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['termsindicator'] . '">' . $myr['terms'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Tax Reference') . '</label>
									<input type="text" name="TaxRef" class="db-input" value="' . $_POST['TaxRef'] . '" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Invoice Branch') . '</label>
									<select name="AddrInvBranch" class="db-input">
										<option value="0">' . __('None') . '</option>';
	$Result = DB_query("SELECT branchcode, brname FROM custbranch WHERE debtorno='" . $DebtorNo . "'");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['InvAddrBranch'] == $myr['branchcode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['branchcode'] . '">' . $myr['brname'] . '</option>';
	}
	echo '				</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Customer PO Line') . '</label>
									<select name="CustomerPOLine" class="db-input">
										<option ' . ($_POST['CustomerPOLine'] == 0 ? 'selected="selected"' : '') . ' value="0">' . __('No') . '</option>
										<option ' . ($_POST['CustomerPOLine'] == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Yes') . '</option>
									</select>
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Discount Code') . '</label>
									<input type="text" name="DiscountCode" class="db-input" value="' . $_POST['DiscountCode'] . '" maxlength="2" />
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 4: Settings -->
				<div id="tab-settings" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header">
							<h3 class="db-card-title">' . __('System Preferences') . '</h3>
						</div>
						<div class="db-card-body" style="background: #fff;">
							<div class="db-grid-2">
								<div class="db-form-group">
									<label class="db-label">' . __('Client Since') . '</label>
									<input type="date" name="ClientSince" class="db-input" value="' . (Is_Date($_POST['ClientSince']) ? FormatDateForSQL($_POST['ClientSince']) : $_POST['ClientSince']) . '" />
								</div>
								<div class="db-form-group">
									<label class="db-label">' . __('Credit Status') . '</label>
									<select name="HoldReason" class="db-input">';
	$Result = DB_query("SELECT reasoncode, reasondescription FROM holdreasons");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['HoldReason'] == $myr['reasoncode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['reasoncode'] . '">' . $myr['reasondescription'] . '</option>';
	}
	echo '				</select>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 5: Contacts -->
				<div id="tab-contacts" class="db-tab-panel">
					<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
						<div class="db-card-header" style="padding: 20px 30px; display: flex; justify-content: space-between; align-items: center;">
							<h3 class="db-card-title">' . __('Authorized Contacts') . '</h3>
							<button type="submit" name="AddContact" style="background: transparent; border: none; color: #059669; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 8px;">
								<i class="fas fa-plus-circle"></i> ' . __('Add Contact') . '
							</button>
						</div>
						<div style="background: #fff; overflow-x: auto;">
							<table class="registry-table">
								<thead>
									<tr>
										<th>' . __('Name') . '</th>
										<th>' . __('Role') . '</th>
										<th>' . __('Contact Method') . '</th>
										<th style="text-align: right;">' . __('Actions') . '</th>
									</tr>
								</thead>
								<tbody>';
	$SQL = "SELECT * FROM custcontacts WHERE debtorno='" . $DebtorNo . "' ORDER BY contid";
	$Result = DB_query($SQL);
	while ($myr = DB_fetch_array($Result)) {
		echo '<tr>
				<td style="font-weight: 700; color: #064e3b;">' . $myr['contactname'] . '</td>
				<td><span class="badge" style="background: #f0fdf4; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">' . $myr['role'] . '</span></td>
				<td>
					<div style="font-weight: 600;">' . $myr['phoneno'] . '</div>
					<div style="font-size: 0.75rem; opacity: 0.6;">' . $myr['email'] . '</div>
				</td>
				<td style="text-align: right;">
					<a href="' . $RootPath . '/AddCustomerContacts.php?Id=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '" style="color: #059669; margin-right: 16px;"><i class="fas fa-edit"></i></a>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?ID=' . $myr['contid'] . '&DebtorNo=' . $DebtorNo . '&delete=1" style="color: #dc2626;" onclick="return confirm(\'' . __('Are you sure you wish to delete this contact?') . '\');"><i class="fas fa-trash-alt"></i></a>
				</td>
			</tr>';
	}
	echo '				</tbody>
							</table>
						</div>
					</div>
				</div>
			</main>
		</div>
	</form>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
?>