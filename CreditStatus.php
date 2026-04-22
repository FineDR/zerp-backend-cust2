<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Credit Status Code Maintenance');
$ViewTopic = 'CreditStatus';
$BookMark = 'CreditStatus';

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

    .check-wrap { display: flex; align-items: center; gap: 10px; padding: 14px; background: #f8fafc; border-radius: 10px; border: 1px solid #edf2f7; margin-bottom: 18px; cursor: pointer; }
    .check-wrap input { width: 18px; height: 18px; cursor: pointer; margin: 0; }
    .check-wrap span { font-size: 0.8rem; font-weight: 700; color: #064e3b; }

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
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 700px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedReason'])){
	$SelectedReason = $_GET['SelectedReason'];
} elseif (isset($_POST['SelectedReason'])){
	$SelectedReason = $_POST['SelectedReason'];
}

$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {
	$i=1;
	$SQL="SELECT count(reasoncode) FROM holdreasons WHERE reasoncode='".$_POST['ReasonCode']."'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);

	if ($MyRow[0]!= 0 and !isset($SelectedReason)) {
		$InputError = 1;
		prnMsg( __('The credit status code already exists in the database'),'error');
	}
	if (!is_numeric($_POST['ReasonCode'])) {
		$InputError = 1;
		prnMsg(__('The status code name must be an integer'),'error');
	}
	if (mb_strlen($_POST['ReasonDescription']) > 30 || mb_strlen($_POST['ReasonDescription']) == 0) {
		$InputError = 1;
		prnMsg(__('The credit status description must be between 1 and 30 characters'),'error');
	}

	if (isset($SelectedReason) AND $InputError != 1) {
		$Disallow = (isset($_POST['DisallowInvoices']) and $_POST['DisallowInvoices']=='on') ? 1 : 0;
		$SQL = "UPDATE holdreasons SET reasondescription='" . $_POST['ReasonDescription'] . "', dissallowinvoices=".$Disallow." WHERE reasoncode = '".$SelectedReason."'";
		$Msg = __('The credit status record has been updated');
	} elseif ($InputError != 1) {
		$Disallow = (isset($_POST['DisallowInvoices']) and $_POST['DisallowInvoices']=='on') ? 1 : 0;
		$SQL = "INSERT INTO holdreasons (reasoncode, reasondescription, dissallowinvoices) VALUES ('" .$_POST['ReasonCode'] . "', '".$_POST['ReasonDescription'] . "', ".$Disallow.")";
		$Msg = __('A new credit status record has been inserted');
	}
	if ($InputError != 1) {
		DB_query($SQL);
		prnMsg($Msg,'success');
		unset($SelectedReason); unset($_POST['ReasonCode']); unset($_POST['ReasonDescription']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM debtorsmaster WHERE debtorsmaster.holdreason='".$SelectedReason."'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg( __('Cannot delete this code: customer accounts are referring to it'),'warn');
	}  else {
		DB_query("DELETE FROM holdreasons WHERE reasoncode='" . $SelectedReason . "'");
		prnMsg(__('This credit status code has been deleted'),'success');
	}
	unset ($SelectedReason);
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
                        ' . __('Credit Status') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="credit-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedReason) ? __('Update Status') : __('Create Status')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL = "SELECT * FROM holdreasons ORDER BY reasoncode";
                $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-shield-alt"></i> ' . __('Hold & Restriction Definitions') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Description') . '</th>
                                    <th>' . __('Invoicing Restricted') . '</th>
                                    <th style="width: 100px; text-align: right;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<tr>
                                        <td><span class="badge badge-secondary">', $MyRow['reasoncode'], '</span></td>
                                        <td style="font-weight: 600; color: #064e3b;">', $MyRow['reasondescription'], '</td>
                                        <td>', ($MyRow['dissallowinvoices'] == 1 ? '<span class="badge badge-danger"><i class="fas fa-ban" style="margin-right:6px;"></i>' . __('Restricted') . '</span>' : '<span class="badge badge-success"><i class="fas fa-check" style="margin-right:6px;"></i>' . __('OK') . '</span>'), '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedReason=', $MyRow['reasoncode'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedReason=', $MyRow['reasoncode'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedReason)) {
                    $Res = DB_query("SELECT * FROM holdreasons WHERE reasoncode='" . $SelectedReason . "'");
                    $MyRow = DB_fetch_array($Res);
                    $_POST['ReasonCode'] = $MyRow['reasoncode'];
                    $_POST['ReasonDescription'] = $MyRow['reasondescription'];
                    $_POST['DisallowInvoices'] = ($MyRow['dissallowinvoices'] == 1) ? 'on' : '';
                }

echo '          <form id="credit-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedReason)) { echo '<input type="hidden" name="SelectedReason" value="' . $SelectedReason . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedReason) ? __('Edit Status') : __('New Status')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Hold Code') . '</label>
                                <input type="number" name="ReasonCode" ' . (isset($SelectedReason) ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required autofocus') . ' value="' . ($_POST['ReasonCode'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Description') . '</label>
                                <input type="text" name="ReasonDescription" required maxlength="30" value="' . ($_POST['ReasonDescription'] ?? '') . '" />
                            </field>

                            <label class="check-wrap">
                                <input type="checkbox" name="DisallowInvoices" ' . (($_POST['DisallowInvoices'] ?? '') == 'on' ? 'checked' : '') . ' />
                                <span>' . __('Restrict Invoicing') . '</span>
                            </label>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedReason) ? __('Update Definition') : __('Save Definition')) . '
                            </button>
                            ' . (isset($SelectedReason) ? '<div style="text-align:center; margin-top:15px;"><a href="CreditStatus.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
