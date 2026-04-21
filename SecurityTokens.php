<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Security Tokens');
$ViewTopic = 'SecuritySchema';
$BookMark = 'SecurityTokens';
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
    .arch-badge-primary { background: #ecfdf5; color: #059669; border:1px solid #d1fae5; }
    .arch-badge-danger { background: #fee2e2; color: #dc2626; }
    
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

    .dependency-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 14px; background: #f9fafb; border: 1px solid #e5e7eb;
        border-radius: 8px; font-size: 0.8rem; font-weight: 600; color: #374151;
        margin: 4px; border-left: 3px solid #059669;
    }

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
                        <i class="fas fa-user-shield"></i> ' . __('System Security') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Tokens') . '
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

$FixedTokens = array(0, 1, 2, 3, 5, 9, 11, 12, 15, 18, 19, 20);

if ($AllowDemoMode) {
	prnMsg(__('In demo mode security administration is disabled'), 'warn');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['TokenId'])) $_POST['TokenId'] = $_GET['TokenId'];
if (isset($_GET['TokenDescription'])) $_POST['TokenDescription'] = $_GET['TokenDescription'];

// Logic Handling
if (isset($_POST['Insert']) or isset($_POST['Update'])) {
	$InputError = 0;
	if (!is_numeric($_POST['TokenId'])) {
		prnMsg(__('Token ID must be a number'), 'error');
		$InputError = 1;
	}
	if (mb_strlen($_POST['TokenId']) == 0 || mb_strlen($_POST['TokenDescription']) == 0) {
		prnMsg(__('Token ID and Description are required'), 'error');
		$InputError = 1;
	}

	if (isset($_POST['Insert']) && $InputError == 0) {
		$Result = DB_query("SELECT tokenid FROM securitytokens WHERE tokenid='" . $_POST['TokenId'] . "'");
		if (DB_num_rows($Result) != 0) {
			prnMsg( __('This token ID is already in use') , 'warn');
		} else {
			DB_query("INSERT INTO securitytokens values('" . $_POST['TokenId'] . "', '" . $_POST['TokenDescription'] . "')");
			prnMsg(__('New security token inserted'), 'success');
			unset($_POST['TokenId'], $_POST['TokenDescription']);
		}
	} elseif (isset($_POST['Update']) && $InputError == 0) {
		DB_query("UPDATE securitytokens SET tokenname='" . $_POST['TokenDescription'] . "' WHERE tokenid='" . $_POST['TokenId'] . "'");
		prnMsg(__('Security token updated'), 'success');
		unset($_POST['TokenId'], $_POST['TokenDescription']);
	}
} elseif (isset($_GET['Delete'])) {
	$Result = DB_query("SELECT script FROM scripts WHERE pagesecurity='" . $_GET['TokenId'] . "'");
	if (DB_num_rows($Result) > 0) {
		prnMsg(__('Token is used by scripts and cannot be deleted'), 'error');
	} else {
		DB_query("DELETE FROM securitytokens WHERE tokenid='" . $_GET['TokenId'] . "'");
		prnMsg(__('Security token deleted'), 'success');
	}
	unset($_GET['TokenId']);
}

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-key"></i> ' . __('Token Registry') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 280px); overflow-y: auto;">';

    $Result = DB_query("SELECT tokenid, tokenname FROM securitytokens ORDER BY tokenid");
    while($MyRow = DB_fetch_array($Result)) {
        $isActive = (isset($_GET['TokenId']) && $_GET['TokenId'] == $MyRow['tokenid']) ? 'active' : '';
        $isFixed = in_array($MyRow['tokenid'], $FixedTokens);
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit=Yes&amp;TokenId=' . $MyRow['tokenid'] . '" class="list-item ' . $isActive . '">
                <div style="width:32px; height:32px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:800; font-size:0.75rem;">' . $MyRow['tokenid'] . '</div>
                <div style="flex:1;">
                    <div style="font-weight: 800; font-size: 0.85rem; color:#111827;">' . htmlspecialchars(__($MyRow['tokenname']), ENT_QUOTES, 'UTF-8') . '</div>
                    ' . ($isFixed ? '<span class="arch-badge arch-badge-warn" style="font-size:0.6rem; transform:scale(0.85); transform-origin:left; display:inline-block; margin-top:2px;">Reserved</span>' : '') . '
                </div>
                <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.7rem;"></i>
              </a>';
    }

    echo '      </div>
                <div style="padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus"></i> ' . __('Register Custom Token') . '
                    </a>
                </div>
            </div>

            <!-- Security Links -->
            <div class="arch-card">
                <div class="arch-card-header"><h3 class="arch-card-title"><i class="fas fa-lock-open"></i> ' . __('Governance') . '</h3></div>
                <div class="db-card-body" style="padding: 10px 0;">
                    <a href="' . $RootPath . '/WWW_Users.php" class="list-item" style="border:none;"><i class="fas fa-user-gear" style="color:#6366f1;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('User Maintenance') . '</span></a>
                    <a href="' . $RootPath . '/SecurityRoles.php" class="list-item" style="border:none;"><i class="fas fa-user-tag" style="color:#f59e0b;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Security Roles') . '</span></a>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

    if (isset($_GET['Edit'])) {
        $Result = DB_query("SELECT tokenid, tokenname FROM securitytokens WHERE tokenid='" . $_GET['TokenId'] . "'");
        $MyRow = DB_fetch_array($Result);
        $_POST['TokenId'] = $MyRow['tokenid'];
        $_POST['TokenDescription'] = $MyRow['tokenname'];
        $isFixed = in_array($_POST['TokenId'], $FixedTokens);

        $formTitle = __('Token Master Profile');
        $formSubtitle = __('Configuring security access for ID') . ' ' . $_POST['TokenId'];
    } else {
        if (!isset($_POST['TokenId'])) $_POST['TokenId'] = '';
        if (!isset($_POST['TokenDescription'])) $_POST['TokenDescription'] = '';
        $isFixed = false;
        $formTitle = __('Register Custom Token');
        $formSubtitle = __('Define a new access point for cross-script security');
    }

    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <div>
                    <h3 class="arch-card-title"><i class="fas fa-fingerprint" style="color:var(--primary);"></i> ' . $formTitle . '</h3>
                    <div style="font-size: 0.75rem; color: #6b7280; font-weight:600; margin-top:5px;">' . $formSubtitle . '</div>
                </div>';
    
    if (isset($_GET['Edit']) && !$isFixed) {
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=Yes&amp;TokenId=' . $_POST['TokenId'] . '" class="arch-btn" style="background:#fee2e2; color:#dc2626;" onclick="return confirm(\'' . __('Delete this custom security token?') . '\');">
                <i class="fas fa-trash-alt"></i>
              </a>';
    }

    echo '  </div>
            <div class="db-card-body" style="padding:40px;">';

    if ($isFixed) {
        echo '<div style="background: #fffbef; border: 1px solid #fef3c7; padding: 20px; border-radius: 12px; margin-bottom: 30px; display: flex; gap: 15px; align-items: center;">
                <div style="width:40px; height:40px; background:#fef3c7; color:#92400e; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:1.1rem;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div>
                    <div style="font-weight: 850; color: #92400e; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">' . __('System Reserved Token') . '</div>
                    <div style="font-size: 0.85rem; color: #b45309; font-weight:600;">' . __('This token ID is hardcoded in core logic. Modifications and deletions are restricted.') . '</div>
                </div>
              </div>';
    }

    echo '      <div class="section-divider" style="margin-top:0;">
                    <i class="fas fa-id-card"></i> <span class="section-title">' . __('Basic Classification') . '</span>
                </div>
                <div class="arch-form-grid">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Token ID (Internal)') . '</label>
                        ' . (isset($_GET['Edit']) ? 
                            '<div class="arch-form-input" style="background:#f9fafb; display:flex; align-items:center; color:#6b7280;">' . $_POST['TokenId'] . '<input type="hidden" name="TokenId" value="' . $_POST['TokenId'] . '" /></div>' : 
                            '<input type="number" name="TokenId" class="arch-form-input" required maxlength="4" value="' . $_POST['TokenId'] . '" placeholder="e.g. 50" />') . '
                    </div>
                </div>

                <div class="section-divider">
                    <i class="fas fa-signature"></i> <span class="section-title">' . __('Functional Description') . '</span>
                </div>
                <div class="arch-form-grid" style="grid-template-columns: 1fr;">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Registry Description') . '</label>
                        <input type="text" name="TokenDescription" class="arch-form-input" required maxlength="60" value="' . $_POST['TokenDescription'] . '" placeholder="' . __('Explain what this access point controls...') . '" ' . ($isFixed ? 'readonly style="background:#f9fafb;"' : '') . ' />
                        <div style="font-size: 0.72rem; color: #9ca3af; font-weight: 600; margin-top: 8px;">' . __('Describes which system functions this token allows a user or role to access.') . '</div>
                    </div>
                </div>';

    if (isset($_GET['Edit'])) {
        echo '<div class="section-divider">
                <i class="fas fa-diagram-project"></i> <span class="section-title">' . __('Dependency Inquiry') . '</span>
              </div>';
        
        // Inquiry logic
        $InqResult = DB_query("SELECT script FROM scripts WHERE pagesecurity='" . $_POST['TokenId'] . "'");
        $ScriptList = array();
        while($r = DB_fetch_array($InqResult)) $ScriptList[] = $r['script'];

        $InqRoles = DB_query("SELECT securityroles.secrolename FROM securitygroups INNER JOIN securityroles ON securitygroups.secroleid = securityroles.secroleid WHERE securitygroups.tokenid='" . $_POST['TokenId'] . "'");
        $RoleList = array();
        while($r = DB_fetch_array($InqRoles)) $RoleList[] = $r['secrolename'];

        echo '<div class="arch-form-grid">
                <div>
                    <label class="arch-form-label">' . __('Linked Scripts') . ' (' . count($ScriptList) . ')</label>
                    <div style="max-height: 150px; overflow-y: auto; background: #f9fafb; border-radius: 12px; padding: 12px;">';
        if (count($ScriptList) == 0) echo '<div style="font-size:0.8rem; color:#9ca3af;">' . __('No scripts directly linked.') . '</div>';
        foreach($ScriptList as $s) echo '<div class="dependency-chip"><i class="fas fa-file-code" style="font-size:0.7rem; opacity:0.6;"></i> ' . $s . '</div>';
        echo '      </div>
                </div>
                <div>
                    <label class="arch-form-label">' . __('Linked Security Roles') . ' (' . count($RoleList) . ')</label>
                    <div style="max-height: 150px; overflow-y: auto; background: #f9fafb; border-radius: 12px; padding: 12px;">';
        if (count($RoleList) == 0) echo '<div style="font-size:0.8rem; color:#9ca3af;">' . __('No roles linked.') . '</div>';
        foreach($RoleList as $s) echo '<div class="dependency-chip" style="border-left-color:#6366f1;"><i class="fas fa-user-tag" style="font-size:0.7rem; opacity:0.6;"></i> ' . $s . '</div>';
        echo '      </div>
                </div>
              </div>';
    }

    if (!$isFixed) {
        echo '  <div style="margin-top:50px; display:flex; justify-content:center;">
                    <button type="submit" name="' . (isset($_GET['Edit']) ? 'Update' : 'Insert') . '" class="arch-btn" style="padding:16px 80px; font-size:1.05rem; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);">
                        <i class="fas fa-check-double" style="margin-right:12px;"></i>
                        ' . (isset($_GET['Edit']) ? __('Update Profile') : __('Register Token')) . '
                    </button>
                </div>';
    }

    echo '  </div>
          </div>
          </form>';

    if (!isset($_GET['Edit'])) {
        echo '<div style="padding: 40px; text-align: center; color: #065f46; border: 2px dashed #d1fae5; border-radius: 12px; background: #f0fdf4; margin-top:20px;">
                <i class="fas fa-shield" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3 style="font-weight: 850; margin-bottom: 10px;">Scale your system permissions</h3>
                <p style="font-size: 0.9rem; font-weight: 600; color: #059669;">Security tokens represent specific access points that can be assigned to scripts and user roles for granular control.</p>
              </div>';
    }

    echo '</main></div>'; // End Layout
echo '</div>'; // End Page

include(__DIR__ . '/includes/footer.php');
