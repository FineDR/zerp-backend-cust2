<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$ViewTopic = 'GeneralLedger';
$BookMark = 'ChequePaymentListing';

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

$InputError=0; $Msg = '';
if (isset($_POST['FromDate']) AND !Is_Date($_POST['FromDate'])){ $Msg = __('Invalid from date'); $InputError=1; unset($_POST['FromDate']); }
if (isset($_POST['ToDate']) and !Is_Date($_POST['ToDate'])){ $Msg = __('Invalid to date'); $InputError=1; unset($_POST['ToDate']); }

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$SQL = "SELECT bankaccountname, decimalplaces AS bankcurrdecimalplaces FROM bankaccounts INNER JOIN currencies ON bankaccounts.currcode=currencies.currabrev WHERE accountcode = '" .$_POST['BankAccount'] . "'";
	$BankActResult = DB_query($SQL);
	$MyRow = DB_fetch_array($BankActResult);
	$BankAccountName = $MyRow['bankaccountname'];
	$BankCurrDecimalPlaces = $MyRow['bankcurrdecimalplaces'];

	$SQL= "SELECT amount, ref, transdate, banktranstype, type, transno FROM banktrans WHERE banktrans.bankact='" . $_POST['BankAccount'] . "' AND (banktrans.type=1 or banktrans.type=22) AND transdate >='" . FormatDateForSQL($_POST['FromDate']) . "' AND transdate <='" . FormatDateForSQL($_POST['ToDate']) . "'";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result) == 0){
		$Title = __('Payment Listing');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('No transactions found for the selected range'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$HTML = '';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />'; }
	$HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP"></head><body>';
	if (isset($_POST['PrintPDF'])) { $HTML .= '<img class="logo" src=' . $_SESSION['LogoFile'] . ' /><br />'; }

	$HTML .= '<div class="centre" id="ReportHeader">
					<b>' . $_SESSION['CompanyRecord']['coyname'] . '</b><br />
					' . $BankAccountName . ' ' . __('Payments Summary') . '<br />
					' . __('From') . ' ' . $_POST['FromDate'] . ' ' . __('to') . ' ' .  $_POST['ToDate'] . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>';

	while ($MyRow=DB_fetch_array($Result)){
		$HTML .= '<table style="width:100%; border-collapse:collapse; margin-top:20px; border:1px solid #ddd; font-size:10pt;">
					<thead><tr style="background:#f4f4f4;"><th>' . __('Cheque Amount') . '</th><th>' . __('Reference') . '</th></tr></thead>
					<tbody><tr style="border-bottom:1px solid #eee;"><td style="text-align:right; font-weight:bold;">' . locale_number_format(-$MyRow['amount'],$BankCurrDecimalPlaces) . '</td><td>' . htmlspecialchars($MyRow['ref'], ENT_QUOTES, 'UTF-8') . '</td></tr></tbody></table>';

		$GLRes = DB_query("SELECT accountname, accountcode, amount, narrative FROM gltrans INNER JOIN chartmaster ON gltrans.account=chartmaster.accountcode WHERE gltrans.typeno ='" . $MyRow['transno'] . "' AND gltrans.type='" . $MyRow['type'] . "'");
		$HTML .= '<table style="width:100%; font-size:9pt; margin-bottom:20px;"><thead><tr style="color:#666;"><th>' . __('GL Account') . '</th><th style="text-align:right;">' . __('Amount') . '</th><th>' . __('Narrative') . '</th></tr></thead>';
		while ($GLRow=DB_fetch_array($GLRes)){
			$Check = DB_fetch_row(DB_query("SELECT count(*) FROM glaccountusers WHERE accountcode= '" . $GLRow['accountcode'] . "' AND userid = '" . $_SESSION['UserID'] . "' AND canview = '1'"))[0];
			$AccountName = ($Check > 0) ? $GLRow['accountname'] : __('Other GL Accounts');
			$HTML .= '<tr><td>' . $GLRow['accountcode'] . ' - ' . htmlspecialchars($AccountName, ENT_QUOTES, 'UTF-8') . '</td><td style="text-align:right;">' . locale_number_format($GLRow['amount'],$_SESSION['CompanyRecord']['decimalplaces']) . '</td><td>' . htmlspecialchars($GLRow['narrative'], ENT_QUOTES, 'UTF-8') . '</td></tr>';
		}
		$HTML .= '</table>';
	}
	$HTML .= '</body></html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'landscape'); $DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_ChequeListing_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	} else {
		$Title = __('Payment Listing Results');
		include(__DIR__ . '/includes/header.php');
		echo '<style>.centre { text-align:center; } table { margin: 10px auto; border-radius:8px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }</style>';
		echo '<div style="padding:2rem; background:#f8fafc; min-height:100vh;">' . $HTML . '</div>';
		include(__DIR__ . '/includes/footer.php');
	}
} else {
	$Title = __('Payment Listing');
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(145, 63%, 38%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; overflow: hidden; }
        .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); background: #fff; }
        .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
        .db-card-body { padding: 1.5rem; }
        .db-field { margin-bottom: 1rem; }
        .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
        .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.8rem; width: 100%; transition: 0.2s; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; border: none; transition: 0.2s; }
        .db-btn-primary { background: var(--db-primary); color: white; width: 100%; margin-top: 1rem; }
    </style>';
    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Payment Listing Criteria') . '</h3></div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                if($InputError) prnMsg($Msg, 'error');
                echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">From Date</label><input name="FromDate" type="date" class="db-input" value="' . date('Y-m-d') . '" required /></div>
                    <div class="db-field"><label class="db-label">To Date</label><input name="ToDate" type="date" class="db-input" value="' . date('Y-m-d') . '" required /></div>
                </div>
                <div class="db-field"><label class="db-label">Bank Account</label><select name="BankAccount" class="db-select">';
                $BRes = DB_query("SELECT bankaccountname, accountcode FROM bankaccounts");
                while ($BRow = DB_fetch_array($BRes)) echo '<option value="' . $BRow['accountcode'] . '">' . $BRow['bankaccountname'] . '</option>';
                echo '</select></div>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Print PDF</button>
                    <button type="submit" name="View" class="db-btn db-btn-primary" style="background:var(--db-primary-soft); color:var(--db-primary);">View Online</button>
                </div>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
