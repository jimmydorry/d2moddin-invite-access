<?php
require_once('../functions.php');
require_once('../connections/parameters.php');

try {
    $db = new dbWrapper($hostname, $username, $password, $database, false);
    if ($db) {

    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>