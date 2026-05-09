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
	$Orientation = 'portrait';
}
$is_pdf = false;

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

// Suppress sidebar ONLY for PDF/Print views or if explicitly requested
if (isset($_GET['PrintPDF']) || isset($_GET['View']) || (isset($_GET['Receipt']) && !isset($_GET['View']) && !isset($_GET['WithMenu']))) {
	$NoMenu = 1;
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
	$HTML = ''; // Initialize HTML buffer
	$IsThermal = false; // Initialize thermal flag
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
							debtortrans.alloc,
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
							debtortrans.alloc,
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
							debtortrans.alloc,
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
				$addr = isset($MyRow['braddress' . $i]) ? trim($MyRow['braddress' . $i]) : '';
				if ($addr != '' && !preg_match('/^address[1-6]$/i', $addr)) {
					$BranchAddress .= $addr . '<br />';
				}
			}

			$DeliveryAddress = '';
			for ($i = 1; $i < 7; $i++) {
				$addr = isset($MyRow['deladd' . $i]) ? trim($MyRow['deladd' . $i]) : '';
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
			$IsThermal = (strcasecmp($InvOrCredit, 'Receipt') == 0 || (isset($_GET['View']) && isset($_GET['Receipt'])));
			$ExchRate = (float)$MyRow['rate'];
			if ($ExchRate == 0) $ExchRate = 1;

			if (strcasecmp($InvOrCredit, 'Invoice') == 0) {
				$SQLLines = "SELECT stockmoves.stockid,
								COALESCE(stockmaster.description, stockmoves.stockid) as description,
								-stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . "* -stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								COALESCE(stockmaster.decimalplaces, 2) as decimalplaces
							FROM stockmoves LEFT JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=10
							AND stockmoves.transno='" . (int)$FromTransNo . "'";
			} elseif (strcasecmp($InvOrCredit, 'Credit') == 0) {
				$SQLLines = "SELECT stockmoves.stockid,
								COALESCE(stockmaster.description, stockmoves.stockid) as description,
								stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * " . $ExchRate . " * stockmoves.qty) AS fxnet,
								(stockmoves.price * " . $ExchRate . ") AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								COALESCE(stockmaster.decimalplaces, 2) as decimalplaces
							FROM stockmoves LEFT JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=11
							AND stockmoves.transno='" . (int)$FromTransNo . "'";
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
			$ErrMsgLines = __('There was a problem retrieving the transaction details');
			$ResultLines = DB_query($SQLLines, $ErrMsgLines);

			// --- Calculate Due Date ---
			if (strcasecmp($InvOrCredit, 'Invoice') == 0) {
				$DisplayDueDate = CalcDueDate(ConvertSQLDate($MyRow['trandate']), $MyRow['dayinfollowingmonth'], $MyRow['daysbeforedue']);
			} else {
				$DisplayDueDate = ConvertSQLDate($MyRow['trandate']);
			}

			if (strcasecmp($InvOrCredit, 'Invoice') == 0) {
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

			// --- Calculate Balance & Status ---
			$TotalAmount = $MyRow['ovamount'] + $MyRow['ovgst'] + $MyRow['ovfreight'];
			$AmountPaid = abs($MyRow['alloc']);
			$BalanceDue = $TotalAmount - $AmountPaid;
			
			$StatusLabel = 'Pending';
			$StatusClass = 'status-pending';
			
			if ($BalanceDue <= 0.001) {
				$StatusLabel = 'Paid';
				$StatusClass = 'status-paid';
			} elseif (isset($MyRow['daysbeforedue']) && strtotime($MyRow['trandate']) < strtotime('-' . $MyRow['daysbeforedue'] . ' days')) {
				$StatusLabel = 'Overdue';
				$StatusClass = 'status-overdue';
			}

			// --- Helper for missing data ---
			$getVal = function($val, $default = 'Not provided') {
				return (trim($val) != '' && !preg_match('/^address[1-6]$/i', trim($val))) ? $val : '<span class="not-provided">' . $default . '</span>';
			};

			if ($IsThermal) {
				// ====================================================================
				// PART 3: UNIFIED THERMAL POS RECEIPT SYSTEM
				// ====================================================================
				$HTML = '
					<style>
						@page { margin: 0; }
						.dashboard-content { 
							font-family: "Courier New", Courier, monospace; 
							background: #f1f5f9;
							padding: 20px;
							display: flex;
							justify-content: center;
							min-height: 80vh;
						}
						.receipt-container { 
							width: 400px; 
							background: #fff; 
							padding: 30px; 
							box-shadow: 0 10px 25px rgba(0,0,0,0.05);
							border: 1px solid #e2e8f0;
							margin: 20px auto; /* Centered */
						}
						.centered { text-align: center; }
						.header-name { font-size: 18px; font-weight: 900; text-transform: uppercase; margin-bottom: 5px; }
						.header-info { font-size: 11px; color: #444; margin-bottom: 2px; }
						.divider { border-top: 1px dashed #000; margin: 15px 0; }
						
						.meta-table { width: 100%; margin-bottom: 15px; }
						.meta-table td { font-size: 12px; padding: 2px 0; }
						.label { font-weight: bold; }
						
						.items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
						.items-table th, td {
							padding: 4px 10px;
							text-align: left;
							border-bottom: 1px solid var(--slate-50);
							font-size: 11px;
						}
						.text-right { text-align: right; }
						
						.total-section { margin-top: 15px; border-top: 1px solid #000; padding-top: 10px; }
						.total-row { display: flex; justify-content: space-between; font-size: 16px; font-weight: 900; }
						
						.footer { margin-top: 30px; font-size: 11px; text-align: center; color: #666; }

						.sticky-footer {
							position: fixed;
							bottom: 20px;
							right: 40px;
							background: rgba(15, 23, 42, 0.9);
							backdrop-filter: blur(8px);
							padding: 12px 24px;
							border-radius: 50px;
							display: flex;
							gap: 12px;
							box-shadow: 0 10px 25px rgba(0,0,0,0.2);
							z-index: 1000;
						}
						.btn { 
							display: inline-flex; align-items: center; padding: 10px 18px; border-radius: 8px; 
							font-weight: 600; font-size: 14px; text-decoration: none; border: 1px solid #e2e8f0;
							background: white; color: #1e293b; cursor: pointer;
						}
						.btn-primary { background: #059669; color: white; border: none; }
						.btn-primary:hover { background: #047857; }

						@media print {
							.ModuleList, header, footer, .sidebar-mask, #SidebarToggle, .help-bubble, .sticky-footer { display: none !important; }
							body { background: #fff !important; padding: 0 !important; margin: 0 !important; }
							.dashboard-content { padding: 0 !important; width: 100% !important; display: block !important; }
							.receipt-container { width: 100% !important; box-shadow: none !important; border: none !important; padding: 10px !important; margin: 0 !important; }
						}
				</style>
				<div class="dashboard-content">
					<div class="receipt-container">
						<div class="centered">
							<div class="header-name">' . $_SESSION['CompanyRecord']['coyname'] . '</div>
							<div class="header-info">' . $_SESSION['CompanyRecord']['regoffice1'] . '</div>
							<div class="header-info">' . $_SESSION['CompanyRecord']['regoffice2'] . '</div>
							<div class="header-info">' . __('Tel') . ': ' . $_SESSION['CompanyRecord']['telephone'] . '</div>
						</div>

						<div class="divider"></div>

						<table class="meta-table">
							<tr><td class="label">' . ($InvOrCredit == "Invoice" ? __("Invoice No") : __("Receipt No")) . ':</td><td class="text-right"><strong>#' . $FromTransNo . '</strong></td></tr>
							<tr><td class="label">' . __('Date') . ':</td><td class="text-right">' . ConvertSQLDate($MyRow['trandate']) . '</td></tr>
							<tr><td class="label">' . __('Customer') . ':</td><td class="text-right">' . (trim($MyRow['name']) != '' ? $MyRow['name'] : __('Walk-in Customer')) . '</td></tr>
							<tr><td class="label">' . __('Cashier') . ':</td><td class="text-right">' . ($_SESSION['UsersRealName'] ?? 'Administrator') . '</td></tr>
						</table>

						<div class="divider"></div>';

				if ($InvOrCredit == 'Invoice') {
					$HTML .= '<div class="label">' . __('Sale Details') . '</div>';
				} else {
					$HTML .= '<div class="label">' . __('Payment Method') . ': ' . (trim($MyRow['reference']) != '' ? $MyRow['reference'] : __('N/A')) . '</div>';
				}

				$HTML .= '		<table class="items-table">
							<thead>
								<tr>
									<th>' . ($InvOrCredit == 'Invoice' ? __('Item/Qty') : __('Description')) . '</th>
									<th class="text-right">' . __('Amount') . '</th>
								</tr>
							</thead>
							<tbody>';

				if (DB_num_rows($ResultLines) > 0) {
					DB_data_seek($ResultLines, 0); // Ensure we are at the start
					while ($MyRow2 = DB_fetch_array($ResultLines)) {
						$DisplayNet = locale_number_format($MyRow2['fxnet'], $MyRow['decimalplaces']);
						if ($InvOrCredit == 'Invoice') {
							$HTML .= '<tr>
										<td>' . $MyRow2['description'] . '<br/><small>' . locale_number_format($MyRow2['quantity'], $MyRow2['decimalplaces']) . ' x ' . locale_number_format($MyRow2['fxprice'], $MyRow['decimalplaces']) . '</small></td>
										<td class="text-right">' . $DisplayNet . '</td>
									</tr>';
						} else {
							$HTML .= '<tr>
										<td>' . $MyRow2['description'] . '</td>
										<td class="text-right">' . $DisplayNet . '</td>
									</tr>';
						}
					}
				} else {
					$HTML .= '<tr><td colspan="2" class="centered" style="padding:10px;">-- ' . ($InvOrCredit == 'Invoice' ? __('No items found') : __('Unallocated Payment')) . ' --</td></tr>';
				}

				$HTML .= '  </tbody>
						</table>

						<div class="total-section">
							<div class="total-row">
								<span>' . ($InvOrCredit == "Invoice" ? __("TOTAL DUE") : __("TOTAL PAID")) . '</span>
								<span>' . $MyRow['currcode'] . ' ' . $DisplayTotal . '</span>
							</div>
						</div>

						<div class="divider"></div>

						<div class="footer">
							<div style="font-weight:bold;">' . __('Thank you for your business!') . '</div>
							<div style="margin-top:8px; font-size:10px; color:#666;">' . __('Printed at') . ': ' . date('H:i:s') . '</div>
						</div>
					</div>

					<div class="sticky-footer no-print">
						<a href="' . $RootPath . '/TRAReceipt.php?BatchNumber=' . $FromTransNo . '" class="btn btn-primary">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"></path></svg>
							Download Legal PDF
						</a>
						<button onclick="window.print()" class="btn">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
							Print Receipt
						</button>
					</div>
				</div>';
				
				$HTML .= '';
			} else {
				// ====================================================================
				// PART 1 & 2: REUSABLE INVOICE TEMPLATE (Dashboard & PDF)
				// ====================================================================
				
				// Prepare Data Arrays
				$invoice = array(
					'logo' => (isset($_SESSION['LogoFile']) ? $_SESSION['LogoFile'] : (isset($_SESSION['CompanyRecord']['logo']) ? $_SESSION['CompanyRecord']['logo'] : '')),
					'company_name' => $_SESSION['CompanyRecord']['coyname'],
					'company_address' => $_SESSION['CompanyRecord']['regoffice1'] . ', ' . $_SESSION['CompanyRecord']['regoffice2'],
					'company_contact' => $_SESSION['CompanyRecord']['telephone'] . ' | ' . $_SESSION['CompanyRecord']['email'],
					'title' => ($InvOrCredit == "Invoice" ? __("TAX INVOICE") : __("TAX CREDIT NOTE")),
					'number' => $FromTransNo,
					'status' => (isset($IsPaid) && $IsPaid ? 'PAID' : 'PENDING'),
					'date' => ConvertSQLDate($MyRow['trandate']),
					'due_date' => $DisplayDueDate,
					'terms' => $MyRow['terms'],
					'notes' => $MyRow['invtext']
				);

				$customer = array(
					'name' => $MyRow['name'],
					'address' => $CustomerAddress,
					'ship_to' => $MyRow['deliverto'] . '<br/>' . $DeliveryAddress
				);

				$items = array();
				if (DB_num_rows($ResultLines) > 0) {
					DB_data_seek($ResultLines, 0);
					while ($line = DB_fetch_array($ResultLines)) {
						$line_units = isset($line['units']) ? $line['units'] : '';
						$line_decimals = isset($line['decimalplaces']) ? $line['decimalplaces'] : 2;
						
						$items[] = array(
							'code' => $line['stockid'],
							'description' => $line['description'],
							'narrative' => $line['narrative'],
							'qty' => locale_number_format($line['quantity'], $line_decimals) . ' ' . $line_units,
							'price' => locale_number_format($line['fxprice'], $MyRow['decimalplaces']),
							'total' => locale_number_format($line['fxnet'], $MyRow['decimalplaces'])
						);
					}
				}

				// FALLBACK: If no stock items found, use the transaction reference/comments (Service Invoices)
				if (count($items) == 0 && (strcasecmp($InvOrCredit, 'Invoice') == 0 || strcasecmp($InvOrCredit, 'Credit') == 0)) {
					$items[] = array(
						'code' => __('SERVICE'),
						'description' => (trim($MyRow['invtext']) != '' ? $MyRow['invtext'] : (trim($MyRow['reference']) != '' ? $MyRow['reference'] : __('General Service Charge'))),
						'narrative' => '',
						'qty' => '1',
						'price' => $DisplaySubTot,
						'total' => $DisplaySubTot
					);
				}

				$totals = array(
					'subtotal' => $DisplaySubTot,
					'freight' => $DisplayFreight,
					'tax' => $DisplayTax,
					'paid' => ($AmountPaid > 0 ? locale_number_format($AmountPaid, $MyRow['decimalplaces']) : null),
					'total' => $MyRow['currcode'] . ' ' . $DisplayTotal
				);

				// Render Template
				$is_pdf = (isset($_GET['PrintPDF']) && $_GET['PrintPDF'] == 'True');
				ob_start();
				include 'InvoiceTemplate.php';
				$HTML = ob_get_clean();
			}
		}
		$FromTransNo++;
	}
	// Handle Output
	if (isset($_GET['PrintPDF']) && $_GET['PrintPDF'] == 'True') {
		if ($InvOrCredit == 'Receipt') {
			header('Location: ' . $RootPath . '/TRAReceipt.php?BatchNumber=' . ($FromTransNo - 1) . (isset($_GET['Download']) ? '&Download=True' : ''));
			exit;
		}
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], ($IsThermal ? 'portrait' : $Orientation));
		$DomPDF->render();
		if (ob_get_length()) ob_end_clean();
		$Attachment = (isset($_GET['Download']) && $_GET['Download'] == 'True') ? 1 : 0;
		$DomPDF->stream($InvOrCredit . '_' . ($FromTransNo - 1) . '.pdf', array('Attachment' => $Attachment));
		exit;
	} elseif (isset($_GET['View']) && $_GET['View'] == 'Yes') {
		if (empty($HTML)) {
			// Thermal Error Screen (Standalone)
			if (isset($_GET['Receipt'])) {
				echo '<!DOCTYPE html>
					<html>
					<head>
						<meta charset="UTF-8">
						<style>
							body { font-family: sans-serif; background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
							.error-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 400px; }
							h1 { font-size: 48px; margin-bottom: 20px; }
							h2 { color: #1e293b; margin-bottom: 10px; }
							p { color: #64748b; line-height: 1.6; }
							.btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #3b82f6; color: white; text-decoration: none; border-radius: 12px; font-weight: 700; }
						</style>
					</head>
					<body>
						<div class="error-card">
							<h1>📄</h1>
							<h2>' . __('Transaction Not Found') . '</h2>
							<p>' . __('The requested ' . $InvOrCredit . ' #' . ($FromTransNo-1) . ' could not be found.') . '</p>
							<a href="' . $RootPath . '/CustomerInquiry.php" class="btn">' . __('Back to Inquiry') . '</a>
						</div>
					</body>
					</html>';
				exit;
			}
			
			// Dashboard Error Screen (With Sidebar)
			include(__DIR__ . '/includes/header.php');
			echo '<div style="padding: 100px; text-align: center; font-family: sans-serif; color: #64748b;">
					<h1 style="font-size: 64px; margin-bottom: 20px;">📄</h1>
					<h2 style="color: #1e293b;">' . __('Transaction Not Found') . '</h2>
					<p>' . __('The requested ' . $InvOrCredit . ' #' . ($FromTransNo-1) . ' could not be found in the system.') . '</p>
					<br/><a href="' . $RootPath . '/CustomerInquiry.php" class="btn btn-primary" style="padding: 12px 24px; border-radius: 12px; background: #2563eb; color: white; text-decoration: none; font-weight: 700;">' . __('Back to Customer Inquiry') . '</a>
				  </div>';
			include(__DIR__ . '/includes/footer.php');
		} else {
			// Do NOT include ERP header/footer for standalone templates as they provide their own HTML structure
			if (strcasecmp($InvOrCredit, 'Receipt') == 0 || isset($_GET['WithMenu'])) {
				include(__DIR__ . '/includes/header.php');
				echo $HTML;
				include(__DIR__ . '/includes/footer.php');
			} else {
				echo $HTML;
			}
		}
		exit;
	} elseif (isset($_GET['Email'])) {
		$PdfFileName = $_SESSION['DatabaseName'] . '_' . $InvOrCredit . '_' . ($FromTransNo-1) .'_'. date('Y-m-d') . '.pdf';
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], ($IsThermal ? 'portrait' : 'landscape'));
		$DomPDF->render();
		file_put_contents($PdfFileName, $DomPDF->output());
		include(__DIR__ . '/EmailCustTrans.php');
		exit;
	}
} else {
	// Standard webform for selecting invoices
	$Title = __('Print Invoices or Credit Notes');
	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
			<form action="' . $_SERVER['PHP_SELF'] . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div style="padding: 40px; text-align: center;">
					<h2>' . __('Select Document to Print') . '</h2>
					<p>' . __('Please use the Customer Inquiry page to select specific documents for printing.') . '</p>
					<br/><a href="' . $RootPath . '/CustomerInquiry.php" class="btn btn-primary">' . __('Go to Inquiry') . '</a>
				</div>
			</form>
		  </div>';
	include(__DIR__ . '/includes/footer.php');
}
