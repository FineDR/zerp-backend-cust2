<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Category Maintenance');
$ViewTopic = 'Setup';
$BookMark = '';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_GET['SelectedCategory'])) {
	$SelectedCategory = mb_strtoupper($_GET['SelectedCategory']);
} elseif (isset($_POST['SelectedCategory'])) {
	$SelectedCategory = mb_strtoupper($_POST['SelectedCategory']);
}

$SupportedImgExt = array('png', 'jpg', 'jpeg');
$DisplayRecords = $_SESSION['DisplayRecordsMax'] ?? 20;

// HANDLING ACTIONS
if (isset($_GET['AddFeature'])) {
	$SQL = "UPDATE salescatprod SET featured=1 WHERE salescatid='" . $SelectedCategory . "' AND stockid='" . $_GET['StockID'] . "'";
	DB_query($SQL);
	prnMsg(__('Featured status enabled'), 'success');
	$_GET['Select'] = 'Yes';
}
if (isset($_GET['RemoveFeature'])) {
	$SQL = "UPDATE salescatprod SET featured=0 WHERE salescatid='" . $SelectedCategory . "' AND stockid='" . $_GET['StockID'] . "'";
	DB_query($SQL);
	prnMsg(__('Featured status disabled'), 'success');
	$_GET['Select'] = 'Yes';
}
if (isset($_GET['DelStockID'])) {
	$SQL = "DELETE FROM salescatprod WHERE salescatid='" . $SelectedCategory . "' AND stockid='" . $_GET['DelStockID'] . "'";
	DB_query($SQL);
	prnMsg(__('Item removed from category'), 'success');
	$_GET['Select'] = 'Yes';
}
if (isset($_POST['AddItems'])) {
	foreach ($_POST as $Key => $Value) {
		if (substr($Key, 0, 8) == 'StockID_') {
			$SID = substr($Key, 8);
			$Brand = $_POST['Brand_' . $SID];
			if ($Brand != '') {
				$SQL = "INSERT INTO salescatprod (stockid, salescatid, manufacturers_id) VALUES ('" . $SID . "', '" . $SelectedCategory . "', '" . $Brand . "')";
				DB_query($SQL);
				prnMsg(__('Item') . ' ' . $SID . ' ' . __('added'), 'success');
			}
		}
	}
	$_GET['Select'] = 'Yes';
}

if (isset($_POST['SubmitCategory'])) {
	$InputError = 0;
	if (mb_strlen($_POST['SalesCatName']) > 50 or trim($_POST['SalesCatName']) == '') {
		$InputError = 1;
		prnMsg(__('Invalid category name'), 'error');
	}

	if (isset($SelectedCategory) and $InputError != 1) {
		$SQL = "UPDATE salescat SET salescatname = '" . $_POST['SalesCatName'] . "', parentcatid = '" . $_POST['ParentCategory'] . "', active  = '" . $_POST['Active'] . "' WHERE salescatid = '" . $SelectedCategory . "'";
		DB_query($SQL);
		prnMsg(__('Category updated'), 'success');
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO salescat (salescatname, parentcatid, active) VALUES ('" . $_POST['SalesCatName'] . "', '" . $_POST['ParentCategory'] . "', '" . $_POST['Active'] . "')";
		DB_query($SQL);
		$SelectedCategory = DB_Last_Insert_ID('salescat', 'salescatid');
		prnMsg(__('New category created'), 'success');
	}
	
	if ($InputError != 1 && isset($_FILES['CategoryPicture']) && $_FILES['CategoryPicture']['name'] != '') {
		$ImgExt = pathinfo($_FILES['CategoryPicture']['name'], PATHINFO_EXTENSION);
		if (in_array(strtolower($ImgExt), $SupportedImgExt)) {
			$FileName = $_SESSION['part_pics_dir'] . '/SALESCAT_' . $SelectedCategory . '.' . $ImgExt;
			foreach ($SupportedImgExt as $ext) {
				if (file_exists($_SESSION['part_pics_dir'] . '/SALESCAT_' . $SelectedCategory . '.' . $ext)) {
					unlink($_SESSION['part_pics_dir'] . '/SALESCAT_' . $SelectedCategory . '.' . $ext);
				}
			}
			move_uploaded_file($_FILES['CategoryPicture']['tmp_name'], $FileName);
		}
	}
	if (isset($_POST['ClearImage'])) {
		foreach ($SupportedImgExt as $ext) {
			if (file_exists($_SESSION['part_pics_dir'] . '/SALESCAT_' . $SelectedCategory . '.' . $ext)) {
				unlink($_SESSION['part_pics_dir'] . '/SALESCAT_' . $SelectedCategory . '.' . $ext);
			}
		}
	}
	unset($SelectedCategory);
}

// SEARCH LOGIC (AVAIL ITEMS)
$SearchResult = null;
if (isset($_POST['Search']) or isset($_POST['Prev']) or isset($_POST['Next'])) {
	$_POST['Keywords'] = mb_strtoupper($_POST['Keywords'] ?? '');
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
	$SearchCode = '%' . ($_POST['StockCode'] ?? '') . '%';
	$SCat = ($_POST['StockCat'] == 'All') ? '%' : $_POST['StockCat'];

	$SQL = "SELECT stockmaster.stockid, description, stockmaster.units
			FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid
			WHERE stockmaster.description " . LIKE . " '" . $SearchString . "'
				AND stockmaster.categoryid " . LIKE . " '" . $SCat . "'
				AND stockmaster.stockid " . LIKE . " '" . $SearchCode . "'
				AND stockmaster.discontinued=0
				AND NOT EXISTS (SELECT stockid FROM salescatprod WHERE salescatid='" . $SelectedCategory . "' AND stockid=stockmaster.stockid)
			ORDER BY stockmaster.stockid LIMIT " . $DisplayRecords;
	$SearchResult = DB_query($SQL);
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
if (isset($_GET['Select']) && isset($SelectedCategory)) {
	// SEARCH SIDEBAR
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Find Items') . '</h3></div>
			<div class="db-card-body">
				<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $SelectedCategory . '&Select=Yes">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-form-group">
						<label class="db-label">' . __('Base Category') . '</label>
						<select name="StockCat" class="db-select">
							<option value="All">' . __('All Categories') . '</option>';
	$SCatsResult = DB_query("SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype='F' OR stocktype='M'");
	while ($cRow = DB_fetch_array($SCatsResult)) {
		echo '<option ' . (($_POST['StockCat'] ?? '') == $cRow['categoryid'] ? 'selected' : '') . ' value="' . $cRow['categoryid'] . '">' . $cRow['categorydescription'] . '</option>';
	}
	echo '				</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Keywords') . '</label>
						<input type="text" name="Keywords" class="db-input" value="' . ($_POST['Keywords'] ?? '') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Code') . '</label>
						<input type="text" name="StockCode" class="db-input" value="' . ($_POST['StockCode'] ?? '') . '" />
					</div>
					<button type="submit" name="Search" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;"><i class="fas fa-search"></i> ' . __('Search Now') . '</button>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-input-light" style="width: 100%; margin-top: 10px; text-align:center;"><i class="fas fa-arrow-left"></i> ' . __('Exit Assignment') . '</a>
				</form>
			</div>
		  </div>';
} else {
	// MAINTENANCE SIDEBAR
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-tags"></i> ' . (isset($SelectedCategory) ? __('Edit Category') : __('New Category')) . '</h3></div>
			<div class="db-card-body">
				<form enctype="multipart/form-data" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	if (isset($SelectedCategory)) {
		$SQL = "SELECT * FROM salescat WHERE salescatid='" . $SelectedCategory . "'";
		$cRow = DB_fetch_array(DB_query($SQL));
		$_POST['SalesCatName'] = $cRow['salescatname'];
		$_POST['ParentCategory'] = $cRow['parentcatid'];
		$_POST['Active'] = $cRow['active'];
		echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';
	}
	echo '			<div class="db-form-group">
						<label class="db-label">' . __('Full Name') . '</label>
						<input type="text" name="SalesCatName" class="db-input" required maxlength="50" value="' . ($_POST['SalesCatName'] ?? '') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Parent Category') . '</label>
						<select name="ParentCategory" class="db-select">
							<option value="0">' . __('No Parent') . '</option>';
	$Parents = DB_query("SELECT salescatid, salescatname FROM salescat");
	while ($pRow = DB_fetch_array($Parents)) {
		if (isset($SelectedCategory) && $pRow['salescatid'] == $SelectedCategory) continue;
		echo '<option ' . (($_POST['ParentCategory'] ?? 0) == $pRow['salescatid'] ? 'selected' : '') . ' value="' . $pRow['salescatid'] . '">' . $pRow['salescatname'] . '</option>';
	}
	echo '				</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Active Status') . '</label>
						<select name="Active" class="db-select">
							<option ' . (($_POST['Active'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Active') . '</option>
							<option ' . (($_POST['Active'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('Disabled') . '</option>
						</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Banner/Icon') . '</label>
						<input type="file" name="CategoryPicture" class="db-input db-input-light" accept="image/*" />';
	if (isset($SelectedCategory)) {
		echo '			<div style="margin-top: 10px; display: flex; align-items: center; gap: 8px;">
							<input type="checkbox" name="ClearImage" id="ClearImg" /><label class="db-label mb-0" for="ClearImg">' . __('Remove Photo') . '</label>
						</div>';
	}
	echo '			</div>
					<button type="submit" name="SubmitCategory" class="db-btn db-btn-primary" style="width: 100%; margin-top: 20px;"><i class="fas fa-save"></i> ' . __('Save Category') . '</button>
				</form>
			</div>
		  </div>';
}
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';

if (isset($_GET['Select']) && isset($SelectedCategory)) {
	// Mode: Manage Items
	$CName = DB_fetch_array(DB_query("SELECT salescatname FROM salescat WHERE salescatid='" . $SelectedCategory . "'"))['salescatname'];
	
	// Current Items Grid (Paginated)
	$CountSQL = "SELECT COUNT(*) FROM salescatprod WHERE salescatid='" . $SelectedCategory . "'";
	$TotalMatches = DB_fetch_row(DB_query($CountSQL))[0];
	$Pages = ceil($TotalMatches / $DisplayRecords);
	$Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
	if ($Page < 1) $Page = 1;
	if ($Page > $Pages && $Pages > 0) $Page = $Pages;
	$Offset = ($Page - 1) * $DisplayRecords;

	echo '<div class="db-card" style="margin-bottom: 25px;">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-link"></i> ' . __('Mapped Items') . ': <span class="text-primary">' . $CName . '</span></h3>';
	if ($Pages > 1) {
		echo '	<div class="db-pagination" style="display: flex; gap: 5px; align-items: center;">';
		if ($Page > 1) echo ' <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $SelectedCategory . '&Select=Yes&Page=' . ($Page - 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-left"></i></a>';
		echo '		<span class="db-muted" style="font-size: 0.8rem;">' . __('Page') . ' ' . $Page . ' ' . __('of') . ' ' . $Pages . '</span>';
		if ($Page < $Pages) echo ' <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $SelectedCategory . '&Select=Yes&Page=' . ($Page + 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-right"></i></a>';
		echo '	</div>';
	}
	echo '</div>
			<div class="db-card-body p-0">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Brand') . '</th>
								<th class="text-center">' . __('Featured') . '</th>
								<th class="text-right">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	$MappedSQL = "SELECT salescatprod.stockid, featured, description, manufacturers_name 
				  FROM salescatprod INNER JOIN stockmaster ON salescatprod.stockid=stockmaster.stockid 
				  INNER JOIN manufacturers ON salescatprod.manufacturers_id=manufacturers.manufacturers_id
				  WHERE salescatid='" . $SelectedCategory . "'
				  ORDER BY salescatprod.stockid ASC LIMIT " . $DisplayRecords . " OFFSET " . $Offset;
	$mRes = DB_query($MappedSQL);
	if (DB_num_rows($mRes) == 0) {
		echo '<tr><td colspan="5" class="text-center db-muted p-5">' . __('No items currently mapped to this sales category.') . '</td></tr>';
	} else {
		while ($mRow = DB_fetch_array($mRes)) {
			echo '<tr>
					<td><div class="db-font-bold text-primary">' . $mRow['stockid'] . '</div></td>
					<td>' . $mRow['description'] . '</td>
					<td><span class="db-badge">' . $mRow['manufacturers_name'] . '</span></td>
					<td class="text-center">';
			if ($mRow['featured']) {
				echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?RemoveFeature=1&SelectedCategory=' . $SelectedCategory . '&StockID=' . $mRow['stockid'] . '&Page=' . $Page . '" class="db-badge db-badge-success" title="Click to disable"><i class="fas fa-star"></i> ' . __('Featured') . '</a>';
			} else {
				echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?AddFeature=1&SelectedCategory=' . $SelectedCategory . '&StockID=' . $mRow['stockid'] . '&Page=' . $Page . '" class="db-badge db-badge-secondary" title="Click to feature"><i class="far fa-star"></i></a>';
			}
			echo '</td>
					<td class="text-right">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DelStockID=' . $mRow['stockid'] . '&SelectedCategory=' . $SelectedCategory . '&Page=' . $Page . '" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'Remove item?\');"><i class="fas fa-trash"></i> ' . __('Remove') . '</a>
					</td>
				  </tr>';
		}
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';

	// Search Results Grid
	if ($SearchResult) {
		echo '<div class="db-card">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Available Items') . '</h3></div>
				<div class="db-card-body p-0">
					<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $SelectedCategory . '&Select=Yes">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />
						<div class="db-table-wrapper">
							<table class="db-table">
								<thead>
									<tr>
										<th style="width: 40px;"></th>
										<th>' . __('Item Info') . '</th>
										<th>' . __('Assign Brand') . '</th>
									</tr>
								</thead>
								<tbody>';
		$BrandsRes = DB_query("SELECT manufacturers_id, manufacturers_name FROM manufacturers");
		$BrandsArr = array();
		while ($bRow = DB_fetch_array($BrandsRes)) $BrandsArr[] = $bRow;

		while ($sRow = DB_fetch_array($SearchResult)) {
			echo '<tr>
					<td><input type="checkbox" name="StockID_' . $sRow['stockid'] . '" /></td>
					<td>
						<div class="db-font-bold text-primary">' . $sRow['stockid'] . '</div>
						<div class="db-muted" style="font-size: 0.8rem;">' . $sRow['description'] . ' (' . $sRow['units'] . ')</div>
					</td>
					<td>
						<select name="Brand_' . $sRow['stockid'] . '" class="db-select db-select-sm">
							<option value="">' . __('Select Brand...') . '</option>';
			foreach ($BrandsArr as $b) {
				echo '<option value="' . $b['manufacturers_id'] . '">' . $b['manufacturers_name'] . '</option>';
			}
			echo '		</select>
					</td>
				  </tr>';
		}
		echo '					</tbody>
							</table>
						</div>
						<div class="p-4 bg-light text-center">
							<button type="submit" name="AddItems" class="db-btn db-btn-primary"><i class="fas fa-plus-circle"></i> ' . __('Batch Map Selected Items') . '</button>
						</div>
					</form>
				</div>
			  </div>';
	}

} else {
	// Mode: List Categories (Paginated)
	$CountSQL = "SELECT COUNT(*) FROM salescat";
	$TotalMatches = DB_fetch_row(DB_query($CountSQL))[0];
	$Pages = ceil($TotalMatches / $DisplayRecords);
	$Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
	if ($Page < 1) $Page = 1;
	if ($Page > $Pages && $Pages > 0) $Page = $Pages;
	$Offset = ($Page - 1) * $DisplayRecords;

	$SQL = "SELECT * FROM salescat ORDER BY salescatname LIMIT " . $DisplayRecords . " OFFSET " . $Offset;
	$Result = DB_query($SQL);
	echo '<div class="db-card">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-stream"></i> ' . __('Sales Catalog Groups') . '</h3>';
	if ($Pages > 1) {
		echo '	<div class="db-pagination" style="display: flex; gap: 5px; align-items: center;">';
		if ($Page > 1) echo ' <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Page=' . ($Page - 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-left"></i></a>';
		echo '		<span class="db-muted" style="font-size: 0.8rem;">' . __('Page') . ' ' . $Page . ' ' . __('of') . ' ' . $Pages . '</span>';
		if ($Page < $Pages) echo ' <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Page=' . ($Page + 1) . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-chevron-right"></i></a>';
		echo '	</div>';
	}
	echo '</div>
			<div class="db-card-body p-0">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Icon') . '</th>
								<th>' . __('Category Description') . '</th>
								<th>' . __('Hierarchy') . '</th>
								<th class="text-center">' . __('Active') . '</th>
								<th class="text-right">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';
	while ($Row = DB_fetch_array($Result)) {
		$Glob = glob($_SESSION['part_pics_dir'] . '/SALESCAT_' . $Row['salescatid'] . '.{' . implode(",", $SupportedImgExt) . '}', GLOB_BRACE);
		$ImgFile = reset($Glob);
		$ImgLink = GetImageLink($ImgFile, 'SALESCAT_' . $Row['salescatid'], 50, 50, "db-avatar", "");
		
		$ParentName = __('Sub-Category');
		if ($Row['parentcatid'] == 0) $ParentName = __('Master Group');
		else {
			$PRes = DB_query("SELECT salescatname FROM salescat WHERE salescatid='" . $Row['parentcatid'] . "'");
			if (DB_num_rows($PRes)) $ParentName = DB_fetch_array($PRes)['salescatname'];
		}

		echo '<tr>
				<td style="width: 70px;">' . $ImgLink . '</td>
				<td>
					<div class="db-font-bold text-primary">' . $Row['salescatname'] . '</div>
					<div class="db-muted" style="font-size: 0.8rem;">ID: ' . $Row['salescatid'] . '</div>
				</td>
				<td><span class="db-badge ' . ($Row['parentcatid'] == 0 ? 'db-badge-primary' : '') . '">' . $ParentName . '</span></td>
				<td class="text-center">' . ($Row['active'] == 1 ? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>' : '<span class="db-badge db-badge-secondary">' . __('No') . '</span>') . '</td>
				<td class="text-right">
					<div style="display: flex; gap: 8px; justify-content: flex-end;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $Row['salescatid'] . '&Select=Yes" class="db-btn db-btn-sm db-btn-outline-primary" title="' . __('Map Items') . '"><i class="fas fa-boxes"></i> ' . __('Items') . '</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $Row['salescatid'] . '&Edit=Yes&Page=' . $Page . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i> ' . __('Edit') . '</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $Row['salescatid'] . '&Delete=yes&Page=' . $Page . '" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Delete category?') . '\');"><i class="fas fa-trash"></i> ' . __('Del') . '</a>
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
