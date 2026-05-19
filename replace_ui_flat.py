with open('SupplierInvoice.php', 'r') as f:
    content = f.read()

# 1. Update CSS
css_start = content.find("echo '<style>\n    /* Vertical Accordion Styles */")
css_end = content.find("</script>';", css_start) + len("</script>';")

new_css = """echo '<style>
    /* Dashboard Utility Styles */
    .charge-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 12px;
    }
    .charge-info { display: flex; flex-direction: column; }
    .charge-title { font-weight: 700; color: #1e293b; }
    .charge-sub { font-size: 0.8rem; color: #64748b; }
    .charge-amt { font-weight: 800; color: #059669; font-size: 1.1rem; }
    
    .db-aside-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        background: #fff;
        color: #374151;
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 8px;
    }
    .db-aside-btn:hover {
        border-color: #059669;
        background: #f0fdf4;
        color: #059669;
        transform: translateX(4px);
    }
    .db-aside-btn i { color: #059669; width: 20px; }
</style>';"""

if css_start != -1 and css_end != -1:
    content = content[:css_start] + new_css + content[css_end:]

# 2. Rebuild the Body
form_body_start = content.find("echo '<div class=\"db-bottom-layout\">';")
form_body_end = content.find("} } else { // $_POST['PostInvoice'] is set so do the postings")

new_body = """echo '<div class="db-bottom-layout">';

    // --- SIDEBAR START ---
    echo '<aside class="db-col-aside">';

    // Card 1: Active Supplier
    echo '<div class="db-card" style="margin-bottom: var(--space-4);">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-user-tag db-icon-green"></i> ' . __('Supplier Details') . '</h3>
            </div>
            <div class="db-card-body" style="padding: var(--space-4);">
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--db-primary);">' . $_SESSION['SuppTrans']->SupplierName . '</div>
                <div style="font-family: monospace; color: var(--text-muted); margin-bottom: var(--space-3);">[' . $_SESSION['SuppTrans']->SupplierID . ']</div>
                <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
                    <div><span class="db-muted">' . __('Currency') . ':</span> <span class="val-bold">' . $_SESSION['SuppTrans']->CurrCode . '</span></div>
                    <div><span class="db-muted">' . __('Terms') . ':</span> ' . $_SESSION['SuppTrans']->TermsDescription . '</div>
                </div>
            </div>
        </div>';

    // Pre-calculate Summary
    $TaxTotal = 0;
    foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
        if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
            $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
        }
        if (!isset($_POST['OverRideTax']) OR $_POST['OverRideTax'] == 'Auto') {
            if ($Tax->TaxOnTax == 1) {
                $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * ($_SESSION['SuppTrans']->OvAmount + $TaxTotal);
            } else {
                $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxRate * $_SESSION['SuppTrans']->OvAmount;
            }
        } else {
            $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);
        }
        $TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax->TaxCalculationOrder]->TaxOvAmount;
    }

    // Card 2: Live Summary
    echo '<div class="db-card" style="position: sticky; top: var(--space-4);">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-calculator"></i> ' . __('Invoice Summary') . '</h3>
            </div>
            <div class="db-card-body" style="padding: var(--space-4);">
                <div style="display: flex; flex-direction: column; gap: var(--space-3);">
                    <div style="display: flex; justify-content: space-between;">
                        <span class="db-muted">' . __('Manual Amount') . ':</span>
                        <span class="val-bold">' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    </div>';
    
    foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
        echo '<div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                <span class="db-muted">' . $Tax->TaxAuthDescription . ':</span>
                <span>' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
              </div>';
    }
    
    echo '          <div style="margin: var(--space-2) 0; height: 1px; background: var(--border-soft);"></div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; color: #059669;">
                        <span class="val-bold">' . __('Grand Total') . ':</span>
                        <span class="val-bold">' . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' ' . $_SESSION['SuppTrans']->CurrCode . '</span>
                    </div>
                </div>
            </div>
        </div>';

    echo '</aside>';
    // --- SIDEBAR END ---

    // --- MAIN CONTENT START ---
    echo '<main class="db-col-main">';

    // -------------------------------------------------------------
    // SECTION 1: HEADER
    // -------------------------------------------------------------
    echo '<div class="db-card" style="margin-bottom: var(--space-6);">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-info-circle"></i> ' . __('Invoice Header Details') . '</h3>
            </div>
            <div class="db-card-body" style="padding: var(--space-4);">
                <div class="db-grid db-grid-2">';
    echo '<div class="db-form-group">
            <label class="db-label">' . __('Supplier Invoice Reference') . '</label>
            <input type="text" required="required" placeholder="' . __('Enter Invoice Number') . '" name="SuppReference" value="' . $_SESSION['SuppTrans']->SuppReference . '" />
        </div>';
    if (!isset($_SESSION['SuppTrans']->TranDate)) {
        $_SESSION['SuppTrans']->TranDate = date($_SESSION['DefaultDateFormat']);
    }
    echo '<div class="db-form-group">
            <label class="db-label">' . __('Invoice Date') . '</label>
            <input type="date" name="TranDate" value="' . FormatDateForSQL($_SESSION['SuppTrans']->TranDate) . '" />
        </div>';
    echo '<div class="db-form-group">
            <label class="db-label">' . __('Exchange Rate') . '</label>
            <input class="number" name="ExRate" type="text" value="' . locale_number_format($_SESSION['SuppTrans']->ExRate, 'Variable') . '" />
        </div>';
    echo '<div class="db-form-group">
            <label class="db-label">' . __('Comments / Narrative') . '</label>
            <textarea name="Comments" rows="3">' . $_SESSION['SuppTrans']->Comments . '</textarea>
        </div>';
    echo '      </div>
            </div>
        </div>';

    // -------------------------------------------------------------
    // SECTION 2: CHARGES / GL ALLOCATION
    // -------------------------------------------------------------
    echo '<div class="db-card" style="margin-bottom: var(--space-6);">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-list"></i> ' . __('Analysis & Allocation') . '</h3>
            </div>
            <div class="db-card-body" style="padding: var(--space-4);">';

    echo '      <div class="db-grid db-grid-4" style="margin-bottom:30px;">
                    <button type="submit" name="GRNS" value="' . __('Purchase Orders') . '" class="db-aside-btn"><i class="fas fa-shopping-cart"></i> ' . __('Add PO Items') . '</button>
                    <button type="submit" name="Shipts" value="' . __('Shipments') . '" class="db-aside-btn"><i class="fas fa-truck"></i> ' . __('Add Shipments') . '</button>
                    <button type="submit" name="Contracts" value="' . __('Contracts') . '" class="db-aside-btn"><i class="fas fa-file-contract"></i> ' . __('Add Contracts') . '</button>
                    <button type="submit" name="FixedAssets" value="' . __('Fixed Assets') . '" class="db-aside-btn"><i class="fas fa-briefcase"></i> ' . __('Add Fixed Assets') . '</button>
                </div>';

    // GL ENTRY INLINE
    echo '      <div style="background:#f8fafc; padding:24px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:30px;">
                    <h4 style="margin-bottom:16px; font-weight:800; color:#064e3b; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em;">' . __('Add General Ledger Line') . '</h4>
                    <div class="db-grid db-grid-3" style="align-items:end;">';
    
    $SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
    $Result = DB_query($SQL);
    echo '<div class="db-form-group" style="margin-bottom:0;">
            <label class="db-label">' . __('Select GL Account No') . '</label>
            <select name="AcctSelection" class="db-form-select">
                <option value=""></option>';
    while ($MyRow = DB_fetch_array($Result)) {
        echo '<option value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . $MyRow['accountname'] . '</option>';
    }
    echo '</select></div>';
    
    echo '<div class="db-form-group" style="margin-bottom:0;">
            <label class="db-label">' . __('Amount') . '</label>
            <input type="text" class="number" name="GLAmount" placeholder="0.00" />
        </div>';
    echo '<div class="db-form-group" style="margin-bottom:0;">
            <button type="submit" name="AddGLCodeToTrans" value="' . __('Enter GL Line') . '" class="architect-btn" style="width:100%;"><i class="fas fa-plus"></i> ' . __('Enter GL Line') . '</button>
        </div>';
    echo '          </div>
                </div>';
                
    // SHOW EXISTING CHARGES
    echo '      <h4 style="font-weight:800; color:#1e293b; margin-bottom:16px;">' . __('Currently Added Allocations') . '</h4>';
    $hasCharges = false;
    foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
        $hasCharges = true;
        echo '<div class="charge-item">
                <div class="charge-info">
                    <span class="charge-title">' . $GRN->ItemDescription . '</span>
                    <span class="charge-sub">GRN: #' . $GRN->GRNNo . ' | Order: #' . $GRN->PONo . '</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span class="charge-amt">' . locale_number_format($GRN->This_QuantityInv * $GRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    <a href="' . $RootPath . '/SuppInvGRNs.php?Delete=' . $GRN->GRNNo . '" class="db-badge db-badge-danger"><i class="fas fa-trash"></i></a>
                </div>
              </div>';
    }
    foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
        $hasCharges = true;
        echo '<div class="charge-item" style="border-left: 4px solid #3b82f6;">
                <div class="charge-info">
                    <span class="charge-title">' . $GLLine->GLActName . '</span>
                    <span class="charge-sub">' . $GLLine->GLCode . ' | ' . $GLLine->Narrative . '</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span class="charge-amt">' . locale_number_format($GLLine->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    <a href="?DeleteGLCode=' . $GLLine->Counter . '" class="db-badge db-badge-danger"><i class="fas fa-trash"></i></a>
                </div>
              </div>';
    }
    foreach ($_SESSION['SuppTrans']->Shipts as $Shipt) {
        $hasCharges = true;
        echo '<div class="charge-item" style="border-left: 4px solid #f59e0b;">
                <div class="charge-info">
                    <span class="charge-title">' . __('Shipment') . ': ' . $Shipt->ShiptRef . '</span>
                    <span class="charge-sub">' . __('Shipment Charge') . '</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span class="charge-amt">' . locale_number_format($Shipt->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    <a href="?DeleteShipt=' . $Shipt->Counter . '" class="db-badge db-badge-danger"><i class="fas fa-trash"></i></a>
                </div>
              </div>';
    }
    foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
        $hasCharges = true;
        echo '<div class="charge-item" style="border-left: 4px solid #8b5cf6;">
                <div class="charge-info">
                    <span class="charge-title">' . __('Contract') . ': ' . $Contract->ContractRef . '</span>
                    <span class="charge-sub">' . $Contract->Narrative . '</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span class="charge-amt">' . locale_number_format($Contract->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    <a href="?DeleteContract=' . $Contract->Counter . '" class="db-badge db-badge-danger"><i class="fas fa-trash"></i></a>
                </div>
              </div>';
    }
    foreach ($_SESSION['SuppTrans']->Assets as $Asset) {
        $hasCharges = true;
        echo '<div class="charge-item" style="border-left: 4px solid #10b981;">
                <div class="charge-info">
                    <span class="charge-title">' . $Asset->Description . '</span>
                    <span class="charge-sub">' . __('Asset ID') . ': ' . $Asset->AssetID . '</span>
                </div>
                <div style="display:flex; align-items:center; gap:20px;">
                    <span class="charge-amt">' . locale_number_format($Asset->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                    <a href="?DeleteAsset=' . $Asset->Counter . '" class="db-badge db-badge-danger"><i class="fas fa-trash"></i></a>
                </div>
              </div>';
    }

    if (!$hasCharges) {
        echo '<div style="padding:40px; text-align:center; background:#f8fafc; border-radius:12px; border: 2px dashed #e5e7eb;">
                <i class="fas fa-info-circle" style="font-size:2rem; color:#94a3b8; margin-bottom:12px;"></i>
                <p style="color:#64748b; font-weight:600;">' . __('No charges added to this invoice yet.') . '</p>
              </div>';
    }

    echo '      </div>
            </div>';

    // -------------------------------------------------------------
    // SECTION 3: REVIEW & FINISH
    // -------------------------------------------------------------
    echo '<div class="db-card" style="margin-bottom: var(--space-6);">
            <div class="db-card-header">
                <h3 class="db-card-title"><i class="fas fa-check-double"></i> ' . __('Review & Post') . '</h3>
            </div>
            <div class="db-card-body" style="padding: 0;">
                <div style="background:#f8fafc; padding:24px; margin:24px; border-radius:16px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-bottom:16px; font-weight:800; color:#064e3b; text-transform:uppercase; font-size:0.8rem; letter-spacing:0.05em;">' . __('Summary Details') . '</h4>
                    <div class="db-grid db-grid-3">
                        <div><span class="db-muted">' . __('Reference') . ':</span> <div style="font-weight:800; font-size:1.1rem;">' . $_SESSION['SuppTrans']->SuppReference . '</div></div>
                        <div><span class="db-muted">' . __('Date') . ':</span> <div style="font-weight:700;">' . $_SESSION['SuppTrans']->TranDate . '</div></div>
                        <div><span class="db-muted">' . __('Total Value') . ':</span> <div style="font-weight:900; font-size:1.2rem; color:#059669;">' . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' ' . $_SESSION['SuppTrans']->CurrCode . '</div></div>
                    </div>
                </div>';
    
    echo '<table class="registry-table" style="margin: 0 24px 24px; width: calc(100% - 48px);">
            <thead><tr><th>' . __('Type') . '</th><th>' . __('Description') . '</th><th class="text-right">' . __('Amount') . '</th></tr></thead>
            <tbody>';
    foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
        echo '<tr><td>GRN</td><td>' . $GRN->ItemDescription . '</td><td class="text-right">' . locale_number_format($GRN->This_QuantityInv * $GRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td></tr>';
    }
    foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
        echo '<tr><td>GL</td><td>' . $GLLine->GLActName . ' (' . $GLLine->Narrative . ')</td><td class="text-right">' . locale_number_format($GLLine->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td></tr>';
    }
    foreach ($_SESSION['SuppTrans']->Shipts as $Shipt) {
        echo '<tr><td>' . __('Shipment') . '</td><td>' . $Shipt->ShiptRef . '</td><td class="text-right">' . locale_number_format($Shipt->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td></tr>';
    }
    foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
        echo '<tr><td>' . __('Contract') . '</td><td>' . $Contract->ContractRef . ' (' . $Contract->Narrative . ')</td><td class="text-right">' . locale_number_format($Contract->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td></tr>';
    }
    foreach ($_SESSION['SuppTrans']->Assets as $Asset) {
        echo '<tr><td>' . __('Asset') . '</td><td>' . $Asset->Description . '</td><td class="text-right">' . locale_number_format($Asset->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td></tr>';
    }
    echo '  </tbody></table>
            </div>
            <div class="db-card-footer" style="padding:30px; text-align:center; background:#f0fdf4; border-top:1px solid #dcfce3;">
                <button type="submit" name="PostInvoice" class="architect-btn" style="padding:20px 60px; font-size:1.3rem; border-radius:16px; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.3);">
                    <i class="fas fa-cloud-upload-alt"></i> ' . __('Post Supplier Invoice Now') . '
                </button>
            </div>
        </div>';

    echo '</main></div><!-- .db-bottom-layout -->';
    echo '</form></div><!-- .db-page -->';
"""

if form_body_start != -1 and form_body_end != -1:
    content = content[:form_body_start] + new_body + content[form_body_end:]

with open('SupplierInvoice.php', 'w') as f:
    f.write(content)
print("Flat UI replacement complete")
