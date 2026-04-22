<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Shop Configuration');
$ViewTopic = 'Setup';
$BookMark = 'ShopParameters';

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
		padding: 16px 20px;
        display: flex; justify-content: space-between; align-items: center;
	}
	.db-card-title {
		font-size: 0.75rem;
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
	
    field { display: block; margin-bottom: 20px; }
    field label {
        font-size: 0.62rem; text-transform: uppercase; font-weight: 950; letter-spacing: 0.8px; 
        color: #064e3b; display: block; margin-bottom: 6px; opacity: 0.75;
    }
    field input, field select, field textarea {
        width: 100%; border-radius: 10px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 12px 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input[type="text"], field input[type="email"], field select { height: 46px; }
    field input:focus, field select:focus, field textarea:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.7rem; color: #64748b; margin-top: 6px; display: block; font-weight: 500; }

	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 32px; border-radius: 12px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 800; font-size: 0.95rem; text-decoration: none;
		transition: all 0.3s ease;
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; font-family: inherit;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-1px); box-shadow: 0 6px 15px rgba(5, 150, 105, 0.3); }

    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 30px; 
        max-width: 1400px;
        margin: 0 auto;
    }

    @media (max-width: 1100px) {
        .db-bottom-layout { grid-template-columns: 1fr; }
        .premium-header-inner { flex-direction: column; text-align: center; }
        .architect-btn { width: 100%; }
    }
</style>';

include(__DIR__ . '/includes/header.php');

if (isset($_POST['submit'])) {
	$SQL = array();
	$Fields = [
		'ShopName', 'ShopTitle', 'ShopManagerEmail', 'ShopPrivacyStatement', 
		'ShopFreightPolicy', 'ShopTermsConditions', 'ShopAboutUs', 'ShopContactUs',
		'ShopDebtorNo', 'ShopBranchCode', 'ShopShowOnlyAvailableItems', 'ShopShowQOHColumn',
		'ShopAllowSurcharges', 'ShopAllowCreditCards', 'ShopAllowPayPal', 'ShopAllowBankTransfer',
		'ShopPayPalSurcharge', 'ShopBankTransferSurcharge', 'ShopCreditCardSurcharge',
		'ShopSurchargeStockID', 'ShopCreditCardBankAccount', 'ShopPayPalBankAccount',
		'ShopPayPalCommissionAccount', 'ShopFreightMethod'
	];
	foreach($Fields as $f) {
		if (isset($_POST['X_'.$f]) && $_SESSION[$f] != $_POST['X_'.$f]) {
			$SQL[] = "UPDATE config SET confvalue = '" . DB_escape_string($_POST['X_'.$f]) ."' WHERE confname = '$f'";
		}
	}

	if (isset($_POST['X_ShopStockLocations'])) {
		$ShopStockLocations = implode(',', $_POST['X_ShopStockLocations']);
		if ($_SESSION['ShopStockLocations'] != $ShopStockLocations) {
			$SQL[] = "UPDATE config SET confvalue='" . $ShopStockLocations . "' WHERE confname='ShopStockLocations'";
		}
	}

	if (!$AllowDemoMode) {
		$LiveFields = ['ShopCreditCardGateway', 'ShopPayPalUser', 'ShopPayPalPassword', 'ShopPayPalSignature', 
					   'ShopPayPalProUser', 'ShopPayPalProPassword', 'ShopPayPalProSignature', 
					   'ShopPayFlowUser', 'ShopPayFlowPassword', 'ShopPayFlowVendor', 'ShopPayFlowMerchant', 
					   'ShopMode', 'ShopSwipeHQMerchantID', 'ShopSwipeHQAPIKey'];
		foreach($LiveFields as $f) {
			if (isset($_POST['X_'.$f]) && $_SESSION[$f] != $_POST['X_'.$f]) {
				$SQL[] = "UPDATE config SET confvalue = '" . DB_escape_string($_POST['X_'.$f]) ."' WHERE confname = '$f'";
			}
		}
	}

	if (count($SQL) > 0) {
		DB_Txn_Begin();
		foreach ($SQL as $SqlLine) { DB_query($SqlLine, '', '', true); }
		DB_Txn_Commit();
		prnMsg(__('Configuration updated successfully'), 'success');
		$ForceConfigReload = true; include($PathPrefix . 'includes/GetConfig.php'); $ForceConfigReload = false;
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
                        ' . __('Shop Parameters') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="shop-config-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . __('Apply Settings') . '
                    </button>
                </div>
			</div>
		</div>

        <form id="shop-config-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
            <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
            
            <div class="db-bottom-layout">
                <div class="db-col">
                    <!-- General Settings Card -->
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-id-card"></i> ' . __('Store Identity') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Shop Mode') . '</label>
                                <select name="X_ShopMode">
                                    <option value="test" ' . ($_SESSION['ShopMode'] == 'test' ? 'selected' : '') . '>' . __('Test (No real payments)') . '</option>
                                    <option value="live" ' . ($_SESSION['ShopMode'] == 'live' ? 'selected' : '') . '>' . __('Live (Active transactions)') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Shop Display Name') . '</label>
                                <input type="text" name="X_ShopName" required value="' . $_SESSION['ShopName'] . '" />
                            </field>
                            <field>
                                <label>' . __('Manager Email') . '</label>
                                <input type="email" name="X_ShopManagerEmail" required value="' . $_SESSION['ShopManagerEmail'] . '" />
                            </field>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <field>
                                    <label>' . __('Default Debtor') . '</label>
                                    <input type="text" name="X_ShopDebtorNo" required value="' . $_SESSION['ShopDebtorNo'] . '" />
                                </field>
                                <field>
                                    <label>' . __('Default Branch') . '</label>
                                    <input type="text" name="X_ShopBranchCode" required value="' . $_SESSION['ShopBranchCode'] . '" />
                                </field>
                            </div>
                        </div>
                    </div>

                    <!-- Behavior Card -->
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-gears"></i> ' . __('Store Behavior') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Stock Locations') . '</label>
                                <select name="X_ShopStockLocations[]" size="5" multiple>';
                                    $LocResult = DB_query("SELECT loccode, locationname FROM locations");
                                    $Locs = explode(',', $_SESSION['ShopStockLocations']);
                                    while ($LocRow = DB_fetch_array($LocResult)){
                                        echo '<option value="' . $LocRow['loccode'] . '" ' . (in_array($LocRow['loccode'], $Locs) ? 'selected' : '') . '>' . $LocRow['locationname'] . '</option>';
                                    }
                            echo '</select>
                                <span class="fieldhelp">' . __('Hold Ctrl to select multiple locations.') . '</span>
                            </field>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <field>
                                    <label>' . __('Show Only Available') . '</label>
                                    <select name="X_ShopShowOnlyAvailableItems">
                                        <option value="1" ' . ($_SESSION['ShopShowOnlyAvailableItems'] == '1' ? 'selected' : '') . '>' . __('Yes') . '</option>
                                        <option value="0" ' . ($_SESSION['ShopShowOnlyAvailableItems'] == '0' ? 'selected' : '') . '>' . __('No') . '</option>
                                    </select>
                                </field>
                                <field>
                                    <label>' . __('Display QOH Column') . '</label>
                                    <select name="X_ShopShowQOHColumn">
                                        <option value="1" ' . ($_SESSION['ShopShowQOHColumn'] == '1' ? 'selected' : '') . '>' . __('Visible') . '</option>
                                        <option value="0" ' . ($_SESSION['ShopShowQOHColumn'] == '0' ? 'selected' : '') . '>' . __('Hidden') . '</option>
                                    </select>
                                </field>
                            </div>
                            <field>
                                <label>' . __('Freight Calculation') . '</label>
                                <select name="X_ShopFreightMethod">
                                    <option value="NoFreight" ' . ($_SESSION['ShopFreightMethod'] == 'NoFreight' ? 'selected' : '') . '>' . __('No Freight') . '</option>
                                    <option value="webERPCalculation" ' . ($_SESSION['ShopFreightMethod'] == 'webERPCalculation' ? 'selected' : '') . '>' . __('webERP Internal') . '</option>
                                    <option value="AusPost" ' . ($_SESSION['ShopFreightMethod'] == 'AusPost' ? 'selected' : '') . '>' . __('Australia Post API') . '</option>
                                </select>
                            </field>
                        </div>
                    </div>

                    <!-- Legal Texts -->
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-file-contract"></i> ' . __('Policies & Legal') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field><label>' . __('Terms & Conditions') . '</label><textarea name="X_ShopTermsConditions" rows="4">' . stripslashes($_SESSION['ShopTermsConditions']) . '</textarea></field>
                            <field><label>' . __('Privacy Policy') . '</label><textarea name="X_ShopPrivacyStatement" rows="4">' . stripslashes($_SESSION['ShopPrivacyStatement']) . '</textarea></field>
                            <field><label>' . __('Freight Policy') . '</label><textarea name="X_ShopFreightPolicy" rows="4">' . stripslashes($_SESSION['ShopFreightPolicy']) . '</textarea></field>
                        </div>
                    </div>
                </div>

                <div class="db-col">
                    <!-- Payments Overview -->
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-credit-card"></i> ' . __('Payment Gateway Status') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <field>
                                    <label>' . __('Enable PayPal') . '</label>
                                    <select name="X_ShopAllowPayPal">
                                        <option value="1" ' . ($_SESSION['ShopAllowPayPal'] == '1' ? 'selected' : '') . '>' . __('Active') . '</option>
                                        <option value="0" ' . ($_SESSION['ShopAllowPayPal'] == '0' ? 'selected' : '') . '>' . __('Disabled') . '</option>
                                    </select>
                                </field>
                                <field>
                                    <label>' . __('Enable Bank Transfer') . '</label>
                                    <select name="X_ShopAllowBankTransfer">
                                        <option value="1" ' . ($_SESSION['ShopAllowBankTransfer'] == '1' ? 'selected' : '') . '>' . __('Active') . '</option>
                                        <option value="0" ' . ($_SESSION['ShopAllowBankTransfer'] == '0' ? 'selected' : '') . '>' . __('Disabled') . '</option>
                                    </select>
                                </field>
                            </div>
                            <field>
                                <label>' . __('Credit Card Gateway') . '</label>
                                <select name="X_ShopCreditCardGateway">
                                    <option value="PayPalPro" ' . ($_SESSION['ShopCreditCardGateway'] == 'PayPalPro' ? 'selected' : '') . '>' . __('PayPal Pro') . '</option>
                                    <option value="PayFlow" ' . ($_SESSION['ShopCreditCardGateway'] == 'PayFlow' ? 'selected' : '') . '>' . __('PayFlow Pro') . '</option>
                                    <option value="SwipeHQ" ' . ($_SESSION['ShopCreditCardGateway'] == 'SwipeHQ' ? 'selected' : '') . '>' . __('Swipe HQ') . '</option>
                                </select>
                            </field>
                        </div>
                    </div>

                    <!-- Surcharges -->
                    <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-percent"></i> ' . __('Fee Management') . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Surcharge Handling Item') . '</label>
                                <select name="X_ShopSurchargeStockID">';
                                    $Items = DB_query("SELECT stockid, description FROM stockmaster WHERE mbflag='D'");
                                    while ($iR = DB_fetch_array($Items)){
                                        echo '<option value="' . $iR['stockid'] . '" ' . ($_SESSION['ShopSurchargeStockID'] == $iR['stockid'] ? 'selected' : '') . '>' . $iR['stockid'] . ' - ' . $iR['description'] . '</option>';
                                    }
                            echo '</select>
                            </field>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <field><label>' . __('PayPal %') . '</label><input type="text" name="X_ShopPayPalSurcharge" value="' . $_SESSION['ShopPayPalSurcharge'] . '" /></field>
                                <field><label>' . __('Card %') . '</label><input type="text" name="X_ShopCreditCardSurcharge" value="' . $_SESSION['ShopCreditCardSurcharge'] . '" /></field>
                                <field><label>' . __('Bank Fixed') . '</label><input type="text" name="X_ShopBankTransferSurcharge" value="' . $_SESSION['ShopBankTransferSurcharge'] . '" /></field>
                            </div>
                        </div>
                    </div>';

                    if (!$AllowDemoMode) {
                        echo '<!-- Live Credentials (Hidden in Demo) -->
                        <div class="db-card">
                            <div class="db-card-header">
                                <h3 class="db-card-title"><i class="fas fa-lock"></i> ' . __('Live Credentials') . '</h3>
                            </div>
                            <div class="db-card-body" style="background:#fff7ed;">
                                <p style="font-size:0.7rem; color:#9a3412; font-weight:700; margin-bottom:15px; text-transform:uppercase;"><i class="fas fa-warning"></i> ' . __('Production Keys - Handle with Care') . '</p>
                                <field><label>PayPal API User</label><input type="text" name="X_ShopPayPalUser" value="'.$_SESSION['ShopPayPalUser'].'" /></field>
                                <field><label>PayPal API Signature</label><input type="text" name="X_ShopPayPalSignature" value="'.$_SESSION['ShopPayPalSignature'].'" /></field>
                                <field><label>SwipeHQ API Key</label><input type="password" name="X_ShopSwipeHQAPIKey" value="'.$_SESSION['ShopSwipeHQAPIKey'].'" /></field>
                            </div>
                        </div>';
                    }

echo '          </div>
            </div>
        </form>
    </div>';

include(__DIR__ . '/includes/footer.php');
