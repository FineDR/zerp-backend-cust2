<?php
include(__DIR__ . '/includes/DefineCartClass.php');
require(__DIR__ . '/includes/session.php');
include(__DIR__ . '/includes/GetPrice.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');
include(__DIR__ . '/includes/GetSalesTransGLCodes.php');

$identifier = $_POST['identifier'];

if (!isset($_SESSION['Items' . $identifier])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

$action = $_POST['action'];

if ($action == 'add') {
    $StockID = $_POST['stockid'];
    $Qty = filter_number_format($_POST['qty']);
    $NewItemDue = date($_SESSION['DefaultDateFormat']);

    if ($Qty > 0) {
        $NewPOLine = 0;
        include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
        $_SESSION['Items' . $identifier]->GetTaxes(($_SESSION['Items' . $identifier]->LineCounter - 1));
        renderResponse($identifier);
    }
} elseif ($action == 'remove') {
    $LineID = $_POST['line_id'];
    $_SESSION['Items' . $identifier]->remove_from_cart($LineID);
    renderResponse($identifier);
} elseif ($action == 'update_qty') {
    $LineID = $_POST['line_id'];
    $Qty = filter_number_format($_POST['qty']);
    $item = $_SESSION['Items' . $identifier]->LineItems[$LineID];
    
    $_SESSION['Items' . $identifier]->update_cart_item(
        $LineID, $Qty, $item->Price, $item->DiscountPercent, $item->Narrative, 'Yes', 
        $item->ItemDue, $item->POLine, $item->GPPercent, $identifier
    );
    renderResponse($identifier);
} elseif ($action == 'update_discount') {
    $LineID = $_POST['line_id'];
    $Discount = filter_number_format($_POST['discount']) / 100;
    $item = $_SESSION['Items' . $identifier]->LineItems[$LineID];

    $_SESSION['Items' . $identifier]->update_cart_item(
        $LineID, $item->Quantity, $item->Price, $Discount, $item->Narrative, 'Yes', 
        $item->ItemDue, $item->POLine, $item->GPPercent, $identifier
    );
    renderResponse($identifier);

} elseif ($action == 'clear') {
    unset($_SESSION['Items' . $identifier]->LineItems);
    $_SESSION['Items' . $identifier]->ItemsOrdered = 0;
    $_SESSION['Items' . $identifier]->LineCounter = 0;
    renderResponse($identifier);
} elseif ($action == 'initial') {
    renderResponse($identifier);
}

function renderResponse($identifier) {
    global $RootPath;
    $cart = $_SESSION['Items' . $identifier];
    $html = '';
    
    if (count($cart->LineItems) == 0) {
        $html = '<div class="centre" style="padding: 40px; color: var(--text-muted); font-style: italic;">' . __('Your return cart is empty') . '</div>';
    } else {
        $html .= '<table class="db-table">
                    <thead>
                        <tr>
                            <th>' . __('Item') . '</th>
                            <th class="number">' . __('Qty') . '</th>
                            <th class="number">' . __('Price') . '</th>
                            <th class="number">' . __('Disc %') . '</th>
                            <th class="number">' . __('Net') . '</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>';
        
        $TaxTotal = 0;
        foreach ($cart->LineItems as $line) {
            $Net = $line->Quantity * $line->Price * (1 - $line->DiscountPercent);
            
            // Tax calculation
            $LineTax = 0;
            foreach ($line->Taxes as $tax) {
                if ($tax->TaxOnTax == 1) {
                    $LineTax += ($tax->TaxRate * ($Net + $LineTax));
                } else {
                    $LineTax += ($tax->TaxRate * $Net);
                }
            }
            $TaxTotal += $LineTax;

            $html .= '<tr>
                        <td>
                            <div class="db-font-bold">' . $line->StockID . '</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">' . $line->ItemDescription . '</div>
                        </td>
                        <td class="number">
                            <input type="number" class="db-input db-input-compact number" value="' . $line->Quantity . '" 
                                   onchange="CounterReturns.UpdateQty(' . $line->LineNumber . ', this.value)" style="width: 60px;">
                        </td>
                        <td class="number">' . locale_number_format($line->Price, $cart->CurrDecimalPlaces) . '</td>
                        <td class="number">
                            <input type="number" class="db-input db-input-compact number" value="' . ($line->DiscountPercent * 100) . '" 
                                   onchange="CounterReturns.UpdateDiscount(' . $line->LineNumber . ', this.value)" style="width: 50px;">
                        </td>
                        <td class="number">' . locale_number_format($Net, $cart->CurrDecimalPlaces) . '</td>
                        <td class="centre">
                            <button type="button" class="db-btn db-btn-icon db-btn-ghost text-danger" onclick="CounterReturns.RemoveItem(' . $line->LineNumber . ')">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>';
        }
        $html .= '</tbody></table>';
    }

    echo json_encode([
        'success' => true,
        'cart_html' => $html,
        'cart_total' => $cart->total,
        'tax_total' => $TaxTotal,
        'grand_total' => ($cart->total + $TaxTotal),
        'currency' => $cart->DefaultCurrency,
        'item_count' => count($cart->LineItems)
    ]);
}
