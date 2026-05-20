<?php

require(__DIR__ . '/includes/session.php');
$Title = __('Audit Trail Inquiry');
$ViewTopic = 'Setup';
$BookMark = 'AuditTrail';

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
		box-shadow: var(--shadow-sm);
		overflow: hidden;
        margin-bottom: 24px;
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 14px 20px;
        display: flex; justify-content: space-between; align-items: center;
	}
	.db-card-title {
		font-size: 0.7rem;
		font-weight: 900;
		color: #066e96;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
    .db-card-body { padding: 20px; }
	
    field { display: block; margin-bottom: 16px; }
    field label {
        font-size: 0.6rem; text-transform: uppercase; font-weight: 900; letter-spacing: 0.8px; 
        color: #066e96; display: block; margin-bottom: 6px; opacity: 0.75;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 42px; font-weight: 600; border: 1px solid #cceeff;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { border-color: #09aae8; outline: none; box-shadow: 0 0 0 4px rgba(9, 170, 232, 0.1); }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #09aae8; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(9, 170, 232, 0.2);
		cursor: pointer; font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #0788ba; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(9, 170, 232, 0.3); }

    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 320px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-container { 
        background: #ffffff; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: var(--shadow-sm); overflow: hidden;
    }
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.6rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; padding: 14px 20px; position: sticky; top: 0; z-index: 10; }
    table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; color: #334155; vertical-align: top; line-height: 1.5; }
    
    .row-insert { background-color: rgba(16, 185, 129, 0.03); }
    .row-update { background-color: rgba(245, 158, 11, 0.03); }
    .row-delete { background-color: rgba(239, 68, 68, 0.03); }

    .stat-bar { 
        display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; 
    }
    .stat-card { 
        background: #ffffff; padding: 18px 24px; border-radius: 16px; border: 1px solid #e5e7eb; 
        display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm); 
    }
    .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
    .stat-val { font-size: 1.4rem; font-weight: 900; color: #066e96; letter-spacing: -0.5px; line-height: 1; }
    .stat-lbl { font-size: 0.65rem; font-weight: 850; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

    .sql-code { 
        font-family: "JetBrains Mono", "Courier New", monospace; font-size: 0.72rem; color: #475569; 
        background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; 
        word-break: break-all; max-height: 80px; overflow-y: auto; opacity: 0.9;
    }

    .preset-btn { 
        padding: 6px 12px; border-radius: 8px; background: #f0f9ff; color: #09aae8; 
        font-size: 0.65rem; font-weight: 800; border: 1px solid #cceeff; cursor: pointer; 
        transition: all 0.2s; margin-right: 5px; text-transform: uppercase;
    }
    .preset-btn:hover { background: #09aae8; color: #fff; }

    @media (max-width: 1200px) {
        .stat-bar { grid-template-columns: 1fr 1fr; }
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .db-bottom-layout main { order: 1; }
        .db-bottom-layout aside { order: 2; }
    }
    @media (max-width: 768px) {
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: left; gap: 15px; }
        .header-actions { width: 100%; }
        .architect-btn { width: 100%; }
        .stat-bar { grid-template-columns: 1fr; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

// Filter Logic Initialization
if (!isset($_POST['FromDate'])) { 
    $_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d') - 30, date('Y'))); 
}
if (!isset($_POST['ToDate'])) { 
    $_POST['ToDate'] = date($_SESSION['DefaultDateFormat']); 
}

// Data Processing
$SQL = "SELECT transactiondate, userid, querystring FROM audittrail WHERE transactiondate >= '" . FormatDateForSQL($_POST['FromDate']) . " 00:00:00' AND transactiondate <= '" . FormatDateForSQL($_POST['ToDate']) . " 23:59:59'";
if (isset($_POST['User']) && $_POST['User'] != 'ALL') { $SQL .= " AND userid = '" . $_POST['User'] . "'"; }
if (!empty($_POST['Table'])) { $SQL .= " AND querystring LIKE '%" . $_POST['Table'] . "%'"; }
$SQL .= " ORDER BY transactiondate DESC";

$Result = DB_query($SQL);
$TotalCount = DB_num_rows($Result);
$Inserts = 0; $Updates = 0; $Deletes = 0;
$Rows = array();
while ($row = DB_fetch_array($Result)) {
    if (stripos($row['querystring'], 'INSERT') !== false) { $Inserts++; }
    elseif (stripos($row['querystring'], 'DELETE') !== false) { $Deletes++; }
    else { $Updates++; }
    $Rows[] = $row;
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
                        ' . __('Audit Reports') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #066e96; margin: 0; line-height: 1.1;">' . __('Audit Trail Explorer') . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="AuditFilterForm" name="Go" class="architect-btn">
                        <i class="fas fa-sync-alt"></i> ' . __('Refresh Inquiry') . '
                    </button>
                </div>
			</div>
		</div>

        <!-- Metric Bar -->
        <div class="stat-bar">
            <div class="stat-card">
                <div class="stat-icon" style="background:#f1f5f9; color:#475569;"><i class="fas fa-database"></i></div>
                <div><div class="stat-val">' . $TotalCount . '</div><div class="stat-lbl">' . __('Total Trails') . '</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0f9ff; color:#09aae8;"><i class="fas fa-plus-circle"></i></div>
                <div><div class="stat-val">' . $Inserts . '</div><div class="stat-lbl">' . __('Inserts') . '</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fffbeb; color:#f59e0b;"><i class="fas fa-edit"></i></div>
                <div><div class="stat-val">' . $Updates . '</div><div class="stat-lbl">' . __('Updates') . '</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef2f2; color:#ef4444;"><i class="fas fa-trash-alt"></i></div>
                <div><div class="stat-val">' . $Deletes . '</div><div class="stat-lbl">' . __('Deletes') . '</div></div>
            </div>
        </div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">
                <div class="table-container">
                    <div class="db-card-header" style="background:transparent; border-bottom:1px solid #f1f5f9;">
                        <h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Activity Log Feed') . '</h3>
                        <div style="font-size:0.65rem; color:#64748b; font-weight:700;">' . sprintf(__('Showing last %s operations'), count($Rows)) . '</div>
                    </div>
                    <div class="table-responsive" style="max-height: 75vh;">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th style="width: 170px;">' . __('Date & Time') . '</th>
                                    <th style="width: 140px;">' . __('Identity') . '</th>
                                    <th style="width: 100px;">' . __('Operation') . '</th>
                                    <th>' . __('Detailed SQL Statement') . '</th>
                                </tr>
                            </thead>
                            <tbody>';
                            
                            if (empty($Rows)) {
                                echo '<tr><td colspan="4" style="text-align:center; padding: 100px 20px; color:#94a3b8;"><i class="fas fa-magnifying-glass" style="font-size:2.5rem; display:block; margin-bottom:20px; opacity:0.3;"></i>' . __('No audit trail records found for the selected criteria.') . '</td></tr>';
                            }
                            
                            foreach ($Rows as $row) {
                                $op = 'UPDATE'; $rowCls = 'row-update'; $dotColor = '#f59e0b'; $icon = 'fa-edit';
                                if (stripos($row['querystring'], 'INSERT') !== false) { $op = 'INSERT'; $rowCls = 'row-insert'; $dotColor = '#09aae8'; $icon = 'fa-plus-circle'; }
                                elseif (stripos($row['querystring'], 'DELETE') !== false) { $op = 'DELETE'; $rowCls = 'row-delete'; $dotColor = '#ef4444'; $icon = 'fa-trash-alt'; }
                                
                                echo '<tr class="' . $rowCls . '">
                                        <td style="font-weight: 750; color: #1e293b;">
                                            <div style="font-size:0.85rem;">' . date($_SESSION['DefaultDateFormat'], strtotime($row['transactiondate'])) . '</div>
                                            <div style="font-size:0.7rem; color:#64748b; font-weight:500;">' . date('H:i:s', strtotime($row['transactiondate'])) . '</div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 850; color: #066e96; display: flex; align-items: center; gap: 6px;">
                                                <i class="fas fa-user-circle" style="opacity:0.4;"></i> ' . $row['userid'] . '
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <span style="width:8px; height:8px; border-radius:50%; background:' . $dotColor . ';"></span>
                                                <span style="font-weight: 900; font-size:0.65rem; color:' . $dotColor . ';">' . $op . '</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="sql-code">' . htmlspecialchars($row['querystring']) . '</div>
                                        </td>
                                      </tr>';
                            }
                            
                echo '      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="AuditFilterForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="db-card" style="position: sticky; top: 100px;">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-filter"></i> ' . __('Inquiry Filters') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <div style="margin-bottom: 20px;">
                                <label style="font-size:0.6rem; font-weight:900; color:#64748b; text-transform:uppercase; margin-bottom:8px; display:block;">' . __('Quick Presets') . '</label>
                                <button type="button" class="preset-btn" onclick="document.getElementsByName(\'FromDate\')[0].value=\'' . date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d') - 7, date('Y'))) . '\'">' . __('7 Days') . '</button>
                                <button type="button" class="preset-btn" onclick="document.getElementsByName(\'FromDate\')[0].value=\'' . date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), date('d') - 30, date('Y'))) . '\'">' . __('30 Days') . '</button>
                            </div>

                            <field>
                                <label>' . __('From Date') . '</label>
                                <input type="text" name="FromDate" class="date" required value="' . $_POST['FromDate'] . '" />
                            </field>
                            <field>
                                <label>' . __('To Date') . '</label>
                                <input type="text" name="ToDate" class="date" required value="' . $_POST['ToDate'] . '" />
                            </field>

                            <field>
                                <label>' . __('Operator / User') . '</label>
                                <select name="User">
                                    <option value="ALL">' . __('All Active Users') . '</option>';
                                    $Users = DB_query("SELECT userid, realname FROM www_users");
                                    while ($u = DB_fetch_array($Users)) {
                                        echo '<option value="' . $u['userid'] . '" ' . (isset($_POST['User']) && $_POST['User'] == $u['userid'] ? 'selected' : '') . '>' . $u['realname'] . '</option>';
                                    }
                echo '          </select>
                            </field>

                            <field>
                                <label>' . __('Search Database Table') . '</label>
                                <input type="text" name="Table" placeholder="' . __('e.g. stockmaster') . '" value="' . ($_POST['Table'] ?? '') . '" />
                            </field>

                            <div style="margin-top: 25px; pt-15px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                <button type="submit" name="Go" class="architect-btn" style="width: 100%;">
                                    <i class="fas fa-search"></i> ' . __('Apply Inquiry') . '
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
