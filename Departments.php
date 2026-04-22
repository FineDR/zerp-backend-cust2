<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Departments');
$ViewTopic = 'Setup';
$BookMark = 'Departments';

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
    field input[type="text"], field select {
        width: 100%; border-radius: 10px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 15px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.95rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
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
        grid-template-columns: 1fr 350px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1400px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 500px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }

    .action-link { 
        color: #059669; font-weight: 700; text-decoration: none; 
        display: inline-flex; align-items: center; gap: 5px; font-size: 0.8rem; 
    }
    .action-link:hover { color: #065f46; }
    .action-link.delete { color: #dc2626; }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
    @media (max-width: 600px) {
        .premium-header { padding: 15px; margin-bottom: 20px; }
        .db-card-body { padding: 15px; }
        h1 { font-size: 1.4rem !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if ( isset($_GET['SelectedDepartmentID']) )
	$SelectedDepartmentID = $_GET['SelectedDepartmentID'];
elseif (isset($_POST['SelectedDepartmentID']))
	$SelectedDepartmentID = $_POST['SelectedDepartmentID'];

if (isset($_POST['Submit'])) {
	$InputError = 0;
	if (ContainsIllegalCharacters($_POST['DepartmentName'])) {
		$InputError = 1;
		prnMsg( __('The description of the department must not contain illegal characters'),'error');
	}
	if (trim($_POST['DepartmentName']) == '') {
		$InputError = 1;
		prnMsg( __('The Name of the Department should not be empty'), 'error');
	}

	if (isset($_POST['SelectedDepartmentID']) AND $_POST['SelectedDepartmentID']!='' AND $InputError !=1) {
		$SQL = "SELECT count(*) FROM departments WHERE departmentid <> '" . $SelectedDepartmentID ."' AND description " . LIKE . " '" . $_POST['DepartmentName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('This department name already exists.'),'error');
		} else {
			$SQL = "SELECT description FROM departments WHERE departmentid = '" . $SelectedDepartmentID . "'";
			$Result = DB_query($SQL);
			if ( DB_num_rows($Result) != 0 ) {
				$MyRow = DB_fetch_array($Result);
				$UpdtSQL = "UPDATE departments SET description='" . $_POST['DepartmentName'] . "', authoriser='" . $_POST['Authoriser'] . "' WHERE departmentid = '" . $SelectedDepartmentID . "'";
				$Result = DB_query($UpdtSQL);
			} else {
				$InputError = 1;
				prnMsg( __('The department does not exist.'),'error');
			}
		}
		$Msg = __('The department has been modified');
	} elseif ($InputError !=1) {
		$SQL = "SELECT count(*) FROM departments WHERE description " . LIKE . " '" . $_POST['DepartmentName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			$InputError = 1;
			prnMsg( __('There is already a department with the specified name.'),'error');
		} else {
			$SQL = "INSERT INTO departments (description, authoriser ) VALUES ('" . $_POST['DepartmentName'] . "', '" . $_POST['Authoriser'] . "')";
			$Result = DB_query($SQL);
		}
		$Msg = __('The new department has been created');
	}
	if ($InputError!=1) prnMsg($Msg,'success');
	unset ($SelectedDepartmentID); unset ($_POST['SelectedDepartmentID']); unset ($_POST['DepartmentName']);
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM stockrequest WHERE departmentid='" . $SelectedDepartmentID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg( __('You cannot delete this Department because it has items related to it'),'warn');
	} else {
		$SQL="DELETE FROM departments WHERE departmentid = '" . $SelectedDepartmentID . "'";
		$Result = DB_query($SQL);
		prnMsg(__('The department has been removed') . '!','success');
	}
	unset ($SelectedDepartmentID);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div style="font-size: 0.65rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.6;">
						<i class="fas fa-sitemap"></i> ' . __('Corporate') . ' <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> ' . __('Departments') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="dept-form" name="Submit" class="architect-btn">
                        <i class="fas fa-plus-circle"></i> ' . (isset($SelectedDepartmentID) ? __('Update Department') : __('Create New')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-list-alt"></i> ' . __('Existing Departments') . '</h3>
                    </div>';

                $SQL = "SELECT departmentid, description, authoriser FROM departments ORDER BY description";
                $Result = DB_query($SQL);

                if (DB_num_rows($Result) > 0) {
                    echo '<div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>' . __('Name') . '</th>
                                        <th>' . __('Authorizer') . '</th>
                                        <th style="width: 80px;"></th>
                                    </tr>
                                </thead>
                                <tbody>';
                    while ($MyRow = DB_fetch_array($Result)) {
                        echo '<tr>
                                <td style="font-weight: 700;">' . $MyRow['description'] . '</td>
                                <td style="font-size: 0.8rem; color: #64748b;">' . $MyRow['authoriser'] . '</td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedDepartmentID=' . $MyRow['departmentid'] . '" class="action-link" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedDepartmentID=' . $MyRow['departmentid'] . '&amp;delete=1" class="action-link delete" style="margin-left: 12px;" onclick="return confirm(\'' . __('Confirm delete?') . '\')" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>';
                    }
                    echo '          </tbody>
                            </table>
                        </div>';
                } else {
                    echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No departments found.') . '</div>';
                }
echo '          </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="dept-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                    if (isset($SelectedDepartmentID)) {
                        $SQL = "SELECT departmentid, description, authoriser FROM departments WHERE departmentid='" . $SelectedDepartmentID . "'";
                        $Result = DB_query($SQL);
                        $MyRow = DB_fetch_array($Result);
                        $_POST['DepartmentID'] = $MyRow['departmentid'];
                        $_POST['DepartmentName'] = $MyRow['description'];
                        $AuthoriserID = $MyRow['authoriser'];
                        echo '<input type="hidden" name="SelectedDepartmentID" value="' . $_POST['DepartmentID'] . '" />';
                        echo '<div class="db-card" style="border-color: #059669;">
                                <div class="db-card-header" style="background: #f0fdf4;">
                                    <h3 class="db-card-title"><i class="fas fa-edit"></i> ' . __('Edit Entry') . '</h3>
                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="color: #64748b;"><i class="fas fa-times"></i></a>
                                </div>';
                    } else {
                        $_POST['DepartmentName'] = '';
                        $AuthoriserID = '';
                        echo '<div class="db-card">
                                <div class="db-card-header">
                                    <h3 class="db-card-title"><i class="fas fa-plus-circle"></i> ' . __('Quick Create') . '</h3>
                                </div>';
                    }

                    echo '<div class="db-card-body">
                            <field>
                                <label for="DepartmentName">' . __('Dept Name') . '</label>
                                <input type="text" name="DepartmentName" required="required" placeholder="' . __('Sales, HR, etc.') . '" maxlength="100" value="' . $_POST['DepartmentName'] . '" />
                            </field>
                            <field>
                                <label for="Authoriser">' . __('Manager/Auth') . '</label>
                                <select name="Authoriser">';
                                $Userresult = DB_query("SELECT userid, realname FROM www_users ORDER BY userid");
                                while ($MyRow = DB_fetch_array($Userresult)) {
                                    echo '<option ' . ($MyRow['userid'] == $AuthoriserID ? 'selected="selected"' : '') . ' value="' . $MyRow['userid'] . '">' . $MyRow['userid'] . '</option>';
                                }
                    echo '      </select>
                            </field>
                            
                            <div style="margin-top: 10px;">
                                <button type="submit" name="Submit" class="architect-btn" style="width: 100%;">
                                    <i class="fas fa-save"></i> ' . (isset($SelectedDepartmentID) ? __('Update') : __('Commit')) . '
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="db-card" style="background: #f8fafc; border-style: dashed;">
                    <div class="db-card-body" style="padding: 15px;">
                        <h4 style="font-size: 0.7rem; font-weight: 800; color: #475569; margin: 0 0 8px 0; text-transform: uppercase;">' . __('Guidance') . '</h4>
                        <p style="font-size: 0.75rem; color: #64748b; line-height: 1.5; margin: 0;">' . __('Departments define cost centers and approval chains for requisitions.') . '</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
