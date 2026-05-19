<?php
include('includes/ConnectDB_mysqli.php');

$DebtorNo = 'TESTCUST';
$BranchCode = 'TESTCUST';
$TransNo = 500;
$TransDate = date('Y-m-d');

// 1. Insert Invoice into debtortrans
$sql = "INSERT INTO debtortrans (transno, type, debtorno, branchcode, trandate, inputdate, prrate, ovamount, ovdiscount, ovfreight, ovgst, rate, invtext, shipvia, order_, alloc) 
        VALUES ($TransNo, 10, '$DebtorNo', '$BranchCode', '$TransDate', NOW(), 1, 1500, 0, 50, 270, 1, 'Bulk Order - Redesign Test', 1, 1, 0)";
DB_query($sql);

// 2. Insert line items into stockmoves
$sql = "INSERT INTO stockmoves (stockid, type, transno, loccode, trandate, prrate, qty, price, discountpercent, narrative) 
        VALUES ('AUTO-0001', 10, $TransNo, 'MAIN', '$TransDate', 1, -10, 150, 0, 'Test item 1')";
DB_query($sql);

$sql = "INSERT INTO stockmoves (stockid, type, transno, loccode, trandate, prrate, qty, price, discountpercent, narrative) 
        VALUES ('AUTO-0001', 10, $TransNo, 'MAIN', '$TransDate', 1, -5, 100, 0, 'Test item 2')";
DB_query($sql);

echo "Seed data created: Invoice #$TransNo for $DebtorNo\n";
?>
