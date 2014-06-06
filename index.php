<?php
require_once('./functions.php');
require_once('./connections/parameters.php');

if (!isset($_SESSION)) {
    session_start();
}
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

                        <h2 class="col-md-offset-5 animated delay023 fadeInBottom">Sign up to get your slot in the
                            beta!</h2>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section id="dialog">
        <div class="container">
            <div class="animated delay030 fadeInBottom betaDialog">
                <?php
                try {
                    $db = new dbWrapper($hostname, $username, $password, $database, $port, false);
                    if ($db) {
                        $memcache = new Memcache;
                        $memcache->connect("localhost", 11211); # You might need to set "localhost" to "127.0.0.1"

                        $steamid64 = '';
                        if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
                            $steamid64 = $_SESSION['user_id'];
                        }

                        $user_details = !empty($_SESSION['user_details'])
                            ? $_SESSION['user_details']
                            : NULL;

                        $d2moddin_stats = simple_cached_query('d2moddin_stats',
                            "SELECT
                                (SELECT COUNT(*) FROM `invite_key`) as total_users,
                                (SELECT COUNT(*) FROM `invite_key` WHERE `invited` = 1) as total_users_invited
                            ;",
                            10);
                        $d2moddin_stats = $d2moddin_stats[0];


                        if (isset($_GET['thanks'])) {
                            echo '<div class="text-center">';
                            echo '<h2>Thanks for donating!</h2>';
                            echo '<p>Your donation may take several minutes to be received. When it is received, your queue status will be updated.</p>';
                            echo '<p><a href="http://steamcommunity.com/groups/D2Moddin/discussions/4/">Errors and bugs can be reported in the donation forum</a></p>';
                            echo '<p><a href="http://d2modd.in">Reload page</a></p>';
                            echo '</div>';
                        } else if (empty($steamid64)) {
                            echo '<div class="text-center">';
                            echo '<p>To sign-up for your invite to D2Modd.in, login via steam.</p>';
                            echo '<p>After logging in, you will be entered into the queue for an invite.</p>';
                            echo '<p><a href="./auth/?login"><img src="./assets/images/steam_small.png" alt="Sign in with Steam"/></a></p>';
                            echo '</div>';
                        } else {
                            echo '<span class="h4">Logged in as:</span> ' . $user_details->personaname . '<br />';
                            //echo '<span class="h3">User ID:</span> ' . $steamid64 . '<br />';
                            echo '<p><a href="./auth/?logout">Click here to Logout</a></p><br />';

                            $d2moddin_user = simple_cached_query('d2moddin_user' . $steamid64,
                                "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                                30);
                            if (empty($d2moddin_user)) {
                                $d2moddin_user = $db->q(
                                    'INSERT INTO `invite_key` (`steam_id`) VALUES (?);',
                                    'i',
                                    $steamid64
                                );

                                $memcache->delete('d2moddin_user' . $steamid64);
                                $d2moddin_user = simple_cached_query('d2moddin_user' . $steamid64,
                                    "SELECT * FROM `invite_key` WHERE `steam_id` = " . $steamid64 . " LIMIT 0,1;",
                                    30);
                            }
                            $d2moddin_user = $d2moddin_user[0];

                            echo '<div class="text-center">';
                            echo '<a href="http://steamcommunity.com/profiles/' . $steamid64 . '" target="_new"><img src="' . $user_details->avatarmedium . '" /></a><br /><br />';

                            if ($d2moddin_user['invited']) {
                                echo '<h2>Invited: Yes</h2>';
                                echo '<p>You have received an invite!</p>';
                                echo '<p><a href="http://alpha.d2modd.in/" target="_new"><span class="h5">Login to D2Moddin via this link</span></a></p>';
                            } else {
                                echo '<h1>You are #' . number_format(max(1, $d2moddin_user['queue_id'] - $d2moddin_stats['total_users_invited'])) . ' in the queue</h1><br />';
                                echo '<h2>Invited: No</h2>';
                            }
                            echo '<p>Your original queue id was ' . number_format($d2moddin_user['queue_id']) . '</p>';

                            $donate_url = 'https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=BY9D59PJTKRA4&lc=US&item_name=D2Moddin&item_number=stream&currency_code=USD&bn=PP%2dDonationsBF%3apanel%2d51694185%2dimage%2dc3579668e9e7350a%2d320%2epng%3aNonHosted';
                            $donate_url .= '&return=' . urlencode('http://d2modd.in/?thanks') .
                                '&notify_url=' . urlencode('http://d2modd.in/donate/IPN_noperks.php') .
                                '&cancel_return=' . urlencode('http://d2modd.in/') .
                                '&rm=2' .
                                '&custom=' . $steamid64;

                            echo '<p><a href="' . $donate_url . '" target="_new"><span class="h5">Donations Accepted Here (no obligation)</span></a></p>';

                            echo '</div>';

                        }
                        echo '<hr />';
                        echo '<p>' . number_format($d2moddin_stats['total_users']) . ' users in queue.</p>';
//                        echo '<p>' . number_format($d2moddin_stats['total_users_invited']) . ' users in queue.</p>';

                        $memcache->close();
                    } else {
                        echo 'No DB';
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                ?>
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
