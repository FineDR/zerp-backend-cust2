<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Purchase Order Financial Planning');
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
	.aw-page-header { margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 320px 1fr; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 12px; font-size: 0.82rem; outline: none; transition: 0.2s; background: white; }
	.aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 10px 20px; border-radius: 8px; font-weight: 750; font-size: 0.85rem; cursor: pointer; border: none; gap: 8px; transition: 0.2s; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

	.aw-supplier-row { background: var(--primary-soft) !important; border-top: 2px solid var(--primary); }
	.aw-supplier-name { font-weight: 900; color: var(--primary-dark); font-size: 0.9rem; }
	.aw-sub-row { background: #fff !important; }
	.aw-total-row { background: #f8fafc !important; font-weight: 800; border-top: 1px solid var(--border-color); }
</style>
<div class="aw-container">
	<div class="aw-page-header">
		<div class="aw-breadcrumb">Purchasing / Financial Planning</div>
		<h1 class="aw-page-title">' . $Title . '</h1>
	</div>';

if (isset($_POST['submit'])) {
    submit($_POST['Country'], $_POST['Currency'], $RootPath, $Title);
} else {
    display($Title);
}

function submit($Country, $Currency, $RootPath, $Title) {
    if ($Country != 'All'){ $WhereCountry = " AND suppliers.address6 = '". $Country ."' "; } else { $WhereCountry = ' '; }
	if ($Currency != 'All'){ $WhereCurrency = " AND suppliers.currcode = '". $Currency ."' "; } else { $WhereCurrency = ' '; }

	$SQL = "SELECT suppliers.supplierid, suppliers.suppname, suppliers.currcode, currencies.decimalplaces, currencies.rate, (SELECT SUM(supptrans.balance) FROM supptrans WHERE suppliers.supplierid = supptrans.supplierno) AS balance FROM suppliers INNER JOIN purchorders ON purchorders.supplierno = suppliers.supplierid INNER JOIN purchorderdetails ON purchorders.orderno = purchorderdetails.orderno INNER JOIN currencies ON suppliers.currcode=currencies.currabrev WHERE purchorderdetails.completed=0 AND purchorders.status IN ('Authorised', 'Printed', 'Pending')" . $WhereCountry . $WhereCurrency . " GROUP BY suppliers.supplierid ORDER BY suppliers.supplierid ASC";

	$ResultSuppliers = DB_query($SQL);
	if (DB_num_rows($ResultSuppliers) != 0){
		echo '<div class="aw-card">
				<div class="aw-card-header"><h3 class="aw-card-title">' . __('Active Purchase Order Summary') . '</h3></div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>' . __('Code/PO#') . '</th>
								<th>' . __('Supplier/Item') . '</th>
								<th>' . __('Order/Date') . '</th>
								<th>' . __('Delivery') . '</th>
								<th style="text-align:right;">' . __('Order Value') . '</th>
								<th style="text-align:right;">' . __('Value in') . ' ' . $_SESSION['CompanyRecord']['currencydefault'] . '</th>
								<th style="text-align:right;">' . __('Supplier Balance') . '</th>
								<th style="text-align:right;">' . __('Target Pending') . '</th>
							</tr>
						</thead>
						<tbody>';

		$TotalValueOrders = 0; $TotalValuePending = 0;
		while ($mySupplier = DB_fetch_array($ResultSuppliers)) {
			echo '<tr class="aw-supplier-row">
					<td class="aw-supplier-name">' . $mySupplier['supplierid'] . '</td>
					<td class="aw-supplier-name">' . $mySupplier['suppname'] . '</td>
					<td colspan="4"></td>
					<td style="text-align:right; font-weight:850; color:var(--primary-dark);">' . locale_number_format($mySupplier['balance'],$mySupplier['decimalplaces']) . ' ' . $mySupplier['currcode'] . '</td>
					<td></td>
				</tr>';

			$SQLSupplier = "SELECT purchorders.orderno, purchorders.orddate, purchorders.deliverydate, purchorders.status, SUM(purchorderdetails.unitprice*purchorderdetails.quantityord) AS ordervalue FROM purchorders INNER JOIN purchorderdetails ON purchorders.orderno = purchorderdetails.orderno WHERE purchorderdetails.completed=0 AND purchorders.status IN ('Authorised', 'Printed', 'Pending') AND purchorders.supplierno = '" . $mySupplier['supplierid'] . "' GROUP BY purchorders.orderno ORDER BY purchorders.orderno ASC";
			$SupplierResult = DB_query($SQLSupplier);

			$TotalSupplierOwnCurrency = 0; $TotalSupplierFunctionalCurrency = 0;
			while ($myPOs = DB_fetch_array($SupplierResult)) {
				$TotalSupplierOwnCurrency += $myPOs['ordervalue'];
				$OrderValueFuntionalCurrency = $myPOs['ordervalue'] / $mySupplier['rate'];
				$TotalSupplierFunctionalCurrency += $OrderValueFuntionalCurrency;
				echo '<tr class="aw-sub-row">
						<td style="padding-left:30px; font-weight:700; color:var(--primary);">#' . $myPOs['orderno'] . '</td>
						<td style="font-size:0.75rem; color:var(--text-muted);">' . __('Direct Purchase Order') . '</td>
						<td>' . ConvertSQLDate($myPOs['orddate']) . '</td>
						<td>' . ConvertSQLDate($myPOs['deliverydate']) . '</td>
						<td style="text-align:right;">' . locale_number_format($myPOs['ordervalue'],$mySupplier['decimalplaces']) . ' ' . $mySupplier['currcode'] . '</td>
						<td style="text-align:right; color:var(--text-muted);">' . locale_number_format($OrderValueFuntionalCurrency,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td></td>
						<td></td>
					</tr>';
			}
			$PendingOwn = $TotalSupplierOwnCurrency + $mySupplier['balance'];
			$PendingFunc = $PendingOwn / $mySupplier['rate'];
			$TotalValueOrders += $TotalSupplierFunctionalCurrency;
			$TotalValuePending += $PendingFunc;
			echo '<tr class="aw-total-row" style="border-bottom: 2px solid var(--border-color);">
					<td colspan="4" style="text-align:right;">' . __('Supplier Totals') . ':</td>
					<td style="text-align:right;">' . locale_number_format($TotalSupplierOwnCurrency,$mySupplier['decimalplaces']) . ' ' . $mySupplier['currcode'] . '</td>
					<td style="text-align:right;">' . locale_number_format($TotalSupplierFunctionalCurrency,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="text-align:right;">' . locale_number_format($mySupplier['balance'],$mySupplier['decimalplaces']) . '</td>
					<td style="text-align:right; font-weight:900; color:var(--primary);">' . locale_number_format($PendingFunc,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
				</tr>';
		}
		echo '</tbody>
				<tfoot style="background:var(--primary-dark); color:white;">
					<tr>
						<th colspan="5" style="text-align:right; padding:15px; color:white;">' . __('GRAND TOTAL ALL SUPPLIERS') . ' (' . $_SESSION['CompanyRecord']['currencydefault'] . ')</th>
						<th style="text-align:right; padding:15px; font-size:1.1rem; color:white;">' . locale_number_format($TotalValueOrders,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
						<th style="text-align:right; padding:15px; color:white;">' . locale_number_format($TotalValuePending - $TotalValueOrders, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
						<th style="text-align:right; padding:15px; font-size:1.1rem; color:white;">' . locale_number_format($TotalValuePending,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					</tr>
				</tfoot>
				</table>
			</div>
			<div class="aw-card-body" style="text-align:center; padding-top:20px;">
				<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="aw-btn aw-btn-secondary">' . __('Run Another Filter') . '</a>
			</div>
			</div>';
	} else {
		echo '<div class="aw-card"><div class="aw-card-body" style="text-align:center; padding:50px; color:var(--text-muted);">';
		prnMsg(__('No active purchase orders found matching your criteria.'), 'info');
		echo '<br/><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="aw-btn aw-btn-primary" style="margin-top:20px;">' . __('Back to Selection') . '</a>';
		echo '</div></div>';
	}
}

function display($Title) {
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="aw-card" style="max-width:500px; margin: 40px auto;">
			<div class="aw-card-header"><h2 class="aw-card-title">' . __('Filter Financial Planning') . '</h2></div>
			<div class="aw-card-body">
				<div class="aw-form-group" style="margin-bottom:16px;">
					<label class="aw-label">' . __('Supplier Country') . '</label>
					<select name="Country" class="aw-select">';
					$SQL = "SELECT DISTINCT(address6) AS country FROM suppliers ORDER BY address6";
					$CountryResult = DB_query($SQL);
					echo '<option value="All">' . __('All Countries') . '</option>';
					while ($MyRow=DB_fetch_array($CountryResult)){ echo '<option value="' . $MyRow['country'] . '">' . $MyRow['country'] . '</option>'; }
	echo '			</select>
				</div>
				<div class="aw-form-group" style="margin-bottom:20px;">
					<label class="aw-label">' . __('Transaction Currency') . '</label>
					<select name="Currency" class="aw-select">';
					$SQL = "SELECT currabrev, currency FROM currencies ORDER BY currency";
					$CurrencyResult = DB_query($SQL);
					echo '<option value="All">' . __('All Currencies') . '</option>';
					while ($MyRow=DB_fetch_array($CurrencyResult)){ echo '<option value="' . $MyRow['currabrev'] . '">' . $MyRow['currabrev'] . ' - ' . $MyRow['currency'] . '</option>'; }
	echo '			</select>
				</div>
				<div style="text-align:right;">
					<button type="submit" name="submit" class="aw-btn aw-btn-primary" style="width:100%;">' . __('Calculate Financial Status') . '</button>
				</div>
			</div>
		  </div>
		</form>';
}

echo '</div>'; // End aw-container
include(__DIR__ . '/includes/footer.php');
?>
