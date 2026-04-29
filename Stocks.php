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
}
if (isset($_POST['PreviousItem'])) {
	$Result = DB_query("SELECT stockid FROM stockmaster WHERE stockid<'" . $StockID . "' ORDER BY stockid DESC LIMIT 1");
	if (DB_num_rows($Result) > 0) {
		$PreviousItemRow = DB_fetch_row($Result);
		$StockID = $PreviousItemRow[0];
	} else {
		$HasPrev = false;
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
			DB_Txn_Begin();
			$SQL = "UPDATE stockmaster SET longdescription='" . $_POST['LongDescription'] . "', description='" . $_POST['Description'] . "', discontinued='" . $_POST['Discontinued'] . "', controlled='" . $_POST['Controlled'] . "', serialised='" . $_POST['Serialised'] . "', perishable='" . $_POST['Perishable'] . "', categoryid='" . $_POST['CategoryID'] . "', units='" . $_POST['Units'] . "', mbflag='" . $_POST['MBFlag'] . "', eoq='" . filter_number_format($_POST['EOQ']) . "', volume='" . filter_number_format($_POST['Volume']) . "', grossweight='" . filter_number_format($_POST['GrossWeight']) . "', netweight='" . filter_number_format($_POST['NetWeight']) . "', barcode='" . $_POST['BarCode'] . "', discountcategory='" . $_POST['DiscountCategory'] . "', taxcatid='" . $_POST['TaxCat'] . "', decimalplaces='" . $_POST['DecimalPlaces'] . "', shrinkfactor='" . filter_number_format($_POST['ShrinkFactor']) . "', pansize='" . filter_number_format($_POST['Pansize']) . "', nextserialno='" . $_POST['NextSerialNo'] . "' WHERE stockid='" . $StockID . "'";
			DB_query($SQL, __('The item could not be updated'), '', true);

			// Properties
			DB_query("DELETE FROM stockitemproperties WHERE stockid ='" . $StockID . "'", '', '', true);
			for ($j = 0; $j < ($_POST['PropertyCounter'] ?? 0); $j++) {
				$propVal = $_POST['PropValue' . $j] ?? '';
				if ($_POST['PropType' . $j] == 2) $propVal = (isset($_POST['PropValue' . $j]) ? 1 : 0);
				if (($_POST['PropNumeric' . $j] ?? 0) == 1) $propVal = filter_number_format($propVal);
				DB_query("INSERT INTO stockitemproperties (stockid, stkcatpropid, value) VALUES ('" . $StockID . "', '" . $_POST['PropID' . $j] . "', '" . $propVal . "')", '', '', true);
			}

			DB_Txn_Commit();
			prnMsg(__('Item') . ' ' . $StockID . ' ' . __('updated'), 'success');
		} else {
			// NEW ITEM INSERT
			$CheckVal = DB_query("SELECT stockid FROM stockmaster WHERE stockid='" . $StockID . "'");
			if (DB_num_rows($CheckVal) == 1) {
				prnMsg(__('Duplicate stock code'), 'error');
				$InputError = 1;
			} else {
				DB_Txn_Begin();
				$SQL = "INSERT INTO stockmaster (stockid, description, longdescription, categoryid, units, mbflag, eoq, discontinued, controlled, serialised, perishable, volume, grossweight, netweight, barcode, discountcategory, taxcatid, decimalplaces, shrinkfactor, pansize, lastcost, materialcost, labourcost, overheadcost, lowestlevel, lastcostupdate)
						VALUES ('" . $StockID . "', '" . $_POST['Description'] . "', '" . $_POST['LongDescription'] . "', '" . $_POST['CategoryID'] . "', '" . $_POST['Units'] . "', '" . $_POST['MBFlag'] . "', '" . filter_number_format($_POST['EOQ'] ?? 0) . "', '" . ($_POST['Discontinued'] ?? 0) . "', '" . ($_POST['Controlled'] ?? 0) . "', '" . ($_POST['Serialised'] ?? 0) . "', '" . ($_POST['Perishable'] ?? 0) . "', '" . filter_number_format($_POST['Volume'] ?? 0) . "', '" . filter_number_format($_POST['GrossWeight'] ?? 0) . "', '" . filter_number_format($_POST['NetWeight'] ?? 0) . "', '" . ($_POST['BarCode'] ?? '') . "', '" . ($_POST['DiscountCategory'] ?? '') . "', '" . ($_POST['TaxCat'] ?? '') . "', '" . ($_POST['DecimalPlaces'] ?? 0) . "', '" . filter_number_format($_POST['ShrinkFactor'] ?? 0) . "', '" . filter_number_format($_POST['Pansize'] ?? 1) . "', 0, 0, 0, 0, 0, '1000-01-01')";
				DB_query($SQL, __('The item could not be added'), '', true);
				
				// Insert locstock for all locations
				DB_query("INSERT INTO locstock (loccode, stockid, quantity, reorderlevel, bin) SELECT locations.loccode, '" . $StockID . "', 0, 0, '' FROM locations", '', '', true);
				
				// Properties for new items
				for ($j = 0; $j < ($_POST['PropertyCounter'] ?? 0); $j++) {
					$propVal = $_POST['PropValue' . $j] ?? '';
					if ($_POST['PropType' . $j] == 2) $propVal = (isset($_POST['PropValue' . $j]) ? 1 : 0);
					if (($_POST['PropNumeric' . $j] ?? 0) == 1) $propVal = filter_number_format($propVal);
					DB_query("INSERT INTO stockitemproperties (stockid, stkcatpropid, value) VALUES ('" . $StockID . "', '" . $_POST['PropID' . $j] . "', '" . $propVal . "')", '', '', true);
				}

				DB_Txn_Commit();
				prnMsg(__('New Item') . ' ' . $StockID . ' ' . __('added'), 'success');
				$New = 0; // Transition to edit mode
			}
		}
	}
}

?>
<style>
    :root {
        --primary: hsl(145, 63%, 38%);
        --primary-hover: hsl(145, 63%, 32%);
        --primary-dark: hsl(145, 45%, 22%);
        --primary-soft: hsl(145, 40%, 95%);
        --bg: hsl(210, 20%, 97%);
        --white: #ffffff;
        --border: #e2e8f0;
        --border-soft: #f1f5f9;
        --text-main: #334155;
        --text-muted: #64748b;
        --shadow: 0 1px 3px rgba(0,0,0,0.1);
        --radius: 12px;
        --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
    }

    body {
        background-color: var(--bg);
        color: var(--text-main);
        font-family: var(--font-sans);
    }

    .aw-page {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }

    .aw-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 2rem;
    }

    .aw-breadcrumb {
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .aw-title {
        font-size: 2rem;
        font-weight: 900;
        color: var(--primary-dark);
        margin: 0;
        line-height: 1;
    }

    .aw-layout-grid {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .aw-layout-grid {
            grid-template-columns: 1fr;
        }
    }

    .aw-card {
        background: var(--white);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        border: 1px solid var(--border-soft);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .aw-card-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--border-soft);
        background: var(--white);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .aw-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary-dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .aw-card-title i {
        color: var(--primary);
        font-size: 1.1rem;
    }

    .aw-card-body {
        padding: 1.25rem;
    }

    .aw-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .aw-form-grid-3 {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .aw-form-grid-4 {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }

    .aw-field-group {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    .aw-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--primary-dark);
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .aw-input, .aw-select, .aw-textarea {
        width: 100%;
        padding: 0.6rem 0.8rem;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: var(--white);
        font-size: 0.9rem;
        color: var(--text-main);
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .aw-input:focus, .aw-select:focus, .aw-textarea:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px var(--primary-soft);
    }

    .aw-input.text-right { text-align: right; }

    .aw-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        text-decoration: none;
    }

    .aw-btn-primary {
        background: var(--primary);
        color: var(--white);
    }

    .aw-btn-primary:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .aw-btn-outline {
        background: transparent;
        border: 1px solid var(--border);
        color: var(--text-main);
    }

    .aw-btn-outline:hover {
        background: var(--border-soft);
        border-color: var(--primary);
        color: var(--primary);
    }

    .aw-btn-sm {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }

    .aw-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .aw-badge-primary { background: var(--primary-soft); color: var(--primary); }
    .aw-badge-success { background: #dcfce7; color: #166534; }
    .aw-badge-warn { background: #fef9c3; color: #854d0e; }

    .aw-sidebar-section {
        margin-bottom: 1.5rem;
    }

    .aw-image-container {
        width: 100%;
        aspect-ratio: 1;
        background: var(--bg-soft);
        border-radius: var(--radius);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid var(--border-soft);
        margin-bottom: 1rem;
    }

    .aw-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .aw-flag-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid var(--border-soft);
    }

    .aw-flag-row:last-child { border-bottom: none; }

    .aw-flag-label {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
        font-weight: 600;
    }

    .aw-flag-label i { width: 16px; text-align: center; color: var(--text-muted); }

    .aw-section-header {
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--primary);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .aw-section-header::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border-soft);
    }

    .aw-quick-action-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 8px;
        color: var(--text-main);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    .aw-quick-action-link:hover {
        background: var(--primary-soft);
        color: var(--primary);
    }

    .aw-quick-action-link i { color: var(--primary); width: 20px; text-align: center; }

</style>

<div class="aw-page">
    <div class="aw-header">
        <div>
            <div class="aw-breadcrumb">
                <a href="index.php?Application=stock" style="color: inherit; text-decoration: none;"><?php echo __('Inventory'); ?></a> / <?php echo __('Maintenance'); ?>
            </div>
            <h1 class="aw-title">
                <?php echo $Title; ?>
                <?php if ($StockID != ''): ?>
                    <span class="aw-badge aw-badge-primary" style="vertical-align: middle; margin-left: 10px;"><?php echo $StockID; ?></span>
                <?php endif; ?>
            </h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <?php if (isset($StockID) && $StockID != '' && $InputError == 0): ?>
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" style="display:inline-block">
                    <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
                    <input type="hidden" name="StockID" value="<?php echo $StockID; ?>" />
                    <button <?php echo ($HasPrev ? '' : 'disabled'); ?> name="PreviousItem" type="submit" class="aw-btn aw-btn-outline aw-btn-sm" title="<?php echo __('Prev'); ?>"><i class="fas fa-chevron-left"></i></button>
                    <button <?php echo ($HasNext ? '' : 'disabled'); ?> name="NextItem" type="submit" class="aw-btn aw-btn-outline aw-btn-sm" title="<?php echo __('Next'); ?>"><i class="fas fa-chevron-right"></i></button>
                </form>
            <?php endif; ?>
            <a href="<?php echo $RootPath; ?>/SelectProduct.php" class="aw-btn aw-btn-outline aw-btn-sm"><i class="fas fa-search"></i> <?php echo __('Catalogue'); ?></a>
        </div>
    </div>

    <form id="StockForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" enctype="multipart/form-data">
        <input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
        
        <div class="aw-layout-grid">
            <!-- MAIN COLUMN -->
            <main>
                <!-- PRODUCT IDENTITY CARD -->
                <div class="aw-card">
                    <div class="aw-card-header">
                        <h3 class="aw-card-title"><i class="fas fa-id-card"></i> <?php echo __('Product Specification'); ?></h3>
                    </div>
                    <div class="aw-card-body">
                        <div class="aw-form-grid">
                            <?php if ($New): ?>
                                <div class="aw-field-group">
                                    <label class="aw-label"><?php echo __('Item SKU'); ?> *</label>
                                    <input type="text" name="StockID" class="aw-input" required maxlength="20" autofocus value="<?php echo ($StockID ?? ''); ?>" />
                                </div>
                            <?php else: ?>
                                <input type="hidden" name="StockID" value="<?php echo $StockID; ?>" />
                            <?php endif; ?>

                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Short Name'); ?> *</label>
                                <input type="text" name="Description" class="aw-input" required maxlength="50" value="<?php echo ($_POST['description'] ?? $_POST['Description'] ?? ''); ?>" />
                            </div>
                            
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Classification'); ?></label>
                                <select name="CategoryID" class="aw-select" onchange="document.getElementById('StockForm').submit()">
                                    <?php 
                                    $CatsRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
                                    while ($c = DB_fetch_array($CatsRes)) {
                                        echo '<option ' . (($_POST['categoryid'] ?? $_POST['CategoryID'] ?? '') == $c['categoryid'] ? 'selected' : '') . ' value="' . $c['categoryid'] . '">' . $c['categorydescription'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Make or Buy Flag'); ?></label>
                                <select name="MBFlag" class="aw-select">
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'B' ? 'selected' : ''); ?> value="B"><?php echo __('Purchased'); ?></option>
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'M' ? 'selected' : ''); ?> value="M"><?php echo __('Manufactured'); ?></option>
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'A' ? 'selected' : ''); ?> value="A"><?php echo __('Assembly'); ?></option>
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'K' ? 'selected' : ''); ?> value="K"><?php echo __('Kit Set'); ?></option>
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'D' ? 'selected' : ''); ?> value="D"><?php echo __('Service / Labour'); ?></option>
                                    <option <?php echo (($_POST['mbflag'] ?? $_POST['MBFlag'] ?? 'B') == 'G' ? 'selected' : ''); ?> value="G"><?php echo __('Phantom / Ghost'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="aw-field-group" style="margin-top: 1.5rem;">
                            <label class="aw-label"><?php echo __('Technical / Marketing Description'); ?></label>
                            <textarea name="LongDescription" class="aw-textarea" rows="4"><?php echo ($_POST['longdescription'] ?? $_POST['LongDescription'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- LOGISTICS & UNITS CARD -->
                <div class="aw-card">
                    <div class="aw-card-header">
                        <h3 class="aw-card-title"><i class="fas fa-truck-loading"></i> <?php echo __('Logistics & Units'); ?></h3>
                    </div>
                    <div class="aw-card-body">
                        <div class="aw-form-grid-4">
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('UOM'); ?></label>
                                <select name="Units" class="aw-select">
                                    <?php 
                                    $UnitsRes = DB_query("SELECT unitname FROM unitsofmeasure");
                                    while ($u = DB_fetch_array($UnitsRes)) {
                                        echo '<option ' . (($_POST['units'] ?? $_POST['Units'] ?? 'each') == $u['unitname'] ? 'selected' : '') . ' value="' . $u['unitname'] . '">' . $u['unitname'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Precision'); ?></label>
                                <select name="DecimalPlaces" class="aw-select">
                                    <option value="0" <?php echo (($_POST['decimalplaces'] ?? $_POST['DecimalPlaces'] ?? 0) == 0 ? 'selected' : ''); ?>>0</option>
                                    <option value="1" <?php echo (($_POST['decimalplaces'] ?? $_POST['DecimalPlaces'] ?? 0) == 1 ? 'selected' : ''); ?>>1</option>
                                    <option value="2" <?php echo (($_POST['decimalplaces'] ?? $_POST['DecimalPlaces'] ?? 0) == 2 ? 'selected' : ''); ?>>2</option>
                                    <option value="3" <?php echo (($_POST['decimalplaces'] ?? $_POST['DecimalPlaces'] ?? 0) == 3 ? 'selected' : ''); ?>>3</option>
                                    <option value="4" <?php echo (($_POST['decimalplaces'] ?? $_POST['DecimalPlaces'] ?? 0) == 4 ? 'selected' : ''); ?>>4</option>
                                </select>
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Barcode'); ?></label>
                                <input type="text" name="BarCode" class="aw-input" maxlength="20" value="<?php echo ($_POST['barcode'] ?? $_POST['BarCode'] ?? ''); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Standard EOQ'); ?></label>
                                <input type="number" step="any" name="EOQ" class="aw-input text-right" value="<?php echo filter_number_format($_POST['eoq'] ?? $_POST['EOQ'] ?? 0); ?>" />
                            </div>
                        </div>

                        <div class="aw-section-header"><?php echo __('Physical Dimensions'); ?></div>
                        <div class="aw-form-grid-3" style="background: var(--bg); padding: 1rem; border-radius: 8px;">
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Volume (m3)'); ?></label>
                                <input type="number" step="any" name="Volume" class="aw-input text-right" value="<?php echo filter_number_format($_POST['volume'] ?? $_POST['Volume'] ?? 0); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Gross Weight (kg)'); ?></label>
                                <input type="number" step="any" name="GrossWeight" class="aw-input text-right" value="<?php echo filter_number_format($_POST['grossweight'] ?? $_POST['GrossWeight'] ?? 0); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Net Weight (kg)'); ?></label>
                                <input type="number" step="any" name="NetWeight" class="aw-input text-right" value="<?php echo filter_number_format($_POST['netweight'] ?? $_POST['NetWeight'] ?? 0); ?>" />
                            </div>
                        </div>

                        <div class="aw-section-header"><?php echo __('Financial & Planning'); ?></div>
                        <div class="aw-form-grid-3">
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Next Serial No'); ?></label>
                                <input type="number" name="NextSerialNo" class="aw-input text-right" value="<?php echo ($_POST['nextserialno'] ?? $_POST['NextSerialNo'] ?? 0); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Tax Category'); ?></label>
                                <select name="TaxCat" class="aw-select">
                                    <?php 
                                    $TaxRes = DB_query("SELECT taxcatid, taxcatname FROM taxcategories");
                                    while ($t = DB_fetch_array($TaxRes)) {
                                        echo '<option ' . (($_POST['taxcatid'] ?? $_POST['TaxCat'] ?? '') == $t['taxcatid'] ? 'selected' : '') . ' value="' . $t['taxcatid'] . '">' . $t['taxcatname'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Discount Group'); ?></label>
                                <input type="text" name="DiscountCategory" class="aw-input" maxlength="3" value="<?php echo ($_POST['discountcategory'] ?? $_POST['DiscountCategory'] ?? ''); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Shrink Factor'); ?></label>
                                <input type="number" step="any" name="ShrinkFactor" class="aw-input text-right" value="<?php echo filter_number_format($_POST['shrinkfactor'] ?? $_POST['ShrinkFactor'] ?? 0); ?>" />
                            </div>
                            <div class="aw-field-group">
                                <label class="aw-label"><?php echo __('Pan Size'); ?></label>
                                <input type="number" step="any" name="Pansize" class="aw-input text-right" value="<?php echo filter_number_format($_POST['pansize'] ?? $_POST['Pansize'] ?? 1); ?>" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- EXTENDED SPECIFICATIONS CARD -->
                <?php 
                if (isset($_POST['categoryid']) || isset($_POST['CategoryID'])) {
                    $cid = $_POST['categoryid'] ?? $_POST['CategoryID'];
                    $PropSQL = "SELECT stkcatpropid, label, controltype, defaultvalue, numericvalue, minimumvalue, maximumvalue
                                FROM stockcatproperties WHERE categoryid = '" . $cid . "' ORDER BY label";
                    $PropRes = DB_query($PropSQL);
                    if (DB_num_rows($PropRes) > 0) {
                        echo '<div class="aw-card">
                                <div class="aw-card-header">
                                    <h3 class="aw-card-title"><i class="fas fa-list-ul"></i> ' . __('Extended Specifications') . '</h3>
                                </div>
                                <div class="aw-card-body">
                                    <div class="aw-form-grid">';
                        $k = 0;
                        while ($pRow = DB_fetch_array($PropRes)) {
                            $valRes = DB_query("SELECT value FROM stockitemproperties WHERE stockid='" . $StockID . "' AND stkcatpropid='" . $pRow['stkcatpropid'] . "'");
                            $valRow = DB_fetch_array($valRes);
                            $currentVal = $valRow['value'] ?? $pRow['defaultvalue'];
                            
                            echo '<div class="aw-field-group">
                                    <label class="aw-label">' . $pRow['label'] . '</label>
                                    <input type="hidden" name="PropID' . $k . '" value="' . $pRow['stkcatpropid'] . '" />
                                    <input type="hidden" name="PropType' . $k . '" value="' . $pRow['controltype'] . '" />
                                    <input type="hidden" name="PropNumeric' . $k . '" value="' . $pRow['numericvalue'] . '" />';
                            if ($pRow['controltype'] == 2) { // checkbox
                                echo '<div style="display: flex; align-items: center; gap: 10px; padding-top: 5px;">
                                        <input type="checkbox" name="PropValue' . $k . '" ' . ($currentVal == 1 ? 'checked' : '') . ' />
                                        <span class="aw-label" style="font-weight: 500;">' . __('Enabled') . '</span>
                                      </div>';
                            } else {
                                echo '<input type="' . ($pRow['numericvalue'] == 1 ? 'number' : 'text') . '" step="any" name="PropValue' . $k . '" class="aw-input" value="' . $currentVal . '" />';
                            }
                            echo '</div>';
                            $k++;
                        }
                        echo '<input type="hidden" name="PropertyCounter" value="' . $k . '" />';
                        echo '      </div>
                                </div>
                              </div>';
                    }
                }
                ?>

                <div style="margin-top: 2rem; display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="submit" name="submit" class="aw-btn aw-btn-primary">
                        <i class="fas fa-save"></i> <?php echo __('Sync Item Details'); ?>
                    </button>
                </div>
            </main>

            <!-- SIDEBAR COLUMN -->
            <aside>
                <!-- ASSET IMAGE CARD -->
                <?php if ($StockID != ''): ?>
                    <div class="aw-card">
                        <div class="aw-card-header">
                            <h3 class="aw-card-title"><i class="fas fa-image"></i> <?php echo __('Asset Visual'); ?></h3>
                        </div>
                        <div class="aw-card-body text-center">
                            <div class="aw-image-container">
                                <?php 
                                $ImgGlob = glob($_SESSION['part_pics_dir'] . '/' . $StockID . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE);
                                $Pic = reset($ImgGlob);
                                if ($Pic): ?>
                                    <img src="<?php echo $Pic; ?>" />
                                <?php else: ?>
                                    <i class="fas fa-image fa-4x" style="color: var(--border);"></i>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="ItemPicture" id="PicUp" style="display: none;" onchange="this.form.submit()" />
                            <button type="button" class="aw-btn aw-btn-outline aw-btn-sm w-100" onclick="document.getElementById('PicUp').click()">
                                <i class="fas fa-upload"></i> <?php echo __('Update Photo'); ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ATTRIBUTE FLAGS CARD -->
                <div class="aw-card">
                    <div class="aw-card-header">
                        <h3 class="aw-card-title"><i class="fas fa-tags"></i> <?php echo __('Attribute Flags'); ?></h3>
                    </div>
                    <div class="aw-card-body" style="padding-top: 0; padding-bottom: 0;">
                        <?php 
                        $Flags = array(
                            'discontinued' => array('icon' => 'fa-ban', 'label' => __('Discontinued')),
                            'controlled' => array('icon' => 'fa-barcode', 'label' => __('Batch Controlled')),
                            'serialised' => array('icon' => 'fa-hashtag', 'label' => __('Serialised')),
                            'perishable' => array('icon' => 'fa-clock', 'label' => __('Perishable'))
                        );
                        foreach ($Flags as $fKey => $fData): 
                            $val = ($_POST[$fKey] ?? 0);
                        ?>
                            <div class="aw-flag-row">
                                <div class="aw-flag-label">
                                    <i class="fas <?php echo $fData['icon']; ?>"></i>
                                    <span><?php echo $fData['label']; ?></span>
                                </div>
                                <select name="<?php echo ucfirst($fKey); ?>" class="aw-select" style="width: 80px; padding: 0.3rem 0.5rem; font-size: 0.8rem;">
                                    <option value="1" <?php echo ($val == 1 ? 'selected' : ''); ?>><?php echo __('Yes'); ?></option>
                                    <option value="0" <?php echo ($val == 0 ? 'selected' : ''); ?>><?php echo __('No'); ?></option>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- QUICK ACTIONS CARD -->
                <?php if (isset($StockID) && $StockID != '' && $New == 0): ?>
                    <div class="aw-card">
                        <div class="aw-card-header">
                            <h3 class="aw-card-title"><i class="fas fa-bolt"></i> <?php echo __('Quick Actions'); ?></h3>
                        </div>
                        <div class="aw-card-body">
                            <a href="<?php echo $RootPath; ?>/SelectProduct.php?StockID=<?php echo urlencode($StockID); ?>" class="aw-quick-action-link">
                                <i class="fas fa-chart-line"></i> <?php echo __('Inventory Dashboard'); ?>
                            </a>
                            <a href="<?php echo $RootPath; ?>/Prices.php?Item=<?php echo urlencode($StockID); ?>" class="aw-quick-action-link">
                                <i class="fas fa-tag"></i> <?php echo __('Maintain Pricing'); ?>
                            </a>
                            <a href="<?php echo $RootPath; ?>/PurchData.php?StockID=<?php echo urlencode($StockID); ?>" class="aw-quick-action-link">
                                <i class="fas fa-truck"></i> <?php echo __('Assign Suppliers'); ?>
                            </a>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-soft);">
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="aw-btn aw-btn-outline aw-btn-sm w-100">
                                    <i class="fas fa-plus-circle"></i> <?php echo __('Add Another Item'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="aw-card" style="background: var(--primary-soft); border-color: var(--primary);">
                    <div class="aw-card-body">
                        <div class="aw-label" style="color: var(--primary); margin-bottom: 0.5rem;">
                            <i class="fas fa-lightbulb"></i> <?php echo __('Pro Tip'); ?>
                        </div>
                        <p style="font-size: 0.75rem; color: var(--primary-dark); margin: 0; line-height: 1.4;">
                            <?php echo __('Ensure correct UOM is set before saving, as changing it later may affect historical stock movements.'); ?>
                        </p>
                    </div>
                </div>
            </aside>
        </div>
        
        <input type="hidden" name="New" value="<?php echo $New; ?>" />
    </form>
</div>

<?php 
include(__DIR__ . '/includes/footer.php');
?>
