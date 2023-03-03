<?php

namespace YusamHub\Daemon\Interfaces;

use YusamHub\Daemon\Daemon;
interface DaemonJobInterface
{
    /**
     * @param Daemon $daemon
     * @return void
     */
    public function handle(Daemon $daemon): void;

}