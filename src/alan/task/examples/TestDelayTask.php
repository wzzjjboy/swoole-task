<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/8/23
 * Time: 10:26
 */

namespace alan\task\examples;

use alan\task\basic\Task;


class TestDelayTask extends Task
{
    public $rule = [
        'type' => 'delay',
        'rule' => [
            '1s','5s',
        ],
    ];

    /**
     * @param mixed $data
     * @return bool
     */
    public function consume($data): bool
    {
        // TODO: Implement consume() method.
        var_dump($data);
        return false;
    }


}