<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Change Asset Location');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetTransfer';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-exchange-alt"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Relocate assets between physical locations') . '</div>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		
		<div class="db-centered-container" style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
';

foreach ($_POST as $AssetToMove => $Value) { //Value is not used?
	if (mb_substr($AssetToMove,0,4)=='Move') { // the form variable is of the format MoveAssetID so need to strip the move bit off
		$AssetID	= mb_substr($AssetToMove,4);
		if (isset($_POST['Location' . $AssetID]) AND $_POST['Location' . $AssetID] != ''){
			$SQL		= "UPDATE fixedassets
						SET assetlocation='".$_POST['Location'.$AssetID] ."'
						WHERE assetid='". $AssetID . "'";

			$Result = DB_query($SQL);
			prnMsg(__('The Fixed Asset has been moved successfully'), 'success');
			echo '<br />';
		}
	}
}

if (isset($_GET['AssetID'])) {
	$AssetID=$_GET['AssetID'];
} elseif (isset($_POST['AssetID'])) {
	$AssetID=$_POST['AssetID'];
} else {
	$SQL="SELECT categoryid, categorydescription FROM fixedassetcategories";
	$Result = DB_query($SQL);
	echo '<div class="db-card" style="margin-bottom: 30px; border-top: 3px solid var(--text-main);">
				<div class="db-card-body" style="padding: 30px;">
					<div class="db-grid db-grid-3 db-grid-mobile-stack">
						<div class="db-form-group">
							<label class="db-label">' . __('Asset Category') . '</label>
							<select name="AssetCat" class="db-select">';

	if (!isset($_POST['AssetCat'])) {
		$_POST['AssetCat'] = 'All';
	}
	if ($_POST['AssetCat'] == 'All') {
		echo '<option selected="selected" value="All">' . __('All Categories') . '</option>';
	} else {
		echo '<option value="All">' . __('All Categories') . '</option>';
	}
	
	$Result = DB_query("SELECT categoryid, categorydescription FROM fixedassetcategories");
	while ($MyRow = DB_fetch_array($Result)) {
		if ($MyRow['categoryid'] == $_POST['AssetCat']) {
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
		echo '				<input type="text" name="Keywords" class="db-input" value="' . trim($_POST['Keywords'], '%') . '" />';
	} else {
		echo '				<input type="text" name="Keywords" class="db-input" />';
	}
	echo '				</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Current Location') . '</label>
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
						
						<div class="db-form-group" style="display: flex; align-items: flex-end; grid-column: span 3;">
							<button type="submit" name="Search" class="db-btn db-btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
								<i class="fas fa-search"></i> ' . __('Search Assets for Transfer') . '
							</button>
						</div>
					</div>
				</div>
			</div>';
}

if (isset($_POST['Search'])) {

	if ($_POST['AssetLocation']=='ALL') {
		$AssetLocation	='%';
	} else {
		$AssetLocation	= '%'.$_POST['AssetLocation'].'%';
	}
	if ($_POST['AssetCat']=='All') {
		$AssetID	='%';
	}
	if (isset($_POST['Keywords'])) {
		$Keywords	='%'.$_POST['Keywords'].'%';
	} else {
		$Keywords	='%';
	}
	if (isset($_POST['AssetID'])) {
		$AssetID	='%'.$_POST['AssetID'].'%';
	} else {
		$AssetID	='%';
	}


	$SQL= "SELECT fixedassets.assetid,
				fixedassets.cost,
				fixedassets.accumdepn,
				fixedassets.description,
				fixedassets.depntype,
				fixedassets.serialno,
				fixedassets.barcode,
				fixedassets.assetlocation as ItemAssetLocation,
				fixedassetlocations.locationdescription
			FROM fixedassets
			INNER JOIN fixedassetlocations
			ON fixedassets.assetlocation=fixedassetlocations.locationid
			WHERE fixedassets.assetcategoryid " . LIKE . "'".$_POST['AssetCat']."'
			AND fixedassets.description " . LIKE . "'".$Keywords."'
			AND fixedassets.assetid " . LIKE . "'".$AssetID."'
			AND fixedassets.assetlocation " . LIKE . "'".$AssetLocation."'
			ORDER BY fixedassets.assetid";


	$Result = DB_query($SQL);
	echo '<br />';
	echo '<div class="db-card" style="margin-top: 25px; border: none; box-shadow: var(--shadow-md);">
				<div class="db-table-wrap" style="overflow-x: auto;">
					<table class="db-table monochromatic-table">
						<thead>
							<tr>
								<th style="padding-left: 24px;">' . __('Asset ID') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('NBV') . '</th>
								<th>' . __('From Location') . '</th>
								<th>' . __('Move To') . '</th>
								<th style="padding-right: 24px;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';

	$LocationSQL="SELECT locationid, locationdescription from fixedassetlocations";
	$LocationResult = DB_query($LocationSQL);

	while ($MyRow=DB_fetch_array($Result)) {
		$nbv = $MyRow['cost'] - $MyRow['accumdepn'];
		echo '<tr>
				<td class="db-font-bold" style="padding-left: 24px;">' . $MyRow['assetid'] . '</td>
				<td>' . $MyRow['description'] . ' <span style="font-size: 0.70rem; color: var(--text-muted); opacity: 0.7;">(' . $MyRow['serialno'] . ')</span></td>
				<td class="number">' . locale_number_format($nbv,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
				<td><span class="db-badge">' . $MyRow['ItemAssetLocation'] . '</span></td>';
		
		echo '<td>
				<input type="hidden" name="AssetCat" value="' . $_POST['AssetCat'].'" />
				<input type="hidden" name="AssetLocation" value="' . $_POST['AssetLocation'].'" />
				<input type="hidden" name="Keywords" value="' . $_POST['Keywords'].'" />
				<input type="hidden" name="AssetID" value="' . $_POST['AssetID'].'" />
				<input type="hidden" name="Search" value="' . $_POST['Search'].'" />';
		
		echo '	<select name="Location' . $MyRow['assetid'] . '" class="db-select db-select-sm" style="min-width: 140px;">';
		$ThisDropDownName	= 'Location' . $MyRow['assetid'];
		while ($LocationRow=DB_fetch_array($LocationResult)) {
			$selected = (isset($_POST[$ThisDropDownName]) && $_POST[$ThisDropDownName] == $LocationRow['locationid']) || $LocationRow['locationid'] == $MyRow['ItemAssetLocation'] ? 'selected="selected"' : '';
			echo '	<option ' . $selected . ' value="' . $LocationRow['locationid'].'">' . $LocationRow['locationdescription'] . '</option>';
		}
		DB_data_seek($LocationResult,0);
		echo '	</select>
			  </td>';
		
		echo '<td style="padding-right: 24px;">
				<button type="submit" name="Move'.$MyRow['assetid'].'" class="db-btn db-btn-secondary db-btn-sm" style="width: 100%; justify-content: center;">
					<i class="fas fa-exchange-alt"></i> ' . __('Move Asset') . '
				</button>
			  </td>';
		echo '</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>';
}

echo '		</div> 
	  </div> 
</form>'; // End centered container, db-page, and form

echo '<style>
.db-field-help { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; font-style: italic; }
.db-action-footer { border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border); position: sticky; bottom: 20px; z-index: 10; }

@media (max-width: 768px) {
	.db-input-group-mobile { flex-direction: column; align-items: stretch !important; gap: 10px; }
	.db-input-group-mobile .db-btn { width: 100%; justify-content: center; }
}

.monochromatic-table th { background: transparent !important; color: var(--text-main) !important; border-bottom: 2px solid var(--border) !important; }
.monochromatic-table tr:hover td { background: transparent !important; }
.monochromatic-table td { border-bottom: 1px solid var(--border-soft); }

@media (max-width: 768px) {
	.db-grid-mobile-stack { grid-template-columns: 1fr !important; }
	.db-grid-mobile-stack .db-form-group { grid-column: span 1 !important; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
