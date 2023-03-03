<?php

namespace YusamHub\Daemon;

use YusamHub\Daemon\Interfaces\DaemonJobInterface;

class Daemon
{
    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 1;
    const EXIT_MEMORY_LIMIT = 12;

    /**
     * @var bool
     */
    protected bool $daemonStop = false;

    /**
     * @var bool
     */
    protected bool $shouldQuit = false;

    /**
     * @var bool
     */
    protected bool $paused = false;

    /**
     * @var DaemonConsole
     */
    protected DaemonConsole $daemonConsole;

    /**
     * @var bool
     */
    protected bool $isLoop;

    /**
     * @var int
     */
    protected int $daemonStart;

    /**
     * @var bool
     */
    protected bool $isRunning = false;

    /**
     * @param DaemonConsole $daemonConsole
     * @param bool $isLoop
     */
    public function __construct(DaemonConsole $daemonConsole, bool $isLoop)
    {
        $this->daemonConsole = $daemonConsole;
        $this->isLoop = $isLoop;
        $this->daemonStart = time();
    }

    /**
     * @return void
     */
    public function daemonStop()
    {
        $this->daemonStop = true;
    }

    /**
     * @return int
     */
    public function upTime(): int
    {
        return time() - $this->daemonStart;
    }

    /**
     * @return bool
     */
    protected function isDisabled(): bool
    {
        return false;
    }

    /**
     * @param DaemonOptions $options
     * @return int
     */
    public function run(DaemonOptions $options): int
    {
        if ($this->isRunning) return 0;
        $this->isRunning = true;

        $this->daemonConsole->consoleInfo(sprintf("[%s] Is started at [%s]", get_class($this), date("Y-m-d H:i:s")));

        $this->daemonConsole->consoleInfo(sprintf("[%s] Options [%s]", get_class($this), json_encode((array) $options, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)));

        if ($this->isDisabled()) {
            $this->daemonConsole->consoleInfo(sprintf("[%s] Cannot start while disabled=true", get_class($this)));
            return $this->stop(static::EXIT_ERROR);
        }

        if ($supportsAsyncSignals = $this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        while (true) {

            if (! $this->shouldRun($options)) {

                $status = $this->pause($options);

                if (!is_null($status)) {
                    return $this->stop($status);
                }

                continue;
            }

            $job = $this->getNextJob();

            if ($supportsAsyncSignals) {
                $this->registerTimeoutHandler($job, $options);
            }

            if ($job) {

                $this->runJob($job, $options);

                if ($options->rest > 0) {
                    $this->sleep($options->rest);
                }

            } else {

                $this->sleep($options->sleep);

            }

            if ($supportsAsyncSignals) {
                $this->resetTimeoutHandler();
            }

            $status = $this->stopIfNecessary($options);

            if (!is_null($status)) {
                return $this->stop($status);
            }

            if (!$this->isLoop) {
                return $this->stop(self::EXIT_SUCCESS);
            }
        }
    }

    /**
     * @param DaemonOptions $options
     * @return bool
     */
    private function shouldRun(DaemonOptions $options): bool
    {
        return !($this->paused);
    }

    /**
     * @param DaemonOptions $options
     * @return int|null
     */
    private function pause(DaemonOptions $options): ?int
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);

        return $this->stopIfNecessary($options);
    }

    /**
     * @param DaemonOptions $options
     * @return int|null
     */
    private function stopIfNecessary(DaemonOptions $options): ?int
    {
        if ($this->shouldQuit) {
            $this->daemonConsole->consoleInfo(sprintf("[%s] Should quit", get_class($this)));
            return static::EXIT_SUCCESS;
        } elseif ($this->memoryLimit($options->memoryMb)) {
            $this->daemonConsole->consoleError(sprintf("[%s] Memory limit", get_class($this)));
            return static::EXIT_MEMORY_LIMIT;
        } elseif ($this->isDisabled()) {
            $this->daemonConsole->consoleError(sprintf("[%s] Stop while disabled=true", get_class($this)));
            return static::EXIT_ERROR;
        } elseif ($this->daemonStop) {
            $this->daemonConsole->consoleInfo(sprintf("[%s] Daemon stop", get_class($this)));
            return static::EXIT_SUCCESS;
        }

        return null;
    }

    /**
     * @return DaemonJobInterface|null
     */
    protected function getNextJob(): ?DaemonJobInterface
    {
        return new DaemonJob();
    }

    /**
     * @param DaemonJobInterface $job
     * @param DaemonOptions $options
     * @return void
     */
    private function runJob(DaemonJobInterface $job, DaemonOptions $options)
    {
        try {

            $this->process($job, $options);

        } catch (\Throwable $e) {

            $this->daemonConsole->exceptionReport($e);

            $this->stopDaemonIfLostConnection($e);
        }
    }

    /**
     * @param \Throwable $e
     * @return void
     */
    private function stopDaemonIfLostConnection(\Throwable $e): void
    {
        if ($this->stopDaemonIfLostConnectionThrowable($e)) {
            $this->shouldQuit = true;
        }
    }

    /**
     * @param DaemonJobInterface $job
     * @param DaemonOptions $options
     * @return void
     * @throws \Throwable
     */
    private function process(DaemonJobInterface $job, DaemonOptions $options)
    {
        $processStart = microtime(true);

        $processId = md5(microtime() . serialize($job));

        try {

            $this->daemonConsole->consoleInfo(sprintf("[%s] Process [%s], Memory usage [%.2F Mb]", get_class($this), $processId, $this->getMemoryUsageForPhp()));

            $job->handle($this);

            $processEnd = microtime(true);

            $this->daemonConsole->consoleInfo(sprintf("[%s] Process [%s] -> %s (%.6F seconds), Memory usage [%.2F Mb]", get_class($this), $processId, "SUCCESS", $processEnd - $processStart, $this->getMemoryUsageForPhp()));

        } catch (\Throwable $e) {

            $processEnd = microtime(true);

            $this->daemonConsole->consoleInfo(sprintf("[%s] Process [%s] -> %s (%.6F seconds), Memory usage [%.2F Mb]", get_class($this), $processId, "FAIL", $processEnd - $processStart, $this->getMemoryUsageForPhp()));

            $this->handleJobException($processId, $job, $options, $e);

        }
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
        throw $e;
    }

    /**
     * @return void
     */
    private function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->daemonConsole->consoleInfo(sprintf("[%s] Received signal SIGTERM (15) for should quit", get_class($this)));
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
            $this->daemonConsole->consoleInfo(sprintf("[%s] Received signal SIGUSR2 (12) for pause", get_class($this)));
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
            $this->daemonConsole->consoleInfo(sprintf("[%s] Received signal SIGCONT (18) for continue", get_class($this)));
        });
    }

    /**
     * @param DaemonJobInterface|null $job
     * @param DaemonOptions $options
     * @return void
     */
    private function registerTimeoutHandler(?DaemonJobInterface $job, DaemonOptions $options)
    {
        pcntl_signal(SIGALRM, function () use ($job, $options) {
            $this->daemonConsole->consoleError(sprintf("[%s] Received signal SIGALRM (14) for killed", get_class($this)));
            $this->kill(static::EXIT_ERROR);
        });

        pcntl_alarm(
            max($options->timeout, 0)
        );
    }

    /**
     * @return void
     */
    private function resetTimeoutHandler()
    {
        pcntl_alarm(0);
    }

    /**
     * @return bool
     */
    private function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * @return float
     */
    protected function getMemoryUsageForPhp(): float
    {
        return memory_get_usage(true) / 1024 / 1024;
    }

    /**
     * @param int $memoryLimit
     * @return bool
     */
    private function memoryLimit(int $memoryLimit): bool
    {
        return $this->getMemoryUsageForPhp() >= $memoryLimit;
    }

    /**
     * @param int $status
     * @return int
     */
    private function stop(int $status = 0): int
    {
        if ($status === 0) {
            $this->daemonConsole->consoleInfo(sprintf("[%s] Stopped with status [%d]", get_class($this), $status));
        } else {
            $this->daemonConsole->consoleError(sprintf("[%s] Stopped with status [%d]", get_class($this), $status));
        }
        return $status;
    }

    /**
     * @param int $status
     * @return void
     */
    private function kill(int $status = 0): void
    {
        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * @param int|float $seconds
     * @return void
     */
    private function sleep($seconds): void
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }


    /**
     * @param \Throwable $e
     * @return bool
     */
    public function stopDaemonIfLostConnectionThrowable(\Throwable $e): bool
    {
        $message = $e->getMessage();

        $needles = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
            'Transaction() on null',
            'child connection forced to terminate due to client_idle_limit',
            'query_wait_timeout',
            'reset by peer',
            'Physical connection is not usable',
            'TCP Provider: Error code 0x68',
            'ORA-03114',
            'Packets out of order. Expected',
            'Adaptive Server connection failed',
            'Communication link failure',
            'connection is no longer usable',
            'Login timeout expired',
            'SQLSTATE[HY000] [2002] Connection refused',
            'running with the --read-only option so it cannot execute this statement',
            'The connection is broken and recovery is not possible. The connection is marked by the client driver as unrecoverable. No attempt was made to restore the connection.',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Try again',
            'SQLSTATE[HY000] [2002] php_network_getaddresses: getaddrinfo failed: Name or service not known',
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: EOF detected',
            'SQLSTATE[HY000] [2002] Connection timed out',
            'SSL: Connection timed out',
            'SQLSTATE[HY000]: General error: 1105 The last transaction was aborted due to Seamless Scaling. Please retry.',
            'Temporary failure in name resolution',
            'SSL: Broken pipe',
            'SQLSTATE[08S01]: Communication link failure',
            'SQLSTATE[08006] [7] could not connect to server: Connection refused Is the server running on host',
            'SQLSTATE[HY000]: General error: 7 SSL SYSCALL error: No route to host',
            'The client was disconnected by the server because of inactivity. See wait_timeout and interactive_timeout for configuring this behavior.',
            'SQLSTATE[08006] [7] could not translate host name',
            'TCP Provider: Error code 0x274C',
        ];

        foreach($needles as $needle) {
            if (stristr($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}