<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

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
	
	$app->get('/monitor', function() {
		print_r( json_encode( get_monitor_names() ) );
	});
	
	$app->get('/monitor/:id', function($id) {
		print_r( json_encode( get_monitors_with_locations( $id ) ) );
	});

	$app->get('/monitor/:id/:time', function($id, $time) {
		$timestamp = date('Y-m-d H:i:s', intval( $time ) );
		
		print_r( json_encode( get_monitors_with_locations( $id, $timestamp ) ) );
	});

	$app->get('/sample/by-date/:dt', function($dt) {
		$t1 = strtotime( $dt );
		print_r( json_encode( get_sample_at( $t1 ) ) );
	});

	$app->notFound( function() {
		print_r( json_encode( array('error'=>'No matching API method') ) );
	});
});

$app->run();
