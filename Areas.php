<?php

require (__DIR__ . '/includes/session.php');

$Title = __('Sales Area Maintenance');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'Areas';

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
	.architect-btn-outline { background: transparent; color: #059669; border: 1.5px solid #d1fae5; box-shadow: none; }
	.architect-btn-outline:hover { background: #f0fdf4; border-color: #059669; }
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
    .badge-emerald { background: #d1fae5; color: #065f46; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedArea'])) {
	$SelectedArea = mb_strtoupper($_GET['SelectedArea']);
} elseif (isset($_POST['SelectedArea'])) {
	$SelectedArea = mb_strtoupper($_POST['SelectedArea']);
}

$Errors = array();

if (isset($_POST['submit'])) {
	$InputError = 0;
	$i = 1;
	$_POST['AreaCode'] = mb_strtoupper($_POST['AreaCode']);
	$SQL = "SELECT areacode FROM areas WHERE areacode='" . $_POST['AreaCode'] . "'";
	$Result = DB_query($SQL);
	if (mb_strlen($_POST['AreaCode']) > 3) {
		$InputError = 1;
		prnMsg(__('The area code must be three characters or less long'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	} elseif (DB_num_rows($Result) > 0 and !isset($SelectedArea)) {
		$InputError = 1;
		prnMsg(__('The area code entered already exists'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	} elseif (mb_strlen($_POST['AreaDescription']) > 25) {
		$InputError = 1;
		prnMsg(__('The area description must be twenty five characters or less long'), 'error');
		$Errors[$i] = 'AreaDescription';
		$i++;
	} elseif (trim($_POST['AreaCode']) == '') {
		$InputError = 1;
		prnMsg(__('The area code may not be empty'), 'error');
		$Errors[$i] = 'AreaCode';
		$i++;
	} elseif (trim($_POST['AreaDescription']) == '') {
		$InputError = 1;
		prnMsg(__('The area description may not be empty'), 'error');
		$Errors[$i] = 'AreaDescription';
		$i++;
	}

	if (isset($SelectedArea) and $InputError != 1) {
		$SQL = "UPDATE areas SET areadescription='" . $_POST['AreaDescription'] . "' WHERE areacode = '" . $SelectedArea . "'";
		$Msg = __('Area code') . ' ' . $SelectedArea . ' ' . __('has been updated');
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO areas (areacode, areadescription) VALUES ('" . $_POST['AreaCode'] . "', '" . $_POST['AreaDescription'] . "')";
		$SelectedArea = $_POST['AreaCode'];
		$Msg = __('New area code') . ' ' . $_POST['AreaCode'] . ' ' . __('has been inserted');
	}

	if ($InputError != 1) {
		$ErrMsg = __('The area could not be added or updated because');
		$Result = DB_query($SQL, $ErrMsg);
		unset($SelectedArea);
		unset($_POST['AreaCode']);
		unset($_POST['AreaDescription']);
		prnMsg($Msg, 'success');
	}

} elseif (isset($_GET['delete'])) {
	$CancelDelete = 0;
	$SQL = "SELECT COUNT(branchcode) AS branches FROM custbranch WHERE custbranch.area='$SelectedArea'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['branches'] > 0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this area because customer branches have been created using this area'), 'warn');
	} else {
		$SQL = "SELECT COUNT(area) AS records FROM salesanalysis WHERE salesanalysis.area ='$SelectedArea'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		if ($MyRow['records'] > 0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete this area because sales analysis records exist that use this area'), 'warn');
		}
	}

	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM areas WHERE areacode='" . $SelectedArea . "'";
		$Result = DB_query($SQL);
		prnMsg(__('Area Code') . ' ' . $SelectedArea . ' ' . __('has been deleted') . ' !', 'success');
	}
	unset($SelectedArea);
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
                        ' . __('Sales Areas') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="area-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedArea) ? __('Update Area') : __('Create Area')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL = "SELECT areacode, areadescription FROM areas";
                $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-map-marked-alt"></i> ' . __('Defined Sales Regions') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Area Description') . '</th>
                                    <th style="width: 120px; text-align: right;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<tr>
                                        <td><span class="badge badge-emerald">', $MyRow['areacode'], '</span></td>
                                        <td style="font-weight: 600; color: #064e3b;">', $MyRow['areadescription'], '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="' . $RootPath . '/SelectCustomer.php?Area=' . $MyRow['areacode'] . '" style="color:#64748b; margin-right:12px;" title="' . __('View Customers') . '"><i class="fas fa-users"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedArea=', $MyRow['areacode'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedArea=', $MyRow['areacode'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedArea)) {
                    $Res = DB_query("SELECT areacode, areadescription FROM areas WHERE areacode='" . $SelectedArea . "'");
                    $MyRow = DB_fetch_array($Res);
                    $_POST['AreaCode'] = $MyRow['areacode'];
                    $_POST['AreaDescription'] = $MyRow['areadescription'];
                }

echo '          <form id="area-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedArea)) { echo '<input type="hidden" name="SelectedArea" value="' . $SelectedArea . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedArea) ? __('Edit Area') : __('New Area')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Area Code') . '</label>
                                <input type="text" name="AreaCode" ' . (isset($SelectedArea) ? 'readonly style="background:#f1f5f9; cursor:not-allowed;"' : 'required maxlength="3" autofocus') . ' value="' . ($_POST['AreaCode'] ?? '') . '" placeholder="e.g. LON" />
                                ' . (isset($SelectedArea) ? '' : '<span class="fieldhelp">' . __('Enter a unique 3-character code') . '</span>') . '
                            </field>
                            <field>
                                <label>' . __('Description') . '</label>
                                <input type="text" name="AreaDescription" required maxlength="25" value="' . ($_POST['AreaDescription'] ?? '') . '" placeholder="e.g. London Area" />
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedArea) ? __('Update Area') : __('Save Area')) . '
                            </button>
                            ' . (isset($SelectedArea) ? '<div style="text-align:center; margin-top:15px;"><a href="Areas.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
