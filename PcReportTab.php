<?php

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

$ViewTopic = 'PettyCash';
$BookMark = 'PcReportTab';
$Title = __('Petty Cash Management Report');

// --- Architect Workspace Styling ---
$ExtraHeadContent = '
<style>
    :root {
        --primary: #059669;
        --primary-hover: #047857;
        --rose: #e11d48;
        --slate: #64748b;
        --bg-main: #f8fafc;
        --card-bg: #ffffff;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }
    body { background-color: var(--bg-main) !important; color: var(--text-main); font-family: "Inter", sans-serif; -webkit-font-smoothing: antialiased; }
    .db-page { padding: 30px; max-width: 1600px; margin: 0 auto; }
    
    /* Header */
    .premium-header {
        background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border-color);
        margin: -30px -30px 30px -30px; padding: 20px 30px; position: sticky; top: 0; z-index: 1000;
    }
    .header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Cards */
    .db-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 25px; }
    .db-card-header { padding: 18px 24px; border-bottom: 1px solid var(--border-color); background: #fcfcfd; display: flex; align-items: center; justify-content: space-between; }
    .db-card-title { font-size: 0.95rem; font-weight: 800; color: #334155; }
    .db-card-body { padding: 24px; }

    /* Tables */
    .table-container { overflow-x: auto; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f8fafc !important; color: #475569 !important; padding: 14px 20px !important; border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important; font-size: 0.75rem !important; text-transform: uppercase !important; font-weight: 800 !important;
    }
    table.selection td { padding: 16px 20px !important; font-size: 0.85rem !important; border-bottom: 1px solid #f1f5f9 !important; vertical-align: middle; }
    
    /* Forms */
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
    .form-control { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 1rem; transition: all 0.2s; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); outline: none; }

    .btn-architect { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; border-radius: 10px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1.5px solid #d1d5db; color: #475569; }

    /* Responsive Scaling - Forced Overrides */
    @media (max-width: 767px) {
        .db-page { padding: 15px !important; margin-left: 0 !important; width: 100% !important; overflow-x: hidden !important; }
        .premium-header { margin: -15px -15px 20px -15px !important; padding: 15px !important; width: calc(100% + 30px) !important; border-radius: 0 !important; }
        .page-title { font-size: 1.4rem !important; }
        .db-card-body { padding: 15px !important; }
        .responsive-grid { grid-template-columns: 1fr !important; gap: 10px !important; }
        .btn-architect { width: 100% !important; margin-bottom: 8px !important; }
        .table-container { margin: 0 -15px !important; border-radius: 0 !important; border-left: none !important; border-right: none !important; }
    }
</style>';

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['FromDate'])){$_POST['FromDate'] = ConvertSQLDate($_POST['FromDate']);}
if (isset($_POST['ToDate'])){$_POST['ToDate'] = ConvertSQLDate($_POST['ToDate']);}

if (isset($_POST['SelectedTabs'])){
	$SelectedTabs = mb_strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])){
	$SelectedTabs = mb_strtoupper($_GET['SelectedTabs']);
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	$SQLFromDate = FormatDateForSQL($_POST['FromDate']);
	$SQLToDate = FormatDateForSQL($_POST['ToDate']);

	$SQLTabs = "SELECT tabcode,
						usercode,
						typetabcode,
						currency,
						tablimit,
						assigner,
						authorizer,
						authorizerexpenses,
						glaccountassignment,
						glaccountpcash,
						taxgroupid
			FROM pctabs
			WHERE tabcode = '" . $SelectedTabs . "'";

	$TabResult = DB_query($SQLTabs,
						 __('No Petty Cash Tabs were returned by the SQL because'),
						 __('The SQL that failed was:'));

	$Tabs = DB_fetch_array($TabResult);

	$SQLDecimalPlaces = "SELECT decimalplaces
					FROM currencies,pctabs
					WHERE currencies.currabrev = pctabs.currency
						AND tabcode='" . $SelectedTabs . "'";
	$Result = DB_query($SQLDecimalPlaces);
	$MyRow = DB_fetch_array($Result);
	$CurrDecimalPlaces = $MyRow['decimalplaces'];


	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$CurrencySQL = "SELECT currency FROM currencies WHERE currabrev='" . $Tabs['currency'] . "'";
	$CurrencyResult = DB_query($CurrencySQL);
	$CurrencyRow = DB_fetch_array($CurrencyResult);

	$UserSQL = "SELECT realname FROM www_users WHERE userid='" . $Tabs['usercode'] . "'";
	$UserResult = DB_query($UserSQL);
	$UserRow = DB_fetch_array($UserResult);

	$AssignerSQL = "SELECT realname FROM www_users WHERE userid='" . $Tabs['assigner'] . "'";
	$AssignerResult = DB_query($AssignerSQL);
	$AssignerRow = DB_fetch_array($AssignerResult);

	$AuthoriserSQL = "SELECT realname FROM www_users WHERE userid='" . $Tabs['authorizer'] . "'";
	$AuthoriserResult = DB_query($AuthoriserSQL);
	$AuthoriserRow = DB_fetch_array($AuthoriserResult);

	$AuthExpSQL = "SELECT realname FROM www_users WHERE userid='" . $Tabs['authorizerexpenses'] . "'";
	$AuthExpResult = DB_query($AuthExpSQL);
	$AuthExpRow = DB_fetch_array($AuthExpResult);

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="db-page">
				<div class="premium-header">
					<div class="header-inner">
						<div>
							<div class="breadcrumb">' . $_SESSION['CompanyRecord']['coyname'] . '</div>
							<div class="page-title">' . $Title . '</div>
						</div>
					</div>
				</div>
				<div class="db-card">
					<div class="db-card-header">
						<div class="db-card-title">' . __('Tab Meta Information') . '</div>
					</div>
					<div class="db-card-body">
						<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
							<div><div class="breadcrumb">' . __('Tab Code') . '</div><div style="font-weight:800;">' . $SelectedTabs . '</div></div>
							<div><div class="breadcrumb">' . __('User') . '</div><div style="font-weight:700;">' . $Tabs['usercode'] . ' - ' . $UserRow['realname'] . '</div></div>
							<div><div class="breadcrumb">' . __('Currency') . '</div><div style="font-weight:700;">' . $Tabs['currency'] . ' - ' . $CurrencyRow['currency'] . '</div></div>
							<div><div class="breadcrumb">' . __('Date Range') . '</div><div style="font-weight:700;">' . $_POST['FromDate'] . ' ' . __('to') . ' ' . $_POST['ToDate'] . '</div></div>
						</div>
					</div>
				</div>
				<div class="db-card">
				<div class="table-container">';

	$SQLBalance = "SELECT SUM(amount)
			FROM pcashdetails
			WHERE tabcode = '" . $SelectedTabs . "'
			AND date < '" . $SQLFromDate . "'";

	$TabBalance = DB_query($SQLBalance);

	$Balance = DB_fetch_array($TabBalance);

	if ( !isset($Balance['0'])){
		$Balance['0'] = 0;
	}

	$HTML .= '<tr><td>' . __('Balance before ') . '' . $_POST['FromDate'] . ':</td>
				<td></td>
				<td>' . locale_number_format($Balance['0'],$_SESSION['CompanyRecord']['decimalplaces']) . ' ' . $Tabs['currency'] . '</td>
			</tr>';

	$SQLBalanceNotAut = "SELECT SUM(amount)
			FROM pcashdetails
			WHERE tabcode = '" . $SelectedTabs . "'
			AND authorized = '1000-01-01'
			AND date < '" . $SQLFromDate . "'";

	$TabBalanceNotAut = DB_query($SQLBalanceNotAut);

	$BalanceNotAut = DB_fetch_array($TabBalanceNotAut);

	if ( !isset($BalanceNotAut['0'])){
		$BalanceNotAut['0'] = 0;
	}

	$HTML .= '<tr><td>' . __('Total not authorised before ') . '' . $_POST['FromDate'] . ':</td>
			  <td></td>
			  <td>' . '' . locale_number_format($BalanceNotAut['0'],$_SESSION['CompanyRecord']['decimalplaces']) . ' ' . $Tabs['currency'] . '</td>
		  </tr>';


	$HTML .=  '</table></div></div>';

	/*show a table of the accounts info returned by the SQL
	Account Code ,   Account Name , Month Actual, Month Budget, Period Actual, Period Budget */

	$SQL = "SELECT counterindex,
					tabcode,
					date,
					codeexpense,
					amount,
					authorized,
					posted,
					purpose,
					notes
			FROM pcashdetails
			WHERE tabcode = '" . $SelectedTabs . "'
				AND date >= '" . $SQLFromDate . "'
				AND date <= '" . $SQLToDate . "'
			ORDER BY date, counterindex Asc";

	$TabDetail = DB_query($SQL,
						__('No Petty Cash movements for this tab were returned by the SQL because'),
						__('The SQL that failed was:'));

	$HTML .=  '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Transaction History') . '</div>
				</div>
				<div class="table-container">
				<table class="selection">
			<thead>
				<tr>
					<th class="SortedColumn">' . __('Date of Expense') . '</th>
					<th class="SortedColumn">' . __('Expense Code') . '</th>
					<th class="SortedColumn">' . __('Gross Amount') . '</th>
					<th>' . __('Tax') . '</th>
					<th>' . __('Tax Group') . '</th>
					<th>' . __('Business Purpose') . '</th>
					<th>' . __('Notes') . '</th>
					<th>' . __('Receipt Attachment') . '</th>
					<th>' . __('Date Authorised') . '</th>
				</tr>
			</thead>
			</tbody>';

	while ($MyRow = DB_fetch_array($TabDetail)) {

		$TaxesDescription = '';
		$TaxesTaxAmount = '';
		$TaxSQL = "SELECT counterindex,
							pccashdetail,
							calculationorder,
							description,
							taxauthid,
							purchtaxglaccount,
							taxontax,
							taxrate,
							amount
						FROM pcashdetailtaxes
						WHERE pccashdetail='" . $MyRow['counterindex'] . "'";
		$TaxResult = DB_query($TaxSQL);

		while ($MyTaxRow = DB_fetch_array($TaxResult)) {
			$TaxesDescription .= $MyTaxRow['description'] . '<br />';
			$TaxesTaxAmount .= locale_number_format($MyTaxRow['amount'], $CurrDecimalPlaces) . '<br />';
		}

		//Generate download link for expense receipt, or show text if no receipt file is found.
		$ReceiptSupportedExt = array('png','jpg','jpeg','pdf','doc','docx','xls','xlsx'); //Supported file extensions
		$ReceiptDir = $PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/expenses_receipts/'; //Receipts upload directory
		$ReceiptSQL = "SELECT hashfile,
								extension
								FROM pcreceipts
								WHERE pccashdetail='" . $MyRow['counterindex'] . "'";
		$ReceiptResult = DB_query($ReceiptSQL);
		$ReceiptRow = DB_fetch_array($ReceiptResult);
		if (DB_num_rows($ReceiptResult) > 0) { //If receipt exists in database
			$ReceiptHash = $ReceiptRow['hashfile'];
			$ReceiptExt = $ReceiptRow['extension'];
			$ReceiptFileName = $ReceiptHash . '.' . $ReceiptExt;
			$ReceiptPath = $ReceiptDir . $ReceiptFileName;
			$ReceiptText = '<a href="' . $ReceiptPath . '" download="ExpenseReceipt-' . mb_strtolower($SelectedTabs) . '-[' . $MyRow['date'] . ']-[' . $MyRow['counterindex'] . ']">' . __('Download attachment') . '</a>';
		} else {
			$ReceiptText = __('No attachment');
		}

		if ($MyRow['authorized'] == '1000-01-01' or $MyRow['authorized'] == '0000-00-00') {
					$AuthorisedDate = __('Unauthorised');
				} else {
					$AuthorisedDate = ConvertSQLDate($MyRow['authorized']);
				}

		$SQLDes = "SELECT description
					FROM pcexpenses
					WHERE codeexpense = '" . $MyRow['codeexpense'] . "'";

		$ResultDes = DB_query($SQLDes);
		$Description=DB_fetch_array($ResultDes);
		if (!isset($Description[0])) {
				$ExpenseCodeDes = 'ASSIGNCASH';
		} else {
				$ExpenseCodeDes = $MyRow['codeexpense'] . ' - ' . $Description[0];
		}

		$HTML .=  '<tr class="striped_row">
					<td class="date">' . ConvertSQLDate($MyRow['date']) . '</td>
					<td>' . $ExpenseCodeDes . '</td>
					<td class="number">' . locale_number_format($MyRow['amount'], $CurrDecimalPlaces) . '</td>
					<td class="number">' . $TaxesTaxAmount . '</td>
					<td>' . $TaxesDescription . '</td>
					<td>' . $MyRow['purpose'] . '</td>
					<td>' . $MyRow['notes'] . '</td>
					<td>' . $ReceiptText . '</td>
					<td class="date">' . $AuthorisedDate . '</td>
				</tr>';
	}

	$SQLAmount="SELECT sum(amount)
				FROM pcashdetails
				WHERE tabcode = '" . $SelectedTabs . "'
				AND date <= '" . $SQLToDate . "'";

	$ResultAmount = DB_query($SQLAmount);
	$Amount = DB_fetch_array($ResultAmount);

	if (!isset($Amount[0])) {
		$Amount[0] = 0;
	}

	$HTML .= '</tbody>
		<tfoot>
			<tr class="total_row">
				<td colspan="2" class="number">' . __('Balance at') . ' ' .$_POST['ToDate'] . ':</td>
				<td class="number">' . locale_number_format($Amount[0],$_SESSION['CompanyRecord']['decimalplaces']) . ' </td>
				<td>' . $Tabs['currency'] . '</td>
				<td colspan="6"></td>
			</tr>
		</tfoot>';


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'landscape');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_PettyCashTabReport_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		include(__DIR__ . '/includes/header.php');
		echo '<div class="db-page">' . $HTML . '</div>';
		include(__DIR__ . '/includes/footer.php');
	}

    echo '</form>';
} else {
	include(__DIR__ . '/includes/header.php');

	echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $_SESSION['Theme'] . '/images/money_add.png" title="' . __('Payment Entry')
	. '" alt="" />' . ' ' . $Title . '</p>';

	echo '<div class="db-page">
		<div class="premium-header">
			<div class="header-inner">
				<div>
					<div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Reports') . '</div>
					<div class="page-title">' . $Title . '</div>
				</div>
			</div>
		</div>

		<div style="max-width: 600px; margin: 40px auto;">
			<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title">' . __('Generate Management Report') . '</div>
				</div>
				<div class="db-card-body">
					<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						
						<div class="form-group">
							<label class="form-label">' . __('Petty Cash Tab') . '</label>
							<select name="SelectedTabs" class="form-control">';
								$SQL = "SELECT tabcode FROM pctabs WHERE (authorizer='" . $_SESSION['UserID'] . "' OR usercode='" . $_SESSION['UserID'] . "' OR assigner='" . $_SESSION['UserID'] . "') ORDER BY tabcode";
								$Result = DB_query($SQL);
								while ($MyRow = DB_fetch_array($Result)) {
									$sel = (isset($_POST['SelectedTabs']) && $MyRow['tabcode'] == $_POST['SelectedTabs']) ? 'selected' : '';
									echo '<option ' . $sel . ' value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
								}
							echo '</select>
						</div>

						<div class="responsive-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
							<div class="form-group">
								<label class="form-label">' . __('From Date') . '</label>
								<input type="date" name="FromDate" class="form-control" value="' . FormatDateForSQL($_POST['FromDate']) . '" />
							</div>
							<div class="form-group">
								<label class="form-label">' . __('To Date') . '</label>
								<input type="date" name="ToDate" class="form-control" value="' . FormatDateForSQL($_POST['ToDate']) . '" />
							</div>
						</div>

						<div class="responsive-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px;">
							<button type="submit" name="View" class="btn-architect btn-primary">' . __('Show HTML') . '</button>
							<button type="submit" name="PrintPDF" class="btn-architect btn-outline">' . __('Print PDF') . '</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>';
	include(__DIR__ . '/includes/footer.php');

}
