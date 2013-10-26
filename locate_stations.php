<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('scraper.php');
require('simple_html_dom.php');

require('db.php');

function locate_station( $id ) {
	$url = 'http://envistaweb.env.gov.bc.ca/StationDetails.aspx?ST_ID='. $id;
	$scraper = new Scraper();
	$content = $scraper->curl($url);
	$body = str_get_html($content);
	$rows = $body->find('table table[cellpadding=3] tr');
	foreach ($rows as $row) {
		$key = trim($row->find('td',0)->plaintext);
		$val = trim($row->find('td',1)->plaintext);
		if ($key == 'Latitude') {
			$latt = $val;
		}
		if ($key == 'Longitude') {
			$longt = $val;
		}
	}
	return array(
		'lat' => $latt,
		'lng' => $longt,
	);
}

// Pick a few stations that we need to locate
$ids = array();
$sql = "SELECT station_id FROM station WHERE latitude is NULL LIMIT 200;";
$stmt = $db->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch()) {
	$ids[] = $row['station_id'];
}

$sql = "UPDATE station
	SET latitude = :lat, longitude = :lng
	WHERE station_id = :id;";
$stmt = $db->prepare($sql);

foreach ($ids as $id) {
	echo "Locating station $id...\n";
	$loc = locate_station( $id );
	print_r($loc);
	
	$stmt->bindParam(':lat', $loc['lat']);
	$stmt->bindParam(':lng', $loc['lng']);
	$stmt->bindParam(':id',        $id);
	$stmt->execute() or die(print_r($db->errorInfo(), true));;
}
