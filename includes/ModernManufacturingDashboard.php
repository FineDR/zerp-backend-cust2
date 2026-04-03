<?php
/* Modern Manufacturing Dashboard - High-fidelity overview for Production & Work Orders */

// 1. Data Fetching
$today = date('Y-m-d');
$CurrentMonthStart = date('Y-m-01');

// Total Work Orders
$sqlTotal = "SELECT COUNT(*) as total_orders FROM workorders";
$resTotal = DB_query($sqlTotal);
$rowTotal = DB_fetch_array($resTotal);
$totalOrders = $rowTotal['total_orders'] ?? 0;

// WIP (Work In Progress) - Open and Released
$sqlWIP = "SELECT COUNT(*) as wip_count FROM workorders WHERE closed = 0";
$resWIP = DB_query($sqlWIP);
$rowWIP = DB_fetch_array($resWIP);
$wipCount = $rowWIP['wip_count'] ?? 0;

// Completed MTD (Using requiredby as proxy if closeddate is missing, or just counting closed)
$sqlCompleted = "SELECT COUNT(*) as completed_count FROM workorders WHERE closed = 1 AND requiredby >= '$CurrentMonthStart'";
$resCompleted = DB_query($sqlCompleted);
$rowCompleted = DB_fetch_array($resCompleted);
$completedCount = $rowCompleted['completed_count'] ?? 0;

// Delayed Orders (Past requiredby date and still open)
$sqlDelayed = "SELECT COUNT(*) as delayed_count FROM workorders WHERE closed = 0 AND requiredby < '$today'";
$resDelayed = DB_query($sqlDelayed);
$rowDelayed = DB_fetch_array($resDelayed);
$delayedCount = $rowDelayed['delayed_count'] ?? 0;

// Production Trend (Last 6 Months)
$trendData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $sqlTrend = "SELECT COUNT(*) as monthly_total FROM workorders WHERE closed = 1 AND requiredby LIKE '$month%'";
    $resTrend = DB_query($sqlTrend);
    $rowTrend = DB_fetch_array($resTrend);
    $trendData[$monthLabel] = (int)($rowTrend['monthly_total'] ?? 0);
}
$maxTrend = max(array_values($trendData)) ?: 1;

// Work Centre Utilization (Simplified - based on open orders)
$wcData = [];
$sqlWC = "SELECT locations.locationname, COUNT(workorders.wo) as order_count 
          FROM workorders JOIN locations ON workorders.loccode = locations.loccode 
          WHERE workorders.closed = 0 
          GROUP BY locations.locationname ORDER BY order_count DESC LIMIT 5";
$resWC = DB_query($sqlWC);
while ($row = DB_fetch_assoc($resWC)) {
    $wcData[] = $row;
}

// Recent Work Orders
$recentWOs = [];
$sqlWOs = "SELECT wo, stockmaster.description, qtyreqd, qtyrecd, requiredby, closed 
           FROM workorders JOIN stockmaster ON workorders.stockid = stockmaster.stockid 
           ORDER BY wo DESC LIMIT 6";
$resWOs = DB_query($sqlWOs);
while ($row = DB_fetch_assoc($resWOs)) {
    $recentWOs[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Production Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Manufacturing & Work Orders') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/WorkOrderEntry.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Work Order') ?>
            </a>
            <a href="<?= $RootPath ?>/TimesheetEntry.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <?= __('Enter Timesheet') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Orders') ?></span>
                <span class="db-kpi-value"><?= $totalOrders ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('WIP (Active)') ?></span>
                <span class="db-kpi-value" style="color: var(--info);"><?= $wipCount ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Completed (MTD)') ?></span>
                <span class="db-kpi-value" style="color: var(--success);"><?= $completedCount ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-red">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Delayed Orders') ?></span>
                <span class="db-kpi-value" style="color: var(--danger);"><?= $delayedCount ?></span>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="db-row-2col">
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Production Trend') ?> <span class="db-badge db-badge-info"><?= __('Monthly Completed') ?></span></h3>
            <div class="db-chart-container">
                <svg class="db-svg-chart" viewBox="0 0 600 200">
                    <defs>
                        <linearGradient id="prodGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:var(--primary);stop-opacity:0.2" />
                            <stop offset="100%" style="stop-color:var(--primary);stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <?php 
                    $x = 40; $step = 100; $points = ""; $areaPoints = "40,200 ";
                    foreach ($trendData as $label => $val) {
                        $h = ($val / $maxTrend) * 150;
                        $y = 180 - $h;
                        $points .= "$x,$y ";
                        $areaPoints .= "$x,$y ";
                        echo "<circle cx='$x' cy='$y' r='4' fill='var(--primary)' />";
                        echo "<text x='$x' y='195' font-size='10' text-anchor='middle' fill='var(--text-muted)'>$label</text>";
                        $x += $step;
                    }
                    $areaPoints .= ($x - $step) . ",200";
                    ?>
                    <polyline points="<?= $points ?>" fill="none" stroke="var(--primary)" stroke-width="3" />
                    <polygon points="<?= $areaPoints ?>" fill="url(#prodGradient)" />
                </svg>
            </div>
        </div>
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Resource Allocation') ?></h3>
            <div style="display: flex; flex-direction: column; gap: var(--space-4); margin-top: var(--space-2);">
                <?php if (empty($wcData)): ?>
                    <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem;"><?= __('No active resource data') ?></p>
                <?php else: ?>
                    <?php foreach ($wcData as $wc): 
                        $pct = ($wc['order_count'] / ($wipCount ?: 1)) * 100;
                    ?>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700;">
                            <span><?= htmlspecialchars($wc['locationname']) ?></span>
                            <span style="color: var(--primary);"><?= $wc['order_count'] ?> <?= __('Orders') ?></span>
                        </div>
                        <div style="height: 6px; background: var(--border-soft); border-radius: 3px; overflow: hidden;">
                            <div style="height: 100%; background: var(--primary); width: <?= $pct ?>%; border-radius: 3px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Production Orders') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Order #') ?></th>
                        <th><?= __('Product') ?></th>
                        <th><?= __('Quantity') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Due Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentWOs)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent work orders found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentWOs as $wo): 
                            $isDelayed = (strtotime($wo['requiredby']) < strtotime($today) && $wo['closed'] == 0);
                            $statusLabel = $wo['closed'] == 1 ? 'Completed' : ($isDelayed ? 'Delayed' : 'In Progress');
                            $statusClass = $wo['closed'] == 1 ? 'db-badge-completed' : ($isDelayed ? 'db-badge-delayed' : 'db-badge-in-progress');
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">WO <?= $wo['wo'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($wo['description']) ?></td>
                            <td style="font-weight: 700;">
                                <?= number_format($wo['qtyrecd'], 0) ?> / <?= number_format($wo['qtyreqd'], 0) ?>
                                <div style="height: 4px; background: var(--border-soft); border-radius: 2px; width: 60px; margin-top: 4px;">
                                    <div style="height: 100%; background: var(--primary); width: <?= ($wo['qtyrecd'] / ($wo['qtyreqd'] ?: 1)) * 100 ?>%; border-radius: 2px;"></div>
                                </div>
                            </td>
                            <td><span class="db-badge <?= $statusClass ?>"><?= __($statusLabel) ?></span></td>
                            <td style="font-weight: 700; color: <?= $isDelayed ? 'var(--danger)' : 'var(--text-main)' ?>;">
                                <?= date('d M Y', strtotime($wo['requiredby'])) ?>
                            </td>
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
                <?= __('Extended Manufacturing Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_Manuf_' . $Type;
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
