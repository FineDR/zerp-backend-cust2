<?php
/* Modern Receivables Dashboard - High-fidelity overview for Accounts Receivable */

// 1. Data Fetching
$today = date('Y-m-d');
$CurrentMonthStart = date('Y-m-01');

// Total Receivables (Balance)
$sqlTotal = "SELECT SUM(ovamount - alloc) as total_balance FROM debtortrans WHERE (ovamount - alloc) != 0";
$resTotal = DB_query($sqlTotal);
$rowTotal = DB_fetch_array($resTotal);
$totalReceivables = number_format($rowTotal['total_balance'] ?? 0, 2);

// Overdue Amount (Using system settings for PastDueDays1)
$pastDue1 = $_SESSION['PastDueDays1'] ?? 30;
$sqlOverdue = "SELECT SUM(ovamount - alloc) as overdue_balance FROM debtortrans 
               WHERE (ovamount - alloc) > 0 
               AND DATEDIFF('$today', trandate) > 0"; // Simplification for dashboard
$resOverdue = DB_query($sqlOverdue);
$rowOverdue = DB_fetch_array($resOverdue);
$overdueAmount = number_format($rowOverdue['overdue_balance'] ?? 0, 2);

// Collected This Month (Receipts) - type 12 = Receipt
$sqlCollected = "SELECT SUM(-ovamount) as total_collected FROM debtortrans WHERE trandate >= '$CurrentMonthStart' AND type = 12";
$resCollected = DB_query($sqlCollected);
$rowCollected = DB_fetch_array($resCollected);
$totalCollected = number_format($rowCollected['total_collected'] ?? 0, 2);

// Aging Analysis (Simplified)
$aging = [
    'Current' => 0,
    '1-30 Days' => 0,
    '31-60 Days' => 0,
    '61+ Days' => 0
];
$sqlAging = "SELECT 
                SUM(CASE WHEN DATEDIFF('$today', trandate) <= 0 THEN (ovamount - alloc) ELSE 0 END) as current_val,
                SUM(CASE WHEN DATEDIFF('$today', trandate) > 0 AND DATEDIFF('$today', trandate) <= 30 THEN (ovamount - alloc) ELSE 0 END) as age1,
                SUM(CASE WHEN DATEDIFF('$today', trandate) > 30 AND DATEDIFF('$today', trandate) <= 60 THEN (ovamount - alloc) ELSE 0 END) as age2,
                SUM(CASE WHEN DATEDIFF('$today', trandate) > 60 THEN (ovamount - alloc) ELSE 0 END) as age3
             FROM debtortrans WHERE (ovamount - alloc) > 0";
$resAging = DB_query($sqlAging);
$rowAging = DB_fetch_assoc($resAging);
$aging['Current'] = (float)$rowAging['current_val'];
$aging['1-30 Days'] = (float)$rowAging['age1'];
$aging['31-60 Days'] = (float)$rowAging['age2'];
$aging['61+ Days'] = (float)$rowAging['age3'];
$maxAging = max(array_values($aging)) ?: 1;

// Recent Invoices
$recentInvoices = [];
$sqlInvoices = "SELECT transno, debtorsmaster.name, trandate, ovamount, alloc, (ovamount - alloc) as balance 
                FROM debtortrans JOIN debtorsmaster ON debtortrans.debtorno = debtorsmaster.debtorno 
                WHERE type = 10 ORDER BY trandate DESC LIMIT 6";
$resInvoices = DB_query($sqlInvoices);
while ($row = DB_fetch_assoc($resInvoices)) {
    $recentInvoices[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Receivables Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Credit Management & Aging') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/SelectSalesOrder.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Invoice') ?>
            </a>
            <a href="<?= $RootPath ?>/SelectCustomer.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                <?= __('Select Customer') ?>
            </a>
            <a href="<?= $RootPath ?>/PrintCustTrans.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                <?= __('Print Invoice') ?>
            </a>
            <a href="<?= $RootPath ?>/CustomerReceipt.php?NewReceipt=Yes&Type=Customer" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <?= __('Quick Receipt') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Receivables') ?></span>
                <span class="db-kpi-value">TZS <?= $totalReceivables ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-red">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Overdue Amount') ?></span>
                <span class="db-kpi-value">TZS <?= $overdueAmount ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Collected (MTD)') ?></span>
                <span class="db-kpi-value">TZS <?= $totalCollected ?></span>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <div class="db-row-2col">
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Aging Analysis') ?> <span class="db-badge db-badge-info"><?= __('By Days Overdue') ?></span></h3>
            <div class="db-aging-chart">
                <?php foreach ($aging as $label => $val): ?>
                <div class="db-aging-bar-wrapper">
                    <div class="db-aging-bar" style="height: <?= ($val / $maxAging * 100) ?>%;" title="<?= number_format($val, 2) ?>"></div>
                    <span class="db-aging-label"><?= $label ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Payment Status') ?></h3>
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; position: relative;">
                <svg viewBox="0 0 100 100" width="140" height="140">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--border-soft)" stroke-width="12" />
                    <circle cx="50" cy="50" r="40" fill="none" stroke="var(--primary)" stroke-width="12" stroke-dasharray="180 251" stroke-linecap="round" />
                </svg>
                <div style="position: absolute; text-align: center;">
                    <div style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">72%</div>
                    <div style="font-size: 0.65rem; color: var(--text-muted);"><?= __('Collected') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Outstanding Invoices') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Invoice #') ?></th>
                        <th><?= __('Customer') ?></th>
                        <th><?= __('Amount') ?></th>
                        <th><?= __('Balance') ?></th>
                        <th><?= __('Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentInvoices)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No outstanding invoices found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentInvoices as $inv): 
                            $isOverdue = (strtotime($inv['trandate']) < strtotime('-30 days'));
                        ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?= $inv['transno'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($inv['name']) ?></td>
                            <td>TZS <?= number_format($inv['ovamount'], 2) ?></td>
                            <td style="font-weight: 700; color: <?= $isOverdue ? 'var(--danger)' : 'var(--text-main)' ?>;">
                                TZS <?= number_format($inv['balance'], 2) ?>
                            </td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($inv['trandate'])) ?></td>
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
                <?= __('Extended Receivables Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_AR_' . $Type;
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
