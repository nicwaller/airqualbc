<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('db.php');

/**
 * get_sensor_daily( 'FP10', '2013-10-30' );
 **/
function get_sensor_daily( $sensor_name = 'FP10', $date ) {
	global $db;
	$date_prefix = $date . "%";
	$sql = "SELECT station_id, sensor_name, time, value
		FROM sample
		WHERE sensor_name = :sensor_name and time like :date;";
	$stmt = $db->prepare($sql);
	$stmt->bindParam( ':sensor_name', $sensor_name );
	$stmt->bindParam( ':date', $date_prefix );
	$stmt->execute();
	$results = array();
	while ($row = $stmt->fetch()) {
		$results[] = $row;
	}
	return $results;
}

/**
 * get_sensors_with_locations( 'FP10', '2013-10-25 16:00' );
 **/
function get_sensors_with_locations( $sensor_name, $date ) {
	global $db;
	$date_prefix = $date . "%";
	$sql = "SELECT station.station_id, station.latitude, station.longitude, sample.value, sample.time
	        FROM sample
		INNER JOIN station ON sample.station_id = station.station_id
		WHERE sensor_name = :sensor_name AND time LIKE '2013-10%';";
	$stmt = $db->prepare($sql);
	$stmt->bindParam( ':sensor_name', $sensor_name );
	$stmt->bindParam( ':date', $date_prefix );
	$stmt->execute();
	$results = array();
	while ($row = $stmt->fetch()) {
		// Keep only named columns. Get rid of numbers.
		$j = 0; do { unset( $row[$j++] ); } while ( isset($row[$j]) );

		$results[] = $row;
		//echo "{location: new google.maps.LatLng($lat, $lng), weight: $val},";
	}
	return $results;
}
