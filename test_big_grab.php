<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('scraper.php');
require('simple_html_dom.php');

date_default_timezone_set('America/Vancouver');

require('db.php');

// date parameter must be of type DateTime
function fetch_station_data( $station_id, $date ) {

	// PART 1: Use the form to request measurement data
	// ------------------------------------------------
	$url = 'http://envistaweb.env.gov.bc.ca/frmStationReport.aspx';

	$data = array();
	//$data['__EVENTTARGET'] = 'lnkWord';
	//$data['__EVENTARGUMENT'] = '';
	//$data['C1WebGrid1_scroll'] = '0,0';
	$data['RadioButtonList1'] = '0';
	$data['RadioButtonList2'] = '0';
	$data['ddlPurpose'] = '0';
	$data['ddlRegion']  = '-1';
	$data['ddlOwner']   = '-1';
	$data['ddlStation'] = strval( $station_id ); // '107';
	$data['chkAll']     = 'on'; // should be 1 or something else?
	$data['lstMonitorsn0CheckBox'] = 'on';
	$data['lstMonitorsn1CheckBox'] = 'on';
	$data['lstMonitorsn2CheckBox'] = 'on';
	$data['lstMonitorsn3CheckBox'] = 'on';
	$data['lstMonitorsn4CheckBox'] = 'on';
	$data['BasicDatePicker1$TextBox'] = $date->format('m/d/Y');
	$data['ddlAvgType'] = 'Mean';
	$data['ddlTimeBase'] = '60';
	//$data['txtErrorMonitor'] = 'Please select at least 1 monitor';
	$data['btnGenerateReport'] = 'Create Report';
	
	$scraper = new Scraper();
	$content = $scraper->aspFormPost($url, $data);
	return $content;
}
	
function parse_station_data( $content ) {
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

	$body = str_get_html($content);
	$table = $body->find('table#C1WebGrid1', 0);
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

	$filename = 'st_'. $station_id .'_'. $date->format('Y-m-d') .'.html';
	if (!file_exists($filename)) {
		$html_response = fetch_station_data( $station_id, $date );
		file_put_contents( $filename, $html_response );
	
		$results = parse_station_data( file_get_contents( $filename ) );
		//print_r( $results );
		
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

//foreach (array(52, 107, 110, 132, 133, 444, 134, 139, 140, 210, 13, 176, 179, 212, 440) as $station) {
foreach ($stations as $station) {
	download_latest_for_station( $station );
}

$sql = "SELECT station_id, sensor_name, time, value
	FROM sample
	WHERE sensor_name='FP10' and time like '2013-10-24%';";
$stmt = $db->prepare($sql);
$stmt->execute();
while ($row = $stmt->fetch()) {
	print_r($row);
}
