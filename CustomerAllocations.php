<?php

/*	Call this page with:
	1. A TransID to show the make up and to modify existing allocations.
	2. A DebtorNo to show all outstanding receipts or credits yet to be allocated.
	3. No parameters to show all outstanding credits and receipts yet to be allocated.
*/

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCustAllocsClass.php');

require(__DIR__ . '/includes/session.php');

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-row">
				<div class="db-header-main">
					<h1 class="db-page-title">' . __('Customer Allocations') . '</h1>
					<p class="db-page-subtitle">' . __('Link receipts and credits to outstanding invoices') . '</p>
				</div>
				<div class="db-header-actions">';
if (isset($_SESSION['Alloc'])) {
	echo '			<span class="db-badge db-badge-success" style="padding: 8px 16px; font-weight: 800;">' . __('Allocating Transaction') . ' #' . $_SESSION['Alloc']->TransNo . '</span>';
}
echo '				</div>
			</div>
		</div>';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['Cancel'])) {
	unset($_POST['AllocTrans']);
}

if (isset($_POST['UpdateDatabase']) OR isset($_POST['RefreshAllocTotal'])) {

	if (!isset($_SESSION['Alloc'])) {
		prnMsg(
			__('Allocations can not be processed again') . '. ' .
			__('If you hit refresh on this page after having just processed an allocation') . ', ' .
			__('try to use the navigation links provided rather than the back button') . ', ' .
			__('to avoid this message in future'),
			'info'
		);
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$InputError = 0;
	$TotalAllocated = 0;
	$TotalDiffOnExch = 0;

	for ($AllocCounter = 0; $AllocCounter < $_POST['TotalNumberOfAllocs']; $AllocCounter++) {
		// loop through amounts allocated using AllocnItm->ID for each record

		if (isset($_POST['Amt' . $AllocCounter])) {

			// allocatable charge amounts
			if (!is_numeric(filter_number_format($_POST['Amt' . $AllocCounter]))) {
				$_POST['Amt' . $AllocCounter] = 0;
			}
			if (filter_number_format($_POST['Amt' . $AllocCounter]) < 0) {
				prnMsg(__('Amount entered was negative') . '. ' . __('Only positive amounts are allowed') . '.', 'warn');
				$_POST['Amt' . $AllocCounter] = 0;
			}
			if (isset($_POST['All' . $AllocCounter]) AND $_POST['All' . $AllocCounter] == true) {
				$_POST['Amt' . $AllocCounter] = $_POST['YetToAlloc' . $AllocCounter];
			}
			if (filter_number_format($_POST['Amt' . $AllocCounter]) > $_POST['YetToAlloc' . $AllocCounter]) {
				$_POST['Amt' . $AllocCounter] = locale_number_format($_POST['YetToAlloc' . $AllocCounter], $_SESSION['Alloc']->CurrDecimalPlaces);
				// Amount entered must be smaller than unallocated amount
			}

			$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->AllocAmt = filter_number_format($_POST['Amt' . $AllocCounter]);
			// recalcuate the new difference on exchange (a +positive amount is a gain -ve a loss)
			$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch =
				(filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->TransExRate) -
				(filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->ExRate);

			$TotalDiffOnExch += $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch;
			$TotalAllocated += filter_number_format($_POST['Amt' . $AllocCounter]);
		}

	}

	if ($TotalAllocated + $_SESSION['Alloc']->TransAmt > CurrencyTolerance($_SESSION['Alloc']->Currency)) {
		prnMsg(__('Allocation could not be processed because the amount allocated is more than the') . ' ' . $_SESSION['Alloc']->TransTypeName . ' ' . __('being allocated') . '<br />' . __('Total allocated') . ' = ' . $TotalAllocated . ' ' . __('and the total amount of the') . ' ' . $_SESSION['Alloc']->TransTypeName . ' ' . __('was') . ' ' . -$_SESSION['Alloc']->TransAmt, 'error');
		$InputError = 1;
	}
}

if (isset($_POST['UpdateDatabase'])) {
	if ($InputError == 0) {
		//
		//========[ START TRANSACTION ]===========
		//
		$Error = '';
		DB_Txn_Begin();
		$AllAllocations = 0;
		foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
			if ($AllocnItem->PrevAllocRecordID != 'NA') {
				// original allocation has changed so delete the old allocation record
				$SQL = "DELETE FROM custallocns WHERE id = '" . $AllocnItem->PrevAllocRecordID . "'";
				if (!$Result = DB_query($SQL)) {
					$Error = __('Could not delete old allocation record');
				}
			}

			if ($AllocnItem->AllocAmt > 0) {
				$SQL = "INSERT INTO
							custallocns (
							datealloc,
							amt,
							transid_allocfrom,
							transid_allocto
						) VALUES (
							CURRENT_DATE,
							'" . $AllocnItem->AllocAmt . "',
							'" . $_SESSION['Alloc']->AllocTrans . "',
							'" . $AllocnItem->ID . "'
						)";
				if (!$Result = DB_query($SQL)) {
					$Error = __('Could not change allocation record');
				}
			}
			$NewAllocTotal = $AllocnItem->PrevAlloc + $AllocnItem->AllocAmt;
			$AllAllocations = $AllAllocations + $AllocnItem->AllocAmt;
			$Settled = (abs($NewAllocTotal - $AllocnItem->TransAmount) < CurrencyTolerance($_SESSION['Alloc']->Currency)) ? 1 : 0;

			$SQL = "UPDATE debtortrans
					SET diffonexch='" . ($AllocnItem->DiffOnExch + $AllocnItem->PrevDiffOnExch) . "',
					alloc = '" . $NewAllocTotal . "',
					settled = '" . $Settled . "'
					WHERE id = '" . $AllocnItem->ID . "'";
			if (!$Result = DB_query($SQL)) {
				$Error = __('Could not update sales exchange difference');
			}
		}
		if (abs($TotalAllocated + $_SESSION['Alloc']->TransAmt) < CurrencyTolerance($_SESSION['Alloc']->Currency)) {
			$Settled = 1;
		} else {
			$Settled = 0;
		}
		// Update the receipt or credit note
		$SQL = "UPDATE debtortrans
				SET alloc = '" . -$AllAllocations . "',
				diffonexch = '" . -$TotalDiffOnExch . "',
				settled='" . $Settled . "'
				WHERE id = '" . $_POST['AllocTrans'] . "'";

		if (!$Result = DB_query($SQL)) {
			$Error = __('Could not update receipt or credit note');
		}

		// If GLLink to debtors active post diff on exchange to GL
		$MovtInDiffOnExch = -$_SESSION['Alloc']->PrevDiffOnExch - $TotalDiffOnExch;

		if ($MovtInDiffOnExch != 0) {
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
				$PeriodNo = GetPeriod($_SESSION['Alloc']->TransDate);
				$SQLTransDate = FormatDateForSQL($_SESSION['Alloc']->TransDate);

				$SQL = "INSERT INTO gltrans (
								type,
								typeno,
								trandate,
								periodno,
								account,
								narrative,
								amount
							) VALUES (
								'" . $_SESSION['Alloc']->TransType . "',
								'" . $_SESSION['Alloc']->TransNo . "',
								'" . $SQLTransDate . "',
								'" . $PeriodNo . "',
								'" . $_SESSION['CompanyRecord']['salesexchangediffact'] . "',
								'',
								'" . $MovtInDiffOnExch . "'
							)";
				if (!$Result = DB_query($SQL)) {
					$Error = __('Could not update sales exchange difference in General Ledger');
				}

				$SQL = "INSERT INTO gltrans (
							type,
							typeno,
							trandate,
							periodno,
							account,
							narrative,
							amount
		  				) VALUES (
							'" . $_SESSION['Alloc']->TransType . "',
							'" . $_SESSION['Alloc']->TransNo . "',
							'" . $_SESSION['Alloc']->TransDate . "',
							'" . $PeriodNo . "',
							'" . $_SESSION['CompanyRecord']['debtorsact'] . "',
							'',
							'" . -$MovtInDiffOnExch . "'
						)";
				if (!$Result = DB_query($SQL)) {
					$Error = __('Could not update debtors control in General Ledger');
				}
			}

		}

		//
		//========[ COMMIT TRANSACTION ]===========
		//
		if (empty($Error)) {
			DB_Txn_Commit();
		} else {
			DB_Txn_Rollback();
			prnMsg($Error, 'error');
		}
		unset($_SESSION['Alloc']);
		unset($_POST['AllocTrans']);
	}
}

if (isset($_GET['AllocTrans'])) {

	if (isset($_SESSION['Alloc'])) {
		unset($_SESSION['Alloc']->Allocs);
		unset($_SESSION['Alloc']);
	}

	$_SESSION['Alloc'] = new Allocation;
	$_POST['AllocTrans'] = $_GET['AllocTrans']; // Set AllocTrans when page first called

	$SQL = "SELECT systypes.typename,
				debtortrans.type,
				debtortrans.transno,
				debtortrans.trandate,
				debtortrans.debtorno,
				debtorsmaster.name,
				debtortrans.rate,
				(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) as total,
				debtortrans.diffonexch,
				debtortrans.alloc,
				currencies.decimalplaces,
				currencies.currabrev
			FROM debtortrans INNER JOIN systypes
			ON debtortrans.type = systypes.typeid
			INNER JOIN debtorsmaster
			ON debtortrans.debtorno = debtorsmaster.debtorno
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtortrans.id='" . $_POST['AllocTrans'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$_SESSION['Alloc']->AllocTrans = $_POST['AllocTrans'];
	$_SESSION['Alloc']->DebtorNo = $MyRow['debtorno'];
	$_SESSION['Alloc']->CustomerName = $MyRow['name'];
	$_SESSION['Alloc']->TransType = $MyRow['type'];
	$_SESSION['Alloc']->TransTypeName = __($MyRow['typename']);
	$_SESSION['Alloc']->TransNo = $MyRow['transno'];
	$_SESSION['Alloc']->TransExRate = $MyRow['rate'];
	$_SESSION['Alloc']->TransAmt = $MyRow['total'];
	$_SESSION['Alloc']->PrevDiffOnExch = $MyRow['diffonexch'];
	$_SESSION['Alloc']->TransDate = ConvertSQLDate($MyRow['trandate']);
	$_SESSION['Alloc']->CurrDecimalPlaces = $MyRow['decimalplaces'];
	$_SESSION['Alloc']->Currency = $MyRow['currabrev'];

	// First get transactions that have outstanding balances
	$SQL = "SELECT debtortrans.id,
					typename,
					transno,
					trandate,
					rate,
					ovamount+ovgst+ovfreight+ovdiscount as total,
					diffonexch,
					alloc
			FROM debtortrans INNER JOIN systypes
			ON debtortrans.type = systypes.typeid
			WHERE debtortrans.settled=0
			AND debtorno='" . $_SESSION['Alloc']->DebtorNo . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		$_SESSION['Alloc']->add_to_AllocsAllocn(
			$MyRow['id'],
			__($MyRow['typename']),
			$MyRow['transno'],
			ConvertSQLDate($MyRow['trandate']),
			0,
			$MyRow['total'],
			$MyRow['rate'],
			$MyRow['diffonexch'],
			$MyRow['diffonexch'],
			$MyRow['alloc'],
			'NA'
		);
	}
	DB_free_result($Result);

	// Get trans previously allocated to by this trans - this will overwrite incomplete allocations above
	$SQL = "SELECT debtortrans.id,
					typename,
					transno,
					trandate,
					rate,
					ovamount+ovgst+ovfreight+ovdiscount AS total,
					diffonexch,
					debtortrans.alloc-custallocns.amt AS prevallocs,
					amt,
					custallocns.id AS allocid
			FROM debtortrans INNER JOIN systypes
			ON debtortrans.type = systypes.typeid
			INNER JOIN custallocns
			ON debtortrans.id=custallocns.transid_allocto
			WHERE custallocns.transid_allocfrom='" . $_POST['AllocTrans'] . "'
			AND debtorno='" . $_SESSION['Alloc']->DebtorNo . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		$DiffOnExchThisOne = ($MyRow['amt'] / $MyRow['rate']) - ($MyRow['amt'] / $_SESSION['Alloc']->TransExRate);
		$_SESSION['Alloc']->add_to_AllocsAllocn(
			$MyRow['id'],
			__($MyRow['typename']),
			$MyRow['transno'],
			ConvertSQLDate($MyRow['trandate']),
			$MyRow['amt'],
			$MyRow['total'],
			$MyRow['rate'],
			$DiffOnExchThisOne,
			($MyRow['diffonexch'] - $DiffOnExchThisOne),
			$MyRow['prevallocs'],
			$MyRow['allocid']
		);
	}
	DB_free_result($Result);
}


/* Header already handled at top */

$TableHeader = '<thead>
					<tr>
						<th>' . __('Type') . '</th>
						<th>' . __('Customer') . '</th>
						<th>' . __('Code') . '</th>
						<th>' . __('No') . '</th>
						<th>' . __('Date') . '</th>
						<th class="number">' . __('Total') . '</th>
						<th class="number">' . __('To Alloc') . '</th>
						<th>' . __('Cur') . '</th>
						<th class="number">' . __('Action') . '</th>
					</tr>
				</thead>';

if (isset($_POST['AllocTrans'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="AllocTrans" value="' . $_POST['AllocTrans'] . '" />

		<div class="db-bottom-layout">
			<aside class="db-col-aside">';

	/* 1. Sidebar Context: Transaction Detail */
	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
					' . __('Allocating From') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div style="margin-bottom: var(--space-4);">
					<div style="font-weight: 800; color: var(--text-main); font-size: 1.1rem;">' . $_SESSION['Alloc']->CustomerName . '</div>
					<div style="font-size: 0.85rem; color: var(--text-muted);">' . $_SESSION['Alloc']->DebtorNo . '</div>
				</div>
				<div class="db-field-group">
					<div class="db-field">
						<label class="db-label">' . __('Transaction Type') . '</label>
						<div style="font-weight: 600;">' . $_SESSION['Alloc']->TransTypeName . ' #' . $_SESSION['Alloc']->TransNo . '</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Date') . '</label>
						<div>' . $_SESSION['Alloc']->TransDate . '</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Original Amount') . '</label>
						<div style="font-weight: 700; color: var(--text-main);">' . $_SESSION['Alloc']->Currency . ' ' . locale_number_format(-$_SESSION['Alloc']->TransAmt, $_SESSION['Alloc']->CurrDecimalPlaces) . '</div>
					</div>';
	if ($_SESSION['Alloc']->TransExRate != 1) {
		echo '		<div class="db-field">
						<label class="db-label">' . __('Exchange Rate') . '</label>
						<div>' . $_SESSION['Alloc']->TransExRate . '</div>
					</div>';
	}
	echo '		</div>
			</div>
		</div>';

	/* 2. Sidebar Progress: Totals */
	echo '<div class="card-v2" style="margin-top: var(--space-4); border-left: 3px solid var(--primary);">
			<div class="card-header-v2">
				<h3 style="font-size: 0.95rem;">' . __('Allocation Progress') . '</h3>
				<div class="db-header-actions">
					<button type="submit" name="RefreshAllocTotal" class="db-btn db-btn-secondary" style="padding: 4px; min-width: auto;" title="' . __('Refresh Totals') . '">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 4v6h-6M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
					</button>
				</div>
			</div>
			<div class="db-card-body">';

	$TotalAllocated = 0;
	foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
		$TotalAllocated += round($AllocnItem->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces);
	}

	echo '		<div class="db-field-group">
					<div class="db-field">
						<label class="db-label">' . __('Already Allocated') . '</label>
						<div style="font-weight: 700;">' . locale_number_format($TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces) . '</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Left to Allocate') . '</label>
						<div style="font-weight: 800; color: var(--primary); font-size: 1.25rem;">' . locale_number_format(-$_SESSION['Alloc']->TransAmt - $TotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces) . '</div>
					</div>
				</div>
				<div style="margin-top: var(--space-6); display: flex; flex-direction: column; gap: var(--space-3);">
					<button type="submit" name="UpdateDatabase" class="db-btn db-btn-success" style="width: 100%; font-weight: 700;">' . __('Process Allocations') . '</button>
					<button type="submit" name="Cancel" class="db-btn db-btn-danger" style="width: 100%;">' . __('Cancel') . '</button>
				</div>
			</div>
		</div>';

	echo '	</aside>
			<main class="db-col-main">';

	echo '<div class="card-v2">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
					' . __('Target Invoices for Allocation') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Type') . '</th>
								<th>' . __('No') . '</th>
								<th>' . __('Date') . '</th>
								<th class="number">' . __('Original Amount') . '</th>
								<th class="number">' . __('Yet to Alloc') . '</th>
								<th style="width: 180px;">' . __('Allocation') . '</th>
								<th class="number">' . __('Balance') . '</th>
							</tr>
						</thead>
						<tbody>';

	$Counter = 0;
	$TotalAllocated = 0;
	$Balance = 0;
	$j = 0;
	foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
		$YetToAlloc = ($AllocnItem->TransAmount - $AllocnItem->PrevAlloc);

		if ($AllocnItem->ID == $_POST['AllocTrans']) {
			$CurTrans = __('Being allocated');
		} elseif ($AllocnItem->AllocAmt > 0) {
		} else {
			$CurTrans = "&nbsp;";
		}

		echo '<tr class="striped_row">
			<td>' . __($AllocnItem->TransType) . '</td>
			<td class="number">' . $AllocnItem->TypeNo . '</td>
			<td>' . $AllocnItem->TransDate . '</td>
			<td class="number">' . locale_number_format($AllocnItem->TransAmount, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>
			<td class="number">' . locale_number_format($YetToAlloc, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>';
		$j++;

		if ($AllocnItem->TransAmount < 0) {
			$Balance += $YetToAlloc;
			echo '<td>' . $CurTrans . '</td>
						<td class="number">' . locale_number_format($Balance, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>
					</tr>';
		} else {
			echo '<td class="number"><input type="hidden" name="YetToAlloc' . $Counter . '" value="' . round($YetToAlloc, $_SESSION['Alloc']->CurrDecimalPlaces) . '" />';
			echo '<input tabindex="' . $j . '" type="checkbox" title="' . __('Check this box to allocate the entire amount of this transaction. Just enter the amount without ticking this check box for a partial allocation') . '" name="All' . $Counter . '"';// NewText: __('Check this box to allocate the entire amount of this transaction. Just enter the amount without ticking this check box for a partial allocation')

			if (ABS($AllocnItem->AllocAmt - $YetToAlloc) < CurrencyTolerance($_SESSION['Alloc']->Currency)) {
				echo ' checked="checked" />';
			} else {
				echo ' />';
			}
			$Balance += $YetToAlloc - $AllocnItem->AllocAmt;
			$j++;
			echo '<input tabindex="' . $j . '" type="text" class="number" ' . ($j == 1 ? 'autofocus="autofocus"' : '') . ' name="Amt' . $Counter . '" title="' . __('Enter the amount of this transaction to be allocated. Nothing should be entered here if the entire transaction is to be allocated, use the check box') . '" maxlength="12" size="13" value="' . locale_number_format(round($AllocnItem->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces), $_SESSION['Alloc']->CurrDecimalPlaces) . '" />
					<input type="hidden" name="AllocID' . $Counter . '" value="' . $AllocnItem->ID . '" ></td>
					<td class="number">' . locale_number_format($Balance, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>
				</tr>';
		}
		$TotalAllocated += round($AllocnItem->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces);
		$Counter++;
	}


	echo '		</tbody>
				</table>
				<input type="hidden" name="TotalNumberOfAllocs" value="' . $Counter . '" />
			</div>
		</div>
	</main>
</div>
</form>';

} elseif (isset($_GET['DebtorNo'])) {
	// Page called with customer code
	unset($_SESSION['Alloc']->Allocs);
	unset($_SESSION['Alloc']);

	$SQL = "SELECT debtortrans.id,
				debtortrans.transno,
				systypes.typename,
				debtortrans.type,
				debtortrans.debtorno,
				debtorsmaster.name,
				debtortrans.trandate,
				debtortrans.reference,
				debtortrans.rate,
				debtortrans.ovamount+debtortrans.ovgst+debtortrans.ovdiscount+debtortrans.ovfreight as total,
				debtortrans.alloc,
				currencies.decimalplaces AS currdecimalplaces,
				debtorsmaster.currcode
			FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN systypes
			ON debtortrans.type=systypes.typeid
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE debtortrans.debtorno='" . $_GET['DebtorNo'] . "'
			AND (debtortrans.type=12 OR debtortrans.type=11)
			AND debtortrans.settled=0";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0) {
		prnMsg(__('No outstanding receipts or credits to be allocated for this customer'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					' . __('Pending Allocations for Customer') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						' . $TableHeader . '
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td>' . __($MyRow['typename']) . '</td>
				<td>' . $MyRow['name'] . '</td>
				<td style="font-weight: 700;">' . $MyRow['debtorno'] . '</td>
				<td>' . $MyRow['transno'] . '</td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td class="number" style="font-weight: 600;">' . locale_number_format($MyRow['total'], $MyRow['currdecimalplaces']) . '</td>
				<td class="number" style="color: var(--primary); font-weight: 700;">' . locale_number_format($MyRow['total'] - $MyRow['alloc'], $MyRow['currdecimalplaces']) . '</td>
				<td>' . $MyRow['currcode'] . '</td>
				<td class="number">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?AllocTrans=' . $MyRow['id'] . '" class="db-btn db-btn-secondary" style="padding: 6px 14px; font-size: 0.85rem;">
						' . __('Allocate') . '
					</a>
				</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';
} else {
	/* Page called with no parameters */
	unset($_SESSION['Alloc']->Allocs);
	unset($_SESSION['Alloc']);

	$SQL = "SELECT debtortrans.id,
				debtortrans.transno,
				systypes.typename,
				debtortrans.type,
				debtortrans.debtorno,
				debtorsmaster.name,
				debtortrans.trandate,
				debtortrans.reference,
				debtortrans.rate,
				debtortrans.ovamount+debtortrans.ovgst+debtortrans.ovdiscount+debtortrans.ovfreight as total,
				debtortrans.alloc,
				debtorsmaster.currcode,
				currencies.decimalplaces AS currdecimalplaces
			FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN systypes
			ON debtortrans.type=systypes.typeid
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE (debtortrans.type=12 OR debtortrans.type=11)
			AND debtortrans.settled=0
			AND (debtortrans.ovamount<0 OR debtortrans.ovdiscount<0)";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtortrans.trandate, debtortrans.transno";

	$Result = DB_query($SQL);
	$NoOfUnallocatedTrans = DB_num_rows($Result);

	if ($NoOfUnallocatedTrans == 0) {
		prnMsg(__('There are no allocations to be done'), 'info');
	} else {
		$CurrentTransaction = 1;
		$CurrentDebtor = '';
		echo '<div class="card-v2" style="margin-top: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2v20M2 12h20"></path></svg>
					' . __('All Outstanding Receipts/Credits') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						' . $TableHeader . '
						<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {

			$AllocateLink = '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?AllocTrans=' . $MyRow['id'] . '" class="db-btn db-btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;">' . __('Allocate') . '</a>';

			if ($CurrentDebtor != $MyRow['debtorno']) {
				if ($CurrentTransaction > 1) {
					echo '<tr style="background: var(--surface-alt); font-weight: 700;">
						<td colspan="7" class="number">' . locale_number_format($Balance, $CurrDecimalPlaces) . '</td>
						<td>' . $CurrCode . '</td>
						<td class="number">' . __('Balance') . '</td>
					</tr>';
				}

				$Balance = 0;
				$CurrentDebtor = $MyRow['debtorno'];

				$BalSQL = "SELECT SUM(balance) as total
						FROM debtortrans
						WHERE (type=12 OR type=11)
						AND debtorno='" . $MyRow['debtorno'] . "'
						AND (ovamount<0 OR ovdiscount<0)";
				$BalResult = DB_query($BalSQL);
				$BalRow = DB_fetch_array($BalResult);
				$Balance = $BalRow['total'];
			}
			$CurrentTransaction++;
			$CurrCode = $MyRow['currcode'];
			$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
			if (isset($Balance) AND abs($Balance) < CurrencyTolerance($_SESSION['Alloc']->Currency)) {
				$AllocateLink = '&nbsp;';
			}

			echo '<tr>
				<td>' . __($MyRow['typename']) . '</td>
				<td>' . $MyRow['name'] . '</td>
				<td style="font-weight: 700;">' . $MyRow['debtorno'] . '</td>
				<td>' . $MyRow['transno'] . '</td>
				<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
				<td class="number" style="font-weight: 600;">' . locale_number_format($MyRow['total'], $CurrDecimalPlaces) . '</td>
				<td class="number" style="color: var(--primary); font-weight: 700;">' . locale_number_format($MyRow['total'] - $MyRow['alloc'], $CurrDecimalPlaces) . '</td>
				<td>' . $CurrCode . '</td>
				<td class="number">' . $AllocateLink . '</td>
			</tr>';

		} //end loop around unallocated receipts and credit notes

		if (!isset($Balance)) {
			$Balance = 0;
		}

		echo '<tr style="background: var(--surface-alt); font-weight: 700;">
					<td colspan="7" class="number">' . locale_number_format($Balance, $CurrDecimalPlaces) . '</td>
					<td>' . $CurrCode . '</td>
					<td class="number">' . __('Balance') . '</td>
				</tr>
			</tbody>
		</table>
	</div>
	</div>
	</div>';
	}

	echo '</div>'; // Close db-page
}
include(__DIR__ . '/includes/footer.php');
