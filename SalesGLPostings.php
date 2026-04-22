<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales GL Postings Set Up');
$ViewTopic = 'CreatingNewSystem';
$BookMark = 'SalesGLPostings';

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
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 1200px; table-layout: fixed; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    table.modern-table th:nth-child(1), table.modern-table td:nth-child(1) { width: 80px; }
    table.modern-table th:nth-child(2), table.modern-table td:nth-child(2) { width: 150px; }
    table.modern-table th:nth-child(3), table.modern-table td:nth-child(3) { width: 120px; }
    table.modern-table th:nth-child(4), table.modern-table td:nth-child(4) { width: auto; }
    table.modern-table th:nth-child(5), table.modern-table td:nth-child(5) { width: auto; }
    table.modern-table th:nth-child(6), table.modern-table td:nth-child(6) { width: 80px; text-align: right; }

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

if (isset($_GET['SelectedSalesPostingID'])){
	$SelectedSalesPostingID =$_GET['SelectedSalesPostingID'];
} elseif (isset($_POST['SelectedSalesPostingID'])){
	$SelectedSalesPostingID =$_POST['SelectedSalesPostingID'];
}

$InputError=false;

if (isset($_POST['submit'])) {
	if (isset($SelectedSalesPostingID)) {
		$SQL = "UPDATE salesglpostings SET salesglcode = '" . $_POST['SalesGLCode'] . "', discountglcode = '" . $_POST['DiscountGLCode'] . "', area = '" . $_POST['Area'] . "', stkcat = '" . $_POST['StkCat'] . "', salestype = '" . $_POST['SalesType'] . "' WHERE salesglpostings.id = '".$SelectedSalesPostingID."'";
		$Msg = __('The sales GL posting record has been updated');
	} else {
		$SQL_count = "SELECT count(*) FROM salesglpostings WHERE area='" . $_POST['Area'] . "' AND stkcat='" . $_POST['StkCat'] . "' AND salestype='" . $_POST['SalesType'] . "'";
		$CountRow = DB_fetch_row(DB_query($SQL_count));
		if ($CountRow[0] == 0) {
			$SQL = "INSERT INTO salesglpostings (salesglcode, discountglcode, area, stkcat, salestype) VALUES ('" . $_POST['SalesGLCode'] . "', '" . $_POST['DiscountGLCode'] . "', '" . $_POST['Area'] . "', '" . $_POST['StkCat'] . "', '" . $_POST['SalesType'] . "')";
			$Msg = __('The new sales GL posting record has been inserted');
		} else {
			prnMsg(__('A sales gl posting account already exists for the selected area, stock category, salestype'),'warn');
			$InputError = true;
		}
	}
	if ($InputError==false){
		DB_query($SQL);
		prnMsg($Msg,'success');
	}
	unset ($SelectedSalesPostingID); unset($_POST['SalesGLCode']); unset($_POST['DiscountGLCode']); unset($_POST['Area']); unset($_POST['StkCat']); unset($_POST['SalesType']);
} elseif (isset($_GET['delete'])) {
	$SQL="DELETE FROM salesglpostings WHERE id='".$SelectedSalesPostingID."'";
	DB_query($SQL);
	prnMsg( __('Sales posting record has been deleted'),'success');
	unset ($SelectedSalesPostingID);
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
                        ' . __('Sales GL Setup') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="sales-gl-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedSalesPostingID) ? __('Update Mapping') : __('Create Mapping')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                // Initial check logic for default records (Legacy parity)
                $Count_rules = DB_fetch_row(DB_query("SELECT COUNT(*) FROM salesglpostings"))[0];
                if ($Count_rules == 0) {
                     $CheckGL = DB_query("SELECT accountcode FROM chartmaster WHERE accountcode ='1'");
                     if (DB_num_rows($CheckGL)==0){
                         DB_query("INSERT INTO chartmaster (accountcode, accountname, group_) SELECT '1', 'Default Sales/Discounts', groupname FROM accountgroups WHERE pandl=1 LIMIT 1");
                     }
                     DB_query("INSERT INTO salesglpostings (area, stkcat, salestype, salesglcode, discountglcode) VALUES ('AN', 'ANY', 'AN', '1', '1')");
                }

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-link"></i> ' . __('Revenue & Discount Mapping') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Area') . '</th>
                                    <th>' . __('Stock Category') . '</th>
                                    <th>' . __('Sales Type') . '</th>
                                    <th>' . __('Revenue Account') . '</th>
                                    <th>' . __('Discount Account') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            $SQL_list = "SELECT salesglpostings.id, salesglpostings.area, salesglpostings.stkcat, salesglpostings.salestype, chart1.accountname as sname, chart2.accountname as dname FROM salesglpostings LEFT JOIN chartmaster as chart1 ON salesglpostings.salesglcode = chart1.accountcode LEFT JOIN chartmaster as chart2 ON salesglpostings.discountglcode = chart2.accountcode ORDER BY salesglpostings.area, salesglpostings.stkcat, salesglpostings.salestype";
                            $Result_list = DB_query($SQL_list);
                            while ($MyRow = DB_fetch_array($Result_list)) {
                                echo '<tr>
                                        <td><span class="badge badge-secondary">', $MyRow['area'], '</span></td>
                                        <td><span class="badge badge-emerald">', $MyRow['stkcat'], '</span></td>
                                        <td><span class="badge badge-secondary">', $MyRow['salestype'], '</span></td>
                                        <td style="font-weight: 600; color: #064e3b;">', ($MyRow['sname'] ?? '<span style="color:#dc2626;">INVALID</span>'), '</td>
                                        <td style="font-size:0.8rem; opacity:0.7;">', $MyRow['dname'], '</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedSalesPostingID=', $MyRow['id'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedSalesPostingID=', $MyRow['id'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedSalesPostingID)) {
                    $SQL_sel = "SELECT * FROM salesglpostings WHERE id='".$SelectedSalesPostingID."'";
                    $MyRow = DB_fetch_array(DB_query($SQL_sel));
                    $_POST['SalesGLCode']= $MyRow['salesglcode'];
                    $_POST['DiscountGLCode']= $MyRow['discountglcode'];
                    $_POST['Area']=$MyRow['area'];
                    $_POST['StkCat']=$MyRow['stkcat'];
                    $_POST['SalesType']=$MyRow['salestype'];
                }

echo '          <form id="sales-gl-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedSalesPostingID)) { echo '<input type="hidden" name="SelectedSalesPostingID" value="' . $SelectedSalesPostingID . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedSalesPostingID) ? __('Edit Posting Rule') : __('New Posting Rule')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Sales Area') . '</label>
                                <select name="Area">
                                    <option value="AN">' . __('Any Other') . '</option>';
                                    $Res_areas = DB_query("SELECT areacode, areadescription FROM areas");
                                    while ($MyRow = DB_fetch_array($Res_areas)) {
                                        echo '<option ' . ((isset($_POST['Area']) && $MyRow['areacode']==$_POST['Area']) ? 'selected' : '') . ' value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Stock Category') . '</label>
                                <select name="StkCat">
                                    <option value="ANY">' . __('Any Other') . '</option>';
                                    $Res_cats = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
                                    while ($MyRow = DB_fetch_array($Res_cats)) {
                                        echo '<option ' . ((isset($_POST['StkCat']) && $MyRow['categoryid']==$_POST['StkCat']) ? 'selected' : '') . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Sales Type / Price List') . '</label>
                                <select name="SalesType">
                                    <option value="AN">' . __('Any Other') . '</option>';
                                    $Res_stypes = DB_query("SELECT typeabbrev, sales_type FROM salestypes");
                                    while ($MyRow = DB_fetch_array($Res_stypes)) {
                                        echo '<option ' . ((isset($_POST['SalesType']) && $MyRow['typeabbrev']==$_POST['SalesType']) ? 'selected' : '') . ' value="' . $MyRow['typeabbrev'] . '">' .  $MyRow['sales_type'] . '</option>';
                                    }
echo '                          </select>
                            </field>
                            
                            <h4 style="font-size:0.65rem; font-weight:850; color:#64748b; margin:25px 0 12px 0; text-transform:uppercase;">' . __('General Ledger Links') . '</h4>
                            <field>
                                <label>' . __('Sales Revenue Account') . '</label>
                                <select name="SalesGLCode">';
                                    $Res_gl = DB_query("SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY accountgroups.sequenceintb, chartmaster.accountcode");
                                    while ($MyRow = DB_fetch_array($Res_gl)) {
                                        echo '<option ' . ((isset($_POST['SalesGLCode']) && $MyRow['accountcode']==$_POST['SalesGLCode']) ? 'selected' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - '  . htmlspecialchars($MyRow['accountname'],ENT_QUOTES,'UTF-8') . '</option>';
                                    }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Sales Discount Account') . '</label>
                                <select name="DiscountGLCode">';
                                    DB_data_seek($Res_gl, 0);
                                    while ($MyRow = DB_fetch_array($Res_gl)) {
                                        echo '<option ' . ((isset($_POST['DiscountGLCode']) && $MyRow['accountcode']==$_POST['DiscountGLCode']) ? 'selected' : '') . ' value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - '  . htmlspecialchars($MyRow['accountname'],ENT_QUOTES,'UTF-8') . '</option>';
                                    }
echo '                          </select>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:20px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedSalesPostingID) ? __('Update Definition') : __('Save Definition')) . '
                            </button>
                            ' . (isset($SelectedSalesPostingID) ? '<div style="text-align:center; margin-top:15px;"><a href="SalesGLPostings.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
