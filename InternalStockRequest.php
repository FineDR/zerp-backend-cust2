<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineStockRequestClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Create an Internal Materials Request');
$ViewTopic = 'Inventory';
$BookMark = 'CreateRequest';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/StockFunctions.php');

if (isset($_POST['DispatchDate'])){$_POST['DispatchDate'] = ConvertSQLDate($_POST['DispatchDate']);}

if (isset($_GET['New'])) {
	unset($_SESSION['Transfer']);
	$_SESSION['Request'] = new StockRequest();
}

if (isset($_POST['Update'])) {
	$InputError = 0;
	if ($_POST['Department'] == '') {
		prnMsg(__('You must select a Department for the request'), 'error');
		$InputError = 1;
	}
	if ($_POST['Location'] == '') {
		prnMsg(__('You must select a Location to request the items from'), 'error');
		$InputError = 1;
	}
	if ($InputError == 0) {
		$_SESSION['Request']->Department = $_POST['Department'];
		$_SESSION['Request']->Location = $_POST['Location'];
		$_SESSION['Request']->DispatchDate = $_POST['DispatchDate'];
		$_SESSION['Request']->Narrative = $_POST['Narrative'];
	}
}

if (isset($_POST['Edit'])) {
	$_SESSION['Request']->LineItems[$_POST['LineNumber']]->Quantity = $_POST['Quantity'];
}

if (isset($_GET['Delete'])) {
	unset($_SESSION['Request']->LineItems[$_GET['Delete']]);
	echo '<br />';
	prnMsg(__('The line was successfully deleted'), 'success');
	echo '<br />';
}

foreach ($_POST as $key => $Value) {
	if (mb_strstr($key, 'StockID')) {
		$Index = mb_substr($key, 7);
		if (filter_number_format($_POST['Quantity' . $Index]) > 0) {
			$StockID = $Value;
			$ItemDescription = $_POST['ItemDescription' . $Index];
			$DecimalPlaces = $_POST['DecimalPlaces' . $Index];
			$NewItem_array[$StockID] = filter_number_format($_POST['Quantity' . $Index]);
			$_POST['Units' . $StockID] = $_POST['Units' . $Index];
			$_SESSION['Request']->AddLine($StockID, $ItemDescription, $NewItem_array[$StockID], $_POST['Units' . $StockID], $DecimalPlaces);
		}
	}
}

if (isset($_POST['Submit']) and (!empty($_SESSION['Request']->LineItems))) {

	DB_Txn_Begin();
	$InputError = 0;
	if ($_SESSION['Request']->Department == '') {
		prnMsg(__('You must select a Department for the request'), 'error');
		$InputError = 1;
	}
	if ($_SESSION['Request']->Location == '') {
		prnMsg(__('You must select a Location to request the items from'), 'error');
		$InputError = 1;
	}
	if ($InputError == 0) {
		$RequestNo = GetNextTransNo(38);
		$HeaderSQL = "INSERT INTO stockrequest (dispatchid,
											loccode,
											departmentid,
											despatchdate,
											narrative,
											initiator)
										VALUES(
											'" . $RequestNo . "',
											'" . $_SESSION['Request']->Location . "',
											'" . $_SESSION['Request']->Department . "',
											'" . FormatDateForSQL($_SESSION['Request']->DispatchDate) . "',
											'" . $_SESSION['Request']->Narrative . "',
											'" . $_SESSION['UserID'] . "')";
		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The request header record could not be inserted because');
		$Result = DB_query($HeaderSQL, $ErrMsg, '', true);

		foreach ($_SESSION['Request']->LineItems as $LineItems) {
			$LineSQL = "INSERT INTO stockrequestitems (dispatchitemsid,
													dispatchid,
													stockid,
													quantity,
													decimalplaces,
													uom)
												VALUES(
													'" . $LineItems->LineNumber . "',
													'" . $RequestNo . "',
													'" . $LineItems->StockID . "',
													'" . $LineItems->Quantity . "',
													'" . $LineItems->DecimalPlaces . "',
													'" . $LineItems->UOM . "')";
			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The request line record could not be inserted because');
			$Result = DB_query($LineSQL, $ErrMsg, '', true);
		}

		$EmailSQL = "SELECT email
					FROM www_users, departments
					WHERE departments.authoriser = www_users.userid
						AND departments.departmentid = '" . $_SESSION['Request']->Department . "'";
		$EmailResult = DB_query($EmailSQL);
		if ($MyEmail = DB_fetch_array($EmailResult)) {
			$ConfirmationText = __('An internal stock request has been created and is waiting for your authoritation');
			$EmailSubject = __('Internal Stock Request needs your authoritation');
			SendEmailFromWebERP($SysAdminEmail,
								$MyEmail['email'],
								$EmailSubject,
								$ConfirmationText,
								'',
								false);
		}
	}
	DB_Txn_Commit();
	prnMsg(__('The internal stock request has been entered and now needs to be authorised'), 'success');
	echo '<br /><div class="centre"><a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?New=Yes">', __('Create another request'), '</a></div>';
	unset($_SESSION['Request']);
	include(__DIR__ . '/includes/footer.php');
	exit();
} elseif (isset($_POST['Submit'])) {
	prnMsg(__('There are no items added to this request'), 'error');
}

echo '<div class="db-bottom-layout">';

// SIDEBAR START
echo '<aside class="db-col-aside">';

// CARD 1: REQUEST SETTINGS
echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-cog"></i> ' . __('Request Configuration') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-label">' . __('Department') . '</label>
					<select name="Department" class="db-select db-input-light">';
if ($_SESSION['AllowedDepartment'] == 0) {
	$SQL = "SELECT departmentid, description FROM departments ORDER BY description";
} else {
	$SQL = "SELECT departmentid, description FROM departments WHERE departmentid = '" . $_SESSION['AllowedDepartment'] . "' ORDER BY description";
}
$Res = DB_query($SQL);
while ($MyRow = DB_fetch_array($Res)) {
	$selected = (isset($_SESSION['Request']->Department) and $_SESSION['Request']->Department == $MyRow['departmentid']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['departmentid'] . '">' . htmlspecialchars($MyRow['description'], ENT_QUOTES, 'UTF-8') . '</option>';
}
echo '				</select>
				</div>

				<div class="db-form-group">
					<label class="db-label">' . __('Source Location') . '</label>
					<select name="Location" class="db-select db-input-light">
						<option value="">' . __('Select a Location') . '</option>';
$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE internalrequest = 1 ORDER BY locationname";
$Res = DB_query($SQL);
while ($MyRow = DB_fetch_array($Res)) {
	$selected = (isset($_SESSION['Request']->Location) and $_SESSION['Request']->Location == $MyRow['loccode']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['loccode'] . ' - ' . htmlspecialchars($MyRow['locationname'], ENT_QUOTES, 'UTF-8') . '</option>';
}
echo '				</select>
				</div>

				<div class="db-form-group">
					<label class="db-label">' . __('Date Required') . '</label>
					<input type="date" name="DispatchDate" class="db-input db-input-light" value="' . FormatDateForSQL($_SESSION['Request']->DispatchDate) . '" />
				</div>

				<div class="db-form-group">
					<label class="db-label">' . __('Internal Note / Narrative') . '</label>
					<textarea name="Narrative" class="db-input db-input-light" rows="3" placeholder="' . __('Reason for request...') . '">' . $_SESSION['Request']->Narrative . '</textarea>
				</div>
				
		<button type="submit" name="Update" class="db-btn db-btn-primary" style="width: 100%;">
					<i class="fas fa-save"></i> ' . __('Update Header') . '
				</button>
			</div>
		</div>
	  </form>';

echo '</aside>';
// SIDEBAR END

echo '<main class="db-col-main">';

$SQL = "SELECT stockcategory.categoryid,
				stockcategory.categorydescription
		FROM stockcategory
		INNER JOIN internalstockcatrole
			ON stockcategory.categoryid = internalstockcatrole.categoryid
		WHERE internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
			ORDER BY stockcategory.categorydescription";

$Result1 = DB_query($SQL);
if (DB_num_rows($Result1) == 0) {
	echo '<div class="db-status-bar db-status-danger">
			<div class="db-status-icon"><i class="fas fa-exclamation-triangle"></i></div>
			<div class="db-status-text">' . __('There are no authorized stock categories defined for your role.') . '</div>
		  </div>';
	echo '	</main>
		</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['Edit'])) {

	echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">';
	echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
	echo '<fieldset>';
	echo '<legend>', __('Edit the Request Line'), '</legend>';
	echo '<field>
			<label>', __('Line number'), '</label>
			<fieldtext>', $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber, '</fieldtext>
		</field>
		<field>
			<label>', __('Stock Code'), '</label>
			<fieldtext>', $_SESSION['Request']->LineItems[$_GET['Edit']]->StockID, '</fieldtext>
		</field>
		<field>
			<label>', __('Item Description'), '</label>
			<fieldtext>', $_SESSION['Request']->LineItems[$_GET['Edit']]->ItemDescription, '</fieldtext>
		</field>
		<field>
			<label>', __('Unit of Measure'), '</label>
			<fieldtext>', $_SESSION['Request']->LineItems[$_GET['Edit']]->UOM, '</fieldtext>
		</field>
		<field>
			<label>', __('Quantity Requested'), '</label>
			<fieldtext><input type="text" class="number" name="Quantity" value="', locale_number_format($_SESSION['Request']->LineItems[$_GET['Edit']]->Quantity, $_SESSION['Request']->LineItems[$_GET['Edit']]->DecimalPlaces), '" /></fieldtext>
		</field>';
	echo '<input type="hidden" name="LineNumber" value="', $_SESSION['Request']->LineItems[$_GET['Edit']]->LineNumber, '" />';
	echo '</fieldset>';
	echo '<div class="centre">
			<input type="submit" name="Edit" value="', __('Update Line'), '" />
		</div>
		</form>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}



if (!isset($_SESSION['Request']->Location)) {
	include(__DIR__ . '/includes/footer.php');
	exit();
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		
		<div class="db-card" style="margin-bottom: 30px;">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-shopping-cart"></i> ' . __('Details of Items Requested') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>#</th>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('Quantity') . '</th>
								<th>' . __('UOM') . '</th>
								<th class="text-center">' . __('Actions') . '</th>
							</tr>
						</thead>
						<tbody>';

if (empty($_SESSION['Request']->LineItems)) {
	echo '<tr><td colspan="6" class="text-center" style="padding: 40px; color: var(--text-muted);">' . __('No items added to this request yet.') . '</td></tr>';
} else {
	foreach ($_SESSION['Request']->LineItems as $LineItems) {
		echo '<tr>
				<td>' . $LineItems->LineNumber . '</td>
				<td><span class="db-font-bold text-primary">' . $LineItems->StockID . '</span></td>
				<td>' . $LineItems->ItemDescription . '</td>
				<td class="text-right db-font-bold">' . locale_number_format($LineItems->Quantity, $LineItems->DecimalPlaces) . '</td>
				<td>' . $LineItems->UOM . '</td>
				<td class="text-center">
					<div style="display: flex; gap: 8px; justify-content: center;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Edit=' . urlencode($LineItems->LineNumber) . '" class="db-btn db-btn-sm db-btn-secondary" title="' . __('Edit') . '">
							<i class="fas fa-edit"></i>
						</a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Delete=' . urlencode($LineItems->LineNumber) . '" class="db-btn db-btn-sm db-btn-danger" title="' . __('Delete') . '">
							<i class="fas fa-trash-alt"></i>
						</a>
					</div>
				</td>
			</tr>';
	}
}

echo '				</tbody>
					</table>
				</div>
			</div>';
if (!empty($_SESSION['Request']->LineItems)) {
	echo '	<div class="db-card-footer" style="padding: 20px; text-align: right; background: var(--db-bg-alt);">
				<button type="submit" name="Submit" class="db-btn db-btn-primary db-btn-lg">
					<i class="fas fa-check-double"></i> ' . __('Submit Requisition') . '
				</button>
			</div>';
}
echo '	</div>
    </form>';


echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		
		<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-search"></i> ' . __('Search for Inventory Items') . '</h3>
			</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">
					<div class="db-form-group">
						<label class="db-label">' . __('Stock Category') . '</label>
						<select name="StockCat" class="db-select">
							<option value="All">' . __('All Authorized Categories') . '</option>';
while ($MyRow1 = DB_fetch_array($Result1)) {
	$selected = ($MyRow1['categoryid'] == $_POST['StockCat']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
}
echo '					</select>
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('Description Keywords') . '</label>
						<input type="text" name="Keywords" class="db-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="' . __('e.g. Printer Paper') . '" />
					</div>
					<div class="db-form-group">
						<label class="db-label">' . __('OR Stock Code') . '</label>
						<input type="text" name="StockCode" class="db-input" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" placeholder="' . __('e.g. PAP-100') . '" />
					</div>
				</div>
				<button type="submit" name="Search" class="db-btn db-btn-primary" style="margin-top: 15px;">
					<i class="fas fa-search"></i> ' . __('Search Now') . '
				</button>
			</div>
		</div>
	</form>';


if (isset($_POST['Search']) or isset($_POST['Next']) or isset($_POST['Previous'])) {

	if ($_POST['Keywords'] != '' and $_POST['StockCode'] == '') {
		prnMsg(__('Order Item description has been used in search'), 'warn');
	} elseif ($_POST['StockCode'] != '' and $_POST['Keywords'] == '') {
		prnMsg(__('Stock Code has been used in search'), 'warn');
	} elseif ($_POST['Keywords'] == '' and $_POST['StockCode'] == '') {
		prnMsg(__('Stock Category has been used in search'), 'warn');
	}

	if (isset($_POST['Keywords']) and mb_strlen($_POST['Keywords']) > 0) {
		//insert wildcard characters in spaces
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.description " . LIKE . " '" . $SearchString . "'
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} elseif (mb_strlen($_POST['StockCode']) > 0) {

		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		$SearchString = '%' . $_POST['StockCode'] . '%';

		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.stockid " . LIKE . " '" . $SearchString . "'
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}

	} else {
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
					ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid,
							stockmaster.description,
							stockmaster.units as stockunits,
							stockmaster.decimalplaces
					FROM stockmaster
					INNER JOIN stockcategory
						ON stockmaster.categoryid=stockcategory.categoryid
					INNER JOIN internalstockcatrole
						ON stockcategory.categoryid = internalstockcatrole.categoryid
					WHERE stockmaster.mbflag <>'G'
						AND stockmaster.discontinued=0
						AND internalstockcatrole.secroleid= " . $_SESSION['AccessLevel'] . "
						AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
					ORDER BY stockmaster.stockid";
		}
	}

	if (isset($_POST['Next'])) {
		$Offset = $_POST['NextList'];
	}
	if (isset($_POST['Previous'])) {
		$Offset = $_POST['PreviousList'];
	}
	if (!isset($Offset) or $Offset < 0) {
		$Offset = 0;
	}
	$SQL = $SQL . ' LIMIT ' . $_SESSION['DisplayRecordsMax'] . ' OFFSET ' . ($_SESSION['DisplayRecordsMax'] * $Offset);

	$ErrMsg = __('There is a problem selecting the part records to display because');
	$SearchResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('There are no products available meeting the criteria specified'), 'info');
	}
	if (DB_num_rows($SearchResult) < $_SESSION['DisplayRecordsMax']) {
		$Offset = 0;
	}

} //end of if search
if (isset($SearchResult)) {
	$j = 1;
	echo '<div class="db-card" style="margin-top: 30px;">
			<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
				<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Available Items') . '</h3>
				<div style="font-size: 0.85rem; color: var(--text-muted);">' . __('Enter quantities and click Add to Requisition') . '</div>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" id="orderform">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Code') . '</th>
									<th>' . __('Description') . '</th>
									<th class="text-right">' . __('Available') . '</th>
									<th class="text-right">' . __('Quantity') . '</th>
									<th>' . __('UOM') . '</th>
								</tr>
							</thead>
							<tbody>';

	$i = 0;
	while ($MyRow = DB_fetch_array($SearchResult)) {
		$DecimalPlaces = $MyRow['decimalplaces'];
		$QOH = GetQuantityOnHand($MyRow['stockid'], $_SESSION['Request']->Location);
		$DemandQty = GetDemand($MyRow['stockid'], $_SESSION['Request']->Location);
		$OnOrder = GetQuantityOnOrder($MyRow['stockid'], 'ALL');
		$Available = $QOH - $DemandQty + $OnOrder;

		echo '<tr>
				<td><span class="db-font-bold text-primary">' . $MyRow['stockid'] . '</span></td>
				<td style="font-size: 0.85rem;">' . $MyRow['description'] . '</td>
				<td class="text-right">' . locale_number_format($Available, $DecimalPlaces) . '</td>
				<td class="text-right">
					<input class="db-input number" ' . ($i == 0 ? 'autofocus="autofocus"' : '') . ' type="text" size="6" name="Quantity' . $i . '" value="0" style="width: 80px; display: inline-block;" />
					<input type="hidden" name="StockID' . $i . '" value="' . $MyRow['stockid'] . '" />
					<input type="hidden" name="DecimalPlaces' . $i . '" value="' . $MyRow['decimalplaces'] . '" />
					<input type="hidden" name="ItemDescription' . $i . '" value="' . $MyRow['description'] . '" />
					<input type="hidden" name="Units' . $i . '" value="' . $MyRow['stockunits'] . '" />
				</td>
				<td style="font-size: 0.8rem; color: var(--text-muted);">' . $MyRow['stockunits'] . '</td>
			</tr>';
		$i++;
	}
	echo '				</tbody>
						<tfoot>
							<tr>
								<td colspan="2" style="background: var(--db-bg-alt);">
									<div style="display: flex; gap: 8px;">
										<button type="submit" name="Previous" class="db-btn db-btn-sm db-btn-secondary" ' . ($Offset == 0 ? 'disabled' : '') . '>
											<i class="fas fa-chevron-left"></i> ' . __('Previous') . '
										</button>
										<button type="submit" name="Next" class="db-btn db-btn-sm db-btn-secondary">
											' . __('Next') . ' <i class="fas fa-chevron-right"></i>
										</button>
										<input type="hidden" name="PreviousList" value="' . ($Offset - 1) . '" />
										<input type="hidden" name="NextList" value="' . ($Offset + 1) . '" />
									</div>
								</td>
								<td colspan="3" class="text-right" style="background: var(--db-bg-alt); padding: 15px;">
									<input type="hidden" name="order_items" value="1" />
									<button type="submit" class="db-btn db-btn-primary">
										<i class="fas fa-cart-plus"></i> ' . __('Add Selected to Requisition') . '
									</button>
								</td>
							</tr>
						</tfoot>
						</table>
					</div>
				</form>
			</div>
		</div>';
}

	echo '	</main>
	</div>'; // End db-bottom-layout
include(__DIR__ . '/includes/footer.php');
