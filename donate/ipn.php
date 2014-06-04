<?php

$test = json_encode($_POST);

$myFile = "./pings/ipn".time().".txt";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $test);
fclose($fh);

require_once('../functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
    if ($db) {

        $steam_id = $_POST['custom'];
        $donation = $_POST['payment_gross'];
        $donation_fee = $_POST['payment_fee'];
        $donation_email = $_POST['payer_email'];
        $donation_txn_id = $_POST['txn_id'];
        $donation_ipn_id = $_POST['ipn_track_id'];


        $updateSQL = $db->q("INSERT INTO `invite_key` (`steam_id`, `permament`, `donated`, `donation`, `donation_fee`, `donation_email`, `donation_txn_id`, `donation_ipn_id`) VALUES (?, 1, 1, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `donated` = VALUES(`donated`), `permament` = VALUES(`permament`), `donation` = VALUES(`donation`), `donation_fee` = VALUES(`donation_fee`), `donation_email` = VALUES(`donation_email`), `donation_txn_id` = VALUES(`donation_txn_id`), `donation_ipn_id` = VALUES(`donation_ipn_id`);",
            'iddsss',
            $steam_id, $donation, $donation_fee, $donation_email, $donation_txn_id, $donation_ipn_id);
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}