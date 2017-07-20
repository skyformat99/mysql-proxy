<?php

/*
 * 是否开启记录日志功能，开启后每次查询都会发送给REDIS_HOST所在的redis，用于生成web管理界面 统计慢查询等
 * 开启后会降低30%左右的性能
 */
define("RECORD_QUERY", false);
/*
 * 记录客户端sql查询的redis机器
 */
define("REDIS_HOST", "127.0.0.1");
define("REDIS_PORT", "6379");
/*
 * redis key
 */
define("REDIS_SLOW", "sqlslow");
define("REDIS_BIG", "sqlbig");

/*
 * swoole table的key
 */
define("MYSQL_CONN_KEY", "proxy_conn_key");
define("MYSQL_CONN_REDIS_KEY", "proxy_connection");

/*
 * 错误码定义
 */
define("ERROR_CONN", 10001);
define("ERROR_AUTH", 10002);
define("ERROR_QUERY", 10003);
define("ERROR_PREPARE", 10004);


/*
 * swoole server通用配置信息
 */
define("WORKER_NUM", 1);
//task 用于上报查询用
define("TASK_WORKER_NUM", 1);
//SWOOLE server的 日志
define("SWOOLE_LOG", "/tmp/sqlproxy.log");
//是否守护进程方式运行
define("DAEMON", 0);
//mysql proxy绑定的端口
define("PORT", "9536");


/*
 * mysql数据源配置
 */
$config = array(
    'eguanjia' => array(
        'master' => array(
            'host' => '10.10.2.73',
            'port' => 3306,
            'user' => 'root',
            'password' => 'woshiguo35',
            'database' => 'eguanjia',
            'charset' => 'utf8',
            
            'maxconn' =>20
        ),
        'slave' => array(
            array(
                'host' => '10.10.2.73',
                'port' => 3306,
                'user' => 'root',
                'password' => 'woshiguo35',
                'database' => 'eguanjia',
                'charset' => 'utf8',
                
                'maxconn' =>20
            ),
        ),
    ),
);
define("MYSQL_CONF", $config);
