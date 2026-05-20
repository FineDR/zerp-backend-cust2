<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Authorise Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2
echo '
<style>
	:root {
		--primary: hsl(197, 92%, 47%); 
		--primary-hover: hsl(197, 92%, 38%);
		--primary-dark: hsl(197, 75%, 22%);
		--primary-bg: hsl(197, 65%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--text-main: hsl(197, 15%, 12%);
		--text-muted: hsl(197, 8%, 50%);
		--card-bg: #ffffff;
		--border-color: hsl(220, 15%, 88%);
		--radius: 12px;
		--shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
	}

	body {
		background-color: var(--bg-workspace);
		font-family: "Inter", sans-serif;
		color: var(--text-main);
	}

	.aw-container {
		padding: 24px;
	}

	.aw-page-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 24px;
	}

	.aw-breadcrumb {
		font-size: 0.75rem;
		font-weight: 800;
		color: var(--primary);
		text-transform: uppercase;
		letter-spacing: 0.05em;
		margin-bottom: 4px;
	}

	.aw-page-title {
		font-size: 1.75rem;
		font-weight: 950;
		letter-spacing: -0.04em;
		color: var(--primary-dark);
		margin: 0;
	}

	.aw-grid-layout {
		display: grid;
		grid-template-columns: 1fr;
		gap: 24px;
	}

	@media (min-width: 1024px) {
		.aw-grid-layout {
			grid-template-columns: 1fr 350px;
		}
	}

	.aw-card {
		background: var(--card-bg);
		border-radius: var(--radius);
		border: 1px solid var(--border-color);
		box-shadow: var(--shadow-sm);
		overflow: hidden;
		margin-bottom: 20px;
	}

	.aw-card-header {
		padding: 12px 16px;
		border-bottom: 1px solid var(--border-color);
		background-color: #ffffff;
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.aw-card-title {
		font-size: 0.85rem;
		font-weight: 850;
		color: var(--primary-dark);
		text-transform: uppercase;
		letter-spacing: 0.025em;
		margin: 0;
	}

	.aw-table-wrapper {
		overflow-x: auto;
	}

	.aw-table {
		width: 100%;
		border-collapse: collapse;
		font-size: 0.82rem;
	}

	.aw-table th {
		text-align: left;
		padding: 10px 16px;
		background: #fbfcfd;
		color: var(--text-muted);
		font-weight: 800;
		text-transform: uppercase;
		font-size: 0.65rem;
		border-bottom: 1.5px solid var(--border-color);
	}

	.aw-table td {
		padding: 12px 16px;
		border-bottom: 1px solid var(--border-color);
	}

	.aw-nested-table {
		background: #fdfdfd;
		border-radius: 8px;
		margin: 8px;
		border: 1px solid #f1f5f9;
	}

	.aw-nested-table th {
		background: #f8fafc;
	}

	.aw-badge {
		padding: 4px 10px;
		border-radius: 999px;
		font-size: 0.7rem;
		font-weight: 750;
	}

	.aw-badge-pending { background: #fef3c7; color: #92400e; }
	.aw-badge-success { background: #dcfce7; color: #166534; }

	.aw-btn {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 10px 20px;
		border-radius: 8px;
		font-weight: 700;
		font-size: 0.85rem;
		cursor: pointer;
		transition: all 0.2s;
		border: none;
	}

	.aw-btn-primary {
		background: var(--primary);
		color: white;
	}

	.aw-btn-primary:hover {
		background: var(--primary-hover);
		transform: translateY(-1px);
	}

	.aw-select {
		padding: 6px 12px;
		border-radius: 6px;
		border: 1px solid var(--border-color);
		font-size: 0.75rem;
		font-weight: 600;
		outline: none;
		background: #fff;
	}

	.aw-select:focus {
		border-color: var(--primary);
		box-shadow: 0 0 0 3px var(--primary-bg);
	}
</style>
<div class="aw-container">';

$EmailSQL = "SELECT email FROM www_users WHERE userid='".$_SESSION['UserID']."'";
$EmailResult = DB_query($EmailSQL);
$EmailRow = DB_fetch_array($EmailResult);

if (isset($_POST['UpdateAll'])) {
	foreach ($_POST as $key => $Value) {
		if (mb_substr($key,0,6)=='Status') {
			$OrderNo=mb_substr($key,6);
			$Status=$_POST['Status'.$OrderNo];
			$Comment=date($_SESSION['DefaultDateFormat']).' - '.__('Authorised by').' <a href="mailto:' . $EmailRow['email'].'">' . $_SESSION['UserID'] . '</a><br />' . html_entity_decode($_POST['comment'],ENT_QUOTES,'UTF-8');
			$SQL="UPDATE purchorders
					SET status='".$Status."',
						stat_comment='".$Comment."',
						allowprint=1
					WHERE orderno='". $OrderNo."'";
			$Result = DB_query($SQL);
		}
	}
	prnMsg(__('Authorisation statuses updated successfully'), 'success');
}

$SQL="SELECT purchorders.*,
			suppliers.suppname,
			suppliers.currcode,
			www_users.realname,
			www_users.email,
			currencies.decimalplaces AS currdecimalplaces
		FROM purchorders INNER JOIN suppliers
			ON suppliers.supplierid=purchorders.supplierno
		INNER JOIN currencies
			ON suppliers.currcode=currencies.currabrev
		INNER JOIN www_users
			ON www_users.userid=purchorders.initiator
	WHERE status='Pending'";
$Result = DB_query($SQL);

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Purchasing / Workflow</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	</div>';

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="aw-grid-layout">';

// LEFT COLUMN: List of Orders
echo '<div class="aw-main-content">';

if (DB_num_rows($Result) == 0) {
    echo '<div class="aw-card">
            <div class="aw-card-body" style="padding: 40px; text-align: center; color: var(--text-muted);">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.4; margin-bottom: 12px;"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
                <div style="font-weight:700;">' . __('No pending purchase orders found for authorisation') . '</div>
            </div>
          </div>';
}

while ($MyRow=DB_fetch_array($Result)) {

	$AuthSQL="SELECT authlevel FROM purchorderauth
				WHERE userid='".$_SESSION['UserID']."'
				AND currabrev='".$MyRow['currcode']."'";

	$AuthResult = DB_query($AuthSQL);
	$MyAuthRow=DB_fetch_array($AuthResult);
	$AuthLevel=$MyAuthRow['authlevel'] ?? 0;

	$OrderValueSQL="SELECT sum(unitprice*quantityord) as ordervalue
		           	FROM purchorderdetails
			        WHERE orderno='".$MyRow['orderno'] . "'";

	$OrderValueResult = DB_query($OrderValueSQL);
	$MyOrderValueRow=DB_fetch_array($OrderValueResult);
	$OrderValue=$MyOrderValueRow['ordervalue'];

	if ($AuthLevel >= $OrderValue) {
		echo '<div class="aw-card">
				<div class="aw-card-header">
					<span class="aw-badge aw-badge-pending">#' . $MyRow['orderno'] . '</span>
					<h3 class="aw-card-title">' . $MyRow['suppname'] . '</h3>
					<div style="margin-left: auto;">
						<select name="Status'.$MyRow['orderno'].'" class="aw-select">
							<option selected="selected" value="Pending">' . __('Pending') . '</option>
							<option value="Authorised">' . __('Authorised') . '</option>
							<option value="Rejected">' . __('Rejected') . '</option>
							<option value="Cancelled">' . __('Cancelled') . '</option>
						</select>
					</div>
				</div>
				<div class="aw-table-wrapper">
					<table class="aw-table">
						<thead>
							<tr>
								<th>'.__('Requested By').'</th>
								<th>'.__('Ordered Date').'</th>
								<th>'.__('Delivery Date').'</th>
								<th style="text-align: right;">'.__('Total Value').'</th>
							</tr>
						</thead>
						<tbody>
							<tr style="font-weight: 700;">
								<td><a href="mailto:'.$MyRow['email'].'" style="color: var(--primary); text-decoration:none;">' . $MyRow['realname'] . '</a></td>
								<td>' . ConvertSQLDate($MyRow['orddate']) . '</td>
								<td>' . ConvertSQLDate($MyRow['deliverydate']) . '</td>
								<td style="text-align: right; color: var(--primary-dark);">' . $MyRow['currcode'] . ' ' . locale_number_format($OrderValue, $MyRow['currdecimalplaces']) . '</td>
							</tr>
							<tr>
								<td colspan="4" style="padding: 0;">';
		
		echo '<div class="aw-nested-table">
				<table class="aw-table">
					<thead>
						<tr>
							<th>' . __('Product / Description') . '</th>
							<th style="text-align: right;">' . __('Quantity') . '</th>
							<th style="text-align: right;">' . __('Price') . '</th>
							<th style="text-align: right;">' . __('Line Total') . '</th>
						</tr>
					</thead>
					<tbody>';

		$LineSQL="SELECT purchorderdetails.*,
					stockmaster.description,
					stockmaster.decimalplaces
				FROM purchorderdetails
				LEFT JOIN stockmaster
				ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE orderno='".$MyRow['orderno'] . "'";
		$LineResult = DB_query($LineSQL);

		while ($LineRow=DB_fetch_array($LineResult)) {
			$DecimalPlaces = ($LineRow['decimalplaces']!=NULL) ? $LineRow['decimalplaces'] : 2;
			echo '<tr>
					<td><div style="font-weight:650;">' . $LineRow['itemcode'] . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $LineRow['description'] . '</div></td>
					<td style="text-align: right;">' . locale_number_format($LineRow['quantityord'],$DecimalPlaces) . '</td>
					<td style="text-align: right;">' . locale_number_format($LineRow['unitprice'],$MyRow['currdecimalplaces']) . '</td>
					<td style="text-align: right; font-weight: 700;">' . locale_number_format($LineRow['unitprice']*$LineRow['quantityord'],$MyRow['currdecimalplaces']) . '</td>
				</tr>';
		} 
		echo '</tbody></table></div>';
		
		echo '</td></tr></tbody></table></div>';
		echo '<input type="hidden" name="comment" value="' . htmlspecialchars($MyRow['stat_comment'], ENT_QUOTES,'UTF-8') . '" />';
		echo '</div>';
	}
}

echo '</div>'; // End Main Content

// RIGHT COLUMN: Sidebar Summary
echo '<div class="aw-sidebar">';
echo '<div class="aw-card">
		<div class="aw-card-header">
			<h3 class="aw-card-title">' . __('Workflow Controls') . '</h3>
		</div>
		<div class="aw-card-body" style="padding: 16px;">
			<div style="margin-bottom: 20px;">
				<label style="display:block; font-size: 0.72rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 8px;">' . __('Decision Remarks') . '</label>
				<textarea name="comment" style="width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; font-size: 0.85rem; height: 100px; outline: none;" placeholder="' . __('Enter any relevant comments for the authorization history...') . '"></textarea>
			</div>
			<button type="submit" name="UpdateAll" class="aw-btn aw-btn-primary" style="width: 100%; justify-content: center;">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
				' . __('Process Authorisations') . '
			</button>
		</div>
	  </div>';

echo '<div class="aw-card" style="background: var(--primary-bg); border-color: var(--primary-subtle);">
		<div class="aw-card-body" style="padding: 16px; color: var(--primary-dark);">
			<div style="font-size: 0.75rem; font-weight: 800; margin-bottom: 6px;">' . __('Authority Note') . '</div>
			<div style="font-size: 0.8rem; line-height: 1.4; opacity: 0.8;">' . __('Only orders within your assigned authorization level for the respective currencies are shown in this list.') . '</div>
		</div>
	  </div>';
echo '</div>'; // End Sidebar

echo '</div>'; // End Grid Layout
echo '</form></div>'; // End Container

include(__DIR__ . '/includes/footer.php');
?>
