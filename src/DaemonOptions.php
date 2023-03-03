<?php

namespace YusamHub\Daemon;

class DaemonOptions
{
    /**
     * The maximum amount of RAM the worker may consume im MB.
     *
     * @var int
     */
    public int $memoryMb;

    /**
     * The number of seconds to rest between jobs.
     *
     * @var int|float
     */
    public $rest;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int|float
     */
    public $sleep;

    /**
     * The maximum number of seconds a handle job may run.
     *
     * @var int|float
     */
    public $timeout;

    /**
     * Extra custom data
     *
     * @var array
     */
    public array $extra;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->rest = $config['rest']??0;
        $this->sleep = $config['sleep']??3;
        $this->timeout = $config['timeout']??0;
        $this->memoryMb = $config['memoryMb']??512;
        $this->extra = $config['extra']??[];
    }
}