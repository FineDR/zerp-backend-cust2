<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Where Used Inquiry');
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

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 350px 1fr; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .aw-badge-success { background: #d1fae5; color: #059669; }
    .aw-badge-warn { background: #fee2e2; color: #dc2626; }
</style>
<div class="aw-container">';

if (isset($_GET['StockID'])){ $StockID = trim(mb_strtoupper($_GET['StockID'])); } elseif (isset($_POST['StockID'])){ $StockID = trim(mb_strtoupper($_POST['StockID'])); }

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Component Analytics</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/SelectProduct.php" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg> ' . __('Back to Items') . '</a>
		</div>
	  </div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="aw-grid aw-grid-layout">';

// SIDEBAR (Search Input)
echo '<aside class="aw-sidebar-side">';
echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> ' . __('Search Context') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-form-group">
                <label class="aw-label">' . __('Enter Item Code') . '</label>
                <input type="text" required name="StockID" class="aw-input" autofocus value="' . (isset($StockID)?$StockID:'') . '" placeholder="Search component..." />
            </div>
			<button type="submit" name="ShowWhereUsed" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Show Where Used') . '</button>';
            if (isset($StockID)) {
                $Res = DB_query("SELECT description, units FROM stockmaster WHERE stockid='".$StockID."'");
                if (DB_num_rows($Res)>0) {
                    $R = DB_fetch_array($Res);
                    echo '<div style="margin-top:20px; padding:12px; background:var(--primary-soft); border-radius:8px;">
                            <div style="font-size:0.6rem; font-weight:800; color:var(--primary); text-transform:uppercase;">Active Component</div>
                            <div style="font-weight:750; font-size:0.85rem; color:var(--primary-dark); margin-top:2px;">'.$R['description'].'</div>
                            <div style="font-size:0.7rem; color:var(--text-muted);">Units: '.$R['units'].'</div>
                          </div>';
                }
            }
echo '		</div>
	  </div>';
echo '</aside>';

// MAIN AREA (Results)
echo '<main class="aw-main-side">';

if (isset($StockID)) {
	$SQL = "SELECT bom.*, stockmaster.description, stockmaster.discontinued FROM bom INNER JOIN stockmaster ON bom.parent = stockmaster.stockid INNER JOIN locationusers ON locationusers.loccode=bom.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE component='" . $StockID . "' AND bom.effectiveafter <= CURRENT_DATE AND bom.effectiveto > CURRENT_DATE ORDER BY stockmaster.discontinued, bom.parent";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)>0) {
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Parent Assemblies Utilizing Item') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Used By Assembly') . '</th>
								<th>' . __('Status') . '</th>
								<th>' . __('Work Centre') . '</th>
								<th>' . __('Location') . '</th>
								<th style="text-align:right;">' . __('Qty Req') . '</th>
								<th>' . __('Effective Range') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($R=DB_fetch_array($Result)) {
			$Status = ($R['discontinued'] == 1) ? '<span class="aw-badge aw-badge-warn">'.__('Obsolete').'</span>' : '<span class="aw-badge aw-badge-success">'.__('Current').'</span>';
			echo '<tr>
					<td><a href="' . $RootPath . '/BOMInquiry.php?StockID=' . $R['parent'] . '" style="text-decoration:none;"><div style="font-weight:700; color:var(--primary);">' . $R['parent'] . '</div><div style="font-size:0.65rem; color:var(--text-muted);">' . $R['description'] . '</div></a></td>
					<td>' . $Status. '</td>
					<td>' . $R['workcentreadded']. '</td>
					<td>' . $R['loccode']. '</td>
					<td style="text-align:right; font-weight:700;">' . locale_number_format($R['quantity'],'Variable') . '</td>
					<td style="font-size:0.7rem; color:var(--text-muted);">' . ConvertSQLDate($R['effectiveafter']) . ' &rarr; ' . ConvertSQLDate($R['effectiveto']) . '</td>
                </tr>';
		}
		echo '</tbody></table></div></div>';
	} else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('This item is not used as a component of any other parts.') . '</div></div>'; }
} else { echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Perform a search to see the parent hierarchy.') . '</div></div>'; }

echo '</main></div></form></div>';

include(__DIR__ . '/includes/footer.php');
?>
