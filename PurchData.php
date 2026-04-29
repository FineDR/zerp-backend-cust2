<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Purchasing Data');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['EffectiveFrom'])){$_POST['EffectiveFrom'] = ConvertSQLDate($_POST['EffectiveFrom']);}

if (isset($_GET['SupplierID'])) {
	$SupplierID = trim(mb_strtoupper($_GET['SupplierID']));
} elseif (isset($_POST['SupplierID'])) {
	$SupplierID = trim(mb_strtoupper($_POST['SupplierID']));
}

if (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
}

if (isset($_GET['Edit'])) {
	$Edit = true;
} elseif (isset($_POST['Edit'])) {
	$Edit = true;
} else {
	$Edit = false;
}

if (isset($_GET['EffectiveFrom'])) {
	$EffectiveFrom = $_GET['EffectiveFrom'];
} elseif ($Edit == true AND isset($_POST['EffectiveFrom'])) {
	$EffectiveFrom = FormatDateForSQL($_POST['EffectiveFrom']);
}

if (isset($_POST['StockUOM'])) {
	$StockUOM = $_POST['StockUOM'];
}

/*Deleting a supplier purchasing discount */
if (isset($_GET['DeleteDiscountID'])){
	$Result = DB_query("DELETE FROM supplierdiscounts WHERE id='" . intval($_GET['DeleteDiscountID']) . "'");
	prnMsg(__('Deleted the supplier discount record'),'success');
}

$NoPurchasingData = 0;

if (isset($_POST['SupplierDescription'])) {
	$_POST['SupplierDescription'] = trim($_POST['SupplierDescription']);
}

if ((isset($_POST['AddRecord']) OR isset($_POST['UpdateRecord'])) AND isset($SupplierID)) { /*Validate Inputs */
	$InputError = 0; /*Start assuming the best */

	if ($StockID == '' OR !isset($StockID)) {
		$InputError = 1;
		prnMsg(__('There is no stock item set up enter the stock code or select a stock item using the search page'), 'error');
	}
	if (!is_numeric(filter_number_format($_POST['Price']))) {
		$InputError = 1;
		unset($_POST['Price']);
		prnMsg(__('The price entered was not numeric and a number is expected. No changes have been made to the database'), 'error');
	} elseif ($_POST['Price'] == 0) {
		prnMsg(__('The price entered is zero') . '   ' . __('Is this intentional?'), 'warn');
	}
	if (!is_numeric(filter_number_format($_POST['LeadTime']))) {
		$InputError = 1;
		unset($_POST['LeadTime']);
		prnMsg(__('The lead time entered was not numeric a number of days is expected no changes have been made to the database'), 'error');
	}
	if (!is_numeric(filter_number_format($_POST['MinOrderQty']))) {
		$InputError = 1;
		unset($_POST['MinOrderQty']);
		prnMsg(__('The minimum order quantity was not numeric and a number is expected no changes have been made to the database'), 'error');
	}
	if (!is_numeric(filter_number_format($_POST['ConversionFactor']))) {
		$InputError = 1;
		unset($_POST['ConversionFactor']);
		prnMsg(__('The conversion factor entered was not numeric'), 'error');
	}
	if (!Is_Date($_POST['EffectiveFrom'])){
		$InputError = 1;
		unset($_POST['EffectiveFrom']);
		prnMsg(__('The date this purchase price is to take effect from must be entered'),'error');
	}
	if ($InputError == 0 AND isset($_POST['AddRecord'])) {
		$SQL = "INSERT INTO purchdata (supplierno, stockid, price, effectivefrom, suppliersuom, conversionfactor, supplierdescription, suppliers_partno, leadtime, minorderqty, preferred)
						VALUES ('" . $SupplierID . "', '" . $StockID . "', '" . filter_number_format($_POST['Price']) . "', '" . FormatDateForSQL($_POST['EffectiveFrom']) . "', '" . $_POST['SuppliersUOM'] . "', '" . filter_number_format($_POST['ConversionFactor']) . "', '" . mb_substr(DB_escape_string($_POST['SupplierDescription']), 0, 50) . "', '" . mb_substr(DB_escape_string($_POST['SupplierCode']), 0, 50) . "', '" . filter_number_format($_POST['LeadTime']) . "', '" . filter_number_format($_POST['MinOrderQty']) . "', '" . $_POST['Preferred'] . "')";
		DB_query($SQL, __('The supplier purchasing details could not be added'));
		prnMsg(__('This supplier purchasing data has been added'), 'success');
	}
	if ($InputError == 0 AND isset($_POST['UpdateRecord'])) {
		$SQL = "UPDATE purchdata SET price='" . filter_number_format($_POST['Price']) . "', effectivefrom='" . FormatDateForSQL($_POST['EffectiveFrom']) . "', suppliersuom='" . $_POST['SuppliersUOM'] . "', conversionfactor='" . filter_number_format($_POST['ConversionFactor']) . "', supplierdescription='" . mb_substr(DB_escape_string($_POST['SupplierDescription']), 0, 50) . "', suppliers_partno='" . mb_substr(DB_escape_string($_POST['SupplierCode']), 0, 50) . "', leadtime='" . filter_number_format($_POST['LeadTime']) . "', minorderqty='" . filter_number_format($_POST['MinOrderQty']) . "', preferred='" . $_POST['Preferred'] . "'
							WHERE purchdata.stockid='" . $StockID . "' AND purchdata.supplierno='" . $SupplierID . "' AND purchdata.effectivefrom='" . $_POST['WasEffectiveFrom'] . "'";
		DB_query($SQL, __('The supplier purchasing details could not be updated'));
		prnMsg(__('Supplier purchasing data has been updated'), 'success');

		for ($i = 0; $i < ($_POST['NumberOfDiscounts'] ?? 0); $i++) {
			$SQL = "UPDATE supplierdiscounts SET discountnarrative ='" . $_POST['DiscountNarrative' . $i] . "', discountamount ='" . filter_number_format($_POST['DiscountAmount' . $i]) . "', discountpercent = '" . filter_number_format($_POST['DiscountPercent' . $i]) / 100 . "', effectivefrom = '" . FormatDateForSQL($_POST['DiscountEffectiveFrom' . $i]) . "', effectiveto = '" . FormatDateForSQL($_POST['DiscountEffectiveTo' . $i]) . "' WHERE id = " . intval($_POST['DiscountID' . $i]);
			DB_query($SQL);
		}
		if (mb_strlen($_POST['DiscountNarrative'] ?? '') > 0) {
			$SQL = "INSERT INTO supplierdiscounts ( supplierno, stockid, discountnarrative, discountamount, discountpercent, effectivefrom, effectiveto )
						VALUES ('" . $SupplierID . "', '" . $StockID . "', '" . $_POST['DiscountNarrative'] . "', '" . floatval($_POST['DiscountAmount']) . "', '" . floatval($_POST['DiscountPercent']) / 100 . "', '" . FormatDateForSQL($_POST['DiscountEffectiveFrom']) . "', '" . FormatDateForSQL($_POST['DiscountEffectiveTo']) . "')";
			DB_query($SQL);
			prnMsg(__('A new supplier purchasing discount record was entered successfully'),'success');
		}
	}

	if ($InputError == 0 AND isset($_POST['AddRecord'])) {
		unset($SupplierID, $_POST['Price'], $CurrCode, $_POST['SuppliersUOM'], $_POST['EffectiveFrom'], $_POST['ConversionFactor'], $_POST['SupplierDescription'], $_POST['LeadTime'], $_POST['Preferred'], $_POST['SupplierCode'], $_POST['MinOrderQty'], $SuppName);
	}
}

if (isset($_GET['Delete'])) {
	$SQL = "DELETE FROM purchdata WHERE purchdata.supplierno='" . $SupplierID . "' AND purchdata.stockid='" . $StockID . "' AND purchdata.effectivefrom='" . $EffectiveFrom . "'";
	DB_query($SQL, __('The supplier purchasing details could not be deleted'));
	prnMsg(__('This purchasing data record has been successfully deleted'), 'success');
	unset($SupplierID);
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
    .aw-layout-grid { display: grid; grid-template-columns: 400px 1fr; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .aw-layout-grid { grid-template-columns: 1fr; } }
    .aw-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border-soft); margin-bottom: 1.5rem; overflow: hidden; }
    .aw-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-soft); background: var(--white); display: flex; align-items: center; gap: 0.75rem; }
    .aw-card-title { font-size: 1rem; font-weight: 700; color: var(--primary-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .aw-card-title i { color: var(--primary); font-size: 1.1rem; }
    .aw-card-body { padding: 1.25rem; }
    .aw-field-group { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1rem; }
    .aw-label { font-size: 0.8rem; font-weight: 700; color: var(--primary-dark); }
    .aw-input, .aw-select { width: 100%; padding: 0.6rem 0.8rem; border-radius: 8px; border: 1px solid var(--border); background: var(--white); font-size: 0.9rem; box-sizing: border-box; }
    .aw-input.text-right { text-align: right; }
    .aw-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; text-decoration: none; }
    .aw-btn-primary { background: var(--primary); color: var(--white); }
    .aw-btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
    .aw-btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    .aw-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .aw-table th { background: var(--primary-soft); color: var(--primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 0.75rem; text-align: left; }
    .aw-table td { padding: 0.75rem; border-bottom: 1px solid var(--border-soft); }
    .aw-table .number { text-align: right; font-family: monospace; }
    .aw-badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 800; background: var(--primary-soft); color: var(--primary); }
</style>

<div class="aw-page">
    <?php 
    $ItemResult = DB_query("SELECT description, units FROM stockmaster WHERE stockid='" . $StockID . "'");
    $StockRow = DB_fetch_array($ItemResult);
    $StockUOM = $StockRow['units'] ?? '';
    ?>
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb"><?php echo __('Inventory'); ?> / <?php echo __('Purchasing Data'); ?></div>
            <h1 class="aw-title"><?php echo $Title; ?> <span class="aw-badge"><?php echo $StockID; ?></span></h1>
            <p style="margin: 10px 0 0; color: var(--text-muted); font-weight: 600;"><?php echo $StockRow['description'] ?? ''; ?></p>
        </div>
        <a href="Stocks.php?StockID=<?php echo $StockID; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-box"></i> <?php echo __('Back to Item'); ?></a>
    </div>

    <div class="aw-layout-grid">
        <!-- LEFT: FORM / SEARCH -->
        <aside>
            <?php if (!isset($SupplierID)): ?>
                <div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-search"></i> <?php echo __('Supplier Selection'); ?></h3></div>
                    <div class="aw-card-body">
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'); ?>" method="post">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <input type="hidden" name="StockID" value="<?php echo $StockID; ?>" />
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Supplier Name Keywords'); ?></label>
                                <input type="text" name="Keywords" class="aw-input" placeholder="e.g. Acme Corp" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('OR Supplier Code'); ?></label>
                                <input type="text" name="SupplierCode" class="aw-input" />
                            </div>
                            <button type="submit" name="SearchSupplier" class="aw-btn aw-btn-primary w-100"><i class="fas fa-search"></i> <?php echo __('Find Suppliers'); ?></button>
                        </form>
                    </div>
                </div>

                <?php if (isset($_POST['SearchSupplier'])): 
                    $SearchString = '%' . str_replace(' ', '%', $_POST['Keywords'] ?? '') . '%';
                    if (mb_strlen($_POST['Keywords'] ?? '') > 0) {
                        $SQL = "SELECT supplierid, suppname, currcode, address1, address2, address3 FROM suppliers WHERE suppname " . LIKE  . " '".$SearchString."'";
                    } else {
                        $SQL = "SELECT supplierid, suppname, currcode, address1, address2, address3 FROM suppliers WHERE supplierid " . LIKE . " '%" . ($_POST['SupplierCode'] ?? '') . "%'";
                    }
                    $SuppRes = DB_query($SQL);
                ?>
                    <div class="aw-card">
                        <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-list-ol"></i> <?php echo __('Search Results'); ?></h3></div>
                        <div class="aw-card-body p-0">
                            <table class="aw-table">
                                <thead><tr><th><?php echo __('Code'); ?></th><th><?php echo __('Supplier'); ?></th></tr></thead>
                                <tbody>
                                    <?php while ($sRow = DB_fetch_array($SuppRes)): ?>
                                        <tr>
                                            <td>
                                                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                                                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                                                    <input type="hidden" name="StockID" value="<?php echo $StockID; ?>" />
                                                    <button type="submit" name="SupplierID" value="<?php echo $sRow['supplierid']; ?>" class="aw-btn aw-btn-outline aw-btn-sm"><?php echo $sRow['supplierid']; ?></button>
                                                </form>
                                            </td>
                                            <td><?php echo $sRow['suppname']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

            <?php else: 
                $SQL = "SELECT suppname, currcode, currencies.decimalplaces FROM suppliers INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE supplierid='".$SupplierID."'";
                $SuppRow = DB_fetch_array(DB_query($SQL));
                $SuppName = $SuppRow['suppname'];
                $CurrCode = $SuppRow['currcode'];
                
                if ($Edit || isset($_GET['Copy'])) {
                    $SQL = "SELECT * FROM purchdata WHERE supplierno='".$SupplierID."' AND stockid='".$StockID."' AND effectivefrom='".$EffectiveFrom."'";
                    $EditRow = DB_fetch_array(DB_query($SQL));
                    if ($Edit) {
                        $_POST['Price'] = locale_number_format($EditRow['price'], $SuppRow['decimalplaces']);
                        $_POST['EffectiveFrom'] = ConvertSQLDate($EditRow['effectivefrom']);
                    }
                    $_POST['SuppliersUOM'] = $EditRow['suppliersuom'];
                    $_POST['ConversionFactor'] = $EditRow['conversionfactor'];
                    $_POST['SupplierCode'] = $EditRow['suppliers_partno'];
                    $_POST['MinOrderQty'] = $EditRow['minorderqty'];
                    $_POST['LeadTime'] = $EditRow['leadtime'];
                    $_POST['Preferred'] = $EditRow['preferred'];
                    $_POST['SupplierDescription'] = $EditRow['supplierdescription'];
                }
            ?>
                <div class="aw-card">
                    <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-edit"></i> <?php echo $Edit ? __('Edit Data') : __('Add Data'); ?></h3></div>
                    <div class="aw-card-body">
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                            <input type="hidden" name="StockID" value="<?php echo $StockID; ?>" />
                            <input type="hidden" name="SupplierID" value="<?php echo $SupplierID; ?>" />
                            <?php if ($Edit): ?>
                                <input type="hidden" name="WasEffectiveFrom" value="<?php echo $EditRow['effectivefrom']; ?>" />
                                <input type="hidden" name="Edit" value="1" />
                            <?php endif; ?>

                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Supplier'); ?></label>
                                <div style="font-weight: 700; color: var(--primary);"><?php echo $SupplierID . ' - ' . $SuppName; ?></div>
                            </div>

                            <div class="aw-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Price'); ?> (<?php echo $CurrCode; ?>)</label>
                                    <input type="text" name="Price" class="aw-input text-right" value="<?php echo $_POST['Price'] ?? '0'; ?>" />
                                </div>
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Effective From'); ?></label>
                                    <input type="date" name="EffectiveFrom" class="aw-input" value="<?php echo FormatDateForSQL($_POST['EffectiveFrom'] ?? date($_SESSION['DefaultDateFormat'])); ?>" />
                                </div>
                            </div>

                            <div class="aw-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Supp. UOM'); ?></label>
                                    <input type="text" name="SuppliersUOM" class="aw-input" value="<?php echo $_POST['SuppliersUOM'] ?? $StockUOM; ?>" />
                                </div>
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Conv. Factor'); ?></label>
                                    <input type="text" name="ConversionFactor" class="aw-input text-right" value="<?php echo $_POST['ConversionFactor'] ?? '1'; ?>" />
                                </div>
                            </div>

                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Supplier Stock Code'); ?></label>
                                <input type="text" name="SupplierCode" class="aw-input" value="<?php echo $_POST['SupplierCode'] ?? ''; ?>" />
                            </div>

                            <div class="aw-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Min Order Qty'); ?></label>
                                    <input type="text" name="MinOrderQty" class="aw-input text-right" value="<?php echo $_POST['MinOrderQty'] ?? '1'; ?>" />
                                </div>
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Lead Time (Days)'); ?></label>
                                    <input type="text" name="LeadTime" class="aw-input text-right" value="<?php echo $_POST['LeadTime'] ?? '1'; ?>" />
                                </div>
                            </div>

                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Preferred Supplier'); ?></label>
                                <select name="Preferred" class="aw-select">
                                    <option value="1" <?php echo (($_POST['Preferred'] ?? 0) == 1 ? 'selected' : ''); ?>><?php echo __('Yes'); ?></option>
                                    <option value="0" <?php echo (($_POST['Preferred'] ?? 0) == 0 ? 'selected' : ''); ?>><?php echo __('No'); ?></option>
                                </select>
                            </div>

                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Supplier Description'); ?></label>
                                <input type="text" name="SupplierDescription" class="aw-input" value="<?php echo $_POST['SupplierDescription'] ?? ''; ?>" />
                            </div>

                            <button type="submit" name="<?php echo $Edit ? 'UpdateRecord' : 'AddRecord'; ?>" class="aw-btn aw-btn-primary w-100"><i class="fas fa-save"></i> <?php echo $Edit ? __('Update Data') : __('Add Data'); ?></button>
                            <a href="<?php echo $_SERVER['PHP_SELF'] . '?StockID=' . $StockID; ?>" class="aw-btn aw-btn-outline w-100" style="margin-top: 10px;"><?php echo __('Cancel'); ?></a>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <!-- RIGHT: TABLE -->
        <main>
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title"><i class="fas fa-truck"></i> <?php echo __('Current Purchasing Sources'); ?></h3></div>
                <div class="aw-card-body p-0">
                    <?php 
                    $SQL = "SELECT purchdata.*, suppliers.suppname, suppliers.currcode, currencies.decimalplaces AS currdecimalplaces FROM purchdata INNER JOIN suppliers ON purchdata.supplierno=suppliers.supplierid INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE stockid = '" . $StockID . "' ORDER BY effectivefrom DESC";
                    $Res = DB_query($SQL);
                    if (DB_num_rows($Res) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="aw-table">
                                <thead>
                                    <tr>
                                        <th><?php echo __('Supplier'); ?></th>
                                        <th class="number"><?php echo __('Price'); ?></th>
                                        <th><?php echo __('UOM'); ?></th>
                                        <th class="number"><?php echo __('Conv.'); ?></th>
                                        <th><?php echo __('Effective'); ?></th>
                                        <th><?php echo __('Lead'); ?></th>
                                        <th><?php echo __('Pref.'); ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = DB_fetch_array($Res)): ?>
                                        <tr>
                                            <td style="font-weight: 600;"><?php echo $row['suppname']; ?></td>
                                            <td class="number"><?php echo locale_number_format($row['price'], $row['currdecimalplaces']); ?> <span style="font-size: 0.7rem; color: var(--text-muted);"><?php echo $row['currcode']; ?></span></td>
                                            <td><?php echo $row['suppliersuom']; ?></td>
                                            <td class="number"><?php echo $row['conversionfactor']; ?></td>
                                            <td><?php echo ConvertSQLDate($row['effectivefrom']); ?></td>
                                            <td><?php echo $row['leadtime'] . 'd'; ?></td>
                                            <td><?php echo $row['preferred'] ? '<span class="aw-badge">YES</span>' : 'No'; ?></td>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?StockID=<?php echo $StockID; ?>&SupplierID=<?php echo $row['supplierno']; ?>&Edit=1&EffectiveFrom=<?php echo $row['effectivefrom']; ?>" class="aw-btn aw-btn-outline aw-btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="<?php echo $_SERVER['PHP_SELF']; ?>?StockID=<?php echo $StockID; ?>&SupplierID=<?php echo $row['supplierno']; ?>&Delete=1&EffectiveFrom=<?php echo $row['effectivefrom']; ?>" class="aw-btn aw-btn-outline aw-btn-sm" style="color: #ef4444;" onclick="return confirm('Delete this record?');"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center; color: var(--text-muted);"><i class="fas fa-info-circle fa-2x mb-3"></i><p><?php echo __('No purchasing data for this item.'); ?></p></div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include(__DIR__ . '/includes/footer.php'); ?>
