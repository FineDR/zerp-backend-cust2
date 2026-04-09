<?php

/* Shows to which invoices a receipt was allocated to */

require(__DIR__ . '/includes/session.php');

$Title = __('How Allocated Inquiry');
$ViewTopic = 'ARInquiries';
$BookMark = 'WhereAllocated';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['TransNo']) AND isset($_GET['TransType'])) {
	$_POST['TransNo'] = (int)$_GET['TransNo'];
	$_POST['TransType'] = (int)$_GET['TransType'];
	$_POST['ShowResults'] = true;
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Track and analyze transaction settlement and allocation details') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/CustomerInquiry.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Inquiry') . '
				</a>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="card-v2 noPrint">
		<div class="card-header-v2">
			<h3>
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
				' . __('Search Transaction') . '
			</h3>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">
				<div class="db-field">
					<label class="db-label" for="TransType">' . __('Type') . '</label>
					<select tabindex="1" name="TransType">';

if (!isset($_POST['TransType'])) {
	$_POST['TransType'] = '10';
}
$Types = [
	'10' => __('Invoice'),
	'12' => __('Receipt'),
	'11' => __('Credit Note')
];
foreach ($Types as $TypeNo => $TypeName) {
	if ($_POST['TransType'] == $TypeNo) {
		echo '<option selected="selected" value="' . $TypeNo . '">' . $TypeName . '</option>';
	} else {
		echo '<option value="' . $TypeNo . '">' . $TypeName . '</option>';
	}
}

echo '				</select>
				</div>
				<div class="db-field">
					<label class="db-label" for="TransNo">' . __('Transaction Number') . '</label>
					<input tabindex="2" type="text" class="number" name="TransNo" required="required" maxlength="10" value="' . (isset($_POST['TransNo']) ? $_POST['TransNo'] : '') . '" placeholder="' . __('Enter Number...') . '" />
				</div>
			</div>
			<div class="form-footer-actions" style="margin-top:var(--space-6);">
				<button tabindex="3" type="submit" name="ShowResults" class="db-btn db-btn-primary">' . __('Show How Allocated') . '</button>
			</div>
		</div>
	</div>';

if (isset($_POST['ShowResults']) AND $_POST['TransNo'] == '') {
	prnMsg(__('The transaction number to be queried must be entered first'), 'warn');
}

if (isset($_POST['ShowResults']) AND $_POST['TransNo'] != '') {

	$SQL = "SELECT debtortrans.id,
				ovamount+ovgst AS totamt,
				currencies.decimalplaces AS currdecimalplaces,
				debtorsmaster.currcode,
				debtortrans.rate
			FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE type='" . $_POST['TransType'] . "'
			AND transno = '" . $_POST['TransNo'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$Result = DB_query($SQL);
	$GrandTotal = 0;
	$Rows = DB_num_rows($Result);
	if ($Rows >= 1) {
		while ($MyRow = DB_fetch_array($Result)) {
			$GrandTotal += $MyRow['totamt'];
			$Rate = $MyRow['rate'];
			$AllocToID = $MyRow['id'];
			$CurrCode = $MyRow['currcode'];
			$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
			
			$SQL = "SELECT type, transno, trandate, debtortrans.debtorno, reference, debtortrans.rate, ovamount+ovgst+ovfreight+ovdiscount as totalamt, custallocns.amt
					FROM debtortrans INNER JOIN custallocns ";
			
			if ($_POST['TransType'] == 12 OR $_POST['TransType'] == 11) {
				$TitleInfo = ($_POST['TransType'] == 12) ? __('Receipt') : __('Credit Note');
				if ($MyRow['totamt'] < 0) {
					$SQL .= "ON debtortrans.id = custallocns.transid_allocto WHERE custallocns.transid_allocfrom = '" . $AllocToID . "'";
				} else {
					$SQL .= "ON debtortrans.id = custallocns.transid_allocfrom WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
				}
			} else {
				$TitleInfo = __('invoice');
				$SQL .= "ON debtortrans.id = custallocns.transid_allocfrom WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
			}
			$SQL .= " ORDER BY transno ";

			$ErrMsg = __('The customer transactions for the selected criteria could not be retrieved because');
			$TransResult = DB_query($SQL, $ErrMsg);

			if (DB_num_rows($TransResult) == 0) {
				if ($MyRow['totamt'] < 0 AND ($_POST['TransType'] == 12 OR $_POST['TransType'] == 11)) {
					prnMsg(__('This transaction was a receipt of funds and there can be no allocations of receipts or credits to a receipt. This inquiry is meant to be used to see how a payment which is entered as a negative receipt is settled against credit notes or receipts'), 'info');
				} else {
					prnMsg(__('There are no allocations made against this transaction'), 'info');
				}
			} else {
				$Printer = true;
				echo '<div class="card-v2" style="margin-top:var(--space-6);">
						<div class="card-header-v2">
							<h3>' . __('Allocations Results') . '</h3>
							<span class="tag">' . __('Against') . ' ' . $TitleInfo . ' #' . $_POST['TransNo'] . '</span>
						</div>
						<div class="db-table-wrapper">
							<table class="db-table">
								<thead>
									<tr>
										<th>' . __('Date') . '</th>
										<th>' . __('Type') . '</th>
										<th>' . __('Number') . '</th>
										<th>' . __('Reference') . '</th>
										<th class="number">' . __('Ex Rate') . '</th>
										<th class="number">' . __('Amount') . '</th>
										<th class="number">' . __('Alloc') . '</th>
									</tr>
								</thead>
								<tbody>';

				$AllocsTotal = 0;
				while ($TransRow = DB_fetch_array($TransResult)) {
					$TransTypeName = ($TransRow['type'] == 11) ? __('Credit Note') : (($TransRow['type'] == 10) ? __('Invoice') : __('Receipt'));
					echo '<tr>
							<td>' . ConvertSQLDate($TransRow['trandate']) . '</td>
							<td>' . $TransTypeName . '</td>
							<td class="val-bold">' . $TransRow['transno'] . '</td>
							<td>' . $TransRow['reference'] . '</td>
							<td class="number">' . $TransRow['rate'] . '</td>
							<td class="number">' . locale_number_format($TransRow['totalamt'], $CurrDecimalPlaces) . '</td>
							<td class="number val-bold">' . locale_number_format($TransRow['amt'], $CurrDecimalPlaces) . '</td>
						</tr>';
					$AllocsTotal += $TransRow['amt'];
				}
				echo '			</tbody>
								<tfoot>
									<tr class="total_row">
										<td colspan="6" class="number val-bold">' . __('Total Allocated') . '</td>
										<td class="number val-bold">' . locale_number_format($AllocsTotal, $CurrDecimalPlaces) . ' ' . $CurrCode . '</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>';
			}
		}
	}
	if ($Rows > 1) {
		echo '<div class="card-v2" style="margin-top:var(--space-2); padding:var(--space-3); text-align:right;">
				<span class="val-bold">' . __('Transaction Total') . ': ' . locale_number_format($GrandTotal, $CurrDecimalPlaces) . ' ' . $CurrCode . '</span>
			</div>';
	}
	if ($_POST['TransType'] == 12) {
		$SQL = "SELECT account, amount FROM gltrans LEFT JOIN bankaccounts ON account=accountcode WHERE type=12 AND typeno='" . $_POST['TransNo'] . "' AND account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "' AND accountcode IS NULL";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) > 0) {
			echo '<div class="card-v2" style="margin-top:var(--space-6);">
					<div class="card-header-v2"><h3>' . __('Transaction Charges') . '</h3></div>
					<div class="db-card-body">';
			while ($ChargesRow = DB_fetch_array($Result)) {
				echo '<div style="display:flex; justify-content:space-between; margin-bottom:var(--space-2);">
						<span>' . __('GL Account') . ': ' . $ChargesRow['account'] . '</span>
						<span class="val-bold">' . locale_number_format($ChargesRow['amount'], $CurrDecimalPlaces) . ' ' . $CurrCode . ' (' . locale_number_format($ChargesRow['amount'] * $Rate, $CurrDecimalPlaces) . ' @ ' . $Rate . ')</span>
					</div>';
				$GrandTotal += $ChargesRow['amount'] * $Rate;
			}
			echo '		<div style="border-top:1px solid var(--border); margin-top:var(--space-3); padding-top:var(--space-3); text-align:right;">
							<span class="val-bold" style="font-size:1.1rem;">' . __('Grand Total') . ': ' . locale_number_format($GrandTotal, $CurrDecimalPlaces) . '</span>
						</div>
					</div>
				</div>';
		}
	}
}

echo '</form>';

if (isset($Printer)) {
	echo '<div class="centre noPrint" style="margin-top:var(--space-6);">
			<button class="db-btn db-btn-secondary" onclick="javascript:window.print()" type="button">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
				' . __('Print This Inquiry') . '
			</button>
		</div>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
