<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Payment Methods');
$ViewTopic = 'ARTransactions';
$BookMark = 'PaymentMethods';

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
        width: 100%; border-radius: 10px; height: 44px; font-weight: 600; border: 1px solid #d1fae5;
        padding: 0 14px; box-sizing: border-box; background: #ffffff; font-family: inherit; font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    field input:focus, field select:focus { 
        border-color: #059669; outline: none; box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1); 
    }
    .fieldhelp { font-size: 0.75rem; color: #64748b; margin-top: 6px; display: block; font-weight: 500; }

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
	
    .db-bottom-layout { 
        display: grid; 
        grid-template-columns: 1fr 340px; 
        gap: 30px; 
        align-items: start; 
        max-width: 100%;
        margin: 0 auto;
    }

    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.modern-table { width: 100%; border-collapse: collapse; min-width: 900px; }
    table.modern-table th, table.modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 0.85rem; color: #334155; vertical-align: middle; }
    table.modern-table th { text-align: left; background: #f8fafc; font-size: 0.65rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px; color: #64748b; border-bottom: 2px solid #edf2f7; }
    
    .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
    .badge-emerald { background: #d1fae5; color: #065f46; }
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

if (isset($_GET['SelectedPaymentID'])) {
	$SelectedPaymentID = $_GET['SelectedPaymentID'];
} elseif (isset($_POST['SelectedPaymentID'])) {
	$SelectedPaymentID = $_POST['SelectedPaymentID'];
}

if (isset($_POST['submit'])) {
	$InputError = 0;
	if (trim($_POST['MethodName']) == "") {
		$InputError = 1;
		prnMsg(__('The payment method may not be empty.'), 'error');
	}
    $Discount = filter_number_format($_POST['DiscountPercent']);
	if (!is_numeric($Discount) || $Discount > 100 || $Discount < 0) {
		$InputError = 1;
		prnMsg(__('The discount percentage must be a number between 0 and 100'),'error');
	}

	if (isset($SelectedPaymentID) AND $InputError != 1) {
		$SQL = "UPDATE paymentmethods SET paymentname='" . $_POST['MethodName'] . "', paymenttype = '" . $_POST['ForPayment'] . "', receipttype = '" . $_POST['ForReceipt'] . "', usepreprintedstationery = '" . $_POST['UsePrePrintedStationery']. "', opencashdrawer = '" . $_POST['OpenCashDrawer'] . "', percentdiscount = '" . ($Discount/100) . "' WHERE paymentid = '" . $SelectedPaymentID . "'";
		$Msg = __('Payment method updated');
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO paymentmethods (paymentname, paymenttype, receipttype, usepreprintedstationery, opencashdrawer, percentdiscount) VALUES ('" . $_POST['MethodName'] ."', '" . $_POST['ForPayment'] ."', '" . $_POST['ForReceipt'] ."', '" . $_POST['UsePrePrintedStationery'] ."', '" . $_POST['OpenCashDrawer']  . "', '" . ($Discount/100) . "')";
		$Msg = __('New payment method added');
	}

	if ($InputError!= 1){
		DB_query($SQL);
		prnMsg($Msg,'success');
	}
	unset ($SelectedPaymentID);
} elseif (isset($_GET['delete'])) {
	$SQL = "SELECT paymentname FROM paymentmethods WHERE paymentid = '" . $SelectedPaymentID . "'";
	$Result = DB_query($SQL);
	if (DB_num_rows($Result) > 0) {
		$OldName = DB_fetch_row($Result)[0];
		$CheckRes = DB_query("SELECT COUNT(*) FROM banktrans WHERE banktranstype LIKE '" . $OldName . "'");
		if (DB_fetch_row($CheckRes)[0] > 0) {
			prnMsg(__('Cannot delete this payment method because bank transactions refer to it'),'warn');
		} else {
			DB_query("DELETE FROM paymentmethods WHERE paymentid='" . $SelectedPaymentID . "'");
			prnMsg($OldName . ' ' . __('deleted'),'success');
		}
	}
	unset ($SelectedPaymentID);
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
                        ' . __('Payment Methods') . '
					</div>
					<h1 style="font-size: 1.6rem; font-weight: 950; letter-spacing: -0.5px; color: #064e3b; margin: 0; line-height: 1.1;">' . $Title . '</h1>
				</div>
                <div class="header-actions">
                     <button type="submit" form="payment-form" name="submit" class="architect-btn">
                        <i class="fas fa-save"></i> ' . (isset($SelectedPaymentID) ? __('Update Method') : __('Create Method')) . '
                    </button>
                </div>
			</div>
		</div>

        <div class="db-bottom-layout">
            <main class="db-main" style="min-width: 0;">';
                
                $SQL = "SELECT * FROM paymentmethods ORDER BY paymentname";
                $Result = DB_query($SQL);

echo '          <div class="db-card">
                    <div class="db-card-header">
                        <h3 class="db-card-title"><i class="fas fa-money-check-alt"></i> ' . __('Available Payment Channels') . '</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>' . __('Method Name') . '</th>
                                    <th>' . __('Usage') . '</th>
                                    <th>' . __('Stationery') . '</th>
                                    <th>' . __('Discount') . '</th>
                                    <th style="width: 100px; text-align: right;"></th>
                                </tr>
                            </thead>
                            <tbody>';
                            while ($MyRow = DB_fetch_array($Result)) {
                                echo '<tr>
                                        <td>
                                            <div style="font-weight:700; color:#064e3b;">', $MyRow['paymentname'], '</div>
                                            <div style="font-size:0.7rem; color:#64748b;">ID: #', $MyRow['paymentid'], '</div>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                ', ($MyRow['paymenttype'] ? '<span class="badge badge-emerald" title="Outgoing">P</span>' : ''), '
                                                ', ($MyRow['receipttype'] ? '<span class="badge badge-emerald" title="Incoming">R</span>' : ''), '
                                                ', ($MyRow['opencashdrawer'] ? '<span class="badge badge-secondary" title="Cash Drawer Interface"><i class="fas fa-cash-register"></i></span>' : ''), '
                                            </div>
                                        </td>
                                        <td>', ($MyRow['usepreprintedstationery'] ? '<span class="badge badge-emerald">' . __('Pre-Printed') . '</span>' : '<span class="badge badge-secondary">' . __('Standard') . '</span>'), '</td>
                                        <td style="font-weight:700; color:#059669;">', locale_number_format($MyRow['percentdiscount']*100, 2), '%</td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedPaymentID=', $MyRow['paymentid'], '" style="color:#059669; margin-right:12px;"><i class="fas fa-edit"></i></a>
                                            <a href="', htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'), '?SelectedPaymentID=', $MyRow['paymentid'], '&amp;delete=yes" style="color:#dc2626;" onclick="return confirm(\'' . __('Confirm delete?') . '\');"><i class="fas fa-trash-alt"></i></a>
                                        </td>
                                    </tr>';
                            }
echo '                      </tbody>
                        </table>
                    </div>
                </div>
            </main>

            <aside class="db-sidebar" style="min-width: 0;">';
                if (isset($SelectedPaymentID)) {
                    $Res = DB_query("SELECT * FROM paymentmethods WHERE paymentid='" . $SelectedPaymentID . "'");
                    $MyRow = DB_fetch_array($Res);
                    $_POST['MethodName'] = $MyRow['paymentname'];
                    $_POST['ForPayment'] = $MyRow['paymenttype'];
                    $_POST['ForReceipt'] = $MyRow['receipttype'];
                    $_POST['UsePrePrintedStationery'] = $MyRow['usepreprintedstationery'];
                    $_POST['OpenCashDrawer'] = $MyRow['opencashdrawer'];
                    $_POST['DiscountPercent'] = $MyRow['percentdiscount'] * 100;
                }

echo '          <form id="payment-form" method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
                    <input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
                    if (isset($SelectedPaymentID)) { echo '<input type="hidden" name="SelectedPaymentID" value="' . $SelectedPaymentID . '" />'; }

echo '              <div class="db-card">
                        <div class="db-card-header">
                            <h3 class="db-card-title"><i class="fas fa-credit-card"></i> ' . (isset($SelectedPaymentID) ? __('Edit Method') : __('New Method')) . '</h3>
                        </div>
                        <div class="db-card-body">
                            <field>
                                <label>' . __('Method Name') . '</label>
                                <input type="text" name="MethodName" required maxlength="30" autofocus value="' . ($_POST['MethodName'] ?? '') . '" placeholder="e.g. Credit Card" />
                            </field>
                            <field>
                                <label>' . __('Use For Payments (Outgoing)') . '</label>
                                <select name="ForPayment">
                                    <option ' . (($_POST['ForPayment'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
                                    <option ' . (($_POST['ForPayment'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Use For Receipts (Incoming)') . '</label>
                                <select name="ForReceipt">
                                    <option ' . (($_POST['ForReceipt'] ?? 1) == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
                                    <option ' . (($_POST['ForReceipt'] ?? 1) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Pre-printed Stationery') . '</label>
                                <select name="UsePrePrintedStationery">
                                    <option ' . (($_POST['UsePrePrintedStationery'] ?? 0) == 1 ? 'selected' : '') . ' value="1">' . __('Yes') . '</option>
                                    <option ' . (($_POST['UsePrePrintedStationery'] ?? 0) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Open Cash Drawer') . '</label>
                                <select name="OpenCashDrawer">
                                    <option ' . (($_POST['OpenCashDrawer'] ?? 0) == 1 ? 'selected' : '') . ' value="1">' . __('Yes (POS Mode)') . '</option>
                                    <option ' . (($_POST['OpenCashDrawer'] ?? 0) == 0 ? 'selected' : '') . ' value="0">' . __('No') . '</option>
                                </select>
                            </field>
                            <field>
                                <label>' . __('Receipt Discount %') . '</label>
                                <input type="text" name="DiscountPercent" value="' . locale_number_format(($_POST['DiscountPercent'] ?? 0), 2) . '" />
                                <span class="fieldhelp">' . __('Automatically applied discount for this receipt type') . '</span>
                            </field>

                            <button type="submit" name="submit" class="architect-btn" style="width: 100%; margin-top:10px;">
                                <i class="fas fa-check-circle"></i> ' . (isset($SelectedPaymentID) ? __('Update Definition') : __('Save Definition')) . '
                            </button>
                            ' . (isset($SelectedPaymentID) ? '<div style="text-align:center; margin-top:15px;"><a href="PaymentMethods.php" style="font-size:0.8rem; color:#64748b; font-weight:700; text-decoration:none;">' . __('Cancel Edit') . '</a></div>' : '') . '
                        </div>
                    </div>
                </form>
            </aside>
        </div>
    </div>';

include(__DIR__ . '/includes/footer.php');
