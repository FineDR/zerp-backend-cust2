<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Shipping Company Maintenance');
$ViewTopic = 'Shipments';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage carrier companies and logistics partners') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if (isset($_GET['SelectedShipper'])){
	$SelectedShipper = $_GET['SelectedShipper'];
} elseif (isset($_POST['SelectedShipper'])){
	$SelectedShipper = $_POST['SelectedShipper'];
}

$Errors = array();

if ( isset($_POST['submit']) ) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i=1;

	if (mb_strlen($_POST['ShipperName']) >40) {
		$InputError = 1;
		prnMsg( __('The shipper\'s name must be forty characters or less long'), 'error');
		$Errors[$i] = 'ShipperName';
		$i++;
	} elseif ( trim($_POST['ShipperName']) == '' ) {
		$InputError = 1;
		prnMsg( __('The shipper\'s name may not be empty'), 'error');
		$Errors[$i] = 'ShipperName';
		$i++;
	}

	if (isset($SelectedShipper) AND $InputError !=1) {

		/*SelectedShipper could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$SQL = "UPDATE shippers SET shippername='" . $_POST['ShipperName'] . "'
				WHERE shipper_id = '".$SelectedShipper."'";
		$Msg = __('The shipper record has been updated');
	} elseif ($InputError !=1) {

	/*SelectedShipper is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Shipper form */

		$SQL = "INSERT INTO shippers (shippername) VALUES ('" . $_POST['ShipperName'] . "')";
		$Msg = __('The shipper record has been added');
	}

	//run the SQL from either of the above possibilites
	if ($InputError !=1) {
		$Result = DB_query($SQL);
		echo '<br />';
		prnMsg($Msg, 'success');
		unset($SelectedShipper);
		unset($_POST['ShipperName']);
		unset($_POST['Shipper_ID']);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'SalesOrders'

	$SQL= "SELECT COUNT(*) FROM salesorders WHERE salesorders.shipvia='".$SelectedShipper."'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		$CancelDelete = 1;
		echo '<br />';
		prnMsg( __('Cannot delete this shipper because sales orders have been created using this shipper') . '. ' . __('There are'). ' '.
			$MyRow[0] . ' '. __('sales orders using this shipper code'), 'error');

	} else {
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'

		$SQL= "SELECT COUNT(*) FROM debtortrans WHERE debtortrans.shipvia='".$SelectedShipper."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			$CancelDelete = 1;
			echo '<br />';
			prnMsg( __('Cannot delete this shipper because invoices have been created using this shipping company') . '. ' . __('There are').  ' ' .
				$MyRow[0] . ' ' . __('invoices created using this shipping company'), 'error');
		} else {
			// Prevent deletion if the selected shipping company is the current default shipping company in config.php !!
			if ($_SESSION['Default_Shipper']==$SelectedShipper) {

				$CancelDelete = 1;
				echo '<br />';
				prnMsg( __('Cannot delete this shipper because it is defined as the default shipping company in the configuration file'), 'error');

			} else {

				$SQL="DELETE FROM shippers WHERE shipper_id='".$SelectedShipper."'";
				$Result = DB_query($SQL);
				echo '<br />';
				prnMsg( __('The shipper record has been deleted'), 'success');
			}
		}
	}
	unset($SelectedShipper);
	unset($_GET['delete']);
}

	$SQL = "SELECT * FROM shippers ORDER BY shipper_id";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M10 17h4V5H2v12h3m5 0h4m5 2V9l-7-4-7 4v10"></path></svg>
					' . __('Defined Shipping Companies') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('ID') . '</th>
								<th>' . __('Carrier Name') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td class="font-bold">' . $MyRow[0] . '</td>
				<td>' . $MyRow[1] . '</td>
				<td class="text-center">
					<div class="db-action-group" style="justify-content:center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedShipper=' . $MyRow[0] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedShipper=' . $MyRow[0] . '&amp;delete=1" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this shipper?') . '\');">
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


	if (isset($SelectedShipper)) {
		echo '<div class="centre" style="margin-bottom: var(--space-6);">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
					' . __('REVIEW RECORDS') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedShipper)) {
			// Editing an existing Shipper
			$SQL = "SELECT shipper_id, shippername FROM shippers WHERE shipper_id='" . $SelectedShipper . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);

			$_POST['Shipper_ID'] = $MyRow['shipper_id'];
			$_POST['ShipperName'] = $MyRow['shippername'];

			echo '<input type="hidden" name="SelectedShipper" value="' . $SelectedShipper . '" />';
			echo '<input type="hidden" name="Shipper_ID" value="' . $_POST['Shipper_ID'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Shipper Details') . ': ' . $_POST['Shipper_ID'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Shipper Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['Shipper_ID'] . '" disabled />
							</div>';

		} else {
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create New Shipper') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">';
		}

		if (!isset($_POST['ShipperName'])) {
			$_POST['ShipperName'] = '';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Shipper Name') . '</label>
				<input type="text" name="ShipperName" class="db-input" required maxlength="40" value="' . $_POST['ShipperName'] . '" autofocus />
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Shipper Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page

} //end if record deleted no point displaying form to add record

include(__DIR__ . '/includes/footer.php');
