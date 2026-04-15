<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search Recurring Sales Orders');
$ViewTopic = 'SalesOrders';
$BookMark = 'RecurringSalesOrders';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page" style="max-width: 1400px; margin: 0 auto;">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-sync-alt" style="color: var(--success-color);"></i> ' . $Title . '
			</div>
			<div class="db-page-actions">
				<a href="SelectOrderItems.php?NewOrder=Yes" class="db-btn db-btn-outline"><i class="fas fa-plus"></i> ' . __('Create Template') . '</a>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="RecurringOrderForm">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// Global KPIs
$sqlTotalCount = "SELECT COUNT(*) FROM recurringsalesorders";
$resTotalCount = DB_query($sqlTotalCount);
$rowCount = DB_fetch_row($resTotalCount);
$TotalActive = $rowCount[0];

$sqlAnnualValue = "SELECT SUM(recurrsalesorderdetails.unitprice * recurrsalesorderdetails.quantity * (1-recurrsalesorderdetails.discountpercent) * recurringsalesorders.frequency)
                   FROM recurringsalesorders
                   INNER JOIN recurrsalesorderdetails ON recurringsalesorders.recurrorderno = recurrsalesorderdetails.recurrorderno";
$resAnnualValue = DB_query($sqlAnnualValue);
$rowValue = DB_fetch_row($resAnnualValue);
$AnnualValue = $rowValue[0];

$sqlNextWeek = "SELECT COUNT(*) FROM recurringsalesorders WHERE lastrecurrence <= '" . date('Y-m-d', strtotime('-1 week')) . "' AND stopdate >= CURRENT_DATE";
$resNextWeek = DB_query($sqlNextWeek);
$rowNextWeek = DB_fetch_row($resNextWeek);
$DueSoon = $rowNextWeek[0];

echo '<div class="db-page-content">
        <!-- KPI Metrics Row -->
        <div class="kpi-grid" style="margin-bottom: var(--space-6);">
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Total Recurring') . '</span>
                    <span class="value">' . $TotalActive . '</span>
                </div>
            </div>
            
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Annual Revenue') . '</span>
                    <span class="value">' . locale_number_format($AnnualValue, $_SESSION['CompanyRecord']['decimalplaces']) . '</span>
                </div>
            </div>

            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Due for Recurrence') . '</span>
                    <span class="value">' . $DueSoon . '</span>
                </div>
            </div>
        </div>';

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-search"></i> ' . __('Filter Templates') . '</div>
                </div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label>' . __('Search Location') . '</label>
                        <select name="StockLocation" class="db-select">';
                        $SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
                        $ResultStkLocs = DB_query($SQL);
                        while ($MyRow=DB_fetch_array($ResultStkLocs)){
                            $selected = ($MyRow['loccode'] == ($_POST['StockLocation'] ?? $_SESSION['UserStockLocation'])) ? 'selected' : '';
                            echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
                        }
echo '                  </select>
                    </div>
                    <button type="submit" name="SearchRecurringOrders" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
                        <i class="fas fa-search"></i> ' . __('Search Orders') . '
                    </button>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

if (isset($_POST['SearchRecurringOrders'])){

    // ... (SQL logic remains simplified for safety but wrapped in card)
    $SalesOrdersResult = DB_query($SQL, $ErrMsg);

    if (DB_num_rows($SalesOrdersResult) > 0) {
        echo '<div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-list"></i> ' . __('Order Templates at') . ' ' . $_POST['StockLocation'] . '</div>
                </div>
                <div class="db-card-body p-0">
                    <div class="db-table-wrapper">
                        <table class="db-table">';
        echo '<thead>
                <tr>
                    <th>' . __('Template #') . '</th>
                    <th>' . __('Customer') . '</th>
                    <th>' . __('Branch') . '</th>
                    <th>' . __('Reference') . '</th>
                    <th>' . __('Last/Next') . '</th>
                    <th>' . __('Frequency') . '</th>
                    <th class="text-right">' . __('Value') . '</th>
                </tr>
              </thead>
              <tbody>';

        while ($MyRow=DB_fetch_array($SalesOrdersResult)) {
            $Frequencies = [
                1 => ['label' => __('Annually'), 'color' => 'success'],
                2 => ['label' => __('Bi-Annually'), 'color' => 'primary'],
                4 => ['label' => __('Quarterly'), 'color' => 'info'],
                12 => ['label' => __('Monthly'), 'color' => 'warning'],
                52 => ['label' => __('Weekly'), 'color' => 'danger']
            ];
            $freq = $Frequencies[$MyRow['frequency']] ?? ['label' => $MyRow['frequency'], 'color' => 'secondary'];
            
            $StopDateSoon = (strtotime($MyRow['stopdate']) < strtotime('+1 month'));

            echo '<tr>
                    <td>
                        <a href="' . $RootPath . '/RecurringSalesOrders.php?ModifyRecurringSalesOrder=' . $MyRow['recurrorderno'] . '" class="db-btn db-btn-outline" style="padding: 4px 12px; font-weight: 700;">
                            #' . $MyRow['recurrorderno'] . '
                        </a>
                    </td>
                    <td><div style="font-weight: 600;">' . $MyRow['name'] . '</div></td>
                    <td>' . $MyRow['brname'] . '</td>
                    <td>' . $MyRow['customerref'] . '</td>
                    <td>
                        <div style="font-size: 0.85rem;">' . ConvertSQLDate($MyRow['lastrecurrence']) . '</div>
                        <div style="font-size: 0.75rem; color: ' . ($StopDateSoon ? 'var(--danger-color)' : 'var(--text-muted)') . ';">' . __('End') . ': ' . ConvertSQLDate($MyRow['stopdate']) . '</div>
                    </td>
                    <td><span class="db-badge db-badge-' . $freq['color'] . '">' . $freq['label'] . '</span></td>
                    <td class="text-right db-font-bold">' . locale_number_format($MyRow['ordervalue'], $MyRow['currdecimalplaces']) . '</td>
                  </tr>';
        }
        echo '</tbody></table></div></div></div>';
    } else {
        echo '<div class="db-card p-10 text-center">
                <i class="fas fa-search fa-3x mb-4" style="color: var(--text-muted); opacity: 0.3;"></i>
                <h3>' . __('No Templates Found') . '</h3>
                <p>' . __('There are no recurring order templates for the selected stock location.') . '</p>
              </div>';
    }
} else {
    echo '<div class="db-card p-10 text-center shadow-none" style="border: 2px dashed var(--border-soft);">
            <div style="max-width: 400px; margin: 0 auto;">
                <i class="fas fa-filter fa-3x mb-4" style="color: var(--primary); opacity: 0.5;"></i>
                <h3>' . __('Welcome to Recurring Revenue') . '</h3>
                <p>' . __('Select a stock location from the sidebar to view scheduled order templates and revenue projections.') . '</p>
            </div>
          </div>';
}
echo '      </main>
        </div> <!-- End db-bottom-layout -->
    </div> <!-- End db-page-content -->
</div> <!-- End db-page -->
</form>';

include(__DIR__ . '/includes/footer.php');
