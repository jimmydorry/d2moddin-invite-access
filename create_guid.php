<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <?php
    require_once('./functions.php');
    require_once('./connections/parameters.php');

    if (!isset($_SESSION)) {
        session_start();
    }

    $steamid64 = '';
    if (!empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        $steamid64 = $_SESSION['user_id'];
    }

    //$steamid64 = '76561197989020883'; /////////////////////////////////////////////////////////////////////////////////////////TEMP HACK

    if (empty($steamid64)) {
        echo 'Not logged in. Login via <a href="./">homepage</a>.';
        exit();
    }

    $user_details = !empty($_SESSION['user_details'])
        ? $_SESSION['user_details']
        : NULL;
    ?>
</head>

<body>
<?php
function guid()
{
    if (function_exists('com_create_guid')) {
        return com_create_guid();
    } else {
        mt_srand((double)microtime() * 10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        $uuid =
            //chr(123) . // "{"
            substr($charid, 0, 8) . $hyphen
            . substr($charid, 8, 4) . $hyphen
            . substr($charid, 12, 4) . $hyphen
            . substr($charid, 16, 4) . $hyphen
            . substr($charid, 20, 12);
        //. chr(125); // "}"
        return $uuid;
    }
}

try {
    $db = new dbWrapper($hostname, $username, $password, $database, $port, false, 'utf8');
    if ($db) {
        $d2moddin_gifter = $db->q("SELECT * FROM `invite_key` WHERE `steam_id` = ? AND `gifter` = 1 LIMIT 0,1;",
            'i',
            $steamid64);

        if (!empty($d2moddin_gifter)) {
            $num_unactivated_codes = $db->q("SELECT COUNT(*) as count_unactivated FROM `invite_codes` WHERE `sender` = ? AND `activated` = 0 LIMIT 0,1;",
                'i',
                $steamid64);
            $num_unactivated_codes = $num_unactivated_codes[0]['count_unactivated'];

            $max_unactivated = 100;

            if (isset($_POST['numCodes']) && !empty($_POST['numCodes']) && is_numeric($_POST['numCodes'])) {
                $numCodes = $_POST['numCodes'];
                if (($num_unactivated_codes + $numCodes) <= $max_unactivated) {
                    $codes = array();
                    for ($i = 0; $i < $numCodes; $i++) {
                        $codes[] = guid();
                    }

                    $sql = 'INSERT INTO `invite_codes` (`token`, `sender`) VALUES ';
                    $i = 0;
                    foreach ($codes as $key => $value) {
                        if ($i > 0) $sql .= ', ';

                        $sql .= '(\'' . $value . '\', ' . $steamid64 . ')';
                        $i++;
                    }
                    unset($i);

                    //echo $sql . '<br />';

                    $updateSQL = $db->q($sql);
                    if ($updateSQL) {
                        echo '<strong>Codes generated!</strong><br />';
                    } else {
                        echo '<strong>Failed to generate codes!</strong><br />';
                    }
                } else {
                    echo 'You have too many unactivated keys!';
                }
            }
            ?>

            <h2>Generate Codes</h2>
            <p>Maximum of 100 un-activated codes at a time. You can generate
                another <?= ($max_unactivated - $num_unactivated_codes) ?> invites.</p>
            <?php
            if (($max_unactivated - $num_unactivated_codes) > 0) {
                ?>
                <form method="post" action="">
                    <table border="1">
                        <tr>
                            <th align="left">Number to Generate</th>
                            <td><input type="number" name="numCodes" min="1"
                                       max="<?= ($max_unactivated - $num_unactivated_codes) ?>">
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" align="center"><input name="submit" type="submit" value="Generate"></td>
                        </tr>
                    </table>
                </form>

            <?php
            }
            $my_codes = $db->q("SELECT * FROM `invite_codes` WHERE `sender` = ?;",
                'i',
                $steamid64);

            $table = '';
            if (!empty($my_codes)) {
                $table .= '<table border="1">';
                $table .= '<tr align="center">
                <th>&nbsp;</th>
                <th>Code</th>
                <th>Redeemed?</th>
                <th>Created</th>
                <th>Activated</th>
            </tr>';

                $codes = array();
                foreach ($my_codes as $key => $value) {
                    if ($value['activated']) {
                        $receiver_id = !empty($value['receiver_nick'])
                            ? $value['receiver_nick']
                            : $value['receiver'];
                        $activated = '<a href="http://steamcommunity.com/profiles/' . $value['receiver'] . '" target="_new">' . $receiver_id . '</a>';
                    } else {
                        $codes[] = $value['token'];
                        $activated = 'no';
                    }

                    if ($value['date_activated']) {
                        $dateActivated = relative_time($value['date_activated']);
                    } else {
                        $dateActivated = 'N/A';
                    }

                    $table .= '<tr align="center">
                <td>' . ($key + 1) . '</td>
                <td align="left">' . $value['token'] . '</td>
                <td>' . $activated . '</td>
                <td>' . relative_time($value['date_created']) . '</td>
                <td>' . $dateActivated . '</td>
            </tr>';
                }
                $table .= '</table>';
            } else {
                $table = 'No codes generated yet.<br />';
            }

            echo '<h2>Un-Activated Codes</h2>';
            if (!empty($codes)) {
                $codes = implode("\n", $codes);
                echo '<textarea rows="10" cols="40" type="text">' . $codes . '</textarea>';
            } else {
                echo 'No un-activated codes.<br />';
            }

            echo '<h2>My Codes</h2>';
            echo $table;
        } else {
            echo 'You do not have permission to generate activation codes.';
        }
    } else {
        echo 'No DB';
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
</body>
</html>
