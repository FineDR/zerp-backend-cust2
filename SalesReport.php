<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;

$Title = __('Sales to Customers');
$ViewTopic = 'Sales';
$BookMark = 'SalesReport';

// Parameter Initialization
if (isset($_POST['PeriodFrom'])){$_POST['PeriodFrom'] = ConvertSQLDate($_POST['PeriodFrom']);}
if (isset($_POST['PeriodTo'])){$_POST['PeriodTo'] = ConvertSQLDate($_POST['PeriodTo']);}

if (!isset($_POST['PeriodFrom'])) {
	$_POST['PeriodFrom'] = date($_SESSION['DefaultDateFormat'], strtotime("-1 month", time()));
}
if (!isset($_POST['PeriodTo'])) {
	$_POST['PeriodTo'] = date($_SESSION['DefaultDateFormat']);
}

$PeriodFrom = FormatDateForSQL($_POST['PeriodFrom']);
$PeriodTo = FormatDateForSQL($_POST['PeriodTo']);
$ShowDetails = isset($_POST['ShowDetails']);
$ShowResults = isset($_POST['View']) || isset($_POST['PrintPDF']);

// PDF Generation Branch
if (isset($_POST['PrintPDF'])) {
    include(__DIR__ . '/includes/SetDomPDFOptions.php');
    
    $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
    $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Sales Report') . '<br />' . __('Range') . ': ' . $_POST['PeriodFrom'] . ' - ' . $_POST['PeriodTo'] . '</div>';
    $HTML .= '<table class="db-table">';
    $HTML .= '<thead><tr><th>' . __('Customer') . '</th><th>' . __('Amount') . '</th><th>' . __('Tax') . '</th><th>' . __('Total') . '</th><th>' . __('GL Amount') . '</th><th>' . __('GL Tax') . '</th><th>' . __('GL Total') . '</th></tr></thead><tbody>';

    $SQL = "SELECT debtortrans.debtorno, debtorsmaster.name, debtorsmaster.currcode, debtortrans.trandate, debtortrans.reference, debtortrans.transno, debtortrans.ovamount, debtortrans.ovgst, debtortrans.rate
            FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno
            WHERE debtortrans.trandate>='" . $PeriodFrom . "' AND debtortrans.trandate<='" . $PeriodTo . "' AND debtortrans.type=10 ORDER BY debtortrans.debtorno, debtortrans.trandate";
    $Result = DB_query($SQL);
    
    $TotalGlAmount = 0; $TotalGlTax = 0;
    while ($MyRow = DB_fetch_array($Result)) {
        $GlAmount = $MyRow['ovamount']/$MyRow['rate'];
        $GlTax = $MyRow['ovgst']/$MyRow['rate'];
        $HTML .= '<tr><td>' . $MyRow['name'] . '</td><td class="number">' . locale_number_format($MyRow['ovamount'], 2) . '</td><td class="number">' . locale_number_format($MyRow['ovgst'], 2) . '</td><td class="number">' . locale_number_format($MyRow['ovamount']+$MyRow['ovgst'], 2) . '</td><td class="number">' . locale_number_format($GlAmount, 2) . '</td><td class="number">' . locale_number_format($GlTax, 2) . '</td><td class="number">' . locale_number_format($GlAmount+$GlTax, 2) . '</td></tr>';
        $TotalGlAmount += $GlAmount; $TotalGlTax += $GlTax;
    }
    $HTML .= '<tr class="total_row"><td colspan="4">'.__('GRAND TOTAL').'</td><td class="number">'.locale_number_format($TotalGlAmount,2).'</td><td class="number">'.locale_number_format($TotalGlTax,2).'</td><td class="number">'.locale_number_format($TotalGlAmount+$TotalGlTax,2).'</td></tr>';
    $HTML .= '</tbody></table></body></html>';

    $DomPDF = new Dompdf($DomPDFOptions);
    $DomPDF->loadHtml($HTML);
    $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
    $DomPDF->render();
    $DomPDF->stream($_SESSION['DatabaseName'] . '_SalesReport_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
    exit;
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-chart-bar"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-calendar-alt"></i> ' . __('Report Range') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period From') . '</label>
                                <input type="date" name="PeriodFrom" class="db-input" value="' . FormatDateForSQL($_POST['PeriodFrom']) . '" required />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period To') . '</label>
                                <input type="date" name="PeriodTo" class="db-input" value="' . FormatDateForSQL($_POST['PeriodTo']) . '" required />
                            </div>
                            
                            <div class="db-form-group">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="ShowDetails" ' . ($ShowDetails ? 'checked' : '') . ' />
                                    <span style="font-size: 0.9rem;">' . __('Show Invoices') . '</span>
                                </label>
                            </div>

                            <div style="margin-top: 30px; display: flex; flex-direction: column; gap: 10px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="justify-content: center;">
                                    <i class="fas fa-eye"></i> ' . __('View Report') . '
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
                        // Core Data Loading
                        include(__DIR__ . '/includes/CurrenciesArray.php');
                        
                        $TotalGlAmount = 0; $TotalGlTax = 0;
                        if ($ShowDetails) {
                            $SQL = "SELECT debtortrans.debtorno, debtorsmaster.name, debtorsmaster.currcode, debtortrans.trandate, debtortrans.reference, debtortrans.transno, debtortrans.ovamount, debtortrans.ovgst, debtortrans.rate
                                    FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno
                                    WHERE debtortrans.trandate>='" . $PeriodFrom . "' AND debtortrans.trandate<='" . $PeriodTo . "' AND debtortrans.type=10 ORDER BY debtortrans.debtorno, debtortrans.trandate";
                        } else {
                            $SQL = "SELECT debtortrans.debtorno, debtorsmaster.name, debtorsmaster.currcode, SUM(debtortrans.ovamount) AS CustomerOvAmount, SUM(debtortrans.ovgst) AS CustomerOvTax, SUM(debtortrans.ovamount/debtortrans.rate) AS CustomerGlAmount, SUM(debtortrans.ovgst/debtortrans.rate) AS CustomerGlTax
                                    FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno
                                    WHERE debtortrans.trandate>='" . $PeriodFrom . "' AND debtortrans.trandate<='" . $PeriodTo . "' AND debtortrans.type=10 GROUP BY debtortrans.debtorno ORDER BY debtortrans.debtorno";
                        }
                        $Result = DB_query($SQL);
                        
                        // First pass for KPIs
                        if ($ShowDetails) {
                            $resKpi = DB_query($SQL);
                            while($kRow = DB_fetch_array($resKpi)) {
                                $TotalGlAmount += $kRow['ovamount']/$kRow['rate'];
                                $TotalGlTax += $kRow['ovgst']/$kRow['rate'];
                            }
                        } else {
                            $resKpi = DB_query($SQL);
                            while($kRow = DB_fetch_array($resKpi)) {
                                $TotalGlAmount += $kRow['CustomerGlAmount'];
                                $TotalGlTax += $kRow['CustomerGlTax'];
                            }
                        }

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-coins"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Net Sales') . '</span><span class="value">' . locale_number_format($TotalGlAmount, 2) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-percentage"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Tax Total') . '</span><span class="value">' . locale_number_format($TotalGlTax, 2) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-money-check-alt"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Grand Total') . '</span><span class="value">' . locale_number_format($TotalGlAmount+$TotalGlTax, 2) . '</span></div>
                                </div>
                              </div>';

                        echo '<div class="db-card">
                                <div class="db-card-header"><div class="db-card-title"><i class="fas fa-list-ol"></i> ' . __('Transaction Register') . '</div></div>
                                <div class="db-card-body p-0">
                                    <div class="db-table-wrapper">
                                        <table class="db-table">
                                            <thead>
                                                <tr>
                                                    ' . ($ShowDetails ? '<th>' . __('Date') . '</th><th>' . __('Ref #') . '</th>' : '<th>' . __('Customer Code') . '</th>') . '
                                                    <th>' . __('Customer Name') . '</th>
                                                    <th class="text-right">' . __('Amount') . '</th>
                                                    <th class="text-right">' . __('Tax') . '</th>
                                                    <th class="text-right">' . __('Total (Base)') . '</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                            
                                            if ($ShowDetails) {
                                                $lastCust = '';
                                                while ($Row = DB_fetch_array($Result)) {
                                                    if ($Row['debtorno'] != $lastCust) {
                                                        echo '<tr style="background: var(--surface-alt);"><td colspan="6" class="db-font-bold text-primary">' . $Row['debtorno'] . ' - ' . $Row['name'] . ' <small class="text-muted">(' . $Row['currcode'] . ')</small></td></tr>';
                                                        $lastCust = $Row['debtorno'];
                                                    }
                                                    $GlNet = $Row['ovamount']/$Row['rate'];
                                                    $GlTax = $Row['ovgst']/$Row['rate'];
                                                    echo '<tr>
                                                            <td>' . ConvertSQLDate($Row['trandate']) . '</td>
                                                            <td class="db-font-mono">' . $Row['transno'] . '</td>
                                                            <td><small>' . $Row['reference'] . '</small></td>
                                                            <td class="text-right">' . locale_number_format($Row['ovamount'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['ovgst'], 2) . '</td>
                                                            <td class="text-right db-font-bold"> ' . locale_number_format($GlNet + $GlTax, 2) . '</td>
                                                          </tr>';
                                                }
                                            } else {
                                                while ($Row = DB_fetch_array($Result)) {
                                                    echo '<tr>
                                                            <td><span class="db-font-mono">' . $Row['debtorno'] . '</span></td>
                                                            <td class="db-font-bold">' . $Row['name'] . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['CustomerGlAmount'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['CustomerGlTax'], 2) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['CustomerGlAmount'] + $Row['CustomerGlTax'], 2) . '</td>
                                                          </tr>';
                                                }
                                            }
    echo '                                  </tbody>
                                        </table>
                                    </div>
                                </div>
                              </div>';

                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-chart-line" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Unified Sales Intelligence') . '</h2>
                                    <p>' . __('Specify your reporting range on the left and click "View Report" to begin analysis.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
