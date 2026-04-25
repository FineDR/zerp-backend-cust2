<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fixed Asset Maintenance Tasks');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetMaintenance';

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
					<i class="fas fa-tools"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Configure asset maintenance cycles and responsibilities') . '</div>
			</div>
		</div>';

if (isset($_POST['Submit'])) {
	if (!is_numeric(filter_number_format($_POST['FrequencyDays'])) OR filter_number_format($_POST['FrequencyDays']) < 0){
		prnMsg(__('Frequency must be positive'),'error');
	} else {
		$SQL="INSERT INTO fixedassettasks (assetid, taskdescription, frequencydays, userresponsible, manager, lastcompleted)
						VALUES( '" . $_POST['AssetID'] . "', '" . $_POST['TaskDescription'] . "', '" . filter_number_format($_POST['FrequencyDays']) . "', '" . $_POST['UserResponsible'] . "', '" . $_POST['Manager'] . "', CURRENT_DATE )";
		DB_query($SQL);
		prnMsg(__('New task created'), 'success');
		unset($_POST['AssetID'], $_POST['TaskDescription'], $_POST['FrequencyDays'], $_POST['Manager'], $_POST['UserResponsible']);
	}
}

if (isset($_POST['Update'])) {
	if (!is_numeric(filter_number_format($_POST['FrequencyDays'])) OR filter_number_format($_POST['FrequencyDays']) < 0){
		prnMsg(__('Frequency must be positive'),'error');
	} else {
		$SQL="UPDATE fixedassettasks SET assetid = '" . $_POST['AssetID'] . "', taskdescription='".$_POST['TaskDescription'] ."', frequencydays='" . filter_number_format($_POST['FrequencyDays'])."', userresponsible='" . $_POST['UserResponsible'] . "', manager='" . $_POST['Manager'] . "' WHERE taskid='".$_POST['TaskID']."'";
		DB_query($SQL);
		prnMsg(__('Task updated'), 'success');
		unset($_POST['AssetID'], $_POST['TaskDescription'], $_POST['FrequencyDays'], $_POST['Manager'], $_POST['UserResponsible']);
	}
}

if (isset($_GET['Delete'])) {
	DB_query("DELETE FROM fixedassettasks WHERE taskid='".$_GET['TaskID']."'");
	prnMsg(__('Task deleted'), 'success');
}

echo '<div class="db-centered-container" style="width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
		<div class="db-main-layout" style="width: 100%; display: grid; grid-template-columns: 350px 1fr; gap: 30px; box-sizing: border-box;">';

// LEFT: FORM
echo '<div class="db-layout-left">';
if (isset($_GET['Edit'])) {
	$SQL="SELECT * FROM fixedassettasks WHERE taskid='".$_GET['TaskID']."'";
	$Result = DB_query($SQL); $MyRow=DB_fetch_array($Result);
	$_POST['TaskDescription'] = $MyRow['taskdescription'];
	$_POST['FrequencyDays'] = $MyRow['frequencydays'];
	$_POST['UserResponsible'] = $MyRow['userresponsible'];
	$_POST['Manager'] = $MyRow['manager'];
	$_POST['AssetID'] = $MyRow['assetid'];
}

if (!isset($_POST['TaskDescription'])) $_POST['TaskDescription']='';
if (!isset($_POST['FrequencyDays'])) $_POST['FrequencyDays']='';
if (!isset($_POST['UserResponsible'])) $_POST['UserResponsible']= '';
if (!isset($_POST['Manager'])) $_POST['Manager']='';
if (!isset($_POST['AssetID'])) $_POST['AssetID']='';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="form1">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-lg);">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-clock"></i> ' . (isset($_GET['Edit']) ? __('Edit Task') : __('New Task')) . '</div>
		</div>
		<div class="db-card-body" style="padding: 25px;">';

if (isset($_GET['Edit'])) {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Task ID') . '</label>
			<input type="hidden" name="TaskID" value="'.$_GET['TaskID'].'" />
			<div class="db-font-bold" style="padding: 10px; background: var(--surface-alt); border-radius: 8px;">' . $_GET['TaskID'] . '</div>
		  </div>';
}

echo '<div class="db-form-group">
		<label class="db-label">' . __('Asset') . '</label>
		<select required="required" name="AssetID" class="db-select">';
$AssetSQL="SELECT assetid, description FROM fixedassets";
$ARes = DB_query($AssetSQL);
while ($ARow=DB_fetch_array($ARes)) {
	$sel = ($ARow['assetid']==$_POST['AssetID']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$ARow['assetid'].'">' . $ARow['assetid'] . ' - ' . $ARow['description']  . '</option>';
}
echo '</select></div>';

echo '<div class="db-form-group">
		<label class="db-label">' . __('Task Description') . '</label>
		<textarea name="TaskDescription" required="required" class="db-input" rows="3">'.$_POST['TaskDescription'].'</textarea>
	  </div>';

echo '<div class="db-form-group">
		<label class="db-label">' . __('Frequency (Days)') . '</label>
		<input type="text" class="integer db-input" required="required" name="FrequencyDays" maxlength="5" value="' . $_POST['FrequencyDays'] . '" />
	  </div>';

$UserSQL="SELECT userid, realname FROM www_users";
echo '<div class="db-form-group">
		<label class="db-label">' . __('Responsible') . '</label>
		<select required="required" name="UserResponsible" class="db-select">';
$URes = DB_query($UserSQL);
while ($URow=DB_fetch_array($URes)) {
	$sel = ($URow['userid']==$_POST['UserResponsible']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$URow['userid'].'">' . $URow['realname'] . '</option>';
}
echo '</select></div>';

echo '<div class="db-form-group">
		<label class="db-label">' . __('Manager') . '</label>
		<select required="required" name="Manager" class="db-select">
			<option value="">' . __('No Manager Assigned') . '</option>';
DB_data_seek($URes, 0);
while ($URow=DB_fetch_array($URes)) {
	$sel = ($URow['userid']==$_POST['Manager']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$URow['userid'].'">' . $URow['realname'] . '</option>';
}
echo '</select></div>';

echo '</div>
		<div class="db-card-footer" style="padding: 20px; background: var(--surface-alt); display: flex; flex-direction: column; gap: 10px;">';
if (isset($_GET['Edit'])) {
	echo '<button type="submit" name="Update" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . __('Update Task') . '</button>
		  <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn db-btn-secondary" style="justify-content: center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
} else {
	echo '<button type="submit" name="Submit" class="db-btn db-btn-primary"><i class="fas fa-check-circle"></i> ' . __('Create Task') . '</button>';
}
echo '	</div>
	</div>
	</form>
</div>';

// RIGHT: TABLE
echo '<div class="db-layout-right">';
$SQL="SELECT taskid, fixedassettasks.assetid, description, taskdescription, frequencydays, lastcompleted, userresponsible, realname, manager FROM fixedassettasks INNER JOIN fixedassets ON fixedassettasks.assetid=fixedassets.assetid INNER JOIN www_users ON fixedassettasks.userresponsible=www_users.userid";
$Result = DB_query($SQL);
echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-md);">
		<div class="db-table-wrap">
			<table class="db-table monochromatic-table">
				<thead>
					<tr>
						<th style="padding-left: 20px;">' . __('ID') . '</th>
						<th>' . __('Asset') . '</th>
						<th>' . __('Description') . '</th>
						<th>' . __('Responsible') . '</th>
						<th style="padding-right: 20px;">' . __('Actions') . '</th>
						<th>' . __('Actions') . '</th>
					</tr>
				</thead>
				<tbody>';
while ($MyRow = DB_fetch_array($Result)) {
	$isSel = (isset($_GET['TaskID']) && $_GET['TaskID'] == $MyRow['taskid']) ? 'style="background: var(--surface-alt);"' : '';
	echo '<tr '.$isSel.'>
			<td data-label="' . __('ID') . '">' . $MyRow['taskid'] . '</td>
			<td data-label="' . __('Asset') . '">' . $MyRow['description'] . '</td>
			<td data-label="' . __('Description') . '">' . $MyRow['taskdescription'] . '</td>
			<td data-label="' . __('Responsible') . '">' . $MyRow['realname'] . '</td>
			<td class="db-table-actions" data-label="' . __('Actions') . '" style="padding-right: 20px; white-space: nowrap; text-align: center;">
				<div style="display: flex; gap: 8px; justify-content: center;">
					<a href="'.$RootPath.'/MaintenanceTasks.php?Edit=Yes&amp;TaskID=' . $MyRow['taskid'] .'" class="db-btn db-btn-secondary db-btn-sm">
						<i class="fas fa-edit"></i> <span class="db-btn-text-mobile">' . __('Edit') . '</span>
					</a>
					<a href="'.$RootPath.'/MaintenanceTasks.php?Delete=Yes&amp;TaskID=' . $MyRow['taskid'] .'" class="db-btn db-btn-danger db-btn-sm" onclick="return confirm(\'' . __('Confirm delete?') . '\');">
						<i class="fas fa-trash"></i> <span class="db-btn-text-mobile">' . __('Del') . '</span>
					</a>
				</div>
			</td>
		</tr>';
}
echo '</tbody></table></div></div></div>'; // End layout-right

echo '	</div>
	  </div>'; // End layout-grid

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

.db-table-wrap { border: none !important; overflow-x: auto !important; width: 100% !important; display: block !important; -webkit-overflow-scrolling: touch; box-sizing: border-box !important; }
.monochromatic-table td { border-bottom: 1px solid var(--border-soft); vertical-align: middle; }

@media (max-width: 768px) {
	.db-page-header { padding: 15px !important; }
	.monochromatic-table, .monochromatic-table thead, .monochromatic-table tbody, .monochromatic-table th, .monochromatic-table td, .monochromatic-table tr { 
		display: block !important; 
		width: 100% !important;
	}
	.monochromatic-table thead tr { display: none !important; }
	.monochromatic-table tr { 
		border: 1px solid var(--border-soft) !important; 
		border-radius: 12px !important; 
		margin-bottom: 15px !important; 
		padding: 15px !important; 
		background: #fff !important; 
	}
	.monochromatic-table td { 
		border: none !important; 
		display: flex !important; 
		justify-content: space-between !important; 
		padding: 8px 0 !important; 
		text-align: right !important;
	}
	.monochromatic-table td::before { 
		content: attr(data-label); 
		font-weight: 700 !important; 
		color: var(--text-muted) !important; 
		text-align: left !important;
		flex: 1 !important;
	}
	.monochromatic-table td.db-table-actions {
		border-top: 1px solid var(--border-soft) !important;
		margin-top: 10px !important;
		padding-top: 15px !important;
		display: block !important;
		text-align: center !important;
	}
	.monochromatic-table td.db-table-actions::before { display: none !important; }
	.monochromatic-table td.db-table-actions div { justify-content: center !important; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
?>
