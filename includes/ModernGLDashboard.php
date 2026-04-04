<?php
/* Modern GL Dashboard - High-fidelity overview for General Ledger */

// 1. Data Fetching
$CurrentMonthStart = date('Y-m-01');
$date30 = date('Y-m-d', strtotime('-30 days'));

// 1.1 Monthly Income (Accounts with pandl=1 and budget > 0? No, usually by group)
// In webERP, income accounts usually have a certain account group or section.
// Let's use a simpler query: sum of gltrans for income-type accounts this month.
$sqlIncome = "SELECT SUM(-amount) as total_income 
              FROM gltrans 
              JOIN chartmaster ON gltrans.account = chartmaster.accountcode
              JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname
              WHERE gltrans.trandate >= '$CurrentMonthStart' 
              AND accountgroups.pandl = 1 
              AND gltrans.amount < 0"; // Credits are income
$resIncome = DB_query($sqlIncome);
$rowIncome = DB_fetch_array($resIncome);
$totalIncome = number_format($rowIncome['total_income'] ?? 0, 2);

// 1.2 Monthly Expenses
$sqlExpenses = "SELECT SUM(amount) as total_expenses 
                FROM gltrans 
                JOIN chartmaster ON gltrans.account = chartmaster.accountcode
                JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname
                WHERE gltrans.trandate >= '$CurrentMonthStart' 
                AND accountgroups.pandl = 1 
                AND gltrans.amount > 0"; // Debits are expenses
$resExpenses = DB_query($sqlExpenses);
$rowExpenses = DB_fetch_array($resExpenses);
$totalExpenses = number_format($rowExpenses['total_expenses'] ?? 0, 2);

// 1.3 Cash & Bank Balance
$sqlCash = "SELECT SUM(amount) as balance 
            FROM gltrans 
            JOIN bankaccounts ON gltrans.account = bankaccounts.accountcode";
$resCash = DB_query($sqlCash);
$rowCash = DB_fetch_array($resCash);
$cashBalance = number_format($rowCash['balance'] ?? 0, 2);

// 1.4 Recent GL Transactions
$recentGL = [];
$sqlRecent = "SELECT counterindex, trandate, account, chartmaster.accountname, amount, narrative 
              FROM gltrans 
              JOIN chartmaster ON gltrans.account = chartmaster.accountcode
              ORDER BY counterindex DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentGL[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('General Ledger Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Financial Health & Reporting') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/GLJournal.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Journal') ?>
            </a>
            <a href="<?= $RootPath ?>/GLTrialBalance.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                <?= __('Trial Balance') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Monthly Income') ?></span>
                <span class="db-kpi-value">TZS <?= $totalIncome ?></span>
                <span class="db-kpi-trend db-trend-up">↑ Current Month</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-red">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Monthly Expenses') ?></span>
                <span class="db-kpi-value">TZS <?= $totalExpenses ?></span>
                <span class="db-kpi-trend db-trend-warn">↓ Tracked Costs</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Cash & Bank') ?></span>
                <span class="db-kpi-value">TZS <?= $cashBalance ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Total Liquidity') ?></span>
            </div>
        </div>
    </div>

    <!-- Recent GL Transactions -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Ledger Postings') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Date') ?></th>
                        <th><?= __('Account') ?></th>
                        <th><?= __('Narrative') ?></th>
                        <th style="text-align: right;"><?= __('Amount') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentGL)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent GL transactions found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentGL as $gl): ?>
                        <tr>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($gl['trandate'])) ?></td>
                            <td>
                                <div style="font-weight: 600;"><?= $gl['account'] ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($gl['accountname']) ?></div>
                            </td>
                            <td style="font-size: 0.85rem; max-width: 300px;"><?= htmlspecialchars($gl['narrative']) ?></td>
                            <td style="font-weight: 700; text-align: right; color: <?= $gl['amount'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;">
                                <?= number_format(abs($gl['amount']), 2) ?> <?= $gl['amount'] > 0 ? '(Dr)' : '(Cr)' ?>
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
                <?= __('Extended General Ledger Menu') ?>
            </span>
            <span class="db-badge db-badge-info" style="font-size: 0.65rem;"><?= __('Click category to expand') ?></span>
        </div>
        <div class="legacy-menu-sections">
            <?php 
            $icons = [
                'Transactions' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
                'Reports'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
                'Maintenance'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.72V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.1a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>'
            ];

            foreach (array('Transactions', 'Reports', 'Maintenance') as $Type): 
                $sectionId = 'Sec_GL_' . $Type;
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
