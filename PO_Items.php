<?php

// Entry of a purchase order items - allows entry of items with lookup of currency cost from Purchasing Data previously entered also allows entry of nominal items against a general ledger code if the AP is integrated to the GL.
include(__DIR__ . '/includes/DefinePOClass.php');
require(__DIR__ . '/includes/session.php');

$Title = __('Purchase Order Items');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_POST['ReqDelDate'])){$_POST['ReqDelDate'] = ConvertSQLDate($_POST['ReqDelDate']);}

$identifier = $_GET['identifier'];

/* If a purchase order header doesn't exist, then go to PO_Header.php to create one */
if (!isset($_SESSION['PO'.$identifier])) {
	header('Location:' . htmlspecialchars_decode($RootPath) . '/PO_Header.php');
	exit();
}

$ViewTopic = 'PurchaseOrdering';
$BookMark = 'PurchaseOrdering';
include(__DIR__ . '/includes/header.php');

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
	.aw-container { padding: 12px; max-width: 1600px; margin: 0 auto; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1200px) { 
		.aw-grid-main { grid-template-columns: 1fr 350px; align-items: start; } 
		.aw-grid-search { grid-template-columns: 320px 1fr; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; display: flex; align-items: center; gap: 8px; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select, .aw-textarea { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 10px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus, .aw-textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
	.aw-btn-danger { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
	.aw-btn-danger:hover { background: #ffe4e6; }
    .aw-btn-sm { padding: 4px 10px; font-size: 0.75rem; }

	.aw-stat-box { background: #f8fafc; padding: 12px; border-radius: 12px; margin-bottom: 12px; border: 1px solid var(--border-color); }
	.aw-stat-label { font-size: 0.65rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
	.aw-stat-val { font-size: 1.25rem; font-weight: 950; color: var(--primary-dark); }
</style>
<div class="aw-container">';

/* Logic blocks (Update, Commit, Delete, etc.) remain unchanged */
if (isset($_POST['UpdateLines']) OR isset($_POST['Commit'])) {
	foreach ($_SESSION['PO'.$identifier]->LineItems as $POLine) {
		if ($POLine->Deleted == false) {
			if (!is_numeric(filter_number_format($_POST['ConversionFactor'.$POLine->LineNo]))){
				prnMsg(__('The conversion factor is expected to be numeric'),'error');
				$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ConversionFactor = 1;
			} else {
				$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ConversionFactor = filter_number_format($_POST['ConversionFactor'.$POLine->LineNo]);
			}
			if (is_numeric(filter_number_format($_POST['SuppQty'.$POLine->LineNo]))){
				$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->Quantity = round(filter_number_format($_POST['SuppQty'.$POLine->LineNo])*$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ConversionFactor,$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->DecimalPlaces);
			}
			if (is_numeric(filter_number_format($_POST['SuppPrice'.$POLine->LineNo]))){
				$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->Price = filter_number_format($_POST['SuppPrice'.$POLine->LineNo])/$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ConversionFactor;
			}
			$_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ReqDelDate = $_POST['ReqDelDate'.$POLine->LineNo];
            $_SESSION['PO'.$identifier]->LineItems[$POLine->LineNo]->ItemDescription = $_POST['ItemDescription'.$POLine->LineNo];
		}
	}
}

if (isset($_POST['Commit'])){
	$InputError=0;
	if ($_SESSION['PO'.$identifier]->DelAdd1=='' or mb_strlen($_SESSION['PO'.$identifier]->DelAdd1)<3){
		prnMsg( __('street address specified'),'error');
		$InputError=1;
	} elseif ($_SESSION['PO'.$identifier]->Location=='' or ! isset($_SESSION['PO'.$identifier]->Location)){
		prnMsg( __('no location specified'),'error');
		$InputError=1;
	} elseif ($_SESSION['PO'.$identifier]->LinesOnOrder <=0){
		prnMsg( __('no lines entered'),'error');
		$InputError=1;
	}

	if ($InputError!=1){
		DB_Txn_Begin();
		if (IsEmailAddress($_SESSION['UserEmail'])){ $UserDetails  = ' ' . $_SESSION['UsersRealName']. ' '; } else { $UserDetails  = ' ' . $_SESSION['UsersRealName'] . ' '; }
		if ($_SESSION['AutoAuthorisePO']==1) {
			$AuthSQL ="SELECT authlevel FROM purchorderauth WHERE userid='".$_SESSION['UserID']."' AND currabrev='".$_SESSION['PO'.$identifier]->CurrCode."'";
			$AuthResult = DB_query($AuthSQL);
			$AuthRow=DB_fetch_array($AuthResult);
			if (DB_num_rows($AuthResult) > 0 AND $AuthRow['authlevel'] > $_SESSION['PO'.$identifier]->Order_Value()) {
				$_SESSION['PO'.$identifier]->AllowPrintPO=1;
				$_SESSION['PO'.$identifier]->Status = 'Authorised';
				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created and Authorised by') . $UserDetails . '<br />' .  $_SESSION['PO'.$identifier]->StatusComments . '<br />';
			} else {
				$_SESSION['PO'.$identifier]->AllowPrintPO=0;
				$_SESSION['PO'.$identifier]->Status = 'Pending';
				$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . $UserDetails . '<br />' . $_SESSION['PO'.$identifier]->StatusComments . '<br />';
			}
		} else {
			$_SESSION['PO'.$identifier]->AllowPrintPO=0;
			$_SESSION['PO'.$identifier]->Status = 'Pending';
			$StatusComment=date($_SESSION['DefaultDateFormat']).' - ' . __('Order Created by') . $UserDetails . ' - '.$_SESSION['PO'.$identifier]->StatusComments . '<br />';
		}

		if ($_SESSION['ExistingOrder']==0){
			$_SESSION['PO'.$identifier]->OrderNo = GetNextTransNo(18);
			if (!isset( $_SESSION['PO' . $identifier]->DeliveryDate)){ $_SESSION['PO' . $identifier]->DeliveryDate = ConvertSQLDate('1000-01-01'); }
			$SQL = "INSERT INTO purchorders ( orderno, supplierno, comments, orddate, rate, initiator, requisitionno, intostocklocation, deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, suppdeladdress1, suppdeladdress2, suppdeladdress3, suppdeladdress4, suppdeladdress5, suppdeladdress6, suppliercontact, supptel, contact, version, revised, deliveryby, status, stat_comment, deliverydate, paymentterms, allowprint)
							VALUES(	'" . $_SESSION['PO'.$identifier]->OrderNo . "', '" . $_SESSION['PO'.$identifier]->SupplierID . "', '" . $_SESSION['PO'.$identifier]->Comments . "', CURRENT_DATE, '" . $_SESSION['PO'.$identifier]->ExRate . "', '" . $_SESSION['PO'.$identifier]->Initiator . "', '" . $_SESSION['PO'.$identifier]->RequisitionNo . "', '" . $_SESSION['PO'.$identifier]->Location . "', '" . $_SESSION['PO'.$identifier]->DelAdd1 . "', '" . $_SESSION['PO'.$identifier]->DelAdd2 . "', '" . $_SESSION['PO'.$identifier]->DelAdd3 . "', '" . $_SESSION['PO'.$identifier]->DelAdd4 . "', '" . $_SESSION['PO'.$identifier]->DelAdd5 . "', '" . $_SESSION['PO'.$identifier]->DelAdd6 . "', '" . $_SESSION['PO'.$identifier]->Tel . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd1 . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd2 . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd3 . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd4 . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd5 . "', '" . $_SESSION['PO'.$identifier]->SuppDelAdd6 . "', '" . $_SESSION['PO'.$identifier]->SupplierContact . "', '" . $_SESSION['PO'.$identifier]->SuppTel. "', '" . $_SESSION['PO'.$identifier]->Contact . "', '" . $_SESSION['PO'.$identifier]->Version . "', CURRENT_DATE, '" . $_SESSION['PO'.$identifier]->DeliveryBy . "', '" . $_SESSION['PO'.$identifier]->Status . "', '" . htmlspecialchars($StatusComment,ENT_QUOTES,'UTF-8') . "', '" . FormatDateForSQL($_SESSION['PO'.$identifier]->DeliveryDate) . "', '" . $_SESSION['PO'.$identifier]->PaymentTerms. "', '" . $_SESSION['PO'.$identifier]->AllowPrintPO . "' )";
			DB_query($SQL);
			foreach ($_SESSION['PO'.$identifier]->LineItems as $POLine) {
				if ($POLine->Deleted==false) {
					$SQL = "INSERT INTO purchorderdetails (orderno, itemcode, deliverydate, itemdescription, glcode, unitprice, quantityord, shiptref, jobref, suppliersunit, suppliers_partno, assetid, conversionfactor )
							VALUES ('" . $_SESSION['PO'.$identifier]->OrderNo . "', '" . $POLine->StockID . "', '" . FormatDateForSQL($POLine->ReqDelDate) . "', '" . DB_escape_string($POLine->ItemDescription) . "', '" . $POLine->GLCode . "', '" . $POLine->Price . "', '" . $POLine->Quantity . "', '" . $POLine->ShiptRef . "', '" . $POLine->JobRef . "', '" . $POLine->SuppliersUnit . "', '" . DB_escape_string($POLine->Suppliers_PartNo) . "', '" . $POLine->AssetID . "', '" . $POLine->ConversionFactor . "')";
					DB_query($SQL);
				}
			}
			prnMsg(__('Purchase Order') . ' ' . $_SESSION['PO'.$identifier]->OrderNo . ' ' . __('created'),'success');
		} else {
			$Completed = true; foreach ($_SESSION['PO'.$identifier]->LineItems as $POLine) { if ($POLine->Completed==0){ $Completed = false; break; } }
			if ($Completed){ $_SESSION['PO'.$identifier]->Status = 'Completed'; }
			$SQL = "UPDATE purchorders SET supplierno = '" . $_SESSION['PO'.$identifier]->SupplierID . "' , comments='" . $_SESSION['PO'.$identifier]->Comments . "', rate='" . $_SESSION['PO'.$identifier]->ExRate . "', initiator='" . $_SESSION['PO'.$identifier]->Initiator . "', requisitionno= '" . $_SESSION['PO'.$identifier]->RequisitionNo . "', version= '" .  $_SESSION['PO'.$identifier]->Version . "', deliveryby='" . $_SESSION['PO'.$identifier]->DeliveryBy . "', deliverydate='" . FormatDateForSQL($_SESSION['PO'.$identifier]->DeliveryDate) . "', revised= CURRENT_DATE, intostocklocation='" . $_SESSION['PO'.$identifier]->Location . "', deladd1='" . $_SESSION['PO'.$identifier]->DelAdd1 . "', deladd2='" . $_SESSION['PO'.$identifier]->DelAdd2 . "', deladd3='" . $_SESSION['PO'.$identifier]->DelAdd3 . "', deladd4='" . $_SESSION['PO'.$identifier]->DelAdd4 . "', deladd5='" . $_SESSION['PO'.$identifier]->DelAdd5 . "', deladd6='" . $_SESSION['PO'.$identifier]->DelAdd6 . "', tel='" . $_SESSION['PO'.$identifier]->Tel . "', suppdeladdress1='" . $_SESSION['PO'.$identifier]->SuppDelAdd1 . "', suppdeladdress2='" . $_SESSION['PO'.$identifier]->SuppDelAdd2 . "', suppdeladdress3='" . $_SESSION['PO'.$identifier]->SuppDelAdd3 . "', suppdeladdress4='" . $_SESSION['PO'.$identifier]->SuppDelAdd4 . "', suppdeladdress5='" . $_SESSION['PO'.$identifier]->SuppDelAdd5 . "', suppdeladdress6='" . $_SESSION['PO'.$identifier]->SuppDelAdd6 . "', suppliercontact='" . $_SESSION['PO'.$identifier]->SupplierContact . "', supptel='" . $_SESSION['PO'.$identifier]->SuppTel . "', contact='" . $_SESSION['PO'.$identifier]->Contact . "', paymentterms='" . $_SESSION['PO'.$identifier]->PaymentTerms . "', allowprint='" . $_SESSION['PO'.$identifier]->AllowPrintPO . "', status = '" . $_SESSION['PO'.$identifier]->Status . "', stat_comment = '" . htmlspecialchars($_SESSION['PO'.$identifier]->StatusComments,ENT_QUOTES,'UTF-8') . "' WHERE orderno = '" . $_SESSION['PO'.$identifier]->OrderNo ."'";
			DB_query($SQL);
			foreach ($_SESSION['PO'.$identifier]->LineItems as $POLine) {
				if ($POLine->Deleted==true) { if ($POLine->PODetailRec!='') { DB_query("DELETE FROM purchorderdetails WHERE podetailitem='" . $POLine->PODetailRec . "'"); } }
				elseif ($POLine->PODetailRec=='') { $SQL = "INSERT INTO purchorderdetails ( orderno, itemcode, deliverydate, itemdescription, glcode, unitprice, quantityord, shiptref, jobref, suppliersunit, suppliers_partno, assetid, conversionfactor) VALUES ( '" . $_SESSION['PO'.$identifier]->OrderNo . "', '" . $POLine->StockID . "', '" . FormatDateForSQL($POLine->ReqDelDate) . "', '" . DB_escape_string($POLine->ItemDescription) . "', '" . $POLine->GLCode . "', '" . $POLine->Price . "', '" . $POLine->Quantity . "', '" . $POLine->ShiptRef . "', '" . $POLine->JobRef . "', '" . $POLine->SuppliersUnit . "', '" . $POLine->Suppliers_PartNo . "', '" . $POLine->AssetID . "', '" . $POLine->ConversionFactor . "')"; DB_query($SQL); }
				else { $CompletedLine = ($POLine->Quantity==$POLine->QtyReceived) ? 1 : 0; $SQL = "UPDATE purchorderdetails SET itemcode='" . $POLine->StockID . "', deliverydate ='" . FormatDateForSQL($POLine->ReqDelDate) . "', itemdescription='" . DB_escape_string($POLine->ItemDescription) . "', glcode='" . $POLine->GLCode . "', unitprice='" . $POLine->Price . "', quantityord='" . $POLine->Quantity . "', shiptref='" . $POLine->ShiptRef . "', jobref='" . $POLine->JobRef . "', suppliersunit='" . $POLine->SuppliersUnit . "', suppliers_partno='" . DB_escape_string($POLine->Suppliers_PartNo) . "', completed='" . $CompletedLine . "', assetid='" . $POLine->AssetID . "', conversionfactor = '" . $POLine->ConversionFactor . "' WHERE podetailitem='" . $POLine->PODetailRec . "'"; DB_query($SQL); }
			}
			prnMsg(__('Purchase Order') . ' ' . $_SESSION['PO'.$identifier]->OrderNo . ' ' . __('updated'),'success');
		}
		DB_Txn_Commit();
		unset($_SESSION['PO'.$identifier]);
		include(__DIR__ . '/includes/footer.php'); exit();
	}
}

if (isset($_GET['Delete'])){ if ($_SESSION['PO'.$identifier]->Some_Already_Received($_GET['Delete'])==0){ $_SESSION['PO'.$identifier]->remove_from_order($_GET['Delete']); include(__DIR__ . '/includes/PO_UnsetFormVbls.php'); } else { prnMsg( __('item already received'),'warn'); } }
if (isset($_GET['Complete'])){ $_SESSION['PO'.$identifier]->LineItems[$_GET['Complete']]->Completed=1; }

if (isset($_POST['EnterLine'])){
	$AllowUpdate = true;
	if (filter_number_format($_POST['Qty'])<0){ $AllowUpdate = false; prnMsg( __('quantity must be positive'),'error'); }
	if ($_SESSION['PO'.$identifier]->GLLink==1 OR $_SESSION['CompanyRecord']['gllink_creditors']==1){
		$SQL = "SELECT accountname FROM chartmaster WHERE accountcode ='" . $_POST['GLCode'] . "'";
		$GLValidResult = DB_query($SQL);
		if (DB_num_rows($GLValidResult) == 0) { $AllowUpdate = false; prnMsg( __('GL Code invalid'),'error'); } else { $MyRow = DB_fetch_row($GLValidResult); $GLAccountName = $MyRow[0]; }
	} else { $_POST['GLCode']=0; }
	if ($_POST['AssetID'] !='Not an Asset'){
		$ValidAssetResult = DB_query("SELECT assetid, description, costact FROM fixedassets INNER JOIN fixedassetcategories ON fixedassets.assetcategoryid=fixedassetcategories.categoryid WHERE assetid='" . $_POST['AssetID'] . "'");
		if (DB_num_rows($ValidAssetResult)==0){ $AllowUpdate = false; prnMsg(__('Asset does not exist'),'error'); } else { $AssetRow = DB_fetch_array($ValidAssetResult); $_POST['GLCode'] = $AssetRow['costact']; if ($_POST['ItemDescription']==''){ $_POST['ItemDescription'] = $AssetRow['description']; } }
	} else { $_POST['AssetID'] = 0; }
	if ($AllowUpdate == true){ $_SESSION['PO'.$identifier]->add_to_order($_SESSION['PO'.$identifier]->LinesOnOrder+1, '', 0, 0, filter_number_format($_POST['Qty']), $_POST['ItemDescription'], filter_number_format($_POST['Price']), $_POST['SuppliersUnit'], $_POST['GLCode'], $_POST['ReqDelDate'], '', 0, '', 0, 0, $GLAccountName, 2, $_POST['SuppliersUnit'], 1, 1, '', $_POST['AssetID']); include(__DIR__ . '/includes/PO_UnsetFormVbls.php'); }
}

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Purchasing / Batch Operations</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
		<div class="aw-actions">
			<a href="' . $RootPath . '/PO_Header.php?identifier=' . $identifier . '" class="aw-btn aw-btn-secondary"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"></path></svg> ' . __('Back To Header') . '</a>
		</div>
	  </div>';

echo '<form id="PO_ItemsForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '" method="post" enctype="multipart/form-data">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="aw-grid aw-grid-main">';

// MAIN CONTENT (Left)
echo '<main class="aw-main-side">';

if (count($_SESSION['PO' . $identifier]->LineItems) > 0) {
	echo '<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg> ' . __('Current Order Lines') . '</h3></div>
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('Item Code') . '</th>
							<th>' . __('Description') . '</th>
							<th style="width:100px; text-align:right;">' . __('Order Qty') . '</th>
							<th>' . __('Units') . '</th>
							<th style="width:100px; text-align:right;">' . __('Price') . '</th>
							<th style="width:120px; text-align:right;">' . __('Total Value') . '</th>
							<th>' . __('Delivery Date') . '</th>
							<th style="text-align:center;">' . __('Action') . '</th>
						</tr>
					</thead>
					<tbody>';
	foreach ($_SESSION['PO' . $identifier]->LineItems as $POLine) {
		if ($POLine->Deleted == false) {
			echo '<tr>
					<td style="font-weight:700; color:var(--primary);">' . $POLine->StockID . '</td>
					<td><input type="text" class="aw-input" style="padding:4px;" name="ItemDescription' . $POLine->LineNo . '" value="' . $POLine->ItemDescription . '" /></td>
					<td><input type="text" class="aw-input" style="text-align:right; font-weight:800;" name="SuppQty' . $POLine->LineNo . '" value="' . locale_number_format($POLine->Quantity / $POLine->ConversionFactor, $POLine->DecimalPlaces) . '" />
						<input type="hidden" name="ConversionFactor' . $POLine->LineNo . '" value="' . $POLine->ConversionFactor . '" /></td>
					<td>' . $POLine->SuppliersUnit . '</td>
					<td><input type="text" class="aw-input" style="text-align:right;" name="SuppPrice' . $POLine->LineNo . '" value="' . locale_number_format($POLine->Price * $POLine->ConversionFactor, $_SESSION['PO' . $identifier]->CurrDecimalPlaces) . '" /></td>
					<td style="text-align:right; font-weight:850; color:var(--primary-dark);">' . locale_number_format($POLine->Price * $POLine->Quantity, $_SESSION['PO' . $identifier]->CurrDecimalPlaces) . '</td>
					<td><input type="date" class="aw-input" name="ReqDelDate' . $POLine->LineNo . '" value="' . $POLine->ReqDelDate . '" /></td>
					<td style="text-align:center;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '&Delete=' . $POLine->LineNo . '" class="aw-btn-danger aw-btn-sm" style="display:inline-flex;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a></td>
				  </tr>';
		}
	}
	echo '				</tbody>
				</table>
			</div>
			<div class="aw-card-body" style="background:#fbfcfd; border-top:1px solid var(--border-color); text-align:right;">
				<button type="submit" name="UpdateLines" class="aw-btn aw-btn-secondary">' . __('Refresh Totals') . '</button>
			</div>
		  </div>';
}

echo '<div class="aw-card">
		<div class="aw-card-header"><h3 class="aw-card-title"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"></path></svg> ' . __('Add Non-Stock Items') . '</h3></div>
		<div class="aw-card-body">
			<div class="aw-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
				<div class="aw-form-group"><label class="aw-label">' . __('Description') . '</label><input type="text" name="ItemDescription" class="aw-input" placeholder="Service or manual item name" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Qty') . '</label><input type="text" name="Qty" class="aw-input" value="1" style="text-align:right;" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Price') . ' (' . $_SESSION['PO' . $identifier]->CurrCode . ')</label><input type="text" name="Price" class="aw-input" value="0.00" style="text-align:right;" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('UOM') . '</label><input type="text" name="SuppliersUnit" class="aw-input" value="' . __('each') . '" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('GL Account') . '</label><select name="GLCode" class="aw-select">';
					$SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
					$GLRes = DB_query($SQL);
					while ($GLRow = DB_fetch_array($GLRes)) { echo '<option value="' . $GLRow['accountcode'] . '">' . $GLRow['accountcode'] . ' - ' . $GLRow['accountname'] . '</option>'; }
echo '				</select>
				</div>
                <div class="aw-form-group"><label class="aw-label">' . __('Fixed Asset') . '</label><select name="AssetID" class="aw-select"><option value="Not an Asset">' . __('Not an Asset') . '</option>';
                    $AssetRes = DB_query("SELECT assetid, description FROM fixedassets ORDER BY assetid");
                    while ($AssetRow = DB_fetch_array($AssetRes)) { echo '<option value="' . $AssetRow['assetid'] . '">' . $AssetRow['assetid'] . ' - ' . $AssetRow['description'] . '</option>'; }
echo '              </select></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Needed Date') . '</label><input type="date" name="ReqDelDate" class="aw-input" value="' . date('Y-m-d') . '" /></div>
			</div>
			<div style="text-align:right; margin-top:12px;"><button type="submit" name="EnterLine" class="aw-btn aw-btn-primary">' . __('Add to Order') . '</button></div>
		</div>
	  </div>';

echo '</main>';

// SIDEBAR (Right)
echo '<aside class="aw-sidebar-side">
		<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Order Summary') . '</h3></div>
			<div class="aw-card-body">
				<div class="aw-stat-box">
					<div class="aw-stat-label">Total Value (' . $_SESSION['PO' . $identifier]->CurrCode . ')</div>
					<div class="aw-stat-val">' . locale_number_format($_SESSION['PO' . $identifier]->Order_Value(), $_SESSION['PO' . $identifier]->CurrDecimalPlaces) . '</div>
				</div>
				<div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:12px;">
					<div style="display:flex; justify-content:space-between; margin-bottom:4px;"><span>' . __('Vendor') . ':</span><span style="font-weight:700; color:var(--primary-dark);">' . $_SESSION['PO' . $identifier]->SupplierID . '</span></div>
					<div style="display:flex; justify-content:space-between;"><span>' . __('Status') . ':</span><span class="aw-badge aw-badge-info">' . (isset($_SESSION['PO' . $identifier]->Status) ? $_SESSION['PO' . $identifier]->Status : __('New')) . '</span></div>
				</div>
				<button type="submit" name="Commit" class="aw-btn aw-btn-primary" style="width:100%; height:48px; font-size:0.9rem;">' . __('Place Purchase Order') . '</button>
			</div>
		</div>
		
		<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Bulk Import') . '</h3></div>
			<div class="aw-card-body">
				<div class="aw-form-group"><label class="aw-label">' . __('CSV File (Code,Qty)') . '</label><input type="file" name="CSVFile" class="aw-input" /></div>
				<button type="submit" name="UploadFile" class="aw-btn aw-btn-secondary" style="width:100%">' . __('Upload & Pulse') . '</button>
			</div>
		</div>
	  </aside>';

echo '</div>'; // End aw-grid-main

// SEARCH SECTION
echo '<div class="aw-grid aw-grid-search" style="margin-top:32px; border-top: 1px solid var(--border-color); padding-top:32px;">';
echo '<aside class="aw-sidebar-side">
		<div class="aw-card">
			<div class="aw-card-header"><h3 class="aw-card-title">' . __('Product Catalog Search') . '</h3></div>
			<div class="aw-card-body">
				<div class="aw-form-group"><label class="aw-label">' . __('Category') . '</label><select name="StockCat" class="aw-select">';
				$StockCatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
				echo '<option value="All">' . __('All Categories') . '</option>';
				while ($CatRow = DB_fetch_array($StockCatRes)) { 
					$sel = (isset($_POST['StockCat']) && $_POST['StockCat'] == $CatRow['categoryid']) ? 'selected' : '';
					echo '<option ' . $sel . ' value="' . $CatRow['categoryid'] . '">' . $CatRow['categorydescription'] . '</option>'; 
				}
echo '				</select></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Keywords') . '</label><input type="text" name="Keywords" class="aw-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" /></div>
				<div class="aw-form-group"><label class="aw-label">' . __('Stock Code') . '</label><input type="text" name="SearchCode" class="aw-input" value="' . (isset($_POST['SearchCode']) ? $_POST['SearchCode'] : '') . '" /></div>
				<button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%">' . __('Find Items') . '</button>
			</div>
		</div>
	  </aside>';

echo '<main class="aw-main-side">';
if (isset($_POST['Search'])) {
	$SearchString = (mb_strlen($_POST['Keywords']) > 0) ? '%' . str_replace(' ', '%', $_POST['Keywords']) . '%' : '%';
	$CodeFilter = (mb_strlen($_POST['SearchCode']) > 0) ? '%' . $_POST['SearchCode'] . '%' : '%';
	$CatFilter = ($_POST['StockCat'] == 'All') ? "" : " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
	
	$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.units FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid WHERE stockmaster.mbflag!='D' AND stockmaster.mbflag!='A' AND stockmaster.mbflag!='K' AND stockmaster.discontinued!=1 AND (stockmaster.description " . LIKE . " '$SearchString' AND stockmaster.stockid " . LIKE . " '$CodeFilter') $CatFilter ORDER BY stockmaster.stockid LIMIT 50";
	$SearchResult = DB_query($SQL);
	
	echo '<div class="aw-card">
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('Code') . '</th>
							<th>' . __('Description') . '</th>
							<th>' . __('Units') . '</th>
							<th style="width:100px;">' . __('Qty') . '</th>
							<th style="text-align:center;">' . __('Action') . '</th>
						</tr>
					</thead>
					<tbody>';
	$si = 0;
	while ($SItem = DB_fetch_array($SearchResult)) {
		echo '<tr>
				<td style="font-weight:700;">' . $SItem['stockid'] . '</td>
				<td>' . $SItem['description'] . '</td>
				<td>' . $SItem['units'] . '</td>
				<td><input type="text" class="aw-input" style="text-align:right;" name="NewQty' . $si . '" value="0" /></td>
				<td style="text-align:center;"><input type="hidden" name="StockID' . $si . '" value="' . $SItem['stockid'] . '" /><button type="submit" name="NewItem" value="' . $SItem['stockid'] . '" class="aw-btn aw-btn-outline aw-btn-sm">' . __('Select') . '</button></td>
			  </tr>';
		$si++;
	}
	echo '				</tbody>
				</table>
			</div>
			<div class="aw-card-body" style="background:#fbfcfd; border-top:1px solid var(--border-color); text-align:right;">
				<button type="submit" name="NewItem" value="Multi" class="aw-btn aw-btn-primary">' . __('Add Selected to Order') . '</button>
			</div>
		  </div>';
} else {
	echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Search results will appear here.') . '</div></div>';
}
echo '</main>';
echo '</div>'; // End aw-grid-search

echo '</form></div>'; // End aw-container

include(__DIR__ . '/includes/footer.php');
?>
