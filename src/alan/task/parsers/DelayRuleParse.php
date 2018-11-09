<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/8/21
 * Time: 19:43
 */

namespace alan\task\parsers;

use common\asyncTasks\basic\IParse;
use common\asyncTasks\basic\Task;



class DelayRuleParse extends BaseParse implements IParse
{
    /**
     * @var Task
     */
    public $task;

    /**
     * @var string 单位：秒
     */
    public $second = 's';

    /**
     * @var string 单位：分钟
     */
    public $minutes = 'm';

    /**
     * @var string 单位：分钟
     */
    public $hour = 'h';

    /**
     * @var string 单位：天
     */
    public $day = 'd';

    /**
     * @var string 默认的时间单位
     */
    public $defaultUntil = 's';

    public $rule = [];

    public function parse()
    {
        $seconds = [];
        foreach ($this->rule as $k => $item) {
            if (is_int($item)){
                $v = $this->parseTime($item, $this->defaultUntil);
            } else {
                preg_match("/(\d+)([a-zA-Z]?)/", $item, $out);
                list(, $val, $until) =  $out;
                $v = $this->parseTime($val, $until ?: $this->defaultUntil);
            }

            $seconds[] = $v;
        }
        asort($seconds);

        $count = $this->task->getTaskRunCount();

        return [$seconds[$count] ?? false, false];
    }


}