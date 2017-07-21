<?php
require __DIR__."/config.php";
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
require ROOT_PATH . '/Vendor/Autoloader.php';
\Vendor\Autoloader::instance()->addRoot(ROOT_PATH)->init();


$core = new \Core\MysqlProxy();
$core->init();
$core->start();
