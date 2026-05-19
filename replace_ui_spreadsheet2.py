with open('SupplierInvoice.php', 'r') as f:
    content = f.read()

# Rebuild the Body
form_body_start = content.find("echo '<div class=\"db-bottom-layout\">';")
form_body_end = content.find("} } else { // $_POST")

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

    echo '<div style="max-width: 1200px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">';
    
    // --- HEADER COMPACT ROW ---
    echo '<div class="invoice-header-grid" style="display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 24px; margin-bottom: 30px;">
            <div style="flex: 2;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase;">' . __('Supplier') . '</label>
                <div style="font-size: 1.5rem; font-weight: 800; color: #0f172a; padding-top: 4px;">' . $_SESSION['SuppTrans']->SupplierName . '</div>
                <div style="color:#94a3b8; font-size:1rem; font-family: monospace;">[' . $_SESSION['SuppTrans']->SupplierID . ']</div>
            </div>
            
            <div style="flex: 1; margin-left: 20px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">' . __('Reference') . '</label>
                <input type="text" class="compact-input" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" required="required" placeholder="' . __('Inv No.') . '" name="SuppReference" value="' . $_SESSION['SuppTrans']->SuppReference . '" />
            </div>';
            
    if (!isset($_SESSION['SuppTrans']->TranDate)) {
        $_SESSION['SuppTrans']->TranDate = date($_SESSION['DefaultDateFormat']);
    }
    
    echo '  <div style="flex: 1; margin-left: 20px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">' . __('Date') . '</label>
                <input type="date" class="compact-input" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" name="TranDate" value="' . FormatDateForSQL($_SESSION['SuppTrans']->TranDate) . '" />
            </div>
            
            <div style="flex: 1; margin-left: 20px;">
                <label style="display: block; font-size: 0.8rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">' . __('Ex. Rate') . '</label>
                <input type="text" class="compact-input number" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;" name="ExRate" value="' . locale_number_format($_SESSION['SuppTrans']->ExRate, 'Variable') . '" />
            </div>
          </div>';

    // --- ACTION TOOLBAR ---
    echo '<div style="margin-bottom: 16px; display: flex; gap: 20px;">
            <button type="submit" name="GRNS" value="' . __('Purchase Orders') . '" style="background:none; border:none; padding:0; cursor:pointer; color: #059669; font-weight: 700;"><i class="fas fa-shopping-cart"></i> ' . __('+ PO Items') . '</button>
            <button type="submit" name="Shipts" value="' . __('Shipments') . '" style="background:none; border:none; padding:0; cursor:pointer; color: #059669; font-weight: 700;"><i class="fas fa-truck"></i> ' . __('+ Shipment') . '</button>
            <button type="submit" name="Contracts" value="' . __('Contracts') . '" style="background:none; border:none; padding:0; cursor:pointer; color: #059669; font-weight: 700;"><i class="fas fa-file-contract"></i> ' . __('+ Contract') . '</button>
            <button type="submit" name="FixedAssets" value="' . __('Fixed Assets') . '" style="background:none; border:none; padding:0; cursor:pointer; color: #059669; font-weight: 700;"><i class="fas fa-briefcase"></i> ' . __('+ Fixed Asset') . '</button>
          </div>';

    // --- THE SPREADSHEET TABLE ---
    echo '<table class="spreadsheet-table" style="width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #475569; width: 15%;">' . __('Type') . '</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #475569; width: 40%;">' . __('Description / Narrative') . '</th>
                    <th style="padding: 12px; text-align: left; font-size: 0.85rem; color: #475569; width: 25%;">' . __('Account / Reference') . '</th>
                    <th style="padding: 12px; text-align: right; font-size: 0.85rem; color: #475569; width: 15%;">' . __('Amount') . '</th>
                    <th style="padding: 12px; text-align: center; width: 5%;"></th>
                </tr>
            </thead>
            <tbody>';
            
    // RENDER EXISTING CHARGES AS ROWS
    foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
        echo '<tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><span style="color:#64748b; font-weight:600;">' . __('PO Item') . '</span></td>
                <td style="padding: 12px;">' . $GRN->ItemDescription . '</td>
                <td style="padding: 12px;">' . __('GRN') . ': ' . $GRN->GRNNo . '</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 1.1rem;">' . locale_number_format($GRN->This_QuantityInv * $GRN->ChgPrice, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="padding: 12px; text-align: center;"><a href="' . $RootPath . '/SuppInvGRNs.php?Delete=' . $GRN->GRNNo . '" style="color:#ef4444;"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
        echo '<tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><span style="color:#64748b; font-weight:600;">' . __('GL Line') . '</span></td>
                <td style="padding: 12px;">' . $GLLine->Narrative . '</td>
                <td style="padding: 12px;">' . $GLLine->GLCode . ' - ' . $GLLine->GLActName . '</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 1.1rem;">' . locale_number_format($GLLine->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="padding: 12px; text-align: center;"><a href="?DeleteGLCode=' . $GLLine->Counter . '" style="color:#ef4444;"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Shipts as $Shipt) {
        echo '<tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><span style="color:#64748b; font-weight:600;">' . __('Shipment') . '</span></td>
                <td style="padding: 12px;">' . __('Shipment Charge') . '</td>
                <td style="padding: 12px;">' . $Shipt->ShiptRef . '</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 1.1rem;">' . locale_number_format($Shipt->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="padding: 12px; text-align: center;"><a href="?DeleteShipt=' . $Shipt->Counter . '" style="color:#ef4444;"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
        echo '<tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><span style="color:#64748b; font-weight:600;">' . __('Contract') . '</span></td>
                <td style="padding: 12px;">' . $Contract->Narrative . '</td>
                <td style="padding: 12px;">' . $Contract->ContractRef . '</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 1.1rem;">' . locale_number_format($Contract->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="padding: 12px; text-align: center;"><a href="?DeleteContract=' . $Contract->Counter . '" style="color:#ef4444;"><i class="fas fa-times"></i></a></td>
              </tr>';
    }
    foreach ($_SESSION['SuppTrans']->Assets as $Asset) {
        echo '<tr style="border-bottom: 1px solid #e2e8f0;">
                <td style="padding: 12px;"><span style="color:#64748b; font-weight:600;">' . __('Fixed Asset') . '</span></td>
                <td style="padding: 12px;">' . $Asset->Description . '</td>
                <td style="padding: 12px;">' . $Asset->AssetID . '</td>
                <td style="padding: 12px; text-align: right; font-weight: 700; font-size: 1.1rem;">' . locale_number_format($Asset->Amount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</td>
                <td style="padding: 12px; text-align: center;"><a href="?DeleteAsset=' . $Asset->Counter . '" style="color:#ef4444;"><i class="fas fa-times"></i></a></td>
              </tr>';
    }

    // INLINE GL ENTRY (LAST ROW)
    $SQL = "SELECT accountcode, accountname FROM chartmaster ORDER BY accountcode";
    $Result = DB_query($SQL);
    
    echo '      <tr style="background: #f0fdf4; border-top: 2px solid #059669;">
                    <td style="padding: 12px;"><span style="color:#059669; font-weight:700;"><i class="fas fa-level-up-alt" style="transform: rotate(90deg);"></i> ' . __('New GL Line') . '</span></td>
                    <td style="padding: 12px;"><input type="text" name="GLNarrative" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" placeholder="' . __('Enter Narrative...') . '" /></td>
                    <td style="padding: 12px;">
                        <select name="AcctSelection" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                            <option value="">' . __('Select GL Account...') . '</option>';
    while ($MyRow = DB_fetch_array($Result)) {
        echo '              <option value="' . $MyRow['accountcode'] . '">' . $MyRow['accountcode'] . ' - ' . $MyRow['accountname'] . '</option>';
    }
    echo '              </select>
                    </td>
                    <td style="padding: 12px;"><input type="text" style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;" class="number" name="GLAmount" placeholder="0.00" /></td>
                    <td style="padding: 12px; text-align: center;"><button type="submit" name="AddGLCodeToTrans" value="' . __('Add') . '" style="background:#059669; color:#fff; border:none; padding:8px 16px; border-radius:4px; cursor:pointer; font-weight:700;">' . __('Add') . '</button></td>
                </tr>';
    
    echo '  </tbody>
          </table>';

    // --- BOTTOM SUMMARY & SAVE ---
    echo '<div style="display: flex; justify-content: flex-end; margin-top: 30px;">
            <div style="width: 400px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; font-size: 1rem;">
                    <span style="color:#64748b;">' . __('Sub-Total') . '</span>
                    <span style="font-weight:700; color:#1e293b;">' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                </div>';
                
    foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
        echo '  <div style="display: flex; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; font-size: 1rem;">
                    <span style="color:#64748b;">' . $Tax->TaxAuthDescription . '</span>
                    <span style="font-weight:700; color:#1e293b;">' . locale_number_format($Tax->TaxOvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces) . '</span>
                </div>';
    }
    
    echo '      <div style="display: flex; justify-content: space-between; padding: 24px; font-size: 1.5rem; background: #059669; color: #fff; border-radius: 0 0 8px 8px;">
                    <span style="font-weight:800;">' . __('Grand Total') . '</span>
                    <span style="font-weight:900;">' . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' <span style="font-size:1rem; opacity: 0.8;">' . $_SESSION['SuppTrans']->CurrCode . '</span></span>
                </div>
            </div>
          </div>';
          
    // --- COMMENTS & SAVE BUTTON ---
    echo '<div style="margin-top: 40px; text-align: right; border-top: 2px solid #f1f5f9; padding-top: 30px;">
            <button type="submit" name="PostInvoice" style="background:#059669; color:#fff; border:none; padding:20px 60px; font-size:1.25rem; font-weight:800; border-radius:12px; cursor:pointer; box-shadow: 0 10px 25px -5px rgba(5, 150, 105, 0.4); transition: transform 0.2s;">
                <i class="fas fa-check-circle" style="margin-right: 12px;"></i> ' . __('Post Supplier Invoice Now') . '
            </button>
          </div>';

    echo '</div><!-- end max-width -->';
    
"""

if form_body_start != -1 and form_body_end != -1:
    content = content[:form_body_start] + new_body + content[form_body_end:]
    with open('SupplierInvoice.php', 'w') as f:
        f.write(content)
    print("Spreadsheet UI replacement applied successfully.")
else:
    print(f"Error: form_body_start={form_body_start}, form_body_end={form_body_end}")

