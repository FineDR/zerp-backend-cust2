<?php
require_once (__DIR__ . '/includes/session.php');
require_once (__DIR__ . '/includes/DateFunctions.php');
require_once (__DIR__ . '/vendor/autoload.php');
include_once (__DIR__ . '/includes/SQL_CommonFunctions.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

$ViewTopic = 'ARReports';
$BookMark = 'PrintInvoicesCredits';

if (isset($_GET['orientation'])) {
	$Orientation = $_GET['orientation'];
} else {
	$Orientation = 'landscape';
}

if (isset($_GET['FromTransNo'])) {
	$FromTransNo = filter_number_format($_GET['FromTransNo']);
} elseif (isset($_POST['FromTransNo'])) {
	$FromTransNo = filter_number_format($_POST['FromTransNo']);
} else {
	$FromTransNo = '';
}

if (isset($_GET['InvOrCredit'])) {
	$InvOrCredit = $_GET['InvOrCredit'];
} elseif (isset($_POST['InvOrCredit'])) {
	$InvOrCredit = $_POST['InvOrCredit'];
}

if (isset($_GET['PrintPDF'])) {
	$PrintPDF = $_GET['PrintPDF'];
} elseif (isset($_POST['PrintPDF'])) {
	$PrintPDF = $_POST['PrintPDF'];
}

if (!isset($InvOrCredit) || ($InvOrCredit != 'Invoice' && $InvOrCredit != 'Credit' && $InvOrCredit != 'Receipt')) {
	$InvOrCredit = 'Invoice';
}

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = $_GET['DebtorNo'];
} elseif (isset($_POST['DebtorNo'])) {
	$DebtorNo = $_POST['DebtorNo'];
} else {
	$DebtorNo = '';
}

if (!isset($_POST['ToTransNo'])
	|| trim($_POST['ToTransNo'])==''
	|| filter_number_format($_POST['ToTransNo']) < $FromTransNo) {

	$_POST['ToTransNo'] = $FromTransNo;
}

$FirstTrans = $FromTransNo;

if (isset($PrintPDF)
	&& $PrintPDF!=''
	&& isset($FromTransNo)
	&& isset($InvOrCredit)
	&& $FromTransNo!=''
	OR isset($_GET['View'])
	OR isset($_GET['Email'])) {
	$UserLanguage = $_SESSION['Language'];

	while ($FromTransNo <= filter_number_format($_POST['ToTransNo'])) {

		// --- Fetch bank account details for invoice footer ---
		$SQL = "SELECT bankaccounts.invoice,
					bankaccounts.bankaccountnumber,
					bankaccounts.bankaccountcode
				FROM bankaccounts
				WHERE bankaccounts.invoice = '1'";
		$Result = DB_query($SQL, '', '', false, false);
		if (DB_error_no()!=1) {
			if (DB_num_rows($Result)==1) {
				$MyRowBank = DB_fetch_array($Result);
				$DefaultBankAccountNumber = __('Account') .': ' .$MyRowBank['bankaccountnumber'];
				$DefaultBankAccountCode = __('Bank Code:') .' ' .$MyRowBank['bankaccountcode'];
			} else {
				$DefaultBankAccountNumber = '';
				$DefaultBankAccountCode = '';
			}
		} else {
			$DefaultBankAccountNumber = '';
			$DefaultBankAccountCode = '';
		}

		// --- Invoice/Credit Header Query ---
		if ($InvOrCredit=='Invoice') {
			$SQL = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtortrans.consignment,
							debtortrans.packages,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.invaddrbranch,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							paymentterms.terms,
							paymentterms.dayinfollowingmonth,
							paymentterms.daysbeforedue,
							COALESCE(salesorders.deliverto, custbranch.brname) as deliverto,
							COALESCE(salesorders.deladd1, custbranch.braddress1) as deladd1,
							COALESCE(salesorders.deladd2, custbranch.braddress2) as deladd2,
							COALESCE(salesorders.deladd3, custbranch.braddress3) as deladd3,
							COALESCE(salesorders.deladd4, custbranch.braddress4) as deladd4,
							COALESCE(salesorders.deladd5, custbranch.braddress5) as deladd5,
							COALESCE(salesorders.deladd6, custbranch.braddress6) as deladd6,
							COALESCE(salesorders.customerref, '') as customerref,
							COALESCE(salesorders.orderno, '" . __('Direct Sale') . "') as orderno,
							COALESCE(salesorders.orddate, debtortrans.trandate) as orddate,
							COALESCE(locations.locationname, '') as locationname,
							shippers.shippername,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							debtortrans.reference,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						LEFT JOIN salesorders
						ON debtortrans.order_ = salesorders.orderno
						INNER JOIN shippers
						ON debtortrans.shipvia=shippers.shipper_id
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						LEFT JOIN locations
						ON salesorders.fromstkloc=locations.loccode
						LEFT JOIN locationusers
						ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=10
						AND debtortrans.transno='" . $FromTransNo . "'";
			if ($DebtorNo != '') {
				$SQL .= " AND debtortrans.debtorno='" . $DebtorNo . "'";
			}

			if (isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No') {
				$SQL .= ' AND debtorsmaster.ediinvoices=0';
			}
			} elseif ($InvOrCredit=='Credit') {
			$SQL = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtorsmaster.invaddrbranch,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							debtortrans.reference,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=11
						AND debtortrans.transno='" . $FromTransNo . "'";
			if ($DebtorNo != '') {
				$SQL .= " AND debtortrans.debtorno='" . $DebtorNo . "'";
			}
		} elseif ($InvOrCredit=='Receipt') {
			$SQL = "SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtorsmaster.invaddrbranch,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							debtortrans.id as transid,
							debtortrans.reference,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=12
						AND debtortrans.transno='" . $FromTransNo . "'";
			if ($DebtorNo != '') {
				$SQL .= " AND debtortrans.debtorno='" . $DebtorNo . "'";
			}
			if (isset($_POST['PrintEDI']) AND $_POST['PrintEDI']=='No') {
				$SQL .= ' AND debtorsmaster.ediinvoices=0';
			}
		}
		$ErrMsg = __('There was a problem retrieving the invoice or credit note details for note number') . ' ' . $FromTransNo;
		$Result = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($Result) >= 1) {
			$MyRow = DB_fetch_array($Result);

			$CustomerAddress = '';
			for ($i = 1; $i < 7; $i++) {
				$addr = trim($MyRow['address' . $i]);
				// Hide dummy data placeholders
				if ($addr != '' && !preg_match('/^address[1-6]$/i', $addr)) {
					$CustomerAddress .= $addr . '<br />';
				}
			}

			$BranchAddress = '';
			for ($i = 1; $i < 7; $i++) {
				$addr = trim($MyRow['braddress' . $i]);
				if ($addr != '' && !preg_match('/^address[1-6]$/i', $addr)) {
					$BranchAddress .= $addr . '<br />';
				}
			}

			$DeliveryAddress = '';
			for ($i = 1; $i < 7; $i++) {
				$addr = trim($MyRow['deladd' . $i]);
				if ($addr != '' && !preg_match('/^address[1-6]$/i', $addr)) {
					$DeliveryAddress .= $addr . '<br />';
				}
			}

			// Security checks as before (salesman/customer authorization)
			if ($_SESSION['SalesmanLogin'] != '' AND $_SESSION['SalesmanLogin'] != $MyRow['salesman']){
				echo '<p class="bad">' . __('Your account is set up to see only a specific salespersons orders. You are not authorised to view transaction for this order') . '</p>';
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
			if (isset($CustomerLogin) && $CustomerLogin == 1 AND $MyRow['debtorno'] != $_SESSION['CustomerID']){
				echo '<p class="bad">' . __('This transaction is addressed to another customer and cannot be displayed for privacy reasons') . '</p>';
				include(__DIR__ . '/includes/footer.php');
				exit();
			}

			$ExchRate = $MyRow['rate'];

			// --- Get line items ---
			if ($InvOrCredit=='Invoice') {
				$SQLLines = "SELECT stockmoves.stockid,
								stockmaster.description,
								-stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								stockmaster.decimalplaces
							FROM stockmoves INNER JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=10
							AND stockmoves.transno='" . $FromTransNo . "'";
			} elseif ($InvOrCredit=='Credit') {
				$SQLLines = "SELECT stockmoves.stockid,
								stockmaster.description,
								stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								stockmaster.decimalplaces
							FROM stockmoves INNER JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=11
							AND stockmoves.transno='" . $FromTransNo . "'";
			} else {
				// Receipts show allocations
				$SQLLines = "SELECT systypes.typename,
								debtortrans.transno,
								debtortrans.trandate,
								custallocns.amt as quantity,
								1 as units,
								custallocns.amt as fxprice,
								0 as discountpercent,
								custallocns.amt as fxnet,
								debtortrans.reference as stockid,
								CONCAT(systypes.typename, ' #', debtortrans.transno) as description,
								0 as controlled,
								0 as serialised,
								0 as decimalplaces,
								'' as narrative
							FROM custallocns
							INNER JOIN debtortrans ON custallocns.transid_allocto = debtortrans.id
							INNER JOIN systypes ON debtortrans.type = systypes.typeid
							WHERE custallocns.transid_allocfrom = '" . $MyRow['transid'] . "'";
			}
			$ErrMsgLines = __('There was a problem retrieving the invoice or credit note stock movement details for invoice number') . ' ' . $FromTransNo;
			$ResultLines = DB_query($SQLLines, $ErrMsgLines);

			// --- Calculate Due Date ---
			if ($InvOrCredit=='Invoice') {
				$DisplayDueDate = CalcDueDate(ConvertSQLDate($MyRow['trandate']), $MyRow['dayinfollowingmonth'], $MyRow['daysbeforedue']);
			} else {
				$DisplayDueDate = ConvertSQLDate($MyRow['trandate']);
			}

			if ($InvOrCredit=='Invoice') {
				$DisplaySubTot = locale_number_format($MyRow['ovamount'],$MyRow['decimalplaces']);
				$DisplayFreight = locale_number_format($MyRow['ovfreight'],$MyRow['decimalplaces']);
				$DisplayTax = locale_number_format($MyRow['ovgst'],$MyRow['decimalplaces']);
				$DisplayTotal = locale_number_format($MyRow['ovfreight']+$MyRow['ovgst']+$MyRow['ovamount'],$MyRow['decimalplaces']);
			} else {
				$DisplaySubTot = locale_number_format(abs($MyRow['ovamount']),$MyRow['decimalplaces']);
				$DisplayFreight = locale_number_format(abs($MyRow['ovfreight']),$MyRow['decimalplaces']);
				$DisplayTax = locale_number_format(abs($MyRow['ovgst']),$MyRow['decimalplaces']);
				$DisplayTotal = locale_number_format(abs($MyRow['ovfreight']+$MyRow['ovgst']+$MyRow['ovamount']),$MyRow['decimalplaces']);
			}

			// --- Begin Modern Industry-Standard HTML ---
			$HTML = '<html>
			<head>
				<style>
					@page { margin: 30px; }
					body { 
						font-family: "Helvetica", "Arial", sans-serif; 
						font-size: 10px; 
						color: #000; 
						line-height: 1.5;
						background: #fff;
					}
					.container { width: 100%; }
					
					/* Header Layout */
					.header-table { width: 100%; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 25px; }
					.logo { height: 70px; max-width: 300px; filter: grayscale(100%); }
					.document-title { 
						font-size: 32px; 
						font-weight: 800; 
						color: #000; 
						text-align: right; 
						text-transform: uppercase;
						margin: 0;
						letter-spacing: 2px;
					}
					.document-meta { text-align: right; font-size: 11px; margin-top: 8px; color: #333; }
					.meta-label { font-weight: 700; color: #000; }
					
					/* Address Sections */
					.address-table { width: 100%; margin-bottom: 30px; }
					.address-box { width: 33%; vertical-align: top; padding: 0 15px; border-left: 1px solid #ddd; }
					.address-box:first-child { border-left: none; padding-left: 0; }
					.address-label { 
						font-size: 10px; 
						text-transform: uppercase; 
						font-weight: 800; 
						color: #000; 
						margin-bottom: 12px; 
						display: block;
						letter-spacing: 1px;
						border-bottom: 1px solid #eee;
						padding-bottom: 4px;
					}
					.address-content { font-size: 11px; font-weight: 500; color: #111; }

					/* Info Bar (Order details) - Smart Gray */
					.info-bar { 
						width: 100%; 
						background: #f9fafb; 
						color: #000; 
						margin-bottom: 25px;
						border: 1px solid #ddd;
					}
					.info-bar td { padding: 10px 15px; font-size: 10px; text-align: center; border-right: 1px solid #ddd; }
					.info-bar td:last-child { border-right: none; }
					.info-label { display: block; font-size: 8px; text-transform: uppercase; color: #666; font-weight: 700; margin-bottom: 3px; }

					/* Main Items Table with Gray Vertical Lines */
					.items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border: 1px solid #333; }
					.items-table th { 
						background: #f3f4f6; 
						color: #000; 
						text-transform: uppercase; 
						font-size: 9px; 
						font-weight: 800; 
						padding: 12px 10px; 
						text-align: left;
						border: 1px solid #333;
					}
					.items-table td { 
						padding: 12px 10px; 
						border: 1px solid #333; 
						vertical-align: top; 
						font-size: 10px;
					}
					.items-table tr:nth-child(even) { background: #fdfdfd; }
					.text-right { text-align: right; }
					.font-bold { font-weight: 700; }
					
					/* Totals Layout */
					.totals-container { width: 100%; margin-top: 20px; }
					.totals-table { width: 280px; float: right; border-collapse: collapse; }
					.totals-table td { padding: 8px 10px; font-size: 11px; border-bottom: 1px solid #eee; }
					.total-row { background: #f3f4f6; font-size: 16px; font-weight: 900; color: #000; border: 2px solid #333; }
					.total-row td { padding: 15px 10px; border-bottom: none; }

					/* Footer */
					.footer-section { clear: both; margin-top: 60px; padding-top: 25px; border-top: 2px solid #333; }
					.payment-info { width: 65%; font-size: 10px; color: #333; }
					.thank-you { font-size: 16px; font-weight: 800; color: #000; margin-bottom: 12px; }
					.legal-notice { font-size: 8px; color: #666; margin-top: 25px; font-style: italic; line-height: 1.4; }
				</style>
			</head>
			<body>
				<div class="container">
					<table class="header-table">
						<tr>
							<td width="50%">
								<img class="logo" src="' . $_SESSION['LogoFile'] . '" alt="Logo" />
								<div style="margin-top:10px; font-weight: bold;">' . $_SESSION['CompanyRecord']['coyname'] . '</div>
								<div>' . $_SESSION['CompanyRecord']['regoffice1'] . ', ' . $_SESSION['CompanyRecord']['regoffice2'] . '</div>
								<div>' . __('Tel') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' | ' . __('Email') . ': ' . $_SESSION['CompanyRecord']['email'] . '</div>
								<div>' . __("Tax Ref") . ': ' . $_SESSION['CompanyRecord']['gstno'] . '</div>
							</td>
							<td width="50%" style="vertical-align: top;">
								<div class="document-title">' . ($InvOrCredit == "Invoice" ? __("TAX INVOICE") : ($InvOrCredit == "Receipt" ? __("OFFICIAL RECEIPT") : __("TAX CREDIT NOTE"))) . '</div>
								<div class="document-meta">
									<div><span class="meta-label">' . ($InvOrCredit == "Receipt" ? __("Receipt No") : __("Document No")) . ':</span> #' . $FromTransNo . '</div>
									<div><span class="meta-label">' . __("Date") . ':</span> ' . ConvertSQLDate($MyRow['trandate']) . '</div>
									<div><span class="meta-label">' . __("Due Date") . ':</span> ' . $DisplayDueDate . '</div>
									<div><span class="meta-label">' . __("Currency") . ':</span> ' . $MyRow['currcode'] . '</div>
								</div>
							</td>
						</tr>
					</table>

					<table class="address-table">
						<tr>
							<td class="address-box">
								<span class="address-label">' . __('Bill To') . '</span>
								<div class="address-content">
									' . $MyRow['name'] . '<br/>
									' . $CustomerAddress . '
									' . ($MyRow['taxref'] ? '<br/>'.__('Tax Ref').': ' . $MyRow['taxref'] : '') . '
								</div>
							</td>
							<td width="3%">&nbsp;</td>
							<td class="address-box">
								<span class="address-label">' . __('Ship To') . '</span>
								<div class="address-content">
									' . $MyRow['deliverto'] . '<br/>
									' . $DeliveryAddress . '
								</div>
							</td>
							<td width="3%">&nbsp;</td>
							<td class="address-box">
								<span class="address-label">' . __('Branch Details') . '</span>
								<div class="address-content">
									' . $MyRow['brname'] . '<br/>
									' . $BranchAddress . '
								</div>
							</td>
						</tr>
					</table>

					' . ($InvOrCredit == 'Invoice' ? '
					<table class="info-bar">
						<tr>
							<td><span class="info-label">' . __('Your Ref') . '</span>' . $MyRow['customerref'] . '</td>
							<td><span class="info-label">' . __('Our Order') . '</span>' . $MyRow['orderno'] . '</td>
							<td><span class="info-label">' . __('Order Date') . '</span>' . ConvertSQLDate($MyRow['orddate']) . '</td>
							<td><span class="info-label">' . __('Sales Person') . '</span>' . $MyRow['salesmanname'] . '</td>
							<td><span class="info-label">' . __('Shipper') . '</span>' . $MyRow['shippername'] . '</td>
						</tr>
					</table>' : '') . '

					<table class="items-table">
						<thead>
							' . ($InvOrCredit == 'Receipt' ? '
							<tr>
								<th width="20%">' . __('Original Ref') . '</th>
								<th width="45%">' . __('Trans Type & Date') . '</th>
								<th width="15%" class="text-right">' . __('Allocated') . '</th>
								<th width="20%" class="text-right">' . __('Running Total') . '</th>
							</tr>' : '
							<tr>
								<th width="15%">' . __('Item Code') . '</th>
								<th width="35%">' . __('Item Description') . '</th>
								<th width="10%" class="text-right">' . __('Qty') . '</th>
								<th width="15%" class="text-right">' . __('Price') . '</th>
								<th width="10%" class="text-right">' . __('Discount') . '</th>
								<th width="15%" class="text-right">' . __('Net Amount') . '</th>
							</tr>') . '
						</thead>
						<tbody>';

			if (DB_num_rows($ResultLines) > 0) {
				while ($MyRow2 = DB_fetch_array($ResultLines)) {
					$DisplayPrice = locale_number_format($MyRow2['fxprice'], $MyRow['decimalplaces']);
					$DisplayQty = locale_number_format($MyRow2['quantity'], $MyRow2['decimalplaces']);
					$DisplayNet = locale_number_format($MyRow2['fxnet'], $MyRow['decimalplaces']);
					$DisplayDiscount = locale_number_format($MyRow2['discountpercent'] * 100, 1) . '%';

					if ($InvOrCredit == 'Receipt') {
						$HTML .= '<tr>
									<td class="font-bold">' . $MyRow2['stockid'] . '</td>
									<td>' . $MyRow2['description'] . ' (' . ConvertSQLDate($MyRow2['trandate']) . ')</td>
									<td class="text-right font-bold">' . $DisplayNet . '</td>
									<td class="text-right">' . $DisplayNet . '</td>
								</tr>';
					} else {
						$HTML .= '<tr>
									<td class="font-bold">' . $MyRow2['stockid'] . '</td>
									<td>';
						
						// Get translation if available
						$TranslationResult = DB_query("SELECT descriptiontranslation FROM stockdescriptiontranslations WHERE stockid='" . $MyRow2['stockid'] . "' AND language_id='" . $MyRow['language_id'] ."'");
						if (DB_num_rows($TranslationResult)==1){
							$TranslationRow = DB_fetch_array($TranslationResult);
							$HTML .= $TranslationRow['descriptiontranslation'];
						} else {
							$HTML .= $MyRow2['description'];
						}

						if (mb_strlen($MyRow2['narrative']) > 1) {
							$HTML .= '<br/><span style="font-size:8px; color:#666; font-style:italic;">' . str_replace(array("\r\n", "\n", "\r"), "<br/>", $MyRow2['narrative']) . '</span>';
						}

						$HTML .= '  </td>
									<td class="text-right">' . $DisplayQty . ' ' . $MyRow2['units'] . '</td>
									<td class="text-right">' . $DisplayPrice . '</td>
									<td class="text-right">' . $DisplayDiscount . '</td>
									<td class="text-right font-bold">' . $DisplayNet . '</td>
								</tr>';
					}
				}
			} else {
				// Fallback for GL-only invoices (no stock moves)
				if ($InvOrCredit != 'Receipt') {
					$HTML .= '<tr>
								<td class="font-bold">SERVICE</td>
								<td>' . ($MyRow['invtext'] ? $MyRow['invtext'] : ($MyRow['reference'] ? $MyRow['reference'] : __('Invoice Detail / Narrative'))) . '</td>
								<td class="text-right">1.00</td>
								<td class="text-right">' . $DisplaySubTot . '</td>
								<td class="text-right">0.0%</td>
								<td class="text-right font-bold">' . $DisplaySubTot . '</td>
							</tr>';
				}
			}

			$HTML .= '  </tbody>
					</table>

					<div class="totals-container">
						<div style="float: left; width: 50%;">
							<div class="thank-you">' . __('Thank you for your business!') . '</div>
							<div style="font-size: 9px; color: #666;">
								<b>' . __('Terms') . ':</b> ' . $MyRow['terms'] . '<br/>
								' . ($MyRow['invtext'] ? '<b>' . __('Notes') . ':</b> ' . $MyRow['invtext'] : '') . '
							</div>
						</div>
						<table class="totals-table">
							<tr>
								<td>' . __('Sub Total') . '</td>
								<td class="text-right">' . $DisplaySubTot . '</td>
							</tr>
							<tr>
								<td>' . __('Freight') . '</td>
								<td class="text-right">' . $DisplayFreight . '</td>
							</tr>
							<tr>
								<td>' . __('Tax') . '</td>
								<td class="text-right">' . $DisplayTax . '</td>
							</tr>
							<tr class="total-row">
								<td>' . ($InvOrCredit == "Receipt" ? __("TOTAL RECEIVED") : __("TOTAL DUE")) . '</td>
								<td class="text-right">' . $DisplayTotal . '</td>
							</tr>
						</table>
					</div>

					<div class="footer-section">
						<table width="100%">
							<tr>
								<td class="payment-info">
									' . (($DefaultBankAccountCode || $DefaultBankAccountNumber) ? '
									<div style="margin-bottom:10px;">
										<span class="address-label">' . __('Payment Instructions') . '</span>
										<div style="font-weight:bold; color:#333;">' . $DefaultBankAccountCode . ' ' . $DefaultBankAccountNumber . '</div>
									</div>' : '') . '
									<div class="legal-notice">' . ($_SESSION['RomalpaClause'] ? $_SESSION['RomalpaClause'] : '') . '</div>
								</td>
								<td style="text-align:right; vertical-align: bottom;">
									<div style="font-size: 8px; color: #999;">' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '</div>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</body>
			</html>';
		}
		$FromTransNo++;
	}
if (isset($_GET['View']) and $_GET['View'] == 'Yes') {
	include(__DIR__ . '/includes/header.php');
	echo $HTML;
	include(__DIR__ . '/includes/footer.php');
} elseif (isset($_GET['Email'])) {
	$PdfFileName = $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . ($FromTransNo-1) .'_'. date('Y-m-d') . '.pdf';

	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);
	// (Optional) set up the paper size and orientation
	$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

	// Render the HTML as PDF
	$DomPDF->render();
	// Output the generated PDF to a temporary file
	$output = $DomPDF->output();

	file_put_contents($PdfFileName, $output);

	if ($_GET['Email']!='') {
		$ConfirmationText = __('Please find attached the') . $InvOrCredit . '_' . ($FromTransNo-1);
		$EmailSubject = $PdfFileName;
		$Success = SendEmailFromWebERP($_SESSION['CompanyRecord']['email'],
							array($_GET['Email'] =>  ''),
							$EmailSubject,
							$ConfirmationText,
							array($PdfFileName)
						);
	}
	unlink($PdfFileName);

	$Title = __('Send Report By Email');
	include(__DIR__ . '/includes/header.php');
	/// @todo give different message based on $Success
	echo '<div class="centre">
			<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
		</div>';
	include(__DIR__ . '/includes/footer.php');

} else {
	// Generate PDF with DomPDF
	$PdfFileName = $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . ($FromTransNo-1) .'_'. date('Y-m-d') . '.pdf';

	// Display PDF in browser
	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);
	
	// (Optional) Setup the paper size and orientation
	if($Orientation==''){
		$Orientation = 'portrait';
	}

	$DomPDF->setPaper($_SESSION['PageSize'], $Orientation);

	// Render the HTML as PDF
	$DomPDF->render();

	// Output the generated PDF to Browser
	$Attachment = (isset($_GET['Download']) && $_GET['Download'] == 'True') ? true : false;
	$DomPDF->stream($PdfFileName, array("Attachment" => $Attachment));
}

} else {
	// --- HTML output for preview form ---
	$Title=__('Select Invoices/Credit Notes To Print');

	// Inject premium styles for the Architect workspace
	$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
	/* Architect Workspace Overrides */
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; gap: 10px;
		padding: 12px 28px; border-radius: 50px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	.custom-range-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 20px;
		margin-bottom: 24px;
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
</style>';

	include(__DIR__ . '/includes/header.php');

	if (!isset($FromTransNo) OR $FromTransNo=='') {
		$TransactionType = ($InvOrCredit == 'Invoice') ? 10 : 11;
		$TransactionOptions = array();
		$SQL = "SELECT debtortrans.transno,
					debtorsmaster.name,
					(debtortrans.ovamount + debtortrans.ovfreight + debtortrans.ovgst) AS totalamount,
					currencies.decimalplaces,
					currencies.currabrev
				FROM debtortrans
				INNER JOIN debtorsmaster
					ON debtortrans.debtorno=debtorsmaster.debtorno
				INNER JOIN currencies
					ON debtorsmaster.currcode=currencies.currabrev
				WHERE debtortrans.type='" . $TransactionType . "'
				ORDER BY debtortrans.transno DESC";
		$Result = DB_query($SQL);
		while ($MyRow = DB_fetch_array($Result)) {
			$DisplayTotal = locale_number_format(abs($MyRow['totalamount']), $MyRow['decimalplaces']);
			$TransactionOptions[$MyRow['transno']] = $MyRow['transno'] . ' - ' . $MyRow['name'] . ' - ' . $MyRow['currabrev'] . ' ' . $DisplayTotal;
		}

		echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('Home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('Receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('Document Generation') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Generate pixel-perfect PDF documents and previews') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

		echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post" target="_blank" style="display: contents;">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		echo '<div class="custom-bottom-layout">
				<aside class="db-sidebar">';

		echo '<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
				<div class="db-card-header">
					<h3 class="db-card-title">
						<i class="fas fa-sliders-h" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Print Configuration') . '
					</h3>
				</div>
				<div style="padding: 24px;">

					<div class="db-form-group" style="margin-bottom: 24px;">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Document Type') . '</label>
						<select name="InvOrCredit" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';
		if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {
			echo '<option selected="selected" value="Invoice">' . __('Invoices') . '</option>';
			echo '<option value="Credit">' . __('Credit Notes') . '</option>';
		} else {
			echo '<option selected="selected" value="Credit">' . __('Credit Notes') . '</option>';
			echo '<option value="Invoice">' . __('Invoices') . '</option>';
		}
		echo '			</select>
					</div>

					<div class="db-form-group" style="margin-bottom: 24px;">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('EDI Inclusion') . '</label>
						<select name="PrintEDI" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';
		if ($InvOrCredit=='Invoice' OR !isset($InvOrCredit)) {
			echo '<option selected="selected" value="No">' . __('Skip EDI Customers') . '</option>';
			echo '<option value="Yes">' . __('Include EDI Customers') . '</option>';
		} else {
			echo '<option value="No">' . __('Skip EDI Customers') . '</option>';
			echo '<option selected="selected" value="Yes">' . __('Include EDI Customers') . '</option>';
		}
		echo '			</select>
					</div>

					<div style="display: flex; flex-direction: column; gap: 12px;">
						<button type="submit" name="RefreshList" class="db-btn" style="width: 100%; justify-content: center; font-weight: 700; padding: 14px; border-radius: 14px; background: #e5e7eb; color: #374151; border: none; cursor: pointer;" formtarget="_self">
							<i class="fas fa-sync" style="margin-right: 8px;"></i> ' . __('Refresh List') . '
						</button>
						<button type="submit" name="Print" class="db-btn" style="width: 100%; justify-content: center; font-weight: 700; padding: 14px; border-radius: 14px; background: #10b981; color: white; border: none; cursor: pointer;">
							<i class="fas fa-eye" style="margin-right: 8px;"></i> ' . __('HTML Preview') . '
						</button>
						<button type="submit" name="PrintPDF" class="db-btn" style="width: 100%; justify-content: center; font-weight: 700; padding: 18px; border-radius: 14px; background: #059669; color: white; border: none; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3); cursor: pointer;">
							<i class="fas fa-file-pdf" style="margin-right: 8px;"></i> ' . __('Generate PDF') . '
						</button>
					</div>

				</div>
			</div>
			</aside>';

		echo '<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-list-ol" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Document Range Selection') . '
						</h3>
					</div>
					<div style="padding: 30px;">
						
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Start Range') . '</label>
								<select name="FromTransNo" class="db-input" required="required" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
									<option value="">' . __('Select a transaction') . '</option>';
		foreach ($TransactionOptions as $TransactionNo => $TransactionLabel) {
			if ((string)$FromTransNo === (string)$TransactionNo) {
				echo '<option selected="selected" value="' . $TransactionNo . '">' . htmlspecialchars($TransactionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
			} else {
				echo '<option value="' . $TransactionNo . '">' . htmlspecialchars($TransactionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
			}
		}
		echo '					</select>
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('End Range') . '</label>
								<select name="ToTransNo" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
									<option value="">' . __('Select a transaction') . '</option>';
		foreach ($TransactionOptions as $TransactionNo => $TransactionLabel) {
			if (isset($_POST['ToTransNo']) && (string)filter_number_format($_POST['ToTransNo']) === (string)$TransactionNo) {
				echo '<option selected="selected" value="' . $TransactionNo . '">' . htmlspecialchars($TransactionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
			} else {
				echo '<option value="' . $TransactionNo . '">' . htmlspecialchars($TransactionLabel, ENT_QUOTES, 'UTF-8') . '</option>';
			}
		}
		echo '					</select>
							</div>
						</div>';

		$SQL = "SELECT typeno FROM systypes WHERE typeid=10";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		$LastInv = $MyRow[0];

		$SQL = "SELECT typeno FROM systypes WHERE typeid=11";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		$LastCr = $MyRow[0];

		echo '			<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 20px; border-radius: 16px; display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span style="color: #064e3b; font-weight: 700; font-size: 0.9rem;">' . __('Last Invoice Number') . '</span>
								<span style="background: #ffffff; padding: 4px 12px; border-radius: 8px; font-weight: 900; color: #059669; border: 1px solid #d1fae5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' . $LastInv . '</span>
							</div>
							<div style="display: flex; justify-content: space-between; align-items: center;">
								<span style="color: #064e3b; font-weight: 700; font-size: 0.9rem;">' . __('Last Credit Note Number') . '</span>
								<span style="background: #ffffff; padding: 4px 12px; border-radius: 8px; font-weight: 900; color: #059669; border: 1px solid #d1fae5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' . $LastCr . '</span>
							</div>
							<div style="margin-top: 10px; font-size: 0.85rem; color: #047857; opacity: 0.8;">
								<i class="fas fa-info-circle"></i> ' . __('To print a single document, select the same number for both Start and End range.') . '
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>'; // End db-bottom-layout

		echo '</form>
		</div>'; // End db-page

	} else {
		// --- Output HTML preview for selected invoice(s) (similar to above, but just echo) ---
		while($FromTransNo <= filter_number_format($_POST['ToTransNo'])) {
			// ... (reuse earlier logic to fetch and echo details, but as HTML, not PDF)
			// For brevity, you can reuse the same PHP/HTML as in the PDF block, but echo instead of buffering.
			// You can copy the above HTML for invoice preview, replacing $HTML .= ob_get_clean(); with echo.
			// (Omitted for brevity here, but you can copy-paste the HTML/PHP above)
			$FromTransNo++;

	        echo '<br> Weka Hapa Preview invoice';exit;
		}
	}
	include(__DIR__ . '/includes/footer.php');
}
