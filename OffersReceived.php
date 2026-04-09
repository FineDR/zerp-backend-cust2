<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Offers');
$ViewTopic = 'SupplierTenders';
$BookMark = 'SupplierOffers';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Review and process supplier offers from tenders') . '</p>
			</div>
		</div>';

if (isset($_POST['supplierid'])) {
	$SQL = "SELECT suppname,
				email,
				currcode,
				paymentterms
			FROM suppliers
			WHERE supplierid = '" . $_POST['supplierid'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$SupplierName = $MyRow['suppname'];
	$Email = $MyRow['email'];
	$CurrCode = $MyRow['currcode'];
	$PaymentTerms = $MyRow['paymentterms'];
}

if (!isset($_POST['supplierid'])) {
	$SQL = "SELECT DISTINCT
			offers.supplierid,
			suppliers.suppname
		FROM offers
		LEFT JOIN purchorderauth
			ON offers.currcode = purchorderauth.currabrev
		LEFT JOIN suppliers
			ON suppliers.supplierid = offers.supplierid
		WHERE purchorderauth.userid = '" . $_SESSION['UserID'] . "'
			AND offers.expirydate > CURRENT_DATE
			AND purchorderauth.cancreate = 0";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		prnMsg(__('There are no offers outstanding that you are authorised to deal with'), 'information');
	} else {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<div class="db-card">
				<div class="db-card-title">' . __('Supplier Selection') . '</div>
				<div class="db-card-body">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Select Supplier') . ':</label>
						<select name="supplierid" class="db-form-select">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="' . $MyRow['supplierid'] . '">' . $MyRow['suppname'] . '</option>';
		}
		echo '			</select>
					</div>
				</div>
				<div class="db-card-footer db-form-actions">
					<button type="submit" name="select" class="db-btn db-btn-primary">' . __('Enter Information') . '</button>
				</div>
			</div>
			</form>';
	}
}

if (!isset($_POST['submit']) and isset($_POST['supplierid'])) {
	$SQL = "SELECT offers.offerid,
				offers.tenderid,
				offers.supplierid,
				suppliers.suppname,
				offers.stockid,
				stockmaster.description,
				offers.quantity,
				offers.uom,
				offers.price,
				offers.expirydate,
				offers.currcode,
				stockmaster.decimalplaces,
				currencies.decimalplaces AS currdecimalplaces
			FROM offers INNER JOIN purchorderauth
				ON offers.currcode = purchorderauth.currabrev
			INNER JOIN suppliers
				ON suppliers.supplierid = offers.supplierid
			INNER JOIN currencies
				ON suppliers.currcode = currencies.currabrev
			LEFT JOIN stockmaster
				ON stockmaster.stockid = offers.stockid
			WHERE purchorderauth.userid = '" . $_SESSION['UserID'] . "'
				AND offers.expirydate >= CURRENT_DATE
				AND offers.supplierid = '" . $_POST['supplierid'] . "'
			ORDER BY offerid";
	$Result = DB_query($SQL);

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="db-card">
			<div class="db-card-title">' . __('Offers from') . ' ' . $SupplierName . '</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('ID') . '</th>
								<th>' . __('Stock Item') . '</th>
								<th class="text-right">' . __('Qty') . '</th>
								<th>' . __('UOM') . '</th>
								<th class="text-right">' . __('Price') . '</th>
								<th class="text-right">' . __('Total') . '</th>
								<th>' . __('Curr') . '</th>
								<th>' . __('Expires') . '</th>
								<th class="text-center">' . __('Accept') . '</th>
								<th class="text-center">' . __('Reject') . '</th>
								<th class="text-center">' . __('Defer') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
			<td class="db-text-muted">' . $MyRow['offerid'] . '</td>
			<td class="db-font-semibold">' . $MyRow['description'] . '</td>
			<td class="text-right">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>
			<td>' . $MyRow['uom'] . '</td>
			<td class="text-right">' . locale_number_format($MyRow['price'], $MyRow['currdecimalplaces']) . '</td>
			<td class="text-right db-font-bold">' . locale_number_format($MyRow['price'] * $MyRow['quantity'], $MyRow['currdecimalplaces']) . '</td>
			<td><span class="db-badge db-badge-info">' . $MyRow['currcode'] . '</span></td>
			<td class="text-nowrap">' . ConvertSQLDate($MyRow['expirydate']) . '</td>
			<td class="text-center"><label class="db-radio-container"><input type="radio" name="action' . $MyRow['offerid'] . '" value="1" /><span class="db-radio-checkmark"></span></label></td>
			<td class="text-center"><label class="db-radio-container"><input type="radio" name="action' . $MyRow['offerid'] . '" value="2" /><span class="db-radio-checkmark"></span></label></td>
			<td class="text-center"><label class="db-radio-container"><input type="radio" checked name="action' . $MyRow['offerid'] . '" value="3" /><span class="db-radio-checkmark"></span></label></td>
			<input type="hidden" name="supplierid" value="' . $MyRow['supplierid'] . '" />
		</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
			<div class="db-card-footer db-form-actions">
				<button type="submit" name="submit" class="db-btn db-btn-primary">' . __('Process Selections') . '</button>
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">' . __('Back to Selection') . '</a>
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
				case 1:
					$Accepts[] = $OfferID;
					break;
				case 2:
					$RejectsArray[] = $OfferID;
					break;
				case 3:
					$Defers[] = $OfferID;
					break;
			}
		}
	}
	if (sizeOf($Accepts) > 0) {
		$MailText = __('This email has been automatically generated by the webERP installation at') . ' ' . $_SESSION['CompanyRecord']['coyname'] . "\n";
		$MailText .= __('The following offers you made have been accepted') . "\n";
		$MailText .= __('An official order will be sent to you in due course') . "\n\n";
		$SQL = "SELECT rate FROM currencies where currabrev = '" . $CurrCode . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$Rate = $MyRow['rate'];
		$OrderNo = GetNextTransNo(18);
		$SQL = "INSERT INTO purchorders (
					orderno,
					supplierno,
					orddate,
					rate,
					initiator,
					intostocklocation,
					deliverydate,
					status,
					stat_comment,
					paymentterms)
				VALUES (
					'" . $OrderNo . "',
					'" . $_POST['supplierid'] . "',
					CURRENT_DATE,
					'" . $Rate . "',
					'" . $_SESSION['UserID'] . "',
					'" . $_SESSION['DefaultFactoryLocation'] . "',
					CURRENT_DATE,
					'" . __('Pending') . "',
					'" . __('Automatically generated from tendering system') . "',
					'" . $PaymentTerms . "')";
		DB_query($SQL);
		foreach ($Accepts as $AcceptID) {
			$SQL = "SELECT offers.quantity,
							offers.price,
							offers.uom,
							stockmaster.description,
							stockmaster.stockid
						FROM offers
						LEFT JOIN stockmaster
							ON offers.stockid = stockmaster.stockid
						WHERE offerid = '" . $AcceptID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$MailText .= $MyRow['description'] . "\t" . __('Quantity') . ' ' . $MyRow['quantity'] . "\t" . __('Price') . ' ' .
					locale_number_format($MyRow['price']) . "\n";
			$SQL = "INSERT INTO purchorderdetails (orderno,
												itemcode,
												deliverydate,
												itemdescription,
												unitprice,
												actprice,
												quantityord,
												suppliersunit)
									VALUES ('" . $OrderNo . "',
											'" . $MyRow['stockid'] . "',
											CURRENT_DATE,
											'" . DB_escape_string($MyRow['description']) . "',
											'" . $MyRow['price'] . "',
											'" . $MyRow['price'] . "',
											'" . $MyRow['quantity'] . "',
											'" . $MyRow['uom'] . "')";
			$Result = DB_query($SQL);
			$SQL = "DELETE FROM offers WHERE offerid = '" . $AcceptID . "'";
			$Result = DB_query($SQL);
		}

		$Recipients = GetMailList('OffersReceivedResultRecipients');
		if (sizeOf($Recipients) == 0) {
			prnMsg(__('There are no members of the Offers Received Result Recipients email group'), 'warn');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		array_push($Recipients, $Email);

		$From = $_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>';
		$Subject = __('Your offer to') . ' ' . $_SESSION['CompanyRecord']['coyname'] . ' ' . __('has been accepted');

		$Result = SendEmailFromWebERP($From, $Recipients, $Subject, $MailText);

		if ($Result) {
			prnMsg(__('The accepted offers from') . ' ' . $SupplierName . ' ' . __('have been converted to purchase orders and an email sent to')
				. ' ' . $Email . "\n" . __('Please review the order contents') . ' ' . '<a href="' . $RootPath .
				'/PO_Header.php?ModifyOrderNumber=' . $OrderNo . '">' . __('here') . '</a>', 'success');
		} else {
			prnMsg(__('The accepted offers from') . ' ' . $SupplierName . ' ' . __('have been converted to purcharse orders but failed to mail, you can view the order contents') . ' ' . '<a href="' . $RootPath . '/PO_Header.php?ModifyOrderNumber=' . $OrderNo . '">' . __('here') . '</a>', 'warn');
		}
	}
	if (sizeOf($RejectsArray) > 0) {
		$MailText = __('This email has been automatically generated by the webERP installation at') . ' ' .
		$_SESSION['CompanyRecord']['coyname'] . "\n";
		$MailText .= __('The following offers you made have been rejected') . "\n\n";
		foreach ($RejectsArray as $RejectID) {
			$SQL = "SELECT offers.quantity,
							offers.price,
							stockmaster.description
						FROM offers
						LEFT JOIN stockmaster
							ON offers.stockid = stockmaster.stockid
						WHERE offerid = '" . $RejectID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$MailText .= $MyRow['description'] . "\t" . __('Quantity') . ' ' . $MyRow['quantity'] . "\t" . __('Price') . ' ' . locale_number_format($MyRow['price']) . "\n";
			$SQL = "DELETE FROM offers WHERE offerid = '" . $RejectID . "'";
			$Result = DB_query($SQL);
		}

		$Recipients = GetMailList('OffersReceivedResultRecipients');
		if (sizeOf($Recipients) == 0) {
			prnMsg(__('There are no members of the Offers Received Result Recipients email group'), 'warn');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
		array_push($Recipients, $Email);

		$From = $_SESSION['CompanyRecord']['coyname'] . ' <' . $_SESSION['CompanyRecord']['email'] . '>';
		$Subject = __('Your offer to') . ' ' . $_SESSION['CompanyRecord']['coyname'] . ' ' . __('has been rejected');

		$Result = SendEmailFromWebERP($From, $Recipients, $Subject, $MailText);

		if ($Result) {
			prnMsg(__('The rejected offers from') . ' ' . $SupplierName . ' ' .
				__('have been removed from the system and an email sent to') . ' ' . $Email, 'success');
		} else {
			prnMsg(__('The rejected offers from') . ' ' . $SupplierName . ' ' .
				__('have been removed from the system and but no email was not sent to') . ' ' . $Email, 'warn');
		}
	}
	prnMsg(__('All offers have been processed, and emails sent where appropriate'), 'success');
}
echo '</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
