<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Authorisation of Petty Cash Expenses');
$ViewTopic = 'PettyCash';
$BookMark = 'AuthorizeExpense';

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-hover: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-main) !important; color: var(--text-main); font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border-color);
        margin: -20px -30px 30px -30px;
        padding: 15px 30px;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .premium-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .breadcrumb a { color: var(--primary); text-decoration: none; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Cards */
    .db-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .db-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #fcfcfd; }
    .db-card-title { font-size: 0.95rem; font-weight: 700; color: #334155; }
    .db-card-body { padding: 20px; }

    /* Metrics Summary */
    .metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .metric-card { padding: 20px; background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .metric-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
    .metric-value { font-size: 1.5rem; font-weight: 800; color: var(--primary); }

    /* Tables */
    .table-container { overflow-x: auto; border-radius: 2px; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f8fafc !important; 
        color: #475569 !important; 
        padding: 12px 16px !important;
        border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
    }
    table.selection td { 
        padding: 14px 16px !important; 
        font-size: 0.85rem !important; 
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .status-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-authorized { background: #dcfce7; color: #166534; }

    /* Buttons & Form */
    .architect-btn {
        display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #475569; }

    .action-bar {
        position: sticky; bottom: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(8px);
        padding: 15px 30px; border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
        display: flex; justify-content: flex-end; z-index: 10; margin-top: 20px;
    }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

// --- Legacy Processing Logic (Preserved) ---
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
if (isset($_POST['Go'])) {
	if ($Days <= 0) {
		prnMsg(__('The number of days must be a positive number'), 'error');
		$Days = 30;
	}
}

echo '<div class="db-page">';

if (!isset($SelectedTabs) || (isset($_POST['Process']) && $SelectedTabs == '')) {
    // --- Step 1: Selection Dashboard ---
    echo '
    <div class="premium-header">
        <div class="premium-header-inner">
            <div>
                <div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Selection') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    
    <div style="max-width: 500px; margin: 40px auto;">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Authorize Tab Expenses') . '</div>
            </div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px;">' . __('Select Petty Cash Tab') . '</label>
                        <select required="required" name="SelectedTabs" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d1d5db;">';
                        $SQL = "SELECT tabcode FROM pctabs WHERE authorizerexpenses='" . $_SESSION['UserID'] . "' ORDER BY tabcode";
                        $Result = DB_query($SQL);
                        while ($MyRow = DB_fetch_array($Result)) {
                            echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
                        }
                    echo '</select>
                    </div>
                    <button type="submit" name="Process" class="architect-btn btn-primary" style="width: 100%;">' . __('Load Expenses') . '</button>
                </form>
            </div>
        </div>
    </div>';
} else {
    // --- Step 2: Main Authorization Dashboard ---
    if (!isset($Days)) $Days = 30;
	$SucessfullyAuthorized = 0;

    // Process Submissions First
    if (isset($_POST['Submit']) && $_POST['Submit'] == __('Update')) {
        // Re-fetch to process individual checkboxes
        $SQL = "SELECT pcashdetails.*, pctabs.glaccountassignment, pctabs.glaccountpcash, currencies.rate
                FROM pcashdetails, pctabs, currencies
                WHERE pcashdetails.tabcode = pctabs.tabcode AND pctabs.currency = currencies.currabrev
                AND pcashdetails.tabcode = '$SelectedTabs' AND pcashdetails.codeexpense<>'ASSIGNCASH'
                AND (pcashdetails.authorized='1000-01-01' OR pcashdetails.authorized='0000-00-00')";
        $Result = DB_query($SQL);
        while ($MyRow = DB_fetch_array($Result)) {
            if (isset($_POST[$MyRow['counterindex']])) {
                // ... legacy authorization logic preserved exactly ...
                $PeriodNo = GetPeriod(ConvertSQLDate($MyRow['date']));
                $TaxSumRes = DB_query("SELECT SUM(amount) as taxes FROM pcashdetailtaxes WHERE pccashdetail='" . $MyRow['counterindex'] . "'");
                $TaxSum = DB_fetch_array($TaxSumRes)['taxes'];
                
                $GrossAmount = $MyRow['rate'] == 1 ? $MyRow['amount'] : ($MyRow['amount'] / $MyRow['rate']);
                $NetAmount = $MyRow['rate'] == 1 ? ($MyRow['amount'] - $TaxSum) : (($MyRow['amount'] - $TaxSum) / $MyRow['rate']);
                $NetAmount = -$NetAmount;
                
                $Type = 1; $TypeNo = GetNextTransNo($Type);
                $AccountFrom = $MyRow['glaccountpcash'];
                $ExpAccRes = DB_query("SELECT glaccount FROM pcexpenses WHERE codeexpense = '" . $MyRow['codeexpense'] . "'");
                $AccountTo = DB_fetch_array($ExpAccRes)['glaccount'];
                
                $Narrative = __('PettyCash') . ' - ' . $MyRow['tabcode'] . ' - ' . $MyRow['codeexpense'] . ' - ' . DB_escape_string($MyRow['notes']);
                
                DB_Txn_Begin();
                DB_Query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('$Type', '$TypeNo', '" . $MyRow['date'] . "', '$PeriodNo', '$AccountFrom', '$Narrative', '$GrossAmount')", '', '', true);
                DB_Query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('$Type', '$TypeNo', '" . $MyRow['date'] . "', '$PeriodNo', '$AccountTo', '$Narrative', '$NetAmount')", '', '', true);
                
                $Tags = array();
                $TagsRes = DB_query("SELECT tag FROM pctags WHERE pccashdetail='" . $MyRow['counterindex'] . "'");
                while ($tr = DB_fetch_array($TagsRes)) $Tags[] = $tr['tag'];
                InsertGLTags($Tags);

                $TaxesRes = DB_query("SELECT * FROM pcashdetailtaxes WHERE pccashdetail='" . $MyRow['counterindex'] . "'");
                while ($tx = DB_fetch_array($TaxesRes)) {
                    DB_Query("INSERT INTO gltrans (type, typeno, trandate, periodno, account, narrative, amount) VALUES ('$Type', '$TypeNo', '" . $MyRow['date'] . "', '$PeriodNo', '" . $tx['purchtaxglaccount'] . "', '$Narrative', '" . -$tx['amount'] . "')", '', '', true);
                }
                DB_query("UPDATE pcashdetails SET authorized = CURRENT_DATE, posted = 1 WHERE counterindex = '" . $MyRow['counterindex'] . "'", '', '', true);
                DB_Txn_Commit();
                $SucessfullyAuthorized++;
            }
        }
        if ($SucessfullyAuthorized > 0) prnMsg($SucessfullyAuthorized . ' ' . __('Expenses have been correctly authorised'), 'success');
    }

    $CurrentBalance = PettyCashTabCurrentBalance($SelectedTabs);

    echo '
    <div class="premium-header">
        <div class="premium-header-inner">
            <div>
                <div class="breadcrumb"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Select Tab') . '</a> / ' . $SelectedTabs . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
            <div class="header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="architect-btn btn-outline">' . __('Change Tab') . '</a>
            </div>
        </div>
    </div>';

    echo '<div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">' . __('Current Balance') . '</div>
                <div class="metric-value">' . locale_number_format($CurrentBalance, 2) . '</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">' . __('Selected Mode') . '</div>
                <div class="metric-value" style="color: var(--slate); font-size: 1.2rem; font-weight: 700;">' . __('Expense Authorization') . '</div>
            </div>
          </div>';

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />';

    echo '<div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Pending Authorisations') . '</div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 0.8rem; color: #64748b;">' . __('In the last') . '</span>
                    <input type="text" name="Days" value="' . $Days . '" style="width: 50px; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;" />
                    <span style="font-size: 0.8rem; color: #64748b;">' . __('Days') . '</span>
                    <button type="submit" name="Go" class="architect-btn btn-outline" style="padding: 4px 12px; font-size: 0.8rem;">' . __('Update View') . '</button>
                </div>
            </div>
            <div class="table-container">';

    $SQL = "SELECT pcashdetails.*, currencies.decimalplaces
            FROM pcashdetails, pctabs, currencies
            WHERE pcashdetails.tabcode = pctabs.tabcode AND pctabs.currency = currencies.currabrev
            AND pcashdetails.tabcode = '$SelectedTabs' AND pcashdetails.date >= DATE_SUB(CURDATE(), INTERVAL '$Days' DAY)
            AND pcashdetails.codeexpense<>'ASSIGNCASH'
            ORDER BY pcashdetails.date DESC, pcashdetails.counterindex ASC";
    $Result = DB_query($SQL);
    
    echo '<table class="selection">
            <thead>
                <tr>
                    <th>' . __('Date') . '</th>
                    <th>' . __('Expense Code') . '</th>
                    <th>' . __('Gross Amount') . '</th>
                    <th>' . __('Purpose / Notes') . '</th>
                    <th>' . __('Status') . '</th>
                    <th style="width: 50px; text-align: center;">' . __('Approve') . '</th>
                </tr>
            </thead>
            <tbody>';

    while ($MyRow = DB_fetch_array($Result)) {
        $IsPending = ($MyRow['authorized'] == '1000-01-01' or $MyRow['authorized'] == '0000-00-00');
        $StatusClass = $IsPending ? 'status-pending' : 'status-authorized';
        $StatusText = $IsPending ? __('Pending') : __('Authorized on') . ' ' . ConvertSQLDate($MyRow['authorized']);
        
        echo '<tr>
                <td>' . ConvertSQLDate($MyRow['date']) . '</td>
                <td><div style="font-weight:700; color:#1e293b;">' . $MyRow['codeexpense'] . '</div></td>
                <td style="font-family: \'JetBrains Mono\', monospace; font-weight: 800; color: var(--primary);">' . locale_number_format($MyRow['amount'], $MyRow['decimalplaces']) . '</td>
                <td>
                    <div style="font-size:0.85rem; color:#1e293b; font-weight:600;">' . $MyRow['purpose'] . '</div>
                    <div style="font-size:0.75rem; color:#64748b;">' . $MyRow['notes'] . '</div>
                </td>
                <td><span class="status-badge ' . $StatusClass . '">' . $StatusText . '</span></td>
                <td style="text-align: center;">';
                if ($IsPending) {
                    echo '<input type="checkbox" name="' . $MyRow['counterindex'] . '" style="width: 20px; height: 20px; cursor: pointer; accent-color: var(--primary);" />';
                } else {
                    echo '<svg style="width:20px; height:20px; color:var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                }
        echo '</td></tr>';
    }
    echo '</tbody></table></div></div>';

    echo '<div class="action-bar">
            <button type="submit" name="Submit" value="' . __('Update') . '" class="architect-btn btn-primary">
                <svg style="width:18px; height:18px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                ' . __('Confirm Authorizations') . '
            </button>
          </div>';
    echo '</form>';
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
