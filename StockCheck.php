<?php
require (__DIR__ . '/includes/session.php');
require (__DIR__ . '/includes/StockFunctions.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['PrintPDF'])) {

	// First off do the stock check file stuff
	if ($_POST['MakeStkChkData'] == 'New') {
		$SQL = "TRUNCATE TABLE stockcheckfreeze";
		$Result = DB_query($SQL);
		$SQL = "INSERT INTO stockcheckfreeze (stockid,
										  loccode,
										  qoh,
										  stockcheckdate)
					   SELECT locstock.stockid,
							  locstock.loccode,
							  locstock.quantity,
							  CURRENT_DATE
					   FROM locstock,
							stockmaster
					   WHERE locstock.stockid=stockmaster.stockid
					   AND locstock.loccode='" . $_POST['Location'] . "'
					   AND stockmaster.categoryid IN ('" . implode("','", $_POST['Categories']) . "')
					   AND stockmaster.mbflag!='A'
					   AND stockmaster.mbflag!='K'
					   AND stockmaster.mbflag!='D'";

		$ErrMsg = __('The inventory quantities could not be added to the freeze file');
		$Result = DB_query($SQL, $ErrMsg);
	}

	if ($_POST['MakeStkChkData'] == 'AddUpdate') {
		$SQL = "DELETE stockcheckfreeze
				FROM stockcheckfreeze
				INNER JOIN stockmaster ON stockcheckfreeze.stockid=stockmaster.stockid
				WHERE stockmaster.categoryid IN ('" . implode("','", $_POST['Categories']) . "')
				AND stockcheckfreeze.loccode='" . $_POST['Location'] . "'";

		$ErrMsg = __('The old quantities could not be deleted from the freeze file');
		$Result = DB_query($SQL, $ErrMsg);

		$SQL = "INSERT INTO stockcheckfreeze (stockid,
										  loccode,
										  qoh,
										  stockcheckdate)
				SELECT locstock.stockid,
					loccode ,
					locstock.quantity,
					CURRENT_DATE
				FROM locstock INNER JOIN stockmaster
				ON locstock.stockid=stockmaster.stockid
				WHERE locstock.loccode='" . $_POST['Location'] . "'
				AND stockmaster.categoryid IN ('" . implode("','", $_POST['Categories']) . "')
				AND stockmaster.mbflag!='A'
				AND stockmaster.mbflag!='K'
				AND stockmaster.mbflag!='G'
				AND stockmaster.mbflag!='D'";

		$ErrMsg = __('The inventory quantities could not be added to the freeze file');
		$Result = DB_query($SQL, $ErrMsg);

		$Title = __('Stock Check Freeze Update');
		include ('includes/header.php');
		echo '<p><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Print Check Sheets') . '</a>';
		prnMsg(__('Added to the stock check file successfully'), 'success');
		include ('includes/footer.php');
		exit();
	}

	$SQL = "SELECT stockmaster.categoryid,
				 stockcheckfreeze.stockid,
				 stockmaster.description,
				 stockmaster.decimalplaces,
				 stockcategory.categorydescription,
				 stockcheckfreeze.qoh
			 FROM stockcheckfreeze INNER JOIN stockmaster
			 ON stockcheckfreeze.stockid=stockmaster.stockid
			 INNER JOIN stockcategory
			 ON stockmaster.categoryid=stockcategory.categoryid
			 WHERE stockmaster.categoryid IN ('" . implode("','", $_POST['Categories']) . "')
			 AND (stockmaster.mbflag='B' OR mbflag='M')
			 AND stockcheckfreeze.loccode = '" . $_POST['Location'] . "'";
	if (isset($_POST['NonZerosOnly']) and $_POST['NonZerosOnly'] == true) {
		$SQL .= " AND stockcheckfreeze.qoh<>0";
	}

	$SQL .= " ORDER BY stockmaster.categoryid, stockmaster.stockid";

	$ErrMsg = __('The inventory quantities could not be retrieved');
	$InventoryResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($InventoryResult) == 0) {
		$Title = __('Stock Count Sheets - Problem Report');
		include ('includes/header.php');
		prnMsg(__('Before stock count sheets can be printed, a copy of the stock quantities needs to be taken - the stock check freeze. Make a stock check data file first'), 'error');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include ('includes/footer.php');
		exit();
	}

	// Build HTML for DomPDF
	$HTML = '<html><head><style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
		table { border-collapse: collapse; width: 100%; }
		th, td { border: 1px solid #000; padding: 5px; font-size: 10px; }
		.category-header { background: #eee; font-weight: bold; font-size: 10px; }
	</style></head><body>';
	$HTML .= '<h1>' . __('Stock Count Sheets') . ' - ' . date($_SESSION['DefaultDateFormat']) . '</h1>';
	$Category = '';

	$HTML .= '<table>';
	$HTML .= '<tr>';
	$HTML .= '<th>' . __('Category') . '</th>';
	$HTML .= '<th>' . __('Stock ID') . '</th>';
	$HTML .= '<th>' . __('Description') . '</th>';
	$HTML .= '<th>' . __('Quantity On Hand') . '</th>';
	if (isset($_POST['ShowInfo']) && $_POST['ShowInfo'] == true) {
		$HTML .= '<th>' . __('Demand') . '</th>';
		$HTML .= '<th>' . __('Net Quantity') . '</th>';
	}
	$HTML .= '<th>' . __('Counted Quantity') . '</th>';
	$HTML .= '</tr>';

	while ($InventoryCheckRow = DB_fetch_array($InventoryResult)) {
		// Print category header if changed
		if ($Category != $InventoryCheckRow['categoryid']) {
			$HTML .= '<tr class="category-header"><td colspan="6">' . htmlspecialchars($InventoryCheckRow['categoryid'] . ' - ' . $InventoryCheckRow['categorydescription']) . '</td><td></td></tr>';
			$Category = $InventoryCheckRow['categoryid'];
		}
		$HTML .= '<tr>';
		$HTML .= '<td>' . htmlspecialchars($InventoryCheckRow['categoryid']) . '</td>';
		$HTML .= '<td>' . htmlspecialchars($InventoryCheckRow['stockid']) . '</td>';
		$HTML .= '<td>' . htmlspecialchars($InventoryCheckRow['description']) . '</td>';
		$HTML .= '<td style="text-align:right">' . locale_number_format($InventoryCheckRow['qoh'], $InventoryCheckRow['decimalplaces']) . '</td>';
		if (isset($_POST['ShowInfo']) && $_POST['ShowInfo'] == true) {
			$DemandQty = GetDemand($InventoryCheckRow['stockid'], $_POST['Location']);
			$HTML .= '<td style="text-align:right">' . locale_number_format($DemandQty, $InventoryCheckRow['decimalplaces']) . '</td>';
			$HTML .= '<td style="text-align:right">' . locale_number_format($InventoryCheckRow['qoh'] - $DemandQty, $InventoryCheckRow['decimalplaces']) . '</td>';
		}
		$HTML .= '<td></td>';
		$HTML .= '</tr>';
	}
	$HTML .= '</table>';
	$HTML .= '</body></html>';

	// Generate PDF using Dompdf
	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');
	$DomPDF->render();

	$FileName = $_SESSION['DatabaseName'] . '_Stock_Count_Sheets_' . date('Y-m-d') . '.pdf';

	// Output PDF inline in browser
	$DomPDF->stream($FileName, array('Attachment' => false));

} else { /*The option to print PDF was not hit */

	$Title = __('Stock Check Sheets');
	$ViewTopic = 'Inventory';
	$BookMark = '';
	include ('includes/header.php');

	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/printer.png" title="' . __('print') . '" alt="" />' . ' ' . $Title . '</p>';

	echo '<style>
		.modern-form-container {
			max-width: 900px;
			margin: 20px auto;
			padding: 30px;
			background: var(--surface);
			border: 1px solid var(--border);
			border-radius: var(--radius-lg);
			box-shadow: var(--shadow-md);
		}
		.form-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
			gap: 25px;
			margin-bottom: 30px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group.checkbox-group {
			flex-direction: row;
			align-items: center;
			gap: 12px;
			padding: 10px;
			background: var(--primary-soft);
			border-radius: var(--radius-sm);
		}
		.form-group label {
			font-weight: 600;
			color: var(--text-label);
			font-size: 0.9rem;
		}
		.form-group select {
			padding: 10px;
			border: 1px solid var(--border);
			border-radius: var(--radius-sm);
			background: var(--surface);
			font-size: 0.9rem;
			transition: all var(--transition-fast);
		}
		.form-group select:focus {
			border-color: var(--primary);
			box-shadow: 0 0 0 3px var(--primary-soft);
			outline: none;
		}
		.button-group {
			display: flex;
			justify-content: center;
			gap: 15px;
			border-top: 1px solid var(--border-soft);
			padding-top: 25px;
		}
		.button-group input[type="submit"] {
			padding: 12px 35px;
			border-radius: var(--radius-sm);
			font-weight: 700;
			cursor: pointer;
			border: none;
			transition: all var(--transition-fast);
			background: var(--primary);
			color: white;
		}
		.button-group input[type="submit"]:hover {
			opacity: 0.9;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px var(--primary-glow);
			background: var(--primary-hover);
		}
	</style>';

	echo '<div class="modern-form-container">';
	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	echo '<div class="form-grid">';
	
	echo '<div class="form-group">
			<label>' . __('Inventory Categories') . '</label>
			<select autofocus="autofocus" required="required" name="Categories[]" multiple="multiple" style="height: 150px;">';
	$SQL = 'SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription';
	$CatResult = DB_query($SQL);
	while ($MyRow = DB_fetch_array($CatResult)) {
		$selected = (isset($_POST['Categories']) and in_array($MyRow['categoryid'], $_POST['Categories'])) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Stock Location') . '</label>
			<select name="Location">';
	$SQL = "SELECT locations.loccode, locationname FROM locations
			INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1
			ORDER BY locationname";
	$LocnResult = DB_query($SQL);
	while ($MyRow = DB_fetch_array($LocnResult)) {
		echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '  </select>
		  </div>';

	echo '<div class="form-group">
			<label>' . __('Action for Stock Check Freeze') . '</label>
			<select name="MakeStkChkData">
				<option value="New">' . __('Make new stock check data file') . '</option>
				<option value="AddUpdate">' . __('Add/update existing stock check file') . '</option>
				<option selected="selected" value="PrintOnly">' . __('Print Stock Check Sheets Only') . '</option>
			</select>
		  </div>';

	echo '<div style="display: flex; flex-direction: column; gap: 15px;">';
	echo '<div class="form-group checkbox-group">
			<input type="checkbox" name="ShowInfo" value="true" ' . (isset($_POST['ShowInfo']) && $_POST['ShowInfo'] == 'true' ? 'checked' : '') . ' id="ShowInfo" />
			<label for="ShowInfo">' . __('Show system quantity on sheets') . '</label>
		  </div>';

	echo '<div class="form-group checkbox-group">
			<input type="checkbox" name="NonZerosOnly" value="true" ' . (isset($_POST['NonZerosOnly']) && $_POST['NonZerosOnly'] == 'true' ? 'checked' : '') . ' id="NonZerosOnly" />
			<label for="NonZerosOnly">' . __('Only print items with non zero quantities') . '</label>
		  </div>';
	echo '</div>';
	
	echo '</div>'; // end form-grid

	echo '<div class="button-group">
			<input type="submit" name="PrintPDF" value="' . __('Print and Process') . '" />
		  </div>';
	echo '</form></div>';

	include ('includes/footer.php');

} /*end of else not PrintPDF */
