<?php
/**
 * Created by PhpStorm.
 * User: alan
 * Date: 2018/8/23
 * Time: 10:26
 */

namespace alan\task\examples;

use alan\task\basic\Task;

class TestAsyncTask extends Task
{

    public $rule = [
        'type' => 'async',
    ];

    /**
     * @param mixed $data
     * @return bool
     */
    public function consume($data): bool
    {
        // TODO: Implement consume() method.
        var_dump($data);
        return true;
    }
}