<?php
require (__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include ('includes/SQL_CommonFunctions.php');

if (isset($_POST['Process'])) {
	if (isset($_POST['FromDate'])) {
		$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);
	}

	/* Check that the config variable is set for picking notes and get out if not. */
	if ($_SESSION['RequirePickingNote'] == 0) {
		$Title = __('Picking Lists Not Enabled');
		include ('includes/header.php');
		echo '<div class="db-page">
				<div class="db-card">
					<div class="db-card-body centre" style="padding: 60px;">
						<div class="db-icon-circle" style="background: var(--warning-light-color); color: var(--warning-color); margin: 0 auto 20px;">
							<i class="fas fa-exclamation-triangle fa-2x"></i>
						</div>
						<h2 style="margin-bottom: 10px;">' . __('Picking Lists Not Enabled') . '</h2>
						<p style="color: var(--text-muted);">' . __('The system is not configured for picking lists. Please consult your system administrator.') . '</p>
					</div>
				</div>
			  </div>';
		include ('includes/footer.php');
		exit();
	}

	/* Retrieve the order details from the database to print */
	$ErrMsg = __('There was a problem retrieving the order header details from the database');

	if (!isset($_POST['TransDate']) and $_GET['TransNo'] !=  'Preview') {
		/* If there is no transaction date set, then it must be for a single order */
		$SQL = "SELECT salesorders.debtorno,
				salesorders.orderno,
				salesorders.customerref,
				salesorders.comments,
				salesorders.orddate,
				salesorders.deliverto,
				salesorders.deladd1,
				salesorders.deladd2,
				salesorders.deladd3,
				salesorders.deladd4,
				salesorders.deladd5,
				salesorders.deladd6,
				salesorders.deliverblind,
				salesorders.deliverydate,
				debtorsmaster.name,
				debtorsmaster.address1,
				debtorsmaster.address2,
				debtorsmaster.address3,
				debtorsmaster.address4,
				debtorsmaster.address5,
				debtorsmaster.address6,
				shippers.shippername,
				salesorders.printedpackingslip,
				salesorders.datepackingslipprinted,
				locations.locationname
			FROM salesorders,
				debtorsmaster,
				shippers,
				locations
			WHERE salesorders.debtorno=debtorsmaster.debtorno
			AND salesorders.shipvia=shippers.shipper_id
			AND salesorders.fromstkloc=locations.loccode
			AND salesorders.orderno='" . $_GET['TransNo'] . "'";
	}
	elseif (isset($_POST['TransDate']) || (isset($_GET['TransNo']) and $_GET['TransNo'] !=  'Preview')) {
		/* We are printing picking lists for all orders on a day */
		$SQL = "SELECT salesorders.debtorno,
					salesorders.orderno,
					salesorders.customerref,
					salesorders.comments,
					salesorders.orddate,
					salesorders.deliverto,
					salesorders.deladd1,
					salesorders.deladd2,
					salesorders.deladd3,
					salesorders.deladd4,
					salesorders.deladd5,
					salesorders.deladd6,
					salesorders.deliverblind,
					salesorders.deliverydate,
					debtorsmaster.name,
					debtorsmaster.address1,
					debtorsmaster.address2,
					debtorsmaster.address3,
					debtorsmaster.address4,
					debtorsmaster.address5,
					debtorsmaster.address6,
					shippers.shippername,
					salesorders.printedpackingslip,
					salesorders.datepackingslipprinted,
					locations.locationname
				FROM salesorders,
					debtorsmaster,
					shippers,
					locations
				WHERE salesorders.debtorno=debtorsmaster.debtorno
				AND salesorders.shipvia=shippers.shipper_id
				AND salesorders.fromstkloc=locations.loccode
				AND salesorders.fromstkloc='" . $_POST['loccode'] . "'
				AND salesorders.deliverydate<='" . FormatDateForSQL($_POST['TransDate']) . "'";
	}

	if ($_SESSION['SalesmanLogin'] !=  '') {
		$SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}

	if (isset($_POST['TransDate']) || (isset($_GET['TransNo']) and $_GET['TransNo'] !=  'Preview')) {
		$Result = DB_query($SQL, $ErrMsg);

		/* If there are no rows, there's a problem. */
		if (DB_num_rows($Result) == 0) {
			$Title = __('Print Picking List Error');
			include ('includes/header.php');
			echo '<div class="db-page">
					<div class="db-card">
						<div class="db-card-body centre" style="padding: 60px;">
							<div class="db-icon-circle" style="background: var(--danger-light-color); color: var(--danger-color); margin: 0 auto 20px;">
								<i class="fas fa-search"></i>
							</div>
							<h2 style="margin-bottom: 10px;">' . __('No Orders Found') . '</h2>
							<p style="color: var(--text-muted);">' . __('Unable to locate any orders meeting your specified criteria.') . '</p>
							<div style="margin-top: 30px;">
								<a href="' . $RootPath . '/PDFPickingList.php" class="db-btn db-btn-secondary">' . __('Return to Selection') . '</a>
							</div>
						</div>
					</div>
				  </div>';
			include ('includes/footer.php');
			exit();
		}

		/* Retrieve the order details from the database and place them in an array */
		$i = 0;
		while ($MyRow = DB_fetch_array($Result)) {
			$OrdersToPick[$i] = $MyRow;
			$i++;
		}
	}
	else {
		$OrdersToPick[0]['debtorno'] = str_pad('', 10, 'x');
		$OrdersToPick[0]['orderno'] = 'Preview';
		$OrdersToPick[0]['customerref'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['comments'] = str_pad('', 100, 'x');
		$OrdersToPick[0]['orddate'] = '1000-01-01';
		$OrdersToPick[0]['deliverto'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd1'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd2'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd3'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd4'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd5'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deladd6'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deliverblind'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['deliverydate'] = '1000-01-01';
		$OrdersToPick[0]['name'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address1'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address2'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address3'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address4'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address5'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['address6'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['shippername'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['printedpackingslip'] = str_pad('', 20, 'x');
		$OrdersToPick[0]['datepackingslipprinted'] = '1000-01-01';
		$OrdersToPick[0]['locationname'] = str_pad('', 15, 'x');
	}

	$ListCount = 0;
	$HTML = '<!DOCTYPE html>
	<html>
	<head>
		<style>
			@page { margin: 30px; }
			body { font-family: "Helvetica", "Arial", sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
			.report-header { border-bottom: 2px solid #e74c3c; padding-bottom: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
			.report-title { font-size: 20pt; font-weight: bold; color: #e74c3c; margin: 0; text-transform: uppercase; letter-spacing: 1px; }
			.order-info { width: 100%; border-collapse: collapse; margin-bottom: 20px; background: #fdfdfd; }
			.order-info td { padding: 8px; vertical-align: top; border: 1px solid #eee; }
			.label { font-weight: bold; color: #555; font-size: 9pt; text-transform: uppercase; margin-bottom: 3px; display: block; }
			.value { font-size: 11pt; color: #000; font-weight: 500; }
			.line-items { width: 100%; border-collapse: collapse; margin-top: 10px; }
			.line-items th { background: #34495e; color: #fff; padding: 10px 8px; text-align: left; font-size: 9pt; text-transform: uppercase; border: none; }
			.line-items td { padding: 10px 8px; border-bottom: 1px solid #eee; font-size: 9.5pt; vertical-align: top; }
			.line-items tr:nth-child(even) { background-color: #f9f9f9; }
			.stock-code { font-weight: bold; font-family: monospace; font-size: 10.5pt; }
			.qty { text-align: right; font-weight: bold; }
			.qty-pick { text-align: right; font-weight: bold; color: #e74c3c; font-size: 11pt; }
			.footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #999; text-align: center; border-top: 1px solid #eee; padding-top: 5px; }
			.page-break { page-break-after: always; }
			.badge { display: inline-block; padding: 3px 6px; background: #eee; border-radius: 3px; font-size: 8pt; font-weight: bold; }
		</style>
	</head>
	<body>';

	for ($i = 0; $i < sizeof($OrdersToPick); $i++) {
		$order = $OrdersToPick[$i];
		$DeliveryAddress = '';
		for ($j = 1; $j < 5; $j++) {
			if ($order['deladd' . $j] != '') {
				$DeliveryAddress .= htmlspecialchars($order['deladd' . $j]) . ", ";
			}
		}
		$DeliveryAddress .= htmlspecialchars($order['deladd5']);

		$HTML .= '<div class="' . ($i < count($OrdersToPick) - 1 ? 'page-break' : '') . '">';
		
		$HTML .= '<div class="report-header">
					<h1 class="report-title">' . __('Picking List') . '</h1>
					<div style="font-size: 8pt; color: #999; text-align: right;">' . __('Printed on') . ': ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '</div>
				  </div>';

		$HTML .= '<table class="order-info">
					<tr>
						<td width="30%"><span class="label">' . __('Order Number') . '</span><span class="value">#' . htmlspecialchars($order['orderno']) . '</span></td>
						<td width="40%"><span class="label">' . __('Customer') . '</span><span class="value">' . htmlspecialchars($order['name']) . '</span></td>
						<td width="30%"><span class="label">' . __('Warehouse') . '</span><span class="value">' . htmlspecialchars($order['locationname']) . '</span></td>
					</tr>
					<tr>
						<td><span class="label">' . __('Delivery Date') . '</span><span class="value">' . htmlspecialchars($order['deliverydate']) . '</span></td>
						<td colspan="2"><span class="label">' . __('Deliver To') . '</span><span class="value">' . htmlspecialchars($order['deliverTo']) . ', ' . $DeliveryAddress . '</span></td>
					</tr>';
		if ($order['comments'] != '') {
			$HTML .= '<tr><td colspan="3"><span class="label">' . __('Special Instructions / Comments') . '</span><span class="value" style="color: #c0392b;">' . htmlspecialchars($order['comments']) . '</span></td></tr>';
		}
		$HTML .= '</table>';

		// Get line items
		if ($order['orderno'] == 'Preview') {
			$lineItems = [['stkcode' => 'EXAMPLE-01', 'description' => 'Preview Item 1', 'narrative' => '', 'quantity' => '10.00', 'qtyinvoiced' => '0.00', 'supplied' => '10.00']];
		} else {
			$SQL = "SELECT salesorderdetails.stkcode,
						stockmaster.description,
						salesorderdetails.orderlineno,
						salesorderdetails.quantity,
						salesorderdetails.qtyinvoiced,
						salesorderdetails.unitprice,
						salesorderdetails.narrative,
						stockmaster.decimalplaces
					FROM salesorderdetails
					INNER JOIN stockmaster ON salesorderdetails.stkcode=stockmaster.stockid
					WHERE salesorderdetails.orderno='" . $order['orderno'] . "'";
			$LineResult = DB_query($SQL);
			$lineItems = [];
			while ($row = DB_fetch_array($LineResult)) {
				$lineItems[] = [
					'stkcode' => $row['stkcode'],
					'description' => $row['description'],
					'narrative' => $row['narrative'],
					'quantity' => locale_number_format($row['quantity'], $row['decimalplaces']),
					'qtyinvoiced' => locale_number_format($row['qtyinvoiced'], $row['decimalplaces']),
					'supplied' => locale_number_format($row['quantity'] - $row['qtyinvoiced'], $row['decimalplaces'])
				];
			}
		}

		$HTML .= '<table class="line-items">
					<thead>
						<tr>
							<th width="15%">' . __('Code') . '</th>
							<th width="40%">' . __('Description') . '</th>
							<th width="15%" class="qty">' . __('Ordered') . '</th>
							<th width="15%" class="qty">' . __('To Pick') . '</th>
							<th width="15%" class="qty">' . __('Delivered') . '</th>
						</tr>
					</thead>
					<tbody>';

		foreach ($lineItems as $item) {
			$HTML .= '<tr>
						<td class="stock-code">' . htmlspecialchars($item['stkcode']) . '</td>
						<td>
							<div style="font-weight: bold;">' . htmlspecialchars($item['description']) . '</div>' . 
							($item['narrative'] ? '<div style="font-size: 8pt; color: #666; font-style: italic;">' . htmlspecialchars($item['narrative']) . '</div>' : '') . '
						</td>
						<td class="qty">' . htmlspecialchars($item['quantity']) . '</td>
						<td class="qty-pick">' . htmlspecialchars($item['supplied']) . '</td>
						<td class="qty">' . htmlspecialchars($item['qtyinvoiced']) . '</td>
					</tr>';
		}
		$HTML .= '</tbody></table>';
		
		$HTML .= '<div style="margin-top: 30px; border-top: 1px dashed #ccc; padding-top: 10px; font-size: 9pt;">
					<strong>' . __('Warehouse Verification') . ':</strong> _________________________________ 
					<span style="margin-left: 30px;"><strong>' . __('Date') . ':</strong> ________________</span>
				  </div>';
				  
		$HTML .= '</div>';
		$ListCount++;
	}

	if ($ListCount == 0) {
		$Title = __('Print Picking List Error');
		include ('includes/header.php');
		include ('includes/footer.php');
		exit();
	}
	else {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_PickingLists_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));

	}
	/* Show selection screen if we have no orders to work with */
} else {
	if ((!isset($_GET['TransNo']) or $_GET['TransNo'] == '') and !isset($_POST['TransDate'])) {
		$Title = __('Select Picking Lists');
		include ('includes/header.php');
		
		$SQL = "SELECT locations.loccode,
				locationname
			FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
		$Result = DB_query($SQL);
		
		echo '<div class="db-page">
				<div class="db-page-header">
					<div class="db-page-title">
						<i class="fas fa-clipboard-list" style="color: var(--primary-color);"></i> ' . $Title . '
					</div>
				</div>

				<div class="db-bottom-layout" style="justify-content: center;">
					<div style="width: 100%; max-width: 600px;">
						<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
							<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
							
							<div class="db-card shadow-sm">
								<div class="db-card-header">
									<div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Search Criteria') . '</div>
								</div>
								<div class="db-card-body">
									<div class="db-field">
										<label for="TransDate">' . __('Deliveries to be made on') . '</label>
										<input type="date" name="TransDate" class="db-input" required="required" autofocus="autofocus" 
											   value="' . date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y'))) . '" />
									</div>

									<div class="db-field" style="margin-top: 20px;">
										<label for="loccode">' . __('From Warehouse') . '</label>
										<select name="loccode" class="db-select" required="required">';
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
		}
		echo '						</select>
									</div>

									<div class="centre" style="margin-top: 30px;">
										<button type="submit" name="Process" class="db-btn db-btn-primary" style="width: 100%; padding: 12px; font-weight: 600;">
											<i class="fas fa-print"></i> ' . __('Print Picking Lists') . '
										</button>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			  </div>';

		include ('includes/footer.php');
		exit();
	}
}

