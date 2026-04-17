<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['Date'])){$_POST['Date'] = ConvertSQLDate($_POST['Date']);}

$InputError=0;
if (isset($_POST['Date']) AND !Is_Date($_POST['Date'])){
	$Msg = __('The date must be specified in the format') . ' ' . $_SESSION['DefaultDateFormat'];
	$InputError=1;
	unset($_POST['Date']);
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$SQL= "SELECT type,
				debtortrans.debtorno,
				transno,
				trandate,
				ovamount,
				ovgst,
				invtext,
				debtortrans.rate,
				decimalplaces
			FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE type='" . $_POST['TransType'] . "'
			AND date_format(inputdate, '%Y-%m-%d')='".FormatDateForSQL($_POST['Date'])."'";

	$ErrMsg = __('An error occurred getting the transactions');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0){
		$Title = __('Payment Listing');
		include(__DIR__ . '/includes/header.php');
		echo '<br />';
		prnMsg(__('There were no transactions found in the database for the date') . ' ' . $_POST['Date'] .'. '.__('Please try again selecting a different date'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	switch ($_POST['TransType']) {
		case 10:
			$TransType = __('Customer Invoices');
			break;
		case 11:
			$TransType = __('Customer Credit Notes');
			break;
		case 12:
			$TransType = __('Customer Receipts');
	}

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>';


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<img class="logo" src=' . $_SESSION['LogoFile'] . ' /><br />';
	}

	$HTML .= '<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . $TransType . ' ' .__('input on') . ' ' . $_POST['Date']. '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . __('Customer') . '</th>
							<th>' . __('Reference') . '</th>
							<th>' . __('Trans Date') . '</th>
							<th>' . __('Net Amount') . '</th>
							<th>' . __('Tax Amount') . '</th>
							<th>' . __('Total Amount') . '</th>
						</tr>
					</thead>
					<tbody>';

	while ($MyRow=DB_fetch_array($Result)){

		$SQL = "SELECT name FROM debtorsmaster WHERE debtorno='" . $MyRow['debtorno'] . "'";
		$CustomerResult = DB_query($SQL);
		$CustomerRow = DB_fetch_array($CustomerResult);

		$HTML .= '<tr class="striped_row">
					<td>' . $CustomerRow['name'] . '</td>
					<td>' . $MyRow['transno'] . '</td>
					<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
					<td class="number">' . locale_number_format($MyRow['ovamount'],$MyRow['decimalplaces']) . '</td>
					<td class="number">' . locale_number_format($MyRow['ovgst'],$MyRow['decimalplaces']) . '</td>
					<td class="number">' . locale_number_format($MyRow['ovamount']+$MyRow['ovgst'],$MyRow['decimalplaces']) . '</td>
				</tr>';

		$TotalAmount = $TotalAmount + ($MyRow['ovamount']/$MyRow['rate']);

	} /* end of while there are customer receipts in the batch to print */

	$HTML .= '<tr class="total_row">
				<td colspan="4"></td>
				<td class="number">' . __('Total') . '  ' . __('Transactions') . ' ' . $_SESSION['CompanyRecord']['currencydefault'] . '</td>
				<td class="number">' . locale_number_format($TotalAmount,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			</tr>';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '__CustTransListing__' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Customer Transactions Listing');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/customer.png" title="' . __('Receipts') . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}
} else {
	$Title = __('Customer Transaction Listing');
	$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
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
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 28px; border-radius: 12px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; width: 100%;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	.architect-btn.secondary { background: #e5e7eb; color: #374151; box-shadow: none; }
	.architect-btn.secondary:hover { background: #d1d5db; color: #111827; }
	.architect-btn.secondary i { color: #374151 !important; }
	
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
	
	@media (max-width: 900px) {
		.custom-bottom-layout { 
			display: flex; 
			flex-direction: column; 
		}
		.custom-range-grid {
			grid-template-columns: 1fr;
		}
	}
</style>';

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('transaction listing') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Track daily customer transaction volume with detailed audit trails') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	if ($InputError==1){
		prnMsg($Msg,'error');
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" target="_blank" style="display: contents;">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-cog" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Actions') . '
						</h3>
					</div>
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px; background: #fff;">
						<button type="submit" name="PrintPDF" class="architect-btn">
							<i class="fas fa-file-pdf"></i> ' . __('Generate PDF') . '
						</button>
						<button type="submit" name="View" class="architect-btn secondary">
							<i class="fas fa-eye"></i> ' . __('View Online') . '
						</button>
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-sliders-h" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Filter Criteria') . '
						</h3>
					</div>
					<div style="padding: 30px; background: #fff;">
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Target Transaction Date') . '</label>
								<input name="Date" class="db-input" maxlength="10" type="date" value="' . date('Y-m-d') . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Transaction Type') . '</label>
								<select name="TransType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
									<option value="10">' . __('Invoices') . '</option>
									<option value="11">' . __('Credit Notes') . '</option>
									<option value="12">' . __('Receipts') . '</option>
								</select>
							</div>
						</div>
						
						<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 16px; display: flex; align-items: flex-start; gap: 12px; margin-top: 10px;">
							<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
							<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
								<strong>' . __('Note:') . '</strong> ' . __('The listing will show all customer transactions that were input on the specified date. Only transactions with the selected type will be included in the report.') . '
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>';

	echo '</form>
	</div>';

	include(__DIR__ . '/includes/footer.php');
}

?>
