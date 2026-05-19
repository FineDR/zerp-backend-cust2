with open('SupplierInvoice.php', 'r') as f:
    content = f.read()

# 1. Update CSS
css_start = content.find("echo '<style>\n    /* Dashboard Utility Styles */")
css_end = content.find("</style>';", css_start) + len("</style>';")

new_css = """echo '<style>
    /* Spreadsheet Utility Styles */
    .spreadsheet-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border: 1px solid #cbd5e1;
    }
    .spreadsheet-table th {
        background: #f1f5f9;
        color: #334155;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 12px;
        border-bottom: 2px solid #cbd5e1;
        text-align: left;
    }
    .spreadsheet-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
        font-size: 0.9rem;
        color: #1e293b;
    }
    .spreadsheet-table tr:hover td {
        background: #f8fafc;
    }
    .compact-input {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    .compact-input:focus {
        border-color: #059669;
        outline: none;
        box-shadow: 0 0 0 2px rgba(5, 150, 105, 0.1);
    }
    .action-link {
        color: #059669;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        margin-right: 16px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .action-link:hover {
        color: #047857;
        text-decoration: underline;
    }
    .delete-icon {
        color: #ef4444;
        cursor: pointer;
    }
    .delete-icon:hover {
        color: #dc2626;
    }
    .invoice-header-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 20px;
        background: #fff;
        padding: 20px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .header-field label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
        text-transform: uppercase;
    }
    .summary-box {
        width: 350px;
        float: right;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        margin-top: 20px;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
    }
    .summary-row.total {
        background: #f0fdf4;
        font-weight: 800;
        font-size: 1.2rem;
        color: #064e3b;
        border-bottom: none;
        border-radius: 0 0 8px 8px;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
</style>';"""

if css_start != -1 and css_end != -1:
    content = content[:css_start] + new_css + content[css_end:]

# 2. Rebuild the Body
form_body_start = content.find("echo '<div class=\"db-bottom-layout\">';")
form_body_end = content.find("} } else { // $_POST['PostInvoice'] is set so do the postings")

new_body = """
    // Pre-calculate Summary Taxes
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

    echo '<div style="max-width: 1200px; margin: 0 auto;">';

    // --- HEADER COMPACT ROW ---
    echo '<div class="invoice-header-grid">
            <div class="header-field">
                <label>' . __('Supplier') . '</label>
                <div style="font-size: 1.1rem; font-weight: 700; color: #1e293b; padding-top: 6px;">' . $_SESSION['SuppTrans']->SupplierName . ' <span style="color:#94a3b8; font-size:0.9rem; font-weight:400;">[' . $_SESSION['SuppTrans']->SupplierID . ']</span></div>
            </div>
            <div class="header-field">
                <label>' . __('Reference') . '</label>
                <input type="text" class="compact-input" required="required" placeholder="' . __('Inv No.') . '" name="SuppReference" value="' . $_SESSION['SuppTrans']->SuppReference . '" />
            </div>';
            
    if (!isset($_SESSION['SuppTrans']->TranDate)) {
        $_SESSION['SuppTrans']->TranDate = date($_SESSION['DefaultDateFormat']);
    }
    
    echo '  <div class="header-field">
                <label>' . __('Date') . '</label>
                <input type="date" class="compact-input" name="TranDate" value="' . FormatDateForSQL($_SESSION['SuppTrans']->TranDate) . '" />
            </div>
            <div class="header-field">
                <label>' . __('Ex. Rate') . '</label>
                <input type="text" class="compact-input number" name="ExRate" value="' . locale_number_format($_SESSION['SuppTrans']->ExRate, 'Variable') . '" />
            </div>
          </div>';

    // --- ACTION TOOLBAR ---
    echo '<div style="margin-bottom: 12px; padding-left: 4px;">
            <button type="submit" name="GRNS" value="' . __('Purchase Orders') . '" style="background:none; border:none; padding:0; cursor:pointer;" class="action-link"><i class="fas fa-shopping-cart"></i> ' . __('+ PO Items') . '</button>
            <button type="submit" name="Shipts" value="' . __('Shipments') . '" style="background:none; border:none; padding:0; cursor:pointer;" class="action-link"><i class="fas fa-truck"></i> ' . __('+ Shipment') . '</button>
            <button type="submit" name="Contracts" value="' . __('Contracts') . '" style="background:none; border:none; padding:0; cursor:pointer;" class="action-link"><i class="fas fa-file-contract"></i> ' . __('+ Contract') . '</button>
            <button type="submit" name="FixedAssets" value="' . __('Fixed Assets') . '" style="background:none; border:none; padding:0; cursor:pointer;" class="action-link"><i class="fas fa-briefcase"></i> ' . __('+ Fixed Asset') . '</button>
          </div>';

    // --- THE SPREADSHEET TABLE ---
    echo '<table class="spreadsheet-table">
            <thead>
                <tr>
                    <th style="width: 15%;">' . __('Type') . '</th>
                    <th style="width: 45%;">' . __('Description / Narrative') . '</th>
                    <th style="width: 25%;">' . __('Account / Reference') . '</th>
                    <th style="width: 10%; text-align: right;">' . __('Amount') . '</th>
                    <th style="width: 5%; text-align: center;"></th>
                </tr>
            </thead>
            <tbody>';
            
    // RENDER EXISTING CHARGES AS ROWS
    foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
        echo '<tr>
                <td><span style="color:#64748b; font-weight:600;">' . __('PO Item') . '</span></td>
                <td>' . $GRN->ItemDescription . '</td>
                <td>' . __('GRN') . ': ' . $GRN->GRNNo . '</td>
                <td style="text-align: right; font-weight: 600;">' . locale_number_format($GRN->This_QuantityInv * $GRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="text-align: center;"><a href="' . $RootPath . '/SuppInvGRNs.php?Delete=' . $GRN->GRNNo . '" class="delete-icon"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
        echo '<tr>
                <td><span style="color:#64748b; font-weight:600;">' . __('GL Line') . '</span></td>
                <td>' . $GLLine->Narrative . '</td>
                <td>' . $GLLine->GLCode . ' - ' . $GLLine->GLActName . '</td>
                <td style="text-align: right; font-weight: 600;">' . locale_number_format($GLLine->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="text-align: center;"><a href="?DeleteGLCode=' . $GLLine->Counter . '" class="delete-icon"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Shipts as $Shipt) {
        echo '<tr>
                <td><span style="color:#64748b; font-weight:600;">' . __('Shipment') . '</span></td>
                <td>' . __('Shipment Charge') . '</td>
                <td>' . $Shipt->ShiptRef . '</td>
                <td style="text-align: right; font-weight: 600;">' . locale_number_format($Shipt->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="text-align: center;"><a href="?DeleteShipt=' . $Shipt->Counter . '" class="delete-icon"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
        echo '<tr>
                <td><span style="color:#64748b; font-weight:600;">' . __('Contract') . '</span></td>
                <td>' . $Contract->Narrative . '</td>
                <td>' . $Contract->ContractRef . '</td>
                <td style="text-align: right; font-weight: 600;">' . locale_number_format($Contract->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="text-align: center;"><a href="?DeleteContract=' . $Contract->Counter . '" class="delete-icon"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Assets as $Asset) {
        echo '<tr>
                <td><span style="color:#64748b; font-weight:600;">' . __('Fixed Asset') . '</span></td>
                <td>' . $Asset->Description . '</td>
                <td>' . $Asset->AssetID . '</td>
                <td style="text-align: right; font-weight: 600;">' . locale_number_format($Asset->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="text-align: center;"><a href="?DeleteAsset=' . $Asset->Counter . '" class="delete-icon"><i class="fas fa-times"></i></a></td>
              </tr>';
    }

    // INLINE GL ENTRY (LAST ROW)
    $SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
    $Result = DB_query($SQL);
    
    echo '      <tr style="background: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <td><span style="color:#059669; font-weight:700;"><i class="fas fa-level-up-alt" style="transform: rotate(90deg);"></i> ' . __('New GL') . '</span></td>
                    <td><input type="text" name="GLNarrative" class="compact-input" placeholder="' . __('Enter Narrative...') . '" /></td>
                    <td>
                        <select name="AcctSelection" class="compact-input">
                            <option value="">' . __('Select GL Account...') . '</option>';
    while ($MyRow = DB_fetch_array($Result)) {
        echo '              <option value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . $MyRow['accountname'] . '</option>';
    }
    echo '              </select>
                    </td>
                    <td><input type="text" class="compact-input number" name="GLAmount" placeholder="0.00" /></td>
                    <td style="text-align: center;"><button type="submit" name="AddGLCodeToTrans" value="' . __('Add') . '" style="background:#059669; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:600;">' . __('Add') . '</button></td>
                </tr>';
    
    echo '  </tbody>
          </table>';

    // --- BOTTOM SUMMARY & SAVE ---
    echo '<div class="clearfix">
            <div class="summary-box">
                <div class="summary-row">
                    <span style="color:#64748b;">' . __('Sub-Total') . '</span>
                    <span style="font-weight:600; color:#1e293b;">' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                </div>';
                
    foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
        echo '  <div class="summary-row">
                    <span style="color:#64748b;">' . $Tax->TaxAuthDescription . '</span>
                    <span style="font-weight:600; color:#1e293b;">' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                </div>';
    }
    
    echo '      <div class="summary-row total">
                    <span>' . __('Grand Total') . '</span>
                    <span>' . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' <span style="font-size:0.8rem;">' . $_SESSION['SuppTrans']->CurrCode . '</span></span>
                </div>
            </div>
          </div>';
          
    // --- COMMENTS & SAVE BUTTON ---
    echo '<div style="margin-top: 30px; text-align: right; padding-bottom: 50px;">
            <button type="submit" name="PostInvoice" style="background:#059669; color:#fff; border:none; padding:16px 40px; font-size:1.1rem; font-weight:700; border-radius:8px; cursor:pointer; box-shadow: 0 4px 6px -1px rgba(5, 150, 105, 0.2);">
                <i class="fas fa-check"></i> ' . __('Save Supplier Invoice') . '
            </button>
          </div>';

    echo '</div><!-- end max-width -->';
"""

if form_body_start != -1 and form_body_end != -1:
    content = content[:form_body_start] + new_body + content[form_body_end:]

with open('SupplierInvoice.php', 'w') as f:
    f.write(content)
print("Spreadsheet UI replacement complete")
