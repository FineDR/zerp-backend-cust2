<?php

/* Suppliers Where allocated */

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier How Paid Inquiry');
$ViewTopic = 'APInquiries';
$BookMark = 'WhereAllocated';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['TransNo']) AND isset($_GET['TransType'])) {
	$_POST['TransNo'] = (int)$_GET['TransNo'];
	$_POST['TransType'] = (int)$_GET['TransType'];
	$_POST['ShowResults'] = true;
}

echo '<style>
    /* Super Modern ERP Search Bar Styles */
    :root {
        --search-bg: #ffffff;
        --search-border: #e2e8f0;
        --search-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    }
    .modern-page-header { text-align: center; margin-top: 2rem; margin-bottom: 2.5rem; }
    .modern-page-header h1 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; }
    .modern-page-header p { font-size: 1.05rem; color: #64748b; margin: 0 auto; max-width: 600px; }
    
    .modern-search-container { max-width: 850px; margin: 0 auto 3rem auto; background: var(--search-bg); border-radius: 16px; box-shadow: var(--search-shadow); border: 1px solid var(--search-border); padding: 1rem; display: flex; flex-direction: column; gap: 15px; transition: all 0.3s ease; }
    .modern-search-container:focus-within { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 0 0 3px var(--primary-soft); border-color: var(--primary); }
    @media (min-width: 768px) {
        .modern-search-container { flex-direction: row; align-items: center; padding: 0.75rem 0.75rem 0.75rem 1.5rem; border-radius: 50px; }
    }
    
    .modern-search-field { display: flex; flex-direction: column; flex: 1; position: relative; padding: 0.5rem; }
    @media (min-width: 768px) {
        .modern-search-field { border-right: 1px solid #e2e8f0; padding: 0 1.5rem; }
        .modern-search-field:last-of-type { border-right: none; }
    }
    
    .modern-search-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; color: #64748b; margin-bottom: 0.3rem; letter-spacing: 0.05em; }
    .modern-search-input, .modern-search-select { border: none; background: transparent; font-size: 1.05rem; color: #0f172a; font-weight: 600; width: 100%; padding: 0; outline: none; cursor: pointer; appearance: none; -webkit-appearance: none; -moz-appearance: none; }
    .modern-search-select { background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right center; background-size: 10px auto; padding-right: 1.5rem; }
    .modern-search-input::placeholder { color: #cbd5e1; font-weight: 400; }
    
    .modern-search-btn { background: var(--primary); color: white; border: none; border-radius: 12px; padding: 1rem 2rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; white-space: nowrap; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    @media (min-width: 768px) { .modern-search-btn { border-radius: 50px; } }
    .modern-search-btn:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
    .modern-search-btn svg { width: 18px; height: 18px; }
    
    .report-table-wrapper { width: 100%; overflow-x: auto; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); background: white; margin-top: 1.5rem; border: 1px solid #e2e8f0; }
    table.selection { width: 100%; border-collapse: collapse; margin: 0; font-size: 0.9rem; }
    table.selection th { background: #f8fafc; color: #475569; padding: 15px; text-align: left; font-weight: 700; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 0.05em; }
    table.selection th.centre { text-align: center; }
    table.selection th.number { text-align: right; }
    table.selection td { padding: 15px; border-bottom: 1px solid #f1f5f9; color: #1e293b; font-weight: 500; }
    table.selection td.centre { text-align: center; }
    table.selection td.number { text-align: right; font-family: "Courier New", Courier, monospace; font-weight: 600; }
    table.selection tr:hover td { background: #f8fafc; }
    table.selection tr:last-child td { font-weight: 800; border-top: 2px solid #cbd5e1; background: #f8fafc; }
    
    .modern-btn-print { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.75rem 1.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; margin-top: 2rem; }
    .modern-btn-print:hover { background: #e2e8f0; color: #1e293b; }
    
    @media print { .noPrint { display: none !important; } .report-table-wrapper { box-shadow: none; border: none; } }
</style>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<div class="modern-page-header noPrint">
		<h1>' . $Title . '</h1>
        <p>' . __('Find and trace where supplier payments, invoices, or debit notes have been allocated.') . '</p>
	</div>';

echo '<div class="modern-search-container noPrint">
		<div class="modern-search-field">
			<label for="TransType" class="modern-search-label">' . __('Document Type') . '</label>
			<select tabindex="1" name="TransType" id="TransType" class="modern-search-select"> ';

if (!isset($_POST['TransType'])) {
	$_POST['TransType']='20';
}
if ($_POST['TransType']==20) {
	 echo '<option selected="selected" value="20">' . __('Purchase Invoice') . '</option>
			<option value="22">' . __('Payment') . '</option>
			<option value="21">' . __('Debit Note') . '</option>';
} elseif ($_POST['TransType'] == 22) {
	echo '<option selected="selected" value="22">' . __('Payment') . '</option>
			<option value="20">' . __('Purchase Invoice') . '</option>
			<option value="21">' . __('Debit Note') . '</option>';
} elseif ($_POST['TransType'] == 21) {
	echo '<option selected="selected" value="21">' . __('Debit Note') . '</option>
		<option value="20">' . __('Purchase Invoice') . '</option>
		<option value="22">' . __('Payment') . '</option>';
}

echo '</select>
	</div>';

if (!isset($_POST['TransNo'])) {$_POST['TransNo']='';}
echo '<div class="modern-search-field">
		<label for="TransNo" class="modern-search-label">' . __('Transaction Number') . '</label>
		<input tabindex="2" type="text" id="TransNo" class="number modern-search-input" name="TransNo" required="required" maxlength="20" placeholder="e.g. 1045" value="'. htmlspecialchars($_POST['TransNo'], ENT_QUOTES, 'UTF-8') . '" />
	</div>
	<button tabindex="3" type="submit" name="ShowResults" class="modern-search-btn">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        ' . __('Search Allocations') . '
    </button>
	</div>';

if (isset($_POST['ShowResults']) AND  $_POST['TransNo']=='') {
	echo '<br />';
	prnMsg(__('The transaction number to be queried must be entered first'),'warn');
}

if (isset($_POST['ShowResults']) AND $_POST['TransNo']!='') {

/*First off get the DebtorTransID of the transaction (invoice normally) selected */
	$SQL = "SELECT supptrans.id,
				ovamount+ovgst AS totamt,
				currencies.decimalplaces AS currdecimalplaces,
				suppliers.currcode
			FROM supptrans INNER JOIN suppliers
			ON supptrans.supplierno=suppliers.supplierid
			INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
			WHERE type='" . $_POST['TransType'] . "'
			AND transno = '" . $_POST['TransNo']."'";

	if ($_SESSION['SalesmanLogin'] != '') {
			$SQL .= " AND supptrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) > 0) {
		$MyRow = DB_fetch_array($Result);
		$AllocToID = $MyRow['id'];
		$CurrCode = $MyRow['currcode'];
		$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
		$SQL = "SELECT type,
					transno,
					trandate,
					supptrans.supplierno,
					suppreference,
					supptrans.rate,
					ovamount+ovgst as totalamt,
					suppallocs.amt
				FROM supptrans
				INNER JOIN suppallocs ";
		if ($_POST['TransType']==22 OR $_POST['TransType'] == 21) {

			$TitleInfo = ($_POST['TransType'] == 22)?__('Payment'):__('Debit Note');
			$SQL .= "ON supptrans.id = suppallocs.transid_allocto
				WHERE suppallocs.transid_allocfrom = '" . $AllocToID . "'";
		} else {
			$TitleInfo = __('invoice');
			$SQL .= "ON supptrans.id = suppallocs.transid_allocfrom
				WHERE suppallocs.transid_allocto = '" . $AllocToID . "'";
		}
		$SQL .= " ORDER BY transno ";

		$ErrMsg = __('The customer transactions for the selected criteria could not be retrieved because');
		$TransResult = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($TransResult)==0) {

			if ($MyRow['totamt']>0 AND ($_POST['TransType']==22 OR $_POST['TransType'] == 21)) {
					prnMsg(__('This transaction was a receipt of funds and there can be no allocations of receipts or credits to a receipt. This inquiry is meant to be used to see how a payment which is entered as a negative receipt is settled against credit notes or receipts'),'info');
			} else {
				prnMsg(__('There are no allocations made against this transaction'),'info');
			}
		} else {
			$Printer = true;
			echo '<br />
				<div id="Report" class="report-table-wrapper">
				<table class="selection">
				<thead>
				<tr>
					<th class="centre" colspan="7" style="background: var(--primary); color: white; font-size: 1rem; padding: 1.5rem; text-transform: none;">
						<div style="font-size:1.15rem;margin-bottom:6px;">' . __('Allocations made against') . ' ' . $TitleInfo . ' <b>#' . $_POST['TransNo'] . '</b></div>
                        <div style="opacity:0.9;font-weight:500;">' . __('Transaction Total').': '. locale_number_format($MyRow['totamt'],$CurrDecimalPlaces) . ' ' . $CurrCode . '</div>
					</th>
				</tr>';

			$TableHeader = '<tr>
					<th class="centre">' . __('Date') . '</th>
					<th class="text">' . __('Type') . '</th>
					<th class="number">' . __('Number') . '</th>
					<th class="text">' . __('Reference') . '</th>
					<th class="number">' . __('Ex Rate') . '</th>
					<th class="number">' . __('Amount') . '</th>
					<th class="number">' . __('Alloc') . '</th>
				</tr>';
			echo $TableHeader,
				'</thead>
				<tbody>';

			$RowCounter = 1;
			$AllocsTotal = 0;

			while($MyRow=DB_fetch_array($TransResult)) {
				if ($MyRow['type']==21) {
					$TransType = __('Debit Note');
				} elseif ($MyRow['type'] == 20) {
					$TransType = __('Purchase Invoice');
				} else {
					$TransType = __('Payment');
				}
				echo '<tr class="striped_row">
						<td class="centre">', ConvertSQLDate($MyRow['trandate']), '</td>
						<td class="text">' . $TransType . '</td>
						<td class="number">' . $MyRow['transno'] . '</td>
						<td class="text">' . $MyRow['suppreference'] . '</td>
						<td class="number">' . $MyRow['rate'] . '</td>
						<td class="number">' . locale_number_format($MyRow['totalamt'], $CurrDecimalPlaces) . '</td>
						<td class="number">' . locale_number_format($MyRow['amt'], $CurrDecimalPlaces) . '</td>
					</tr>';

				$RowCounter++;
				if ($RowCounter == 22) {
					$RowCounter=1;
					echo $TableHeader;
				}
				//end of page full new headings if
				$AllocsTotal += $MyRow['amt'];
			}
			//end of while loop
			echo '<tr>
					<td class="number" colspan="6">' . __('Total allocated') . '</td>
					<td class="number">' . locale_number_format($AllocsTotal, $CurrDecimalPlaces) . '</td>
				</tr>
				</tbody></table>
				</div>';
		} // end if there are allocations against the transaction
	} //got the ID of the transaction to find allocations for
}
echo '</form>';
if (isset($Printer)) {
	echo '<div class="centre noPrint">
			<button onclick="javascript:window.print()" type="button" class="modern-btn modern-btn-print"><img alt="" src="', $RootPath, '/css/', $Theme,
				'/images/printer.png" style="vertical-align:middle;margin-right:5px;" /> ', __('Print'), '</button>', // "Print" button.
		'</div>';
}
include(__DIR__ . '/includes/footer.php');
