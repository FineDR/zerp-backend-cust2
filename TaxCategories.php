<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Tax Categories');
$ViewTopic = 'Tax';
$BookMark = 'TaxCategories';
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
    
    /* Layout with RIGHT Registry Sidebar */
    .db-bottom-layout {
        display: grid;
        grid-template-columns: 1fr 380px;
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
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .arch-form-label { display: block; font-size: 0.72rem; font-weight: 900; color: #064e3b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
    .arch-form-input { width: 100%; height: 48px; border-radius: 8px; border: 1.5px solid #d1fae5; padding: 0 16px; font-weight: 600; font-size: 0.95rem; transition: border-color 0.2s; }
    .arch-form-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

    .list-item {
        padding: 16px 20px; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; cursor: pointer; display: flex; align-items: center; gap: 15px; text-decoration: none; color: inherit;
    }
    .list-item:hover { background: #f0fdf4; }
    .list-item.active { background: #ecfdf5; border-right: 4px solid #059669; padding-right: 16px; border-left: none; }

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

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr 320px; }
    }

    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .premium-header { position: relative; border-radius: 0; margin-left: calc(-1 * var(--page-padding)); margin-right: calc(-1 * var(--page-padding)); }
        .db-col-aside { order: 2; margin-top: 32px; }
        .db-col-main { order: 1; }
    }

    @media (max-width: 640px) {
        :root { --page-padding: 15px; }
        .premium-header-inner { flex-direction: column; align-items: flex-start; }
    }
</style>';

echo '<div class="db-page">
		<header class="premium-header">
			<div class="premium-header-inner">
                <div>
                    <div style="font-size: 0.75rem; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-boxes-stacked"></i> ' . __('Inventory Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem; opacity: 0.5;"></i> ' . __('Tax Categories') . '
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

$SelectedTaxCategory = isset($_GET['SelectedTaxCategory']) ? $_GET['SelectedTaxCategory'] : (isset($_POST['SelectedTaxCategory']) ? $_POST['SelectedTaxCategory'] : null);

// Logic Handling
if (isset($_POST['submit'])) {
	$InputError = 0;
	if (ContainsIllegalCharacters($_POST['TaxCategoryName']) || trim($_POST['TaxCategoryName']) == '') {
		$InputError = 1;
		prnMsg(__('Invalid characters or empty category name'), 'error');
	}

	if ($SelectedTaxCategory != '' && $InputError != 1) {
		$ClashSQL = "SELECT count(*) FROM taxcategories WHERE taxcatid <> '" . $SelectedTaxCategory ."' AND taxcatname ".LIKE." '" . $_POST['TaxCategoryName'] . "'";
		if (DB_fetch_row(DB_query($ClashSQL))[0] > 0) {
			prnMsg(__('Category name already exists'), 'error');
			$InputError = 1;
		} else {
			$OldNameResult = DB_query("SELECT taxcatname FROM taxcategories WHERE taxcatid = '" . $SelectedTaxCategory . "'");
			if (DB_num_rows($OldNameResult) > 0) {
				$OldName = DB_fetch_row($OldNameResult)[0];
				DB_query("UPDATE taxcategories SET taxcatname='" . $_POST['TaxCategoryName'] . "' WHERE taxcatname ".LIKE." '".$OldName."'");
				prnMsg(__('Tax category updated'), 'success');
				unset($SelectedTaxCategory, $_POST['SelectedTaxCategory'], $_POST['TaxCategoryName']);
			}
		}
	} elseif ($InputError != 1) {
		if (DB_fetch_row(DB_query("SELECT count(*) FROM taxcategories WHERE taxcatname ".LIKE." '".$_POST['TaxCategoryName'] ."'"))[0] > 0) {
			prnMsg(__('Category name already exists'), 'error');
		} else {
			DB_Txn_Begin();
			DB_query("INSERT INTO taxcategories (taxcatname) VALUES ('" . $_POST['TaxCategoryName'] ."')");
			$LastID = DB_Last_Insert_ID('taxcategories','taxcatid');
			DB_query("INSERT INTO taxauthrates (taxauthority, dispatchtaxprovince, taxcatid) SELECT taxid, taxprovinceid, '" . $LastID . "' FROM taxauthorities CROSS JOIN taxprovinces");
			DB_Txn_Commit();
			prnMsg(__('New tax category added'), 'success');
			unset($SelectedTaxCategory, $_POST['SelectedTaxCategory'], $_POST['TaxCategoryName']);
		}
	}
} elseif (isset($_GET['delete'])) {
	$UsageSQL = "SELECT COUNT(*) FROM stockmaster WHERE taxcatid = '" . $SelectedTaxCategory . "'";
	if (DB_fetch_row(DB_query($UsageSQL))[0] > 0) {
		prnMsg(__('Cannot delete category used by inventory items'), 'warn');
	} else {
		DB_query("DELETE FROM taxauthrates WHERE taxcatid = '" . $SelectedTaxCategory . "'");
		DB_query("DELETE FROM taxcategories WHERE taxcatid = '" . $SelectedTaxCategory . "'");
		prnMsg(__('Tax category deleted'), 'success');
		unset($SelectedTaxCategory, $_GET['SelectedTaxCategory']);
	}
}

echo '<div class="db-bottom-layout">
        <main class="db-col-main" style="min-height: 600px;">';

    if ($SelectedTaxCategory !== null && $SelectedTaxCategory !== '') {
        $r = DB_fetch_array(DB_query("SELECT taxcatid, taxcatname FROM taxcategories WHERE taxcatid='" . $SelectedTaxCategory . "'"));
        $_POST['TaxCategoryName'] = $r['taxcatname'];
        $formTitle = __('Category Master Profile');
        $formSubtitle = __('Configuring classification for ID') . ' ' . $SelectedTaxCategory;
    } else {
        if (!isset($_POST['TaxCategoryName'])) $_POST['TaxCategoryName'] = '';
        $formTitle = __('Define Tax Category');
        $formSubtitle = __('Create a new stock categorization for tax treatment');
    }

    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">';
    echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    if ($SelectedTaxCategory) echo '<input type="hidden" name="SelectedTaxCategory" value="' . $SelectedTaxCategory . '" />';

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <div>
                    <h3 class="arch-card-title"><i class="fas fa-tag" style="color:var(--primary);"></i> ' . $formTitle . '</h3>
                    <div style="font-size: 0.75rem; color: #6b7280; font-weight:600; margin-top:5px;">' . $formSubtitle . '</div>
                </div>';
    
    if ($SelectedTaxCategory && $_POST['TaxCategoryName'] != 'Freight') {
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxCategory=' . $SelectedTaxCategory . '&amp;delete=1" class="arch-btn" style="background:#fee2e2; color:#dc2626;" onclick="return confirm(\'' . __('Delete this tax category?') . '\');">
                <i class="fas fa-trash-alt"></i>
              </a>';
    }

    echo '  </div>
            <div class="db-card-body" style="padding:40px;">';

    if ($_POST['TaxCategoryName'] == 'Freight') {
        echo '<div style="background: #fffbef; border: 1px solid #fef3c7; padding: 20px; border-radius: 12px; margin-bottom: 30px; display: flex; gap: 15px; align-items: center;">
                <div style="width:40px; height:40px; background:#fef3c7; color:#92400e; display:flex; align-items:center; justify-content:center; border-radius:50%; font-size:1.1rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <div style="font-weight: 850; color: #92400e; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px;">' . __('System Reserved Category') . '</div>
                    <div style="font-size: 0.85rem; color: #b45309; font-weight:600;">' . __('The "Freight" category is a core system requirement and cannot be deleted or extensively modified.') . '</div>
                </div>
              </div>';
    }

    echo '      <div class="section-divider" style="margin-top:0;">
                    <i class="fas fa-signature"></i> <span class="section-title">' . __('Category Identity') . '</span>
                </div>
                <div class="arch-form-grid">
                    <div class="arch-form-field">
                        <label class="arch-form-label">' . __('Tax Category Name') . '</label>
                        <input type="text" name="TaxCategoryName" class="arch-form-input" required maxlength="30" value="' . $_POST['TaxCategoryName'] . '" placeholder="' . __('e.g. Taxable Standard') . '" />
                    </div>
                </div>';

    if ($SelectedTaxCategory) {
        $UsageCount = DB_fetch_row(DB_query("SELECT COUNT(*) FROM stockmaster WHERE taxcatid = '" . $SelectedTaxCategory . "'"))[0];
        
        echo '<div class="section-divider" style="margin-top:50px;">
                <i class="fas fa-chart-line"></i> <span class="section-title">' . __('Operational Usage') . '</span>
              </div>
              <div style="display:flex; gap:24px; background:#f9fafb; padding:24px; border-radius:12px; border:1px solid #f3f4f6; align-items:center;">
                <div style="width:56px; height:56px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:12px; font-size:1.4rem;">
                    <i class="fas fa-cube"></i>
                </div>
                <div>
                    <div style="font-size:0.7rem; font-weight:900; color:#065f46; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">' . __('Associated Inventory Items') . '</div>
                    <div style="font-size:1.2rem; font-weight:850; color:#111827;">' . $UsageCount . ' ' . __('Stock Items') . '</div>
                </div>
              </div>';
    }

    echo '      <div style="margin-top:50px; display:flex; justify-content:center;">
                    <button type="submit" name="submit" class="arch-btn" style="padding:16px 80px; font-size:1.05rem; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4);">
                        <i class="fas fa-check-double" style="margin-right:12px;"></i>
                        ' . ($SelectedTaxCategory ? __('Update Category') : __('Create Category')) . '
                    </button>
                </div>';

    echo '  </div>
          </div>
          </form>';

    if (!$SelectedTaxCategory) {
        echo '<div style="padding: 40px; text-align: center; color: #065f46; border: 2px dashed #d1fae5; border-radius: 12px; background: #f0fdf4; margin-top:20px;">
                <i class="fas fa-layer-group" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3 style="font-weight: 850; margin-bottom: 10px;">Classification Governance</h3>
                <p style="font-size: 0.9rem; font-weight: 600; color: #059669;">Tax categories allow you to group stock items for specific tax treatments across different jurisdictions.</p>
              </div>';
    }

    echo '  </main>

        <aside class="db-col-aside">
            <div class="arch-card" style="position: sticky; top: 100px;">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-tags"></i> ' . __('Category Registry') . '</h3>
                </div>
                <div class="db-card-body" style="padding:0; max-height: calc(100vh - 250px); overflow-y: auto;">';

    $Result = DB_query("SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatid");
    while($MyRow = DB_fetch_array($Result)) {
        $isActive = ($SelectedTaxCategory !== null && $SelectedTaxCategory == $MyRow['taxcatid']) ? 'active' : '';
        $catName = (trim($MyRow['taxcatname']) == '' ? __('Unnamed Category') : __($MyRow['taxcatname']));
        echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTaxCategory=' . $MyRow['taxcatid'] . '" class="list-item ' . $isActive . '">
                <div style="width:32px; height:32px; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:800; font-size:0.75rem;">' . (int)$MyRow['taxcatid'] . '</div>
                <div style="flex:1;">
                    <div style="font-weight: 800; font-size: 0.85rem; color:#111827;">' . htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') . '</div>
                </div>
                <i class="fas fa-chevron-right" style="color:#9ca3af; font-size:0.7rem;"></i>
              </a>';
    }

    echo '      </div>
                <div style="padding: 20px; background: #f9fafb; border-top: 1px solid #f3f4f6;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">
                        <i class="fas fa-plus"></i> ' . __('Add New Category') . '
                    </a>
                </div>
            </div>
        </aside>
    </div>'; // End db-bottom-layout
echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
