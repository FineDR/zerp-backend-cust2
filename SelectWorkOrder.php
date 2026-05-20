<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search Work Orders');
$ViewTopic = 'Manufacturing';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2 - High Density
echo '
<style>
	:root {
		--primary: hsl(197, 92%, 47%); 
		--primary-hover: hsl(197, 92%, 38%);
		--primary-dark: hsl(197, 75%, 22%);
		--primary-soft: hsl(197, 65%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(197, 15%, 12%);
		--text-muted: hsl(197, 8%, 50%);
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
		.aw-grid-search { grid-template-columns: 320px 1fr; align-items: start; }
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

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .aw-badge-info { background: var(--primary-soft); color: var(--primary); }
</style>
<div class="aw-container">';

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Exploration</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/WorkOrderEntry.php" class="aw-btn aw-btn-primary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> ' . __('New Work Order') . '</a>
		</div>
	  </div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="SelectWOForm">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($_GET['WO'])) { $SelectedWO = $_GET['WO']; } elseif (isset($_POST['WO'])){ $SelectedWO = $_POST['WO']; } else { unset($SelectedWO); }
if (isset($_GET['SelectedStockItem'])) { $SelectedStockItem = $_GET['SelectedStockItem']; } elseif (isset($_POST['SelectedStockItem'])){ $SelectedStockItem = $_POST['SelectedStockItem']; } else { unset($SelectedStockItem); }
if (isset($_POST['ResetPart'])){ unset($SelectedStockItem); }

echo '<div class="aw-grid aw-grid-search">';

// SIDEBAR (Filters)
echo '<aside class="aw-sidebar-side">';

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> ' . __('WO Search') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-form-group"><label class="aw-label">' . __('Work Order #') . '</label><input type="text" name="WO" class="aw-input" autofocus="autofocus" maxlength="8" value="' . (isset($SelectedWO)?$SelectedWO:'') . '" /></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Location') . '</label><select name="StockLocation" class="aw-select">';
				$LocRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE locations.usedforwo = 1");
				while ($MLoc=DB_fetch_array($LocRes)){ $sel = (isset($_POST['StockLocation']) && $_POST['StockLocation']==$MLoc['loccode']) || (!isset($_POST['StockLocation']) && $MLoc['loccode']==$_SESSION['UserStockLocation']) ? 'selected' : ''; echo '<option ' . $sel . ' value="' . $MLoc['loccode'] . '">' . $MLoc['locationname'] . '</option>'; }
echo '		</select></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Status') . '</label><select name="ClosedOrOpen" class="aw-select">';
				$cur = (isset($_POST['ClosedOrOpen']) ? $_POST['ClosedOrOpen'] : (isset($_GET['ClosedOrOpen']) ? $_GET['ClosedOrOpen'] : 'Open_Only'));
				echo '<option value="Open_Only" '.($cur=='Open_Only'?'selected':'').'>' . __('Open Orders Only') . '</option>';
				echo '<option value="Closed_Only" '.($cur=='Closed_Only'?'selected':'').'>' . __('Closed Orders Only') . '</option>';
echo '		</select></div>
            <button type="submit" name="SearchOrders" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Search Orders') . '</button>
		</div>
	  </div>';

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> ' . __('Search by Item') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-form-group"><label class="aw-label">' . __('Category') . '</label><select name="StockCat" class="aw-select">';
				$CatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
				while ($Cat = DB_fetch_array($CatRes)) { echo '<option value="'. $Cat['categoryid'] . '">' . $Cat['categorydescription'] . '</option>'; }
echo '		</select></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Keywords') . '</label><input type="text" name="Keywords" class="aw-input" /></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Stock Code') . '</label><input type="text" name="StockCode" class="aw-input" /></div>
			<div style="display:flex; gap:4px; margin-top:20px;">
                <button type="submit" name="SearchParts" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('Find') . '</button>
                <button type="submit" name="ResetPart" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('All') . '</button>
            </div>';
            if (isset($SelectedStockItem)) { echo '<div class="aw-badge aw-badge-info" style="margin-top:12px; width:100%; justify-content:center;">' . __('Filtering Item') . ': ' . $SelectedStockItem . '</div><input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />'; }
echo '		</div>
	  </div>';

echo '</aside>';

// MAIN CONTENT (Results)
echo '<main class="aw-main-side">';

if (isset($_POST['SearchParts'])) {
	$Keywords = (isset($_POST['Keywords']) ? mb_strtoupper($_POST['Keywords']) : ''); $SearchString = '%' . str_replace(' ', '%', $Keywords) . '%'; $CodeFilter = (isset($_POST['StockCode']) ? '%' . mb_strtoupper($_POST['StockCode']) . '%' : '%');
	$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, stockmaster.units FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND stockmaster.description " . LIKE . " '" . $SearchString . "' AND stockmaster.stockid " . LIKE . " '" . $CodeFilter . "' AND stockmaster.categoryid='" . $_POST['StockCat']. "' AND stockmaster.mbflag='M' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
	$StockItemsResult = DB_query($SQL);
	echo '<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Catalog Items') . '</h3></div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead><tr><th>' . __('Code') . '</th><th>' . __('Description') . '</th><th style="text-align:right;">' . __('On Hand') . '</th><th>' . __('Units') . '</th></tr></thead>
					<tbody>';
	while ($R=DB_fetch_array($StockItemsResult)) {
		echo '<tr><td><button type="submit" name="SelectedStockItem" value="'.$R['stockid'].'" class="aw-btn aw-btn-primary aw-btn-sm">'.$R['stockid'].'</button></td><td style="font-weight:700;">'.$R['description'].'</td><td style="text-align:right;">'.locale_number_format($R['qoh'],$R['decimalplaces']).'</td><td>'.$R['units'].'</td></tr>';
	}
	echo '</tbody></table></div></div>';
}

if (isset($_POST['SearchOrders']) OR (isset($SelectedWO) AND !isset($_POST['SearchParts']))) {
	$ClosedOrOpen = (isset($_POST['ClosedOrOpen']) and $_POST['ClosedOrOpen']=='Open_Only') ? 0 : 1;
	$SQL = "SELECT workorders.wo, woitems.stockid, stockmaster.description, stockmaster.decimalplaces, woitems.qtyreqd, woitems.qtyrecd, workorders.requiredby, workorders.startdate, workorders.reference, workorders.loccode FROM workorders INNER JOIN woitems ON workorders.wo=woitems.wo INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid INNER JOIN locationusers ON locationusers.loccode=workorders.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE workorders.closed='" . $ClosedOrOpen . "'";
	if (isset($SelectedWO) AND $SelectedWO !='') { $SQL .= " AND workorders.wo='". trim($SelectedWO) ."'"; }
	if (isset($SelectedStockItem)) { $SQL .= " AND woitems.stockid='". $SelectedStockItem ."'"; }
	if (isset($_POST['StockLocation']) AND $_POST['StockLocation'] != '') { $SQL .= " AND workorders.loccode='" . $_POST['StockLocation'] . "'"; }
	$SQL .= " ORDER BY workorders.wo, woitems.stockid";
	
	$Res = DB_query($SQL);
	if (DB_num_rows($Res) > 0) {
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Work Order Results') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('WO# [Ref]') . '</th>
								<th>' . __('Actions') . '</th>
								<th>' . __('Location') . '</th>
								<th>' . __('Output Item') . '</th>
								<th style="text-align:right;">' . __('Required') . '</th>
								<th style="text-align:right;">' . __('Recd') . '</th>
								<th style="text-align:right;">' . __('Outstanding') . '</th>
								<th>' . __('Required By') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($R = DB_fetch_array($Res)) {
			$Action_WO = array('Status' => '/WorkOrderStatus.php?WO=' . $R['wo'] . '&StockID=' . urlencode($R['stockid']), 'Issue' => '/WorkOrderIssue.php?WO=' . $R['wo'] . '&StockID=' . urlencode($R['stockid']), 'Receive' => '/WorkOrderReceive.php?WO=' . $R['wo'] . '&StockID=' . urlencode($R['stockid']), 'Paperwork' => '/PDFWOPrint.php?WO=' . $R['wo'] . '&StockID=' . urlencode($R['stockid']));
			echo '<tr>
					<td style="font-weight:700;"><a href="'.$RootPath.'/WorkOrderEntry.php?WO='.$R['wo'].'" style="text-decoration:none; color:var(--primary);">' . $R['wo'] . ' [' . $R['reference'] . ']</a></td>
					<td style="white-space:nowrap;">';
						foreach($Action_WO as $name => $url) { echo '<a href="'.$RootPath.$url.'" class="aw-btn aw-btn-secondary aw-btn-sm" style="margin-right:2px; font-size:0.65rem;">' . __($name) . '</a>'; }
			echo '	</td>
					<td>' . $R['loccode'] . '</td>
					<td><div style="font-weight:700;">' . $R['stockid'] . '</div><div style="font-size:0.65rem; color:var(--text-muted);">' . $R['description'] . '</div></td>
					<td style="text-align:right;">' . locale_number_format($R['qtyreqd'],$R['decimalplaces']) . '</td>
					<td style="text-align:right;">' . locale_number_format($R['qtyrecd'],$R['decimalplaces']) . '</td>
					<td style="text-align:right; font-weight:800; color:var(--primary);">' . locale_number_format($R['qtyreqd']-$R['qtyrecd'],$R['decimalplaces']) . '</td>
					<td style="white-space:nowrap;">' . ConvertSQLDate($R['requiredby']) . '</td>
				  </tr>';
		}
		echo '</tbody></table></div></div>';
	} else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('No work orders found matching your criteria.') . '</div></div>'; }
} else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Fill in search criteria to explore manufacturing history.') . '</div></div>'; }

echo '</main></div>'; // End aw-grid-search
echo '</form></div>'; // End aw-container

include(__DIR__ . '/includes/footer.php');
?>
