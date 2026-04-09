<?php
require (__DIR__ . '/includes/session.php');

$Title = __('Sales Area Maintenance');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'Areas';
include ('includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Define geographical or logical sales territories') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Search') . '
				</a>
			</div>
		</header>';

if (isset($_GET['SelectedArea'])) {
	$SelectedArea = mb_strtoupper($_GET['SelectedArea']);
} elseif (isset($_POST['SelectedArea'])) {
	$SelectedArea = mb_strtoupper($_POST['SelectedArea']);
}

$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	$i = 1;

	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$_POST['AreaCode'] = mb_strtoupper($_POST['AreaCode']);
	$SQL = "SELECT areacode FROM areas WHERE areacode='" . $_POST['AreaCode'] . "'";
	$Result = DB_query($SQL);
	// mod to handle 3 char area codes
	if (mb_strlen($_POST['AreaCode']) > 3) {
		$InputError = 1;
		prnMsg(__('The area code must be three characters or less long'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	}
	elseif (DB_num_rows($Result) > 0 and !isset($SelectedArea)) {
		$InputError = 1;
		prnMsg(__('The area code entered already exists'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	}
	elseif (mb_strlen($_POST['AreaDescription']) > 25) {
		$InputError = 1;
		prnMsg(__('The area description must be twenty five characters or less long'), 'error');
		$Errors[$i] = 'AreaDescription';
		$i++;
	}
	elseif (trim($_POST['AreaCode']) == '') {
		$InputError = 1;
		prnMsg(__('The area code may not be empty'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	}
	elseif (trim($_POST['AreaDescription']) == '') {
		$InputError = 1;
		prnMsg(__('The area description may not be empty'), 'error');
		$Errors[$i] = 'AreaDescription';
		$i++;
	}

	if (isset($SelectedArea) and $InputError != 1) {

		/*SelectedArea could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		$SQL = "UPDATE areas SET areadescription='" . $_POST['AreaDescription'] . "'
						WHERE areacode = '" . $SelectedArea . "'";

		$Msg = __('Area code') . ' ' . $SelectedArea . ' ' . __('has been updated');

	}
	elseif ($InputError != 1) {

		/*Selectedarea is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new area form */

		$SQL = "INSERT INTO areas (areacode,
								areadescription
							) VALUES (
								'" . $_POST['AreaCode'] . "',
								'" . $_POST['AreaDescription'] . "'
							)";

		$SelectedArea = $_POST['AreaCode'];
		$Msg = __('New area code') . ' ' . $_POST['AreaCode'] . ' ' . __('has been inserted');
	}
	else {
		$Msg = '';
	}

	//run the SQL from either of the above possibilites
	if ($InputError != 1) {
		$ErrMsg = __('The area could not be added or updated because');
		$Result = DB_query($SQL, $ErrMsg);
		unset($SelectedArea);
		unset($_POST['AreaCode']);
		unset($_POST['AreaDescription']);
		prnMsg($Msg, 'success');
	}

} elseif (isset($_GET['delete'])) {
	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorsMaster'
	$SQL = "SELECT COUNT(branchcode) AS branches FROM custbranch WHERE custbranch.area='$SelectedArea'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['branches'] > 0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this area because customer branches have been created using this area'), 'warn');
		echo '<br />' . __('There are') . ' ' . $MyRow['branches'] . ' ' . __('branches using this area code');

	}
	else {
		$SQL = "SELECT COUNT(area) AS records FROM salesanalysis WHERE salesanalysis.area ='$SelectedArea'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		if ($MyRow['records'] > 0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete this area because sales analysis records exist that use this area'), 'warn');
			echo '<br />' . __('There are') . ' ' . $MyRow['records'] . ' ' . __('sales analysis records referring this area code');
		}
	}

	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM areas WHERE areacode='" . $SelectedArea . "'";
		$Result = DB_query($SQL);
		prnMsg(__('Area Code') . ' ' . $SelectedArea . ' ' . __('has been deleted') . ' !', 'success');
	} //end if Delete area
	unset($SelectedArea);
	unset($_GET['delete']);
}



	$SQL = "SELECT areacode, areadescription FROM areas";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
					' . __('Defined Sales Areas') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Area Name') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow['areacode'] . '</td>
				<td>' . $MyRow['areadescription'] . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedArea=' . $MyRow['areacode'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . $RootPath . '/SelectCustomer.php?Area=' . $MyRow['areacode'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('View Customers') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedArea=' . $MyRow['areacode'] . '&amp;delete=yes" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this area?') . '\');">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</a>
					</div>
				</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';

//end of ifs and buts!
	if (isset($SelectedArea)) {
		echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Review Areas Defined') . '</a></div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedArea)) {
			// Editing an existing area
			$Res = DB_query("SELECT areacode, areadescription FROM areas WHERE areacode='" . $SelectedArea . "'");
			$MyRow = DB_fetch_array($Res);

			$_POST['AreaCode'] = $MyRow['areacode'];
			$_POST['AreaDescription'] = $MyRow['areadescription'];

			echo '<input type="hidden" name="SelectedArea" value="' . $SelectedArea . '" />';
			echo '<input type="hidden" name="AreaCode" value="' . $_POST['AreaCode'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Area Details') . ': ' . $_POST['AreaCode'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Area Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['AreaCode'] . '" disabled />
							</div>';

		} else {
			if (!isset($_POST['AreaCode'])) {
				$_POST['AreaCode'] = '';
			}
			if (!isset($_POST['AreaDescription'])) {
				$_POST['AreaDescription'] = '';
			}
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Define New Sales Area') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Area Code') . '</label>
								<input type="text" name="AreaCode" class="db-input" required maxlength="3" autofocus value="' . $_POST['AreaCode'] . '" />
								<p class="db-field-help">' . __('Enter a unique 3-character code') . '</p>
							</div>';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Area Name') . '</label>
				<input type="text" name="AreaDescription" class="db-input" required maxlength="25" value="' . $_POST['AreaDescription'] . '" />
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Area Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page
include ('includes/footer.php');

