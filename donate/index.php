<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=DCCQ3EWFT6HWS&lc=AU&item_name=d2moddin&currency_code=AUD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted&return=http%3A%2F%2Fd2modd.in%2Fdonate%2Fipn.php">Donate
    here</a>

<br/>

<?php

//$steam_id = $_SESSION['steam_id'];
$steam_id = 28755155;

$extra_url = '&return='.urlencode('http://d2modd.in/donate/thanks.php') .
                '&notify_url='.urlencode('http://d2modd.in/donate/ipn.php?sid='.$steam_id) .
                '&cancel_return='.urlencode('http://d2modd.in/donate/cancel.php') .
                '&rm=2';
    echo '<a href="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_donations&business=NU6P6N82DAHS8&lc=US&item_name=d2moddin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted' . $extra_url . '">Donate here</a>';
?>

