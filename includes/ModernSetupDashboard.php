<?php
/* Modern Setup Dashboard - High-fidelity overview for System Settings & Administration */

// 1. Data Fetching
$today = date('Y-m-d');

// 1.1 Total Active Users
$sqlUsers = "SELECT COUNT(*) as user_count FROM www_users";
$resUsers = DB_query($sqlUsers);
$rowUsers = DB_fetch_array($resUsers);
$totalUsers = (int)($rowUsers['user_count'] ?? 0);

// 1.2 Total Roles
$sqlRoles = "SELECT COUNT(*) as role_count FROM securityroles";
$resRoles = DB_query($sqlRoles);
$rowRoles = DB_fetch_array($resRoles);
$totalRoles = (int)($rowRoles['role_count'] ?? 0);

// 1.3 System Locations
$sqlLocs = "SELECT COUNT(*) as loc_count FROM locations";
$resLocs = DB_query($sqlLocs);
$rowLocs = DB_fetch_array($resLocs);
$totalLocs = (int)($rowLocs['loc_count'] ?? 0);

// 1.4 Currencies
$sqlCurr = "SELECT COUNT(*) as curr_count FROM currencies";
$resCurr = DB_query($sqlCurr);
$rowCurr = DB_fetch_array($resCurr);
$totalCurr = (int)($rowCurr['curr_count'] ?? 0);

// 1.5 Tax Groups
$sqlTax = "SELECT COUNT(*) as tax_count FROM taxgroups";
$resTax = DB_query($sqlTax);
$rowTax = DB_fetch_array($resTax);
$totalTax = (int)($rowTax['tax_count'] ?? 0);

// 1.6 Recent Active Users
$recentUsers = [];
$sqlRecent = "SELECT userid, realname, lastvisitdate, email FROM www_users ORDER BY lastvisitdate DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentUsers[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('System Setup') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Configuration & User Management') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/WWW_Users.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <?= __('New User') ?>
            </a>
            <a href="<?= $RootPath ?>/CompanyPreferences.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <?= __('Settings') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('System Users') ?></span>
                <span class="db-kpi-value"><?= $totalUsers ?></span>
                <span class="db-kpi-trend db-trend-up">↑ <?= $totalRoles ?> <?= __('Roles') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Locations') ?></span>
                <span class="db-kpi-value"><?= $totalLocs ?></span>
                <span class="db-kpi-trend db-trend-up">↑ <?= __('Registered') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-warn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Currencies') ?></span>
                <span class="db-kpi-value"><?= $totalCurr ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= $totalTax ?> <?= __('Tax Groups') ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Access & Recent Activity -->
    <div class="db-row-2col">
        <!-- Quick Setup Links -->
        <div class="db-card">
            <h3 class="db-card-title"><?= __('Quick Setup Shortcuts') ?></h3>
            <div class="setup-quick-links">
                <a href="<?= $RootPath ?>/CompanyPreferences.php" class="setup-link-item">
                    <div class="setup-link-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div>
                    <div class="setup-link-text">
                        <span class="setup-link-title"><?= __('Company Prefs') ?></span>
                        <span class="setup-link-desc"><?= __('General system settings') ?></span>
                    </div>
                </a>
                <a href="<?= $RootPath ?>/SystemParameters.php" class="setup-link-item">
                    <div class="setup-link-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div>
                    <div class="setup-link-text">
                        <span class="setup-link-title"><?= __('Configuration') ?></span>
                        <span class="setup-link-desc"><?= __('Security & behavior') ?></span>
                    </div>
                </a>
                <a href="<?= $RootPath ?>/Currencies.php" class="setup-link-item">
                    <div class="setup-link-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
                    <div class="setup-link-text">
                        <span class="setup-link-title"><?= __('Currencies') ?></span>
                        <span class="setup-link-desc"><?= __('Exchange rates') ?></span>
                    </div>
                </a>
                <a href="<?= $RootPath ?>/TaxGroups.php" class="setup-link-item">
                    <div class="setup-link-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
                    <div class="setup-link-text">
                        <span class="setup-link-title"><?= __('Tax Setup') ?></span>
                        <span class="setup-link-desc"><?= __('Rates & categories') ?></span>
                    </div>
                </a>
            </div>
        </div>

    <!-- Recent User Activity -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent Login Activity') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('User ID') ?></th>
                        <th><?= __('Name') ?></th>
                        <th><?= __('Last Visit') ?></th>
                        <th style="text-align: right;"><?= __('Status') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentUsers)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No user activity found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);"><?= $user['userid'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($user['realname']) ?></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y H:i', strtotime($user['lastvisitdate'])) ?></td>
                            <td style="text-align: right;"><span class="db-badge db-badge-success"><?= __('Active') ?></span></td>
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
                <?= __('Extended Setup Menu') ?>
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
                $sectionId = 'Sec_Setup_' . $Type;
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
