<?php

require(__DIR__ . '/includes/session.php');
ob_start();

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['BatchNo'])){
	$_POST['BatchNo'] = $_GET['BatchNo'];
	$_POST['PrintPDF'] = true;
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

	$HeaderRow = DB_fetch_array($Result);
	$ExRate = ($HeaderRow['exrate'] != 0) ? $HeaderRow['exrate'] : 1;
	$FunctionalExRate = ($HeaderRow['functionalexrate'] != 0) ? $HeaderRow['functionalexrate'] : 1;
	
	// Safety check to ensure we don't multiply by zero if data is missing
	if ($ExRate == 0) $ExRate = 1;
	if ($FunctionalExRate == 0) $FunctionalExRate = 1;

	$Currency = $HeaderRow['currcode'];
	$BankedDate = $HeaderRow['transdate'];
	$BankActName = $HeaderRow['bankaccountname'];
	$BankActNumber = $HeaderRow['bankaccountnumber'];
	$BankingReference = $HeaderRow['ref'];
	$BankCurrDecimalPlaces = $HeaderRow['currdecimalplaces'];

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
		AND gltrans.account !='" . $HeaderRow['bankact'] . "'
		AND gltrans.account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "'";

	$ErrMsg = __('An error occurred getting the GL receipts for batch number') . ' ' . $_POST['BatchNo'];
	$GLRecs = DB_query($SQL, $ErrMsg);

	$Style = '
		@page { margin: 15mm; }
		body { font-family: "Helvetica", sans-serif; font-size: 9pt; color: #334155; line-height: 1.4; margin: 0; padding: 0; background: white !important; }
		.logo { max-height: 50px; margin-bottom: 20px; }
		.header-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; border-bottom: 2px solid #059669; padding-bottom: 20px; }
		.header-left { width: 60%; vertical-align: top; }
		.header-right { width: 40%; vertical-align: top; text-align: right; }
		.company-name { font-size: 14pt; font-weight: 900; color: #059669; margin-bottom: 4px; }
		.report-title { font-size: 20pt; font-weight: 900; color: #059669; letter-spacing: -0.5px; }
		.meta-label { font-size: 7pt; text-transform: uppercase; color: #94a3b8; font-weight: 800; letter-spacing: 0.5px; }
		.meta-value { font-size: 9pt; font-weight: 700; color: #1e293b; margin-bottom: 10px; }
		
		.summary-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin-bottom: 30px; }
		.summary-grid { display: table; width: 100%; }
		.summary-item { display: table-cell; width: 33%; padding: 0 10px; }
		
		table { width: 100%; border-collapse: collapse; margin-top: 20px; }
		th { background: #f1f5f9; color: #475569; font-weight: 800; font-size: 7pt; text-transform: uppercase; text-align: left; padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
		td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
		.number { text-align: right; font-weight: 600; }
		.total-row { background: #f8fafc; font-weight: 900; border-top: 2px solid #059669; }
		.footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #94a3b8; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 10px; }
		
		@media print {
			.db-sidebar, .db-header, .db-footer, .no-print, .ScriptTitle, .breadcrumb-nav, .breadcrumb-item, .breadcrumb-separator, .premium-header { display: none !important; }
			.db-page { padding: 0 !important; margin: 0 !important; background: white !important; min-height: 0 !important; }
			.db-card { box-shadow: none !important; border: none !important; padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
            body { background: white !important; padding: 0 !important; }
		}
	';

	$HTML = '<!DOCTYPE html>
			<html>
			<head>
				<meta charset="UTF-8">
				<style>' . $Style . '</style>
			</head>
			<body>';

	$HTML .= '<table class="header-table">
				<tr>
					<td class="header-left">
						<img class="logo" src="' . ($_SESSION['LogoFile'] ?? '') . '" />
						<div class="company-name">' . ($_SESSION['CompanyRecord']['coyname'] ?? 'Company') . '</div>
						<div style="font-size: 8pt; color: #64748b;">' . nl2br($_SESSION['CompanyRecord']['regoffice1'] ?? '') . '</div>
					</td>
					<td class="header-right">
						<div class="report-title">' . __('Banking Summary') . '</div>
						<div class="meta-label">' . __('Batch Number') . '</div>
						<div class="meta-value">#' . $_POST['BatchNo'] . '</div>
						<div class="meta-label">' . __('Date of Banking') . '</div>
						<div class="meta-value">' . ConvertSQLDate($BankedDate) . '</div>
					</td>
				</tr>
			</table>';

	$HTML .= '<div class="summary-card">
				<div class="summary-grid">
					<div class="summary-item">
						<div class="meta-label">' . __('Bank Account') . '</div>
						<div class="meta-value">' . $BankActName . '</div>
					</div>
					<div class="summary-item">
						<div class="meta-label">' . __('Account Number') . '</div>
						<div class="meta-value">' . $BankActNumber . '</div>
					</div>
					<div class="summary-item" style="text-align: right;">
						<div class="meta-label">' . __('Currency') . '</div>
						<div class="meta-value">' . $Currency . '</div>
					</div>
				</div>
			</div>';

	$HTML .= '<table>
				<thead>
					<tr>
						<th style="width: 15%;">' . __('Amount') . '</th>
						<th style="width: 30%;">' . __('Target / Customer') . '</th>
						<th style="width: 25%;">' . __('Bank Details') . '</th>
						<th style="width: 30%;">' . __('Narrative') . '</th>
					</tr>
				</thead>
				<tbody>';

	$TotalBanked = 0;

	while ($CustRow = DB_fetch_array($CustRecs)) {
		$HTML .= '<tr>
					<td class="number">' . locale_number_format(-$CustRow['ovamount'], $BankCurrDecimalPlaces) . '</td>
					<td style="font-weight: 700; color: #0f172a;">' . $CustRow['name'] . '</td>
					<td style="font-size: 8pt;">' . $CustRow['invtext'] . '</td>
					<td style="font-size: 8pt; color: #64748b;">' . $CustRow['reference'] . '</td>
				</tr>';
		$TotalBanked -= $CustRow['ovamount'];
	}

	while ($GLRow = DB_fetch_array($GLRecs)){
		$HTML .= '<tr>
					<td class="number">' . locale_number_format((-$GLRow['amount']*$ExRate*$FunctionalExRate), $BankCurrDecimalPlaces) . '</td>
					<td style="font-weight: 700; color: #0f172a;">' . __('General Ledger') . '</td>
					<td></td>
					<td style="font-size: 8pt; color: #64748b;">' . $GLRow['narrative'] . '</td>
				</tr>';
		$TotalBanked += (-$GLRow['amount']*$ExRate);
	}

	$HTML .= '<tr class="total_row">
				<td class="number" style="font-size: 11pt; color: #059669;">' . locale_number_format($TotalBanked, $BankCurrDecimalPlaces) . '</td>
				<td colspan="3" style="text-align: left; padding-left: 20px;">' . __('TOTAL') . ' ' . $Currency . ' ' . __('BANKED') . '</td>
			</tr>';

	$HTML .= '</tbody>
			</table>';

	$HTML .= '<div class="footer">
				' . ($_SESSION['CompanyRecord']['coyname'] ?? '') . ' - ' . __('Banking Summary') . ' #' . $_POST['BatchNo'] . ' - ' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat'] . ' H:i') . '
			</div>';

	$HTML .= '</body></html>';

	if (isset($_POST['PrintPDF'])) {
        ob_clean();
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
		$DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_BankingSummary_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
        exit();
	} else {
		$Title = __('Banking Summary Report');
		include(__DIR__ . '/includes/header.php');
        echo '<div class="db-page">
                <div style="max-width: 900px; margin: 0 auto;">
                    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;" class="no-print">
                        <h2 class="db-page-title">' . $Title . '</h2>
                       
                    </div>
                    <div class="db-card" style="background: white; padding: 40px; border-radius: 16px; box-shadow: var(--shadow-lg);">
                        ' . $HTML . '
                    </div>
                </div>
              </div>';
		include(__DIR__ . '/includes/footer.php');
        exit();
	}

} else {
	include(__DIR__ . '/includes/header.php');

    $SQL="SELECT DISTINCT transno, transdate FROM banktrans WHERE type=12 ORDER BY transno DESC";
	$Result = DB_query($SQL);

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item" style="color:var(--text-secondary); text-decoration:none;"><i class="fas fa-home"></i> ' . __('Home') . '</a>
						<span style="margin:0 8px; opacity:0.4;">/</span>
						<a href="index.php?Application=GL" class="breadcrumb-item" style="color:var(--text-secondary); text-decoration:none;">' . __('Cash & Bank') . '</a>
						<span style="margin:0 8px; opacity:0.4;">/</span>
						<span style="color: #064e3b; opacity: 0.9;">' . __('Banking Summary') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . __('Banking Summary') . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Generate professional PDF banking summaries for receipt batches') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>

        <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" target="_blank">
        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
        
        <div style="display: grid; grid-template-columns: 380px 1fr; gap: 32px; align-items: start;">
            <aside>
                <div class="db-card" style="border-radius: 20px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <div style="padding: 20px 30px; background: #f9fafb; border-bottom: 1px solid #f3f4f6;">
                        <h3 style="font-size: 1rem; font-weight: 800; color: #064e3b; margin: 0;">' . __('ACTIONS') . '</h3>
                    </div>
                    <div style="padding: 24px; display: flex; flex-direction: column; gap: 12px;">
                        <button type="submit" name="PrintPDF" class="db-btn db-btn-primary" style="width: 100%; height: 50px; font-weight: 700;">
                            <i class="fas fa-file-pdf" style="margin-right: 10px;"></i> ' . __('Generate PDF') . '
                        </button>
                        <button type="submit" name="View" class="db-btn db-btn-secondary" style="width: 100%; height: 50px; font-weight: 700;">
                            <i class="fas fa-eye" style="margin-right: 10px;"></i> ' . __('View Online') . '
                        </button>
                    </div>
                </div>
            </aside>

            <main>
                <div class="db-card" style="border-radius: 20px; overflow: hidden; border: 1px solid #e5e7eb;">
                    <div style="padding: 20px 30px; background: #f9fafb; border-bottom: 1px solid #f3f4f6;">
                        <h3 style="font-size: 1rem; font-weight: 800; color: #064e3b; margin: 0;">' . __('BATCH SELECTION') . '</h3>
                    </div>
                    <div style="padding: 30px;">
                        <div class="db-form-group">
                            <label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; color: #065f46; display: block; margin-bottom: 12px;">' . __('Select Receipt Batch Number') . '</label>
                            <select required name="BatchNo" class="db-input" style="width: 100%; height: 50px; border-radius: 12px; font-weight: 600;">';
	while ($MyRow=DB_fetch_array($Result)) {
		echo '<option value="'.$MyRow['transno'].'">' . __('Batch') .' '. $MyRow['transno'].' - '.ConvertSqlDate($MyRow['transdate']) . '</option>';
	}
	echo '                  </select>
                        </div>
                    </div>
                </div>
            </main>
        </div>
        </form>
	</div>';

	include(__DIR__ . '/includes/footer.php');
	exit();
}
