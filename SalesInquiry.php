<?php

// Inquiry on Sales Orders - If Date Type is Order Date, salesorderdetails is the main table
// If Date Type is Invoice, stockmoves is the main table

require(__DIR__ . '/includes/session.php');

$Title = __('Sales Inquiry');
$ViewTopic = 'Sales';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<header class="db-page-header">
			<div class="db-page-title">
				<div class="db-page-icon">
					<i class="fas fa-search-dollar"></i>
				</div>
				<h1>' . $Title . '</h1>
			</div>
		</header>';

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

# Sets default date range for current month
if (!isset($_POST['FromDate'])) {

	$_POST['FromDate']=date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m'),1,date('Y')));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

if (isset($_POST['PartNumber'])) {
	$PartNumber = trim(mb_strtoupper($_POST['PartNumber']));
} elseif (isset($_GET['PartNumber'])) {
	$PartNumber = trim(mb_strtoupper($_GET['PartNumber']));
}

# Part Number operator - either LIKE or =
$PartNumberOp = $_POST['PartNumberOp'] ?? '=';

if (isset($_POST['DebtorNo'])) {
	$DebtorNo = trim(mb_strtoupper($_POST['DebtorNo']));
} elseif (isset($_GET['DebtorNo'])) {
	$DebtorNo = trim(mb_strtoupper($_GET['DebtorNo']));
}
$DebtorNoOp = $_POST['DebtorNoOp'] ?? '=';
if (isset($_POST['DebtorName'])) {
	$DebtorName = trim(mb_strtoupper($_POST['DebtorName']));
} elseif (isset($_GET['DebtorName'])) {
	$DebtorName = trim(mb_strtoupper($_GET['DebtorName']));
}
$DebtorNameOp = $_POST['DebtorNameOp'] ?? '=';

// Save $_POST['SummaryType'] in $SaveSummaryType because change $_POST['SummaryType'] when
// create $SQL
$SaveSummaryType = $_POST['SummaryType'] ?? 'name';

if (isset($_POST['submit'])) {
    submit($PartNumber,$PartNumberOp,$DebtorNo,$DebtorNoOp,$DebtorName,$DebtorNameOp,$SaveSummaryType);
} else {
    display();
}

//####_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT####
function submit($PartNumber,$PartNumberOp,$DebtorNo,$DebtorNoOp,$DebtorName,$DebtorNameOp,$SaveSummaryType) {

	//initialise no input errors
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (!Is_Date($_POST['FromDate'])) {
		$InputError = 1;
		prnMsg(__('Invalid From Date'),'error');
	}
	if (!Is_Date($_POST['ToDate'])) {
		$InputError = 1;
		prnMsg(__('Invalid To Date'),'error');
	}

	if ($_POST['ReportType'] == 'Summary' AND $_POST['DateType'] == 'Order'  AND $_POST['SummaryType'] == 'transno') {
		$InputError = 1;
		prnMsg(__('Cannot summarize by transaction number with a date type of Order Date'),'error');
		return;
	}

	if ($_POST['ReportType'] == 'Detail' AND $_POST['DateType'] == 'Order'  AND $_POST['SortBy'] == 'tempstockmoves.transno,salesorderdetails.stkcode') {
		$InputError = 1;
		prnMsg(__('Cannot sort by transaction number with a date type of Order Date'),'error');
		return;
	}
	if (!in_array($_POST['SortBy'],array('salesorderdetails.orderno',
						'salesorderdetails.stkcode',
						'debtorsmaster.debtorno,salesorderdetails.orderno',
						'debtorsmaster.name,debtorsmaster.debtorno,salesorderdetails.orderno',
						'tempstockmoves.transno,salesorderdetails.stkcode',
						'salesorderdetails.itemdue,salesorderdetails.orderno'))) {
		$InputError = 1;
		prnMsg(__('The sorting order is not defined'),'error');
		return;
	}


// TempStockmoves function creates a temporary table of stockmoves that is used when the DateType
// is Invoice Date
	if ($_POST['DateType'] == 'Invoice') {
		TempStockmoves();
	}

	# Add more to WHERE statement, if user entered something for the part number,debtorno, name
	// Variables that end with Op - meaning operator - are either = or LIKE
	$WherePart = ' ';
	if (mb_strlen($PartNumber) > 0 AND $PartNumberOp == 'LIKE') {
	    $PartNumber = $PartNumber . '%';
	} else {
	    $PartNumberOp = '=';
	}
	if (mb_strlen($PartNumber) > 0) {
	    $WherePart = " AND salesorderdetails.stkcode " . $PartNumberOp . " '" . $PartNumber . "'  ";
	}

	$WhereDebtorNo = ' ';
	if ($DebtorNoOp == 'LIKE') {
	    $DebtorNo = $DebtorNo . '%';
	} else {
	    $DebtorNoOp = '=';
	}
	if (mb_strlen($DebtorNo) > 0) {
	    $WhereDebtorNo = " AND salesorders.debtorno " . $DebtorNoOp . " '" . $DebtorNo . "'  ";
	} else {
		$WhereDebtorNo = ' ';
	}

	$WhereDebtorName = ' ';
	if (mb_strlen($DebtorName) > 0 AND $DebtorNameOp == 'LIKE') {
	    $DebtorName = $DebtorName . '%';
	} else {
	    $DebtorNameOp = '=';
	}
	if (mb_strlen($DebtorName) > 0) {
	    $WhereDebtorName = " AND debtorsmaster.name " . $DebtorNameOp . " '" . $DebtorName . "'  ";
	}
	if (mb_strlen($_POST['OrderNo']) > 0) {
	    $WhereOrderNo = " AND salesorderdetails.orderno = " . " '" . $_POST['OrderNo'] . "'  ";
	} else {
		$WhereOrderNo =  " ";
	}

    $WhereLineStatus = ' ';
    # Had to use IF statement instead of comparing 'linestatus' to $_POST['LineStatus']
    #in WHERE clause because the WHERE clause did not recognize
    # that had used the IF statement to create a field caused linestatus
    if ($_POST['LineStatus'] != 'All') {
        $WhereLineStatus = " AND if (salesorderdetails.quantity = salesorderdetails.qtyinvoiced ||
		  salesorderdetails.completed = 1,'Completed','Open') = '" . $_POST['LineStatus'] . "'";
    }

    // The following is from PDFCustomerList.php and shows how to set up WHERE clause
    // for multiple selections from Areas - decided to just allow selection of one Area at
    // a time, so used simpler code
	 $WhereArea = ' ';
    if ($_POST['Area'] != 'All') {
        $WhereArea = " AND custbranch.area = '" . $_POST['Area'] . "'";
    }

	$WhereSalesman = ' ';
	if ($_SESSION['SalesmanLogin'] != '') {

		$WhereSalesman .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";

	} elseif ($_POST['Salesman'] != 'All') {

        $WhereSalesman = " AND custbranch.salesman = '" . $_POST['Salesman'] . "'";
    }

 	 $WhereCategory = ' ';
    if ($_POST['Category'] != 'All') {
        $WhereCategory = " AND stockmaster.categoryid = '" . $_POST['Category'] . "'";
    }

// Only used for Invoice Date type where tempstockmoves is the main table
 	 $WhereType = " AND (tempstockmoves.type='10' OR tempstockmoves.type='11')";
    if ($_POST['InvoiceType'] != 'All') {
        $WhereType = " AND tempstockmoves.type = '" . $_POST['InvoiceType'] . "'";
    }
    if ($InputError !=1) {
		$FromDate = FormatDateForSQL($_POST['FromDate']);
		$ToDate = FormatDateForSQL($_POST['ToDate']);
		if ($_POST['ReportType'] == 'Detail') {
		    if ($_POST['DateType'] == 'Order') {
				$SQL = "SELECT salesorderdetails.orderno,
							   salesorderdetails.stkcode,
							   salesorderdetails.itemdue,
							   salesorders.debtorno,
							   salesorders.orddate,
							   salesorders.branchcode,
							   salesorderdetails.quantity,
							   salesorderdetails.qtyinvoiced,
							   (salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
							   (salesorderdetails.quantity * stockmaster.actualcost) as extcost,
							   if (salesorderdetails.quantity = salesorderdetails.qtyinvoiced ||
								  salesorderdetails.completed = 1,'Completed','Open') as linestatus,
							   debtorsmaster.name,
							   custbranch.brname,
							   custbranch.area,
							   custbranch.salesman,
							   stockmaster.decimalplaces,
							   stockmaster.description
							   FROM salesorderdetails
						LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
						LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
						LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
											AND salesorders.debtorno = custbranch.debtorno)
						LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
						LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
						WHERE salesorders.orddate >='" . $FromDate . "'
						 AND salesorders.orddate <='" . $ToDate . "'
						 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
						$WherePart .
						$WhereOrderNo .
						$WhereDebtorNo .
						$WhereDebtorName .
						$WhereLineStatus .
						$WhereArea .
						$WhereSalesman .
						$WhereCategory .
						"ORDER BY " . $_POST['SortBy'];
			  } else {
			    // Selects by tempstockmoves.trandate not order date
				$SQL = "SELECT salesorderdetails.orderno,
							   salesorderdetails.stkcode,
							   salesorderdetails.itemdue,
							   salesorders.debtorno,
							   salesorders.orddate,
							   salesorders.branchcode,
							   salesorderdetails.quantity,
							   salesorderdetails.qtyinvoiced,
							   (tempstockmoves.qty * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) * -1 / currencies.rate) as extprice,
							   (tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
							   if (salesorderdetails.quantity = salesorderdetails.qtyinvoiced ||
								  salesorderdetails.completed = 1,'Completed','Open') as linestatus,
							   debtorsmaster.name,
							   custbranch.brname,
							   custbranch.area,
							   custbranch.salesman,
							   stockmaster.decimalplaces,
							   stockmaster.description,
							   (tempstockmoves.qty * -1) as qty,
							   tempstockmoves.transno,
							   tempstockmoves.trandate,
							   tempstockmoves.type
							   FROM tempstockmoves
						LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
						LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
						LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
						LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
											AND salesorders.debtorno = custbranch.debtorno)
						LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
						LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
						WHERE tempstockmoves.trandate >='" . $FromDate . "'
						 AND tempstockmoves.trandate <='" . $ToDate . "'
						 AND tempstockmoves.stockid=salesorderdetails.stkcode
						 AND tempstockmoves.hidemovt=0
						 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " .
						$WherePart .
						$WhereType .
						$WhereOrderNo .
						$WhereDebtorNo .
						$WhereDebtorName .
						$WhereLineStatus .
						$WhereArea .
						$WhereSalesman .
						$WhereCategory .
						"ORDER BY " . $_POST['SortBy'];
		    }
		} else {
		  // sql for Summary report
		  $OrderBy = $_POST['SummaryType'];
		  // The following is because the 'extprice' summary is a special case - with the other
		  // summaries, you group and order on the same field; with 'extprice', you are actually
		  // grouping on the stkcode and ordering by extprice descending
		  if ($_POST['SummaryType'] == 'extprice') {
		      $_POST['SummaryType'] = 'stkcode';
		      $OrderBy = 'extprice DESC';
		  }
		  if ($_POST['DateType'] == 'Order') {
		      if ($_POST['SummaryType'] == 'extprice' OR $_POST['SummaryType'] == 'stkcode') {
					$SQL = "SELECT salesorderdetails.stkcode,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost,
								   stockmaster.description,
								   stockmaster.decimalplaces
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",salesorderdetails.stkcode,
								   stockmaster.description,
								   stockmaster.decimalplaces
								   ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'orderno') {
					$SQL = "SELECT salesorderdetails.orderno,
					               salesorders.debtorno,
					               debtorsmaster.name,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate  . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",salesorders.debtorno,
								   debtorsmaster.name
								   ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'debtorno' OR $_POST['SummaryType'] == 'name') {
				    if ($_POST['SummaryType'] == 'name') {
				        $OrderBy = 'name';
				    }
					$SQL = "SELECT debtorsmaster.debtorno,
					               debtorsmaster.name,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY debtorsmaster.debtorno
							,debtorsmaster.name
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'month') {
					$SQL = "SELECT EXTRACT(YEAR_MONTH from salesorders.orddate) as month,
								   CONCAT(MONTHNAME(salesorders.orddate),' ',YEAR(salesorders.orddate)) as monthname,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",monthname
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'categoryid') {
					$SQL = "SELECT stockmaster.categoryid,
								   stockcategory.categorydescription,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",categorydescription

							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'salesman') {
					$SQL = "SELECT custbranch.salesman,
								   salesman.salesmanname,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",salesmanname
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'area') {
					$SQL = "SELECT custbranch.area,
								   areas.areadescription,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent) / currencies.rate) as extprice,
								   SUM(salesorderdetails.quantity * stockmaster.actualcost) as extcost
								   FROM salesorderdetails
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
							LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE salesorders.orddate >='" . $FromDate . "'
							 AND salesorders.orddate <='" . $ToDate . "'
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "' " .
							$WherePart .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",areas.areadescription
							ORDER BY " . $OrderBy;
				}
		   } else {
		        // Selects by tempstockmoves.trandate not order date
		      if ($_POST['SummaryType'] == 'extprice' OR $_POST['SummaryType'] == 'stkcode') {
					$SQL = "SELECT salesorderdetails.stkcode,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   stockmaster.description,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",stockmaster.description
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'orderno') {
					$SQL = "SELECT salesorderdetails.orderno,
					               salesorders.debtorno,
					               debtorsmaster.name,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",salesorders.debtorno,
							  debtorsmaster.name
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'debtorno' OR $_POST['SummaryType'] == 'name') {
				    if ($_POST['SummaryType'] == 'name') {
				        $OrderBy = 'name';
				    }
					$SQL = "SELECT debtorsmaster.debtorno,
					               debtorsmaster.name,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY debtorsmaster.debtorno" . ' ' .
							",debtorsmaster.name
							ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'month') {
					$SQL = "SELECT EXTRACT(YEAR_MONTH from salesorders.orddate) as month,
								   CONCAT(MONTHNAME(salesorders.orddate),' ',YEAR(salesorders.orddate)) as monthname,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",monthname
						    ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'categoryid') {
					$SQL = "SELECT stockmaster.categoryid,
								   stockcategory.categorydescription,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
												AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",categorydescription
						    ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'salesman') {
					$SQL = "SELECT custbranch.salesman,
								   salesman.salesmanname,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
													AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",salesmanname
						    ORDER BY " . $OrderBy;
				} elseif ($_POST['SummaryType'] == 'area') {
					$SQL = "SELECT custbranch.area,
								   areas.areadescription,
								   SUM(salesorderdetails.quantity) as quantity,
								   SUM(salesorderdetails.qtyinvoiced) as qtyinvoiced,
								   SUM(tempstockmoves.qty * tempstockmoves.price * -1 / currencies.rate) as extprice,
								   SUM(tempstockmoves.qty * tempstockmoves.standardcost) * -1 as extcost,
								   SUM(tempstockmoves.qty * -1) as qty
								   FROM tempstockmoves
							LEFT JOIN salesorderdetails ON tempstockmoves.reference=salesorderdetails.orderno
							LEFT JOIN salesorders ON salesorders.orderno=salesorderdetails.orderno
							LEFT JOIN debtorsmaster ON salesorders.debtorno = debtorsmaster.debtorno
							LEFT JOIN custbranch ON (salesorders.branchcode = custbranch.branchcode
													AND salesorders.debtorno = custbranch.debtorno)
						    LEFT JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid
							LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
							LEFT JOIN salesman ON salesman.salesmancode = custbranch.salesman
							LEFT JOIN areas ON areas.areacode = custbranch.area
							LEFT JOIN currencies ON currencies.currabrev = debtorsmaster.currcode
							WHERE tempstockmoves.trandate >='" . $FromDate . "'
							 AND tempstockmoves.trandate <='" . $ToDate . "'
						     AND tempstockmoves.stockid=salesorderdetails.stkcode
							 AND tempstockmoves.hidemovt=0
							 AND salesorders.quotation = '" . $_POST['OrderType'] . "'" .
							$WherePart .
							$WhereType .
							$WhereOrderNo .
							$WhereDebtorNo .
							$WhereDebtorName .
							$WhereLineStatus .
							$WhereArea .
							$WhereSalesman .
							$WhereCategory .
							"GROUP BY " . $_POST['SummaryType'] .
							",areas.areadescription
						    ORDER BY " . $OrderBy;
				}
		   }
		} // End of if ($_POST['ReportType']
		//echo "<br/>$SQL<br/>";
		$ErrMsg = __('The SQL to find the parts selected failed with the message');
		$Result = DB_query($SQL, $ErrMsg);
		$ctr = 0;
		echo '<pre>';
		$TotalQty = 0;
		$TotalExtCost = 0;
		$TotalExtPrice = 0;
		$TotalInvQty = 0;

	// Create array for summary type to display in header. Access it with $SaveSummaryType
	$Summary_Array['orderno'] =  __('Order Number');
	$Summary_Array['stkcode'] =  __('Stock Code');
	$Summary_Array['extprice'] =  __('Extended Price');
	$Summary_Array['debtorno'] =  __('Customer Code');
	$Summary_Array['name'] =  __('Customer Name');
	$Summary_Array['month'] =  __('Month');
	$Summary_Array['categoryid'] =  __('Stock Category');
	$Summary_Array['salesman'] =  __('Salesman');
	$Summary_Array['area'] = __('Sales Area');
	$Summary_Array['transno'] = __('Transaction Number');
    // Create array for sort for detail report to display in header
    $Detail_Array['salesorderdetails.orderno'] = __('Order Number');
	$Detail_Array['salesorderdetails.stkcode'] = __('Stock Code');
	$Detail_Array['debtorsmaster.debtorno,salesorderdetails.orderno'] = __('Customer Code');
	$Detail_Array['debtorsmaster.name,debtorsmaster.debtorno,salesorderdetails.orderno'] = __('Customer Name');
	$Detail_Array['tempstockmoves.transno,salesorderdetails.stkcode'] = __('Transaction Number');

		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Inquiry Results') . ' - ' . $_POST['ReportType'] . ' ' . __('By') . ' ' . $SortBy_Display . '</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-container">
						<table class="db-table">';
		if ($_POST['ReportType'] == 'Detail') {
			if ($_POST['DateType'] == 'Order') {
				echo '<thead>
						<tr>
							<th>' . __('Order No') . '</th>
							<th>' . __('Stock Code') . '</th>
							<th>' . __('Order Date') . '</th>
							<th>' . __('Debtor No') . '</th>
							<th>' . __('Debtor Name') . '</th>
							<th>' . __('Branch Name') . '</th>
							<th class="number">' . __('Order Qty') . '</th>
							<th class="number">' . __('Extended Cost') . '</th>
							<th class="number">' . __('Extended Price') . '</th>
							<th class="number">' . __('Invoiced Qty') . '</th>
							<th>' . __('Line Status') . '</th>
							<th>' . __('Item Due') . '</th>
							<th>' . __('Salesman') . '</th>
							<th>' . __('Area') . '</th>
							<th>' . __('Item Description') . '</th>
						</tr>
					</thead>
					<tbody>';
			} else {
				echo '<thead>
						<tr>
							<th>' . __('Order No') . '</th>
							<th>' . __('Trans. No') . '</th>
							<th>' . __('Stock Code') . '</th>
							<th>' . __('Order Date') . '</th>
							<th>' . __('Debtor No') . '</th>
							<th>' . __('Debtor Name') . '</th>
							<th>' . __('Branch Name') . '</th>
							<th class="number">' . __('Invoiced Qty') . '</th>
							<th class="number">' . __('Extended Cost') . '</th>
							<th class="number">' . __('Extended Price') . '</th>
							<th>' . __('Line Status') . '</th>
							<th>' . __('Invoiced') . '</th>
							<th>' . __('Salesman') . '</th>
							<th>' . __('Area') . '</th>
							<th>' . __('Item Description') . '</th>
						</tr>
					</thead>
					<tbody>';
			}

			$Linectr = 0;
			while ($MyRow = DB_fetch_array($Result)) {
			    $Linectr++;
			    if ($_POST['DateType'] == 'Order') {
					echo '<tr>
						<td>' . $MyRow['orderno'] . '</td>
						<td>' . $MyRow['stkcode'] . '</td>
						<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
						<td>' . $MyRow['debtorno'] . '</td>
						<td>' . $MyRow['name'] . '</td>
						<td>' . $MyRow['brname'] . '</td>
						<td class="number">' . locale_number_format($MyRow['quantity'], $MyRow['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['extcost'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['extprice'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['qtyinvoiced'], $MyRow['decimalplaces']) . '</td>
						<td>' . $MyRow['linestatus'] . '</td>
						<td>' . ConvertSQLDate($MyRow['itemdue']) . '</td>
						<td>' . $MyRow['salesman'] . '</td>
						<td>' . $MyRow['area'] . '</td>
						<td>' . $MyRow['description'] . '</td>
					</tr>';
					$TotalQty += $MyRow['quantity'];
				} else {
				    // Detail for Invoiced Date
				    echo '<tr>
						<td>' . $MyRow['orderno'] . '</td>
						<td>' . $MyRow['transno'] . '</td>
						<td>' . $MyRow['stkcode'] . '</td>
						<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
						<td>' . $MyRow['debtorno'] . '</td>
						<td>' . $MyRow['name'] . '</td>
						<td>' . $MyRow['brname'] . '</td>
						<td class="number">' . locale_number_format($MyRow['qty'], $MyRow['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['extcost'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['extprice'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td>' . $MyRow['linestatus'] . '</td>
						<td>' . ConvertSQLDate($MyRow['trandate']) . '</td>
						<td>' . $MyRow['salesman'] . '</td>
						<td>' . $MyRow['area'] . '</td>
						<td>' . $MyRow['description'] . '</td>
					</tr>';
					$TotalQty += $MyRow['qty'];
				}
				$LastDecimalPlaces = $MyRow['decimalplaces'];
				$TotalExtCost += $MyRow['extcost'];
				$TotalExtPrice += $MyRow['extprice'];
				$TotalInvQty += $MyRow['qtyinvoiced'];
			} //END WHILE LIST LOOP

			echo '</tbody>
						<tfoot>
							<tr class="db-table-total">
								<td colspan="' . ($_POST['DateType'] == 'Order' ? 6 : 7) . '"><b>' . __('Totals') . '</b> - ' . __('Lines') . ': ' . $Linectr . '</td>
								<td class="number"><b>' . locale_number_format($TotalQty, 2) . '</b></td>
								<td class="number"><b>' . locale_number_format($TotalExtCost, $_SESSION['CompanyRecord']['decimalplaces']) . '</b></td>
								<td class="number"><b>' . locale_number_format($TotalExtPrice, $_SESSION['CompanyRecord']['decimalplaces']) . '</b></td>
								<td class="number"><b>' . locale_number_format($TotalInvQty, 2) . '</b></td>
								<td colspan="' . ($_POST['DateType'] == 'Order' ? 5 : 4) . '"></td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		</div><br />';
		} else {
		  // Print summary stuff
			$SummaryType = $_POST['SummaryType'];
			$ColumnHeader7 = ' ';
			// Set up description based on the Summary Type
			if ($SummaryType == 'name') {
				$SummaryType = 'name';
				$Description = 'debtorno';
				$SummaryHeader = __('Customer Name');
				$DescriptionHeader =  __('Customer Code');
			}
			if ($SummaryType == 'stkcode' OR $SummaryType == 'extprice') {
				$Description = 'Description';
				$SummaryHeader =  __('Stock Code');
				$DescriptionHeader =  __('Item Description');
			}
			if ($SummaryType == 'transno') {
				$Description = 'name';
				$SummaryHeader =  __('Transaction Number');
				$DescriptionHeader =  __('Customer Name');
				$ColumnHeader7 =  __('Order Number');
			}
			if ($SummaryType == 'debtorno') {
				$Description = 'name';
				$SummaryHeader =  __('Customer Code');
				$DescriptionHeader =  __('Customer Name');
			}
			if ($SummaryType == 'orderno') {
				$Description = 'debtorno';
				$SummaryHeader =  __('Order Number');
				$DescriptionHeader =  __('Customer Code');
				$ColumnHeader7 =  __('Customer Name');
			}
			if ($SummaryType == 'categoryid') {
				$Description = 'categorydescription';
				$SummaryHeader =  __('Stock Category');
				$DescriptionHeader =  __('Category Description');
			}
			if ($SummaryType == 'salesman') {
				$Description = 'salesmanname';
				$SummaryHeader =  __('Salesman Code');
				$DescriptionHeader =  __('Salesman Name');
			}
			if ($SummaryType == 'area') {
				$Description = 'areadescription';
				$SummaryHeader =  __('Sales Area');
				$DescriptionHeader =  __('Area Description');
			}
			if ($SummaryType == 'month') {
				$Description = 'monthname';
				$SummaryHeader =  __('Month');
				$DescriptionHeader =  __('Month');
			}
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Inquiry Results') . ' - ' . $_POST['ReportType'] . ' ' . __('By') . ' ' . $SortBy_Display . ' (' . ($_POST['DateType'] == 'Order' ? __('Order Date') : __('Invoice Date')) . ': ' . $_POST['FromDate'] . ' ' . __('To') . ' ' . $_POST['ToDate'] . ')</div>
				</div>
				<div class="db-card-body">
					<div class="db-table-container">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __($SummaryHeader) . '</th>
									<th>' . __($DescriptionHeader) . '</th>
									<th class="number">' . __('Quantity') . '</th>
									<th class="number">' . __('Extended Cost') . '</th>
									<th class="number">' . __('Extended Price') . '</th>
									<th class="number">' . __('Invoiced Qty') . '</th>
									<th>' . __($ColumnHeader7) . '</th>
								</tr>
							</thead>
							<tbody>';

				$Column7 = ' ';
				$Linectr = 0;
			while ($MyRow = DB_fetch_array($Result)) {
			    $Linectr++;
				if ($SummaryType == 'orderno') {
				    $Column7 = $MyRow['name'];
				}
				if ($SummaryType == 'transno') {
				    $Column7 =  $MyRow['orderno'];
				}
				if ($_POST['DateType'] == 'Order') {
				    // quantity is from salesorderdetails
				    $DisplayQty = $MyRow['quantity'];
				} else {
				    // qty is from stockmoves
				    $DisplayQty = $MyRow['qty'];
				}
				echo '<tr>
						<td>' . $MyRow[$SummaryType] . '</td>
						<td>' . $MyRow[$Description] . '</td>
						<td class="number">' . locale_number_format($DisplayQty, 2) . '</td>
						<td class="number">' . locale_number_format($MyRow['extcost'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['extprice'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
						<td class="number">' . locale_number_format($MyRow['qtyinvoiced'], 2) . '</td>
						<td>' . $Column7 . '</td>
					</tr>';
				$TotalQty += $DisplayQty;
				$TotalExtCost += $MyRow['extcost'];
				$TotalExtPrice += $MyRow['extprice'];
				$TotalInvQty += $MyRow['qtyinvoiced'];
			} //END WHILE LIST LOOP
			// Print totals
			echo '</tbody>
					</table>
				</div>
			</div>
		</div>';
		} // End of if ($_POST['ReportType']

    } // End of if inputerror != 1
} // End of function submit()


function display()  //####DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_DISPLAY_#####
{
// Display form fields. This function is called the first time
// the page is called.

	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title">' . __('Inquiry Criteria') . '</div>
			</div>
			<div class="db-card-body">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<div class="db-fieldset">
					<div class="db-field">
						<label class="db-label">' . __('Report Type') . ':</label>
						<select name="ReportType" class="db-input">
							<option selected="selected" value="Detail">' . __('Detail') . '</option>
							<option value="Summary">' . __('Summary') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Order Type') . ':</label>
						<select name="OrderType" class="db-input">
							<option selected="selected" value="0">' . __('Sales Order') . '</option>
							<option value="1">' . __('Quotation') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Date Type') . ':</label>
						<select name="DateType" class="db-input">
							<option selected="selected" value="Order">' . __('Order Date') . '</option>
							<option value="Invoice">' . __('Invoice Date') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Invoice Type') . ':</label>
						<select name="InvoiceType" class="db-input">
							<option selected="selected" value="All">' . __('All') . '</option>
							<option value="10">' . __('Sales Invoice') . '</option>
							<option value="11">' . __('Credit Note') . '</option>
						</select>
						<span class="db-field-help">' . __('Only Applies To Invoice Date Type') . '</span>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Date Range') . ':</label>
						<div class="db-date-range">
							<input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
							<span>' . __('To') . '</span>
							<input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Stock Code') . ':</label>
						<div class="db-input-group">
							<select name="PartNumberOp" class="db-input db-input-small">
								<option selected="selected" value="Equals">' . __('Equals') . '</option>
								<option value="LIKE">' . __('Begins With') . '</option>
							</select>
							<input type="text" name="PartNumber" class="db-input" value="'. ($_POST['PartNumber'] ?? '') . '" />
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Customer Number') . ':</label>
						<div class="db-input-group">
							<select name="DebtorNoOp" class="db-input db-input-small">
								<option selected="selected" value="Equals">' . __('Equals') . '</option>
								<option value="LIKE">' . __('Begins With') . '</option>
							</select>
							<input type="text" name="DebtorNo" class="db-input" value="' . ($_POST['DebtorNo'] ?? '') . '" />
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Customer Name') . ':</label>
						<div class="db-input-group">
							<select name="DebtorNameOp" class="db-input db-input-small">
								<option selected="selected" value="LIKE">' . __('Begins With') . '</option>
								<option value="Equals">' . __('Equals') . '</option>
							</select>
							<input type="text" name="DebtorName" class="db-input" value="' . ($_POST['DebtorName'] ?? '') .'" />
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Order Number') . ':</label>
						<div class="db-input-group">
							<span class="db-input-addon">' . __('Equals') . '</span>
							<input type="text" name="OrderNo" class="db-input" value="' . ($_POST['OrderNo'] ?? '') . '" />
						</div>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Line Item Status') . ':</label>
						<select name="LineStatus" class="db-input">
							<option selected="selected" value="All">' . __('All') . '</option>
							<option value="Completed">' . __('Completed') . '</option>
							<option value="Open">' . __('Not Completed') . '</option>
						</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Stock Categories') . ':</label>
						<select name="Category" class="db-input">';
	$CategoryResult = DB_query("SELECT categoryid, categorydescription FROM stockcategory");
	echo '<option selected="selected" value="All">' . __('All Categories')  . '</option>';
	while($MyRow = DB_fetch_array($CategoryResult)) {
		echo '<option value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription']  . '</option>';
	}
	echo '</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('For Sales Person') . ':</label>';
	if ($_SESSION['SalesmanLogin'] != '') {
		echo '<span class="db-input-text">' . $_SESSION['UsersRealName'] . '</span>';
	} else {
		echo '<select name="Salesman" class="db-input">';
		$SQL="SELECT salesmancode, salesmanname FROM salesman";
		$SalesmanResult = DB_query($SQL);
		echo '<option selected="selected" value="All">' . __('All Salespeople')  . '</option>';
		while($MyRow = DB_fetch_array($SalesmanResult)) {
			echo '<option value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname']  . '</option>';
		}
		echo '</select>';
	}
	echo '</div>
					<div class="db-field">
						<label class="db-label">' . __('For Sales Areas') . ':</label>
						<select name="Area" class="db-input">';
	$AreasResult = DB_query("SELECT areacode, areadescription FROM areas");
	echo '<option selected="selected" value="All">' . __('All Areas')  . '</option>';
	while($MyRow = DB_fetch_array($AreasResult)) {
		echo '<option value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription']  . '</option>';
	}
	echo '</select>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Sort By') . ':</label>
						<select name="SortBy" class="db-input">
							<option selected="selected" value="salesorderdetails.orderno">' . __('Order Number') . '</option>
							<option value="salesorderdetails.stkcode">' . __('Stock Code') . '</option>
							<option value="debtorsmaster.debtorno,salesorderdetails.orderno">' . __('Customer Number') . '</option>
							<option value="debtorsmaster.name,debtorsmaster.debtorno,salesorderdetails.orderno">' . __('Customer Name') . '</option>
							<option value="tempstockmoves.transno,salesorderdetails.stkcode">' . __('Transaction Number') . '</option>
						</select>
						<span class="db-field-help">' . __('Transaction Number sort only valid for Invoice Date Type') . '</span>
					</div>
					<div class="db-field">
						<label class="db-label">' . __('Summary Type') . ':</label>
						<select name="SummaryType" class="db-input">
							<option selected="selected" value="orderno">' . __('Order Number') . '</option>
							<option value="transno">' . __('Transaction Number') . '</option>
							<option value="stkcode">' . __('Stock Code') . '</option>
							<option value="extprice">' . __('Extended Price') . '</option>
							<option value="debtorno">' . __('Customer Code') . '</option>
							<option value="name">' . __('Customer Name') . '</option>
							<option value="month">' . __('Month') . '</option>
							<option value="categoryid">' . __('Stock Category') . '</option>
							<option value="salesman">' . __('Salesman') . '</option>
							<option value="area">' . __('Sales Area') . '</option>
						</select>
						<span class="db-field-help">' . __('Transaction Number summary only valid for Invoice Date Type') . '</span>
					</div>
				</div>
			</div>
			<div class="db-card-footer">
				<div class="db-actions">
					<input type="submit" name="submit" class="db-btn db-btn-primary" value="' . __('Run Inquiry') . '" />
				</div>
			</div>
			</form>
		</div>';

} // End of function display()

function TempStockmoves() {
// When report based on Invoice Date, use stockmoves as the main file, but credit
// notes, which are type 11 in stockmoves, do not have the order number in the
// reference field; instead they have "Ex Inv - " and then the transno from the
// type 10 stockmoves the credit note was applied to. Use this function to load all
// type 10 and 11 stockmoves into a temporary table and then update the
// reference field for type 11 records with the orderno from the type 10 records.

	$FromDate = FormatDateForSQL($_POST['FromDate']);
	$ToDate = FormatDateForSQL($_POST['ToDate']);

	$SQL = "CREATE TEMPORARY TABLE tempstockmoves LIKE stockmoves";
	$ErrMsg = __('The SQL to the create temp stock moves table failed with the message');
	$Result = DB_query($SQL, $ErrMsg);

	$SQL = "INSERT tempstockmoves
	          SELECT * FROM stockmoves
	          WHERE (stockmoves.type='10' OR stockmoves.type='11')
	          AND stockmoves.trandate >='" . $FromDate .
			  "' AND stockmoves.trandate <='" . $ToDate . "'";
	$ErrMsg = __('The SQL to insert temporary stockmoves records failed with the message');
	$Result = DB_query($SQL, $ErrMsg);

	$SQL = "UPDATE tempstockmoves, stockmoves
	          SET tempstockmoves.reference = stockmoves.reference
	          WHERE tempstockmoves.type='11'
	            AND SUBSTR(tempstockmoves.reference,10,10) = stockmoves.transno
                AND tempstockmoves.stockid = stockmoves.stockid
                AND stockmoves.type ='10'";
	$ErrMsg = __('The SQL to update tempstockmoves failed with the message');
	$Result = DB_query($SQL, $ErrMsg);


} // End of function TempStockmoves

echo '</div><!-- .db-page -->';

include(__DIR__ . '/includes/footer.php');
