<?php

// SuppPriceList.php - Modernized Supplier Price List Dashboard

require(__DIR__ . '/includes/session.php');
use Dompdf\Dompdf;
include(__DIR__ . '/includes/SetDomPDFOptions.php');

$Title = __('Supplier Price List');
$ViewTopic = 'AccountsPayable';
$BookMark = '';

if (isset($_GET['SelectedSupplier'])) {
	$_POST['supplierid']=$_GET['SelectedSupplier'];
}

// Logic for Processing Results
if (isset($_POST['PrintPDF']) or isset($_POST['View']) or isset($_POST['Email'])) {

	// Get Supplier Details
	$SQLsup = "SELECT suppname, currcode, decimalplaces AS currdecimalplaces
				FROM suppliers INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_POST['supplier'] . "'";
	$Resultsup = DB_query($SQLsup);
	$RowSup = DB_fetch_array($Resultsup);
	$SupplierName = $RowSup['suppname'];
	$CurrCode = $RowSup['currcode'];
	$CurrDecimalPlaces = $RowSup['currdecimalplaces'];

	// Get Category Details
	if ($_POST['category']!='all'){
		$SQLcat="SELECT categorydescription FROM stockcategory WHERE categoryid ='" . $_POST['category'] . "'";
		$Resultcat = DB_query($SQLcat);
		$RowCat = DB_fetch_row($Resultcat);
		$Categoryname = $RowCat['0'];
	} else {
		$Categoryname = 'ALL';
	}

	$CurrentOrAllPrices = ($_POST['price']=='all') ? __('All Prices') : __('Current Price');

	// Build Query
	if (($_POST['price']=='all') AND ($_POST['category']=='all')){
		$SQL = "SELECT purchdata.stockid, stockmaster.description, purchdata.price, purchdata.conversionfactor, (purchdata.effectivefrom) as dateprice, purchdata.supplierdescription, purchdata.suppliers_partno
				FROM purchdata,stockmaster
				WHERE supplierno='" . $_POST['supplier'] . "' AND stockmaster.stockid=purchdata.stockid
				ORDER BY stockid ASC ,dateprice DESC";
	} else {
		if (($_POST['price']!='all') AND ($_POST['category']=='all')){
			$SQL = "SELECT purchdata.stockid, stockmaster.description, (SELECT purchdata.price FROM purchdata WHERE purchdata.stockid = stockmaster.stockid ORDER BY effectivefrom DESC LIMIT 1) AS price, purchdata.conversionfactor, (SELECT purchdata.effectivefrom FROM purchdata WHERE purchdata.stockid = stockmaster.stockid ORDER BY effectivefrom DESC LIMIT 1) AS dateprice, purchdata.supplierdescription, purchdata.suppliers_partno
					FROM purchdata, stockmaster
					WHERE supplierno = '" . $_POST['supplier'] . "' AND stockmaster.stockid = purchdata.stockid
					GROUP BY stockid ORDER BY stockid ASC , dateprice DESC";
		} else {
			if (($_POST['price']=='all')and($_POST['category']!='all')){
				$SQL = "SELECT purchdata.stockid, stockmaster.description, purchdata.price, purchdata.conversionfactor, (purchdata.effectivefrom) as dateprice, purchdata.supplierdescription, purchdata.suppliers_partno
						FROM purchdata,stockmaster
						WHERE supplierno='" . $_POST['supplier'] . "' AND stockmaster.stockid=purchdata.stockid AND stockmaster.categoryid='" . $_POST['category'] . "'
						ORDER BY stockid ASC ,dateprice DESC";
			} else {
				$SQL = "SELECT purchdata.stockid, stockmaster.description, (SELECT purchdata.price FROM purchdata WHERE purchdata.stockid = stockmaster.stockid ORDER BY effectivefrom DESC LIMIT 1) AS price, purchdata.conversionfactor, (SELECT purchdata.effectivefrom FROM purchdata WHERE purchdata.stockid = stockmaster.stockid ORDER BY effectivefrom DESC LIMIT 1) AS dateprice, purchdata.supplierdescription, purchdata.suppliers_partno
						FROM purchdata,stockmaster
						WHERE supplierno='" . $_POST['supplier'] . "' AND stockmaster.stockid=purchdata.stockid AND stockmaster.categoryid='" . $_POST['category'] . "'
						GROUP BY stockid ORDER BY stockid ASC ,dateprice DESC";
			}
		}
	}
	$PricesResult = DB_query($SQL, __('The Price List could not be retrieved'));

	if (DB_num_rows($PricesResult)==0) {
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page"><div class="db-page-header"><h1 class="db-page-title">' . $Title . '</h1></div>';
		prnMsg(__('There are no results found for the selected criteria.'), 'info');
		echo '<div class="db-form-actions" style="margin-top: 20px;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-secondary">' . __('Back to Search') . '</a></div></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$HTML = '';
	if (isset($_POST['PrintPDF']) or isset($_POST['Email'])) {
		$HTML .= '<html><head><link href="css/reports.css" rel="stylesheet" type="text/css" />';
	} else {
		// Embed local styles for HTML view
		$HTML .= '<style>
					.db-info-item { background: var(--surface-alt); padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-soft); }
					.db-info-label { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
					.db-info-value { font-size: 0.9375rem; font-weight: 700; color: var(--text-main); }
				  </style>';
	}

	$HTML .= '<meta name="author" content="WebERP"><meta name="Creator" content="webERP"></head><body>';
	
	// Data Headers Card
	$HTML .= '<div class="card-v2" style="margin-bottom: var(--space-6);">
				<div class="card-body-v2" style="padding: var(--space-5);">
					<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-4);">
						<div class="db-info-item"><div class="db-info-label">' . __('Registry Type') . '</div><div class="db-info-value">' . $CurrentOrAllPrices . '</div></div>
						<div class="db-info-item"><div class="db-info-label">' . __('Target Supplier') . '</div><div class="db-info-value">' . $_POST['supplier'] . ' - ' . $SupplierName . '</div></div>
						<div class="db-info-item"><div class="db-info-label">' . __('Category Scope') . '</div><div class="db-info-value">' . $Categoryname . '</div></div>
						<div class="db-info-item"><div class="db-info-label">' . __('Valuation Currency') . '</div><div class="db-info-value">' . $CurrCode . '</div></div>
					</div>
				</div>
			  </div>';

	// Data Table Card
	$HTML .= '<div class="card-v2">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Item Code') . '</th>
								<th>' . __('Description') . '</th>
								<th class="text-right">' . __('Conv Factor') . '</th>
								<th class="text-right">' . __('Registry Price') . '</th>
								<th>' . __('Effective From') . '</th>
								<th>' . __('Supp Code') . '</th>
							</tr>
						</thead>
						<tbody>';

	while ($MyRow = DB_fetch_array($PricesResult)) {
		$HTML .= '<tr>
					<td style="font-weight: 800; color: var(--primary);">' . $MyRow['stockid'] . '</td>
					<td style="font-weight: 600;">' . $MyRow['description'] . '</td>
					<td class="text-right">' . $MyRow['conversionfactor'] . '</td>
					<td class="text-right" style="font-weight: 800; color: var(--text-main);">' . locale_number_format($MyRow['price'], $CurrDecimalPlaces) . '</td>
					<td><span class="db-badge" style="background: var(--surface-alt);">' . ConvertSQLDate($MyRow['dateprice']) . '</span></td>
					<td style="color: var(--text-muted); font-size: 0.8rem;">' . $MyRow['suppliers_partno'] . '</td>
				</tr>';
	}
	$HTML .= '</tbody></table></div></div>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
		$DomPDF->render();
		$DomPDF->stream($_SESSION['DatabaseName'] . '_SuppPriceList.pdf', ["Attachment" => false]);
	} elseif (isset($_POST['Email'])) {
		$DomPDF = new Dompdf($DomPDFOptions);
		$DomPDF->loadHtml($HTML);
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
		$DomPDF->render();
		$output = $DomPDF->output();
		$PDFFileName = sys_get_temp_dir() . '/' . $_SESSION['DatabaseName'] . '_SuppPriceList.pdf';
		file_put_contents($PDFFileName, $output);

		if ($_SESSION['InventoryManagerEmail']!='') {
			SendEmailFromWebERP($_SESSION['CompanyRecord']['email'], [$_SESSION['InventoryManagerEmail'] => ''], $_SESSION['DatabaseName'] . '_SuppPriceList', __('Attached is the requested Price List.'), [$PDFFileName]);
		}
		unlink($PDFFileName);
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page"><div class="db-page-header"><h1 class="db-page-title">' . __('Email Sent') . '</h1></div><div class="card-v2"><div class="card-body-v2">'.prnMsg(__('The report has been successfully sent.'),'success').'</div></div></div>';
		include(__DIR__ . '/includes/footer.php');
	} else {
		// View in Browser
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page">
				<div class="db-page-header" style="margin-bottom: var(--space-6);">
					<div class="db-header-row">
						<div class="db-header-main">
							<h1 class="db-page-title">' . __('Supplier Price List Registry') . '</h1>
							<p class="db-page-subtitle">' . __('Real-time valuation and item registry for') . ' <strong>' . $SupplierName . '</strong></p>
						</div>
						<div class="db-header-actions">
							<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-secondary">
								<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
								' . __('New Selection') . '
							</a>
						</div>
					</div>
				</div>';
		echo $HTML;
		echo '</div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else { /* Display Selection Form */

	include(__DIR__ . '/includes/header.php');
	echo '<div class="db-page">
			<div class="db-page-header" style="margin-bottom: var(--space-6);">
				<div class="db-header-row">
					<div class="db-header-main">
						<h1 class="db-page-title">' . __('Supplier Price List Designer') . '</h1>
						<p class="db-page-subtitle">' . __('Configure report parameters and registry output formats') . '</p>
					</div>
				</div>
			</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="card-v2">
			<div class="card-header-v2" style="padding: 16px 24px; border-bottom: 1px solid var(--border-soft); font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
				<div style="width: 24px; height: 24px; border-radius: 6px; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 20V10M18 20V4M6 20v-4"/></svg>
				</div>
				' . __('Criteria Selection') . '
			</div>
			<div class="card-body-v2" style="padding: var(--space-6);">
				<div class="db-grid db-grid-3" style="gap: var(--space-6);">';
	
	// Supplier Selection
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Target Supplier') . '</label>
			<select name="supplier" class="db-form-select">';
	$SQL = "SELECT supplierid, suppname FROM suppliers ORDER BY suppname";
	$Result = DB_query($SQL);
	while ($MyRow=DB_fetch_array($Result)){
		$Selected = (isset($_POST['supplierid']) && $MyRow['supplierid'] == $_POST['supplierid']) ? 'selected="selected"' : '';
		echo '<option ' . $Selected . ' value="' . $MyRow['supplierid'] . '">' . $MyRow['supplierid'].' - '.$MyRow['suppname'] . '</option>';
	}
	echo '	</select>
		  </div>';

	// Category Selection
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Stock Category') . '</label>
			<select name="category" class="db-form-select">
				<option value="all">' . __('ALL CATEGORIES') . '</option>';
	$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$Result = DB_query($SQL);
	while ($MyRow=DB_fetch_array($Result)){
		echo '<option value="' . $MyRow['categoryid'] . '">' .$MyRow['categoryid'].' - '. $MyRow['categorydescription'] . '</option>';
	}
	echo '	</select>
		  </div>';

	// Price Type
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Historical Scope') . '</label>
			<select name="price" class="db-form-select">
				<option value="all">' .__('Full Price History') . '</option>
				<option value="current">' .__('Current Active Price Only') . '</option>
			</select>
		  </div>
		</div>
	</div>
	<div class="card-footer-v2" style="padding: var(--space-5) var(--space-6); background: var(--surface-alt); border-top: 1px solid var(--border-soft);">
		<div class="db-form-actions" style="justify-content: flex-end; gap: var(--space-3);">
			 <button type="submit" name="Email" class="db-btn db-btn-outline">
			 	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7M16 8l-4-4-4 4M12 4v12"/></svg>
				' . __('Email Report') . '
			 </button>
			 <button type="submit" name="PrintPDF" class="db-btn db-btn-secondary">
			 	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
				' . __('Print PDF') . '
			 </button>
			 <button type="submit" name="View" class="db-btn db-btn-primary">
			 	<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-right: 8px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
				' . __('View Results') . '
			 </button>
		</div>
	</div>
</div>';

	echo '</form></div>';
	include(__DIR__ . '/includes/footer.php');
}
