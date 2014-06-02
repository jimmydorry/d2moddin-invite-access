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
            //GRAB SITE STATS
            $site_stats_sql = "SELECT
                                    (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1 AND `permament` = 0) as total_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `donated` = 1) as total_donated_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `permament` = 1) as total_permament_invited
                                ;";

            $site_stats = $db->q($site_stats_sql);
            $site_stats = $site_stats[0];

            //IF WE HAVE CHANGED NUMBER INVITED, MAKES CHANGES IN DB
            if (isset($_POST['numInvited']) && !empty($_POST['numInvited'])) {
                $numinvited = $db->escape($_POST['numInvited']);
                $updateSQL = $db->q("UPDATE `invite_key` SET `invited` = 1 WHERE `queue_id` <= ?;",
                    'i',
                    $numinvited);

                if ($updateSQL) {
                    echo '<strong>Changed number of users invited!</strong><br /><br />';
                } else {
                    echo '<strong>Failed to change number of users invited!</strong><br /><br />';
                }

                //IF WE HAVE REDUCED INVITES, SET EVERYONE ABOVE THE INVITE NUMBER TO "NOT INVITED"
                if ($numinvited < $site_stats['total_users_invited']) {
                    $updateSQL = $db->q("UPDATE `invite_key` SET `invited` = 0 WHERE `queue_id` > ? AND permament = 0;",
                        'i',
                        $numinvited);

                    if ($updateSQL) {
                        echo '<strong>Revoked invites for users above #' . $numinvited . '!</strong><br /><br />';
                    } else {
                        echo '<strong>Failed to revoke invites for users above #' . $numinvited . '!</strong><br /><br />';
                    }
                }
            }

            if (isset($_POST['steamidInvite']) && !empty($_POST['steamidInvite'])) {
                isset($_POST['isPermament']) && $_POST['isPermament'] == 1 ? $permamentInvite = 1 : $permamentInvite = 0;
                isset($_POST['isDonated']) && $_POST['isDonated'] == 1 ? $donated = 1 : $donated = 0;
                isset($_POST['donationAmount']) && !empty($_POST["donationAmount"]) && is_numeric($_POST["donationAmount"]) ? $donation = $_POST["donationAmount"] : $donation = 0.05;

                if (empty($donated)) {
                    $donation = 0;
                } else {
                    $permamentInvite = 1;
                    $donation = number_format($donation, 2);
                }

                $steamidInvite = $_POST['steamidInvite'];
                $steamidInvite = explode('<br />', nl2br($_POST['steamidInvite']));
                //$sql = '(' . implode(', ', $steamidInvite) . ')';

                $upd_success = $upd_failure = 0;
                foreach ($steamidInvite as $key => $value) {
                    $updateSQL = $db->q("INSERT INTO `invite_key` (`invited`, `permament`, `steam_id`, `donated`, `donation`) VALUES (1, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `invited` = VALUES(`invited`), `permament` = VALUES(`permament`), `donated` = VALUES(`donated`), `donation` = VALUES(`donation`);",
                        'iiii',
                        $permamentInvite, $value, $donated, $donation);

                    if ($updateSQL) {
                        $upd_success++;
                    } else {
                        $upd_failure++;
                    }
                }

                echo '<strong>Specified users have skipped the queue!</strong> (Successes: ' . $upd_success . ' | Failures: ' . $upd_failure . ')<br /><br />';
            }

            $site_stats = $db->q($site_stats_sql);
            $site_stats = $site_stats[0];


            echo number_format($site_stats['total_users']) . ' total users in queue<br />';
            echo number_format($site_stats['total_permament_invited']) . ' permament invitees<br />';
            echo number_format($site_stats['total_donated_invited']) . ' donation invitees<br />';
            echo number_format($site_stats['total_users_invited']) . ' normal users invited<br /><br />';
            echo '<p>Set the number of invited users. Users already invited will lose their invite if you set it lower than
                the current number invited (number above).</p>';
            echo '<p>Steam IDs can be pasted into the "users to invite" to mass invite people. These steam_ids must be 64bit, and only one ID per line (no spaces before or after).</p>';
            echo '<p>Tick the "permament user" checkbox if these users are to be permamently invited.<br /> Inviting them again without the "permament user" checkbox ticked will remove them from the permament list.</p>';
            ?>

            <form method="post" action="./?key=<?= $_GET['key'] ?>">
                <table border="1">
                    <tr>
                        <th>Invited Users</th>
                        <td><input name="numInvited" type="number">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">&nbsp;</td>
                    </tr>
                    <tr>
                        <th>Users to invite</th>
                        <td><textarea rows="4" cols="50" name="steamidInvite" type="text" value=""></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>Permament?</th>
                        <td><input name="isPermament" value="1" type="checkbox">
                        </td>
                    </tr>
                    <tr>
                        <th>Donated?</th>
                        <td>
                            <input name="isDonated" value="1" type="checkbox">
                            <input name="donationAmount" value="5.00" type="number">
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center"><input type="submit" value="Modify"></td>
                    </tr>
                </table>
            </form>

            <?php

            $permament_users = $db->q("SELECT * FROM `invite_key` WHERE `permament` = 1;");

            echo '<h1>Permament Users</h1>';
            if (!empty($permament_users)) {
                echo '<table border="1">';
                echo '<tr align="center">
                    <th>Queue ID</th>
                    <th>Steam ID</th>
                    <th>Invited</th>
                    <th>Date Joined</th>
                </tr>';
                foreach ($permament_users as $key => $value) {
                    echo '<tr align="center">
                    <td>' . $value['queue_id'] . '</td>
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['invited'] . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                }
                echo '</table>';
            } else {
                echo 'No users have donated yet.<br />';
            }


            $donated_users = $db->q("SELECT * FROM `invite_key` WHERE `donated` = 1;");

            echo '<h1>Users that have donated</h1>';
            if (!empty($donated_users)) {
                echo '<table border="1">';
                echo '<tr align="center">
                    <th>Queue ID</th>
                    <th>Steam ID</th>
                    <th>Invited</th>
                    <th>Amount</th>
                    <th>Date Joined</th>
                </tr>';
                foreach ($donated_users as $key => $value) {
                    echo '<tr align="center">
                    <td>' . $value['queue_id'] . '</td>
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['invited'] . '</td>
                    <td>$' . number_format($value['donation'], 2) . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                }
                echo '</table>';
            } else {
                echo 'No users have donated yet.<br />';
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