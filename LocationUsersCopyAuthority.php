<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Copy Authority of Locations from one user to another');

// Inject premium Architect Workspace styles
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { 
        margin-bottom: 30px; 
        padding: 20px 30px; 
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        margin: -40px -30px 30px -30px;
        border-radius: 0 0 16px 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
    }
	
	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-md);
		overflow: hidden;
        margin-bottom: 30px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
	}
    .db-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 24px;
	}
	.db-card-title {
		font-size: 0.9rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 10px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 30px; }
	
    field {
        display: block;
        margin-bottom: 24px;
    }
    field label {
        font-size: 0.72rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 8px;
        opacity: 0.7;
    }
    field select {
        width: 100%; border-radius: 10px; height: 52px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 20px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23059669\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'%23059669\'%3E%3Cpath stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M19 9l-7 7-7-7\'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
    }
    field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    
	.architect-btn {
		display: inline-flex; align-items: center; gap: 10px;
		padding: 14px 28px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.9rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.35); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 320px 1fr; 
        gap: 40px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .info-card {
        background: #f0fdf4;
        border: 1px solid #d1fae5;
        border-radius: 16px;
        padding: 24px;
    }
    .info-title {
        font-size: 0.75rem;
        font-weight: 900;
        color: #065f46;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .info-text {
        font-size: 0.88rem;
        color: #374151;
        line-height: 1.6;
        margin: 0;
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header { margin: -20px -15px 20px -15px; border-radius: 0; }
    }
    @media (max-width: 768px) {
        .premium-header-inner { flex-direction: column; align-items: flex-start; gap: 15px; }
        .architect-btn { width: 100%; justify-content: center; }
    }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['ProcessCopyAuthority'])) {

	$InputError = 0;

	if ($_POST['FromUserID'] == $_POST['ToUserID']) {
		prnMsg(__('User FROM must be different from user TO'), 'error');
		$InputError = 1;
	}

	if ($InputError == 0) {// no input errors
		DB_Txn_Begin();

		$SQL = "DELETE FROM locationusers WHERE UPPER(userid) = UPPER('" . $_POST['ToUserID'] . "')";
		$ErrMsg = __('The SQL to delete the auhority in locationusers record failed');
		$Result = DB_query($SQL, $ErrMsg, '', true);
		prnMsg(__('Deleting the previous authority to view / update the Locations of user') . ' ' . $_POST['ToUserID'], 'success');

		$SQL = "INSERT INTO locationusers (userid, loccode, canview, canupd)
				SELECT '" . $_POST['ToUserID'] . "', loccode, canview, canupd
				FROM locationusers
				WHERE UPPER(userid) = UPPER('" . $_POST['FromUserID'] . "')";

		$ErrMsg = __('The SQL to insert the auhority in locationusers record failed');
		$Result = DB_query($SQL, $ErrMsg, '', true);
		prnMsg(__('Copied the authority to view / update the Locations from user') . ' ' . $_POST['FromUserID'] . ' ' . __('to user') . ' ' . $_POST['ToUserID'], 'success');

		DB_Txn_Commit();

	}//only do the stuff above if  $InputError==0
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div>
					<div style="font-size: 0.72rem; font-weight: 850; color: #6b7280; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1.2px; opacity: 0.6;">
						<i class="fas fa-shield-alt"></i> ' . __('Security') . ' <i class="fas fa-chevron-right" style="font-size: 0.5rem;"></i> ' . __('Permissions') . '
					</div>
					<h1 style="font-size: 1.8rem; font-weight: 950; letter-spacing: -1px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div>
                     <button type="submit" form="copy-authority-form" name="ProcessCopyAuthority" class="architect-btn">
                        <i class="fas fa-sync-alt"></i> ' . __('Process Transfer') . '
                    </button>
                </div>
			</div>
		</div>

        <form id="copy-authority-form" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <aside class="db-sidebar">
                    <div class="info-card">
                        <div class="info-title"><i class="fas fa-info-circle"></i> ' . __('Authority Synchronization') . '</div>
                        <p class="info-text">' . __('This tool allows you to clone location access permissions from one user to another. Useful for onboarding new employees or mirroring roles.') . '</p>
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #d1fae5; font-size: 0.75rem; color: #059669; font-weight: 700;">
                            <i class="fas fa-exclamation-triangle"></i> ' . __('Warning: This will overwrite ALL existing location authorities for the target user.') . '
                        </div>
                    </div>
                </aside>

                <main class="db-main">
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-users-cog"></i> ' . __('Select Users') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">';

                                // Source User
                                echo '<field>
                                        <label>' . __('Copy FROM User') . ':</label>
                                        <select name="FromUserID" required>';
                                        
                                        if ($_SESSION['AccessLevel'] == 8) {
                                            $Result = DB_query("SELECT userid, realname FROM www_users ORDER BY userid");
                                        } else {
                                            $Result = DB_query("SELECT userid, realname FROM www_users WHERE fullaccess != '8' ORDER BY userid");
                                        }
                                        
                                        echo '<option value="">' . __('Select source user...') . '</option>';
                                        while ($MyRow = DB_fetch_array($Result)) {
                                            echo '<option value="' . $MyRow['userid'] . '">' . $MyRow['userid'] . ' - ' . $MyRow['realname'] . '</option>';
                                        }
                                echo '  </select>
                                      </field>';

                                // Target User
                                echo '<field>
                                        <label>' . __('Copy TO User') . ':</label>
                                        <select name="ToUserID" required>';
                                        
                                        // Reset pointer or re-run query for second select
                                        DB_data_seek($Result, 0);
                                        
                                        echo '<option value="">' . __('Select target user...') . '</option>';
                                        while ($MyRow = DB_fetch_array($Result)) {
                                            echo '<option value="' . $MyRow['userid'] . '">' . $MyRow['userid'] . ' - ' . $MyRow['realname'] . '</option>';
                                        }
                                echo '  </select>
                                      </field>';

echo '                      </div>
                        </div>
                    </div>
                </main>
            </div>
        </form>
      </div>';

include(__DIR__ . '/includes/footer.php');
