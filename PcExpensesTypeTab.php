<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Maintenance Of Petty Cash Expenses For a Type Tab');
/* webERP manual links before header.php */
$ViewTopic = 'PettyCash';
$BookMark = 'PCTabTypes';
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
    </div>';

if (isset($_POST['SelectedCode'])) {
	$SelectedCode = mb_strtoupper($_POST['SelectedCode']);
} elseif (isset($_GET['SelectedCode'])) {
	$SelectedCode = mb_strtoupper($_GET['SelectedCode']);
} else {
	$SelectedCode = '';
}
if (!isset($_GET['delete']) and (ContainsIllegalCharacters($SelectedCode) or mb_strpos($SelectedCode, ' ') > 0)) {
	$InputError = 1;
	prnMsg(__('The petty cash tab type contain any of the following characters') . ' ' . '" \' - &amp; or a space', 'error');
}
if (isset($_POST['SelectedTab'])) {
	$SelectedTab = mb_strtoupper($_POST['SelectedTab']);
} elseif (isset($_GET['SelectedTab'])) {
	$SelectedTab = mb_strtoupper($_GET['SelectedTab']);
}
if (isset($_POST['Cancel'])) {
	unset($SelectedTab);
	unset($SelectedCode);
}
if (isset($_POST['Process'])) {
	if ($_POST['SelectedTab'] == '') {
		prnMsg(__('You have not selected a tab to maintain the expenses on'), 'error');
		echo '<br />';
		unset($SelectedTab);
		unset($_POST['SelectedTab']);
	}
}
if (isset($_POST['submit'])) {
	$InputError = 0;
	if ($_POST['SelectedExpense'] == '') {
		$InputError = 1;
		prnMsg(__('You have not selected an expense to add to this tab'), 'error');
		echo '<br />';
		unset($SelectedTab);
	}
	if ($InputError != 1) {
		// First check the type is not being duplicated
		$CheckSQL = "SELECT count(*)
				 FROM pctabexpenses
				 WHERE typetabcode= '" . $_POST['SelectedTab'] . "'
				 AND codeexpense = '" . $_POST['SelectedExpense'] . "'";
		$CheckResult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($CheckResult);
		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The Expense') . ' ' . $_POST['codeexpense'] . ' ' . __('already exists in this Type of Tab'), 'error');
		} else {
			// Add new record on submit
			$SQL = "INSERT INTO pctabexpenses (typetabcode,
												codeexpense)
										VALUES ('" . $_POST['SelectedTab'] . "',
												'" . $_POST['SelectedExpense'] . "')";
			$Msg = __('Expense code') . ': ' . $_POST['SelectedExpense'] . ' ' . __('for Type of Tab') . ': ' . $_POST['SelectedTab'] . ' ' . __('has been created');
			$CheckSQL = "SELECT count(typetabcode)
							FROM pctypetabs";
			$Result = DB_query($CheckSQL);
			$Row = DB_fetch_row($Result);
		}
	}
	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		unset($_POST['SelectedExpense']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM pctabexpenses
		WHERE typetabcode='" . $SelectedTab . "'
		AND codeexpense='" . $SelectedCode . "'";
	$ErrMsg = __('The Tab Type record could not be deleted because');
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(__('Expense code') . ' ' . $SelectedCode . ' ' . __('for type of tab') . ' ' . $SelectedTab . ' ' . __('has been deleted'), 'success');
	unset($_GET['delete']);
}
if (!isset($SelectedTab) or $SelectedTab == '') {
	// First step: Select Tab Type
	echo '<div class="db-card" style="max-width:600px; margin: 40px auto;">
			<div class="db-card-header">
				<div class="db-card-title">', __('Select Type of Tab to Maintain'), '</div>
			</div>
			<div class="db-card-body">
				<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
					<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
					<div class="form-group">
						<label class="form-label">', __('Petty Cash Tab Type'), '</label>
						<select required="required" name="SelectedTab" class="form-control">';
	$SQL = "SELECT typetabcode, typetabdescription FROM pctypetabs ORDER BY typetabcode";
	$Result = DB_query($SQL);
	echo '<option value="">', __('Not Yet Selected'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="', $MyRow['typetabcode'], '">', $MyRow['typetabcode'], ' - ', $MyRow['typetabdescription'], '</option>';
	}
	echo '		</select>
					</div>
					<div style="margin-top:30px;">
						<button type="submit" name="Process" class="btn-architect btn-primary" style="width:100%;">', __('Continue to Expenses'), '</button>
					</div>
				</form>
			</div>
		</div>';
} else {
	// Second step: Main mapping interface
	echo '<div class="db-grid">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">', __('Add Expense to Tab'), '</div>
				</div>
				<div class="db-card-body">
					<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
						<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
						<input type="hidden" name="SelectedTab" value="', $SelectedTab, '" />
						
						<div class="form-group">
							<label class="form-label">', __('Current Tab Type'), '</label>
							<div class="badge-code">', $SelectedTab, '</div>
						</div>

						<div class="form-group">
							<label class="form-label">', __('Select Expense Code'), '</label>
							<select required="required" name="SelectedExpense" class="form-control">';
	$SQL = "SELECT codeexpense, description FROM pcexpenses ORDER BY codeexpense";
	$Result = DB_query($SQL);
	echo '<option value="">', __('Not Yet Selected'), '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="', $MyRow['codeexpense'], '">', $MyRow['codeexpense'], ' - ', $MyRow['description'], '</option>';
	}
	echo '			</select>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:30px;">
							<button type="submit" name="submit" class="btn-architect btn-primary">', __('Add Expense'), '</button>
							<button type="submit" name="Cancel" class="btn-architect btn-outline">', __('Back'), '</button>
						</div>
					</form>
				</div>
			</div>

			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">', __('Expenses mapped to'), ' ', $SelectedTab, '</div>
				</div>
				<div class="db-card-body">
					<div class="table-container">
						<table class="premium-table">
							<thead>
								<tr>
									<th>', __('Expense Code'), '</th>
									<th>', __('Description'), '</th>
									<th style="width:100px;">', __('Actions'), '</th>
								</tr>
							</thead>
							<tbody>';

	$SQL = "SELECT pctabexpenses.codeexpense, pcexpenses.description FROM pctabexpenses INNER JOIN pcexpenses ON pctabexpenses.codeexpense=pcexpenses.codeexpense WHERE pctabexpenses.typetabcode='" . $SelectedTab . "' ORDER BY pctabexpenses.codeexpense ASC";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><span class="badge-code">', $MyRow['codeexpense'], '</span></td>
				<td style="font-weight:500;">', $MyRow['description'], '</td>
				<td>
					<a class="action-link action-delete" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?SelectedCode=', $MyRow['codeexpense'], '&amp;delete=yes&amp;SelectedTab=', $SelectedTab, '" onclick="return confirm(\'' . __('Are you sure you wish to delete this expense code?') . '\');">' . __('Delete') . '</a>
				</td>
			</tr>';
	}

	echo '				</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>'; // End db-grid
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
