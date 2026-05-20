<?php

require(__DIR__ . '/includes/session.php');

$PricesSecurity = 12; // don't show pricing info unless security token 12 available to user

$Title = __('Stock Status');
$ViewTopic = 'Inventory';
$BookMark = '';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

if (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

if ($StockID != '') {
	$Res = DB_query("SELECT description, units, decimalplaces, mbflag, serialised, controlled FROM stockmaster WHERE stockid='" . $StockID . "'");
	if (DB_num_rows($Res) > 0) {
		$MyRow = DB_fetch_array($Res);
		$Description = $MyRow['description'];
		$Units = $MyRow['units'];
		$DecimalPlaces = $MyRow['decimalplaces'];
		$KitSet = $MyRow['mbflag'];
		$Serialised = $MyRow['serialised'];
		$Controlled = $MyRow['controlled'];
	}
}

?>
<style>
    :root {
        --primary: hsl(197, 92%, 47%);
        --primary-hover: hsl(197, 92%, 38%);
        --primary-dark: hsl(197, 75%, 22%);
        --primary-soft: hsl(197, 65%, 95%);
        --bg: hsl(210, 20%, 97%);
        --white: #ffffff;
        --border: #e2e8f0;
        --border-soft: #f1f5f9;
        --text-main: #334155;
        --text-muted: #64748b;
        --shadow: 0 1px 3px rgba(0,0,0,0.1);
        --radius: 12px;
        --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
    }
    body { background-color: var(--bg); color: var(--text-main); font-family: var(--font-sans); }
    .aw-page { max-width: 1400px; margin: 0 auto; padding: 2rem; }
    .aw-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
    .aw-breadcrumb { font-size: 0.75rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.5rem; }
    .aw-title { font-size: 2rem; font-weight: 900; color: var(--primary-dark); margin: 0; line-height: 1; }
    .aw-layout-grid { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .aw-layout-grid { grid-template-columns: 1fr; } }
    .aw-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); margin-bottom: 1.5rem; overflow: hidden; }
    .aw-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft); background: var(--white); display: flex; align-items: center; gap: 0.75rem; }
    .aw-card-title { font-size: 1rem; font-weight: 700; color: var(--primary-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .aw-card-title i { color: var(--primary); font-size: 1.1rem; }
    .aw-card-body { padding: 1.25rem; }
    .aw-field-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
    .aw-label { font-size: 0.8rem; font-weight: 700; color: var(--primary-dark); }
    .aw-input { width: 100%; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); font-size: 0.9rem; box-sizing: border-box; }
    .aw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; text-decoration: none; }
    .aw-btn-primary { background: var(--primary); color: var(--white); }
    .aw-btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
    .aw-btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    .aw-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .aw-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-soft); }
    .aw-table td { padding: 1rem; border-bottom: 1px solid var(--border-soft); }
    .aw-table tr:hover { background: var(--bg); }
    .aw-table .number { text-align: right; font-family: 'JetBrains Mono', monospace; }
    .aw-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; background: var(--primary-soft); color: var(--primary); }
</style>

<div class="aw-page">
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb"><?php echo __('Inventory'); ?> / <?php echo __('Stock Status'); ?></div>
            <h1 class="aw-title"><?php echo $Title; ?> <?php if ($StockID) echo '<span class="aw-badge">' . $StockID . '</span>'; ?></h1>
            <p style="margin: 10px 0 0; color: var(--text-muted); font-weight: 600;"><?php echo $Description ?? ''; ?></p>
        </div>
        <div>
            <a href="SelectProduct.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-th"></i> <?php echo __('Product Dashboard'); ?></a>
        </div>
    </div>

    <div class="aw-layout-grid">
        <!-- LEFT: LOOKUP & INFO -->
        <aside>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-search"></i> <?php echo __('Item Lookup'); ?></h3></div>
                <div class="aw-card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'); ?>">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Stock Code'); ?></label>
                            <input type="text" name="StockID" class="aw-input" value="<?php echo $StockID; ?>" required autofocus />
                        </div>
                        <button type="submit" class="aw-btn aw-btn-primary w-100"><i class="fas fa-search"></i> <?php echo __('Show Status'); ?></button>
                    </form>
                </div>
            </div>

            <?php if ($StockID != '' && isset($Description)): ?>
                <div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-info-circle"></i> <?php echo __('Specifications'); ?></h3></div>
                    <div class="aw-card-body">
                        <div style="margin-bottom: 1rem;">
                            <label class="aw-label" style="display:block; font-size: 0.7rem; color: var(--text-muted);"><?php echo __('Unit of Measure'); ?></label>
                            <div style="font-weight: 700;"><?php echo $Units; ?></div>
                        </div>
                        <div>
                            <label class="aw-label" style="display:block; font-size: 0.7rem; color: var(--text-muted);"><?php echo __('Decimal Precision'); ?></label>
                            <div style="font-weight: 700;"><?php echo $DecimalPlaces; ?></div>
                        </div>
                    </div>
                </div>

                <div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-external-link-alt"></i> <?php echo __('Quick Insights'); ?></h3></div>
                    <div class="aw-card-body" style="padding: 0.5rem;">
                        <a href="StockMovements.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none; font-size: 0.85rem;"><i class="fas fa-exchange-alt"></i> <?php echo __('Stock Movements'); ?></a>
                        <a href="StockUsage.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none; font-size: 0.85rem;"><i class="fas fa-chart-line"></i> <?php echo __('Stock Usage'); ?></a>
                        <a href="SelectSalesOrder.php?SelectedStockItem=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none; font-size: 0.85rem;"><i class="fas fa-shopping-cart"></i> <?php echo __('Open Sales Orders'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <!-- RIGHT: STATUS TABLE -->
        <main>
            <?php 
            if ($StockID == '') {
                echo '<div class="aw-card"><div class="aw-card-body text-center" style="padding: 4rem;"><i class="fas fa-warehouse fa-3x mb-3" style="color: var(--border);"></i><p style="color: var(--text-muted);">' . __('Search for an item to view location-wise status.') . '</p></div></div>';
            } else {
                $SQL = "SELECT locstock.loccode, locations.locationname, locstock.quantity, locstock.reorderlevel, locstock.bin, locations.managed, canupd FROM locstock INNER JOIN locations ON locstock.loccode=locations.loccode INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE locstock.stockid = '" . $StockID . "' ORDER BY locations.locationname";
                $Res = DB_query($SQL);
                
                if (DB_num_rows($Res) > 0): ?>
                    <div class="aw-card">
                        <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-warehouse"></i> <?php echo __('Location Status'); ?></h3></div>
                        <div class="aw-card-body" style="padding: 0;">
                            <div style="overflow-x: auto;">
                                <table class="aw-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('Location'); ?></th>
                                            <th><?php echo __('Bin'); ?></th>
                                            <th class="number"><?php echo __('On Hand'); ?></th>
                                            <th class="number"><?php echo __('Demand'); ?></th>
                                            <th class="number"><?php echo __('Available'); ?></th>
                                            <th class="number"><?php echo __('On Order'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = DB_fetch_array($Res)): 
                                            $DemandQty = GetDemand($StockID, $row['loccode']);
                                            $QOO = GetQuantityOnOrder($StockID, $row['loccode']);
                                            $Avail = $row['quantity'] - $DemandQty;
                                        ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--primary);"><?php echo $row['locationname']; ?></td>
                                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?php echo $row['bin'] ?: '-'; ?></td>
                                                <td class="number" style="font-weight: 700;"><?php echo locale_number_format($row['quantity'], $DecimalPlaces); ?></td>
                                                <td class="number" style="color: #ef4444;"><?php echo locale_number_format($DemandQty, $DecimalPlaces); ?></td>
                                                <td class="number" style="font-weight: 700; color: var(--primary);"><?php echo locale_number_format($Avail, $DecimalPlaces); ?></td>
                                                <td class="number"><?php echo locale_number_format($QOO, $DecimalPlaces); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="aw-card"><div class="aw-card-body text-center" style="padding: 4rem;"><p><?php echo __('No stock records found for this item.'); ?></p></div></div>
                <?php endif;
            }
            ?>
        </main>
    </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
