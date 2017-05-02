<?php

define("REDIS_HOST", "127.0.0.1");
define("REDIS_PORT", "6379");

define("REDIS_SLOW", "sqlslow");
define("REDIS_BIG", "sqlbig");


define("MYSQL_CONN_KEY", "proxy_conn_key");
define("MYSQL_CONN_REDIS_KEY", "proxy_connection");

define("ERROR_CONN", 10001);
define("ERROR_AUTH", 10002);
define("ERROR_QUERY", 10003);

// no use
$shequ_test = array(
    'chelun' => array(//test is tes db
        'master' => array(
            'host' => '172.16.0.38',
            'port' => 3306,
            'user' => 'chelun_test',
            'password' => '4OX36HnN',
            'database' => 'chelun',
            'charset' => 'utf8mb4',
        ),
        'slave' => array(
            array(
                'host' => '172.16.0.38',
                'port' => 3306,
                'user' => 'chelun_test',
                'password' => '4OX36HnN',
                'database' => 'chelun',
                'charset' => 'utf8mb4',
            ),
        ),
    ),
    'chelun_home' => array(//test is tes db
        'master' => array(
            'host' => '172.16.0.38',
            'port' => 3306,
            'user' => 'chelun_test',
            'password' => '4OX36HnN',
            'database' => 'chelun_home',
            'charset' => 'utf8mb4',
        ),
        'slave' => array(
            array(
                'host' => '172.16.0.38',
                'port' => 3306,
                'user' => 'chelun_test',
                'password' => '4OX36HnN',
                'database' => 'chelun_home',
                'charset' => 'utf8mb4',
            ),
        ),
    )
    , 'spider' => array(//test is tes db
        'master' => array(
            'host' => '172.16.0.38',
            'port' => 3306,
            'user' => 'chelun_test',
            'password' => '4OX36HnN',
            'database' => 'spider',
            'charset' => 'utf8mb4',
        ),
        'slave' => array(
            array(
                'host' => '172.16.0.38',
                'port' => 3306,
                'user' => 'chelun_test',
                'password' => '4OX36HnN',
                'database' => 'spider',
                'charset' => 'utf8mb4',
            ),
        ),
    )
);
