<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('db.php');

function get_stations() {
	global $db;
	$sql = "SELECT station_id, station_name, latitude, longitude FROM station;";
	$stmt = $db->prepare($sql);
	$stmt->execute();
        return $stmt->fetchAll();
}

/**
 * get_monitor_daily( 'FP10', '2013-10-30' );
 **/
function get_monitor_daily( $monitor_name = 'FP10', $date ) {
	global $db;
	$date_prefix = $date . "%";
	$sql = "SELECT station_id, monitor_name, time, value
		FROM sample
		WHERE monitor_name = :monitor_name and time like :date;";
	$stmt = $db->prepare($sql);
	$stmt->bindParam( ':monitor_name', $monitor_name );
	$stmt->bindParam( ':date', $date_prefix );
	$stmt->execute();
	$results = array();
	while ($row = $stmt->fetch()) {
		$results[] = $row;
	}
	return $results;
}

/**
 * get_monitors_with_locations( 'FP10', '2013-10-25 16:00' );
 **/
function get_monitors_with_locations( $monitor_name, $date = null ) {
	global $db;

	if ($date == null) {
		$date = date('Y-m-d H:i:s');
	}

//	$sql = "SELECT station.station_id, station.latitude, station.longitude, sample.value, sample.time
//	        FROM sample
//		INNER JOIN station ON sample.station_id = station.station_id
//		WHERE monitor_name = :monitor_name AND time = :date;";

	$sql = "WITH latest AS (
			SELECT station_id, monitor_name, MAX(time) as maxtime
			FROM sample
			WHERE monitor_name = :monitor_name AND time <= :date
			GROUP BY station_id, monitor_name)
		SELECT sample.station_id, sample.monitor_name, sample.time, sample.value, station.latitude, station.longitude
		FROM sample
		INNER JOIN latest ON sample.station_id = latest.station_id AND sample.monitor_name = latest.monitor_name AND sample.time = latest.maxtime
		INNER JOIN station ON sample.station_id = station.station_id
		ORDER BY time asc;";
	$stmt = $db->prepare($sql);
	$stmt->bindParam( ':monitor_name', $monitor_name );
	$stmt->bindParam( ':date', $date );
	$stmt->execute();
	$results = array();
	while ($row = $stmt->fetch()) {
		// Keep only named columns. Get rid of numbers.
		$j = 0; do { unset( $row[$j++] ); } while ( isset($row[$j]) );

		$results[] = $row;
	}
	return $results;
}

function get_monitor_names() {
	global $db;
	$sql = "SELECT DISTINCT monitor_name FROM sample;";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

/*
function get_sample_at( $time ) {
	global $db;
	$sql = "SELECT station.station_id, station.latitude, station.longitude, sample.value, sample.time
		FROM sample
		INNER JOIN station ON sample.station_id = station.station_id
		WHERE sample.time
}
*/
