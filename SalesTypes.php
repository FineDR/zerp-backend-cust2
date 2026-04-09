<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Types') . ' / ' . __('Price List Maintenance');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Define various sales types and pricing tiers') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
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

	if (mb_strlen($_POST['TypeAbbrev']) > 2) {
		$InputError = 1;
		prnMsg(__('The sales type (price list) code must be two characters or less long'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif ($_POST['TypeAbbrev']=='' OR $_POST['TypeAbbrev']==' ' OR $_POST['TypeAbbrev']=='  ') {
		$InputError = 1;
		prnMsg( __('The sales type (price list) code cannot be an empty string or spaces'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif ( trim($_POST['Sales_Type'])==''){
		$InputError = 1;
		prnMsg(__('The sales type (price list) description cannot be empty'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif (mb_strlen($_POST['Sales_Type']) >40) {
		$InputError = 1;
		prnMsg(__('The sales type (price list) description must be forty characters or less long'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	} elseif ($_POST['TypeAbbrev']=='AN'){
		$InputError = 1;
		prnMsg(__('The sales type code cannot be AN since this is a system defined abbreviation for any sales type in general ledger interface lookups'),'error');
		$Errors[$i] = 'SalesType';
		$i++;
	}

	if (isset($SelectedType) AND $InputError !=1) {

		$SQL = "UPDATE salestypes
			SET sales_type = '" . $_POST['Sales_Type'] . "'
			WHERE typeabbrev = '".$SelectedType."'";

		$Msg = __('The customer/sales/pricelist type') . ' ' . $SelectedType . ' ' .  __('has been updated');
	} elseif ( $InputError !=1 ) {

		// First check the type is not being duplicated

		$CheckSQL = "SELECT count(*)
			     FROM salestypes
			     WHERE typeabbrev = '" . $_POST['TypeAbbrev'] . "'";

		$CheckResult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($CheckResult);

		if ( $CheckRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The customer/sales/pricelist type ') . $_POST['TypeAbbrev'] . __(' already exist.'),'error');
		} else {

			// Add new record on submit

			$SQL = "INSERT INTO salestypes (typeabbrev,
											sales_type)
							VALUES ('" . str_replace(' ', '', $_POST['TypeAbbrev']) . "',
									'" . $_POST['Sales_Type'] . "')";

			$Msg = __('Customer/sales/pricelist type') . ' ' . $_POST['Sales_Type'] .  ' ' . __('has been created');
			$CheckSQL = "SELECT count(typeabbrev)
						FROM salestypes";
			$Result = DB_query($CheckSQL);
			$Row = DB_fetch_row($Result);

		}
	}

	if ( $InputError !=1) {
	//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);

	// Check the default price list exists
		$CheckSQL = "SELECT count(*)
			     FROM salestypes
			     WHERE typeabbrev = '" . $_SESSION['DefaultPriceList'] . "'";
		$CheckResult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($CheckResult);

	// If it doesnt then update config with newly created one.
		if ($CheckRow[0] == 0) {
			$SQL = "UPDATE config
					SET confvalue='".$_POST['TypeAbbrev']."'
					WHERE confname='DefaultPriceList'";
			$Result = DB_query($SQL);
			$_SESSION['DefaultPriceList'] = $_POST['TypeAbbrev'];
		}

		prnMsg($Msg,'success');

		unset($SelectedType);
		unset($_POST['TypeAbbrev']);
		unset($_POST['Sales_Type']);
	}

} elseif ( isset($_GET['delete']) ) {

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'
	// Prevent delete if saletype exist in customer transactions

	$SQL= "SELECT COUNT(*)
	       FROM debtortrans
	       WHERE debtortrans.tpe='".$SelectedType."'";

	$ErrMsg = __('The number of transactions using this customer/sales/pricelist type could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this sale type because customer transactions have been created using this sales type') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('transactions using this sales type code'),'error');

	} else {

		$SQL = "SELECT COUNT(*) FROM debtorsmaster WHERE salestype='".$SelectedType."'";

		$ErrMsg = __('The number of transactions using this Sales Type record could not be retrieved because');
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg(__('Cannot delete this sale type because customers are currently set up to use this sales type') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('customers with this sales type code'));
		} else {

			$SQL="DELETE FROM salestypes WHERE typeabbrev='" . $SelectedType . "'";
			$ErrMsg = __('The Sales Type record could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg(__('Sales type') . ' / ' . __('price list') . ' ' . $SelectedType  . ' ' . __('has been deleted') ,'success');

			$SQL ="DELETE FROM prices WHERE prices.typeabbrev='" . $SelectedType . "'";
			$ErrMsg =  __('The Sales Type prices could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			prnMsg(' ...  ' . __('and any prices for this sales type / price list were also deleted'),'success');
			unset ($SelectedType);
			unset($_GET['delete']);

		}
	} //end if sales type used in debtor transactions or in customers set up
}


if (isset($_POST['Cancel'])){
	unset($SelectedType);
	unset($_POST['TypeAbbrev']);
	unset($_POST['Sales_Type']);
}

	$SQL = "SELECT typeabbrev, sales_type FROM salestypes ORDER BY typeabbrev";
	$Result = DB_query($SQL);

	echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
			<div class="card-header-v2">
				<h3>
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
					' . __('Defined Sales Types') . '
				</h3>
			</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
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
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedType=' . $MyRow[0] . '&amp;delete=yes" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this price list and all the prices it may have set up?') . '\');">
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
					' . __('Show All Sales Types Defined') . '
				</a>
			</div>';
	}

	if (!isset($_GET['delete'])) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		if (isset($SelectedType) AND $SelectedType != '') {
			$Res = DB_query("SELECT typeabbrev, sales_type FROM salestypes WHERE typeabbrev='" . $SelectedType . "'");
			$MyRow = DB_fetch_array($Res);
			$_POST['TypeAbbrev'] = $MyRow['typeabbrev'];
			$_POST['Sales_Type'] = $MyRow['sales_type'];

			echo '<input type="hidden" name="SelectedType" value="' . $SelectedType . '" />
				<input type="hidden" name="TypeAbbrev" value="' . $_POST['TypeAbbrev'] . '" />';

			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							' . __('Edit Sales Type / Price List') . ': ' . $_POST['TypeAbbrev'] . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Type Code') . '</label>
								<input type="text" class="db-input" value="' . $_POST['TypeAbbrev'] . '" disabled />
							</div>';

		} else {
			echo '<div class="card-v2">
					<div class="card-header-v2">
						<h3>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
							' . __('Create Sales Type / Price List') . '
						</h3>
					</div>
					<div class="db-card-body">
						<div class="db-grid db-grid-2">
							<div class="db-field">
								<label class="db-label">' . __('Type Code') . '</label>
								<input type="text" name="TypeAbbrev" class="db-input" required maxlength="2" autofocus />
								<p class="db-field-help">' . __('Enter a unique 2-character code') . '</p>
							</div>';
		}

		if (!isset($_POST['Sales_Type'])) {
			$_POST['Sales_Type'] = '';
		}

		echo '<div class="db-field">
				<label class="db-label">' . __('Sales Type Name') . '</label>
				<input type="text" name="Sales_Type" class="db-input" required maxlength="40" value="' . $_POST['Sales_Type'] . '" />
			</div>
		</div></div>'; // End db-grid & db-card-body

		echo '<div class="db-card-actions" style="justify-content: center; padding: 2rem; background: var(--surface-alt); border-top: 1px solid var(--border-color);">
				<button type="submit" name="submit" class="db-btn db-btn-primary db-btn-large">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:10px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
					' . __('Save Sales Type Information') . '
				</button>
			</div>
		</div></form>'; // End card-v2 & form
	}

	echo '</div>'; // End db-page

} // end if user wish to delete

include(__DIR__ . '/includes/footer.php');
