<?php

/*
Shows a report of purchases from suppliers for the range of selected dates.
This program is under the GNU General Public License, last version. 2016-12-18.
This creative work is under the CC BY-NC-SA, last version. 2016-12-18.

This script is "mirror-symmetric" to script SalesReport.php.
*/

require(__DIR__ . '/includes/session.php');

$Title = __('Purchases from Suppliers');
$ViewTopic = 'PurchaseOrdering';
$BookMark = 'PurchasesReport';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Analyze supplier purchases across selected periods') . '</p>
			</div>
		</div>';

if (isset($_POST['PeriodFrom'])){$_POST['PeriodFrom'] = ConvertSQLDate($_POST['PeriodFrom']);}
if (isset($_POST['PeriodTo'])){$_POST['PeriodTo'] = ConvertSQLDate($_POST['PeriodTo']);}

// Merges gets into posts:
if (isset($_GET['PeriodFrom'])) {
	$_POST['PeriodFrom'] = $_GET['PeriodFrom'];
}
if (isset($_GET['PeriodTo'])) {
	$_POST['PeriodTo'] = $_GET['PeriodTo'];
}
if (isset($_GET['ShowDetails'])) {
	$_POST['ShowDetails'] = $_GET['ShowDetails'];
}

// Validates the data submitted in the form:
if (isset($_POST['PeriodFrom']) AND isset($_POST['PeriodTo'])) {
	if (Date1GreaterThanDate2($_POST['PeriodFrom'], $_POST['PeriodTo'])) {
		// The beginning is after the end.
		$_POST['NewReport'] = 'on';
		prnMsg(__('The beginning of the period should be before or equal to the end of the period. Please reselect the reporting period.'), 'error');
	}
}

// Main code:
if (isset($_POST['PeriodFrom']) AND isset($_POST['PeriodTo']) AND !$_POST['NewReport']) {
	// If PeriodFrom and PeriodTo are set and it is not a NewReport, generates the report:
	echo '<div class="db-card">
			<div class="db-card-title">' . __('Report Results') . ' (' . $_POST['PeriodFrom'] . ' ' . __('to') . ' ' . $_POST['PeriodTo'] . ')</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>';
	// $CommonHead is the common table head between ShowDetails=off and ShowDetails=on:
	$CommonHead =
				'<th>' . __('Orig. Amount') . '</th>' .
				'<th>' . __('Orig. Taxes') . '</th>' .
				'<th>' . __('Orig. Total') . '</th>' .
				'<th>' . __('GL Amount') . '</th>' .
				'<th>' . __('GL Taxes') . '</th>' .
				'<th>' . __('GL Total') . '</th>' .
			'</tr>' .
		'</thead><tbody>';
	$TotalGlAmount = 0;
	$TotalGlTax = 0;
	$PeriodFrom = FormatDateForSQL($_POST['PeriodFrom']);
	$PeriodTo = FormatDateForSQL($_POST['PeriodTo']);
	if ($_POST['ShowDetails']) {// Parameters: PeriodFrom, PeriodTo, ShowDetails=on.
		echo		'<th>', __('Date'), '</th>',
					'<th>', __('Purchase Invoice'), '</th>',
					'<th>', __('Reference'), '</th>',
					$CommonHead;
		// Includes $CurrencyName array with currency three-letter alphabetic code and name based on ISO 4217:
		include(__DIR__ . '/includes/CurrenciesArray.php');
		$SupplierId = '';
		$SupplierOvAmount = 0;
		$SupplierOvTax = 0;
		$SupplierGlAmount = 0;
		$SupplierGlTax = 0;
		$SQL = "SELECT
					supptrans.supplierno,
					suppliers.suppname,
					suppliers.currcode,
					supptrans.trandate,
					supptrans.suppreference,
					supptrans.transno,
					supptrans.ovamount,
					supptrans.ovgst,
					supptrans.rate
				FROM supptrans
					INNER JOIN suppliers ON supptrans.supplierno=suppliers.supplierid
				WHERE supptrans.trandate>='" . $PeriodFrom . "'
					AND supptrans.trandate<='" . $PeriodTo . "'
					AND supptrans.`type`=20
				ORDER BY supptrans.supplierno, supptrans.trandate";
		$Result = DB_query($SQL);
		foreach($Result as $MyRow) {
			if ($MyRow['supplierno'] != $SupplierId) {// If different, prints supplier totals:
				if ($SupplierId != '') {// If NOT the first line.
					echo '<tr>',
							'<td colspan="3">&nbsp;</td>',
							'<td class="number">', locale_number_format($SupplierOvAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
							'<td class="number">', locale_number_format($SupplierOvTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
							'<td class="number">', locale_number_format($SupplierOvAmount+$SupplierOvTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
							'<td class="number">', locale_number_format($SupplierGlAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
							'<td class="number">', locale_number_format($SupplierGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
							'<td class="number">', locale_number_format($SupplierGlAmount+$SupplierGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
						'</tr>';
				}
				echo '<tr><td colspan="9">&nbsp;</td></tr>';
				echo '<tr><td class="text" colspan="9"><a href="', $RootPath, '/SupplierInquiry.php?SupplierID=', $MyRow['supplierno'], '">', $MyRow['supplierno'], ' - ', $MyRow['suppname'], '</a> - ', $MyRow['currcode'], ' ', $CurrencyName[$MyRow['currcode']], '</td></tr>';
				$TotalGlAmount += $SupplierGlAmount;
				$TotalGlTax += $SupplierGlTax;
				$SupplierId = $MyRow['supplierno'];
				$SupplierOvAmount = 0;
				$SupplierOvTax = 0;
				$SupplierGlAmount = 0;
				$SupplierGlTax = 0;
			}

			$GlAmount = $MyRow['ovamount']/$MyRow['rate'];
			$GlTax = $MyRow['ovgst']/$MyRow['rate'];
			echo '<tr class="striped_row">
					<td class="centre">', $MyRow['trandate'], '</td>',
					'<td class="number">', $MyRow['transno'], '</td>',
					'<td class="text">', $MyRow['suppreference'], '</td>',
					'<td class="number">', locale_number_format($MyRow['ovamount'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['ovgst'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number"><a href="', $RootPath, '/SuppWhereAlloc.php?TransType=20&TransNo=', $MyRow['transno'], '&amp;ScriptFrom=PurchasesReport" target="_blank" title="', __('Click to view where allocated'), '">', locale_number_format($MyRow['ovamount']+$MyRow['ovgst'], $_SESSION['CompanyRecord']['decimalplaces']), '</a></td>',
					'<td class="number">', locale_number_format($GlAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">',	locale_number_format($GlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number"><a href="', $RootPath, '/GLTransInquiry.php?TypeID=20&amp;TransNo=', $MyRow['transno'], '&amp;ScriptFrom=PurchasesReport" target="_blank" title="', __('Click to view the GL entries'), '">', locale_number_format($GlAmount+$GlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</a></td>', // RChacon: Should be "Click to view the General Ledger transaction" instead?
				'</tr>';
			$SupplierOvAmount += $MyRow['ovamount'];
			$SupplierOvTax += $MyRow['ovgst'];
			$SupplierGlAmount += $GlAmount;
			$SupplierGlTax += $GlTax;
		}

		// Prints last supplier total:
		echo '<tr>',
				'<td colspan="3">&nbsp;</td>',
				'<td class="number">', locale_number_format($SupplierOvAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'<td class="number">', locale_number_format($SupplierOvTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'<td class="number">', locale_number_format($SupplierOvAmount+$SupplierOvTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'<td class="number">', locale_number_format($SupplierGlAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'<td class="number">', locale_number_format($SupplierGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'<td class="number">', locale_number_format($SupplierGlAmount+$SupplierGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
			'</tr>',
			'<tr><td colspan="9">&nbsp;</td></tr>';

		$TotalGlAmount += $SupplierGlAmount;
		$TotalGlTax += $SupplierGlTax;

	} else {// Parameters: PeriodFrom, PeriodTo, ShowDetails=off.
		// RChacon: Needs to update the table_sort function to use in this table.
		echo		'<th>', __('Supplier Code'), '</th>',
					'<th>', __('Supplier Name'), '</th>',
					'<th>', __('Supplier\'s Currency'), '</th>',
					$CommonHead;
		$SQL = "SELECT
					supptrans.supplierno,
					suppliers.suppname,
					suppliers.currcode,
					SUM(supptrans.ovamount) AS SupplierOvAmount,
					SUM(supptrans.ovgst) AS SupplierOvTax,
					SUM(supptrans.ovamount/supptrans.rate) AS SupplierGlAmount,
					SUM(supptrans.ovgst/supptrans.rate) AS SupplierGlTax
				FROM supptrans
					INNER JOIN suppliers ON supptrans.supplierno=suppliers.supplierid
				WHERE supptrans.trandate>='" . $PeriodFrom . "'
					AND supptrans.trandate<='" . $PeriodTo . "'
					AND supptrans.`type`=20
				GROUP BY
					supptrans.supplierno
				ORDER BY supptrans.supplierno, supptrans.trandate";
		$Result = DB_query($SQL);
		foreach($Result as $MyRow) {
			echo '<tr class="striped_row">',
					'<td class="text">', $MyRow['supplierno'], '</td>',
					'<td class="text"><a href="', $RootPath, '/SupplierInquiry.php?SupplierID=', $MyRow['supplierno'], '">', $MyRow['suppname'], '</a></td>',
					'<td class="text">', $MyRow['currcode'], '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierOvAmount'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierOvTax'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierOvAmount']+$MyRow['SupplierOvTax'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierGlAmount'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierGlTax'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
					'<td class="number">', locale_number_format($MyRow['SupplierGlAmount']+$MyRow['SupplierGlTax'], $_SESSION['CompanyRecord']['decimalplaces']), '</td>',
				'</tr>';
			$TotalGlAmount += $MyRow['SupplierGlAmount'];
			$TotalGlTax += $MyRow['SupplierGlTax'];
		}
	}
	// Prints all suppliers total:
	echo	'<tr class="db-font-bold" style="background: var(--bg-workspace);">
				<td class="text" colspan="' . ($_POST['ShowDetails'] ? '6' : '6') . '"><div class="text-right">' . __('GRAND TOTAL') . '</div></td>
				<td class="text-right">', locale_number_format($TotalGlAmount, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="text-right">', locale_number_format($TotalGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
				<td class="text-right">', locale_number_format($TotalGlAmount+$TotalGlTax, $_SESSION['CompanyRecord']['decimalplaces']), '</td>
			</tr>',
		'</tbody></table></div>
		<div class="db-alert db-alert-info" style="margin-top: var(--space-4);">
			<strong>' . __('Notes') . ':</strong> ' . __('Original amounts in the supplier\'s currency. GL amounts in the functional currency.') . '
		</div>
		</div>';
	// Shows a form to select an action after the report was shown:
		echo '<div class="db-card-footer db-form-actions" style="margin-top: var(--space-4);">
				<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post" style="display: contents;">
					<input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />
					<input name="PeriodFrom" type="hidden" value="', $_POST['PeriodFrom'], '" />
					<input name="PeriodTo" type="hidden" value="', $_POST['PeriodTo'], '" />
					<input name="ShowDetails" type="hidden" value="', $_POST['ShowDetails'], '" />
					<button class="db-btn db-btn-secondary" onclick="window.print()" type="button">' . __('Print Report') . '</button>
					<button class="db-btn db-btn-primary" name="NewReport" type="submit" value="on">' . __('New Report') . '</button>
					<a href="' . $RootPath . '/index.php?Application=PO" class="db-btn db-btn-secondary">' . __('Return') . '</a>
				</form>
			</div>
		</div>';
} else {
	echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">
			<input name="FormID" type="hidden" value="', $_SESSION['FormID'] . '" />
			<div class="db-card">
				<div class="db-card-title">' . __('Report Criteria') . '</div>
				<div class="db-card-body">
					<div class="db-grid db-grid-3">
						<div class="db-form-group">
							<label class="db-form-label" for="PeriodFrom">' . __('Period from') . ':</label>
							<input type="date" id="PeriodFrom" name="PeriodFrom" class="db-form-input" required="required" value="' . FormatDateForSQL($_POST['PeriodFrom']) . '" />
						</div>
						<div class="db-form-group">
							<label class="db-form-label" for="PeriodTo">' . __('Period to') . ':</label>
							<input type="date" id="PeriodTo" name="PeriodTo" class="db-form-input" required="required" value="' . FormatDateForSQL($_POST['PeriodTo']) . '" />
						</div>
						<div class="db-form-group" style="display: flex; align-items: flex-end; padding-bottom: 5px;">
							<label class="db-checkbox-container">
								<input' . (isset($_POST['ShowDetails']) && $_POST['ShowDetails'] ? ' checked="checked"' : '') . ' id="ShowDetails" name="ShowDetails" type="checkbox" />
								<span class="db-checkbox-label">' . __('Show Transaction Details') . '</span>
							</label>
						</div>
					</div>
				</div>
				<div class="db-card-footer db-form-actions">
					<button name="Submit" type="submit" value="submit" class="db-btn db-btn-primary">' . __('Generate Report') . '</button>
					<a href="' . $RootPath . '/index.php?Application=PO" class="db-btn db-btn-secondary">' . __('Return') . '</a>
				</div>
			</div>
		</form>';
}
echo '</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
// END Procedure division ======================================================
