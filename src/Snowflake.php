<?php

namespace Ericliucn\LaravelSnowflake;

use Ericliucn\LaravelSnowflake\Server\CountServerInterFace;
use Ericliucn\LaravelSnowflake\Server\FileCountServer;
use Ericliucn\LaravelSnowflake\Server\RedisCountServer;

/**
 * Snowflake算法生成unique id
 * @package Snowflake
 */
class Snowflake
{
    const TIMESTAMP_BITS      = 32;
    const DATA_CENTER_BITS    = 4;
    const MACHINE_ID_BITS     = 4;
    const SEQUENCE_BITS       = 13;

    protected $config = array(
        /**
         * 初始时间戳
         * @var number
         */
        'start_time' => 0,
        /**
         * 数据中心ID 【0~31】
         * @var number
         */
        'dataCenter_id' => 0,

        /**
         * 机器ID 【0~31】
         * @var number
         */
        'machine_id' => 0,

        /**
         * 是否启用redis锁 false使用 文件锁
         * @var bool
         */
        'redis_lock' => false,

        /**
         * redis配置信息
         * @var array
         */
        'redis_config' => array(
            'host'     => '127.0.0.1',
            'port'     => '6379',
            'database' => '0',
            'password' => '',
        )
    );

    private static $startTime;
    private static $idWorker;
    protected $dataCenterId;
    protected $machineId;
    protected $lastTimestamp       = null;
    private $countService          = null;

    protected $sequence            = 1;
    protected $signLeftShift       = self::TIMESTAMP_BITS + self::DATA_CENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $timestampLeftShift  = self::DATA_CENTER_BITS + self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $dataCenterLeftShift = self::MACHINE_ID_BITS + self::SEQUENCE_BITS;
    protected $machineLeftShift    = self::SEQUENCE_BITS;
    protected $maxSequenceId       = -1 ^ (-1 << self::SEQUENCE_BITS);
    protected $maxMachineId        = -1 ^ (-1 << self::MACHINE_ID_BITS);
    protected $maxDataCenterId     = -1 ^ (-1 << self::DATA_CENTER_BITS);

    //初始化
    public function __construct()
    {
        $config = config('snowflake.config', array());
        $this->config = array_merge($this->config, $config);
        self::$startTime = $this->config['start_time'];
        $this->dataCenterId = $this->config['dataCenter_id'];
        if ($this->dataCenterId > $this->maxDataCenterId) {
            throw new \Exception('data center id should between 0 and ' . $this->maxDataCenterId);
        }

        $this->machineId    = $this->config['machine_id'];
        if ($this->machineId > $this->maxMachineId) {
            throw new \Exception('machine id should between 0 and ' . $this->maxMachineId);
        }
        if (isset($this->config['redis_lock']) && $this->config['redis_lock']) {
            $redisConfig = $this->config['redis_config'];
            $this->countService = new RedisCountServer($redisConfig);
        } else {
            $this->countService = new FileCountServer();
        }
    }

    //获取ID
    public function nextId()
    {
        $sign = 0;
        $timestamp = $this->getUnixTimestamp();
        if ($timestamp < $this->lastTimestamp) {
            throw new \Exception('Clock moved backwards!');
        }
        $countServiceKey = $this->dataCenterId . '-' . $this->machineId . '-' . $timestamp;
        $sequence = $this->countService->getSequenceId($countServiceKey);
        if ($sequence > $this->maxSequenceId) {
            $timestamp = $this->getUnixTimestamp();
            while ($timestamp <= $this->lastTimestamp) {
                $timestamp = $this->getUnixTimestamp();
            }
            $countServiceKey = $this->dataCenterId . '-' . $this->machineId . '-' . $timestamp;
            $sequence = $this->countService->getSequenceId($countServiceKey);
        }
        $this->lastTimestamp = $timestamp;
        $time = (int)($timestamp - self::$startTime);
        $id = ($sign << $this->signLeftShift) | ($time << $this->timestampLeftShift) | ($this->dataCenterId << $this->dataCenterLeftShift) | ($this->machineId << $this->machineLeftShift) | $sequence;
        return (string)$id;
    }

    //解析ID
    public static function parse($uuid)
    {
        $binUuid = decbin($uuid);
        $len     = strlen($binUuid);
        $sequenceStart = $len - self::SEQUENCE_BITS;
        $sequence      = substr($binUuid, $sequenceStart, self::SEQUENCE_BITS);

        $machineIdStart = $len - self::MACHINE_ID_BITS - self::SEQUENCE_BITS;
        $machineId      = substr($binUuid, $machineIdStart, self::MACHINE_ID_BITS);

        $dataCenterIdStart = $len - self::DATA_CENTER_BITS - self::MACHINE_ID_BITS - self::SEQUENCE_BITS;
        $dataCenterId      = substr($binUuid, $dataCenterIdStart, self::DATA_CENTER_BITS);

        $timestamp     = substr($binUuid, 0, $dataCenterIdStart);
        $realTimestamp = bindec($timestamp) + self::$startTime;
        $timestamp     = substr($realTimestamp, 0, -3);
        $microSecond   = substr($realTimestamp, -3);
        return [
            'timestamp'    => date('Y-m-d H:i:s', $realTimestamp),
            'dataCenterId' => bindec($dataCenterId),
            'machineId'    => bindec($machineId),
            'sequence'     => bindec($sequence),
        ];
    }

    // 取当前时间
    private function getUnixTimestamp()
    {
        return (float)sprintf("%.0f", microtime(true));
    }
}
