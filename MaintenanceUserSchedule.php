<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Fixed Assets Maintenance Schedule');
$ViewTopic = 'FixedAssets';
$BookMark = 'AssetMaintenance';
include(__DIR__ . '/includes/header.php');

// Page Header
echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-calendar-check"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Manage your upcoming asset maintenance tasks') . '</div>
			</div>
		</div>';

if (isset($_GET['Complete'])) {
	$Result = DB_query("UPDATE fixedassettasks
						SET lastcompleted = CURRENT_DATE
						WHERE taskid='" . $_GET['TaskID'] . "'");
	prnMsg(__('Maintenance task marked as completed'), 'success');
}

$SQL="SELECT taskid,
				fixedassettasks.assetid,
				description,
				taskdescription,
				frequencydays,
				lastcompleted,
				ADDDATE(lastcompleted,frequencydays) AS duedate,
				userresponsible,
				realname,
				manager
		FROM fixedassettasks
		INNER JOIN fixedassets
		ON fixedassettasks.assetid=fixedassets.assetid
		INNER JOIN www_users
		ON fixedassettasks.userresponsible=www_users.userid
		WHERE userresponsible='" . $_SESSION['UserID'] . "'
		OR manager = '" . $_SESSION['UserID'] . "'
		ORDER BY ADDDATE(lastcompleted,frequencydays) DESC";

$ErrMsg = __('The maintenance schedule cannot be retrieved because');
$Result = DB_query($SQL, $ErrMsg);

echo '<div class="db-centered-container" style="max-width: 1100px; margin: 0 auto; padding: 0 20px;">
		<div class="db-card" style="border: none; box-shadow: var(--shadow-md);">
			<div class="db-table-wrap" style="overflow-x: auto;">
				<table class="db-table monochromatic-table">
					<thead>
						<tr>
							<th style="padding-left: 24px;">' . __('Task ID') . '</th>
							<th>' . __('Asset') . '</th>
							<th>' . __('Task Description') . '</th>
							<th>' . __('Last Completed') . '</th>
							<th>' . __('Due By') . '</th>
							<th>' . __('Responsible') . '</th>
							<th>' . __('Manager') . '</th>
							<th style="padding-right: 24px;">' . __('Action') . '</th>
						</tr>
					</thead>
					<tbody>';

while ($MyRow=DB_fetch_array($Result)) {

	if ($MyRow['manager']!=''){
		$ManagerResult = DB_query("SELECT realname FROM www_users WHERE userid='" . $MyRow['manager'] . "'");
		$ManagerRow = DB_fetch_array($ManagerResult);
		$ManagerName = $ManagerRow['realname'];
	} else {
		$ManagerName = '<span style="color: var(--text-muted); opacity: 0.6;">' . __('Not Set') . '</span>';
	}
	
	$DueDate = ConvertSQLDate($MyRow['duedate']);
	$IsOverdue = Date1GreaterThanDate2(date($_SESSION['DefaultDateFormat']), $DueDate);
	$DueDateDisplay = $IsOverdue ? '<span class="db-badge db-badge-danger" style="font-weight: 700;">' . $DueDate . ' (' . __('OVERDUE') . ')</span>' : $DueDate;

	echo '<tr>
			<td class="db-font-bold" style="padding-left: 24px;">' . $MyRow['taskid'] . '</td>
			<td>
				<div class="db-font-bold">' . $MyRow['description'] . '</div>
				<div style="font-size: 0.75rem; color: var(--text-muted);">' . __('Asset ID') . ': ' . $MyRow['assetid'] . '</div>
			</td>
			<td>' . $MyRow['taskdescription'] . '</td>
			<td>' . ConvertSQLDate($MyRow['lastcompleted']) . '</td>
			<td>' . $DueDateDisplay . '</td>
			<td>' . $MyRow['realname'] . '</td>
			<td>' . $ManagerName . '</td>
			<td style="padding-right: 24px;">
				<a href="'.$RootPath.'/MaintenanceUserSchedule.php?Complete=Yes&amp;TaskID=' . $MyRow['taskid'] .'" 
				   class="db-btn db-btn-secondary db-btn-sm" 
				   style="justify-content: center; width: 100%; white-space: nowrap;"
				   onclick="return confirm(\'' . __('Are you sure you wish to mark this maintenance task as completed?') . '\');">
					<i class="fas fa-check"></i> ' . __('Mark Done') . '
				</a>
			</td>
		</tr>';
}

echo '					</tbody>
				</table>
			</div>
		  </div> 
	  </div>';

echo '</div>'; // End db-page

echo '<style>
.monochromatic-table th { background: transparent !important; color: var(--text-main) !important; border-bottom: 2px solid var(--border) !important; }
.monochromatic-table tr:hover td { background: transparent !important; }
.monochromatic-table td { border-bottom: 1px solid var(--border-soft); vertical-align: middle; }

@media (max-width: 768px) {
	.db-page-header { padding: 15px !important; }
	.db-page-title { font-size: 1.25rem !important; }
	.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
	.db-table-wrap { overflow-x: auto !important; margin: 0 -20px; width: calc(100% + 40px); -webkit-overflow-scrolling: touch; }
	.monochromatic-table { min-width: 1000px !important; width: 1000px !important; }
	.db-card-body { padding: 20px !important; }
	.db-font-bold { word-break: break-word; }
	.db-btn { width: 100% !important; display: flex !important; justify-content: center !important; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
?>
