<?php

// Report of parts with quantity. Sorts by part and shows
// all locations where there are quantities of the part

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$WhereCategory = ' ';
	$CatDescription = ' ';
	if ($_POST['StockCat'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid='" . $_POST['StockCat'] . "'";
		$SQL= "SELECT categoryid,
					categorydescription
				FROM stockcategory
				WHERE categoryid='" . $_POST['StockCat'] . "' ";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		$CatDescription = $MyRow[1];
	}

	$HTML .= '<meta name="author" content="WebERP">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('Inventory Quantities Report') . '<br />
					' . __('Category') . ' ' . $_POST['StockCat'] . ' ' . $CatDescription . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
				</div>
				<div class="report-table-wrapper">
				<table>
					<thead>
						<tr>
							<th>' . __('Part Number') . '</th>
							<th>' . __('Description') . '</th>
							<th>' . __('Location') . '</th>
							<th>' . __('Quantity') . '</th>
							<th>' . __('Reorder Level') . '</th>
						</tr>
					</thead>
					<tbody>';

	if ($_POST['Selection'] == 'All') {
		$SQL = "SELECT locstock.stockid,
					stockmaster.description,
					locstock.loccode,
					locations.locationname,
					locstock.quantity,
					locstock.reorderlevel,
					stockmaster.decimalplaces,
					stockmaster.serialised,
					stockmaster.controlled
				FROM locstock INNER JOIN stockmaster
				ON locstock.stockid=stockmaster.stockid
				INNER JOIN locations
				ON locstock.loccode=locations.loccode
				WHERE locstock.quantity <> 0
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
				$WhereCategory . "
				ORDER BY locstock.stockid,
						locstock.loccode";
	} else {
		// sql to only select parts in more than one location
		// The SELECT statement at the beginning of the WHERE clause limits the selection to
		// parts with quantity in more than one location
		$SQL = "SELECT locstock.stockid,
					stockmaster.description,
					locstock.loccode,
					locations.locationname,
					locstock.quantity,
					locstock.reorderlevel,
					stockmaster.decimalplaces,
					stockmaster.serialised,
					stockmaster.controlled
				FROM locstock INNER JOIN stockmaster
				ON locstock.stockid=stockmaster.stockid
				INNER JOIN locations
				ON locstock.loccode=locations.loccode
				WHERE (SELECT count(*)
					  FROM locstock
					  WHERE stockmaster.stockid = locstock.stockid
					  AND locstock.quantity <> 0
					  GROUP BY locstock.stockid) > 1
				AND locstock.quantity <> 0
				AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') " .
				$WhereCategory . "
				ORDER BY locstock.stockid,
						locstock.loccode";
	}

	$ErrMsg = __('The Inventory Quantity report could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result)==0){
			$Title = __('Print Inventory Quantities Report');
			include(__DIR__ . '/includes/header.php');
			prnMsg(__('There were no items with inventory quantities'),'error');
			echo '<br /><a href="'.$RootPath.'/index.php">' . __('Back to the menu') . '</a>';
			include(__DIR__ . '/includes/footer.php');
			exit();
	}

	$HoldPart = " ";
	while ($MyRow = DB_fetch_array($Result)){

		if ($MyRow['stockid'] != $HoldPart) {
			$HoldPart = $MyRow['stockid'];
			$HTML .= '<tr class="total_row">
						<td colspan="5"> </td>
					</tr>';
		}

		$HTML .= '<tr class="striped_row">
					<td>' . $MyRow['stockid'] . '</td>
					<td>' . $MyRow['description'] . '</td>
					<td>' . $MyRow['locationname'] . ' (' . $MyRow['loccode'] . ')</td>
					<td class="number">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>
					<td class="number">' . locale_number_format($MyRow['reorderlevel'], $MyRow['decimalplaces']) . '</td>
				</tr>';

	} /*end while loop */


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table></div>';
	} else {
		$HTML .= '</tbody>
				</table></div>
				<div class="centre" style="margin-top: 20px;">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" style="padding: 10px 25px; border-radius: 8px; background: var(--primary); color: white; border: none; cursor: pointer;" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_InventoryQuantities_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Inventory Quantities');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . $Title . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */

	$Title=__('Inventory Quantities Reporting');
	$ViewTopic = 'Inventory';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');

	// Modern UI styles
	echo '<style>
		.modern-form-container {
			max-width: 800px;
			margin: 20px auto;
			padding: 25px;
			background: #fff;
			border-radius: 12px;
			box-shadow: 0 4px 20px rgba(0,0,0,0.08);
		}
		.page_help_text {
			background: var(--primary-soft);
			border-left: 4px solid var(--primary);
			padding: 15px;
			margin-bottom: 25px;
			border-radius: 4px;
			color: var(--text-main);
			font-size: 0.9rem;
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 20px;
			margin-bottom: 25px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group label {
			font-weight: 600;
			color: #374151;
			font-size: 0.9rem;
		}
		.form-group select {
			padding: 10px 12px;
			border: 1px solid #d1d5db;
			border-radius: 8px;
			font-size: 0.95rem;
			background-color: #f9fafb;
			width: 100%;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 12px;
			flex-wrap: wrap;
			margin-top: 20px;
			padding-top: 20px;
			border-top: 1px solid #e5e7eb;
		}
		.button-group input[type="submit"] {
			padding: 12px 28px;
			border-radius: 8px;
			font-weight: 600;
			cursor: pointer;
			transition: all 0.2s;
			border: none;
		}
		.button-group input[name="PrintPDF"] { background: var(--primary); color: white; }
		.button-group input[name="View"] { background: var(--primary); color: white; }
		.button-group input:hover { opacity: 0.9; transform: translateY(-1px); background: var(--primary-hover); }
		
		@media (max-width: 640px) {
			.modern-form-container { padding: 15px; margin: 10px; }
			.form-grid { grid-template-columns: 1fr; }
			.button-group input { width: 100%; }
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . __('Inventory') . '" alt="" />' . ' ' . __('Inventory Quantities Report') . '</p>';
	echo '<div class="page_help_text">' . __('Use this report to display the quantity of Inventory items in different categories.') . '</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<fieldset style="border:none; padding:0; margin:0;">
			<legend style="font-weight:bold; margin-bottom:15px; font-size:1.1rem;">', __('Report Criteria'), '</legend>
			<div class="form-grid">
				<div class="form-group">
					<label for="Selection">' . __('Selection') . ':</label>
					<select name="Selection" id="Selection">
						<option selected="selected" value="All">' . __('All') . '</option>
						<option value="Multiple">' . __('Only Parts With Multiple Locations') . '</option>
					</select>
				</div>';

	$SQL="SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$Result1 = DB_query($SQL);
	if (DB_num_rows($Result1)==0){
		echo '</div></fieldset>';
		prnMsg(__('There are no stock categories currently defined please use the link below to set them up'),'warn');
		echo '<br /><a href="' . $RootPath . '/StockCategories.php">' . __('Define Stock Categories') . '</a>';
		echo '</div></form></div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	echo '		<div class="form-group">
					<label for="StockCat">' . __('In Stock Category') . ':</label>
					<select name="StockCat" id="StockCat">';
	if (!isset($_POST['StockCat'])){ $_POST['StockCat']='All'; }
	$selectedAll = ($_POST['StockCat']=='All') ? 'selected="selected"' : '';
	echo '<option ' . $selectedAll . ' value="All">' . __('All') . '</option>';
	while ($MyRow1 = DB_fetch_array($Result1)) {
		$selected = ($MyRow1['categoryid']==$_POST['StockCat']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
	}
	echo '			</select>
				</div>
			</div>
		</fieldset>';

	echo '	<div class="button-group">
				<input type="submit" name="PrintPDF" title="Produce PDF Report" value="' . __('Print PDF') . '" />
				<input type="submit" name="View" title="View Report" value="' . __('View') . '" />
			</div>';

	echo '</form></div>';
	include(__DIR__ . '/includes/footer.php');

} /*end of else not PrintPDF */
