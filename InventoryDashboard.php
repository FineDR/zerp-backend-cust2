<?php
require_once(__DIR__ . '/includes/session.php');

$Title = __('Inventory Dashboard');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryDashboard';
include(__DIR__ . '/includes/header.php');

// 1. Fetch Key Metrics
// Total Value
$sql_val = "SELECT SUM(locstock.quantity * stockmaster.materialcost) as total_value
            FROM locstock 
            INNER JOIN stockmaster ON locstock.stockid = stockmaster.stockid
            WHERE stockmaster.discontinued = 0";
$res_val = DB_query($sql_val);
$row_val = DB_fetch_assoc($res_val);
$total_inventory_value = $row_val['total_value'] ?? 0;

// Total SKUs
$sql_skus = "SELECT COUNT(*) as sku_count FROM stockmaster WHERE discontinued = 0";
$res_skus = DB_query($sql_skus);
$row_skus = DB_fetch_assoc($res_skus);
$total_skus = $row_skus['sku_count'] ?? 0;

// Low Stock Items (QoH < ReorderLevel)
$sql_low = "SELECT COUNT(DISTINCT stockid) as low_count FROM locstock WHERE quantity < reorderlevel AND reorderlevel > 0";
$res_low = DB_query($sql_low);
$row_low = DB_fetch_assoc($res_low);
$low_stock_count = $row_low['low_count'] ?? 0;

// Out of Stock Items
$sql_oos = "SELECT COUNT(DISTINCT stockid) as oos_count FROM locstock WHERE quantity <= 0";
$res_oos = DB_query($sql_oos);
$row_oos = DB_fetch_assoc($res_oos);
$oos_count = $row_oos['oos_count'] ?? 0;

// 2. Fetch Recent Activities (Adjustments)
$sql_adj = "SELECT stockmoves.stockid, stockmaster.description, stockmoves.trandate, stockmoves.qty, stockmoves.userid
            FROM stockmoves 
            INNER JOIN stockmaster ON stockmoves.stockid = stockmaster.stockid
            WHERE stockmoves.type = 17 
            ORDER BY stockmoves.trandate DESC, stockmoves.stkmoveno DESC
            LIMIT 5";
$res_adj = DB_query($sql_adj);

// 3. Category Distribution (Top 5)
$sql_cat = "SELECT stockcategory.categorydescription, SUM(locstock.quantity * stockmaster.materialcost) as cat_value
            FROM locstock
            INNER JOIN stockmaster ON locstock.stockid = stockmaster.stockid
            INNER JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid
            GROUP BY stockcategory.categorydescription
            ORDER BY cat_value DESC
            LIMIT 5";
$res_cat = DB_query($sql_cat);

echo '<div class="db-dashboard">
        <div class="db-stats-grid">
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:rgba(34, 197, 94, 0.1); color:#22c55e;">
                    <i class="fas fa-wallet"></i>
                </div>
                <div class="db-stat-info">
                    <div class="db-stat-label">' . __('Total Inventory Value') . '</div>
                    <div class="db-stat-value">' . locale_number_format($total_inventory_value, 2) . '</div>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:rgba(59, 130, 246, 0.1); color:#3b82f6;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="db-stat-info">
                    <div class="db-stat-label">' . __('Active SKUs') . '</div>
                    <div class="db-stat-value">' . $total_skus . '</div>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:rgba(249, 115, 22, 0.1); color:#f97316;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="db-stat-info">
                    <div class="db-stat-label">' . __('Low Stock Alerts') . '</div>
                    <div class="db-stat-value">' . $low_stock_count . '</div>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon" style="background:rgba(239, 68, 68, 0.1); color:#ef4444;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="db-stat-info">
                    <div class="db-stat-label">' . __('Out of Stock') . '</div>
                    <div class="db-stat-value">' . $oos_count . '</div>
                </div>
            </div>
        </div>

        <div class="db-grid">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('Recent Adjustments') . '</div>
                    <a href="StockMovements.php" class="db-link">' . __('View All') . '</a>
                </div>
                <div class="db-card-content">
                    <table class="db-table">
                        <thead>
                            <tr>
                                <th>' . __('Item') . '</th>
                                <th>' . __('Date') . '</th>
                                <th class="text-right">' . __('Qty') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
while ($row = DB_fetch_assoc($res_adj)) {
    echo '<tr>
            <td>
                <div class="db-item-primary">' . $row['stockid'] . '</div>
                <div class="db-item-secondary">' . $row['description'] . '</div>
            </td>
            <td>' . date('d M Y', strtotime($row['trandate'])) . '</td>
            <td class="text-right ' . ($row['qty'] < 0 ? 'text-red' : 'text-green') . '">' . ($row['qty'] > 0 ? '+' : '') . $row['qty'] . '</td>
          </tr>';
}
echo '                  </tbody>
                    </table>
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('Top Categories by Value') . '</div>
                </div>
                <div class="db-card-content">
                    <div class="db-cat-list">';
while ($row = DB_fetch_assoc($res_cat)) {
    $percent = ($total_inventory_value > 0) ? ($row['cat_value'] / $total_inventory_value) * 100 : 0;
    echo '<div class="db-cat-item">
            <div class="db-cat-info">
                <span>' . $row['categorydescription'] . '</span>
                <span>' . locale_number_format($row['cat_value'], 2) . '</span>
            </div>
            <div class="db-progress-bg">
                <div class="db-progress-bar" style="width:' . $percent . '%;"></div>
            </div>
          </div>';
}
echo '              </div>
                </div>
            </div>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
?>
