<?php
namespace App\Core;

use App\Core\Task;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Application;
use Phalcon\Loader;
use Phalcon\Mvc\View;
use Phalcon\Config;

/**
 * Http服务类
 * @example    异步模式：http://192.168.197.197:9501?mode=async&task=&action=&...
 *              同步模式：http://192.168.197.197:9501/test
 * @author yishuihang<515294372@qq.com>
 * @copyright HttpServer 2017-01-20
 */
class HttpServer extends Core {

    // httpserver单例,内含http变量swoole_http_server实例
    public static $_httpserver = null;
    // httpserver配置
    public static $_config     = array();
    // httpserver监听地址
    public static $_host;
    // httpserver监听端口
    public static $_port;
    // swoole_http_server实例
    public $http;
    // phalcon容器
    public static $_di;

    /**
     * 构造函数
     * @param type $host
     * @param type $port
     */
    function __construct($host, $port, $config) {
        $this->http = new \swoole_http_server($host, $port);
        $this->http->set($config);
    }

    /**
     * 初始化httpserver服务
     */
    function init() {
        $this->http->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->http->on('Request', array($this, 'onRequest'));
        $this->http->on('Task', array($this, 'onTask'));
        $this->http->on('Finish', array($this, 'onFinish'));
        return $this;
    }

    /**
     * 运行httpserver服务
     */
    public function run() {
        $this->http->start();
    }

    /**
     * 设置相关参数，并返回httpserver单例
     * @param type $host
     * @param type $port
     * @param type $config
     * @return type
     */
    public static function getHttpServerInstance($host = '127.0.0.1', $port = '9501', $config = array()) {
        if (is_null(self::$_httpserver)) {
            self::$_httpserver = new self($host, $port, $config);
            self::$_host = $host;
            self::$_port = $port;
            self::$_config = $config;
        }
        return self::$_httpserver;
    }

    /**
     * Phalcon应用设置并允许
     */
    function onWorkerStart() {
        // 注册加载
        $loader = new Loader();
        $loader->registerDirs(array(
            ROOT_PATH . '/app/Controller/',
            ROOT_PATH . '/app/Model/'
        ));
        //注册公共命名空间(Todo此处不针对控制器和模型类，控制器和模型类的命名空间在路由文件里面设置)
        $loader->registerNamespaces(array(
            'App\Controller' => ROOT_PATH . 'app/Controller',
            'App\Model'      => ROOT_PATH . 'app/Model',
            'App\Task'       => ROOT_PATH . 'app/Task',
        ));
        $loader->register();

        self::$_di = new FactoryDefault();
        // App应用配置文件
        $appConfigFile = ROOT_PATH . 'app/Config/config.php';
        if (file_exists($appConfigFile)) {
            // 设置配置文件
            $appConfig = require_once( $appConfigFile );
            $config    = new Config($appConfig);
            self::$_di->set('config', $config);
        } else {
            // 应用配置文件缺失
            die('应用配置文件缺失');
        }
        // 设置视图
        self::$_di->set('view', function() {
            $view = new View();
            $view->setViewsDir(ROOT_PATH . '/app/view/');
            return $view;
        });
        // 设置路由配置
        $routeFile = ROOT_PATH . 'app/routes.php';
        if (file_exists($routeFile)) {
            self::$_di->set('router', function () use ($routeFile) {
                // 注册路由
                return require_once $routeFile;
            });
        } else {
            // 路由文件缺失
            die('路由文件缺失');
        }
    }

    /**
     * 请求响应
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
        // 捕获异常
        register_shutdown_function(array($this, 'handleFatal'));
        try {
            ob_start();
            // 变量
            $_GET     = $_POST    = $_COOKIE  = $_REQUEST = $_SERVER  = $_FILES   = [];
            if (!empty($request->get)) {
                $_GET = $request->get;
                $_REQUEST += $_GET;
            }
            if (!empty($request->post)) {
                $_POST = $request->post;
                $_REQUEST += $_POST;
            }
            if (!empty($request->cookie)) {
                $_COOKIE = $request->cookie;
            }
            if (!empty($request->server)) {
                $_SERVER = $request->server;
            }
            if (!empty($request->files)) {
                $_FILES = $request->files;
            }

            $requestUri   = $request->server['request_uri'];
            $_GET['_url'] = $requestUri;
            self::$_request=$request;
            self::$_response=$response;
            self::$_get=$request->get;
            self::$_post=$request->post;
            
            if (isset($request->get['mode']) && $request->get['mode'] === 'async') {
                // 异步处理任务模式
                $param  = array(
                    'task'   => $request->get['task'],
                    'action' => $request->get['action'],
                    'name'   => '', // 任务名称
                    'argv'   => $_REQUEST, // Todo 
                );
                // 放进异步任务池
                $taskId = $this->http->task(json_encode($param));
                self::$_response->end(json_encode(['code' => 200, 'message' => '成功']));
            }
            // 浏览器处理模式，启用phalcon应用处理
            $application = new Application(self::$_di);
            echo $application->handle($request->server['request_uri'])->getContent();
        } catch (\Exception $e) {
            // 异常处理
            self::$_response->end($e->getMessage());
            exit;
        }
        // 输出处理
        $result = ob_get_contents();
        ob_end_clean();
        self::$_response->end($result);
        unset($result);
    }

    /**
     * 异步任务池处理事件
     * @param \swoole_http_server $httpServer
     * @param type $taskId
     * @param type $fromId
     * @param type $taskData
     */
    function onTask(\swoole_http_server $httpServer, $taskId, $fromId, $taskData) {
        Task::run($httpServer, $taskId, $fromId, $taskData);
        $httpServer->finish($taskData);
    }

    /**
     * 异步任务池处理完成后事件
     * @param \swoole_http_server $httpServer
     * @param type $data
     */
    function onFinish(\swoole_http_server $httpServer, $taskId, $data) {
        echo "Task {$taskId} finish\n";
        echo "Result: {$data}\n";
        unset($data);
    }

    /**
     * 捕获异常，并对异常处理
     * @return type
     */
    function handleFatal() {
        $error = error_get_last();
        if (!isset($error['type'])) {
            return;
        }

        switch ($error['type']) {
            case E_ERROR:
            case E_PARSE:
            case E_DEPRECATED:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                break;
            default:
                return;
        }
        $message = $error['message'];
        $file    = $error['file'];
        $line    = $error['line'];
        $log     = "\n异常提示：$message ($file:$line)\nStack trace:\n";
        $trace   = debug_backtrace(1);

        foreach ($trace as $i => $t) {
            if (!isset($t['file'])) {
                $t['file'] = 'unknown';
            }
            if (!isset($t['line'])) {
                $t['line'] = 0;
            }
            if (!isset($t['function'])) {
                $t['function'] = 'unknown';
            }
            $log .= "#$i {$t['file']}({$t['line']}): ";
            if (isset($t['object']) && is_object($t['object'])) {
                $log .= get_class($t['object']) . '->';
            }
            $log .= "{$t['function']}()\n";
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            $log .= '[QUERY] ' . $_SERVER['REQUEST_URI'];
        }
        echo $log;
    }

}
