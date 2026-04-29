<?php

/* Selection of items. All item maintenance, transactions and inquiries start with this script. */

$PricesSecurity = 12; //don't show pricing info unless security token 12 available to user
$SuppliersSecurity = 9; //don't show supplier purchasing info unless security token 9 available to user
$CostSecurity = 18; //don't show cost info unless security token 18 available to user

require(__DIR__ . '/includes/session.php');

$Title = __('Search Inventory Items');
$ViewTopic = 'Inventory';
$BookMark = 'SelectingInventory';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_GET['StockID'])) {
	$_GET['StockID'] = trim(mb_strtoupper($_GET['StockID']));
	$_POST['Select'] = trim(mb_strtoupper($_GET['StockID']));
}

if (isset($_GET['NewSearch']) or isset($_POST['Next']) or isset($_POST['Previous']) or isset($_POST['Go'])) {
	unset($StockID);
	unset($_SESSION['SelectedStockItem']);
	unset($_POST['Select']);
}

if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}
}
if (isset($_POST['StockCode'])) {
	$_POST['StockCode'] = trim(mb_strtoupper($_POST['StockCode']));
}

if (!isset($_POST['StockFilter'])) {
    $_POST['StockFilter'] = 'All';
}

// Auto-trigger search if no item is selected and no search performed yet
if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous']) OR (!isset($_POST['Select']) AND !isset($_SESSION['SelectedStockItem']))) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous']) AND !isset($_POST['Search'])) {
        // Default View on Landing: Automatically show the list
		$_POST['PageOffset'] = 1;
        $_POST['StockCat'] = 'All';
        $_POST['Search'] = 'Search'; // Trigger the search results view
	} elseif (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) {
		// Just stay on current page if not a nav action
	}
	$SQL = GenerateStockmasterQuery($_POST);
	$SearchResult = DB_query($SQL);
}

// Always show the search facilities
$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
$Result1 = DB_query($SQL);
if (DB_num_rows($Result1) == 0) {
	prnMsg(__('There are no stock categories currently defined'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

?>
<style>
    :root {
        --primary: hsl(145, 63%, 38%);
        --primary-hover: hsl(145, 63%, 32%);
        --primary-dark: hsl(145, 45%, 22%);
        --primary-soft: hsl(145, 40%, 95%);
        --bg: hsl(210, 20%, 97%);
        --white: #ffffff;
        --border: #e2e8f0;
        --border-soft: #f1f5f9;
        --text-main: #334155;
        --text-muted: #64748b;
        --shadow: 0 1px 3px rgba(0,0,0,0.1);
        --radius: 12px;
        --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
    }
    body { background-color: var(--bg); color: var(--text-main); font-family: var(--font-sans); overflow-x: hidden; }
    .aw-page { max-width: 1400px; margin: 0 auto; padding: 1.5rem; }
    .aw-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; }
    .aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.25rem; }
    .aw-title { font-size: 1.75rem; font-weight: 900; color: var(--primary-dark); margin: 0; line-height: 1; }
    
    .aw-layout-grid { display: grid; gap: 1.5rem; align-items: start; }
    .aw-layout-search { grid-template-columns: 320px 1fr; }
    .aw-layout-dashboard { grid-template-columns: 1fr 350px; }
    
    @media (max-width: 1024px) { .aw-layout-grid { grid-template-columns: 1fr; } }
    
    .aw-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); margin-bottom: 1rem; overflow: hidden; }
    .aw-card-header { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-soft); background: var(--white); display: flex; align-items: center; gap: 0.75rem; }
    .aw-card-title { font-size: 0.9rem; font-weight: 700; color: var(--primary-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .aw-card-body { padding: 1rem; }
    
    .aw-field-group { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.75rem; }
    .aw-label { font-size: 0.75rem; font-weight: 700; color: var(--primary-dark); }
    .aw-input, .aw-select { width: 100%; padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); font-size: 0.85rem; box-sizing: border-box; }
    
    .aw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; border: none; text-decoration: none; transition: all 0.2s; }
    .aw-btn-primary { background: var(--primary); color: var(--white); }
    .aw-btn-primary:hover { background: var(--primary-hover); }
    .aw-btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
    .aw-btn-sm { padding: 0.35rem 0.75rem; font-size: 0.75rem; border-radius: 6px; }
    
    .aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
    .aw-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.65rem; padding: 0.75rem; text-align: left; position: sticky; top: 0; z-index: 10; }
    .aw-table td { padding: 0.75rem; border-bottom: 1px solid var(--border-soft); }
    .aw-table tr:hover { background: var(--bg); }
    .aw-table .number { text-align: right; font-family: 'JetBrains Mono', monospace; font-weight: 600; }
    
    .aw-pagination { display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 1rem; background: var(--white); border-top: 1px solid var(--border-soft); }
    .aw-page-link { min-width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; background: var(--white); border: 1px solid var(--border); color: var(--text-main); font-weight: 700; font-size: 0.8rem; cursor: pointer; transition: all 0.2s; }
    .aw-page-link:hover { border-color: var(--primary); color: var(--primary); }
    .aw-page-link.active { background: var(--primary); color: var(--white); border-color: var(--primary); }
    .aw-page-link.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
    
    .aw-badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; background: var(--primary-soft); color: var(--primary); }
</style>

<div class="aw-page">

<?php 
if (!isset($_POST['Search']) AND (isset($_POST['Select']) OR isset($_SESSION['SelectedStockItem']))) {
	// --- DASHBOARD MODE (MASTER-SIDEBAR) ---
	if (isset($_POST['Select'])) {
		$_SESSION['SelectedStockItem'] = $_POST['Select'];
		$StockID = $_POST['Select'];
		unset($_POST['Select']);
	} else {
		$StockID = $_SESSION['SelectedStockItem'];
	}

	$Result = DB_query("SELECT stockmaster.*, stockcategory.stocktype, stockcategory.categorydescription
						FROM stockmaster INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
						WHERE stockid='" . $StockID . "'");
	$MyRow = DB_fetch_array($Result);
	
	$Its_A_Kitset_Assembly_Or_Dummy = in_array($MyRow['mbflag'], ['A', 'G', 'K', 'D']);
?>
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb"><?php echo __('Inventory'); ?> / <?php echo __('Dashboard'); ?></div>
            <h1 class="aw-title"><?php echo $StockID; ?> - <?php echo $MyRow['description']; ?></h1>
        </div>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?NewSearch=Yes" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-search"></i> <?php echo __('New Search'); ?></a>
    </div>

    <div class="aw-layout-grid aw-layout-dashboard">
        <!-- LEFT: MAIN CONTENT -->
        <main>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-info-circle"></i> <?php echo __('Item Specifications'); ?></h3></div>
                <div class="aw-card-body">
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                        <div><label class="aw-label"><?php echo __('UOM'); ?></label><div style="font-weight:700;"><?php echo $MyRow['units']; ?></div></div>
                        <div><label class="aw-label"><?php echo __('Type'); ?></label><div style="font-weight:700;"><?php echo $MyRow['mbflag']; ?></div></div>
                        <div><label class="aw-label"><?php echo __('Weight'); ?></label><div style="font-weight:700;"><?php echo locale_number_format($MyRow['grossweight'],3); ?></div></div>
                    </div>
                </div>
            </div>

            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-chart-pie"></i> <?php echo __('Stock Levels'); ?></h3></div>
                <div class="aw-card-body">
                    <?php 
                    $QOH = ($Its_A_Kitset_Assembly_Or_Dummy ? 0 : GetQuantityOnHand($StockID, 'ALL'));
                    $Demand = GetDemand($StockID, 'ALL');
                    ?>
                    <div style="display: flex; gap: 2rem;">
                        <div><div class="aw-label"><?php echo __('On Hand'); ?></div><div style="font-size: 1.5rem; font-weight: 900; color: var(--primary);"><?php echo locale_number_format($QOH, $MyRow['decimalplaces']); ?></div></div>
                        <div><div class="aw-label"><?php echo __('Demand'); ?></div><div style="font-size: 1.5rem; font-weight: 900; color: #ef4444;"><?php echo locale_number_format($Demand, $MyRow['decimalplaces']); ?></div></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- RIGHT: SIDEBAR -->
        <aside>
            <div class="aw-card" style="padding: 1rem; text-align: center;">
                <?php 
                $PossibleImageFiles = glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{png,jpg,jpeg}', GLOB_BRACE);
                $ImageFile = (count($PossibleImageFiles) > 0 ? $PossibleImageFiles[0] : '');
                echo GetImageLink($ImageFile, $StockID, 200, 200, "max-width: 100%; height: auto; border-radius: 8px;");
                ?>
            </div>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-bolt"></i> <?php echo __('Quick Actions'); ?></h3></div>
                <div class="aw-card-body" style="display: grid; gap: 0.5rem;">
                    <a href="Stocks.php?StockID=<?php echo urlencode($StockID); ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none;"><i class="fas fa-edit"></i> <?php echo __('Edit Item'); ?></a>
                    <a href="StockStatus.php?StockID=<?php echo urlencode($StockID); ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none;"><i class="fas fa-warehouse"></i> <?php echo __('Stock Status'); ?></a>
                    <a href="StockMovements.php?StockID=<?php echo urlencode($StockID); ?>" class="aw-btn aw-btn-outline w-100" style="justify-content: flex-start; border: none;"><i class="fas fa-exchange-alt"></i> <?php echo __('Movements'); ?></a>
                </div>
            </div>
        </aside>
    </div>

<?php 
} else {
	// --- SEARCH MODE (FILTERS LEFT, RESULTS RIGHT) ---
?>
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb"><?php echo __('Inventory'); ?> / <?php echo __('Listing'); ?></div>
            <h1 class="aw-title"><?php echo $Title; ?></h1>
        </div>
    </div>

    <div class="aw-layout-grid aw-layout-search">
        <!-- LEFT: FILTERS -->
        <aside>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-search"></i> <?php echo __('Search Filters'); ?></h3></div>
                <div class="aw-card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Category'); ?></label>
                            <select name="StockCat" class="aw-select">
                                <option value="All"><?php echo __('All Categories'); ?></option>
                                <?php 
                                DB_data_seek($Result1, 0);
                                while ($cRow = DB_fetch_array($Result1)) {
                                    echo '<option ' . (($_POST['StockCat'] ?? '') == $cRow['categoryid'] ? 'selected' : '') . ' value="' . $cRow['categoryid'] . '">' . $cRow['categorydescription'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Stock Status'); ?></label>
                            <select name="StockFilter" class="aw-select">
                                <option value="All" <?php if ($_POST['StockFilter'] == 'All') echo 'selected'; ?>><?php echo __('All Registered Items'); ?></option>
                                <option value="InStock" <?php if ($_POST['StockFilter'] == 'InStock') echo 'selected'; ?>><?php echo __('In-Stock Only'); ?></option>
                                <option value="OutOfStock" <?php if ($_POST['StockFilter'] == 'OutOfStock') echo 'selected'; ?>><?php echo __('Out-of-Stock Only'); ?></option>
                            </select>
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Keywords'); ?></label>
                            <input type="text" name="Keywords" class="aw-input" value="<?php echo $_POST['Keywords'] ?? ''; ?>" />
                        </div>
                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Stock Code'); ?></label>
                            <input type="text" name="StockCode" class="aw-input" value="<?php echo $_POST['StockCode'] ?? ''; ?>" />
                        </div>
                        <button type="submit" name="Search" class="aw-btn aw-btn-primary w-100"><i class="fas fa-sync"></i> <?php echo __('Find Items'); ?></button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- RIGHT: RESULTS WITH RESPONSIVE PAGINATION -->
        <main>
            <?php 
            if (isset($SearchResult)) {
                $ListCount = DB_num_rows($SearchResult);
                $DisplayMax = 15; // Set a modern compact limit to avoid scrolling
                $MaxPages = ceil($ListCount / $DisplayMax);
                $PageOffset = $_POST['PageOffset'] ?? 1;

                if ($ListCount > 0): ?>
                    <div class="aw-card">
                        <div class="aw-card-header" style="justify-content: space-between;">
                            <h3 class="aw-card-title"><i class="fas fa-list"></i> <?php echo __('Inventory Records'); ?></h3>
                            <div style="display: flex; gap: 0.5rem;">
                                <span class="aw-badge" style="background: var(--primary-soft); color: var(--primary);"><?php echo $ListCount; ?> <?php echo __('Found'); ?></span>
                                <?php if ($_POST['StockFilter'] == 'All'): ?>
                                    <span class="aw-badge" style="background: #dcfce7; color: #166534;"><?php echo __('All SKUs'); ?></span>
                                <?php elseif ($_POST['StockFilter'] == 'InStock'): ?>
                                    <span class="aw-badge" style="background: #dcfce7; color: #166534;"><?php echo __('Physical Stock'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="aw-card-body p-0">
                            <div style="overflow-x: auto;">
                                <table class="aw-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo __('Code'); ?></th>
                                            <th><?php echo __('Description'); ?></th>
                                            <th class="number"><?php echo __('On Hand'); ?></th>
                                            <th><?php echo __('Units'); ?></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        DB_data_seek($SearchResult, ($PageOffset - 1) * $DisplayMax);
                                        $i = 0;
                                        while ($row = DB_fetch_array($SearchResult) AND ($i < $DisplayMax)): ?>
                                            <tr>
                                                <td style="font-weight: 700; color: var(--primary);"><?php echo $row['stockid']; ?></td>
                                                <td style="font-weight: 600;"><?php echo $row['description']; ?></td>
                                                <td class="number" style="color: <?php echo ($row['qoh'] > 0 ? '#166534' : '#ef4444'); ?>;"><?php echo locale_number_format($row['qoh'], $row['decimalplaces']); ?></td>
                                                <td><?php echo $row['units']; ?></td>
                                                <td style="text-align: right;">
                                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                                                        <button type="submit" name="Select" value="<?php echo $row['stockid']; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><?php echo __('Select'); ?></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php 
                                        $i++;
                                        endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- MODERN RESPONSIVE PAGINATION -->
                            <?php if ($MaxPages > 1): ?>
                                <div class="aw-pagination">
                                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="display: flex; gap: 0.5rem;">
                                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                                        <input type="hidden" name="StockCat" value="<?php echo $_POST['StockCat']; ?>" />
                                        <input type="hidden" name="StockFilter" value="<?php echo $_POST['StockFilter']; ?>" />
                                        <input type="hidden" name="Keywords" value="<?php echo $_POST['Keywords']; ?>" />
                                        <input type="hidden" name="StockCode" value="<?php echo $_POST['StockCode']; ?>" />
                                        <input type="hidden" name="Search" value="Search" />

                                        <!-- Prev -->
                                        <button type="submit" name="PageOffset" value="<?php echo $PageOffset - 1; ?>" class="aw-page-link <?php if ($PageOffset <= 1) echo 'disabled'; ?>"><i class="fas fa-chevron-left"></i></button>
                                        
                                        <!-- Page Numbers (Modern Responsive Logic) -->
                                        <?php 
                                        $range = 2;
                                        for ($p = 1; $p <= $MaxPages; $p++): 
                                            if ($p == 1 || $p == $MaxPages || ($p >= $PageOffset - $range && $p <= $PageOffset + $range)):
                                        ?>
                                            <button type="submit" name="PageOffset" value="<?php echo $p; ?>" class="aw-page-link <?php if ($p == $PageOffset) echo 'active'; ?>"><?php echo $p; ?></button>
                                        <?php 
                                            elseif ($p == $PageOffset - $range - 1 || $p == $PageOffset + $range + 1):
                                                echo '<span style="color: var(--text-muted);">...</span>';
                                            endif;
                                        endfor; 
                                        ?>

                                        <!-- Next -->
                                        <button type="submit" name="PageOffset" value="<?php echo $PageOffset + 1; ?>" class="aw-page-link <?php if ($PageOffset >= $MaxPages) echo 'disabled'; ?>"><i class="fas fa-chevron-right"></i></button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="aw-card"><div class="aw-card-body text-center" style="padding: 4rem;"><p><?php echo __('No items found matching your criteria.'); ?></p></div></div>
                <?php endif;
            }
            ?>
        </main>
    </div>
<?php } ?>

</div>

<?php 
include(__DIR__ . '/includes/footer.php');

function PrepareSearchString(string $InputString): string {
    return '%' . str_replace(' ', '%', mb_strtoupper($InputString)) . '%';
}

function GenerateStockmasterQuery(array $post): string {
    $SQL = "SELECT stockmaster.stockid, stockmaster.description, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.decimalplaces
            FROM stockmaster LEFT JOIN locstock ON stockmaster.stockid = locstock.stockid ";
    $WhereSQL = " WHERE 1=1 "; 
    if (isset($post['Keywords']) && mb_strlen($post['Keywords']) > 0) {
        $SearchString = PrepareSearchString($post['Keywords']);
        $WhereSQL .= "AND (stockmaster.description LIKE '$SearchString' OR stockmaster.stockid LIKE '$SearchString') ";
    } elseif (isset($post['StockCode']) && mb_strlen($post['StockCode']) > 0) {
        $SearchString = PrepareSearchString($post['StockCode']);
        $WhereSQL .= "AND stockmaster.stockid LIKE '$SearchString' ";
    }
    if ($post['StockCat'] != 'All') {
        $WhereSQL .= "AND stockmaster.categoryid = '" . $post['StockCat'] . "' ";
    }
    
    $SQL .= $WhereSQL . " GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.decimalplaces ";
    
    // Applying the Stock Filter
    if ($post['StockFilter'] == 'InStock') {
        $SQL .= " HAVING SUM(locstock.quantity) > 0 ";
    } elseif ($post['StockFilter'] == 'OutOfStock') {
        $SQL .= " HAVING SUM(locstock.quantity) <= 0 OR SUM(locstock.quantity) IS NULL ";
    }

    $SQL .= " ORDER BY stockmaster.stockid";
    return $SQL;
}
?>
