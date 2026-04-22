<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Users currently logged in');
$ViewTopic = 'Setup';
$BookMark = '';

// Inject premium Architect Workspace styles with enhanced responsiveness
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
        max-width: 1400px;
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
        flex-wrap: wrap;
        gap: 10px;
	}
	.db-card-title {
		font-size: 0.85rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 20px; }
	
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }

    .user-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: #d1fae5; color: #059669;
        display: flex; align-items: center; justify-content: center; font-weight: 950; font-size: 0.75rem;
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
    @media (max-width: 600px) {
        .premium-header { padding: 15px; margin-bottom: 20px; }
        h1 { font-size: 1.4rem !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

$SQL = "SELECT sessionid,
				sessions.userid,
				logintime,
				realname,
				email,
				phone,
				scripttime,
				script
			FROM sessions
			INNER JOIN www_users
			ON www_users.userid = sessions.userid ORDER BY scripttime DESC";
$Result = DB_query($SQL);
$UserCount = DB_num_rows($Result);

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Monitoring') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Active Sessions') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" class="architect-btn">
                        <i class="fas fa-sync-alt"></i> ' . __('Refresh Data') . '
                    </a>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-signal"></i> ' . __('Live User Connections') . '</h3>
                        <span style="background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 800;">' . $UserCount . ' ' . __('Online') . '</span>
                    </div>';

                    if ($UserCount > 0) {
                        echo '<div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('User') . '</th>
                                            <th>' . __('Contact') . '</th>
                                            <th>' . __('Login Time') . '</th>
                                            <th>' . __('Current Task') . '</th>
                                            <th>' . __('Last Sync') . '</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        while ($MyRow = DB_fetch_array($Result)) {
                            $Initial = mb_strtoupper(mb_substr($MyRow['realname'], 0, 1));
                            echo '<tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div class="user-avatar">' . $Initial . '</div>
                                            <div>
                                                <div style="font-weight: 700; color: #1f2937;">' . $MyRow['realname'] . '</div>
                                                <div style="font-size: 0.75rem; color: #64748b;">ID: ' . $MyRow['userid'] . '</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-size: 0.8rem; color: #374151;">' . $MyRow['email'] . '</div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">' . $MyRow['phone'] . '</div>
                                    </td>
                                    <td style="font-size: 0.8rem; font-weight: 600; color: #475569;">' . ConvertSQLDateTime($MyRow['logintime']) . '</td>
                                    <td>
                                        <span style="display: inline-block; padding: 4px 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.75rem; color: #334155;">' . $MyRow['script'] . '</span>
                                    </td>
                                    <td style="font-size: 0.8rem; color: #64748b;">' . ConvertSQLDateTime($MyRow['scripttime']) . '</td>
                                </tr>';
                        }
                        echo '      </tbody>
                                </table>
                            </div>';
                    } else {
                        echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No active users found.') . '</div>';
                    }
echo '          </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <div class="db-card" style="background: #f0fdf4; border-color: #d1fae5;">
                    <div class="db-card-body">
                        <div style="font-size: 0.7rem; font-weight: 900; color: #065f46; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i> ' . __('Session Monitoring') . '
                        </div>
                        <p style="font-size: 0.82rem; color: #374151; line-height: 1.6; margin: 0;">' . __('This screen provides a real-time view of all users currently authenticated with the ERP system.') . '</p>
                        <ul style="padding: 0; margin: 15px 0 0 0; list-style: none;">
                            <li style="font-size: 0.78rem; color: #065f46; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check" style="font-size: 0.6rem;"></i> ' . __('Track user activity') . '
                            </li>
                            <li style="font-size: 0.78rem; color: #065f46; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check" style="font-size: 0.6rem;"></i> ' . __('Identify idle sessions') . '
                            </li>
                            <li style="font-size: 0.78rem; color: #065f46; display: flex; align-items: center; gap: 10px;">
                                <i class="fas fa-check" style="font-size: 0.6rem;"></i> ' . __('Monitor system load') . '
                            </li>
                        </ul>
                    </div>
                </div>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
