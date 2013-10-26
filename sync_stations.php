<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('scraper.php');
require('simple_html_dom.php');

require('db.php');

// date parameter must be of type DateTime
function fetch_station_list() {
	$url = 'http://envistaweb.env.gov.bc.ca/frmStationReport.aspx';
	
	$scraper = new Scraper();
	$content = $scraper->curl($url);
	return $content;
}
	
function parse_station_list( $content ) {
	$body = str_get_html($content);
	$options = $body->find('select#ddlStation option');

	$stations = array();
	foreach ($body->find('select#ddlStation option') as $opt) {
		$name = $opt->plaintext;
		$id = $opt->value;
		$stations[$id] = $name;
	}
	return $stations;
}

function save_station_list( $list ) {
	global $db;

	$sql = "INSERT INTO station (station_id, station_name) VALUES
		(:station_id, :station_name);";
	$stmt = $db->prepare($sql);

	foreach ($list as $id => $station_name) {
		$stmt->bindParam(':station_id',   $id);
		$stmt->bindParam(':station_name', $station_name);
		$stmt->execute();
	}
}

$list = parse_station_list( fetch_station_list() );
save_station_list( $list );
print_r( $list );
