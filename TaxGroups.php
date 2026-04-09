<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Groups');
$ViewTopic = 'Tax';
$BookMark = 'TaxGroups';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Group multiple tax authorities for complex taxing rules') . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectOrderItems.php" class="db-btn db-btn-secondary">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg>
					' . __('Back to Orders') . '
				</a>
			</div>
		</header>';

if (isset($_GET['SelectedGroup'])) {
	$SelectedGroup = $_GET['SelectedGroup'];
} elseif (isset($_POST['SelectedGroup'])) {
	$SelectedGroup = $_POST['SelectedGroup'];
}

if (isset($_POST['submit']) OR isset($_GET['remove']) OR isset($_GET['add']) ) {
	$InputError = 0;
	if (isset($_POST['GroupName']) AND mb_strlen($_POST['GroupName'])<4) {
		$InputError = 1;
		prnMsg(__('The Group description entered must be at least 4 characters long'),'error');
	}
	unset($SQL);
	if (isset($_POST['GroupName']) ) {
		if (isset($SelectedGroup)) {
			$SQL = "UPDATE taxgroups SET taxgroupdescription = '". $_POST['GroupName'] ."' WHERE taxgroupid = '".$SelectedGroup . "'";
			$SuccessMsg = __('The tax group description was updated to') . ' ' . $_POST['GroupName'];
		} else {
			$Result = DB_query("SELECT taxgroupid FROM taxgroups WHERE taxgroupdescription='" . $_POST['GroupName'] . "'");
			if (DB_num_rows($Result)==1) {
				prnMsg( __('A new tax group could not be added because a tax group already exists for') . ' ' . $_POST['GroupName'],'warn');
			} else {
				$SQL = "INSERT INTO taxgroups (taxgroupdescription) VALUES ('". $_POST['GroupName'] . "')";
				$SuccessMsg = __('Added the new tax group') . ' ' . $_POST['GroupName'];
			}
		}
		unset($_POST['GroupName']);
		unset($SelectedGroup);
	} elseif (isset($SelectedGroup) ) {
		$TaxAuthority = $_GET['TaxAuthority'];
		if ( isset($_GET['add']) ) {
			$SQL = "INSERT INTO taxgrouptaxes ( taxgroupid, taxauthid, calculationorder) VALUES ('" . $SelectedGroup . "', '" . $TaxAuthority . "', 0)";
			$SuccessMsg = __('The tax was added.');
		} elseif ( isset($_GET['remove']) ) {
			$SQL = "DELETE FROM taxgrouptaxes WHERE taxgroupid = '".$SelectedGroup."' AND taxauthid = '".$TaxAuthority . "'";
			$SuccessMsg = __('This tax was removed.');
		}
		unset($_GET['add']);
		unset($_GET['remove']);
		unset($_GET['TaxAuthority']);
	}
	if (isset($SQL) AND $InputError != 1 ) {
		DB_query($SQL);
		prnMsg( $SuccessMsg,'success');
	}
} elseif (isset($_POST['UpdateOrder'])) {
	$SQL = "SELECT taxauthid FROM taxgrouptaxes WHERE taxgroupid='" . $SelectedGroup . "'";
	$Result = DB_query($SQL);
	while ($MyRow=DB_fetch_row($Result)) {
		if (is_numeric($_POST['CalcOrder_' . $MyRow[0]]) AND $_POST['CalcOrder_' . $MyRow[0]] < 10) {
			$SQL = "UPDATE taxgrouptaxes SET calculationorder='" . $_POST['CalcOrder_' . $MyRow[0]] . "', taxontax='" . $_POST['TaxOnTax_' . $MyRow[0]] . "' WHERE taxgroupid='" . $SelectedGroup . "' AND taxauthid='" . $MyRow[0] . "'";
			DB_query($SQL);
		}
	}
	$SQL = "SELECT taxauthid, taxontax FROM taxgrouptaxes WHERE taxgroupid='" . $SelectedGroup . "' ORDER BY calculationorder";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)>0) {
		$MyRow=DB_fetch_array($Result);
		if ($MyRow['taxontax']==1) {
			prnMsg(__('It is inappropriate to set tax on tax where the tax is the first in the calculation order. The system has changed it back to no tax on tax for this tax authority'),'warning');
			DB_query("UPDATE taxgrouptaxes SET taxontax=0 WHERE taxgroupid='" . $SelectedGroup . "' AND taxauthid='" . $MyRow['taxauthid'] . "'");
		}
	}
} elseif (isset($_GET['Delete'])) {
	$SQL= "SELECT COUNT(*) FROM custbranch WHERE taxgroupid='" . $_GET['SelectedGroup'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg( __('Cannot delete this tax group because some customer branches are setup using it'),'warn');
	} else {
		$SQL= "SELECT COUNT(*) FROM suppliers WHERE taxgroupid='" . $_GET['SelectedGroup'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg( __('Cannot delete this tax group because some suppliers are setup using it'),'warn');
		} else {
			DB_query("DELETE FROM taxgrouptaxes WHERE taxgroupid='" . $_GET['SelectedGroup'] . "'");
			DB_query("DELETE FROM taxgroups WHERE taxgroupid='" . $_GET['SelectedGroup'] . "'");
			prnMsg( $_GET['GroupID'] . ' ' . __('tax group has been deleted') . '!','success');
		}
	}
	unset($SelectedGroup);
}

if (!isset($SelectedGroup)) {
	$SQL = "SELECT taxgroupid, taxgroupdescription FROM taxgroups";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
				<div class="card-header-v2">
					<h3>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:middle; margin-right:8px; color:var(--primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
						' . __('Defined Tax Groups') . '
					</h3>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table divider">
							<thead>
								<tr>
									<th>' . __('ID') . '</th>
									<th>' . __('Group Description') . '</th>
									<th class="text-center">' . __('Actions') . '</th>
								</tr>
							</thead>
							<tbody>';
		while($MyRow = DB_fetch_array($Result)) {
			echo '<tr>
					<td class="font-bold">' . $MyRow['taxgroupid'] . '</td>
					<td>' . $MyRow['taxgroupdescription'] . '</td>
					<td class="text-center">
						<div class="db-action-group" style="justify-content:center;">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroup=' . $MyRow['taxgroupid'] . '" class="db-btn db-btn-icon db-btn-ghost" title="' . __('Edit') . '">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
							</a>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroup=' . $MyRow['taxgroupid'] . '&amp;Delete=1&amp;GroupID=' . urlencode($MyRow['taxgroupdescription']) . '" class="db-btn db-btn-icon db-btn-ghost text-danger" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this tax group?') . '\');">
								<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
							</a>
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
}

if (isset($SelectedGroup)) {
	echo '<div class="centre" style="margin-bottom: var(--space-6);">
			<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-secondary">
				<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right:8px;"><path d="M4 19h16M4 14h16M4 9h16M4 4h16"></path></svg>
				' . __('Review Existing Groups') . '
			</a>
		</div>';

	$SQL = "SELECT taxgroupid, taxgroupdescription FROM taxgroups WHERE taxgroupid='" . $SelectedGroup . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		$MyRow = DB_fetch_array($Result);
		$_POST['SelectedGroup'] = $MyRow['taxgroupid'];
		$_POST['GroupName'] = $MyRow['taxgroupdescription'];
	}
}

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
echo '<div class="card-v2" style="margin-bottom: var(--space-6);">';
echo '<div class="card-header-v2"><h3>' . (isset($_POST['SelectedGroup']) ? __('Edit Tax Group Name') : __('Create New Tax Group')) . '</h3></div>';
echo '<div class="db-card-body">';
if (isset($_POST['SelectedGroup'])) {
	echo '<input type="hidden" name="SelectedGroup" value="' . $_POST['SelectedGroup'] . '" />';
}
if (!isset($_POST['GroupName'])) $_POST['GroupName'] = '';
echo '<div class="db-field">
		<label class="db-label">' . __('Tax Group Name') . '</label>
		<input type="text" name="GroupName" class="db-input" required maxlength="40" value="' . $_POST['GroupName'] . '" placeholder="' . __('e.g. Standard VAT Group') . '" />
	</div>';
echo '</div>';
echo '<div class="db-card-actions">
		<button type="submit" name="submit" class="db-btn db-btn-primary">' . __('Save Group Name') . '</button>
	</div>';
echo '</div></form>';

if (isset($SelectedGroup)) {
	$SQLUsed = "SELECT taxauthid, description AS taxname, calculationorder, taxontax FROM taxgrouptaxes INNER JOIN taxauthorities ON taxgrouptaxes.taxauthid=taxauthorities.taxid WHERE taxgroupid='". $SelectedGroup . "' ORDER BY calculationorder";
	$UsedResult = DB_query($SQLUsed);
	$TaxAuthsUsed = array();
	$TaxAuthRow = array();
	$i=1;
	while($MyRow=DB_fetch_array($UsedResult)) {
		$TaxAuthsUsed[$i] = $MyRow['taxauthid'];
		$TaxAuthRow[$i] = $MyRow;
		$i++;
	}

	if (count($TaxAuthsUsed) > 0) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" name="SelectedGroup" value="' . $SelectedGroup .'" />';
		echo '<div class="card-v2" style="margin-bottom: var(--space-6);">
				<div class="card-header-v2"><h3>' . __('Calculation Order & Tax on Tax') . '</h3></div>
				<div class="db-card-body">
					<div class="db-table-wrapper">
						<table class="db-table divider">
							<thead>
								<tr>
									<th>' . __('Tax Authority') . '</th>
									<th style="width:100px;">' . __('Order') . '</th>
									<th>' . __('Tax on Prior Taxes') . '</th>
								</tr>
							</thead>
							<tbody>';
		for ($i=1; $i <= count($TaxAuthRow); $i++) {
			echo '<tr>
					<td>' . $TaxAuthRow[$i]['taxname'] . '</td>
					<td><input type="number" class="db-input" name="CalcOrder_' . $TaxAuthRow[$i]['taxauthid'] . '" value="' . ($TaxAuthRow[$i]['calculationorder'] == 0 ? $i : $TaxAuthRow[$i]['calculationorder']) . '" min="1" max="9" /></td>
					<td>
						<select name="TaxOnTax_' . $TaxAuthRow[$i]['taxauthid'] . '" class="db-input">
							<option value="1" ' . ($TaxAuthRow[$i]['taxontax'] == 1 ? 'selected' : '') . '>' . __('Yes') . '</option>
							<option value="0" ' . ($TaxAuthRow[$i]['taxontax'] == 0 ? 'selected' : '') . '>' . __('No') . '</option>
						</select>
					</td>
				</tr>';
		}
		echo '				</tbody>
						</table>
					</div>
				</div>
				<div class="db-card-actions">
					<button type="submit" name="UpdateOrder" class="db-btn db-btn-primary">' . __('Update Order Settings') . '</button>
				</div>
			</div></form>';
	}

	$SQLAll = "SELECT taxid, description as taxname FROM taxauthorities ORDER BY taxid";
	$AllResult = DB_query($SQLAll);

	echo '<div class="card-v2">
			<div class="card-header-v2"><h3>' . __('Authority Allocation') . '</h3></div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table divider">
						<thead>
							<tr>
								<th>' . __('Status') . '</th>
								<th>' . __('ID') . '</th>
								<th>' . __('Tax Authority Name') . '</th>
								<th class="text-center">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
	while($AvailRow = DB_fetch_array($AllResult)) {
		$isUsed = array_search($AvailRow['taxid'], $TaxAuthsUsed);
		echo '<tr>
				<td>' . ($isUsed ? '<span class="db-badge db-badge-success">' . __('Assigned') . '</span>' : '<span class="db-badge db-badge-ghost">' . __('Available') . '</span>') . '</td>
				<td class="font-bold">' . $AvailRow['taxid'] . '</td>
				<td>' . $AvailRow['taxname'] . '</td>
				<td class="text-center">';
		if ($isUsed) {
			echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroup=' . $SelectedGroup . '&amp;remove=1&amp;TaxAuthority=' . $AvailRow['taxid'] . '" class="db-btn db-btn-sm db-btn-secondary text-danger" onclick="return confirm(\'' . __('Remove this authority from the group?') . '\');">' . __('Remove') . '</a>';
		} else {
			echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedGroup=' . $SelectedGroup . '&amp;add=1&amp;TaxAuthority=' . $AvailRow['taxid'] . '" class="db-btn db-btn-sm db-btn-primary">' . __('Add to Group') . '</a>';
		}
		echo '	</td>
			</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';
}

echo '<div class="db-action-grid" style="margin-top:2rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
		<a href="' . $RootPath . '/TaxAuthorities.php" class="db-btn db-btn-ghost">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
			' . __('Tax Authority Maintenance') . '
		</a>
		<a href="' . $RootPath . '/TaxProvinces.php" class="db-btn db-btn-ghost">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
			' . __('Tax Province Maintenance') . '
		</a>
		<a href="' . $RootPath . '/TaxCategories.php" class="db-btn db-btn-ghost">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
			' . __('Tax Category Maintenance') . '
		</a>
	</div>';

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
