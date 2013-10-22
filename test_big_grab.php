<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

$url = 'http://envistaweb.env.gov.bc.ca/frmStationReport.aspx';

require('scraper.php');
$data = array();
//$data['__EVENTTARGET'] = 'lnkWord';
//$data['__EVENTARGUMENT'] = '';
//$data['C1WebGrid1_scroll'] = '0,0';
$data['RadioButtonList1'] = '0';
$data['RadioButtonList2'] = '0';
$data['ddlPurpose'] = '0';
$data['ddlRegion']  = '-1';
$data['ddlOwner']   = '-1';
$data['ddlStation'] = '107';
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
echo $content;



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
                $dat .= $cell->innertext();
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

