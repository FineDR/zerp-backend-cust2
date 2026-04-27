<?php

// Produces a csv, html or pdf report of the fixed assets over a period showing period depreciation, additions and disposals.

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['FromDate'])) {
	$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);
}
if (isset($_POST['ToDate'])) {
	$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);
}

// Reports being generated in HTML, PDF and CSV/EXCEL format
if (isset($_POST['submit']) or isset($_POST['PrintPDF']) or isset($_POST['Spreadsheet'])) {

	$DisposalSQL = '';
	if ($_POST['DisposalStatus'] == 'ALL') {
		$DisposalSQL .= " AND (fixedassets.disposaldate = '1000-01-01'
								OR fixedassets.disposaldate >='" . $DateFrom . "')";
	}
	elseif ($_POST['DisposalStatus'] == 'ACTIVE') {
		$DisposalSQL .= ' AND disposaldate = "1000-01-01"';
	}
	else {
		$DisposalSQL .= ' AND disposaldate != "1000-01-01"';
	}

	$DateFrom = FormatDateForSQL($_POST['FromDate']);
	$DateTo = FormatDateForSQL($_POST['ToDate']);
	$SQL = "SELECT fixedassets.assetid,
					fixedassets.description,
					fixedassets.longdescription,
					fixedassets.assetcategoryid,
					fixedassets.serialno,
					fixedassetlocations.locationdescription,
					fixedassets.datepurchased,
					fixedassetlocations.parentlocationid,
					fixedassets.assetlocation,
					fixedassets.disposaldate,
					SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS costbfwd,
					SUM(CASE WHEN (fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='depn') THEN fixedassettrans.amount ELSE 0 END) AS depnbfwd,
					SUM(CASE WHEN (fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost') THEN fixedassettrans.amount ELSE 0 END) AS periodadditions,
					SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='depn' THEN fixedassettrans.amount ELSE 0 END) AS perioddepn,
					SUM(CASE WHEN fixedassettrans.transdate >='" . $DateFrom . "'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='disposal' THEN fixedassettrans.amount ELSE 0 END) AS perioddisposal
			FROM fixedassets
			INNER JOIN fixedassetcategories ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
			INNER JOIN fixedassetlocations ON fixedassets.assetlocation=fixedassetlocations.locationid
			INNER JOIN fixedassettrans ON fixedassets.assetid=fixedassettrans.assetid
			WHERE fixedassets.assetcategoryid " . LIKE . "'" . $_POST['AssetCategory'] . "'
			AND fixedassets.assetid " . LIKE . "'" . $_POST['AssetID'] . "'
			AND fixedassets.assetlocation " . LIKE . "'" . $_POST['AssetLocation'] . "'" . $DisposalSQL . "
			GROUP BY fixedassets.assetid,
					fixedassets.description,
					fixedassets.longdescription,
					fixedassets.assetcategoryid,
					fixedassets.serialno,
					fixedassetlocations.locationdescription,
					fixedassets.datepurchased,
					fixedassetlocations.parentlocationid,
					fixedassets.assetlocation";
	$Result = DB_query($SQL);

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>';
	
	if (!isset($_POST['PrintPDF']) && !isset($_POST['Spreadsheet'])) {
		$HTML .= '<div class="db-centered-container" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
					<div class="db-card" style="border: none; box-shadow: var(--shadow-lg);">';
	}

	$HTML .= '<div class="centre" id="ReportHeader" style="padding: 30px; border-bottom: 2px solid var(--border-soft);">
					<h2 style="margin: 0; color: var(--text-main);">' . $_SESSION['CompanyRecord']['coyname'] . '</h2>
					<div style="font-size: 0.9rem; color: var(--text-muted); margin-top: 5px;">
						' . __('From') . ': <span class="db-font-bold">' . $_POST['FromDate'] . '</span> ' . __('to') . ' <span class="db-font-bold">' . $_POST['ToDate'] . '</span><br />
						' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '
					</div>
				</div>
				<form id="RegisterForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	
	if (!isset($_POST['PrintPDF']) && !isset($_POST['Spreadsheet'])) {
		$HTML .= '<div class="db-table-wrap" style="overflow-x: auto;">';
	}

	$HTML .= '<table class="db-table monochromatic-table">
					<thead>
						<tr>
							<th style="padding-left: 20px;">' . __('Asset ID') . '</th>
							<th>' . __('Description') . '</th>
							<th>' . __('Serial') . '</th>
							<th>' . __('Location') . '</th>
							<th>' . __('Acquired') . '</th>
							<th class="number">' . __('Cost B/fwd') . '</th>
							<th class="number">' . __('Depn B/fwd') . '</th>
							<th class="number">' . __('Additions') . '</th>
							<th class="number">' . __('Depn') . '</th>
							<th class="number">' . __('Cost C/fwd') . '</th>
							<th class="number">' . __('Depn C/fwd') . '</th>
							<th class="number">' . __('NBV') . '</th>
							<th class="number">' . __('Disposal') . '</th>
							<th style="padding-right: 20px;">' . __('Disp. Date') . '</th>
						</tr>
					</thead>
					<tbody>';

	$TotalCostBfwd = 0;
	$TotalCostCfwd = 0;
	$TotalDepnBfwd = 0;
	$TotalDepnCfwd = 0;
	$TotalAdditions = 0;
	$TotalDepn = 0;
	$TotalDisposals = 0;
	$TotalNBV = 0;

	while ($MyRow = DB_fetch_array($Result)) {

		if (Date1GreaterThanDate2(ConvertSQLDate($MyRow['disposaldate']) , $_POST['FromDate']) or $MyRow['disposaldate'] = '1000-01-01') {

			if ($MyRow['disposaldate'] != '1000-01-01' and Date1GreaterThanDate2($_POST['ToDate'], ConvertSQLDate($MyRow['disposaldate']))) {
				/*The asset was disposed during the period */
				$CostCfwd = 0;
				$AccumDepnCfwd = 0;
			}
			else {
				$CostCfwd = $MyRow['periodadditions'] + $MyRow['costbfwd'];
				$AccumDepnCfwd = $MyRow['perioddepn'] + $MyRow['depnbfwd'];
			}
			if ($MyRow['disposaldate'] == '1000-01-01') {
				$DisposalDate = "";
			}
			else {
				$DisposalDate = $MyRow['disposaldate'];
			}
			$HTML .= '<tr>
						<td style="padding-left: 20px;" class="db-font-bold">' . $MyRow['assetid'] . '</td>
						<td style="min-width: 200px;">' . $MyRow['longdescription'] . '</td>
						<td>' . $MyRow['serialno'] . '</td>
						<td>' . $MyRow['locationdescription'] . '</td>
						<td>' . ConvertSQLDate($MyRow['datepurchased']) . '</td>
						<td class="number">' . locale_number_format($MyRow['costbfwd'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['depnbfwd'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['periodadditions'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['perioddepn'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($CostCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number db-font-bold">' . locale_number_format($CostCfwd - $AccumDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['perioddisposal'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td style="padding-right: 20px;">' . ($MyRow['disposaldate'] == '1000-01-01' ? '' : ConvertSQLDate($MyRow['disposaldate'])) . '</td>
					</tr>';
		} // end of if the asset was either not disposed yet or disposed after the start date
		$TotalCostBfwd += $MyRow['costbfwd'];
		$TotalCostCfwd += ($MyRow['costbfwd'] + $MyRow['periodadditions']);
		$TotalDepnBfwd += $MyRow['depnbfwd'];
		$TotalDepnCfwd += ($MyRow['depnbfwd'] + $MyRow['perioddepn']);
		$TotalAdditions += $MyRow['periodadditions'];
		$TotalDepn += $MyRow['perioddepn'];
		$TotalDisposals += $MyRow['perioddisposal'];

		$TotalNBV += ($CostCfwd - $AccumDepnCfwd);
	}

	//Total Values
	$HTML .= '</tbody><tfoot>
				<tr style="background: var(--surface-alt); font-weight: 700;">
					<th colspan="5" style="padding-left: 20px;">' . __('TOTAL') . '</th>
					<th class="number">' . locale_number_format($TotalCostBfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalDepnBfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalAdditions, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalDepn, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalCostCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalDepnCfwd, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalNBV, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalDisposals, $_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th></th>
				</tr>
			</tfoot>';
	$HTML .= '</table>';
	
	if (!isset($_POST['PrintPDF']) && !isset($_POST['Spreadsheet'])) {
		$HTML .= '</div>'; // End table-wrap
	}

	$HTML .= '<input type="hidden" name="FromDate" value="' . $_POST['FromDate'] . '" />';
	$HTML .= '<input type="hidden" name="ToDate" value="' . $_POST['ToDate'] . '" />';
	$HTML .= '<input type="hidden" name="AssetCategory" value="' . $_POST['AssetCategory'] . '" />';
	$HTML .= '<input type="hidden" name="AssetID" value="' . $_POST['AssetID'] . '" />';
	$HTML .= '<input type="hidden" name="AssetLocation" value="' . $_POST['AssetLocation'] . '" />';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>';
	}
	else {
		$HTML .= '<div class="db-card-footer" style="padding: 20px; display: flex; justify-content: center;">
					<button type="submit" name="close" class="db-btn db-btn-secondary" onclick="window.close()">
						<i class="fas fa-times"></i> ' . __('Close Report') . '
					</button>
				  </div>
				  </form>';
	}
	
	if (!isset($_POST['PrintPDF']) && !isset($_POST['Spreadsheet'])) {
		$HTML .= '</div></div>'; // End card, centered-container
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_FixedAssetRegister_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	}
	elseif (isset($_POST['Spreadsheet'])) {
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

		$File = 'FixedAssetRegister-' . date('Y-m-d') . '.' . 'ods';

		header('Content-Disposition: attachment;filename="' . $File . '"');
		header('Cache-Control: max-age=0');
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
		$spreadsheet = $reader->loadFromString($HTML);

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Ods');
		$writer->save('php://output');
	}
	else {
		$Title = __("Fixed Asset Register");
		include(__DIR__ . "/includes/header.php");
	echo '<div class="db-page">
				<div class="db-page-header">
					<div class="db-header-left">
						<div class="db-page-title"><i class="fas fa-file-invoice"></i> ' . $Title . '</div>
						<div class="db-page-subtitle">' . __('Detailed financial overview of your asset portfolio') . '</div>
					</div>
				</div>';
		echo $HTML;
		echo '<style>
		.monochromatic-table th { background: transparent !important; color: var(--text-main) !important; border-bottom: 2px solid var(--border) !important; }
		.monochromatic-table tr:hover td { background: transparent !important; }
		.monochromatic-table td { border-bottom: 1px solid var(--border-soft); }
		
		@media (max-width: 768px) {
			.db-page-header { padding: 15px !important; }
			.db-page-title { font-size: 1.25rem !important; }
			.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
			.db-table-wrap { overflow-x: auto !important; margin: 0 -20px; width: calc(100% + 40px); -webkit-overflow-scrolling: touch; }
			.monochromatic-table { min-width: 1200px !important; width: 1200px !important; }
			.db-card-footer { flex-direction: column !important; padding: 20px !important; gap: 10px !important; }
			.db-btn { width: 100% !important; display: flex !important; justify-content: center !important; }
		}
		</style>';
		include(__DIR__ . '/includes/footer.php');
	}
} else {
	$Title = __('Fixed Asset Register');

	$ViewTopic = 'FixedAssets';
	$BookMark = 'AssetRegister';

	include(__DIR__ . '/includes/header.php');
	
	echo '<style>
	@media (max-width: 768px) {
		.db-page-header { padding: 15px !important; }
		.db-page-title { font-size: 1.25rem !important; }
		.db-page-subtitle { white-space: normal !important; overflow: visible !important; font-size: 0.8rem !important; }
		.db-table-wrap { overflow-x: auto !important; margin: 0 -20px; width: calc(100% + 40px); -webkit-overflow-scrolling: touch; }
		.db-card-footer { flex-direction: column !important; padding: 20px !important; gap: 10px !important; }
		.db-btn { width: 100% !important; justify-content: center !important; }
	}
	</style>';

	echo '<div class="db-page">
			<div class="db-page-header">
				<div class="db-header-left">
					<div class="db-page-title"><i class="fas fa-file-invoice"></i> ' . $Title . '</div>
					<div class="db-page-subtitle">' . __('Generate a report of your assets') . '</div>
				</div>
			</div>';

	$Result = DB_query('SELECT categoryid,categorydescription FROM fixedassetcategories');
	echo '<form id="RegisterForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<div class="db-centered-container" style="max-width: 900px; margin: 0 auto; padding: 0 20px;">
				<div class="db-card" style="border: none; box-shadow: var(--shadow-lg);">
					<div class="db-card-header">
						<div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Report Criteria') . '</div>
					</div>
					<div class="db-card-body" style="padding: 30px;">
						<div class="db-grid db-grid-2 db-grid-mobile-stack">
							<div class="db-form-group">
								<label class="db-label">' . __('Asset Category') . '</label>
								<select name="AssetCategory" class="db-select">
									<option value="%">' . __('ALL') . '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetCategory']) and $MyRow['categoryid'] == $_POST['AssetCategory']) {
			echo '<option selected="selected" value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
		else {
			echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
	}
	echo '				</select>
							</div>';
	
	$SQL = "SELECT locationid, locationdescription FROM fixedassetlocations";
	$Result = DB_query($SQL);
	echo '					<div class="db-form-group">
								<label class="db-label">' . __('Asset Location') . '</label>
								<select name="AssetLocation" class="db-select">
									<option value="%">' . __('ALL') . '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetLocation']) and $MyRow['locationid'] == $_POST['AssetLocation']) {
			echo '<option selected="selected" value="' . $MyRow['locationid'] . '">' . $MyRow['locationdescription'] . '</option>';
		}
		else {
			echo '<option value="' . $MyRow['locationid'] . '">' . $MyRow['locationdescription'] . '</option>';
		}
	}
	echo '				</select>
							</div>';
	
	$SQL = "SELECT assetid, description FROM fixedassets";
	$Result = DB_query($SQL);
	echo '					<div class="db-form-group">
								<label class="db-label">' . __('Specific Asset') . '</label>
								<select name="AssetID" class="db-select">
									<option value="%">' . __('ALL') . '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		if (isset($_POST['AssetID']) and $MyRow['assetid'] == $_POST['AssetID']) {
			echo '<option selected="selected" value="' . $MyRow['assetid'] . '">' . $MyRow['assetid'] . ' - ' . $MyRow['description'] . '</option>';
		}
		else {
			echo '<option value="' . $MyRow['assetid'] . '">' . $MyRow['assetid'] . ' - ' . $MyRow['description'] . '</option>';
		}
	}
	echo '				</select>
							</div>';

	if (!isset($_POST['DisposalStatus'])) {
		$_POST['DisposalStatus'] = "ACTIVE";
	}

	echo '					<div class="db-form-group">
								<label class="db-label">' . __('Disposal Status') . '</label>
								<select name="DisposalStatus" class="db-select">';

	if ($_POST['DisposalStatus'] == 'ALL') {
		echo '	<option selected="selected" value="ALL">' . __('All') . '</option>
				<option value="ACTIVE">' . __('Active') . '</option>
				<option value="DISPOSED">' . __('Disposed') . '</option>';
	}
	elseif ($_POST['DisposalStatus'] == 'ACTIVE') {
		echo '	<option value="ALL">' . __('All') . '</option>
				<option selected="selected" value="ACTIVE">' . __('Active') . '</option>
				<option value="DISPOSED">' . __('Disposed') . '</option>';
	}
	else {
		echo '	<option value="ALL">' . __('All') . '</option>
				<option value="ACTIVE">' . __('Active') . '</option>
				<option selected="selected" value="DISPOSED">' . __('Disposed') . '</option>';
	}

	echo '				</select>
							</div>';

	if (empty($_POST['FromDate'])) {
		$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m') , date('d') , date('Y') - 1));
	}
	if (empty($_POST['ToDate'])) {
		$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
	}

	echo '					<div class="db-form-group">
								<label class="db-label">' . __('From Date') . '</label>
								<input type="date" name="FromDate" required="required" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
								<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">' . __('Start date for cost/depn analysis') . '</div>
							</div>
							<div class="db-form-group">
								<label class="db-label">' . __('To Date') . '</label>
								<input type="date" name="ToDate" required="required" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
								<div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">' . __('End date for cost/depn analysis') . '</div>
							</div>
						</div>
					</div>
					<div class="db-card-footer" style="padding: 25px; display: flex; justify-content: center; gap: 15px; background: var(--surface-alt);">
						<button type="submit" name="submit" class="db-btn db-btn-primary" style="padding: 12px 25px;">
							<i class="fas fa-search"></i> ' . __('Show Assets') . '
						</button>
						<button type="submit" name="PrintPDF" class="db-btn db-btn-secondary" style="padding: 12px 25px;">
							<i class="fas fa-file-pdf"></i> ' . __('Print PDF') . '
						</button>
						<button type="submit" name="Spreadsheet" class="db-btn db-btn-secondary" style="padding: 12px 25px;">
							<i class="fas fa-file-excel"></i> ' . __('Spreadsheet') . '
						</button>
					</div>
				</div>
			</div>
		</form>
	  </div>';

	include(__DIR__ . '/includes/footer.php');
}
?>
