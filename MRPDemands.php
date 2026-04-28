<?php

require(__DIR__ . '/includes/session.php');

$Title = __('MRP Demands');
$ViewTopic = 'MRP';
$BookMark = 'MRP_MasterSchedule';
include(__DIR__ . '/includes/header.php');

if (isset($_POST['Duedate'])){$_POST['Duedate'] = ConvertSQLDate($_POST['Duedate']);}

if (isset($_POST['DemandID'])){
	$DemandID =$_POST['DemandID'];
} elseif (isset($_GET['DemandID'])){
	$DemandID =$_GET['DemandID'];
}

if (isset($_POST['StockID'])){
	$StockID =trim(mb_strtoupper($_POST['StockID']));
} elseif (isset($_GET['StockID'])){
	$StockID =trim(mb_strtoupper($_GET['StockID']));
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
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1400px; margin: 0 auto; }
    .db-page-header { margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: flex-end; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; line-height: 1.1; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; align-items: start; }
    @media (max-width: 1100px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1.5rem; }
    .db-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.8125rem; font-weight: 700; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.05em; }
    .db-card-body { padding: 1.25rem; }
    
    .db-table-container { overflow-x: auto; width: 100%; border-radius: var(--radius-lg); }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.875rem 1rem; text-transform: uppercase; font-size: 0.7rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.875rem 1rem; border-bottom: 1px solid var(--db-border); }
    .db-table tr:hover td { background: #f8fafc; }
    .db-table .number { text-align: right; font-family: "JetBrains Mono", monospace; }
    
    .db-field { margin-bottom: 1rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.375rem; display: block; }
    .db-input, .db-select { 
        padding: 0.625rem 0.875rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fff; font-size: 0.875rem; transition: all 0.2s; width: 100%;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.625rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 700; font-size: 0.875rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
    }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-ghost { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-badge { padding: 3px 6px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.75rem; }
    .link-delete { color: #dc2626; }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div>
                <div class="db-breadcrumb">' . __('MRP') . ' / ' . __('Master Schedule') . '</div>
                <h1 class="db-page-title">' . $Title . '</h1>
            </div>
            <a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?listall=yes" class="db-btn db-btn-ghost" style="width: auto;">' . __('See All Demands') . '</a>
        </header>';

if (isset($_POST['Search'])) {
	search($StockID);
} elseif (isset($_POST['submit'])) {
	submit($StockID,$DemandID);
} elseif (isset($_GET['delete'])) {
	delete($DemandID,'',$StockID);
} elseif (isset($_POST['deletesome'])) {
	delete('',$_POST['MRPDemandtype'],$StockID);
} elseif (isset($_GET['listall'])) {
	listall('','');
} elseif (isset($_POST['listsome'])) {
	listall($StockID,$_POST['MRPDemandtype']);
} else {
	display($StockID,$DemandID);
}

function wrap_grid($main, $sidebar) {
    echo '<div class="db-main-grid"><div class="db-field-group">'.$main.'</div><div class="db-field-group">'.$sidebar.'</div></div>';
}

function search(&$StockID) {
	if ($_POST['Keywords']=='' AND $_POST['StockCode']=='') { $_POST['StockCode']='%'; }
	if (mb_strlen($_POST['Keywords'])>0) {
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		$SQL = "SELECT stockid, description FROM stockmaster WHERE description " . LIKE . " '" . $SearchString ."' ORDER BY stockid";
	} else {
		$SQL = "SELECT stockid, description FROM stockmaster WHERE stockid " . LIKE  . "'%" . $_POST['StockCode'] . "%' ORDER BY stockid";
	}
	$Result = DB_query($SQL);
	ob_start();
	if (DB_num_rows($Result) > 0) {
		echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">' . __('Search Results') . '</h3></div>';
		echo '<div class="db-card-body" style="padding:0;"><table class="db-table"><thead><tr><th>' . __('Code') . '</th><th>' . __('Description') . '</th></tr></thead><tbody>';
		while ($MyRow=DB_fetch_array($Result)) {
			echo '<tr><td><form method="post" action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'"><input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/><button type="submit" name="StockID" value="'.$MyRow['stockid'].'" class="link-action" style="background:none;border:none;cursor:pointer;padding:0;">'.$MyRow['stockid'].'</button></form></td><td>'.$MyRow['description'].'</td></tr>';
		}
		echo '</tbody></table></div></div>';
	} else {
		prnMsg(__('No record found in search'),'error');
		unset($StockID); display($StockID,$DemandID); return;
	}
	$main = ob_get_clean();
	ob_start(); display($StockID,$DemandID, true); $sidebar = ob_get_clean();
	wrap_grid($main, $sidebar);
}

function submit(&$StockID,&$DemandID) {
	$FormatedDuedate = FormatDateForSQL($_POST['Duedate']);
	$InputError = 0;
	if (!is_numeric(filter_number_format($_POST['Quantity'])) || filter_number_format($_POST['Quantity']) <= 0) { $InputError = 1; prnMsg(__('Quantity must be positive numeric'),'error'); }
	if (!Is_Date($_POST['Duedate'])) { $InputError = 1; prnMsg(__('Invalid due date'),'error'); }
	
	if ($InputError != 1) {
		$SQL = "SELECT count(*) FROM mrpdemands WHERE demandid='" . $DemandID . "'";
		$Result = DB_query($SQL); $MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$SQL = "UPDATE mrpdemands SET quantity = '" . filter_number_format($_POST['Quantity']) . "', mrpdemandtype = '" . trim(mb_strtoupper($_POST['MRPDemandtype'])) . "', duedate = '" . $FormatedDuedate . "' WHERE demandid = '" . $DemandID . "'";
			$Msg = __('Updated');
		} else {
			$SQL = "INSERT INTO mrpdemands (stockid, mrpdemandtype, quantity, duedate) VALUES ('" . $StockID . "', '" . trim(mb_strtoupper($_POST['MRPDemandtype'])) . "', '" . filter_number_format($_POST['Quantity']) . "', '" . $FormatedDuedate . "')";
			$Msg = __('Added');
		}
		DB_query($SQL); prnMsg($Msg . ' ' . $StockID, 'success');
		unset($_POST['Quantity'], $_POST['Duedate'], $StockID, $DemandID);
	}
	display($StockID,$DemandID);
}

function delete($DemandID,$DemandType,$StockID) {
	$Where = $DemandType ? " WHERE mrpdemandtype ='"  .  $DemandType . "'" : " WHERE demandid ='"  .  $DemandID . "'";
	DB_query("DELETE FROM mrpdemands $Where");
	prnMsg(__('Deleted'), 'success');
	unset($DemandID, $StockID);
	display($StockID, $DemandID);
}

function listall($Part,$DemandType) {
	$Where = $Part ? " WHERE mrpdemands.stockid ='$Part'" : ($DemandType ? " WHERE mrpdemandtype ='$DemandType'" : "");
	$SQL = "SELECT mrpdemands.demandid, mrpdemands.stockid, mrpdemands.mrpdemandtype, mrpdemands.quantity, mrpdemands.duedate, stockmaster.description, stockmaster.decimalplaces
			FROM mrpdemands LEFT JOIN stockmaster on mrpdemands.stockid = stockmaster.stockid $Where ORDER BY mrpdemands.stockid, mrpdemands.duedate";
	$Result = DB_query($SQL);
	ob_start();
	echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">' . __('MRP Demands List') . '</h3></div>';
	echo '<div class="db-card-body" style="padding:0;"><table class="db-table"><thead><tr><th>' . __('Item') . '</th><th>' . __('Type') . '</th><th>' . __('Qty') . '</th><th>' . __('Due') . '</th><th>' . __('Action') . '</th></tr></thead><tbody>';
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr><td><b class="db-mono">'.$MyRow['stockid'].'</b><br><small>'.$MyRow['description'].'</small></td><td>'.$MyRow['mrpdemandtype'].'</td><td class="number">'.locale_number_format($MyRow['quantity'],$MyRow['decimalplaces']).'</td><td>'.ConvertSQLDate($MyRow['duedate']).'</td>';
		echo '<td><a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?DemandID='.$MyRow['demandid'].'&StockID='.$MyRow['stockid'].'" class="link-action">'.__('Edit').'</a> | <a href="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'?DemandID='.$MyRow['demandid'].'&StockID='.$MyRow['stockid'].'&delete=yes" class="link-action link-delete">'.__('Del').'</a></td></tr>';
	}
	echo '</tbody></table></div></div>';
	$main = ob_get_clean();
	ob_start(); display($StockID,$DemandID, true); $sidebar = ob_get_clean();
	wrap_grid($main, $sidebar);
}

function display(&$StockID,&$DemandID, $sidebar_only = false) {
    ob_start();
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post"><input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
    if (!isset($StockID)) {
        echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">' . __('Search Stock') . '</h3></div>';
        echo '<div class="db-card-body"><div class="db-field"><label class="db-label">'.__('Description').'</label><input type="text" name="Keywords" class="db-input" /></div>';
        echo '<div class="db-field"><label class="db-label">'.__('Stock Code').'</label><input type="text" name="StockCode" class="db-input" /></div>';
        echo '<button type="submit" name="Search" class="db-btn db-btn-primary">'.__('Search').'</button></div></div>';
    } else {
        if (isset($DemandID)) {
            $Result = DB_query("SELECT * FROM mrpdemands WHERE demandid='$DemandID'");
            $MyRow = DB_fetch_array($Result);
            $_POST['StockID'] = $MyRow['stockid']; $_POST['MRPDemandtype'] = $MyRow['mrpdemandtype']; $_POST['Quantity'] = locale_number_format($MyRow['quantity'],'Variable'); $_POST['Duedate'] = ConvertSQLDate($MyRow['duedate']);
            echo '<input type="hidden" name="DemandID" value="'.$DemandID.'" /><input type="hidden" name="StockID" value="'.$_POST['StockID'].'" />';
            $title = __('Edit Demand');
        } else {
            $title = __('New Demand');
        }
        echo '<div class="db-card"><div class="db-card-header"><h3 class="db-card-title">'.$title.'</h3></div><div class="db-card-body">';
        echo '<div class="db-field"><label class="db-label">'.__('Item').'</label><div class="db-badge">'.$StockID.'</div></div>';
        echo '<div class="db-field"><label class="db-label">'.__('Quantity').'</label><input type="text" name="Quantity" class="db-input number" value="'.(isset($_POST['Quantity'])?$_POST['Quantity']:'').'" /></div>';
        echo '<div class="db-field"><label class="db-label">'.__('Due Date').'</label><input type="date" name="Duedate" class="db-input" value="'.(isset($_POST['Duedate'])?FormatDateForSQL($_POST['Duedate']):'').'" /></div>';
        echo '<div class="db-field"><label class="db-label">'.__('Demand Type').'</label><select name="MRPDemandtype" class="db-select">';
        $Res = DB_query("SELECT mrpdemandtype, description FROM mrpdemandtypes");
        while ($DRow = DB_fetch_array($Res)) { $sel = (isset($_POST['MRPDemandtype']) && $_POST['MRPDemandtype']==$DRow['mrpdemandtype']) ? 'selected':''; echo '<option '.$sel.' value="'.$DRow['mrpdemandtype'].'">'.$DRow['description'].'</option>'; }
        echo '</select></div>';
        echo '<button type="submit" name="submit" class="db-btn db-btn-primary">'.__('Save').'</button>';
        echo '<div style="margin-top:10px;"><button type="submit" name="listsome" class="db-btn db-btn-ghost">'.__('List Logic').'</button></div>';
        echo '</div></div>';
    }
    echo '</form>';
    $content = ob_get_clean();
    if ($sidebar_only) { echo $content; } else { wrap_grid('<div class="db-card" style="border: 2px dashed var(--db-border); height: 300px; display: flex; align-items:center; justify-content:center; color: var(--db-text-muted);">' . __('Select an item to view or create demands.') . '</div>', $content); }
}

echo '</div></div>';
include(__DIR__ . '/includes/footer.php');
?>
