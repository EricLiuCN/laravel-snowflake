<?php

namespace Ericliucn\LaravelSnowflake\Server;

class RedisCountServer implements CountServerInterFace
{
    private $redis = null;
    //初始化
    public function __construct($config)
    {
        $this->redis = new \Redis();
        if (!isset($config['host']) || !isset($config['port'])) {
            throw new \Exception('invalid redis config');
        }
        $this->redis->connect($config['host'], $config['port']);
        if (isset($config['database'])) {
            $this->redis->select($config['database']);
        }
        if (isset($config['password'])) {
            $this->redis->auth($config['password']);
        }
        return $this;
    }

    public function getSequenceId($key)
    {
        $sequenceId = $this->redis->incr($key) - 1;
        $this->redis->expire($key, 5);
        return $sequenceId;
    }
}