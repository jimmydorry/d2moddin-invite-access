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

            $donated_users = $db->q("SELECT * FROM `invite_key` WHERE `donated` = 1 AND donation_txn_id IS NULL ORDER BY `queue_id` ASC;");

            echo '<h1>Legacy Donators</h1>';
            if (!empty($donated_users)) {
                echo '<table border="1">';
                echo '<tr align="center">
                    <th>Queue ID</th>
                    <th>Steam ID</th>
                    <th>Invited</th>
                    <th>Permament</th>
                    <th>Amount</th>
                    <th>Fees</th>
                    <th>Email</th>
                    <th>Date Joined</th>
                </tr>';
                foreach ($donated_users as $key => $value) {
                    echo '<tr align="center">
                    <td>' . $value['queue_id'] . '</td>
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['invited'] . '</td>
                    <td>' . $value['permament'] . '</td>
                    <td>$' . number_format($value['donation'], 2) . '</td>
                    <td>$' . number_format($value['donation_fee'], 2) . '</td>
                    <td>' . $value['donation_email'] . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                }
                echo '</table>';
            } else {
                echo 'No old donations.<br />';
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