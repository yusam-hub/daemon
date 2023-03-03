<?php

namespace YusamHub\Daemon;

use YusamHub\Daemon\Interfaces\DaemonConsoleInterface;

class DaemonConsole implements DaemonConsoleInterface
{
    /**
     * @param string $message
     * @return void
     */
    public function consoleInfo(string $message): void
    {
        echo sprintf("INFO: %s", $message) . PHP_EOL;
    }

    /**
     * @param string $message
     * @return void
     */
    public function consoleError(string $message): void
    {
        echo sprintf("ERROR: %s", $message) . PHP_EOL;
    }

    /**
     * @param \Throwable $e
     * @return void
     */
    public function exceptionReport(\Throwable $e): void
    {
        $this->consoleError($e->getMessage() . ' ' . json_encode(
                [
                    'errorCode' => $e->getCode(),
                    'errorFileLine' => $e->getFile() . ":" . $e->getLine(),
                    'errorClass' => get_class($e),
                ]
                , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

}