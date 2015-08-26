<?php
namespace CST\Yii\Illuminate\Queue;

use Carbon\Carbon;
use Illuminate\Queue\Failed\FailedJobProviderInterface;

class DatabaseFailedJobProvider implements FailedJobProviderInterface
{

    /**
     * Log a failed job into storage.
     *
     * @param  string $connection
     * @param  string $queue
     * @param  string $payload
     * @return void
     */
    public function log($connection, $queue, $payload)
    {
        $failed_at = Carbon::now();
        FailedJob::create(compact('connection', 'queue', 'payload', 'failed_at'));
    }

    /**
     * Get a list of all of the failed jobs.
     *
     * @return array
     */
    public function all()
    {
        return FailedJob::model()->findAll([
            'order' => 'id DESC'
        ]);
    }

    /**
     * Get a single failed job.
     *
     * @param  mixed $id
     * @return FailedJob
     */
    public function find($id)
    {
        return FailedJob::findOrFail($id);
    }

    /**
     * Delete a single failed job from storage.
     *
     * @param  mixed $id
     * @return bool
     */
    public function forget($id)
    {
        return FailedJob::model()->findOrFail($id)->delete();
    }

    /**
     * Flush all of the failed jobs from storage.
     *
     * @return void
     */
    public function flush()
    {
        FailedJob::model()->deleteAll();
    }
}