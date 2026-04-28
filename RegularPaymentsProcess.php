<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Process Regular Payments');
$ViewTopic = 'GeneralLedger';
$BookMark = 'RegularPayments';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['Add'])) {
	$AddedPayments = array(); $pCount = 0;
	foreach ($_POST as $Key => $Value) {
		if (substr($Key, 0, 7) == 'Payment') {
			$ID = substr($Key, 7);
			$SQL = "SELECT * FROM regularpayments WHERE id='" . $ID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$AddedPayments[$ID] = $MyRow;
			$AddedPayments[$ID]['PaymentDate'] = ConvertSQLDate($MyRow['nextpayment']);
			$AddedPayments[$ID]['FinalPaymentDate'] = ConvertSQLDate($MyRow['finalpayment']);
			$AddedPayments[$ID]['Tags'] = explode(',', $MyRow['tag']);
			$AddedPayments[$ID]['FunctionalExRate'] = $_POST['FuncRate' . $ID];
			$AddedPayments[$ID]['ExchangeRate'] = $_POST['ExRate' . $ID];
            $pCount++;
		}
	}

	foreach ($AddedPayments as $ID => $PaymentItem) {
		$TransNo = GetNextTransNo(1);
		$PeriodNo = GetPeriod($PaymentItem['PaymentDate']);

		switch ($PaymentItem['frequency']) {
			case 'D': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'd', 1); break;
			case 'W': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'w', 1); break;
			case 'F': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'w', 2); break;
			case 'M': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'm', 1); break;
			case 'Q': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'm', 3); break;
			case 'Y': $NextPaymentDate = DateAdd($PaymentItem['PaymentDate'], 'y', 1); break;
		}
		$Completed = Date1GreaterThanDate2($NextPaymentDate, $PaymentItem['FinalPaymentDate']) ? 1 : 0;
		
        DB_Txn_Begin();
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount, chequeno) VALUES (1, '" . $TransNo . "', '" . FormatDateForSQL($PaymentItem['PaymentDate']) . "', '" . $PeriodNo . "', '" . $PaymentItem['glcode'] . "', '" . mb_substr($PaymentItem['narrative'], 0, 200) . "', '" . ($PaymentItem['amount'] / $PaymentItem['ExchangeRate'] / $PaymentItem['FunctionalExRate']) . "', '" . $ID . "')", '', '', true);
		InsertGLTags($PaymentItem['Tags']);
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount, chequeno) VALUES (1, '" . $TransNo . "', '" . FormatDateForSQL($PaymentItem['PaymentDate']) . "', '" . $PeriodNo . "', '" . $PaymentItem['bankaccountcode'] . "', '" . mb_substr($PaymentItem['narrative'], 0, 200) . "', '" . -($PaymentItem['amount'] / $PaymentItem['ExchangeRate'] / $PaymentItem['FunctionalExRate']) . "', '" . $ID . "')", '', '', true);
		DB_query("INSERT INTO banktrans (transno, type, bankact, ref, chequeno, exrate, functionalexrate, transdate, banktranstype, amount, currcode) VALUES ('" . $TransNo . "', '1', '" . $PaymentItem['bankaccountcode'] . "', '" . $ID . "', '" . $ID . "', '" . $PaymentItem['ExchangeRate'] . "', '" . $PaymentItem['FunctionalExRate'] . "', '" . FormatDateForSQL($PaymentItem['PaymentDate']) . "', '1', '" . -($PaymentItem['amount']) . "', '" . $PaymentItem['currabrev'] . "')", '', '', true);
		DB_query("UPDATE regularpayments SET nextpayment='" . FormatDateForSQL($NextPaymentDate) . "', completed='" . $Completed . "' WHERE id='" . $ID . "'", '', '', true);
		DB_Txn_Commit();
	}
    if($pCount > 0) prnMsg($pCount . ' ' . __('regular payments processed successfully'), 'success');
}

echo '<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --radius-lg: 12px;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.25rem; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; }
    .db-card-body { padding: 1rem; }
    
    .db-input { padding: 0.4rem 0.6rem; border-radius: 6px; border: 1px solid var(--db-border); font-size: 0.75rem; width: 80px; text-align: right; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; border: none; transition: 0.2s; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .db-table tr:hover td { background: #f8fafc; }
    
    .db-badge { padding: 2px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Banking</div>
    <h1 class="db-page-title">' . $Title . '</h1>
</header>';

$SQL = "SELECT regularpayments.*, chartmaster.accountname, bankaccounts.bankaccountname FROM regularpayments INNER JOIN bankaccounts ON bankaccounts.accountcode=regularpayments.bankaccountcode INNER JOIN chartmaster ON chartmaster.accountcode=regularpayments.glcode WHERE completed=0 AND nextpayment <= CURRENT_DATE";
$Result = DB_query($SQL);
$totalDue = DB_num_rows($Result);

if ($totalDue > 0) {
    echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">
    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

    echo '<div class="db-card">
        <div class="db-card-header"><h3 class="db-card-title">' . __('Pending Regular Payments') . ' (' . $totalDue . ')</h3></div>
        <div class="db-card-body" style="padding:0;">
            <div class="db-table-container">
            <table class="db-table">
                <thead><tr>
                    <th>Bank Account</th>
                    <th>GL Account</th>
                    <th>Amount</th>
                    <th>Functional Rate</th>
                    <th>Exchange Rate</th>
                    <th>Currency</th>
                    <th>Next Due</th>
                    <th style="text-align:right;">Process</th>
                </tr></thead>
                <tbody>';
    
    while ($MyRow = DB_fetch_array($Result)) {
		$FuncExRateRow = DB_fetch_row(DB_query("SELECT rate FROM currencies WHERE currabrev='" . $MyRow['currabrev'] . "'"));
		$SuggestedFunctionalExRate = $FuncExRateRow[0];
		$ExRateRow = DB_fetch_row(DB_query("SELECT decimalplaces, rate FROM currencies WHERE currabrev='" . $MyRow['currabrev'] . "'"));
		$TableExRate = $ExRateRow[1];
		$SuggestedExRate = ($SuggestedFunctionalExRate != 0) ? $TableExRate / $SuggestedFunctionalExRate : 0;
		$DecimalPlaces = $ExRateRow[0];

		echo '<tr>
				<td><b>' . $MyRow['bankaccountname'] . '</b></td>
				<td><small>' . $MyRow['accountname'] . '</small></td>
                <td><b>' . locale_number_format($MyRow['amount'], $DecimalPlaces) . '</b></td>
				<td><input type="text" class="db-input" name="FuncRate' . $MyRow['id'] . '" value="' . $SuggestedFunctionalExRate . '" /></td>
				<td><input type="text" class="db-input" name="ExRate' . $MyRow['id'] . '" value="' . $SuggestedExRate . '" /></td>
				<td><span class="db-badge">' . $MyRow['currabrev'] . '</span></td>
				<td>' . ConvertSQLDate($MyRow['nextpayment']) . '</td>
				<td style="text-align:right;"><input type="checkbox" name="Payment' . $MyRow['id'] . '" /></td>
			</tr>';
    }
    echo '</tbody></table></div></div>
        <div class="db-card-body" style="background:#f8fafc; text-align:right; border-top:1px solid var(--db-border);">
            <button type="submit" name="Add" class="db-btn db-btn-primary">' . __('Process Selected Transactions') . '</button>
        </div>
    </div></form>';
} else {
    echo '<div class="db-card"><div class="db-card-body" style="text-align:center; padding:4rem; color:var(--db-text-muted);">
        <i class="fas fa-check-circle" style="font-size:3rem; margin-bottom:1rem; display:block; color:var(--db-primary);"></i>
        ' . __('No regular payments are currently due for processing.') . '
    </div></div>';
}

echo '</div></div>';
include(__DIR__ . '/includes/footer.php');
?>
