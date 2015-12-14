<?php
namespace CST\Yii\Illuminate\Queue;

trait DispatchesJobs
{
    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed $job
     * @return mixed
     */
    protected function dispatch($job)
    {
        $queue = $job instanceof Job && $job->queue ? $job->queue : null;

        return Yii::app()->queue->push($job, null, $queue);
    }
}