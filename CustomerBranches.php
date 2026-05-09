<?php

/* Defines the details of customer branches such as delivery address and contact details - also sales area, representative etc.*/

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Branches');// Screen identification.
$ViewTopic = 'AccountsReceivable';// Filename's id in ManualContents.php's TOC.
$BookMark = 'NewCustomerBranch';// Anchor's id in the manual's html document.
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/CountriesArray.php');
include(__DIR__ . '/includes/LanguagesArray.php');

$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: 40px 32px; background: #f8fafc; min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { 
		margin-bottom: 40px; 
		padding: 40px;
		background: #ffffff;
		border-radius: 24px;
		border: 1px solid #e2e8f0;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	
	.db-card {
		background: #ffffff;
		border-radius: 20px;
		border: 1px solid #e2e8f0;
		box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
		margin-bottom: 32px;
		overflow: hidden;
	}
	.db-card-header {
		padding: 24px 32px;
		background: #f8fafc;
		border-bottom: 1px solid #e2e8f0;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #1e293b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 12px 24px; border-radius: 12px;
		background: #059669; color: #ffffff !important; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.2s; cursor: pointer;
	}
	.architect-btn:hover { background: #047857; transform: translateY(-1px); }
	.architect-btn.secondary { background: #f1f5f9; color: #475569 !important; }
	.architect-btn.secondary:hover { background: #e2e8f0; color: #1e293b !important; }
	.architect-btn.danger { background: #fee2e2; color: #dc2626 !important; }
	.architect-btn.danger:hover { background: #fecaca; }

	.db-grid { display: grid; gap: 24px; }
	.db-grid-2 { grid-template-columns: repeat(2, 1fr); }
	.db-grid-3 { grid-template-columns: repeat(3, 1fr); }
	
	.db-field { display: flex; flex-direction: column; gap: 8px; }
	.db-label { font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #64748b; letter-spacing: 0.5px; }
	.db-input { 
		width: 100%; border-radius: 10px; height: 48px; 
		border: 1px solid #e2e8f0; padding: 0 16px; 
		font-weight: 600; color: #1e293b; transition: all 0.2s;
	}
	.db-input:focus { border-color: #059669; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }
	
	.registry-table { width: 100%; border-collapse: collapse; }
	.registry-table th { padding: 16px 24px; background: #f8fafc; text-align: left; font-size: 0.75rem; font-weight: 800; color: #64748b; border-bottom: 1px solid #e2e8f0; }
	.registry-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	
	.breadcrumb-item { color: #64748b; text-decoration: none; font-weight: 600; transition: color 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
</style>
';

// Initialize numeric separators from language preferences with global scope
global $ThousandsSeparator, $DecimalPoint;
$ThousandsSeparator = $LanguagesArray[$_SESSION['Language']]['ThousandsSeparator'] ?? (isset($_SESSION['DefaultThousandsSeparator']) ? $_SESSION['DefaultThousandsSeparator'] : ',');
$DecimalPoint = $LanguagesArray[$_SESSION['Language']]['DecimalPoint'] ?? (isset($_SESSION['DefaultDecimalPoint']) ? $_SESSION['DefaultDecimalPoint'] : '.');

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

$SQLname = "SELECT name FROM debtorsmaster WHERE debtorno='" . $DebtorNo . "'";
$Result = DB_query($SQLname);
$Row = DB_fetch_array($Result);
$CustomerName = $Row['name'];

echo '<div class="db-page">
		<div class="premium-header">
			<div>
				<div style="font-size: 0.75rem; display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
					<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('Home') . '</a>
					<i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.3;"></i>
					<a href="SelectCustomer.php" class="breadcrumb-item">' . __('Customers') . '</a>
					<i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.3;"></i>
					<span style="color: #64748b; font-weight: 600;">' . __('Branches') . '</span>
				</div>
				<div>
					<h1 style="font-size: 2rem; font-weight: 900; color: #1e293b; margin: 0; letter-spacing: -0.5px;">' . (isset($_GET['SelectedBranch']) ? __('Modify Branch') : __('Branch Registry')) . '</h1>
					<p style="margin: 8px 0 0 0; color: #64748b; font-weight: 500;">' . __('Managing logistics for') . ' <span style="color: #059669; font-weight: 700;">' . htmlspecialchars($CustomerName) . '</span></p>
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
		$TotalEnable = 0;
		$TotalDisable = 0;
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Active Branches') . '</h3>
				</div>
				<div style="overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Name') . '</th>
								<th>' . __('Contact') . '</th>
								<th>' . __('Sales & Area') . '</th>
								<th>' . __('Contact Info') . '</th>
								<th class="text-center">' . __('Status') . '</th>
								<th style="text-align: right;">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

		while ($MyRow = DB_fetch_row($Result)) {
			echo '<tr>
					<td style="font-weight: 800; color: #1e293b;">' . $MyRow[1] . '</td>
					<td style="font-weight: 600;">' . $MyRow[2] . '</td>
					<td>' . $MyRow[5] . '</td>
					<td>
						<div style="font-weight: 700; color: #059669; font-size: 0.85rem;">' . $MyRow[3] . '</div>
						<div style="font-size: 0.72rem; color: #64748b;">' . $MyRow[4] . '</div>
					</td>
					<td>
						<div style="font-weight: 600;">' . $MyRow[6] . '</div>
						<div style="font-size: 0.75rem; color: #059669;"><a href="mailto:' . $MyRow[8] . '" style="text-decoration:none;">' . $MyRow[8] . '</a></div>
					</td>
					<td class="text-center">';
			
			if ($MyRow[10]) {
				echo '<span style="background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">' . __('Disabled') . '</span>';
				$TotalDisable++;
			} else {
				echo '<span style="background: #dcfce7; color: #15803d; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">' . __('Active') . '</span>';
				$TotalEnable++;
			}

			echo '</td>
					<td style="text-align: right;">
						<div style="display: flex; gap: 8px; justify-content: flex-end;">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?DebtorNo=' . $DebtorNo . '&amp;SelectedBranch=' . urlencode($MyRow[1]) . '" class="architect-btn secondary" style="padding: 8px; min-width: 36px; height: 36px;" title="' . __('Edit') . '">
								<i class="fas fa-edit"></i>
							</a>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?DebtorNo=' . $DebtorNo . '&amp;SelectedBranch=' . urlencode($MyRow[1]) . '&amp;delete=yes" class="architect-btn danger" style="padding: 8px; min-width: 36px; height: 36px;" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this branch?') . '\');">
								<i class="fas fa-trash-alt"></i>
							</a>
						</div>
					</td>
				</tr>';
		}

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
	}
	echo '<div class="db-card" style="margin-top: 32px;">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-edit"></i> ' . (isset($SelectedBranch) ? __('Modify Branch Details') : __('Register New Branch')) . '</h3>
			</div>
			<div class="db-card-body">
				<input type="hidden" name="DebtorNo" value="'. $DebtorNo . '" />';

	if (isset($SelectedBranch)) {
		echo '<input type="hidden" name="SelectedBranch" value="' . $SelectedBranch . '" />';
		echo '<input type="hidden" name="BranchCode" value="' . $_POST['BranchCode'] . '" />';
	}

	echo '<div class="db-grid db-grid-3" style="margin-bottom: 32px;">';
	
	if (!isset($SelectedBranch)) {
		echo '<div class="db-field">
				<label class="db-label">' . __('Branch Code') . '</label>
				<input tabindex="1" type="text" name="BranchCode" required="required" placeholder="'.__('e.g. BR001').'" class="db-input" maxlength="10" value="' . ($_POST['BranchCode'] ?? '') . '" />
			  </div>';
	} else {
		echo '<div class="db-field">
				<label class="db-label">' . __('Branch Code') . '</label>
				<input type="text" class="db-input" value="' . $_POST['BranchCode'] . '" disabled style="background:#f1f5f9;" />
			  </div>';
	}

	echo '<div class="db-field">
			<label class="db-label">', __('Branch Name'), '</label>
			<input tabindex="2" type="text" autofocus="autofocus" required="required" name="BrName" class="db-input" maxlength="40" value="'. ($_POST['BrName'] ?? '').'" />
		</div>
		<div class="db-field">
			<label class="db-label">' . __('Contact Person') . '</label>
			<input tabindex="3" type="text" name="ContactName" required="required" class="db-input" maxlength="40" value="'. ($_POST['ContactName'] ?? '').'" />
		</div>
	</div>

	<div style="padding: 24px; background: #f8fafc; border-radius: 16px; margin-bottom: 32px;">
		<h4 style="margin: 0 0 20px 0; font-size: 0.85rem; text-transform: uppercase; color: #64748b; letter-spacing: 1px;"><i class="fas fa-map-marked-alt" style="margin-right:8px;"></i>' . __('Logistics & Delivery Address') . '</h4>
		<div class="db-grid db-grid-2">
			<div class="db-field">
				<label class="db-label">' . __('Physical Address') . '</label>
				<input tabindex="4" type="text" name="BrAddress1" class="db-input" maxlength="40" placeholder="' . __('Street / Building') . '" value="'. ($_POST['BrAddress1'] ?? '').'" />
				<input tabindex="5" type="text" name="BrAddress2" class="db-input" maxlength="40" placeholder="' . __('Area / Plot') . '" value="'. ($_POST['BrAddress2'] ?? '').'" style="margin-top: 8px;" />
			</div>
			<div class="db-grid db-grid-2">
				<div class="db-field">
					<label class="db-label">' . __('City') . '</label>
					<input tabindex="6" type="text" name="BrAddress3" class="db-input" maxlength="40" value="'. ($_POST['BrAddress3'] ?? '').'" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Region / State') . '</label>
					<input tabindex="7" type="text" name="BrAddress4" class="db-input" maxlength="50" value="'. ($_POST['BrAddress4'] ?? '').'" />
				</div>
			</div>
			<div class="db-grid db-grid-2">
				<div class="db-field">
					<label class="db-label">' . __('Postal Code / ZIP') . '</label>
					<input tabindex="8" type="text" name="BrAddress5" class="db-input" maxlength="20" value="'. ($_POST['BrAddress5'] ?? '').'" />
				</div>
				<div class="db-field">
					<label class="db-label">' . __('Country') . '</label>
					<input tabindex="9" type="text" name="BrAddress6" class="db-input" maxlength="40" value="'. ($_POST['BrAddress6'] ?? '').'" />
				</div>
			</div>
		</div>
	</div>

	<div class="db-grid db-grid-3" style="margin-bottom: 32px;">
		<div class="db-field">
			<label class="db-label">' . __('Communication (Phone)') . '</label>
			<input tabindex="10" type="tel" name="PhoneNo" class="db-input" maxlength="25" value="'. ($_POST['PhoneNo'] ?? '').'" />
		</div>
		<div class="db-field">
			<label class="db-label">' . __('Email Address') . '</label>
			<input tabindex="11" type="email" name="Email" class="db-input" maxlength="55" value="'. ($_POST['Email'] ?? '').'" />
		</div>
		<div class="db-field">
			<label class="db-label">' . __('Stock Source Location') . '</label>
			<select tabindex="12" name="DefaultLocation" class="db-input">';
	$Result = DB_query("SELECT locationname, loccode FROM locations");
	while ($myr = DB_fetch_array($Result)) {
		$sel = (isset($_POST['DefaultLocation']) && $_POST['DefaultLocation'] == $myr['loccode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['loccode'] . '">' . $myr['locationname'] . '</option>';
	}
	echo '</select></div>
	</div>

	<div class="db-grid db-grid-2" style="margin-bottom: 32px;">
		<div class="db-field">
			<label class="db-label">' . __('Sales Representative') . '</label>
			<select tabindex="13" name="Salesman" class="db-input">';
	$Result = DB_query("SELECT salesmanname, salesmancode FROM salesman");
	while ($myr = DB_fetch_array($Result)) {
		$sel = (isset($_POST['Salesman']) && $_POST['Salesman'] == $myr['salesmancode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['salesmancode'] . '">' . $myr['salesmanname'] . '</option>';
	}
	echo '</select></div>
		<div class="db-field">
			<label class="db-label">' . __('Sales Area') . '</label>
			<select tabindex="14" name="Area" class="db-input">';
	$Result = DB_query("SELECT areadescription, areacode FROM areas");
	while ($myr = DB_fetch_array($Result)) {
		$sel = (isset($_POST['Area']) && $_POST['Area'] == $myr['areacode']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['areacode'] . '">' . $myr['areadescription'] . '</option>';
	}
	echo '</select></div>
	</div>

	<div class="db-grid db-grid-3" style="margin-bottom: 32px;">
		<div class="db-field">
			<label class="db-label">' . __('Tax Group') . '</label>
			<select tabindex="15" name="TaxGroup" class="db-input">';
	$Result = DB_query("SELECT taxgroupdescription, taxgroupid FROM taxgroups");
	while ($myr = DB_fetch_array($Result)) {
		$sel = (isset($_POST['TaxGroup']) && $_POST['TaxGroup'] == $myr['taxgroupid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['taxgroupid'] . '">' . $myr['taxgroupdescription'] . '</option>';
	}
	echo '</select></div>
		<div class="db-field">
			<label class="db-label">' . __('Shipping Method') . '</label>
			<select tabindex="16" name="DefaultShipVia" class="db-input">';
	$Result = DB_query("SELECT shipper_id, shippername FROM shippers");
	while ($myr = DB_fetch_array($Result)) {
		$sel = (isset($_POST['DefaultShipVia']) && $_POST['DefaultShipVia'] == $myr['shipper_id']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $myr['shipper_id'] . '">' . $myr['shippername'] . '</option>';
	}
	echo '</select></div>
		<div class="db-field">
			<label class="db-label">' . __('Transaction Status') . '</label>
			<select tabindex="17" name="DisableTrans" class="db-input">
				<option ' . (($_POST['DisableTrans'] ?? 0) == 0 ? 'selected="selected"' : '') . ' value="0">' . __('Enabled') . '</option>
				<option ' . (($_POST['DisableTrans'] ?? 0) == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Disabled') . '</option>
			</select>
		</div>
	</div>

	<div class="db-field" style="margin-bottom: 32px;">
		<label class="db-label">' . __('Special Instructions') . '</label>
		<textarea tabindex="18" name="SpecialInstructions" class="db-input" style="height: 100px; padding: 12px;">' . ($_POST['SpecialInstructions'] ?? '') . '</textarea>
	</div>

	<div style="display: none;">
		<input type="hidden" name="EstDeliveryDays" value="' . ($_POST['EstDeliveryDays'] ?? 1) . '" />
		<input type="hidden" name="FwdDate" value="' . ($_POST['FwdDate'] ?? 0) . '" />
		<input type="hidden" name="CustBranchCode" value="' . ($_POST['CustBranchCode'] ?? '') . '" />
		<input type="hidden" name="DeliverBlind" value="' . ($_POST['DeliverBlind'] ?? 1) . '" />
	</div>
	</div>

	<div style="display: flex; gap: 16px; padding: 32px; background: #f8fafc; border-top: 1px solid #e2e8f0; justify-content: center;">
		<button type="submit" name="submit" class="architect-btn" style="min-width: 200px;">
			<i class="fas fa-save"></i> ' . __('Commit Branch Details') . '
		</button>
		<button type="reset" class="architect-btn secondary" style="min-width: 120px;">
			<i class="fas fa-undo"></i> ' . __('Reset') . '
		</button>
	</div>
</div>';

	echo '</form>';
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
?>