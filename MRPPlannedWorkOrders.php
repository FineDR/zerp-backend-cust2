<?php
require (__DIR__ . '/includes/session.php');
require_once 'vendor/autoload.php';
use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['cutoffdate'])) {
	$_POST['cutoffdate'] = ConvertSQLDate($_POST['cutoffdate']);
}

if (!DB_table_exists('mrprequirements')) {
	$Title = __('MRP error');
	include ('includes/header.php');
	echo '<br />';
	prnMsg(__('The MRP calculation must be run before you can run this report') . '<br />' . __('To run the MRP calculation click') . ' ' . '<a href="' . $RootPath . '/MRP.php">' . __('here') . '</a>', 'error');
	include ('includes/footer.php');
	exit();
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$WhereDate = ' ';
	$ReportDate = ' ';
	if (Is_Date($_POST['cutoffdate'])) {
		$FormatDate = FormatDateForSQL($_POST['cutoffdate']);
		$WhereDate = " AND duedate <= '" . $FormatDate . "' ";
		$ReportDate = ' ' . __('Through') . ' ' . $_POST['cutoffdate'];
	}

	if ($_POST['Consolidation'] == 'None') {
		$SQL = "SELECT mrpplannedorders.*,
					   stockmaster.stockid,
					   stockmaster.description,
					   stockmaster.mbflag,
					   stockmaster.decimalplaces,
					   stockmaster.actualcost as computedcost
				FROM mrpplannedorders
				INNER JOIN stockmaster
					ON mrpplannedorders.part = stockmaster.stockid
				WHERE stockmaster.mbflag = 'M' " . $WhereDate . "
				ORDER BY mrpplannedorders.part,mrpplannedorders.duedate";
	}
	elseif ($_POST['Consolidation'] == 'Weekly') {
		$SQL = "SELECT mrpplannedorders.part,
					   SUM(mrpplannedorders.supplyquantity) as supplyquantity,
					   TRUNCATE(((TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
					   MIN(mrpplannedorders.duedate) as duedate,
					   MIN(mrpplannedorders.mrpdate) as mrpdate,
					   COUNT(*) AS consolidatedcount,
					   stockmaster.stockid,
					   stockmaster.description,
					   stockmaster.mbflag,
					   stockmaster.decimalplaces,
					   stockmaster.actualcost as computedcost
				FROM mrpplannedorders
				INNER JOIN stockmaster
					ON mrpplannedorders.part = stockmaster.stockid
				WHERE stockmaster.mbflag = 'M' " . $WhereDate . "
				GROUP BY mrpplannedorders.part,
						 weekindex,
						 stockmaster.stockid,
						 stockmaster.description,
						 stockmaster.mbflag,
						 stockmaster.decimalplaces,
						 stockmaster.actualcost,
						 computedcost
				ORDER BY mrpplannedorders.part,weekindex";
	}
	else { // Consolidate by month
		$SQL = "SELECT mrpplannedorders.part,
					   SUM(mrpplannedorders.supplyquantity) as supplyquantity,
					   EXTRACT(YEAR_MONTH from duedate) AS yearmonth,
					   MIN(mrpplannedorders.duedate) as duedate,
					   MIN(mrpplannedorders.mrpdate) as mrpdate,
					   COUNT(*) AS consolidatedcount,
					   stockmaster.stockid,
					   stockmaster.description,
					   stockmaster.mbflag,
					   stockmaster.decimalplaces,
					   stockmaster.actualcost as computedcost
				FROM mrpplannedorders
				INNER JOIN stockmaster
					ON mrpplannedorders.part = stockmaster.stockid
				WHERE stockmaster.mbflag = 'M' " . $WhereDate . "
				GROUP BY mrpplannedorders.part,
						 yearmonth,
						 stockmaster.stockid,
						 stockmaster.description,
						 stockmaster.mbflag,
						 stockmaster.decimalplaces,
						 stockmaster.actualcost,
						 computedcost
				ORDER BY mrpplannedorders.part,yearmonth";
	}
	$ErrMsg = __('The MRP planned work orders could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0) {
		$Title = __('MRP Planned Work Orders');
		include ('includes/header.php');
		prnMsg(__('There were no items with demand greater than supply'), 'info');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include ('includes/footer.php');
		exit();
	}

	// Build the report
	$HTML = '<html><head><style>
			body { font-size: 10pt; font-family: Arial, sans-serif; }
			.report-title { font-size: 16pt; font-weight: bold; margin-bottom: 10px; }
			.company { font-size: 12pt; font-weight: bold; }
			table { border-collapse: collapse; width: 100%; }
			th, td { border: 1px solid #555; padding: 3px; }
			th { background: #eee; }
			.alt { background: #e0ebff; }
			.right { text-align: right;}
			.center { text-align: center;}
		</style></head><body>';

	$HTML .= '<div class="company">' . $_SESSION['CompanyRecord']['coyname'] . '</div>';
	$HTML .= '<div class="report-title">' . __('MRP Planned Work Orders Report') . $ReportDate . '</div>';
	$HTML .= '<div>' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '</div>';

	$HTML .= '<table>';
	$HTML .= '<tr>
			<th>' . __('Part Number') . '</th>
			<th>' . __('Due Date') . '</th>
			<th>' . __('MRP Date') . '</th>
			<th>' . __('Quantity') . '</th>
			<th>' . __('Ext. Cost') . '</th>';

	if ($_POST['Consolidation'] == 'None') {
		$HTML .= '<th>' . __('Source Type') . '</th>
					  <th>' . __('Source Order') . '</th>';
	}
	else {
		$HTML .= '<th>' . __('Consolidation Count') . '</th>';
	}
	$HTML .= '</tr>';

	$HoldPart = '';
	$HoldDescription = '';
	$HoldMBFlag = '';
	$HoldCost = 0;
	$HoldDecimalPlaces = 0;
	$TotalPartQty = 0;
	$TotalPartCost = 0;
	$Total_ExtCost = 0;
	$Partctr = 0;
	$rowClass = false;

	while ($MyRow = DB_fetch_array($Result)) {
		$rowClass = !$rowClass;
		$class = $rowClass && $_POST['Fill'] == 'yes' ? 'alt' : '';
		$FormatedSupDueDate = ConvertSQLDate($MyRow['duedate']);
		$FormatedSupMRPDate = ConvertSQLDate($MyRow['mrpdate']);
		$ExtCost = $MyRow['supplyquantity'] * $MyRow['computedcost'];

		$HTML .= '<tr class="' . $class . '">
				<td>' . htmlspecialchars($MyRow['part']) . '</td>
				<td class="right">' . $FormatedSupDueDate . '</td>
				<td class="right">' . $FormatedSupMRPDate . '</td>
				<td class="right">' . locale_number_format($MyRow['supplyquantity'], $MyRow['decimalplaces']) . '</td>
				<td class="right">' . locale_number_format($ExtCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';

		if ($_POST['Consolidation'] == 'None') {
			$HTML .= '<td class="center">' . htmlspecialchars($MyRow['ordertype']) . '</td>
						  <td class="center">' . htmlspecialchars($MyRow['orderno']) . '</td>';
		}
		else {
			$HTML .= '<td class="center">' . htmlspecialchars($MyRow['consolidatedcount']) . '</td>';
		}
		$HTML .= '</tr>';

		// Totals for summary
		$HoldDescription = $MyRow['description'];
		$HoldPart = $MyRow['part'];
		$HoldMBFlag = $MyRow['mbflag'];
		$HoldCost = $MyRow['computedcost'];
		$HoldDecimalPlaces = $MyRow['decimalplaces'];
		$TotalPartCost += $ExtCost;
		$TotalPartQty += $MyRow['supplyquantity'];
		$Total_ExtCost += $ExtCost;
		$Partctr++;
	}

	// Print summary information for last part
	$HTML .= '<tr><td colspan="2"><b>' . $HoldDescription . '</b></td>
			<td class="center">' . __('Unit Cost:') . ' ' . locale_number_format($HoldCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td class="right">' . locale_number_format($TotalPartQty, $HoldDecimalPlaces) . '</td>
			<td class="right">' . locale_number_format($TotalPartCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td class="right">' . __('M/B:') . ' ' . $HoldMBFlag . '</td>
			<td></td></tr>';

	// Grand totals
	$HTML .= '<tr><td colspan="3" class="right"><b>' . __('Number of Work Orders:') . ' ' . $Partctr . '</b></td>
			<td colspan="4" class="right"><b>' . __('Total Extended Cost:') . ' ' . locale_number_format($Total_ExtCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</b></td></tr>';

	if (isset($_POST['PrintPDF']) or isset($_POST['Email'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	}
	else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	// Create PDF with DomPDF
	$pdf_file = $_SESSION['DatabaseName'] . '_MRP_Planned_Work_Orders_' . date('Y-m-d') . '.pdf';
	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_MRPPlannedWorkOrders_' . date('Y-m-d') . '.pdf', array("Attachment" => false));
	}
	else {
		$Title = __('MRP Planned Work Orders');
		include ('includes/header.php');

        echo '<style>
            :root {
                --db-primary: hsl(197, 92%, 47%);
                --db-primary-hover: hsl(197, 92%, 38%);
                --db-primary-dark: hsl(197, 75%, 22%);
                --db-primary-soft: hsl(197, 65%, 95%);
                --db-bg: hsl(210, 20%, 97%);
                --db-card-bg: #ffffff;
                --db-border: hsl(210, 14%, 89%);
                --db-text-main: hsl(210, 24%, 16%);
                --db-text-muted: hsl(210, 16%, 46%);
                --radius-lg: 12px;
                --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            }

            .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, -apple-system, sans-serif; color: var(--db-text-main); }
            .db-centered { max-width: 1400px; margin: 0 auto; }
            
            /* Header */
            .db-page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; }
            .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
            .db-page-title { font-size: 2rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; line-height: 1.1; }

            /* Grid System */
            .db-main-grid { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start; }
            @media (max-width: 1024px) { .db-main-grid { grid-template-columns: 1fr; } }

            /* Cards */
            .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; }
            .db-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
            .db-card-title { font-size: 0.875rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; display: flex; align-items: center; gap: 10px; }
            .db-card-body { padding: 1.5rem; }

            /* Forms */
            .db-field-group { display: flex; flex-direction: column; gap: 1.25rem; }
            .db-field { display: flex; flex-direction: column; gap: 0.5rem; }
            .db-label { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); }
            .db-input, .db-select { 
                padding: 0.625rem 0.875rem; 
                border-radius: 8px; 
                border: 1px solid var(--db-border); 
                background: #fff; 
                font-size: 0.875rem; 
                transition: all 0.2s; 
                width: 100%;
            }
            .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }

            /* Buttons */
            .db-btn { 
                display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; 
                border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none;
            }
            .db-btn-primary { background: var(--db-primary); color: white; }
            .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
            .db-btn-secondary { background: #fff; border: 1px solid var(--db-border); color: var(--db-text-main); }
            .db-btn-secondary:hover { border-color: var(--db-primary); color: var(--db-primary); }

            /* Report Styles */
            .db-report-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
            .db-report-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 1rem; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--db-border); }
            .db-report-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
            .db-report-table tr:hover td { background: #f8fafc; }
            .db-report-table .right { text-align: right; }
            .db-report-table .center { text-align: center; }

            .db-mono { font-family: "JetBrains Mono", monospace; }
        </style>

        <div class="db-page">
            <div class="db-centered">
                <div class="db-page-header">
                    <div>
                        <div class="db-breadcrumb">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            ' . __('MRP Production / Supply Plan') . '
                        </div>
                        <h1 class="db-page-title">' . __('Planned Work Orders') . '</h1>
                    </div>
                    <div class="db-page-actions">
                         <a href="' . $_SERVER['PHP_SELF'] . '" class="db-btn db-btn-secondary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"></path></svg>
                            ' . __('Back to Selection') . '
                         </a>
                    </div>
                </div>

                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            ' . __('Work Order Plan') . '
                        </h3>
                    </div>
                    <div class="db-card-body" style="padding:0;">
          ';
        
        // Refactor the $HTML to use Architect classes
        $HTML = str_replace('<table>', '<table class="db-report-table">', $HTML);
        $HTML = str_replace('<td class="right">', '<td class="right db-mono">', $HTML);
        $HTML = str_replace('<div class="report-title">', '<div style="padding: 1rem 1.5rem; font-size: 0.8125rem; font-weight:600; color:var(--db-text-muted); border-bottom:1px solid var(--db-border);">', $HTML);
        $HTML = str_replace('<div class="company">', '', $HTML);
        
		echo $HTML;
		echo '      </div>
                </div>
            </div>
        </div>';
		include ('includes/footer.php');
	}

} else { /*The option to print PDF was not hit so display form */

	$Title = __('MRP Planned Work Orders Reporting');
	$ViewTopic = 'MRP';
	$BookMark = '';
	include ('includes/header.php');

    echo '<style>
        :root {
            --db-primary: hsl(197, 92%, 47%);
            --db-primary-hover: hsl(197, 92%, 38%);
            --db-primary-dark: hsl(197, 75%, 22%);
            --db-primary-soft: hsl(197, 65%, 95%);
            --db-bg: hsl(210, 20%, 97%);
            --db-card-bg: #ffffff;
            --db-border: hsl(210, 14%, 89%);
            --db-text-main: hsl(210, 24%, 16%);
            --db-text-muted: hsl(210, 16%, 46%);
            --radius-lg: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
        }

        .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, -apple-system, sans-serif; color: var(--db-text-main); }
        .db-centered { max-width: 1400px; margin: 0 auto; }
        
        /* Header */
        .db-page-header { margin-bottom: 2rem; }
        .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; }
        .db-page-title { font-size: 2rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; line-height: 1.1; }

        /* Grid System */
        .db-main-grid { display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start; }
        @media (max-width: 1024px) { .db-main-grid { grid-template-columns: 1fr; } }

        /* Cards */
        .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; }
        .db-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
        .db-card-title { font-size: 0.875rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; display: flex; align-items: center; gap: 10px; }
        .db-card-body { padding: 1.5rem; }

        /* Forms */
        .db-field-group { display: flex; flex-direction: column; gap: 1.25rem; }
        .db-field { display: flex; flex-direction: column; gap: 0.5rem; }
        .db-label { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); }
        .db-input, .db-select { 
            padding: 0.625rem 0.875rem; 
            border-radius: 8px; 
            border: 1px solid var(--db-border); 
            background: #fff; 
            font-size: 0.875rem; 
            transition: all 0.2s; 
            width: 100%;
        }
        .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }

        /* Buttons */
        .db-btn { 
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; 
            border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none;
        }
        .db-btn-primary { background: var(--db-primary); color: white; }
        .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
        .db-btn-secondary { background: var(--db-primary-soft); color: var(--db-primary); border: 1px solid var(--db-primary); }
        .db-btn-secondary:hover { background: var(--db-primary); color: #fff; }

        /* Info Boxes */
        .db-info-box { padding: 1.5rem; background: var(--db-primary-soft); border-radius: 12px; color: var(--db-primary-dark); font-size: 0.875rem; line-height: 1.6; }
    </style>

    <div class="db-page">
        <div class="db-centered">
            <div class="db-page-header">
                <div class="db-breadcrumb">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    ' . __('Inventory / MRP Supply Plan') . '
                </div>
                <h1 class="db-page-title">' . $Title . '</h1>
            </div>

            <div class="db-main-grid">
                <!-- Main Content -->
                <div class="db-field-group">
                    <div class="db-info-box">
                        <h4 style="margin:0 0 0.5rem 0; font-weight:900;">' . __('About this Report') . '</h4>
                        ' . __('This report list all the parts that the MRP calculation has determined should have work orders created for them.') . '
                        <ul style="margin: 1rem 0 0 1.5rem; padding:0;">
                            <li>' . __('Consolidation allows grouping production requirements together.') . '</li>
                            <li>' . __('The "Cut Off Date" filters production due on or before that date.') . '</li>
                        </ul>
                    </div>
                </div>

                <!-- Sidebar: Criteria Form -->
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                            ' . __('Report Criteria') . '
                        </h3>
                    </div>
                    <div class="db-card-body">
                        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" target="_blank">
                            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                            <div class="db-field-group">
                                <div class="db-field">
                                    <label class="db-label">' . __('Consolidation') . '</label>
                                    <select required="required" name="Consolidation" class="db-select">
                                        <option value="None">' . __('None') . '</option>
                                        <option value="Weekly">' . __('Weekly') . '</option>
                                        <option value="Monthly">' . __('Monthly') . '</option>
                                    </select>
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('Cut Off Date') . '</label>
                                    <input required="required" type="date" name="cutoffdate" class="db-input" value="' . date('Y-m-d') . '" />
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('Highlighting') . '</label>
                                    <select name="Fill" class="db-select">
                                        <option value="yes">' . __('Alternating Highlights') . '</option>
                                        <option value="no">' . __('Plain Minimalist') . '</option>
                                    </select>
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:0.5rem;">
                                    <button type="submit" name="PrintPDF" class="db-btn db-btn-primary">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                        ' . __('PDF') . '
                                    </button>
                                    <button type="submit" name="View" class="db-btn db-btn-secondary">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                        ' . __('View') . '
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>';

	include ('includes/footer.php');
}

