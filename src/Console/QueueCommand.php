<?php
namespace CST\Yii\Illuminate\Console;

use CST\Yii\Illuminate\Exception\ExceptionHandler;
use CST\Yii\Illuminate\Queue\DatabaseFailedJobProvider;
use CST\Yii\Illuminate\Queue\Worker;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

class QueueCommand extends Command
{
    public function actionListen($queue = 'default', $memory = '128', $delay = 0, $sleep = 3, $maxTries = 0)
    {
        $failed = new DatabaseFailedJobProvider();
        $dispatcher = new Dispatcher(new Container());
        $queueManager = \Yii::app()->queue->getQueueManager();

        $worker = new Worker($queueManager, $failed, $dispatcher);
        $worker->setDaemonExceptionHandler(new ExceptionHandler());

        $worker->daemon('default', $queue, $delay, $memory, $sleep, $maxTries);
    }

    public function actionFailedJobsMigration($table = 'failed_jobs')
    {
        $name = 'm' . '_create_' . $table . '_table';

        $stub = str_replace(
            ['{{table}}', '{{tableClassName}}'], [$table, $name],
            file_get_contents(__DIR__ . '/stubs/failed_jobs.stub')
        );

        $file = \Yii::getPathOfAlias('application.migrations') . DIRECTORY_SEPARATOR . $name . '.php';

        if ($this->confirm("Create new migration '$file'?")) {
            file_put_contents($file, $stub);
            echo "New migration created successfully.\n";
        }
    }
}