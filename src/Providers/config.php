<?php

return array(
	/**
	 * 初始时间戳
	 * @var number
	 */
	'start_time' => strtotime(env("SNOWFLAKE_START_TIME", '2020-07-18 10:00:00')),
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
	 * 是否启用redis锁 false使用 文件锁
	 * @var bool
	 */
	'redis_lock' => env("SNOWFLAKE_REDIS_LOCK", false),

	/**
	 * redis配置信息
	 * @var array
	 */
	'redis_config' => array(
		'host'     => env('REDIS_HOST', '127.0.0.1'),
		'password' => env('REDIS_PASSWORD', null),
		'port'     => env('REDIS_PORT', 6379),
		'database' => env('REDIS_DB', 0),
    )
);