<?php
namespace App\Task;

use App\Core\Task;

/*
 * 上报数据任务
 */
class ReportTask  extends Task{
    
    /**
     * 测试
     * http://192.168.197.197:9501/?action=test&task=report&mode=async
     * @param type $argv
     */
    function testAction($argv = null) {
        sleep(3);
        var_dump($argv);
        echo 'task-test';
    }

}
