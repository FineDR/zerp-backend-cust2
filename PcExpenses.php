<?php

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'PettyCash';
$BookMark = 'PCExpenses';
$Title = __('Maintenance Of Petty Cash Of Expenses');
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
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 1rem; transition: all 0.2s; box-sizing: border-box; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }

    .btn-architect { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; box-sizing: border-box; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #475569; }

    /* Table Styling */
    .table-container { overflow-x: auto; background: white; border-radius: 12px; border: 1px solid var(--border-color); }
    table.premium-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    table.premium-table th { background: #f8fafc; padding: 14px 20px; text-align: left; font-weight: 700; color: #64748b; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); }
    table.premium-table td { padding: 16px 20px; border-bottom: 1px dotted #e2e8f0; color: #334155; }
    table.premium-table tr:hover td { background-color: #f1f5f9; }

    .badge-code { font-family: "JetBrains Mono", monospace; background: #f1f5f9; padding: 4px 8px; border-radius: 6px; font-weight: 600; color: #475569; }

    /* Action Links */
    .action-link { font-size: 0.85rem; font-weight: 700; color: var(--primary); text-decoration: none; margin-right: 15px; }
    .action-link:hover { text-decoration: underline; }
    .action-delete { color: var(--rose); }

    /* Responsive Scaling - Forced Overrides */
    @media (max-width: 1200px) {
        .db-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .db-page { padding: 15px !important; margin-left: 0 !important; width: 100% !important; overflow-x: hidden !important; }
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

if (isset($_POST['SelectedExpense'])) {
	$SelectedExpense = mb_strtoupper($_POST['SelectedExpense']);
} elseif (isset($_GET['SelectedExpense'])) {
	$SelectedExpense = mb_strtoupper($_GET['SelectedExpense']);
}
if (isset($_POST['Cancel'])) {
	unset($SelectedExpense);
	unset($_POST['CodeExpense']);
	unset($_POST['Description']);
	unset($_POST['GLAccount']);
}
// gg: seems unused
//if (isset($Errors)) {
//	unset($Errors);
//}
if (isset($_POST['submit'])) {
	//initialise no input errors assumed initially before we test
	$InputError = 0;
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	//first off validate inputs sensible
	if ($_POST['CodeExpense'] == '' or $_POST['CodeExpense'] == ' ' or $_POST['CodeExpense'] == '  ') {
		$InputError = 1;
		prnMsg(__('The Expense type  code cannot be an empty string or spaces'), 'error');
	} elseif (mb_strlen($_POST['CodeExpense']) > 20) {
		$InputError = 1;
		prnMsg(__('The expense code must be twenty characters or less long'), 'error');
	} elseif (ContainsIllegalCharacters($_POST['CodeExpense'])) {
		$InputError = 1;
		prnMsg(__('The expense code cannot contain any of the following characters ') . '" \' - &amp;', 'error');
	} elseif (ContainsIllegalCharacters($_POST['Description'])) {
		$InputError = 1;
		prnMsg(__('The expense description cannot contain any of the following characters ') . '" \' - &amp;', 'error');
	} elseif (mb_strlen($_POST['Description']) > 50) {
		$InputError = 1;
		prnMsg(__('The tab code must be fifty characters or less long'), 'error');
	} elseif (mb_strlen($_POST['Description']) == 0) {
		$InputError = 1;
		prnMsg(__('The tab code description must be entered'), 'error');
	} elseif ($_POST['GLAccount'] == '') {
		$InputError = 1;
	} elseif ($_POST['TaxCategory'] === '0') {
		$InputError = 1;
		prnMsg(__('A tax category must be selected from the list'), 'error');
	}
	if (isset($SelectedExpense) and $InputError != 1) {
		$SQL = "UPDATE pcexpenses
				SET description = '" . $_POST['Description'] . "',
					glaccount = '" . $_POST['GLAccount'] . "',
					taxcatid='" . $_POST['TaxCategory'] . "'
				WHERE codeexpense = '" . $SelectedExpense . "'";
		$Msg = __('The Expenses type') . ' ' . $SelectedExpense . ' ' . __('has been updated');
	} elseif ($InputError != 1) {
		// First check the type is not being duplicated
		$CheckSQL = "SELECT count(*)
				 FROM pcexpenses
				 WHERE codeexpense = '" . $_POST['CodeExpense'] . "'";
		$CheckResult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($CheckResult);
		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The Expense type ') . $_POST['CodeExpense'] . __(' already exists'), 'error');
		} else {
			// Add new record on submit
			$SQL = "INSERT INTO pcexpenses
						(codeexpense,
			 			 description,
			 			 glaccount,
			 			 taxcatid)
				VALUES ('" . $_POST['CodeExpense'] . "',
						'" . $_POST['Description'] . "',
						'" . $_POST['GLAccount'] . "',
						'" . $_POST['TaxCategory'] . "'
						)";
			$Msg = __('Expense') . ' ' . $_POST['CodeExpense'] . ' ' . __('has been created');
		}
	}
	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		unset($SelectedExpense);
		unset($_POST['CodeExpense']);
		unset($_POST['Description']);
		unset($_POST['GLAccount']);
		unset($_POST['TaxGroup']);
	}
} elseif (isset($_GET['delete'])) {
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'PcTabExpenses'
	$SQL = "SELECT COUNT(*)
		   FROM pctabexpenses
		   WHERE codeexpense='" . $SelectedExpense . "'";
	$ErrMsg = __('The number of type of tabs using this expense code could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(__('Cannot delete this petty cash expense because it is used in some tab types') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('tab types using this expense code'), 'error');
	} else {
		$SQL = "DELETE FROM pcexpenses
				  WHERE codeexpense='" . $SelectedExpense . "'";
		$ErrMsg = __('The expense type record could not be deleted because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('Expense type') . ' ' . $SelectedExpense . ' ' . __('has been deleted'), 'success');
		unset($SelectedExpense);
		unset($_GET['delete']);
	} //end if tab type used in transactions
}
	// Left Column: Entry Form
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title">', (isset($SelectedExpense) ? __('Amend Expense Code') : __('Create Expense Code')), '</div>
			</div>
			<div class="db-card-body">
				<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
					<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedExpense) and $SelectedExpense != '') {
		$SQL = "SELECT codeexpense, description, glaccount, taxcatid FROM pcexpenses WHERE codeexpense='" . $SelectedExpense . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_POST['CodeExpense'] = $MyRow['codeexpense'];
		$_POST['Description'] = $MyRow['description'];
		$_POST['GLAccount'] = $MyRow['glaccount'];
		$_POST['TaxCategory'] = $MyRow['taxcatid'];
		
		echo '<input type="hidden" name="SelectedExpense" value="', $SelectedExpense, '" />
			  <input type="hidden" name="CodeExpense" value="', $_POST['CodeExpense'], '" />
			  <div class="form-group">
				  <label class="form-label">', __('Expense Code'), '</label>
				  <div class="badge-code" style="display:inline-block; margin-top:5px;">', $_POST['CodeExpense'], '</div>
			  </div>';
	} else {
		echo '<div class="form-group">
				  <label class="form-label">', __('Expense Code'), '</label>
				  <input type="text" class="form-control" name="CodeExpense" autofocus="autofocus" required="required" maxlength="20" placeholder="e.g. TRAVEL" />
			  </div>';
	}

	if (!isset($_POST['Description'])) { $_POST['Description'] = ''; }

	echo '<div class="form-group">
			  <label class="form-label">', __('Description'), '</label>
			  <input type="text" class="form-control" name="Description" required="required" maxlength="50" value="', $_POST['Description'], '" />
		  </div>';

	echo '<div class="form-group">
			  <label class="form-label">', __('GL Account'), '</label>
			  <select required="required" name="GLAccount" class="form-control">';
	$SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
	$Result = DB_query($SQL);
	echo '<option value="">', __('Not Yet Selected'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['GLAccount']) && $MyRow['accountcode'] == $_POST['GLAccount'] ? 'selected="selected"' : ''), ' value="', $MyRow['accountcode'], '">', $MyRow['accountcode'], ' - ', htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8', false), '</option>';
	}
	echo '</select></div>';

	echo '<div class="form-group">
			  <label class="form-label">', __('Tax Category'), '</label>
			  <select name="TaxCategory" class="form-control">';
	$SQL = "SELECT taxcatid, taxcatname FROM taxcategories";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option ', (isset($_POST['TaxCategory']) && $_POST['TaxCategory'] == $MyRow['taxcatid'] ? 'selected="selected"' : ''), ' value="', $MyRow['taxcatid'], '">', $MyRow['taxcatname'], '</option>';
	}
	echo '</select></div>';

	echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:30px;">
			  <button type="submit" name="submit" class="btn-architect btn-primary">', __('Save Expense'), '</button>
			  <button type="submit" name="Cancel" class="btn-architect btn-outline">', __('Reset'), '</button>
		  </div>';

	if (isset($SelectedExpense)) {
		echo '<div style="margin-top:20px; text-align:center;">
				<a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" class="action-link" style="margin-right:0;">' . __('Cancel Edit & Show All') . '</a>
			  </div>';
	}
	
	echo '		</form>
			</div>
		</div>
	</div>'; // End Left Column

	// Right Column: Data Table
	echo '<div>
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">', __('Defined Expenses'), '</div>
				</div>
				<div class="db-card-body">
					<div class="table-container">
						<table class="premium-table">
							<thead>
								<tr>
									<th>', __('Code'), '</th>
									<th>', __('Description'), '</th>
									<th>', __('GL Account'), '</th>
									<th>', __('Tax Category'), '</th>
									<th style="width:120px;">', __('Actions'), '</th>
								</tr>
							</thead>
							<tbody>';

	$SQL = "SELECT pcexpenses.codeexpense, pcexpenses.description, pcexpenses.glaccount, chartmaster.accountname, taxcategories.taxcatname FROM pcexpenses INNER JOIN chartmaster ON pcexpenses.glaccount = chartmaster.accountcode INNER JOIN taxcategories ON pcexpenses.taxcatid = taxcategories.taxcatid ORDER BY pcexpenses.codeexpense";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><span class="badge-code">', $MyRow['codeexpense'], '</span></td>
				<td style="font-weight:500;">', $MyRow['description'], '</td>
				<td><div style="font-size:0.75rem; color:var(--text-muted);">', $MyRow['glaccount'], '</div>', $MyRow['accountname'], '</td>
				<td>', $MyRow['taxcatname'], '</td>
				<td>
					<a class="action-link" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedExpense=', $MyRow['codeexpense'], '">' . __('Edit') . '</a>
					<a class="action-link action-delete" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedExpense=', $MyRow['codeexpense'], '&amp;delete=yes" onclick=\'return confirm("' . __('Are you sure you wish to delete this expense code?') . '");\'>' . __('Delete') . '</a>
				</td>
			</tr>';
	}

	echo '				</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>'; // End Right Column Column

echo '</div></div>'; // Close db-grid and db-page

include(__DIR__ . '/includes/footer.php');
