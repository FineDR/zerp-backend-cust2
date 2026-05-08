<?php
require (__DIR__ . '/includes/session.php');

include ('includes/SQL_CommonFunctions.php');
include ('includes/GetPaymentMethods.php');

// Add DomPDF namespace and autoload
use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

class Allocation {
	var $TransID;
	var $Amount;

	function __construct($TransID, $Amount) {
		$this->TransID = $TransID;
		$this->Amount = $Amount;
	}
}

if (isset($_POST['AmountsDueBy'])) {
	$_POST['AmountsDueBy'] = ConvertSQLDate($_POST['AmountsDueBy']);
}

if ((isset($_POST['PrintPDF']) or isset($_POST['PrintPDFAndProcess'])) and isset($_POST['FromCriteria']) and mb_strlen($_POST['FromCriteria']) >= 1 and isset($_POST['ToCriteria']) and mb_strlen($_POST['ToCriteria']) >= 1 and is_numeric(filter_number_format($_POST['ExRate']))) {

	// Start HTML for PDF
	$HTML = '<html><head><style>
		@page { margin: 30px; }
		body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
		.header-container { margin-bottom: 20px; border-bottom: 2px solid #059669; padding-bottom: 10px; }
		.document-title { text-align: center; font-weight: 900; font-size: 16pt; text-transform: uppercase; margin: 10px 0; }
		table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
		th { background: #f9fafb; color: #4b5563; font-size: 8pt; text-transform: uppercase; padding: 8px; border-bottom: 1px solid #e5e7eb; text-align: left; }
		td { padding: 8px; border-bottom: 1px solid #f3f4f6; font-size: 8pt; }
		.text-right { text-align: right; }
		.totals-row { background: #f0fdf4; font-weight: 800; color: #064e3b; }
	</style></head><body>';

	$HTML .= '<div class="header-container"><strong>' . $_SESSION['CompanyRecord']['coyname'] . '</strong></div>';
	$HTML .= '<div class="document-title">' . __('Payment Run Report') . '</div>';
	$HTML .= '<p style="font-size:9pt; color:#666;">' . __('Suppliers from') . ' ' . $_POST['FromCriteria'] . ' ' . __('to') . ' ' . $_POST['ToCriteria'] . ' ' . __('in') . ' ' . $_POST['Currency'] . ' ' . __('and Due By') . ' ' . $_POST['AmountsDueBy'] . '</p>';

	$HTML .= '<table><thead>
		<tr>
			<th>' . __('Supplier') . '</th>
			<th>' . __('Name') . '</th>
			<th>' . __('Terms') . '</th>
			<th>' . __('Date') . '</th>
			<th>' . __('Type') . '</th>
			<th>' . __('Reference') . '</th>
			<th class="text-right">' . __('Balance') . '</th>
			<th class="text-right">' . __('Exchange Diff') . '</th>
		</tr>
	</thead><tbody>';

	$SQL = "SELECT suppliers.supplierid,
					currencies.decimalplaces AS currdecimalplaces,
					SUM(supptrans.balance) AS balance
			FROM suppliers INNER JOIN paymentterms
			ON suppliers.paymentterms = paymentterms.termsindicator
			INNER JOIN supptrans
			ON suppliers.supplierid = supptrans.supplierno
			INNER JOIN systypes
			ON systypes.typeid = supptrans.type
			INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
			WHERE supptrans.balance !=0
			AND supptrans.duedate <='" . FormatDateForSQL($_POST['AmountsDueBy']) . "'
			AND supptrans.hold=0
			AND suppliers.currcode = '" . $_POST['Currency'] . "'
			AND supptrans.supplierno >= '" . $_POST['FromCriteria'] . "'
			AND supptrans.supplierno <= '" . $_POST['ToCriteria'] . "'
			GROUP BY suppliers.supplierid,
					currencies.decimalplaces
			HAVING SUM(supptrans.balance) > 0
			ORDER BY suppliers.supplierid";

	$SuppliersResult = DB_query($SQL);

	if (isset($_POST['PrintPDFAndProcess'])) {
		DB_Txn_Begin();
	}

	$AccumBalance = 0;
	$AccumDiffOnExch = 0;
	while ($SuppliersToPay = DB_fetch_array($SuppliersResult)) {

		$CurrDecimalPlaces = $SuppliersToPay['currdecimalplaces'];

		$SQL = "SELECT suppliers.supplierid,
						suppliers.suppname,
						systypes.typename,
						paymentterms.terms,
						supptrans.suppreference,
						supptrans.trandate,
						supptrans.rate,
						supptrans.transno,
						supptrans.type,
						(supptrans.balance) AS balance,
						(supptrans.ovamount + supptrans.ovgst ) AS trantotal,
						supptrans.diffonexch,
						supptrans.id
				FROM suppliers INNER JOIN paymentterms
				ON suppliers.paymentterms = paymentterms.termsindicator
				INNER JOIN supptrans
				ON suppliers.supplierid = supptrans.supplierno
				INNER JOIN systypes
				ON systypes.typeid = supptrans.type
				WHERE supptrans.supplierno = '" . $SuppliersToPay['supplierid'] . "'
				AND supptrans.balance !=0
				AND supptrans.duedate <='" . FormatDateForSQL($_POST['AmountsDueBy']) . "'
				AND supptrans.hold = 0
				AND suppliers.currcode = '" . $_POST['Currency'] . "'
				AND supptrans.supplierno >= '" . $_POST['FromCriteria'] . "'
				AND supptrans.supplierno <= '" . $_POST['ToCriteria'] . "'
				ORDER BY supptrans.supplierno,
					supptrans.type,
					supptrans.transno";

		$TransResult = DB_query($SQL);
		
		unset($Allocs);
		$Allocs = array();
		$AllocCounter = 0;

		while ($DetailTrans = DB_fetch_array($TransResult)) {
			$DiffOnExch = ($DetailTrans['balance'] / $DetailTrans['rate']) - ($DetailTrans['balance'] / filter_number_format($_POST['ExRate']));

			$AccumBalance += $DetailTrans['balance'];
			$AccumDiffOnExch += $DiffOnExch;

			if (isset($_POST['PrintPDFAndProcess'])) {
				$Allocs[$AllocCounter] = new Allocation($DetailTrans['id'], $DetailTrans['balance']);
				$AllocCounter++;

				$SQL = "UPDATE supptrans SET settled = 1,
												alloc = '" . $DetailTrans['trantotal'] . "',
												diffonexch = '" . ($DetailTrans['diffonexch'] + $DiffOnExch) . "'
								WHERE type = '" . $DetailTrans['type'] . "'
								AND transno = '" . $DetailTrans['transno'] . "'";
				DB_query($SQL, '', '', true);
			}

			$HTML .= '<tr>
						<td>' . $DetailTrans['supplierid'] . '</td>
						<td>' . htmlspecialchars($DetailTrans['suppname']) . '</td>
						<td>' . htmlspecialchars($DetailTrans['terms']) . '</td>
						<td>' . ConvertSQLDate($DetailTrans['trandate']) . '</td>
						<td>' . htmlspecialchars(__($DetailTrans['typename'])) . '</td>
						<td>' . htmlspecialchars($DetailTrans['suppreference']) . '</td>
						<td class="text-right">' . locale_number_format($DetailTrans['balance'], $CurrDecimalPlaces) . '</td>
						<td class="text-right">' . locale_number_format($DiffOnExch, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					</tr>';
		}
	}

	$HTML .= '</tbody><tfoot>
				<tr class="totals-row">
					<td colspan="6" class="text-right"><strong>' . __('GRAND TOTAL') . ':</strong></td>
					<td class="text-right"><strong>' . locale_number_format($AccumBalance, $CurrDecimalPlaces) . '</strong></td>
					<td class="text-right"><strong>' . locale_number_format($AccumDiffOnExch, $_SESSION['CompanyRecord']['decimalplaces']) . '</strong></td>
				</tr>
			</tfoot></table>';

	$HTML .= '</body></html>';

	if (isset($_POST['PrintPDFAndProcess'])) {
		DB_Txn_Commit();
	}

	$DomPDF = new Dompdf($DomPDFOptions);
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
	$DomPDF->render();
	$DomPDF->stream($_SESSION['DatabaseName'] . '_Payment_Run_' . date('Y-m-d_His') . '.pdf', array('Attachment' => false));

} else {
	$Title = __('Payment Run');
	$ViewTopic = 'AccountsPayable';
	$BookMark = '';
	include ('includes/header.php');

	if (isset($_POST['Currency']) and !is_numeric(filter_number_format($_POST['ExRate']))) {
		prnMsg(__('To process payments for') . ' ' . $_POST['Currency'] . ' ' . __('a numeric exchange rate must be entered'), 'error');
	}

	// Default criteria
	$SQL = "SELECT supplierid FROM suppliers ORDER BY supplierid";
	$Result = DB_query($SQL);
	$SupplierRow = DB_fetch_array($Result);
	$DefaultFromCriteria = $_POST['FromCriteria'] ?? $SupplierRow['supplierid'];

	$SQL = "SELECT supplierid FROM suppliers ORDER BY supplierid DESC";
	$Result = DB_query($SQL);
	$SupplierRow = DB_fetch_array($Result);
	$DefaultToCriteria = $_POST['ToCriteria'] ?? $SupplierRow['supplierid'];

	$DefaultExRate = isset($_POST['ExRate']) ? filter_number_format($_POST['ExRate']) : '1';
	$DefaultDate = isset($_POST['AmountsDueBy']) ? FormatDateForSQL($_POST['AmountsDueBy']) : date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 0, date('y')));

	echo '<div class="db-page">
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title">
						<i class="fas fa-file-invoice-dollar" style="margin-right:8px; color:var(--primary);"></i>
						' . __('Supplier Payment Run Configuration') . '
					</h3>
				</div>
				<div class="db-card-body">
					<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
							<div class="db-field-group">
								<h4 style="margin-bottom:15px; color:var(--primary);">' . __('Supplier Selection') . '</h4>
								<div class="db-field">
									<label class="db-label">' . __('From Supplier Code') . '</label>
									<input type="text" name="FromCriteria" value="' . $DefaultFromCriteria . '" />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('To Supplier Code') . '</label>
									<input type="text" name="ToCriteria" value="' . $DefaultToCriteria . '" />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Payments Due To') . '</label>
									<input type="date" name="AmountsDueBy" value="' . $DefaultDate . '" />
								</div>
							</div>
							
							<div class="db-field-group">
								<h4 style="margin-bottom:15px; color:var(--primary);">' . __('Financial Settings') . '</h4>
								<div class="db-field">
									<label class="db-label">' . __('Currency') . '</label>
									<select name="Currency">';
	$SQL = "SELECT currency, currabrev FROM currencies";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ' . ($MyRow['currabrev'] == $_SESSION['CompanyRecord']['currencydefault'] ? 'selected="selected"' : '') . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '					</select>
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Exchange Rate') . '</label>
									<input type="text" class="number" name="ExRate" value="' . locale_number_format($DefaultExRate, 'Variable') . '" />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Pay From Account') . '</label>
									<select name="BankAccount">';
	$SQL = "SELECT bankaccountname, accountcode FROM bankaccounts";
	$AccountsResults = DB_query($SQL);
	while ($MyRow = DB_fetch_array($AccountsResults)) {
		echo '<option ' . (isset($_POST['BankAccount']) && $_POST['BankAccount'] == $MyRow['accountcode'] ? 'selected="selected"' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['bankaccountname'] . '</option>';
	}
	echo '					</select>
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Payment Type') . '</label>
									<select name="PaytType">';
	foreach ($PaytTypes as $PaytType) {
		echo '<option ' . (isset($_POST['PaytType']) && $_POST['PaytType'] == $PaytType ? 'selected="selected"' : '') . ' value="' . $PaytType . '">' . $PaytType . '</option>';
	}
	echo '					</select>
								</div>
							</div>
						</div>

						<div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid var(--border-soft); display: flex; gap: 15px; justify-content: flex-end;">
							<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary" style="padding: 12px 30px;">
								<i class="fas fa-print" style="margin-right:8px;"></i> ' . __('Print PDF Only') . '
							</button>
							<button type="submit" name="PrintPDFAndProcess" class="db-btn db-btn-primary" style="padding: 12px 30px;">
								<i class="fas fa-check-circle" style="margin-right:8px;"></i> ' . __('Print and Process Payments') . '
							</button>
						</div>
					</form>
				</div>
			</div>
		  </div>';
	include ('includes/footer.php');
}
