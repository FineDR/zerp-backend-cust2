<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Item Prices');
$ViewTopic = 'Prices';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['StartDate'])) {
	$_POST['StartDate'] = ConvertSQLDate($_POST['StartDate']);
}
if (isset($_POST['EndDate'])) {
	$_POST['EndDate'] = ConvertSQLDate($_POST['EndDate']);
}

/* Check at least one sales type exists */
$SQL = "SELECT typeabbrev, sales_type FROM salestypes";
$TypeResult = DB_query($SQL);
if (DB_num_rows($TypeResult) == 0) {
	prnMsg(__('There are no sales types setup. Click') .
		' <a href="' . $RootPath . '/SalesTypes.php" target="_blank">' .
		' ' . __('here') . ' ' . '</a>' . __('to create them'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

//initialise no input errors assumed initially before we test
$InputError = 0;

if (isset($_GET['Item'])) {
	$Item = trim(mb_strtoupper($_GET['Item']));
} elseif (isset($_POST['Item'])) {
	$Item = trim(mb_strtoupper($_POST['Item']));
}

if (!isset($_POST['TypeAbbrev']) OR $_POST['TypeAbbrev'] == '') {
	$_POST['TypeAbbrev'] = $_SESSION['DefaultPriceList'];
}

if (!isset($_POST['CurrAbrev'])) {
	$_POST['CurrAbrev'] = $_SESSION['CompanyRecord']['currencydefault'];
}

if (!isset($Item)) {
	echo '<div class="aw-page"><div class="aw-header"><h1 class="aw-title">' . __('Error') . '</h1></div>';
	prnMsg(__('An item must first be selected before this page is called'), 'error');
	echo '</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$SQL = "SELECT stockmaster.description, stockmaster.mbflag FROM stockmaster WHERE stockmaster.stockid='" . $Item . "'";
$Result = DB_query($SQL);
$MyRow = DB_fetch_row($Result);

if (DB_num_rows($Result) == 0) {
	prnMsg(__('The part code entered does not exist in the database'), 'error');
	$InputError = 1;
}

$PartDescription = $MyRow[0] ?? '';

if (($MyRow[1] ?? '') == 'K') {
	prnMsg(__('The part selected is a kit set item') . ', ' .
		__('these items explode into their components when selected on an order'), 'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['submit'])) {
	if (!is_numeric(filter_number_format($_POST['Price'])) OR $_POST['Price'] == '') {
		$InputError = 1;
		prnMsg(__('The price entered must be numeric'), 'error');
	}
	if (!Is_Date($_POST['StartDate'])) {
		$InputError = 1;
		prnMsg(__('The date this price is to take effect from must be entered'), 'error');
	}
	
	if (Is_Date($_POST['EndDate'])) {
		$SQLEndDate = FormatDateForSQL($_POST['EndDate']);
	} else {
		$SQLEndDate = '9999-12-31';
	}

	$SQL = "SELECT COUNT(typeabbrev) FROM prices WHERE stockid='" . $Item . "' AND startdate='" . FormatDateForSQL($_POST['StartDate']) . "' AND enddate ='" . $SQLEndDate . "' AND typeabbrev='" . $_POST['TypeAbbrev'] . "' AND currabrev='" . $_POST['CurrAbrev'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0] != 0 AND !isset($_POST['OldTypeAbbrev']) AND !isset($_POST['OldCurrAbrev'])) {
		prnMsg(__('This price has already been entered. To change it you should edit it'), 'warn');
		$InputError = 1;
	}

	if (isset($_POST['OldTypeAbbrev']) AND isset($_POST['OldCurrAbrev']) AND mb_strlen($Item) > 1 AND $InputError != 1) {
		$SQL = "UPDATE prices SET typeabbrev='" . $_POST['TypeAbbrev'] . "', currabrev='" . $_POST['CurrAbrev'] . "', price='" . filter_number_format($_POST['Price']) . "', startdate='" . FormatDateForSQL($_POST['StartDate']) . "', enddate='" . $SQLEndDate . "'
				WHERE prices.stockid='" . $Item . "' AND startdate='" . $_POST['OldStartDate'] . "' AND enddate ='" . $_POST['OldEndDate'] . "' AND prices.typeabbrev='" . $_POST['OldTypeAbbrev'] . "' AND prices.currabrev='" . $_POST['OldCurrAbrev'] . "' AND prices.debtorno=''";
		DB_query($SQL, __('Could not update the existing prices'));
		ReSequenceEffectiveDates($Item, $_POST['TypeAbbrev'], $_POST['CurrAbrev']);
		prnMsg(__('The price has been updated'), 'success');
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO prices (stockid, typeabbrev, currabrev, startdate, enddate, price)
				VALUES ('" . $Item . "', '" . $_POST['TypeAbbrev'] . "', '" . $_POST['CurrAbrev'] . "', '" . FormatDateForSQL($_POST['StartDate']) . "', '" . $SQLEndDate . "', '" . filter_number_format($_POST['Price']) . "')";
		DB_query($SQL, __('The new price could not be added'));
		ReSequenceEffectiveDates($Item, $_POST['TypeAbbrev'], $_POST['CurrAbrev']);
		prnMsg(__('The new price has been inserted'), 'success');
	}
	unset($_POST['Price'], $_POST['StartDate'], $_POST['EndDate'], $_POST['OldTypeAbbrev'], $_POST['OldCurrAbrev'], $_POST['OldStartDate'], $_POST['OldEndDate']);
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM prices WHERE stockid = '" . $Item . "' AND typeabbrev='" . $_GET['TypeAbbrev'] . "' AND currabrev ='" . $_GET['CurrAbrev'] . "' AND startdate = '" . $_GET['StartDate'] . "' AND enddate = '" . $_GET['EndDate'] . "' AND debtorno=''";
	DB_query($SQL, __('Could not delete this price'));
	prnMsg(__('The selected price has been deleted'), 'success');
}

if (isset($_GET['Edit'])) {
	$_POST['OldTypeAbbrev'] = $_GET['TypeAbbrev'];
	$_POST['OldCurrAbrev'] = $_GET['CurrAbrev'];
	$_POST['OldStartDate'] = $_GET['StartDate'];
	$_POST['OldEndDate'] = $_GET['EndDate'];
	$_POST['CurrAbrev'] = $_GET['CurrAbrev'];
	$_POST['TypeAbbrev'] = $_GET['TypeAbbrev'];
	$_POST['Price'] = $_GET['Price'];
	$_POST['StartDate'] = ConvertSQLDate($_GET['StartDate']);
	$_POST['EndDate'] = ($_GET['EndDate'] == '' OR $_GET['EndDate'] == '9999-12-31') ? '' : ConvertSQLDate($_GET['EndDate']);
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

    body { background-color: var(--bg); color: var(--text-main); font-family: var(--font-sans); }
    .aw-page { max-width: 1400px; margin: 0 auto; padding: 2rem; }
    .aw-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; }
    .aw-breadcrumb { font-size: 0.75rem; font-weight: 800; color: var(--primary); text-transform: uppercase; margin-bottom: 0.5rem; }
    .aw-title { font-size: 2rem; font-weight: 900; color: var(--primary-dark); margin: 0; line-height: 1; }
    .aw-layout-grid { display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .aw-layout-grid { grid-template-columns: 1fr; } }
    .aw-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); margin-bottom: 1.5rem; overflow: hidden; }
    .aw-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft); background: var(--white); display: flex; align-items: center; gap: 0.75rem; }
    .aw-card-title { font-size: 1rem; font-weight: 700; color: var(--primary-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .aw-card-title i { color: var(--primary); font-size: 1.1rem; }
    .aw-card-body { padding: 1.25rem; }
    .aw-field-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
    .aw-label { font-size: 0.8rem; font-weight: 700; color: var(--primary-dark); display: flex; align-items: center; gap: 0.25rem; }
    .aw-input, .aw-select { width: 100%; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); font-size: 0.9rem; transition: all 0.2s; box-sizing: border-box; }
    .aw-input:focus, .aw-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }
    .aw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
    .aw-btn-primary { background: var(--primary); color: var(--white); }
    .aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .aw-btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
    .aw-btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    .aw-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    .aw-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; text-align: left; border-bottom: 2px solid var(--border-soft); }
    .aw-table td { padding: 1rem; border-bottom: 1px solid var(--border-soft); color: var(--text-main); }
    .aw-table tr:hover { background: var(--bg); }
    .aw-table .number { text-align: right; font-family: 'JetBrains Mono', monospace; }
    .aw-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; background: var(--primary-soft); color: var(--primary); }
</style>

<div class="aw-page">
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb">
                <a href="index.php?Application=stock" style="color: inherit; text-decoration: none;"><?php echo __('Inventory'); ?></a> / <?php echo __('Pricing'); ?>
            </div>
            <h1 class="aw-title">
                <?php echo $Title; ?>
                <span class="aw-badge" style="vertical-align: middle; margin-left: 10px;"><?php echo $Item; ?></span>
            </h1>
            <p style="margin: 10px 0 0; color: var(--text-muted); font-weight: 600;"><?php echo $PartDescription; ?></p>
        </div>
        <div>
            <a href="<?php echo $RootPath; ?>/Stocks.php?StockID=<?php echo $Item; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-edit"></i> <?php echo __('Back to Item'); ?></a>
        </div>
    </div>

    <div class="aw-layout-grid">
        <!-- LEFT: FORM -->
        <aside>
            <div class="aw-card">
                <div class="aw-card-header">
                    <h3 class="aw-card-title"><i class="fas fa-tag"></i> <?php echo (isset($_POST['OldTypeAbbrev']) ? __('Edit Price') : __('Add New Price')); ?></h3>
                </div>
                <div class="aw-card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                        <input type="hidden" name="Item" value="<?php echo $Item; ?>" />
                        <?php if (isset($_POST['OldTypeAbbrev'])): ?>
                            <input type="hidden" name="OldTypeAbbrev" value="<?php echo $_POST['OldTypeAbbrev']; ?>" />
                            <input type="hidden" name="OldCurrAbrev" value="<?php echo $_POST['OldCurrAbrev']; ?>" />
                            <input type="hidden" name="OldStartDate" value="<?php echo $_POST['OldStartDate']; ?>" />
                            <input type="hidden" name="OldEndDate" value="<?php echo $_POST['OldEndDate']; ?>" />
                        <?php endif; ?>

                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Currency'); ?></label>
                            <select name="CurrAbrev" class="aw-select">
                                <?php 
                                require_once(__DIR__ . '/includes/CurrenciesArray.php');
                                $CurrRes = DB_query("SELECT currabrev FROM currencies");
                                while ($cRow = DB_fetch_array($CurrRes)) {
                                    echo '<option ' . (($_POST['CurrAbrev'] ?? '') == $cRow['currabrev'] ? 'selected' : '') . ' value="' . $cRow['currabrev'] . '">' . ($CurrencyName[$cRow['currabrev']] ?? $cRow['currabrev']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Sales Type / Price List'); ?></label>
                            <select name="TypeAbbrev" class="aw-select">
                                <?php 
                                DB_data_seek($TypeResult, 0);
                                while ($tRow = DB_fetch_array($TypeResult)) {
                                    echo '<option ' . (($_POST['TypeAbbrev'] ?? '') == $tRow['typeabbrev'] ? 'selected' : '') . ' value="' . $tRow['typeabbrev'] . '">' . $tRow['sales_type'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Effective From'); ?></label>
                            <input type="date" name="StartDate" class="aw-input" required value="<?php echo FormatDateForSQL($_POST['StartDate'] ?? date($_SESSION['DefaultDateFormat'])); ?>" />
                        </div>

                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Effective To'); ?></label>
                            <input type="date" name="EndDate" class="aw-input" value="<?php echo ($_POST['EndDate'] ?? '') != '' ? FormatDateForSQL($_POST['EndDate']) : ''; ?>" placeholder="<?php echo __('Indefinite'); ?>" />
                        </div>

                        <div class="aw-field-group">
                            <label class="aw-label"><?php echo __('Price'); ?></label>
                            <input type="text" name="Price" class="aw-input text-right" required value="<?php echo $_POST['Price'] ?? ''; ?>" />
                        </div>

                        <button type="submit" name="submit" class="aw-btn aw-btn-primary w-100" style="margin-top: 10px;">
                            <i class="fas fa-save"></i> <?php echo (isset($_POST['OldTypeAbbrev']) ? __('Update Price') : __('Enter Price')); ?>
                        </button>
                        
                        <?php if (isset($_POST['OldTypeAbbrev'])): ?>
                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?Item=<?php echo $Item; ?>" class="aw-btn aw-btn-outline w-100" style="margin-top: 10px;">
                                <?php echo __('Cancel Edit'); ?>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </aside>

        <!-- RIGHT: TABLE -->
        <main>
            <div class="aw-card">
                <div class="aw-card-header">
                    <h3 class="aw-card-title"><i class="fas fa-list"></i> <?php echo __('Pricing List'); ?></h3>
                </div>
                <div class="aw-card-body" style="padding: 0;">
                    <?php 
                    $SQL = "SELECT currencies.currency, salestypes.sales_type, prices.price, prices.stockid, prices.typeabbrev, prices.currabrev, prices.startdate, prices.enddate, currencies.decimalplaces AS currdecimalplaces
                            FROM prices INNER JOIN salestypes ON prices.typeabbrev = salestypes.typeabbrev INNER JOIN currencies ON prices.currabrev=currencies.currabrev
                            WHERE prices.stockid='" . $Item . "' AND prices.debtorno='' ORDER BY prices.currabrev, prices.typeabbrev, prices.startdate";
                    $Result = DB_query($SQL);
                    
                    if (DB_num_rows($Result) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="aw-table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Currency'); ?></th>
                                        <th><?php echo __('Sales Type'); ?></th>
                                        <th class="number"><?php echo __('Price'); ?></th>
                                        <th><?php echo __('Start Date'); ?></th>
                                        <th><?php echo __('End Date'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = DB_fetch_array($Result)): 
                                        $EndDateDisplay = ($row['enddate'] == '9999-12-31') ? '<span class="aw-badge aw-badge-success">' . __('Indefinite') . '</span>' : ConvertSQLDate($row['enddate']);
                                    ?>
                                        <tr>
                                            <td><?php echo $CurrencyName[$row['currabrev']] ?? $row['currabrev']; ?></td>
                                            <td><?php echo $row['sales_type']; ?></td>
                                            <td class="number"><?php echo locale_number_format($row['price'], $row['currdecimalplaces'] + 2); ?></td>
                                            <td><?php echo ConvertSQLDate($row['startdate']); ?></td>
                                            <td><?php echo $EndDateDisplay; ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?Item=<?php echo $Item; ?>&TypeAbbrev=<?php echo $row['typeabbrev']; ?>&CurrAbrev=<?php echo $row['currabrev']; ?>&Price=<?php echo locale_number_format($row['price'], $row['currdecimalplaces']); ?>&StartDate=<?php echo $row['startdate']; ?>&EndDate=<?php echo $row['enddate']; ?>&Edit=1" class="aw-btn aw-btn-outline aw-btn-sm" title="<?php echo __('Edit'); ?>"><i class="fas fa-edit"></i></a>
                                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>?Item=<?php echo $Item; ?>&TypeAbbrev=<?php echo $row['typeabbrev']; ?>&CurrAbrev=<?php echo $row['currabrev']; ?>&StartDate=<?php echo $row['startdate']; ?>&EndDate=<?php echo $row['enddate']; ?>&delete=yes" class="aw-btn aw-btn-outline aw-btn-sm" style="color: #ef4444;" onclick="return confirm('<?php echo __('Are you sure you wish to delete this price?'); ?>');" title="<?php echo __('Delete'); ?>"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p><?php echo __('There are no prices set up for this part'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php 
include(__DIR__ . '/includes/footer.php');

function ReSequenceEffectiveDates($Item, $PriceList, $CurrAbbrev) {
	$StartDate = '';
	$EndDate = '';
	$SQL = "SELECT price, startdate, enddate FROM prices WHERE debtorno='' AND stockid='" . $Item . "' AND currabrev='" . $CurrAbbrev . "' AND typeabbrev='" . $PriceList . "' AND enddate <> '9999-12-31' ORDER BY startdate, enddate";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($NextStartDate)) {
			if (Date1GreaterThanDate2(ConvertSQLDate($MyRow['startdate']), $NextStartDate)) {
				$NextStartDate = ConvertSQLDate($MyRow['startdate']);
				if (Date1GreaterThanDate2(ConvertSQLDate($EndDate), ConvertSQLDate($MyRow['startdate']))) {
					$SQL = "UPDATE prices SET enddate = '" . FormatDateForSQL(DateAdd($NextStartDate, 'd', -1)) . "' WHERE stockid ='" . $Item . "' AND currabrev='" . $CurrAbbrev . "' AND typeabbrev='" . $PriceList . "' AND startdate ='" . $StartDate . "' AND enddate = '" . $EndDate . "' AND debtorno =''";
					DB_query($SQL);
				}
			}
		} else { $NextStartDate = ConvertSQLDate($MyRow['startdate']); }
		$StartDate = $MyRow['startdate'];
		$EndDate = $MyRow['enddate'];
	}
	$SQL = "SELECT price, startdate, enddate FROM prices WHERE debtorno='' AND stockid='" . $Item . "' AND currabrev='" . $CurrAbbrev . "' AND typeabbrev='" . $PriceList . "' AND enddate ='9999-12-31' ORDER BY startdate";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($OldStartDate)) {
			$NewEndDate = FormatDateForSQL(DateAdd(ConvertSQLDate($MyRow['startdate']), 'd', -1));
			$SQL = "UPDATE prices SET enddate = '" . $NewEndDate . "' WHERE stockid ='" . $Item . "' AND currabrev='" . $CurrAbbrev . "' AND typeabbrev='" . $PriceList . "' AND startdate ='" . $OldStartDate . "' AND enddate = '9999-12-31' AND debtorno =''";
			DB_query($SQL);
		}
		$OldStartDate = $MyRow['startdate'];
	}
}
?>
