<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Authorisation of Assigned Cash');
$ViewTopic = 'PettyCash';
$BookMark = 'AuthorizeCash';

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-dark: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-main: #f9fafb;
        --card-bg: #ffffff;
        --border-color: #e5e7eb;
        --text-main: #111827;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-main) !important; color: var(--text-main); font-family: "Inter", sans-serif; }
    .db-page { padding: 25px 35px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(14px);
        border-bottom: 1px solid var(--border-color);
        margin: -25px -35px 30px -35px;
        padding: 18px 35px;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.8rem; color: var(--text-muted); margin-bottom: 4px; display: flex; gap: 8px; }
    .breadcrumb a { color: var(--primary); text-decoration: none; font-weight: 550; }
    .page-title { font-size: 1.6rem; font-weight: 900; color: #0f172a; letter-spacing: -0.025em; }

    /* Cards */
    .db-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 25px;
    }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #fdfdfd; }
    .db-card-title { font-size: 1rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }

    /* Metrics Summary */
    .metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 30px; }
    .metric-card { padding: 22px; background: white; border-radius: 14px; border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
    .metric-label { font-size: 0.7rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
    .metric-value { font-size: 1.7rem; font-weight: 900; color: var(--primary); }

    /* Tables */
    .table-container { overflow-x: auto; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f8fafc !important; 
        color: #475569 !important; 
        padding: 14px 20px !important;
        border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
    }
    table.selection td { 
        padding: 16px 20px !important; 
        font-size: 0.85rem !important; 
        border-bottom: 1px solid #f1f5f9 !important;
        color: #334155;
    }
    .status-badge { padding: 4px 10px; border-radius: 6px; font-weight: 750; font-size: 0.7rem; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-authorized { background: #dcfce7; color: #166534; }

    /* Architect Components */
    .architect-btn {
        display: inline-flex; align-items: center; justify-content: center; padding: 12px 28px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1.5px solid #d1d5db; color: #475569; }

    .action-bar {
        position: sticky; bottom: 25px; background: rgba(255,255,255,0.85); backdrop-filter: blur(10px);
        padding: 18px 35px; border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 -6px 15px rgba(0,0,0,0.06);
        display: flex; justify-content: flex-end; z-index: 100; margin-top: 25px;
    }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- Legacy Logic Blocks (Preserved) ---
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
	$Days = filter_number_format($_POST['Days']);
} elseif (isset($_GET['Days'])) {
	$Days = filter_number_format($_GET['Days']);
}

echo '<div class="db-page">';

if (!isset($SelectedTabs) || (isset($_POST['Process']) && $SelectedTabs == '')) {
    // --- Selection Dashboard ---
    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Authorisation') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    
    <div style="max-width: 550px; margin: 45px auto;">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Load Assignment History') . '</div>
            </div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div style="margin-bottom: 25px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 750; color: #475569; margin-bottom: 8px;">' . __('Target Petty Cash Tab') . '</label>
                        <select required="required" name="SelectedTabs" style="width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #d1d5db; font-size: 1rem;">';
                        $SQL = "SELECT tabcode FROM pctabs WHERE authorizer='" . $_SESSION['UserID'] . "' ORDER BY tabcode";
                        $Result = DB_query($SQL);
                        while ($MyRow = DB_fetch_array($Result)) {
                            echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
                        }
                    echo '</select>
                    </div>
                    <button type="submit" name="Process" class="architect-btn btn-primary" style="width: 100%;">' . __('Access Dashboard') . '</button>
                </form>
            </div>
        </div>
    </div>';
} else {
    // --- Main Authorization Dashboard ---
    if (!isset($Days)) $Days = 30;
	$CurrentBalance = PettyCashTabCurrentBalance($SelectedTabs);

    // Submission Logic
    if (isset($_POST['Submit']) && $_POST['Submit'] == __('Update')) {
        $AuthorizedCount = 0;
        // Re-fetch pending assignments to process individual checkboxes
        $SQL = "SELECT pcashdetails.*, pctabs.glaccountassignment, pctabs.glaccountpcash, pctabs.currency, currencies.rate
                FROM pcashdetails, pctabs, currencies
                WHERE pcashdetails.tabcode = pctabs.tabcode AND pctabs.currency = currencies.currabrev
                AND pcashdetails.tabcode = '$SelectedTabs' AND pcashdetails.codeexpense='ASSIGNCASH' AND pcashdetails.authorized='1000-01-01'";
        $Result = DB_query($SQL);
        while ($MyRow = DB_fetch_array($Result)) {
            if (isset($_POST[$MyRow['counterindex']])) {
                // ... legacy auth & GL logic preserved exactly ...
                $PeriodNo = GetPeriod(ConvertSQLDate($MyRow['date']));
                $Amount = $MyRow['rate'] == 1 ? $MyRow['amount'] : ($MyRow['amount'] / $MyRow['rate']);
                $Type = 2; $TypeNo = GetNextTransNo($Type);
                $Narrative = __('PettyCash') . ' - ' . $MyRow['tabcode'] . ' - ' . $MyRow['codeexpense'] . ' - ' . DB_escape_string($MyRow['notes']);
                
                DB_Txn_Begin();
                DB_Query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('$Type', '$TypeNo', '" . $MyRow['date'] . "', '$PeriodNo', '" . $MyRow['glaccountassignment'] . "', '$Narrative', '" . -$Amount . "')", '', '', true);
                DB_Query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('$Type', '$TypeNo', '" . $MyRow['date'] . "', '$PeriodNo', '" . $MyRow['glaccountpcash'] . "', '$Narrative', '$Amount')", '', '', true);
                
                DB_query("INSERT INTO banktrans (transno, type, bankact, ref, functionalexrate, transdate, banktranstype, amount, currcode) VALUES ('" . GetNextTransNo(2) . "', 1, '" . $MyRow['glaccountassignment'] . "', '$Narrative', '" . $MyRow['rate'] . "', '" . $MyRow['date'] . "', 'Cash', '" . -$MyRow['amount'] . "', '" . $MyRow['currency'] . "')", '', '', true);
                
                DB_query("UPDATE pcashdetails SET authorized = CURRENT_DATE, posted = 1 WHERE counterindex = '" . $MyRow['counterindex'] . "'", '', '', true);
                DB_Txn_Commit();
                $AuthorizedCount++;
            }
        }
        if ($AuthorizedCount > 0) prnMsg($AuthorizedCount . ' ' . __('Assignments authorised successfully'), 'success');
    }

    echo '
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Select Tab') . '</a> / ' . $SelectedTabs . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
            <div class="header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="architect-btn btn-outline">' . __('Change Selection') . '</a>
            </div>
        </div>
    </div>';

    echo '<div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">' . __('Current Tab Balance') . '</div>
                <div class="metric-value">' . locale_number_format($CurrentBalance, 2) . '</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">' . __('Active Mode') . '</div>
                <div class="metric-value" style="color: var(--slate); font-size: 1.1rem; font-weight: 700;">' . __('Cash Assignment Auth') . '</div>
            </div>
          </div>';

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />';

    echo '<div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Cash Assignments to Authorise') . '</div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">' . __('Lookback') . ':</span>
                    <input type="text" name="Days" value="' . $Days . '" style="width: 50px; padding: 6px 10px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 0.85rem;" />
                    <button type="submit" name="Go" class="architect-btn btn-outline" style="padding: 6px 14px; font-size: 0.8rem;">' . __('Go') . '</button>
                </div>
            </div>
            <div class="table-container">';

    $SQL = "SELECT pcashdetails.*, currencies.decimalplaces FROM pcashdetails, pctabs, currencies
            WHERE pcashdetails.tabcode = pctabs.tabcode AND pctabs.currency = currencies.currabrev
            AND pcashdetails.tabcode = '$SelectedTabs' AND pcashdetails.date >= DATE_SUB(CURDATE(), INTERVAL '$Days' DAY)
            AND pcashdetails.codeexpense='ASSIGNCASH' ORDER BY pcashdetails.date DESC, pcashdetails.counterindex ASC";
    $Result = DB_query($SQL);
    
    echo '<table class="selection">
            <thead>
                <tr>
                    <th>' . __('Date') . '</th>
                    <th>' . __('Amount') . '</th>
                    <th>' . __('Reference / Notes') . '</th>
                    <th>' . __('Status') . '</th>
                    <th style="width: 60px; text-align: center;">' . __('Select') . '</th>
                </tr>
            </thead>
            <tbody>';

    while ($Row = DB_fetch_array($Result)) {
        $isAuth = ($Row['authorized'] != '1000-01-01');
        $statusClass = $isAuth ? 'status-authorized' : 'status-pending';
        $statusText = $isAuth ? __('Authorized on') . ' ' . ConvertSQLDate($Row['authorized']) : __('Pending Authorization');
        
        echo '<tr>
                <td style="font-weight: 550;">' . ConvertSQLDate($Row['date']) . '</td>
                <td style="font-family: \'JetBrains Mono\', monospace; font-weight: 900; color: var(--primary); font-size: 1rem;">' . locale_number_format($Row['amount'], $Row['decimalplaces']) . '</td>
                <td style="color: var(--slate); font-size: 0.8rem;">' . $Row['notes'] . '</td>
                <td><span class="status-badge ' . $statusClass . '">' . $statusText . '</span></td>
                <td style="text-align: center;">';
                if ($isAuth) {
                    echo '<div style="color:#059669;"><svg style="width:22px; height:22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>';
                } else {
                    echo '<input type="checkbox" name="' . $Row['counterindex'] . '" style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary);" />';
                }
        echo '</td></tr>';
    }
    echo '</tbody></table></div></div>';

    echo '<div class="action-bar">
            <button type="submit" name="Submit" value="' . __('Update') . '" class="architect-btn btn-primary">
                <svg style="width:20px; height:20px; margin-right:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ' . __('Confirm Selected Authorizations') . '
            </button>
          </div>';
    echo '</form>';
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
