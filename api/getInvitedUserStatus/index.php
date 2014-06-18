<?php
require_once('../../functions.php');
require_once('../../connections/parameters.php');

$result = array();
$result['status'] = 0;
$result['error'] = '';

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
    if ($db) {
        $memcache = new Memcache;
        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

        !empty($_GET["user_id"]) && is_numeric($_GET["user_id"])? $user_id = $_GET["user_id"] : $account_id = null;
        !empty($_GET["key"]) && is_numeric($_GET["key"])? $api_key = $_GET["key"] : $api_key = null;

        if (!empty($user_id) && !empty($api_key)) {
            if ($api_key == $getdotastats_api_key_master) {
                $sql = "SELECT ik.`queue_id`, ik.`steam_id`, ik.`invited`, ik.`permament`, ik.`banned`, ik.`banned_reason`, ik.`donated`, ik.`donation`, ik.`donation_fee`, ik.`donation_email`, ik.`donation_txn_id`, ik.`donation_ipn_id`, ik.`date_invited`, ((SELECT COUNT(*) FROM invite_key ik2 WHERE ik2.queue_id < ik.queue_id AND ik2.invited = 0)+1) as true_queue_id
FROM `invite_key` ik WHERE ik.`steam_id` = " . $user_id . " LIMIT 0,1;";
                $d2moddin_user = simple_cached_query('d2moddin_user' . $user_id,
                    $sql,
                    10);
                $d2moddin_user =  $d2moddin_user[0];

                if (!empty($d2moddin_user)) {
                    if($d2moddin_user['invited'] == 1){
                        $result['status'] = 1;
                    }
                    else {
                        $result['error'] = 'User not invited';
                    }

                    !empty($d2moddin_user['banned'])
                        ? $result['banned'] = $d2moddin_user['banned']
                        : NULL;
                    !empty($d2moddin_user['banned_reason'])
                        ? $result['banned_reason'] = $d2moddin_user['banned_reason']
                        : NULL;
                    !empty($d2moddin_user['invited'])
                        ? $result['invited'] = $d2moddin_user['invited']
                        : NULL;
                    !empty($d2moddin_user['permament'])
                        ? $result['permament'] = $d2moddin_user['permament']
                        : NULL;
                    !empty($d2moddin_user['donated'])
                        ? $result['donated'] = $d2moddin_user['donated']
                        : NULL;
                    !empty($d2moddin_user['queue_id'])
                        ? $result['queue_id'] = $d2moddin_user['queue_id']
                        : NULL;
                    !empty($d2moddin_user['true_queue_id'])
                        ? $result['true_queue_id'] = $d2moddin_user['true_queue_id']
                        : NULL;
                    !empty($d2moddin_user['date_invited'])
                        ? $result['date_invited'] = $d2moddin_user['date_invited']
                        : NULL;
                }
                else {
                    $result['error'] = 'User does not exist';
                }
            }
            else{
                $result['error'] = 'Bad API key';
            }
        } else {
            $result['error'] = 'Required parameter missing';
        }

        $memcache->close();
    } else {
        $result['error'] = 'No DB';
    }
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result);