<?php

namespace YusamHub\Daemon;

use YusamHub\Daemon\Interfaces\DaemonJobInterface;

class DaemonJob implements DaemonJobInterface
{
    /**
     * @param Daemon $daemon
     * @return void
     */
    public function handle(Daemon $daemon): void
    {

    }
}