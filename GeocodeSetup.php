<?php
require (__DIR__ . '/includes/session.php');

$Title = __('Geocode Maintenance');
$ViewTopic = 'Setup';
$BookMark = '';

// Inject modern Architect styles
echo '<style>
    :root {
        --primary: #059669;
        --primary-dark: #065f46;
        --primary-light: #ecfdf5;
        --page-padding: 30px;
    }
    .db-page { padding: 0 var(--page-padding); max-width: 1400px; margin: 0 auto; }
    .premium-header { 
        margin-bottom: 24px; padding: 24px 30px; background: #fff;
        border-bottom: 1px solid #e5e7eb; border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        display: flex; justify-content: space-between; align-items: center;
    }
    
    .db-bottom-layout { display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start; padding-bottom: 50px; }
    
    .arch-card { 
        background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; 
        box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 24px;
    }
    .arch-card-header { 
        background: #f8fafc; border-bottom: 1px solid #f1f5f9; padding: 16px 24px;
        display: flex; justify-content: space-between; align-items: center;
    }
    .arch-card-title {
        font-size: 0.9rem; font-weight: 800; color: #1e293b; margin:0;
        text-transform: uppercase; letter-spacing: 0.5px;
        display: flex; align-items: center; gap: 8px;
    }

    .arch-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 10px 20px; border-radius: 8px;
        background: var(--primary); color: #fff; border: none;
        font-weight: 700; font-size: 0.8rem; cursor: pointer;
        transition: all 0.2s; text-decoration: none;
    }
    .arch-btn:hover { background: var(--primary-dark); transform: translateY(-1px); }
    .arch-btn-secondary { background: #f1f5f9; color: #475569; }

    .arch-form-label { display: block; font-size: 0.75rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
    .arch-form-input { 
        width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #e2e8f0;
        font-size: 0.9rem; transition: border-color 0.2s; background: #fcfcfc;
    }
    .arch-form-input:focus { outline: none; border-color: var(--primary); background: #fff; }

    .smooth-table { width: 100%; border-collapse: collapse; }
    .smooth-table th { background: #f8fafc; padding: 12px 16px; text-align: left; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .smooth-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; }
    .smooth-table tr:hover { background: #f8fafc; }

    .action-link { 
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 8px; transition: all 0.2s; color: #64748b; background: #f1f5f9; text-decoration: none; border: none; cursor: pointer;
    }
    .action-link:hover { background: var(--primary-light); color: var(--primary); }
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
';

include ('includes/header.php');

if (isset($_GET['SelectedParam'])) {
	$SelectedParam = $_GET['SelectedParam'];
} elseif (isset($_POST['SelectedParam'])) {
	$SelectedParam = $_POST['SelectedParam'];
}

$Errors = array();
$InputError = 0;

if (isset($_POST['submit'])) {
	$i = 1;

	$SQL = "SELECT count(geocodeid) FROM geocode_param WHERE geocodeid='" . $_POST['GeoCodeID'] . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);

	if ($MyRow[0] != 0 and !isset($SelectedParam)) {
		$InputError = 1;
		prnMsg(__('That geocode ID already exists in the database'), 'error');
		$Errors[$i] = 'GeoCodeID';
		$i++;
	}

	$Msg = '';
	if (isset($SelectedParam) and $InputError != 1) {
		$SQL = "UPDATE geocode_param SET
				center_long='" . $_POST['Center_Long'] . "',
				center_lat='" . $_POST['Center_Lat'] . "',
				map_height='" . $_POST['Map_Height'] . "',
				map_width='" . $_POST['Map_Width'] . "'
				WHERE geocodeid = '" . $SelectedParam . "'";
		$Msg = __('The geocode status record has been updated');
	} elseif ($InputError != 1) {
		if (isset($_POST['GeoCode_Key']) and $_POST['GeoCode_Key'] > 0) {
			$SQL = "INSERT INTO geocode_param (geocodeid, center_long, center_lat, map_height, map_width)
					VALUES ('', '" . $_POST['Center_Long'] . "', '" . $_POST['Center_Lat'] . "', '" . $_POST['Map_Height'] . "', '" . $_POST['Map_Width'] . "')";
		} else {
			$SQL = "INSERT INTO geocode_param (geocodeid, center_long, center_lat, map_height, map_width)
					VALUES ('" . $_POST['GeoCodeID'] . "', '" . $_POST['Center_Long'] . "', '" . $_POST['Center_Lat'] . "', '" . $_POST['Map_Height'] . "', '" . $_POST['Map_Width'] . "')";
		}
		$Msg = __('A new geocode status record has been inserted');
		unset($SelectedParam);
		unset($_POST['GeoCode_Key']);
	}
	$Result = DB_query($SQL);
	if ($Msg != '') prnMsg($Msg, 'success');
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM geocode_param WHERE geocodeid = '" . $_GET['delete'] . "' LIMIT 1";
	$Result = DB_query($SQL);
	prnMsg(__('Geocode deleted'), 'success');
	unset($_GET['delete']);
	unset($SelectedParam);
}

echo '<div class="db-page">
		<header class="premium-header">
            <div>
                <div style="font-size: 0.7rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">' . __('Configuration') . '</div>
                <h1 style="font-size: 1.8rem; font-weight: 900; letter-spacing: -1px; color: #1e293b; margin: 0;">' . __('Geocode Setup') . '</h1>
            </div>
            <div style="display:flex; gap:12px;">
                <a href="geocode.php" class="arch-btn">
                    <i class="fas fa-play"></i> ' . __('Run Geocode Process') . '
                </a>
            </div>
		</header>';

echo '<div class="db-bottom-layout">
        <aside class="db-col-aside">
            <div class="arch-card">
                <div class="arch-card-header">
                    <h3 class="arch-card-title"><i class="fas fa-list-check"></i> ' . __('Defined Codes') . '</h3>
                </div>
                <div style="overflow-x:auto;">
                    <table class="smooth-table">
                        <thead>
                            <tr>
                                <th>' . __('ID') . '</th>
                                <th>' . __('Actions') . '</th>
                            </tr>
                        </thead>
                        <tbody>';

$SQL = "SELECT geocodeid, center_long, center_lat, map_height, map_width FROM geocode_param";
$Result = DB_query($SQL);
while ($MyRow = DB_fetch_array($Result)) {
    echo '<tr>
            <td style="font-weight:800; color:var(--primary);">' . $MyRow['geocodeid'] . '</td>
            <td>
                <div style="display:flex; gap:8px;">
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedParam=' . $MyRow[0] . '" class="action-link" title="' . __('Edit') . '">
                        <i class="fas fa-pen-to-square"></i>
                    </a>
                    <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedParam=' . $MyRow[0] . '&delete=' . $MyRow[0] . '" class="action-link" title="' . __('Delete') . '" style="color:#ef4444;" onclick="return confirm(\'' . __('Confirm Delete?') . '\')">
                        <i class="fas fa-trash-can"></i>
                    </a>
                </div>
            </td>
          </tr>';
}

echo '                  </tbody>
                    </table>
                </div>
            </div>
            
            <div class="arch-card" style="background:var(--primary-light); border-color:#d1fae5;">
                <div style="padding:20px;">
                    <h4 style="font-size:0.7rem; font-weight:900; color:var(--primary-dark); text-transform:uppercase; margin:0 0 12px 0;">
                        <i class="fas fa-circle-info"></i> ' . __('Quick Help') . '
                    </h4>
                    <p style="font-size:0.8rem; color:#065f46; line-height:1.5; margin:0;">
                        ' . __('Find coordinates at ') . '<a href="//www.openstreetmap.org/" target="_blank" style="color:var(--primary-dark); font-weight:700;">openstreetmap.org</a>. ' . __('Set the maps centre point using Longitude / Latitude.') . '
                    </p>
                </div>
            </div>
        </aside>

        <main class="db-col-main">';

if (!isset($_GET['delete'])) {
    echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

    if (isset($SelectedParam) and ($InputError != 1)) {
        $SQL = "SELECT geocodeid, geocode_key, center_long, center_lat, map_height, map_width, map_host FROM geocode_param WHERE geocodeid='" . $SelectedParam . "'";
        $Result = DB_query($SQL);
        $MyRow = DB_fetch_array($Result);
        $_POST['GeoCodeID'] = $MyRow['geocodeid'];
        $_POST['GeoCode_Key'] = $MyRow['geocode_key'];
        $_POST['Center_Long'] = $MyRow['center_long'];
        $_POST['Center_Lat'] = $MyRow['center_lat'];
        $_POST['Map_Height'] = $MyRow['map_height'];
        $_POST['Map_Width'] = $MyRow['map_width'];
        $_POST['Map_Host'] = $MyRow['map_host'];

        echo '<input type="hidden" name="SelectedParam" value="' . $SelectedParam . '" />';
        echo '<input type="hidden" name="GeoCodeID" value="' . $_POST['GeoCodeID'] . '" />';
    } else {
        if (!isset($_POST['GeoCodeID'])) $_POST['GeoCodeID'] = '';
        $_POST['Center_Long'] = $_POST['Center_Long'] ?? '';
        $_POST['Center_Lat'] = $_POST['Center_Lat'] ?? '';
        $_POST['Map_Height'] = $_POST['Map_Height'] ?? '';
        $_POST['Map_Width'] = $_POST['Map_Width'] ?? '';
    }

    echo '<div class="arch-card">
            <div class="arch-card-header">
                <h3 class="arch-card-title"><i class="fas fa-map-location-dot"></i> ' . (isset($SelectedParam) ? __('Edit Configuration') : __('New Geocode Parameter')) . '</h3>
            </div>
            <div style="padding:24px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">';

    if (!isset($SelectedParam)) {
        echo '<div>
                <label class="arch-form-label">' . __('Geocode ID') . '</label>
                <input type="text" class="arch-form-input" name="GeoCodeID" value="' . $_POST['GeoCodeID'] . '" size="3" maxlength="2" placeholder="e.g. 01">
              </div><div></div>';
    } else {
        echo '<div>
                <label class="arch-form-label">' . __('Geocode ID') . '</label>
                <div style="font-weight:900; font-size:1.5rem; color:var(--primary);">' . $_POST['GeoCodeID'] . '</div>
              </div><div></div>';
    }

    echo '      <div>
                    <label class="arch-form-label">' . __('Center Longitude') . '</label>
                    <input type="text" class="arch-form-input" name="Center_Long" value="' . $_POST['Center_Long'] . '" placeholder="e.g. 45.000">
                </div>
                <div>
                    <label class="arch-form-label">' . __('Center Latitude') . '</label>
                    <input type="text" class="arch-form-input" name="Center_Lat" value="' . $_POST['Center_Lat'] . '" placeholder="e.g. -1.000">
                </div>
                <div>
                    <label class="arch-form-label">' . __('Map Height (px)') . '</label>
                    <input type="text" class="arch-form-input" name="Map_Height" value="' . $_POST['Map_Height'] . '" placeholder="e.g. 600">
                </div>
                <div>
                    <label class="arch-form-label">' . __('Map Width (px)') . '</label>
                    <input type="text" class="arch-form-input" name="Map_Width" value="' . $_POST['Map_Width'] . '" placeholder="e.g. 800">
                </div>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:12px; padding:20px; background:#f8fafc; border-top:1px solid #f1f5f9;">
                ' . (isset($SelectedParam) ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="arch-btn arch-btn-secondary">' . __('Cancel') . '</a>' : '') . '
                <button type="submit" name="submit" class="arch-btn">
                    <i class="fas fa-floppy-disk"></i> ' . __('Save Configuration') . '
                </button>
            </div>
        </div>
    </form>';

    echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
            <div class="arch-card">
                <div style="padding:24px; text-align:center;">
                    <div style="width:64px; height:64px; background:var(--primary-light); color:var(--primary); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:16px;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h4 style="margin:0 0 8px 0; font-weight:800;">' . __('Customer Map') . '</h4>
                    <p style="font-size:0.8rem; color:#64748b; margin-bottom:20px;">' . __('View spatial distribution of customer branches.') . '</p>
                    <a href="geo_displaymap_customers.php" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">' . __('Display Customers') . '</a>
                </div>
            </div>
            <div class="arch-card">
                <div style="padding:24px; text-align:center;">
                    <div style="width:64px; height:64px; background:var(--primary-light); color:var(--primary); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:1.5rem; margin-bottom:16px;">
                        <i class="fas fa-truck-field"></i>
                    </div>
                    <h4 style="margin:0 0 8px 0; font-weight:800;">' . __('Supplier Map') . '</h4>
                    <p style="font-size:0.8rem; color:#64748b; margin-bottom:20px;">' . __('Visualize the geographical reach of your suppliers.') . '</p>
                    <a href="geo_displaymap_suppliers.php" class="arch-btn arch-btn-secondary" style="width:100%; justify-content:center;">' . __('Display Suppliers') . '</a>
                </div>
            </div>
          </div>';
}

echo '  </main>
    </div>
</div>';

include ('includes/footer.php');
?>
