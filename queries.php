<?php
require_once 'db.php';

function get_station_detail($id) {
	global $db;

	$sql = "SELECT DISTINCT sample.monitor_name
			FROM sample
			WHERE station_id = :id;";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(':id', $id);
	$stmt->execute();
	return cleanResults($stmt);
}

function get_monitor_detail($name) {
	global $db;

	$sql = "SELECT station_id, station_name, latitude, longitude
			FROM station
			WHERE station_id IN
				(SELECT DISTINCT sample.station_id
				FROM sample
				WHERE monitor_name = :name);";
	$stmt = $db->prepare($sql);
	$stmt->bindParam(':name', $name);
	$stmt->execute();
	return cleanResults($stmt);
}

function get_recent_samples($monitor_name=null, $time=null) {
	global $db;
	$time = ( $time == null ? time() : $time );
	if ($monitor_name === null) {
		$m_condition = "";
	} else {
		$m_condition = "AND monitor_name = :monitor_name";
	}

	$sql = "WITH latest AS
			(SELECT station_id, monitor_name, MAX(time) as maxtime
			FROM sample
			WHERE time <= :time
			$m_condition
			GROUP BY station_id, monitor_name)
		SELECT sample.station_id, sample.monitor_name, sample.time, sample.value, station.latitude, station.longitude
		FROM sample
		INNER JOIN latest ON sample.station_id = latest.station_id AND sample.monitor_name = latest.monitor_name AND sample.time = latest.maxtime
		INNER JOIN station ON sample.station_id = station.station_id
		ORDER BY time DESC;";
	$stmt = $db->prepare($sql);
	if ($monitor_name!==null) {
		$stmt->bindParam( ':monitor_name', $monitor_name );
	}
	$stmt->bindParam( ':time', date('Y-m-d H:i:s',$time) );
	$stmt->execute();
	return cleanResults($stmt);
}
