<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineContractClass.php');

require(__DIR__ . '/includes/session.php');

if (isset($_POST['RequiredDate'])){$_POST['RequiredDate'] = ConvertSQLDate($_POST['RequiredDate']);}

if (isset($_GET['ModifyContractNo'])) {
	$Title = __('Modify Contract') . ' ' . $_GET['ModifyContractNo'];
} else {
	$Title = __('Contract Entry');
}

if (isset($_GET['CustomerID'])) {
	$_POST['SelectedCustomer']=$_GET['CustomerID'];
}

foreach ($_POST as $FormVariableName=>$FormVariableValue) {
	if (mb_substr($FormVariableName, 0, 6)=='Submit') {
		$Index = mb_substr($FormVariableName, 6);
		$_POST['SelectedCustomer']=$_POST['SelectedCustomer'.$Index];
		$_POST['SelectedBranch']=$_POST['SelectedBranch'.$Index];
	}
}

$ViewTopic = 'Contracts';
$BookMark = 'CreateContract';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// KPI Metrics Logic
$sqlOrders = "SELECT COUNT(*) FROM contracts WHERE status=2";
$resOrders = DB_query($sqlOrders);
$rowOrders = DB_fetch_row($resOrders);
$OrderedCount = $rowOrders[0];

$sqlQuotes = "SELECT COUNT(*) FROM contracts WHERE status=1";
$resQuotes = DB_query($sqlQuotes);
$rowQuotes = DB_fetch_row($resQuotes);
$QuoteCount = $rowQuotes[0];

$sqlDrafts = "SELECT COUNT(*) FROM contracts WHERE status=0";
$resDrafts = DB_query($sqlDrafts);
$rowDrafts = DB_fetch_row($resDrafts);
$DraftCount = $rowDrafts[0];

echo '<div class="dashboard-shell-container" style="max-width: 1400px; margin: 0 auto;">
        <header class="db-page-header">
            <div>
                <h2 class="db-page-title">' . $Title . '</h2>
                <p class="db-page-subtitle">' . __('Manage contract lifecycle from draft to confirmed order') . '</p>
            </div>
            <div class="db-page-actions">
                <a href="' . $RootPath . '/SelectContract.php" class="db-btn db-btn-outline">
                    <i class="fas fa-list"></i> ' . __('Search Contracts') . '
                </a>
                <a href="' . $RootPath . '/Contracts.php?NewContract=Yes" class="db-btn db-btn-primary">
                    <i class="fas fa-plus"></i> ' . __('New Contract') . '
                </a>
            </div>
        </header>

        <!-- Premium KPI Row -->
        <div class="kpi-grid" style="padding: 0 var(--space-6); margin-bottom: var(--space-6);">
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Ordered Contracts') . '</span>
                    <span class="value">' . $OrderedCount . '</span>
                </div>
            </div>
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Quotation Pipeline') . '</span>
                    <span class="value">' . $QuoteCount . '</span>
                </div>
            </div>
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Drafts') . '</span>
                    <span class="value">' . $DraftCount . '</span>
                </div>
            </div>
        </div>

        <div class="MainBody" style="display: flex; flex-direction: column; gap: var(--space-6); padding-bottom: var(--space-10);">';

/*If the page is called is called without an identifier being set then
 * it must be either a new contract, or the start of a modification of an
 * existing contract, and so we must create a new identifier.
 *
 * The identifier only needs to be unique for this php session, so a
 * unix timestamp will be sufficient.
 */

if (!isset($_GET['identifier'])) {
	$identifier=date('U');
} else {
	$identifier=$_GET['identifier'];
}

if (isset($_GET['NewContract']) AND isset($_SESSION['Contract'.$identifier])){
	unset($_SESSION['Contract'.$identifier]);
	$_SESSION['ExistingContract'] = 0;
}

if (isset($_GET['NewContract']) AND isset($_GET['SelectedCustomer'])) {
	/*
	* initialize a new contract
	*/
	$_SESSION['ExistingContract']=0;
	unset($_SESSION['Contract'.$identifier]->ContractBOM);
	unset($_SESSION['Contract'.$identifier]->ContractReqts);
	unset($_SESSION['Contract'.$identifier]);
	/* initialize new class object */
	$_SESSION['Contract'.$identifier] = new Contract;

	$_POST['SelectedCustomer'] = $_GET['SelectedCustomer'];

	/*The customer is checked for credit and the Contract Object populated
	 * using the usual logic of when a customer is selected
	 * */
}

if (isset($_SESSION['Contract'.$identifier]) AND
			(isset($_POST['EnterContractBOM'])
				OR isset($_POST['EnterContractRequirements']))){
	/**  Ensure session variables updated */

	$_SESSION['Contract'.$identifier]->ContractRef=$_POST['ContractRef'];
	$_SESSION['Contract'.$identifier]->ContractDescription=$_POST['ContractDescription'];
	$_SESSION['Contract'.$identifier]->CategoryID = $_POST['CategoryID'];
	$_SESSION['Contract'.$identifier]->LocCode = $_POST['LocCode'];
	$_SESSION['Contract'.$identifier]->RequiredDate = $_POST['RequiredDate'];
	$_SESSION['Contract'.$identifier]->Margin = filter_number_format($_POST['Margin']);
	$_SESSION['Contract'.$identifier]->CustomerRef = $_POST['CustomerRef'];
	$_SESSION['Contract'.$identifier]->ExRate = filter_number_format($_POST['ExRate']);
	$_SESSION['Contract'.$identifier]->DefaultWorkCentre = $_POST['DefaultWorkCentre'];


/*User hit the button to enter line items -
  then meta refresh to Contract_Items.php*/
	$InputError = false;
	if (mb_strlen($_SESSION['Contract'.$identifier]->ContractRef)<5){
		prnMsg(__('The contract reference must be entered (and be longer than 5 characters) before the requirements of the contract can be setup'),'warn');
		$InputError = true;
	}

	if (isset($_POST['EnterContractBOM']) AND !$InputError){
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/ContractBOM.php?identifier='.$identifier. '" />';
		echo '<br />';
		prnMsg(__('You should automatically be forwarded to the entry of the Contract line items page') . '. ' .
		__('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' . '<a href="' . $RootPath . '/ContractBOM.php?identifier='.$identifier . '">' . __('click here') . '</a> ' . __('to continue'),'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (isset($_POST['EnterContractRequirements']) AND !$InputError){
		echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/ContractOtherReqts.php?identifier='.$identifier. '" />';
		echo '<br />';
		prnMsg(__('You should automatically be forwarded to the entry of the Contract requirements page') . '. ' .
		__('If this does not happen') . ' (' . __('if the browser does not support META Refresh') . ') ' .
		'<a href="' . $RootPath . '/ContractOtherReqts.php?identifier=' . $identifier . '">' . __('click here') . '</a> ' . __('to continue'),'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
} /* end of if going to contract BOM or contract requriements */

// Workflow links handled within the header or action cards

$SupportedImgExt = array('png','jpg','jpeg');

//attempting to upload the drawing image file
if (isset($_FILES['Drawing']) AND $_FILES['Drawing']['name'] != '' AND $_SESSION['Contract'.$identifier]->ContractRef!= '') {

	$Result = $_FILES['Drawing']['error'];
	$ImgExt = pathinfo($_FILES['Drawing']['name'], PATHINFO_EXTENSION);

 	$UploadTheFile = 'Yes'; //Assume all is well to start off with
	$FileName = $_SESSION['part_pics_dir'] . '/' . $_SESSION['Contract'.$identifier]->ContractRef . '.' . $ImgExt;

	//But check for the worst
	if (!in_array ($ImgExt, $SupportedImgExt)) {
		prnMsg(__('Only ' . implode(", ", $SupportedImgExt) . ' files are supported - a file extension of ' . implode(", ", $SupportedImgExt) . ' is expected'),'warn');
		$UploadTheFile ='No';
	} elseif ( $_FILES['Drawing']['size'] > ($_SESSION['MaxImageSize']*1024)) { //File Size Check
		prnMsg(__('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $_SESSION['MaxImageSize'],'warn');
		$UploadTheFile ='No';
	} elseif ( $_FILES['Drawing']['type'] == 'text/plain' ) {  //File Type Check
		prnMsg( __('Only graphics files can be uploaded'),'warn');
		$UploadTheFile ='No';
	}
	foreach ($SupportedImgExt as $Ext) {
		$File = $_SESSION['part_pics_dir'] . '/' . $_SESSION['Contract'.$identifier]->ContractRef . '.' . $Ext;
		if (file_exists ($File) ) {
			$Result = unlink($File);
			if (!$Result){
				prnMsg(__('The existing image could not be removed'),'error');
				$UploadTheFile ='No';
			}
		}
	}

	if ($UploadTheFile=='Yes'){
		$Result  =  move_uploaded_file($_FILES['Drawing']['tmp_name'], $FileName);
		$Message = ($Result)?__('File url') . '<a href="' . $FileName . '">' .  $FileName . '</a>' : __('Something is wrong with uploading the file');
	}
}


/*The page can be called with ModifyContractRef=x where x is a contract
 * reference. The page then looks up the details of contract x and allows
 * these details to be modified */

if (isset($_GET['ModifyContractRef'])){

	if (isset($_SESSION['Contract'.$identifier])){
		unset ($_SESSION['Contract'.$identifier]->ContractBOM);
		unset ($_SESSION['Contract'.$identifier]->ContractReqts);
		unset ($_SESSION['Contract'.$identifier]);
	}

	$_SESSION['ExistingContract']=$_GET['ModifyContractRef'];
	$_SESSION['RequireCustomerSelection'] = 0;
	$_SESSION['Contract'.$identifier] = new Contract;

	/*read in all the guff from the selected contract into the contract Class variable  */
	$ContractRef = $_GET['ModifyContractRef'];
	include(__DIR__ . '/includes/Contract_Readin.php');

}// its an existing contract to readin

if (isset($_POST['CancelContract'])) {
/*The cancel button on the header screen - to delete the contract */
	$OK_to_delete = true; //assume this in the first instance
	if (!isset($_SESSION['ExistingContract']) OR $_SESSION['ExistingContract']!= 0) {
		/* need to check that not already ordered by the customer - status = 100  */
		if ($_SESSION['Contract'.$identifier]->Status==2) {
			$OK_to_delete = false;
			prnMsg( __('The contract has already been ordered by the customer the order must also be deleted first before the contract can be deleted'),'warn');
		}
	}

	if ($OK_to_delete){
		$SQL = "DELETE FROM contractbom WHERE contractref='" . $_SESSION['Contract'.$identifier]->ContractRef . "'";
		$ErrMsg = __('The contract bill of materials could not be deleted because');
		$DelResult = DB_query($SQL, $ErrMsg);
		$SQL = "DELETE FROM contractreqts WHERE contractref='" . $_SESSION['Contract'.$identifier]->ContractRef . "'";
		$ErrMsg = __('The contract requirements could not be deleted because');
		$DelResult = DB_query($SQL, $ErrMsg);
		$SQL= "DELETE FROM contracts WHERE contractref='" . $_SESSION['Contract'.$identifier]->ContractRef . "'";
		$ErrMsg = __('The contract could not be deleted because');
		$DelResult = DB_query($SQL, $ErrMsg);

		if ($_SESSION['Contract'.$identifier]->Status==1){
			$SQL = "DELETE FROM salesorderdetails WHERE orderno='" . $_SESSION['Contract'.$identifier]->OrderNo . "'";
			$ErrMsg = __('The quotation lines for the contract could not be deleted because');
			$DelResult = DB_query($SQL, $ErrMsg);
			$SQL = "DELETE FROM salesorders WHERE orderno='" . $_SESSION['Contract'.$identifier]->OrderNo . "'";
			$ErrMsg = __('The quotation for the contract could not be deleted because');
			$DelResult = DB_query($SQL, $ErrMsg);
		}
		prnMsg( __('Contract').' '.$_SESSION['Contract'.$identifier]->ContractRef.' '.__('has been cancelled'), 'success');
		unset($_SESSION['ExistingContract']);
		unset($_SESSION['Contract'.$identifier]->ContractBOM);
		unset($_SESSION['Contract'.$identifier]->ContractReqts);
		unset($_SESSION['Contract'.$identifier]);
	}
}

if (!isset($_SESSION['Contract'.$identifier])){
	/* It must be a new contract being created
	 * $_SESSION['Contract'.$identifier] would be set up from the order modification
	 * code above if a modification to an existing contract. Also
	 * $ExistingContract would be set to the ContractRef
	 * */
		$_SESSION['ExistingContract']= 0;
		$_SESSION['Contract'.$identifier] = new Contract;

		if ($_SESSION['Contract'.$identifier]->DebtorNo==''
				OR !isset($_SESSION['Contract'.$identifier]->DebtorNo)){

/* a session variable will have to maintain if a supplier
 * has been selected for the order or not the session
 * variable CustomerID holds the supplier code already
 * as determined from user id /password entry  */
			$_SESSION['RequireCustomerSelection'] = 1;
		} else {
			$_SESSION['RequireCustomerSelection'] = 0;
		}
}

if (isset($_POST['CommitContract']) OR isset($_POST['CreateQuotation'])){
	/*This is the bit where the contract object is commited to the database after a bit of error checking */

	//First update the session['Contract'.$identifier] variable with all inputs from the form

	$InputError = false; //assume no errors on input then test for errors
	if (mb_strlen($_POST['ContractRef']) < 2){
		prnMsg(__('The contract reference is expected to be more than 2 characters long. Please alter the contract reference before proceeding.'),'error');
		$InputError = true;
	}
	if (ContainsIllegalCharacters($_POST['ContractRef'])){
		prnMsg(__('The contract reference cannot contain any spaces, slashes, or inverted commas. Please alter the contract reference before proceeding.'),'error');
		$InputError = true;
	}

	//The contractRef cannot be the same as an existing stockid or contractref
	$Result = DB_query("SELECT stockid FROM stockmaster WHERE stockid='" . $_POST['ContractRef'] . "'");
	if (DB_num_rows($Result)==1 AND $_SESSION['Contract'.$identifier]->Status ==0){
		prnMsg(__('The contract reference cannot be the same as a previously created stock item. Please modify the contract reference before continuing'),'error');
		$InputError=true;
	}
	if (mb_strlen($_POST['ContractDescription'])<10){
		prnMsg(__('The contract description is expected to be more than 10 characters long. Please alter the contract description in full before proceeding.'),'error');
		$InputError = true;
	}
	if (! Is_Date($_POST['RequiredDate'])){
		prnMsg(__('The date the contract is required to be completed by must be entered in the format') . ' ' . $_SESSION['DefaultDateFormat'],'error');
		$InputError =true;
	}
	if (Date1GreaterThanDate2(date($_SESSION['DefaultDateFormat']),$_POST['RequiredDate']) AND $_POST['RequiredDate']!= ''){
		prnMsg(__('The date that the contract is to be completed by is expected to be a date in the future. Make the required date a date after today before proceeding.'),'error');
		$InputError =true;
	}

	if (!$InputError) {
		$_SESSION['Contract'.$identifier]->ContractRef=$_POST['ContractRef'];
		$_SESSION['Contract'.$identifier]->ContractDescription=$_POST['ContractDescription'];
		$_SESSION['Contract'.$identifier]->CategoryID = $_POST['CategoryID'];
		$_SESSION['Contract'.$identifier]->LocCode = $_POST['LocCode'];
		$_SESSION['Contract'.$identifier]->RequiredDate = $_POST['RequiredDate'];
		$_SESSION['Contract'.$identifier]->Margin = filter_number_format($_POST['Margin']);
		$_SESSION['Contract'.$identifier]->Status = $_POST['Status'];
		$_SESSION['Contract'.$identifier]->CustomerRef = $_POST['CustomerRef'];
		$_SESSION['Contract'.$identifier]->ExRate = filter_number_format($_POST['ExRate']);

		/*Get the first work centre for the users location - until we set this up properly */
		$Result = DB_query("SELECT code FROM workcentres WHERE location='" . $_SESSION['Contract'.$identifier]->LocCode ."'");
		if (DB_num_rows($Result)>0){
			$WorkCentreRow = DB_fetch_row($Result);
			$WorkCentre = $WorkCentreRow[0];
		} else { //need to add a default work centre for the location
			$Result = DB_query("INSERT INTO workcentres (code,
														location,
														description,
														overheadrecoveryact)
											VALUES ('" . $_SESSION['Contract'.$identifier]->LocCode . "',
													'" . $_SESSION['Contract'.$identifier]->LocCode . "',
													'" . __('Default for') . ' ' . $_SESSION['Contract'.$identifier]->LocCode . "',
													'1')");
			$WorkCentre = $_SESSION['Contract'.$identifier]->LocCode;
		}
		/*The above is a bit of a hack to get a default workcentre for a location based on the users default location*/
	}

	$SQL = "SELECT contractref,
					debtorno,
					branchcode,
					categoryid,
					loccode,
					requireddate,
					margin,
					customerref,
					exrate,
					status
			FROM contracts
			WHERE contractref='" . $_POST['ContractRef'] . "'";

	$Result = DB_query($SQL);
	if (DB_num_rows($Result)==1){ // then we have an existing contract with this contractref
		$ExistingContract = DB_fetch_array($Result);
		if ($ExistingContract['debtorno'] !=  $_SESSION['Contract'.$identifier]->DebtorNo){
			prnMsg(__('The contract reference cannot be the same as a previously created contract for another customer. Please modify the contract reference before continuing'),'error');
			$InputError=true;
		}

		if ($ExistingContract['status']<=1 AND ! $InputError) {
			//then we can accept any changes at all do an update on the whole lot
			$SQL = "UPDATE contracts SET categoryid = '" . $_POST['CategoryID'] ."',
										requireddate = '" . FormatDateForSQL($_POST['RequiredDate']) . "',
										loccode='" . $_POST['LocCode'] . "',
										margin = '" . filter_number_format($_POST['Margin']) . "',
										customerref = '" . $_POST['CustomerRef'] . "',
										exrate = '" . filter_number_format($_POST['ExRate']) . "'
							WHERE contractref ='" . $_POST['ContractRef'] . "'";
			$ErrMsg = __('Cannot update the contract because');
			$Result = DB_query($SQL, $ErrMsg);
			/* also need to update the items on the contract BOM  - delete the existing contract BOM then add these items*/
			$Result = DB_query("DELETE FROM contractbom WHERE contractref='" .$_POST['ContractRef'] . "'");
			$ErrMsg = __('Could not add a component to the contract bill of material');
			foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $Component){
				$SQL = "INSERT INTO contractbom (contractref,
												stockid,
												workcentreadded,
												quantity)
											VALUES ( '" . $_POST['ContractRef'] . "',
												'" . $Component->StockID . "',
												'" . $WorkCentre . "',
												'" . $Component->Quantity . "')";
				$Result = DB_query($SQL, $ErrMsg);
			}

			/*also need to update the items on the contract requirements  - delete the existing database entries then add these */
			$Result = DB_query("DELETE FROM contractreqts WHERE contractref='" .$_POST['ContractRef'] . "'");
			$ErrMsg = __('Could not add a requirement to the contract requirements');
			foreach ($_SESSION['Contract'.$identifier]->ContractReqts as $Requirement){
				$SQL = "INSERT INTO contractreqts (contractref,
													requirement,
													costperunit,
													quantity)
												VALUES (
													'" . $_POST['ContractRef'] . "',
													'" . $Requirement->Requirement . "',
													'" . $Requirement->CostPerUnit . "',
													'" . $Requirement->Quantity . "')";
				$Result = DB_query($SQL, $ErrMsg);
			}

			prnMsg(__('The changes to the contract have been committed to the database'),'success');
		}
		if ($ExistingContract['status']==1 AND ! $InputError){
			//then the quotation will need to be updated with the revised contract cost if necessary
			$ContractBOMCost =0;
			foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $Component) {
				$ContractBOMCost += ($Component->ItemCost *  $Component->Quantity);
			}
			$ContractReqtsCost=0;
			foreach ($_SESSION['Contract'.$identifier]->ContractReqts as $Requirement) {
				$ContractReqtsCost += ($Requirement->CostPerUnit * $Requirement->Quantity);
			}
			$ContractCost = $ContractReqtsCost+$ContractBOMCost;
			$ContractPrice = ($ContractBOMCost+$ContractReqtsCost)/((100-$_SESSION['Contract'.$identifier]->Margin)/100);

			$SQL = "UPDATE stockmaster SET description='" . $_SESSION['Contract'.$identifier]->ContractDescription . "',
											longdescription='" . $_SESSION['Contract'.$identifier]->ContractDescription . "',
											categoryid = '" . $_SESSION['Contract'.$identifier]->CategoryID . "',
											materialcost= '" . $ContractCost . "'
										WHERE stockid ='" . $_SESSION['Contract'.$identifier]->ContractRef."'";
			$ErrMsg =  __('The contract item could not be updated because');
			$InsertNewItemResult = DB_query($SQL, $ErrMsg);

			//update the quotation
			$SQL = "UPDATE salesorderdetails
						SET unitprice = '" . $ContractPrice* $_SESSION['Contract'.$identifier]->ExRate . "'
						WHERE stkcode='" .  $_SESSION['Contract'.$identifier]->ContractRef . "'
						AND orderno='" .  $_SESSION['Contract'.$identifier]->OrderNo . "'";
			$ErrMsg = __('The contract quotation could not be updated because');
			$UpdQuoteResult = DB_query($SQL, $ErrMsg);
			prnMsg(__('The contract quotation has been updated based on the new contract cost and margin'),'success');
			echo '<br /><a href="' .$RootPath . '/SelectSalesOrder.php?OrderNumber=' .  $_SESSION['Contract'.$identifier]->OrderNo . '&amp;Quotations=Quotes_Only">' . __('Go to Quotation') . ' ' .  $_SESSION['Contract'.$identifier]->OrderNo . '</a>';

		}
		if ($ExistingContract['status'] == 0 AND $_POST['Status']==1){
			/*we are updating the status on the contract to a quotation so we need to
			 * add a new item for the contract into the stockmaster
			 * add a salesorder header and detail as a quotation for the item
			 */


		}
	} elseif (!$InputError) { /*Its a new contract - so insert */

		$SQL = "INSERT INTO contracts ( contractref,
										debtorno,
										branchcode,
										contractdescription,
										categoryid,
										loccode,
										requireddate,
										margin,
										customerref,
										exrate)
					VALUES ('" . $_POST['ContractRef'] . "',
							'" . $_SESSION['Contract'.$identifier]->DebtorNo  . "',
							'" . $_SESSION['Contract'.$identifier]->BranchCode . "',
							'" . $_POST['ContractDescription'] . "',
							'" . $_POST['CategoryID'] . "',
							'" . $_POST['LocCode'] . "',
							'" . FormatDateForSQL($_POST['RequiredDate']) . "',
							'" . filter_number_format($_POST['Margin']) . "',
							'" . $_POST['CustomerRef'] . "',
							'". filter_number_format($_POST['ExRate']) ."')";

		$ErrMsg = __('The new contract could not be added because');
		$Result = DB_query($SQL, $ErrMsg);

		/*Also need to add the reqts and contracbom*/
		$ErrMsg = __('Could not add a component to the contract bill of material');
		foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $Component){
			$SQL = "INSERT INTO contractbom (contractref,
											stockid,
											workcentreadded,
											quantity)
							VALUES ('" . $_POST['ContractRef'] . "',
									'" . $Component->StockID . "',
									'" . $WorkCentre . "',
									'" . $Component->Quantity . "')";
			$Result = DB_query($SQL, $ErrMsg);
		}

		$ErrMsg = __('Could not add a requirement to the contract requirements');
		foreach ($_SESSION['Contract'.$identifier]->ContractReqts as $Requirement){
			$SQL = "INSERT INTO contractreqts (contractref,
												requirement,
												costperunit,
												quantity)
							VALUES ( '" . $_POST['ContractRef'] . "',
									'" . $Requirement->Requirement . "',
									'" . $Requirement->CostPerUnit . "',
									'" . $Requirement->Quantity . "')";
			$Result = DB_query($SQL, $ErrMsg);
		}
		prnMsg(__('The new contract has been added to the database'),'success');

	} //end of adding a new contract
}//end of commital to database

if (isset($_POST['CreateQuotation']) AND !$InputError){
//Create a quotation for the contract as entered
//First need to create the item in stockmaster

//calculate the item's contract cost
	$ContractBOMCost =0;
	foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $Component) {
		$ContractBOMCost += ($Component->ItemCost *  $Component->Quantity);
	}
	$ContractReqtsCost=0;
	foreach ($_SESSION['Contract'.$identifier]->ContractReqts as $Requirement) {
		$ContractReqtsCost += ($Requirement->CostPerUnit * $Requirement->Quantity);
	}
	$ContractCost = $ContractReqtsCost+$ContractBOMCost;
	$ContractPrice = ($ContractBOMCost+$ContractReqtsCost)/((100-$_SESSION['Contract'.$identifier]->Margin)/100);

//Check if the item exists already
	$SQL = "SELECT stockid FROM stockmaster WHERE stockid='" . $_SESSION['Contract'.$identifier]->ContractRef."'";
	$ErrMsg =  __('The item could not be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($Result)==0) { //then the item doesn't currently exist so add it

		$SQL = "INSERT INTO stockmaster (stockid,
										description,
										longdescription,
										categoryid,
										mbflag,
										taxcatid,
										materialcost)
							VALUES ('" . $_SESSION['Contract'.$identifier]->ContractRef."',
									'" . $_SESSION['Contract'.$identifier]->ContractDescription . "',
									'" . $_SESSION['Contract'.$identifier]->ContractDescription . "',
									'" . $_SESSION['Contract'.$identifier]->CategoryID . "',
									'M',
									'" . $_SESSION['DefaultTaxCategory'] . "',
									'" . $ContractCost . "')";
		$ErrMsg =  __('The new contract item could not be added because');
		$InsertNewItemResult = DB_query($SQL, $ErrMsg);
		$SQL = "INSERT INTO locstock (loccode,
										stockid)
						SELECT locations.loccode,
								'" . $_SESSION['Contract'.$identifier]->ContractRef . "'
						FROM locations";

		$ErrMsg =  __('The locations for the item') . ' ' . $_SESSION['Contract'.$identifier]->ContractRef . ' ' . __('could not be added because');
		$InsLocnsResult = DB_query($SQL, $ErrMsg);
	}
	//now add the quotation for the item

	//first need to get some more details from the customer/branch record
	$SQL = "SELECT debtorsmaster.salestype,
					custbranch.defaultshipvia,
					custbranch.brname,
					custbranch.braddress1,
					custbranch.braddress2,
					custbranch.braddress3,
					custbranch.braddress4,
					custbranch.braddress5,
					custbranch.braddress6,
					custbranch.phoneno,
					custbranch.email,
					custbranch.defaultlocation
				FROM debtorsmaster INNER JOIN custbranch
				ON debtorsmaster.debtorno=custbranch.debtorno
				WHERE debtorsmaster.debtorno='" . $_SESSION['Contract'.$identifier]->DebtorNo  . "'
				AND custbranch.branchcode='" . $_SESSION['Contract'.$identifier]->BranchCode . "'";
	$ErrMsg =  __('The customer and branch details could not be retrieved because');
	$CustomerDetailsResult = DB_query($SQL, $ErrMsg);

	$CustomerDetailsRow = DB_fetch_array($CustomerDetailsResult);

	//start a DB transaction
	DB_Txn_Begin();
	$OrderNo = GetNextTransNo(30);
	$HeaderSQL = "INSERT INTO salesorders (	orderno,
											debtorno,
											branchcode,
											customerref,
											orddate,
											ordertype,
											shipvia,
											deliverto,
											deladd1,
											deladd2,
											deladd3,
											deladd4,
											deladd5,
											deladd6,
											contactphone,
											contactemail,
											fromstkloc,
											deliverydate,
											quotedate,
											quotation)
										VALUES (
											'". $OrderNo . "',
											'" . $_SESSION['Contract'.$identifier]->DebtorNo  . "',
											'" . $_SESSION['Contract'.$identifier]->BranchCode . "',
											'". $_SESSION['Contract'.$identifier]->CustomerRef ."',
											'" . date('Y-m-d H:i') . "',
											'" . $CustomerDetailsRow['salestype'] . "',
											'" . $CustomerDetailsRow['defaultshipvia'] ."',
											'". $CustomerDetailsRow['brname'] . "',
											'" . $CustomerDetailsRow['braddress1'] . "',
											'" . $CustomerDetailsRow['braddress2'] . "',
											'" . $CustomerDetailsRow['braddress3'] . "',
											'" . $CustomerDetailsRow['braddress4'] . "',
											'" . $CustomerDetailsRow['braddress5'] . "',
											'" . $CustomerDetailsRow['braddress6'] . "',
											'" . $CustomerDetailsRow['phoneno'] . "',
											'" . $CustomerDetailsRow['email'] . "',
											'" . $_SESSION['Contract'.$identifier]->LocCode ."',
											'" . FormatDateForSQL($_SESSION['Contract'.$identifier]->RequiredDate) . "',
											CURRENT_DATE,
											'1' )";

	$ErrMsg = __('The quotation cannot be added because');
	$InsertQryResult = DB_query($HeaderSQL, $ErrMsg,true);
	$LineItemSQL = "INSERT INTO salesorderdetails ( orderlineno,
													orderno,
													stkcode,
													unitprice,
													quantity,
													poline,
													itemdue)
										VALUES ('0',
												'" . $OrderNo . "',
												'" . $_SESSION['Contract'.$identifier]->ContractRef . "',
												'" . ($ContractPrice * $_SESSION['Contract'.$identifier]->ExRate) . "',
												'1',
												'" . $_SESSION['Contract'.$identifier]->CustomerRef . "',
												'" . FormatDateForSQL($_SESSION['Contract'.$identifier]->RequiredDate) . "')";
	$ErrMsg = __('Unable to add the quotation line');
	$Ins_LineItemResult = DB_query($LineItemSQL, $ErrMsg, '', true);
	 //end of adding the quotation to salesorders/details

	//make the status of the contract 1 - to indicate that it is now quoted
	$SQL = "UPDATE contracts SET orderno='" . $OrderNo . "',
								status='" . 1 . "'
						WHERE contractref='" . DB_escape_string($_SESSION['Contract'.$identifier]->ContractRef) . "'";
	$ErrMsg = __('Unable to update the contract status and order number because');
	$UpdContractResult = DB_query($SQL, $ErrMsg, '', true);
	DB_Txn_Commit();
	$_SESSION['Contract'.$identifier]->Status=1;
	$_SESSION['Contract'.$identifier]->OrderNo=$OrderNo;
	prnMsg(__('The contract has been made into quotation number') . ' ' . $OrderNo,'info');
	echo '<br /><a href="' . $RootPath . '/SelectSalesOrder.php?OrderNumber=' . $OrderNo . '&amp;Quotations=Quotes_Only">' . __('Go to quotation number:') . ' ' . $OrderNo . '</a>';

} //end of if making a quotation

if (isset($_POST['SearchCustomers'])){

	if (($_POST['CustKeywords']!= '') AND (($_POST['CustCode']!= '') OR ($_POST['CustPhone']!= ''))) {
		prnMsg( __('Customer Branch Name keywords have been used in preference to the Customer Branch Code or Branch Phone Number entered'), 'warn');
	}
	if (($_POST['CustCode']!= '') AND ($_POST['CustPhone']!= '')) {
		prnMsg(__('Customer Branch Code has been used in preference to the Customer Branch Phone Number entered'), 'warn');
	}
	if (mb_strlen($_POST['CustKeywords'])>0) {
	//insert wildcard characters in spaces
		$_POST['CustKeywords'] = mb_strtoupper(trim($_POST['CustKeywords']));
		$SearchString = '%' . str_replace(' ', '%', $_POST['CustKeywords']) . '%';

		$SQL = "SELECT custbranch.brname,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.branchcode,
						custbranch.debtorno,
						debtorsmaster.name
					FROM custbranch
					LEFT JOIN debtorsmaster
						ON custbranch.debtorno=debtorsmaster.debtorno
					WHERE custbranch.brname " . LIKE . " '$SearchString'
						AND custbranch.disabletrans=0
					ORDER BY custbranch.debtorno, custbranch.branchcode";

	} elseif (mb_strlen($_POST['CustCode'])>0){

		$_POST['CustCode'] = mb_strtoupper(trim($_POST['CustCode']));

		$SQL = "SELECT custbranch.brname,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.branchcode,
						custbranch.debtorno,
						debtorsmaster.name
					FROM custbranch
					LEFT JOIN debtorsmaster
						ON custbranch.debtorno=debtorsmaster.debtorno
					WHERE custbranch.branchcode " . LIKE . " '%" . $_POST['CustCode'] . "%'
						AND custbranch.disabletrans=0
					ORDER BY custbranch.debtorno";

	} elseif (mb_strlen($_POST['CustPhone'])>0){
		$SQL = "SELECT custbranch.brname,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.branchcode,
						custbranch.debtorno,
						debtorsmaster.name
					FROM custbranch
					LEFT JOIN debtorsmaster
						ON custbranch.debtorno=debtorsmaster.debtorno
					WHERE custbranch.phoneno " . LIKE . " '%" . $_POST['CustPhone'] . "%'
						AND custbranch.disabletrans=0
					ORDER BY custbranch.debtorno";
	} else {
		$SQL = "SELECT custbranch.brname,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.branchcode,
						custbranch.debtorno,
						debtorsmaster.name
					FROM custbranch
					LEFT JOIN debtorsmaster
						ON custbranch.debtorno=debtorsmaster.debtorno
					WHERE custbranch.disabletrans=0
					ORDER BY custbranch.debtorno";
	}

	$ErrMsg = __('The searched customer records requested cannot be retrieved because');
	$Result_CustSelect = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result_CustSelect)==0){
		prnMsg(__('No Customer Branch records contain the search criteria') . ' - ' . __('please try again') . ' - ' . __('Note a Customer Branch Name may be different to the Customer Name'),'info');
	}
} /*one of keywords or custcode was more than a zero length string */

if (isset($_POST['SelectedCustomer'])) {

/* will only be true if page called from customer selection form
 * or set because only one customer record returned from a search
 * so parse the $Select string into debtorno and branch code */


	$_SESSION['Contract'.$identifier]->DebtorNo  = $_POST['SelectedCustomer'];
	$_SESSION['Contract'.$identifier]->BranchCode = $_POST['SelectedBranch'];

	$SQL = "SELECT debtorsmaster.name,
					custbranch.brname,
					debtorsmaster.currcode,
					debtorsmaster.holdreason,
					holdreasons.dissallowinvoices,
					currencies.rate
			FROM debtorsmaster INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			INNER JOIN custbranch
			ON debtorsmaster.debtorno=custbranch.debtorno
			INNER JOIN holdreasons
			ON debtorsmaster.holdreason=holdreasons.reasoncode
			WHERE debtorsmaster.debtorno='" . $_SESSION['Contract'.$identifier]->DebtorNo  . "'
			AND custbranch.branchcode='" . $_SESSION['Contract'.$identifier]->BranchCode . "'" ;

	$ErrMsg = __('The customer record selected') . ': ' . $_SESSION['Contract'.$identifier]->DebtorNo . ' ' . __('cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow = DB_fetch_array($Result);
	if (DB_num_rows($Result)==0){
		prnMsg(__('The customer details were unable to be retrieved'),'error');
	} else {
		$_SESSION['Contract'.$identifier]->BranchName = $MyRow['brname'];
		$_SESSION['RequireCustomerSelection'] = 0;
		$_SESSION['Contract'.$identifier]->CustomerName = $MyRow['name'];
		$_SESSION['Contract'.$identifier]->CurrCode = $MyRow['currcode'];
		$_SESSION['Contract'.$identifier]->ExRate = $MyRow['rate'];

		if ($_SESSION['CheckCreditLimits'] > 0){  /*Check credit limits is 1 for warn and 2 for prohibit contracts */
			$CreditAvailable = GetCreditAvailable($_SESSION['Contract'.$identifier]->DebtorNo);
			if ($_SESSION['CheckCreditLimits']==1 AND $CreditAvailable <=0){
				prnMsg(__('The') . ' ' . $_SESSION['Contract'.$identifier]->CustomerName . ' ' . __('account is currently at or over their credit limit'),'warn');
			} elseif ($_SESSION['CheckCreditLimits']==2 AND $CreditAvailable <=0){
				prnMsg(__('No more orders can be placed by') . ' ' . $MyRow[0] . ' ' . __(' their account is currently at or over their credit limit'),'warn');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
		}
	} //a customer was retrieved ok
} //end if a customer has just been selected


if (!isset($_SESSION['Contract'.$identifier]->DebtorNo)
		OR $_SESSION['Contract'.$identifier]->DebtorNo=='' ) {

    echo '<div class="db-card" style="margin: 0 var(--space-6);">
            <div class="db-card-header">
                <div class="db-card-title"><i class="fas fa-user-tag"></i> ' . __('Stage 1: Select Customer') . '</div>
            </div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" name="CustomerSelection" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div class="db-field-group">
                    <div class="db-field">
                        <label>' . __('Customer Branch Name') . '</label>
                        <input type="text" name="CustKeywords" autofocus="autofocus" placeholder="' . __('Enter keywords...') . '" />
                    </div>
                    <div class="db-field">
                        <label>' . __('Branch Code') . '</label>
                        <input type="text" name="CustCode" placeholder="' . __('e.g. CUST001') . '" />
                    </div>
                    <div class="db-field">
                        <label>' . __('Branch Phone') . '</label>
                        <input type="text" name="CustPhone" placeholder="' . __('e.g. 0123456') . '" />
                    </div>
                </div>

                <div class="db-action-btn-row" style="margin-top: 20px; justify-content: flex-end;">
                    <button type="submit" name="SearchCustomers" class="db-btn db-btn-primary">
                        <i class="fas fa-search"></i> ' . __('Search Customers') . '
                    </button>
                </div>
                </form>
            </div>
          </div>';

	if (isset($Result_CustSelect)) {
		echo '<div class="db-card" style="margin: 0 var(--space-6);">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-users"></i> ' . __('Customer Search Results') . '</div>
                </div>
                <div class="db-card-body p-0">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="db-table-wrapper">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>' . __('Customer') . '</th>
                                    <th>' . __('Select Branch') . '</th>
                                    <th>' . __('Contact') . '</th>
                                    <th>' . __('Phone') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

		$j = 1;
		$LastCustomer='';
		while ($MyRow=DB_fetch_array($Result_CustSelect)) {
			echo '<tr>
					<td><div class="db-font-bold text-primary">' . ($LastCustomer != $MyRow['name'] ? $MyRow['name'] : '') . '</div></td>
					<td>
                        <button type="submit" name="Submit' . $j . '" class="db-btn db-btn-outline-primary" style="padding: 4px 12px; min-width: 140px;">
                            ' . $MyRow['brname'] . '
                        </button>
                        <input type="hidden" name="SelectedCustomer' . $j . '" value="' . $MyRow['debtorno'] . '" />
                        <input type="hidden" name="SelectedBranch' . $j . '" value="' . $MyRow['branchcode'] . '" />
                    </td>
					<td>' . $MyRow['contactname'] . '</td>
					<td>' . $MyRow['phoneno'] . '</td>
				</tr>';
			$LastCustomer = $MyRow['name'];
			$j++;
		}
		echo '      </tbody>
                        </table>
                    </div>
                    </form>
                </div>
              </div>';
	}

//end if RequireCustomerSelection
} else { /* A customer is already selected so show the Contract Header Entry Stage */

	$ContractTitle = $_SESSION['ExistingContract'] == 0 ? __('New Contract Entry') : __('Modify Contract') . ': ' . $_SESSION['Contract'.$identifier]->ContractRef;
    
    $stCfg = [0 => 'secondary', 1 => 'info', 2 => 'success'];
    $stLabels = [0 => __('Draft'), 1 => __('Quoted'), 2 => __('Ordered')];
    $badgeColor = $stCfg[$_SESSION['Contract'.$identifier]->Status] ?? 'secondary';
    $badgeLabel = $stLabels[$_SESSION['Contract'.$identifier]->Status] ?? __('Unknown');

    echo '<div class="db-card" style="margin: 0 var(--space-6);">
            <div class="db-card-header">
                <div class="db-card-title">
                    <i class="fas fa-file-signature"></i> ' . $ContractTitle . '
                    <span class="db-badge db-badge-' . $badgeColor . '" style="margin-left: 10px;">' . $badgeLabel . '</span>
                </div>
            </div>
            <div class="db-card-body">
                <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '" method="post" enctype="multipart/form-data">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                
                <div class="db-grid-2">
                    <div class="db-section">
                        <h4 class="db-section-title"><i class="fas fa-info-circle"></i> ' . __('Contract Identity') . '</h4>
                        
                        <div class="db-field" style="margin-bottom: 20px;">
                            <label>' . __('Customer') . '</label>
                            <div class="db-font-bold text-primary" style="background: var(--surface-alt); padding: 10px; border-radius: var(--radius-sm);">
                                ' . $_SESSION['Contract'.$identifier]->DebtorNo . ' - ' . $_SESSION['Contract'.$identifier]->CustomerName . '<br/>
                                <small class="text-muted">' . __('Branch') . ': ' . $_SESSION['Contract'.$identifier]->BranchName . '</small>
                            </div>
                        </div>

                        <div class="db-field">
                            <label>' . __('Contract Reference') . '</label>
                            <input type="text" name="ContractRef" value="' . $_SESSION['Contract'.$identifier]->ContractRef . '" ' . ($_SESSION['ExistingContract'] == 0 ? 'autofocus' : 'readonly') . ' placeholder="' . __('min 5 chars') . '" />
                        </div>
                        <div class="db-field">
                            <label>' . __('Contract Description') . '</label>
                            <textarea name="ContractDescription" rows="4" placeholder="' . __('Detailed service/item description...') . '">' . $_SESSION['Contract'.$identifier]->ContractDescription . '</textarea>
                        </div>
                    </div>

                    <div class="db-section">
                        <h4 class="db-section-title"><i class="fas fa-cog"></i> ' . __('Operational Details') . '</h4>
                        <div class="db-field text-right" style="margin-bottom: 10px;">
                            <a href="' . $RootPath . '/StockCategories.php" target="_blank" class="db-font-sm text-primary"><i class="fas fa-external-link-alt"></i> ' . __('Manage Categories') . '</a>
                        </div>
                        <div class="db-field">
                            <label>' . __('Stock Category') . '</label>
                            <select name="CategoryID" class="db-select">';
                            $SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
                            $ResCat = DB_query($SQL);
                            while ($CRow = DB_fetch_array($ResCat)) {
                                $sel = ($CRow['categoryid'] == ($_SESSION['Contract'.$identifier]->CategoryID ?? '')) ? 'selected' : '';
                                echo '<option ' . $sel . ' value="' . $CRow['categoryid'] . '">' . $CRow['categorydescription'] . '</option>';
                            }
    echo '                  </select>
                        </div>
                        <div class="db-field">
                            <label>' . __('Warehouse (Location)') . '</label>
                            <select name="LocCode" class="db-select">';
                            $SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 ORDER BY locationname";
                            $ResLoc = DB_query($SQL);
                            while ($LRow = DB_fetch_array($ResLoc)) {
                                $sel = ($LRow['loccode'] == ($_SESSION['Contract'.$identifier]->LocCode ?? '')) ? 'selected' : '';
                                echo '<option ' . $sel . ' value="' . $LRow['loccode'] . '">' . $LRow['locationname'] . '</option>';
                            }
    echo '                  </select>
                        </div>
                        <div class="db-field">
                            <label>' . __('Completion Date') . '</label>
                            <input type="text" name="RequiredDate" class="db-input datepicker" value="' . $_SESSION['Contract'.$identifier]->RequiredDate . '" />
                        </div>
                    </div>
                </div>

                <div class="db-grid-3" style="margin-top: 20px;">
                    <div class="db-field">
                        <label>' . __('Margin (%)') . '</label>
                        <input type="text" name="Margin" class="number" value="' . locale_number_format($_SESSION['Contract'.$identifier]->Margin, 2) . '" />
                    </div>
                    <div class="db-field">
                        <label>' . __('Customer Reference') . '</label>
                        <input type="text" name="CustomerRef" value="' . $_SESSION['Contract'.$identifier]->CustomerRef . '" />
                    </div>
                    <div class="db-field">
                        <label>' . ($_SESSION['Contract'.$identifier]->CurrCode ?? 'USD') . ' ' . __('Exchange Rate') . '</label>
                        <input type="text" name="ExRate" class="number" value="' . locale_number_format($_SESSION['Contract'.$identifier]->ExRate, 'Variable') . '" />
                    </div>
                </div>';

                if ($_SESSION['ExistingContract'] != 0) {
    echo '      <div class="db-field" style="margin-top: 20px;">
                    <label>' . __('Contract Status') . '</label>
                    <select name="Status" class="db-select">';
                    if ($_SESSION['Contract'.$identifier]->Status == 0) {
                        echo '<option selected value="0">' . __('Draft') . '</option><option value="1">' . __('Quoted') . '</option>';
                    } elseif ($_SESSION['Contract'.$identifier]->Status == 1) {
                        echo '<option value="0">' . __('Draft') . '</option><option selected value="1">' . __('Quoted') . '</option><option value="2">' . __('Ordered') . '</option>';
                    } else {
                        echo '<option value="1">' . __('Quoted') . '</option><option selected value="2">' . __('Ordered') . '</option>';
                    }
    echo '          </select>
                </div>';
                }

    echo '      <div class="db-field" style="margin-top: 20px;">
                    <label><i class="fas fa-file-upload"></i> ' . __('Drawing/Reference Image') . '</label>
                    <input type="file" name="Drawing" class="db-input" />
                    <p class="db-font-sm text-muted" style="margin-top: 5px;">' . __('Leave blank to keep current image.') . '</p>
                </div>

                <div class="db-action-btn-row" style="margin-top: 40px; border-top: 1px solid var(--border-color); padding-top: 20px; justify-content: flex-end;">
                    <button type="submit" name="CommitContract" class="db-btn db-btn-primary">
                        <i class="fas fa-save"></i> ' . ($_SESSION['ExistingContract'] == 0 ? __('Create Contract') : __('Update Header')) . '
                    </button>
                    <button type="submit" name="CreateQuotation" class="db-btn db-btn-outline-success">
                        <i class="fas fa-paper-plane"></i> ' . __('Generate Quotation') . '
                    </button>
                    <button type="submit" name="CancelContract" class="db-btn db-btn-outline-danger" onclick="return confirm(\'' . __('Confirm cancellation? This will delete components and requirements.') . '\')">
                        <i class="fas fa-times-circle"></i> ' . __('Cancel Contract') . '
                    </button>
                </div>
                </form>
            </div>
          </div>';

    // Summary Section: BOM & Requirements
    echo '<div class="db-grid-2" style="margin: 0 var(--space-6);">';
        
        // BOM Summary
        echo '<div class="db-card">
                <div class="db-card-header" style="justify-content: space-between;">
                    <div class="db-card-title"><i class="fas fa-cubes"></i> ' . __('Stock Items Required') . '</div>
                    <button type="submit" form="ActionForm" name="EnterContractBOM" class="db-btn db-btn-outline-primary db-btn-sm"><i class="fas fa-edit"></i> ' . __('Edit') . '</button>
                </div>
                <div class="db-card-body p-0">
                    <div class="db-table-wrapper">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>' . __('Item') . '</th>
                                    <th class="text-right">' . __('Qty') . '</th>
                                    <th class="text-right">' . __('Total Cost') . '</th>
                                </tr>
                            </thead>
                            <tbody>';
                            $ContractBOMCost = 0;
                            if (count($_SESSION['Contract'.$identifier]->ContractBOM) > 0) {
                                foreach ($_SESSION['Contract'.$identifier]->ContractBOM as $Component) {
                                    $cost = $Component->ItemCost * $Component->Quantity;
                                    echo '<tr>
                                            <td><div class="db-font-bold text-primary">' . $Component->StockID . '</div><small class="text-muted">' . $Component->ItemDescription . '</small></td>
                                            <td class="text-right">' . locale_number_format($Component->Quantity, $Component->DecimalPlaces) . '</td>
                                            <td class="text-right">' . locale_number_format($cost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                          </tr>';
                                    $ContractBOMCost += $cost;
                                }
                                echo '<tfoot><tr><th colspan="2">' . __('Total Stock Cost') . '</th><th class="text-right">' . locale_number_format($ContractBOMCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</th></tr></tfoot>';
                            } else {
                                echo '<tr><td colspan="3" class="text-center p-10 text-muted"><i>' . __('No items entered') . '</i></td></tr>';
                            }
        echo '      </tbody>
                        </table>
                    </div>
                </div>
              </div>';

        // Requirements Summary
        echo '<div class="db-card">
                <div class="db-card-header" style="justify-content: space-between;">
                    <div class="db-card-title"><i class="fas fa-tasks"></i> ' . __('Other Requirements') . '</div>
                    <button type="submit" form="ActionForm" name="EnterContractRequirements" class="db-btn db-btn-outline-primary db-btn-sm"><i class="fas fa-edit"></i> ' . __('Edit') . '</button>
                </div>
                <div class="db-card-body p-0">
                    <div class="db-table-wrapper">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>' . __('Requirement') . '</th>
                                    <th class="text-right">' . __('Total Cost') . '</th>
                                </tr>
                            </thead>
                            <tbody>';
                            $ContractReqtsCost = 0;
                            if (count($_SESSION['Contract'.$identifier]->ContractReqts) > 0) {
                                foreach ($_SESSION['Contract'.$identifier]->ContractReqts as $Requirement) {
                                    $cost = $Requirement->CostPerUnit * $Requirement->Quantity;
                                    echo '<tr>
                                            <td><div class="db-font-bold text-primary">' . $Requirement->Requirement . '</div></td>
                                            <td class="text-right">' . locale_number_format($cost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
                                          </tr>';
                                    $ContractReqtsCost += $cost;
                                }
                                echo '<tfoot><tr><th>' . __('Total Other Cost') . '</th><th class="text-right">' . locale_number_format($ContractReqtsCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</th></tr></tfoot>';
                            } else {
                                echo '<tr><td colspan="2" class="text-center p-10 text-muted"><i>' . __('No requirements entered') . '</i></td></tr>';
                            }
        echo '      </tbody>
                        </table>
                    </div>
                </div>
              </div>
          </div>';

    // Summary Banner
    echo '<div class="db-card" style="margin: 0 var(--space-6); background: var(--surface-alt); border-left: 4px solid var(--primary);">
            <div class="db-card-body">
                <div class="db-grid-2">
                    <div class="text-center">
                        <div class="text-muted" style="margin-bottom: 5px;">' . __('Total Contract Cost') . '</div>
                        <h2 class="text-primary">' . locale_number_format(($ContractBOMCost + $ContractReqtsCost), $_SESSION['CompanyRecord']['decimalplaces']) . '</h2>
                    </div>
                    <div class="text-center">
                        <div class="text-muted" style="margin-bottom: 5px;">' . __('Contract Price (Est.)') . '</div>
                        <h2 class="text-success">' . locale_number_format(($ContractBOMCost + $ContractReqtsCost) / ((100 - $_SESSION['Contract'.$identifier]->Margin) / 100), $_SESSION['CompanyRecord']['decimalplaces']) . '</h2>
                    </div>
                </div>
            </div>
          </div>';

    // Invisible action form for the summary cards
    echo '<form id="ActionForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="ContractRef" value="' . $_SESSION['Contract'.$identifier]->ContractRef . '" />
            <input type="hidden" name="ContractDescription" value="' . $_SESSION['Contract'.$identifier]->ContractDescription . '" />
            <input type="hidden" name="CategoryID" value="' . $_SESSION['Contract'.$identifier]->CategoryID . '" />
            <input type="hidden" name="LocCode" value="' . $_SESSION['Contract'.$identifier]->LocCode . '" />
            <input type="hidden" name="RequiredDate" value="' . $_SESSION['Contract'.$identifier]->RequiredDate . '" />
            <input type="hidden" name="Margin" value="' . $_SESSION['Contract'.$identifier]->Margin . '" />
            <input type="hidden" name="CustomerRef" value="' . $_SESSION['Contract'.$identifier]->CustomerRef . '" />
            <input type="hidden" name="ExRate" value="' . $_SESSION['Contract'.$identifier]->ExRate . '" />
          </form>';

} // End Stage switcher

echo '</div> <!-- End MainBody vertical stack -->
    </div> <!-- End dashboard-shell-container -->';

include(__DIR__ . '/includes/footer.php');
