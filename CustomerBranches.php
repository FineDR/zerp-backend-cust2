<?php

/* Defines the details of customer branches such as delivery address and contact details - also sales area, representative etc.*/

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Branches');// Screen identification.
$ViewTopic = 'AccountsReceivable';// Filename's id in ManualContents.php's TOC.
$BookMark = 'NewCustomerBranch';// Anchor's id in the manual's html document.
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/CountriesArray.php');
include(__DIR__ . '/includes/LanguagesArray.php');

// Initialize numeric separators from language preferences with global scope
global $ThousandsSeparator, $DecimalPoint;
$ThousandsSeparator = $LanguagesArray[$_SESSION['Language']]['ThousandsSeparator'] ?? (isset($_SESSION['DefaultThousandsSeparator']) ? $_SESSION['DefaultThousandsSeparator'] : ',');
$DecimalPoint = $LanguagesArray[$_SESSION['Language']]['DecimalPoint'] ?? (isset($_SESSION['DefaultDecimalPoint']) ? $_SESSION['DefaultDecimalPoint'] : '.');

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = mb_strtoupper($_GET['DebtorNo']);
} elseif (isset($_POST['DebtorNo'])){
	$DebtorNo = mb_strtoupper($_POST['DebtorNo']);
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
					<span style="color: #064e3b; opacity: 0.9;">' . __('customer branches') . '</span>
				</div>
				<div>
					<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . (isset($_GET['SelectedBranch']) ? __('Modify Branch') : __('Branch Registry')) . '</h1>
					<p style="font-size: 1.1rem; margin-top: 12px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Managing delivery points for') . ' <span style="font-weight: 800;">' . htmlspecialchars($CustomerName, ENT_QUOTES, 'UTF-8') . '</span></p>
				</div>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Customers.php?DebtorNo=' . $DebtorNo . '" class="architect-btn secondary">
					<i class="fas fa-user"></i> ' . __('Edit Customer') . '
				</a>
				<a href="' . $RootPath . '/SelectCustomer.php" class="architect-btn secondary">
					<i class="fas fa-search"></i> ' . __('Search Others') . '
				</a>
			</div>
		</div>';

if (isset($_GET['DebtorNo'])) {
	$DebtorNo = mb_strtoupper($_GET['DebtorNo']);
} elseif (isset($_POST['DebtorNo'])){
	$DebtorNo = mb_strtoupper($_POST['DebtorNo']);
}

if (!isset($DebtorNo)) {
	prnMsg(__('This page must be called with the debtor code of the customer for whom you wish to edit the branches for').'.
		<br />' . __('When the pages is called from within the system this will always be the case').' <br />' .
			__('Select a customer first then select the link to add/edit/delete branches'),'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}


if (isset($_GET['SelectedBranch'])){
	$SelectedBranch = mb_strtoupper($_GET['SelectedBranch']);
} elseif (isset($_POST['SelectedBranch'])){
	$SelectedBranch = mb_strtoupper($_POST['SelectedBranch']);
}

// initialise no input errors assumed initially before we test
$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {

	$i=1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	$_POST['BranchCode'] = mb_strtoupper($_POST['BranchCode']);

	if ($_SESSION['SalesmanLogin'] !=  '') {
		$_POST['Salesman'] = $_SESSION['SalesmanLogin'];
	}
	if (ContainsIllegalCharacters($_POST['BranchCode']) OR mb_strstr($_POST['BranchCode'],' ')) {
		$InputError = 1;
		prnMsg(__('The Branch code cannot contain any of the following characters')." - &amp; \' &lt; &gt;",'error');
		$Errors[$i] = 'BranchCode';
		$i++;
	}
	if (mb_strlen($_POST['BranchCode'])==0) {
		$InputError = 1;
		prnMsg(__('The Branch code must be at least one character long'),'error');
		$Errors[$i] = 'BranchCode';
		$i++;
	}
	if (!is_numeric($_POST['FwdDate'])) {
		$InputError = 1;
		prnMsg(__('The date after which invoices are charged to the following month is expected to be a number and a recognised number has not been entered'),'error');
		$Errors[$i] = 'FwdDate';
		$i++;
	}
	if ($_POST['FwdDate'] >30) {
		$InputError = 1;
		prnMsg(__('The date (in the month) after which invoices are charged to the following month should be a number less than 31'),'error');
		$Errors[$i] = 'FwdDate';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['EstDeliveryDays']))) {
		$InputError = 1;
		prnMsg(__('The estimated delivery days is expected to be a number and a recognised number has not been entered'),'error');
		$Errors[$i] = 'EstDeliveryDays';
		$i++;
	}
	if (filter_number_format($_POST['EstDeliveryDays']) >60) {
		$InputError = 1;
		prnMsg(__('The estimated delivery days should be a number of days less than 60') . '. ' . __('A package can be delivered by seafreight anywhere in the world normally in less than 60 days'),'error');
		$Errors[$i] = 'EstDeliveryDays';
		$i++;
	}
	if (!isset($_POST['EstDeliveryDays'])) {
		$_POST['EstDeliveryDays']=1;
	}
	if (!isset($Latitude)) {
		$Latitude=0.0;
		$Longitude=0.0;
	}
	if ($_SESSION['geocode_integration']==1 ){
		// Get the lat/long from OpenStreetMap Nominatim
		$SQL = "SELECT * FROM geocode_param";
		$Resultgeo = DB_query($SQL);
		$Row = DB_fetch_array($Resultgeo);
		
		// Build address string
		$Address = urlencode($_POST['BrAddress1'] . ', ' . $_POST['BrAddress2'] . ', ' . $_POST['BrAddress3'] . ', ' . $_POST['BrAddress4']);
		$BaseURL = "https://nominatim.openstreetmap.org/search?format=json&q=";
		$RequestURL = $BaseURL . $Address . '&limit=1';

		// Set up proper headers for Nominatim usage policy
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header'=>"User-Agent: webERP-geocoding\r\n"
			)
		);
		$context = stream_context_create($opts);
		$buffer = @file_get_contents($RequestURL, false, $context);

		if ($buffer !== false) {
			$json = json_decode($buffer, true);
			if (!empty($json) && isset($json[0]['lat']) && isset($json[0]['lon'])) {
				// Successful geocode
				$Latitude = $json[0]['lat'];
				$Longitude = $json[0]['lon'];
			} else {
				// No results found
				prnMsg(__('Geocode Notice') . ': ' . $Address . ' ' . __('failed to geocode') . ' - ' . __('No results found'), 'info');
			}
		} else {
			// Connection failed
			prnMsg(__('Geocode Notice') . ': ' . $Address . ' ' . __('failed to geocode') . ' - ' . __('Connection failed'), 'warn');
		}
		
		// Respect Nominatim usage policy: 1 request per second
		usleep(1000000);
	}
	if (isset($SelectedBranch) AND $InputError != 1) {

		/*SelectedBranch could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the 	delete code below*/

		$SQL = "UPDATE custbranch SET brname = '" . $_POST['BrName'] . "',
						braddress1 = '" . $_POST['BrAddress1'] . "',
						braddress2 = '" . $_POST['BrAddress2'] . "',
						braddress3 = '" . $_POST['BrAddress3'] . "',
						braddress4 = '" . $_POST['BrAddress4'] . "',
						braddress5 = '" . $_POST['BrAddress5'] . "',
						braddress6 = '" . $_POST['BrAddress6'] . "',
						lat = '" . $Latitude . "',
						lng = '" . $Longitude . "',
						specialinstructions = '" . $_POST['SpecialInstructions'] . "',
						phoneno='" . $_POST['PhoneNo'] . "',
						faxno='" . $_POST['FaxNo'] . "',
						fwddate= '" . $_POST['FwdDate'] . "',
						contactname='" . $_POST['ContactName'] . "',
						salesman= '" . $_POST['Salesman'] . "',
						area='" . $_POST['Area'] . "',
						estdeliverydays ='" . filter_number_format($_POST['EstDeliveryDays']) . "',
						email='" . $_POST['Email'] . "',
						taxgroupid='" . $_POST['TaxGroup'] . "',
						defaultlocation='" . $_POST['DefaultLocation'] . "',
						brpostaddr1 = '" . $_POST['BrPostAddr1'] . "',
						brpostaddr2 = '" . $_POST['BrPostAddr2'] . "',
						brpostaddr3 = '" . $_POST['BrPostAddr3'] . "',
						brpostaddr4 = '" . $_POST['BrPostAddr4'] . "',
						brpostaddr5 = '" . $_POST['BrPostAddr5'] . "',
						disabletrans='" . $_POST['DisableTrans'] . "',
						defaultshipvia='" . $_POST['DefaultShipVia'] . "',
						custbranchcode='" . $_POST['CustBranchCode'] ."',
						deliverblind='" . $_POST['DeliverBlind'] . "'
					WHERE branchcode = '".$SelectedBranch."' AND debtorno='".$DebtorNo."'";

		if ($_SESSION['SalesmanLogin'] !=  '') {
			$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
		}

		$Msg = $_POST['BrName'] . ' '.__('branch has been updated.');

	} elseif ($InputError != 1) {

	/*Selected branch is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new Customer Branches form */

		$SQL = "INSERT INTO custbranch (branchcode,
						debtorno,
						brname,
						braddress1,
						braddress2,
						braddress3,
						braddress4,
						braddress5,
						braddress6,
						lat,
						lng,
 						specialinstructions,
						estdeliverydays,
						fwddate,
						salesman,
						phoneno,
						faxno,
						contactname,
						area,
						email,
						taxgroupid,
						defaultlocation,
						brpostaddr1,
						brpostaddr2,
						brpostaddr3,
						brpostaddr4,
						brpostaddr5,
						disabletrans,
						defaultshipvia,
						custbranchcode,
						deliverblind)
				VALUES ('" . $_POST['BranchCode'] . "',
					'" . $DebtorNo . "',
					'" . $_POST['BrName'] . "',
					'" . $_POST['BrAddress1'] . "',
					'" . $_POST['BrAddress2'] . "',
					'" . $_POST['BrAddress3'] . "',
					'" . $_POST['BrAddress4'] . "',
					'" . $_POST['BrAddress5'] . "',
					'" . $_POST['BrAddress6'] . "',
					'" . $Latitude . "',
					'" . $Longitude . "',
					'" . $_POST['SpecialInstructions'] . "',
					'" . filter_number_format($_POST['EstDeliveryDays']) . "',
					'" . $_POST['FwdDate'] . "',
					'" . $_POST['Salesman'] . "',
					'" . $_POST['PhoneNo'] . "',
					'" . $_POST['FaxNo'] . "',
					'" . $_POST['ContactName'] . "',
					'" . $_POST['Area'] . "',
					'" . $_POST['Email'] . "',
					'" . $_POST['TaxGroup'] . "',
					'" . $_POST['DefaultLocation'] . "',
					'" . $_POST['BrPostAddr1'] . "',
					'" . $_POST['BrPostAddr2'] . "',
					'" . $_POST['BrPostAddr3'] . "',
					'" . $_POST['BrPostAddr4'] . "',
					'" . $_POST['BrPostAddr5'] . "',
					'" . $_POST['DisableTrans'] . "',
					'" . $_POST['DefaultShipVia'] . "',
					'" . $_POST['CustBranchCode'] ."',
					'" . $_POST['DeliverBlind'] . "')";
	}
	echo '<br />';
	$Msg = __('Customer branch') . '<b> ' . $_POST['BranchCode'] . ': ' . $_POST['BrName'] . ' </b>' . __('has been added, add another branch, or return to the') . ' <a href="' . $RootPath . '/index.php">' . __('Main Menu') . '</a>';

	//run the SQL from either of the above possibilites

	$ErrMsg = __('The branch record could not be inserted or updated because');
	if ($InputError==0) {
		$Result = DB_query($SQL, $ErrMsg);
	}

	if (DB_error_no() ==0 AND $InputError==0) {
		prnMsg($Msg,'success');
		unset($_POST['BranchCode']);
		unset($_POST['BrName']);
		unset($_POST['BrAddress1']);
		unset($_POST['BrAddress2']);
		unset($_POST['BrAddress3']);
		unset($_POST['BrAddress4']);
		unset($_POST['BrAddress5']);
		unset($_POST['BrAddress6']);
		unset($_POST['SpecialInstructions']);
		unset($_POST['EstDeliveryDays']);
		unset($_POST['FwdDate']);
		unset($_POST['Salesman']);
		unset($_POST['PhoneNo']);
		unset($_POST['FaxNo']);
		unset($_POST['ContactName']);
		unset($_POST['Area']);
		unset($_POST['Email']);
		unset($_POST['TaxGroup']);
		unset($_POST['DefaultLocation']);
		unset($_POST['DisableTrans']);
		unset($_POST['BrPostAddr1']);
		unset($_POST['BrPostAddr2']);
		unset($_POST['BrPostAddr3']);
		unset($_POST['BrPostAddr4']);
		unset($_POST['BrPostAddr5']);
		unset($_POST['DefaultShipVia']);
		unset($_POST['CustBranchCode']);
		unset($_POST['DeliverBlind']);
		unset($SelectedBranch);
	}
} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'DebtorTrans'

	$SQL= "SELECT COUNT(*) FROM debtortrans WHERE debtortrans.branchcode='".$SelectedBranch."' AND debtorno = '".$DebtorNo."'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this branch because customer transactions have been created to this branch') . '<br />' .
			 __('There are').' ' . $MyRow[0] . ' '.__('transactions with this Branch Code'),'error');

	} else {
		$SQL= "SELECT COUNT(*) FROM salesanalysis WHERE salesanalysis.custbranch='".$SelectedBranch."' AND salesanalysis.cust = '".$DebtorNo."'";

		$Result = DB_query($SQL);

		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg(__('Cannot delete this branch because sales analysis records exist for it'),'error');
			echo '<br />' . __('There are').' ' . $MyRow[0] . ' '.__('sales analysis records with this Branch Code/customer');

		} else {

			$SQL= "SELECT COUNT(*) FROM salesorders WHERE salesorders.branchcode='".$SelectedBranch."' AND salesorders.debtorno = '".$DebtorNo."'";
			$Result = DB_query($SQL);

			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0]>0) {
				prnMsg(__('Cannot delete this branch because sales orders exist for it') . '. ' . __('Purge old sales orders first'),'warn');
				echo '<br />' . __('There are').' ' . $MyRow[0] . ' '.__('sales orders for this Branch/customer');
			} else {
				// Check if there are any users that refer to this branch code
				$SQL= "SELECT COUNT(*) FROM www_users WHERE www_users.branchcode='".$SelectedBranch."' AND www_users.customerid = '".$DebtorNo."'";

				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);

				if ($MyRow[0]>0) {
					prnMsg(__('Cannot delete this branch because users exist that refer to it') . '. ' . __('Purge old users first'),'warn');
					echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' '.__('users referring to this Branch/customer');
				} else {
						// Check if there are any contract that refer to this branch code
					$SQL = "SELECT COUNT(*) FROM contracts WHERE contracts.branchcode='" . $SelectedBranch . "' AND contracts.debtorno = '" . $DebtorNo . "'";

					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);

					if ($MyRow[0]>0) {
						prnMsg(__('Cannot delete this branch because contract have been created that refer to it') . '. ' . __('Purge old contracts first'),'warn');
						echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' '.__('contracts referring to this branch/customer');
					} else {
						//check if this it the last customer branch - don't allow deletion of the last branch
						$SQL = "SELECT COUNT(*) FROM custbranch WHERE debtorno='" . $DebtorNo . "'";

						$Result = DB_query($SQL);
						$MyRow = DB_fetch_row($Result);

						if ($MyRow[0]==1) {
							prnMsg(__('Cannot delete this branch because it is the only branch defined for this customer.'),'warn');
						} else {
							$SQL="DELETE FROM custbranch WHERE branchcode='" . $SelectedBranch . "' AND debtorno='" . $DebtorNo . "'";
							if ($_SESSION['SalesmanLogin'] !=  '') {
								$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
							}
							$ErrMsg = __('The branch record could not be deleted') . ' - ' . __('the SQL server returned the following message');
							$Result = DB_query($SQL, $ErrMsg);
							if (DB_error_no()==0){
								prnMsg(__('Branch Deleted'),'success');
							}
						}
					}
				}
			}
		}
	}//end ifs to test if the branch can be deleted

}
if (!isset($SelectedBranch)){

/* It could still be the second time the page has been run and a record has been selected for modification - SelectedBranch will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters then none of the above are true and the list of branches will be displayed with links to delete or edit each. These will call the same page again and allow update/input or deletion of the records*/

	$SQL = "SELECT debtorsmaster.name,
					custbranch.branchcode,
					brname,
					salesman.salesmanname,
					areas.areadescription,
					contactname,
					phoneno,
					faxno,
					custbranch.email,
					taxgroups.taxgroupdescription,
					custbranch.disabletrans
				FROM custbranch INNER JOIN debtorsmaster
				ON custbranch.debtorno=debtorsmaster.debtorno
				INNER JOIN areas
				ON custbranch.area=areas.areacode
				INNER JOIN salesman
				ON custbranch.salesman=salesman.salesmancode
				INNER JOIN taxgroups
				ON custbranch.taxgroupid=taxgroups.taxgroupid
				WHERE custbranch.debtorno='" . $DebtorNo . "'";

	$Result = DB_query($SQL);

	if (DB_num_rows($Result) > 0) {
		$MyRow = DB_fetch_row($Result);
		$TotalEnable = 0;
		$TotalDisable = 0;
		echo '<div style="background: #fff; overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Branch Code') . '</th>
								<th>' . __('Branch Name') . '</th>
								<th>' . __('Authorized Contact') . '</th>
								<th>' . __('Logistics / Region') . '</th>
								<th>' . __('Communication') . '</th>
								<th>' . __('Tax Group') . '</th>
								<th class="text-center">' . __('Status') . '</th>
								<th style="text-align: right;">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

		do {
			echo '<tr>
					<td style="font-weight: 700; color: #064e3b;">' . $MyRow[1] . '</td>
					<td>' . $MyRow[2] . '</td>
					<td>' . $MyRow[5] . '</td>
					<td>
						<div style="font-weight: 600;">' . $MyRow[3] . '</div>
						<div style="font-size: 0.75rem; opacity: 0.6;">' . $MyRow[4] . '</div>
					</td>
					<td>
						<div style="font-weight: 600;">' . $MyRow[6] . '</div>
						<div style="font-size: 0.75rem; color: #059669;"><a href="mailto:' . $MyRow[8] . '">' . $MyRow[8] . '</a></div>
					</td>
					<td>' . $MyRow[9] . '</td>
					<td class="text-center">';
			
			if ($MyRow[10]) {
				echo '<span class="badge" style="background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">' . __('Disabled') . '</span>';
				$TotalDisable++;
			} else {
				echo '<span class="badge" style="background: #f0fdf4; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700;">' . __('Enabled') . '</span>';
				$TotalEnable++;
			}

			echo '</td>
					<td style="text-align: right;">
						<div style="display: flex; gap: 8px; justify-content: flex-end;">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?DebtorNo=' . $DebtorNo . '&amp;SelectedBranch=' . urlencode($MyRow[1]) . '" class="db-btn db-btn-icon" style="background: #f3f4f6; color: #059669; height: 32px; width: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" title="' . __('Edit') . '">
								<i class="fas fa-edit" style="font-size: 0.8rem;"></i>
							</a>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?DebtorNo=' . $DebtorNo . '&amp;SelectedBranch=' . urlencode($MyRow[1]) . '&amp;delete=yes" class="db-btn db-btn-icon" style="background: #fef2f2; color: #dc2626; height: 32px; width: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this branch?') . '\');">
								<i class="fas fa-trash-alt" style="font-size: 0.8rem;"></i>
							</a>
						</div>
					</td>
				</tr>';
		} while ($MyRow = DB_fetch_row($Result));

		echo '			</tbody>
						</table>
					</div>
				</div>
				<div class="db-card-actions" style="padding: 1rem; background: var(--surface-alt); border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
					<div class="text-sm text-muted">
						' . __('Total Branches') . ': <strong>' . ($TotalEnable+$TotalDisable) . '</strong>
					</div>
					<div class="db-action-group">
						<span class="text-sm text-success" style="margin-right: 1rem;">' . $TotalEnable . ' ' . __('Enabled') . '</span>
						<span class="text-sm text-danger">' . $TotalDisable . ' ' . __('Disabled') . '</span>
					</div>
				</div>
			</div>';
	} else {
		$SQL = "SELECT debtorsmaster.name,
						address1,
						address2,
						address3,
						address4,
						address5,
						address6
					FROM debtorsmaster
					WHERE debtorno = '".$DebtorNo."'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		echo '<div class="page_help_text">' . __('No Branches are defined for').' - '.$MyRow[0]. '. ' . __('You must have a minimum of one branch for each Customer. Please add a branch now.') . '</div>';
		$_POST['BranchCode'] = mb_substr($DebtorNo,0,10);
		$_POST['BrName'] = $MyRow[0];
		$_POST['BrAddress1'] = $MyRow[1];
		$_POST['BrAddress2'] = $MyRow[2];
		$_POST['BrAddress3'] = $MyRow[3];
		$_POST['BrAddress4'] = $MyRow[4];
		$_POST['BrAddress5'] = $MyRow[5];
		$_POST['BrAddress6'] = $MyRow[6];
		unset($MyRow);
	}
}

if (!isset($_GET['delete'])) {
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedBranch)) {
		//editing an existing branch

		$SQL = "SELECT branchcode,
						brname,
						braddress1,
						braddress2,
						braddress3,
						braddress4,
						braddress5,
						braddress6,
						specialinstructions,
						estdeliverydays,
						fwddate,
						salesman,
						area,
						phoneno,
						faxno,
						contactname,
						email,
						taxgroupid,
						defaultlocation,
						brpostaddr1,
						brpostaddr2,
						brpostaddr3,
						brpostaddr4,
						brpostaddr5,
						disabletrans,
						defaultshipvia,
						custbranchcode,
						deliverblind
					FROM custbranch
					WHERE branchcode='".$SelectedBranch."'
					AND debtorno='".$DebtorNo."'";

		if ($_SESSION['SalesmanLogin'] !=  '') {
			$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
		}

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		if ($InputError==0) {
			$_POST['BranchCode'] = $MyRow['branchcode'];
			$_POST['BrName'] = $MyRow['brname'];
			$_POST['BrAddress1'] = $MyRow['braddress1'];
			$_POST['BrAddress2'] = $MyRow['braddress2'];
			$_POST['BrAddress3'] = $MyRow['braddress3'];
			$_POST['BrAddress4'] = $MyRow['braddress4'];
			$_POST['BrAddress5'] = $MyRow['braddress5'];
			$_POST['BrAddress6'] = $MyRow['braddress6'];
			$_POST['SpecialInstructions'] = $MyRow['specialinstructions'];
			$_POST['BrPostAddr1'] = $MyRow['brpostaddr1'];
			$_POST['BrPostAddr2'] = $MyRow['brpostaddr2'];
			$_POST['BrPostAddr3'] = $MyRow['brpostaddr3'];
			$_POST['BrPostAddr4'] = $MyRow['brpostaddr4'];
			$_POST['BrPostAddr5'] = $MyRow['brpostaddr5'];
			$_POST['EstDeliveryDays'] = locale_number_format($MyRow['estdeliverydays'],0);
			$_POST['FwdDate'] =$MyRow['fwddate'];
			$_POST['ContactName'] = $MyRow['contactname'];
			$_POST['Salesman'] =$MyRow['salesman'];
			$_POST['Area'] =$MyRow['area'];
			$_POST['PhoneNo'] =$MyRow['phoneno'];
			$_POST['FaxNo'] =$MyRow['faxno'];
			$_POST['Email'] =$MyRow['email'];
			$_POST['TaxGroup'] = $MyRow['taxgroupid'];
			$_POST['DisableTrans'] = $MyRow['disabletrans'];
			$_POST['DefaultLocation'] = $MyRow['defaultlocation'];
			$_POST['DefaultShipVia'] = $MyRow['defaultshipvia'];
			$_POST['CustBranchCode'] = $MyRow['custbranchcode'];
			$_POST['DeliverBlind'] = $MyRow['deliverblind'];
		}

		echo '<input type="hidden" name="SelectedBranch" value="' . $SelectedBranch . '" />';
		echo '<input type="hidden" name="BranchCode" value="' . $_POST['BranchCode'] . '" />';

		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
						' . __('Change Branch Details') . ': ' . $SelectedBranch . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Branch Code') . '</label>
							<input type="text" class="db-input" value="' . $_POST['BranchCode'] . '" disabled />
						</div>';

	} else {//end of if $SelectedBranch only do the else when a new record is being entered

		if (isset($_GET['BranchCode'])){
			$SQL="SELECT name, address1, address2, address3, address4, address5, address6
					FROM debtorsmaster WHERE debtorno='".$_GET['BranchCode']."'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$_POST['BranchCode'] = $_GET['BranchCode'];
			$_POST['BrName'] = $MyRow['name'];
		 	$_POST['BrAddress1'] = $MyRow['address1'];
			$_POST['BrAddress2'] = $MyRow['address2'];
			$_POST['BrAddress3'] = $MyRow['address3'];
		 	$_POST['BrAddress4'] = $MyRow['address4'];
			$_POST['BrAddress5'] = $MyRow['address5'];
			$_POST['BrAddress6'] = $MyRow['address6'];
		}
		if (!isset($_POST['BranchCode'])) {
			$_POST['BranchCode']='';
		}
		echo '<div class="card-v2">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M12 5v14M5 12h14"></path></svg>
						' . __('Create New Branch') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-2">
						<div class="db-field">
							<label class="db-label">' . __('Branch Code') . '</label>
							<input tabindex="1" type="text" name="BranchCode" required="required" placeholder="'.__('alpha-numeric').'" class="db-input" maxlength="10" value="' . $_POST['BranchCode'] . '" />
							<p class="db-field-help">'.__('Up to 10 characters. Avoid special characters.') . '</p>
						</div>';
		$_POST['DeliverBlind'] = $_SESSION['DefaultBlindPackNote'];
	}

	echo '<input type="hidden" name="DebtorNo" value="'. $DebtorNo . '" />';

	echo '<div class="db-field">
			<label class="db-label">', __('Branch Name'), '</label>
			<input tabindex="2" type="text" autofocus="autofocus" required="required" name="BrName" class="db-input" maxlength="40" value="'. $_POST['BrName'].'" />
		</div>
		<div class="db-field">
			<label class="db-label">' . __('Branch Contact Name') . '</label>
			<input tabindex="3" type="text" name="ContactName" required="required" class="db-input" maxlength="40" value="'. $_POST['ContactName'].'" />
		</div>
	</div>'; // End top grid

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Street Address') . '</label>
				<input tabindex="4" type="text" name="BrAddress1" class="db-input" maxlength="40" placeholder="' . __('Line 1') . '" value="'. $_POST['BrAddress1'].'" /><br />

				<input tabindex="5" type="text" name="BrAddress2" class="db-input" maxlength="40" placeholder="' . __('Line 2') . '" value="'. $_POST['BrAddress2'].'" style="margin-top: 8px;" />
			</div>

			<div class="db-grid db-grid-2">
				<div class="db-field">
					<label class="db-label">' . __('City / Suburb') . '</label>
					<input tabindex="6" type="text" name="BrAddress3" class="db-input" maxlength="40" value="'. $_POST['BrAddress3'].'" />
				</div>

				<div class="db-field">
					<label class="db-label">' . __('Province / State') . '</label>
					<input tabindex="7" type="text" name="BrAddress4" class="db-input" maxlength="50" value="'. $_POST['BrAddress4'].'" />
				</div>
			</div>

			<div class="db-grid db-grid-2">
				<div class="db-field">
					<label class="db-label">' . __('Postal Code') . '</label>
					<input tabindex="8" type="text" name="BrAddress5" class="db-input" maxlength="20" value="'. $_POST['BrAddress5'].'" />
				</div>

				<div class="db-field">
					<label class="db-label">' . __('Country') . '</label>
					<select name="BrAddress6" class="db-input">';
	foreach ($CountriesArray as $CountryEntry => $CountryName){
		$sel = (isset($_POST['BrAddress6']) AND ($_POST['BrAddress6'] == $CountryName)) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $CountryName . '">' . $CountryName . '</option>';
	}
	echo '			</select>
				</div>
			</div>';

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-3">
			<div class="db-field">
				<label class="db-label">' . __('Special Instructions') . '</label>
				<input tabindex="10" type="text" name="SpecialInstructions" class="db-input" value="'. $_POST['SpecialInstructions'].'" />
			</div>

			<div class="db-field">
				<label class="db-label">' . __('Days to Deliver') . '</label>
				<input tabindex="11" type="text" name="EstDeliveryDays" class="db-input db-number" maxlength="2" value="'. $_POST['EstDeliveryDays'].'" />
			</div>

			<div class="db-field">
				<label class="db-label">' . __('Forward Date Day') . '</label>
				<input tabindex="12" type="text" name="FwdDate" class="db-input db-number" maxlength="2" value="'. $_POST['FwdDate'].'" />
				<p class="db-field-help">' . __('Day of month') . '</p>
			</div>
		</div>';

	if ($_SESSION['SalesmanLogin'] !=  '') {
		echo '<field>
				<label for="Salesman">' . __('Salesperson').':</label>
				<fieldtext>', $_SESSION['UsersRealName'], '</fieldtext>
			</field>';
	} else {

		//SQL to poulate account selection boxes
		$SQL = "SELECT salesmanname,
						salesmancode
				FROM salesman
				WHERE current = 1
				ORDER BY salesmanname";

		$Result = DB_query($SQL);

		if (DB_num_rows($Result)==0){
			echo '</fieldset>';
			prnMsg(__('There are no sales people defined as yet') . ' - ' . __('customer branches must be allocated to a sales person') . '. ' . __('Please use the link below to define at least one sales person'),'error');
			echo '<p align="center"><a href="' . $RootPath . '/SalesPeople.php">' . __('Define Sales People') . '</a>';
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	} // <-- ADDED THIS BRACE

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-3">';

	if ($_SESSION['SalesmanLogin'] !=  '') {
		echo '<div class="db-field">
				<label class="db-label">' . __('Salesperson') . '</label>
				<input type="text" class="db-input" value="' . $_SESSION['UsersRealName'] . '" disabled />
			</div>';
	} else {
		echo '<div class="db-field">
				<label class="db-label">' . __('Salesperson') . '</label>
				<select tabindex="13" name="Salesman" class="db-input">';
		$Result = DB_query("SELECT salesmanname, salesmancode FROM salesman");
		while ($myr = DB_fetch_array($Result)) {
			$sel = ($_POST['Salesman'] == $myr['salesmancode']) ? 'selected="selected"' : '';
			echo '<option ' . $sel . ' value="' . $myr['salesmancode'] . '">' . $myr['salesmanname'] . '</option>';
		}
		echo '</select></div>';
	}

	echo '<div class="db-field">
			<label class="db-label">' . __('Sales Area') . '</label>
			<select tabindex="14" name="Area" class="db-input">';
	$Result = DB_query("SELECT areadescription, areacode FROM areas");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['Area'] == $myr['areacode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['areacode'] . '">' . $myr['areadescription'] . '</option>';
	}
	echo '</select></div>';

	echo '<div class="db-field">
			<label class="db-label">' . __('Stock Location') . '</label>
			<select tabindex="15" name="DefaultLocation" class="db-input">';
	$Result = DB_query("SELECT locationname, loccode FROM locations");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['DefaultLocation'] == $myr['loccode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['loccode'] . '">' . $myr['locationname'] . '</option>';
	}
	echo '</select></div></div>';

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-3">
			<div class="db-field">
				<label class="db-label">' . __('Phone Number') . '</label>
				<input tabindex="16" type="tel" name="PhoneNo" class="db-input" maxlength="25" value="'. $_POST['PhoneNo'].'" />
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Fax Number') . '</label>
				<input tabindex="17" type="tel" name="FaxNo" class="db-input" maxlength="25" value="'. $_POST['FaxNo'].'" />
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Email Address') . '</label>
				<input tabindex="18" type="email" name="Email" class="db-input" maxlength="55" value="'. $_POST['Email'].'" />
			</div>
		</div>';

	DB_data_seek($Result,0);

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Tax Group') . '</label>
				<select tabindex="19" name="TaxGroup" class="db-input">';
	$Result = DB_query("SELECT taxgroupid, taxgroupdescription FROM taxgroups");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['TaxGroup'] == $myr['taxgroupid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['taxgroupid'] . '">' . $myr['taxgroupdescription'] . '</option>';
	}
	echo '			</select>
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Branch Status') . '</label>
				<select tabindex="20" name="DisableTrans" class="db-input">
					<option ' . ($_POST['DisableTrans']==0 ? 'selected="selected"' : '') . ' value="0">' . __('Enabled') . '</option>
					<option ' . ($_POST['DisableTrans']==1 ? 'selected="selected"' : '') . ' value="1">' . __('Disabled') . '</option>
				</select>
			</div>
		</div>
		<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Freight Shipper') . '</label>
				<select tabindex="21" name="DefaultShipVia" class="db-input">';
	$Result = DB_query("SELECT shipper_id, shippername FROM shippers");
	while ($myr = DB_fetch_array($Result)) {
		$sel = ($_POST['DefaultShipVia'] == $myr['shipper_id']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['shipper_id'] . '">' . $myr['shippername'] . '</option>';
	}
	echo '			</select>
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Default Packlist Type') . '</label>
				<select tabindex="22" name="DeliverBlind" class="db-input">
					<option ' . ($_POST['DeliverBlind']==1 ? 'selected="selected"' : '') . ' value="1">' . __('Show Company Details') . '</option>
					<option ' . ($_POST['DeliverBlind']==2 ? 'selected="selected"' : '') . ' value="2">' . __('Hide Company Details') . '</option>
				</select>
			</div>
		</div>';

	echo '<hr style="margin: var(--space-6) 0; border: 0; border-top: 1px solid var(--border-color);" />';

	echo '<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Postal Address') . '</label>
				<input tabindex="23" type="text" name="BrPostAddr1" class="db-input" maxlength="40" placeholder="' . __('Line 1') . '" value="'. $_POST['BrPostAddr1'].'" />
				<input tabindex="24" type="text" name="BrPostAddr2" class="db-input" maxlength="40" placeholder="' . __('Line 2') . '" value="'. $_POST['BrPostAddr2'].'" style="margin-top: 8px;" />
				<input tabindex="25" type="text" name="BrPostAddr3" class="db-input" maxlength="40" placeholder="' . __('Province / State') . '" value="'. $_POST['BrPostAddr3'].'" style="margin-top: 8px;" />
				<input tabindex="26" type="text" name="BrPostAddr4" class="db-input" maxlength="40" placeholder="' . __('Postal Code') . '" value="'. $_POST['BrPostAddr4'].'" style="margin-top: 8px;" />
				<input tabindex="27" type="text" name="BrPostAddr5" class="db-input" maxlength="20" placeholder="' . __('Country (Optional)') . '" value="'. $_POST['BrPostAddr5'].'" style="margin-top: 8px;" />
			</div>
			<div class="db-field">
				<label class="db-label">' . __('Internal Branch Code (EDI)') . '</label>
				<input tabindex="28" type="text" name="CustBranchCode" class="db-input" maxlength="30" value="'. $_POST['CustBranchCode'].'" />
				<p class="db-field-help">' . __('For electronic data interchange purposes') . '</p>
			</div>
		</div>
	</div>'; // End db-card-body

	echo '<div class="db-card-actions" style="justify-content: center; padding: 32px; background: #f9fafb; border-top: 1px solid #f3f4f6; gap: 16px;">
			<button type="submit" name="submit" class="architect-btn" style="width: 240px; height: 48px;">
				<i class="fas fa-check-circle" style="margin-right: 10px;"></i>
				' . __('Save Branch Details') . '
			</button>
			<button type="reset" class="architect-btn secondary" style="width: 140px; height: 48px;">
				<i class="fas fa-undo" style="margin-right: 10px;"></i>
				' . __('Reset') . '
			</button>
		</div>
	</div>'; // End card-v2

	echo '</form>';
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
