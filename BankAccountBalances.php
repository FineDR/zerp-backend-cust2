<?php

// Shows bank accounts authorised for with balances

require(__DIR__ . '/includes/session.php');

$Title = __('Bank Account Balances');
$ViewTopic = 'GeneralLedger';
$BookMark = 'BankAccountBalances';
include(__DIR__ . '/includes/header.php');

echo '<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --radius-lg: 16px;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, sans-serif; }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 2rem; letter-spacing: -0.02em; }
    
    .db-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1.5rem; }
    
    .db-card { 
        background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); overflow: hidden; 
        padding: 1.5rem; display: flex; flex-direction: column; transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .db-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
    
    .db-account-info { margin-bottom: 1.5rem; }
    .db-account-name { font-size: 0.85rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
    .db-account-code { font-size: 0.75rem; color: var(--db-text-muted); font-family: monospace; }
    
    .db-balance-main { font-size: 1.75rem; font-weight: 900; color: var(--db-primary); margin-bottom: 0.5rem; letter-spacing: -0.02em; }
    .db-balance-label { font-size: 0.7rem; color: var(--db-text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
    
    .db-balance-secondary { 
        border-top: 1px solid var(--db-border); padding-top: 1rem; margin-top: 0.5rem;
        display: flex; justify-content: space-between; align-items: flex-end;
    }
    .db-func-val { font-size: 0.9375rem; font-weight: 700; color: var(--db-text-main); }
    .db-func-currency { font-size: 0.65rem; font-weight: 800; color: var(--db-primary-dark); background: var(--db-primary-soft); padding: 2px 6px; border-radius: 4px; }
    
    .db-action-bar { margin-top: 1.25rem; display: flex; gap: 0.75rem; }
    .db-btn-circle { 
        width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: var(--db-primary-soft); color: var(--db-primary); transition: 0.2s; border: none; cursor: pointer;
    }
    .db-btn-circle:hover { background: var(--db-primary); color: white; }
</style>';

echo '<div class="db-page"><div class="db-centered">
    <div class="db-breadcrumb">Financial Dashboard / Assets</div>
    <h1 class="db-page-title">' . __('Cash & Equities') . '</h1>
    <div class="db-grid">';

$SQL = "SELECT DISTINCT bankaccounts.accountcode, bankaccounts.bankaccountname, bankaccounts.currcode FROM bankaccounts INNER JOIN bankaccountusers ON bankaccounts.accountcode=bankaccountusers.accountcode AND userid='" . $_SESSION['UserID'] . "' ORDER BY bankaccounts.accountcode";
$Result = DB_query($SQL);

if (DB_num_rows($Result) == 0) {
	echo '<div class="db-card" style="grid-column: 1 / -1; text-align:center; padding: 4rem;">
            <p style="color:var(--db-text-muted); font-weight:600;">' . __('There are no bank accounts defined that you have authority to see') . '</p>
          </div>';
} else {
	while ($MyBankRow = DB_fetch_array($Result)) {
		$CurrBalance = DB_fetch_row(DB_query("SELECT SUM(amount) FROM banktrans WHERE bankact='" . $MyBankRow['accountcode'] . "'"))[0];
		$FuncBalance = DB_fetch_row(DB_query("SELECT SUM(amount) FROM gltotals WHERE account='" . $MyBankRow['accountcode'] . "'"))[0];
		$Decimals = DB_fetch_row(DB_query("SELECT decimalplaces FROM currencies WHERE currabrev='" . $MyBankRow['currcode'] . "'"))[0];

		echo '<div class="db-card">
                <div class="db-account-info">
                    <span class="db-account-name">' . $MyBankRow['bankaccountname'] . '</span>
                    <span class="db-account-code">' . $MyBankRow['accountcode'] . '</span>
                </div>
                
                <div>
                    <span class="db-balance-label">' . __('Account Balance') . '</span>
                    <div class="db-balance-main">' . locale_number_format($CurrBalance, $Decimals) . ' <small style="font-size:0.5em; vertical-align:middle;">' . $MyBankRow['currcode'] . '</small></div>
                </div>

                <div class="db-balance-secondary">
                    <div>
                        <span class="db-balance-label">' . __('Net Asset (Functional)') . '</span>
                        <div class="db-func-val">' . locale_number_format($FuncBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</div>
                    </div>
                    <div style="text-align:right;">
                        <span class="db-func-currency">' . $_SESSION['CompanyRecord']['currencydefault'] . '</span>
                    </div>
                </div>

                <div class="db-action-bar">
                    <a href="' . $RootPath . '/BankMatching.php?Type=Receipts&Account=' . $MyBankRow['accountcode'] . '" title="Match Receipts" class="db-btn-circle" style="text-decoration:none;">📥</a>
                    <a href="' . $RootPath . '/BankMatching.php?Type=Payments&Account=' . $MyBankRow['accountcode'] . '" title="Match Payments" class="db-btn-circle" style="text-decoration:none;">📤</a>
                    <a href="' . $RootPath . '/BankReconciliation.php?Account=' . $MyBankRow['accountcode'] . '" title="Reconciliation" class="db-btn-circle" style="text-decoration:none;">📋</a>
                </div>
              </div>';
	}
}

echo '</div></div></div>';
include(__DIR__ . '/includes/footer.php');
?>
