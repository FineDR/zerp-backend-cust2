<?php
$PathPrefix = './';
require 'includes/session.php';
require 'includes/MiscFunctions.php';
echo "\nTesting filter_number_format:\n";
echo "1,000.00 -> " . filter_number_format("1,000.00") . "\n";
echo "ThousandsSeparator: '" . $ThousandsSeparator . "'\n";
echo "DecimalPoint: '" . $DecimalPoint . "'\n";
?>
