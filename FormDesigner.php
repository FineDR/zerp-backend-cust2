<?php

/*	Allows to customize the form layout without requiring the use of scripting or technical development. */

require(__DIR__ . '/includes/session.php');

$Title = __('Form Designer');
$ViewTopic = 'Setup';
$BookMark = 'FormDesigner';

// Inject premium Architect Workspace styles
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
        max-width: 1400px;
        margin: 0 auto;
        gap: 20px;
    }
	
    .breadcrumb-wrap { 
        font-size: 0.65rem; font-weight: 850; color: #6b7280; margin-bottom: 4px; 
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
        margin-bottom: 24px;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 12px 16px;
        display: flex; justify-content: space-between; align-items: center;
	}
	.db-card-title {
		font-size: 0.65rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 16px; }
	
    field { display: block; margin-bottom: 12px; }
    field label {
        font-size: 0.6rem; text-transform: uppercase; font-weight: 900; letter-spacing: 0.8px; 
        color: #064e3b; margin-bottom: 4px; opacity: 0.7; display: block;
    }
    field input, field select {
        width: 100%; border-radius: 8px; height: 38px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 10px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.85rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; font-family: inherit;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }

    .db-grid { 
        display: grid; 
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); 
        gap: 20px; 
    }

    .info-bar { border-left: 4px solid #059669; background: #ecfdf5; padding: 15px 20px; border-radius: 0 10px 10px 0; margin-bottom: 30px; font-size: 0.85rem; color: #065f46; line-height: 1.5; font-weight: 600; }

    @media (max-width: 768px) {
        .premium-header-inner { flex-direction: column; text-align: center; }
        .architect-btn { width: 100%; }
        .db-grid { grid-template-columns: 1fr; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

function InputX($keyName, $keyValue) {
	echo '<field><label>', __('X Coordinate (mm)'), '</label><input type="number" id="', $keyName, 'x" name="', $keyName, 'x" value="', $keyValue, '" title="', __('Distance from left edge in points'), '" /></field>';
}
function InputY($keyName, $keyValue) {
	echo '<field><label>', __('Y Coordinate (mm)'), '</label><input type="number" id="', $keyName, 'y" name="', $keyName, 'y" value="', $keyValue, '" title="', __('Distance from lower edge in points'), '" /></field>';
}
function InputWidth($keyName, $keyValue, $isLength = false) {
    $fieldName = $isLength ? 'Length' : 'width';
	echo '<field><label>', __('Width (points)'), '</label><input type="number" id="', $keyName, $fieldName, '" name="', $keyName, $fieldName, '" value="', $keyValue, '" /></field>';
}
function InputHeight($keyName, $keyValue) {
	echo '<field><label>', __('Height (points)'), '</label><input type="number" id="', $keyName, 'height" name="', $keyName, 'height" value="', $keyValue, '" /></field>';
}
function InputFontSize($keyName, $keyValue) {
	echo '<field><label>', __('Font Size'), '</label><input type="number" id="', $keyName, 'FontSize" name="', $keyName, 'FontSize" value="', $keyValue, '" /></field>';
}
function SelectAlignment($keyName, $keyValue) {
	$Alignments = ['left' => __('Left'), 'centre' => __('Centre'), 'right' => __('Right'), 'full' => __('Justify')];
	echo '<field><label>', __('Alignment'), '</label><select name="', $keyName, 'Alignment">';
	foreach ($Alignments as $val => $cap) {
		echo '<option value="' . $val . '" ' . ($val == $keyValue ? 'selected' : '') . '>' . $cap . '</option>';
	}
	echo '</select></field>';
}

$PaperSizes = ['A3_Portrait', 'A3_Landscape', 'A4_Portrait', 'A4_Landscape', 'A5_Portrait', 'A5_Landscape', 'A6_Portrait', 'A6_Landscape', 'Legal_Portrait', 'Legal_Landscape', 'Letter_Portrait', 'Letter_Landscape'];

if (isset($_POST['preview']) or isset($_POST['save'])) {
	$FormDesign = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/' . $_POST['FormName']);
	$FormDesign['name'] = $_POST['formname'];
	if (mb_substr($_POST['PaperSize'], -8) == 'Portrait') { $_POST['PaperSize'] = mb_substr($_POST['PaperSize'], 0, mb_strlen($_POST['PaperSize']) - 9); }
	$FormDesign->PaperSize = $_POST['PaperSize'];
	$FormDesign->LineHeight = $_POST['LineHeight'];
	foreach ($FormDesign as $key) {
		foreach ($key as $subkey => $Value) {
			if ($key['type'] == 'ElementArray') {
				foreach ($Value as $subsubkey => $subvalue) { $Value->$subsubkey = $_POST[$Value['id'] . $subsubkey]; }
			} else { $key->$subkey = $_POST[$key['id'] . $subkey]; }
		}
	}
	if (isset($_POST['preview'])) {
		$FormDesign->asXML(sys_get_temp_dir() . '/' . $_POST['FormName']);
		switch ($_POST['FormName']) {
			case 'PurchaseOrder.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PO_PDFPurchOrder.php?OrderNo=Preview">'; break;
			case 'GoodsReceived.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFGrn.php?GRNNo=Preview&PONo=1">'; break;
			case 'PickingList.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFPickingList.php?TransNo=Preview">'; break;
			case 'QALabel.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFQALabel.php?GRNNo=Preview&PONo=1">'; break;
			case 'WOPaperwork.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFWOPrint.php?WO=Preview">'; break;
			case 'FGLabel.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFFGLabel.php?WO=Preview">'; break;
			case 'ShippingLabel.xml': echo '<meta http-equiv="Refresh" content="0; url=' . $RootPath . '/PDFShipLabel.php?ORD=Preview">'; break;
		}
	} else {
		if (is_writable($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/' . $_POST['FormName'])) {
			$FormDesign->asXML($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/' . $_POST['FormName']);
            prnMsg(__('Changes saved successfully'), 'success');
		} else { prnMsg(__('No write permissions on XML file'), 'error'); }
	}
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=system">' . __('Setup') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Form Designer') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . (isset($_POST['FormName']) ? (string)$FormDesign['name'] : __('Select Form')) . '</h1>
				</div>
                <div class="header-actions">
                    ' . (isset($_POST['FormName']) ? '
                        <button type="submit" form="Form" name="preview" class="architect-btn" style="background:#64748b; margin-right:10px;"><i class="fas fa-eye"></i> ' . __('Preview PDF') . '</button>
                        <button type="submit" form="Form" name="save" class="architect-btn"><i class="fas fa-save"></i> ' . __('Save Layout') . '</button>
                    ' : '') . '
                </div>
			</div>
		</div>';

if (empty($_POST['FormName'])) {
	echo '<div class="db-card" style="max-width: 500px; margin: 50px auto;">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-file-invoice"></i> ' . __('Module Template Selection') . '</h3></div>
            <div class="db-card-body">
                <form action="'. htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'). '" method="post">
                    <input name="FormID" type="hidden" value="'. $_SESSION['FormID']. '" />
                    <field>
                        <label>' . __('Choose a form to redesign') . '</label>
                        <select name="FormName">';
                        if ($Handle = opendir($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/')) {
                            while (false !== ($File = readdir($Handle))) {
                                if ($File[0] != '.') {
                                    $FD = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/' . $File);
                                    echo '<option value="', $File, '">' . $FD['name'] . '</option>';
                                }
                            }
                            closedir($Handle);
                        }
                echo '  </select>
                    </field>
                    <button type="submit" class="architect-btn" style="width: 100%;"><i class="fas fa-chevron-right"></i> ' . __('Enter Designer') . '</button>
                </form>
            </div>
        </div>';
	include(__DIR__ . '/includes/footer.php');
	exit();
}

$FormDesign = simplexml_load_file($PathPrefix . 'companies/' . $_SESSION['DatabaseName'] . '/FormDesigns/' . $_POST['FormName']);

echo '<div class="info-bar">
        <i class="fas fa-circle-info"></i> ' . __('Grid Reference: All measurements are in PostScript points (72 points = 25.4 mm). Coordinates (X,Y) are measured from the lower-left corner of the sheet to the top-left of each field.') . '
      </div>';

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" id="Form" method="post" >
        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
        <input name="FormName" type="hidden" value="' . $_POST['FormName'] . '" />

        <div class="db-card">
            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-file-export"></i> ' . __('Global Page Configuration') . '</h3></div>
            <div class="db-card-body" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
                <field>
                    <label>' . __('Designer Reference Name') . '</label>
                    <input name="formname" type="text" value="', $FormDesign['name'], '" />
                </field>
                <field>
                    <label>' . __('Standard Paper Size') . '</label>
                    <select name="PaperSize">';
                    foreach ($PaperSizes as $Paper) {
                        $PaperValue = (mb_substr($Paper, -8) == 'Portrait') ? mb_substr($Paper, 0, mb_strlen($Paper) - 9) : $Paper;
                        echo '<option value="', $PaperValue, '" ' . ($PaperValue == $FormDesign->PaperSize ? 'selected' : '') . '>', $Paper, '</option>';
                    }
                echo '</select>
                </field>
                <field>
                    <label>' . __('Line Height (pts)') . '</label>
                    <input type="number" name="LineHeight" value="', $FormDesign->LineHeight, '" />
                </field>
            </div>
        </div>

        <div class="db-grid">';
        foreach ($FormDesign as $key) {
            echo '<div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-layer-group"></i> ' . _((string)$key['name']) . '</h3></div>
                    <div class="db-card-body">';
                switch ($key['type']) {
                    case 'image':
                    case 'Rectangle':
                        InputX($key['id'], $key->x);
                        InputY($key['id'], $key->y);
                        InputWidth($key['id'], $key->width);
                        InputHeight($key['id'], $key->height);
                    break;
                    case 'SimpleText':
                        InputX($key['id'], $key->x);
                        InputY($key['id'], $key->y);
                        InputFontSize($key['id'], $key->FontSize);
                    break;
                    case 'MultiLineText':
                        InputX($key['id'], $key->x);
                        InputY($key['id'], $key->y);
                        InputWidth($key['id'], $key->Length, true);
                        InputFontSize($key['id'], $key->FontSize);
                    break;
                    case 'CurvedRectangle':
                        InputX($key['id'], $key->x); InputY($key['id'], $key->y);
                        InputWidth($key['id'], $key->width); InputHeight($key['id'], $key->height);
                        echo '<field><label>Radius</label><input type="number" name="', $key['id'], 'radius" value="', $key->radius, '" /></field>';
                    break;
                    case 'Line':
                        echo '<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <field><label>Start X</label><input type="number" name="'.$key['id'].'startx" value="'.$key->startx.'" /></field>
                                <field><label>Start Y</label><input type="number" name="'.$key['id'].'starty" value="'.$key->starty.'" /></field>
                                <field><label>End X</label><input type="number" name="'.$key['id'].'endx" value="'.$key->endx.'" /></field>
                                <field><label>End Y</label><input type="number" name="'.$key['id'].'endy" value="'.$key->endy.'" /></field>
                              </div>';
                    break;
                    case 'ElementArray':
                        foreach ($key as $subkey) {
                            echo '<div style="border: 1px solid #f1f5f9; padding: 12px; border-radius:8px; margin-bottom:10px; background:#fafafa;">
                                    <div style="font-size:0.6rem; font-weight:900; color:#64748b; margin-bottom:8px; border-bottom:1px solid #eee; padding-bottom:4px;">' . _((string)$subkey['name']) . '</div>';
                                if ($subkey['type'] == 'SimpleText') {
                                    InputX($subkey['id'], $subkey->x); InputY($subkey['id'], $subkey->y); InputFontSize($subkey['id'], $subkey->FontSize);
                                } elseif ($subkey['type'] == 'MultiLineText') {
                                    InputX($subkey['id'], $subkey->x); InputY($subkey['id'], $subkey->y); InputWidth($subkey['id'], $subkey->Length, true); InputFontSize($subkey['id'], $subkey->FontSize);
                                } elseif ($subkey['type'] == 'DataText') {
                                    InputX($subkey['id'], $subkey->x); InputWidth($subkey['id'], $subkey->Length, true); InputFontSize($subkey['id'], $subkey->FontSize);
                                } elseif ($subkey['type'] == 'StartLine') {
                                    echo '<field><label>Y Start</label><input type="number" name="StartLine" value="' . $key->y . '" /></field>';
                                }
                            echo '</div>';
                        }
                    break;
                }
            echo '  </div>
                  </div>';
        }
echo '  </div>
      </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
