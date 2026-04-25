<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fixed Asset Category Maintenance');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetCategories';

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
					<i class="fas fa-tags"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Manage your asset categories and GL mappings') . '</div>
			</div>
		</div>';

if (isset($_GET['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_GET['SelectedCategory']);
} elseif (isset($_POST['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_POST['SelectedCategory']);
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	$_POST['CategoryID'] = mb_strtoupper($_POST['CategoryID']);

	if (mb_strlen($_POST['CategoryID']) > 6) {
		$InputError = 1;
		prnMsg(__('The Fixed Asset Category code must be six characters or less long'),'error');
	} elseif (mb_strlen($_POST['CategoryID'])==0) {
		$InputError = 1;
		prnMsg(__('The Fixed Asset Category code must be at least 1 character but less than six characters long'),'error');
	} elseif (mb_strlen($_POST['CategoryDescription']) >20) {
		$InputError = 1;
		prnMsg(__('The Fixed Asset Category description must be twenty characters or less long'),'error');
	}

	if ($_POST['CostAct'] == $_SESSION['CompanyRecord']['debtorsact']
			OR $_POST['CostAct'] == $_SESSION['CompanyRecord']['creditorsact']
			OR $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['debtorsact']
			OR $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['creditorsact']
			OR $_POST['CostAct'] == $_SESSION['CompanyRecord']['grnact']
			OR $_POST['AccumDepnAct'] == $_SESSION['CompanyRecord']['grnact']){
		prnMsg(__('The accounts selected to post cost or accumulated depreciation to cannot be either of the debtors control account, creditors control account or GRN suspense accounts'),'error');
		$InputError =1;
	}

	$SQL = "SELECT bankaccounts.accountcode FROM bankaccounts INNER JOIN chartmaster ON bankaccounts.accountcode=chartmaster.accountcode";
	$Result = DB_query($SQL);
	$BankAccounts = array();
	while ($Act = DB_fetch_row($Result)){
		$BankAccounts[]= $Act[0];
	}
	if (in_array($_POST['CostAct'], $BankAccounts)) {
		prnMsg(__('The asset cost account selected is a bank account'),'error');
		$InputError=1;
	}
	if (in_array($_POST['AccumDepnAct'], $BankAccounts)) {
		prnMsg( __('The accumulated depreciation account selected is a bank account'),'error');
		$InputError=1;
	}

	if (isset($SelectedCategory) AND $InputError != 1) {
		$SQL = "UPDATE fixedassetcategories
					SET categorydescription = '" . $_POST['CategoryDescription'] . "',
						costact = '" . $_POST['CostAct'] . "',
						depnact = '" . $_POST['DepnAct'] . "',
						disposalact = '" . $_POST['DisposalAct'] . "',
						accumdepnact = '" . $_POST['AccumDepnAct'] . "'
				WHERE categoryid = '".$SelectedCategory . "'";
		DB_query($SQL);
		prnMsg(__('Updated category') . ': ' . $_POST['CategoryDescription'],'success');
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO fixedassetcategories (categoryid, categorydescription, costact, depnact, disposalact, accumdepnact)
								VALUES ('" . $_POST['CategoryID'] . "', '" . $_POST['CategoryDescription'] . "', '" . $_POST['CostAct'] . "', '" . $_POST['DepnAct'] . "', '" . $_POST['DisposalAct'] . "', '" . $_POST['AccumDepnAct'] . "')";
		DB_query($SQL);
		prnMsg(__('Created new category') . ': ' . $_POST['CategoryDescription'],'success');
	}
	unset($_POST['CategoryID'], $_POST['CategoryDescription'], $_POST['CostAct'], $_POST['DepnAct'], $_POST['DisposalAct'], $_POST['AccumDepnAct'], $SelectedCategory);

} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM fixedassets WHERE assetcategoryid='" . $SelectedCategory . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete category') . ' - ' . $MyRow[0] . ' ' . __('assets refer to it'),'warn');
	} else {
		$SQL="DELETE FROM fixedassetcategories WHERE categoryid='" . $SelectedCategory . "'";
		DB_query($SQL);
		prnMsg(__('Category deleted') . ': ' . $SelectedCategory,'success');
		unset ($SelectedCategory);
	}
}

echo '<div class="db-centered-container" style="width: 100%; max-width: 1400px; margin: 0 auto; padding: 0 20px; box-sizing: border-box;">
		<div class="db-main-layout" style="width: 100%; display: grid; grid-template-columns: 400px 1fr; gap: 30px; box-sizing: border-box;">';

// LEFT COLUMN: FORM
echo '<div class="db-layout-left">';
if (isset($SelectedCategory) and !isset($_POST['submit'])) {
	$SQL = "SELECT * FROM fixedassetcategories WHERE categoryid='" . $SelectedCategory . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$_POST['CategoryID'] = $MyRow['categoryid'];
	$_POST['CategoryDescription']  = $MyRow['categorydescription'];
	$_POST['CostAct']  = $MyRow['costact'];
	$_POST['DepnAct']  = $MyRow['depnact'];
	$_POST['DisposalAct']  = $MyRow['disposalact'];
	$_POST['AccumDepnAct']  = $MyRow['accumdepnact'];
}

$SQL = "SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 ORDER BY accountcode";
$BSAccountsResult = DB_query($SQL);
$SQL = "SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl!= 0 ORDER BY accountcode";
$PnLAccountsResult = DB_query($SQL);

if (!isset($_POST['CategoryDescription'])) $_POST['CategoryDescription'] = '';
if (!isset($_POST['CategoryID'])) $_POST['CategoryID'] = '';

echo '<form id="CategoryForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($SelectedCategory)) echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';

echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-lg);">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-edit"></i> ' . (isset($SelectedCategory) ? __('Amend Category') : __('Create Category')) . '</div>
		</div>
		<div class="db-card-body" style="padding: 25px;">';

if (isset($SelectedCategory)) {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Category Code') . '</label>
			<input type="hidden" name="CategoryID" value="' . $_POST['CategoryID'] . '" />
			<div class="db-font-bold" style="padding: 10px; background: var(--surface-alt); border-radius: 8px;">' . $_POST['CategoryID'] . '</div>
		  </div>';
} else {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Category Code') . '</label>
			<input type="text" name="CategoryID" required="required" class="db-input" maxlength="6" value="' . $_POST['CategoryID'] . '" />
		  </div>';
}

echo '<div class="db-form-group">
		<label class="db-label">' . __('Description') . '</label>
		<input type="text" name="CategoryDescription" required="required" class="db-input" maxlength="20" value="' . $_POST['CategoryDescription'] . '" />
	  </div>';

echo '<div class="db-form-group">
		<label class="db-label">' . __('Cost GL Account') . '</label>
		<select name="CostAct" required="required" class="db-select">';
while ($ActRow = DB_fetch_array($BSAccountsResult)){
	$sel = (isset($_POST['CostAct']) && $ActRow['accountcode']==$_POST['CostAct']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$ActRow['accountcode'] . '">' . htmlspecialchars($ActRow['accountname'],ENT_QUOTES,'UTF-8',false) . ' ('.$ActRow['accountcode'].')</option>';
}
echo '</select></div>';

DB_data_seek($PnLAccountsResult, 0);
echo '<div class="db-form-group">
		<label class="db-label">' . __('Depreciation (P&L)') . '</label>
		<select name="DepnAct" required="required" class="db-select">';
while ($ActRow = DB_fetch_array($PnLAccountsResult)) {
	$sel = (isset($_POST['DepnAct']) && $ActRow['accountcode']==$_POST['DepnAct']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$ActRow['accountcode'] . '">' . htmlspecialchars($ActRow['accountname'],ENT_QUOTES,'UTF-8',false) . ' ('.$ActRow['accountcode'].')</option>';
}
echo '</select></div>';

DB_data_seek($PnLAccountsResult,0);
echo '<div class="db-form-group">
		<label class="db-label">' . __('Disposal GL') . '</label>
		<select name="DisposalAct" required="required" class="db-select">';
while ($ActRow = DB_fetch_array($PnLAccountsResult)) {
	$sel = (isset($_POST['DisposalAct']) && $ActRow['accountcode']==$_POST['DisposalAct']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$ActRow['accountcode'] . '">' . htmlspecialchars($ActRow['accountname'],ENT_QUOTES,'UTF-8',false) . ' ('.$ActRow['accountcode'].')' . '</option>';
}
echo '</select></div>';

DB_data_seek($BSAccountsResult,0);
echo '<div class="db-form-group">
		<label class="db-label">' . __('Accum. Depn (BS)') . '</label>
		<select name="AccumDepnAct" required="required" class="db-select">';
while ($ActRow = DB_fetch_array($BSAccountsResult)) {
	$sel = (isset($_POST['AccumDepnAct']) && $ActRow['accountcode']==$_POST['AccumDepnAct']) ? 'selected="selected"' : '';
	echo '<option '.$sel.' value="'.$ActRow['accountcode'] . '">' . htmlspecialchars($ActRow['accountname'],ENT_QUOTES,'UTF-8',false) . ' ('.$ActRow['accountcode'].')' . '</option>';
}
echo '</select></div>';

echo '</div>
		<div class="db-card-footer" style="padding: 20px; background: var(--surface-alt); display: flex; flex-direction: column; gap: 10px;">
			<button type="submit" name="submit" class="db-btn db-btn-primary">
				<i class="fas fa-save"></i> ' . (isset($SelectedCategory) ? __('Update Category') : __('Create Category')) . '
			</button>';
if (isset($SelectedCategory)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="db-btn db-btn-secondary" style="justify-content: center;">
			<i class="fas fa-times"></i> ' . __('Cancel Edit') . '
		  </a>';
}
echo '	</div>
	</div>
</form>
</div>';

// RIGHT COLUMN: TABLE
echo '<div class="db-layout-right">';
$SQL = "SELECT * FROM fixedassetcategories";
$Result = DB_query($SQL);
echo '<div class="db-card" style="border: none; box-shadow: var(--shadow-md); overflow: hidden;">
			<div class="db-table-wrap" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
				<table class="db-table monochromatic-table" style="width: 100%; min-width: 800px;">
					<thead>
						<tr>
							<th style="padding-left: 20px;">' . __('Code') . '</th>
							<th>' . __('Description') . '</th>
							<th class="number">' . __('Cost GL') . '</th>
							<th class="number">' . __('Depn GL') . '</th>
							<th class="number">' . __('Accum GL') . '</th>
							<th style="padding-right: 20px; text-align: center;">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		$isSelStyle = (isset($SelectedCategory) && $SelectedCategory == $MyRow['categoryid']) ? 'style="background: var(--surface-alt);"' : '';
		echo '<tr class="db-table-row" '.$isSelStyle.'>
			<td data-label="' . __('Category Code') . '">' . $MyRow['categoryid'] . '</td>
			<td data-label="' . __('Description') . '">' . $MyRow['categorydescription'] . '</td>
			<td data-label="' . __('Cost GL Code') . '">' . $MyRow['costact'] . '</td>
			<td data-label="' . __('Depn GL Code') . '">' . $MyRow['depnact'] . '</td>
			<td data-label="' . __('Accum Depn GL Code') . '">' . $MyRow['accumdepnact'] . '</td>
			<td class="db-table-actions" data-label="' . __('Actions') . '" style="padding-right: 20px; white-space: nowrap; text-align: center;">
				<div style="display: flex; gap: 8px; justify-content: flex-end;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . urlencode($MyRow['categoryid']) . '" class="db-btn db-btn-secondary db-btn-sm" style="width: auto !important;">
						<i class="fas fa-edit"></i> <span class="db-btn-text-mobile">' . __('Edit') . '</span>
					</a>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . urlencode($MyRow['categoryid']) . '&delete=1" class="db-btn db-btn-danger db-btn-sm" style="width: auto !important;" onclick="return confirm(\'' . __('Are you sure you wish to delete this category?') . '\');">
						<i class="fas fa-trash"></i> <span class="db-btn-text-mobile">' . __('Del') . '</span>
					</a>
				</div>
			</td>
		</tr>';
	}
echo '			</tbody>
			</table>
		</div>
	  </div>
</div>';

echo '	</div>
	  </div>'; // End centered-container/layout-grid

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

@media (max-width: 768px) {
	.db-page-header { padding: 15px !important; }
	.db-page-title { font-size: 1.25rem !important; }
	.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
	.db-table-wrap { border: none !important; }
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
		box-shadow: var(--shadow-sm) !important;
	}
	.monochromatic-table td { 
		border: none !important; 
		display: flex !important; 
		justify-content: space-between !important; 
		padding: 8px 0 !important; 
		text-align: right !important;
		font-size: 0.85rem !important;
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
	.monochromatic-table td.db-table-actions div { justify-content: center !important; width: 100% !important; gap: 10px !important; }
    
	.db-card-body { padding: 20px !important; }
	.db-btn:not(.db-btn-sm) { width: 100% !important; display: flex !important; justify-content: center !important; }
	.db-btn-text-mobile { display: inline !important; margin-left: 5px; }
}
@media (min-width: 769px) {
	.db-btn-text-mobile { display: none; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
?>
