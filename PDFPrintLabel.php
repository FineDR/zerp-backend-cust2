<?php

require(__DIR__ . '/includes/session.php');

// Include DomPDF
require_once(__DIR__ . '/vendor/autoload.php'); // Make sure DomPDF is installed via composer

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');
use BarcodePack\code128;

if (isset($_POST['EffectiveDate'])) {
	$_POST['EffectiveDate'] = ConvertSQLDate($_POST['EffectiveDate']);
}

$PtsPerMM = 2.83464567; //pdf points per mm (72 dpi / 25.4 mm per inch)

if ((isset($_POST['ShowLabels']) or isset($_POST['SelectAll']))
	and isset($_POST['StockCategory'])
	and mb_strlen($_POST['StockCategory']) >= 1) {

	$Title = __('Print Labels');
	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-bottom-layout">';

	// SIDEBAR (Always show filters)
	echo '<aside class="db-col-aside">
			<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-card">
					<div class="db-card-header">
						<h3 class="db-card-title"><i class="fas fa-tag"></i> ' . __('Label Parameters') . '</h3>
					</div>
					<div class="db-card-body">
						<div class="db-form-group">
							<label class="db-label">' . __('Template') . '</label>
							<select name="LabelID" class="db-select">';
	$LabRes = DB_query("SELECT labelid, description FROM labels");
	while ($LabRow = DB_fetch_array($LabRes)) {
		$sel = (isset($_POST['LabelID']) AND $_POST['LabelID'] == $LabRow['labelid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $LabRow['labelid'] . '">' . $LabRow['description'] . '</option>';
	}
	echo '					</select>
						</div>
						
						<div class="db-form-group">
							<label class="db-label">' . __('Category') . '</label>
							<select name="StockCategory" class="db-select">';
	$CatRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
	while ($CatRow = DB_fetch_array($CatRes)) {
		$sel = (isset($_POST['StockCategory']) AND $_POST['StockCategory'] == $CatRow['categoryid']) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $CatRow['categoryid'] . '">' . $CatRow['categorydescription'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Sales Type') . '</label>
							<select name="SalesType" class="db-select">';
	$STRes = DB_query("SELECT sales_type, typeabbrev FROM salestypes");
	while ($STRow = DB_fetch_array($STRes)) {
		$sel = ($_POST['SalesType'] == $STRow['typeabbrev'] OR (!isset($_POST['SalesType']) AND $_SESSION['DefaultPriceList'] == $STRow['typeabbrev'])) ? 'selected="selected"' : '';
		echo '<option ' . $sel . ' value="' . $STRow['typeabbrev'] . '">' . $STRow['sales_type'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Effective Date') . '</label>
							<input type="date" name="EffectiveDate" class="db-input" value="' . ($_POST['EffectiveDate'] ?? date('Y-m-d')) . '" />
						</div>

						<button type="submit" name="ShowLabels" class="db-btn db-btn-primary" style="width: 100%; margin-top: 15px;">
							<i class="fas fa-sync"></i> ' . __('Refresh Items') . '
						</button>
					</div>
				</div>
				<div style="margin-top: 15px; text-align: center;">
					<a href="' . $RootPath . '/Labels.php" class="db-link" style="font-size: 0.8rem;"><i class="fas fa-cog"></i> ' . __('Maintenance Template') . '</a>
				</div>
			</form>
		  </aside>';

	echo '<main class="db-col-main" id="main_content">';


	$ErrMsg = __('The Price Labels could not be retrieved');
	$LabelsResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($LabelsResult) == 0) {
		prnMsg(__('There were no price labels to print out for the category specified'), 'warn');
		echo '<br /><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' .  __('Back') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="LabelID" value="' . $_POST['LabelID'] . '" />
			<input type="hidden" name="StockCategory" value="' . $_POST['StockCategory'] . '" />
			<input type="hidden" name="SalesType" value="' . $_POST['SalesType'] . '" />
			<input type="hidden" name="Currency" value="' . $_POST['Currency'] . '" />
			<input type="hidden" name="EffectiveDate" value="' . FormatDateForSQL($_POST['EffectiveDate']) . '" />
			<input type="hidden" name="LabelsPerItem" value="' . $_POST['LabelsPerItem'] . '" />

			<div class="db-card">
				<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center;">
					<h3 class="db-card-title"><i class="fas fa-list-check"></i> ' . __('Select Labels to Print') . '</h3>
					<div style="display: flex; align-items: center; gap: 15px;">
						<div style="display: flex; align-items: center; gap: 5px; font-size: 0.8rem;">
							<input type="checkbox" id="check_all" name="CheckAll" ' . (isset($_POST['CheckAll']) ? 'checked="checked"' : '') . ' onchange="this.form.ShowLabels.name=\'SelectAll\'; this.form.submit();" />
							<label for="check_all">' . __('Select All') . '</label>
						</div>
						<button type="submit" name="PrintLabels" class="db-btn db-btn-primary" style="font-size: 0.8rem; padding: 6px 20px;">
							<i class="fas fa-print"></i> ' . __('Render PDF') . '
						</button>
					</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-wrapper" style="border: 1px solid var(--border-soft); border-radius: var(--radius-sm);">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Item Code') . '</th>
									<th>' . __('Item Description') . '</th>
									<th class="text-right">' . __('Price') . '</th>
									<th class="text-center">' . __('Include') . '</th>
								</tr>
							</thead>
							<tbody>';

	$i = 0;
	while ($LabelRow = DB_fetch_array($LabelsResult)) {
		$checked = (isset($_POST['SelectAll']) && isset($_POST['CheckAll'])) ? 'checked="checked"' : '';
		echo '			<tr>
							<td><div class="db-font-bold text-primary">' . $LabelRow['stockid'] . '</div></td>
							<td style="font-size: 0.9rem;">' . $LabelRow['description'] . '</td>
							<td class="text-right db-font-bold">' . locale_number_format($LabelRow['price'], $LabelRow['decimalplaces']) . '</td>
							<td class="text-center">
								<input type="checkbox" ' . $checked . ' name="PrintLabel' . $i . '" style="width: 18px; height: 18px; cursor: pointer;" />
								<input type="hidden" name="StockID' . $i . '" value="' . $LabelRow['stockid'] . '" />
								<input type="hidden" name="Description' . $i . '" value="' . $LabelRow['description'] . '" />
								<input type="hidden" name="Barcode' . $i . '" value="' . $LabelRow['barcode'] . '" />
								<input type="hidden" name="Price' . $i . '" value="' . locale_number_format($LabelRow['price'], $LabelRow['decimalplaces']) . '" />
							</td>
						</tr>';
		$i++;
	}
	$i--;
	echo '				</tbody>
						<tfoot>
							<tr>
								<td colspan="4" class="text-right" style="padding: 15px;">
									<input type="hidden" name="NoOfLabels" value="' . $i . '" />
									<input type="hidden" name="ShowLabels" value="1" />
								</td>
							</tr>
						</tfoot>
						</table>
					</div>
				</div>
			</div>
			</form>';

	echo '	</main>
		</div>'; // end db-bottom-layout
	include(__DIR__ . '/includes/footer.php');
	exit();
}


$NoOfLabels = 0;
if (isset($_POST['PrintLabels']) && isset($_POST['NoOfLabels']) && $_POST['NoOfLabels'] > 0) {
	for ($i = 0; $i < $_POST['NoOfLabels']; $i++) {
		if (isset($_POST['PrintLabel' . $i])) {
			$NoOfLabels++;
		}
	}
	if ($NoOfLabels == 0) {
		prnMsg(__('There are no labels selected to print'), 'info');
	}
}

if (isset($_POST['PrintLabels']) && $NoOfLabels > 0) {

	$Result = DB_query("SELECT description,
								pagewidth*" . $PtsPerMM . " as page_width,
								pageheight*" . $PtsPerMM . " as page_height,
								width*" . $PtsPerMM . " as label_width,
								height*" . $PtsPerMM . " as label_height,
								rowheight*" . $PtsPerMM . " as label_rowheight,
								columnwidth*" . $PtsPerMM . " as label_columnwidth,
								topmargin*" . $PtsPerMM . " as label_topmargin,
								leftmargin*" . $PtsPerMM . " as label_leftmargin
						FROM labels
						WHERE labelid='" . $_POST['LabelID'] . "'");
	$LabelDimensions = DB_fetch_array($Result);

	$Result = DB_query("SELECT fieldvalue,
								vpos,
								hpos,
								fontsize,
								barcode
						FROM labelfields
						WHERE labelid = '" . $_POST['LabelID'] . "'");
	$LabelFields = array();
	$i = 0;
	while ($LabelFieldRow = DB_fetch_array($Result)) {
		if ($LabelFieldRow['fieldvalue'] == 'itemcode') {
			$LabelFields[$i]['FieldValue'] = 'stockid';
		} elseif ($LabelFieldRow['fieldvalue'] == 'itemdescription') {
			$LabelFields[$i]['FieldValue'] = 'description';
		} else {
			$LabelFields[$i]['FieldValue'] = $LabelFieldRow['fieldvalue'];
		}
		$LabelFields[$i]['VPos'] = $LabelFieldRow['vpos'] * $PtsPerMM;
		$LabelFields[$i]['HPos'] = $LabelFieldRow['hpos'] * $PtsPerMM;
		$LabelFields[$i]['FontSize'] = $LabelFieldRow['fontsize'];
		$LabelFields[$i]['Barcode'] = $LabelFieldRow['barcode'];
		$i++;
	}

	// Prepare HTML output for DomPDF
	$HTML = '<html>
	<head>
		<style>
			.label-table { border-collapse: separate; }
			.label-cell {
				border: 1px solid #000;
				width: ' . $LabelDimensions['label_width'] . 'pt;
				height: ' . $LabelDimensions['label_height'] . 'pt;
				padding: 0;
				vertical-align: top;
				text-align: left;
				overflow: hidden;
				position: relative;
			}
			.label-content {
				position: absolute;
				width: 100%;
				height: 100%;
				left: 0;
				top: 0;
			}
		</style>
	</head>
	<body>
		<table class="label-table">';
	$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';

	$TotalLabels = $NoOfLabels * $_POST['LabelsPerItem'];
	$LabelsPrinted = 0;
	$rowCount = 0;
	$colCount = 0;

	for ($i = 0; $i < $_POST['NoOfLabels']; $i++) {
		if (isset($_POST['PrintLabel' . $i])) {
			for ($LabelNumber = 0; $LabelNumber < $_POST['LabelsPerItem']; $LabelNumber++) {

				if ($colCount == 0) {
					$HTML .= '<tr>';
				}
				$HTML .= '<td class="label-cell"><div class="label-content" style="font-size:' . $LabelFields[0]['FontSize'] . 'pt;">';

				foreach ($LabelFields as $Field) {
					if ($Field['FieldValue'] == 'price') {
						$Value = $_POST['Price' . $i] . ' ' . $_POST['Currency'];
						$HTML .= '<div style="position:absolute;top:' . $Field['VPos'] . 'pt;left:' . $Field['HPos'] . 'pt;">' . htmlspecialchars($Value) . '</div>';
					} elseif ($Field['FieldValue'] == 'stockid') {
						$Value = $_POST['StockID' . $i];
						$HTML .= '<div style="position:absolute;top:' . $Field['VPos'] . 'pt;left:' . $Field['HPos'] . 'pt;">' . htmlspecialchars($Value) . '</div>';
					} elseif ($Field['FieldValue'] == 'description') {
						$Value = $_POST['Description' . $i];
						$HTML .= '<div style="position:absolute;top:' . $Field['VPos'] . 'pt;left:' . $Field['HPos'] . 'pt;">' . htmlspecialchars($Value) . '</div>';
					} elseif ($Field['FieldValue'] == 'barcode') {
						$Value = $_POST['Barcode' . $i];
						if ($Field['Barcode'] == 1 && !empty($Value)) {
							// Generate barcode using an external library and embed as an image
							// For demonstration, just output barcode value as text
							$HTML .= '<div style="position:absolute;top:' . $Field['VPos'] . 'pt;left:' . $Field['HPos'] . 'pt;"><span style="font-family:monospace;">' . htmlspecialchars($Value) . '</span></div>';
						}
					} elseif ($Field['FieldValue'] == 'logo') {
						if (!empty($_SESSION['LogoFile'])) {
							$LogoPath = $_SESSION['LogoFile'];
							$HTML .= '<img src="' . $LogoPath . '" style="position:absolute;top:' . $Field['VPos'] . 'pt;left:' . $Field['HPos'] . 'pt;max-height:' . $Field['FontSize'] . 'pt;" />';
						}
					}
				}

				$HTML .= '</div></td>';
				$colCount++;
				$LabelsPrinted++;

				if ($colCount >= floor($LabelDimensions['page_width'] / $LabelDimensions['label_columnwidth'])) {
					$HTML .= '</tr>';
					$rowCount++;
					$colCount = 0;
				}
			}
		}
	}

	// Close last row if needed
	if ($colCount > 0) {
		$HTML .= '</tr>';
	}
	$HTML .= '</table>
	</body>
	</html>';

	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
	$DomPDF->render();

	$FileName = $_SESSION['DatabaseName'] . '_' . __('Price_Labels') . '_' . date('Y-m-d') . '.pdf';
	// Output the PDF inline to the browser
	header('Content-Type: application/pdf');
	header('Content-Disposition: inline; filename="' . $FileName . '"');
	echo $DomPDF->output();
	exit();

} else { /*The option to print PDF was not hit */

	// INITIAL SETUP PAGE (No category selected yet)
	$Title = __('Price Labels');
	$ViewTopic = 'Inventory';
	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-bottom-layout">';

	// SIDEBAR (Empty in initial state)
	echo '<aside class="db-col-aside">
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Getting Started') . '</h3>
				</div>
				<div class="db-card-body">
					<p style="font-size: 0.8rem; color: var(--text-muted);">
						' . __('Select your label template and stock category in the main form to begin.') . '
					</p>
				</div>
			</div>
		  </aside>';

	echo '<main class="db-col-main">';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-tag"></i> ' . __('Label Printing Wizard') . '</h3>
				</div>
				<div class="db-card-body">
					<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
						<div class="db-form-group">
							<label class="db-label">' . __('Template to use') . '</label>
							<select required="required" autofocus="autofocus" name="LabelID" class="db-select">';
	$LRes = DB_query("SELECT labelid, description FROM labels");
	while ($LRow = DB_fetch_array($LRes)) {
		echo '<option value="' . $LRow['labelid'] . '">' . $LRow['description'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Stock Category') . '</label>
							<select name="StockCategory" class="db-select">';
	$CRes = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
	while ($CRow = DB_fetch_array($CRes)) {
		echo '<option value="' . $CRow['categoryid'] . '">' . $CRow['categorydescription'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Sales Type') . '</label>
							<select name="SalesType" class="db-select">';
	$STRes = DB_query("SELECT sales_type, typeabbrev FROM salestypes");
	while ($STRow = DB_fetch_array($STRes)) {
		echo '<option ' . ($_SESSION['DefaultPriceList'] == $STRow['typeabbrev'] ? 'selected="selected"' : '') . ' value="' . $STRow['typeabbrev'] . '">' . $STRow['sales_type'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Currency') . '</label>
							<select name="Currency" class="db-select">';
	$CurRes = DB_query("SELECT currabrev, country, currency FROM currencies");
	while ($CurRow = DB_fetch_array($CurRes)) {
		echo '<option ' . ($_SESSION['CompanyRecord']['currencydefault'] == $CurRow['currabrev'] ? 'selected="selected"' : '') . ' value="' . $CurRow['currabrev'] . '">' . $CurRow['country'] . ' - ' . $CurRow['currency'] . '</option>';
	}
	echo '					</select>
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Effective Date') . '</label>
							<input type="date" name="EffectiveDate" class="db-input" value="' . date('Y-m-d') . '" />
						</div>

						<div class="db-form-group">
							<label class="db-label">' . __('Labels per item') . '</label>
							<input type="number" name="LabelsPerItem" class="db-input" value="1" min="1" />
						</div>
					</div>

					<button type="submit" name="ShowLabels" class="db-btn db-btn-primary" style="width: 100%; margin-top: 25px;">
						<i class="fas fa-search-plus"></i> ' . __('Search Specified Category') . '
					</button>
				</div>
			</div>
			</form>';

	echo '	</main>
		</div>'; // end db-bottom-layout

	include(__DIR__ . '/includes/footer.php');
}
