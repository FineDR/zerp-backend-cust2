<?php

// Create mrpdemands based on sales order history

require(__DIR__ . '/includes/session.php');

$Title = __('MRP Create Demands');
$ViewTopic = 'MRP';
$BookMark = 'MRP_MasterSchedule';

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}
if (isset($_POST['DistDate'])){$_POST['DistDate'] = ConvertSQLDate($_POST['DistDate']);}

if (isset($_POST['submit'])) {
    // Logic: Submission
	$InputError=0;
	if (isset($_POST['FromDate']) AND !Is_Date($_POST['FromDate'])){ $Msg = __('The date from is invalid'); $InputError=1; }
	if (isset($_POST['ToDate']) AND !Is_Date($_POST['ToDate'])){ $Msg = __('The date to is invalid'); $InputError=1; }
	if (!$InputError && Date1GreaterThanDate2($_POST['FromDate'], $_POST['ToDate'])){ $Msg = __('The date to must be after from'); $InputError=1; }
	if (isset($_POST['DistDate']) AND !Is_Date($_POST['DistDate'])){ $Msg = __('The distribution start date is invalid'); $InputError=1; }

	if ($InputError==1){
		prnMsg($Msg,'error');
	} else {
    	$WhereLocation = ($_POST['Location']!= 'All') ? " AND salesorders.fromstkloc ='" . $_POST['Location'] . "' " : " ";
        $catList = "'" . implode("','",$_POST['Categories'] ?? []) . "'";
    	$SQL= "SELECT salesorderdetails.stkcode, SUM(salesorderdetails.quantity) AS totqty, SUM(salesorderdetails.qtyinvoiced) AS totqtyinvoiced, SUM(salesorderdetails.quantity * salesorderdetails.unitprice ) AS totextqty
    			FROM salesorders INNER JOIN salesorderdetails ON salesorders.orderno = salesorderdetails.orderno
    			INNER JOIN locationusers ON locationusers.loccode=salesorders.fromstkloc AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1
    			INNER JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
    			WHERE orddate >='" . FormatDateForSQL($_POST['FromDate']) ."' AND orddate <='" . FormatDateForSQL($_POST['ToDate']) .  "' " . $WhereLocation . "
    			AND stockmaster.categoryid IN ($catList) AND stockmaster.discontinued = 0 AND salesorders.quotation=0
    			GROUP BY salesorderdetails.stkcode";
    	$Result = DB_query($SQL);
    
    	$Multiplier = max(1, (float)filter_number_format($_POST['Multiplier']));
    	$ExcludeQty = max(1, (float)filter_number_format($_POST['ExcludeQuantity']));
    	$ExcludeAmount = (float)filter_number_format($_POST['ExcludeAmount']);
    
    	$FormatedDistdate = FormatDateForSQL($_POST['DistDate']);
        $sep = mb_strpos($FormatedDistdate,"/") ? "/" : (mb_strpos($FormatedDistdate,"-") ? "-" : ".");
        list($yyyy,$mm,$dd) = explode($sep, $FormatedDistdate);

    	$DateArray[0] = $FormatedDistdate;
    	$CalendarSQL = "SELECT cal2.calendardate FROM mrpcalendar LEFT JOIN mrpcalendar as cal2 ON mrpcalendar.daynumber = cal2.daynumber WHERE mrpcalendar.calendardate = '".$DateArray[0]."' AND cal2.manufacturingflag='1' GROUP BY cal2.calendardate";
    	$Resultdate = DB_query($CalendarSQL);
    	if ($MyRowdate = DB_fetch_array($Resultdate)) $DateArray[0] = $MyRowdate[0];
    
    	$DateStr = date('Y-m-d',mktime(0,0,0,$mm,$dd,$yyyy));
    	for ($i = 1; $i < (int)$_POST['PeriodNumber']; $i++) {
    		$DateStr = date('Y-m-d', strtotime($DateStr . ($_POST['Period'] == 'weekly' ? ' + 1 week' : ' + 1 month')));
    		$DateArray[$i] = $DateStr;
    		$CalendarSQL = "SELECT cal2.calendardate FROM mrpcalendar LEFT JOIN mrpcalendar as cal2 ON mrpcalendar.daynumber = cal2.daynumber WHERE mrpcalendar.calendardate = '".$DateArray[$i]."' AND cal2.manufacturingflag='1' GROUP BY cal2.calendardate";
    		$Resultdate = DB_query($CalendarSQL);
    		if ($MyRowdate = DB_fetch_array($Resultdate)) $DateArray[$i] = $MyRowdate[0];
    	}
    
    	$TotalRecords = 0;
    	while ($MyRow = DB_fetch_array($Result)) {
    		if (($MyRow['totqty'] >= $ExcludeQty) && ($MyRow['totextqty'] >= $ExcludeAmount)) {
    			$TotalQty = $MyRow['totqtyinvoiced'] * $Multiplier;
    			$pn = (int)$_POST['PeriodNumber'];
    			$WholeNumber = floor($TotalQty / $pn);
    			$Remainder = ($TotalQty % $pn);
    			for ($i = 0; $i < $pn; $i++) {
    				$DemandQty = $WholeNumber + ($i < $Remainder ? 1 : 0);
    				if ($DemandQty > 0) {
    					DB_query("INSERT INTO mrpdemands (stockid, mrpdemandtype, quantity, duedate) VALUES ('" . $MyRow['stkcode'] . "', '" . $_POST['MRPDemandtype'] . "', '" . $DemandQty . "', '" . $DateArray[$i] . "')");
    					$TotalRecords++;
    				}
    			}
    		}
    	}
    	prnMsg( $TotalRecords . ' ' . __('records have been created'),'success');
    }
}

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
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 800px; margin: 0 auto; }
    .db-page-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; }
    .db-card-header { padding: 1.25rem; border-bottom: 1px solid var(--db-border); background: #fff; }
    .db-card-title { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 2rem; }
    
    .db-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }
    @media (max-width: 600px) { .db-grid { grid-template-columns: 1fr; } }
    
    .db-field { margin-bottom: 1.25rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.375rem; display: block; }
    .db-input, .db-select { 
        padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fff; font-size: 0.875rem; width: 100%; transition: all 0.2s;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 700; font-size: 0.9375rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
    }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div class="db-breadcrumb">' . __('MRP') . ' / ' . __('Master Schedule') . '</div>
            <h1 class="db-page-title">' . __('Generate Demand from Sales') . '</h1>
        </header>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">' . __('Source Parameters') . '</h3>
                </div>
                <div class="db-card-body">
                    <div class="db-grid">
                        <div class="db-field">
                            <label class="db-label">' . __('Demand Type to Create') . '</label>
                            <select name="MRPDemandtype" class="db-select">';
    $Res = DB_query("SELECT mrpdemandtype, description FROM mrpdemandtypes");
    while ($Row = DB_fetch_array($Res)) echo '<option value="' . $Row['mrpdemandtype'] . '">' . $Row['mrpdemandtype'] . ' - ' .$Row['description'] . '</option>';
    echo '                  </select>
                        </div>
                        <div class="db-field">
                            <label class="db-label">' . __('Inventory Categories') . '</label>
                            <select name="Categories[]" multiple="multiple" class="db-select" style="height: 100px;" required>';
    $Res = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
    while ($Row = DB_fetch_array($Res)) echo '<option value="' . $Row['categoryid'] . '">' . $Row['categorydescription'] .'</option>';
    echo '                  </select>
                        </div>
                    </div>

                    <div class="db-grid">
                        <div class="db-field"><label class="db-label">' . __('From Sales Date') . '</label><input type="date" name="FromDate" class="db-input" value="' . date('Y-m-d', strtotime('-1 year')) . '" /></div>
                        <div class="db-field"><label class="db-label">' . __('To Sales Date') . '</label><input type="date" name="ToDate" class="db-input" value="' . date('Y-m-d') . '" /></div>
                    </div>

                    <hr style="border:0; border-top: 1px solid var(--db-border); margin: 1rem 0 2rem;">
                    <h3 class="db-card-title" style="margin-bottom: 1.25rem;">' . __('Distribution & Multipliers') . '</h3>

                    <div class="db-grid">
                        <div class="db-field"><label class="db-label">' . __('Distribution Start') . '</label><input type="date" name="DistDate" class="db-input" value="' . date('Y-m-d') . '" /></div>
                        <div class="db-field"><label class="db-label">' . __('Frequence') . '</label><select name="Period" class="db-select"><option value="weekly">' . __('Weekly') . '</option><option value="monthly">' . __('Monthly') . '</option></select></div>
                    </div>

                    <div class="db-grid">
                        <div class="db-field"><label class="db-label">' . __('Num Periods') . '</label><input type="number" name="PeriodNumber" class="db-input" value="4" /></div>
                        <div class="db-field"><label class="db-label">' . __('Multiplier') . '</label><input type="text" name="Multiplier" class="db-input" value="1.0" /></div>
                    </div>

                    <div class="db-grid">
                        <div class="db-field"><label class="db-label">' . __('Min Total Qty') . '</label><input type="number" name="ExcludeQuantity" class="db-input" value="1" /></div>
                        <div class="db-field"><label class="db-label">' . __('Min Total Dollars') . '</label><input type="number" name="ExcludeAmount" class="db-input" value="0" /></div>
                    </div>

                    <div class="db-field" style="margin-top: 2rem;">
                        <button type="submit" name="submit" class="db-btn db-btn-primary">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            ' . __('Process Forecast Generation') . '
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>';

include(__DIR__ . '/includes/footer.php');
?>
