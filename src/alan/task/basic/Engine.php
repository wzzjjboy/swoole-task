<?php

namespace alan\task\basic;
/**
 *
 */
interface Engine
{
    /**
     *启动服务
     */
    public function start():void;

    /**
     *停止服务
     */
    public function stop():void;

    /**
     *服务状态
     */
    public function status():void;

    /**
     *服务热重启
     */
    public function reload():void;

    /**
     *服务重启
     */
    public function restart():void;


}
