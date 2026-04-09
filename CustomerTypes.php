<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Types') . ' / ' . __('Maintenance');
$ViewTopic = 'Setup';
$BookMark = 'CustomerTypes';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Categorize customers for reporting and pricing') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectCustomer.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Search') . '
				</a>
			</div>
		</header>';

if (isset($_POST['SelectedType'])){
	$SelectedType = mb_strtoupper($_POST['SelectedType']);
} elseif (isset($_GET['SelectedType'])){
	$SelectedType = mb_strtoupper($_GET['SelectedType']);
}

$Errors = array();



if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i=1;
	if (mb_strlen($_POST['TypeName']) >100) {
		$InputError = 1;
		prnMsg(__('The customer type name description must be 100 characters or less long'),'error');
		$Errors[$i] = 'CustomerType';
		$i++;
	}

	if (mb_strlen($_POST['TypeName'])==0) {
		$InputError = 1;
		echo '<br />';
		prnMsg(__('The customer type name description must contain at least one character'),'error');
		$Errors[$i] = 'CustomerType';
		$i++;
	}

	$CheckSQL = "SELECT count(*)
		     FROM debtortype
		     WHERE typename = '" . $_POST['TypeName'] . "'";
	$Checkresult=DB_query($CheckSQL);
	$CheckRow=DB_fetch_row($Checkresult);
	if ($CheckRow[0]>0 and !isset($SelectedType)) {
		$InputError = 1;
		echo '<br />';
		prnMsg(__('You already have a customer type called').' '.$_POST['TypeName'],'error');
		$Errors[$i] = 'CustomerName';
		$i++;
	}

	if (isset($SelectedType) AND $InputError != 1) {

		$SQL = "UPDATE debtortype
			SET typename = '" . $_POST['TypeName'] . "'
			WHERE typeid = '" .$SelectedType."'";

		$Msg = __('The customer type') . ' ' . $SelectedType . ' ' .  __('has been updated');
	} elseif ( $InputError != 1 ) {

		// First check the type is not being duplicated

		$CheckSQL = "SELECT count(*)
			     FROM debtortype
			     WHERE typename = '" . $_POST['TypeName'] . "'";

		$Checkresult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($Checkresult);

		if ( $CheckRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The customer type') . ' ' . $_POST['typeid'] . __(' already exist.'),'error');
		} else {

			// Add new record on submit

			$SQL = "INSERT INTO debtortype
						(typename)
					VALUES ('" . $_POST['TypeName'] . "')";


			$Msg = __('Customer type') . ' ' . $_POST["typename"] .  ' ' . __('has been created');
			$CheckSQL = "SELECT count(typeid)
			     FROM debtortype";
			$Result = DB_query($CheckSQL);
			$Row = DB_fetch_row($Result);

		}
	}

	if ( $InputError != 1) {
	//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);


	// Fetch the default price list.
		$DefaultCustomerType = $_SESSION['DefaultCustomerType'];

	// Does it exist
		$CheckSQL = "SELECT count(*)
			     FROM debtortype
			     WHERE typeid = '" . $DefaultCustomerType . "'";
		$Checkresult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($Checkresult);

	// If it doesnt then update config with newly created one.
		if ($CheckRow[0] == 0) {
			$SQL = "UPDATE config
					SET confvalue='" . $_POST['typeid'] . "'
					WHERE confname='DefaultCustomerType'";
			$Result = DB_query($SQL);
			$_SESSION['DefaultCustomerType'] = $_POST['typeid'];
		}
		echo '<br />';
		prnMsg($Msg,'success');

		unset($SelectedType);
		unset($_POST['typeid']);
		unset($_POST['TypeName']);
	}

} elseif ( isset($_GET['delete']) ) {

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
	// Prevent delete if saletype exist in customer transactions

	$SQL= "SELECT COUNT(*)
	       FROM debtortrans
	       WHERE debtortrans.type='".$SelectedType."'";

	$ErrMsg = __('The number of transactions using this customer type could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this type because customer transactions have been created using this type') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('transactions using this type'),'error');

	} else {

		$SQL = "SELECT COUNT(*) FROM debtorsmaster WHERE typeid='".$SelectedType."'";

		$ErrMsg = __('The number of transactions using this Type record could not be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg(__('Cannot delete this type because customers are currently set up to use this type') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('customers with this type code'));
		} else {
			$Result = DB_query("SELECT typename FROM debtortype WHERE typeid='".$SelectedType."'");
			if (DB_Num_Rows($Result)>0){
				$TypeRow = DB_fetch_array($Result);
				$TypeName = $TypeRow['typename'];

				$SQL="DELETE FROM debtortype WHERE typeid='".$SelectedType."'";
				$ErrMsg = __('The Type record could not be deleted because');
				$Result = DB_query($SQL, $ErrMsg);
				echo '<br />';
				prnMsg(__('Customer type') . ' ' . $TypeName  . ' ' . __('has been deleted') ,'success');
			}
			unset ($SelectedType);
			unset($_GET['delete']);

		}
	} //end if sales type used in debtor transactions or in customers set up
}

	$SQL = "SELECT typeid, typename FROM debtortype";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
					' . __('Defined Customer Types') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('ID') . '</th>
								<th>' . __('Type Name') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_row($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow[0] . '</td>
				<td>' . $MyRow[1] . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedType=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedType=' . $MyRow[0] . '&amp;delete=yes" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this Customer Type?') . '\');">
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
	if (isset($SelectedType)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('Show All Types Defined') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedType) AND $SelectedType != '') {
			$Res = DB_query("SELECT typeid, typename FROM debtortype WHERE typeid='" . $SelectedType . "'");
			$MyRow = DB_fetch_array($Res);
			$_POST['typeid'] = $MyRow['typeid'];
			$_POST['TypeName'] = $MyRow['typename'];

			echo '<input type="hidden" name="SelectedType" value="' . $SelectedType . '" />
				<input type="hidden" name="typeid" value="' . $_POST['typeid'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Customer Type') . ': ' . $_POST['typeid'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Type ID') . '</label>
								<input type="text" class="db-input" value="' . $_POST['typeid'] . '" disabled />
							</div>';

		} else {
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create New Customer Type') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">';
		}

		if (!isset($_POST['TypeName'])) {
			$_POST['TypeName'] = '';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Type Name') . '</label>
				<input type="text" name="TypeName" class="db-input" required maxlength="100" value="' . $_POST['TypeName'] . '" autofocus />
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Customer Type') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
