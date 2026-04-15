<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Dormant Stock Analysis');

// Parameter Initialization
if (!isset($_POST['NumberOfDays'])) { $_POST['NumberOfDays'] = 30; }
if (!isset($_POST['Location'])) { $_POST['Location'] = array('All'); }
if (!isset($_POST['Customers'])) { $_POST['Customers'] = 'All'; }
if (!isset($_POST['StockCat'])) { $_POST['StockCat'] = 'All'; }

$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// Query Construction Logic
$FromDate = FormatDateForSQL(DateAdd(date($_SESSION['DefaultDateFormat']),'d', -filter_number_format($_POST['NumberOfDays'])));
$WhereStockCat = ($_POST['StockCat'] == 'All') ? "" : " AND stockmaster.categoryid = '" . $_POST['StockCat'] ."'";

if ($_POST['Location'][0] == 'All') {
    $WhereLocation = "";
    $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.actualcost, stockmaster.categoryid, SUM(locstock.quantity) as total_qty
            FROM stockmaster INNER JOIN locstock ON stockmaster.stockid = locstock.stockid
            INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
            WHERE 1=1 " . $WhereStockCat . "
            AND (locstock.quantity > 0)
            AND NOT EXISTS (SELECT * FROM salesorderdetails, salesorders WHERE stockmaster.stockid = salesorderdetails.stkcode AND (salesorderdetails.orderno = salesorders.orderno) AND salesorderdetails.actualdispatchdate > '" . $FromDate . "')
            AND NOT EXISTS (SELECT * FROM stockmoves WHERE stockmoves.stockid = stockmaster.stockid AND stockmoves.trandate >= '" . $FromDate . "')
            GROUP BY stockmaster.stockid ORDER BY stockmaster.stockid";
} else {
    $locList = "'" . implode("','", $_POST['Location']) . "'";
    $WhereLocation = " AND locstock.loccode IN (" . $locList . ") ";
    $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.actualcost, stockmaster.categoryid, locstock.quantity as item_qty, locations.locationname
            FROM stockmaster INNER JOIN locstock ON stockmaster.stockid = locstock.stockid
            INNER JOIN locations ON locstock.loccode = locations.loccode
            INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
            WHERE 1=1 " . $WhereLocation . $WhereStockCat . "
            AND (locstock.quantity > 0)
            AND NOT EXISTS (SELECT * FROM salesorderdetails, salesorders WHERE stockmaster.stockid = salesorderdetails.stkcode AND (salesorders.fromstkloc = locstock.loccode) AND (salesorderdetails.orderno = salesorders.orderno) AND salesorderdetails.actualdispatchdate > '" . $FromDate . "')
            AND NOT EXISTS (SELECT * FROM stockmoves WHERE stockmoves.loccode = locstock.loccode AND stockmoves.stockid = stockmaster.stockid AND stockmoves.trandate >= '" . $FromDate . "')
            ORDER BY stockmaster.stockid";
}

// PDF Generation Branch
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    $Result = DB_query($SQL);
    $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
    $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Dormant Stock Report') . '<br />' . __('Inactivity Period') . ': ' . $_POST['NumberOfDays'] . ' ' . __('Days') . '</div>';
    $HTML .= '<table class="db-table"><thead><tr><th>Item</th><th>Loc</th><th>QOH</th><th>Units</th><th>Cost</th></tr></thead><tbody>';
    while ($Row = DB_fetch_array($Result)) {
        $loc = $Row['locationname'] ?? __('All');
        $qty = $Row['item_qty'] ?? $Row['total_qty'];
        $HTML .= '<tr><td>' . $Row['stockid'] . ' - ' . $Row['description'] . '</td><td>' . $loc . '</td><td class="number">' . $qty . '</td><td>' . $Row['units'] . '</td><td class="number">' . locale_number_format($Row['actualcost'], 2) . '</td></tr>';
    }
    $HTML .= '</tbody></table></body></html>';
    $DomPDF = new Dompdf($DomPDFOptions);
    $DomPDF->loadHtml($HTML);
    $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
    $DomPDF->render();
    $DomPDF->stream($_SESSION['DatabaseName'] . '_DormantStock_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
    exit;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-snowflake"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-search"></i> ' . __('Inquiry Criteria') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Days of Inactivity') . '</label>
                                <input type="number" name="NumberOfDays" class="db-input" value="' . (int)$_POST['NumberOfDays'] . '" min="1" required />
                                <small class="text-muted">' . __('Examine stock with zero movements for these days') . '</small>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Stock Category') . '</label>
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
                                <label class="db-label">' . __('Locations') . '</label>
                                <select name="Location[]" class="db-select" multiple style="min-height: 120px;">
                                    <option value="All" ' . (in_array('All', $_POST['Location']) ? 'selected' : '') . '>' . __('All Locations') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 ORDER BY locationname");
                                    while ($loc = DB_fetch_array($locRes)) {
                                        $sel = (in_array($loc['loccode'], $_POST['Location'])) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $loc['loccode'] . '">' . $loc['locationname'] . '</option>';
                                    }
    echo '                      </select>
                                <small class="text-muted">' . __('Hold Ctrl to select multiple') . '</small>
                            </div>

                            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="justify-content: center;">
                                    <i class="fas fa-microscope"></i> ' . __('Analyze Hub') . '
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
                        $Result = DB_query($SQL);
                        $ProbCount = 0; $TotalValueAtRisk = 0; $Categories = [];
                        $data = [];
                        
                        while ($Row = DB_fetch_array($Result)) {
                            // Secondary query for Network QOH
                            $QOHRes = DB_query("SELECT SUM(quantity) FROM locstock INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE stockid = '" . $Row['stockid'] . "'");
                            $QOHRow = DB_fetch_row($QOHRes);
                            $Row['network_qoh'] = $QOHRow[0];
                            $Row['target_qty'] = $Row['item_qty'] ?? $Row['total_qty'];
                            
                            $TotalValueAtRisk += $Row['actualcost'] * $Row['target_qty'];
                            $Categories[$Row['categoryid']] = ($Categories[$Row['categoryid']] ?? 0) + 1;
                            $ProbCount++;
                            $data[] = $Row;
                        }
                        
                        arsort($Categories);
                        $topCat = count($Categories) > 0 ? key($Categories) : 'N/A';

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-box-open"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Dormant Items') . '</span><span class="value">' . $ProbCount . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);"><i class="fas fa-money-bill-wave"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Value at Risk') . '</span><span class="value">' . locale_number_format($TotalValueAtRisk, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-tags"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Top Category') . '</span><span class="value">' . $topCat . '</span></div>
                                </div>
                              </div>';

                        if ($ProbCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Inventory Optimization List') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Item Detail') . '</th>
                                                        <th>' . __('Location') . '</th>
                                                        <th class="text-right">' . __('Loc QOH') . '</th>
                                                        <th class="text-right">' . __('Network QOH') . '</th>
                                                        <th class="text-right">' . __('Unit Cost') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                foreach ($data as $Row) {
                                                    $locName = $Row['locationname'] ?? __('All (Visible)');
                                                    echo '<tr>
                                                            <td>
                                                                <a href="' . $RootPath . '/SelectProduct.php?StockID=' . $Row['stockid'] . '" class="db-font-bold text-primary">' . $Row['stockid'] . '</a>
                                                                <div class="db-font-sm text-muted">' . $Row['description'] . '</div>
                                                            </td>
                                                            <td><span class="db-badge db-badge-secondary">' . $locName . '</span></td>
                                                            <td class="text-right db-font-semibold">' . locale_number_format($Row['target_qty'], 1) . ' </td>
                                                            <td class="text-right">' . locale_number_format($Row['network_qoh'], 1) . '</td>
                                                            <td class="text-right db-font-mono">' . locale_number_format($Row['actualcost'], 2) . '</td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="db-card" style="text-align: center; padding: 80px; background: var(--surface-alt);">
                                    <i class="fas fa-smile" style="font-size: 5rem; color: var(--success); margin-bottom: 25px;"></i>
                                    <h3>' . __('Clean Inventory!') . '</h3>
                                    <p class="text-muted">' . __('No items have been sitting dormant for over ' . (int)$_POST['NumberOfDays'] . ' days.') . '</p>
                                </div>';
                        }

                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-archive" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Dormant Stock Intelligence') . '</h2>
                                    <p>' . __('Specify your inactivity threshold on the left to identify dead stock and free up warehouse space.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
