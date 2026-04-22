<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Maintenance Of Petty Cash Type of Tabs');
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
    .db-grid { display: grid; grid-template-columns: 400px 1fr; gap: 30px; align-items: start; }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 25px; }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); background: #fcfcfd; display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.95rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }
    
    /* Forms */
    .form-group { margin-bottom: 1.5rem; }
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
    @media (max-width: 1024px) {
        .db-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 767px) {
        .db-page { padding: 15px !important; margin-left: 0 !important; width: 100% !important; }
        .premium-header { margin: -15px -15px 20px -15px !important; padding: 15px !important; width: calc(100% + 30px) !important; }
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
if (isset($_POST['submit'])) {
	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */
	//first off validate inputs sensible
	$InputError = 0;
	if ($_POST['TypeTabCode'] == '') {
		$InputError = 1;
		prnMsg(__('The Tabs type code cannot be an empty string'), 'error');
	} elseif (mb_strlen($_POST['TypeTabCode']) > 20) {
		$InputError = 1;
		prnMsg(__('The tab code must be twenty characters or less long'), 'error');
	} elseif (ContainsIllegalCharacters($_POST['TypeTabCode']) or mb_strpos($_POST['TypeTabCode'], ' ') > 0) {
		$InputError = 1;
		prnMsg(__('The petty cash tab type code cannot contain any of the illegal characters') . ' ' . '" \' - &amp; or a space', 'error');
	} elseif (mb_strlen($_POST['TypeTabDescription']) > 50) {
		$InputError = 1;
		prnMsg(__('The tab code must be Fifty characters or less long'), 'error');
	}
	if (isset($SelectedTab) and $InputError != 1) {
		$SQL = "UPDATE pctypetabs
			SET typetabdescription = '" . $_POST['TypeTabDescription'] . "'
			WHERE typetabcode = '" . $SelectedTab . "'";
		$Msg = __('The Tabs type') . ' ' . $SelectedTab . ' ' . __('has been updated');
	} elseif ($InputError != 1) {
		// First check the type is not being duplicated
		$CheckSQL = "SELECT count(*)
				 FROM pctypetabs
				 WHERE typetabcode = '" . $_POST['TypeTabCode'] . "'";
		$Checkresult = DB_query($CheckSQL);
		$CheckRow = DB_fetch_row($Checkresult);
		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The Tab type ') . $_POST['TypeAbbrev'] . __(' already exist.'), 'error');
		} else {
			// Add new record on submit
			$SQL = "INSERT INTO pctypetabs
						(typetabcode,
			 			 typetabdescription)
				VALUES ('" . $_POST['TypeTabCode'] . "',
					'" . $_POST['TypeTabDescription'] . "')";
			$Msg = __('Tabs type') . ' ' . $_POST['TypeTabCode'] . ' ' . __('has been created');
		}
	}
	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg, 'success');
		echo '<br />';
		unset($SelectedTab);
		unset($_POST['TypeTabCode']);
		unset($_POST['TypeTabDescription']);
	}
} elseif (isset($_GET['delete'])) {
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'PcTabExpenses'
	$SQLPcTabExpenses = "SELECT COUNT(*)
		FROM pctabexpenses
		WHERE typetabcode='" . $SelectedTab . "'";
	$ErrMsg = __('The number of tabs using this Tab type could not be retrieved');
	$ResultPcTabExpenses = DB_query($SQLPcTabExpenses, $ErrMsg);
	$MyRowPcTabExpenses = DB_fetch_row($ResultPcTabExpenses);
	$SqlPcTabs = "SELECT COUNT(*)
		FROM pctabs
		WHERE typetabcode='" . $SelectedTab . "'";
	$ErrMsg = __('The number of tabs using this Tab type could not be retrieved');
	$ResultPcTabs = DB_query($SqlPcTabs, $ErrMsg);
	$MyRowPcTabs = DB_fetch_row($ResultPcTabs);
	if ($MyRowPcTabExpenses[0] > 0 or $MyRowPcTabs[0] > 0) {
		prnMsg(__('Cannot delete this tab type because tabs have been created using this tab type'), 'error');
		echo '<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">';
		echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
		echo '<div class="centre"><input type="submit" name="Return" value="', __('Return to list of tab types'), '" /></div>';
		echo '</form>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	} else {
		$SQL = "DELETE FROM pctypetabs WHERE typetabcode='" . $SelectedTab . "'";
		$ErrMsg = __('The Tab Type record could not be deleted because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('Tab type') . ' ' . $SelectedTab . ' ' . __('has been deleted'), 'success');
		unset($SelectedTab);
		unset($_GET['delete']);
	} //end if tab type used in transactions
}
	// Left Column: Entry Form
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title">', (isset($SelectedTab) ? __('Edit Tab Type') : __('Create Tab Type')), '</div>
			</div>
			<div class="db-card-body">
				<form method="post" action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '">
					<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';

	if (isset($SelectedTab) and $SelectedTab != '') {
		$SQL = "SELECT typetabcode,
						typetabdescription
				FROM pctypetabs
				WHERE typetabcode='" . $SelectedTab . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$_POST['TypeTabCode'] = $MyRow['typetabcode'];
		$_POST['TypeTabDescription'] = $MyRow['typetabdescription'];
		
		echo '<input type="hidden" name="SelectedTab" value="', $SelectedTab, '" />
			  <input type="hidden" name="TypeTabCode" value="', $_POST['TypeTabCode'], '" />
			  <div class="form-group">
				  <label class="form-label">', __('Tab Type Code'), '</label>
				  <div class="badge-code" style="display:inline-block; margin-top:5px;">', $_POST['TypeTabCode'], '</div>
				  <div style="font-size:0.75rem; color:var(--text-muted); margin-top:8px;">' . __('Code cannot be modified once created.') . '</div>
			  </div>';
	} else {
		echo '<div class="form-group">
				  <label class="form-label">', __('Tab Type Code'), '</label>
				  <input type="text" class="form-control" minlegth="1" maxlength="20" name="TypeTabCode" required="required" placeholder="e.g. OFFICE" />
			  </div>';
	}

	if (!isset($_POST['TypeTabDescription'])) {
		$_POST['TypeTabDescription'] = '';
	}

	echo '<div class="form-group">
			  <label class="form-label">', __('Description'), '</label>
			  <input type="text" class="form-control" name="TypeTabDescription" required="required" maxlength="50" value="', $_POST['TypeTabDescription'], '" placeholder="' . __('Brief description...') . '" />
		  </div>

		  <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:30px;">
			  <button type="submit" name="submit" class="btn-architect btn-primary">', __('Save Type'), '</button>
			  <button type="reset" name="Cancel" class="btn-architect btn-outline">', __('Reset'), '</button>
		  </div>';

	if (isset($SelectedTab)) {
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
					<div class="db-card-title">', __('Defined Tab Types'), '</div>
				</div>
				<div class="db-card-body">
					<div class="table-container">
						<table class="premium-table">
							<thead>
								<tr>
									<th>', __('Code'), '</th>
									<th>', __('Description'), '</th>
									<th style="width:120px;">', __('Actions'), '</th>
								</tr>
							</thead>
							<tbody>';

	$SQL = "SELECT typetabcode,
					typetabdescription
				FROM pctypetabs
				ORDER BY typetabcode";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><span class="badge-code">', $MyRow['typetabcode'], '</span></td>
				<td style="font-weight:500;">', $MyRow['typetabdescription'], '</td>
				<td>
					<a class="action-link" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTab=', $MyRow['typetabcode'], '">' . __('Edit') . '</a>
					<a class="action-link action-delete" href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTab=', $MyRow['typetabcode'], '&amp;delete=yes" onclick="return confirm(\'' . __('Are you sure you wish to delete this tab type?') . '\');">' . __('Delete') . '</a>
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

