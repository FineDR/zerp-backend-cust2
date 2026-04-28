<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Product Spec Groups Maintenance');
$ViewTopic = 'QualityAssurance';
$BookMark = 'QA_ProdSpecs';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedGroup'])){
	$SelectedGroup = $_GET['SelectedGroup'];
} elseif (isset($_POST['SelectedGroup'])){
	$SelectedGroup = $_POST['SelectedGroup'];
}

$Errors = array();

if (isset($_POST['submit'])) {
	$InputError = 0; $i=1;
	if (mb_strlen($_POST['GroupName']) < 1 || mb_strlen($_POST['GroupName']) > 50) { $InputError = 1; prnMsg(__('Invalid group name'),'error'); $Errors[$i++] = 'GroupName'; }
	if (empty($_POST['GroupByNo']) || !is_numeric(filter_number_format($_POST['GroupByNo'])) || filter_number_format($_POST['GroupByNo']) <= 0){ $InputError = 1; prnMsg( __('Invalid numerical sort order') ,'error'); $Errors[$i++] = 'GroupByNo'; }
	if (empty($_POST['Labels']) || mb_strlen($_POST['Labels']) > 240) { $InputError = 1; prnMsg( __('Labels are required') ,'error'); $Errors[$i++] = 'Labels'; }
	if (!empty($_POST['HeaderTitle']) && mb_strlen($_POST['HeaderTitle']) > 100) { $InputError = 1; prnMsg( __('Title too long') ,'error'); $Errors[$i++] = 'HeaderTitle'; }
    if (empty($_POST['NumCols']) || !in_array($_POST['NumCols'], array('2', '3'))) { $InputError = 1; prnMsg( __('Cols must be 2 or 3') ,'error'); $Errors[$i++] = 'NumCols'; }

	if (!empty($_POST['Labels']) && !empty($_POST['NumCols'])) {
		if (count(explode(',', $_POST['Labels'])) != $_POST['NumCols']) { $InputError = 1; prnMsg( __('Label count mismatch') ,'error'); $Errors[$i++] = 'Labels'; }
	}

	if (!$InputError) {
		if (isset($SelectedGroup)) {
			$OldName = DB_fetch_array(DB_query("SELECT groupname FROM prodspecgroups WHERE groupid = '$SelectedGroup'"))['groupname'];
			if ($OldName != $_POST['GroupName']) {
				if (DB_fetch_row(DB_query("SELECT COUNT(*) FROM qatests WHERE groupby = '$OldName'"))[0] > 0) { $InputError = 1; prnMsg(__('Cannot rename - group in use'),'error'); }
			}
			if (!$InputError) {
				$SQL = "UPDATE prodspecgroups SET groupname='" . $_POST['GroupName'] . "', groupbyNo='" . (float)filter_number_format($_POST['GroupByNo']) . "', headertitle=" . (empty($_POST['HeaderTitle']) ? 'NULL' : "'" . $_POST['HeaderTitle'] . "'") . ", trailertext=" . (empty($_POST['TrailerText']) ? 'NULL' : "'" . $_POST['TrailerText'] . "'") . ", labels='" . $_POST['Labels'] . "', numcols='" . $_POST['NumCols'] . "' WHERE groupid = '$SelectedGroup'";
				$Msg = __('Updated');
			}
		} else {
			$SQL = "INSERT INTO prodspecgroups (groupname, groupbyNo, headertitle, trailertext, labels, numcols) VALUES ('" . $_POST['GroupName'] . "', '" . (float)filter_number_format($_POST['GroupByNo']) . "', " . (empty($_POST['HeaderTitle']) ? 'NULL' : "'" . $_POST['HeaderTitle'] . "'") . ", " . (empty($_POST['TrailerText']) ? 'NULL' : "'" . $_POST['TrailerText'] . "'") . ", '" . $_POST['Labels'] . "', '" . $_POST['NumCols'] . "')";
			$Msg = __('Added');
		}
		if (!$InputError) { DB_query($SQL); prnMsg($Msg, 'success'); unset($SelectedGroup, $_POST['GroupName'], $_POST['GroupByNo'], $_POST['HeaderTitle'], $_POST['TrailerText'], $_POST['Labels'], $_POST['NumCols']); }
	}
} elseif (isset($_GET['delete'])) {
	$GroupName = DB_fetch_array(DB_query("SELECT groupname FROM prodspecgroups WHERE groupid = '$SelectedGroup'"))['groupname'];
	if (DB_fetch_row(DB_query("SELECT COUNT(*) FROM qatests WHERE qatests.groupby = '$GroupName'"))[0] > 0) prnMsg(__('In use'),'warn');
	else { DB_query("DELETE FROM prodspecgroups WHERE groupid='$SelectedGroup'"); prnMsg(__('Deleted'),'success'); unset($SelectedGroup); }
}

echo '<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-card-bg: #ffffff;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 380px 1fr; gap: 1.5rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.75rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    .db-input, .db-select { 
        padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: all 0.2s;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); background: #fff; }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table-container { overflow-x: auto; width: 100%; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
    .db-table tr:hover td { background: #f8fafc; }
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
    .link-delete { color: #dc2626; }

    .db-pagination { padding: 0.875rem 1rem; border-top: 1px solid var(--db-border); display: flex; justify-content: space-between; align-items: center; }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div class="db-breadcrumb">Quality Assurance / Definitions</div>
            <h1 class="db-page-title">' . __('Spec Group Maintenance') . '</h1>
        </header>

        <div class="db-main-grid">
            <!-- Form Card -->
            <div class="db-column">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title">' . (isset($SelectedGroup) ? __('Update Definition') : __('New Group Definition')) . '</h3></div>
                    <div class="db-card-body">';
                    
                    if (isset($SelectedGroup)) {
                        $SQL = "SELECT * FROM prodspecgroups WHERE groupid='$SelectedGroup'"; $Row = DB_fetch_array(DB_query($SQL));
                        foreach($Row as $k=>$v) if(!isset($_POST[$k])) $_POST[$k] = $v;
                        echo '<input type="hidden" name="SelectedGroup" value="'.$SelectedGroup.'" />';
                        echo '<div class="db-field"><label class="db-label">Internal ID</label><div class="db-badge">#'.$SelectedGroup.'</div></div>';
                    }

                    echo '<div class="db-field"><label class="db-label">Group Name</label><input type="text" name="GroupName" class="db-input" value="'.($_POST['groupname'] ?? $_POST['GroupName'] ?? '').'" required maxlength="50" autofocus /></div>';
                    echo '<div class="db-field"><label class="db-label">Sequence #</label><input type="number" name="GroupByNo" class="db-input" value="'.($_POST['groupbyNo'] ?? $_POST['GroupByNo'] ?? '1').'" required /></div>';
                    echo '<div class="db-field"><label class="db-label">Display Header</label><input type="text" name="HeaderTitle" class="db-input" value="'.($_POST['headertitle'] ?? $_POST['HeaderTitle'] ?? '').'" maxlength="100" /></div>';
                    echo '<div class="db-field"><label class="db-label">Footer/Trailer Text</label><input type="text" name="TrailerText" class="db-input" value="'.($_POST['trailertext'] ?? $_POST['TrailerText'] ?? '').'" maxlength="240" /></div>';
                    
                    echo '<div class="db-field"><label class="db-label">Grid Columns</label><select name="NumCols" class="db-select"><option value="2">2 Columns</option><option '.(($_POST['numcols'] ?? $_POST['NumCols'] ?? '3') == '3' ? 'selected':'').' value="3">3 Columns</option></select></div>';
                    echo '<div class="db-field"><label class="db-label">Column Labels</label><input type="text" name="Labels" class="db-input" value="'.($_POST['labels'] ?? $_POST['Labels'] ?? '').'" required placeholder="E.g. Method, Target, Result" /></div>';

                    echo '<button type="submit" name="submit" class="db-btn db-btn-primary" style="margin-top:0.5rem;">' . (isset($SelectedGroup) ? __('Update Group'):__('Create Group')) . '</button>';
                    if(isset($SelectedGroup)) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn" style="display:block; text-align:center; padding-top:0.75rem; font-size:0.7rem; color:var(--db-text-muted);">'.__('Cancel').'</a>';

                echo '</div></div></form></div>'; echo '<!-- Results Grid -->
            <div class="db-column">
                <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title">Existing Groups</h3></div>
                    <div class="db-card-body" style="padding:0;">';
            
            // Pagination
            $ItemsPerPage = 10;
            $Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
            $Offset = ($Page - 1) * $ItemsPerPage;
            $Total = DB_fetch_row(DB_query("SELECT COUNT(*) FROM prodspecgroups"))[0];
            $TotalPages = ceil($Total / $ItemsPerPage);

            echo '<div class="db-table-container">
                    <table class="db-table">
                        <thead><tr><th>Seq</th><th>Group Name</th><th>Header</th><th>Cols</th><th>Labels</th><th>Actions</th></tr></thead>
                        <tbody>';
            $SQL = "SELECT * FROM prodspecgroups ORDER BY groupbyNo LIMIT $ItemsPerPage OFFSET $Offset";
            $Res = DB_query($SQL);
            while ($MyRow = DB_fetch_array($Res)) {
                echo '<tr>
                        <td><div class="db-badge">'.$MyRow['groupbyNo'].'</div></td>
                        <td><b style="color:var(--db-primary-dark);">'.$MyRow['groupname'].'</b></td>
                        <td><small>'.($MyRow['headertitle'] ?: '-').'</small></td>
                        <td>'.$MyRow['numcols'].'</td>
                        <td><small>'.$MyRow['labels'].'</small></td>
                        <td style="white-space:nowrap;">
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedGroup='.$MyRow['groupid'].'" class="link-action">Edit</a> | 
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedGroup='.$MyRow['groupid'].'&delete=1" class="link-action link-delete" onclick="return confirm(\'Delete this group?\');">Del</a>
                        </td>
                      </tr>';
            }
            echo '</tbody></table></div>';
            
            if ($TotalPages > 1) {
                echo '<div class="db-pagination"><div class="db-page-info">Rows '.($Offset+1).'-'.min($Offset+$ItemsPerPage, $Total).' / '.$Total.'</div>';
                echo '<div style="display:flex; gap:6px;">';
                if($Page > 1) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page-1).'" class="db-btn db-btn-ghost" style="width:auto;">Prev</a>';
                if($Page < $TotalPages) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page+1).'" class="db-btn db-btn-ghost" style="width:auto;">Next</a>';
                echo '</div></div>';
            }

            echo '</div></div></div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

include(__DIR__ . '/includes/footer.php');
?>
