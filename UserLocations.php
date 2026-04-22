<?php

require(__DIR__ . '/includes/session.php');

$Title = __('User Authorised Inventory Locations Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'LocationUsers';

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
        max-width: 1400px;
        margin: 0 auto;
        gap: 20px;
    }
	
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
    .db-card-body { padding: 25px; }
	
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
    field select {
        width: 100%; border-radius: 10px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 15px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
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
        grid-template-columns: 1fr 360px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 600px; }
    table.modern-table th { 
        text-align: left; padding: 15px 20px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #334155; }

    .action-link { 
        color: #059669; font-weight: 700; text-decoration: none; 
        display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; 
    }
    .action-link:hover { color: #065f46; }
    .action-link.delete { color: #dc2626; }

    .user-info-card {
        background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 12px; padding: 15px; margin-bottom: 20px;
        display: flex; align-items: center; gap: 15px;
    }
    .user-info-icon { width: 40px; height: 40px; background: #059669; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedLocation'])) {
	$SelectedLocation = mb_strtoupper($_POST['SelectedLocation']);
} elseif (isset($_GET['SelectedLocation'])) {
	$SelectedLocation = mb_strtoupper($_GET['SelectedLocation']);
} else {
	$SelectedLocation = '';
}

if (isset($_POST['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_POST['SelectedUser']);
} elseif (isset($_GET['SelectedUser'])) {
	$SelectedUser = mb_strtoupper($_GET['SelectedUser']);
}

if (isset($_POST['Cancel'])) {
	unset($SelectedUser);
	unset($SelectedLocation);
}

if (isset($_POST['Process'])) {
	if ($_POST['SelectedUser'] == '') {
		prnMsg(__('You have not selected any User'), 'error');
		unset($SelectedUser); unset($_POST['SelectedUser']);
	}
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if ($_POST['SelectedLocation'] == '') {
		$InputError = 1;
		prnMsg(__('You have not selected an inventory location to be authorised for this user'), 'error');
		unset($SelectedUser);
	}
	if ($InputError != 1) {
		$CheckSql = "SELECT count(*) FROM locationusers WHERE loccode= '" . $_POST['SelectedLocation'] . "' AND userid = '" . $_POST['SelectedUser'] . "'";
		$CheckResult = DB_query($CheckSql);
		$CheckRow = DB_fetch_row($CheckResult);
		if ($CheckRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The location') . ' ' . $_POST['SelectedLocation'] . ' ' . __('is already authorised for this user'), 'error');
		} else {
			$SQL = "INSERT INTO locationusers (loccode, userid, canview, canupd) VALUES ('" . $_POST['SelectedLocation'] . "', '" . $_POST['SelectedUser'] . "', '1', '1')";
			$Msg = __('User') . ': ' . $_POST['SelectedUser'] . ' ' . __('authority logic updated successfully');
			$Result = DB_query($SQL);
			prnMsg($Msg, 'success');
			unset($_POST['SelectedLocation']);
		}
	}
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM locationusers WHERE loccode='" . $SelectedLocation . "' AND userid='" . $SelectedUser . "'";
	$Result = DB_query($SQL);
	prnMsg(__('User') . ' ' . $SelectedUser . ' ' . __('authority removed successfully'), 'success');
	unset($_GET['delete']);
} elseif (isset($_GET['ToggleUpdate'])) {
	$SQL = "UPDATE locationusers SET canupd='" . $_GET['ToggleUpdate'] . "' WHERE loccode='" . $SelectedLocation . "' AND userid='" . $SelectedUser . "'";
	$Result = DB_query($SQL);
	prnMsg(__('Update authority toggled successfully'), 'success');
	unset($_GET['ToggleUpdate']);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div style="font-size: 0.6rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">
						<i class="fas fa-user-lock"></i> ' . __('Security') . ' <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> ' . __('Access Control') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="location-form" name="' . (isset($SelectedUser) ? 'submit' : 'Process') . '" class="architect-btn">
                        <i class="fas fa-check-circle"></i> ' . (isset($SelectedUser) ? __('Update Access') : __('Assign User')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

                if (isset($SelectedUser)) {
                    $SQLName = "SELECT realname FROM www_users WHERE userid='" . $SelectedUser . "'";
                    $Result = DB_query($SQLName);
                    $MyRow = DB_fetch_array($Result);
                    $SelectedUserName = $MyRow['realname'];

                    echo '<div class="user-info-card">
                            <div class="user-info-icon"><i class="fas fa-user"></i></div>
                            <div>
                                <div style="font-size: 0.7rem; font-weight: 900; color: #065f46; text-transform: uppercase; letter-spacing: 1px;">' . __('Selected User') . '</div>
                                <div style="font-size: 1.1rem; font-weight: 850; color: #064e3b;">' . $SelectedUserName . ' (' . $SelectedUser . ')</div>
                            </div>
                          </div>';

                    $SQL = "SELECT locationusers.loccode, canview, canupd, locations.locationname FROM locationusers INNER JOIN locations ON locationusers.loccode=locations.loccode WHERE locationusers.userid='" . $SelectedUser . "' ORDER BY locations.locationname ASC";
                    $Result = DB_query($SQL);

                    echo '<div class="db-card">
                            <div class="db-card-header">
                                <h3 class="db-card-title"><i class="fas fa-map-marker-alt"></i> ' . __('Authorised Locations') . '</h3>
                            </div>';
                    if (DB_num_rows($Result) > 0) {
                        echo '<div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Loc Code') . '</th>
                                            <th>' . __('Location Name') . '</th>
                                            <th style="text-align: center;">' . __('View') . '</th>
                                            <th style="text-align: center;">' . __('Update') . '</th>
                                            <th style="width: 120px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        while ($MyRow = DB_fetch_array($Result)) {
                            $ToggleUpdateVal = ($MyRow['canupd'] == 1) ? 0 : 1;
                            $ToggleIcon = ($MyRow['canupd'] == 1) ? 'fa-lock-open' : 'fa-lock';
                            $ToggleTitle = ($MyRow['canupd'] == 1) ? __('Revoke Update') : __('Grant Update');

                            echo '<tr>
                                    <td style="font-weight: 700;">', $MyRow['loccode'], '</td>
                                    <td style="font-size: 0.9rem; color: #64748b;">', $MyRow['locationname'], '</td>
                                    <td style="text-align: center;"><i class="fas fa-check-circle" style="color:#059669;"></i></td>
                                    <td style="text-align: center;">' . ($MyRow['canupd'] == 1 ? '<i class="fas fa-check-circle" style="color:#059669;"></i>' : '<i class="fas fa-minus-circle" style="color:#cbd5e1;"></i>') . '</td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocation=' . $MyRow['loccode'] . '&amp;ToggleUpdate=' . $ToggleUpdateVal . '&amp;SelectedUser=' . $SelectedUser . '" class="action-link" title="' . $ToggleTitle . '"><i class="fas ' . $ToggleIcon . '"></i></a>
                                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLocation=' . $MyRow['loccode'] . '&amp;delete=yes&amp;SelectedUser=' . $SelectedUser . '" class="action-link delete" style="margin-left: 15px;" onclick="return confirm(\'' . __('Remove authority?') . '\')" title="' . __('Delete') . '"><i class="fas fa-user-slash"></i></a>
                                    </td>
                                </tr>';
                        }
                        echo '      </tbody>
                                </table>
                            </div>';
                    } else {
                        echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No locations assigned to this user.') . '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="db-card">
                            <div class="db-card-body" style="text-align: center; padding: 60px; color: #64748b;">
                                <i class="fas fa-user-lock" style="font-size: 3rem; opacity: 0.15; margin-bottom: 20px;"></i>
                                <h3 style="margin: 0; color: #1e293b;">' . __('Access Control Workspace') . '</h3>
                                <p style="margin-top: 10px;">' . __('Please select a system user from the sidebar to manage their location permissions.') . '</p>
                            </div>
                          </div>';
                }
echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="location-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                    if (!isset($SelectedUser)) {
                        echo '<div class="db-card">
                                <div class="db-card-header">
                                    <h3 class="db-card-title"><i class="fas fa-users"></i> ' . __('User Selection') . '</h3>
                                </div>
                                <div class="db-card-body">
                                    <field>
                                        <label for="SelectedUser">' . __('System User') . '</label>
                                        <select name="SelectedUser">';
                                        $Result = DB_query("SELECT userid, realname FROM www_users ORDER BY userid");
                                        echo '<option value="">' . __('Select...') . '</option>';
                                        while ($MyRow = DB_fetch_array($Result)) {
                                            echo '<option value="' . $MyRow['userid'] . '">' . $MyRow['userid'] . ' - ' . $MyRow['realname'] . '</option>';
                                        }
                        echo '          </select>
                                    </field>
                                    <button type="submit" name="Process" class="architect-btn" style="width: 100%;">' . __('Manage Access') . '</button>
                                </div>
                              </div>';
                    } else {
                        echo '<input type="hidden" name="SelectedUser" value="' . $SelectedUser . '" />';
                        if (!isset($_GET['delete'])) {
                            echo '<div class="db-card" style="border-color: #059669;">
                                    <div class="db-card-header" style="background: #f0fdf4;">
                                        <h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Grant Authority') . '</h3>
                                        <button type="submit" name="Cancel" style="border:none; background:none; cursor:pointer;"><i class="fas fa-times" style="color:#64748b;"></i></button>
                                    </div>
                                    <div class="db-card-body">
                                        <field>
                                            <label for="SelectedLocation">' . __('Inventory Location') . '</label>
                                            <select name="SelectedLocation">';
                                            $Result = DB_query("SELECT loccode, locationname FROM locations WHERE NOT EXISTS (SELECT loccode FROM locationusers WHERE userid='" . $SelectedUser . "' AND loccode=locations.loccode) ORDER BY locationname");
                                            echo '<option value="">' . __('Available Locations...') . '</option>';
                                            while ($MyRow = DB_fetch_array($Result)) {
                                                echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . ' (' . $MyRow['loccode'] . ')' . '</option>';
                                            }
                            echo '          </select>
                                        </field>
                                        <button type="submit" name="submit" class="architect-btn" style="width: 100%;">' . __('Add to User') . '</button>
                                        <div style="margin-top: 15px; text-align: center;">
                                            <button type="submit" name="Cancel" style="background:none; border:none; color:#64748b; font-size: 0.75rem; font-weight:700; cursor:pointer;">' . __('Change User') . '</button>
                                        </div>
                                    </div>
                                  </div>';
                        }
                    }
echo '          </form>
                
                <div class="db-card" style="background: #f8fafc; border-style: dashed;">
                    <div class="db-card-body" style="padding: 15px;">
                        <h4 style="font-size: 0.7rem; font-weight: 800; color: #475569; margin: 0 0 8px 0; text-transform: uppercase;">' . __('Security Policy') . '</h4>
                        <p style="font-size: 0.75rem; color: #64748b; line-height: 1.5; margin: 0;">' . __('Inventory access controls define which locations a user can view or update. Only authorised staff should have update permissions.') . '</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
