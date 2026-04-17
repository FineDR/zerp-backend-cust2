<?php

/* Absolute Final Modernized Allocation Traceability Hub */

require(__DIR__ . '/includes/session.php');

$Title = __('Allocation Traceability Hub');
$ViewTopic = 'ARInquiries';
$BookMark = 'WhereAllocated';

if (isset($_GET['TransNo']) AND isset($_GET['TransType'])) {
	$_POST['TransNo'] = (int)$_GET['TransNo'];
	$_POST['TransType'] = (int)$_GET['TransType'];
	$_POST['ShowResults'] = true;
}

// Inject premium styles for the Architect workspace
$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
	/* Architect Workspace Overrides */
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
		display: inline-flex; align-items: center; gap: 10px;
		padding: 12px 28px; border-radius: 50px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
</style>';

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6;">
						<i class="fas fa-home"></i> ' . __('Receivables') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem;"></i> ' . __('Intelligence') . '
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div style="width: 64px; height: 64px; border-radius: 20px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 12px 24px -6px rgba(5, 150, 105, 0.4); color: white;">
							<i class="fas fa-microchip" style="font-size: 1.8rem;"></i>
						</div>
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('End-to-end trace of transaction settlements and debt aging') . '</p>
						</div>
					</div>
				</div>
				<div class="db-header-actions">
					<a href="' . $RootPath . '/CustomerInquiry.php" class="architect-btn">
						<i class="fas fa-arrow-left" style="color: var(--primary);"></i> ' . __('Back to Inquiry') . '
					</a>
				</div>
			</div>
		</div>';

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" style="display: contents;">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<div class="db-bottom-layout" style="display: grid; grid-template-columns: 340px 1fr; gap: var(--space-8); align-items: start;">
		<aside class="db-sidebar">';

/* Filter Card */
echo '<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
		<div class="db-card-header">
			<h3 class="db-card-title">
				<i class="fas fa-filter" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Registry Filter') . '
			</h3>
		</div>
		<div style="padding: 24px;">
			<div class="db-form-group" style="margin-bottom: 24px;">
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Ledger Object') . '</label>
				<select name="TransType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';

if (!isset($_POST['TransType'])) {
	$_POST['TransType'] = '10';
}
$Types = [
	'10' => __('Sales Invoice'),
	'12' => __('Cash Receipt'),
	'11' => __('Credit Memo')
];
foreach ($Types as $TypeNo => $TypeName) {
	echo '<option ' . ($_POST['TransType'] == $TypeNo ? 'selected="selected"' : '') . ' value="' . $TypeNo . '">' . $TypeName . '</option>';
}

echo '				</select>
			</div>
			<div class="db-form-group" style="margin-bottom: 32px;">
				<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Transaction ID') . '</label>
				<input type="text" class="db-input" name="TransNo" required="required" maxlength="10" value="' . (isset($_POST['TransNo']) ? $_POST['TransNo'] : '') . '" placeholder="' . __('e.g. 1045') . '" style="border-radius: 12px; height: 50px; border-color: #d1fae5;" />
			</div>
			<button type="submit" name="ShowResults" class="db-btn" style="width: 100%; justify-content: center; font-weight: 700; padding: 18px; border-radius: 14px; background: #059669; color: white; border: none; box-shadow: 0 10px 15px -3px rgba(5, 150, 105, 0.3); cursor: pointer;">
				<i class="fas fa-bolt" style="margin-right: 12px;"></i> ' . __('Run Analysis') . '
			</button>
		</div>
	</div>';

echo '	</aside>
		<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">';

if (isset($_POST['ShowResults']) AND $_POST['TransNo'] == '') {
	prnMsg(__('Search identifier is required'), 'warn');
}

if (isset($_POST['ShowResults']) AND $_POST['TransNo'] != '') {

	$SQL = "SELECT debtortrans.id,
				ovamount+ovgst AS totamt,
				currencies.decimalplaces AS currdecimalplaces,
				debtorsmaster.currcode,
				debtortrans.rate
			FROM debtortrans INNER JOIN debtorsmaster
			ON debtortrans.debtorno=debtorsmaster.debtorno
			INNER JOIN currencies
			ON debtorsmaster.currcode=currencies.currabrev
			WHERE type='" . $_POST['TransType'] . "'
			AND transno = '" . (int)$_POST['TransNo'] . "'";

	if ($_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND debtortrans.salesperson='" . $_SESSION['SalesmanLogin'] . "'";
	}
	$Result = DB_query($SQL);
	$GrandTotal = 0;
	$Rows = DB_num_rows($Result);
	
	if ($Rows == 0) {
		echo '<div class="db-card" style="min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center; border-radius: 20px; border: 2px dashed #d1fae5; background: #f9fafb;">
				<div class="db-card-body">
					<i class="fas fa-search fa-4x" style="color: #a7f3d0; margin-bottom: 30px;"></i>
					<h3 style="color: #064e3b; font-weight: 900; font-size: 1.5rem;">' . __('Record Not Found') . '</h3>
					<p style="color: #059669; opacity: 0.7;">' . __('No matching transaction found for the specified parameters.') . '</p>
				</div>
			</div>';
	} else {
		while ($MyRow = DB_fetch_array($Result)) {
			$GrandTotal += $MyRow['totamt'];
			$Rate = $MyRow['rate'];
			$AllocToID = $MyRow['id'];
			$CurrCode = $MyRow['currcode'];
			$CurrDecimalPlaces = $MyRow['currdecimalplaces'];
			
			$SQL = "SELECT type, transno, trandate, debtortrans.debtorno, reference, debtortrans.rate, ovamount+ovgst+ovfreight+ovdiscount as totalamt, custallocns.amt
					FROM debtortrans INNER JOIN custallocns ";
			
			if ($_POST['TransType'] == 12 OR $_POST['TransType'] == 11) {
				$TitleInfo = ($_POST['TransType'] == 12) ? __('Receipt') : __('Credit Note');
				if ($MyRow['totamt'] < 0) {
					$SQL .= "ON debtortrans.id = custallocns.transid_allocto WHERE custallocns.transid_allocfrom = '" . $AllocToID . "'";
				} else {
					$SQL .= "ON debtortrans.id = custallocns.transid_allocfrom WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
				}
			} else {
				$TitleInfo = __('invoice');
				$SQL .= "ON debtortrans.id = custallocns.transid_allocfrom WHERE custallocns.transid_allocto = '" . $AllocToID . "'";
			}
			$SQL .= " ORDER BY transno ";

			$TransResult = DB_query($SQL);

			if (DB_num_rows($TransResult) == 0) {
				echo '<div class="db-card" style="border-radius: 20px; text-align: center; padding: 100px 40px; border: 1px solid #e5e7eb;">
						<i class="fas fa-info-circle fa-3x" style="color: #a7f3d0; margin-bottom: 25px;"></i>
						<h4 style="font-weight: 900; color: #064e3b;">' . __('No Allocations Detected') . '</h4>
						<p style="color: #059669; opacity: 0.7;">' . __('This transaction has not yet been linked to any settlement items.') . '</p>
					</div>';
			} else {
				$Printer = true;
				echo '<div class="db-card" style="overflow: hidden; border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
						<div class="db-card-header">
							<h3 class="db-card-title">
								<i class="fas fa-link" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Consolidated Registry') . '
							</h3>
							<div style="background: #ecfdf5; color: #059669; padding: 8px 18px; border-radius: 12px; font-weight: 800; font-size: 0.75rem; border: 1px solid #d1fae5; text-transform: uppercase; letter-spacing: 0.5px;">' . mb_strtoupper($TitleInfo) . ' #' . $_POST['TransNo'] . '</div>
						</div>
						<div class="db-table-wrapper" style="padding: 0;">
							<table class="db-table">
								<thead>
									<tr style="height: 60px;">
										<th style="padding-left: 35px; background: #f9fafb;">' . __('Date') . '</th>
										<th style="background: #f9fafb;">' . __('Type') . '</th>
										<th style="background: #f9fafb;">' . __('Identity') . '</th>
										<th style="background: #f9fafb;">' . __('Ref') . '</th>
										<th class="text-right" style="background: #f9fafb;">' . __('Value') . '</th>
										<th class="text-right" style="padding-right: 35px; background: #f9fafb;">' . __('Allocated') . '</th>
									</tr>
								</thead>
								<tbody>';

				$AllocsTotal = 0;
				while ($TransRow = DB_fetch_array($TransResult)) {
					$TypeName = ($TransRow['type'] == 11) ? __('Credit Memo') : (($TransRow['type'] == 10) ? __('Sales Invoice') : __('Receipt'));
					echo '<tr style="height: 65px;">
							<td style="padding-left: 35px;">' . ConvertSQLDate($TransRow['trandate']) . '</td>
							<td><span style="background: #f3f4f6; color: #4b5563; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800;">' . $TypeName . '</span></td>
							<td style="font-weight: 800; color: #059669;">#' . $TransRow['transno'] . '</td>
							<td style="color: #6b7280; font-size: 0.85rem;">' . (empty($TransRow['reference']) ? '-' : $TransRow['reference']) . '</td>
							<td class="text-right" style="font-weight: 600;">' . locale_number_format($TransRow['totalamt'], $CurrDecimalPlaces) . '</td>
							<td class="text-right" style="color: #059669; padding-right: 35px; font-weight: 900;">' . locale_number_format($TransRow['amt'], $CurrDecimalPlaces) . '</td>
						</tr>';
					$AllocsTotal += $TransRow['amt'];
				}
				echo '			</tbody>
								<tfoot style="background: #f9fafb; border-top: 2px solid #f1f5f9;">
									<tr style="height: 100px;">
										<td colspan="5" class="text-right" style="text-transform: uppercase; font-size: 0.8rem; font-weight: 950; color: #059669; opacity: 0.6; padding-right: 30px;">' . __('Consolidated Allocation') . '</td>
										<td class="text-right" style="font-weight: 950; color: #059669; font-size: 1.8rem; padding-right: 35px;">' . locale_number_format($AllocsTotal, $CurrDecimalPlaces) . ' <small style="font-size: 0.5em; opacity: 0.4;">' . $CurrCode . '</small></td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>';
			}
		}
	}
	
	if ($Rows > 1) {
		echo '<div style="background: #064e3b; border-radius: 24px; padding: 40px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 20px 25px -5px rgba(5, 150, 105, 0.15);">
				<div>
					<div style="color: #34d399; font-weight: 900; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 2px;">' . __('Total Fiscal Impact') . '</div>
					<div style="color: #ffffff; font-size: 3rem; font-weight: 950; letter-spacing: -3px;">' . locale_number_format($GrandTotal, $CurrDecimalPlaces) . ' <span style="font-size: 1rem; opacity: 0.3;">' . $CurrCode . '</span></div>
				</div>
				<i class="fas fa-coins fa-3x" style="color: white; opacity: 0.2;"></i>
			</div>';
	}
	
	if ($_POST['TransType'] == 12) {
		$SQL = "SELECT account, amount FROM gltrans LEFT JOIN bankaccounts ON account=accountcode WHERE type=12 AND typeno='" . (int)$_POST['TransNo'] . "' AND account !='" . $_SESSION['CompanyRecord']['debtorsact'] . "' AND accountcode IS NULL";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) > 0) {
			echo '<div class="db-card" style="border-radius: 20px; overflow: hidden; border: 1px solid #e5e7eb;">
					<div class="db-card-header">
						<h3 class="db-card-title"><i class="fas fa-calculator" style="font-size: 0.9rem; opacity: 0.7;"></i> ' . __('GL Reconciliation') . '</h3>
					</div>
					<div style="padding: 10px 35px 50px;">';
			while ($ChargesRow = DB_fetch_array($Result)) {
				echo '<div style="display:flex; justify-content:space-between; align-items: center; padding: 25px 0; border-bottom: 1px solid #f1f5f9;">
						<div style="display: flex; align-items: center; gap: 20px;">
							<div style="background: #ecfdf5; color: #059669; width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center;"><i class="fas fa-receipt"></i></div>
							<div style="font-size: 1.1rem; color: #059669; font-weight: 600;">' . __('Account') . ': <strong style="color: #064e3b; font-weight: 950;">' . $ChargesRow['account'] . '</strong></div>
						</div>
						<div style="text-align: right;">
							<div style="font-size: 1.5rem; font-weight: 950; color: #064e3b;">' . locale_number_format($ChargesRow['amount'], $CurrDecimalPlaces) . '</div>
							<div style="font-size: 0.8rem; font-weight: 750; color: #059669;">' . locale_number_format($ChargesRow['amount'] * $Rate, $CurrDecimalPlaces) . ' @ ' . $Rate . '</div>
						</div>
					</div>';
				$GrandTotal += $ChargesRow['amount'] * $Rate;
			}
			echo '		<div style="margin-top: 50px; text-align:right;">
							<span style="display: block; font-weight: 900; font-size: 0.8rem; text-transform: uppercase; color: #059669; opacity: 0.5; margin-bottom: 5px;">' . __('Total Fiscal Recon') . '</span>
							<span style="font-size: 4rem; font-weight: 950; color: #059669; letter-spacing: -5px; line-height: 1;">' . locale_number_format($GrandTotal, $CurrDecimalPlaces) . '</span>
						</div>
					</div>
				</div>';
		}
	}
}

if (isset($Printer)) {
	echo '<div class="noPrint" style="margin-top: 80px; display: flex; justify-content: center; padding-bottom: 200px;">
			<button onclick="window.print()" type="button" style="padding: 24px 80px; border-radius: 50px; box-shadow: 0 20px 40px -12px rgba(5,150,105,0.25); font-weight: 700; background: #059669; color: white; border: none; cursor: pointer; display: flex; align-items: center; gap: 20px;">
				<i class="fas fa-print" style="color: #ffffff; opacity: 0.8;"></i> ' . __('Generate Audit Report') . '
			</button>
		</div>';
}

echo '	</main>
	</div>'; // End db-bottom-layout
echo '</form>';

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
