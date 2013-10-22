<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$url = 'http://envistaweb.env.gov.bc.ca/DynamicTable.aspx?G_ID=152';

require('scraper.php');
$data = array();
//$data['__EVENTTARGET'] = 'lnkWord';
$data['__EVENTARGUMENT'] = '';
$data['C1WebGrid1_scroll'] = '0,0';
$scraper = new Scraper();
$content = $scraper->aspFormPost($url, $data);
//echo $content;

require('simple_html_dom.php');
$body = str_get_html($content);
// print_r($body);
$table = $body->find('table');
$table = $table[0];
//echo $table[0]->__toString();
//$table->dump();
//die('end');

$realdata = array();

$row_id = 0;
foreach ($table->find('tr') as $row) {
	$col_id = 0;
	foreach ($row->find('td') as $cell) {
		$dat = '';
		foreach ($cell->find('div') as $val) {
			$dat .= $val->innertext();
			//$val->dump();
		//$cellValue = $cell->find('div');
		//echo $cellValue[0]->__toString(); // or ->outerText
		}
		$realdata[$row_id][$col_id] = $dat;
		$col_id++;
	}
	$row_id++;
}
// print_r($table);
// print_r($table[0]);
print_r($realdata);
