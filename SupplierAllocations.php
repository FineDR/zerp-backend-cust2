<?php

/* This page handles the allocation of supplier payments or credit notes to invoices. */

include(__DIR__ . '/includes/DefineSuppAllocsClass.php');
require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Allocations');
$ViewTopic = 'ARTransactions';
$BookMark = 'SupplierAllocations';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- PROCESSING SECTION START ---
if (isset($_POST['UpdateDatabase']) OR isset($_POST['RefreshAllocTotal'])) {

	if (!isset($_SESSION['Alloc'])){
		prnMsg(__('Allocations can not be processed again. Please use the navigation links.'), 'warn');
		echo '<div class="centre" style="margin-top:20px;"><a href="' . $RootPath . '/SupplierInquiry.php" class="db-btn db-btn-primary">' . __('Back to Supplier Inquiry') . '</a></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$InputError = 0;
	$TotalAllocated = 0;
	$TotalDiffOnExch = 0;

	for ($AllocCounter=0; $AllocCounter < $_POST['TotalNumberOfAllocs']; $AllocCounter++){
		$_POST['Amt' . $AllocCounter] = filter_number_format($_POST['Amt' . $AllocCounter]);

		if (!is_numeric($_POST['Amt' . $AllocCounter])){
		      $_POST['Amt' . $AllocCounter] = 0;
		}
		if ($_POST['Amt' . $AllocCounter] < 0){
			prnMsg(__('The entry for the amount to allocate was negative.'), 'error');
			$_POST['Amt' . $AllocCounter] = 0;
		}

		if (isset($_POST['All' . $AllocCounter]) AND $_POST['All' . $AllocCounter] == true){
			$_POST['Amt' . $AllocCounter] = $_POST['YetToAlloc' . $AllocCounter];
		}

		if ($_POST['Amt' . $AllocCounter] > $_POST['YetToAlloc' . $AllocCounter]){
		     $_POST['Amt' . $AllocCounter] = $_POST['YetToAlloc' . $AllocCounter];
		}

		$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->AllocAmt = $_POST['Amt' . $AllocCounter];
		$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch = ($_POST['Amt' . $AllocCounter] / $_SESSION['Alloc']->TransExRate) - ($_POST['Amt' . $AllocCounter] / $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->ExRate);

		$TotalDiffOnExch += $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch;
		$TotalAllocated += round($_POST['Amt' . $AllocCounter],$_SESSION['Alloc']->CurrDecimalPlaces);
	}

	if ($TotalAllocated + $_SESSION['Alloc']->TransAmt > CurrencyTolerance($_SESSION['Alloc']->Currency)){
		prnMsg(__('The amount allocated is more than the amount of the transaction.'), 'error');
		$InputError = 1;
	}
}

if (isset($_POST['UpdateDatabase']) AND $InputError == 0){
	DB_Txn_Begin();

	foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
		if ($AllocnItem->OrigAlloc > 0 AND ($AllocnItem->OrigAlloc != $AllocnItem->AllocAmt)){
			$SQL = "DELETE FROM suppallocs WHERE id = '" . $AllocnItem->PrevAllocRecordID . "'";
			DB_query($SQL, '', '', true);
		}

		if ($AllocnItem->OrigAlloc != $AllocnItem->AllocAmt){
			if ($AllocnItem->AllocAmt > 0){
				$SQL = "INSERT INTO suppallocs (datealloc, amt, transid_allocfrom, transid_allocto)
						VALUES ('" . FormatDateForSQL(date($_SESSION['DefaultDateFormat'])) . "',
								'" . $AllocnItem->AllocAmt . "',
								'" . $_SESSION['Alloc']->AllocTrans . "',
								'" . $AllocnItem->ID . "')";
				DB_query($SQL, '', '', true);
			}
			$NewAllocTotal = $AllocnItem->PrevAlloc + $AllocnItem->AllocAmt;
			$Settled = (abs($NewAllocTotal - $AllocnItem->TransAmount) < CurrencyTolerance($_SESSION['Alloc']->Currency)) ? 1 : 0;

			$SQL = "UPDATE supptrans SET diffonexch='" . $AllocnItem->DiffOnExch . "',
										alloc = '" .  $NewAllocTotal . "',
										settled = '" . $Settled . "'
					WHERE id = '" . $AllocnItem->ID . "'";
			DB_query($SQL, '', '', true);
		}
	}

	$Settled = (abs($TotalAllocated + $_SESSION['Alloc']->TransAmt) < CurrencyTolerance($_SESSION['Alloc']->Currency)) ? 1 : 0;
	$SQL = "UPDATE supptrans SET alloc = '" .  -$TotalAllocated . "',
								diffonexch = '" . -$TotalDiffOnExch . "',
								settled='" . $Settled . "'
			WHERE id = '" . $_SESSION['Alloc']->AllocTrans . "'";
	DB_query($SQL, '', '', true);

	$MovtInDiffOnExch = $_SESSION['Alloc']->PrevDiffOnExch + $TotalDiffOnExch;
	if ($MovtInDiffOnExch != 0 AND $_SESSION['CompanyRecord']['gllink_creditors'] == 1){
		$PeriodNo = GetPeriod($_SESSION['Alloc']->TransDate);
		$SQLTransDate = FormatDateForSQL($_SESSION['Alloc']->TransDate);

		$SQL = "INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount)
				VALUES ('" . $_SESSION['Alloc']->TransType . "',
						'" . $_SESSION['Alloc']->TransNo . "',
						'" . $SQLTransDate . "',
						'" . $PeriodNo . "',
						'" . $_SESSION['CompanyRecord']['purchasesexchangediffact'] . "',
						'". __('Purchase Exchange difference') . "',
						'" . $MovtInDiffOnExch . "')";
		DB_query($SQL, '', '', true);

		$SQL = "INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount)
				VALUES ('" . $_SESSION['Alloc']->TransType . "',
						'" . $_SESSION['Alloc']->TransNo . "',
						'" . $SQLTransDate . "',
						'" . $PeriodNo . "',
						'" . $_SESSION['CompanyRecord']['creditorsact'] . "',
						'" . __('Purchase Exchange difference') . "',
						'" . -$MovtInDiffOnExch . "')";
		DB_query($SQL, '', '', true);
	}

	DB_Txn_Commit();
	prnMsg(__('Allocations processed successfully'), 'success');
	
	$SuppID = $_SESSION['Alloc']->SupplierID;
	unset($_SESSION['Alloc']);
	unset($_SESSION['AllocTrans']);
	
	echo '<div class="centre" style="margin-top:20px;">
			<a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . $SuppID . '" class="db-btn db-btn-primary">' . __('Back to Supplier Inquiry') . '</a>
		  </div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}
// --- PROCESSING SECTION END ---

// --- INITIALIZATION SECTION START ---
if (isset($_GET['AllocTrans'])){
	$_SESSION['Alloc'] = new Allocation;
	$_SESSION['AllocTrans'] = $_GET['AllocTrans'];

	$SQL= "SELECT systypes.typename, supptrans.type, supptrans.transno, supptrans.trandate, supptrans.supplierno,
				  suppliers.suppname, supptrans.rate, (supptrans.ovamount+supptrans.ovgst) AS total,
				  supptrans.diffonexch, supptrans.alloc, currencies.decimalplaces, currencies.currabrev
		    FROM supptrans INNER JOIN systypes ON supptrans.type = systypes.typeid
			INNER JOIN suppliers ON supptrans.supplierno = suppliers.supplierid
			INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
		    WHERE supptrans.id='" . DB_escape_string($_SESSION['AllocTrans']) . "'";

	$Result = DB_query($SQL);
	if (DB_num_rows($Result) != 1){
		prnMsg(__('Transaction not found.'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$MyRow = DB_fetch_array($Result);
	$_SESSION['Alloc']->AllocTrans = $_SESSION['AllocTrans'];
	$_SESSION['Alloc']->SupplierID = $MyRow['supplierno'];
	$_SESSION['Alloc']->SuppName = $MyRow['suppname'];
	$_SESSION['Alloc']->TransType = $MyRow['type'];
	$_SESSION['Alloc']->TransTypeName = __($MyRow['typename']);
	$_SESSION['Alloc']->TransNo = $MyRow['transno'];
	$_SESSION['Alloc']->TransExRate = $MyRow['rate'];
	$_SESSION['Alloc']->TransAmt = $MyRow['total'];
	$_SESSION['Alloc']->PrevDiffOnExch = $MyRow['diffonexch'];
	$_SESSION['Alloc']->TransDate = ConvertSQLDate($MyRow['trandate']);
	$_SESSION['Alloc']->CurrDecimalPlaces = $MyRow['decimalplaces'];
	$_SESSION['Alloc']->Currency = $MyRow['currabrev'];

	// Fetch potential allocations
	$SQL= "SELECT supptrans.id, typename, transno, trandate, suppreference, rate, ovamount+ovgst AS total, diffonexch, alloc
			FROM supptrans INNER JOIN systypes ON supptrans.type = systypes.typeid
			WHERE supptrans.settled=0
			AND abs(ovamount+ovgst-alloc) > " . CurrencyTolerance($_SESSION['Alloc']->Currency) . "
			AND supplierno='" . $_SESSION['Alloc']->SupplierID . "'";
	$Result = DB_query($SQL);
	while ($MyRow=DB_fetch_array($Result)){
		$_SESSION['Alloc']->add_to_AllocsAllocn($MyRow['id'], __($MyRow['typename']), $MyRow['transno'], ConvertSQLDate($MyRow['trandate']), $MyRow['suppreference'], 0, $MyRow['total'], $MyRow['rate'], $MyRow['diffonexch'], $MyRow['diffonexch'], $MyRow['alloc'], 'NA');
	}

	// Fetch existing allocations
	$SQL = "SELECT supptrans.id, typename, transno, trandate, suppreference, rate, ovamount+ovgst AS total, diffonexch,
				   supptrans.alloc-suppallocs.amt AS prevallocs, amt, suppallocs.id AS allocid
			FROM supptrans INNER JOIN systypes ON supptrans.type = systypes.typeid
			INNER JOIN suppallocs ON supptrans.id=suppallocs.transid_allocto
			WHERE suppallocs.transid_allocfrom='" . $_SESSION['AllocTrans'] . "' AND supplierno='" . $_SESSION['Alloc']->SupplierID . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)){
		$DiffOnExchThisOne = ($MyRow['amt']/$MyRow['rate']) - ($MyRow['amt']/$_SESSION['Alloc']->TransExRate);
		$_SESSION['Alloc']->add_to_AllocsAllocn($MyRow['id'], __($MyRow['typename']), $MyRow['transno'], ConvertSQLDate($MyRow['trandate']), $MyRow['suppreference'], $MyRow['amt'], $MyRow['total'], $MyRow['rate'], $DiffOnExchThisOne, ($MyRow['diffonexch'] - $DiffOnExchThisOne), $MyRow['prevallocs'], $MyRow['allocid']);
	}
}
// --- INITIALIZATION SECTION END ---

echo '<style>
	#Header_SubBreadcrumb { display: none !important; }
	.db-page { height: calc(100vh - 60px); display: flex; flex-direction: column; overflow: hidden; background: var(--bg-main); }
	.db-workspace { flex: 1; overflow-y: auto; padding: var(--space-6); background: var(--bg-main); }
	.alloc-summary-card { background: var(--surface); border: 1px solid var(--border-soft); border-radius: 16px; padding: var(--space-5); box-shadow: var(--shadow-sm); }
	.alloc-sidebar { min-width: 320px; max-width: 340px; display: flex; flex-direction: column; gap: var(--space-6); }
	.alloc-main { flex: 1; min-width: 0; }
</style>';

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-row">
				<div class="db-header-main">
					<h1 class="db-page-title">' . $Title . '</h1>
					<p class="db-page-subtitle">' . __('Managing allocations for') . ' <span style="color:var(--primary); font-weight: 700;">' . $_SESSION['Alloc']->SupplierID . ' — ' . $_SESSION['Alloc']->SuppName . '</span></p>
				</div>
				<div class="db-header-actions">
					<a href="' . $RootPath . '/SupplierInquiry.php?SupplierID=' . $_SESSION['Alloc']->SupplierID . '" class="db-btn db-btn-secondary">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
						' . __('Back to Inquiry') . '
					</a>
				</div>
			</div>
		</div>

		<div class="db-workspace">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div style="display: flex; gap: var(--space-6); align-items: flex-start;">
				
				<div class="alloc-main">
					<div class="card-v2">
						<div class="card-header-v2">
							<h3>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
								' . __('Outstanding Invoices') . '
							</h3>
						</div>
						<div class="db-table-wrapper">';

if (count($_SESSION['Alloc']->Allocs) == 0) {
	echo '				<div style="padding: var(--space-12); text-align: center; color: var(--text-muted);">
							<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom:16px; opacity:0.3;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
							<p style="font-weight: 600;">' . __('There are no outstanding transactions to allocate this to.') . '</p>
						</div>';
} else {
	echo '				<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Type') . '</th>
									<th>' . __('Trans #') . '</th>
									<th>' . __('Date') . '</th>
									<th>' . __('Reference') . '</th>
									<th class="number">' . __('Total') . '</th>
									<th class="number">' . __('Outstanding') . '</th>
									<th class="number" style="width: 180px;">' . __('Allocation') . '</th>
									<th class="text-center">' . __('All') . '</th>
								</tr>
							</thead>
							<tbody>';

	$AllocCounter = 0;
	$TotalAllocated = 0;
	foreach ($_SESSION['Alloc']->Allocs as $AllocnItm) {
		$YetToAlloc = round($AllocnItm->TransAmount - $AllocnItm->PrevAlloc, $_SESSION['Alloc']->CurrDecimalPlaces);
		
		echo '					<tr>
									<td style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted);">' . $AllocnItm->TransType . '</td>
									<td style="font-weight: 700; color: var(--primary);">#' . $AllocnItm->TypeNo . '</td>
									<td style="font-size: 0.8125rem;">' . $AllocnItm->TransDate . '</td>
									<td style="font-size: 0.8125rem; font-weight: 600;">' . $AllocnItm->SuppRef . '</td>
									<td class="number">' . locale_number_format($AllocnItm->TransAmount, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>
									<td class="number" style="color: var(--danger); font-weight: 700;">' . locale_number_format($YetToAlloc, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>
									<td class="number">
										<input type="hidden" name="AllocID' . $AllocCounter . '" value="' . $AllocnItm->ID . '" />
										<input type="hidden" name="YetToAlloc' . $AllocCounter . '" value="' . $YetToAlloc . '" />
										<input type="text" class="db-input number" style="height: 38px; text-align: right; font-weight: 700; background: var(--bg-main);" name="Amt' . $AllocCounter . '" value="' . $AllocnItm->AllocAmt . '" />
									</td>
									<td class="text-center">
										<input type="checkbox" name="All' . $AllocCounter . '" style="width: 20px; height: 20px; cursor: pointer;" />
									</td>
								</tr>';
		$TotalAllocated += $AllocnItm->AllocAmt;
		$AllocCounter++;
	}

	echo '					</tbody>
						</table>
						<input type="hidden" name="TotalNumberOfAllocs" value="' . $AllocCounter . '" />';
}

echo '					</div>
					</div>
				</div>

				<div class="alloc-sidebar">
					<div class="alloc-summary-card">
						<div style="font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">' . __('Transaction to Allocate') . '</div>
						<div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 4px;">' . $_SESSION['Alloc']->TransTypeName . ' #' . $_SESSION['Alloc']->TransNo . '</div>
						<div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 20px;">' . __('Dated') . ' ' . $_SESSION['Alloc']->TransDate . '</div>
						
						<div style="display: flex; flex-direction: column; gap: 12px; padding-top: 20px; border-top: 1px solid var(--border-soft);">
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">' . __('Total Amount') . ':</span>
								<span style="font-size: 1rem; font-weight: 800; color: var(--text-main);">' . locale_number_format(-$_SESSION['Alloc']->TransAmt, $_SESSION['Alloc']->CurrDecimalPlaces) . ' ' . $_SESSION['Alloc']->Currency . '</span>
							</div>
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">' . __('Total Allocated') . ':</span>
								<span style="font-size: 1.25rem; font-weight: 900; color: var(--primary);">' . locale_number_format($TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces) . '</span>
							</div>
							<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
								<span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">' . __('Left to Allocate') . ':</span>
								<span style="font-size: 1rem; font-weight: 800; color: var(--danger);">' . locale_number_format(-$_SESSION['Alloc']->TransAmt - $TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces) . '</span>
							</div>
						</div>

						<div style="margin-top: 32px; display: flex; flex-direction: column; gap: 12px;">
							<button type="submit" name="RefreshAllocTotal" class="db-btn db-btn-secondary" style="width: 100%; justify-content: center;">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
								' . __('Refresh Totals') . '
							</button>
							<button type="submit" name="UpdateDatabase" class="db-btn db-btn-primary" style="width: 100%; justify-content: center; height: 50px; font-size: 1rem;">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M20 6L9 17l-5-5"></path></svg>
								' . __('Process Allocations') . '
							</button>
						</div>
					</div>

					<div style="padding: var(--space-4); background: rgba(5, 150, 105, 0.05); border: 1px dashed var(--primary-soft); border-radius: 12px; font-size: 0.8rem; color: var(--primary); line-height: 1.5;">
						<div style="font-weight: 800; margin-bottom: 4px;">' . __('Pro Tip') . '</div>
						' . __('Use the "All" checkboxes to quickly allocate the full outstanding amount of an invoice.') . '
					</div>
				</div>

			</div>
			</form>
		</div>
	</div>';

include(__DIR__ . '/includes/footer.php');
?>
