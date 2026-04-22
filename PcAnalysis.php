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

if (isset($_POST['submit'])) {
	//initialise no input errors
	$InputError = 0;
	$TabToShow = $_POST['Tabs'];
	//first off validate inputs sensible

	if ($InputError == 0){
		// Creation of beginning of SQL query
		$SQL = "SELECT pcexpenses.codeexpense,";

		// Creation of periods SQL query
		$PeriodToday=GetPeriod(date($_SESSION['DefaultDateFormat']));
		$SQLPeriods = "SELECT periodno,
						lastdate_in_period
				FROM periods
				WHERE periodno <= ". $PeriodToday ."
				ORDER BY periodno DESC
				LIMIT 24";
		$Periods = DB_query($SQLPeriods);
		$NumPeriod = 0;
		$LabelsArray = array();
		while ($MyRow=DB_fetch_array($Periods)){

			$NumPeriod++;
			$LabelsArray[$NumPeriod] = MonthAndYearFromSQLDate($MyRow['lastdate_in_period']);
			$SQL = $SQL . "(SELECT SUM(pcashdetails.amount)
							FROM pcashdetails
							WHERE pcashdetails.codeexpense = pcexpenses.codeexpense";
			if ($TabToShow!='All'){
				$SQL = $SQL." 	AND pcashdetails.tabcode = '". $TabToShow ."'";
			}
			$SQL = $SQL . "		AND date >= '" . beginning_of_month($MyRow['lastdate_in_period']). "'
								AND date <= '" . $MyRow['lastdate_in_period'] . "') AS expense_period".$NumPeriod.", ";
		}
		// Creation of final part of SQL
		$SQL = $SQL." pcexpenses.description
				FROM  pcexpenses
				ORDER BY pcexpenses.codeexpense";

		$Result = DB_query($SQL);
		if (DB_num_rows($Result) != 0){

			// Create new PHPSpreadsheet object
			$SpreadSheet = new Spreadsheet();

			// Set document properties
			$SpreadSheet->getProperties()->setCreator("webERP")
										 ->setLastModifiedBy("webERP")
										 ->setTitle("Petty Cash Expenses Analysis")
										 ->setSubject("Petty Cash Expenses Analysis")
										 ->setDescription("Petty Cash Expenses Analysis")
										 ->setKeywords("")
										 ->setCategory("");

			// Formatting

			$SpreadSheet->getActiveSheet()->getStyle('C:AB')->getNumberFormat()->setFormatCode('#,##0.00');
			$SpreadSheet->getActiveSheet()->getStyle('4')->getFont()->setBold(true);
			$SpreadSheet->getActiveSheet()->getStyle('A2')->getFont()->setBold(true);
			$SpreadSheet->getActiveSheet()->getStyle('A:B')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

			// Add title data
			$SpreadSheet->setActiveSheetIndex(0);
			$SpreadSheet->getActiveSheet()->setCellValue('A2', 'Petty Cash Tab(s)');
			$SpreadSheet->getActiveSheet()->setCellValue('B2', $TabToShow);
			$SpreadSheet->getActiveSheet()->setCellValue('A4', 'Expense Code');
			$SpreadSheet->getActiveSheet()->setCellValue('B4', 'Description');

			$SpreadSheet->getActiveSheet()->setCellValue('C4', 'Total 12 Months');
			$SpreadSheet->getActiveSheet()->setCellValue('D4', 'Average 12 Months');

			$SpreadSheet->getActiveSheet()->setCellValue('E4', $LabelsArray[24]);
			$SpreadSheet->getActiveSheet()->setCellValue('F4', $LabelsArray[23]);
			$SpreadSheet->getActiveSheet()->setCellValue('G4', $LabelsArray[22]);
			$SpreadSheet->getActiveSheet()->setCellValue('H4', $LabelsArray[21]);
 			$SpreadSheet->getActiveSheet()->setCellValue('I4', $LabelsArray[20]);
 			$SpreadSheet->getActiveSheet()->setCellValue('J4', $LabelsArray[19]);
 			$SpreadSheet->getActiveSheet()->setCellValue('K4', $LabelsArray[18]);
 			$SpreadSheet->getActiveSheet()->setCellValue('L4', $LabelsArray[17]);
 			$SpreadSheet->getActiveSheet()->setCellValue('M4', $LabelsArray[16]);
 			$SpreadSheet->getActiveSheet()->setCellValue('N4', $LabelsArray[15]);
 			$SpreadSheet->getActiveSheet()->setCellValue('O4', $LabelsArray[14]);
 			$SpreadSheet->getActiveSheet()->setCellValue('P4', $LabelsArray[13]);
 			$SpreadSheet->getActiveSheet()->setCellValue('Q4', $LabelsArray[12]);
 			$SpreadSheet->getActiveSheet()->setCellValue('R4', $LabelsArray[11]);
 			$SpreadSheet->getActiveSheet()->setCellValue('S4', $LabelsArray[10]);
 			$SpreadSheet->getActiveSheet()->setCellValue('T4', $LabelsArray[9]);
 			$SpreadSheet->getActiveSheet()->setCellValue('U4', $LabelsArray[8]);
 			$SpreadSheet->getActiveSheet()->setCellValue('V4', $LabelsArray[7]);
 			$SpreadSheet->getActiveSheet()->setCellValue('W4', $LabelsArray[6]);
 			$SpreadSheet->getActiveSheet()->setCellValue('X4', $LabelsArray[5]);
 			$SpreadSheet->getActiveSheet()->setCellValue('Y4', $LabelsArray[4]);
 			$SpreadSheet->getActiveSheet()->setCellValue('Z4', $LabelsArray[3]);
 			$SpreadSheet->getActiveSheet()->setCellValue('AA4', $LabelsArray[2]);
 			$SpreadSheet->getActiveSheet()->setCellValue('AB4', $LabelsArray[1]);

			// Add data
			$i = 5;
			while ($MyRow = DB_fetch_array($Result)) {
				$SpreadSheet->setActiveSheetIndex(0);
				$SpreadSheet->getActiveSheet()->setCellValue('A'.$i, $MyRow['codeexpense']);
				$SpreadSheet->getActiveSheet()->setCellValue('B'.$i, $MyRow['description']);

				$SpreadSheet->getActiveSheet()->setCellValue('C'.$i, '=SUM(Q'.$i.':AB'.$i.')');
				$SpreadSheet->getActiveSheet()->setCellValue('D'.$i, '=AVERAGE(Q'.$i.':AB'.$i.')');

				$SpreadSheet->getActiveSheet()->setCellValue('E'.$i, -$MyRow['expense_period24']);
				$SpreadSheet->getActiveSheet()->setCellValue('F'.$i, -$MyRow['expense_period23']);
				$SpreadSheet->getActiveSheet()->setCellValue('G'.$i, -$MyRow['expense_period22']);
				$SpreadSheet->getActiveSheet()->setCellValue('H'.$i, -$MyRow['expense_period21']);
				$SpreadSheet->getActiveSheet()->setCellValue('I'.$i, -$MyRow['expense_period20']);
				$SpreadSheet->getActiveSheet()->setCellValue('J'.$i, -$MyRow['expense_period19']);
				$SpreadSheet->getActiveSheet()->setCellValue('K'.$i, -$MyRow['expense_period18']);
				$SpreadSheet->getActiveSheet()->setCellValue('L'.$i, -$MyRow['expense_period17']);
				$SpreadSheet->getActiveSheet()->setCellValue('M'.$i, -$MyRow['expense_period16']);
				$SpreadSheet->getActiveSheet()->setCellValue('N'.$i, -$MyRow['expense_period15']);
				$SpreadSheet->getActiveSheet()->setCellValue('O'.$i, -$MyRow['expense_period14']);
				$SpreadSheet->getActiveSheet()->setCellValue('P'.$i, -$MyRow['expense_period13']);
				$SpreadSheet->getActiveSheet()->setCellValue('Q'.$i, -$MyRow['expense_period12']);
				$SpreadSheet->getActiveSheet()->setCellValue('R'.$i, -$MyRow['expense_period11']);
				$SpreadSheet->getActiveSheet()->setCellValue('S'.$i, -$MyRow['expense_period10']);
				$SpreadSheet->getActiveSheet()->setCellValue('T'.$i, -$MyRow['expense_period9']);
				$SpreadSheet->getActiveSheet()->setCellValue('U'.$i, -$MyRow['expense_period8']);
				$SpreadSheet->getActiveSheet()->setCellValue('V'.$i, -$MyRow['expense_period7']);
				$SpreadSheet->getActiveSheet()->setCellValue('W'.$i, -$MyRow['expense_period6']);
				$SpreadSheet->getActiveSheet()->setCellValue('X'.$i, -$MyRow['expense_period5']);
				$SpreadSheet->getActiveSheet()->setCellValue('Y'.$i, -$MyRow['expense_period4']);
				$SpreadSheet->getActiveSheet()->setCellValue('Z'.$i, -$MyRow['expense_period3']);
				$SpreadSheet->getActiveSheet()->setCellValue('AA'.$i, -$MyRow['expense_period2']);
				$SpreadSheet->getActiveSheet()->setCellValue('AB'.$i, -$MyRow['expense_period1']);

				$i++;
			}

			// Freeze panes
			$SpreadSheet->getActiveSheet()->freezePane('E5');

			// Auto Size columns
			for($col = 'A'; $col !== $SpreadSheet->getActiveSheet()->getHighestDataColumn(); $col++) {
				$SpreadSheet->getActiveSheet()
					->getColumnDimension($col)
					->setAutoSize(true);
}

			// Rename worksheet
			if ($TabToShow=='All'){
				$SpreadSheet->getActiveSheet()->setTitle('All Accounts');
			} else {
				$SpreadSheet->getActiveSheet()->setTitle($TabToShow);
			}
			// Set active sheet index to the first sheet, so Excel opens this as the first sheet
			$SpreadSheet->setActiveSheetIndex(0);

			// Redirect output to a client’s web browser (Excel2007)
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

			$File = 'PCExpensesAnalysis-' . date('Y-m-d'). '.' . $_POST['Format'];

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
			$Title = __('Excel file for Petty Cash Expenses Analysis');
			include(__DIR__ . '/includes/header.php');
			prnMsg('There is no data to analyse');
			include(__DIR__ . '/includes/footer.php');
		}
	}
} else {
// Display form fields. This function is called the first time
// the page is called.
	$Title = __('Excel file for Petty Cash Expenses Analysis');
	$ViewTopic = 'PettyCash';// Filename's id in ManualContents.php's TOC.
	$BookMark = 'top';// Anchor's id in the manual's html document.

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div class="header-inner">
				<div>
					<div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Analysis') . '</div>
					<div class="page-title">' . $Title . '</div>
				</div>
			</div>
		</div>

		<div style="max-width: 600px; margin: 40px auto;">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Expense Analysis Settings') . '</div>
				</div>
				<div class="db-card-body">
					<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						
						<div class="form-group">
							<label class="form-label">' . __('Select Petty Cash Tabs') . '</label>
							<select name="Tabs" class="form-control">
								<option value="All">' . __('All Tabs') . '</option>';
								$SQL = "SELECT tabcode FROM pctabs ORDER BY tabcode";
								$CatResult = DB_query($SQL);
								while ($MyRow = DB_fetch_array($CatResult)) {
									echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
								}
							echo '</select>
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
								<svg style="width:20px; height:20px; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
								' . __('Generate Analysis File') . '
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>';
	include(__DIR__ . '/includes/footer.php');

}

function beginning_of_month($Date){
	$Date2 = explode("-",$Date);
	$M = $Date2[1];
	$Y = $Date2[0];
	$FirstOfMonth = $Y . '-' . $M . '-01';
	return $FirstOfMonth;
}
