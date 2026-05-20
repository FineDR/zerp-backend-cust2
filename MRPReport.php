<?php

// Shows supply and demand for a part as determined by MRP

require(__DIR__ . '/includes/session.php');

// Use DomPDF for PDF generation
use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['Select'])) {
	$_POST['Part']=$_POST['Select'];
	$_POST['PrintPDF']='Yes';
}

if (isset($_POST['PrintPDF']) && $_POST['Part'] != '') {

	// Load mrprequirements into $Requirements array
	$SQL = "SELECT mrprequirements.*,
				TRUNCATE(((TO_DAYS(daterequired) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				TO_DAYS(daterequired) - TO_DAYS(CURRENT_DATE) AS datediff
			FROM mrprequirements
			WHERE part = '" . $_POST['Part'] ."'
			ORDER BY daterequired,whererequired";

	$ErrMsg = __('The MRP calculation must be run before this report will have any output. MRP requires set up of many parameters, including, EOQ, lead times, minimums, bills of materials, demand types, etc.');
	$Result = DB_query($SQL, $ErrMsg);
	if (DB_error_no() != 0) {
		$Errors = 1;
	}

	if (DB_num_rows($Result) == 0) {
		$Errors = 1;
		$Title = __('Print MRP Report Warning');
		include(__DIR__ . '/includes/header.php');
		echo '<br /><a href="' . $RootPath . '/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$Requirements = array();
	$WeeklyReq = array_fill(0, 28, 0);
	$PastDueReq = 0;
	$FutureReq = 0;
	$GrossReq = 0;

	while ($MyRow=DB_fetch_array($Result)) {
		array_push($Requirements,$MyRow);
		$GrossReq += $MyRow['quantity'];
		if ($MyRow['datediff'] < 0) {
			$PastDueReq += $MyRow['quantity'];
		} elseif ($MyRow['weekindex'] > 27) {
			$FutureReq += $MyRow['quantity'];
		} else {
			$WeeklyReq[$MyRow['weekindex']] += $MyRow['quantity'];
		}
	}

	// Load mrpsupplies into $Supplies array
	$SQL = "SELECT mrpsupplies.*,
				   TRUNCATE(((TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				   TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE) AS datediff
			 FROM mrpsupplies
			 WHERE part = '" . $_POST['Part'] . "'
			 ORDER BY mrpdate";
	$Result = DB_query($SQL);
	if (DB_error_no() !=0) {
		$Errors = 1;
	}
	$Supplies = array();
	$WeeklySup = array_fill(0, 28, 0);
	$PastDueSup = 0;
	$FutureSup = 0;
	$QOH = 0; // Get quantity on Hand to display
	$OpenOrd = 0;
	while ($MyRow=DB_fetch_array($Result)) {
		if ($MyRow['ordertype'] == 'QOH') {
			$QOH += $MyRow['supplyquantity'];
		} else {
			$OpenOrd += $MyRow['supplyquantity'];
			if ($MyRow['datediff'] < 0) {
				$PastDueSup += $MyRow['supplyquantity'];
			} elseif ($MyRow['weekindex'] > 27) {
				$FutureSup += $MyRow['supplyquantity'];
			} else {
				$WeeklySup[$MyRow['weekindex']] += $MyRow['supplyquantity'];
			}
		}
		array_push($Supplies,$MyRow);
	}

	// Load planned orders
	$SQL = "SELECT mrpplannedorders.*,
				   TRUNCATE(((TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE)) / 7),0) AS weekindex,
				   TO_DAYS(duedate) - TO_DAYS(CURRENT_DATE) AS datediff
				FROM mrpplannedorders WHERE part = '" . $_POST['Part'] . "' ORDER BY mrpdate";
	$Result = DB_query($SQL,'','',false);
	if (DB_error_no() !=0) {
		$Errors = 1;
	}

	$WeeklyPlan = array_fill(0, 28, 0);
	$PastDuePlan = 0;
	$FuturePlan = 0;
	while ($MyRow=DB_fetch_array($Result)) {
		array_push($Supplies,$MyRow);
		if ($MyRow['datediff'] < 0) {
			$PastDuePlan += $MyRow['supplyquantity'];
		} elseif ($MyRow['weekindex'] > 27) {
			$FuturePlan += $MyRow['supplyquantity'];
		} else {
			$WeeklyPlan[$MyRow['weekindex']] += $MyRow['supplyquantity'];
		}
	}

	foreach ($Supplies as $key => $Row) {
		$MRPDate[$key] = $Row['mrpdate'];
	}

	if (isset($Errors)) {
		$Title = __('MRP Report') . ' - ' . __('Problem Report');
		include(__DIR__ . '/includes/header.php');
		prnMsg( __('The MRP Report could not be retrieved'), 'error');
		echo '<br /><a href="' .$RootPath .'/index.php">' . __('Back to the menu') . '</a>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	if (count($Supplies)) {
		array_multisort($MRPDate, SORT_ASC, $Supplies);
	}

	// Get and display part information
	$SQL = "SELECT levels.*,
				   stockmaster.description,
				   stockmaster.lastcost,
				   stockmaster.decimalplaces,
				   stockmaster.mbflag
				   FROM levels
			LEFT JOIN stockmaster
			ON levels.part = stockmaster.stockid
			WHERE part = '" . $_POST['Part'] . "'";
	$Result = DB_query($SQL,'','',false);
	$MyRow=DB_fetch_array($Result);

	// Calculate fields for projected available weekly buckets
	$PlannedAccum = array();
	$PastDueAvail = ($QOH + $PastDueSup + $PastDuePlan) - $PastDueReq;
	$WeeklyAvail = array();
	$WeeklyAvail[0] = ($PastDueAvail + $WeeklySup[0] + $WeeklyPlan[0]) - $WeeklyReq[0];
	$PlannedAccum[0] = $PastDuePlan + $WeeklyPlan[0];
	for ($i = 1; $i < 28; $i++) {
		$WeeklyAvail[$i] = ($WeeklyAvail[$i - 1] + $WeeklySup[$i] + $WeeklyPlan[$i]) - $WeeklyReq[$i];
		$PlannedAccum[$i] = $PlannedAccum[$i-1] + $WeeklyPlan[$i];
	}
	$FutureAvail = ($WeeklyAvail[27] + $FutureSup + $FuturePlan) - $FutureReq;
	$FuturePlannedaccum = $PlannedAccum[27] + $FuturePlan;

	// Prepare the HTML content
	$HTML = '';
	$HTML .= '<html>
	<head>
		<link href="css/reports.css" rel="stylesheet" type="text/css" />
		<style>
			body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
			table { border-collapse: collapse; width: 100%; margin-bottom: 18px; }
			th, td { border: 1px solid #666; padding: 4px 6px; font-size: 11px; }
			th { background: #e0e8f8; }
			.section { font-weight: bold; background: #eee; }
			.header-table td { border: none; }
		</style>
	</head>
	<body>
		<h2 style="text-align: center;">'.htmlspecialchars($_SESSION['CompanyRecord']['coyname']).'</h2>
		<h3 style="text-align: center;">MRP Report</h3>
		<table class="header-table">
			<tr>
				<td><b>Part:</b></td>
				<td>'.htmlspecialchars($MyRow['part']).'</td>
				<td><b>EOQ:</b></td>
				<td class="number">'.locale_number_format($MyRow['eoq'],$MyRow['decimalplaces']).'</td>
				<td><b>On Hand:</b></td>
				<td class="number">'.locale_number_format($QOH,$MyRow['decimalplaces']).'</td>
			</tr>
			<tr>
				<td><b>Description:</b></td>
				<td colspan="3">'.html_entity_decode($MyRow['description']).'</td>
				<td><b>On Order:</b></td>
				<td class="number">'.locale_number_format($OpenOrd,$MyRow['decimalplaces']).'</td>
			</tr>
			<tr>
				<td><b>M/B:</b></td>
				<td>'.htmlspecialchars($MyRow['mbflag']).'</td>
				<td><b>Shrinkage:</b></td>
				<td class="number">'.locale_number_format($MyRow['shrinkfactor'],$MyRow['decimalplaces']).'</td>
				<td><b>Gross Req:</b></td>
				<td class="number">'.locale_number_format($GrossReq,$MyRow['decimalplaces']).'</td>
			</tr>
			<tr>
				<td><b>Lead Time:</b></td>
				<td>'.htmlspecialchars($MyRow['leadtime']).'</td>
				<td><b>Last Cost:</b></td>
				<td class="number">'.locale_number_format($MyRow['lastcost'],2).'</td>
				<td></td>
				<td></td>
			</tr>
		</table>';

	// Weekly Buckets Table
	$HTML .= '<table>
		<tr>
			<th></th>';
	$Dateformat = $_SESSION['DefaultDateFormat'];
	$Today = date($Dateformat);
	$HTML .= '<th>Past Due</th>';
	for ($i=0; $i<9; $i++) {
		$HTML .= '<th>'.htmlspecialchars(DateAdd($Today,'w',$i)).'</th>';
	}
	$HTML .= '</tr>
		<tr><td class="section">Gross Reqts</td><td class="number">'.locale_number_format($PastDueReq,0).'</td>';
	for ($i=0; $i<9; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyReq[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Open Order</td><td class="number">'.locale_number_format($PastDueSup,0).'</td>';
	for ($i=0; $i<9; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklySup[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Planned</td><td class="number">'.locale_number_format($PastDuePlan,0).'</td>';
	for ($i=0; $i<9; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyPlan[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Proj Avail</td><td class="number">'.locale_number_format($PastDueAvail,0).'</td>';
	for ($i=0; $i<9; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyAvail[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Planned Acc</td><td class="number">'.locale_number_format($PastDuePlan,0).'</td>';
	for ($i=0; $i<9; $i++) $HTML .= '<td class="number">'.locale_number_format($PlannedAccum[$i],0).'</td>';
	$HTML .= '</tr></table>';

	$HTML .= '<table>
		<tr>
			<th></th>';
	for ($i=9; $i<19; $i++) {
		$HTML .= '<th>'.htmlspecialchars(DateAdd($Today,'w',$i)).'</th>';
	}
	$HTML .= '</tr>
		<tr><td class="section">Gross Reqts</td>';
	for ($i=9; $i<19; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyReq[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Open Order</td>';
	for ($i=9; $i<19; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklySup[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Planned</td>';
	for ($i=9; $i<19; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyPlan[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Proj Avail</td>';
	for ($i=9; $i<19; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyAvail[$i],0).'</td>';
	$HTML .= '</tr>
		<tr><td class="section">Planned Acc</td>';
	for ($i=9; $i<19; $i++) $HTML .= '<td class="number">'.locale_number_format($PlannedAccum[$i],0).'</td>';
	$HTML .= '</tr></table>';

	$HTML .= '<table>
		<tr>
			<th></th>';
	for ($i=19; $i<28; $i++) {
		$HTML .= '<th>'.htmlspecialchars(DateAdd($Today,'w',$i)).'</th>';
	}
	$HTML .= '<th>Future</th></tr>
		<tr><td class="section">Gross Reqts</td>';
	for ($i=19; $i<28; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyReq[$i],0).'</td>';
	$HTML .= '<td class="number">'.locale_number_format($FutureReq,0).'</td></tr>
		<tr><td class="section">Open Order</td>';
	for ($i=19; $i<28; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklySup[$i],0).'</td>';
	$HTML .= '<td class="number">'.locale_number_format($FutureSup,0).'</td></tr>
		<tr><td class="section">Planned</td>';
	for ($i=19; $i<28; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyPlan[$i],0).'</td>';
	$HTML .= '<td class="number">'.locale_number_format($FuturePlan,0).'</td></tr>
		<tr><td class="section">Proj Avail</td>';
	for ($i=19; $i<28; $i++) $HTML .= '<td class="number">'.locale_number_format($WeeklyAvail[$i],0).'</td>';
	$HTML .= '<td class="number">'.locale_number_format($FutureAvail,0).'</td></tr>
		<tr><td class="section">Planned Acc</td>';
	for ($i=19; $i<28; $i++) $HTML .= '<td class="number">'.locale_number_format($PlannedAccum[$i],0).'</td>';
	$HTML .= '<td class="number">'.locale_number_format($FuturePlannedaccum,0).'</td></tr>
	</table>';

	// Demand/Supply details
	$HTML .= '<h4>Demand / Supply Details</h4>
	<table>
		<tr>
			<th colspan="5">' . _('Demand') . '</th>
			<th colspan="6">' . _('Supply') . '</th>
		</tr>
		<tr>
			<th>Dem Type</th>
			<th>Where Required</th>
			<th>Order</th>
			<th>Quantity</th>
			<th>Due Date</th>
			<th>Order No.</th>
			<th>Sup Type</th>
			<th>For</th>
			<th>Quantity</th>
			<th>Due Date</th>
			<th>MRP Date</th>
		</tr>';

	$i = 0;
	while ((isset($Supplies[$i]) && mb_strlen($Supplies[$i]['part']) > 1)
		|| (isset($Requirements[$i]) && mb_strlen($Requirements[$i]['part']) > 1)) {

		$HTML .= '<tr>';
		// Demand
		if (isset($Requirements[$i]['part']) && mb_strlen($Requirements[$i]['part']) > 1) {
			$FormatedReqDueDate = ConvertSQLDate($Requirements[$i]['daterequired']);
			$HTML .= '<td>' . htmlspecialchars($Requirements[$i]['mrpdemandtype']) . '</td>
				<td>' . htmlspecialchars($Requirements[$i]['whererequired']) . '</td>
				<td>' . htmlspecialchars($Requirements[$i]['orderno']) . '</td>
				<td class="number">' . locale_number_format($Requirements[$i]['quantity'],$MyRow['decimalplaces']) . '</td>
				<td>' . htmlspecialchars($FormatedReqDueDate) . '</td>';
		} else {
			$HTML .= '<td colspan="5"></td>';
		}
		// Supply
		if (isset($Supplies[$i]) && mb_strlen($Supplies[$i]['part']) > 1) {
			$SupType = $Supplies[$i]['ordertype'];
			if ($SupType == 'QOH' || $SupType == 'PO' || $SupType == 'WO') {
				$DisplayType = $SupType;
				$ForType = ' ';
			} else {
				$DisplayType = 'Planned';
				$ForType = $SupType;
			}
			$FormatedSupDueDate = ConvertSQLDate($Supplies[$i]['duedate']);
			$FormatedSupMRPDate = ConvertSQLDate($Supplies[$i]['mrpdate']);
			$OrderNo = ($SupType == 'QOH' OR $SupType == 'REORD') ? ' ' : $Supplies[$i]['orderno'];
			$HTML .= '<td>' . htmlspecialchars($OrderNo) . '</td>
				<td>' . htmlspecialchars($DisplayType) . '</td>
				<td>' . htmlspecialchars($ForType) . '</td>
				<td class="number">' . locale_number_format($Supplies[$i]['supplyquantity'],$MyRow['decimalplaces']) . '</td>
				<td>' . htmlspecialchars($FormatedSupDueDate) . '</td>
				<td>' . htmlspecialchars($FormatedSupMRPDate) . '</td>';
		} else {
			$HTML .= '<td colspan="6"></td>';
		}
		$HTML .= '</tr>';
		$i++;
	}
	$HTML .= '</table>
	</body></html>';

	// Generate PDF with DomPDF
	$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
	$DomPDF->loadHtml($HTML);
	$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');
	$DomPDF->render();
	$DomPDF->stream($_SESSION['DatabaseName'] . '_MRPReport_' . date('Y-m-d') . '.pdf', array("Attachment" => false));

} else { /*The option to print PDF was not hit so display form */

	$Title=__('MRP Report');
	$ViewTopic = 'MRP';
	$BookMark = '';
	include(__DIR__ . '/includes/header.php');

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
        .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; align-items: start; }
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

        /* Tables */
        .monochromatic-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .monochromatic-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 1rem; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--db-border); }
        .monochromatic-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
        .monochromatic-table tr:hover td { background: #f8fafc; }
        
        .db-id-link { color: var(--db-primary); font-weight: 700; text-decoration: none; border-bottom: 1px dashed transparent; }
        .db-id-link:hover { border-bottom-color: var(--db-primary); }

        /* Empty State */
        .db-empty-state { text-align: center; padding: 4rem 2rem; color: var(--db-text-muted); }
        .db-empty-icon { font-size: 3rem; margin-bottom: 1.5rem; opacity: 0.3; color: var(--db-primary); }
    </style>

    <div class="db-page">
        <div class="db-centered">
            <div class="db-page-header">
                <div>
                    <div class="db-breadcrumb">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        ' . __('Production Planning / MRP') . '
                    </div>
                    <h1 class="db-page-title">' . $Title . '</h1>
                </div>
            </div>';

	if (isset($_POST['PrintPDF'])) {
		prnMsg(__('This report shows the MRP calculation for a specific item - a part code must be selected'),'warn');
	}

	// Always show the search facilities
	$SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
	$Result1 = DB_query($SQL);
	
    if (DB_num_rows($Result1) == 0) {
		echo '<div class="db-card" style="border-color: var(--db-border); background: #fef2f2;">
                <div class="db-card-body" style="text-align:center; padding:3rem;">
                    <div style="font-size:2rem; color:hsl(0, 84%, 60%); margin-bottom:1rem;"><i class="fas fa-exclamation-triangle"></i></div>
                    <h3 style="color:hsl(0, 84%, 30%);">' . __('Problem Report') . '</h3>
                    <p style="color:hsl(0, 84%, 40%);">' . __('There are no stock categories currently defined please use the link below to set them up') . '</p>
                    <a href="' . $RootPath . '/StockCategories.php" class="db-btn db-btn-primary" style="background:hsl(0, 84%, 60%);">' . __('Define Stock Categories') . '</a>
                </div>
              </div>';
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

    echo '<div class="db-main-grid">
            <!-- Sidebar: Search Filters -->
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        ' . __('Inventory Search') . '
                    </h3>
                </div>
                <div class="db-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="searchForm">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('In Stock Category') . '</label>
                                <select name="StockCat" class="db-select">';
                                    if (!isset($_POST['StockCat'])) $_POST['StockCat'] = '';
                                    echo '<option value="All" ' . ($_POST['StockCat'] == 'All' ? 'selected' : '') . '>' . __('All Categories') . '</option>';
                                    while ($MyRow1 = DB_fetch_array($Result1)) {
                                        echo '<option value="' . $MyRow1['categoryid'] . '" ' . ($MyRow1['categoryid'] == $_POST['StockCat'] ? 'selected' : '') . '>' . $MyRow1['categorydescription'] . '</option>';
                                    }
    echo '                      </select>
                            </div>
                            <div class="db-field">
                                <label class="db-label">' . __('Description Keywords') . '</label>
                                <input type="text" name="Keywords" class="db-input" placeholder="' . __('Enter partial description...') . '" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" autofocus>
                            </div>
                            <div class="db-field">
                                <label class="db-label">' . __('Stock Code Extract') . '</label>
                                <input type="text" name="StockCode" class="db-input" placeholder="' . __('Enter partial code...') . '" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '">
                            </div>
                            <button type="submit" name="Search" class="db-btn db-btn-primary" style="width:100%; margin-top:0.5rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                ' . __('Search Now') . '
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Content: Results -->
            <div class="db-card">';

	if (!isset($_POST['Search']) && !isset($SearchResult)) {
		echo '<div class="db-empty-state">
                <div class="db-empty-icon"><i class="fas fa-search"></i></div>
                <h3 style="color:var(--db-primary-dark);">' . __('Ready for Analysis') . '</h3>
                <p style="max-width:300px; margin: 1rem auto;">' . __('Use the filters on the left to find a specific inventory item and analyze its MRP supply and demand.') . '</p>
              </div>';
	}

} /*end of else not PrintPDF */

// query for list of record(s)
if (isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	$_POST['Search']='Search';
}

if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) {
		$_POST['PageOffset'] = 1;
	}
	if ($_POST['Keywords'] AND $_POST['StockCode']) {
		prnMsg( __('Stock description keywords have been used in preference to the Stock code extract entered'), 'info' );
	}
	if ($_POST['Keywords']) {
		$_POST['Keywords'] = mb_strtoupper($_POST['Keywords']);
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND stockmaster.description " . LIKE . " '".$SearchString."' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND description " . LIKE . " '".$SearchString."' AND categoryid='" . $_POST['StockCat'] . "' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		}
	} elseif (isset($_POST['StockCode'])) {
		$_POST['StockCode'] = mb_strtoupper($_POST['StockCode']);
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.mbflag, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.mbflag, sum(locstock.quantity) as qoh, stockmaster.units, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%' AND categoryid='" . $_POST['StockCat'] . "' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		}
	} elseif (!isset($_POST['StockCode']) AND !isset($_POST['Keywords'])) {
		if ($_POST['StockCat'] == 'All') {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.mbflag, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		} else {
			$SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.mbflag, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.decimalplaces FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid AND categoryid='" . $_POST['StockCat'] . "' GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
		}
	}
	$ErrMsg = __('No stock items were returned by the SQL because');
	$SearchResult = DB_query($SQL, $ErrMsg);
	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('No stock items were returned by this search please re-enter alternative criteria to try again'), 'info');
	}
	unset($_POST['Search']);
}

/* display list if there is more than one record */
if (isset($SearchResult) AND !isset($_POST['Select'])) {
	
    $ListCount = DB_num_rows($SearchResult);
	if ($ListCount > 0) {
        echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        ' . __('Search Results') . ' (' . $ListCount . ')
                    </h3>
                </div>';

		$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
		if (isset($_POST['Next'])) {
			if ($_POST['PageOffset'] < $ListPageMax) $_POST['PageOffset'] = $_POST['PageOffset'] + 1;
		}
		if (isset($_POST['Previous'])) {
			if ($_POST['PageOffset'] > 1) $_POST['PageOffset'] = $_POST['PageOffset'] - 1;
		}
		if ($_POST['PageOffset'] > $ListPageMax) $_POST['PageOffset'] = $ListPageMax;

		if ($ListPageMax > 1) {
			echo '<div style="padding: 1rem 1.5rem; background: #fafafa; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between;">
					<div style="font-size: 0.8125rem; font-weight: 600; color: var(--db-text-muted);">' . __('Page') . ' ' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . '</div>
					<div style="display: flex; gap: 8px;">
                        <input type="hidden" name="Keywords" value="'.$_POST['Keywords'].'" />
                        <input type="hidden" name="StockCat" value="'.$_POST['StockCat'].'" />
                        <input type="hidden" name="StockCode" value="'.$_POST['StockCode'].'" />
                        <input type="hidden" name="PageOffset" value="'.$_POST['PageOffset'].'" />
                        <button type="submit" name="Previous" class="db-btn" style="padding: 0.5rem 1rem; background: #fff; border: 1px solid var(--db-border);" ' . ($_POST['PageOffset'] <= 1 ? 'disabled' : '') . '>' . __('Prev') . '</button>
                        <button type="submit" name="Next" class="db-btn" style="padding: 0.5rem 1rem; background: #fff; border: 1px solid var(--db-border);" ' . ($_POST['PageOffset'] >= $ListPageMax ? 'disabled' : '') . '>' . __('Next') . '</button>
                    </div>
				</div>';
		}

		echo '<div class="db-table-wrapper">
                <table class="monochromatic-table">
					<thead>
						<tr>
							<th>' . __('Select') . '</th>
							<th>' . __('Item Code') . '</th>
							<th>' . __('Description') . '</th>
							<th style="text-align:right;">' . __('Qty On Hand') . '</th>
							<th>' . __('Units') . '</th>
							<th style="text-align:center;">' . __('Status') . '</th>
						</tr>
					</thead>
					<tbody>';

		if (DB_num_rows($SearchResult) <> 0) {
			DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		}
		$j = 1;
		$RowIndex = 0;
		while (($MyRow = DB_fetch_array($SearchResult)) AND ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			$QOH = ($MyRow['mbflag'] == 'D') ? 'N/A' : locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']);
			
			echo '<tr>
    				<td>
                        <button type="submit" name="Select" value="'.$MyRow['stockid']. '" class="db-btn db-btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.75rem;">
                            <i class="fas fa-file-pdf"></i> ' . __('Analyze') . '
                        </button>
                    </td>
                    <td style="font-weight:700; color:var(--db-primary-dark);">' . $MyRow['stockid'] . '</td>
    				<td style="font-weight:600;">' . $MyRow['description'] . '</td>
    				<td style="text-align:right; font-family: monospace; font-weight:700;">' . $QOH . '</td>
    				<td style="font-size:0.75rem; color:var(--db-text-muted); font-weight:700;">' . $MyRow['units'] . '</td>
    				<td style="text-align:center;">
                        <a target="_blank" href="' . $RootPath . '/StockStatus.php?StockID=' . $MyRow['stockid'] .'" class="db-id-link">
                            <i class="fas fa-chart-line"></i> ' . __('View') . '
                        </a>
                    </td>
				</tr>';
			$RowIndex++;
		}
		echo '</tbody></table></div></form>';
	}
    echo '</div></div></div></div>'; // Close db-card, db-main-grid, db-centered, db-page
	include(__DIR__ . '/includes/footer.php');
}

/* end display list if there is more than one record */

function PrintHeader($pdf,&$YPos,&$PageNumber,$Page_Height,$Top_Margin,$Left_Margin,
					 $Page_Width,$Right_Margin) {

	$LineHeight=12;
	/*PDF page header for MRP Report */
	if ($PageNumber>1){
		$pdf->newPage();
	}

	$FontSize=9;
	$YPos= $Page_Height-$Top_Margin;

	$pdf->addTextWrap($Left_Margin,$YPos,300,$FontSize,$_SESSION['CompanyRecord']['coyname']);

	$YPos -=$LineHeight;

	$pdf->addTextWrap($Left_Margin,$YPos,300,$FontSize,__('MRP Report'));
	$pdf->addTextWrap($Page_Width-$Right_Margin-110,$YPos,160,$FontSize,__('Printed') . ': ' .
		 date($_SESSION['DefaultDateFormat']) . '   ' . __('Page') . ' ' . $PageNumber,'left');

	$YPos -=(2*$LineHeight);

	/*set up the headings */
	$Xpos = $Left_Margin+1;

	$FontSize=8;
	$YPos =$YPos - (2*$LineHeight);
	$PageNumber++;

} // End of PrintHeader function
