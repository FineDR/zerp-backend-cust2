<?php

require(__DIR__ . '/includes/session.php');

$Title = __('QA Tests Maintenance');
$ViewTopic = 'QualityAssurance';
$BookMark = 'QA_Tests';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedQATest'])){
	$SelectedQATest =mb_strtoupper($_GET['SelectedQATest']);
} elseif (isset($_POST['SelectedQATest'])){
	$SelectedQATest =mb_strtoupper($_POST['SelectedQATest']);
}

$Errors = array();

if (isset($_POST['submit'])) {
	$InputError = 0;
	$i=1;

	if (mb_strlen($_POST['QATestName']) > 50) {
		$InputError = 1; prnMsg(__('The QA Test name must be fifty characters or less long'),'error'); $Errors[$i++] = 'QATestName';
	}
	if (mb_strlen($_POST['Type']) =='') {
		$InputError = 1; prnMsg(__('The Type must not be blank'),'error'); $Errors[$i++] = 'Type';
	}
	$SQL= "SELECT COUNT(*) FROM qatests WHERE qatests.name='".$_POST['QATestName']."' AND qatests.testid <> '" .($SelectedQATest ?? ''). "'";
	$Result = DB_query($SQL); $MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) { $InputError = 1; prnMsg(__('The QA Test name already exists'),'error'); $Errors[$i++] = 'QATestName'; }

	if (!$InputError) {
		if (isset($SelectedQATest)) {
			$SQL = "UPDATE qatests SET name='" . $_POST['QATestName'] . "', method='" . $_POST['Method'] . "', groupby='" . $_POST['GroupBy'] . "', units='" . $_POST['Units'] . "', type='" . $_POST['Type'] . "', defaultvalue='" . $_POST['DefaultValue'] . "', numericvalue='" . $_POST['NumericValue'] . "', showoncert='" . $_POST['ShowOnCert'] . "', showonspec='" . $_POST['ShowOnSpec'] . "', showontestplan='" . $_POST['ShowOnTestPlan'] . "', active='" . $_POST['Active'] . "' WHERE testid = '".$SelectedQATest."'";
			$Msg = __('Updated');
		} else {
			$SQL = "INSERT INTO qatests (name, method, groupby, units, type, defaultvalue, numericvalue, showoncert, showonspec, showontestplan, active) VALUES ('" . $_POST['QATestName'] . "', '" . $_POST['Method'] . "', '" . $_POST['GroupBy'] . "', '" . $_POST['Units'] . "', '" .$_POST['Type'] . "', '" . $_POST['DefaultValue'] . "', '" . $_POST['NumericValue'] . "', '" . $_POST['ShowOnCert'] . "', '" . $_POST['ShowOnSpec'] . "', '" . $_POST['ShowOnTestPlan'] . "', '" . $_POST['Active'] . "')";
			$Msg = __('Added');
		}
		DB_query($SQL); prnMsg($Msg . ' ' . $_POST['QATestName'], 'success');
		unset($SelectedQATest, $_POST['QATestName'], $_POST['DefaultValue'], $_POST['NumericValue']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM prodspec WHERE testid='".$SelectedQATest."'";
	$Result = DB_query($SQL); $MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) { prnMsg(__('Cannot delete - Product Specs are using it'), 'error'); }
	else {
		DB_query("DELETE FROM qatests WHERE testid='". $SelectedQATest."'");
		prnMsg(__('Deleted'), 'success'); unset($SelectedQATest);
	}
}

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --radius-lg: 12px;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1600px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 2rem; }
    
    .db-main-grid { display: grid; grid-template-columns: 420px 1fr; gap: 1.5rem; align-items: start; }
    @media (max-width: 1200px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.8125rem; font-weight: 900; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.375rem; display: block; }
    .db-input, .db-select { padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); width: 100%; font-size: 0.875rem; }
    .db-btn { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; width: 100%; transition: 0.2s; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); padding: 0.75rem; text-align: left; text-transform: uppercase; font-weight: 800; color: var(--db-primary-dark); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .db-badge { padding: 2px 6px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
</style>

<div class="db-page">
    <div class="db-centered">
        <div class="db-breadcrumb">Manufacturing / Quality Assurance</div>
        <h1 class="db-page-title">QA Test Dictionary</h1>

        <div class="db-main-grid">
            <!-- Sidebar Form -->
            <div class="db-field-group">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title">' . (isset($SelectedQATest) ? __('Edit Test Definition') : __('New Test Definition')) . '</h3></div>
                    <div class="db-card-body">';
                    
                    if (isset($SelectedQATest)) {
                        $SQL = "SELECT * FROM qatests WHERE testid='".$SelectedQATest."'";
                        $Row = DB_fetch_array(DB_query($SQL));
                        foreach($Row as $k=>$v) if(!isset($_POST[$k])) $_POST[$k] = $v;
                        echo '<input type="hidden" name="SelectedQATest" value="'.$SelectedQATest.'" />';
                        echo '<div class="db-field"><label class="db-label">Test Identifier</label><div class="db-badge">#'.$SelectedQATest.'</div></div>';
                    }

                    echo '<div class="db-field"><label class="db-label">Name</label><input type="text" name="QATestName" class="db-input" value="'.($_POST['QATestName'] ?? '').'" required maxlength="50" /></div>';
                    echo '<div class="db-field"><label class="db-label">Method / Std</label><input type="text" name="Method" class="db-input" value="'.($_POST['Method'] ?? '').'" maxlength="20" /></div>';
                    
                    echo '<div class="db-field"><label class="db-label">Group</label><select name="GroupBy" class="db-select">';
                    $ResG = DB_query("SELECT groupname FROM prodspecgroups");
                    while($G = DB_fetch_array($ResG)) { $sel = (($_POST['GroupBy'] ?? '') == $G['groupname']) ? 'selected':''; echo '<option '.$sel.' value="'.$G['groupname'].'">'.$G['groupname'].'</option>'; }
                    echo '</select></div>';

                    echo '<div class="db-field"><label class="db-label">Units</label><input type="text" name="Units" class="db-input" value="'.($_POST['Units'] ?? '').'" maxlength="20" /></div>';
                    
                    echo '<div class="db-field"><label class="db-label">Input UI Type</label><select name="Type" class="db-select">';
                    $Types = [0=>'Text Box', 1=>'Select List', 2=>'Checkbox', 3=>'Date Picker', 4=>'Value Range'];
                    foreach($Types as $k=>$v) { $sel = (($_POST['Type'] ?? 4) == $k) ? 'selected':''; echo '<option '.$sel.' value="'.$k.'">'.$v.'</option>'; }
                    echo '</select></div>';

                    echo '<div class="db-field"><label class="db-label">Permitted / Possible Values</label><input type="text" name="DefaultValue" class="db-input" value="'.($_POST['DefaultValue'] ?? '').'" /></div>';

                    $YesNo = [1=>'Yes', 0=>'No'];
                    echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">';
                    foreach(['NumericValue','ShowOnCert','ShowOnSpec','ShowOnTestPlan','Active'] as $f) {
                        echo '<div class="db-field"><label class="db-label">'.$f.'</label><select name="'.$f.'" class="db-select">';
                        foreach($YesNo as $k=>$v) { $sel = (($_POST[$f] ?? 1) == $k) ? 'selected':''; echo '<option '.$sel.' value="'.$k.'">'.$v.'</option>'; }
                        echo '</select></div>';
                    }
                    echo '</div>';

                    echo '<button type="submit" name="submit" class="db-btn db-btn-primary" style="margin-top:1rem;">' . __('Commit Configuration') . '</button>';
                    if(isset($SelectedQATest)) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn" style="display:block; text-align:center; padding:0.75rem; font-size:0.8rem; color:var(--db-text-muted);">'.__('Cancel Edit').'</a>';

                echo '</div></div></form></div>';

            // Pagination Logic
            $ItemsPerPage = 15;
            $Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
            $Offset = ($Page - 1) * $ItemsPerPage; $Types = [0=>'Text Box', 1=>'Select List', 2=>'Checkbox', 3=>'Date Picker', 4=>'Value Range'];

            $TotalRes = DB_query("SELECT COUNT(*) FROM qatests");
            $TotalRows = DB_fetch_row($TotalRes)[0];
            $TotalPages = ceil($TotalRows / $ItemsPerPage);

            echo '<div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title">Configured QA Tests (' . $TotalRows . ')</h3></div>
                    <div class="db-card-body" style="padding:0;">
                        <table class="db-table">
                            <thead><tr><th>' . __('Name') . '</th><th>' . __('Standard') . '</th><th>' . __('Units') . '</th><th>' . __('Type') . '</th><th>' . __('Visibility') . '</th><th>' . __('Actions') . '</th></tr></thead>
                            <tbody>';
            $SQL = "SELECT * FROM qatests ORDER BY name LIMIT $ItemsPerPage OFFSET $Offset";
            $Res = DB_query($SQL);
            while ($Row = DB_fetch_array($Res)) {
                $vis = ($Row['showoncert']?'C':'').($Row['showonspec']?'S':'').($Row['showontestplan']?'P':'');
                echo '<tr>
                        <td><b style="color:var(--db-primary-dark);">'.$Row['name'].'</b><br><small class="db-badge">'.$Row['groupby'].'</small></td>
                        <td>'.$Row['method'].'</td>
                        <td>'.$Row['units'].'</td>
                        <td>'.($Types[$Row['type']] ?? $Row['type']).'</td>
                        <td><span class="db-badge" title="Cert/Spec/Plan">'.$vis.'</span></td>
                        <td style="white-space:nowrap;">
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedQATest='.$Row['testid'].'" class="link-action">Edit</a> | 
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedQATest='.$Row['testid'].'&delete=1" class="link-action" style="color:#dc2626;" onclick="return confirm(\'Delete?\');">Del</a>
                        </td>
                      </tr>';
            }
            echo '</tbody></table>';
            
            // Pagination Controls
            if ($TotalPages > 1) {
                echo '<div style="padding: 1rem; border-top: 1px solid var(--db-border); display:flex; justify-content:space-between; align-items:center; background: #fff;">';
                echo '<div style="font-size: 0.75rem; color: var(--db-text-muted); font-weight: 600;">Page ' . $Page . ' of ' . $TotalPages . '</div>';
                echo '<div style="display:flex; gap: 8px;">';
                if ($Page > 1) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page-1).'" class="db-btn db-btn-ghost" style="width: auto; padding: 0.5rem 1rem;">Previous</a>';
                if ($Page < $TotalPages) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page+1).'" class="db-btn db-btn-ghost" style="width: auto; padding: 0.5rem 1rem;">Next</a>';
                echo '</div></div>';
            }
            echo '</div></div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

include(__DIR__ . '/includes/footer.php');
?>
