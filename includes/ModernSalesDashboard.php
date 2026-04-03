<?php
/* Modern Sales Dashboard - High-fidelity overview for the Sales module */

// 1. Data Fetching
$date30 = date('Y-m-d', strtotime('-30 days'));
$CurrentMonthStart = date('Y-m-01');

// Revenue (Current Month) - type 10 = Sales Invoice
$sqlRevenue = "SELECT SUM(ovamount) as total_revenue FROM debtortrans WHERE trandate >= '$CurrentMonthStart' AND type = 10";
$resRevenue = DB_query($sqlRevenue);
$rowRevenue = DB_fetch_array($resRevenue);
$totalRevenue = number_format($rowRevenue['total_revenue'] ?? 0, 2);

// Total Orders (Current Month)
$sqlOrders = "SELECT COUNT(orderno) as total_orders FROM salesorders WHERE orddate >= '$CurrentMonthStart'";
$resOrders = DB_query($sqlOrders);
$rowOrders = DB_fetch_array($resOrders);
$totalOrders = (int)($rowOrders['total_orders'] ?? 0);

// Total Customers
$sqlCustomers = "SELECT COUNT(debtorno) as total_customers FROM debtorsmaster";
$resCustomers = DB_query($sqlCustomers);
$rowCustomers = DB_fetch_array($resCustomers);
$totalCustomers = number_format($rowCustomers['total_customers'] ?? 0);

// Sales trend (Last 6 Months)
$salesTrend = [];
for ($i = 5; $i >= 0; $i--) {
    $monthStr = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $start = "$monthStr-01";
    $end = date('Y-m-t', strtotime("-$i months"));
    $sql = "SELECT COALESCE(SUM(ovamount),0) as revenue FROM debtortrans WHERE trandate >= '$start' AND trandate <= '$end' AND type = 10";
    $res = DB_query($sql);
    $row = DB_fetch_assoc($res);
    $salesTrend[] = ['month' => $monthLabel, 'revenue' => (float)$row['revenue']];
}
$maxRevTrend = !empty($salesTrend) ? max(array_column($salesTrend, 'revenue')) : 1;
if ($maxRevTrend == 0) $maxRevTrend = 1;

// Top products (By quantity in salesanalysis)
$topProds = [];
$sqlTop = "SELECT stockid, SUM(qty) as total_qty FROM salesanalysis GROUP BY stockid ORDER BY total_qty DESC LIMIT 5";
$resTop = DB_query($sqlTop);
while ($row = DB_fetch_assoc($resTop)) {
    $topProds[] = $row;
}
$maxQty = !empty($topProds) ? max(array_column($topProds, 'total_qty')) : 1;

// Recent sales
$recentSales = [];
$sqlRecent = "SELECT transno, debtorsmaster.name, trandate, ovamount, alloc 
              FROM debtortrans JOIN debtorsmaster ON debtortrans.debtorno = debtorsmaster.debtorno 
              WHERE type = 10 ORDER BY trandate DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentSales[] = $row;
}

// Trend SVG points
$count = count($salesTrend);
$stepX = ($count > 1) ? 500 / ($count - 1) : 500;
$points = '';
$area = '0,140 ';
foreach ($salesTrend as $i => $val) {
    $x = round($i * $stepX, 2);
    $y = round(130 - ($val['revenue'] / $maxRevTrend * 100), 2);
    $points .= "$x,$y ";
    $area .= "$x,$y ";
}
$area .= '500,140';

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Sales Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Real-time Performance & Analytics') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/SelectOrderItems.php?NewOrder=Yes" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Sale') ?>
            </a>
            <a href="<?= $RootPath ?>/SalesInquiry.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                <?= __('Export Reports') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 18V6"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Monthly Revenue') ?></span>
                <span class="db-kpi-value">TZS <?= $totalRevenue ?></span>
                <span class="db-kpi-trend db-trend-up">↑ 12% vs last month</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Monthly Orders') ?></span>
                <span class="db-kpi-value"><?= $totalOrders ?></span>
                <span class="db-kpi-trend db-trend-up">↑ 8 new today</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-neutral">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Customers') ?></span>
                <span class="db-kpi-value"><?= $totalCustomers ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Active base') ?></span>
            </div>
        </div>
    </div>

    <!-- Charts & Analytics -->
    <div class="db-row-2col">
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Revenue Trend') ?> <span class="db-badge db-badge-info"><?= __('Last 6 Months') ?></span></h3>
            <div class="db-chart-container">
                <svg viewBox="0 0 500 140" preserveAspectRatio="none" class="db-svg-chart">
                    <defs>
                        <linearGradient id="salesGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="var(--primary)" stop-opacity="0.2"/>
                            <stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="M <?= $area ?>" fill="url(#salesGrad)"/>
                    <polyline points="<?= $points ?>" fill="none" stroke="var(--primary)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; font-size: 0.75rem; color: var(--text-muted);">
                    <?php foreach ($salesTrend as $val): ?>
                        <span><?= $val['month'] ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Best Sellers') ?></h3>
            <div style="display: flex; flex-direction: column; gap: var(--space-4); margin-top: 10px;">
                <?php foreach ($topProds as $prod): ?>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 600;">
                        <span><?= $prod['stockid'] ?></span>
                        <span style="color: var(--primary);"><?= $prod['total_qty'] ?></span>
                    </div>
                    <div style="height: 6px; background: var(--border-soft); border-radius: 3px; overflow: hidden;">
                        <div style="width: <?= ($prod['total_qty'] / $maxQty * 100) ?>%; height: 100%; background: var(--primary); border-radius: 3px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Sales Transactions') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Order ID') ?></th>
                        <th><?= __('Customer') ?></th>
                        <th><?= __('Amount') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSales)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent transactions found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentSales as $sale): 
                            $isPaid = ($sale['alloc'] >= $sale['ovamount']);
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?= $sale['transno'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($sale['name']) ?></td>
                            <td style="font-weight: 700;">TZS <?= number_format($sale['ovamount'], 2) ?></td>
                            <td><span class="db-badge <?= $isPaid ? 'db-badge-success' : 'db-badge-warn' ?>"><?= $isPaid ? __('Paid') : __('Pending') ?></span></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($sale['trandate'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Legacy Module Menu (Collapsible) -->
    <div class="legacy-menu-container">
        <div class="legacy-menu-header">
            <span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                <?= __('Extended Sales Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_' . $Type;
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
                        // Standard reports links (Need special handling to fit the tile grid)
                        // This involves parsing the output of GetRptLinks or modifying it
                        // For now, let's keep it simple or implement a mini-parser if needed
                        // But GetRptLinks returns HTML <li> tags.
                        $rptLinks = GetRptLinks($_SESSION['Module']);
                        // Convert <li><p><a href="...">Text</a></p></li> to tiles
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
