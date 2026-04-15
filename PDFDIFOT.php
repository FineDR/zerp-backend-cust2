<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Service Reliability Dashboard');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// Parameter Initialization
if (!isset($_POST['FromDate'])) { $_POST['FromDate'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, 0, date('y'))); }
if (!isset($_POST['ToDate'])) { $_POST['ToDate'] = date('Y-m-d'); }
if (!isset($_POST['DaysAcceptable'])) { $_POST['DaysAcceptable'] = 1; }
if (!isset($_POST['CategoryID'])) { $_POST['CategoryID'] = 'All'; }
if (!isset($_POST['Location'])) { $_POST['Location'] = 'All'; }

$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// Query Calculation Branch
if ($ShowResults) {
    $FromSQL = FormatDateForSQL($_POST['FromDate']);
    $ToSQL = FormatDateForSQL($_POST['ToDate']);
    $Threshold = (int)$_POST['DaysAcceptable'];

    // 1. Fetch Variances (Deliveries that might be late)
    $SQL = "SELECT salesorders.orderno, salesorders.deliverydate, salesorderdetails.actualdispatchdate,
                   TO_DAYS(salesorderdetails.actualdispatchdate) - TO_DAYS(salesorders.deliverydate) AS daydiff,
                   salesorderdetails.quantity, salesorderdetails.stkcode, stockmaster.description, stockmaster.decimalplaces,
                   salesorders.debtorno, salesorders.branchcode
            FROM salesorderdetails 
            INNER JOIN stockmaster ON salesorderdetails.stkcode=stockmaster.stockid
            INNER JOIN salesorders ON salesorderdetails.orderno=salesorders.orderno
            INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
            WHERE salesorders.deliverydate >='" . $FromSQL . "' AND salesorders.deliverydate <='" . $ToSQL . "'";
    
    if ($_POST['CategoryID'] != 'All') { $SQL .= " AND stockmaster.categoryid='" . $_POST['CategoryID'] . "'"; }
    if ($_POST['Location'] != 'All') { $SQL .= " AND salesorders.fromstkloc='" . $_POST['Location'] . "'"; }
    
    // We fetch all records in this range to apply the Weekend logic in PHP
    $VarResult = DB_query($SQL);
}

// PDF Export Branch (Placeholder for legacy logic integration if needed)
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    // ... Legacy PDF generation logic would go here ...
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-shipping-fast"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Delivery In Full On Time (DIFOT) Performance Audit') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-stopwatch"></i> ' . __('Service Benchmarks') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Acceptance Threshold') . '</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="number" name="DaysAcceptable" class="db-input" value="' . (int)$_POST['DaysAcceptable'] . '" min="0" max="99" style="width: 80px;" />
                                    <span class="text-muted">' . __('Days Tolerance') . '</span>
                                </div>
                                <small class="text-muted">' . __('Maximum acceptable delay for "On Time" status') . '</small>
                            </div>
                            
                            <hr style="border:0; border-top: 1px solid var(--border-color); margin: var(--space-4) 0;" />

                            <div class="db-form-group">
                                <label class="db-label">' . __('Audit Horizon') . '</label>
                                <div class="db-form-group"><label class="db-label-sm">' . __('From') . '</label><input type="date" name="FromDate" class="db-input" value="' . $_POST['FromDate'] . '" /></div>
                                <div class="db-form-group"><label class="db-label-sm">' . __('To') . '</label><input type="date" name="ToDate" class="db-input" value="' . $_POST['ToDate'] . '" /></div>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Segment Filter') . '</label>
                                <select name="CategoryID" class="db-select" style="margin-bottom: 8px;">
                                    <option value="All">' . __('All Categories') . '</option>';
                                    $catRes = DB_query("SELECT categorydescription, categoryid FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'");
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . ($_POST['CategoryID'] == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                                <select name="Location" class="db-select">
                                    <option value="All">' . __('All Fulfillment Centers') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1");
                                    while ($l = DB_fetch_array($locRes)) {
                                        echo '<option ' . ($_POST['Location'] == $l['loccode'] ? 'selected' : '') . ' value="' . $l['loccode'] . '">' . $l['locationname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-sync-alt"></i> ' . __('Audit Reliability') . '
                                </button>
                                <button type="submit" name="PrintPDF" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> ' . __('Export Audit') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $DelayedLines = 0;
                        $data = [];
                        
                        while ($Row = DB_fetch_array($VarResult)) {
                            // Weekend Logic: Monday deliveries (DayOfWeek 1) get a 2-day grace from the SQL diff
                            if (DayOfWeekFromSQLDate($Row['actualdispatchdate']) == 1) {
                                $Row['days_late'] = $Row['daydiff'] - 2;
                            } else {
                                $Row['days_late'] = $Row['daydiff'];
                            }

                            if ($Row['days_late'] > (int)$_POST['DaysAcceptable']) {
                                $DelayedLines++;
                                $data[] = $Row;
                            }
                        }

                        // Get Total Portfolio lines for the period to calculate %
                        $TotalSQL = "SELECT COUNT(salesorderdetails.orderno) FROM salesorderdetails 
                                     INNER JOIN debtortrans ON salesorderdetails.orderno=debtortrans.order_ 
                                     INNER JOIN salesorders ON salesorderdetails.orderno = salesorders.orderno 
                                     INNER JOIN stockmaster ON salesorderdetails.stkcode=stockmaster.stockid
                                     INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
                                     WHERE debtortrans.trandate >= '" . $FromSQL . "' AND debtortrans.trandate <= '" . $ToSQL . "'";
                        if ($_POST['CategoryID'] != 'All') { $TotalSQL .= " AND stockmaster.categoryid='" . $_POST['CategoryID'] . "'"; }
                        if ($_POST['Location'] != 'All') { $TotalSQL .= " AND salesorders.fromstkloc='" . $_POST['Location'] . "'"; }
                        
                        $totalRes = DB_query($TotalSQL);
                        $totalRow = DB_fetch_row($totalRes);
                        $totalLines = (int)$totalRow[0];
                        $difotPercent = ($totalLines > 0) ? (1 - ($DelayedLines / $totalLines)) * 100 : 100;

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-medal"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Global DIFOT %') . '</span><span class="value">' . locale_number_format($difotPercent, 2) . '%</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);"><i class="fas fa-clock"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Failed Benchmarks') . '</span><span class="value">' . $DelayedLines . '</span><small class="text-muted">' . __('Lines Delayed') . '</small></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-history"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Activity') . '</span><span class="value">' . $totalLines . '</span><small class="text-muted">' . __('Total Lines Analysed') . '</small></div>
                                </div>
                              </div>';

                        if ($DelayedLines > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-exclamation-triangle"></i> ' . __('Fulfillment Variance Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Order Ref') . '</th>
                                                        <th>' . __('Item Context') . '</th>
                                                        <th class="text-right">' . __('Qty') . '</th>
                                                        <th>' . __('Customer & Branch') . '</th>
                                                        <th>' . __('Dispatch') . '</th>
                                                        <th class="text-right">' . __('Delay') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                foreach ($data as $Row) {
                                                    echo '<tr>
                                                            <td><div class="db-font-bold">#' . $Row['orderno'] . '</div></td>
                                                            <td>
                                                                <div class="db-font-semibold">' . $Row['stkcode'] . '</div>
                                                                <small class="text-muted">' . $Row['description'] . '</small>
                                                            </td>
                                                            <td class="text-right">' . locale_number_format($Row['quantity'], $Row['decimalplaces']) . '</td>
                                                            <td>
                                                                <div class="db-font-medium">' . $Row['debtorno'] . '</div>
                                                                <small class="text-muted">' . $Row['branchcode'] . '</small>
                                                            </td>
                                                            <td>' . ConvertSQLDate($Row['actualdispatchdate']) . '</td>
                                                            <td class="text-right">
                                                                <span class="db-badge db-badge-danger" style="font-weight: 700;">+' . $Row['days_late'] . ' ' . __('Days') . '</span>
                                                            </td>
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
                                    <i class="fas fa-truck-loading" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Fulfillment Audit Hub') . '</h2>
                                    <p>' . __('Audit delivery reliability and service benchmarks. Define your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
