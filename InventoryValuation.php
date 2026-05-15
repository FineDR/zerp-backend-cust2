<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PrintPDF']) or isset($_POST['Spreadsheet']) or isset($_POST['View'])){

/*Now figure out the inventory data to report for the category range under review */
	if ($_POST['Location']=='All'){
		$SQL = "SELECT stockmaster.categoryid,
					stockcategory.categorydescription,
					stockmaster.stockid,
					stockmaster.description,
					stockmaster.decimalplaces,
					SUM(locstock.quantity) AS qtyonhand,
					stockmaster.units,
					stockmaster.actualcost AS unitcost,
					SUM(locstock.quantity) *(stockmaster.actualcost) AS itemtotal
				FROM stockmaster,
					stockcategory,
					locstock
				INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE stockmaster.stockid=locstock.stockid
				AND stockmaster.categoryid=stockcategory.categoryid
				GROUP BY stockmaster.categoryid,
					stockcategory.categorydescription,
					unitcost,
					stockmaster.units,
					stockmaster.decimalplaces,
					stockmaster.actualcost,
					stockmaster.stockid,
					stockmaster.description
				HAVING SUM(locstock.quantity)!=0
				AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
				ORDER BY stockcategory.categorydescription,
					stockmaster.stockid";
	} else {
		$SQL = "SELECT stockmaster.categoryid,
					stockcategory.categorydescription,
					stockmaster.stockid,
					stockmaster.description,
					stockmaster.units,
					stockmaster.decimalplaces,
					locstock.quantity AS qtyonhand,
					stockmaster.actualcost AS unitcost,
					locstock.quantity *(stockmaster.actualcost) AS itemtotal
				FROM stockmaster,
					stockcategory,
					locstock
				INNER JOIN locationusers ON locationusers.loccode=locstock.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
				WHERE stockmaster.stockid=locstock.stockid
				AND stockmaster.categoryid=stockcategory.categoryid
				AND locstock.quantity!=0
				AND stockmaster.categoryid IN ('". implode("','",$_POST['Categories'])."')
				AND locstock.loccode = '" . $_POST['Location'] . "'
				ORDER BY stockcategory.categorydescription,
					stockmaster.stockid";
	}
	$ErrMsg =  __('The inventory valuation could not be retrieved');
	$InventoryResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($InventoryResult)==0){
		$Title = __('Print Inventory Valuation Error');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('There were no items with any value to print out for the location specified'),'info');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	if ($_POST['DetailedReport']=='Yes'){
		$HTML .= '<meta name="author" content="WebERP " . $Version">
				<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
					<div class="centre" id="ReportHeader">
						' . $_SESSION['CompanyRecord']['coyname'] . '<br />
						' . __('Inventory Valuation Report') . '<br />
						' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
					</div>
					<div class="report-table-wrapper">
					<table>
						<thead>
							<tr>
								<th>' . __('Category') . '/' . __('Item') . '</th>
								<th>' . __('Description') . '</th>
								<th>' . __('Quantity') . '</th>
								<th>' . __('Cost Per Unit') . '</th>
								<th>' . __('Units') . '</th>
								<th>' . __('Extended Cost') . '</th>
							</tr>
						</thead>
						<tbody>';
	} else {
		$HTML .= '<meta name="author" content="WebERP " . $Version">
				<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
					<div class="centre" id="ReportHeader">
						' . $_SESSION['CompanyRecord']['coyname'] . '<br />
						' . __('Inventory Valuation Report') . '<br />
						' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
					</div>
					<div class="report-table-wrapper">
					<table>
						<thead>
							<tr>
								<th>' . __('Category') . '/' . __('Item') . '</th>
								<th>' . __('Quantity') . '</th>
								<th>' . __('Extended Cost') . '</th>
							</tr>
						</thead>
						<tbody>';
	}

	$Tot_Val=0;
	$Category = '';
	$CatTot_Val=0;
	$CatTot_Qty=0;

	while ($InventoryValn = DB_fetch_array($InventoryResult)){

		if ($Category!=$InventoryValn['categoryid']){
			if ($Category!=''){ /*Then it's NOT the first time round */

				/* need to print the total of previous category */
				$HTML .= '<tr class="total_row">';
				$DisplayCatTotQty = locale_number_format($CatTot_Qty,2);
				$DisplayCatTotVal = locale_number_format($CatTot_Val,$_SESSION['CompanyRecord']['decimalplaces']);
				if ($_POST['DetailedReport']=='Yes'){

					/* need to print the total of previous category */
					$DisplayCatTotVal = locale_number_format($CatTot_Val, 2);
					$HTML .= '<td colspan="2">' . __('Total for') . ' ' . $Category . " - " . $CategoryName . '</td>
								<td class="number">' . $DisplayCatTotQty . '</td>
								<td></td>';

					$CatTot_Qty = 0;
					$CatTot_Val = 0;
					$HTML .= '<td></td>
								<td class="number">' . $DisplayCatTotVal . '</td>
							</tr>';
				} else {
					$HTML .= '<td>' .  $Category . " - " . $CategoryName . '</td>
							<td class="number">' . $DisplayCatTotQty . '</td>
							<td class="number">' . $DisplayCatTotVal . '</td>
							</tr>';
				}


			}
			if ($_POST['DetailedReport']=='Yes'){
				$HTML .= '<tr>
							<th colspan="6"><h3>' . $InventoryValn['categoryid'] . ' - ' . $InventoryValn['categorydescription'] . '</h3></th>
						</tr>';
			}
			$Category = $InventoryValn['categoryid'];
			$CategoryName = $InventoryValn['categorydescription'];
		}

		if ($_POST['DetailedReport']=='Yes'){

			$DisplayUnitCost = locale_number_format($InventoryValn['unitcost'],$_SESSION['CompanyRecord']['decimalplaces']);
			$DisplayQtyOnHand = locale_number_format($InventoryValn['qtyonhand'],$InventoryValn['decimalplaces']);
			$DisplayItemTotal = locale_number_format($InventoryValn['itemtotal'],$_SESSION['CompanyRecord']['decimalplaces']);

			$HTML .= '<tr class="striped_row">
						<td>' . $InventoryValn['stockid'] . '</td>
						<td>' . $InventoryValn['description'] . '</td>
						<td class="number">' . $DisplayQtyOnHand . '</td>
						<td class="number">' . $DisplayUnitCost . '</td>
						<td class="number">' . $InventoryValn['units'] . '</td>
						<td class="number">' . $DisplayItemTotal . '</td>
					</tr>';
		}
		$Tot_Val += $InventoryValn['itemtotal'];
		$CatTot_Val += $InventoryValn['itemtotal'];
		$CatTot_Qty += $InventoryValn['qtyonhand'];

	} /*end inventory valn while loop */

/*Print out the category totals */
	$DisplayCatTotVal = locale_number_format($CatTot_Val,$_SESSION['CompanyRecord']['decimalplaces']);
	$DisplayCatTotQty = locale_number_format($CatTot_Qty,2);

	$HTML .= '<tr class="total_row">';
	if ($_POST['DetailedReport']=='Yes'){
		$HTML .= '<td colspan="2">' . __('Total for') . ' ' . $Category . ' - ' . $CategoryName . '</td>';
		$HTML .= '<td class="number">' . $DisplayCatTotQty . '</td>
					<td colspan="2"></td>
					<td class="number">' . $DisplayCatTotVal . '</td>
				</tr>';
	} else {
		$HTML .= '<td>' .  $Category . " - " . $CategoryName . '</td>
				<td class="number">' . $DisplayCatTotQty . '</td>
				<td class="number">' . $DisplayCatTotVal . '</td>
			</tr>';
	}

/*Print out the grand totals */
	$DisplayTotalVal = locale_number_format($Tot_Val,$_SESSION['CompanyRecord']['decimalplaces']);
	if ($_POST['DetailedReport']=='Yes'){
		$HTML .= '<tr class="total_row">
					<td class="number" colspan="5">' . __('Grand Total Value') . '</td>
					<td class="number">' . $DisplayTotalVal . '</td>
				</tr>';
	} else {
		$HTML .= '<tr class="total_row">
					<td class="number" colspan="2">' . __('Grand Total Value') . '</td>
					<td class="number">' . $DisplayTotalVal . '</td>
				</tr>';
	}

	if (isset($_POST['PrintPDF']) or isset($_POST['Email'])) {
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
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_InventoryValuation_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} elseif (isset($_POST['Spreadsheet'])) {
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$File = 'InventoryValuation-' . date('Y-m-d'). '.' . 'ods';

		header('Content-Disposition: attachment;filename="' . $File . '"');
		header('Cache-Control: max-age=0');
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
		$spreadsheet = $reader->loadFromString($HTML);

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Ods');
		$writer->save('php://output');
	} else {
		$Title = __('Inventory Valuation Report');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . __('Inventory') . '" alt="" />' . ' ' . $Title . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF nor to create the CSV was not hit */

	$Title=__('Inventory Valuation Reporting');
	$ViewTopic = 'Inventory';
	$BookMark = 'InventoryValuation';
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
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
		.form-group select, .form-group input {
			padding: 10px 12px;
			border: 1px solid #d1d5db;
			border-radius: 8px;
			font-size: 0.95rem;
			transition: border-color 0.2s, box-shadow 0.2s;
			background-color: #f9fafb;
		}
		.form-group select:focus {
			outline: none;
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			background-color: #fff;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 12px;
			flex-wrap: wrap;
			margin-top: 10px;
		}
		.button-group input[type="submit"] {
			padding: 10px 24px;
			border-radius: 8px;
			font-weight: 600;
			cursor: pointer;
			transition: transform 0.1s, opacity 0.2s;
			border: none;
		}
		.button-group input[name="PrintPDF"] { background: var(--primary); color: white; }
		.button-group input[name="View"] { background: var(--primary); color: white; }
		.button-group input[name="Spreadsheet"] { background: #6b7280; color: white; }
		.button-group input:hover { opacity: 0.9; transform: translateY(-1px); background: var(--primary-hover); }

		/* Responsive Table Styles */
		.report-table-wrapper {
			width: 100%;
			overflow-x: auto;
			margin-top: 20px;
			background: white;
			border-radius: 8px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		table {
			width: 100%;
			border-collapse: collapse;
			font-size: 0.9rem;
		}
		th {
			background: #f3f4f6;
			color: #374151;
			font-weight: 600;
			text-align: left;
			padding: 12px 15px;
			border-bottom: 2px solid #e5e7eb;
			white-space: nowrap;
		}
		td {
			padding: 12px 15px;
			border-bottom: 1px solid #f3f4f6;
		}
		.striped_row:nth-child(even) { background: #f9fafb; }
		.total_row { background: #fefce8 !important; font-weight: bold; }
		.number { text-align: right; }
		
		@media (max-width: 640px) {
			.modern-form-container { padding: 15px; margin: 10px; }
			.form-grid { grid-template-columns: 1fr; }
			.button-group input { width: 100%; }
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<p class="page_title_text">
			<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" title="' . __('Inventory') . '" alt="" />' . ' ' . $Title . '
		</p>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="form-grid">
			<div class="form-group">
				<label for="Categories">' . __('Select Inventory Categories') . ':</label>
				<select autofocus="autofocus" required="required" minlength="1" name="Categories[]" id="Categories" multiple="multiple" style="height: 150px;">';
	
	$SQL = 'SELECT categoryid, categorydescription
			FROM stockcategory
			ORDER BY categorydescription';
	$CatResult = DB_query($SQL);
	while ($MyRow = DB_fetch_array($CatResult)) {
		if (isset($_POST['Categories']) AND in_array($MyRow['categoryid'], $_POST['Categories'])) {
			echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] .'</option>';
		} else {
			echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
	}
	echo '		</select>
			</div>';

	echo '	<div class="form-group">
				<label for="Location">' . __('For Inventory in Location') . ':</label>
				<select name="Location" id="Location">';
	
	$SQL = "SELECT locations.loccode,
					locationname
			FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1
			ORDER BY locationname";
	$LocnResult = DB_query($SQL);
	echo '<option value="All">' . __('All Locations') . '</option>';
	while ($MyRow=DB_fetch_array($LocnResult)){
		echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '		</select>
			</div>';

	echo '	<div class="form-group">
				<label for="DetailedReport">' . __('Summary or Detailed Report') . ':</label>
				<select name="DetailedReport" id="DetailedReport">
					<option selected="selected" value="No">' . __('Summary Report') . '</option>
					<option value="Yes">' . __('Detailed Report') . '</option>
				</select>
			</div>
		</div>';

	echo '<div class="button-group">
				<input type="submit" name="PrintPDF" title="Produce PDF Report" value="' . __('Print PDF') . '" />
				<input type="submit" name="View" title="View Report" value="' . __('View') . '" />
				<input type="submit" name="Spreadsheet" title="Spreadsheet" value="' . __('Spreadsheet') . '" />
		</div>';
	echo '</form></div>';

	include(__DIR__ . '/includes/footer.php');

} /*end of else not PrintPDF */
