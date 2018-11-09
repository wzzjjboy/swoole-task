<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/8/23
 * Time: 14:26
 */

namespace alan\task\parsers;

use yii\base\BaseObject;
use common\asyncTasks\basic\Task;

class BaseParse extends BaseObject
{
    protected function parseTime($val, $until)
    {
        $until = strtolower($until);
        switch ($until){
            case "s":
                $second = 1;
                break;
            case "m":
                $second = 60;
                break;
            case "h":
                $second = 3600;
                break;
            case "d":
                $second = 86400;
                break;
            default:
                Task::showError("无效的时间单位");
        }

        return $val * $second * 1000;
    }
}