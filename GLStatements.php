<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Financial Statements');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLStatements';
include(__DIR__ . '/includes/header.php');

// Merges gets into posts:
foreach (array('PeriodFrom', 'PeriodTo', 'Period', 'ShowBudget', 'ShowZeroBalance', 'ShowFinancialPosition', 'ShowComprehensiveIncome', 'ShowChangesInEquity', 'ShowCashFlows', 'ShowNotes', 'NewReport') as $val) {
    if (isset($_GET[$val])) $_POST[$val] = $_GET[$val];
}

if (isset($_POST['Period']) and $_POST['Period'] != '') {
	$_POST['PeriodFrom'] = ReportPeriod($_POST['Period'], 'From');
	$_POST['PeriodTo'] = ReportPeriod($_POST['Period'], 'To');
}

if (isset($_POST['PeriodFrom']) and $_POST['PeriodFrom'] > $_POST['PeriodTo']) { $_POST['NewReport'] = 'on'; prnMsg(__('Invalid period range'), 'error'); }
if (isset($_POST['PeriodTo']) and $_POST['PeriodTo']-$_POST['PeriodFrom']+1 > 12) { $_POST['NewReport'] = 'on'; prnMsg(__('Period duration must be <= 12 months'), 'error'); }

echo '<style>
    :root { --db-primary: hsl(145, 63%, 38%); --db-primary-dark: hsl(145, 45%, 22%); --db-primary-soft: hsl(145, 40%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); }
    .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.5rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
    .db-select { padding: 0.5rem; border-radius: 6px; border: 1px solid var(--db-border); width: 100%; font-size: 0.85rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; width: 100%; margin-top: 10px; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1rem; margin-top:1.5rem; }
    .checkbox-item { display: flex; align-items: center; gap: 10px; font-size: 0.8rem; font-weight: 600; color: #334155; }
    .statement-wrapper { max-width: 1200px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; }
    @media print { .noPrint { display: none; } .db-page { padding: 0; background: white; } }
</style>';

if (isset($_POST['PeriodFrom']) AND isset($_POST['PeriodTo']) AND (!isset($_POST['NewReport']) OR $_POST['NewReport']!='on')) {
    echo '<div class="db-page"><div class="statement-wrapper">';
    echo '<div style="text-align:center; padding: 1.5rem 0; border-bottom: 2px solid var(--db-primary-soft); margin-bottom:2rem;">';
    echo '<h1 style="margin:0; color:#1e293b;">' . stripslashes($_SESSION['CompanyRecord']['coyname']) . '</h1>';
    echo '<h2 style="font-weight:900; color:var(--db-primary); margin:5px 0;">' . $Title . '</h2>';
    $PFN = EndDateSQLFromPeriodNo($_POST['PeriodFrom']); $PTN = EndDateSQLFromPeriodNo($_POST['PeriodTo']);
    echo '<div style="color:#64748b;">' . __('From') . ' ' . MonthAndYearFromSQLDate($PFN) . ' ' . __('to') . ' ' . MonthAndYearFromSQLDate($PTN) . '</div>';
    echo '</div>';

	$IsIncluded = true; $PageBreak = '<div style="page-break-before:always; margin-top:4rem;"></div>';
	$_POST['ShowDetail'] = 'Detailed';
	if (isset($_POST['ShowFinancialPosition']) && $_POST['ShowFinancialPosition']) { include('GLBalanceSheet.php'); echo $PageBreak; }
	if (isset($_POST['ShowComprehensiveIncome']) && $_POST['ShowComprehensiveIncome']) { include('GLProfit_Loss.php'); echo $PageBreak; }
	if (isset($_POST['ShowChangesInEquity']) && $_POST['ShowChangesInEquity'] && file_exists('GLChangesInEquity.php')) { include('GLChangesInEquity.php'); echo $PageBreak; }
	if (isset($_POST['ShowCashFlows']) && $_POST['ShowCashFlows']) { include('GLCashFlowsIndirect.php'); echo $PageBreak; }
	if (isset($_POST['ShowNotes']) && $_POST['ShowNotes'] && file_exists('GLNotes.php')) { include('GLNotes.php'); }

	echo '<div class="noPrint" style="display:flex; justify-content:center; gap:15px; margin-top:3rem; padding-top:2rem; border-top:1px solid var(--db-border);">';
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    foreach(array('PeriodFrom','PeriodTo','ShowBudget','ShowZeroBalance','ShowFinancialPosition', 'ShowComprehensiveIncome', 'ShowChangesInEquity', 'ShowCashFlows', 'ShowNotes') as $f) 
        if(isset($_POST[$f])) echo '<input type="hidden" name="'.$f.'" value="'.$_POST[$f].'" />';
    echo '<button type="button" class="db-btn db-btn-primary" style="width:auto;" onclick="window.print()">Print Batch</button> ';
    echo '<button type="submit" name="NewReport" value="on" class="db-btn db-btn-ghost" style="width:auto;">New Set</button>';
    echo '</form></div></div></div>';

} else {
    echo '<div class="db-page"><div class="db-card" style="max-width:800px; margin:0 auto;">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Generate Financial Statement Set') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div class="db-field"><label class="db-label">Period From</label><select name="PeriodFrom" class="db-select">';
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno ASC");
                    $curP = GetPeriod(date($_SESSION['DefaultDateFormat']));
                    while($R = DB_fetch_array($Pers)) echo '<option '.($R['periodno']==$curP-11?'selected':'').' value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                    <div class="db-field"><label class="db-label">Period To</label><select name="PeriodTo" class="db-select">';
                    DB_data_seek($Pers, 0); while($R = DB_fetch_array($Pers)) echo '<option '.($R['periodno']==$curP?'selected':'').' value="'.$R['periodno'].'">'.MonthAndYearFromSQLDate($R['lastdate_in_period']).'</option>';
                    echo '</select></div>
                </div>

                <div class="checkbox-grid">
                    <div class="checkbox-item"><input type="checkbox" name="ShowFinancialPosition" id="sfp" checked /> <label for="sfp">Financial Position</label></div>
                    <div class="checkbox-item"><input type="checkbox" name="ShowComprehensiveIncome" id="sci" checked /> <label for="sci">Comprehensive Income</label></div>
                    <div class="checkbox-item"><input type="checkbox" name="ShowChangesInEquity" id="sce" /> <label for="sce">Changes in Equity</label></div>
                    <div class="checkbox-item"><input type="checkbox" name="ShowCashFlows" id="scf" /> <label for="scf">Cash Flows</label></div>
                    <div class="checkbox-item"><input type="checkbox" name="ShowBudget" id="sb" /> <label for="sb">Include Budget</label></div>
                    <div class="checkbox-item"><input type="checkbox" name="ShowZeroBalance" id="szb" /> <label for="szb">Zero Balances</label></div>
                </div>

                <button type="submit" class="db-btn db-btn-primary" style="margin-top:2rem;">Generate Financial Statements</button>
                </form>
            </div>
        </div></div>';
}

include(__DIR__ . '/includes/footer.php');
?>
