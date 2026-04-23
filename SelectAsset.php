<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Select an Asset');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetSelection';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['AssetID'])) {
	//The page is called with a AssetID
	$_POST['Select'] = $_GET['AssetID'];
}

if (isset($_GET['NewSearch']) OR isset($_POST['Next']) OR isset($_POST['Previous']) OR isset($_POST['Go'])) {
	unset($AssetID);
	unset($_SESSION['SelectedAsset']);
	unset($_POST['Select']);
}
if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}
}
if (isset($_POST['AssetCode'])) {
	$_POST['AssetCode'] = trim(mb_strtoupper($_POST['AssetCode']));
}

if (!isset($_POST['DisposalStatus'])) {
	$_POST['DisposalStatus'] = "ACTIVE";
}

// Always show the search facilities
$SQL = "SELECT categoryid,
				categorydescription
			FROM fixedassetcategories
			ORDER BY categorydescription";
$Result = DB_query($SQL);
if (DB_num_rows($Result) == 0) {
	echo '<p><font size="4" color="red">' . __('Problem Report') . ':</font><br />' .
		__('There are no asset categories currently defined please use the link below to set them up');
	echo '<br /><a href="' . $RootPath . '/FixedAssetCategories.php">' . __('Define Asset Categories') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}
// end of showing search facilities

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-search"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Locate and manage your fixed assets') . '</div>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/FixedAssetItems.php" class="db-btn db-btn-primary">
					<i class="fas fa-plus"></i> ' . __('Add New Asset') . '
				</a>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		
		<div class="db-centered-container" style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
			<div class="db-card" style="margin-bottom: 30px; border-top: 3px solid var(--text-main);">
				<div class="db-card-body" style="padding: 30px;">
					<div class="db-grid db-grid-3">
						<div class="db-form-group">
							<label class="db-label">' . __('Asset Category') . '</label>
							<select name="AssetCategory" class="db-select">';

if (!isset($_POST['AssetCategory'])) {
	$_POST['AssetCategory'] = 'ALL';
}
if ($_POST['AssetCategory'] == 'ALL') {
	echo '<option selected="selected" value="ALL">' . __('Any asset category') . '</option>';
} else {
	echo '<option value="ALL">' . __('Any asset category') . '</option>';
}

while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['categoryid'] == $_POST['AssetCategory']) {
		echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
}
echo '				</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Keywords') . '</label>';
if (isset($_POST['Keywords'])) {
	echo '				<input type="text" name="Keywords" class="db-input" autofocus="autofocus" value="' . $_POST['Keywords'] . '" />';
} else {
	echo '				<input type="text" name="Keywords" class="db-input" autofocus="autofocus" />';
}
echo '				</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Location') . '</label>
							<select name="AssetLocation" class="db-select">';

if (!isset($_POST['AssetLocation'])) {
	$_POST['AssetLocation'] = 'ALL';
}
if ($_POST['AssetLocation'] == 'ALL') {
	echo '<option selected="selected" value="ALL">' . __('All Locations') . '</option>';
} else {
	echo '<option value="ALL">' . __('All Locations') . '</option>';
}
$Result = DB_query("SELECT locationid, locationdescription FROM fixedassetlocations");

while ($MyRow = DB_fetch_array($Result)) {
	if ($MyRow['locationid'] == $_POST['AssetLocation']) {
		echo '<option selected="selected" value="' . $MyRow['locationid'] . '">' . $MyRow['locationdescription'] . '</option>';
	} else {
		echo '<option value="' . $MyRow['locationid'] . '">' . $MyRow['locationdescription'] . '</option>';
	}
}
echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Asset Code') . '</label>';
if (isset($_POST['AssetCode'])) {
	echo '				<input type="text" class="db-input number" name="AssetCode" value="' . $_POST['AssetCode'] . '" />';
} else {
	echo '				<input type="text" class="db-input" name="AssetCode" />';
}
echo '				</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Disposal Status') . '</label>
							<select name="DisposalStatus" class="db-select">';

if ($_POST['DisposalStatus'] == 'ALL') {
	echo '	<option selected="selected" value="ALL">' . __('All statuses') . '</option>
			<option value="ACTIVE">' . __('Active only') . '</option>
			<option value="DISPOSED">' . __('Disposed only') . '</option>';
} elseif ($_POST['DisposalStatus'] == 'ACTIVE') {
	echo '	<option value="ALL">' . __('All statuses') . '</option>
			<option selected="selected" value="ACTIVE">' . __('Active only') . '</option>
			<option value="DISPOSED">' . __('Disposed only') . '</option>';
} else {
	echo '	<option value="ALL">' . __('All statuses') . '</option>
			<option value="ACTIVE">' . __('Active only') . '</option>
			<option selected="selected" value="DISPOSED">' . __('Disposed only') . '</option>';
}
echo '					</select>
						</div>
						
						<div class="db-form-group" style="display: flex; align-items: flex-end;">
							<button type="submit" name="Search" class="db-btn db-btn-primary" style="width: 100%; justify-content: center; padding: 10px;">
								<i class="fas fa-search"></i> ' . __('Search Assets') . '
							</button>
						</div>
					</div>
				</div>
			</div>';

// query for list of record(s)
if (isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	$_POST['Search'] = 'Search';
}
if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) {
		// if Search then set to first page
		$_POST['PageOffset'] = 1;
	}
	if ($_POST['Keywords'] AND $_POST['AssetCode']) {
		prnMsg(__('Asset description keywords have been used in preference to the asset code extract entered'), 'info');
	}
	$SQL = "SELECT assetid,
					description,
					datepurchased,
					fixedassetlocations.locationdescription
			FROM fixedassets INNER JOIN fixedassetlocations
			ON fixedassets.assetlocation=fixedassetlocations.locationid ";

	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		if ($_POST['AssetCategory'] == 'ALL') {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= "WHERE description " . LIKE . " '" . $SearchString . "'";
			} else {
				$SQL .= "WHERE fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'
						AND description " . LIKE . " '" . $SearchString . "'";
			}
		} else {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= "WHERE description " . LIKE . " '" . $SearchString . "'
						AND assetcategoryid='" . $_POST['AssetCategory'] . "'";
			} else {
				$SQL .= "WHERE fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'
						AND description " . LIKE . " '" . $SearchString . "'
						AND assetcategoryid='" . $_POST['AssetCategory'] . "'";
			}
		}
	} elseif (isset($_POST['AssetCode'])) {
		if ($_POST['AssetCategory'] == 'ALL') {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= "WHERE fixedassets.assetid " . LIKE . " '%" . $_POST['AssetCode'] . "%'";
			} else {
				$SQL .= "WHERE fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'
						AND fixedassets.assetid " . LIKE . " '%" . $_POST['AssetCode'] . "%'";
			}
		} else {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= "WHERE fixedassets.assetid " . LIKE . " '%" . $_POST['AssetCode'] . "%'
						AND assetcategoryid='" . $_POST['AssetCategory'] . "'";
			} else {
				$SQL .= "WHERE fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'
						AND fixedassets.assetid " . LIKE . " '%" . $_POST['AssetCode'] . "%'
						AND assetcategoryid='" . $_POST['AssetCategory'] . "'";
			}
		}
	} elseif (!isset($_POST['AssetCode']) AND !isset($_POST['Keywords'])) {
		if ($_POST['AssetCategory'] == 'All') {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= 'WHERE 1=1 ';
			} else {
				$SQL .= "WHERE fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'";
			}
		} else {
			if ($_POST['AssetLocation'] == 'ALL') {
				$SQL .= "WHERE assetcategoryid='" . $_POST['AssetCategory'] . "'";
			} else {
				$SQL .= "WHERE assetcategoryid='" . $_POST['AssetCategory'] . "'
						AND fixedassets.assetlocation='" . $_POST['AssetLocation'] . "'";
			}
		}
	}

	if ($_POST['DisposalStatus'] == 'ALL') {
		$SQL .= ' ';
	} elseif ($_POST['DisposalStatus'] == 'ACTIVE') {
		$SQL .= ' AND disposaldate = "1000-01-01"';
	} else {
		$SQL .= ' AND disposaldate != "1000-01-01"';
	}

	$SQL .= " ORDER BY fixedassets.assetid";

	$ErrMsg = __('No assets were returned by the SQL because');
	$SearchResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('No assets were returned by this search please re-enter alternative criteria to try again'), 'info');
	}
	unset($_POST['Search']);
}
/* end query for list of records */
/* display list if there is more than one record */
if (isset($SearchResult) AND !isset($_POST['Select'])) {
	$ListCount = DB_num_rows($SearchResult);
	if ($ListCount > 0) {
		// If the user hit the search button and there is more than one item to show
		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) {
				$_POST['PageOffset']++;
			}
		}
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) {
				$_POST['PageOffset']--;
			}
		}
		if ($_POST['PageOffset'] > $ListPageMax) {
			$_POST['PageOffset'] = $ListPageMax;
		}
			echo '					<div class="db-pagination-controls">
								<span>' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . ' ' . __('pages') . '</span>
								<div class="db-btn-group">';
			
			if ($_POST['PageOffset'] > 1) {
				echo '			<button type="submit" name="Previous" class="db-btn db-btn-secondary db-btn-sm"><i class="fas fa-chevron-left"></i> ' . __('Prev') . '</button>';
			}
			
			echo '				<select name="PageOffset" class="db-select db-select-sm" style="width: auto;" onchange="this.form.submit()">';
			$ListPage = 1;
			while ($ListPage <= $ListPageMax) {
				$selected = ($ListPage == $_POST['PageOffset']) ? 'selected="selected"' : '';
				echo '				<option value="' . $ListPage . '" ' . $selected . '>' . $ListPage . '</option>';
				$ListPage++;
			}
			echo '				</select>';
			
			if ($_POST['PageOffset'] < $ListPageMax) {
				echo '			<button type="submit" name="Next" class="db-btn db-btn-secondary db-btn-sm">' . __('Next') . ' <i class="fas fa-chevron-right"></i></button>';
			}
			
			echo '				</div>
							</div>';
		}
		echo '</form>';

		echo '<form action="' . $RootPath . '/FixedAssetItems.php" method="post">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

		echo '<div class="db-card" style="margin-top: 20px; border: none; box-shadow: var(--shadow-md);">
				<div class="db-table-wrap" style="overflow-x: auto;">
					<table class="db-table monochromatic-table">
						<thead>';
		$TableHeader = '	<tr>
								<th style="padding-left: 24px;">' . __('Asset ID') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Location') . '</th>
								<th>' . __('Purchased') . '</th>
								<th class="db-table-actions" style="padding-right: 24px;">' . __('Action') . '</th>
							</tr>';
		echo $TableHeader;
		echo '			</thead>
						<tbody>';

		$j = 1;
		$RowIndex = 0;
		if (DB_num_rows($SearchResult) <> 0) {
			DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}
		while (($MyRow = DB_fetch_array($SearchResult)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			echo '<tr>
				<td class="db-font-bold" style="padding-left: 24px;">' . $MyRow['assetid'] . '</td>
				<td>' . $MyRow['description'] . '</td>
				<td><span class="db-badge">' . $MyRow['locationdescription'] . '</span></td>
				<td>' . ConvertSQLDate($MyRow['datepurchased']) . '</td>
				<td class="db-table-actions" style="padding-right: 24px;">
					<button type="submit" name="Select" value="' . $MyRow['assetid'] . '" class="db-btn db-btn-secondary db-btn-sm">
						<i class="fas fa-arrow-right"></i> ' . __('Manage') . '
					</button>
				</td>
				</tr>';
			$RowIndex = $RowIndex + 1;
		}
		echo '			</tbody>
					</table>
				</div>
			  </div>';
	}

echo '		</div> 
	  </div> 
</form>'; // End centered container, db-page, and form

echo '<style>
.monochromatic-table th { background: transparent !important; color: var(--text-main) !important; border-bottom: 2px solid var(--border) !important; }
.monochromatic-table tr:hover td { background: transparent !important; }
.monochromatic-table td { border-bottom: 1px solid var(--border-soft); }
</style>';

include(__DIR__ . '/includes/footer.php');
