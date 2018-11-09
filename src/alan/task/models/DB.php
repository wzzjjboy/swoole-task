<?php

namespace alan\task\models;

/**
 *
 */
interface DB
{

    /**
     * 查询任务
     * @param int $taskId
     * @return DB
     */
    public static function findTask(int $taskId);

    /**
     * 查询所有未未完成的任务
     * @return \Iterator
     */
    public static function findAllForLoading();


    /**
     * 获取task id
     * @return int
     */
    public function getTaskId():int;

    /**
     * 获取task规则解析器
     * @return string
     */
    public function getRule():string;

    /**
     * 获取任务的业务数据
     * @return string
     */
    public function getData():string;

    /**
     * 获取task的名字
     * @return string
     */
    public function getName():string;

    /**
     * 获取task的完成状态
     * @return int
     */
    public function getStatus():int;

    /**
     * 获取task的运行次数
     * @return int
     */
    public function getRunCount():int;

    /**
     * 获取task的创建时间
     * @return string
     */
    public function getCreatedAt():string;

    /**
     * 获取task的更新时间
     * @return string
     */
    public function getUpdatedAt():string;

    /**
     * 生成task任务
     * @param $data
     * @param null $msg
     * @return DB|bool
     */
    public static function generate($data, &$msg = null);

    /**
     * 修改task的状态为完成
     * @return bool
     */
    public function taskFinished();

    /**
     * task未完成需要继续执行
     * @return mixed
     */
    public function taskContinue();

    /**
     * 判断task是否已经完成
     * @return bool
     */
    public function isFinish();

    /**
     * 获取task对应处理的类
     * @return string
     */
    public function getTaskClass();

    /**
     * task设置结束态
     * 结束的原因是：
     * 1.运行次数超限
     * @return bool
     */
    public function taskOver();

    /**
     * 判断task是否为结束态
     * @return bool
     */
    public function isOver();
}
