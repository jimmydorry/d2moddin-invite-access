<?php

$test = '';

$test .= json_encode($_POST);

$myFile = "./pings/test".time().".txt";
$fh = fopen($myFile, 'w') or die("can't open file");
fwrite($fh, $test);
fclose($fh);
?>