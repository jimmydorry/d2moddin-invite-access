<?php

$receiver_email = 'kidovate@gmail.com';
$log_file = "./pings/ipn" . time() . ".txt";
$log = '';

// tell PHP to log errors to ipn_errors.log in this directory
ini_set('log_errors', true);
ini_set('error_log', dirname(__FILE__) . '/pings/ipn_errors.log');

// intantiate the IPN listener
include('ipnlistener.php');
$listener = new IpnListener();

// tell the IPN listener to use the PayPal test sandbox
$listener->use_sandbox = false;

// try to process the IPN POST
try {
    $listener->requirePostMethod();
    $verified = $listener->processIpn();
} catch (Exception $e) {
    error_log($e->getMessage());
    exit(0);
}

if ($verified) {

    $errmsg = ''; // stores errors from fraud checks

    // 1. Make sure the payment status is "Completed"
    if ($_POST['payment_status'] != 'Completed') {
        // simply ignore any IPN that is not completed
        exit(0);
    }

    // 2. Make sure seller email matches your primary account email.
    if ($_POST['receiver_email'] != $receiver_email) {
        $errmsg .= "'receiver_email' does not match: ";
        $errmsg .= $_POST['receiver_email'] . "\n";
    }

    if (!empty($errmsg)) {

        // manually investigate errors from the fraud checking
        $log .= "IPN failed fraud checks: \n$errmsg\n\n";
        $log .= $listener->getTextReport();
    } else {
        //$log .= "Success!!!\n";

        require_once('../functions.php');
        require_once('../connections/parameters.php');

        try {
            $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
            if ($db) {

                isset($_POST['custom']) && !empty($_POST["custom"]) && is_numeric($_POST["custom"])
                    ? $steam_id = $_POST["custom"]
                    : $steam_id = 0;
                isset($_POST['payment_gross']) && !empty($_POST["payment_gross"]) && is_numeric($_POST["payment_gross"])
                    ? $donation = $_POST["payment_gross"]
                    : $donation = 0;
                isset($_POST['payment_fee']) && !empty($_POST["payment_fee"]) && is_numeric($_POST["payment_fee"])
                    ? $donation_fee = $_POST["payment_fee"]
                    : $donation_fee = 0.01;
                isset($_POST['payer_email']) && !empty($_POST["payer_email"])
                    ? $donation_email = $_POST["payer_email"]
                    : $donation_email = '';
                isset($_POST['txn_id']) && !empty($_POST["txn_id"])
                    ? $donation_txn_id = $_POST["txn_id"]
                    : $donation_txn_id = '';
                isset($_POST['ipn_track_id']) && !empty($_POST["ipn_track_id"])
                    ? $donation_ipn_id = $_POST["ipn_track_id"]
                    : $donation_ipn_id = '';

                //$steam_id = $_POST['custom'];
                //$donation = $_POST['payment_gross'];
                //$donation_fee = $_POST['payment_fee'];
                //$donation_email = $_POST['payer_email'];
                //$donation_txn_id = $_POST['txn_id'];
                //$donation_ipn_id = $_POST['ipn_track_id'];

                /*if($donation > 2){
                    $invited = 1;
                }
                else{
                    $invited = 0;
                }*/

                $updateSQL = $db->q("INSERT INTO `invite_key` (`steam_id`, `donated`, `invited`, `donation`, `donation_fee`, `donation_email`, `donation_txn_id`, `donation_ipn_id`) VALUES (?, 1, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `donated` = VALUES(`donated`), `invited` = VALUES(`invited`), `donation` = VALUES(`donation`), `donation_fee` = VALUES(`donation_fee`), `donation_email` = VALUES(`donation_email`), `donation_txn_id` = VALUES(`donation_txn_id`), `donation_ipn_id` = VALUES(`donation_ipn_id`);",
                    'iiddsss',
                    $steam_id, $invited, $donation, $donation_fee, $donation_email, $donation_txn_id, $donation_ipn_id);
            } else {
                echo 'No DB';
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

} else {
    // manually investigate the invalid IPN
    $log .= "Failure, not verified!!!\n";
    $log .= $listener->getTextReport();
}

if (!empty($log)) {
    $fh = fopen($log_file, 'w') or die("can't open file");
    fwrite($fh, $log);
    fclose($fh);
}