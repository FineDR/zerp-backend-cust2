<?php

$PageSecurity = 0;

require(__DIR__ . '/includes/session.php');

$Title = __('Dashboard');
$ViewTopic = 'Dashboard';
$BookMark = 'MainScreen';
include(__DIR__ . '/includes/header.php');

$DashBoardURL = $_SERVER['REQUEST_URI'];

// 1. Fetch User Dashboard Config
$UserSQL = "SELECT scripts FROM dashboard_users WHERE userid = '" . $_SESSION['UserID'] . "' ";
$Result = DB_query($UserSQL);
if (DB_num_rows($Result) == 0) {
	$InsertSQL = "INSERT INTO dashboard_users (userid, scripts) VALUES('" . $_SESSION['UserID'] . "', '')";
	DB_query($InsertSQL);
	$ScriptArray = array();
} else {
	$MyRow = DB_fetch_array($Result);
	$ScriptArray = array_filter(explode(',', $MyRow['scripts']));
}

// 2. Handle Add/Remove Actions
if (isset($_GET['Remove'])) {
	$ScriptArray = array_diff($ScriptArray, array($_GET['Remove']));
	$UpdateSQL = "UPDATE dashboard_users SET scripts='" . implode(',', $ScriptArray) . "' WHERE userid = '" . $_SESSION['UserID'] . "'";
	DB_query($UpdateSQL);
}

if (isset($_GET['Reports']) && count($ScriptArray) < 7) {
	if (!in_array($_GET['Reports'], $ScriptArray)) {
		$ScriptArray[] = $_GET['Reports'];
		asort($ScriptArray);
		$UpdateSQL = "UPDATE dashboard_users SET scripts='" . implode(',', $ScriptArray) . "' WHERE userid = '" . $_SESSION['UserID'] . "' ";
		DB_query($UpdateSQL);
	}
} elseif (isset($_GET['Reports']) && count($ScriptArray) >= 7) {
	prnMsg(__('A maximum of 6 reports is allowed on each users dashboard'), 'warn');
}

// 3. Fetch KPI Metrics
$sqlCust = "SELECT COUNT(*) as total FROM debtorsmaster";
$resCust = DB_query($sqlCust);
$totalCustomers = DB_fetch_array($resCust)['total'];

$sqlStock = "SELECT COUNT(*) as total FROM stockmaster";
$resStock = DB_query($sqlStock);
$totalItems = DB_fetch_array($resStock)['total'];

$sqlBank = "SELECT SUM(amount) as total FROM banktrans";
$resBank = DB_query($sqlBank);
$bankBalance = DB_fetch_array($resBank)['total'] ?? 0;

$sqlTrans = "SELECT COUNT(*) as total FROM debtortrans WHERE trandate >= '" . date('Y-m-d', strtotime('-30 days')) . "'";
$resTrans = DB_query($sqlTrans);
$recentTrans = DB_fetch_array($resTrans)['total'];

?>

<div class="db-page">
	<!-- Dashboard Header / Welcome -->
	<div class="welcome-banner" style="margin-bottom: var(--space-6);">
		<div class="banner-gradient"></div>
		<div class="banner-content">
			<h1><?= __('Welcome back') ?>, <?= $_SESSION['UsersRealName'] ?></h1>
			<p><?= date('l, d F Y') ?> &mdash; <?= __('Here is what is happening across your business today.') ?></p>
		</div>
		<div class="db-header-actions">
			<?php if (count($ScriptArray) < 6): ?>
			<form method="get" class="noPrint" style="display: flex; gap: 10px; align-items: center;">
				<select name="Reports" onchange="this.form.submit()" style="min-width: 220px;">
					<option value=""><?= __('+ Add Widget to Dashboard') ?></option>
					<?php
					$SQL = "SELECT id, description, scripts FROM dashboard_scripts";
					$ScriptsResult = DB_query($SQL);
					while ($ScriptRow = DB_fetch_array($ScriptsResult)) {
						if (!in_array($ScriptRow['id'], $ScriptArray)) {
							echo '<option value="', $ScriptRow['id'], '">', __($ScriptRow['description']), '</option>';
						}
					}
					?>
				</select>
			</form>
			<?php endif; ?>
		</div>
	</div>

	<!-- KPI Grid -->
	<div class="kpi-responsive-grid" style="margin-bottom: var(--space-6);">
		<a href="<?= $RootPath ?>/SelectCustomer.php" class="db-kpi-card" style="text-decoration: none;">
			<div class="db-kpi-icon db-icon-blue">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Total Customers') ?></span>
				<span class="db-kpi-value"><?= number_format($totalCustomers) ?></span>
				<span class="db-kpi-trend db-trend-neutral"><?= __('Active Partners') ?></span>
			</div>
		</a>
		<a href="<?= $RootPath ?>/Stocks.php" class="db-kpi-card" style="text-decoration: none;">
			<div class="db-kpi-icon db-icon-green">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Stock Items') ?></span>
				<span class="db-kpi-value"><?= number_format($totalItems) ?></span>
				<span class="db-kpi-trend db-trend-up"><?= __('In Inventory') ?></span>
			</div>
		</a>
		<a href="<?= $RootPath ?>/GLAccounts.php" class="db-kpi-card" style="text-decoration: none;">
			<div class="db-kpi-icon db-icon-neutral">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('Cash Position') ?></span>
				<span class="db-kpi-value"><?= number_format($bankBalance, 2) ?></span>
				<span class="db-kpi-trend db-trend-neutral"><?= __('Total Liquidity') ?></span>
			</div>
		</a>
		<a href="<?= $RootPath ?>/CustomerInquiry.php" class="db-kpi-card" style="text-decoration: none;">
			<div class="db-kpi-icon db-icon-blue">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
			</div>
			<div class="db-kpi-body">
				<span class="db-kpi-label"><?= __('30-Day Activity') ?></span>
				<span class="db-kpi-value"><?= number_format($recentTrans) ?></span>
				<span class="db-kpi-trend db-trend-info"><?= __('Recent Trans.') ?></span>
			</div>
		</a>
	</div>

	<!-- Dashboard Widgets Grid -->
	<div class="widget-responsive-grid">
		<?php
		$SQL = "SELECT id, scripts, description FROM dashboard_scripts";
		$Result = DB_query($SQL);
		$i = 0;
		while ($MyRow = DB_fetch_array($Result)) {
			if (in_array($MyRow['id'], $ScriptArray)) {
				echo '<div class="db-card" style="position: relative;">';
				echo '	<div class="db-card-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-4);">
							<h3 class="db-card-title">' . __($MyRow['description']) . '</h3>
							<a href="' . $DashBoardURL . '?Remove=' . $MyRow['id'] . '" class="db-badge db-badge-danger" style="text-decoration: none;">' . __('Remove') . '</a>
						</div>';
				echo '	<div class="widget-content" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">';
				include('dashboard/' . $MyRow['scripts']);
				echo '	</div>';
				echo '</div>';
				$i++;
			}
		}

		if ($i == 0) {
			echo '<div class="db-card" style="grid-column: 1 / -1; padding: 60px; text-align: center;">
					<div style="color: var(--text-muted); margin-bottom: 20px;">
						<svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><line x1="15" y1="9" x2="9" y2="15"></line></svg>
					</div>
					<h3>' . __('Your Dashboard is Empty') . '</h3>
					<p>' . __('Select reports from the "Add Widget" dropdown to customize your overview.') . '</p>
				  </div>';
		}
		?>
	</div>
</div>

<?php
include(__DIR__ . '/includes/footer.php');
?>
<style>
/* Dashboard-specific local overrides */
.db-kpi-card {
	transition: all var(--transition-base);
	cursor: pointer;
}
.db-kpi-card:hover {
	border-color: var(--primary);
	transform: translateY(-3px);
	box-shadow: var(--shadow-md);
}

/* Responsive Grids */
.kpi-responsive-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
	gap: var(--space-4);
}
* {
	box-sizing: border-box;
}
.db-page {
	width: 100%;
	max-width: 100%;
	overflow-x: hidden;
	padding: var(--space-4);
}
.widget-responsive-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: var(--space-6);
	width: 100%;
	max-width: 100%;
}

@media (max-width: 768px) {
	.widget-responsive-grid {
		grid-template-columns: 1fr;
		width: 100%;
	}
	.welcome-banner {
		padding: var(--space-5) !important;
		flex-direction: column;
		align-items: flex-start;
		gap: var(--space-4);
		width: 100%;
	}
	.banner-content h1 { font-size: 1.3rem !important; }
}

.db-card {
	width: 100%;
	max-width: 100%;
	overflow: hidden;
}
.widget-content {
	width: 100%;
	max-width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	border-radius: var(--radius-sm);
}
.widget-content table {
	width: 100% !important;
	border: none !important;
	margin: 0 !important;
	min-width: 600px; /* Force scrollable content */
	border-collapse: collapse;
}
.widget-content th {
	background: var(--surface-alt) !important;
	border-bottom: 2px solid var(--border) !important;
	white-space: nowrap;
}
.widget-content td {
	white-space: nowrap;
}
.DashboardTable {
	width: 100% !important;
}
.DashboardTable th:first-child {
	display: none;
}
</style>
<script async src="<?= $RootPath ?>/dashboard/javascript/dashboard.js"></script>
<script async src="<?= $RootPath ?>/dashboard/javascript/dashboard.js"></script>
