<?php
include('config.php');

try {
	$db = new PDO($dsn);
} catch (PDOException $e) {
	die ("DB ERR: ". $e->getMessage());
}

// Keep only named columns.
function cleanResults($stmt) {
	$results = array();
	while ($row = $stmt->fetch()) {
		$j = 0; do { unset( $row[$j++] ); } while ( isset( $row[$j] ) );
		$results[] = $row;
	}
	return $results;
}

function queryAll($sql) {
	global $db;
	$stmt = $db->prepare($sql);
	$stmt->execute();
	return cleanResults($stmt);
}
