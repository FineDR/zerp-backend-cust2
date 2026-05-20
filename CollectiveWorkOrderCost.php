<?php

/* Multiple work orders cost review */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Work Orders');
$ViewTopic = 'Manufacturing';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['DateFrom'])){$_POST['DateFrom'] = ConvertSQLDate($_POST['DateFrom']);}
if (isset($_POST['DateTo'])){$_POST['DateTo'] = ConvertSQLDate($_POST['DateTo']);}

    echo '<style>
        :root {
            --db-primary: hsl(197, 92%, 47%);
            --db-primary-hover: hsl(197, 92%, 38%);
            --db-primary-dark: hsl(197, 75%, 22%);
            --db-primary-soft: hsl(197, 40%, 100%);
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
        .db-main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; align-items: start; }
        @media (max-width: 1024px) { .db-main-grid { grid-template-columns: 1fr; } }

        /* Cards */
        .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
        .db-card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; justify-content: space-between; }
        .db-card-title { font-size: 0.875rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; display: flex; align-items: center; gap: 10px; }
        .db-card-body { padding: 1.5rem; }

        /* Tables */
        .db-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.875rem 1rem; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em; border-bottom: 2px solid var(--db-border); }
        .db-table td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
        .db-table tr:hover td { background: #f8fafc; }
        .db-table .number { text-align: right; }
        .db-table .date { white-space: nowrap; }

        /* Forms */
        .db-field-group { display: flex; flex-direction: column; gap: 1rem; }
        .db-field { display: flex; flex-direction: column; gap: 0.375rem; }
        .db-label { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.02em; }
        .db-input, .db-select { 
            padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--db-border); background: #fff; font-size: 0.875rem; transition: all 0.2s; width: 100%;
        }
        .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }

        /* Buttons */
        .db-btn { 
            display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; 
            border-radius: 6px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: all 0.2s; border: none;
        }
        .db-btn-primary { background: var(--db-primary); color: white; }
        .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
        .db-btn-secondary { background: #fff; border: 1px solid var(--db-border); color: var(--db-text-main); }
        .db-btn-secondary:hover { border-color: var(--db-primary); color: var(--db-primary); }

        .db-mono { font-family: "JetBrains Mono", monospace; }
        .db-badge { padding: 3px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); border: 1px solid var(--db-primary); }
        
        .db-summary-card { background: var(--db-primary-dark); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; }
        .db-summary-val { font-size: 2rem; font-weight: 900; }
    </style>

    <div class="db-page">
        <div class="db-centered">
            <!-- Part 1: Report Output (Top Priority if Submitted) -->';

if (isset($_POST['Submit'])) {
    $WOSelected = '';
    $i = 0;
    foreach ($_POST as $Key=>$Value) {
        if (substr($Key,0,3) == 'WO_'){
            if ($i>0) $WOSelected .=",";
            if ($Value == 'on') {
                $WOSelected .= substr($Key,3);
            }
            $i++;
        }
    }

    echo '<div class="db-page-header">
            <div>
                <div class="db-breadcrumb">' . __('Manufacturing / Analytics') . '</div>
                <h1 class="db-page-title">' . __('Collective Work Order Cost') . '</h1>
            </div>
            <a href="' . $_SERVER['PHP_SELF'] . '" class="db-btn db-btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                ' . __('Back to Selection') . '
            </a>
          </div>';

    if (empty($WOSelected)) {
        prnMsg(__('There are no work orders selected'),'error');
    } else {
        $SQL = "SELECT stockmoves.stockid,
            stockmaster.description,
            stockmaster.decimalplaces,
            trandate,
            qty,
            reference,
            stockmoves.standardcost
            FROM stockmoves INNER JOIN stockmaster
            ON stockmoves.stockid=stockmaster.stockid
            WHERE stockmoves.type=28
            AND reference IN (" . $WOSelected . ")
            ORDER BY reference";
        $Result = DB_query($SQL);
        
        if (DB_num_rows($Result)>0) {
            $TotalCost = 0;
            $TempResults = [];
            while ($MyRow = DB_fetch_array($Result)) {
                $IssuedQty = - $MyRow['qty'];
                $IssuedCost = $IssuedQty * $MyRow['standardcost'];
                $TotalCost += $IssuedCost;
                $TempResults[] = array_merge($MyRow, ['IssuedQty' => $IssuedQty, 'IssuedCost' => $IssuedCost]);
            }

            echo '<div class="db-summary-card">
                    <div>
                        <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; opacity: 0.8; letter-spacing: 0.1em;">' . __('Accumulated Total Cost') . '</div>
                        <div class="db-summary-val">' . locale_number_format($TotalCost, 2) . '</div>
                    </div>
                    <div style="text-align: right; opacity: 0.8; font-size: 0.875rem;">
                        ' . count($TempResults) . ' ' . __('Individual Issues Analyzed') . '<br>
                        ' . __('Impacts') . ' ' . $i . ' ' . __('Work Orders') . '
                    </div>
                  </div>';

            echo '<div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">' . __('Detailed Issue History') . '</h3>
                    </div>
                    <div class="db-card-body" style="padding:0;">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>' . __('Item') . '</th>
                                    <th>' . __('Description') . '</th>
                                    <th>' . __('Date') . '</th>
                                    <th class="number">' . __('Qty') . '</th>
                                    <th class="number">' . __('Cost') . '</th>
                                    <th>' . __('WO#') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

            foreach ($TempResults as $R) {
                echo '<tr>
                        <td class="db-mono">' . $R['stockid'] . '</td>
                        <td style="font-weight:600;">' . $R['description'] . '</td>
                        <td class="date db-mono">' . $R['trandate'] . '</td>
                        <td class="number db-mono">' . locale_number_format($R['IssuedQty'], $R['decimalplaces']) . '</td>
                        <td class="number db-mono" style="font-weight:800; color:var(--db-primary-dark);">' . locale_number_format($R['IssuedCost'], 2) . '</td>
                        <td class="db-mono"><span class="db-badge">WO' . $R['reference'] . '</span></td>
                    </tr>';
            }
            echo '      </tbody>
                        <tfoot>
                            <tr style="background: var(--db-primary-soft);">
                                <td colspan="4" style="text-align:right; font-weight:900; font-size: 1rem; color:var(--db-primary-dark);">' . __('GRAND TOTAL') . '</td>
                                <td class="number" style="font-weight:900; font-size: 1.125rem; color:var(--db-primary-dark);">' . locale_number_format($TotalCost, 2) . '</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                  </div>
                </div>';
        } else {
             prnMsg(__('There are no cost data available for the selected work orders'), 'info');
        }
    }
    echo '</div></div>';
    include(__DIR__ . '/includes/footer.php');
    exit();
}

// Logic for Search / Selection
if (isset($_GET['WO'])) { $SelectedWO = $_GET['WO']; } 
elseif (isset($_POST['WO'])) { $SelectedWO = $_POST['WO']; }

if (isset($_GET['SelectedStockItem'])) { $SelectedStockItem = $_GET['SelectedStockItem']; } 
elseif (isset($_POST['SelectedStockItem'])) { $SelectedStockItem = $_POST['SelectedStockItem']; }

if (isset($_POST['ResetPart'])) { unset($SelectedStockItem); }

if (isset($SelectedWO) && $SelectedWO != '') {
    $SelectedWO = trim($SelectedWO);
    if (!is_numeric($SelectedWO)) {
          prnMsg(__('The work order number entered MUST be numeric'),'warn');
          unset($SelectedWO);
    }
}

$StockItemsResult = null;
if (isset($_POST['SearchParts'])) {
    if ($_POST['Keywords']) {
        $SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, SUM(locstock.quantity) AS qoh, stockmaster.units
                FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid
                AND stockmaster.description LIKE '" . $SearchString . "' AND stockmaster.categoryid='" . $_POST['StockCat']. "' AND stockmaster.mbflag='M'
                GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
    } elseif (isset($_POST['StockCode'])) {
        $SQL = "SELECT stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, sum(locstock.quantity) as qoh, stockmaster.units
                FROM stockmaster, locstock WHERE stockmaster.stockid=locstock.stockid
                AND stockmaster.stockid LIKE '%" . $_POST['StockCode'] . "%' AND stockmaster.categoryid='" . $_POST['StockCat'] . "' AND stockmaster.mbflag='M'
                GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces, stockmaster.units ORDER BY stockmaster.stockid";
    }
    $StockItemsResult = DB_query($SQL);
}

echo '<div class="db-page-header">
        <div>
            <div class="db-breadcrumb">' . __('Production / Costing') . '</div>
            <h1 class="db-page-title">' . __('Collective Cost Review') . '</h1>
        </div>
      </div>

      <div class="db-main-grid">
        <div class="db-field-group">';

// Intermediate Results: Work Orders List
if (!isset($_POST['StockCode']) && !isset($_POST['Keywords'])) {
    if (!isset($_POST['StockLocation'])) { $_POST['StockLocation'] = $_SESSION['UserStockLocation']; }
    if (!isset($_POST['ClosedOrOpen'])) { $_POST['ClosedOrOpen'] = 'Open_Only'; }
    if (!isset($_POST['DateFrom'])) { $_POST['DateFrom'] = date($_SESSION['DefaultDateFormat'], strtotime('-1 month')); }
    if (!isset($_POST['DateTo'])) { $_POST['DateTo'] = date($_SESSION['DefaultDateFormat']); }

    $ClosedOrOpen = ($_POST['ClosedOrOpen']=='Open_Only') ? ' AND workorders.closed=0' : (($_POST['ClosedOrOpen']=='Closed_Only') ? ' AND workorders.closed=1' : '');
    $StartDateFrom = " AND workorders.startdate>='" . FormatDateForSQL($_POST['DateFrom']) . "'";
    $StartDateTo = " AND workorders.startdate<='" . FormatDateForSQL($_POST['DateTo']) . "'";

    if (isset($SelectedWO) && $SelectedWO != '') {
        $SQL = "SELECT workorders.wo, woitems.stockid, stockmaster.description, stockmaster.decimalplaces, woitems.qtyreqd, woitems.qtyrecd, workorders.requiredby, workorders.startdate
                FROM workorders INNER JOIN woitems ON workorders.wo=woitems.wo INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid
                WHERE workorders.wo='". $SelectedWO ."' ORDER BY workorders.wo";
    } else {
        $StockFilter = isset($SelectedStockItem) ? " AND woitems.stockid='". $SelectedStockItem ."'" : "";
        $SQL = "SELECT workorders.wo, woitems.stockid, stockmaster.description, stockmaster.decimalplaces, woitems.qtyreqd, woitems.qtyrecd, workorders.requiredby, workorders.startdate
                FROM workorders INNER JOIN woitems ON workorders.wo=woitems.wo INNER JOIN stockmaster ON woitems.stockid=stockmaster.stockid
                WHERE workorders.loccode='" . $_POST['StockLocation'] . "' " . $StockFilter . $ClosedOrOpen . $StartDateFrom . $StartDateTo . "
                ORDER BY workorders.wo DESC";
    }

    $WorkOrdersResult = DB_query($SQL);
    
    if (DB_num_rows($WorkOrdersResult) > 0) {
        echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">' . __('Select Work Orders to Analyze') . '</h3>
                        <button type="submit" name="Submit" class="db-btn db-btn-primary">
                            ' . __('Analyze Costing') . '
                        </button>
                    </div>
                    <div class="db-card-body" style="padding:0;">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th style="width:40px;">' . __('Sel') . '</th>
                                    <th>' . __('WO#') . '</th>
                                    <th>' . __('Produced Item') . '</th>
                                    <th class="number">' . __('Qty Reqd') . '</th>
                                    <th class="number">' . __('Qty Recd') . '</th>
                                    <th>' . __('Start Date') . '</th>
                                    <th>' . __('Required') . '</th>
                                </tr>
                            </thead>
                            <tbody>';

        while ($MyRow = DB_fetch_array($WorkOrdersResult)) {
            echo '<tr>
                    <td><input type="checkbox" name="WO_', $MyRow['wo'], '" /></td>
                    <td class="db-mono" style="font-weight:700;">' . $MyRow['wo'] . '</td>
                    <td>' . $MyRow['stockid'] . ' - ' . $MyRow['description'] . '</td>
                    <td class="number db-mono">' . locale_number_format($MyRow['qtyreqd'], $MyRow['decimalplaces']) . '</td>
                    <td class="number db-mono">' . locale_number_format($MyRow['qtyrecd'], $MyRow['decimalplaces']) . '</td>
                    <td class="date db-mono">' . ConvertSQLDate($MyRow['startdate']) . '</td>
                    <td class="date db-mono">' . ConvertSQLDate($MyRow['requiredby']) . '</td>
                  </tr>';
        }
        echo '      </tbody>
                        </table>
                    </div>
                </div>
              </form>';
    } else {
        echo '<div class="db-card" style="border: 2px dashed var(--db-border); background: transparent; text-align:center; padding: 4rem;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3; margin-bottom:1rem;"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                <div style="color:var(--db-text-muted); font-weight:600;">' . __('No work orders found matching criteria.') . '</div>
              </div>';
    }
}

// Stock Item Search Results
if (isset($StockItemsResult)) {
    echo '<div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title">' . __('Matching Items') . '</h3></div>
            <div class="db-card-body" style="padding:0;">
                <table class="db-table">
                    <thead>
                        <tr>
                            <th>' . __('Code') . '</th>
                            <th>' . __('Description') . '</th>
                            <th class="number">' . __('On Hand') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
    while ($MyRow = DB_fetch_array($StockItemsResult)) {
        echo '<tr>
                <td><a href="' . $_SERVER['PHP_SELF'] . '?SelectedStockItem=' . $MyRow['stockid'] . '" class="db-mono" style="font-weight:700; color:var(--db-primary);">' . $MyRow['stockid'] . '</a></td>
                <td>' . $MyRow['description'] . '</td>
                <td class="number db-mono">' . locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']) . '</td>
              </tr>';
    }
    echo '      </tbody>
                </table>
            </div>
          </div>';
}

echo '  </div>

        <!-- Sidebar: Advanced Filters -->
        <div class="db-field-group">
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        ' . __('Find Work Orders') . '
                    </h3>
                </div>
                <div class="db-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('Location') . '</label>
                                <select name="StockLocation" class="db-select">';
    $SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1 WHERE locations.usedforwo = 1";
    $Res = DB_query($SQL);
    while ($L = DB_fetch_array($Res)) {
        $sel = ($_POST['StockLocation'] == $L['loccode']) ? 'selected' : '';
        echo '<option ' . $sel . ' value="' . $L['loccode'] . '">' . $L['locationname'] . '</option>';
    }
    echo '                      </select>
                            </div>
                            <div class="db-field">
                                <label class="db-label">' . __('Status') . '</label>
                                <select name="ClosedOrOpen" class="db-select">
                                    <option ' . ($_POST['ClosedOrOpen']=='All'?'selected':'') . ' value="All">' . __('All Orders') . '</option>
                                    <option ' . ($_POST['ClosedOrOpen']=='Open_Only'?'selected':'') . ' value="Open_Only">' . __('Open Only') . '</option>
                                    <option ' . ($_POST['ClosedOrOpen']=='Closed_Only'?'selected':'') . ' value="Closed_Only">' . __('Closed Only') . '</option>
                                </select>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div class="db-field">
                                    <label class="db-label">' . __('From') . '</label>
                                    <input name="DateFrom" type="date" class="db-input" value="' . FormatDateForSQL($_POST['DateFrom']) . '" />
                                </div>
                                <div class="db-field">
                                    <label class="db-label">' . __('To') . '</label>
                                    <input name="DateTo" type="date" class="db-input" value="' . FormatDateForSQL($_POST['DateTo']) . '" />
                                </div>
                            </div>
                            <button type="submit" name="SearchOrders" class="db-btn db-btn-primary">' . __('Search Orders') . '</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Filter by Manufactured Item') . '</h3></div>
                <div class="db-card-body">
                    <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <div class="db-field-group">
                            <div class="db-field">
                                <label class="db-label">' . __('Keyword') . '</label>
                                <input type="text" name="Keywords" class="db-input" placeholder="' . __('Description...') . '" />
                            </div>
                            <div class="db-field">
                                <label class="db-label">' . __('Category') . '</label>
                                <select name="StockCat" class="db-select">';
    $ResCat = DB_query("SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription");
    while ($C = DB_fetch_array($ResCat)) { echo '<option value="' . $C['categoryid'] . '">' . $C['categorydescription'] . '</option>'; }
    echo '                      </select>
                            </div>
                            <button type="submit" name="SearchParts" class="db-btn db-btn-secondary" style="width:100%;">' . __('Filter Items') . '</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
      </div>';

include(__DIR__ . '/includes/footer.php');
