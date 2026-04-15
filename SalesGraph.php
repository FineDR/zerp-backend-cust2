<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Projection Dashboard');
$ViewTopic = 'ARInquiries';
$BookMark = 'SalesGraph';

// Period Logic Preparation
if (isset($_POST['Period']) && $_POST['Period'] != '') {
    $_POST['FromPeriod'] = ReportPeriod($_POST['Period'], 'From');
    $_POST['ToPeriod'] = ReportPeriod($_POST['Period'], 'To');
}

if (!isset($_POST['GraphType'])) { $_POST['GraphType'] = 'bars'; }
if (!isset($_POST['GraphOn'])) { $_POST['GraphOn'] = 'All'; }
if (!isset($_POST['GraphValue'])) { $_POST['GraphValue'] = 'Net'; }
if (!isset($_POST['SalesArea'])) { $_POST['SalesArea'] = 'All'; }
if (!isset($_POST['CategoryID'])) { $_POST['CategoryID'] = 'All'; }
if (!isset($_POST['SalesmanCode'])) { $_POST['SalesmanCode'] = 'All'; }

$ShowResults = isset($_POST['ShowGraph']);

if ($ShowResults) {
    if ($_POST['FromPeriod'] > $_POST['ToPeriod']) {
        prnMsg(__('The selected period from is after the period to!'), 'error');
        $ShowResults = false;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-chart-line"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Trend Intelligence: Actual Fulfillment vs. Strategic Budget') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Trend Horizons') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Period Range') . '</label>
                                <select name="FromPeriod" class="db-select" style="margin-bottom: 8px;">';
                                    $Periods = DB_query("SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno");
                                    if (date('m') > $_SESSION['YearEnd']) {
                                        $DefaultFromDate = Date('Y-m-d', mktime(0,0,0, $_SESSION['YearEnd'] + 2, 0, date('Y')));
                                    } else {
                                        $DefaultFromDate = Date('Y-m-d', mktime(0,0,0, $_SESSION['YearEnd'] + 2, 0, date('Y')-1));
                                    }
                                    while ($m = DB_fetch_array($Periods)) {
                                        $selected = (isset($_POST['FromPeriod']) && $_POST['FromPeriod'] == $m['periodno']) || (!isset($_POST['FromPeriod']) && $m['lastdate_in_period'] == $DefaultFromDate) ? 'selected' : '';
                                        echo '<option ' . $selected . ' value="' . $m['periodno'] . '">' . MonthAndYearFromSQLDate($m['lastdate_in_period']) . '</option>';
                                    }
    echo '                      </select>
                                <select name="ToPeriod" class="db-select">';
                                    DB_data_seek($Periods, 0);
                                    $DefaultToPeriod = isset($_POST['ToPeriod']) ? $_POST['ToPeriod'] : GetPeriod(DateAdd(ConvertSQLDate($DefaultFromDate), 'm', 11));
                                    while ($m = DB_fetch_array($Periods)) {
                                        echo '<option ' . ($m['periodno'] == $DefaultToPeriod ? 'selected' : '') . ' value="' . $m['periodno'] . '">' . MonthAndYearFromSQLDate($m['lastdate_in_period']) . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Quick Selection') . '</label>
                                ' . ReportPeriodList($_POST['Period'] ?? '', array('l', 't'), 'db-select') . '
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Demographic Focus') . '</label>
                                <select name="SalesArea" class="db-select" style="margin-bottom: 8px;">
                                    <option value="All">' . __('All Sales Areas') . '</option>';
                                    $areas = DB_query("SELECT areacode, areadescription FROM areas ORDER BY areadescription");
                                    while ($a = DB_fetch_array($areas)) {
                                        echo '<option ' . ($_POST['SalesArea'] == $a['areacode'] ? 'selected' : '') . ' value="' . $a['areacode'] . '">' . $a['areadescription'] . '</option>';
                                    }
    echo '                      </select>
                                <select name="CategoryID" class="db-select" style="margin-bottom: 8px;">
                                    <option value="All">' . __('All Stock Categories') . '</option>';
                                    $cats = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    while ($c = DB_fetch_array($cats)) {
                                        echo '<option ' . ($_POST['CategoryID'] == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                                <select name="SalesmanCode" class="db-select">
                                    <option value="All">' . __('All Salespeople') . '</option>';
                                    $sm = DB_query("SELECT salesmancode, salesmanname FROM salesman ORDER BY salesmanname");
                                    while ($s = DB_fetch_array($sm)) {
                                        echo '<option ' . ($_POST['SalesmanCode'] == $s['salesmancode'] ? 'selected' : '') . ' value="' . $s['salesmancode'] . '">' . $s['salesmanname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Visual Configuration') . '</label>
                                <select name="GraphType" class="db-select" style="margin-bottom: 8px;">
                                    <option ' . ($_POST['GraphType'] == 'bars' ? 'selected' : '') . ' value="bars">' . __('Bar Graph') . '</option>
                                    <option ' . ($_POST['GraphType'] == 'lines' ? 'selected' : '') . ' value="lines">' . __('Line Graph') . '</option>
                                    <option ' . ($_POST['GraphType'] == 'area' ? 'selected' : '') . ' value="area">' . __('Area Graph') . '</option>
                                    <option ' . ($_POST['GraphType'] == 'pie' ? 'selected' : '') . ' value="pie">' . __('Pie Graph') . '</option>
                                    <option ' . ($_POST['GraphType'] == 'stackedbars' ? 'selected' : '') . ' value="stackedbars">' . __('Stacked Bar') . '</option>
                                </select>
                                <select name="GraphValue" class="db-select">
                                    <option ' . ($_POST['GraphValue'] == 'Net' ? 'selected' : '') . ' value="Net">' . __('Net Sales Value') . '</option>
                                    <option ' . ($_POST['GraphValue'] == 'GP' ? 'selected' : '') . ' value="GP">' . __('Gross Profit') . '</option>
                                    <option ' . ($_POST['GraphValue'] == 'Quantity' ? 'selected' : '') . ' value="Quantity">' . __('Sales Volume') . '</option>
                                </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Entity Range') . '</label>
                                <select name="GraphOn" class="db-select" style="margin-bottom: 8px;">
                                    <option ' . ($_POST['GraphOn'] == 'All' ? 'selected' : '') . ' value="All">' . __('Global Performance') . '</option>
                                    <option ' . ($_POST['GraphOn'] == 'Customer' ? 'selected' : '') . ' value="Customer">' . __('Customer Specific') . '</option>
                                    <option ' . ($_POST['GraphOn'] == 'StockID' ? 'selected' : '') . ' value="StockID">' . __('Item Specific') . '</option>
                                </select>
                                <div class="db-grid-2">
                                    <input type="text" name="ValueFrom" class="db-input" value="' . ($_POST['ValueFrom'] ?? '') . '" placeholder="' . __('From') . '" />
                                    <input type="text" name="ValueTo" class="db-input" value="' . ($_POST['ValueTo'] ?? '') . '" placeholder="' . __('To') . '" />
                                </div>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="ShowGraph" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-eye"></i> ' . __('Visualize Trend') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        // Data Generation Logic
                        $SelectClause = match ($_POST['GraphValue']) { 'Net' => 'amt - disc', 'GP' => 'amt - disc - cost', default => 'qty' };
                        $WhereClause = "WHERE salesanalysis.periodno>='" . $_POST['FromPeriod'] . "' AND salesanalysis.periodno <= '" . $_POST['ToPeriod'] . "'";
                        
                        if ($_POST['SalesArea'] != 'All') { $WhereClause .= " AND area='" . $_POST['SalesArea'] . "'"; }
                        if ($_POST['CategoryID'] != 'All') { $WhereClause .= " AND stkcategory='" . $_POST['CategoryID'] . "'"; }
                        if ($_POST['SalesmanCode'] != 'All') { $WhereClause .= " AND salesperson='" . $_POST['SalesmanCode'] . "'"; }
                        if ($_POST['GraphOn'] == 'Customer') { $WhereClause .= " AND cust >='" . $_POST['ValueFrom'] . "' AND cust <='" . $_POST['ValueTo'] . "'"; }
                        if ($_POST['GraphOn'] == 'StockID') { $WhereClause .= " AND stockid >='" . $_POST['ValueFrom'] . "' AND stockid <='" . $_POST['ValueTo'] . "'"; }

                        $SQL = "SELECT salesanalysis.periodno, periods.lastdate_in_period,
                                       SUM(CASE WHEN budgetoractual=1 THEN " . $SelectClause . " ELSE 0 END) AS actual,
                                       SUM(CASE WHEN budgetoractual=0 THEN " . $SelectClause . " ELSE 0 END) AS budget
                                FROM salesanalysis 
                                INNER JOIN periods ON salesanalysis.periodno=periods.periodno " . $WhereClause . "
                                GROUP BY salesanalysis.periodno, periods.lastdate_in_period ORDER BY salesanalysis.periodno";
                        
                        $Result = DB_query($SQL);
                        $TotalActual = 0; $TotalBudget = 0; $GraphArray = [];
                        while ($Row = DB_fetch_array($Result)) {
                            $TotalActual += $Row['actual']; $TotalBudget += $Row['budget'];
                            $GraphArray[] = [MonthAndYearFromSQLDate($Row['lastdate_in_period']), $Row['actual'], $Row['budget']];
                        }
                        $achievement = ($TotalBudget != 0) ? ($TotalActual / $TotalBudget) * 100 : 0;
                        $severity = ($achievement < 75) ? 'danger' : (($achievement < 95) ? 'warning' : 'success');

                        // PHPlot Generation
                        $graph = new Phplot\Phplot\phplot(950, 450);
                        $graph->SetOutputFile($_SESSION['reports_dir'] . '/salesgraph.png');
                        $graph->SetPlotType($_POST['GraphType']);
                        $graph->SetDataType('text-data');
                        $graph->SetDataValues($GraphArray);
                        $graph->SetIsInline('1');
                        $graph->SetBackgroundColor('white');
                        $graph->SetDrawYGrid(true);
                        $graph->SetLegend([__('Actual'), __('Budget')]);
                        $graph->SetDataColors(['grey', 'wheat'], ['black']);
                        $graph->DrawGraph();

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-handshake"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Actual') . '</span><span class="value">' . locale_number_format($TotalActual, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-calendar-check"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Budgeted') . '</span><span class="value">' . locale_number_format($TotalBudget, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--' . $severity . '-soft); color: var(--' . $severity . ');"><i class="fas fa-percent"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Achievement %') . '</span><span class="value">' . locale_number_format($achievement, 1) . '%</span></div>
                                </div>
                              </div>';

                        echo '<div class="db-card" style="margin-bottom: var(--space-6);">
                                <div class="db-card-header"><div class="db-card-title"><i class="fas fa-chart-bar"></i> ' . __('Strategic Trend Visualization') . '</div></div>
                                <div class="db-card-body" style="text-align: center;">
                                    <img src="' . $RootPath . '/' . $_SESSION['reports_dir'] . '/salesgraph.png" alt="Sales Trend" style="max-width: 100%; height: auto;" />
                                </div>
                              </div>';

                        echo '<div class="db-card">
                                <div class="db-card-header"><div class="db-card-title"><i class="fas fa-table"></i> ' . __('Monthly Performance Registry') . '</div></div>
                                <div class="db-card-body p-0">
                                    <div class="db-table-wrapper">
                                        <table class="db-table">
                                            <thead>
                                                <tr>
                                                    <th>' . __('Fiscal Month') . '</th>
                                                    <th class="text-right">' . __('Actual Performance') . '</th>
                                                    <th class="text-right">' . __('Target Budget') . '</th>
                                                    <th class="text-right">' . __('Variance %') . '</th>
                                                </tr>
                                            </thead>
                                            <tbody>';
                                            foreach ($GraphArray as $g) {
                                                $var = ($g[2] != 0) ? ($g[1] / $g[2]) * 100 : 0;
                                                $vSev = ($var < 75) ? 'danger' : (($var < 95) ? 'warning' : 'success');
                                                echo '<tr>
                                                        <td class="db-font-semibold">' . $g[0] . '</td>
                                                        <td class="text-right db-font-bold">' . locale_number_format($g[1], 0) . '</td>
                                                        <td class="text-right">' . locale_number_format($g[2], 0) . '</td>
                                                        <td class="text-right"><span class="db-badge db-badge-' . $vSev . '">' . locale_number_format($var, 1) . '%</span></td>
                                                      </tr>';
                                            }
                        echo '              </tbody>
                                        </table>
                                    </div>
                                </div>
                              </div>';
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-chart-area" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Trend Intelligence Hub') . '</h2>
                                    <p>' . __('Visualize fulfillment performance vs. strategic budgets. Define your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
