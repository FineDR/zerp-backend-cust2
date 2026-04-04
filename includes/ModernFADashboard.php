<?php
/* Modern Fixed Assets Dashboard - High-fidelity overview for Asset Management */

// 1. Data Fetching
$today = date('Y-m-d');

// 1.1 Total Assets Count
$sqlTotalAssets = "SELECT COUNT(*) as asset_count FROM fixedassets";
$resTotalAssets = DB_query($sqlTotalAssets);
$rowTotalAssets = DB_fetch_array($resTotalAssets);
$totalAssets = (int)($rowTotalAssets['asset_count'] ?? 0);

// 1.2 Total Net Book Value (NBV)
// NBV = Cost - AccumDepn. In webERP, these are usually stored in different fields or calculated.
$sqlNBV = "SELECT SUM(cost - accumdepn) as total_nbv FROM fixedassets";
$resNBV = DB_query($sqlNBV);
$rowNBV = DB_fetch_array($resNBV);
$totalNBV = number_format($rowNBV['total_nbv'] ?? 0, 2);

// 1.3 Assets by Category
$sqlCat = "SELECT fixedassetcategories.categorydescription, COUNT(fixedassets.assetid) as asset_count 
           FROM fixedassets 
           JOIN fixedassetcategories ON fixedassets.assetcategoryid = fixedassetcategories.categoryid 
           GROUP BY fixedassetcategories.categorydescription 
           ORDER BY asset_count DESC LIMIT 5";
$resCat = DB_query($sqlCat);
$assetCats = [];
while ($row = DB_fetch_assoc($resCat)) {
    $assetCats[] = $row;
}

// 1.4 Recent Asset Additions
$recentAssets = [];
$sqlRecent = "SELECT assetid, description, datepurchased, cost FROM fixedassets ORDER BY assetid DESC LIMIT 6";
$resRecent = DB_query($sqlRecent);
while ($row = DB_fetch_assoc($resRecent)) {
    $recentAssets[] = $row;
}

?>

<div class="db-page">
    <!-- Header -->
    <div class="db-page-header">
        <div>
            <h2 class="db-page-title"><?= __('Fixed Assets Overview') ?></h2>
            <p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Asset Lifecycle & Depreciation') ?></p>
        </div>
        <div class="db-header-actions">
            <a href="<?= $RootPath ?>/FixedAssetItems.php" class="db-btn db-btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <?= __('New Asset') ?>
            </a>
            <a href="<?= $RootPath ?>/FixedAssetRegister.php" class="db-btn db-btn-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <?= __('Asset Register') ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="db-kpi-row">
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Total Assets') ?></span>
                <span class="db-kpi-value"><?= $totalAssets ?></span>
                <span class="db-kpi-trend db-trend-neutral"><?= __('Fixed Assets Count') ?></span>
            </div>
        </div>
        <div class="db-kpi-card">
            <div class="db-kpi-icon db-icon-green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"></path><path d="M12 18V6"></path></svg>
            </div>
            <div class="db-kpi-body">
                <span class="db-kpi-label"><?= __('Net Book Value') ?></span>
                <span class="db-kpi-value">TZS <?= $totalNBV ?></span>
                <span class="db-kpi-trend db-trend-up">↑ Current Valuation</span>
            </div>
        </div>
    </div>

    <!-- Assets by Category -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Assets by Category') ?></h3>
        <div class="db-dist-list">
            <?php foreach ($assetCats as $dist): 
                $pct = $totalAssets > 0 ? ($dist['asset_count'] / $totalAssets) * 100 : 0;
            ?>
            <div class="db-dist-item">
                <div class="db-dist-header">
                    <span><?= htmlspecialchars($dist['categorydescription']) ?></span>
                    <span style="color: var(--primary);"><?= $dist['asset_count'] ?> <?= __('Assets') ?></span>
                </div>
                <div class="db-dist-bar-bg">
                    <div class="db-dist-bar-fill" style="width: <?= $pct ?>%;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Assets -->
    <div class="db-card">
        <h3 class="db-card-title"><?= __('Recently Added Assets') ?></h3>
        <div class="db-table-wrapper">
            <table class="db-table">
                <thead>
                    <tr>
                        <th><?= __('Asset ID') ?></th>
                        <th><?= __('Description') ?></th>
                        <th><?= __('Purchased') ?></th>
                        <th style="text-align: right;"><?= __('Cost') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentAssets)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);"><?= __('No assets found') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($recentAssets as $asset): ?>
                        <tr>
                            <td style="font-weight: 700; color: var(--primary);">#<?= $asset['assetid'] ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($asset['description']) ?></td>
                            <td style="color: var(--text-muted);"><?= date('d M Y', strtotime($asset['datepurchased'])) ?></td>
                            <td style="font-weight: 700; text-align: right;">
                                TZS <?= number_format($asset['cost'], 2) ?>
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
                <?= __('Extended Assets Menu') ?>
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
                $sectionId = 'Sec_FA_' . $Type;
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
