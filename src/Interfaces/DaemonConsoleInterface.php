<?php

namespace YusamHub\Daemon\Interfaces;

interface DaemonConsoleInterface
{
    /**
     * @param string $message
     * @return void
     */
    public function consoleInfo(string $message): void;

    /**
     * @param string $message
     * @return void
     */
    public function consoleError(string $message): void;

    /**
     * @param \Throwable $e
     * @return void
     */
    public function exceptionReport(\Throwable $e): void;
}