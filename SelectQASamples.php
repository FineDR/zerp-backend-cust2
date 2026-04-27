<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Select QA Samples');
$ViewTopic = 'QualityAssurance';
$BookMark = 'QA_Samples';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['SampleDate'])){$_POST['SampleDate'] = ConvertSQLDate($_POST['SampleDate']);}
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

echo '
<style>
	:root {
		--primary: hsl(145, 63%, 38%); 
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-soft: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(145, 15%, 12%);
		--text-muted: hsl(145, 8%, 50%);
		--card-bg: #ffffff;
		--radius: 12px;
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); }
	.aw-container { padding: 2px 10px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
	.MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 1fr 350px; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .aw-badge-success { background: #d1fae5; color: #059669; }
</style>
<div class="aw-container">';

if (isset($_GET['SelectedSampleID'])){ $SelectedSampleID =mb_strtoupper($_GET['SelectedSampleID']); } elseif (isset($_POST['SelectedSampleID'])){ $SelectedSampleID =mb_strtoupper($_POST['SelectedSampleID']); }
if (isset($_GET['SelectedStockItem'])) { $SelectedStockItem = $_GET['SelectedStockItem']; } elseif (isset($_POST['SelectedStockItem'])) { $SelectedStockItem = $_POST['SelectedStockItem']; }
if (isset($_GET['LotNumber'])) { $LotNumber = $_GET['LotNumber']; } elseif (isset($_POST['LotNumber'])) { $LotNumber = $_POST['LotNumber']; }
if (isset($_GET['SampleID'])) { $SampleID = $_GET['SampleID']; } elseif (isset($_POST['SampleID'])) { $SampleID = $_POST['SampleID']; }
if (!isset($_POST['FromDate'])){ $_POST['FromDate']=date(($_SESSION['DefaultDateFormat']), mktime(0, 0, 0, date('m'), date('d')-15, date('Y'))); }
if (!isset($_POST['ToDate'])){ $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']); }

if (isset($_POST['submit'])) {
	if (isset($SelectedSampleID)) {
		$ResultsNotEntered = DB_fetch_row(DB_query("SELECT count(sampleid) FROM sampleresults WHERE sampleid = '" . $SelectedSampleID . "' AND showoncert='1' AND testvalue=''"));
		if ($ResultsNotEntered[0]>0 AND $_POST['Cert']=='1') { $_POST['Cert']='0'; prnMsg(__('Test Results incomplete. COA unavailable.'), 'error'); }
		DB_query("UPDATE qasamples SET identifier='" . $_POST['Identifier'] . "', comments='" . $_POST['Comments'] . "', sampledate='" . FormatDateForSQL($_POST['SampleDate']) . "', cert='" . $_POST['Cert'] . "' WHERE sampleid = '" . $SelectedSampleID . "'");
		prnMsg(__('QA Sample updated'), 'success');
		if ($_POST['Cert']==1) { $SD = DB_fetch_row(DB_query("SELECT prodspeckey, lotkey FROM qasamples WHERE sampleid = '".$SelectedSampleID."'")); DB_query("UPDATE qasamples SET cert='0' WHERE sampleid <> '".$SelectedSampleID . "' AND prodspeckey='" . $SD[0] . "' AND lotkey='" . $SD[1] . "'"); }
	} else { CreateQASample($_POST['ProdSpecKey'],$_POST['LotKey'], $_POST['Identifier'], $_POST['Comments'], $_POST['Cert'], $_POST['DuplicateOK']); prnMsg(__('New QA Sample Created'), 'success'); }
	unset($SelectedSampleID, $_POST['ProdSpecKey'], $_POST['LotKey'], $_POST['Identifier'], $_POST['Comments'], $_POST['Cert']);
} elseif (isset($_GET['delete'])) {
	$ResultsExist = DB_fetch_row(DB_query("SELECT COUNT(*) FROM sampleresults WHERE sampleresults.sampleid='".$SelectedSampleID."' AND sampleresults.testvalue > ''"));
	if ($ResultsExist[0]>0) { prnMsg(__('Cannot delete Sample with existing test results'),'error'); }
	else { DB_query("DELETE FROM sampleresults WHERE sampleid='" . $SelectedSampleID . "'"); DB_query("DELETE FROM qasamples WHERE sampleid='" . $SelectedSampleID ."'"); prnMsg(__('QA Sample deleted'),'success'); unset($SelectedSampleID); }
}

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Quality Control / Sample Management</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

echo '<div class="aw-grid aw-grid-layout">';

// MAIN AREA (Left)
echo '<main class="aw-main-side">';

// CREATE / EDIT FORM
echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title">' . (isset($SelectedSampleID) ? __('Edit QA Sample Details') . ' #' . $SelectedSampleID : __('Create New QA Sample')) . '</h3></div>
		<div class="aw-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
			if (isset($SelectedSampleID)) {
				$MyRow = DB_fetch_array(DB_query("SELECT prodspeckey, lotkey, identifier, comments, cert, sampledate FROM qasamples WHERE sampleid='".$SelectedSampleID."'"));
				echo '<input type="hidden" name="SelectedSampleID" value="' . $SelectedSampleID . '" />';
				echo '<div class="aw-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
						<div><label class="aw-label">Specification</label><div style="font-weight:700;">'.$MyRow['prodspeckey'].'</div></div>
						<div><label class="aw-label">Lot / Serial</label><div style="font-weight:700;">'.$MyRow['lotkey'].'</div></div>
						<div><label class="aw-label">Identifier</label><input type="text" name="Identifier" class="aw-input" value="'.$MyRow['identifier'].'" /></div>
						<div><label class="aw-label">Sample Date</label><input type="date" name="SampleDate" class="aw-input" value="'.FormatDateForSQL(ConvertSQLDate($MyRow['sampledate'])).'" /></div>
						<div><label class="aw-label">Comments</label><input type="text" name="Comments" class="aw-input" value="'.$MyRow['comments'].'" /></div>
						<div><label class="aw-label">Cert Allowed?</label><select name="Cert" class="aw-select"><option value="1" '.($MyRow['cert']==1?'selected':'').'>Yes</option><option value="0" '.($MyRow['cert']==0?'selected':'').'>No</option></select></div>
					  </div>';
			} else {
				echo '<div class="aw-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
						<div><label class="aw-label">Specification</label><select name="ProdSpecKey" class="aw-select">';
						$SpecRes = DB_query("SELECT DISTINCT(keyval), description FROM prodspecs LEFT OUTER JOIN stockmaster ON stockmaster.stockid=prodspecs.keyval");
						while ($R=DB_fetch_array($SpecRes)){ echo '<option value="' . $R['keyval'] . '">' . $R['keyval'].' - ' .$R['description']  . '</option>'; }
				echo '	</select></div>
						<div><label class="aw-label">Lot / Serial</label><input type="text" required name="LotKey" class="aw-input" /></div>
						<div><label class="aw-label">Identifier</label><input type="text" name="Identifier" class="aw-input" /></div>
						<div><label class="aw-label">Comments</label><input type="text" name="Comments" class="aw-input" /></div>
						<div><label class="aw-label">Cert Allowed?</label><select name="Cert" class="aw-select"><option value="0">No</option><option value="1">Yes</option></select></div>
                        <div><label class="aw-label">Allow Duplicate?</label><select name="DuplicateOK" class="aw-select"><option value="1">Yes</option><option value="0">No</option></select></div>
					  </div>';
			}
			echo '<div style="margin-top:16px; text-align:right;"><button type="submit" name="submit" class="aw-btn aw-btn-primary">' . __('Save Sample Details') . '</button></div>';
echo '		</form></div></div>';

// RESULTS TABLE
if (!isset($SelectedSampleID)) {
	$FromDate = FormatDateForSQL($_POST['FromDate']); $ToDate = FormatDateForSQL($_POST['ToDate']); $SQL = "SELECT qasamples.sampleid, prodspeckey, description, lotkey, identifier, createdby, sampledate, comments, cert FROM qasamples LEFT OUTER JOIN stockmaster on stockmaster.stockid=qasamples.prodspeckey WHERE 1=1";
	if (isset($LotNumber) && $LotNumber != '') { $SQL .= " AND lotkey LIKE '%" . $LotNumber . "%'"; } elseif (isset($SampleID) && $SampleID != '') { $SQL .= " AND sampleid='" . $SampleID . "'"; } else {
		if (isset($SelectedStockItem)) { $SQL .= " AND prodspeckey='" . $SelectedStockItem . "'"; }
		$SQL .= " AND sampledate>='".$FromDate."' AND sampledate <='".$ToDate."'";
	}
	$SQL .= " ORDER BY sampleid DESC";
	$Res = DB_query($SQL);
	if (DB_num_rows($Res) > 0) {
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('QA Sample Log') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead><tr><th>ID</th><th>Spec</th><th>Description</th><th>Lot / Serial</th><th>Identifier</th><th>Sample Date</th><th>COA</th><th>Actions</th></tr></thead>
						<tbody>';
		while ($R = DB_fetch_array($Res)) {
			$Cert = ($R['cert']==1) ? '<a href="'.$RootPath.'/PDFCOA.php?LotKey='.$R['lotkey'].'&ProdSpec='.$R['prodspeckey'].'" target="_blank" class="aw-badge aw-badge-success">'.__('Yes').'</a>' : '<span style="color:var(--text-muted);">No</span>';
			echo '<tr>
					<td style="font-weight:700;"><a href="'.$RootPath.'/TestPlanResults.php?SelectedSampleID='.$R['sampleid'].'" style="text-decoration:none; color:var(--primary);">' . str_pad($R['sampleid'],6,'0',STR_PAD_LEFT) . '</a></td>
					<td>' . $R['prodspeckey'] . '</td>
					<td style="font-size:0.7rem;">' . $R['description'] . '</td>
					<td><div style="font-weight:700;">' . $R['lotkey'] . '</div></td>
					<td>' . $R['identifier'] . '</td>
					<td>' . ConvertSQLDate($R['sampledate']) . '</td>
					<td style="text-align:center;">' . $Cert . '</td>
					<td style="white-space:nowrap;">
						<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?SelectedSampleID='.$R['sampleid'].'" class="aw-btn aw-btn-secondary aw-btn-sm">Edit</a>
						<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?delete=yes&SelectedSampleID='.$R['sampleid'].'" class="aw-btn-sm" style="color:#e11d48; text-decoration:none;" onclick="return confirm(\'Delete Sample?\');">Delete</a>
					</td>
				  </tr>';
		}
		echo '</tbody></table></div></div>';
	}
}
echo '</main>';

// SIDEBAR (Search Filters)
echo '<aside class="aw-sidebar-side">';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
	  <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title">' . __('Quick Filters') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-form-group"><label class="aw-label">Lot / Serial Number</label><input type="text" name="LotNumber" class="aw-input" value="'.(isset($LotNumber)?$LotNumber:'').'" /></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">Sample ID</label><input type="text" name="SampleID" class="aw-input" value="'.(isset($SampleID)?$SampleID:'').'" /></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">From Date</label><input type="date" name="FromDate" class="aw-input" value="'.FormatDateForSQL($_POST['FromDate']).'" /></div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">To Date</label><input type="date" name="ToDate" class="aw-input" value="'.FormatDateForSQL($_POST['ToDate']).'" /></div>
			<button type="submit" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Apply Filters') . '</button>
		</div>
	  </div>';

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title">' . __('Item Library Search') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-form-group"><label class="aw-label">Category</label><select name="StockCat" class="aw-select">';
				$CatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
				while ($C = DB_fetch_array($CatRes)) { echo '<option value="'.$C['categoryid'].'" '.((isset($_POST['StockCat'])&&$_POST['StockCat']==$C['categoryid'])?'selected':'').'>'.$C['categorydescription'].'</option>'; }
echo '		</select></div>
            <div class="aw-form-group" style="margin-top:10px;"><label class="aw-label">Keywords</label><input type="text" name="Keywords" class="aw-input" /></div>
            <div style="display:flex; gap:4px; margin-top:16px;">
                <button type="submit" name="SearchParts" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('Find') . '</button>
                <button type="submit" name="ResetPart" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('All') . '</button>
            </div>';
            if (isset($SelectedStockItem)) { echo '<div class="aw-badge aw-badge-info" style="margin-top:12px; width:100%; justify-content:center;">' . __('Active Item') . ': ' . $SelectedStockItem . '</div><input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" />'; }
echo '		</div>
	  </div>';
echo '</form>';

echo '</aside>';

echo '</div>'; // End aw-grid-layout
echo '</div>'; // End aw-container

include(__DIR__ . '/includes/footer.php');
?>
