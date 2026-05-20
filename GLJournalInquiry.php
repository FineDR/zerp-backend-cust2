<?php

require(__DIR__ . '/includes/session.php');

$Title = __('General Ledger Journal Inquiry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLJournalInquiry';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromTransDate'])){$_POST['FromTransDate'] = ConvertSQLDate($_POST['FromTransDate']);}
if (isset($_POST['ToTransDate'])){$_POST['ToTransDate'] = ConvertSQLDate($_POST['ToTransDate']);}

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; background: #fff; }
    .db-card-title { font-size: 0.75rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1.25rem; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: 0.2s; }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); background: #fff; }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: 0.2s; border: none; width: 100%; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
    
    .journal-group-header { background: #f8fafc; font-weight: 800; color: var(--db-primary-dark); }
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Inquiries</div>
    <h1 class="db-page-title">' . $Title . '</h1>
</header>';

if (!isset($_POST['Show'])) {
	$SQL = "SELECT typeid,systypes.typeno,typename FROM systypes INNER JOIN gltrans ON systypes.typeid=gltrans.type GROUP BY typeid";
	$Result = DB_query($SQL);
    $MaxJournal = 0;
    $typeOptions = '';
	while ($MyRow = DB_fetch_array($Result)) {
        $MaxJournal = ($MyRow['typeno'] > $MaxJournal) ? $MyRow['typeno'] : $MaxJournal;
		$typeOptions .= '<option value="' . $MyRow['typeid'] . '">' . __($MyRow['typename']) . '</option>';
	}

    $Dates = DB_fetch_array(DB_query("SELECT MIN(trandate) AS fromdate, MAX(trandate) AS todate FROM gltrans WHERE type=0"));
    $FromDate = ($Dates['fromdate']!='') ? $Dates['fromdate'] : date('Y-m-d');
    $ToDate = ($Dates['todate']!='') ? $Dates['todate'] : date('Y-m-d');

    echo '<div class="db-card" style="max-width: 650px;">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Inquiry Criteria') . '</h3></div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-field">
                    <label class="db-label">' . __('Transaction Type') . '</label>
                    <select name="TransType" class="db-select" autofocus>' . $typeOptions . '</select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">' . __('Journal From') . '</label><input type="number" name="NumberFrom" class="db-input" value="1" min="1" /></div>
                    <div class="db-field"><label class="db-label">' . __('Journal To') . '</label><input type="number" name="NumberTo" class="db-input" value="' . $MaxJournal . '" min="1" /></div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="db-field"><label class="db-label">' . __('Dated From') . '</label><input name="FromTransDate" type="date" class="db-input" value="' . $FromDate . '" /></div>
                    <div class="db-field"><label class="db-label">' . __('Dated To') . '</label><input name="ToTransDate" type="date" class="db-input" value="' . $ToDate . '" /></div>
                </div>
                <button type="submit" name="Show" class="db-btn db-btn-primary" style="margin-top:1rem;">' . __('Show Transactions') . '</button>
                </form>
            </div>
        </div>';

} else {
	$SQL = "SELECT gltrans.counterindex, gltrans.typeno, gltrans.trandate, gltrans.account, chartmaster.accountname, gltrans.narrative, gltrans.amount, gltrans.jobref FROM gltrans INNER JOIN chartmaster ON gltrans.account=chartmaster.accountcode WHERE gltrans.type='" . $_POST['TransType'] . "' AND gltrans.trandate>='" . FormatDateForSQL($_POST['FromTransDate']) . "' AND gltrans.trandate<='" . FormatDateForSQL($_POST['ToTransDate']) . "' AND gltrans.typeno>='" . $_POST['NumberFrom'] . "' AND gltrans.typeno<='" . $_POST['NumberTo'] . "' ORDER BY gltrans.typeno";
	$Result = DB_query($SQL);
    
    echo '<div class="db-main-grid">
        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Active Filters') . '</h3></div>
                <div class="db-card-body">
                    <div class="db-field"><label class="db-label">' . __('Journal Range') . '</label><div style="font-weight:700; font-size:0.8rem;">' . $_POST['NumberFrom'] . ' - ' . $_POST['NumberTo'] . '</div></div>
                    <div class="db-field"><label class="db-label">' . __('Date Range') . '</label><div style="font-weight:700; font-size:0.8rem;">' . $_POST['FromTransDate'] . ' to ' . $_POST['ToTransDate'] . '</div></div>
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <button type="submit" name="Return" class="db-btn db-btn-ghost">' . __('Change Search') . '</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="db-column">';
        if (DB_num_rows($Result) == 0) {
            echo '<div class="db-card"><div class="db-card-body">' . __('No transactions found.') . '</div></div>';
        } else {
            echo '<div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Journal Entries') . '</h3></div>
                <div class="db-card-body" style="padding:0;">
                    <div class="db-table-container">
                        <table class="db-table">
                            <thead><tr><th>Journal / Act</th><th>Account / Narrative</th><th style="text-align:right;">Amount (' . $_SESSION['CompanyRecord']['currencydefault'] . ')</th><th>Tag / Detail</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>';

            $LastJournal = 0;
            while ($MyRow = DB_fetch_array($Result)) {
                $TagsRes = DB_query("SELECT gltags.tagref, tags.tagdescription FROM gltags INNER JOIN tags ON gltags.tagref=tags.tagref WHERE gltags.counterindex='" . $MyRow['counterindex'] . "'");
                $TagText = ''; while ($TR = DB_fetch_array($TagsRes)) $TagText .= '<div class="db-badge">' . $TR['tagref'] . ' - ' . $TR['tagdescription'] . '</div>';

                if ($MyRow['typeno'] != $LastJournal) {
                    echo '<tr class="journal-group-header">
                            <td colspan="4"><span style="font-size:0.65rem; color:var(--db-text-muted);">JOURNAL #</span>' . $MyRow['typeno'] . ' <span style="margin-left:1rem; font-size:0.7rem; font-weight:400; color:var(--db-text-muted);">' . ConvertSQLDate($MyRow['trandate']) . '</span></td>
                            <td style="text-align:right;">
                                <a href="' . $RootPath . '/PDFGLJournal.php?JournalNo=' . $MyRow['typeno'] . '&Type=' . $_POST['TransType'] . '&PDF=True" target="_blank" style="margin-right:10px;"><i class="fas fa-file-pdf"></i> PDF</a>
                                <a href="' . $RootPath . '/PDFGLJournal.php?JournalNo=' . $MyRow['typeno'] . '&Type=' . $_POST['TransType'] . '&View=True" target="_blank"><i class="fas fa-eye"></i> View</a>
                            </td>
                          </tr>';
                    $LastJournal = $MyRow['typeno'];
                }

                $CheckRow = DB_fetch_row(DB_query("SELECT count(*) FROM glaccountusers WHERE accountcode= '" . $MyRow['account'] . "' AND userid = '" . $_SESSION['UserID'] . "' AND canview = '1'"));
                if ($CheckRow[0] > 0) {
                    $accCode = $MyRow['account']; $accName = $MyRow['accountname'];
                } else {
                    $accCode = __('Others'); $accName = __('Other GL Accounts');
                }

                echo '<tr>
                        <td><b>' . $accCode . '</b></td>
                        <td><div>' . $accName . '</div><small style="color:var(--db-text-muted);">' . $MyRow['narrative'] . '</small></td>
                        <td style="text-align:right; font-weight:900;">' . locale_number_format($MyRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                        <td>' . $TagText . '</td>
                        <td></td>
                      </tr>';
            }
            echo '</tbody></table></div></div></div>';
        }
        echo '</div>
    </div>';
}

echo '</div></div>';
include(__DIR__ . '/includes/footer.php');
?>
