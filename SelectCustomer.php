<?php

/* Selection of customer - from where all customer related maintenance, transactions and inquiries start */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Customers');
$ViewTopic = 'AccountsReceivable';
$BookMark = 'SelectCustomer';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 32px; position: relative; }
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 12px 24px; border-radius: 12px;
		background: #059669; color: #ffffff !important; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn.secondary { background: #f1f5f9; color: #475569 !important; box-shadow: none; }
	.architect-btn.secondary:hover { background: #e2e8f0; color: #1e293b !important; }

	.custom-bottom-layout { display: grid; grid-template-columns: 380px 1fr; gap: 32px; align-items: start; }
	.db-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 24px; }
	.db-card-header { background: #f9fafb; border-bottom: 1px solid #f3f4f6; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; }
	.db-card-title { font-size: 0.9rem; font-weight: 850; color: #064e3b; margin: 0; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1px; }
	.db-card-body { padding: 24px; }

	.registry-table { width: 100%; border-collapse: separate; border-spacing: 0; }
	.registry-table th { background: #f9fafb; padding: 16px 20px; text-align: left; font-size: 0.72rem; text-transform: uppercase; font-weight: 900; color: #064e3b; letter-spacing: 1.2px; border-bottom: 1px solid #f3f4f6; }
	.registry-table td { padding: 16px 20px; font-size: 0.88rem; color: #374151; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
	.registry-table tr:hover td { background: #f0fdf4; }

	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: #64748b; text-decoration: none; font-size: 0.85rem; font-weight: 500; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	.db-input { width: 100%; border-radius: 12px; height: 44px; font-weight: 600; border: 1px solid #e2e8f0; padding: 0 16px; box-sizing: border-box; background: #fff; transition: all 0.2s; font-size: 0.9rem; margin-bottom: 12px; }
	.db-label { font-size: 0.7rem; text-transform: uppercase; font-weight: 850; letter-spacing: 1px; color: #64748b; display: block; margin-bottom: 6px; }

	.db-tab-bar { display: flex; gap: 8px; margin-bottom: 24px; background: #f1f5f9; padding: 6px; border-radius: 16px; border: 1px solid #e2e8f0; }
	.db-tab { padding: 10px 20px; border-radius: 12px; font-size: 0.8rem; font-weight: 700; color: #64748b; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
	.db-tab:hover { background: #fff; color: #059669; }
	.db-tab.active { background: #fff; color: #059669; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
	.db-tab-panel { display: none; }
	.db-tab-panel.active { display: block; animation: slideIn 0.3s ease-out; }

	.action-tile { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 16px; border-radius: 16px; background: #fff; border: 1px solid #e2e8f0; transition: all 0.3s; text-decoration: none; gap: 12px; }
	.action-tile:hover { transform: translateY(-3px); border-color: #059669; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.1); }
	.action-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
	.action-icon.green { background: #f0fdf4; color: #059669; }
	.action-icon.blue { background: #eff6ff; color: #2563eb; }
	.action-icon.neutral { background: #f8fafc; color: #64748b; }
	.action-text { font-size: 0.8rem; font-weight: 700; color: #1e293b; text-align: center; }

	@keyframes slideIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
	@media (max-width: 1024px) { .custom-bottom-layout { grid-template-columns: 1fr; } .db-tab-bar { overflow-x: auto; white-space: nowrap; } }
</style>
<script>
	function switchTab(tabId) {
		document.querySelectorAll(".db-tab").forEach(t => t.classList.remove("active"));
		document.querySelectorAll(".db-tab-panel").forEach(p => p.classList.remove("active"));
		document.querySelector(`[onclick=\"switchTab(\'${tabId}\')\"]`).classList.add("active");
		document.getElementById(tabId).classList.add("active");
	}
</script>';

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
				<div style="display: flex; align-items: center; gap: 8px;">
					<a href="' . $RootPath . '/index.php" class="breadcrumb-item">' . __('home') . '</a>
					<span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
					<a href="' . $RootPath . '/index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
					<span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
					<span class="breadcrumb-item" style="color: #064e3b; font-weight: 700;">' . __('select customer') . '</span>
				</div>
				<a href="' . $RootPath . '/Customers.php" class="architect-btn">
					<i class="fas fa-user-plus"></i> ' . __('Add New Customer') . '
				</a>
			</div>
			<h1 style="font-size: 2rem; font-weight: 900; color: #064e3b; margin-bottom: 4px; letter-spacing: -1px;">' . $Title . '</h1>
			<p style="color: #64748b; font-size: 0.95rem; font-weight: 500;">' . __('Identify and manage customer accounts from a centralized dashboard.') . '</p>
		</div>';

if (isset($_GET['Select'])) {
	$_SESSION['CustomerID'] = $_GET['Select'];
}
if (!isset($_SESSION['CustomerID'])) {
	$_SESSION['CustomerID'] = '';
}
if (isset($_GET['Area'])) {
	$_POST['Area'] = $_GET['Area'];
	$_POST['Search'] = 'Search';
	$_POST['Keywords'] = '';
	$_POST['CustCode'] = '';
	$_POST['CustPhone'] = '';
	$_POST['CustAdd'] = '';
	$_POST['CustType'] = '';
}
if (!isset($_SESSION['CustomerType'])) {
	$_SESSION['CustomerType'] = '';
}
if (isset($_POST['JustSelectedACustomer'])) {
	if (isset($_POST['SubmitCustomerSelection'])) {
		foreach ($_POST['SubmitCustomerSelection'] as $CustomerID => $BranchCode)
			$_SESSION['CustomerID'] = $CustomerID;
		$_SESSION['BranchCode'] = $BranchCode;
	} elseif (!isset($_POST['Search'])) {
		prnMsg(__('Unable to identify the selected customer'), 'error');
	}
}

$Msg = '';

if (isset($_POST['Go1']) or isset($_POST['Go2'])) {
	$_POST['PageOffset'] = (isset($_POST['Go1']) ? $_POST['PageOffset1'] : $_POST['PageOffset2']);
	$_POST['Go'] = '';
}
if (!isset($_POST['PageOffset'])) {
	$_POST['PageOffset'] = 1;
} else {
	if ($_POST['PageOffset'] == 0) {
		$_POST['PageOffset'] = 1;
	}

}

if (isset($_POST['Search']) or isset($_POST['CSV']) or isset($_POST['Go']) or isset($_POST['Next']) or isset($_POST['Previous'])) {
	unset($_POST['JustSelectedACustomer']);
	if (isset($_POST['Search'])) {
		$_POST['PageOffset'] = 1;
	}
	$SQL = "SELECT debtorsmaster.debtorno,
				debtorsmaster.name,
				debtorsmaster.address1,
				debtorsmaster.address2,
				debtorsmaster.address3,
				debtorsmaster.address4,
				custbranch.branchcode,
				custbranch.brname,
				custbranch.contactname,
				debtortype.typename,
				custbranch.phoneno,
				custbranch.faxno,
				custbranch.email
			FROM debtorsmaster
			LEFT JOIN custbranch
				ON debtorsmaster.debtorno = custbranch.debtorno
			INNER JOIN debtortype
				ON debtorsmaster.typeid = debtortype.typeid";
	if (isset($_POST['SmartSearch']) && mb_strlen($_POST['SmartSearch']) > 0) {
		$SearchKeywords = mb_strtoupper(trim(str_replace(' ', '%', $_POST['SmartSearch'])));
		$SQL .= " WHERE (debtorsmaster.name " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.debtorno " . LIKE . " '%" . $SearchKeywords . "%'
						OR custbranch.phoneno " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address1 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address2 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address3 " . LIKE . " '%" . $SearchKeywords . "%'
						OR debtorsmaster.address4 " . LIKE . " '%" . $SearchKeywords . "%')";

		if (isset($_POST['CustType']) && $_POST['CustType'] != 'ALL') {
			$SQL .= " AND debtortype.typename = '" . $_POST['CustType'] . "'";
		}
		if (isset($_POST['Area']) && $_POST['Area'] != 'ALL') {
			$SQL .= " AND custbranch.area = '" . $_POST['Area'] . "'";
		}
	} else {
		$SQL .= " WHERE debtorsmaster.name " . LIKE . " '%" . mb_strtoupper($_POST['Keywords']) . "%'
				AND debtorsmaster.debtorno " . LIKE . " '%" . mb_strtoupper($_POST['CustCode']) . "%'
				AND (custbranch.phoneno " . LIKE . " '%" . $_POST['CustPhone'] . "%' OR custbranch.phoneno IS NULL)
				AND (debtorsmaster.address1 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address2 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address3 " . LIKE . " '%" . $_POST['CustAdd'] . "%'
					OR debtorsmaster.address4 " . LIKE . " '%" . $_POST['CustAdd'] . "%')";

		if ($_POST['CustType'] != 'ALL') {
			$SQL .= " AND debtortype.typename = '" . $_POST['CustType'] . "'";
		}
		if ($_POST['Area'] != 'ALL') {
			$SQL .= " AND custbranch.area = '" . $_POST['Area'] . "'";
		}
	}

	if (isset($_SESSION['SalesmanLogin']) and $_SESSION['SalesmanLogin'] != '') {
		$SQL .= " AND custbranch.salesman='" . $_SESSION['SalesmanLogin'] . "'";
	}

	$SQL .= " ORDER BY debtorsmaster.name";

	$SearchResult = DB_query($SQL);
	if (DB_num_rows($SearchResult) == 0) {
		prnMsg(__('No customers were identified matching the search criteria'), 'warn');
	}
}

echo '<div class="custom-bottom-layout">
		<aside class="db-sidebar">
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title">
						<i class="fas fa-search"></i> ' . __('Find Customer') . '
					</h3>
				</div>
				<div class="db-card-body">
					<form action="', htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8'), '" method="post">
						<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
						
						<div class="db-field">
							<label class="db-label">' . __('Smart Search') . '</label>
							<input type="text" name="SmartSearch" class="db-input" placeholder="' . __('Name, Code, Phone...') . '" ', (isset($_POST['SmartSearch']) ? 'value="' . $_POST['SmartSearch'] . '"' : ''), ' autofocus />
						</div>

						<details id="advanced-filters" style="margin-top: 16px; border-radius: 12px; border: 1px solid #f1f5f9; background: #f8fafc; overflow: hidden;">
							<summary style="font-size: 0.72rem; font-weight: 850; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background: #fff; border-bottom: 1px solid #f1f5f9; text-transform: uppercase; letter-spacing: 0.5px;">
								<span><i class="fas fa-sliders-h" style="margin-right: 8px;"></i> ' . __('Advanced Identification') . '</span>
								<i class="fas fa-chevron-down" style="font-size: 0.6rem; opacity: 0.5;"></i>
							</summary>
							<div style="padding: 16px; display: flex; flex-direction: column; gap: 4px;">
								<div class="db-field">
									<label class="db-label">' . __('Keywords') . '</label>
									<input type="text" name="Keywords" class="db-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="..." />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Account Code') . '</label>
									<input type="text" name="CustCode" class="db-input" value="' . (isset($_POST['CustCode']) ? $_POST['CustCode'] : '') . '" placeholder="..." />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Phone') . '</label>
									<input type="text" name="CustPhone" class="db-input" value="' . (isset($_POST['CustPhone']) ? $_POST['CustPhone'] : '') . '" placeholder="..." />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Address') . '</label>
									<input type="text" name="CustAdd" class="db-input" value="' . (isset($_POST['CustAdd']) ? $_POST['CustAdd'] : '') . '" placeholder="..." />
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Type') . '</label>';
$Result2 = DB_query("SELECT typeid, typename FROM debtortype ORDER BY typename");
echo '<select name="CustType" class="db-input">
			<option value="ALL">' . __('Any Type') . '</option>';
while ($MyRow = DB_fetch_array($Result2)) {
	$selected = (isset($_POST['CustType']) AND $_POST['CustType'] == $MyRow['typename']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['typename'] . '">' . $MyRow['typename'] . '</option>';
}
echo '</select>
								</div>
								<div class="db-field">
									<label class="db-label">' . __('Sales Area') . '</label>';
$Result2 = DB_query("SELECT areacode, areadescription FROM areas");
echo '<select name="Area" class="db-input">
			<option value="ALL">' . __('Any Area') . '</option>';
while ($MyRow = DB_fetch_array($Result2)) {
	$selected = (isset($_POST['Area']) AND $_POST['Area'] == $MyRow['areacode']) ? 'selected="selected"' : '';
	echo '<option ' . $selected . ' value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription'] . '</option>';
}
echo '</select>
								</div>
							</div>
						</details>

						<button type="submit" name="Search" class="architect-btn" style="width: 100%; margin-top: 24px;">
							<i class="fas fa-search-dollar"></i> ' . __('Search Now') . '
						</button>
					</form>
				</div>
			</div>
		</aside>

		<main class="db-main">';

if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '' and !isset($_POST['Search']) and !isset($_POST['CSV'])) {
	$SQL = "SELECT debtorsmaster.name,
					custbranch.brname,
					custbranch.phoneno
			FROM debtorsmaster
			INNER JOIN custbranch
				ON debtorsmaster.debtorno = custbranch.debtorno
			WHERE debtorsmaster.debtorno = '" . $_SESSION['CustomerID'] . "'
				AND custbranch.branchcode = '" . $_SESSION['BranchCode'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	$CustomerName = $MyRow['name'];
	$BranchName = $MyRow['brname'];
	$PhoneNo = $MyRow['phoneno'];

	echo '<div class="db-card" style="margin-bottom: 32px; border: 2px solid #bbf7d0; box-shadow: 0 10px 30px rgba(5, 150, 105, 0.05);">
			<div class="db-card-header" style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0; padding: 24px 30px;">
				<div style="display: flex; align-items: center; gap: 20px;">
					<div style="width: 56px; height: 56px; border-radius: 16px; background: #ffffff; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
						<i class="fas fa-user-check"></i>
					</div>
					<div>
						<h3 style="margin: 0; font-size: 1.25rem; font-weight: 900; color: #064e3b;">' . $CustomerName . '</h3>
						<div style="font-size: 0.85rem; color: #059669; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-top: 4px;">
							<span style="background: #059669; color: #fff; padding: 2px 8px; border-radius: 6px; font-size: 0.7rem;">#' . stripslashes($_SESSION['CustomerID']) . '</span>
							<i class="fas fa-circle" style="font-size: 0.4rem; opacity: 0.5;"></i>
							' . $BranchName . '
						</div>
					</div>
				</div>
				<span style="background: #064e3b; color: #fff; padding: 6px 14px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">' . __('Active Selection') . '</span>
			</div>
			
			<div class="db-card-body" style="padding: 30px;">
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px;">
					<a href="' . $RootPath . '/SelectOrderItems.php?NewOrder=Yes&SelectedCustomer=' . urlencode($_SESSION['CustomerID']) . '" class="action-tile">
						<div class="action-icon blue"><i class="fas fa-shopping-cart"></i></div>
						<span class="action-text">' . __('New Order') . '</span>
					</a>
					<a href="' . $RootPath . '/CustomerReceipt.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '&NewReceipt=Yes&Type=Customer" class="action-tile">
						<div class="action-icon green"><i class="fas fa-cash-register"></i></div>
						<span class="action-text">' . __('Receipt') . '</span>
					</a>
					<a href="' . $RootPath . '/CustomerInquiry.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="action-tile">
						<div class="action-icon neutral"><i class="fas fa-search-location"></i></div>
						<span class="action-text">' . __('Inquiry') . '</span>
					</a>
					<a href="' . $RootPath . '/Customers.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="action-tile">
						<div class="action-icon neutral"><i class="fas fa-user-edit"></i></div>
						<span class="action-text">' . __('Edit Profile') . '</span>
					</a>
					<a href="' . $RootPath . '/CustomerAccount.php?CustomerID=' . urlencode($_SESSION['CustomerID']) . '" class="action-tile">
						<div class="action-icon neutral"><i class="fas fa-file-invoice-dollar"></i></div>
						<span class="action-text">' . __('Statement') . '</span>
					</a>
					<a href="' . $RootPath . '/CounterSales.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '&amp;BranchNo=' . $_SESSION['BranchCode'] . '" class="action-tile">
						<div class="action-icon neutral" style="background: #fff1f2; color: #e11d48;"><i class="fas fa-store"></i></div>
						<span class="action-text">' . __('Counter Sales') . '</span>
					</a>
				</div>
			</div>
		</div>';
}

if (isset($SearchResult)) {
	$ListCount = DB_num_rows($SearchResult);
	$ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);

	if (!isset($_POST['CSV']) && $ListCount > 0) {
		if (isset($_POST['Next']) && $_POST['PageOffset'] < $ListPageMax)
			$_POST['PageOffset']++;
		if (isset($_POST['Previous']) && $_POST['PageOffset'] > 1)
			$_POST['PageOffset']--;

		echo '<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Search Results') . '</h3>
					<div style="font-size: 0.8rem; font-weight: 700; color: #64748b;">
						' . __('Page') . ' ' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . ' (' . $ListCount . ' ' . __('results') . ')
					</div>
				</div>
				<div style="overflow-x: auto;">
					<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post">
						<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
						<input type="hidden" name="PageOffset" value="' . $_POST['PageOffset'] . '" />
						<table class="registry-table">
							<thead>
								<tr>
									<th>' . __('Customer') . '</th>
									<th>' . __('Branch / Contact') . '</th>
									<th>' . __('Category') . '</th>
									<th>' . __('Phone') . '</th>
									<th style="text-align: right;">' . __('Action') . '</th>
								</tr>
							</thead>
							<tbody>';
		
		DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
		$RowIndex = 0;
		while (($MyRow = DB_fetch_array($SearchResult)) and ($RowIndex <> $_SESSION['DisplayRecordsMax'])) {
			echo '<tr>
					<td>
						<div style="display: flex; align-items: center; gap: 12px;">
							<div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; color: #059669; display: flex; align-items: center; justify-content: center; font-weight: 850; font-size: 0.75rem; flex-shrink: 0;">
								' . mb_substr($MyRow['name'] ?? 'C', 0, 1) . '
							</div>
							<div>
								<div style="font-weight: 700; color: #1e293b; font-size: 0.85rem;">' . htmlspecialchars($MyRow['name'], ENT_QUOTES, 'UTF-8') . '</div>
								<div style="font-size: 0.7rem; color: #64748b; font-weight: 600;">#' . $MyRow['debtorno'] . '</div>
							</div>
						</div>
					</td>
					<td>
						<div style="font-weight: 600; color: #334155; font-size: 0.85rem;">' . htmlspecialchars($MyRow['brname'], ENT_QUOTES, 'UTF-8') . '</div>
						<div style="font-size: 0.7rem; color: #64748b; opacity: 0.8;">' . htmlspecialchars($MyRow['contactname'], ENT_QUOTES, 'UTF-8') . '</div>
					</td>
					<td><span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">' . $MyRow['typename'] . '</span></td>
					<td style="font-weight: 600; color: #475569; font-size: 0.85rem;">' . $MyRow['phoneno'] . '</td>
					<td style="text-align: right;">
						<button type="submit" name="SubmitCustomerSelection[' . htmlspecialchars($MyRow['debtorno'], ENT_QUOTES, 'UTF-8') . ']" value="' . htmlspecialchars($MyRow['branchcode'], ENT_QUOTES, 'UTF-8') . '" style="background: #059669; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: all 0.2s;">
							' . __('Select Account') . '
						</button>
					</td>
				</tr>';
			$RowIndex++;
		}
		echo '		</tbody>
						</table>
						<input type="hidden" name="JustSelectedACustomer" value="Yes" />
					</form>
				</div>
				<div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
					<div style="display: flex; gap: 8px;">
						<form action="' . htmlspecialchars(basename(__FILE__), ENT_QUOTES, 'UTF-8') . '" method="post" style="display: flex; gap: 8px; align-items: center;">
							<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
							<input type="hidden" name="PageOffset" value="' . $_POST['PageOffset'] . '" />
							<button type="submit" name="Previous" class="architect-btn secondary" ' . ($_POST['PageOffset'] == 1 ? 'disabled' : '') . ' style="padding: 8px 16px; font-size: 0.75rem;">
								<i class="fas fa-arrow-left"></i> ' . __('Previous') . '
							</button>
							<button type="submit" name="Next" class="architect-btn secondary" ' . ($_POST['PageOffset'] == $ListPageMax ? 'disabled' : '') . ' style="padding: 8px 16px; font-size: 0.75rem;">
								' . __('Next') . ' <i class="fas fa-arrow-right"></i>
							</button>
						</form>
					</div>
					<div style="font-size: 0.75rem; color: #64748b; font-weight: 600;">' . __('Total Identification Success') . '</div>
				</div>
			</div>';
	}
}

// Geocode Mapping and Intelligence Hub Tabs
if (isset($_SESSION['CustomerID']) and $_SESSION['CustomerID'] != '') {
	echo '<!-- Intelligence Hub Tabs -->
		<div class="db-tab-bar" style="margin-top: 32px;">
			<div class="db-tab active" onclick="switchTab(\'tab-overview\')">
				<i class="fas fa-chart-pie"></i> ' . __('Account Overview') . '
			</div>
			<div class="db-tab" onclick="switchTab(\'tab-contacts\')">
				<i class="fas fa-address-book"></i> ' . __('Contact Registry') . '
			</div>
			<div class="db-tab" onclick="switchTab(\'tab-notes\')">
				<i class="fas fa-sticky-note"></i> ' . __('Customer Notes') . '
			</div>
		</div>

		<!-- Tab 1: Overview -->
		<div id="tab-overview" class="db-tab-panel active">';
	
	// KPI Row and Map Row
	if ($_SESSION['Extended_CustomerInfo'] == 1) {
		$SQL = "SELECT clientsince, lastpaid, lastpaiddate, currencies.decimalplaces, currencies.currency
				FROM debtorsmaster INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev
				WHERE debtorsmaster.debtorno ='" . $_SESSION['CustomerID'] . "'";
		$DataRes = DB_query($SQL);
		$MyRow = DB_fetch_array($DataRes);

		$SQL = "SELECT SUM(ovamount+ovgst) as total FROM debtortrans WHERE debtorno = '" . $_SESSION['CustomerID'] . "' AND type != 12";
		$TotalRes = DB_query($SQL);
		$TRow = DB_fetch_array($TotalRes);

		echo '
			<div class="db-card" style="margin-bottom: 24px;">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-chart-line"></i> ' . __('Financial Insights') . '</h3>
				</div>
				<div class="db-card-body" style="padding: 24px 30px;">
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 32px;">
						<div class="kpi-mini">
							<div style="font-size: 0.7rem; font-weight: 850; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">' . __('Lifetime Value') . '</div>
							<div style="font-size: 1.25rem; font-weight: 950; color: #059669; margin-top: 4px;">' . $MyRow['currency'] . ' ' . locale_number_format($TRow['total'], $MyRow['decimalplaces']) . '</div>
						</div>
						<div class="kpi-mini">
							<div style="font-size: 0.7rem; font-weight: 850; color: #64748b; text-transform: uppercase;">' . __('Last Payment') . '</div>
							<div style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-top: 4px;">' . ConvertSQLDate($MyRow['lastpaiddate']) . '</div>
							<div style="font-size: 0.75rem; color: #059669; font-weight: 700;">' . $MyRow['currency'] . ' ' . locale_number_format($MyRow['lastpaid'], $MyRow['decimalplaces']) . '</div>
						</div>
						<div class="kpi-mini">
							<div style="font-size: 0.7rem; font-weight: 850; color: #64748b; text-transform: uppercase;">' . __('Customer Tenure') . '</div>
							<div style="font-size: 1.1rem; font-weight: 800; color: #1e293b; margin-top: 4px;">' . ConvertSQLDate($MyRow['clientsince']) . '</div>
						</div>
					</div>
				</div>
			</div>';
	}

	if ($_SESSION['geocode_integration'] == 1) {
		$SQL = "SELECT * FROM geocode_param";
		$ResMap = DB_query($SQL);
		if (DB_num_rows($ResMap) > 0) {
			$MapRow = DB_fetch_array($ResMap);
			$SQL = "SELECT lat, lng, brname FROM custbranch WHERE debtorno = '" . $_SESSION['CustomerID'] . "' AND branchcode = '" . $_SESSION['BranchCode'] . "'";
			$ResBranch = DB_query($SQL);
			$BRow = DB_fetch_array($ResBranch);
			if ($BRow && $BRow['lat'] != 0) {
				echo '<div class="db-card">
						<div class="db-card-header">
							<h3 class="db-card-title"><i class="fas fa-map-marked-alt"></i> ' . __('Regional Branch Positioning') . '</h3>
						</div>
						<div class="db-card-body" style="padding: 0;">
							<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
							<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
							<div id="map" style="height:280px; width: 100%;"></div>
							<script>
								var map = L.map(\'map\', { zoomControl: false }).setView([' . $BRow['lat'] . ', ' . $BRow['lng'] . '], 14);
								L.tileLayer(\'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png\').addTo(map);
								L.marker([' . $BRow['lat'] . ', ' . $BRow['lng'] . ']).addTo(map).bindPopup(\'<b>' . htmlspecialchars($BRow['brname'], ENT_QUOTES, 'UTF-8') . '</b>\').openPopup();
							</script>
						</div>
					</div>';
			}
		}
	}
	echo '</div>

		<!-- Tab 2: Contacts -->
		<div id="tab-contacts" class="db-tab-panel">
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-users"></i> ' . __('Authorized Personnel') . '</h3>
					<a href="' . $RootPath . '/AddCustomerContacts.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="architect-btn secondary" style="padding: 6px 14px; font-size: 0.75rem;">
						<i class="fas fa-plus-circle"></i> ' . __('Add New') . '
					</a>
				</div>
				<div style="background: #fff; overflow-x: auto;">';
	$SQL = "SELECT * FROM custcontacts WHERE debtorno='" . $_SESSION['CustomerID'] . "' ORDER BY contid";
	$ConRes = DB_query($SQL);
	if (DB_num_rows($ConRes) > 0) {
		echo '<table class="registry-table">
				<thead>
					<tr><th>' . __('Contact Name') . '</th><th>' . __('Role/Designation') . '</th><th>' . __('Actions') . '</th></tr>
				</thead>
				<tbody>';
		while ($CR = DB_fetch_array($ConRes)) {
			echo '<tr>
					<td style="font-weight: 700; color: #1e293b;">' . $CR[2] . '</td>
					<td><span style="background: #f0fdf4; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 850;">' . $CR[3] . '</span></td>
					<td><a href="mailto:' . $CR[6] . '" class="architect-btn secondary" style="padding: 6px 12px; font-size: 0.7rem;"><i class="fas fa-envelope"></i></a></td>
				  </tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<div style="padding: 40px; text-align:center; color: #94a3b8; font-weight: 600;">' . __('No authorized contacts registered') . '</div>';
	}
	echo '</div></div></div>

		<!-- Tab 3: Notes -->
		<div id="tab-notes" class="db-tab-panel">
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title"><i class="fas fa-sticky-note"></i> ' . __('Account Engagement History') . '</h3>
					<a href="' . $RootPath . '/AddCustomerNotes.php?DebtorNo=' . urlencode($_SESSION['CustomerID']) . '" class="architect-btn secondary" style="padding: 6px 14px; font-size: 0.75rem;">
						<i class="fas fa-pen"></i> ' . __('Write Note') . '
					</a>
				</div>
				<div style="background: #fff; overflow-x: auto;">';
	$SQL = "SELECT * FROM custnotes WHERE debtorno='" . $_SESSION['CustomerID'] . "' ORDER BY date DESC LIMIT 8";
	$NoteRes = DB_query($SQL);
	if (DB_num_rows($NoteRes) > 0) {
		echo '<table class="registry-table">
				<thead>
					<tr><th>' . __('Date') . '</th><th>' . __('Status') . '</th><th>' . __('Engagement Log') . '</th></tr>
				</thead>
				<tbody>';
		while ($NR = DB_fetch_array($NoteRes)) {
			echo '<tr>
					<td style="font-weight: 700; color: #1e293b; white-space: nowrap;">' . ConvertSQLDate($NR['date']) . '</td>
					<td><span style="background: #fff1f2; color: #e11d48; padding: 4px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 850;">' . $NR['priority'] . '</span></td>
					<td style="color: #475569; font-size: 0.85rem; line-height: 1.4;">' . $NR['note'] . '</td>
				  </tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<div style="padding: 40px; text-align:center; color: #94a3b8; font-weight: 600;">' . __('No engagement history available') . '</div>';
	}
	echo '</div></div></div>';
}

echo '</main></div></div>'; // Close .db-main, .custom-bottom-layout, and .db-page
include(__DIR__ . '/includes/footer.php');