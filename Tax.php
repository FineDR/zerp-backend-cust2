<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PrintPDF']) or isset($_POST['View'])){
	$PeriodEnd = ConvertSQLDate(EndDateSQLFromPeriodNo($_POST['ToPeriod']));
	$TaxAuthDescription = DB_fetch_row(DB_query("SELECT description FROM taxauthorities WHERE taxid='" . $_POST['TaxAuthority'] . "'"));
	$TaxAuthorityName = $TaxAuthDescription[0];
	$ReportTitle = __('Tax Report') . ': ' . $TaxAuthorityName;
	$HTML = '';

	if (isset($_POST['PrintPDF'])) { $HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />'; }
	$HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP"></head><body>';
	if (isset($_POST['PrintPDF'])) $HTML .= '<img class="logo" src=' . $_SESSION['LogoFile'] . ' /><br />';

	$HTML .= '<div class="centre" id="ReportHeader" style="margin-bottom:2rem;">
					<h2 style="margin:0; color:#1e293b;">' . $_SESSION['CompanyRecord']['coyname'] . '</h2>
					<div style="font-weight:800; font-size:1.1rem; color:hsl(197, 92%, 47%);">' . $ReportTitle . '</div>
					<div style="color:#64748b; font-size:0.8rem; margin-top:5px;">' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . ' | ' . __('For Periods') . ' - ' . $_POST['NoOfPeriods'] . ' ' . __('months to') . ' ' . $PeriodEnd . '</div>
				</div>';

	$SQL_Sales = "SELECT debtortrans.trandate, debtortrans.type, systypes.typename, debtortrans.transno, debtorsmaster.name, debtortrans.branchcode, (debtortrans.ovamount+debtortrans.ovfreight)/debtortrans.rate AS netamount, debtortranstaxes.taxamount AS tax FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN systypes ON debtortrans.type=systypes.typeid INNER JOIN debtortranstaxes ON debtortrans.id = debtortranstaxes.debtortransid WHERE debtortrans.prd >= '" . ($_POST['ToPeriod'] - $_POST['NoOfPeriods'] + 1) . "' AND debtortrans.prd <= '" . $_POST['ToPeriod'] . "' AND (debtortrans.type=10 OR debtortrans.type=11) AND debtortranstaxes.taxauthid = '" . $_POST['TaxAuthority'] . "' ORDER BY debtortrans.id";
	$DebtorRes = DB_query($SQL_Sales); $SalesNet = 0; $SalesTax = 0; $SalesCount = 0;

	if ($_POST['DetailOrSummary'] == 'Detail') {
		$HTML .= '<div class="report-section" style="margin-bottom:2rem;">
                    <h3 style="font-size:0.8rem; text-transform:uppercase; color:#1e293b; border-bottom:2px solid hsl(197, 92%, 47%); padding-bottom:5px;">' . __('Tax on Sales (Outputs)') . '</h3>
                    <table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                        <thead><tr style="background:hsl(197, 65%, 95%); color:hsl(197, 75%, 22%);"><th>Date</th><th>Type</th><th>#</th><th>Customer</th><th>Branch</th><th style="text-align:right;">Net</th><th style="text-align:right;">Tax</th></tr></thead>
                        <tbody>';
		while ($R = DB_fetch_array($DebtorRes)) {
			$HTML .= '<tr style="border-bottom:1px solid #eee;"><td>' . ConvertSQLDate($R['trandate']) . '</td><td>' . __($R['typename']) . '</td><td>' . $R['transno'] . '</td><td>' . htmlspecialchars($R['name']) . '</td><td>' . htmlspecialchars($R['branchcode']) . '</td><td style="text-align:right;">' . locale_number_format($R['netamount'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($R['tax'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
			$SalesCount++; $SalesNet += $R['netamount']; $SalesTax += $R['tax'];
		}
		$HTML .= '<tr style="background:#f8fafc; font-weight:800;"><td colspan="5">' . __('Total Sales') . '</td><td style="text-align:right;">' . locale_number_format($SalesNet, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SalesTax, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr></tbody></table></div>';
	} else {
		while ($R = DB_fetch_array($DebtorRes)) { $SalesCount++; $SalesNet += $R['netamount']; $SalesTax += $R['tax']; }
	}

    // Dates for purchases
    $DArr = explode((mb_strpos($PeriodEnd,'/')?'/':(mb_strpos($PeriodEnd,'.')?'.':'-')), $PeriodEnd);
    $idxY=($_SESSION['DefaultDateFormat']=='Y/m/d'||$_SESSION['DefaultDateFormat']=='Y-m-d'?0:2);
    $idxM=($_SESSION['DefaultDateFormat']=='m/d/Y'?0:1);
    $idxD=($_SESSION['DefaultDateFormat']=='d/m/Y'||$_SESSION['DefaultDateFormat']=='d.m.Y'?0:($_SESSION['DefaultDateFormat']=='Y-m-d'?2:1));
    $StartDateSQL = date('Y-m-d', mktime(0, 0, 0, (int)$DArr[$idxM] - $_POST['NoOfPeriods'] + 1, 1, (int)$DArr[$idxY]));

	$SQL_Purch = "SELECT supptrans.trandate, supptrans.type, systypes.typename, supptrans.transno, suppliers.suppname, supptrans.suppreference, supptrans.ovamount/supptrans.rate AS netamount, supptranstaxes.taxamount/supptrans.rate AS taxamt FROM supptrans INNER JOIN suppliers ON supptrans.supplierno=suppliers.supplierid INNER JOIN systypes ON supptrans.type=systypes.typeid INNER JOIN supptranstaxes ON supptrans.id = supptranstaxes.supptransid WHERE supptrans.trandate >= '" . $StartDateSQL . "' AND supptrans.trandate <= '" . FormatDateForSQL($PeriodEnd) . "' AND (supptrans.type=20 OR supptrans.type=21) AND supptranstaxes.taxauthid = '" . $_POST['TaxAuthority'] . "' ORDER BY supptrans.id";
	$SuppRes = DB_query($SQL_Purch); $PurchNet = 0; $PurchTax = 0; $PurchCount = 0;
	if ($_POST['DetailOrSummary'] == 'Detail') {
		$HTML .= '<div class="report-section" style="margin-bottom:2rem;">
                    <h3 style="font-size:0.8rem; text-transform:uppercase; color:#1e293b; border-bottom:2px solid hsl(197, 92%, 47%); padding-bottom:5px;">' . __('Tax on Purchases (Inputs)') . '</h3>
                    <table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                        <thead><tr style="background:hsl(197, 65%, 95%); color:hsl(197, 75%, 22%);"><th>Date</th><th>Type</th><th>#</th><th>Supplier</th><th>Reference</th><th style="text-align:right;">Net</th><th style="text-align:right;">Tax</th></tr></thead>
                        <tbody>';
		while ($R = DB_fetch_array($SuppRes)) {
			$HTML .= '<tr style="border-bottom:1px solid #eee;"><td>' . ConvertSQLDate($R['trandate']) . '</td><td>' . __($R['typename']) . '</td><td>' . $R['transno'] . '</td><td>' . htmlspecialchars($R['suppname']) . '</td><td>' . htmlspecialchars($R['suppreference']) . '</td><td style="text-align:right;">' . locale_number_format($R['netamount'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($R['taxamt'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
			$PurchCount++; $PurchNet += $R['netamount']; $PurchTax += $R['taxamt'];
		}
		$HTML .= '</tbody></table></div>';
	} else {
		while ($R = DB_fetch_array($SuppRes)) { $PurchCount++; $PurchNet += $R['netamount']; $PurchTax += $R['taxamt']; }
	}

    // Petty Cash
    $PettyRes = DB_query("SELECT pcashdetails.date AS trandate, pcashdetailtaxes.pccashdetail AS transno, pcashdetailtaxes.description AS suppreference, pcashdetails.amount AS gross, pcashdetailtaxes.amount AS taxamt, www_users.realname AS suppname FROM pcashdetails INNER JOIN pcashdetailtaxes ON pcashdetails.counterindex=pcashdetailtaxes.pccashdetail INNER JOIN pctabs ON pcashdetails.tabcode = pctabs.tabcode INNER JOIN www_users ON pctabs.usercode=www_users.userid WHERE pcashdetails.date >= '" . $StartDateSQL . "' AND pcashdetails.date <= '" . FormatDateForSQL($PeriodEnd) . "' AND pcashdetailtaxes.taxauthid = '" . $_POST['TaxAuthority'] . "' ORDER BY pcashdetailtaxes.counterindex");
    $PCNet = 0; $PCTax = 0; $PCCount = 0;
	if ($_POST['DetailOrSummary'] == 'Detail') {
		$HTML .= '<div class="report-section" style="margin-bottom:2rem;">
                    <h3 style="font-size:0.8rem; text-transform:uppercase; color:#1e293b; border-bottom:2px solid hsl(197, 92%, 47%); padding-bottom:5px;">' . __('Tax on Petty Cash') . '</h3>
                    <table class="report-table" style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                        <thead><tr style="background:hsl(197, 65%, 95%); color:hsl(197, 75%, 22%);"><th>Date</th><th>#</th><th>Name</th><th>Reference</th><th style="text-align:right;">Net</th><th style="text-align:right;">Tax</th></tr></thead>
                        <tbody>';
		while ($R = DB_fetch_array($PettyRes)) {
			$totTax = DB_fetch_row(DB_query("SELECT SUM(-amount) FROM pcashdetailtaxes WHERE pccashdetail='" . $R['transno'] . "'"))[0];
			$Net = ((-$R['gross']) - $totTax);
			$HTML .= '<tr style="border-bottom:1px solid #eee;"><td>' . ConvertSQLDate($R['trandate']) . '</td><td>' . $R['transno'] . '</td><td>' . htmlspecialchars($R['suppname']) . '</td><td>' . htmlspecialchars($R['suppreference']) . '</td><td style="text-align:right;">' . locale_number_format($Net, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format((-$R['taxamt']), $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
			$PCCount++; $PCNet += $Net; $PCTax += (-$R['taxamt']);
		}
		$HTML .= '</tbody></table></div>';
	} else {
		while ($R = DB_fetch_array($PettyRes)) { $totTax = DB_fetch_row(DB_query("SELECT SUM(-amount) FROM pcashdetailtaxes WHERE pccashdetail='" . $R['transno'] . "'"))[0]; $Net = ((-$R['gross']) - $totTax); $PCCount++; $PCNet += $Net; $PCTax += (-$R['taxamt']); }
	}

	$HTML .= '<div class="summary-section">
                <h3 style="font-size:0.8rem; text-transform:uppercase; color:#1e293b; border-bottom:2px solid hsl(197, 92%, 47%); padding-bottom:5px;">' . __('Tax Summary') . '</h3>
                <table class="summary-table" style="width:100%; border-collapse:collapse; font-size:0.85rem;">
                    <thead><tr style="background:hsl(197, 75%, 22%); color:white;"><th>' . __('Type') . '</th><th style="text-align:right;">' . __('Count') . '</th><th style="text-align:right;">' . __('Net') . '</th><th style="text-align:right;">' . __('Tax') . '</th><th style="text-align:right;">' . __('Total') . '</th></tr></thead>
                    <tbody>';
    $SalesTot = $SalesNet + $SalesTax; $PurchTot = $PurchNet + $PCNet + $PurchTax + $PCTax;
    $HTML .= '<tr style="background:hsl(197, 65%, 95%);"><td>' . __('Outputs (Sales)') . '</td><td style="text-align:right;">' . locale_number_format($SalesCount) . '</td><td style="text-align:right;">' . locale_number_format($SalesNet, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($SalesTax, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right; font-weight:800;">' . locale_number_format($SalesTot, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
    $HTML .= '<tr style="background:#fef2f2;"><td>' . __('Inputs (Purchases/PC)') . '</td><td style="text-align:right;">' . locale_number_format($PurchCount + $PCCount) . '</td><td style="text-align:right;">' . locale_number_format($PurchNet + $PCNet, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right;">' . locale_number_format($PurchTax + $PCTax, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td style="text-align:right; font-weight:800;">' . locale_number_format($PurchTot, $_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>';
    $HTML .= '<tr style="background:white; border-top:2px solid #334155;"><td><b>' . __('NET TAX DUE / (REFUND)') . '</b></td><td></td><td></td><td style="text-align:right; font-weight:900; font-size:1.1rem; color:hsl(197, 92%, 47%);">' . locale_number_format($SalesTax - ($PurchTax + $PCTax), $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td></tr>';
    $HTML .= '</tbody></table></div>';

	$HTML .= '<div style="margin-top:2rem; padding:1rem; background:#f8fafc; border-radius:8px; font-size:0.75rem; color:#64748b; line-height:1.5;">
                ' . __('Note: Adjustments for Tax paid to Customs, FBT, entertainments etc must also be entered. This report excludes journal entries.') . '
              </div></body></html>';

	if ($SalesCount + $PurchCount + $PCCount == 0) {
		$Title = __('Inquiry Results'); include(__DIR__ . '/includes/header.php');
		prnMsg(__('No transactions found for the selected criteria'), 'info');
		include(__DIR__ . '/includes/footer.php');
	} else {
		if (isset($_POST['PrintPDF'])) {
			$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'portrait'); $DomPDF->render();
			$DomPDF->stream($_SESSION['DatabaseName'] . '_TaxReport_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
		} else {
			$Title = __('Tax Report View'); include(__DIR__ . '/includes/header.php');
            echo '<style>.report-grid { max-width: 1000px; margin: 0 auto; background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); } table th, table td { padding: 8px; }</style>';
			echo '<div style="background:hsl(210, 20%, 97%); padding:2rem; min-height:100vh;"><div class="report-grid">' . $HTML . '</div></div>';
			include(__DIR__ . '/includes/footer.php');
		}
	}
} else {
	$Title = __('Tax Reporting');
	include(__DIR__ . '/includes/header.php');
	echo '<style>
        :root { --db-primary: hsl(197, 92%, 47%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; }
        .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; overflow: hidden; }
        .db-card-header { padding: 1rem; border-bottom: 1px solid var(--db-border); }
        .db-card-title { font-size: 0.8rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin:0; }
        .db-card-body { padding: 1.5rem; }
        .db-field { margin-bottom: 1.25rem; }
        .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
        .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.8rem; width: 100%; background:#fdfdfd; }
        .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; border: none; transition: 0.2s; width: 100%; margin-top: 10px; }
        .db-btn-primary { background: var(--db-primary); color: #fff; }
    </style>';

    echo '<div class="db-page"><div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Tax Report Generator') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-field"><label class="db-label">Tax Authority</label><select name="TaxAuthority" class="db-select">';
                $Res = DB_query("SELECT taxid, description FROM taxauthorities");
                while ($R = DB_fetch_array($Res)) echo '<option value="' . $R['taxid'] . '">' . $R['description'] . '</option>';
                echo '</select></div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">Duration</label><select name="NoOfPeriods" class="db-select"><option value="1">1 Month</option><option value="3">3 Months</option><option value="6">6 Months</option><option value="12">12 Months</option></select></div>
                    <div class="db-field"><label class="db-label">To Period</label><select name="ToPeriod" class="db-select">';
                    $DefP = GetPeriod(date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 0, date('Y'))));
                    $Pers = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC");
                    while ($R = DB_fetch_array($Pers)) echo '<option ' . ($R['periodno']==$DefP?'selected':'') . ' value="' . $R['periodno'] . '">' . ConvertSQLDate($R['lastdate_in_period']) . '</option>';
                    echo '</select></div>
                </div>
                <div class="db-field"><label class="db-label">Report Detail</label><select name="DetailOrSummary" class="db-select"><option value="Detail">Detailed Report</option><option selected value="Summary">Summary Only</option></select></div>
                <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">Generate PDF</button>
                <button type="submit" name="View" class="db-btn db-btn-primary" style="background:var(--db-primary-soft); color:var(--db-primary);">View Online</button>
                </form>
            </div>
        </div></div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
