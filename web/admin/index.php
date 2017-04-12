<?php
require __DIR__."/../../config.php";
$env = get_cfg_var('env.name') ? get_cfg_var('env.name') : 'product';
//date_default_timezone_set('Asia/Shanghai');
//config
$yii = dirname(__FILE__) . '/../public/framework/yii.php';
$config = dirname(__FILE__) . '/protected/config/main.php';

//constant and threshold
$mlogLevel = 2;
$yiiTraceLevel = 0;
$yiiDebug = false;
$const = dirname(__FILE__) . '/protected/config/const.php';
if ($env === 'TEST') {
    $mlogLevel = 1;
    $yiiTraceLevel = 3;
    $yiiDebug = true;
    $const = dirname(__FILE__) . '/protected/config/const_test.php';
    $config = dirname(__FILE__) . '/protected/config/main_test.php';
    ini_set("display_errors", "On");
    define('YII_ENABLE_EXCEPTION_HANDLER', false);
    define('YII_ENABLE_ERROR_HANDLER', false);
} elseif ($env === 'DEV') {
    $mlogLevel = 1;
    $yiiTraceLevel = 3;
    $yiiDebug = true;
    $const = dirname(__FILE__) . '/protected/config/const_dev.php';
    $config = dirname(__FILE__) . '/protected/config/main_dev.php';
    ini_set("display_errors", "On");
    define('YII_ENABLE_EXCEPTION_HANDLER', false);
    define('YII_ENABLE_ERROR_HANDLER', false);
}
//$conText = dirname(__FILE__).'/protected/config/conText.php';
//$classConst=dirname(__FILE__).'/protected/config/classConst.php';
//yii debug switch
defined('YII_DEBUG') or define('YII_DEBUG', $yiiDebug);

// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', $yiiTraceLevel);

/* How to write MongoDB log, level is:
 * 1-info(default)      all write for info_log + warning_log + error_log;
 * 2-warning            write to warning_log + error_log;
 * 3-error              write to error_log only;
 * chris add.
 */
defined('YII_MLOG_LEVEL') or define('YII_MLOG_LEVEL', $mlogLevel);

require_once($yii);
require_once($const);
//require_once($classConst);
//require_once($conText);
Yii::createWebApplication($config)->run();
