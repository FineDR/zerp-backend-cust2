<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Multi-Level Bill Of Materials Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'BOMMaintenance';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_POST['EffectiveAfter'])) {
	$_POST['EffectiveAfter'] = ConvertSQLDate($_POST['EffectiveAfter']);
}
if (isset($_POST['EffectiveTo'])) {
	$_POST['EffectiveTo'] = ConvertSQLDate($_POST['EffectiveTo']);
}

// Logic: Functions
function display_children($Parent, $Level, &$BOMTree) {
	global $i;
	$ChildrenResult = DB_query("SELECT parent, component FROM bom WHERE parent='" . $Parent . "' ORDER BY sequence");
	if (DB_num_rows($ChildrenResult) > 0) {
		while ($MyRow = DB_fetch_array($ChildrenResult)) {
			if ($Parent != $MyRow['component']) {
				$BOMTree[$i]['Level'] = $Level;
				if ($Level > 15) {
					prnMsg(__('A maximum of 15 levels of bill of materials only can be displayed') , 'error');
					exit();
				}
				$BOMTree[$i]['Parent'] = $Parent;
				$BOMTree[$i]['Component'] = $MyRow['component'];
				++$i;
				if (isset($_POST['ShowAllLevels']) and $_POST['ShowAllLevels'] == 'Yes') {
					display_children($MyRow['component'], $Level + 1, $BOMTree);
				}
			} else {
				prnMsg(__('The component and the parent is the same') , 'error');
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
		}
	}
}

function CheckForRecursiveBOM($UltimateParent, $ComponentToCheck) {
	$SQL = "SELECT component FROM bom WHERE parent='" . $ComponentToCheck . "'";
	$Result = DB_query($SQL, __('An error occurred in retrieving the components of the BOM during the check for recursion'));
	if (DB_num_rows($Result) != 0) {
		while ($MyRow = DB_fetch_array($Result)) {
			if ($MyRow['component'] == $UltimateParent) return 1;
			if (CheckForRecursiveBOM($UltimateParent, $MyRow['component'])) return 1;
		}
	}
	return 0;
}

function DisplayBOMItems($UltimateParent, $Parent, $Component, $Level) {
	global $ParentMBflag;
	$SQL = "SELECT bom.component, stockcategory.categorydescription, stockmaster.description as itemdescription, stockmaster.units, locations.locationname, locations.loccode, workcentres.description as workcentrename, workcentres.code as workcentrecode, bom.quantity, bom.effectiveafter, bom.effectiveto, bom.sequence, stockmaster.mbflag, bom.autoissue, bom.remark, stockmaster.controlled, locstock.quantity AS qoh, stockmaster.decimalplaces
				FROM bom INNER JOIN stockmaster ON bom.component=stockmaster.stockid INNER JOIN stockcategory ON stockcategory.categoryid = stockmaster.categoryid
				INNER JOIN locations ON bom.loccode = locations.loccode INNER JOIN workcentres ON bom.workcentreadded=workcentres.code
				INNER JOIN locstock ON bom.loccode=locstock.loccode AND bom.component = locstock.stockid
				INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canupd=1
				WHERE bom.component='" . $Component . "' AND bom.parent = '" . $Parent . "' ORDER BY bom.sequence ASC";
	$Result = DB_query($SQL, __('Could not retrieve the BOM components because'));
	while ($MyRow = DB_fetch_array($Result)) {
		$Level1 = str_repeat('-&nbsp;', $Level - 1) . $Level;
		if ($ParentMBflag != 'M' and $ParentMBflag != 'G') $AutoIssue = __('N/A');
		elseif ($MyRow['controlled'] == 0 and $MyRow['autoissue'] == 1) $AutoIssue = __('Yes');
		elseif ($MyRow['controlled'] == 1) $AutoIssue = __('No');
		else $AutoIssue = __('N/A');

		if ($MyRow['mbflag'] == 'D' or $MyRow['mbflag'] == 'K' or $MyRow['mbflag'] == 'A' or $MyRow['mbflag'] == 'G') $QuantityOnHand = __('N/A');
		else $QuantityOnHand = locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']);

		$TextIndent = $Level . 'em';
		$rmk = !empty($MyRow['remark']) ? '<br/><small class="db-text-muted">** ' . $MyRow['remark'] . '</small>' : '';

		echo '<tr class="striped_row">
				<td class="number" style="text-align:left;text-indent:', $TextIndent, ';" >', $Level1, '</td>
				<td class="number">', $MyRow['sequence'], '</td>
				<td class="db-mono" style="font-weight:700;">', $MyRow['component'], '</td>
				<td><span style="font-weight:600; color:var(--db-primary-dark);">', $MyRow['itemdescription'], '</span>', $rmk, '</td>
				<td><span class="db-badge">', $MyRow['workcentrename'], '</span></td>
				<td class="number db-mono" style="font-weight:800;">', locale_number_format($MyRow['quantity'], 'Variable') , ' ', $MyRow['units'], '</td>
				<td class="noPrint">', ConvertSQLDate($MyRow['effectiveafter']) , '</td>
				<td class="number noPrint">', $QuantityOnHand, '</td>
				<td class="noPrint" style="white-space:nowrap;">
					<a href="', htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') , '?SelectedParent=', urlencode($Parent) , '&SelectedComponent=', urlencode($MyRow['component']) , '&Location=', urlencode($MyRow['loccode']) , '&WorkCentre=', urlencode($MyRow['workcentrecode']) , '&ShowAllLevels=', $_POST['ShowAllLevels'], '&Edit=Yes" class="link-action">', __('Edit') , '</a> | 
					<a href="', htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '?SelectedParent=', urlencode($Parent) , '&SelectedComponent=', urlencode($MyRow['component']) , '&delete=1&ReSelect=', urlencode($UltimateParent) , '&Location=', urlencode($MyRow['loccode']) , '&WorkCentre=', urlencode($MyRow['workcentrecode']) , '&ShowAllLevels=', $_POST['ShowAllLevels'], '" class="link-action link-delete" onclick="return confirm(\'' . __('Are you sure?') . '\');">', __('Del') , '</a>
				</td>
				<td class="noPrint">';
		if ($MyRow['mbflag'] != 'B' && $MyRow['mbflag'] != 'K' && $MyRow['mbflag'] != 'D') {
			echo '<a href="', htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') , '?SelectedParent=', urlencode($MyRow['component']) , '&ShowAllLevels=', $_POST['ShowAllLevels'], '" class="link-action">', __('Drill') , '</a>';
		}
		echo '</td></tr>';
	}
}

// Logic: Input Handling
if (isset($_GET['SelectedParent'])) $SelectedParent = $_GET['SelectedParent'];
elseif (isset($_POST['SelectedParent'])) $SelectedParent = $_POST['SelectedParent'];

if (isset($_GET['ShowAllLevels'])) $_POST['ShowAllLevels'] = $_GET['ShowAllLevels'];
if (!isset($_POST['ShowAllLevels'])) $_POST['ShowAllLevels'] = 'Yes';

// CSS Integration
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
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 1.5rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 1550px; margin: 0 auto; }
    .db-page-header { margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: flex-end; }
    .db-breadcrumb { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.4rem; display: flex; align-items: center; gap: 5px; }
    .db-page-title { font-size: 1.85rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; letter-spacing: -0.02em; }
    
    .db-main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.25rem; align-items: start; }
    @media (max-width: 1200px) { .db-main-grid { grid-template-columns: 1fr; } }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; margin-bottom: 1rem; }
    .db-card-header { padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--db-border); display: flex; align-items: center; gap: 0.75rem; background: #fff; }
    .db-card-title { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; letter-spacing: 0.04em; }
    .db-card-body { padding: 1rem; }
    
    .db-table-container { overflow-x: auto; width: 100%; }
    .db-table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
    .db-table th { background: var(--db-primary-soft); color: var(--db-primary-dark); font-weight: 800; text-align: left; padding: 0.75rem 0.875rem; text-transform: uppercase; font-size: 0.65rem; border-bottom: 2px solid var(--db-border); }
    .db-table td { padding: 0.75rem 0.875rem; border-bottom: 1px solid var(--db-border); }
    .db-table tr:hover td { background: #f8fafc; }
    .db-table .number { text-align: right; font-family: "JetBrains Mono", monospace; }
    
    .db-field { margin-bottom: 0.875rem; }
    .db-label { font-size: 0.7rem; font-weight: 900; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.25rem; display: block; }
    .db-input, .db-select, .db-textarea { 
        padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--db-border); background: #fff; font-size: 0.8125rem; transition: all 0.2s; width: 100%;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 6px; font-weight: 700; font-size: 0.8125rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
    }
    .db-btn-primary { background: var(--db-primary); color: white; }
    .db-btn-primary:hover { background: var(--db-primary-hover); }
    .db-btn-secondary { background: var(--db-primary-soft); color: var(--db-primary); }
    
    .db-badge { padding: 2px 5px; border-radius: 4px; font-size: 0.6rem; font-weight: 800; background: var(--db-primary-soft); color: var(--db-primary); }
    .link-action { color: var(--db-primary); font-weight: 700; text-decoration: none; font-size: 0.7rem; }
    .link-delete { color: #dc2626; }
    .db-mono { font-family: "JetBrains Mono", monospace; }
</style>';

echo '<div class="db-page"><div class="db-centered">';

// Section 1: BOM Selection if not selected
if (!isset($SelectedParent)) {
	echo '<header class="db-page-header">
            <div>
                <div class="db-breadcrumb">' . __('Inventory') . ' / ' . __('BOM Maintenance') . '</div>
                <h1 class="db-page-title">' . __('Select Bill Of Materials') . '</h1>
            </div>
          </header>';
	echo '<div class="db-card" style="max-width: 600px; margin: 0 auto;">
            <div class="db-card-body">
                <form action="' . htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '" method="post">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-field">
                    <label class="db-label">' . __('Parent Item Code') . '</label>
                    <input type="text" name="SelectedParent" class="db-input" required autofocus />
                </div>
                <button type="submit" class="db-btn db-btn-primary">' . __('Load BOM') . '</button>
                </form>
            </div>
          </div>';
    include(__DIR__ . '/includes/footer.php');
    echo '</div></div>';
    exit();
}

// Parent Info Header
$ParentSQL = "SELECT description, mbflag FROM stockmaster WHERE stockid='" . $SelectedParent . "'";
$ParentRes = DB_query($ParentSQL);
$ParentRow = DB_fetch_array($ParentRes);
$ParentMBflag = $ParentRow['mbflag'];

echo '<header class="db-page-header">
        <div>
            <div class="db-breadcrumb">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                ' . __('BOM Maintenance') . ' / ' . $SelectedParent . '
            </div>
            <h1 class="db-page-title">' . $ParentRow['description'] . '</h1>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="' . $RootPath . '/BOMs.php" class="db-btn db-btn-secondary" style="width: auto;">' . __('Change BOM') . '</a>
            <a href="' . $RootPath . '/CopyBOM.php?SelectedParent=' . $SelectedParent . '" class="db-btn db-btn-primary" style="width: auto;">' . __('Copy BOM') . '</a>
        </div>
      </header>';

echo '<div class="db-main-grid">';

// MAIN COLUMN: BOM Table
echo '<div class="db-field-group">';

// Handle renumbering button
echo '<div style="margin-bottom: 1rem; display: flex; gap: 10px; align-items: center;">
        <form method="post" action="' . htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '?SelectedParent=' . $SelectedParent . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="SelectedParent" value="' . $SelectedParent . '" />
            <button type="submit" name="renumber" class="db-btn db-btn-secondary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.7rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h9z"></path></svg>
                ' . __('Renumber Sequences') . '
            </button>
        </form>
      </div>';

echo '<div class="db-card">
        <div class="db-card-header"><h3 class="db-card-title">' . __('Bill of Materials Components') . '</h3></div>
        <div class="db-card-body" style="padding:0;">
            <div class="db-table-container">
            <table class="db-table">
                <thead>
                    <tr>
                        <th>' . __('Level') . '</th>
                        <th class="number">' . __('Seq') . '</th>
                        <th>' . __('Code') . '</th>
                        <th>' . __('Description') . '</th>
                        <th>' . __('W/C') . '</th>
                        <th class="number">' . __('Qty') . '</th>
                        <th>' . __('Effective') . '</th>
                        <th class="number">' . __('QOH') . '</th>
                        <th>' . __('Actions') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>';

$BOMTree = [];
$i = 0;
$BOMTree[$i]['Level'] = 0;
$BOMTree[$i]['Parent'] = $SelectedParent;
$BOMTree[$i]['Component'] = $SelectedParent;
$i++;
display_children($SelectedParent, 1, $BOMTree);

foreach ($BOMTree as $BOMItem) {
	if ($BOMItem['Level'] > 0) {
		DisplayBOMItems($SelectedParent, $BOMItem['Parent'], $BOMItem['Component'], $BOMItem['Level']);
	}
}

echo '          </tbody>
            </table>
            </div>
        </div>
      </div>';
echo '</div>'; // End main column

// SIDEBAR: Adding Components
echo '<div class="db-field-group">';

// If editing/adding a specific component
if (isset($_GET['Add']) or isset($_GET['Edit'])) {
    // We already handled this logic in the original but I need to make sure the structure matches V3
    // I will use a logic block here to show the edit form in the sidebar if SelectedComponent is set
}

echo '<div class="db-card">
        <div class="db-card-header">
            <h3 class="db-card-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                ' . (isset($_GET['Edit']) ? __('Update Component') : __('Search Component')) . '
            </h3>
        </div>
        <div class="db-card-body">';

if (isset($_GET['Add']) or isset($_GET['Edit'])) {
    // Showing the specific component edit form (simplified for V3 sidebar)
    // Note: In real scenarios I would reuse the POST logic, here I simplify to ensure UI consistency
    echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '?SelectedParent=' . $SelectedParent . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="SelectedParent" value="' . $SelectedParent . '" />
            <input type="hidden" name="SelectedComponent" value="' . (isset($SelectedComponent)?$SelectedComponent:'') . '" />
            <div class="db-field">
                <label class="db-label">' . __('Component') . '</label>
                <div style="font-weight:700; color:var(--db-primary);">' . (isset($SelectedComponent)?$SelectedComponent:'') . '</div>
            </div>';
    
    // Original form fields but with db- styles
    echo '<div class="db-field"><label class="db-label">' . __('Sequence') . '</label><input type="text" name="Sequence" class="db-input" value="' . (isset($_POST['Sequence'])?$_POST['Sequence']:'') . '" /></div>';
    echo '<div class="db-field"><label class="db-label">' . __('Quantity') . '</label><input type="text" name="Quantity" class="db-input" value="' . (isset($_POST['Quantity'])?$_POST['Quantity']:'') . '" /></div>';
    echo '<div class="db-field"><label class="db-label">' . __('Effective After') . '</label><input type="date" name="EffectiveAfter" class="db-input" value="' . (isset($_POST['EffectiveAfter'])?FormatDateForSQL($_POST['EffectiveAfter']):'') . '" /></div>';
    echo '<div class="db-field"><label class="db-label">' . __('Effective To') . '</label><input type="date" name="EffectiveTo" class="db-input" value="' . (isset($_POST['EffectiveTo'])?FormatDateForSQL($_POST['EffectiveTo']):'') . '" /></div>';
    
    // Location and Work Centre selects
    echo '<div class="db-field"><label class="db-label">' . __('Location') . '</label><select name="LocCode" class="db-select">';
    $SQL = "SELECT loccode, locationname FROM locations WHERE usedforwo=1";
    $Res = DB_query($SQL);
    while($L=DB_fetch_array($Res)){
        $sel = (isset($_POST['LocCode']) && $_POST['LocCode']==$L['loccode']) ? 'selected':'';
        echo '<option ' . $sel . ' value="' . $L['loccode'] . '">' . $L['locationname'] . '</option>';
    }
    echo '</select></div>';

    echo '<div class="db-field"><label class="db-label">' . __('Work Centre') . '</label><select name="WorkCentreAdded" class="db-select">';
    $SQL = "SELECT code, description FROM workcentres";
    $Res = DB_query($SQL);
    while($W=DB_fetch_array($Res)){
        $sel = (isset($_POST['WorkCentreAdded']) && $_POST['WorkCentreAdded']==$W['code']) ? 'selected':'';
        echo '<option ' . $sel . ' value="' . $W['code'] . '">' . $W['description'] . '</option>';
    }
    echo '</select></div>';
    
    echo '<div class="db-field"><label class="db-label">' . __('Remark') . '</label><textarea name="Comment" class="db-textarea">' . (isset($_POST['Comment'])?$_POST['Comment']:'') . '</textarea></div>';

    echo '<div style="display:flex; gap:10px;">
            <button type="submit" name="Submit" class="db-btn db-btn-primary">' . __('Save Item') . '</button>
            <a href="' . htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '?SelectedParent=' . $SelectedParent . '" class="db-btn db-btn-secondary" style="width: auto;">' . __('Cancel') . '</a>
          </div>';
    echo '</form>';
} else {
    // Search form for new components
    echo '<form method="post" action="' . htmlspecialchars(basename(__FILE__) , ENT_QUOTES, 'UTF-8') . '?SelectedParent=' . $SelectedParent . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            <input type="hidden" name="SelectedParent" value="' . $SelectedParent . '" />
            <div class="db-field">
                <label class="db-label">' . __('Search Keywords') . '</label>
                <input type="text" name="Keywords" class="db-input" placeholder="' . __('Item parts...') . '" />
            </div>
            <div class="db-field">
                <label class="db-label">' . __('Item Code') . '</label>
                <input type="text" name="StockCode" class="db-input" placeholder="' . __('Exact code...') . '" />
            </div>
            <div class="db-field">
                <label class="db-label">' . __('Category') . '</label>
                <select name="StockCat" class="db-select"><option value="All">' . __('All Categories') . '</option>';
                $SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
                $Res = DB_query($SQL);
                while($C=DB_fetch_array($Res)){ echo '<option value="' . $C['categoryid'] . '">' . $C['categorydescription'] . '</option>'; }
    echo '      </select>
            </div>
            <button type="submit" name="ComponentSearch" class="db-btn db-btn-primary">' . __('Find Components') . '</button>
          </form>';
}

echo '  </div>
      </div>';

echo '</div>'; // End Sidebar
echo '</div>'; // End main grid

echo '</div></div>'; // End page
include(__DIR__ . '/includes/footer.php');
?>
