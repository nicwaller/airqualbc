<?php
require('db.php');
require('queries.php');
header('Content-Type: application/json');

// TODO use php recess framework for better REST API
// http://www.recessframework.org/

switch ( $_GET['resource'] ) {
	case 'sensor':
		if ( isset($_GET['id']) ) {
			$id = $_GET['id'];

			// FIXME remove this
			$day = '2013-11-01 08:00';

			//$day = date('Y-m-d');
			print_r( json_encode( get_sensors_with_locations( $id, $day ) ) );
		} else {
			print_r( json_encode( get_sensor_names() ) );
		}
		break;
	case 'station':
		if ( isset($_GET['id']) ) {
			echo "not yet implemented";
		} else {
			print_r( json_encode( get_stations() ) );
		}
		break;
	default:
		print_r( "No match in API." );
		break;
}
