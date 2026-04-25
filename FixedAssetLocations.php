<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fixed Asset Locations');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetLocations';

// Force Load FontAwesome and Modern Fonts for High-Fidelity UI
$ExtraHeadContent = '
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
	:root {
		--db-primary: #2563eb;
		--db-secondary: #64748b;
		--db-danger: #ef4444;
		--db-surface-alt: #f8fafc;
	}
	
	/* High-Fidelity Button System */
	.db-btn {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		padding: 10px 18px !important;
		border-radius: 10px !important;
		font-weight: 700 !important;
		font-size: 0.85rem !important;
		text-decoration: none !important;
		transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
		border: none !important;
		cursor: pointer !important;
		line-height: 1 !important;
		box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
	}
	.db-btn:active { transform: translateY(1px) !important; }
	
	.db-btn-secondary { background: #f1f5f9 !important; color: #475569 !important; border: 1px solid #e2e8f0 !important; }
	.db-btn-secondary:hover { background: #e2e8f0 !important; color: #1e293b !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important; }
	
	.db-btn-danger { background: #fef2f2 !important; color: #b91c1c !important; border: 1px solid #fee2e2 !important; }
	.db-btn-danger:hover { background: #fee2e2 !important; color: #991b1b !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important; }
	
	.db-btn i { font-size: 0.9rem !important; }
</style>';

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page" style="width: 100%; max-width: 100vw; overflow-x: hidden;">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-map-marker-alt"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Manage operational and storage sites for your assets') . '</div>
			</div>
		</div>';

if (isset($_POST['submit']) AND !isset($_POST['delete'])) {
	$InputError=0;
	if (!isset($_POST['LocationID']) OR mb_strlen($_POST['LocationID'])<1) {
		prnMsg(__('You must enter at least one character in the location ID'),'error');
		$InputError=1;
	}
	if (!isset($_POST['LocationDescription']) OR mb_strlen($_POST['LocationDescription'])<1) {
		prnMsg(__('You must enter at least one character in the location description'),'error');
		$InputError=1;
	}
	if ($InputError==0) {
		$SQL="INSERT INTO fixedassetlocations VALUES ('".$_POST['LocationID']."', '".$_POST['LocationDescription']."', '".$_POST['ParentLocationID']."')";
		DB_query($SQL);
		prnMsg(__('New location added'), 'success');
	}
}

if (isset($_POST['update']) and !isset($_POST['delete'])) {
		$InputError=0;
		if (!isset($_POST['LocationDescription']) or mb_strlen($_POST['LocationDescription'])<1) {
			prnMsg(__('Location description cannot be empty'),'error');
			$InputError=1;
		}
		if ($InputError==0) {
			 $SQL="UPDATE fixedassetlocations SET locationdescription='" . $_POST['LocationDescription'] . "', parentlocationid='" . $_POST['ParentLocationID'] . "' WHERE locationid ='" . $_POST['LocationID'] . "'";
			 DB_query($SQL);
			 prnMsg(__('Location updated'), 'success');
			 echo '<meta http-equiv="Refresh" content="0; url="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'">';
		}
} elseif (isset($_POST['delete']))  {
	$InputError=0;
	$SQL="SELECT COUNT(*) FROM fixedassetlocations WHERE parentlocationid='" . $_POST['LocationID']."'";
	$Res = DB_query($SQL); $Row=DB_fetch_row($Res);
	if ($Row[0]>0) { prnMsg(__('Cannot remove - has children'), 'warning'); $InputError=1; }
	$SQL="SELECT COUNT(*) FROM fixedassets WHERE assetlocation='" . $_POST['LocationID']."'";
	$Res = DB_query($SQL); $Row=DB_fetch_row($Res);
	if ($Row[0]>0) { prnMsg(__('Cannot remove - has assets'), 'warn'); $InputError=1; }
	if ($InputError==0) {
		DB_query("DELETE FROM fixedassetlocations WHERE locationid = '".$_POST['LocationID']."'");
		prnMsg(__('Deleted successfully'), 'success');
	}
}

echo '<div class="db-centered-container" style="width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
		<div class="db-main-layout" style="width: 100%; display: grid; grid-template-columns: 350px 1fr; gap: 30px; box-sizing: border-box;">';

// LEFT: FORM
echo '<div class="db-layout-left">';
if (isset($_GET['SelectedLocation'])) {
	$SQL="SELECT * FROM fixedassetlocations WHERE locationid='".$_GET['SelectedLocation']."'";
	$Res = DB_query($SQL); $MyRow = DB_fetch_array($Res);
	$LocationID = $MyRow['locationid'];
	$LocationDescription = $MyRow['locationdescription'];
	$ParentLocationID = $MyRow['parentlocationid'];
} else {
	$LocationID = ''; $LocationDescription = ''; $ParentLocationID = '';
}

echo '<form id="LocationForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-lg);">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-plus-circle"></i> ' . (isset($_GET['SelectedLocation']) ? __('Edit Location') : __('New Location')) . '</div>
		</div>
		<div class="db-card-body" style="padding: 25px;">';

if (isset($_GET['SelectedLocation'])) {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Location ID') . '</label>
			<input type="hidden" name="LocationID" value="'.$LocationID.'" />
			<div class="db-font-bold" style="padding: 10px; background: var(--surface-alt); border-radius: 8px;">' . $LocationID . '</div>
		  </div>';
} else {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Location ID') . '</label>
			<input type="text" name="LocationID" required="required" class="db-input" maxlength="6" value="'.$LocationID.'" />
		  </div>';
}

echo '<div class="db-form-group">
		<label class="db-label">' . __('Description') . '</label>
		<input type="text" name="LocationDescription" required="required" class="db-input" maxlength="20" value="'.$LocationDescription.'" />
	  </div>';

echo '<div class="db-form-group">
		<label class="db-label">' . __('Parent Location') . '</label>
		<select name="ParentLocationID" class="db-select">
			<option value="">' . __('None (Top Level)') . '</option>';
$PSql="SELECT locationid, locationdescription FROM fixedassetlocations";
$PRes = DB_query($PSql);
while ($PRow=DB_fetch_array($PRes)) {
	if (!isset($_GET['SelectedLocation']) or $_GET['SelectedLocation'] != $PRow['locationid']) {
		$sel = ($PRow['locationid']==$ParentLocationID) ? 'selected="selected"' : '';
		echo '<option '.$sel.' value="' . $PRow['locationid'] . '">' . $PRow['locationdescription'] . '</option>';
	}
}
echo '		</select>
	  </div>';

echo '</div>
		<div class="db-card-footer" style="padding: 20px; background: var(--surface-alt); display: flex; flex-direction: column; gap: 10px;">';
if (isset($_GET['SelectedLocation'])) {
	echo '<button type="submit" name="update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . __('Update') . '</button>
		  <button type="submit" name="delete" class="db-btn db-btn-danger" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash"></i> ' . __('Delete') . '</button>
		  <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn db-btn-secondary" style="justify-content: center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
} else {
	echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-check-circle"></i> ' . __('Save Location') . '</button>';
}
echo '	</div>
	</div>
	</form>
</div>';

// RIGHT: TABLE
echo '<div class="db-layout-right">';
$SQL='SELECT * FROM fixedassetlocations';
$Result = DB_query($SQL);
echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-md);">
		<div class="db-table-wrap">
			<table class="db-table monochromatic-table">
				<thead>
					<tr>
						<th style="padding-left: 20px;">' . __('ID') . '</th>
						<th>' . __('Description') . '</th>
						<th>' . __('Parent') . '</th>
						<th style="padding-right: 20px;">' . __('Action') . '</th>
					</tr>
				</thead>
				<tbody>';
while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['parentlocationid'] != '') {
		$ParentRes = DB_query("SELECT locationdescription FROM fixedassetlocations WHERE locationid='".$MyRow['parentlocationid']."'");
		$ParentRow = DB_fetch_array($ParentRes);
	}
	echo '<tr class="db-table-row">
			<td data-label="' . __('Location ID') . '">' . $MyRow['locationid'] . '</td>
			<td data-label="' . __('Description') . '">' . $MyRow['locationdescription'] . '</td>
			<td data-label="' . __('Parent Location') . '">' . ($MyRow['parentlocationid'] != '' ? $ParentRow['locationdescription'] : '-') . '</td>
			<td class="db-table-actions" data-label="' . __('Actions') . '" style="padding-right: 20px; white-space: nowrap; text-align: center;">
				<div style="display: flex; gap: 8px; justify-content: flex-end;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocation=' . urlencode($MyRow['locationid']) . '" class="db-btn db-btn-secondary db-btn-sm" style="width: auto !important;">
						<i class="fas fa-edit"></i> <span class="db-btn-text-mobile">' . __('Edit') . '</span>
					</a>
				</div>
			</td>
		</tr>';
}
echo '</tbody></table></div></div></div>'; // End layout-right

echo '	</div>
	  </div>'; // End layout-grid, centered-container

echo '</div>'; // End db-page

echo '<style>
.monochromatic-table th { background: transparent !important; color: var(--text-main) !important; border-bottom: 2px solid var(--border) !important; }
.db-page { width: 100% !important; max-width: 100vw !important; overflow-x: hidden !important; box-sizing: border-box !important; }
.db-centered-container { width: 100% !important; max-width: 1400px !important; box-sizing: border-box !important; }
.db-main-layout { width: 100% !important; box-sizing: border-box !important; min-width: 0 !important; }
.db-card { width: 100% !important; box-sizing: border-box !important; margin-bottom: 20px; min-width: 0 !important; }

/* Aggressive Legacy System Cleanout */
@media (max-width: 1024px) {
    #header, .header-container, #footer, .canvas, #Canvas, .ModuleList { 
        display: none !important; 
        max-width: 100vw !important; 
        overflow: hidden !important; 
        visibility: hidden !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    #logoutLink, #adminLink, .AdminLink, .LogoutLink { display: none !important; }
    html, body { width: 100% !important; max-width: 100vw !important; overflow-x: hidden !important; margin: 0 !important; padding: 0 !important; }
}

.db-label { white-space: normal !important; width: 100% !important; display: block !important; }
.db-input, .db-select, .db-form-group { width: 100% !important; min-width: 0 !important; box-sizing: border-box !important; }

.db-table-wrap { overflow-x: auto !important; width: 100% !important; display: block !important; -webkit-overflow-scrolling: touch; box-sizing: border-box !important; }
.monochromatic-table td { border-bottom: 1px solid var(--border-soft); vertical-align: middle; }

@media (max-width: 1024px) {
	.db-main-layout { grid-template-columns: 1fr !important; }
}
@media (max-width: 768px) {
	.db-page-header { padding: 15px !important; }
	.db-page-title { font-size: 1.25rem !important; }
	.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
	.db-table-wrap { width: 100% !important; margin: 0 !important; border: 1px solid var(--border-soft); border-radius: 8px; }
	.monochromatic-table { min-width: 600px !important; }
	.db-card-body { padding: 20px !important; }
	.db-card-footer { flex-direction: column !important; padding: 20px !important; gap: 10px !important; }
	.db-btn:not(.db-btn-sm) { width: 100% !important; display: flex !important; justify-content: center !important; }
	.monochromatic-table td.db-table-actions {
		border-top: 1px solid var(--border-soft) !important;
		margin-top: 10px !important;
		padding-top: 15px !important;
		display: block !important;
		text-align: center !important;
	}
	.monochromatic-table td.db-table-actions::before { display: none !important; }
	.monochromatic-table td.db-table-actions div { justify-content: center !important; }
	.db-btn-text-mobile { display: inline !important; margin-left: 5px; }
}
@media (min-width: 769px) {
	.db-btn-text-mobile { display: none; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
?>
