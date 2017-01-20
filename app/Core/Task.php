<?php
namespace App\Core;

use App\Core\Core;

/** 
 * Task异步模式 
 * 
 * @author yishuihang<515294372@qq.com>
 * @copyright Task 2017-01-20
 **/
class Task extends Core {
    
    /**
     * 执行异步任务池任务
     * 
     * @param \swoole_http_server $httpServer
     * @param type $taskId
     * @param type $fromId
     * @param type $taskData 
     */
    public static function run(\swoole_http_server $httpServer, $taskId, $fromId, $taskData) {
        $data       = json_decode($taskData, true);
        $taskName   = "App\Task\\" . ucfirst($data['task']) . "Task";
        $actionName = ucfirst($data['action']) . 'Action';
        $argv       = $data['argv'];
        if (method_exists($taskName, $actionName)) {
            try {
                // 调用相关的异步任务
                $task = new $taskName();
                $task->$actionName($argv);
            } catch (Exception $e) {
                var_dump($e);
            }
        } else {
            echo ("action not find");
        }
    }
     
}
