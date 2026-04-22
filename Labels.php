<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Label Templates');
$ViewTopic = 'Setup';
$BookMark = 'Labels.php';

// Inject premium Architect Workspace styles with enhanced responsiveness
$ExtraHeadContent = '
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: 20px 15px; background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; box-sizing: border-box; }
	
	.premium-header { 
        margin: -20px -15px 30px -15px;
        padding: 20px; 
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .premium-header-inner {
        display: flex; 
        justify-content: space-between; 
        align-items: center;
        max-width: 1440px;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.6rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
        display: flex; align-items: center; gap: 8px; text-transform: uppercase; 
        letter-spacing: 1px; opacity: 0.6;
    }
    .breadcrumb-wrap a { color: inherit; text-decoration: none; }
    .breadcrumb-wrap a:hover { text-decoration: underline; opacity: 1; }
	
	.db-card { 
		background: #ffffff; 
		border-radius: 16px; 
		border: 1px solid #e5e7eb; 
		box-shadow: var(--shadow-md);
		overflow: hidden;
        margin-bottom: 30px;
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
	}
	.db-card-title {
		font-size: 0.85rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 20px; }
	
    field {
        display: block;
        margin-bottom: 20px;
    }
    field label {
        font-size: 0.65rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 46px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.8rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 10px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
    .architect-btn-secondary { background: #f3f4f6; color: #4b5563; box-shadow: none; font-size: 0.75rem; }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 380px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1440px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 700px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }
    table.modern-table td select, table.modern-table td input { height: 36px; padding: 0 8px; font-size: 0.8rem; border-radius: 6px; }

    .action-badge {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9;
        color: #64748b; text-decoration: none;
    }

    @media (max-width: 1024px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
    @media (max-width: 768px) {
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .header-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .architect-btn { width: 100%; font-size: 0.75rem; }
    }
    @media (max-width: 480px) {
        .header-actions { grid-template-columns: 1fr; }
        .premium-header { padding: 15px; }
        h1 { font-size: 1.3rem !important; }
    }
</style>
<script>
    function ReloadForm(submitBtn) {
        if (submitBtn) submitBtn.click();
        else document.forms[0].submit();
    }
</script>';

// (Logic unchanged...)
$PaperSize = array();
$PaperSize['A4']['PageHeight'] = 297;
$PaperSize['A4']['PageWidth'] = 210;
$PaperSize['A5']['PageHeight'] = 210;
$PaperSize['A5']['PageWidth'] = 148;
$PaperSize['A3']['PageHeight'] = 420;
$PaperSize['A3']['PageWidth'] = 297;
$PaperSize['Letter']['PageHeight'] = 279.4;
$PaperSize['Letter']['PageWidth'] = 215.9;
$PaperSize['Legal']['PageHeight'] = 355.6;
$PaperSize['Legal']['PageWidth'] = 215.9;

include(__DIR__ . '/includes/header.php');

if (isset($_POST['SelectedLabelID'])){
	$SelectedLabelID =$_POST['SelectedLabelID'];
	if (ctype_digit($_POST['NoOfFieldsDefined'])){
		for ($i=0;$i<=$_POST['NoOfFieldsDefined'];$i++){
			if (ctype_digit($_POST['VPos' . $i]) AND ctype_digit($_POST['HPos' . $i]) AND ctype_digit($_POST['FontSize' . $i])){
				DB_query("UPDATE labelfields SET fieldvalue='" . $_POST['FieldName' . $i] . "', vpos='" . $_POST['VPos' . $i] . "', hpos='" . $_POST['HPos' . $i] . "', fontsize='" . $_POST['FontSize' . $i] . "', barcode='" . $_POST['Barcode' . $i] . "' WHERE labelfieldid='" . $_POST['LabelFieldID' . $i] . "'");
			}
		}
	}
	if (isset($_POST['FieldName']) && $_POST['FieldName'] != '' && ctype_digit($_POST['VPos']) AND ctype_digit($_POST['HPos']) AND ctype_digit($_POST['FontSize'])){
		DB_query("INSERT INTO labelfields (labelid, fieldvalue, vpos, hpos, fontsize, barcode) VALUES ('" . $SelectedLabelID . "', '" . $_POST['FieldName'] . "', '" . $_POST['VPos'] . "', '" . $_POST['HPos'] . "', '" . $_POST['FontSize'] . "', '" . $_POST['Barcode'] . "')");
	}
} elseif (isset($_GET['SelectedLabelID'])){
	$SelectedLabelID =$_GET['SelectedLabelID'];
	if (isset($_GET['DeleteField'])){
		DB_query("DELETE FROM labelfields WHERE labelfieldid='" . $_GET['DeleteField'] . "'");
	}
}

if (isset($_POST['submit'])) {
	if ( trim( $_POST['Description'] ) != '' ) {
		if (isset($_POST['PaperSize']) AND $_POST['PaperSize']!='custom'){
			$_POST['PageWidth'] = $PaperSize[$_POST['PaperSize']]['PageWidth'];
			$_POST['PageHeight'] = $PaperSize[$_POST['PaperSize']]['PageHeight'];
		}
		if (isset($SelectedLabelID)) {
			$SQL = "UPDATE labels SET description = '" . $_POST['Description'] . "', height = '" . $_POST['Height'] . "', topmargin = '". $_POST['TopMargin'] . "', width = '". $_POST['Width'] . "', leftmargin = '". $_POST['LeftMargin'] . "', rowheight =  '". $_POST['RowHeight'] . "', columnwidth = '". $_POST['ColumnWidth'] . "', pagewidth = '" . $_POST['PageWidth'] . "', pageheight = '" . $_POST['PageHeight'] . "' WHERE labelid = '" . $SelectedLabelID . "'";
			DB_query($SQL);
			prnMsg(__('The label template has been updated'), 'success');
		} else {
			$SQL = "INSERT INTO labels (description, height, topmargin, width, leftmargin, rowheight, columnwidth, pagewidth, pageheight) VALUES ('" . $_POST['Description'] . "', '" . $_POST['Height'] . "', '" . $_POST['TopMargin'] . "', '" . $_POST['Width'] . "', '" . $_POST['LeftMargin'] . "', '" . $_POST['RowHeight'] . "', '" . $_POST['ColumnWidth'] . "', '" . $_POST['PageWidth'] . "', '" . $_POST['PageHeight'] . "')";
			DB_query($SQL);
			prnMsg(__('The new label template has been added to the database'), 'success');
		}
	}
} elseif (isset($_GET['delete']) && isset($SelectedLabelID)) {
	DB_query("DELETE FROM labelfields WHERE labelid= '" . $SelectedLabelID . "'");
	DB_query("DELETE FROM labels WHERE labelid= '" . $SelectedLabelID . "'");
	prnMsg(__('The selected label template has been deleted'),'success');
	unset ($SelectedLabelID);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=stock">' . __('Inventory') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Label Setup') . '
					</div>
					<h1 style="font-size: 1.5rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <a href="' . $RootPath . '/PDFPrintLabel.php" class="architect-btn architect-btn-secondary">
                        <i class="fas fa-print"></i> ' . __('Bulk Print') . '
                    </a>
                     <button type="submit" form="label-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . __('Save Template') . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';

                if (!isset($SelectedLabelID)) {
                    $SQL = "SELECT labelid, description, pagewidth, pageheight, height, width, topmargin, leftmargin, rowheight, columnwidth FROM labels ORDER BY description";
                    $Result = DB_query($SQL);

                    echo '<div class="db-card">
                            <div class="db-card-header">
                                <h3 class="db-card-title"><i class="fas fa-layer-group"></i> ' . __('Template Library') . '</h3>
                            </div>';
                    if (DB_num_rows($Result) > 0) {
                        echo '<div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Name') . '</th>
                                            <th>' . __('Page Size') . '</th>
                                            <th>' . __('Label Dim') . '</th>
                                            <th style="width: 80px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                        while ($MyRow = DB_fetch_array($Result)) {
                            echo '<tr>
                                    <td style="font-weight: 700;">' . $MyRow['description'] . '</td>
                                    <td style="font-size: 0.75rem; color: #64748b;">' . $MyRow['pagewidth'] . 'x' . $MyRow['pageheight'] . 'mm</td>
                                    <td style="font-size: 0.75rem; color: #64748b;">' . $MyRow['height'] . 'x' . $MyRow['width'] . 'mm</td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLabelID=' . $MyRow['labelid'] . '" class="action-badge" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>
                                        <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLabelID=' . $MyRow['labelid'] . '&delete=yes" class="action-badge" style="margin-left:8px; color: #dc2626;" onclick="return confirm(\'' . __('Delete this template?') . '\')" title="' . __('Delete') . '"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>';
                        }
                        echo '      </tbody>
                                </table>
                            </div>';
                    } else {
                        echo '<div class="db-card-body" style="text-align: center; color: #64748b; padding: 40px;">' . __('No label templates found.') . '</div>';
                    }
                    echo '</div>';
                } else {
                    $SQL = "SELECT labelfieldid, labelid, fieldvalue, vpos, hpos, fontsize, barcode FROM labelfields WHERE labelid = '" . $SelectedLabelID . "' ORDER BY vpos DESC";
                    $Result = DB_query($SQL);
                    echo '<div class="db-card">
                            <div class="db-card-header">
                                <h3 class="db-card-title"><i class="fas fa-th"></i> ' . __('Field Positions') . '</h3>
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="color: #64748b; font-size: 0.8rem;"><i class="fas fa-times"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>' . __('Field') . '</th>
                                            <th>' . __('V-Pos') . '</th>
                                            <th>' . __('H-Pos') . '</th>
                                            <th>' . __('Size') . '</th>
                                            <th style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>';
                    $i = 0;
                    while ($MyRow = DB_fetch_array($Result)) {
                        echo '<tr>
                                <td>
                                    <input type="hidden" name="LabelFieldID' . $i . '" value="' . $MyRow['labelfieldid'] . '" />
                                    <select name="FieldName' . $i . '" style="width: auto;">
                                        <option ' . ($MyRow['fieldvalue']=='itemcode' ? 'selected' : '') . ' value="itemcode">' . __('Code') . '</option>
                                        <option ' . ($MyRow['fieldvalue']=='itemdescription' ? 'selected' : '') . ' value="itemdescription">' . __('Desc') . '</option>
                                        <option ' . ($MyRow['fieldvalue']=='barcode' ? 'selected' : '') . ' value="barcode">' . __('Barcode') . '</option>
                                        <option ' . ($MyRow['fieldvalue']=='price' ? 'selected' : '') . ' value="price">' . __('Price') . '</option>
                                    </select>
                                </td>
                                <td><input type="text" name="VPos' . $i . '" value="' . $MyRow['vpos'] . '" style="width: 50px; text-align: center;" /></td>
                                <td><input type="text" name="HPos' . $i . '" value="' . $MyRow['hpos'] . '" style="width: 50px; text-align: center;" /></td>
                                <td><input type="text" name="FontSize' . $i . '" value="' . $MyRow['fontsize'] . '" style="width: 40px; text-align: center;" /></td>
                                <td>
                                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedLabelID=' . $SelectedLabelID . '&amp;DeleteField=' . $MyRow['labelfieldid'] . '" style="color: #dc2626;"><i class="fas fa-times"></i></a>
                                </td>
                            </tr>';
                        $i++;
                    }
                    echo '      <tr style="background: #f8fafc;">
                                    <td>
                                        <select name="FieldName" style="border-color: #94a3b8;">
                                            <option value="">' . __('+ Add...') . '</option>
                                            <option value="itemcode">' . __('Item Code') . '</option>
                                            <option value="itemdescription">' . __('Item Description') . '</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="VPos" placeholder="0" style="width: 50px; text-align: center;" /></td>
                                    <td><input type="text" name="HPos" placeholder="0" style="width: 50px; text-align: center;" /></td>
                                    <td><input type="text" name="FontSize" placeholder="10" style="width: 40px; text-align: center;" /></td>
                                    <td></td>
                                </tr>
                            </tbody>
                            </table>
                            <input type="hidden" name="NoOfFieldsDefined" value="' . ($i-1) . '" />
                        </div></div>';
                }

echo '      </main>

            <aside class="db-sidebar" style="min-width: 0;">
                <form id="label-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

                    if (isset($SelectedLabelID)) {
                        $SQL = "SELECT pagewidth, pageheight, description, height, width, topmargin, leftmargin, rowheight, columnwidth FROM labels WHERE labelid='" . $SelectedLabelID . "'";
                        $Result = DB_query($SQL);
                        $MyRow = DB_fetch_array($Result);
                        $_POST['PageWidth'] = $MyRow['pagewidth']; $_POST['PageHeight'] = $MyRow['pageheight']; $_POST['Description'] = $MyRow['description'];
                        $_POST['Height'] = $MyRow['height']; $_POST['TopMargin'] = $MyRow['topmargin']; $_POST['Width'] = $MyRow['width'];
                        $_POST['LeftMargin'] = $MyRow['leftmargin']; $_POST['RowHeight'] = $MyRow['rowheight']; $_POST['ColumnWidth'] = $MyRow['columnwidth'];
                        foreach ($PaperSize as $PaperName=>$PaperType) { if ($PaperType['PageWidth'] == $MyRow['pagewidth'] AND $PaperType['PageHeight'] == $MyRow['pageheight']) $_POST['PaperSize'] = $PaperName; }
                        echo '<input type="hidden" name="SelectedLabelID" value="' . $SelectedLabelID . '" />';
                    }

                    if (!isset($_POST['Description'])) $_POST['Description'] = '';
                    if (!isset($_POST['PageHeight'])) $_POST['PageHeight'] = 0; if (!isset($_POST['PageWidth'])) $_POST['PageWidth'] = 0;
                    if (!isset($_POST['Height'])) $_POST['Height'] = 0; if (!isset($_POST['TopMargin'])) $_POST['TopMargin'] = 5;
                    if (!isset($_POST['Width'])) $_POST['Width'] = 0; if (!isset($_POST['LeftMargin'])) $_POST['LeftMargin'] = 10;

                    echo '<div class="db-card" style="border-color: #059669;">
                            <div class="db-card-header" style="background: #f0fdf4;">
                                <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedLabelID) ? __('Edit Template') : __('New Template')) . '</h3>
                            </div>
                            <div class="db-card-body">
                                <field>
                                    <label>' . __('Name') . '</label>
                                    <input type="text" name="Description" required maxlength="20" placeholder="' . __('e.g. A4 Standard') . '" value="' . $_POST['Description'] . '" />
                                </field>
                                
                                <field>
                                    <label>' . __('Paper') . '</label>
                                    <select name="PaperSize" onchange="ReloadForm(document.getElementsByName(\'submit\')[0])">';
                                    echo '<option ' . (!isset($_POST['PaperSize']) ? 'selected' : '') . ' value="custom">' . __('Custom Size') . '</option>';
                                    foreach($PaperSize as $PaperType=>$PaperSizeElement) { echo '<option ' . (isset($_POST['PaperSize']) && $PaperType==$_POST['PaperSize'] ? 'selected' : '') . ' value="' . $PaperType . '">' . $PaperType . '</option>'; }
                                echo '      </select>
                                </field>';
                                
                        echo '  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <field><label>' . __('Lb He') . '</label><input type="text" name="Height" value="' . $_POST['Height'] . '" /></field>
                                    <field><label>' . __('Lb Wi') . '</label><input type="text" name="Width" value="' . $_POST['Width'] . '" /></field>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <field><label>' . __('Tm') . '</label><input type="text" name="TopMargin" value="' . $_POST['TopMargin'] . '" /></field>
                                    <field><label>' . __('Lm') . '</label><input type="text" name="LeftMargin" value="' . $_POST['LeftMargin'] . '" /></field>
                                </div>
                                
                                <div style="margin-top: 10px;">
                                    <button type="submit" name="submit" class="architect-btn" style="width: 100%;">
                                        <i class="fas fa-save"></i> ' . __('Update') . '
                                    </button>
                                </div>
                            </div>
                        </div>';

                echo '  <div style="background: #f8fafc; border-radius: 12px; padding: 15px; text-align: center; border: 1px dashed #cbd5e1;">
                            <img src="css/paramsLabel.png" style="max-width: 100%;" alt="Guide">
                        </div>';

                echo '  </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
