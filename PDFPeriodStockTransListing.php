<?php
include (__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include (__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['FromDate'])) {
	$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);
}
if (isset($_POST['ToDate'])) {
	$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);
}

$InputError = 0;
if (isset($_POST['FromDate']) and !Is_Date($_POST['FromDate'])) {
	$Msg = __('The date must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError = 1;
	unset($_POST['FromDate']);
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	if ($_POST['StockLocation'] == 'All') {
		$SQL = "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='" . FormatDateForSQL($_POST['FromDate']) . "'
			AND date_format(trandate, '%Y-%m-%d')<='" . FormatDateForSQL($_POST['ToDate']) . "'";
	}
	else {
		$SQL = "SELECT stockmoves.type,
				stockmoves.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmoves.transno,
				stockmoves.trandate,
				stockmoves.qty,
				stockmoves.reference,
				stockmoves.narrative,
				locations.locationname
			FROM stockmoves
			LEFT JOIN stockmaster
			ON stockmoves.stockid=stockmaster.stockid
			LEFT JOIN locations
			ON stockmoves.loccode=locations.loccode
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(trandate, '%Y-%m-%d')>='" . FormatDateForSQL($_POST['FromDate']) . "'
			AND date_format(trandate, '%Y-%m-%d')<='" . FormatDateForSQL($_POST['ToDate']) . "'
			AND stockmoves.loccode='" . $_POST['StockLocation'] . "'";
	}
	$Result = DB_query($SQL, '', '', false, false);

	if (DB_error_no() != 0) {
		$Title = __('Transaction Listing');
		include (__DIR__ . '/includes/header.php');
		prnMsg(__('An error occurred getting the transactions'), 'error');
		include (__DIR__ . '/includes/footer.php');
		exit();
	}
	elseif (DB_num_rows($Result) == 0) {
		$Title = __('Transaction Listing');
		include (__DIR__ . '/includes/header.php');
		echo '<br />';
		prnMsg(__('There were no transactions found in the database between the dates') . ' ' . $_POST['FromDate'] . ' ' . __('and') . ' ' . $_POST['ToDate'] . '<br />' . __('Please try again selecting a different date range'), 'info');
		include (__DIR__ . '/includes/footer.php');
		exit();
	}

	// Build HTML for DomPDF
	$ReportTitle = __('Stock Transaction Listing');
	$ReportSubTitle = __('Stock transaction listing from') . ' ' . $_POST['FromDate'] . ' ' . __('to') . ' ' . $_POST['ToDate'];

	$TransType = match ($_POST['TransType']) {
		10      => __('Customer Invoices'),
		11      => __('Customer Credit Notes'),
		16      => __('Location Transfers'),
		17      => __('Stock Adjustments'),
		25      => __('Purchase Order Deliveries'),
		26      => __('Work Order Receipts'),
		28      => __('Work Order Issues'),
		default => __('Other'),
	};

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}
	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . $ReportTitle . '<br />
					' . $ReportSubTitle . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>';

	$HTML .= '<table>
				<tr class="total_row">
					<td colspan="6"><p><strong>' . __('Transaction Type') . ':</strong> ' . $TransType . '</p></td>
				</tr>
				<tr>
					<th>' . __('Description') . '</th>
					<th>' . __('Transaction No') . '</th>
					<th>' . __('Date') . '</th>
					<th class="right">' . __('Quantity') . '</th>
					<th>' . __('Location') . '</th>
					<th>' . __('Reference') . '</th>
				</tr>';

	while ($MyRow = DB_fetch_array($Result)) {
		$HTML .= '<tr class="striped_row">
					<td>' . htmlspecialchars($MyRow['description']) . '</td>
					<td>' . htmlspecialchars($MyRow['transno']) . '</td>
					<td>' . htmlspecialchars(ConvertSQLDate($MyRow['trandate'])) . '</td>
					<td class="number">' . locale_number_format($MyRow['qty'], $MyRow['decimalplaces']) . '</td>
					<td>' . htmlspecialchars($MyRow['locationname']) . '</td>
					<td>' . htmlspecialchars($MyRow['reference']) . '</td>
				</tr>';
	}

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
			</table>';
	}
	else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_PeriodStockTransListing_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	}
	else {
		$Title = __('Inventory Planning Report');
		include (__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . __('Inventory') . '" alt="" />' . ' ' . __('Inventory Planning Report') . '</p>';
		echo $HTML;
		include (__DIR__ . '/includes/footer.php');
	}
} else {
	$Title = __('Stock Transaction Listing');
	$ViewTopic = 'Inventory';
	$BookMark = '';
	include (__DIR__ . '/includes/header.php');

	echo '<div class="centre">
			<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/transactions.png" title="' . $Title . '" alt="" />' . ' ' . __('Stock Transaction Listing') . '</p>
		</div>';

	if ($InputError == 1) {
		prnMsg($Msg, 'error');
	}

	echo '<style>
		.modern-form-container {
			max-width: 900px;
			margin: 20px auto;
			padding: 30px;
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-md);
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 25px;
			margin-bottom: 30px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group label {
			font-weight: 600;
			color: var(--text-label);
			font-size: 0.9rem;
		}
		.form-group select, .form-group input {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus, .form-group input:focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			outline: none;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 15px;
			border-top: 1px solid var(--border-soft);
			padding-top: 25px;
		}
		.button-group input[type="submit"] {
			padding: 12px 30px;
			border-radius: var(--radius-sm);
			font-weight: 700;
			cursor: pointer;
			border: none;
			transition: all var(--transition-fast);
			background: var(--primary);
			color: white;
		}
		.button-group input[type="submit"]:hover {
			opacity: 0.9;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px var(--primary-glow);
			background: var(--primary-hover);
		}
		.report-table-wrapper {
			width: 100%;
			overflow-x: auto;
			margin-top: 20px;
			border-radius: var(--radius-md);
			border: 1px solid var(--border);
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="form-grid">';
	
	echo '<div class="form-group">
			<label>' . __('From Date') . '</label>
			<input required="required" autofocus="autofocus" name="FromDate" type="date" value="' . date('Y-m-d') . '" />
		  </div>';

	echo '<div class="form-group">
			<label>' . __('To Date') . '</label>
			<input required="required" name="ToDate" type="date" value="' . date('Y-m-d') . '" />
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Transaction type') . '</label>
			<select name="TransType">
				<option value="10">' . __('Sales Invoice') . '</option>
				<option value="11">' . __('Sales Credit Note') . '</option>
				<option value="16">' . __('Location Transfer') . '</option>
				<option value="17">' . __('Stock Adjustment') . '</option>
				<option value="25">' . __('Purchase Order Delivery') . '</option>
				<option value="26">' . __('Work Order Receipt') . '</option>
				<option value="28">' . __('Work Order Issue') . '</option>
			</select>
		  </div>';

	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);

	echo '<div class="form-group">
			<label>' . __('For Stock Location') . '</label>
			<select required="required" name="StockLocation">
				<option value="All">' . __('All') . '</option>';
	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		$selected = (isset($_POST['StockLocation']) && $_POST['StockLocation'] == $MyRow['loccode']) ? 'selected="selected"' : (!isset($_POST['StockLocation']) && $MyRow['loccode']==$_SESSION['UserStockLocation'] ? 'selected="selected"' : '');
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input type="submit" name="PrintPDF" title="Produce PDF Report" value="' . __('Print PDF') . '" />
			<input type="submit" name="View" title="View Report" value="' . __('View') . '" />
		  </div>';
	echo '</form></div>';

	include (__DIR__ . '/includes/footer.php');
}

