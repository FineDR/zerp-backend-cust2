<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Analysis Architect');
$ViewTopic = 'SalesAnalysis';
$BookMark = 'SalesAnalysis';

// CRUD Logic (Migrated and Streamlined)
if (isset($_GET['SelectedReport'])) { $SelectedReport = $_GET['SelectedReport']; } 
elseif (isset($_POST['SelectedReport'])) { $SelectedReport = $_POST['SelectedReport']; }

if (isset($_POST['submit'])) {
    $InputError = 0;
    if (mb_strlen($_POST['ReportHeading']) < 2) {
        $InputError = 1;
        prnMsg(__('The report heading must be more than two characters long'), 'error');
    }
    if ($_POST['GroupByData1'] == 'Not Used') {
        $InputError = 1;
        prnMsg(__('A group by item must be specified for level 1'), 'error');
    }
    
    // Auto-realign grouping levels if gaps exist
    if ($_POST['GroupByData3'] == 'Not Used' && $_POST['GroupByData4'] != 'Not Used') {
        $_POST['GroupByData3'] = $_POST['GroupByData4']; $_POST['Lower3'] = $_POST['Lower4']; $_POST['Upper3'] = $_POST['Upper4'];
    }
    if ($_POST['GroupByData2'] == 'Not Used' && $_POST['GroupByData3'] != 'Not Used') {
        $_POST['GroupByData2'] = $_POST['GroupByData3']; $_POST['Lower2'] = $_POST['Lower3']; $_POST['Upper2'] = $_POST['Upper3'];
    }

    if ($InputError != 1) {
        if (isset($SelectedReport)) {
            $SQL = "UPDATE reportheaders SET
                        reportheading='" . $_POST['ReportHeading'] . "',
                        groupbydata1='" . $_POST['GroupByData1'] . "',
                        groupbydata2='" . $_POST['GroupByData2'] . "',
                        groupbydata3='" . $_POST['GroupByData3'] . "',
                        groupbydata4='" . $_POST['GroupByData4'] . "',
                        newpageafter1='" . $_POST['NewPageAfter1'] . "',
                        newpageafter2='" . $_POST['NewPageAfter2'] . "',
                        newpageafter3='" . $_POST['NewPageAfter3'] . "',
                        lower1='" . filter_number_format($_POST['Lower1']) . "',
                        upper1='" . filter_number_format($_POST['Upper1']) . "',
                        lower2='" . filter_number_format($_POST['Lower2']) . "',
                        upper2='" . filter_number_format($_POST['Upper2']) . "',
                        lower3='" . filter_number_format($_POST['Lower3']) . "',
                        upper3='" . filter_number_format($_POST['Upper3']) . "',
                        lower4='" . filter_number_format($_POST['Lower4']) . "',
                        upper4='" . filter_number_format($_POST['Upper4']) . "'
                    WHERE reportid = " . $SelectedReport;
            $ErrMsg = __('The report could not be updated because');
            DB_query($SQL, $ErrMsg);
            prnMsg(__('Report structure successfully engineered'), 'success');
        } else {
            $SQL = "INSERT INTO reportheaders (reportheading, groupbydata1, groupbydata2, groupbydata3, groupbydata4, newpageafter1, newpageafter2, newpageafter3, lower1, upper1, lower2, upper2, lower3, upper3, lower4, upper4)
                    VALUES ('" . $_POST['ReportHeading'] . "', '" . $_POST['GroupByData1']. "', '" . $_POST['GroupByData2'] . "', '" . $_POST['GroupByData3'] . "', '" . $_POST['GroupByData4'] . "', '" . $_POST['NewPageAfter1'] . "', '" . $_POST['NewPageAfter2'] . "', '" . $_POST['NewPageAfter3'] . "', '" . filter_number_format($_POST['Lower1']) . "', '" . filter_number_format($_POST['Upper1']) . "', '" . filter_number_format($_POST['Lower2']) . "', '" . filter_number_format($_POST['Upper2']) . "', '" . filter_number_format($_POST['Lower3']) . "', '" . filter_number_format($_POST['Upper3']) . "', '" . filter_number_format($_POST['Lower4']) . "', '" . filter_number_format($_POST['Upper4']) . "')";
            $ErrMsg = __('The report could not be added because');
            DB_query($SQL, $ErrMsg);
            prnMsg(__('New report design successfully registered'), 'success');
        }
        unset($SelectedReport);
    }
} elseif (isset($_GET['delete'])) {
    DB_query("DELETE FROM reportcolumns WHERE reportid='".$SelectedReport."'");
    DB_query("DELETE FROM reportheaders WHERE reportid='".$SelectedReport."'");
    prnMsg(__('Report design successfully decommissioned'),'info');
    unset($SelectedReport);
}

include(__DIR__ . '/includes/header.php');

function GrpByDataOptions($current) {
    $options = ['Sales Area', 'Product Code', 'Customer Code', 'Sales Type', 'Product Type', 'Customer Branch', 'Sales Person', 'Not Used'];
    foreach ($options as $opt) {
        $sel = ($current == $opt || (empty($current) && $opt == 'Not Used')) ? 'selected' : '';
        echo '<option ' . $sel . ' value="' . $opt . '">' . __($opt) . '</option>';
    }
}

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-drafting-compass"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">' . __('Architect and maintain custom-engineered sales analysis reporting structures') . '</div>
        </div>

        <div class="db-bottom-layout">
            <!-- Part 1: Report Selection Sidebar -->
            <aside class="db-col-aside">
                <div class="db-card">
                    <div class="db-card-header">
                        <div class="db-card-title"><i class="fas fa-project-diagram"></i> ' . __('Design Portfolio') . '</div>
                    </div>
                    <div class="db-card-body p-0">
                        <div class="db-sidebar-list">';
                            $res = DB_query("SELECT reportid, reportheading FROM reportheaders ORDER BY reportid");
                            while ($r = DB_fetch_array($res)) {
                                $active = (isset($SelectedReport) && $SelectedReport == $r['reportid']) ? 'active' : '';
                                echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?SelectedReport=' . $r['reportid'] . '" class="db-sidebar-item ' . $active . '">
                                        <div class="db-sidebar-item-content">
                                            <div class="db-font-bold text-truncate">' . $r['reportheading'] . '</div>
                                            <small class="text-muted">' . __('ID') . ': #' . $r['reportid'] . '</small>
                                        </div>
                                      </a>';
                            }
    echo '              </div>
                        <div style="padding: var(--space-4);">
                            <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                <i class="fas fa-plus"></i> ' . __('Architect New Report') . '
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Part 2: Design Canvas Main Panel -->
            <main class="db-col-main">';

                if (isset($SelectedReport) || isset($_GET['new'])) {
                    if (isset($SelectedReport)) {
                        $myRow = DB_fetch_array(DB_query("SELECT * FROM reportheaders WHERE reportid='".$SelectedReport."'"));
                        $_POST = array_merge($_POST, $myRow);
                        // Ensure defaults for page breaks
                        if (!isset($_POST['newpageafter1'])) $_POST['newpageafter1'] = $myRow['newpageafter1'];
                        if (!isset($_POST['newpageafter2'])) $_POST['newpageafter2'] = $myRow['newpageafter2'];
                        if (!isset($_POST['newpageafter3'])) $_POST['newpageafter3'] = $myRow['newpageafter3'];
                    }

                    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                            <input type="hidden" name="SelectedReport" value="' . ($SelectedReport ?? '') . '" />

                            <!-- Professional Header Action Bar -->
                            ' . (isset($SelectedReport) ? '
                            <div class="db-card" style="margin-bottom: var(--space-4); border-left: 4px solid var(--primary);">
                                <div class="db-card-body" style="display: flex; align-items: center; justify-content: space-between; padding: var(--space-4) var(--space-6);">
                                    <div>
                                        <div class="db-font-bold" style="font-size: 1.1rem;">' . $_POST['reportheading'] . '</div>
                                        <small class="text-muted">' . __('Unified Design Architecture') . ' #' . $SelectedReport . '</small>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="' . $RootPath . '/SalesAnalReptCols.php?ReportID=' . $SelectedReport . '" class="db-btn db-btn-outline"><i class="fas fa-columns"></i> ' . __('Engineer Columns') . '</a>
                                        <a href="' . $RootPath . '/SalesAnalysis_UserDefined.php?ReportID=' . $SelectedReport . '&ProducePDF=True" class="db-btn db-btn-outline"><i class="fas fa-file-pdf"></i> ' . __('Execute PDF') . '</a>
                                        <a href="' . $RootPath . '/SalesAnalysis_UserDefined.php?ReportID=' . $SelectedReport . '&ProduceCVSFile=True" class="db-btn db-btn-outline"><i class="fas fa-file-csv"></i> ' . __('Execute CSV') . '</a>
                                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '?SelectedReport=' . $SelectedReport . '&delete=1" class="db-btn db-btn-danger" onclick="return confirm(\'' . __('Confirm decommissioning of this report design?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                    </div>
                                </div>
                            </div>' : '') . '

                            <div class="db-card" style="margin-bottom: var(--space-6);">
                                <div class="db-card-header"><div class="db-card-title"><i class="fas fa-heading"></i> ' . __('Global Design Identity') . '</div></div>
                                <div class="db-card-body">
                                    <div class="db-form-group">
                                        <label class="db-label">' . __('Engineering Headline / Report Title') . '</label>
                                        <input type="text" name="ReportHeading" class="db-input" value="' . ($_POST['reportheading'] ?? '') . '" placeholder="' . __('e.g., Sales Velocity by Territory') . '" required />
                                    </div>
                                </div>
                            </div>

                            <div class="db-grid-2" style="gap: var(--space-4);">
                                <!-- Level 1 -->
                                <div class="db-card">
                                    <div class="db-card-header" style="background: var(--primary-soft);"><div class="db-card-title"><span class="db-badge db-badge-primary">1</span> ' . __('Primary Grouping (Core)') . '</div></div>
                                    <div class="db-card-body">
                                        <div class="db-form-group"><label class="db-label">' . __('Group Metric') . '</label><select name="GroupByData1" class="db-select">'; GrpByDataOptions($_POST['groupbydata1'] ?? ''); echo '</select></div>
                                        <div class="db-form-group"><label class="db-label">' . __('Page Break After') . '</label><select name="NewPageAfter1" class="db-select"><option value="0" ' . (($_POST['newpageafter1'] ?? 0) == 0 ? 'selected' : '') . '>' . __('No') . '</option><option value="1" ' . (($_POST['newpageafter1'] ?? 0) == 1 ? 'selected' : '') . '>' . __('Yes') . '</option></select></div>
                                        <div class="db-grid-2">
                                            <div class="db-form-group"><label class="db-label">' . __('Range From') . '</label><input type="text" name="Lower1" class="db-input" value="' . ($_POST['lower1'] ?? '') . '" placeholder="Min" /></div>
                                            <div class="db-form-group"><label class="db-label">' . __('Range To') . '</label><input type="text" name="Upper1" class="db-input" value="' . ($_POST['upper1'] ?? '') . '" placeholder="Max" /></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Level 2 -->
                                <div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><span class="db-badge">2</span> ' . __('Secondary Grouping') . '</div></div>
                                    <div class="db-card-body">
                                        <div class="db-form-group"><label class="db-label">' . __('Group Metric') . '</label><select name="GroupByData2" class="db-select">'; GrpByDataOptions($_POST['groupbydata2'] ?? ''); echo '</select></div>
                                        <div class="db-form-group"><label class="db-label">' . __('Page Break After') . '</label><select name="NewPageAfter2" class="db-select"><option value="0" ' . (($_POST['newpageafter2'] ?? 0) == 0 ? 'selected' : '') . '>' . __('No') . '</option><option value="1" ' . (($_POST['newpageafter2'] ?? 0) == 1 ? 'selected' : '') . '>' . __('Yes') . '</option></select></div>
                                        <div class="db-grid-2">
                                            <div class="db-form-group"><label class="db-label">' . __('Range From') . '</label><input type="text" name="Lower2" class="db-input" value="' . ($_POST['lower2'] ?? '') . '" /></div>
                                            <div class="db-form-group"><label class="db-label">' . __('Range To') . '</label><input type="text" name="Upper2" class="db-input" value="' . ($_POST['upper2'] ?? '') . '" /></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Level 3 -->
                                <div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><span class="db-badge">3</span> ' . __('Tertiary Grouping') . '</div></div>
                                    <div class="db-card-body">
                                        <div class="db-form-group"><label class="db-label">' . __('Group Metric') . '</label><select name="GroupByData3" class="db-select">'; GrpByDataOptions($_POST['groupbydata3'] ?? ''); echo '</select></div>
                                        <div class="db-form-group"><label class="db-label">' . __('Page Break After') . '</label><select name="NewPageAfter3" class="db-select"><option value="0" ' . (($_POST['newpageafter3'] ?? 0) == 0 ? 'selected' : '') . '>' . __('No') . '</option><option value="1" ' . (($_POST['newpageafter3'] ?? 0) == 1 ? 'selected' : '') . '>' . __('Yes') . '</option></select></div>
                                        <div class="db-grid-2">
                                            <div class="db-form-group"><label class="db-label">' . __('Range From') . '</label><input type="text" name="Lower3" class="db-input" value="' . ($_POST['lower3'] ?? '') . '" /></div>
                                            <div class="db-form-group"><label class="db-label">' . __('Range To') . '</label><input type="text" name="Upper3" class="db-input" value="' . ($_POST['upper3'] ?? '') . '" /></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Level 4 -->
                                <div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><span class="db-badge">4</span> ' . __('Quaternary Grouping') . '</div></div>
                                    <div class="db-card-body">
                                        <div class="db-form-group"><label class="db-label">' . __('Group Metric') . '</label><select name="GroupByData4" class="db-select">'; GrpByDataOptions($_POST['groupbydata4'] ?? ''); echo '</select></div>
                                        <div style="height: 52px;"></div> <!-- Spacer matching page break select -->
                                        <div class="db-grid-2">
                                            <div class="db-form-group"><label class="db-label">' . __('Range From') . '</label><input type="text" name="Lower4" class="db-input" value="' . ($_POST['lower4'] ?? '') . '" /></div>
                                            <div class="db-form-group"><label class="db-label">' . __('Range To') . '</label><input type="text" name="Upper4" class="db-input" value="' . ($_POST['upper4'] ?? '') . '" /></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top: var(--space-8); text-align: center;">
                                <button type="submit" name="submit" class="db-btn db-btn-primary" style="padding: 12px 40px; font-size: 1.1rem; border-radius: var(--radius-lg);">
                                    <i class="fas fa-save"></i> ' . (isset($SelectedReport) ? __('Commit Engineering Changes') : __('Engineer Report Design')) . '
                                </button>
                            </div>
                          </form>';
                } else {
                    echo '<!-- Empty State -->
                          <div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                            <div class="db-card-body">
                                <i class="fas fa-ruler-combined" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                <h2 class="text-muted">' . __('Report Architect Workspace') . '</h2>
                                <p>' . __('Select an existing design from the Design Portfolio or click "Architect New Report" to start a new sales engineered analysis.') . '</p>
                            </div>
                          </div>';
                }
    echo '  </main>
        </div>
      </div>';

include(__DIR__ . '/includes/footer.php');
