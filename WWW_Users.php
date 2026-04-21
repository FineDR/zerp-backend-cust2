<?php

// Entry of users and security settings of users.

require(__DIR__ . '/includes/session.php');

$Title = __('Users Maintenance');
$ViewTopic = 'GettingStarted';
$BookMark = 'UserMaintenance';


// Inject premium styles for the Architect workspace
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; }
	
	/* Architect Workspace Overrides */
	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-sm);
		overflow: hidden;
	}
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
		padding: 12px 28px; border-radius: 8px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }

    /* Form Styles */
    .db-form-section { margin-bottom: 32px; }
    .db-form-label {
        font-size: 0.72rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 1.2px; 
        color: #065f46; 
        display: block; 
        margin-bottom: 8px;
    }
    .db-input {
        width: 100%; border-radius: 8px; height: 50px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 16px; box-sizing: border-box; background: #ffffff;
    }
    .db-input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }
    
    /* Responsive Layout */
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 280px 1fr; 
        gap: 32px; 
        align-items: start; 
    }
    
    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 280px 1fr; gap: 24px; }
    }
    
    @media (max-width: 992px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .db-sidebar { position: static; width: 100%; }
        .premium-header { flex-direction: column; align-items: flex-start; gap: 20px; }
        .db-header-actions { width: 100%; }
        .architect-btn { width: 100% !important; justify-content: center; }
        .db-page { padding: var(--space-4) var(--space-2); }
    }

    .module-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 16px;
    }
    
    /* Card Grids */
    .card-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .card-grid-fill { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
    
    .db-table-wrapper { overflow-x: auto; border-radius: 12px; }
    .db-table th { white-space: nowrap; }

    @media (max-width: 768px) {
        .card-grid-2 { grid-template-columns: 1fr; }
        .db-card-header { padding: 15px 20px; }
        .db-card-body { padding: 20px !important; }
        .premium-header h1 { font-size: 1.8rem !important; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.6;">
						<i class="fas fa-home"></i> ' . __('Setup') . ' <i class="fas fa-chevron-right" style="font-size: 0.6rem;"></i> ' . __('Identity') . '
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Manage system access, security roles, and user preferences') . '</p>
						</div>
					</div>
				</div>
				<div class="db-header-actions">';

if (isset($SelectedUser)) {
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="architect-btn">
            <i class="fas fa-users"></i> ' . __('Review Existing Users') . '
          </a>';
} else {
    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?NewUser=Yes" class="architect-btn">
            <i class="fas fa-plus"></i> ' . __('Create New User') . '
          </a>';
}

echo '				</div>
			</div>
		</div>';

if ($AllowDemoMode) {
	prnMsg(__('Demo mode is currently active, which disables the security model administration'), 'warn');
	echo '</div>'; // End db-page
	include(__DIR__ . '/includes/footer.php');
	exit();
}
$ModuleList = array(
	__('Sales'),
	__('Receivables'),
	__('Purchases'),
	__('Payables'),
	__('Inventory'),
	__('Manufacturing'),
	__('General Ledger'),
	__('Asset Manager'),
	__('Petty Cash'),
	__('Setup'),
	__('Utilities')
);
$ModuleListLabel = array(
	__('Display Sales module'),
	__('Display Receivables module'),
	__('Display Purchases module'),
	__('Display Payables module'),
	__('Display Inventory module'),
	__('Display Manufacturing module'),
	__('Display General Ledger module'),
	__('Display Asset Manager module'),
	__('Display Petty Cash module'),
	__('Display Setup module'),
	__('Display Utilities module')
);
$PDFLanguages = array(
	__('Latin Western Languages - Times'),
	__('Eastern European Russian Japanese Korean Hebrew Arabic Thai'),
	__('Chinese'),
	__('Free Serif')
);

include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/LanguagesArray.php');

// Make an array of the security roles
$SQL = "SELECT secroleid,
				secrolename
		FROM securityroles
		ORDER BY secrolename";

$Sec_Result = DB_query($SQL);
$SecurityRoles = array();
// Now load it into an a ray using Key/Value pairs
while( $Sec_row = DB_fetch_row($Sec_Result) ) {
	$SecurityRoles[$Sec_row[0]] = $Sec_row[1];
}
DB_free_result($Sec_Result);

if (isset($_GET['SelectedUser'])) {
	$SelectedUser = $_GET['SelectedUser'];
} elseif (isset($_POST['SelectedUser'])) {
	$SelectedUser = $_POST['SelectedUser'];
}

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (mb_strlen($_POST['UserID'])<4) {
		$InputError = 1;
		prnMsg(__('The user ID entered must be at least 4 characters long'), 'error');
	} elseif (ContainsIlLegalCharacters($_POST['UserID'])) {
		$InputError = 1;
		prnMsg(__('User names cannot contain any of the following characters') . " - ' &amp; + \" \\ " . __('or a space'), 'error');
	} elseif (mb_strlen($_POST['Password'])<5) {
		if (!$SelectedUser) {
			$InputError = 1;
			prnMsg(__('The password entered must be at least 5 characters long'), 'error');
		}
	} elseif (mb_strstr($_POST['Password'],$_POST['UserID'])!= false) {
		$InputError = 1;
		prnMsg(__('The password cannot contain the user id'), 'error');
	} elseif ((mb_strlen($_POST['Cust'] ?? '')>0)
				AND (mb_strlen($_POST['BranchCode'] ?? '')==0)) {
		$InputError = 1;
		prnMsg(__('If you enter a Customer Code you must also enter a Branch Code valid for this Customer'), 'error');
	} elseif ($AllowDemoMode AND $_POST['UserID'] == 'admin') {
		prnMsg(__('The demonstration user called demo cannot be modified.'), 'error');
		$InputError = 1;
	}

	if (!isset($SelectedUser)) {
		/* check to ensure the user id is not already entered */
		$Result = DB_query("SELECT userid FROM www_users WHERE userid='" . $_POST['UserID'] . "'");
		if (DB_num_rows($Result)==1) {
			$InputError =1;
			prnMsg(__('The user ID') . ' ' . $_POST['UserID'] . ' ' . __('already exists and cannot be used again'), 'error');
		}
	}

	if ((mb_strlen($_POST['BranchCode'] ?? '')>0) AND ($InputError !=1)) {
		// check that the entered branch is valid for the customer code
		$SQL = "SELECT custbranch.debtorno
				FROM custbranch
				WHERE custbranch.debtorno='" . ($_POST['Cust'] ?? '') . "'
				AND custbranch.branchcode='" . ($_POST['BranchCode'] ?? '') . "'";

		$ErrMsg = __('The check on validity of the customer code and branch failed because');
		$Result = DB_query($SQL, $ErrMsg);

		if (DB_num_rows($Result)==0) {
			prnMsg(__('The entered Branch Code is not valid for the entered Customer Code'), 'error');
			$InputError = 1;
		}
	}

	/* Make a comma separated list of modules allowed ready to update the database*/
	$i = 0;
	$ModulesAllowed = '';
	while($i < count($ModuleList)) {
		$FormVbl = 'Module_' . $i;
		$ModulesAllowed .= $_POST[($FormVbl)] . ',';
		$i++;
	}
	$_POST['ModulesAllowed']= $ModulesAllowed;

	// Initialize missing POST variables to prevent undefined array key warnings and ensure stable SQL
	if (!isset($_POST['Cust'])) $_POST['Cust'] = '';
	if (!isset($_POST['BranchCode'])) $_POST['BranchCode'] = '';
	if (!isset($_POST['SupplierID'])) $_POST['SupplierID'] = '';
	if (!isset($_POST['Salesman'])) $_POST['Salesman'] = '';
    if (!isset($_POST['Blocked'])) $_POST['Blocked'] = 0;
    if (!isset($_POST['Access'])) $_POST['Access'] = 0;
    if (!isset($_POST['CanCreateTender'])) $_POST['CanCreateTender'] = 0;
    if (!isset($_POST['Theme'])) $_POST['Theme'] = $_SESSION['Theme'];
    if (!isset($_POST['UserLanguage'])) $_POST['UserLanguage'] = $_SESSION['Language'];
    if (!isset($_POST['PDFLanguage'])) $_POST['PDFLanguage'] = 0;
    if (!isset($_POST['PageSize'])) $_POST['PageSize'] = 'A4';
    if (!isset($_POST['ShowDashboard'])) $_POST['ShowDashboard'] = 0;
    if (!isset($_POST['ShowPageHelp'])) $_POST['ShowPageHelp'] = 1;
    if (!isset($_POST['ShowFieldHelp'])) $_POST['ShowFieldHelp'] = 1;
    if (!isset($_POST['Department'])) $_POST['Department'] = 0;
    if (!isset($_POST['Timeout'])) $_POST['Timeout'] = 60;

	if (isset($SelectedUser) AND $InputError != 1) {

		/*SelectedUser could also exist if submit had not been clicked this code would not run in this case
		because submit is false of course see the delete code below*/

		if (!isset($_POST['Cust']) OR $_POST['Cust']==NULL OR $_POST['Cust']=='') {
			$_POST['Cust']='';
			$_POST['BranchCode']='';
		}

		$UpdatePassword = '';
		if ($_POST['Password'] != '') {
			$UpdatePassword = "password='" . CryptPass($_POST['Password']) . "',";
		}

		$SQL = "UPDATE www_users SET realname='" . $_POST['RealName'] . "',
						customerid='" . $_POST['Cust'] ."',
						phone='" . $_POST['Phone'] ."',
						email='" . $_POST['Email'] ."',
						timeout='" . $_POST['Timeout'] . "',
						" . $UpdatePassword . "
						branchcode='" . $_POST['BranchCode'] . "',
						supplierid='" . $_POST['SupplierID'] . "',
						salesman='" . $_POST['Salesman'] . "',
						pagesize='" . $_POST['PageSize'] . "',
						fullaccess='" . $_POST['Access'] . "',
						cancreatetender='" . $_POST['CanCreateTender'] . "',
						theme='" . $_POST['Theme'] . "',
						language ='" . $_POST['UserLanguage'] . "',
						defaultlocation='" . $_POST['DefaultLocation'] ."',
						modulesallowed='" . $ModulesAllowed . "',
						showdashboard='" . $_POST['ShowDashboard'] . "',
						showpagehelp='" . $_POST['ShowPageHelp'] . "',
						showfieldhelp='" . $_POST['ShowFieldHelp'] . "',
						blocked='" . $_POST['Blocked'] . "',
						pdflanguage='" . $_POST['PDFLanguage'] . "',
						department='" . $_POST['Department'] . "'
					WHERE userid = '". $SelectedUser . "'";

		$ErrMsg = __('The user alterations could not be processed because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('The selected user record has been updated'), 'success' );

		$_SESSION['ShowPageHelp'] = $_POST['ShowPageHelp'];
		$_SESSION['ShowFieldHelp'] = $_POST['ShowFieldHelp'];

	} elseif ($InputError !=1) {

		$SQL = "INSERT INTO www_users (
					userid,
					realname,
					customerid,
					branchcode,
					supplierid,
					salesman,
					password,
					phone,
					email,
					timeout,
					pagesize,
					fullaccess,
					cancreatetender,
					defaultlocation,
					modulesallowed,
					showdashboard,
					showpagehelp,
					showfieldhelp,
					displayrecordsmax,
					theme,
					language,
					pdflanguage,
					department)
				VALUES ('" . $_POST['UserID'] . "',
					'" . $_POST['RealName'] ."',
					'" . $_POST['Cust'] ."',
					'" . $_POST['BranchCode'] ."',
					'" . $_POST['SupplierID'] ."',
					'" . $_POST['Salesman'] . "',
					'" . CryptPass($_POST['Password']) ."',
					'" . $_POST['Phone'] . "',
					'" . $_POST['Email'] ."',
					'" . $_POST['Timeout'] ."',
					'" . $_POST['PageSize'] ."',
					'" . $_POST['Access'] . "',
					'" . $_POST['CanCreateTender'] . "',
					'" . $_POST['DefaultLocation'] ."',
					'" . $ModulesAllowed . "',
					'" . $_POST['ShowDashboard'] . "',
					'" . $_POST['ShowPageHelp'] . "',
					'" . $_POST['ShowFieldHelp'] . "',
					'" . $_SESSION['DefaultDisplayRecordsMax'] . "',
					'" . $_POST['Theme'] . "',
					'". $_POST['UserLanguage'] ."',
					'" . $_POST['PDFLanguage'] . "',
					'" . $_POST['Department'] . "')";

		$ErrMsg = __('The user insertion could not be processed because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('A new user record has been inserted'), 'success');

		$LocationSql = "INSERT INTO locationusers (loccode,
													userid,
													canview,
													canupd
												) VALUES (
													'" . $_POST['DefaultLocation'] . "',
													'" . $_POST['UserID'] . "',
													1,
													1
												)";

		$ErrMsg = __('The default user locations could not be processed because');
		$Result = DB_query($LocationSql, $ErrMsg);
		prnMsg(__('User has been authorized to use and update only his / her default location'), 'success' );

		$GLAccountsSql = "INSERT INTO glaccountusers (userid, accountcode, canview, canupd)
						 SELECT '" . $_POST['UserID'] . "', chartmaster.accountcode,1,1
						 FROM chartmaster;	";

		$ErrMsg = __('The default user GL Accounts could not be processed because');
		$Result = DB_query($GLAccountsSql, $ErrMsg);
		prnMsg(__('User has been authorized to use and update all GL accounts'), 'success' );
	}

	if ($InputError!=1) {
		unset($_POST['UserID']);
		unset($_POST['RealName']);
		unset($_POST['Cust']);
		unset($_POST['BranchCode']);
		unset($_POST['SupplierID']);
		unset($_POST['Salesman']);
		unset($_POST['Phone']);
		unset($_POST['Email']);
		unset($_POST['Timeout']);
		unset($_POST['Password']);
		unset($_POST['PageSize']);
		unset($_POST['Access']);
		unset($_POST['CanCreateTender']);
		unset($_POST['DefaultLocation']);
		unset($_POST['ModulesAllowed']);
		unset($_POST['ShowDashboard']);
		unset($_POST['ShowPageHelp']);
		unset($_POST['ShowFieldHelp']);
		unset($_POST['Blocked']);
		unset($_POST['Theme']);
		unset($_POST['UserLanguage']);
		unset($_POST['PDFLanguage']);
		unset($_POST['Department']);
		unset($SelectedUser);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

	if ($AllowDemoMode AND $SelectedUser == 'admin') {
		prnMsg(__('The demonstration user called demo cannot be deleted'), 'error');
	} else {
		$SQL = "SELECT userid FROM audittrail where userid='" . $SelectedUser ."'";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result)!=0) {
			prnMsg(__('Cannot delete user as entries already exist in the audit trail'), 'warn');
		} else {
			$SQL = "DELETE FROM locationusers WHERE userid='" . $SelectedUser . "'";
			$ErrMsg = __('The Location - User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM glaccountusers WHERE userid='" . $SelectedUser . "'";
			$ErrMsg = __('The GL Account - User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM bankaccountusers WHERE userid='" . $SelectedUser . "'";
			$ErrMsg = __('The Bank Accounts - User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM purchorderauth WHERE userid='" . $SelectedUser . "'";
			$ErrMsg = __('The Purchase Orders Authority could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM sessions WHERE userid = '" . $SelectedUser . "'";
			$ErrMsg = __('The Sessions User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM session_data WHERE userid = '" . $SelectedUser . "'";
			$ErrMsg = __('The Session Data User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);

			$SQL = "DELETE FROM www_users WHERE userid='" . $SelectedUser . "'";
			$ErrMsg = __('The User could not be deleted because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg(__('User Deleted'),'info');
		}
		unset($SelectedUser);
	}

}


/* --------------------------------------------------------------------------------------------------
   MAIN DASHBOARD RENDERER
   -------------------------------------------------------------------------------------------------- */

/* VIEW: LIST REGISTRY */
if (!isset($SelectedUser) && !isset($_GET['NewUser'])) {

    echo '<div class="db-bottom-layout">';

    echo '<aside class="db-sidebar">';
    /* Sidebar Card: Registry Actions */
    echo '<div class="db-card">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-tools"></i>' . __('Maintenance') . '</h3>
            </div>
            <div style="padding: 24px;">
                <p style="font-size: 0.85rem; color: #065f46; font-weight: 500; margin-bottom: 24px;">' . __('User accounts define the security perimeters and environmental preferences for each employee.') . '</p>
                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?NewUser=Yes" class="architect-btn" style="width: 100%; justify-content: center;">
                    <i class="fas fa-user-plus"></i> ' . __('Add New Member') . '
                </a>
            </div>
        </div>';
    echo '</aside>';

    echo '<main class="db-main" style="display: flex; flex-direction: column; gap: 32px; overflow: hidden;">';
	echo '<div class="db-card">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Active User Registry') . '</h3>
            </div>
            <div class="db-table-wrapper">
                <table class="db-table">
                    <thead>
                        <tr style="height: 60px;">
                            <th style="padding-left: 30px;">', __('Login'), '</th>
                            <th>', __('Full Name'), '</th>
                            <th>', __('Role'), '</th>
                            <th>', __('Email'), '</th>
                            <th>', __('Last Visit'), '</th>
                            <th>', __('Language'), '</th>
                            <th style="padding-right: 30px;" class="noPrint">', __('Actions'), '</th>
                        </tr>
                    </thead>
                    <tbody>';

	$SQL = "SELECT userid,
					realname,
					phone,
					email,
					timeout,
					customerid,
					branchcode,
					supplierid,
					salesman,
					lastvisitdate,
					fullaccess,
					cancreatetender,
					pagesize,
					theme,
					language
				FROM www_users";

	if ($_SESSION['AccessLevel'] != 8){
		$SQL = $SQL . " WHERE fullaccess != '8'";
	}
	$SQL = $SQL . " ORDER BY userid";
	$Result = DB_query($SQL);

	while ($MyRow = DB_fetch_array($Result)) {
		if (!isset($MyRow['lastvisitdate'])) {
			$LastVisitDate = __('Never');
		} else {
			$LastVisitDate = ConvertSQLDate($MyRow['lastvisitdate']);
		}
        
        $EditLink = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedUser=' . $MyRow['userid'];
        $DeleteLink = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedUser=' . $MyRow['userid'] . '&amp;delete=1';

		echo '<tr style="height: 65px;">
				<td style="padding-left: 30px; font-weight: 800; color: #059669;">
                    <a href="' . $EditLink . '" style="color: #059669; text-decoration: none; border-bottom: 1px dashed transparent; transition: all 0.2s;" onmouseover="this.style.borderBottomColor=\'#059669\'" onmouseout="this.style.borderBottomColor=\'transparent\'">' . $MyRow['userid'] . '</a>
                </td>
				<td style="font-weight: 600;">' . $MyRow['realname'] . '</td>
				<td><span style="background: #f3f4f6; color: #4b5563; padding: 4px 10px; border-radius: 8px; font-size: 0.7rem; font-weight: 800;">' . $SecurityRoles[$MyRow['fullaccess']] . '</span></td>
				<td style="color: #6b7280; font-size: 0.85rem;">' . $MyRow['email'] . '</td>
				<td style="font-size: 0.85rem;">' . $LastVisitDate . '</td>
				<td style="font-size: 0.85rem;">' . $LanguagesArray[$MyRow['language']]['LanguageName'] . '</td>
				<td style="padding-right: 30px;" class="noPrint">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <a href="' . $EditLink . '" title="' . __('Edit') . '" style="color: #059669; text-decoration: none; font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 4px;">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="' . $DeleteLink . '" title="' . __('Delete') . '" onclick="return confirm(\'' . __('Are you sure you wish to delete this user?') . '\');" style="color: #ef4444; text-decoration: none; font-weight: 800; font-size: 0.9rem; display: flex; align-items: center; gap: 4px;">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                </td>
			</tr>';
	}
	echo '</tbody></table></div></div>';
    echo '</main>';
    echo '</div>'; // End db-bottom-layout

} else {
    /* VIEW: EDIT/CREATE FORM */

    if (isset($SelectedUser)) {
        //editing an existing User
        $SQL = "SELECT * FROM www_users WHERE userid='" . $SelectedUser . "'";
        $Result = DB_query($SQL);
        $MyRow = DB_fetch_array($Result);

        $_POST['UserID'] = $MyRow['userid'];
        $_POST['RealName'] = $MyRow['realname'];
        $_POST['Phone'] = $MyRow['phone'];
        $_POST['Email'] = $MyRow['email'];
        $_POST['Timeout'] = $MyRow['timeout'];
        $_POST['Cust'] = $MyRow['customerid'];
        $_POST['BranchCode'] = $MyRow['branchcode'];
        $_POST['SupplierID'] = $MyRow['supplierid'];
        $_POST['Salesman'] = $MyRow['salesman'];
        $_POST['PageSize'] = $MyRow['pagesize'];
        $_POST['Access'] = $MyRow['fullaccess'];
        $_POST['CanCreateTender'] = $MyRow['cancreatetender'];
        $_POST['DefaultLocation'] = $MyRow['defaultlocation'];
        $_POST['ModulesAllowed'] = $MyRow['modulesallowed'];
        $_POST['ShowDashboard'] = $MyRow['showdashboard'];
        $_POST['ShowPageHelp'] = $MyRow['showpagehelp'];
        $_POST['ShowFieldHelp'] = $MyRow['showfieldhelp'];
        $_POST['Blocked'] = $MyRow['blocked'];
        $_POST['Theme'] = $MyRow['theme'];
        $_POST['UserLanguage'] = $MyRow['language'];
        $_POST['PDFLanguage'] = $MyRow['pdflanguage'];
        $_POST['Department'] = $MyRow['department'];
    } else {
        // New User defaults
        $i=0;
        if (!isset($_POST['ModulesAllowed'])) {
            $_POST['ModulesAllowed']='';
            foreach($ModuleList as $ModuleName) {
                if ($i>0) $_POST['ModulesAllowed'] .=',';
                $_POST['ModulesAllowed'] .= '1';
                $i++;
            }
        }
        $_POST['ShowDashboard'] = 0;
        $_POST['ShowPageHelp'] = 1;
        $_POST['ShowFieldHelp'] = 1;
        $_POST['UserID'] = ''; // Ensure UserID is empty for new users
        if (!isset($_POST['Timeout'])) $_POST['Timeout']=60;
    }

    if (isset($SelectedUser) || isset($_GET['NewUser'])) {

        echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" style="display: contents;">';
        echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

        if (isset($SelectedUser)) {
            echo '<input type="hidden" name="SelectedUser" value="' . $SelectedUser . '" />';
            echo '<input type="hidden" name="UserID" value="' . $_POST['UserID'] . '" />';
        }
        echo '<input type="hidden" name="ModulesAllowed" value="' . ($_POST['ModulesAllowed'] ?? '') . '" />';

        echo '<div class="db-bottom-layout">
                <aside class="db-sidebar">
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Status') . '</h3>
                        </div>
                        <div style="padding: 24px;">
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280;">' . __('Mode') . '</span>
                                    <span class="db-badge ' . (isset($SelectedUser) ? 'db-badge-info' : 'db-badge-success') . '">' . (isset($SelectedUser) ? __('Editing') : __('Creating')) . '</span>
                                </div>';
        if (isset($SelectedUser)) {
            echo '              <div style="display: flex; justify-content: space-between;">
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #6b7280;">' . __('ID') . '</span>
                                    <span style="font-size: 0.85rem; font-weight: 900; color: #064e3b;">' . $_POST['UserID'] . '</span>
                                </div>';
        }
        echo '              </div>
                            <hr style="margin: 24px 0; border: 0; border-top: 1px solid #f3f4f6;" />
                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; justify-content: center;">
                                <i class="fas fa-save"></i> ' . __('Commit Changes') . '
                            </button>
                        </div>
                    </div>
                </aside>
                <main class="db-main" style="display: flex; flex-direction: column; gap: 32px; overflow: hidden;">';

        /* CARD 1: IDENTITY & SECURITY */
        echo '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-shield-alt"></i> ' . __('Security & Identity') . '</h3>
                </div>
                <div class="db-card-body card-grid-2" style="padding: 30px;">';

        if (!isset($SelectedUser)) {
            echo '<div>
                    <label class="db-form-label">' . __('User Login ID') . '</label>
                    <input pattern="(?!^([aA]{1}[dD]{1}[mM]{1}[iI]{1}[nN]{1})$)[^?+.&\\>< ]{4,}" type="text" required="required" name="UserID" class="db-input" placeholder="'.__('At least 4 characters').'" />
                  </div>';
        } else {
            echo '<div>
                    <label class="db-form-label">' . __('User Login ID') . '</label>
                    <div style="height: 50px; display: flex; align-items: center; font-weight: 800; color: #064e3b; padding: 0 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #f3f4f6;">' . $_POST['UserID'] . '</div>
                  </div>';
        }

        echo '<div>
                <label class="db-form-label">' . __('User Professional Role') . '</label>
                <select name="Access" class="db-input">';
        foreach($SecurityRoles as $SecKey => $SecVal) {
            echo '<option ' . (($_POST['Access'] ?? '') == $SecKey ? 'selected="selected"' : '') . ' value="' . $SecKey . '">' . $SecVal . '</option>';
        }
        echo '  </select>
                <input type="hidden" name="ID" value="'.$_SESSION['UserID'].'" />
              </div>

              <div>
                <label class="db-form-label">' . __('Password') . '</label>
                <input type="password" pattern=".{5,}" name="Password" ' . (!isset($SelectedUser) ? 'required="required"' : '') . ' class="db-input" placeholder="'.__('At least 5 characters').'" />
              </div>
              
              <div>
                <label class="db-form-label">' . __('Account Status') . '</label>
                <select name="Blocked" class="db-input">
                    <option value="0" ' . (($_POST['Blocked'] ?? 0) == 0 ? 'selected="selected"' : '') . '>' . __('Active') . '</option>
                    <option value="1" ' . (($_POST['Blocked'] ?? 1) == 1 ? 'selected="selected"' : '') . '>' . __('Blocked / Suspended') . '</option>
                </select>
              </div>
            </div>
        </div>';

        /* CARD 2: PERSONAL PROFILE */
        echo '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-address-card"></i> ' . __('Personnel Profile') . '</h3>
                </div>
                <div class="db-card-body card-grid-2" style="padding: 30px;">
                    <div style="grid-column: span 2;">
                        <label class="db-form-label">' . __('Full Display Name') . '</label>
                        <input type="text" name="RealName" required="required" value="' . ($_POST['RealName'] ?? '') . '" class="db-input" />
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Professional Email') . '</label>
                        <input type="email" name="Email" required="required" value="' . ($_POST['Email'] ?? '') . '" class="db-input" />
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Contact Telephone') . '</label>
                        <input type="tel" name="Phone" value="' . ($_POST['Phone'] ?? '') . '" class="db-input" />
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Session Timeout') . '</label>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <input type="number" name="Timeout" required="required" value="' . ($_POST['Timeout'] ?? 60) . '" class="db-input" style="width: 100px;" />
                            <span style="font-weight: 700; color: #6b7280;">' . __('Minutes') . '</span>
                        </div>
                    </div>
                </div>
              </div>';

        /* CARD 3: WORKSPACE & PREFERENCES */
        echo '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-desktop"></i> ' . __('Workspace & Preferences') . '</h3>
                </div>
                <div class="db-card-body card-grid-fill" style="padding: 30px;">
                    <div>
                        <label class="db-form-label">' . __('System Language') . '</label>
                        <select name="UserLanguage" class="db-input">';
        foreach($LanguagesArray as $LanguageEntry => $LanguageName) {
            echo '<option ' . (($_POST['UserLanguage'] ?? '') == $LanguageEntry ? 'selected="selected"' : '') . ' value="' . $LanguageEntry . '">' . $LanguageName['LanguageName'] . '</option>';
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Visual Theme') . '</label>
                        <select name="Theme" class="db-input">';
        $ThemeDirectories = scandir($PathPrefix . 'css/');
        foreach($ThemeDirectories as $ThemeName) {
            if (is_dir('css/' . $ThemeName) AND $ThemeName != '.' AND $ThemeName != '..' AND $ThemeName != '.svn') {
                echo '<option ' . (($_POST['Theme'] ?? '') == $ThemeName ? 'selected="selected"' : '') . ' value="' . $ThemeName . '">' . $ThemeName . '</option>';
            }
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('PDF Language') . '</label>
                        <select name="PDFLanguage" class="db-input">';
        for($i=0;$i<count($PDFLanguages);$i++) {
            echo '<option ' . (($_POST['PDFLanguage'] ?? 0) == $i ? 'selected="selected"' : '') . ' value="' . $i .'">' . $PDFLanguages[$i] . '</option>';
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Reports Page Size') . '</label>
                        <select name="PageSize" class="db-input">
                            <option value="A4" ' . (($_POST['PageSize'] ?? '') == 'A4' ? 'selected="selected"' : '') . '>A4</option>
                            <option value="A3" ' . (($_POST['PageSize'] ?? '') == 'A3' ? 'selected="selected"' : '') . '>A3</option>
                            <option value="A3_Landscape" ' . (($_POST['PageSize'] ?? '') == 'A3_Landscape' ? 'selected="selected"' : '') . '>A3 Landscape</option>
                            <option value="Letter" ' . (($_POST['PageSize'] ?? '') == 'Letter' ? 'selected="selected"' : '') . '>Letter</option>
                            <option value="Letter_Landscape" ' . (($_POST['PageSize'] ?? '') == 'Letter_Landscape' ? 'selected="selected"' : '') . '>Letter Landscape</option>
                            <option value="Legal" ' . (($_POST['PageSize'] ?? '') == 'Legal' ? 'selected="selected"' : '') . '>Legal</option>
                            <option value="Legal_Landscape" ' . (($_POST['PageSize'] ?? '') == 'Legal_Landscape' ? 'selected="selected"' : '') . '>Legal Landscape</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Display Dashboard') . '</label>
                        <select name="ShowDashboard" class="db-input">
                            <option value="0" ' . (($_POST['ShowDashboard'] ?? 0) == 0 ? 'selected="selected"' : '') . '>' . __('No') . '</option>
                            <option value="1" ' . (($_POST['ShowDashboard'] ?? 1) == 1 ? 'selected="selected"' : '') . '>' . __('Yes') . '</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Show Page Help') . '</label>
                        <select name="ShowPageHelp" class="db-input">
                            <option value="0" ' . (($_POST['ShowPageHelp'] ?? 1) == 0 ? 'selected="selected"' : '') . '>' . __('No') . '</option>
                            <option value="1" ' . (($_POST['ShowPageHelp'] ?? 1) == 1 ? 'selected="selected"' : '') . '>' . __('Yes') . '</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Show Field Help') . '</label>
                        <select name="ShowFieldHelp" class="db-input">
                            <option value="0" ' . (($_POST['ShowFieldHelp'] ?? 1) == 0 ? 'selected="selected"' : '') . '>' . __('No') . '</option>
                            <option value="1" ' . (($_POST['ShowFieldHelp'] ?? 1) == 1 ? 'selected="selected"' : '') . '>' . __('Yes') . '</option>
                        </select>
                    </div>
                </div>
              </div>';

        /* CARD 4: BUSINESS LOGIC & RESTRICTIONS */
        echo '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-lock"></i> ' . __('Business Logic & Restrictions') . '</h3>
                </div>
                <div class="db-card-body card-grid-fill" style="padding: 30px;">
                    <div>
                        <label class="db-form-label">' . __('Default Inventory Location') . '</label>
                        <select name="DefaultLocation" class="db-input">';
        $LocSQL = "SELECT loccode, locationname FROM locations";
        $LocResult = DB_query($LocSQL);
        while($LocRow=DB_fetch_array($LocResult)) {
            echo '<option ' . (($_POST['DefaultLocation'] ?? '') == $LocRow['loccode'] ? 'selected="selected"' : '') . ' value="' . $LocRow['loccode'] . '">' . $LocRow['locationname'] . '</option>';
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Restrict to Sales Person') . '</label>
                        <select name="Salesman" class="db-input">
                            <option value="">' . __('Not restricted') . '</option>';
        $SmSQL = "SELECT salesmancode, salesmanname FROM salesman WHERE current = 1 ORDER BY salesmanname";
        $SmResult = DB_query($SmSQL);
        while($SmRow=DB_fetch_array($SmResult)) {
            echo '<option ' . (($_POST['Salesman'] ?? '') == $SmRow['salesmancode'] ? 'selected="selected"' : '') . ' value="' . $SmRow['salesmancode'] . '">' . $SmRow['salesmanname'] . '</option>';
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Internal Department') . '</label>
                        <select name="Department" class="db-input">
                            <option value="0">' . __('Any Department') . '</option>';
        $DeptSQL = "SELECT departmentid, description FROM departments ORDER BY description";
        $DeptResult = DB_query($DeptSQL);
        while($DeptRow=DB_fetch_array($DeptResult)) {
            echo '<option ' . (($_POST['Department'] ?? '') == $DeptRow['departmentid'] ? 'selected="selected"' : '') . ' value="' . $DeptRow['departmentid'] . '">' . $DeptRow['description'] . '</option>';
        }
        echo '          </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Audit Tender Rights') . '</label>
                        <select name="CanCreateTender" class="db-input">
                            <option value="0" ' . (($_POST['CanCreateTender'] ?? 0) == 0 ? 'selected="selected"' : '') . '>' . __('No') . '</option>
                            <option value="1" ' . (($_POST['CanCreateTender'] ?? 1) == 1 ? 'selected="selected"' : '') . '>' . __('Yes') . '</option>
                        </select>
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Linked Customer Code') . '</label>
                        <input type="text" name="Cust" value="' . ($_POST['Cust'] ?? '') . '" class="db-input" placeholder="' . __('Optional') . '" />
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Linked Branch Code') . '</label>
                        <input type="text" name="BranchCode" value="' . ($_POST['BranchCode'] ?? '') . '" class="db-input" placeholder="' . __('Optional') . '" />
                    </div>
                    <div>
                        <label class="db-form-label">' . __('Linked Supplier Code') . '</label>
                        <input type="text" name="SupplierID" value="' . ($_POST['SupplierID'] ?? '') . '" class="db-input" placeholder="' . __('Optional') . '" />
                    </div>
                </div>
              </div>';

        /* CARD 5: MODULE PERMISSIONS */
        echo '<div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title"><i class="fas fa-th-large"></i> ' . __('System Module Access') . '</h3>
                </div>
                <div style="padding: 30px;">
                    <div class="module-grid">';
        
        $ModulesAllowedArr = explode(',', ($_POST['ModulesAllowed'] ?? ''));
        foreach($ModuleList as $i => $ModuleName) {
            $allowed = (isset($ModulesAllowedArr[$i]) && $ModulesAllowedArr[$i] == 1);
            echo '<div class="module-card" style="background: #f9fafb; padding: 16px; border-radius: 12px; border: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; color: #064e3b; font-size: 0.85rem;">' . $ModuleName . '</span>
                    <select name="Module_' . $i . '" style="border: 1px solid #d1fae5; border-radius: 8px; padding: 4px 8px; font-weight: 700; color: ' . ($allowed ? '#059669' : '#ef4444') . ';">
                        <option value="1" ' . ($allowed ? 'selected="selected"' : '') . '>' . __('Allowed') . '</option>
                        <option value="0" ' . (!$allowed ? 'selected="selected"' : '') . '>' . __('Restricted') . '</option>
                    </select>
                  </div>';
        }
        echo '      </div>
                </div>
              </div>';

        echo '	</main>
            </div>'; // End db-bottom-layout
        echo '</form>';
    }
}

echo '</div>'; // End db-page

include(__DIR__ . '/includes/footer.php');
