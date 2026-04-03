<?php
$PathPrefix = __DIR__ . '/../../';
require_once(__DIR__ . '/includes/api_session.php');

$orderno = 279;
$SQL = "SELECT * FROM salesorders WHERE orderno = " . $orderno;
$result = api_DB_query($SQL);
if ($row = DB_fetch_array($result)) {
    echo "Order 279 Header:\n";
    print_r($row);
} else {
    echo "Order 279 not found.\n";
}

$SQL = "SELECT * FROM salesorderdetails WHERE orderno = " . $orderno;
$result = api_DB_query($SQL);
echo "\nOrder 279 Details:\n";
while ($row = DB_fetch_array($result)) {
    print_r($row);
}
?>
