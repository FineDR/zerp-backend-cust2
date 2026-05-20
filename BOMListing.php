<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Bill Of Material Listing');
$ViewTopic = 'Manufacturing';
$BookMark = '';

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	$SQL = "SELECT bom.parent, bom.component, stockmaster.description as compdescription, stockmaster.decimalplaces, stockmaster.units, bom.quantity, bom.loccode, bom.workcentreadded, bom.effectiveto AS eff_to, bom.effectiveafter AS eff_frm FROM stockmaster INNER JOIN bom ON stockmaster.stockid=bom.component INNER JOIN locationusers ON locationusers.loccode=bom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE bom.parent >= '" . $_POST['FromCriteria'] . "' AND bom.parent <= '" . $_POST['ToCriteria'] . "' AND bom.effectiveto > CURRENT_DATE AND bom.effectiveafter <= CURRENT_DATE ORDER BY bom.parent, bom.component";
	$BOMResult = DB_query($SQL);
	if (DB_num_rows($BOMResult)==0){
	   include(__DIR__ . '/includes/header.php');
	   prnMsg( __('The Bill of Material listing has no bills to report on'),'warn');
	   include(__DIR__ . '/includes/footer.php');
	   exit();
	}

	if (isset($_POST['PrintPDF'])) {
		$HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />';
        $HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP https://www.weberp.org"></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">'.$_SESSION['CompanyRecord']['coyname'].'<br />'.__('Bill Of Material Listing for Parts Between').' '.$_POST['FromCriteria'].' '.__('and').' '.$_POST['ToCriteria'].'<br />'.__('Printed').': '.date($_SESSION['DefaultDateFormat']).'<br /></div>';
        $HTML .= '<table><thead><tr><th>'.__('Component Part').'</th><th>'.__('Description').'</th><th>'.__('Effective After').'</th><th>'.__('Effective To').'</th><th>'.__('Location').'</th><th>'.__('Work Centre').'</th><th>'.__('Quantity').'</th></tr></thead><tbody>';
        $ParentPart = '';
        while ($BOMList = DB_fetch_array($BOMResult)){
            if ($ParentPart!= $BOMList['parent']){ $ParentRow = DB_fetch_row(DB_query("SELECT description FROM stockmaster WHERE stockid = '" . $BOMList['parent'] . "'")); $HTML .= '<tr class="total_row"><td>' . $BOMList['parent'] . '</td><td>' . $ParentRow[0] . '</td><td colspan="5"></td></tr>'; $ParentPart = $BOMList['parent']; }
            $HTML .= '<tr class="striped_row"><td>'.$BOMList['component'].'</td><td>'.$BOMList['compdescription'].'</td><td class="date">'.ConvertSQLDate($BOMList['eff_frm']).'</td><td class="date">'.ConvertSQLDate($BOMList['eff_to']).'</td><td>'.$BOMList['loccode'].'</td><td>'.$BOMList['workcentreadded'].'</td><td class="number">'.locale_number_format($BOMList['quantity'],$BOMList['decimalplaces']).' '.$BOMList['units'].'</td></tr>';
        }
        $HTML .= '</tbody></table><div class="footer fixed-section"><div class="right"><span class="page-number">Page </span></div></div></body></html>';
        $DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'landscape'); $DomPDF->render(); $DomPDF->stream($_SESSION['DatabaseName'] . '_BOMListing_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
        exit();
    } else {
        include(__DIR__ . '/includes/header.php');
        echo '<style>
            :root { --primary: hsl(197, 92%, 47%); --primary-dark: hsl(197, 75%, 22%); --primary-soft: hsl(197, 65%, 95%); --bg-workspace: hsl(210, 20%, 97%); --border-color: hsl(220, 15%, 88%); }
            body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
            .aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
            .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
            .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-top: 16px; }
            .aw-card-header { padding: 12px 16px; border-bottom: 2px solid var(--primary-soft); background: white; display: flex; align-items: center; justify-content: space-between; }
            .aw-card-title { font-size: 0.85rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
            .aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
            .aw-table th { text-align: left; padding: 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--border-color); }
            .aw-table td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
            .aw-parent-row td { background: var(--primary-soft); color: var(--primary-dark); font-weight: 900; border-top: 1px solid var(--primary-soft); }
            .aw-code { font-weight: 700; color: var(--primary); }
        </style>
        <div class="aw-container">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">'.__('Bill of Material Listing Report').'</h3> <a href="'.$RootPath.'/BOMListing.php" class="aw-btn" style="text-decoration:none; color:var(--primary); font-weight:700;">&larr; '.__('Change Filter').'</a></div>
                <div style="overflow-x:auto;">
                <table class="aw-table">
                    <thead><tr><th>'.__('Part / Component').'</th><th>'.__('Description').'</th><th>'.__('Eff After').'</th><th>'.__('Eff To').'</th><th>'.__('Loc').'</th><th>'.__('WC').'</th><th style="text-align:right;">'.__('Qty').'</th></tr></thead>
                    <tbody>';
        $ParentPart = '';
        while ($Row = DB_fetch_array($BOMResult)){
            if ($ParentPart != $Row['parent']){ 
                $ParentRow = DB_fetch_row(DB_query("SELECT description FROM stockmaster WHERE stockid = '" . $Row['parent'] . "'")); 
                echo '<tr class="aw-parent-row"><td>'.$Row['parent'].'</td><td colspan="6">'.$ParentRow[0].'</td></tr>'; 
                $ParentPart = $Row['parent']; 
            }
            echo '<tr>
                    <td style="padding-left:24px;"><span class="aw-code">'.$Row['component'].'</span></td>
                    <td>'.$Row['compdescription'].'</td>
                    <td style="color:var(--text-muted); font-size:0.7rem;">'.ConvertSQLDate($Row['eff_frm']).'</td>
                    <td style="color:var(--text-muted); font-size:0.7rem;">'.ConvertSQLDate($Row['eff_to']).'</td>
                    <td>'.$Row['loccode'].'</td>
                    <td>'.$Row['workcentreadded'].'</td>
                    <td style="text-align:right; font-weight:700;">'.locale_number_format($Row['quantity'],$Row['decimalplaces']).' <span style="font-size:0.6rem; color:var(--text-muted);">'.$Row['units'].'</span></td>
                  </tr>';
        }
        echo '</tbody></table></div></div></div>';
        include(__DIR__ . '/includes/footer.php');
    }
} else {
	include(__DIR__ . '/includes/header.php');
    echo '<style>
        :root { --primary: hsl(197, 92%, 47%); --primary-hover: hsl(197, 92%, 38%); --primary-dark: hsl(197, 75%, 22%); --primary-soft: hsl(197, 65%, 95%); --bg-workspace: hsl(210, 20%, 97%); --border-color: hsl(220, 15%, 88%); }
        body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
        .aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
        .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
        .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 40px auto; overflow: hidden; }
        .aw-card-header { padding: 16px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 12px; }
        .aw-card-title { font-size: 0.9rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
        .aw-card-body { padding: 24px; }
        .aw-label { display: block; font-size: 0.72rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 6px; }
        .aw-input { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; outline: none; transition: 0.2s; margin-bottom: 20px; }
        .aw-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
        .aw-btn-primary { background: var(--primary); color: white; width: 100%; margin-bottom: 12px; }
        .aw-btn-primary:hover { background: var(--primary-hover); }
        .aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); width: 100%; }
        .aw-btn-secondary:hover { background: #f1f5f9; }
    </style>
    <div class="aw-container">
        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="aw-card">
                <div class="aw-card-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--primary);"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    <h3 class="aw-card-title">' . __('Report Criteria') . '</h3>
                </div>
                <div class="aw-card-body">
                    <label class="aw-label">' . __('From Inventory Part Code') . '</label>
                    <input type="text" name="FromCriteria" class="aw-input" required autofocus value="1" />
                    
                    <label class="aw-label">' . __('To Inventory Part Code') . '</label>
                    <input type="text" name="ToCriteria" class="aw-input" required value="zzzzzzz" />
                    
                    <button type="submit" name="View" class="aw-btn aw-btn-primary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg> ' . __('View Report') . '</button>
                    <button type="submit" name="PrintPDF" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> ' . __('Download PDF') . '</button>
                    <div style="font-size:0.65rem; color:var(--text-muted); margin-top:20px; text-align:center;">' . __('Listings include only active Bill of Materials for components with valid effective dates.') . '</div>
                </div>
            </div>
        </form>
    </div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
