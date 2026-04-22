<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Assignment of Cash to Petty Cash Tab');
$ViewTopic = 'PettyCash';
$BookMark = 'CashAssignment';

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-hover: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-page: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-page) !important; color: var(--text-main); font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border-color);
        margin: -25px -40px 30px -40px;
        padding: 18px 40px;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .premium-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .breadcrumb a { color: var(--primary); text-decoration: none; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Layout Grid */
    .db-grid { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
    @media (max-width: 1100px) { .db-grid { grid-template-columns: 1fr; } }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; height: 100%; }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #fdfdfd; }
    .db-card-title { font-size: 1rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }

    /* Metrics Bar */
    .metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 30px; }
    .metric-card { padding: 22px; background: white; border-radius: 14px; border: 1px solid var(--border-color); display: flex; flex-direction: column; }
    .metric-label { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
    .metric-value { font-size: 1.7rem; font-weight: 900; color: #0f172a; }
    .metric-sub { font-size: 0.85rem; margin-top: 4px; font-weight: 500; }

    /* Table Styles */
    .table-container { overflow-x: auto; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f1f5f9 !important; padding: 14px 20px !important; border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important; font-size: 0.75rem !important; text-transform: uppercase !important; font-weight: 800 !important; color: #475569 !important;
    }
    table.selection td { padding: 16px 20px !important; font-size: 0.85rem !important; border-bottom: 1px solid #f1f5f9 !important; color: #334155; }
    .type-pill { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; }
    .type-assign { background: #dcfce7; color: #166534; }
    .type-expense { background: #fee2e2; color: #991b1b; }

    /* Forms */
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; outline: none; transition: border-color 0.2s; box-sizing: border-box; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }

    .btn-architect { 
        display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 10px; 
        font-size: 0.9rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; width: 100%;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1.5px solid #d1d5db; color: #475569; width: auto; font-size: 0.8rem; padding: 8px 16px; }
    .btn-danger { color: #ef4444; background: rgba(239, 68, 68, 0.1); padding: 6px 12px; font-size: 0.75rem; }
    .btn-danger:hover { background: #ef4444; color: white; }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- Legacy Context Logic (Preserved) ---
if (isset($_POST['Date'])){ $_POST['Date'] = ConvertSQLDate($_POST['Date']); }

if (isset($_POST['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_GET['SelectedTabs']);
}
if (isset($_POST['SelectedIndex'])) {
	$SelectedIndex = $_POST['SelectedIndex'];
} elseif (isset($_GET['SelectedIndex'])) {
	$SelectedIndex = $_GET['SelectedIndex'];
}
if (isset($_POST['Days'])) {
	$Days = $_POST['Days'];
} elseif (isset($_GET['Days'])) {
	$Days = $_GET['Days'];
}

echo '<div class="db-page">';

if (!isset($SelectedTabs)) {
    // --- Step 1: Selection View ---
    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Management') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    
    <div style="max-width: 550px; margin: 40px auto;">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Select Petty Cash Tab') . '</div>
            </div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="form-group">
                        <label class="form-label">' . __('Assign cash to petty cash tab') . '</label>
                        <select name="SelectedTabs" class="form-control">';
                        $SQL = "SELECT tabcode FROM pctabs WHERE assigner='" . $_SESSION['UserID'] . "' ORDER BY tabcode";
                        $Result = DB_query($SQL);
                        while ($MyRow = DB_fetch_array($Result)) {
                            echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
                        }
                    echo '</select>
                    </div>
                    <button type="submit" name="Process" class="btn-architect btn-primary">' . __('Load Tab Management') . '</button>
                </form>
            </div>
        </div>
    </div>';
} else {
    // --- Step 2: Main Dashboard View ---
    
    // Header
    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('All Tabs') . '</a> / ' . $SelectedTabs . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
            <div class="header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="btn-architect btn-outline">' . __('Switch Tab') . '</a>
            </div>
        </div>
    </div>';

    // Fetch tab meta for metrics
    $SQLLimit = "SELECT pctabs.tablimit, pctabs.currency, currencies.decimalplaces 
                FROM pctabs, currencies 
                WHERE pctabs.currency = currencies.currabrev AND pctabs.tabcode='" . $SelectedTabs . "'";
    $ResultLimit = DB_query($SQLLimit);
    $LimitMeta = DB_fetch_array($ResultLimit);
    $CurrentBalance = PettyCashTabCurrentBalance($SelectedTabs);
    $ExceedsLimit = $CurrentBalance > $LimitMeta['tablimit'];

    // Metrics Row
    echo '<div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">' . __('Current Balance') . '</div>
                <div class="metric-value" style="color: ' . ($ExceedsLimit ? 'var(--rose)' : 'var(--text-main)') . '; font-family: \'JetBrains Mono\', monospace;">' . locale_number_format($CurrentBalance, $LimitMeta['decimalplaces']) . ' ' . $LimitMeta['currency'] . '</div>
                <div class="metric-sub" style="color: ' . ($ExceedsLimit ? 'var(--rose)' : 'var(--primary)') . ';">' . ($ExceedsLimit ? __('Over Limit') : __('Within Limit')) . '</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">' . __('Tab Cash Limit') . '</div>
                <div class="metric-value">' . locale_number_format($LimitMeta['tablimit'], $LimitMeta['decimalplaces']) . ' ' . $LimitMeta['currency'] . '</div>
                <div class="metric-sub" style="color: #64748b;">' . __('Pre-defined maximum') . '</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">' . __('Tab Identifier') . '</div>
                <div class="metric-value" style="color: var(--primary);">' . $SelectedTabs . '</div>
                <div class="metric-sub" style="color: var(--text-muted);">' . __('Active Reference') . '</div>
            </div>
          </div>';

    // Legacy Processing (Assignments/Deletes)
    if (isset($_POST['submit'])) {
        $InputError = 0;
        if ($_POST['Amount'] == 0) { $InputError = 1; prnMsg(__('The Amount must be input'), 'error'); }
        if ($_POST['CurrentAmount'] > $LimitMeta['tablimit']) {
            prnMsg(__('Balance exceeds limit. Clear expenses before additional assignments.'), 'warning');
        }
        
        if ($InputError == 0) {
            if (isset($SelectedIndex)) {
                $SQL = "UPDATE pcashdetails SET date='" . FormatDateForSQL($_POST['Date']) . "', amount='" . filter_number_format($_POST['Amount']) . "', notes='" . $_POST['Notes'] . "' WHERE counterindex='$SelectedIndex'";
                $Msg = __('Assignment updated successfully');
            } else {
                $SQL = "INSERT INTO pcashdetails (tabcode, date, codeexpense, amount, authorized, posted, notes) VALUES ('$SelectedTabs','" . FormatDateForSQL($_POST['Date']) . "','ASSIGNCASH','" . filter_number_format($_POST['Amount']) . "','1000-01-01','0','" . $_POST['Notes'] . "')";
                $Msg = __('Cash assigned successfully');
            }
            DB_query($SQL);
            prnMsg($Msg, 'success');
            unset($SelectedIndex); unset($_POST['Amount']); unset($_POST['Notes']);
        }
    } elseif (isset($_GET['delete'])) {
        DB_query("DELETE FROM pcashdetails WHERE counterindex='$SelectedIndex'");
        prnMsg(__('Entry deleted'), 'success');
        unset($SelectedIndex);
    }

    echo '<div class="db-grid">';

    // Left Column: History Table
    echo '<div class="db-main-content">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('Tab Movement History') . '</div>
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display: flex; gap: 8px;">
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        <input type="text" name="Days" value="' . ($Days ?? 30) . '" class="form-control" style="width: 60px; padding: 4px 8px; font-size: 0.8rem; border-radius: 6px;" />
                        <button type="submit" name="Go" class="btn-architect btn-outline">' . __('Days') . '</button>
                    </form>
                </div>
                <div class="table-container">';
                
                $SQL = "SELECT * FROM pcashdetails WHERE tabcode='$SelectedTabs' AND date >=DATE_SUB(CURDATE(), INTERVAL " . ($Days ?? 30) . " DAY) ORDER BY date DESC, counterindex DESC";
                $Result = DB_query($SQL);
                
                echo '<table class="selection">
                        <thead>
                            <tr>
                                <th>' . __('Date') . '</th>
                                <th>' . __('Movement Type') . '</th>
                                <th>' . __('Amount') . '</th>
                                <th>' . __('Notes / Purpose') . '</th>
                                <th style="text-align: right;">' . __('Actions') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
                while ($MyRow = DB_fetch_array($Result)) {
                    $isAssign = ($MyRow['codeexpense'] == 'ASSIGNCASH');
                    $isAuth = ($MyRow['authorized'] != '1000-01-01' && $MyRow['authorized'] != '0000-00-00');
                    $pillClass = $isAssign ? 'type-assign' : 'type-expense';
                    $pillText = $isAssign ? __('ASSIGNCASH') : $MyRow['codeexpense'];
                    
                    echo '<tr>
                            <td class="date">' . ConvertSQLDate($MyRow['date']) . '</td>
                            <td><span class="type-pill ' . $pillClass . '">' . $pillText . '</span></td>
                            <td style="font-family: \'JetBrains Mono\', monospace; font-weight: 800; color: ' . ($isAssign ? 'var(--primary)' : 'var(--rose)') . ';">' . ($isAssign ? '+' : '-') . locale_number_format($MyRow['amount'], $LimitMeta['decimalplaces']) . '</td>
                            <td>
                                <div style="font-weight: 600;">' . $MyRow['purpose'] . '</div>
                                <div style="font-size: 0.75rem; color: #64748b;">' . $MyRow['notes'] . '</div>
                            </td>
                            <td style="text-align: right;">';
                    if ($isAssign && !$isAuth) {
                        echo '<div style="display: flex; gap: 8px; justify-content: flex-end;">
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedIndex=' . $MyRow['counterindex'] . '&SelectedTabs=' . $SelectedTabs . '&Days=' . ($Days ?? 30) . '&edit=yes" class="btn-architect btn-outline" style="padding: 4px 10px; font-weight: 700;">' . __('Edit') . '</a>
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedIndex=' . $MyRow['counterindex'] . '&SelectedTabs=' . $SelectedTabs . '&Days=' . ($Days ?? 30) . '&delete=yes" class="btn-architect btn-danger" style="font-weight: 700;" onclick="return confirm(\'' . __('Confirm delete?') . '\');">' . __('Delete') . '</a>
                              </div>';
                    } else {
                        echo '<span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">' . ($isAuth ? __('Authorized') : __('Expense Row')) . '</span>';
                    }
                    echo '</td></tr>';
                }
                echo '</tbody></table></div></div></div>';

    // Right Column: Sidebar Form
    echo '<div class="db-sidebar">
            <div class="db-card" style="position: sticky; top: 110px;">
                <div class="db-card-header">
                    <div class="db-card-title">' . (isset($_GET['edit']) ? __('Edit Assignment') : __('New Cash Assignment')) . '</div>
                </div>
                <div class="db-card-body">
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        <input type="hidden" name="CurrentAmount" value="' . $CurrentBalance . '" />
                        <input type="hidden" name="Days" value="' . ($Days ?? 30) . '" />';
                        
                        if (isset($_GET['edit'])) {
                            $EditRes = DB_query("SELECT * FROM pcashdetails WHERE counterindex='$SelectedIndex'");
                            $EditRow = DB_fetch_array($EditRes);
                            $_POST['Date'] = ConvertSQLDate($EditRow['date']);
                            $_POST['Amount'] = $EditRow['amount'];
                            $_POST['Notes'] = $EditRow['notes'];
                            echo '<input type="hidden" name="SelectedIndex" value="' . $SelectedIndex . '" />';
                        }
                        
                        $defDate = isset($_POST['Date']) ? FormatDateForSQL($_POST['Date']) : date('Y-m-d');
                        $defAmount = isset($_POST['Amount']) ? $_POST['Amount'] : 0;
                        $defNotes = isset($_POST['Notes']) ? $_POST['Notes'] : '';

                        echo '
                        <div class="form-group">
                            <label class="form-label">' . __('Assignment Date') . '</label>
                            <input type="date" name="Date" class="form-control" value="' . $defDate . '" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">' . __('Amount') . ' (' . $LimitMeta['currency'] . ')</label>
                            <input type="text" name="Amount" class="form-control" value="' . locale_number_format($defAmount, $LimitMeta['decimalplaces']) . '" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">' . __('Reference / Notes') . '</label>
                            <textarea name="Notes" class="form-control" style="height: 100px;">' . $defNotes . '</textarea>
                        </div>
                        
                        <div style="display: flex; gap: 12px; margin-top: 30px;">
                            <button type="submit" name="submit" class="btn-architect btn-primary">' . (isset($_GET['edit']) ? __('Update Assignment') : __('Assign Cash')) . '</button>';
                            if (isset($_GET['edit'])) {
                                echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTabs=' . $SelectedTabs . '" class="btn-architect btn-outline" style="width: 100%;">' . __('Cancel') . '</a>';
                            }
                        echo '</div>
                    </form>
                </div>
            </div>
          </div>';

    echo '</div>'; // End db-grid
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
