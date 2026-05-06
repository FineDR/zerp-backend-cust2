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

// Suppress sidebar ONLY for PDF/Print views or if explicitly requested
if (isset($_GET['PrintPDF']) || (isset($_GET['Receipt']) && !isset($_GET['View']) && !isset($_GET['WithMenu']))) {
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
			$IsThermal = ($InvOrCredit == 'Receipt' || (isset($_GET['View']) && isset($_GET['Receipt'])));
			
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
			$ErrMsgLines = __('There was a problem retrieving the transaction details');
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
						.items-table th { text-align: left; border-bottom: 1px dashed #000; padding-bottom: 5px; font-size: 11px; }
						.items-table td { padding: 5px 0; vertical-align: top; font-size: 12px; }
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
						<a href="' . $RootPath . '/TRAReceipt.php?BatchNumber=' . ($FromTransNo-1) . '" class="btn btn-primary">
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
			} elseif (isset($_GET['View']) && $_GET['View'] == 'Yes') {
				// ====================================================================
				// PART 1: INTERACTIVE INVOICE UI (Dashboard View)
				// ====================================================================
				$HTML = '
					<style>
						:root {
							--primary: #059669; /* Green instead of Blue */
							--primary-dark: #047857;
							--primary-light: #ecfdf5;
							--success: #059669;
							--slate-50: #f8fafc;
							--slate-100: #f1f5f9;
							--slate-200: #e2e8f0;
							--slate-700: #334155;
							--slate-800: #1e293b;
							--slate-900: #0f172a;
							--border: #e2e8f0;
							--text-main: #334155;
							--text-muted: #64748b;
							--radius: 12px;
							--shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
						}

					.dashboard-content {
						padding: 20px;
						font-family: "Inter", system-ui, sans-serif;
						color: var(--text-main);
						background: #f8fafc;
					}

						.invoice-card {
							background: white;
							border-radius: var(--radius);
							border: 1px solid var(--border);
							box-shadow: var(--shadow);
							overflow: hidden;
							margin-bottom: 20px;
							max-width: 1000px;
							margin-left: auto;
							margin-right: auto;
						}

						.invoice-header {
							padding: 20px 30px;
							background: var(--slate-900);
							color: white;
							display: flex;
							justify-content: space-between;
							align-items: center;
						}

						.badge {
							padding: 5px 10px;
							border-radius: 20px;
							font-size: 11px;
							font-weight: 700;
							text-transform: uppercase;
						}
						.badge-success { background: #dcfce7; color: #166534; }
						.badge-pending { background: #fef9c3; color: #854d0e; }

						.invoice-body { padding: 30px; }

						.info-grid {
							display: grid;
							grid-template-columns: repeat(3, 1fr);
							gap: 20px;
							margin-bottom: 25px;
						}

						.info-section h4 {
							font-size: 10px;
							text-transform: uppercase;
							color: var(--text-muted);
							margin-bottom: 6px;
							letter-spacing: 0.5px;
						}

						.info-section p { margin: 0; font-weight: 600; font-size: 14px; color: var(--slate-800); }

						.items-table {
							width: 100%;
							border-collapse: collapse;
							margin-bottom: 20px;
						}

						.items-table th {
							text-align: left;
							padding: 12px;
							background: var(--slate-50);
							border-bottom: 2px solid var(--border);
							color: var(--text-muted);
							font-size: 11px;
							text-transform: uppercase;
						}

						.items-table td {
							padding: 12px;
							border-bottom: 1px solid var(--border);
							font-size: 13px;
						}

						.summary-section {
							display: flex;
							justify-content: flex-end;
							padding-top: 15px;
						}

						.summary-table {
							width: 320px;
						}

						.summary-table tr td {
							padding: 6px 0;
							font-size: 13px;
						}

						.summary-table tr td:last-child {
							text-align: right;
							font-weight: 600;
						}

						.summary-table tr.grand-total td {
							border-top: 2px solid var(--slate-900);
							padding-top: 12px;
							font-size: 18px;
							font-weight: 800;
							color: var(--slate-900);
						}
						
						.summary-table tr.payment-row td {
							color: var(--success);
						}

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
						
						.btn-floating {
							display: inline-flex;
							align-items: center;
							padding: 8px 16px;
							background: white;
							color: var(--slate-900);
							text-decoration: none;
							border-radius: 30px;
							font-weight: 600;
							font-size: 12px;
							transition: all 0.2s;
							border: none;
							cursor: pointer;
						}
						
						.btn-floating:hover { background: var(--slate-200); transform: translateY(-1px); }
						.btn-primary-float { background: var(--primary); color: white; }

						@media print {
							.sticky-footer, .no-print, .ModuleList, header, footer, .sidebar-mask, #SidebarToggle, .help-bubble { display: none !important; }
							body { background: white !important; padding: 0 !important; margin: 0 !important; }
							.dashboard-content { background: white !important; padding: 0 !important; margin: 0 !important; width: 100% !important; display: block !important; }
							.invoice-card { box-shadow: none !important; border: none !important; max-width: 100% !important; width: 100% !important; margin: 0 !important; }
							.invoice-header { background: white !important; color: black !important; border-bottom: 2px solid black; padding: 10px 0; }
							.invoice-body { padding: 20px 0; }
							.summary-table tr.grand-total td { border-top: 2px solid black; }
						}
				</style>
				<div class="dashboard-content">
					<div class="invoice-card">
							<div class="invoice-header">
								<div>
									<h2 style="margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px;">' . $InvOrCredit . '</h2>
									<p style="margin: 5px 0 0; opacity: 0.8; font-size: 12px;">#' . $FromTransNo . ' / ' . $MyRow['trandate'] . '</p>
								</div>
								<div class="badge ' . ($AmountPaid >= $TotalAmount ? 'badge-success' : 'badge-pending') . '">
									' . ($AmountPaid >= $TotalAmount ? __('PAID') : __('PENDING')) . '
								</div>
							</div>

							<div class="invoice-body">
								<div class="info-grid">
									<div class="info-section">
										<h4>' . __('Customer') . '</h4>
										<p>' . $MyRow['name'] . '</p>
										<div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">' . $CustomerAddress . '</div>
									</div>
									<div class="info-section">
										<h4>' . __('Shipping To') . '</h4>
										<p>' . $MyRow['deliverto'] . '</p>
										<div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">' . $DeliveryAddress . '</div>
									</div>
									<div class="info-section">
										<h4>' . __('Payment Details') . '</h4>
										<p>' . $MyRow['terms'] . '</p>
										<div style="font-size: 12px; color: var(--text-muted); margin-top: 5px;">' . __('Sales Person') . ': ' . $getVal($MyRow['salesmanname']) . '</div>
									</div>
								</div>

								<table class="items-table">
									<thead>
										' . ($InvOrCredit == 'Receipt' ? '
										<tr>
											<th>' . __('Original Ref') . '</th>
											<th>' . __('Trans Type & Date') . '</th>
											<th style="text-align: right;">' . __('Allocated') . '</th>
											<th style="text-align: right;">' . __('Running Total') . '</th>
										</tr>' : '
										<tr>
											<th>' . __('Code') . '</th>
											<th>' . __('Description') . '</th>
											<th style="text-align: right;">' . __('Qty') . '</th>
											<th style="text-align: right;">' . __('Price') . '</th>
											<th style="text-align: right;">' . __('Disc') . '</th>
											<th style="text-align: right;">' . __('Total') . '</th>
										</tr>') . '
									</thead>
									<tbody>';

				if (DB_num_rows($ResultLines) > 0) {
					DB_data_seek($ResultLines, 0);
					while ($MyRow2 = DB_fetch_array($ResultLines)) {
						$DisplayPrice = locale_number_format($MyRow2['fxprice'], $MyRow['decimalplaces']);
						$DisplayQty = locale_number_format($MyRow2['quantity'], $MyRow2['decimalplaces']);
						$DisplayNet = locale_number_format($MyRow2['fxnet'], $MyRow['decimalplaces']);
						$DisplayDiscount = locale_number_format($MyRow2['discountpercent'] * 100, 1) . '%';

						if ($InvOrCredit == 'Receipt') {
							$HTML .= '<tr>
										<td class="font-bold">' . $MyRow2['stockid'] . '</td>
										<td>' . $MyRow2['description'] . ' - ' . ConvertSQLDate($MyRow2['trandate']) . '</td>
										<td class="text-right">' . locale_number_format($MyRow2['quantity'], $MyRow['decimalplaces']) . '</td>
										<td class="text-right font-bold">' . locale_number_format($MyRow2['fxprice'], $MyRow['decimalplaces']) . '</td>
									</tr>';
						} else {
							$HTML .= '<tr>
										<td>' . $MyRow2['stockid'] . '</td>
										<td>
											<div style="font-weight:600;">' . $MyRow2['description'] . '</div>';
							if ($MyRow2['narrative'] != '') {
								$HTML .= '<div style="font-size:11px; color:var(--text-muted); margin-top:4px;">' . nl2br($MyRow2['narrative']) . '</div>';
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
					$HTML .= '<tr><td colspan="6" style="padding: 40px; text-align: center; color: var(--text-muted);">' . __('No items available') . '</td></tr>';
				}

				$HTML .= '  	</tbody>
							</table>
						</div>

						<div class="summary-section">
							<table class="summary-table">
								<tr>
									<td>' . __('Sub Total') . '</td>
									<td>' . $DisplaySubTot . '</td>
								</tr>
								<tr>
									<td>' . __('Freight') . '</td>
									<td>' . $DisplayFreight . '</td>
								</tr>
								<tr>
									<td>' . __('Tax') . '</td>
									<td>' . $DisplayTax . '</td>
								</tr>
								<tr style="border-top:1px solid var(--border);">
									<td>' . __('Total Amount') . '</td>
									<td>' . locale_number_format($TotalAmount, $MyRow['decimalplaces']) . '</td>
								</tr>';
								
				if ($AmountPaid > 0) {
					$HTML .= '<tr class="payment-row">
								<td>' . __('Amount Paid') . '</td>
								<td>' . locale_number_format($AmountPaid, $MyRow['decimalplaces']) . '</td>
							  </tr>';
				}

				$HTML .= '	<tr class="grand-total">
								<td>' . ($InvOrCredit == "Receipt" ? __("Total Received") : __("Total Due")) . '</td>
								<td>' . $MyRow['currcode'] . ' ' . ($InvOrCredit == "Receipt" ? $DisplayTotal : locale_number_format($BalanceDue, $MyRow['decimalplaces'])) . '</td>
							</tr>
							<tr>
								<td colspan="2" style="padding-top: 10px; font-size: 11px; color: var(--text-muted); text-align: right;">
									' . sprintf(__('Due Date: %s'), $DisplayDueDate) . '
								</td>
							</tr>
						</table>
					</div>

					<div class="sticky-footer no-print">
						<a href="' . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&PrintPDF=True" class="btn-floating btn-primary-float">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"></path></svg>
							Download PDF
						</a>
						<button onclick="window.print()" class="btn-floating">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
							Print Document
						</button>
					</div>
				</div>';
				
				$HTML .= '';
			} else {
				// ====================================================================
				// PART 2: PRINT / PDF INVOICE (Minimalist Document View)
				// ====================================================================
				$HTML = '<html>
				<head>
					<meta charset="UTF-8">
					<style>
						@page { margin: 30px; }
						body { 
							font-family: "Helvetica", "Arial", sans-serif; 
							font-size: 10px; 
							color: #1e293b; 
							line-height: 1.5;
							background: #fff;
							margin: 0;
						}
						.container { width: 100%; }
						
						.header-table { width: 100%; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px; }
						.logo { height: 50px; max-width: 200px; }
						.document-title { 
							font-size: 22px; 
							font-weight: 900; 
							text-align: right; 
							text-transform: uppercase;
							letter-spacing: 1px;
						}
						.meta-table { width: 100%; margin-top: 5px; font-size: 10px; }
						.meta-table td { text-align: right; padding: 2px 0; }
						.meta-label { font-weight: bold; color: #444; }
						
						.address-table { width: 100%; margin-bottom: 20px; table-layout: fixed; }
						.address-box { vertical-align: top; padding-right: 15px; }
						.address-label { 
							font-size: 8px; 
							text-transform: uppercase; 
							font-weight: bold; 
							color: #000; 
							margin-bottom: 5px; 
							border-bottom: 1px solid #000;
							display: block;
							padding-bottom: 1px;
						}
						
						.info-bar { 
							width: 100%; 
							background: #f3f4f6; 
							margin-bottom: 20px;
							border-top: 1px solid #ccc;
							border-bottom: 1px solid #ccc;
						}
						.info-bar td { padding: 6px 8px; text-align: center; border-right: 1px solid #ccc; font-size: 8px; }
						.info-bar td:last-child { border-right: none; }
						.info-label { display: block; font-weight: bold; font-size: 6px; text-transform: uppercase; color: #666; margin-bottom: 1px; }

						.items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
						.items-table th { 
							background: #000; 
							color: #fff; 
							text-transform: uppercase; 
							font-size: 8px; 
							padding: 8px 6px; 
							text-align: left;
						}
						.items-table td { 
							padding: 8px 6px; 
							border-bottom: 1px solid #eee; 
							vertical-align: top; 
						}
						.items-table tr:last-child td { border-bottom: 1px solid #000; }
						.text-right { text-align: right; }
						
						.totals-table { width: 220px; float: right; border-collapse: collapse; margin-top: 10px; }
						.totals-table td { padding: 4px 6px; font-size: 10px; border-bottom: 1px solid #f3f4f6; }
						.total-row { border-top: 2px solid #000; font-size: 13px; font-weight: bold; background: #fafafa; }
						.payment-row { color: #059669; font-style: italic; }

						.footer { clear: both; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ccc; }
						.bank-details { width: 60%; font-size: 8px; }
						.legal { font-size: 7px; color: #777; margin-top: 10px; }
					</style>
				</head>
				<body>
					<div class="container">
						<table class="header-table">
							<tr>
								<td width="60%">
									<img class="logo" src="' . $_SESSION['LogoFile'] . '" />
									<div style="font-weight:bold; font-size:12px; margin-top:5px;">' . $_SESSION['CompanyRecord']['coyname'] . '</div>
									<div>' . $_SESSION['CompanyRecord']['regoffice1'] . '</div>
									<div>' . $_SESSION['CompanyRecord']['telephone'] . ' | ' . $_SESSION['CompanyRecord']['email'] . '</div>
								</td>
								<td width="40%" style="vertical-align: top;">
									<div class="document-title">' . ($InvOrCredit == "Invoice" ? __("TAX INVOICE") : ($InvOrCredit == "Receipt" ? __("OFFICIAL RECEIPT") : __("TAX CREDIT NOTE"))) . '</div>
									<table class="meta-table">
										<tr><td class="meta-label">' . ($InvOrCredit == "Receipt" ? __("Receipt No") : __("Invoice No")) . ':</td><td>#' . $FromTransNo . '</td></tr>
										<tr><td class="meta-label">' . __("Date") . ':</td><td>' . ConvertSQLDate($MyRow['trandate']) . '</td></tr>
										<tr><td class="meta-label">' . __("Due Date") . ':</td><td>' . $DisplayDueDate . '</td></tr>
									</table>
								</td>
							</tr>
						</table>

						<table class="address-table">
							<tr>
								<td class="address-box">
									<span class="address-label">' . __('Bill To') . '</span>
									<div style="font-weight:bold; font-size:11px;">' . $MyRow['name'] . '</div>
									' . $getVal($CustomerAddress) . '
								</td>
								<td class="address-box">
									<span class="address-label">' . __('Ship To') . '</span>
									' . $getVal($MyRow['deliverto'] . '<br/>' . $DeliveryAddress) . '
								</td>
								<td class="address-box" style="padding-right:0;">
									<span class="address-label">' . __('Payment Terms') . '</span>
									' . $getVal($MyRow['terms']) . '
								</td>
							</tr>
						</table>

						' . ($InvOrCredit == 'Invoice' ? '
						<table class="info-bar">
							<tr>
								<td><span class="info-label">' . __('Your Ref') . '</span>' . $getVal($MyRow['customerref']) . '</td>
								<td><span class="info-label">' . __('Our Order') . '</span>' . $getVal($MyRow['orderno']) . '</td>
								<td><span class="info-label">' . __('Sales Person') . '</span>' . $getVal($MyRow['salesmanname']) . '</td>
								<td><span class="info-label">' . __('Shipper') . '</span>' . $getVal($MyRow['shippername']) . '</td>
							</tr>
						</table>' : '') . '

						<table class="items-table">
							<thead>
								' . ($InvOrCredit == 'Receipt' ? '
								<tr>
									<th width="20%">' . __('Original Ref') . '</th>
									<th width="45%">' . __('Description') . '</th>
									<th style="text-align: right;">' . __('Allocated') . '</th>
									<th style="text-align: right;">' . __('Total') . '</th>
								</tr>' : '
								<tr>
									<th width="15%">' . __('Code') . '</th>
									<th width="40%">' . __('Description') . '</th>
									<th style="text-align: right;">' . __('Qty') . '</th>
									<th style="text-align: right;">' . __('Price') . '</th>
									<th style="text-align: right;">' . __('Total') . '</th>
								</tr>') . '
							</thead>
							<tbody>';

				if (DB_num_rows($ResultLines) > 0) {
					DB_data_seek($ResultLines, 0); 
					while ($MyRow2 = DB_fetch_array($ResultLines)) {
						$DisplayPrice = locale_number_format($MyRow2['fxprice'], $MyRow['decimalplaces']);
						$DisplayQty = locale_number_format($MyRow2['quantity'], $MyRow2['decimalplaces']);
						$DisplayNet = locale_number_format($MyRow2['fxnet'], $MyRow['decimalplaces']);

						if ($InvOrCredit == 'Receipt') {
							$HTML .= '<tr>
										<td>' . $MyRow2['stockid'] . '</td>
										<td>' . $MyRow2['description'] . ' (' . ConvertSQLDate($MyRow2['trandate']) . ')</td>
										<td class="text-right">' . $DisplayNet . '</td>
										<td class="text-right">' . $DisplayNet . '</td>
									</tr>';
						} else {
							$HTML .= '<tr>
										<td>' . $MyRow2['stockid'] . '</td>
										<td>' . $MyRow2['description'] . '</td>
										<td class="text-right">' . $DisplayQty . '</td>
										<td class="text-right">' . $DisplayPrice . '</td>
										<td class="text-right" style="font-weight:bold;">' . $DisplayNet . '</td>
									</tr>';
						}
					}
				}

				$HTML .= '  	</tbody>
						</table>

						<table class="totals-table">
							<tr><td>' . __('Sub Total') . '</td><td class="text-right">' . $DisplaySubTot . '</td></tr>
							<tr><td>' . __('Freight') . '</td><td class="text-right">' . $DisplayFreight . '</td></tr>
							<tr><td>' . __('Tax') . '</td><td class="text-right">' . $DisplayTax . '</td></tr>
							<tr style="border-top:1px solid #e2e8f0; font-weight:bold;"><td>' . __('Total Amount') . '</td><td class="text-right">' . locale_number_format($TotalAmount, $MyRow['decimalplaces']) . '</td></tr>';
							
				if ($AmountPaid > 0) {
					$HTML .= '<tr class="payment-row"><td>' . __('Amount Paid') . '</td><td class="text-right">(' . locale_number_format($AmountPaid, $MyRow['decimalplaces']) . ')</td></tr>';
				}

				$HTML .= '	<tr class="total-row" style="background:#f8fafc; font-size:12px;"><td>' . ($InvOrCredit == "Receipt" ? __("TOTAL RECEIVED") : __("TOTAL DUE")) . '</td><td class="text-right">' . $MyRow['currcode'] . ' ' . ($InvOrCredit == "Receipt" ? $DisplayTotal : locale_number_format($BalanceDue, $MyRow['decimalplaces'])) . '</td></tr>
						</table>

						<div class="footer">
							<table width="100%">
								<tr>
									<td class="bank-details">
										<div style="font-weight:bold; margin-bottom:5px;">' . __('Payment Instructions') . '</div>
										' . $DefaultBankAccountCode . ' ' . $DefaultBankAccountNumber . '
										<div class="legal">' . ($_SESSION['RomalpaClause'] ?? '') . '</div>
									</td>
									<td style="text-align:right; vertical-align:bottom; font-size:8px; color:#999;">
										' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '
									</td>
								</tr>
							</table>
						</div>
					</div>
				</body>
				</html>';
			}
		}
		$FromTransNo++;
	}
	// Handle Output
	if (isset($_GET['PrintPDF']) && $_GET['PrintPDF'] == 'True') {
		if ($InvOrCredit == 'Receipt') {
			header('Location: ' . $RootPath . '/TRAReceipt.php?BatchNumber=' . ($FromTransNo - 1));
			exit;
		}
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], ($IsThermal ? 'portrait' : 'landscape'));
		$DomPDF->render();
		$DomPDF->stream($InvOrCredit . '_' . $FromTransNo . '.pdf', array('Attachment' => 0));
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
			if (!isset($_GET['Receipt']) || isset($_GET['WithMenu'])) {
				include(__DIR__ . '/includes/header.php');
			}
			echo $HTML;
			if (!isset($_GET['Receipt']) || isset($_GET['WithMenu'])) {
				include(__DIR__ . '/includes/footer.php');
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
