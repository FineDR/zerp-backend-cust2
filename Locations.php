<?php

/* Defines the inventory stocking locations or warehouses */

require(__DIR__ . '/includes/session.php');

$Title = __('Location Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'Locations';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/CountriesArray.php');

if (isset($_GET['SelectedLocation'])) {
	$SelectedLocation = $_GET['SelectedLocation'];
} elseif (isset($_POST['SelectedLocation'])) {
	$SelectedLocation = $_POST['SelectedLocation'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;

	$_POST['LocCode']=mb_strtoupper($_POST['LocCode']);
	if (trim($_POST['LocCode']) == '') {
		$InputError = 1;
		prnMsg(__('The location code may not be empty'), 'error');
	}
	if ($_POST['CashSaleCustomer']!='') {
		if ($_POST['CashSaleBranch']=='') {
			prnMsg(__('A cash sale customer and branch are necessary to fully setup the counter sales functionality'),'error');
			$InputError =1;
		} else {
			$SQL = "SELECT * FROM custbranch
					WHERE debtorno='" . $_POST['CashSaleCustomer'] . "'
					AND branchcode='" . $_POST['CashSaleBranch'] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result)==0) {
				$InputError = 1;
				prnMsg(__('The cash sale customer for this location must be defined with both a valid customer code and a valid branch code for this customer'),'error');
			}
		}
	}

	if (isset($SelectedLocation) AND $InputError !=1) {

		$Managed = (isset($_POST['Managed']) and $_POST['Managed'] == 'on') ? 1 : 0;

		$SQL = "UPDATE locations SET loccode='" . $_POST['LocCode'] . "',
									locationname='" . $_POST['LocationName'] . "',
									deladd1='" . $_POST['DelAdd1'] . "',
									deladd2='" . $_POST['DelAdd2'] . "',
									deladd3='" . $_POST['DelAdd3'] . "',
									deladd4='" . $_POST['DelAdd4'] . "',
									deladd5='" . $_POST['DelAdd5'] . "',
									deladd6='" . $_POST['DelAdd6'] . "',
									tel='" . $_POST['Tel'] . "',
									fax='" . $_POST['Fax'] . "',
									email='" . $_POST['Email'] . "',
									contact='" . $_POST['Contact'] . "',
									taxprovinceid = '" . $_POST['TaxProvince'] . "',
									cashsalecustomer ='" . $_POST['CashSaleCustomer'] . "',
									cashsalebranch ='" . $_POST['CashSaleBranch'] . "',
									managed = '" . $Managed . "',
									internalrequest = '" . $_POST['InternalRequest'] . "',
									usedforwo = '" . $_POST['UsedForWO'] . "',
									glaccountcode = '" . $_POST['GLAccountCode'] . "',
									allowinvoicing = '" . $_POST['AllowInvoicing'] . "'
						WHERE loccode = '" . $SelectedLocation . "'";

		$ErrMsg = __('An error occurred updating the') . ' ' . $SelectedLocation . ' ' . __('location record because');
		$Result = DB_query($SQL, $ErrMsg);

		prnMsg(__('The location record has been updated'),'success');
		unset($SelectedLocation);
		foreach($_POST as $key => $val) unset($_POST[$key]);

	} elseif ($InputError !=1) {

		$Managed = (isset($_POST['Managed']) and $_POST['Managed'] == 'on') ? 1 : 0;

		$SQL = "INSERT INTO locations (loccode,
										locationname,
										deladd1,
										deladd2,
										deladd3,
										deladd4,
										deladd5,
										deladd6,
										tel,
										fax,
										email,
										contact,
										taxprovinceid,
										cashsalecustomer,
										cashsalebranch,
										managed,
										internalrequest,
										usedforwo,
										glaccountcode,
										allowinvoicing)
						VALUES ('" . $_POST['LocCode'] . "',
								'" . $_POST['LocationName'] . "',
								'" . $_POST['DelAdd1'] ."',
								'" . $_POST['DelAdd2'] ."',
								'" . $_POST['DelAdd3'] . "',
								'" . $_POST['DelAdd4'] . "',
								'" . $_POST['DelAdd5'] . "',
								'" . $_POST['DelAdd6'] . "',
								'" . $_POST['Tel'] . "',
								'" . $_POST['Fax'] . "',
								'" . $_POST['Email'] . "',
								'" . $_POST['Contact'] . "',
								'" . $_POST['TaxProvince'] . "',
								'" . $_POST['CashSaleCustomer'] . "',
								'" . $_POST['CashSaleBranch'] . "',
								'" . $Managed . "',
								'" . $_POST['InternalRequest'] . "',
								'" . $_POST['UsedForWO'] . "',
								'" . $_POST['GLAccountCode'] . "',
								'" . $_POST['AllowInvoicing'] . "')";

		$ErrMsg = __('An error occurred inserting the new location record because');
		$Result = DB_query($SQL, $ErrMsg);

		// Also need to add LocStock records for all existing stock items
		$SQL = "INSERT INTO locstock (loccode, stockid, quantity, reorderlevel)
			SELECT '" . $_POST['LocCode'] . "', stockmaster.stockid, 0, 0 FROM stockmaster";
		DB_query($SQL);

		// Also need to add locationuser records for all existing users
		$SQL = "INSERT INTO locationusers (userid, loccode, canview, canupd)
				SELECT www_users.userid, locations.loccode, 1, 1
				FROM www_users CROSS JOIN locations
				LEFT JOIN locationusers ON www_users.userid = locationusers.userid AND locations.loccode = locationusers.loccode
				WHERE locationusers.userid IS NULL AND locations.loccode='". $_POST['LocCode'] . "';";
		DB_query($SQL);

		prnMsg(__('The new location record has been added and stock locations/users initialized'),'success');
		unset($SelectedLocation);
		foreach($_POST as $key => $val) unset($_POST[$key]);
	}

	// Tax Auth Rates alignment logic
	$ResTax = DB_query("SELECT COUNT(taxid) FROM taxauthorities");
	$NoTaxAuths =DB_fetch_row($ResTax);
	$DispTaxProvincesResult = DB_query("SELECT DISTINCT taxprovinceid FROM locations");
	$TaxCatsResult = DB_query("SELECT taxcatid FROM taxcategories");
	if (DB_num_rows($TaxCatsResult) > 0) {
		while ($TRow=DB_fetch_row($DispTaxProvincesResult)) {
			$NoTaxRates = DB_query("SELECT taxauthority FROM taxauthrates WHERE dispatchtaxprovince='" . $TRow[0] . "'");
			if (DB_num_rows($NoTaxRates) < $NoTaxAuths[0]) {
				DB_query("DELETE FROM taxauthrates WHERE dispatchtaxprovince='" . $TRow[0] . "'");
				while ($CatRow = DB_fetch_row($TaxCatsResult)) {
					$SQL = "INSERT INTO taxauthrates (taxauthority, dispatchtaxprovince, taxcatid)
							SELECT taxid, '" . $TRow[0] . "', '" . $CatRow[0] . "' FROM taxauthorities";
					DB_query($SQL);
				}
				DB_data_seek($TaxCatsResult,0);
			}
		}
	}

} elseif (isset($_GET['delete'])) {
	$CancelDelete = 0;
	$SQL= "SELECT COUNT(*) FROM salesorders WHERE fromstkloc='". $SelectedLocation . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this location because sales orders refer to it'),'warn');
	} else {
		$SQL= "SELECT COUNT(*) FROM stockmoves WHERE loccode='" . $SelectedLocation . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete this location because stock movements refer to it'),'warn');
		} else {
			$SQL= "SELECT COUNT(*) FROM locstock WHERE loccode='". $SelectedLocation . "' AND quantity !=0";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0]>0) {
				$CancelDelete = 1;
				prnMsg(__('Cannot delete this location because there is still stock on hand'),'warn');
			}
		}
	}
	if (! $CancelDelete) {
		$Result = DB_query("SELECT taxprovinceid FROM locations WHERE loccode='" . $SelectedLocation . "'");
		$TaxProvinceRow = DB_fetch_row($Result);
		$Result = DB_query("SELECT COUNT(taxprovinceid) FROM locations WHERE taxprovinceid='" .$TaxProvinceRow[0] . "'");
		$TaxProvinceCount = DB_fetch_row($Result);
		if ($TaxProvinceCount[0]==1) {
			DB_query("DELETE FROM taxauthrates WHERE dispatchtaxprovince='" . $TaxProvinceRow[0] . "'");
		}
		DB_query("DELETE FROM locstock WHERE loccode ='" . $SelectedLocation . "'");
		DB_query("DELETE FROM locationusers WHERE loccode='" . $SelectedLocation . "'");
		DB_query("DELETE FROM locations WHERE loccode='" . $SelectedLocation . "'");
		prnMsg(__('Location') . ' ' . $SelectedLocation . ' ' . __('has been deleted'), 'success');
		unset ($SelectedLocation);
	}
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-warehouse"></i> ' . (isset($SelectedLocation) ? __('Edit Location') : __('New Warehouse')) . '</h3></div>
		<div class="db-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($SelectedLocation)) {
	$SQL = "SELECT * FROM locations WHERE loccode='" . $SelectedLocation . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$_POST['LocCode'] = $MyRow['loccode'];
	$_POST['LocationName'] = $MyRow['locationname'];
	$_POST['DelAdd1'] = $MyRow['deladd1'];
	$_POST['DelAdd2'] = $MyRow['deladd2'];
	$_POST['DelAdd3'] = $MyRow['deladd3'];
	$_POST['DelAdd4'] = $MyRow['deladd4'];
	$_POST['DelAdd5'] = $MyRow['deladd5'];
	$_POST['DelAdd6'] = $MyRow['deladd6'];
	$_POST['Contact'] = $MyRow['contact'];
	$_POST['Tel'] = $MyRow['tel'];
	$_POST['Fax'] = $MyRow['fax'];
	$_POST['Email'] = $MyRow['email'];
	$_POST['TaxProvince'] = $MyRow['taxprovinceid'];
	$_POST['CashSaleCustomer'] = $MyRow['cashsalecustomer'];
	$_POST['CashSaleBranch'] = $MyRow['cashsalebranch'];
	$_POST['Managed'] = ($MyRow['managed'] == 1 ? 'on' : 'off');
	$_POST['InternalRequest'] = $MyRow['internalrequest'];
	$_POST['UsedForWO'] = $MyRow['usedforwo'];
	$_POST['GLAccountCode'] = $MyRow['glaccountcode'];
	$_POST['AllowInvoicing'] = $MyRow['allowinvoicing'];
	
	echo '<input type="hidden" name="SelectedLocation" value="' . $SelectedLocation . '" />';
	echo '<input type="hidden" name="LocCode" value="' . $_POST['LocCode'] . '" />';
	echo '<div class="db-form-group"><label class="db-label">' . __('Location Code') . '</label><input type="text" class="db-input" value="' . $_POST['LocCode'] . '" disabled /></div>';
} else {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Location Code') . '</label>
			<input type="text" name="LocCode" class="db-input" required maxlength="5" value="' . ($_POST['LocCode'] ?? '') . '" placeholder="' . __('e.g. WH01') . '" />
		  </div>';
}

echo '			<div class="db-form-group">
					<label class="db-label">' . __('Name') . '</label>
					<input type="text" name="LocationName" class="db-input" required maxlength="50" value="' . ($_POST['LocationName'] ?? '') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Contact Person') . '</label>
					<input type="text" name="Contact" class="db-input" required maxlength="30" value="' . ($_POST['Contact'] ?? '') . '" />
				</div>
				<div class="db-grid db-grid-2">
					<div class="db-form-group"><label class="db-label">' . __('Tel') . '</label><input type="text" name="Tel" class="db-input" value="' . ($_POST['Tel'] ?? '') . '" /></div>
					<div class="db-form-group"><label class="db-label">' . __('Fax') . '</label><input type="text" name="Fax" class="db-input" value="' . ($_POST['Fax'] ?? '') . '" /></div>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Email') . '</label>
					<input type="email" name="Email" class="db-input" value="' . ($_POST['Email'] ?? '') . '" />
				</div>
				
				<div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border-soft);">
					<h4 class="db-font-bold" style="margin-bottom: 10px; font-size: 0.9rem;">' . __('Delivery Address') . '</h4>
					<div class="db-form-group"><input type="text" name="DelAdd1" class="db-input mb-2" value="' . ($_POST['DelAdd1'] ?? '') . '" placeholder="' . __('Building/Suite') . '" /></div>
					<div class="db-form-group"><input type="text" name="DelAdd2" class="db-input mb-2" value="' . ($_POST['DelAdd2'] ?? '') . '" placeholder="' . __('Street Address') . '" /></div>
					<div class="db-form-group"><input type="text" name="DelAdd3" class="db-input mb-2" value="' . ($_POST['DelAdd3'] ?? '') . '" placeholder="' . __('Suburb') . '" /></div>
					<div class="db-grid db-grid-2">
						<input type="text" name="DelAdd4" class="db-input" value="' . ($_POST['DelAdd4'] ?? '') . '" placeholder="' . __('City') . '" />
						<input type="text" name="DelAdd5" class="db-input" value="' . ($_POST['DelAdd5'] ?? '') . '" placeholder="' . __('Zip') . '" />
					</div>
					<div class="db-form-group mt-2">
						<select name="DelAdd6" class="db-select">';
foreach ($CountriesArray as $CName) {
	echo '<option ' . (($_POST['DelAdd6'] ?? '') == $CName ? 'selected' : '') . ' value="' . $CName . '">' . $CName . '</option>';
}
echo '					</select>
					</div>
				</div>

				<div style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed var(--border-soft);">
					<h4 class="db-font-bold" style="margin-bottom: 15px; font-size: 0.9rem;">' . __('System Settings') . '</h4>
					<div class="db-form-group">
						<label class="db-label">' . __('Tax Province') . '</label>
						<select name="TaxProvince" class="db-select">';
$TaxResult = DB_query("SELECT taxprovinceid, taxprovincename FROM taxprovinces");
while ($Trow = DB_fetch_array($TaxResult)) {
	echo '<option ' . (($_POST['TaxProvince'] ?? '') == $Trow['taxprovinceid'] ? 'selected' : '') . ' value="' . $Trow['taxprovinceid'] . '">' . $Trow['taxprovincename'] . '</option>';
}
echo '					</select>
					</div>
					<div class="db-grid db-grid-2">
						<div class="db-form-group">
							<label class="db-label">' . __('Internal Req') . '</label>
							<select name="InternalRequest" class="db-select">
								<option ' . (($_POST['InternalRequest'] ?? '') == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
								<option ' . (($_POST['InternalRequest'] ?? '') == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
							</select>
						</div>
						<div class="db-form-group">
							<label class="db-label">' . __('Invoicing') . '</label>
							<select name="AllowInvoicing" class="db-select">
								<option ' . (($_POST['AllowInvoicing'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
								<option ' . (($_POST['AllowInvoicing'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
							</select>
						</div>
					</div>
				</div>

				<div style="margin-top: 20px;">
					<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%;"><i class="fas fa-save"></i> ' . __('Save Warehouse') . '</button>';
if (isset($SelectedLocation)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-input-light" style="width: 100%; margin-top: 10px; text-align: center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
}
echo '				</div>
			</form>
		</div>
	  </div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
$SQL = "SELECT locations.*, taxprovinces.taxprovincename 
		FROM locations INNER JOIN taxprovinces ON locations.taxprovinceid=taxprovinces.taxprovinceid";
$Result = DB_query($SQL);

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-map-marked-alt"></i> ' . __('Inventory Hubs') . '</h3></div>
		<div class="db-card-body p-0">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('Code') . '</th>
							<th>' . __('Location Hub') . '</th>
							<th>' . __('Tax Region') . '</th>
							<th class="text-center">' . __('Invoice') . '</th>
							<th class="text-center">' . __('Internal') . '</th>
							<th class="text-right">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

while ($MyRow = DB_fetch_array($Result)) {
	$isSel = (isset($SelectedLocation) && $SelectedLocation == $MyRow['loccode']);
	echo '<tr ' . ($isSel ? 'style="background: var(--bg-soft);"' : '') . '>
			<td><div class="db-badge db-badge-secondary">' . $MyRow['loccode'] . '</div></td>
			<td>
				<div class="db-font-bold ' . ($isSel ? 'text-primary' : '') . '">' . $MyRow['locationname'] . '</div>
				<div class="db-muted" style="font-size: 0.8rem;">' . $MyRow['contact'] . ' | ' . $MyRow['tel'] . '</div>
			</td>
			<td>' . $MyRow['taxprovincename'] . '</td>
			<td class="text-center">' . ($MyRow['allowinvoicing'] == 1 ? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>' : '<span class="db-badge db-badge-secondary">' . __('No') . '</span>') . '</td>
			<td class="text-center">' . ($MyRow['internalrequest'] == 1 ? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>' : '<span class="db-badge db-badge-secondary">' . __('No') . '</span>') . '</td>
			<td class="text-right">
				<div style="display: flex; gap: 8px; justify-content: flex-end;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocation=' . $MyRow['loccode'] . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i></a>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocation=' . $MyRow['loccode'] . '&amp;delete=1" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Are you sure?') . '\');"><i class="fas fa-trash"></i></a>
				</div>
			</td>
		  </tr>';
}
echo '		</tbody>
				</table>
			</div>
		</div>
	  </div>';
echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
