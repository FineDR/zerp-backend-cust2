<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Authorise Internal Stock Requests');
$ViewTopic = 'Inventory';
$BookMark = 'AuthoriseRequest';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">
		<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-check-circle"></i> ' . __('Process Authorisations') . '</h3>
				</div>
				<div class="db-card-body">
					<p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">
						' . __('Review pending requests and click Update to process all selected authorisations and line cancellations.') . '
					</p>
					<button type="submit" name="UpdateAll" class="db-btn db-btn-primary" style="width: 100%;">
						<i class="fas fa-save"></i> ' . __('Update Changes') . '
					</button>
				</div>
			</div>
		</aside>';

echo '<main class="db-col-main">';

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


if (isset($_POST['UpdateAll'])) {
	foreach ($_POST as $POSTVariableName => $POSTValue) {
		if (mb_substr($POSTVariableName,0,6)=='status') {
			$RequestNo=mb_substr($POSTVariableName,6);
			$SQL="UPDATE stockrequest
					SET authorised='1'
					WHERE dispatchid='" . $RequestNo . "'";
			$Result = DB_query($SQL);
		}
		if (strpos($POSTVariableName, 'cancel')!== false) {
			$CancelItems = explode('cancel', $POSTVariableName);
 			$SQL = "UPDATE stockrequestitems
 						SET completed=1
 						WHERE dispatchid='" . $CancelItems[0] . "'
 						AND dispatchitemsid='" . $CancelItems[1] . "'";
 			$Result = DB_query($SQL);
 			$Result = DB_query("SELECT stockid FROM stockrequestitems WHERE completed=0 AND dispatchid='" . $CancelItems[0] . "'");
 			if (DB_num_rows($Result) ==0){
				$Result = DB_query("UPDATE stockrequest
									SET authorised='1'
									WHERE dispatchid='" . $CancelItems[0] . "'");
			}

 		}
	}
}

/* Retrieve the requisition header information
 */
$SQL="SELECT stockrequest.dispatchid,
			locations.locationname,
			stockrequest.despatchdate,
			stockrequest.narrative,
			departments.description,
			www_users.realname,
			www_users.email
		FROM stockrequest INNER JOIN departments
			ON stockrequest.departmentid=departments.departmentid
		INNER JOIN locations
			ON stockrequest.loccode=locations.loccode
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1
		INNER JOIN www_users
			ON www_users.userid=departments.authoriser
		WHERE stockrequest.authorised=0
		AND stockrequest.closed=0
		AND www_users.userid='".$_SESSION['UserID']."'";
$Result = DB_query($SQL);

if (DB_num_rows($Result) == 0) {
	echo '<div class="db-status-bar db-status-info">
			<div class="db-status-icon"><i class="fas fa-info-circle"></i></div>
			<div class="db-status-text">' . __('There are no internal stock requests waiting for your authorisation at this time.') . '</div>
		  </div>';
}

while ($MyRow = DB_fetch_array($Result)) {
	echo '<div class="db-card" style="margin-bottom: 25px;">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<div style="display: flex; items-center; gap: 15px;">
					<span class="db-badge db-badge-primary">#' . $MyRow['dispatchid'] . '</span>
					<h3 class="db-card-title">' . $MyRow['description'] . '</h3>
				</div>
				<div style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; font-weight: 600; color: var(--primary);">
					<input type="checkbox" name="status' . $MyRow['dispatchid'] . '" id="auth_' . $MyRow['dispatchid'] . '" style="width: 18px; height: 18px; cursor: pointer;" />
					<label for="auth_' . $MyRow['dispatchid'] . '" style="cursor: pointer;">' . __('Authorise Request') . '</label>
				</div>
			</div>
			
			<div class="db-card-body">
				<div class="db-grid db-grid-3" style="margin-bottom: 20px; background: var(--db-bg-alt); padding: 15px; border-radius: var(--radius-sm);">
					<div>
						<label class="db-label" style="font-size: 0.75rem; margin-bottom: 2px;">' . __('Source Location') . '</label>
						<div class="db-font-bold text-main">' . $MyRow['locationname'] . '</div>
					</div>
					<div>
						<label class="db-label" style="font-size: 0.75rem; margin-bottom: 2px;">' . __('Requested Date') . '</label>
						<div class="db-font-bold text-main">' . ConvertSQLDate($MyRow['despatchdate']) . '</div>
					</div>
					<div>
						<label class="db-label" style="font-size: 0.75rem; margin-bottom: 2px;">' . __('Narrative') . '</label>
						<div style="font-size: 0.85rem; color: var(--text-muted);">' . ($MyRow['narrative'] ?: __('No context provided')) . '</div>
					</div>
				</div>';

	$LinesSQL = "SELECT stockrequestitems.dispatchitemsid,
						stockrequestitems.stockid,
						stockrequestitems.decimalplaces,
						stockrequestitems.uom,
						stockmaster.description,
						stockrequestitems.quantity
				FROM stockrequestitems
				INNER JOIN stockmaster ON stockmaster.stockid=stockrequestitems.stockid
				WHERE dispatchid='" . $MyRow['dispatchid'] . "' AND completed=0";
	$LineResult = DB_query($LinesSQL);

	echo '		<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Item Description') . '</th>
								<th class="text-right">' . __('Quantity Required') . '</th>
								<th>' . __('UOM') . '</th>
								<th class="text-center">' . __('Cancel Line') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($LineRow = DB_fetch_array($LineResult)) {
		echo '			<tr>
							<td>
								<div class="db-font-bold text-primary">' . $LineRow['stockid'] . '</div>
								<div style="font-size: 0.8rem; color: var(--text-muted);">' . $LineRow['description'] . '</div>
							</td>
							<td class="text-right db-font-bold">' . locale_number_format($LineRow['quantity'], $LineRow['decimalplaces']) . '</td>
							<td>' . $LineRow['uom'] . '</td>
							<td class="text-center">
								<input type="checkbox" name="' . $MyRow['dispatchid'] . 'cancel' . $LineRow['dispatchitemsid'] . '" style="width: 16px; height: 16px; cursor: pointer;" />
							</td>
						</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		  </div>';
}

echo '	</main>
	</div> <!-- end db-bottom-layout -->
</form>';


include(__DIR__ . '/includes/footer.php');
