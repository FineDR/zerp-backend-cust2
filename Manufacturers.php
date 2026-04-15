<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Brands Maintenance');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_GET['SelectedBrand'])){
	$SelectedBrand = $_GET['SelectedBrand'];
} elseif (isset($_POST['SelectedBrand'])){
	$SelectedBrand = $_POST['SelectedBrand'];
}

$SupportedImgExt = array('png','jpg','jpeg');

if (isset($_POST['submit'])) {

	$InputError = 0;

	if (isset($SelectedBrand) AND $InputError !=1) {

		if (isset($_FILES['BrandPicture']) AND $_FILES['BrandPicture']['name'] !='') {

			$Result	= $_FILES['BrandPicture']['error'];
		 	$UploadTheFile = 'Yes';

			$ImgExt = pathinfo($_FILES['BrandPicture']['name'], PATHINFO_EXTENSION);
			$FileName = $_SESSION['part_pics_dir'] . '/BRAND-' . $SelectedBrand . '.' . $ImgExt;

			if (!in_array ($ImgExt, $SupportedImgExt)) {
				prnMsg(__('Only ' . implode(", ", $SupportedImgExt) . ' files are supported'),'warn');
				$UploadTheFile ='No';
			} elseif ( $_FILES['BrandPicture']['size'] > ($_SESSION['MaxImageSize']*1024)) {
				prnMsg(__('The file size is over the maximum allowed'),'warn');
				$UploadTheFile ='No';
			}

			if ($UploadTheFile=='Yes'){
				foreach ($SupportedImgExt as $Ext) {
					$OldFile = $_SESSION['part_pics_dir'] . '/BRAND-' . $SelectedBrand . '.' . $Ext;
					if (file_exists ($OldFile) ) @unlink($OldFile);
				}
				$Result  =  move_uploaded_file($_FILES['BrandPicture']['tmp_name'], $FileName);
				$_POST['BrandsImage'] = 'BRAND-' . $SelectedBrand;
			}
		}

		if (isset($_POST['ClearImage'])) {
			foreach ($SupportedImgExt as $Ext) {
				$File = $_SESSION['part_pics_dir'] . '/BRAND-' . $SelectedBrand . '.' . $Ext;
				if (file_exists ($File) ) @unlink($File);
			}
			$_POST['BrandsImage'] = '';
		}

		$SQL = "UPDATE manufacturers SET manufacturers_name='" . $_POST['BrandsName'] . "',
									manufacturers_url='" . $_POST['BrandsURL'] . "'";
		if (isset($_POST['BrandsImage'])){
			$SQL .= ", manufacturers_image='" . $_POST['BrandsImage'] . "'";
		}
		$SQL .= " WHERE manufacturers_id = '" . $SelectedBrand . "'";

		$Result = DB_query($SQL);
		prnMsg( __('The brand record has been updated'),'success');
		unset($SelectedBrand);

	} elseif ($InputError !=1) {

		$SQL = "INSERT INTO manufacturers (manufacturers_name, manufacturers_url)
						VALUES ('" . $_POST['BrandsName'] . "', '" . $_POST['BrandsURL'] . "')";
		$Result = DB_query($SQL);
		$LastInsertId = DB_Last_Insert_ID('manufacturers', 'manufacturers_id');

		if (isset($_FILES['BrandPicture']) AND $_FILES['BrandPicture']['name'] !='') {
			$ImgExt = pathinfo($_FILES['BrandPicture']['name'], PATHINFO_EXTENSION);
			$FileName = $_SESSION['part_pics_dir'] . '/BRAND-' . $LastInsertId . '.' . $ImgExt;
			if (in_array ($ImgExt, $SupportedImgExt) && $_FILES['BrandPicture']['size'] <= ($_SESSION['MaxImageSize']*1024)) {
				if (move_uploaded_file($_FILES['BrandPicture']['tmp_name'], $FileName)) {
					DB_query("UPDATE manufacturers SET manufacturers_image='BRAND-" . $LastInsertId . "' WHERE manufacturers_id='" . $LastInsertId . "'");
				}
			}
		}
		prnMsg( __('The new brand record has been added'),'success');
		unset($SelectedBrand);
	}

} elseif (isset($_GET['delete'])) {
	$CancelDelete = false;
	$SQL= "SELECT COUNT(*) FROM salescatprod WHERE manufacturers_id='". $SelectedBrand . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		$CancelDelete = true;
		prnMsg( __('Cannot delete this brand because products have been defined as from this brand'),'warn');
	}

	if (!$CancelDelete) {
		DB_query("DELETE FROM manufacturers WHERE manufacturers_id='" . $SelectedBrand . "'");
		foreach ($SupportedImgExt as $Ext) {
			$File = $_SESSION['part_pics_dir'] . '/BRAND-' . $SelectedBrand . '.' . $Ext;
			if (file_exists ($File) ) @unlink($File);
		}
		prnMsg( __('Brand') . ' ' . $SelectedBrand . ' ' . __('has been deleted') . '!', 'success');
	}
	unset ($SelectedBrand);
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-tags"></i> ' . (isset($SelectedBrand) ? __('Edit Brand') : __('Create Brand')) . '</h3></div>
		<div class="db-card-body">
			<form enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($SelectedBrand)) {
	$SQL = "SELECT manufacturers_id, manufacturers_name, manufacturers_url, manufacturers_image FROM manufacturers WHERE manufacturers_id='" . $SelectedBrand . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$_POST['BrandsName'] = $MyRow['manufacturers_name'];
	$_POST['BrandsURL'] = $MyRow['manufacturers_url'];
	echo '<input type="hidden" name="SelectedBrand" value="' . $SelectedBrand . '" />';
}
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Brand Name') . '</label>
					<input type="text" name="BrandsName" class="db-input" required value="' . ($_POST['BrandsName'] ?? '') . '" placeholder="' . __('e.g. Acme Corp') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Website URL') . '</label>
					<input type="text" name="BrandsURL" class="db-input" value="' . ($_POST['BrandsURL'] ?? '') . '" placeholder="https://..." />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Brand Logo') . '</label>
					<input type="file" name="BrandPicture" class="db-input db-input-light" accept="image/*" />';
if (isset($SelectedBrand)) {
	echo '			<div style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
						<input type="checkbox" name="ClearImage" id="ClearImage" />
						<label class="db-label mb-0" for="ClearImage">' . __('Remove Current Image') . '</label>
					</div>';
}
echo '				</div>
				<div style="margin-top: 20px;">
					<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%;"><i class="fas fa-save"></i> ' . __('Save Brand Assets') . '</button>';
if (isset($SelectedBrand)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-input-light" style="width: 100%; margin-top: 10px; text-align: center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
}
echo '				</div>
			</form>
		</div>
	  </div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
$SQL = "SELECT manufacturers_id, manufacturers_name, manufacturers_url, manufacturers_image FROM manufacturers ORDER BY manufacturers_id";
$Result = DB_query($SQL);

if (DB_num_rows($Result) == 0) {
	echo '<div class="centre"><p class="db-muted">' . __('No brands configured yet.') . '</p></div>';
} else {
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-th-large"></i> ' . __('Brands Portfolio') . '</h3></div>
			<div class="db-card-body p-0">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Logo') . '</th>
								<th>' . __('Brand Info') . '</th>
								<th class="text-right">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		$Glob = (glob($_SESSION['part_pics_dir'] . '/BRAND-' . $MyRow['manufacturers_id'] . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE));
		$ImageFile = reset($Glob);
		$BrandImgLink = GetImageLink($ImageFile, '/BRAND-' . $MyRow['manufacturers_id'], 60, 60, "db-avatar db-avatar-lg", "");
		
		echo '<tr>
				<td style="width: 80px;">' . $BrandImgLink . '</td>
				<td>
					<div class="db-font-bold text-primary">' . $MyRow['manufacturers_name'] . '</div>
					<div class="db-muted" style="font-size: 0.8rem;"><a href="' . $MyRow['manufacturers_url'] . '" target="_blank">' . $MyRow['manufacturers_url'] . '</a></div>
				</td>
				<td class="text-right">
					<div style="display: flex; gap: 8px; justify-content: flex-end;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedBrand=' . $MyRow['manufacturers_id'] . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i></a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedBrand=' . $MyRow['manufacturers_id'] . '&amp;delete=1" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Are you sure?') . '\');"><i class="fas fa-trash"></i></a>
					</div>
				</td>
			  </tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';
}
echo '</main></div>';

include(__DIR__ . '/includes/footer.php');
