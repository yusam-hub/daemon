<?php

namespace YusamHub\Daemon;

use YusamHub\Daemon\Interfaces\DaemonJobInterface;

class DaemonDb extends Daemon
{
    /**
     * @var int
     */
    protected int $parallelIndex;
    /**
     * @var int
     */
    protected int $parallelCount;
    /**
     * @var array
     */
    protected array $fetchedJobs = [];

    /**
     * @param DaemonConsole $daemonConsole
     * @param bool $isLoop
     * @param int $parallelIndex
     * @param int $parallelCount
     */
    public function __construct(DaemonConsole $daemonConsole, bool $isLoop, int $parallelIndex, int $parallelCount)
    {
        $this->parallelIndex = $parallelIndex;
        $this->parallelCount = $parallelCount;
        parent::__construct($daemonConsole, $isLoop);
        $this->daemonConsole->consoleInfo(sprintf("[%s] Create with params (parallelIndex = %d , parallelCount = %d)", get_class($this), $this->parallelIndex, $this->parallelCount));
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getCondition(string $key): string
    {
        return $key . ' % ' . $this->parallelCount . ' = ' . $this->parallelIndex;
    }

    /**
     * @return array
     */
    protected function fetchJobs(): array
    {
        /**
         * THIS METHOD REQUIRE OVERRIDE
         */
        echo $this->getCondition('id') . PHP_EOL;
        try {
            $empty = random_int(0, 1);
        } catch (\Exception $e) {
            $empty = 0;
        }
        if ($empty) {
            $out = [];
            for ($i = 1; $i <= 10; $i++) {
                $out[] = new DaemonJob();
            }
            echo "GENERATED: " . count($out) . PHP_EOL;
            return $out;
        }
        sleep(1);
        return [];
    }

    /**
     * @return DaemonJobInterface|null
     */
    protected function getNextJob(): ?DaemonJobInterface
    {
        if (empty($this->fetchedJobs)) {
            $this->fetchedJobs = $this->fetchJobs();
        }
        $job = array_shift($this->fetchedJobs);
        if ($job instanceof DaemonJobInterface) {
            return $job;
        }
        return null;
    }
}