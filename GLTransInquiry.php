<?php

require(__DIR__ . '/includes/session.php');

$Title = __('General Ledger Transaction Inquiry');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLTransInquiry';

$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { 
		margin-bottom: 40px; padding: 40px 50px; background: #ffffff; border-radius: 24px;
		border: 1px solid #e5e7eb; box-shadow: var(--shadow-sm); display: flex; justify-content: space-between; align-items: center;
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; font-size: 0.72rem; font-weight: 700; text-transform: lowercase; letter-spacing: 1px; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	.card-v2 { background: #ffffff; border-radius: 24px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 24px; }
	.card-header-v2 { background: #f9fafb; border-bottom: 1px solid #f3f4f6; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; }
	.card-header-v2 h3 { font-size: 0.95rem; font-weight: 850; color: #064e3b; margin: 0; text-transform: uppercase; letter-spacing: 1.5px; }

	.synopsis-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; padding: 30px; }
	.synopsis-item { display: flex; flex-direction: column; gap: 8px; }
	.synopsis-label { font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
	.synopsis-value { font-size: 1.1rem; font-weight: 700; color: #1e293b; }
	
	.db-table { width: 100%; border-collapse: collapse; }
	.db-table th { background: #f8fafc; padding: 16px 24px; text-align: left; font-size: 0.72rem; font-weight: 900; color: #64748b; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; }
	.db-table td { padding: 16px 24px; font-size: 0.88rem; color: #334155; border-bottom: 1px solid #f1f5f9; }
	.db-table tr:last-child td { border-bottom: none; }
	.db-table tr.striped:nth-child(even) { background: #f9fafb; }
	
	.badge { padding: 6px 12px; border-radius: 999px; font-size: 0.7rem; font-weight: 850; text-transform: uppercase; letter-spacing: 0.5px; }
	.badge-info { background: #e0f2fe; color: #075985; }
	.badge-success { background: #dcfce7; color: #166534; }
	
	.db-ref { font-family: "JetBrains Mono", monospace; font-weight: 700; color: #059669; background: #ecfdf5; padding: 2px 6px; border-radius: 4px; }
</style>';

include(__DIR__ . '/includes/header.php');

if (!isset($_GET['TypeID']) or !isset($_GET['TransNo'])) {
	echo '<div class="db-page"><div class="premium-header"><div><h1>' . __('Missing Parameters') . '</h1></div></div></div>';
} else {
	// Fetch Sub-Ledger Party Details for traceability
	$OtherParty = '';
	$OtherPartyCode = '';
	$Allocations = array();
	
	if (in_array($_GET['TypeID'], array(10, 11, 12, 15))) { // Customer Transactions
		$PartySQL = "SELECT debtortrans.debtorno, debtorsmaster.name 
					 FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno = debtorsmaster.debtorno 
					 WHERE type = '" . $_GET['TypeID'] . "' AND transno = '" . $_GET['TransNo'] . "'";
		$PartyRes = DB_query($PartySQL);
		if ($PartyRow = DB_fetch_array($PartyRes)) {
			$OtherParty = $PartyRow['name'];
			$OtherPartyCode = $PartyRow['debtorno'];
		}
		
		if ($_GET['TypeID'] == 12) { // Receipts: Fetch Allocations
			$AllocSQL = "SELECT custallocns.amt, debtortrans.transno AS transno_to, debtortrans.type AS type_to 
						 FROM custallocns 
						 INNER JOIN debtortrans ON custallocns.transid_allocto = debtortrans.id
						 WHERE transid_allocfrom = (SELECT id FROM debtortrans WHERE type=12 AND transno='" . $_GET['TransNo'] . "' LIMIT 1)";
			$AllocRes = DB_query($AllocSQL);
			while ($AllocRow = DB_fetch_array($AllocRes)) {
				$Allocations[] = array('type' => $AllocRow['type_to'], 'no' => $AllocRow['transno_to'], 'amount' => $AllocRow['amt']);
			}
		}
	} elseif (in_array($_GET['TypeID'], array(20, 21, 22))) { // Supplier Transactions
		$PartySQL = "SELECT supptrans.supplierno, suppliers.suppname 
					 FROM supptrans INNER JOIN suppliers ON supptrans.supplierno = suppliers.supplierid 
					 WHERE type = '" . $_GET['TypeID'] . "' AND transno = '" . $_GET['TransNo'] . "'";
		$PartyRes = DB_query($PartySQL);
		if ($PartyRow = DB_fetch_array($PartyRes)) {
			$OtherParty = $PartyRow['suppname'];
			$OtherPartyCode = $PartyRow['supplierno'];
		}
	}

	$TypeSQL = "SELECT typename FROM systypes WHERE typeid = '" . $_GET['TypeID'] . "'";
	$TypeResult = DB_query($TypeSQL);
	$MyRow = DB_fetch_row($TypeResult);
	$TransName = $MyRow[0];

	echo '<div class="db-page">
			<div class="premium-header">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=GL" class="breadcrumb-item">' . __('ledger') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('transaction inquiry') . '</span>
					</div>
					<div>
						<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . __($TransName) . ' <span style="opacity: 0.4;">#' . $_GET['TransNo'] . '</span></h1>
						<p style="font-size: 1.1rem; margin-top: 12px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Detailed ledger perspective for this event') . '</p>
					</div>
				</div>
			</div>

			<div class="card-v2">
				<div class="card-header-v2">
					<h3>' . __('Transaction Synopsis') . '</h3>
					<span class="badge badge-info">' . __('Status: Verified') . '</span>
				</div>
				<div class="synopsis-grid">
					<div class="synopsis-item">
						<span class="synopsis-label">' . __('Other Party') . '</span>
						<span class="synopsis-value">' . ($OtherParty ? htmlspecialchars($OtherParty) . ' <span class="db-ref" style="font-size: 0.8rem;">#' . $OtherPartyCode . '</span>' : '<span style="opacity: 0.4;">' . __('Internal / Journal') . '</span>') . '</span>
					</div>';
	
	if (!empty($Allocations)) {
		echo '<div class="synopsis-item">
				<span class="synopsis-label">' . __('Applied Invoices') . '</span>
				<div style="display: flex; gap: 8px; flex-wrap: wrap;">';
		foreach ($Allocations as $alloc) {
			echo '<span class="badge badge-success" style="font-size: 0.65rem;">' . __('Inv') . ' #' . $alloc['no'] . ' (' . locale_number_format($alloc['amount'], $_SESSION['CompanyRecord']['decimalplaces']) . ')</span>';
		}
		echo '	</div>
			  </div>';
	}

	echo '		<div class="synopsis-item">
						<span class="synopsis-label">' . __('Transaction Date') . '</span>
						<span class="synopsis-value" id="TransHeaderDate">--</span>
					</div>
				</div>
			</div>

			<div class="card-v2">
				<div class="card-header-v2">
					<h3>' . __('General Ledger Postings') . '</h3>
				</div>
				<div style="overflow-x: auto;">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Period') . '</th>
								<th>' . __('Date') . '</th>
								<th>' . __('GL Account') . '</th>
								<th>' . __('Narrative') . '</th>
								<th style="text-align: right;">' . __('Debits') . '</th>
								<th style="text-align: right;">' . __('Credits') . '</th>
							</tr>
						</thead>
						<tbody>';

	$SQL = "SELECT gltrans.periodno, gltrans.trandate, gltrans.type, gltrans.account, chartmaster.accountname, gltrans.narrative, gltrans.amount, periods.lastdate_in_period
			FROM gltrans INNER JOIN chartmaster ON gltrans.account = chartmaster.accountcode
			INNER JOIN periods ON periods.periodno=gltrans.periodno
			WHERE gltrans.type= '" . $_GET['TypeID'] . "' AND gltrans.typeno = '" . $_GET['TransNo'] . "'
			ORDER BY gltrans.counterindex";
	$TransResult = DB_query($SQL);

	$CreditTotal = 0;
	$DebitTotal = 0;
	$FirstDate = '';

	while ($TransRow = DB_fetch_array($TransResult)) {
		$TranDate = ConvertSQLDate($TransRow['trandate']);
		if (empty($FirstDate)) $FirstDate = $TranDate;

		if ($TransRow['amount'] > 0) {
			$DebitAmount = locale_number_format($TransRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
			$DebitTotal += $TransRow['amount'];
			$CreditAmount = '&nbsp;';
		} else {
			$CreditAmount = locale_number_format(-$TransRow['amount'], $_SESSION['CompanyRecord']['decimalplaces']);
			$CreditTotal += (-$TransRow['amount']);
			$DebitAmount = '&nbsp;';
		}

		echo '<tr class="striped">
				<td style="font-size: 0.75rem; font-weight: 700; color: #64748b;">' . MonthAndYearFromSQLDate($TransRow['lastdate_in_period']) . '</td>
				<td>' . $TranDate . '</td>
				<td>
					<div style="font-weight: 700; color: #1e293b;">' . $TransRow['accountname'] . '</div>
					<div style="font-size: 0.72rem; opacity: 0.6; font-family: monospace;">' . $TransRow['account'] . '</div>
				</td>
				<td style="font-style: italic; color: #64748b; font-size: 0.8rem;">' . (mb_strlen($TransRow['narrative']) > 0 ? $TransRow['narrative'] : '&nbsp;') . '</td>
				<td style="text-align: right; font-weight: 700; color: #059669;">' . $DebitAmount . '</td>
				<td style="text-align: right; font-weight: 700; color: #dc2626;">' . $CreditAmount . '</td>
			</tr>';
	}

	echo '				</tbody>
						<tfoot style="background: #f8fafc;">
							<tr>
								<td colspan="4" style="text-align: right; font-weight: 800; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;">' . __('Total Transaction Value') . '</td>
								<td style="text-align: right; font-weight: 900; color: #059669; font-size: 1.1rem; border-top: 2px solid #e2e8f0;">' . locale_number_format($DebitTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
								<td style="text-align: right; font-weight: 900; color: #dc2626; font-size: 1.1rem; border-top: 2px solid #e2e8f0;">' . locale_number_format($CreditTotal, $_SESSION['CompanyRecord']['decimalplaces']) . '</td>
							</tr>
						</tfoot>
					</table>
				</div>
			</div>
		  </div>';

	echo '<script>document.getElementById("TransHeaderDate").innerText = "' . $FirstDate . '";</script>';
}

include(__DIR__ . '/includes/footer.php');
