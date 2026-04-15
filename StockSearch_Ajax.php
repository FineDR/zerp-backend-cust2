<?php
$PageSecurity = 1;
require(__DIR__ . '/includes/session.php');

if (isset($_GET['term'])) {
	$SearchTerm = DB_escape_string($_GET['term']);
	$SQL = "SELECT stockid, description, units, actualcost 
			FROM stockmaster 
			WHERE description LIKE '%$SearchTerm%' OR stockid LIKE '%$SearchTerm%'
			AND discontinued = 0
			LIMIT 15";
	$Result = DB_query($SQL);
	$Items = [];
	while ($MyRow = DB_fetch_array($Result)) {
		$Items[] = [
			'id' => $MyRow['stockid'],
			'description' => $MyRow['description'],
			'units' => $MyRow['units'],
			'cost' => $MyRow['actualcost']
		];
	}
	header('Content-Type: application/json');
	echo json_encode($Items);
	exit;
}
?>
