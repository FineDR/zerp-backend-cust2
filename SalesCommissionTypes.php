<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Commission Calculation Methods');
$ViewTopic = 'SalesCommission';
$BookMark = 'SalesCommission';

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
        max-width: 1600px;
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
        font-size: 0.65rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.75rem; color: #64748b; margin-top: 6px; display: block; font-weight: 500; }

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
        grid-template-columns: 1fr 320px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1600px;
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

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedTypeID'])) {
	$SelectedTypeID = $_GET['SelectedTypeID'];
} elseif (isset($_POST['SelectedTypeID'])) {
	$SelectedTypeID = $_POST['SelectedTypeID'];
}

if (isset($_POST['Submit'])) {
	$InputError = 0;
	if (trim($_POST['CommissionTypeName']) == '') {
		$InputError = 1;
		prnMsg(__('The commission type name may not be empty'), 'error');
	}

	if (isset($_POST['SelectedTypeID']) and $_POST['SelectedTypeID'] != '' and $InputError != 1) {
		$SQL = "SELECT count(*) FROM salescommissiontypes WHERE commissiontypeid <> '" . $SelectedTypeID . "' AND commissiontypename='" . $_POST['CommissionTypeName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The commission type can not be renamed because another with the same name already exist.'), 'error');
		} else {
			$SQL_check = "SELECT commissiontypename FROM salescommissiontypes WHERE commissiontypeid = '" . $SelectedTypeID . "'";
			$Res_check = DB_query($SQL_check);
			if (DB_num_rows($Res_check) != 0) {
				$MyRow = DB_fetch_row($Res_check);
				$OldCommissionTypeName = $MyRow[0];
				$SQL_upd = "UPDATE salescommissiontypes SET commissiontypename='" . $_POST['CommissionTypeName'] . "' WHERE commissiontypename='" . DB_escape_string($OldCommissionTypeName) . "'";
				DB_query($SQL_upd);
			} else {
				$InputError = 1;
				prnMsg(__('The commission type no longer exist.'), 'error');
			}
		}
		$Msg = __('Commision Type changed');
	} elseif ($InputError != 1) {
		$SQL = "SELECT count(*) FROM salescommissiontypes WHERE commissiontypename='" . $_POST['CommissionTypeName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$InputError = 1;
			prnMsg(__('The commission type can not be created because another with the same name already exists.'), 'error');
		} else {
			$SQL_ins = "INSERT INTO salescommissiontypes (commissiontypename ) VALUES ('" . $_POST['CommissionTypeName'] . "')";
			DB_query($SQL_ins);
		}
		$Msg = __('New sales commission type added');
	}

	if ($InputError != 1) {
		prnMsg($Msg, 'success');
	}
	unset($SelectedTypeID); unset($_POST['SelectedTypeID']); unset($_POST['CommissionTypeName']);
} elseif (isset($_GET['delete'])) {
	$SQL = "SELECT commissiontypename FROM salescommissiontypes WHERE commissiontypeid= '" . $SelectedTypeID . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) == 0) {
		prnMsg(__('Cannot delete this sales commission calculation method because it no longer exist'), 'warn');
	} else {
		$MyRow = DB_fetch_row($Result);
		$OldTypeName = $MyRow[0];
		$SQL_count = "SELECT COUNT(*) FROM salesman WHERE commissiontypeid='" . $_GET['SelectedTypeID'] . "'";
		$Res_count = DB_query($SQL_count);
		$MyRow = DB_fetch_row($Res_count);
		if ($MyRow[0] > 0) {
			prnMsg(__('Cannot delete this sales commission type because sales people items have been created using this type'), 'warn');
		} else {
			$SQL_del = "DELETE FROM salescommissiontypes WHERE commissiontypeid= '" . $SelectedTypeID . "'";
			DB_query($SQL_del);
			prnMsg($OldTypeName . ' ' . __('commision type has been deleted') . '!', 'success');
		}
	}
	unset($SelectedTypeID); unset($_GET['SelectedTypeID']); unset($_GET['delete']);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=Sales">' . __('Sales') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Commission Methods') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="commission-form" name="Submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedTypeID) ? __('Update Method') : __('Create Method')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

            $SQL = "SELECT commissiontypeid, commissiontypename FROM salescommissiontypes ORDER BY commissiontypeid";
            $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Calculation Algorithms') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('ID') . '</th>
                                    <th>' . __('Method Name') . '</th>
                                    <th style="width: 100px; text-align: right;">' . __('Actions') . '</th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_row($Result)) {
                                echo '<tr>
                                        <td style="color: #64748b; font-weight: 700;">#', $MyRow[0], '</td>
                                        <td style="font-weight: 600;">', $MyRow[1], '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedTypeID=', $MyRow[0], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?SelectedTypeID=', $MyRow[0], '&amp;delete=1" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedTypeID)) {
                    $SQL = "SELECT commissiontypeid, commissiontypename FROM salescommissiontypes WHERE commissiontypeid='" . $SelectedTypeID . "'";
                    $Result = DB_query($SQL);
                    $MyRow = DB_fetch_array($Result);
                    $_POST['CommissionTypeName'] = $MyRow['commissiontypename'];
                } else {
                    $_POST['CommissionTypeName'] = $_POST['CommissionTypeName'] ?? '';
                }

echo '          <form id="commission-form" method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedTypeID)) {
                        echo '<input type="hidden" name="SelectedTypeID" value="' . $SelectedTypeID . '" />';
                    }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedTypeID) ? __('Edit Method') : __('New Method')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Method Name') . '</label>
                                <input type="text" name="CommissionTypeName" required maxlength="55" value="' . $_POST['CommissionTypeName'] . '" autofocus />
                                <span class="fieldhelp">' . __('Example: Fixed Rate, Tiered Bonus, etc.') . '</span>
                            </field>

                            <button type="submit" name="Submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedTypeID) ? __('Update Algorithm') : __('Save Algorithm')) . '
                            </button>
                            ' . (isset($SelectedTypeID) ? '<div style="text-align:center; margin-top:15px;"><a href="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
