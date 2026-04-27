<?php

/*	Call this page with:
	1. A TransID to show the make up and to modify existing allocations.
	2. A DebtorNo to show all outstanding receipts or credits yet to be allocated.
	3. No parameters to show all outstanding credits and receipts yet to be allocated.
*/

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineCustAllocsClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Allocations');
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2 - High Density
echo '
<style>
	:root {
		--primary: hsl(145, 63%, 38%); 
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-soft: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(145, 15%, 12%);
		--text-muted: hsl(145, 8%, 50%);
		--card-bg: #ffffff;
		--radius: 12px;
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); }
	.aw-container { padding: 2px 10px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
	.MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 1fr 380px; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.65rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 2px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
    .aw-btn-danger:hover { background: #ffe4e6; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .aw-badge-info { background: var(--primary-soft); color: var(--primary); }
</style>
<div class="aw-container">';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['Cancel'])) { unset($_POST['AllocTrans']); }
if (isset($_POST['UpdateDatabase']) OR isset($_POST['RefreshAllocTotal'])) {
	if (!isset($_SESSION['Alloc'])) { prnMsg(__('Allocations expired. Please restart the process.'), 'info'); include(__DIR__ . '/includes/footer.php'); exit(); }
	$InputError = 0; $TotalAllocated = 0; $TotalDiffOnExch = 0;
	if (isset($_POST['TotalNumberOfAllocs'])) {
		for ($AllocCounter = 0; $AllocCounter < $_POST['TotalNumberOfAllocs']; $AllocCounter++) {
			if (isset($_POST['Amt' . $AllocCounter])) {
				if (!is_numeric(filter_number_format($_POST['Amt' . $AllocCounter]))) { $_POST['Amt' . $AllocCounter] = 0; }
				if (filter_number_format($_POST['Amt' . $AllocCounter]) < 0) { prnMsg(__('Positive amounts only.'), 'warn'); $_POST['Amt' . $AllocCounter] = 0; }
				if (isset($_POST['All' . $AllocCounter]) AND $_POST['All' . $AllocCounter] == true) { $_POST['Amt' . $AllocCounter] = $_POST['YetToAlloc' . $AllocCounter]; }
				if (filter_number_format($_POST['Amt' . $AllocCounter]) > $_POST['YetToAlloc' . $AllocCounter]) { $_POST['Amt' . $AllocCounter] = locale_number_format($_POST['YetToAlloc' . $AllocCounter], $_SESSION['Alloc']->CurrDecimalPlaces); }
				$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->AllocAmt = filter_number_format($_POST['Amt' . $AllocCounter]);
				$_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch = (filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->TransExRate) - (filter_number_format($_POST['Amt' . $AllocCounter]) / $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->ExRate);
				$TotalDiffOnExch += $_SESSION['Alloc']->Allocs[$_POST['AllocID' . $AllocCounter]]->DiffOnExch;
				$TotalAllocated += filter_number_format($_POST['Amt' . $AllocCounter]);
			}
		}
	}
	if ($TotalAllocated + $_SESSION['Alloc']->TransAmt > CurrencyTolerance($_SESSION['Alloc']->Currency)) { prnMsg(__('Allocation exceeds available amount.'), 'error'); $InputError = 1; }
}

if (isset($_POST['UpdateDatabase']) && $InputError == 0) {
	DB_Txn_Begin(); $AllAllocations = 0;
	foreach ($_SESSION['Alloc']->Allocs as $AllocnItem) {
		if ($AllocnItem->PrevAllocRecordID != 'NA') { DB_query("DELETE FROM custallocns WHERE id = '" . $AllocnItem->PrevAllocRecordID . "'"); }
		if ($AllocnItem->AllocAmt > 0) { DB_query("INSERT INTO custallocns (datealloc, amt, transid_allocfrom, transid_allocto) VALUES (CURRENT_DATE, '" . $AllocnItem->AllocAmt . "', '" . $_SESSION['Alloc']->AllocTrans . "', '" . $AllocnItem->ID . "')"); }
		$NewAllocTotal = $AllocnItem->PrevAlloc + $AllocnItem->AllocAmt; $AllAllocations += $AllocnItem->AllocAmt; $Settled = (abs($NewAllocTotal - $AllocnItem->TransAmount) < CurrencyTolerance($_SESSION['Alloc']->Currency)) ? 1 : 0;
		DB_query("UPDATE debtortrans SET diffonexch='" . ($AllocnItem->DiffOnExch + $AllocnItem->PrevDiffOnExch) . "', alloc = '" . $NewAllocTotal . "', settled = '" . $Settled . "' WHERE id = '" . $AllocnItem->ID . "'");
	}
	$Settled = (abs($TotalAllocated + $_SESSION['Alloc']->TransAmt) < CurrencyTolerance($_SESSION['Alloc']->Currency)) ? 1 : 0;
	DB_query("UPDATE debtortrans SET alloc = '" . -$AllAllocations . "', diffonexch = '" . -$TotalDiffOnExch . "', settled='" . $Settled . "' WHERE id = '" . $_POST['AllocTrans'] . "'");
	$MovtInDiffOnExch = -$_SESSION['Alloc']->PrevDiffOnExch - $TotalDiffOnExch;
	if ($MovtInDiffOnExch != 0 && $_SESSION['CompanyRecord']['gllink_debtors'] == 1) {
		$PeriodNo = GetPeriod($_SESSION['Alloc']->TransDate); $SQLTransDate = FormatDateForSQL($_SESSION['Alloc']->TransDate);
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('" . $_SESSION['Alloc']->TransType . "', '" . $_SESSION['Alloc']->TransNo . "', '" . $SQLTransDate . "', '" . $PeriodNo . "', '" . $_SESSION['CompanyRecord']['salesexchangediffact'] . "', '', '" . $MovtInDiffOnExch . "')");
		DB_query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('" . $_SESSION['Alloc']->TransType . "', '" . $_SESSION['Alloc']->TransNo . "', '" . $_SESSION['Alloc']->TransDate . "', '" . $PeriodNo . "', '" . $_SESSION['CompanyRecord']['debtorsact'] . "', '', '" . -$MovtInDiffOnExch . "')");
	}
	DB_Txn_Commit(); prnMsg(__('Allocations saved successfully.'), 'success'); unset($_SESSION['Alloc'], $_POST['AllocTrans']);
}

if (isset($_GET['AllocTrans'])) {
	if (isset($_SESSION['Alloc'])) { unset($_SESSION['Alloc']->Allocs, $_SESSION['Alloc']); }
	$_SESSION['Alloc'] = new Allocation; $_POST['AllocTrans'] = $_GET['AllocTrans'];
	$MyRow = DB_fetch_array(DB_query("SELECT systypes.typename, debtortrans.type, debtortrans.transno, debtortrans.trandate, debtortrans.debtorno, debtorsmaster.name, debtortrans.rate, (debtortrans.ovamount + debtortrans.ovgst + debtortrans.ovfreight + debtortrans.ovdiscount) as total, debtortrans.diffonexch, debtortrans.alloc, currencies.decimalplaces, currencies.currabrev FROM debtortrans INNER JOIN systypes ON debtortrans.type = systypes.typeid INNER JOIN debtorsmaster ON debtortrans.debtorno = debtorsmaster.debtorno INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE debtortrans.id='" . $_POST['AllocTrans'] . "'" . ($_SESSION['SalesmanLogin'] != '' ? " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'" : "")));
	$_SESSION['Alloc']->AllocTrans = $_POST['AllocTrans']; $_SESSION['Alloc']->DebtorNo = $MyRow['debtorno']; $_SESSION['Alloc']->CustomerName = $MyRow['name']; $_SESSION['Alloc']->TransType = $MyRow['type']; $_SESSION['Alloc']->TransTypeName = __($MyRow['typename']); $_SESSION['Alloc']->TransNo = $MyRow['transno']; $_SESSION['Alloc']->TransExRate = $MyRow['rate']; $_SESSION['Alloc']->TransAmt = $MyRow['total']; $_SESSION['Alloc']->PrevDiffOnExch = $MyRow['diffonexch']; $_SESSION['Alloc']->TransDate = ConvertSQLDate($MyRow['trandate']); $_SESSION['Alloc']->CurrDecimalPlaces = $MyRow['decimalplaces']; $_SESSION['Alloc']->Currency = $MyRow['currabrev'];
	$Result = DB_query("SELECT id, typename, transno, trandate, rate, ovamount+ovgst+ovfreight+ovdiscount as total, diffonexch, alloc FROM debtortrans INNER JOIN systypes ON debtortrans.type = systypes.typeid WHERE settled=0 AND debtorno='" . $_SESSION['Alloc']->DebtorNo . "'" . ($_SESSION['SalesmanLogin'] != '' ? " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'" : "") . " ORDER BY trandate, transno");
	while ($R = DB_fetch_array($Result)) { $_SESSION['Alloc']->add_to_AllocsAllocn($R['id'], __($R['typename']), $R['transno'], ConvertSQLDate($R['trandate']), 0, $R['total'], $R['rate'], $R['diffonexch'], $R['diffonexch'], $R['alloc'], 'NA'); }
	$Result = DB_query("SELECT debtortrans.id, typename, transno, trandate, rate, ovamount+ovgst+ovfreight+ovdiscount AS total, diffonexch, debtortrans.alloc-custallocns.amt AS prevallocs, amt, custallocns.id AS allocid FROM debtortrans INNER JOIN systypes ON debtortrans.type = systypes.typeid INNER JOIN custallocns ON debtortrans.id=custallocns.transid_allocto WHERE custallocns.transid_allocfrom='" . $_POST['AllocTrans'] . "' AND debtorno='" . $_SESSION['Alloc']->DebtorNo . "'" . ($_SESSION['SalesmanLogin'] != '' ? " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'" : "") . " ORDER BY trandate, transno");
	while ($R = DB_fetch_array($Result)) { $DTE = ($R['amt'] / $R['rate']) - ($R['amt'] / $_SESSION['Alloc']->TransExRate); $_SESSION['Alloc']->add_to_AllocsAllocn($R['id'], __($R['typename']), $R['transno'], ConvertSQLDate($R['trandate']), $R['amt'], $R['total'], $R['rate'], $DTE, ($R['diffonexch'] - $DTE), $R['prevallocs'], $R['allocid']); }
}

echo '<div class="aw-page-header"><div><div class="aw-breadcrumb">Finance / Receivable Allocations</div><h1 class="aw-page-title">' . $Title . '</h1></div></div>';

if (isset($_POST['AllocTrans'])) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="AllocTrans" value="' . $_POST['AllocTrans'] . '" />';

	echo '<div class="aw-grid aw-grid-layout">';
    
    // MAIN AREA (Invoices)
    echo '<main class="aw-main-side">';
    echo '<div class="aw-card">
            <div class="aw-card-header"><h3 class="aw-card-title">' . __('Target Invoices') . '</h3></div>
            <div class="aw-table-wrapper">
                <table class="aw-table">
                    <thead><tr><th>Type</th><th>No.</th><th>Date</th><th style="text-align:right;">Orig. Amt</th><th style="text-align:right;">Outstanding</th><th style="width:160px;">Link Amt</th><th style="text-align:right;">Balance</th></tr></thead>
                    <tbody>';
    $Counter = 0; $CurrentTotalAllocated = 0; $Bal = 0;
    foreach ($_SESSION['Alloc']->Allocs as $AI) {
        $Yet = ($AI->TransAmount - $AI->PrevAlloc);
        echo '<tr><td>' . $AI->TransType . '</td><td style="font-weight:700;">' . $AI->TypeNo . '</td><td>' . $AI->TransDate . '</td><td style="text-align:right;">' . locale_number_format($AI->TransAmount, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td><td style="text-align:right; font-weight:700;">' . locale_number_format($Yet, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>';
        if ($AI->TransAmount < 0) { $Bal += $Yet; echo '<td><span class="aw-badge aw-badge-info">'.__('Credit/Rec').'</span></td><td style="text-align:right;">'.locale_number_format($Bal, $_SESSION['Alloc']->CurrDecimalPlaces).'</td>'; }
        else {
            $Bal += $Yet - $AI->AllocAmt;
            echo '<td><div style="display:flex; align-items:center; gap:4px;"><input type="hidden" name="YetToAlloc'.$Counter.'" value="'.round($Yet, $_SESSION['Alloc']->CurrDecimalPlaces).'" />';
            echo '<input type="checkbox" name="All'.$Counter.'" '.(ABS($AI->AllocAmt - $Yet) < CurrencyTolerance($_SESSION['Alloc']->Currency) ? 'checked' : '').' />';
            echo '<input type="text" name="Amt'.$Counter.'" class="aw-input" style="text-align:right; padding:4px;" value="'.locale_number_format(round($AI->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces), $_SESSION['Alloc']->CurrDecimalPlaces).'" /><input type="hidden" name="AllocID'.$Counter.'" value="'.$AI->ID.'" /></div></td>';
            echo '<td style="text-align:right; font-weight:800; color:var(--primary);">' . locale_number_format($Bal, $_SESSION['Alloc']->CurrDecimalPlaces) . '</td>';
        }
        echo '</tr>';
        $CurrentTotalAllocated += round($AI->AllocAmt, $_SESSION['Alloc']->CurrDecimalPlaces); $Counter++;
    }
    echo '</tbody></table><input type="hidden" name="TotalNumberOfAllocs" value="' . $Counter . '" /></div></div></main>';

    // SIDEBAR (Status/Actions)
    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Transaction Profile') . '</h3></div>
                <div class="aw-card-body">
                    <div style="font-weight:950; color:var(--primary-dark);">' . $_SESSION['Alloc']->CustomerName . '</div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:12px;">' . $_SESSION['Alloc']->DebtorNo . '</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <div><label class="aw-label">Type & Ref</label><div style="font-size:0.85rem; font-weight:700;">'.$_SESSION['Alloc']->TransTypeName.' #'.$_SESSION['Alloc']->TransNo.'</div></div>
                        <div><label class="aw-label">Date</label><div style="font-size:0.85rem;">'.$_SESSION['Alloc']->TransDate.'</div></div>
                        <div><label class="aw-label">Available Amount</label><div style="font-size:1.1rem; font-weight:950; color:var(--primary-dark);">'.$_SESSION['Alloc']->Currency.' '.locale_number_format(-$_SESSION['Alloc']->TransAmt, $_SESSION['Alloc']->CurrDecimalPlaces).'</div></div>
                    </div>
                </div>
            </div>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Allocation Progress') . '</h3></div>
                <div class="aw-card-body">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;"><div><div class="aw-label">Allocated</div><div style="font-weight:700;">'.locale_number_format($CurrentTotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces).'</div></div> <div style="text-align:right;"><div class="aw-label">Remaining</div><div style="font-weight:950; color:var(--primary); font-size:1.1rem;">'.locale_number_format(-$_SESSION['Alloc']->TransAmt - $CurrentTotalAllocated, $_SESSION['Alloc']->CurrDecimalPlaces).'</div></div></div>
                    <button type="submit" name="UpdateDatabase" class="aw-btn aw-btn-primary" style="width:100%; height:44px;">' . __('Process Allocations') . '</button>
                    <div style="display:flex; gap:4px; margin-top:8px;"><button type="submit" name="RefreshAllocTotal" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('Refresh') . '</button><button type="submit" name="Cancel" class="aw-btn aw-btn-danger" style="flex:1;">' . __('Cancel') . '</button></div>
                </div>
            </div>
          </aside></div></form>';
} elseif (isset($_GET['DebtorNo'])) {
	$Res = DB_query("SELECT id, transno, typename, type, debtorno, name, trandate, total, alloc, currdecimalplaces, currcode FROM (SELECT debtortrans.id, debtortrans.transno, systypes.typename, debtortrans.type, debtortrans.debtorno, debtorsmaster.name, debtortrans.trandate, (ovamount+ovgst+ovdiscount+ovfreight) as total, debtortrans.alloc, currencies.decimalplaces AS currdecimalplaces, debtorsmaster.currcode FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN systypes ON debtortrans.type=systypes.typeid INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE debtortrans.debtorno='" . $_GET['DebtorNo'] . "' AND (debtortrans.type=12 OR debtortrans.type=11) AND debtortrans.settled=0) AS t ".($_SESSION['SalesmanLogin']!=''?"WHERE salesperson='".$_SESSION['SalesmanLogin']."'":"")." ORDER BY trandate, transno");
	if (DB_num_rows($Res) == 0) { prnMsg(__('No pending credits for this customer'), 'info'); }
	else {
		echo '<div class="aw-card"><div class="aw-card-header"><h3 class="aw-card-title">' . __('Pending Credits & Receipts') . '</h3></div><div class="aw-table-wrapper"><table class="aw-table"><thead><tr><th>Type</th><th>Customer</th><th>Ref</th><th>Date</th><th style="text-align:right;">Amount</th><th style="text-align:right;">To Alloc</th><th>Action</th></tr></thead><tbody>';
		while ($R = DB_fetch_array($Res)) { echo '<tr><td>'.$R['typename'].'</td><td>'.$R['name'].'</td><td style="font-weight:700;">'.$R['transno'].'</td><td>'.ConvertSQLDate($R['trandate']).'</td><td style="text-align:right;">'.locale_number_format($R['total'],$R['currdecimalplaces']).'</td><td style="text-align:right; font-weight:800; color:var(--primary);">'.locale_number_format($R['total']-$R['alloc'],$R['currdecimalplaces']).'</td><td style="text-align:right;"><a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?AllocTrans='.$R['id'].'" class="aw-btn aw-btn-primary aw-btn-sm">'.__('Allocate').'</a></td></tr>'; }
		echo '</tbody></table></div></div>';
	}
} else {
	$Res = DB_query("SELECT debtortrans.id, transno, typename, type, debtortrans.debtorno, name, trandate, (ovamount+ovgst+ovdiscount+ovfreight) as total, alloc, currcode, currencies.decimalplaces AS currdecimalplaces FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN systypes ON debtortrans.type=systypes.typeid INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE (debtortrans.type=12 OR debtortrans.type=11) AND debtortrans.settled=0 AND (debtortrans.ovamount<0 OR debtortrans.ovdiscount<0) ".($_SESSION['SalesmanLogin']!=''?" AND debtortrans.salesperson='".$_SESSION['SalesmanLogin']."'":"")." ORDER BY debtorno, trandate");
	if (DB_num_rows($Res) == 0) { prnMsg(__('No outstanding allocations to process.'), 'info'); }
	else {
		echo '<div class="aw-card"><div class="aw-card-header"><h3 class="aw-card-title">' . __('Universal Allocation Ledger') . '</h3></div><div class="aw-table-wrapper"><table class="aw-table"><thead><tr><th>Customer</th><th>Type</th><th>Ref</th><th>Date</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Unallocated</th><th>Action</th></tr></thead><tbody>';
		while ($R = DB_fetch_array($Res)) { echo '<tr><td style="font-weight:700;">'.$R['name'].' <div style="font-size:0.65rem; color:var(--text-muted);">'.$R['debtorno'].'</div></td><td>'.$R['typename'].'</td><td>'.$R['transno'].'</td><td>'.ConvertSQLDate($R['trandate']).'</td><td style="text-align:right;">'.locale_number_format($R['total'],$R['currdecimalplaces']).'</td><td style="text-align:right; font-weight:800; color:var(--primary);">'.locale_number_format($R['total']-$R['alloc'],$R['currdecimalplaces']).'</td><td style="text-align:right;"><a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?AllocTrans='.$R['id'].'" class="aw-btn aw-btn-primary aw-btn-sm">'.__('Allocate').'</a></td></tr>'; }
		echo '</tbody></table></div></div>';
	}
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
