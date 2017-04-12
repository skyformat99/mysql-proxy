<?php

$mzRoot = dirname(dirname(dirname(dirname(__FILE__)))) . '/public';
// $mzRoot = "/data/www/mazhan/gay_api3.3/public";
// $mzRoot = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/public/trunk';
Yii::setPathOfAlias('mzRoot', $mzRoot);

return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => '易管家',
    'preload' => array('log'),
    'import' => array(
        'application.models.*',
        'application.components.*',
        'mzRoot.classes.*',
        'mzRoot.classes.utils.*',
        'mzRoot.classes.service.*',
        'mzRoot.models.*',
        'application.extensions.*',
        'application.service.*',
    ),
    //应用基础目录
    'modules' => array(
        'gii' => array(
            'class' => 'system.gii.GiiModule', //声明一个名为gii的模块，它的类是GiiModule。
            'password' => '999999', //为这个模块设置了密码，访问Gii时会有一个输入框要求填写这个密码。
            'ipFilters' => array('192.168.1.*', '127.0.0.1', '::1'), // 默认情况下只允许本机访问Gii
        ),
    ),
    // application components
    'components' => array(
        'request' => array(
            'enableCsrfValidation' => false,
        ),
        'user' => array(
            'allowAutoLogin' => false,
        ),
        //数据库连接
        'db' => array(
            'class' => 'MDbConnection',
            'connectionString' => 'mysql:host=127.0.0.1;dbname=eguanjia',
            'emulatePrepare' => true,
            'username' => 'root',
            'password' => 'woshiguo35',
            'charset' => 'utf8',
            'tablePrefix' => 'mz_',
            'enableParamLogging' => true,
            'enableSlave' => true,
            'slaves' => array(
                array(
                    'connectionString' => 'mysql:host=127.0.0.1;port=3306;dbname=eguanjia',
                    'emulatePrepare' => true,
                    'username' => 'root',
                    'password' => 'woshiguo35',
                    'charset' => 'utf8',
                    'tablePrefix' => 'mz_',
                    'enableParamLogging' => true,
                    'weight' => '100',
                )
            )
        ),
      
        //MemCache连接
        'cache' => array(
            'keyPrefix' => 'mazhan',
            'class' => 'CMemCache',
            'servers' => array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 60,
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 40,
                ),
            ),
        ),
        //Redis连接
        'redis' => array(
            'class' => 'DyRedis',
            'database' => 0,
            'prefix' => '',
            'servers' => array(
                array(
                    'host' => '192.168.1.31',
                    'port' => 6379,
                ),
                array(
                    'host' => '192.168.1.34',
                    'port' => 6379,
                ),
                array(
                    'host' => '192.168.1.25',
                    'port' => 6379,
                ),
            ),
        ),
        //RabbitMQ
        'amqp' => array(
            'class' => 'RabbitMq',
            'servers' => array(
                'hosts' => array(
                    'host1' => array(
                        'host' => '192.168.1.17',
                        'port' => '5672',
                        'user' => 'ms',
                        'password' => 'ms',
                        'vhost' => 'ms'
                    ),
                    'host2' => array(
                        'host' => '192.168.1.18',
                        'port' => '5672',
                        'user' => 'ms',
                        'password' => 'ms',
                        'vhost' => 'ms'
                    )
                ),
                'exchange' => 'UserClearEx'
            )
        ),
        'OSSClient' => array(
            'class' => 'OSS'
        ),
        //URL重写
        'urlManager' => array(
            'urlFormat' => 'path',
            'showScriptName' => false,
            'urlSuffix' => '.html',
            'caseSensitive' => false,
            'rules' => array(
            //'<controller:\w+>/<id:\d+>'=>'<controller>/view',
            //'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
            //'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
            ),
        ),
        //404页面
        'errorHandler' => array(
            'errorAction' => 'site/error',
        ),
        //记录
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning',
                ),
            ),
        ),
    ),
    //默认
     'defaultController'=> 'Home/index',
    //常用变量
    'params' => array(
        'adminEmail' => 'mz@mazhan.com',
    ),
);
