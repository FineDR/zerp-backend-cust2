<?php

/**
 * Author: Ashish Shukla
 * Script to duplicate BoMs.
 */

require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

$Title = __('Copy BOM to New Item');
$ViewTopic = 'Manufacturing';
$BookMark = '';

if (isset($_POST['Submit'])) {
	$StockID = $_POST['StockID'];
	$NewOrExisting = $_POST['NewOrExisting'];
	$NewStockID = '';
	$InputError = 0;

	if ($NewOrExisting == 'N') {
		$NewStockID = $_POST['ToStockID'];
		if (mb_strlen($NewStockID)==0 OR $NewStockID==''){
			$InputError = 1;
			prnMsg(__('The new item code cannot be blank'),'error');
		}
	} else {
		$NewStockID = $_POST['ExStockID'];
	}
	if ($InputError==0) {
		DB_Txn_Begin();
		if ($NewOrExisting == 'N') {
			$SQL = "INSERT INTO stockmaster(stockid, categoryid, description, longdescription, units, mbflag, actualcost, lastcost, materialcost, labourcost, overheadcost, lowestlevel, discontinued, controlled, eoq, volume, grossweight, barcode, discountcategory, taxcatid, serialised, perishable, nextserialno, pansize, shrinkfactor, netweight)
							SELECT '".$NewStockID."', categoryid, description, longdescription, units, mbflag, actualcost, lastcost, materialcost, labourcost, overheadcost, lowestlevel, discontinued, controlled, eoq, volume, grossweight, barcode, discountcategory, taxcatid, serialised, perishable, nextserialno, pansize, shrinkfactor, netweight FROM stockmaster WHERE stockid='".$StockID."';";
			DB_query($SQL);
		} else {
			$SQL = "SELECT lastcostupdate, actualcost, lastcost, materialcost, labourcost, overheadcost, lowestlevel FROM stockmaster WHERE stockid='".$StockID."';";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			$SQL = "UPDATE stockmaster SET lastcostupdate = '" . $MyRow[0] . "', actualcost = " . $MyRow[1] . ", lastcost = " . $MyRow[2] . ", materialcost = " . $MyRow[3] . ", labourcost = " . $MyRow[4] . ", overheadcost = " . $MyRow[5] . ", lowestlevel = " . $MyRow[6] . " WHERE stockid='".$NewStockID."';";
			DB_query($SQL);
		}
		$SQL = "INSERT INTO bom SELECT '".$NewStockID."', sequence, component, workcentreadded, loccode, effectiveafter, effectiveto, quantity, autoissue, remark, digitals FROM bom WHERE parent='".$StockID."';";
		DB_query($SQL);
		if ($NewOrExisting == 'N') {
			$SQL = "INSERT INTO locstock (loccode, stockid, quantity, reorderlevel, bin) SELECT loccode, '".$NewStockID."', 0, reorderlevel, bin FROM locstock WHERE stockid='".$StockID."'";
			DB_query($SQL);
		}
		DB_Txn_Commit();
		UpdateCost($NewStockID);
		header('Location: ' . $RootPath . '/BOMs.php?SelectedParent=' . urlencode($NewStockID));
		exit();
	}
}

include(__DIR__ . '/includes/header.php');

echo '<style>
    :root {
        --db-primary: hsl(197, 92%, 47%);
        --db-primary-hover: hsl(197, 92%, 38%);
        --db-primary-dark: hsl(197, 75%, 22%);
        --db-primary-soft: hsl(197, 65%, 95%);
        --db-bg: hsl(210, 20%, 97%);
        --db-card-bg: #ffffff;
        --db-border: hsl(210, 14%, 89%);
        --db-text-main: hsl(210, 24%, 16%);
        --radius-lg: 12px;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
    }
    .db-page { background: var(--db-bg); min-height: 100vh; padding: 2rem; font-family: "Inter", system-ui, sans-serif; color: var(--db-text-main); }
    .db-centered { max-width: 700px; margin: 0 auto; }
    .db-page-header { margin-bottom: 2rem; text-align: center; }
    .db-breadcrumb { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--db-primary); letter-spacing: 0.05em; margin-bottom: 0.5rem; justify-content: center; display: flex; align-items: center; gap: 6px; }
    .db-page-title { font-size: 2.25rem; font-weight: 950; color: var(--db-primary-dark); margin: 0; }
    
    .db-card { background: var(--db-card-bg); border-radius: var(--radius-lg); border: 1px solid var(--db-border); shadow: var(--shadow-sm); overflow: hidden; }
    .db-card-header { padding: 1.25rem; border-bottom: 1px solid var(--db-border); background: #fff; text-align: center; }
    .db-card-title { font-size: 0.875rem; font-weight: 800; color: var(--db-primary-dark); margin: 0; text-transform: uppercase; }
    .db-card-body { padding: 2rem; }
    
    .db-field { margin-bottom: 1.5rem; }
    .db-label { font-size: 0.75rem; font-weight: 800; color: var(--db-primary-dark); text-transform: uppercase; margin-bottom: 0.5rem; display: block; }
    .db-input, .db-select { 
        padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid var(--db-border); background: #fff; font-size: 0.875rem; transition: all 0.2s; width: 100%;
    }
    .db-input:focus, .db-select:focus { outline: none; border-color: var(--db-primary); box-shadow: 0 0 0 3px var(--db-primary-soft); }
    
    .db-radio-group { background: var(--db-primary-soft); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--db-border); margin-top: 1rem; }
    .db-radio-option { display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; cursor: pointer; }
    .db-radio-option:last-child { margin-bottom: 0; }
    .db-radio-option input[type="radio"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--db-primary); }
    .db-radio-label { font-size: 0.875rem; font-weight: 700; color: var(--db-primary-dark); }
    
    .db-btn { 
        display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; padding: 1rem 2rem; border-radius: 8px; font-weight: 700; font-size: 0.9375rem; cursor: pointer; transition: all 0.2s; border: none; width: 100%;
    }
    .db-btn-primary { background: var(--db-primary); color: white; margin-top: 1rem; }
    .db-btn-primary:hover { background: var(--db-primary-hover); transform: translateY(-1px); }
</style>

<div class="db-page">
    <div class="db-centered">
        <header class="db-page-header">
            <div class="db-breadcrumb">' . __('Manufacturing') . ' / ' . __('Inventory Management') . '</div>
            <h1 class="db-page-title">' . __('Copy Product BOM') . '</h1>
        </header>

        <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-card">
                <div class="db-card-header">
                    <h3 class="db-card-title">' . __('Replication Settings') . '</h3>
                </div>
                <div class="db-card-body">
                    <div class="db-field">
                        <label class="db-label">' . __('Source Stock Item') . '</label>
                        <select name="StockID" class="db-select">';
    $SQL = "SELECT stockid, description FROM stockmaster WHERE stockid IN (SELECT DISTINCT parent FROM bom) AND mbflag IN ('M', 'A', 'K', 'G');";
    $Res = DB_query($SQL);
    while ($Row = DB_fetch_array($Res)) {
        $sel = (isset($_GET['Item']) && $Row['stockid'] == $_GET['Item']) ? 'selected' : '';
        echo '<option ' . $sel . ' value="' . $Row['stockid'] . '">' . $Row['stockid'] . ' -- ' . $Row['description'] . '</option>';
    }
    echo '              </select>
                    </div>

                    <div class="db-radio-group">
                        <label class="db-label">' . __('Target Destination') . '</label>
                        
                        <label class="db-radio-option">
                            <input type="radio" name="NewOrExisting" value="N" checked />
                            <span class="db-radio-label">' . __('Clone to a New Item Code') . '</span>
                        </label>
                        <div style="margin-left: 28px; margin-bottom: 2rem;">
                            <input type="text" name="ToStockID" class="db-input" placeholder="' . __('Enter unique item code...') . '" maxlength="20" />
                        </div>

                        <label class="db-radio-option">
                            <input type="radio" name="NewOrExisting" value="E" />
                            <span class="db-radio-label">' . __('Merge into Existing Item') . '</span>
                        </label>';
    
    $SQL_Ex = "SELECT stockid, description FROM stockmaster WHERE stockid NOT IN (SELECT DISTINCT parent FROM bom) AND mbflag IN ('M', 'A', 'K', 'G');";
    $Res_Ex = DB_query($SQL_Ex);
    if (DB_num_rows($Res_Ex) > 0) {
        echo '          <div style="margin-left: 28px;">
                            <select name="ExStockID" class="db-select">';
        while ($Row = DB_fetch_array($Res_Ex)) {
            echo '<option value="' . $Row['stockid'] . '">' . $Row['stockid'] . ' -- ' . $Row['description'] . '</option>';
        }
        echo '              </select>
                        </div>';
    } else {
        echo '          <div style="margin-left: 28px; font-size: 0.75rem; color: var(--db-text-muted);">' . __('No eligible existing items found.') . '</div>';
    }
    echo '          </div>

                    <button type="submit" name="Submit" class="db-btn db-btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        ' . __('Execute Direct Copy') . '
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>';

include(__DIR__ . '/includes/footer.php');
?>
