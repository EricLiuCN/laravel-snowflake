<?php

return array(
	/**
	 * 初始时间戳
	 * @var number
	 */
	'start_time' => strtotime(env("SNOWFLAKE_START_TIME", '2022-04-12 00:00:00')),
	/**
	 * 数据中心ID 【0~31】
	 * @var number
	 */
	'dataCenter_id' => env("SNOWFLAKE_DATA_ID", 0),

	/**
	 * 机器ID 【0~31】
	 * @var number
	 */
	'machine_id' => env("SNOWFLAKE_MACHINE_ID", 0),

    /**
     * 是否短ID 【兼容js】
     * @var number
     */
    'short_id' => env("SNOWFLAKE_SHORT_ID", false),

	/**
	 * 是否启用Redis锁 false使用 文件锁
	 * @var bool
	 */
	'redis_lock' => env("SNOWFLAKE_REDIS_LOCK", false),

	/**
	 * Redis配置连接名称
	 * @var array
	 */
	'redis_connection' => "default"
);