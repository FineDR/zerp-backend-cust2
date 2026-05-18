<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PaymentDate'])) {
	$_POST['PaymentDate'] = ConvertSQLDate($_POST['PaymentDate']);
}

if (isset($_GET['BatchNo'])) {
	$BatchNo = $_GET['BatchNo'];
	$SQL = "SELECT suppliers.supplierid,
					suppliers.suppname,
					suppliers.address1,
					suppliers.address2,
					suppliers.address3,
					suppliers.address4,
					suppliers.address5,
					suppliers.address6,
					suppliers.currcode,
					supptrans.id,
					currencies.decimalplaces AS currdecimalplaces
			FROM supptrans INNER JOIN suppliers ON supptrans.supplierno = suppliers.supplierid
			INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
			WHERE supptrans.type=22
			AND supptrans.transno = '" . $BatchNo . "'
			ORDER BY supplierno";
} elseif (
	isset($_POST['PrintPDF']) &&
	isset($_POST['FromCriteria']) && mb_strlen($_POST['FromCriteria']) >= 1 &&
	isset($_POST['ToCriteria']) && mb_strlen($_POST['ToCriteria']) >= 1
) {
	$SQL = "SELECT suppliers.supplierid,
					suppliers.suppname,
					suppliers.address1,
					suppliers.address2,
					suppliers.address3,
					suppliers.address4,
					suppliers.address5,
					suppliers.address6,
					suppliers.currcode,
					supptrans.id,
					currencies.decimalplaces AS currdecimalplaces
			FROM supptrans INNER JOIN suppliers ON supptrans.supplierno = suppliers.supplierid
			INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
			WHERE supptrans.type=22
			AND trandate ='" . FormatDateForSQL($_POST['PaymentDate']) . "'
			AND supplierno >= '" . $_POST['FromCriteria'] . "'
			AND supplierno <= '" . $_POST['ToCriteria'] . "'
			AND suppliers.remittance=1
			ORDER BY supplierno";
} else {
	$SQL = ""; // No query to run
}

if ($SQL != "") {
	$SuppliersResult = DB_query($SQL);
	if (DB_num_rows($SuppliersResult) == 0) {
		$Title = __('Print Remittance Advices Error');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('There were no remittance advices to print out for the criteria specified'), 'warn');
		echo '<br /><div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-primary">' . __('Back') . '</a></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	// Prepare HTML for DomPDF
	$HTML = '<html><head><style>
		@page { margin: 30px; }
		body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
		.header-container { margin-bottom: 40px; border-bottom: 2px solid #059669; padding-bottom: 20px; }
		.company-logo { float: left; width: 150px; }
		.company-details { float: right; text-align: right; font-size: 9pt; color: #666; }
		.document-title { clear: both; text-align: center; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; font-size: 18pt; color: #111; margin: 20px 0; }
		
		.info-grid { width: 100%; margin-bottom: 30px; border:none; }
		.info-box { width: 48%; vertical-align: top; border:none; padding:0; }
		.info-label { font-size: 8pt; text-transform: uppercase; color: #059669; font-weight: 800; margin-bottom: 5px; }
		.info-content { font-size: 11pt; font-weight: 600; }
		
		table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
		th { background: #f9fafb; color: #4b5563; font-size: 8pt; text-transform: uppercase; padding: 10px 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
		td { padding: 10px 8px; border-bottom: 1px solid #f3f4f6; font-size: 9pt; }
		.text-right { text-align: right; }
		
		.totals-row { background: #f0fdf4; font-weight: 800; color: #064e3b; }
		.footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
		</style></head><body>';

	while ($SuppliersPaid = DB_fetch_array($SuppliersResult)) {
		$SupplierID = $SuppliersPaid['supplierid'];
		$AccumBalance = 0;

		$HTML .= '<div class="header-container">';
		$HTML .= '  <div class="company-logo"><img src="' . $_SESSION['LogoFile'] . '" style="max-height: 60px;"></div>';
		$HTML .= '  <div class="company-details">';
		$HTML .= '    <strong>' . $_SESSION['CompanyRecord']['coyname'] . '</strong><br>';
		$HTML .= $_SESSION['CompanyRecord']['regoffice1'] . ', ' . $_SESSION['CompanyRecord']['regoffice2'] . '<br>';
		$HTML .= $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . '<br>';
		$HTML .= __('Tel') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' | ' . __('Email') . ': ' . $_SESSION['CompanyRecord']['email'];
		$HTML .= '  </div>';
		$HTML .= '</div>';

		$HTML .= '<div class="document-title">' . __('Remittance Advice') . '</div>';

		$HTML .= '<table class="info-grid"><tr>';
		$HTML .= '  <td class="info-box">';
		$HTML .= '    <div class="info-label">' . __('Supplier') . '</div>';
		$HTML .= '    <div class="info-content">' . $SuppliersPaid['suppname'] . ' (' . $SupplierID . ')</div>';
		$HTML .= '    <div style="font-size:9pt; color:#666; margin-top:5px;">' . $SuppliersPaid['address1'] . ' ' . $SuppliersPaid['address2'] . '<br>' . $SuppliersPaid['address3'] . '</div>';
		$HTML .= '  </td>';
		$HTML .= '  <td class="info-box" style="text-align:right;">';
		$HTML .= '    <div class="info-label">' . __('Batch / Date') . '</div>';
		$HTML .= '    <div class="info-content">' . (isset($BatchNo) ? __('Batch') . ' #' . $BatchNo : ConvertSQLDate($_POST['PaymentDate'])) . '</div>';
		$HTML .= '    <div class="info-label" style="margin-top:10px;">' . __('Currency') . '</div>';
		$HTML .= '    <div class="info-content">' . $SuppliersPaid['currcode'] . '</div>';
		$HTML .= '  </td>';
		$HTML .= '</tr></table>';

		$HTML .= '<table>';
		$HTML .= '<thead><tr>
					<th>' . __('Transaction Type') . '</th>
					<th>' . __('Date') . '</th>
					<th>' . __('Reference') . '</th>
					<th class="text-right">' . __('Invoice Total') . '</th>
					<th class="text-right">' . __('Amount Paid') . '</th>
				</tr></thead><tbody>';

		$SQL = "SELECT systypes.typename,
						supptrans.suppreference,
						supptrans.trandate,
						supptrans.transno,
						suppallocs.amt,
						(supptrans.ovamount + supptrans.ovgst ) AS trantotal
				FROM supptrans
				INNER JOIN systypes ON systypes.typeid = supptrans.type
				INNER JOIN suppallocs ON suppallocs.transid_allocto=supptrans.id
				WHERE suppallocs.transid_allocfrom='" . $SuppliersPaid['id'] . "'
				ORDER BY supptrans.type,
						 supptrans.transno";

		$TransResult = DB_query($SQL);
		while ($DetailTrans = DB_fetch_array($TransResult)) {
			$HTML .= '<tr>
						<td>' . htmlspecialchars(__($DetailTrans['typename'])) . '</td>
						<td>' . ConvertSQLDate($DetailTrans['trandate']) . '</td>
						<td>' . htmlspecialchars($DetailTrans['suppreference']) . '</td>
						<td class="text-right">' . locale_number_format($DetailTrans['trantotal'], $SuppliersPaid['currdecimalplaces']) . '</td>
						<td class="text-right" style="font-weight:700;">' . locale_number_format($DetailTrans['amt'], $SuppliersPaid['currdecimalplaces']) . '</td>
					</tr>';
			$AccumBalance += $DetailTrans['amt'];
		}

		$HTML .= '<tr class="totals-row">
					<td colspan="4" class="text-right" style="padding:15px;">' . __('TOTAL REMITTANCE') . ':</td>
					<td class="text-right" style="font-size:12pt; padding:15px;">' . locale_number_format($AccumBalance, $SuppliersPaid['currdecimalplaces']) . '</td>
				  </tr>';
		$HTML .= '</tbody></table>';

		$HTML .= '<div class="footer">' . __('Generated by') . ' ' . $_SESSION['CompanyRecord']['coyname'] . ' ' . __('on') . ' ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '</div>';
		$HTML .= '<div style="page-break-after:always;"></div>';
	}

	$HTML .= '</body></html>';

	$DomPDF = new Dompdf($DomPDFOptions);
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
	$DomPDF->render();
	$DomPDF->stream($_SESSION['DatabaseName'] . '_Remittance_Advices_' . date('Y-m-d') .'.pdf', array('Attachment' => false));
	exit();
} else {
	// Show form
	$Title = __('Remittance Advices');
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
    .modern-search-input::placeholder { color: #cbd5e1; font-weight: 400; }
    
    .modern-search-btn { background: var(--primary); color: white; border: none; border-radius: 12px; padding: 1rem 2rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; white-space: nowrap; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    @media (min-width: 768px) { .modern-search-btn { border-radius: 50px; } }
    .modern-search-btn:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
    .modern-search-btn svg { width: 18px; height: 18px; }
</style>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="modern-page-header noPrint">
		<h1>' . $Title . '</h1>
        <p>' . __('Generate remittance advice PDFs for supplier payments.') . '</p>
	</div>';

	if (!isset($_POST['FromCriteria']) or mb_strlen($_POST['FromCriteria']) < 1) {
		$DefaultFromCriteria = '1';
	} else {
		$DefaultFromCriteria = $_POST['FromCriteria'];
	}
	if (!isset($_POST['ToCriteria']) or mb_strlen($_POST['ToCriteria']) < 1) {
		$DefaultToCriteria = 'zzzzzzz';
	} else {
		$DefaultToCriteria = $_POST['ToCriteria'];
	}
	if (!isset($_POST['PaymentDate'])) {
		$DefaultDate = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') + 1, 0, date('y')));
	} else {
		$DefaultDate = $_POST['PaymentDate'];
	}

	echo '<div class="modern-search-container noPrint">
		<div class="modern-search-field">
			<label for="FromCriteria" class="modern-search-label">' . __('From Supplier Code') . '</label>
			<input type="text" id="FromCriteria" class="modern-search-input" name="FromCriteria" value="' . htmlspecialchars($DefaultFromCriteria, ENT_QUOTES, 'UTF-8') . '" />
		</div>
		<div class="modern-search-field">
			<label for="ToCriteria" class="modern-search-label">' . __('To Supplier Code') . '</label>
			<input type="text" id="ToCriteria" class="modern-search-input" name="ToCriteria" value="' . htmlspecialchars($DefaultToCriteria, ENT_QUOTES, 'UTF-8') . '" />
		</div>
		<div class="modern-search-field">
			<label for="PaymentDate" class="modern-search-label">' . __('Date Of Payment') . '</label>
			<input type="date" id="PaymentDate" class="modern-search-input" name="PaymentDate" value="' . FormatDateForSQL($DefaultDate) . '" />
		</div>
		<button type="submit" name="PrintPDF" class="modern-search-btn">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
			' . __('Print PDF') . '
		</button>
	</div>';

	echo '</form>';

	include(__DIR__ . '/includes/footer.php');
}
