<?php

/**
 * 路由配置文件
 */
use Phalcon\Mvc\Router;

$router = new Router();
$router->add('/',               [ 'namespace' => 'App\Controller', 'controller' => "Index", 'action' => 'index']);
$router->add('/test',           [ 'namespace' => 'App\Controller', 'controller' => 'Index', 'action' => 'test']);
$router->add('/ss',           [ 'namespace' => 'App\Controller', 'controller' => 'Index', 'action' => 'test']);
return $router;
