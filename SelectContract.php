<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Select Contract');
$ViewTopic = 'Contracts';
$BookMark = 'SelectContract';
include(__DIR__ . '/includes/header.php');

// KPI Metrics Logic (Global Context)
$sqlOrders = "SELECT COUNT(*) FROM contracts WHERE status=2";
$resOrders = DB_query($sqlOrders);
$rowOrders = DB_fetch_row($resOrders);
$OrderedCount = $rowOrders[0];

$sqlQuotes = "SELECT COUNT(*) FROM contracts WHERE status=1";
$resQuotes = DB_query($sqlQuotes);
$rowQuotes = DB_fetch_row($resQuotes);
$QuoteCount = $rowQuotes[0];

$sqlDrafts = "SELECT COUNT(*) FROM contracts WHERE status=0";
$resDrafts = DB_query($sqlDrafts);
$rowDrafts = DB_fetch_row($resDrafts);
$DraftCount = $rowDrafts[0];

// Handle Query Parameters
if (isset($_GET['ContractRef'])) {
	$_POST['ContractRef'] = $_GET['ContractRef'];
}
if (!isset($_POST['Status'])) {
	$_POST['Status'] = isset($_GET['Status']) ? $_GET['Status'] : 4;
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title">
				<i class="fas fa-file-signature"></i> ' . $Title . '
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/Contracts.php?NewContract=Yes" class="db-btn db-btn-primary">
					<i class="fas fa-plus"></i> ' . __('New Contract') . '
				</a>
			</div>
		</div>

		<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			
			<div class="db-bottom-layout">
				<!-- Sidebar Filters -->
				<aside class="db-col-aside">
					<div class="db-card">
						<div class="db-card-header">
							<div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Search Options') . '</div>
						</div>
						<div class="db-card-body">
							<div class="db-form-group">
								<label class="db-label">' . __('Contract Reference') . '</label>
								<input type="text" name="ContractRef" class="db-input" placeholder="' . __('Search reference...') . '" value="' . (isset($_POST['ContractRef']) ? $_POST['ContractRef'] : '') . '" />
							</div>
							
							<div class="db-form-group">
								<label class="db-label">' . __('Contract Status') . '</label>
								<select name="Status" class="db-select">';
								$Statuses = [
									0 => __('Not Yet Quoted'),
									1 => __('Quoted - No Order'),
									2 => __('Order Placed'),
									3 => __('Completed'),
									4 => __('All Contracts')
								];
								foreach ($Statuses as $val => $label) {
									$sel = (isset($_POST['Status']) && $_POST['Status'] == $val) ? 'selected' : '';
									echo '<option ' . $sel . ' value="' . $val . '">' . $label . '</option>';
								}
	echo '						</select>
							</div>

							<div style="margin-top: 20px;">
								<button type="submit" name="SearchContracts" class="db-btn db-btn-primary" style="width: 100%; justify-content: center;">
									<i class="fas fa-search"></i> ' . __('Apply Filters') . '
								</button>
							</div>
						</div>
					</div>

					<div class="db-card" style="margin-top: 20px; background: var(--surface-alt);">
						<div class="db-card-body" style="padding: 15px;">
							<p class="db-font-sm text-muted">
								<i class="fas fa-info-circle"></i> ' . __('Select a contract to modify its components, requirements, or generate quotes.') . '
							</p>
						</div>
					</div>
				</aside>

				<!-- Main Content Column -->
				<main class="db-col-main">
					<!-- KPI Metric Blocks -->
					<div class="kpi-grid" style="margin-bottom: var(--space-6);">
						<div class="kpi-card-v2">
							<div class="kpi-icon" style="background: var(--success-soft); color: var(--success);">
								<i class="fas fa-check-double"></i>
							</div>
							<div class="kpi-data">
								<span class="label">' . __('Confirmed') . '</span>
								<span class="value">' . $OrderedCount . '</span>
							</div>
						</div>
						<div class="kpi-card-v2">
							<div class="kpi-icon" style="background: var(--info-soft); color: var(--info);">
								<i class="fas fa-file-invoice-dollar"></i>
							</div>
							<div class="kpi-data">
								<span class="label">' . __('In Quote') . '</span>
								<span class="value">' . $QuoteCount . '</span>
							</div>
						</div>
						<div class="kpi-card-v2">
							<div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);">
								<i class="fas fa-edit"></i>
							</div>
							<div class="kpi-data">
								<span class="label">' . __('Drafts') . '</span>
								<span class="value">' . $DraftCount . '</span>
							</div>
						</div>
					</div>';

					// Construct SQL
					if (isset($_POST['ContractRef']) AND $_POST['ContractRef'] != '') {
						$SearchRef = trim($_POST['ContractRef']);
						$SQL = "SELECT contractref, contractdescription, contracts.debtorno, debtorsmaster.name AS customername, branchcode, status, orderno, wo, requireddate
								FROM contracts INNER JOIN debtorsmaster ON contracts.debtorno = debtorsmaster.debtorno
								INNER JOIN locationusers ON locationusers.loccode=contracts.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1
								WHERE contractref " . LIKE . " '%" . $SearchRef . "%'";
					} else {
						$SQL = "SELECT contractref, contractdescription, contracts.debtorno, debtorsmaster.name AS customername, branchcode, status, orderno, wo, requireddate
								FROM contracts INNER JOIN debtorsmaster ON contracts.debtorno = debtorsmaster.debtorno
								INNER JOIN locationusers ON locationusers.loccode=contracts.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
						if (isset($_POST['Status']) && $_POST['Status'] != 4) {
							$SQL .= " AND status='" . $_POST['Status'] . "'";
						}
					}
					$SQL .= " ORDER BY contractref DESC";

					$ContractsResult = DB_query($SQL);

					echo '<div class="db-card">
							<div class="db-card-header" style="justify-content: space-between;">
								<div class="db-card-title"><i class="fas fa-list-alt"></i> ' . __('Contract Portfolio') . '</div>
								<span class="db-badge db-badge-info">' . DB_num_rows($ContractsResult) . ' ' . __('Results') . '</span>
							</div>
							<div class="db-card-body p-0">
								<div class="db-table-wrapper">
									<table class="db-table">
										<thead>
											<tr>
												<th>' . __('Contract Reference') . '</th>
												<th>' . __('Customer') . '</th>
												<th>' . __('Status') . '</th>
												<th>' . __('Required') . '</th>
												<th class="text-right">' . __('Manage') . '</th>
											</tr>
										</thead>
										<tbody>';

					$stCfg = [0 => 'secondary', 1 => 'info', 2 => 'success', 3 => 'primary'];
					$stLabels = [0 => __('Draft'), 1 => __('Quoted'), 2 => __('Ordered'), 3 => __('Done')];

					while ($MyRow = DB_fetch_array($ContractsResult)) {
						$color = $stCfg[$MyRow['status']] ?? 'secondary';
						$label = $stLabels[$MyRow['status']] ?? __('Unknown');

						echo '<tr>
								<td>
									<div class="db-font-bold text-primary">' . $MyRow['contractref'] . '</div>
									<small class="text-muted">' . $MyRow['contractdescription'] . '</small>
								</td>
								<td>
									<div class="db-font-bold">' . $MyRow['customername'] . '</div>
									<small class="text-muted">' . $MyRow['branchcode'] . '</small>
								</td>
								<td><span class="db-badge db-badge-' . $color . '">' . $label . '</span></td>
								<td>' . ConvertSQLDate($MyRow['requireddate']) . '</td>
								<td class="text-right db-action-btn-row">
									' . ($MyRow['status'] <= 1 ? '<a href="' . $RootPath . '/Contracts.php?ModifyContractRef=' . $MyRow['contractref'] . '" class="db-btn db-btn-outline-primary db-btn-sm" title="' . __('Modify Header') . '"><i class="fas fa-edit"></i></a>' : '') . '
									' . ($MyRow['status'] >= 1 ? '<a href="' . $RootPath . '/SelectOrderItems.php?ModifyOrderNumber=' . $MyRow['orderno'] . '" class="db-btn db-btn-outline-primary db-btn-sm" title="' . __('View Order') . '"><i class="fas fa-shopping-cart"></i></a>' : '') . '
									' . ($MyRow['status'] == 2 ? '<a href="' . $RootPath . '/WorkOrderIssue.php?WO=' . $MyRow['wo'] . '&StockID=' . $MyRow['contractref'] . '" class="db-btn db-btn-outline-primary db-btn-sm" title="' . __('Issue Materials') . '"><i class="fas fa-box"></i></a>' : '') . '
									' . ($MyRow['status'] >= 2 ? '<a href="' . $RootPath . '/ContractCosting.php?SelectedContract=' . $MyRow['contractref'] . '" class="db-btn db-btn-outline-primary db-btn-sm" title="' . __('Full Costing Analysis') . '"><i class="fas fa-chart-line"></i></a>' : '') . '
								</td>
							</tr>';
					}
					
	echo '				</tbody>
									</table>
								</div>
							</div>
						</div>
				</main>
			</div>
		</form>
	</div>';

include(__DIR__ . '/includes/footer.php');
