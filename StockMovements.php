<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Stock Movements');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryMovement';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

if (!isset($_POST['BeforeDate']) OR !Is_Date($_POST['BeforeDate'])) {
	$_POST['BeforeDate'] = date($_SESSION['DefaultDateFormat']);
}
if (!isset($_POST['AfterDate']) OR !Is_Date($_POST['AfterDate'])) {
	$_POST['AfterDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 1, date('y')));
}

?>
<style>
    :root {
        --primary: hsl(145, 63%, 38%);
        --primary-hover: hsl(145, 63%, 32%);
        --primary-dark: hsl(145, 45%, 22%);
        --primary-soft: hsl(145, 40%, 95%);
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
    .aw-input, .aw-select { width: 100%; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); font-size: 0.9rem; box-sizing: border-box; }
    .aw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; text-decoration: none; }
    .aw-btn-primary { background: var(--primary); color: var(--white); }
    .aw-btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
    .aw-btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    .aw-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .aw-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-soft); }
    .aw-table td { padding: 1rem; border-bottom: 1px solid var(--border-soft); }
    .aw-table .number { text-align: right; font-family: 'JetBrains Mono', monospace; }
    .aw-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; background: var(--primary-soft); color: var(--primary); }
</style>

<div class="aw-page">
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb"><?php echo __('Inventory'); ?> / <?php echo __('Movements'); ?></div>
            <h1 class="aw-title"><?php echo $Title; ?> <?php if ($StockID) echo '<span class="aw-badge">' . $StockID . '</span>'; ?></h1>
        </div>
        <div>
            <a href="SelectProduct.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-th"></i> <?php echo __('Product Dashboard'); ?></a>
        </div>
    </div>

    <div class="aw-layout-grid">
        <!-- LEFT: FILTERS -->
        <aside>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-filter"></i> <?php echo __('Search Criteria'); ?></h3></div>
                <div class="aw-card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Stock Code'); ?></label>
                            <input type="text" name="StockID" class="aw-input" value="<?php echo $StockID; ?>" required />
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Location'); ?></label>
                            <select name="StockLocation" class="aw-select">
                                <?php 
                                $SQL_Loc = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY locationname";
                                $ResStkLocs = DB_query($SQL_Loc);
                                while ($RowLoc = DB_fetch_array($ResStkLocs)) {
                                    echo '<option ' . ((($_POST['StockLocation'] ?? '') == $RowLoc['loccode']) ? 'selected' : '') . ' value="' . $RowLoc['loccode'] . '">' . $RowLoc['locationname'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('From Date'); ?></label>
                            <input name="AfterDate" type="date" class="aw-input" value="<?php echo FormatDateForSQL($_POST['AfterDate']); ?>" />
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('To Date'); ?></label>
                            <input name="BeforeDate" type="date" class="aw-input" value="<?php echo FormatDateForSQL($_POST['BeforeDate']); ?>" />
                        </div>
                        <button type="submit" name="ShowMoves" class="aw-btn aw-btn-primary w-100"><i class="fas fa-sync"></i> <?php echo __('Show Movements'); ?></button>
                    </form>
                </div>
            </div>

            <?php if ($StockID != ''): ?>
                <div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-external-link-alt"></i> <?php echo __('Related'); ?></h3></div>
                    <div class="aw-card-body" style="padding: 0.5rem;">
                        <a href="StockStatus.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none; font-size: 0.85rem;"><i class="fas fa-warehouse"></i> <?php echo __('Stock Status'); ?></a>
                        <a href="SelectSalesOrder.php?SelectedStockItem=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none; font-size: 0.85rem;"><i class="fas fa-shopping-cart"></i> <?php echo __('Sales Orders'); ?></a>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <!-- RIGHT: TABLE -->
        <main>
            <?php 
            if ($StockID == '' || !isset($_POST['ShowMoves'])) {
                echo '<div class="aw-card"><div class="aw-card-body text-center" style="padding: 4rem;"><i class="fas fa-exchange-alt fa-3x mb-3" style="color: var(--border);"></i><p style="color: var(--text-muted);">' . __('Search for an item to view movement history.') . '</p></div></div>';
            } else {
                $SQLBeforeDate = FormatDateForSQL($_POST['BeforeDate']);
                $SQLAfterDate = FormatDateForSQL($_POST['AfterDate']);
                $SQL = "SELECT stockmoves.*, systypes.typename, stockmaster.decimalplaces, stockmaster.controlled, stockmaster.serialised FROM stockmoves INNER JOIN systypes ON stockmoves.type=systypes.typeid INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid WHERE stockmoves.loccode='" . ($_POST['StockLocation'] ?? '') . "' AND stockmoves.trandate >= '" . $SQLAfterDate . "' AND stockmoves.stockid = '" . $StockID . "' AND stockmoves.trandate <= '" . $SQLBeforeDate . "' AND hidemovt=0 ORDER BY stkmoveno DESC";
                $Res = DB_query($SQL);
                
                if (DB_num_rows($Res) > 0): ?>
                    <div class="aw-card">
                        <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-list"></i> <?php echo __('Movement History'); ?></h3></div>
                        <div class="aw-card-body" style="padding: 0;">
                            <div style="overflow-x: auto;">
                                <table class="aw-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('Type / #'); ?></th>
                                            <th><?php echo __('Date'); ?></th>
                                            <th><?php echo __('User / Ref'); ?></th>
                                            <th class="number"><?php echo __('Qty'); ?></th>
                                            <th class="number"><?php echo __('New QOH'); ?></th>
                                            <th><?php echo __('Narrative'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = DB_fetch_array($Res)): ?>
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 700; color: var(--primary);"><?php echo $row['typename']; ?></div>
                                                    <div style="font-size: 0.7rem; color: var(--text-muted);">#<?php echo $row['transno']; ?></div>
                                                </td>
                                                <td style="white-space: nowrap;"><?php echo ConvertSQLDate($row['trandate']); ?></td>
                                                <td>
                                                    <div style="font-weight: 600; font-size: 0.8rem;"><?php echo $row['userid']; ?></div>
                                                    <div style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $row['reference']; ?></div>
                                                </td>
                                                <td class="number" style="font-weight: 700; color: <?php echo $row['qty'] < 0 ? '#ef4444' : '#166534'; ?>;">
                                                    <?php echo locale_number_format($row['qty'], $row['decimalplaces']); ?>
                                                </td>
                                                <td class="number" style="font-weight: 700;"><?php echo locale_number_format($row['newqoh'], $row['decimalplaces']); ?></td>
                                                <td style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $row['narrative']; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="aw-card"><div class="aw-card-body text-center" style="padding: 4rem;"><p><?php echo __('No movements found for the selected period.'); ?></p></div></div>
                <?php endif;
            }
            ?>
        </main>
    </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
