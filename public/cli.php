<?php

/* 
 * Cli 模式 
 * /opt/php7/bin/php cli.php controller=index action=index
 */

// 全局定义
date_default_timezone_set('Asia/Shanghai');
define('DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(dirname(__FILE__))) . DS);
define('BEGIN_TIME', microtime(true));
// 加载
require_once ROOT_PATH . DS . 'vendor' . DS . 'autoload.php';

use App\Core\Cli;
$cli=new Cli();
var_dump($cli->run());