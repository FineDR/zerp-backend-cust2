<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (!DB_table_exists('mrprequirements')) {
	$Title = __('MRP error');
	include(__DIR__ . '/includes/header.php');
	echo '<br />';
	prnMsg(__('The MRP calculation must be run before you can run this report') . '<br />' . __('To run the MRP calculation click') . ' ' . '<a href="' . $RootPath . '/MRP.php">' . __('here') . '</a>', 'error');
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	// Prepare data as in the original script
	$SQL = "CREATE TEMPORARY TABLE demandtotal (
				part char(20),
				demand double,
				KEY `PART` (`part`)) DEFAULT CHARSET=utf8";
	$Result = DB_query($SQL, __('Create of demandtotal failed because'));

	$SQL = "INSERT INTO demandtotal
						(part,
						 demand)
			   SELECT part,
					  SUM(quantity) as demand
				FROM mrprequirements
				GROUP BY part";
	$Result = DB_query($SQL);

	$SQL = "CREATE TEMPORARY TABLE supplytotal (
				part char(20),
				supply double,
				KEY `PART` (`part`)) DEFAULT CHARSET=utf8";
	$Result = DB_query($SQL, __('Create of supplytotal failed because'));

	$SQL = "INSERT INTO supplytotal
						(part,
						 supply)
			SELECT stockid,
				  0
			FROM stockmaster";
	$Result = DB_query($SQL);

	$SQL = "UPDATE supplytotal
			SET supply = (SELECT SUM(mrpsupplies.supplyquantity)
							FROM mrpsupplies
							WHERE supplytotal.part = mrpsupplies.part
								AND mrpsupplies.supplyquantity > 0)";
	$Result = DB_query($SQL);

	$SQL = "UPDATE supplytotal SET supply = 0 WHERE supply IS NULL";
	$Result = DB_query($SQL);

	if ($_POST['CategoryID'] == 'All') {
		$SQLCategory = ' ';
	} else {
		$SQLCategory = "WHERE stockmaster.categoryid = '" . $_POST['CategoryID'] . "'";
	}

	if ($_POST['ReportType'] == 'Shortage') {
		$SQLHaving = " HAVING demandtotal.demand > supplytotal.supply ";
		$reportTitle = __('MRP Shortages Report');
		$reportSubject = __('MRP Shortages');
	} else {
		$SQLHaving = " HAVING demandtotal.demand <= supplytotal.supply ";
		$reportTitle = __('MRP Excess Report');
		$reportSubject = __('MRP Excess');
	}

	$SortField = $_POST['Sort'] === 'stockid' ? 'stockmaster.stockid' : 'extcost';

	$SQL = "SELECT stockmaster.stockid,
		stockmaster.description,
		stockmaster.mbflag,
		stockmaster.actualcost,
		stockmaster.decimalplaces,
		(stockmaster.actualcost) as computedcost,
		demandtotal.demand,
		supplytotal.supply,
		(demandtotal.demand - supplytotal.supply) *
		(stockmaster.actualcost) as extcost
		   FROM stockmaster
			 LEFT JOIN demandtotal ON stockmaster.stockid = demandtotal.part
			 LEFT JOIN supplytotal ON stockmaster.stockid = supplytotal.part
			 LEFT JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid " . $SQLCategory . "WHERE stockcategory.stocktype<>'L'
			 GROUP BY stockmaster.stockid,
			   stockmaster.description,
			   stockmaster.mbflag,
			   stockmaster.actualcost,
			   stockmaster.decimalplaces,
			   stockmaster.actualcost,
			   supplytotal.supply,
			   demandtotal.demand " . $SQLHaving . " ORDER BY $SortField";

	$ErrMsg = __('The MRP shortages and excesses could not be retrieved');
	$Result = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($Result) == 0) {
		$Title = __('MRP Shortages and Excesses') . ' - ' . __('Problem Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg(__('No MRP shortages - Excess retrieved'), 'warn');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	// Build report as HTML
	$HTML = '<html><head><meta charset="UTF-8"><style>
		body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; }
		h1 { text-align: center; font-size: 18px; }
		table { border-collapse: collapse; width: 100%; margin-top: 10px; }
		th, td { border: 1px solid #aaa; padding: 4px 6px; font-size: 10px; }
		th { background: #f0f0f0; }
		.fill { background-color: #e0ebff; }
		.right { text-align: right; }
		.centre { text-align: center; }
	</style></head><body>';

	$HTML .= '<h1>' . htmlspecialchars($_SESSION['CompanyRecord']['coyname']) . '</h1>';
	$HTML .= '<h2>' . $reportTitle . '</h2>';
	$HTML .= '<div>' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '</div>';

	$HTML .= '<table><thead><tr>
		<th>' . __('Part Number') . '</th>
		<th>' . __('Description') . '</th>
		<th>' . __('M/B') . '</th>
		<th>' . __('Unit Cost') . '</th>
		<th>' . __('Supply') . '</th>
		<th>' . __('Demand') . '</th>';

	if ($_POST['ReportType'] == 'Shortage') {
		$HTML .= '<th>' . __('Shortage') . '</th><th>' . __('Ext. Shortage') . '</th>';
	} else {
		$HTML .= '<th>' . __('Excess') . '</th><th>' . __('Ext. Excess') . '</th>';
	}

	$HTML .= '</tr></thead><tbody>';

	$Total_Shortage = 0;
	$Partctr = 0;
	$fillRow = ($_POST['Fill'] == 'yes');
	$rowAlt = false;

	while ($MyRow = DB_fetch_array($Result)) {
		if ($_POST['ReportType'] == 'Shortage') {
			$LineToPrint = ($MyRow['demand'] > $MyRow['supply']);
			$Shortage = ($MyRow['demand'] - $MyRow['supply']) * -1;
			$Extcost = $Shortage * $MyRow['computedcost'];
			$shortageVal = locale_number_format($Shortage, $MyRow['decimalplaces']);
			$extcostVal = locale_number_format($MyRow['extcost'], 2);
		} else {
			$LineToPrint = ($MyRow['demand'] <= $MyRow['supply']);
			$Shortage = ($MyRow['supply'] - $MyRow['demand']);
			$Extcost = $Shortage * $MyRow['computedcost'];
			$shortageVal = locale_number_format($Shortage, $MyRow['decimalplaces']);
			$extcostVal = locale_number_format($MyRow['extcost'], 2);
		}

		if ($LineToPrint) {
			$class = ($fillRow && $rowAlt) ? 'fill' : '';
			$HTML .= '<tr class="' . $class . '">';
			$HTML .= '<td>' . htmlspecialchars($MyRow['stockid']) . '</td>';
			$HTML .= '<td>' . htmlspecialchars($MyRow['description']) . '</td>';
			$HTML .= '<td class="centre">' . htmlspecialchars($MyRow['mbflag']) . '</td>';
			$HTML .= '<td class="right">' . locale_number_format($MyRow['computedcost'], 2) . '</td>';
			$HTML .= '<td class="right">' . locale_number_format($MyRow['supply'], $MyRow['decimalplaces']) . '</td>';
			$HTML .= '<td class="right">' . locale_number_format($MyRow['demand'], $MyRow['decimalplaces']) . '</td>';
			$HTML .= '<td class="right">' . $shortageVal . '</td>';
			$HTML .= '<td class="right">' . $extcostVal . '</td>';
			$HTML .= '</tr>';

			$Total_Shortage += $MyRow['extcost'];
			$Partctr++;
			$rowAlt = !$rowAlt;
		}
	}

	$HTML .= '</tbody></table>';

	$DisplayTotalVal = locale_number_format($Total_Shortage, 2);

	$HTML .= '<br><table style="width: 40%;"><tr>
		<td>' . __('Number of Parts:') . '</td>
		<td class="right">' . $Partctr . '</td>
	</tr><tr>
		<td>' . ($_POST['ReportType'] == 'Shortage' ? __('Total Extended Shortage:') : __('Total Extended Excess:')) . '</td>
		<td class="right">' . $DisplayTotalVal . '</td>
	</tr></table>';

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
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_MRPShortages_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('MRP Shortages');
		include(__DIR__ . '/includes/header.php');

        echo '<style>
            :root {
                --db-primary: hsl(145, 63%, 38%);
                --db-primary-hover: hsl(145, 63%, 32%);
                --db-primary-dark: hsl(145, 45%, 22%);
                --db-primary-soft: hsl(145, 40%, 95%);
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
            .db-report-table .centre { text-align: center; }

            .db-mono { font-family: "JetBrains Mono", monospace; }
        </style>

        <div class="db-page">
            <div class="db-centered">
                <div class="db-page-header">
                    <div>
                        <div class="db-breadcrumb">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            ' . __('MRP Management / Analysis') . '
                        </div>
                        <h1 class="db-page-title">' . $reportTitle . '</h1>
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
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            ' . __('Analysis Results') . '
                        </h3>
                    </div>
                    <div class="db-card-body" style="padding:0;">
          ';
        
        // Refactor the $HTML to use Architect classes
        $HTML = str_replace('<table>', '<table class="db-report-table">', $HTML);
        $HTML = str_replace('<thead>', '<thead>', $HTML); // Ensure case match
        $HTML = str_replace('<th>', '<th>', $HTML);
        $HTML = str_replace('<td class="right">', '<td class="right db-mono">', $HTML);
        $HTML = str_replace('<h1>', '', $HTML);
        $HTML = str_replace('<h2>', '', $HTML);
        $HTML = str_replace('<div>', '<div style="padding: 1rem 1.5rem; font-size: 0.8125rem; font-weight:600; color:var(--db-text-muted); border-bottom:1px solid var(--db-border);">', $HTML);
        
		echo $HTML;
		echo '      </div>
                </div>
            </div>
        </div>';
		include(__DIR__ . '/includes/footer.php');
	}

} else {
	// Display form as before (unchanged)
	$Title = __('MRP Shortages - Excess Reporting');
	$ViewTopic = 'MRP';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');

    echo '<style>
        :root {
            --db-primary: hsl(145, 63%, 38%);
            --db-primary-hover: hsl(145, 63%, 32%);
            --db-primary-dark: hsl(145, 45%, 22%);
            --db-primary-soft: hsl(145, 40%, 95%);
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
                    ' . __('Inventory Management / MRP Analysis') . '
                </div>
                <h1 class="db-page-title">' . $Title . '</h1>
            </div>

            <div class="db-main-grid">
                <!-- Main Content -->
                <div class="db-field-group">
                    <div class="db-info-box">
                        <h4 style="margin:0 0 0.5rem 0; font-weight:900;">' . __('About this Report') . '</h4>
                        ' . __('This report identifies materials that have insufficient (Search Report) or excessive (Excess Report) planned supply relative to the total demand generated by the MRP calculation.') . '
                        <ul style="margin: 1rem 0 0 1.5rem; padding:0;">
                            <li>' . __('Run the MRP calculation first before generating this report.') . '</li>
                            <li>' . __('Choose "Shortages" to see what you need to order.') . '</li>
                            <li>' . __('Choose "Excesses" to see what items you have too much of.') . '</li>
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
                                    <label class="db-label">' . __('Inventory Category') . '</label>
                                    <select name="CategoryID" class="db-select">
                                        <option value="All">' . __('All Stock Categories') . '</option>';
                                        $SQL = "SELECT categoryid, categorydescription FROM stockcategory";
                                        $CategoryResult = DB_query($SQL);
                                        while ($MyRow = DB_fetch_array($CategoryResult)) {
                                            echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categoryid'] . ' - ' . $MyRow['categorydescription'] . '</option>';
                                        }
    echo '                          </select>
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('Sort By') . '</label>
                                    <select name="Sort" class="db-select">
                                        <option value="extcost">' . __('Extended Shortage Dollars') . '</option>
                                        <option value="stockid">' . __('Part Number') . '</option>
                                    </select>
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('Analysis Type') . '</label>
                                    <select name="ReportType" class="db-select">
                                        <option value="Shortage">' . __('Report MRP Shortages') . '</option>
                                        <option value="Excess">' . __('Report MRP Excesses') . '</option>
                                    </select>
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

	include(__DIR__ . '/includes/footer.php');
}