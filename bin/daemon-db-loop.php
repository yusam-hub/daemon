<?php

require_once(__DIR__ . "/../vendor/autoload.php");

$daemon = new \YusamHub\Daemon\DaemonDb(
    new \YusamHub\Daemon\DaemonConsole(),
    true,
    0,
    4
);
exit($daemon->run(new \YusamHub\Daemon\DaemonOptions(['rest' => 1])));
