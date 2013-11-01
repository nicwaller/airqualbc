<?php
include('config.php');

try {
	$db = new PDO ($dsn, $username, $password);
} catch (PDOException $e) {
	die ("DB ERR: ". $e->getMessage());
}

$db->exec("USE $database");
