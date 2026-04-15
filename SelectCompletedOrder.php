<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fulfillment Audit Hub');
$ViewTopic = 'SalesOrders';
$BookMark = '';

// Parameter Initialization
if (isset($_POST['OrdersAfterDate'])) { $_POST['OrdersAfterDate'] = ConvertSQLDate($_POST['OrdersAfterDate']); }
if (isset($_POST['completed'])) { $Completed = "=1"; $ShowChecked = "checked='checked'"; } else { $Completed = ">=0"; $ShowChecked = ''; }

// Identification Processing
$SelectedStockItem = $_POST['SelectedStockItem'] ?? $_GET['SelectedStockItem'] ?? null;
$OrderNumber = filter_number_format($_POST['OrderNumber'] ?? $_GET['OrderNumber'] ?? '');
$CustomerRef = $_POST['CustomerRef'] ?? $_GET['CustomerRef'] ?? null;
$SelectedCustomer = $_POST['SelectedCustomer'] ?? $_GET['SelectedCustomer'] ?? ($CustomerLogin == 1 ? $_SESSION['CustomerID'] : null);

if (empty($SelectedStockItem)) unset($SelectedStockItem);
if (empty($OrderNumber)) unset($OrderNumber);
if (empty($CustomerRef)) unset($CustomerRef);
if (empty($SelectedCustomer)) unset($SelectedCustomer);

if (isset($_POST['ResetPart'])) { unset($SelectedStockItem); }

include(__DIR__ . '/includes/header.php');

// Intelligence Processing
$StockItemsResult = null;
$SalesOrdersResult = null;

// Mode 1: Item-Centric Discovery
if (isset($_POST['SearchParts'])) {
    $Keywords = $_POST['Keywords'] ?? '';
    $StockCode = $_POST['StockCode'] ?? '';
    $StockCat = $_POST['StockCat'] ?? 'All';
    $CompCriteria = (isset($_POST['completed']) ? "salesorderdetails.completed = 1 AND" : "");

    if ($Keywords != '') {
        $SearchString = '%' . str_replace(' ', '%', $Keywords) . '%';
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, stockmaster.units, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem
                FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
                WHERE $CompCriteria stockmaster.description " . LIKE . " '" . $SearchString . "' AND stockmaster.categoryid='" . $StockCat . "'
                GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
    } elseif ($StockCode != '') {
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem, stockmaster.units
                FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
                WHERE $CompCriteria stockmaster.stockid " . LIKE . " '%" . $StockCode . "%' AND stockmaster.categoryid='" . $StockCat . "'
                GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
    } elseif ($StockCat != 'All') {
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, SUM(purchorderdetails.quantityord-purchorderdetails.quantityrecd) AS qoo, SUM(salesorderdetails.quantity - salesorderdetails.qtyinvoiced) AS qdem, stockmaster.units
                FROM (((stockmaster LEFT JOIN salesorderdetails on stockmaster.stockid = salesorderdetails.stkcode) LEFT JOIN locstock ON stockmaster.stockid=locstock.stockid) LEFT JOIN purchorderdetails on stockmaster.stockid = purchorderdetails.itemcode)
                WHERE $CompCriteria stockmaster.categoryid='" . $StockCat . "'
                GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
    }
    if (isset($SQL)) {
        $StockItemsResult = DB_query($SQL);
        if (DB_num_rows($StockItemsResult) == 1) {
            $MyRow = DB_fetch_row($StockItemsResult);
            $SelectedStockItem = $MyRow[0];
            $_POST['SearchOrders'] = 'true';
        }
    }
}

// Mode 2: Order/Fulfillment Audit
if ((isset($_POST['SearchOrders']) && Is_Date($_POST['OrdersAfterDate'])) || isset($_GET['SelectedCustomer']) || isset($SelectedStockItem)) {
    $DateAfterCriteria = FormatDateforSQL($_POST['OrdersAfterDate'] ?? date('Y-m-d', mktime(0, 0, 0, date('m') - 2, date('d'), date('Y'))));
    
    $SQL = "SELECT salesorders.orderno, debtorsmaster.name, currencies.decimalplaces AS currdecimalplaces, custbranch.brname, salesorders.customerref, salesorders.orddate, salesorders.deliverydate, salesorders.deliverto, SUM(salesorderdetails.linenetprice) AS ordervalue
            FROM salesorders 
            INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno 
            INNER JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno 
            INNER JOIN custbranch ON salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno 
            INNER JOIN currencies ON debtorsmaster.currcode = currencies.currabrev
            WHERE salesorders.quotation=0 AND salesorderdetails.completed " . $Completed;
    
    if (!empty($OrderNumber)) $SQL .= " AND salesorders.orderno='" . $OrderNumber . "'";
    if (!empty($CustomerRef)) $SQL .= " AND salesorders.customerref LIKE '" . $CustomerRef . "%'";
    if (!empty($SelectedCustomer)) $SQL .= " AND salesorders.debtorno='" . $SelectedCustomer . "'";
    if (!empty($SelectedStockItem)) $SQL .= " AND salesorderdetails.stkcode='" . $SelectedStockItem . "'";
    
    $SQL .= " AND salesorders.orddate >= '" . $DateAfterCriteria . "'";
    if ($_SESSION['SalesmanLogin'] != '') { $SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'"; }

    $SQL .= " GROUP BY salesorders.orderno, debtorsmaster.name, currencies.decimalplaces, custbranch.brname, salesorders.customerref, salesorders.orddate, salesorders.deliverydate, salesorders.deliverto ORDER BY salesorders.orderno";
    
    $SalesOrdersResult = DB_query($SQL);
    if (DB_num_rows($SalesOrdersResult) == 1 && empty($StockItemsResult)) {
        $OrdRow = DB_fetch_array($SalesOrdersResult);
        echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/OrderDetails.php?OrderNumber=' . $OrdRow['orderno'] . '">';
    }
}

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-history"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Search and analyze historic fulfillment performance across all closed sales cycles') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Discovery Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-search"></i> ' . __('Search Horizons') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Placed After') . '</label>
                                <input type="date" name="OrdersAfterDate" class="db-input" value="' . (isset($_POST['OrdersAfterDate']) ? FormatDateForSQL($_POST['OrdersAfterDate']) : date('Y-m-d', mktime(0, 0, 0, date('m') - 12, date('d'), date('Y')))) . '" />
                            </div>
                            <div class="db-form-group" style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" ' . $ShowChecked . ' name="completed" id="completed_check" class="db-checkbox" />
                                <label class="db-label-sm" for="completed_check">' . __('Completed Orders Only') . '</label>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Order / Customer Ref') . '</label>
                                <input type="text" name="OrderNumber" class="db-input" style="margin-bottom: 8px;" value="' . ($OrderNumber ?? '') . '" placeholder="' . __('Order #') . '" />
                                <input type="text" name="CustomerRef" class="db-input" value="' . ($CustomerRef ?? '') . '" placeholder="' . __('Customer Ref') . '" />
                            </div>
                            <button type="submit" name="SearchOrders" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-binoculars"></i> ' . __('Search Fulfillment') . '
                            </button>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-box-open"></i> ' . __('Inventory Discovery') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Stock Category') . '</label>
                                <select name="StockCat" class="db-select">';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    echo '<option value="All">' . __('All Categories') . '</option>';
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . (($_POST['StockCat'] ?? '') == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('SKU / Keywords') . '</label>
                                <input type="text" name="StockCode" class="db-input" style="margin-bottom: 8px;" placeholder="' . __('Stock Code Extract') . '" />
                                <input type="text" name="Keywords" class="db-input" placeholder="' . __('Description Keywords') . '" />
                            </div>
                            <button type="submit" name="SearchParts" class="db-btn db-btn-secondary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-search-plus"></i> ' . __('Find Parts') . '
                            </button>
                            ' . (isset($SelectedStockItem) ? '<div class="db-badge db-badge-info" style="margin-top: 10px; width: 100%; text-align: center;">Filtering by: ' . $SelectedStockItem . ' <button type="submit" name="ResetPart" style="border:none;background:none;cursor:pointer;margin-left:5px;">&times;</button></div>' : '') . '
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if (isset($SalesOrdersResult) || isset($StockItemsResult)) {
                        $countFound = isset($SalesOrdersResult) ? DB_num_rows($SalesOrdersResult) : DB_num_rows($StockItemsResult);
                        $totalVal = 0;
                        if (isset($SalesOrdersResult)) {
                            $temp = []; while($r = DB_fetch_array($SalesOrdersResult)) { $totalVal += $r['ordervalue']; $temp[] = $r; }
                            $SalesOrdersResult = $temp;
                        }

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-list-ol"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Volume Found') . '</span><span class="value">' . $countFound . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-calendar-alt"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Audit Horizon') . '</span><span class="value">' . ($_POST['OrdersAfterDate'] ?? __('Active')) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-dollar-sign"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Value') . '</span><span class="value">' . locale_number_format($totalVal, 0) . '</span></div>
                                </div>
                              </div>';

                        // Part Discovery Ledger
                        if (isset($StockItemsResult)) {
                            echo '<div class="db-card" style="margin-bottom: var(--space-6);">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-tag"></i> ' . __('Item Discovery Ledger') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Code') . '</th>
                                                        <th>' . __('Description') . '</th>
                                                        <th class="text-right">' . __('On Hand') . '</th>
                                                        <th class="text-right">' . __('PO Items') . '</th>
                                                        <th class="text-right">' . __('Back Orders') . '</th>
                                                        <th>' . __('UOM') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                while ($Row = DB_fetch_array($StockItemsResult)) {
                                                    echo '<tr>
                                                            <td><button type="submit" name="SelectedStockItem" value="' . $Row['stockid'] . '" class="db-btn db-btn-outline" style="padding: 2px 8px; font-family: monospace;">' . $Row['stockid'] . '</button></td>
                                                            <td class="db-font-medium">' . $Row['description'] . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['qoh'], $Row['decimalplaces']) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['qoo'], $Row['decimalplaces']) . '</td>
                                                            <td class="text-right text-danger">' . locale_number_format($Row['qdem'], $Row['decimalplaces']) . '</td>
                                                            <td><span class="db-badge">' . $Row['units'] . '</span></td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        }

                        // Order Fulfillment Ledger
                        if (isset($SalesOrdersResult)) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-shipping-fast"></i> ' . __('Historic Fulfillment Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Order') . '</th>
                                                        <th>' . __('Identity') . '</th>
                                                        <th>' . __('Reference') . '</th>
                                                        <th>' . __('Order Date') . '</th>
                                                        <th>' . __('Fulfillment') . '</th>
                                                        <th class="text-right">' . __('Total Value') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                foreach ($SalesOrdersResult as $Ord) {
                                                    echo '<tr>
                                                            <td><a href="' . $RootPath . '/OrderDetails.php?OrderNumber=' . $Ord['orderno'] . '" class="db-badge db-badge-primary" style="font-family: monospace;">#' . $Ord['orderno'] . '</a></td>
                                                            <td>
                                                                <div class="db-font-bold text-primary">' . $Ord['name'] . '</div>
                                                                <small class="text-muted">' . $Ord['brname'] . '</small>
                                                            </td>
                                                            <td class="db-font-medium">' . $Ord['customerref'] . '</td>
                                                            <td>' . ConvertSQLDate($Ord['orddate']) . '</td>
                                                            <td>
                                                                <div style="font-size: 0.85rem;">' . $Ord['deliverto'] . '</div>
                                                                <small class="text-muted">' . __('Req') . ': ' . ConvertSQLDate($Ord['deliverydate']) . '</small>
                                                            </td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Ord['ordervalue'], $Ord['currdecimalplaces']) . '</td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        }
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-history" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Audit Discovery Hub') . '</h2>
                                    <p>' . __('Search historic fulfillment performance and closed sales cycles. Select your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
