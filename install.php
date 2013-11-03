<?php
include('db.php');

$db->exec("CREATE DATABASE IF NOT EXISTS $database;
	CREATE USER $username@localhost IDENTIFIED BY '$password';
	GRANT ALL ON $database.* to $username@localhost;
	FLUSH PRIVILEGES;")
	or die(print_r($db->errorInfo(), true));

$db->exec("CREATE TABLE IF NOT EXISTS sample (
	station_id INT,
	monitor_name VARCHAR(15),
	time DATETIME,
	value FLOAT,
	PRIMARY KEY (station_id, monitor_name, time));");
