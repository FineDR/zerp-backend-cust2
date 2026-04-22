<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Freight Costs Maintenance');
$ViewTopic = 'Setup';
$BookMark = 'FreightCosts';

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
    .db-card-body { padding: 25px; }
	
    field {
        display: block;
        margin-bottom: 18px;
    }
    field label {
        font-size: 0.62rem; 
        text-transform: uppercase; 
        font-weight: 900; 
        letter-spacing: 0.8px; 
        color: #064e3b; 
        display: block; 
        margin-bottom: 6px;
        opacity: 0.7;
    }
    field input, field select {
        width: 100%; border-radius: 10px; height: 42px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.85rem;
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
		cursor: pointer;
        font-family: inherit;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
    .architect-btn-outline { background: transparent; color: #059669; border: 2px solid #059669; }
    .architect-btn-outline:hover { background: #059669; color: white; }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 1200px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.6rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; color: #334155; }

    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
    }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/CountriesArray.php');

if (isset($_GET['LocationFrom'])){
	$LocationFrom = $_GET['LocationFrom'];
} elseif (isset($_POST['LocationFrom'])){
	$LocationFrom = $_POST['LocationFrom'];
}
if (isset($_GET['ShipperID'])){
	$ShipperID = $_GET['ShipperID'];
} elseif (isset($_POST['ShipperID'])){
	$ShipperID = $_POST['ShipperID'];
}
if (isset($_GET['SelectedFreightCost'])){
	$SelectedFreightCost = $_GET['SelectedFreightCost'];
} elseif (isset($_POST['SelectedFreightCost'])){
	$SelectedFreightCost = $_POST['SelectedFreightCost'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (trim($_POST['DestinationCountry']) == '' ) { $_POST['DestinationCountry'] = $CountriesArray[$_SESSION['CountryOfOperation']]; }
	if (trim($_POST['CubRate']) == '' ) { $_POST['CubRate'] = 0; }
	if (trim($_POST['KGRate']) == '' ) { $_POST['KGRate'] = 0; }
	if (trim($_POST['MAXKGs']) == '' ) { $_POST['MAXKGs'] = 0; }
	if (trim($_POST['MAXCub']) == '' ) { $_POST['MAXCub'] = 0; }
	if (trim($_POST['FixedPrice']) == '' ){ $_POST['FixedPrice'] = 0; }
	if (trim($_POST['MinimumChg']) == '' ) { $_POST['MinimumChg'] = 0; }

	if (!is_double((float) $_POST['CubRate']) OR !is_double((float) $_POST['KGRate']) OR !is_double((float) $_POST['MAXKGs']) OR !is_double((float) $_POST['MAXCub']) OR !is_double((float) $_POST['FixedPrice']) OR !is_double((float) $_POST['MinimumChg'])) {
		$InputError=1;
		prnMsg(__('The entries for Cubic Rate, KG Rate, Maximum Weight, Maximum Volume, Fixed Price and Minimum charge must be numeric'),'warn');
	}

	if (isset($SelectedFreightCost) AND $InputError !=1) {
		$SQL = "UPDATE freightcosts SET locationfrom='".$LocationFrom."', destinationcountry='" . $_POST['DestinationCountry'] . "', destination='" . $_POST['Destination'] . "', shipperid='" . $ShipperID . "', cubrate='" . $_POST['CubRate'] . "', kgrate ='" . $_POST['KGRate'] . "', maxkgs ='" . $_POST['MAXKGs'] . "', maxcub= '" . $_POST['MAXCub'] . "', fixedprice = '" . $_POST['FixedPrice'] . "', minimumchg= '" . $_POST['MinimumChg'] . "' WHERE shipcostfromid='" . $SelectedFreightCost . "'";
		$Msg = __('Freight cost record updated');
	} elseif ($InputError !=1) {
		$SQL = "INSERT INTO freightcosts (locationfrom, destinationcountry, destination, shipperid, cubrate, kgrate, maxkgs, maxcub, fixedprice, minimumchg) VALUES ('".$LocationFrom."', '" . $_POST['DestinationCountry'] . "', '" . $_POST['Destination'] . "', '" . $ShipperID . "', '" . $_POST['CubRate'] . "', '" . $_POST['KGRate'] . "', '" . $_POST['MAXKGs'] . "', '" . $_POST['MAXCub'] . "', '" . $_POST['FixedPrice'] ."', '" . $_POST['MinimumChg'] . "')";
		$Msg = __('Freight cost record inserted');
	}
	if ($InputError != 1) {
		DB_query($SQL);
		prnMsg($Msg,'success');
		unset($SelectedFreightCost); unset($_POST['CubRate']); unset($_POST['KGRate']); unset($_POST['MAXKGs']); unset($_POST['MAXCub']); unset($_POST['FixedPrice']); unset($_POST['MinimumChg']);
	}
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM freightcosts WHERE shipcostfromid='" . $SelectedFreightCost . "'";
	DB_query($SQL);
	prnMsg( __('Freight cost record deleted'),'success');
	unset ($SelectedFreightCost); unset($_GET['delete']);
}

echo '<div class="db-page">
		<div class="premium-header">
			<div class="premium-header-inner">
				<div style="flex: 1;">
					<div class="breadcrumb-wrap">
						<a href="index.php"><i class="fas fa-home"></i></a> 
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i>
                        <a href="index.php?Application=Sales">' . __('Sales') . '</a>
                        <i class="fas fa-chevron-right" style="font-size: 0.4rem;"></i> 
                        ' . __('Freight Costs') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>';
                if (isset($LocationFrom) && isset($ShipperID)) {
                    echo '<div class="header-actions">
                             <button type="submit" form="FreightForm" name="submit" class="architect-btn">
                                <i class="fas fa-save"></i> ' . (isset($SelectedFreightCost) ? __('Update Rate') : __('Save Rate')) . '
                            </button>
                        </div>';
                }
echo '		</div>
		</div>';

if (!isset($LocationFrom) OR !isset($ShipperID)) {
echo '  <div style="max-width: 600px; margin: 40px auto;">
            <form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                <div class="db-card">
                    <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-truck-loading"></i> ' . __('Carrier Selection') . '</h3></div>
                    <div class="db-card-body">
                        <field>
                            <label>' . __('Select Freight Company') . '</label>
                            <select name="ShipperID">';
                            $Res_ships = DB_query("SELECT shippername, shipper_id FROM shippers ORDER BY shippername");
                            while ($MyRow = DB_fetch_array($Res_ships)){
                                echo '<option value="' . $MyRow['shipper_id'] . '">' . $MyRow['shippername'] . '</option>';
                            }
echo '                      </select>
                        </field>
                        <field>
                            <label>' . __('Select Origin Warehouse') . '</label>
                            <select name="LocationFrom">';
                            $Res_locs = DB_query("SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1");
                            while ($MyRow = DB_fetch_array($Res_locs)){
                                echo '<option value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
                            }
echo '                      </select>
                        </field>
                        <button type="submit" name="Accept" class="architect-btn" style="width:100%; margin-top:10px;">' . __('Continue to Management') . ' <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </form>
        </div>';
} else {
    $ShipperName = DB_fetch_row(DB_query("SELECT shippername FROM shippers WHERE shipper_id = '".$ShipperID."'"))[0];
    $LocationName = DB_fetch_row(DB_query("SELECT locationname FROM locations WHERE loccode = '".$LocationFrom."'"))[0];

echo '  <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL_costs = "SELECT shipcostfromid, destinationcountry, destination, cubrate, kgrate, maxkgs, maxcub, fixedprice, minimumchg FROM freightcosts WHERE locationfrom = '".$LocationFrom. "' AND shipperid = '" . $ShipperID . "' ORDER BY destinationcountry, destination";
                $Result_costs = DB_query($SQL_costs);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Rates From') . ' ' . $LocationName . ' (' . $ShipperName . ')</h3>
                        <a href="FreightCosts.php" class="architect-btn architect-btn-outline" style="padding: 6px 12px; font-size:0.7rem;"><i class="fas fa-sync"></i> ' . __('Change Selection') . '</a>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Country') . '</th>
                                    <th>' . __('Zone') . '</th>
                                    <th style="text-align:right;">' . __('Cubic Rate') . '</th>
                                    <th style="text-align:right;">' . __('KG Rate') . '</th>
                                    <th style="text-align:right;">' . __('Max KG') . '</th>
                                    <th style="text-align:right;">' . __('Max Vol') . '</th>
                                    <th style="text-align:right;">' . __('Fixed Price') . '</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result_costs)) {
                                $RowURL = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?LocationFrom=' . $LocationFrom . '&amp;ShipperID=' . $ShipperID;
                                echo '<tr>
                                        <td style="font-weight:700;">' . $MyRow['destinationcountry'] . '</td>
                                        <td><span class="badge badge-secondary">' . $MyRow['destination'] . '</span></td>
                                        <td style="text-align:right;">' . locale_number_format($MyRow['cubrate'],2) . '</td>
                                        <td style="text-align:right;">' . locale_number_format($MyRow['kgrate'],2) . '</td>
                                        <td style="text-align:right;">' . locale_number_format($MyRow['maxkgs'],2) . '</td>
                                        <td style="text-align:right;">' . locale_number_format($MyRow['maxcub'],3) . '</td>
                                        <td style="text-align:right; font-weight:700; color:#059669;">' . locale_number_format($MyRow['fixedprice'],2) . '</td>
                                        <td style="text-align:right; white-space:nowrap;">
                                            <a href="' . $RowURL . '&amp;SelectedFreightCost=' . $MyRow['shipcostfromid'] . '" style="color:#059669; margin-right:8px;"><i class="fas fa-edit"></i></a>
                                            <a href="' . $RowURL . '&amp;SelectedFreightCost=' . $MyRow['shipcostfromid'] . '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedFreightCost)) {
                    $SQL_sel = "SELECT * FROM freightcosts WHERE shipcostfromid='" . $SelectedFreightCost ."'";
                    $MyRow = DB_fetch_array(DB_query($SQL_sel));
                    $_POST['DestinationCountry'] = $MyRow['destinationcountry'];
                    $_POST['Destination'] = $MyRow['destination'];
                    $_POST['CubRate'] = $MyRow['cubrate'];
                    $_POST['KGRate'] = $MyRow['kgrate'];
                    $_POST['MAXKGs'] = $MyRow['maxkgs'];
                    $_POST['MAXCub'] = $MyRow['maxcub'];
                    $_POST['FixedPrice'] = $MyRow['fixedprice'];
                    $_POST['MinimumChg'] = $MyRow['minimumchg'];
                }

echo '          <form id="FreightForm" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
                    <input type="hidden" name="LocationFrom" value="' . $LocationFrom . '" />
                    <input type="hidden" name="ShipperID" value="' . $ShipperID . '" />';
                    if (isset($SelectedFreightCost)) { echo '<input type="hidden" name="SelectedFreightCost" value="' . $SelectedFreightCost . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-coins"></i> ' . (isset($SelectedFreightCost) ? __('Edit Cost Record') : __('New Cost Record')) . '</h3></div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Destination Country') . '</label>
                                <select name="DestinationCountry">';
                                foreach ($CountriesArray as $CName){
                                    echo '<option ' . ((isset($_POST['DestinationCountry']) && strtoupper($_POST['DestinationCountry']) == strtoupper($CName)) ? 'selected' : '') . ' value="' . $CName . '">' . $CName  . '</option>';
                                }
echo '                          </select>
                            </field>
                            <field>
                                <label>' . __('Destination Zone') . '</label>
                                <input type="text" name="Destination" maxlength="20" value="' . ($_POST['Destination'] ?? '') . '" placeholder="e.g. Zone A" />
                            </field>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <field><label>' . __('Rate / m3') . '</label><input type="text" name="CubRate" value="' . ($_POST['CubRate'] ?? '') . '" /></field>
                                <field><label>' . __('Rate / KG') . '</label><input type="text" name="KGRate" value="' . ($_POST['KGRate'] ?? '') . '" /></field>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <field><label>' . __('Max Weight') . '</label><input type="text" name="MAXKGs" value="' . ($_POST['MAXKGs'] ?? '') . '" /></field>
                                <field><label>' . __('Max Vol') . '</label><input type="text" name="MAXCub" value="' . ($_POST['MAXCub'] ?? '') . '" /></field>
                            </div>
                            <field>
                                <label>' . __('Fixed Price') . '</label>
                                <input type="text" name="FixedPrice" style="font-weight:900; color:#059669;" value="' . ($_POST['FixedPrice'] ?? '0') . '" />
                            </field>
                            <field>
                                <label>' . __('Minimum Charge') . '</label>
                                <input type="text" name="MinimumChg" value="' . ($_POST['MinimumChg'] ?? '0') . '" />
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width:100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedFreightCost) ? __('Update Rate') : __('Save Rate')) . '
                            </button>
                            ' . (isset($SelectedFreightCost) ? '<div style="text-align:center; margin-top:10px;"><a href="FreightCosts.php?LocationFrom=' . $LocationFrom . '&ShipperID=' . $ShipperID . '" style="font-size:0.75rem; color:#6b7280; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>';
}

echo '</div>'; // End db-page
include(__DIR__ . '/includes/footer.php');
