<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Claim Petty Cash Expenses From Tab');
$ViewTopic = 'PettyCash';
$BookMark = 'ExpenseClaim';

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
    
    /* Header & Navigation */
    .premium-header {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--border-color);
        margin: -20px -30px 30px -30px;
        padding: 15px 30px;
        position: sticky;
        top: 0;
        z-index: 100;
    }
    .premium-header-inner { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
    .breadcrumb { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .breadcrumb a { color: var(--primary); text-decoration: none; }
    .page-title { font-size: 1.75rem; font-weight: 900; color: #0f172a; letter-spacing: -0.04em; }

    /* Layout Grid */
    .db-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; align-items: start; }
    @media (max-width: 1200px) { .db-grid { grid-template-columns: 1fr; } }

    /* Cards */
    .db-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .db-card-header { padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: #fcfcfd; }
    .db-card-title { font-size: 0.95rem; font-weight: 700; color: #334155; }
    .db-card-body { padding: 20px; }

    /* Metrics Bar */
    .metrics-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
    .metric-card { padding: 20px; background: white; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .metric-label { font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
    .metric-value { font-size: 1.5rem; font-weight: 800; color: var(--primary); }

    /* Tables */
    .table-container { overflow-x: auto; border-radius: 2px; }
    table.selection { width: 100% !important; border-collapse: collapse !important; border: none !important; margin: 0 !important; }
    table.selection th { 
        background: #f8fafc !important; 
        color: #475569 !important; 
        font-weight: 700 !important; 
        font-size: 0.75rem !important; 
        text-transform: uppercase !important; 
        padding: 12px 16px !important;
        border-bottom: 2px solid var(--border-color) !important;
        text-align: left !important;
    }
    table.selection td { 
        padding: 14px 16px !important; 
        font-size: 0.85rem !important; 
        border-bottom: 1px solid #f1f5f9 !important;
        vertical-align: middle !important;
    }
    .status-badge { padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.7rem; text-transform: uppercase; }
    .status-unauth { background: #fee2e2; color: #991b1b; }
    .status-auth { background: #dcfce7; color: #166534; }

    /* Form Elements */
    .form-group { margin-bottom: 1.25rem; }
    .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 6px; }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem;
        transition: border-color 0.2s, box-shadow 0.2s; background: #fff;
    }
    .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1); }
    
    .architect-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;
        transition: all 0.2s; border: none; text-decoration: none;
    }
    .btn-primary { background: var(--primary); color: white; }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .btn-outline { background: transparent; border: 1px solid #d1d5db; color: #475569; }
    .btn-outline:hover { background: #f9fafb; border-color: #94a3b8; }
    
    .tag-container { display: flex; flex-wrap: wrap; gap: 4px; }
    .tag-pill { background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; border: 1px solid #e2e8f0; }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

// --- Legacy Logic Block (Preserved) ---
if (isset($_POST['Date'])){$_POST['Date'] = ConvertSQLDate($_POST['Date']);}

if (isset($_POST['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_POST['SelectedTabs']);
} elseif (isset($_GET['SelectedTabs'])) {
	$SelectedTabs = mb_strtoupper($_GET['SelectedTabs']);
}
if (isset($_POST['SelectedIndex'])) {
	$SelectedIndex = $_POST['SelectedIndex'];
} elseif (isset($_GET['SelectedIndex'])) {
	$SelectedIndex = $_GET['SelectedIndex'];
}
if (isset($_POST['Days'])) {
	$Days = filter_number_format($_POST['Days']);
} elseif (isset($_GET['Days'])) {
	$Days = filter_number_format($_GET['Days']);
}
if (isset($_POST['Cancel'])) {
	unset($SelectedTabs);
	unset($SelectedIndex);
	unset($Days);
	unset($_POST['Amount']);
	unset($_POST['Purpose']);
	unset($_POST['Notes']);
	unset($_FILES['Receipt']);
}
if (isset($_POST['Process'])) {
	if ($_POST['SelectedTabs'] == '') {
		prnMsg(__('You have not selected a tab to claim the expenses on'), 'error');
		unset($SelectedTabs);
	}
}
if (isset($_POST['Go'])) {
	if ($Days <= 0) {
		prnMsg(__('The number of days must be a positive number'), 'error');
		$Days = 30;
	}
}
$ReceiptSupportedExt = array('png','jpg','jpeg','pdf','doc','docx','xls','xlsx');
$ReceiptDir = $PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/expenses_receipts/';

if (isset($_POST['submit'])) {
	$InputError = 0;
	if ($_POST['SelectedExpense'] == '') {
		$InputError = 1;
		prnMsg(__('You have not selected an expense to claim on this tab'), 'error');
	} elseif ($_POST['Amount'] == 0) {
		$InputError = 1;
		prnMsg(__('The amount must be greater than 0'), 'error');
	}
	if (!is_date($_POST['Date'])) {
		$InputError = 1;
		prnMsg(__('The date input is not in the correct format'), 'error');
	}
	if (isset($SelectedIndex) and $InputError != 1) { //Edit
		$SQL = "UPDATE pcashdetails
			SET date = '" . FormatDateForSQL($_POST['Date']) . "',
				codeexpense = '" . $_POST['SelectedExpense'] . "',
				amount = '" . -filter_number_format($_POST['Amount']) . "',
				purpose='" . $_POST['Purpose'] . "',
				notes = '" . $_POST['Notes'] . "'
			WHERE counterindex = '" . $SelectedIndex . "'";
		$Msg = __('The expense record on tab') . ' ' . $SelectedTabs . ' ' . __('has been updated');
		$Result = DB_query($SQL);
		foreach ($_POST as $Index => $Value) {
			if (substr($Index, 0, 5) == 'index') {
				$Index = $Value;
				$SQL = "UPDATE pcashdetailtaxes SET pccashdetail='" . $_POST['PcCashDetail' . $Index] . "',
													calculationorder='" . $_POST['CalculationOrder' . $Index] . "',
													description='" . $_POST['Description' . $Index] . "',
													taxauthid='" . $_POST['TaxAuthority' . $Index] . "',
													purchtaxglaccount='" . $_POST['TaxGLAccount' . $Index] . "',
													taxontax='" . $_POST['TaxOnTax' . $Index] . "',
													taxrate='" . $_POST['TaxRate' . $Index] . "',
													amount='" . -$_POST['TaxAmount' . $Index] . "'
												WHERE counterindex='" . $Index ."'";
				$Result = DB_query($SQL);
			}
		}
		$SQL = "DELETE FROM pctags WHERE pccashdetail='" . $SelectedIndex . "'";
		$Result = DB_query($SQL);
		if (isset($_POST['tag'])) {
			foreach ($_POST['tag'] as $Tag) {
				$SQL = "INSERT INTO pctags (pccashdetail, tag) VALUES ('" . $SelectedIndex . "', '" . $Tag . "')";
				$Result = DB_query($SQL);
			}
		}
		if (isset($_FILES['Receipt']) and $_FILES['Receipt']['name'] != '') {
			$UploadTheFile = 'Yes';
			if ($_FILES['Receipt']['error'] != 0) $UploadTheFile = 'No';
			if ($UploadTheFile == 'Yes') {
				$ReceiptSQL = "SELECT hashfile, extension FROM pcreceipts WHERE pccashdetail='" . $SelectedIndex . "' LIMIT 1";
				$ReceiptResult = DB_query($ReceiptSQL);
				$ReceiptRow = DB_fetch_assoc($ReceiptResult);
				if (DB_num_rows($ReceiptResult) > 0) {
					unlink($ReceiptDir . $ReceiptRow['hashfile'] . '.' . $ReceiptRow['extension']);
					$ReceiptHash = md5(md5_file($_FILES['Receipt']['tmp_name']) . microtime());
					$ReceiptExt = strtolower(pathinfo($_FILES['Receipt']['name'], PATHINFO_EXTENSION));
					move_uploaded_file($_FILES['Receipt']['tmp_name'], $ReceiptDir . $ReceiptHash . '.' . $ReceiptExt);
					$ReceiptSQL = "UPDATE pcreceipts SET hashfile='" . $ReceiptHash . "', type='" . $_FILES['Receipt']['type'] . "', extension='" . $ReceiptExt . "', size=" . $_FILES['Receipt']['size'] . " WHERE pccashdetail='" . $SelectedIndex . "'";
				} else {
					$ReceiptExt = strtolower(pathinfo($_FILES['Receipt']['name'], PATHINFO_EXTENSION));
					$ReceiptHash = md5(md5_file($_FILES['Receipt']['tmp_name']) . microtime());
					move_uploaded_file($_FILES['Receipt']['tmp_name'], $ReceiptDir . $ReceiptHash . '.' . $ReceiptExt);
					$ReceiptSQL = "INSERT INTO pcreceipts (pccashdetail, hashfile, type, extension, size) VALUES ('" . $SelectedIndex . "', '" . $ReceiptHash . "', '" . $_FILES['Receipt']['type'] . "', '" . $ReceiptExt . "', " . $_FILES['Receipt']['size'] . ")";
				}
				DB_query($ReceiptSQL);
			}
		}
		prnMsg($Msg, 'success');
	} elseif ($InputError != 1) { // New
		$SQL = "INSERT INTO pcashdetails (tabcode, date, codeexpense, amount, authorized, posted, purpose, notes)
				VALUES ('" . $_POST['SelectedTabs'] . "', '" . FormatDateForSQL($_POST['Date']) . "', '" . $_POST['SelectedExpense'] . "', '" . -filter_number_format($_POST['Amount']) . "', '1000-01-01', 0, '" . $_POST['Purpose'] . "', '" . $_POST['Notes'] . "')";
		$Msg = __('The expense claim on tab') . ' ' . $_POST['SelectedTabs'] . ' ' . __('has been created');
		DB_query($SQL);
		$SelectedIndex = DB_Last_Insert_ID('pcashdetails', 'counterindex');
		if (isset($_POST['tag'])) {
			foreach ($_POST['tag'] as $Tag) {
				DB_query("INSERT INTO pctags (pccashdetail, tag) VALUES ('" . $SelectedIndex . "', '" . $Tag . "')");
			}
		}
		foreach ($_POST as $Index => $Value) {
			if (substr($Index, 0, 5) == 'index') {
				$Index = $Value;
				$SQL = "INSERT INTO pcashdetailtaxes (pccashdetail, calculationorder, description, taxauthid, purchtaxglaccount, taxontax, taxrate, amount)
						VALUES ('" . $SelectedIndex . "', '" . $_POST['CalculationOrder' . $Index] . "', '" . $_POST['Description' . $Index] . "', '" . $_POST['TaxAuthority' . $Index] . "', '" . $_POST['TaxGLAccount' . $Index] . "', '" . $_POST['TaxOnTax' . $Index] . "', '" . $_POST['TaxRate' . $Index] . "', '" . -$_POST['TaxAmount' . $Index] . "')";
				DB_query($SQL);
			}
		}
		if (isset($_FILES['Receipt']) and $_FILES['Receipt']['name'] != '') {
			$ReceiptHash = md5(md5_file($_FILES['Receipt']['tmp_name']) . microtime());
			$ReceiptExt = strtolower(pathinfo($_FILES['Receipt']['name'], PATHINFO_EXTENSION));
			move_uploaded_file($_FILES['Receipt']['tmp_name'], $ReceiptDir . $ReceiptHash . '.' . $ReceiptExt);
			DB_query("INSERT INTO pcreceipts (pccashdetail, hashfile, type, extension, size) VALUES ('" . $SelectedIndex . "', '" . $ReceiptHash . "', '" . $_FILES['Receipt']['type'] . "', '" . $ReceiptExt . "', " . $_FILES['Receipt']['size'] . ")");
		}
		prnMsg($Msg, 'success');
	}
	if ($InputError != 1) {
		unset($_POST['SelectedExpense'], $_POST['Amount'], $_POST['Tag'], $_POST['Date'], $_POST['Purpose'], $_POST['Notes'], $_FILES['Receipt'], $SelectedIndex);
	}
} elseif (isset($_GET['delete'])) {
	$ReceiptSQL = "SELECT hashfile, extension FROM pcreceipts WHERE pccashdetail='" . $SelectedIndex . "' LIMIT 1";
	$ReceiptResult = DB_query($ReceiptSQL);
	if ($ReceiptRow = DB_fetch_assoc($ReceiptResult)) {
		unlink($ReceiptDir . $ReceiptRow['hashfile'] . '.' . $ReceiptRow['extension']);
		DB_query("DELETE FROM pcreceipts WHERE pccashdetail='" . $SelectedIndex . "'");
	}
	DB_query("DELETE FROM pcashdetailtaxes WHERE pccashdetail = '" . $SelectedIndex . "'");
	DB_query("DELETE FROM pcashdetails WHERE counterindex = '" . $SelectedIndex . "'");
	prnMsg(__('The expense record on tab') . ' ' . $SelectedTabs . ' ' . __('has been deleted'), 'success');
	unset($SelectedIndex);
}

echo '<div class="db-page">';

if (!isset($SelectedTabs)) {
    // --- Step 1: Tab Selection ---
    echo '
    <div class="premium-header">
        <div class="premium-header-inner">
            <div>
                <div class="breadcrumb">' . __('Petty Cash') . ' / ' . __('Select Tab') . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
        </div>
    </div>
    
    <div style="max-width: 500px; margin: 40px auto;">
        <div class="db-card">
            <div class="db-card-header">
                <div class="db-card-title">' . __('Authorized Tabs') . '</div>
            </div>
            <div class="db-card-body">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="form-group">
                        <label>' . __('Select a petty cash tab to manage') . '</label>
                        <select required="required" name="SelectedTabs">
                            <option value="">' . __('Not Yet Selected') . '</option>';
                            $SQL = "SELECT tabcode FROM pctabs WHERE usercode='" . $_SESSION['UserID'] . "'";
                            $Result = DB_query($SQL);
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<option value="' . $MyRow['tabcode'] . '">' . $MyRow['tabcode'] . '</option>';
                            }
                        echo '</select>
                    </div>
                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <button type="submit" name="Process" class="architect-btn btn-primary" style="flex: 1;">' . __('Continue') . '</button>
                        <button type="reset" class="architect-btn btn-outline">' . __('Reset') . '</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
} else {
    // --- Step 2: Main Dashboard ---
    $CurrentBalance = PettyCashTabCurrentBalance($SelectedTabs);
    
    echo '
    <div class="premium-header">
        <div class="premium-header-inner">
            <div>
                <div class="breadcrumb"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">' . __('Select Tab') . '</a> / ' . $SelectedTabs . '</div>
                <div class="page-title">' . $Title . '</div>
            </div>
            <div class="header-actions">
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="architect-btn btn-outline">' . __('Change Tab') . '</a>
            </div>
        </div>
    </div>';

    echo '<div class="metrics-row">
            <div class="metric-card">
                <div class="metric-label">' . __('Current Balance') . '</div>
                <div class="metric-value">' . locale_number_format($CurrentBalance, 2) . '</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">' . __('Active Tab') . '</div>
                <div class="metric-value" style="color: #64748b; font-size: 1.2rem;">' . $SelectedTabs . '</div>
            </div>
          </div>';

    echo '<div class="db-grid">';
    
    // --- Main Area: History Table ---
    echo '<div class="db-main">';
        echo '<div class="db-card">
                <div class="db-card-header">
                    <div class="db-card-title">' . __('Movement History') . '</div>
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="display: flex; gap: 8px; align-items: center;">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        <span style="font-size: 0.8rem; color: #64748b;">' . __('Last') . '</span>
                        <input type="text" name="Days" value="' . ($Days ?? 30) . '" style="width: 50px; padding: 4px 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.8rem;" />
                        <span style="font-size: 0.8rem; color: #64748b;">' . __('Days') . '</span>
                        <button type="submit" name="Go" class="architect-btn btn-outline" style="padding: 4px 12px; font-size: 0.8rem;">' . __('Go') . '</button>
                    </form>
                </div>
                <div class="table-container">';
        
        $SQL = "SELECT counterindex, date, codeexpense, amount, authorized, purpose, notes
                FROM pcashdetails
                WHERE tabcode='" . $SelectedTabs . "'
                    AND date >= DATE_SUB(CURDATE(), INTERVAL " . ($Days ?? 30) . " DAY)
                ORDER BY date DESC, counterindex DESC";
        $Result = DB_query($SQL);
        
        echo '<table class="selection">
                <thead>
                    <tr>
                        <th>' . __('Date') . '</th>
                        <th>' . __('Expense') . '</th>
                        <th>' . __('Gross') . '</th>
                        <th>' . __('Tax') . '</th>
                        <th>' . __('Purpose') . '</th>
                        <th>' . __('Status') . '</th>
                        <th>' . __('Action') . '</th>
                    </tr>
                </thead>
                <tbody>';
        
        while ($MyRow = DB_fetch_array($Result)) {
            $ExpenseDes = $MyRow['codeexpense'] == 'ASSIGNCASH' ? 'Assignment of Cash' : $MyRow['codeexpense'];
            $StatusClass = ($MyRow['authorized'] == '1000-01-01' or $MyRow['authorized'] == '0000-00-00') ? 'status-unauth' : 'status-auth';
            $StatusText = ($MyRow['authorized'] == '1000-01-01' or $MyRow['authorized'] == '0000-00-00') ? __('Unauthorised') : ConvertSQLDate($MyRow['authorized']);
            
            // Taxes
            $TaxSum = 0;
            $TaxSQL = "SELECT SUM(amount) as taxes FROM pcashdetailtaxes WHERE pccashdetail='" . $MyRow['counterindex'] . "'";
            $TaxRes = DB_query($TaxSQL);
            $TaxRow = DB_fetch_array($TaxRes);
            $TaxSum = $TaxRow['taxes'];

            echo '<tr>
                    <td>' . ConvertSQLDate($MyRow['date']) . '</td>
                    <td><div style="font-weight:700; color:#1e293b;">' . $ExpenseDes . '</div></td>
                    <td style="font-family: \'JetBrains Mono\', monospace; font-weight: 800; color: var(--text-main);">' . locale_number_format($MyRow['amount'], 2) . '</td>
                    <td style="font-family: \'JetBrains Mono\', monospace; color: var(--text-muted);">' . locale_number_format($TaxSum, 2) . '</td>
                    <td><div style="max-width:200px; font-size:0.8rem; color:#64748b;">' . $MyRow['purpose'] . '</div></td>
                    <td><span class="status-badge ' . $StatusClass . '">' . $StatusText . '</span></td>
                    <td>';
            if ($MyRow['authorized'] == '1000-01-01' && $MyRow['codeexpense'] != 'ASSIGNCASH') {
                echo '<div style="display:flex; gap:8px;">
                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedIndex=' . $MyRow['counterindex'] . '&SelectedTabs=' . $SelectedTabs . '&edit=yes" style="color:var(--primary); font-weight:700; font-size: 0.8rem;">' . __('Edit') . '</a>
                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedIndex=' . $MyRow['counterindex'] . '&SelectedTabs=' . $SelectedTabs . '&delete=yes" onclick="return confirm(\'' . __('Delete this expense?') . '\')" style="color:var(--rose); font-weight:700; font-size: 0.8rem;">' . __('Del') . '</a>
                      </div>';
            }
            echo '</td>
                  </tr>';
        }
        echo '</tbody></table></div></div>';
    echo '</div>'; // End db-main

    // --- Sidebar: Form Area ---
    echo '<div class="db-aside">';
        if (isset($_GET['edit'])) {
            $EditSQL = "SELECT * FROM pcashdetails WHERE counterindex='" . $SelectedIndex . "'";
            $EditRes = DB_query($EditSQL);
            $EditRow = DB_fetch_assoc($EditRes);
            $_POST['Date'] = ConvertSQLDate($EditRow['date']);
            $_POST['SelectedExpense'] = $EditRow['codeexpense'];
            $_POST['Amount'] = -$EditRow['amount'];
            $_POST['Purpose'] = $EditRow['purpose'];
            $_POST['Notes'] = $EditRow['notes'];
        }

        echo '<div class="db-card" style="position: sticky; top: 80px;">
                <div class="db-card-header">
                    <div class="db-card-title">' . (isset($SelectedIndex) ? __('Update Expense') : __('New Expense Feed')) . '</div>
                </div>
                <div class="db-card-body">
                    <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" enctype="multipart/form-data">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        <input type="hidden" name="SelectedTabs" value="' . $SelectedTabs . '" />
                        ' . (isset($SelectedIndex) ? '<input type="hidden" name="SelectedIndex" value="' . $SelectedIndex . '" />' : '') . '
                        
                        <div class="form-group">
                            <label>' . __('Date') . '</label>
                            <input type="date" name="Date" required value="' . FormatDateForSQL($_POST['Date'] ?? date($_SESSION['DefaultDateFormat'])) . '" />
                        </div>
                        
                        <div class="form-group">
                            <label>' . __('Expense Code') . '</label>
                            <select required name="SelectedExpense">
                                <option value="">' . __('Not Yet Selected') . '</option>';
                                $ExplSQL = "SELECT px.codeexpense, px.description 
                                           FROM pctabexpenses te, pcexpenses px, pctabs pt 
                                           WHERE te.codeexpense = px.codeexpense AND te.typetabcode = pt.typetabcode AND pt.tabcode = '$SelectedTabs'
                                           ORDER BY px.codeexpense";
                                $ExplRes = DB_query($ExplSQL);
                                while ($Row = DB_fetch_array($ExplRes)) {
                                    $sel = ($_POST['SelectedExpense'] ?? '') == $Row['codeexpense'] ? 'selected' : '';
                                    echo '<option ' . $sel . ' value="' . $Row['codeexpense'] . '">' . $Row['codeexpense'] . ' - ' . $Row['description'] . '</option>';
                                }
                            echo '</select>
                        </div>
                        
                        <div class="form-group">
                            <label>' . __('Gross Amount') . '</label>
                            <input type="text" class="number" name="Amount" required value="' . ($_POST['Amount'] ?? 0) . '" />
                        </div>';

        // Taxes
        if (!isset($SelectedIndex)) {
            $TaxSQL = "SELECT tg.calculationorder, ta.description, tg.taxauthid, ta.purchtaxglaccount, tg.taxontax, tr.taxrate
                       FROM taxauthrates tr, taxgrouptaxes tg, taxauthorities ta, taxgroups tgr, pctabs pt
                       WHERE tr.taxauthority = tg.taxauthid AND tr.taxauthority = ta.taxid AND tgr.taxgroupid = tg.taxgroupid AND pt.taxgroupid = tgr.taxgroupid
                       AND tr.taxcatid = " . $_SESSION['DefaultTaxCategory'] . " AND pt.tabcode = '$SelectedTabs'
                       ORDER BY tg.calculationorder";
            $TaxRes = DB_query($TaxSQL);
            $i = 0;
            while ($Tax = DB_fetch_array($TaxRes)) {
                echo '<input type="hidden" name="index' . $i . '" value="' . $i . '" />
                      <input type="hidden" name="CalculationOrder' . $i . '" value="' . $Tax['calculationorder'] . '" />
                      <input type="hidden" name="Description' . $i . '" value="' . $Tax['description'] . '" />
                      <input type="hidden" name="TaxAuthority' . $i . '" value="' . $Tax['taxauthid'] . '" />
                      <input type="hidden" name="TaxGLAccount' . $i . '" value="' . $Tax['purchtaxglaccount'] . '" />
                      <input type="hidden" name="TaxOnTax' . $i . '" value="' . $Tax['taxontax'] . '" />
                      <input type="hidden" name="TaxRate' . $i . '" value="' . $Tax['taxrate'] . '" />
                      <div class="form-group">
                        <label>' . $Tax['description'] . ' (' . ($Tax['taxrate']*100) . '%)</label>
                        <input type="text" class="number" name="TaxAmount' . $i . '" value="0" />
                      </div>';
                $i++;
            }
        } else {
             $TaxSQL = "SELECT * FROM pcashdetailtaxes WHERE pccashdetail='$SelectedIndex'";
             $TaxRes = DB_query($TaxSQL);
             while ($Tax = DB_fetch_array($TaxRes)) {
                 $idx = $Tax['counterindex'];
                 echo '<input type="hidden" name="index' . $idx . '" value="' . $idx . '" />
                       <input type="hidden" name="PcCashDetail' . $idx . '" value="' . $Tax['pccashdetail'] . '" />
                       <input type="hidden" name="CalculationOrder' . $idx . '" value="' . $Tax['calculationorder'] . '" />
                       <input type="hidden" name="Description' . $idx . '" value="' . $Tax['description'] . '" />
                       <input type="hidden" name="TaxAuthority' . $idx . '" value="' . $Tax['taxauthid'] . '" />
                       <input type="hidden" name="TaxGLAccount' . $idx . '" value="' . $Tax['purchtaxglaccount'] . '" />
                       <input type="hidden" name="TaxOnTax' . $idx . '" value="' . $Tax['taxontax'] . '" />
                       <input type="hidden" name="TaxRate' . $idx . '" value="' . $Tax['taxrate'] . '" />
                       <div class="form-group">
                         <label>' . $Tax['description'] . ' (' . ($Tax['taxrate']*100) . '%)</label>
                         <input type="text" class="number" name="TaxAmount' . $idx . '" value="' . -$Tax['amount'] . '" />
                       </div>';
             }
        }

        echo '
                        <div class="form-group">
                            <label>' . __('Business Purpose') . '</label>
                            <input type="text" name="Purpose" required maxlength="49" value="' . ($_POST['Purpose'] ?? '') . '" />
                        </div>
                        
                        <div class="form-group">
                            <label>' . __('Notes') . '</label>
                            <textarea name="Notes" style="height: 60px;">' . ($_POST['Notes'] ?? '') . '</textarea>
                        </div>

                        <div class="form-group">
                            <label>' . __('Receipt Attachment') . '</label>
                            <input type="file" name="Receipt" style="font-size: 0.8rem; padding: 6px;" />
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="submit" class="architect-btn btn-primary">' . __('Accept') . '</button>
                            <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedTabs=' . $SelectedTabs . '" class="architect-btn btn-outline">' . __('Cancel') . '</a>
                        </div>
                    </form>
                </div>
              </div>';
    echo '</div>'; // End db-aside

    echo '</div>'; // End db-grid
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
