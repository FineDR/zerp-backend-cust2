<?php
include('includes/session.php');
echo "ExchangeRateFeed: " . $_SESSION['ExchangeRateFeed'] . "\n";
echo "UpdateCurrencyRatesDaily: " . $_SESSION['UpdateCurrencyRatesDaily'] . "\n";
$res = DB_query("SELECT currabrev, rate FROM currencies");
while ($row = DB_fetch_assoc($res)) {
    echo "Currency: " . $row['currabrev'] . " Rate: " . $row['rate'] . "\n";
}
?>
