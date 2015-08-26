<?php
namespace CST\Yii\Illuminate\Queue;

class Worker extends \Illuminate\Queue\Worker
{
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