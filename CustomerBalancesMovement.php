<?php

require(__DIR__ . '/includes/session.php');

if (isset($_POST['FromDate'])) {
	$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);
}
if (isset($_POST['ToDate'])) {
	$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);
}

$Title = __('Customer Activity and Balances');
/*To do: Info in the manual. RChacon.*/
$ViewTopic = 'ARInquiries';
$BookMark = '';

if (!isset($_POST['CreateCSV'])) {
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

	.checkbox-container {
		display: flex;
		align-items: center;
		padding: 16px;
		background: #f9fafb;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		cursor: pointer;
		transition: all 0.2s;
	}
	.checkbox-container:hover { border-color: #059669; background: #f0fdf4; }
	
	@media (max-width: 1100px) {
		.custom-bottom-layout { display: flex; flex-direction: column; }
		.db-sidebar { width: 100%; }
	}
</style>';

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('balances movement') . '</span>
					</div>
					<div>
						<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
						<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Track customer debit/credit activity and opening/closing balance transitions') . '</p>
					</div>
				</div>
			</div>
		</div>';

	$SalesAreasResult = DB_query("SELECT areacode, areadescription FROM areas");
	$CustomersResult = DB_query("SELECT debtorno, name FROM debtorsmaster ORDER BY name");
	$SalesFolkResult = DB_query("SELECT salesmancode, salesmanname FROM salesman ORDER BY salesmanname");

	echo '<form id="Form1" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" style="display: contents;">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-calendar-alt" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Period Analysis') . '
						</h3>
					</div>
					<div style="padding: 24px; background: #fff; display: flex; flex-direction: column; gap: 20px;">
						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Start Date') . '</label>
							<input type="date" name="FromDate" class="db-input" value="' . date('Y-m-d', mktime(0, 0, 0, date('m') - $_SESSION['NumberOfMonthMustBeShown'], date('d'), date('Y'))) . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
						</div>

						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('End Date') . '</label>
							<input type="date" name="ToDate" class="db-input" value="' . date('Y-m-d') . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
						</div>

						<label class="checkbox-container">
							<input type="checkbox" name="CreateCSV" value="" style="width: 20px; height: 20px; margin-right: 12px; cursor: pointer;">
							<span style="font-size: 0.85rem; font-weight: 600; color: #374151;">' . __('Export as CSV Spreadsheet') . '</span>
						</label>

						<button type="submit" name="RunReport" class="architect-btn" style="margin-top: 10px;">
							<i class="fas fa-sync-alt"></i> ' . __('Run Movement Report') . '
						</button>
					</div>
				</div>

				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 20px; display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
					<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
					<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
						' . __('Report showing opening balance, movement during the period and the closing balance in local currency.') . '
					</div>
				</div>
			</aside>

			<main class="db-main">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; background: #fff;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-users" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Report Criteria') . '
						</h3>
					</div>
					<div style="padding: 30px; display: flex; flex-direction: column; gap: 24px;">
						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Select Customer') . '</label>
							<select name="Customer" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
								<option selected="selected" value="">' . __('All Customers') . '</option>';
	while ($CustomerRow = DB_fetch_array($CustomersResult)) {
		echo '<option value="' . $CustomerRow['debtorno'] . '">' . $CustomerRow['debtorno'] . ' - ' . $CustomerRow['name'] . '</option>';
	}
	echo '					</select>
						</div>

						<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Sales Area') . '</label>
								<select name="SalesArea" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
									<option selected="selected" value="">' . __('All Areas') . '</option>';
	while ($AreaRow = DB_fetch_array($SalesAreasResult)) {
		echo '<option value="' . $AreaRow['areacode'] . '">' . $AreaRow['areadescription'] . '</option>';
	}
	echo '						</select>
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Sales Person') . '</label>
								<select name="SalesPerson" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
									<option selected="selected" value="">' . __('All Salesfolk') . '</option>';
	while ($SalesPersonRow = DB_fetch_array($SalesFolkResult)) {
		echo '<option value="' . $SalesPersonRow['salesmancode'] . '">' . $SalesPersonRow['salesmanname'] . '</option>';
	}
	echo '						</select>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>';
	echo '</form>
	</div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

if ($_POST['Customer'] != '') {
	$WhereClause = "debtorsmaster.debtorno='" . $_POST['Customer'] . "'";
} elseif ($_POST['SalesArea'] != '') {
	$WhereClause = "custbranch.area='" . $_POST['SalesArea'] . "'";
} elseif ($_POST['SalesPerson'] != '') {
	$WhereClause = "custbranch.salesman='" . $_POST['SalesPerson'] . "'";
}

$SQL = "SELECT SUM(debtortrans.balance) AS currencybalance,
				debtorsmaster.debtorno,
				debtorsmaster.name,
				decimalplaces AS currdecimalplaces,
				SUM((debtortrans.balance)/debtortrans.rate) AS localbalance
		FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
		INNER JOIN currencies
		ON debtorsmaster.currcode=currencies.currabrev
		INNER JOIN custbranch
		ON debtorsmaster.debtorno=custbranch.debtorno";

if (isset($WhereClause) and mb_strlen($WhereClause) > 0) {
	$SQL .= " WHERE " . $WhereClause . " ";
}
$SQL .= " GROUP BY debtorsmaster.debtorno";

$Result = DB_query($SQL);

$LocalTotal = 0;

if (!isset($_POST['CreateCSV'])) {
	echo '<div class="db-page" style="padding-top: 0;">
			<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; background: #fff;">
				<div style="overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Customer') . ' </th>
								<th style="text-align: right;">' . __('Opening Balance') . '</th>
								<th style="text-align: right;">' . __('Debits') . '</th>
								<th style="text-align: right;">' . __('Credits') . '</th>
								<th style="text-align: right;">' . __('Closing Balance') . '</th>
							</tr>
						</thead>
						<tbody>';
} else {
	$CSVFile = '"' . __('Customer') . '","' . __('Opening Balance') . '","' . __('Debits') . '", "' . __('Credits') . '","' . __('Balance') . '"' . "\n";
}

$OpeningBalances = 0;
$Debits = 0;
$Credits = 0;
$ClosingBalances = 0;

while ($MyRow = DB_fetch_array($Result)) {

	$SQL = "SELECT SUM(ovamount+ovgst+ovdiscount+ovfreight) AS currencytotalpost,
					debtorsmaster.debtorno,
					SUM((ovamount+ovgst+ovdiscount+ovfreight)/debtortrans.rate) AS localtotalpost
			FROM debtortrans INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			WHERE trandate > '" . FormatDateForSQL($_POST['FromDate']) . "'
			AND debtorsmaster.debtorno = '" . $MyRow['debtorno'] . "'
			GROUP BY debtorsmaster.debtorno";

	$TransPostResult = DB_query($SQL);
	$TransPostRow = DB_fetch_array($TransPostResult);

	$SQL = "SELECT SUM(CASE WHEN debtortrans.type=10 THEN ovamount+ovgst+ovdiscount+ovfreight ELSE 0 END) AS currencydebits,
					SUM(CASE WHEN debtortrans.type<>10 THEN ovamount+ovgst+ovdiscount+ovfreight ELSE 0 END) AS currencycredits,
					debtorsmaster.debtorno,
					SUM(CASE WHEN debtortrans.type=10 THEN (ovamount+ovgst+ovdiscount+ovfreight)/debtortrans.rate ELSE 0 END) AS localdebits,
					SUM(CASE WHEN debtortrans.type<>10 THEN (ovamount+ovgst+ovdiscount+ovfreight)/debtortrans.rate ELSE 0 END) AS localcredits
			FROM debtortrans INNER JOIN debtorsmaster
				ON debtortrans.debtorno=debtorsmaster.debtorno
			WHERE trandate>='" . FormatDateForSQL($_POST['FromDate']) . "' AND trandate <= '" . FormatDateForSQL($_POST['ToDate']) . "'
			AND debtorsmaster.debtorno = '" . $MyRow['debtorno'] . "'
			GROUP BY debtorsmaster.debtorno";

	$TransResult = DB_query($SQL);
	$TransRow = DB_fetch_array($TransResult);

	$OpeningBal = $MyRow['localbalance'] - $TransPostRow['localtotalpost'] - $TransRow['localdebits'] - $TransRow['localcredits'];
	$ClosingBal = $MyRow['localbalance'] - $TransPostRow['localtotalpost'];
	if ($OpeningBal != 0 OR $ClosingBal != 0 OR $TransRow['localdebits'] != 0 OR $TransRow['localcredits'] != 0) {

		if (!isset($_POST['CreateCSV'])) {
			echo '<tr>
					<td style="font-weight: 700; color: #064e3b;">' . $MyRow['name'] . ' </td>
					<td style="text-align: right; font-family: monospace;">' . locale_number_format($OpeningBal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="text-align: right; color: #059669; font-weight: 600;">' . locale_number_format($TransRow['localdebits'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="text-align: right; color: #dc2626; font-weight: 600;">' . locale_number_format($TransRow['localcredits'], $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td style="text-align: right; font-weight: 800; color: #064e3b;">' . locale_number_format($ClosingBal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
				</tr>';
		} else { //send the line to CSV file
			$CSVFile .= '"' . stripcomma($MyRow['name']) . '","' . stripcomma($OpeningBal) . '","' . stripcomma($TransRow['localdebits']) . '","' . stripcomma($TransRow['localcredits']) . '","' . stripcomma($ClosingBal) . '"' . "\n";
		}
	}

	$OpeningBalances += $OpeningBal;
	$Debits += $TransRow['localdebits'];
	$Credits += $TransRow['localcredits'];
	$ClosingBalances += $ClosingBal;
}

if (!isset($_POST['CreateCSV'])) {
	echo '<tr>
			<td style="background: #f8fafc; font-weight: 900; text-transform: uppercase; color: #064e3b;">' . __('Total Sum') . ' </td>
			<td style="background: #f8fafc; text-align: right; font-weight: 900; font-family: monospace;">' . locale_number_format($OpeningBalances, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td style="background: #f8fafc; text-align: right; font-weight: 900; color: #059669;">' . locale_number_format($Debits, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td style="background: #f8fafc; text-align: right; font-weight: 900; color: #dc2626;">' . locale_number_format($Credits, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
			<td style="background: #f8fafc; text-align: right; font-weight: 900; color: #064e3b;">' . locale_number_format($ClosingBalances, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
		</tr>';
	echo '</tbody></table></div></div></div>';
}

if (isset($_POST['CreateCSV'])) {

	header('Content-Encoding: UTF-8');
	header('Content-type: text/csv; charset=UTF-8');
	header("Content-disposition: attachment; filename=CustomerBalancesMovement_" . FormatDateForSQL($_POST['FromDate']) . '-' . FormatDateForSQL($_POST['ToDate']) . '.csv');
	header("Pragma: public");
	header("Expires: 0");
	echo $CSVFile;
	exit();
}

include(__DIR__ . '/includes/footer.php');

function stripcomma($str)
{ //because we're using comma as a delimiter
	return str_replace(',', '', $str);
}
