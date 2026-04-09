<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Terms Maintenance');
$ViewTopic = 'PaymentTerms';
$BookMark = 'PaymentTerms';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Configure credit terms and payment deadlines') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if (isset($_GET['SelectedTerms'])){
	$SelectedTerms = $_GET['SelectedTerms'];
} elseif (isset($_POST['SelectedTerms'])){
	$SelectedTerms = $_POST['SelectedTerms'];
}

$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	$i=1;

	//first off validate inputs are sensible

	if (mb_strlen($_POST['TermsIndicator']) < 1) {
		$InputError = 1;
		prnMsg(__('The payment terms name must exist'),'error');
		$Errors[$i] = 'TermsIndicator';
		$i++;
	}
	if (mb_strlen($_POST['TermsIndicator']) > 2) {
		$InputError = 1;
		prnMsg(__('The payment terms name must be two characters or less long'),'error');
		$Errors[$i] = 'TermsIndicator';
		$i++;
	}
	if (empty($_POST['DayNumber']) OR !is_numeric(filter_number_format($_POST['DayNumber'])) OR filter_number_format($_POST['DayNumber']) <= 0){
		$InputError = 1;
		prnMsg( __('The number of days or the day in the following month must be numeric') ,'error');
		$Errors[$i] = 'DayNumber';
		$i++;
	}
	if (empty($_POST['Terms']) OR mb_strlen($_POST['Terms']) > 40) {
		$InputError = 1;
		prnMsg( __('The terms description must be forty characters or less long') ,'error');
		$Errors[$i] = 'Terms';
		$i++;
	}
	/*
	if ($_POST['DayNumber'] > 30 AND empty($_POST['DaysOrFoll'])) {
		$InputError = 1;
		prnMsg( __('When the check box is not checked to indicate a day in the following month is the due date') . ', ' . __('the due date cannot be a day after the 30th') . '. ' . __('A number between 1 and 30 is expected') ,'error');
		$Errors[$i] = 'DayNumber';
		$i++;
	} */
	if ($_POST['DayNumber']>360 AND !empty($_POST['DaysOrFoll'])) {
		$InputError = 1;
		prnMsg( __('When the check box is checked to indicate that the term expects a number of days after which accounts are due') . ', ' . __('the number entered should be less than 361 days') ,'error');
		$Errors[$i] = 'DayNumber';
		$i++;
	}

	if (isset($SelectedTerms) AND $InputError != 1) {

		/*SelectedTerms could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		if (isset($_POST['DaysOrFoll']) AND $_POST['DaysOrFoll']=='on') {
			$SQL = "UPDATE paymentterms SET
							terms='" . $_POST['Terms'] . "',
							dayinfollowingmonth=0,
							daysbeforedue='" . filter_number_format($_POST['DayNumber']) . "'
					WHERE termsindicator = '" . $SelectedTerms . "'";
		} else {
			$SQL = "UPDATE paymentterms SET
							terms='" . $_POST['Terms'] . "',
							dayinfollowingmonth='" . filter_number_format($_POST['DayNumber']) . "',
							daysbeforedue=0
						WHERE termsindicator = '" . $SelectedTerms . "'";
		}

		$Msg = __('The payment terms definition record has been updated') . '.';
	} elseif ($InputError != 1) {

	/*Selected terms is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new payment terms form */

		if ($_POST['DaysOrFoll']=='on') {
			$SQL = "INSERT INTO paymentterms (termsindicator,
								terms,
								daysbeforedue,
								dayinfollowingmonth)
						VALUES (
							'" . $_POST['TermsIndicator'] . "',
							'" . $_POST['Terms'] . "',
							'" . filter_number_format($_POST['DayNumber']) . "',
							0
						)";
		} else {
			$SQL = "INSERT INTO paymentterms (termsindicator,
								terms,
								daysbeforedue,
								dayinfollowingmonth)
						VALUES (
							'" . $_POST['TermsIndicator'] . "',
							'" . $_POST['Terms'] . "',
							0,
							'" . filter_number_format($_POST['DayNumber']) . "'
							)";
		}

		$Msg = __('The payment terms definition record has been added') . '.';
	}
	if ($InputError != 1){
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg,'success');
		unset($SelectedTerms);
		unset($_POST['DaysOrFoll']);
		unset($_POST['TermsIndicator']);
		unset($_POST['Terms']);
		unset($_POST['DayNumber']);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN DebtorsMaster

	$SQL= "SELECT COUNT(*) FROM debtorsmaster WHERE debtorsmaster.paymentterms = '" . $SelectedTerms . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg( __('Cannot delete this payment term because customer accounts have been created referring to this term'),'warn');
		echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('customer accounts that refer to this payment term');
	} else {
		$SQL= "SELECT COUNT(*) FROM suppliers WHERE suppliers.paymentterms = '" . $SelectedTerms . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg( __('Cannot delete this payment term because supplier accounts have been created referring to this term'),'warn');
			echo '<br /> ' . __('There are') . ' ' . $MyRow[0] . ' ' . __('supplier accounts that refer to this payment term');
		} else {
			//only delete if used in neither customer or supplier accounts

			$SQL="DELETE FROM paymentterms WHERE termsindicator='" . $SelectedTerms . "'";
			$Result = DB_query($SQL);
			prnMsg( __('The payment term definition record has been deleted') . '!','success');
		}
	}
	//end if payment terms used in customer or supplier accounts

}

if (!isset($SelectedTerms)) {

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedTerms will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of payment termss will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$SQL = "SELECT termsindicator, terms, daysbeforedue, dayinfollowingmonth FROM paymentterms";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.94 2.14c.46.45.8 1.03.97 1.67C21.41 12.06 22 13.94 22 16c0 1.1-.9 2-2 2h-1v1c0 1.1-.9 2-2 2h-4c-1.1 0-2-.9-2-2v-1h-2v1c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2v-1H2c-1.1 0-2-.9-2-2 0-2.06.59-3.94 1.09-4.19.17-.64.51-1.22.97-1.67C2.71 8.5 4.5 7 7 7c2.5 0 4.29 1.5 4.94 2.14.46.45.8 1.03.97 1.67.5 2.06 1.09 3.94 1.09 4.19.17.64.51 1.22.97 1.67C15.71 15.5 17.5 14 20 14c2.5 0 4.29 1.5 4.94 2.14"></path></svg>
					' . __('Defined Payment Terms') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Following Month') . '</th>
								<th>' . __('Due After') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		$FollMthText = ($MyRow['dayinfollowingmonth'] == 0) ? '<span class="text-muted">' . __('N/A') . '</span>' : $MyRow['dayinfollowingmonth'] . __('th');
		$DueAfterText = ($MyRow['daysbeforedue'] == 0) ? '<span class="text-muted">' . __('N/A') . '</span>' : $MyRow['daysbeforedue'] . ' ' . __('days');

		echo '<tr>
				<td class="font-bold">' . $MyRow['termsindicator'] . '</td>
				<td>' . $MyRow['terms'] . '</td>
				<td>' . $FollMthText . '</td>
				<td>' . $DueAfterText . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTerms=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTerms=' . $MyRow[0] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this payment term?') . '\');">
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

	if (isset($SelectedTerms)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Show all Payment Terms Definitions') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedTerms)) {
			// Editing an existing payment terms
			$SQL = "SELECT termsindicator, terms, daysbeforedue, dayinfollowingmonth FROM paymentterms WHERE termsindicator='" . $SelectedTerms . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			$_POST['TermsIndicator'] = $MyRow['termsindicator'];
			$_POST['Terms'] = $MyRow['terms'];
			$DaysBeforeDue = $MyRow['daysbeforedue'];
			$DayInFollowingMonth = $MyRow['dayinfollowingmonth'];

			echo '<input type="hidden" name="SelectedTerms" value="' . $SelectedTerms . '" />';
			echo '<input type="hidden" name="TermsIndicator" value="' . $_POST['TermsIndicator'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Update Payment Terms') . ': ' . $_POST['TermsIndicator'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Term Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['TermsIndicator'] . '" disabled />
							</div>';

		} else {
			if (!isset($_POST['TermsIndicator'])) $_POST['TermsIndicator'] = '';
			if (!isset($DaysBeforeDue)) $DaysBeforeDue = 0;
			if (!isset($_POST['Terms'])) $_POST['Terms'] = '';
			unset($DayInFollowingMonth);

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('New Payment Terms') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Term Code') . '</label>
								<input type="text" name="TermsIndicator" class="db-input" required maxlength="2" autofocus value="' . $_POST['TermsIndicator'] . '" />
								<p class="db-field-help">' . __('Enter a unique 2-character code') . '</p>
							</div>';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Terms Description') . '</label>
				<input type="text" name="Terms" class="db-input" required maxlength="40" value="' . $_POST['Terms'] . '" />
			</div>';

		echo '<div class="db-field" style="grid-column: span 2;">
				<div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--surface-alt); border-radius: var(--radius-md); border: 1px solid var(--border-color); margin-bottom: 1rem;">
					<input type="checkbox" name="DaysOrFoll" id="DaysOrFoll" style="width: 20px; height: 20px; cursor: pointer;" ' . ((isset($DayInFollowingMonth) AND !$DayInFollowingMonth) ? 'checked' : '') . ' />
					<label for="DaysOrFoll" class="db-label" style="margin-bottom: 0; cursor: pointer;">' . __('Due After A Given No. Of Days (Uncheck for Day In Following Month)') . '</label>
				</div>
			</div>';

		echo '<div class="db-field">
				<label class="db-label">' . __('Days (Or Day In Following Month)') . '</label>
				<input type="number" name="DayNumber" class="db-input" required value="' . (($DaysBeforeDue != 0) ? $DaysBeforeDue : (isset($DayInFollowingMonth) ? $DayInFollowingMonth : '')) . '" />
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Payment Terms Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page
} //end if record deleted no point displaying form to add record

include(__DIR__ . '/includes/footer.php');
