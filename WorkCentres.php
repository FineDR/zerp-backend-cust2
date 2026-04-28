<?php

/* Defines the various centres of work within a manufacturing company. Also the overhead and labour rates applicable to the work centre and its standard capacity */

require(__DIR__ . '/includes/session.php');

$Title = __('Work Centres');
$ViewTopic = 'Manufacturing';
$BookMark = 'WorkCentres';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedWC'])){
	$SelectedWC =$_POST['SelectedWC'];
} elseif (isset($_GET['SelectedWC'])){
	$SelectedWC =$_GET['SelectedWC'];
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (mb_strlen($_POST['Code']) < 2) {
		$InputError = 1;
		prnMsg(__('The Work Centre code must be at least 2 characters long'),'error');
	}
	if (mb_strlen($_POST['Description'])<3) {
		$InputError = 1;
		prnMsg(__('The Work Centre description must be at least 3 characters long'),'error');
	}
	if (mb_strstr($_POST['Code'],' ') OR ContainsIllegalCharacters($_POST['Code']) ) {
		$InputError = 1;
		prnMsg(__('The work centre code cannot contain any of the following characters') . " - ' &amp; + \" \\ " . __('or a space'),'error');
	}

	if (isset($SelectedWC) AND $InputError !=1) {

		/*SelectedWC could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$SQL = "UPDATE workcentres SET location = '" . $_POST['Location'] . "',
						description = '" . $_POST['Description'] . "',
						overheadrecoveryact ='" . $_POST['OverheadRecoveryAct'] . "',
						overheadperhour = '" . $_POST['OverheadPerHour'] . "'
				WHERE code = '" . $SelectedWC . "'";
		$Msg = __('The work centre record has been updated');
	} elseif ($InputError !=1) {

	/*Selected work centre is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new work centre form */

		$SQL = "INSERT INTO workcentres (code,
										location,
										description,
										overheadrecoveryact,
										overheadperhour)
					VALUES ('" . $_POST['Code'] . "',
						'" . $_POST['Location'] . "',
						'" . $_POST['Description'] . "',
						'" . $_POST['OverheadRecoveryAct'] . "',
						'" . $_POST['OverheadPerHour'] . "'
						)";
		$Msg = __('The new work centre has been added to the database');
	}
	//run the SQL from either of the above possibilites

	if ($InputError !=1){
		$Result = DB_query($SQL,__('The update/addition of the work centre failed because'));
		prnMsg($Msg,'success');
		unset ($_POST['Location']);
		unset ($_POST['Description']);
		unset ($_POST['Code']);
		unset ($_POST['OverheadRecoveryAct']);
		unset ($_POST['OverheadPerHour']);
		unset ($SelectedWC);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'BOM'

	$SQL= "SELECT COUNT(*) FROM bom WHERE bom.workcentreadded='" . $SelectedWC . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this work centre because bills of material have been created requiring components to be added at this work center') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' .__('BOM items referring to this work centre code'),'warn');
	}  else {
		$SQL= "SELECT COUNT(*) FROM contractbom WHERE contractbom.workcentreadded='" . $SelectedWC . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			prnMsg(__('Cannot delete this work centre because contract bills of material have been created having components added at this work center') . '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('Contract BOM items referring to this work centre code'),'warn');
		} else {
			$SQL="DELETE FROM workcentres WHERE code='" . $SelectedWC . "'";
			$Result = DB_query($SQL);
			prnMsg(__('The selected work centre record has been deleted'),'succes');
		} // end of Contract BOM test
	} // end of BOM test
}

echo '<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-card-bg: #ffffff;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-page-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; line-height: 1.1; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 1fr 400px; gap: 1.5rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 10px; }
    .db-card-body { padding: 1.25rem; }
    
    .db-table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.875rem 1rem; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); }
    .db-table tr:hover td { background: #f8fafc; }
    .db-table .number { text-align: right; font-family: "JetBrains Mono", monospace; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.375rem; display: block; }
    .db-input, .db-select { 
        padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fff; font-size: 0.875rem; transition: all 0.2s; width: 100%;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    .db-help { font-size: 0.7rem; color: var(--db-text-muted); margin-top: 0.25rem; font-style: italic; }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
    }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    .db-btn-ghost:hover { background: hsl(145, 40%, 90%); }
    
    .db-badge { padding: 3px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.75rem; }
    .link-action:hover { text-decoration: underline; }
    .link-delete { color: hsl(0, 72%, 41%); }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div class="db-breadcrumb">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                ' . __('Manufacturing') . ' / ' . __('Configuration') . '
            </div>
            <h1 class="db-page-title">' . $Title . '</h1>
        </header>

        <div class="db-main-grid">
            <!-- Left Column: Form -->
            <div class="db-field-group">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                if (isset($SelectedWC)) {
                    $SQL = "SELECT code, location, description, overheadrecoveryact, overheadperhour
                            FROM workcentres
                            INNER JOIN locationusers ON locationusers.loccode=workcentres.location AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1
                            WHERE code='" . $SelectedWC . "'";
                    $Result = DB_query($SQL);
                    $MyRow = DB_fetch_array($Result);
                    $_POST['Code'] = $MyRow['code'];
                    $_POST['Location'] = $MyRow['location'];
                    $_POST['Description'] = $MyRow['description'];
                    $_POST['OverheadRecoveryAct']  = $MyRow['overheadrecoveryact'];
                    $_POST['OverheadPerHour']  = $MyRow['overheadperhour'];

                    echo '<input type="hidden" name="SelectedWC" value="' . $SelectedWC . '" />
                          <input type="hidden" name="Code" value="' . $_POST['Code'] . '" />';
                    $cardTitle = __('Modify Work Centre');
                } else {
                    if (!isset($_POST['Code'])) $_POST['Code'] = '';
                    $cardTitle = __('Register New Work Centre');
                }

                echo '<div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4L18.5 2.5z"></path></svg>
                                ' . $cardTitle . '
                            </h3>
                        </div>
                        <div class="db-card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">';
                
                if (isset($SelectedWC)) {
                    echo '<div class="db-field">
                            <label class="db-label">' .__('Work Centre Code') . '</label>
                            <div style="padding: 0.625rem; background: var(--db-primary-soft); border-radius: 8px; font-weight: 700; color: var(--db-primary-dark);">' . $_POST['Code'] . '</div>
                          </div>';
                } else {
                    echo '<div class="db-field">
                            <label class="db-label">' . __('Work Centre Code') . '</label>
                            <input type="text" name="Code" class="db-input" pattern="[^&+-]{2,}" required="required" autofocus="autofocus" maxlength="5" value="' . $_POST['Code'] . '" placeholder="'.__('Min 2 chars').'" />
                            <div class="db-help">'.__('At least 2 characters, no illegal symbols').'</div>
                          </div>';
                }

                echo '<div class="db-field">
                        <label class="db-label">' . __('Description') . '</label>
                        <input type="text" name="Description" class="db-input" pattern="[^&+-]{3,}" required="required" size="21" maxlength="20" value="' . (isset($_POST['Description'])?$_POST['Description']:'') . '" placeholder="'.__('Min 3 chars').'" ' . (isset($SelectedWC)? 'autofocus="autofocus"': '') . ' />
                      </div>

                      <div class="db-field">
                        <label class="db-label">' . __('Location') . '</label>
                        <select name="Location" class="db-select">';
                
                $SQL_Loc = "SELECT locationname, locations.loccode FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
                $Res_Loc = DB_query($SQL_Loc);
                while ($LRow = DB_fetch_array($Res_Loc)) {
                    $sel = (isset($_POST['Location']) and $LRow['loccode']==$_POST['Location']) ? 'selected="selected"' : '';
                    echo '<option ' . $sel . ' value="' . $LRow['loccode'] . '">' . $LRow['locationname'] . '</option>';
                }
                echo '  </select>
                      </div>

                      <div class="db-field">
                        <label class="db-label">' . __('Recovery GL Account') . '</label>
                        <select name="OverheadRecoveryAct" class="db-select">';
                        
                $SQL_GL = "SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl!=0 ORDER BY accountcode";
                $Res_GL = DB_query($SQL_GL);
                while ($GRow = DB_fetch_array($Res_GL)) {
                    $sel = (isset($_POST['OverheadRecoveryAct']) and $GRow['accountcode']==$_POST['OverheadRecoveryAct']) ? 'selected="selected"' : '';
                    echo '<option ' . $sel . ' value="' . $GRow['accountcode'] . '">' . $GRow['accountcode'] . ' - ' . htmlspecialchars($GRow['accountname'], ENT_QUOTES, 'UTF-8', false) . '</option>';
                }
                echo '  </select>
                      </div>

                      <div class="db-field">
                        <label class="db-label">' . __('Overhead Per Hour') . '</label>
                        <input type="text" name="OverheadPerHour" class="db-input number" maxlength="6" value="' . (isset($_POST['OverheadPerHour'])?$_POST['OverheadPerHour']:0) . '" />
                      </div>
                    </div>

                    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                        <button type="submit" name="submit" class="db-btn db-btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            ' . __('Save Work Centre') . '
                        </button>
                        ' . (isset($SelectedWC) ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="db-btn db-btn-ghost">' . __('Cancel') . '</a>' : '') . '
                    </div>
                </div>
              </div>
              </form>
            </div>

            <!-- Right Column: Listing -->
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                        ' . __('Existing Work Centres') . '
                    </h3>
                </div>
                <div class="db-card-body" style="padding:0;">
                    <div class="db-table-container">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Description') . '</th>
                                    <th>' . __('Action') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

                $SQL_List = "SELECT workcentres.code, workcentres.description, locations.locationname, workcentres.overheadrecoveryact, workcentres.overheadperhour
                            FROM workcentres, locations
                            INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
                            WHERE workcentres.location = locations.loccode";
                $Res_List = DB_query($SQL_List);

                while ($LRow = DB_fetch_array($Res_List)) {
                    $activeClass = (isset($SelectedWC) && $SelectedWC == $LRow['code']) ? 'style="background: var(--db-primary-soft);"' : '';
                    echo '<tr ' . $activeClass . '>
                            <td class="db-mono" style="font-weight:700;">' . $LRow['code'] . '</td>
                            <td>' . $LRow['description'] . ' <br><small style="color:var(--db-text-muted);">' . $LRow['locationname'] . '</small></td>
                            <td style="white-space:nowrap;">
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SelectedWC=' . $LRow['code'] . '" class="link-action">' . __('Edit') . '</a> | 
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?SelectedWC=' . $LRow['code'] . '&delete=yes" class="link-action link-delete" onclick="return confirm(\'' . __('Are you sure?') . '\');">' . __('Del') . '</a>
                            </td>
                          </tr>';
                }

                echo '          </tbody>
                        </table>
                    </div>';
                
                if (DB_num_rows($Res_List) == 0) {
                    echo '<div style="padding: 2rem; text-align: center; color: var(--db-text-muted);">' . __('No work centres defined') . '</div>';
                }

                echo '</div>
            </div>
        </div>
    </div>
</div>';

include(__DIR__ . '/includes/footer.php');
?>
