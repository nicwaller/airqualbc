<?php
require('db.php');
require('queries.php');
require 'lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->get('/', function() {
	header('Location: /map.php');
	exit();
});

$app->get('/hello/:name/', function ($name) {
	echo "name = $name";
});

$app->group('/api', function() use ($app) {
	$app->response()->header('Content-Type', 'application/json');

	$app->get('/station', function() {
		print_r( json_encode( get_stations() ) );
	});
	
	$app->get('/sensor', function() {
		print_r( json_encode( get_sensor_names() ) );
	});
	
	$app->get('/sensor/:id', function($id) {
		print_r( json_encode( get_sensors_with_locations( $id, '2013-11-01' ) ) );
	});

});

$app->run();
