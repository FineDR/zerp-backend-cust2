<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Commission Reports');
$ViewTopic = 'SalesCommission';
$BookMark = 'Reports';
include(__DIR__ . '/includes/header.php');

// Parameter Initialization
if (!isset($_POST['SalesPerson'])) { $_POST['SalesPerson'] = '%%'; }
if (!isset($_POST['Currency'])) { $_POST['Currency'] = '%%'; }
if (!isset($_POST['PaidUnpaid'])) { $_POST['PaidUnpaid'] = '%%'; }
if (!isset($_POST['FromPeriod'])) { $_POST['FromPeriod'] = GetPeriod(date($_SESSION['DefaultDateFormat'])); }
if (!isset($_POST['ToPeriod'])) { $_POST['ToPeriod'] = GetPeriod(date($_SESSION['DefaultDateFormat'])); }
if (!isset($_POST['Period'])) { $_POST['Period'] = ''; }

if ($_POST['Period'] != '') {
    $_POST['FromPeriod'] = ReportPeriod($_POST['Period'], 'From');
    $_POST['ToPeriod'] = ReportPeriod($_POST['Period'], 'To');
}

$ShowResults = isset($_POST['Submit']);

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-hand-holding-usd"></i> ' . $Title . '
            </div>
        </div>

        <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Sidebar Criteria Panel -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Report Parameters') . '</div>
                        </div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Sales Person') . '</label>
                                <select name="SalesPerson" class="db-select">';
                                    $SQL = "SELECT salesmancode, salesmanname FROM salesman";
                                    $Res = DB_query($SQL);
                                    echo '<option value="%%">' . __('All Sales People') . '</option>';
                                    while ($MyRow = DB_fetch_array($Res)) {
                                        $sel = ($_POST['SalesPerson'] == $MyRow['salesmancode']) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Currency') . '</label>
                                <select name="Currency" class="db-select">';
                                    $SQL = "SELECT currabrev, currency FROM currencies";
                                    $Res = DB_query($SQL);
                                    echo '<option value="%%">' . __('All Currencies') . '</option>';
                                    while ($MyRow = DB_fetch_array($Res)) {
                                        $sel = ($_POST['Currency'] == $MyRow['currabrev']) ? 'selected' : '';
                                        echo '<option ' . $sel . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . ' (' . $MyRow['currabrev'] . ')</option>';
                                    }
    echo '                      </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Commission Status') . '</label>
                                <select name="PaidUnpaid" class="db-select">
                                    <option ' . ($_POST['PaidUnpaid'] == '%%' ? 'selected' : '') . ' value="%%">' . __('All Statuses') . '</option>
                                    <option ' . ($_POST['PaidUnpaid'] == '0' ? 'selected' : '') . ' value="0">' . __('Only Unpaid') . '</option>
                                    <option ' . ($_POST['PaidUnpaid'] == '1' ? 'selected' : '') . ' value="1">' . __('Only Paid') . '</option>
                                </select>
                            </div>

                            <div class="db-form-group">
                                <label class="db-label">' . __('Period Selection') . '</label>
                                <div style="display: flex; flex-direction: column; gap: 8px;">
                                    <select name="FromPeriod" class="db-select">';
                                        $NextYear = date('Y-m-d', strtotime('+1 Year'));
                                        $SQL = "SELECT periodno, lastdate_in_period FROM periods WHERE lastdate_in_period < '" . $NextYear . "' ORDER BY periodno DESC";
                                        $Periods = DB_query($SQL);
                                        while ($MyRow = DB_fetch_array($Periods)) {
                                            $sel = ($_POST['FromPeriod'] == $MyRow['periodno']) ? 'selected' : '';
                                            echo '<option ' . $sel . ' value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
                                        }
    echo '                          </select>
                                    <select name="ToPeriod" class="db-select">';
                                        DB_data_seek($Periods, 0);
                                        while ($MyRow = DB_fetch_array($Periods)) {
                                            $sel = ($_POST['ToPeriod'] == $MyRow['periodno']) ? 'selected' : '';
                                            echo '<option ' . $sel . ' value="' . $MyRow['periodno'] . '">' . MonthAndYearFromSQLDate($MyRow['lastdate_in_period']) . '</option>';
                                        }
    echo '                          </select>
                                </div>
                            </div>
                            
                            <div class="db-form-group">
                                <label class="db-label">' . __('OR Use Predefined Period') . '</label>
                                ' . str_replace('style="', 'class="db-select" style="', ReportPeriodList($_POST['Period'], array('l', 't'))) . '
                            </div>

                            <div style="margin-top: 25px;">
                                <button type="submit" name="Submit" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-sync"></i> ' . __('View Report') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Report Results Body -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        $SQL = "SELECT salescommissions.commissionno, salescommissions.type, salescommissions.transno, salescommissions.stkmoveno, salescommissions.salespersoncode,
                                       salescommissions.paid, salescommissions.amount, salesman.salesmanname, MONTHNAME(periods.lastdate_in_period) AS month, YEAR(periods.lastdate_in_period) AS year,
                                       salescommissions.currency, salescommissions.exrate, stockmoves.debtorno, stockmoves.type AS invcredit, stockmoves.transno AS invcredno, debtorsmaster.name, currencies.decimalplaces
                                FROM salescommissions
                                INNER JOIN gltrans ON salescommissions.commissionno=gltrans.typeno AND gltrans.type=39
                                INNER JOIN salesman ON salescommissions.salespersoncode=salesman.salesmancode
                                INNER JOIN periods ON periods.periodno=gltrans.periodno
                                INNER JOIN stockmoves ON salescommissions.stkmoveno=stockmoves.stkmoveno
                                INNER JOIN debtorsmaster ON stockmoves.debtorno=debtorsmaster.debtorno
                                INNER JOIN currencies ON salescommissions.currency=currencies.currabrev
                                WHERE salescommissions.salespersoncode LIKE '" . $_POST['SalesPerson'] . "'
                                AND salescommissions.currency LIKE '" . $_POST['Currency'] . "'
                                AND salescommissions.paid LIKE '" . $_POST['PaidUnpaid'] . "'
                                AND gltrans.periodno>='" . $_POST['FromPeriod'] . "'
                                AND gltrans.periodno<='" . $_POST['ToPeriod'] . "'
                                AND gltrans.account='" . $_SESSION['CompanyRecord']['commissionsact'] . "'
                                ORDER BY salescommissions.commissionno";
                        $Result = DB_query($SQL);

                        if (DB_num_rows($Result) > 0) {
                            $Total = 0;
                            $UnpaidCount = 0;
                            while($row = DB_fetch_array($Result)) {
                                $Total += $row['amount'];
                                if($row['paid'] == 0) $UnpaidCount++;
                            }
                            DB_data_seek($Result, 0);

                            echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                    <div class="kpi-card-v2">
                                        <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-coins"></i></div>
                                        <div class="kpi-data"><span class="label">' . __('Total Comm.') . '</span><span class="value">' . locale_number_format($Total, 2) . '</span></div>
                                    </div>
                                    <div class="kpi-card-v2">
                                        <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);"><i class="fas fa-hourglass-half"></i></div>
                                        <div class="kpi-data"><span class="label">' . __('Pending') . '</span><span class="value">' . $UnpaidCount . '</span></div>
                                    </div>
                                    <div class="kpi-card-v2">
                                        <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-calculator"></i></div>
                                        <div class="kpi-data"><span class="label">' . __('Trans. Count') . '</span><span class="value">' . DB_num_rows($Result) . '</span></div>
                                    </div>
                                  </div>';

                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-clipboard-check"></i> ' . __('Commission Report Results') . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">
                                                <thead>
                                                    <tr>
                                                        <th>' . __('ID') . '</th>
                                                        <th>' . __('Sales Person') . '</th>
                                                        <th>' . __('Period') . '</th>
                                                        <th>' . __('Invoice / Customer') . '</th>
                                                        <th class="text-right">' . __('Amount') . '</th>
                                                        <th class="text-center">' . __('Paid') . '</th>
                                                    </tr>
                                                </thead>
                                                <tbody>';
                                                while ($Row = DB_fetch_array($Result)) {
                                                    $type = ($Row['invcredit'] == 10) ? __('Inv') : __('CR');
                                                    $badge = ($Row['paid'] == 0) ? 'warning' : 'success';
                                                    $paidText = ($Row['paid'] == 0) ? __('Unpaid') : __('Paid');

                                                    echo '<tr>
                                                            <td><span class="db-font-mono">' . $Row['commissionno'] . '</span></td>
                                                            <td><div class="db-font-bold">' . $Row['salesmanname'] . '</div></td>
                                                            <td>' . $Row['month'] . ' ' . $Row['year'] . '</td>
                                                            <td>
                                                                <div class="db-font-semibold">' . $type . ' #' . $Row['invcredno'] . '</div>
                                                                <small class="text-muted">' . $Row['name'] . '</small>
                                                            </td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['amount'], $Row['decimalplaces']) . '</td>
                                                            <td class="text-center"><span class="db-badge db-badge-' . $badge . '">' . $paidText . '</span></td>
                                                          </tr>';
                                                }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="db-card" style="text-align: center; padding: 60px;">
                                    <i class="fas fa-search" style="font-size: 3rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h3>' . __('No commissions found matching your criteria.') . '</h3>
                                    <p class="text-muted">' . __('Please adjust your filters on the left and try again.') . '</p>
                                  </div>';
                        }
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-id-card-alt" style="font-size: 5rem; color: var(--border-color); margin-bottom: 20px;"></i>
                                    <h2 class="text-muted">' . __('Ready to Report') . '</h2>
                                    <p>' . __('Adjust the parameters on the left and click "View Report" to generate the performance analysis.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
