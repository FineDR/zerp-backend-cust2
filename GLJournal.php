<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineJournalClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Journal Entry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLJournals';

// Architect Workspace UI: Core assets & high-fidelity buttons
$ExtraHeadContent = '
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
	:root {
		--db-primary: #059669; /* Specific user requirement */
		--db-secondary: #6b7280;
		--db-danger: #ef4444;
		--db-surface-alt: #f9fafb;
		--db-border: #e5e7eb;
		--db-text-main: #111827;
		--db-text-muted: #6b7280;
		--db-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
		--db-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
	}
	html, body { width: 100% !important; max-width: 100vw !important; overflow-x: hidden !important; }
	#body_wrap_wrapper, .canvas { width: 100% !important; min-width: 0 !important; box-sizing: border-box !important; overflow: hidden !important; }
	
	body { font-family: "Inter", sans-serif !important; background-color: var(--db-surface-alt) !important; color: var(--db-text-main) !important; margin: 0; padding: 0; }
	
	.db-page { width: 100% !important; max-width: 100vw !important; overflow-x: hidden !important; box-sizing: border-box !important; padding: 20px; }
	.db-centered-container { width: 100% !important; max-width: 1200px !important; margin: 0 auto; box-sizing: border-box !important; }
	
	/* Page Header */
	.db-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
	.db-header-left { display: flex; flex-direction: column; }
	.db-page-title { font-size: 1.5rem; font-weight: 700; color: var(--db-text-main); display: flex; align-items: center; gap: 10px; margin: 0; }
	.db-page-subtitle { font-size: 0.875rem; color: var(--db-text-muted); margin-top: 4px; }
	
	/* Layout & Cards */
	.db-main-layout { display: grid; gap: 24px; box-sizing: border-box !important; min-width: 0 !important; }
	.db-card { background: #ffffff; border-radius: 12px; box-shadow: var(--db-shadow-sm); border: 1px solid var(--db-border); overflow: hidden; width: 100% !important; box-sizing: border-box !important; margin-bottom: 24px; min-width: 0 !important; }
	.db-card-header { padding: 16px 20px; border-bottom: 1px solid var(--db-border); display: flex; justify-content: space-between; align-items: center; background: #ffffff; }
	.db-card-title { font-size: 1.1rem; font-weight: 600; color: var(--db-text-main); margin: 0; }
	.db-card-body { padding: 20px; }
	
	/* Forms */
	.db-form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px; }
	.db-form-group { display: flex; flex-direction: column; gap: 6px; }
	.db-label { font-size: 0.875rem; font-weight: 500; color: #374151; }
	.db-input, .db-select, input[type="text"], input[type="date"], select { 
		width: 100% !important; min-width: 0 !important; box-sizing: border-box !important; 
		padding: 10px 14px !important; border: 1px solid #d1d5db !important; border-radius: 6px !important; font-size: 0.9rem !important; transition: border-color 0.15s ease !important; background: #fff !important; 
	}
	.db-input:focus, .db-select:focus, input[type="text"]:focus, select:focus { border-color: var(--db-primary) !important; outline: none !important; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1) !important; }
	
	/* Buttons */
	.db-btn { display: inline-flex !important; align-items: center !important; justify-content: center !important; gap: 8px !important; padding: 10px 18px !important; border-radius: 6px !important; font-weight: 600 !important; font-size: 0.875rem !important; transition: all 0.2s ease !important; border: none !important; cursor: pointer !important; text-decoration: none !important; }
	.db-btn-primary { background: var(--db-primary) !important; color: #ffffff !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; }
	.db-btn-primary:hover { background: #047857 !important; transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important; }
	.db-btn-secondary { background: #ffffff !important; color: #374151 !important; border: 1px solid #d1d5db !important; box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important; }
	.db-btn-secondary:hover { background: #f3f4f6 !important; color: #111827 !important; }
	.db-btn-danger { background: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #fecaca !important; }
	.db-btn-danger:hover { background: #fca5a5 !important; color: #991b1b !important; }
	
	/* Enhanced Tables */
	.db-table-wrap { width: 100% !important; overflow-x: auto !important; -webkit-overflow-scrolling: touch; box-sizing: border-box !important; border-radius: 8px; border: 1px solid var(--db-border); background: #fff; }
	.monochromatic-table { width: 100%; border-collapse: collapse; text-align: left; }
	.monochromatic-table th { background: #f9fafb !important; color: #4b5563 !important; padding: 12px 16px !important; font-weight: 600 !important; font-size: 0.85rem !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; border-bottom: 1px solid var(--db-border) !important; }
	.monochromatic-table td { padding: 14px 16px !important; border-bottom: 1px solid var(--db-border) !important; font-size: 0.9rem !important; color: #111827 !important; vertical-align: middle; }
	.monochromatic-table tr:last-child td { border-bottom: none !important; }
	.monochromatic-table tr:nth-child(even) { background-color: #f9fafb !important; }
	.monochromatic-table .number { text-align: right !important; }
	.db-table-actions { text-align: center !important; white-space: nowrap !important; }
	
	/* Responsiveness */
	@media (max-width: 1024px) {
		#header, #footer { display: none !important; } /* Caging legacy elements */
	}
	@media (max-width: 768px) {
		.db-page { padding: 12px; }
		.db-card-header { padding: 14px 16px; }
		.db-card-body { padding: 16px; }
		.db-btn { width: 100% !important; justify-content: center !important; }
		
		.monochromatic-table, .monochromatic-table thead, .monochromatic-table tbody, .monochromatic-table th, .monochromatic-table td, .monochromatic-table tr { display: block !important; width: 100% !important; }
		.monochromatic-table thead tr { display: none !important; }
		.monochromatic-table tr { border: 1px solid var(--db-border) !important; border-radius: 8px !important; margin-bottom: 12px !important; background: #fff !important; }
		.monochromatic-table td { border: none !important; display: flex !important; justify-content: space-between !important; padding: 10px 14px !important; text-align: right !important; border-bottom: 1px solid #f3f4f6 !important; }
		.monochromatic-table tr:nth-child(even) { background-color: #fff !important; }
		.monochromatic-table td::before { content: attr(data-label); font-weight: 600 !important; color: #6b7280 !important; text-align: left !important; flex: 1; padding-right: 12px; }
		.db-table-actions { border-top: 1px solid #f3f4f6 !important; margin-top: 4px !important; justify-content: center !important; }
		.db-table-actions::before { display: none !important; }
	}
</style>
';

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-centered-container">
			<div class="db-page-header">
				<div class="db-header-left">
					<h1 class="db-page-title"><i class="fas fa-book"></i> ' . $Title . '</h1>
					<div class="db-page-subtitle">' . __('Create and manage General Ledger journals') . '</div>
				</div>
			</div>
			<div class="db-main-layout">';


include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['JournalProcessDate'])){$_POST['JournalProcessDate'] = ConvertSQLDate($_POST['JournalProcessDate']);}

if (isset($_GET['NewJournal']) and $_GET['NewJournal'] == 'Yes' and isset($_SESSION['JournalDetail'])) {

	unset($_SESSION['JournalDetail']->GLEntries);
	unset($_SESSION['JournalDetail']);

}

if (!isset($_SESSION['JournalDetail'])) {
	$_SESSION['JournalDetail'] = new Journal;

	/* Make an array of the defined bank accounts - better to make it now than do it each time a line is added
	Journals cannot be entered against bank accounts GL postings involving bank accounts must be done using
	a receipt or a payment transaction to ensure a bank trans is available for matching off vs statements */

	$SQL = "SELECT accountcode FROM bankaccounts";
	$Result = DB_query($SQL);
	$i = 0;
	while ($Act = DB_fetch_row($Result)) {
		$_SESSION['JournalDetail']->BankAccounts[$i] = $Act[0];
		$i++;
	}

}

if (isset($_GET['TemplateID'])) {
	$SQL = "SELECT journaltype FROM jnltmplheader WHERE templateid='" . $_GET['TemplateID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['journaltype'] == 0) {
		$_SESSION['JournalDetail']->JournalType = 'Normal';
	}
	else {
		$_SESSION['JournalDetail']->JournalType = 'Reversing';
	}
	$SQL = "SELECT amount,
					narrative,
					accountcode,
					tags
				FROM jnltmpldetails
				WHERE templateid='" . $_GET['TemplateID'] . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$SQL = "SELECT accountname
			FROM chartmaster
			WHERE accountcode='" . $MyRow['accountcode'] . "'";
		$ChartResult = DB_query($SQL);
		$MyChartRow = DB_fetch_array($ChartResult);
		$_SESSION['JournalDetail']->Add_To_GLAnalysis($MyRow['amount'], $MyRow['narrative'], $MyRow['accountcode'], $MyChartRow['accountname'], $MyRow['tags']);
	}
}

if (isset($_POST['JournalProcessDate'])) {
	if (!Is_Date($_POST['JournalProcessDate'])) {
		prnMsg(__('The date entered was not valid please enter the date to process the journal in the format') . $_SESSION['DefaultDateFormat'], 'warn');
		$_POST['CommitBatch'] = 'Do not do it the date is wrong';
	} else {
		$_SESSION['JournalDetail']->JnlDate = $_POST['JournalProcessDate'];
	}
}

if (isset($_POST['JournalType'])) {
	$_SESSION['JournalDetail']->JournalType = $_POST['JournalType'];
}

if (isset($_POST['LoadTemplate'])) {

	$SQL = "SELECT templateid,
					templatedescription,
					journaltype
				FROM jnltmplheader ";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		prnMsg(__('There are no templates saved. You must first create a template.') , 'warn');
	}
	else {
		echo '<div class="db-card">
				<div class="db-card-header"><h3 class="db-card-title">', __('Load journal from a template') , '</h3></div>
				<div class="db-card-body">
					<div class="db-table-wrap">
						<table class="monochromatic-table">
							<thead>
								<tr>
									<th>', __('Template ID') , '</th>
									<th>', __('Template Description') , '</th>
									<th>', __('Journal Type') , '</th>
									<th style="text-align: center;">', __('Action') , '</th>
								</tr>
							</thead>
							<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {
			if ($MyRow['journaltype'] == 0) {
				$JournalType = __('Normal');
			}
			else {
				$JournalType = __('Reversing');
			}
			echo '<tr>
					<td data-label="', __('Template ID') , '">', $MyRow['templateid'], '</td>
					<td data-label="', __('Template Description') , '">', $MyRow['templatedescription'], '</td>
					<td data-label="', __('Journal Type') , '">', $JournalType, '</td>
					<td class="db-table-actions noPrint"><a class="db-btn db-btn-primary" href="', basename(__FILE__) , '?TemplateID=', urlencode($MyRow['templateid']) , '"><i class="fas fa-check"></i> ', __('Select') , '</a></td>
				</tr>';
		}

		echo '				</tbody>
						</table>
					</div>
				</div>
			</div>';
		
		echo '</div></div></div>'; // Close main layout and page containers
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}

if (isset($_POST['SaveTemplate'])) {
	if (!isset($_POST['Description']) or $_POST['Description'] == '') {
		$_POST['ConfimSave'] = 'ConfirmSave';
		prnMsg(__('You must enter a description of between 1 and 50 characters for this template.') , 'error');
	}
	else {
		// Check if duplicate description
		$SQL = "SELECT templateid AS templates FROM jnltmplheader WHERE templatedescription='" . $_POST['Description'] . "'";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) == 0) {
			//Save the header
			$TemplateNo = GetNextTransNo(4);
			if ($_SESSION['JournalDetail']->JournalType == 'Reversing') {
				$JournalType = 1;
			}
			else {
				$JournalType = 0;
			}
			$SQL = "INSERT INTO jnltmplheader (templateid,
												templatedescription,
												journaltype
											) VALUES (
												'" . $TemplateNo . "',
												'" . $_POST['Description'] . "',
												'" . $JournalType . "'
											)";
			$Result = DB_query($SQL);
			if (DB_error_no() !=  0) {
				prnMsg(__('The journal template header info could not be saved') , 'error');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
			$LineNumber = 0;
			foreach ($_SESSION['JournalDetail']->GLEntries as $JournalItem) {
				$SQL = "INSERT INTO jnltmpldetails (linenumber,
													templateid,
													tags,
													accountcode,
													amount,
													narrative
												) VALUES (
													'" . $LineNumber . "',
													'" . $TemplateNo . "',
													'" . $JournalItem->tag . "',
													'" . $JournalItem->GLCode . "',
													'" . $JournalItem->Amount . "',
													'" . $JournalItem->Narrative . "'
												)";
				$Result = DB_query($SQL);
				++$LineNumber;
				if (DB_error_no() !=  0) {
					prnMsg(__('The journal template line info could not be saved') , 'error');
					include(__DIR__ . '/includes/footer.php');
					exit();
				}
			}
			prnMsg(__('The template has been successfully saved') , 'success');
		}
		else {
			$_POST['ConfimSave'] = 'ConfirmSave';
			prnMsg(__('A template with this description already exists. You must use a unique description') , 'info');
		}
	}
}

if (isset($_POST['ConfimSave'])) {

	echo '<form action="', htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') , '" method="post" id="form">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<form action="', htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') , '" method="post" id="form">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title">', __('Save journal as a template') , '</h3></div>
			<div class="db-card-body">
				<div class="db-form-row">
					<div class="db-form-group">
						<label class="db-label" for="Description">', __('Template description') , '</label>
						<input type="text" class="db-input" size="50" name="Description" value="" maxlength="50" />
					</div>
				</div>
				<br>
				<h3 class="db-card-title" style="margin-bottom: 10px;">', __('Journal Summary') , '</h3>
				<div class="db-table-wrap">
					<table class="monochromatic-table">
						<thead>
							<tr>
								<th>', __('GL Tag') , '</th>
								<th>', __('GL Account') , '</th>
								<th class="number">', __('Debit') , '</th>
								<th class="number">', __('Credit') , '</th>
								<th>', __('Narrative') , '</th>
							</tr>
						</thead>
						<tbody>';

	foreach ($_SESSION['JournalDetail']->GLEntries as $JournalItem) {
		echo '<tr>
				<td data-label="', __('GL Tag') , '">';
		$Tag = $JournalItem->tag;
		$SQL = "SELECT tagdescription
					FROM tags
					WHERE tagref='" . $Tag . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($Tag == 0) {
			$TagDescription = __('None');
		}
		else {
			$TagDescription = $MyRow[0];
		}
		echo $Tag, ' - ', $TagDescription;
		echo '</td>';
		echo '<td data-label="', __('GL Account') , '">', $JournalItem->GLCode, ' - ', $JournalItem->GLActName, '</td>';
		if ($JournalItem->Amount > 0) {
			echo '<td data-label="', __('Debit') , '" class="number">', locale_number_format($JournalItem->Amount, $_SESSION['CompanyRecord']['decimalplaces']) , '</td>
					<td data-label="', __('Credit') , '" class="number"></td>';
		}
		elseif ($JournalItem->Amount < 0) {
			$Credit = (-1 * $JournalItem->Amount);
			echo '<td data-label="', __('Debit') , '" class="number"></td>
				<td data-label="', __('Credit') , '" class="number">', locale_number_format($Credit, $_SESSION['CompanyRecord']['decimalplaces']) , '</td>';
		}

		echo '<td data-label="', __('Narrative') , '">', $JournalItem->Narrative, '</td>
		</tr>';
	}
	echo '				</tbody>
					</table>
				</div>
				<div class="centre" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
					<button type="submit" name="SaveTemplate" class="db-btn db-btn-primary" value="', __('Save as template') , '"><i class="fas fa-save"></i> ', __('Save as template') , '</button>
					<button type="reset" name="Cancel" class="db-btn db-btn-secondary" value="', __('Cancel') , '"><i class="fas fa-times"></i> ', __('Cancel') , '</button>
				</div>
			</div>
		</div>';
	echo '</form>';

	echo '</div></div></div>'; // Close main layout and page containers
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['CommitBatch']) and $_POST['CommitBatch'] == __('Accept and Process Journal')) {

	/* once the GL analysis of the journal is entered
	 process all the data in the session cookie into the DB
	 A GL entry is created for each GL entry
	*/

	$PeriodNo = GetPeriod($_SESSION['JournalDetail']->JnlDate);

	/*Start a transaction to do the whole lot inside */
	DB_Txn_Begin();

	$TransNo = GetNextTransNo(0);

	foreach ($_SESSION['JournalDetail']->GLEntries as $JournalItem) {
		$SQL = "INSERT INTO gltrans (type,
									typeno,
									trandate,
									periodno,
									account,
									narrative,
									amount)
				VALUES ('0',
					'" . $TransNo . "',
					'" . FormatDateForSQL($_SESSION['JournalDetail']->JnlDate) . "',
					'" . $PeriodNo . "',
					'" . $JournalItem->GLCode . "',
					'" . mb_substr($JournalItem->Narrative, 0, 200) . "',
					'" . $JournalItem->Amount . "'
					)";
		$ErrMsg = __('Cannot insert a GL entry for the journal line because');
		$Result = DB_query($SQL, $ErrMsg, '', true);
		InsertGLTags($JournalItem->tag);

		if ($_POST['JournalType'] == 'Reversing') {
			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
					VALUES ('0',
						'" . $TransNo . "',
						'" . FormatDateForSQL($_SESSION['JournalDetail']->JnlDate) . "',
						'" . ($PeriodNo + 1) . "',
						'" . $JournalItem->GLCode . "',
						'" . mb_substr(__('Reversal') . " - " . $JournalItem->Narrative, 0, 200) . "',
						'" . -($JournalItem->Amount) . "'
						)";

			$ErrMsg = __('Cannot insert a GL entry for the reversing journal because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
			InsertGLTags($JournalItem->tag);
		}
	}

	$ErrMsg = __('Cannot commit the changes');
	DB_Txn_Commit();

	prnMsg(__('Journal') . ' ' . $TransNo . ' ' . __('has been successfully entered') , 'success');

	unset($_POST['JournalProcessDate']);
	unset($_POST['JournalType']);
	unset($_SESSION['JournalDetail']->GLEntries);
	unset($_SESSION['JournalDetail']);

	/*Set up a newy in case user wishes to enter another */
	echo '<br />
			<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?NewJournal=Yes">' . __('Enter Another General Ledger Journal') . '</a>';

	include(__DIR__ . '/includes/footer.php');
	exit();

} elseif (isset($_GET['Delete'])) {

	/* User hit delete the line from the journal */
	$_SESSION['JournalDetail']->Remove_GLEntry($_GET['Delete']);

} elseif (isset($_POST['Process']) and $_POST['Process'] == __('Accept')) { //user hit submit a new GL Analysis line into the journal
	if ($_POST['GLCode'] !=  '') {
		$Extract = explode(' - ', $_POST['GLCode']);
		$_POST['GLCode'] = $Extract[0];
	}
	if ($_POST['Debit'] > 0) {
		$_POST['GLAmount'] = filter_number_format($_POST['Debit']);
	}
	elseif ($_POST['Credit'] > 0) {
		$_POST['GLAmount'] = - filter_number_format($_POST['Credit']);
	}
	if ($_POST['GLManualCode'] !=  '') {
		// If a manual code was entered need to check it exists and isnt a bank account
		$AllowThisPosting = true; //by default
		if ($_SESSION['ProhibitJournalsToControlAccounts'] == 1) {
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == '1' and $_POST['GLManualCode'] == $_SESSION['CompanyRecord']['debtorsact']) {
				prnMsg(__('GL Journals involving the debtors control account cannot be entered. The general ledger debtors ledger (AR) integration is enabled so control accounts are automatically maintained by webERP. This setting can be disabled in System Configuration') , 'warn');
				$AllowThisPosting = false;
			}
			if ($_SESSION['CompanyRecord']['gllink_creditors'] == '1' and $_POST['GLManualCode'] == $_SESSION['CompanyRecord']['creditorsact']) {
				prnMsg(__('GL Journals involving the creditors control account cannot be entered. The general ledger creditors ledger (AP) integration is enabled so control accounts are automatically maintained by webERP. This setting can be disabled in System Configuration') , 'warn');
				$AllowThisPosting = false;
			}
		}
		if (in_array($_POST['GLManualCode'], $_SESSION['JournalDetail']->BankAccounts)) {
			prnMsg(__('GL Journals involving a bank account cannot be entered') . '. ' . __('Bank account general ledger entries must be entered by either a bank account receipt or a bank account payment') , 'info');
			$AllowThisPosting = false;
		}

		if ($AllowThisPosting) {
			$SQL = "SELECT accountname
				FROM chartmaster
				WHERE accountcode='" . $_POST['GLManualCode'] . "'";
			$Result = DB_query($SQL);

			if (DB_num_rows($Result) == 0) {
				prnMsg(__('The manual GL code entered does not exist in the database') . ' - ' . __('so this GL analysis item could not be added') , 'warn');
				unset($_POST['GLManualCode']);
			}
			else {
				$MyRow = DB_fetch_array($Result);
				$_SESSION['JournalDetail']->add_to_glanalysis($_POST['GLAmount'], $_POST['GLNarrative'], $_POST['GLManualCode'], $MyRow['accountname'], $_POST['tag']);
			}
		}
	}
	else {
		$AllowThisPosting = true; //by default
		if ($_SESSION['ProhibitJournalsToControlAccounts'] == 1) {
			if ($_SESSION['CompanyRecord']['gllink_debtors'] == '1' and $_POST['GLCode'] == $_SESSION['CompanyRecord']['debtorsact']) {

				prnMsg(__('GL Journals involving the debtors control account cannot be entered. The general ledger debtors ledger (AR) integration is enabled so control accounts are automatically maintained by webERP. This setting can be disabled in System Configuration') , 'warn');
				$AllowThisPosting = false;
			}
			if ($_SESSION['CompanyRecord']['gllink_creditors'] == '1' and $_POST['GLCode'] == $_SESSION['CompanyRecord']['creditorsact']) {

				prnMsg(__('GL Journals involving the creditors control account cannot be entered. The general ledger creditors ledger (AP) integration is enabled so control accounts are automatically maintained by webERP. This setting can be disabled in System Configuration') , 'warn');
				$AllowThisPosting = false;
			}
		}
		if ($_POST['GLCode'] == '' and $_POST['GLManualCode'] == '') {
			prnMsg(__('You must select a GL account code') , 'info');
			$AllowThisPosting = false;
		}

		if (in_array($_POST['GLCode'], $_SESSION['JournalDetail']->BankAccounts)) {
			prnMsg(__('GL Journals involving a bank account cannot be entered') . '. ' . __('Bank account general ledger entries must be entered by either a bank account receipt or a bank account payment') , 'warn');
			$AllowThisPosting = false;
		}

		if ($AllowThisPosting) {
			if (!isset($_POST['GLAmount'])) {
				$_POST['GLAmount'] = 0;
			}
			$SQL = "SELECT accountname FROM chartmaster WHERE accountcode='" . $_POST['GLCode'] . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$_SESSION['JournalDetail']->add_to_glanalysis($_POST['GLAmount'], $_POST['GLNarrative'], $_POST['GLCode'], $MyRow['accountname'], $_POST['tag']);
		}
	}

	/*Make sure the same receipt is not double processed by a page refresh */
	$Cancel = 1;
	unset($_POST['Credit']);
	unset($_POST['Debit']);
	unset($_POST['tag']);
	unset($_POST['GLManualCode']);
	unset($_POST['GLNarrative']);
}

if (isset($Cancel)) {
	unset($_POST['Credit']);
	unset($_POST['Debit']);
	unset($_POST['GLAmount']);
	unset($_POST['GLCode']);
	unset($_POST['tag']);
	unset($_POST['GLManualCode']);
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" name="form">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

// Legacy title removed as it is now shown in db-page-header

// A new table in the first column of the main table
if (!isset($_SESSION['JournalDetail']->JnlDate) or !Is_Date($_SESSION['JournalDetail']->JnlDate)) {
	// Default the date to the last day of the previous month
	$_SESSION['JournalDetail']->JnlDate = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') , 0, date('Y')));
}

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title">', __('Journal Header') , '</h3></div>
		<div class="db-card-body">
			<div class="db-form-row">
				<div class="db-form-group">
					<label class="db-label" for="JournalProcessDate">' . __('Date to Process Journal') . '</label>
					<input class="db-input" type="date" required="required" name="JournalProcessDate" maxlength="10" size="11" value="' . FormatDateForSQL($_SESSION['JournalDetail']->JnlDate) . '" />
				</div>
				<div class="db-form-group">
					<label class="db-label" for="JournalType">' . __('Type') . '</label>
					<select class="db-select" name="JournalType">';

if ($_POST['JournalType'] == 'Reversing') {
	echo '<option selected="selected" value = "Reversing">' . __('Reversing') . '</option>';
	echo '<option value = "Normal">' . __('Normal') . '</option>';
} else {
	echo '<option value = "Reversing">' . __('Reversing') . '</option>';
	echo '<option selected="selected" value = "Normal">' . __('Normal') . '</option>';
}

echo '</select>
				</div>
			</div>
		</div>
	</div>';
/* close off the table in the first column  */

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title">' . __('Journal Line Entry') . '</h3></div>
		<div class="db-card-body">
			<div class="db-form-row">';

/* Set upthe form for the transaction entry for a GL Payment Analysis item */

//Select the tag
$SQL = "SELECT tagref,
			tagdescription
	FROM tags
	ORDER BY tagref";
$Result = DB_query($SQL);
echo '<div class="db-form-group">
	<label class="db-label" for="tag">', __('GL Tag') , '</label>
	<select class="db-select" multiple="multiple" name="tag[]">';
while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_GET['Edit']) and isset($_POST['tag']) and $_POST['tag'] == $MyRow['tagref'] or (isset($_SESSION['JournalDetail']->GLEntries[$_GET['Edit']]->tag)) and in_array($MyRow['tagref'], $_SESSION['JournalDetail']->GLEntries[$_GET['Edit']]->tag)) {
		echo '<option selected="selected" value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
	}
	else {
		echo '<option value="', $MyRow['tagref'], '">', $MyRow['tagref'], ' - ', $MyRow['tagdescription'], '</option>';
	}
}
echo '</select>
</div>';
// End select tag
if (!isset($_POST['GLManualCode'])) {
	$_POST['GLManualCode'] = '';
}
echo '<div class="db-form-group">
		<label class="db-label" for="GLManualCode">' . __('GL Account Code') . '</label>
		<input type="text" class="db-input" autofocus="autofocus" name="GLManualCode" maxlength="12" size="12" onchange="inArray(this, GLCode.options,' . "'" . 'The account code ' . "'" . '+ this.value+ ' . "'" . ' doesnt exist' . "'" . ')" value="' . $_POST['GLManualCode'] . '"  />
	</div>';

$SQL = "SELECT chartmaster.accountcode,
			chartmaster.accountname
		FROM chartmaster
			INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canupd=1
		ORDER BY chartmaster.accountcode";

$Result = DB_query($SQL);
echo '<div class="db-form-group">
		<label class="db-label" for="GLCode">' . __('Select GL Account') . '</label>
		<select class="db-select" name="GLCode" onchange="return assignComboToInput(this,' . 'GLManualCode' . ')">
			<option value="">' . __('Select a general ledger account code') . '</option>';
while ($MyRow = DB_fetch_array($Result)) {
	if (isset($_POST['GLCode']) and $_POST['GLCode'] == $MyRow['accountcode']) {
		echo '<option selected="selected" value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
	}
	else {
		echo '<option value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
	}
}
echo '</select>
	</div></div><div class="db-form-row">';

if (!isset($_POST['GLNarrative'])) {
	$_POST['GLNarrative'] = '';
}
if (!isset($_POST['Credit'])) {
	$_POST['Credit'] = 0;
}
if (!isset($_POST['Debit'])) {
	$_POST['Debit'] = 0;
}

echo '<div class="db-form-group">
		<label class="db-label" for="Debit">' . __('Debit') . '</label>
		<input type="text" class="db-input number" name="Debit" onchange="eitherOr(this,Credit)" maxlength="12" size="10" value="' . locale_number_format($_POST['Debit'], $_SESSION['CompanyRecord']['decimalplaces']) . '" />
	</div>
	<div class="db-form-group">
		<label class="db-label" for="Credit">' . __('Credit') . '</label>
		<input type="text" class="db-input number" name="Credit" onchange="eitherOr(this,Debit)" maxlength="12" size="10" value="' . locale_number_format($_POST['Credit'], $_SESSION['CompanyRecord']['decimalplaces']) . '" />
	</div>
	<div class="db-form-group">
		<label class="db-label" for="GLNarrative">' . __('GL Narrative') . '</label>
		<input type="text" class="db-input" name="GLNarrative" maxlength="100" size="100" value="' . $_POST['GLNarrative'] . '" />
	</div>
	</div>'; /*Close the db-form-row */
echo '<div class="centre" style="margin-top: 15px;">
		<button type="submit" name="Process" value="' . __('Accept') . '" class="db-btn db-btn-primary"><i class="fas fa-check"></i> ' . __('Accept') . '</button>
	</div>
	</div></div>';

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title">' . __('Journal Summary') . '</h3></div>
		<div class="db-card-body">
			<div class="db-table-wrap">
				<table class="monochromatic-table">
					<thead>
						<tr>
							<th>' . __('GL Tag') . '</th>
							<th>' . __('GL Account') . '</th>
							<th class="number">' . __('Debit') . '</th>
							<th class="number">' . __('Credit') . '</th>
							<th>' . __('Narrative') . '</th>
							<th style="text-align: center;">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

$DebitTotal = 0;
$CreditTotal = 0;

foreach ($_SESSION['JournalDetail']->GLEntries as $JournalItem) {
	echo '<tr>
		<td data-label="' . __('GL Tag') . '">';
	echo GetDescriptionsFromTagArray($JournalItem->tag);
	echo '</td>
		<td data-label="' . __('GL Account') . '">' . $JournalItem->GLCode . ' - ' . $JournalItem->GLActName . '</td>';
	if ($JournalItem->Amount > 0) {
		echo '<td data-label="' . __('Debit') . '" class="number">' . locale_number_format($JournalItem->Amount, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
				<td data-label="' . __('Credit') . '" class="number"></td>';
		$DebitTotal += $JournalItem->Amount;
	}
	elseif ($JournalItem->Amount < 0) {
		$Credit = (-1 * $JournalItem->Amount);
		echo '<td data-label="' . __('Debit') . '" class="number"></td>
			<td data-label="' . __('Credit') . '" class="number">' . locale_number_format($Credit, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
		$CreditTotal = $CreditTotal + $Credit;
	}

	echo '<td data-label="' . __('Narrative') . '">' . $JournalItem->Narrative . '</td>
		<td class="db-table-actions"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . $JournalItem->ID . '" class="db-btn db-btn-danger"><i class="fas fa-trash"></i> ' . __('Delete') . '</a></td>
	</tr>';
}

echo '<tr><td data-label=""></td>
		<td data-label="" class="number" style="text-align: right; font-weight: 700;">' . __('Total') . '</td>
		<td data-label="' . __('Total Debit') . '" class="number" style="font-weight: 700;">' . locale_number_format($DebitTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
		<td data-label="' . __('Total Credit') . '" class="number" style="font-weight: 700;">' . locale_number_format($CreditTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
	</tr>';
if ($DebitTotal !=  $CreditTotal) {
	echo '<tr><td class="centre" colspan="6" style="background-color: #fee2e2; padding: 10px; text-align: center; color: #b91c1c; border-radius: 6px; font-weight: 600;"><b>' . __('Required to balance') . ' - </b>' . locale_number_format(abs($DebitTotal - $CreditTotal) , $_SESSION['CompanyRecord']['decimalplaces']);
}
if ($DebitTotal > $CreditTotal) {
	echo ' ' . __('Credit') . '</td></tr>';
} elseif ($DebitTotal < $CreditTotal) {
	echo ' ' . __('Debit') . '</td></tr>';
}
echo '			</tbody>
			</table>
		</div>
	</div>
</div>';

if (abs($_SESSION['JournalDetail']->JournalTotal) < CurrencyTolerance($_SESSION['CompanyRecord']['currencydefault']) 
	and $_SESSION['JournalDetail']->GLItemCounter > 0) {
	echo '<div class="centre" style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
			<button type="submit" name="CommitBatch" class="db-btn db-btn-primary" value="' . __('Accept and Process Journal') . '"><i class="fas fa-save"></i> ' . __('Accept and Process Journal') . '</button>
			<button type="submit" name="ConfimSave" class="db-btn db-btn-secondary" value="' . __('Save as a template') . '"><i class="fas fa-file-export"></i> ' . __('Save as a template') . '</button>
		</div>';
} elseif (count($_SESSION['JournalDetail']->GLEntries) > 0) {
	prnMsg(__('The journal must balance ie debits equal to credits before it can be processed') , 'warn');
} else {
	echo '<div class="centre" style="display: flex; justify-content: center; margin-top: 15px;">
			<button type="submit" name="LoadTemplate" class="db-btn db-btn-secondary" value="' . __('Load from a template') . '"><i class="fas fa-file-import"></i> ' . __('Load from a template') . '</button>
		</div>';
}

echo '</div>'; // End db-main-layout
echo '</div>'; // End db-centered-container
echo '</div>'; // End db-page
echo '</form>';
include(__DIR__ . '/includes/footer.php');
