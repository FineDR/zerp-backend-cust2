<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales People Maintenance');
$ViewTopic = 'SalesPeople';
$BookMark = 'SalesPeople';
if (isset($_GET['SelectedSalesPerson'])) {
	$BookMark = 'SalespeopleEdit';
}// For Edit's screen.
if (isset($_GET['delete'])) {
	$BookMark = 'SalespeopleDelete';
}// For Delete's ERROR Message Report.
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage your sales team and their commission settings') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Search') . '
				</a>
			</div>
		</header>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	$i=1;

	//first off validate inputs sensible

	if (mb_strlen($_POST['SalesmanCode']) > 3) {
		$InputError = 1;
		prnMsg(__('The salesperson code must be three characters or less long'),'error');
		$Errors[$i] = 'SalesmanCode';
		$i++;
	} elseif (mb_strlen($_POST['SalesmanCode'])==0 OR $_POST['SalesmanCode']=='') {
		$InputError = 1;
		prnMsg(__('The salesperson code cannot be empty'),'error');
		$Errors[$i] = 'SalesmanCode';
		$i++;
	} elseif (mb_strlen($_POST['SalesmanName']) > 30) {
		$InputError = 1;
		prnMsg(__('The salesperson name must be thirty characters or less long'),'error');
		$Errors[$i] = 'SalesmanName';
		$i++;
	} elseif (mb_strlen($_POST['SManTel']) > 20) {
		$InputError = 1;
		prnMsg(__('The salesperson telephone number must be twenty characters or less long'),'error');

	} elseif (mb_strlen($_POST['SManFax']) > 20) {
		$InputError = 1;
		prnMsg(__('The salesperson telephone number must be twenty characters or less long'),'error');

	}
	if (!isset($_POST['SManTel'])){
		$_POST['SManTel']='';
	}
	if (!isset($_POST['SManFax'])){
		$_POST['SManFax']='';
	}
	if (!isset($_POST['Current'])){
		$_POST['Current']=0;
	}
	if (!isset($_POST['CommissionPeriod'])){
		$_POST['CommissionPeriod']=0;
	}
	if (!isset($_POST['CommissionTypeID'])){
		$_POST['CommissionTypeID']=0;
	}
	if (!isset($_POST['GLAccount'])){
		$_POST['GLAccount']='';
	}

	if (isset($SelectedSalesPerson) AND $InputError !=1) {

		/*SelectedSalesPerson could also exist if submit had not been clicked this code would not run in this case cos submit is false of course  see the delete code below*/

		$SQL = "UPDATE salesman SET salesmanname='" . $_POST['SalesmanName'] . "',
									smantel='" . $_POST['SManTel'] . "',
									smanfax='" . $_POST['SManFax'] . "',
									current='" . $_POST['Current'] . "',
									commissionperiod='" . $_POST['CommissionPeriod'] . "',
									commissiontypeid='" . $_POST['CommissionTypeID'] . "',
									glaccount='" . $_POST['GLAccount'] . "'
								WHERE salesmancode = '" . stripslashes($SelectedSalesPerson) . "'";

		$Msg = __('Salesperson record for') . ' ' . $_POST['SalesmanName'] . ' ' . __('has been updated');
	} elseif ($InputError !=1) {

	/*Selected group is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new Sales-person form */

		$SQL = "INSERT INTO salesman (salesmancode,
						salesmanname,
						smantel,
						smanfax,
						current,
						commissionperiod,
						commissiontypeid,
						glaccount)
				VALUES ('" . $_POST['SalesmanCode'] . "',
						'" . $_POST['SalesmanName'] . "',
						'" . $_POST['SManTel'] . "',
						'" . $_POST['SManFax'] . "',
						'" . $_POST['Current'] . "',
						'" . $_POST['CommissionPeriod'] . "',
						'" . $_POST['CommissionTypeID'] . "',
						'" . $_POST['GLAccount'] . "'
					)";

		$Msg = __('A new salesperson record has been added for') . ' ' . $_POST['SalesmanName'];
	}
	if ($InputError !=1) {
		//run the SQL from either of the above possibilites
		$ErrMsg = __('The insert or update of the salesperson failed because');
		$Result = DB_query($SQL, $ErrMsg);

		prnMsg($Msg , 'success');

		unset($SelectedSalesPerson);
		unset($_POST['SalesmanCode']);
		unset($_POST['SalesmanName']);
		unset($_POST['SManFax']);
		unset($_POST['SManTel']);
		unset($_POST['Current']);
		unset($_POST['CommissionPeriod']);
		unset($_POST['CommissionTypeID']);
		unset($_POST['GLAccount']);
	}

} elseif (isset($_GET['delete'])) {
$BookMark = 'SalespeopleDelete';
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorsMaster'

	$SQL= "SELECT COUNT(*) FROM custbranch WHERE  custbranch.salesman='".$SelectedSalesPerson."'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this salesperson because branches are set up referring to them') . ' - ' . __('first alter the branches concerned') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('branches that refer to this salesperson'),'error');

	} else {
		$SQL= "SELECT COUNT(*) FROM salesanalysis WHERE salesanalysis.salesperson='".$SelectedSalesPerson."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg(__('Cannot delete this salesperson because sales analysis records refer to them') , '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('sales analysis records that refer to this salesperson'),'error');
		} else {
			$SQL= "SELECT COUNT(*) FROM www_users WHERE salesman='".$SelectedSalesPerson."'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0]>0) {
				prnMsg(__('Cannot delete this salesperson because') , '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('user records that refer to this salesperson') . '.' .__('First delete any users that refer to this sales person'),'error');
			} else {

				$SQL="DELETE FROM salesman WHERE salesmancode='". $SelectedSalesPerson."'";
				$ErrMsg = __('The salesperson could not be deleted because');
				$Result = DB_query($SQL, $ErrMsg);

				prnMsg(__('Salesperson') . ' ' . $SelectedSalesPerson . ' ' . __('has been deleted from the database'),'success');
				unset ($SelectedSalesPerson);
				unset($Delete);
			}
		}
	} //end if Sales-person used in GL accounts
}

	$SQL = "SELECT salesman.salesmancode,
					salesman.salesmanname,
					salesman.smantel,
					salesman.smanfax,
					salesman.current,
					salesman.commissionperiod,
					salesman.commissiontypeid,
					salesman.glaccount,
					salescommissiontypes.commissiontypename,
					chartmaster.accountname
				FROM salesman
				LEFT JOIN salescommissiontypes ON salesman.commissiontypeid=salescommissiontypes.commissiontypeid
				LEFT JOIN chartmaster ON salesman.glaccount=chartmaster.accountcode";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
					' . __('Existing Sales People') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Salesperson Name') . '</th>
								<th>' . __('Contact Details') . '</th>
								<th>' . __('Commission & GL') . '</th>
								<th class="text-center">' . __('Status') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow['salesmancode'] . '</td>
				<td>' . $MyRow['salesmanname'] . '</td>
				<td>
					<div class="text-sm">' . $MyRow['smantel'] . '</div>
					<div class="text-xs text-muted">' . $MyRow['smanfax'] . '</div>
				</td>
				<td>
					<div class="text-sm font-bold">' . $CommissionPeriods[$MyRow['commissionperiod']] . '</div>
					<div class="text-xs text-muted">' . $MyRow['glaccount'] . ' - ' . $MyRow['accountname'] . '</div>
				</td>
				<td class="text-center">
					' . ($MyRow['current'] == 1 ? '<span class="db-badge db-badge-success">' . __('Active') . '</span>' : '<span class="db-badge db-badge-secondary">' . __('Inactive') . '</span>') . '
				</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedSalesPerson=' . urlencode($MyRow['salesmancode']) . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . $RootPath . '/SalesCommissionRates.php?SelectedSalesPerson=' . urlencode($MyRow['salesmancode']) . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Commission Rates') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1v22M17 5H9.5a4.5 4.5 0 1 0 0 9h5a4.5 4.5 0 1 1 0 9H6"></path></svg>
						</a>
						<a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedSalesPerson=' . urlencode($MyRow['salesmancode']) . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this salesperson?') . '\');">
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

	if (isset($SelectedSalesPerson)) {
		//editing an existing Sales-person

		$SQL = "SELECT salesmancode,
					salesmanname,
					smantel,
					smanfax,
					current,
					commissionperiod,
					commissiontypeid,
					glaccount
				FROM salesman
				WHERE salesmancode='".$SelectedSalesPerson."'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['SalesmanCode'] = $MyRow['salesmancode'];
		$_POST['SalesmanName'] = $MyRow['salesmanname'];
		$_POST['SManTel'] = $MyRow['smantel'];
		$_POST['SManFax'] = $MyRow['smanfax'];
		$_POST['Current'] = $MyRow['current'];
		$_POST['CommissionPeriod'] = $MyRow['commissionperiod'];
		$_POST['CommissionTypeID'] = $MyRow['commissiontypeid'];
		$_POST['GLAccount'] = $MyRow['glaccount'];

		echo '<input type="hidden" name="SelectedSalesPerson" value="' . $SelectedSalesPerson . '" />';
		echo '<input type="hidden" name="SalesmanCode" value="' . $_POST['SalesmanCode'] . '" />';

		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						' . __('Edit Salesperson Details') . ': ' . $_POST['SalesmanCode'] . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Salesperson Code') . '</label>
							<input type="text" class="db-input" value="' . $_POST['SalesmanCode'] . '" disabled />
						</div>';

	} else {
		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
						' . __('Register New Salesperson') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Salesperson Code') . '</label>
							<input type="text" name="SalesmanCode" class="db-input" required maxlength="3" autofocus />
							<p class="db-field-help">' . __('Enter a unique 3-character code') . '</p>
						</div>';
	}

	echo '<div class="db-field">
			<label class="db-label">' . __('Full Name') . '</label>
			<input type="text" name="SalesmanName" class="db-input" required maxlength="30" value="' . $_POST['SalesmanName'] . '" />
		</div>
	</div>'; // End top grid

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Phone Number') . '</label>
				<input type="tel" name="SManTel" class="db-input" maxlength="20" value="' . $_POST['SManTel'] . '" />
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Fax Number') . '</label>
				<input type="tel" name="SManFax" class="db-input" maxlength="20" value="' . $_POST['SManFax'] . '" />
			</div>
		</div>';

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-3">
			<div class="db-field">
				<label class="db-label">' . __('Commission Period') . '</label>
				<select name="CommissionPeriod" required class="db-input">';
	foreach ($CommissionPeriods as $idx => $name) {
		$sel = ($_POST['CommissionPeriod'] == $idx) ? 'selected' : '';
		echo '<option ' . $sel . ' value="' . $idx . '">' . $name . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Calculation Method') . '</label>
				<select name="CommissionTypeID" class="db-input">
					<option ' . ($_POST['CommissionTypeID'] == 0 ? 'selected' : '') . ' value="0">' . __('No Commission') . '</option>';
	$Res = DB_query("SELECT commissiontypeid, commissiontypename FROM salescommissiontypes ORDER BY commissiontypename");
	while ($myr = DB_fetch_array($Res)) {
		$sel = ($_POST['CommissionTypeID'] == $myr['commissiontypeid']) ? 'selected' : '';
		echo '<option ' . $sel . ' value="' . $myr['commissiontypeid'] . '">' . $myr['commissiontypename'] . ' (' . $myr['commissiontypeid'] . ')</option>';
	}
	echo '		</select>
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Commission Account') . '</label>
				<select name="GLAccount" class="db-input">';
	$Res = DB_query("SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY chartmaster.accountcode");
	while ($myr = DB_fetch_array($Res)) {
		$sel = ($_POST['GLAccount'] == $myr['accountcode']) ? 'selected' : '';
		echo '<option ' . $sel . ' value="' . $myr['accountcode'] . '">' . htmlspecialchars($myr['accountname'], ENT_QUOTES, 'UTF-8') . ' (' . $myr['accountcode'] . ')</option>';
	}
	echo '		</select>
			</div>
		</div>';

	echo '<div class="db-grid db-grid-2" style="margin-top: 1.5rem;">
			<div class="db-field">
				<label class="db-label">' . __('Is currently active?') . '</label>
				<select name="Current" class="db-input" required>
					<option ' . ($_POST['Current'] == 1 ? 'selected' : '') . ' value="1">' . __('Yes, Currently Active') . '</option>
					<option ' . ($_POST['Current'] == 0 ? 'selected' : '') . ' value="0">' . __('No, Inactive') . '</option>
				</select>
			</div>
		</div>
	</div>'; // End db-card-body

	echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
			<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
				' . __('Save Salesperson Information') . '
			</button>
		</div>
	</div>'; // End card-v2

	echo '</form>';
}

echo '</div>'; // End db-page

} //end if record deleted no point displaying form to add record

include(__DIR__ . '/includes/footer.php');
