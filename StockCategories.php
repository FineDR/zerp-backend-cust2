<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Inventory Categories Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'InventoryCategories';
include(__DIR__ . '/includes/header.php');

// BEGIN: Stock Type Name array.
$StockTypeName = array();
$StockTypeName['D'] = __('Dummy Item - (No Movements)');
$StockTypeName['F'] = __('Finished Goods');
$StockTypeName['L'] = __('Labour');
$StockTypeName['M'] = __('Raw Materials');
asort($StockTypeName);
// END: Stock Type Name array.

// BEGIN: Tax Category Name array.
$TaxCategoryName = array();
$Query = "SELECT taxcatid, taxcatname FROM taxcategories ORDER BY taxcatname";
$Result = DB_query($Query);
if (DB_num_rows($Result) == 0) {
	prnMsg(__('There are no Tax Categories defined for this company. To define Tax Categories click') . ' ' .
		'<a href="'.$RootPath.'/TaxCategories.php" target="_blank">' . __('here'). '</a>', 'warn');
}
while ($Row = DB_fetch_array($Result)) {
	$TaxCategoryName[$Row['taxcatid']] = $Row['taxcatname'];
}
// END: Tax Category Name array.

if (isset($_GET['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_GET['SelectedCategory']);
} elseif (isset($_POST['SelectedCategory'])){
	$SelectedCategory = mb_strtoupper($_POST['SelectedCategory']);
}

if (isset($_GET['DeleteProperty'])){

	$ErrMsg = __('Could not delete the property') . ' ' . $_GET['DeleteProperty'] . ' ' . __('because');
	$SQL = "DELETE FROM stockitemproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	$Result = DB_query($SQL, $ErrMsg);
	$SQL = "DELETE FROM stockcatproperties WHERE stkcatpropid='" . $_GET['DeleteProperty'] . "'";
	$Result = DB_query($SQL, $ErrMsg);
	prnMsg(__('Deleted the property') . ' ' . $_GET['DeleteProperty'],'success');
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

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
	} elseif ($_POST['StockType'] !='D' AND $_POST['StockType'] !='L' AND $_POST['StockType'] !='F' AND $_POST['StockType'] !='M') {
		$InputError = 1;
		prnMsg(__('The stock type selected must be one of') . ' "D" - ' . __('Dummy item') . ', "L" - ' . __('Labour stock item') . ', "F" - ' . __('Finished product') . ' ' . __('or') . ' "M" - ' . __('Raw Materials'),'error');
	}
	for ($i=0;$i<=$_POST['PropertyCounter'];$i++){
		if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
			if (!is_numeric(filter_number_format($_POST['PropMinimum' .$i]))){
				$InputError = 1;
				prnMsg(__('The minimum value is expected to be a numeric value'),'error');
			}
			if (!is_numeric(filter_number_format($_POST['PropMaximum' .$i]))){
				$InputError = 1;
				prnMsg(__('The maximum value is expected to be a numeric value'),'error');
			}
		}
	} //check the properties are sensible

	if (isset($SelectedCategory) AND $InputError !=1) {

		/*SelectedCategory could also exist if submit had not been clicked this code
		would not run in this case cos submit is false of course  see the
		delete code below*/

		$SQL = "UPDATE stockcategory SET stocktype = '" . $_POST['StockType'] . "',
									 categorydescription = '" . $_POST['CategoryDescription'] . "',
									 defaulttaxcatid = '" . $_POST['DefaultTaxCatID'] . "',
									 stockact = '" . $_POST['StockAct'] . "',
									 adjglact = '" . $_POST['AdjGLAct'] . "',
									 issueglact = '" . $_POST['IssueGLAct'] . "',
									 purchpricevaract = '" . $_POST['PurchPriceVarAct'] . "',
									 materialuseagevarac = '" . $_POST['MaterialUseageVarAc'] . "',
									 wipact = '" . $_POST['WIPAct'] . "'
									 WHERE
									 categoryid = '" . $SelectedCategory. "'";
		$ErrMsg = __('Could not update the stock category') . $_POST['CategoryDescription'] . __('because');
		$Result = DB_query($SQL, $ErrMsg);

		if ($_POST['PropertyCounter']==0 and $_POST['PropLabel0']!='') {
			$_POST['PropertyCounter']=0;
		}

		for ($i=0;$i<=$_POST['PropertyCounter'];$i++){

			if (isset($_POST['PropReqSO' .$i]) and $_POST['PropReqSO' .$i] == true){
					$_POST['PropReqSO' .$i] =1;
			} else {
					$_POST['PropReqSO' .$i] =0;
			}
			if (isset($_POST['PropNumeric' .$i]) and $_POST['PropNumeric' .$i] == true){
					$_POST['PropNumeric' .$i] =1;
			} else {
					$_POST['PropNumeric' .$i] =0;
			}
			if (!isset($_POST['PropMinimum' . $i]) or $_POST['PropMinimum' . $i] === ''){
				$_POST['PropMinimum' . $i] = '-999999999';
			}
			if (!isset($_POST['PropMaximum' . $i]) or $_POST['PropMaximum' . $i] === ''){
				$_POST['PropMaximum' . $i] = '999999999';
			}

			if ($_POST['PropID' .$i] =='NewProperty' AND mb_strlen($_POST['PropLabel'.$i])>0){
				$SQL = "INSERT INTO stockcatproperties (categoryid,
														label,
														controltype,
														defaultvalue,
														minimumvalue,
														maximumvalue,
														numericvalue,
														reqatsalesorder)
											VALUES ('" . $SelectedCategory . "',
													'" . $_POST['PropLabel' . $i] . "',
													" . $_POST['PropControlType' . $i] . ",
													'" . $_POST['PropDefault' .$i] . "',
													'" . filter_number_format($_POST['PropMinimum' .$i]) . "',
													'" . filter_number_format($_POST['PropMaximum' .$i]) . "',
													'" . $_POST['PropNumeric' .$i] . "',
													" . $_POST['PropReqSO' .$i] . ')';
				$ErrMsg = __('Could not insert a new category property for') . $_POST['PropLabel' . $i];
				$Result = DB_query($SQL, $ErrMsg);
			} elseif ($_POST['PropID' .$i] !='NewProperty') { //we could be amending existing properties
				$SQL = "UPDATE stockcatproperties SET label ='" . $_POST['PropLabel' . $i] . "',
													  controltype = " . $_POST['PropControlType' . $i] . ",
													  defaultvalue = '"	. $_POST['PropDefault' .$i] . "',
													  minimumvalue = '" . filter_number_format($_POST['PropMinimum' .$i]) . "',
													  maximumvalue = '" . filter_number_format($_POST['PropMaximum' .$i]) . "',
													  numericvalue = '" . $_POST['PropNumeric' .$i] . "',
													  reqatsalesorder = " . $_POST['PropReqSO' .$i] . "
												WHERE stkcatpropid =" . $_POST['PropID' .$i];
				$ErrMsg = __('Updated the stock category property for') . ' ' . $_POST['PropLabel' . $i];
				$Result = DB_query($SQL, $ErrMsg);
			}

		} //end of loop round properties

		prnMsg(__('Updated the stock category record for') . ' ' . $_POST['CategoryDescription'],'success');

	} elseif ($InputError !=1) {

	/*Selected category is null cos no item selected on first time round so must be adding a	record must be submitting new entries in the new stock category form */

		$SQL = "INSERT INTO stockcategory (categoryid,
											stocktype,
											categorydescription,
											defaulttaxcatid,
											stockact,
											adjglact,
											issueglact,
											purchpricevaract,
											materialuseagevarac,
											wipact)
										VALUES ('" .
											$_POST['CategoryID'] . "','" .
											$_POST['StockType'] . "','" .
											$_POST['CategoryDescription'] . "','" .
											$_POST['DefaultTaxCatID'] . "','" .
											$_POST['StockAct'] . "','" .
											$_POST['AdjGLAct'] . "','" .
											$_POST['IssueGLAct'] . "','" .
											$_POST['PurchPriceVarAct'] . "','" .
											$_POST['MaterialUseageVarAc'] . "','" .
											$_POST['WIPAct'] . "')";
		$ErrMsg = __('Could not insert the new stock category') . $_POST['CategoryDescription'] . __('because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('A new stock category record has been added for') . ' ' . $_POST['CategoryDescription'],'success');

	}
	//run the SQL from either of the above possibilites

	unset($_POST['StockType']);
	unset($_POST['CategoryDescription']);
	unset($_POST['StockAct']);
	unset($_POST['AdjGLAct']);
	unset($_POST['IssueGLAct']);
	unset($_POST['PurchPriceVarAct']);
	unset($_POST['MaterialUseageVarAc']);
	unset($_POST['WIPAct']);


} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'StockMaster'

	$SQL= "SELECT stockid FROM stockmaster WHERE stockmaster.categoryid='" . $SelectedCategory . "'";
	$Result = DB_query($SQL);

	if (DB_num_rows($Result)>0) {
		prnMsg(__('Cannot delete this stock category because stock items have been created using this stock category'),'warn');

	} else {
		$SQL = "SELECT stkcat FROM salesglpostings WHERE stkcat='" . $SelectedCategory . "'";
		$Result = DB_query($SQL);

		if (DB_num_rows($Result)>0) {
			prnMsg(__('Cannot delete this stock category because it is used by the sales') . ' - ' . __('GL posting interface') . '. ' . __('Delete any records in the Sales GL Interface set up using this stock category first'),'warn');
		} else {
			$SQL = "SELECT stkcat FROM cogsglpostings WHERE stkcat='" . $SelectedCategory . "'";
			$Result = DB_query($SQL);

			if (DB_num_rows($Result)>0) {
				prnMsg(__('Cannot delete this stock category because it is used by the cost of sales') . ' - ' . __('GL posting interface') . '. ' . __('Delete any records in the Cost of Sales GL Interface set up using this stock category first'),'warn');
			} else {
				$SQL="DELETE FROM stockcategory WHERE categoryid='" . $SelectedCategory . "'";
				$Result = DB_query($SQL);
				prnMsg(__('The stock category') . ' ' . $SelectedCategory . ' ' . __('has been deleted') . ' !','success');
				unset ($SelectedCategory);
			}
		}
	} //end if stock category used in debtor transactions
}

	echo '<div class="db-bottom-layout">';

	// SIDEBAR
	echo '<aside class="db-col-aside">';
	renderStockCategorySidebar($SelectedCategory ?? null);
	echo '</aside>';

	// MAIN COLUMN
	echo '<main class="db-col-main">';

	if (!isset($SelectedCategory)) {
		$SQL = "SELECT categoryid, categorydescription, stocktype, defaulttaxcatid, stockact, adjglact, issueglact, purchpricevaract, materialuseagevarac, wipact
				FROM stockcategory ORDER BY categoryid";
		$Result = DB_query($SQL);

		echo '<div class="db-card">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Stock Categories') . '</h3></div>
				<div class="db-card-body p-0">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Code') . '</th>
									<th>' . __('Description') . '</th>
									<th>' . __('Type') . '</th>
									<th>' . __('Tax Cat') . '</th>
									<th class="text-right">' . __('Actions') . '</th>
								</tr>
							</thead>
							<tbody>';

		while ($MyRow = DB_fetch_array($Result)) {
			echo '<tr>
					<td><div class="db-font-bold text-primary">' . $MyRow['categoryid'] . '</div></td>
					<td>' . $MyRow['categorydescription'] . '</td>
					<td><span class="db-badge">' . $StockTypeName[$MyRow['stocktype']] . '</span></td>
					<td>' . $TaxCategoryName[$MyRow['defaulttaxcatid']] . '</td>
					<td class="text-right">
						<div style="display: flex; gap: 8px; justify-content: flex-end;">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $MyRow['categoryid'] . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i></a>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedCategory=' . $MyRow['categoryid'] . '&amp;delete=yes" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'' . __('Are you sure?') . '\');"><i class="fas fa-trash"></i></a>
						</div>
					</td>
				  </tr>';
		}
		echo '</tbody></table></div></div></div>';
	}

	// FORM SECTION
	echo '<form id="CategoryForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedCategory)) {
		if (!isset($_POST['UpdateTypes'])) {
			$SQL = "SELECT categoryid, stocktype, categorydescription, stockact, adjglact, issueglact, purchpricevaract, materialuseagevarac, wipact, defaulttaxcatid
					FROM stockcategory WHERE categoryid='" . $SelectedCategory . "'";
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
		echo '<input type="hidden" name="SelectedCategory" value="' . $SelectedCategory . '" />';
	}

	echo '<div class="db-card" style="margin-bottom: 25px;">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-' . (isset($SelectedCategory) ? 'pen-to-square' : 'plus-circle') . '"></i> ' . (isset($SelectedCategory) ? __('Edit Category') : __('New Category')) . '</h3></div>
			<div class="db-card-body">
				<div class="db-grid db-grid-2">
					<div class="db-form-group">
						<label class="db-label">' . __('Category Code') . '</label>';
	if (isset($SelectedCategory)) {
		echo '<input type="text" class="db-input" value="' . $_POST['CategoryID'] . '" disabled />';
		echo '<input type="hidden" name="CategoryID" value="' . $_POST['CategoryID'] . '" />';
	} else {
		echo '<input type="text" name="CategoryID" class="db-input" required maxlength="6" value="' . ($_POST['CategoryID'] ?? '') . '" placeholder="' . __('e.g. METAL') . '" />';
	}
	echo '			</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Description') . '</label>
						<input type="text" name="CategoryDescription" class="db-input" required maxlength="20" value="' . ($_POST['CategoryDescription'] ?? '') . '" placeholder="' . __('e.g. Metal Parts') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Type') . '</label>
						<select name="StockType" class="db-select" onchange="ReloadForm(CategoryForm.UpdateTypes)">';
	foreach ($StockTypeName as $STypeId => $STypeName) {
		echo '<option ' . ((isset($_POST['StockType']) && $_POST['StockType'] == $STypeId) ? 'selected' : '') . ' value="' . $STypeId . '">' . $STypeName . '</option>';
	}
	echo '				</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Default Tax Category') . '</label>
						<select name="DefaultTaxCatID" class="db-select">';
	foreach ($TaxCategoryName as $TId => $TName) {
		echo '<option ' . (($_POST['DefaultTaxCatID'] ?? $_SESSION['DefaultTaxCategory']) == $TId ? 'selected' : '') . ' value="' . $TId . '">' . $TName . '</option>';
	}
	echo '				</select>
					</div>
				</div>

				<div class="db-grid db-grid-3" style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--border-soft);">';
	
	// Accounts selection
	$BSRes = DB_query("SELECT accountcode, accountname FROM chartmaster LEFT JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=0 ORDER BY accountcode");
	$PnLRes = DB_query("SELECT accountcode, accountname FROM chartmaster LEFT JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY accountcode");
	
	$StockLabel = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? __('Recovery GL') : __('Stock GL');
	$AccRes = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? $PnLRes : $BSRes;
	
	echo '			<div class="db-form-group">
						<label class="db-label">' . $StockLabel . '</label>
						<select name="StockAct" class="db-select">';
	while ($ARow = DB_fetch_array($AccRes)) {
		echo '<option ' . (($_POST['StockAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
	}
	echo '				</select>
					</div>';
	
	DB_data_seek($BSRes, 0);
	echo '			<div class="db-form-group">
						<label class="db-label">' . __('WIP GL Code') . '</label>
						<select name="WIPAct" class="db-select">';
	while ($ARow = DB_fetch_array($BSRes)) {
		echo '<option ' . (($_POST['WIPAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
	}
	echo '				</select>
					</div>';

	$UsageLabel = (isset($_POST['StockType']) && $_POST['StockType'] == 'L') ? __('Efficiency Var GL') : __('Usage Var GL');
	DB_data_seek($PnLRes, 0);
	echo '			<div class="db-form-group">
						<label class="db-label">' . $UsageLabel . '</label>
						<select name="MaterialUseageVarAc" class="db-select">';
	while ($ARow = DB_fetch_array($PnLRes)) {
		echo '<option ' . (($_POST['MaterialUseageVarAc'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
	}
	echo '				</select>
					</div>';

	if (isset($_POST['StockType']) && $_POST['StockType'] != 'L' && $_POST['StockType'] != 'D') {
		DB_data_seek($PnLRes, 0);
		echo '		<div class="db-form-group">
						<label class="db-label">' . __('Adjts GL') . '</label>
						<select name="AdjGLAct" class="db-select">';
		while ($ARow = DB_fetch_array($PnLRes)) {
			echo '<option ' . (($_POST['AdjGLAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
		}
		echo '			</select>
					</div>';
		DB_data_seek($PnLRes, 0);
		echo '		<div class="db-form-group">
						<label class="db-label">' . __('Issues GL') . '</label>
						<select name="IssueGLAct" class="db-select">';
		while ($ARow = DB_fetch_array($PnLRes)) {
			echo '<option ' . (($_POST['IssueGLAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
		}
		echo '			</select>
					</div>';
		DB_data_seek($PnLRes, 0);
		echo '		<div class="db-form-group">
						<label class="db-label">' . __('Price Var GL') . '</label>
						<select name="PurchPriceVarAct" class="db-select">';
		while ($ARow = DB_fetch_array($PnLRes)) {
			echo '<option ' . (($_POST['PurchPriceVarAct'] ?? '') == $ARow['accountcode'] ? 'selected' : '') . ' value="' . $ARow['accountcode'] . '">' . $ARow['accountname'] . ' (' . $ARow['accountcode'] . ')</option>';
		}
		echo '			</select>
					</div>';
	} else {
		echo '<input type="hidden" name="AdjGLAct" value="1" /><input type="hidden" name="IssueGLAct" value="1" /><input type="hidden" name="PurchPriceVarAct" value="1" />';
	}

	echo '		</div>
				<div class="text-center" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-soft);">
					<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . __('Save Category') . '</button>
				</div>
			</div>
		  </div>';
	
	// PROPERTIES SECTION
	if (isset($SelectedCategory)) {
		$PropRes = DB_query("SELECT stkcatpropid, label, controltype, defaultvalue, numericvalue, reqatsalesorder, minimumvalue, maximumvalue FROM stockcatproperties WHERE categoryid='" . $SelectedCategory . "' ORDER BY stkcatpropid");
		
		echo '<div class="db-card">
				<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-tags"></i> ' . __('Extended Properties') . '</h3></div>
				<div class="db-card-body p-0">
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Property Label') . '</th>
									<th>' . __('Control Type') . '</th>
									<th>' . __('Default') . '</th>
									<th class="text-center">' . __('Numeric') . '</th>
									<th>' . __('Min / Max') . '</th>
									<th class="text-center">' . __('Req in SO') . '</th>
									<th class="text-center">' . __('Action') . '</th>
								</tr>
							</thead>
							<tbody>';
		
		$PropertyCounter = 0;
		while ($PRow = DB_fetch_array($PropRes)) {
			renderPropertyRow($PropertyCounter, $PRow);
			$PropertyCounter++;
		}
		// New Property Row
		renderPropertyRow($PropertyCounter, null);
		
		echo '				</tbody>
						</table>
					</div>
					<input type="hidden" name="PropertyCounter" value="' . $PropertyCounter . '" />
				</div>
			  </div>';
	}
	
	echo '<input type="submit" name="UpdateTypes" style="display:none;" />
		  </form>';

	echo '</main></div>';

	include(__DIR__ . '/includes/footer.php');

function renderStockCategorySidebar($sel) {
	global $RootPath;
	echo '<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-wrench"></i> ' . __('Quick Actions') . '</h3></div>
			<div class="db-card-body">';
	if ($sel) {
		echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-btn-primary" style="width: 100%; margin-bottom: 15px;"><i class="fas fa-list"></i> ' . __('View All Categories') . '</a>';
	}
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="db-btn db-input-light" style="width: 100%;"><i class="fas fa-plus-circle"></i> ' . __('Create New') . '</a>
			</div>
		  </div>';
	
	echo '<div class="db-card">
			<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Knowledge Base') . '</h3></div>
			<div class="db-card-body">
				<p class="db-muted" style="font-size: 0.85rem;">' . __('Categories group items for financial reporting and default inventory behavior. Assigning properties allows tracking technical specifications per category.') . '</p>
			</div>
		  </div>';
}

function renderPropertyRow($i, $row) {
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
				<input type="hidden" name="PropID' . $i . '" value="' . $id . '" />
				<input type="text" name="PropLabel' . $i . '" class="db-input db-input-sm" value="' . $label . '" placeholder="' . ($isNew ? __('Enter Label...') : '') . '" />
			</td>
			<td>
				<select name="PropControlType' . $i . '" class="db-select db-select-sm">
					<option value="0" ' . ($cType == 0 ? 'selected' : '') . '>' . __('Text Box') . '</option>
					<option value="1" ' . ($cType == 1 ? 'selected' : '') . '>' . __('Select Box') . '</option>
					<option value="2" ' . ($cType == 2 ? 'selected' : '') . '>' . __('Check Box') . '</option>
					<option value="3" ' . ($cType == 3 ? 'selected' : '') . '>' . __('Date Box') . '</option>
				</select>
			</td>
			<td><input type="text" name="PropDefault' . $i . '" class="db-input db-input-sm" value="' . $default . '" /></td>
			<td class="text-center"><input type="checkbox" name="PropNumeric' . $i . '" ' . ($numeric ? 'checked' : '') . ' /></td>
			<td>
				<div style="display: flex; gap: 4px;">
					<input type="text" name="PropMinimum' . $i . '" class="db-input db-input-sm" style="width: 60px;" value="' . $min . '" placeholder="Min" />
					<input type="text" name="PropMaximum' . $i . '" class="db-input db-input-sm" style="width: 60px;" value="' . $max . '" placeholder="Max" />
				</div>
			</td>
			<td class="text-center"><input type="checkbox" name="PropReqSO' . $i . '" ' . ($reqSO ? 'checked' : '') . ' /></td>
			<td class="text-center">';
	if (!$isNew) {
		echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?DeleteProperty=' . $id . '&SelectedCategory=' . ($_GET['SelectedCategory'] ?? '') . '" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'Delete property?\');"><i class="fas fa-times"></i></a>';
	} else {
		echo '<span class="db-badge db-badge-secondary">' . __('New') . '</span>';
	}
	echo '</td></tr>';
}
