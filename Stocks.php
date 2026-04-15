<?php

// Defines an item - maintenance and addition of new parts.

require(__DIR__ . '/includes/session.php');

$Title = __('Item Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryAddingItems';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

/* If this form is called with the StockID then it is assumed that the stock item is to be modified */

if (isset($_GET['StockID'])) {
	$StockID = trim(mb_strtoupper($_GET['StockID']));
} elseif (isset($_POST['StockID'])) {
	$StockID = trim(mb_strtoupper($_POST['StockID']));
} else {
	$StockID = '';
}

if (empty($_SESSION['ItemDescriptionLanguages']) or $_SESSION['ItemDescriptionLanguages'] == '') {
	$_SESSION['ItemDescriptionLanguages'] = ',';
}
$ItemDescriptionLanguagesArray = explode(',', $_SESSION['ItemDescriptionLanguages']); //WARNING: if the last character is a ",", there are n+1 languages.
$HasNext = true;
$HasPrev = true;

if (isset($_POST['NextItem'])) {
	$Result = DB_query("SELECT stockid FROM stockmaster WHERE stockid>'" . $StockID . "' ORDER BY stockid ASC LIMIT 1");
	if (DB_num_rows($Result) > 0) {
		$NextItemRow = DB_fetch_row($Result);
		$StockID = $NextItemRow[0];
	} else {
		$HasNext = false;
	}
	foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
		unset($_POST['Description_' . str_replace('.', '_', $LanguageId) ]);
	}
}
if (isset($_POST['PreviousItem'])) {
	$Result = DB_query("SELECT stockid FROM stockmaster WHERE stockid<'" . $StockID . "' ORDER BY stockid DESC LIMIT 1");
	if (DB_num_rows($Result) > 0) {
		$PreviousItemRow = DB_fetch_row($Result);
		$StockID = $PreviousItemRow[0];
	} else {
		$HasPrev = false;
	}
	foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
		unset($_POST['Description_' . str_replace('.', '_', $LanguageId) ]);
	}
}

if (isset($StockID) and $StockID != '' and !isset($_POST['UpdateCategories'])) {
	$SQL = "SELECT COUNT(stockid) FROM stockmaster WHERE stockid='" . $StockID . "' GROUP BY stockid";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	$New = ($MyRow[0] == 0) ? 1 : 0;
} else {
	$New = 1;
}

if (isset($_POST['New'])) {
	$New = $_POST['New'];
}

// IMAGE HANDLING
$SupportedImgExt = array('png', 'jpg', 'jpeg');
if (isset($_FILES['ItemPicture']) and $_FILES['ItemPicture']['name'] != '') {
	$ImgExt = pathinfo($_FILES['ItemPicture']['name'], PATHINFO_EXTENSION);
	$UploadTheFile = 'Yes';
	$FileName = $_SESSION['part_pics_dir'] . '/' . $StockID . '.' . $ImgExt;
	if (!in_array($ImgExt, $SupportedImgExt)) {
		prnMsg(__('Only ' . implode(", ", $SupportedImgExt) . ' files are supported'), 'warn');
		$UploadTheFile = 'No';
	} elseif ($_FILES['ItemPicture']['size'] > ($_SESSION['MaxImageSize'] * 1024)) {
		prnMsg(__('File size over maximum allowed'), 'warn');
		$UploadTheFile = 'No';
	}
	if ($UploadTheFile == 'Yes') {
		foreach ($SupportedImgExt as $Ext) {
			$File = $_SESSION['part_pics_dir'] . '/' . $StockID . '.' . $Ext;
			if (file_exists($File)) unlink($File);
		}
		move_uploaded_file($_FILES['ItemPicture']['tmp_name'], $FileName);
	}
}

$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {
	$i = 1;
	if (!isset($_POST['Description']) or mb_strlen($_POST['Description']) > 50 or mb_strlen($_POST['Description']) == 0) {
		$InputError = 1;
		prnMsg(__('The stock item description must be entered and be fifty characters or less long'), 'error');
		$Errors[$i++] = 'Description';
	}
	if (mb_strlen($_POST['LongDescription']) == 0) {
		$InputError = 1;
		prnMsg(__('A long description is required'), 'error');
		$Errors[$i++] = 'LongDescription';
	}
	if (mb_strlen($StockID) == 0) {
		$InputError = 1;
		prnMsg(__('Stock code cannot be empty'), 'error');
		$Errors[$i++] = 'StockID';
	}
	if (ContainsIllegalCharacters($StockID) or mb_strpos($StockID, ' ')) {
		$InputError = 1;
		prnMsg(__('Stock code contains illegal characters'), 'error');
		$Errors[$i++] = 'StockID';
	}

	if ($InputError != 1) {
		if ($_POST['Serialised'] == 1) $_POST['DecimalPlaces'] = 0;
		if ($New == 0) {
			// EXISTING ITEM UPDATE LOGIC
			$SQL = "SELECT mbflag, controlled, serialised, actualcost, stockcategory.stockact, stockcategory.wipact, description, longdescription
					FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid
					WHERE stockid = '" . $StockID . "'";
			$CheckRes = DB_query($SQL);
			$OldData = DB_fetch_array($CheckRes);
			
			$ResCount = DB_query("SELECT SUM(locstock.quantity) FROM locstock WHERE stockid='" . $StockID . "' GROUP BY stockid");
			$QOHRow = DB_fetch_row($ResCount);
			$TotalQOH = $QOHRow[0] ?? 0;

			$ResNewCat = DB_query("SELECT stockact, wipact FROM stockcategory WHERE categoryid='" . $_POST['CategoryID'] . "'");
			$NewCatData = DB_fetch_array($ResNewCat);

			// COMPLEX VALIDATIONS (PRESERVED)
			if ($OldData['mbflag'] != $_POST['MBFlag']) {
				if (($OldData['mbflag'] == 'M' or $OldData['mbflag'] == 'B') and ($_POST['MBFlag'] == 'A' or $_POST['MBFlag'] == 'K' or $_POST['MBFlag'] == 'D' or $_POST['MBFlag'] == 'G')) {
					if ($TotalQOH != 0 and $OldData['mbflag'] != 'G') {
						$InputError = 1;
						prnMsg(__('Cannot change MB flag where there is stock on hand'), 'error');
					}
				}
			}

			if ($InputError == 0) {
				DB_Txn_Begin();
				$SQL = "UPDATE stockmaster SET longdescription='" . $_POST['LongDescription'] . "', description='" . $_POST['Description'] . "', discontinued='" . $_POST['Discontinued'] . "', controlled='" . $_POST['Controlled'] . "', serialised='" . $_POST['Serialised'] . "', perishable='" . $_POST['Perishable'] . "', categoryid='" . $_POST['CategoryID'] . "', units='" . $_POST['Units'] . "', mbflag='" . $_POST['MBFlag'] . "', eoq='" . filter_number_format($_POST['EOQ']) . "', volume='" . filter_number_format($_POST['Volume']) . "', grossweight='" . filter_number_format($_POST['GrossWeight']) . "', netweight='" . filter_number_format($_POST['NetWeight']) . "', barcode='" . $_POST['BarCode'] . "', discountcategory='" . $_POST['DiscountCategory'] . "', taxcatid='" . $_POST['TaxCat'] . "', decimalplaces='" . $_POST['DecimalPlaces'] . "', shrinkfactor='" . filter_number_format($_POST['ShrinkFactor']) . "', pansize='" . filter_number_format($_POST['Pansize']) . "', nextserialno='" . $_POST['NextSerialNo'] . "' WHERE stockid='" . $StockID . "'";
				DB_query($SQL, '', '', true);

				// Translation handling
				if (count($ItemDescriptionLanguagesArray) > 0) {
					foreach ($ItemDescriptionLanguagesArray as $LangId) {
						if ($LangId != '') {
							DB_query("DELETE FROM stockdescriptiontranslations WHERE stockid='" . $StockID . "' AND language_id='" . $LangId . "'", '', '', true);
							DB_query("INSERT INTO stockdescriptiontranslations (stockid, language_id, descriptiontranslation, longdescriptiontranslation) VALUES('" . $StockID . "','" . $LangId . "', '" . ($_POST['Description_' . str_replace('.', '_', $LangId)] ?? '') . "', '" . ($_POST['LongDescription_' . str_replace('.', '_', $LangId)] ?? '') . "')", '', '', true);
						}
					}
				}
				
				// Properties
				DB_query("DELETE FROM stockitemproperties WHERE stockid ='" . $StockID . "'", '', '', true);
				for ($j = 0; $j < ($_POST['PropertyCounter'] ?? 0); $j++) {
					$propVal = $_POST['PropValue' . $j] ?? '';
					if ($_POST['PropType' . $j] == 2) $propVal = ($propVal == 'on') ? 1 : 0;
					if ($_POST['PropNumeric' . $j] == 1) $propVal = filter_number_format($propVal);
					DB_query("INSERT INTO stockitemproperties (stockid, stkcatpropid, value) VALUES ('" . $StockID . "', '" . $_POST['PropID' . $j] . "', '" . $propVal . "')", '', '', true);
				}

				DB_Txn_Commit();
				prnMsg(__('Item') . ' ' . $StockID . ' ' . __('updated'), 'success');
			}
		} else {
			// NEW ITEM INSERT
			$CheckVal = DB_query("SELECT stockid FROM stockmaster WHERE stockid='" . $StockID . "'");
			if (DB_num_rows($CheckVal) == 1) {
				prnMsg(__('Duplicate stock code'), 'error');
				$InputError = 1;
			} else {
				DB_Txn_Begin();
				$SQL = "INSERT INTO stockmaster (stockid, description, longdescription, categoryid, units, mbflag, eoq, discontinued, controlled, serialised, perishable, volume, grossweight, netweight, barcode, discountcategory, taxcatid, decimalplaces, shrinkfactor, pansize)
						VALUES ('" . $StockID . "', '" . $_POST['Description'] . "', '" . $_POST['LongDescription'] . "', '" . $_POST['CategoryID'] . "', '" . $_POST['Units'] . "', '" . $_POST['MBFlag'] . "', '" . filter_number_format($_POST['EOQ']) . "', '" . $_POST['Discontinued'] . "', '" . $_POST['Controlled'] . "', '" . $_POST['Serialised'] . "', '" . $_POST['Perishable'] . "', '" . filter_number_format($_POST['Volume']) . "', '" . filter_number_format($_POST['GrossWeight']) . "', '" . filter_number_format($_POST['NetWeight']) . "', '" . $_POST['BarCode'] . "', '" . $_POST['DiscountCategory'] . "', '" . $_POST['TaxCat'] . "', '" . $_POST['DecimalPlaces'] . "', '" . filter_number_format($_POST['ShrinkFactor']) . "', '" . filter_number_format($_POST['Pansize']) . "')";
				DB_query($SQL, '', '', true);
				
				// Insert locstock for all locations
				DB_query("INSERT INTO locstock (loccode, stockid) SELECT locations.loccode, '" . $StockID . "' FROM locations", '', '', true);
				
				DB_Txn_Commit();
				prnMsg(__('New Item') . ' ' . $StockID . ' ' . __('added'), 'success');
				unset($StockID); $New = 1;
			}
		}
	}
}

// UI HEADER
echo '<div class="db-page">
		<div class="db-page-header" style="margin-bottom: 20px;">
			<div class="db-page-title" style="display: flex; align-items: center; gap: 12px;">
				<i class="fas fa-box-open"></i> ' . $Title . '
				' . ($StockID != '' ? '<span class="db-badge db-badge-primary" style="font-size: 0.9rem; padding: 4px 12px;">' . $StockID . '</span>' : '') . '
			</div>
			<div class="db-page-actions">';
if (isset($StockID) && $StockID != '' && $InputError == 0) {
	echo '		<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display:inline-block">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<input type="hidden" name="StockID" value="' . $StockID . '" />
					<button ' . ($HasPrev ? '' : 'disabled') . ' name="PreviousItem" type="submit" class="db-btn db-btn-outline db-btn-small" title="' . __('Prev') . '"><i class="fas fa-chevron-left"></i></button>
					<button ' . ($HasNext ? '' : 'disabled') . ' name="NextItem" type="submit" class="db-btn db-btn-outline db-btn-small" title="' . __('Next') . '"><i class="fas fa-chevron-right"></i></button>
				</form>';
}
echo '			<a href="' . $RootPath . '/SelectProduct.php" class="db-btn db-btn-outline db-btn-small"><i class="fas fa-search"></i> ' . __('Catalogue') . '</a>
			</div>
		</div>';

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';

// ASSET CARD
if ($StockID != '') {
	$ImgGlob = glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE);
	$Pic = reset($ImgGlob);
	echo '<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-body text-center p-4">
				<div style="width: 160px; height: 160px; margin: 0 auto 15px; background: var(--bg-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--border-soft);">';
	if ($Pic) {
		echo '<img src="' . $Pic . '" style="max-width: 100%; max-height: 100%; object-fit: contain;" />';
	} else {
		echo '<i class="fas fa-image fa-4x db-muted"></i>';
	}
	echo '		</div>
				<form enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<input type="hidden" name="StockID" value="' . $StockID . '" />
					<input type="file" name="ItemPicture" id="PicUp" style="display: none;" onchange="this.form.submit()" />
					<button type="button" class="db-btn db-btn-sm db-input-light" onclick="document.getElementById(\'PicUp\').click()"><i class="fas fa-upload"></i> ' . __('Update Photo') . '</button>
				</form>
			</div>
		  </div>';
}

// STATUS CARD
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Attribute Flags') . '</h3></div>
		<div class="db-card-body p-0">
			<form method="post" id="StockForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

if (isset($StockID) && $StockID != '') {
	$ResMaster = DB_query("SELECT * FROM stockmaster WHERE stockid='" . $StockID . "'");
	$Master = DB_fetch_array($ResMaster);
	$_POST = array_merge($_POST, $Master);
	echo '<input type="hidden" name="StockID" value="' . $StockID . '" />';
	echo '<input type="hidden" name="New" value="0" />';
} else {
	echo '<input type="hidden" name="New" value="1" />';
}

$Flags = array(
	'Discontinued' => array('icon' => 'fa-ban', 'label' => __('Discontinued')),
	'Controlled' => array('icon' => 'fa-barcode', 'label' => __('Batch / Lot Controlled')),
	'Serialised' => array('icon' => 'fa-hashtag', 'label' => __('Individual Serial Nos')),
	'Perishable' => array('icon' => 'fa-clock', 'label' => __('Perishable Item'))
);

echo '<div style="padding: 15px;">';
foreach ($Flags as $fKey => $fData) {
	$val = ($_POST[$fKey] ?? 0);
	echo '<div class="db-form-group d-flex align-items-center justify-content-between mb-3" style="display: flex; justify-content: space-between;">
			<div style="display: flex; align-items: center; gap: 10px;">
				<i class="fas ' . $fData['icon'] . ' db-muted" style="width: 20px;"></i>
				<label class="db-label mb-0">' . $fData['label'] . '</label>
			</div>
			<select name="' . $fKey . '" class="db-select db-select-sm" style="width: 80px;">
				<option value="1" ' . ($val == 1 ? 'selected' : '') . '>' . __('Yes') . '</option>
				<option value="0" ' . ($val == 0 ? 'selected' : '') . '>' . __('No') . '</option>
			</select>
		  </div>';
}
echo '</div>';

echo '</div></div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';

// CORE IDENTITY CARD
echo '<div class="db-card shadow-sm" style="margin-bottom: 25px;">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-id-card"></i> ' . __('Product Specification') . '</h3></div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">';

if ($New) {
	echo '<div class="db-form-group">
			<label class="db-label">' . __('Item SKU') . ' *</label>
			<input type="text" name="StockID" class="db-input" required maxlength="20" autofocus value="' . ($StockID ?? '') . '" />
		  </div>';
}

echo '		<div class="db-form-group">
				<label class="db-label">' . __('Short Name') . ' *</label>
				<input type="text" name="Description" class="db-input" required maxlength="50" value="' . ($_POST['Description'] ?? '') . '" />
			</div>
			<div class="db-form-group">
				<label class="db-label">' . __('Classification') . '</label>
				<select name="CategoryID" class="db-select" onchange="document.getElementById(\'StockForm\').submit()">';
$CatsRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
while ($c = DB_fetch_array($CatsRes)) {
	echo '<option ' . (($_POST['CategoryID'] ?? '') == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
}
echo '			</select>
			</div>
			<div class="db-form-group">
				<label class="db-label">' . __('Make or Buy Flag') . '</label>
				<select name="MBFlag" class="db-select">
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'B' ? 'selected' : '') . ' value="B">' . __('Purchased') . '</option>
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'M' ? 'selected' : '') . ' value="M">' . __('Manufactured') . '</option>
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'A' ? 'selected' : '') . ' value="A">' . __('Assembly') . '</option>
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'K' ? 'selected' : '') . ' value="K">' . __('Kit Set') . '</option>
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'D' ? 'selected' : '') . ' value="D">' . __('Service / Labour') . '</option>
					<option ' . (($_POST['MBFlag'] ?? 'B') == 'G' ? 'selected' : '') . ' value="G">' . __('Phantom / Ghost') . '</option>
				</select>
			</div>
		  </div>
		  <div class="db-form-group mt-3">
		  	<label class="db-label">' . __('Technical / Marketing Description') . '</label>
			<textarea name="LongDescription" class="db-input" rows="4">' . ($_POST['LongDescription'] ?? '') . '</textarea>
		  </div>
		</div>
	  </div>';

// LOGISTICS CARD
echo '<div class="db-card shadow-sm" style="margin-bottom: 25px;">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Logistics & Units') . '</h3></div>
		<div class="db-card-body">
			<div class="db-grid db-grid-4">
				<div class="db-form-group">
					<label class="db-label">' . __('UOM') . '</label>
					<select name="Units" class="db-select">';
$UnitsRes = DB_query("SELECT unitname FROM unitsofmeasure");
while ($u = DB_fetch_array($UnitsRes)) {
	echo '<option ' . (($_POST['Units'] ?? 'each') == $u['unitname'] ? 'selected' : '') . ' value="' . $u['unitname'] . '">' . $u['unitname'] . '</option>';
}
echo '				</select>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Precision') . '</label>
					<select name="DecimalPlaces" class="db-select">
						<option value="0" ' . (($_POST['DecimalPlaces'] ?? 0) == 0 ? 'selected' : '') . '>0</option>
						<option value="1" ' . (($_POST['DecimalPlaces'] ?? 0) == 1 ? 'selected' : '') . '>1</option>
						<option value="2" ' . (($_POST['DecimalPlaces'] ?? 0) == 2 ? 'selected' : '') . '>2</option>
						<option value="3" ' . (($_POST['DecimalPlaces'] ?? 0) == 3 ? 'selected' : '') . '>3</option>
						<option value="4" ' . (($_POST['DecimalPlaces'] ?? 0) == 4 ? 'selected' : '') . '>4</option>
					</select>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Barcode') . '</label>
					<input type="text" name="BarCode" class="db-input" maxlength="20" value="' . ($_POST['BarCode'] ?? '') . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Standard EOQ') . '</label>
					<input type="number" step="any" name="EOQ" class="db-input text-right" value="' . filter_number_format($_POST['EOQ'] ?? 0) . '" />
				</div>
			</div>
			<div class="db-grid db-grid-3 mt-3" style="padding: 15px; background: var(--bg-soft); border-radius: 8px;">
				<div class="db-form-group">
					<label class="db-label">' . __('Volume (m3)') . '</label>
					<input type="number" step="any" name="Volume" class="db-input text-right" value="' . filter_number_format($_POST['Volume'] ?? 0) . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Gross Weight (kg)') . '</label>
					<input type="number" step="any" name="GrossWeight" class="db-input text-right" value="' . filter_number_format($_POST['GrossWeight'] ?? 0) . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Net Weight (kg)') . '</label>
					<input type="number" step="any" name="NetWeight" class="db-input text-right" value="' . filter_number_format($_POST['NetWeight'] ?? 0) . '" />
				</div>
			</div>
			<div class="db-grid db-grid-3 mt-3">
				<div class="db-form-group">
					<label class="db-label">' . __('Next Serial No') . '</label>
					<input type="number" name="NextSerialNo" class="db-input text-right" value="' . ($_POST['NextSerialNo'] ?? 0) . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Tax Category') . '</label>
					<select name="TaxCat" class="db-select">';
$TaxRes = DB_query("SELECT taxcatid, taxcatname FROM taxcategories");
while ($t = DB_fetch_array($TaxRes)) {
	echo '<option ' . (($_POST['TaxCat'] ?? '') == $t['taxcatid'] ? 'selected' : '') . ' value="' . $t['taxcatid'] . '">' . $t['taxcatname'] . '</option>';
}
echo '				</select>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Discount Group') . '</label>
					<input type="text" name="DiscountCategory" class="db-input" maxlength="3" value="' . ($_POST['DiscountCategory'] ?? '') . '" />
				</div>
			</div>
		</div>
	  </div>';

// DYNAMIC PROPERTIES CARD
if (isset($_POST['CategoryID'])) {
	$PropSQL = "SELECT stkcatpropid, label, controltype, defaultvalue, numericvalue, minimumvalue, maximumvalue
				FROM stockcatproperties WHERE categoryid = '" . $_POST['CategoryID'] . "' ORDER BY label";
	$PropRes = DB_query($PropSQL);
	if (DB_num_rows($PropRes) > 0) {
		echo '<div class="db-card shadow-sm" style="margin-bottom: 25px;">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Extended Specifications') . '</h3></div>
				<div class="db-card-body">
					<div class="db-grid db-grid-2">';
		$k = 0;
		while ($pRow = DB_fetch_array($PropRes)) {
			$valRes = DB_query("SELECT value FROM stockitemproperties WHERE stockid='" . $StockID . "' AND stkcatpropid='" . $pRow['stkcatpropid'] . "'");
			$valRow = DB_fetch_array($valRes);
			$currentVal = $valRow['value'] ?? $pRow['defaultvalue'];
			
			echo '<div class="db-form-group">
					<label class="db-label">' . $pRow['label'] . '</label>
					<input type="hidden" name="PropID' . $k . '" value="' . $pRow['stkcatpropid'] . '" />
					<input type="hidden" name="PropType' . $k . '" value="' . $pRow['controltype'] . '" />
					<input type="hidden" name="PropNumeric' . $k . '" value="' . $pRow['numericvalue'] . '" />';
			if ($pRow['controltype'] == 2) { // checkbox
				echo '<div style="display: flex; align-items: center; gap: 8px;">
						<input type="checkbox" name="PropValue' . $k . '" ' . ($currentVal == 1 ? 'checked' : '') . ' />
						<span class="db-muted" style="font-size: 0.8rem;">' . __('Enabled') . '</span>
					  </div>';
			} else {
				echo '<input type="' . ($pRow['numericvalue'] == 1 ? 'number' : 'text') . '" step="any" name="PropValue' . $k . '" class="db-input" value="' . $currentVal . '" />';
			}
			echo '</div>';
			$k++;
		}
		echo '<input type="hidden" name="PropertyCounter" value="' . $k . '" />';
		echo '		</div>
				</div>
			  </div>';
	}
}

echo '<div style="margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end;">
		<button type="submit" name="submit" class="db-btn db-btn-primary" style="padding: 12px 40px; font-weight: bold;"><i class="fas fa-save"></i> ' . __('Sync Item Details') . '</button>
	  </div>';

echo '	</form>';
echo '</main></div></div>';

include(__DIR__ . '/includes/footer.php');
