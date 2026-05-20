<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Maintain General Ledger Tags');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLTags';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedTag'])) {
	if (isset($_GET['Action']) && $_GET['Action'] == 'delete') {
		$Result = DB_query("SELECT counterindex FROM gltags WHERE tagref='" . $_GET['SelectedTag'] . "'");
		if (DB_num_rows($Result) > 0) {
			prnMsg(__('This tag cannot be deleted since there are already general ledger transactions created using it.') , 'error');
		} else {
			DB_query("DELETE FROM tags WHERE tagref='" . $_GET['SelectedTag'] . "'");
			prnMsg(__('The selected tag has been deleted') , 'success');
		}
		$Description = '';
	} else {
		$SQL = "SELECT tagref, tagdescription FROM tags WHERE tagref='" . $_GET['SelectedTag'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$Description = $MyRow['tagdescription']??'';
	}
} else {
	$Description = '';
	$_GET['SelectedTag'] = '';
}

if (isset($_POST['submit'])) {
	$SQL = "INSERT INTO tags values(NULL, '" . $_POST['Description'] . "')";
	if (DB_query($SQL)) prnMsg(__('The tag was inserted correctly'), 'success');
}

if (isset($_POST['update'])) {
	$SQL = "UPDATE tags SET tagdescription='" . $_POST['Description'] . "' WHERE tagref='" . $_POST['reference'] . "'";
	if (DB_query($SQL)) prnMsg(__('The tag was updated correctly'), 'success');
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 340px; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-input { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Setup') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN: Tags Table
echo '<main class="db-main">';
echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-tags" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Existing G/L Tags') . '</h3></div>';
echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>ID</th><th>Description</th><th style="text-align:right">Actions</th></tr></thead><tbody>';
$Result = DB_query("SELECT tagref, tagdescription FROM tags ORDER BY tagref");
while ($MyRow = DB_fetch_array($Result)) {
    echo '<tr>
            <td style="font-weight:700;">'.$MyRow['tagref'].'</td>
            <td>'.$MyRow['tagdescription'].'</td>
            <td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; width:auto;" href="'.basename(__FILE__).'?SelectedTag='.$MyRow['tagref'].'&Action=edit"><i class="fas fa-edit"></i></a>
                <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; color:#dc2626; width:auto;" href="'.basename(__FILE__).'?SelectedTag='.$MyRow['tagref'].'&Action=delete" onclick="return confirm(\''.__('Final confirmation: Delete tag?').'\');"><i class="fas fa-trash"></i></a>
            </div></td></tr>';
}
echo '</tbody></table></div></div></main>';

// SIDEBAR: Tag Management
echo '<aside class="db-aside">';
echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . (isset($_GET['Action']) && $_GET['Action']=='edit' ? __('Edit Tag') : __('New Tag')) . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="'.basename(__FILE__).'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'" />';
echo '<div class="db-form-group"><label class="db-label">Tag Description</label><input class="db-input" name="Description" required maxlength="30" autofocus value="'.$Description.'" /><div style="font-size:0.7rem; color:#64748b; margin-top:0.35rem;">Enter up to 30 characters.</div></div>';
echo '<input type="hidden" name="reference" value="'.$_GET['SelectedTag'].'" />';
if (isset($_GET['Action']) && $_GET['Action']=='edit') {
    echo '<button type="submit" name="update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> Update Tag</button>';
    echo '<a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="margin-top:0.5rem; width:100%">Cancel</a>';
} else {
    echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> Create Tag</button>';
}
echo '</form></div></div></aside>';

echo '</div></div>';

include(__DIR__ . '/includes/footer.php');
?>
