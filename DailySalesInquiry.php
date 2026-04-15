<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Monthly Velocity Dashboard');
$ViewTopic = 'ARInquiries';
$BookMark = '';

// Parameter Initialization
if (!isset($_POST['MonthToShow'])) { $_POST['MonthToShow'] = GetPeriod(date($_SESSION['DefaultDateFormat'])); }
if (!isset($_POST['Salesperson'])) { $_POST['Salesperson'] = 'All'; }

$ShowResults = (isset($_POST['View']) || isset($_POST['PrintPDF']));

// Logic Processing
if ($ShowResults) {
    $EndDateSQL = EndDateSQLFromPeriodNo($_POST['MonthToShow']);
    if (mb_strpos($EndDateSQL,'/')) { $Date_Array = explode('/',$EndDateSQL); } elseif (mb_strpos ($EndDateSQL,'-')) { $Date_Array = explode('-',$EndDateSQL); } elseif (mb_strpos ($EndDateSQL,'.')) { $Date_Array = explode('.',$EndDateSQL); }
    if (mb_strlen($Date_Array[2])>4) { $Date_Array[2]= mb_substr($Date_Array[2],0,2); }
    $StartDateSQL = date('Y-m-d', mktime(0,0,0, (int)$Date_Array[1],1,(int)$Date_Array[0]));

    $SQL = "SELECT trandate,
                   SUM(price*(1-discountpercent)* (-qty)) as salesvalue,
                   SUM(CASE WHEN mbflag='A' THEN 0 ELSE (standardcost * -qty) END) as cost
            FROM stockmoves
            INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid
            INNER JOIN custbranch ON stockmoves.debtorno=custbranch.debtorno AND stockmoves.branchcode=custbranch.branchcode
            WHERE (stockmoves.type=10 or stockmoves.type=11)
            AND trandate>='" . $StartDateSQL . "' AND trandate<='" . $EndDateSQL . "'";

    if ($_SESSION['SalesmanLogin'] != '') {
        $SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
    } elseif ($_POST['Salesperson'] != 'All') {
        $SQL .= " AND custbranch.salesman='" . $_POST['Salesperson'] . "'";
    }

    $SQL .= " GROUP BY trandate ORDER BY trandate";
    $SalesResult = DB_query($SQL);

    $CumulativeTotalSales = 0; $CumulativeTotalCost = 0; $BilledDays = 0; $DaySalesArray = [];
    while ($DaySalesRow = DB_fetch_array($SalesResult)) {
        $day = DayOfMonthFromSQLDate($DaySalesRow['trandate']);
        $DaySalesArray[$day] = [
            'Sales' => (float)$DaySalesRow['salesvalue'],
            'GPPercent' => ($DaySalesRow['salesvalue'] > 0) ? ($DaySalesRow['salesvalue'] - $DaySalesRow['cost']) / $DaySalesRow['salesvalue'] : 0
        ];
        $BilledDays++;
        $CumulativeTotalSales += $DaySalesRow['salesvalue'];
        $CumulativeTotalCost += $DaySalesRow['cost'];
    }

    // PDF Generation
    if (isset($_POST['PrintPDF'])) {
        $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Daily Sales Velocity') . '<br />' . MonthAndYearFromSQLDate($EndDateSQL) . '</div>';
        $HTML .= '<table><thead><tr><th>' . __('Sun') . '</th><th>' . __('Mon') . '</th><th>' . __('Tue') . '</th><th>' . __('Wed') . '</th><th>' . __('Thu') . '</th><th>' . __('Fri') . '</th><th>' . __('Sat') . '</th></tr></thead><tbody><tr>';
        
        $startDay = DayOfWeekFromSQLDate($StartDateSQL);
        $HTML .= str_repeat('<td></td>', $startDay);
        $daysInMonth = (int)date('t', strtotime($StartDateSQL));
        for ($i=1; $i<=$daysInMonth; $i++) {
            $val = isset($DaySalesArray[$i]) ? locale_number_format($DaySalesArray[$i]['Sales'], 0) . '<br/>' . locale_number_format($DaySalesArray[$i]['GPPercent']*100, 1) . '%' : '0<br/>0.0%';
            $HTML .= '<td style="border:1px solid #ddd; text-align:right;">' . $i . '<br/>' . $val . '</td>';
            if (($i + $startDay) % 7 == 0 && $i != $daysInMonth) $HTML .= '</tr><tr>';
        }
        $HTML .= '</tr></tbody></table></body></html>';
        $DomPDF = new Dompdf($DomPDFOptions);
        $DomPDF->loadHtml($HTML);
        $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
        $DomPDF->render();
        $DomPDF->stream($_SESSION['DatabaseName'] . '_DailySales_' . date('Y-m-d') . '.pdf', ["Attachment" => false]);
        exit;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-calendar-day"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Audit daily revenue velocity, fulfillment peaks, and monthly profitability heatmaps') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-history"></i> ' . __('Audit Horizon') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Financial Period') . '</label>
                                <select name="MonthToShow" class="db-select">';
                                    $pRes = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC LIMIT 24");
                                    while ($p = DB_fetch_array($pRes)) {
                                        echo '<option ' . (($_POST['MonthToShow'] ?? '') == $p['periodno'] ? 'selected' : '') . ' value="' . $p['periodno'] . '">' . MonthAndYearFromSQLDate($p['lastdate_in_period']) . '</option>';
                                    }
    echo '                      </select>
                            </div>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-user-tag"></i> ' . __('Sales Segment') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Salesperson') . '</label>';
                                if ($_SESSION['SalesmanLogin'] != '') {
                                    echo '<div class="db-badge db-badge-info" style="width: 100%; justify-content: center;">' . $_SESSION['UsersRealName'] . '</div>';
                                    echo '<input type="hidden" name="Salesperson" value="' . $_SESSION['SalesmanLogin'] . '" />';
                                } else {
                                    echo '<select name="Salesperson" class="db-select">
                                            <option value="All">' . __('Global Portfolio') . '</option>';
                                            $sRes = DB_query("SELECT salesmancode, salesmanname FROM salesman");
                                            while ($s = DB_fetch_array($sRes)) {
                                                echo '<option ' . (($_POST['Salesperson'] ?? '') == $s['salesmancode'] ? 'selected' : '') . ' value="' . $s['salesmancode'] . '">' . $s['salesmanname'] . '</option>';
                                            }
                                    echo '</select>';
                                }
    echo '                  </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-fire-alt"></i> ' . __('Audit Velocity') . '
                                </button>
                                <button type="submit" name="PrintPDF" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">
                                    <i class="fas fa-file-pdf"></i> ' . __('Export PDF') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $avgGP = ($CumulativeTotalSales != 0) ? ($CumulativeTotalSales - $CumulativeTotalCost) * 100 / $CumulativeTotalSales : 0;
                        $avgDaily = ($BilledDays > 0) ? $CumulativeTotalSales / $BilledDays : 0;
                        $gpSev = ($avgGP < 25) ? 'danger' : (($avgGP < 40) ? 'warning' : 'success');

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-hand-holding-usd"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Monthly Gross Yield') . '</span><span class="value">' . locale_number_format($CumulativeTotalSales, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-chart-line"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Average Daily Yield') . '</span><span class="value">' . locale_number_format($avgDaily, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--' . $gpSev . '-soft); color: var(--' . $gpSev . ');"><i class="fas fa-percentage"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio GP %') . '</span><span class="value">' . locale_number_format($avgGP, 1) . '%</span></div>
                                </div>
                              </div>';

                        // Sales Excellence Calendar
                        $daysInMonth = (int)date('t', strtotime($StartDateSQL));
                        $startDay = (int)date('w', strtotime($StartDateSQL)); // 0 (Sun) to 6 (Sat)
                        $daysToRender = ceil(($daysInMonth + $startDay) / 7) * 7;

                        echo '<div class="db-card">
                                <div class="db-card-header">
                                    <div class="db-card-title"><i class="fas fa-th"></i> ' . __('Monthly Velocity Heatmap') . ' - ' . MonthAndYearFromSQLDate($EndDateSQL) . '</div>
                                </div>
                                <div class="db-card-body p-0">
                                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: var(--border-color); gap: 1px;">
                                        <!-- Weekdays Headings -->';
                                        $weeks = [__('Sunday'), __('Monday'), __('Tuesday'), __('Wednesday'), __('Thursday'), __('Friday'), __('Saturday')];
                                        foreach ($weeks as $w) {
                                            echo '<div style="background: var(--surface-alt); padding: 10px; text-align: center; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted);">' . $w . '</div>';
                                        }
                                        
                                        $d = 1;
                                        for ($i = 0; $i < $daysToRender; $i++) {
                                            if ($i < $startDay || $d > $daysInMonth) {
                                                echo '<div style="background: var(--surface); height: 120px;"></div>';
                                            } else {
                                                $data = $DaySalesArray[$d] ?? ['Sales' => 0, 'GPPercent' => 0];
                                                $dayColor = ($data['Sales'] > 0) ? 'var(--primary-soft)' : 'var(--surface)';
                                                $gpCol = ($data['GPPercent'] < 0.2) ? 'danger' : (($data['GPPercent'] < 0.35) ? 'warning' : 'success');
                                                $isHigh = ($data['Sales'] > ($avgDaily * 1.5)) ? 'border: 2px solid var(--primary);' : '';

                                                echo '<div style="background: var(--surface); height: 120px; padding: 10px; display: flex; flex-direction: column; justify-content: space-between; position: relative; ' . $isHigh . '">
                                                        <div style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted); text-align: right;">' . $d . '</div>';
                                                        if ($data['Sales'] > 0) {
                                                            echo '<div>
                                                                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary); margin-bottom: 2px;">' . locale_number_format($data['Sales'], 0) . '</div>
                                                                    <div class="db-badge db-badge-' . $gpCol . '" style="padding: 1px 6px; font-size: 0.7rem;">' . locale_number_format($data['GPPercent'] * 100, 1) . '% ' . __('GP') . '</div>
                                                                  </div>';
                                                        }
                                                echo '</div>';
                                                $d++;
                                            }
                                        }
                        echo '      </div>
                                </div>
                              </div>';
                        
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-calendar-alt" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Monthly Velocity Hub') . '</h2>
                                    <p>' . __('Audit daily revenue velocity, fulfillment peaks, and weekly profitability trends. Select your horizon on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
