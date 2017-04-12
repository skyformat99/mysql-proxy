<?php
require __DIR__."/config.php";
swoole_load_module(__DIR__ .'/cpp_moudle/mysql_proxy.so');
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
require ROOT_PATH . '/Bootstrap/Autoloader.php';
\Bootstrap\Autoloader::instance()->addRoot(ROOT_PATH)->init();


$core = new \Core\MysqlProxy();
$core->init();
$core->start();
