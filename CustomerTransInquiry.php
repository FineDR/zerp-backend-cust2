<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Customer Transactions Inquiry');
$ViewTopic = 'ARInquiries';
$BookMark = 'ARTransInquiry';

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
	
	.badge { padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
	.badge-invoice { background: #ecfdf5; color: #047857; }
	.badge-credit { background: #fef2f2; color: #b91c1c; }
	.badge-receipt { background: #eff6ff; color: #1d4ed8; }

	@media (max-width: 1100px) {
		.custom-bottom-layout { display: flex; flex-direction: column; }
		.db-sidebar { width: 100%; }
	}
</style>';

include(__DIR__ . '/includes/header.php');

if (!isset($_POST['FromDate'])) {
	$_POST['FromDate'] = date($_SESSION['DefaultDateFormat'], mktime(0, 0, 0, date('m'), 1, date('Y')));
}
if (!isset($_POST['ToDate'])) {
	$_POST['ToDate'] = date($_SESSION['DefaultDateFormat']);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('transaction inquiry') . '</span>
					</div>
					<div>
						<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
						<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Analyze customer billing and payment cycles within specific date ranges') . '</p>
					</div>
				</div>
			</div>
		</div>';

echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-search" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Search Parameters') . '
						</h3>
					</div>
					<div style="padding: 24px; background: #fff;">
						<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" style="display: flex; flex-direction: column; gap: 20px;">
							<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
							
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Transaction Type') . '</label>
								<select name="TransType" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">';
$SQL = "SELECT typeid, typename FROM systypes WHERE typeid >= 10 AND typeid <= 14";
$ResultTypes = DB_query($SQL);
echo '<option value="All">' . __('All Types') . '</option>';
while ($MyRow = DB_fetch_array($ResultTypes)) {
	$selected = (isset($_POST['TransType']) && $MyRow['typeid'] == $_POST['TransType']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['typeid'] . '">' . __($MyRow['typename']) . '</option>';
}
echo '						</select>
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('From Date') . '</label>
								<input type="date" name="FromDate" required="required" class="db-input" value="' . FormatDateForSQL($_POST['FromDate']) . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('To Date') . '</label>
								<input type="date" name="ToDate" required="required" class="db-input" value="' . FormatDateForSQL($_POST['ToDate']) . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
							</div>

							<button type="submit" name="ShowResults" class="architect-btn" style="margin-top: 10px;">
								<i class="fas fa-sync-alt"></i> ' . __('Search Transactions') . '
							</button>
						</form>
					</div>
				</div>

				<div style="background: #f0fdf4; border: 1px solid #bbf7d0; padding: 16px 20px; border-radius: 20px; display: flex; align-items: flex-start; gap: 12px; margin-top: 24px;">
					<i class="fas fa-info-circle" style="color: #059669; font-size: 1.2rem; margin-top: 2px;"></i>
					<div style="font-size: 0.85rem; color: #047857; opacity: 0.9; line-height: 1.5;">
						' . __('Filter results by invoice, credit note, or receipt type to cross-reference customer account activity.') . '
					</div>
				</div>
			</aside>

			<main class="db-main">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; background: #fff;">';

if (isset($_POST['ShowResults']) && $_POST['TransType'] != '') {
	$SQL_FromDate = FormatDateForSQL($_POST['FromDate']);
	$SQL_ToDate = FormatDateForSQL($_POST['ToDate']);
	$SQL = "SELECT transno, trandate, debtortrans.debtorno, branchcode, reference, invtext, order_, debtortrans.rate, 
					ovamount+ovgst+ovfreight+ovdiscount as totalamt, currcode, typename, decimalplaces AS currdecimalplaces, type
				FROM debtortrans
				INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno
				INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev
				INNER JOIN systypes ON debtortrans.type = systypes.typeid
				WHERE trandate >='" . $SQL_FromDate . "' AND trandate <= '" . $SQL_ToDate . "'";
	if ($_POST['TransType'] != 'All') {
		$SQL .= " AND type = '" . $_POST['TransType'] . "'";
	}
	$SQL .= " ORDER BY id";
	$TransResult = DB_query($SQL);

	if (DB_num_rows($TransResult) > 0) {
		echo '<div style="overflow-x: auto;">
					<table class="registry-table">
						<thead>
							<tr>
								<th>' . __('Type') . '</th>
								<th>' . __('Number') . '</th>
								<th>' . __('Date') . '</th>
								<th>' . __('Customer') . '</th>
								<th>' . __('Ref/Order') . '</th>
								<th style="text-align: right;">' . __('Ex Rate') . '</th>
								<th style="text-align: right;">' . __('Total Amount') . '</th>
								<th>' . __('Action') . '</th>
							</tr>
						</thead>
						<tbody>';
		while ($MyRow = DB_fetch_array($TransResult)) {
			$badgeClass = 'badge-receipt';
			if ($MyRow['type'] == 10)
				$badgeClass = 'badge-invoice';
			if ($MyRow['type'] == 11)
				$badgeClass = 'badge-credit';

			echo '<tr>
						<td><span class="badge ' . $badgeClass . '">' . __($MyRow['typename']) . '</span></td>
						<td style="font-weight: 700; color: #064e3b;">' . $MyRow['transno'] . '</td>
						<td style="white-space: nowrap;">' . ConvertSQLDate($MyRow['trandate']) . '</td>
						<td style="font-weight: 600;">' . $MyRow['debtorno'] . ' <span style="opacity: 0.5; font-weight: 400;">[' . $MyRow['branchcode'] . ']</span></td>
						<td>
							<div style="font-weight: 600;">' . $MyRow['reference'] . '</div>
							<div style="font-size: 0.75rem; opacity: 0.6;">' . __('Order') . ': ' . $MyRow['order_'] . '</div>
						</td>
						<td style="text-align: right; font-family: monospace;">' . locale_number_format($MyRow['rate'], 4) . '</td>
						<td style="text-align: right; font-weight: 800; color: #064e3b;">' . $MyRow['currcode'] . ' ' . locale_number_format($MyRow['totalamt'], $MyRow['currdecimalplaces']) . '</td>
						<td>';
			if ($MyRow['type'] == 10 || $MyRow['type'] == 11) {
				$typeParam = ($MyRow['type'] == 10) ? 'Invoice' : 'Credit';
				echo '<a target="_blank" href="' . $RootPath . '/PrintCustTrans.php?FromTransNo=' . $MyRow['transno'] . '&InvOrCredit=' . $typeParam . '" 
							style="color: #059669; text-decoration: none; display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 0.8rem;">
							<i class="fas fa-print"></i> ' . __('Preview') . '
						  </a>';
			} else {
				echo '<span style="opacity: 0.3;"><i class="fas fa-minus"></i></span>';
			}
			echo '</td>
					</tr>';
		}
		echo '</tbody></table></div>';
	} else {
		echo '<div style="padding: 60px 30px; text-align: center;">
					<i class="fas fa-folder-open" style="font-size: 3rem; color: #e5e7eb; margin-bottom: 20px;"></i>
					<h3 style="margin: 0; color: #374151;">' . __('No records found') . '</h3>
					<p style="margin: 8px 0 0; color: #6b7280;">' . __('No transactions matched your search criteria for the selected period.') . '</p>
				  </div>';
	}
} else {
	echo '<div style="padding: 100px 30px; text-align: center; background: #fff;">
				<div style="width: 80px; height: 80px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
					<i class="fas fa-search" style="font-size: 2rem; color: #059669;"></i>
				</div>
				<h3 style="margin: 0; color: #374151; font-weight: 800; font-size: 1.25rem;">' . __('Ready to Search') . '</h3>
				<p style="margin: 12px auto; color: #6b7280; max-width: 400px; line-height: 1.6;">
					' . __('Select a transaction type and date range in the sidebar then click search to display the customer transaction registry.') . '
				</p>
			</div>';
}

echo '</div>
			</main>
		</div>
	</div>';

include(__DIR__ . '/includes/footer.php');
