<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Authorities');
$ViewTopic = 'Tax';
$BookMark = 'TaxAuthorities';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Configure tax jurisdictions and regulatory bodies') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if (isset($_POST['SelectedTaxAuthID'])) {
	$SelectedTaxAuthID =$_POST['SelectedTaxAuthID'];
} elseif (isset($_GET['SelectedTaxAuthID'])) {
	$SelectedTaxAuthID =$_GET['SelectedTaxAuthID'];
}

if (isset($_POST['submit'])) {

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	if ( trim( $_POST['Description'] ) == '' ) {
		$InputError = 1;
		prnMsg( __('The tax type description may not be empty'), 'error');
	}

	if (isset($SelectedTaxAuthID)) {

		/*SelectedTaxAuthID could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$SQL = "UPDATE taxauthorities
					SET taxglcode ='" . $_POST['TaxGLCode'] . "',
					purchtaxglaccount ='" . $_POST['PurchTaxGLCode'] . "',
					description = '" . $_POST['Description'] . "',
					bank = '" . $_POST['Bank'] . "',
					bankacctype = '". $_POST['BankAccType'] . "',
					bankacc = '". $_POST['BankAcc'] . "',
					bankswift = '". $_POST['BankSwift'] . "'
				WHERE taxid = '" . $SelectedTaxAuthID . "'";

		$ErrMsg = __('The update of this tax authority failed because');
		$Result = DB_query($SQL, $ErrMsg);

		$Msg = __('The tax authority for record has been updated');

	} elseif ($InputError !=1) {

	/*Selected tax authority is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new tax authority form */

		$SQL = "INSERT INTO taxauthorities (
						taxglcode,
						purchtaxglaccount,
						description,
						bank,
						bankacctype,
						bankacc,
						bankswift)
			VALUES (
				'" . $_POST['TaxGLCode'] . "',
				'" . $_POST['PurchTaxGLCode'] . "',
				'" . $_POST['Description'] . "',
				'" . $_POST['Bank'] . "',
				'" . $_POST['BankAccType'] . "',
				'" . $_POST['BankAcc'] . "',
				'" . $_POST['BankSwift'] . "'
				)";

		$Errmsg = __('The addition of this tax authority failed because');
		$Result = DB_query($SQL, $ErrMsg);

		$Msg = __('The new tax authority record has been added to the database');

		$NewTaxID = DB_Last_Insert_ID('taxauthorities','taxid');

		$SQL = "INSERT INTO taxauthrates (
					taxauthority,
					dispatchtaxprovince,
					taxcatid
					)
				SELECT
					'" . $NewTaxID  . "',
					taxprovinces.taxprovinceid,
					taxcategories.taxcatid
				FROM taxprovinces,
					taxcategories";

			$InsertResult = DB_query($SQL);
	}
	//run the SQL from either of the above possibilites
	if (isset($InputError) and $InputError !=1) {
		unset($_POST['TaxGLCode']);
		unset($_POST['PurchTaxGLCode']);
		unset($_POST['Description']);
		unset($SelectedTaxID);
	}

	prnMsg($Msg);

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN OTHER TABLES

	$SQL= "SELECT COUNT(*)
			FROM taxgrouptaxes
		WHERE taxauthid='" . $SelectedTaxAuthID . "'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnmsg(__('Cannot delete this tax authority because there are tax groups defined that use it'),'warn');
	} else {
		/*Cascade deletes in TaxAuthLevels */
		$Result = DB_query("DELETE FROM taxauthrates WHERE taxauthority= '" . $SelectedTaxAuthID . "'");
		$Result = DB_query("DELETE FROM taxauthorities WHERE taxid= '" . $SelectedTaxAuthID . "'");
		prnMsg(__('The selected tax authority record has been deleted'),'success');
		unset ($SelectedTaxAuthID);
	} // end of related records testing
}

if (!isset($SelectedTaxAuthID)) {

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedTaxAuthID will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters then none of the above are true and the list of tax authorities will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of the records*/

	$SQL = "SELECT taxid, description, taxglcode, purchtaxglaccount, bank, bankacc, bankacctype, bankswift FROM taxauthorities";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
					' . __('Defined Tax Authorities') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('ID') . '</th>
								<th>' . __('Authority Description') . '</th>
								<th>' . __('Input Tax Account') . '</th>
								<th>' . __('Output Tax Account') . '</th>
								<th>' . __('Bank Details') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_row($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow[0] . '</td>
				<td>' . $MyRow[1] . '</td>
				<td class="font-mono">' . $MyRow[3] . '</td>
				<td class="font-mono">' . $MyRow[2] . '</td>
				<td>
					<div class="text-sm font-bold">' . $MyRow[4] . '</div>
					<div class="text-xs text-muted">' . $MyRow[5] . ' (' . $MyRow[6] . ')</div>
					<div class="text-xs text-muted">SWIFT: ' . $MyRow[7] . '</div>
				</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxAuthID=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxAuthID=' . $MyRow[0] . '&amp;delete=yes" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this tax authority?') . '\');">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2-2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						</a>
						<a href="' . $RootPath . '/TaxAuthorityRates.php?TaxAuthority=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit Rates') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20v-6M6 20V10M18 20V4"></path></svg>
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
}

	if (isset($SelectedTaxAuthID)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Review all defined tax authority records') . '
				</a>
			</div>';
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedTaxAuthID)) {
		// Editing an existing tax authority
		$SQL = "SELECT taxglcode, purchtaxglaccount, description, bank, bankacc, bankacctype, bankswift FROM taxauthorities WHERE taxid='" . $SelectedTaxAuthID . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['TaxGLCode'] = $MyRow['taxglcode'];
		$_POST['PurchTaxGLCode'] = $MyRow['purchtaxglaccount'];
		$_POST['Description'] = $MyRow['description'];
		$_POST['Bank'] = $MyRow['bank'];
		$_POST['BankAccType'] = $MyRow['bankacctype'];
		$_POST['BankAcc'] = $MyRow['bankacc'];
		$_POST['BankSwift'] = $MyRow['bankswift'];

		echo '<input type="hidden" name="SelectedTaxAuthID" value="' . $SelectedTaxAuthID . '" />';
		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						' . __('Edit Tax Authority Details') . ': ' . $SelectedTaxAuthID . '
					</h3>
				</div>';
	} else {
		if (!isset($_POST['Description'])) $_POST['Description'] = '';
		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
						' . __('Create New Tax Authority') . '
					</h3>
				</div>';
	}

	$GLAccountsSQL = "SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 ORDER BY accountcode";
	$GLAccountsResult = DB_query($GLAccountsSQL);

	echo '<div class="db-card-body">
			<div class="db-grid db-grid-2">
				<div class="db-field" style="grid-column: span 2;">
					<label class="db-label">' . __('Tax Type Description') . '</label>
					<input type="text" name="Description" class="db-input" required maxlength="20" value="' . $_POST['Description'] . '" placeholder="' . __('Enter authority name') . '" />
				</div>

				<div class="db-field">
					<label class="db-label">' . __('Input Tax GL Account (Purchases)') . '</label>
					<select name="PurchTaxGLCode" class="db-input">';
	while ($MyRow = DB_fetch_array($GLAccountsResult)) {
		echo '<option value="' . $MyRow['accountcode'] . '" ' . ((isset($_POST['PurchTaxGLCode']) && $MyRow['accountcode'] == $_POST['PurchTaxGLCode']) ? 'selected' : '') . '>' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8') . ' (' . $MyRow['accountcode'] . ')</option>';
	}
	echo '			</select>
				</div>';

	DB_data_seek($GLAccountsResult, 0);

	echo '		<div class="db-field">
					<label class="db-label">' . __('Output Tax GL Account (Sales)') . '</label>
					<select name="TaxGLCode" class="db-input">';
	while ($MyRow = DB_fetch_array($GLAccountsResult)) {
		echo '<option value="' . $MyRow['accountcode'] . '" ' . ((isset($_POST['TaxGLCode']) && $MyRow['accountcode'] == $_POST['TaxGLCode']) ? 'selected' : '') . '>' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8') . ' (' . $MyRow['accountcode'] . ')</option>';
	}
	echo '			</select>
				</div>';

	if (!isset($_POST['Bank'])) $_POST['Bank'] = '';
	if (!isset($_POST['BankAccType'])) $_POST['BankAccType'] = '';
	if (!isset($_POST['BankAcc'])) $_POST['BankAcc'] = '';
	if (!isset($_POST['BankSwift'])) $_POST['BankSwift'] = '';

	echo '		<div class="db-field">
					<label class="db-label">' . __('Bank Name') . '</label>
					<input type="text" name="Bank" class="db-input" maxlength="40" value="' . $_POST['Bank'] . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Bank Account Type') . '</label>
					<input type="text" name="BankAccType" class="db-input" maxlength="20" value="' . $_POST['BankAccType'] . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Bank Account Number') . '</label>
					<input type="text" name="BankAcc" class="db-input" maxlength="20" value="' . $_POST['BankAcc'] . '" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Bank SWIFT Code') . '</label>
					<input type="text" name="BankSwift" class="db-input" maxlength="15" value="' . $_POST['BankSwift'] . '" />
				</div>
			</div>
		</div>'; // End db-grid & db-card-body

	echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
			<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
				' . __('Save Tax Authority Information') . '
			</button>
		</div>
	</div></form>'; // End card-v2 & form

	echo '<div class="db-action-grid" style="margin-top:2rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
			<a href="' . $RootPath . '/TaxGroups.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
				' . __('Tax Group Maintenance') . '
			</a>
			<a href="' . $RootPath . '/TaxProvinces.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
				' . __('Tax Province Maintenance') . '
			</a>
			<a href="' . $RootPath . '/TaxCategories.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
				' . __('Tax Category Maintenance') . '
			</a>
		</div>';

	echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
