<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Sell Through Support Claims Report');

// Parameter Initialization
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (!isset($_POST['FromDate'])) {
	$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], strtotime("-1 month", time()));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

$FromDate = FormatDateForSQL($_POST['FromDate']);
$ToDate = FormatDateForSQL($_POST['ToDate']);
$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// PDF Generation Branch
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    
    $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
    $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Sell Through Support Claims') . '<br />' . __('Range') . ': ' . $_POST['FromDate'] . ' - ' . $_POST['ToDate'] . '</div>';
    
    $SQL = "SELECT suppliers.suppname, suppliers.currcode, currencies.decimalplaces as currdecimalplaces, stockmaster.stockid, stockmaster.description, stockmoves.transno, stockmoves.trandate, systypes.typename, stockmoves.qty, stockmoves.debtorno, debtorsmaster.name, stockmoves.price*(1-stockmoves.discountpercent) as sellingprice, purchdata.price as fxcost, sellthroughsupport.rebatepercent, sellthroughsupport.rebateamount
            FROM stockmaster INNER JOIN stockmoves ON stockmaster.stockid=stockmoves.stockid
            INNER JOIN systypes ON stockmoves.type=systypes.typeid
            INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
            INNER JOIN purchdata ON purchdata.stockid = stockmaster.stockid
            INNER JOIN suppliers ON suppliers.supplierid = purchdata.supplierno
            INNER JOIN sellthroughsupport ON sellthroughsupport.supplierno=suppliers.supplierid
            INNER JOIN currencies ON currencies.currabrev=suppliers.currcode
            WHERE stockmoves.trandate >= '" . $FromDate . "' AND stockmoves.trandate <= '" . $ToDate . "'
            AND sellthroughsupport.effectivefrom <= stockmoves.trandate AND sellthroughsupport.effectiveto >= stockmoves.trandate
            AND (stockmoves.type=10 OR stockmoves.type=11)
            AND (sellthroughsupport.stockid=stockmoves.stockid OR sellthroughsupport.categoryid=stockmaster.categoryid)
            AND (sellthroughsupport.debtorno=stockmoves.debtorno OR sellthroughsupport.debtorno='')
            ORDER BY suppliers.suppname";

    $Result = DB_query($SQL);
    $HTML .= '<table class="db-table"><thead><tr><th>Supplier</th><th>Item</th><th>Customer</th><th>Claim Amount</th></tr></thead><tbody>';
    
    $GrandTotal = 0;
    while ($Row = DB_fetch_array($Result)) {
        $ClaimAmount = (($Row['fxcost'] * $Row['rebatepercent']) + $Row['rebateamount']) * -$Row['qty'];
        $HTML .= '<tr><td>' . $Row['suppname'] . '</td><td>' . $Row['description'] . '</td><td>' . $Row['name'] . '</td><td class="number">' . locale_number_format($ClaimAmount, 2) . '</td></tr>';
        $GrandTotal += $ClaimAmount;
    }
    $HTML .= '<tr class="total_row"><td colspan="3">'.__('GRAND TOTAL').'</td><td class="number">'.locale_number_format($GrandTotal,2).'</td></tr></tbody></table></body></html>';

    $DomPDF = new Dompdf($DomPDFOptions);
    $DomPDF->loadHtml($HTML);
    $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
    $DomPDF->render();
    $DomPDF->stream($_SESSION['DatabaseName'] . '_SupportClaim_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
    exit;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-file-invoice-dollar"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Claim Period') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Sales Made From') . '</label>
                                <input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" required />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Sales Made To') . '</label>
                                <input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" required />
                            </div>

                            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="justify-content: center;">
                                    <i class="fas fa-eye"></i> ' . __('View Claims') . '
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
                        $SQL = "SELECT suppliers.suppname, suppliers.currcode, currencies.decimalplaces as currdecimalplaces, stockmaster.stockid, stockmaster.description, stockmoves.transno, stockmoves.trandate, systypes.typename, stockmoves.qty, stockmoves.debtorno, debtorsmaster.name, stockmoves.price*(1-stockmoves.discountpercent) as sellingprice, purchdata.price as fxcost, sellthroughsupport.rebatepercent, sellthroughsupport.rebateamount
                                FROM stockmaster INNER JOIN stockmoves ON stockmaster.stockid=stockmoves.stockid
                                INNER JOIN systypes ON stockmoves.type=systypes.typeid
                                INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
                                INNER JOIN purchdata ON purchdata.stockid = stockmaster.stockid
                                INNER JOIN suppliers ON suppliers.supplierid = purchdata.supplierno
                                INNER JOIN sellthroughsupport ON sellthroughsupport.supplierno=suppliers.supplierid
                                INNER JOIN currencies ON currencies.currabrev=suppliers.currcode
                                WHERE stockmoves.trandate >= '" . $FromDate . "' AND stockmoves.trandate <= '" . $ToDate . "'
                                AND sellthroughsupport.effectivefrom <= stockmoves.trandate AND sellthroughsupport.effectiveto >= stockmoves.trandate
                                AND (stockmoves.type=10 OR stockmoves.type=11)
                                AND (sellthroughsupport.stockid=stockmoves.stockid OR sellthroughsupport.categoryid=stockmaster.categoryid)
                                AND (sellthroughsupport.debtorno=stockmoves.debtorno OR sellthroughsupport.debtorno='')
                                ORDER BY suppliers.suppname";

                        $Result = DB_query($SQL);
                        
                        // Pass 1 for KPIs
                        $TotalClaimValue = 0; $TotalQty = 0; $Suppliers = [];
                        $data = [];
                        while ($Row = DB_fetch_array($Result)) {
                            $claim = (($Row['fxcost'] * $Row['rebatepercent']) + $Row['rebateamount']) * -$Row['qty'];
                            $TotalClaimValue += $claim;
                            $TotalQty += -$Row['qty'];
                            $Suppliers[$Row['suppname']] = true;
                            $Row['calculated_claim'] = $claim;
                            $data[] = $Row;
                        }

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-hand-holding-usd"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Claim') . '</span><span class="value">' . locale_number_format($TotalClaimValue, 2) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-cubes"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Units') . '</span><span class="value">' . $TotalQty . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-truck-loading"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Suppliers') . '</span><span class="value">' . count($Suppliers) . '</span></div>
                                </div>
                              </div>';

                        if (count($data) > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-clipboard-list"></i> ' . __('Validated Claim Portfolio') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Transaction') . '</th>
                                                        <th>' . __('Item Detail') . '</th>
                                                        <th>' . __('Customer') . '</th>
                                                        <th class="text-right">' . __('Qty') . '</th>
                                                        <th class="text-right">' . __('Claim (Base)') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                $lastSupp = '';
                                                $suppTotal = 0;
                                                foreach ($data as $Row) {
                                                    if ($Row['suppname'] != $lastSupp) {
                                                        if ($lastSupp != '') {
                                                            echo '<tr style="background: var(--db-bg-workspace);"><td colspan="4" class="text-right db-font-bold">' . __('Subtotal for') . ' ' . $lastSupp . ':</td><td class="text-right db-font-bold">' . locale_number_format($suppTotal, 2) . '</td></tr>';
                                                        }
                                                        echo '<tr style="background: var(--surface-alt);"><td colspan="5" class="db-font-bold text-primary"><i class="fas fa-truck"></i> ' . $Row['suppname'] . ' <small class="text-muted">(' . $Row['currcode'] . ')</small></td></tr>';
                                                        $lastSupp = $Row['suppname'];
                                                        $suppTotal = 0;
                                                    }
                                                    echo '<tr>
                                                            <td><div class="db-font-semibold">' . $Row['typename'] . '</div><small class="text-muted">#' . $Row['transno'] . '</small></td>
                                                            <td><div class="db-font-medium">' . $Row['stockid'] . '</div><small class="text-muted">' . $Row['description'] . '</small></td>
                                                            <td>' . $Row['name'] . '</td>
                                                            <td class="text-right">' . locale_number_format(-$Row['qty']) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['calculated_claim'], $Row['currdecimalplaces']) . '</td>
                                                          </tr>';
                                                    $suppTotal += $Row['calculated_claim'];
                                                }
                                                // Final subtotal
                                                echo '<tr style="background: var(--db-bg-workspace);"><td colspan="4" class="text-right db-font-bold">' . __('Subtotal for') . ' ' . $lastSupp . ':</td><td class="text-right db-font-bold">' . locale_number_format($suppTotal, 2) . '</td></tr>';
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="db-card" style="text-align: center; padding: 60px;">
                                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning); margin-bottom: 20px;"></i>
                                    <h3>' . __('No claimable support items found.') . '</h3>
                                    <p class="text-muted">' . __('Please verify your rebate agreements and date ranges.') . '</p>
                                  </div>';
                        }

                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-search-dollar" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Claim Validation Hub') . '</h2>
                                    <p>' . __('Specify the sales period on the left to calculate and validate support claims.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
