<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Offers');
$ViewTopic = 'SupplierTenders';
$BookMark = 'SupplierOffers';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// Architectural Workspace Design System v2
echo '
<style>
	:root {
		--primary: hsl(145, 63%, 38%); 
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-bg: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--text-main: hsl(145, 15%, 12%);
		--text-muted: hsl(145, 8%, 50%);
		--card-bg: #ffffff;
		--border-color: hsl(220, 15%, 88%);
		--radius: 12px;
		--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
	}

	body {
		background-color: var(--bg-workspace);
		font-family: "Inter", -apple-system, sans-serif;
		color: var(--text-main);
	}

	.aw-container {
		padding: 12px;
		max-width: 1400px;
		margin: 0 auto;
	}

	.aw-page-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
	}

	.aw-breadcrumb {
		font-size: 0.7rem;
		font-weight: 800;
		color: var(--primary);
		text-transform: uppercase;
		letter-spacing: 0.1em;
		margin-bottom: 2px;
	}

	.aw-page-title {
		font-size: 1.6rem;
		font-weight: 950;
		letter-spacing: -0.04em;
		color: var(--primary-dark);
		margin: 0;
	}

	.aw-card {
		background: var(--card-bg);
		border-radius: var(--radius);
		border: 1px solid var(--border-color);
		box-shadow: var(--shadow-sm);
		overflow: hidden;
		margin-bottom: 20px;
	}

	.aw-card-header {
		padding: 10px 16px;
		border-bottom: 1px solid var(--border-color);
		background-color: #ffffff;
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.aw-card-title {
		font-size: 0.8rem;
		font-weight: 850;
		color: var(--primary-dark);
		text-transform: uppercase;
		letter-spacing: 0.05em;
		margin: 0;
	}

	.aw-card-body {
		padding: 12px;
	}

	.aw-table-wrapper {
		overflow-x: auto;
		width: 100%;
	}

	.aw-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 0.8rem;
	}

	.aw-table th {
		text-align: left;
		padding: 10px 14px;
		background: #fbfcfd;
		color: var(--text-muted);
		font-weight: 800;
		text-transform: uppercase;
		font-size: 0.62rem;
		letter-spacing: 0.05em;
		border-bottom: 1px solid var(--border-color);
	}

	.aw-table td {
		padding: 10px 14px;
		border-bottom: 1px solid #f1f5f9;
		vertical-align: middle;
	}

	.aw-table tr:hover td {
		background-color: #f8fafc;
	}

	.aw-label {
		display: block;
		font-size: 0.7rem;
		font-weight: 850;
		color: var(--primary-dark);
		text-transform: uppercase;
		margin-bottom: 4px;
	}

	.aw-select {
		width: 100%;
		padding: 8px 12px;
		border-radius: 8px;
		border: 1px solid var(--border-color);
		font-size: 0.82rem;
		font-weight: 500;
		outline: none;
		background: #fff;
	}

	.aw-radio-group {
		display: flex;
		gap: 12px;
		justify-content: center;
	}

	.aw-radio-label {
		display: flex;
		align-items: center;
		gap: 6px;
		font-size: 0.72rem;
		font-weight: 750;
		cursor: pointer;
	}

	.aw-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 8px 16px;
		border-radius: 8px;
		font-weight: 750;
		font-size: 0.8rem;
		cursor: pointer;
		transition: all 0.2s;
		border: none;
		text-decoration: none;
	}

	.aw-btn-primary {
		background: var(--primary);
		color: white;
	}

	.aw-btn-primary:hover {
		background: var(--primary-hover);
		transform: translateY(-1px);
	}

	.aw-btn-secondary {
		background: #f8fafc;
		border: 1px solid var(--border-color);
		color: var(--text-main);
	}

	.aw-btn-secondary:hover {
		background: #f1f5f9;
	}

	.aw-badge {
		padding: 2px 8px;
		border-radius: 999px;
		font-size: 0.65rem;
		font-weight: 800;
	}

	.aw-badge-info { background: #eff6ff; color: #1d4ed8; }
</style>
<div class="aw-container">';

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Purchasing / Tenders</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

if (isset($_POST['supplierid'])) {
	$SQL = "SELECT suppname, email, currcode, paymentterms FROM suppliers WHERE supplierid = '" . $_POST['supplierid'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$SupplierName = $MyRow['suppname'];
	$Email = $MyRow['email'];
	$CurrCode = $MyRow['currcode'];
	$PaymentTerms = $MyRow['paymentterms'];
}

if (!isset($_POST['supplierid'])) {
	$SQL = "SELECT DISTINCT offers.supplierid, suppliers.suppname FROM offers LEFT JOIN purchorderauth ON offers.currcode = purchorderauth.currabrev LEFT JOIN suppliers ON suppliers.supplierid = offers.supplierid WHERE purchorderauth.userid = '" . $_SESSION['UserID'] . "' AND offers.expirydate > CURRENT_DATE AND purchorderauth.cancreate = 0";
	$Result = DB_query($SQL);
	
	if (DB_num_rows($Result) == 0) {
		echo '<div class="aw-card">
				<div class="aw-card-body" style="text-align: center; padding: 40px; color: var(--text-muted);">
					<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3; margin-bottom: 16px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
					<p>' . __('There are no offers outstanding that you are authorised to deal with') . '</p>
				</div>
			  </div>';
	} else {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<div class="aw-card" style="max-width: 500px; margin: 0 auto;">
				<div class="aw-card-header"><h2 class="aw-card-title">' . __('Supplier Selection') . '</h2></div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Select Supplier') . '</label>
						<select name="supplierid" class="aw-select">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="' . $MyRow['supplierid'] . '">' . $MyRow['suppname'] . '</option>';
		}
		echo '			</select>
					</div>
				</div>
				<div class="aw-card-body" style="background: #fbfcfd; border-top: 1px solid var(--border-color); text-align: right;">
					<button type="submit" name="select" class="aw-btn aw-btn-primary">' . __('Enter Information') . '</button>
				</div>
			</div>
			</form>';
	}
}

if (!isset($_POST['submit']) and isset($_POST['supplierid'])) {
	$SQL = "SELECT offers.offerid, offers.tenderid, offers.supplierid, suppliers.suppname, offers.stockid, stockmaster.description, offers.quantity, offers.uom, offers.price, offers.expirydate, offers.currcode, stockmaster.decimalplaces, currencies.decimalplaces AS currdecimalplaces FROM offers INNER JOIN purchorderauth ON offers.currcode = purchorderauth.currabrev INNER JOIN suppliers ON suppliers.supplierid = offers.supplierid INNER JOIN currencies ON suppliers.currcode = currencies.currabrev LEFT JOIN stockmaster ON stockmaster.stockid = offers.stockid WHERE purchorderauth.userid = '" . $_SESSION['UserID'] . "' AND offers.expirydate >= CURRENT_DATE AND offers.supplierid = '" . $_POST['supplierid'] . "' ORDER BY offerid";
	$Result = DB_query($SQL);

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Offers from') . ' ' . $SupplierName . '</h3></div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('ID') . '</th>
							<th>' . __('Stock Item') . '</th>
							<th style="text-align:right;">' . __('Qty') . '</th>
							<th>' . __('UOM') . '</th>
							<th style="text-align:right;">' . __('Price') . '</th>
							<th style="text-align:right;">' . __('Total') . '</th>
							<th>' . __('Expires') . '</th>
							<th style="text-align:center;">' . __('Action Selection') . '</th>
						</tr>
					</thead>
					<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
			<td style="color:var(--text-muted); font-size: 0.72rem;">#' . $MyRow['offerid'] . '</td>
			<td style="font-weight:700;">' . $MyRow['description'] . ' <span class="aw-badge aw-badge-info">' . $MyRow['currcode'] . '</span></td>
			<td style="text-align:right;">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>
			<td>' . $MyRow['uom'] . '</td>
			<td style="text-align:right;">' . locale_number_format($MyRow['price'], $MyRow['currdecimalplaces']) . '</td>
			<td style="text-align:right; font-weight: 850; color: var(--primary-dark);">' . locale_number_format($MyRow['price'] * $MyRow['quantity'], $MyRow['currdecimalplaces']) . '</td>
			<td style="white-space:nowrap;">' . ConvertSQLDate($MyRow['expirydate']) . '</td>
			<td>
				<div class="aw-radio-group">
					<label class="aw-radio-label"><input type="radio" name="action' . $MyRow['offerid'] . '" value="1" /> ' . __('Accept') . '</label>
					<label class="aw-radio-label"><input type="radio" name="action' . $MyRow['offerid'] . '" value="2" /> ' . __('Reject') . '</label>
					<label class="aw-radio-label"><input type="radio" checked name="action' . $MyRow['offerid'] . '" value="3" /> ' . __('Defer') . '</label>
				</div>
			</td>
			<input type="hidden" name="supplierid" value="' . $MyRow['supplierid'] . '" />
		</tr>';
	}
	echo '				</tbody>
				</table>
			</div>
			<div class="aw-card-body" style="background: #fbfcfd; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between;">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="aw-btn aw-btn-secondary">' . __('Back to Selection') . '</a>
				<button type="submit" name="submit" class="aw-btn aw-btn-primary">' . __('Process Selections') . '</button>
			</div>
		</div>
		</form>';
} elseif (isset($_POST['submit']) and isset($_POST['supplierid'])) {
	$Accepts = array();
	$RejectsArray = array();
	$Defers = array();
	foreach ($_POST as $key => $Value) {
		if (mb_substr($key, 0, 6) == 'action') {
			$OfferID = mb_substr($key, 6);
			switch ($Value) {
				case 1: $Accepts[] = $OfferID; break;
				case 2: $RejectsArray[] = $OfferID; break;
				case 3: $Defers[] = $OfferID; break;
			}
		}
	}
	
	if (sizeOf($Accepts) > 0) {
		$MailText = __('The following offers you made have been accepted') . "\n\n";
		$SQL = "SELECT rate FROM currencies where currabrev = '" . $CurrCode . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$Rate = $MyRow['rate'];
		$OrderNo = GetNextTransNo(18);
		$SQL = "INSERT INTO purchorders (orderno, supplierno, orddate, rate, initiator, intostocklocation, deliverydate, status, stat_comment, paymentterms) VALUES ('" . $OrderNo . "', '" . $_POST['supplierid'] . "', CURRENT_DATE, '" . $Rate . "', '" . $_SESSION['UserID'] . "', '" . $_SESSION['DefaultFactoryLocation'] . "', CURRENT_DATE, '" . __('Pending') . "', '" . __('Automatically generated from tendering system') . "', '" . $PaymentTerms . "')";
		DB_query($SQL);
		foreach ($Accepts as $AcceptID) {
			$SQL = "SELECT offers.quantity, offers.price, offers.uom, stockmaster.description, stockmaster.stockid FROM offers LEFT JOIN stockmaster ON offers.stockid = stockmaster.stockid WHERE offerid = '" . $AcceptID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$MailText .= $MyRow['description'] . "\t" . __('Quantity') . ' ' . $MyRow['quantity'] . "\t" . __('Price') . ' ' . locale_number_format($MyRow['price']) . "\n";
			$SQL = "INSERT INTO purchorderdetails (orderno, itemcode, deliverydate, itemdescription, unitprice, actprice, quantityord, suppliersunit) VALUES ('" . $OrderNo . "', '" . $MyRow['stockid'] . "', CURRENT_DATE, '" . DB_escape_string($MyRow['description']) . "', '" . $MyRow['price'] . "', '" . $MyRow['price'] . "', '" . $MyRow['quantity'] . "', '" . $MyRow['uom'] . "')";
			DB_query($SQL);
			$SQL = "DELETE FROM offers WHERE offerid = '" . $AcceptID . "'";
			DB_query($SQL);
		}

		$Recipients = GetMailList('OffersReceivedResultRecipients');
		array_push($Recipients, $Email);
		$From = $_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>';
		$Subject = __('Your offer has been accepted');
		SendEmailFromWebERP($From, $Recipients, $Subject, $MailText);

		prnMsg(__('The accepted offers from') . ' ' . $SupplierName . ' ' . __('have been converted to purchase orders and an email sent') . ' <a href="' . $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $OrderNo . '" class="aw-btn aw-btn-primary aw-btn-sm" style="margin-left:12px;">' . __('View Order') . '</a>', 'success');
	}
	
	if (sizeOf($RejectsArray) > 0) {
		$MailText = __('The following offers you made have been rejected') . "\n\n";
		foreach ($RejectsArray as $RejectID) {
			$SQL = "SELECT offers.quantity, offers.price, stockmaster.description FROM offers LEFT JOIN stockmaster ON offers.stockid = stockmaster.stockid WHERE offerid = '" . $RejectID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$MailText .= $MyRow['description'] . "\t" . __('Quantity') . ' ' . $MyRow['quantity'] . "\t" . __('Price') . ' ' . locale_number_format($MyRow['price']) . "\n";
			$SQL = "DELETE FROM offers WHERE offerid = '" . $RejectID . "'";
			DB_query($SQL);
		}
		$Recipients = GetMailList('OffersReceivedResultRecipients');
		array_push($Recipients, $Email);
		$From = $_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>';
		$Subject = __('Your offer has been rejected');
		SendEmailFromWebERP($From, $Recipients, $Subject, $MailText);
		prnMsg(__('The rejected offers from') . ' ' . $SupplierName . ' ' . __('have been removed from the system.'), 'success');
	}
	prnMsg(__('All offers have been processed, and emails sent where appropriate'), 'success');
}

echo '</div> <!-- End aw-container -->';
include(__DIR__ . '/includes/footer.php');
?>
