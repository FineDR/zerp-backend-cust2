<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Configure Dashboard Scripts');
$ViewTopic = 'Dashboard';
$BookMark = 'Configure';

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
	
    field {
        display: block;
        margin-bottom: 20px;
    }
    field label {
        font-size: 0.7rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 8px;
        opacity: 0.7;
    }
    field select, field input[type="text"] {
        width: 100%; border-radius: 10px; height: 48px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 15px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field select:focus, field input:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    
    .fieldtext {
        font-weight: 700; color: #1f2937; padding: 10px 0; border-bottom: 1px dashed #e5e7eb; margin-bottom: 15px; font-size: 0.9rem;
    }

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
        grid-template-columns: 1fr 350px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 1px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }

    .action-link { 
        color: #059669; font-weight: 700; text-decoration: none; 
        display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; 
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
    @media (max-width: 600px) {
        .premium-header { margin: -20px -15px 20px -15px; padding: 15px; }
        .db-card-header { padding: 12px 15px; }
        .db-card-body { padding: 15px; }
        h1 { font-size: 1.4rem !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_GET['Delete'])) {
	$SQL = "SELECT scripts FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$SQL = "DELETE FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$SQL = "DELETE FROM scripts WHERE script='" . $MyRow['scripts'] . "'";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(__('The script was successfully removed'), 'success');
	} else {
		prnMsg(__('There was a peoblem removing the script'), 'error');
	}
}

if (isset($_POST['Update'])) {
	$SQL = "SELECT scripts FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);

	$SQL = "UPDATE dashboard_scripts SET pagesecurity='" . $_POST['PageSecurity'] . "',
										description='" . $_POST['Description'] . "'
									WHERE id='" . $_POST['ID'] . "'";
	$Result = DB_query($SQL);
	$SQL = "UPDATE scripts SET pagesecurity='" . $_POST['PageSecurity'] . "',
								description='" . $_POST['Description'] . "'
							WHERE script='" . $MyRow['scripts'] . "'";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(__('The script was successfully updated'), 'success');
	} else {
		prnMsg(__('There was a peoblem updating the script'), 'error');
	}
}

if (isset($_POST['Insert'])) {
	$SQL = "INSERT INTO dashboard_scripts (id,
											scripts,
											pagesecurity,
											description
										) VALUES (
											NULL,
											'" . $_POST['Script'] . "',
											'" . $_POST['PageSecurity'] . "',
											'" . $_POST['Description'] . "'
										)";
	$Result = DB_query($SQL);
	$SQL = "INSERT INTO scripts (script,
								pagesecurity,
								description
							) VALUES (
								'" . $_POST['Script'] . "',
								'" . $_POST['PageSecurity'] . "',
								'" . $_POST['Description'] . "'
							)";
	$Result = DB_query($SQL);
	if (DB_error_no() == 0) {
		prnMsg(__('The script was successfully inserted'), 'success');
	} else {
		prnMsg(__('There was a peoblem inserting the script'), 'error');
	}
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=Dashboard">' . __('Dashboard') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Settings') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="main-form" name="' . (isset($_GET['Edit']) ? 'Update' : 'Insert') . '" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($_GET['Edit']) ? __('Update Changes') : __('Register Widget')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Registered Components') . '</h3>
                    </div>';

                    $SQL = "SELECT id, scripts, tokenname, description FROM dashboard_scripts
                            INNER JOIN securitytokens ON dashboard_scripts.pagesecurity=securitytokens.tokenid ORDER BY scripts";
                    $Result = DB_query($SQL);

                    if (DB_num_rows($Result) > 0) {
                        echo '<div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Filename') . '</th>
                                            <th>' . __('Description') . '</th>
                                            <th>' . __('Security') . '</th>
                                            <th style="width: 80px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        $ScriptArray = array();
                        while ($MyRow = DB_fetch_array($Result)) {
                            $ScriptArray[] = $MyRow['scripts'];
                            echo '<tr>
                                    <td style="font-weight: 700;">' . $MyRow['scripts'] . '</td>
                                    <td style="font-size: 0.8rem; color: #64748b;">' . __($MyRow['description']) . '</td>
                                    <td style="font-size: 0.8rem; color: #64748b;">' . __($MyRow['tokenname']) . '</td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedScript=' . urlencode($MyRow['id']) . '&amp;Edit=1" class="action-link" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
                                        <a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '?SelectedScript=' . urlencode($MyRow['id']) . '&amp;Delete=1" class="action-link" style="margin-left: 12px; color: #dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\')" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>';
                        }
                        echo '      </tbody>
                                </table>
                            </div>';
                    } else {
                         echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No components found.') . '</div>';
                    }
echo '          </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="main-form" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                    if (isset($_GET['Edit'])) {
                        $SQL = "SELECT id, scripts, pagesecurity, description FROM dashboard_scripts WHERE id='" . $_GET['SelectedScript'] . "'";
                        $Result = DB_query($SQL);
                        $MyRow = DB_fetch_array($Result);

                        $_POST['Script'] = $MyRow['scripts'];
                        $_POST['PageSecurity'] = $MyRow['pagesecurity'];
                        $_POST['Description'] = $MyRow['description'];

                        echo '<div class="db-card" style="border-color: #059669;">
                                <div class="db-card-header" style="background: #f0fdf4;">
                                    <h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Edit Settings') . '</h3>
                                    <a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" style="color: #64748b;"><i class="fas fa-times"></i></a>
                                </div>
                                <div class="db-card-body">
                                    <input type="hidden" name="ID" value="' . $MyRow['id'] . '" />
                                    <field><label>' . __('Component') . '</label><div class="fieldtext">' . $MyRow['scripts'] . '</div></field>';
                    } else {
                        $_POST['Script'] = '';
                        $_POST['PageSecurity'] = 1;
                        $_POST['Description'] = '';

                        echo '<div class="db-card">
                                <div class="db-card-header">
                                    <h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Quick Action') . '</h3>
                                </div>
                                <div class="db-card-body">
                                    <field>
                                        <label for="Script">' . __('Select File') . '</label>
                                        <select name="Script">';
                                        $Scripts = glob('dashboard/*.php');
                                        foreach ($Scripts as $ScriptName) {
                                            $ScriptName = basename($ScriptName);
                                            if ($ScriptName != 'template.php' and !in_array($ScriptName, $ScriptArray)) {
                                                echo '<option ' . ($_POST['Script'] == $ScriptName ? 'selected="selected"' : '') . ' value="' . $ScriptName . '">' . $ScriptName . '</option>';
                                            }
                                        }
                        echo '          </select>
                                    </field>';
                    }

                    $TokenSQL = "SELECT tokenid, tokenname FROM securitytokens WHERE tokenid<1000 ORDER BY tokenid";
                    $TokenResult = DB_query($TokenSQL);
                    echo '<field>
                            <label for="PageSecurity">' . __('Required Security') . '</label>
                            <select name="PageSecurity">';
                    while ($MyTokenRow = DB_fetch_array($TokenResult)) {
                        echo '<option ' . ($MyTokenRow['tokenid'] == $_POST['PageSecurity'] ? 'selected="selected"' : '') . ' value="' . $MyTokenRow['tokenid'] . '">' . $MyTokenRow['tokenname'] . '</option>';
                    }
                    echo '  </select>
                        </field>';

                    echo '<field>
                            <label for="Description">' . __('Display Label') . '</label>
                            <input type="text" name="Description" placeholder="' . __('Internal Name') . '" value="' . $_POST['Description'] . '" />
                        </field>
                        
                        <div style="margin-top: 10px;">
                            <button type="submit" name="' . (isset($_GET['Edit']) ? 'Update' : 'Insert') . '" class="architect-btn" style="width: 100%;">
                                <i class="fas fa-check-circle"></i> ' . (isset($_GET['Edit']) ? __('Commit Changes') : __('Register Widget')) . '
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
