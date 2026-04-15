<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Units Of Measure');
$ViewTopic = 'Setup';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if ( isset($_GET['SelectedMeasureID']) )
	$SelectedMeasureID = $_GET['SelectedMeasureID'];
elseif (isset($_POST['SelectedMeasureID']))
	$SelectedMeasureID = $_POST['SelectedMeasureID'];

if (isset($_POST['Submit'])) {

	$InputError = 0;

	if (ContainsIllegalCharacters($_POST['MeasureName'])) {
		$InputError = 1;
		prnMsg( __('The unit of measure cannot contain any of the illegal characters') . ' ' . '" \' - &amp; or a space' ,'error');
	}
	if (trim($_POST['MeasureName']) == '') {
		$InputError = 1;
		prnMsg( __('The unit of measure may not be empty'), 'error');
	}

	if (isset($_POST['SelectedMeasureID']) AND $_POST['SelectedMeasureID']!='' AND $InputError !=1) {

		$SQL = "SELECT count(*) FROM unitsofmeasure
				WHERE unitid <> '" . $SelectedMeasureID ."'
				AND unitname ".LIKE." '" . $_POST['MeasureName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The unit of measure can not be renamed because another with the same name already exist.'),'error');
		} else {
			$SQL = "SELECT unitname FROM unitsofmeasure
				WHERE unitid = '" . $SelectedMeasureID . "'";
			$Result = DB_query($SQL);
			if ( DB_num_rows($Result) != 0 ) {
				$MyRow = DB_fetch_row($Result);
				$OldMeasureName = $MyRow[0];
				$SQL = array();
				$SQL[] = "UPDATE unitsofmeasure
					SET unitname='" . $_POST['MeasureName'] . "'
					WHERE unitname ".LIKE." '".$OldMeasureName."'";
				$SQL[] = "UPDATE stockmaster
					SET units='" . $_POST['MeasureName'] . "'
					WHERE units ".LIKE." '" . $OldMeasureName . "'";
			} else {
				$InputError = 1;
				prnMsg( __('The unit of measure no longer exist.'),'error');
			}
		}
		$Msg = __('Unit of measure changed');
	} elseif ($InputError !=1) {
		$SQL = "SELECT count(*) FROM unitsofmeasure
				WHERE unitname " .LIKE. " '".$_POST['MeasureName'] ."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('The unit of measure can not be created because another with the same name already exists.'),'error');
		} else {
			$SQL = "INSERT INTO unitsofmeasure (unitname )
					VALUES ('" . $_POST['MeasureName'] ."')";
		}
		$Msg = __('New unit of measure added');
	}

	if ($InputError!=1){
		if (is_array($SQL)) {
			DB_Txn_Begin();
			$TmpErr = __('Could not update unit of measure');
			$tmpDbg = __('The sql that failed was') . ':';
			foreach ($SQL as $stmt ) {
				$Result = DB_query($stmt, $TmpErr,$tmpDbg,true);
				if (!$Result) {
					$InputError = 1;
					break;
				}
			}
			if ($InputError!=1){
				DB_Txn_Commit();
			} else {
				DB_Txn_Rollback();
			}
		} else {
			$Result = DB_query($SQL);
		}
		prnMsg($Msg,'success');
	}
	unset ($SelectedMeasureID);
	unset ($_POST['SelectedMeasureID']);
	unset ($_POST['MeasureName']);

} elseif (isset($_GET['delete'])) {
	$SQL = "SELECT unitname FROM unitsofmeasure
		WHERE unitid = '" . $SelectedMeasureID . "'";
	$Result = DB_query($SQL);
	if ( DB_num_rows($Result) == 0 ) {
		prnMsg( __('Cannot delete this unit of measure because it no longer exist'),'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldMeasureName = $MyRow[0];
		$SQL= "SELECT COUNT(*) FROM stockmaster WHERE units ".LIKE." '" . $OldMeasureName . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg( __('Cannot delete this unit of measure because inventory items have been created using this unit of measure'),'warn');
		} else {
			$SQL="DELETE FROM unitsofmeasure WHERE unitname ".LIKE."'" . $OldMeasureName . "'";
			$Result = DB_query($SQL);
			prnMsg( $OldMeasureName . ' ' . __('unit of measure has been deleted') . '!','success');
		}
	}
	unset ($SelectedMeasureID);
}

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . (isset($SelectedMeasureID) ? __('Edit Unit') : __('Add New Unit')) . '</h3></div>
		<div class="db-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($SelectedMeasureID)) {
	$SQL = "SELECT unitid, unitname FROM unitsofmeasure WHERE unitid='" . $SelectedMeasureID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$_POST['MeasureName'] = $MyRow['unitname'];
	echo '<input type="hidden" name="SelectedMeasureID" value="' . $SelectedMeasureID . '" />';
}
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Unit Name') . '</label>
					<input type="text" name="MeasureName" class="db-input" required maxlength="30" value="' . ($_POST['MeasureName'] ?? '') . '" placeholder="' . __('e.g. Piece') . '" />
				</div>
				<div style="margin-top: 15px;">
					<button type="submit" name="Submit" class="db-btn db-btn-primary" style="width: 100%;"><i class="fas fa-save"></i> ' . __('Save Unit') . '</button>';
if (isset($SelectedMeasureID)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-input-light" style="width: 100%; margin-top: 10px; text-align: center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
}
echo '				</div>
			</form>
		</div>
	  </div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
$SQL = "SELECT unitid, unitname FROM unitsofmeasure ORDER BY unitid";
$Result = DB_query($SQL);

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-balance-scale"></i> ' . __('Units Portfolio') . '</h3></div>
		<div class="db-card-body p-0">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('ID') . '</th>
							<th>' . __('Unit Name') . '</th>
							<th class="text-right">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

while ($MyRow = DB_fetch_array($Result)) {
	$isSel = (isset($SelectedMeasureID) && $SelectedMeasureID == $MyRow['unitid']);
	echo '<tr ' . ($isSel ? 'style="background: var(--bg-soft);"' : '') . '>
			<td><span class="db-badge db-badge-secondary">' . $MyRow['unitid'] . '</span></td>
			<td><div class="db-font-bold ' . ($isSel ? 'text-primary' : '') . '">' . $MyRow['unitname'] . '</div></td>
			<td class="text-right">
				<div style="display: flex; gap: 8px; justify-content: flex-end;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedMeasureID=' . $MyRow['unitid'] . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i></a>
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedMeasureID=' . $MyRow['unitid'] . '&amp;delete=1" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Are you sure?') . '\');"><i class="fas fa-trash"></i></a>
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
