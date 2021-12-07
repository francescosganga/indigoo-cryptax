<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use CrypTax\Controllers\MainController;
use CrypTax\Controllers\WebAppController;
use CrypTax\Exceptions\BaseException;
use CrypTax\Exceptions\InvalidTransactionException;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Cookies');
header('Access-Control-Allow-Credentials: true');

$data = file_get_contents("php://input");

set_time_limit(0);

try {
    MainController::run($data);
} catch (BaseException $e) {
    header('Content-type: application/json');
    echo json_encode($e->toJson(), JSON_PRETTY_PRINT);
}