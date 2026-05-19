<?php
$AllowAnything = true;
require 'includes/session.php';
echo "Language: " . $_SESSION['Language'] . "\n";
echo "ThousandsSeparator: '" . $ThousandsSeparator . "'\n";
echo "DecimalPoint: '" . $DecimalPoint . "'\n";
