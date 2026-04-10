<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Credit Status Code Maintenance');
$ViewTopic = 'CreditStatus';
$BookMark = 'CreditStatus';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage credit hold reasons and invoice restrictions') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Search') . '
				</a>
			</div>
		</header>';

if (isset($_GET['SelectedReason'])){
	$SelectedReason = $_GET['SelectedReason'];
} elseif (isset($_POST['SelectedReason'])){
	$SelectedReason = $_POST['SelectedReason'];
}

$Errors = array();
$InputError = 0;


if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$i=1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs are sensible

	$SQL="SELECT count(reasoncode)
			FROM holdreasons WHERE reasoncode='".$_POST['ReasonCode']."'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);

	if ($MyRow[0]!= 0 and !isset($SelectedReason)) {
		$InputError = 1;
		prnMsg( __('The credit status code already exists in the database'),'error');
		$Errors[$i] = 'ReasonCode';
		$i++;
	}
	if (!is_numeric($_POST['ReasonCode'])) {
		$InputError = 1;
		prnMsg(__('The status code name must be an integer'),'error');
		$Errors[$i] = 'ReasonCode';
		$i++;
	}
	if (mb_strlen($_POST['ReasonDescription']) > 30) {
		$InputError = 1;
		prnMsg(__('The credit status description must be thirty characters or less long'),'error');
	}
	if (mb_strlen($_POST['ReasonDescription']) == 0) {
		$InputError = 1;
		prnMsg(__('The credit status description must be entered'),'error');
		$Errors[$i] = 'ReasonDescription';
		$i++;
	}

	$Msg='';

	if (isset($SelectedReason) AND $InputError != 1) {

		/*SelectedReason could also exist if submit had not been clicked this code would not run in this case cos submit is false of course	see the delete code below*/

		if (isset($_POST['DisallowInvoices']) and $_POST['DisallowInvoices']=='on'){
			$SQL = "UPDATE holdreasons SET
							reasondescription='" . $_POST['ReasonDescription'] . "',
							dissallowinvoices=1
							WHERE reasoncode = '".$SelectedReason."'";
		} else {
			$SQL = "UPDATE holdreasons SET
							reasondescription='" . $_POST['ReasonDescription'] . "',
							dissallowinvoices=0
							WHERE reasoncode = '".$SelectedReason."'";
		}
		$Msg = __('The credit status record has been updated');

	} elseif ($InputError != 1) {

	/*Selected Reason is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new status code form */

		if (isset($_POST['DisallowInvoices']) AND $_POST['DisallowInvoices']=='on'){

			$SQL = "INSERT INTO holdreasons (reasoncode,
											reasondescription,
											dissallowinvoices)
									VALUES ('" .$_POST['ReasonCode'] . "',
											'".$_POST['ReasonDescription'] . "',
											1)";
		} else {
			$SQL = "INSERT INTO holdreasons (reasoncode,
											reasondescription,
											dissallowinvoices)
									VALUES ('" . $_POST['ReasonCode'] . "',
											'" . $_POST['ReasonDescription'] ."',
											0)";
		}

		$Msg = __('A new credit status record has been inserted');
	}
	//run the SQL from either of the above possibilites
	$Result = DB_query($SQL);
	if ($Msg !=  '') {
		prnMsg($Msg,'success');
	}
	unset ($SelectedReason);
	unset ($_POST['ReasonCode']);
	unset ($_POST['ReasonDescription']);
	unset ($_POST['submit']);
} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN DebtorsMaster

	$SQL= "SELECT COUNT(*)
			FROM debtorsmaster
			WHERE debtorsmaster.holdreason='".$SelectedReason."'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg( __('Cannot delete this credit status code because customer accounts have been created referring to it'),'warn');
		echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('customer accounts that refer to this credit status code');
	}  else {
		//only delete if used in neither customer or supplier accounts

		$SQL="DELETE FROM holdreasons WHERE reasoncode='" . $SelectedReason . "'";
		$Result = DB_query($SQL);
		prnMsg(__('This credit status code has been deleted'),'success');
	}
	//end if status code used in customer or supplier accounts
	unset ($_GET['delete']);
	unset ($SelectedReason);

}

if (!isset($SelectedReason)) {

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedReason will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of status codes will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
					' . __('Defined Credit Status Codes') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-center">' . __('Restricts Invoicing') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		$statusBadge = ($MyRow['dissallowinvoices'] == 0) 
			? '<span class="db-badge db-badge-success">' . __('Invoice OK') . '</span>'
			: '<span class="db-badge db-badge-danger">' . __('NO INVOICING') . '</span>';

		echo '<tr>
				<td class="font-bold">' . $MyRow['reasoncode'] . '</td>
				<td>' . $MyRow['reasondescription'] . '</td>
				<td class="text-center">' . $statusBadge . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedReason=' . $MyRow['reasoncode'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedReason=' . $MyRow['reasoncode'] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this credit status record?') . '\');">
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

} //end of ifs and buts!

	if (isset($SelectedReason)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Show Defined Credit Status Codes') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedReason) and ($InputError != 1)) {
			// Editing an existing status code
			$SQL = "SELECT reasoncode, reasondescription, dissallowinvoices FROM holdreasons WHERE reasoncode='" . $SelectedReason . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			$_POST['ReasonCode'] = $MyRow['reasoncode'];
			$_POST['ReasonDescription'] = $MyRow['reasondescription'];
			$_POST['DisallowInvoices'] = $MyRow['dissallowinvoices'];

			echo '<input type="hidden" name="SelectedReason" value="' . $SelectedReason . '" />';
			echo '<input type="hidden" name="ReasonCode" value="' . $_POST['ReasonCode'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Credit Status') . ': ' . $_POST['ReasonCode'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Status Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['ReasonCode'] . '" disabled />
							</div>';

		} else {
			if (!isset($_POST['ReasonCode'])) {
				$_POST['ReasonCode'] = '';
			}
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create Credit Status Code') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Status Code') . '</label>
								<input type="number" name="ReasonCode" class="db-input" required value="' . $_POST['ReasonCode'] . '" autofocus />
								<p class="db-field-help">' . __('Enter a unique numeric code') . '</p>
							</div>';
		}

		if (!isset($_POST['ReasonDescription'])) {
			$_POST['ReasonDescription'] = '';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Description') . '</label>
				<input type="text" name="ReasonDescription" class="db-input" required maxlength="30" value="' . $_POST['ReasonDescription'] . '" />
			</div>
		</div><br />'; // End db-grid

		echo '<div class="db-field" style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface-alt); border-radius: var(--radius-md); border: 1px solid var(--border-color);">
				<input type="checkbox" name="DisallowInvoices" id="DisallowInvoices" style="width: 20px; height: 20px; cursor: pointer;" ' . ((isset($_POST['DisallowInvoices']) and $_POST['DisallowInvoices'] == 1) ? 'checked' : '') . ' />
				<label for="DisallowInvoices" class="db-label" style="margin-bottom: 0; cursor: pointer;">' . __('Disallow Invoices for customers with this status') . '</label>
			</div>';

		echo '</div>'; // End db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Credit Status Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
