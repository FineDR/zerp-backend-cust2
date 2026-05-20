<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Search GL Accounts');
$ViewTopic = 'GeneralLedger';
$BookMark = 'GLAccountInquiry';
include(__DIR__ . '/includes/header.php');

$Msg='';
unset($Result);

if (isset($_POST['Search'])){

	if (mb_strlen($_POST['Keywords']>0) AND mb_strlen($_POST['GLCode'])>0) {
		$Msg=__('Account name keywords used in preference to the account code extract entered');
	}
	if ($_POST['Keywords']=='' AND $_POST['GLCode']=='') {
            $SQL = "SELECT chartmaster.accountcode, chartmaster.accountname, chartmaster.group_, CASE WHEN accountgroups.pandl!=0 THEN '" . __('Profit and Loss') . "' ELSE '" . __('Balance Sheet') ."' END AS pl FROM chartmaster, accountgroups, glaccountusers WHERE glaccountusers.accountcode = chartmaster.accountcode AND glaccountusers.userid='" .  $_SESSION['UserID'] . "' AND glaccountusers.canview=1 AND chartmaster.group_=accountgroups.groupname ORDER BY chartmaster.accountcode";
    }
	elseif (mb_strlen($_POST['Keywords'])>0) {
			$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
			$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname, chartmaster.group_, CASE WHEN accountgroups.pandl!=0 THEN '" . __('Profit and Loss') . "' THEN '" . __('Balance Sheet') . "' END AS pl FROM chartmaster, accountgroups, glaccountusers WHERE glaccountusers.accountcode = chartmaster.accountcode AND glaccountusers.userid='" .  $_SESSION['UserID'] . "' AND glaccountusers.canview=1 AND chartmaster.group_ = accountgroups.groupname AND accountname " . LIKE  . "'". $SearchString ."' ORDER BY accountgroups.sequenceintb, chartmaster.accountcode";
		} elseif (mb_strlen($_POST['GLCode'])>0){
			if (!empty($_POST['GLCode'])) {
				echo '<meta http-equiv="refresh" content="0; url=' . $RootPath . '/GLAccountInquiry.php?Account=' . $_POST['GLCode'] . '&Show=Yes">';
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
			$SQL = "SELECT chartmaster.accountcode, chartmaster.accountname, chartmaster.group_, CASE WHEN accountgroups.pandl!=0 THEN '" . __('Profit and Loss') . "' ELSE '" . __('Balance Sheet') ."' END AS pl FROM chartmaster, accountgroups, glaccountusers WHERE glaccountusers.accountcode = chartmaster.accountcode AND glaccountusers.userid='" .  $_SESSION['UserID'] . "' AND glaccountusers.canview=1 AND chartmaster.group_=accountgroups.groupname AND chartmaster.accountcode >= '" . $_POST['GLCode'] . "' ORDER BY chartmaster.accountcode";
		}
		if (isset($SQL) and $SQL!=''){
			$Result = DB_query($SQL);
			if (DB_num_rows($Result) == 1) {
				$AccountRow = DB_fetch_row($Result);
				header('location:' . htmlspecialchars_decode($RootPath) . '/GLAccountInquiry.php?Account=' . urlencode(htmlspecialchars_decode($AccountRow[0])) . '&Show=Yes');
				exit();
			}
		}
}

$TargetPeriod = GetPeriod(date($_SESSION['DefaultDateFormat']));

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.5rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.75rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1.25rem; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.4rem; display: block; }
    .db-input, .db-select { padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: 0.2s; }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); background: #fff; }
    
    .db-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: 0.2s; border: none; width: 100%; text-decoration: none; }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); vertical-align: middle; }
    .db-table tr:hover td { background: #f8fafc; }
    
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
</style>';

echo '<div class="db-page"><div class="db-centered">';

echo '<header class="db-page-header">
    <div class="db-breadcrumb">General Ledger / Maintenance</div>
    <h1 class="db-page-title">' . __('GL Account Search') . '</h1>
</header>';

if (mb_strlen($Msg)>1) prnMsg($Msg,'info');

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">
    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
    
    <div class="db-main-grid">
        <!-- Sidebar: SEARCH -->
        <div class="db-column">
            <div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Filters') . '</h3></div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label class="db-label">' . __('Account Name Keywords') . '</label>
                        <input type="text" name="Keywords" class="db-input" placeholder="e.g. Sales, Rent..." autofocus />
                    </div>
                    
                    <div style="text-align:center; padding: 0.5rem 0; font-weight:900; color:var(--db-text-muted); font-size:0.7rem;">' . __('OR SEARCH BY CODE') . '</div>';

                    $SQLAccountSelect="SELECT chartmaster.accountcode, chartmaster.accountname, chartmaster.group_ FROM chartmaster INNER JOIN glaccountusers ON glaccountusers.accountcode=chartmaster.accountcode AND glaccountusers.userid='" .  $_SESSION['UserID'] . "' AND glaccountusers.canview=1 INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname ORDER BY accountgroups.sequenceintb, accountgroups.groupname, chartmaster.accountcode";
                    $ResultSelection=DB_query($SQLAccountSelect);
                    $OptGroup = ''; echo '<div class="db-field"><select name="GLCode" class="db-select"><option value="">' . __('Select Account') . '</option>';
                    while ($MyRowSelection=DB_fetch_array($ResultSelection)){
                        if ($OptGroup != $MyRowSelection['group_']) {
                            if($OptGroup!='') echo '</optgroup>';
                            echo '<optgroup label="' . $MyRowSelection['group_'] . '">'; $OptGroup = $MyRowSelection['group_'];
                        }
                        $sel = (isset($_POST['GLCode']) and $_POST['GLCode']==$MyRowSelection['accountcode']) ? 'selected':'';
                        echo '<option '.$sel.' value="' . $MyRowSelection['accountcode'] . '">' . $MyRowSelection['accountcode'].' - ' .htmlspecialchars($MyRowSelection['accountname'], ENT_QUOTES,'UTF-8', false) . '</option>';
                    }
                    echo '</optgroup></select></div>';

                    echo '<button type="submit" name="Search" class="db-btn db-btn-primary">' . __('Find Account') . '</button>
                    <button type="submit" name="reset" class="db-btn db-btn-ghost" style="margin-top:0.75rem;">' . __('Reset') . '</button>
                </div>
            </div>
            
            <div class="db-card" style="background:var(--db-primary-soft); border-color:var(--db-primary);">
                <div class="db-card-body" style="font-size:0.75rem; color:var(--db-primary-dark); font-weight:600;">
                    <i class="fas fa-info-circle"></i> ' . __('Use high-level keywords or pick an account from the group lists to view details or history.') . '
                </div>
            </div>
        </div>

        <!-- Main: RESULTS -->
        <div class="db-column">';
        
        if (isset($Result) and DB_num_rows($Result)>0) {
            echo '<div class="db-card">
                <div class="db-card-header"><h3 class="db-card-title">' . __('Matching Financial Accounts') . ' (' . DB_num_rows($Result) . ')</h3></div>
                <div class="db-card-body" style="padding:0;">
                    <div class="db-table-container">
                        <table class="db-table">
                            <thead><tr><th>Code</th><th>Account Name</th><th>Parent Group</th><th>Type</th><th style="text-align:right;">Actions</th></tr></thead>
                            <tbody>';
            
            while ($MyRow=DB_fetch_array($Result)) {
                echo '<tr>
                    <td><b>' . htmlspecialchars($MyRow['accountcode'],ENT_QUOTES,'UTF-8',false) . '</b></td>
                    <td>' . htmlspecialchars($MyRow['accountname'],ENT_QUOTES,'UTF-8',false) . '</td>
                    <td><small class="db-badge">' . $MyRow['group_'] . '</small></td>
                    <td>' . $MyRow['pl'] . '</td>
                    <td style="text-align:right; white-space:nowrap;">
                        <a href="' . $RootPath . '/GLAccountInquiry.php?Account=' . $MyRow['accountcode'] . '&Show=Yes&FromPeriod=' . $TargetPeriod . '&ToPeriod=' . $TargetPeriod . '" class="link-action" style="margin-right:8px;">View</a>
                        <a href="' . $RootPath . '/GLAccounts.php?SelectedAccount=' . $MyRow['accountcode'] . '" class="link-action" style="color:var(--db-text-muted);">Edit</a>
                    </td>
                </tr>';
            }
            echo '</tbody></table></div></div></div>';
        } elseif (isset($_POST['Search'])) {
            echo '<div class="db-card"><div class="db-card-body" style="text-align:center; padding:3rem; color:var(--db-text-muted);">
                ' . __('No accounts found matching your criteria.') . '
            </div></div>';
        } else {
            echo '<div class="db-card"><div class="db-card-body" style="text-align:center; padding:4rem; color:var(--db-text-muted);">
                <i class="fas fa-search" style="font-size:2rem; margin-bottom:1rem; display:block;"></i>
                ' . __('Enter search criteria to list general ledger accounts.') . '
            </div></div>';
        }
        
        echo '</div>
    </div>
</form></div></div>';

include(__DIR__ . '/includes/footer.php');
?>
