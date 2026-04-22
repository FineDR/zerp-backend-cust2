<?php

require(__DIR__ . '/includes/session.php');

$Title = __('User Roles');
$ViewTopic = 'SecuritySchema';
$BookMark = 'WWW_Access';
include(__DIR__ . '/includes/header.php');

// Inject premium Architect styles
echo '<style>
    :root {
        --primary: #059669;
        --primary-dark: #065f46;
        --primary-light: #ecfdf5;
        --page-padding: 40px;
    }
    .db-page {
        padding: 0 var(--page-padding);
        max-width: 1600px;
        margin: 0 auto;
    }
    .premium-header { 
        margin-bottom: 30px; 
        padding: 24px 30px; 
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        gap: 20px;
    }
    .db-bottom-layout {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 32px;
        align-items: start;
        padding-bottom: 50px;
    }
    .arch-card { 
        background: #ffffff; 
        border-radius: 16px; 
        border: 1px solid #e5e7eb; 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 32px;
    }
    .arch-card-header { 
        background: #f9fafb; 
        border-bottom: 1px solid #f3f4f6; 
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }
    .arch-card-title {
        font-size: 0.95rem; font-weight: 850; color: #064e3b; margin:0;
        display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .arch-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px;
        background: #059669; color: #ffffff; border: none;
        font-weight: 700; font-size: 0.85rem; cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .arch-btn:hover { background: #065f46; transform: translateY(-1px); }
    .arch-btn-secondary { background: #f3f4f6; color: #374151; }
    .arch-btn-secondary:hover { background: #e5e7eb; }
    
    .arch-badge { padding: 4px 10px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; }
    .arch-badge-success { background: #dcfce7; color: #166534; }
    .arch-badge-neutral { background: #f3f4f6; color: #4b5563; }
    .arch-badge-warn { background: #fef3c7; color: #92400e; }
    
    .arch-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px 40px;
    }
    .arch-form-label { display: block; font-size: 0.72rem; font-weight: 900; color: #064e3b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .arch-form-input { width: 100%; height: 48px; border-radius: 8px; border: 1.5px solid #d1fae5; padding: 0 16px; font-weight: 600; font-size: 0.95rem; transition: border-color 0.2s; }
    .arch-form-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

    .list-item {
        padding: 16px 20px; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit;
    }
    .list-item:hover { background: #f0fdf4; }
    .list-item.active { background: #ecfdf5; border-left: 4px solid #059669; padding-left: 16px; }

    .arch-table th { background: #f9fafb; color: #064e3b; font-weight: 800; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid #ecfdf5; padding: 15px 20px; text-align: left; }
    .arch-table td { padding: 15px 20px; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; font-weight: 600; }

    .section-divider {
        margin: 40px 0 24px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #065f46;
    }
    .section-title { font-size: 0.75rem; font-weight: 950; text-transform: uppercase; letter-spacing: 1.5px; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .premium-header { position: relative; border-radius: 0; margin-left: calc(-1 * var(--page-padding)); margin-right: calc(-1 * var(--page-padding)); }
        .db-col-aside { order: 2; }
        .db-col-main { order: 1; }
    }

    @media (max-width: 640px) {
        :root { --page-padding: 15px; }
        .premium-header-inner { flex-direction: column; align-items: flex-start; }
        .arch-form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>';

echo '<div class="db-page">
		<header class="premium-header">
			<div class="premium-header-inner">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-users-gear"></i> ' . __('System Security') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Access Roles') . '
                    </div>
                    <h1 style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
                </div>
                <div>
                    <a href="' . $RootPath . '/WWW_Users.php" class="arch-btn arch-btn-secondary">
                        <i class="fas fa-users"></i> ' . __('Back to Users') . '
                    </a>
                </div>
			</div>
		</header>';

if ($AllowDemoMode) {
	prnMsg(__('In demo mode security administration is disabled'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['SelectedRole'])) $SelectedRole = $_GET['SelectedRole'];
elseif (isset($_POST['SelectedRole'])) $SelectedRole = $_POST['SelectedRole'];

// Logic Handling
if (isset($_POST['submit']) OR isset($_GET['remove']) OR isset($_GET['add']) ) {
	$InputError = 0;
	if (isset($_POST['SecRoleName']) AND mb_strlen($_POST['SecRoleName'])<4){
		$InputError = 1;
		prnMsg(__('User role description must be at least 4 characters'),'error');
	}

	unset($SQL);
	if (isset($_POST['SecRoleName']) ){
		if (isset($SelectedRole)) {
			$SQL = "UPDATE securityroles SET secrolename = '" . $_POST['SecRoleName'] . "' WHERE secroleid = '".$SelectedRole . "'";
			$Msg = __('Role updated.');
		} else {
			$SQL = "INSERT INTO securityroles (secrolename) VALUES ('" . $_POST['SecRoleName'] ."')";
			$Msg = __('New role created.');
		}
		unset($_POST['SecRoleName']);
		unset($SelectedRole);
	} elseif (isset($SelectedRole) ) {
		$PageTokenId = $_GET['PageToken'];
		if ( isset($_GET['add']) ) {
			$SQL = "INSERT INTO securitygroups (secroleid, tokenid) VALUES ('".$SelectedRole."', '".$PageTokenId."' )";
			$Msg = __('Token assigned.');
		} elseif ( isset($_GET['remove']) ) {
			$SQL = "DELETE FROM securitygroups WHERE secroleid = '".$SelectedRole."' AND tokenid = '".$PageTokenId . "'";
			$Msg = __('Token removed.');
		}
		unset($_GET['add'], $_GET['remove'], $_GET['PageToken']);
	}
	if (isset($SQL) && $InputError != 1 ) {
		DB_query($SQL);
		prnMsg($Msg,'success');
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM www_users WHERE fullaccess='" . $_GET['SelectedRole'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg( __('Cannot delete role assigned to') . ' ' . $MyRow[0] . ' ' . __('users'),'warn');
	} else {
		DB_query("DELETE FROM securitygroups WHERE secroleid='" . $_GET['SelectedRole'] . "'");
		DB_query("DELETE FROM securityroles WHERE secroleid='" . $_GET['SelectedRole'] . "'");
		prnMsg(__('User role deleted'),'success');
		unset($SelectedRole);
	}
}

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-list-ul"></i> ' . __('Role Registry') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 250px); overflow-y: auto;">';

    $SQL = "SELECT secroleid, secrolename FROM securityroles ORDER BY secroleid";
    $Result = DB_query($SQL);
    while ($MyRow = DB_fetch_array($Result)) {
        $isActive = (isset($SelectedRole) && $SelectedRole == $MyRow['secroleid']) ? 'active' : '';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $MyRow['secroleid'] . '" class="list-item ' . $isActive . '">
                <div style="width:32px; height:32px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:800; font-size:0.75rem;">' . $MyRow['secroleid'] . '</div>
                <div style="flex:1;"><div style="font-weight: 800; font-size: 0.85rem; color:#111827;">' . $MyRow['secrolename'] . '</div></div>
                <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.7rem;"></i>
              </a>';
    }

    echo '      </div>
                <div style="padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus"></i> ' . __('Create New Role') . '
                    </a>
                </div>
            </div>

            <div class="arch-card">
                <div class="arch-card-header"><h3 class="arch-card-title"><i class="fas fa-tools"></i> ' . __('Governance Hub') . '</h3></div>
                <div class="db-card-body" style="padding: 10px 0;">
                    <a href="' . $RootPath . '/SecurityTokens.php" class="list-item" style="border:none;"><i class="fas fa-key" style="color:#6366f1;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Security Tokens') . '</span></a>
                    <a href="' . $RootPath . '/WWW_Users.php" class="list-item" style="border:none;"><i class="fas fa-user-shield" style="color:#ff5e5e;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('User Maintenance') . '</span></a>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

    if (isset($SelectedRole)) {
        $SQL = "SELECT secroleid, secrolename FROM securityroles WHERE secroleid='" . $SelectedRole . "'";
        $Result = DB_query($SQL);
        $MyRow = DB_fetch_array($Result);
        $_POST['SecRoleName'] = $MyRow['secrolename'];
        $formTitle = __('Role Master Profile');
        $formSubtitle = __('Configuring capabilities for ID') . ' ' . $SelectedRole;
    } else {
        $_POST['SecRoleName'] = '';
        $formTitle = __('Register Access Role');
        $formSubtitle = __('Define a new authorization group profile');
    }

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    if (isset($SelectedRole)) echo '<input type="hidden" name="SelectedRole" value="' . $SelectedRole . '" />';

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <div>
                    <h3 class="arch-card-title"><i class="fas fa-id-badge" style="color:var(--primary);"></i> ' . $formTitle . '</h3>
                    <div style="font-size: 0.75rem; color: #6b7280; font-weight:600; margin-top:5px;">' . $formSubtitle . '</div>
                </div>';
    
    if (isset($SelectedRole)) {
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $SelectedRole . '&amp;delete=1&amp;SecRoleName=' . urlencode($_POST['SecRoleName']) . '" class="arch-btn" style="background:#fee2e2; color:#dc2626;" onclick="return confirm(\'' . __('Delete this access role?') . '\');">
                <i class="fas fa-trash-alt"></i>
              </a>';
    }

    echo '  </div>
            <div class="db-card-body" style="padding:40px;">';

    // Partition 1: Identity
    echo '      <div class="section-divider" style="margin-top:0;">
                    <i class="fas fa-fingerprint"></i> <span class="section-title">' . __('Role Signature') . '</span>
                </div>
                <div class="arch-form-grid" style="grid-template-columns: 1fr;">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('User Role Description') . '</label>
                        <div style="display:flex; gap:16px;">
                            <input type="text" name="SecRoleName" class="arch-form-input" required maxlength="40" pattern=".{4,}" value="' . $_POST['SecRoleName'] . '" placeholder="' . __('e.g. Regional Accounts Manager') . '" style="flex:1;" />
                            <button type="submit" name="submit" class="arch-btn" style="height:48px; min-width:140px; justify-content:center;">
                                <i class="fas fa-save"></i> ' . (isset($SelectedRole) ? __('Update') : __('Create')) . '
                            </button>
                        </div>
                    </div>
                </div>';

    if (isset($SelectedRole)) {
        // Partition 2: Governance Status
        $SQLUsers = "SELECT COUNT(*) FROM www_users WHERE fullaccess='" . $SelectedRole . "'";
        $UsersCount = DB_fetch_row(DB_query($SQLUsers))[0];

        echo '  <div class="section-divider" style="margin-top:50px;">
                    <i class="fas fa-users-viewfinder"></i> <span class="section-title">' . __('Governance Status') . '</span>
                </div>
                <div style="display:flex; gap:24px; background:#f9fafb; padding:24px; border-radius:12px; border:1px solid #f3f4f6; align-items:center;">
                    <div style="width:56px; height:56px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:12px; font-size:1.4rem;">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div style="font-size:0.7rem; font-weight:900; color:#065f46; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">' . __('Assigned User Accounts') . '</div>
                        <div style="font-size:1.2rem; font-weight:850; color:#111827;">' . $UsersCount . ' ' . __('Active Users') . '</div>
                    </div>
                    <div style="margin-left:auto;">
                        ' . ($UsersCount > 0 ? '<span class="arch-badge arch-badge-warn"><i class="fas fa-lock"></i> Deletion Protected</span>' : '<span class="arch-badge arch-badge-success">Open for Deletion</span>') . '
                    </div>
                </div>';

        // Partition 3: Permission Matrix
        echo '  <div class="section-divider" style="margin-top:50px;">
                    <i class="fas fa-shield-halved"></i> <span class="section-title">' . __('Permission Matrix') . '</span>
                </div>
                <div style="overflow-x:auto;">
                    <table class="arch-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th style="width:150px;">' . __('Status') . '</th>
                                <th>' . __('Security Token') . '</th>
                                <th class="text-center">' . __('Action') . '</th>
                            </tr>
                        </thead>
                        <tbody>';

        $TokensResult = DB_query("SELECT tokenid, tokenname FROM securitytokens ORDER BY tokenid");
        $UsedResult = DB_query("SELECT tokenid FROM securitygroups WHERE secroleid='". $SelectedRole . "'");
        $TokensUsed = array();
        while($r = DB_fetch_row($UsedResult)) $TokensUsed[] = $r[0];

        while($r = DB_fetch_array($TokensResult)) {
            $isUsed = in_array($r['tokenid'], $TokensUsed);
            echo '<tr>
                    <td>' . ($isUsed ? '<span class="arch-badge arch-badge-success">' . __('Assigned') . '</span>' : '<span class="arch-badge arch-badge-neutral">' . __('Available') . '</span>') . '</td>
                    <td>
                        <div style="font-weight:750;">' . htmlspecialchars(__($r['tokenname']), ENT_QUOTES, 'UTF-8') . '</div>
                        <div style="font-size:0.7rem; color:#9ca3af; font-weight:600;">ID: ' . $r['tokenid'] . '</div>
                    </td>
                    <td class="text-center">';
            if ($isUsed) {
                echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $SelectedRole . '&amp;remove=1&amp;PageToken=' . $r['tokenid'] . '" class="arch-btn arch-btn-secondary" style="padding:6px 12px; color:#dc2626; font-size:0.75rem; background:transparent; border:1px solid #fee2e2;">
                        <i class="fas fa-minus-circle"></i> ' . __('Remove') . '
                      </a>';
            } else {
                echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedRole=' . $SelectedRole . '&amp;add=1&amp;PageToken=' . $r['tokenid'] . '" class="arch-btn" style="padding:6px 12px; font-size:0.75rem;">
                        <i class="fas fa-plus-circle"></i> ' . __('Add Token') . '
                      </a>';
            }
            echo '	</td>
                </tr>';
        }

        echo '          </tbody>
                    </table>
                </div>';
    } else {
        echo '<div style="padding: 60px; text-align: center; color: #065f46; border: 2px dashed #d1fae5; border-radius: 12px; background: #f0fdf4; margin-top:20px;">
                <i class="fas fa-users-cog" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3 style="font-weight: 850; margin-bottom: 10px;">Define authorization groups</h3>
                <p style="font-size: 0.9rem; font-weight: 600; color: #059669;">Select an existing user role from the sidebar to manage permissions or create a new authorization profile.</p>
              </div>';
    }

    echo '  </div>
          </div>
          </form>';

    echo '</main></div>'; // End Layout
echo '</div>'; // End Page

include(__DIR__ . '/includes/footer.php');
