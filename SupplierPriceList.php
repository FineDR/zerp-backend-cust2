<?php

/* Maintain Supplier Price Lists */

require(__DIR__ . '/includes/session.php');

$Title = __('Supplier Purchasing Data');
$ViewTopic = 'PurchaseOrdering';
$BookMark = 'SupplierPriceList';
include(__DIR__ . '/includes/header.php');

// Architectural Workspace Design System v2 - High Density
echo '
<style>
	:root {
		--primary: hsl(197, 92%, 47%); 
		--primary-hover: hsl(197, 92%, 38%);
		--primary-dark: hsl(197, 75%, 22%);
		--primary-soft: hsl(197, 65%, 95%);
		--bg-workspace: hsl(210, 20%, 97%);
		--border-color: hsl(220, 15%, 88%);
		--text-main: hsl(197, 15%, 12%);
		--text-muted: hsl(197, 8%, 50%);
		--card-bg: #ffffff;
		--radius: 12px;
	}

	body { background-color: var(--bg-workspace); font-family: "Inter", -apple-system, sans-serif; color: var(--text-main); }
	.aw-container { padding: 12px; max-width: 1600px; margin: 0 auto; }
	.aw-page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
	.aw-breadcrumb { font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 2px; }
	.aw-page-title { font-size: 1.5rem; font-weight: 950; letter-spacing: -0.04em; color: var(--primary-dark); margin: 0; }

	.aw-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 16px; }
	@media (min-width: 1024px) { 
		.aw-grid-layout { grid-template-columns: 1fr 350px; align-items: start; }
		.aw-grid-search { grid-template-columns: 320px 1fr; align-items: start; }
	}

	.aw-card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border-color); box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 16px; }
	.aw-card-header { padding: 10px 16px; border-bottom: 1px solid var(--border-color); background: #fff; display: flex; align-items: center; gap: 10px; }
	.aw-card-title { font-size: 0.78rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin: 0; }
	.aw-card-body { padding: 12px; }

	.aw-table-wrapper { overflow-x: auto; width: 100%; }
	.aw-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
	.aw-table th { text-align: left; padding: 10px 12px; background: #fbfcfd; color: var(--text-muted); font-weight: 800; text-transform: uppercase; font-size: 0.62rem; border-bottom: 1px solid var(--border-color); }
	.aw-table td { padding: 8px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
	.aw-table tr:hover td { background-color: #f8fafc; }

	.aw-label { display: block; font-size: 0.7rem; font-weight: 850; color: var(--primary-dark); text-transform: uppercase; margin-bottom: 4px; }
	.aw-input, .aw-select { width: 100%; border: 1px solid var(--border-color); border-radius: 8px; padding: 8px 10px; font-size: 0.82rem; outline: none; transition: 0.2s; background: white; }
	.aw-input:focus, .aw-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); }

	.aw-btn { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-weight: 750; font-size: 0.8rem; cursor: pointer; transition: 0.2s; border: none; gap: 8px; text-decoration: none; }
	.aw-btn-primary { background: var(--primary); color: white; }
	.aw-btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
	.aw-btn-secondary { background: #f8fafc; border: 1px solid var(--border-color); color: var(--text-main); }
	.aw-btn-secondary:hover { background: #f1f5f9; }
    .aw-btn-icon-only { padding: 6px; border-radius: 6px; border: 1px solid var(--border-color); background: #fff; cursor: pointer; }
    .aw-btn-icon-only:hover { background: var(--primary-soft); border-color: var(--primary); }

	.aw-search-box { background: white; padding: 16px; border-radius: 12px; border: 1px solid var(--border-color); }
</style>
<div class="aw-container">';

echo '<div class="aw-page-header">
		<div>
			<div class="aw-breadcrumb">Purchasing / Maintenance</div>
			<h1 class="aw-page-title">' . $Title . '</h1>
		</div>
	  </div>';

if (isset($_POST['StockSearch'])) {
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="aw-grid aw-grid-search">';
    echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';
    echo '<input name="SupplierID" type="hidden" value="' . $_POST['SupplierID'] . '" />';
    
    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Catalog Filters') . '</h3></div>
                <div class="aw-card-body">
                    <div class="aw-form-group">
                        <label class="aw-label">' . __('Category') . '</label>
                        <select name="StockCat" class="aw-select">';
                        $SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
                        $Result1 = DB_query($SQL);
                        echo '<option value="All">' . __('All Categories') . '</option>';
                        while($MyRow1 = DB_fetch_array($Result1)) {
                            $sel = (isset($_POST['StockCat']) && $_POST['StockCat'] == $MyRow1['categoryid']) ? 'selected' : '';
                            echo '<option ' . $sel . ' value="' . $MyRow1['categoryid'] . '">' . $MyRow1['categorydescription'] . '</option>';
                        }
    echo '              </select>
                    </div>
                    <div class="aw-form-group" style="margin-top:12px;">
                        <label class="aw-label">' . __('Keywords') . '</label>
                        <input type="text" name="Keywords" class="aw-input" value="' . (isset($_POST['Keywords']) ? $_POST['Keywords'] : '') . '" placeholder="Description..." />
                    </div>
                    <div class="aw-form-group" style="margin-top:12px;">
                        <label class="aw-label">' . __('OR Stock Code') . '</label>
                        <input type="text" name="StockCode" class="aw-input" value="' . (isset($_POST['StockCode']) ? $_POST['StockCode'] : '') . '" />
                    </div>
                    <button type="submit" name="Search" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Search Catalog') . '</button>
                </div>
            </div>
          </aside>';
    echo '<main class="aw-main-side"><div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Search results will appear here.') . '</div></div></main>';
    echo '</form></div>';
    include(__DIR__ . '/includes/footer.php'); exit();
}

if (isset($_POST['Search']) OR isset($_POST['Go']) OR isset($_POST['Next']) OR isset($_POST['Previous'])) {
	if (!isset($_POST['Go']) AND !isset($_POST['Next']) AND !isset($_POST['Previous'])) { $_POST['PageOffset'] = 1; }
    
    $Keywords = (isset($_POST['Keywords']) ? mb_strtoupper($_POST['Keywords']) : '');
    $SearchString = '%' . str_replace(' ', '%', $Keywords) . '%';
    $CodeFilter = (isset($_POST['StockCode']) ? '%' . mb_strtoupper($_POST['StockCode']) . '%' : '%');
    $CatFilter = ($_POST['StockCat'] == 'All') ? "" : " AND categoryid='" . $_POST['StockCat'] . "'";

    $SQL = "SELECT stockmaster.stockid, stockmaster.description, SUM(locstock.quantity) AS qoh, stockmaster.units, stockmaster.mbflag, stockmaster.discontinued, stockmaster.decimalplaces FROM stockmaster INNER JOIN locstock ON stockmaster.stockid=locstock.stockid WHERE stockmaster.description " . LIKE . " '$SearchString' AND stockmaster.stockid " . LIKE . " '$CodeFilter' AND (stockmaster.mbflag='B' OR stockmaster.mbflag='M') $CatFilter GROUP BY stockmaster.stockid, stockmaster.description, stockmaster.units, stockmaster.mbflag, stockmaster.discontinued, stockmaster.decimalplaces ORDER BY stockmaster.stockid";
	$SearchResult = DB_query($SQL);
    unset($_POST['Search']);
}

if (isset($SearchResult) AND !isset($_POST['Select'])) {
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="aw-grid aw-grid-search">';
    echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';
    echo '<input name="SupplierID" type="hidden" value="' . $_POST['SupplierID'] . '" />';
    echo '<input type="hidden" name="Keywords" value="'.$_POST['Keywords'].'" /><input type="hidden" name="StockCat" value="'.$_POST['StockCat'].'" /><input type="hidden" name="StockCode" value="'.$_POST['StockCode'].'" />';

    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Catalog Navigation') . '</h3></div>
                <div class="aw-card-body">';
                $ListCount = DB_num_rows($SearchResult);
                $ListPageMax = ceil($ListCount / $_SESSION['DisplayRecordsMax']);
                if ($ListPageMax > 1) {
                    echo '<div class="aw-form-group">
                            <label class="aw-label">' . __('Page') . ' ' . $_POST['PageOffset'] . ' ' . __('of') . ' ' . $ListPageMax . '</label>
                            <select name="PageOffset" class="aw-select">';
                            for ($p=1; $p<=$ListPageMax; $p++) { $sel = ($p == $_POST['PageOffset']) ? 'selected' : ''; echo '<option ' . $sel . ' value=' . $p . '>' . $p . '</option>'; }
                    echo '  </select>
                          </div>
                          <div style="display:flex; gap:4px; margin-top:8px;">
                            <button type="submit" name="Previous" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('Prev') . '</button>
                            <button type="submit" name="Next" class="aw-btn aw-btn-secondary" style="flex:1;">' . __('Next') . '</button>
                          </div>';
                } else { echo '<p style="font-size:0.8rem; color:var(--text-muted);">' . __('Single page result') . '</p>'; }
    echo '      </div>
            </div>
          </aside>';

    echo '<main class="aw-main-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Selection Results') . '</h3></div>
                <div class="aw-table-wrapper">
                    <table class="aw-table">
                        <thead>
                            <tr>
                                <th>' . __('Item Code') . '</th>
                                <th>' . __('Description') . '</th>
                                <th>' . __('Units') . '</th>
                            </tr>
                        </thead>
                        <tbody>';
    DB_data_seek($SearchResult, ($_POST['PageOffset'] - 1) * $_SESSION['DisplayRecordsMax']);
    $ri = 0;
    while(($Row = DB_fetch_array($SearchResult)) AND ($ri <> $_SESSION['DisplayRecordsMax'])) {
        echo '<tr>
                <td style="font-weight:700;"><button type="submit" name="Select" value="' . $Row['stockid'] . '" class="aw-btn aw-btn-primary aw-btn-sm">' . $Row['stockid'] . '</button></td>
                <td>' . $Row['description'] . '</td>
                <td>' . $Row['units'] . '</td>
              </tr>';
        $ri++;
    }
    echo '</tbody></table></div></div></main></form></div>';
    include(__DIR__ . '/includes/footer.php'); exit();
}

foreach ($_POST as $key=>$Value) {
    if (mb_substr($key,0,6)=='Update') {
		$Index = mb_substr($key,6); $StockID = $_POST['StockID'.$Index]; $Price = filter_number_format($_POST['Price'.$Index]); $SuppUOM = $_POST['SuppUOM'.$Index]; $ConversionFactor = $_POST['ConversionFactor'.$Index]; $SupplierDescription = $_POST['SupplierDescription'.$Index]; $LeadTime = $_POST['LeadTime'.$Index]; $EffectiveFrom=$_POST['EffectiveFrom'.$Index]; $SupplierPartNo=$_POST['SupplierPartNo'.$Index]; $MinOrderQty=$_POST['MinOrderQty'.$Index];
		if (isset($_POST['Preferred'.$Index])) { $Preferred = 1; DB_query("UPDATE purchdata SET preferred=0 WHERE stockid='" . $StockID . "'"); } else { $Preferred = 0; }
		DB_query("UPDATE purchdata SET price='" . $Price . "', suppliersuom='" . $SuppUOM . "', conversionfactor='" . $ConversionFactor . "', supplierdescription='" . $SupplierDescription . "', leadtime='" . $LeadTime . "', preferred='" . $Preferred . "', effectivefrom='" . $EffectiveFrom . "', suppliers_partno='" . $SupplierPartNo . "', minorderqty='" . $MinOrderQty . "' WHERE supplierno='" . $_POST['SupplierID'] . "' AND stockid='" . $StockID . "'");
	}
	if (mb_substr($key,0,6)=='Insert') {
		$Preferred = isset($_POST['Preferred0']) ? 1 : 0;
		DB_query("INSERT INTO purchdata (stockid, supplierno, price, suppliersuom, conversionfactor, supplierdescription, leadtime, preferred, effectivefrom, suppliers_partno, minorderqty) VALUES ('" . $_POST['StockID0'] . "', '" . $_POST['SupplierID'] . "', '" . $_POST['Price0'] . "', '" . $_POST['SuppUOM0'] . "', '" . $_POST['ConversionFactor0'] . "', '" . $_POST['SupplierDescription0'] . "', '" . $_POST['LeadTime0'] . "', '" . $Preferred . "', '" . $_POST['EffectiveFrom0'] . "', '" . $_POST['SupplierPartNo0'] . "', '" . $_POST['MinOrderQty0'] . "')");
	}
}

if (isset($_GET['SupplierID'])) { $SupplierID = trim(mb_strtoupper($_GET['SupplierID'])); } elseif (isset($_POST['SupplierID'])) { $SupplierID = trim(mb_strtoupper($_POST['SupplierID'])); }

if (!isset($SupplierID) OR $SupplierID == '' OR isset($_POST['SearchSupplier'])) {
    echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="aw-grid aw-grid-search">';
    echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" />';
    echo '<aside class="aw-sidebar-side">
            <div class="aw-card">
                <div class="aw-card-header"><h3 class="aw-card-title">' . __('Filter Supplier') . '</h3></div>
                <div class="aw-card-body">
                    <div class="aw-form-group"><label class="aw-label">' . __('Supplier Name') . '</label><input type="text" name="Keywords" class="aw-input" /></div>
                    <div class="aw-form-group" style="margin-top:12px;"><label class="aw-label">' . __('OR Supplier Code') . '</label><input type="text" name="SupplierCode" class="aw-input" /></div>
                    <button type="submit" name="SearchSupplier" class="aw-btn aw-btn-primary" style="width:100%; margin-top:20px;">' . __('Find Suppliers') . '</button>
                </div>
            </div>
          </aside>';

    if (isset($_POST['SearchSupplier'])) {
        $K = (mb_strlen($_POST['Keywords']) > 0) ? '%' . str_replace(' ', '%', $_POST['Keywords']) . '%' : '%';
        $C = (mb_strlen($_POST['SupplierCode']) > 0) ? '%' . $_POST['SupplierCode'] . '%' : '%';
        $SQL = "SELECT supplierid, suppname, currcode, address1, address2 FROM suppliers WHERE suppname " . LIKE . " '$K' AND supplierid " . LIKE . " '$C'";
        $Res = DB_query($SQL);
        echo '<main class="aw-main-side">
                <div class="aw-card">
                    <div class="aw-table-wrapper">
                        <table class="aw-table">
                            <thead><tr><th>' . __('Code') . '</th><th>' . __('Name') . '</th><th>' . __('Currency') . '</th><th>' . __('Address') . '</th></tr></thead>
                            <tbody>';
        while($S = DB_fetch_array($Res)) {
            echo '<tr>
                    <td><button name="SupplierID" type="submit" value="' . $S['supplierid'] . '" class="aw-btn aw-btn-primary aw-btn-sm">' . $S['supplierid'] . '</button></td>
                    <td style="font-weight:700;">' . $S['suppname'] . '</td><td>' . $S['currcode'] . '</td><td>' . $S['address1'] . '</td>
                  </tr>';
        }
        echo '</tbody></table></div></div></main>';
    } else { echo '<main class="aw-main-side"><div class="aw-card" style="border: 2px dashed var(--border-color); background:transparent;"><div class="aw-card-body" style="text-align:center; padding:100px; color:var(--text-muted);">' . __('Search for a supplier to begin.') . '</div></div></main>'; }
    echo '</form></div>'; include(__DIR__ . '/includes/footer.php'); exit();
}

$SQL = "SELECT suppname, currcode FROM suppliers WHERE supplierid='".$SupplierID."'";
$MyRow = DB_fetch_array(DB_query($SQL));
$SuppName = $MyRow['suppname']; $CurrCode = $MyRow['currcode'];

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" class="aw-grid aw-grid-layout">';
echo '<input name="FormID" type="hidden" value="' . $_SESSION['FormID'] . '" /><input name="SupplierID" type="hidden" value="' . $SupplierID . '" />';

echo '<main class="aw-main-side">
        <div class="aw-card">
            <div class="aw-card-header">
                <h3 class="aw-card-title">' . __('Active Price List') . '</h3>
                <button type="submit" name="StockSearch" class="aw-btn aw-btn-primary aw-btn-sm" title="' . __('Add New Item') . '"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"></path></svg></button>
            </div>
            <div class="aw-table-wrapper">
                <table class="aw-table">
                    <thead>
                        <tr>
                            <th>' . __('Stock ID/Description') . '</th>
                            <th style="width:110px;">' . __('Price') . '</th>
                            <th style="width:100px;">' . __('UOM/Conv') . '</th>
                            <th>' . __('Supp. Desc/Part#') . '</th>
                            <th style="width:70px;">' . __('Lead') . '</th>
                            <th>' . __('Effective') . '</th>
                            <th style="width:50px; text-align:center;">' . __('Pref') . '</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody>';

if (isset($_POST['Select'])) {
    $SRow = DB_fetch_array(DB_query("SELECT description, units FROM stockmaster WHERE stockid='" . $_POST['Select'] . "'"));
    echo '<tr style="background:var(--primary-soft);">
            <td><input type="hidden" value="' . $_POST['Select'] . '" name="StockID0" /><div style="font-weight:950;">' . $_POST['Select'] . '</div><div style="font-size:0.75rem;">' . $SRow['description'] . '</div></td>
            <td><input type="text" class="aw-input" style="text-align:right;" value="0.0000" name="Price0" /></td>
            <td><select name="SuppUOM0" class="aw-select" style="padding:2px;">';
                $UOMR = DB_query("SELECT unitname FROM unitsofmeasure");
                while($U = DB_fetch_array($UOMR)) { $sel = ($U['unitname']==$SRow['units']) ? 'selected' : ''; echo '<option ' . $sel . ' value="'.$U['unitname'].'">' . $U['unitname'] . '</option>'; }
    echo '  </select><input class="aw-input" name="ConversionFactor0" style="text-align:right; margin-top:2px;" type="text" value="1" /></td>
            <td><input class="aw-input" name="SupplierDescription0" type="text" value="" placeholder="Vendor Desc" /><input class="aw-input" name="SupplierPartNo0" style="margin-top:2px;" type="text" value="" placeholder="Part#" /></td>
            <td><input class="aw-input" name="LeadTime0" style="text-align:right;" type="text" value="1" /></td>
            <td><input type="date" class="aw-input" name="EffectiveFrom0" value="' . date('Y-m-d') . '" /></td>
            <td style="text-align:center;"><input name="Preferred0" type="checkbox" /></td>
            <td><button name="Insert" type="submit" class="aw-btn-icon-only"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></button></td>
          </tr>';
}

$Result = DB_query("SELECT purchdata.stockid, stockmaster.description, price, suppliersuom, conversionfactor, supplierdescription, leadtime, preferred, effectivefrom, suppliers_partno, minorderqty FROM purchdata INNER JOIN stockmaster ON purchdata.stockid=stockmaster.stockid WHERE supplierno='".$SupplierID."' ORDER BY purchdata.stockid, effectivefrom DESC");
$UOMR = DB_query("SELECT unitname FROM unitsofmeasure"); $uom_list = DB_fetch_all($UOMR);
$RC = 1;
while($Row = DB_fetch_array($Result)) {
    echo '<tr>
            <td><input name="StockID'. $RC. '" type="hidden" value="' . $Row['stockid'] . '" /><div style="font-weight:700; color:var(--primary);">' . $Row['stockid'] . '</div><div style="font-size:0.75rem; color:var(--text-muted);">' . $Row['description'] . '</div></td>
            <td><input class="aw-input" style="text-align:right; font-weight:800;" type="text" value="' . locale_number_format($Row['price'], 4) . '" name="Price'.$RC.'" /></td>
            <td><select name="SuppUOM'.$RC.'" class="aw-select" style="padding:2px;">';
                foreach($uom_list as $U) { $sel = ($U['unitname']==$Row['suppliersuom']) ? 'selected' : ''; echo '<option ' . $sel . ' value="'.$U['unitname'].'">' . $U['unitname'] . '</option>'; }
    echo '  </select><input class="aw-input" name="ConversionFactor'. $RC. '" style="text-align:right; margin-top:2px;" type="text" value="' . $Row['conversionfactor'] . '" /></td>
            <td><input class="aw-input" name="SupplierDescription'. $RC. '" type="text" value="' . $Row['supplierdescription'] . '" /><input class="aw-input" name="SupplierPartNo'. $RC. '" style="margin-top:2px;" type="text" value="' . $Row['suppliers_partno'] . '" /></td>
            <td><input class="aw-input" name="LeadTime'. $RC. '" style="text-align:right;" type="text" value="' . $Row['leadtime'] . '" /></td>
            <td><input type="date" class="aw-input" name="EffectiveFrom'. $RC. '" value="' . $Row['effectivefrom'] . '" /><input class="aw-input" name="MinOrderQty'. $RC. '" style="text-align:right; margin-top:2px;" type="text" value="' . $Row['minorderqty'] . '" /></td>
            <td style="text-align:center;"><input ' . ($Row['preferred'] == 1 ? 'checked' : '') . ' name="Preferred'. $RC. '" type="checkbox" /></td>
            <td><button type="submit" name="Update'.$RC.'" class="aw-btn-icon-only"><svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"></polyline></svg></button></td>
          </tr>';
    $RC++;
}
echo '</tbody></table></div></div></main>';

echo '<aside class="aw-sidebar-side">
        <div class="aw-card">
            <div class="aw-card-header"><h3 class="aw-card-title">' . __('Vendor Details') . '</h3></div>
            <div class="aw-card-body">
                <div style="font-size:1.4rem; font-weight:950; color:var(--primary-dark);">' . $SupplierID . '</div>
                <div style="font-weight:700; margin-bottom:12px;">' . $SuppName . '</div>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; border-top:1px solid var(--border-color); padding-top:10px;">
                    <span style="color:var(--text-muted);">' . __('Primary Currency') . ':</span><span style="font-weight:850; color:var(--primary);">' . $CurrCode . '</span>
                </div>
                <hr style="border:none; border-top:1px solid var(--border-color); margin:15px 0;" />
                <button type="submit" name="SearchSupplier" class="aw-btn aw-btn-secondary" style="width:100%;">' . __('Change Supplier') . '</button>
            </div>
        </div>
        <div class="aw-card" style="background:var(--primary-dark); color:white; border:none;">
            <div class="aw-card-body" style="padding:20px;">
                <h4 style="margin:0 0 8px; font-weight:900; letter-spacing:0.02em;">' . __('Pro Tip') . '</h4>
                <p style="font-size:0.75rem; opacity:0.8; margin:0;">' . __('Updating the price will automatically overwrite the old value for this effective date. Use individual save buttons for high-performance updates.') . '</p>
            </div>
        </div>
      </aside></form></div>';

include(__DIR__ . '/includes/footer.php');
?>
