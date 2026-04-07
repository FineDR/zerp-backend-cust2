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

	// Only change the StockID if we find a row.
	// If not, the StockID is 'clobbered' with null and causes form havoc.
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

	// Only change the StockID if we find a row.
	// If not, the StockID is 'clobbered' with null and causes form havoc.
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
	$SQL = "SELECT COUNT(stockid)
			FROM stockmaster
			WHERE stockid='" . $StockID . "'
			GROUP BY stockid";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] == 0) {
		$New = 1;
	} else {
		$New = 0;
	}
} else {
	$New = 1;
}

if (isset($_POST['New'])) {
	$New = $_POST['New'];
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-boxes"></i> ' . $Title . '
			</div>
			<div class="db-page-actions">';

if (isset($StockID) && $StockID != '' && $InputError == 0) {
	echo '		<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display:inline-block">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<input type="hidden" name="StockID" value="' . $StockID . '" />
					<button ' . ($HasPrev ? '' : 'disabled') . ' name="PreviousItem" type="submit" class="db-btn db-btn-outline db-btn-small" title="' . __('Previous Item') . '"><i class="fas fa-chevron-left"></i></button>
					<button ' . ($HasNext ? '' : 'disabled') . ' name="NextItem" type="submit" class="db-btn db-btn-outline db-btn-small" title="' . __('Next Item') . '"><i class="fas fa-chevron-right"></i></button>
				</form>';
}

echo '			<a href="' . $RootPath . '/SelectProduct.php" class="db-btn db-btn-outline db-btn-small"><i class="fas fa-list"></i> ' . __('Back to Items') . '</a>
			</div>
		</div><br />';
$SupportedImgExt = array('png', 'jpg', 'jpeg');

if (isset($_FILES['ItemPicture']) and $_FILES['ItemPicture']['name'] != '') {
	$ImgExt = pathinfo($_FILES['ItemPicture']['name'], PATHINFO_EXTENSION);

	$Result = $_FILES['ItemPicture']['error'];
	$UploadTheFile = 'Yes'; //Assume all is well to start off with
	$FileName = $_SESSION['part_pics_dir'] . '/' . $StockID . '.' . $ImgExt;
	//But check for the worst
	if (!in_array($ImgExt, $SupportedImgExt)) {
		prnMsg(__('Only ' . implode(", ", $SupportedImgExt) . ' files are supported - a file extension of ' . implode(", ", $SupportedImgExt) . ' is expected'), 'warn');
		$UploadTheFile = 'No';
	} elseif ($_FILES['ItemPicture']['size'] > ($_SESSION['MaxImageSize'] * 1024)) { //File Size Check
		prnMsg(__('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . $_SESSION['MaxImageSize'], 'warn');
		$UploadTheFile = 'No';
	} elseif ($_FILES['ItemPicture']['type'] == 'text/plain') { //File Type Check
		prnMsg(__('Only graphics files can be uploaded'), 'warn');
		$UploadTheFile = 'No';
	} elseif ($_FILES['ItemPicture']['error'] == 6) { //upload temp directory check
		prnMsg(__('No tmp directory set. You must have a tmp directory set in your PHP for upload of files. '), 'warn');
		$UploadTheFile = 'No';
	} elseif (!is_writable($_SESSION['part_pics_dir'])) {
		prnMsg(__('The web server user does not have permission to upload files. Please speak to your system administrator'), 'warn');
		$UploadTheFile = 'No';
	}
	foreach ($SupportedImgExt as $Ext) {
		$File = $_SESSION['part_pics_dir'] . '/' . $StockID . '.' . $Ext;
		if (file_exists($File)) {
			$Result = unlink($File);
			if (!$Result) {
				prnMsg(__('The existing image could not be removed'), 'error');
				$UploadTheFile = 'No';
			}
		}
	}

	if ($UploadTheFile == 'Yes') {
		$Result = move_uploaded_file($_FILES['ItemPicture']['tmp_name'], $FileName);
		$Message = ($Result) ? __('File url') . '<a href="' . $FileName . '">' . $FileName . '</a>' : __('Something is wrong with uploading a file');
	}
}

$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$i = 1;

	if (!isset($_POST['Description']) or mb_strlen($_POST['Description']) > 50 or mb_strlen($_POST['Description']) == 0) {
		$InputError = 1;
		prnMsg(__('The stock item description must be entered and be fifty characters or less long') . '. ' . __('It cannot be a zero length string either') . ' - ' . __('a description is required'), 'error');
		$Errors[$i] = 'Description';
		$i++;
	}
	if (mb_strlen($_POST['LongDescription']) == 0) {
		$InputError = 1;
		prnMsg(__('The stock item description cannot be a zero length string') . ' - ' . __('a long description is required'), 'error');
		$Errors[$i] = 'LongDescription';
		$i++;
	}
	if (mb_strlen($StockID) == 0) {
		$InputError = 1;
		prnMsg(__('The Stock Item code cannot be empty'), 'error');
		$Errors[$i] = 'StockID';
		$i++;
	}
	if (ContainsIllegalCharacters($StockID) or mb_strpos($StockID, ' ')) {
		$InputError = 1;
		prnMsg(__('The stock item code cannot contain any of the following characters') . " - ' &amp; + \" \\ ." . __('or a space'), 'error');
		$Errors[$i] = 'StockID';
		$i++;
		$StockID = '';
	}
	if (mb_strlen($_POST['Units']) > 20) {
		$InputError = 1;
		prnMsg(__('The unit of measure must be 20 characters or less long'), 'error');
		$Errors[$i] = 'Units';
		$i++;
	}
	if (mb_strlen($_POST['BarCode']) > 20) {
		$InputError = 1;
		prnMsg(__('The barcode must be 20 characters or less long'), 'error');
		$Errors[$i] = 'BarCode';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['Volume']))) {
		$InputError = 1;
		prnMsg(__('The volume of the packaged item in cubic metres must be numeric'), 'error');
		$Errors[$i] = 'Volume';
		$i++;
	}
	if (filter_number_format($_POST['Volume']) < 0) {
		$InputError = 1;
		prnMsg(__('The volume of the packaged item must be a positive number'), 'error');
		$Errors[$i] = 'Volume';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['GrossWeight']))) {
		$InputError = 1;
		prnMsg(__('The weight of the packaged item in Gross Weight must be numeric'), 'error');
		$Errors[$i] = 'GrossWeight';
		$i++;
	}
	if (filter_number_format($_POST['GrossWeight']) < 0) {
		$InputError = 1;
		prnMsg(__('The weight of the packaged item must be a positive number'), 'error');
		$Errors[$i] = 'GrossWeight';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['NetWeight']))) {
		$InputError = 1;
		prnMsg(__('The net weight of the item in Net Weight must be numeric'), 'error');
		$Errors[$i] = 'NetWeight';
		$i++;
	}
	if (filter_number_format($_POST['NetWeight']) < 0) {
		$InputError = 1;
		prnMsg(__('The net weight of the item must be a positive number'), 'error');
		$Errors[$i] = 'NetWeight';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['EOQ']))) {
		$InputError = 1;
		prnMsg(__('The economic order quantity must be numeric'), 'error');
		$Errors[$i] = 'EOQ';
		$i++;
	}
	if (filter_number_format($_POST['EOQ']) < 0) {
		$InputError = 1;
		prnMsg(__('The economic order quantity must be a positive number'), 'error');
		$Errors[$i] = 'EOQ';
		$i++;
	}
	if ($_POST['Controlled'] == 0 and $_POST['Serialised'] == 1) {
		$InputError = 1;
		prnMsg(__('The item can only be serialised if there is lot control enabled already') . '. ' . __('Batch control') . ' - ' . __('with any number of items in a lot/bundle/roll is enabled when controlled is enabled') . '. ' . __('Serialised control requires that only one item is in the batch') . '. ' . __('For serialised control') . ', ' . __('both controlled and serialised must be enabled'), 'error');
		$Errors[$i] = 'Serialised';
		$i++;
	}
	if ($_POST['NextSerialNo'] != 0 and $_POST['Serialised'] == 0) {
		$InputError = 1;
		prnMsg(__('The item can only have automatically generated serial numbers if it is a serialised item'), 'error');
		$Errors[$i] = 'NextSerialNo';
		$i++;
	}
	if ($_POST['NextSerialNo'] != 0 and $_POST['MBFlag'] != 'M') {
		$InputError = 1;
		prnMsg(__('The item can only have automatically generated serial numbers if it is a manufactured item'), 'error');
		$Errors[$i] = 'NextSerialNo';
		$i++;
	}
	if (($_POST['MBFlag'] == 'A' or $_POST['MBFlag'] == 'K' or $_POST['MBFlag'] == 'D' or $_POST['MBFlag'] == 'G') and $_POST['Controlled'] == 1) {

		$InputError = 1;
		prnMsg(__('Assembly/Kitset/Phantom/Service/Labour items cannot also be controlled items') . '. ' . __('Assemblies/Dummies/Phantom and Kitsets are not physical items and batch/serial control is therefore not appropriate'), 'error');
		$Errors[$i] = 'Controlled';
		$i++;
	}
	if (trim($_POST['CategoryID']) == '') {
		$InputError = 1;
		prnMsg(__('There are no inventory categories defined. All inventory items must belong to a valid inventory category,'), 'error');
		$Errors[$i] = 'CategoryID';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['Pansize']))) {
		$InputError = 1;
		prnMsg(__('Pansize quantity must be numeric'), 'error');
		$Errors[$i] = 'Pansize';
		$i++;
	}
	if (!is_numeric(filter_number_format($_POST['ShrinkFactor']))) {
		$InputError = 1;
		prnMsg(__('Shrinkage factor quantity must be numeric'), 'error');
		$Errors[$i] = 'ShrinkFactor';
		$i++;
	}

	if ($InputError != 1) {
		if ($_POST['Serialised'] == 1) { /*Not appropriate to have several dp on serial items */
			$_POST['DecimalPlaces'] = 0;
		}
		if ($New == 0) { /*so its an existing one */

			/*first check on the changes being made we must disallow:
			- changes from manufactured or purchased to Service, Assembly or Kitset if there is stock			- changes from manufactured, kitset or assembly where a BOM exists
			*/
			$SQL = "SELECT mbflag,
							controlled,
							serialised,
							actualcost AS itemcost,
							stockcategory.stockact,
							stockcategory.wipact,
							description,
							longdescription
					FROM stockmaster
					INNER JOIN stockcategory
					ON stockmaster.categoryid=stockcategory.categoryid
					WHERE stockid = '" . $StockID . "'";
			$MBFlagResult = DB_query($SQL);
			$MyRow = DB_fetch_row($MBFlagResult);
			$OldMBFlag = $MyRow[0];
			$OldControlled = $MyRow[1];
			$OldSerialised = $MyRow[2];
			$UnitCost = $MyRow[3];
			$OldStockAccount = $MyRow[4];
			$OldWIPAccount = $MyRow[5];
			$OldDescription = $MyRow[6];
			$OldLongDescription = $MyRow[7];

			$SQL = "SELECT SUM(locstock.quantity)
					FROM locstock
					WHERE stockid='" . $StockID . "'
					GROUP BY stockid";
			$Result = DB_query($SQL);
			$StockQtyRow = DB_fetch_row($Result);

			/*Now check the GL account of the new category to see if it is different to the old stock gl account */

			$Result = DB_query("SELECT stockact,
										wipact
								FROM stockcategory
								WHERE categoryid='" . $_POST['CategoryID'] . "'");
			$NewStockActRow = DB_fetch_array($Result);
			$NewStockAct = $NewStockActRow['stockact'];
			$NewWIPAct = $NewStockActRow['wipact'];

			if ($OldMBFlag != $_POST['MBFlag']) {
				if (($OldMBFlag == 'M' or $OldMBFlag == 'B') and ($_POST['MBFlag'] == 'A' or $_POST['MBFlag'] == 'K' or $_POST['MBFlag'] == 'D' or $_POST['MBFlag'] == 'G')) { /*then need to check that there is no stock holding first */
					/* stock holding OK for phantom (ghost) items */
					if ($StockQtyRow[0] != 0 and $OldMBFlag != 'G') {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed from') . ' ' . $OldMBFlag . ' ' . __('to') . ' ' . $_POST['MBFlag'] . ' ' . __('where there is a quantity of stock on hand at any location') . '. ' . __('Currently there are') . ' ' . $StockQtyRow[0] . ' ' . __('on hand'), 'errror');
					}
					/* don't allow controlled/serialized  */
					if ($_POST['Controlled'] == 1) {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed from') . ' ' . $OldMBFlag . ' ' . __('to') . ' ' . $_POST['MBFlag'] . ' ' . __('where the item is to be lot controlled') . '. ' . __('Kitset, phantom, dummy and assembly items cannot be lot controlled'), 'error');
					}
				}
				/*now check that if the item is being changed to a kitset, there are no items on sales orders or purchase orders*/
				if ($_POST['MBFlag'] == 'K') {
					$SQL = "SELECT quantity-qtyinvoiced
							FROM salesorderdetails
							WHERE stkcode = '" . $StockID . "'
							AND completed=0";

					$Result = DB_query($SQL);
					$ChkSalesOrds = DB_fetch_row($Result);
					if ($ChkSalesOrds[0] != 0) {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed to a kitset where there is a quantity outstanding to be delivered on sales orders') . '. ' . __('Currently there are') . ' ' . $ChkSalesOrds[0] . ' ' . __('outstanding'), 'error');
					}
				}
				/*now check that if it is to be a kitset or assembly or dummy there is no quantity on purchase orders outstanding*/
				if ($_POST['MBFlag'] == 'K' or $_POST['MBFlag'] == 'A' or $_POST['MBFlag'] == 'D') {

					$SQL = "SELECT quantityord-quantityrecd
							FROM purchorderdetails INNER JOIN purchorders
							ON purchorders.orderno=purchorderdetails.orderno
							WHERE itemcode = '" . $StockID . "'
							AND purchorderdetails.completed=0
							AND purchorders.status<>'Cancelled'
							AND purchorders.status<>'Completed'
							AND purchorders.status<>'Rejected'";

					$Result = DB_query($SQL);
					$ChkPurchOrds = DB_fetch_row($Result);
					if ($ChkPurchOrds[0] != 0) {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed to') . ' ' . $_POST['MBFlag'] . ' ' . __('where there is a quantity outstanding to be received on purchase orders') . '. ' . __('Currently there are') . ' ' . $ChkPurchOrds[0] . ' ' . __('yet to be received') . 'error');
					}
				}

				/*now check that if it was a Manufactured, Kitset, Phantom or Assembly and is being changed to a purchased or dummy - that no BOM exists */
				if (($OldMBFlag == 'M' or $OldMBFlag == 'K' or $OldMBFlag == 'A' or $OldMBFlag == 'G') and ($_POST['MBFlag'] == 'B' or $_POST['MBFlag'] == 'D')) {
					$SQL = "SELECT COUNT(*)
							FROM bom
							WHERE parent = '" . $StockID . "'
							GROUP BY parent";
					$Result = DB_query($SQL);
					$ChkBOM = DB_fetch_row($Result);
					if ($ChkBOM[0] != 0) {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed from manufactured, kitset or assembly to') . ' ' . $_POST['MBFlag'] . ' ' . __('where there is a bill of material set up for the item') . '. ' . __('Bills of material are not appropriate for purchased or dummy items'), 'error');
					}
				}

				/*now check that if it was Manufac, Phantom or Purchased and is being changed to assembly or kitset, it is not a component on an existing BOM */
				if (($OldMBFlag == 'M' or $OldMBFlag == 'B' or $OldMBFlag == 'D' or $OldMBFlag == 'G') and ($_POST['MBFlag'] == 'A' or $_POST['MBFlag'] == 'K')) {
					$SQL = "SELECT COUNT(*)
							FROM bom
							WHERE component = '" . $StockID . "'
							GROUP BY component";
					$Result = DB_query($SQL);
					$ChkBOM = DB_fetch_row($Result);
					if ($ChkBOM[0] != 0) {
						$InputError = 1;
						prnMsg(__('The make or buy flag cannot be changed from manufactured, purchased or dummy to a kitset or assembly where the item is a component in a bill of material') . '. ' . __('Assembly and kitset items are not appropriate as components in a bill of materials'), 'error');
					}
				}
			}

			/* Do some checks for changes in the Serial & Controlled setups */
			if ($OldControlled != $_POST['Controlled'] and $StockQtyRow[0] != 0) {
				$InputError = 1;
				prnMsg(__('You can not change a Non-Controlled Item to Controlled (or back from Controlled to non-controlled when there is currently stock on hand for the item'), 'error');

			}
			if ($OldSerialised != $_POST['Serialised'] and $StockQtyRow[0] != 0) {
				$InputError = 1;
				prnMsg(__('You can not change a Serialised Item to Non-Serialised (or vice-versa) when there is a quantity on hand for the item'), 'error');
			}
			/* Do some check for property input */

			for ($i = 0;$i < $_POST['PropertyCounter'];$i++) {
				if ($_POST['PropNumeric' . $i] == 1) {
					if (filter_number_format($_POST['PropValue' . $i]) < $_POST['PropMin' . $i] or filter_number_format($_POST['PropValue' . $i]) > $_POST['PropMax' . $i]) {
						$InputError = 1;
						prnMsg(__('The property value should between') . ' ' . $_POST['PropMin' . $i] . ' ' . __('and') . $_POST['PropMax' . $i], 'error');
					}
				}
			}

			if ($InputError == 0) {

				DB_Txn_Begin();

				$SQL = "UPDATE stockmaster
						SET longdescription='" . $_POST['LongDescription'] . "',
							description='" . $_POST['Description'] . "',
							discontinued='" . $_POST['Discontinued'] . "',
							controlled='" . $_POST['Controlled'] . "',
							serialised='" . $_POST['Serialised'] . "',
							perishable='" . $_POST['Perishable'] . "',
							categoryid='" . $_POST['CategoryID'] . "',
							units='" . $_POST['Units'] . "',
							mbflag='" . $_POST['MBFlag'] . "',
							eoq='" . filter_number_format($_POST['EOQ']) . "',
							volume='" . filter_number_format($_POST['Volume']) . "',
							grossweight='" . filter_number_format($_POST['GrossWeight']) . "',
							netweight='" . filter_number_format($_POST['NetWeight']) . "',
							barcode='" . $_POST['BarCode'] . "',
							discountcategory='" . $_POST['DiscountCategory'] . "',
							taxcatid='" . $_POST['TaxCat'] . "',
							decimalplaces='" . $_POST['DecimalPlaces'] . "',
							shrinkfactor='" . filter_number_format($_POST['ShrinkFactor']) . "',
							pansize='" . filter_number_format($_POST['Pansize']) . "',
							nextserialno='" . $_POST['NextSerialNo'] . "'
					WHERE stockid='" . $StockID . "'";

				$ErrMsg = __('The stock item could not be updated because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$ErrMsg = __('Could not update the language description because');

				if (count($ItemDescriptionLanguagesArray) > 0) {
					foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
						if ($LanguageId != '') {
							$Result = DB_query("DELETE FROM stockdescriptiontranslations WHERE stockid='" . $StockID . "' AND language_id='" . $LanguageId . "'", $ErrMsg, '', true);
							$Result = DB_query("INSERT INTO stockdescriptiontranslations (stockid,
																						language_id,
																						descriptiontranslation,
																						longdescriptiontranslation)
												VALUES('" . $StockID . "','" . $LanguageId . "', '" . $_POST['Description_' . str_replace('.', '_', $LanguageId) ] . "', '" . $_POST['LongDescription_' . str_replace('.', '_', $LanguageId) ] . "')", $ErrMsg, '', true);
						}
					}
					/*
					foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
						$DescriptionTranslation = $_POST['Description_' . str_replace('.', '_', $LanguageId)];
							//WARNING: It DOES NOT update if database row DOES NOT exist.
							$SQL = "UPDATE stockdescriptiontranslations " .
									"SET descriptiontranslation='" . $DescriptionTranslation . "' " .
									"WHERE stockid='" . $StockID . "' AND (language_id='" . $LanguageId. "')";
							$Result = DB_query($SQL, $ErrMsg, '', true);
					}
					*/

				}

				/* Activate the needs revision flag for translations for modified descriptions */
				if ($OldDescription != $_POST['Description'] or $OldLongDescription != $_POST['LongDescription']) {
					$SQL = "UPDATE stockdescriptiontranslations
						SET needsrevision = '0'
						WHERE stockid='" . $StockID . "'";
					$ErrMsg = __('The stock description translations could not be updated because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

				//delete any properties for the item no longer relevant with the change of category
				$Result = DB_query("DELETE FROM stockitemproperties WHERE stockid ='" . $StockID . "'", $ErrMsg, '', true);

				//now insert any item properties
				for ($i = 0;$i < $_POST['PropertyCounter'];$i++) {

					if ($_POST['PropType' . $i] == 2) {
						if ($_POST['PropValue' . $i] == 'on') {
							$_POST['PropValue' . $i] = 1;
						} else {
							$_POST['PropValue' . $i] = 0;
						}
					}
					if ($_POST['PropNumeric' . $i] == 1) {
						$_POST['PropValue' . $i] = filter_number_format($_POST['PropValue' . $i]);
					} /*else {
						$_POST['PropValue' . $i] = $_POST['PropValue' . $i];
					}*/
					$Result = DB_query("INSERT INTO stockitemproperties (stockid,
																		stkcatpropid,
																		value)
														VALUES ('" . $StockID . "',
																'" . $_POST['PropID' . $i] . "',
																'" . $_POST['PropValue' . $i] . "')", $ErrMsg, '', true);
				} //end of loop around properties defined for the category
				if ($OldStockAccount != $NewStockAct and $_SESSION['CompanyRecord']['gllink_stock'] == 1) {
					/*Then we need to make a journal to transfer the cost to the new stock account */
					$JournalNo = GetNextTransNo(0); //enter as a journal
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES ( 0,
												'" . $JournalNo . "',
												CURRENT_DATE,
												'" . GetPeriod(date($_SESSION['DefaultDateFormat'])) . "',
												'" . $NewStockAct . "',
												'" . mb_substr($StockID . ' ' . __('Change stock category'), 0, 200) . "',
												'" . ($UnitCost * $StockQtyRow[0]) . "')";
					$ErrMsg = __('The stock cost journal could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES ( 0,
												'" . $JournalNo . "',
												CURRENT_DATE,
												'" . GetPeriod(date($_SESSION['DefaultDateFormat'])) . "',
												'" . $OldStockAccount . "',
												'" . mb_substr($StockID . ' ' . __('Change stock category'), 0, 200) . "',
												'" . (-$UnitCost * $StockQtyRow[0]) . "')";
					$Result = DB_query($SQL, $ErrMsg, '', true);

				} /* end if the stock category changed and forced a change in stock cost account */
				if ($OldWIPAccount != $NewWIPAct and $_SESSION['CompanyRecord']['gllink_stock'] == 1) {
					/*Then we need to make a journal to transfer the cost  of WIP to the new WIP account */
					/*First get the total cost of WIP for this category */

					$WOCostsResult = DB_query("SELECT workorders.costissued,
													SUM(woitems.qtyreqd * woitems.stdcost) AS costrecd
												FROM woitems INNER JOIN workorders
												ON woitems.wo = workorders.wo
												INNER JOIN stockmaster
												ON woitems.stockid=stockmaster.stockid
												WHERE stockmaster.stockid='" . $StockID . "'
												AND workorders.closed=0
												GROUP BY workorders.costissued", __('Error retrieving value of finished goods received and cost issued against work orders for this item'));
					$WIPValue = 0;
					while ($WIPRow = DB_fetch_array($WOCostsResult)) {
						$WIPValue+= ($WIPRow['costissued'] - $WIPRow['costrecd']);
					}
					if ($WIPValue != 0) {
						$JournalNo = GetNextTransNo(0); //enter as a journal
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
											VALUES ( 0,
													'" . $JournalNo . "',
													CURRENT_DATE,
													'" . GetPeriod(date($_SESSION['DefaultDateFormat'])) . "',
													'" . $NewWIPAct . "',
													'" . mb_substr($StockID . ' ' . __('Change stock category'), 0, 200) . "',
													'" . $WIPValue . "')";
						$ErrMsg = __('The WIP cost journal could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
											VALUES ( 0,
													'" . $JournalNo . "',
													CURRENT_DATE,
													'" . GetPeriod(date($_SESSION['DefaultDateFormat'])) . "',
													'" . $OldWIPAccount . "',
													'" . mb_substr($StockID . ' ' . __('Change stock category'), 0, 200) . "',
													'" . (-$WIPValue) . "')";
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}
				} /* end if the stock category changed and forced a change in WIP account */
				DB_Txn_Commit();
				prnMsg(__('Stock Item') . ' ' . $StockID . ' ' . __('has been updated'), 'success');
				echo '<br />';
			}

		} else { //it is a NEW part
			//but lets be really sure here
			$Result = DB_query("SELECT stockid
								FROM stockmaster
								WHERE stockid='" . $StockID . "'");

			if (DB_num_rows($Result) == 1) {
				prnMsg(__('The stock code entered is actually already in the database - duplicate stock codes are prohibited by the system. Try choosing an alternative stock code'), 'error');
				$InputError = 1;
				$Errors[$i] = 'StockID';
				$i++;
			} else {
				DB_Txn_Begin();
				$SQL = "INSERT INTO stockmaster (stockid,
												description,
												longdescription,
												categoryid,
												units,
												mbflag,
												eoq,
												discontinued,
												controlled,
												serialised,
												perishable,
												volume,
												grossweight,
												netweight,
												barcode,
												discountcategory,
												taxcatid,
												decimalplaces,
												shrinkfactor,
												pansize)
							VALUES ('" . $StockID . "',
								'" . $_POST['Description'] . "',
								'" . $_POST['LongDescription'] . "',
								'" . $_POST['CategoryID'] . "',
								'" . $_POST['Units'] . "',
								'" . $_POST['MBFlag'] . "',
								'" . filter_number_format($_POST['EOQ']) . "',
								'" . $_POST['Discontinued'] . "',
								'" . $_POST['Controlled'] . "',
								'" . $_POST['Serialised'] . "',
								'" . $_POST['Perishable'] . "',
								'" . filter_number_format($_POST['Volume']) . "',
								'" . filter_number_format($_POST['GrossWeight']) . "',
								'" . filter_number_format($_POST['NetWeight']) . "',
								'" . $_POST['BarCode'] . "',
								'" . $_POST['DiscountCategory'] . "',
								'" . $_POST['TaxCat'] . "',
								'" . $_POST['DecimalPlaces'] . "',
								'" . filter_number_format($_POST['ShrinkFactor']) . "',
								'" . filter_number_format($_POST['Pansize']) . "')";

				$ErrMsg = __('The item could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', '', true);
				if (DB_error_no() == 0) {
					//now insert the language descriptions
					$ErrMsg = __('Could not update the language description because');
					if (count($ItemDescriptionLanguagesArray) > 0) {
						foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
							if ($LanguageId != '' and $_POST['Description_' . str_replace('.', '_', $LanguageId) ] != '') {
								$Result = DB_query("INSERT INTO stockdescriptiontranslations (stockid,
																							language_id,
																							descriptiontranslation,
																							longdescriptiontranslation)
													VALUES('" . $StockID . "','" . $LanguageId . "', '" . $_POST['Description_' . str_replace('.', '_', $LanguageId) ] . "', '" . $_POST['longDescription_' . str_replace('.', '_', $LanguageId) ] . "')", $ErrMsg, '', true);
							}
						}
					}
					//now insert any item properties
					for ($i = 0;$i < $_POST['PropertyCounter'];$i++) {

						if ($_POST['PropType' . $i] == 2) {
							if ($_POST['PropValue' . $i] == 'on') {
								$_POST['PropValue' . $i] = 1;
							} else {
								$_POST['PropValue' . $i] = 0;
							}
						}

						if ($_POST['PropNumeric' . $i] == 1) {
							$_POST['PropValue' . $i] = filter_number_format($_POST['PropValue' . $i]);
						} /*else {
							$_POST['PropValue' . $i] = $_POST['PropValue' . $i];
						}*/

						$Result = DB_query("INSERT INTO stockitemproperties (stockid,
													stkcatpropid,
													value)
													VALUES ('" . $StockID . "',
														'" . $_POST['PropID' . $i] . "',
														'" . $_POST['PropValue' . $i] . "')", $ErrMsg, '', true);
					} //end of loop around properties defined for the category
					//Add data to locstock
					$SQL = "INSERT INTO locstock (loccode,
													stockid)
										SELECT locations.loccode,
										'" . $StockID . "'
										FROM locations";

					$ErrMsg = __('The locations for the item') . ' ' . $StockID . ' ' . __('could not be added because');
					$InsResult = DB_query($SQL, $ErrMsg, '', true);
					DB_Txn_Commit();
					if (DB_error_no() == 0) {
						prnMsg(__('New Item') . ' ' . '<a href="' . $RootPath . '/SelectProduct.php?StockID=' . $StockID . '">' . $StockID . '</a> ' . __('has been added to the database') . '<br />' . __('NB: The item cost and pricing must also be setup') . '<br />' . '<a target="_blank" href="' . $RootPath . '/StockCostUpdate.php?StockID=' . $StockID . '">' . __('Enter Item Cost') . '</a>
							<br />' . '<a target="_blank" href="' . $RootPath . '/Prices.php?Item=' . $StockID . '">' . __('Enter Item Prices') . '</a> ', 'success');
						echo '<br />';
						unset($_POST['Description']);
						unset($_POST['LongDescription']);
						unset($_POST['EOQ']);
						// Leave Category ID set for ease of batch entry
						//						unset($_POST['CategoryID']);
						unset($_POST['Units']);
						unset($_POST['MBFlag']);
						unset($_POST['Discontinued']);
						unset($_POST['Controlled']);
						unset($_POST['Serialised']);
						unset($_POST['Perishable']);
						unset($_POST['Volume']);
						unset($_POST['GrossWeight']);
						unset($_POST['NetWeight']);
						unset($_POST['BarCode']);
						unset($_POST['ReorderLevel']);
						unset($_POST['DiscountCategory']);
						unset($_POST['DecimalPlaces']);
						unset($_POST['ShrinkFactor']);
						unset($_POST['Pansize']);
						unset($StockID);
						foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
							unset($_POST['Description_' . str_replace('.', '_', $LanguageId) ]);
						}
						$New = 1;
					} //ALL WORKED SO RESET THE FORM VARIABLES

				} //THE INSERT OF THE NEW CODE WORKED SO BANG IN THE STOCK LOCATION RECORDS TOO

			} //END CHECK FOR ALREADY EXISTING ITEM OF THE SAME CODE

		}

	} else {
		echo '<br />' . "\n";
		prnMsg(__('Validation failed, no updates or deletes took place'), 'error');
	}

} elseif (isset($_POST['delete']) and mb_strlen($_POST['delete']) > 1) {
	//the button to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'StockMoves'
	$SQL = "SELECT COUNT(*) FROM stockmoves WHERE stockid='" . $StockID . "' GROUP BY stockid";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this stock item because there are stock movements that refer to this item'), 'warn');
		echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('stock movements that refer to this item');

	} else {
		$SQL = "SELECT COUNT(*) FROM bom WHERE component='" . $StockID . "' GROUP BY component";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete this item record because there are bills of material that require this part as a component'), 'warn');
			echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('bills of material that require this part as a component');
		} else {
			$SQL = "SELECT COUNT(*) FROM salesorderdetails WHERE stkcode='" . $StockID . "' GROUP BY stkcode";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				$CancelDelete = 1;
				prnMsg(__('Cannot delete this item record because there are existing sales orders for this part'), 'warn');
				echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('sales order items against this part');
			} else {
				$SQL = "SELECT COUNT(*) FROM salesanalysis WHERE stockid='" . $StockID . "' GROUP BY stockid";
				$Result = DB_query($SQL);
				$MyRow = DB_fetch_row($Result);
				if ($MyRow[0] > 0) {
					$CancelDelete = 1;
					prnMsg(__('Cannot delete this item because sales analysis records exist for it'), 'warn');
					echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('sales analysis records against this part');
				} else {
					$SQL = "SELECT COUNT(*) FROM purchorderdetails WHERE itemcode='" . $StockID . "' GROUP BY itemcode";
					$Result = DB_query($SQL);
					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0] > 0) {
						$CancelDelete = 1;
						prnMsg(__('Cannot delete this item because there are existing purchase order items for it'), 'warn');
						echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('purchase order item record relating to this part');
					} else {
						$QOH = GetQuantityOnHand($StockID, 'ALL');
						if ($QOH != 0) {
							$CancelDelete = 1;
							prnMsg(__('Cannot delete this item because there is currently some stock on hand'), 'warn');
							echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('on hand for this part');
						} else {
							$SQL = "SELECT COUNT(*) FROM offers WHERE stockid='" . $StockID . "' GROUP BY stockid";
							$Result = DB_query($SQL);
							$MyRow = DB_fetch_row($Result);
							if ($MyRow[0] != 0) {
								$CancelDelete = 1;
								prnMsg(__('Cannot delete this item because there are offers for this item'), 'warn');
								echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('offers from suppliers for this part');
							} else {
								$SQL = "SELECT COUNT(*) FROM tenderitems WHERE stockid='" . $StockID . "' GROUP BY stockid";
								$Result = DB_query($SQL);
								$MyRow = DB_fetch_row($Result);
								if ($MyRow[0] != 0) {
									$CancelDelete = 1;
									prnMsg(__('Cannot delete this item because there are tenders for this item'), 'warn');
									echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('tenders from suppliers for this part');
								}
							}
						}
					}
				}
			}
		}

	}
	if ($CancelDelete == 0) {
		DB_Txn_Begin();

		/*Deletes LocStock records*/
		$SQL = "DELETE FROM locstock WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the location stock records because'), '', true);
		/*Deletes Price records*/
		$SQL = "DELETE FROM prices WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the prices for this stock record because'), '', true);
		/*and cascade deletes in PurchData */
		$SQL = "DELETE FROM purchdata WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the purchasing data because'), '', true);
		/*and cascade delete the bill of material if any */
		$SQL = "DELETE FROM bom WHERE parent='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the bill of material because'), '', true);
		//and cascade delete the item properties
		$SQL = "DELETE FROM stockitemproperties WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the item properties'), '', true);
		//and cascade delete the item descriptions in other languages
		$SQL = "DELETE FROM stockdescriptiontranslations WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the item language descriptions'), '', true);
		$SQL = "DELETE FROM stockmaster WHERE stockid='" . $StockID . "'";
		$Result = DB_query($SQL, __('Could not delete the item record'), '', true);

		DB_Txn_Commit();

		prnMsg(__('Deleted the stock master record for') . ' ' . $StockID . '....' . '<br />. . ' . __('and all the location stock records set up for the part') . '<br />. . .' . __('and any bill of material that may have been set up for the part') . '<br /> . . . .' . __('and any purchasing data that may have been set up for the part') . '<br /> . . . . .' . __('and any prices that may have been set up for the part'), 'success');
		echo '<br />';
		unset($_POST['LongDescription']);
		unset($_POST['Description']);
		unset($_POST['EOQ']);
		unset($_POST['CategoryID']);
		unset($_POST['Units']);
		unset($_POST['MBFlag']);
		unset($_POST['Discontinued']);
		unset($_POST['Controlled']);
		unset($_POST['Serialised']);
		unset($_POST['Perishable']);
		unset($_POST['Volume']);
		unset($_POST['GrossWeight']);
		unset($_POST['NetWeight']);
		unset($_POST['BarCode']);
		unset($_POST['ReorderLevel']);
		unset($_POST['DiscountCategory']);
		unset($_POST['TaxCat']);
		unset($_POST['DecimalPlaces']);
		unset($_SESSION['SelectedStockItem']);
		foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
			unset($_POST['Description_' . str_replace('.', '_', $LanguageId) ]);
		}
		unset($StockID);

		$New = 1;
	} //end if Delete Part

}

echo '<form name="ItemForm" enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<input type="hidden" name="New" value="' . $New . '" />';

echo '<div class="db-grid db-grid-2">';

	if ($New == 1) {
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Create Stock Item') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-field">
						<label for="StockID">' . __('Item Code') . '</label>
						<input type="text" class="db-input ' . (in_array('StockID', $Errors) ? 'inputerror' : '') . '" data-type="no-illegal-chars" autofocus="autofocus" required="required" value="' . $StockID . '" name="StockID" maxlength="20" placeholder="' . __('alpha-numeric only') . '" />
					</div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-tag"></i> ' . __('Identification') . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-field">
						<label>' . __('Item Code') . '</label>
						<div class="db-field-content">
							<b>' . $StockID . '</b>
							<input type="hidden" name="StockID" value="' . $StockID . '" />
						</div>
					</div>';
	}

echo '		<div class="db-field">
				<label for="Description">' . __('Part Description') . ' (' . __('short') . ')</label>
				<input ' . (in_array('Description', $Errors) ? 'class="db-input inputerror"' : 'class="db-input"') . ' type="text" ' . ($New == 0 ? 'autofocus="autofocus"' : '') . ' name="Description" required="required" maxlength="50" value="' . stripslashes($Description) . '" />
			</div>';

foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
	if ($LanguageId != '') {
		$PostVariableName = 'Description_' . str_replace('.', '_', $LanguageId);
		if (!isset($_POST[$PostVariableName])) {
			$_POST[$PostVariableName] = '';
		}
		echo '<div class="db-field">
				<label for="' . $PostVariableName . '">' . $LanguagesArray[$LanguageId]['LanguageName'] . ' ' . __('Description') . '</label>
				<input class="db-input" type="text" name="' . $PostVariableName . '" maxlength="50" value="' . $_POST[$PostVariableName] . '" />
				<div class="db-field-help">' . __('Used in invoices for customers with this language.') . '</div>
			</div>';
	}
}

if (isset($_POST['LongDescription'])) {
	$LongDescription = AddCarriageReturns($_POST['LongDescription']);
} else {
	$LongDescription = '';
}
echo '		<div class="db-field">
				<label for="LongDescription">' . __('Part Description') . ' (' . __('long') . ')</label>
				<textarea ' . (in_array('LongDescription', $Errors) ? 'class="db-input texterror"' : 'class="db-input"') . ' name="LongDescription" rows="3">' . stripslashes($LongDescription) . '</textarea>
			</div>';

foreach ($ItemDescriptionLanguagesArray as $LanguageId) {
	if ($LanguageId != '') {
		$PostVariableName = 'LongDescription_' . str_replace('.', '_', $LanguageId);
		if (!isset($_POST[$PostVariableName])) {
			$_POST[$PostVariableName] = '';
		}
		echo '		<div class="db-field">
				<label for="' . $PostVariableName . '">' . $LanguagesArray[$LanguageId]['LanguageName'] . ' ' . __('Long Description') . '</label>
				<textarea class="db-input" name="' . $PostVariableName . '" rows="2">' . stripslashes(AddCarriageReturns($_POST[$PostVariableName])) . '</textarea>
			</div>';
	}
}

echo '			<div class="db-field">
					<label for="ItemPicture">' . __('Image File') . ' (' . implode(", ", $SupportedImgExt) . ')</label>
					<div class="db-grid db-grid-2" style="align-items: center;">
						<div>
							<input type="file" id="ItemPicture" name="ItemPicture" class="db-input" />
							<div style="margin-top: 5px;">
								<input type="checkbox" name="ClearImage" id="ClearImage" value="1" >
								<label for="ClearImage" style="display:inline; font-weight:normal; margin-left:5px;"> ' . __('Clear Image') . '</label>
							</div>
						</div>';

if (sizeof(glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{' . implode(",", $SupportedImgExt) . '}')) > 0) {
	$Glob = (glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE));
	$ImageFile = reset($Glob);
} else {
	$ImageFile = '';
}
$StockImgLink = GetImageLink($ImageFile, $StockID, 80, 80, "", "db-image-preview");

echo '					<div class="centre">' . $StockImgLink . '</div>
					</div>
				</div>
			</div>
		</div>';

	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Logistics') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-field">
					<label for="CategoryID">' . __('Category') . '</label>
					<select name="CategoryID" class="db-select" onchange="ReloadForm(ItemForm.UpdateCategories)">';

	$SQL = "SELECT categoryid, categorydescription FROM stockcategory";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = (isset($_POST['CategoryID']) && $MyRow['categoryid'] == $_POST['CategoryID']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		$Category = $MyRow['categoryid'];
	}
	if (!isset($_POST['CategoryID'])) {
		$_POST['CategoryID'] = $Category;
	}

	echo '			</select>
					<div class="db-field-help"><a target="_blank" href="' . $RootPath . '/StockCategories.php" class="db-link">' . __('Manage Categories') . '</a></div>
				</div>

				<div class="db-field">
					<label for="Units">' . __('Units of Measure') . '</label>
					<select name="Units" class="db-select">';
	$SQL = "SELECT unitname FROM unitsofmeasure ORDER by unitname";
	$UOMResult = DB_query($SQL);
	while ($UOMrow = DB_fetch_array($UOMResult)) {
		$selected = (isset($_POST['Units']) && $_POST['Units'] == $UOMrow['unitname']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $UOMrow['unitname'] . '">' . $UOMrow['unitname'] . '</option>';
	}
	echo '			</select>
				</div>

				<div class="db-field">
					<label for="EOQ">' . __('Economic Order Qty (EOQ)') . '</label>
					<input type="text" class="db-input number ' . (in_array('EOQ', $Errors) ? 'inputerror' : '') . '" name="EOQ" maxlength="10" value="' . locale_number_format($_POST['EOQ'], 'Variable') . '" />
				</div>

				<div class="db-field">
					<label for="Volume">' . __('Packaged Volume (m³)') . '</label>
					<input type="text" class="db-input number ' . (in_array('Volume', $Errors) ? 'inputerror' : '') . '" name="Volume" maxlength="10" value="' . locale_number_format($_POST['Volume'], 'Variable') . '" />
				</div>

				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label for="GrossWeight">' . __('Gross Weight (KG)') . '</label>
						<input type="text" class="db-input number ' . (in_array('GrossWeight', $Errors) ? 'inputerror' : '') . '" name="GrossWeight" maxlength="10" value="' . locale_number_format($_POST['GrossWeight'], 'Variable') . '" />
					</div>
					<div class="db-field">
						<label for="NetWeight">' . __('Net Weight (KG)') . '</label>
						<input type="text" class="db-input number ' . (in_array('NetWeight', $Errors) ? 'inputerror' : '') . '" name="NetWeight" maxlength="10" value="' . locale_number_format($_POST['NetWeight'], 'Variable') . '" />
					</div>
				</div>
			</div>
		</div><br />';

	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-sync-alt"></i> ' . __('Lifecycle & Control') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-field">
					<label for="MBFlag">' . __('Item Type') . '</label>
					<select name="MBFlag" class="db-select">';
	$flags = [
		'A' => __('Assembly'),
		'K' => __('Kit'),
		'M' => __('Manufactured'),
		'G' => __('Phantom'),
		'B' => __('Purchased'),
		'D' => __('Service/Labour')
	];
	foreach ($flags as $val => $label) {
		$selected = (isset($_POST['MBFlag']) && $_POST['MBFlag'] == $val) ? 'selected="selected"' : '';
		if (!isset($_POST['MBFlag']) && ($val == 'K' || $val == 'M' || $val == 'B')) { /* defaults? */ }
		echo '<option ' . $selected . ' value="' . $val . '">' . $label . '</option>';
	}
	echo '			</select>
				</div>

				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label for="Discontinued">' . __('Status') . '</label>
						<select name="Discontinued" class="db-select">
							<option ' . ($_POST['Discontinued'] == 0 ? 'selected="selected"' : '') . ' value="0">' . __('Current') . '</option>
							<option ' . ($_POST['Discontinued'] == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Obsolete') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label for="Perishable">' . __('Perishable') . '</label>
						<select name="Perishable" class="db-select">
							<option ' . ($_POST['Perishable'] == 0 ? 'selected="selected"' : '') . ' value="0">' . __('No') . '</option>
							<option ' . ($_POST['Perishable'] == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Yes') . '</option>
						</select>
					</div>
				</div>

				<div class="db-field">
					<label for="Controlled">' . __('Lot/Batch Control') . '</label>
					<select name="Controlled" class="db-select">
						<option ' . ($_POST['Controlled'] == 0 ? 'selected="selected"' : '') . ' value="0">' . __('No Control') . '</option>
						<option ' . ($_POST['Controlled'] == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Controlled') . '</option>
					</select>
				</div>

				<div class="db-field">
					<label for="Serialised">' . __('Serialised') . '</label>
					<select name="Serialised" class="db-select ' . (in_array('Serialised', $Errors) ? 'selecterror' : '') . '">';
	echo '				<option ' . ($_POST['Serialised'] == 0 ? 'selected="selected"' : '') . ' value="0">' . __('No') . '</option>
						<option ' . ($_POST['Serialised'] == 1 ? 'selected="selected"' : '') . ' value="1">' . __('Yes') . '</option>
					</select>
					<div class="db-field-help"><i>' . __('Only effective if Controlled is enabled.') . '</i></div>
				</div>';

	if ($_POST['Serialised'] == 1 and $_POST['MBFlag'] == 'M') {
		echo '	<div class="db-field">
					<label for="NextSerialNo">' . __('Next Serial No') . '</label>
					<input type="text" class="db-input ' . (in_array('NextSerialNo', $Errors) ? 'inputerror' : '') . '" name="NextSerialNo" maxlength="15" value="' . $_POST['NextSerialNo'] . '" />
				</div>';
	} else {
		echo '	<input type="hidden" name="NextSerialNo" value="0" />';
	}

	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-file-invoice-dollar"></i> ' . __('Sales & Accounting') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-field">
					<label for="BarCode">' . __('Bar Code') . '</label>
					<input type="text" class="db-input ' . (in_array('BarCode', $Errors) ? 'inputerror' : '') . '" name="BarCode" maxlength="20" value="' . ($_POST['BarCode'] ?? '') . '" />
				</div>

				<div class="db-field">
					<label for="TaxCat">' . __('Tax Category') . '</label>
					<select name="TaxCat" class="db-select">';
	$SQL = "SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatname";
	$Result = DB_query($SQL);
	if (!isset($_POST['TaxCat'])) {
		$_POST['TaxCat'] = $_SESSION['DefaultTaxCategory'];
	}
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = ($_POST['TaxCat'] == $MyRow['taxcatid']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['taxcatid'] . '">' . $MyRow['taxcatname'] . '</option>';
	}
	echo '			</select>
				</div>

				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label for="DiscountCategory">' . __('Discount Category') . '</label>
						<input type="text" class="db-input" name="DiscountCategory" maxlength="2" value="' . ($_POST['DiscountCategory'] ?? '') . '" />
					</div>
					<div class="db-field">
						<label for="DecimalPlaces">' . __('Decimal Places') . '</label>
						<input type="text" class="db-input number" name="DecimalPlaces" maxlength="1" value="' . $_POST['DecimalPlaces'] . '" />
					</div>
				</div>

				<div class="db-grid db-grid-2">
					<div class="db-field">
						<label for="PanSize">' . __('Pan Size') . '</label>
						<input type="text" class="db-input number" name="Pansize" maxlength="6" value="' . locale_number_format($_POST['Pansize'], 0) . '" />
						<div class="db-field-help">' . __('Min packing qty.') . '</div>
					</div>
					<div class="db-field">
						<label for="ShrinkageFactor">' . __('Shrinkage Factor') . '</label>
						<input type="text" class="db-input number" name="ShrinkFactor" maxlength="6" value="' . locale_number_format($_POST['ShrinkFactor'], 'Variable') . '" />
					</div>
				</div>
			</div>
		</div>
	</div><br />';

if (!isset($_POST['CategoryID'])) {
	$_POST['CategoryID'] = '';
}

$SQL = "SELECT stkcatpropid,
				label,
				controltype,
				defaultvalue,
				numericvalue,
				minimumvalue,
				maximumvalue
		FROM stockcatproperties
		WHERE categoryid ='" . $_POST['CategoryID'] . "'
		AND reqatsalesorder =0
		ORDER BY stkcatpropid";

$PropertiesResult = DB_query($SQL);
$PropertyCounter = 0;
$PropertyWidth = array();

if (DB_num_rows($PropertiesResult) > 0) {
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Item Category Properties') . '</div>
			</div>
			<div class="db-card-body">';

	while ($PropertyRow = DB_fetch_array($PropertiesResult)) {
		if (isset($StockID)) {
			$PropValResult = DB_query("SELECT value FROM stockitemproperties WHERE stockid='" . $StockID . "' AND stkcatpropid ='" . $PropertyRow['stkcatpropid'] . "'");
			$PropValRow = DB_fetch_row($PropValResult);
			$PropertyValue = $PropValRow[0] ?? '';
		} else {
			$PropertyValue = '';
		}

		echo '<div class="db-field">
				<label>' . $PropertyRow['label'] . '</label>
				<input type="hidden" name="PropID' . $PropertyCounter . '" value="' . $PropertyRow['stkcatpropid'] . '" />
				<input type="hidden" name="PropNumeric' . $PropertyCounter . '" value="' . $PropertyRow['numericvalue'] . '" />
				<input type="hidden" name="PropType' . $PropertyCounter . '" value="' . $PropertyRow['controltype'] . '" />';

		switch ($PropertyRow['controltype']) {
			case 0: //textbox
				if ($PropertyRow['numericvalue'] == 1) {
					echo '<input type="hidden" name="PropMin' . $PropertyCounter . '" value="' . $PropertyRow['minimumvalue'] . '" />';
					echo '<input type="hidden" name="PropMax' . $PropertyCounter . '" value="' . $PropertyRow['maximumvalue'] . '" />';
					echo '<input type="text" class="db-input number" name="PropValue' . $PropertyCounter . '" maxlength="100" value="' . locale_number_format($PropertyValue, 'Variable') . '" />';
					echo '<div class="db-field-help">' . __('Expected range:') . ' ' . locale_number_format($PropertyRow['minimumvalue'], 'Variable') . ' - ' . locale_number_format($PropertyRow['maximumvalue'], 'Variable') . '</div>';
				} else {
					echo '<input type="text" class="db-input" name="PropValue' . $PropertyCounter . '" maxlength="100" value="' . $PropertyValue . '" />';
				}
				break;
			case 1: //select box
				$OptionValues = explode(',', $PropertyRow['defaultvalue']);
				echo '<select name="PropValue' . $PropertyCounter . '" class="db-select">';
				foreach ($OptionValues as $PropertyOptionValue) {
					$selected = ($PropertyOptionValue == $PropertyValue) ? 'selected="selected"' : '';
					echo '<option ' . $selected . ' value="' . $PropertyOptionValue . '">' . $PropertyOptionValue . '</option>';
				}
				echo '</select>';
				break;
			case 2: //checkbox
				echo '<div style="margin-top:10px;"><input type="checkbox" name="PropValue' . $PropertyCounter . '" ' . ($PropertyValue == 1 ? 'checked' : '') . ' /></div>';
				break;
		}
		echo '</div>';
		$PropertyCounter++;
	}
	echo '	</div>
		</div><br />';
}
echo '<input type="hidden" name="PropertyCounter" value="' . $PropertyCounter . '" />';

echo '<div class="db-card">
		<div class="db-card-body centre">';
if ($New == 1) {
	echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> ' . __('Insert New Item') . '</button>';
	echo '<input type="submit" name="UpdateCategories" style="visibility:hidden;width:1px" value="' . __('Categories') . '" />';
} else {
	echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . __('Update Details') . '</button> ';
	echo '<button type="submit" name="delete" class="db-btn db-btn-outline" style="color:var(--danger-color); border-color:var(--danger-color);" onclick="return confirm(\'' . __('Are You Sure?') . '\');"><i class="fas fa-trash"></i> ' . __('Delete This Item') . '</button>';
	echo '<input type="submit" name="UpdateCategories" style="visibility:hidden;width:1px" value="' . __('Categories') . '" />';
}
echo '	</div>
	</div>';

echo '	</form>
	</div>'; // End of db-page
include(__DIR__ . '/includes/footer.php');
