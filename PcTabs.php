<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Maintenance Of Petty Cash Tabs');
$ViewTopic = 'PettyCash';
$BookMark = 'PCTabSetup';
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-hover: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-main) !important; color: var(--text-main); font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; box-sizing: border-box; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color);
        margin: -30px -30px 30px -30px; padding: 20px 30px; position: sticky; top: 0; z-index: 1000;
    }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Layout */
    .db-grid { display: grid; grid-template-columns: 450px 1fr; gap: 30px; align-items: start; }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 25px; }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); background: #fcfcfd; display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.95rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }
    
    /* Forms */
    .form-group { margin-bottom: 1.25rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 11px; border-radius: 9px; border: 1px solid #cbd5e1; font-size: 0.95rem; transition: all 0.2s; box-sizing: border-box; background: #fff; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }
    .form-section-title { font-size: 0.75rem; font-weight: 800; color: var(--slate); text-transform: uppercase; letter-spacing: 0.05em; margin: 25px 0 15px 0; padding-top: 15px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }

    .btn-architect { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; box-sizing: border-box; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #475569; }

    /* Table Styling */
    .table-container { overflow-x: auto; background: white; border-radius: 12px; border: 1px solid var(--border-color); }
    table.premium-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; white-space: nowrap; }
    table.premium-table th { background: #f8fafc; padding: 14px 18px; text-align: left; font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); }
    table.premium-table td { padding: 14px 18px; border-bottom: 1px dotted #e2e8f0; color: #334155; }
    table.premium-table tr:hover td { background-color: #f8fafc; }

    .badge-code { font-family: "JetBrains Mono", monospace; background: #f1f5f9; padding: 3px 6px; border-radius: 5px; font-weight: 600; color: #475569; font-size: 0.85rem; }
    .amount-field { font-family: "JetBrains Mono", monospace; font-weight: 700; color: var(--primary); }

    /* Action Links */
    .action-link { font-size: 0.8rem; font-weight: 700; color: var(--primary); text-decoration: none; margin-right: 12px; }
    .action-link:hover { text-decoration: underline; }
    .action-delete { color: var(--rose); }

    /* Responsive Scaling - Forced Overrides */
    @media (max-width: 1200px) {
        .db-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .db-page { padding: 15px !important; margin-left: 0 !important; width: 100% !important; }
        .premium-header { margin: -15px -15px 20px -15px !important; padding: 15px !important; width: calc(100% + 30px) !important; border-radius: 0 !important; }
        .page-title { font-size: 1.4rem !important; }
        .db-card-body { padding: 15px !important; }
        .btn-architect { width: 100% !important; margin-bottom: 8px !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
    <div class="premium-header">
        <div class="header-inner">
            <div>
                <div class="breadcrumb">' . __('Setup') . ' / ' . __('Petty Cash') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    <div class="db-grid">';

if (isset($_POST['SelectedTab'])) {
	$SelectedTab = mb_strtoupper($_POST['SelectedTab']);
} elseif (isset($_GET['SelectedTab'])) {
	$SelectedTab = mb_strtoupper($_GET['SelectedTab']);
}
if (isset($_POST['Cancel'])) {
	unset($SelectedTab);
	unset($_POST['TabCode']);
	unset($_POST['SelectUser']);
	unset($_POST['SelectTabs']);
	unset($_POST['SelectCurrency']);
	unset($_POST['TabLimit']);
	unset($_POST['SelectAssigner']);
	unset($_POST['SelectAuthoriserCash']);
	unset($_POST['SelectAuthoriserExpenses']);
	unset($_POST['GLAccountCash']);
	unset($_POST['GLAccountPcashTab']);
}
if (isset($_POST['Submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	//first off validate inputs sensible
	if ($_POST['TabCode'] == '' or $_POST['TabCode'] == ' ' or $_POST['TabCode'] == '  ') {
		$InputError = 1;
		prnMsg('<br />' . __('The Tab code cannot be an empty string or spaces'), 'error');
	} elseif (mb_strlen($_POST['TabCode']) > 20) {
		$InputError = 1;
		prnMsg(__('The Tab code must be twenty characters or less long'), 'error');
	} elseif (($_POST['SelectUser']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a User for this tab'), 'error');
	} elseif (($_POST['SelectTabs']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a type of tab from the list'), 'error');
	} elseif (($_POST['SelectAssigner']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a User to assign cash to this tab'), 'error');
	} elseif (($_POST['SelectAuthoriserCash']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a User to authorise this tab'), 'error');
	} elseif (($_POST['GLAccountCash']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a General ledger code for the cash to be assigned from'), 'error');
	} elseif (($_POST['GLAccountPcashTab']) == '') {
		$InputError = 1;
		prnMsg(__('You must select a General ledger code for this petty cash tab'), 'error');
	} elseif (($_POST['TaxGroup']) === '0') {
		$InputError = 1;
		prnMsg(__('You must select a tax group'), 'error');
	}
	if (isset($SelectedTab) and $InputError != 1) {
		$SQL = "UPDATE pctabs SET usercode = '" . $_POST['SelectUser'] . "',
									typetabcode = '" . $_POST['SelectTabs'] . "',
									currency = '" . $_POST['SelectCurrency'] . "',
									tablimit = '" . filter_number_format($_POST['TabLimit']) . "',
									assigner = '" . $_POST['SelectAssigner'] . "',
									authorizer = '" . $_POST['SelectAuthoriserCash'] . "',
									authorizerexpenses = '" . $_POST['SelectAuthoriserExpenses'] . "',
									glaccountassignment = '" . $_POST['GLAccountCash'] . "',
									glaccountpcash = '" . $_POST['GLAccountPcashTab'] . "',
									taxgroupid='" . $_POST['TaxGroup'] . "'
				WHERE tabcode = '" . $SelectedTab . "'";
		$Msg = __('The Petty Cash Tab') . ' ' . $SelectedTab . ' ' . __('has been updated');
	} elseif ($InputError != 1) {
		// First check the type is not being duplicated
		$CheckSQL = "SELECT count(*)
					 FROM pctabs
					 WHERE tabcode = '" . $_POST['TabCode'] . "'";
		$CheckResult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($CheckResult);
		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The Tab ') . ' ' . $_POST['TabCode'] . ' ' . __(' already exists'), 'error');
		} else {
			// Add new record on submit
			$SQL = "INSERT INTO pctabs	(tabcode,
							 			 usercode,
										 typetabcode,
										 currency,
										 tablimit,
										 assigner,
										 authorizer,
										 authorizerexpenses,
										 glaccountassignment,
										 glaccountpcash,
										 taxgroupid)
								VALUES ('" . $_POST['TabCode'] . "',
									'" . $_POST['SelectUser'] . "',
									'" . $_POST['SelectTabs'] . "',
									'" . $_POST['SelectCurrency'] . "',
									'" . filter_number_format($_POST['TabLimit']) . "',
									'" . $_POST['SelectAssigner'] . "',
									'" . $_POST['SelectAuthoriserCash'] . "',
									'" . $_POST['SelectAuthoriserExpenses'] . "',
									'" . $_POST['GLAccountCash'] . "',
									'" . $_POST['GLAccountPcashTab'] . "',
									'" . $_POST['TaxGroup'] . "'
								)";
			$Msg = __('The Petty Cash Tab') . ' ' . $_POST['TabCode'] . ' ' . __('has been created');
		}
	}
	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		unset($SelectedTab);
		unset($_POST['SelectUser']);
		unset($_POST['TabCode']);
		unset($_POST['SelectTabs']);
		unset($_POST['SelectCurrency']);
		unset($_POST['TabLimit']);
		unset($_POST['SelectAssigner']);
		unset($_POST['SelectAuthoriserCash']);
		unset($_POST['GLAccountCash']);
		unset($_POST['GLAccountPcashTab']);
		unset($_POST['TaxGroup']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM pctabs WHERE tabcode='" . $SelectedTab . "'";
	$ErrMsg = __('The Tab record could not be deleted because');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(__('The Petty Cash Tab') . ' ' . $SelectedTab . ' ' . __('has been deleted'), 'success');
	unset($SelectedTab);
	unset($_GET['delete']);
}
	// Left Column: Entry Form Card
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title">', (isset($SelectedTab) ? __('Amend Petty Cash Tab') : __('Create Petty Cash Tab')), '</div>
			</div>
			<div class="db-card-body">
				<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
					<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedTab) and $SelectedTab != '') {
		$SQL = "SELECT tabcode, usercode, typetabcode, currency, tablimit, assigner, authorizer, authorizerexpenses, glaccountassignment, glaccountpcash, taxgroupid FROM pctabs WHERE tabcode='" . $SelectedTab . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_POST['TabCode'] = $MyRow['tabcode'];
		$_POST['SelectUser'] = $MyRow['usercode'];
		$_POST['SelectTabs'] = $MyRow['typetabcode'];
		$_POST['SelectCurrency'] = $MyRow['currency'];
		$_POST['TabLimit'] = locale_number_format($MyRow['tablimit']);
		$_POST['SelectAssigner'] = $MyRow['assigner'];
		$_POST['SelectAuthoriserCash'] = $MyRow['authorizer'];
		$_POST['SelectAuthoriserExpenses'] = $MyRow['authorizerexpenses'];
		$_POST['GLAccountCash'] = $MyRow['glaccountassignment'];
		$_POST['GLAccountPcashTab'] = $MyRow['glaccountpcash'];
		$_POST['TaxGroup'] = $MyRow['taxgroupid'];
		
		echo '<input type="hidden" name="SelectedTab" value="', $SelectedTab, '" />
			  <input type="hidden" name="TabCode" value="', $_POST['TabCode'], '" />
			  <div class="form-group">
				  <label class="form-label">', __('Tab Code'), '</label>
				  <div class="badge-code" style="display:inline-block; margin-top:5px;">', $_POST['TabCode'], '</div>
			  </div>';
	} else {
		echo '<div class="form-group">
				  <label class="form-label">', __('Tab Code'), '</label>
				  <input type="text" class="form-control" required="required" maxlength="20" name="TabCode" placeholder="e.g. TAB01" />
			  </div>';
	}

	echo '<div class="form-section-title">', __('General Configuration'), '</div>';

	echo '<div class="form-group">
			<label class="form-label">', __('Primary User'), '</label>
			<select required="required" name="SelectUser" class="form-control">';
	$SQL = "SELECT userid, realname FROM www_users ORDER BY userid";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectUser']) && $MyRow['userid'] == $_POST['SelectUser'] ? 'selected="selected"' : ''), ' value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
	}
	echo '</select></div>';

	echo '<div class="form-group">
			<label class="form-label">', __('Tab Type'), '</label>
			<select required="required" name="SelectTabs" class="form-control">';
	$SQL = "SELECT typetabcode, typetabdescription FROM pctypetabs ORDER BY typetabcode";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectTabs']) && $MyRow['typetabcode'] == $_POST['SelectTabs'] ? 'selected="selected"' : ''), ' value="', $MyRow['typetabcode'], '">', $MyRow['typetabcode'], ' - ', $MyRow['typetabdescription'], '</option>';
	}
	echo '</select></div>';

	echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
			<div class="form-group">
				<label class="form-label">', __('Currency'), '</label>
				<select required="required" name="SelectCurrency" class="form-control">';
	$SQL = "SELECT currency, currabrev FROM currencies";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectCurrency']) && $MyRow['currabrev'] == $_POST['SelectCurrency'] ? 'selected="selected"' : ''), ' value="', $MyRow['currabrev'], '">', $MyRow['currency'], '</option>';
	}
	echo '</select></div>';

	if (!isset($_POST['TabLimit'])) { $_POST['TabLimit'] = 0; }
	echo '<div class="form-group">
				<label class="form-label">', __('Tab Limit'), '</label>
				<input type="text" class="form-control number" name="TabLimit" required="required" maxlength="11" value="', $_POST['TabLimit'], '" />
			</div>
		  </div>';

	echo '<div class="form-group">
			<label class="form-label">', __('Tax Group'), '</label>
			<select name="TaxGroup" class="form-control">';
	$SQL = "SELECT taxgroupid, taxgroupdescription FROM taxgroups ORDER BY taxgroupdescription";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['TaxGroup']) && $_POST['TaxGroup'] == $MyRow['taxgroupid'] ? 'selected="selected"' : ''), ' value="', $MyRow['taxgroupid'], '">', $MyRow['taxgroupdescription'], '</option>';
	}
	echo '</select></div>';

	echo '<div class="form-section-title">', __('Workflow & Authorizations'), '</div>';

	echo '<div class="form-group">
			<label class="form-label">', __('Cash Assigner'), '</label>
			<select required="required" name="SelectAssigner" class="form-control">';
	$SQL = "SELECT userid, realname FROM www_users ORDER BY userid"; $Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectAssigner']) && $MyRow['userid'] == $_POST['SelectAssigner'] ? 'selected="selected"' : ''), ' value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
	}
	echo '</select></div>';

	echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
			<div class="form-group">
				<label class="form-label">', __('Authorizer (Cash)'), '</label>
				<select required="required" name="SelectAuthoriserCash" class="form-control">';
	$SQL = "SELECT userid, realname FROM www_users ORDER BY userid"; $Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectAuthoriserCash']) && $MyRow['userid'] == $_POST['SelectAuthoriserCash'] ? 'selected="selected"' : ''), ' value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
	}
	echo '</select></div>';

	echo '<div class="form-group">
				<label class="form-label">', __('Authorizer (Exp)'), '</label>
				<select required="required" name="SelectAuthoriserExpenses" class="form-control">';
	$SQL = "SELECT userid, realname FROM www_users ORDER BY userid"; $Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['SelectAuthoriserExpenses']) && $MyRow['userid'] == $_POST['SelectAuthoriserExpenses'] ? 'selected="selected"' : ''), ' value="', $MyRow['userid'], '">', $MyRow['userid'], ' - ', $MyRow['realname'], '</option>';
	}
	echo '</select></div>
		  </div>';

	echo '<div class="form-section-title">', __('GL Integration'), '</div>';

	echo '<div class="form-group">
			<label class="form-label">', __('GL Account Assignment'), '</label>
			<select required="required" name="GLAccountCash" class="form-control">';
	$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN bankaccounts ON chartmaster.accountcode = bankaccounts.accountcode ORDER BY chartmaster.accountcode";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['GLAccountCash']) && $MyRow['accountcode'] == $_POST['GLAccountCash'] ? 'selected="selected"' : ''), ' value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</option>';
	}
	echo '</select></div>';

	echo '<div class="form-group">
			<label class="form-label">', __('GL Account Petty Cash'), '</label>
			<select required="required" name="GLAccountPcashTab" class="form-control">';
	$SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['GLAccountPcashTab']) && $MyRow['accountcode'] == $_POST['GLAccountPcashTab'] ? 'selected="selected"' : ''), ' value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</option>';
	}
	echo '</select></div>';

	echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:30px;">
			  <button type="submit" name="Submit" class="btn-architect btn-primary">', __('Save Changes'), '</button>
			  <button type="submit" name="Cancel" class="btn-architect btn-outline">', __('Reset Form'), '</button>
		  </div>';

	if (isset($SelectedTab)) {
		echo '<div style="margin-top:20px; text-align:center;">
				<a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" class="action-link" style="margin-right:0;">' . __('Cancel Edit & Show All') . '</a>
			  </div>';
	}
	
	echo '		</form>
			</div>
		</div>
	</div>'; // End Left Card

	// Right Column: Data Table Card
	echo '<div>
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">', __('Active Petty Cash Tabs'), '</div>
				</div>
				<div class="db-card-body">
					<div class="table-container">
						<table class="premium-table">
							<thead>
								<tr>
									<th>', __('Code'), '</th>
									<th>', __('User'), '</th>
									<th>', __('Type'), '</th>
									<th>', __('Currency'), '</th>
									<th>', __('Limit'), '</th>
									<th>', __('Assigner'), '</th>
									<th>', __('Auth (C)'), '</th>
									<th>', __('Auth (E)'), '</th>
									<th>', __('Tax Group'), '</th>
									<th style="width:100px;">', __('Actions'), '</th>
								</tr>
							</thead>
							<tbody>';

	$SQL = "SELECT tabcode, usercode, typetabdescription, currabrev, tablimit, assigner, authorizer, authorizerexpenses, taxgroupdescription, currencies.decimalplaces FROM pctabs INNER JOIN currencies ON pctabs.currency=currencies.currabrev INNER JOIN pctypetabs ON pctabs.typetabcode=pctypetabs.typetabcode INNER JOIN taxgroups ON pctabs.taxgroupid=taxgroups.taxgroupid ORDER BY tabcode";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><span class="badge-code">', $MyRow['tabcode'], '</span></td>
				<td>', $MyRow['usercode'], '</td>
				<td>', $MyRow['typetabdescription'], '</td>
				<td>', $MyRow['currabrev'], '</td>
				<td class="amount-field">', locale_number_format($MyRow['tablimit'], $MyRow['decimalplaces']), '</td>
				<td>', $MyRow['assigner'], '</td>
				<td>', $MyRow['authorizer'], '</td>
				<td>', $MyRow['authorizerexpenses'], '</td>
				<td>', $MyRow['taxgroupdescription'], '</td>
				<td>
					<a class="action-link" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTab=', $MyRow['tabcode'], '">' . __('Edit') . '</a>
					<a class="action-link action-delete" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTab=', $MyRow['tabcode'], '&amp;delete=yes" onclick=\'return confirm("' . __('Are you sure you wish to delete this tab code?') . '");\'>' . __('Delete') . '</a>
				</td>
			</tr>';
	}

	echo '				</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>'; // End Right Card

echo '</div></div>'; // Close db-grid and db-page

include(__DIR__ . '/includes/footer.php');
