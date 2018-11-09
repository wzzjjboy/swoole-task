<?php

use yii\db\Migration;

/**
 * Class m180820_072812_crt_tab_async_task
 */
class m180820_072812_crt_tab_async_task extends Migration
{
    public $tableName = '{{%async_tasks}}';

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable($this->tableName,[
            'task_id' => $this->primaryKey()->comment("任务ID"),
            'task_name' => $this->string(20)->notNull()->defaultValue("")->comment("任务名称"),
            'task_class' => $this->string(50)->notNull()->defaultValue("")->comment("任务类名"),
            'task_data' => $this->string(500)->notNull()->defaultValue('')->comment("任务数据JSON"),
            'task_rule' => $this->string(500)->notNull()->defaultValue('')->comment("任务规则JSON"),
            'task_status' => $this->smallInteger()->notNull()->defaultValue(0)->comment("状态 0|未完成 1|已完成"),
            'run_count'  => $this->integer()->notNull()->defaultValue(0)->comment("任务执行次数"),
            'task_over'  => $this->integer()->notNull()->defaultValue(0)->comment("任务结束 0|未结束 1|已结束"),
            'output' => $this->string(200)->notNull()->defaultValue("")->comment("执行中的输出"),
            'finish_at' => $this->dateTime()->null()->comment("任务完成时间"),
            'created_at' => $this->dateTime()->notNull()->comment("创建时间"),
            'updated_at' => $this->dateTime()->notNull()->comment("更新时间"),
        ]);

        $this->createIndex('task_status', $this->tableName, ['task_status', 'task_over']);
        $this->addCommentOnTable($this->tableName, "定时任务记录表");
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropTable($this->tableName);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m180820_072812_crt_tab_async_task cannot be reverted.\n";

        return false;
    }
    */
}
