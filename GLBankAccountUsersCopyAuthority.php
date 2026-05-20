<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Copy Authority of Bank Accounts');
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['ProcessCopyAuthority'])) {
	$InputError = 0;
	if ($_POST['FromUserID'] == $_POST['ToUserID']) {
		prnMsg(__('User FROM must be different from user TO'), 'error');
		$InputError = 1;
	}

	if ($InputError == 0) {
		DB_Txn_Begin();
		$SQL = "DELETE FROM bankaccountusers WHERE UPPER(userid) = UPPER('" . $_POST['ToUserID'] . "')";
		DB_query($SQL, '', '', true);
		prnMsg(__('Cleared previous bank authority for user') . ' ' . $_POST['ToUserID'], 'success');

		$SQL = "INSERT INTO bankaccountusers (userid, accountcode)
				SELECT '" . $_POST['ToUserID'] . "', accountcode
				FROM bankaccountusers
				WHERE UPPER(userid) = UPPER('" . $_POST['FromUserID'] . "')";
		DB_query($SQL, '', '', true);
		prnMsg(__('Copied bank account authority from') . ' ' . $_POST['FromUserID'] . ' ' . __('to') . ' ' . $_POST['ToUserID'], 'success');
		DB_Txn_Commit();
	}
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .db-header { margin-bottom: 2rem; text-align: center; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 550px; }
    .db-card-header { padding: 1.5rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 1rem; }
    .db-card-title { font-size: 1rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 2rem; }
    .db-form-group { margin-bottom: 1.5rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.75rem; }
    .db-select { width: 100%; padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.875rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.75rem; transition: all 0.2s; width: 100%; }
    .db-btn-primary { background: var(--db-primary); color: #fff; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-arrow { font-size: 1.5rem; color: #cbd5e1; margin: 0.5rem 0; display: flex; justify-content: center; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('Banking') . ' / ' . __('Tools') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-card">
        <div class="db-card-header"><i class="fas fa-university" style="color:var(--db-primary); font-size:1.5rem;"></i><h3 class="db-card-title">' . __('Bank Authority Cloning') . '</h3></div>
        <div class="db-card-body">
            <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-form-group">
                <label class="db-label">' . __('Source Personnel (Copy FROM)') . '</label>
                <select name="FromUserID" class="db-select" required>
                    <option value="">' . __('Select source user...') . '</option>';
                    $whereAdmin = ($_SESSION['AccessLevel'] == 8 ? "" : "WHERE fullaccess != '8'");
                    $users = DB_query("SELECT userid, realname FROM www_users $whereAdmin ORDER BY userid");
                    while($r = DB_fetch_array($users)) echo '<option value="'.$r['userid'].'">'.$r['userid'].' - '.$r['realname'].'</option>';
echo '          </select>
            </div>

            <div class="db-arrow"><i class="fas fa-chevron-down"></i></div>

            <div class="db-form-group">
                <label class="db-label">' . __('Target Personnel (Copy TO)') . '</label>
                <select name="ToUserID" class="db-select" required>
                    <option value="">' . __('Select destination user...') . '</option>';
                    DB_data_seek($users, 0);
                    while($r = DB_fetch_array($users)) echo '<option value="'.$r['userid'].'">'.$r['userid'].' - '.$r['realname'].'</option>';
echo '          </select>
                <div style="font-size:0.75rem; color:#dc2626; margin-top:0.6rem; font-weight:600;">⚠️ ' . __('All existing bank access for the target user will be replaced.') . '</div>
            </div>

            <button type="submit" name="ProcessCopyAuthority" class="db-btn db-btn-primary">
                <i class="fas fa-clone"></i> ' . __('Clone Bank Access Rights') . '
            </button>
            </form>
        </div>
      </div>';

echo '<div style="margin-top:2rem;"><a href="index.php?Application=GL" style="text-decoration:none; color:var(--db-primary); font-weight:700; font-size:0.875rem;"><i class="fas fa-chevron-left"></i> ' . __('Back to Management') . '</a></div>';

echo '</div>'; // db-page

include(__DIR__ . '/includes/footer.php');
?>
