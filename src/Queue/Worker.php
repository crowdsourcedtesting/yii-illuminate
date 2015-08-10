<?php
namespace CST\Yii\Illuminate\Queue;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\QueueManager;

class Worker extends \Illuminate\Queue\Worker
{
    public function __construct(QueueManager $manager)
    {
        parent::__construct($manager, null, new Dispatcher(new Container()));
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun()
    {
        if (method_exists(\Yii::app(), 'isDownForMaintenance') &&
            \Yii::app()->isDownForMaintenance()
        ) {
            return false;
        }

        return $this->events->until('illuminate.queue.looping') !== false;
    }
}