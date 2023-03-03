<?php

require_once(__DIR__ . "/../vendor/autoload.php");

$daemon = new \YusamHub\Daemon\Daemon(
    new \YusamHub\Daemon\DaemonConsole(),
    false
);
exit($daemon->run(new \YusamHub\Daemon\DaemonOptions()));
