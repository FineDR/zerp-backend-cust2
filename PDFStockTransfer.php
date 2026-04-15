<?php

require (__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_GET['TransferNo'])) {
	$_POST['TransferNo'] = $_GET['TransferNo'];
	$_POST['Process'] = 'Yes';
}

if (isset($_POST['Process'])) {
	$HTML = '<html><head><meta charset="UTF-8"><style>
	body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 0; }
	.container { padding: 20px; }
	
	/* Layout Tables */
	.layout-table { width: 100%; border: none; margin-bottom: 20px; }
	.layout-table td { border: none; padding: 0; vertical-align: top; }
	
	.logo { max-height: 70px; margin-bottom: 5px; }
	.doc-title { font-size: 22px; font-weight: bold; color: #1a1a1a; margin: 0; text-transform: uppercase; }
	.metadata { margin-top: 5px; color: #555; line-height: 1.4; }
	.metadata-label { font-weight: bold; color: #222; }
	
	/* Item Table */
	.item-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
	.item-table th { background: #f4f4f4; color: #222; font-weight: bold; text-align: left; padding: 10px; border-bottom: 2px solid #ccc; font-size: 10px; text-transform: uppercase; }
	.item-table td { padding: 10px; border-bottom: 1px solid #eee; vertical-align: top; }
	tr.striped { background: #fafafa; }
	tr.controlled-row { background: #fdfdfd; font-size: 10px; color: #777; }
	.number { text-align: right; }
	
	/* Signatures */
	.signature-section { margin-top: 50px; }
	.signature-box { border-top: 1px dotted #999; padding-top: 10px; width: 45%; }
	.signature-label { font-size: 9px; color: #666; text-transform: uppercase; font-weight: bold; margin-bottom: 20px; }
</style></head><body>';
	
	$HTML .= '<div class="container">';

	// --- DATA FETCHING ---
	$SQL = "SELECT stockmoves.stockid, description, transno, stockmoves.loccode, locationname, trandate, qty, reference, stockmaster.units
			FROM stockmoves INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid
			INNER JOIN locations ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE transno='" . $_POST['TransferNo'] . "' AND qty < 0 AND type=16";

	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		$Title = __('Print Stock Transfer - Error');
		include ('includes/header.php');
		echo '<div class="centre" style="padding: 100px;">
				<div class="db-badge db-badge-danger mb-4" style="font-size: 1.25rem;">' . __('Error: Transfer Not Found') . '</div>
				<p class="db-muted mb-4">' . __('There was no transfer found with number') . ': <b>' . $_POST['TransferNo'] . '</b></p>
				<a href="' . $RootPath . '/PDFStockTransfer.php" class="db-btn db-btn-primary">' . __('Try Again') . '</a>
			  </div>';
		include ('includes/footer.php');
		exit();
	}

	$FirstRow = DB_fetch_array($Result);
	DB_data_seek($Result, 0);

	// --- HEADER (Table layout for DomPDF compatibility) ---
	$HTML .= '<table class="layout-table">
				<tr>
					<td style="width: 50%;">
						<img class="logo" src="' . $_SESSION['LogoFile'] . '" /><br />
						<div class="metadata">
							<div><span class="metadata-label">' . __('From') . ':</span> ' . htmlspecialchars($FirstRow['locationname']) . '</div>
							<div><span class="metadata-label">' . __('To Reference') . ':</span> ' . htmlspecialchars($FirstRow['reference']) . '</div>
						</div>
					</td>
					<td style="width: 50%; text-align: right;">
						<h1 class="doc-title">' . __('Stock Transfer Note') . '</h1>
						<div class="metadata">
							<div><span class="metadata-label">' . __('Transfer #') . ':</span> ' . htmlspecialchars($FirstRow['transno']) . '</div>
							<div><span class="metadata-label">' . __('Date') . ':</span> ' . ConvertSQLDate($FirstRow['trandate']) . '</div>
						</div>
					</td>
				</tr>
			  </table>';

	$HTML .= '<table class="item-table"><thead>
				<tr>
					<th>' . __('Item Code') . '</th>
					<th>' . __('Description') . '</th>
					<th class="number">' . __('Quantity') . '</th>
					<th style="width: 50px;">' . __('Units') . '</th>
				</tr>
			</thead><tbody>';

	$i = 0;
	while ($MyRow = DB_fetch_array($Result)) {
		$i++;
		$stripeClass = ($i % 2 == 0) ? 'striped' : '';
		$HTML .= '<tr class="' . $stripeClass . '">';
		$HTML .= '<td style="font-weight: bold;">' . htmlspecialchars($MyRow['stockid']) . '</td>';
		$HTML .= '<td>' . htmlspecialchars($MyRow['description']) . '</td>';
		$HTML .= '<td class="number" style="font-weight: bold;">' . locale_number_format(-$MyRow['qty'], 2) . '</td>';
		$HTML .= '<td>' . htmlspecialchars($MyRow['units']) . '</td>';
		$HTML .= '</tr>';

		// Controlled Items Check
		$SQL = "SELECT stockmaster.controlled FROM stockmaster WHERE stockid ='" . $MyRow['stockid'] . "'";
		$CheckControlledResult = DB_query($SQL);
		$ControlledRow = DB_fetch_row($CheckControlledResult);

		if ($ControlledRow[0] == 1) {
			$SQL = "SELECT stockserialmoves.serialno, stockserialmoves.moveqty
					FROM stockmoves INNER JOIN stockserialmoves ON stockmoves.stkmoveno=stockserialmoves.stockmoveno
					WHERE stockmoves.stockid='" . $MyRow['stockid'] . "' AND stockmoves.type=16 AND qty > 0 AND stockmoves.transno='" . $_POST['TransferNo'] . "'";
			$GetStockMoveResult = DB_query($SQL);
			while ($SerialStockMoves = DB_fetch_array($GetStockMoveResult)) {
				$HTML .= '<tr class="controlled-row ' . $stripeClass . '">
							<td style="padding-left: 20px;" colspan="2"><i>' . __('Lot/Serial') . ':</i> ' . htmlspecialchars($SerialStockMoves['serialno']) . '</td>
							<td class="number">' . locale_number_format($SerialStockMoves['moveqty'], 2) . '</td>
							<td></td>
						  </tr>';
			}
		}
	}
	$HTML .= '</tbody></table>';

	// --- SIGNATURES (Table layout for DomPDF compatibility) ---
	$HTML .= '<div class="signature-section">
				<table class="layout-table" style="margin-top: 40px;">
					<tr>
						<td class="signature-box" style="margin-right: 5%;">
							<div class="signature-label">' . __('Issued / Signed for') . ' ' . htmlspecialchars($FirstRow['locationname']) . '</div>
							<div style="margin-top: 30px; font-size: 9px; color: #999;">' . __('Full Name') . ': ____________________________</div>
						</td>
						<td style="width: 10%;"></td>
						<td class="signature-box">
							<div class="signature-label">' . __('Received / Signed for') . ' ' . htmlspecialchars($FirstRow['reference']) . '</div>
							<div style="margin-top: 30px; font-size: 9px; color: #999;">' . __('Full Name') . ': ____________________________</div>
						</td>
					</tr>
				</table>
				<div style="margin-top: 60px; text-align: center; font-size: 8px; color: #ccc; border-top: 1px solid #eee; padding-top: 10px;">
					' . __('Document generated by') . ' ' . $_SESSION['UsersRealName'] . ' ' . __('on') . ' ' . date('d/M/Y H:i') . '
				</div>
			  </div>';

	$HTML .= '</div></body></html>';

	// Setup DomPDF
	$FileName = $_SESSION['DatabaseName'] . '_StockTransfer_' . $_POST['TransferNo'] . '.pdf';
	$DomPDF = new Dompdf($DomPDFOptions);
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
	$DomPDF->render();
	$DomPDF->stream($FileName, array("Attachment" => false));

} else {
	if (isset($_POST['TransferNo'])) {
		if (!is_numeric($_POST['TransferNo'])) {
			prnMsg(__('The entered transfer reference is expected to be numeric'), 'error');
			unset($_POST['TransferNo']);
		}
	}
	
	if (!isset($_GET['TransferNo']) && !isset($_POST['Process'])) {
		$Title = __('Print Stock Transfer');
		$ViewTopic = 'Inventory';
		include ('includes/header.php');

		echo '<div class="db-bottom-layout">';
		echo '<main class="db-col-main" style="max-width: 1000px; margin: 0 auto; width: 100%;">';

		echo '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 30px;">';

		// Note Printing Card
		echo '<div class="db-card">
				<div class="db-card-header d-flex align-items-center">
					<div class="bg-primary text-white p-2 rounded mr-3"><i class="fas fa-print"></i></div>
					<h2 class="db-card-title">' . __('Print Transfer Form') . '</h2>
				</div>
				<div class="db-card-body p-4">
					<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						<p class="db-muted text-sm mb-4">' . __('Generate a professional PDF transfer note for an existing internal requisition or direct transfer.') . '</p>
						<div class="db-form-group">
							<label class="db-label">' . __('Transfer Number') . '</label>
							<input type="text" name="TransferNo" class="db-input" autofocus placeholder="' . __('Enter ID (e.g. 123)') . '" />
						</div>
						<button type="submit" name="Process" class="db-btn db-btn-primary w-100 mt-5">' . __('Generate PDF Document') . '</button>
					</form>
				</div>
			  </div>';

		// Shipping Label Card
		echo '<div class="db-card">
				<div class="db-card-header d-flex align-items-center">
					<div class="bg-primary text-white p-2 rounded mr-3"><i class="fas fa-shipping-fast"></i></div>
					<h2 class="db-card-title">' . __('Shipping Labels') . '</h2>
				</div>
				<div class="db-card-body p-4">
					<form action="' . $RootPath . '/PDFShipLabel.php?Type=Sales" method="post" target="_blank">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						<input type="hidden" name="Type" value="Transfer" />
						<p class="db-muted text-sm mb-4">' . __('Reprint address and tracking labels for any completed stock transfer docket.') . '</p>
						<div class="db-form-group">
							<label class="db-label">' . __('Transfer Docket Reference') . '</label>
							<input type="text" name="ORD" class="db-input" placeholder="' . __('Enter ID (e.g. 123)') . '" />
						</div>
						<button type="submit" name="Print" class="db-btn db-btn-secondary w-100 mt-5">' . __('Generate Shipping Labels') . '</button>
					</form>
				</div>
			  </div>';

		echo '</div>'; // End Grid

		// Help Hint
		echo '<div class="db-card mt-5" style="background: var(--primary-soft);">
				<div class="db-card-body p-4 d-flex align-items-center">
					<div class="text-primary mr-3"><i class="fas fa-info-circle fa-2x"></i></div>
					<div>
						<div class="db-font-bold">' . __('Pro Tip: Batch Printing') . '</div>
						<div class="text-sm">' . __('For printing multiple transfers across a date range, use the') . ' <a href="' . $RootPath . '/PDFStockLocTransfer.php" class="text-primary db-font-bold">' . __('Multi-Item Transfer Report') . '</a>.</div>
					</div>
				</div>
			  </div>';

		echo '</main></div>';

		include ('includes/footer.php');
		exit();
	}
}
