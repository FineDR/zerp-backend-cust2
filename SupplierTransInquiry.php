<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Transactions Inquiry');
$ViewTopic = 'AccountsPayable';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Search and analyze supplier transaction history') . '</p>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<div class="db-card">
			<div class="db-card-title">' . __('Inquiry Criteria') . '</div>
			<div class="db-card-body">
				<div class="db-grid db-grid-4">
					<div class="db-form-group">
						<label class="db-form-label">' . __('Transaction Type') . ':</label>
						<select name="TransType" class="db-form-select">';
$SQL = "SELECT typeid, typename FROM systypes WHERE typeid >= 20 AND typeid <= 23";
$ResultTypes = DB_query($SQL);
echo '<option value="All">' .__('All Types') . '</option>';
while ($MyRow=DB_fetch_array($ResultTypes)){
	$selected = (isset($_POST['TransType']) AND $MyRow['typeid'] == $_POST['TransType']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['typeid'] . '">' . $MyRow['typename'] . '</option>';
}
echo '					</select>
					</div>';

if (!isset($_POST['FromDate'])){
	$_POST['FromDate']=date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m'),1,date('Y')));
}
if (!isset($_POST['ToDate'])){
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}
if (!isset($_POST['SupplierNo'])) {
	$_POST['SupplierNo'] = '';
}

echo '				<div class="db-form-group">
						<label class="db-form-label">' . __('From Date') . ':</label>
						<input type="date" name="FromDate" class="db-form-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('To Date') . ':</label>
						<input type="date" name="ToDate" class="db-form-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
					</div>
					<div class="db-form-group">
						<label class="db-form-label">' . __('Supplier No') . ':</label>
						<input type="text" name="SupplierNo" class="db-form-input" placeholder="' . __('Search by Supplier ID') . '" value="' . $_POST['SupplierNo'] . '" />
					</div>
				</div>
			</div>
			<div class="db-card-footer db-form-actions">
				<button type="submit" name="ShowResults" class="db-btn db-btn-primary">' . __('Show Transactions') . '</button>
			</div>
		</div>
	</form>';

if (isset($_POST['ShowResults']) && $_POST['TransType'] != ''){
   $SQL_FromDate = FormatDateForSQL($_POST['FromDate']);
   $SQL_ToDate = FormatDateForSQL($_POST['ToDate']);
   $SQL = "SELECT type,
				transno,
		   		trandate,
				duedate,
				supplierno,
				suppname,
				suppreference,
				transtext,
				supptrans.rate,
				diffonexch,
				alloc,
				ovamount+ovgst as totalamt,
				currcode,
				typename,
				decimalplaces AS currdecimalplaces
			FROM supptrans
			INNER JOIN suppliers ON supptrans.supplierno=suppliers.supplierid
			INNER JOIN systypes ON supptrans.type = systypes.typeid
			INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
			WHERE ";

   $SQL = $SQL . "trandate >='" . $SQL_FromDate . "' AND trandate <= '" . $SQL_ToDate . "'";
	if  ($_POST['TransType']!='All')  {
		$SQL .= " AND type = " . $_POST['TransType'];
	}
	if ($_POST['SupplierNo'] != "")
	{
		$SQL .= " AND supptrans.supplierno LIKE '%".$_POST['SupplierNo']."%'";
	}
	$SQL .=  " ORDER BY id";

   $TransResult = DB_query($SQL);
   $ErrMsg = __('The supplier transactions for the selected criteria could not be retrieved because') . ' - ' . DB_error_msg();

   echo '<div class="db-card" style="margin-top: var(--space-6);">
			<div class="db-card-title">' . __('Search Results') . '</div>
			<div class="db-card-body">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Type') . '</th>
								<th>' . __('Number') . '</th>
								<th>' . __('Supplier Reference') . '</th>
								<th>' . __('Date') . '</th>
								<th>' . __('Supplier') . '</th>
								<th>' . __('Comments') . '</th>
								<th>' . __('Due Date') . '</th>
								<th class="text-right">' . __('Rate') . '</th>
								<th class="text-right">' . __('Amount') . '</th>
								<th class="text-center">' . __('Curr') . '</th>
							</tr>
						</thead>
						<tbody>';

	$RowCounter = 1;

	while ($MyRow=DB_fetch_array($TransResult)) {

		echo '<tr>
				<td class="db-font-semibold">', $MyRow['typename'], '</td>
				<td><span class="db-badge db-badge-info">', $MyRow['transno'], '</span></td>
				<td class="db-text-muted">', $MyRow['suppreference'], '</td>
				<td class="text-nowrap">', ConvertSQLDate($MyRow['trandate']), '</td>
				<td><div class="db-font-bold">', $MyRow['supplierno'], '</div><div class="db-text-muted db-font-sm">', $MyRow['suppname'], '</div></td>
				<td class="db-font-sm">', $MyRow['transtext'], '</td>
				<td class="text-nowrap">', ConvertSQLDate($MyRow['duedate']), '</td>
				<td class="text-right db-text-muted">', locale_number_format($MyRow['rate'],'Variable'), '</td>
				<td class="text-right db-font-bold">', locale_number_format($MyRow['totalamt'],$MyRow['currdecimalplaces']), '</td>
				<td class="text-center"><span class="db-badge db-badge-info">', $MyRow['currcode'], '</span></td>
			</tr>';


		$GLTransResult = DB_query("SELECT account,
										accountname,
										narrative,
										amount
									FROM gltrans INNER JOIN chartmaster
									ON gltrans.account=chartmaster.accountcode
									WHERE type='" . $MyRow['type'] . "'
									AND typeno='" . $MyRow['transno'] . "'",
									__('Could not retrieve the GL transactions for this AP transaction'));

		if (DB_num_rows($GLTransResult)==0){
			echo '<tr>
					<td colspan="10">' . __('There are no GL transactions created for the above AP transaction') . '</td>
				</tr>';
		} else {
			echo '<tr>
					<td colspan="10" style="padding: 1rem;">
						<div class="db-table-wrapper" style="border: 1px solid var(--border-color); border-radius: 8px;">
							<table class="db-table db-table-sm">
								<thead>
									<tr style="background: var(--bg-surface);">
										<th colspan="2"><b>' . __('GL Account') . '</b></th>
										<th class="text-right"><b>' . __('Local Amount') . '</b></th>
										<th><b>' . __('Narrative') . '</b></th>
									</tr>
								</thead>
								<tbody>';
			$CheckGLTransBalance =0;
			while ($GLTransRow = DB_fetch_array($GLTransResult)){

				echo '<tr>
						<td class="db-text-muted">', $GLTransRow['account'], '</td>
						<td class="db-font-semibold">', $GLTransRow['accountname'], '</td>
						<td class="text-right db-font-bold">', locale_number_format($GLTransRow['amount'],$_SESSION['CompanyRecord']['decimalplaces']), '</td>
						<td class="db-font-sm">', $GLTransRow['narrative'], '</td>
					</tr>';

				$CheckGLTransBalance += $GLTransRow['amount'];
			}
			if (round($CheckGLTransBalance, 5) != 0) {
				echo '<tr>
						<td colspan="4"><div class="db-alert db-alert-danger" style="margin: 0;">' . __('The GL transactions are out of balance by') . ' ' . locale_number_format($CheckGLTransBalance, $_SESSION['CompanyRecord']['decimalplaces']) . '</div></td>
					</tr>';
			}
			echo '				</tbody>
							</table>
						</div>
					</td>
				</tr>';
		}
	}
	echo '				</tbody>
					</table>
				</div>
			</div>
		</div>';
}
echo '</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
