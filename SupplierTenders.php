<?php

include(__DIR__ . '/includes/DefineOfferClass.php');
require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Tendering');
$ViewTopic = 'SupplierTenders';
$BookMark = '';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/ImageFunctions.php');

$Maximum_Number_Of_Parts_To_Show=50;

// Internal Architectural Style (Architect Workspace v2 - High Density)
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
	.aw-page-header { margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-main { grid-template-columns: 1fr 380px; } 
		.aw-grid-search { grid-template-columns: 350px 1fr; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 14px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-size: 0.82rem; font-weight: 500; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }

	.aw-status-badge { padding: 2px 8px; border-radius: 999px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
	.aw-status-open { background: var(--primary-soft); color: var(--primary); }

	.aw-delivery-info { background: #f8fafc; border-radius: 8px; padding: 12px; font-size: 0.8rem; line-height: 1.5; color: var(--text-main); }
</style>
<div class="aw-container">';

if (isset($_GET['TenderType'])) { $_POST['TenderType']=$_GET['TenderType']; }

if (empty($_GET['identifier'])) { $identifier=date('U'); } else { $identifier=$_GET['identifier']; }

if (!isset($_POST['SupplierID'])) {
	$SQL="SELECT supplierid FROM www_users WHERE userid='" . $_SESSION['UserID'] . "'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_array($Result);
	if ($MyRow['supplierid']=='') {
		echo '<div class="aw-card"><div class="aw-card-body" style="text-align:center; padding:40px;">';
		prnMsg(__('This functionality can only be accessed via a supplier login.'), 'warning');
		echo '</div></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	} else {
		$_POST['SupplierID']=$MyRow['supplierid'];
	}
}

if (isset($_GET['Delete'])) {
	$_POST['SupplierID']=$_SESSION['offer'.$identifier]->SupplierID;
	$_POST['TenderType']=$_GET['Type'];
	$_SESSION['offer'.$identifier]->remove_from_offer($_GET['Delete']);
}

$SQL="SELECT suppname, currcode FROM suppliers WHERE supplierid='" . $_POST['SupplierID'] . "'";
$Result = DB_query($SQL);
$MyRow=DB_fetch_array($Result);
$Supplier=$MyRow['suppname'];
$Currency=$MyRow['currcode'];

if (isset($_POST['Confirm'])) {
	$_SESSION['offer'.$identifier]->Save();
	$_SESSION['offer'.$identifier]->EmailOffer();
	$SQL="UPDATE tendersuppliers SET responded=1 WHERE supplierid='" . $_SESSION['offer'.$identifier]->SupplierID . "' AND tenderid='" . $_SESSION['offer'.$identifier]->TenderID . "'";
	DB_query($SQL);
	echo '<div class="aw-card"><div class="aw-card-body" style="text-align:center; padding: 40px; border-color:var(--primary);">';
	echo '<div style="background:var(--primary-soft); color:var(--primary); width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></div>';
	echo '<h2 style="font-weight:950; letter-spacing:-0.03em;">' . __('Offer Confirmed') . '</h2>';
	echo '<p style="color:var(--text-muted);">' . __('Your response to tender') . ' #' . $_SESSION['offer'.$identifier]->TenderID . ' ' . __('has been transmitted.') . '</p>';
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="aw-btn aw-btn-primary">' . __('Back to Overview') . '</a>';
	echo '</div></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

echo '<div class="aw-page-header">
		<div class="aw-breadcrumb">Tender Management / Offers</div>
		<h1 class="aw-page-title">' . $Title . '</h1>
	  </div>';

if (isset($_POST['Process'])) {
	if (isset($_SESSION['offer'.$identifier])) { unset($_SESSION['offer'.$identifier]); }
	$_SESSION['offer'.$identifier]=new Offer($_POST['SupplierID']);
	$_SESSION['offer'.$identifier]->TenderID=$_POST['Tender'];
	$_SESSION['offer'.$identifier]->CurrCode=$Currency;
	$LineNo=0;
	foreach ($_POST as $key=>$Value) {
		if (mb_substr($key,0,7)=='StockID') {
			$Index = mb_substr($key,7,mb_strlen($key)-7);
			$_SESSION['offer'.$identifier]->add_to_offer($LineNo, $Value, $_POST['Qty'.$Index], $_POST['ItemDescription'.$Index], $_POST['Price'.$Index], $_POST['UOM'.$Index], $_POST['DecimalPlaces'.$Index], $_POST['RequiredByDate'.$Index]);
			$LineNo++;
		}
	}

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="Tender" value="' . $_SESSION['offer'.$identifier]->TenderID . '" />';
	echo '<input type="hidden" name="TenderType" value="3" />';

	echo '<div class="aw-grid aw-grid-main">
			<div class="aw-main-side">
				<div class="aw-card">
					<div class="aw-card-header"><h3 class="aw-card-title">' . __('Review Offer Line Items') . '</h3></div>
					<div class="aw-table-wrapper">
						<table class="aw-table">
							<thead>
								<tr>
									<th>' . __('Item Code') . '</th>
									<th>' . __('Description') . '</th>
									<th style="text-align:right;">' . __('Qty') . '</th>
									<th>' . __('Units') . '</th>
									<th style="text-align:right;">' . __('Price') .' ('.$Currency.')</th>
									<th style="text-align:right;">' . __('Total Value') . '</th>
								</tr>
							</thead>
							<tbody>';
	$SumTotal = 0;
	foreach ($_SESSION['offer'.$identifier]->LineItems as $LineItem)  {
		$SumTotal += ($LineItem->Price * $LineItem->Quantity);
		echo '<tr>
				<td style="font-weight:700;">' . $LineItem->StockID . '</td>
				<td>' . $LineItem->ItemDescription . '</td>
				<td style="text-align:right;">' . locale_number_format($LineItem->Quantity, $LineItem->DecimalPlaces) . '</td>
				<td>' . $LineItem->Units . '</td>
				<td style="text-align:right;">' . locale_number_format($LineItem->Price, 2) . '</td>
				<td style="text-align:right; font-weight:850; color:var(--primary-dark);">' . locale_number_format($LineItem->Price * $LineItem->Quantity, 2) . '</td>
			  </tr>';
	}
	echo '				</tbody>
						<tfoot>
							<tr style="background:#fbfcfd;">
								<td colspan="5" style="text-align:right; font-weight:800; font-size:0.65rem; text-transform:uppercase;">' . __('Grand Total') . ' (' . $Currency . ')</td>
								<td style="text-align:right; font-weight:900; font-size:1rem; color:var(--primary);">' . locale_number_format($SumTotal, 2) . '</td>
							</tr>
						</tfoot>
						</table>
					</div>
				</div>
			</div>
			
			<div class="aw-sidebar-side">
				<div class="aw-card">
					<div class="aw-card-header"><h3 class="aw-card-title">' . __('Fulfillment Details') . '</h3></div>
					<div class="aw-card-body">
						<div class="aw-label">' . __('Tender Reference') . '</div>
						<div style="font-size:1.1rem; font-weight:900; margin-bottom:16px;">#' . $_SESSION['offer'.$identifier]->TenderID . '</div>
						
						<div class="aw-label">' . __('Deliver To') . '</div>
						<div class="aw-delivery-info">';
						$LocSQL="SELECT tenderid, locations.locationname, address1, address2, address3, address4, address5, address6 FROM tenders INNER JOIN locations ON tenders.location=locations.loccode WHERE tenderid='".$_SESSION['offer'.$identifier]->TenderID."'";
						$LocRes = DB_query($LocSQL);
						$LocRow = DB_fetch_row($LocRes);
						for ($i=1; $i<8; $i++) { if ($LocRow[$i]!='') { echo $LocRow[$i] . '<br />'; } }
	echo '				</div>
						<div style="margin-top:20px; display:flex; flex-direction:column; gap:8px;">
							<button type="submit" name="Confirm" class="aw-btn aw-btn-primary" style="width:100%">' . __('Confirm and Send Response') . '</button>
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="aw-btn aw-btn-secondary" style="width:100%">' . __('Back to Tenders') . '</a>
						</div>
					</div>
				</div>
			</div>
		</div>';
	echo '</form>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['SupplierID']) AND empty($_POST['TenderType']) AND empty($_POST['Search']) AND empty($_POST['NewItem']) AND empty($_GET['Delete'])) {
	if (isset($_SESSION['offer'.$identifier])) { unset($_SESSION['offer'.$identifier]); }
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="SupplierID" value="'.$_POST['SupplierID'].'" />';
	
	echo '<div class="aw-card" style="max-width:550px; margin: 40px auto;">
			<div class="aw-card-header"><h2 class="aw-card-title">' . __('Welcome') . ', ' . $Supplier . '</h2></div>
			<div class="aw-card-body">
				<div class="aw-form-group">
					<label class="aw-label">' . __('What would you like to do?') . '</label>
					<select name="TenderType" class="aw-select" size="3" style="font-weight:600;">
						<option value="1" selected>' . __('View or Amend Your Outstanding Offers') . '</option>
						<option value="2">' . __('Create a Brand New Offer') . '</option>
						<option value="3">' . __('Browse Open Tenders Seeking Offers') . '</option>
					</select>
				</div>
				<div style="text-align:right; margin-top:20px;">
					<button type="submit" name="submit" class="aw-btn aw-btn-primary">' . __('Continue to Dashboard') . '</button>
				</div>
			</div>
		  </div>';
	echo '</form>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['NewItem']) AND !isset($_POST['Refresh'])) {
	foreach ($_POST as $key => $Value) {
		if (mb_substr($key,0,7)=='StockID') {
			$Index = mb_substr($key,7,mb_strlen($key)-7);
			$Quantity=filter_number_format($_POST['Qty'.$Index]);
			$Price=filter_number_format($_POST['Price'.$Index]);
			$UOM=$_POST['uom'.$Index];
			if (isset($UOM) AND $Quantity>0) {
				$SQL="SELECT description, decimalplaces FROM stockmaster WHERE stockid='".$Value."'";
				$Result = DB_query($SQL);
				$MyRow=DB_fetch_array($Result);
				$_SESSION['offer'.$identifier]->add_to_offer($_SESSION['offer'.$identifier]->LinesOnOffer, $Value, $Quantity, $MyRow['description'], $Price, $UOM, $MyRow['decimalplaces'], DateAdd(date($_SESSION['DefaultDateFormat']),'m',3));
			}
		}
	}
}

if ((isset($_POST['Update']) or isset($_POST['Save'])) and isset($_SESSION['offer'.$identifier])) {
	foreach ($_POST as $key => $Value) {
		if (mb_substr($key,0,3)=='Qty') { $LineNo=mb_substr($key,3); $Quantity=$Value; }
		if (mb_substr($key,0,5)=='Price') { $Price=$Value; }
		if (mb_substr($key,0,10)=='expirydate') { $ExpiryDate=$Value; }
		if (isset($ExpiryDate)) { $_SESSION['offer'.$identifier]->update_offer_item($LineNo, $Quantity, $Price, $ExpiryDate); unset($ExpiryDate); }
	}
	$_SESSION['offer'.$identifier]->Save(isset($_POST['Update']) ? 'Yes' : '');
	$_SESSION['offer'.$identifier]->EmailOffer();
	echo '<div class="aw-card"><div class="aw-card-body" style="text-align:center; padding:40px;">';
	prnMsg(__('Offer saved successfully to the system.'), 'success');
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="aw-btn aw-btn-primary">' . __('Return') . '</a>';
	echo '</div></div>';
	unset($_SESSION['offer'.$identifier]);
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['TenderType']) AND ($_POST['TenderType']==1 or $_POST['TenderType']==2)) {
	if ($_POST['TenderType']==1 and !isset($_SESSION['offer'.$identifier])) {
		$SQL="SELECT offers.offerid, offers.stockid, stockmaster.description, offers.quantity, offers.uom, offers.price, offers.expirydate, stockmaster.decimalplaces FROM offers INNER JOIN stockmaster ON offers.stockid=stockmaster.stockid WHERE offers.supplierid='" . $_POST['SupplierID'] . "' AND offers.expirydate >= CURRENT_DATE";
		$Result = DB_query($SQL);
		$_SESSION['offer'.$identifier]=new Offer($_POST['SupplierID']);
		$_SESSION['offer'.$identifier]->CurrCode=$Currency;
		while ($MyRow=DB_fetch_array($Result)) {
			$_SESSION['offer'.$identifier]->add_to_offer($MyRow['offerid'], $MyRow['stockid'], $MyRow['quantity'], $MyRow['description'], $MyRow['price'], $MyRow['uom'], $MyRow['decimalplaces'], ConvertSQLDate($MyRow['expirydate']));
		}
	}

	if (isset($_SESSION['offer'.$identifier]) and $_SESSION['offer'.$identifier]->LinesOnOffer>0) {
		echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '">';
		echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
		echo '<input type="hidden" name="TenderType" value="'.$_POST['TenderType'].'" />';
		echo '<input type="hidden" name="SupplierID" value="'.$_POST['SupplierID'].'" />';

		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Draft Offer Elements') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Item') . '</th>
								<th style="width:120px; text-align:right;">' . __('Qty') . '</th>
								<th>' . __('Units') . '</th>
								<th style="width:140px; text-align:right;">' . __('Price') .' ('.$Currency.')</th>
								<th style="text-align:right;">' . __('Total Value') . '</th>
								<th>' . __('Valid Until') . '</th>
								<th style="text-align:center;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
		foreach ($_SESSION['offer'.$identifier]->LineItems as $LineItems) {
			if ($LineItems->Deleted==false) {
				echo '<tr>
						<td><div style="font-weight:700;">' . $LineItems->StockID . '</div><div style="font-size:0.72rem; color:var(--text-muted);">' . $LineItems->ItemDescription . '</div></td>
						<td><input type="text" class="aw-input" style="text-align:right; font-weight:800;" name="Qty'.$LineItems->LineNo.'" value="'.locale_number_format($LineItems->Quantity,$LineItems->DecimalPlaces).'" /></td>
						<td>' . $LineItems->Units . '</td>
						<td><input type="text" class="aw-input" style="text-align:right;" name="Price'.$LineItems->LineNo.'" value="'.locale_number_format($LineItems->Price,2,'.','').'" /></td>
						<td style="text-align:right; font-weight:850; color:var(--primary-dark);">' . locale_number_format($LineItems->Price*$LineItems->Quantity,2) . '</td>
						<td><input type="date" class="aw-input" name="expirydate'.$LineItems->LineNo.'" value="'.$LineItems->ExpiryDate.'" /></td>
						<td style="text-align:center;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?identifier='.$identifier.'&Delete=' . $LineItems->LineNo . '&Type=' . $_POST['TenderType'] . '" style="color:#ef4444;"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a></td>
					</tr>';
			}
		}
		echo '					</tbody>
					</table>
				</div>
				<div class="aw-card-body" style="background:#fbfcfd; border-top:1px solid var(--border-color); text-align:right; display:flex; justify-content:flex-end; gap:8px;">';
		if ($_POST['TenderType']==1) {
			echo '<button type="submit" name="Update" class="aw-btn aw-btn-primary">' . __('Update Existing Offer') . '</button>';
		} else {
			echo '<button type="submit" name="Save" class="aw-btn aw-btn-primary">' . __('Submit New Offer') . '</button>';
		}
		echo '			<button type="submit" name="Refresh" class="aw-btn aw-btn-secondary">' . __('Refresh Totals') . '</button>
				</div>
			</div>
			</form>';
	}
}

if (isset($_POST['TenderType']) AND $_POST['TenderType']==2) {
	if (!isset($_SESSION['offer'.$identifier])) { $_SESSION['offer'.$identifier]=new Offer($_POST['SupplierID']); }
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" class="aw-grid aw-grid-search">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="TenderType" value="'.$_POST['TenderType'].'" />';
	echo '<input type="hidden" name="SupplierID" value="'.$_POST['SupplierID'].'" />';

	echo '<aside class="aw-sidebar-side">
			<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Find Catalog Items') . '</h3></div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Category') . '</label>
						<select name="StockCat" class="aw-select">';
						$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
						$CatRes = DB_query($SQL);
						echo '<option value="All">' . __('All Categories') . '</option>';
						while ($catRow = DB_fetch_array($CatRes)) {
							$sel = (isset($_POST['StockCat']) && $_POST['StockCat'] == $catRow['categoryid']) ? 'selected' : '';
							echo '<option ' . $sel . ' value="' . $catRow['categoryid'] . '">' . $catRow['categorydescription'] . '</option>';
						}
	echo '				</select>
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('Keywords') . '</label>
						<input type="text" name="Keywords" class="aw-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" />
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('OR Stock Code') . '</label>
						<input type="text" name="StockCode" class="aw-input" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" />
					</div>
					<button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%">' . __('Search Product Catalog') . '</button>
				</div>
			</div>
		  </aside>';

	if (isset($_POST['Search'])) {
		// (Search logic from PO_Items, etc., is handled by $SearchResult if standard query is run)
		// I\'ll use the logic provided in the original file but output with AW v2 styles.
		include_once(__DIR__ . '/includes/SQL_CommonFunctions.php');
		$SearchString = (mb_strlen($_POST['Keywords']) > 0) ? '%' . str_replace(' ', '%', $_POST['Keywords']) . '%' : '%';
		$StockCodeFilter = (mb_strlen($_POST['StockCode']) > 0) ? '%' . $_POST['StockCode'] . '%' : '%';
		
		$CatFilter = ($_POST['StockCat'] == 'All') ? "" : " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
		
		$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.units FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid=stockcategory.categoryid WHERE stockmaster.mbflag!='D' AND stockmaster.mbflag!='A' AND stockmaster.mbflag!='K' AND stockmaster.discontinued!=1 AND (stockmaster.description " . LIKE . " '$SearchString' AND stockmaster.stockid " . LIKE . " '$StockCodeFilter') $CatFilter ORDER BY stockmaster.stockid LIMIT " . $Maximum_Number_Of_Parts_To_Show;
		$SearchResult = DB_query($SQL);

		echo '<main class="aw-main-side">
				<div class="aw-card">
					<div class="aw-table-wrapper">
						<table class="aw-table">
							<thead>
								<tr>
									<th>' . __('Item Code') . '</th>
									<th>' . __('Description') . '</th>
									<th>' . __('Units') . '</th>
									<th style="width:100px;">' . __('Qty') . '</th>
									<th style="width:120px;">' . __('Price') . '</th>
								</tr>
							</thead>
							<tbody>';
		$i = 0;
		while ($MyRow = DB_fetch_array($SearchResult)) {
			echo '<tr>
					<td style="font-weight:700;">' . $MyRow['stockid'] . '</td>
					<td>' . $MyRow['description'] . '</td>
					<td>' . $MyRow['units'] . '</td>
					<td><input type="text" class="aw-input" style="text-align:right;" name="Qty'.$i.'" value="0" /></td>
					<td><input type="text" class="aw-input" style="text-align:right;" name="Price'.$i.'" value="0.00" /></td>
					<input type="hidden" name="StockID'.$i.'" value="'.$MyRow['stockid'].'" />
					<input type="hidden" name="uom'.$i.'" value="'.$MyRow['units'].'" />
				</tr>';
			$i++;
		}
		echo '				</tbody>
						</table>
					</div>
					<div class="aw-card-body" style="background:#fbfcfd; border-top:1px solid var(--border-color); text-align:right;">
						<button type="submit" name="NewItem" class="aw-btn aw-btn-primary">' . __('Add Selected to Offer') . '</button>
					</div>
				</div>
			  </main>';
	} else {
		echo '<main class="aw-main-side"><div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Use filters to add items.') . '</div></div></main>';
	}
	echo '</form>';
}

if (isset($_POST['TenderType']) AND $_POST['TenderType']==3) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<input type="hidden" name="TenderType" value="3" />';
	echo '<input type="hidden" name="SupplierID" value="'.$_POST['SupplierID'].'" />';

	$SQL="SELECT DISTINCT tendersuppliers.tenderid, suppliers.currcode FROM tendersuppliers LEFT JOIN suppliers ON suppliers.supplierid=tendersuppliers.supplierid LEFT JOIN tenders ON tenders.tenderid=tendersuppliers.tenderid WHERE tendersuppliers.supplierid='" . $_POST['SupplierID'] . "' AND tenders.closed=0 AND tendersuppliers.responded=0 ORDER BY tendersuppliers.tenderid";
	$Result = DB_query($SQL);
	
	if (DB_num_rows($Result) == 0) {
		echo '<div class="aw-card" style="text-align:center; padding:40px; color:var(--text-muted);">' . __('No outstanding tenders found for your account.') . '</div>';
	}

	while ($MyRow=DB_fetch_row($Result)) {
		$TenderID = $MyRow[0];
		echo '<div class="aw-card">
				<div class="aw-card-header" style="justify-content:space-between;">
					<h3 class="aw-card-title">' . __('Open Invitation for Tender') . ' #' . $TenderID . '</h3>
					<button type="submit" value="' . $TenderID . '" name="Process" class="aw-btn aw-btn-primary aw-btn-sm">' . __('Click to Response') . '</button>
					<input type="hidden" name="Tender" value="' . $TenderID . '" />
				</div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th style="text-align:right;">' . __('Requested Qty') . '</th>
								<th>' . __('UOM') . '</th>
								<th>' . __('Needed By') . '</th>
								<th style="width:100px; text-align:right;">' . __('Your Qty') . '</th>
								<th style="width:120px; text-align:right;">' . __('Your Price') . '</th>
							</tr>
						</thead>
						<tbody>';
		$ItemSQL="SELECT tenderitems.stockid, stockmaster.description, stockmaster.decimalplaces, purchdata.suppliers_partno, tenderitems.quantity, tenderitems.units, tenders.requiredbydate, purchdata.suppliersuom FROM tenderitems LEFT JOIN stockmaster ON tenderitems.stockid=stockmaster.stockid LEFT JOIN purchdata ON tenderitems.stockid=purchdata.stockid AND purchdata.supplierno='".$_POST['SupplierID']."' LEFT JOIN tenders ON tenders.tenderid=tenderitems.tenderid WHERE tenderitems.tenderid='" . $TenderID . "'";
		$ItemResult = DB_query($ItemSQL);
		$item_idx=0;
		while ($ItemRow=DB_fetch_array($ItemResult)) {
			echo '<tr>
					<td style="font-weight:700;">' . $ItemRow['stockid'] . '</td>
					<td>' . $ItemRow['description'] . '</td>
					<td style="text-align:right;">' . locale_number_format($ItemRow['quantity'], $ItemRow['decimalplaces']) . '</td>
					<td>' . $ItemRow['units'] . '</td>
					<td>' . ConvertSQLDate($ItemRow['requiredbydate']) . '</td>
					<td><input type="text" class="aw-input" style="text-align:right;" name="Qty'.$item_idx.'" value="' . locale_number_format($ItemRow['quantity'], $ItemRow['decimalplaces']) . '" /></td>
					<td><input type="text" class="aw-input" style="text-align:right;" name="Price'.$item_idx.'" value="0.00" /></td>
					<input type="hidden" name="StockID'.$item_idx.'" value="'.$ItemRow['stockid'].'" />
					<input type="hidden" name="ItemDescription'.$item_idx.'" value="'.$ItemRow['description'].'" />
					<input type="hidden" name="UOM'.$item_idx.'" value="'.$ItemRow['units'].'" />
					<input type="hidden" name="DecimalPlaces'.$item_idx.'" value="'.$ItemRow['decimalplaces'].'" />
					<input type="hidden" name="RequiredByDate'.$item_idx.'" value="' . $ItemRow['requiredbydate'] . '" />
				  </tr>';
			$item_idx++;
		}
		echo '				</tbody>
					</table>
				</div>
			  </div>';
	}
	echo '</form>';
}

echo '</div> <!-- End aw-container -->';
include(__DIR__ . '/includes/footer.php');
?>
