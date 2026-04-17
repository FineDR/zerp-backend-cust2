<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	/*Get the date of the last day in the period selected */
	$PeriodEndDate = ConvertSQLDate(EndDateSQLFromPeriodNo($_POST['PeriodEnd']));

	  /*Now figure out the aged analysis for the customer range under review */

	$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
		  			currencies.currency,
		  			currencies.decimalplaces,
					SUM((debtortrans.balance)/debtortrans.rate) AS balance,
					SUM(debtortrans.balance) AS fxbalance,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "' THEN
					(debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount)/debtortrans.rate ELSE 0 END) AS afterdatetrans,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "'
						AND (debtortrans.type=11 OR debtortrans.type=12) THEN
						debtortrans.diffonexch ELSE 0 END) AS afterdatediffonexch,
					SUM(CASE WHEN debtortrans.prd > '" . $_POST['PeriodEnd'] . "' THEN
					debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount ELSE 0 END
					) AS fxafterdatetrans
			FROM debtorsmaster INNER JOIN currencies
			ON debtorsmaster.currcode = currencies.currabrev
			INNER JOIN debtortrans
			ON debtorsmaster.debtorno = debtortrans.debtorno
			WHERE debtorsmaster.debtorno >= '" . $_POST['FromCriteria'] . "'
			AND debtorsmaster.debtorno <= '" . $_POST['ToCriteria'] . "'
			GROUP BY debtorsmaster.debtorno,
				debtorsmaster.name,
				currencies.currency,
				currencies.decimalplaces";

	$ErrMsg = ('The customer details could not be retrieved');
	$CustomerResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($CustomerResult) == 0) {
		$Title = __('Customer Balances') . ' - ' . __('Problem Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('The customer details listing has no clients to report on'),'warn');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Customer Balances For Customers between') . ' ' . $_POST['FromCriteria'] .  ' ' . __('and') . ' ' . $_POST['ToCriteria'] . ' ' . __('as at') . ' ' . $PeriodEndDate . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . __('Customer') . '</th>
							<th>' . __('Balance') . '</th>
							<th>' . __('FX') . '</th>
							<th>' . __('Currency') . '</th>
						</tr>
					</thead>
					<tbody>';

	$TotBal=0;

	while ($DebtorBalances = DB_fetch_array($CustomerResult)){

		$Balance = $DebtorBalances['balance'] - $DebtorBalances['afterdatetrans'] + $DebtorBalances['afterdatediffonexch'] ;
		$FXBalance = $DebtorBalances['fxbalance'] - $DebtorBalances['fxafterdatetrans'];

		if (ABS($Balance) > CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault'])
			OR ABS($FXBalance) > CurrencyTolerance($DebtorBalances['currency'])) {

			$DisplayBalance = locale_number_format($DebtorBalances['balance'] - $DebtorBalances['afterdatetrans'],$DebtorBalances['decimalplaces']);
			$DisplayFXBalance = locale_number_format($DebtorBalances['fxbalance'] - $DebtorBalances['fxafterdatetrans'],$DebtorBalances['decimalplaces']);

			$TotBal += $Balance;
			$HTML .= '<tr class="striped_row">
						<td>' . $DebtorBalances['debtorno'] . ' - ' . html_entity_decode($DebtorBalances['name'],ENT_QUOTES,'UTF-8') . '</td>
						<td class="number">' . $DisplayBalance . '</td>
						<td class="number">' . $DisplayFXBalance . '</td>
						<td class="number">' . $DebtorBalances['currency'] . '</td>
					</tr>';
		}
	} /*end customer aged analysis while loop */

	$DisplayTotBalance = locale_number_format($TotBal,$_SESSION['CompanyRecord']['decimalplaces']);

	$HTML .= '<tr class="total_row">
				<td>' . __('Total balances') . '</td>
				<td class="number">' . $DisplayTotBalance . '</td>
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
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_DebtorBals_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Debtor Balances');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/maintenance.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit */

	$Title=__('Debtor Balances');

	$ViewTopic = 'ARReports';
	$BookMark = 'PriorMonthDebtors';

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
		padding: 14px 28px; border-radius: 12px; /* Standard rounded as requested, not full pill */
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
</style>';

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('Home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('Receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('Period End Balances') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Review comprehensive customer balances for specific architectural periods') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank" style="display: contents;">';
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

				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-top: 24px;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-calendar-alt" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Reporting Period') . '
						</h3>
					</div>
					<div style="padding: 24px;">
						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Period Selection') . '</label>
							<select tabindex="3" name="PeriodEnd" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';

		$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
		$Periods = DB_query($SQL);
		while ($MyRow = DB_fetch_array($Periods)){
			echo '<option value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
		}

	echo '				</select>
						</div>
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-users" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Customer Coverage') . '
						</h3>
					</div>
					<div style="padding: 30px;">
						
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Start Customer (Code)') . '</label>
								<input tabindex="1" type="text" class="db-input" maxlength="10" name="FromCriteria" required="required" value="1" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('End Customer (Code)') . '</label>
								<input tabindex="2" type="text" class="db-input" maxlength="10" name="ToCriteria" required="required" value="zzzzzz" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>
						</div>
						
						<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 16px; display: flex; align-items: flex-start; gap: 12px; margin-top: 10px;">
							<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
							<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
								<strong>' . __('Note on Ranges:') . '</strong> ' . __('Leave the default values to process all customers. To generate balances for a specific slice of the registry, filter by customer code ranges above.') . '
							</div>
						</div>
						
					</div>
				</div>
			</main>
		</div>'; // End custom-bottom-layout
		
	echo '</form>
	</div>'; // End db-page

	include(__DIR__ . '/includes/footer.php');
} /*end of else not PrintPDF */
?>
