<?php

/* Adds customer contacts */

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Contacts');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'AddCustomerContacts';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['Id'])) {
	$Id = (int)$_GET['Id'];
} elseif (isset($_POST['Id'])) {
	$Id = (int)$_POST['Id'];
}
if (isset($_POST['DebtorNo'])) {
	$DebtorNo = $_POST['DebtorNo'];
} elseif (isset($_GET['DebtorNo'])) {
	$DebtorNo = $_GET['DebtorNo'];
}

$SQLname = "SELECT name FROM debtorsmaster WHERE debtorno='" . $DebtorNo . "'";
$Result = DB_query($SQLname);
$Row = DB_fetch_array($Result);
$CustomerName = $Row['name'];

echo '<div class="db-page">
		<div class="premium-header">
			<div>
				<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
					<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<a href="SelectCustomer.php" class="breadcrumb-item">' . __('customer search') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<a href="Customers.php?DebtorNo=' . $DebtorNo . '" class="breadcrumb-item">' . __('maintenance') . '</a>
					<i class="fas fa-chevron-right breadcrumb-separator"></i>
					<span style="color: #064e3b; opacity: 0.9;">' . __('authorized contacts') . '</span>
				</div>
				<div>
					<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . (isset($Id) ? __('Edit Contact') : __('Add Contact')) . '</h1>
					<p style="font-size: 1.1rem; margin-top: 12px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Managing contacts for') . ' <span style="font-weight: 800;">' . htmlspecialchars($CustomerName, ENT_QUOTES, 'UTF-8') . '</span></p>
				</div>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Customers.php?DebtorNo=' . $DebtorNo . '" class="architect-btn secondary">
					<i class="fas fa-arrow-left"></i> ' . __('Back to Customer') . '
				</a>
			</div>
		</div>';

if ( isset($_POST['submit']) ) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (isset($_POST['Con_ID']) AND !is_long((int)$_POST['Con_ID'])) {
		$InputError = 1;
		prnMsg( __('The Contact ID must be an integer.'), 'error');
	} elseif (mb_strlen($_POST['ContactName']) >40) {
		$InputError = 1;
		prnMsg( __('The contact name must be forty characters or less long'), 'error');
	} elseif (trim($_POST['ContactName']) == '') {
		$InputError = 1;
		prnMsg( __('The contact name may not be empty'), 'error');
	} elseif (!IsEmailAddress($_POST['ContactEmail']) AND mb_strlen($_POST['ContactEmail']) > 0) {
		$InputError = 1;
		prnMsg( __('The contact email address is not a valid email address'), 'error');
	}

	if (isset($Id) AND ($Id AND $InputError != 1)) {
		$SQL = "UPDATE custcontacts SET contactname='" . $_POST['ContactName'] . "',
										role='" . $_POST['ContactRole'] . "',
										phoneno='" . $_POST['ContactPhone'] . "',
										notes='" . $_POST['ContactNotes'] . "',
										email='" . $_POST['ContactEmail'] . "',
										statement='" . $_POST['StatementAddress'] . "'
					WHERE debtorno ='".$DebtorNo."'
					AND contid='".$Id."'";
		$Msg = __('Contact details updated successfully.');
	} elseif ($InputError != 1) {

		$SQL = "INSERT INTO custcontacts (debtorno,
										contactname,
										role,
										phoneno,
										notes,
										email,
										statement)
				VALUES ('" . $DebtorNo. "',
						'" . $_POST['ContactName'] . "',
						'" . $_POST['ContactRole'] . "',
						'" . $_POST['ContactPhone'] . "',
						'" . $_POST['ContactNotes'] . "',
						'" . $_POST['ContactEmail'] . "',
						'" . $_POST['StatementAddress'] . "')";
		$Msg = __('New contact has been created.');
	}

	if ($InputError != 1) {
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		unset($Id);
		unset($_POST['ContactName']);
		unset($_POST['ContactRole']);
		unset($_POST['ContactPhone']);
		unset($_POST['ContactNotes']);
		unset($_POST['ContactEmail']);
		unset($_POST['Con_ID']);
	}
} elseif (isset($_GET['delete']) AND $_GET['delete']) {

	$SQL = "DELETE FROM custcontacts
			WHERE contid='" . $Id . "'
			AND debtorno='" . $DebtorNo . "'";
	$Result = DB_query($SQL);

	prnMsg( __('The contact record has been deleted'), 'success');
	unset($Id);
	unset($_GET['delete']);
}

echo '<div class="custom-bottom-layout" style="display: flex; gap: 40px; margin-top: 40px; padding: 0 40px 40px 40px;">';

// Left Column: Contact Registry Table
echo '<div style="flex: 1.5;">';

$SQL = "SELECT contid,
				debtorno,
				contactname,
				role,
				phoneno,
				statement,
				notes,
				email
		FROM custcontacts
		WHERE debtorno='".$DebtorNo."'
		ORDER BY contid";
$Result = DB_query($SQL);

echo '<div class="db-card" style="border-radius: 24px; border: 1px solid #e5e7eb; background: #fff; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
		<div class="db-card-header" style="padding: 24px 32px; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
			<h3 class="db-card-title" style="margin: 0; font-size: 1.1rem; color: #064e3b; font-weight: 800;">
				<i class="fas fa-users" style="margin-right: 12px; opacity: 0.6;"></i>' . __('Authorized Contacts') . '
			</h3>
			<span class="badge" style="background: #f0fdf4; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">' . DB_num_rows($Result) . ' ' . __('Total') . '</span>
		</div>
		<div style="overflow-x: auto;">
			<table class="registry-table" style="width: 100%; border-collapse: collapse;">
				<thead>
					<tr style="background: #f9fafb; border-bottom: 1px solid #f3f4f6;">
						<th style="padding: 16px 32px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">' . __('Contact Name') . '</th>
						<th style="padding: 16px 24px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #6b7280; text-transform: uppercase;">' . __('Role') . '</th>
						<th style="padding: 16px 24px; text-align: left; font-size: 0.75rem; font-weight: 800; color: #6b7280; text-transform: uppercase;">' . __('Communication') . '</th>
						<th style="padding: 16px 32px; text-align: right; font-size: 0.75rem; font-weight: 800; color: #6b7280; text-transform: uppercase;">' . __('Actions') . '</th>
					</tr>
				</thead>
				<tbody style="font-size: 0.9rem;">';

if (DB_num_rows($Result) == 0) {
	echo '<tr><td colspan="4" style="padding: 40px; text-align: center; color: #9ca3af; font-style: italic;">' . __('No contacts defined for this customer.') . '</td></tr>';
} else {
	while ($MyRow = DB_fetch_array($Result)) {
		$isCurrent = (isset($Id) && $Id == $MyRow['contid']);
		echo '<tr style="border-bottom: 1px solid #f9fafb; transition: all 0.2s; ' . ($isCurrent ? 'background: #f0fdf4;' : 'hover:background: #f9fafb;') . '">
				<td style="padding: 16px 32px;">
					<div style="font-weight: 700; color: #064e3b;">' . htmlspecialchars($MyRow['contactname'], ENT_QUOTES, 'UTF-8') . '</div>
					<div style="font-size: 0.7rem; color: #6b7280;">ID: ' . $MyRow['contid'] . '</div>
				</td>
				<td style="padding: 16px 24px;">
					<span class="badge" style="background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; border: 1px solid #bbf7d0;">' . htmlspecialchars($MyRow['role'], ENT_QUOTES, 'UTF-8') . '</span>
				</td>
				<td style="padding: 16px 24px;">
					<div style="font-weight: 600; color: #374151;">' . htmlspecialchars($MyRow['phoneno'], ENT_QUOTES, 'UTF-8') . '</div>
					<div style="font-size: 0.75rem; color: #6b7280;">' . htmlspecialchars($MyRow['email'], ENT_QUOTES, 'UTF-8') . '</div>
				</td>
				<td style="padding: 16px 32px; text-align: right;">
					<div style="display: flex; gap: 8px; justify-content: flex-end;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Id=' . $MyRow['contid'] . '&DebtorNo=' . $DebtorNo . '" class="db-btn db-btn-icon" style="background: #f3f4f6; color: #059669; height: 32px; width: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" title="' . __('Edit') . '">
							<i class="fas fa-edit" style="font-size: 0.8rem;"></i>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Id=' . $MyRow['contid'] . '&DebtorNo=' . $DebtorNo . '&delete=1" class="db-btn db-btn-icon" style="background: #fef2f2; color: #dc2626; height: 32px; width: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this contact?') . '\');">
							<i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
						</a>
					</div>
				</td>
			</tr>';
	}
}

echo '		</tbody>
			</table>
		</div>
	</div>
</div>';

// Right Column: Form
echo '<div style="flex: 1;">';
if (!isset($_GET['delete'])) {

	if (isset($Id)) {
		$SQL = "SELECT contid, debtorno, contactname, role, phoneno, notes, email, statement
				FROM custcontacts
				WHERE contid='".$Id."' AND debtorno='".$DebtorNo."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['Con_ID'] = $MyRow['contid'];
		$_POST['ContactName'] = $MyRow['contactname'];
		$_POST['ContactRole'] = $MyRow['role'];
		$_POST['ContactPhone']  = $MyRow['phoneno'];
		$_POST['ContactEmail'] = $MyRow['email'];
		$_POST['ContactNotes'] = $MyRow['notes'];
		$_POST['StatementAddress'] = $MyRow['statement'];
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DebtorNo='.$DebtorNo.'">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="DebtorNo" value="' . $DebtorNo . '" />';
	
	if (isset($Id)) {
		echo '<input type="hidden" name="Id" value="'. $Id .'" />
			  <input type="hidden" name="Con_ID" value="' . $_POST['Con_ID'] . '" />';
	}

	echo '<div class="db-card" style="border-radius: 24px; border: 1px solid #e5e7eb; background: #fff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); overflow: hidden;">
			<div class="db-card-header" style="padding: 24px 32px; background: #fdfdfd; border-bottom: 1px solid #f3f4f6;">
				<h4 style="margin: 0; font-size: 1rem; color: #064e3b; font-weight: 800; display: flex; align-items: center; gap: 10px;">
					<i class="fas fa-user-edit" style="opacity: 0.6;"></i>
					' . (isset($Id) ? __('Modify Contact') : __('New Contact Registration')) . '
				</h4>
			</div>
			<div style="padding: 32px;">
				<div class="db-form-group" style="margin-bottom: 24px;">
					<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Contact Full Name') . '</label>
					<input tabindex="1" type="text" name="ContactName" required class="db-input" maxlength="40" placeholder="' . __('Enter name...') . '" value="' . ($_POST['ContactName'] ?? '') . '" style="width: 100%;" />
				</div>
				
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
					<div class="db-form-group">
						<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Job Role / Position') . '</label>
						<input tabindex="2" type="text" name="ContactRole" class="db-input" maxlength="40" placeholder="' . __('e.g. Manager') . '" value="' . ($_POST['ContactRole'] ?? '') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Primary Phone') . '</label>
						<input tabindex="3" type="tel" name="ContactPhone" class="db-input" maxlength="40" placeholder="+..." value="' . ($_POST['ContactPhone'] ?? '') . '" />
					</div>
				</div>

				<div class="db-form-group" style="margin-bottom: 24px;">
					<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Email Address') . '</label>
					<input tabindex="4" type="email" name="ContactEmail" class="db-input" maxlength="55" placeholder="email@domain.com" value="' . ($_POST['ContactEmail'] ?? '') . '" style="width: 100%;" />
				</div>

				<div class="db-form-group" style="margin-bottom: 24px;">
					<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Send Statements via Email?') . '</label>
					<select tabindex="5" name="StatementAddress" class="db-input" style="width: 100%;">
						<option value="0" ' . (($_POST['StatementAddress'] ?? 0) == 0 ? 'selected' : '') . '>' . __('No - Do not send statements') . '</option>
						<option value="1" ' . (($_POST['StatementAddress'] ?? 0) == 1 ? 'selected' : '') . '>' . __('Yes - Include in statement mailing') . '</option>
					</select>
				</div>

				<div class="db-form-group" style="margin-bottom: 32px;">
					<label class="db-label" style="font-weight: 700; color: #374151; font-size: 0.85rem; margin-bottom: 8px; display: block;">' . __('Internal Notes') . '</label>
					<textarea tabindex="6" name="ContactNotes" class="db-input" rows="3" placeholder="' . __('Additional info...') . '" style="width: 100%; min-height: 100px; resize: vertical;">' . ($_POST['ContactNotes'] ?? '') . '</textarea>
				</div>

				<div style="display: flex; gap: 12px; margin-top: 10px;">
					<button type="submit" name="submit" class="architect-btn" style="flex: 2; height: 48px;">
						<i class="fas fa-check-circle" style="margin-right: 8px;"></i>
						' . (isset($Id) ? __('Save Updates') : __('Register Contact')) . '
					</button>
					' . (isset($Id) ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DebtorNo=' . $DebtorNo . '" class="architect-btn secondary" style="flex: 1; height: 48px; display: flex; align-items: center; justify-content: center;">' . __('Cancel') . '</a>' : '') . '
				</div>
			</div>
		</div>
	  </form>';
}
echo '</div>'; // End right column

echo '</div>'; // End custom-bottom-layout
echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
