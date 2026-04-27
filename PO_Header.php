<?php

// NB: these classes are not autoloaded, and their definition has to be included before the session is started (in session.php)
include(__DIR__ . '/includes/DefinePOClass.php');

require(__DIR__ . '/includes/session.php');

if (isset($_GET['ModifyOrderNumber'])) {
	$Title = __('Modify Purchase Order') . ' ' . $_GET['ModifyOrderNumber'];
} else {
	$Title = __('Purchase Order Entry');
}
$ViewTopic = 'PurchaseOrdering';
$BookMark = 'PurchaseOrdering';
include(__DIR__ . '/includes/header.php');

// Define the Architect Workspace Design System
echo '
<style>
	:root {
		--primary: hsl(145, 63%, 38%); 
		--primary-hover: hsl(145, 63%, 32%);
		--primary-dark: hsl(145, 45%, 22%);
		--primary-bg: hsl(145, 40%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--text-main: hsl(145, 15%, 12%);
		--text-muted: hsl(145, 8%, 50%);
		--card-bg: #ffffff;
		--border-color: hsl(220, 15%, 88%);
		--radius: 12px;
		--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
		--shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
		--space-1: 0.125rem;
		--space-2: 0.25rem;
		--space-3: 0.5rem;
		--space-4: 0.75rem;
		--space-6: 1rem;
		--space-8: 1.5rem;
	}

	body {
		background-color: var(--bg-workspace);
		font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		color: var(--text-main);
	}

	.aw-container {
		max-width: 100%;
		padding: var(--space-4);
	}

	.aw-card {
		background: var(--card-bg);
		border-radius: var(--radius);
		border: 1px solid var(--border-color);
		box-shadow: var(--shadow-sm);
		overflow: hidden;
		margin-bottom: var(--space-6);
		transition: box-shadow 0.2s ease;
	}

	.aw-card:hover {
		box-shadow: var(--shadow);
	}

	.aw-card-header {
		padding: var(--space-4) var(--space-4);
		border-bottom: 1px solid var(--border-color);
		display: flex;
		align-items: center;
		justify-content: space-between;
		background-color: #ffffff;
	}

	.aw-card-title {
		font-size: 0.95rem;
		font-weight: 800;
		color: var(--primary-dark);
		margin: 0;
		display: flex;
		align-items: center;
		gap: var(--space-2);
		text-transform: uppercase;
		letter-spacing: 0.025em;
	}

	.aw-card-body {
		padding: var(--space-4);
	}

	.aw-card-footer {
		padding: var(--space-4) var(--space-4);
		background: #fbfcfd;
		border-top: 1px solid var(--border-color);
		display: flex;
		justify-content: flex-end;
		gap: var(--space-4);
	}

	.aw-page-header {
		display: flex;
		flex-direction: column;
		gap: var(--space-1);
		margin-bottom: var(--space-6);
	}

	@media (min-width: 768px) {
		.aw-page-header {
			flex-direction: row;
			justify-content: space-between;
			align-items: center;
			padding: 10px 0;
		}
	}

	.aw-page-title-group h1 {
		font-size: 1.75rem;
		font-weight: 900;
		letter-spacing: -0.04em;
		color: var(--primary-dark);
		margin: 0;
	}

	.aw-breadcrumb {
		display: flex;
		align-items: center;
		gap: var(--space-2);
		font-size: 0.75rem;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: 0.1em;
		color: var(--primary);
		margin-bottom: var(--space-1);
	}

	.aw-grid-main {
		display: grid;
		gap: var(--space-6);
		grid-template-columns: 1fr;
	}

	@media (min-width: 1024px) {
		.aw-grid-main {
			grid-template-columns: 1fr 350px;
			align-items: start;
		}
		.aw-grid-selection {
			grid-template-columns: 350px 1fr;
			align-items: start;
		}
	}

	.aw-section-header {
		display: flex;
		align-items: center;
		gap: var(--space-3);
		margin-bottom: var(--space-4);
		padding-bottom: var(--space-1);
	}

	.aw-section-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 32px;
		height: 32px;
		background: var(--primary-bg);
		color: var(--primary);
		border-radius: 6px;
	}

	.aw-section-title {
		font-size: 0.8rem;
		font-weight: 800;
		text-transform: uppercase;
		letter-spacing: 0.075em;
		color: var(--primary-dark);
	}

	.aw-form-row {
		display: grid;
		gap: var(--space-4);
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	}

	.aw-sticky-side {
		position: sticky;
		top: var(--space-4);
	}

	@media (max-width: 640px) {
		.aw-btn { width: 100%; }
		.aw-input-group { flex-direction: column; }
	}
</style>
<div class="aw-container">';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['DeliveryDate'])){$_POST['DeliveryDate'] = ConvertSQLDate($_POST['DeliveryDate']);}

if (isset($_GET['SupplierID'])) {
	$_POST['Select'] = $_GET['SupplierID'];
}

if (empty($_GET['identifier'])) {
	$identifier = date('U');
} else {
	$identifier = $_GET['identifier'];
}

if (isset($_GET['NewOrder']) and isset($_SESSION['PO' . $identifier])) {
	unset($_SESSION['PO' . $identifier]);
	$_SESSION['ExistingOrder'] = 0;
}

if (isset($_POST['Select']) and empty($_POST['SupplierContact'])) {
	$SQL = "SELECT contact
				FROM suppliercontacts
				WHERE supplierid='" . $_POST['Select'] . "'";

	$SuppCoResult = DB_query($SQL);
	if (DB_num_rows($SuppCoResult) > 0) {
		$MyRow = DB_fetch_row($SuppCoResult);
		$_POST['SupplierContact'] = $MyRow[0];
	} else {
		$_POST['SupplierContact'] = '';
	}
}

// Update Status Logic
if ((isset($_POST['UpdateStatus']) and $_POST['UpdateStatus'] != '')) {
	if ($_SESSION['ExistingOrder'] == 0) {
		prnMsg(__('This is a new order. It must be created before you can change the status'), 'warn');
		$OKToUpdateStatus = 0;
	} elseif ($_SESSION['PO' . $identifier]->Status != $_POST['Status']) {
		$OKToUpdateStatus = 1;
		$AuthSQL = "SELECT authlevel
					FROM purchorderauth
					WHERE userid='" . $_SESSION['UserID'] . "'
					AND currabrev='" . $_SESSION['PO' . $identifier]->CurrCode . "'";

		$AuthResult = DB_query($AuthSQL);
		$MyRow = DB_fetch_array($AuthResult);
		$AuthorityLevel = $MyRow['authlevel'];
		$OrderTotal = $_SESSION['PO' . $identifier]->Order_Value();

		if ($_POST['StatusComments'] != '') {
			$_POST['StatusComments'] = ' - ' . $_POST['StatusComments'];
		}
		if (IsEmailAddress($_SESSION['UserEmail'])) {
			$UserChangedStatus = ' <a href="mailto:' . $_SESSION['UserEmail'] . '">' . $_SESSION['UsersRealName'] . '</a>';
		} else {
			$UserChangedStatus = ' ' . $_SESSION['UsersRealName'] . ' ';
		}

		if ($_POST['Status'] == 'Authorised') {
			if ($AuthorityLevel > $OrderTotal) {
				$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Authorised by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');
				$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
			} else {
				$OKToUpdateStatus = 0;
				prnMsg(__('You do not have permission to authorise this purchase order'), 'warn');
			}
		}

		if ($_POST['Status'] == 'Rejected' or $_POST['Status'] == 'Cancelled') {
			if (!isset($_SESSION['ExistingOrder']) or $_SESSION['ExistingOrder'] != 0) {
				if ($_SESSION['PO' . $identifier]->Any_Already_Received() == 1) {
					$OKToUpdateStatus = 0;
					prnMsg(__('This order cannot be cancelled because it has receipts'), 'warn');
				}
			}
			if ($OKToUpdateStatus == 1) {
				if ($AuthorityLevel > $OrderTotal) {
					$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . $_POST['Status'] . ' ' . __('by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');
				} else {
					$OKToUpdateStatus = 0;
					prnMsg(__('You do not have permission to reject this purchase order'), 'warn');
				}
			}
		}
		if ($_POST['Status'] == 'Pending') {
			if ($OKToUpdateStatus == 1) {
				$_SESSION['PO' . $identifier]->StatusComments = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order set to pending status by') . $UserChangedStatus . $_POST['StatusComments'] . '<br />' . html_entity_decode($_POST['StatusCommentsComplete'], ENT_QUOTES, 'UTF-8');
			}
		}
		if ($OKToUpdateStatus == 1) {
			$_SESSION['PO' . $identifier]->Status = $_POST['Status'];
			$AllowPrint = ($_SESSION['PO' . $identifier]->Status == 'Authorised') ? 1 : 0;
			$SQL = "UPDATE purchorders SET status='" . $_POST['Status'] . "',
							stat_comment='" . $_SESSION['PO' . $identifier]->StatusComments . "',
							allowprint='" . $AllowPrint . "'
					WHERE purchorders.orderno ='" . $_SESSION['ExistingOrder'] . "'";
			DB_query($SQL);
			if (in_array($_POST['Status'], ['Completed', 'Cancelled', 'Rejected'])) {
				DB_query("UPDATE purchorderdetails SET completed=1 WHERE orderno='" . $_SESSION['ExistingOrder'] . "'");
			} else {
				DB_query("UPDATE purchorderdetails SET completed=0 WHERE orderno='" . $_SESSION['ExistingOrder'] . "'");
			}
		}
	}
}

if (isset($_GET['NewOrder']) and isset($_GET['StockID']) and isset($_GET['SelectedSupplier'])) {
	$_SESSION['ExistingOrder'] = 0;
	unset($_SESSION['PO' . $identifier]);
	$_SESSION['PO' . $identifier] = new PurchOrder;
	$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
	$_SESSION['PO' . $identifier]->GLLink = $_SESSION['CompanyRecord']['gllink_stock'];
	$_SESSION['PO' . $identifier]->SupplierID = $_GET['SelectedSupplier'];
	$_SESSION['PO' . $identifier]->DeliveryDate = date($_SESSION['DefaultDateFormat']);
	$_SESSION['PO' . $identifier]->Initiator = $_SESSION['UserID'];
	$_SESSION['RequireSupplierSelection'] = 0;
	$_POST['Select'] = $_GET['SelectedSupplier'];
	$Purch_Item = $_GET['StockID'];
}

if (isset($_POST['EnterLines']) or isset($_POST['AllowRePrint'])) {
	$_SESSION['PO' . $identifier]->Location = $_POST['StkLocation'];
	$_SESSION['PO' . $identifier]->SupplierContact = $_POST['SupplierContact'] ?? '';
	$_SESSION['PO' . $identifier]->DelAdd1 = $_POST['DelAdd1'];
	$_SESSION['PO' . $identifier]->DelAdd2 = $_POST['DelAdd2'];
	$_SESSION['PO' . $identifier]->DelAdd3 = $_POST['DelAdd3'];
	$_SESSION['PO' . $identifier]->DelAdd4 = $_POST['DelAdd4'];
	$_SESSION['PO' . $identifier]->DelAdd5 = $_POST['DelAdd5'];
	$_SESSION['PO' . $identifier]->DelAdd6 = $_POST['DelAdd6'];
	$_SESSION['PO' . $identifier]->SuppDelAdd1 = $_POST['SuppDelAdd1'];
	$_SESSION['PO' . $identifier]->SuppDelAdd2 = $_POST['SuppDelAdd2'];
	$_SESSION['PO' . $identifier]->SuppDelAdd3 = $_POST['SuppDelAdd3'];
	$_SESSION['PO' . $identifier]->SuppDelAdd4 = $_POST['SuppDelAdd4'];
	$_SESSION['PO' . $identifier]->SuppDelAdd5 = $_POST['SuppDelAdd5'];
	$_SESSION['PO' . $identifier]->SuppTel = $_POST['SuppTel'];
	$_SESSION['PO' . $identifier]->Initiator = $_POST['Initiator'];
	$_SESSION['PO' . $identifier]->RequisitionNo = $_POST['Requisition'];
	$_SESSION['PO' . $identifier]->Version = $_POST['Version'];
	$_SESSION['PO' . $identifier]->DeliveryDate = $_POST['DeliveryDate'];
	$_SESSION['PO' . $identifier]->Revised = $_POST['Revised'];
	$_SESSION['PO' . $identifier]->ExRate = filter_number_format($_POST['ExRate']);
	$_SESSION['PO' . $identifier]->Comments = $_POST['Comments'];
	$_SESSION['PO' . $identifier]->DeliveryBy = $_POST['DeliveryBy'];
	if (isset($_POST['StatusComments'])) {
		$_SESSION['PO' . $identifier]->StatusComments = $_POST['StatusComments'];
	}
	$_SESSION['PO' . $identifier]->PaymentTerms = $_POST['PaymentTerms'];
	$_SESSION['PO' . $identifier]->Contact = $_POST['Contact'];
	$_SESSION['PO' . $identifier]->Tel = $_POST['Tel'];
	$_SESSION['PO' . $identifier]->Port = $_POST['Port'];

	if (isset($_POST['RePrint']) and $_POST['RePrint'] == 1) {
		$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
		DB_query("UPDATE purchorders SET allowprint='1' WHERE orderno='" . $_SESSION['PO' . $identifier]->OrderNo . "'");
	}

	if (!isset($_POST['AllowRePrint'])) {
		echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PO_Items.php?identifier=' . $identifier . '">';
		echo '<div class="aw-card"><div class="aw-card-body">';
		prnMsg(__('Forwarding to line item entry...'), 'info');
		echo '</div></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
}

// Page Header
echo '<div class="aw-page-header">
		<div class="aw-page-title-group">
			<div class="aw-breadcrumb">
				<span>' . __('Purchasing') . '</span>
				<span>/</span>
				<span>' . __('Orders') . '</span>
			</div>
			<h1>' . $Title . '</h1>
		</div>
		<div class="aw-page-actions">
			<a href="' . $RootPath . '/PO_SelectOSPurchOrder.php?identifier=' . $identifier . '" class="aw-btn aw-btn-secondary">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
				' . __('Back to Orders') . '
			</a>
		</div>
	</div>';

if (isset($_GET['ModifyOrderNumber'])) {
	include(__DIR__ . '/includes/PO_ReadInOrder.php');
}

if (!isset($_SESSION['PO' . $identifier])) {
	$_SESSION['ExistingOrder'] = 0;
	$_SESSION['PO' . $identifier] = new PurchOrder;
	$_SESSION['PO' . $identifier]->AllowPrintPO = 1;
	$_SESSION['PO' . $identifier]->GLLink = $_SESSION['CompanyRecord']['gllink_stock'];
	$_SESSION['RequireSupplierSelection'] = (empty($_SESSION['PO' . $identifier]->SupplierID)) ? 1 : 0;
}

if (isset($_POST['ChangeSupplier'])) {
	if ($_SESSION['PO' . $identifier]->Status == 'Pending' and $_SESSION['UserID'] == $_SESSION['PO' . $identifier]->Initiator) {
		if ($_SESSION['PO' . $identifier]->Any_Already_Received() == 0) {
			$_SESSION['RequireSupplierSelection'] = 1;
		} else {
			prnMsg(__('Cannot modify supplier once receipts exist'), 'warn');
		}
	}
}

if (isset($_POST['SearchSuppliers'])) {
	$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords'] ?? '') . '%';
	$SuppCode = $_POST['SuppCode'] ?? '';
	
	if (mb_strlen($_POST['Keywords']) > 0) {
		$SQL = "SELECT supplierid, suppname, address1, address2, address3, address4, address5, address6, currcode FROM suppliers WHERE suppname " . LIKE . " '" . $SearchString . "' ORDER BY suppname";
	} elseif (mb_strlen($SuppCode) > 0) {
		$SQL = "SELECT supplierid, suppname, address1, address2, address3, address4, address5, address6, currcode FROM suppliers WHERE supplierid " . LIKE . " '%" . $SuppCode . "%' ORDER BY supplierid";
	} else {
		$SQL = "SELECT supplierid, suppname, address1, address2, address3, address4, address5, address6, currcode FROM suppliers ORDER BY supplierid LIMIT 50";
	}

	$Result_SuppSelect = DB_query($SQL);
	if (DB_num_rows($Result_SuppSelect) == 1) {
		$MyRow = DB_fetch_array($Result_SuppSelect);
		$_POST['Select'] = $MyRow['supplierid'];
	}
}

if ((!isset($_POST['SearchSuppliers']) or $_POST['SearchSuppliers'] == '') and (isset($_SESSION['PO' . $identifier]->SupplierID) and $_SESSION['PO' . $identifier]->SupplierID != '')) {
	$_POST['SupplierID'] = $_SESSION['PO' . $identifier]->SupplierID;
	$_POST['SupplierName'] = $_SESSION['PO' . $identifier]->SupplierName;
	$_POST['CurrCode'] = $_SESSION['PO' . $identifier]->CurrCode;
	$_POST['ExRate'] = $_SESSION['PO' . $identifier]->ExRate;
	$_POST['PaymentTerms'] = $_SESSION['PO' . $identifier]->PaymentTerms;
	for($i=1; $i<=6; $i++) {
		$_POST['DelAdd'.$i] = $_SESSION['PO' . $identifier]->{'DelAdd'.$i};
		$_POST['SuppDelAdd'.$i] = $_SESSION['PO' . $identifier]->{'SuppDelAdd'.$i};
	}
	if (!isset($_POST['DeliveryDate'])) {
		$_POST['DeliveryDate'] = $_SESSION['PO' . $identifier]->DeliveryDate;
	}
}

if (isset($_POST['Select'])) {
	$SQL = "SELECT suppliers.suppname, suppliers.currcode, currencies.rate, currencies.decimalplaces, suppliers.paymentterms, suppliers.address1, suppliers.address2, suppliers.address3, suppliers.address4, suppliers.address5, suppliers.address6, suppliers.telephone, suppliers.port, suppliers.defaultshipper FROM suppliers INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE supplierid='" . $_POST['Select'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	
	$AuthSql = "SELECT cancreate FROM purchorderauth WHERE userid='" . $_SESSION['UserID'] . "' AND currabrev='" . $MyRow['currcode'] . "'";
	$AuthResult = DB_query($AuthSql);
	$AuthRow = DB_fetch_array($AuthResult);

	if ($AuthRow['cancreate'] != 0) {
		$_POST['SupplierName'] = $MyRow['suppname'];
		$_POST['CurrCode'] = $MyRow['currcode'];
		$_POST['CurrDecimalPlaces'] = $MyRow['decimalplaces'];
		$_POST['ExRate'] = $MyRow['rate'];
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['SuppTel'] = $MyRow['telephone'];
		$_POST['Port'] = $MyRow['port'];
		$_POST['DeliveryBy'] = $MyRow['defaultshipper'];

		$_SESSION['PO' . $identifier]->SupplierID = $_POST['Select'];
		$_SESSION['RequireSupplierSelection'] = 0;
		$_SESSION['PO' . $identifier]->SupplierName = $_POST['SupplierName'];
		$_SESSION['PO' . $identifier]->CurrCode = $_POST['CurrCode'];
		$_SESSION['PO' . $identifier]->CurrDecimalPlaces = $_POST['CurrDecimalPlaces'];
		$_SESSION['PO' . $identifier]->ExRate = $_POST['ExRate'];
		$_SESSION['PO' . $identifier]->PaymentTerms = $_POST['PaymentTerms'];
		for($i=1; $i<=6; $i++) {
			$_POST['SuppDelAdd'.$i] = $MyRow['address'.$i];
			$_SESSION['PO' . $identifier]->{'SuppDelAdd'.$i} = $MyRow['address'.$i];
		}
		$_SESSION['PO' . $identifier]->SuppTel = $_POST['SuppTel'];
		$_SESSION['PO' . $identifier]->Port = $_POST['Port'];
		$_SESSION['PO' . $identifier]->DeliveryBy = $_POST['DeliveryBy'];
	} else {
		prnMsg(__('Unauthorized for this currency/supplier'), 'warn');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
} elseif (isset($_SESSION['PO' . $identifier]->SupplierID) && $_SESSION['PO' . $identifier]->SupplierID != '') {
	$_POST['Select'] = $_SESSION['PO' . $identifier]->SupplierID;
}

// Supplier Selection View
if ($_SESSION['RequireSupplierSelection'] == 1 or empty($_SESSION['PO' . $identifier]->SupplierID)) {
    echo '<div class="aw-grid-main aw-grid-selection">';
    
    // Left: Search Form
    echo '<div>
            <div class="aw-card">
                <div class="aw-card-header">
                    <h2 class="aw-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        ' . __('Find Supplier') . '
                    </h2>
                </div>
                <div class="aw-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="aw-form-group">
                            <label class="aw-label">' . __('Search Name') . '</label>
                            <input type="text" name="Keywords" class="aw-input" placeholder="' . __('e.g. Acme Corp') . '" autofocus />
                        </div>
                        <div class="aw-form-group">
                            <label class="aw-label">' . __('Search Code') . '</label>
                            <input type="text" name="SuppCode" class="aw-input" placeholder="' . __('e.g. S001') . '" />
                        </div>
                        <div style="margin-top: var(--space-4); display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button type="submit" name="SearchSuppliers" class="aw-btn aw-btn-primary">' . __('Search') . '</button>
                            <button type="submit" class="aw-btn aw-btn-secondary">' . __('Reset') . '</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';

    // Right: Results Table
    echo '<div>';
    if (isset($Result_SuppSelect)) {
        echo '<div class="aw-card">
                <div class="aw-card-header">
                    <h3 class="aw-card-title">' . __('Matching Suppliers') . '</h3>
                </div>
                <div class="aw-table-wrapper">
                    <table class="aw-table">
                        <thead>
                            <tr>
                                <th>' . __('Select') . '</th>
                                <th>' . __('Supplier Details') . '</th>
                                <th>' . __('Currency') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
        while ($MyRow = DB_fetch_array($Result_SuppSelect)) {
            echo '<tr>
                    <td style="width: 80px;">
                        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
                            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                            <input type="hidden" name="Select" value="' . $MyRow['supplierid'] . '" />
                            <button type="submit" class="aw-btn aw-btn-secondary aw-btn-sm">' . __('Select') . '</button>
                        </form>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--primary-dark);">' . $MyRow['suppname'] . '</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">' . $MyRow['address1'] . ', ' . $MyRow['address2'] . '</div>
                    </td>
                    <td><span class="aw-badge aw-badge-info">' . $MyRow['currcode'] . '</span></td>
                </tr>';
        }
        echo '</tbody></table></div></div>';
    } else {
        echo '<div class="aw-card" style="border: 2px dashed var(--border-color); background: transparent; shadow: none;">
                <div class="aw-card-body" style="text-align: center; color: var(--text-muted); padding: 40px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 15px; opacity: 0.5;"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                    <div>' . __('Search for a supplier to begin') . '</div>
                </div>
              </div>';
    }
    echo '</div>'; // End Results Column
    echo '</div>'; // End Grid
} 
else {
	// Order Header View
	echo '<form id="form1" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?identifier=' . urlencode($identifier) . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($Purch_Item)) {
		echo '<div class="aw-card" style="border-left: 4px solid var(--primary);">
				<div class="aw-card-body" style="display: flex; justify-content: space-between; align-items: center;">
					<div>
						<div style="font-size: 0.8rem; color: var(--text-muted);">' . __('Adding Item') . '</div>
						<div style="font-weight: 600; font-size: 1.1rem;">' . $Purch_Item . '</div>
					</div>
					<a href="' . $RootPath . '/PO_Items.php?NewItem=' . $Purch_Item . '&identifier=' . $identifier . '" class="aw-btn aw-btn-primary">' . __('Confirm and Enter Line Item') . '</a>
				</div>
			</div>';
	}

	if (!isset($_POST['LookupDeliveryAddress']) and (!isset($_POST['StkLocation']) or $_POST['StkLocation']) and (isset($_SESSION['PO' . $identifier]->Location) and $_SESSION['PO' . $identifier]->Location != '')) {
		 if (!isset($_SESSION['PO' . $identifier]->Initiator)) { $_SESSION['PO' . $identifier]->Initiator = $_SESSION['UserID']; }
		$_POST['StkLocation'] = $_SESSION['PO' . $identifier]->Location;
		$_POST['SupplierContact'] = $_SESSION['PO' . $identifier]->SupplierContact;
		for($i=1; $i<=6; $i++) { $_POST['DelAdd'.$i] = $_SESSION['PO' . $identifier]->{'DelAdd'.$i}; }
		$_POST['Initiator'] = $_SESSION['PO' . $identifier]->Initiator;
		$_POST['Requisition'] = $_SESSION['PO' . $identifier]->RequisitionNo;
		$_POST['Version'] = $_SESSION['PO' . $identifier]->Version;
		$_POST['DeliveryDate'] = $_SESSION['PO' . $identifier]->DeliveryDate;
		$_POST['Revised'] = $_SESSION['PO' . $identifier]->Revised;
		$_POST['ExRate'] = $_SESSION['PO' . $identifier]->ExRate;
		$_POST['Comments'] = $_SESSION['PO' . $identifier]->Comments;
		$_POST['DeliveryBy'] = $_SESSION['PO' . $identifier]->DeliveryBy;
		$_POST['PaymentTerms'] = $_SESSION['PO' . $identifier]->PaymentTerms;
		$Res = DB_query("SELECT realname FROM www_users WHERE userid='" . $_POST['Initiator'] . "'");
		$Row = DB_fetch_array($Res);
		$_POST['InitiatorName'] = $Row['realname'];
	}

	echo '<div class="aw-grid-main">';
	
	// Main Content Area
	echo '<div class="aw-content-body">';

	echo '<div class="aw-card">
			<div class="aw-card-body">';

	// Section 1: Core Identification
	echo '<div class="aw-section">
			<div class="aw-section-header">
				<div class="aw-section-icon">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
				</div>
				<h3 class="aw-section-title">' . __('Basic Configuration') . '</h3>
			</div>
			<div class="aw-form-row">
				<div class="aw-form-group">
					<label class="aw-label">' . __('PO Date') . '</label>
					<div class="aw-input aw-input-readonly">' . ($_SESSION['ExistingOrder'] != 0 ? ConvertSQLDate($_SESSION['PO' . $identifier]->Orig_OrderDate) : date($_SESSION['DefaultDateFormat'])) . '</div>
				</div>
				<div class="aw-form-group">
					<label class="aw-label">' . __('Target Delivery Date') . '</label>
					<input required type="date" name="DeliveryDate" class="aw-input" value="' . FormatDateForSQL($_POST['DeliveryDate']) . '" />
				</div>
				<div class="aw-form-group">
					<label class="aw-label">' . __('Requisition Ref') . '</label>
					<input type="text" name="Requisition" class="aw-input" maxlength="15" value="' . $_POST['Requisition'] . '" />
				</div>
			</div>
			<div class="aw-form-row" style="margin-top: 10px;">
				<div class="aw-form-group">
					<label class="aw-label">' . __('Initiated By') . '</label>
					<input type="hidden" name="Initiator" value="' . $_POST['Initiator'] . '" />
					<div class="aw-input aw-input-readonly">' . $_POST['InitiatorName'] . '</div>
				</div>
				<div class="aw-form-group">
					<label class="aw-label">' . __('Version') . '</label>
					<div class="aw-input aw-input-readonly">v' . ($_POST['Version'] ?? 1) . '</div>
					<input type="hidden" name="Version" value="' . ($_POST['Version'] ?? 1) . '" />
				</div>
				<input type="hidden" name="Revised" value="' . date($_SESSION['DefaultDateFormat']) . '" />
			</div>
		</div>';

	// Section 2: Vendor Details
	echo '<div class="aw-section">
			<div class="aw-section-header">
				<div class="aw-section-icon">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
				</div>
				<h3 class="aw-section-title">' . __('Vendor & Contact') . '</h3>
			</div>
			<div class="aw-form-row">
				<div class="aw-form-group">
					<label class="aw-label">' . __('Vendor Contact Person') . '</label>
					<select name="SupplierContact" class="aw-select">';
					$SuppCoResult = DB_query("SELECT contact FROM suppliercontacts WHERE supplierid='" . $_POST['Select'] . "'");
					while ($SuppCoRow = DB_fetch_array($SuppCoResult)) {
						$selected = ($_POST['SupplierContact'] == $SuppCoRow['contact']) ? 'selected' : '';
						echo '<option ' . $selected . ' value="' . $SuppCoRow['contact'] . '">' . $SuppCoRow['contact'] . '</option>';
					}
					echo '</select>
				</div>
				<div class="aw-form-group">
					<label class="aw-label">' . __('Payment Terms') . '</label>
					<select name="PaymentTerms" class="aw-select">';
					$Res = DB_query("SELECT terms, termsindicator FROM paymentterms");
					while ($MyRow = DB_fetch_array($Res)) {
						$selected = ($MyRow['termsindicator'] == $_SESSION['PO' . $identifier]->PaymentTerms) ? 'selected' : '';
						echo '<option ' . $selected . ' value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
					}
					echo '</select>
				</div>
			</div>
			<div class="aw-form-group" style="margin-top: 10px;">
				<label class="aw-label">' . __('Vendor Registered Address lines') . '</label>
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px;">';
					for ($i = 1; $i <= 4; $i++) {
						echo '<input type="text" name="SuppDelAdd' . $i . '" class="aw-input" value="' . $_POST['SuppDelAdd' . $i] . '" />';
					}
				echo '</div>
			</div>
		</div>';

	// Section 3: Logistics
	echo '<div class="aw-section">
			<div class="aw-section-header">
				<div class="aw-section-icon">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
				</div>
				<h3 class="aw-section-title">' . __('Fulfillment & Logistics') . '</h3>
			</div>
			<div class="aw-form-group">
				<label class="aw-label">' . __('Receiving Warehouse') . '</label>
				<div class="aw-input-group">
					<select name="StkLocation" class="aw-select" onchange="ReloadForm(form1.LookupDeliveryAddress)">';
					$LocnResult = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1");
					while ($LocnRow = DB_fetch_array($LocnResult)) {
						$selected = (isset($_POST['StkLocation']) && $_POST['StkLocation'] == $LocnRow['loccode']) || (empty($_POST['StkLocation']) && $LocnRow['loccode'] == $_SESSION['UserStockLocation']) ? 'selected' : '';
						echo '<option ' . $selected . ' value="' . $LocnRow['loccode'] . '">' . $LocnRow['locationname'] . '</option>';
					}
					echo '</select>
					<button type="submit" name="LookupDeliveryAddress" class="aw-btn aw-btn-secondary aw-btn-sm">' . __('Reload Addr') . '</button>
				</div>
			</div>
			<div class="aw-grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 10px;">
				<div class="aw-form-group">
					<label class="aw-label">' . __('Delivery Contact') . '</label>
					<input type="text" name="Contact" class="aw-input" value="' . $_SESSION['PO' . $identifier]->Contact . '" />
				</div>
				<div class="aw-form-group">
					<label class="aw-label">' . __('Delivery Port') . '</label>
					<input type="text" name="Port" class="aw-input" value="' . $_POST['Port'] . '" />
				</div>
			</div>
			<div class="aw-form-group">
				<label class="aw-label">' . __('Full Delivery Address') . '</label>
				<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">';
					for ($i = 1; $i <= 6; $i++) {
						echo '<input type="text" name="DelAdd' . $i . '" class="aw-input" value="' . $_POST['DelAdd' . $i] . '" placeholder="' . __('Line') . ' ' . $i . '" />';
					}
				echo '</div>
			</div>
		</div>';

	echo '  </div> <!-- End Inner Card Body -->
			<div class="aw-card-footer">
				<button type="submit" name="EnterLines" class="aw-btn aw-btn-primary aw-btn-lg">
					' . __('Process to Line Items') . '
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
				</button>
			</div>
		</div> <!-- End Main Card -->';

	echo '</div> <!-- End Main Content Body Area -->';

	// Sidebar Content
	echo '<div class="aw-sticky-side">';

	// Card: Status & Action
	echo '<div class="aw-card">
			<div class="aw-card-header">
				<h2 class="aw-card-title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
					' . __('Workflow') . '
				</h2>
			</div>
			<div class="aw-card-body">';
			if ($_SESSION['PO' . $identifier]->Status == '') {
				echo '<div class="aw-badge aw-badge-success" style="width: 100%; justify-content: center; padding: 8px; margin-bottom: 12px;">' . __('Draft Order') . '</div>
					  <input type="hidden" name="Status" value="NewOrder" />';
			} else {
				echo '<div class="aw-form-group">
						<label class="aw-label">' . __('Current Status') . '</label>
						<select name="Status" class="aw-select" onchange="ReloadForm(form1.UpdateStatus)">';
						foreach (['Pending', 'Authorised', 'Rejected', 'Cancelled', 'Printed', 'Completed'] as $st) {
							if ($_SESSION['PO' . $identifier]->Status == $st) {
								echo '<option selected value="'.$st.'">'.__($st).'</option>';
							} else {
								echo '<option value="'.$st.'">'.__($st).'</option>';
							}
						}
						echo '</select>
					</div>
					<div class="aw-form-group">
						<label class="aw-label">' . __('Activity Log Note') . '</label>
						<input type="text" name="StatusComments" class="aw-input" placeholder="' . __('Internal note...') . '" />
					</div>
					<button type="submit" name="UpdateStatus" value="1" class="aw-btn aw-btn-secondary aw-btn-sm aw-btn-full" style="margin-bottom: 12px;">' . __('Log Activity') . '</button>
					<label class="aw-label">' . __('History') . '</label>
					<div class="aw-history-box">' . html_entity_decode($_SESSION['PO' . $identifier]->StatusComments, ENT_QUOTES, 'UTF-8') . '</div>
					<input type="hidden" name="StatusCommentsComplete" value="' . htmlspecialchars($_SESSION['PO' . $identifier]->StatusComments, ENT_QUOTES, 'UTF-8') . '" />';
			}
	echo '</div></div>';

	// Card: Global Comments
	echo '<div class="aw-card">
			<div class="aw-card-header">
				<h2 class="aw-card-title">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
					' . __('Remarks') . '
				</h2>
			</div>
			<div class="aw-card-body">
				<textarea name="Comments" class="aw-textarea" style="min-height: 80px;" placeholder="' . __('General order remarks...') . '">' . stripcslashes($_POST['Comments']) . '</textarea>
			</div>
		</div>';

	// Card: Financial Meta
	if ($_SESSION['PO' . $identifier]->CurrCode != $_SESSION['CompanyRecord']['currencydefault'] || !isset($_POST['ExRate'])) {
		echo '<div class="aw-card">
				<div class="aw-card-header">
					<h2 class="aw-card-title">' . __('Finance') . '</h2>
				</div>
				<div class="aw-card-body">
					<div class="aw-form-group">
						<label class="aw-label">' . __('Ex Rate (' . $_SESSION['PO' . $identifier]->CurrCode . ')') . '</label>
						<input type="text" name="ExRate" class="aw-input" value="' . locale_number_format($_POST['ExRate'] ?? 1, 4) . '" />
					</div>
				</div>
			</div>';
	} else {
		echo '<input type="hidden" name="ExRate" value="1" />';
	}

	echo '</div>'; // End Sidebar
	echo '</div>'; // End Main Grid

	echo '</form>';
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
