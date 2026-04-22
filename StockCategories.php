<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Inventory Categories Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryCategories';

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
        width: 100%; border-radius: 10px; height: 40px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }

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
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.62rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }

    .badge {
        display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    .accounts-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

// Logic Part
$StockTypeName = array();
$StockTypeName['D'] = __('Dummy Item - (No Movements)');
$StockTypeName['F'] = __('Finished Goods');
$StockTypeName['L'] = __('Labour');
$StockTypeName['M'] = __('Raw Materials');
asort($StockTypeName);

$TaxCategoryName = array();
$Query = "SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatname";
$Result = DB_query($Query);
if (DB_num_rows($Result) == 0) {
	prnMsg(__('There are no Tax Categories defined for this company. To define Tax Categories click') . ' ' . '<a href="'.$RootPath.'/TaxCategories.php" target="_blank">' . __('here'). '</a>', 'warn');
}
while ($Row = DB_fetch_array($Result)) {
	$TaxCategoryName[$Row['taxcatid']] = $Row['taxcatname'];
}

if (isset($_GET['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_GET['SelectedCategory']);
} elseif (isset($_POST['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_POST['SelectedCategory']);
}

if (isset($_GET['DeleteProperty'])){
	$ErrMsg = __('Could not delete the property') . ' ' . $_GET['DeleteProperty'] . ' ' . __('because');
	$SQL = "DELETE FROM stockitemproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	DB_query($SQL, $ErrMsg);
	$SQL = "DELETE FROM stockcatproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	DB_query($SQL, $ErrMsg);
	prnMsg(__('Deleted the property') . ' ' . $_GET['DeleteProperty'],'success');
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	$_POST['CategoryID'] = mb_strtoupper($_POST['CategoryID']);
	if (mb_strlen($_POST['CategoryID']) > 6) {
		$InputError = 1;
		prnMsg(__('The Inventory Category code must be six characters or less long'),'error');
	} elseif (mb_strlen($_POST['CategoryID'])==0) {
		$InputError = 1;
		prnMsg(__('The Inventory category code must be at least 1 character but less than six characters long'),'error');
	} elseif (mb_strlen($_POST['CategoryDescription']) >20 or mb_strlen($_POST['CategoryDescription'])==0) {
		$InputError = 1;
		prnMsg(__('The Sales category description must be twenty characters or less long and cannot be zero'),'error');
	}
	for ($i=0;$i<=$_POST['PropertyCounter'];$i++){
		if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
			if (!is_numeric(filter_number_format($_POST['PropMinimum' .$i]))){
				$InputError = 1;
				prnMsg(__('The minimum value for property') . ' ' . $_POST['PropLabel'.$i] . ' ' . __('is expected to be a numeric value'),'error');
			}
			if (!is_numeric(filter_number_format($_POST['PropMaximum' .$i]))){
				$InputError = 1;
				prnMsg(__('The maximum value for property') . ' ' . $_POST['PropLabel'.$i] . ' ' . __('is expected to be a numeric value'),'error');
			}
		}
	}

	if (isset($SelectedCategory) AND $InputError !=1) {
		$SQL = "UPDATE stockcategory SET stocktype = '" . $_POST['StockType'] . "', categorydescription = '" . $_POST['CategoryDescription'] . "', defaulttaxcatid = '" . $_POST['DefaultTaxCatID'] . "', stockact = '" . $_POST['StockAct'] . "', adjglact = '" . $_POST['AdjGLAct'] . "', issueglact = '" . $_POST['IssueGLAct'] . "', purchpricevaract = '" . $_POST['PurchPriceVarAct'] . "', materialuseagevarac = '" . $_POST['MaterialUseageVarAc'] . "', wipact = '" . $_POST['WIPAct'] . "' WHERE categoryid = '" . $SelectedCategory. "'";
		$ErrMsg = __('Could not update the stock category') . $_POST['CategoryDescription'] . __('because');
		$Result = DB_query($SQL, $ErrMsg);

		for ($i=0;$i<=$_POST['PropertyCounter'];$i++){
			$PropReqSO = (isset($_POST['PropReqSO' .$i]) and $_POST['PropReqSO' .$i] == true) ? 1 : 0;
			$PropNumeric = (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true) ? 1 : 0;
			$PropMin = (isset($_POST['PropMinimum' . $i]) && $_POST['PropMinimum' . $i] !== '') ? filter_number_format($_POST['PropMinimum' . $i]) : '-999999999';
			$PropMax = (isset($_POST['PropMaximum' . $i]) && $_POST['PropMaximum' . $i] !== '') ? filter_number_format($_POST['PropMaximum' . $i]) : '999999999';

			if ($_POST['PropID' .$i] =='NewProperty' AND mb_strlen($_POST['PropLabel'.$i])>0){
				$SQL = "INSERT INTO stockcatproperties (categoryid, label, controltype, defaultvalue, minimumvalue, maximumvalue, numericvalue, reqatsalesorder) VALUES ('" . $SelectedCategory . "', '" . $_POST['PropLabel' . $i] . "', " . $_POST['PropControlType' . $i] . ", '" . $_POST['PropDefault' .$i] . "', '" . $PropMin . "', '" . $PropMax . "', '" . $PropNumeric . "', " . $PropReqSO . ")";
				DB_query($SQL);
			} elseif ($_POST['PropID' .$i] !='NewProperty') {
				$SQL = "UPDATE stockcatproperties SET label ='" . $_POST['PropLabel' . $i] . "', controltype = " . $_POST['PropControlType' . $i] . ", defaultvalue = '"	. $_POST['PropDefault' .$i] . "', minimumvalue = '" . $PropMin . "', maximumvalue = '" . $PropMax . "', numericvalue = '" . $PropNumeric . "', reqatsalesorder = " . $PropReqSO . " WHERE stkcatpropid =" . $_POST['PropID' .$i];
				DB_query($SQL);
			}
		}
		prnMsg(__('Updated the stock category record for') . ' ' . $_POST['CategoryDescription'],'success');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO stockcategory (categoryid, stocktype, categorydescription, defaulttaxcatid, stockact, adjglact, issueglact, purchpricevaract, materialuseagevarac, wipact) VALUES ('" . $_POST['CategoryID'] . "','" . $_POST['StockType'] . "','" . $_POST['CategoryDescription'] . "','" . $_POST['DefaultTaxCatID'] . "','" . $_POST['StockAct'] . "','" . $_POST['AdjGLAct'] . "','" . $_POST['IssueGLAct'] . "','" . $_POST['PurchPriceVarAct'] . "','" . $_POST['MaterialUseageVarAc'] . "','" . $_POST['WIPAct'] . "')";
		$ErrMsg = __('Could not insert the new stock category') . $_POST['CategoryDescription'] . __('because');
		DB_query($SQL, $ErrMsg);
		prnMsg(__('A new stock category record has been added for') . ' ' . $_POST['CategoryDescription'],'success');
	}
	unset($_POST['StockType']); unset($_POST['CategoryDescription']); unset($_POST['StockAct']); unset($_POST['AdjGLAct']); unset($_POST['IssueGLAct']); unset($_POST['PurchPriceVarAct']); unset($_POST['MaterialUseageVarAc']); unset($_POST['WIPAct']);
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT stockid FROM stockmaster WHERE stockmaster.categoryid='" . $SelectedCategory . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result)>0) {
		prnMsg(__('Cannot delete this stock category because stock items have been created using this stock category'),'warn');
	} else {
		$SQL = "SELECT stkcat FROM salesglpostings WHERE stkcat='" . $SelectedCategory . "'";
		$PostRes = DB_query($SQL);
		if (DB_num_rows($PostRes)>0) {
			prnMsg(__('Cannot delete this stock category because it is used by the sales GL posting interface'),'warn');
		} else {
			DB_query("DELETE FROM stockcategory WHERE categoryid='" . $SelectedCategory . "'");
			prnMsg(__('The stock category') . ' ' . $SelectedCategory . ' ' . __('has been deleted'),'success');
			unset ($SelectedCategory);
		}
	}
}

// UI Part
echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=stock">' . __('Inventory') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Stock Categories') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="CategoryForm" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedCategory) ? __('Update Category') : __('Create Category')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

            if (!isset($SelectedCategory)) {
                $SQL = "SELECT categoryid, categorydescription, stocktype, defaulttaxcatid FROM stockcategory ORDER BY categoryid";
                $Result = DB_query($SQL);
echo '          <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Inventory Categories') . '</h3></div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Description') . '</th>
                                    <th>' . __('Type') . '</th>
                                    <th>' . __('Tax Category') . '</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<tr>
                                        <td style="font-weight:700; color:#059669;">' . $MyRow['categoryid'] . '</td>
                                        <td style="font-weight:600;">' . $MyRow['categorydescription'] . '</td>
                                        <td><span class="badge badge-secondary">' . $StockTypeName[$MyRow['stocktype']] . '</span></td>
                                        <td>' . $TaxCategoryName[$MyRow['defaulttaxcatid']] . '</td>
                                        <td style="text-align:right; white-space:nowrap;">
                                            <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $MyRow['categoryid'] . '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $MyRow['categoryid'] . '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>';
            }

            if (isset($SelectedCategory)) {
                $PropRes = DB_query("SELECT stkcatpropid, label, controltype, defaultvalue, numericvalue, reqatsalesorder, minimumvalue, maximumvalue FROM stockcatproperties WHERE categoryid='" . $SelectedCategory . "' ORDER BY stkcatpropid");
echo '          <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-tags"></i> ' . __('Extended Properties') . '</h3></div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Prop Label') . '</th>
                                    <th>' . __('Type') . '</th>
                                    <th>' . __('Default') . '</th>
                                    <th style="text-align:center;">' . __('Num') . '</th>
                                    <th>' . __('Range') . '</th>
                                    <th style="text-align:center;">' . __('SO Req') . '</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            $PropertyCounter = 0;
                            while ($PRow = DB_fetch_array($PropRes)) {
                                renderCategoryPropertyRow($PropertyCounter, $PRow);
                                $PropertyCounter++;
                            }
                            renderCategoryPropertyRow($PropertyCounter, null); // New row
echo '                      </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="PropertyCounter" form="CategoryForm" value="' . $PropertyCounter . '" />
                </div>';
            }
echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedCategory)) {
                    if (!isset($_POST['UpdateTypes'])) {
                        $SQL = "SELECT categoryid, stocktype, categorydescription, stockact, adjglact, issueglact, purchpricevaract, materialuseagevarac, wipact, defaulttaxcatid FROM stockcategory WHERE categoryid='" . $SelectedCategory . "'";
                        $Result = DB_query($SQL);
                        $MyRow = DB_fetch_array($Result);
                        $_POST['CategoryID'] = $MyRow['categoryid'];
                        $_POST['StockType']  = $MyRow['stocktype'];
                        $_POST['CategoryDescription']  = $MyRow['categorydescription'];
                        $_POST['StockAct']  = $MyRow['stockact'];
                        $_POST['AdjGLAct']  = $MyRow['adjglact'];
                        $_POST['IssueGLAct']  = $MyRow['issueglact'];
                        $_POST['PurchPriceVarAct']  = $MyRow['purchpricevaract'];
                        $_POST['MaterialUseageVarAc']  = $MyRow['materialuseagevarac'];
                        $_POST['WIPAct']  = $MyRow['wipact'];
                        $_POST['DefaultTaxCatID']  = $MyRow['defaulttaxcatid'];
                    }
                }

echo '          <form id="CategoryForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedCategory)) {
                        echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';
                        echo '<input type="hidden" name="CategoryID" value="' . $_POST['CategoryID'] . '" />';
                    }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedCategory) ? __('Edit Configuration') : __('New Configuration')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Category Code') . '</label>
                                <input type="text" name="CategoryID" required maxlength="6" value="' . ($_POST['CategoryID'] ?? '') . '" ' . (isset($SelectedCategory) ? 'disabled' : '') . ' placeholder="e.g. METAL" />
                            </field>
                            <field>
                                <label>' . __('Description') . '</label>
                                <input type="text" name="CategoryDescription" required maxlength="20" value="' . ($_POST['CategoryDescription'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Stock Type') . '</label>
                                <select name="StockType" onchange="this.form.UpdateTypes.click()">';
                                foreach ($StockTypeName as $STypeId => $STypeName) {
                                    echo '<option ' . ((isset($_POST['StockType']) && $_POST['StockType'] == $STypeId) ? 'selected' : '') . ' value="' . $STypeId . '">' . $STypeName . '</option>';
                                }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Default Tax Category') . '</label>
                                <select name="DefaultTaxCatID">';
                                foreach ($TaxCategoryName as $TId => $TName) {
                                    echo '<option ' . (($_POST['DefaultTaxCatID'] ?? $_SESSION['DefaultTaxCategory']) == $TId ? 'selected' : '') . ' value="' . $TId . '">' . $TName . '</option>';
                                }
echo '                          </select>
                            </field>

                            <h4 style="font-size:0.65rem; font-weight:850; color:#64748b; margin:25px 0 12px 0; text-transform:uppercase;">' . __('Financial Accounts') . '</h4>
                            <div class="accounts-grid">';
                                $BSRes = DB_query("SELECT accountcode, accountname FROM chartmaster LEFT JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 ORDER BY accountcode");
                                $PnLRes = DB_query("SELECT accountcode, accountname FROM chartmaster LEFT JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY accountcode");
                                
                                $StockLabel = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? __('Recovery GL') : __('Stock GL');
                                $AccRes = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? $PnLRes : $BSRes;
                                
echo '                          <field><label>' . $StockLabel . '</label><select name="StockAct">';
                                    while ($ARow = DB_fetch_array($AccRes)) { echo '<option ' . (($_POST['StockAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
echo '                          </select></field>';
                                
                                DB_data_seek($BSRes, 0);
echo '                          <field><label>' . __('WIP GL Code') . '</label><select name="WIPAct">';
                                    while ($ARow = DB_fetch_array($BSRes)) { echo '<option ' . (($_POST['WIPAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
echo '                          </select></field>';

                                $UsageLabel = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? __('Efficiency Var GL') : __('Usage Var GL');
                                DB_data_seek($PnLRes, 0);
echo '                          <field><label>' . $UsageLabel . '</label><select name="MaterialUseageVarAc">';
                                    while ($ARow = DB_fetch_array($PnLRes)) { echo '<option ' . (($_POST['MaterialUseageVarAc'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
echo '                          </select></field>';

                                if (isset($_POST['StockType']) && $_POST['StockType'] != 'L' && $_POST['StockType'] != 'D') {
                                    DB_data_seek($PnLRes, 0);
                                    echo '<field><label>' . __('Adjustments GL') . '</label><select name="AdjGLAct">';
                                    while ($ARow = DB_fetch_array($PnLRes)) { echo '<option ' . (($_POST['AdjGLAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
                                    echo '</select></field>';
                                    DB_data_seek($PnLRes, 0);
                                    echo '<field><label>' . __('Issues GL') . '</label><select name="IssueGLAct">';
                                    while ($ARow = DB_fetch_array($PnLRes)) { echo '<option ' . (($_POST['IssueGLAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
                                    echo '</select></field>';
                                    DB_data_seek($PnLRes, 0);
                                    echo '<field><label>' . __('Price Variance GL') . '</label><select name="PurchPriceVarAct">';
                                    while ($ARow = DB_fetch_array($PnLRes)) { echo '<option ' . (($_POST['PurchPriceVarAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . '</option>'; }
                                    echo '</select></field>';
                                } else {
                                    echo '<input type="hidden" name="AdjGLAct" value="1" /><input type="hidden" name="IssueGLAct" value="1" /><input type="hidden" name="PurchPriceVarAct" value="1" />';
                                }
echo '                      </div>

                            <div style="margin-top:20px;">
                                <button type="submit" name="submit" class="architect-btn" style="width: 100%;">
                                    <i class="fas fa-check-circle"></i> ' . (isset($SelectedCategory) ? __('Update Category') : __('Add Category')) . '
                                </button>
                                ' . (isset($SelectedCategory) ? '<div style="text-align:center; margin-top:10px;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="font-size:0.75rem; color:#6b7280; text-decoration:none; font-weight:700;">' . __('View All List') . '</a></div>' : '') . '
                            </div>
                        </div>
                    </div>
                    <input type="submit" name="UpdateTypes" style="display:none;" />
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');

function renderCategoryPropertyRow($i, $row) {
    global $SelectedCategory;
	$isNew = ($row === null);
	$id = $isNew ? 'NewProperty' : $row['stkcatpropid'];
	$label = $isNew ? '' : $row['label'];
	$cType = $isNew ? 0 : $row['controltype'];
	$default = $isNew ? '' : $row['defaultvalue'];
	$numeric = $isNew ? 0 : $row['numericvalue'];
	$reqSO = $isNew ? 0 : $row['reqatsalesorder'];
	$min = $isNew ? '' : $row['minimumvalue'];
	$max = $isNew ? '' : $row['maximumvalue'];

	echo '<tr>
			<td>
				<input type="hidden" name="PropID' . $i . '" form="CategoryForm" value="' . $id . '" />
				<input type="text" name="PropLabel' . $i . '" form="CategoryForm" class="db-input" style="height:32px; font-size:0.8rem;" value="' . $label . '" placeholder="' . ($isNew ? __('Label...') : '') . '" />
			</td>
			<td>
				<select name="PropControlType' . $i . '" form="CategoryForm" class="db-select" style="height:32px; font-size:0.75rem;">
					<option value="0" ' . ($cType == 0 ? 'selected' : '') . '>' . __('Text Box') . '</option>
					<option value="1" ' . ($cType == 1 ? 'selected' : '') . '>' . __('Select Box') . '</option>
					<option value="2" ' . ($cType == 2 ? 'selected' : '') . '>' . __('Check Box') . '</option>
					<option value="3" ' . ($cType == 3 ? 'selected' : '') . '>' . __('Date Box') . '</option>
				</select>
			</td>
			<td><input type="text" name="PropDefault' . $i . '" form="CategoryForm" class="db-input" style="height:32px; font-size:0.8rem;" value="' . $default . '" /></td>
			<td style="text-align:center;"><input type="checkbox" name="PropNumeric' . $i . '" form="CategoryForm" ' . ($numeric ? 'checked' : '') . ' /></td>
			<td>
				<div style="display: flex; gap: 4px;">
					<input type="text" name="PropMinimum' . $i . '" form="CategoryForm" class="db-input" style="height:32px; width:50px; font-size:0.7rem;" value="' . $min . '" placeholder="Min" />
					<input type="text" name="PropMaximum' . $i . '" form="CategoryForm" class="db-input" style="height:32px; width:50px; font-size:0.7rem;" value="' . $max . '" placeholder="Max" />
				</div>
			</td>
			<td style="text-align:center;"><input type="checkbox" name="PropReqSO' . $i . '" form="CategoryForm" ' . ($reqSO ? 'checked' : '') . ' /></td>
			<td style="text-align:right;">';
	if (!$isNew) {
		echo '<a href="StockCategories.php?DeleteProperty=' . $id . '&SelectedCategory=' . $SelectedCategory . '" style="color:#dc2626;" onclick="return confirm(\'Delete property?\');"><i class="fas fa-times-circle"></i></a>';
	} else {
		echo '<span class="badge badge-secondary">' . __('New') . '</span>';
	}
	echo '</td></tr>';
}
