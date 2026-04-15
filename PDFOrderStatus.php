<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$Title = __('Fulfillment Intelligence Dashboard');
$ViewTopic = 'Sales';
$BookMark = '';

// Parameter Initialization
if (isset($_POST['FromDate'])) { $_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']); }
if (isset($_POST['ToDate'])) { $_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']); }
if (!isset($_POST['FromDate'])) { $_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d') - 30, date('y'))); }
if (!isset($_POST['ToDate'])) { $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']); }
if (!isset($_POST['CategoryID'])) { $_POST['CategoryID'] = 'All'; }
if (!isset($_POST['Location'])) { $_POST['Location'] = 'All'; }
if (!isset($_POST['BackOrders'])) { $_POST['BackOrders'] = 'No'; }

$ShowResults = (isset($_POST['View']) || isset($_POST['PrintPDF']));

// Query Strategy
if ($ShowResults) {
    $SQL = "SELECT salesorders.orderno, salesorders.debtorno, salesorders.branchcode, salesorders.customerref, salesorders.orddate, salesorders.fromstkloc, salesorders.printedpackingslip, salesorders.datepackingslipprinted,
                   salesorderdetails.stkcode, stockmaster.description, stockmaster.units, stockmaster.decimalplaces, salesorderdetails.quantity, salesorderdetails.qtyinvoiced, salesorderdetails.completed, 
                   debtorsmaster.name, custbranch.brname, locations.locationname
            FROM salesorders
            INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno
            INNER JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
            INNER JOIN debtorsmaster ON salesorders.debtorno=debtorsmaster.debtorno
            INNER JOIN custbranch ON custbranch.debtorno=salesorders.debtorno AND custbranch.branchcode=salesorders.branchcode
            INNER JOIN locations ON salesorders.fromstkloc=locations.loccode
            INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
            WHERE salesorders.orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
            AND salesorders.orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'
            AND salesorders.quotation=0";

    if ($_POST['CategoryID'] != 'All') { $SQL .= " AND stockmaster.categoryid ='" . $_POST['CategoryID'] . "'"; }
    if ($_POST['Location'] != 'All') { $SQL .= " AND salesorders.fromstkloc ='" . $_POST['Location'] . "'"; }
    if ($_POST['BackOrders'] == 'Yes') { $SQL .= " AND salesorderdetails.quantity-salesorderdetails.qtyinvoiced >0"; }
    if ($_SESSION['SalesmanLogin'] != '') { $SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'"; }

    $SQL .= " ORDER BY salesorders.orderno, salesorderdetails.stkcode";
    $Result = DB_query($SQL);

    // PDF Generation Logic
    if (isset($_POST['PrintPDF'])) {
        if (DB_num_rows($Result) == 0) {
            include(__DIR__ . '/includes/header.php');
            prnMsg(__('No fulfillment data found for this selection.'), 'warn');
            echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">' . __('Back') . '</a>';
            include(__DIR__ . '/includes/footer.php');
            exit;
        }
        $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Order Status Listing') . '</div>';
        $HTML .= '<table><tbody>';
        $OrderNo = 0;
        while ($Row = DB_fetch_array($Result)) {
            if ($Row['orderno'] != $OrderNo) {
                $HTML .= '<tr style="background:#eee;"><th>' . __('Order') . '</th><th>' . __('Customer') . '</th><th>' . __('Date') . '</th><th>' . __('Location') . '</th><th>' . __('Status') . '</th></tr>';
                $status = ($Row['printedpackingslip'] == 1) ? __('Printed') . ' ' . ConvertSQLDate($Row['datepackingslipprinted']) : __('Not printed');
                $HTML .= '<tr><td>' . $Row['orderno'] . '</td><td>' . $Row['name'] . '</td><td>' . ConvertSQLDate($Row['orddate']) . '</td><td>' . $Row['locationname'] . '</td><td>' . $status . '</td></tr>';
                $HTML .= '<tr><th>' . __('SKU') . '</th><th>' . __('Description') . '</th><th>' . __('Ordered') . '</th><th>' . __('Invoiced') . '</th><th>' . __('Outstanding') . '</th></tr>';
                $OrderNo = $Row['orderno'];
            }
            $out = ($Row['quantity'] > $Row['qtyinvoiced']) ? locale_number_format($Row['quantity']-$Row['qtyinvoiced'], $Row['decimalplaces']) : __('Complete');
            $HTML .= '<tr><td>' . $Row['stkcode'] . '</td><td>' . $Row['description'] . '</td><td>' . locale_number_format($Row['quantity'], $Row['decimalplaces']) . '</td><td>' . locale_number_format($Row['qtyinvoiced'], $Row['decimalplaces']) . '</td><td>' . $out . '</td></tr>';
        }
        $HTML .= '</tbody></table></body></html>';
        $DomPDF = new Dompdf($DomPDFOptions);
        $DomPDF->loadHtml($HTML);
        $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
        $DomPDF->render();
        $DomPDF->stream($_SESSION['DatabaseName'] . '_OrderStatus_' . date('Y-m-d') . '.pdf', ["Attachment" => false]);
        exit;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-truck-loading"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Real-time fulfillment visibility and order lifecycle management') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-calendar-alt"></i> ' . __('Audit Horizon') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period From') . '</label>
                                <input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period To') . '</label>
                                <input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
                            </div>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Operations Segment') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Location') . '</label>
                                <select name="Location" class="db-select">
                                    <option value="All">' . __('Global Grid (All)') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1");
                                    while ($l = DB_fetch_array($locRes)) {
                                        echo '<option ' . (($_POST['Location'] ?? '') == $l['loccode'] ? 'selected' : '') . ' value="' . $l['loccode'] . '">' . $l['locationname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Category') . '</label>
                                <select name="CategoryID" class="db-select">
                                    <option value="All">' . __('All Categories') . '</option>';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype<>'D' AND stocktype<>'L'");
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . (($_POST['CategoryID'] ?? '') == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group" style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="BackOrders" value="Yes" ' . (($_POST['BackOrders'] ?? '') == 'Yes' ? 'checked' : '') . ' id="boCheck" class="db-checkbox" />
                                <label class="db-label-sm" for="boCheck">' . __('Isolate Back Orders Only') . '</label>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-search-dollar"></i> ' . __('Audit Fulfillment') . '
                                </button>
                                <button type="submit" name="PrintPDF" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> ' . __('Generate PDF') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $orderCount = 0; $outstandingLines = 0; $totalValue = 0;
                        $data = []; $lastOrder = 0;
                        while ($r = DB_fetch_array($Result)) {
                            if ($r['orderno'] != $lastOrder) { $orderCount++; $lastOrder = $r['orderno']; }
                            if ($r['quantity'] > $r['qtyinvoiced']) { $outstandingLines++; }
                            $data[] = $r;
                        }

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Active Volume') . '</span><span class="value">' . $orderCount . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-exclamation-circle"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Backlog Lines') . '</span><span class="value">' . $outstandingLines . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-check-circle"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Audit Health') . '</span><span class="value">' . ($orderCount > 0 ? locale_number_format(100 - ($outstandingLines/$orderCount*10), 0) . '%' : '100%') . '</span><small class=' . 'text-muted' . '>' . __('Fulfillment Rate') . '</small></div>
                                </div>
                              </div>';

                        if ($orderCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-layer-group"></i> ' . __('Order Excellence Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Order Identity') . '</th>
                                                        <th>' . __('Customer Context') . '</th>
                                                        <th>' . __('Fulfillment Origin') . '</th>
                                                        <th>' . __('Audit Status') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                
                                                $ordNo = 0;
                                                foreach ($data as $r) {
                                                    if ($r['orderno'] != $ordNo) {
                                                        // Order Header Row
                                                        $slip = ($r['printedpackingslip'] == 1) ? '<span class="db-badge db-badge-success">' . __('Printed') . '</span>' : '<span class="db-badge db-badge-warning">' . __('Pending') . '</span>';
                                                        echo '<tr style="background: var(--surface-alt); border-top: 2px solid var(--border-color);">
                                                                <td><a href="' . $RootPath . '/OrderDetails.php?OrderNumber=' . $r['orderno'] . '" class="db-badge db-badge-primary" style="font-family: monospace;">#' . $r['orderno'] . '</a></td>
                                                                <td>
                                                                    <div class="db-font-bold text-primary">' . $r['name'] . '</div>
                                                                    <small class="text-muted">' . $r['brname'] . '</small>
                                                                </td>
                                                                <td>
                                                                    <div class="db-font-medium">' . $r['locationname'] . '</div>
                                                                    <small class="text-muted">' . ConvertSQLDate($r['orddate']) . '</small>
                                                                </td>
                                                                <td>' . $slip . '</td>
                                                              </tr>
                                                              <tr>
                                                                <td colspan="4" style="padding: 0;">
                                                                    <table style="width: 100%; border-collapse: collapse; background: var(--surface);">
                                                                        <thead>
                                                                            <tr style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); background: rgba(0,0,0,0.02);">
                                                                                <th style="padding: 8px 30px;">' . __('Item SKU') . '</th>
                                                                                <th style="padding: 8px 15px;">' . __('Description') . '</th>
                                                                                <th class="text-right" style="padding: 8px 15px;">' . __('Ordered') . '</th>
                                                                                <th class="text-right" style="padding: 8px 15px;">' . __('Invoiced') . '</th>
                                                                                <th class="text-right" style="padding: 8px 30px;">' . __('Balance') . '</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>';
                                                        $ordNo = $r['orderno'];
                                                    }
                                                    
                                                    $out = $r['quantity'] - $r['qtyinvoiced'];
                                                    $outBadge = ($out > 0) ? '<span class="db-badge db-badge-danger">' . locale_number_format($out, $r['decimalplaces']) . '</span>' : '<span class="db-badge db-badge-success"><i class="fas fa-check"></i></span>';
                                                    
                                                    echo '<tr style="font-size: 0.85rem; border-bottom: 1px solid var(--border-color);">
                                                            <td style="padding: 8px 30px; font-family: monospace; color: var(--primary);">' . $r['stkcode'] . '</td>
                                                            <td style="padding: 8px 15px;">' . $r['description'] . ' <small class="text-muted">(' . $r['units'] . ')</small></td>
                                                            <td class="text-right" style="padding: 8px 15px;">' . locale_number_format($r['quantity'], $r['decimalplaces']) . '</td>
                                                            <td class="text-right" style="padding: 8px 15px;">' . locale_number_format($r['qtyinvoiced'], $r['decimalplaces']) . '</td>
                                                            <td class="text-right" style="padding: 8px 30px;">' . $outBadge . '</td>
                                                          </tr>';

                                                    // Peek ahead to see if we need to close the internal table
                                                    $next = current($data);
                                                    if (!$next || ($next && $next['orderno'] != $ordNo)) {
                                                        echo '          </tbody>
                                                                    </table>
                                                                </td>
                                                              </tr>';
                                                    }
                                                    next($data);
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
                                    <i class="fas fa-clipboard-check" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Fulfillment Intelligence Hub') . '</h2>
                                    <p>' . __('Audit order status, backlogs, and warehouse fulfillment performance. Define your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
