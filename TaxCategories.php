<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Categories');
$ViewTopic = 'Tax';
$BookMark = 'TaxCategories';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Define stock tax categories for distinct tax treatments') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if ( isset($_GET['SelectedTaxCategory']) )
	$SelectedTaxCategory = $_GET['SelectedTaxCategory'];
elseif (isset($_POST['SelectedTaxCategory']))
	$SelectedTaxCategory = $_POST['SelectedTaxCategory'];

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (ContainsIllegalCharacters($_POST['TaxCategoryName'])) {
		$InputError = 1;
		prnMsg( __('The tax category name cannot contain the character') . " '&amp;' " . __('or the character') ." ' " . __('or a space') ,'error');
	}
	if (trim($_POST['TaxCategoryName']) == '') {
		$InputError = 1;
		prnMsg( __('The tax category name may not be empty'), 'error');
	}

	if ($_POST['SelectedTaxCategory']!='' AND $InputError !=1) {

		/*SelectedTaxCategory could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/
		// Check the name does not clash
		$SQL = "SELECT count(*) FROM taxcategories
				WHERE taxcatid <> '" . $SelectedTaxCategory ."'
				AND taxcatname ".LIKE." '" . $_POST['TaxCategoryName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The tax category cannot be renamed because another with the same name already exists.'),'error');
		} else {
			// Get the old name and check that the record still exists

			$SQL = "SELECT taxcatname FROM taxcategories
					WHERE taxcatid = '" . $SelectedTaxCategory . "'";
			$Result = DB_query($SQL);
			if ( DB_num_rows($Result) != 0 ) {
				// This is probably the safest way there is
				$MyRow = DB_fetch_row($Result);
				$OldTaxCategoryName = $MyRow[0];
				$SQL = "UPDATE taxcategories
						SET taxcatname='" . $_POST['TaxCategoryName'] . "'
						WHERE taxcatname ".LIKE." '".$OldTaxCategoryName."'";
				$ErrMsg = __('The tax category could not be updated');
				$Result = DB_query($SQL, $ErrMsg);
			} else {
				$InputError = 1;
				prnMsg( __('The tax category no longer exists'),'error');
			}
		}
		$Msg = __('Tax category name changed');
	} elseif ($InputError !=1) {
		/*SelectedTaxCategory is null cos no item selected on first time round so must be adding a record*/
		$SQL = "SELECT count(*) FROM taxcategories
				WHERE taxcatname " .LIKE. " '".$_POST['TaxCategoryName'] ."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The tax category cannot be created because another with the same name already exists'),'error');
		} else {
			DB_Txn_Begin();
			$SQL = "INSERT INTO taxcategories (
						taxcatname )
					VALUES (
						'" . $_POST['TaxCategoryName'] ."'
						)";
			$ErrMsg = __('The new tax category could not be added');
			$Result = DB_query($SQL, $ErrMsg,'', true);

			$LastTaxCatID = DB_Last_Insert_ID('taxcategories','taxcatid');

			$SQL = "INSERT INTO taxauthrates (taxauthority,
					dispatchtaxprovince,
					taxcatid)
				SELECT taxauthorities.taxid,
 					taxprovinces.taxprovinceid,
					'" . $LastTaxCatID . "'
				FROM taxauthorities CROSS JOIN taxprovinces";
			$Result = DB_query($SQL, $ErrMsg,'', true);

			DB_Txn_Commit();
		}
		$Msg = __('New tax category added');
	}

	if ($InputError!=1) {
		prnMsg($Msg,'success');
	}
	unset ($SelectedTaxCategory);
	unset ($_POST['SelectedTaxCategory']);
	unset ($_POST['TaxCategoryName']);

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button
// PREVENT DELETES IF DEPENDENT RECORDS IN 'stockmaster'
	// Get the original name of the tax category the ID is just a secure way to find the tax category
	$SQL = "SELECT taxcatname FROM taxcategories
		WHERE taxcatid = '" . $SelectedTaxCategory . "'";
	$Result = DB_query($SQL);
	if ( DB_num_rows($Result) == 0 ) {
		// This is probably the safest way there is
		prnMsg( __('Cannot delete this tax category because it no longer exists'),'warn');
	} else {
		$MyRow = DB_fetch_array($Result);
		$TaxCatName = $MyRow['taxcatname'];
		$SQL= "SELECT COUNT(*) FROM stockmaster WHERE taxcatid = '" . $SelectedTaxCategory . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg( __('Cannot delete this tax category because inventory items have been created using this tax category'),'warn');
			echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('inventory items that refer to this tax category') . '</font>';
		} else {
			$SQL = "DELETE FROM taxauthrates WHERE taxcatid  = '" . $SelectedTaxCategory . "'";
			$Result = DB_query($SQL);
			$SQL = "DELETE FROM taxcategories WHERE taxcatid = '" . $SelectedTaxCategory . "'";
			$Result = DB_query($SQL);
			prnMsg( $TaxCatName . ' ' . __('tax category and any tax rates set for it have been deleted'),'success');
		}
	} //end if
	unset ($SelectedTaxCategory);
	unset ($_GET['SelectedTaxCategory']);
	unset($_GET['delete']);
	unset ($_POST['SelectedTaxCategory']);
	unset ($_POST['TaxCategoryName']);
}

 if (!isset($SelectedTaxCategory)) {

/* An tax category could be posted when one has been edited and is being updated
  or GOT when selected for modification
  SelectedTaxCategory will exist because it was sent with the page in a GET .
  If its the first time the page has been displayed with no parameters
  then none of the above are true and the list of account groups will be displayed with
  links to delete or edit each. These will call the same page again and allow update/input
  or deletion of the records*/

	$SQL = "SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatid";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
					' . __('Defined Tax Categories') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Tax Category Name') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_row($Result)) {
		echo '<tr>
				<td class="font-bold">' . __($MyRow[1]) . '</td>
				<td class="text-center">';
		if ($MyRow[1] != 'Freight') {
			echo '<div class="db-action-group" style="justify-content:center;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxCategory=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
					</a>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxCategory=' . $MyRow[0] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this tax category?') . '\');">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
					</a>
				</div>';
		} else {
			echo '<span class="text-muted text-xs italic">' . __('System Reserved') . '</span>';
		}
		echo '	</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';
} //end of ifs and buts!


	if (isset($SelectedTaxCategory)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Review Tax Categories') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedTaxCategory)) {
			$SQL = "SELECT taxcatid, taxcatname FROM taxcategories WHERE taxcatid='" . $SelectedTaxCategory . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 0) {
				prnMsg(__('Could not retrieve the requested tax category, please try again.'), 'warn');
				unset($SelectedTaxCategory);
			} else {
				$MyRow = DB_fetch_array($Result);
				$_POST['TaxCategoryName'] = $MyRow['taxcatname'];
				echo '<input type="hidden" name="SelectedTaxCategory" value="' . $MyRow['taxcatid'] . '" />';
				echo '<div class="card-v2">
						<div class="card-header-v2">
							<h3>
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
								' . __('Edit Tax Category') . ': ' . $SelectedTaxCategory . '
							</h3>
						</div>';
			}
		} else {
			$_POST['TaxCategoryName'] = '';
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create Tax Category') . '
						</h3>
					</div>';
		}

		echo '<div class="db-card-body">
				<div class="db-field">
					<label class="db-label">' . __('Tax Category Name') . '</label>
					<input type="text" name="TaxCategoryName" class="db-input" required maxlength="30" value="' . $_POST['TaxCategoryName'] . '" placeholder="' . __('e.g. Taxable, Exempt, Luxury') . '" />
				</div>
			</div>';

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Tax Category') . '
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
			<a href="' . $RootPath . '/TaxProvinces.php" class="db-btn db-btn-ghost">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
				' . __('Tax Province Maintenance') . '
			</a>
		</div>';

	echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
