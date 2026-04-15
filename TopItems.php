<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Inventory Velocity Analysis');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

// Parameter Initialization
if (!isset($_POST['NumberOfDays'])) { $_POST['NumberOfDays'] = 30; }
if (!isset($_POST['NumberOfTopItems'])) { $_POST['NumberOfTopItems'] = 50; }
if (!isset($_POST['Location'])) { $_POST['Location'] = 'All'; }
if (!isset($_POST['StockCat'])) { $_POST['StockCat'] = 'All'; }
if (!isset($_POST['Customers'])) { $_POST['Customers'] = 'All'; }
if (!isset($_POST['Sequence'])) { $_POST['Sequence'] = 'valuesales'; }
if (!isset($_POST['MaxDaysOfStock'])) { $_POST['MaxDaysOfStock'] = 9999; }

$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// Query Calculation Branch
if ($ShowResults) {
    $FromDate = FormatDateForSQL(DateAdd(date($_SESSION['DefaultDateFormat']),'d', -filter_number_format($_POST['NumberOfDays'])));
    
    $SQL = "SELECT salesorderdetails.stkcode,
                   SUM(salesorderdetails.qtyinvoiced) AS totalinvoiced,
                   SUM(salesorderdetails.qtyinvoiced * salesorderdetails.unitprice/currencies.rate ) AS valuesales,
                   stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces, currencies.rate, debtorsmaster.currcode, fromstkloc
            FROM salesorderdetails, salesorders 
            INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1,
            debtorsmaster, stockmaster, currencies
            WHERE salesorderdetails.orderno = salesorders.orderno
            AND salesorderdetails.stkcode = stockmaster.stockid
            AND salesorders.debtorno = debtorsmaster.debtorno
            AND debtorsmaster.currcode = currencies.currabrev
            AND salesorderdetails.actualdispatchdate >= '" . $FromDate . "'";

    if ($_POST['Location'] != 'All') { $SQL .= " AND salesorders.fromstkloc = '" . $_POST['Location'] . "'"; }
    if ($_POST['Customers'] != 'All') { $SQL .= " AND debtorsmaster.typeid = '" . $_POST['Customers'] . "'"; }
    if ($_POST['StockCat'] != 'All') { $SQL .= " AND stockmaster.categoryid = '" . $_POST['StockCat'] . "'"; }

    $SQL .= " GROUP BY salesorderdetails.stkcode
              ORDER BY `" . $_POST['Sequence'] . "` DESC
              LIMIT " . (int)$_POST['NumberOfTopItems'];
    $Result = DB_query($SQL);
}

// PDF Generation Branch
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
    $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Top Sales Items') . '<br />' . __('Lookback Period') . ': ' . $_POST['NumberOfDays'] . ' ' . __('Days') . '</div>';
    $HTML .= '<table class="db-table"><thead><tr><th>Item</th><th>Qty</th><th>Value</th><th>QOH</th></tr></thead><tbody>';
    while ($Row = DB_fetch_array($Result)) {
        $HTML .= '<tr><td>' . $Row['stkcode'] . ' - ' . $Row['description'] . '</td><td class="number">' . locale_number_format($Row['totalinvoiced'], $Row['decimalplaces']) . '</td><td class="number">' . locale_number_format($Row['valuesales'], 2) . '</td><td class="number">' . GetQuantityOnHand($Row['stkcode'], 'USER_CAN_VIEW') . '</td></tr>';
    }
    $HTML .= '</tbody></table></body></html>';
    $DomPDF = new Dompdf($DomPDFOptions);
    $DomPDF->loadHtml($HTML);
    $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
    $DomPDF->render();
    $DomPDF->stream($_SESSION['DatabaseName'] . '_TopSalesItems_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
    exit;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-chart-line"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Velocity Filters') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Lookback (Days)') . '</label>
                                <input type="number" name="NumberOfDays" class="db-input" value="' . (int)$_POST['NumberOfDays'] . '" min="1" required />
                                <small class="text-muted">' . __('Examine sales within this period') . '</small>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Rank By') . '</label>
                                <select name="Sequence" class="db-select">
                                    <option ' . ($_POST['Sequence'] == 'valuesales' ? 'selected' : '') . ' value="valuesales">' . __('Value of Sales') . '</option>
                                    <option ' . ($_POST['Sequence'] == 'totalinvoiced' ? 'selected' : '') . ' value="totalinvoiced">' . __('Quantity (Pieces)') . '</option>
                                </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Max Stock Buffer (Days)') . '</label>
                                <input type="number" name="MaxDaysOfStock" class="db-input" value="' . (int)$_POST['MaxDaysOfStock'] . '" min="1" />
                                <small class="text-muted">' . __('Only show items with buffer less than this') . '</small>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Category') . '</label>
                                <select name="StockCat" class="db-select">
                                    <option value="All">' . __('All Categories') . '</option>';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    while ($cat = DB_fetch_array($catRes)) {
                                        $sel = ($_POST['StockCat'] == $cat['categoryid']) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $cat['categoryid'] . '">' . $cat['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Location') . '</label>
                                <select name="Location" class="db-select">
                                    <option value="All">' . __('All Locations') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY locationname");
                                    while ($loc = DB_fetch_array($locRes)) {
                                        $sel = ($_POST['Location'] == $loc['loccode']) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $loc['loccode'] . '">' . $loc['locationname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Display Limit') . '</label>
                                <input type="number" name="NumberOfTopItems" class="db-input" value="' . (int)$_POST['NumberOfTopItems'] . '" min="1" max="500" />
                            </div>

                            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="justify-content: center;">
                                    <i class="fas fa-bolt"></i> ' . __('Calculate Velocity') . '
                                </button>
                                <button type="submit" name="PrintPDF" class="db-btn db-btn-outline-primary" style="justify-content: center;">
                                    <i class="fas fa-file-pdf"></i> ' . __('Export PDF') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="justify-content: center;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $ProbCount = 0; $TotalRevenue = 0; $TotalStockDays = 0; $ItemsWithStockInfo = 0;
                        $data = [];
                        
                        while ($Row = DB_fetch_array($Result)) {
                            $QOH = 0; $QOO = 0;
                            if (in_array($Row['mbflag'], ['M', 'B'])) {
                                $QOH = GetQuantityOnHand($Row['stkcode'], 'USER_CAN_VIEW');
                                $QOO = GetQuantityOnOrder($Row['stkcode'], 'ALL');
                            }
                            
                            $dailyUsage = $Row['totalinvoiced'] / (float)$_POST['NumberOfDays'];
                            $daysStock = ($dailyUsage > 0) ? ($QOH + $QOO) / $dailyUsage : 9999;
                            
                            if ($daysStock < $_POST['MaxDaysOfStock']) {
                                $Row['qoh'] = $QOH;
                                $Row['qoo'] = $QOO;
                                $Row['days_stock'] = $daysStock;
                                $TotalRevenue += $Row['valuesales'];
                                $TotalStockDays += $daysStock;
                                $ItemsWithStockInfo++;
                                $ProbCount++;
                                $data[] = $Row;
                            }
                        }
                        
                        $avgBuffer = ($ItemsWithStockInfo > 0) ? $TotalStockDays / $ItemsWithStockInfo : 0;
                        $leadItem = count($data) > 0 ? $data[0]['description'] : 'N/A';

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-funnel-dollar"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Top Items Revenue') . '</span><span class="value">' . locale_number_format($TotalRevenue, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-crown"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Peak Velocity Item') . '</span><span class="value" style="font-size: 1.1rem; filter: none;">' . $leadItem . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-hourglass-half"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Avg Network Buffer') . '</span><span class="value">' . locale_number_format($avgBuffer, 0) . ' ' . __('Days') . '</span></div>
                                </div>
                              </div>';

                        if ($ProbCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-medal"></i> ' . __('Inventory Excellence Detections') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">' . __('Rank') . '</th>
                                                        <th>' . __('Item Context') . '</th>
                                                        <th class="text-right">' . __('Qty Invoiced') . '</th>
                                                        <th class="text-right">' . __('Revenue') . '</th>
                                                        <th class="text-right">' . __('Buffer (Days)') . '</th>
                                                        <th class="text-right">' . __('Stock Detail') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                $rank = 1;
                                                foreach ($data as $Row) {
                                                    $badge = '';
                                                    if ($rank == 1) $badge = '<i class="fas fa-medal" style="color: #FFD700; font-size: 1.2rem;"></i>';
                                                    elseif ($rank == 2) $badge = '<i class="fas fa-medal" style="color: #C0C0C0; font-size: 1.1rem;"></i>';
                                                    elseif ($rank == 3) $badge = '<i class="fas fa-medal" style="color: #CD7F32; font-size: 1rem;"></i>';
                                                    else $badge = '<span class="text-muted">#' . $rank . '</span>';
                                                    
                                                    $riskClass = ($Row['days_stock'] < 7) ? 'text-danger' : (($Row['days_stock'] < 30) ? 'text-warning' : 'text-success');

                                                    echo '<tr>
                                                            <td class="text-center">' . $badge . '</td>
                                                            <td>
                                                                <a href="' . $RootPath . '/SelectProduct.php?StockID=' . $Row['stkcode'] . '" class="db-font-bold text-primary">' . $Row['stkcode'] . '</a>
                                                                <div class="db-font-sm text-muted">' . $Row['description'] . '</div>
                                                            </td>
                                                            <td class="text-right db-font-semibold">' . locale_number_format($Row['totalinvoiced'], $Row['decimalplaces']) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['valuesales'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                                            <td class="text-right db-font-bold ' . $riskClass . '">' . locale_number_format($Row['days_stock'], 0) . '</td>
                                                            <td class="text-right">
                                                                <div class="db-font-sm"><span class="text-muted">' . __('QOH') . ':</span> ' . locale_number_format($Row['qoh'], $Row['decimalplaces']) . '</div>
                                                                <div class="db-font-sm"><span class="text-muted">' . __('QOO') . ':</span> ' . locale_number_format($Row['qoo'], $Row['decimalplaces']) . '</div>
                                                            </td>
                                                          </tr>';
                                                    $rank++;
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
                                    <i class="fas fa-tachometer-alt" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Velocity Calculation Hub') . '</h2>
                                    <p>' . __('Analyze item movement by revenue or volume. Define your lookback period and risk thresholds on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
