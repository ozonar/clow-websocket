<?php


$currentMachineConfig = @include '../../../config/currentMachine.php';

if ($currentMachineConfig) {
    $ip = $currentMachineConfig['ip'];
} else {
    $ip = $_SERVER['SERVER_ADDR'];
}

$ip = '127.0.0.1:9090';

echo json_encode($ip);