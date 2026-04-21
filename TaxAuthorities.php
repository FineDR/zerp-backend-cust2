<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Authorities');
$ViewTopic = 'Tax';
$BookMark = 'TaxAuthorities';
include(__DIR__ . '/includes/header.php');

// Inject premium Architect styles
echo '<style>
    :root {
        --primary: #059669;
        --primary-dark: #065f46;
        --primary-light: #ecfdf5;
        --page-padding: 40px;
    }
    .db-page {
        padding: 0 var(--page-padding);
        max-width: 1600px;
        margin: 0 auto;
    }
    .premium-header { 
        margin-bottom: 30px; 
        padding: 24px 30px; 
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        gap: 20px;
    }
    .db-bottom-layout {
        display: grid;
        grid-template-columns: 400px 1fr;
        gap: 32px;
        align-items: start;
        padding-bottom: 50px;
    }
    .arch-card { 
        background: #ffffff; 
        border-radius: 16px; 
        border: 1px solid #e5e7eb; 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 32px;
    }
    .arch-card-header { 
        background: #f9fafb; 
        border-bottom: 1px solid #f3f4f6; 
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
    }
    .arch-card-title {
        font-size: 0.95rem; font-weight: 850; color: #064e3b; margin:0;
        display: flex; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    .arch-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px;
        background: #059669; color: #ffffff; border: none;
        font-weight: 700; font-size: 0.85rem; cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .arch-btn:hover { background: #065f46; transform: translateY(-1px); }
    .arch-btn-secondary { background: #f3f4f6; color: #374151; }
    .arch-btn-secondary:hover { background: #e5e7eb; }
    
    .arch-badge { padding: 4px 10px; border-radius: 10px; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; }
    
    .arch-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px 40px;
    }
    .arch-form-label { display: block; font-size: 0.72rem; font-weight: 900; color: #064e3b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .arch-form-input { width: 100%; height: 48px; border-radius: 8px; border: 1.5px solid #d1fae5; padding: 0 16px; font-weight: 600; font-size: 0.95rem; transition: border-color 0.2s; }
    .arch-form-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

    .list-item {
        padding: 16px 20px; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit;
    }
    .list-item:hover { background: #f0fdf4; }
    .list-item.active { background: #ecfdf5; border-left: 4px solid #059669; padding-left: 16px; }

    .section-divider {
        margin: 40px 0 24px 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #065f46;
    }
    .section-title {
        font-size: 0.75rem;
        font-weight: 950;
        text-transform: uppercase;
        letter-spacing: 1.5px;
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .premium-header { position: relative; border-radius: 0; margin-left: calc(-1 * var(--page-padding)); margin-right: calc(-1 * var(--page-padding)); }
        .db-col-aside { order: 2; }
        .db-col-main { order: 1; }
    }

    @media (max-width: 640px) {
        :root { --page-padding: 15px; }
        .premium-header-inner { flex-direction: column; align-items: flex-start; }
        .arch-form-grid { grid-template-columns: 1fr; gap: 20px; }
    }
</style>';

echo '<div class="db-page">
		<header class="premium-header">
			<div class="premium-header-inner">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-building-columns"></i> ' . __('Tax Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Authorities') . '
                    </div>
                    <h1 style="font-size: 2.2rem; font-weight: 950; letter-spacing: -1.5px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
                </div>
                <div>
                    <a href="' . $RootPath . '/SelectOrderItems.php" class="arch-btn arch-btn-secondary">
                        <i class="fas fa-arrow-left"></i> ' . __('Back to Orders') . '
                    </a>
                </div>
			</div>
		</header>';

if (isset($_POST['SelectedTaxAuthID'])) {
	$SelectedTaxAuthID =$_POST['SelectedTaxAuthID'];
} elseif (isset($_GET['SelectedTaxAuthID'])) {
	$SelectedTaxAuthID =$_GET['SelectedTaxAuthID'];
}

// Logic Handling
if (isset($_POST['submit'])) {
	if ( trim( $_POST['Description'] ) == '' ) {
		$InputError = 1;
		prnMsg( __('The tax type description may not be empty'), 'error');
	}

	if (isset($SelectedTaxAuthID) && !isset($InputError)) {
		$SQL = "UPDATE taxauthorities
					SET taxglcode ='" . $_POST['TaxGLCode'] . "',
					purchtaxglaccount ='" . $_POST['PurchTaxGLCode'] . "',
					description = '" . $_POST['Description'] . "',
					bank = '" . $_POST['Bank'] . "',
					bankacctype = '". $_POST['BankAccType'] . "',
					bankacc = '". $_POST['BankAcc'] . "',
					bankswift = '". $_POST['BankSwift'] . "'
				WHERE taxid = '" . $SelectedTaxAuthID . "'";
		DB_query($SQL);
		prnMsg(__('Tax authority updated'), 'success');
	} elseif (!isset($InputError)) {
		$SQL = "INSERT INTO taxauthorities (taxglcode, purchtaxglaccount, description, bank, bankacctype, bankacc, bankswift)
			VALUES ('".$_POST['TaxGLCode']."', '".$_POST['PurchTaxGLCode']."', '".$_POST['Description']."', '".$_POST['Bank']."', '".$_POST['BankAccType']."', '".$_POST['BankAcc']."', '".$_POST['BankSwift']."')";
		DB_query($SQL);
		$NewTaxID = DB_Last_Insert_ID('taxauthorities','taxid');
		$SQL = "INSERT INTO taxauthrates (taxauthority, dispatchtaxprovince, taxcatid)
				SELECT '" . $NewTaxID  . "', taxprovinces.taxprovinceid, taxcategories.taxcatid FROM taxprovinces, taxcategories";
		DB_query($SQL);
		prnMsg(__('New tax authority added'), 'success');
        unset($SelectedTaxAuthID);
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM taxgrouptaxes WHERE taxauthid='" . $SelectedTaxAuthID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnmsg(__('Cannot delete this tax authority because there are tax groups defined that use it'),'warn');
	} else {
		DB_query("DELETE FROM taxauthrates WHERE taxauthority= '" . $SelectedTaxAuthID . "'");
		DB_query("DELETE FROM taxauthorities WHERE taxid= '" . $SelectedTaxAuthID . "'");
		prnMsg(__('Tax authority deleted'),'success');
		unset ($SelectedTaxAuthID);
	}
}

// Global Variables
$GLAccountsSQL = "SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 ORDER BY accountcode";
$GLAccountsResult = DB_query($GLAccountsSQL);

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-list-ul"></i> ' . __('Authority Registry') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 280px); overflow-y: auto;">';

    $SQL = "SELECT taxid, description FROM taxauthorities ORDER BY taxid";
    $Result = DB_query($SQL);
    while ($MyRow = DB_fetch_array($Result)) {
        $isActive = (isset($SelectedTaxAuthID) && $SelectedTaxAuthID == $MyRow['taxid']) ? 'active' : '';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxAuthID=' . $MyRow['taxid'] . '" class="list-item ' . $isActive . '">
                <div style="width:32px; height:32px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:800; font-size:0.75rem;">' . $MyRow['taxid'] . '</div>
                <div style="flex:1;">
                    <div style="font-weight: 800; font-size: 0.85rem; color:#111827;">' . $MyRow['description'] . '</div>
                </div>
                <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.7rem;"></i>
              </a>';
    }

    echo '      </div>
                <div style="padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus"></i> ' . __('Add New Authority') . '
                    </a>
                </div>
            </div>

            <div class="arch-card">
                <div class="arch-card-header"><h3 class="arch-card-title"><i class="fas fa-tools"></i> ' . __('Setup Hub') . '</h3></div>
                <div class="db-card-body" style="padding: 10px 0;">
                    <a href="' . $RootPath . '/TaxGroups.php" class="list-item" style="border:none;"><i class="fas fa-users-rectangle" style="color:#6366f1;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Tax Groups') . '</span></a>
                    <a href="' . $RootPath . '/TaxProvinces.php" class="list-item" style="border:none;"><i class="fas fa-map-location-dot" style="color:#f59e0b;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Tax Provinces') . '</span></a>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

    if (isset($SelectedTaxAuthID)) {
        $SQL = "SELECT taxglcode, purchtaxglaccount, description, bank, bankacc, bankacctype, bankswift FROM taxauthorities WHERE taxid='" . $SelectedTaxAuthID . "'";
        $Result = DB_query($SQL);
        $MyRow = DB_fetch_array($Result);
        $_POST['TaxGLCode'] = $MyRow['taxglcode'];
        $_POST['PurchTaxGLCode'] = $MyRow['purchtaxglaccount'];
        $_POST['Description'] = $MyRow['description'];
        $_POST['Bank'] = $MyRow['bank'];
        $_POST['BankAccType'] = $MyRow['bankacctype'];
        $_POST['BankAcc'] = $MyRow['bankacc'];
        $_POST['BankSwift'] = $MyRow['bankswift'];
        $formTitle = __('Authority Profile');
        $formSubtitle = __('Full configuration for ID') . ' ' . $SelectedTaxAuthID;
    } else {
        $formTitle = __('Register Authority');
        $formSubtitle = __('Create a new taxing jurisdiction entry');
        foreach(['Description','TaxGLCode','PurchTaxGLCode','Bank','BankAccType','BankAcc','BankSwift'] as $f) if(!isset($_POST[$f])) $_POST[$f] = '';
    }

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    if (isset($SelectedTaxAuthID)) echo '<input type="hidden" name="SelectedTaxAuthID" value="' . $SelectedTaxAuthID . '" />';

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <div>
                    <h3 class="arch-card-title"><i class="fas fa-shield-halved" style="color:var(--primary);"></i> ' . $formTitle . '</h3>
                    <div style="font-size: 0.75rem; color: #6b7280; font-weight:600; margin-top:5px;">' . $formSubtitle . '</div>
                </div>';
    
    if (isset($SelectedTaxAuthID)) {
        echo '  <div style="display:flex; gap:12px;">
                    <a href="' . $RootPath . '/TaxAuthorityRates.php?TaxAuthority=' . $SelectedTaxAuthID . '" class="arch-btn arch-btn-secondary" style="background:var(--primary-light); color:var(--primary); border:1px solid #d1fae5;">
                        <i class="fas fa-chart-line"></i> ' . __('Manage Rates') . '
                    </a>
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxAuthID=' . $SelectedTaxAuthID . '&amp;delete=yes" class="arch-btn" style="background:#fee2e2; color:#dc2626;" onclick="return confirm(\'' . __('Delete this jurisdiction?') . '\');">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>';
    }

    echo '  </div>
            <div class="db-card-body" style="padding:40px;">
                
                <div class="section-divider" style="margin-top:0;">
                    <i class="fas fa-id-card"></i> <span class="section-title">' . __('Basic Identity') . '</span>
                </div>
                <div class="arch-form-grid" style="grid-template-columns: 1fr;">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Jurisdiction Name') . '</label>
                        <input type="text" name="Description" class="arch-form-input" required maxlength="20" value="' . $_POST['Description'] . '" placeholder="' . __('e.g. Kenya Revenue Authority') . '" />
                    </div>
                </div>

                <div class="section-divider">
                    <i class="fas fa-file-invoice-dollar"></i> <span class="section-title">' . __('Financial GL Mapping') . '</span>
                </div>
                <div class="arch-form-grid">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Input Tax Account (Purchases)') . '</label>
                        <select name="PurchTaxGLCode" class="arch-form-input">';
                        DB_data_seek($GLAccountsResult, 0);
                        while ($r = DB_fetch_array($GLAccountsResult)) echo '<option value="'.$r['accountcode'].'" '.($r['accountcode']==$_POST['PurchTaxGLCode']?'selected':'').'>'.htmlspecialchars($r['accountname'], 2).' ('.$r['accountcode'].')</option>';
    echo '              </select>
                    </div>
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Output Tax Account (Sales)') . '</label>
                        <select name="TaxGLCode" class="arch-form-input">';
                        DB_data_seek($GLAccountsResult, 0);
                        while ($r = DB_fetch_array($GLAccountsResult)) echo '<option value="'.$r['accountcode'].'" '.($r['accountcode']==$_POST['TaxGLCode']?'selected':'').'>'.htmlspecialchars($r['accountname'], 2).' ('.$r['accountcode'].')</option>';
    echo '              </select>
                    </div>
                </div>

                <div class="section-divider">
                    <i class="fas fa-university"></i> <span class="section-title">' . __('Settlement Registry') . '</span>
                </div>
                <div class="arch-form-grid">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Settling Bank') . '</label>
                        <input type="text" name="Bank" class="arch-form-input" maxlength="40" value="' . $_POST['Bank'] . '" />
                    </div>
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Account Type') . '</label>
                        <input type="text" name="BankAccType" class="arch-form-input" maxlength="20" value="' . $_POST['BankAccType'] . '" />
                    </div>
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Account Number') . '</label>
                        <input type="text" name="BankAcc" class="arch-form-input" maxlength="20" value="' . $_POST['BankAcc'] . '" />
                    </div>
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('SWIFT / BIC') . '</label>
                        <input type="text" name="BankSwift" class="arch-form-input" maxlength="15" value="' . $_POST['BankSwift'] . '" />
                    </div>
                </div>

                <div style="margin-top:50px; display:flex; justify-content:center;">
                    <button type="submit" name="submit" class="arch-btn" style="padding:16px 80px; font-size:1.05rem; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);">
                        <i class="fas fa-check-double" style="margin-right:12px;"></i>
                        ' . (isset($SelectedTaxAuthID) ? __('Update Authorization') : __('Register Jurisdiction')) . '
                    </button>
                </div>
            </div>
          </div>
          </form>';

    echo '</main></div>'; // End Layout
echo '</div>'; // End Page

include(__DIR__ . '/includes/footer.php');
