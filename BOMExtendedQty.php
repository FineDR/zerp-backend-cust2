<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Quantity Extended BOM Listing');
$ViewTopic = 'Manufacturing';
$BookMark = '';

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
    if (!$_POST['Quantity'] or !is_numeric(filter_number_format($_POST['Quantity']))) { $_POST['Quantity'] = 1; }
    $BuildQty = filter_number_format($_POST['Quantity']);

    DB_query("DROP TABLE IF EXISTS tempbom; DROP TABLE IF EXISTS passbom; DROP TABLE IF EXISTS passbom2;");
    DB_query("CREATE TEMPORARY TABLE passbom (part char(20), extendedqpa double, sortpart text) DEFAULT CHARSET=utf8");
    DB_query("CREATE TEMPORARY TABLE tempbom (parent char(20), component char(20), sortpart text, level int, workcentreadded char(5), loccode char(5), effectiveafter date, effectiveto date, quantity double) DEFAULT CHARSET=utf8");
    DB_query("INSERT INTO passbom (part, extendedqpa, sortpart) SELECT bom.component AS part, (" . $BuildQty . " * bom.quantity) as extendedqpa, CONCAT(bom.parent,bom.component) AS sortpart FROM bom WHERE bom.parent ='" . $_POST['Part'] . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
    $LevelCounter = 2;
    DB_query("INSERT INTO tempbom (parent, component, sortpart, level, workcentreadded, loccode, effectiveafter, effectiveto, quantity) SELECT bom.parent, bom.component, CONCAT(bom.parent,bom.component) AS sortpart, $LevelCounter as level, bom.workcentreadded, bom.loccode, bom.effectiveafter, bom.effectiveto, (" . $BuildQty . " * bom.quantity) as extendedqpa FROM bom WHERE bom.parent ='" . $_POST['Part'] . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");

    $ComponentCounter = 1;
    while ($ComponentCounter > 0) { $LevelCounter++;
        DB_query("INSERT INTO tempbom (parent, component, sortpart, level, workcentreadded, loccode, effectiveafter, effectiveto, quantity) SELECT bom.parent, bom.component, CONCAT(passbom.sortpart,bom.component) AS sortpart, $LevelCounter as level, bom.workcentreadded, bom.loccode, bom.effectiveafter, bom.effectiveto, (bom.quantity * passbom.extendedqpa) FROM bom,passbom WHERE bom.parent = passbom.part AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
        DB_query("DROP TABLE IF EXISTS passbom2; ALTER TABLE passbom RENAME AS passbom2; DROP TABLE IF EXISTS passbom; CREATE TEMPORARY TABLE passbom (part char(20), extendedqpa decimal(10,3), sortpart text) DEFAULT CHARSET=utf8;");
        DB_query("INSERT INTO passbom (part, extendedqpa, sortpart) SELECT bom.component AS part, (bom.quantity * passbom2.extendedqpa), CONCAT(passbom2.sortpart,bom.component) AS sortpart FROM bom INNER JOIN passbom2 ON bom.parent = passbom2.part WHERE bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
        $MyRow = DB_fetch_array(DB_query("SELECT COUNT(bom.parent) AS components FROM bom INNER JOIN passbom ON bom.parent = passbom.part GROUP BY passbom.part")); $ComponentCounter = $MyRow['components'];
    }

    $SQL = "SELECT tempbom.component, SUM(tempbom.quantity) as quantity, stockmaster.description, stockmaster.decimalplaces, stockmaster.mbflag, (SELECT SUM(locstock.quantity) as invqty FROM locstock INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE locstock.stockid = tempbom.component GROUP BY locstock.stockid) AS qoh, (SELECT SUM(purchorderdetails.quantityord - purchorderdetails.quantityrecd) as netqty FROM purchorderdetails INNER JOIN purchorders ON purchorderdetails.orderno=purchorders.orderno INNER JOIN locationusers ON locationusers.loccode=purchorders.intostocklocation AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE purchorderdetails.itemcode = tempbom.component AND purchorderdetails.completed = 0 AND (purchorders.status = 'Authorised' OR purchorders.status='Printed') GROUP BY purchorderdetails.itemcode) AS poqty, (SELECT SUM(woitems.qtyreqd - woitems.qtyrecd) as netwoqty FROM woitems INNER JOIN workorders ON woitems.wo = workorders.wo INNER JOIN locationusers ON locationusers.loccode=workorders.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE woitems.stockid = tempbom.component AND workorders.closed=0 GROUP BY woitems.stockid) AS woqty FROM tempbom INNER JOIN stockmaster ON tempbom.component = stockmaster.stockid INNER JOIN locationusers ON locationusers.loccode=tempbom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 GROUP BY tempbom.component, stockmaster.description, stockmaster.decimalplaces, stockmaster.mbflag";
    $Result = DB_query($SQL);

    if (isset($_POST['PrintPDF'])) {
        $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Extended Quantity BOM Listing For') . ' ' . mb_strtoupper($_POST['Part']) . '<br /> build qty: '.locale_number_format($BuildQty, 0).'</div>';
        $HTML .= '<table><thead><tr><th>' . __('Part Number') . '</th><th>' . __('M/B') . '</th><th>' . __('Description') . '</th><th>' . __('Build Qty') . '</th><th>' . __('On Hand') . '</th><th>' . __('P.O.') . '</th><th>' . __('W.O.') . '</th><th>' . __('Shortage') . '</th></tr></thead><tbody>';
        while ($Row = DB_fetch_array($Result)){ $Shortage = $Row['quantity'] - ($Row['qoh'] + $Row['poqty'] + $Row['woqty']); if (($_POST['Select'] == 'All') or ($Shortage > 0)) { $HTML .= '<tr class="striped_row"><td>'.$Row['component'].'</td><td>'.$Row['mbflag'].'</td><td>'.$Row['description'].'</td><td class="number">'.locale_number_format($Row['quantity'],$Row['decimalplaces']).'</td><td class="number">'.locale_number_format($Row['qoh'],$Row['decimalplaces']).'</td><td class="number">'.locale_number_format($Row['poqty'],$Row['decimalplaces']).'</td><td class="number">'.locale_number_format($Row['woqty'],$Row['decimalplaces']).'</td><td class="number">'.locale_number_format($Shortage,$Row['decimalplaces']).'</td></tr>'; } }
        $HTML .= '</tbody></table></body></html>';
        $DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'landscape'); $DomPDF->render(); $DomPDF->stream($_SESSION['DatabaseName'] . '_BOMExtendedQty_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
        exit();
    } else {
        include(__DIR__ . '/includes/header.php');
        echo '<style>
            :root { --primary: hsl(197, 92%, 47%); --primary-hover: hsl(197, 92%, 38%); --primary-dark: hsl(197, 75%, 22%); --primary-soft: hsl(197, 65%, 95%); --bg-workspace: hsl(210, 20%, 97%); --border-color: hsl(220, 15%, 88%); --danger: #e11d48; --danger-soft: #fff1f2; }
            body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
            .aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
            .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
            .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-top: 16px; }
            .aw-card-header { padding: 12px 16px; border-bottom: 2px solid var(--primary-soft); background: white; display: flex; align-items: center; justify-content: space-between; }
            .aw-card-title { font-size: 0.85rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
            .aw-table { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
            .aw-table th { text-align: left; padding: 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.6rem; border-bottom: 2px solid var(--border-color); }
            .aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
            .aw-shortage-warn { color: var(--danger); font-weight: 800; background: var(--danger-soft); }
            .aw-stat-pill { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; background: var(--primary-soft); border-radius: 99px; font-size: 0.75rem; font-weight: 800; color: var(--primary-dark); }
        </style>
        <div class="aw-container">
            <div class="aw-card">
                <div class="aw-card-header">
                    <div><h3 class="aw-card-title">'.__('Extended Requirements Analysis').' &rarr; '.$_POST['Part'].'</h3></div>
                    <div class="aw-stat-pill">Build Quantity: '.locale_number_format($BuildQty, 0).'</div>
                    <a href="'.$RootPath.'/BOMExtendedQty.php" class="aw-btn" style="text-decoration:none; color:var(--primary); font-weight:800;">&larr; '.__('New Probe').'</a>
                </div>
                <div style="overflow-x:auto;">
                <table class="aw-table">
                    <thead><tr><th>'.__('Component Part').'</th><th>'.__('M/B').'</th><th>'.__('Description').'</th><th style="text-align:right;">'.__('Required').'</th><th style="text-align:right;">'.__('On Hand').'</th><th style="text-align:right;">'.__('On P.O.').'</th><th style="text-align:right;">'.__('On W.O.').'</th><th style="text-align:right;">'.__('Shortage').'</th></tr></thead>
                    <tbody>';
        while ($Row = DB_fetch_array($Result)){
            $Shortage = $Row['quantity'] - ($Row['qoh'] + $Row['poqty'] + $Row['woqty']);
            if (($_POST['Select'] == 'All') or ($Shortage > 0)) {
                $ShortStyle = ($Shortage > 0) ? 'class="aw-shortage-warn"' : '';
                echo '<tr>
                        <td style="font-weight:700; color:var(--primary);">'.$Row['component'].'</td>
                        <td><span style="font-size:0.6rem; font-weight:800; background:#f1f5f9; padding:2px 4px; border-radius:4px;">'.$Row['mbflag'].'</span></td>
                        <td>'.$Row['description'].'</td>
                        <td style="text-align:right; font-weight:700;">'.locale_number_format($Row['quantity'],$Row['decimalplaces']).'</td>
                        <td style="text-align:right; color:var(--text-muted);">'.locale_number_format($Row['qoh'],$Row['decimalplaces']).'</td>
                        <td style="text-align:right; color:var(--text-muted);">'.locale_number_format($Row['poqty'],$Row['decimalplaces']).'</td>
                        <td style="text-align:right; color:var(--text-muted);">'.locale_number_format($Row['woqty'],$Row['decimalplaces']).'</td>
                        <td style="text-align:right;" '.$ShortStyle.'>'.locale_number_format($Shortage,$Row['decimalplaces']).'</td>
                      </tr>';
            }
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
        .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 60px auto; overflow: hidden; }
        .aw-card-header { padding: 16px; border-bottom: 2px solid var(--primary-soft); display: flex; align-items: center; gap: 12px; }
        .aw-card-title { font-size: 0.9rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
        .aw-card-body { padding: 24px; }
        .aw-label { display: block; font-size: 0.72rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 6px; }
        .aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 10px 12px; font-size: 0.85rem; outline: none; transition: 0.2s; margin-bottom: 20px; background:white; }
        .aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 24px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; cursor: pointer; transition: 0.2s; border: none; gap: 10px; text-decoration: none; width: 100%; }
        .aw-btn-primary { background: var(--primary); color: white; margin-bottom: 12px; }
        .aw-btn-primary:hover { background: var(--primary-hover); }
        .aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
    </style>
    <div class="aw-container">
        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="aw-card">
                <div class="aw-card-header">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--primary);"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    <h3 class="aw-card-title">' . __('Extended Requirements Filter') . '</h3>
                </div>
                <div class="aw-card-body">
                    <label class="aw-label">' . __('Assembly Part Code') . '</label>
                    <input type="text" name="Part" class="aw-input" required autofocus placeholder="e.g. FG-1002" />
                    
                    <label class="aw-label">' . __('Target Build Quantity') . '</label>
                    <input type="text" name="Quantity" class="aw-input" required value="1" />
                    
                    <label class="aw-label">' . __('Reporting View') . '</label>
                    <select name="Select" class="aw-select">
                        <option value="All">' . __('Show All Components') . '</option>
                        <option value="Shortages">' . __('Only Show Critical Shortages') . '</option>
                    </select>
                    
                    <button type="submit" name="View" class="aw-btn aw-btn-primary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> ' . __('Run Scarcity Probe') . '</button>
                    <button type="submit" name="PrintPDF" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> ' . __('Export Analysis PDF') . '</button>
                </div>
            </div>
        </form>
    </div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
