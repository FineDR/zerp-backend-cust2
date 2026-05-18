<?php

include (__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include (__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['Date'])) {
	$_POST['Date'] = ConvertSQLDate($_POST['Date']);
}

$InputError = 0;
if (isset($_POST['Date']) && !Is_Date($_POST['Date'])) {
	$Msg = __('The date must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError = 1;
	unset($_POST['Date']);
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$SQL = "SELECT type,
			supplierno,
			suppreference,
			trandate,
			ovamount,
			ovgst,
			transtext,
			currcode,
			decimalplaces AS currdecimalplaces,
			suppname
		FROM supptrans INNER JOIN suppliers
		ON supptrans.supplierno = suppliers.supplierid
		INNER JOIN currencies
		ON suppliers.currcode=currencies.currabrev
		WHERE type='" . $_POST['TransType'] . "'
		AND trandate='" . FormatDateForSQL($_POST['Date']) . "'";

	$ErrMsg = __('An error occurred getting the payments');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0) {
		$Title = __('Payment Listing');
		include (__DIR__ . '/includes/header.php');
		echo '<br />';
		prnMsg(__('There were no transactions found in the database for the date') . ' ' . $_POST['Date'] . '. ' . __('Please try again selecting a different date'), 'info');
		include (__DIR__ . '/includes/footer.php');
		exit();
	}

	$TransactionType = match ($_POST['TransType']) {
		20      => __('Invoices'),
		21      => __('Credits'),
		22      => __('Payments'),
		default => __('None'),
	};

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>';


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<img class="logo" src=' . $_SESSION['LogoFile'] . ' /><br />';
	}

	$HTML .= '<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Transaction type') . ': ' . $TransactionType . '<br />
					' . __('Date of Transactions') .': ' . $_POST['Date'] . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . __('Supplier Name') . '</th>
							<th>' . __('Reference') . '</th>
							<th>' . __('Date') . '</th>
							<th>' . __('Amount') . '</th>
							<th>' . __('GST') . '</th>
							<th>' . __('Total') . '</th>
						</tr>
					</thead>
					<tbody>';

	$TotalCheques = 0;
	$CurrDecimalPlaces = 2; // fallback
	while ($MyRow = DB_fetch_array($Result)) {
		$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
		$suppname = htmlspecialchars($MyRow['suppname']);
		$suppreference = htmlspecialchars($MyRow['suppreference']);
		$trandate = htmlspecialchars(ConvertSQLDate($MyRow['trandate']));
		$ovamount = locale_number_format($MyRow['ovamount'], $CurrDecimalPlaces);
		$ovgst = locale_number_format($MyRow['ovgst'], $CurrDecimalPlaces);
		$total = locale_number_format($MyRow['ovamount'] + $MyRow['ovgst'], $CurrDecimalPlaces);

		$HTML .= '<tr class="striped_row">
		<td>' . $suppname . '</td>
		<td>' . $suppreference . '</td>
		<td>' . $trandate . '</td>
		<td class="number">' . $ovamount . '</td>
		<td class="number">' . $ovgst . '</td>
		<td class="number">' . $total . '</td>
	</tr>';

		$TotalCheques -= $MyRow['ovamount'];
	}

	$HTML .= '<tr class="total_row">
				<td colspan="5" style="text-align: right;">' . __('Total Transactions') . '</td>
				<td class="number">' . locale_number_format(-$TotalCheques, $CurrDecimalPlaces) . '</td>
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
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_SuppTransListing_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	}
	else {
		$Title = __('Inventory Planning Report');
		include (__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . __('Supplier Transaction Listing') . '" alt="" />' . ' ' . __('Supplier Transaction Listing') . '</p>';
		echo $HTML;
		include (__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit */
	$Title = __('Supplier Transaction Listing');
	$ViewTopic = 'AccountsPayable';
	$BookMark = '';
	include (__DIR__ . '/includes/header.php');

	echo '<style>
    /* Super Modern ERP Search Bar Styles */
    :root { --search-bg: #ffffff; --search-border: #e2e8f0; --search-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); }
    .modern-page-header { text-align: center; margin-top: 2rem; margin-bottom: 2.5rem; }
    .modern-page-header h1 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; }
    .modern-page-header p { font-size: 1.05rem; color: #64748b; margin: 0 auto; max-width: 600px; }
    
    .modern-search-container { max-width: 850px; margin: 0 auto 3rem auto; background: var(--search-bg); border-radius: 16px; box-shadow: var(--search-shadow); border: 1px solid var(--search-border); padding: 1rem; display: flex; flex-direction: column; gap: 15px; transition: all 0.3s ease; }
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
</style>';

	if ($InputError == 1) {
		prnMsg($Msg, 'error');
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="modern-page-header noPrint">
		<h1>' . $Title . '</h1>
        <p>' . __('List and verify supplier transactions (Invoices, Credit Notes, or Payments) for a specific date.') . '</p>
	</div>';

	echo '<div class="modern-search-container noPrint">
		<div class="modern-search-field">
			<label for="Date" class="modern-search-label">' . __('Transaction Date') . '</label>
			<input name="Date" id="Date" type="date" class="modern-search-input" value="' . date('Y-m-d') . '" />
		</div>
		<div class="modern-search-field">
			<label for="TransType" class="modern-search-label">' . __('Transaction Type') . '</label>
			<select name="TransType" id="TransType" class="modern-search-select">
				<option value="20">' . __('Invoices') . '</option>
				<option value="21">' . __('Credit Notes') . '</option>
				<option value="22">' . __('Payments') . '</option>
			</select>
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

	include (__DIR__ . '/includes/footer.php');

}
