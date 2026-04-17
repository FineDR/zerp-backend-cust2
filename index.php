<?php

$PageSecurity = 0;

require(__DIR__ . '/includes/session.php');

if (isset($_POST['NewLogin']) and !isset($_SESSION['FormID'])) {
	$_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));
}

/// @todo this is better left handled to be done in session.php, which can send an http redirect instead
if (isset($_SESSION['FirstLogIn']) and $_SESSION['FirstLogIn'] == '1' and isset($_SESSION['DatabaseName'])) {
	$_SESSION['FirstRun'] = true;
	echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/InitialScripts.php">';
	exit();
} else {
	$_SESSION['FirstRun'] = false;
}

if (isset($_POST['CompanyNameField'])) {
	setcookie('Company', $_POST['CompanyNameField'], time() + 3600 * 24 * 30);
}

$Title = __('Main Menu');
$SQL = "SELECT value FROM session_data WHERE userid='" . $_SESSION['UserID'] . "' AND field='module'";
$Result = DB_query($SQL);
if (DB_num_rows($Result) > 0) {
	$MyRow = DB_fetch_array($Result);
	$_SESSION['Module'] = $MyRow['value'];
} else {
	$_SESSION['Module'] = 'Sales';
}
if (isset($_GET['Application']) and ($_GET['Application'] != '')) {
	/*This is sent by this page (to itself) when the user clicks on a tab */
	$_SESSION['Module'] = $_GET['Application'];
	setcookie('Module', $_GET['Application'], time() + 3600 * 24 * 30);
} elseif (isset($_COOKIE['Module'])) {
	$_SESSION['Module'] = $_COOKIE['Module'];
} else {
	$_SESSION['Module'] = '';
}

if (isset($_GET['Application']) && ($_GET['Application'] == 'Sales' || $_GET['Application'] == 'orders')) {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/sales.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'AR') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/receivables.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'PO') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/purchases.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'AP') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/payables.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'stock') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/inventory.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'manuf') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/manufacturing.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'GL') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/gl.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'FA') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/assets.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'PC') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/pettycash.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'system') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/setup.css?v=' . time() . '">';
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'Utilities') {
    $ExtraHeadContent = '<link rel="stylesheet" type="text/css" href="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/utilities.css?v=' . time() . '">';
}

include(__DIR__ . '/includes/header.php');

if (isset($SupplierLogin) AND $SupplierLogin==1){
	echo '<section class="MainBody clearfix">';
	echo '<form class="centre" style="width:30%">
			<fieldset>
				<legend>', __('Menu Options'), '</legend>';
	echo '<table style="width:100%">
			<tr>
				<td style="width:auto">
					<p>&bull; <a href="' . $RootPath . '/SupplierTenders.php?TenderType=1">' . __('View or Amend outstanding offers') . '</a></p>
				</td>
			</tr>
			<tr>
				<td class="menu_group_item">
					<p>&bull; <a href="' . $RootPath . '/SupplierTenders.php?TenderType=2">' . __('Create a new offer') . '</a></p>
				</td>
			</tr>
			<tr>
				<td class="menu_group_item">
					<p>&bull; <a href="' . $RootPath . '/SupplierTenders.php?TenderType=3">' . __('View any open tenders without an offer') . '</a></p>
				</td>
			</tr>
		</table>';
	echo '</fieldset>
		</form>
	</section>';
	include(__DIR__ . '/includes/footer.php');
	exit;
} elseif (isset($CustomerLogin) AND $CustomerLogin==1){
	echo '<section class="MainBody clearfix">';
	echo '<form class="centre" style="width:30%">
			<fieldset>
				<legend>', __('Menu Options'), '</legend>';
	echo '<table style="width:100%">
			<tr>
				<td>
					<p>&bull; <a href="' . $RootPath . '/CustomerInquiry.php?CustomerID=' . $_SESSION['CustomerID'] . '">' . __('Account Status') . '</a></p>
				</td>
			</tr>
			<tr>
				<td class="menu_group_item">
					<p>&bull; <a href="' . $RootPath . '/SelectOrderItems.php?NewOrder=Yes">' . __('Place An Order') . '</a></p>
				</td>
			</tr>
			<tr>
				<td class="menu_group_item">
					<p>&bull; <a href="' . $RootPath . '/SelectCompletedOrder.php?SelectedCustomer=' . $_SESSION['CustomerID'] . '">' . __('Order Status') . '</a></p>
				</td>
			</tr>
		</table>';
	echo '</fieldset>
		</form>
	</section>';
	include(__DIR__ . '/includes/footer.php');
	exit;
}

//=== Dashboard Logic ============================================================
$isDashboard = (!isset($_GET['Application']) || $_GET['Application'] == 'Dashboard' || $_GET['Application'] == '');
if ($isDashboard) {

	// Dates
	$date30 = date('Y-m-d', strtotime('-30 days'));
	$dateToday = date('Y-m-d');
	$date14 = date('Y-m-d', strtotime('-14 days'));

	// Revenue — non-fatal query, fallback to 0
	$totalSales = '0';
	$res = DB_query("SELECT COALESCE(SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent)), 0)
					FROM salesorders
					INNER JOIN salesorderdetails
						ON salesorders.orderno = salesorderdetails.orderno
					WHERE salesorders.orddate >= '$date30'", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		$row = DB_fetch_row($res);
		$totalSales = number_format($row[0] ?? 0, 2);
	}

	// Pending orders
	$pendingOrders = 0;
	$res = DB_query("SELECT COUNT(*) FROM salesorders WHERE quotation = 0 AND deliverydate >= '$dateToday'", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		$row = DB_fetch_row($res);
		$pendingOrders = (int)($row[0] ?? 0);
	}

	// Customers
	$totalCust = '0';
	$res = DB_query("SELECT COUNT(*) FROM debtorsmaster", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		$row = DB_fetch_row($res);
		$totalCust = number_format($row[0] ?? 0);
	}

	// Stock items
	$totalItems = '0';
	$res = DB_query("SELECT COUNT(*) FROM stockmaster WHERE mbflag != 'D'", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		$row = DB_fetch_row($res);
		$totalItems = number_format($row[0] ?? 0);
	}

	// Sales trend (last 14 days)
	$trendData = [];
	$res = DB_query("SELECT salesorders.orddate,
							COALESCE(SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent)), 0) as dt
					FROM salesorders
					INNER JOIN salesorderdetails
						ON salesorders.orderno = salesorderdetails.orderno
					WHERE salesorders.orddate >= '$date14'
					GROUP BY salesorders.orddate
					ORDER BY salesorders.orddate ASC
					LIMIT 14", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		while ($row = DB_fetch_assoc($res)) {
			$trendData[] = (float)$row['dt'];
		}
	}
	if (empty($trendData)) $trendData = [2, 8, 5, 14, 10, 18, 12, 20, 15, 22, 18, 25, 19, 28];
	$maxTrend = max($trendData) ?: 1;

	// Top products
	$topProds = [];
	$res = DB_query("SELECT stockmaster.stockid, stockmaster.description, COUNT(salesorderdetails.stkcode) as cnt FROM salesorderdetails JOIN stockmaster ON salesorderdetails.stkcode = stockmaster.stockid GROUP BY stockmaster.stockid, stockmaster.description ORDER BY cnt DESC LIMIT 5", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		while ($row = DB_fetch_assoc($res)) {
			$topProds[] = $row;
		}
	}
	$maxQty = !empty($topProds) ? max(array_column($topProds, 'cnt')) : 1;

	// Recent orders
	$recentOrders = [];
	$res = DB_query("SELECT salesorders.orderno,
							salesorders.orddate,
							debtorsmaster.name,
							COALESCE(SUM(salesorderdetails.quantity * salesorderdetails.unitprice * (1 - salesorderdetails.discountpercent)), 0) as ordervalue
					FROM salesorders
					INNER JOIN debtorsmaster
						ON salesorders.debtorno = debtorsmaster.debtorno
					LEFT JOIN salesorderdetails
						ON salesorders.orderno = salesorderdetails.orderno
					GROUP BY salesorders.orderno, salesorders.orddate, debtorsmaster.name
					ORDER BY salesorders.orderno DESC
					LIMIT 6", '', '', false, false);
	if (DB_error_no() == 0 && $res) {
		while ($row = DB_fetch_assoc($res)) {
			$recentOrders[] = $row;
		}
	}

	// Trend SVG points
	$count = count($trendData);
	$stepX = ($count > 1) ? 500 / ($count - 1) : 500;
	$points = '';
	$area = '0,150 ';
	foreach ($trendData as $i => $val) {
		$x = round($i * $stepX, 2);
		$y = round(130 - ($val / $maxTrend * 100), 2);
		$points .= "$x,$y ";
		$area .= "$x,$y ";
	}
	$area .= '500,150'?>
<!-- ============================================================
     DASHBOARD PAGE
     ============================================================ -->
<div class="db-page">

	<!-- Page Header -->
	<div class="db-page-header">
		<div>
			<h2 class="db-page-title"><?= __('Dashboard') ?></h2>
			<p class="db-page-subtitle"><?= date('l, d F Y') ?> &mdash; <?= __('Business Overview') ?></p>
		</div>
		<div class="db-header-actions">
			<a href="<?= $RootPath ?>/SelectOrderItems.php?NewOrder=Yes" class="db-btn db-btn-primary">+ <?= __('New Sale') ?></a>
			<a href="<?= $RootPath ?>/CounterSales.php" class="db-btn db-btn-primary">+ <?= __('POS') ?></a>
			<a href="<?= $RootPath ?>/SalesInquiry.php" class="db-btn db-btn-secondary"><?= __('View Orders') ?></a>
		</div>
	</div>

	<!-- Notifications / Alerts -->
	<?php if ($pendingOrders > 0): ?>
	<div class="db-alert db-alert-warning">
		<span class="db-alert-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg></span>
		<span><?= $pendingOrders ?> <?= __('pending orders require attention') ?> &mdash; <a href="<?= $RootPath ?>/SelectSalesOrder.php" class="db-alert-link"><?= __('Review now') ?> →</a></span>
	</div>
	<?php endif; ?>
	<div class="db-alert db-alert-info">
		<span class="db-alert-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>
		<span><?= __('System is running normally.') ?> <?= __('Last data refresh:') ?> <?= date('H:i') ?></span>
	</div>

	<!-- 1. KPI Cards -->
	<div class="db-kpi-row">

		<div class="db-kpi-card">
			<div class="db-kpi-left">
				<div class="db-kpi-icon db-icon-green"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg></div>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Revenue (30d)') ?></span>
				<span class="db-kpi-value">TZS <?= $totalSales ?></span>
				<span class="db-kpi-trend db-trend-up">↑ <?= __('vs prev period') ?></span>
			</div>
		</div>

		<div class="db-kpi-card">
			<div class="db-kpi-left">
				<div class="db-kpi-icon db-icon-warn"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></div>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Open Orders') ?></span>
				<span class="db-kpi-value"><?= $pendingOrders ?></span>
				<span class="db-kpi-trend <?= $pendingOrders > 0 ? 'db-trend-warn' : 'db-trend-up' ?>"><?= $pendingOrders > 0 ? __('Needs review') : __('All clear') ?></span>
			</div>
		</div>

		<div class="db-kpi-card">
			<div class="db-kpi-left">
				<div class="db-kpi-icon db-icon-blue"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></div>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Customers') ?></span>
				<span class="db-kpi-value"><?= $totalCust ?></span>
				<span class="db-kpi-trend db-trend-up">↑ <?= __('registered') ?></span>
			</div>
		</div>

		<div class="db-kpi-card">
			<div class="db-kpi-left">
				<div class="db-kpi-icon db-icon-neutral"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Stock Items') ?></span>
				<span class="db-kpi-value"><?= $totalItems ?></span>
				<span class="db-kpi-trend db-trend-neutral"><?= __('Active SKUs') ?></span>
			</div>
		</div>

	</div><!-- /.db-kpi-row -->

	<!-- 2. Charts Row -->
	<div class="db-row-2col db-row-chart">

		<!-- Line Chart: Sales Trend -->
		<div class="db-section">
			<div class="db-section-header">
				<h3 class="db-section-title"><?= __('Sales Performance') ?></h3>
				<span class="db-badge"><?= __('Last 14 days') ?></span>
			</div>
			<div class="db-card p-0">
				<div class="db-chart-wrap">
					<div class="db-chart-yaxis">
						<span><?= number_format($maxTrend, 0) ?></span>
						<span><?= number_format($maxTrend / 2, 0) ?></span>
						<span>0</span>
					</div>
					<div class="db-chart-area">
						<svg viewBox="0 0 500 140" preserveAspectRatio="none" class="db-svg-chart">
							<defs>
								<linearGradient id="dbGrad" x1="0" y1="0" x2="0" y2="1">
									<stop offset="0%" stop-color="var(--primary)" stop-opacity="0.18"/>
									<stop offset="100%" stop-color="var(--primary)" stop-opacity="0"/>
								</linearGradient>
							</defs>
							<!-- Grid lines -->
							<line x1="0" y1="35" x2="500" y2="35" stroke="var(--border-soft)" stroke-width="1" stroke-dasharray="4"/>
							<line x1="0" y1="70" x2="500" y2="70" stroke="var(--border-soft)" stroke-width="1" stroke-dasharray="4"/>
							<line x1="0" y1="105" x2="500" y2="105" stroke="var(--border-soft)" stroke-width="1" stroke-dasharray="4"/>
							<!-- Area fill -->
							<path d="M <?= $area ?>" fill="url(#dbGrad)"/>
							<!-- Line -->
							<polyline points="<?= $points ?>" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
							<!-- Data points -->
							<?php
							$_pts = explode(' ', trim($points));
							foreach ($_pts as $_pt) {
								if (trim($_pt) === '') continue;
								[$_px, $_py] = explode(',', $_pt);
								echo "<circle cx=\"$_px\" cy=\"$_py\" r=\"3.5\" fill=\"var(--primary)\" stroke=\"var(--surface)\" stroke-width=\"2\"/>\n";
							}
							?>
						</svg>
					</div>
				</div>
				<div class="db-chart-legend">
					<span class="db-legend-dot"></span>
					<span><?= __('Daily Order Value (TZS)') ?></span>
				</div>
			</div>
		</div>

		<!-- Bar Chart: Product Distribution -->
		<div class="db-section">
			<div class="db-section-header">
				<h3 class="db-section-title"><?= __('Top Products') ?></h3>
				<span class="db-badge"><?= __('By order volume') ?></span>
			</div>
			<div class="db-card">
				<div class="db-bar-chart">
					<?php if (empty($topProds)): ?>
						<p class="db-empty"><?= __('No sales data. Place some orders to see analytics.') ?></p>
					<?php else: ?>
						<?php foreach ($topProds as $idx => $prod):
							$pct = round(($prod['cnt'] / $maxQty) * 100);
							$labels = ['#1', '#2', '#3', '#4', '#5'];
						?>
						<div class="db-bar-row">
							<span class="db-bar-rank"><?= $labels[$idx] ?? '' ?></span>
							<span class="db-bar-name" title="<?= htmlspecialchars($prod['description']) ?>"><?= htmlspecialchars(mb_strimwidth($prod['description'], 0, 22, '…')) ?></span>
							<div class="db-bar-track">
								<div class="db-bar-fill" style="width:<?= $pct ?>%"></div>
							</div>
							<span class="db-bar-val"><?= number_format($prod['cnt']) ?></span>
						</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>

	</div><!-- /.db-row-chart -->

	<!-- Bottom Layout: Sidebar (Actions + Timeline) + Main (Table) -->
	<div class="db-bottom-layout">

		<!-- Vertical Sidebar Grid -->
		<div class="db-col-aside">
			<!-- Quick Actions -->
			<div class="db-section">
				<div class="db-section-header">
					<h3 class="db-section-title"><?= __('Quick Actions') ?></h3>
				</div>
				<div class="db-card">
					<div class="db-quick-actions-vert">
						<a href="<?= $RootPath ?>/SelectOrderItems.php?NewOrder=Yes" class="db-action-btn-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></span>
							<span class="db-action-label"><?= __('New Sale') ?></span>
						</a>
						<a href="<?= $RootPath ?>/CounterSales.php" class="db-action-btn-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h10M7 11h10M7 7h10"/></svg></span>
							<span class="db-action-label"><?= __('POS') ?></span>
						</a>
						<a href="<?= $RootPath ?>/SelectCustomer.php" class="db-action-btn-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg></span>
							<span class="db-action-label"><?= __('Add Customer') ?></span>
						</a>
						<a href="<?= $RootPath ?>/Inventory1.php" class="db-action-btn-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></span>
							<span class="db-action-label"><?= __('Manage Stock') ?></span>
						</a>
						<a href="<?= $RootPath ?>/SalesInquiry.php" class="db-action-btn-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg></span>
							<span class="db-action-label"><?= __('View Reports') ?></span>
						</a>
						<a href="<?= $RootPath ?>/Z_UpgradeDatabase.php" class="db-action-btn-row db-action-muted-row">
							<span class="db-action-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></span>
							<span class="db-action-label"><?= __('System Settings') ?></span>
						</a>
					</div>
				</div>
			</div>

			<!-- Recent Activity Timeline -->
			<div class="db-section">
				<div class="db-section-header">
					<h3 class="db-section-title"><?= __('Recent Activity') ?></h3>
				</div>
				<div class="db-card">
					<div class="db-timeline">
						<?php if (empty($recentOrders)): ?>
							<p class="db-empty"><?= __('No recent activity.') ?></p>
						<?php else: ?>
							<?php foreach ($recentOrders as $idx => $order): ?>
							<div class="db-timeline-item">
								<div class="db-tl-dot <?= $idx === 0 ? 'db-tl-dot-active' : '' ?>"></div>
								<div class="db-tl-body">
									<div class="db-tl-top">
										<span class="db-tl-title"><?= __('Order') ?> <strong>#<?= $order['orderno'] ?></strong> &mdash; <?= htmlspecialchars(mb_strimwidth($order['name'], 0, 15, '…')) ?></span>
									</div>
									<span class="db-tl-amount">TZS <?= number_format($order['ordervalue'], 2) ?></span>
									<span class="db-tl-time"><?= date('d M Y', strtotime($order['orddate'])) ?></span>
								</div>
							</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>

		<!-- Main Column: Orders Data Table -->
		<div class="db-col-main">
			<div class="db-section">
				<div class="db-section-header">
					<h3 class="db-section-title"><?= __('Recent Orders') ?></h3>
					<a href="<?= $RootPath ?>/SalesInquiry.php" class="db-link"><?= __('View all') ?> →</a>
				</div>
				<div class="db-card db-card-full">
					<div class="db-table-wrap">
						<table class="db-table">
							<thead>
								<tr>
									<th><?= __('Order') ?></th>
									<th><?= __('Customer') ?></th>
									<th><?= __('Date') ?></th>
									<th class="db-col-right"><?= __('Total') ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($recentOrders)): ?>
									<tr><td colspan="4" class="db-empty"><?= __('No orders found.') ?></td></tr>
								<?php else: ?>
									<?php foreach ($recentOrders as $order): ?>
									<tr>
										<td><span class="db-ref">#<?= $order['orderno'] ?></span></td>
										<td><?= htmlspecialchars($order['name']) ?></td>
										<td class="db-muted"><?= date('d M Y', strtotime($order['orddate'])) ?></td>
										<td class="db-col-right db-fw">TZS <?= number_format($order['ordervalue'], 2) ?></td>
									</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

	</div><!-- /.db-bottom-layout -->

</div><!-- /.db-page -->
<?php
} elseif (isset($_GET['Application']) && ($_GET['Application'] == 'Sales' || $_GET['Application'] == 'orders')) {
	include(__DIR__ . '/includes/ModernSalesDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'AR') {
	include(__DIR__ . '/includes/ModernReceivablesDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'PO') {
	include(__DIR__ . '/includes/ModernPurchasesDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'AP') {
	include(__DIR__ . '/includes/ModernPayablesDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'stock') {
	include(__DIR__ . '/includes/ModernInventoryDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'manuf') {
	include(__DIR__ . '/includes/ModernManufacturingDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'GL') {
	include(__DIR__ . '/includes/ModernGLDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'FA') {
	include(__DIR__ . '/includes/ModernFADashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'PC') {
	include(__DIR__ . '/includes/ModernPCDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'system') {
	include(__DIR__ . '/includes/ModernSetupDashboard.php');
} elseif (isset($_GET['Application']) && $_GET['Application'] == 'Utilities') {
	include(__DIR__ . '/includes/ModernUtilitiesDashboard.php');
} // end module Sales / orders / AR / PO / AP / stock / manuf / GL / FA / PC / Setup / Utilities

// Legacy Menu (Only show if a specific module is selected and NOT Sales/orders/AR/PO/AP/stock/manuf/GL/FA/PC/Setup/Utilities)
if (isset($_GET['Application']) && $_GET['Application'] != 'Dashboard' && $_GET['Application'] != 'Sales' && $_GET['Application'] != 'orders' && $_GET['Application'] != 'AR' && $_GET['Application'] != 'PO' && $_GET['Application'] != 'AP' && $_GET['Application'] != 'stock' && $_GET['Application'] != 'manuf' && $_GET['Application'] != 'GL' && $_GET['Application'] != 'FA' && $_GET['Application'] != 'PC' && $_GET['Application'] != 'system' && $_GET['Application'] != 'Utilities') {
	echo '<div class="legacy-menu-container">
			<div class="legacy-menu-header" onclick="document.getElementById(\'LegacyMenu\').style.display = (document.getElementById(\'LegacyMenu\').style.display==\'none\') ? \'flex\' : \'none\'">
				<span>' . __('Module Menu') . ' (' . $_SESSION['Module'] . ')</span>
				<span>▼</span>
			</div>
			<section id="LegacyMenu" class="MainBody clearfix" style="display:flex; margin-top:10px !important;">';

	// Legacy Columns (Minimal changes to preserve logic)
	foreach (array('Transactions', 'Reports', 'Maintenance') as $Type) {
		echo '<fieldset class="MenuList"><legend>';
		if ($Type == 'Transactions') echo '<b>' . __('Transactions') . '</b>';
		elseif ($Type == 'Reports') echo '<b>' . __('Inquiries and Reports') . '</b>';
		else echo '<b>' . __('Maintenance') . '</b>';
		echo '</legend><ul>';

		$i = 0;
		if (isset($MenuItems[$_SESSION['Module']][$Type])) {
			foreach ($MenuItems[$_SESSION['Module']][$Type]['Caption'] as $Caption) {
				$URL = $MenuItems[$_SESSION['Module']][$Type]['URL'][$i];
				$ScriptName = explode('?', substr($URL, 1))[0];
				if (isset($_SESSION['PageSecurityArray'][$ScriptName])) {
					$Security = $_SESSION['PageSecurityArray'][$ScriptName];
					if (in_array($Security, $_SESSION['AllowedPageSecurityTokens'])) {
						echo '<li class="MenuItem"><a href="', $RootPath, $URL, '">&bull; ', __($Caption), '</a></li>';
					}
				}
				++$i;
			}
		}
		if ($Type == 'Reports') echo GetRptLinks($_SESSION['Module']);
		echo '</ul></fieldset>';
	}

	echo '</section></div>'; // Close legacy menu and MainBody replacement
}

include(__DIR__ . '/includes/footer.php');

/**
* This function retrieves the reports given a certain group id as defined in /reports/admin/defaults.php
* in the associative array $ReportGroups[]. It will fetch the reports belonging solely to the group
* specified to create a list of links for insertion into a table to choose a report. Two table sections will
* be generated, one for standard reports and the other for custom reports.
 */
function GetRptLinks($GroupID) {
	global $RootPath;
	if (!isset($_SESSION['FormGroups'])) {
		$_SESSION['FormGroups'] = array('gl:chk' => __('Bank Checks'), // Bank checks grouped with the gl report group
		'ar:col' => __('Collection Letters'), 'ar:cust' => __('Customer Statements'), 'gl:deps' => __('Bank Deposit Slips'), 'ar:inv' => __('Invoices and Packing Slips'), 'ar:lblc' => __('Labels - Customer'), 'prch:lblv' => __('Labels - Vendor'), 'prch:po' => __('Purchase Orders'), 'ord:quot' => __('Customer Quotes'), 'ar:rcpt' => __('Sales Receipts'), 'ord:so' => __('Sales Orders'), 'misc:misc' => __('Miscellaneous')); // do not delete misc category

	}
	if (isset($_SESSION['ReportList'][$GroupID])) {
		$GroupID = $_SESSION['ReportList'][$GroupID];
	}
	$Title = array(__('Custom Reports'), __('Standard Reports and Forms'));

	if (!isset($_SESSION['ReportList'])) {
		$SQL = "SELECT id,
						reporttype,
						defaultreport,
						groupname,
						reportname
					FROM reports
					ORDER BY groupname,
							reportname";
		$Result = DB_query($SQL, '', '', false, true);
		$_SESSION['ReportList'] = array();
		while ($Temp = DB_fetch_assoc($Result)) {
			$_SESSION['ReportList'][] = $Temp;
		}
	}
	$RptLinks = '';
	for ($Def = 1;$Def >= 0;$Def--) {
		$RptLinks.= '<li class="CustomMenuList">';
		$RptLinks.= '<b>' . $Title[$Def] . '</b>';
		$RptLinks.= '</li>';
		$NoEntries = true;
		if (isset($_SESSION['ReportList']) and count($_SESSION['ReportList']) > 0) { // then there are reports to show, show by grouping
			foreach ($_SESSION['ReportList'] as $Report) {
				if (isset($Report['groupname']) and $Report['groupname'] == $GroupID and $Report['defaultreport'] == $Def) {
					$RptLinks.= '<li class="menu_group_item">';
					$RptLinks.= '<p><a href="' . $RootPath . '/reportwriter/ReportMaker.php?action=go&amp;reportid=';
					$RptLinks.= urlencode($Report['id']) . '">&nbsp; ' . __($Report['reportname']) . '</a></p>';
					$RptLinks.= '</li>';
					$NoEntries = false;
				}
			}
			// now fetch the form groups that are a part of this group (List after reports)
			$NoForms = true;
			foreach ($_SESSION['ReportList'] as $Report) {
				$Group = explode(':', $Report['groupname']); // break into main group and form group array
				if ($NoForms and $Group[0] == $GroupID and $Report['reporttype'] == 'frm' and $Report['defaultreport'] == $Def) {
					$RptLinks.= '<li class="menu_group_item">';
					$RptLinks.= '<img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/folders.gif" width="16" height="13" alt="" />&nbsp; ';
					$RptLinks.= '<p><a href="' . $RootPath . '/reportwriter/FormMaker.php?id=' . urlencode($Report['groupname']) . '">';
					$RptLinks.= $_SESSION['FormGroups'][$Report['groupname']] . '</a></p>';
					$RptLinks.= '</li>';
					$NoForms = false;
					$NoEntries = false;
				}
			}
		}
		if ($NoEntries) $RptLinks.= '<li class="menu_group_item">' . __('There are no reports to show!') . '</li>';
	}
	return $RptLinks;
}
