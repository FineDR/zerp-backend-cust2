<?php

// Assign cash from one tab to another.

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'PettyCash';
$BookMark = 'CashAssignment';
$Title = __('Assignment of Cash from Tab to Tab');

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-dark: #047857;
        --emerald: #10b981;
        --rose: #e11d48;
        --amber: #f59e0b;
        --slate: #64748b;
        --bg-main: #f8fafc;
        --border-color: #e2e8f0;
        --card-bg: #ffffff;
    }
    body { background-color: var(--bg-main) !important; font-family: "Inter", sans-serif; color: #1e293b; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border-color);
        margin: -30px -30px 30px -30px;
        padding: 20px 30px;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .header-inner { display: flex; align-items: center; justify-content: space-between; }
    .breadcrumb { font-size: 0.8rem; color: #64748b; margin-bottom: 4px; }
    .breadcrumb a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .page-title { font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.025em; }

    /* Layout */
    .db-grid { display: grid; grid-template-columns: 1fr 400px; gap: 24px; }
    @media (max-width: 1200px) { .db-grid { grid-template-columns: 1fr; } }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
    .db-card-header { padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #fdfdfd; }
    .db-card-title { font-size: 1rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }

    /* Dual Metrics Row */
    .metrics-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    @media (max-width: 768px) { .metrics-container { grid-template-columns: 1fr; } }
    .metric-group { padding: 20px; background: white; border-radius: 16px; border: 1px solid var(--border-color); }
    .metric-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
    .metric-label { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
    .metric-value { font-size: 1.8rem; font-weight: 900; color: #0f172a; }
    .metric-sub { font-size: 0.85rem; font-weight: 500; margin-top: 5px; }

    /* Table */
    .table-container { overflow-x: auto; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f8fafc !important; padding: 14px 20px !important; border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important; font-size: 0.75rem !important; text-transform: uppercase !important; font-weight: 800 !important; color: #475569 !important;
    }
    table.selection td { padding: 16px 20px !important; font-size: 0.85rem !important; border-bottom: 1px solid #f1f5f9 !important; }

    /* Form UI */
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; box-sizing: border-box; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }

    .btn-architect { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; width: 100%; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1.5px solid #d1d5db; color: #475569; font-size: 0.85rem; padding: 10px 20px; width: auto; }
    
    .status-pill { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; display: inline-block; }
    .status-emerald { background: #dcfce7; color: #065f46; }
    .status-rose { background: #fee2e2; color: #991b1b; }
</style>';

include(__DIR__ . '/includes/header.php');

// --- Legacy Context Logic (Preserved) ---
if (isset($_POST['Date'])){ $_POST['Date'] = ConvertSQLDate($_POST['Date']); }

if (isset($_POST['SelectedTabs'])){
	$SelectedTabs = mb_strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])){
	$SelectedTabs = mb_strtoupper($_GET['SelectedTabs']);
}
if (isset($_POST['SelectedTabsTo'])){
	$SelectedTabsTo = mb_strtoupper($_POST['SelectedTabsTo']);
}
if (isset($_POST['Days'])){
	$Days = $_POST['Days'];
} elseif (isset($_GET['Days'])){
	$Days = $_GET['Days'];
}

echo '<div class="db-page">';

if (!isset($SelectedTabs)) {
    // --- Step 1: Selection View ---
    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Inter-Tab Transfer') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    
    <div style="max-width: 600px; margin: 40px auto;">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Select Transfer Pair') . '</div>
            </div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="form-group">
                        <label class="form-label">' . __('From Petty Cash Tab') . '</label>
                        <select name="SelectedTabs" class="form-control">';
                        $SQL = "SELECT tabcode FROM pctabs WHERE assigner = '" . $_SESSION['UserID'] . "' ORDER BY tabcode";
                        $Result = DB_query($SQL);
                        while ($Row = DB_fetch_array($Result)) echo '<option value="' . $Row['tabcode'] . '">' . $Row['tabcode'] . '</option>';
                    echo '</select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">' . __('To Petty Cash Tab') . '</label>
                        <select name="SelectedTabsTo" class="form-control">';
                        DB_data_seek($Result, 0);
                        while ($Row = DB_fetch_array($Result)) echo '<option value="' . $Row['tabcode'] . '">' . $Row['tabcode'] . '</option>';
                    echo '</select>
                    </div>
                    <button type="submit" name="Process" class="btn-architect btn-primary" style="margin-top: 10px;">' . __('Accept Transfer Setup') . '</button>
                </form>
            </div>
        </div>
    </div>';
} else {
    // --- Step 2: Dashboard View ---
    
    // Header
    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('All Transfers') . '</a> / ' . $SelectedTabs . ' → ' . $SelectedTabsTo . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
            <div class="header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="btn-architect btn-outline">' . __('Reset Pair') . '</a>
            </div>
        </div>
    </div>';

    // Fetch tab meta and balances
    $SQLMeta = "SELECT pctabs.tablimit, pctabs.tabcode, currencies.currabrev, currencies.decimalplaces 
                FROM pctabs, currencies 
                WHERE pctabs.currency = currencies.currabrev AND pctabs.tabcode IN ('$SelectedTabs', '$SelectedTabsTo')";
    $ResMeta = DB_query($SQLMeta);
    $TabMeta = []; while ($r = DB_fetch_array($ResMeta)) $TabMeta[$r['tabcode']] = $r;

    $balFrom = PettyCashTabCurrentBalance($SelectedTabs);
    $balTo = PettyCashTabCurrentBalance($SelectedTabsTo);

    // Metrics Container
    echo '<div class="metrics-container">
            <div class="metric-group">
                <div class="metric-header">
                    <div style="background:rgba(5, 150, 105, 0.1); padding:8px; border-radius:10px;"><svg style="width:20px; height:20px; color:var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div class="metric-label">' . __('From Tab') . ': ' . $SelectedTabs . '</div>
                </div>
                <div class="metric-value">' . locale_number_format($balFrom, $TabMeta[$SelectedTabs]['decimalplaces']) . ' ' . $TabMeta[$SelectedTabs]['currabrev'] . '</div>
                <div class="metric-sub" style="color: #64748b;">' . __('Limit') . ': ' . locale_number_format($TabMeta[$SelectedTabs]['tablimit'], $TabMeta[$SelectedTabs]['decimalplaces']) . '</div>
            </div>
            <div class="metric-group">
                <div class="metric-header">
                    <div style="background:rgba(16,185,129,0.1); padding:8px; border-radius:10px;"><svg style="width:20px; height:20px; color:var(--emerald);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <div class="metric-label">' . __('To Tab') . ': ' . $SelectedTabsTo . '</div>
                </div>
                <div class="metric-value">' . locale_number_format($balTo, $TabMeta[$SelectedTabsTo]['decimalplaces']) . ' ' . $TabMeta[$SelectedTabsTo]['currabrev'] . '</div>
                <div class="metric-sub" style="color: #64748b;">' . __('Limit') . ': ' . locale_number_format($TabMeta[$SelectedTabsTo]['tablimit'], $TabMeta[$SelectedTabsTo]['decimalplaces']) . '</div>
            </div>
          </div>';

    // Submission Logic
    if (isset($_POST['submit'])) {
        $InputError = 0; $Amt = filter_number_format($_POST['Amount']);
        if ($Amt == 0) { $InputError = 1; prnMsg(__('The Amount must be input'), 'error'); }
        if (($balFrom + $Amt) > $TabMeta[$SelectedTabs]['tablimit']) { $InputError = 1; prnMsg(__('Source tab would exceed limit.'), 'error'); }
        if (($balTo - $Amt) > $TabMeta[$SelectedTabsTo]['tablimit']) { $InputError = 1; prnMsg(__('Destination tab would exceed limit.'), 'error'); }

        if ($InputError == 0) {
            $SQL = "INSERT INTO pcashdetails (tabcode, date, codeexpense, amount, authorized, posted, notes) VALUES 
                    ('$SelectedTabs','" . FormatDateForSQL($_POST['Date']) . "','ASSIGNCASH','$Amt','1000-01-01','0','" . $_POST['Notes'] . "'),
                    ('$SelectedTabsTo','" . FormatDateForSQL($_POST['Date']) . "','ASSIGNCASH','-$Amt','1000-01-01','0','" . $_POST['Notes'] . "')";
            DB_query($SQL);
            prnMsg(__('Transfer completed successfully'), 'success');
            $balFrom += $Amt; $balTo -= $Amt; // Virtual update for UI
        }
    }

    echo '<div class="db-grid">';

    // Left Column: Source History
    echo '<div class="db-main">
            <div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('Source Tab Movements') . '</div>
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display: flex; gap: 8px;">
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        <input type="hidden" name="SelectedTabsTo" value="' . $SelectedTabsTo . '" />
                        <input type="text" name="Days" value="' . ($Days ?? 30) . '" class="form-control" style="width: 50px; padding: 4px; font-size: 0.8rem;" />
                        <button type="submit" name="Go" class="btn-architect btn-outline" style="padding: 4px 10px;">' . __('Days') . '</button>
                    </form>
                </div>
                <div class="table-container">';
                
                $SQLHis = "SELECT * FROM pcashdetails WHERE tabcode='$SelectedTabs' AND date >= DATE_SUB(CURDATE(), INTERVAL " . ($Days ?? 30) . " DAY) ORDER BY date DESC, counterindex DESC";
                $ResHis = DB_query($SQLHis);
                
                echo '<table class="selection">
                        <thead>
                            <tr>
                                <th>' . __('Date') . '</th>
                                <th>' . __('Type') . '</th>
                                <th>' . __('Amount') . '</th>
                                <th>' . __('Notes / Purpose') . '</th>
                                <th>' . __('Auth Status') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
                while ($Row = DB_fetch_array($ResHis)) {
                    $isAuth = ($Row['authorized'] != '1000-01-01');
                    $isAssign = ($Row['codeexpense'] == 'ASSIGNCASH');
                    echo '<tr>
                            <td class="date">' . ConvertSQLDate($Row['date']) . '</td>
                            <td><span class="status-pill ' . ($isAssign ? 'status-emerald' : 'status-rose') . '">' . $Row['codeexpense'] . '</span></td>
                            <td style="font-family: monospace; font-weight: 700;">' . locale_number_format($Row['amount'], $TabMeta[$SelectedTabs]['decimalplaces']) . '</td>
                            <td>' . $Row['purpose'] . '<div style="font-size:0.75rem; color:#64748b;">' . $Row['notes'] . '</div></td>
                            <td><span class="status-pill" style="background:#f1f5f9; color:var(--slate);">' . ($isAuth ? ConvertSQLDate($Row['authorized']) : __('Pending')) . '</span></td>
                          </tr>';
                }
                echo '</tbody></table></div></div></div>';

    // Right Column: Sidebar Form
    echo '<div class="db-sidebar">
            <div class="db-card" style="position: sticky; top: 115px;">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('New Transfer Assignment') . '</div>
                </div>
                <div class="db-card-body">
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        <input type="hidden" name="SelectedTabsTo" value="' . $SelectedTabsTo . '" />
                        <input type="hidden" name="CurrentAmount" value="' . $balFrom . '" />
                        <input type="hidden" name="SelectedTabsToAmt" value="' . $balTo . '" />
                        <input type="hidden" name="Days" value="' . ($Days ?? 30) . '" />

                        <div class="form-group">
                            <label class="form-label">' . __('Transfer Date') . '</label>
                            <input type="date" name="Date" class="form-control" value="' . date('Y-m-d') . '" required />
                        </div>
                        <div class="form-group">
                            <label class="form-label">' . __('Transfer Amount') . '</label>
                            <input type="text" name="Amount" class="form-control" value="0" required />
                            <div style="font-size: 0.7rem; color: #64748b; margin-top: 5px;">' . __('Moves from Source to Destination') . '</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">' . __('Reference / Notes') . '</label>
                            <textarea name="Notes" class="form-control" style="height: 80px;"></textarea>
                        </div>
                        
                        <button type="submit" name="submit" class="btn-architect btn-primary" style="margin-top: 20px;">
                            <svg style="width:20px; height:20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            ' . __('Confirm Transfer') . '
                        </button>
                    </form>
                </div>
            </div>
          </div>';

    echo '</div>'; // End db-grid
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
