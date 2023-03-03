<?php

require_once(__DIR__ . "/../vendor/autoload.php");

$daemon = new \YusamHub\Daemon\DaemonQueue(
    new \YusamHub\Daemon\DaemonConsole(),
    true,
    'default',
);
exit($daemon->run(new \YusamHub\Daemon\DaemonOptions(['rest' => 1])));
