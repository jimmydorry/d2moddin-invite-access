<?php
require_once('../functions.php');
require_once('../connections/parameters.php');

$start = time();
include('./chart.php');

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        echo '<script type="text/javascript" src="//www.google.com/jsapi"></script>';

        $chart = new Chart('ColumnChart');

        $options = array(
            //'title' => 'Average spins in ' . $hits . ' attacks',
            //'theme' => 'maximized',
            'axisTitlesPosition' => 'in',
            'width' => 600,
            'height' => 260,
            'chartArea' => array(
                'width' => '100%',
                'left' => 60
            ),
            'hAxis' => array(
                'title' => 'Spins',
                'maxAlternation' => 1,
                //'textPosition' => 'in',
                //'viewWindowMode' => 'maximized'
            ),
            'vAxis' => array(
                'title' => 'Frequency',
                //'textPosition' => 'in',
            ),
            'legend' => array(
                'position' => 'bottom',
                'textStyle' => array(
                    'fontSize' => 10
                )
            ));

        $optionsDataTable = array(
            'width' => 600,
            'sortColumn' => 0,
            'sortAscending' => true,
            'alternatingRowStyle' => true,
            'page' => 'enable',
            'pageSize' => 6);

        echo '<div id="about" style="width: 600px;">';
        echo '<p>This page looks at some of the queue stats.</p>';
        echo '</div>';

        $signup_stats = $db->q('SELECT HOUR(date_invited) as hour, DAY(date_invited) as day, MONTH(date_invited) as month, YEAR(date_invited) as year, COUNT(*) as count FROM invite_key GROUP BY HOUR(date_invited), DAY(date_invited), MONTH(date_invited) ORDER BY 4,3,2,1;');


        $table = '';
        $table .= '<table border="1">';
        $table .= '
            <tr>
                <th>Time</th>
                <th>Count</th>
            </tr>';

        $super_array = array();
        foreach ($signup_stats as $key => $value) {
            $date = str_pad($value['hour'], 2, '0', STR_PAD_LEFT).':00 '.$value['day'].'-'.$value['month'].'-'.$value['year'];
            $table .= '
            <tr>
                <td>'.$date.'</td>
                <td>'.$value['count'].'</td>
            </tr>';
            $super_array[] = array('c' => array(array('v' => $date), array('v' => $value['count'])));
        }
        $table .= '</table>';

        $data = array(
            'cols' => array(
                array('id' => '', 'label' => 'Date', 'type' => 'string'),
                array('id' => '', 'label' => 'Joins', 'type' => 'number'),
            ),
            'rows' => $super_array
        );
        $chart->load(json_encode($data));
        $options['hAxis']['title'] = 'Spins';
        echo $chart->draw('queue_count', $options, true, $optionsDataTable);


        echo '<div id="queue_count"></div>';
        echo '<div id="queue_count_dataTable"></div>';

        echo '<hr />';
        echo $table;
        echo '<hr />';

        echo '<div id="pagerendertime" style="font-size: 12px;">';
        echo '<hr />Page generated in ' . (time() - $start) . 'secs';
        echo '</div>';


        $memcache->close();
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}