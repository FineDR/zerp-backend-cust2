<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Fulfillment Accuracy Dashboard');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// Parameter Initialization
if (!isset($_POST['FromDate'])) { $_POST['FromDate'] = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, 0, date('y'))); }
if (!isset($_POST['ToDate'])) { $_POST['ToDate'] = date('Y-m-d'); }
if (!isset($_POST['CategoryID'])) { $_POST['CategoryID'] = 'All'; }
if (!isset($_POST['Location'])) { $_POST['Location'] = 'All'; }

$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// Query Calculation Branch
if ($ShowResults) {
    $FromSQL = FormatDateForSQL($_POST['FromDate']);
    $ToSQL = FormatDateForSQL($_POST['ToDate']);

    // Consolidation of legacy SQL branches
    $SQL = "SELECT invoiceno, orderdeliverydifferenceslog.orderno, orderdeliverydifferenceslog.stockid, stockmaster.description, stockmaster.decimalplaces, quantitydiff, trandate, orderdeliverydifferenceslog.debtorno, orderdeliverydifferenceslog.branch
            FROM orderdeliverydifferenceslog
            INNER JOIN stockmaster ON orderdeliverydifferenceslog.stockid=stockmaster.stockid
            INNER JOIN salesorders ON orderdeliverydifferenceslog.orderno = salesorders.orderno
            INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
            INNER JOIN debtortrans ON orderdeliverydifferenceslog.invoiceno=debtortrans.transno AND debtortrans.type=10
            WHERE trandate >= '" . $FromSQL . "' AND trandate <= '" . $ToSQL . "'";

    if ($_POST['CategoryID'] != 'All') { $SQL .= " AND stockmaster.categoryid='" . $_POST['CategoryID'] . "'"; }
    if ($_POST['Location'] != 'All') { $SQL .= " AND salesorders.fromstkloc='" . $_POST['Location'] . "'"; }
    if ($_SESSION['SalesmanLogin'] != '') { $SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'"; }

    $Result = DB_query($SQL);
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
                <i class="fas fa-balance-scale"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Audit of quantity variances between Sales Orders and Deliveries') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-history"></i> ' . __('Audit Horizons') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Date Range') . '</label>
                                <div class="db-form-group"><label class="db-label-sm">' . __('From') . '</label><input type="date" name="FromDate" class="db-input" value="' . $_POST['FromDate'] . '" /></div>
                                <div class="db-form-group"><label class="db-label-sm">' . __('To') . '</label><input type="date" name="ToDate" class="db-input" value="' . $_POST['ToDate'] . '" /></div>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Segments') . '</label>
                                <select name="CategoryID" class="db-select" style="margin-bottom: 8px;">
                                    <option value="All">' . __('All Categories') . '</option>';
                                    $catRes = DB_query("SELECT categorydescription, categoryid FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'");
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . ($_POST['CategoryID'] == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                                <select name="Location" class="db-select">
                                    <option value="All">' . __('All Locations') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1");
                                    while ($l = DB_fetch_array($locRes)) {
                                        echo '<option ' . ($_POST['Location'] == $l['loccode'] ? 'selected' : '') . ' value="' . $l['loccode'] . '">' . $l['locationname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-microscope"></i> ' . __('Audit Variances') . '
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
                        $VarianceCount = 0;
                        $data = [];
                        while ($Row = DB_fetch_array($Result)) {
                            $VarianceCount++;
                            $data[] = $Row;
                        }

                        // Get Total Portfolio lines for Accuracy calculation
                        $TotalSQL = "SELECT COUNT(salesorderdetails.orderno) FROM salesorderdetails 
                                     INNER JOIN debtortrans ON salesorderdetails.orderno=debtortrans.order_ 
                                     INNER JOIN salesorders ON salesorderdetails.orderno = salesorders.orderno 
                                     INNER JOIN stockmaster ON salesorderdetails.stkcode=stockmaster.stockid
                                     INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
                                     WHERE debtortrans.trandate >= '" . $FromSQL . "' AND debtortrans.trandate <= '" . $ToSQL . "' AND debtortrans.type=10";
                        if ($_POST['CategoryID'] != 'All') { $TotalSQL .= " AND stockmaster.categoryid='" . $_POST['CategoryID'] . "'"; }
                        if ($_POST['Location'] != 'All') { $TotalSQL .= " AND salesorders.fromstkloc='" . $_POST['Location'] . "'"; }
                        if ($_SESSION['SalesmanLogin'] != '') { $TotalSQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'"; }
                        
                        $countRes = DB_query($TotalSQL);
                        $countRow = DB_fetch_row($countRes);
                        $totalLines = (int)$countRow[0];
                        $accuracyPercent = ($totalLines > 0) ? (1 - ($VarianceCount / $totalLines)) * 100 : 100;

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-check-double"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Fulfillment Accuracy') . '</span><span class="value">' . locale_number_format($accuracyPercent, 2) . '%</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);"><i class="fas fa-exclamation-circle"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Variance Detections') . '</span><span class="value">' . $VarianceCount . '</span><small class="text-muted">' . __('Quantity Delta Lines') . '</small></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-database"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Network Throughput') . '</span><span class="value">' . $totalLines . '</span><small class="text-muted">' . __('Total Lines Analysed') . '</small></div>
                                </div>
                              </div>';

                        if ($VarianceCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-clipboard-list"></i> ' . __('Quantity Variance Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Document Ref') . '</th>
                                                        <th>' . __('Item Context') . '</th>
                                                        <th class="text-right">' . __('Qty Delta') . '</th>
                                                        <th>' . __('Customer Context') . '</th>
                                                        <th>' . __('Invoice Date') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                foreach ($data as $Row) {
                                                    echo '<tr>
                                                            <td>
                                                                <div class="db-font-bold">INV #' . $Row['invoiceno'] . '</div>
                                                                <small class="text-muted">' . __('Order') . ': #' . $Row['orderno'] . '</small>
                                                            </td>
                                                            <td>
                                                                <div class="db-font-semibold">' . $Row['stockid'] . '</div>
                                                                <small class="text-muted">' . $Row['description'] . '</small>
                                                            </td>
                                                            <td class="text-right">
                                                                <span class="db-badge db-badge-danger" style="font-weight: 700;">' . locale_number_format($Row['quantitydiff'], $Row['decimalplaces']) . '</span>
                                                            </td>
                                                            <td>
                                                                <div class="db-font-medium">' . $Row['debtorno'] . '</div>
                                                                <small class="text-muted">' . $Row['branch'] . '</small>
                                                            </td>
                                                            <td>' . ConvertSQLDate($Row['trandate']) . '</td>
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
                                    <i class="fas fa-balance-scale-right" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Accuracy Audit Hub') . '</h2>
                                    <p>' . __('Audit Fulfillment accuracy and quantity variances. Define your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
