<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Purchase Order Authorisation Maintenance');
$ViewTopic = '';
$BookMark = 'PO_AuthorisationLevels';

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
    .db-checkbox-field { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; cursor: pointer; }
    .db-checkbox-field input { width: 18px; height: 18px; margin: 0; cursor: pointer; }
    .db-checkbox-field span { font-size: 0.8rem; font-weight: 600; color: #334155; }

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
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['Submit'])) {
	$CanCreate = (isset($_POST['CanCreate']) AND $_POST['CanCreate']=='on') ? 0 : 1;
	$OffHold = (isset($_POST['OffHold']) AND $_POST['OffHold']=='on') ? 0 : 1;
	$SQL="SELECT COUNT(*) FROM purchorderauth WHERE userid='" . $_POST['UserID'] . "' AND currabrev='" . $_POST['CurrCode'] . "'";
	if (DB_fetch_row(DB_query($SQL))[0]==0) {
		$SQL="INSERT INTO purchorderauth (userid, currabrev, cancreate, offhold, authlevel) VALUES( '".$_POST['UserID']."', '".$_POST['CurrCode']."', '".$CanCreate."', '".$OffHold."', '" . filter_number_format($_POST['AuthLevel'])."')";
		DB_query($SQL);
		prnMsg(__('Auth level created'), 'success');
	} else {
		prnMsg(__('Already exists for this user/currency'), 'error');
	}
} elseif (isset($_POST['Update'])) {
	$CanCreate = (isset($_POST['CanCreate']) AND $_POST['CanCreate']=='on') ? 0 : 1;
	$OffHold = (isset($_POST['OffHold']) AND $_POST['OffHold']=='on') ? 0 : 1;
	$SQL="UPDATE purchorderauth SET cancreate='".$CanCreate."', offhold='".$OffHold."', authlevel='".filter_number_format($_POST['AuthLevel'])."' WHERE userid='".$_POST['UserID']."' AND currabrev='".$_POST['CurrCode']."'";
	DB_query($SQL);
	prnMsg(__('Auth level updated'), 'success');
} elseif (isset($_GET['Delete'])) {
	$SQL="DELETE FROM purchorderauth WHERE userid='".$_GET['UserID']."' AND currabrev='".$_GET['Currency']."'";
	DB_query($SQL);
	prnMsg(__('Auth level deleted'), 'success');
}

$SQL_list = "SELECT purchorderauth.*, www_users.realname, currencies.currency, currencies.decimalplaces 
             FROM purchorderauth 
             INNER JOIN www_users ON purchorderauth.userid=www_users.userid 
             INNER JOIN currencies ON purchorderauth.currabrev=currencies.currabrev";
$Result_list = DB_query($SQL_list);

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Setup') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('PO Approval Levels') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="auth-form" name="' . (isset($_GET['Edit']) ? 'Update' : 'Submit') . '" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($_GET['Edit']) ? __('Update Authority') : __('Create Authority')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-shield-alt"></i> ' . __('Active Approval Matrix') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Approver') . '</th>
                                    <th>' . __('Currency') . '</th>
                                    <th style="text-align:center;">' . __('Create Orders') . '</th>
                                    <th style="text-align:center;">' . __('Release Inv.') . '</th>
                                    <th style="text-align:right;">' . __('Limit') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result_list)) {
                                echo '<tr>
                                        <td>
                                            <div style="font-weight:700; color:#064e3b;">', $MyRow['userid'], '</div>
                                            <div style="font-size:0.7rem; color:#64748b;">', $MyRow['realname'], '</div>
                                        </td>
                                        <td style="font-weight:600;">', $MyRow['currency'], '</td>
                                        <td style="text-align:center;">', ($MyRow['cancreate']==0 ? '<span class="badge badge-success">' . __('YES') . '</span>' : '<span class="badge badge-danger">' . __('NO') . '</span>'), '</td>
                                        <td style="text-align:center;">', ($MyRow['offhold']==0 ? '<span class="badge badge-success">' . __('YES') . '</span>' : '<span class="badge badge-danger">' . __('NO') . '</span>'), '</td>
                                        <td style="text-align:right; font-weight:700; color:#059669;">', locale_number_format($MyRow['authlevel'], $MyRow['decimalplaces']), '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="PO_AuthorisationLevels.php?Edit=Yes&amp;UserID=' . $MyRow['userid'] . '&amp;Currency='.$MyRow['currabrev'].'" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="PO_AuthorisationLevels.php?Delete=Yes&amp;UserID=' . $MyRow['userid'] . '&amp;Currency='.$MyRow['currabrev'].'" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                $AuthLevel = 0; $CanCreate = 0; $OffHold = 0; $CurrDecimalPlaces = 2;
                if (isset($_GET['Edit'])) {
                    $SQL_edit ="SELECT * FROM purchorderauth WHERE userid='".$_GET['UserID']."' AND currabrev='".$_GET['Currency']."'";
                    $MyRow = DB_fetch_array(DB_query($SQL_edit));
                    $AuthLevel = $MyRow['authlevel'];
                    $CanCreate = $MyRow['cancreate'];
                    $OffHold = $MyRow['offhold'];
                    $CurrRes = DB_query("SELECT decimalplaces FROM currencies WHERE currabrev='".$_GET['Currency']."'");
                    $CurrDecimalPlaces = DB_fetch_row($CurrRes)[0];
                }

echo '          <form id="auth-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-user-lock"></i> ' . (isset($_GET['Edit']) ? __('Edit Authority') : __('New Authority')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('User Account') . '</label>';
                                if (isset($_GET['Edit'])) {
                                    echo '<input type="text" readonly value="' . $_GET['UserID'] . '" style="background:#f8fafc;" />';
                                    echo '<input type="hidden" name="UserID" value="' . $_GET['UserID'] . '" />';
                                } else {
                                    echo '<select name="UserID">';
                                    $UserRes = DB_query("SELECT userid, realname FROM www_users");
                                    while ($urow = DB_fetch_array($UserRes)) { echo '<option value="'.$urow['userid'].'">'.$urow['userid'].' - '.$urow['realname'].'</option>'; }
                                    echo '</select>';
                                }
echo '                          </field>
                            <field>
                                <label>' . __('Currency') . '</label>';
                                if (isset($_GET['Edit'])) {
                                    echo '<input type="text" readonly value="' . $_GET['Currency'] . '" style="background:#f8fafc;" />';
                                    echo '<input type="hidden" name="CurrCode" value="' . $_GET['Currency'] . '" />';
                                } else {
                                    echo '<select name="CurrCode">';
                                    $CurrRes = DB_query("SELECT currabrev, currency FROM currencies");
                                    while ($crow = DB_fetch_array($CurrRes)) { echo '<option value="'.$crow['currabrev'].'">'.$crow['currency'].'</option>'; }
                                    echo '</select>';
                                }
echo '                          </field>
                            <field>
                                <label>' . __('Approval Limit') . '</label>
                                <input type="text" name="AuthLevel" value="' . locale_number_format($AuthLevel, $CurrDecimalPlaces) . '" placeholder="0.00" />
                                <span class="fieldhelp">' . __('Maximum PO amount for this approver') . '</span>
                            </field>
                            
                            <label class="db-checkbox-field">
                                <input type="checkbox" name="CanCreate" ' . ($CanCreate==0 ? 'checked' : '') . ' />
                                <span>' . __('Can Create Purchase Orders') . '</span>
                            </label>
                            <label class="db-checkbox-field">
                                <input type="checkbox" name="OffHold" ' . ($OffHold==0 ? 'checked' : '') . ' />
                                <span>' . __('Can Release Supplier Invoices') . '</span>
                            </label>

                            <button type="submit" name="' . (isset($_GET['Edit']) ? 'Update' : 'Submit') . '" class="architect-btn" style="width: 100%; margin-top:20px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($_GET['Edit']) ? __('Update Level') : __('Define Level')) . '
                            </button>
                            ' . (isset($_GET['Edit']) ? '<div style="text-align:center; margin-top:15px;"><a href="PO_AuthorisationLevels.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
