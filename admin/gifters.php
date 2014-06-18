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

            $gifter_users = $db->q("SELECT ik.`queue_id`, ik.`steam_id`, ik.`date_invited`,
                (SELECT COUNT(*) FROM `invite_codes` WHERE `sender` = ik.`steam_id`) as num_invites,
                 (SELECT COUNT(*) FROM `invite_codes` WHERE `sender` = ik.`steam_id` AND `activated` = 1) as num_invites_activated
                 FROM `invite_key` ik WHERE ik.`gifter` = 1;");

            echo '<h1>Users that can create invite codes</h1>';
            if (!empty($gifter_users)) {
                echo '<table border="1">';
                echo '<tr align="center">
                    <th>Queue ID</th>
                    <th>Steam ID</th>
                    <th>Num Invites</th>
                    <th>Num Invites Activated</th>
                    <th>Date Joined</th>
                </tr>';
                foreach ($gifter_users as $key => $value) {
                    echo '<tr align="center">
                    <td>' . $value['queue_id'] . '</td>
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['num_invites'] . '</td>
                    <td>' . $value['num_invites_activated'] . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                }
                echo '</table>';
            } else {
                echo 'No users can create invite codes yet.<br />';
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