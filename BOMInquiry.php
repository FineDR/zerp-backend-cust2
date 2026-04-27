<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Costed Bill Of Material');
$ViewTopic = 'Manufacturing';
$BookMark = '';
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
	.aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
    .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 12px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-search { grid-template-columns: 320px 1fr; align-items: start; }
        .aw-grid-report { grid-template-columns: 350px 1fr; align-items: start; }
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
    .aw-table tr.total-row td { background-color: #f8fafc; font-weight: 700; border-top: 2px solid var(--border-color); }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-stat-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
    .aw-stat-label { font-size: 0.7rem; font-weight: 700; color: var(--text-muted); }
    .aw-stat-val { font-size: 0.8rem; font-weight: 800; color: var(--primary-dark); }
</style>
<div class="aw-container">';

if (isset($_GET['StockID'])){ $StockID =trim(mb_strtoupper($_GET['StockID'])); } elseif (isset($_POST['StockID'])){ $StockID =trim(mb_strtoupper($_POST['StockID'])); }

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Bill of Materials</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/BOMListing.php" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg> ' . __('BOM Listing') . '</a>
		</div>
	  </div>';

if (!isset($StockID)) {
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <div class="aw-grid aw-grid-search">';

    // SIDEBAR (Search Filters)
    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> ' . __('Search Part') . '</h3></div>
                <div class="aw-card-body">
                    <div class="aw-form-group"><label class="aw-label">' . __('Description Keywords') . '</label><input type="text" name="Keywords" class="aw-input" autofocus /></div>
                    <div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Stock Code Extract') . '</label><input type="text" name="StockCode" class="aw-input" /></div>
                    <button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Search Items') . '</button>
                    <div style="font-size:0.65rem; color:var(--text-muted); margin-top:12px;">' . __('Search manufactured, assembly, kit or phantom items.') . '</div>
                </div>
            </div>
          </aside>';

    // MAIN AREA (Search Results)
    echo '<main class="aw-main-side">';
    if (isset($_POST['Search'])){
        if ($_POST['Keywords']=='' AND $_POST['StockCode']=='') { $_POST['StockCode']='%'; }
        $Keywords = mb_strtoupper($_POST['Keywords']); $SearchString = '%' . str_replace(' ', '%', $Keywords) . '%'; $CodeExtract = mb_strtoupper($_POST['StockCode']);
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, SUM(locstock.quantity) as totalonhand FROM stockmaster INNER JOIN locstock ON stockmaster.stockid = locstock.stockid WHERE 1=1";
        if (mb_strlen($Keywords)>0) { $SQL .= " AND stockmaster.description " . LIKE . "'" . $SearchString . "'"; } elseif (mb_strlen($CodeExtract)>0){ $SQL .= " AND stockmaster.stockid " . LIKE  . "'%" . $CodeExtract . "%'"; }
        $SQL .= " AND (stockmaster.mbflag='M' OR stockmaster.mbflag='K' OR stockmaster.mbflag='A' OR stockmaster.mbflag='G') GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag ORDER BY stockmaster.stockid";
        $Result = DB_query($SQL);
        if (DB_num_rows($Result)>0) {
            echo '<div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title">' . __('Select Assembly to View') . '</h3></div>
                    <div class="aw-table-wrapper">
                        <table class="aw-table">
                            <thead><tr><th>' . __('Code') . '</th><th>' . __('Description') . '</th><th>' . __('On Hand') . '</th><th>' . __('Units') . '</th></tr></thead>
                            <tbody>';
            while ($MyRow=DB_fetch_array($Result)) {
                $StockOnHand = ($MyRow['mbflag']=='A' OR $MyRow['mbflag']=='K' OR $MyRow['mbflag']=='G') ? 'N/A' : locale_number_format($MyRow['totalonhand'],2);
                echo '<tr>
                        <td><button type="submit" name="StockID" value="'.$MyRow['stockid'].'" class="aw-btn aw-btn-primary aw-btn-sm">'.$MyRow['stockid'].'</button></td>
                        <td style="font-weight:700;">'.$MyRow['description'].'</td>
                        <td>'.$StockOnHand.'</td>
                        <td>'.$MyRow['units'].'</td>
                      </tr>';
            }
            echo '</tbody></table></div></div>';
        } else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('No parts found matching your criteria.') . '</div></div>'; }
    } else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Enter search criteria to explore assembly structures.') . '</div></div>'; }
    echo '</main></div></form>';
} else {
    // REPORT VIEW
    $Result = DB_query("SELECT description, units, labourcost, overheadcost FROM stockmaster WHERE stockid='" . $StockID  . "'");
	$MyRow = DB_fetch_array($Result);
	$ParentLabourCost = $MyRow['labourcost'];
	$ParentOverheadCost = $MyRow['overheadcost'];

	$SQL = "SELECT bom.parent, bom.component, stockmaster.description, stockmaster.decimalplaces, stockmaster.actualcost as standardcost, bom.quantity, bom.quantity * (stockmaster.actualcost) AS componentcost FROM bom INNER JOIN stockmaster ON bom.component = stockmaster.stockid WHERE bom.parent = '" . $StockID . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE";
	$BOMResult = DB_query($SQL);

    echo '<div class="aw-grid aw-grid-report">';
    
    // SIDEBAR (Item Profile)
    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Selected Assembly') . '</h3> <a href="'.$RootPath.'/BOMInquiry.php" class="aw-btn aw-btn-secondary aw-btn-sm">' . __('New Search') . '</a></div>
                <div class="aw-card-body">
                    <div style="font-weight:950; font-size:1.1rem; color:var(--primary-dark); line-height:1.2; margin-bottom:4px;">' . $StockID . '</div>
                    <div style="font-size:0.85rem; font-weight:600; color:var(--text-muted); margin-bottom:20px;">' . $MyRow['description'] . '</div>
                    <div class="aw-stat-row"><span class="aw-stat-label">Measure Unit</span><span class="aw-stat-val">'.$MyRow['units'].'</span></div>
                    <div class="aw-stat-row"><span class="aw-stat-label">Internal Labour</span><span class="aw-stat-val">'.locale_number_format($ParentLabourCost, 2).'</span></div>
                    <div class="aw-stat-row"><span class="aw-stat-label">Internal Overhead</span><span class="aw-stat-val">'.locale_number_format($ParentOverheadCost, 2).'</span></div>
                </div>
            </div>
            <div class="aw-card" style="background:var(--primary-soft);">
                <div class="aw-card-body">
                    <div style="font-size:0.65rem; color:var(--primary); font-weight:800; text-transform:uppercase; margin-bottom:8px;">Costing Note</div>
                    <div style="font-size:0.75rem; color:var(--primary-dark); line-height:1.5;">Costs shown are based on Current Standard Costs at the time of inquiry.</div>
                </div>
            </div>
          </aside>';

    // MAIN AREA (BOM Breakdown)
    echo '<main class="aw-main-side">';
    if (DB_num_rows($BOMResult)>0) {
        echo '<div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Bill of Material Breakdown') . '</h3></div>
                <div class="aw-table-wrapper">
                    <table class="aw-table">
                        <thead><tr><th>' . __('Component') . '</th><th>' . __('Description') . '</th><th style="text-align:right;">' . __('Quantity') . '</th><th style="text-align:right;">' . __('Unit Cost') . '</th><th style="text-align:right;">' . __('Line Total') . '</th></tr></thead>
                        <tbody>';
        $TotalCompCost = 0;
        while ($CompRow=DB_fetch_array($BOMResult)) {
            echo '<tr>
                    <td><a href="'.$RootPath.'/SelectProduct.php?StockID='.$CompRow['component'].'" style="text-decoration:none; color:var(--primary); font-weight:700;">'.$CompRow['component'].'</a></td>
                    <td>'.$CompRow['description'].'</td>
                    <td style="text-align:right;">'.locale_number_format($CompRow['quantity'],$CompRow['decimalplaces']).'</td>
                    <td style="text-align:right;">'.locale_number_format($CompRow['standardcost'],$_SESSION['CompanyRecord']['decimalplaces'] + 2).'</td>
                    <td style="text-align:right; font-weight:700;">'.locale_number_format($CompRow['componentcost'],$_SESSION['CompanyRecord']['decimalplaces'] + 2).'</td>
                  </tr>';
            $TotalCompCost += $CompRow['componentcost'];
        }
        $GrandTotal = $TotalCompCost + $ParentLabourCost + $ParentOverheadCost;
        echo '</tbody>
              <tfoot>
                <tr class="total-row"><td colspan="4" style="text-align:right;">' . __('Labour Cost') . '</td><td style="text-align:right;">' . locale_number_format($ParentLabourCost,$_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>
                <tr class="total-row"><td colspan="4" style="text-align:right;">' . __('Overhead Cost') . '</td><td style="text-align:right;">' . locale_number_format($ParentOverheadCost,$_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>
                <tr class="total-row" style="background:var(--primary-soft); color:var(--primary-dark); font-size:1rem;"><td colspan="4" style="text-align:right; font-weight:950;">' . __('GRAND TOTAL COST') . '</td><td style="text-align:right; font-weight:950;">' . locale_number_format($GrandTotal,$_SESSION['CompanyRecord']['decimalplaces']) . '</td></tr>
              </tfoot>
            </table></div></div>';
    } else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('No components defined for this part.') . '</div></div>'; }
    echo '</main></div>';
}

echo '</div>'; // End aw-container

include(__DIR__ . '/includes/footer.php');
?>
