<?php

include(__DIR__ . '/includes/DefineTenderClass.php');
require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/ImageFunctions.php');

if (isset($_POST['RequiredByDate'])){$_POST['RequiredByDate'] = ConvertSQLDate($_POST['RequiredByDate']);}

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (isset($_GET['New']) and isset($_SESSION['tender' . $identifier])) {
	unset($_SESSION['tender' . $identifier]);
}

if (isset($_GET['New']) and $_SESSION['CanCreateTender'] == 0) {
	$Title = __('Authorisation Problem');
	include(__DIR__ . '/includes/header.php');
	echo '
	<style>
		:root {
			--primary: hsl(145, 63%, 38%);
			--bg-workspace: hsl(210, 20%, 97%);
		}
		.aw-container { padding: 32px; font-family: "Inter", sans-serif; }
		.aw-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; }
	</style>
	<div class="aw-container">
		<div class="aw-card">
			<h2 style="color:var(--primary); margin-top:0;">' . $Title . '</h2>
			<p>' . __('You do not have authority to create supplier tenders for this company.') . '</p>
		</div>
	</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

// ... auth checks continue ...

if (isset($_POST['Close'])) {
	$SQL = "UPDATE tenders SET closed=1 WHERE tenderid='" . $_SESSION['tender' . $identifier]->TenderId . "'";
	$Result = DB_query($SQL);
	$_GET['Edit'] = 'Yes';
	unset($_SESSION['tender' . $identifier]);
}

$ShowTender = 0;

if (isset($_GET['ID'])) {
	$SQL = "SELECT tenderid, location, address1, address2, address3, address4, address5, address6, telephone, requiredbydate
				FROM tenders
				INNER JOIN locationusers ON locationusers.loccode=tenders.location AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE tenderid='" . $_GET['ID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if (isset($_SESSION['tender' . $identifier])) {
		unset($_SESSION['tender' . $identifier]);
	}
	$_SESSION['tender' . $identifier] = new Tender();
	$_SESSION['tender' . $identifier]->TenderId = $MyRow['tenderid'];
	$_SESSION['tender' . $identifier]->Location = $MyRow['location'];
	$_SESSION['tender' . $identifier]->DelAdd1 = $MyRow['address1'];
	$_SESSION['tender' . $identifier]->DelAdd2 = $MyRow['address2'];
	$_SESSION['tender' . $identifier]->DelAdd3 = $MyRow['address3'];
	$_SESSION['tender' . $identifier]->DelAdd4 = $MyRow['address4'];
	$_SESSION['tender' . $identifier]->DelAdd5 = $MyRow['address5'];
	$_SESSION['tender' . $identifier]->DelAdd6 = $MyRow['address6'];
	$_SESSION['tender' . $identifier]->RequiredByDate = FormatDateForSQL(ConvertSQLDate($MyRow['requiredbydate']));

	$SQL = "SELECT tenderid, tendersuppliers.supplierid, suppliers.suppname, tendersuppliers.email
				FROM tendersuppliers LEFT JOIN suppliers ON tendersuppliers.supplierid=suppliers.supplierid
				WHERE tenderid='" . $_GET['ID'] . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$_SESSION['tender' . $identifier]->add_supplier_to_tender($MyRow['supplierid'], $MyRow['suppname'], $MyRow['email']);
	}

	$SQL = "SELECT tenderid, tenderitems.stockid, tenderitems.quantity, stockmaster.description, tenderitems.units, stockmaster.decimalplaces
				FROM tenderitems LEFT JOIN stockmaster ON tenderitems.stockid=stockmaster.stockid
				WHERE tenderid='" . $_GET['ID'] . "'";
	$Result = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Result)) {
		$_SESSION['tender' . $identifier]->add_item_to_tender($_SESSION['tender' . $identifier]->LinesOnTender, $MyRow['stockid'], $MyRow['quantity'], $MyRow['description'], $MyRow['units'], $MyRow['decimalplaces'], DateAdd(date($_SESSION['DefaultDateFormat']), 'm', 3));
	}
	$ShowTender = 1;
}

// Global UI styles
$ArchitectStyles = '
<style>
	:root {
		--primary: hsl(145, 63%, 38%);
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-soft: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-muted: #64748b;
		--radius: 12px;
	}

	body { background: var(--bg-workspace); font-family: "Inter", sans-serif; }
	.aw-container { padding: 12px; max-width: 1600px; margin: 0 auto; }
	
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }
	
	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { .aw-grid-main { grid-template-columns: 1fr 380px; } .aw-grid-search { grid-template-columns: 350px 1fr; } }

	.aw-card { background: white; border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 8px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
	.aw-card-body { padding: 12px; }

	.aw-form-group { margin-bottom: 12px; }
	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-size: 0.82rem; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-table { width: 100%; border-collapse: collapse; }
	.aw-table th { text-align: left; background: #f8fafc; padding: 10px 14px; font-size: 0.62rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 10px 14px; border-bottom: 1px solid var(--border-color); font-size: 0.8rem; }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border-radius: 8px; font-weight: 750; font-size: 0.85rem; cursor: pointer; border: none; gap: 8px; transition: 0.2s; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); }
	.aw-btn-secondary { background: #f1f5f9; color: #475569; }
	.aw-btn-outline { background: transparent; border: 1.5px solid var(--border-color); color: var(--primary-dark); }
</style>';

if (isset($_GET['Edit'])) {
	$Title = __('Edit an Existing Supplier Tender Request');
	include(__DIR__ . '/includes/header.php');
	echo $ArchitectStyles;
	echo '<div class="aw-container">
			<div class="aw-breadcrumb">Tenders / Manage</div>
			<h1 class="aw-page-title">' . $Title . '</h1>';
			
	$SQL = "SELECT tenderid, location, address1, address2, address3, address4, address5, address6, telephone
				FROM tenders
				INNER JOIN locationusers ON locationusers.loccode=tenders.location AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1
				WHERE closed=0";
	$Result = DB_query($SQL);
	echo '<div class="aw-card" style="margin-top:24px;">
			<div class="aw-table-wrapper">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('ID') . '</th>
							<th>' . __('Location') . '</th>
							<th>' . __('Address Details') . '</th>
							<th>' . __('Phone') . '</th>
							<th class="text-center">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><span style="font-weight:900; color:var(--primary);">' . $MyRow['tenderid'] . '</span></td>
				<td>' . $MyRow['location'] . '</td>
				<td>' . $MyRow['address1'] . ', ' . $MyRow['address2'] . '</td>
				<td>' . $MyRow['telephone'] . '</td>
				<td style="text-align:center;">
					<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . $identifier . '&amp;ID=' . $MyRow['tenderid'] . '" class="aw-btn aw-btn-outline aw-btn-sm">' . __('Open') . '</a>
				</td>
			</tr>';
	}
	echo '      </tbody>
				</table>
			</div>
		</div>
	</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

// ... more logic ...

if (isset($_GET['ID']) or (isset($_SESSION['tender' . $identifier]->TenderId))) {
	$Title = __('Edit an Existing Supplier Tender Request');
} else {
	$Title = __('Create a New Supplier Tender Request');
}

include(__DIR__ . '/includes/header.php');
echo $ArchitectStyles;

if (isset($_POST['Save'])) {
	$_SESSION['tender' . $identifier]->RequiredByDate = $_POST['RequiredByDate'];
	$_SESSION['tender' . $identifier]->save();
	$_SESSION['tender' . $identifier]->EmailSuppliers();
	echo '<div class="aw-container"><div class="aw-card" style="text-align:center; padding: 40px; border-color:var(--primary);">
			<h2 style="color:var(--primary);">' . __('Tender Saved Successfully') . '</h2>
			<p>' . __('Supplier tender request has been broadcasted.') . '</p>
			<a href="index.php" class="aw-btn aw-btn-primary">' . __('Return Home') . '</a>
		  </div></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_GET['DeleteSupplier'])) {
	$_SESSION['tender' . $identifier]->remove_supplier_from_tender($_GET['DeleteSupplier']);
	$ShowTender = 1;
}

if (isset($_GET['DeleteItem'])) {
	$_SESSION['tender' . $identifier]->remove_item_from_tender($_GET['DeleteItem']);
	$ShowTender = 1;
}

if (isset($_POST['SelectedSupplier'])) {
	$SQL = "SELECT suppname, email FROM suppliers WHERE supplierid='" . $_POST['SelectedSupplier'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if (mb_strlen($MyRow['email']) > 0) {
		$_SESSION['tender' . $identifier]->add_supplier_to_tender($_POST['SelectedSupplier'], $MyRow['suppname'], $MyRow['email']);
	} else {
		prnMsg(__('The supplier must have an email set up or they cannot be part of a tender'), 'warn');
	}
	$ShowTender = 1;
}

if (isset($_POST['NewItem']) and !isset($_POST['Refresh'])) {
	foreach ($_POST as $key => $Value) {
		if (mb_substr($key, 0, 7) == 'StockID') {
			$Index = mb_substr($key, 7, mb_strlen($key) - 7);
			$StockID = $Value;
			$Quantity = filter_number_format($_POST['Qty' . $Index]);
			$UOM = $_POST['UOM' . $Index];
			$SQL = "SELECT description, decimalplaces FROM stockmaster WHERE stockid='" . $StockID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_array($Result);
			$_SESSION['tender' . $identifier]->add_item_to_tender($_SESSION['tender' . $identifier]->LinesOnTender, $StockID, $Quantity, $MyRow['description'], $UOM, $MyRow['decimalplaces'], DateAdd(date($_SESSION['DefaultDateFormat']), 'm', 3));
		}
	}
	$ShowTender = 1;
}

if (!isset($_SESSION['tender' . $identifier]) or isset($_POST['LookupDeliveryAddress']) or $ShowTender == 1) {

	if (!isset($_SESSION['tender' . $identifier])) {
		$_SESSION['tender' . $identifier] = new Tender();
	}
	if (!isset($_SESSION['tender' . $identifier]->RequiredByDate)) {
		$_SESSION['tender' . $identifier]->RequiredByDate = FormatDateForSQL(date($_SESSION['DefaultDateFormat']));
	}

	echo '<div class="aw-container">';
	echo '<div class="aw-breadcrumb">Purchasing / Tenders</div>';
	echo '<h1 class="aw-page-title">' . $Title . '</h1>';
	
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" class="noPrint">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (!isset($_POST['StkLocation']) or $_POST['StkLocation'] == '') {
		$_POST['StkLocation'] = $_SESSION['UserStockLocation'];
		$SQL = "SELECT deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, contact FROM locations WHERE locations.loccode='" . $_POST['StkLocation'] . "'";
		$LocnAddrResult = DB_query($SQL);
		if (DB_num_rows($LocnAddrResult) == 1) {
			$LocnRow = DB_fetch_array($LocnAddrResult);
			$_POST['DelAdd1'] = $LocnRow['deladd1'];
			$_POST['DelAdd2'] = $LocnRow['deladd2'];
			$_POST['DelAdd3'] = $LocnRow['deladd3'];
			$_POST['DelAdd4'] = $LocnRow['deladd4'];
			$_POST['DelAdd5'] = $LocnRow['deladd5'];
			$_POST['DelAdd6'] = $LocnRow['deladd6'];
			$_POST['Tel'] = $LocnRow['tel'];
			$_POST['Contact'] = $LocnRow['contact'];
			$_SESSION['tender' . $identifier]->Location = $_POST['StkLocation'];
			$_SESSION['tender' . $identifier]->DelAdd1 = $_POST['DelAdd1'];
			$_SESSION['tender' . $identifier]->DelAdd2 = $_POST['DelAdd2'];
			$_SESSION['tender' . $identifier]->DelAdd3 = $_POST['DelAdd3'];
			$_SESSION['tender' . $identifier]->DelAdd4 = $_POST['DelAdd4'];
			$_SESSION['tender' . $identifier]->DelAdd5 = $_POST['DelAdd5'];
			$_SESSION['tender' . $identifier]->DelAdd6 = $_POST['DelAdd6'];
			$_SESSION['tender' . $identifier]->Telephone = $_POST['Tel'];
			$_SESSION['tender' . $identifier]->Contact = $_POST['Contact'];
		}
	} elseif (isset($_POST['LookupDeliveryAddress'])) {
		$SQL = "SELECT deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, contact FROM locations WHERE locations.loccode='" . $_POST['StkLocation'] . "'";
		$LocnAddrResult = DB_query($SQL);
		if (DB_num_rows($LocnAddrResult) == 1) {
			$LocnRow = DB_fetch_array($LocnAddrResult);
			$_POST['DelAdd1'] = $LocnRow['deladd1'];
			$_POST['DelAdd2'] = $LocnRow['deladd2'];
			$_POST['DelAdd3'] = $LocnRow['deladd3'];
			$_POST['DelAdd4'] = $LocnRow['deladd4'];
			$_POST['DelAdd5'] = $LocnRow['deladd5'];
			$_POST['DelAdd6'] = $LocnRow['deladd6'];
			$_POST['Tel'] = $LocnRow['tel'];
			$_POST['Contact'] = $LocnRow['contact'];
			$_SESSION['tender' . $identifier]->Location = $_POST['StkLocation'];
			$_SESSION['tender' . $identifier]->DelAdd1 = $_POST['DelAdd1'];
			$_SESSION['tender' . $identifier]->DelAdd2 = $_POST['DelAdd2'];
			$_SESSION['tender' . $identifier]->DelAdd3 = $_POST['DelAdd3'];
			$_SESSION['tender' . $identifier]->DelAdd4 = $_POST['DelAdd4'];
			$_SESSION['tender' . $identifier]->DelAdd5 = $_POST['DelAdd5'];
			$_SESSION['tender' . $identifier]->DelAdd6 = $_POST['DelAdd6'];
			$_SESSION['tender' . $identifier]->Telephone = $_POST['Tel'];
			$_SESSION['tender' . $identifier]->Contact = $_POST['Contact'];
		}
	}

	echo '<div class="aw-grid aw-grid-main">';
	
	echo '<div class="aw-main-side">';
	echo '	<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Tender Items') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Description') . '</th>
								<th style="text-align:right;">' . __('Qty') . '</th>
								<th>' . __('Units') . '</th>
								<th style="text-align:center;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
	if (empty($_SESSION['tender' . $identifier]->LineItems)) {
		echo '<tr><td colspan="5" style="text-align:center; padding: 32px; color:var(--text-muted);">' . __('No items added yet.') . '</td></tr>';
	} else {
		foreach ($_SESSION['tender' . $identifier]->LineItems as $LineItems) {
			if ($LineItems->Deleted == false) {
				echo '<tr>
						<td style="font-weight:700;">' . $LineItems->StockID . '</td>
						<td>' . $LineItems->ItemDescription . '</td>
						<td style="text-align:right;">' . locale_number_format($LineItems->Quantity, $LineItems->DecimalPlaces) . '</td>
						<td>' . $LineItems->Units . '</td>
						<td style="text-align:center;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?identifier=' . $identifier, ENT_QUOTES, 'UTF-8') . '&amp;DeleteItem=' . $LineItems->LineNo . '" style="color:#ef4444;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a></td>
					</tr>';
			}
		}
	}
	echo '					</tbody>
					</table>
				</div>
				<div class="aw-card-body" style="background:#f8fafc; border-top:1px solid var(--border-color); text-align:right;">
					<button type="submit" name="Items" class="aw-btn aw-btn-outline"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg> ' . __('Add Items') . '</button>
				</div>
			</div>';

	echo '	<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Selected Suppliers') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Supplier Name') . '</th>
								<th style="text-align:center;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
	if (empty($_SESSION['tender' . $identifier]->Suppliers)) {
		echo '<tr><td colspan="3" style="text-align:center; padding: 32px; color:var(--text-muted);">' . __('No suppliers selected.') . '</td></tr>';
	} else {
		foreach ($_SESSION['tender' . $identifier]->Suppliers as $Supplier) {
			echo '<tr>
					<td style="font-weight:700; color:var(--primary);">' . $Supplier->SupplierCode . '</td>
					<td>' . $Supplier->SupplierName . '</td>
					<td style="text-align:center;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'] . '?identifier=' . $identifier, ENT_QUOTES, 'UTF-8') . '&amp;DeleteSupplier=' . $Supplier->SupplierCode . '" style="color:#ef4444;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></a></td>
				</tr>';
		}
	}
	echo '					</tbody>
					</table>
				</div>
				<div class="aw-card-body" style="background:#f8fafc; border-top:1px solid var(--border-color); text-align:right;">
					<button type="submit" name="Suppliers" class="aw-btn aw-btn-outline"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg> ' . __('Add Suppliers') . '</button>
				</div>
			</div>';
	echo '</div>'; // End Main Side

	echo '<div class="aw-sidebar-side">';
	echo '	<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Tender Logistics') . '</h3></div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Required By Date') . '</label>
						<input type="date" required name="RequiredByDate" class="aw-input" value="' . $_SESSION['tender' . $identifier]->RequiredByDate . '" />
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('Receiving Warehouse') . '</label>
						<select name="StkLocation" class="aw-select" onchange="this.form.submit()">';
						$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1";
						$LocnResult = DB_query($SQL);
						while ($LocnRow = DB_fetch_array($LocnResult)) {
							$sel = ($_SESSION['tender' . $identifier]->Location == $LocnRow['loccode']) ? 'selected' : '';
							echo '<option ' . $sel . ' value="' . $LocnRow['loccode'] . '">' . $LocnRow['locationname'] . '</option>';
						}
	echo '				</select>
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('Delivery Address') . '</label>
						<input type="text" name="DelAdd1" class="aw-input" style="margin-bottom:8px;" placeholder="Line 1" value="' . $_SESSION['tender' . $identifier]->DelAdd1 . '" />
						<input type="text" name="DelAdd2" class="aw-input" style="margin-bottom:8px;" placeholder="Line 2" value="' . $_SESSION['tender' . $identifier]->DelAdd2 . '" />
						<input type="text" name="DelAdd3" class="aw-input" style="margin-bottom:8px;" placeholder="City" value="' . $_SESSION['tender' . $identifier]->DelAdd3 . '" />
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('Contact Person') . '</label>
						<input type="text" name="Contact" class="aw-input" value="' . $_SESSION['tender' . $identifier]->Contact . '" />
					</div>
					<div style="margin-top:24px; display:flex; flex-direction:column; gap:8px;">';
					if ($_SESSION['tender' . $identifier]->LinesOnTender > 0 and $_SESSION['tender' . $identifier]->SuppliersOnTender > 0) {
						echo '<button type="submit" name="Save" class="aw-btn aw-btn-primary" style="width:100%">' . __('Save & Send Tender') . '</button>';
						echo '<button type="submit" name="Close" class="aw-btn aw-btn-secondary" style="width:100%">' . __('Discard Tender') . '</button>';
					} else {
						echo '<p style="font-size:0.75rem; color: #ef4444; font-weight:700; text-align:center;">' . __('Add items and suppliers to proceed.') . '</p>';
					}
	echo '			</div>
				</div>
			</div>';
	echo '</div>'; // End Sidebar

	echo '</div>'; // End Grid
	echo '</form></div>'; // End Container
	include(__DIR__ . '/includes/footer.php');
	exit();
}

// SEARCH SCREENS (Suppliers & Items)
if (isset($_POST['Suppliers']) or isset($_POST['SearchSupplier'])) {
	echo '<div class="aw-container">';
	echo '<div class="aw-breadcrumb">Tender / Selection</div>';
	echo '<h1 class="aw-page-title">' . __('Select Suppliers') . '</h1>';
	
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" class="aw-grid aw-grid-search">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<aside class="aw-search-filters">
			<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Search Criteria') . '</h3></div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Supplier Name') . '</label>
						<input type="text" name="Keywords" class="aw-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="Search by name..." />
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('OR Supplier Code') . '</label>
						<input type="text" name="SupplierCode" class="aw-input" value="' . (isset($_POST['SupplierCode']) ? $_POST['SupplierCode'] : '') . '" placeholder="Search by ID..." />
					</div>
					<button type="submit" name="SearchSupplier" class="aw-btn aw-btn-primary" style="width:100%">' . __('Search Suppliers') . '</button>
				</div>
			</div>
		  </aside>';

	echo '<main class="aw-search-results">
			<div class="aw-card">';
	
	if (isset($_POST['SearchSupplier'])) {
		// ... standard ZERP search logic ...
		if (isset($Result)) {
			echo '<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Supplier Name') . '</th>
								<th>' . __('Currency') . '</th>
								<th>' . __('Address') . '</th>
								<th style="text-align:center;">' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
			while ($MyRow = DB_fetch_array($Result)) {
				echo '<tr>
						<td style="font-weight:700;">' . $MyRow['supplierid'] . '</td>
						<td style="font-weight:600;">' . $MyRow['suppname'] . '</td>
						<td>' . $MyRow['currcode'] . '</td>
						<td>' . $MyRow['address1'] . ', ' . $MyRow['address2'] . '</td>
						<td style="text-align:center;">
							<button type="submit" name="SelectedSupplier" value="' . $MyRow['supplierid'] . '" class="aw-btn aw-btn-outline aw-btn-sm">' . __('Select') . '</button>
						</td>
					</tr>';
			}
			echo '</tbody></table></div>';
		}
	} else {
		echo '<div style="padding: 48px; text-align:center; color:var(--text-muted);">' . __('Enter criteria on the left to find suppliers.') . '</div>';
	}
	
	echo '	</div>
		  </main>';
	
	echo '</form></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['Items']) or isset($_POST['Search'])) {
	echo '<div class="aw-container">';
	echo '<div class="aw-breadcrumb">Tender / Selection</div>';
	echo '<h1 class="aw-page-title">' . __('Select Items') . '</h1>';
	
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post" class="aw-grid aw-grid-search">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<aside class="aw-search-filters">
			<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Filter Items') . '</h3></div>
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
						<input type="text" name="Keywords" class="aw-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="Search description..." />
					</div>
					<button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%">' . __('Find Items') . '</button>
				</div>
			</div>
		  </aside>';

	echo '<main class="aw-search-results">
			<div class="aw-card">';
	
	if (isset($_POST['Search'])) {
		// ... standard ZERP search results ...
		if (isset($SearchResult)) {
			echo '<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Code') . '</th>
								<th>' . __('Product') . '</th>
								<th>' . __('Units') . '</th>
								<th style="width:100px; text-align:right;">' . __('Qty') . '</th>
							</tr>
						</thead>
						<tbody>';
			$i = 0;
			while ($MyRow = DB_fetch_array($SearchResult)) {
				echo '<tr>
						<td style="font-weight:700;">' . $MyRow['stockid'] . '</td>
						<td style="font-weight:600;">' . $MyRow['description'] . '</td>
						<td>' . $MyRow['units'] . '</td>
						<td style="text-align:right;">
							<input type="hidden" value="' . $MyRow['units'] . '" name="UOM' . $i . '" />
							<input type="hidden" value="' . $MyRow['stockid'] . '" name="StockID' . $i . '" />
							<input type="text" class="aw-input" style="text-align:right; padding: 4px 8px;" value="0" name="Qty' . $i . '" />
						</td>
					</tr>';
				$i++;
			}
			echo '</tbody></table></div>
				<div class="aw-card-body" style="background:#f8fafc; border-top:1px solid var(--border-color); text-align:right;">
					<button type="submit" name="NewItem" class="aw-btn aw-btn-primary">' . __('Add Selected to Tender') . '</button>
				</div>';
		}
	} else {
		echo '<div style="padding: 48px; text-align:center; color:var(--text-muted);">' . __('Enter criteria on the left to find items.') . '</div>';
	}
	
	echo '	</div>
		  </main>';
	
	echo '</form></div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

include(__DIR__ . '/includes/footer.php');
?>
