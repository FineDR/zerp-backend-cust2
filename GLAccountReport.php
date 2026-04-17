<?php

require(__DIR__ . '/includes/session.php');

$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountReport';

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

if (isset($_POST['Period'])) {
	$SelectedPeriod = $_POST['Period'];
} elseif (isset($_GET['Period'])) {
	$SelectedPeriod = $_GET['Period'];
}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	if (!isset($SelectedPeriod)) {
		prnMsg(__('A period or range of periods must be selected from the list box'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}
	if (!isset($_POST['Account'])) {
		prnMsg(__('An account or range of accounts must be selected from the list box'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>
				<div class="centre" id="ReportHeader">
					' . $_SESSION['CompanyRecord']['coyname'] . '<br />
					' . __('GL Account Report') . '<br />
					' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '   ' . __('User') . ': ' . $_SESSION['UserID'] . '<br />
				</div>
				<table>
					<thead>
						<tr>
							<th>' . __('Type') . '</th>
							<th>' . __('Reference') . '</th>
							<th>' . __('Date') . '</th>
							<th>' . __('Debit') . '</th>
							<th>' . __('Credit') . '</th>
							<th>' . __('Narrative') . '</th>
							<th>' . __('Tag') . '</th>
						</tr>
					</thead>
					<tbody>';

	foreach ($_POST['Account'] as $SelectedAccount) {
		// Get account info
		$Result = DB_query("SELECT chartmaster.accountname,
								accountgroups.pandl
							FROM accountgroups
							INNER JOIN chartmaster ON accountgroups.groupname=chartmaster.group_
							WHERE chartmaster.accountcode='" . $SelectedAccount . "'");
		$AccountDetailRow = DB_fetch_row($Result);
		$AccountName = $AccountDetailRow[0];
		$PandLAccount = ($AccountDetailRow[1] == 1);

		$FirstPeriodSelected = min($SelectedPeriod);
		$LastPeriodSelected = max($SelectedPeriod);

		// Get transactions
		$SQL = "SELECT gltrans.counterindex,
					gltrans.type,
					typename,
					gltrans.typeno,
					gltrans.trandate,
					gltrans.narrative,
					gltrans.amount,
					gltrans.periodno,
					gltags.tagref AS tag
					FROM gltrans
					INNER JOIN systypes
						ON gltrans.type=systypes.typeid
					LEFT JOIN gltags
						ON gltrans.counterindex=gltags.counterindex
					WHERE gltrans.account = '" . $SelectedAccount . "'
						AND periodno>='" . $FirstPeriodSelected . "'
						AND periodno<='" . $LastPeriodSelected . "'";

		if (isset($_POST['tag']) and $_POST['tag'] != -1) {
			$SQL .= " AND gltags.tagref='" . $_POST['tag'] . "'";
		}

		$SQL .= " ORDER BY periodno,
						gltrans.trandate,
						gltrans.counterindex";

		$ErrMsg = __('The transactions for account') . ' ' . $SelectedAccount . ' ' . __('could not be retrieved because');
		$TransResult = DB_query($SQL, $ErrMsg);
		$HTML .= '<tr class="total_row">
					<td colspan="7"><h3>' . $SelectedAccount . ' - ' . $AccountName . ' ' . ': ' . __('Listing for Period') . ' ' . $FirstPeriodSelected . ' ' . __('to') . ' ' . $LastPeriodSelected . '</h3></td>
				<tr>';
		if ($PandLAccount) {
			$RunningTotal = 0;
		} else {
			// Calculate the brought forward balance from gltotals
			$SQL = "SELECT SUM(amount) AS bfwd
					FROM gltotals
					WHERE gltotals.account = '" . $SelectedAccount . "'
					AND gltotals.period < '" . $FirstPeriodSelected . "'";
			$ErrMsg = __('The brought forward balance for account') . ' ' . $SelectedAccount . ' ' . __('could not be retrieved');
			$BfwdResult = DB_query($SQL, $ErrMsg);
			$BfwdRow = DB_fetch_array($BfwdResult);
			$RunningTotal = $BfwdRow['bfwd'];

			$HTML .= '<tr class="total_row"><td colspan="3">' . __('Brought Forward Balance') . '</td>';
			if ($RunningTotal < 0) {
				$HTML .= '<td></td><td class="number">' . locale_number_format(-$RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
			} else {
				$HTML .= '<td class="number">' . locale_number_format($RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
			}
			$HTML .= '<td colspan="3"></td></tr>';
		}

		$PeriodTotal = 0;
		$PeriodNo = -9999;

		while ($MyRow = DB_fetch_array($TransResult)) {
			$TagsSQL = "SELECT gltags.tagref,
								tags.tagdescription
							FROM gltags
							INNER JOIN tags
								ON gltags.tagref=tags.tagref
							WHERE gltags.counterindex='" . $MyRow['counterindex'] . "'";
			$TagsResult = DB_query($TagsSQL);

			$TagDescriptions = '';
			while ($TagRows = DB_fetch_array($TagsResult)) {
				$TagDescriptions .= $TagRows['tagref'] . ' - ' . $TagRows['tagdescription'] . '<br />';
			}
			if ($MyRow['periodno'] != $PeriodNo) {
				if ($PeriodNo != -9999) { // not first
					$HTML .= '<tr class="total_row">
						<td colspan="3">' . __('Period Total') . '</td>';
					if ($PeriodTotal < 0) {
						$HTML .= '<td></td><td class="number">' . locale_number_format(-$PeriodTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>';
					} else {
						$HTML .= '<td class="number">' . locale_number_format($PeriodTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td><td></td>';
					}
					$HTML .= '<td colspan="2"></td></tr>';
				}
				$PeriodNo = $MyRow['periodno'];
				$PeriodTotal = 0;
			}

			$RunningTotal += $MyRow['amount'];
			$PeriodTotal += $MyRow['amount'];

			if ($MyRow['amount'] >= 0) {
				$DebitAmount = locale_number_format($MyRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
				$CreditAmount = '';
			} else {
				$CreditAmount = locale_number_format(-$MyRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
				$DebitAmount = '';
			}

			$FormatedTranDate = ConvertSQLDate($MyRow['trandate']);

			$TagSQL = "SELECT tagdescription FROM tags WHERE tagref='" . $MyRow['tag'] . "'";
			$TagResult = DB_query($TagSQL);
			$TagRow = DB_fetch_array($TagResult);

			$HTML .= '<tr class="striped_row">
				<td class="centre">' . $MyRow['typename'] . '</td>
				<td class="number">' . $MyRow['typeno'] . '</td>
				<td class="centre">' . $FormatedTranDate . '</td>
				<td class="number">' . $DebitAmount . '</td>
				<td class="number">' . $CreditAmount . '</td>
				<td>' . $MyRow['narrative'] . '</td>
				<td>' . $TagDescriptions . '</td>
			</tr>';
		}

		$HTML .= '<tr class="total_row">';
		if ($PandLAccount) {
			$HTML .= '<td>' . __('Total Period Movement') . '</td>';
		} else {
			$HTML .= '<td>' . __('Balance C/Fwd') . '</td>';
		}
		if ($RunningTotal < 0) {
			$HTML .= '<td colspan="3">
					</td><td class="number">' . locale_number_format(-$RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td colspan="2"></td>';
		} else {
			$HTML .= '<td colspan="2">
					</td><td class="number">' . locale_number_format($RunningTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
					<td colspan="3"></td>';
		}
		$HTML .= '</tr>';
	}

	if (count($_POST['Account']) == 0) {
		prnMsg(__('An account or range of accounts must be selected from the list box'), 'info');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="number">
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
		$DomPDF->stream($_SESSION['DatabaseName'] . '_GL_Account_report_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Inventory Planning Report');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/inventory.png" title="' . __('General Ledger Account Report') . '" alt="" />' . ' ' . __('General Ledger Account Report') . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else {
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
	.architect-btn.secondary { background: #e5e7eb; color: #374151; box-shadow: none; }
	.architect-btn.secondary:hover { background: #d1d5db; color: #111827; }
	.architect-btn.secondary i { color: #374151 !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	.custom-range-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 20px;
		margin-bottom: 24px;
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	@media (max-width: 900px) {
		.custom-bottom-layout { 
			display: flex; 
			flex-direction: column; 
		}
		.custom-range-grid {
			grid-template-columns: 1fr;
		}
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
						<a href="index.php?Application=GL" class="breadcrumb-item">' . __('general ledger') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('account report') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Analyze ledger transactions with hierarchical account and period selection') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" target="_blank" style="display: contents;">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-cog" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Execution') . '
						</h3>
					</div>
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px; background: #fff;">
						<button type="submit" name="PrintPDF" class="architect-btn">
							<i class="fas fa-file-pdf"></i> ' . __('Generate PDF') . '
						</button>
						<button type="submit" name="View" class="architect-btn secondary">
							<i class="fas fa-eye"></i> ' . __('View Online') . '
						</button>

						<div style="background: #fdf2f2; border: 1px solid #fecaca; padding: 16px; border-radius: 12px; margin-top: 12px;">
							<div style="display: flex; gap: 10px; align-items: flex-start;">
								<i class="fas fa-keyboard" style="color: #dc2626; margin-top: 3px;"></i>
								<div style="font-size: 0.8rem; color: #991b1b; line-height: 1.4;">
									' . __('Hold down the Shift or Ctrl key to select multiple accounts and periods simultaneously.') . '
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-top: 24px;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-tag" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Tag Filter') . '
						</h3>
					</div>
					<div style="padding: 24px; background: #fff;">
						<div class="db-form-group">
							<select name="tag" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';
	$SQL = "SELECT tagref, tagdescription FROM tags ORDER BY tagref";
	$Result = DB_query($SQL);
	echo '<option value="-1">-1 - ' . __('All tags') . '</option>';
	while ($MyRow = DB_fetch_array($Result)) {
		$selected = (isset($_POST['tag']) and $_POST['tag'] == $MyRow['tagref']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['tagref'] . '">' . $MyRow['tagref'] . ' - ' . $MyRow['tagdescription'] . '</option>';
	}
	echo '				</select>
						</div>
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-tasks" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Selection Criteria') . '
						</h3>
					</div>
					<div style="padding: 30px; background: #fff;">
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('General Ledger Accounts') . '</label>
								<select name="Account[]" size="12" multiple="multiple" class="db-input" style="width: 100%; border-radius: 12px; height: 300px; font-weight: 600; border-color: #d1fae5; padding: 12px;">';
	$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" . $_SESSION['UserID'] . "' AND glaccountusers.canview=1 ORDER BY chartmaster.accountcode";
	$AccountsResult = DB_query($SQL);
	while ($MyRow = DB_fetch_array($AccountsResult)) {
		echo '<option value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' ' . $MyRow['accountname'] . '</option>';
	}
	echo '						</select>
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Fiscal Periods') . '</label>
								<select name="Period[]" size="12" multiple="multiple" class="db-input" style="width: 100%; border-radius: 12px; height: 300px; font-weight: 600; border-color: #d1fae5; padding: 12px;">';
	$SQL = "SELECT periodno, lastdate_in_period FROM periods ORDER BY periodno DESC";
	$Periods = DB_query($SQL);
	while ($MyRow = DB_fetch_array($Periods)) {
		echo '<option value="' . $MyRow['periodno'] . '">' . __(MonthAndYearFromSQLDate($MyRow['lastdate_in_period'])) . '</option>';
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
