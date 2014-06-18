<?php
require_once('./functions.php');
require_once('./connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}

$db = new dbWrapper($hostname, $username, $password, $database, $port, false);

$memcache = new Memcache;
$memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"


$steamid64 = '';
if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
    $steamid64 = $_SESSION['user_id'];
} else {
    header("Location: ./");
}

$user_details = !empty($_SESSION['user_details'])
    ? $_SESSION['user_details']
    : NULL;

?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>D2Moddin Alpha Access</title>

    <link href="./dist/css/bootstrap.css" rel="stylesheet">
    <link href="./dist/css/style.css" rel="stylesheet">
    <link href="./dist/css/fade.css" rel="stylesheet">
    <link href="//netdna.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="./dist/css/animate.css" rel="stylesheet">
    <link
        href='//fonts.googleapis.com/css?family=Source+Sans+Pro|Dosis|Abel|PT+Sans|Poiret+One|Ubuntu|Josefin+Sans|Titillium+Web|Open+Sans|Julius+Sans+One|Text+Me+One'
        rel='stylesheet' type='text/css'>
</head>

<body>
<div id="preloader"></div>
<div id="mash"></div>
<div id="maximage">
    <img src="./assets/images/l4zw0tO.png" alt=""/>
    <img src="./assets/images/pbc4xVZ.png" alt=""/>
</div>
<div id="contain">
    <section id="home">
        <div id="index">
            <div class="container">
                <div class="row">
                    <div class="head col-xs-12 col-sm-12 col-md-12 col-lg-12">
                        <h1 class="col-md-offset-5 animated delay020 fadeInBottom">CUSTOM GAMES</h1>

                        <?php
                        try {
                            if (!empty($steamid64)) {
                                $d2moddin_admins = simple_cached_query('d2moddin_admins' . $steamid64,
                                    "SELECT * FROM `admins` WHERE `steam_id` = $steamid64 LIMIT 0,1;",
                                    30);
                                $d2moddin_admins = $d2moddin_admins[0];

                                if (!empty($d2moddin_admins)) {
                                    echo '<h2 class="col-md-offset-5 animated delay023 fadeInBottom"><a class="active" target="_new" href="./admin/?key=' . $admin_pass_master . '">ADMIN PANEL⇚</a></h2>';
                                } else {
                                    echo '<h2 class="col-md-offset-5 animated delay023 fadeInBottom">Sign up to get your slot in the
                            beta!</h2>';
                                }

                                $d2moddin_gifters = simple_cached_query('d2moddin_gifters' . $steamid64,
                                    "SELECT * FROM `invite_key` WHERE `steam_id` = $steamid64 AND `gifter` = 1 LIMIT 0,1;",
                                    30);
                                $d2moddin_gifters = $d2moddin_gifters[0];

                                if (!empty($d2moddin_gifters)) {
                                    echo '<h2 class="col-md-offset-5 animated delay023 fadeInBottom"><a class="active" target="_new" href="./create_guid.php">Manage Invite Codes⇚</a> </h2>';
                                }

                                echo '<h2 class="col-md-offset-5 animated delay023 fadeInBottom"><a class="active" href="./apply_code.php">Use Invite Code⇚</a></h2>';
                            }
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }

                        ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="dialog">
        <div class="container">
            <div class="animated delay030 fadeInBottom betaDialog">
                <div class="text-center">
                    <?php
                    try {
                        $db = new dbWrapper($hostname, $username, $password, $database, $port, false, 'utf8');
                        if ($db) {
                            if (isset($_POST['codeAttempt']) && !empty($_POST['codeAttempt'])) {
                                $codeAttempt = $db->escape($_POST['codeAttempt']);

                                $isCodeActive = $db->q("SELECT * FROM `invite_codes` WHERE `token` = ? AND `activated` = 0 LIMIT 0,1;",
                                    's',
                                    $codeAttempt);

                                if (!empty($isCodeActive)) {
                                    $updateSQL2 = $db->q("UPDATE `invite_key` SET `invited` = 1, `permament` = 1 WHERE `steam_id` = ? ",
                                        'i',
                                        $steamid64);

                                    if ($updateSQL2) {
                                        echo '<strong>Queue position updated!</strong><br />';

                                        $persona_name = !empty($user_details->personaname)
                                            ? $user_details->personaname
                                            : NULL;
                                        $updateSQL1 = $db->q("UPDATE `invite_codes` SET `activated` = 1, `receiver` = ?, `receiver_nick` = ?, `date_activated` = NOW() WHERE `token` = ? ",
                                            'iss',
                                            $steamid64, $persona_name, $codeAttempt);

                                        if ($updateSQL1) {
                                            echo '<strong>Code redeemed!</strong><br />';
                                        } else {
                                            echo '<strong>Failed to redeem code!</strong><br />';
                                        }
                                    } else {
                                        echo '<strong>Queue position not updated!</strong><br />';
                                    }
                                } else {
                                    echo '<strong>Invalid code!</strong> This code is either invalid, or already used.<br />';
                                }
                            }
                            ?>

                            <br/>

                            <form method="post" action="" style="width:337px;margin: 0 auto;">
                                <table border="1">
                                    <tr>
                                        <th align="left">Invite Code</th>
                                        <td><input type="text"
                                                   name="codeAttempt"
                                                   value="<?= !empty($_POST['codeAttempt']) ? $db->escape($_POST['codeAttempt']) : 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX' ?>"/>

                                            <!--<textarea name="codeAttempt" rows="1" cols="40" type="text"></textarea>-->
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" align="center"><input name="submit" type="submit"
                                                                              value="Redeem"></td>
                                    </tr>
                                </table>
                            </form>

                        <?php
                        } else {
                            echo 'No DB';
                        }
                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }
                    ?>
                    <p><a href="./">Return to homepage</a></p>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0-beta2.min.js"></script>
<script type="text/javascript" src="./dist/js/sys/plugins.js"></script>
<script type="text/javascript" src="./dist/js/bootstrap.min.js"></script>
<script type="text/javascript" src="./dist/js/rAF.js"></script>
<script type="text/javascript" src="./dist/js/config-fade.js"></script>
<script type="text/javascript" src="./dist/js/theme.script.js"></script>
</body>
</html>
