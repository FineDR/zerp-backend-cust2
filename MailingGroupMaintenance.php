<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Mailing Group Maintenance');
$ViewTopic = 'Setup';
$BookMark = 'MailingGroupMaintenance';

// Inject premium Architect Workspace styles
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: 20px 15px; background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; box-sizing: border-box; }
	
	.premium-header { 
        margin: -20px -15px 30px -15px;
        padding: 20px; 
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 100%;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.65rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
        display: flex; align-items: center; gap: 8px; text-transform: uppercase; 
        letter-spacing: 1px; opacity: 0.6;
    }
    .breadcrumb-wrap a { color: inherit; text-decoration: none; }
    .breadcrumb-wrap a:hover { text-decoration: underline; opacity: 1; }

	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-md);
		overflow: hidden;
        margin-bottom: 30px;
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
	}
	.db-card-title {
		font-size: 0.8rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 25px; }
	
    field {
        display: block;
        margin-bottom: 18px;
    }
    field label {
        font-size: 0.62rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-primary { background: #d1fae5; color: #065f46; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['Enter'])) {
	$MailGroup = strtolower(trim($_POST['MailGroup']));
	if (!empty($MailGroup) and mb_strlen($MailGroup) <= 100 and !ContainsIllegalCharacters($MailGroup)) {
		DB_query("INSERT INTO mailgroups (groupname) VALUES ('" . $MailGroup . "')");
	} else {
		prnMsg(__('Invalid Mail Group name'), 'error');
	}
}

if (isset($_GET['Add']) and isset($_GET['UserId']) and isset($_GET['GroupName'])) {
	$UserId = $_GET['UserId']; $GroupName = trim($_GET['GroupName']); $GroupId = (int)$_GET['GroupId'];
	DB_query("INSERT INTO mailgroupdetails (groupname, userid) VALUES ('" . $GroupName . "', '" . $UserId . "')");
}

if (isset($_GET['Remove']) and isset($_GET['UserId']) and isset($_GET['GroupName'])) {
	$UserId = $_GET['UserId']; $GroupName = trim($_GET['GroupName']); $GroupId = (int)$_GET['GroupId'];
	DB_query("DELETE FROM mailgroupdetails WHERE userid = '" . $UserId . "' AND groupname = '" . $GroupName . "'");
}

if (isset($_GET['Delete'])) {
	$id = (int)$_GET['Id'];
	$Check = DB_query("SELECT userid FROM mailgroupdetails INNER JOIN mailgroups ON mailgroupdetails.groupname=mailgroups.groupname WHERE id='" . $id . "'");
	if (DB_num_rows($Check) == 0) {
		DB_query("DELETE FROM mailgroups WHERE id = '" . $id . "'");
		prnMsg(__('Group deleted'), 'success');
	} else {
		prnMsg(__('Remove associated users first'), 'error');
	}
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Setup') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Mailing Groups') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="MailGroups" name="Enter" class="architect-btn">
                        <i class="fas fa-plus-circle"></i> ' . __('Register New Group') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

                if (isset($_GET['Edit'])) {
                    $GroupId = (int)$_GET['GroupId'];
                    $GroupName = trim($_GET['GroupName']);
                    echo GetUsersArchitect($GroupId, $GroupName);
                } else {
                    echo GetMailGroupsArchitect();
                }

echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="MailGroups" action="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'). '" method="post">
                    <input type="hidden" name="FormID" value="'. $_SESSION['FormID']. '" />
                    <input type="hidden" name="Clean" value="1" />
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-users-cog"></i> ' . __('Quick Create') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Group Name') . '</label>
                                <input type="text" required name="MailGroup" maxlength="100" placeholder="' . __('e.g. Sales Team') . '" autofocus />
                            </field>
                            <button type="submit" name="Enter" class="architect-btn" style="width: 100%;">
                                <i class="fas fa-check"></i> ' . __('Create Group') . '
                            </button>
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');

function GetMailGroupsArchitect() {
	$SQL = "SELECT groupname, id FROM mailgroups ORDER BY groupname";
	$Result = DB_query($SQL);
    $html = '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Existing Mailing Groups') . '</h3>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>' . __('Group Name') . '</th>
                                <th style="width: 150px; text-align: right;">' . __('Actions') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
    if (DB_num_rows($Result) == 0) {
        $html .= '<tr><td colspan="2" style="text-align:center; padding: 40px; color: #64748b;">' . __('No mailing groups defined yet.') . '</td></tr>';
    }
    while ($MyRow = DB_fetch_array($Result)) {
        $html .= '<tr>
                    <td style="font-weight: 700; color: #064e3b;">' . $MyRow['groupname'] . '</td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?GroupId=' . $MyRow['id'] . '&Edit=1&GroupName=' . $MyRow['groupname'] . '" style="color: #059669; font-weight:700; margin-right:15px; text-decoration:none;"><i class="fas fa-user-edit"></i> ' . __('Manage') . '</a>
                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Id=' . $MyRow['id'] . '&Delete=1" style="color: #ef4444;" onclick="return confirm(\'' . __('Delete this group?') . '\');"><i class="fas fa-trash-alt"></i></a>
                    </td>
                </tr>';
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}

function GetUsersArchitect($GroupId, $GroupName) {
	$SQL = "SELECT userid FROM mailgroups INNER JOIN mailgroupdetails ON mailgroups.groupname=mailgroupdetails.groupname WHERE mailgroups.id = '" . $GroupId . "'";
	$Result = DB_query($SQL);
	$Assigned = array();
	while ($MyRow = DB_fetch_array($Result)) { $Assigned[] = $MyRow['userid']; }

	$SQL = "SELECT userid, realname FROM www_users ORDER BY realname";
	$Result = DB_query($SQL);
    
    $html = '<div style="margin-bottom: 20px;">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="color: #64748b; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fas fa-arrow-left"></i> ' . __('Back to Groups') . '
                </a>
            </div>
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-users-viewfinder"></i> ' . __('Group Membership') . ': ' . $GroupName . '</h3>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>' . __('User Identity') . '</th>
                                <th>' . __('Status') . '</th>
                                <th style="width: 150px; text-align: right;">' . __('Membership Action') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
    while ($MyRow = DB_fetch_array($Result)) {
        $isAssigned = in_array($MyRow['userid'], $Assigned);
        $html .= '<tr>
                    <td>
                        <div style="font-weight: 700; color: #064e3b;">' . $MyRow['realname'] . '</div>
                        <div style="font-size: 0.75rem; color: #64748b; font-weight: 500;">@' . $MyRow['userid'] . '</div>
                    </td>
                    <td>' . ($isAssigned ? '<span class="badge badge-primary">' . __('Assigned') . '</span>' : '<span style="color: #cbd5e1; font-weight: 700; font-size: 0.75rem; text-transform: uppercase;">' . __('Available') . '</span>') . '</td>
                    <td style="text-align: right;">';
        if ($isAssigned) {
            $html .= '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?UserId=' . $MyRow['userid'] . '&GroupName=' . $GroupName . '&Remove=1&GroupId=' . $GroupId . '&Edit=1" style="color:#ef4444; font-weight:700; text-decoration:none;" onclick="return confirm(\'' . __('Remove user?') . '\');"><i class="fas fa-user-minus"></i> ' . __('Remove') . '</a>';
        } else {
            $html .= '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?UserId=' . $MyRow['userid'] . '&Add=1&GroupName=' . $GroupName . '&GroupId=' . $GroupId . '&Edit=1" style="color:#059669; font-weight:700; text-decoration:none;"><i class="fas fa-user-plus"></i> ' . __('Add to Group') . '</a>';
        }
        $html .= '</td></tr>';
    }
    $html .= '</tbody></table></div></div>';
    return $html;
}
