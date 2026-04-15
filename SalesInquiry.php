<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Intelligence Dashboard');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Parameter Initialization
if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (!isset($_POST['FromDate'])) {
	$_POST['FromDate']=date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m'),1,date('Y')));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

$PartNumber = isset($_POST['PartNumber']) ? trim(mb_strtoupper($_POST['PartNumber'])) : '';
$PartNumberOp = $_POST['PartNumberOp'] ?? '=';
$DebtorNo = isset($_POST['DebtorNo']) ? trim(mb_strtoupper($_POST['DebtorNo'])) : '';
$DebtorNoOp = $_POST['DebtorNoOp'] ?? '=';
$DebtorName = isset($_POST['DebtorName']) ? trim(mb_strtoupper($_POST['DebtorName'])) : '';
$DebtorNameOp = $_POST['DebtorNameOp'] ?? '=';

if (!isset($_POST['ReportType'])) { $_POST['ReportType'] = 'Detail'; }
if (!isset($_POST['OrderType'])) { $_POST['OrderType'] = '0'; }
if (!isset($_POST['DateType'])) { $_POST['DateType'] = 'Order'; }
if (!isset($_POST['InvoiceType'])) { $_POST['InvoiceType'] = 'All'; }
if (!isset($_POST['LineStatus'])) { $_POST['LineStatus'] = 'All'; }
if (!isset($_POST['Category'])) { $_POST['Category'] = 'All'; }
if (!isset($_POST['Salesman'])) { $_POST['Salesman'] = 'All'; }
if (!isset($_POST['Area'])) { $_POST['Area'] = 'All'; }
if (!isset($_POST['SortBy'])) { $_POST['SortBy'] = 'salesorderdetails.orderno'; }
if (!isset($_POST['SummaryType'])) { $_POST['SummaryType'] = 'orderno'; }

$SaveSummaryType = $_POST['SummaryType'];
$ShowResults = isset($_POST['submit']);

// Function for temp stock moves remains essential for Invoice Date type
function TempStockmoves() {
	$FromDate = FormatDateForSQL($_POST['FromDate']);
	$ToDate = FormatDateForSQL($_POST['ToDate']);

	DB_query("CREATE TEMPORARY TABLE tempstockmoves LIKE stockmoves");
	DB_query("INSERT tempstockmoves SELECT * FROM stockmoves WHERE (stockmoves.type='10' OR stockmoves.type='11') AND stockmoves.trandate >='" . $FromDate . "' AND stockmoves.trandate <='" . $ToDate . "'");
	DB_query("UPDATE tempstockmoves, stockmoves SET tempstockmoves.reference = stockmoves.reference WHERE tempstockmoves.type='11' AND SUBSTR(tempstockmoves.reference,10,10) = stockmoves.transno AND tempstockmoves.stockid = stockmoves.stockid AND stockmoves.type ='10'");
}

echo '<div class="db-page">
        <div class="db-page-header">
            <div class="db-page-title">
                <i class="fas fa-chart-line"></i> ' . $Title . '
            </div>
            <div class="db-page-subtitle">
                ' . ($_POST['ReportType'] == 'Detail' ? __('Transaction Level Audit') : __('High-Level Trend Analysis')) . '
            </div>
        </div>

        <form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <!-- Analysis Sidebar -->
                <aside class="db-col-aside">
                    <div class="db-card">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-cog"></i> ' . __('Foundation') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Report Mode') . '</label>
                                <select name="ReportType" class="db-select" onchange="this.form.submit()">
                                    <option ' . ($_POST['ReportType'] == 'Detail' ? 'selected' : '') . ' value="Detail">' . __('Detail Log') . '</option>
                                    <option ' . ($_POST['ReportType'] == 'Summary' ? 'selected' : '') . ' value="Summary">' . __('Executive Summary') . '</option>
                                </select>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Analysis Basis') . '</label>
                                <select name="DateType" class="db-select" onchange="this.form.submit()">
                                    <option ' . ($_POST['DateType'] == 'Order' ? 'selected' : '') . ' value="Order">' . __('Order Date') . '</option>
                                    <option ' . ($_POST['DateType'] == 'Invoice' ? 'selected' : '') . ' value="Invoice">' . __('Invoice Date') . '</option>
                                </select>
                            </div>
                            <div class="db-grid-2">
                                <div class="db-form-group"><label class="db-label">' . __('From') . '</label><input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" /></div>
                                <div class="db-form-group"><label class="db-label">' . __('To') . '</label><input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" /></div>
                            </div>
                            
                            ' . ($_POST['ReportType'] == 'Summary' ? 
                                '<div class="db-form-group">
                                    <label class="db-label">' . __('Summarize By') . '</label>
                                    <select name="SummaryType" class="db-select">
                                        <option ' . ($_POST['SummaryType'] == 'orderno' ? 'selected' : '') . ' value="orderno">' . __('Order Number') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'transno' ? 'selected' : '') . ' value="transno">' . __('Transaction Number') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'stkcode' ? 'selected' : '') . ' value="stkcode">' . __('Stock Code') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'extprice' ? 'selected' : '') . ' value="extprice">' . __('Extended Price') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'debtorno' ? 'selected' : '') . ' value="debtorno">' . __('Customer Code') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'name' ? 'selected' : '') . ' value="name">' . __('Customer Name') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'month' ? 'selected' : '') . ' value="month">' . __('Month') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'categoryid' ? 'selected' : '') . ' value="categoryid">' . __('Stock Category') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'salesman' ? 'selected' : '') . ' value="salesman">' . __('Salesman') . '</option>
                                        <option ' . ($_POST['SummaryType'] == 'area' ? 'selected' : '') . ' value="area">' . __('Sales Area') . '</option>
                                    </select>
                                </div>' :
                                '<div class="db-form-group">
                                    <label class="db-label">' . __('Sort By') . '</label>
                                    <select name="SortBy" class="db-select">
                                        <option ' . ($_POST['SortBy'] == 'salesorderdetails.orderno' ? 'selected' : '') . ' value="salesorderdetails.orderno">' . __('Order Number') . '</option>
                                        <option ' . ($_POST['SortBy'] == 'salesorderdetails.stkcode' ? 'selected' : '') . ' value="salesorderdetails.stkcode">' . __('Stock Code') . '</option>
                                        <option ' . ($_POST['SortBy'] == 'debtorsmaster.debtorno,salesorderdetails.orderno' ? 'selected' : '') . ' value="debtorsmaster.debtorno,salesorderdetails.orderno">' . __('Customer Number') . '</option>
                                        <option ' . ($_POST['SortBy'] == 'debtorsmaster.name,debtorsmaster.debtorno,salesorderdetails.orderno' ? 'selected' : '') . ' value="debtorsmaster.name,debtorsmaster.debtorno,salesorderdetails.orderno">' . __('Customer Name') . '</option>
                                        <option ' . ($_POST['SortBy'] == 'tempstockmoves.transno,salesorderdetails.stkcode' ? 'selected' : '') . ' value="tempstockmoves.transno,salesorderdetails.stkcode">' . __('Transaction Number') . '</option>
                                    </select>
                                </div>'
                            ) . '
                        </div>
                    </div>

                    <div class="db-card" style="margin-top: var(--space-4);">
                        <div class="db-card-header"><div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Search Filters') . '</div></div>
                        <div class="db-card-body">
                            <div class="db-form-group">
                                <label class="db-label">' . __('Stock Code') . '</label>
                                <div style="display: flex; gap: 5px;">
                                    <select name="PartNumberOp" class="db-select" style="width: 80px;"><option ' . ($PartNumberOp == '=' ? 'selected' : '') . ' value="=">=</option><option ' . ($PartNumberOp == 'LIKE' ? 'selected' : '') . ' value="LIKE">~</option></select>
                                    <input type="text" name="PartNumber" class="db-input" value="' . $PartNumber . '" placeholder="Code" />
                                </div>
                            </div>
                            <div class="db-form-group">
                                <label class="db-label">' . __('Customer') . '</label>
                                <div style="display: flex; gap: 5px; margin-bottom: 5px;">
                                    <select name="DebtorNoOp" class="db-select" style="width: 80px;"><option ' . ($DebtorNoOp == '=' ? 'selected' : '') . ' value="=">=</option><option ' . ($DebtorNoOp == 'LIKE' ? 'selected' : '') . ' value="LIKE">~</option></select>
                                    <input type="text" name="DebtorNo" class="db-input" value="' . $DebtorNo . '" placeholder="ID" />
                                </div>
                                <div style="display: flex; gap: 5px;">
                                    <select name="DebtorNameOp" class="db-select" style="width: 80px;"><option ' . ($DebtorNameOp == 'LIKE' ? 'selected' : '') . ' value="LIKE">~</option><option ' . ($DebtorNameOp == '=' ? 'selected' : '') . ' value="=">=</option></select>
                                    <input type="text" name="DebtorName" class="db-input" value="' . $DebtorName . '" placeholder="' . __('Name') . '" />
                                </div>
                            </div>
                            
                            <div class="db-form-group">
                                <label class="db-label">' . __('Area & Team') . '</label>
                                <select name="Area" class="db-select" style="margin-bottom: 5px;">
                                    <option value="All">' . __('All Areas') . '</option>';
                                    $areaRes = DB_query("SELECT areacode, areadescription FROM areas ORDER BY areadescription");
                                    while($a = DB_fetch_array($areaRes)) { echo '<option ' . ($_POST['Area'] == $a['areacode'] ? 'selected' : '') . ' value="' . $a['areacode'] . '">' . $a['areadescription'] . '</option>'; }
    echo '                      </select>
                                <select name="Salesman" class="db-select">';
                                    if ($_SESSION['SalesmanLogin'] != '') { echo '<option value="' . $_SESSION['SalesmanLogin'] . '">' . $_SESSION['UsersRealName'] . '</option>'; }
                                    else {
                                        echo '<option value="All">' . __('All Salespeople') . '</option>';
                                        $smRes = DB_query("SELECT salesmancode, salesmanname FROM salesman ORDER BY salesmanname");
                                        while($sm = DB_fetch_array($smRes)) { echo '<option ' . ($_POST['Salesman'] == $sm['salesmancode'] ? 'selected' : '') . ' value="' . $sm['salesmancode'] . '">' . $sm['salesmanname'] . '</option>'; }
                                    }
    echo '                      </select>
                            </div>
                            
                            <div style="margin-top: 30px;">
                                <button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
                                    <i class="fas fa-search-dollar"></i> ' . __('Run Inquiry') . '
                                </button>
                                ' . ($ShowResults ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '" class="db-btn db-btn-outline" style="width: 100%; justify-content: center; margin-top: 10px;">' . __('Reset') . '</a>' : '') . '
                            </div>
                        </div>
                    </div>
                </aside>

                <!-- Data Intelligence Main -->
                <main class="db-col-main">';

                    if ($ShowResults) {
                        // SQL Generation Logic (Migrated from legacy submit function)
                        if ($_POST['DateType'] == 'Invoice') { TempStockmoves(); }
                        
                        $WherePart = (mb_strlen($PartNumber) > 0) ? " AND salesorderdetails.stkcode " . $PartNumberOp . " '" . ($PartNumberOp == 'LIKE' ? $PartNumber.'%' : $PartNumber) . "' " : "";
                        $WhereDebtorNo = (mb_strlen($DebtorNo) > 0) ? " AND salesorders.debtorno " . $DebtorNoOp . " '" . ($DebtorNoOp == 'LIKE' ? $DebtorNo.'%' : $DebtorNo) . "' " : "";
                        $WhereDebtorName = (mb_strlen($DebtorName) > 0) ? " AND debtorsmaster.name " . $DebtorNameOp . " '" . ($DebtorNameOp == 'LIKE' ? $DebtorName.'%' : $DebtorName) . "' " : "";
                        $WhereOrderNo = (mb_strlen($_POST['OrderNo']) > 0) ? " AND salesorderdetails.orderno = '" . $_POST['OrderNo'] . "' " : "";
                        $WhereLineStatus = ($_POST['LineStatus'] != 'All') ? " AND if(salesorderdetails.quantity = salesorderdetails.qtyinvoiced || salesorderdetails.completed = 1,'Completed','Open') = '" . $_POST['LineStatus'] . "'" : "";
                        $WhereArea = ($_POST['Area'] != 'All') ? " AND custbranch.area = '" . $_POST['Area'] . "'" : "";
                        $WhereSalesman = ($_SESSION['SalesmanLogin'] != '') ? " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'" : ($_POST['Salesman'] != 'All' ? " AND custbranch.salesman = '" . $_POST['Salesman'] . "'" : "");
                        $WhereCategory = ($_POST['Category'] != 'All') ? " AND stockmaster.categoryid = '" . $_POST['Category'] . "'" : "";
                        $WhereType = ($_POST['InvoiceType'] != 'All') ? " AND tempstockmoves.type = '" . $_POST['InvoiceType'] . "'" : " AND (tempstockmoves.type='10' OR tempstockmoves.type='11')";

                        $FromSQLDate = FormatDateForSQL($_POST['FromDate']);
                        $ToSQLDate = FormatDateForSQL($_POST['ToDate']);

                        if ($_POST['ReportType'] == 'Detail') {
                            if ($_POST['DateType'] == 'Order') {
                                $SQL = "SELECT salesorderdetails.orderno, salesorderdetails.stkcode, salesorderdetails.itemdue, salesorders.debtorno, salesorders.orddate, salesorders.branchcode, salesorderdetails.quantity, salesorderdetails.qtyinvoiced,
                                               (salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
                                               (salesorderdetails.quantity * stockmaster.actualcost) as extcost,
                                               if (salesorderdetails.quantity = salesorderdetails.qtyinvoiced || salesorderdetails.completed = 1,'Completed','Open') as linestatus,
                                               debtorsmaster.name, custbranch.brname, custbranch.area, custbranch.salesman, stockmaster.decimalplaces, stockmaster.description
                                        FROM salesorderdetails
                                        LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
                                        LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
                                        LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno)
                                        LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
                                        LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                        WHERE salesorders.orddate >='" . $FromSQLDate . "' AND salesorders.orddate <='" . $ToSQLDate . "'
                                        AND salesorders.quotation = '" . $_POST['OrderType'] . "'" . $WherePart . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " ORDER BY " . $_POST['SortBy'];
                            } else {
                                $SQL = "SELECT salesorderdetails.orderno, salesorderdetails.stkcode, salesorderdetails.itemdue, salesorders.debtorno, salesorders.orddate, salesorders.branchcode, (tempstockmoves.qty * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) * -1 / currencies.rate) as extprice, (tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
                                               if (salesorderdetails.quantity = salesorderdetails.qtyinvoiced || salesorderdetails.completed = 1,'Completed','Open') as linestatus,
                                               debtorsmaster.name, custbranch.brname, custbranch.area, custbranch.salesman, stockmaster.decimalplaces, stockmaster.description, (tempstockmoves.qty * -1) as qty, tempstockmoves.transno, tempstockmoves.trandate, tempstockmoves.type
                                        FROM tempstockmoves
                                        LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
                                        LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
                                        LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
                                        LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno)
                                        LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
                                        LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                        WHERE tempstockmoves.trandate >='" . $FromSQLDate . "' AND tempstockmoves.trandate <='" . $ToSQLDate . "'
                                        AND tempstockmoves.stockid=salesorderdetails.stkcode AND tempstockmoves.hidemovt=0 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereType . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " ORDER BY " . $_POST['SortBy'];
                            }
                        } else {
                            $OrderBy = $_POST['SummaryType'] == 'extprice' ? 'extprice DESC' : $_POST['SummaryType'];
                            $SummaryPivot = $_POST['SummaryType'] == 'extprice' ? 'stkcode' : $_POST['SummaryType'];
                            
                            if ($_POST['DateType'] == 'Order') {
                                // Grouped SQL for Order Summary
                                if ($SummaryPivot == 'stkcode') {
                                    $SQL = "SELECT salesorderdetails.stkcode, SUM(salesorderdetails.quantity) as quantity, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice, SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost, stockmaster.description, stockmaster.decimalplaces
                                            FROM salesorderdetails LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE salesorders.orddate >='" . $FromSQLDate . "' AND salesorders.orddate <='" . $ToSQLDate . "' AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.decimalplaces ORDER BY " . $OrderBy;
                                } elseif ($SummaryPivot == 'orderno') {
                                    $SQL = "SELECT salesorderdetails.orderno, salesorders.debtorno, debtorsmaster.name, SUM(salesorderdetails.quantity) as quantity, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice, SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
                                            FROM salesorderdetails LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE salesorders.orddate >='" . $FromSQLDate . "' AND salesorders.orddate <='" . $ToSQLDate . "' AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY salesorderdetails.orderno, salesorders.debtorno, debtorsmaster.name ORDER BY " . $OrderBy;
                                } elseif ($SummaryPivot == 'debtorno' || $SummaryPivot == 'name') {
                                    $SQL = "SELECT debtorsmaster.debtorno, debtorsmaster.name, SUM(salesorderdetails.quantity) as quantity, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice, SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
                                            FROM salesorderdetails LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE salesorders.orddate >='" . $FromSQLDate . "' AND salesorders.orddate <='" . $ToSQLDate . "' AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY debtorsmaster.debtorno, debtorsmaster.name ORDER BY " . ($_POST['SummaryType'] == 'name' ? 'name' : $OrderBy);
                                } else {
                                    // Default fallback for month/category/salesman/area
                                    $grp = ($SummaryPivot == 'month') ? "month" : (($SummaryPivot == 'categoryid') ? "stockmaster.categoryid" : (($SummaryPivot == 'salesman') ? "custbranch.salesman" : "custbranch.area"));
                                    $selExtra = ($SummaryPivot == 'month') ? "EXTRACT(YEAR_MONTH from salesorders.orddate) as month, CONCAT(MONTHNAME(salesorders.orddate),' ',YEAR(salesorders.orddate)) as monthname" : (($SummaryPivot == 'categoryid') ? "stockmaster.categoryid, stockcategory.categorydescription" : (($SummaryPivot == 'salesman') ? "custbranch.salesman, salesman.salesmanname" : "custbranch.area, areas.areadescription"));
                                    $SQL = "SELECT " . $selExtra . ", SUM(salesorderdetails.quantity) as quantity, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice, SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
                                            FROM salesorderdetails LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE salesorders.orddate >='" . $FromSQLDate . "' AND salesorders.orddate <='" . $ToSQLDate . "' AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY " . $grp . " ORDER BY " . $OrderBy;
                                }
                            } else {
                                // Grouped SQL for Invoice Summary
                                if ($SummaryPivot == 'stkcode') {
                                    $SQL = "SELECT salesorderdetails.stkcode, SUM(tempstockmoves.qty * -1) as qty, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice, SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost, stockmaster.description
                                            FROM tempstockmoves LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE tempstockmoves.trandate >='" . $FromSQLDate . "' AND tempstockmoves.trandate <='" . $ToSQLDate . "' AND tempstockmoves.stockid=salesorderdetails.stkcode AND tempstockmoves.hidemovt=0 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereType . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY salesorderdetails.stkcode, stockmaster.description ORDER BY " . $OrderBy;
                                } else {
                                    // Other Invoice summaries... (Simplified here for performance and brevity, similar logic as Order Date)
                                    $grp = ($SummaryPivot == 'orderno') ? "salesorderdetails.orderno, salesorders.debtorno, debtorsmaster.name" : (($SummaryPivot == 'debtorno' || $SummaryPivot == 'name') ? "debtorsmaster.debtorno, debtorsmaster.name" : (($SummaryPivot == 'month') ? "month, monthname" : (($SummaryPivot == 'categoryid') ? "stockmaster.categoryid, categorydescription" : (($SummaryPivot == 'salesman') ? "custbranch.salesman, salesmanname" : "custbranch.area, areadescription"))));
                                    $selExtra = ($SummaryPivot == 'orderno') ? "salesorderdetails.orderno, salesorders.debtorno, debtorsmaster.name" : (($SummaryPivot == 'debtorno' || $SummaryPivot == 'name') ? "debtorsmaster.debtorno, debtorsmaster.name" : (($SummaryPivot == 'month') ? "EXTRACT(YEAR_MONTH from trandate) as month, CONCAT(MONTHNAME(trandate),' ',YEAR(trandate)) as monthname" : (($SummaryPivot == 'categoryid') ? "stockmaster.categoryid, stockcategory.categorydescription" : (($SummaryPivot == 'salesman') ? "custbranch.salesman, salesman.salesmanname" : "custbranch.area, areas.areadescription"))));
                                    $SQL = "SELECT " . $selExtra . ", SUM(tempstockmoves.qty * -1) as qty, SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced, SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice, SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost
                                            FROM tempstockmoves LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode AND salesorders.debtorno = custbranch.debtorno) LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman LEFT JOIN areas ON areas.areacode = custbranch.area LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
                                            WHERE tempstockmoves.trandate >='" . $FromSQLDate . "' AND tempstockmoves.trandate <='" . $ToSQLDate . "' AND tempstockmoves.stockid=salesorderdetails.stkcode AND tempstockmoves.hidemovt=0 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " . $WherePart . $WhereType . $WhereOrderNo . $WhereDebtorNo . $WhereDebtorName . $WhereLineStatus . $WhereArea . $WhereSalesman . $WhereCategory . " GROUP BY " . $grp . " ORDER BY " . ($SummaryPivot == 'name' ? 'name' : $OrderBy);
                                }
                            }
                        }

                        $Result = DB_query($SQL);
                        
                        $TotalQty = 0; $TotalCost = 0; $TotalValue = 0; $RowCount = 0;
                        $data = [];
                        while ($Row = DB_fetch_array($Result)) {
                            $TotalQty += ($Row['qty'] ?? $Row['quantity']);
                            $TotalCost += $Row['extcost'];
                            $TotalValue += $Row['extprice'];
                            $RowCount++;
                            $data[] = $Row;
                        }
                        $gpValue = $TotalValue - $TotalCost;
                        $gpPercent = ($TotalValue != 0) ? ($gpValue / $TotalValue) * 100 : 0;

                        echo '<div class="kpi-grid" style="margin-bottom: var(--space-6);">
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--success-soft); color: var(--success);"><i class="fas fa-hand-holding-usd"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Total Sales Value') . '</span><span class="value">' . locale_number_format($TotalValue, 0) . '</span></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--primary-soft); color: var(--primary);"><i class="fas fa-chart-line"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Margin Performance') . '</span><span class="value">' . locale_number_format($gpPercent, 1) . '%</span><small class="text-muted">' . locale_number_format($gpValue, 0) . ' (' . __('GP') . ')</small></div>
                                </div>
                                <div class="kpi-card-v2">
                                    <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);"><i class="fas fa-receipt"></i></div>
                                    <div class="kpi-data"><span class="label">' . __('Volume Analysed') . '</span><span class="value">' . locale_number_format($TotalQty, 0) . '</span><small class="text-muted">' . $RowCount . ' ' . __('Lines') . '</small></div>
                                </div>
                              </div>';

                        if ($RowCount > 0) {
                            echo '<div class="db-card">
                                    <div class="db-card-header"><div class="db-card-title"><i class="fas fa-list-ul"></i> ' . ($_POST['ReportType'] == 'Detail' ? __('Transaction Registry') : __('Executive Portfolio')) . '</div></div>
                                    <div class="db-card-body p-0">
                                        <div class="db-table-wrapper">
                                            <table class="db-table">';
                                            if ($_POST['ReportType'] == 'Detail') {
                                                echo '<thead><tr>
                                                        <th>' . __('Reference') . '</th>
                                                        <th>' . __('Item Context') . '</th>
                                                        <th>' . __('Entity') . '</th>
                                                        <th class="text-right">' . __('Qty') . '</th>
                                                        <th class="text-right">' . __('Value') . '</th>
                                                        <th class="text-right">' . __('Cost') . '</th>
                                                        <th>' . __('Status') . '</th>
                                                      </tr></thead><tbody>';
                                                foreach ($data as $Row) {
                                                    $ref = $_POST['DateType'] == 'Order' ? $Row['orderno'] : $Row['transno'];
                                                    $refDate = $_POST['DateType'] == 'Order' ? $Row['orddate'] : $Row['trandate'];
                                                    echo '<tr>
                                                            <td><div class="db-font-bold">#' . $ref . '</div><small class="text-muted">' . ConvertSQLDate($refDate) . '</small></td>
                                                            <td><div class="db-font-semibold">' . $Row['stkcode'] . '</div><small class="text-muted">' . $Row['description'] . '</small></td>
                                                            <td><div class="db-font-medium">' . $Row['name'] . '</div><small class="text-muted">' . $Row['brname'] . '</small></td>
                                                            <td class="text-right">' . locale_number_format(($Row['qty'] ?? $Row['quantity']), $Row['decimalplaces']) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['extprice'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['extcost'], 2) . '</td>
                                                            <td><span class="db-badge ' . ($Row['linestatus'] == 'Completed' ? 'db-badge-success' : 'db-badge-warning') . '">' . $Row['linestatus'] . '</span></td>
                                                          </tr>';
                                                }
                                            } else {
                                                // SUMMARY MODE
                                                $pivotLabel = ($_POST['SummaryType'] == 'month') ? __('Period') : (($_POST['SummaryType'] == 'categoryid') ? __('Category') : (($_POST['SummaryType'] == 'salesman') ? __('Salesman') : (($_POST['SummaryType'] == 'area') ? __('Area') : (($_POST['SummaryType'] == 'stkcode' || $_POST['SummaryType'] == 'extprice') ? __('Item') : (($_POST['SummaryType'] == 'debtorno' || $_POST['SummaryType'] == 'name') ? __('Customer') : __('Reference'))))));
                                                $pivotKey = ($_POST['SummaryType'] == 'name') ? 'name' : (($_POST['SummaryType'] == 'month') ? 'monthname' : (($_POST['SummaryType'] == 'categoryid') ? 'categorydescription' : (($_POST['SummaryType'] == 'salesman') ? 'salesmanname' : (($_POST['SummaryType'] == 'area') ? 'areadescription' : (($_POST['SummaryType'] == 'extprice' ? 'stkcode' : $_POST['SummaryType']))))));
                                                
                                                echo '<thead><tr>
                                                        <th>' . $pivotLabel . '</th>
                                                        <th class="text-right">' . __('Qty Impact') . '</th>
                                                        <th class="text-right">' . __('Revenue') . '</th>
                                                        <th class="text-right">' . __('Cost Basis') . '</th>
                                                        <th class="text-right">' . __('GP %') . '</th>
                                                      </tr></thead><tbody>';
                                                foreach ($data as $Row) {
                                                    $rowGp = ($Row['extprice'] != 0) ? (($Row['extprice'] - $Row['extcost']) / $Row['extprice']) * 100 : 0;
                                                    $severity = ($rowGp < 10) ? 'danger' : (($rowGp < 25) ? 'warning' : 'success');
                                                    echo '<tr>
                                                            <td><div class="db-font-bold">' . $Row[$pivotKey] . '</div>' . (isset($Row['description']) ? '<small class="text-muted">'.$Row['description'].'</small>' : '') . '</td>
                                                            <td class="text-right">' . locale_number_format(($Row['qty'] ?? $Row['quantity']), 2) . '</td>
                                                            <td class="text-right db-font-bold">' . locale_number_format($Row['extprice'], 2) . '</td>
                                                            <td class="text-right">' . locale_number_format($Row['extcost'], 2) . '</td>
                                                            <td class="text-right"><span class="db-badge db-badge-' . $severity . '">' . locale_number_format($rowGp, 1) . '%</span></td>
                                                          </tr>';
                                                }
                                            }
                            echo '              </tbody>
                                            </table>
                                        </div>
                                    </div>
                                  </div>';
                        } else {
                            echo '<div class="db-card" style="text-align: center; padding: 100px; background: var(--surface-alt);">
                                    <i class="fas fa-search" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h3>' . __('No records matched your criteria.') . '</h3>
                                    <p class="text-muted">' . __('Try broadening your date range or adjusting your entity filters.') . '</p>
                                  </div>';
                        }
                    } else {
                        echo '<div class="db-card" style="min-height: 500px; display: flex; align-items: center; justify-content: center; text-align: center; background: var(--surface-alt);">
                                <div class="db-card-body">
                                    <i class="fas fa-microscope" style="font-size: 5rem; color: var(--border-color); margin-bottom: 25px;"></i>
                                    <h2 class="text-muted">' . __('Sales Intelligence Hub') . '</h2>
                                    <p>' . __('Configure your analysis basis on the left and run the inquiry to unlock financial insights.') . '</p>
                                </div>
                              </div>';
                    }

    echo '      </main>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
