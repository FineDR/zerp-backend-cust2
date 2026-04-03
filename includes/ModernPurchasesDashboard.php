<?php
/* Modern Purchases Dashboard - High-fidelity overview for Procurement */

// 1. Data Fetching
$today = date('Y-m-d');
$CurrentMonthStart = date('Y-m-01');
$YearStart = date('Y-01-01');

// Total Purchases (MTD) - type 20 = Supplier Invoice
$sqlTotalMTD = "SELECT SUM(ovamount) as total_mtd FROM supptrans WHERE trandate >= '$CurrentMonthStart' AND type = 20";
$resTotalMTD = DB_query($sqlTotalMTD);
$rowTotalMTD = DB_fetch_array($resTotalMTD);
$totalPurchasesMTD = number_format($rowTotalMTD['total_mtd'] ?? 0, 2);

// Pending Orders (Count)
$sqlPending = "SELECT COUNT(*) as pending_count FROM purchorders WHERE status NOT IN ('Completed', 'Cancelled', 'Rejected')";
$resPending = DB_query($sqlPending);
$rowPending = DB_fetch_array($resPending);
$pendingOrders = $rowPending['pending_count'] ?? 0;

// Received Items (MTD) - Using GRNs
$sqlReceived = "SELECT COUNT(*) as received_count FROM grns WHERE deliverydate >= '$CurrentMonthStart'";
$resReceived = DB_query($sqlReceived);
$rowReceived = DB_fetch_array($resReceived);
$receivedItemsMTD = $rowReceived['received_count'] ?? 0;

// Total Spend (YTD)
$sqlTotalYTD = "SELECT SUM(ovamount) as total_ytd FROM supptrans WHERE trandate >= '$YearStart' AND type = 20";
$resTotalYTD = DB_query($sqlTotalYTD);
$rowTotalYTD = DB_fetch_array($resTotalYTD);
$totalSpendYTD = number_format($rowTotalYTD['total_ytd'] ?? 0, 2);

// Purchase Trend (Last 6 Months)
$trendData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M', strtotime("-$i months"));
    $sqlTrend = "SELECT SUM(ovamount) as monthly_total FROM supptrans WHERE trandate LIKE '$month%' AND type = 20";
    $resTrend = DB_query($sqlTrend);
    $rowTrend = DB_fetch_array($resTrend);
    $trendData[$monthLabel] = (float)($rowTrend['monthly_total'] ?? 0);
}
$maxTrend = max(array_values($trendData)) ?: 1;

// Top Suppliers (By Spend YTD)
$topSuppliers = [];
$sqlSupp = "SELECT suppliers.suppname, SUM(supptrans.ovamount) as spend 
            FROM supptrans JOIN suppliers ON supptrans.supplierno = suppliers.supplierid 
            WHERE supptrans.trandate >= '$YearStart' AND supptrans.type = 20 
            GROUP BY suppliers.suppname ORDER BY spend DESC LIMIT 5";
$resSupp = DB_query($sqlSupp);
while ($row = DB_fetch_assoc($resSupp)) {
    $topSuppliers[] = $row;
}

// Recent Purchase Orders
$recentPOs = [];
$sqlPOs = "SELECT orderno, suppliers.suppname, orddate, status, deliverydate 
           FROM purchorders JOIN suppliers ON purchorders.supplierno = suppliers.supplierid 
           ORDER BY orddate DESC LIMIT 6";
$resPOs = DB_query($sqlPOs);
while ($row = DB_fetch_assoc($resPOs)) {
    $recentPOs[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Procurement Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Supply Chain & Expenditure') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/PO_Header.php?NewOrder=Yes" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Purchase') ?>
            </a>
            <a href="<?= $RootPath ?>/PO_Authorisation.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?= __('Approve Orders') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Purchases (MTD)') ?></span>
                <span class="db-kpi-value">TZS <?= $totalPurchasesMTD ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Pending Orders') ?></span>
                <span class="db-kpi-value"><?= $pendingOrders ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Received (MTD)') ?></span>
                <span class="db-kpi-value"><?= $receivedItemsMTD ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Spend (YTD)') ?></span>
                <span class="db-kpi-value">TZS <?= $totalSpendYTD ?></span>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="db-row-2col">
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Monthly Spend Trend') ?> <span class="db-badge db-badge-info"><?= __('Last 6 Months') ?></span></h3>
            <div class="db-chart-container">
                <svg class="db-svg-chart" viewBox="0 0 600 200">
                    <defs>
                        <linearGradient id="spendGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" style="stop-color:var(--primary);stop-opacity:0.2" />
                            <stop offset="100%" style="stop-color:var(--primary);stop-opacity:0" />
                        </linearGradient>
                    </defs>
                    <?php 
                    $x = 0; $step = 100; $points = ""; $areaPoints = "0,200 ";
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
                    <polygon points="<?= $areaPoints ?>" fill="url(#spendGradient)" />
                </svg>
            </div>
        </div>
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Top Suppliers') ?></h3>
            <div style="display: flex; flex-direction: column; gap: var(--space-4); margin-top: var(--space-2);">
                <?php foreach ($topSuppliers as $sup): 
                    $pct = ($sup['spend'] / (float)($rowTotalYTD['total_ytd'] ?: 1)) * 100;
                ?>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.8rem; font-weight: 700;">
                        <span><?= htmlspecialchars($sup['suppname']) ?></span>
                        <span style="color: var(--primary);">TZS <?= number_format($sup['spend'], 0) ?></span>
                    </div>
                    <div style="height: 6px; background: var(--border-soft); border-radius: 3px; overflow: hidden;">
                        <div style="height: 100%; background: var(--primary); width: <?= $pct ?>%; border-radius: 3px;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Purchase Orders') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Order #') ?></th>
                        <th><?= __('Supplier') ?></th>
                        <th><?= __('Date') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Delivery') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentPOs)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent purchase orders found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPOs as $po): 
                            $statusClass = strtolower($po['status']) == 'pending' ? 'db-badge-pending' : (strtolower($po['status']) == 'completed' ? 'db-badge-received' : 'db-badge-info');
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?= $po['orderno'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($po['suppname']) ?></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($po['orddate'])) ?></td>
                            <td><span class="db-badge <?= $statusClass ?>"><?= __($po['status']) ?></span></td>
                            <td style="font-weight: 700; color: var(--text-main);"><?= date('d M Y', strtotime($po['deliverydate'])) ?></td>
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
                <?= __('Extended Purchases Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"></path><path d="M2 7v13a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7"></path><path d="M10 12h4"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_PO_' . $Type;
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
