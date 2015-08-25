<?php
namespace CST\Yii\Illuminate\Console;

use CST\Yii\Illuminate\Queue\Worker;

class QueueCommand extends Command
{
    public function actionListen($queue = 'default', $memory = '128', $delay = 0, $sleep = 3, $maxTries = 0)
    {
        $worker = new Worker(\Yii::app()->queue->getQueueManager(), null, null);
        $worker->daemon('default', $queue, $delay, $memory, $sleep, $maxTries);
    }
}