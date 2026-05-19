<?php
$PathPrefix = __DIR__ . '/../';
if (!function_exists('__')) {
    function __($text) { return $text; }
}
include($PathPrefix . 'includes/DefineStockAdjustment.php');
include($PathPrefix . 'includes/DefineSerialItems.php');

// Mock session
session_start();
$_SESSION['DatabaseName'] = 'zerp_10TZ120093';
$_SESSION['UserID'] = 'admin';
$_SESSION['CompanyRecord']['gllink_stock'] = 0; // Disable GL for now

include($PathPrefix . 'config.php');
include($PathPrefix . 'includes/ConnectDB.php');
include($PathPrefix . 'includes/SQL_CommonFunctions.php');
include($PathPrefix . 'includes/DateFunctions.php');

$identifier = 'TEST';
$_SESSION['Adjustment' . $identifier] = new StockAdjustment();
$_SESSION['Adjustment' . $identifier]->StockID = 'TEST-PART-001';
$_SESSION['Adjustment' . $identifier]->StockLocation = 'MAIN';
$_SESSION['Adjustment' . $identifier]->Quantity = 10;
$_SESSION['Adjustment' . $identifier]->AdjDate = date('Y-m-d');
$_SESSION['Adjustment' . $identifier]->StandardCost = 0;
$_SESSION['Adjustment' . $identifier]->Narrative = 'Test Adjustment';

$AdjustmentNumber = GetNextTransNo(17);
$Period = GetPeriod(date('d/m/Y'));

echo "Adjustment Number: $AdjustmentNumber\n";
echo "Period: $Period\n";

DB_Txn_Begin();

$SQL = "INSERT INTO stockmoves (stockid,
								type,
								transno,
								loccode,
								trandate,
								userid,
								prd,
								reference,
								qty,
								standardcost,
								newqoh)
			VALUES ('" . $_SESSION['Adjustment' . $identifier]->StockID . "',
					17,
					'" . $AdjustmentNumber . "',
					'" . $_SESSION['Adjustment' . $identifier]->StockLocation . "',
					'" . $_SESSION['Adjustment' . $identifier]->AdjDate . "',
					'" . $_SESSION['UserID'] . "',
					'" . $Period . "',
					'" . $_SESSION['Adjustment' . $identifier]->Narrative . "',
					'" . $_SESSION['Adjustment' . $identifier]->Quantity . "',
					'" . $_SESSION['Adjustment' . $identifier]->StandardCost . "',
					'" . 10 . "')";

echo "SQL: $SQL\n";
$Result = DB_query($SQL, 'Failed to insert stockmove', '', true);

if ($Result) {
    echo "Insert Stockmoves: SUCCESS\n";
} else {
    echo "Insert Stockmoves: FAILED\n";
}

$SQL = "UPDATE locstock SET quantity = quantity + 10
        WHERE stockid='TEST-PART-001'
        AND loccode='MAIN'";
echo "SQL: $SQL\n";
$Result = DB_query($SQL, 'Failed to update locstock', '', true);

if ($Result) {
    echo "Update Locstock: SUCCESS\n";
} else {
    echo "Update Locstock: FAILED\n";
}

DB_Txn_Commit();
echo "Transaction Committed\n";
?>
