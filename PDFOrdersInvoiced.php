<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Revenue Conversion Dashboard');
$ViewTopic = 'Sales';
$BookMark = '';

// Parameter Initialization
if (isset($_POST['FromDate'])) { $_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']); }
if (isset($_POST['ToDate'])) { $_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']); }
if (!isset($_POST['FromDate'])) { $_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d') - 30, date('y'))); }
if (!isset($_POST['ToDate'])) { $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']); }
if (!isset($_POST['CategoryID'])) { $_POST['CategoryID'] = 'All'; }
if (!isset($_POST['Location'])) { $_POST['Location'] = 'All'; }

$ShowResults = (isset($_POST['View']) || isset($_POST['PrintPDF']));

// Intelligence Strategy
if ($ShowResults) {
    $SQL = "SELECT salesorders.orderno, salesorders.debtorno, salesorders.branchcode, salesorders.customerref, salesorders.orddate, salesorders.fromstkloc, 
                   salesorderdetails.stkcode, stockmaster.description, stockmaster.units, stockmaster.decimalplaces, 
                   debtorsmaster.name, custbranch.brname, locations.locationname,
                   SUM(salesorderdetails.quantity) AS totqty,
                   SUM(salesorderdetails.qtyinvoiced) AS totqtyinvoiced
            FROM salesorders
            INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno
            INNER JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
            INNER JOIN debtorsmaster ON salesorders.debtorno=debtorsmaster.debtorno
            INNER JOIN custbranch ON custbranch.debtorno=salesorders.debtorno AND custbranch.branchcode=salesorders.branchcode
            INNER JOIN locations ON salesorders.fromstkloc=locations.loccode
            INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
            WHERE orddate >='" . FormatDateForSQL($_POST['FromDate']) . "'
            AND orddate <='" . FormatDateForSQL($_POST['ToDate']) . "'";

    if ($_POST['CategoryID'] != 'All') { $SQL .= " AND stockmaster.categoryid ='" . $_POST['CategoryID'] . "'"; }
    if ($_POST['Location'] != 'All') { $SQL .= " AND salesorders.fromstkloc ='" . $_POST['Location'] . "'"; }
    if ($_SESSION['SalesmanLogin'] != '') { $SQL .= " AND salesorders.salesperson='" . $_SESSION['SalesmanLogin'] . "'"; }

    $SQL .= " GROUP BY salesorders.orderno, salesorderdetails.stkcode
              ORDER BY salesorders.orderno, salesorderdetails.stkcode";

    $Result = DB_query($SQL);

    // PDF Generation Logic
    if (isset($_POST['PrintPDF'])) {
        if (DB_num_rows($Result) == 0) {
            include(__DIR__ . '/includes/header.php');
            prnMsg(__('No conversion data found for this selection.'), 'warn');
            echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">' . __('Back') . '</a>';
            include(__DIR__ . '/includes/footer.php');
            exit;
        }
        $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Order Invoiced Listing') . '</div>';
        $HTML .= '<table><tbody>';
        $OrderNo = 0; $totalGrand = 0;
        while ($Row = DB_fetch_array($Result)) {
            if ($Row['orderno'] != $OrderNo) {
                $HTML .= '<tr><th colspan="10" style="background:#eee;">' . __('Order') . ' #' . $Row['orderno'] . ' - ' . $Row['name'] . '</th></tr>';
                $OrderNo = $Row['orderno'];
            }
            $HTML .= '<tr><td>' . $Row['stkcode'] . '</td><td>' . $Row['description'] . '</td><td class="number">' . locale_number_format($Row['totqty'], $Row['decimalplaces']) . '</td><td class="number">' . locale_number_format($Row['totqtyinvoiced'], $Row['decimalplaces']) . '</td></tr>';
            
            // Nested transactions in PDF
            $subSQL = "SELECT systypes.typename, debtortrans.transno, debtortrans.trandate, stockmoves.price *(1-stockmoves.discountpercent) AS netprice, -stockmoves.qty AS quantity
                       FROM debtortrans INNER JOIN stockmoves ON debtortrans.type = stockmoves.type AND debtortrans.transno=stockmoves.transno INNER JOIN systypes ON debtortrans.type=systypes.typeid
                       WHERE debtortrans.order_ ='" . $OrderNo . "' AND stockmoves.stockid ='" . $Row['stkcode'] . "'";
            $subRes = DB_query($subSQL);
            while ($sRow = DB_fetch_array($subRes)) {
                $v = $sRow['netprice'] * $sRow['quantity'];
                $HTML .= '<tr style="font-size:0.8rem; color:#666;"><td></td><td>' . $sRow['typename'] . ' ' . $sRow['transno'] . '</td><td>' . ConvertSQLDate($sRow['trandate']) . '</td><td class="number">' . locale_number_format($v, 2) . '</td></tr>';
                $totalGrand += $v;
            }
        }
        $HTML .= '<tr><th colspan="3">' . __('GRAND TOTAL') . '</th><th class="number">' . locale_number_format($totalGrand, 2) . '</th></tr>';
        $HTML .= '</tbody></table></body></html>';
        $DomPDF = new Dompdf($DomPDFOptions);
        $DomPDF->loadHtml($HTML);
        $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
        $DomPDF->render();
        $DomPDF->stream($_SESSION['DatabaseName'] . '_OrdersInvoiced_' . date('Y-m-d') . '.pdf', ["Attachment" => false]);
        exit;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-file-invoice-dollar"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Strategic revenue recognition and order-to-cash lifecycle auditing') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Discovery Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-calendar-check"></i> ' . __('Invoicing Horizon') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Audit From') . '</label>
                                <input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Audit To') . '</label>
                                <input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
                            </div>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-microchip"></i> ' . __('Operations Segment') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Location') . '</label>
                                <select name="Location" class="db-select">
                                    <option value="All">' . __('All Fulfillment Centers') . '</option>';
                                    $locRes = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1");
                                    while ($l = DB_fetch_array($locRes)) {
                                        echo '<option ' . (($_POST['Location'] ?? '') == $l['loccode'] ? 'selected' : '') . ' value="' . $l['loccode'] . '">' . $l['locationname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Category') . '</label>
                                <select name="CategoryID" class="db-select">
                                    <option value="All">' . __('Global Portfolio (All)') . '</option>';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . (($_POST['CategoryID'] ?? '') == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-coins"></i> ' . __('Audit Revenue') . '
                                </button>
                                <button type="submit" name="PrintPDF" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> ' . __('Export Ledger') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $orderCount = 0; $totalInvoiced = 0; $skuLines = 0;
                        $data = []; $lastOrder = 0;
                        while ($r = DB_fetch_array($Result)) {
                            if ($r['orderno'] != $lastOrder) { $orderCount++; $lastOrder = $r['orderno']; }
                            $skuLines++;
                            $data[] = $r;
                        }

                        // We'll need a second pass for total value if we want KPIs to be precise across nested transactions
                        // But let's calculate them as we render for performance, or aggregate them once.
                        
                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-file-signature"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Orders Processed') . '</span><span class="value">' . $orderCount . '</span></div>
                                </div>
                                <div class="kpi-card-v2" id="kpi-total-val">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-hand-holding-usd"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Invoiced Portfolio') . '</span><span class="value" id="grand-total-val">...</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-cubes"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Fulfillment Density') . '</span><span class="value">' . ($orderCount > 0 ? locale_number_format($skuLines/$orderCount, 1) : '0') . '</span><small class="text-muted">' . __('Lines per Order') . '</small></div>
                                </div>
                              </div>';

                        if ($orderCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-stream"></i> ' . __('Invoicing Excellence Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Order Identity') . '</th>
                                                        <th>' . __('Customer Context') . '</th>
                                                        <th>' . __('Fulfillment Hub') . '</th>
                                                        <th class="text-right">' . __('Invoiced Value') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                
                                                $ordNo = 0; $runningGrandTotal = 0;
                                                foreach ($data as $r) {
                                                    if ($r['orderno'] != $ordNo) {
                                                        // Order Header Row
                                                        echo '<tr style="background: var(--surface-alt); border-top: 2px solid var(--border-color);">
                                                                <td><a href="' . $RootPath . '/OrderDetails.php?OrderNumber=' . $r['orderno'] . '" class="db-badge db-badge-primary" style="font-family: monospace;">#' . $r['orderno'] . '</a></td>
                                                                <td>
                                                                    <div class="db-font-bold text-primary">' . $r['name'] . '</div>
                                                                    <small class="text-muted">' . $r['customerref'] . '</small>
                                                                </td>
                                                                <td>
                                                                    <div class="db-font-medium">' . $r['locationname'] . '</div>
                                                                    <small class="text-muted">' . ConvertSQLDate($r['orddate']) . '</small>
                                                                </td>
                                                                <td class="text-right db-font-bold" id="order-total-' . $r['orderno'] . '">...</td>
                                                              </tr>
                                                              <tr>
                                                                <td colspan="4" style="padding: 0;">
                                                                    <table style="width: 100%; border-collapse: collapse; background: var(--surface);">
                                                                        <thead>
                                                                            <tr style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); background: rgba(0,0,0,0.02);">
                                                                                <th style="padding: 10px 30px;">' . __('SKU Identity') . '</th>
                                                                                <th style="padding: 10px 15px;">' . __('Fulfillment Status') . '</th>
                                                                                <th colspan="3" style="padding: 10px 15px;">' . __('Transaction Discovery Ledger') . '</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>';
                                                        $ordNo = $r['orderno']; $orderTotalValue = 0;
                                                    }
                                                    
                                                    // Item Level Row
                                                    $out = $r['totqty'] - $r['totqtyinvoiced'];
                                                    $status = ($out > 0) ? '<span class="text-danger" style="font-size: 0.75rem;">' . __('Outstanding') . ': ' . locale_number_format($out, $r['decimalplaces']) . '</span>' : '<span class="text-success" style="font-size: 0.75rem;"><i class="fas fa-check"></i> ' . __('Fulfilled') . '</span>';
                                                    
                                                    echo '<tr style="font-size: 0.85rem; border-bottom: 1px solid var(--border-color); vertical-align: top;">
                                                            <td style="padding: 15px 30px; border-right: 1px solid var(--border-color); width: 25%;">
                                                                <div class="db-font-bold text-primary">' . $r['stkcode'] . '</div>
                                                                <small class="text-muted">' . $r['description'] . '</small>
                                                            </td>
                                                            <td style="padding: 15px 15px; border-right: 1px solid var(--border-color); width: 20%;">
                                                                <div class="db-font-medium">' . locale_number_format($r['totqtyinvoiced'], $r['decimalplaces']) . ' ' . $r['units'] . '</div>
                                                                ' . $status . '
                                                            </td>
                                                            <td style="padding: 0;">
                                                                <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">';
                                                            
                                                                $subSQL = "SELECT systypes.typename, debtortrans.transno, debtortrans.trandate, stockmoves.price *(1-stockmoves.discountpercent) AS netprice, -stockmoves.qty AS quantity, stockmoves.narrative
                                                                           FROM debtortrans INNER JOIN stockmoves ON debtortrans.type = stockmoves.type AND debtortrans.transno=stockmoves.transno INNER JOIN systypes ON debtortrans.type=systypes.typeid
                                                                           WHERE debtortrans.order_ ='" . $ordNo . "' AND stockmoves.stockid ='" . $r['stkcode'] . "'";
                                                                $subRes = DB_query($subSQL);
                                                                while ($sRow = DB_fetch_array($subRes)) {
                                                                    $v = $sRow['netprice'] * $sRow['quantity'];
                                                                    $orderTotalValue += $v; $runningGrandTotal += $v;
                                                                    echo '<tr style="border-bottom: 1px dotted var(--border-color);">
                                                                            <td style="padding: 8px 15px; width: 40%;"><i class="fas fa-file-invoice" style="color: var(--text-muted); margin-right: 5px;"></i> ' . $sRow['typename'] . ' ' . $sRow['transno'] . ' <br/><small class="text-muted">' . ConvertSQLDate($sRow['trandate']) . '</small></td>
                                                                            <td style="padding: 8px 15px; width: 20%;" class="text-right">' . locale_number_format($sRow['quantity'], $r['decimalplaces']) . '</td>
                                                                            <td style="padding: 8px 15px; width: 40%;" class="text-right db-font-semibold">' . locale_number_format($v, 2) . '</td>
                                                                          </tr>';
                                                                }
                                                                if (DB_num_rows($subRes) == 0) {
                                                                    echo '<tr><td colspan="3" style="padding: 15px; color: var(--text-muted); font-style: italic;">' . __('No direct invoices found for this SKU.') . '</td></tr>';
                                                                }
                                                    echo '      </table>
                                                            </td>
                                                          </tr>';

                                                    // Update Order Total Badge using JS injection or just buffer
                                                    $peek = current($data);
                                                    if (!$peek || $peek['orderno'] != $ordNo) {
                                                        echo '          </tbody>
                                                                    </table>
                                                                </td>
                                                              </tr>';
                                                        // Inject the calculated order total into the header row
                                                        echo '<script>document.getElementById("order-total-' . $ordNo . '").innerText = "' . locale_number_format($orderTotalValue, 2) . '";</script>';
                                                    }
                                                    next($data);
                                                }
                            echo '              </tbody>
                                                <tfoot>
                                                    <tr style="background: var(--surface-alt); font-size: 1.1rem; font-weight: 800;">
                                                        <td colspan="3" class="text-right">' . __('PORTFOLIO TOTAL INVOICED') . '</td>
                                                        <td class="text-right text-primary">' . locale_number_format($runningGrandTotal, 2) . '</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                            // Final KPI update
                            echo '<script>document.getElementById("grand-total-val").innerText = "' . locale_number_format($runningGrandTotal, 0) . '";</script>';
                        }
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-coins" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Revenue Conversion Hub') . '</h2>
                                    <p>' . __('Audit revenue recognition and fulfillment lifecycle performance. Define your audit horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
