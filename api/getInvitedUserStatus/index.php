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
                $d2moddin_user = simple_cached_query('d2moddin_user' . $user_id,
                    "SELECT * FROM `invite_key` WHERE `steam_id` = " . $user_id . " AND `invited` = 1 LIMIT 0,1;",
                    10);

                if (!empty($d2moddin_user)) {
                    $result['status'] = 1;
                    $result['invited'] = $d2moddin_user['invited'];
                    $result['permament'] = $d2moddin_user['permament'];
                    $result['donated'] = $d2moddin_user['donated'];
                    $result['queue_id'] = $d2moddin_user['queue_id'];
                    $result['date_invited'] = $d2moddin_user['date_invited'];
                } else {
                    $result['error'] = 'User not invited';
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