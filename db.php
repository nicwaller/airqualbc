<?php
include('config.php');

try {
	$db = new PDO ($dsn, $username, $password);
} catch (PDOException $e) {
	die ("DB ERR: ". $e->getMessage());
}

function createDB() {
	global $db;

	$db->exec("CREATE DATABASE IF NOT EXISTS airqual;
		CREATE USER airqual@localhost IDENTIFIED BY 'airqual';
		GRANT ALL ON airqual.* to teamdrive@localhost;
		FLUSH PRIVILEGES;")
		or die(print_r($db->errorInfo(), true));
}

function createTables() {
	global $db;

	$db->exec("CREATE TABLE IF NOT EXISTS sample (
		station_id INT,
		sensor_name VARCHAR(15),
		time DATETIME,
		value FLOAT,
		PRIMARY KEY (station_id, sensor_name, time)
	);");
}

//createDB();
$db->exec("USE airqual");
createTables();

/*
function getUsers($prefix = '') {
	global $db;
	$pattern = "%$prefix%";
	$stmt = $db->prepare("SELECT fullname FROM user
		WHERE fullname LIKE :pattern
		OR userPrincipalName LIKE :pattern;");
	$stmt->bindParam(':pattern', $pattern);
	$stmt->execute();
	while ($row = $stmt->fetch()) {
		print_r($row);
	}
}

getUsers('wall');
*/
