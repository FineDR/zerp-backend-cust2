<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Top Customer Sales Inquiry');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Parameter Initialization
if (!isset($_POST['DateRange'])) { $_POST['DateRange'] = 'ThisMonth'; }
if (!isset($_POST['OrderBy'])) { $_POST['OrderBy'] = 'NetSales'; }
if (!isset($_POST['NoToDisplay'])) { $_POST['NoToDisplay'] = 20; }
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if ($_POST['DateRange'] == 'Custom' && !isset($_POST['FromDate'])) {
    $_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0,0,0, date('m')-12, date('d'), date('Y')));
    $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

$ShowResults = isset($_POST['ShowSales']);

// Query Calculation Branch
if ($ShowResults) {
    switch ($_POST['DateRange']) {
        case 'ThisWeek':
            $FromDate = date('Y-m-d', mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'ThisMonth':
            $FromDate = date('Y-m-d', mktime(0,0,0,date('m'),1,date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'ThisQuarter':
            $qStart = match (date('m')) { 1,2,3=>1, 4,5,6=>4, 7,8,9=>7, default=>10 };
            $FromDate = date('Y-m-d', mktime(0,0,0, $qStart, 1, date('Y')));
            $ToDate = date('Y-m-d');
            break;
        case 'Custom':
            $FromDate = FormatDateForSQL($_POST['FromDate']);
            $ToDate = FormatDateForSQL($_POST['ToDate']);
    }

    $SQL = "SELECT stockmoves.debtorno, debtorsmaster.name,
                   SUM(CASE WHEN stockmoves.type=10 OR stockmoves.type=11 THEN -qty ELSE 0 END) as salesquantity,
                   SUM(CASE WHEN stockmoves.type=10 THEN price*(1-discountpercent)* -qty ELSE 0 END) as salesvalue,
                   SUM(CASE WHEN stockmoves.type=11 THEN price*(1-discountpercent)* (-qty) ELSE 0 END) as returnvalue,
                   SUM(CASE WHEN stockmoves.type=11 OR stockmoves.type=10 THEN price*(1-discountpercent)* (-qty) ELSE 0 END) as netsalesvalue
            FROM stockmoves INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
            WHERE (stockmoves.type=10 OR stockmoves.type=11) AND show_on_inv_crds = 1
            AND trandate >= '" . $FromDate . "' AND trandate <= '" . $ToDate . "'
            GROUP BY stockmoves.debtorno";

    if ($_POST['OrderBy'] == 'NetSales') {
        $SQL .= " ORDER BY netsalesvalue DESC ";
    } else {
        $SQL .= " ORDER BY salesquantity DESC ";
    }
    if (is_numeric($_POST['NoToDisplay']) && $_POST['NoToDisplay'] > 0) {
        $SQL .= " LIMIT " . (int)$_POST['NoToDisplay'];
    }
    $SalesResult = DB_query($SQL);
}

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-trophy"></i> ' . $Title . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Parameters Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-calendar-alt"></i> ' . __('Search Context') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Time Horizon') . '</label>
                                <select name="DateRange" class="db-select" onchange="this.form.submit()">
                                    <option ' . ($_POST['DateRange'] == 'ThisWeek' ? 'selected' : '') . ' value="ThisWeek">' . __('This Week') . '</option>
                                    <option ' . ($_POST['DateRange'] == 'ThisMonth' ? 'selected' : '') . ' value="ThisMonth">' . __('This Month') . '</option>
                                    <option ' . ($_POST['DateRange'] == 'ThisQuarter' ? 'selected' : '') . ' value="ThisQuarter">' . __('This Quarter') . '</option>
                                    <option ' . ($_POST['DateRange'] == 'Custom' ? 'selected' : '') . ' value="Custom">' . __('Custom Range') . '</option>
                                </select>
                            </div>';
                            
                            if ($_POST['DateRange'] == 'Custom') {
                                echo '<div class="db-grid-2">
                                        <div class="db-form-group"><label class="db-label">' . __('From') . '</label><input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" /></div>
                                        <div class="db-form-group"><label class="db-label">' . __('To') . '</label><input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" /></div>
                                      </div>';
                            }

    echo '                  <div class="db-form-group">
                                <label class="db-label">' . __('Rank By') . '</label>
                                <select name="OrderBy" class="db-select">
                                    <option ' . ($_POST['OrderBy'] == 'NetSales' ? 'selected' : '') . ' value="NetSales">' . __('Net Sales Value') . '</option>
                                    <option ' . ($_POST['OrderBy'] == 'Quantity' ? 'selected' : '') . ' value="Quantity">' . __('Sales Quantity') . '</option>
                                </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Volume Limit') . '</label>
                                <input type="number" name="NoToDisplay" class="db-input" value="' . (int)$_POST['NoToDisplay'] . '" min="1" max="500" />
                            </div>

                            <div style="margin-top: 30px;">
                                <button type="submit" name="ShowSales" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-sync"></i> ' . __('Refresh Rankings') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Intelligence Content Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $CumulativeSales = 0; $CumulativeReturns = 0; $CumulativeNet = 0;
                        $data = [];
                        while ($Row = DB_fetch_array($SalesResult)) {
                            $CumulativeSales += $Row['salesvalue'];
                            $CumulativeReturns += $Row['returnvalue'];
                            $CumulativeNet += $Row['netsalesvalue'];
                            $data[] = $Row;
                        }
                        $mvp = (count($data) > 0) ? $data[0]['name'] : 'N/A';

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-star"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Portfolio Net') . '</span><span class="value">' . locale_number_format($CumulativeNet, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--danger-soft); color: var(--danger);"><i class="fas fa-undo"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Returns') . '</span><span class="value">' . locale_number_format(abs($CumulativeReturns), 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-crown"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Current MVP') . '</span><span class="value" style="font-size: 1.1rem; filter: none;">' . $mvp . '</span></div>
                                </div>
                              </div>';

                        if (count($data) > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-medal"></i> ' . __('Customer Excellence Registry') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 80px;">' . __('Rank') . '</th>
                                                        <th>' . __('Customer Detail') . '</th>
                                                        <th class="text-right">' . __('Value') . '</th>
                                                        <th class="text-right">' . __('Returns') . '</th>
                                                        <th class="text-right">' . __('Net Impact') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                $rank = 1;
                                                foreach ($data as $Row) {
                                                    $badge = '';
                                                    if ($rank == 1) $badge = '<i class="fas fa-medal" style="color: #FFD700; font-size: 1.2rem;"></i>';
                                                    elseif ($rank == 2) $badge = '<i class="fas fa-medal" style="color: #C0C0C0; font-size: 1.1rem;"></i>';
                                                    elseif ($rank == 3) $badge = '<i class="fas fa-medal" style="color: #CD7F32; font-size: 1rem;"></i>';
                                                    else $badge = '<span class="text-muted">#' . $rank . '</span>';

                                                    echo '<tr>
                                                            <td class="text-center">' . $badge . '</td>
                                                            <td>
                                                                <div class="db-font-bold text-primary">' . $Row['name'] . '</div>
                                                                <small class="text-muted">' . $Row['debtorno'] . '</small>
                                                            </td>
                                                            <td class="text-right">' . locale_number_format($Row['salesvalue'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                                            <td class="text-right text-danger">' . locale_number_format($Row['returnvalue'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['netsalesvalue'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                                          </tr>';
                                                    $rank++;
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
                                    <i class="fas fa-users-cog" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Customer Excellence Hub') . '</h2>
                                    <p>' . __('Analyze customer performance by value or volume. Select your horizon on the left to begin.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
