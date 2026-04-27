<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Reverse Indented BOM Listing');
$ViewTopic = 'Manufacturing';
$BookMark = '';

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {
	DB_query("DROP TABLE IF EXISTS tempbom; DROP TABLE IF EXISTS passbom; DROP TABLE IF EXISTS passbom2;");
	DB_query("CREATE TEMPORARY TABLE passbom (part char(20), sortpart text) DEFAULT CHARSET=utf8");
	DB_query("CREATE TEMPORARY TABLE tempbom (parent char(20), component char(20), sortpart text, level int, workcentreadded char(5), loccode char(5), effectiveafter date, effectiveto date, quantity double) DEFAULT CHARSET=utf8");
	DB_query("INSERT INTO passbom (part, sortpart) SELECT bom.parent AS part, CONCAT(bom.component,bom.parent) AS sortpart FROM bom WHERE bom.component ='" . $_POST['Part'] . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
	$LevelCounter = 2;
	DB_query("INSERT INTO tempbom (parent, component, sortpart, level, workcentreadded, loccode, effectiveafter, effectiveto, quantity) SELECT bom.parent, bom.component, CONCAT(bom.component,bom.parent) AS sortpart, " . $LevelCounter . " AS level, bom.workcentreadded, bom.loccode, bom.effectiveafter, bom.effectiveto, bom.quantity FROM bom WHERE bom.component ='" . $_POST['Part'] . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");

	$ComponentCounter = 1;
	while ($ComponentCounter > 0) { $LevelCounter++;
		DB_query("INSERT INTO tempbom (parent, component, sortpart, level, workcentreadded, loccode, effectiveafter, effectiveto, quantity) SELECT bom.parent, bom.component, CONCAT(passbom.sortpart,bom.parent) AS sortpart, " . $LevelCounter . " AS level, bom.workcentreadded, bom.loccode, bom.effectiveafter, bom.effectiveto, bom.quantity FROM bom,passbom WHERE bom.component = passbom.part AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
		DB_query("DROP TABLE IF EXISTS passbom2; ALTER TABLE passbom RENAME AS passbom2; DROP TABLE IF EXISTS passbom; CREATE TEMPORARY TABLE passbom (part char(20), sortpart text) DEFAULT CHARSET=utf8;");
		DB_query("INSERT INTO passbom (part, sortpart) SELECT bom.parent AS part, CONCAT(passbom2.sortpart,bom.parent) AS sortpart FROM bom,passbom2 WHERE bom.component = passbom2.part AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE");
		$MyRow = DB_fetch_row(DB_query("SELECT COUNT(*) FROM bom,passbom WHERE bom.component = passbom.part")); $ComponentCounter = $MyRow[0];
	}
	$MyRow = DB_fetch_array(DB_query("SELECT description FROM stockmaster WHERE stockid = '" . $_POST['Part'] . "'"));
	$Assembly = $_POST['Part']; $AssemblyDesc = $MyRow['description'];

	if (isset($_POST['PrintPDF'])) {
		$HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
		$HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Reverse Indented BOM Listing for Component') . ' ' . mb_strtoupper($_POST['Part']) . '<br />' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br /></div>';
		$HTML .= '<table><thead><tr><th>' . __('Part Number') . '</th><th>' . __('M/B') . '</th><th>' . __('Description') . '</th><th>' . __('Location') . '</th><th>' . __('WC') . '</th><th>' . __('Quantity') . '</th><th>' . __('UOM') . '</th><th>' . __('From') . '</th><th>' . __('To') . '</th></tr></thead><tbody>';
		$Result = DB_query("SELECT tempbom.*, stockmaster.description, stockmaster.mbflag, stockmaster.units FROM tempbom INNER JOIN stockmaster ON tempbom.parent = stockmaster.stockid INNER JOIN locationusers ON locationusers.loccode=tempbom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY sortpart");
		while ($MyRow = DB_fetch_array($Result)){ $HTML .= '<tr class="striped_row"><td>' . $MyRow['parent'] . '</td><td>' . $MyRow['mbflag'] . '</td><td>' . $MyRow['description'] . '</td><td>' . $MyRow['loccode'] . '</td><td>' . $MyRow['workcentreadded'] . '</td><td class="number">' . locale_number_format($MyRow['quantity'],'Variable') . '</td><td>' . $MyRow['units'] . '</td><td class="date">' . ConvertSQLDate($MyRow['effectiveafter']) . '</td><td class="date">' . ConvertSQLDate($MyRow['effectiveto']) . '</td></tr>'; }
		$HTML .= '</tbody></table></body></html>';
		$DomPDF = new Dompdf($DomPDFOptions); $DomPDF->loadHtml($HTML); $DomPDF->setPaper($_SESSION['PageSize'], 'landscape'); $DomPDF->render(); $DomPDF->stream($_SESSION['DatabaseName'] . '_BOMIndentedReverse_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
		exit();
	} else {
		include(__DIR__ . '/includes/header.php');
		echo '<style>
            :root { --primary: hsl(145, 63%, 38%); --primary-hover: hsl(145, 63%, 32%); --primary-dark: hsl(145, 45%, 22%); --primary-soft: hsl(145, 40%, 95%); --bg-workspace: hsl(210, 20%, 97%); --border-color: hsl(220, 15%, 88%); }
            body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
            .aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
            .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
            .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-top: 16px; }
            .aw-card-header { padding: 12px 16px; border-bottom: 2px solid var(--primary-soft); background: white; display: flex; align-items: center; justify-content: space-between; }
            .aw-card-title { font-size: 0.85rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
            .aw-table { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
            .aw-table th { text-align: left; padding: 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.6rem; border-bottom: 2px solid var(--border-color); }
            .aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
            .aw-parent-stack { background: var(--primary-soft); font-weight: 900; color: var(--primary-dark); }
            .aw-level-dot { display: inline-block; width: 20px; height: 1px; vertical-align: middle; position: relative; }
            .aw-level-dot::before { content: ""; position: absolute; left: 0; top: -14px; width: 1px; height: 18px; background: var(--border-color); }
            .aw-level-dot::after { content: ""; position: absolute; left: 0; top: 4px; width: 8px; height: 1px; background: var(--border-color); }
            .aw-code { font-weight: 700; color: var(--primary); }
        </style>
        <div class="aw-container">
            <div class="aw-card">
                <div class="aw-card-header">
                    <div><h3 class="aw-card-title">'.__('Reverse Explosion').': '.$Assembly.'</h3></div>
                    <a href="'.$RootPath.'/BOMIndentedReverse.php" class="aw-btn" style="text-decoration:none; color:var(--primary); font-weight:800;">&larr; '.__('Restart Trace').'</a>
                </div>
                <div style="overflow-x:auto;">
                <table class="aw-table">
                    <thead><tr><th>'.__('Upward Hierarchy / Parent').'</th><th>'.__('M/B').'</th><th>'.__('Description').'</th><th>'.__('Loc').'</th><th>'.__('WC').'</th><th style="text-align:right;">'.__('Usage Qty').'</th><th>'.__('UOM').'</th><th>'.__('Date Range').'</th></tr></thead>
                    <tbody>
                        <tr class="aw-parent-stack"><td>'.$Assembly.'</td><td colspan="7" style="font-size:0.65rem; text-transform:uppercase; color:var(--primary);">Trace Origin Component</td></tr>';
        $Result = DB_query("SELECT tempbom.*, stockmaster.description, stockmaster.mbflag, stockmaster.units FROM tempbom INNER JOIN stockmaster ON tempbom.parent = stockmaster.stockid INNER JOIN locationusers ON locationusers.loccode=tempbom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY sortpart");
        while ($Row = DB_fetch_array($Result)){
            $Level = $Row['level'] - 1;
            echo '<tr>
                    <td>';
            for($i=0; $i<$Level; $i++){ echo '<span class="aw-level-dot"></span>'; }
            echo '<span class="aw-code">'.$Row['parent'].'</span></td>
                    <td><span style="font-size:0.65rem; font-weight:800; background:#f1f5f9; padding:2px 4px; border-radius:4px;">'.$Row['mbflag'].'</span></td>
                    <td>'.$Row['description'].'</td>
                    <td>'.$Row['loccode'].'</td>
                    <td>'.$Row['workcentreadded'].'</td>
                    <td style="text-align:right; font-weight:700;">'.locale_number_format($Row['quantity'],'Variable').'</td>
                    <td style="font-size:0.7rem; color:var(--text-muted);">'.$Row['units'].'</td>
                    <td style="font-size:0.65rem; color:var(--text-muted);">'.ConvertSQLDate($Row['effectiveafter']).' &rarr; '.ConvertSQLDate($Row['effectiveto']).'</td>
                  </tr>';
        }
        echo '</tbody></table></div></div>
        <div class="centre" style="margin-top:20px;"><form><input type="submit" class="aw-btn" style="background:var(--primary-dark); color:white; width:auto; padding:10px 30px; font-weight:800; border-radius:8px; border:none; cursor:pointer;" value="' . __('Close Trace') . '" onclick="window.close()" /></form></div>
        </div>';
		include(__DIR__ . '/includes/footer.php');
	}
} else {
	include(__DIR__ . '/includes/header.php');
    echo '<style>
        :root { --primary: hsl(145, 63%, 38%); --primary-hover: hsl(145, 63%, 32%); --primary-dark: hsl(145, 45%, 22%); --primary-soft: hsl(145, 40%, 95%); --bg-workspace: hsl(210, 20%, 97%); --border-color: hsl(220, 15%, 88%); }
        body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
        .aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
        .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
        .aw-card { background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); width: 100%; max-width: 600px; margin: 60px auto; overflow: hidden; }
        .aw-card-header { padding: 16px; border-bottom: 2px solid var(--primary-soft); display: flex; align-items: center; gap: 12px; }
        .aw-card-title { font-size: 0.9rem; font-weight: 900; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
        .aw-card-body { padding: 24px; }
        .aw-label { display: block; font-size: 0.72rem; font-weight: 800; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 6px; }
        .aw-input { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 0.85rem; outline: none; transition: 0.2s; margin-bottom: 24px; background:white; }
        .aw-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
        .aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 14px 24px; border-radius: 8px; font-weight: 800; font-size: 0.85rem; cursor: pointer; transition: 0.2s; border: none; gap: 10px; text-decoration: none; width: 100%; }
        .aw-btn-primary { background: var(--primary); color: white; margin-bottom: 12px; }
        .aw-btn-primary:hover { background: var(--primary-hover); }
        .aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
    </style>
    <div class="aw-container">
        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="aw-card">
                <div class="aw-card-header">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--primary);"><path d="M7 11V7a5 5 0 0 1 10 0v4"></path><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M12 18v-3"></path></svg>
                    <h3 class="aw-card-title">' . __('Reverse BOM Explosion') . '</h3>
                </div>
                <div class="aw-card-body">
                    <label class="aw-label">' . __('Component Part Code') . '</label>
                    <input type="text" name="Part" class="aw-input" required autofocus placeholder="e.g. COMP-001" />
                    
                    <button type="submit" name="View" class="aw-btn aw-btn-primary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="18 15 12 9 6 15"></polyline></svg> ' . __('Trace Upward Hierarchy') . '</button>
                    <button type="submit" name="PrintPDF" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg> ' . __('Export Trace PDF') . '</button>
                    <div style="font-size:0.65rem; color:var(--text-muted); margin-top:20px; text-align:center; line-height:1.4;">' . __('This report identifies all parent assemblies that utilize the specified component at any depth of their structure.') . '</div>
                </div>
            </div>
        </form>
    </div>';
	include(__DIR__ . '/includes/footer.php');
}
?>
