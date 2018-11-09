# swoole-task
使用方法：
1.composer require alan/yii2-swoole-task
2.配置yii2框架的DB，执行数据迁移 php yii migrate/up --migrationPath=@alan/task/migrations
3.添加管理脚本
4.配置swoole组件
'swoole' => [
    'class'            => 'common\asyncTasks\basic\Swoole',
    'host'             => '127.0.0.1',
    'port'             => '9501',
    'taskWorkerNum'    => '3',
    'daemonize'        => true,
],