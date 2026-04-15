<?php

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Price Intelligence Dashboard');
$ViewTopic = 'SalesTypes';
$BookMark = 'PDFPriceList';

// Parameter Initialization
if (isset($_POST['EffectiveDate'])) { $_POST['EffectiveDate'] = ConvertSQLDate($_POST['EffectiveDate']); }
if (!isset($_POST['SalesType'])) { $_POST['SalesType'] = $_SESSION['DefaultPriceList'] ?? 'DE'; } // Default fallback
if (!isset($_POST['Currency'])) { $_POST['Currency'] = 'All'; }
if (!isset($_POST['ShowGPPercentages'])) { $_POST['ShowGPPercentages'] = 'No'; }
if (!isset($_POST['CustomerSpecials'])) { $_POST['CustomerSpecials'] = 'Sales Type Prices'; }
if (!isset($_POST['ItemOrder'])) { $_POST['ItemOrder'] = 'Code'; }
if (!isset($_POST['EffectiveDate'])) { $_POST['EffectiveDate'] = date($_SESSION['DefaultDateFormat']); }

$ShowResults = (isset($_POST['View']) || isset($_POST['PrintPDF']));

// Logic Processing
if ($ShowResults) {
    $WhereCurrency = ($_POST['Currency'] != "All") ? " AND prices.currabrev = '" . $_POST['Currency'] ."' " : "";
    $ShowObsolete = isset($_POST['ShowObsolete']) ? "" : " AND `stockmaster`.`discontinued` != 1 ";
    $ItemOrder = ($_POST['ItemOrder'] == 'Description') ? 'stockmaster.description' : 'stockmaster.stockid';
    $EffectiveDateSQL = FormatDateForSQL($_POST['EffectiveDate']);

    if ($_POST['CustomerSpecials'] == 'Customer Special Prices Only') {
        if ($_SESSION['CustomerID'] == '') {
            include(__DIR__ . '/includes/header.php');
            prnMsg(__('A customer must first be selected to view special prices.'), 'error');
            echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">' . __('Back') . '</a>';
            include(__DIR__ . '/includes/footer.php');
            exit;
        }
        $custRow = DB_fetch_array(DB_query("SELECT name, salestype FROM debtorsmaster WHERE debtorno = '" . $_SESSION['CustomerID'] . "'"));
        $SalesType = $custRow['salestype'];
        $CustomerName = $custRow['name'];

        $SQL = "SELECT prices.typeabbrev, prices.stockid, stockmaster.description, stockmaster.longdescription, prices.currabrev, prices.startdate, prices.enddate, prices.price, 
                       stockmaster.actualcost AS standardcost, stockmaster.categoryid, stockcategory.categorydescription, prices.debtorno, prices.branchcode, custbranch.brname, currencies.decimalplaces
                FROM stockmaster
                INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid
                INNER JOIN prices ON stockmaster.stockid=prices.stockid
                INNER JOIN currencies ON prices.currabrev=currencies.currabrev
                LEFT JOIN custbranch ON prices.debtorno=custbranch.debtorno AND prices.branchcode=custbranch.branchcode
                WHERE prices.typeabbrev = '$SalesType'
                AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
                AND prices.debtorno='" . $_SESSION['CustomerID'] . "'
                AND prices.startdate<='$EffectiveDateSQL' AND prices.enddate >'$EffectiveDateSQL'
                $WhereCurrency $ShowObsolete
                ORDER BY prices.currabrev, stockcategory.categorydescription, $ItemOrder";
    } else {
        $SQL = "SELECT prices.typeabbrev, prices.stockid, prices.startdate, prices.enddate, stockmaster.description, stockmaster.longdescription, prices.currabrev, prices.price, 
                       stockmaster.actualcost as standardcost, stockmaster.categoryid, stockcategory.categorydescription, currencies.decimalplaces
                FROM stockmaster
                INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid
                INNER JOIN prices ON stockmaster.stockid=prices.stockid
                INNER JOIN currencies ON prices.currabrev=currencies.currabrev
                WHERE stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
                AND prices.typeabbrev='" . $_POST['SalesType'] . "'
                AND prices.startdate<='$EffectiveDateSQL' AND prices.enddate>'$EffectiveDateSQL'
                $WhereCurrency $ShowObsolete AND prices.debtorno LIKE '%%'
                ORDER BY prices.currabrev, stockcategory.categorydescription, $ItemOrder";
    }

    $PricesResult = DB_query($SQL);
    
    // PDF Generation Path
    if (isset($_POST['PrintPDF'])) {
        if (DB_num_rows($PricesResult) == 0) {
            include(__DIR__ . '/includes/header.php');
            prnMsg(__('No prices found to export.'), 'warn');
            echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">' . __('Back') . '</a>';
            include(__DIR__ . '/includes/footer.php');
            exit;
        }

        $HTML = '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" /></head><body>';
        $HTML .= '<div class="centre" id="ReportHeader">' . $_SESSION['CompanyRecord']['coyname'] . '<br />' . __('Price List') . ' - ' . $_POST['SalesType'] . '<br />' . __('Effective') . ': ' . $_POST['EffectiveDate'] . '</div>';
        $HTML .= '<table><thead><tr><th>' . __('Item') . '</th><th>' . __('Description') . '</th><th>' . __('Start') . '</th><th>' . __('End') . '</th>';
        if ($_POST['ShowGPPercentages'] == 'Yes') $HTML .= '<th>' . __('GP %') . '</th>';
        $HTML .= '<th>' . __('Price') . '</th></tr></thead><tbody>';
        
        $cCat = ''; $cCurr = '';
        require_once(__DIR__ . '/includes/CurrenciesArray.php');
        while ($Row = DB_fetch_array($PricesResult)) {
            if ($cCat != $Row['categoryid']) { $HTML .= '<tr><th colspan="10" style="background:#eee;">' . $Row['categorydescription'] . '</th></tr>'; $cCat = $Row['categoryid']; }
            if ($cCurr != $Row['currabrev']) { $HTML .= '<tr><th colspan="10" style="background:#f9f9f9;">' . $Row['currabrev'] . ' - ' . $CurrencyName[$Row['currabrev']] . '</th></tr>'; $cCurr = $Row['currabrev']; }
            
            $eDate = ($Row['enddate'] != '9999-12-31') ? ConvertSQLDate($Row['enddate']) : __('No End Date');
            $HTML .= '<tr><td>' . $Row['stockid'] . '</td><td>' . $Row['description'] . '</td><td>' . ConvertSQLDate($Row['startdate']) . '</td><td>' . $eDate . '</td>';
            if ($_POST['ShowGPPercentages'] == 'Yes') {
                $gp = ($Row['price'] != 0) ? locale_number_format((($Row['price']-$Row['standardcost'])*100/$Row['price']), 2) . '%' : '-';
                $HTML .= '<td class="number">' . $gp . '</td>';
            }
            $HTML .= '<td class="number">' . locale_number_format($Row['price'], $Row['decimalplaces']) . '</td></tr>';
        }
        $HTML .= '</tbody></table></body></html>';

        $DomPDF = new Dompdf($DomPDFOptions);
        $DomPDF->loadHtml($HTML);
        $DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
        $DomPDF->render();
        $DomPDF->stream($_SESSION['DatabaseName'] . '_PriceList_' . date('Y-m-d') . '.pdf', ["Attachment" => false]);
        exit;
    }
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-tags"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Strategic Catalog Management and Fulfillment Pricing Intelligence') . '</div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Strategy Panel -->
                <aside class="db-col-aside">
                    <div class="db-card" style="margin-bottom: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-layer-group"></i> ' . __('Catalog Segment') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Inventory Categories') . '</label>
                                <select name="Categories[]" class="db-select" multiple style="height: 160px; font-size: 0.85rem;">';
                                    $catRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
                                    while ($c = DB_fetch_array($catRes)) {
                                        $sel = (isset($_POST['Categories']) && in_array($c['categoryid'], $_POST['Categories'])) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                                <small class="text-muted">' . __('Hold Cmd/Ctrl for multiple') . '</small>
                            </div>
                        </div>
                    </div>

                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-cog"></i> ' . __('Pricing Strategy') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Sales Type / Price List') . '</label>
                                <select name="SalesType" class="db-select">';
                                    $stRes = DB_query("SELECT sales_type, typeabbrev FROM salestypes");
                                    while ($s = DB_fetch_array($stRes)) {
                                        echo '<option ' . (($_POST['SalesType'] ?? '') == $s['typeabbrev'] ? 'selected' : '') . ' value="' . $s['typeabbrev'] . '">' . $s['sales_type'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Regional Currency') . '</label>
                                <select name="Currency" class="db-select">
                                    <option value="All">' . __('All Currencies') . '</option>';
                                    $currRes = DB_query("SELECT currabrev, currency FROM currencies ORDER BY currency");
                                    while ($c = DB_fetch_array($currRes)) {
                                        echo '<option ' . (($_POST['Currency'] ?? '') == $c['currabrev'] ? 'selected' : '') . ' value="' . $c['currabrev'] . '">' . $c['currency'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Effective As Of') . '</label>
                                <input type="date" name="EffectiveDate" class="db-input" value="' . FormatDateForSQL($_POST['EffectiveDate']) . '" />
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Listing Format') . '</label>
                                <select name="CustomerSpecials" class="db-select">
                                    <option ' . ($_POST['CustomerSpecials'] == 'Sales Type Prices' ? 'selected' : '') . ' value="Sales Type Prices">' . __('Default Sales Type') . '</option>
                                    <option ' . ($_POST['CustomerSpecials'] == 'Customer Special Prices Only' ? 'selected' : '') . ' value="Customer Special Prices Only">' . __('Customer Specials') . '</option>
                                    <option ' . ($_POST['CustomerSpecials'] == 'Full Description' ? 'selected' : '') . ' value="Full Description">' . __('Full Media Listing') . '</option>
                                </select>
                            </div>
                            <div class="db-form-group" style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="ShowObsolete" ' . (isset($_POST['ShowObsolete']) ? 'checked' : '') . ' id="obCheck" class="db-checkbox" />
                                    <label class="db-label-sm" for="obCheck">' . __('Incl. Obsolete') . '</label>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="ShowGPPercentages" value="Yes" ' . (($_POST['ShowGPPercentages'] ?? '') == 'Yes' ? 'checked' : '') . ' id="gpCheck" class="db-checkbox" />
                                    <label class="db-label-sm" for="gpCheck">' . __('Show GP %') . '</label>
                                </div>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="View" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-binoculars"></i> ' . __('Audit Pricing') . '
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
                        $countFound = DB_num_rows($PricesResult);
                        $totalGP = 0; $gpItems = 0;
                        $data = [];
                        while ($r = DB_fetch_array($PricesResult)) {
                            if ($r['price'] != 0) {
                                $totalGP += (($r['price'] - $r['standardcost']) / $r['price']);
                                $gpItems++;
                            }
                            $data[] = $r;
                        }
                        $avgGP = ($gpItems > 0) ? ($totalGP / $gpItems) * 100 : 0;
                        $gpSev = ($avgGP < 25) ? 'danger' : (($avgGP < 40) ? 'warning' : 'success');

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-box-open"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Size') . '</span><span class="value">' . $countFound . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-coins"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Catalog Currency') . '</span><span class="value">' . ($_POST['Currency'] == 'All' ? __('Mixed') : $_POST['Currency']) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--' . $gpSev . '-soft); color: var(--' . $gpSev . ');"><i class="fas fa-chart-line"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Average GP %') . '</span><span class="value">' . locale_number_format($avgGP, 1) . '%</span><small class="text-muted">' . ($_POST['ShowGPPercentages'] == 'Yes' ? __('Portfolio Weighted') : __('Audit Basis')) . '</small></div>
                                </div>
                              </div>';

                        if ($countFound > 0) {
                            require_once(__DIR__ . '/includes/CurrenciesArray.php');
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-file-invoice-dollar"></i> ' . __('Price Excellence Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 120px;">' . __('Identity') . '</th>
                                                        <th>' . __('Detailed Intelligence') . '</th>
                                                        <th>' . __('Horizon') . '</th>';
                                                        if (($_POST['ShowGPPercentages'] ?? '') == 'Yes') echo '<th class="text-right">' . __('Margin %') . '</th>';
                                                        echo '<th class="text-right">' . __('Price Basis') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                
                                                $cCat = ''; $cCurr = '';
                                                foreach ($data as $Row) {
                                                    if ($cCat != $Row['categoryid']) {
                                                        echo '<tr><th colspan="10" style="background: var(--surface-alt); color: var(--primary); text-align: left; padding: 10px 15px;"><i class="fas fa-folder"></i> ' . $Row['categoryid'] . ' - ' . $Row['categorydescription'] . '</th></tr>';
                                                        $cCat = $Row['categoryid'];
                                                    }
                                                    if ($cCurr != $Row['currabrev']) {
                                                        echo '<tr><th colspan="10" style="background: var(--info-soft); font-size: 0.8rem; text-align: left; padding: 5px 15px;">' . $Row['currabrev'] . ' - ' . __($CurrencyName[$Row['currabrev']] ?? $Row['currabrev']) . '</th></tr>';
                                                        $cCurr = $Row['currabrev'];
                                                    }

                                                    $eDate = ($Row['enddate'] != '9999-12-31') ? ConvertSQLDate($Row['enddate']) : __('Permanent');
                                                    $itemGP = ($Row['price'] != 0) ? (($Row['price'] - $Row['standardcost']) * 100 / $Row['price']) : 0;
                                                    $itemSev = ($itemGP < 25) ? 'danger' : (($itemGP < 40) ? 'warning' : 'success');

                                                    echo '<tr>
                                                            <td><div class="db-font-bold text-primary">' . $Row['stockid'] . '</div>';
                                                            if ($_POST['CustomerSpecials'] == 'Full Description') {
                                                                $supported = ['png','jpg','jpeg']; $glob = glob($_SESSION['part_pics_dir'] . '/' . $Row['stockid'] . '.{' . implode(",", $supported) . '}', GLOB_BRACE);
                                                                if ($glob) echo '<img src="' . $RootPath . '/' . reset($glob) . '" style="width: 60px; height: 60px; object-fit: cover; margin-top: 5px; border-radius: var(--radius-sm);" />';
                                                            }
                                                    echo '  </td>
                                                            <td>
                                                                <div class="db-font-semibold">' . $Row['description'] . '</div>';
                                                                if ($_POST['CustomerSpecials'] == 'Full Description') echo '<div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px; line-height: 1.4;">' . nl2br($Row['longdescription']) . '</div>';
                                                                if (!empty($Row['brname'])) echo '<small class="text-info"><i class="fas fa-map-marker-alt"></i> ' . $Row['brname'] . '</small>';
                                                    echo '  </td>
                                                            <td>
                                                                <div style="font-size: 0.85rem;">' . ConvertSQLDate($Row['startdate']) . '</div>
                                                                <div class="text-muted" style="font-size: 0.75rem;">' . __('Until') . ': ' . $eDate . '</div>
                                                            </td>';
                                                            if (($_POST['ShowGPPercentages'] ?? '') == 'Yes') echo '<td class="text-right"><span class="db-badge db-badge-' . $itemSev . '">' . locale_number_format($itemGP, 1) . '%</span></td>';
                                                            echo '<td class="text-right db-font-bold" style="font-size: 1.1rem;">' . locale_number_format($Row['price'], $Row['decimalplaces']) . '</td>
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
                                    <i class="fas fa-receipt" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Price Intelligence Hub') . '</h2>
                                    <p>' . __('Audit pricing strategies, margins, and catalog excellence. Define your catalog segments on the left.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
