<?php
/* Modern Manufacturing Dashboard - High-fidelity overview for Production & Work Orders */

// 1. Data Fetching
$today = date('Y-m-d');

// 1.1 Total Open Work Orders
$sqlOpenWO = "SELECT COUNT(*) as open_wo FROM workorders WHERE closed = 0";
$resOpenWO = DB_query($sqlOpenWO);
$rowOpenWO = DB_fetch_array($resOpenWO);
$openWO = (int)($rowOpenWO['open_wo'] ?? 0);

// 1.2 Recent Closed Work Orders (Past 30 days)
$date30 = date('Y-m-d', strtotime('-30 days'));
$sqlClosedWO = "SELECT COUNT(*) as closed_wo FROM workorders WHERE closed = 1 AND startdate >= '$date30'";
$resClosedWO = DB_query($sqlClosedWO);
$rowClosedWO = DB_fetch_array($resClosedWO);
$closedWO = (int)($rowClosedWO['closed_wo'] ?? 0);

// 1.3 Total Items in Production (Quantity Outstanding)
$sqlItemsProd = "SELECT SUM(qtyreqd - qtyrecd) as total_prod 
                 FROM woitems 
                 JOIN workorders ON woitems.wo = workorders.wo 
                 WHERE workorders.closed = 0";
$resItemsProd = DB_query($sqlItemsProd);
$rowItemsProd = DB_fetch_array($resItemsProd);
$itemsInProd = number_format($rowItemsProd['total_prod'] ?? 0);

// 1.4 Recent Work Orders
$recentWO = [];
$sqlRecent = "SELECT workorders.wo, stockmaster.description, workorders.startdate, workorders.requiredby, woitems.qtyreqd 
              FROM workorders 
              JOIN woitems ON workorders.wo = woitems.wo 
              JOIN stockmaster ON woitems.stockid = stockmaster.stockid 
              ORDER BY workorders.wo DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentWO[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Manufacturing Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Production Planning & Control') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/WorkOrderEntry.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Work Order') ?>
            </a>
            <a href="<?= $RootPath ?>/SelectWorkOrder.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                <?= __('View All WO') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-warn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Open Work Orders') ?></span>
                <span class="db-kpi-value"><?= $openWO ?></span>
                <span class="db-kpi-trend <?= $openWO > 5 ? 'db-trend-warn' : 'db-trend-up' ?>"><?= $openWO > 5 ? __('High Load') : __('Manageable') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Recently Closed') ?></span>
                <span class="db-kpi-value"><?= $closedWO ?></span>
                <span class="db-kpi-trend db-trend-up">↑ Past 30 Days</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Items in Production') ?></span>
                <span class="db-kpi-value"><?= $itemsInProd ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Outstanding Qty') ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Work Orders -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Latest Work Orders') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('WO #') ?></th>
                        <th><?= __('Product') ?></th>
                        <th><?= __('Qty Reqd') ?></th>
                        <th><?= __('Start Date') ?></th>
                        <th><?= __('Due Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentWO)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent work orders found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentWO as $wo): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?= $wo['wo'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($wo['description']) ?></td>
                            <td style="font-weight: 700;"><?= number_format($wo['qtyreqd'], 0) ?></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($wo['startdate'])) ?></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($wo['requiredby'])) ?></td>
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
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
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
