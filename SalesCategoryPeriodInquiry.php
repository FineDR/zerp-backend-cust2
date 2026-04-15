<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Segment Intelligence Dashboard');
$ViewTopic = 'Sales';
$BookMark = '';

// Parameter Initialization
if (!isset($_POST['DateRange'])) { $_POST['DateRange'] = 'ThisMonth'; }
if (isset($_POST['FromDate'])) { $_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']); }
if (isset($_POST['ToDate'])) { $_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']); }

$ShowResults = (isset($_POST['ShowSales']));

if ($ShowResults || true) {
    switch ($_POST['DateRange']) {
        case 'ThisWeek':
            $FromDate = date('Y-m-d', mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'ThisMonth':
            $FromDate = date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'ThisQuarter':
            $qStart = match (date('m')) { 1, 2, 3 => 1, 4, 5, 6 => 4, 7, 8, 9 => 7, default => 10 };
            $FromDate = date('Y-m-d', mktime(0, 0, 0, $qStart, 1, date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'Custom':
            $FromDate = isset($_POST['FromDate']) ? FormatDateForSQL($_POST['FromDate']) : date('Y-m-d', mktime(0,0,0, date('m')-1, date('d'), date('Y')));
            $ToDate = isset($_POST['ToDate']) ? FormatDateForSQL($_POST['ToDate']) : date('Y-m-d');
            break;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-boxes"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Audit category performance, margin health, and product segment revenue recognized') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="AuditForm">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-calendar-alt"></i> ' . __('Comparative Horizon') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Time Segment') . '</label>
                                <div style="display: flex; flex-direction: column; gap: 8px;">';
                                    $ranges = ['ThisWeek' => __('This Week'), 'ThisMonth' => __('This Month'), 'ThisQuarter' => __('This Quarter'), 'Custom' => __('Custom Period')];
                                    foreach ($ranges as $val => $lbl) {
                                        echo '<label class="db-label-sm" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border: 1px solid var(--border-color); border-radius: var(--radius-md); transition: var(--transition-base);">
                                                <input type="radio" name="DateRange" value="' . $val . '" ' . (($_POST['DateRange'] == $val) ? 'checked' : '') . ' onchange="this.form.submit()" />
                                                <span>' . $lbl . '</span>
                                              </label>';
                                    }
    echo '                      </div>
                            </div>';
                            
                            if ($_POST['DateRange'] == 'Custom') {
                                echo '<div class="db-form-group">
                                        <label class="db-label">' . __('Audit From') . '</label>
                                        <input type="date" name="FromDate" class="db-input" value="' . $FromDate . '" />
                                      </div>
                                      <div class="db-form-group">
                                        <label class="db-label">' . __('Audit To') . '</label>
                                        <input type="date" name="ToDate" class="db-input" value="' . $ToDate . '" />
                                      </div>';
                            }
    echo '              </div>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" name="ShowSales" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-layer-group"></i> ' . __('Audit Categories') . '
                        </button>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $SQL = "SELECT stockmaster.categoryid, stockcategory.categorydescription,
                                       SUM(CASE WHEN stockmoves.type=10 THEN price*(1-discountpercent)* -qty ELSE 0 END) as salesvalue,
                                       SUM(CASE WHEN stockmoves.type=11 THEN price*(1-discountpercent)* (-qty) ELSE 0 END) as returnvalue,
                                       SUM((standardcost * -qty)) as cost
                                FROM stockmoves 
                                INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid
                                INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid
                                WHERE (stockmoves.type=10 or stockmoves.type=11) AND show_on_inv_crds =1
                                AND trandate>='" . $FromDate . "' AND trandate<='" . $ToDate . "'
                                GROUP BY stockmaster.categoryid, stockcategory.categorydescription
                                ORDER BY salesvalue DESC";

                        $SalesResult = DB_query($SQL);

                        $data = []; $cumSales = 0; $cumRefunds = 0; $cumCost = 0;
                        while ($r = DB_fetch_array($SalesResult)) {
                            $data[] = $r;
                            $cumSales += $r['salesvalue'];
                            $cumRefunds += $r['returnvalue'];
                            $cumCost += $r['cost'];
                        }
                        $cumNet = $cumSales + $cumRefunds;
                        $cumGP = ($cumNet != 0) ? ($cumNet - $cumCost) * 100 / $cumNet : 0;

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-chart-line"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Gross Sales') . '</span><span class="value">' . locale_number_format($cumSales, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-file-invoice-dollar"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Net Realized Revenue') . '</span><span class="value">' . locale_number_format($cumNet, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-percentage"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Weighted Portfolio GP %') . '</span><span class="value">' . locale_number_format($cumGP, 1) . '%</span></div>
                                </div>
                              </div>';

                        if (count($data) > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-stream"></i> ' . __('Category Excellence Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Category Identity') . '</th>
                                                        <th class="text-right">' . __('Total Sales') . '</th>
                                                        <th class="text-right">' . __('Refunds') . '</th>
                                                        <th class="text-right text-primary">' . __('Net Revenue') . '</th>
                                                        <th class="text-right">' . __('GP % Outcome') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                
                                                foreach ($data as $r) {
                                                    $net = $r['salesvalue'] + $r['returnvalue'];
                                                    $gp = ($net != 0) ? ($net - $r['cost']) * 100 / $net : 0;
                                                    $gpSev = ($gp < 20) ? 'danger' : (($gp < 35) ? 'warning' : 'success');

                                                    echo '<tr>
                                                            <td>
                                                                <div class="db-font-bold text-primary">' . $r['categoryid'] . '</div>
                                                                <small class="text-muted">' . $r['categorydescription'] . '</small>
                                                            </td>
                                                            <td class="text-right">' . locale_number_format($r['salesvalue'], 0) . '</td>
                                                            <td class="text-right">' . locale_number_format($r['returnvalue'], 0) . '</td>
                                                            <td class="text-right text-primary db-font-semibold">' . locale_number_format($net, 0) . '</td>
                                                            <td class="text-right"><span class="db-badge db-badge-' . $gpSev . '" style="min-width: 60px; justify-content: center;">' . locale_number_format($gp, 1) . '%</span></td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                                <tfoot>
                                                    <tr style="background: var(--surface-alt); font-size: 1.1rem; font-weight: 800;">
                                                        <td class="text-right">' . __('PORTFOLIO TOTAL') . '</td>
                                                        <td class="text-right">' . locale_number_format($cumSales, 0) . '</td>
                                                        <td class="text-right">' . locale_number_format($cumRefunds, 0) . '</td>
                                                        <td class="text-right text-primary">' . locale_number_format($cumNet, 0) . '</td>
                                                        <td class="text-right">' . locale_number_format($cumGP, 1) . '%</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        }
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-boxes" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Segment Intelligence Hub') . '</h2>
                                    <p>' . __('Audit category profitability, revenue segments, and margin health performance. Define your analytical horizon on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
