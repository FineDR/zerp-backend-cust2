<?php
require (__DIR__ . '/includes/session.php');

$Title = __('Geocode Maintenance');
$ViewTopic = 'Setup';
$BookMark = 'GeocodeSetup';

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
        max-width: 100%;
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
        width: 100%;
        box-sizing: border-box;
	}
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center;
	}
	.db-card-title {
		font-size: 0.8rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
		text-transform: uppercase;
		letter-spacing: 0.8px;
	}
    .db-card-body { padding: 24px; }
	
    field { display: block; margin-bottom: 18px; }
    field label {
        font-size: 0.62rem; text-transform: uppercase; font-weight: 900; letter-spacing: 0.8px; 
        color: #064e3b; display: block; margin-bottom: 6px; opacity: 0.75;
    }
    field input {
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 8px;
		padding: 12px 24px; border-radius: 10px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }

    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; }
    table.modern-table th, table.modern-table td { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .identity-badge { 
        display: inline-flex; width: 32px; height: 32px; align-items: center; justify-content: center;
        background: #ecfdf5; color: #059669; border-radius: 8px; font-weight: 850; font-size: 0.75rem; border: 1px solid #d1fae5;
    }

    .help-box { background: #f0fdf4; border: 1px solid #dcfce7; border-radius: 12px; padding: 20px; margin-top: 24px; }
    .help-box h4 { margin: 0 0 10px 0; font-size: 0.7rem; font-weight: 900; color: #166534; text-transform: uppercase; letter-spacing: 0.5px; }
    .help-box p { margin: 0; font-size: 0.8rem; color: #14532d; line-height: 1.5; font-weight: 500; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout main { order: 1; }
        .db-bottom-layout aside { order: 2; }
    }
</style>';

include ('includes/header.php');

if (isset($_GET['SelectedParam'])) {
	$SelectedParam = $_GET['SelectedParam'];
} elseif (isset($_POST['SelectedParam'])) {
	$SelectedParam = $_POST['SelectedParam'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (isset($SelectedParam)) {
		$SQL = "UPDATE geocode_param SET center_long='" . $_POST['Center_Long'] . "', center_lat='" . $_POST['Center_Lat'] . "', map_height='" . $_POST['Map_Height'] . "', map_width='" . $_POST['Map_Width'] . "' WHERE geocodeid = '" . $SelectedParam . "'";
		$Msg = __('The geocode status record has been updated');
	} else {
		$SQL = "INSERT INTO geocode_param (geocodeid, center_long, center_lat, map_height, map_width) VALUES ('" . $_POST['GeoCodeID'] . "', '" . $_POST['Center_Long'] . "', '" . $_POST['Center_Lat'] . "', '" . $_POST['Map_Height'] . "', '" . $_POST['Map_Width'] . "')";
		$Msg = __('A new geocode status record has been inserted');
	}
	DB_query($SQL);
	prnMsg($Msg, 'success');
} elseif (isset($_GET['delete'])) {
	DB_query("DELETE FROM geocode_param WHERE geocodeid = '" . $_GET['delete'] . "' LIMIT 1");
	prnMsg(__('Geocode deleted'), 'success');
	unset($SelectedParam);
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
                        ' . __('Geocode Parameters') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <a href="geocode.php" class="architect-btn">
                        <i class="fas fa-play"></i> ' . __('Run Geocode Process') . '
                    </a>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                // List of defined parameters
                $SQL = "SELECT geocodeid, center_long, center_lat, map_height, map_width FROM geocode_param";
                $Res = DB_query($SQL);
                echo '<div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-map-marked-alt"></i> ' . __('Active Coordinate Definitions') . '</h3></div>
                        <div class="table-responsive">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">' . __('ID') . '</th>
                                        <th>' . __('Center Coordinates') . '</th>
                                        <th>' . __('Map Size (px)') . '</th>
                                        <th style="width: 120px; text-align: right;">' . __('Actions') . '</th>
                                    </tr>
                                </thead>
                                <tbody>';
                while ($MyRow = DB_fetch_array($Res)) {
                    echo '<tr>
                            <td><span class="identity-badge">' . $MyRow['geocodeid'] . '</span></td>
                            <td style="font-weight: 700; color: #064e3b;">' . $MyRow['center_lat'] . ' , ' . $MyRow['center_long'] . '</td>
                            <td style="color: #64748b; font-weight: 600;">' . $MyRow['map_width'] . ' x ' . $MyRow['map_height'] . '</td>
                            <td style="text-align: right;">
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedParam=' . $MyRow[0] . '" style="color: #059669; font-weight:700; margin-right:15px; text-decoration:none;"><i class="fas fa-pen"></i></a>
                                <a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?SelectedParam=' . $MyRow[0] . '&delete=' . $MyRow[0] . '" style="color: #ef4444;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash"></i></a>
                            </td>
                          </tr>';
                }
                echo '      </tbody>
                            </table>
                        </div>
                      </div>

                      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                        <div class="db-card">
                            <div class="db-card-body" style="text-align:center;">
                                <i class="fas fa-users" style="font-size: 2rem; color: #059669; margin-bottom: 12px; opacity: 0.4;"></i>
                                <h4 style="margin:0 0 15px 0; font-weight:800; color:#064e3b;">' . __('Customer Location Map') . '</h4>
                                <a href="geo_displaymap_customers.php" class="architect-btn" style="width:100%; box-shadow:none; background:#ecfdf5; color:#059669;">' . __('Open Customer Map') . '</a>
                            </div>
                        </div>
                        <div class="db-card">
                            <div class="db-card-body" style="text-align:center;">
                                <i class="fas fa-truck-ramp-box" style="font-size: 2rem; color: #059669; margin-bottom: 12px; opacity: 0.4;"></i>
                                <h4 style="margin:0 0 15px 0; font-weight:800; color:#064e3b;">' . __('Supplier Location Map') . '</h4>
                                <a href="geo_displaymap_suppliers.php" class="architect-btn" style="width:100%; box-shadow:none; background:#ecfdf5; color:#059669;">' . __('Open Supplier Map') . '</a>
                            </div>
                        </div>
                      </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedParam)) {
                    $Result = DB_query("SELECT * FROM geocode_param WHERE geocodeid='" . $SelectedParam . "'");
                    $MyRow = DB_fetch_array($Result);
                    $_POST['GeoCodeID'] = $MyRow['geocodeid']; $_POST['Center_Long'] = $MyRow['center_long']; 
                    $_POST['Center_Lat'] = $MyRow['center_lat']; $_POST['Map_Height'] = $MyRow['map_height']; $_POST['Map_Width'] = $MyRow['map_width'];
                }
                echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                        <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                        ' . (isset($SelectedParam) ? '<input type="hidden" name="SelectedParam" value="' . $SelectedParam . '" />' : '') . '
                        <div class="db-card">
                            <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-crosshairs"></i> ' . (isset($SelectedParam) ? __('Edit Configuration') : __('New Definition')) . '</h3></div>
                            <div class="db-card-body">
                                <field>
                                    <label>' . __('Geocode ID') . '</label>
                                    ' . (isset($SelectedParam) ? '<div style="font-weight:900; color:#059669; font-size:1.2rem;">' . $_POST['GeoCodeID'] . '</div>' : '<input type="text" name="GeoCodeID" required maxlength="2" placeholder="e.g. 01" />') . '
                                </field>
                                <field>
                                    <label>' . __('Latitude') . '</label>
                                    <input type="text" name="Center_Lat" required value="' . ($_POST['Center_Lat'] ?? '') . '" placeholder="e.g. -1.2863" />
                                </field>
                                <field>
                                    <label>' . __('Longitude') . '</label>
                                    <input type="text" name="Center_Long" required value="' . ($_POST['Center_Long'] ?? '') . '" placeholder="e.g. 36.8172" />
                                </field>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <field><label>' . __('Map W (px)') . '</label><input type="number" name="Map_Width" required value="' . ($_POST['Map_Width'] ?? '') . '" /></field>
                                    <field><label>' . __('Map H (px)') . '</label><input type="number" name="Map_Height" required value="' . ($_POST['Map_Height'] ?? '') . '" /></field>
                                </div>
                                <div style="margin-top: 15px;">
                                    <button type="submit" name="submit" class="architect-btn" style="width: 100%;">
                                        <i class="fas fa-check-circle"></i> ' . __('Save Configuration') . '
                                    </button>
                                    ' . (isset($SelectedParam) ? '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" class="architect-btn" style="width: 100%; margin-top: 10px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; box-shadow:none;">' . __('Cancel') . '</a>' : '') . '
                                </div>
                            </div>
                        </div>

                        <div class="help-box">
                            <h4><i class="fas fa-lightbulb"></i> ' . __('Quick Help') . '</h4>
                            <p>' . __('Get your exact coordinates from ') . '<a href="https://www.openstreetmap.org/" target="_blank" style="color:#166534; font-weight:700;">OpenStreetMap</a>' . __('. Input these to center your distribution maps.') . '</p>
                        </div>
                      </form>
            </aside>
        </div>
    </div>';

include ('includes/footer.php');
