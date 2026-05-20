<?php

/* Maintenance of GL Accounts allowed for a user. */

require(__DIR__ . '/includes/session.php');

$Title = __('GL Account Authorised Users');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountUsers';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedGLAccount']) and $_POST['SelectedGLAccount']<>'') {
	$SelectedGLAccount = mb_strtoupper($_POST['SelectedGLAccount']);
} elseif (isset($_GET['SelectedGLAccount']) and $_GET['SelectedGLAccount']<>'') {
	$SelectedGLAccount = mb_strtoupper($_GET['SelectedGLAccount']);
}

if (isset($_POST['SelectedUser']) and $_POST['SelectedUser']<>'') {
	$SelectedUser = mb_strtoupper($_POST['SelectedUser']);
} elseif (isset($_GET['SelectedUser']) and isset($_GET['SelectedGLAccount']) and $_GET['SelectedGLAccount']<>'') {
	$SelectedUser = mb_strtoupper($_GET['SelectedUser']);
}

if (isset($_POST['Cancel']) or isset($_GET['Cancel'] )) {
	unset($SelectedGLAccount, $SelectedUser);
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 350px; gap: 2rem; align-items: start; }
    @media (max-width: 1024px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; transition: all 0.2s; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: #fff; width: 100%; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-btn-outline-sm { padding: 0.4rem 0.75rem; font-size: 0.75rem; border-color: var(--db-border); }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-green { background: #dcfce7; color: #166534; }
</style>';

echo '<div class="db-page">';

if (!isset($SelectedGLAccount)) {
	echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Security') . '</div><h1 class="db-title">' . $Title . '</h1></header>';
	if (isset($_POST['Process'])) prnMsg(__('Please select a GL Account'), 'error');

	echo '<div class="db-card" style="max-width: 600px; margin: 0 auto;">
            <div class="db-card-header"><i class="fas fa-search" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Account Selection') . '</h3></div>
            <div class="db-card-body">
                <form action="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '" method="post">
                <input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />
                <div class="db-form-group">
                    <label class="db-label">Select GL Account</label>
                    <select name="SelectedGLAccount" class="db-select" onchange="this.form.submit()">
                        <option value="">', __('Not Yet Selected'), '</option>';
                        $Result = DB_query("SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode");
                        while ($MyRow = DB_fetch_array($Result)) {
                            echo '<option value="', $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . $MyRow['accountname'] . '</option>';
                        }
	echo '          </select>
                </div>
                <button name="Process" type="submit" class="db-btn db-btn-primary"><i class="fas fa-check"></i> ', __('Configure Permissions'), '</button>
                </form>
            </div>
          </div>';

} else {
	$Result = DB_query("SELECT accountname FROM chartmaster WHERE accountcode='" . $SelectedGLAccount . "'");
	$MyRow = DB_fetch_array($Result);
	$SelectedGLAccountName = $MyRow['accountname'];

	echo '<header class="db-header">
            <div class="db-breadcrumb"><a href="'.basename(__FILE__).'" style="color:inherit">' . __('Security') . '</a> / ' . $SelectedGLAccount . '</div>
            <h1 class="db-title">' . $SelectedGLAccountName . '</h1>
          </header>';

	if (isset($_POST['submit'])) {
		if (!isset($SelectedUser)) {
			prnMsg(__('No user selected'), 'error');
		} else {
			$CheckResult = DB_query("SELECT count(*) FROM glaccountusers WHERE accountcode= '" . $SelectedGLAccount . "' AND userid = '" . $SelectedUser . "'");
			if (DB_fetch_row($CheckResult)[0] > 0) {
				prnMsg(__('User already authorised'), 'error');
			} else {
				$SQL = "INSERT INTO glaccountusers (accountcode, userid, canview, canupd) VALUES ('".$SelectedGLAccount."','".$SelectedUser."','1','1')";
				if (DB_query($SQL)) {
					prnMsg(__('User access added'), 'success');
					unset($_POST['SelectedUser']);
				}
			}
		}
	} elseif (isset($_GET['delete'])) {
		if (DB_query("DELETE FROM glaccountusers WHERE accountcode='" . $SelectedGLAccount . "' AND userid='" . $SelectedUser . "'")) {
			prnMsg(__('Access removed'), 'success');
		}
	} elseif (isset($_GET['ToggleUpdate'])) {
		if (DB_query("UPDATE glaccountusers SET canupd='" . $_GET['ToggleUpdate'] . "' WHERE accountcode='" . $SelectedGLAccount . "' AND userid='" . $SelectedUser . "'")) {
			prnMsg(__('Update permissions modified'), 'success');
		}
	}

    echo '<div class="db-layout">';
    
    // MAIN: Users Table
    echo '<main class="db-main">';
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-users" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Authorised Users') . '</h3></div>';
    echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>User</th><th>View</th><th>Update</th><th style="text-align:right">Actions</th></tr></thead><tbody>';
    $Result = DB_query("SELECT glaccountusers.userid, canview, canupd, www_users.realname FROM glaccountusers INNER JOIN www_users ON glaccountusers.userid=www_users.userid WHERE glaccountusers.accountcode='" . $SelectedGLAccount . "' ORDER BY glaccountusers.userid ASC");
	if (DB_num_rows($Result)>0) {
		while($MyRow = DB_fetch_array($Result)) {
            $view = ($MyRow['canview'] == 1 ? '<span class="db-badge db-badge-green">Yes</span>' : '<span class="db-badge">No</span>');
            $upd = ($MyRow['canupd'] == 1 ? '<span class="db-badge db-badge-green">Yes</span>' : '<span class="db-badge">No</span>');
            $toggleLabel = ($MyRow['canupd'] == 1 ? __('Disable Update') : __('Enable Update'));
            $toggleVal = ($MyRow['canupd'] == 1 ? 0 : 1);
            
			echo '<tr>
				<td style="font-weight:700;">', $MyRow['userid'], ' <span style="font-weight:400; color:#64748b; font-size:0.8rem;">(', $MyRow['realname'], ')</span></td>
				<td>', $view, '</td>
				<td>', $upd, '</td>
				<td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                    <a class="db-btn db-btn-outline-sm" href="'.basename(__FILE__).'?SelectedGLAccount='.$SelectedGLAccount.'&SelectedUser='.$MyRow['userid'].'&ToggleUpdate='.$toggleVal.'">'.$toggleLabel.'</a>
                    <a class="db-btn db-btn-outline-sm" style="color:#dc2626" href="'.basename(__FILE__).'?SelectedGLAccount='.$SelectedGLAccount.'&SelectedUser='.$MyRow['userid'].'&delete=yes" onclick="return confirm(\''.__('Remove user access?').'\');">'.__('Un-authorise').'</a>
                </div></td></tr>';
		}
	} else {
		echo '<tr><td colspan="4" style="text-align:center; padding:2rem; color:#64748b;">', __('No users authorised for this account yet.'), '</td></tr>';
	}
    echo '</tbody></table></div></div></main>';

    // SIDEBAR: Add User
    echo '<aside class="db-aside">';
    $UsersResult = DB_query("SELECT userid, realname FROM www_users WHERE NOT EXISTS (SELECT userid FROM glaccountusers WHERE accountcode='".$SelectedGLAccount."' AND glaccountusers.userid=www_users.userid) ORDER BY userid");
    
    if (DB_num_rows($UsersResult) > 0) {
        echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-user-plus" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Add User Access') . '</h3></div>';
        echo '<div class="db-card-body"><form action="', basename(__FILE__), '" method="post"><input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" /><input name="SelectedGLAccount" type="hidden" value="', $SelectedGLAccount, '" />';
        echo '<div class="db-form-group"><label class="db-label">Access Permissions For:</label><select name="SelectedUser" class="db-select"><option value="">Select User...</option>';
        while ($UR = DB_fetch_array($UsersResult)) echo '<option value="'.$UR['userid'].'">'.$UR['userid'].' - '.$UR['realname'].'</option>';
        echo '</select></div>';
        echo '<button name="submit" type="submit" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> Grant Access</button>';
        echo '</form></div></div>';
    }

    echo '<div style="margin-top:1.5rem; display:flex; flex-direction:column; gap:0.5rem;">
            <a class="db-btn db-btn-outline" href="'.basename(__FILE__).'?Cancel"><i class="fas fa-reply-all"></i> Switch Account</a>
            <button class="db-btn db-btn-outline" onclick="window.print()"><i class="fas fa-print"></i> Print Authorisations</button>
            <a class="db-btn db-btn-outline" href="index.php?Application=GL"><i class="fas fa-home"></i> Return to GL</a>
          </div>';
    echo '</aside></div>';
}

echo '</div>'; // db-page

include(__DIR__ . '/includes/footer.php');
?>
