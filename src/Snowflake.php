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
    /*const TIMESTAMP_BITS      = 32;
    const DATA_CENTER_BITS    = 4;
    const MACHINE_ID_BITS     = 4;
    const SEQUENCE_BITS       = 13;*/

    private static $config = array(
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
         * 是否短ID 【兼容js】
         * @var number
         */
        'short_id' => false,

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

    private static $timestamp_bits;
    private static $data_center_bits;
    private static $machine_id_bits;
    private static $sequence_bits;

    private static $startTime;
    private static $dataCenterId;
    private static $machineId;
    private static $lastTimestamp;
    private static $countService;
    
    private static $sequence = 1;

    private static $signLeftShift;
    private static $timestampLeftShift;
    private static $dataCenterLeftShift;
    private static $machineLeftShift;
    private static $maxSequenceId;
    private static $maxMachineId;
    private static $maxDataCenterId;

    //初始化
    public function __construct()
    {
        $config = config('snowflake.config', array());
        self::$config = array_merge(self::$config, $config);
        if(self::$config['short_id']){
            self::$timestamp_bits   = 32;
            self::$data_center_bits = 4;
            self::$machine_id_bits  = 4;
            self::$sequence_bits    = 13;
            self::$startTime        = self::$config['start_time'];
        }else{
            self::$timestamp_bits   = 41;
            self::$data_center_bits = 5;
            self::$machine_id_bits  = 5;
            self::$sequence_bits    = 12;
            self::$startTime        = self::$config['start_time'] * 1000;
        }
        self::$dataCenterId = self::$config['dataCenter_id'];
        if (self::$dataCenterId > self::$maxDataCenterId) {
            throw new \Exception('data center id should between 0 and ' . self::$maxDataCenterId);
        }

        self::$machineId = self::$config['machine_id'];
        if (self::$machineId > self::$maxMachineId) {
            throw new \Exception('machine id should between 0 and ' . self::$maxMachineId);
        }

        self::$signLeftShift       = self::$timestamp_bits + self::$data_center_bits + self::$machine_id_bits + self::$sequence_bits;
        self::$timestampLeftShift  = self::$data_center_bits + self::$machine_id_bits + self::$sequence_bits;
        self::$dataCenterLeftShift = self::$machine_id_bits + self::$sequence_bits;
        self::$machineLeftShift    = self::$sequence_bits;
        self::$maxSequenceId       = -1 ^ (-1 << self::$sequence_bits);
        self::$maxMachineId        = -1 ^ (-1 << self::$machine_id_bits);
        self::$maxDataCenterId     = -1 ^ (-1 << self::$data_center_bits);

        if (isset(self::$config['redis_lock']) && self::$config['redis_lock']) {
            $redisConfig = self::$config['redis_config'];
            self::$countService = new RedisCountServer($redisConfig);
        } else {
            self::$countService = new FileCountServer();
        }
    }

    //获取ID
    public function nextId()
    {
        $sign = 0;
        $timestamp = $this->getUnixTimestamp();
        if ($timestamp < self::$lastTimestamp) {
            throw new \Exception('Clock moved backwards!');
        }
        $countServiceKey = self::$dataCenterId . '-' . self::$machineId . '-' . $timestamp;
        $sequence = self::$countService->getSequenceId($countServiceKey);
        if ($sequence > self::$maxSequenceId) {
            $timestamp = $this->getUnixTimestamp();
            while ($timestamp <= self::$lastTimestamp) {
                $timestamp = $this->getUnixTimestamp();
            }
            $countServiceKey = self::$dataCenterId . '-' . self::$machineId . '-' . $timestamp;
            $sequence = self::$countService->getSequenceId($countServiceKey);
        }
        self::$lastTimestamp = $timestamp;
        $time = (int)($timestamp - self::$startTime);
        $id = ($sign << self::$signLeftShift) | ($time << self::$timestampLeftShift) | (self::$dataCenterId << self::$dataCenterLeftShift) | (self::$machineId << self::$machineLeftShift) | $sequence;
        return (string)$id;
    }

    //解析ID
    public static function parse($uuid)
    {
        $binUuid = decbin($uuid);
        $len     = strlen($binUuid);
        $sequenceStart = $len - self::$sequence_bits;
        $sequence      = substr($binUuid, $sequenceStart, self::$sequence_bits);

        $machineIdStart = $len - self::$machine_id_bits - self::$sequence_bits;
        $machineId      = substr($binUuid, $machineIdStart, self::$machine_id_bits);

        $dataCenterIdStart = $len - self::$data_center_bits - self::$machine_id_bits - self::$sequence_bits;
        $dataCenterId      = substr($binUuid, $dataCenterIdStart, self::$data_center_bits);

        $timestamp     = substr($binUuid, 0, $dataCenterIdStart);
        $realTimestamp = bindec($timestamp) + self::$startTime;
        if(self::$config['short_id']){
            return [
                'timestamp'    => date('Y-m-d H:i:s', $realTimestamp),
                'dataCenterId' => bindec($dataCenterId),
                'machineId'    => bindec($machineId),
                'sequence'     => bindec($sequence),
            ];
        }else{
            $timestamp     = substr($realTimestamp, 0, -3);
            $microSecond   = substr($realTimestamp, -3);
            return [
                'timestamp'    => date('Y-m-d H:i:s', $timestamp) . '.' . $microSecond,
                'dataCenterId' => bindec($dataCenterId),
                'machineId'    => bindec($machineId),
                'sequence'     => bindec($sequence),
            ];
        }


    }

    // 取当前时间
    private function getUnixTimestamp()
    {
        if(self::$config['short_id']){
            return (float)sprintf("%.0f", microtime(true));
        }else{
            return (float)sprintf("%.0f", microtime(true) * 1000);
        }
    }
}
