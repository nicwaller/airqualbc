<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require('scraper.php');
require('simple_html_dom.php');

function fetch_station_data( $station_id = 107 ) {

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
	$data['BasicDatePicker1$TextBox'] = '10/20/2013';
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

	date_default_timezone_set('America/Vancouver');

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
			$parts = date_parse($row_time_str);
			$year = $parts['year'];
			$month = $parts['month'];
			$day = $parts['day'];
			$row_time = strtotime( "$year-$month-$day 11:00 PM" ) + 3600;
		} else {
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
				continue;
			}
			$col_id++;
		}
	}
	return $realdata;
}

$filename = 'st_107_2013_10_20.html';
//$html_response = fetch_station_data();
//file_put_contents( $filename, $html_response );

$results = parse_station_data( file_get_contents( $filename ) );
print_r( $results );
