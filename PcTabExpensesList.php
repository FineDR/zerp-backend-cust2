<?php

require(__DIR__ . '/includes/session.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-hover: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-main) !important; color: var(--text-main); font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color);
        margin: -30px -30px 30px -30px; padding: 20px 30px; position: sticky; top: 0; z-index: 1000;
    }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 25px; }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); background: #fcfcfd; display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.95rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }
    
    /* Forms */
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 1rem; transition: all 0.2s; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }

    .btn-architect { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }

    /* Responsive Scaling - Forced Overrides */
    @media (max-width: 767px) {
        .db-page { padding: 15px !important; margin-left: 0 !important; width: 100% !important; overflow-x: hidden !important; }
        .premium-header { margin: -15px -15px 20px -15px !important; padding: 15px !important; width: calc(100% + 30px) !important; border-radius: 0 !important; }
        .page-title { font-size: 1.4rem !important; }
        .db-card-body { padding: 15px !important; }
        .responsive-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
        .btn-architect { width: 100% !important; margin-bottom: 8px !important; }
    }
</style>';

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (isset($_POST['submit'])) {

	$TabToShow= $_POST['Tabs'];
	$FromDate = $_POST['FromDate'];
	$ToDate = $_POST['ToDate'];

	//initialise no input errors
	$InputError = 0;

	//first off validate inputs sensible

	if ($InputError == 0){
		// Search absic PC Tab information
		$SQL = "SELECT pctabs.tabcode,
					   pctabs.usercode,
					   pctabs.typetabcode,
					   pctabs.currency,
					   pctabs.tablimit,
					   pctabs.assigner,
					   pctabs.authorizer,
					   pctabs.authorizerexpenses
				FROM  pctabs
				WHERE pctabs.tabcode = '" . $TabToShow . "'";
		$Result = DB_query($SQL);
		$MyTab = DB_fetch_array($Result);

		$SQL = "SELECT decimalplaces FROM currencies WHERE currabrev='" . $MyTab['currency'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$CurrDecimalPlaces = $MyRow['decimalplaces'];

		$SQL = "SELECT SUM(pcashdetails.amount) AS previous
				FROM  pcashdetails
				WHERE pcashdetails.tabcode = '" . $TabToShow . "'
					AND pcashdetails.date < '" . FormatDateForSQL($FromDate) . "'";
		$Result = DB_query($SQL);
		$MyPreviousBalance = DB_fetch_array($Result);

		$SQL = "SELECT counterindex,
						tabcode,
						date,
						codeexpense,
						amount,
						authorized,
						posted,
						purpose,
						notes,
						receipt
				FROM  pcashdetails
				WHERE pcashdetails.tabcode = '" . $TabToShow . "'
					AND pcashdetails.date >= '" . FormatDateForSQL($FromDate) . "'
					AND pcashdetails.date <= '" . FormatDateForSQL($ToDate) . "'
				ORDER BY pcashdetails.date,
					pcashdetails.counterindex";
		$Result = DB_query($SQL);

		if (DB_num_rows($Result) !=  0){

			// Create new PHPExcel object
			$SpreadSheet = new Spreadsheet();

			// Set document properties
			$SpreadSheet->getProperties()->setCreator("webERP")
										 ->setLastModifiedBy("webERP")
										 ->setTitle("PC Tab Expenses List")
										 ->setSubject("PC Tab Expenses List")
										 ->setDescription("PC Tab Expenses List")
										 ->setKeywords("")
										 ->setCategory("");

			// Formatting

			$SpreadSheet->getActiveSheet()->getStyle('A')->getAlignment()->setWrapText(true);
			$SpreadSheet->getActiveSheet()->getStyle('A')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
			$SpreadSheet->getActiveSheet()->getStyle('B5')->getNumberFormat()->setFormatCode('#,##0.00');
			$SpreadSheet->getActiveSheet()->getStyle('C:E')->getNumberFormat()->setFormatCode('#,##0.00');
			$SpreadSheet->getActiveSheet()->getStyle('E1:E2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
			$SpreadSheet->getActiveSheet()->getStyle('J')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
			$SpreadSheet->getActiveSheet()->getStyle('A:J')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
			$SpreadSheet->getActiveSheet()->getStyle('10')->getFont()->setBold(true);
			$SpreadSheet->getActiveSheet()->getStyle('A1:A8')->getFont()->setBold(true);
			$SpreadSheet->getActiveSheet()->getStyle('D1:D2')->getFont()->setBold(true);

			// Add title data
			$SpreadSheet->setActiveSheetIndex(0);
			$SpreadSheet->getActiveSheet()->setCellValue('A1', 'Tab Code');
			$SpreadSheet->getActiveSheet()->setCellValue('B1', $MyTab['tabcode']);
			$SpreadSheet->getActiveSheet()->setCellValue('A2', 'User Code');
			$SpreadSheet->getActiveSheet()->setCellValue('B2', $MyTab['usercode']);
			$SpreadSheet->getActiveSheet()->setCellValue('A3', 'Type of Tab');
			$SpreadSheet->getActiveSheet()->setCellValue('B3', $MyTab['typetabcode']);
			$SpreadSheet->getActiveSheet()->setCellValue('A4', 'Currency');
			$SpreadSheet->getActiveSheet()->setCellValue('B4', $MyTab['currency']);
			$SpreadSheet->getActiveSheet()->setCellValue('A5', 'Limit');
			$SpreadSheet->getActiveSheet()->setCellValue('B5', $MyTab['tablimit']);
			$SpreadSheet->getActiveSheet()->setCellValue('A6', 'Cash Assigner');
			$SpreadSheet->getActiveSheet()->setCellValue('B6', $MyTab['assigner']);
			$SpreadSheet->getActiveSheet()->setCellValue('A7', 'Authorizer - Cash');
			$SpreadSheet->getActiveSheet()->setCellValue('B7', $MyTab['authorizer']);
			$SpreadSheet->getActiveSheet()->setCellValue('A8', 'Authorizer - Expenses');
			$SpreadSheet->getActiveSheet()->setCellValue('B8', $MyTab['authorizerexpenses']);

			$SpreadSheet->getActiveSheet()->setCellValue('D1', 'From');
			$SpreadSheet->getActiveSheet()->setCellValue('E1', $FromDate);
			$SpreadSheet->getActiveSheet()->setCellValue('D2', 'To');
			$SpreadSheet->getActiveSheet()->setCellValue('E2', $ToDate);

			$SpreadSheet->getActiveSheet()->setCellValue('A10', 'Date');
			$SpreadSheet->getActiveSheet()->setCellValue('B10', 'Expense Code');
			$SpreadSheet->getActiveSheet()->setCellValue('C10', 'Gross Amount');
			$SpreadSheet->getActiveSheet()->setCellValue('D10', 'Balance');
			$SpreadSheet->getActiveSheet()->setCellValue('E10', 'Tax');
			$SpreadSheet->getActiveSheet()->setCellValue('F10', 'Tax Group');
			$SpreadSheet->getActiveSheet()->setCellValue('H10', 'Business Purpose');
			$SpreadSheet->getActiveSheet()->setCellValue('I10', 'Notes');
			$SpreadSheet->getActiveSheet()->setCellValue('J10', 'Receipt Attachment');
			$SpreadSheet->getActiveSheet()->setCellValue('K10', 'Date Authorized');

			$SpreadSheet->getActiveSheet()->setCellValue('B11', 'Previous Balance');
			$SpreadSheet->getActiveSheet()->setCellValue('D11', $MyPreviousBalance['previous']);

			// Add data
			$i = 12;
			while ($MyRow = DB_fetch_array($Result)) {

				$SQLDes = "SELECT description
							FROM pcexpenses
							WHERE codeexpense = '" . $MyRow['codeexpense'] . "'";
				$ResultDes = DB_query($SQLDes);
				$Description=DB_fetch_array($ResultDes);
				if (!isset($Description[0])) {
						$ExpenseCodeDes = 'ASSIGNCASH';
				} else {
						$ExpenseCodeDes = $MyRow['codeexpense'] . ' - ' . $Description[0];
				}

				$TaxesDescription = '';
				$TaxesTaxAmount = '';
				$TaxSQL = "SELECT counterindex,
									pccashdetail,
									calculationorder,
									description,
									taxauthid,
									purchtaxglaccount,
									taxontax,
									taxrate,
									amount
								FROM pcashdetailtaxes
								WHERE pccashdetail='" . $MyRow['counterindex'] . "'";
				$TaxResult = DB_query($TaxSQL);
				while ($MyTaxRow = DB_fetch_array($TaxResult)) {
					$TaxesDescription .= $MyTaxRow['description'];
					$TaxesTaxAmount .= locale_number_format($MyTaxRow['amount'], $CurrDecimalPlaces);
				}

				//Generate download link for expense receipt, or show text if no receipt file is found.
				$ReceiptSupportedExt = array('png','jpg','jpeg','pdf','doc','docx','xls','xlsx'); //Supported file extensions
				$ReceiptDir = $PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/expenses_receipts/'; //Receipts upload directory
				$ReceiptSQL = "SELECT hashfile,
										extension
										FROM pcreceipts
										WHERE pccashdetail='" . $MyRow['counterindex'] . "'";
				$ReceiptResult = DB_query($ReceiptSQL);
				$ReceiptRow = DB_fetch_array($ReceiptResult);
				if (DB_num_rows($ReceiptResult) > 0) { //If receipt exists in database
					$ReceiptHash = $ReceiptRow['hashfile'];
					$ReceiptExt = $ReceiptRow['extension'];
					$ReceiptFileName = $ReceiptHash . '.' . $ReceiptExt;
					$ReceiptPath = $ReceiptDir . $ReceiptFileName;
					$ReceiptText = __('Open Attachment');
					$ReceiptURL = htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . $ReceiptPath, ENT_QUOTES, 'UTF-8');
				} elseif ($ExpenseCodeDes == 'ASSIGNCASH') {
				$ReceiptText = '';
				} else {
				$ReceiptText = __('No attachment');
				}

				if ($MyRow['authorized'] == '1000-01-01') {
					$AuthorisedDate = __('Unauthorised');
				} else {
					$AuthorisedDate = ConvertSQLDate($MyRow['authorized']);
				}

				$SpreadSheet->getActiveSheet()->setCellValue('A'.$i, ConvertSQLDate($MyRow['date']));
				$SpreadSheet->getActiveSheet()->setCellValue('B'.$i, $ExpenseCodeDes);
				$SpreadSheet->getActiveSheet()->setCellValue('C'.$i, $MyRow['amount']);
				$SpreadSheet->getActiveSheet()->setCellValue('D'.$i, '=D'.($i-1).'+C'.$i.'');
				$SpreadSheet->getActiveSheet()->setCellValue('E'.$i, $TaxesTaxAmount);
				$SpreadSheet->getActiveSheet()->setCellValue('F'.$i, $TaxesDescription);
				$SpreadSheet->getActiveSheet()->setCellValue('H'.$i, $MyRow['purpose']);
				$SpreadSheet->getActiveSheet()->setCellValue('I'.$i, $MyRow['notes']);
				$SpreadSheet->getActiveSheet()->setCellValue('J'.$i, $ReceiptText);
				if (isset($ReceiptURL)) {
					$SpreadSheet->getActiveSheet()->getCell('J'.$i)->getHyperlink()->setUrl($ReceiptURL);
					$SpreadSheet->getActiveSheet()->getStyle('J'.$i)->applyFromArray(array( 'font' => array( 'color' => ['rgb' => '0000FF'], 'underline' => 'single' )));
				}
				$SpreadSheet->getActiveSheet()->setCellValue('K'.$i, $AuthorisedDate);

				$i++;
			}

			// Freeze panes
			$SpreadSheet->getActiveSheet()->freezePane('A11');

			// Auto Size columns
			foreach(range('A','K') as $ColumnID) {
				$SpreadSheet->getActiveSheet()->getColumnDimension($ColumnID)
					->setAutoSize(true);
			}

			// Rename worksheet
			$SpreadSheet->getActiveSheet()->setTitle($TabToShow);
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$SpreadSheet->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			$File = 'ExpensesList-' . $TabToShow. '.' . $_POST['Format'];
			header('Content-Disposition: attachment;filename="' . $File . '"');
			/// @todo review caching headers
			header('Cache-Control: max-age=0');
			// If you're serving to IE over SSL, then the following may be needed
			header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
			header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
			header ('Pragma: public'); // HTTP/1.0


			if ($_POST['Format'] == 'xlsx') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($SpreadSheet);
				$objWriter->save('php://output');
			} elseif ($_POST['Format'] == 'ods') {
				$objWriter = new \PhpOffice\PhpSpreadsheet\Writer\Ods($SpreadSheet);
				$objWriter->save('php://output');
			}

		} else {
			$Title = __('Excel file for Petty Cash Tab Expenses List');
			include(__DIR__ . '/includes/header.php');
			prnMsg('There is no data to analyse');
			include(__DIR__ . '/includes/footer.php');
		}
	}
} else {
	$Title = __('Excel file for Petty Cash Tab Expenses List');
	$ViewTopic = 'PettyCash';// Filename's id in ManualContents.php's TOC.
	$BookMark = 'top';// Anchor's id in the manual's html document.
	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div class="header-inner">
				<div>
					<div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Exports') . '</div>
					<div class="page-title">' . $Title . '</div>
				</div>
			</div>
		</div>

		<div style="max-width: 600px; margin: 40px auto;">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Excel Export Settings') . '</div>
				</div>
				<div class="db-card-body">
					<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						
						<div class="form-group">
							<label class="form-label">' . __('Select Petty Cash Tab') . '</label>
							<select name="Tabs" class="form-control">';
								$SQL = "SELECT tabcode FROM pctabs ORDER BY tabcode";
								$CatResult = DB_query($SQL);
								while ($MyRow = DB_fetch_array($CatResult)) {
									echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
								}
							echo '</select>
						</div>

						<div class="responsive-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
							<div class="form-group">
								<label class="form-label">' . __('From Date') . '</label>
								<input type="date" name="FromDate" class="form-control" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
							</div>
							<div class="form-group">
								<label class="form-label">' . __('To Date') . '</label>
								<input type="date" name="ToDate" class="form-control" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
							</div>
						</div>

						<div class="form-group">
							<label class="form-label">' . __('Output Format') . '</label>
							<select name="Format" class="form-control">
								<option value="xlsx">' . __('Excel Format (.xlsx)') . '</option>
								<option value="ods" selected="selected">' . __('Open Document Format (.ods)') . '</option>
							</select>
						</div>

						<div style="margin-top: 30px;">
							<button type="submit" name="submit" class="btn-architect btn-primary">
								<svg style="width:20px; height:20px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
								' . __('Generate Export File') . '
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>';
	include(__DIR__ . '/includes/footer.php');
}

function display()  //####DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_#####
{
// Display form fields. This function is called the first time
// the page is called.

} // End of function display()

function beginning_of_month($Date){
	$Date2 = explode("-",$Date);
	$M = $Date2[1];
	$Y = $Date2[0];
	$FirstOfMonth = $Y . '-' . $M . '-01';
	return $FirstOfMonth;
}
