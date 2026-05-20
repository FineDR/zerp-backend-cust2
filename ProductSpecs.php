<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Product Specifications Maintenance');
$ViewTopic = 'QualityAssurance';
$BookMark = 'QA_ProdSpecs';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedQATest'])){
	$SelectedQATest =mb_strtoupper($_GET['SelectedQATest']);
} elseif (isset($_POST['SelectedQATest'])){
	$SelectedQATest =mb_strtoupper($_POST['SelectedQATest']);
}
if (isset($_GET['KeyValue'])){
	$KeyValue =mb_strtoupper($_GET['KeyValue']);
} elseif (isset($_POST['KeyValue'])){
	$KeyValue =mb_strtoupper($_POST['KeyValue']);
} else {
	$KeyValue = '';
}

// Logic: Basic Variable Sanitization
$RangeMin = (!isset($_POST['RangeMin']) || $_POST['RangeMin']=='') ? 'NULL' : "'" . $_POST['RangeMin'] . "'";
$RangeMax = (!isset($_POST['RangeMax']) || $_POST['RangeMax']=='') ? 'NULL' : "'" . $_POST['RangeMax'] . "'";

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
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1550px; margin: 0 auto; }
    .db-page-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 5px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; }
    
    .db-main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.25rem; align-items: start; }
    @media (max-width: 1200px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 900; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--db-border); width: 100%; font-size: 0.8125rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; border: none; width: 100%; transition: 0.2s; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); padding: 0.75rem; text-align: left; text-transform: uppercase; font-weight: 800; color: var(--db-primary-dark); border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); }
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
</style>';

echo '<div class="db-page"><div class="db-centered">';

// Section 1: Product Selection
if (!$KeyValue) {
	echo '<header class="db-page-header"><div><div class="db-breadcrumb">Quality Assurance / Configuration</div><h1 class="db-page-title">' . __('Select Product Specification') . '</h1></div></header>';
	echo '<div class="db-card" style="max-width: 600px; margin: 0 auto;"><div class="db-card-body">';
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    echo '<div class="db-field"><label class="db-label">' . __('New or Specific Item Code') . '</label><input type="text" name="KeyValue" class="db-input" autofocus /></div>';
    echo '<div style="text-align:center; margin: 1rem 0; font-weight:800; color:var(--db-text-muted);">--- OR ---</div>';
    echo '<div class="db-field"><label class="db-label">' . __('Existing Specification') . '</label><select name="KeyValue" class="db-select">';
    $SQL = "SELECT DISTINCT(keyval), description FROM prodspecs LEFT OUTER JOIN stockmaster ON stockmaster.stockid=prodspecs.keyval"; $Res = DB_query($SQL);
    while($R=DB_fetch_array($Res)) echo '<option value="'.$R['keyval'].'">'.$R['keyval'].' - '.$R['description'].'</option>';
    echo '</select></div><button type="submit" name="pickspec" class="db-btn db-btn-primary">' . __('Load Specification') . '</button></form></div></div>';
    include(__DIR__ . '/includes/footer.php'); echo '</div></div>'; exit();
}

// Logic: Operations (Move to grid later)
if (isset($_POST['CopySpec']) && isset($_POST['CopyTo'])) {
    DB_query("INSERT IGNORE INTO prodspecs (keyval, testid, defaultvalue, targetvalue, rangemin, rangemax, showoncert, showonspec, showontestplan, active) SELECT '"  . $_POST['CopyTo'] . "', testid, defaultvalue, targetvalue, rangemin, rangemax, showoncert, showonspec, showontestplan, active FROM prodspecs WHERE keyval='" .$KeyValue. "'");
    prnMsg(__('Specification copied to') . ' ' . $_POST['CopyTo'], 'success'); $KeyValue = $_POST['CopyTo'];
}
if (isset($_POST['AddTests'])) {
    for ($i=0; $i<=(int)$_POST['AddTestsCounter']; $i++) {
        if (isset($_POST['AddRow'.$i]) && $_POST['AddRow'.$i]=='on') {
            $min = ($_POST['AddRangeMin'.$i]=='') ? 'NULL' : "'".$_POST['AddRangeMin'.$i]."'";
            $max = ($_POST['AddRangeMax'.$i]=='') ? 'NULL' : "'".$_POST['AddRangeMax'.$i]."'";
            DB_query("INSERT INTO prodspecs (keyval, testid, defaultvalue, targetvalue, rangemin, rangemax, showoncert, showonspec, showontestplan, active) SELECT '" . $KeyValue . "', testid, defaultvalue, '" . $_POST['AddTargetValue'.$i] . "', $min, $max, showoncert, showonspec, showontestplan, active FROM qatests WHERE testid='" .$_POST['AddTestID'.$i]. "'");
        }
    }
}
if (isset($_POST['submit']) && isset($SelectedQATest)) {
    DB_query("UPDATE prodspecs SET defaultvalue='" . $_POST['DefaultValue'] . "', targetvalue='" . $_POST['TargetValue'] . "', rangemin=" . $RangeMin . ", rangemax=" . $RangeMax . ", showoncert='" . $_POST['ShowOnCert'] . "', showonspec='" . $_POST['ShowOnSpec'] . "', showontestplan='" . $_POST['ShowOnTestPlan'] . "', active='" . $_POST['Active'] . "' WHERE keyval = '".$KeyValue."' AND testid = '".$SelectedQATest."'");
    prnMsg(__('Updated'), 'success'); unset($SelectedQATest);
}
if (isset($_GET['delete'])) {
    $SQL = "SELECT COUNT(*) FROM qasamples INNER JOIN sampleresults ON sampleresults.sampleid=qasamples.sampleid AND sampleresults.testid='". $SelectedQATest."' WHERE qasamples.prodspeckey='".$KeyValue."'";
    if (DB_fetch_row(DB_query($SQL))[0]>0) prnMsg(__('Cannot delete - test results exist'),'error');
    else { DB_query("DELETE FROM prodspecs WHERE keyval='$KeyValue' AND testid='$SelectedQATest'"); prnMsg(__('Deleted'),'success'); unset($SelectedQATest); }
}

// Display Header
$PRes = DB_query("SELECT description FROM stockmaster WHERE stockid='$KeyValue'"); $PRow = DB_fetch_array($PRes);
echo '<header class="db-page-header"><div><div class="db-breadcrumb">Quality Assurance / Specifications</div><h1 class="db-page-title">' . ($PRow['description'] ?? $KeyValue) . ' <small style="color:var(--db-text-muted); font-size:0.5em; font-weight:normal;">#'.$KeyValue.'</small></h1></div>';
echo '<div style="display:flex; gap:10px;"><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn db-btn-ghost" style="width:auto;">Change Product</a><a target="_blank" href="'.$RootPath.'/PDFProdSpec.php?KeyValue='.$KeyValue.'" class="db-btn db-btn-primary" style="width:auto;">Print PDF</a></div></header>';

echo '<div class="db-main-grid">';

// MAIN COLUMN: List or Add Tests
echo '<div class="db-field-group">';
    if (isset($_GET['ListTests'])) {
        // Multi-add view
        $SQL = "SELECT qatests.testid, name, method, units, type, numericvalue, qatests.defaultvalue FROM qatests LEFT JOIN prodspecs ON prodspecs.testid=qatests.testid AND prodspecs.keyval='$KeyValue' WHERE qatests.active='1' AND prodspecs.keyval IS NULL ORDER BY name";
        $Res = DB_query($SQL);
        echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">Add Available Tests</h3></div><div class="db-card-body" style="padding:0;">';
        echo '<form method="post" action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/><input type="hidden" name="KeyValue" value="'.$KeyValue.'"/>';
        echo '<table class="db-table"><thead><tr><th>Add</th><th>Test Name</th><th>Method</th><th>Target</th><th>Range</th></tr></thead><tbody>';
        $x=0; while($R=DB_fetch_array($Res)) { $x++; echo '<tr><td><input type="checkbox" name="AddRow'.$x.'"/><input type="hidden" name="AddTestID'.$x.'" value="'.$R['testid'].'"/></td><td><b>'.$R['name'].'</b><br><small>'.$R['units'].'</small></td><td>'.$R['method'].'</td><td><input name="AddTargetValue'.$x.'" class="db-input" style="padding:4px;"/></td><td>'.($R['type']==4 ? '<input name="AddRangeMin'.$x.'" class="db-input" placeholder="Min" style="width:50px; padding:4px;"/> - <input name="AddRangeMax'.$x.'" class="db-input" placeholder="Max" style="width:50px; padding:4px;"/>':'N/A').'</td></tr>'; }
        echo '</tbody></table><div style="padding:1.5rem;"><input type="hidden" name="AddTestsCounter" value="'.$x.'"/><button type="submit" name="AddTests" class="db-btn db-btn-primary">Add Selected Tests</button></div></form></div></div>';
    } else {
        // Current Spec Table
        echo '<div class="db-card"><div class="db-card-body" style="padding:0;"><table class="db-table"><thead><tr><th>Test Name</th><th>Type</th><th>Basis/Units</th><th>Target</th><th>Range</th><th>Actions</th></tr></thead><tbody>';
        $SQL = "SELECT prodspecs.*, qatests.name, qatests.method, qatests.units, qatests.type FROM prodspecs INNER JOIN qatests ON qatests.testid=prodspecs.testid WHERE prodspecs.keyval='$KeyValue' ORDER BY qatests.name";
        $Res = DB_query($SQL);
        $types = [0=>'Text', 1=>'Select', 2=>'Check', 3=>'Date', 4=>'Range'];
        while($R = DB_fetch_array($Res)) {
            echo '<tr><td><b>'.$R['name'].'</b><br><small class="db-badge">'.$R['method'].'</small></td><td>'.$types[$R['type']].'</td><td>'.$R['units'].'</td><td>'.$R['targetvalue'].'</td><td>'.($R['type']==4 ? $R['rangemin'].' - '.$R['rangemax'] : '-').'</td>';
            echo '<td style="white-space:nowrap;"><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedQATest='.$R['testid'].'&KeyValue='.$KeyValue.'" class="link-action">Edit</a> | <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedQATest='.$R['testid'].'&KeyValue='.$KeyValue.'&delete=1" class="link-action" style="color:#dc2626;" onclick="return confirm(\'Remove?\');">Del</a></td></tr>';
        }
        echo '</tbody></table></div></div>';
    }
echo '</div>';

// SIDEBAR: Edit/Copy
echo '<div class="db-field-group">';
    if (isset($SelectedQATest)) {
        // Edit Single Test Card
        $SQL = "SELECT prodspecs.*, qatests.name, qatests.units, qatests.type, qatests.numericvalue FROM prodspecs INNER JOIN qatests ON qatests.testid=prodspecs.testid WHERE prodspecs.keyval='$KeyValue' AND prodspecs.testid='$SelectedQATest'";
        $Row = DB_fetch_array(DB_query($SQL));
        echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">Edit Test Property</h3></div><div class="db-card-body">';
        echo '<form method="post" action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/><input type="hidden" name="KeyValue" value="'.$KeyValue.'"/><input type="hidden" name="SelectedQATest" value="'.$SelectedQATest.'"/><input type="hidden" name="QATestName" value="'.$Row['name'].'"/>';
        echo '<div class="db-field"><label class="db-label">'.$Row['name'].'</label></div>';
        echo '<div class="db-field"><label class="db-label">Target Value</label><input type="text" name="TargetValue" class="db-input" value="'.$Row['targetvalue'].'" /></div>';
        if ($Row['type']==4) {
            echo '<div class="db-field"><label class="db-label">Range</label><div style="display:flex; gap:5px; align-items:center;"><input name="RangeMin" class="db-input" value="'.$Row['rangemin'].'"/><span>-</span><input name="RangeMax" class="db-input" value="'.$Row['rangemax'].'"/></div></div>';
        }
        if ($Row['type']==1) echo '<div class="db-field"><label class="db-label">Permitted (CSV)</label><input type="text" name="DefaultValue" class="db-input" value="'.$Row['defaultvalue'].'" /></div>';
        echo '<div class="db-field"><label class="db-label">Active</label><select name="Active" class="db-select"><option value="1">Yes</option><option '.(!$Row['active']?'selected':'').' value="0">No</option></select></div>';
        echo '<button type="submit" name="submit" class="db-btn db-btn-primary">Update Property</button><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?KeyValue='.$KeyValue.'" class="db-btn" style="text-align:center; padding-top:10px; font-size:0.75rem; color:var(--db-text-muted);">Cancel</a>';
        echo '</form></div></div>';
    }

    // Default Side Actions
    echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">Management Tools</h3></div><div class="db-card-body" style="display:flex; flex-direction:column; gap:10px;">';
    echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?ListTests=yes&KeyValue='.$KeyValue.'" class="db-btn db-btn-ghost">Add More Tests</a>';
    
    // Copy Form
    echo '<hr style="border:0; border-top:1px solid var(--db-border); margin:5px 0;">';
    echo '<form method="post" action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/><input type="hidden" name="KeyValue" value="'.$KeyValue.'"/>';
    echo '<div class="db-field"><label class="db-label">Clone This Spec To...</label><input type="text" name="ToStockID" class="db-input" placeholder="Target Part Code"/></div>';
    echo '<button type="submit" name="CopySpec" class="db-btn db-btn-ghost">Execute Clone</button></form>';
    echo '</div></div>';
echo '</div>';

echo '</div></div></div>';
include(__DIR__ . '/includes/footer.php');
?>
