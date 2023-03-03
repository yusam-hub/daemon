<?php

namespace YusamHub\Daemon;

use YusamHub\Daemon\Interfaces\DaemonJobInterface;

class DaemonQueue extends Daemon
{
    /**
     * @var string
     */
    protected string $queue;

    /**
     * @param DaemonConsole $daemonConsole
     * @param bool $isLoop
     * @param string $queue
     */
    public function __construct(DaemonConsole $daemonConsole, bool $isLoop, string $queue)
    {
        $this->queue = $queue;
        parent::__construct($daemonConsole, $isLoop);
        $this->daemonConsole->consoleInfo(sprintf("[%s] Create with params (queue = %s)", get_class($this), $this->queue));
    }

    /**
     * @return DaemonJobInterface|null
     */
    protected function getNextJob(): ?DaemonJobInterface
    {
        /**
         * Override this method to load job from queue
         */
        return parent::getNextJob();
    }

    /**
     * @param string $processId
     * @param DaemonJobInterface $job
     * @param DaemonOptions $options
     * @param \Throwable $e
     * @return void
     * @throws \Throwable
     */
    protected function handleJobException(string $processId, DaemonJobInterface $job, DaemonOptions $options, \Throwable $e): void
    {
        /**
         * Override this method to add back to queue if was exceptions or another queue for save
         */
        parent::handleJobException($processId, $job, $options, $e); // TODO: Change the autogenerated stub
    }
}