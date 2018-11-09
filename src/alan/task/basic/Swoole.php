<?php

namespace alan\task\basic;

use Yii;
use Swoole\Table;
use swoole_client;
use swoole_process;
use swoole_server;
use yii\base\Component;
use alan\task\behaviors\SplitLogBehaviors;

/**
 *
 */
class Swoole extends Component implements Engine
{
    /**
     * task进程启动事件
     */
    const EVENT_START = 'ent_swoole_on_start';

    /**
     * task work进程开始处理事件
     */
    const EVENT_WORK_ON_TASK = 'ent_swoole_on_task';

    const TAB_WORK_ID = "work_id";

    const TAB_RUN_COUNT = "run_cont";

    const TAB_WORK_PID = 'work_pid';

    /**
     * @var swoole_server
     */
    private $server;

    /**
     * @var ILog
     */
    public $log = 'alan\task\basic\Log';

    /**
     * @var string
     */
    public $host;

    /**
     * @var int
     */
    public $port;

    /**
     * @var int
     */
    private $workerNum = 1;

    /**
     * @var int
     */
    public $taskWorkerNum;

    /**
     * @var bool
     */
    public $daemonize;

    /**
     * @var integer
     */
    public $pid;

    /**
     * @var Table
     */
    private $tab;

    /**
     * @var integer
     */
    public $maxRunCount = 10000;


    public function behaviors()
    {
        if ($this->daemonize){
            return [
                [
                    'class' => SplitLogBehaviors::className(),
                    'engine' => $this,
                    'log' => $this->log,
                ],
            ];
        }

        return [];
    }

    /**
     * @var Task
     */
    public $taskClass = 'alan\task\basic\Task';

    public function init()
    {
        parent::init(); // TODO: Change the autogenerated stub
        if (!is_object($this->log)){
            $this->log = Yii::createObject($this->log);
        }
        if (empty($this->pid)){
            $this->pid = Yii::$app->getRuntimePath() . '/swoole.pid';
        }
    }

    /**
     * @param Task $task
     * @return bool
     */
    public function publish(Task $task)
    {
        // TODO: implement here
        $client = new swoole_client(SWOOLE_SOCK_TCP);
        try {
            if (!($ret = $client->connect($this->host, $this->port))) {
                $msg = '连结' . $this->host . ':' . $this->port . '失败';
                $this->log->error($msg);
                return false;
            }
            $client->send($task->taskId) or
            $this->log->error("发送任务({$task->taskId})失败");
        } catch (\Exception $exception) {
            $msg = $exception->getMessage();
            $this->log->error("发送任务({$task->taskId})失败:$msg");
            return false;
        }

        return true;
    }

    /**
     *
     */
    public function loadingTask():void
    {
        // TODO: implement here
        $i = 0;
        $this->log->trace("loading tasks... ");
        try{
            foreach ($this->taskClass::findAllForLoading() as $task)
            {
                $i++;
                $this->publish($task);
                if(0 == $i % 20){
                    //sleep(2);禁用sleep
                }
            }
            $this->log->trace("load tasks finish ");
        }catch (TaskException $exception) {
            $this->handlerTaskException($exception);
        }catch (\Exception $e){
            $this->handlerException($e);
        }
    }

    /**
     * @inheritDoc
     */
    public function start():void
    {
        if($this->getPid()){
            $this->status();
            exit(0);
        }
        // TODO: implement here
        $this->server = new swoole_server($this->host, $this->port);
        $this->server->set([
            'worker_num' => $this->workerNum,
            'task_worker_num' => $this->taskWorkerNum,
            'daemonize' => $this->daemonize,
            'log_file' => $this->getLogPath(),
        ]);
        if ($this->daemonize){
            $this->server->set([
                'pid_file' => $this->pid,
            ]);
        }

        foreach ([
                     'Start',
                     'ManagerStart',
                     'ManagerStop',
                     'WorkerStart',
                     'WorkerStop',
                     'Connect',
                     'Receive',
                     'Close',
                     'Task',
                     'Finish',
                 ] as $event) {
            $method = "on{$event}";
            if (method_exists($this, $method)){
                $this->server->on($event, [$this, $method]);
            }

        }
        $this->tab = $this->createMemoryTab();
        $this->server->start();
    }

    public function onReceive(swoole_server $server, int $fd, int $reactor_id, string $taskId)
    {
        $this->log->trace("onReceive taskId:{$taskId}");
        try{
            $task = $this->taskClass::findById($taskId);
            /** @var Task $task */
            list($first, $next) = $task->getInterval();
            if (false === $first){
                $task->taskOver();
                $this->log->trace("task : ($task->taskId) has over " );
                return false;
            }
            $this->intervalLog($task, $first, $next);
            $server->after($first, function() use ($server, $task, $next){
                $this->startTask($server, $task, $next);
            });
        }catch (TaskException $exception) {
            $this->handlerTaskException($exception);
        }catch (\Exception $e){
            $this->handlerException($e);
        }
    }

    /**
     * @param Task $task
     * @param integer|false $first
     * @param null|integer|false $next
     */
    private function intervalLog($task, $first, $next = null)
    {
        if ($task->rule->isTimed()){
            $msg = "calc timed task($task->taskId) interval, first: $first, next: $next";
        } else {
            $taskType = $task->rule->isDelay() ? "delay" : "async";
            $msg = "calc {$taskType} task({$task->taskId}) interval: $first";
        }
        $this->log->trace($msg);
    }


    public function onWorkerStart(swoole_server $server, int $worker_id)
    {

        if (!$server->taskworker){
            $this->loadingTask();
            $this->log->trace("work start  \tid: {$worker_id} \tpid: {$server->worker_pid}");
            $this->trigger(self::EVENT_START);
        } else {
            $this->log->trace("task work start \tid: {$worker_id} \tpid: {$server->worker_pid}");
        }
    }


    public function onTask(swoole_server $server, int $task_id, int $src_worker_id, $data)
    {
        $this->log->trace(["on task" => $data]);
        list($taskId, $next) =  $data;
        try{
            $task = $this->taskClass::findById($taskId);
            $task->run();
            $this->incrTskRunCount($server);
            return $this->getTaskReturnVal($taskId, $next, $server->worker_id);
        }catch (\Exception $e){
            $this->handlerException($e);
            return $this->getTaskReturnVal($taskId, $next, $server->worker_id);
        }
    }

    private function getTaskReturnVal($taskId, $next, $worker_id)
    {
        return json_encode([$taskId, $next, $worker_id]);
    }


    public function onFinish(swoole_server $server, int $task_id, string $data)
    {
        $this->log->trace("work: {$task_id} on finish : {$data}");
        list($taskId, $next, $worker_id) =  json_decode($data, true);
        $this->restartTaskWork($worker_id);
        try{
            $task = $this->taskClass::findById($taskId);
            $this->log->info([
                'task_id' => $taskId,
                'taskIsFinish' => $task->taskIsFinish(),
                'taskIsOver' => $task->taskIsOver(),
            ]);
            if ($task->taskIsFinish()){
                $this->log->trace("task : ($task->taskId) has finished " );
                return true;
            }

            if ($task->rule->isTimed() && $next){
                $interval = $next;
            } else {
                /** @var Task $task */
                list($interval) = $task->getInterval();
                $this->intervalLog($task, $interval);
            }

            if ($interval){
                $server->after($interval, function() use ($server, $task, $interval){
                    $this->startTask($server, $task, $interval);
                });
            } elseif (false === $interval){
                $task->taskOver();
                $this->log->trace("task : ($task->taskId) has over " );
            }
        }catch (TaskException $exception) {
            $this->handlerTaskException($exception);
        }catch (\Exception $e){
            $this->handlerException($e);
        }
    }

    /**
     * @param swoole_server $server
     * @param Task $task
     * @param integer $interval
     */
    private function startTask($server, $task, $interval)
    {
        $server->task([$task->taskId, $interval]);
    }

    /**
     * @inheritDoc
     */
    public function stop():void
    {
        if (!($pid = $this->getPid())){
            $this->pr("服务未启动");
        } else {
            swoole_process::kill($this->getPid(), SIGTERM);
        }
    }

    /**
     * @inheritDoc
     */
    public function status():void
    {
        if($pid = $this->getPid()){
            $this->pr("服务正运行中... pid:{$pid}",1);
        }
    }

    public function getLogPath()
    {
        $path = Yii::$app->getRuntimePath() . '/logs/swoole/entry.log';
        is_dir($dir = dirname($path)) or mkdir($dir, 0777, true);
        touch($path);
        return $path;
    }

    /**
     *@inheritDoc
     */
    public function reload(): void
    {
        if (!($pid = $this->getPid())){
            $this->pr("服务未启动",1);
        }

        swoole_process::kill($pid, SIGUSR1);
    }

    public function restart():void
    {
        $this->stop();
        sleep(1);
        $this->start();
    }

    public function getPid()
    {
        if(!file_exists($this->pid)){
            return false;
        }
        $pid = file_get_contents($this->pid);
        return swoole_process::kill($pid, 0) ? $pid : false;
    }

    /**
     * @param TaskException $taskException
     */
    public function handlerTaskException($taskException)
    {
        $this->log->warning(implode(PHP_EOL, [
            $taskException->getName(),
            $taskException->getMessage(),
            $taskException->getFile(),
            $taskException->getLine(),
            $taskException->getTraceAsString(),
        ]));
    }

    /**
     * @param \Exception $exception
     */
    public function handlerException($exception)
    {
        $this->log->error(implode(PHP_EOL, [
            $exception->getMessage(),
            $exception->getLine(),
            $exception->getFile(),
            $exception->getTraceAsString(),
        ]));
    }

    private function createMemoryTab()
    {
        $table = new Table($this->taskWorkerNum);
        $table->column(self::TAB_WORK_ID,Table::TYPE_INT, 4);
        $table->column(self::TAB_WORK_PID,Table::TYPE_INT, 4);
        $table->column(self::TAB_RUN_COUNT,Table::TYPE_INT, 4);
        $table->create();
        return $table;
    }

    /**
     * @param swoole_server $server
     */
    private function incrTskRunCount($server)
    {
        $index = $this->getWorkTabItem($server->worker_id, $server->worker_pid);
        $incr = $this->tab->incr($index, self::TAB_RUN_COUNT);
        $this->tab->set($index, [
            self::TAB_RUN_COUNT => $incr,
            self::TAB_WORK_PID => $server->worker_pid,
        ]);
        $this->log->trace("worker_id: {$index} pid: {$server->worker_pid} run count :". $this->tab->get($index)[self::TAB_RUN_COUNT]);
    }

    private function getWorkTabItem($worker_id, $pid)
    {
        if (!$worker_id){
            throw new TaskException("无效的worker_id:{$worker_id}");
        }

        if (!$this->tab->exist($worker_id)){
            $this->tab->set($worker_id,[
               self::TAB_WORK_ID => $worker_id,
               self::TAB_RUN_COUNT => 0,
               self::TAB_WORK_PID => $pid,
            ]);
        }

        return $worker_id;
    }

    private function restartTaskWork($worker_id)
    {
        $item = $this->tab->get($worker_id);
        $work_pid = $item[self::TAB_WORK_PID];
        $count = $item[self::TAB_RUN_COUNT];
        $active = swoole_process::kill($work_pid,0);
        if ($work_pid && $count && ($count % $this->maxRunCount == 0) && $active){
            $result = swoole_process::kill($work_pid, SIGTERM);
            $this->log->trace("kill $work_pid, result: " . ($result ? 'ok' : 'fail'));
            sleep(1);
        }
    }

    private function pr($str)
    {
        echo $str . PHP_EOL;
    }
}
