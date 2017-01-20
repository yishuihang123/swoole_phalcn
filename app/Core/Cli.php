<?php
namespace App\Core;

use App\Core\Core;

/**
 * Cli模式 
 * @author yishuihang<515294372@qq.com>
 * @copyright Cli 2017-01-20
 * */
class Cli extends Core {

    /**
     * 运行Cli模式，请求到相应的控制器中去
     */
    function run() {
        if (php_sapi_name() !== "cli") {
            echo '该请求只能支持cli模式';
            exit;
        }
        $router         = $this->routerCli();
        $controllerName = "App\Controller\\" . ucfirst($router['controller']) . "Controller";
        $actionName     = ucfirst($router['action']) . 'Action';
        if (method_exists($controllerName, $actionName)) {
            try {
                // 调用相关的异步任务
                $controllerName = new $controllerName();
                $controllerName->$actionName();
            } catch (Exception $e) {
                var_dump($e);
            }
        } else {
            echo ("action not find");
            exit;
        }
    }

    /**
     * 命令行参数转换为控制器数组
     * @global type $argv
     * @return int
     */
    function routerCli() {
        $array = array('controller' => 'Index', 'action' => 'index');
        global $argv;
        foreach ($argv as $arg) {
            $e = explode("=", $arg);
            if (count($e) == 2) {
                $_GET[$e[0]] = $e[1];
            } else {
                $_GET[$e[0]] = 0;
            }
        }
        if (!empty($_GET["controller"])) {
            $array['controller'] = $_GET["controller"];
        }
        if (!empty($_GET["action"])) {
            $array['action'] = $_GET["action"];
        }
        unset($_GET['cli.php']);
        return $array;
    }

}
