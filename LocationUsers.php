<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Inventory Location Authorised Users');
$ViewTopic = 'Inventory';
$BookMark = 'LocationUsers';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_POST['SelectedUser']);
} elseif (isset($_GET['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_GET['SelectedUser']);
} else {
	$SelectedUser = '';
}

if (isset($_POST['SelectedLocation'])) {
	$SelectedLocation = mb_strtoupper($_POST['SelectedLocation']);
} elseif (isset($_GET['SelectedLocation'])) {
	$SelectedLocation = mb_strtoupper($_GET['SelectedLocation']);
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if ($_POST['SelectedUser'] == '') {
		$InputError = 1;
		prnMsg(__('You have not selected an user to be authorised to use this Location'), 'error');
	}

	if ($InputError != 1) {
		$CheckResult = DB_query("SELECT count(*) FROM locationusers WHERE loccode= '" . $_POST['SelectedLocation'] . "' AND userid = '" . $_POST['SelectedUser'] . "'");
		$CheckRow = DB_fetch_row($CheckResult);

		if ($CheckRow[0] > 0) {
			prnMsg(__('The user') . ' ' . $_POST['SelectedUser'] . ' ' . __('is already authorised'), 'error');
		} else {
			$SQL = "INSERT INTO locationusers (loccode, userid, canview, canupd)
					VALUES ('" . $_POST['SelectedLocation'] . "', '" . $_POST['SelectedUser'] . "', '1', '1')";
			DB_query($SQL);
			prnMsg(__('Access granted to user') . ': ' . $_POST['SelectedUser'], 'success');
		}
	}
	unset($_POST['SelectedUser']);
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM locationusers WHERE loccode='" . $SelectedLocation . "' AND userid='" . $SelectedUser . "'";
	DB_query($SQL);
	prnMsg(__('Access removed for user') . ' ' . $SelectedUser, 'success');
} elseif (isset($_GET['ToggleUpdate'])) {
	$SQL = "UPDATE locationusers SET canupd='" . $_GET['ToggleUpdate'] . "' WHERE loccode='" . $SelectedLocation . "' AND userid='" . $SelectedUser . "'";
	DB_query($SQL);
	prnMsg(__('Update authority toggled for user') . ' ' . $SelectedUser, 'success');
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card" style="margin-bottom: 20px;">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Context Selection') . '</h3></div>
		<div class="db-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-form-group">
					<label class="db-label">' . __('Target Location') . '</label>
					<select name="SelectedLocation" class="db-select" onchange="this.form.submit()">
						<option value="">' . __('Not Yet Selected') . '</option>';
$LRes = DB_query("SELECT loccode, locationname FROM locations");
while ($Lrow = DB_fetch_array($LRes)) {
	echo '<option ' . ((isset($SelectedLocation) && $SelectedLocation == $Lrow['loccode']) ? 'selected' : '') . ' value="' . $Lrow['loccode'] . '">' . $Lrow['loccode'] . ' - ' . $Lrow['locationname'] . '</option>';
}
echo '				</select>
				</div>
			</form>
		</div>
	  </div>';

if (isset($SelectedLocation) && $SelectedLocation != '') {
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-user-plus"></i> ' . __('Authorise User') . '</h3></div>
			<div class="db-card-body">
				<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<input type="hidden" name="SelectedLocation" value="' . $SelectedLocation . '" />
					<div class="db-form-group">
						<label class="db-label">' . __('Select User') . '</label>
						<select name="SelectedUser" class="db-select" required>
							<option value="">' . __('Search User...') . '</option>';
	$URes = DB_query("SELECT userid, realname FROM www_users WHERE NOT EXISTS (SELECT userid FROM locationusers WHERE loccode='" . $SelectedLocation . "' AND userid=www_users.userid)");
	while ($Urow = DB_fetch_array($URes)) {
		echo '<option value="' . $Urow['userid'] . '">' . $Urow['userid'] . ' - ' . $Urow['realname'] . '</option>';
	}
	echo '				</select>
					</div>
					<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;"><i class="fas fa-shield-check"></i> ' . __('Grant Authority') . '</button>
				</form>
			</div>
		  </div>';
}
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
if (isset($SelectedLocation) && $SelectedLocation != '') {
	$SQLName = "SELECT locationname FROM locations WHERE loccode='" . $SelectedLocation . "'";
	$MyRow = DB_fetch_array(DB_query($SQLName));
	
	$SQL = "SELECT locationusers.userid, canview, canupd, www_users.realname
			FROM locationusers INNER JOIN www_users ON locationusers.userid=www_users.userid
			WHERE locationusers.loccode='" . $SelectedLocation . "'
			ORDER BY locationusers.userid ASC";
	$Result = DB_query($SQL);

	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-users-cog"></i> ' . __('Access Permissions') . ': <span class="text-primary">' . $MyRow['locationname'] . '</span></h3></div>
			<div class="db-card-body p-0">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Security Context') . '</th>
								<th class="text-center">' . __('View') . '</th>
								<th class="text-center">' . __('Update') . '</th>
								<th class="text-right">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	if (DB_num_rows($Result) == 0) {
		echo '<tr><td colspan="4" class="text-center db-muted">' . __('No users authorised for this location.') . '</td></tr>';
	} else {
		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr>
					<td>
						<div class="db-font-bold text-primary">' . $MyRow['userid'] . '</div>
						<div class="db-muted" style="font-size: 0.8rem;">' . $MyRow['realname'] . '</div>
					</td>
					<td class="text-center"><span class="db-badge db-badge-success">' . __('Enabled') . '</span></td>
					<td class="text-center">';
			if ($MyRow['canupd'] == 1) {
				echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedUser=' . $MyRow['userid'] . '&amp;ToggleUpdate=0&amp;SelectedLocation=' . $SelectedLocation . '" class="db-badge db-badge-success" style="cursor: pointer;"><i class="fas fa-check-circle"></i> ' . __('Yes') . '</a>';
			} else {
				echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedUser=' . $MyRow['userid'] . '&amp;ToggleUpdate=1&amp;SelectedLocation=' . $SelectedLocation . '" class="db-badge db-badge-secondary" style="cursor: pointer;"><i class="fas fa-ban"></i> ' . __('No') . '</a>';
			}
			echo '</td>
					<td class="text-right">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedUser=' . $MyRow['userid'] . '&amp;delete=yes&amp;SelectedLocation=' . $SelectedLocation . '" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Un-authorise user?') . '\');"><i class="fas fa-user-slash"></i></a>
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
				<i class="fas fa-user-shield fa-4x db-muted" style="margin-bottom: 20px;"></i>
				<h3 class="db-font-bold">' . __('Location Access Control') . '</h3>
				<p class="db-muted">' . __('Select an inventory location from the sidebar to manage its authorised users and operational permissions.') . '</p>
			</div>
		  </div>';
}
echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
