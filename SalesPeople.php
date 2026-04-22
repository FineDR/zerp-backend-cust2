<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales People Maintenance');
$ViewTopic = 'SalesPeople';
$BookMark = 'SalesPeople';
if (isset($_GET['SelectedSalesPerson'])) {
	$BookMark = 'SalespeopleEdit';
}
if (isset($_GET['delete'])) {
	$BookMark = 'SalespeopleDelete';
}

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
        font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 360px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    .staff-card { display: flex; align-items: center; gap: 12px; }
    .staff-avatar { width: 36px; height: 36px; background: #ecfdf5; color: #059669; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

$CommissionPeriods = array(
	0 => __('New commission starts next month'),
	1 => __('New commission starts immediately')
);

if (isset($_GET['SelectedSalesPerson'])) {
	$SelectedSalesPerson = $_GET['SelectedSalesPerson'];
} elseif (isset($_POST['SelectedSalesPerson'])) {
	$SelectedSalesPerson = $_POST['SelectedSalesPerson'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	$i=1;
	if (mb_strlen($_POST['SalesmanCode']) > 3) {
		$InputError = 1;
		prnMsg(__('The salesperson code must be three characters or less long'),'error');
	} elseif (mb_strlen($_POST['SalesmanCode'])==0) {
		$InputError = 1;
		prnMsg(__('The salesperson code cannot be empty'),'error');
	} elseif (mb_strlen($_POST['SalesmanName']) > 30) {
		$InputError = 1;
		prnMsg(__('The salesperson name must be thirty characters or less long'),'error');
	}
	
	if (!isset($_POST['SManTel'])) $_POST['SManTel']='';
	if (!isset($_POST['SManFax'])) $_POST['SManFax']='';
	if (!isset($_POST['Current'])) $_POST['Current']=1;
	if (!isset($_POST['CommissionPeriod'])) $_POST['CommissionPeriod']=0;
	if (!isset($_POST['CommissionTypeID'])) $_POST['CommissionTypeID']=0;
	if (!isset($_POST['GLAccount'])) $_POST['GLAccount']='';

	if (isset($SelectedSalesPerson) AND $InputError !=1) {
		$SQL = "UPDATE salesman SET salesmanname='" . $_POST['SalesmanName'] . "',
									smantel='" . $_POST['SManTel'] . "',
									smanfax='" . $_POST['SManFax'] . "',
									current='" . $_POST['Current'] . "',
									commissionperiod='" . $_POST['CommissionPeriod'] . "',
									commissiontypeid='" . $_POST['CommissionTypeID'] . "',
									glaccount='" . $_POST['GLAccount'] . "'
								WHERE salesmancode = '" . stripslashes($SelectedSalesPerson) . "'";
		$Msg = __('Salesperson record for') . ' ' . $_POST['SalesmanName'] . ' ' . __('has been updated');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO salesman (salesmancode, salesmanname, smantel, smanfax, current, commissionperiod, commissiontypeid, glaccount)
				VALUES ('" . $_POST['SalesmanCode'] . "', '" . $_POST['SalesmanName'] . "', '" . $_POST['SManTel'] . "', '" . $_POST['SManFax'] . "', '" . $_POST['Current'] . "', '" . $_POST['CommissionPeriod'] . "', '" . $_POST['CommissionTypeID'] . "', '" . $_POST['GLAccount'] . "')";
		$Msg = __('A new salesperson record has been added for') . ' ' . $_POST['SalesmanName'];
	}
	
	if ($InputError !=1) {
		DB_query($SQL);
		prnMsg($Msg , 'success');
		unset($SelectedSalesPerson); unset($_POST['SalesmanCode']); unset($_POST['SalesmanName']); unset($_POST['SManFax']); unset($_POST['SManTel']); unset($_POST['Current']); unset($_POST['CommissionPeriod']); unset($_POST['CommissionTypeID']); unset($_POST['GLAccount']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM custbranch WHERE custbranch.salesman='".$SelectedSalesPerson."'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg(__('Cannot delete this salesperson because branches are set up referring to them'),'error');
	} else {
		$SQL="DELETE FROM salesman WHERE salesmancode='". $SelectedSalesPerson."'";
		DB_query($SQL);
		prnMsg(__('Salesperson') . ' ' . $SelectedSalesPerson . ' ' . __('has been deleted'),'success');
		unset ($SelectedSalesPerson);
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
                        ' . __('Sales People') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="sales-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedSalesPerson) ? __('Update Personnel') : __('Register Personnel')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL = "SELECT salesman.*, salescommissiontypes.commissiontypename, chartmaster.accountname 
                        FROM salesman 
                        LEFT JOIN salescommissiontypes ON salesman.commissiontypeid=salescommissiontypes.commissiontypeid
                        LEFT JOIN chartmaster ON salesman.glaccount=chartmaster.accountcode";
                $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-id-badge"></i> ' . __('Sales Team Directory') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Salesperson') . '</th>
                                    <th>' . __('Contact Details') . '</th>
                                    <th>' . __('Commission/GL') . '</th>
                                    <th>' . __('Status') . '</th>
                                    <th style="width: 100px; text-align: right;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                $initials = mb_substr($MyRow['salesmancode'], 0, 2);
                                echo '<tr>
                                        <td>
                                            <div class="staff-card">
                                                <div class="staff-avatar">', $initials, '</div>
                                                <div>
                                                    <div style="font-weight:700; color:#064e3b;">', $MyRow['salesmanname'], '</div>
                                                    <div style="font-size:0.7rem; color:#64748b; font-weight:600;">CODE: ', $MyRow['salesmancode'], '</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size:0.8rem; font-weight:600;"><i class="fas fa-phone" style="font-size:0.7rem; opacity:0.5; margin-right:6px;"></i>', ($MyRow['smantel'] ?: '--'), '</div>
                                            <div style="font-size:0.7rem; opacity:0.7;"><i class="fas fa-fax" style="font-size:0.7rem; opacity:0.5; margin-right:6px;"></i>', ($MyRow['smanfax'] ?: '--'), '</div>
                                        </td>
                                        <td>
                                            <div style="font-size:0.8rem; font-weight:700;">', $CommissionPeriods[$MyRow['commissionperiod']], '</div>
                                            <div style="font-size:0.7rem; color:#059669; font-weight:600;">', ($MyRow['accountname'] ?: __('No Account')), '</div>
                                        </td>
                                        <td>', ($MyRow['current'] == 1 ? '<span class="badge badge-success">' . __('Active') . '</span>' : '<span class="badge badge-secondary">' . __('Inactive') . '</span>'), '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="' . $RootPath . '/SalesCommissionRates.php?SelectedSalesPerson=' . urlencode($MyRow['salesmancode']) . '" style="color:#64748b; margin-right:12px;" title="' . __('Commission Rates') . '"><i class="fas fa-percentage"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedSalesPerson=', urlencode($MyRow['salesmancode']), '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedSalesPerson=', urlencode($MyRow['salesmancode']), '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedSalesPerson)) {
                    $SQL_sel = "SELECT * FROM salesman WHERE salesmancode='".$SelectedSalesPerson."'";
                    $MyRow = DB_fetch_array(DB_query($SQL_sel));
                    $_POST['SalesmanCode'] = $MyRow['salesmancode'];
                    $_POST['SalesmanName'] = $MyRow['salesmanname'];
                    $_POST['SManTel'] = $MyRow['smantel'];
                    $_POST['SManFax'] = $MyRow['smanfax'];
                    $_POST['Current'] = $MyRow['current'];
                    $_POST['CommissionPeriod'] = $MyRow['commissionperiod'];
                    $_POST['CommissionTypeID'] = $MyRow['commissiontypeid'];
                    $_POST['GLAccount'] = $MyRow['glaccount'];
                }

echo '          <form id="sales-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedSalesPerson)) { echo '<input type="hidden" name="SelectedSalesPerson" value="' . $SelectedSalesPerson . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-user-plus"></i> ' . (isset($SelectedSalesPerson) ? __('Edit Staff Member') : __('Register Staff')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Staff Code') . '</label>
                                <input type="text" name="SalesmanCode" ' . (isset($SelectedSalesPerson) ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required maxlength="3" autofocus') . ' value="' . ($_POST['SalesmanCode'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Full Name') . '</label>
                                <input type="text" name="SalesmanName" required maxlength="30" value="' . ($_POST['SalesmanName'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Telephone') . '</label>
                                <input type="tel" name="SManTel" maxlength="20" value="' . ($_POST['SManTel'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Fax') . '</label>
                                <input type="tel" name="SManFax" maxlength="20" value="' . ($_POST['SManFax'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Commission Start') . '</label>
                                <select name="CommissionPeriod">
                                    <option ' . (($_POST['CommissionPeriod'] ?? 0) == 0 ? 'selected' : '') . ' value="0">' . __('Next Month') . '</option>
                                    <option ' . (($_POST['CommissionPeriod'] ?? 0) == 1 ? 'selected' : '') . ' value="1">' . __('Immediately') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Calculation method') . '</label>
                                <select name="CommissionTypeID">
                                    <option value="0">' . __('No Commission') . '</option>';
                                    $Res_types = DB_query("SELECT commissiontypeid, commissiontypename FROM salescommissiontypes ORDER BY commissiontypename");
                                    while ($myr = DB_fetch_array($Res_types)) {
                                        echo '<option ' . (($_POST['CommissionTypeID'] ?? 0) == $myr['commissiontypeid'] ? 'selected' : '') . ' value="' . $myr['commissiontypeid'] . '">' . $myr['commissiontypename'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Commission Account') . '</label>
                                <select name="GLAccount">
                                    <option value="">' . __('None') . '</option>';
                                    $Res_gl = DB_query("SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY chartmaster.accountcode");
                                    while ($myr = DB_fetch_array($Res_gl)) {
                                        echo '<option ' . (($_POST['GLAccount'] ?? '') == $myr['accountcode'] ? 'selected' : '') . ' value="' . $myr['accountcode'] . '">' . $myr['accountcode'] . ' - ' . $myr['accountname'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Active Status') . '</label>
                                <select name="Current">
                                    <option ' . (($_POST['Current'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Active') . '</option>
                                    <option ' . (($_POST['Current'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('Inactive') . '</option>
                                </select>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedSalesPerson) ? __('Update Personnel') : __('Save Personnel')) . '
                            </button>
                            ' . (isset($SelectedSalesPerson) ? '<div style="text-align:center; margin-top:15px;"><a href="SalesPeople.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
