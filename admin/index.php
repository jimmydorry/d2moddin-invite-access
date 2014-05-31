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
            $site_stats = $db->q("SELECT
                                    (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited
                                ;");
            $site_stats = $site_stats[0];

            //IF WE HAVE CHANGED NUMBER INVITED, MAKES CHANGES IN DB
            if (isset($_POST['numInvited']) && !empty($_POST['numInvited'])) {
                $numinvited = $db->escape($_POST['numInvited']);
                $db->q("UPDATE `invite_key` SET `invited` = 1 WHERE `queue_id` <= ?;",
                    'i',
                    $numinvited);

                //IF WE HAVE REDUCED INVITES, SET EVERYONE ABOVE THE INVITE NUMBER TO "NOT INVITED"
                if($numinvited < $site_stats['total_users_invited']){
                    $db->q("UPDATE `invite_key` SET `invited` = 0 WHERE `queue_id` > ?;",
                        'i',
                        $numinvited);
                }

                echo 'Changed number of users invited!<br /><br />';

                //GRAB SITE STATS
                $site_stats = $db->q("SELECT
                                    (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited
                                ;");
                $site_stats = $site_stats[0];
            }

            if (isset($_POST['steamidInvite']) && !empty($_POST['steamidInvite'])) {
                $steamidInvite = $_POST['steamidInvite'];
                $steamidInvite = explode('<br />', nl2br($_POST['steamidInvite']));

                $sql = '(' . implode(', ', $steamidInvite) . ')';


                $db->q("UPDATE `invite_key` SET `invited` = 1 WHERE `steam_id` IN " . $sql . ";");

                echo 'Changed number of users invited!<br /><br />';
                $site_stats = $db->q("SELECT
                                    (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited
                                ;");
                $site_stats = $site_stats[0];
            }

            echo number_format($site_stats['total_users']) . ' total users<br />';
            echo number_format($site_stats['total_users_invited']) . ' total users invited<br /><br />';
            echo ' <p>Set the number of invited users. Users already invited will lose their invite if you set it lower than
                the current number invited (above).</p>';
            echo ' <p>.</p>';
            ?>

            <form method="post" action="./?key=<?= $_GET['key'] ?>">
                <table border="1">
                    <tr>
                        <th>Invited Users</th>
                        <td><input name="numInvited" type="number">
                        </td>
                    </tr>
                    <tr>
                        <th>Users to invite</th>
                        <td><textarea rows="4" cols="50" name="steamidInvite" type="text" value=""></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" align="center"><input type="submit" value="Modify"></td>
                    </tr>
                </table>
            </form>

        <?php
        }
        else{
            echo 'Incorrect admin pass';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>