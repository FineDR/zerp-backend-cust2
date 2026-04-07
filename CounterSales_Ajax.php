<?php

// Robust AJAX Error Handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => "PHP Error: $errstr"]);
    exit;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => "PHP Fatal: " . $error['message']]);
    }
});

include_once(__DIR__ . '/includes/DefineCartClass.php');
$PageSecurity = 1;
require(__DIR__ . '/includes/session.php');
include_once(__DIR__ . '/includes/GetPrice.php');
include_once(__DIR__ . '/includes/SQL_CommonFunctions.php');
include_once(__DIR__ . '/includes/StockFunctions.php');
include_once(__DIR__ . '/includes/GetSalesTransGLCodes.php');
include_once(__DIR__ . '/includes/MiscFunctions.php');


$identifier = $_POST['identifier'] ?? $_GET['identifier'] ?? '';
$action = $_POST['action'] ?? '';

// Debug Logging
$logData = [
    'time' => date('Y-m-d H:i:s'),
    'identifier' => $identifier,
    'action' => $action,
    'stockid' => $_POST['stockid'] ?? 'n/a',
    'has_session_cart' => isset($_SESSION['Items'.$identifier]),
    'user' => $_SESSION['UserID'] ?? 'guest'
];
file_put_contents(__DIR__ . '/pos_ajax_debug.log', json_encode($logData) . "\n", FILE_APPEND);

if (empty($identifier)) {
    echo json_encode(['error' => 'Missing identifier']);
    exit;
}

if (!isset($_SESSION['Items'.$identifier]) || !($_SESSION['Items'.$identifier] instanceof Cart)) {
    $_SESSION['Items'.$identifier] = new Cart;
    // Fallback initialization if session cart is lost
    $_SESSION['Items'.$identifier]->Location = $_SESSION['UserStockLocation'] ?? '';
    $_SESSION['Items'.$identifier]->DebtorNo = $_SESSION['CompanyRecord']['cashsalecustomer'] ?? '';
}

$response = ['success' => false, 'message' => ''];

switch ($action) {
    case 'add':
        $NewItem = trim($_POST['stockid']);
        $NewItemQty = filter_number_format($_POST['qty'] ?? 1);
        
        // Ensure cart has necessary metadata for GetPrice/SelectOrderItems
        if (empty($_SESSION['Items'.$identifier]->Location)) {
             $_SESSION['Items'.$identifier]->Location = $_SESSION['UserStockLocation'];
        }
        
        $NewItemDue = DateAdd(date($_SESSION['DefaultDateFormat']), 'd', (int)($_SESSION['Items'.$identifier]->DeliveryDays ?? 0));
        $NewPOLine = 0;
        
        // POS Consolidation: Check if item already exists in cart
        $ExistingLine = -1;
        foreach ($_SESSION['Items'.$identifier]->LineItems as $line) {
            if ($line->StockID == $NewItem) {
                $ExistingLine = $line->LineNumber;
                break;
            }
        }

        if ($ExistingLine != -1) {
            // Update existing line quantity
            $_SESSION['Items'.$identifier]->update_cart_item(
                $ExistingLine,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->Quantity + $NewItemQty,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->Price,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->DiscountPercent,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->Narrative,
                '0', // UpdateDB
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->ItemDue,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->POLine,
                $_SESSION['Items'.$identifier]->LineItems[$ExistingLine]->GPPercent,
                $identifier
            );
            $msg = "Quantity updated for existing item.";
        } else {
            // ExRate depends on currency
            if ($_SESSION['Items'.$identifier]->DefaultCurrency != $_SESSION['CompanyRecord']['currencydefault']) {
                $ExRateResult = DB_query("SELECT rate FROM currencies WHERE currabrev='" . $_SESSION['Items'.$identifier]->DefaultCurrency . "'");
                $ExRate = (DB_num_rows($ExRateResult) > 0) ? DB_fetch_row($ExRateResult)[0] : 1;
            } else {
                $ExRate = 1;
            }

            ob_start();
            try {
                include(__DIR__ . '/includes/SelectOrderItems_IntoCart.php');
            } catch (Throwable $e) {
                file_put_contents(__DIR__ . '/pos_debug.log', $e->getMessage() . "\n" . $e->getTraceAsString(), FILE_APPEND);
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                exit;
            }
            $msg = ob_get_clean();
        }
        
        $response['success'] = true;
        $response['message'] = $msg;
        break;

    case 'remove':
        $lineId = filter_number_format($_POST['line_id']);
        $found = false;
        $logMsg = date('[Y-m-d H:i:s]') . " Attempting to remove lineId $lineId from identifier $identifier\n";
        
        if (isset($_SESSION['Items'.$identifier]->LineItems[$lineId])) {
            $logMsg .= "LineId $lineId found at direct index. Removing.\n";
            $_SESSION['Items'.$identifier]->remove_from_cart($lineId);
            $response['success'] = true;
            $found = true;
        } else {
            $logMsg .= "LineId $lineId NOT found at direct index. Searching " . count($_SESSION['Items'.$identifier]->LineItems) . " items...\n";
            foreach ($_SESSION['Items'.$identifier]->LineItems as $idx => $line) {
                if ($line->LineNumber == $lineId) {
                    $logMsg .= "LineId $lineId found at loop index $idx. Removing.\n";
                    $_SESSION['Items'.$identifier]->remove_from_cart($idx);
                    $response['success'] = true;
                    $found = true;
                    break;
                }
            }
        }
        if (!$found) {
            $logMsg .= "LineId $lineId NOT FOUND in cart items.\n";
            foreach ($_SESSION['Items'.$identifier]->LineItems as $idx => $line) {
                $logMsg .= " - Existing item at $idx has LineNumber: " . $line->LineNumber . "\n";
            }
            $response['error'] = "Item $lineId not found in cart";
        }
        file_put_contents(__DIR__ . '/pos_remove_debug.log', $logMsg, FILE_APPEND);
        break;

    case 'update_qty':
        $lineId = filter_number_format($_POST['line_id']);
        $newQty = filter_number_format($_POST['qty']);
        $targetLine = null;
        
        if (isset($_SESSION['Items'.$identifier]->LineItems[$lineId])) {
            $targetLine = &$_SESSION['Items'.$identifier]->LineItems[$lineId];
        } else {
            foreach ($_SESSION['Items'.$identifier]->LineItems as $idx => &$line) {
                if ($line->LineNumber == $lineId) {
                    $targetLine = &$line;
                    $lineId = $idx;
                    break;
                }
            }
        }
        
        if ($targetLine) {
            $_SESSION['Items'.$identifier]->update_cart_item(
                $lineId,
                $newQty,
                $targetLine->Price,
                $targetLine->DiscountPercent,
                $targetLine->Narrative,
                '0', // UpdateDB
                $targetLine->ItemDue,
                $targetLine->POLine,
                $targetLine->GPPercent,
                $identifier
            );
            $response['success'] = true;
        }
        break;

    case 'update_discount':
        $lineId = filter_number_format($_POST['line_id']);
        $newDiscount = filter_number_format($_POST['discount']) / 100;
        $targetLine = null;
        
        if (isset($_SESSION['Items'.$identifier]->LineItems[$lineId])) {
            $targetLine = &$_SESSION['Items'.$identifier]->LineItems[$lineId];
        } else {
            foreach ($_SESSION['Items'.$identifier]->LineItems as $idx => &$line) {
                if ($line->LineNumber == $lineId) {
                    $targetLine = &$line;
                    $lineId = $idx;
                    break;
                }
            }
        }
        
        if ($targetLine) {
            $_SESSION['Items'.$identifier]->update_cart_item(
                $lineId,
                $targetLine->Quantity,
                $targetLine->Price,
                $newDiscount,
                $targetLine->Narrative,
                '0', // UpdateDB
                $targetLine->ItemDue,
                $targetLine->POLine,
                $targetLine->GPPercent,
                $identifier
            );
            $response['success'] = true;
        }
        break;

    case 'clear':
        unset($_SESSION['Items'.$identifier]->LineItems);
        $_SESSION['Items'.$identifier]->LineItems = array();
        $_SESSION['Items'.$identifier]->ItemsOrdered = 0;
        $response['success'] = true;
        break;
}

// After any action, render the updated cart HTML
if ($response['success']) {
    $cartTotal = 0;
    $taxTotal = 0;
    $itemCount = 0;
    
    if (isset($_SESSION['Items'.$identifier]->LineItems) && is_array($_SESSION['Items'.$identifier]->LineItems)) {
        foreach ($_SESSION['Items'.$identifier]->LineItems as $line) {
            $cartTotal += ($line->Quantity * $line->Price * (1 - $line->DiscountPercent));
            $itemCount++;
        }
    }
    
    if (isset($_SESSION['Items'.$identifier]->TaxTotals) && is_array($_SESSION['Items'.$identifier]->TaxTotals)) {
        foreach ($_SESSION['Items'.$identifier]->TaxTotals as $amount) {
            if (is_numeric($amount)) $taxTotal += $amount;
        }
    }
    
    $response['cart_total'] = $cartTotal;
    $response['tax_total'] = $taxTotal;
    $response['grand_total'] = $cartTotal + $taxTotal;
    $response['item_count'] = $itemCount;
    $response['currency'] = $_SESSION['Items'.$identifier]->DefaultCurrency ?? $_SESSION['CompanyRecord']['currencydefault'] ?? 'GBP';
    
    // HTML generation for the cart items list
    ob_start();
    if ($itemCount > 0) {
        foreach ($_SESSION['Items'.$identifier]->LineItems as $line) {
        $subtotal = $line->Quantity * $line->Price * (1 - $line->DiscountPercent);
        $currDecimalPlaces = $_SESSION['Items'.$identifier]->CurrDecimalPlaces ?? 2;
        ?>
        <div class="pos-cart-item" data-line-id="<?= $line->LineNumber ?>">
            <div class="pos-cart-item-info">
                <h4><?= $line->ItemDescription ?></h4>
                <div class="pos-cart-item-meta">
                    <span class="pos-item-code"><?= $line->StockID ?></span>
                    <span style="font-weight: 600; color: var(--primary);">@ <?= locale_number_format($line->Price, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) ?></span>
                </div>
                <div class="pos-cart-item-qty">
                    <button type="button" class="pos-tool-btn" title="<?= __('Decrease Quantity') ?>" onclick="CounterSales.UpdateQty(<?= $line->LineNumber ?>, <?= $line->Quantity - 1 ?>)"><i class="fas fa-minus"></i></button>
                    <input type="text" class="pos-qty-input" 
                           value="<?= $line->Quantity ?>" 
                           onchange="CounterSales.UpdateQty(<?= $line->LineNumber ?>, this.value)">
                    <button type="button" class="pos-tool-btn" title="<?= __('Increase Quantity') ?>" onclick="CounterSales.UpdateQty(<?= $line->LineNumber ?>, <?= $line->Quantity + 1 ?>)"><i class="fas fa-plus"></i></button>
                </div>
                <!-- Discount Row (Hidden by default) -->
                <div id="DiscRow<?= $line->LineNumber ?>" class="pos-disc-row" style="display: <?= ($line->DiscountPercent > 0 ? 'flex' : 'none') ?>; margin-top: 8px; align-items: center; gap: 8px;">
                    <input type="text" class="pos-input-sm" style="width: 50px;" value="<?= $line->DiscountPercent * 100 ?>" 
                           onchange="CounterSales.UpdateDiscount(<?= $line->LineNumber ?>, this.value)">
                    <small style="font-size: 0.75rem; color: var(--text-muted);"><?= __('% Disc') ?></small>
                </div>
            </div>
            <div style="text-align: right; display: flex; flex-direction: column; justify-content: space-between;">
                <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);">
                    <small style="font-size: 0.7rem; vertical-align: middle; opacity: 0.7; margin-right: 2px;"><?= $_SESSION['Items'.$identifier]->DefaultCurrency ?></small>
                    <?= locale_number_format($subtotal, $_SESSION['Items'.$identifier]->CurrDecimalPlaces) ?>
                </div>
                <div class="pos-item-actions" style="display: flex; gap: 4px; justify-content: flex-end;">
                    <button type="button" class="pos-tool-btn" title="<?= __('Add Discount') ?>" onclick="CounterSales.ToggleDiscount(<?= $line->LineNumber ?>)">
                        <i class="fas fa-tag"></i>
                    </button>
                    <button type="button" class="pos-tool-btn delete" title="<?= __('Remove Item') ?>" onclick="CounterSales.RemoveItem(<?= $line->LineNumber ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php
        }
    } else {
        echo '<div class="pos-empty-cart">
                <i class="fas fa-shopping-basket" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-muted); font-weight: 500;">' . __('Cart is empty') . '</p>
              </div>';
    }
    $response['cart_html'] = ob_get_clean();
}

header('Content-Type: application/json');
echo json_encode($response);
