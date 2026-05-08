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

	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/printer.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<fieldset>
			<legend>', __('Remittance Advice Criteria'), '</legend>';

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
	echo '<field>
			<label for="FromCriteria">' . __('From Supplier Code') . ':</label>
			<input type="text" maxlength="6" size="7" name="FromCriteria" value="' . $DefaultFromCriteria . '" />
		</field>';
	echo '<field>
			<label for="ToCriteria">' . __('To Supplier Code') . ':</label>
			<input type="text" maxlength="6" size="7" name="ToCriteria" value="' . $DefaultToCriteria . '" />
		</field>';

	if (!isset($_POST['PaymentDate'])) {
		$DefaultDate = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') + 1, 0, date('y')));
	} else {
		$DefaultDate = $_POST['PaymentDate'];
	}

	echo '<field>
			<label for="PaymentDate">' . __('Date Of Payment') . ':</label>
			<input type="date" name="PaymentDate" maxlength="10" size="11" value="' . FormatDateForSQL($DefaultDate) . '" />
		</field>';

	echo '</fieldset>
		<div class="centre">
			<input type="submit" name="PrintPDF" value="' . __('Print PDF') . '" />
		</div>';

	echo '</form>';

	include(__DIR__ . '/includes/footer.php');
}
