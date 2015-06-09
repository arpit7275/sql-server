<?php
$final_output = "";
$star = 0;


require_once 'processor.php';

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$address = 'localhost';
$port = 6543;


$parse = "";
if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "socket create failed:  " . socket_strerror(socket_last_error()) . "\n";
}

if (socket_bind($sock, $address, $port) === false) {
    echo "socket bind failed: " . socket_strerror(socket_last_error($sock)) . "\n";
}

if (socket_listen($sock, 5) === false) {
    echo "socket listen failed: " . socket_strerror(socket_last_error($sock)) . "\n";
}

do {
    if (($msgsock = socket_accept($sock)) === false) {
        echo "socket accept failed: " . socket_strerror(socket_last_error($sock)) . "\n";
        break;
    }

  /* Server instructions. */
        $msg = "\nSQL Server is now live. \n" .
                    "To quit, type 'quit'. To shut down the server type 'shutdown'.\n";
        socket_write($msgsock, $msg, strlen($msg));


    do {
        if (false === ($sql = socket_read($msgsock, 2048, PHP_NORMAL_READ))) {
            echo "socket read failed: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            break 2;
        }
        if (!$sql = trim($sql)) {
            continue;
        }
        if ($sql == 'quit') {
            break;
        }
        if ($sql == 'shutdown') {
            socket_close($msgsock);
            break 2;
        }



        $sql = trim($sql);
        $length = strlen($sql);
        if($sql[$length-4] == "\\" && $sql[$length-3] == 'r' && $sql[$length-2] == "\\" && $sql[$length-1] == "n"){
            $parse .=$sql;
            continue;
        } else {
            $parse .=$sql;
        }
        $parse = str_replace('\r\n', " " , $parse);
        if(firstCheck($parse)){
            spli($parse);
            $parse = "";
        } else{
            $parse = "";
            $final_output = "syntax error\n";
        }
        socket_write($msgsock, $final_output, strlen($final_output));
    } while (true);
    socket_close($msgsock);
} while (true);

socket_close($sock);
?>
