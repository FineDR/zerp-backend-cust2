<?php

require(__DIR__ . '/includes/session.php');

$Title = __('General Ledger Accounts');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccounts';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/GLFunctions.php');

if (isset($_POST['SelectedAccount'])) {
	$SelectedAccount = $_POST['SelectedAccount'];
} elseif (isset($_GET['SelectedAccount'])) {
	$SelectedAccount = $_GET['SelectedAccount'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (mb_strlen($_POST['AccountName']) >50) {
		$InputError = 1;
		prnMsg(__('The account name must be fifty characters or less long'), 'warn');
	}

	if (isset($SelectedAccount) AND $InputError != 1) {
		$SQL = "UPDATE chartmaster SET accountname='" . $_POST['AccountName'] . "', group_='" . $_POST['Group'] . "', cashflowsactivity='" . $_POST['CashFlowsActivity'] . "' WHERE accountcode ='" . $SelectedAccount . "'";
		$Result = DB_query($SQL);
		prnMsg(__('Account updated successfully'),'success');
	} elseif ($InputError != 1) {
		$SQL = "SELECT accountcode FROM chartmaster WHERE accountcode='" . $_POST['AccountCode'] . "'";
		if (DB_num_rows(DB_query($SQL)) == 0) {
			$SQL = "INSERT INTO chartmaster (accountcode, accountname, group_, cashflowsactivity) VALUES ('" . $_POST['AccountCode'] . "', '" . $_POST['AccountName'] . "', '" . $_POST['Group'] . "', '" . $_POST['CashFlowsActivity'] . "')";
			DB_query($SQL);
			prnMsg(__('New account added successfully'),'success');
		} else {
			prnMsg(__('Account code already exists'),'error');
		}
	}
	unset($_POST['Group'], $_POST['AccountCode'], $_POST['AccountName'], $_POST['CashFlowsActivity'], $SelectedAccount);

} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(*) FROM gltotals WHERE account ='" . $SelectedAccount . "' AND amount <> 0";
	$Result = DB_query($SQL); $MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		prnMsg(__('Cannot delete: account has balances in one or more periods.'), 'warn');
	} else {
		$SQL = "SELECT COUNT(*) FROM gltrans WHERE gltrans.account ='" . $SelectedAccount . "'";
		$Result = DB_query($SQL); $MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			prnMsg(__('Cannot delete: account has transaction history.'), 'warn');
		} else {
			$SQL = "SELECT COUNT(*) FROM companies WHERE debtorsact='" . $SelectedAccount . "' OR pytdiscountact='" . $SelectedAccount . "' OR creditorsact='" . $SelectedAccount . "' OR payrollact='" . $SelectedAccount . "' OR grnact='" . $SelectedAccount . "' OR currencyexchangediffact='" . $SelectedAccount . "' OR unrealizedcurrencydiffact='" . $SelectedAccount . "' OR salesexchangediffact='" . $SelectedAccount . "' OR purchasesexchangediffact='" . $SelectedAccount . "' OR retainedearnings='" . $SelectedAccount . "'";
			if (DB_fetch_row(DB_query($SQL))[0] > 0) {
				prnMsg(__('Cannot delete: used as a system default account.'), 'warn');
			} else {
				$SQL = "SELECT COUNT(*) FROM taxauthorities WHERE taxglcode='" . $SelectedAccount ."' OR purchtaxglaccount ='" . $SelectedAccount ."'";
				if (DB_fetch_row(DB_query($SQL))[0] > 0) {
					prnMsg(__('Cannot delete: used by tax authorities.'), 'warn');
				} else {
					$SQL = "SELECT COUNT(*) FROM salesglpostings WHERE salesglcode='" . $SelectedAccount . "' OR discountglcode='" . $SelectedAccount . "'";
					if (DB_fetch_row(DB_query($SQL))[0] > 0) {
						prnMsg(__('Cannot delete: used in sales posting interface.'), 'warn');
					} else {
						$SQL = "SELECT COUNT(*) FROM cogsglpostings WHERE glcode='" . $SelectedAccount . "'";
						if (DB_fetch_row(DB_query($SQL))[0] > 0) {
							prnMsg(__('Cannot delete: used in COGS posting interface.'), 'warn');
						} else {
							$SQL = "SELECT COUNT(*) FROM stockcategory WHERE stockact='" . $SelectedAccount . "' OR adjglact='" . $SelectedAccount . "' OR purchpricevaract='" . $SelectedAccount . "' OR materialuseagevarac='" . $SelectedAccount . "' OR wipact='" . $SelectedAccount . "'";
							if (DB_fetch_row(DB_query($SQL))[0] > 0) {
								prnMsg(__('Cannot delete: used in stock category posting.'), 'warn');
							} else {
								$SQL= "SELECT COUNT(*) FROM bankaccounts WHERE accountcode='" . $SelectedAccount ."'";
								if (DB_fetch_row(DB_query($SQL))[0] > 0) {
									prnMsg(__('Cannot delete: defined as a bank account.'), 'warn');
								} else {
									DB_query("DELETE FROM gltotals WHERE account='" . $SelectedAccount ."'");
									DB_query("DELETE FROM chartmaster WHERE accountcode= '" . $SelectedAccount ."'");
									prnMsg(__('Account deleted successfully'), 'success');
                                    unset($SelectedAccount);
								}
							}
						}
					}
				}
			}
		}
	}
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root { --db-primary: hsl(197, 92%, 47%); --db-primary-hover: hsl(197, 92%, 38%); --db-primary-dark: hsl(197, 75%, 22%); --db-primary-soft: hsl(197, 65%, 95%); --db-bg: hsl(210, 20%, 97%); --db-border: hsl(210, 14%, 89%); }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", sans-serif; }
    .db-header { margin-bottom: 2rem; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; opacity: 0.7; }
    .db-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); letter-spacing: -0.04em; }
    .db-layout { display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start; }
    @media (max-width: 1200px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-input, .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fff; }
    .db-help { font-size: 0.7rem; color: #64748b; margin-top: 0.35rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; width: 100%; transition: all 0.2s; }
    .db-btn-primary { background: var(--db-primary); color: #fff; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 850; text-transform: uppercase; font-size: 0.7rem; padding: 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-blue { background: #dbeafe; color: #1e40af; }
    .db-badge-teal { background: #ccfbf1; color: #115e59; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Setup') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN: Chart of Accounts
echo '<main class="db-main">';
if (!isset($SelectedAccount)) {
    echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-book" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Chart of Accounts') . '</h3></div>';
    echo '<div style="overflow-x:auto;"><table class="db-table"><thead><tr><th>Code</th><th>Account Name</th><th>Group</th><th>Scope</th><th style="text-align:right">Actions</th></tr></thead><tbody>';
    $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname, chartmaster.group_, CASE WHEN accountgroups.pandl=0 THEN '" . __('B/S') . "' ELSE '" . __('P/L') . "' END AS acttype, chartmaster.cashflowsactivity 
            FROM chartmaster 
            LEFT JOIN accountgroups ON chartmaster.group_=accountgroups.groupname 
            ORDER BY chartmaster.accountcode";
    $Result = DB_query($SQL);
    while ($MyRow = DB_fetch_array($Result)) {
        $badge = ($MyRow['acttype'] == 'P/L' ? 'db-badge-teal' : 'db-badge-blue');
        echo '<tr>
                <td style="font-weight:700; color:var(--db-primary-dark);">' . $MyRow['accountcode'] . '</td>
                <td>' . htmlspecialchars($MyRow['accountname']) . '</td>
                <td><span class="db-badge">' . $MyRow['group_'] . '</span></td>
                <td><span class="db-badge '.$badge.'">' . $MyRow['acttype'] . '</span></td>
                <td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                    <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; font-size:0.7rem; width:auto;" href="'.htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedAccount='.urlencode($MyRow['accountcode'])).'"><i class="fas fa-edit"></i></a>
                    <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; font-size:0.7rem; color:#dc2626; width:auto;" href="'.htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedAccount='.urlencode($MyRow['accountcode']).'&delete=1').'" onclick="return confirm(\''.__('Final confirmation: Delete account?').'\');"><i class="fas fa-trash"></i></a>
                </div></td></tr>';
    }
    echo '</tbody></table></div></div>';
} else {
    echo '<div class="db-card"><div class="db-card-body" style="text-align:center; padding:3rem;">
            <i class="fas fa-info-circle" style="font-size:3rem; color:var(--db-primary); margin-bottom:1.5rem;"></i>
            <h2 style="margin:0; font-weight:800; color:var(--db-primary-dark);">Currently Editing Account: ' . $SelectedAccount . '</h2>
            <p style="color:#64748b;">Adjust account details in the right panel.</p>
            <a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="width:auto; margin-top:1rem;">Back to Listing</a>
          </div></div>';
}
echo '</main>';

// SIDEBAR: Account Entry
echo '<aside class="db-aside">';
if (isset($SelectedAccount)) {
    $MyRow = DB_fetch_array(DB_query("SELECT accountcode, accountname, group_, cashflowsactivity FROM chartmaster WHERE accountcode='" . $SelectedAccount ."'"));
    $_POST['AccountCode'] = $MyRow['accountcode'];
    $_POST['AccountName'] = $MyRow['accountname'];
    $_POST['Group'] = $MyRow['group_'];
    $_POST['CashFlowsActivity'] = $MyRow['cashflowsactivity'];
    $Legend = __('Edit Account Details');
} else {
    if (!isset($_POST['AccountCode'])) $_POST['AccountCode'] = '';
    if (!isset($_POST['AccountName'])) $_POST['AccountName'] = '';
    if (!isset($_POST['CashFlowsActivity'])) $_POST['CashFlowsActivity'] = 0;
    $Legend = __('Add New GL Account');
}

echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . $Legend . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($SelectedAccount)) echo '<input type="hidden" name="SelectedAccount" value="' . $SelectedAccount . '" />';

echo '<div class="db-form-group"><label class="db-label">Account Code</label><input class="db-input" '.(isset($SelectedAccount)?'disabled':'name="AccountCode"').' required value="'.$_POST['AccountCode'].'" /></div>';
echo '<div class="db-form-group"><label class="db-label">Account Name</label><input class="db-input" name="AccountName" required value="'.$_POST['AccountName'].'" /></div>';

echo '<div class="db-form-group"><label class="db-label">Account Group</label><select name="Group" class="db-select">';
$Groups = DB_query("SELECT groupname FROM accountgroups ORDER BY sequenceintb");
while($G = DB_fetch_array($Groups)) echo '<option '.(isset($_POST['Group']) && $_POST['Group']==$G[0]?'selected':'').' value="'.$G[0].'">'.$G[0].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Cash Flows Logic</label><select name="CashFlowsActivity" class="db-select">
    <option value="0"'.($_POST['CashFlowsActivity']==0?' selected':'').'>'.__('No effect on cash flow').'</option>
    <option value="1"'.($_POST['CashFlowsActivity']==1?' selected':'').'>'.__('Operating activity').'</option>
    <option value="2"'.($_POST['CashFlowsActivity']==2?' selected':'').'>'.__('Investing activity').'</option>
    <option value="3"'.($_POST['CashFlowsActivity']==3?' selected':'').'>'.__('Financing activity').'</option>
    <option value="4"'.($_POST['CashFlowsActivity']==4?' selected':'').'>'.__('Cash or cash equivalent').'</option>
</select></div>';

echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-save"></i> ' . (isset($SelectedAccount)?__('Update Account'):__('Create Account')) . '</button>';
echo '</form></div></div></aside>';

echo '</div></div>';

include(__DIR__ . '/includes/footer.php');
?>
