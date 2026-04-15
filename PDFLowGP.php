<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Low Gross Profit Sales');

// Parameter Initialization
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (!isset($_POST['FromDate'])) {
	$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], strtotime("-1 month", time()));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}
if (!isset($_POST['GPMin'])) {
	$_POST['GPMin'] = 15; // Default 15% threshold
}

$FromDate = FormatDateForSQL($_POST['FromDate']);
$ToDate = FormatDateForSQL($_POST['ToDate']);
$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// PDF Generation Branch
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    
    $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
    $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Low GP Sales Report') . '<br />' . __('Range') . ': ' . $_POST['FromDate'] . ' - ' . $_POST['ToDate'] . ' (Threshold: ' . $_POST['GPMin'] . '%)</div>';
    
    $SQL = "SELECT stockmaster.categoryid, stockmaster.stockid, stockmaster.description, stockmoves.transno, stockmoves.trandate, systypes.typename, stockmaster.actualcost as unitcost, stockmoves.qty, stockmoves.debtorno, stockmoves.price*(1-stockmoves.discountpercent) as sellingprice, (stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.actualcost) AS gp, debtorsmaster.name
            FROM stockmaster INNER JOIN stockmoves ON stockmaster.stockid=stockmoves.stockid
            INNER JOIN systypes ON stockmoves.type=systypes.typeid
            INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
            WHERE stockmoves.trandate >= '" . $FromDate . "' AND stockmoves.trandate <= '" . $ToDate . "'
            AND ((stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.actualcost))/(stockmoves.price*(1-stockmoves.discountpercent)) <=" . $_POST['GPMin']/100 . "
            ORDER BY stockmaster.stockid";
    $Result = DB_query($SQL);
    
    $HTML .= '<table class="db-table"><thead><tr><th>Item</th><th>Customer</th><th>Sell Price</th><th>Cost</th><th>GP %</th></tr></thead><tbody>';
    while ($Row = DB_fetch_array($Result)) {
        $gpPerc = ($Row['sellingprice'] > 0) ? ($Row['gp'] * 100) / $Row['sellingprice'] : 0;
        $HTML .= '<tr><td>' . $Row['description'] . '</td><td>' . $Row['name'] . '</td><td class="number">' . locale_number_format($Row['sellingprice'], 2) . '</td><td class="number">' . locale_number_format($Row['unitcost'], 2) . '</td><td class="number">' . locale_number_format($gpPerc, 1) . '%</td></tr>';
    }
    $HTML .= '</tbody></table></body></html>';

    $DomPDF = new Dompdf($DomPDFOptions);
    $DomPDF->loadHtml($HTML);
    $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
    $DomPDF->render();
    $DomPDF->stream($_SESSION['DatabaseName'] . '_LowGPSales_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
    exit;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-exclamation-circle text-danger"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Analysis Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-sliders-h"></i> ' . __('Risk Parameters') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period From') . '</label>
                                <input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" required />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period To') . '</label>
                                <input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" required />
                            </div>
                            
                            <div class="db-form-group">
                                <label class="db-label">' . __('GP % Threshold') . '</label>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="number" name="GPMin" class="db-input" value="' . (int)$_POST['GPMin'] . '" min="-100" max="100" />
                                    <span class="db-font-bold">%</span>
                                </div>
                                <small class="text-muted">' . __('Show sales with margin below this %') . '</small>
                            </div>

                            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="justify-content: center;">
                                    <i class="fas fa-eye"></i> ' . __('Analyze Risk') . '
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
                        $SQL = "SELECT stockmaster.categoryid, stockmaster.stockid, stockmaster.description, stockmoves.transno, stockmoves.trandate, systypes.typename, stockmaster.actualcost as unitcost, stockmoves.qty, stockmoves.debtorno, stockmoves.price*(1-stockmoves.discountpercent) as sellingprice, (stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.actualcost) AS gp, debtorsmaster.name
                                FROM stockmaster INNER JOIN stockmoves ON stockmaster.stockid=stockmoves.stockid
                                INNER JOIN systypes ON stockmoves.type=systypes.typeid
                                INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
                                WHERE stockmoves.trandate >= '" . $FromDate . "' AND stockmoves.trandate <= '" . $ToDate . "'
                                AND ((stockmoves.price*(1-stockmoves.discountpercent)) - (stockmaster.actualcost))/(stockmoves.price*(1-stockmoves.discountpercent)) <=" . $_POST['GPMin']/100 . "
                                ORDER BY stockmaster.stockid";

                        $Result = DB_query($SQL);
                        
                        $TotalAtRisk = 0; $ProbCount = 0; $TotalGP = 0;
                        $data = [];
                        while ($Row = DB_fetch_array($Result)) {
                            $TotalAtRisk += $Row['sellingprice'] * -$Row['qty'];
                            $gpPerc = ($Row['sellingprice'] > 0) ? ($Row['gp'] * 100) / $Row['sellingprice'] : 0;
                            $TotalGP += $gpPerc;
                            $ProbCount++;
                            $Row['gp_perc'] = $gpPerc;
                            $data[] = $Row;
                        }
                        $AvgGP = ($ProbCount > 0) ? $TotalGP / $ProbCount : 0;

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);"><i class="fas fa-biohazard"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Revenue at Risk') . '</span><span class="value">' . locale_number_format($TotalAtRisk, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Problem Count') . '</span><span class="value">' . $ProbCount . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-percentage"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Avg. Problem Margin') . '</span><span class="value">' . locale_number_format($AvgGP, 1) . '%</span></div>
                                </div>
                              </div>';

                        if ($ProbCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-skull-crossbones"></i> ' . __('High-Risk Detection List') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Transaction') . '</th>
                                                        <th>' . __('Affected Item') . '</th>
                                                        <th>' . __('Customer') . '</th>
                                                        <th class="text-right">' . __('Sell Price') . '</th>
                                                        <th class="text-right">' . __('GP %') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                foreach ($data as $Row) {
                                                    $severity = ($Row['gp_perc'] < 0) ? 'danger' : (($Row['gp_perc'] < 10) ? 'warning' : 'info');
                                                    echo '<tr>
                                                            <td><div class="db-font-bold">' . $Row['typename'] . '</div><small class="text-muted">#' . $Row['transno'] . '</small></td>
                                                            <td><div class="db-font-medium">' . $Row['stockid'] . '</div><small class="text-muted">' . $Row['description'] . '</small></td>
                                                            <td>' . $Row['name'] . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['sellingprice'], 2) . '</td>
                                                            <td class="text-right">
                                                                <span class="db-badge db-badge-' . $severity . '" style="min-width: 60px; text-align: center;">' . locale_number_format($Row['gp_perc'], 1) . '%</span>
                                                            </td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="db-card" style="text-align: center; padding: 80px; background: var(--surface-alt);">
                                    <i class="fas fa-check-circle" style="font-size: 5rem; color: var(--success); margin-bottom: 25px;"></i>
                                    <h3>' . __('Perfect Margins Detected!') . '</h3>
                                    <p class="text-muted">' . __('No transactions were found with gross profit below your threshold.') . '</p>
                                  </div>';
                        }

                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-microscope" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Margin Analysis Hub') . '</h2>
                                    <p>' . __('Define your target margin threshold on the left and run the "Analyze Risk" tool.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
