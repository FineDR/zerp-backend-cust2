<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Contacts');
$ViewTopic = 'AccountsPayable';
$BookMark = 'SupplierContact';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SupplierID'])){
	$SupplierID = $_GET['SupplierID'];
} elseif (isset($_POST['SupplierID'])){
	$SupplierID = $_POST['SupplierID'];
}

echo '<div class="db-page">';

echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> ' . $Title . '</h2>
			<p class="db-page-subtitle">' . __('Manage key personnel and communication channels for') . ' <span class="val-bold" style="color: var(--primary);">' . (isset($SupplierID) ? $SupplierID : '') . '</span></p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SelectSupplier.php?SupplierID=' . $SupplierID . '" class="db-btn db-btn-secondary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
				' . __('Back to Supplier') . '
			</a>
		</div>
	</div>';

if (!isset($SupplierID)) {
	prnMsg(__('This page must be called with the supplier code of the supplier for whom you wish to edit the contacts') . '<br />' . __('When the page is called from within the system this will always be the case') .
	'<br />' . __('Select a supplier first, then select the link to add/edit/delete contacts'),'info');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['SelectedContact'])){
	$SelectedContact = $_GET['SelectedContact'];
} elseif (isset($_POST['SelectedContact'])){
	$SelectedContact = $_POST['SelectedContact'];
}


if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (mb_strlen($_POST['Contact']) == 0) {
		$InputError = 1;
		prnMsg(__('The contact name must be at least one character long'),'error');
		echo '<br />';
	}
	if (mb_strlen($_POST['Email'])){
		if (!IsEmailAddress($_POST['Email'])) {
			$InputError = 1;
			prnMsg(__('The email address entered does not appear to be a valid email address'),'error');
			echo '<br />';
		}
	}
	if (isset($SelectedContact) AND $InputError != 1) {

		/*SelectedContact could also exist if submit had not been clicked this code would not run in this case 'cos submit is false of course see the delete code below*/

		$SQL = "UPDATE suppliercontacts SET position='" . $_POST['Position'] . "',
											tel='" . $_POST['Tel'] . "',
											fax='" . $_POST['Fax'] . "',
											email='" . $_POST['Email'] . "',
											mobile = '". $_POST['Mobile'] . "'
				WHERE contact='".$SelectedContact."'
				AND supplierid='".$SupplierID."'";

		$Msg = __('The supplier contact information has been updated');

	} elseif ($InputError != 1) {

	/*Selected contact is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new supplier  contacts form */

		$SQL = "INSERT INTO suppliercontacts (supplierid,
											contact,
											position,
											tel,
											fax,
											email,
											mobile)
				VALUES ('" . $SupplierID . "',
					'" . $_POST['Contact'] . "',
					'" . $_POST['Position'] . "',
					'" . $_POST['Tel'] . "',
					'" . $_POST['Fax'] . "',
					'" . $_POST['Email'] . "',
					'" . $_POST['Mobile'] . "')";

		$Msg = __('The new supplier contact has been added to the database');
	}
	//run the SQL from either of the above possibilites
	if ($InputError != 1) {
		$ErrMsg = __('The supplier contact could not be inserted or updated because');

		$Result = DB_query($SQL, $ErrMsg);

		prnMsg($Msg,'success');

		unset($SelectedContact);
		unset($_POST['Contact']);
		unset($_POST['Position']);
		unset($_POST['Tel']);
		unset($_POST['Fax']);
		unset($_POST['Email']);
		unset($_POST['Mobile']);
	}
} elseif (isset($_GET['delete'])) {

	$SQL = "DELETE FROM suppliercontacts
			WHERE contact='".$SelectedContact."'
			AND supplierid = '".$SupplierID."'";

	$ErrMsg = __('The supplier contact could not be deleted because');

	$Result = DB_query($SQL, $ErrMsg);

	echo '<br />' . __('Supplier contact has been deleted') . '<p />';

}


if (!isset($SelectedContact)){
	$SQL = "SELECT suppliers.suppname,
					contact,
					position,
					tel,
					suppliercontacts.fax,
					suppliercontacts.email
				FROM suppliercontacts,
					suppliers
				WHERE suppliercontacts.supplierid=suppliers.supplierid
				AND suppliercontacts.supplierid = '".$SupplierID."'";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result)>0){

		$MyRow = DB_fetch_array($Result);

		echo '<div class="db-card" style="margin-bottom: var(--space-8);">
				<div class="db-card-header">
					<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg> ' . __('Contacts Defined for') . ' ' . $MyRow['suppname'] . '</h3>
				</div>
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Name') . '</th>
								<th>' . __('Position') . '</th>
								<th>' . __('Communication Details') . '</th>
								<th class="db-table-actions">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

		do {
			echo '<tr class="striped_row">
					<td><div class="cust-name">' . $MyRow['contact'] . '</div></td>
					<td><span class="tag">' . $MyRow['position'] . '</span></td>
					<td>
						<div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.85rem;">
							' . (empty($MyRow['tel']) ? '' : '<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>' . $MyRow['tel'] . '</span>') . '
							' . (empty($MyRow['fax']) ? '' : '<span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;"><path d="M23 4.5V1c0-.3-.2-.5-.5-.5h-21c-.3 0-.5.2-.5.5v3.5c0 .3.2.5.5.5h21c.3 0 .5-.2.5-.5zM8 4H2V1h6v3zm7 0h-6V1h6v3zm7 0h-6V1h6v3zM22 20V6c0-.6-.4-1-1-1H3c-.6 0-1 .4-1 1v14c0 1.7 1.3 3 3 3h14c1.7 0 3-1.3 3-3zM9 11v1h6v-1c0-.6-.4-1-1-1h-4c-.6 0-1 .4-1 1zm8 7v1H7v-1c0-1.1.9-2 2-2h6c1.1 0 2 .9 2 2z"></path></svg>' . $MyRow['fax'] . '</span>') . '
							' . (empty($MyRow['email']) ? '' : '<a href="mailto:'.$MyRow['email'].'" style="color: var(--primary);"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 6px; vertical-align: middle;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>' . $MyRow['email'] . '</a>') . '
						</div>
					</td>
					<td class="db-table-actions">
						<div style="display: flex; gap: 8px; justify-content: flex-end;">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SupplierID=' . $SupplierID . '&SelectedContact=' . $MyRow['contact'] . '" class="db-btn db-btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;">' . __('Edit') . '</a>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SupplierID=' . $SupplierID . '&SelectedContact=' . $MyRow['contact'] . '&delete=yes" class="db-btn db-btn-danger" style="padding: 4px 10px; font-size: 0.75rem;" onclick="return confirm(\''  . __('Are you sure you wish to delete this contact?') . '\');">' .  __('Delete') . '</a>
						</div>
					</td>
				</tr>';
		} while ($MyRow = DB_fetch_array($Result));
		echo '</tbody></table></div></div>';
	} else {
		prnMsg(__('There are no contacts defined for this supplier'),'info');
	}
	//END WHILE LIST LOOP
}

//end of ifs and buts!


if (isset($SelectedContact)) {
	echo '<div class="centre">
			<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SupplierID=' . $SupplierID . '">' .
		  __('Show all the supplier contacts for') . ' ' . $SupplierID . '</a>
		 </div>';
}

if (! isset($_GET['delete'])) {

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="SupplierID" value="' . $SupplierID . '" />';

	if (isset($SelectedContact)) {
		//editing an existing contact

		$SQL = "SELECT contact,
						position,
						tel,
						fax,
						mobile,
						email
					FROM suppliercontacts
					WHERE contact='" . $SelectedContact . "'
					AND supplierid='" . $SupplierID . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['Contact']  = $MyRow['contact'];
		$_POST['Position']  = $MyRow['position'];
		$_POST['Tel']  = $MyRow['tel'];
		$_POST['Fax']  = $MyRow['fax'];
		$_POST['Email']  = $MyRow['email'];
		$_POST['Mobile']  = $MyRow['mobile'];
		echo '<input type="hidden" name="SelectedContact" value="' . $_POST['Contact'] . '" />';
		echo '<input type="hidden" name="Contact" value="' . $_POST['Contact'] . '" />';
		
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> ' . __('Edit Supplier Contact') . ': ' . $_POST['Contact'] . '</h3>
				</div>';
	} else {
		if (!isset($_POST['Contact'])) {
			$_POST['Contact']='';
		}
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg> ' . __('Create New Supplier Contact') . '</h3>
				</div>';
	}

	echo '<div class="db-card-body">
			<div class="db-form-grid">
				' . (!isset($SelectedContact) ? '
				<div class="db-form-group">
					<label for="Contact">' . __('Contact Name') . '</label>
					<input type="text" required="required" pattern="(?!^\s+$).{1,40}" name="Contact" maxlength="40" value="' . $_POST['Contact'] . '" placeholder="' . __('Enter full name') . '" />
				</div>' : '') . '
				
				<div class="db-form-group">
					<label for="Position">' . __('Position / Title') . '</label>
					<input type="text" name="Position" maxlength="30" value="' . (isset($_POST['Position']) ? $_POST['Position'] : '') . '" placeholder="' . __('e.g. Purchasing Manager') . '" />
				</div>

				<div class="db-form-group">
					<label for="Email">' . __('Email Address') . '</label>
					<input type="email" name="Email" maxlength="50" value="' . (isset($_POST['Email']) ? $_POST['Email'] : '') . '" placeholder="' . __('email@example.com') . '" />
				</div>

				<div class="db-form-group">
					<label for="Tel">' . __('Telephone No') . '</label>
					<input type="tel" pattern="[\d\s+()-]{1,30}" name="Tel" maxlength="30" value="' . (isset($_POST['Tel']) ? $_POST['Tel'] : '') . '" />
				</div>

				<div class="db-form-group">
					<label for="Fax">' . __('Facsimile No (Fax)') . '</label>
					<input type="tel" pattern="[\d\s+()-]{1,30}" name="Fax" maxlength="30" value="' . (isset($_POST['Fax']) ? $_POST['Fax'] : '') . '" />
				</div>

				<div class="db-form-group">
					<label for="Mobile">' . __('Mobile No') . '</label>
					<input type="tel" pattern="[\d\s+()-]{1,30}" name="Mobile" maxlength="30" value="' . (isset($_POST['Mobile']) ? $_POST['Mobile'] : '') . '" />
				</div>
			</div>
		</div>
		<div class="db-card-footer" style="background: var(--surface-alt); padding: var(--space-4); text-align: right;">
			<button type="submit" name="submit" class="db-btn db-btn-primary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
				' . __('Save Contact Information') . '
			</button>
		</div>
	</div>
</form>';

echo '</div>'; // End db-page

} //end if record deleted no point displaying form to add record

include(__DIR__ . '/includes/footer.php');
