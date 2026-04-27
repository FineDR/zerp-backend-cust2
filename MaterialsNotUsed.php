<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Raw Materials Not Used Anywhere');
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

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 16px; margin-top: 20px; }
	.aw-card-header { padding: 12px 16px; border-bottom: 2px solid var(--primary-soft); background: #fff; display: flex; align-items: center; justify-content: space-between; }
	.aw-card-title { font-size: 0.78rem; font-weight: 950; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 2px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }
    .aw-table tr.total-row td { background-color: var(--primary-soft); font-weight: 900; border-top: 2px solid var(--primary); color: var(--primary-dark); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
</style>
<div class="aw-container">';

$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, (stockmaster.actualcost) AS stdcost, (SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid) AS qoh FROM stockmaster, stockcategory WHERE stockmaster.categoryid = stockcategory.categoryid AND stockcategory.stocktype = 'M' AND stockmaster.discontinued = 0 AND NOT EXISTS( SELECT * FROM bom WHERE bom.component = stockmaster.stockid ) ORDER BY stockmaster.stockid";
$Result = DB_query($SQL);

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Inventory Audit</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/SelectProduct.php" class="aw-btn aw-btn-primary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> ' . __('Find Item') . '</a>
		</div>
	  </div>';

if (DB_num_rows($Result) != 0) {
	$TotalValue = 0;
	echo '<div class="aw-card">
			<div class="aw-card-header">
                <h3 class="aw-card-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    ' . __('Materials Analysis List') . '
                </h3>
            </div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th style="width:50px;">' . __('#') . '</th>
							<th>' . __('Code') . '</th>
							<th>' . __('Description') . '</th>
							<th style="text-align:right;">' . __('QOH') . '</th>
							<th style="text-align:right;">' . __('Std Cost') . '</th>
							<th style="text-align:right;">' . __('Inventory Value') . '</th>
						</tr>
					</thead>
					<tbody>';
	$i = 1;
	while ($MyRow = DB_fetch_array($Result)) {
		$LineValue = $MyRow['qoh'] * $MyRow['stdcost'];
		$TotalValue += $LineValue;

		echo '<tr>
				<td style="color:var(--text-muted); font-weight:600;">', $i, '</td>
				<td><a href="' . $RootPath . '/SelectProduct.php?StockID=' . $MyRow['stockid'] . '" style="text-decoration:none; color:var(--primary); font-weight:700;">', $MyRow['stockid'], '</a></td>
				<td style="font-weight:600;">', $MyRow['description'], '</td>
				<td style="text-align:right; font-weight:700;">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td style="text-align:right; color:var(--text-muted);">', locale_number_format($MyRow['stdcost'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td style="text-align:right; font-weight:900; color:var(--primary-dark);">', locale_number_format($LineValue, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';
		$i++;
	}
	echo '</tbody>
		  <tfoot>
			<tr class="total-row">
				<td colspan="5" style="text-align:right;">' . __('ACCUMULATED IDLE INVENTORY VALUE') . ':</td>
				<td style="text-align:right; font-size:1rem;">' . locale_number_format($TotalValue, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			</tr>
		  </tfoot>
		</table></div></div>';
} else {
    echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('No raw materials found that are not utilized in a BOM.') . '</div></div>';
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
