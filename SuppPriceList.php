<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_GET['SelectedSupplier'])) {
	$_POST['supplierid']=$_GET['SelectedSupplier'];
}

if (isset($_POST['PrintPDF']) or isset($_POST['View']) or isset($_POST['Email'])) {

	//get supplier
	$SQLsup = "SELECT suppname,
					  currcode,
					  decimalplaces AS currdecimalplaces
				FROM suppliers INNER JOIN currencies
				ON suppliers.currcode=currencies.currabrev
				WHERE supplierid='" . $_POST['supplier'] . "'";
	$Resultsup = DB_query($SQLsup);
	$RowSup = DB_fetch_array($Resultsup);
	$SupplierName=$RowSup['suppname'];
	$CurrCode =$RowSup['currcode'];
	$CurrDecimalPlaces=$RowSup['currdecimalplaces'];

	//get category
	if ($_POST['category']!='all'){
		$SQLcat="SELECT categorydescription
				FROM `stockcategory`
				WHERE categoryid ='" . $_POST['category'] . "'";

		$Resultcat = DB_query($SQLcat);
		$RowCat = DB_fetch_row($Resultcat);
		$Categoryname=$RowCat['0'];
	} else {
		$Categoryname='ALL';
	}


	//get date price
	if ($_POST['price']=='all'){
		$CurrentOrAllPrices=__('All Prices');
	} else {
		$CurrentOrAllPrices=__('Current Price');
	}

	//price and category = all
	if (($_POST['price']=='all') AND ($_POST['category']=='all')){
		$SQL = "SELECT 	purchdata.stockid,
					stockmaster.description,
					purchdata.price,
					purchdata.conversionfactor,
					(purchdata.effectivefrom)as dateprice,
					purchdata.supplierdescription,
					purchdata.suppliers_partno
				FROM purchdata,stockmaster
				WHERE supplierno='" . $_POST['supplier'] . "'
				AND stockmaster.stockid=purchdata.stockid
				ORDER BY stockid ASC ,dateprice DESC";
	} else {
	//category=all and price != all
		if (($_POST['price']!='all') AND ($_POST['category']=='all')){

			$SQL = "SELECT purchdata.stockid,
							stockmaster.description,
							(SELECT purchdata.price
							 FROM purchdata
							 WHERE purchdata.stockid = stockmaster.stockid
							 ORDER BY effectivefrom DESC
							 LIMIT 0,1) AS price,
							purchdata.conversionfactor,
							(SELECT purchdata.effectivefrom
							 FROM purchdata
							 WHERE purchdata.stockid = stockmaster.stockid
							 ORDER BY effectivefrom DESC
							 LIMIT 0,1) AS dateprice,
							purchdata.supplierdescription,
							purchdata.suppliers_partno
					FROM purchdata, stockmaster
					WHERE supplierno = '" . $_POST['supplier'] . "'
					AND stockmaster.stockid = purchdata.stockid
					GROUP BY stockid
					ORDER BY stockid ASC , dateprice DESC";
		} else {
			//price = all category !=all
			if (($_POST['price']=='all')and($_POST['category']!='all')){

				$SQL = "SELECT 	purchdata.stockid,
								stockmaster.description,
								purchdata.price,
								purchdata.conversionfactor,
								(purchdata.effectivefrom)as dateprice,
								purchdata.supplierdescription,
								purchdata.suppliers_partno
						FROM purchdata,stockmaster
						WHERE supplierno='" . $_POST['supplier'] . "'
						AND stockmaster.stockid=purchdata.stockid
						AND stockmaster.categoryid='" . $_POST['category'] .  "'
						ORDER BY stockid ASC ,dateprice DESC";
			} else {
			//price != all category !=all
				$SQL = "SELECT 	purchdata.stockid,
								stockmaster.description,
								(SELECT purchdata.price
								 FROM purchdata
								 WHERE purchdata.stockid = stockmaster.stockid
								 ORDER BY effectivefrom DESC
								 LIMIT 0,1) AS price,
								purchdata.conversionfactor,
								(SELECT purchdata.effectivefrom
								FROM purchdata
								WHERE purchdata.stockid = stockmaster.stockid
								ORDER BY effectivefrom DESC
								LIMIT 0,1) AS dateprice,
								purchdata.supplierdescription,
								purchdata.suppliers_partno
						FROM purchdata,stockmaster
						WHERE supplierno='" . $_POST['supplier'] . "'
						AND stockmaster.stockid=purchdata.stockid
						AND stockmaster.categoryid='" . $_POST['category'] .  "'
						GROUP BY stockid
						ORDER BY stockid ASC ,dateprice DESC";
			}
		}
	}
	$ErrMsg =  __('The Price List could not be retrieved');
	$PricesResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PricesResult)==0) {

		$Title = __('Supplier Price List') . '-' . __('Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('There are no result so the PDF is empty'));
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	$HTML = '';

	if (isset($_POST['PrintPDF']) or isset($_POST['Email'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="db-card">
					<div class="db-card-body">
						<div class="db-grid db-grid-4">
							<div class="db-info-item">
								<div class="db-info-label">' . __('Report Type') . '</div>
								<div class="db-info-value">' . $CurrentOrAllPrices . '</div>
							</div>
							<div class="db-info-item">
								<div class="db-info-label">' . __('Supplier') . '</div>
								<div class="db-info-value">' . $_POST['supplier'] . ' - ' . $SupplierName . '</div>
							</div>
							<div class="db-info-item">
								<div class="db-info-label">' . __('Category') . '</div>
								<div class="db-info-value">' . $Categoryname . '</div>
							</div>
							<div class="db-info-item">
								<div class="db-info-label">' . __('Currency') . '</div>
								<div class="db-info-value">' . $CurrCode . '</div>
							</div>
						</div>
					</div>
				</div>';
				<div class="db-card" style="margin-top: var(--space-4);">
					<div class="db-card-body" style="padding: 0;">
						<div class="db-table-wrapper">
							<table class="db-table">
								<thead>
									<tr>
										<th>' . __('Code') . '</th>
										<th>' . __('Description') . '</th>
										<th class="text-right">' . __('Conv Factor') . '</th>
										<th class="text-right">' . __('Price') . '</th>
										<th>' . __('Date From') . '</th>
										<th>' . __('Supp Code') . '</th>
									</tr>
								</thead>
								<tbody>';

		while ($MyRow = DB_fetch_array($PricesResult)) {
			$HTML .= '<tr>
						<td class="db-font-semibold">' . $MyRow['stockid'] . '</td>
						<td>' . $MyRow['description'] . '</td>
						<td class="text-right">' . $MyRow['conversionfactor'] . '</td>
						<td class="text-right db-font-bold">' . locale_number_format($MyRow['price'], $CurrDecimalPlaces) . '</td>
						<td class="text-nowrap">' . ConvertSQLDate($MyRow['dateprice']) . '</td>
						<td class="db-text-muted">' . $MyRow['suppliers_partno'] . '</td>
					</tr>';

		}

		$HTML .= '</tbody>
							</table>
						</div>
					</div>
				</div>';

	if (isset($_POST['PrintPDF']) or isset($_POST['Email'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table>
		$HTML .= '<div class="db-form-actions" style="margin-top: var(--space-4); justify-content: center;">
					<button type="button" class="db-btn db-btn-secondary" onclick="window.close()">' . __('Close Window') . '</button>
				</div>';
	}

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_SupplierPriceList_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} elseif (isset($_POST['Email'])) {

		/// @todo we could skip generating the pdf if $_SESSION['InventoryManagerEmail'] == ''
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);
		// (Optional) set up the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
		// Render the HTML as PDF
		$DomPDF->render();
		// Output the generated PDF to a temporary file
		$output = $DomPDF->output();

		$PDFFileName = sys_get_temp_dir() . '/' . $_SESSION['DatabaseName'] . '_SupplierPriceList_' . date('Y-m-d') . '.pdf';
		file_put_contents($PDFFileName, $output);

		if ($_SESSION['InventoryManagerEmail']!='') {
			$ConfirmationText = __('Please find attached the Supplier Price List, generated by user') . ' ' . $_SESSION['UserID'] . ' ' . __('at') . ' ' . date('Y-m-d H:i:s');
			$EmailSubject = $_SESSION['DatabaseName'] . '_SupplierPriceList_' . date('Y-m-d') . '.pdf';
			SendEmailFromWebERP($_SESSION['CompanyRecord']['email'],
								array($_SESSION['InventoryManagerEmail'] =>  ''),
								$EmailSubject,
								$ConfirmationText,
								array($PDFFileName)
							);
		}
		unlink($PDFFileName);

		$Title = __('Send Report By Email');
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page">
				<div class="db-page-header">
					<h1 class="db-page-title">' . $Title . '</h1>
				</div>
				<div class="db-card">
					<div class="db-card-body">
						<p class="text-center">' . __('The report has been successfully sent via email.') . '</p>
						<div class="db-form-actions" style="justify-content: center;">
							<button type="button" class="db-btn db-btn-secondary" onclick="window.close()">' . __('Close Window') . '</button>
						</div>
					</div>
				</div>
			</div>';
		include(__DIR__ . '/includes/footer.php');
	} else {
		$Title = __('View Supplier Price List');
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page">
				<div class="db-page-header">
					<div>
						<h1 class="db-page-title">' . $Title . '</h1>
						<p class="db-page-subtitle">' . __('Viewing price list for') . ' ' . $SupplierName . '</p>
					</div>
				</div>';
		echo $HTML;
		echo '</div> <!-- End db-page -->';
		include(__DIR__ . '/includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */

	$Title=__('Supplier Price List');
	$ViewTopic = 'AccountsPayable';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');
	echo '<div class="db-page">
			<div class="db-page-header">
				<div>
					<h1 class="db-page-title">' . $Title . '</h1>
					<p class="db-page-subtitle">' . __('Generate and view price lists for selected suppliers and categories') . '</p>
				</div>
			</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	$SQL = "SELECT supplierid,suppname FROM `suppliers`";
	$Result = DB_query($SQL);
	echo '<div class="db-card">
			<div class="db-card-title">' . __('Report Criteria') . '</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-3">';
					<div class="db-form-group">
						<label class="db-form-label">' . __('Supplier') . ':</label>
						<select name="supplier" class="db-form-select">';
	while ($MyRow=DB_fetch_array($Result)){
		if (isset($_POST['supplierid']) and ($MyRow['supplierid'] == $_POST['supplierid'])) {
			 echo '<option selected="selected" value="' . $MyRow['supplierid'] . '">' . $MyRow['supplierid'].' - '.$MyRow['suppname'] . '</option>';
		} else {
			 echo '<option value="' . $MyRow['supplierid'] . '">' . $MyRow['supplierid'].' - '.$MyRow['suppname'] . '</option>';
		}
	}
	echo '				</select>
					</div>';

	$SQL="SELECT categoryid, categorydescription FROM stockcategory";
	$Result = DB_query($SQL);
	echo '			<div class="db-form-group">
						<label class="db-form-label">' . __('Category') . ':</label>
						<select name="category" class="db-form-select">';
		echo '<option value="all">' . __('ALL') . '</option>';
	while ($MyRow=DB_fetch_array($Result)){
		if (isset($_POST['categoryid']) and ($MyRow['categoryid'] == $_POST['categoryid'])) {
			 echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categoryid'] . ' - ' . $MyRow['categorydescription'] . '</option>';
		} else {
			 echo '<option value="' . $MyRow['categoryid'] . '">' .$MyRow['categoryid'].' - '. $MyRow['categorydescription'] . '</option>';
		}
	}
	echo '				</select>
					</div>';

	echo '			<div class="db-form-group">
						<label class="db-form-label">' . __('Price List') . ':</label>
						<select name="price" class="db-form-select">
							<option value="all">' .__('All Prices') . '</option>
							<option value="current">' .__('Only Current Price') . '</option>
						</select>
					</div>
				</div> <!-- End Grid -->
			</div> <!-- End Card Body -->
			<div class="db-card-footer">
				<div class="db-form-actions">
					<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary">' . __('Print PDF') . '</button>
					<button type="submit" name="View" class="db-btn db-btn-primary">' . __('View Results') . '</button>
					<button type="submit" name="Email" class="db-btn db-btn-outline">' . __('Email Report') . '</button>
				</div>
			</div>
		</div>';

	echo '</form>
	</div> <!-- End db-page -->';
	include(__DIR__ . '/includes/footer.php');

} /*end of else not PrintPDF */

function PrintHeader($pdf,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
					 $Page_Width,$Right_Margin,$SupplierName,$Categoryname,$CurrCode,$CurrentOrAllPrices) {


	/*PDF page header for Supplier price list */
	if ($PageNumber>1){
		$pdf->newPage();
	}
	$LineHeight=12;
	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;
	$YPos -=(3*$LineHeight);


	$FontSize=8;
	$PageNumber++;
} // End of PrintHeader() function
