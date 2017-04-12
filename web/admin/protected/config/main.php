<?php

$mzRoot = dirname(dirname(dirname(dirname(__FILE__)))) . '/public';
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
        //amqp
        'amqp' => array(
            'class' => 'RabbitMq',
            'servers' => array(
                'hosts' => array(
                    'host1' => array(
                        'host' => '10.141.48.145',
                        'port' => '5672',
                        'user' => 'ms',
                        'password' => 'ms',
                        'vhost' => 'ms'
                    ),
                    'host2' => array(
                        'host' => '10.141.48.145',
                        'port' => '5672',
                        'user' => 'ms',
                        'password' => 'ms',
                        'vhost' => 'ms'
                    )
                ),
                'exchange' => 'DealExchange'
            )
        ),
        //404页面
        'errorHandler' => array(
            'errorAction' => 'site/error',
        ),
        'OSSClient' => array(
            'class' => 'OSS'
        ),
        //记录
        'log' => array(
            'class' => 'CLogRouter',
            'routes' => array(
                array(
                    'class' => 'CFileLogRoute',
                    'levels' => 'error, warning, info',
                ),
            ),
        ),
    ),
//     'defaultController'=> 'Home/index',
    'defaultController' => 'Home/index',
    //常用变量
    'params' => array(
        'adminEmail' => 'mz@mazhan.com',
        'pushHost' => '123.206.71.124',
    ),
);
