<?php
/* Modern Petty Cash Dashboard - High-fidelity overview for Petty Cash Management */

// 1. Data Fetching
$today = date('Y-m-d');

// 1.1 Total Cash in All Tabs (Current Balance)
$sqlTotalCash = "SELECT SUM(tablimit) as total_cash FROM pctabs"; // Using tablimit as a proxy for float if total balance isn't a column
$resTotalCash = DB_query($sqlTotalCash);
$rowTotalCash = DB_fetch_array($resTotalCash);
$totalCash = number_format($rowTotalCash['total_cash'] ?? 0, 2);

// 1.2 Pending Authorizations (Expenses not yet authorized)
$sqlPending = "SELECT COUNT(*) as pending_count FROM pcashdetails WHERE authorized = '0000-00-00'";
$resPending = DB_query($sqlPending);
$rowPending = DB_fetch_array($resPending);
$pendingAuth = (int)($rowPending['pending_count'] ?? 0);

// 1.3 Total Expenses (Current Month)
$CurrentMonthStart = date('Y-m-01');
$sqlMonthExp = "SELECT SUM(amount) as month_exp FROM pcashdetails WHERE date >= '$CurrentMonthStart'";
$resMonthExp = DB_query($sqlMonthExp);
$rowMonthExp = DB_fetch_array($resMonthExp);
$monthExpenses = number_format($rowMonthExp['month_exp'] ?? 0, 2);

// 1.4 Recent Expenses
$recentExp = [];
$sqlRecent = "SELECT counterindex, date, amount, notes, tabcode 
              FROM pcashdetails 
              ORDER BY counterindex DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentExp[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Petty Cash Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Cash Management & Requisitions') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/PcClaimExpensesFromTab.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Claim') ?>
            </a>
            <a href="<?= $RootPath ?>/PcAuthorizeExpenses.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <?= __('Authorize') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Tab Cash') ?></span>
                <span class="db-kpi-value">TZS <?= $totalCash ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Current Float') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-warn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Pending Auth') ?></span>
                <span class="db-kpi-value"><?= $pendingAuth ?></span>
                <span class="db-kpi-trend <?= $pendingAuth > 0 ? 'db-trend-warn' : 'db-trend-up' ?>"><?= $pendingAuth > 0 ? __('Requires Action') : __('All Clear') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-red">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Monthly Spent') ?></span>
                <span class="db-kpi-value">TZS <?= $monthExpenses ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Current Month') ?></span>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Petty Cash Claims') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Date') ?></th>
                        <th><?= __('Tab') ?></th>
                        <th><?= __('Notes') ?></th>
                        <th style="text-align: right;"><?= __('Amount') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentExp)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent expenses found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentExp as $exp): ?>
                        <tr>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($exp['date'])) ?></td>
                            <td><span class="db-badge db-badge-info"><?= $exp['tabcode'] ?></span></td>
                            <td style="font-size: 0.85rem; max-width: 300px;"><?= htmlspecialchars($exp['notes']) ?></td>
                            <td style="font-weight: 700; text-align: right; color: var(--danger);">
                                TZS <?= number_format($exp['amount'], 2) ?>
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
                <?= __('Extended Petty Cash Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_PC_' . $Type;
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
