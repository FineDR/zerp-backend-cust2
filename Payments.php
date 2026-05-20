<?php

/* Entry of bank account payments either against an AP account or a general ledger payment - if the AP-GL link in company preferences is set */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefinePaymentClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Entry');
if (isset($_GET['SupplierID'])) { // Links to Manual before header.php
	$ViewTopic = 'AccountsPayable';
	$BookMark = 'SupplierPayments';
	$PageTitleText = __('Supplier Transaction Payment Entry');
} else {
	$ViewTopic = 'GeneralLedger';
	$BookMark = 'BankAccountPayments';
	$PageTitleText = __('Bank Account Payments Entry');
}

if (empty($_GET['identifier']) && empty($_POST['identifier'])) {
	$identifier = date('U'); // Unique session identifier
} else {
	$identifier = !empty($_GET['identifier']) ? $_GET['identifier'] : $_POST['identifier'];
}

if (isset($_GET['NewPayment']) AND $_GET['NewPayment'] == 'Yes') {
	unset($_SESSION['PaymentDetail' . $identifier]->GLItems);
	unset($_SESSION['PaymentDetail' . $identifier]);
}

if (!isset($_SESSION['PaymentDetail' . $identifier])) {
	$_SESSION['PaymentDetail' . $identifier] = new Payment;
	$_SESSION['PaymentDetail' . $identifier]->GLItemCounter = 1;
}

include(__DIR__ . '/includes/header.php');

if (isset($_POST['DatePaid'])) {
	$_POST['DatePaid'] = ConvertSQLDate($_POST['DatePaid']);
}


// ===== ALL PHP PROCESSING IS DONE ABOVE =====

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['PaymentCancelled'])) {
	prnMsg(__('Payment Cancelled since cheque was not printed'), 'warning');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if ((isset($_POST['UpdateHeader']) AND $_POST['BankAccount'] == '') OR (isset($_POST['Process']) AND $_POST['BankAccount'] == '')) {
	prnMsg(__('A bank account must be selected to make this payment from'), 'warn');
	$BankAccountEmpty = true;
} else {
	$BankAccountEmpty = false;
}

if (isset($_POST['CommitBatch']) AND empty($Errors)) {
	/* once the GL analysis of the payment is entered (if the Creditors_GLLink is active),
	process all the data in the session cookie into the DB creating a banktrans record for
	the payment in the batch and SuppTrans record for the supplier payment if a supplier was selected
	A GL entry is created for each GL entry (only one for a supplier entry) and one for the bank
	account credit.
	*/

	$TotalAmount = 0;
	foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems AS $PaymentItem) {
		$TotalAmount += $PaymentItem->Amount;
	}

	if ($TotalAmount == 0 AND ($_SESSION['PaymentDetail' . $identifier]->Discount + $_SESSION['PaymentDetail' . $identifier]->Amount) / $_SESSION['PaymentDetail' . $identifier]->ExRate == 0) {
		prnMsg(__('This payment has no amounts entered and will not be processed'), 'warn');
	} elseif ($_POST['BankAccount'] == '') {
		prnMsg(__('No bank account has been selected so this payment cannot be processed'), 'warn');
	} else {

		/*Make an array of the defined bank accounts */
		$SQL = "SELECT bankaccounts.accountcode FROM bankaccounts INNER JOIN chartmaster ON bankaccounts.accountcode=chartmaster.accountcode";
		$Result = DB_query($SQL);
		$BankAccounts = array();
		while ($Act = DB_fetch_row($Result)) {
			$BankAccounts[] = $Act[0];
		}

		$PeriodNo = GetPeriod($_SESSION['PaymentDetail' . $identifier]->DatePaid);

		$SQL = "SELECT usepreprintedstationery FROM paymentmethods WHERE paymentname='" . $_SESSION['PaymentDetail' . $identifier]->Paymenttype . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);

		if ((!isset($_POST['ChequePrinted'])) AND (!isset($_POST['PaymentCancelled'])) AND (isset($MyRow[0]) && $MyRow[0] == 1)) {
			// it is a supplier payment by cheque and haven't printed yet so print cheque
			if (empty($_POST['ChequeNum'])) {
				prnMsg(__('There are no Check Number input'), 'error');
			} elseif (!is_numeric($_POST['ChequeNum'])) {
				prnMsg(__('The cheque no should be numeric'), 'error');
			} else {
				$ChequeSQL = "SELECT count(chequeno) FROM supptrans WHERE chequeno='" . $_POST['ChequeNum'] . "'";
				$ChequeResult = DB_query($ChequeSQL);
				$ChequeRow = DB_fetch_row($ChequeResult);
				if ($ChequeRow[0] > 0) {
					prnMsg(__('The cheque has already been used'), 'error');
				} else {
					// Logic for cheque printing follows (unchanged)
					$PaidArray = array();
					foreach ($_POST as $Name => $Value) {
						if (substr($Name, 0, 4) == 'paid' AND filter_number_format($Value) > 0) {
							$PaidArray[substr($Name, 4)] = filter_number_format($Value);
						}
					}
					$PaidInput = !empty($PaidArray) ? '<input type="hidden" name="PaidArray" value="' . base64_encode(serialize($PaidArray)) . '" />' : '';

					echo '<div class="db-page" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 80px 24px; min-height: 60vh;">
							<div style="max-width: 600px; width: 100%; animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);">
								<div class="db-card" style="text-align: center; padding: 56px; border: 1px solid #e2e8f0; border-radius: 32px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);">
									<div style="width: 88px; height: 88px; background: #f0f9ff; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 32px;">
										<i class="fas fa-print" style="font-size: 2.5rem;"></i>
									</div>
									<h2 style="font-size: 1.75rem; font-weight: 900; color: #066e96; margin-bottom: 24px;">' . __('Cheque Printing') . '</h2>
									
									<a href="' . $RootPath . '/PrintCheque.php?ChequeNum=' . $_POST['ChequeNum'] . '&amp;identifier=' . $identifier . '" target="_blank" class="architect-btn" style="height: 56px; width: 100%; font-size: 1.1rem; border-radius: 16px; margin-bottom: 32px; box-shadow: 0 10px 15px -3px rgba(9, 170, 232, 0.2);">
										<i class="fas fa-external-link-alt"></i> ' . __('Open Cheque Preview') . '
									</a>

									<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '">
										<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
										<p style="color: #64748b; font-weight: 600; margin-bottom: 24px;">' . __('Has the cheque been printed correctly?') . '</p>' . $PaidInput . '
										<input type="hidden" name="BankTransRef" value="' . $_POST['BankTransRef'] . '" />
										<input type="hidden" name="ChequeNum" value="' . $_POST['ChequeNum'] . '" />
										<input type="hidden" name="CommitBatch" value="' . $_POST['CommitBatch'] . '" />
										<input type="hidden" name="BankAccount" value="' . $_POST['BankAccount'] . '" />
										<div style="display:flex; flex-direction: column; gap:12px;">
											<button type="submit" name="ChequePrinted" class="architect-btn" style="height: 56px; border-radius: 16px;">' . __('Yes, Continue') . '</button>
											<button type="submit" name="PaymentCancelled" class="architect-btn danger" style="height: 56px; border-radius: 16px;">' . __('No, Cancel Payment') . '</button>
										</div>
									</form>
								</div>
							</div>
						  </div>';
					include(__DIR__ . '/includes/footer.php');
					exit();
				}
			}
		} else {
			// Perform Actual Commitment
			DB_Txn_Begin();
			if ($_SESSION['PaymentDetail' . $identifier]->SupplierID == '') {
				$TransNo = GetNextTransNo(1);
				$TransType = 1;
				if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1) {
					$TotalAmount = 0;
					foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {
						if ($PaymentItem->Cheque == '') $PaymentItem->Cheque = 0;
						$SQL = "INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount, chequeno)
								VALUES (1, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', '" . $PeriodNo . "',
										'" . $PaymentItem->GLCode . "', '" . mb_substr($PaymentItem->Narrative, 0, 200) . "',
										'" . ($PaymentItem->Amount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "',
										'" . $PaymentItem->Cheque . "')";
						DB_query($SQL, '', '', true);
						InsertGLTags($PaymentItem->Tag);
						$TotalAmount += $PaymentItem->Amount;
					}
					$_SESSION['PaymentDetail' . $identifier]->Amount = $TotalAmount;
				}

				foreach ($_SESSION['PaymentDetail' . $identifier]->GLItems as $PaymentItem) {
					if (in_array($PaymentItem->GLCode, $BankAccounts)) {
						$SQL = "SELECT currcode, rate FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode = currencies.currabrev WHERE accountcode='" . $PaymentItem->GLCode . "'";
						$Row = DB_fetch_array(DB_query($SQL));
						$TrfToBankExRate = $Row['rate'];
						$ExRate = ($_SESSION['PaymentDetail' . $identifier]->ExRate * $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) / $Row['rate'];
						
						$ReceiptTransNo = GetNextTransNo(2);
						$SQL = "INSERT INTO banktrans (transno, type, bankact, ref, exrate, functionalexrate, transdate, banktranstype, amount, currcode)
								VALUES ('" . $ReceiptTransNo . "', 2, '" . $PaymentItem->GLCode . "', '" . '@' . $TransNo . ' ' . __('Act Transfer From') . ' ' . $_SESSION['PaymentDetail' . $identifier]->Account . "',
										'" . $ExRate . "', '" . $TrfToBankExRate . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "',
										'" . $_SESSION['PaymentDetail' . $identifier]->Paymenttype . "', '" . $PaymentItem->Amount . "', '" . $_SESSION['PaymentDetail' . $identifier]->Currency . "')";
						DB_query($SQL, '', '', true);
					}
				}
			} else {
				// Supplier Payment
				if (!isset($PaidArray)) {
					$PaidArray = array();
					foreach ($_POST as $Name => $Value) {
						if (substr($Name, 0, 4) == 'paid' AND filter_number_format($Value) > 0) {
							$PaidArray[substr($Name, 4)] = filter_number_format($Value);
						}
					}
				}
				$TransNo = GetNextTransNo(22);
				$TransType = 22;
				$CreditorTotal = (($_SESSION['PaymentDetail' . $identifier]->Discount + $_SESSION['PaymentDetail' . $identifier]->Amount) / $_SESSION['PaymentDetail' . $identifier]->ExRate) / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate;

				$SQL = "INSERT INTO supptrans (transno, type, supplierno, trandate, inputdate, suppreference, rate, ovamount, transtext, chequeno)
						VALUES ('" . $TransNo . "', 22, '" . $_SESSION['PaymentDetail' . $identifier]->SupplierID . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "',
								'" . date('Y-m-d H:i:s') . "', '" . $_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference . "',
								'" . ($_SESSION['PaymentDetail' . $identifier]->FunctionalExRate * $_SESSION['PaymentDetail' . $identifier]->ExRate) . "',
								'" . (-$_SESSION['PaymentDetail' . $identifier]->Amount - $_SESSION['PaymentDetail' . $identifier]->Discount) . "',
								'" . $_SESSION['PaymentDetail' . $identifier]->SuppTransTransText . "', '" . $_POST['ChequeNum'] . "')";
				DB_query($SQL, '', '', true);
				
				$PaymentID = DB_Last_Insert_ID('supptrans', 'id');
				foreach ($PaidArray as $PaidID => $PaidAmount) {
					DB_query("UPDATE supptrans SET alloc=alloc-" . $PaidAmount . " WHERE id='" . $PaymentID . "'", '', '', true);
					DB_query("UPDATE supptrans SET alloc=alloc+" . $PaidAmount . " WHERE id='" . $PaidID . "'", '', '', true);
					DB_query("INSERT INTO suppallocs (amt, datealloc, transid_allocfrom, transid_allocto) VALUES ('" . $PaidAmount . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', '" . $PaymentID . "', '" . $PaidID . "')", '', '', true);
				}

				DB_query("UPDATE suppliers SET lastpaiddate = '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', lastpaid='" . $_SESSION['PaymentDetail' . $identifier]->Amount . "' WHERE supplierid='" . $_SESSION['PaymentDetail' . $identifier]->SupplierID . "'", '', '', true);

				if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1) {
					DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount)
							VALUES (22, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', '" . $PeriodNo . "',
									'" . $_SESSION['CompanyRecord']['creditorsact'] . "', '" . mb_substr($_SESSION['PaymentDetail' . $identifier]->SupplierID . ' - ' . $_SESSION['PaymentDetail' . $identifier]->GLTransNarrative, 0, 200) . "', '" . $CreditorTotal . "')", '', '', true);
					if ($_SESSION['PaymentDetail' . $identifier]->Discount != 0) {
						DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount)
								VALUES (22, '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', '" . $PeriodNo . "',
										'" . $_SESSION['CompanyRecord']['pytdiscountact'] . "', '" . mb_substr($_SESSION['PaymentDetail' . $identifier]->GLTransNarrative, 0, 200) . "',
										'" . (-$_SESSION['PaymentDetail' . $identifier]->Discount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "')", '', '', true);
					}
				}
			}

			if ($_SESSION['CompanyRecord']['gllink_creditors'] == 1 AND $_SESSION['PaymentDetail' . $identifier]->Amount != 0) {
				DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount)
						VALUES ('" . $TransType . "', '" . $TransNo . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "', '" . $PeriodNo . "',
								'" . $_SESSION['PaymentDetail' . $identifier]->Account . "', '" . mb_substr($_SESSION['PaymentDetail' . $identifier]->Narrative, 0, 200) . "',
								'" . (-$_SESSION['PaymentDetail' . $identifier]->Amount / $_SESSION['PaymentDetail' . $identifier]->ExRate / $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate) . "')", '', '', true);
				EnsureGLEntriesBalance($TransType, $TransNo);
			}

			DB_query("INSERT INTO banktrans (transno, type, bankact, ref, exrate, functionalexrate, transdate, banktranstype, amount, currcode, chequeno)
					VALUES ('" . $TransNo . "', '" . $TransType . "', '" . $_SESSION['PaymentDetail' . $identifier]->Account . "', '" . $_SESSION['PaymentDetail' . $identifier]->BankTransRef . "',
							'" . $_SESSION['PaymentDetail' . $identifier]->ExRate . "', '" . $_SESSION['PaymentDetail' . $identifier]->FunctionalExRate . "', '" . FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid) . "',
							'" . $_SESSION['PaymentDetail' . $identifier]->Paymenttype . "', '" . -$_SESSION['PaymentDetail' . $identifier]->Amount . "', '" . $_SESSION['PaymentDetail' . $identifier]->Currency . "', '" . $_POST['ChequeNum'] . "')", '', '', true);

			DB_Txn_Commit();

			// SUCCESS RENDERING (Clean outside tabs)
			$DisplayAmount = $_SESSION['PaymentDetail' . $identifier]->Amount;
			$DisplayCurrency = $_SESSION['PaymentDetail' . $identifier]->Currency;
			$DisplayDate = $_SESSION['PaymentDetail' . $identifier]->DatePaid;
			$DisplayPayee = __('General Ledger');
			if (!empty($_SESSION['PaymentDetail' . $identifier]->SupplierID)) {
				$SResult = DB_query("SELECT suppname FROM suppliers WHERE supplierid='" . $_SESSION['PaymentDetail' . $identifier]->SupplierID . "'");
				$SRow = DB_fetch_array($SResult);
				$DisplayPayee = $SRow['suppname'];
			}

			echo '<div id="success-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); z-index: 99999; display: flex; align-items: center; justify-content: center; padding: 24px;">
					<div style="max-width: 500px; width: 100%; animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
						<div style="text-align: center; padding: 48px; border-radius: 24px; background: #ffffff; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); position: relative; overflow: hidden;">
							<div style="width: 80px; height: 80px; background: #e0f2fe; color: #09aae8; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; box-shadow: 0 10px 15px -3px rgba(9, 170, 232, 0.2);">
								<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
							</div>
							<div style="font-size: 1.5rem; font-weight: 900; color: #066e96; margin-bottom: 12px; letter-spacing: -0.025em; line-height: 1.2; background: transparent; padding: 0; border: none;">' . __('Payment Successfully Recorded') . '</div>
							<div style="color: #64748b; font-size: 1rem; margin-bottom: 32px; font-weight: 500; line-height: 1.5;">' . __('The transaction has been processed and all ledger entries have been synchronized.') . '</div>
							
							<div style="background: #f8fafc; border-radius: 16px; padding: 24px; margin-bottom: 32px; text-align: left; border: 1px solid #e2e8f0;">
								<div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px;">
									<span style="color: #64748b; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">' . __('Reference') . '</span>
									<span style="font-weight: 800; color: #0f172a;">#' . $TransNo . '</span>
								</div>
								<div style="display: flex; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 12px;">
									<span style="color: #64748b; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">' . __('Payee') . '</span>
									<span style="font-weight: 700; color: #0f172a;">' . $DisplayPayee . '</span>
								</div>
								<div style="display: flex; justify-content: space-between; padding-top: 4px;">
									<span style="color: #066e96; font-weight: 800; font-size: 0.9rem; text-transform: uppercase;">' . __('Amount') . '</span>
									<span style="font-weight: 900; color: #09aae8; font-size: 1.3rem;">' . locale_number_format($DisplayAmount, 2) . ' <span style="font-size: 0.9rem; opacity: 0.8;">' . $DisplayCurrency . '</span></span>
								</div>
							</div>
							
							<div style="display: flex; gap: 16px;">
								<button type="button" onclick="window.close()" class="db-btn db-btn-primary" style="flex: 1; height: 50px; font-size: 1rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(9, 170, 232, 0.3);"><i class="fas fa-times" style="margin-right: 8px;"></i>' . __('Close') . '</button>
								<button type="button" onclick="document.getElementById(\'success-modal-overlay\').style.display=\'none\'" class="db-btn db-btn-secondary" style="flex: 1; height: 50px; font-size: 1rem; border-radius: 12px; background: #f1f5f9; color: #475569; border: none;">' . __('New Payment') . '</button>
							</div>
						</div>
					</div>
				</div>';

			unset($_SESSION['PaymentDetail' . $identifier]);
			$_SESSION['PaymentDetail' . $identifier] = new Payment;
			$_SESSION['PaymentDetail' . $identifier]->GLItemCounter = 1;
			// Do not exit, let the page render behind the modal
		}
	}
}

if (isset($_GET['Delete'])) {
	$_SESSION['PaymentDetail' . $identifier]->Remove_GLItem($_GET['Delete']);
} elseif (isset($_POST['Process']) AND !$BankAccountEmpty) {
	if (!empty($_POST['Cheque'])) {
		$ChequeNoResult = DB_query("SELECT transno FROM supptrans WHERE chequeno='" . $_POST['Cheque'] . "'");
	}
	if (!isset($_POST['Tag'])) $_POST['Tag'] = array();

	if (is_numeric($_POST['GLManualCode'])) {
		$Result = DB_query("SELECT accountname FROM chartmaster WHERE accountcode='" . $_POST['GLManualCode'] . "'");
		if (DB_num_rows($Result) == 0) {
			prnMsg(__('The manual GL code entered does not exist in the database'), 'warn');
		} elseif (isset($ChequeNoResult) AND DB_num_rows($ChequeNoResult) != 0 AND $_POST['Cheque'] != '') {
			prnMsg(__('The Cheque/Voucher number has already been used'), 'error');
		} else {
			$MyRow = DB_fetch_array($Result);
			$_SESSION['PaymentDetail' . $identifier]->add_to_glanalysis(filter_number_format($_POST['GLAmount']), $_POST['GLNarrative'], $_POST['GLManualCode'], $MyRow['accountname'], $_POST['Tag'], $_POST['Cheque']);
			unset($_POST['GLManualCode']);
		}
	} elseif (isset($ChequeNoResult) AND DB_num_rows($ChequeNoResult) != 0 AND $_POST['Cheque'] != '') {
		prnMsg(__('The cheque number has already been used'), 'error');
	} elseif ($_POST['GLCode'] == '') {
		prnMsg(__('No General Ledger code has been chosen'), 'warn');
	} else {
		$MyRow = DB_fetch_array(DB_query("SELECT accountname FROM chartmaster WHERE accountcode='" . $_POST['GLCode'] . "'"));
		$_SESSION['PaymentDetail' . $identifier]->add_to_glanalysis(filter_number_format($_POST['GLAmount']), $_POST['GLNarrative'], $_POST['GLCode'], $MyRow['accountname'], $_POST['Tag'], $_POST['Cheque']);
	}
}

if (isset($_POST['Cancel'])) {
	unset($_POST['GLAmount'], $_POST['GLNarrative'], $_POST['GLCode'], $_POST['AccountName']);
}

// ===== ALL PHP PROCESSING IS DONE ABOVE =====

// ===== ALL HTML OUTPUT STARTS BELOW =====

// Determine the active tab label for allocation/analysis
$allocationTabLabel = $_SESSION['PaymentDetail' . $identifier]->SupplierID ? __('2. Allocation') : __('2. Analysis');

// --- OUTER PAGE & FORM OPEN ---
echo '<div class="db-page">';

echo '<div class="pay-steps">
		<div class="pay-step-item">
			<div class="pay-step-dot">1</div>
			<div class="pay-step-label">' . __('Setup') . '</div>
		</div>
		<div class="pay-step-item">
			<div class="pay-step-dot">2</div>
			<div class="pay-step-label">' . __('Allocation') . '</div>
		</div>
		<div class="pay-step-item">
			<div class="pay-step-dot">3</div>
			<div class="pay-step-label">' . __('Review') . '</div>
		</div>
	  </div>';

echo '<div class="db-page-header">
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

	/* ---- Unified Page Layout ---- */
	.db-page {
		max-width: 1400px;
		margin: 0 auto;
		padding: 24px;
	}
	
	/* ---- Vertical Accordion Sections ---- */
	.pay-section {
		width: 100%;
		box-sizing: border-box;
		background: #ffffff;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		margin-bottom: 24px;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
		overflow: hidden;
	}
	.pay-section-header-banner {
		padding: 20px 24px;
		background: #f8fafc;
		border-bottom: 1px solid #e5e7eb;
		display: flex;
		align-items: center;
		justify-content: space-between;
		cursor: pointer;
		user-select: none;
		transition: background 0.2s ease;
	}
	.pay-section-header-banner:hover {
		background: #f1f5f9;
	}
	.pay-section-title {
		font-size: 1.15rem;
		font-weight: 800;
		color: #0f172a;
		display: flex;
		align-items: center;
		gap: 12px;
	}
	.pay-section-icon {
		width: 32px;
		height: 32px;
		border-radius: 8px;
		background: #e2e8f0;
		color: #475569;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 0.9rem;
		font-weight: 800;
		transition: all 0.3s ease;
	}
	.pay-section.active .pay-section-header-banner {
		background: #ffffff;
		border-bottom: 2px solid #09aae8;
	}
	.pay-section.active .pay-section-icon {
		background: #e0f2fe;
		color: #09aae8;
	}
	.pay-section-body {
		display: none;
		padding: 32px;
		animation: slideDown 0.3s ease-out forwards;
	}
	.pay-section.active .pay-section-body {
		display: block;
	}
	@keyframes slideDown {
		from { opacity: 0; transform: translateY(-10px); }
		to { opacity: 1; transform: translateY(0); }
	}

	/* ---- Session Summary Header ---- */
	.pay-summary-bar {
		position: sticky;
		top: 16px;
		z-index: 100;
		display: flex;
		align-items: center;
		justify-content: space-between;
		background: #066e96;
		color: #ffffff;
		padding: 16px 32px;
		border-radius: 12px;
		margin-bottom: 24px;
		box-shadow: 0 10px 15px -3px rgba(6, 78, 59, 0.2);
	}
	.pay-summary-item {
		display: flex;
		flex-direction: column;
	}
	.pay-summary-label {
		font-size: 0.65rem;
		text-transform: uppercase;
		font-weight: 800;
		letter-spacing: 0.05em;
		opacity: 0.7;
	}
	.pay-summary-value {
		font-size: 1.25rem;
		font-weight: 900;
	}

	/* ---- Professional Card Overrides ---- */
	.db-card { 
		border: none !important;
		background: transparent !important;
		box-shadow: none !important;
		margin-bottom: 0 !important;
		width: 100%;
		box-sizing: border-box;
	}
	.db-card-header {
		padding: 0 0 20px 0 !important;
		background: transparent !important;
		border: none !important;
	}
	.db-card-title {
		font-size: 1.1rem !important;
		color: #111827 !important;
		font-weight: 850 !important;
	}
	
	/* ---- Responsive Table Wrapper ---- */
	.db-table-wrapper {
		width: 100%;
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
		margin-bottom: 1.5rem;
		border-radius: 8px;
		border: 1px solid #f3f4f6;
	}
	.db-table {
		width: 100%;
		min-width: 800px; /* Force minimum width to enable horizontal scroll on mobile */
		border-collapse: collapse;
	}

	/* ---- Step Progress Indicator ---- */
	.pay-steps {
		display: flex;
		justify-content: space-between;
		margin-bottom: 32px;
		background: #fff;
		padding: 24px;
		border-radius: 16px;
		border: 1px solid #e5e7eb;
		position: relative;
	}
	.pay-step-item {
		display: flex;
		flex-direction: column;
		align-items: center;
		flex: 1;
		position: relative;
		z-index: 2;
	}
	.pay-step-dot {
		width: 32px;
		height: 32px;
		border-radius: 50%;
		background: #fff;
		border: 2px solid #e5e7eb;
		display: flex;
		align-items: center;
		justify-content: center;
		font-weight: 800;
		font-size: 0.85rem;
		color: #94a3b8;
		margin-bottom: 8px;
		transition: all 0.3s ease;
	}
	.pay-step-item.active .pay-step-dot {
		background: #09aae8;
		border-color: #09aae8;
		color: #fff;
		box-shadow: 0 0 0 4px rgba(9, 170, 232, 0.1);
	}
	.pay-step-label {
		font-size: 0.65rem;
		font-weight: 800;
		text-transform: uppercase;
		color: #94a3b8;
		letter-spacing: 0.05em;
		text-align: center;
	}
	.pay-step-item.active .pay-step-label { color: #09aae8; }

	.pay-steps::before {
		content: "";
		position: absolute;
		top: 40px;
		left: 10%;
		right: 10%;
		height: 2px;
		background: #f1f5f9;
		z-index: 1;
	}

    /* ---- Responsive UI ---- */
    @media (max-width: 768px) {
		.db-page { padding: 16px; }
		.pay-steps { display: none; } /* Hide steps on mobile for space */
        .pay-summary-bar { flex-direction: column; align-items: flex-start; gap: 12px; padding: 16px 20px; }
        .pay-tab-content { padding: 20px; }
        .pay-tabs-nav { 
			padding: 4px; 
			justify-content: flex-start; /* Ensure scroll works correctly */
		}
    }
</style>
<script>
function payToggleStep(stepId) {
	var step = document.getElementById(stepId);
	if (step) {
		if (step.classList.contains(\'active\')) {
			step.classList.remove(\'active\');
		} else {
			step.classList.add(\'active\');
		}
	}
}

function payNextStep(currentId, nextId) {
	if (currentId) {
		var curr = document.getElementById(currentId);
		if (curr) curr.classList.remove(\'active\');
	}
	if (nextId) {
		var next = document.getElementById(nextId);
		if (next) {
			next.classList.add(\'active\');
			next.scrollIntoView({ behavior: \'smooth\', block: \'start\' });
			setTimeout(function() {
				var focusField = next.querySelector("input:not([type=hidden]):not([readonly]), select, textarea");
				if (focusField) focusField.focus();
			}, 300);
		}
	}
}

function updateAllocationTotal() {
	var total = 0;
	document.querySelectorAll(".allocation-input").forEach(function(input) {
		total += parseFloat(input.value.replace(/[^-0-9.]/g, "")) || 0;
	});
	var ttlDisplay = document.getElementById("ttl");
	if (ttlDisplay) ttlDisplay.value = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
	
	updateRemaining();
}

function updateRemaining() {
	var amtInput = document.getElementById("Amount");
	var totalAlloc = parseFloat(document.getElementById("ttl")?.value?.replace(/[^-0-9.]/g, "")) || 0;
	var principal = parseFloat(amtInput?.value?.replace(/[^-0-9.]/g, "")) || 0;
	var remaining = principal - totalAlloc;
	
	var remDisplay = document.getElementById("remaining-alloc");
	if (remDisplay) {
		remDisplay.innerText = remaining.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
		remDisplay.style.color = Math.abs(remaining) < 0.01 ? "var(--success)" : "var(--danger)";
	}
}

function payFull(id, amount) {
	var input = document.getElementById(id);
	if (input) {
		input.value = amount;
		updateAllocationTotal();
	}
}

function updateFinalSummary() {
	var summaryBody = document.getElementById("final-summary-body");
	if (!summaryBody) return;
	summaryBody.innerHTML = "";
	
	var hasItems = false;
	// Check GL Items
	document.querySelectorAll(".gl-item-row").forEach(function(row) {
		hasItems = true;
		var cells = row.querySelectorAll("td");
		var amt = cells[1] ? cells[1].innerText.replace(/[^-0-9.]/g, "") : "0";
		summaryBody.innerHTML += "<tr><td>" + (cells[2] ? cells[2].innerText : "GL Item") + "</td><td class=\"text-right\">" + (parseFloat(amt) || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + "</td><td>GL Analysis</td></tr>";
	});
	
	// Check Allocations
	document.querySelectorAll(".allocation-input").forEach(function(input) {
		var val = parseFloat(input.value.replace(/[^-0-9.]/g, "")) || 0;
		if (val !== 0) {
			hasItems = true;
			var row = input.closest("tr");
			var refCell = row ? row.querySelector("td:nth-child(3)") : null;
			var ref = refCell ? refCell.innerText.split("\n")[0] : "Invoice";
			summaryBody.innerHTML += "<tr><td>" + ref + "</td><td class=\"text-right\">" + val.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + "</td><td>Invoice Allocation</td></tr>";
		}
	});
	
	if (!hasItems) {
		summaryBody.innerHTML = "<tr><td colspan=\"3\" class=\"text-center\" style=\"padding:20px; color:var(--text-muted);\">No allocations or GL lines added yet.</td></tr>";
	}
}

function payVerify(amountId, totalId) {
    var amtInput = document.getElementById(amountId);
    var ttlInput = document.getElementById(totalId);

    if (amtInput && ttlInput) {
        var amt = parseFloat(amtInput.value.replace(/[^-0-9.]/g, "")) || 0;
        var ttl = parseFloat(ttlInput.value.replace(/[^-0-9.]/g, "")) || 0;

        if (ttl !== 0 && Math.abs(amt - ttl) > 0.01) {
            if (!confirm("' . __('The principal amount does not match the total allocation. Proceed anyway?') . '")) {
                return false;
            }
        }
    }
	
	// Prevent double click
	var btn = document.querySelector("button[name=CommitBatch]");
	if (btn) {
		btn.disabled = true;
		btn.innerHTML = "<i class=\'fas fa-spinner fa-spin\'></i> Processing...";
		var form = btn.closest("form");
		
		// Add a hidden input to ensure CommitBatch is sent
		var hidden = document.createElement("input");
		hidden.type = "hidden";
		hidden.name = "CommitBatch";
		hidden.value = "1";
		form.appendChild(hidden);
		
		form.submit();
	}
    return true;
}

window.addEventListener("load", function() {
	updateAllocationTotal();
	updateFinalSummary();
});
</script>';

// --- SUMMARY BAR ---
echo '<div class="pay-summary-bar">
		<div class="pay-summary-item">
			<div class="pay-summary-label">' . __('Selected Provider') . '</div>
			<div class="pay-summary-value"><i class="fas fa-building" style="margin-right:8px; font-size:0.9em;"></i>' . (!empty($_SESSION['PaymentDetail' . $identifier]->SuppName) ? htmlspecialchars($_SESSION['PaymentDetail' . $identifier]->SuppName) : __('Generic Payment')) . '</div>
		</div>
		<div style="display: flex; gap: 40px;">
			<div class="pay-summary-item" style="text-align: right;">
				<div class="pay-summary-label">' . __('Transaction Date') . '</div>
				<div class="pay-summary-value"><i class="fas fa-calendar-alt" style="margin-right:8px; font-size:0.9em;"></i>' . ($_SESSION['PaymentDetail' . $identifier]->DatePaid ? $_SESSION['PaymentDetail' . $identifier]->DatePaid : date($_SESSION['DefaultDateFormat'])) . '</div>
			</div>
			<div class="pay-summary-item" style="text-align: right;">
				<div class="pay-summary-label">' . __('Total Amount') . '</div>
				<div class="pay-summary-value" style="color: #34d399;">' . $_SESSION['PaymentDetail' . $identifier]->Currency . ' ' . locale_number_format($_SESSION['PaymentDetail' . $identifier]->Amount, $_SESSION['PaymentDetail' . $identifier]->CurrDecimalPlaces) . '</div>
			</div>
		</div>
	</div>';

// ==========================================
// SECTION 1: SOURCE & BANK SETTINGS
// ==========================================
$step1Active = empty($_SESSION['PaymentDetail' . $identifier]->Account) ? 'active' : '';
echo '<div id="pay-section-header" class="pay-section ' . $step1Active . '">
	<div class="pay-section-header-banner" onclick="payToggleStep(\'pay-section-header\')">
		<div class="pay-section-title"><div class="pay-section-icon">1</div> ' . __('Setup Payment Header') . '</div>
		<i class="fas fa-chevron-down" style="color: #94a3b8;"></i>
	</div>
	<div class="pay-section-body">
	<div class="db-card">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-university"></i> ' . __('Bank & Header Settings') . '</div>
		</div>
		<div class="db-card-body">
			<div style="display: flex; flex-direction: column; gap: var(--space-5);">';



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

$CurrBalanceRow = array('balance' => 0);
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
		$_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference = ($_SESSION['PaymentDetail' . $identifier]->Paymenttype ?? ''); // If blank, it uses the payment type.

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

/* Processing logic moved to top of file */

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

$CurrBalanceRow = array('balance' => 0);
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

	$BalanceSQL = "SELECT SUM(amount) AS balance FROM banktrans WHERE bankact='" . $_POST['BankAccount'] . "'";
	$BalanceResult = DB_query($BalanceSQL);
	$CurrBalanceRow = DB_fetch_array($BalanceResult);
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
		<select class="db-form-select" name="BankAccount" id="BankAccount" onchange="ReloadForm(UpdateHeader)">';

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
		echo '<div style="margin-top: 8px; font-size: 0.8rem; color: var(--success); font-weight: 700;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="margin-right: 4px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>' . __('Current Balance') . ': ' . locale_number_format(($CurrBalanceRow['balance'] ?? 0), $_SESSION['CompanyRecord']['decimalplaces']) . '</div>';
	}
	echo '</div>';
}

echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
		<div class="db-form-group">
			<label class="db-form-label">', __('Date of Payment') , '</label>
			<input class="db-form-input" type="date" name="DatePaid" value="', FormatDateForSQL($_SESSION['PaymentDetail' . $identifier]->DatePaid), '" />
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
		echo '<select class="db-form-select" name="Currency" onchange="ReloadForm(UpdateHeader)">';
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
				<input class="db-form-input number" style="width: 140px;" name="FunctionalExRate" type="text" value="', $_POST['FunctionalExRate'], '" />
				<span style="font-size: 0.75rem; color: var(--text-muted); background: var(--surface-alt); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border-soft);">' . __('Suggested') . ': ' . (isset($SuggestedFunctionalExRate) ? locale_number_format($SuggestedFunctionalExRate, 'Variable') : '1') . '</span>
			</div>
		</div>';
}

	echo '<div style="margin-top: var(--space-6); padding-top: var(--space-6); border-top: 2px dashed #e5e7eb;">
			<div class="db-card-title" style="margin-bottom: var(--space-4); color: var(--primary);"><i class="fas fa-file-invoice-dollar"></i> ' . __('Execution & Audit Details') . '</div>
		  </div>';

if (!isset($_POST['BankTransRef'])) {
	$_POST['BankTransRef'] = (isset($_SESSION['PaymentDetail' . $identifier]->BankTransRef)) ? $_SESSION['PaymentDetail' . $identifier]->BankTransRef : '';
}
if (!isset($_POST['Narrative'])) {
	$_POST['Narrative'] = (isset($_SESSION['PaymentDetail' . $identifier]->Narrative)) ? $_SESSION['PaymentDetail' . $identifier]->Narrative : '';
}
if (!isset($_POST['gltrans_narrative'])) {
	$_POST['gltrans_narrative'] = (isset($_SESSION['PaymentDetail' . $identifier]->GLTransNarrative)) ? $_SESSION['PaymentDetail' . $identifier]->GLTransNarrative : '';
}
if (!isset($_POST['supptrans_suppreference'])) {
	$_POST['supptrans_suppreference'] = (isset($_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference)) ? $_SESSION['PaymentDetail' . $identifier]->SuppTransSuppReference : '';
}
if (!isset($_POST['supptrans_transtext'])) {
	$_POST['supptrans_transtext'] = (isset($_SESSION['PaymentDetail' . $identifier]->SuppTransTransText)) ? $_SESSION['PaymentDetail' . $identifier]->SuppTransTransText : '';
}
if (!isset($_POST['Amount'])) {
	$_POST['Amount'] = (isset($_SESSION['PaymentDetail' . $identifier]->Amount)) ? $_SESSION['PaymentDetail' . $identifier]->Amount : 0;
}
if (!isset($_POST['Discount'])) {
	$_POST['Discount'] = (isset($_SESSION['PaymentDetail' . $identifier]->Discount)) ? $_SESSION['PaymentDetail' . $identifier]->Discount : 0;
}
if (!isset($_POST['Currency'])) {
	$_POST['Currency'] = (isset($_SESSION['PaymentDetail' . $identifier]->Currency)) ? $_SESSION['PaymentDetail' . $identifier]->Currency : '';
}
if (!isset($_POST['Paymenttype'])) {
	$_POST['Paymenttype'] = (isset($_SESSION['PaymentDetail' . $identifier]->Paymenttype)) ? $_SESSION['PaymentDetail' . $identifier]->Paymenttype : '';
}
echo '<div style="display: flex; flex-direction: column; gap: var(--space-5);">';

echo '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
		<div class="db-form-group">
			<label class="db-form-label">' . __('Payment Method') . '</label>
			<select class="db-form-select" name="Paymenttype">';
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
			' . __('Save & Update Header') . '
		</button>
		<button type="button" onclick="payNextStep(\'pay-section-header\', \'pay-section-allocation\')" class="db-btn db-btn-secondary" style="height: 48px; padding: 0 32px; font-weight: 800;">
			' . __('Continue to Allocation') . ' <i class="fas fa-arrow-down" style="margin-left: 10px;"></i>
		</button>
	</div>';
	echo '</div></div></div></div></div></div>'; // end inner-flex, inner-div, card-body, db-card, pay-section-body, pay-section-header



echo '<!-- SECTION 2: ANALYSIS & ALLOCATION -->
	<div id="pay-section-allocation" class="pay-section">
		<div class="pay-section-header-banner" onclick="payToggleStep(\'pay-section-allocation\')">
			<div class="pay-section-title"><div class="pay-section-icon">2</div> ' . $allocationTabLabel . '</div>
			<i class="fas fa-chevron-down" style="color: #94a3b8;"></i>
		</div>
		<div class="pay-section-body">';

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
			<input class="db-form-input number val-bold" type="text" name="GLAmount" value="' . (isset($_POST['GLAmount']) ? $_POST['GLAmount'] : '0') . '" style="color:var(--primary); font-size: 1.15rem;" />
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
				' . __('Add to Analysis') . '
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
			echo '<tr class="gl-item-row">
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
			echo '<div class="card-footer-v2" style="padding: var(--space-5); text-align: center; background: var(--surface-alt); display: flex; justify-content: flex-end; gap: 12px;">
				<button type="button" onclick="payNextStep(\'pay-section-allocation\', \'pay-section-finalize\')" class="db-btn db-btn-secondary" style="height: 44px; padding: 0 32px; font-weight: 800;">' . __('Continue to Review') . ' <i class="fas fa-arrow-down" style="margin-left: 8px;"></i></button>
			</div></div></div></div>'; // end footer-v2, card, pay-section-body, pay-section
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
						<div style="display: flex; gap: 8px; justify-content: center;">
							<button type="button" class="db-btn db-btn-icon" onclick="payFull(' . $MyRow['id'] . ', ' . $MyRow['amount'] . ')" title="' . __('Pay Full') . '" style="color: var(--primary); background: var(--primary-soft);"><i class="fas fa-arrow-right"></i></button>
						</div>
					</td>
					<td class="text-right">
						<input type="text" class="db-form-input number allocation-input" oninput="updateAllocationTotal()" style="width: 140px; text-align: right; font-weight: 800; color: var(--primary);" id="' . $MyRow['id'] . '" name="paid' . $MyRow['id'] . '" value="' . $_POST['paid' . $MyRow['id']] . '" />
						<input type="hidden" name="remainamt' . $MyRow['id'] . '" value="' . $MyRow['amount'] . '" />
					</td>
				</tr>';
		$i++;
	}
	echo '</tbody></table></div><div class="db-card-footer">
			<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
				<div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 700;">' . __('Remaining to Allocate') . ': <span id="remaining-alloc" style="font-weight: 900; font-size: 1.1rem; margin-left: 8px;">0.00</span></div>
				<div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 700;">' . __('Total Allocated') . ': <input type="text" id="ttl" value="0" readonly style="width: 150px; text-align: right; border: none; background: transparent; font-weight: 900; color: var(--primary); font-size: 1.25rem;"></div>
			</div>
	</div>
	<div style="padding: var(--space-5); text-align: center; background: var(--surface-alt); border-top: 1px solid var(--border-soft); display: flex; justify-content: flex-end; border-radius: 0 0 12px 12px;">
		<button type="button" onclick="payNextStep(\'pay-section-allocation\', \'pay-section-finalize\')" class="db-btn db-btn-secondary" style="height: 44px; padding: 0 32px; font-weight: 800;">' . __('Continue to Review') . ' <i class="fas fa-arrow-down" style="margin-left: 8px;"></i></button>
	</div>
	</div></div></div></div>'; // end db-card, pay-section-body, pay-section
}

echo '<!-- SECTION 3: REVIEW & FINALIZE -->
	<div id="pay-section-finalize" class="pay-section">
		<div class="pay-section-header-banner" onclick="payToggleStep(\'pay-section-finalize\')">
			<div class="pay-section-title"><div class="pay-section-icon">3</div> ' . __('Review & Finish') . '</div>
			<i class="fas fa-chevron-down" style="color: #94a3b8;"></i>
		</div>
		<div class="pay-section-body">
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
						<input class="db-form-input" maxlength="200" name="supptrans_transtext" type="text" value="', stripslashes($_POST['supptrans_transtext']) , '" placeholder="' . __('Internal notes') , '" />
						<input name="SuppName" type="hidden" value="', $_SESSION['PaymentDetail' . $identifier]->SuppName, '" />
					</div>
				</div>

				<div style="margin-top: 32px; border-top: 2px solid var(--border-soft); padding-top: 24px;">
					<h3 style="font-size: 1rem; font-weight: 800; margin-bottom: 16px; color: var(--primary);"><i class="fas fa-list-check" style="margin-right:8px;"></i> ' . __('Final Remittance Summary') . '</h3>
					<div class="db-table-wrapper">
						<table class="db-table" style="min-width: 100%;">
							<thead>
								<tr>
									<th>' . __('Description / Invoice') . '</th>
									<th class="text-right">' . __('Amount') . '</th>
									<th>' . __('Type') . '</th>
								</tr>
							</thead>
							<tbody id="final-summary-body">
								<!-- Populated by JS -->
							</tbody>
						</table>
					</div>
				</div>
			</div> <!-- end card-body -->
			<div class="db-card-footer" style="padding: 32px; background: var(--surface-alt); border-top: 1px solid var(--border-soft);">
				<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
					<div style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.4;">
						<i class="fas fa-info-circle" style="margin-right: 6px;"></i> ' . __('Please verify all allocations before finalizing.') . '<br>
						' . __('Once posted, these ledger entries cannot be edited directly.') . '
					</div>
					<button type="button" name="CommitBatch" onClick="payVerify(\'Amount\',\'ttl\')" class="db-btn db-btn-primary" style="height: 56px; padding: 0 40px; font-size: 1.15rem; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2);">
						<i class="fas fa-check-double" style="margin-right: 12px;"></i>
						' . __('Confirm & Post Payment') . '
					</button>
				</div>
			</div>
		</div> <!-- end db-card -->
		</div> <!-- end pay-section-body -->
	</div> <!-- end pay-section-finalize -->

</div> <!-- end db-page -->
</form>';

include(__DIR__ . '/includes/footer.php');
