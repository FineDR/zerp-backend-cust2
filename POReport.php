<?php

// Inquiry on Purchase Orders

// If Date Type is Order, the main file is purchorderdetails
// If Date Type is Delivery, the main file is grns

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'PurchaseOrdering';
$BookMark = 'POReport';
$Title = __('Purchase Order Report');

$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 28px; border-radius: 12px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; width: 100%;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.registry-table th { background: #f9fafb; padding: 16px 20px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 900; color: #065f46; letter-spacing: 1.2px; border-bottom: 1px solid #f3f4f6; }
	.registry-table td { padding: 16px 20px; font-size: 0.88rem; color: #374151; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
	.registry-table tr:hover td { background: #f0fdf4; }
	
	.badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
	
	.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px; }
	.kpi-card-v2 { background: #fff; border-radius: 20px; padding: 24px; display: flex; align-items: center; gap: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
	.kpi-icon { width: 56px; height: 56px; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
	.kpi-data { display: flex; flex-direction: column; }
	.kpi-data .label { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 4px; }
	.kpi-data .value { font-size: 1.5rem; font-weight: 800; color: #111827; line-height: 1.2; }

	@media (max-width: 1100px) {
		.custom-bottom-layout { display: flex; flex-direction: column; }
		.db-sidebar { width: 100%; }
	}
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

# Sets default date range for current month
if (!isset($_POST['FromDate'])){
	$_POST['FromDate']=date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m'),1,date('Y')));
}
if (!isset($_POST['ToDate'])){
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

$PartNumber = '';
$PartNumberOp = 'LIKE';
$SupplierId = '';
$SupplierIdOp = 'LIKE';
$SupplierName = '';
$SupplierNameOp = 'LIKE';
$SaveSummaryType = '';

if (isset($_POST['submit']) or isset($_POST['submitcsv'])) {
	if (isset($_POST['PartNumber'])){
		$PartNumber = trim(mb_strtoupper($_POST['PartNumber']));
	} elseif (isset($_GET['PartNumber'])){
		$PartNumber = trim(mb_strtoupper($_GET['PartNumber']));
	}

	# Part Number operator - either LIKE or =
	$PartNumberOp = $_POST['PartNumberOp'];

	if (isset($_POST['SupplierId'])){
		$SupplierId = trim(mb_strtoupper($_POST['SupplierId']));
	} elseif (isset($_GET['SupplierId'])){
		$SupplierId = trim(mb_strtoupper($_GET['SupplierId']));
	}

	$SupplierIdOp = $_POST['SupplierIdOp'];
	$SupplierNameOp = $_POST['SupplierNameOp'];
	$SaveSummaryType = $_POST['SummaryType'];
}

if (isset($_POST['SupplierName'])){
	$SupplierName = trim(mb_strtoupper($_POST['SupplierName']));
} elseif (isset($_GET['SupplierName'])){
	$SupplierName = trim(mb_strtoupper($_GET['SupplierName']));
}

// Had to add supplierid to SummaryType when do summary by name because there could be several accounts
// with the same name. Tried passing 'suppname,supplierid' in form, but it only read 'suppname'
if (isset($_POST['SummaryType']) and $_POST['SummaryType'] == 'suppname') {
	$_POST['SummaryType'] = "suppname, suppliers.supplierid";
}

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=PO" class="breadcrumb-item">' . __('purchasing') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('purchase order inquiry') . '</span>
					</div>
					<div>
						<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
						<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Analyze procurement cycles and purchase order distributions across suppliers') . '</p>
					</div>
				</div>
			</div>
		</div>

		<div class="custom-bottom-layout">
			<aside class="db-sidebar">';
display();
echo '		</aside>
			<main class="db-main">';

if (isset($_POST['submit'])) {
	submit($PartNumber,$PartNumberOp,$SupplierId,$SupplierIdOp,$SupplierName,$SupplierNameOp,$SaveSummaryType);
} elseif (isset($_POST['submitcsv'])) {
	submitcsv($PartNumber,$PartNumberOp,$SupplierId,$SupplierIdOp,$SupplierName,$SupplierNameOp,$SaveSummaryType);
} else {
	echo '<div style="padding: 100px 30px; text-align: center; background: #fff; border-radius: 20px; border: 1px solid #e5e7eb;">
				<div style="width: 80px; height: 80px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
					<i class="fas fa-search-dollar" style="font-size: 2rem; color: #059669;"></i>
				</div>
				<h3 style="margin: 0; color: #374151; font-weight: 800; font-size: 1.25rem;">' . __('Ready to Generate Report') . '</h3>
				<p style="margin: 12px auto; color: #6b7280; max-width: 400px; line-height: 1.6;">
					' . __('Select your reporting parameters in the sidebar and click generate to analyze your purchase order data.') . '
				</p>
			</div>';
}

echo '		</main>
		</div>
	  </div>';

include(__DIR__ . '/includes/footer.php');
exit; // Prevent further output from original code



//####_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT####
function getReportSQL($PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp) {
	$WherePart = ' ';
	if (mb_strlen($PartNumber) > 0 && $PartNumberOp == 'LIKE') {
		$PartNumber = $PartNumber . '%';
	} else {
		$PartNumberOp = '=';
	}
	if (mb_strlen($PartNumber) > 0) {
		$WherePart = " AND purchorderdetails.itemcode " . $PartNumberOp . " '" . $PartNumber . "'  ";
	}

	$WhereSupplierID = ' ';
	if ($SupplierIdOp == 'LIKE') {
		$SupplierId = $SupplierId . '%';
	} else {
		$SupplierIdOp = '=';
	}
	if (mb_strlen($SupplierId) > 0) {
		$WhereSupplierID = " AND purchorders.supplierno " . $SupplierIdOp . " '" . $SupplierId . "'  ";
	}

	$WhereSupplierName = ' ';
	if (mb_strlen($SupplierName) > 0 AND $SupplierNameOp == 'LIKE') {
		$SupplierName = $SupplierName . '%';
	} else {
		$SupplierNameOp = '=';
	}
	if (mb_strlen($SupplierName) > 0) {
		$WhereSupplierName = " AND suppliers.suppname " . $SupplierNameOp . " '" . $SupplierName . "'  ";
	}

	if (mb_strlen($_POST['OrderNo']) > 0) {
		$WhereOrderNo = " AND purchorderdetails.orderno = '" . $_POST['OrderNo'] . "'  ";
	} else {
		$WhereOrderNo=' ';
	}

	$WhereLineStatus = ' ';
	if ($_POST['LineStatus'] != 'All') {
		if ($_POST['DateType'] == 'Order') {
			$WhereLineStatus = " AND if (purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
			  purchorderdetails.completed = 1,'Completed','Open') = '" . $_POST['LineStatus'] . "'";
		} else {
			$WhereLineStatus = " AND if (grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') = '"
			. $_POST['LineStatus'] . "'";
		}
	}

	$WhereCategory = ' ';
	if ($_POST['Category'] != 'All') {
		$WhereCategory = " AND stockmaster.categoryid = '" . $_POST['Category'] . "'";
	}

	$FromDate = FormatDateForSQL($_POST['FromDate']);
	$ToDate = FormatDateForSQL($_POST['ToDate']);
	
	if ($_POST['ReportType'] == 'Detail') {
		if ($_POST['DateType'] == 'Order') {
			$SQL = "SELECT purchorderdetails.orderno,
						   purchorderdetails.itemcode,
						   purchorderdetails.deliverydate,
						   purchorders.supplierno,
						   purchorders.orddate,
						   purchorderdetails.quantityord,
						   purchorderdetails.quantityrecd,
						   purchorderdetails.qtyinvoiced,
						   (purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
						   (purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
						   if (purchorderdetails.quantityord = purchorderdetails.qtyinvoiced ||
							  purchorderdetails.completed = 1,'Completed','Open') as linestatus,
						   suppliers.suppname,
						   stockmaster.decimalplaces,
						   stockmaster.description
						   FROM purchorderdetails
					LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
					LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
					LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
					WHERE purchorders.orddate >='$FromDate'
					 AND purchorders.orddate <='$ToDate'
					$WherePart
					$WhereSupplierID
					$WhereSupplierName
					$WhereOrderNo
					$WhereLineStatus
					$WhereCategory
					ORDER BY " . $_POST['SortBy'];
		} else {
			$SQL = "SELECT purchorderdetails.orderno,
						   purchorderdetails.itemcode,
						   grns.deliverydate,
						   purchorders.supplierno,
						   purchorders.orddate,
						   purchorderdetails.quantityord as quantityrecd,
						   grns.qtyrecd as quantityord,
						   grns.quantityinv as qtyinvoiced,
						   (grns.qtyrecd * purchorderdetails.unitprice) as extprice,
						   (grns.qtyrecd * grns.stdcostunit) as extcost,
						   if (grns.qtyrecd - grns.quantityinv <> 0,'Open','Completed') as linestatus,
						   suppliers.suppname,
						   stockmaster.decimalplaces,
						   stockmaster.description
						   FROM grns
					LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
					LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
					LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
					LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
					WHERE grns.deliverydate >='$FromDate'
					 AND grns.deliverydate <='$ToDate'
					$WherePart
					$WhereSupplierID
					$WhereSupplierName
					$WhereOrderNo
					$WhereLineStatus
					$WhereCategory
					ORDER BY " . $_POST['SortBy'];
		}
	} else {
		$OrderBy = $_POST['SummaryType'];
		$GroupBy = $_POST['SummaryType'];
		if ($_POST['SummaryType'] == 'extprice') {
			$GroupBy = 'itemcode';
			$OrderBy = 'extprice DESC';
		}
		if ($_POST['DateType'] == 'Order') {
			if ($GroupBy == 'itemcode' || $GroupBy == 'extprice') {
				$SQL = "SELECT purchorderdetails.itemcode as grouping_id,
							   SUM(purchorderdetails.quantityord) as quantityord,
							   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
							   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
							   stockmaster.decimalplaces,
							   stockmaster.description as grouping_desc
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorderdetails.itemcode, stockmaster.decimalplaces, stockmaster.description
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'orderno') {
				$SQL = "SELECT purchorderdetails.orderno as grouping_id,
							   purchorders.supplierno as grouping_desc,
							   SUM(purchorderdetails.quantityord) as quantityord,
							   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
							   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
							   suppliers.suppname as suppname_extra
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorderdetails.orderno, purchorders.supplierno, suppliers.suppname
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'supplierno' || $GroupBy == 'suppname, suppliers.supplierid') {
				$SQL = "SELECT purchorders.supplierno as grouping_id,
							   SUM(purchorderdetails.quantityord) as quantityord,
							   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
							   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost,
							   suppliers.suppname as grouping_desc
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorders.supplierno, suppliers.suppname
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'month') {
				$SQL = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
							   CONCAT(MONTHNAME(purchorders.orddate),' ',YEAR(purchorders.orddate)) as grouping_desc,
							   SUM(purchorderdetails.quantityord) as quantityord,
							   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
							   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY month, grouping_desc
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'categoryid') {
				$SQL = "SELECT stockmaster.categoryid as grouping_id,
							   stockcategory.categorydescription as grouping_desc,
							   SUM(purchorderdetails.quantityord) as quantityord,
							   SUM(purchorderdetails.qtyinvoiced) as qtyinvoiced,
							   SUM(purchorderdetails.quantityord * purchorderdetails.unitprice) as extprice,
							   SUM(purchorderdetails.quantityord * purchorderdetails.stdcostunit) as extcost
							   FROM purchorderdetails
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE purchorders.orddate >='$FromDate'
						 AND purchorders.orddate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY stockmaster.categoryid, stockcategory.categorydescription
						ORDER BY " . $OrderBy;
			}
		} else {
			// Delivery Date mode
			if ($GroupBy == 'itemcode' || $GroupBy == 'extprice') {
				$SQL = "SELECT purchorderdetails.itemcode as grouping_id,
							   SUM(grns.qtyrecd) as quantityord,
							   SUM(grns.quantityinv) as qtyinvoiced,
							   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
							   stockmaster.description as grouping_desc
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorderdetails.itemcode, stockmaster.description
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'orderno') {
				$SQL = "SELECT purchorderdetails.orderno as grouping_id,
							   purchorders.supplierno as grouping_desc,
							   SUM(grns.qtyrecd) as quantityord,
							   SUM(grns.quantityinv) as qtyinvoiced,
							   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
							   suppliers.suppname as suppname_extra
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorderdetails.orderno, purchorders.supplierno, suppliers.suppname
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'supplierno' || $GroupBy == 'suppname, suppliers.supplierid') {
				$SQL = "SELECT purchorders.supplierno as grouping_id,
							   SUM(grns.qtyrecd) as quantityord,
							   SUM(grns.quantityinv) as qtyinvoiced,
							   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   SUM(grns.qtyrecd * grns.stdcostunit) as extcost,
							   suppliers.suppname as grouping_desc
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY purchorders.supplierno, suppliers.suppname
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'month') {
				$SQL = "SELECT EXTRACT(YEAR_MONTH from purchorders.orddate) as month,
							   CONCAT(MONTHNAME(purchorders.orddate),' ',YEAR(purchorders.orddate)) as grouping_desc,
							   SUM(grns.qtyrecd) as quantityord,
							   SUM(grns.quantityinv) as qtyinvoiced,
							   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   SUM(grns.qtyrecd * grns.stdcostunit) as extcost
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY month, grouping_desc
						ORDER BY " . $OrderBy;
			} elseif ($GroupBy == 'categoryid') {
				$SQL = "SELECT stockmaster.categoryid as grouping_id,
							   stockcategory.categorydescription as grouping_desc,
							   SUM(grns.qtyrecd) as quantityord,
							   SUM(grns.quantityinv) as qtyinvoiced,
							   SUM(grns.qtyrecd * purchorderdetails.unitprice) as extprice,
							   SUM(grns.qtyrecd * grns.stdcostunit) as extcost
							   FROM grns
						LEFT JOIN purchorderdetails ON grns.podetailitem = purchorderdetails.podetailitem
						LEFT JOIN purchorders ON purchorders.orderno=purchorderdetails.orderno
						LEFT JOIN suppliers ON purchorders.supplierno = suppliers.supplierid
						LEFT JOIN stockmaster ON purchorderdetails.itemcode = stockmaster.stockid
						LEFT JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
						WHERE grns.deliverydate >='$FromDate'
						 AND grns.deliverydate <='$ToDate'
						$WherePart
						$WhereSupplierID
						$WhereSupplierName
						$WhereOrderNo
						$WhereLineStatus
						$WhereCategory
						GROUP BY stockmaster.categoryid, stockcategory.categorydescription
						ORDER BY " . $OrderBy;
			}
		}
	}
	return $SQL;
}

function submit($PartNumber,$PartNumberOp,$SupplierId,$SupplierIdOp,$SupplierName,$SupplierNameOp,$SaveSummaryType) {
	global $RootPath;
	$SQL = getReportSQL($PartNumber,$PartNumberOp,$SupplierId,$SupplierIdOp,$SupplierName,$SupplierNameOp);
	$Result = DB_query($SQL);
	
	$TotalQty = 0;
	$TotalRecdQty = 0;
	$TotalExtCost = 0;
	$TotalExtPrice = 0;
	$TotalInvQty = 0;
	$Rows = [];
	while ($MyRow = DB_fetch_array($Result)) {
		$Rows[] = $MyRow;
		$TotalQty += ($MyRow['quantityord'] ?? 0);
		$TotalRecdQty += ($MyRow['quantityrecd'] ?? 0);
		$TotalExtCost += ($MyRow['extcost'] ?? 0);
		$TotalExtPrice += ($MyRow['extprice'] ?? 0);
		$TotalInvQty += ($MyRow['qtyinvoiced'] ?? 0);
	}

	// KPI Metrics
	echo '<div class="kpi-grid">
			<div class="kpi-card-v2">
				<div class="kpi-icon" style="background: #ecfdf5; color: #059669;"><i class="fas fa-shopping-cart"></i></div>
				<div class="kpi-data"><span class="label">' . __('Total Items') . '</span><span class="value">' . locale_number_format(count($Rows), 0) . '</span></div>
			</div>
			<div class="kpi-grid-nested" style="display: contents;">
				<div class="kpi-card-v2">
					<div class="kpi-icon" style="background: #eff6ff; color: #1d4ed8;"><i class="fas fa-boxes"></i></div>
					<div class="kpi-data"><span class="label">' . __('Total Qty') . '</span><span class="value">' . locale_number_format($TotalQty, 0) . '</span></div>
				</div>
				<div class="kpi-card-v2">
					<div class="kpi-icon" style="background: #fff7ed; color: #ea580c;"><i class="fas fa-money-bill-wave"></i></div>
					<div class="kpi-data"><span class="label">' . __('Total Value') . '</span><span class="value">' . locale_number_format($TotalExtPrice, 2) . '</span></div>
				</div>
				<div class="kpi-card-v2">
					<div class="kpi-icon" style="background: #fef2f2; color: #dc2626;"><i class="fas fa-chart-pie"></i></div>
					<div class="kpi-data"><span class="label">' . __('Budget Cost') . '</span><span class="value">' . locale_number_format($TotalExtCost, 2) . '</span></div>
				</div>
			</div>
		  </div>';

	if ($_POST['ReportType'] == 'Detail') {
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Detailed Transactions') . '</h3>
				</div>
				<div class="db-card-body" style="padding: 0; overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Order') . '</th>
								<th>' . __('Item') . '</th>
								<th>' . ($_POST['DateType'] == 'Order' ? __('Order Date') : __('Delivery Date')) . '</th>
								<th>' . __('Supplier') . '</th>
								<th style="text-align: right;">' . __('Qty') . '</th>
								<th style="text-align: right;">' . (($_POST['DateType'] == 'Order') ? __('Recd') : __('Target')) . '</th>
								<th style="text-align: right;">' . __('Value') . '</th>
								<th>' . __('Status') . '</th>
							</tr>
						</thead>
						<tbody>';
		foreach ($Rows as $MyRow) {
			echo '<tr>
					<td style="font-weight: 700;"><a href="'. $RootPath . '/PO_OrderDetails.php?OrderNo=', $MyRow['orderno'], '" style="color: #059669; text-decoration: none;">', $MyRow['orderno'], '</a></td>
					<td><div style="font-weight: 600;">', $MyRow['itemcode'], '</div><div style="font-size: 0.75rem; opacity: 0.6;">', $MyRow['description'], '</div></td>
					<td style="white-space: nowrap;">', ConvertSQLDate(($_POST['DateType'] == 'Order' ? $MyRow['orddate'] : $MyRow['deliverydate'])), '</td>
					<td><div style="font-weight: 600;">', $MyRow['suppname'], '</div><div style="font-size: 0.75rem; opacity: 0.6;">', $MyRow['supplierno'], '</div></td>
					<td style="text-align: right;">', locale_number_format($MyRow['quantityord'], ($MyRow['decimalplaces'] ?? 0)), '</td>
					<td style="text-align: right;">', locale_number_format($MyRow['quantityrecd'], ($MyRow['decimalplaces'] ?? 0)), '</td>
					<td style="text-align: right; font-weight: 700; color: #064e3b;">', locale_number_format($MyRow['extprice'], 2), '</td>
					<td><span class="badge" style="background:', ($MyRow['linestatus'] == 'Completed' ? '#ecfdf5; color: #047857;' : '#fff7ed; color: #9a3412;'), '">', __($MyRow['linestatus']), '</span></td>
				</tr>';
		}
		echo '</tbody>
				<tfoot style="background: #f9fafb; font-weight: 800;">
					<tr>
						<td colspan="4" style="text-align: right; padding: 20px;">' . __('TOTALS') . '</td>
						<td style="text-align: right;">' . locale_number_format($TotalQty, 0) . '</td>
						<td style="text-align: right;">' . locale_number_format($TotalRecdQty, 0) . '</td>
						<td style="text-align: right; color: #064e3b;">' . locale_number_format($TotalExtPrice, 2) . '</td>
						<td></td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-chart-bar"></i> ' . __('Consolidated Summary') . '</h3>
				</div>
				<div class="db-card-body" style="padding: 0; overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Grouping') . '</th>
								<th>' . __('Description') . '</th>
								<th style="text-align: right;">' . __('Total Qty') . '</th>
								<th style="text-align: right;">' . __('Inv Qty') . '</th>
								<th style="text-align: right;">' . __('Total Value') . '</th>
							</tr>
						</thead>
						<tbody>';
		foreach ($Rows as $MyRow) {
			echo '<tr>
					<td style="font-weight: 700;">', $MyRow['grouping_id'], '</td>
					<td>', $MyRow['grouping_desc'], '</td>
					<td style="text-align: right;">', locale_number_format($MyRow['quantityord'], 0), '</td>
					<td style="text-align: right;">', locale_number_format($MyRow['qtyinvoiced'], 0), '</td>
					<td style="text-align: right; font-weight: 700; color: #064e3b;">', locale_number_format($MyRow['extprice'], 2), '</td>
				</tr>';
		}
		echo '</tbody>
				<tfoot style="background: #f9fafb; font-weight: 800;">
					<tr>
						<td colspan="2" style="text-align: right; padding: 20px;">' . __('TOTALS') . '</td>
						<td style="text-align: right;">' . locale_number_format($TotalQty, 0) . '</td>
						<td style="text-align: right;">' . locale_number_format($TotalInvQty, 0) . '</td>
						<td style="text-align: right; color: #064e3b;">' . locale_number_format($TotalExtPrice, 2) . '</td>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>';
	}
}

//####_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT_SUBMIT####
function submitcsv($PartNumber, $PartNumberOp, $SupplierId, $SupplierIdOp, $SupplierName, $SupplierNameOp, $SaveSummaryType) {
	$SQL = getReportSQL($PartNumber,$PartNumberOp,$SupplierId,$SupplierIdOp,$SupplierName,$SupplierNameOp);
	$Result = DB_query($SQL);
	
	$FileName = $_SESSION['reports_dir'] .'/POReport.csv';
	$FileHandle = fopen($FileName, 'w');
	
	if ($_POST['ReportType'] == 'Detail') {
		fputcsv($FileHandle, [__('Order No'), __('Item'), __('Date'), __('Supplier'), __('Qty'), __('Recd'), __('Cost'), __('Value'), __('Status')]);
		while ($MyRow = DB_fetch_array($Result)) {
			fputcsv($FileHandle, [
				$MyRow['orderno'],
				$MyRow['itemcode'],
				ConvertSQLDate(($_POST['DateType'] == 'Order' ? $MyRow['orddate'] : $MyRow['deliverydate'])),
				$MyRow['suppname'],
				$MyRow['quantityord'],
				$MyRow['quantityrecd'],
				$MyRow['extcost'],
				$MyRow['extprice'],
				$MyRow['linestatus']
			]);
		}
	} else {
		fputcsv($FileHandle, [__('Grouping'), __('Description'), __('Total Qty'), __('Inv Qty'), __('Total Cost'), __('Total Value')]);
		while ($MyRow = DB_fetch_array($Result)) {
			fputcsv($FileHandle, [
				$MyRow['grouping_id'],
				$MyRow['grouping_desc'],
				$MyRow['quantityord'],
				$MyRow['qtyinvoiced'],
				$MyRow['extcost'],
				$MyRow['extprice']
			]);
		}
	}
	fclose($FileHandle);
	
	echo '<div class="db-card" style="margin-top: 24px; border: 1px solid #d1fae5; background: #f0fdf4;">
			<div class="db-card-body" style="text-align: center; padding: 40px;">
				<i class="fas fa-file-csv" style="font-size: 3rem; color: #059669; margin-bottom: 20px;"></i>
				<h3 style="color: #064e3b; margin-bottom: 12px;">' . __('CSV Export Ready') . '</h3>
				<p style="color: #065f46; margin-bottom: 24px;">' . __('Your purchase order report has been compiled into a spreadsheet format.') . '</p>
				<a href="' .  $FileName . '" class="architect-btn" style="width: auto; display: inline-flex;">
					<i class="fas fa-download"></i> ' . __('Download CSV File') . '
				</a>
			</div>
		  </div>';
} // End of function submitcsv()


function display() {
	echo '<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
			<div class="db-card-header">
				<h3 class="db-card-title">
					<i class="fas fa-search" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Report Parameters') . '
				</h3>
			</div>
			<div style="padding: 24px; background: #fff;">
				<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" style="display: flex; flex-direction: column; gap: 20px;">
					<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
					
					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Report Type') . '</label>
						<select name="ReportType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option ' . (($_POST['ReportType'] ?? 'Detail') == 'Detail' ? 'selected' : '') . ' value="Detail">' . __('Detail Report') . '</option>
							<option ' . (($_POST['ReportType'] ?? '') == 'Summary' ? 'selected' : '') . ' value="Summary">' . __('Summary Report') . '</option>
						</select>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Date Type') . '</label>
						<select name="DateType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option ' . (($_POST['DateType'] ?? 'Order') == 'Order' ? 'selected' : '') . ' value="Order">' . __('Order Date') . '</option>
							<option ' . (($_POST['DateType'] ?? '') == 'Delivery' ? 'selected' : '') . ' value="Delivery">' . __('Delivery Date') . '</option>
						</select>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Date Range') . '</label>
						<div style="grid-template-columns: 1fr 1fr; display: grid; gap: 8px;">
							<input type="date" name="FromDate" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" style="border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 12px;" />
							<input type="date" name="ToDate" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" style="border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 12px;" />
						</div>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Part Number Filter') . '</label>
						<div style="display: flex; gap: 8px;">
							<select name="PartNumberOp" class="db-input" style="width: 120px; border-radius: 12px; border-color: #d1fae5;">
								<option ' . (($_POST['PartNumberOp'] ?? '') == 'Equals' ? 'selected' : '') . ' value="Equals">' . __('Is') . '</option>
								<option ' . (($_POST['PartNumberOp'] ?? 'LIKE') == 'LIKE' ? 'selected' : '') . ' value="LIKE">' . __('Like') . '</option>
							</select>
							<input type="text" name="PartNumber" class="db-input" value="' . ($_POST['PartNumber'] ?? '') . '" style="flex: 1; border-radius: 12px; border-color: #d1fae5;" placeholder="' . __('Code...') . '" />
						</div>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Supplier Number') . '</label>
						<div style="display: flex; gap: 8px;">
							<select name="SupplierIdOp" class="db-input" style="width: 120px; border-radius: 12px; border-color: #d1fae5;">
								<option ' . (($_POST['SupplierIdOp'] ?? '') == 'Equals' ? 'selected' : '') . ' value="Equals">' . __('Is') . '</option>
								<option ' . (($_POST['SupplierIdOp'] ?? 'LIKE') == 'LIKE' ? 'selected' : '') . ' value="LIKE">' . __('Like') . '</option>
							</select>
							<input type="text" name="SupplierId" class="db-input" value="' . ($_POST['SupplierId'] ?? '') . '" style="flex: 1; border-radius: 12px; border-color: #d1fae5;" placeholder="' . __('ID...') . '" />
						</div>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Supplier Name') . '</label>
						<div style="display: flex; gap: 8px;">
							<select name="SupplierNameOp" class="db-input" style="width: 120px; border-radius: 12px; border-color: #d1fae5;">
								<option ' . (($_POST['SupplierNameOp'] ?? 'LIKE') == 'LIKE' ? 'selected' : '') . ' value="LIKE">' . __('Like') . '</option>
								<option ' . (($_POST['SupplierNameOp'] ?? '') == 'Equals' ? 'selected' : '') . ' value="Equals">' . __('Is') . '</option>
							</select>
							<input type="text" name="SupplierName" class="db-input" value="' . ($_POST['SupplierName'] ?? '') . '" style="flex: 1; border-radius: 12px; border-color: #d1fae5;" placeholder="' . __('Name...') . '" />
						</div>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Order Number') . '</label>
						<input type="text" name="OrderNo" class="db-input" value="' . ($_POST['OrderNo'] ?? '') . '" style="width: 100%; border-radius: 12px; border-color: #d1fae5;" placeholder="' . __('Specific PO #') . '" />
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Line Status') . '</label>
						<select name="LineStatus" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option ' . (($_POST['LineStatus'] ?? 'All') == 'All' ? 'selected' : '') . ' value="All">' . __('All Lines') . '</option>
							<option ' . (($_POST['LineStatus'] ?? '') == 'Completed' ? 'selected' : '') . ' value="Completed">' . __('Completed') . '</option>
							<option ' . (($_POST['LineStatus'] ?? '') == 'Open' ? 'selected' : '') . ' value="Open">' . __('Not Completed') . '</option>
						</select>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Stock Category') . '</label>
						<select name="Category" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';
	$SQL="SELECT categoryid, categorydescription FROM stockcategory";
	$CategoryResult = DB_query($SQL);
	echo '<option value="All">' . __('All Categories') . '</option>';
	while ($MyRow = DB_fetch_array($CategoryResult)){
		$selected = (($_POST['Category'] ?? 'All') == $MyRow['categoryid'] ? 'selected' : '');
		echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
	}
	echo '				</select>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Sort By') . '</label>
						<select name="SortBy" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option ' . (($_POST['SortBy'] ?? 'purchorderdetails.orderno') == 'purchorderdetails.orderno' ? 'selected' : '') . ' value="purchorderdetails.orderno">' . __('Order Number') . '</option>
							<option ' . (($_POST['SortBy'] ?? '') == 'purchorderdetails.itemcode' ? 'selected' : '') . ' value="purchorderdetails.itemcode">' . __('Part Number') . '</option>
							<option ' . (($_POST['SortBy'] ?? '') == 'suppliers.supplierid,purchorderdetails.orderno' ? 'selected' : '') . ' value="suppliers.supplierid,purchorderdetails.orderno">' . __('Supplier Number') . '</option>
							<option ' . (($_POST['SortBy'] ?? '') == 'suppliers.suppname,suppliers.supplierid,purchorderdetails.orderno' ? 'selected' : '') . ' value="suppliers.suppname,suppliers.supplierid,purchorderdetails.orderno">' . __('Supplier Name') . '</option>
						</select>
					</div>

					<div class="db-form-group">
						<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Summary Grouping') . '</label>
						<select name="SummaryType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
							<option ' . (($_POST['SummaryType'] ?? 'orderno') == 'orderno' ? 'selected' : '') . ' value="orderno">' . __('Order Number') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'itemcode' ? 'selected' : '') . ' value="itemcode">' . __('Part Number') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'extprice' ? 'selected' : '') . ' value="extprice">' . __('Extended Price') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'supplierno' ? 'selected' : '') . ' value="supplierno">' . __('Supplier Number') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'suppname' ? 'selected' : '') . ' value="suppname">' . __('Supplier Name') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'month' ? 'selected' : '') . ' value="month">' . __('Month') . '</option>
							<option ' . (($_POST['SummaryType'] ?? '') == 'categoryid' ? 'selected' : '') . ' value="categoryid">' . __('Stock Category') . '</option>
						</select>
					</div>

					<div style="display: flex; flex-direction: column; gap: 12px; margin-top: 10px;">
						<button type="submit" name="submit" class="architect-btn">
							<i class="fas fa-sync-alt"></i> ' . __('Generate Report') . '
						</button>
						<button type="submit" name="submitcsv" class="db-btn db-btn-outline" style="border-radius: 12px; height: 50px; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; gap: 10px; border: 2px solid #059669; color: #059669; background: transparent;">
							<i class="fas fa-file-csv"></i> ' . __('Export to CSV') . '
						</button>
					</div>
				</form>
			</div>
		</div>';
}


include(__DIR__ . '/includes/footer.php');
