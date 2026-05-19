<?php
$AllowAnything = true;
require 'includes/session.php';
echo "AllowedTokens: " . implode(',', $_SESSION['AllowedPageSecurityTokens']) . "\n";
echo "PageSecurityArray[ConfirmDispatch_Invoice.php]: " . $_SESSION['PageSecurityArray']['ConfirmDispatch_Invoice.php'] . "\n";
echo "RequireCustomerSelection: " . $_SESSION['RequireCustomerSelection'] . "\n";
