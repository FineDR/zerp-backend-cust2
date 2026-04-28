<?php

/* Defines the employees that require timesheets */

require(__DIR__ . '/includes/session.php');

$Title = __('Employee Maintenance');
$ViewTopic = 'Labour';
$BookMark = 'Employees';
include(__DIR__ . '/includes/header.php');

if (isset($_GET['SelectedEmployee'])) {
	$SelectedEmployee = $_GET['SelectedEmployee'];
} elseif (isset($_POST['SelectedEmployee'])) {
	$SelectedEmployee = $_POST['SelectedEmployee'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (trim($_POST['Surname'] ?? '') == '') { $InputError = 1; prnMsg(__('The employee\'s surname must not be empty'), 'error'); }
	if (($_POST['FirstName'] ?? '') =='') { $InputError = 1; prnMsg(__('The employee\'s first name must not be empty'), 'error'); }

	if (!$InputError) {
		if (isset($SelectedEmployee)) {
			$SQL = "UPDATE employees SET surname='" . $_POST['Surname'] . "', firstname='" . $_POST['FirstName'] . "', stockid='" . $_POST['StockID'] . "', manager='" . $_POST['Manager'] . "', normalhours='" . $_POST['NormalHours'] . "', userid='" . $_POST['UserID'] . "', email='" . $_POST['Email'] . "' WHERE id = '" . $SelectedEmployee . "'";
			$Msg = __('Updated');
		} else {
			$SQL = "INSERT INTO employees (surname, firstname, stockid, manager, normalhours, userid, email ) VALUES ('" . $_POST['Surname'] . "', '" . $_POST['FirstName'] . "', '" . $_POST['StockID'] . "', '" . $_POST['Manager'] . "', '" . $_POST['NormalHours'] . "', '" . $_POST['UserID'] . "', '" . $_POST['Email'] . "')";
			$Msg = __('Added');
		}
		DB_query($SQL); prnMsg($Msg . ' ' . $_POST['FirstName'] . ' ' . $_POST['Surname'], 'success');
		unset($_POST['Surname'], $_POST['FirstName'], $_POST['StockID'], $_POST['Manager'], $_POST['NormalHours'], $_POST['UserID'], $_POST['Email'], $SelectedEmployee);
	}
} elseif (isset($_GET['delete'])) {
    DB_query("DELETE FROM employees WHERE id='" . $SelectedEmployee . "'");
    prnMsg(__('Deleted'), 'success'); unset($SelectedEmployee);
}

echo '<style>
    :root {
        --db-primary: hsl(145, 63%, 38%);
        --db-primary-hover: hsl(145, 63%, 32%);
        --db-primary-dark: hsl(145, 45%, 22%);
        --db-primary-soft: hsl(145, 40%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-card-bg: #ffffff;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --db-text-muted: hsl(210, 16%, 46%);
        --radius-lg: 12px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1550px; margin: 0 auto; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--db-primary); text-transform: uppercase; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0 0 1.5rem; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 350px 1fr; gap: 1.25rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1rem; transition: transform 0.2s; }
    .db-card-header { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.75rem; font-weight: 900; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1rem; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.3rem; display: block; }
    .db-input, .db-select { 
        padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fdfdfd; font-size: 0.8125rem; width: 100%; transition: all 0.2s;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); background: #fff; }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%; 
    }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-table-container { overflow-x: auto; width: 100%; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem; border-bottom: 1px solid var(--db-border); vertical-align: top; }
    .db-table tr:hover td { background: #f8fafc; }
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
    .link-delete { color: #dc2626; }

    .db-pagination { padding: 0.875rem 1rem; border-top: 1px solid var(--db-border); display: flex; justify-content: space-between; align-items: center; background: #fff; }
    .db-page-info { font-size: 0.7rem; font-weight: 700; color: var(--db-text-muted); }
    .db-page-controls { display: flex; gap: 0.5rem; }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div class="db-breadcrumb">Human Resources / Workforce</div>
            <h1 class="db-page-title">' . __('Employee Maintenance') . '</h1>
        </header>

        <div class="db-main-grid">
            <!-- LEFT: Maintenance Form -->
            <div class="db-column">
                <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title">' . (isset($SelectedEmployee) ? __('Update Personnel Profile') : __('New Personnel Entry')) . '</h3>
                        </div>
                        <div class="db-card-body">';
                        
                        if (isset($SelectedEmployee)) {
                            $SQL = "SELECT * FROM employees WHERE id='$SelectedEmployee'";
                            $Row = DB_fetch_array(DB_query($SQL));
                            foreach($Row as $k=>$v) if(!isset($_POST[$k])) $_POST[$k] = $v;
                            echo '<input type="hidden" name="SelectedEmployee" value="'.$SelectedEmployee.'" />';
                            echo '<div class="db-field"><label class="db-label">Personnel ID</label><div class="db-badge">#'.$SelectedEmployee.'</div></div>';
                        }

                        echo '<div class="db-field"><label class="db-label">First Name</label><input type="text" name="FirstName" class="db-input" value="'.($_POST['firstname'] ?? $_POST['FirstName'] ?? '').'" required /></div>';
                        echo '<div class="db-field"><label class="db-label">Surname</label><input type="text" name="Surname" class="db-input" value="'.($_POST['surname'] ?? $_POST['Surname'] ?? '').'" required /></div>';
                        
                        echo '<div class="db-field"><label class="db-label">Labour Classification</label><select name="StockID" class="db-select">';
                        $ResL = DB_query("SELECT stockid, description FROM stockmaster INNER JOIN stockcategory ON stockmaster.categoryid = stockcategory.categoryid WHERE stockcategory.stocktype='L' ORDER BY stockid");
                        while($L = DB_fetch_array($ResL)) { $sel = (($_POST['stockid'] ?? $_POST['StockID'] ?? '')==$L['stockid'] ? 'selected':''); echo '<option '.$sel.' value="'.$L['stockid'].'">'.$L['description'].'</option>'; }
                        echo '</select></div>';

                        echo '<div class="db-field"><label class="db-label">Reporting Manager</label><select name="Manager" class="db-select">';
                        echo '<option value="0">None / Independent</option>';
                        $ResM = DB_query("SELECT id, CONCAT(firstname, ' ', surname) as name FROM employees WHERE id != '" . ($SelectedEmployee ?? '') . "' ORDER BY surname");
                        while($M = DB_fetch_array($ResM)) { $sel = (($_POST['manager'] ?? $_POST['Manager'] ?? 0)==$M['id'] ? 'selected':''); echo '<option '.$sel.' value="'.$M['id'].'">'.$M['name'].'</option>'; }
                        echo '</select></div>';

                        echo '<div class="db-field"><label class="db-label">Std weekly hours</label><input type="number" name="NormalHours" class="db-input" value="'.($_POST['normalhours'] ?? $_POST['NormalHours'] ?? '40').'" /></div>';
                        echo '<div class="db-field"><label class="db-label">Email Address</label><input type="email" name="Email" class="db-input" value="'.($_POST['email'] ?? $_POST['Email'] ?? '').'" /></div>';

                        echo '<div class="db-field"><label class="db-label">User Account Mapping</label><select name="UserID" class="db-select">';
                        echo '<option value="">Not a System User</option>';
                        $ResU = DB_query("SELECT userid, realname FROM www_users");
                        while($U = DB_fetch_array($ResU)) { $sel = (($_POST['userid'] ?? $_POST['UserID'] ?? '')==$U['userid'] ? 'selected':''); echo '<option '.$sel.' value="'.$U['userid'].'">'.$U['realname'].'</option>'; }
                        echo '</select></div>';

                        echo '<button type="submit" name="submit" class="db-btn db-btn-primary" style="margin-top:0.5rem;">' . (isset($SelectedEmployee) ? __('Save Changes'):__('Register Employee')) . '</button>';
                        if(isset($SelectedEmployee)) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" class="db-btn" style="display:block; text-align:center; padding-top:0.75rem; font-size:0.7rem; color:var(--db-text-muted);">'.__('Cancel Selection').'</a>';

                echo '</div></div></form></div>'; echo '<!-- RIGHT: Results Grid -->
            <div class="db-column">
                <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title">Staff Registry</h3>
                    </div>
                    <div class="db-card-body" style="padding:0;">';
            
            // Pagination Logic
            $ItemsPerPage = 12;
            $Page = isset($_GET['Page']) ? (int)$_GET['Page'] : 1;
            $Offset = ($Page - 1) * $ItemsPerPage;

            $TotalCount = DB_fetch_row(DB_query("SELECT COUNT(*) FROM employees"))[0];
            $TotalPages = ceil($TotalCount / $ItemsPerPage);

            echo '<div class="db-table-container">
                    <table class="db-table">
                        <thead><tr><th>' . __('Name') . '</th><th>' . __('Type') . '</th><th>' . __('Manager') . '</th><th>' . __('Email') . '</th><th>' . __('Actions') . '</th></tr></thead>
                        <tbody>';
            
            $SQL = "SELECT employees.id, employees.surname, employees.firstname, employees.stockid, employees.manager, employees2.firstname as mfn, employees2.surname as msn, employees.email 
                    FROM employees LEFT JOIN employees AS employees2 ON employees.manager=employees2.id 
                    ORDER BY employees.surname 
                    LIMIT $ItemsPerPage OFFSET $Offset";
            
            $Res = DB_query($SQL);
            while ($MyRow = DB_fetch_array($Res)) {
                echo '<tr>
                        <td><b>'.$MyRow['firstname'].' '.$MyRow['surname'].'</b><br><small class="db-badge">UID: '.$MyRow['id'].'</small></td>
                        <td>'.$MyRow['stockid'].'</td>
                        <td>'.($MyRow['manager'] ? $MyRow['mfn'].' '.$MyRow['msn'] : '<span style="color:#aaa;">-</span>').'</td>
                        <td><a href="mailto:'.$MyRow['email'].'" class="link-action" style="font-weight:normal;">'.$MyRow['email'].'</a></td>
                        <td style="white-space:nowrap;">
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedEmployee='.$MyRow['id'].'" class="link-action">Edit</a> | 
                            <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?SelectedEmployee='.$MyRow['id'].'&delete=1" class="link-action link-delete" onclick="return confirm(\'Remove this employee?\');">Del</a>
                        </td>
                      </tr>';
            }
            echo '</tbody></table></div>';
            
            if ($TotalPages > 1) {
                echo '<div class="db-pagination">';
                echo '<div class="db-page-info">Showing ' . ($Offset + 1) . '-' . min($Offset + $ItemsPerPage, $TotalCount) . ' of ' . $TotalCount . '</div>';
                echo '<div class="db-page-controls">';
                if ($Page > 1) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page-1).'" class="db-btn db-btn-ghost" style="width:auto;">Previous</a>';
                if ($Page < $TotalPages) echo '<a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?Page='.($Page+1).'" class="db-btn db-btn-ghost" style="width:auto;">Next</a>';
                echo '</div></div>';
            }
            
            echo '</div></div></div>';
        echo '</div>';
    echo '</div>';
echo '</div>';

include(__DIR__ . '/includes/footer.php');
?>
