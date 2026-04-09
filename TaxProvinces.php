<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Dispatch Tax Provinces');
$ViewTopic = 'Tax';
$BookMark = 'TaxProvinces';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Configure dispatch locations for distinct tax jurisdictions') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if ( isset($_GET['SelectedTaxProvince']) )
	$SelectedTaxProvince = $_GET['SelectedTaxProvince'];
elseif (isset($_POST['SelectedTaxProvince']))
	$SelectedTaxProvince = $_POST['SelectedTaxProvince'];

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (ContainsIllegalCharacters($_POST['TaxProvinceName'])) {
		$InputError = 1;
		prnMsg( __('The tax province name cannot contain any of the illegal characters') . ' ' . '" \' - &amp; or a space','error');
	}
	if (trim($_POST['TaxProvinceName']) == '') {
		$InputError = 1;
		prnMsg( __('The tax province name may not be empty'), 'error');
	}

	if ($_POST['SelectedTaxProvince']!='' AND $InputError !=1) {

		/*SelectedTaxProvince could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		// Check the name does not clash
		$SQL = "SELECT count(*) FROM taxprovinces
				WHERE taxprovinceid <> '" . $SelectedTaxProvince ."'
				AND taxprovincename " . LIKE . " '" . $_POST['TaxProvinceName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The tax province cannot be renamed because another with the same name already exists.'),'error');
		} else {
			// Get the old name and check that the record still exists
			$SQL = "SELECT taxprovincename FROM taxprovinces
						WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
			$Result = DB_query($SQL);
			if ( DB_num_rows($Result) != 0 ) {
				// This is probably the safest way there is
				$MyRow = DB_fetch_row($Result);
				$OldTaxProvinceName = $MyRow[0];
				$SQL = "UPDATE taxprovinces
					SET taxprovincename='" . $_POST['TaxProvinceName'] . "'
					WHERE taxprovincename ".LIKE." '".$OldTaxProvinceName."'";
				$ErrMsg = __('Could not update tax province');
				$Result = DB_query($SQL, $ErrMsg);
				if (!$Result) {
					prnMsg(__('Tax province name changed'),'success');
				}
			} else {
				$InputError = 1;
				prnMsg( __('The tax province no longer exists'),'error');
			}
		}
	} elseif ($InputError !=1) {
		/*SelectedTaxProvince is null cos no item selected on first time round so must be adding a record*/
		$SQL = "SELECT count(*) FROM taxprovinces
				WHERE taxprovincename " .LIKE. " '".$_POST['TaxProvinceName'] ."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);

		if ( $MyRow[0] > 0 ) {

			$InputError = 1;
			prnMsg( __('The tax province cannot be created because another with the same name already exists'),'error');

		} else {

			$SQL = "INSERT INTO taxprovinces (taxprovincename )
					VALUES ('" . $_POST['TaxProvinceName'] ."')";

			$ErrMsg = __('Could not add tax province');
			$Result = DB_query($SQL, $ErrMsg);

			$TaxProvinceID = DB_Last_Insert_ID('taxprovinces', 'taxprovinceid');
			$SQL = "INSERT INTO taxauthrates (taxauthority, dispatchtaxprovince, taxcatid)
					SELECT taxauthorities.taxid, '" . $TaxProvinceID . "', taxcategories.taxcatid
					FROM taxauthorities CROSS JOIN taxcategories";
			$ErrMsg = __('Could not add tax authority rates for the new dispatch tax province. The rates of tax will not be able to be added - manual database interaction will be required to use this dispatch tax province');
			$Result = DB_query($SQL, $ErrMsg);
		}

		if (!$Result) {
			prnMsg(__('Errors were encountered adding this tax province'),'error');
		} else {
			prnMsg(__('New tax province added'),'success');
		}
	}
	unset ($SelectedTaxProvince);
	unset ($_POST['SelectedTaxProvince']);
	unset ($_POST['TaxProvinceName']);

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button
// PREVENT DELETES IF DEPENDENT RECORDS IN 'stockmaster'
	// Get the original name of the tax province the ID is just a secure way to find the tax province
	$SQL = "SELECT taxprovincename FROM taxprovinces
		WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
	$Result = DB_query($SQL);
	if ( DB_num_rows($Result) == 0 ) {
		// This is probably the safest way there is
		prnMsg( __('Cannot delete this tax province because it no longer exists'),'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldTaxProvinceName = $MyRow[0];
		$SQL= "SELECT COUNT(*) FROM locations WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg( __('Cannot delete this tax province because at least one stock location is defined to be inside this province'),'warn');
			echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('stock locations that refer to this tax province') . '</font>';
		} else {
			$SQL = "DELETE FROM taxauthrates WHERE dispatchtaxprovince = '" . $SelectedTaxProvince . "'";
			$Result = DB_query($SQL);
			$SQL = "DELETE FROM taxprovinces WHERE taxprovinceid = '" .$SelectedTaxProvince . "'";
			$Result = DB_query($SQL);
			prnMsg( $OldTaxProvinceName . ' ' . __('tax province and any tax rates set for it have been deleted'),'success');
		}
	} //end if
	unset ($SelectedTaxProvince);
	unset ($_GET['SelectedTaxProvince']);
	unset($_GET['delete']);
	unset ($_POST['SelectedTaxProvince']);
	unset ($_POST['TaxProvinceName']);
}

if (!isset($SelectedTaxProvince)) {

/* An tax province could be posted when one has been edited and is being updated
or GOT when selected for modification
SelectedTaxProvince will exist because it was sent with the page in a GET .
If its the first time the page has been displayed with no parameters
then none of the above are true and the list of account groups will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$SQL = "SELECT taxprovinceid, taxprovincename FROM taxprovinces ORDER BY taxprovinceid";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
					' . __('Defined Dispatch Tax Provinces') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Tax Province Name') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_row($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow[1] . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxProvince=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxProvince=' . $MyRow[0] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this tax province?') . '\');">
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


	if (isset($SelectedTaxProvince)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Review Tax Provinces') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedTaxProvince)) {
			// Editing an existing province
			$SQL = "SELECT taxprovinceid, taxprovincename FROM taxprovinces WHERE taxprovinceid='" . $SelectedTaxProvince . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 0) {
				prnMsg(__('Could not retrieve the requested tax province, please try again.'), 'warn');
				unset($SelectedTaxProvince);
			} else {
				$MyRow = DB_fetch_array($Result);
				$_POST['TaxProvinceName'] = $MyRow['taxprovincename'];
				echo '<input type="hidden" name="SelectedTaxProvince" value="' . $MyRow['taxprovinceid'] . '" />';
				echo '<div class="card-v2">
						<div class="card-header-v2">
							<h3>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
								' . __('Edit Tax Province') . ': ' . $SelectedTaxProvince . '
							</h3>
						</div>';
			}
		} else {
			$_POST['TaxProvinceName'] = '';
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create Tax Province') . '
						</h3>
					</div>';
		}

		echo '<div class="db-card-body">
				<div class="db-field">
					<label class="db-label">' . __('Tax Province Name') . '</label>
					<input type="text" name="TaxProvinceName" class="db-input" required maxlength="30" value="' . $_POST['TaxProvinceName'] . '" placeholder="' . __('e.g. California, Ontario, Lagos') . '" />
				</div>
			</div>';

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Tax Province') . '
				</button>
			</div>
		</div></form>';
	}

	echo '<div class="db-action-grid" style="margin-top:2rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
			<a href="' . $RootPath . '/TaxAuthorities.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
				' . __('Tax Authority Maintenance') . '
			</a>
			<a href="' . $RootPath . '/TaxGroups.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
				' . __('Tax Group Maintenance') . '
			</a>
			<a href="' . $RootPath . '/TaxCategories.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
				' . __('Tax Category Maintenance') . '
			</a>
		</div>';

	echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
