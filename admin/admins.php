<?php
require_once('../functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
    if ($db) {
        !empty($_GET["key"]) && is_numeric($_GET["key"]) ? $admin_pass = $_GET["key"] : $admin_pass = null;

        //CHECK ADMIN PASS
        if (!empty($admin_pass) && $admin_pass == $admin_pass_master) {

            $admin_users = $db->q("SELECT * FROM `admins` ORDER BY `date_added` DESC;");

            echo '<h1>Donators (~$'. number_format(($donated_amount[0]['donation_total'] - $donated_amount[0]['donation_total_fees']), 2).')</h1>';
            if (!empty($admin_users)) {
                echo '<table border="1">';
                echo '<tr align="center">
                    <th>Steam ID</th>
                    <th>Level</th>
                    <th>Date Joined</th>
                </tr>';
                foreach ($admin_users as $key => $value) {
                    echo '<tr align="center">
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['level'] . '</td>
                    <td>' . $value['date_added'] . '</td>
                </tr>';
                }
                echo '</table>';
            } else {
                echo 'No admin users yet.<br />';
            }


        } else {
            echo 'Incorrect admin pass';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>