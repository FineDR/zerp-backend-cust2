<?php
/**
 * Account Groups Management
 * 
 * Defines and manages the groupings of general ledger accounts.
 * Allows creating, editing, and deleting account group hierarchies
 * with parent-child relationships.
 */

require(__DIR__ . '/includes/session.php');

// Page configuration
$Title = __('Account Groups');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AccountGroups';
include(__DIR__ . '/includes/header.php');

include(__DIR__ . '/includes/SQL_CommonFunctions.php');

/**
 * Check if creating a parent-child relationship would create a recursive loop
 */
function CheckForRecursiveGroup($ParentGroupName, $GroupName) {
	$ErrMsg = __('An error occurred in retrieving the account groups');
	do {
		$SQL = "SELECT parentgroupname FROM accountgroups WHERE groupname='" . $GroupName ."'";
		$Result = DB_query($SQL, $ErrMsg);
		$MyRow = DB_fetch_row($Result);
		if ($ParentGroupName == $MyRow[0]) return true;
		$GroupName = $MyRow[0];
	} while($MyRow[0] != '');
	return false;
}

$Errors = array();

if (isset($_POST['MoveGroup'])) {
	$SQL="UPDATE chartmaster SET group_='" . $_POST['DestinyAccountGroup'] . "' WHERE group_='" . $_POST['OriginalAccountGroup'] . "'";
	DB_query($SQL);
	prnMsg( __('Accounts migrated successfully'),'success');
}

if (isset($_POST['submit'])) {
	$InputError = 0; $i=1;
	$SQL="SELECT count(groupname) FROM accountgroups WHERE groupname='" . $_POST['GroupName'] . "'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_row($Result);

	if ($MyRow[0] != 0 AND $_POST['SelectedAccountGroup'] == '') {
		$InputError = 1; prnMsg( __('Group already exists'),'error');
		$Errors[$i++] = 'GroupName';
	}
	if (ContainsIllegalCharacters($_POST['GroupName'])) {
		$InputError = 1; prnMsg( __('Illegal characters in name'),'error');
		$Errors[$i++] = 'GroupName';
	}
	if (mb_strlen($_POST['GroupName'])==0) {
		$InputError = 1; prnMsg( __('Name required'),'error');
		$Errors[$i++] = 'GroupName';
	}
	
	if ($_POST['ParentGroupName'] !='') {
		if (CheckForRecursiveGroup($_POST['GroupName'],$_POST['ParentGroupName'])) {
			$InputError =1; prnMsg(__('Recursive structure detected'),'error');
			$Errors[$i++] = 'ParentGroupName';
		} else {
			$SQL = "SELECT pandl, sequenceintb, sectioninaccounts FROM accountgroups WHERE groupname='" . $_POST['ParentGroupName'] . "'";
			$ParentGroupRow = DB_fetch_array(DB_query($SQL));
			$_POST['SequenceInTB'] = $ParentGroupRow['sequenceintb'];
			$_POST['PandL'] = $ParentGroupRow['pandl'];
			$_POST['SectionInAccounts']= $ParentGroupRow['sectioninaccounts'];
			prnMsg(__('Inherited properties from parent group applied.'),'warn');
		}
	}
	if (!ctype_digit($_POST['SectionInAccounts'])) { $InputError = 1; $Errors[$i++] = 'SectionInAccounts'; }
	if (!ctype_digit($_POST['SequenceInTB'])) { $InputError = 1; $Errors[$i++] = 'SequenceInTB'; }

	if ($_POST['SelectedAccountGroup']!='' AND $InputError !=1) {
		if ($_POST['SelectedAccountGroup']!==$_POST['GroupName']) {
			DB_IgnoreForeignKeys();
			DB_query("UPDATE chartmaster SET group_='" . $_POST['GroupName'] . "' WHERE group_='" . $_POST['SelectedAccountGroup'] . "'");
			DB_query("UPDATE accountgroups SET parentgroupname='" . $_POST['GroupName'] . "' WHERE parentgroupname='" . $_POST['SelectedAccountGroup'] . "'");
			DB_ReinstateForeignKeys();
		}
		$SQL = "UPDATE accountgroups SET groupname='" . $_POST['GroupName'] . "', sectioninaccounts='" . $_POST['SectionInAccounts'] . "', pandl='" . $_POST['PandL'] . "', sequenceintb='" . $_POST['SequenceInTB'] . "', parentgroupname='" . $_POST['ParentGroupName'] . "' WHERE groupname = '" . $_POST['SelectedAccountGroup'] . "'";
		$Msg = __('Record Updated');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO accountgroups (groupname, sectioninaccounts, sequenceintb, pandl, parentgroupname) VALUES ('" . $_POST['GroupName'] . "', '" . $_POST['SectionInAccounts'] . "', '" . $_POST['SequenceInTB'] . "', '" . $_POST['PandL'] . "', '" . $_POST['ParentGroupName'] . "')";
		$Msg = __('Record inserted');
	}

	if ($InputError!=1) {
		DB_query($SQL);
		prnMsg($Msg,'success');
		unset ($_POST['SelectedAccountGroup'], $_POST['GroupName'], $_POST['SequenceInTB']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL= "SELECT COUNT(group_) AS total_groups FROM chartmaster WHERE group_='" . $_GET['SelectedAccountGroup'] . "'";
	$MyRow = DB_fetch_array(DB_query($SQL));
	if ($MyRow['total_groups']>0) {
		prnMsg( __('Cannot delete: accounts exist in this group.'),'warn');
		echo '<div class="centre"><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <input type="hidden" name="OriginalAccountGroup" value="' . $_GET['SelectedAccountGroup'] . '" />
                <div class="db-card" style="max-width:400px; margin:1rem auto; padding:1rem;">
                    <label class="db-label">Move Accounts To:</label>
                    <select name="DestinyAccountGroup" class="db-select">';
                    $GroupResult = DB_query("SELECT groupname FROM accountgroups");
                    while($GroupRow = DB_fetch_array($GroupResult) ) echo '<option value="'.$GroupRow['groupname'].'">'.$GroupRow['groupname'].'</option>';
                    echo '</select>
                    <button type="submit" name="MoveGroup" class="db-btn db-btn-primary" style="margin-top:1rem;">Migrate & Allow Delete</button>
                </div></form></div>';
	} else {
		$MyRow = DB_fetch_array(DB_query("SELECT COUNT(groupname) groupnames FROM accountgroups WHERE parentgroupname = '" . $_GET['SelectedAccountGroup'] . "'"));
		if ($MyRow['groupnames']>0) {
			prnMsg( __('Cannot delete: this is a parent group.'),'warn');
		} else {
			DB_query("DELETE FROM accountgroups WHERE groupname='" . $_GET['SelectedAccountGroup'] . "'");
			prnMsg( $_GET['SelectedAccountGroup'] . ' ' . __('deleted'),'success');
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
    .db-layout { display: grid; grid-template-columns: 1fr 380px; gap: 2rem; align-items: start; }
    @media (max-width: 1200px) { .db-layout { grid-template-columns: 1fr; } }
    .db-card { background: #fff; border-radius: 12px; border: 1px solid var(--db-border); box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .db-card-header { padding: 1rem 1.25rem; background: var(--db-primary-soft); border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin: 0; }
    .db-card-body { padding: 1.25rem; }
    .db-form-group { margin-bottom: 1.25rem; }
    .db-label { display: block; font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; }
    .db-input, .db-select { width: 100%; padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); font-size: 0.875rem; background: #fdfdfd; }
    .db-help { font-size: 0.7rem; color: #64748b; margin-top: 0.35rem; }
    .db-btn { display: inline-flex; align-items: center; justify-content: center; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; border: 1px solid transparent; gap: 0.5rem; width: 100%; }
    .db-btn-primary { background: var(--db-primary); color: #fff; }
    .db-btn-outline { border-color: var(--db-border); background: #fff; color: #475569; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 850; text-transform: uppercase; font-size: 0.7rem; padding: 1rem; text-align: left; border-bottom: 1px solid var(--db-border); }
    .db-table td { padding: 0.85rem 1rem; border-bottom: 1px solid var(--db-border); color: #475569; }
    .db-badge { padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700; background: #f1f5f9; color: #475569; }
    .db-badge-green { background: #dcfce7; color: #166534; }
</style>';

echo '<div class="db-page">';
echo '<header class="db-header"><div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Maintenance') . '</div><h1 class="db-title">' . $Title . '</h1></header>';

echo '<div class="db-layout">';

// MAIN CONTENT: LISTING
echo '<main class="db-main">';
            // Pagination Logic
            $ItemsPerPage = 15;
            $Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
            $Offset = ($Page - 1) * $ItemsPerPage;

            $TotalRes = DB_query("SELECT COUNT(*) FROM accountgroups");
            $TotalRows = DB_fetch_row($TotalRes)[0];
            $TotalPages = ceil($TotalRows / $ItemsPerPage);

            echo '<div class="db-card">
                    <div class="db-card-header"><i class="fas fa-sitemap" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . __('Group Hierarchy') . ' (' . $TotalRows . ')</h3></div>
                    <div style="overflow-x:auto;">
                        <table class="db-table">
                            <thead><tr><th>Group Name</th><th>Section</th><th>TB Seq</th><th>P&L</th><th>Parent</th><th style="text-align:right">Actions</th></tr></thead>
                            <tbody>';

            $Result = DB_query("SELECT groupname, sectionname, sequenceintb, pandl, parentgroupname FROM accountgroups LEFT JOIN accountsection ON sectionid = sectioninaccounts ORDER BY sequenceintb LIMIT $ItemsPerPage OFFSET $Offset");
            while($MyRow = DB_fetch_array($Result)) {
                $PandLText = ($MyRow['pandl'] != 0 ? '<span class="db-badge db-badge-green">'.__('Yes').'</span>' : '<span class="db-badge">'.__('No').'</span>');
                echo '<tr>
                        <td style="font-weight:700; color:var(--db-primary-dark);">' . $MyRow['groupname'] . '</td>
                        <td>' . $MyRow['sectionname'] . '</td>
                        <td>' . $MyRow['sequenceintb'] . '</td>
                        <td>' . $PandLText . '</td>
                        <td style="font-size:0.75rem; color:#64748b;">' . $MyRow['parentgroupname'] . '</td>
                        <td style="text-align:right;"><div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                            <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; font-size:0.7rem; width:auto;" href="'.htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedAccountGroup='.urlencode($MyRow['groupname'])).'&Page='.$Page.'"><i class="fas fa-edit"></i></a>
                            <a class="db-btn db-btn-outline" style="padding:0.4rem 0.6rem; font-size:0.7rem; color:#dc2626; width:auto;" href="'.htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedAccountGroup='.urlencode($MyRow['groupname']).'&delete=1&Page='.$Page).'" onclick="return confirm(\''.__('Delete group?').'\');"><i class="fas fa-trash"></i></a>
                        </div></td></tr>';
            }
            echo '</tbody></table></div>';
            
            // Pagination Controls
            if ($TotalPages > 1) {
                echo '<div style="padding: 1rem; border-top: 1px solid var(--db-border); display:flex; justify-content:space-between; align-items:center; background: #fff;">';
                echo '<div style="font-size: 0.75rem; color: #64748b; font-weight: 700;">Page ' . $Page . ' of ' . $TotalPages . '</div>';
                echo '<div style="display:flex; gap: 8px;">';
                if ($Page > 1) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?Page='.($Page-1).'" class="db-btn db-btn-outline" style="width: auto; padding: 0.5rem 1rem; text-decoration:none;">Previous</a>';
                if ($Page < $TotalPages) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF']).'?Page='.($Page+1).'" class="db-btn db-btn-outline" style="width: auto; padding: 0.5rem 1rem; text-decoration:none;">Next</a>';
                echo '</div></div>';
            }
            echo '</div></main>';

// SIDEBAR: FORM
echo '<aside class="db-aside">';
if (isset($_GET['SelectedAccountGroup'])) {
    $SQL = "SELECT groupname, sectioninaccounts, sequenceintb, pandl, parentgroupname FROM accountgroups WHERE groupname='" . $_GET['SelectedAccountGroup'] ."'";
    $MyRow = DB_fetch_array(DB_query($SQL));
    $_POST['GroupName'] = $MyRow['groupname'];
    $_POST['SectionInAccounts'] = $MyRow['sectioninaccounts'];
    $_POST['SequenceInTB'] = $MyRow['sequenceintb'];
    $_POST['PandL'] = $MyRow['pandl'];
    $_POST['ParentGroupName'] = $MyRow['parentgroupname'];
}
if (!isset($_POST['GroupName'])) { $_POST['GroupName']=''; $_POST['SectionInAccounts']=''; $_POST['SequenceInTB']=''; $_POST['PandL']=''; $_POST['SelectedAccountGroup']=''; }

echo '<div class="db-card"><div class="db-card-header"><i class="fas fa-plus-circle" style="color:var(--db-primary)"></i><h3 class="db-card-title">' . (isset($_GET['SelectedAccountGroup'])?__('Edit Group'):__('New Group')) . '</h3></div>';
echo '<div class="db-card-body"><form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF']) . '"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
if (isset($_GET['SelectedAccountGroup'])) echo '<input name="SelectedAccountGroup" type="hidden" value="', $_GET['SelectedAccountGroup'], '" />';

echo '<div class="db-form-group"><label class="db-label">Group Name</label><input class="db-input" maxlength="30" name="GroupName" required value="' . $_POST['GroupName'] . '" /></div>';
echo '<div class="db-form-group"><label class="db-label">Parent Group</label><select name="ParentGroupName" class="db-select"><option value="">Top Level Group</option>';
$GroupResult = DB_query("SELECT groupname FROM accountgroups");
while($GR = DB_fetch_array($GroupResult)) echo '<option '.($_POST['ParentGroupName']==$GR['groupname']?'selected':'').' value="'.$GR['groupname'].'">'.$GR['groupname'].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Section</label><select name="SectionInAccounts" class="db-select">';
$SecResult = DB_query("SELECT sectionid, sectionname FROM accountsection ORDER BY sectionid");
while($SR = DB_fetch_array($SecResult)) echo '<option '.($_POST['SectionInAccounts']==$SR['sectionid']?'selected':'').' value="'.$SR['sectionid'].'">'.$SR['sectionname'].'</option>';
echo '</select></div>';

echo '<div class="db-form-group"><label class="db-label">Profit and Loss</label><select name="PandL" class="db-select"><option '.($_POST['PandL']=='0'?'selected':'').' value="0">No (Balance Sheet)</option><option '.($_POST['PandL']=='1'?'selected':'').' value="1">Yes</option></select></div>';
echo '<div class="db-form-group"><label class="db-label">Sequence In TB</label><input class="db-input" maxlength="4" name="SequenceInTB" required value="' . $_POST['SequenceInTB'] . '" /></div>';

echo '<button type="submit" name="submit" class="db-btn db-btn-primary"><i class="fas fa-check"></i> ' . (isset($_GET['SelectedAccountGroup'])?__('Update'):__('Insert')) . '</button>';
if (isset($_GET['SelectedAccountGroup'])) echo '<a href="'.basename(__FILE__).'" class="db-btn db-btn-outline" style="margin-top:0.5rem; text-decoration:none;">Cancel</a>';
echo '</form></div></div></aside>';

echo '</div></div>';

include(__DIR__ . '/includes/footer.php');
?>
