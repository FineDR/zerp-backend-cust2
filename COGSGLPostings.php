<?php

/* Defines the general ledger account to be used for cost of sales entries */

require(__DIR__ . '/includes/session.php');

$Title = __('Cost Of Sales GL Postings Set Up');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'COGSGLPostings';

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
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 1100px; table-layout: fixed; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    table.modern-table th:nth-child(1), table.modern-table td:nth-child(1) { width: 80px; }
    table.modern-table th:nth-child(2), table.modern-table td:nth-child(2) { width: 150px; }
    table.modern-table th:nth-child(3), table.modern-table td:nth-child(3) { width: 120px; }
    table.modern-table th:nth-child(4), table.modern-table td:nth-child(4) { width: auto; }
    table.modern-table th:nth-child(5), table.modern-table td:nth-child(5) { width: 80px; text-align: right; }

    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }
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

$SelectedCOGSPostingID = 0;
if (isset($_POST['SelectedCOGSPostingID'])){
	$SelectedCOGSPostingID=$_POST['SelectedCOGSPostingID'];
} elseif (isset($_GET['SelectedCOGSPostingID'])){
	$SelectedCOGSPostingID=$_GET['SelectedCOGSPostingID'];
}

if (isset($_POST['submit'])) {
	if (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID > 0) {
		$SQL = "UPDATE cogsglpostings SET glcode = '" . $_POST['GLCode'] . "', area = '" . $_POST['Area'] . "', stkcat = '" . $_POST['StkCat'] . "', salestype='" . $_POST['SalesType'] . "' WHERE id ='" .$SelectedCOGSPostingID."'";
		$Msg = __('Cost of sales GL posting code has been updated');
	} else {
		$SQL = "INSERT INTO cogsglpostings (glcode, area, stkcat, salestype) VALUES ('" . $_POST['GLCode'] . "', '" . $_POST['Area'] . "', '" . $_POST['StkCat'] . "', '" . $_POST['SalesType'] . "')";
		$Msg = __('A new cost of sales posting code has been inserted');
	}
	DB_query($SQL);
	prnMsg($Msg,'success');
	unset ($SelectedCOGSPostingID);
} elseif (isset($_GET['delete'])) {
	$SQL="DELETE FROM cogsglpostings WHERE id='".$SelectedCOGSPostingID."'";
	DB_query($SQL);
	prnMsg( __('The cost of sales posting code record has been deleted'),'success');
	unset ($SelectedCOGSPostingID);
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
                        ' . __('COGS Setup') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="cogs-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID > 0 ? __('Update Mapping') : __('Create Mapping')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

            if (!isset($SelectedCOGSPostingID) || $SelectedCOGSPostingID == 0) {
                // Initial check logic for default records
                $Count_matrix = DB_fetch_row(DB_query("SELECT COUNT(*) FROM cogsglpostings"))[0];
                if ($Count_matrix == 0) {
                     // Ensure account 1 exists (Legacy behavior)
                     $CheckRes = DB_query("SELECT accountcode FROM chartmaster WHERE accountcode ='1'");
                     if (DB_num_rows($CheckRes)==0){
                         DB_query("INSERT INTO chartmaster (accountcode, accountname, group_) SELECT '1', 'Default Sales/Discounts', groupname FROM accountgroups WHERE pandl=1 LIMIT 1");
                     }
                     DB_query("INSERT INTO cogsglpostings (area, stkcat, salestype, glcode) VALUES ('AN', 'ANY', 'AN', '1')");
                }
            }

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-project-diagram"></i> ' . __('Posting Definitions') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Area') . '</th>
                                    <th>' . __('Stock Category') . '</th>
                                    <th>' . __('Sales Type') . '</th>
                                    <th>' . __('GL Account') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            $SQL_list = "SELECT cogsglpostings.id, cogsglpostings.area, cogsglpostings.stkcat, cogsglpostings.salestype, chartmaster.accountname FROM cogsglpostings LEFT JOIN chartmaster ON cogsglpostings.glcode = chartmaster.accountcode ORDER BY cogsglpostings.area, cogsglpostings.stkcat, cogsglpostings.salestype";
                            $Result_list = DB_query($SQL_list);
                            while ($MyRow = DB_fetch_array($Result_list)) {
                                echo '<tr>
                                        <td><span class="badge badge-secondary">', $MyRow['area'], '</span></td>
                                        <td><span class="badge badge-emerald">', $MyRow['stkcat'], '</span></td>
                                        <td><span class="badge badge-secondary">', $MyRow['salestype'], '</span></td>
                                        <td style="font-weight: 600; color: #064e3b;">', ($MyRow['accountname'] ?? '<span style="color:#dc2626;">INVALID CODE</span>'), '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedCOGSPostingID=', $MyRow['id'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedCOGSPostingID=', $MyRow['id'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID != 0) {
                    $SQL_sel = "SELECT stkcat, glcode, area, salestype FROM cogsglpostings WHERE id='".$SelectedCOGSPostingID."'";
                    $MyRow = DB_fetch_array(DB_query($SQL_sel));
                    $_POST['GLCode'] = $MyRow['glcode'];
                    $_POST['Area'] = $MyRow['area'];
                    $_POST['StkCat'] = $MyRow['stkcat'];
                    $_POST['SalesType'] = $MyRow['salestype'];
                }

echo '          <form id="cogs-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID != 0) { echo '<input type="hidden" name="SelectedCOGSPostingID" value="' . $SelectedCOGSPostingID . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-filter"></i> ' . (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID != 0 ? __('Edit Mapping') : __('New Mapping Criterion')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Sales Area') . '</label>
                                <select name="Area" autofocus>
                                    <option value="AN">' . __('Any Other') . '</option>';
                                    $Res_areas = DB_query("SELECT areacode, areadescription FROM areas");
                                    while ($MyRow = DB_fetch_array($Res_areas)) {
                                        echo '<option ' . ((isset($_POST['Area']) && $MyRow['areacode'] == $_POST['Area']) ? 'selected' : '') . ' value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Stock Category') . '</label>
                                <select name="StkCat">
                                    <option value="ANY">' . __('Any Other') . '</option>';
                                    $Res_cats = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
                                    while ($MyRow = DB_fetch_array($Res_cats)) {
                                        echo '<option ' . ((isset($_POST['StkCat']) && $MyRow['categoryid'] == $_POST['StkCat']) ? 'selected' : '') . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Sales Type / Price List') . '</label>
                                <select name="SalesType">
                                    <option value="AN">' . __('Any Other') . '</option>';
                                    $Res_stypes = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
                                    while ($MyRow = DB_fetch_array($Res_stypes)) {
                                        echo '<option ' . ((isset($_POST['SalesType']) && $MyRow['typeabbrev'] == $_POST['SalesType']) ? 'selected' : '') . ' value="' . $MyRow['typeabbrev'] . '">' . $MyRow['sales_type'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('COGS GL Account') . '</label>
                                <select name="GLCode">';
                                    $Res_gl = DB_query("SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY accountgroups.sequenceintb, chartmaster.accountcode");
                                    while ($MyRow = DB_fetch_array($Res_gl)) {
                                        echo '<option ' . ((isset($_POST['GLCode']) && $MyRow['accountcode']==$_POST['GLCode']) ? 'selected' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - '  . htmlspecialchars($MyRow['accountname'],ENT_QUOTES,'UTF-8') . '</option>';
                                    }
echo '                          </select>
                                <span class="fieldhelp">' . __('Account to debit for cost of goods sold') . '</span>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID != 0 ? __('Update COGS Mapping') : __('Save COGS Mapping')) . '
                            </button>
                            ' . (isset($SelectedCOGSPostingID) && $SelectedCOGSPostingID != 0 ? '<div style="text-align:center; margin-top:15px;"><a href="COGSGLPostings.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
