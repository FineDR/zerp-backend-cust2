<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineWOClass.php');
require(__DIR__ . '/includes/session.php');

$ViewTopic = 'Manufacturing';
$BookMark = 'WorkOrderEntry';
$Title = __('Work Order Entry');
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_POST['StartDate'])){$_POST['StartDate'] = ConvertSQLDate($_POST['StartDate']);}
if (isset($_POST['RequiredBy'])){$_POST['RequiredBy'] = ConvertSQLDate($_POST['RequiredBy']);}

// Architectural Workspace Design System v2 - High Density
echo '
<style>
	:root {
		--primary: hsl(145, 63%, 38%); 
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-soft: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(145, 15%, 12%);
		--text-muted: hsl(145, 8%, 50%);
		--card-bg: #ffffff;
		--radius: 12px;
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); }
	.aw-container { padding: 2px 10px !important; max-width: none !important; width: 100% !important; margin: 0 !important; }
	.MainBody { padding-left: 0 !important; padding-right: 0 !important; width: 100% !important; max-width: none !important; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 1fr 350px; align-items: start; }
		.aw-grid-search { grid-template-columns: 320px 1fr; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select, .aw-textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus, .aw-textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
	.aw-btn-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
	.aw-btn-danger:hover { background: #ffe4e6; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

    .aw-badge { padding: 2px 8px; border-radius: 99px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; }
    .aw-badge-info { background: var(--primary-soft); color: var(--primary); }

    .aw-stat-box { background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid var(--border-color); }
	.aw-stat-label { font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
	.aw-stat-val { font-size: 1.25rem; font-weight: 950; color: var(--primary-dark); }
</style>
<div class="aw-container">';

/*unique session identifier to ensure that there is no conflict with other order entry sessions on the same machine  */
if (isset($_GET['identifier'])) { $Identifier = $_GET['identifier']; } elseif (isset($_POST['identifier'])) { $Identifier = $_POST['identifier']; } else { $Identifier = date('U'); $_SESSION['WorkOrder' . $Identifier] = new WorkOrder(); }
if (isset($_GET['WO'])) { $_POST['WO'] = $_GET['WO']; }
if (isset($_POST['RequiredBy'])) { $_SESSION['WorkOrder' . $Identifier]->RequiredBy = $_POST['RequiredBy']; } else { $_SESSION['WorkOrder' . $Identifier]->RequiredBy = date($_SESSION['DefaultDateFormat']); }
if (isset($_POST['StartDate'])) { $_SESSION['WorkOrder' . $Identifier]->StartDate = $_POST['StartDate']; } else { $_SESSION['WorkOrder' . $Identifier]->StartDate = date($_SESSION['DefaultDateFormat']); }
if (isset($_POST['StockLocation'])) { $_SESSION['WorkOrder' . $Identifier]->LocationCode = $_POST['StockLocation']; }
if (isset($_GET['WO'])) { $_SESSION['WorkOrder' . $Identifier]->Load($_GET['WO']); }
if (isset($_POST['Reference'])) { $_SESSION['WorkOrder' . $Identifier]->Reference = $_POST['Reference']; }
if (isset($_POST['Remark'])) { $_SESSION['WorkOrder' . $Identifier]->Remark = $_POST['Remark']; }

if (isset($_POST['AddToOrder'])) {
	$LocSQL = "SELECT locations.loccode FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE locations.loccode='" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "'";
	$LocResult = DB_query($LocSQL);
	$LocRow = DB_fetch_array($LocResult);
	if (is_null($LocRow['loccode']) or $LocRow['loccode'] == '') { prnMsg(__('Your security settings do not allow you to create or update new Work Order at this location') . ' ' . $_SESSION['WorkOrder' . $Identifier]->LocationCode, 'error'); echo '<br /><a href="' . $RootPath . '/SelectWorkOrder.php">' . __('Select an existing work order') . '</a>'; include(__DIR__ . '/includes/footer.php'); exit(); }
	foreach ($_POST as $Key => $Value) {
		if (substr($Key, 0, 7) == 'StockID') {
			$Index = substr($Key, 7);
			if ($_POST['Quantity' . $Index] > 0) {
				$InputError = 0;
				$CheckItemResult = DB_query("SELECT mbflag, eoq, controlled FROM stockmaster WHERE stockid='" . $Value . "'");
				if (DB_num_rows($CheckItemResult) == 1) { $CheckItemRow = DB_fetch_array($CheckItemResult); if ($CheckItemRow['mbflag'] != 'M') { prnMsg(__('The item selected cannot be added to a work order because it is not a manufactured item'), 'warn'); $InputError = true; } } else { prnMsg(__('The item selected cannot be found in the database'), 'error'); $InputError = true; }
				$AlreadyOnOrder = 0; foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $WorkOrderItem) { if ($WorkOrderItem->StockId == $Value) { ++$AlreadyOnOrder; } }
				if ($AlreadyOnOrder > 0) { prnMsg(__('This item is already on the work order and cannot be added again'), 'warn'); $InputError = true; }
				if (!$InputError) { $_SESSION['WorkOrder' . $Identifier]->AddItemToOrder($Value, '', $_POST['Quantity' . $Index], 0, ''); if ($CheckItemRow['controlled'] == 1 and $_SESSION['DefineControlledOnWOEntry'] == 1) { $_SESSION['WorkOrder' . $Identifier]->QuantityRequired = 0; $_SESSION['WorkOrder' . $Identifier]->Controlled = 1; } }
			}
		}
	}
}

if (isset($_POST['Save'])) {
	foreach ($_POST as $Key => $Value) { if (substr($Key, 0, 13) == 'OutputStockId') { $Index = substr($Key, -1); $_SESSION['WorkOrder' . $Identifier]->UpdateItem($Value, $_POST['WOComments' . $Index], $_POST['OutputQty' . $Index], ''); } }
	if (!isset($EOQ)) { $EOQ = 1; }
	$CheckSQL = "SELECT wo FROM workorders WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'";
	$CheckResult = DB_query($CheckSQL);
	if (DB_num_rows($CheckResult) == 0) {
		$_SESSION['WorkOrder' . $Identifier]->OrderNumber = GetNextTransNo(40);
		$SQL = "INSERT INTO workorders (wo, loccode, requiredby, startdate, reference, remark) VALUES ('" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "', '" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "', '" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->RequiredBy) . "', '" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->StartDate) . "', '" . $_SESSION['WorkOrder' . $Identifier]->Reference . "', '" . $_SESSION['WorkOrder' . $Identifier]->Remark . "')";
		DB_query($SQL);
	} else {
		$SQL = "UPDATE workorders SET loccode='" . $_SESSION['WorkOrder' . $Identifier]->LocationCode . "', requiredby='" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->RequiredBy) . "', startdate='" . FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->StartDate) . "', reference='" . $_SESSION['WorkOrder' . $Identifier]->Reference . "', remark='" . $_SESSION['WorkOrder' . $Identifier]->Remark . "' WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "'";
		DB_query($SQL);
	}
	foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $Item) {
		$CostResult = DB_query("SELECT SUM((actualcost)*bom.quantity) AS cost, bom.loccode FROM stockmaster INNER JOIN bom ON stockmaster.stockid=bom.component WHERE bom.parent='" . $Item->StockId . "' AND bom.loccode=(SELECT loccode FROM workorders WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "') AND bom.effectiveafter<=CURRENT_DATE AND bom.effectiveto>=CURRENT_DATE");
		$CostRow = DB_fetch_array($CostResult);
		$Cost = (is_null($CostRow['cost']) or $CostRow['cost'] == 0) ? 0 : $CostRow['cost'];
		$CheckSQL = "SELECT wo FROM woitems WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "' AND stockid='" . $Item->StockId . "'";
		$CheckResult = DB_query($CheckSQL); $QuantityRequired = (isset($Item->QuantityRequired) && is_numeric($Item->QuantityRequired)) ? floatval($Item->QuantityRequired) : 0;
		if (DB_num_rows($CheckResult) == 0) { $SQL = "INSERT INTO woitems (wo, stockid, qtyreqd, stdcost, comments) VALUES ('" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "', '" . $Item->StockId . "', '" . $QuantityRequired . "', '" . $Cost . "', '" . $Item->Comments . "')"; }
		else { $SQL = "UPDATE woitems SET qtyreqd='" . $QuantityRequired . "', comments='" . $Item->Comments . "' WHERE wo='" . $_SESSION['WorkOrder' . $Identifier]->OrderNumber . "' AND stockid='" . $Item->StockId . "'"; }
		DB_query($SQL); WoRealRequirements($_SESSION['WorkOrder' . $Identifier]->OrderNumber, $_SESSION['WorkOrder' . $Identifier]->LocationCode, $Item->StockId);
	}
	prnMsg(__('The work order has been saved correctly'), 'success'); unset($NewItem);
}

if (isset($_POST['delete'])) {
	$CancelDelete = false; $HasTransResult = DB_query("SELECT transno FROM stockmoves WHERE (stockmoves.type= 26 OR stockmoves.type=28) AND reference='" . $_POST['WO'] . "'");
	if (DB_num_rows($HasTransResult) > 0) { prnMsg(__('This work order cannot be deleted because it has issues or receipts related to it'), 'error'); $CancelDelete = true; }
	if ($CancelDelete == false) { DB_Txn_Begin(); DB_query("DELETE FROM worequirements WHERE wo='" . $_POST['WO'] . "'"); DB_query("DELETE FROM woitems WHERE wo='" . $_POST['WO'] . "'"); DB_query("DELETE FROM woserialnos WHERE wo='" . $_POST['WO'] . "'"); DB_query("DELETE FROM workorders WHERE wo='" . $_POST['WO'] . "'"); DB_Txn_Commit(); prnMsg(__('The work order has been cancelled'), 'success'); echo '<p><a href="' . $RootPath . '/SelectWorkOrder.php">' . __('Select an existing outstanding work order') . '</a></p>'; unset($_POST['WO']); include(__DIR__ . '/includes/footer.php'); exit(); }
}

if (isset($_POST['WO']) and $_POST['WO'] != __('Not yet allocated')) {
	$SQL = "SELECT workorders.loccode, requiredby, startdate, costissued, closed, reference, remark FROM workorders INNER JOIN locations ON workorders.loccode=locations.loccode INNER JOIN locationusers ON locationusers.loccode=workorders.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE workorders.wo='" . $_POST['WO'] . "'";
	$WOResult = DB_query($SQL);
	if (DB_num_rows($WOResult) == 1) {
		$MyRow = DB_fetch_array($WOResult); $_SESSION['WorkOrder' . $Identifier]->StartDate = ConvertSQLDate($MyRow['startdate']); $_POST['CostIssued'] = $MyRow['costissued']; $_POST['Closed'] = $MyRow['closed']; $_SESSION['WorkOrder' . $Identifier]->RequiredBy = ConvertSQLDate($MyRow['requiredby']); $_SESSION['WorkOrder' . $Identifier]->Reference = $MyRow['reference']; $_SESSION['WorkOrder' . $Identifier]->Remark = $MyRow['remark']; $_POST['StockLocation'] = $MyRow['loccode'];
		$WOItemsSQL = "SELECT woitems.stockid, stockmaster.description, qtyreqd, qtyrecd, stdcost, nextlotsnref, controlled, serialised, stockmaster.decimalplaces, nextserialno, woitems.comments FROM woitems INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid WHERE wo='" . $_POST['WO'] . "'";
		$WOItemsResult = DB_query($WOItemsSQL); $NumberOfOutputs = DB_num_rows($WOItemsResult); $i = 1;
		while ($WOItem = DB_fetch_array($WOItemsResult)) { $_POST['OutputItem' . $i] = $WOItem['stockid']; $_POST['OutputItemDesc' . $i] = $WOItem['description']; $_POST['OutputQty' . $i] = $WOItem['qtyreqd']; $_POST['RecdQty' . $i] = $WOItem['qtyrecd']; $_POST['WOComments' . $i] = $WOItem['comments']; $_POST['DecimalPlaces' . $i] = $WOItem['decimalplaces']; $_POST['NextLotSNRef' . $i] = ($WOItem['serialised'] == 1 and $WOItem['nextserialno'] > 0) ? $WOItem['nextserialno'] : $WOItem['nextlotsnref']; $_POST['Controlled' . $i] = $WOItem['controlled']; $_POST['Serialised' . $i] = $WOItem['serialised']; $_POST['HasWOSerialNos'] = (DB_num_rows(DB_query("SELECT wo FROM woserialnos WHERE wo='" . $_POST['WO'] . "'")) > 0); $i++; }
	}
}

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Manufacturing / Work Orders</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/SelectWorkOrder.php" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> ' . __('Find Work Order') . '</a>
		</div>
	  </div>';

echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '?identifier=', urlencode($Identifier), '" name="form1">';
echo '<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />';
echo '<input type="hidden" name="WO" value="', $_SESSION['WorkOrder' . $Identifier]->OrderNumber, '" />';

echo '<div class="aw-grid aw-grid-layout">';

// MAIN CONTENT (Left)
echo '<main class="aw-main-side">';

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Work Order Configuration') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
				<div class="aw-form-group"><label class="aw-label">' . __('Factory Location') . '</label><select name="StockLocation" class="aw-select" autofocus="autofocus" onChange="ReloadForm(form1.submit)">';
					$LocResult = DB_query("SELECT locations.loccode,locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1 WHERE locations.usedforwo = 1");
					while ($LocRow = DB_fetch_array($LocResult)) { $sel = ($_SESSION['WorkOrder' . $Identifier]->LocationCode == $LocRow['loccode']) ? 'selected' : ''; echo '<option ' . $sel . ' value="', $LocRow['loccode'], '">', $LocRow['locationname'], '</option>'; }
echo '				</select></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Start Date') . '</label><input name="StartDate" class="aw-input" value="', FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->StartDate), '" type="date" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Required By') . '</label><input name="RequiredBy" class="aw-input" value="', FormatDateForSQL($_SESSION['WorkOrder' . $Identifier]->RequiredBy), '" type="date" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Reference / Job ID') . '</label><input type="text" name="Reference" class="aw-input" value="', $_SESSION['WorkOrder' . $Identifier]->Reference, '" maxlength="40" /></div>
			</div>
			<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Internal Comments') . '</label><textarea name="Remark" class="aw-textarea" rows="2">', $_SESSION['WorkOrder' . $Identifier]->Remark, '</textarea></div>
		</div>
	  </div>';

if (count($_SESSION['WorkOrder' . $Identifier]->Items) > 0) {
	echo '<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg> ' . __('Output Items') . '</h3></div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('Description') . '</th>
							<th style="width:100px; text-align:right;">' . __('Qty Req') . '</th>
							<th style="width:100px; text-align:right;">' . __('Qty Recd') . '</th>
							<th style="width:100px; text-align:right;">' . __('Balance') . '</th>
							<th>' . __('Batch Ref') . '</th>
						</tr>
					</thead>
					<tbody>';
	$i = 1;
	foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $WorkOrderItem) {
		$DRow = DB_fetch_array(DB_query("SELECT description FROM stockmaster WHERE stockid='" . $WorkOrderItem->StockId . "'"));
		echo '<input type="hidden" name="OutputStockId', $i, '" value="', $WorkOrderItem->StockId, '" />';
		echo '<tr>
				<td><div style="font-weight:700; color:var(--primary);">' . $WorkOrderItem->StockId . '</div><div style="font-size:0.75rem;">' . $DRow['description'] . '</div>
					<textarea name="WOComments', $i, '" class="aw-textarea" style="font-size:0.7rem; margin-top:4px;" rows="1">', $WorkOrderItem->Comments, '</textarea></td>';
		if ($WorkOrderItem->Controlled == 1 and $_SESSION['DefineControlledOnWOEntry'] == 1) {
			echo '<td style="text-align:right; font-weight:800;">', locale_number_format($WorkOrderItem->QuantityRequired, $WorkOrderItem->DecimalPlaces), '<input type="hidden" name="OutputQty', $i, '" value="', locale_number_format($WorkOrderItem->QuantityRequired, $WorkOrderItem->DecimalPlaces), '" /></td>';
		} else {
			echo '<td><input type="text" class="aw-input" style="text-align:right; font-weight:800;" name="OutputQty', $i, '" value="', locale_number_format($WorkOrderItem->QuantityRequired, $WorkOrderItem->DecimalPlaces), '" /></td>';
		}
		echo '<td style="text-align:right; color:var(--text-muted);">', locale_number_format(($WorkOrderItem->QuantityReceived), $WorkOrderItem->DecimalPlaces), '</td>';
		echo '<td style="text-align:right; font-weight:700; color:var(--primary-dark);">', locale_number_format(($WorkOrderItem->QuantityRequired - $WorkOrderItem->QuantityReceived), $WorkOrderItem->DecimalPlaces), '</td>';
		echo '<td>';
		if ($WorkOrderItem->Controlled == 1) {
			echo '<input type="text" class="aw-input" style="font-size:0.75rem;" name="NextLotSNRef', $i, '" value="', $WorkOrderItem->NextLotSerialNumbers, '" />';
			if ($_SESSION['DefineControlledOnWOEntry'] == 1) { $LotMsg = ($WorkOrderItem->Serialised == 1) ? __('S/Ns') : __('Batches'); echo '<a href="', $RootPath, '/WOSerialNos.php?identifier=', urlencode($Identifier), '&WO=', urlencode($_POST['WO']), '&StockID=', urlencode($WorkOrderItem->StockId), '&Description=', urlencode($DRow['description']), '&Serialised=', urlencode($WorkOrderItem->Serialised), '&NextSerialNo=', urlencode($WorkOrderItem->NextLotSerialNumbers), '" class="aw-badge aw-badge-info" style="margin-top:4px;">', $LotMsg, '</a>'; }
		}
		echo '</td></tr>';
		$i++;
	}
	echo '</tbody></table></div></div>';
}

echo '</main>';

// SIDEBAR (Right)
echo '<aside class="aw-sidebar-side">
		<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Workflow Actions') . '</h3></div>
			<div class="aw-card-body">
				<div class="aw-stat-box" style="margin-bottom:16px;">
					<div class="aw-stat-label">' . __('WO Reference') . '</div>
					<div class="aw-stat-val">' . ($_SESSION['WorkOrder' . $Identifier]->OrderNumber == 0 ? __('PENDING') : $_SESSION['WorkOrder' . $Identifier]->OrderNumber) . '</div>
				</div>';
				if (isset($MyRow['costissued'])) {
					echo '<div class="aw-stat-box" style="background:var(--primary-soft); margin-bottom:16px;">
							<div class="aw-stat-label">' . __('Accumulated Costs') . '</div>
							<div class="aw-stat-val">' . locale_number_format($MyRow['costissued'], $_SESSION['CompanyRecord']['decimalplaces']) . '</div>
						  </div>';
				}
echo '			<button type="submit" name="Save" class="aw-btn aw-btn-primary" style="width:100%; height:48px; font-size:0.9rem;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg> ' . __('Commit Work Order') . '</button>
				<button type="submit" name="delete" class="aw-btn aw-btn-danger" style="width:100%; margin-top:8px;" onclick="return confirm(\'' . __('Are you sure you want to cancel this work order? This cannot be undone.') . '\');">' . __('Cancel Work Order') . '</button>
			</div>
		</div>

		<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Production Tracking') . '</h3></div>
			<div class="aw-card-body">
				<div style="font-size:0.75rem; color:var(--text-muted); line-height:1.6;">
					' . __('Ensure all BOM requirements are reviewed before committing. Automated cost accumulation will be calculated based on standard component values at the selected location.') . '
				</div>
			</div>
		</div>
	  </aside>';

echo '</div>'; // End aw-grid-layout

// SEARCH SECTION
if ($_SESSION['WorkOrder' . $Identifier]->OrderNumber != 0) {
	echo '<div class="aw-grid aw-grid-search" style="margin-top:32px; border-top: 1px solid var(--border-color); padding-top:32px;">';
	echo '<aside class="aw-sidebar-side">
			<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Add Manufactured Items') . '</h3></div>
				<div class="aw-card-body">
					<div class="aw-form-group"><label class="aw-label">' . __('Category') . '</label><select name="StockCat" class="aw-select">';
						$SQL = "SELECT categoryid, categorydescription FROM stockcategory WHERE stocktype='F' OR stocktype='M' ORDER BY categorydescription";
						$Res1 = DB_query($SQL);
						echo '<option value="All">All Categories</option>';
						while ($C = DB_fetch_array($Res1)) { $sel = (isset($_POST['StockCat']) && $_POST['StockCat'] == $C['categoryid']) ? 'selected' : ''; echo '<option ' . $sel . ' value=', $C['categoryid'], '>', $C['categorydescription'], '</option>'; }
echo '					</select></div>
					<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Keywords') . '</label><input type="text" name="Keywords" class="aw-input" value="', (isset($_POST['Keywords'])?$_POST['Keywords']:''), '" /></div>
					<div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('Stock Code') . '</label><input type="text" name="StockCode" class="aw-input" value="', (isset($_POST['StockCode'])?$_POST['StockCode']:''), '" /></div>
					<button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Find Items') . '</button>
				</div>
			</div>
		  </aside>';

	echo '<main class="aw-main-side">';
	if (isset($_POST['Search']) or isset($_POST['Prev']) or isset($_POST['Next'])) {
		$Keywords = mb_strtoupper($_POST['Keywords']); $SearchString = '%' . str_replace(' ', '%', $Keywords) . '%'; $SearchCode = '%' . $_POST['StockCode'] . '%'; $Cat = ($_POST['StockCat'] == 'All') ? '%' : $_POST['StockCat'];
		$SQL = "SELECT stockmaster.stockid, description, stockmaster.units FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid WHERE (stockcategory.stocktype='F' OR stockcategory.stocktype='M') AND stockmaster.description " . LIKE . " '" . $SearchString . "' AND stockmaster.categoryid " . LIKE . " '" . $Cat . "' AND stockmaster.stockid " . LIKE . " '" . $SearchCode . "' AND stockmaster.discontinued=0 AND mbflag='M' AND (SELECT COUNT(bom.parent) FROM bom WHERE bom.parent=stockmaster.stockid)>0 ORDER BY stockmaster.stockid";
		$Res = DB_query($SQL);
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Catalog Results') . '</h3> <button type="submit" name="AddToOrder" class="aw-btn aw-btn-primary aw-btn-sm">' . __('Add Selected') . '</button></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead><tr><th>' . __('Code') . '</th><th>' . __('Description') . '</th><th>' . __('Img') . '</th><th style="width:100px;">' . __('Quantity') . '</th></tr></thead>
						<tbody>';
		$j = 1; $ItemCodes = array(); foreach ($_SESSION['WorkOrder' . $Identifier]->Items as $WItem) { $ItemCodes[] = $WItem->StockId; }
		while ($R = DB_fetch_array($Res)) {
			if (!in_array($R['stockid'], $ItemCodes)) {
				$ImageFileArray = glob($_SESSION['part_pics_dir'] . '/' . $R['stockid'] . '.{png,jpg,jpeg}', GLOB_BRACE);
				$ImageSource = GetImageLink(reset($ImageFileArray), $R['stockid'], 40, 40, "", "");
				echo '<tr>
						<td style="font-weight:700;">' . $R['stockid'] . '</td>
						<td>' . $R['description'] . ' <span style="font-size:0.7rem; color:var(--text-muted);">(' . $R['units'] . ')</span></td>
						<td>' . $ImageSource . '</td>
						<td><input type="hidden" name="StockID' . $j . '" value="' . $R['stockid'] . '" /><input type="text" class="aw-input" style="text-align:right;" name="Quantity' . $j . '" value="0" /></td>
					  </tr>';
				$j++;
			}
		}
		echo '</tbody></table></div></div>';
	} else {
		echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Search results will appear here.') . '</div></div>';
	}
	echo '</main></div>';
}

echo '</form></div>';
include(__DIR__ . '/includes/footer.php');
?>
