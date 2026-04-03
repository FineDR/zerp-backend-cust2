<?php
/* Modern Inventory Dashboard - High-fidelity overview for Warehouse & Stock */

// 1. Data Fetching
$today = date('Y-m-d');

// Total Unique Items
$sqlTotal = "SELECT COUNT(*) as total_items FROM stockmaster WHERE mbflag != 'K' AND mbflag != 'D'"; // Exclude kits and dummy items
$resTotal = DB_query($sqlTotal);
$rowTotal = DB_fetch_array($resTotal);
$totalItems = $rowTotal['total_items'] ?? 0;

// Low Stock Alerts (Items where quantity < reorderlevel across all locations)
$sqlLowStock = "SELECT COUNT(DISTINCT locstock.stockid) as low_count 
                FROM locstock 
                JOIN stockmaster ON locstock.stockid = stockmaster.stockid
                WHERE locstock.quantity < locstock.reorderlevel 
                AND stockmaster.mbflag != 'K' AND stockmaster.mbflag != 'D'";
$resLowStock = DB_query($sqlLowStock);
$rowLowStock = DB_fetch_array($resLowStock);
$lowStockCount = $rowLowStock['low_count'] ?? 0;

// Out of Stock Items
$sqlOutStock = "SELECT COUNT(DISTINCT stockid) as out_count 
                FROM (SELECT stockid, SUM(quantity) as total_qty FROM locstock GROUP BY stockid) as q 
                WHERE total_qty <= 0";
$resOutStock = DB_query($sqlOutStock);
$rowOutStock = DB_fetch_array($resOutStock);
$outStockCount = $rowOutStock['out_count'] ?? 0;

// In Stock (Total unique items with > 0 qty)
$inStockCount = $totalItems - $outStockCount;

// Stock Distribution by Category (Top 5)
$stockDist = [];
$sqlDist = "SELECT stockcategory.categorydescription, COUNT(stockmaster.stockid) as item_count 
            FROM stockmaster 
            JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid 
            GROUP BY stockcategory.categorydescription 
            ORDER BY item_count DESC LIMIT 5";
$resDist = DB_query($sqlDist);
while ($row = DB_fetch_assoc($resDist)) {
    $stockDist[] = $row;
}

// Recent Stock Movements
$recentMoves = [];
$sqlMoves = "SELECT stockmoves.stockid, stockmaster.description, stockmoves.trandate, stockmoves.qty, stockmoves.loccode 
             FROM stockmoves 
             JOIN stockmaster ON stockmoves.stockid = stockmaster.stockid 
             ORDER BY stockmoves.stkmoveno DESC LIMIT 6";
$resMoves = DB_query($sqlMoves);
while ($row = DB_fetch_assoc($resMoves)) {
    $recentMoves[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Inventory Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Warehouse & Stock Control') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/Stocks.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('Add New Item') ?>
            </a>
            <a href="<?= $RootPath ?>/StockAdjustments.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <?= __('Adjust Stock') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2"></path><path d="M21 12v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6"></path><path d="M3 8h18"></path><path d="M3 12h18"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Items') ?></span>
                <span class="db-kpi-value"><?= $totalItems ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('In-Stock Items') ?></span>
                <span class="db-kpi-value"><?= $inStockCount ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Low Stock') ?></span>
                <span class="db-kpi-value" style="color: var(--warning);"><?= $lowStockCount ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-red">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Out of Stock') ?></span>
                <span class="db-kpi-value" style="color: var(--danger);"><?= $outStockCount ?></span>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="db-row-2col">
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Category Distribution') ?></h3>
            <div class="db-dist-list">
                <?php foreach ($stockDist as $dist): 
                    $pct = ($dist['item_count'] / $totalItems) * 100;
                ?>
                <div class="db-dist-item">
                    <div class="db-dist-header">
                        <span><?= htmlspecialchars($dist['categorydescription']) ?></span>
                        <span style="color: var(--primary);"><?= $dist['item_count'] ?> <?= __('Items') ?></span>
                    </div>
                    <div class="db-dist-bar-bg">
                        <div class="db-dist-bar-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Warehouse Status') ?></h3>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative;">
                <svg viewBox="0 0 100 100" width="140" height="140">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--border-soft)" stroke-width="12" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="280 251" stroke-linecap="round" />
                </svg>
                <div style="position: absolute; text-align: center;">
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">92%</div>
                    <div style="font-size: 0.65rem; color: var(--text-muted);"><?= __('Optimal') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Movements -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Stock Movements') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Item ID') ?></th>
                        <th><?= __('Description') ?></th>
                        <th><?= __('Location') ?></th>
                        <th><?= __('Quantity') ?></th>
                        <th><?= __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMoves)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent movements found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMoves as $move): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);"><?= $move['stockid'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($move['description']) ?></td>
                            <td><span class="db-badge db-badge-info"><?= $move['loccode'] ?></span></td>
                            <td style="font-weight: 700; color: <?= $move['qty'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $move['qty'] > 0 ? '+' : '' ?><?= number_format($move['qty'], 0) ?>
                            </td>
                            <td style="color: var(--text-muted); text-align: right;"><?= date('d M H:i', strtotime($move['trandate'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Extended Menu -->
    <div class="legacy-menu-container">
        <div class="legacy-menu-header">
            <span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                <?= __('Extended Inventory Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_Stock_' . $Type;
            ?>
            <div class="legacy-menu-section">
                <h4 onclick="let g = document.getElementById('<?= $sectionId ?>'); g.style.display = (g.style.display == 'none' ? 'grid' : 'none');" style="cursor: pointer;">
                    <?= __($Type) ?>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                </h4>
                <div id="<?= $sectionId ?>" class="legacy-menu-tile-grid" style="display: none;">
                    <?php 
                    $i = 0;
                    if (isset($MenuItems[$_SESSION['Module']][$Type])) {
                        foreach ($MenuItems[$_SESSION['Module']][$Type]['Caption'] as $Caption) {
                            $URL = $MenuItems[$_SESSION['Module']][$Type]['URL'][$i];
                            $ScriptName = explode('?', substr($URL, 1))[0];
                            if (isset($_SESSION['PageSecurityArray'][$ScriptName])) {
                                $Security = $_SESSION['PageSecurityArray'][$ScriptName];
                                if (in_array($Security, $_SESSION['AllowedPageSecurityTokens'])) {
                                    ?>
                                    <a href="<?= $RootPath . $URL ?>" class="legacy-menu-tile">
                                        <div class="legacy-menu-tile-icon">
                                            <?= $icons[$Type] ?>
                                        </div>
                                        <span class="legacy-menu-tile-text"><?= __($Caption) ?></span>
                                    </a>
                                    <?php
                                }
                            }
                            ++$i;
                        }
                    }
                    if ($Type == 'Reports') {
                        $rptLinks = GetRptLinks($_SESSION['Module']);
                        preg_match_all('/<a href="([^"]+)">([^<]+)<\/a>/', $rptLinks, $matches);
                        for($j=0; $j<count($matches[0]); $j++) {
                            ?>
                            <a href="<?= $matches[1][$j] ?>" class="legacy-menu-tile">
                                <div class="legacy-menu-tile-icon">
                                    <?= $icons['Reports'] ?>
                                </div>
                                <span class="legacy-menu-tile-text"><?= $matches[2][$j] ?></span>
                            </a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
