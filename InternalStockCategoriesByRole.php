<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Internal Stock Categories Requests By Security Role');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryRequests';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedType'])){
	$SelectedType = mb_strtoupper($_POST['SelectedType']);
} elseif (isset($_GET['SelectedType'])){
	$SelectedType = mb_strtoupper($_GET['SelectedType']);
} else {
	$SelectedType='';
}

if (isset($_POST['SelectedRole'])){
	$SelectedRole = mb_strtoupper($_POST['SelectedRole']);
} elseif (isset($_GET['SelectedRole'])){
	$SelectedRole = mb_strtoupper($_GET['SelectedRole']);
}

if (isset($_POST['submit'])) {
	$InputError=0;
	if ($_POST['SelectedCategory']=='') {
		$InputError=1;
		prnMsg(__('You have not selected a stock category'),'error');
	}

	if ( $InputError !=1 ) {
		$CheckSQL = "SELECT count(*) FROM internalstockcatrole WHERE secroleid= '" .  $_POST['SelectedRole'] . "' AND categoryid = '" .  $_POST['SelectedCategory'] . "'";
		$Checkresult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($Checkresult);
		if ( $CheckRow[0] >0) {
			prnMsg( __('Stock Category already allowed'),'error');
		} else {
			$SQL = "INSERT INTO internalstockcatrole (secroleid, categoryid) VALUES ('" . $_POST['SelectedRole'] . "', '" . $_POST['SelectedCategory'] . "')";
			$Result = DB_query($SQL);
			prnMsg(__('Mapping updated successfully'),'success');
		}
	}
	unset($_POST['SelectedCategory']);
} elseif ( isset($_GET['delete']) ) {
	$SQL="DELETE FROM internalstockcatrole WHERE secroleid='".$SelectedRole."' AND categoryid='".$SelectedType."'";
	DB_query($SQL);
	prnMsg(__('Internal Stock Category alignment removed'),'success');
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-shield-halved"></i> ' . __('Security Role') . '</h3></div>
		<div class="db-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-form-group">
					<label class="db-label">' . __('Select User Role') . '</label>
					<select name="SelectedRole" class="db-select" onchange="this.form.submit()">
						<option value="">' . __('Not Yet Selected') . '</option>';
$SRoles = DB_query("SELECT secroleid, secrolename FROM securityroles");
while ($SRow = DB_fetch_array($SRoles)) {
	echo '<option ' . ((isset($SelectedRole) && $SelectedRole == $SRow['secroleid']) ? 'selected' : '') . ' value="' . $SRow['secroleid'] . '">' . $SRow['secroleid'] . ' - ' . $SRow['secrolename'] . '</option>';
}
echo '				</select>
				</div>';
if (isset($SelectedRole) && $SelectedRole != '') {
	echo '		<div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-soft);">
					<label class="db-label">' . __('Add Internal Category') . '</label>
					<select name="SelectedCategory" class="db-select" required>
						<option value="">' . __('Select Category...') . '</option>';
	$SCats = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
	while ($SCatRow = DB_fetch_array($SCats)) {
		echo '<option value="' . $SCatRow['categoryid'] . '">' . $SCatRow['categoryid'] . ' - ' . $SCatRow['categorydescription'] . '</option>';
	}
	echo '			</select>
					<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;"><i class="fas fa-link"></i> ' . __('Grant Access') . '</button>
				</div>';
}
echo '			</form>
		</div>
	  </div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
if (isset($SelectedRole) && $SelectedRole != '') {
	
	// PAGINATION LOGIC
	$SQLCount = "SELECT COUNT(*) FROM internalstockcatrole WHERE secroleid='".$SelectedRole."'";
	$CountResult = DB_query($SQLCount);
	$TotalMatches = DB_fetch_row($CountResult)[0];
	$DisplayRecords = $_SESSION['DisplayRecordsMax'] ?? 20;
	$Pages = ceil($TotalMatches / $DisplayRecords);
	$Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
	if ($Page < 1) $Page = 1;
	if ($Page > $Pages && $Pages > 0) $Page = $Pages;
	$Offset = ($Page - 1) * $DisplayRecords;

	$SQL = "SELECT internalstockcatrole.categoryid, stockcategory.categorydescription
			FROM internalstockcatrole INNER JOIN stockcategory ON internalstockcatrole.categoryid=stockcategory.categoryid
			WHERE internalstockcatrole.secroleid='".$SelectedRole."'
			ORDER BY internalstockcatrole.categoryid ASC
			LIMIT " . $DisplayRecords . " OFFSET " . $Offset;
	$Result = DB_query($SQL);

	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-lock"></i> ' . __('Internal Access Rights') . ': <span class="text-primary">' . $SelectedRole . '</span></h3>';
	
	if ($Pages > 1) {
		echo '	<div class="db-pagination" style="display: flex; gap: 5px; align-items: center;">';
		if ($Page > 1) {
			echo '	<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $SelectedRole . '&Page=' . ($Page - 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-left"></i></a>';
		}
		echo '		<span class="db-muted" style="font-size: 0.8rem; margin: 0 10px;">' . __('Page') . ' ' . $Page . ' ' . __('of') . ' ' . $Pages . '</span>';
		if ($Page < $Pages) {
			echo '	<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $SelectedRole . '&Page=' . ($Page + 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-right"></i></a>';
		}
		echo '	</div>';
	}

	echo '</div>
			<div class="db-card-body p-0">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Category Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	if ($TotalMatches == 0) {
		echo '<tr><td colspan="3" class="text-center db-muted p-5">' . __('No internal categories assigned to this role.') . '</td></tr>';
	} else {
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr>
					<td><div class="db-badge db-badge-secondary">' . $MyRow['categoryid'] . '</div></td>
					<td>' . $MyRow['categorydescription'] . '</td>
					<td class="text-right">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedType=' . $MyRow['categoryid'] . '&amp;delete=yes&amp;SelectedRole=' . $SelectedRole . '&amp;Page=' . $Page . '" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Remove category access for this role?') . '\');"><i class="fas fa-unlink"></i> ' . __('Remove') . '</a>
					</td>
				  </tr>';
		}
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';
} else {
	echo '<div class="db-card">
			<div class="db-card-body text-center" style="padding: 60px;">
				<i class="fas fa-shield-halved fa-4x db-muted" style="margin-bottom: 20px;"></i>
				<h3 class="db-font-bold">' . __('Role Mapping') . '</h3>
				<p class="db-muted">' . __('Select a security role from the sidebar to view and maintain its internal stock request categories.') . '</p>
			</div>
		  </div>';
}
echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
