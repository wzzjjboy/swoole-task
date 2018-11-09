<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/8/21
 * Time: 19:43
 */

namespace alan\task\parsers;

use alan\task\basic\IParse;
use alan\task\basic\Task;

/**
 * Class TimedRuleParse
 * @package common\tasks
 * 定时规则解析，暂时只支持如下几种
 *  * * * 每分钟执行一次
 *  *\5 * * 每5分钟执行一次
 *  * *\2 * 每2小时执行一次
 *  0 0 *\1 每天 00:00执行一次
 *  * * *\3 每三天执行一次
 */
class TimedRuleParse extends BaseParse implements IParse
{
    /**
     * @var Task
     */
    public $task;

    public $rule = "";

    public $everyFlag = '/';

    /**
     * @return array
     * 解析延时规则，返回值有以下几种情况
     * 1.固定间隔 比如 1分钟后，1小时，1天
     * 2.固定时间 比如每天凌晨 00:01
     * 3.混合情况 比如每隔三天的15:00
     * 针对1的情况，返回下一次的时间间隔即可
     * 针对2和3两种的情况 需要返回 首次执行的时间隔和下次的时间间隔
     * 最终确定返回值为数组
     */
    public function parse()
    {
        if (!is_string($this->rule)){
            Task::showError("无效的定时任务规则");
        }

        list($minute, $hour, $day) = explode(" ", $this->rule);

        $minute = trim($minute);
        $hour = trim($hour);
        $day = trim($day);
        if($this->isStar($minute) && $this->isStar($hour) && $this->isStar($day)){
            return [$this->parseTime(1, "m"), false];
        }
        if ($v = $this->getEveryVal($minute)){
            return [$this->parseTime($v, "m"), false];
        }elseif ($v = $this->getEveryVal($hour)) {
            return [$this->parseTime(intval($minute), "m") + $this->parseTime(intval($v), "h"), false];
        } else {
            $v = $this->getEveryVal($day);
            $next = $this->parseTime(intval($v), "d");

            if ($this->isStar($minute) && $this->isStar($hour)){
                return [$next, false];
            }

            if(!$this->isStar($minute) && !$this->isStar($hour)) {
                $target = mktime(intval($hour), intval($minute),0, date("m"), date('d'), date('Y'));
//                $next += $this->parseTime($minute, "m") + $this->parseTime($hour, "h"); //每天的任务 固定间隔是天，时差算在第一次执行的时间
                $now = time();
                $miss = $target - $now;
                if ($miss > 0){
                    $current =  $this->parseTime($miss, "s");
                } else {
                    $current = $this->parseTime($miss, "s") + $this->parseTime(1, "d");
                }
                return [$current, $next];
            } else {
                Task::showError("无效的规则");
            }
        }
    }

    /**
     * 是否是星号
     * @param $some
     * @return bool
     */
    private function isStar($some)
    {
        return "*" == $some;
    }

    /**
     * 是否为每隔一段时间执行的任务,返回时间间隔
     * @param $val
     * @return bool|string
     */
    private function getEveryVal($val)
    {
        if (false === ($pos = strpos($val, $this->everyFlag))){
            return false;
        }
        return substr($val,  $pos + 1);
    }
}