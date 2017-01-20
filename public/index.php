<?php


// 全局定义
date_default_timezone_set('Asia/Shanghai');
define('DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', realpath(dirname(dirname(__FILE__))) . DS);
define('BEGIN_TIME', microtime(true));
// 加载
require_once ROOT_PATH . DS . 'vendor' . DS . 'autoload.php';

use App\Core\HttpServer;

$httpHost         = '0.0.0.0';
$httpPort         = '9501';
$httpServerConfig = array(
    'worker_num'      => 2,
    'daemonize'       => false,
    'max_request'     => 1,
    'task_worker_num' => 2,
);


// Http服务实例
$httpServer=HttpServer::getHttpServerInstance($httpHost, $httpPort, $httpServerConfig);
$httpServer->init()->run();





