<?php

/*The supplier transaction uses the SuppTrans class to hold the information about the invoice or credit note
the SuppTrans class contains an array of GRNs objects - containing details of GRNs for invoicing/crediting and also
an array of GLCodes objects - only used if the AP - GL link is effective */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineSuppTransClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Transaction General Ledger Analysis');
$ViewTopic = 'AccountsPayable';
$BookMark = 'SuppTransGLAnalysis';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/GLFunctions.php');

echo '<div class="db-page">';
	echo '<style>
		.db-aside-btn {
			width: 100%;
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 10px 12px;
			border-radius: var(--radius-md);
			border: 1px solid transparent;
			background: transparent;
			color: var(--text-body);
			font-size: 0.875rem;
			font-weight: 500;
			cursor: pointer;
			transition: all var(--transition-fast);
			text-align: left;
		}
		.db-aside-btn:hover {
			background: var(--primary-soft);
			color: var(--primary);
			border-color: var(--primary-subtle);
		}
		.db-aside-btn i {
			width: 20px;
			text-align: center;
			color: var(--primary);
			font-size: 1rem;
		}
		.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
		.registry-table th { background: #064e3b; padding: 12px 15px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #fff; letter-spacing: 1px; }
		.registry-table td { padding: 12px 15px; font-size: 0.875rem; color: var(--text-body); border-bottom: 1px solid var(--border-soft); }
		.registry-table tr:nth-child(even) td { background: var(--bg-workspace); }
		.registry-table tr:hover td { background: var(--primary-soft) !important; }
		.db-field { margin-bottom: var(--space-4); }
		.db-label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
	</style>';

	echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . $Title . '</h2>
			<p class="db-page-subtitle">' . __('General Ledger Analysis for') . ' <span class="val-bold">' . $_SESSION['SuppTrans']->SupplierID . ' - ' . $_SESSION['SuppTrans']->SupplierName . '</span></p>
		</div>
		<div class="db-header-actions">';
	if ($_SESSION['SuppTrans']->InvoiceOrCredit == 'Invoice') {
		echo '<a href="' . $RootPath . '/SupplierInvoice.php' . (isset($_GET['identifier']) ? '?identifier=' . $_GET['identifier'] : '') . '" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"></path></svg>
				' . __('Back to Invoice') . '
			</a>';
	} else {
		echo '<a href="' . $RootPath . '/SupplierCredit.php' . (isset($_GET['identifier']) ? '?identifier=' . $_GET['identifier'] : '') . '" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"></path></svg>
				' . __('Back to Credit') . '
			</a>';
	}
	echo '</div>
	</div>';

if (!isset($_SESSION['SuppTrans'])) {
	prnMsg(__('To enter a supplier invoice or credit note the supplier must first be selected from the supplier selection screen') . ', ' . __('then the link to enter a supplier invoice or supplier credit note must be clicked on'), 'info');
	echo '<br /><a href="' . $RootPath . '/SelectSupplier.php">' . __('Select a supplier') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();
	/*It all stops here if there aint no supplier selected and transaction initiated ie $_SESSION['SuppTrans'] started off*/
}

/*If the user hit the Add to transaction button then process this first before showing  all GL codes on the transaction otherwise it wouldnt show the latest addition*/

if (isset($_POST['AddGLCodeToTrans']) and $_POST['AddGLCodeToTrans'] == __('Enter GL Line')) {

	$InputError = false;
	if ($_POST['GLCode'] == '') {
		$_POST['GLCode'] = $_POST['AcctSelection'];
	}

	if ($_POST['GLCode'] == '') {
		prnMsg(__('You must select a general ledger code from the list below'), 'warn');
		$InputError = true;
	}

	$SQL = "SELECT accountcode,
			accountname
		FROM chartmaster
		WHERE accountcode='" . $_POST['GLCode'] . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0 and $_POST['GLCode'] != '') {
		prnMsg(__('The account code entered is not a valid code') . '. ' . __('This line cannot be added to the transaction') . '.<br />' . __('You can use the selection box to select the account you want'), 'error');
		$InputError = true;
	} elseif ($_POST['GLCode'] != '') {
		$MyRow = DB_fetch_row($Result);
		$GLActName = $MyRow[1];
		if (!is_numeric(filter_number_format($_POST['Amount']))) {
			prnMsg(__('The amount entered is not numeric') . '. ' . __('This line cannot be added to the transaction'), 'error');
			$InputError = true;
		} elseif ($_POST['JobRef'] != '') {
			$SQL = "SELECT contractref FROM contracts WHERE contractref='" . $_POST['JobRef'] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 0) {
				prnMsg(__('The contract reference entered is not a valid contract, this line cannot be added to the transaction'), 'error');
				$InputError = true;
			}
		}
	}

	if ($InputError == false) {

		$_SESSION['SuppTrans']->Add_GLCodes_To_Trans($_POST['GLCode'], $GLActName, filter_number_format($_POST['Amount']), $_POST['Narrative'], $_POST['tag']);
		unset($_POST['GLCode']);
		unset($_POST['Amount']);
		unset($_POST['JobRef']);
		unset($_POST['Narrative']);
		unset($_POST['AcctSelection']);
		unset($_POST['Tag']);
	}
}

if (isset($_GET['Delete'])) {
	$_SESSION['SuppTrans']->Remove_GLCodes_From_Trans($_GET['Delete']);
}

if (isset($_GET['Edit'])) {
	$_POST['GLCode'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->GLCode;
	$_POST['AcctSelection'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->GLCode;
	$_POST['Amount'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Amount;
	$_POST['JobRef'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->JobRef;
	$_POST['Narrative'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Narrative;
	$_POST['Tag'] = $_SESSION['SuppTrans']->GLCodes[$_GET['Edit']]->Tag;
	$_SESSION['SuppTrans']->Remove_GLCodes_From_Trans($_GET['Edit']);
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="db-bottom-layout">';

	// --- SIDEBAR START ---
	echo '<aside class="db-col-aside">';
	
	// Card 1: Active Supplier
	echo '<div class="db-card" style="margin-bottom: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-user-tag db-icon-green"></i> ' . __('Supplier Context') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="font-size: 1.1rem; font-weight: 700; color: var(--db-primary);">' . $_SESSION['SuppTrans']->SupplierName . '</div>
				<div style="font-family: monospace; color: var(--text-muted); margin-bottom: var(--space-3);">[' . $_SESSION['SuppTrans']->SupplierID . ']</div>
				<div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
					<div><span class="db-muted">' . __('Currency') . ':</span> <span class="val-bold">' . $_SESSION['SuppTrans']->CurrCode . '</span></div>
				</div>
			</div>
		</div>';

	// Pre-calculate Summary (Reuse logic)
	$TaxTotal = 0;
	$currentOvAmount = 0;
	foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
		$currentOvAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
	}
	foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
		$currentOvAmount += $GLLine->Amount;
	}

	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		if ($Tax->TaxOnTax == 1) {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * ($currentOvAmount + $TaxTotal);
		} else {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * $currentOvAmount;
		}
		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount;
	}

	// Card 2: Live Summary
	echo '<div class="db-card" style="position: sticky; top: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Invoice Summary') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="display: flex; flex-direction: column; gap: var(--space-3);">
					<div style="display: flex; justify-content: space-between;">
						<span class="db-muted">' . __('Items Total') . ':</span>
						<span class="val-bold">' . locale_number_format($currentOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
					</div>';
	
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		echo '<div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
				<span class="db-muted">' . $Tax->TaxAuthDescription . ':</span>
				<span>' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
			  </div>';
	}
	
	echo '			<div style="margin: var(--space-2) 0; height: 1px; background: var(--border-soft);"></div>
					<div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: var(--db-primary);">
						<span class="val-bold">' . __('Grand Total') . ':</span>
						<span class="val-bold">' . locale_number_format($currentOvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' ' . $_SESSION['SuppTrans']->CurrCode . '</span>
					</div>
				</div>
				<div style="margin-top: var(--space-6);">';
	if ($_SESSION['SuppTrans']->InvoiceOrCredit == 'Invoice') {
		echo '<a href="' . $RootPath . '/SupplierInvoice.php' . (isset($_GET['identifier']) ? '?identifier=' . $_GET['identifier'] : '') . '" class="db-btn db-btn-primary" style="width: 100%; height: 44px; justify-content: center; font-size: 1rem;">
				<i class="fas fa-arrow-left"></i> ' . __('Back to Invoice') . '
			</a>';
	} else {
		echo '<a href="' . $RootPath . '/SupplierCredit.php' . (isset($_GET['identifier']) ? '?identifier=' . $_GET['identifier'] : '') . '" class="db-btn db-btn-primary" style="width: 100%; height: 44px; justify-content: center; font-size: 1rem;">
				<i class="fas fa-arrow-left"></i> ' . __('Back to Credit') . '
			</a>';
	}
	echo '		</div>
			</div>
		</div>';

	echo '</aside>';
	// --- SIDEBAR END ---

	// --- MAIN CONTENT START ---
	echo '<main class="db-col-main">';

	$SupplierCodeSQL = "SELECT defaultgl FROM suppliers WHERE supplierid='" . $_SESSION['SuppTrans']->SupplierID . "'";
	$SupplierCodeResult = DB_query($SupplierCodeSQL);
	$SupplierCodeRow = DB_fetch_row($SupplierCodeResult);

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Draft GL Analysis') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<table class="registry-table">
					<thead>
						<tr>
							<th>' . __('Account') . '</th>
							<th>' . __('Name') . '</th>
							<th>' . __('Amount') . '</th>
							<th>' . __('Narrative') . '</th>
							<th>' . __('Tag') . '</th>
							<th colspan="2">&nbsp;</th>
						</tr>
					</thead>
					<tbody>';

$TotalGLValue = 0;

foreach ($_SESSION['SuppTrans']->GLCodes AS $EnteredGLCode) {

	$DescriptionTag = GetDescriptionsFromTagArray($EnteredGLCode->Tag);

	echo '<tr>
			<td class="text">' . $EnteredGLCode->GLCode . '</td>
			<td class="text">' . $EnteredGLCode->GLActName . '</td>
			<td class="number">' . locale_number_format($EnteredGLCode->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
			<td class="text">' . $EnteredGLCode->Narrative . '</td>
			<td class="text">' . $DescriptionTag . '</td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit=' . $EnteredGLCode->Counter . '">' . __('Edit') . '</a></td>
			<td><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . $EnteredGLCode->Counter . '">' . __('Delete') . '</a></td>
		</tr>';

	$TotalGLValue+= $EnteredGLCode->Amount;
}

echo '</tbody>
					<tfoot>
						<tr style="background: var(--surface-alt); font-weight: 700;">
						<td colspan="2" class="text-right">' . __('Sub-Total Analysis') . ':</td>
						<td class="number" style="color: var(--db-primary);">' . locale_number_format($TotalGLValue, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
						<td colspan="4">&nbsp;</td>
					</tr>
					</tfoot>
				</table>
			</div>
		</div>';

/*Set up a form to allow input of new GL entries */
echo '<div class="db-card" style="margin-top: var(--space-6);">
		<div class="db-card-header">
			<h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Add GL Analysis Line') . '</h3>
		</div>
		<div class="db-card-body" style="padding: var(--space-6);">';

if (!isset($_POST['GLCode'])) {
	$_POST['GLCode'] = '';
}

echo '<div class="db-grid db-grid-2">';

// Column 1: Account Selection
echo '<div>';
$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname
		FROM chartmaster
		INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode 
		AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1
		ORDER BY chartmaster.accountcode";
$Result = DB_query($SQL);

if (!isset($_POST['AcctSelection']) or $_POST['AcctSelection'] == '') {
	$_POST['AcctSelection'] = $SupplierCodeRow[0];
}

echo '<div class="db-field">
		<label class="db-label">' . __('Account Selection') . '</label>
		<select name="AcctSelection" style="height: 40px;">
			<option value=""></option>';
while ($MyRow = DB_fetch_array($Result)) {
	$selected = ($MyRow['accountcode'] == $_POST['AcctSelection']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
}
echo '  </select>
		<p class="db-muted" style="font-size: 0.75rem; margin-top: 4px;">' . __('Or enter code below if known') . '</p>
	  </div>';

echo '<div class="db-field">
		<label class="db-label">' . __('Manual Account Code') . '</label>
		<input type="text" name="GLCode" placeholder="' . __('e.g. 1000') . '" maxlength="20" value="' . $_POST['GLCode'] . '" />
	  </div>';

// Amount
if (!isset($_POST['Amount'])) { $_POST['Amount'] = 0; }
echo '<div class="db-field">
		<label class="db-label">' . __('Line Amount') . ' (' . $_SESSION['SuppTrans']->CurrCode . ')</label>
		<input type="text" class="number" required="required" name="Amount" placeholder="' . __('0.00') . '" value="' . locale_number_format($_POST['Amount'], $_SESSION['SuppTrans']->CurrDecimalPlaces) . '" />
	  </div>';
echo '</div>';

// Column 2: Tags and Narrative
echo '<div>';
// Select the tag
$SQL = "SELECT tagref, tagdescription FROM tags ORDER BY tagref";
$Result = DB_query($SQL);
echo '<div class="db-field">
		<label class="db-label">' . __('Tags (Multiple Select)') . '</label>
		<select multiple="multiple" name="tag[]" style="height: 100px;">';
while ($MyRow = DB_fetch_array($Result)) {
	$selected = (isset($_POST['tag']) and in_array($MyRow['tagref'], $_POST['tag'])) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['tagref'] . '">' . $MyRow['tagref'] . ' - ' . $MyRow['tagdescription'] . '</option>';
}
echo '  </select>
	  </div>';

if (!isset($_POST['Narrative'])) { $_POST['Narrative'] = ''; }
echo '<div class="db-field">
		<label class="db-label">' . __('Narrative / Description') . '</label>
		<textarea name="Narrative" rows="3" placeholder="' . __('Optional line notes...') . '">' . $_POST['Narrative'] . '</textarea>
	  </div>';
echo '</div>';

echo '</div>'; // End .db-grid

echo '</div><!-- .db-card-body -->
	  <div class="db-card-footer" style="padding: var(--space-4); text-align: center; background: var(--surface-alt);">
		<input type="hidden" name="JobRef" value="" />
		<button type="submit" name="AddGLCodeToTrans" value="' . __('Enter GL Line') . '" class="db-btn db-btn-primary" style="min-width: 200px;">
			<i class="fas fa-plus-circle"></i> ' . __('Enter GL Line Details') . '
		</button>
	  </div>
	</div>';

echo '</main></div><!-- .db-bottom-layout -->';
echo '</form></div><!-- .db-page -->';
include(__DIR__ . '/includes/footer.php');
