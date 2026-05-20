<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Maintain Journal Templates');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLJournals';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['delete'])) {
	$Result = DB_query("DELETE FROM jnltmpldetails WHERE templateid='" . $_GET['delete'] . "'");
	$Result = DB_query("DELETE FROM jnltmplheader WHERE templateid='" . $_GET['delete'] . "'");
	prnMsg(__('The GL journal template has been removed from the database'), 'success');
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 300px; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-green { background: #dcfce7; color: #166534; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Recurring') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

$SQL = "SELECT templateid, templatedescription, journaltype FROM jnltmplheader";
$Result = DB_query($SQL);

if (DB_num_rows($Result) == 0) {
	prnMsg(__('There are no templates stored in the database.'), 'warn');
} else {
    echo '<div class="db-layout">';
    
    // MAIN CONTENT
    echo '<main class="db-main">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-copy" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Available Templates') . '</h3></div>';
    echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>' . __('ID') . '</th><th>' . __('Description') . '</th><th>' . __('Type') . '</th><th style="text-align:right">' . __('Actions') . '</th></tr></thead><tbody>';

    while ($MyRow = DB_fetch_array($Result)) {
        $JournalType = ($MyRow['journaltype'] == 0 ? __('Normal') : __('Reversing'));
        $TypeBadge = ($MyRow['journaltype'] == 0 ? 'db-badge' : 'db-badge db-badge-green');
        
        echo '<tr>
                <td style="font-weight:700; color:var(--db-primary-dark);">', $MyRow['templateid'], '</td>
                <td>', $MyRow['templatedescription'], '</td>
                <td><span class="'.$TypeBadge.'">', $JournalType, '</span></td>
                <td style="text-align:right;"><a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; color:#dc2626; width:auto;" href="', basename(__FILE__), '?delete=', urlencode($MyRow['templateid']), '" onclick="return confirm(\'' . __('Delete this template?') . '\');"><i class="fas fa-trash"></i></a></td>
            </tr>';
    }
    echo '</tbody></table></div></div></main>';

    // SIDEBAR
    echo '<aside class="db-aside">
            <div class="db-card">
                <div class="db-card-header"><i class="fas fa-info-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Information') . '</h3></div>
                <div class="db-card-body">
                    <p style="font-size:0.8rem; color:#64748b; line-height:1.5; margin:0;">
                        Journal templates allow you to save standard journal structures for frequent reuse. Managing them here allows for cleanup of obsolete templates.
                    </p>
                    <a href="GLJournal.php" class="db-btn db-btn-outline" style="margin-top:1rem; width:100%; border-color:var(--db-primary); color:var(--db-primary)">
                        <i class="fas fa-pen-nib"></i> ' . __('Create Journal') . '
                    </a>
                </div>
            </div>
          </aside>';
    echo '</div>'; // layout
}

echo '</div>'; // db-page

include(__DIR__ . '/includes/footer.php');
?>
