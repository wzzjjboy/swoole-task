<?php

namespace alan\task\basic;

use Yii;
use yii\base\BaseObject;

/**
 *
 */
class Log extends BaseObject implements ILog
{
    public $category = 'application';

    public $levels = ['error', 'warning', 'info', 'trace'];

    private function logFormat($msg, $level)
    {
        if (!in_array($level, $this->levels)){
            return null;
        }

        $msg = is_array($msg) ? json_encode($msg, JSON_UNESCAPED_UNICODE) : $msg;
        echo "[" . date('Y-m-d H:i:s') . "] [$level]: {$msg}" . PHP_EOL;
    }

    /**
     * @inheritDoc
     * @param mixed $msg
     * @return void
     */
    public function error($msg):void
    {
        $this->logFormat($msg, 'error');
        Yii::error($msg, $this->category);
    }

    /**
     * @inheritDoc
     * @param mixed $msg
     * @return void
     */
    public function info($msg):void
    {
        $this->logFormat($msg, 'info');
    }

    /**
     * @inheritDoc
     * @param mixed $msg
     * @return void
     */
    public function warning($msg):void
    {
        $this->logFormat($msg, 'warning');
        Yii::warning($msg, $this->category);
    }

    /**
     * @param mixed $msg
     */
    public function trace($msg): void
    {
        $this->logFormat($msg, 'trace');
    }
}
