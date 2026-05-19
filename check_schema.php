<?php
$PathPrefix = './';
require_once(__DIR__ . '/config.php');
$DatabaseName = $DefaultDatabase;
require_once(__DIR__ . '/includes/ConnectDB.php');

$res = DB_query("DESCRIBE salesorderdetails");
while ($row = DB_fetch_assoc($res)) {
    echo "salesorderdetails: " . $row['Field'] . "\n";
}
echo "\n";
$res = DB_query("DESCRIBE pickreqdetails");
while ($row = DB_fetch_assoc($res)) {
    echo "pickreqdetails: " . $row['Field'] . "\n";
}
?>
