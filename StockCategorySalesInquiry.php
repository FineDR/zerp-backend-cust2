<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Category Margin Intelligence');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Parameter Initialization
if (!isset($_POST['DateRange'])) { $_POST['DateRange'] = 'ThisMonth'; }
if (!isset($_POST['StockCat'])) { $_POST['StockCat'] = 'All'; }
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (!isset($_POST['FromDate'])) {
    $_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0,0,0, date('m')-12, date('d')+1, date('Y')));
    $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

$ShowResults = isset($_POST['ShowSales']);

// Query Calculation Branch
if ($ShowResults) {
    $FromDate = FormatDateForSQL($_POST['FromDate']);
    $ToDate = FormatDateForSQL($_POST['ToDate']);

    $SQL = "SELECT stockmaster.categoryid, stockcategory.categorydescription, stockmaster.stockid, stockmaster.description,
                   SUM(price*(1-discountpercent)* -qty) as salesvalue,
                   SUM(-qty) as quantitysold,
                   SUM(standardcost * -qty) as cogs
            FROM stockmoves 
            INNER JOIN stockmaster ON stockmoves.stockid=stockmaster.stockid
            INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid
            WHERE (stockmoves.type=10 OR stockmoves.type=11) AND show_on_inv_crds = 1
            AND trandate>='" . $FromDate . "' AND trandate<='" . $ToDate . "'";
    
    if ($_POST['StockCat'] != 'All') { $SQL .= " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'"; }
    
    $SQL .= " GROUP BY stockmaster.categoryid, stockcategory.categorydescription, stockmaster.stockid, stockmaster.description
              ORDER BY stockmaster.categoryid, salesvalue DESC";

    $SalesResult = DB_query($SQL);
}

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-chart-pie"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Audit profitability and fulfillment volume across product segments') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-sliders-h"></i> ' . __('Search Criteria') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Stock Category') . '</label>
                                <select name="StockCat" class="db-select">
                                    <option value="All">' . __('Global Portfolio') . '</option>';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    while ($c = DB_fetch_array($catRes)) {
                                        echo '<option ' . ($_POST['StockCat'] == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Audit Horizon') . '</label>
                                <div class="db-form-group"><label class="db-label-sm">' . __('From') . '</label><input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" /></div>
                                <div class="db-form-group"><label class="db-label-sm">' . __('To') . '</label><input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" /></div>
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="ShowSales" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-search-dollar"></i> ' . __('Audit Revenue') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $GlobalSales = 0; $GlobalCOGS = 0; $GlobalQty = 0;
                        $data = [];
                        while ($Row = DB_fetch_array($SalesResult)) {
                            $GlobalSales += $Row['salesvalue'];
                            $GlobalCOGS += $Row['cogs'];
                            $GlobalQty += $Row['quantitysold'];
                            $data[] = $Row;
                        }
                        $globalMargin = ($GlobalSales != 0) ? ($GlobalSales - $GlobalCOGS) * 100 / $GlobalSales : 0;
                        $globalSev = ($globalMargin < 20) ? 'danger' : (($globalMargin < 35) ? 'warning' : 'success');

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-hand-holding-usd"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Revenue') . '</span><span class="value">' . locale_number_format($GlobalSales, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--' . $globalSev . '-soft); color: var(--' . $globalSev . ');"><i class="fas fa-percentage"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Weighted Margin') . '</span><span class="value">' . locale_number_format($globalMargin, 1) . '%</span><small class="text-muted">' . locale_number_format($GlobalSales - $GlobalCOGS, 0) . ' ' . __('GP') . '</small></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-boxes"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Quantity Sold') . '</span><span class="value">' . locale_number_format($GlobalQty, 0) . '</span></div>
                                </div>
                              </div>';

                        if (count($data) > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Segment Performance Matrix') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('Item Identity') . '</th>
                                                        <th class="text-right">' . __('Qty') . '</th>
                                                        <th class="text-right">' . __('Revenue') . '</th>
                                                        <th class="text-right">' . __('COGS') . '</th>
                                                        <th class="text-right">' . __('Margin') . '</th>
                                                        <th class="text-right">' . __('Avg Price') . '</th>
                                                        <th class="text-right">' . __('Efficiency') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                
                                                $currentCat = ''; $catQty = 0; $catSales = 0; $catCOGS = 0;
                                                
                                                foreach ($data as $Row) {
                                                    if ($currentCat != $Row['categoryid']) {
                                                        if ($currentCat != '') {
                                                            // Yield Category Total row
                                                            $cMargin = ($catSales != 0) ? ($catSales - $catCOGS) * 100 / $catSales : 0;
                                                            echo '<tr style="background: var(--surface-alt); font-weight: 700;">
                                                                    <td class="text-right">' . __('Category Total') . '</td>
                                                                    <td class="text-right">' . locale_number_format($catQty, 0) . '</td>
                                                                    <td class="text-right">' . locale_number_format($catSales, 2) . '</td>
                                                                    <td class="text-right">' . locale_number_format($catCOGS, 2) . '</td>
                                                                    <td class="text-right">' . locale_number_format($catSales - $catCOGS, 2) . '</td>
                                                                    <td></td>
                                                                    <td class="text-right">' . locale_number_format($cMargin, 1) . '%</td>
                                                                  </tr>';
                                                        }
                                                        echo '<tr>
                                                                <th colspan="7" style="background: var(--primary-soft); color: var(--primary); text-align: left; padding: 10px 15px;">
                                                                    <i class="fas fa-folder-open"></i> ' . $Row['categoryid'] . ' - ' . $Row['categorydescription'] . '
                                                                </th>
                                                              </tr>';
                                                        $currentCat = $Row['categoryid']; $catQty = 0; $catSales = 0; $catCOGS = 0;
                                                    }
                                                    
                                                    $itemMargin = ($Row['salesvalue'] != 0) ? ($Row['salesvalue'] - $Row['cogs']) * 100 / $Row['salesvalue'] : 0;
                                                    $itemSev = ($itemMargin < 20) ? 'danger' : (($itemMargin < 35) ? 'warning' : 'success');
                                                    $avgPrice = ($Row['quantitysold'] != 0) ? $Row['salesvalue'] / $Row['quantitysold'] : 0;

                                                    echo '<tr>
                                                            <td>
                                                                <div class="db-font-semibold text-primary">' . $Row['stockid'] . '</div>
                                                                <small class="text-muted">' . $Row['description'] . '</small>
                                                            </td>
                                                            <td class="text-right">' . locale_number_format($Row['quantitysold'], 0) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['salesvalue'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['cogs'], 2) . '</td>
                                                            <td class="text-right db-font-semibold">' . locale_number_format($Row['salesvalue'] - $Row['cogs'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($avgPrice, 2) . '</td>
                                                            <td class="text-right"><span class="db-badge db-badge-' . $itemSev . '">' . locale_number_format($itemMargin, 1) . '%</span></td>
                                                          </tr>';
                                                    
                                                    $catQty += $Row['quantitysold']; $catSales += $Row['salesvalue']; $catCOGS += $Row['cogs'];
                                                }
                                                // Last category total
                                                if ($currentCat != '') {
                                                    $cMargin = ($catSales != 0) ? ($catSales - $catCOGS) * 100 / $catSales : 0;
                                                    echo '<tr style="background: var(--surface-alt); font-weight: 700;">
                                                            <td class="text-right">' . __('Category Total') . '</td>
                                                            <td class="text-right">' . locale_number_format($catQty, 0) . '</td>
                                                            <td class="text-right">' . locale_number_format($catSales, 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($catCOGS, 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($catSales - $catCOGS, 2) . '</td>
                                                            <td></td>
                                                            <td class="text-right">' . locale_number_format($cMargin, 1) . '%</td>
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
                                    <i class="fas fa-chart-line" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Margin Intelligence Hub') . '</h2>
                                    <p>' . __('Audit profitability and item-level efficiency across your stock categories. Define your horizons on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
