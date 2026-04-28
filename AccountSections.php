<?php

/* Defines the sections in the general ledger reports */

require(__DIR__ . '/includes/session.php');

$Title = __('Account Sections');
$ViewTopic = 'GeneralLedger';
$BookMark = 'AccountSections';
include(__DIR__ . '/includes/header.php');

// SOME TEST TO ENSURE THAT AT LEAST INCOME AND COST OF SALES ARE THERE
	$SQL = "SELECT sectionid FROM accountsection WHERE sectionid=1";
	$Result = DB_query($SQL);

	if ( DB_num_rows($Result) == 0 ) {
		$SQL = "INSERT INTO accountsection (sectionid,
											sectionname)
									VALUES (1,
											'Income')";
		$Result = DB_query($SQL);
	}

	$SQL = "SELECT sectionid FROM accountsection WHERE sectionid=2";
	$Result = DB_query($SQL);

	if ( DB_num_rows($Result) == 0 ) {
		$SQL = "INSERT INTO accountsection (sectionid,
											sectionname)
									VALUES (2,
											'Cost Of Sales')";
		$Result = DB_query($SQL);
	}
// DONE WITH MINIMUM TESTS


$Errors = array();

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test

	$InputError = 0;
	$i = 1;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if (isset($_POST['SectionID'])) {
		$SQL = "SELECT sectionid
					FROM accountsection
					WHERE sectionid='".$_POST['SectionID']."'";
		$Result = DB_query($SQL);

		if ((DB_num_rows($Result) != 0 AND !isset($_POST['SelectedSectionID']))) {
			$InputError = 1;
			prnMsg( __('The account section already exists in the database'),'error');
			$Errors[$i] = 'SectionID';
			$i++;
		}
	}
	if (ContainsIllegalCharacters($_POST['SectionName'])) {
		$InputError = 1;
		prnMsg( __('The account section name cannot contain any illegal characters') . ' ' . '" \' - &amp; or a space','error');
		$Errors[$i] = 'SectionName';
		$i++;
	}
	if (mb_strlen($_POST['SectionName']) == 0) {
		$InputError = 1;
		prnMsg( __('The account section name must contain at least one character'),'error');
		$Errors[$i] = 'SectionName';
		$i++;
	}
	if (isset($_POST['SectionID']) AND (!is_numeric($_POST['SectionID']))) {
		$InputError = 1;
		prnMsg( __('The section number must be an integer'),'error');
		$Errors[$i] = 'SectionID';
		$i++;
	}
	if (isset($_POST['SectionID']) AND mb_strpos($_POST['SectionID'],".")>0) {
		$InputError = 1;
		prnMsg( __('The section number must be an integer'),'error');
		$Errors[$i] = 'SectionID';
		$i++;
	}

	if (isset($_POST['SelectedSectionID']) AND $_POST['SelectedSectionID'] != '' AND $InputError != 1) {

		/*SelectedSectionID could also exist if submit had not been clicked this code would not run in this case cos submit is false of course see the delete code below*/

		$SQL = "UPDATE accountsection SET sectionname='" . $_POST['SectionName'] . "'
				WHERE sectionid = '" . $_POST['SelectedSectionID'] . "'";

		$Msg = __('Record Updated');
	} elseif ($InputError != 1) {

	/*SelectedSectionID is null cos no item selected on first time round so must be adding a record must be submitting new entries in the new account section form */

		$SQL = "INSERT INTO accountsection (sectionid,
											sectionname
										) VALUES (
											'" . $_POST['SectionID'] . "',
											'" . $_POST['SectionName'] ."')";
		$Msg = __('Record inserted');
	}

	if ($InputError != 1) {
		//run the SQL from either of the above possibilites
		$Result = DB_query($SQL);
		prnMsg($Msg,'success');
		unset ($_POST['SelectedSectionID']);
		unset ($_POST['SectionID']);
		unset ($_POST['SectionName']);
	}

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'accountgroups'
	$SQL= "SELECT COUNT(sectioninaccounts) AS sections FROM accountgroups WHERE sectioninaccounts='" . $_GET['SelectedSectionID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if ($MyRow['sections']>0) {
		prnMsg( __('Cannot delete this account section because general ledger accounts groups have been created using this section'),'warn');
		echo '<div>',
			'<br />', __('There are'), ' ', $MyRow['sections'], ' ', __('general ledger accounts groups that refer to this account section'),
			'</div>';

	} else {
		//Fetch section name
		$SQL = "SELECT sectionname FROM accountsection WHERE sectionid='".$_GET['SelectedSectionID'] . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);
		$SectionName = $MyRow['sectionname'];

		$SQL="DELETE FROM accountsection WHERE sectionid='" . $_GET['SelectedSectionID'] . "'";
		$Result = DB_query($SQL);
		prnMsg( $SectionName . ' ' . __('section has been deleted') . '!','success');

	} //end if account group used in GL accounts
	unset ($_GET['SelectedSectionID']);
	unset($_GET['delete']);
	unset ($_POST['SelectedSectionID']);
	unset ($_POST['SectionID']);
	unset ($_POST['SectionName']);
}

echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-border: hsl(210, 14%, 89%);
    }

    .db-page {
        background: var(--db-bg);
        min-height: 100vh;
        padding: 1.5rem;
        font-family: "Inter", sans-serif;
    }

    .db-header {
        margin-bottom: 2rem;
    }

    .db-breadcrumb {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--db-primary-dark);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
        opacity: 0.7;
    }

    .db-title {
        font-size: 2.25rem;
        font-weight: 900;
        color: var(--db-primary-dark);
        letter-spacing: -0.02em;
    }

    .db-layout {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .db-layout {
            grid-template-columns: 1fr;
        }
    }

    .db-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid var(--db-border);
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .db-card-header {
        padding: 1rem 1.25rem;
        background: var(--db-primary-soft);
        border-bottom: 1px solid var(--db-border);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .db-card-title {
        font-size: 0.875rem;
        font-weight: 800;
        color: var(--db-primary-dark);
        text-transform: uppercase;
        margin: 0;
    }

    .db-card-body {
        padding: 1.25rem;
    }

    .db-form-group {
        margin-bottom: 1.25rem;
    }

    .db-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--db-primary-dark);
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .db-input, .db-select {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border-radius: 8px;
        border: 1px solid var(--db-border);
        font-size: 0.875rem;
        transition: all 0.2s;
        background: #fff;
    }

    .db-input:focus {
        outline: none;
        border-color: var(--db-primary);
        box-shadow: 0 0 0 3px var(--db-primary-soft);
    }

    .db-help {
        font-size: 0.7rem;
        color: #64748b;
        margin-top: 0.35rem;
        font-style: italic;
    }

    .db-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        gap: 0.5rem;
        width: 100%;
    }

    .db-btn-primary {
        background: var(--db-primary);
        color: #fff;
    }

    .db-btn-primary:hover {
        background: var(--db-primary-hover);
    }

    .db-btn-outline {
        border-color: var(--db-border);
        background: #fff;
        color: #475569;
    }

    .db-btn-outline:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .db-table-container, 
    .db-table-container[scrollable="true"], 
    [scrollable="true"] {
        overflow-y: visible !important;
        overflow-x: auto !important;
        max-height: none !important;
        height: auto !important;
        display: block !important;
    }

    .db-card, .db-main {
        overflow: visible !important;
    }

    .db-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .db-table th {
        background: var(--db-primary-soft);
        color: var(--db-primary-dark);
        font-weight: 800;
        text-transform: uppercase;
        font-size: 0.7rem;
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--db-border);
    }

    .db-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--db-border);
        color: #475569;
    }

    .db-table tr:hover {
        background: #f8fafc;
    }

    .db-badge {
        display: inline-flex;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #f1f5f9;
        color: #475569;
    }

    .db-link {
        color: var(--db-primary);
        text-decoration: none;
        font-weight: 600;
    }

    .db-link:hover {
        text-decoration: underline;
    }

    .noPrint { display: initial; }
</style>';

echo '<div class="db-page">';

echo '<header class="db-header">
        <div class="db-breadcrumb">' . __('General Ledger') . ' / ' . __('Setup') . '</div>
        <h1 class="db-title">' . $Title . '</h1>
      </header>';

echo '<div class="db-layout">';

// LEFT COLUMN: FORM
echo '<aside class="db-aside">';

if (isset($_POST['SelectedSectionID']) or isset($_GET['SelectedSectionID'])) {
	echo '<div style="margin-bottom: 1rem;">
            <a class="db-btn db-btn-outline" style="width: auto;" href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                <i class="fas fa-arrow-left"></i> ' . __('Create New Section') . '
            </a>
          </div>';
}

echo '<div class="db-card">';
echo '<div class="db-card-header">
        <i class="fas fa-edit" style="color: var(--db-primary);"></i>
        <h3 class="db-card-title">' . (isset($_GET['SelectedSectionID']) ? __('Edit Account Section') : __('New Account Section')) . '</h3>
      </div>';

echo '<div class="db-card-body">';
echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" id="AccountSections" method="post">',
    '<input name="FormID" type="hidden" value="', $_SESSION['FormID'], '" />';

if (isset($_GET['SelectedSectionID'])) {
    //editing an existing section
    $SQL = "SELECT sectionid, sectionname FROM accountsection WHERE sectionid='" . $_GET['SelectedSectionID'] ."'";
    $Result = DB_query($SQL);
    if ( DB_num_rows($Result) == 0 ) {
        prnMsg( __('Could not retrieve the requested section please try again.'),'warn');
    } else {
        $MyRow = DB_fetch_array($Result);
        $_POST['SectionID'] = $MyRow['sectionid'];
        $_POST['SectionName'] = $MyRow['sectionname'];
        echo '<input name="SelectedSectionID" type="hidden" value="', $_POST['SectionID'], '" />';
        
        echo '<div class="db-form-group">
                <label class="db-label">', __('Section Number'), '</label>
                <div class="db-input" style="background: #f1f5f9; border-color: transparent;">', $_POST['SectionID'], '</div>
              </div>';
    }
} else {
    if (!isset($_POST['SectionID'])) { $_POST['SectionID']=''; }
    echo '<div class="db-form-group">
            <label class="db-label">', __('Section Number'), '</label>
            <input autofocus="autofocus" class="db-input ', ( in_array('SectionID',$Errors) ? 'inputerror' : '' ), '" maxlength="4" name="SectionID" required="required" type="text" value="', $_POST['SectionID'], '" />
            <div class="db-help">', __('Enter a unique integer identifier'), '</div>
          </div>';
}

if (!isset($_POST['SectionName'])) { $_POST['SectionName']=''; }
echo '<div class="db-form-group">
        <label class="db-label">', __('Section Description'), '</label>
        <input class="db-input ', ( in_array('SectionName',$Errors) ? 'inputerror' : '' ), '" maxlength="30" name="SectionName" required="required" type="text" value="', $_POST['SectionName'], '" />
        <div class="db-help">', __('Example: Operating Expenses'), '</div>
      </div>';

echo '<button name="submit" type="submit" class="db-btn db-btn-primary">
        <i class="fas fa-save"></i> ', __('Save Section'), '
      </button>';

echo '</form>';
echo '</div>'; // card-body
echo '</div>'; // card
echo '</aside>';


// Pagination Logic
$ItemsPerPage = 10;
if (!isset($_GET['Offset']) or !is_numeric($_GET['Offset'])) {
	$_GET['Offset'] = 0;
}
$TotalRowsRow = DB_fetch_row(DB_query("SELECT COUNT(*) FROM accountsection"));
$TotalRows = $TotalRowsRow[0];

$SQL = "SELECT sectionid, sectionname FROM accountsection ORDER BY sectionid LIMIT " . $_GET['Offset'] . "," . $ItemsPerPage;
$ErrMsg = __('Could not get account group sections because');
$Result = DB_query($SQL, $ErrMsg);

// RIGHT COLUMN: RESULTS
echo '<main class="db-main">';
echo '<div class="db-card">';
echo '<div class="db-card-header">
        <i class="fas fa-list" style="color: var(--db-primary);"></i>
        <h3 class="db-card-title">' . __('Existing Account Sections') . '</h3>
      </div>';

echo '<div class="db-table-container">';
echo '<table class="db-table">
    <thead>
        <tr>
            <th>', __('ID'), '</th>
            <th>', __('Section Description'), '</th>
            <th class="noPrint" style="text-align: right;">', __('Actions'), '</th>
        </tr>
    </thead>
    <tbody>';

while ($MyRow = DB_fetch_array($Result)) {
    echo '<tr>
            <td><span class="db-badge">', $MyRow['sectionid'], '</span></td>
            <td style="font-weight: 500;">', $MyRow['sectionname'], '</td>
            <td class="noPrint" style="text-align: right;">
                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <a class="db-btn db-btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem;" href="', htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedSectionID='.urlencode($MyRow['sectionid']), ENT_QUOTES, 'UTF-8'), '">
                        <i class="fas fa-edit"></i> ', __('Edit'), '
                    </a>';
    if ( $MyRow['sectionid'] == '1' or $MyRow['sectionid'] == '2' ) {
        echo '<span class="db-badge" style="background: #fee2e2; color: #991b1b;">' . __('Restricted') . '</span>';
    } else {
        echo '<a class="db-btn db-btn-outline" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; color: #dc2626;" href="', htmlspecialchars($_SERVER['PHP_SELF'].'?SelectedSectionID='.urlencode($MyRow['sectionid']).'&delete=1', ENT_QUOTES, 'UTF-8'), '" onclick="return confirm(\'' . __('Are you sure you want to delete this section?') . '\');">
                <i class="fas fa-trash"></i> ', __('Delete'), '
              </a>';
    }
    echo '</div>
            </td>
        </tr>';
}
echo '</tbody>
    </table>
</div>'; // table-container

// Pagination Controls
if ($TotalRows > $ItemsPerPage) {
    echo '<div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: var(--db-primary-soft); border-top: 1px solid var(--db-border);">';
    if ($_GET['Offset'] > 0) {
        echo '<a class="db-btn db-btn-outline" style="width: auto;" href="' . htmlspecialchars(basename(__FILE__) . '?Offset=' . ($_GET['Offset'] - $ItemsPerPage)) . '"><i class="fas fa-chevron-left"></i> Previous</a>';
    } else {
        echo '<span></span>';
    }
    
    echo '<div style="font-size: 0.75rem; font-weight: 700; color: var(--db-primary-dark);">';
    echo __('Showing') . ' ' . ($_GET['Offset'] + 1) . ' - ' . min($_GET['Offset'] + $ItemsPerPage, $TotalRows) . ' ' . __('of') . ' ' . $TotalRows;
    echo '</div>';

    if ($_GET['Offset'] + $ItemsPerPage < $TotalRows) {
        echo '<a class="db-btn db-btn-outline" style="width: auto;" href="' . htmlspecialchars(basename(__FILE__) . '?Offset=' . ($_GET['Offset'] + $ItemsPerPage)) . '">Next <i class="fas fa-chevron-right"></i></a>';
    } else {
        echo '<span></span>';
    }
    echo '</div>';
}

echo '</div>'; // card
echo '</main>';

echo '</div>'; // layout
echo '</div>'; // page

include(__DIR__ . '/includes/footer.php');
?>
