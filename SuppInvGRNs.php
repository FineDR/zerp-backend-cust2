<?php
/*The supplier transaction uses the SuppTrans class to hold the information about the invoice
the SuppTrans class contains an array of GRNs objects - containing details of GRNs for invoicing and also
an array of GLCodes objects - only used if the AP - GL link is effective */

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefineSuppTransClass.php');

require(__DIR__ . '/includes/session.php');

$Title = __('Enter Supplier Invoice Against Goods Received');
$ViewTopic = 'AccountsPayable';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">';
	echo '<style>
		.db-aside-btn {
			width: 100%;
			display: flex;
			align-items: center;
			gap: 12px;
			padding: 10px 12px;
			border-radius: var(--radius-md);
			border: 1px solid transparent;
			background: transparent;
			color: var(--text-body);
			font-size: 0.875rem;
			font-weight: 500;
			cursor: pointer;
			transition: all var(--transition-fast);
			text-align: left;
		}
		.db-aside-btn:hover {
			background: var(--primary-soft);
			color: var(--primary);
			border-color: var(--primary-subtle);
		}
		.db-aside-btn i {
			width: 20px;
			text-align: center;
			color: var(--primary);
			font-size: 1rem;
		}
		.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
		.registry-table th { background: #064e3b; padding: 12px 15px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: #fff; letter-spacing: 1px; }
		.registry-table td { padding: 12px 15px; font-size: 0.88rem; color: var(--text-body); border-bottom: 1px solid var(--border-soft); }
		.registry-table tr:nth-child(even) td { background: var(--bg-workspace); }
		.registry-table tr:hover td { background: var(--primary-soft) !important; }
	</style>';

	echo '<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . $Title . '</h2>
			<p class="db-page-subtitle">' . __('Selecting Goods Received for') . ' <span class="val-bold">' . $_SESSION['SuppTrans']->SupplierID . ' - ' . $_SESSION['SuppTrans']->SupplierName . '</span></p>
		</div>
		<div class="db-header-actions">
			<a href="' . $RootPath . '/SupplierInvoice.php" class="db-btn db-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"></path></svg>
				' . __('Back to Invoice') . '
			</a>
		</div>
	</div>';

$Complete=false;
if (!isset($_SESSION['SuppTrans'])){
	prnMsg(__('To enter a supplier transactions the supplier must first be selected from the supplier selection screen') . ', ' . __('then the link to enter a supplier invoice must be clicked on'),'info');
	echo '<br />
			<a href="' . $RootPath . '/SelectSupplier.php">' . __('Select A Supplier to Enter a Transaction For') . '</a>';
	include(__DIR__ . '/includes/footer.php');
	exit();
	/*It all stops here if there aint no supplier selected and invoice initiated ie $_SESSION['SuppTrans'] started off*/
}

/*If the user hit the Add to Invoice button then process this first before showing  all GRNs on the invoice
otherwise it wouldn't show the latest additions*/
if (isset($_POST['AddPOToTrans']) AND $_POST['AddPOToTrans']!=''){
	foreach($_SESSION['SuppTransTmp']->GRNs as $GRNTmp) { //loop around temp GRNs array
		if ($_POST['AddPOToTrans']==$GRNTmp->PONo) {
			$_SESSION['SuppTrans']->Copy_GRN_To_Trans($GRNTmp); //copy from  temp GRNs array to entered GRNs array
			$_SESSION['SuppTransTmp']->Remove_GRN_From_Trans($GRNTmp->GRNNo); //remove from temp GRNs array
		}
	}
}

if (isset($_POST['AddGRNToTrans'])){ /*adding a GRN to the invoice */
	foreach($_SESSION['SuppTransTmp']->GRNs as $GRNTmp) {
		if (isset($_POST['GRNNo_' . $GRNTmp->GRNNo])) {
			$_POST['GRNNo_' . $GRNTmp->GRNNo] = true;
		} else {
			$_POST['GRNNo_' . $GRNTmp->GRNNo] = false;
		}
		$Selected = $_POST['GRNNo_' . $GRNTmp->GRNNo];
		if ($Selected==true) {
			$_SESSION['SuppTrans']->Copy_GRN_To_Trans($GRNTmp);
			$_SESSION['SuppTransTmp']->Remove_GRN_From_Trans($GRNTmp->GRNNo);
		}
	}
}

if (isset($_POST['ModifyGRN'])){

	for ($i=0;isset($_POST['GRNNo'.$i]);$i++) { //loop through all the possible form variables where a GRNNo is in the POST variable name

		$InputError=false;
		$Hold=false;
		if (filter_number_format($_POST['This_QuantityInv'. $i]) >= ($_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->QtyRecd - $_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->Prev_QuantityInv )){
			$Complete = true;
		} else {
			$Complete = false;
		}

		if (filter_number_format($_POST['This_QuantityInv'.$i])+$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->Prev_QuantityInv-$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->QtyRecd > 0){
			prnMsg(__('The quantity being invoiced is more than the outstanding quantity that was delivered. It is not possible to enter an invoice for a quantity more than was received into stock'),'warn');
			$InputError = true;
		}
		if (!is_numeric(filter_number_format($_POST['ChgPrice' . $i])) AND filter_number_format($_POST['ChgPrice' . $i])<0){
			$InputError = true;
			prnMsg(__('The price charged in the suppliers currency is either not numeric or negative') . '. ' . __('The goods received cannot be invoiced at this price'),'error');
		} elseif ($_SESSION['Check_Price_Charged_vs_Order_Price'] == true AND $_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->OrderPrice != 0) {
			if (filter_number_format($_POST['ChgPrice' . $i])/$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->OrderPrice > (1+ ($_SESSION['OverChargeProportion'] / 100))){
				prnMsg(__('The price being invoiced is more than the purchase order price by more than') . ' ' . $_SESSION['OverChargeProportion'] . '%. ' .
				__('The system is set up to prohibit this so will put this invoice on hold until it is authorised'),'warn');
				$Hold=true;
			}
		}

		if ($InputError==false){
			$_SESSION['SuppTrans']->Modify_GRN_To_Trans($_POST['GRNNo'.$i],
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->PODetailItem,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->ItemCode,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->ItemDescription,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->QtyRecd,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->Prev_QuantityInv,
														filter_number_format($_POST['This_QuantityInv' . $i]),
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->OrderPrice,
														filter_number_format($_POST['ChgPrice' . $i]),
														$Complete,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->StdCostUnit,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->ShiptRef,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->JobRef,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->GLCode,
														$Hold,
														$_SESSION['SuppTrans']->GRNs[$_POST['GRNNo'.$i]]->SupplierRef);
		}
	}
}

if (isset($_GET['Delete'])){
	$_SESSION['SuppTransTmp']->Copy_GRN_To_Trans($_SESSION['SuppTrans']->GRNs[$_GET['Delete']]);
	$_SESSION['SuppTrans']->Remove_GRN_From_Trans($_GET['Delete']);
}


/*Show all the selected GRNs so far from the SESSION['SuppTrans']->GRNs array */

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="db-bottom-layout">';

	// --- SIDEBAR START ---
	echo '<aside class="db-col-aside">';
	
	// Card 1: Active Supplier
	echo '<div class="db-card" style="margin-bottom: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-user-tag db-icon-green"></i> ' . __('Supplier Context') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="font-size: 1.1rem; font-weight: 700; color: var(--db-primary);">' . $_SESSION['SuppTrans']->SupplierName . '</div>
				<div style="font-family: monospace; color: var(--text-muted); margin-bottom: var(--space-3);">[' . $_SESSION['SuppTrans']->SupplierID . ']</div>
				<div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
					<div><span class="db-muted">' . __('Currency') . ':</span> <span class="val-bold">' . $_SESSION['SuppTrans']->CurrCode . '</span></div>
				</div>
			</div>
		</div>';

	// Pre-calculate Summary for Sidebar (Reuse logic from SupplierInvoice.php)
	$TaxTotal = 0;
	// Calculate current OvAmount from selected GRNs and other items
	$currentOvAmount = 0;
	foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
		$currentOvAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
	}
	if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
		foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
			$currentOvAmount += $GLLine->Amount;
		}
	}
	// ... (Other categories if needed, but GRNs and GL are most common in this context)

	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
		}
		if (!isset($_POST['OverRideTax']) OR $_POST['OverRideTax'] == 'Auto') {
			if ($Tax->TaxOnTax == 1) {
				$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * ($currentOvAmount + $TaxTotal);
			} else {
				$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * $currentOvAmount;
			}
		} else {
			$_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);
		}
		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount;
	}

	// Card 2: Live Summary
	echo '<div class="db-card" style="position: sticky; top: var(--space-4);">
			<div class="db-card-header">
				<h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Invoice Summary') . '</h3>
			</div>
			<div class="db-card-body" style="padding: var(--space-4);">
				<div style="display: flex; flex-direction: column; gap: var(--space-3);">
					<div style="display: flex; justify-content: space-between;">
						<span class="db-muted">' . __('Items Total') . ':</span>
						<span class="val-bold">' . locale_number_format($currentOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
					</div>';
	
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		echo '<div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
				<span class="db-muted">' . $Tax->TaxAuthDescription . ':</span>
				<span>' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
			  </div>';
	}
	
	echo '			<div style="margin: var(--space-2) 0; height: 1px; background: var(--border-soft);"></div>
					<div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: var(--db-primary);">
						<span class="val-bold">' . __('Grand Total') . ':</span>
						<span class="val-bold">' . locale_number_format($currentOvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' ' . $_SESSION['SuppTrans']->CurrCode . '</span>
					</div>
				</div>
				<div style="margin-top: var(--space-6);">
					<a href="' . $RootPath . '/SupplierInvoice.php" class="db-btn db-btn-primary" style="width: 100%; height: 44px; justify-content: center; font-size: 1rem;">
						<i class="fas fa-arrow-left"></i> ' . __('Back to Entry') . '
					</a>
				</div>
			</div>
		</div>';

	echo '</aside>';
	// --- SIDEBAR END ---

	// --- MAIN CONTENT START ---
	echo '<main class="db-col-main">';

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg> ' . __('Invoiced Goods Received Selected') . '</h3>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<table class="registry-table">
					<thead>
					<tr>
						<th>' . __('Sequence') . '</th>
						<th>' . __('Supp Ref') . '</th>
						<th>' . __('Item Code') . '</th>
						<th>' . __('Description') . '</th>
						<th>' . __('Qty Outstd') . '</th>
						<th>' . __('Qty Inv') . '</th>
						<th>' . __('Order Price') . '</th>
						<th>' . __('Inv Price') . '</th>
						<th>' . __('Value') . '</th>
						<th>&nbsp;</th>
					</tr>
					</thead>
					<tbody>';

$TotalValueCharged=0;

$i=0;
foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN){
	if ($EnteredGRN->ChgPrice > 1) {
		$DisplayPrice = locale_number_format($EnteredGRN->OrderPrice,$_SESSION['SuppTrans']->CurrDecimalPlaces);
	} else {
		$DisplayPrice = locale_number_format($EnteredGRN->OrderPrice,4);
	}

	echo '<tr>
			<td class="number">', $EnteredGRN->GRNNo, '</td>
			<td class="text">', $EnteredGRN->SupplierRef, '</td>
			<td class="number">', $EnteredGRN->ItemCode, '</td>
			<td class="text">', $EnteredGRN->ItemDescription, '</td>
			<td class="number">', locale_number_format($EnteredGRN->QtyRecd - $EnteredGRN->Prev_QuantityInv,'Variable'), '</td>
			<td class="number"><input class="number" maxlength="10" name="This_QuantityInv', $i, '" size="11" type="text" value="', locale_number_format($EnteredGRN->This_QuantityInv, 'Variable'), '" /></td>
			<td class="number">', $DisplayPrice, '</td>
			<td class="number"><input class="number" maxlength="10" name="ChgPrice', $i, '" size="11" type="text" value="', locale_number_format($EnteredGRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces), '" /></td>
			<td class="number">', locale_number_format($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv, $_SESSION['SuppTrans']->CurrDecimalPlaces), '</td>
			<td class="text"><a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?Delete=', $EnteredGRN->GRNNo, '">', __('Delete'), '</a></td>
		</tr>
		<input type="hidden" name="GRNNo' . $i . '" . value="' . $EnteredGRN->GRNNo . '" />';
	$i++;
}

echo '</tbody>
				</table>
			</div>
			<div class="db-card-footer" style="padding: var(--space-4); display: flex; justify-content: flex-end; gap: var(--space-3); background: var(--surface-alt);">
				<button type="submit" name="ModifyGRN" class="db-btn db-btn-primary">' . __('Update Selected Quantities') . '</button>
			</div>
		</div>';


/* Now get all the outstanding GRNs for this supplier from the database*/

$SQL = "SELECT grnbatch,
				grnno,
				purchorderdetails.orderno,
				purchorderdetails.unitprice,
				grns.itemcode,
				grns.deliverydate,
				grns.itemdescription,
				grns.qtyrecd,
				grns.quantityinv,
				grns.stdcostunit,
				grns.supplierref,
				purchorderdetails.glcode,
				purchorderdetails.shiptref,
				purchorderdetails.jobref,
				purchorderdetails.podetailitem,
				purchorderdetails.assetid,
				stockmaster.decimalplaces
		FROM grns INNER JOIN purchorderdetails
			ON  grns.podetailitem=purchorderdetails.podetailitem
		LEFT JOIN stockmaster ON grns.itemcode=stockmaster.stockid
		WHERE grns.supplierid ='" . $_SESSION['SuppTrans']->SupplierID . "'
		AND grns.qtyrecd - grns.quantityinv > 0
		ORDER BY grns.grnno";
$GRNResults = DB_query($SQL);

if (DB_num_rows($GRNResults)==0){
	echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-body" style="text-align: center; padding: var(--space-8);">
				<div class="db-empty-icon" style="font-size: 3rem; color: var(--text-muted); opacity: 0.3; margin-bottom: var(--space-4);"><i class="fas fa-box-open"></i></div>
				<h3 style="margin-bottom: var(--space-2);">' . __('No Outstanding Goods Received') . '</h3>
				<p class="db-muted" style="margin-bottom: var(--space-6);">' . __('There are no outstanding goods received from this supplier that have not been invoiced yet.') . '</p>
				<a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?SupplierID=' . $_SESSION['SuppTrans']->SupplierID .'" class="db-btn db-btn-primary">' . __('Select Purchase Orders to Receive')  . '</a>
			</div>
		  </div>';
	echo '</main></div><!-- .db-bottom-layout -->';
	echo '</form></div><!-- .db-page -->';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

/*Set up a table to show the GRNs outstanding for selection */
echo '<div>';

if (!isset( $_SESSION['SuppTransTmp'])){
	$_SESSION['SuppTransTmp'] = new SuppTrans;
	while ($MyRow=DB_fetch_array($GRNResults)){

		$GRNAlreadyOnInvoice = false;

		foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN){
			if ($EnteredGRN->GRNNo == $MyRow['grnno']) {
				$GRNAlreadyOnInvoice = true;
			}
		}
		if ($MyRow['decimalplaces']==''){
			$MyRow['decimalplaces']=2;
		}
		if ($GRNAlreadyOnInvoice == false){
			$_SESSION['SuppTransTmp']->Add_GRN_To_Trans($MyRow['grnno'],
														$MyRow['podetailitem'],
														$MyRow['itemcode'],
														$MyRow['itemdescription'],
														$MyRow['qtyrecd'],
														$MyRow['quantityinv'],
														$MyRow['qtyrecd'] - $MyRow['quantityinv'],
														$MyRow['unitprice'],
														$MyRow['unitprice'],
														$Complete,
														$MyRow['stdcostunit'],
														$MyRow['shiptref'],
														$MyRow['jobref'],
														$MyRow['glcode'],
														$MyRow['orderno'],
														$MyRow['assetid'],
														0,
														$MyRow['decimalplaces'],
														$MyRow['grnbatch'],
														$MyRow['supplierref']);
		}
	}
}

if (!isset($_GET['Modify'])){
	if (count( $_SESSION['SuppTransTmp']->GRNs)>0){   /*if there are any outstanding GRNs then */
		echo '<div class="db-card" style="margin-top: var(--space-6);">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-list-check"></i> ' . __('Goods Received Available for Selection') . '</h3>
				</div>
				<div class="db-card-body" style="padding: 0;">
					<table class="registry-table">
						<thead>
						<tr>
							<th>' . __('Seq') . '</th>
							<th>' . __('GRN') . '</th>
							<th>' . __('Supp Ref') . '</th>
							<th>' . __('Order') . '</th>
							<th>' . __('Item') . '</th>
							<th>' . __('Description') . '</th>
							<th>' . __('Total Recd') . '</th>
							<th>' . __('Already Inv') . '</th>
							<th>' . __('Yet To Inv') . '</th>
							<th>' . __('Price') . '</th>
							<th>' . __('Value') . '</th>
							<th>' . __('Select') . '</th>
						</tr>
						</thead>
						<tbody>';
		$i = 0;
		$POs = array();
		foreach($_SESSION['SuppTransTmp']->GRNs as $GRNTmp) {
			$_SESSION['SuppTransTmp']->GRNs[$GRNTmp->GRNNo]->This_QuantityInv = $GRNTmp->QtyRecd - $GRNTmp->Prev_QuantityInv;
			if (isset($POs[$GRNTmp->PONo]) and $POs[$GRNTmp->PONo] != $GRNTmp->PONo) {
				$POs[$GRNTmp->PONo] = $GRNTmp->PONo;
				echo '<tr>
						<td><input type="submit" name="AddPOToTrans" value="' . $GRNTmp->PONo . '" /></td>
						<td colspan="3">' . __('Add Whole PO to Invoice') . '</td>
							</tr>';
			}
			echo '<tr>
				<td class="number">', $GRNTmp->GRNNo, '</td>
				<td class="number">', $GRNTmp->GRNBatchNo, '</td>
				<td class="text">', $GRNTmp->SupplierRef, '</td>
				<td class="number">', $GRNTmp->PONo, '</td>
				<td class="number">', $GRNTmp->ItemCode, '</td>
				<td class="text">', $GRNTmp->ItemDescription, '</td>
				<td class="number">', locale_number_format($GRNTmp->QtyRecd, $GRNTmp->DecimalPlaces), '</td>
				<td class="number">', locale_number_format($GRNTmp->Prev_QuantityInv, $GRNTmp->DecimalPlaces), '</td>
				<td class="number">', locale_number_format(($GRNTmp->QtyRecd - $GRNTmp->Prev_QuantityInv), $GRNTmp->DecimalPlaces), '</td>
				<td class="number">', locale_number_format($GRNTmp->OrderPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces), '</td>
				<td class="number">', locale_number_format($GRNTmp->OrderPrice * ($GRNTmp->QtyRecd - $GRNTmp->Prev_QuantityInv), $_SESSION['SuppTrans']->CurrDecimalPlaces), '</td>
				<td class="centre"><input';
			if (isset($_POST['SelectAll'])) {
				echo ' checked';
			}
			echo ' name=" GRNNo_', $GRNTmp->GRNNo, '" type="checkbox" /></td>
				</tr>';
		}
		echo '</tbody>
					</table>
				</div>
				<div class="db-card-footer" style="padding: var(--space-4); display: flex; justify-content: space-between; align-items: center; background: var(--surface-alt);">
					<div style="display: flex; gap: var(--space-2);">
						<button type="submit" name="SelectAll" class="db-btn db-btn-secondary db-btn-sm">' . __('Select All') . '</button>
						<button type="submit" name="DeSelectAll" class="db-btn db-btn-secondary db-btn-sm">' . __('Deselect All') . '</button>
					</div>
					<button type="submit" name="AddGRNToTrans" class="db-btn db-btn-primary">
						<i class="fas fa-cart-plus"></i> ' . __('Add Selected to Invoice') . '
					</button>
				</div>
			</div>';
	}
}

echo '</main></div>'; // Close .db-col-main and .db-bottom-layout
echo '</form></div><!-- .db-page -->';
include(__DIR__ . '/includes/footer.php');
