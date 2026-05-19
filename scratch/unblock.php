<?php
$PathPrefix = dirname(__DIR__) . '/';
require_once($PathPrefix . 'config.php');
require_once($PathPrefix . 'includes/MiscFunctions.php');
require_once($PathPrefix . 'includes/ConnectDB_mysqli.php');

$SQL = "UPDATE www_users SET blocked = 0, password = '" . password_hash('weberp', PASSWORD_DEFAULT) . "' WHERE userid = 'admin'";
mysqli_query($db, $SQL);

echo "Admin unblocked and password reset to weberp\n";
?>
