<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['FromCriteria'])
	AND mb_strlen($_POST['FromCriteria'])>=1
	AND isset($_POST['ToCriteria'])
	AND mb_strlen($_POST['ToCriteria'])>=1){

/*Now figure out the data to report for the criteria under review */

	$SQL = "SELECT grnno,
					purchorderdetails.orderno,
					grns.supplierid,
					suppliers.suppname,
					grns.itemcode,
					grns.itemdescription,
					qtyrecd,
					quantityinv,
					grns.stdcostunit,
					actprice,
					unitprice,
					suppliers.currcode,
					currencies.rate,
					currencies.decimalplaces as currdecimalplaces,
					stockmaster.decimalplaces as itemdecimalplaces
				FROM grns INNER JOIN purchorderdetails
				ON grns.podetailitem = purchorderdetails.podetailitem
				INNER JOIN suppliers
				ON grns.supplierid=suppliers.supplierid
				INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				LEFT JOIN stockmaster
				ON grns.itemcode=stockmaster.stockid
				WHERE qtyrecd-quantityinv>0
				AND grns.supplierid >='" . $_POST['FromCriteria'] . "'
				AND grns.supplierid <='" . $_POST['ToCriteria'] . "'
				ORDER BY supplierid,
					grnno";

	$GRNsResult = DB_query($SQL, '', '', false, false);

	if (DB_error_no() !=0) {
	  $Title = __('Outstanding GRN Valuation') . ' - ' . __('Problem Report');
	  include(__DIR__ . '/includes/header.php');
	  prnMsg(__('The outstanding GRNs valuation details could not be retrieved by the SQL because') . ' - ' . DB_error_msg(),'error');
	   echo '<br /><a href="' .$RootPath .'/index.php">' . __('Back to the menu') . '</a>';
	   include(__DIR__ . '/includes/footer.php');
	   exit();
	}
	if (DB_num_rows($GRNsResult) == 0) {
		$Title = __('Outstanding GRN Valuation') . ' - ' . __('Problem Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('No outstanding GRNs valuation details retrieved'), 'warn');
		echo '<br /><a href="' .$RootPath .'/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
		$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre noPrint" id="ReportHeader" style="display:none;">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Outstanding GRN Report') . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>';
	}

	$HTML .= '<div class="report-table-wrapper"><table class="selection">
		<thead>
			<tr>
				<th class="centre" colspan="13" style="background: var(--primary); color: white; font-size: 1rem; padding: 1.5rem; text-transform: none;">
					<div style="font-size:1.15rem;margin-bottom:6px;">' . __('Outstanding GRNs Report') . '</div>
					<div style="opacity:0.9;font-weight:500;">' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '</div>
				</th>
			</tr>
			<tr>
				<th>' . __('Supplier') . '</th>
				<th>' . __('Supplier Name') . '</th>
				<th>' . __('PO#') . '</th>
				<th>' . __('Item Code') . '</th>
				<th class="number">' . __('Qty Received') . '</th>
				<th class="number">' . __('Qty Invoiced') . '</th>
				<th class="number">' . __('Qty Pending') . '</th>
				<th class="number">' . __('Unit Price') . '</th>
				<th>' .'' . '</th>
				<th class="number">' . __('Line Total') . '</th>
				<th>' . '' . '</th>
				<th class="number">' . __('Line Total') . '</th>
				<th>' . '' . '</th>
			</tr>
		</thead>
		<tbody>';

	$TotalHomeCurrency = 0;
	while ($GRNs = DB_fetch_array($GRNsResult) ){
		$QtyPending = $GRNs['qtyrecd'] - $GRNs['quantityinv'];
		$TotalHomeCurrency = $TotalHomeCurrency + ($QtyPending * $GRNs['stdcostunit']);
		$HTML .= '<tr class="striped_row">
				<td>' . $GRNs['supplierid'] . '</td>
				<td>' . $GRNs['suppname'] . '</td>
				<td class="number">' . $GRNs['orderno'] . '</td>
				<td>' . $GRNs['itemcode'] . '</td>
				<td class="number">' . $GRNs['qtyrecd'] . '</td>
				<td class="number">' . $GRNs['quantityinv'] . '</td>
				<td class="number">' . $QtyPending . '</td>
				<td class="number">' . locale_number_format($GRNs['unitprice'],$GRNs['decimalplaces']) . '</td>
				<td>' . $GRNs['currcode'] . '</td>
				<td class="number">' . locale_number_format(($QtyPending * $GRNs['unitprice']),$GRNs['decimalplaces']) . '</td>
				<td>' . $GRNs['currcode'] . '</td>
				<td class="number">' . locale_number_format(($GRNs['qtyrecd'] - $GRNs['quantityinv'])*$GRNs['stdcostunit'],$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
				<td>' . $_SESSION['CompanyRecord']['currencydefault'] . '</td>
			</tr>';
	}
	$HTML .= '<tr class="total_row">
			<td colspan="10"></td>
			<td>' . __('Total') .':</td>
			<td class="number">' . locale_number_format($TotalHomeCurrency,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td>' . $_SESSION['CompanyRecord']['currencydefault'] . '</td>
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_OutstandingGRN_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {

		$Title=__('Outstanding GRNs Report');
		include(__DIR__ . '/includes/header.php');

		echo '<p class="page_title_text">
				<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' .__('Inventory') . '" alt="" />
				' . __('Goods Received but not invoiced Yet') . '
			</p>';

		echo '<div class="page_help_text">' . __('Shows the list of goods received not yet invoiced, both in supplier currency and home currency. When run for all suppliers, the total in home curency should match the GL Account for Goods received not invoiced.') . '</div>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*Neither the print PDF nor show on scrren option was hit */

	$Title=__('Outstanding GRNs Report');
	$ViewTopic = 'Inventory';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');

	echo '<style>
    /* Super Modern ERP Search Bar Styles */
    :root { --search-bg: #ffffff; --search-border: #e2e8f0; --search-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); }
    .modern-page-header { text-align: center; margin-top: 2rem; margin-bottom: 2.5rem; }
    .modern-page-header h1 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; }
    .modern-page-header p { font-size: 1.05rem; color: #64748b; margin: 0 auto; max-width: 800px; line-height: 1.5; }
    
    .modern-search-container { max-width: 850px; margin: 0 auto 3rem auto; background: var(--search-bg); border-radius: 16px; box-shadow: var(--search-shadow); border: 1px solid var(--search-border); padding: 1rem; display: flex; flex-direction: column; gap: 15px; transition: all 0.3s ease; }
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

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    
	echo '<div class="modern-page-header noPrint">
		<h1>' . __('Goods Received but not invoiced Yet') . '</h1>
        <p>' . __('Shows the list of goods received not yet invoiced, both in supplier currency and home currency. When run for all suppliers the total in home currency should match the GL Account for Goods received not invoiced.') . '</p>
	</div>';

	echo '<div class="modern-search-container noPrint">
		<div class="modern-search-field">
			<label for="FromCriteria" class="modern-search-label">' . __('From Supplier Code') . '</label>
			<input type="text" id="FromCriteria" name="FromCriteria" required="required" autofocus="autofocus" data-type="no-illegal-chars" value="0" class="modern-search-input" />
		</div>
		<div class="modern-search-field">
			<label for="ToCriteria" class="modern-search-label">' . __('To Supplier Code') . '</label>
			<input type="text" id="ToCriteria" name="ToCriteria" required="required" data-type="no-illegal-chars" value="zzzzzzz" class="modern-search-input" />
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

} /*end of else not PrintPDF */
