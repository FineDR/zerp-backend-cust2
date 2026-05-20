<?php

require(__DIR__ . '/includes/session.php');

$Title = __('WO items can be produced with available stock');
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
	.aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
    .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 12px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 16px; margin-top: 20px; }
	.aw-card-header { padding: 12px 16px; border-bottom: 2px solid var(--primary-soft); background: #fff; display: flex; align-items: center; justify-content: space-between; }
	.aw-card-title { font-size: 0.78rem; font-weight: 950; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.78rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.6rem; border-bottom: 2px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .aw-parent-row { background: var(--primary-soft); }
    .aw-parent-row td { font-weight: 900; color: var(--primary-dark); border-top: 2px solid var(--border-color); }
    .aw-child-row td { background-color: #fff; border-bottom: 1px solid #f8fafc; font-size: 0.72rem; color: var(--text-muted); }
    .aw-child-indent { display: inline-block; width: 24px; border-right: 2px solid var(--primary-soft); margin-right: 8px; height: 10px; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; width: 100%; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }
    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .aw-badge-success { background: #d1fae5; color: #059669; }
</style>
<div class="aw-container">';

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Workshop Planning</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

if (isset($_POST['submit'])) {
	$Location = $_POST['Location'];
    $WhereLocation = " AND workorders.loccode = '". $Location ."' ";
	$SQL = "SELECT woitems.wo, woitems.stockid, woitems.qtyreqd, woitems.qtyrecd, stockmaster.decimalplaces, stockmaster.units FROM workorders, woitems, stockmaster WHERE workorders.wo = woitems.wo AND stockmaster.stockid = woitems.stockid AND workorders.closed = 0 AND woitems.qtyreqd > woitems.qtyrecd ". $WhereLocation . "ORDER BY woitems.wo, woitems.stockid";
	$ResultItems = DB_query($SQL);

	if (DB_num_rows($ResultItems) != 0){
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg> ' . __('Production Readiness Analysis') . ' (' . $Location . ')</h3> <a href="'.$RootPath.'/WOCanBeProducedNow.php" class="aw-btn aw-btn-secondary aw-btn-sm" style="text-decoration:none; color:var(--primary);">' . __('Change Facility') . '</a></div>
				<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('WO Reference') . '</th>
							<th>' . __('Parent Item') . '</th>
							<th style="text-align:right;">' . __('Target') . '</th>
							<th style="text-align:right;">' . __('Recd') . '</th>
							<th style="text-align:right;">' . __('Pending') . '</th>
							<th>' . __('Component / Resource') . '</th>
							<th style="text-align:right;">' . __('Facility QOH') . '</th>
							<th style="text-align:right;">' . __('Needed') . '</th>
							<th>' . __('Status') . '</th>
						</tr>
					</thead>
					<tbody>';

		while ($MyItem = DB_fetch_array($ResultItems)) {
			$QtyPending = $MyItem['qtyreqd'] - $MyItem['qtyrecd'];
			$WOLink = '<a href="' . $RootPath . '/WorkOrderEntry.php?WO=' . $MyItem['wo'] . '" style="color:var(--primary); font-weight:900;">' . $MyItem['wo'] . '</a>';
			$CodeLink = '<a href="' . $RootPath . '/SelectProduct.php?StockID=' . $MyItem['stockid'] . '" style="text-decoration:none; color:inherit;">' . $MyItem['stockid'] . '</a>';

			echo '<tr class="aw-parent-row">
					<td>' . $WOLink . '</td>
					<td>' . $CodeLink . '</td>
					<td style="text-align:right;">' . locale_number_format($MyItem['qtyreqd'],$MyItem['decimalplaces']) . '</td>
					<td style="text-align:right;">' . locale_number_format($MyItem['qtyrecd'],$MyItem['decimalplaces']) . '</td>
					<td style="text-align:right;">' . locale_number_format($QtyPending,$MyItem['decimalplaces']) . ' ' . $MyItem['units'] . '</td>
					<td colspan="4"></td>
				</tr>';

			$SQLBOM = "SELECT bom.parent, bom.component, bom.quantity AS bomqty, stockmaster.decimalplaces, stockmaster.units, stockmaster.shrinkfactor, locstock.quantity AS qoh FROM bom, stockmaster, locstock WHERE bom.component = stockmaster.stockid AND bom.component = locstock.stockid AND locstock.loccode = '". $Location ."' AND bom.parent = '" . $MyItem['stockid'] . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE";
			$BOMResult = DB_query($SQLBOM);
			$ItemCanBeproduced = true;

			while ($MyComponent = DB_fetch_array($BOMResult)) {
				$ComponentNeeded = $MyComponent['bomqty'] * $QtyPending;
                if ($MyComponent['qoh'] >= $ComponentNeeded){ $Available = '<span class="aw-badge aw-badge-success">OK</span>'; } else { $Available = '<span class="aw-badge" style="background:#fee2e2; color:#dc2626;">Shortage</span>'; $ItemCanBeproduced = false; }
				echo '<tr class="aw-child-row">
						<td colspan="5"></td>
						<td><span class="aw-child-indent"></span><a href="' . $RootPath . '/SelectProduct.php?StockID=' . $MyComponent['component'] . '" style="color:var(--text-main);">' . $MyComponent['component'] . '</a></td>
						<td style="text-align:right;">' . locale_number_format($MyComponent['qoh'],$MyComponent['decimalplaces']) . '</td>
						<td style="text-align:right; font-weight:700;">' . locale_number_format($ComponentNeeded,$MyComponent['decimalplaces']) . ' ' . $MyComponent['units'] . '</td>
						<td>' . $Available . '</td>
					</tr>';
			}
			if ($ItemCanBeproduced){
				$Action = 'Produce ' . locale_number_format($QtyPending,0) . ' x ' . $MyItem['stockid'];
				echo '<tr class="aw-child-row">
						<td colspan="8"></td>
						<td style="padding:12px;"><a href="' . $RootPath . '/PrintWOItemSlip.php?StockId=' . $MyItem['stockid'] . '&WO='. $MyItem['wo'] . '&Location=' . $Location . '" target="_blank" class="aw-btn aw-btn-primary aw-btn-sm" style="width:auto;">' . $Action . '</a></td>
					</tr>';
			}
		}
		echo '</tbody></table></div></div>';
	} else { prnMsg('No items waiting to be produced in ' . $Location, 'info'); }
} else {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<div class="aw-card" style="max-width:500px; margin: 60px auto;">
				<div class="aw-card-header"><h3 class="aw-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> ' . __('Select Manufacturing Facility') . '</h3></div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Target Factory Location') . '</label>
						<select name="Location" class="aw-select" autofocus>';
						$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE locations.usedforwo = 1";
						$LocnResult = DB_query($SQL);
						while ($MyRow=DB_fetch_array($LocnResult)){ echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>'; }
		echo '			</select>
					</div>
					<button type="submit" name="submit" class="aw-btn aw-btn-primary" style="margin-top:24px;">' . __('Analyze Readiness') . '</button>
				</div>
			</div>
		  </form>';
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
