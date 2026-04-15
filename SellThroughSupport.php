<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sell Through Support');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Date handling
if (isset($_POST['EffectiveFrom'])){$_POST['EffectiveFrom'] = ConvertSQLDate($_POST['EffectiveFrom']);}
if (isset($_POST['EffectiveTo'])){$_POST['EffectiveTo'] = ConvertSQLDate($_POST['EffectiveTo']);}

// State Management
if (isset($_GET['SupplierID']) AND $_GET['SupplierID']!='') {
	$SupplierID = trim(mb_strtoupper($_GET['SupplierID']));
} elseif (isset($_POST['SupplierID'])) {
	$SupplierID = trim(mb_strtoupper($_POST['SupplierID']));
}

$Edit = isset($_GET['Edit']) || isset($_POST['Edit']);

// Deletion Logic
if (isset($_GET['Delete'])){
	$Result = DB_query("DELETE FROM sellthroughsupport WHERE id='" . intval($_GET['SellSupportID']) . "'");
	prnMsg(__('Deleted the supplier sell through support record'),'success');
}

// Record Committing (Add/Update)
if ((isset($_POST['AddRecord']) OR isset($_POST['UpdateRecord'])) AND isset($SupplierID)) {
	$InputError = 0;
	if (is_numeric(filter_number_format($_POST['RebateAmount']))==false) {
		$InputError = 1;
		prnMsg(__('The rebate amount entered was not numeric.'), 'error');
	} elseif (filter_number_format($_POST['RebateAmount']) == 0 AND filter_number_format($_POST['RebatePercent'])==0) {
		prnMsg(__('Either rebate amount or percent must be positive.'), 'error');
		$InputError = 1;
	} elseif (filter_number_format($_POST['RebatePercent'])>100 OR filter_number_format($_POST['RebatePercent']) < 0) {
		prnMsg(__('Rebate percent must be between 0 and 100.'), 'error');
		$InputError = 1;
	} elseif (Date1GreaterThanDate2($_POST['EffectiveFrom'], $_POST['EffectiveTo'])) {
		prnMsg(__('The effective to date is prior to the effective from date.'), 'error');
		$InputError = 1;
	}

	if ($InputError == 0) {
		if (isset($_POST['AddRecord'])) {
			$SQL = "INSERT INTO sellthroughsupport (supplierno, debtorno, categoryid, stockid, narrative, rebateamount, rebatepercent, effectivefrom, effectiveto )
					VALUES ('" . $SupplierID . "', '" . $_POST['DebtorNo'] . "', '" . $_POST['CategoryID'] . "', '" . $_POST['StockID'] . "', '" . $_POST['Narrative'] . "',
							'" . filter_number_format($_POST['RebateAmount']) . "', '" . filter_number_format($_POST['RebatePercent'])/100 . "',
							'" . FormatDateForSQL($_POST['EffectiveFrom']) . "', '" . FormatDateForSQL($_POST['EffectiveTo']) . "')";
			DB_query($SQL);
			prnMsg(__('Support record added'), 'success');
		} else {
			$SQL = "UPDATE sellthroughsupport SET debtorno='" . $_POST['DebtorNo'] . "', categoryid='" . $_POST['CategoryID'] . "', stockid='" . $_POST['StockID'] . "',
						narrative='" . $_POST['Narrative'] . "', rebateamount='" . filter_number_format($_POST['RebateAmount']) . "',
						rebatepercent='" . filter_number_format($_POST['RebatePercent'])/100 . "', effectivefrom='" . FormatDateForSQL($_POST['EffectiveFrom']) . "',
						effectiveto='" . FormatDateForSQL($_POST['EffectiveTo']) . "'
					WHERE id='" . $_POST['SellSupportID'] . "'";
			DB_query($SQL);
			prnMsg(__('Support record updated'), 'success');
			$Edit = false;
		}
		// Reset form
		unset($_POST['StockID'], $_POST['EffectiveFrom'], $_POST['DebtorNo'], $_POST['CategoryID'], $_POST['Narrative'], $_POST['RebatePercent'], $_POST['RebateAmount'], $_POST['EffectiveTo']);
	}
}

echo '<div class="db-page">';

if (isset($_POST['SearchSupplier'])) {
    // Search Execution
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
	$SQL = "SELECT supplierid, suppname, currcode, address1, address2, address3 FROM suppliers WHERE (suppname " . LIKE . " '" . $SearchString . "' OR supplierid " . LIKE . " '%" . $_POST['SupplierCode'] . "%')";
	$SuppliersResult = DB_query($SQL);

    echo '<div class="db-page-header">
            <div class="db-page-title"><i class="fas fa-truck"></i> ' . __('Supplier Selection') . '</div>
          </div>
          <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
          <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
          <div class="db-bottom-layout">
            <aside class="db-col-aside">';
            include_search_controls();
    echo '  </aside>
            <main class="db-col-main">
                <div class="db-card">
                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-list"></i> ' . __('Search Results') . '</div></div>
                    <div class="db-card-body p-0">
                        <div class="db-table-wrapper">
                            <table class="db-table">
                                <thead>
                                    <tr>
                                        <th>' . __('Code') . '</th>
                                        <th>' . __('Name') . '</th>
                                        <th>' . __('Currency') . '</th>
                                        <th>' . __('Address') . '</th>
                                    </tr>
                                </thead>
                                <tbody>';
                                while ($MyRow = DB_fetch_array($SuppliersResult)) {
                                    echo '<tr>
                                            <td><button type="submit" name="SupplierID" value="' . $MyRow['supplierid'] . '" class="db-btn db-btn-outline-primary db-btn-sm">' . $MyRow['supplierid'] . '</button></td>
                                            <td class="db-font-bold">' . $MyRow['suppname'] . '</td>
                                            <td><span class="db-badge">' . $MyRow['currcode'] . '</span></td>
                                            <td class="db-font-sm text-muted">' . $MyRow['address1'] . ' ' . $MyRow['address2'] . '</td>
                                          </tr>';
                                }
    echo '                      </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
          </div>
          </form>';
} elseif (!isset($SupplierID)) {
    // Initial Search Phase
    echo '<div class="db-page-header">
            <div class="db-page-title"><i class="fas fa-hand-holding-usd"></i> ' . $Title . '</div>
          </div>
          <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
          <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
          <div class="db-bottom-layout">
            <aside class="db-col-aside">';
            include_search_controls();
    echo '  </aside>
            <main class="db-col-main">
                <div class="db-card" style="background: var(--surface-alt); min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center;">
                    <div class="db-card-body">
                        <i class="fas fa-search" style="font-size: 4rem; color: var(--border-color); margin-bottom: 20px;"></i>
                        <h3 class="text-muted">' . __('Search for a supplier to manage support deals') . '</h3>
                    </div>
                </div>
            </main>
          </div>
          </form>';
} else {
    // Management Phase
    $SuppResult = DB_query("SELECT suppname, currcode, decimalplaces FROM suppliers INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE supplierid='" . $SupplierID . "'");
	$SuppRow = DB_fetch_array($SuppResult);

    // KPIs for Supplier Support
    $sqlActiveCount = "SELECT COUNT(*) FROM sellthroughsupport WHERE supplierno='" . $SupplierID . "' AND effectiveto >= CURRENT_DATE";
    $resActiveCount = DB_query($sqlActiveCount);
    $rowActive = DB_fetch_row($resActiveCount);

    echo '<div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-shield-alt"></i> ' . $Title . '
                <span style="font-size: 0.9rem; margin-left:10px; opacity: 0.7;">' . $SupplierID . ' - ' . $SuppRow['suppname'] . '</span>
            </div>
            <div class="db-header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline"><i class="fas fa-sync"></i> ' . __('Change Supplier') . '</a>
            </div>
          </div>

          <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
          <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
          <input type="hidden" name="SupplierID" value="' . $SupplierID . '" />
          
          <div class="db-bottom-layout">
            <!-- Form Sidebar on the Left -->
            <aside class="db-col-aside">';
                include_management_form($SupplierID, $Edit, $SuppRow['currcode']);
    echo '  </aside>

            <!-- KPIs and Results on the Right -->
            <main class="db-col-main">
                <div class="kpi-grid" style="margin-bottom: var(--space-6);">
                    <div class="kpi-card-v2">
                        <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-check-circle"></i></div>
                        <div class="kpi-data"><span class="label">' . __('Active Deals') . '</span><span class="value">' . $rowActive[0] . '</span></div>
                    </div>
                    <div class="kpi-card-v2">
                        <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-coins"></i></div>
                        <div class="kpi-data"><span class="label">' . __('Currency') . '</span><span class="value">' . $SuppRow['currcode'] . '</span></div>
                    </div>
                </div>';

                $SQL = "SELECT id, sellthroughsupport.debtorno, debtorsmaster.name, rebateamount, rebatepercent, effectivefrom, effectiveto, sellthroughsupport.stockid, description, categorydescription, sellthroughsupport.categoryid, narrative
                        FROM sellthroughsupport LEFT JOIN stockmaster ON sellthroughsupport.stockid=stockmaster.stockid
                        LEFT JOIN stockcategory ON sellthroughsupport.categoryid = stockcategory.categoryid
                        LEFT JOIN debtorsmaster ON sellthroughsupport.debtorno=debtorsmaster.debtorno
                        WHERE supplierno = '" . $SupplierID . "' ORDER BY sellthroughsupport.effectivefrom DESC";
                $Result = DB_query($SQL);

                echo '<div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-list-alt"></i> ' . __('Existing Support Records') . '</div></div>
                        <div class="db-card-body p-0">
                            <div class="db-table-wrapper">
                                <table class="db-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Scope') . '</th>
                                            <th>' . __('Customer') . '</th>
                                            <th class="text-right">' . __('Rebate') . '</th>
                                            <th>' . __('Validity') . '</th>
                                            <th class="text-right">' . __('Actions') . '</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                                    while ($MyRow = DB_fetch_array($Result)) {
                                        $ItemDesc = ($MyRow['categoryid']=='') ? $MyRow['stockid'] . ' - ' . $MyRow['description'] : __('Category') . ': ' . $MyRow['categorydescription'];
                                        $Customer = ($MyRow['debtorno']=='') ? __('All Customers') : $MyRow['name'];
                                        $isActive = (strtotime($MyRow['effectiveto']) >= strtotime('today'));
                                        $badge = $isActive ? 'success' : 'secondary';
                                        
                                        echo '<tr>
                                                <td><div class="db-font-bold text-primary">' . $ItemDesc . '</div><small class="text-muted">' . $MyRow['narrative'] . '</small></td>
                                                <td>' . $Customer . '</td>
                                                <td class="text-right">
                                                    <div class="db-font-bold">' . ($MyRow['rebateamount'] != 0 ? locale_number_format($MyRow['rebateamount'], $SuppRow['decimalplaces']) : locale_number_format($MyRow['rebatepercent']*100, 2) . '%') . '</div>
                                                </td>
                                                <td>
                                                    <span class="db-badge db-badge-' . $badge . '" style="margin-right:5px;">' . ($isActive ? __('Active') : __('Expired')) . '</span>
                                                    <small class="text-muted">' . ConvertSQLDate($MyRow['effectivefrom']) . ' - ' . ConvertSQLDate($MyRow['effectiveto']) . '</small>
                                                </td>
                                                <td class="text-right db-action-btn-row">
                                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?SellSupportID=' . $MyRow['id'] . '&SupplierID=' . $SupplierID . '&Edit=1" class="db-btn db-btn-outline-primary db-btn-sm" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
                                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?SellSupportID=' . $MyRow['id'] . '&Delete=1&SupplierID=' . $SupplierID . '" class="db-btn db-btn-outline-danger db-btn-sm" onclick="return confirm(\'' . __('Delete this record?') . '\')" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
                                                </td>
                                              </tr>';
                                    }
    echo '                          </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
            </main>
          </div>
          </form>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');

function include_search_controls() {
    echo '<div class="db-card">
            <div class="db-card-header"><div class="db-card-title"><i class="fas fa-search"></i> ' . __('Supplier Search') . '</div></div>
            <div class="db-card-body">
                <div class="db-form-group">
                    <label class="db-label">' . __('Supplier Name') . '</label>
                    <input type="text" name="Keywords" class="db-input" placeholder="' . __('Name keywords...') . '" />
                </div>
                <div class="db-form-group">
                    <label class="db-label">' . __('Supplier Code') . '</label>
                    <input type="text" name="SupplierCode" class="db-input" placeholder="' . __('Code extract...') . '" />
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" name="SearchSupplier" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-search"></i> ' . __('Find Now') . '
                    </button>
                </div>
            </div>
          </div>';
}

function include_management_form($SupplierID, $Edit, $CurrCode) {
    if ($Edit) {
        $SQL = "SELECT id, debtorno, rebateamount, rebatepercent, effectivefrom, effectiveto, stockid, categoryid, narrative FROM sellthroughsupport WHERE id='" . intval($_GET['SellSupportID']) . "'";
        $Row = DB_fetch_array(DB_query($SQL));
        $_POST['DebtorNo'] = $Row['debtorno'];
        $_POST['StockID'] = $Row['stockid'];
        $_POST['CategoryID'] = $Row['categoryid'];
        $_POST['Narrative'] = $Row['narrative'];
        $_POST['RebatePercent'] = locale_number_format($Row['rebatepercent']*100, 2);
        $_POST['RebateAmount'] = locale_number_format($Row['rebateamount'], 2);
        $_POST['EffectiveFrom'] = ConvertSQLDate($Row['effectivefrom']);
        $_POST['EffectiveTo'] = ConvertSQLDate($Row['effectiveto']);
        echo '<input type="hidden" name="SellSupportID" value="' . $Row['id'] . '" />';
        echo '<input type="hidden" name="Edit" value="1" />';
    }

    echo '<div class="db-card">
            <div class="db-card-header"><div class="db-card-title"><i class="fas fa-edit"></i> ' . ($Edit ? __('Edit Support') : __('Create Support')) . '</div></div>
            <div class="db-card-body">
                <div class="db-form-group">
                    <label class="db-label">' . __('Target Customer') . '</label>
                    <select name="DebtorNo" class="db-select">';
                    echo '<option value="">' . __('All Customers') . '</option>';
                    $CustomerResult = DB_query("SELECT debtorno, name FROM debtorsmaster");
                    while ($Cust = DB_fetch_array($CustomerResult)) {
                        $sel = (($_POST['DebtorNo'] ?? '') == $Cust['debtorno']) ? 'selected' : '';
                        echo '<option ' . $sel . ' value="' . $Cust['debtorno'] . '">' . $Cust['name'] . '</option>';
                    }
    echo '          </select>
                </div>

                <div class="db-form-group">
                    <label class="db-label">' . __('Whole Category Support') . '</label>
                    <select name="CategoryID" class="db-select">';
                    echo '<option value="">' . __('Specific Item Only') . '</option>';
                    $Cats = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype='F'");
                    while ($cat = DB_fetch_array($Cats)) {
                        $sel = (($_POST['CategoryID'] ?? '') == $cat['categoryid']) ? 'selected' : '';
                        echo '<option ' . $sel . ' value="' . $cat['categoryid'] . '">' . $cat['categorydescription'] . '</option>';
                    }
    echo '          </select>
                </div>

                <div class="db-form-group">
                    <label class="db-label">' . __('Specific Item Support') . '</label>
                    <select name="StockID" class="db-select">';
                    echo '<option value="">' . __('Support Entire Category') . '</option>';
                    $Items = DB_query("SELECT stockmaster.stockid, description FROM purchdata INNER JOIN stockmaster ON purchdata.stockid=stockmaster.stockid WHERE supplierno ='" . $SupplierID . "' AND preferred=1");
                    while ($item = DB_fetch_array($Items)) {
                        $sel = (($_POST['StockID'] ?? '') == $item['stockid']) ? 'selected' : '';
                        echo '<option ' . $sel . ' value="' . $item['stockid'] . '">' . $item['stockid'] . ' - ' . $item['description'] . '</option>';
                    }
    echo '          </select>
                </div>

                <div class="db-form-group"><label class="db-label">' . __('Narrative') . '</label><input type="text" name="Narrative" class="db-input" value="' . ($_POST['Narrative'] ?? '') . '" /></div>
                
                <div class="db-grid-2">
                    <div class="db-form-group"><label class="db-label">' . __('Rebate Value (' . $CurrCode . ')') . '</label><input type="text" name="RebateAmount" class="db-input number" value="' . ($_POST['RebateAmount'] ?? 0) . '" /></div>
                    <div class="db-form-group"><label class="db-label">' . __('Rebate %') . '</label><div style="display:flex; align-items:center;"><input type="text" name="RebatePercent" class="db-input number" value="' . ($_POST['RebatePercent'] ?? 0) . '" /> <span style="margin-left:5px;">%</span></div></div>
                </div>

                <div class="db-grid-2">
                    <div class="db-form-group"><label class="db-label">' . __('Start Date') . '</label><input type="date" name="EffectiveFrom" class="db-input" value="' . (isset($_POST['EffectiveFrom']) ? FormatDateForSQL($_POST['EffectiveFrom']) : date('Y-m-d')) . '" /></div>
                    <div class="db-form-group"><label class="db-label">' . __('End Date') . '</label><input type="date" name="EffectiveTo" class="db-input" value="' . (isset($_POST['EffectiveTo']) ? FormatDateForSQL($_POST['EffectiveTo']) : date('Y-m-d', strtotime('last day of this month'))) . '" /></div>
                </div>

                <div style="margin-top: 30px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <button type="submit" name="' . ($Edit ? 'UpdateRecord' : 'AddRecord') . '" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-' . ($Edit ? 'save' : 'plus') . '"></i> ' . ($Edit ? __('Update Deal') : __('Create Deal')) . '
                    </button>
                    ' . ($Edit ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?SupplierID=' . $SupplierID . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Cancel') . '</a>' : '') . '
                </div>
            </div>
          </div>';
}
