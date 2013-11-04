<?php
require_once 'db.php';
require_once 'queries.php';
require_once 'lib/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->get('/', function() use ($app){
	$app->redirect('/map.php');
});

$app->group('/api', function() use ($app) {
	$app->response()->header('Content-Type', 'application/json');

	$app->get('/station', function() {
		$sql = 'SELECT station_id, station_name, latitude, longitude FROM station;';
		$result = queryAll($sql);
		echo json_encode($result);
	});

	$app->get('/station/:id', function($id) {
		$result = get_station_detail($id);
		echo json_encode($result);
	});
	
	$app->get('/monitor', function() {
		$sql = 'SELECT DISTINCT monitor_name FROM sample;';
		$result = queryAll($sql);
		echo json_encode($result);
	});

	$app->get('/monitor/:name', function($name) {
		$result = get_monitor_detail($name);
		echo json_encode($result);
	});	
	
	$app->get('/sample', function() {
		$result = get_recent_samples();
		echo json_encode($result);
	});

	$app->get('/sample/:monitor', function($monitor) {
		$result = get_recent_samples($monitor);
		echo json_encode($result);
	});

	$app->get('/sample/:monitor/:time', function($monitor, $time) {
		$result = get_recent_samples($monitor, intval($time));
		echo json_encode($result);
	});

	$app->notFound( function() {
		$result = array('error' => 'No matching method in API');
		echo json_encode($result);
	});
});

$app->run();
