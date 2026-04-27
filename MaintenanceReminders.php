<?php

// this script can be set to run from cron
$AllowAnyone = true;

require(__DIR__ . '/includes/session.php');
$Title = __('Send maintenance reminders');

$SQL = "SELECT description,
			taskdescription,
			ADDDATE(lastcompleted,frequencydays) AS duedate,
			userresponsible,
			email
		FROM fixedassettasks
		INNER JOIN fixedassets
			ON fixedassettasks.assetid = fixedassets.assetid
		INNER JOIN www_users
			ON fixedassettasks.userresponsible = www_users.userid
		WHERE ADDDATE(lastcompleted,frequencydays-10) > CURDATE()
		ORDER BY userresponsible";

$Result = DB_query($SQL);
$LastUserResponsible = '';
$MailText = __('You have the following maintenance task(s) falling due or over-due:') . "\n";

while ($MyRow = DB_fetch_array($Result)) {
	if ($LastUserResponsible != '' && $LastUserResponsible != $MyRow['userresponsible'] && IsEmailAddress($LastUserEmail)) {
		// Send email to the previous user before moving to the next one
		$SendResult = SendEmailFromWebERP($SysAdminEmail, $LastUserEmail, 'Maintenance Tasks Reminder', $MailText);
		// Reset mail text for new recipient
		$MailText = __('You have the following maintenance task(s) falling due or over-due:') . "\n";
	}

	if ($LastUserResponsible != $MyRow['userresponsible']) {
		$LastUserResponsible = $MyRow['userresponsible'];
		$LastUserEmail = $MyRow['email'];
	}

	$MailText .= 'Asset' . ': ' . $MyRow['description'] . "\nTask: " . $MyRow['taskdescription'] . "\nDue: "
		. ConvertSQLDate($MyRow['duedate']);
	if (Date1GreaterThanDate2(ConvertSQLDate($MyRow['duedate']), date($_SESSION['DefaultDateFormat']))) {
		$MailText .= __('NB: THIS JOB IS OVERDUE');
	}
	$MailText .= "\n\n";
}

// Send email to the last user if there were results
if (DB_num_rows($Result) > 0 && IsEmailAddress($LastUserEmail)) {
	$SendResult = SendEmailFromWebERP($SysAdminEmail, $LastUserEmail, 'Maintenance Tasks Reminder', $MailText);
}

/* Now do manager emails for overdue jobs */
$SQL = "SELECT description,
			taskdescription,
			ADDDATE(lastcompleted,frequencydays) AS duedate,
			realname,
			manager,
			email
		FROM fixedassettasks
		INNER JOIN fixedassets
			ON fixedassettasks.assetid = fixedassets.assetid
		INNER JOIN www_users
			ON fixedassettasks.userresponsible = www_users.userid
		WHERE ADDDATE(lastcompleted,frequencydays) > CURDATE()
		ORDER BY manager";

$Result = DB_query($SQL);
$LastManager = '';
$ManagerMailText = "Your staff have failed to complete the following tasks by the due date:\n";

while ($MyRow = DB_fetch_array($Result)) {
	if ($LastManager != '' && $LastManager != $MyRow['manager'] && IsEmailAddress($LastManagerEmail)) {
		// Send email to the previous manager before moving to the next one
		$SendResult = SendEmailFromWebERP($SysAdminEmail, $LastManagerEmail, 'Overdue Maintenance Tasks Reminder', $ManagerMailText);
		// Reset mail text for new recipient
		$ManagerMailText = "Your staff have failed to complete the following tasks by the due date:\n";
	}

	if ($LastManager != $MyRow['manager']) {
		$LastManager = $MyRow['manager'];
		$LastManagerEmail = $MyRow['email'];
	}

	$ManagerMailText .= __('Asset') . ': ' . $MyRow['description'] . "\n" . __('Task:') . ' ' . $MyRow['taskdescription'] . "\n"
		. __('Due:') . ' ' . ConvertSQLDate($MyRow['duedate']);
	$ManagerMailText .= "\n\n";
}

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-header-left">
				<div class="db-page-title">
					<i class="fas fa-bell"></i> ' . $Title . '
				</div>
				<div class="db-page-subtitle">' . __('Background maintenance notification processing') . '</div>
			</div>
		</div>
		
		<div class="db-centered-container" style="max-width: 600px; margin: 0 auto; padding: 0 20px;">
			<div class="db-card" style="border: none; box-shadow: var(--shadow-md);">
				<div class="db-card-body db-card-mobile-padding" style="padding: 40px; text-align: center;">';

if (DB_num_rows($Result) > 0) {
	if (IsEmailAddress($LastManagerEmail)) {
		$SendResult = SendEmailFromWebERP($SysAdminEmail, $LastManagerEmail, 'Overdue Maintenance Tasks Reminder', $ManagerMailText);
		echo '<div class="db-indicator db-indicator-success" style="margin: 0 auto 20px;">
				<i class="fas fa-check"></i>
			  </div>
			  <h2 class="db-card-title" style="font-size: 1.5rem; margin-bottom: 10px;">' . __('Reminders Processed') . '</h2>
			  <p style="color: var(--text-muted);">' . __('Notifications have been successfully sent to relevant users and managers.') . '</p>
			  <div class="db-badge db-badge-success" style="margin-top: 15px; font-size: 0.9rem; word-break: break-all;">' . __('Last sent to') . ': ' . $LastManagerEmail . '</div>';
	} else {
		echo '<div class="db-indicator db-indicator-info" style="margin: 0 auto 20px;">
				<i class="fas fa-info-circle"></i>
			  </div>
			  <h2 class="db-card-title" style="font-size: 1.5rem; margin-bottom: 10px;">' . __('Processing Complete') . '</h2>
			  <p style="color: var(--text-muted);">' . __('No emails were sent as no valid addresses were found for flagged tasks.') . '</p>';
	}
} else {
	echo '<div class="db-indicator db-indicator-info" style="margin: 0 auto 20px;">
			<i class="fas fa-calendar-alt"></i>
		  </div>
		  <h2 class="db-card-title" style="font-size: 1.5rem; margin-bottom: 10px;">' . __('Up to Date') . '</h2>
		  <p style="color: var(--text-muted);">' . __('There are no pending maintenance reminders to be sent at this time.') . '</p>';
}

echo '				<div style="margin-top: 30px;">
						<a href="' . $RootPath . '/index.php" class="db-btn db-btn-primary db-mobile-full" style="justify-content: center; width: 100%;">
							<i class="fas fa-home"></i> ' . __('Return Home') . '
						</a>
					</div>
				</div>
			</div>
		</div>
	  </div>';

echo '<style>
@media (max-width: 768px) {
	.db-page-header { padding: 15px !important; }
	.db-page-title { font-size: 1.25rem !important; }
	.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
	.db-card-mobile-padding { padding: 25px !important; }
	.db-mobile-full { width: 100% !important; display: flex !important; justify-content: center !important; }
}
</style>';

include(__DIR__ . '/includes/footer.php');
?>
