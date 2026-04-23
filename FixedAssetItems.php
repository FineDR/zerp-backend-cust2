<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fixed Assets');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetItems';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-cube"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . (isset($AssetID) && $AssetID != '' ? __('Modify and manage details for asset') . ': ' . $AssetID : __('Create a new record in the fixed asset register')) . '</div>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectAsset.php" class="db-btn db-btn-secondary">
					<i class="fas fa-search"></i> ' . __('Search Assets') . '
				</a>
				<button type="button" onclick="document.getElementById(\'AssetForm\').elements[\'submit\'].click();" class="db-btn db-btn-primary">
					<i class="fas fa-save"></i> ' . (isset($AssetID) && $AssetID != '' ? __('Update Asset') : __('Save Asset')) . '
				</button>
			</div>
		</div>';

echo '<form id="AssetForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<div class="db-centered-container" style="max-width: 900px; margin: 0 auto; padding: 20px;">
		<div class="db-main-content">';

/* If this form is called with the AssetID then it is assumed that the asset is to be modified  */
if (isset($_GET['AssetID'])){
	$AssetID =$_GET['AssetID'];
} elseif (isset($_POST['AssetID'])){
	$AssetID =$_POST['AssetID'];
} elseif (isset($_POST['Select'])){
	$AssetID =$_POST['Select'];
} else {
	$AssetID = '';
}

$SupportedImgExt = array('png','jpg','jpeg');

if (isset($_FILES['ItemPicture']) AND $_FILES['ItemPicture']['name'] !='') {
	$ImgExt = pathinfo($_FILES['ItemPicture']['name'], PATHINFO_EXTENSION);

	$Result    = $_FILES['ItemPicture']['error'];
 	$UploadTheFile = 'Yes'; //Assume all is well to start off with
	$FileName = $_SESSION['part_pics_dir'] . '/ASSET_' . $AssetID . '.' . $ImgExt;
	//But check for the worst
	if (!in_array ($ImgExt, $SupportedImgExt)) {
		prnMsg(__('Only ' . implode(", ", $SupportedImgExt) . ' files are supported - a file extension of ' . implode(", ", $SupportedImgExt) . ' is expected'),'warn');
		$UploadTheFile ='No';
	} elseif ( $_FILES['ItemPicture']['size'] > ($_SESSION['MaxImageSize']*1024)) { //File Size Check
		prnMsg(__('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $_SESSION['MaxImageSize'],'warn');
		$UploadTheFile ='No';
	} elseif ( $_FILES['ItemPicture']['type'] == 'text/plain' ) {  //File Type Check
		prnMsg( __('Only graphics files can be uploaded'),'warn');
		 	$UploadTheFile ='No';
	}
	foreach ($SupportedImgExt as $Ext) {
		$File = $_SESSION['part_pics_dir'] . '/ASSET_' . $AssetID . '.' . $Ext;
		if (file_exists ($File) ) {
			$Result = unlink($File);
			if (!$Result){
				prnMsg(__('The existing image could not be removed'),'error');
				$UploadTheFile ='No';
			}
		}
	}

	if ($UploadTheFile=='Yes'){
		$Result  =  move_uploaded_file($_FILES['ItemPicture']['tmp_name'], $FileName);
		$Message = ($Result)?__('File url')  . '<a href="' . $FileName .'">' .  $FileName . '</a>' : __('Something is wrong with uploading a file');
	}
 /* EOR Add Image upload for New Item  - by Ori */
}

$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i=1;


	if (!isset($_POST['Description']) or mb_strlen($_POST['Description']) > 50 OR mb_strlen($_POST['Description'])==0) {
		$InputError = 1;
		prnMsg(__('The asset description must be entered and be fifty characters or less long. It cannot be a zero length string either, a description is required'),'error');
		$Errors[$i] = 'Description';
		$i++;
	}
	if (mb_strlen($_POST['LongDescription'])==0) {
		$InputError = 1;
		prnMsg(__('The asset long description cannot be a zero length string, a long description is required'),'error');
		$Errors[$i] = 'LongDescription';
		$i++;
	}

	if (mb_strlen($_POST['BarCode']) >20) {
		$InputError = 1;
		prnMsg(__('The barcode must be 20 characters or less long'),'error');
		$Errors[$i] = 'BarCode';
		$i++;
	}

	if (trim($_POST['AssetCategoryID'])==''){
		$InputError = 1;
		prnMsg(__('There are no asset categories defined. All assets must belong to a valid category,'),'error');
		$Errors[$i] = 'AssetCategoryID';
		$i++;
	}
	if (trim($_POST['AssetLocation'])==''){
		$InputError = 1;
		prnMsg(__('There are no asset locations defined. All assets must belong to a valid location,'),'error');
		$Errors[$i] = 'AssetLocation';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['DepnRate']))
		OR filter_number_format($_POST['DepnRate'])>100
		OR filter_number_format($_POST['DepnRate'])<0){

		$InputError = 1;
		prnMsg(__('The depreciation rate is expected to be a number between 0 and 100'),'error');
		$Errors[$i] = 'DepnRate';
		$i++;
	}
	if (filter_number_format($_POST['DepnRate'])>0 AND filter_number_format($_POST['DepnRate'])<1){
		prnMsg(__('Numbers less than 1 are interpreted as less than 1%. The depreciation rate should be entered as a number between 0 and 100'),'warn');
	}


	if ($InputError !=1){

		if ($_POST['submit']==__('Update')) { /*so its an existing one */

			/*Start a transaction to do the whole lot inside */
			DB_Txn_Begin();

			/*Need to check if changing the balance sheet codes - as will need to do journals for the cost and accum depn of the asset to the new category */
			$Result = DB_query("SELECT assetcategoryid,
										cost,
										accumdepn,
										costact,
										accumdepnact
								FROM fixedassets INNER JOIN fixedassetcategories
								ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
								WHERE assetid='" . $AssetID . "'");
			$OldDetails = DB_fetch_array($Result);
			if ($OldDetails['assetcategoryid'] !=$_POST['AssetCategoryID']  AND $OldDetails['cost']!=0){

				$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));
				/* Get the new account codes for the new asset category */
				$Result = DB_query("SELECT costact,
											accumdepnact
									FROM fixedassetcategories
									WHERE categoryid='" . $_POST['AssetCategoryID'] . "'");
				$NewAccounts = DB_fetch_array($Result);

				$TransNo = GetNextTransNo( 42 ); /* transaction type is asset category change */

				//credit cost for the old category
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
							VALUES ('42',
								'" . $TransNo . "',
								CURRENT_DATE,
								'" . $PeriodNo . "',
								'" . $OldDetails['costact'] . "',
								'" . mb_substr($AssetID . ' ' . __('change category') . ' ' . $OldDetails['assetcategoryid'] . ' - ' . $_POST['AssetCategoryID'], 0, 200) . "',
								'" . -$OldDetails['cost']. "'
								)";
				$ErrMsg = __('Cannot insert a GL entry for the change of asset category because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				//debit cost for the new category
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
							VALUES ('42',
								'" . $TransNo . "',
								CURRENT_DATE,
								'" . $PeriodNo . "',
								'" . $NewAccounts['costact'] . "',
								'" . mb_substr($AssetID . ' ' . __('change category') . ' ' . $OldDetails['assetcategoryid'] . ' - ' . $_POST['AssetCategoryID'], 0, 200) . "',
								'" . $OldDetails['cost']. "'
								)";
				$ErrMsg = __('Cannot insert a GL entry for the change of asset category because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				if ($OldDetails['accumdepn']!=0) {
					//debit accumdepn for the old category
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
								VALUES ('42',
									'" . $TransNo . "',
									CURRENT_DATE,
									'" . $PeriodNo . "',
									'" . $OldDetails['accumdepnact'] . "',
									'" . mb_substr($AssetID . ' ' . __('change category') . ' ' . $OldDetails['assetcategoryid'] . ' - ' . $_POST['AssetCategoryID'], 0, 200) . "',
									'" . $OldDetails['accumdepn']. "'
									)";
					$ErrMsg = __('Cannot insert a GL entry for the change of asset category because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					//credit accum depn for the new category
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
								VALUES ('42',
									'" . $TransNo . "',
									CURRENT_DATE,
									'" . $PeriodNo . "',
									'" . $NewAccounts['accumdepnact'] . "',
									'" . mb_substr($AssetID . ' ' . __('change category') . ' ' . $OldDetails['assetcategoryid'] . ' - ' . $_POST['AssetCategoryID'], 0, 200) . "',
									'" . -$OldDetails['accumdepn']. "'
									)";
					$ErrMsg = __('Cannot insert a GL entry for the change of asset category because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} /*end if there was accumulated depreciation for the asset */
			} /* end if there is a change in asset category */
			$SQL = "UPDATE fixedassets
					SET longdescription='" . $_POST['LongDescription'] . "',
						description='" . $_POST['Description'] . "',
						assetcategoryid='" . $_POST['AssetCategoryID'] . "',
						assetlocation='" . $_POST['AssetLocation'] . "',
						depntype='" . $_POST['DepnType'] . "',
						depnrate='" . filter_number_format($_POST['DepnRate']) . "',
						barcode='" . $_POST['BarCode'] . "',
						serialno='" . $_POST['SerialNo'] . "'
					WHERE assetid='" . $AssetID . "'";

			$ErrMsg = __('The asset could not be updated because');
			$Result = DB_query($SQL, $ErrMsg);

			prnMsg( __('Asset') . ' ' . $AssetID . ' ' . __('has been updated'), 'success');
			echo '<br />';
		} else { //it is a NEW part
			$SQL = "INSERT INTO fixedassets (description,
											longdescription,
											assetcategoryid,
											assetlocation,
											depntype,
											depnrate,
											barcode,
											serialno)
						VALUES (
							'" . $_POST['Description'] . "',
							'" . $_POST['LongDescription'] . "',
							'" . $_POST['AssetCategoryID'] . "',
							'" . $_POST['AssetLocation'] . "',
							'" . $_POST['DepnType'] . "',
							'" . filter_number_format($_POST['DepnRate']). "',
							'" . $_POST['BarCode'] . "',
							'" . $_POST['SerialNo'] . "' )";
			$ErrMsg =  __('The asset could not be added because');
			$Result = DB_query($SQL, $ErrMsg);

			if (DB_error_no() ==0) {
				$NewAssetID = DB_Last_Insert_ID('fixedassets', 'assetid');
				prnMsg( __('The new asset has been added to the database with an asset code of:') . ' ' . $NewAssetID,'success');
				unset($_POST['LongDescription']);
				unset($_POST['Description']);
				unset($_POST['BarCode']);
				unset($_POST['SerialNo']);
			}//ALL WORKED SO RESET THE FORM VARIABLES
			DB_Txn_Commit();
		}
	} else {
		echo '<br />' .  "\n";
		prnMsg( __('Validation failed, no updates or deletes took place'), 'error');
	}

} elseif (isset($_POST['delete']) AND mb_strlen($_POST['delete']) >1 ) {
//the button to delete a selected record was clicked instead of the submit button

	$CancelDelete = 0;
	//what validation is required before allowing deletion of assets ....  maybe there should be no deletion option?
	$Result = DB_query("SELECT cost,
								accumdepn,
								accumdepnact,
								costact
						FROM fixedassets INNER JOIN fixedassetcategories
						ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
						WHERE assetid='" . $AssetID . "'");
	$AssetRow = DB_fetch_array($Result);
	$NBV = $AssetRow['cost'] -$AssetRow['accumdepn'];
	if ($NBV!=0) {
		$CancelDelete =1; //cannot delete assets where NBV is not 0
		prnMsg(__('The asset still has a net book value - only assets with a zero net book value can be deleted'),'error');
	}
	$Result = DB_query("SELECT * FROM fixedassettrans WHERE assetid='" . $AssetID . "'");
	if (DB_num_rows($Result) > 0){
		$CancelDelete =1; /*cannot delete assets with transactions */
		prnMsg(__('The asset has transactions associated with it. The asset can only be deleted when the fixed asset transactions are purged, otherwise the integrity of fixed asset reports may be compromised'),'error');
	}
	$Result = DB_query("SELECT * FROM purchorderdetails WHERE assetid='" . $AssetID . "'");
	if (DB_num_rows($Result) > 0){
		$CancelDelete =1; /*cannot delete assets where there is a purchase order set up for it */
		prnMsg(__('There is a purchase order set up for this asset. The purchase order line must be deleted first'),'error');
	}
	if ($CancelDelete==0) {
		DB_Txn_Begin();

		/*Need to remove cost and accumulate depreciation from cost and accumdepn accounts */
		$PeriodNo = GetPeriod(date($_SESSION['DefaultDateFormat']));
		$TransNo = GetNextTransNo( 43 ); /* transaction type is asset deletion - (and remove cost/acc5umdepn from GL) */
		if ($AssetRow['cost'] > 0){
			//credit cost for the asset deleted
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES ('43',
							'" . $TransNo . "',
							CURRENT_DATE,
							'" . $PeriodNo . "',
							'" . $AssetRow['costact'] . "',
							'" . mb_substr(__('Delete asset') . ' ' . $AssetID, 0, 200) . "',
							'" . -$AssetRow['cost']. "'
							)";
			$ErrMsg = __('Cannot insert a GL entry for the deletion of the asset because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			//debit accumdepn for the depreciation removed on deletion of this asset
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
						VALUES ('43',
							'" . $TransNo . "',
							CURRENT_DATE,
							'" . $PeriodNo . "',
							'" . $AssetRow['accumdepnact'] . "',
							'" . mb_substr(__('Delete asset') . ' ' . $AssetID, 0, 200) . "',
							'" . $Asset['accumdepn']. "'
							)";
			$ErrMsg = __('Cannot insert a GL entry for the reversal of accumulated depreciation on deletion of the asset because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

		} //end if cost > 0

		$SQL="DELETE FROM fixedassets WHERE assetid='" . $AssetID . "'";
		$Result = DB_query($SQL, __('Could not delete the asset record'), '', true);

		DB_Txn_Commit();

		// Delete the AssetImage
		foreach ($SupportedImgExt as $Ext) {
			$File = $_SESSION['part_pics_dir'] . '/ASSET_' . $AssetID . '.' . $Ext;
			if (file_exists ($File) ) {
				unlink($File);
			}
		}

		prnMsg(__('Deleted the asset  record for asset number' ) . ' ' . $AssetID );
		unset($_POST['LongDescription']);
		unset($_POST['Description']);
		unset($_POST['AssetCategoryID']);
		unset($_POST['AssetLocation']);
		unset($_POST['DepnType']);
		unset($_POST['DepnRate']);
		unset($_POST['BarCode']);
		unset($_POST['SerialNo']);
		unset($AssetID);
		unset($_SESSION['SelectedAsset']);

	} //end if OK Delete Asset
} /* end if delete asset */
DB_Txn_Commit();

if (!isset($AssetID) OR $AssetID=='') {
	$New = 1;
	echo '<input type="hidden" name="New" value="" />';
	$_POST['LongDescription'] = '';
	$_POST['Description'] = '';
	$_POST['AssetCategoryID']  = '';
	$_POST['SerialNo']  = '';
	$_POST['AssetLocation']  = '';
	$_POST['DepnType']  = 0;
	$_POST['BarCode']  = '';
	$_POST['DepnRate']  = 0;
} elseif ($InputError!=1) {
	$SQL = "SELECT assetid,
				description,
				longdescription,
				assetcategoryid,
				serialno,
				assetlocation,
				datepurchased,
				depntype,
				depnrate,
				cost,
				accumdepn,
				barcode,
				disposalproceeds,
				disposaldate
			FROM fixedassets
			WHERE assetid ='" . $AssetID . "'";

	$Result = DB_query($SQL);
	$AssetRow = DB_fetch_array($Result);

	$_POST['LongDescription'] = $AssetRow['longdescription'];
	$_POST['Description'] = $AssetRow['description'];
	$_POST['AssetCategoryID']  = $AssetRow['assetcategoryid'];
	$_POST['SerialNo']  = $AssetRow['serialno'];
	$_POST['AssetLocation']  = $AssetRow['assetlocation'];
	$_POST['DepnType']  = $AssetRow['depntype'];
	$_POST['BarCode']  = $AssetRow['barcode'];
	$_POST['DepnRate']  = locale_number_format($AssetRow['depnrate'],2);
}

// Start Main Content Cards
echo '<div class="db-card">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-file-alt"></i> ' . __('Asset Information') . '</div>
		</div>
		<div class="db-card-body">';

if (isset($AssetID) && $AssetID != '') {
	echo '<input type="hidden" name="AssetID" value="' . $AssetID . '"/>';
}

if (isset($AssetRow['disposaldate']) AND $AssetRow['disposaldate'] !='1000-01-01'){
	echo '<div class="db-alert db-alert-warning" style="margin-bottom: 20px;">
			<i class="fas fa-exclamation-triangle db-alert-icon"></i>
			<div>
				<strong>' . __('Asset Disposed') . '</strong>: ' . __('This asset was already disposed on') . ' ' . ConvertSQLDate($AssetRow['disposaldate']) . '
			</div>
		  </div>';
}

$Description = $_POST['Description'] ?? '';
echo '<div class="db-form-group">
		<label class="db-label" for="Description">' . __('Short Description') . ':</label>
		<input class="db-input ' . (in_array('Description',$Errors) ?  'inputerror' : '' ) .'" type="text" required="required" name="Description" maxlength="50" value="' . $Description . '" placeholder="' . __('e.g. Dell Latitude Laptop') . '" />
		<div class="db-field-help">' . __('Up to 50 characters allowed.') . '</div>
	</div>';

if (isset($_POST['LongDescription'])) {
	$LongDescription = AddCarriageReturns($_POST['LongDescription']);
} else {
	$LongDescription ='';
}
echo '<div class="db-form-group">
		<label class="db-label" for="LongDescription">' . __('Full Specifications') . ' / ' . __('Long Description') . ':</label>
		<textarea class="db-input ' . (in_array('LongDescription',$Errors) ?  'texterror' : '' ) .'"  name="LongDescription" required="required" rows="4" placeholder="' . __('Enter detailed specs, serial numbers, hardware details etc.') . '">' . stripslashes($LongDescription) . '</textarea>
	</div>';

echo '</div>
	</div>'; // End Card 1

echo '<div class="db-card" style="margin-top: 20px;">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-th-large"></i> ' . __('Categorization & Identity') . '</div>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">
				<div class="db-form-group">
					<label class="db-label" for="AssetCategoryID">' . __('Asset Category') . ':</label>
					<div class="db-input-group-mobile" style="display: flex; gap: 8px; align-items: center;">
						<select name="AssetCategoryID" class="db-select">';

$SQL = "SELECT categoryid, categorydescription FROM fixedassetcategories";
$ErrMsg = __('The asset categories could not be retrieved because');
$Result = DB_query($SQL, $ErrMsg);
$Category = '';
while ($MyRow=DB_fetch_array($Result)){
	if (!isset($_POST['AssetCategoryID']) or $MyRow['categoryid']==$_POST['AssetCategoryID']){
		echo '<option selected="selected" value="'. $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	} else {
		echo '<option value="'. $MyRow['categoryid'] . '">' . $MyRow['categorydescription']. '</option>';
	}
	$Category=$MyRow['categoryid'];
}
echo '					</select>
						<a target="_blank" href="'. $RootPath . '/FixedAssetCategories.php" class="db-btn db-btn-secondary db-btn-sm" title="' . __('Manage Categories') . '"><i class="fas fa-cog"></i></a>
					</div>
				</div>';

if (!isset($_POST['AssetCategoryID'])) {
	$_POST['AssetCategoryID']=$Category;
}

$SQL = "SELECT locationid, locationdescription FROM fixedassetlocations";
$ErrMsg = __('The asset locations could not be retrieved because');
$Result = DB_query($SQL, $ErrMsg);

echo '			<div class="db-form-group">
					<label class="db-label" for="AssetLocation">' . __('Storage Location') . ':</label>
					<div class="db-input-group-mobile" style="display: flex; gap: 8px; align-items: center;">
						<select name="AssetLocation" class="db-select">';
while ($MyRow=DB_fetch_array($Result)){
	if ($_POST['AssetLocation']==$MyRow['locationid']){
		echo '<option selected="selected" value="' . $MyRow['locationid'] .'">' . $MyRow['locationdescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow['locationid'] .'">' . $MyRow['locationdescription'] . '</option>';
	}
}
echo '					</select>
						<a target="_blank" href="'. $RootPath . '/FixedAssetLocations.php" class="db-btn db-btn-secondary db-btn-sm" title="' . __('Manage Locations') . '"><i class="fas fa-cog"></i></a>
					</div>
				</div>
			</div>'; // End Grid 1

echo '		<div class="db-grid db-grid-2" style="margin-top: 15px;">
				<div class="db-form-group">
					<label class="db-label" for="BarCode">' . __('Bar Code') . ':</label>
					<input class="db-input ' . (in_array('BarCode',$Errors) ?  'inputerror' : '' ) .'" type="text" name="BarCode" maxlength="20" value="' . $_POST['BarCode'] . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label" for="SerialNo">' . __('Serial Number') . ':</label>
					<input class="db-input ' . (in_array('SerialNo',$Errors) ?  'inputerror' : '' ) .'" type="text" name="SerialNo" maxlength="30" value="' . $_POST['SerialNo'] . '" />
				</div>
			</div>
		</div>
	</div>'; // End Card 2

// Financial Stats (If existing)
if (isset($AssetRow)) {
	echo '<div class="db-card-group" style="margin-top: 25px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
			<div class="db-card" style="box-shadow: none; border: 1px solid var(--border-soft);">
				<div class="db-card-body" style="padding: 15px; text-align: center;">
					<div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">' . __('Historical Cost') . '</div>
					<div style="font-size: 1.25rem; font-weight: 700;">' . locale_number_format($AssetRow['cost'],$_SESSION['CompanyRecord']['decimalplaces']) . '</div>
				</div>
			</div>
			<div class="db-card" style="box-shadow: none; border: 1px solid var(--border-soft);">
				<div class="db-card-body" style="padding: 15px; text-align: center;">
					<div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">' . __('Accumulated Depn') . '</div>
					<div style="font-size: 1.25rem; font-weight: 700;">' . locale_number_format($AssetRow['accumdepn'],$_SESSION['CompanyRecord']['decimalplaces']) . '</div>
				</div>
			</div>
			<div class="db-card" style="box-shadow: none; border: 1px solid var(--border-soft); background: var(--bg-workspace);">
				<div class="db-card-body" style="padding: 15px; text-align: center;">
					<div style="font-size: 0.75rem; font-weight: 600; color: var(--text-muted); margin-bottom: 5px;">' . __('Net Book Value') . '</div>
					<div style="font-size: 1.25rem; font-weight: 900;">' . locale_number_format($AssetRow['cost']-$AssetRow['accumdepn'],$_SESSION['CompanyRecord']['decimalplaces']) . '</div>
				</div>
			</div>
		  </div>';
}

echo '<div class="db-card" style="margin-top: 20px;">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-chart-line"></i> ' . __('Depreciation Policy') . '</div>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">
				<div class="db-form-group">
					<label class="db-label" for="DepnType">' . __('Depreciation Method') . ':</label>
					<select name="DepnType" class="db-select">';
if (!isset($_POST['DepnType'])){
	$_POST['DepnType'] = 0;
}
if ($_POST['DepnType']==0){
	echo '<option selected="selected" value="0">' . __('Straight Line') . '</option>';
	echo '<option value="1">' . __('Diminishing Value') . '</option>';
} else {
	echo '<option value="0">' . __('Straight Line') . '</option>';
	echo '<option selected="selected" value="1">' . __('Diminishing Value') . '</option>';
}
echo '				</select>
				</div>
				<div class="db-form-group">
					<label class="db-label" for="DepnRate">' . __('Annual Rate (%)') . ':</label>
					<div style="display: flex; align-items: center; gap: 10px;">
						<input class="db-input ' . (in_array('DepnRate',$Errors) ?  'inputerror number' : 'number' ) .'" type="text" name="DepnRate" size="4" maxlength="4" value="' . $_POST['DepnRate'] . '" style="max-width: 100px; font-weight: 700; text-align: center;" />
						<span class="db-badge">%</span>
					</div>
				</div>
			</div>
		</div>
	</div>'; // End Card 3

echo '</div>
	</div>'; // End Card 3

// Asset Image (Minimalist placement after policy)
if (!isset($New)) {
	echo '<div class="db-card" style="margin-top: 25px;">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-camera"></i> ' . __('Asset Image') . '</div>
			</div>
			<div class="db-card-body">
				<div style="display: flex; gap: 30px; align-items: start; flex-wrap: wrap;">';
	
	$Glob = (glob($_SESSION['part_pics_dir'] . '/ASSET_' . $AssetID . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE));
	$ImageFile = reset($Glob);
	$AssetImgLink = GetImageLink($ImageFile, 'ASSET_' . $AssetID, 120, 120, "", "");
	
	if ($AssetImgLink!=__('No Image')) {
		echo '<div style="flex-shrink: 0; border-radius: var(--radius-sm); overflow: hidden; border: 1px solid var(--border-soft); background: var(--bg-workspace); width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
				' . $AssetImgLink . '
			  </div>';
	} else {
		echo '<div style="flex-shrink: 0; width: 120px; height: 120px; border-radius: var(--radius-sm); border: 2px dashed var(--border); background: var(--bg-workspace); color: var(--text-muted); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px;">
				<i class="fas fa-image" style="font-size: 1.5rem; opacity: 0.3;"></i>
				<span style="font-size: 0.65rem;">' . __('No image') . '</span>
			  </div>';
	}

	echo '		<div style="flex-grow: 1; min-width: 250px;">
					<div class="db-form-group">
						<label class="db-label" for="ItemPicture">' .  __('Upload New Image') . ' (' . implode(", ", $SupportedImgExt) . '):</label>
						<input class="db-input" type="file" id="ItemPicture" name="ItemPicture" />
					</div>';
	
	if ($AssetImgLink!=__('No Image')) {
		echo '		<label style="display: flex; align-items: center; gap: 8px; margin-top: 10px; cursor: pointer; font-size: 0.85rem; color: var(--danger);">
						<input type="checkbox" name="ClearImage" id="ClearImage" value="1"> ' . __('Delete current image') . '
					</label>';
	}

	echo '		</div>
				</div>
			</div>
		  </div>';
}

// Final Action Bar (Persistent at bottom)
echo '<div class="db-action-footer db-footer-stack" style="margin-top: 40px; padding: 20px; border-top: 1px solid var(--border); display: flex; justify-content: center; gap: 15px; background: var(--surface);">
		<a href="' . $RootPath . '/SelectAsset.php" class="db-btn db-btn-secondary" style="min-width: 150px; justify-content: center;">
			<i class="fas fa-times"></i> ' . __('Cancel') . '
		</a>';

if (isset($New)) {
	echo '	<button type="submit" name="submit" class="db-btn db-btn-primary" style="min-width: 200px; justify-content: center; padding: 12px 30px;">
				<i class="fas fa-plus-circle"></i> ' . __('Create Fixed Asset') . '
			</button>';
} else {
	echo '	<button type="submit" name="delete" value="' . __('Delete This Asset') . '" class="db-btn db-btn-danger" style="justify-content: center;" onclick="return confirm(\'' . __('Are You Sure? Only assets with a zero book value can be deleted.') . '\');">
				<i class="fas fa-trash-alt"></i> ' . __('Delete') . '
			</button>';
	echo '	<button type="submit" name="submit" value="' . __('Update') . '" class="db-btn db-btn-primary" style="min-width: 200px; justify-content: center; padding: 12px 30px;">
				<i class="fas fa-save"></i> ' . __('Save Changes') . '
			</button>';
}

echo '</div>'; 

echo '		</div>
	</div> 
</form>';

echo '<style>
.db-field-help { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; font-style: italic; }
.db-action-footer { border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border); position: sticky; bottom: 20px; z-index: 10; }

@media (max-width: 768px) {
	.db-input-group-mobile { flex-direction: column; align-items: stretch !important; gap: 10px; }
	.db-input-group-mobile .db-btn { width: 100%; justify-content: center; height: auto; padding: 12px; }
	.db-footer-stack { flex-direction: column; padding: 15px !important; }
	.db-footer-stack .db-btn, .db-footer-stack a.db-btn { width: 100% !important; min-width: 0 !important; margin: 0 !important; }
}
</style>';

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
