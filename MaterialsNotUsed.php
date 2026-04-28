<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Raw Materials Not Used Anywhere');
$ViewTopic = 'Manufacturing';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Architect Workspace Design System v3 - Premium Modernized Report
echo '
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
	:root {
		--db-primary: #059669; 
		--db-primary-hover: #047857;
		--db-primary-soft: #ecfdf5;
		--db-secondary: #6b7280;
		--db-danger: #ef4444;
		--db-surface-alt: #f8fafc;
		--db-border: #e2e8f0;
		--db-text-main: #0f172a;
		--db-text-muted: #64748b;
		--db-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
		--db-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
		--radius-lg: 12px;
		--radius-md: 8px;
	}

	body { 
        font-family: "Inter", sans-serif !important; 
        background-color: var(--db-surface-alt) !important; 
        color: var(--db-text-main) !important; 
        margin: 0; 
        padding: 0; 
        line-height: 1.5;
    }
	
	.db-page { padding: 24px; box-sizing: border-box; min-height: 100vh; }
	.db-centered-container { max-width: 1200px; margin: 0 auto; }
	
	/* Page Header */
	.db-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; gap: 16px; flex-wrap: wrap; }
	.db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: flex; align-items: center; gap: 8px; }
	.db-page-title { font-size: 1.875rem; font-weight: 800; color: var(--db-text-main); margin: 0; letter-spacing: -0.02em; }
	
	/* Cards */
	.db-card { background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--db-shadow-sm); border: 1px solid var(--db-border); overflow: hidden; margin-bottom: 24px; }
	.db-card-header { padding: 20px 24px; border-bottom: 1px solid var(--db-border); display: flex; justify-content: space-between; align-items: center; background: #fff; flex-wrap: wrap; gap: 16px; }
	.db-card-title { font-size: 1rem; font-weight: 700; color: var(--db-text-main); margin: 0; display: flex; align-items: center; gap: 10px; }
	.db-card-body { padding: 24px; }

    /* Summary Stats */
    .db-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .db-stat-card { background: #fff; padding: 20px; border-radius: var(--radius-lg); border: 1px solid var(--db-border); display: flex; align-items: center; gap: 16px; }
    .db-stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
    .db-stat-info { display: flex; flex-direction: column; }
    .db-stat-label { font-size: 0.8125rem; font-weight: 600; color: var(--db-text-muted); text-transform: uppercase; letter-spacing: 0.025em; }
    .db-stat-value { font-size: 1.25rem; font-weight: 800; color: var(--db-text-main); }

	/* Tables */
	.db-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
	.monochromatic-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
	.monochromatic-table th { 
        background: #f1f5f9; 
        color: var(--db-text-muted); 
        padding: 14px 20px; 
        text-align: left; 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        letter-spacing: 0.05em;
        border-bottom: 2px solid var(--db-border);
    }
	.monochromatic-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.monochromatic-table tr:hover td { background-color: var(--db-primary-soft); transition: background 0.2s; }
    
    .monochromatic-table tr.total-row td { 
        background-color: #f8fafc; 
        border-top: 2px solid var(--db-border);
        font-weight: 800;
        color: var(--db-text-main);
    }

	/* Buttons & Badges */
	.db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 18px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none; gap: 8px; text-decoration: none; }
	.db-btn-primary { background: var(--db-primary); color: white; }
	.db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); box-shadow: var(--db-shadow-md); }
    
    .db-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 4px; }
    .db-badge-primary { background: var(--db-primary-soft); color: var(--db-primary); }
    .db-badge-outline { border: 1px solid var(--db-border); color: var(--db-text-muted); }
    
    .db-id-link { 
        font-family: "JetBrains Mono", monospace; 
        color: var(--db-primary); 
        font-weight: 700; 
        text-decoration: none;
        padding: 4px 8px;
        background: var(--db-primary-soft);
        border-radius: 4px;
        font-size: 0.8rem;
    }
    .db-id-link:hover { background: var(--db-primary); color: white; }
    
    .db-mono { font-family: "JetBrains Mono", monospace; font-weight: 600; }

    /* Search Input */
    .db-search-group { position: relative; }
    .db-search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--db-text-muted); font-size: 0.875rem; }
    .db-search-input { 
        padding: 10px 14px 10px 40px !important; 
        border: 1px solid var(--db-border) !important; 
        border-radius: var(--radius-md) !important; 
        font-size: 0.9rem !important; 
        width: 280px !important; 
        transition: all 0.2s !important;
        background: #fff !important;
    }
    .db-search-input:focus { outline: none !important; border-color: var(--db-primary) !important; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1) !important; }

	@media (max-width: 768px) {
		.db-page { padding: 16px; }
        .db-page-header { flex-direction: column; align-items: flex-start; }
        .db-search-input { width: 100% !important; }
        .db-btn { width: 100%; }
        
        .monochromatic-table thead { display: none; }
        .monochromatic-table tr { display: block; border: 1px solid var(--db-border); border-radius: var(--radius-md); margin-bottom: 16px; padding: 12px; background: #fff; }
        .monochromatic-table td { display: flex; justify-content: space-between; padding: 8px 0; border: none; text-align: right; }
        .monochromatic-table td::before { content: attr(data-label); font-weight: 700; color: var(--db-text-muted); text-align: left; font-size: 0.75rem; text-transform: uppercase; }
        .monochromatic-table tr.total-row { display: none; }
	}

    /* Pagination Styles */
    .db-pagination-footer { 
        padding: 16px 24px; 
        border-top: 1px solid var(--db-border); 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        background: #f8fafc;
        flex-wrap: wrap;
        gap: 16px;
    }
    .db-pagination-info { font-size: 0.8125rem; color: var(--db-text-muted); font-weight: 600; }
    .db-pagination-controls { display: flex; gap: 8px; flex-wrap: wrap; }
    .db-page-btn { 
        min-width: 36px; 
        height: 36px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 8px; 
        border: 1px solid var(--db-border); 
        background: #fff; 
        color: var(--db-text-main); 
        font-weight: 700; 
        font-size: 0.8125rem; 
        cursor: pointer; 
        transition: all 0.2s;
        padding: 0 8px;
    }
    .db-page-btn:hover:not(:disabled) { border-color: var(--db-primary); color: var(--db-primary); background: var(--db-primary-soft); }
    .db-page-btn.active { background: var(--db-primary); color: white; border-color: var(--db-primary); }
    .db-page-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .db-page-btn.nav-btn { font-size: 0.75rem; }
</style>

<div class="db-page">
    <div class="db-centered-container">';

$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, (stockmaster.actualcost) AS stdcost, (SELECT SUM(quantity) FROM locstock WHERE locstock.stockid = stockmaster.stockid) AS qoh FROM stockmaster, stockcategory WHERE stockmaster.categoryid = stockcategory.categoryid AND stockcategory.stocktype = 'M' AND stockmaster.discontinued = 0 AND NOT EXISTS( SELECT * FROM bom WHERE bom.component = stockmaster.stockid ) ORDER BY stockmaster.stockid";
$Result = DB_query($SQL);

echo '<div class="db-page-header">
		<div>
			<div class="db-breadcrumb"><i class="fas fa-warehouse"></i> Inventory Control / Analysis</div>
			<h1 class="db-page-title">' . $Title . '</h1>
		</div>
		<div class="db-actions">
			<a href="' . $RootPath . '/SelectProduct.php" class="db-btn db-btn-primary">
                <i class="fas fa-search-plus"></i> ' . __('Stock Insight') . '
            </a>
		</div>
	  </div>';

if (DB_num_rows($Result) != 0) {
	$TotalValue = 0;
    $TotalCount = DB_num_rows($Result);
    
    // Summary Stats
    $TempResult = DB_query($SQL);
    $TempTotalValue = 0;
    while ($Row = DB_fetch_array($TempResult)) {
        $TempTotalValue += ($Row['qoh'] * $Row['stdcost']);
    }
    
    echo '<div class="db-stats-grid">
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background: var(--db-primary-soft); color: var(--db-primary);">
                    <i class="fas fa-cubes"></i>
                </div>
                <div class="db-stat-info">
                    <span class="db-stat-label">' . __('Orphaned Lines') . '</span>
                    <span class="db-stat-value">' . number_format($TotalCount) . '</span>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background: #fef2f2; color: var(--db-danger);">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="db-stat-info">
                    <span class="db-stat-label">' . __('Potential Dead Value') . '</span>
                    <span class="db-stat-value">' . $_SESSION['CompanyRecord']['currencydefault'] . ' ' . locale_number_format($TempTotalValue, $_SESSION['CompanyRecord']['decimalplaces']) . '</span>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="db-stat-info">
                    <span class="db-stat-label">' . __('Inventory Status') . '</span>
                    <span class="db-stat-value" style="font-size:0.875rem;">' . __('Action Required') . '</span>
                </div>
            </div>
          </div>';

	echo '<div class="db-card">
			<div class="db-card-header">
                <h3 class="db-card-title">
                    <i class="fas fa-list-ul" style="color:var(--db-primary);"></i>
                    ' . __('Materials Not Linked to any BOM') . '
                </h3>
                <div class="db-search-group">
                    <i class="fas fa-filter db-search-icon"></i>
                    <input type="text" class="db-search-input" id="tableSearch" placeholder="'.__('Search materials or IDs...').'" onkeyup="filterTable()">
                </div>
            </div>
			<div class="db-table-wrapper">
				<table class="monochromatic-table" id="auditTable">
					<thead>
						<tr>
							<th style="width:60px;">' . __('#') . '</th>
							<th>' . __('Material Code') . '</th>
							<th>' . __('Description') . '</th>
							<th style="text-align:right;">' . __('Qty On Hand') . '</th>
							<th style="text-align:right;">' . __('Unit Cost') . '</th>
							<th style="text-align:right;">' . __('Total Value') . '</th>
						</tr>
					</thead>
					<tbody>';
	$i = 1;
	while ($MyRow = DB_fetch_array($Result)) {
		$LineValue = $MyRow['qoh'] * $MyRow['stdcost'];
		$TotalValue += $LineValue;

		echo '<tr>
				<td data-label="' . __('#') . '" style="color:var(--db-text-muted); font-weight:700;">', str_pad($i, 2, "0", STR_PAD_LEFT), '</td>
				<td data-label="' . __('Code') . '"><a href="' . $RootPath . '/SelectProduct.php?StockID=' . $MyRow['stockid'] . '" class="db-id-link">', $MyRow['stockid'], '</a></td>
				<td data-label="' . __('Description') . '" style="font-weight:600;">', $MyRow['description'], '</td>
				<td data-label="' . __('Qty On Hand') . '" style="text-align:right;" class="db-mono">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td data-label="' . __('Unit Cost') . '" style="text-align:right; color:var(--db-text-muted);" class="db-mono">', locale_number_format($MyRow['stdcost'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td data-label="' . __('Total Value') . '" style="text-align:right; font-weight:800; color:var(--db-text-main);" class="db-mono">', locale_number_format($LineValue, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>';
		$i++;
	}
	echo '</tbody>
		  <tfoot>
			<tr class="total-row">
				<td colspan="5" style="text-align:right; text-transform:uppercase; letter-spacing:0.05em; font-size:0.75rem;">' . __('Aggregate Potential Dead Stock Value') . ':</td>
				<td style="text-align:right; font-size:1.125rem; color:var(--db-primary);" class="db-mono">' . $_SESSION['CompanyRecord']['currencydefault'] . ' ' . locale_number_format($TotalValue, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			</tr>
		  </tfoot>
		</table></div>
        <div class="db-pagination-footer">
            <div class="db-pagination-info" id="paginationInfo">Showing 0 to 0 of 0 results</div>
            <div class="db-pagination-controls" id="paginationControls"></div>
        </div>
        </div>';
} else {
    echo '<div class="db-card" style="border: 2px dashed var(--db-border); background:transparent; margin-top:40px;"><div class="db-card-body" style="text-align:center; padding:80px 24px; color:var(--db-text-muted);"><div style="margin-bottom:20px; font-size:3rem; opacity:0.3;"><i class="fas fa-check-circle"></i></div><div style="font-weight:800; font-size:1.25rem; color:var(--db-text-main);">' . __('Inventory optimization Complete!') . '</div><div style="font-size:0.95rem; margin-top:12px; max-width:500px; margin-left:auto; margin-right:auto;">' . __('All raw materials in your inventory are correctly associated with defined Bills of Materials. No orphaned records found.') . '</div><div style="margin-top:24px;"><a href="'.$RootPath.'/index.php" class="db-btn db-btn-primary">'.__('Back to Dashboard').'</a></div></div></div>';
}

echo '<script>
let currentPage = 1;
const rowsPerPage = 10;
let filteredRows = [];

function initPagination() {
    const table = document.getElementById("auditTable");
    if (!table) return;
    const tbodyRows = Array.from(table.getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
    filteredRows = tbodyRows;
    updatePage();
}

function updatePage() {
    const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
    if (currentPage > totalPages) currentPage = Math.max(1, totalPages);
    if (currentPage < 1) currentPage = 1;
    
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    
    const table = document.getElementById("auditTable");
    const allRows = Array.from(table.getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = "none");
    
    // Show only those in the current range among filtered rows
    filteredRows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = "";
        }
    });
    
    renderControls(totalPages);
}

function renderControls(totalPages) {
    const container = document.getElementById("paginationControls");
    const info = document.getElementById("paginationInfo");
    if (!container || !info) return;
    
    container.innerHTML = "";
    
    // Previous Button
    const prevBtn = document.createElement("button");
    prevBtn.className = "db-page-btn nav-btn";
    prevBtn.innerHTML = \'<i class="fas fa-chevron-left"></i>\';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => { currentPage--; updatePage(); };
    container.appendChild(prevBtn);
    
    // Page Numbers
    const maxVisible = 5;
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + maxVisible - 1);
    
    if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1);
    }

    if (startPage > 1) {
        addPageBtn(1, container);
        if (startPage > 2) container.appendChild(createDots());
    }

    for (let i = startPage; i <= endPage; i++) {
        addPageBtn(i, container);
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) container.appendChild(createDots());
        addPageBtn(totalPages, container);
    }
    
    // Next Button
    const nextBtn = document.createElement("button");
    nextBtn.className = "db-page-btn nav-btn";
    nextBtn.innerHTML = \'<i class="fas fa-chevron-right"></i>\';
    nextBtn.disabled = currentPage === totalPages || totalPages === 0;
    nextBtn.onclick = () => { currentPage++; updatePage(); };
    container.appendChild(nextBtn);
    
    // Info Text
    const visibleCount = filteredRows.length;
    const startIdx = visibleCount === 0 ? 0 : (currentPage - 1) * rowsPerPage + 1;
    const endIdx = Math.min(currentPage * rowsPerPage, visibleCount);
    info.innerText = `Showing ${startIdx} to ${endIdx} of ${visibleCount} results`;
}

function addPageBtn(i, container) {
    const btn = document.createElement("button");
    btn.className = `db-page-btn ${i === currentPage ? "active" : ""}`;
    btn.innerText = i;
    btn.onclick = () => { currentPage = i; updatePage(); };
    container.appendChild(btn);
}

function createDots() {
    const span = document.createElement("span");
    span.innerText = "...";
    span.style.padding = "0 8px";
    span.style.color = "var(--db-text-muted)";
    return span;
}

function filterTable() {
    const input = document.getElementById("tableSearch");
    const filter = input.value.toUpperCase();
    const table = document.getElementById("auditTable");
    if (!table) return;
    const tbodyRows = Array.from(table.getElementsByTagName("tbody")[0].getElementsByTagName("tr"));
    
    filteredRows = tbodyRows.filter(row => {
        const cells = row.getElementsByTagName("td");
        for (let col = 1; col <= 2; col++) {
            const txtValue = (cells[col].textContent || cells[col].innerText).toUpperCase();
            if (txtValue.indexOf(filter) > -1) return true;
        }
        return false;
    });
    
    currentPage = 1;
    updatePage();
}

// Initialize on load
document.addEventListener("DOMContentLoaded", initPagination);
</script>';

echo '</div></div>'; // End centered container and db-page
include(__DIR__ . '/includes/footer.php');
?>


