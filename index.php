<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = include __DIR__ . '/config/config.php';

use LotTransmitter\Logger;
use LotTransmitter\LotTransmitter;

Sentry\init($config['sentry']);

try {
    $lotTransmitter = new LotTransmitter($config);
    $lotTransmitter->transmitFiles();

    exit(0);
} catch (Throwable $t) {
    Sentry\captureException($t);

    Logger::error(
        'Unexpected error. Created sentry issue'
    );

    exit(1);
}