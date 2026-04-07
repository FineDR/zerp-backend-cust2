<?php
$PageSecurity = 1;
require(__DIR__ . '/includes/session.php');

if (isset($_GET['term'])) {
	$SearchTerm = DB_escape_string($_GET['term']);
	$SQL = "SELECT debtorno, name, address1 FROM debtorsmaster 
			WHERE name LIKE '%$SearchTerm%' OR debtorno LIKE '%$SearchTerm%'
			LIMIT 10";
	$Result = DB_query($SQL);
	$Customers = [];
	while ($MyRow = DB_fetch_array($Result)) {
		$Customers[] = [
			'id' => $MyRow['debtorno'],
			'name' => $MyRow['name'],
			'address' => $MyRow['address1']
		];
	}
	header('Content-Type: application/json');
	echo json_encode($Customers);
	exit;
}
