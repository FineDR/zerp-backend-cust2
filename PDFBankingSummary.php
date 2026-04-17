<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['BatchNo'])){
	$_POST['BatchNo'] = $_GET['BatchNo'];
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$SQL= "SELECT bankaccountname,
				bankaccountnumber,
				ref,
				transdate,
				banktranstype,
				bankact,
				banktrans.exrate,
				banktrans.functionalexrate,
				banktrans.currcode,
				currencies.decimalplaces AS currdecimalplaces
			FROM bankaccounts INNER JOIN banktrans
			ON bankaccounts.accountcode=banktrans.bankact
			INNER JOIN currencies
			ON bankaccounts.currcode=currencies.currabrev
			WHERE banktrans.transno='" . $_POST['BatchNo'] . "'
			AND banktrans.type=12";

	$ErrMsg = __('An error occurred getting the header information about the receipt batch number') . ' ' . $_POST['BatchNo'];
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0){
		$Title = __('Create PDF Print-out For A Batch Of Receipts');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('The receipt batch number') . ' ' . $_POST['BatchNo'] . ' ' . __('was not found in the database') . '. ' . __('Please try again selecting a different batch number'), 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	/* OK get the row of receipt batch header info from the BankTrans table */
	$MyRow = DB_fetch_array($Result);
	$ExRate = $MyRow['exrate'];
	$FunctionalExRate = $MyRow['functionalexrate'];
	$Currency = $MyRow['currcode'];
	$BankTransType = $MyRow['banktranstype'];
	$BankedDate =  $MyRow['transdate'];
	$BankActName = $MyRow['bankaccountname'];
	$BankActNumber = $MyRow['bankaccountnumber'];
	$BankingReference = $MyRow['ref'];
	$BankCurrDecimalPlaces = $MyRow['currdecimalplaces'];

	$SQL = "SELECT debtorsmaster.name,
			ovamount,
			invtext,
			reference
		FROM debtorsmaster INNER JOIN debtortrans
		ON debtorsmaster.debtorno=debtortrans.debtorno
		WHERE debtortrans.transno='" . $_POST['BatchNo'] . "'
		AND debtortrans.type=12";

	$ErrMsg = __('An error occurred getting the customer receipts for batch number') . ' ' . $_POST['BatchNo'];
	$CustRecs = DB_query($SQL, $ErrMsg);

	$SQL = "SELECT narrative,
			amount
		FROM gltrans
		WHERE gltrans.typeno='" . $_POST['BatchNo'] . "'
		AND gltrans.type=12 and gltrans.amount <0
		AND gltrans.account !='" . $MyRow['bankact'] . "'
		AND gltrans.account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "'";

	$ErrMsg = __('An error occurred getting the GL receipts for batch number') . ' ' . $_POST['BatchNo'];
	$GLRecs = DB_query($SQL, $ErrMsg);

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
					' . __('Banking Summary Number') . ' ' . $_POST['BatchNo'] . '<br />
					' . __('Date of Banking') .': ' . ConvertSQLDate($MyRow['transdate']) . '<br />
					' . __('Banked into') . ': ' . $BankActName . ' - ' . __('Account Number') . ': ' . $BankActNumber . '<br />
					' . __('Reference') . ': ' . $BankingReference . '<br />
					' . __('Currency') . ': ' . $Currency . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . __('Amount') . '</th>
							<th>' . __('Customer') . '</th>
							<th>' . __('Bank Details') . '</th>
							<th>' . __('Narrative') . '</th>
						</tr>
					</thead>
					<tbody>';

	$TotalBanked = 0;

	while ($MyRow=DB_fetch_array($CustRecs)) {

		$HTML .= '<tr class="striped_row">
					<td>' . locale_number_format(-$MyRow['ovamount'],$BankCurrDecimalPlaces) . '</td>
					<td>' . $MyRow['name'] . '</td>
					<td>' . $MyRow['invtext'] . '</td>
					<td>' . $MyRow['reference'] . '</td>
				</tr>';

		$TotalBanked -= $MyRow['ovamount'];

	} /* end of while there are customer receipts in the batch to print */

	/* Right now print out the GL receipt entries in the batch */
	while ($MyRow=DB_fetch_array($GLRecs)){

		$HTML .= '<tr class="striped_row">
					<td>' . locale_number_format((-$MyRow['amount']*$ExRate*$FunctionalExRate),$BankCurrDecimalPlaces) . '</td>
					<td></td>
					<td></td>
					<td>' . $MyRow['narrative'] . '</td>
				</tr>';
		$TotalBanked +=  (-$MyRow['amount']*$ExRate);

	} /* end of while there are GL receipts in the batch to print */


	$HTML .= '<tr class="total_row">
				<td>' . locale_number_format($TotalBanked,2) . '</td>
				<td>' . __('TOTAL') . ' ' . $Currency . ' ' . __('BANKED') . '</td>
				<td colspan="2"></td>
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_BankingSummary_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Create PDF Print Out For A Batch Of Receipts');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/bank.png" title="' . __('Receipts') . '" alt="" />' . ' ' . __('Create PDF Print Out For A Batch Of Receipts') . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */
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
		padding: 12px 28px; border-radius: 50px;
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
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
</style>';

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('Home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=GL" class="breadcrumb-item">' . __('Cash & Bank') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('Banking Summary') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Generate professional PDF banking summaries for receipt batches') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	$SQL="SELECT DISTINCT
			transno,
			transdate
		FROM banktrans
		WHERE type=12
		ORDER BY transno DESC";
	$Result = DB_query($SQL);

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
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px;">
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
							<i class="fas fa-list-ol" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Batch Selection') . '
						</h3>
					</div>
					<div style="padding: 30px;">
						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Select Receipt Batch Number') . '</label>
							<select required="required" autofocus="autofocus" name="BatchNo" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px;">';
	while ($MyRow=DB_fetch_array($Result)) {
		echo '<option value="'.$MyRow['transno'].'">' . __('Batch') .' '. $MyRow['transno'].' - '.ConvertSqlDate($MyRow['transdate']) . '</option>';
	}
	echo '				</select>
						</div>

						<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 16px; display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
							<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
							<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
								<strong>' . __('Legacy Note:') . '</strong> ' . __('Fetching the detailed banking transaction log will query all valid type-12 (receipt) entries. Recent batches appear at the top of the list.') . '
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>'; // End custom-bottom-layout

	echo '</form>
	</div>'; // End db-page

	include(__DIR__ . '/includes/footer.php');
	exit();
}
