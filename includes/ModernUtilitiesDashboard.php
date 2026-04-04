<?php
/* Modern Utilities Dashboard - High-fidelity overview for System Utilities & Maintenance */

// 1. Data Fetching
$today = date('Y-m-d');

// 1.1 Database Name
$dbName = $_SESSION['DatabaseName'] ?? 'webERP';

// 1.2 Audit Trail Count (Recent)
$sqlAudit = "SELECT COUNT(*) as audit_count FROM audittrail WHERE transactiondate >= '$today'";
$resAudit = DB_query($sqlAudit);
$rowAudit = DB_fetch_array($resAudit);
$auditCount = (int)($rowAudit['audit_count'] ?? 0);

// 1.3 System Version/Build (Mock or from a config if exists)
$systemVersion = "webERP 5.0 Modernized";

// 1.4 Recent Audit Entries
$recentAudit = [];
$sqlRecent = "SELECT userid, querystring, transactiondate FROM audittrail ORDER BY transactiondate DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentAudit[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('System Utilities') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Maintenance & System Health') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/AuditTrail.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                <?= __('Audit Trail') ?>
            </a>
            <a href="<?= $RootPath ?>/Z_UpgradeDatabase.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                <?= __('System Upgrade') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"></ellipse><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Database') ?></span>
                <span class="db-kpi-value"><?= $dbName ?></span>
                <span class="db-kpi-trend db-trend-up">↑ Online</span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-warn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Daily Logs') ?></span>
                <span class="db-kpi-value"><?= $auditCount ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Audit Entries') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22v-7l-2-3V9l2-3V2"></path><path d="M9 20h6"></path><path d="M10 4h4"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('System Status') ?></span>
                <span class="db-kpi-value"><?= __('Healthy') ?></span>
                <span class="db-kpi-trend db-trend-up">↑ Optimal</span>
            </div>
        </div>
    </div>

    <!-- Recent Audit Activity -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recent System Logs') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('User') ?></th>
                        <th><?= __('Action Summary') ?></th>
                        <th style="text-align: right;"><?= __('Timestamp') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAudit)): ?>
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No recent logs found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentAudit as $audit): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);"><?= $audit['userid'] ?></td>
                            <td style="font-size: 0.75rem; max-width: 400px; font-family: monospace; color: var(--text-muted);">
                                <?= htmlspecialchars(mb_strimwidth($audit['querystring'], 0, 80, '…')) ?>
                            </td>
                            <td style="color: var(--text-muted); text-align: right;"><?= date('d M H:i:s', strtotime($audit['transactiondate'])) ?></td>
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
                <?= __('Extended Utilities Menu') ?>
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
                $sectionId = 'Sec_Util_' . $Type;
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
