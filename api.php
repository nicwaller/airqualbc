<?php
require('db.php');
require('queries.php');
header('Content-Type: application/json');

//$parts = array( $_GET['resource'], $_GET['action'] );
//$directive = strtolower( implode( '.', array_filter( $parts ) ) );

// TODO use php recess framework for better REST API
// http://www.recessframework.org/

switch ( $_GET['resource'] ) {
	case 'sensor':
		if ( isset($_GET['id']) ) {
			$id = $_GET['id'];

			// TODO remove this
			$day = '2013-11-01 08:00';

			//$day = date('Y-m-d');
			print_r( json_encode( get_sensors_with_locations( $id, $day ) ) );
		} else {
			print_r( json_encode( get_sensor_names() ) );
		}
		break;
	default:
		print_r( "No result for $directive" );
		break;
}

function get_sensor_names() {
	global $db;
	$sql = "SELECT DISTINCT sensor_name FROM sample;";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}
