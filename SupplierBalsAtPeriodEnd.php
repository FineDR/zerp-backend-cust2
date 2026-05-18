<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PrintPDF'])
	or isset($_POST['View'])
	and isset($_POST['FromCriteria'])
	and mb_strlen($_POST['FromCriteria']) >= 1
	and isset($_POST['ToCriteria'])
	and mb_strlen($_POST['ToCriteria']) >= 1) {

	$Title = __('Supplier Balance Listing');
	$Subject = __('Supplier Balances');

	// Start building HTML
	$HTML = '';
	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$SQL = "SELECT suppliers.supplierid,
					suppliers.suppname,
					currencies.currency,
					currencies.decimalplaces AS currdecimalplaces,
					SUM((supptrans.balance)/supptrans.rate) AS balance,
					SUM(supptrans.balance) AS fxbalance,
					SUM(CASE WHEN supptrans.trandate > '" . $_POST['PeriodEnd'] . "' THEN
						(supptrans.ovamount + supptrans.ovgst)/supptrans.rate ELSE 0 END) AS afterdatetrans,
					SUM(CASE WHEN supptrans.trandate > '" . $_POST['PeriodEnd'] . "'
						AND (supptrans.type=22 OR supptrans.type=21) THEN
						supptrans.diffonexch ELSE 0 END) AS afterdatediffonexch,
					SUM(CASE WHEN supptrans.trandate > '" . $_POST['PeriodEnd'] . "' THEN
						supptrans.ovamount + supptrans.ovgst ELSE 0 END) AS fxafterdatetrans
			FROM suppliers INNER JOIN currencies
			ON suppliers.currcode = currencies.currabrev
			INNER JOIN supptrans
			ON suppliers.supplierid = supptrans.supplierno
			WHERE suppliers.supplierid >= '" . $_POST['FromCriteria'] . "'
			AND suppliers.supplierid <= '" . $_POST['ToCriteria'] . "'
			GROUP BY suppliers.supplierid,
				suppliers.suppname,
				currencies.currency,
				currencies.decimalplaces";

	$ErrMsg = __('The Supplier details could not be retrieved');
	$SupplierResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($SupplierResult) == 0) {
		$Title = __('Supplier Balances - Problem Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('There are no supplier balances to list'), 'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	// Table header
		$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre noPrint" id="ReportHeader" style="display:none;">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Supplier Balance Listing') . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
                <div class="report-table-wrapper">
                <table class="selection">
		<thead>
            <tr>
				<th class="centre" colspan="4" style="background: var(--primary); color: white; font-size: 1rem; padding: 1.5rem; text-transform: none;">
					<div style="font-size:1.15rem;margin-bottom:6px;">' . __('Supplier Balance Listing') . '</div>
					<div style="opacity:0.9;font-weight:500;">' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '</div>
				</th>
			</tr>
			<tr>
				<th>' . __('Supplier Code & Name') . '</th>
				<th class="number">' . __('Balance') . '</th>
				<th class="number">' . __('FX Balance') . '</th>
				<th>' . __('Currency') . '</th>
			</tr>
		</thead>
		<tbody>';

	$TotBal = 0;

	while ($SupplierBalances = DB_fetch_array($SupplierResult)) {

		$Balance = $SupplierBalances['balance'] - $SupplierBalances['afterdatetrans'] + $SupplierBalances['afterdatediffonexch'];
		$FXBalance = $SupplierBalances['fxbalance'] - $SupplierBalances['fxafterdatetrans'];

		if (ABS($Balance) > CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault'])
			or ABS($FXBalance) > CurrencyTolerance($SupplierBalances['currency'])) {

			$DisplayBalance = locale_number_format($Balance, $_SESSION['CompanyRecord']['decimalplaces']);
			$DisplayFXBalance = locale_number_format($FXBalance, $SupplierBalances['currdecimalplaces']);

			$TotBal += $Balance;

			$HTML .= '<tr class="striped_row">
				<td class="left">' . $SupplierBalances['supplierid'] . ' - ' . $SupplierBalances['suppname'] . '</td>
				<td class="number">' . $DisplayBalance . '</td>
				<td class="number">' . $DisplayFXBalance . '</td>
				<td class="left">' . $SupplierBalances['currency'] . '</td>
			</tr>';
		}
	} // end while

	$DisplayTotBalance = locale_number_format($TotBal, $_SESSION['CompanyRecord']['decimalplaces']);

	// Total row
	$HTML .= '<tr class="total_row">
		<td class="left"><strong>' . __('Total') . '</strong></td>
		<td class="number"><strong>' . $DisplayTotBalance . '</strong></td>
		<td></td>
		<td></td>
	</tr>';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table></div>
				<div class="centre" style="margin-top: 20px;">
					<form><input type="submit" name="close" class="modern-search-btn" style="background:#f1f5f9;color:#475569;display:inline-flex;" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';
	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_Supplier_Balances_At_Prior_Month_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	}
	else {
		$Title = __('Supplier Balances At A Period End');
		include ('includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png" title="' . __('Suppliers') . '" alt="" />' . ' ' . __('Supplier Balances At A Period End') . '</p>';
		echo $HTML;
		include ('includes/footer.php');
	}

} else { // Not printing PDF, show input form

	$Title = __('Supplier Balances At A Period End');
	$ViewTopic = 'AccountsPayable';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');

	echo '<style>
    /* Super Modern ERP Search Bar Styles */
    :root { --search-bg: #ffffff; --search-border: #e2e8f0; --search-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); }
    .modern-page-header { text-align: center; margin-top: 2rem; margin-bottom: 2.5rem; }
    .modern-page-header h1 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; }
    .modern-page-header p { font-size: 1.05rem; color: #64748b; margin: 0 auto; max-width: 600px; }
    
    .modern-search-container { max-width: 950px; margin: 0 auto 3rem auto; background: var(--search-bg); border-radius: 16px; box-shadow: var(--search-shadow); border: 1px solid var(--search-border); padding: 1rem; display: flex; flex-direction: column; gap: 15px; transition: all 0.3s ease; }
    .modern-search-container:focus-within { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 0 0 3px var(--primary-soft); border-color: var(--primary); }
    @media (min-width: 768px) { .modern-search-container { flex-direction: row; align-items: center; padding: 0.75rem 0.75rem 0.75rem 1.5rem; border-radius: 50px; } }
    
    .modern-search-field { display: flex; flex-direction: column; flex: 1; position: relative; padding: 0.5rem; }
    @media (min-width: 768px) { .modern-search-field { border-right: 1px solid #e2e8f0; padding: 0 1.5rem; } .modern-search-field:last-of-type { border-right: none; } }
    
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
    table.selection tr.total_row td { font-weight: 800; border-top: 2px solid #cbd5e1; background: #f8fafc; }
    
    @media print { .noPrint { display: none !important; } .report-table-wrapper { box-shadow: none; border: none; } }
</style>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="modern-page-header noPrint">
		<h1>' . $Title . '</h1>
        <p>' . __('View and print supplier balances at the end of a specific accounting period.') . '</p>
	</div>';

	echo '<div class="modern-search-container noPrint">
		<div class="modern-search-field">
			<label for="FromCriteria" class="modern-search-label">' . __('From Supplier') . '</label>
			<input type="text" id="FromCriteria" class="modern-search-input" name="FromCriteria" value="' . htmlspecialchars($_POST['FromCriteria'], ENT_QUOTES, 'UTF-8') . '" />
		</div>
		<div class="modern-search-field">
			<label for="ToCriteria" class="modern-search-label">' . __('To Supplier') . '</label>
			<input type="text" id="ToCriteria" class="modern-search-input" name="ToCriteria" value="' . htmlspecialchars($_POST['ToCriteria'], ENT_QUOTES, 'UTF-8') . '" />
		</div>
		<div class="modern-search-field">
			<label for="PeriodEnd" class="modern-search-label">' . __('Balances As At') . '</label>
			<select name="PeriodEnd" id="PeriodEnd" class="modern-search-select">';

	$SQL = "SELECT periodno,
					lastdate_in_period
			FROM periods
			ORDER BY periodno DESC";

	$ErrMsg = __('Could not retrieve period data because');
	$Periods = DB_query($SQL, $ErrMsg);

	while ($MyRow = DB_fetch_array($Periods)) {
		echo '<option value="' . $MyRow['lastdate_in_period'] . '" selected="selected" >' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period'], 'M', -1) . '</option>';
	}
	echo '</select>
		</div>
		<div style="display:flex; gap:10px;">
            <button type="submit" name="View" class="modern-search-btn" style="background:#f1f5f9; color:#475569; padding: 1rem 1.5rem; box-shadow:none;">
                ' . __('View') . '
            </button>
            <button type="submit" name="PrintPDF" class="modern-search-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                ' . __('Print PDF') . '
            </button>
        </div>
	</div>';

	echo '</form>';
	include(__DIR__ . '/includes/footer.php');
}
