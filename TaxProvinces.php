<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Provinces');
$ViewTopic = 'Tax';
$BookMark = 'TaxProvinces';
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
    .arch-badge-success { background: #dcfce7; color: #166534; }
    .arch-badge-neutral { background: #f3f4f6; color: #4b5563; }
    .arch-badge-warn { background: #fef3c7; color: #92400e; }
    
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
    .section-title { font-size: 0.75rem; font-weight: 950; text-transform: uppercase; letter-spacing: 1.5px; }

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
                        <i class="fas fa-earth-americas"></i> ' . __('Tax Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Provinces') . '
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

if ( isset($_GET['SelectedTaxProvince']) ) $SelectedTaxProvince = $_GET['SelectedTaxProvince'];
elseif (isset($_POST['SelectedTaxProvince'])) $SelectedTaxProvince = $_POST['SelectedTaxProvince'];

// Logic Handling
if (isset($_POST['submit'])) {
	$InputError = 0;
	if (ContainsIllegalCharacters($_POST['TaxProvinceName'])) {
		$InputError = 1;
		prnMsg( __('The tax province name contains illegal characters'),'error');
	}
	if (trim($_POST['TaxProvinceName']) == '') {
		$InputError = 1;
		prnMsg( __('The tax province name may not be empty'), 'error');
	}

	if (isset($SelectedTaxProvince) && $InputError !=1) {
		$SQL = "SELECT count(*) FROM taxprovinces WHERE taxprovinceid <> '" . $SelectedTaxProvince ."' AND taxprovincename " . LIKE . " '" . $_POST['TaxProvinceName'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			prnMsg( __('Another province with the same name already exists'),'error');
		} else {
			$SQL = "UPDATE taxprovinces SET taxprovincename='" . $_POST['TaxProvinceName'] . "' WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
			DB_query($SQL);
			prnMsg(__('Tax province name updated'),'success');
		}
	} elseif ($InputError !=1) {
		$SQL = "SELECT count(*) FROM taxprovinces WHERE taxprovincename " .LIKE. " '".$_POST['TaxProvinceName'] ."'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ( $MyRow[0] > 0 ) {
			prnMsg( __('Province name already exists'),'error');
		} else {
			$SQL = "INSERT INTO taxprovinces (taxprovincename) VALUES ('" . $_POST['TaxProvinceName'] ."')";
			DB_query($SQL);
			$TaxProvinceID = DB_Last_Insert_ID('taxprovinces', 'taxprovinceid');
			$SQL = "INSERT INTO taxauthrates (taxauthority, dispatchtaxprovince, taxcatid) SELECT taxauthorities.taxid, '" . $TaxProvinceID . "', taxcategories.taxcatid FROM taxauthorities CROSS JOIN taxcategories";
			DB_query($SQL);
			prnMsg(__('New tax province added and rates initialized'),'success');
            unset($SelectedTaxProvince);
		}
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM locations WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		prnMsg( __('Cannot delete this province because it is used by') . ' ' . $MyRow[0] . ' ' . __('stock locations'),'warn');
	} else {
		DB_query("DELETE FROM taxauthrates WHERE dispatchtaxprovince = '" . $SelectedTaxProvince . "'");
		DB_query("DELETE FROM taxprovinces WHERE taxprovinceid = '" .$SelectedTaxProvince . "'");
		prnMsg(__('Province and related tax rates deleted'),'success');
		unset ($SelectedTaxProvince);
	}
}

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-list-ul"></i> ' . __('Province Registry') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 250px); overflow-y: auto;">';

    $SQL = "SELECT taxprovinceid, taxprovincename FROM taxprovinces ORDER BY taxprovinceid";
    $Result = DB_query($SQL);
    while ($MyRow = DB_fetch_array($Result)) {
        $isActive = (isset($SelectedTaxProvince) && $SelectedTaxProvince == $MyRow['taxprovinceid']) ? 'active' : '';
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxProvince=' . $MyRow['taxprovinceid'] . '" class="list-item ' . $isActive . '">
                <div style="width:32px; height:32px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:800; font-size:0.75rem;">' . $MyRow['taxprovinceid'] . '</div>
                <div style="flex:1;"><div style="font-weight: 800; font-size: 0.85rem; color:#111827;">' . $MyRow['taxprovincename'] . '</div></div>
                <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.7rem;"></i>
              </a>';
    }

    echo '      </div>
                <div style="padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus"></i> ' . __('Register New Province') . '
                    </a>
                </div>
            </div>

            <!-- Setup Suite -->
            <div class="arch-card">
                <div class="arch-card-header"><h3 class="arch-card-title"><i class="fas fa-tools"></i> ' . __('Tax Suite Core') . '</h3></div>
                <div class="db-card-body" style="padding: 10px 0;">
                    <a href="' . $RootPath . '/TaxGroups.php" class="list-item" style="border:none;"><i class="fas fa-users-rectangle" style="color:#6366f1;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Tax Groups') . '</span></a>
                    <a href="' . $RootPath . '/TaxAuthorities.php" class="list-item" style="border:none;"><i class="fas fa-building" style="color:#ff5e5e;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Tax Authorities') . '</span></a>
                    <a href="' . $RootPath . '/TaxCategories.php" class="list-item" style="border:none;"><i class="fas fa-tags" style="color:#ec4899;"></i> <span style="font-weight:600; font-size:0.85rem;">' . __('Tax Categories') . '</span></a>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

    if (isset($SelectedTaxProvince)) {
        $SQL = "SELECT taxprovinceid, taxprovincename FROM taxprovinces WHERE taxprovinceid='" . $SelectedTaxProvince . "'";
        $Result = DB_query($SQL);
        $MyRow = DB_fetch_array($Result);
        $_POST['TaxProvinceName'] = $MyRow['taxprovincename'];
        $formTitle = __('Province Identity Hub');
        $formSubtitle = __('Configuring geographic jurisdiction for ID') . ' ' . $SelectedTaxProvince;
    } else {
        $_POST['TaxProvinceName'] = '';
        $formTitle = __('Register Geographic Hub');
        $formSubtitle = __('Define a new geographic jurisdiction for tax calculation');
    }

    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    if (isset($SelectedTaxProvince)) echo '<input type="hidden" name="SelectedTaxProvince" value="' . $SelectedTaxProvince . '" />';

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <div>
                    <h3 class="arch-card-title"><i class="fas fa-map-location-dot" style="color:var(--primary);"></i> ' . $formTitle . '</h3>
                    <div style="font-size: 0.75rem; color: #6b7280; font-weight:600; margin-top:5px;">' . $formSubtitle . '</div>
                </div>';
    
    if (isset($SelectedTaxProvince)) {
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxProvince=' . $SelectedTaxProvince . '&amp;delete=1" class="arch-btn" style="background:#fee2e2; color:#dc2626;" onclick="return confirm(\'' . __('Delete this geographic jurisdiction?') . '\');">
                <i class="fas fa-trash-alt"></i>
              </a>';
    }

    echo '  </div>
            <div class="db-card-body" style="padding:40px;">
                
                <div class="section-divider" style="margin-top:0;">
                    <i class="fas fa-globe"></i> <span class="section-title">' . __('Geographic Identity') . '</span>
                </div>
                <div class="arch-form-grid" style="grid-template-columns: 1fr;">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Province Name / Jurisdiction') . '</label>
                        <input type="text" name="TaxProvinceName" class="arch-form-input" required maxlength="30" value="' . $_POST['TaxProvinceName'] . '" placeholder="' . __('e.g. California, Ontario, Lagos') . '" />
                    </div>
                </div>';

    if (isset($SelectedTaxProvince)) {
        // Operational Context
        $SQLCount = "SELECT COUNT(*) FROM locations WHERE taxprovinceid = '" . $SelectedTaxProvince . "'";
        $CountResult = DB_query($SQLCount);
        $CountRow = DB_fetch_row($CountResult);
        
        echo '<div class="section-divider">
                <i class="fas fa-warehouse"></i> <span class="section-title">' . __('Operational Usage') . '</span>
              </div>
              <div style="display:flex; gap:20px; align-items:center; background:#f9fafb; padding:20px; border-radius:12px; border:1px solid #f3f4f6;">
                <div style="width:48px; height:48px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:10px; font-size:1.2rem;">
                    <i class="fas fa-link"></i>
                </div>
                <div>
                    <div style="font-size:0.75rem; font-weight:900; color:#065f46; text-transform:uppercase; letter-spacing:1px;">' . __('Linked Warehouses') . '</div>
                    <div style="font-size:1.1rem; font-weight:850; color:#111827;">' . $CountRow[0] . ' ' . __('Locations') . '</div>
                </div>
                <div style="margin-left:auto;">
                    ' . ($CountRow[0] > 0 ? '<span class="arch-badge arch-badge-warn"><i class="fas fa-lock"></i> Protected</span>' : '<span class="arch-badge arch-badge-success">Deletable</span>') . '
                </div>
              </div>';
    }

    echo '      <div style="margin-top:50px; display:flex; justify-content:center;">
                    <button type="submit" name="submit" class="arch-btn" style="padding:16px 80px; font-size:1.05rem; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);">
                        <i class="fas fa-check-double" style="margin-right:12px;"></i>
                        ' . (isset($SelectedTaxProvince) ? __('Update Jurisdiction') : __('Register Hub')) . '
                    </button>
                </div>
            </div>
          </div>
          </form>';

    if (!isset($SelectedTaxProvince)) {
        echo '<div style="padding: 40px; text-align: center; color: #065f46; border: 2px dashed #d1fae5; border-radius: 12px; background: #f0fdf4; margin-top:20px;">
                <i class="fas fa-earth-africa" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3 style="font-weight: 850; margin-bottom: 10px;">Register distinct tax jurisdictions</h3>
                <p style="font-size: 0.9rem; font-weight: 600; color: #059669;">Dispatch tax provinces allow the system to calculate specific rates based on where items are shipped from.</p>
              </div>';
    }

    echo '</main></div>'; // End Layout
echo '</div>'; // End Page

include(__DIR__ . '/includes/footer.php');
