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
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1 AND `permament` = 0 AND `donated` = 0) as total_normal_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `donated` = 1) as total_donated_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `donated` = 1 AND `invited` = 1) as total_donated_users_invited,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `permament` = 1) as total_permament_users,
                                    (SELECT COUNT(*) FROM `invite_key` WHERE `permament` = 1 AND `invited` = 1) as total_permament_users_invited
                                ;";

            $site_stats = $db->q($site_stats_sql);
            $site_stats = $site_stats[0];


            if (isset($_POST['steamidInvite']) && !empty($_POST['steamidInvite'])) {
                $steam_id = $_POST['steamidInvite'];
                isset($_POST['donation']) && !empty($_POST["donation"]) && is_numeric($_POST["donation"]) ? $donation = $_POST["donation"] : $donation = NULL;
                isset($_POST['donation_fee']) && !empty($_POST["donation_fee"]) && is_numeric($_POST["donation_fee"]) ? $donation_fee = $_POST["donation_fee"] : $donation_fee = NULL;
                isset($_POST['donation_email']) && !empty($_POST["donation_email"]) ? $donation_email = $_POST["donation_email"] : $donation_email = NULL;
                isset($_POST['donation_txn_id']) && !empty($_POST["donation_txn_id"]) ? $donation_txn_id = $_POST["donation_txn_id"] : $donation_txn_id = NULL;

                    $updateSQL = $db->q("INSERT INTO `invite_key` (`steam_id`, `invited`, `permament`, `donated`, `donation`, `donation_fee`, `donation_email`, `donation_txn_id`) VALUES (?, 1, 1, 1, ?, ?, ?) ON DUPLICATE KEY UPDATE `invited` = VALUES(`invited`), `permament` = VALUES(`permament`), `donated` = VALUES(`donated`), `donation` = VALUES(`donation`), `donation_fee` = VALUES(`donation_fee`), `donation_email` = VALUES(`donation_email`), `donation_txn_id` = VALUES(`donation_txn_id`);",
                        'iddss',
                        $steam_id, $donation, $donation_fee, $donation_email, $donation_txn_id);

                echo '<strong>Specified user '.$steam_id.' has been marked as a donator!</strong><br /><br />';
            }

            $site_stats = $db->q($site_stats_sql);
            $site_stats = $site_stats[0];


            echo number_format($site_stats['total_users']) . ' total users in queue<br />';
            echo number_format($site_stats['total_users_invited']) . ' users invited (no other flags: ' . $site_stats['total_normal_users_invited'] . ')<br />';
            echo number_format($site_stats['total_permament_users']) . ' users with the permament flag (invited: ' . $site_stats['total_permament_users_invited'] . ')<br />';
            echo number_format($site_stats['total_donated_users']) . ' users with the donator flag (invited: ' . $site_stats['total_donated_users_invited'] . ')<br />';

            ?>

            <form method = "post" action = "./add_donators.php?key=<?= $_GET['key'] ?>">
            <table border="1">
                <tr>
                    <th>64bit Steam ID</th>
                    <td><input name="steamidInvite" type="text" value="">
                    </td>
                </tr>
                <tr>
                    <th>Donation</th>
                    <td><input name="donation" type="text" value="">
                    </td>
                </tr>
                <tr>
                    <th>Donation Fee</th>
                    <td><input name="donation_fee" type="text" value="0.01">
                    </td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><input name="donation_email" type="text" value="">
                    </td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td><input name="donation_txn_id" type="text" value="">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center"><input type="submit" value="Modify"></td>
                </tr>
            </table>
            </form >

        <?php
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