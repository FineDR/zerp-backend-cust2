<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Authorise Purchase Orders');
$ViewTopic = 'PurchaseOrdering';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Review and authorize pending purchase orders') . '</p>
			</div>
		</div>';

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
}

/* Retrieve the purchase order header information
 */
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

echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<div class="db-card">
		<div class="db-card-body">
			<div class="db-table-wrapper">
				<table class="db-table">';

/* Create the table for the purchase order header */
echo '<thead>
		<tr>
			<th>' . __('Order Number') . '</th>
			<th>' . __('Supplier') . '</th>
			<th>' . __('Date Ordered') . '</th>
			<th>' . __('Initiator') . '</th>
			<th>' . __('Delivery Date') . '</th>
			<th class="text-center">' . __('Action') . '</th>
		</tr>
	</thead>
	<tbody>';

while ($MyRow=DB_fetch_array($Result)) {

	$AuthSQL="SELECT authlevel FROM purchorderauth
				WHERE userid='".$_SESSION['UserID']."'
				AND currabrev='".$MyRow['currcode']."'";

	$AuthResult = DB_query($AuthSQL);
	$MyAuthRow=DB_fetch_array($AuthResult);
	$AuthLevel=$MyAuthRow['authlevel'];

	$OrderValueSQL="SELECT sum(unitprice*quantityord) as ordervalue
		           	FROM purchorderdetails
			        WHERE orderno='".$MyRow['orderno'] . "'";

	$OrderValueResult = DB_query($OrderValueSQL);
	$MyOrderValueRow=DB_fetch_array($OrderValueResult);
	$OrderValue=$MyOrderValueRow['ordervalue'];

	if ($AuthLevel>=$OrderValue) {
		echo '<tr class="db-font-semibold" style="background: var(--bg-workspace);">
				<td><span class="db-badge db-badge-info">' . $MyRow['orderno'] . '</span></td>
				<td>' . $MyRow['suppname'] . '</td>
				<td class="text-nowrap">' . ConvertSQLDate($MyRow['orddate']) . '</td>
				<td><a href="mailto:'.$MyRow['email'].'" class="db-link">' . $MyRow['realname'] . '</a></td>
				<td class="text-nowrap">' . ConvertSQLDate($MyRow['deliverydate']) . '</td>
				<td class="text-center">
					<select name="Status'.$MyRow['orderno'].'" class="db-form-select db-form-input-sm" style="max-width: 150px; margin: 0 auto;">
						<option selected="selected" value="Pending">' . __('Pending') . '</option>
						<option value="Authorised">' . __('Authorised') . '</option>
						<option value="Rejected">' . __('Rejected') . '</option>
						<option value="Cancelled">' . __('Cancelled') . '</option>
					</select>
				</td>
			</tr>';
		echo '<input type="hidden" name="comment" value="' . htmlspecialchars($MyRow['stat_comment'], ENT_QUOTES,'UTF-8') . '" />';
		$LineSQL="SELECT purchorderdetails.*,
					stockmaster.description,
					stockmaster.decimalplaces
				FROM purchorderdetails
				LEFT JOIN stockmaster
				ON stockmaster.stockid=purchorderdetails.itemcode
			WHERE orderno='".$MyRow['orderno'] . "'";
		$LineResult = DB_query($LineSQL);

		echo '<tr>
				<td colspan="6" style="padding-left: 2rem;">
					<div class="db-table-wrapper" style="border: 1px solid var(--border-color); border-radius: 8px; margin: 10px 0;">
						<table class="db-table db-table-sm">
							<thead>
								<tr>
									<th>' . __('Product') . '</th>
									<th class="text-right">' . __('Quantity') . '</th>
									<th>' . __('Currency') . '</th>
									<th class="text-right">' . __('Price') . '</th>
									<th class="text-right">' . __('Line Total') . '</th>
								</tr>
							</thead>
							<tbody>';

		while ($LineRow=DB_fetch_array($LineResult)) {
			$DecimalPlaces = ($LineRow['decimalplaces']!=NULL) ? $LineRow['decimalplaces'] : 2;
			echo '<tr>
					<td>' . $LineRow['description'] . '</td>
					<td class="text-right">' . locale_number_format($LineRow['quantityord'],$DecimalPlaces) . '</td>
					<td>' . $MyRow['currcode'] . '</td>
					<td class="text-right">' . locale_number_format($LineRow['unitprice'],$MyRow['currdecimalplaces']) . '</td>
					<td class="text-right db-font-semibold">' . locale_number_format($LineRow['unitprice']*$LineRow['quantityord'],$MyRow['currdecimalplaces']) . '</td>
				</tr>';
		} 
		echo '</tbody></table>
					</div>
				</td>
			</tr>';
	}
}
echo '</tbody>
	</table>
			</div>
		</div>
		</div>
		<div class="db-card-footer db-form-actions">
			<button type="submit" name="UpdateAll" class="db-btn db-btn-primary">' . __('Update Authorization Status') . '</button>
		</div>
	</div>
</form>
</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
