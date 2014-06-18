<?php
require_once('../functions.php');
require_once('../connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

$steamid64 = '';
if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $steamid64 = $_SESSION['user_id'];
}

$user_details = !empty($_SESSION['user_details'])
    ? $_SESSION['user_details']
    : NULL;

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
    if ($db) {
        !empty($_GET["key"]) && is_numeric($_GET["key"]) ? $admin_pass = $_GET["key"] : $admin_pass = null;

        //CHECK ADMIN PASS
        if (!empty($admin_pass) && $admin_pass == $admin_pass_master) {
            $d2moddin_admins = $db->q("SELECT * FROM `admins` WHERE `steam_id` = ?;",
                'i',
                $steamid64);
            $d2moddin_admins = $d2moddin_admins[0];

            //CHECK IF STEAM_ID in admin table
            if (!empty($d2moddin_admins)) {

                //GRAB SITE STATS
                $site_stats_sql = "SELECT
                                    (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1 AND `permament` = 0 AND `donated` = 0) as total_normal_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `donated` = 1) as total_donated_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `donated` = 1 AND `invited` = 1) as total_donated_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `permament` = 1) as total_permament_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `permament` = 1 AND `invited` = 1) as total_permament_users_invited
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
                    isset($_POST['isPermament']) && $_POST['isPermament'] == 1
                        ? $permamentInvite = 1
                        : $permamentInvite = 0;
                    isset($_POST['isInvited']) && $_POST['isInvited'] == 1
                        ? $invitedInvite = 1
                        : $invitedInvite = 0;
                    isset($_POST['isGifter']) && $_POST['isGifter'] == 1
                        ? $gifterInvite = 1
                        : $gifterInvite = 0;
                    isset($_POST['isBannedReason']) && !empty($_POST['isBannedReason'])
                        ? $bannedReason = htmlentities($_POST['isBannedReason'])
                        : $bannedReason = 'No reason provided';

                    if (isset($_POST['submit'])) {
                        if ($_POST['submit'] == 'Modify') {
                            $sql_action = 'm';
                        } else if ($_POST['submit'] == 'Delete') {
                            $sql_action = 'd';
                        } else if ($_POST['submit'] == 'Ban') {
                            $invitedInvite = 0;
                            $permamentInvite = 0;
                            $gifterInvite = 0;
                            $bannedInvite = 1;
                            $sql_action = 'b';
                        } else if ($_POST['submit'] == 'Un-Ban') {
                            $bannedInvite = 0;
                            $bannedReason = NULL;
                            $sql_action = 'b';
                        } else if ($_POST['submit'] == 'Adminify') {
                            $sql_action = 'a';
                        } else if ($_POST['submit'] == 'Adminify_Delete') {
                            $sql_action = 'ad';
                        }
                    } else {
                        $sql_action = 'm';
                    }

                    $steamidInvite = $_POST['steamidInvite'];
                    $steamidInvite = explode('<br />', nl2br($_POST['steamidInvite']));
                    //$sql = '(' . implode(', ', $steamidInvite) . ')';

                    $upd_success = $upd_failure = 0;
                    foreach ($steamidInvite as $key => $value) {
                        if ($sql_action == 'm') {
                            $updateSQL = $db->q("INSERT INTO `invite_key` (`invited`, `permament`, `gifter`, `steam_id`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `invited` = VALUES(`invited`), `permament` = VALUES(`permament`), `gifter` = VALUES(`gifter`);",
                                'iiii',
                                $invitedInvite, $permamentInvite, $gifterInvite, $value);
                        } else if ($sql_action == 'b') {
                            $updateSQL = $db->q("INSERT INTO `invite_key` (`invited`, `permament`, `gifter`, `banned`, `banned_reason`, `steam_id`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `invited` = VALUES(`invited`), `permament` = VALUES(`permament`), `banned` = VALUES(`banned`), `banned_reason` = VALUES(`banned_reason`), `gifter` = VALUES(`gifter`);",
                                'iiiisi',
                                $invitedInvite, $permamentInvite, $gifterInvite, $bannedInvite, $bannedReason, $value);
                        } else if ($sql_action == 'd') {
                            $updateSQL = $db->q("DELETE FROM `invite_key` WHERE `steam_id` = ?;",
                                'i',
                                $value);
                        } else if ($sql_action == 'a') {
                            $updateSQL = $db->q("INSERT INTO `admins` (`steam_id`) VALUES (?);",
                                'i',
                                $value);
                        } else if ($sql_action == 'ad') {
                            $updateSQL = $db->q("DELETE FROM `admins` WHERE `steam_id` = ?;",
                                'i',
                                $value);
                        }

                        if ($updateSQL) {
                            $upd_success++;
                        } else {
                            $upd_failure++;
                        }
                    }

                    echo '<strong>Specified users have been modified!</strong> (Successes: ' . $upd_success . ' | Failures: ' . $upd_failure . ')<br /><br />';
                }

                if (isset($_GET['donatorlive'])) {
                    $updateSQL = $db->q("UPDATE `invite_key` SET `invited` = 1 WHERE `donated` = 1;");

                    if ($updateSQL) {
                        echo '<strong>All donators are now invited!</strong><br /><br />';
                    } else {
                        echo '<strong>Failed to invite all donators!</strong><br /><br />';
                    }
                }

                if (isset($_GET['donatorliveperma'])) {
                    $updateSQL = $db->q("UPDATE `invite_key` SET `invited` = 1, `permament` = 1 WHERE `donated` = 1;");

                    if ($updateSQL) {
                        echo '<strong>All donators are now permamently invited!</strong><br /><br />';
                    } else {
                        echo '<strong>Failed to permamently invite all donators!</strong><br /><br />';
                    }
                }

                $site_stats = $db->q($site_stats_sql);
                $site_stats = $site_stats[0];


                echo '<h2>Queue Stats</h2>';
                echo number_format($site_stats['total_users']) . ' total users in queue<br />';
                echo number_format($site_stats['total_users_invited']) . ' users invited (no other flags: ' . number_format($site_stats['total_normal_users_invited']) . ')<br />';
                echo number_format($site_stats['total_permament_users']) . ' users with the permament flag (invited: ' . number_format($site_stats['total_permament_users_invited']) . ')<br />';
                echo number_format($site_stats['total_donated_users']) . ' users with the donator flag (invited: ' . number_format($site_stats['total_donated_users_invited']) . ')<br />';
                echo '<a target="_new" href="../stats/">Joins over time plot</a><br />';
                echo '<p>Set the number of invited users. Users already invited will lose their invite if you set it lower than
                the current number invited (number above).</p>';
                echo '<p>Steam IDs can be pasted into the "list of users" to mass edit user profiles. These steam_ids must be 64bit, and only one ID per line (no spaces before or after).</p>';
                echo '<p>Tick the "permament user" checkbox if these users are to have the permament tag, "invited user" checkbox for invited tag, etc.<br /> These options will overwrite existing tags on the users.</p>';
                ?>

                <h2><a href="./?key=<?= $_GET['key'] ?>">REFRESH THE PAGE BEFORE PERFORMING ANY ACTION (TO CHECK YOUR
                        SESSION)</a></h2>

                <h2>Modify Users</h2>
                <form method="post" action="./?key=<?= $_GET['key'] ?>">
                    <table border="1">
                        <tr>
                            <th align="left">Number of Users<br/>to Invite</th>
                            <td><input name="numInvited" type="number">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th align="left">List of users</th>
                            <td><textarea rows="4" cols="50" name="steamidInvite" type="text" value=""></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th align="left">Ban reason<br />(if applicable)</th>
                            <td><textarea rows="4" cols="50" name="isBannedReason" type="text" value="">No reason given</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th align="left">Permament?</th>
                            <td><input name="isPermament" value="1" type="checkbox">
                            </td>
                        </tr>
                        <tr>
                            <th align="left">Invited?</th>
                            <td><input name="isInvited" value="1" type="checkbox">
                            </td>
                        </tr>
                        <tr>
                            <th align="left">Can Make Codes?</th>
                            <td><input name="isGifter" value="1" type="checkbox">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><input name="submit" type="submit" value="Modify"><input
                                    name="submit" type="submit" value="Delete"><input name="submit" type="submit" value="Ban"><input name="submit" type="submit" value="Un-Ban"></td>
                        </tr>
                    </table>
                </form>

                <h2>Modify Admins</h2>
                <form method="post" action="./?key=<?= $_GET['key'] ?>">
                    <table border="1">
                        <tr>
                            <th align="left">List of admins</th>
                            <td><textarea rows="4" cols="50" name="steamidInvite" type="text" value=""></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><input name="submit" type="submit" value="Adminify"><input
                                    name="submit" type="submit" value="Adminify_Delete"></td>
                        </tr>
                    </table>
                </form>

                <?php

                echo '<br /><a target="_new" href="./add_donators.php?key=' . $admin_pass . '">Manually add donators here</a><br />';
                //echo '<a target="_new" href="./?key=' . $admin_pass . '&donatorlive">CLICK HERE TO INVITE ALL DONATORS</a><br />';
                //echo '<a target="_new" href="./?key=' . $admin_pass . '&donatorliveperma">CLICK HERE TO PERMA INVITE ALL DONATORS</a><br />';

                $permament_users = $db->q("SELECT * FROM `invite_key` WHERE `permament` = 1 ORDER BY queue_id ASC LIMIT 0, 20;");

                echo '<h1>Permament Users (<a target="_new" href="./permament.php?key=' . $admin_pass . '">rest here</a>)</h1>';
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
                    echo 'No permament users yet.<br />';
                }

                $donated_users = $db->q("SELECT * FROM `invite_key` WHERE `donated` = 1 AND `donation_txn_id` IS NOT NULL ORDER BY `donation` DESC LIMIT 0, 20;");
                $donated_amount = $db->q("SELECT SUM(`donation`) as donation_total, SUM(`donation_fee`) as donation_total_fees FROM `invite_key` WHERE `donated` = 1 AND `donation_txn_id` IS NOT NULL;");

                echo '<h1>Top 20 Donators (<a target="_new" href="./donators.php?key=' . $admin_pass . '">$'. number_format(($donated_amount[0]['donation_total'] - $donated_amount[0]['donation_total_fees']), 2).'</a>)</h1>';
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
                    <td align="left">' . $value['donation_email'] . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                    }
                    echo '</table>';
                } else {
                    echo 'No users have donated yet.<br />';
                }

                $admin_users = $db->q("SELECT * FROM `admins` ORDER BY `date_added` DESC LIMIT 0, 20;");

                echo '<h1>Top 20 Admins (<a target="_new" href="./admins.php?key=' . $admin_pass . '">rest here</a>)</h1>';
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

                $banned_users = $db->q("SELECT * FROM `invite_key` WHERE `banned` = 1 ORDER BY `date_invited` DESC LIMIT 0, 20;");

                echo '<h1>Top 20 Banned Users (<a target="_new" href="./banned.php?key=' . $admin_pass . '">rest here</a>)</h1>';
                if (!empty($banned_users)) {
                    echo '<table border="1">';
                    echo '<tr align="center">
                    <th>Queue ID</th>
                    <th>Steam ID</th>
                    <th>Reason</th>
                    <th>Date Joined</th>
                </tr>';
                    foreach ($banned_users as $key => $value) {
                        echo '<tr align="center">
                    <td>' . $value['queue_id'] . '</td>
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['banned_reason'] . '</td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                    }
                    echo '</table>';
                } else {
                    echo 'No banned users yet.<br />';
                }

                $gifter_users = $db->q("SELECT * FROM `invite_key` WHERE `gifter` = 1 ORDER BY `date_invited` DESC LIMIT 0, 20;");

                echo '<h1>Top 20 Users That Can Make Invite Codes (<a target="_new" href="./gifters.php?key=' . $admin_pass . '">rest here</a>)</h1>';
                if (!empty($gifter_users)) {
                    echo '<table border="1">';
                    echo '<tr align="center">
                    <th>Steam ID</th>
                    <th>Date Joined</th>
                </tr>';
                    foreach ($gifter_users as $key => $value) {
                        echo '<tr align="center">
                    <td><a href="http://steamcommunity.com/profiles/' . $value['steam_id'] . '" target="_new">' . $value['steam_id'] . '</a></td>
                    <td>' . $value['date_invited'] . '</td>
                </tr>';
                    }
                    echo '</table>';
                } else {
                    echo 'No users able to create invites yet.<br />';
                }
            } else {
                echo 'Your steam_id is not in the admin group or your steam login session has expired';
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