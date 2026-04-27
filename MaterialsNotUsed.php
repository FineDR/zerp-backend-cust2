<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Raw Materials Not Used Anywhere');
$ViewTopic = 'Manufacturing';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2 - Premium Modernized Audit Table
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
		--radius: 14px;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); line-height: 1.5; }
	.aw-container { padding: 4px 12px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
    .MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	
    .aw-page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; padding: 0 12px; }
	.aw-breadcrumb { font-size: 0.65rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 4px; }
	.aw-page-title { font-size: 1.75rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; line-height: 1; }

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 20px; transition: box-shadow 0.3s ease; }
	.aw-card:hover { box-shadow: var(--shadow-md); }
    
	.aw-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
	.aw-card-title { font-size: 0.85rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 10px; letter-spacing: 0.02em; }
	.aw-card-body { padding: 20px; }

	/* Modern Table Styling */
	.aw-table-wrapper { overflow-x: auto; width: 100%; border-radius: 0 0 var(--radius) var(--radius); }
	.aw-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 0.82rem; }
	.aw-table th { 
        position: sticky; top: 0; z-index: 10;
        text-align: left; padding: 14px 20px; 
        background: #f8fafc; 
        color: var(--text-muted); 
        font-weight: 750; 
        text-transform: uppercase; 
        font-size: 0.65rem; 
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--border-color); 
    }
	.aw-table td { padding: 12px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; transition: background 0.2s; }
	.aw-table tr:last-child td { border-bottom: none; }
	.aw-table tr:hover td { background-color: var(--primary-soft); }
    
    .aw-table tr.total-row td { 
        background-color: var(--primary-dark); 
        color: white; 
        font-weight: 900; 
        border-top: 2px solid var(--primary); 
        padding: 16px 20px;
    }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; border-radius: 10px; font-weight: 800; font-size: 0.8rem; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.15); }
    
    .aw-id-badge { 
        display: inline-block; 
        padding: 4px 10px; 
        background: var(--primary-soft); 
        color: var(--primary); 
        font-weight: 800; 
        border-radius: 8px; 
        font-family: "JetBrains Mono", "Cascadia Code", monospace; 
        font-size: 0.75rem; 
        text-decoration: none;
        border: 1px solid transparent;
        transition: 0.2s;
    }
    .aw-id-badge:hover { border-color: var(--primary); background: white; transform: scale(1.05); }
    
    .aw-mono { font-family: "JetBrains Mono", "Cascadia Code", monospace; letter-spacing: -0.02em; font-weight: 700; }
    .aw-counter { font-size: 0.7rem; color: var(--text-muted); font-weight: 800; width: 30px; }
    
    .aw-search-context { display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 6px 16px; border-radius: 10px; border: 1px solid var(--border-color); }
    .aw-search-icon { color: var(--text-muted); }
    .aw-search-input { border: none; background: transparent; font-size: 0.8rem; font-weight: 600; outline: none; width: 220px; color: var(--text-main); }
    .aw-search-input::placeholder { color: var(--text-muted); opacity: 0.7; }
</style>

<div class="aw-container">';

$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, (stockmaster.actualcost) AS stdcost, (SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid) AS qoh FROM stockmaster, stockcategory WHERE stockmaster.categoryid = stockcategory.categoryid AND stockcategory.stocktype = 'M' AND stockmaster.discontinued = 0 AND NOT EXISTS( SELECT * FROM bom WHERE bom.component = stockmaster.stockid ) ORDER BY stockmaster.stockid";
$Result = DB_query($SQL);

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Logistics / Inventory Control</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/SelectProduct.php" class="aw-btn aw-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> 
                ' . __('Stock Insight') . '
            </a>
		</div>
	  </div>';

if (DB_num_rows($Result) != 0) {
	$TotalValue = 0;
    $TotalCount = DB_num_rows($Result);
	echo '<div class="aw-card">
			<div class="aw-card-header">
                <div style="display:flex; align-items:center; gap:16px;">
                    <h3 class="aw-card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--primary);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        ' . __('Orphaned Raw Materials') . '
                    </h3>
                    <div style="font-size:0.7rem; font-weight:800; background:var(--primary-soft); color:var(--primary); padding:2px 8px; border-radius:6px; text-transform:uppercase;">' . $TotalCount . ' ' . __('Lines Found') . '</div>
                </div>
                <div class="aw-search-context">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" class="aw-search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="aw-search-input" id="tableSearch" placeholder="'.__('Filter materials...').'" onkeyup="filterTable()">
                </div>
            </div>
			<div class="aw-table-wrapper">
				<table class="aw-table" id="auditTable">
					<thead>
						<tr>
							<th style="width:50px;">' . __('#') . '</th>
							<th>' . __('Material Identifier') . '</th>
							<th>' . __('Part Description') . '</th>
							<th style="text-align:right;">' . __('Qty On Hand') . '</th>
							<th style="text-align:right;">' . __('Std Unit Cost') . '</th>
							<th style="text-align:right;">' . __('Value At Cost') . '</th>
						</tr>
					</thead>
					<tbody>';
	$i = 1;
	while ($MyRow = DB_fetch_array($Result)) {
		$LineValue = $MyRow['qoh'] * $MyRow['stdcost'];
		$TotalValue += $LineValue;

		echo '<tr>
				<td class="aw-counter">', str_pad($i, 2, "0", STR_PAD_LEFT), '</td>
				<td><a href="' . $RootPath . '/SelectProduct.php?StockID=' . $MyRow['stockid'] . '" class="aw-id-badge">', $MyRow['stockid'], '</a></td>
				<td style="font-weight:650; color:var(--primary-dark);">', $MyRow['description'], '</td>
				<td style="text-align:right;" class="aw-mono">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td style="text-align:right; color:var(--text-muted);" class="aw-mono">', locale_number_format($MyRow['stdcost'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td style="text-align:right; font-weight:800; color:var(--primary-dark);" class="aw-mono">', locale_number_format($LineValue, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';
		$i++;
	}
	echo '</tbody>
		  <tfoot>
			<tr class="total-row">
				<td colspan="5" style="text-align:right; text-transform:uppercase; letter-spacing:0.05em; font-size:0.75rem;">' . __('Aggregated Potential Dead Stock Value') . ':</td>
				<td style="text-align:right; font-size:1.1rem;" class="aw-mono">' . locale_number_format($TotalValue, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			</tr>
		  </tfoot>
		</table></div></div>';
} else {
    echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent; margin-top:40px;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);"><div style="margin-bottom:12px;"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg></div><div style="font-weight:800; font-size:1.1rem; color:var(--primary-dark);">' . __('No Orphaned Materials') . '</div><div style="font-size:0.85rem; margin-top:8px;">' . __('All raw materials in your inventory are currently linked to at least one active Bill of Materials.') . '</div></div></div>';
}

echo '<script>
function filterTable() {
  var input, filter, table, tr, td, i, txtValue;
  input = document.getElementById("tableSearch");
  filter = input.value.toUpperCase();
  table = document.getElementById("auditTable");
  tr = table.getElementsByTagName("tr");
  for (i = 1; i < tr.length - 1; i++) { // Skip header and footer
    var match = false;
    // Search Code and Description
    for(var col=1; col<=2; col++){
        td = tr[i].getElementsByTagName("td")[col];
        if (td) {
          txtValue = td.textContent || td.innerText;
          if (txtValue.toUpperCase().indexOf(filter) > -1) {
            match = true;
            break;
          }
        }
    }
    tr[i].style.display = match ? "" : "none";
  }
}
</script>';

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
