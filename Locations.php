<?php

/* Defines the inventory stocking locations or warehouses */

require(__DIR__ . '/includes/session.php');

$Title = __('Location Maintenance');
$ViewTopic = 'Inventory';
$BookMark = 'Locations';

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
        max-width: 1800px;
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
    .db-card-body { padding: 25px; }
	
    field {
        display: block;
        margin-bottom: 18px;
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
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
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
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer;
        white-space: nowrap;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 380px; 
        gap: 30px; 
        align-items: start; 
        max-width: 1800px;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 800px; }
    table.modern-table th { 
        text-align: left; padding: 12px 15px; background: #f8fafc; 
        font-size: 0.65rem; text-transform: uppercase; font-weight: 900; 
        letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7;
    }
    table.modern-table td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; color: #334155; }

    .badge {
        display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;
    }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-secondary { background: #f1f5f9; color: #64748b; }

    @media (max-width: 1200px) {
        .db-bottom-layout { grid-template-columns: 1fr; gap: 20px; }
        .premium-header-inner { flex-direction: column; align-items: stretch; text-align: center; }
        .architect-btn { width: 100%; }
        .db-bottom-layout aside { order: 2; }
        .db-bottom-layout main { order: 1; }
    }
</style>';

include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/CountriesArray.php');

if (isset($_GET['SelectedLocation'])) {
	$SelectedLocation = $_GET['SelectedLocation'];
} elseif (isset($_POST['SelectedLocation'])) {
	$SelectedLocation = $_POST['SelectedLocation'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	$_POST['LocCode']=mb_strtoupper($_POST['LocCode']);
	if (trim($_POST['LocCode']) == '') {
		$InputError = 1;
		prnMsg(__('The location code may not be empty'), 'error');
	}
	if ($_POST['CashSaleCustomer']!='') {
		if ($_POST['CashSaleBranch']=='') {
			prnMsg(__('A cash sale customer and branch are necessary to fully setup the counter sales functionality'),'error');
			$InputError =1;
		} else {
			$SQL = "SELECT * FROM custbranch WHERE debtorno='" . $_POST['CashSaleCustomer'] . "' AND branchcode='" . $_POST['CashSaleBranch'] . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result)==0) {
				$InputError = 1;
				prnMsg(__('The cash sale customer for this location must be defined with both a valid customer code and a valid branch code for this customer'),'error');
			}
		}
	}

	if (isset($SelectedLocation) AND $InputError !=1) {
		$Managed = (isset($_POST['Managed']) and $_POST['Managed'] == 'on') ? 1 : 0;
		$SQL = "UPDATE locations SET loccode='" . $_POST['LocCode'] . "',
									locationname='" . $_POST['LocationName'] . "',
									deladd1='" . $_POST['DelAdd1'] . "',
									deladd2='" . $_POST['DelAdd2'] . "',
									deladd3='" . $_POST['DelAdd3'] . "',
									deladd4='" . $_POST['DelAdd4'] . "',
									deladd5='" . $_POST['DelAdd5'] . "',
									deladd6='" . $_POST['DelAdd6'] . "',
									tel='" . $_POST['Tel'] . "',
									fax='" . $_POST['Fax'] . "',
									email='" . $_POST['Email'] . "',
									contact='" . $_POST['Contact'] . "',
									taxprovinceid = '" . $_POST['TaxProvince'] . "',
									cashsalecustomer ='" . $_POST['CashSaleCustomer'] . "',
									cashsalebranch ='" . $_POST['CashSaleBranch'] . "',
									managed = '" . $Managed . "',
									internalrequest = '" . $_POST['InternalRequest'] . "',
									usedforwo = '" . $_POST['UsedForWO'] . "',
									glaccountcode = '" . $_POST['GLAccountCode'] . "',
									allowinvoicing = '" . $_POST['AllowInvoicing'] . "'
						WHERE loccode = '" . $SelectedLocation . "'";
		$ErrMsg = __('An error occurred updating the') . ' ' . $SelectedLocation . ' ' . __('location record because');
		$Result = DB_query($SQL, $ErrMsg);
		prnMsg(__('The location record has been updated'),'success');
		unset($SelectedLocation);
		foreach($_POST as $key => $val) unset($_POST[$key]);
	} elseif ($InputError !=1) {
		$Managed = (isset($_POST['Managed']) and $_POST['Managed'] == 'on') ? 1 : 0;
		$SQL = "INSERT INTO locations (loccode, locationname, deladd1, deladd2, deladd3, deladd4, deladd5, deladd6, tel, fax, email, contact, taxprovinceid, cashsalecustomer, cashsalebranch, managed, internalrequest, usedforwo, glaccountcode, allowinvoicing)
						VALUES ('" . $_POST['LocCode'] . "', '" . $_POST['LocationName'] . "', '" . $_POST['DelAdd1'] ."', '" . $_POST['DelAdd2'] ."', '" . $_POST['DelAdd3'] . "', '" . $_POST['DelAdd4'] . "', '" . $_POST['DelAdd5'] . "', '" . $_POST['DelAdd6'] . "', '" . $_POST['Tel'] . "', '" . $_POST['Fax'] . "', '" . $_POST['Email'] . "', '" . $_POST['Contact'] . "', '" . $_POST['TaxProvince'] . "', '" . $_POST['CashSaleCustomer'] . "', '" . $_POST['CashSaleBranch'] . "', '" . $Managed . "', '" . $_POST['InternalRequest'] . "', '" . $_POST['UsedForWO'] . "', '" . $_POST['GLAccountCode'] . "', '" . $_POST['AllowInvoicing'] . "')";
		$ErrMsg = __('An error occurred inserting the new location record because');
		$Result = DB_query($SQL, $ErrMsg);
		$SQL = "INSERT INTO locstock (loccode, stockid, quantity, reorderlevel) SELECT '" . $_POST['LocCode'] . "', stockmaster.stockid, 0, 0 FROM stockmaster";
		DB_query($SQL);
		$SQL = "INSERT INTO locationusers (userid, loccode, canview, canupd) SELECT www_users.userid, locations.loccode, 1, 1 FROM www_users CROSS JOIN locations LEFT JOIN locationusers ON www_users.userid = locationusers.userid AND locations.loccode = locationusers.loccode WHERE locationusers.userid IS NULL AND locations.loccode='". $_POST['LocCode'] . "';";
		DB_query($SQL);
		prnMsg(__('The new location record has been added'),'success');
		unset($SelectedLocation);
		foreach($_POST as $key => $val) unset($_POST[$key]);
	}
} elseif (isset($_GET['delete'])) {
	$CancelDelete = 0;
	$SQL= "SELECT COUNT(*) FROM salesorders WHERE fromstkloc='". $SelectedLocation . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0]>0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this location because sales orders refer to it'),'warn');
	} else {
		$SQL= "SELECT COUNT(*) FROM stockmoves WHERE loccode='" . $SelectedLocation . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0]>0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete this location because stock movements refer to it'),'warn');
		} else {
			$SQL= "SELECT COUNT(*) FROM locstock WHERE loccode='". $SelectedLocation . "' AND quantity !=0";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0]>0) {
				$CancelDelete = 1;
				prnMsg(__('Cannot delete this location because there is still stock on hand'),'warn');
			}
		}
	}
	if (! $CancelDelete) {
		DB_query("DELETE FROM locstock WHERE loccode ='" . $SelectedLocation . "'");
		DB_query("DELETE FROM locationusers WHERE loccode='" . $SelectedLocation . "'");
		DB_query("DELETE FROM locations WHERE loccode='" . $SelectedLocation . "'");
		prnMsg(__('Location') . ' ' . $SelectedLocation . ' ' . __('has been deleted'), 'success');
		unset ($SelectedLocation);
	}
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
                        ' . __('Location Setup') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="location-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedLocation) ? __('Update Warehouse') : __('Create Warehouse')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
            
            $SQL = "SELECT locations.*, taxprovinces.taxprovincename FROM locations INNER JOIN taxprovinces ON locations.taxprovinceid=taxprovinces.taxprovinceid";
            $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-map-marked-alt"></i> ' . __('Inventory Hubs') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Code') . '</th>
                                    <th>' . __('Location Hub') . '</th>
                                    <th>' . __('Contact Info') . '</th>
                                    <th style="text-align:center;">' . __('Invoice') . '</th>
                                    <th style="text-align:center;">' . __('Internal') . '</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<tr>
                                        <td style="font-weight:700; color:#059669;">', $MyRow['loccode'], '</td>
                                        <td>
                                            <div style="font-weight:700;">', $MyRow['locationname'], '</div>
                                            <div style="font-size:0.75rem; color:#64748b;">', $MyRow['taxprovincename'], '</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600;">', $MyRow['contact'], '</div>
                                            <div style="font-size:0.75rem; color:#64748b;">', $MyRow['tel'], '</div>
                                        </td>
                                        <td style="text-align:center;">', ($MyRow['allowinvoicing'] == 1 ? '<span class="badge badge-success">' . __('Yes') . '</span>' : '<span class="badge badge-secondary">' . __('No') . '</span>'), '</td>
                                        <td style="text-align:center;">', ($MyRow['internalrequest'] == 1 ? '<span class="badge badge-success">' . __('Yes') . '</span>' : '<span class="badge badge-secondary">' . __('No') . '</span>'), '</td>
                                        <td style="text-align:right; white-space:nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?SelectedLocation=', $MyRow['loccode'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '?SelectedLocation=', $MyRow['loccode'], '&amp;delete=1" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm deletion of this location?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedLocation)) {
                    $SQL = "SELECT * FROM locations WHERE loccode='" . $SelectedLocation . "'";
                    $Result = DB_query($SQL);
                    $MyRow = DB_fetch_array($Result);
                    $_POST['LocCode'] = $MyRow['loccode'];
                    $_POST['LocationName'] = $MyRow['locationname'];
                    $_POST['DelAdd1'] = $MyRow['deladd1'];
                    $_POST['DelAdd2'] = $MyRow['deladd2'];
                    $_POST['DelAdd3'] = $MyRow['deladd3'];
                    $_POST['DelAdd4'] = $MyRow['deladd4'];
                    $_POST['DelAdd5'] = $MyRow['deladd5'];
                    $_POST['DelAdd6'] = $MyRow['deladd6'];
                    $_POST['Contact'] = $MyRow['contact'];
                    $_POST['Tel'] = $MyRow['tel'];
                    $_POST['Fax'] = $MyRow['fax'];
                    $_POST['Email'] = $MyRow['email'];
                    $_POST['TaxProvince'] = $MyRow['taxprovinceid'];
                    $_POST['CashSaleCustomer'] = $MyRow['cashsalecustomer'];
                    $_POST['CashSaleBranch'] = $MyRow['cashsalebranch'];
                    $_POST['Managed'] = ($MyRow['managed'] == 1 ? 'on' : 'off');
                    $_POST['InternalRequest'] = $MyRow['internalrequest'];
                    $_POST['UsedForWO'] = $MyRow['usedforwo'];
                    $_POST['GLAccountCode'] = $MyRow['glaccountcode'];
                    $_POST['AllowInvoicing'] = $MyRow['allowinvoicing'];
                }

echo '          <form id="location-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedLocation)) {
                        echo '<input type="hidden" name="SelectedLocation" value="' . $SelectedLocation . '" />';
                        echo '<input type="hidden" name="LocCode" value="' . $_POST['LocCode'] . '" />';
                    }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-cog"></i> ' . (isset($SelectedLocation) ? __('Edit Hub') : __('Add New Hub')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Location Code') . '</label>
                                <input type="text" name="LocCode" required maxlength="5" value="' . ($_POST['LocCode'] ?? '') . '" ' . (isset($SelectedLocation) ? 'disabled' : '') . ' placeholder="e.g. WH01" />
                            </field>
                            <field>
                                <label>' . __('Hub Name') . '</label>
                                <input type="text" name="LocationName" required maxlength="50" value="' . ($_POST['LocationName'] ?? '') . '" />
                            </field>
                            <field>
                                <label>' . __('Contact Person') . '</label>
                                <input type="text" name="Contact" required maxlength="30" value="' . ($_POST['Contact'] ?? '') . '" />
                            </field>
                            
                            <h4 style="font-size:0.65rem; font-weight:850; color:#64748b; margin:25px 0 12px 0; text-transform:uppercase;">' . __('Communication') . '</h4>
                            <field><label>' . __('Telephone') . '</label><input type="text" name="Tel" value="' . ($_POST['Tel'] ?? '') . '" /></field>
                            <field><label>' . __('Email Address') . '</label><input type="email" name="Email" value="' . ($_POST['Email'] ?? '') . '" /></field>

                            <h4 style="font-size:0.65rem; font-weight:850; color:#64748b; margin:25px 0 12px 0; text-transform:uppercase;">' . __('Address Details') . '</h4>
                            <field><input type="text" name="DelAdd1" value="' . ($_POST['DelAdd1'] ?? '') . '" placeholder="' . __('Line 1') . '" /></field>
                            <field><input type="text" name="DelAdd2" value="' . ($_POST['DelAdd2'] ?? '') . '" placeholder="' . __('Line 2') . '" /></field>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <field><input type="text" name="DelAdd3" value="' . ($_POST['DelAdd3'] ?? '') . '" placeholder="' . __('City') . '" /></field>
                                <field><input type="text" name="DelAdd4" value="' . ($_POST['DelAdd4'] ?? '') . '" placeholder="' . __('Suburb') . '" /></field>
                            </div>
                            <field>
                                <select name="DelAdd6">';
                                foreach ($CountriesArray as $CName) {
                                    echo '<option ' . (($_POST['DelAdd6'] ?? '') == $CName ? 'selected' : '') . ' value="' . $CName . '">' . $CName . '</option>';
                                }
echo '                          </select>
                            </field>

                            <h4 style="font-size:0.65rem; font-weight:850; color:#64748b; margin:25px 0 12px 0; text-transform:uppercase;">' . __('System Integration') . '</h4>
                            <field>
                                <label>' . __('Tax Province') . '</label>
                                <select name="TaxProvince">';
                                $TaxResult = DB_query("SELECT taxprovinceid, taxprovincename FROM taxprovinces");
                                while ($Trow = DB_fetch_array($TaxResult)) {
                                    echo '<option ' . (($_POST['TaxProvince'] ?? '') == $Trow['taxprovinceid'] ? 'selected' : '') . ' value="' . $Trow['taxprovinceid'] . '">' . $Trow['taxprovincename'] . '</option>';
                                }
echo '                          </select>
                            </field>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <field>
                                    <label>' . __('Internal Req') . '</label>
                                    <select name="InternalRequest">
                                        <option ' . (($_POST['InternalRequest'] ?? '') == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
                                        <option ' . (($_POST['InternalRequest'] ?? '') == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                    </select>
                                </field>
                                <field>
                                    <label>' . __('Invoicing') . '</label>
                                    <select name="AllowInvoicing">
                                        <option ' . (($_POST['AllowInvoicing'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
                                        <option ' . (($_POST['AllowInvoicing'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                    </select>
                                </field>
                            </div>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedLocation) ? __('Update Hub') : __('Save New Hub')) . '
                            </button>
                            ' . (isset($SelectedLocation) ? '<div style="text-align:center; margin-top:15px;"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
