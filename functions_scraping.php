<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
date_default_timezone_set('America/Vancouver');

require('lib/scraper.php');
require('lib/simple_html_dom.php');
require('db.php');

$data_domain = 'envistaweb.env.gov.bc.ca';

function fetch_station_location( $id ) {
	$url = 'http://'. $data_domain .'/StationDetails.aspx?ST_ID='. $id;

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
	return array( 'lat' => $latt, 'lng' => $longt );
}

/**
 * int limit: maximum number to fetch during this session.
 *            defaults to -1 (unlimited)
 */
function fetch_missing_station_locations( $limit = 0 ) {
	global $db;

	// Find stations that don't have a location yet.
	$sql = "SELECT station_id FROM station WHERE latitude is NULL;";
	$stmt = $db->prepare($sql);
	$stmt->execute();
	$ids = array();
	while ($row = $stmt->fetch()) {
		$ids[] = $row['station_id'];
	}
	$ids = array_slice( $ids, 0, $limit );
	
	$sql = "UPDATE station
		SET latitude = :lat, longitude = :lng
		WHERE station_id = :id;";
	$stmt = $db->prepare($sql);
	
	foreach ($ids as $id) {
		echo "Locating station $id...\n";
		$loc = fetch_station_location( $id );
		print_r($loc);
		
		$stmt->bindParam(':lat', $loc['lat']);
		$stmt->bindParam(':lng', $loc['lng']);
		$stmt->bindParam(':id',        $id);
		$stmt->execute() or die(print_r($db->errorInfo(), true));;
	}
}

// date parameter must be of type DateTime
function fetch_station_htmldata( $station_id, $date ) {
	$url = 'http://'. $data_domain .'/frmStationReport.aspx';

	$data = array();
	$data['RadioButtonList1'] = '0';
	$data['RadioButtonList2'] = '0';
	$data['ddlPurpose'] = '0';
	$data['ddlRegion']  = '-1';
	$data['ddlOwner']   = '-1';
	$data['ddlStation'] = strval( $station_id ); // '107';
	$data['chkAll']     = 'on'; // should be 1 or something else?
	// TODO: how to handle checkboxes with variable numbers of sensors?
	$data['lstMonitorsn0CheckBox'] = 'on';
	$data['lstMonitorsn1CheckBox'] = 'on';
	$data['lstMonitorsn2CheckBox'] = 'on';
	$data['lstMonitorsn3CheckBox'] = 'on';
	$data['lstMonitorsn4CheckBox'] = 'on';
	$data['BasicDatePicker1$TextBox'] = $date->format('m/d/Y');
	$data['ddlAvgType'] = 'Mean';
	$data['ddlTimeBase'] = '60';
	$data['btnGenerateReport'] = 'Create Report';
	
	$scraper = new Scraper();
	$content = $scraper->aspFormPost($url, $data);
	return $content;
}

/**
 * Find the HTML table, extract all the values, and put it into an
 * associative array.
 **/
function parse_station_htmldata( $content ) {
	$skiprows = array(
		'Minimum',
		'MinTime',
		'Maximum',
		'MaxTime',
		'Avg',
		'Num',
		'Data[%]',
		'STD'
	);

	if (strpos('OutOfMemoryException', $content) !== FALSE) {
		die('Oops, crashed the server?');
	}

	$body = str_get_html($content);
	$table = $body->find('table#C1WebGrid1', 0);
	if ($table == null) {
		die("Failed to find table");
	}
	$realdata = array();
	$row_id = 0;
	$rows = $table->find('tr');

	// Gather the column headings and note the column index
	$column_map = array();
	$col_id = 0;
	foreach ($rows[0]->find('td') as $cell) {
		$column_map[ $col_id++ ] = $cell->plaintext;
	}
	unset($rows[0]);

	// Pre-seed the data array with the column names
	foreach ($column_map as $heading => $column_id) {
		$realdata[$heading] = array();
	}

	// Nobody cares about units of measurement, right?
	unset($rows[1]);

	// Now get the actual data values
	foreach ($rows as $row) {
		$col_id = 0;
		$cells = $row->find('td');

		if ( in_array( $cells[0]->plaintext, $skiprows ) ) {
			continue;
		}

		// Grab the Date Time row header
		$row_time_str = $cells[0]->plaintext;
		// Why do they do this? 24:00 AM isn't a real time!
		if (strpos($row_time_str, '24:00') !== FALSE) {
			$parts = date_parse_from_format('m/d/Y g:i A', $row_time_str);
			$year = $parts['year'];
			$month = $parts['month'];
			$day = $parts['day'];
			$row_time = strtotime( "$year-$month-$day 11:00 PM" ) + 3600;
		} else {
			// strtotime() [correctly] assumes m/d/y format when slashes are used
			$row_time = strtotime( $row_time_str );
		}
		if ($row_time === FALSE) {
			echo "Couldn't parse date: $row_time_str";
			continue;
		}
		$row_time_label = date('c', $row_time);
		unset( $cells[0] );

		$col_id = 1;
		foreach ($cells as $cell) {
			$column_name = $column_map[ $col_id ];
			if (strpos($cell->plaintext, '&nbsp;') === FALSE) {
				$cell_value = floatval($cell->plaintext);
				$realdata[$column_name][$row_time_label] = $cell_value;
			} else {
				$col_id++;
				continue;
			}
			$col_id++;
		}
	}
	return $realdata;
}

function download_latest_for_station( $station_id ) {
	global $db;
	//$date = DateTime::createFromFormat('Y-m-d', '2013-10-24');
	$date = new DateTime;
	// the form is silly and we need to provide the day before
	$date->sub(new DateInterval('P1D'));

	$filename = $raw_data_dir . '/st_'. $station_id .'_'. $date->format('Y-m-d') .'.html';
	if (!file_exists($filename)) {
		echo "Fetching station data...\n";
		$html_response = fetch_station_htmldata( $station_id, $date );
		file_put_contents( $filename, $html_response );
	
		echo "Reading file $filename\n";
		$results = parse_station_htmldata( file_get_contents( $filename ) );
		//print_r( $results );
	
		echo "Writing data into database...\n";	
		$sql = "INSERT INTO sample (station_id, sensor_name, time, value) VALUES
			(:station_id, :sensor_name, :time, :value);";
		$stmt = $db->prepare($sql);
		foreach ($results as $sensor_name => $samples) {
			foreach ($samples as $time => $value) {		
				$stmt->bindParam(':station_id',  $station_id);
				$stmt->bindParam(':sensor_name', $sensor_name);
				$stmt->bindParam(':time',        $time);
				$stmt->bindParam(':value',       $value);
				$stmt->execute();
			}
		}
	}
}



$sql = "SELECT station_id FROM station;";
$stmt = $db->prepare($sql);
$stmt->execute();
$stations = array();
while ($row = $stmt->fetch()) {
	$stations[] = $row['station_id'];
}

// this site is glitchy right now
unset($stations[70]);

//foreach (array(52, 107, 110, 132, 133, 444, 134, 139, 140, 210, 13, 176, 179, 212, 440) as $station) {
$downloaded = 0;
foreach ($stations as $station) {
	print_r("Downloading $downloaded/?\n");
	download_latest_for_station( $station );
	$downloaded++;

	echo "Stalling, to be nice...\n";
	sleep(2);
	if ($downloaded >= 2) {
		break;
	}
}

$sql = "SELECT station_id, sensor_name, time, value
	FROM sample
	WHERE sensor_name='FP10' and time like '2013-10-30%';";
$stmt = $db->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch()) {
	print_r($row);
}

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

function fetch_and_save_stations() {
	$list = parse_station_list( fetch_station_list() );
	save_station_list( $list );
}
