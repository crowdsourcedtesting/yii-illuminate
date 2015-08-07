<?php
namespace CST\Yii\Illuminate\Queue;

use Illuminate\Encryption\Encrypter;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Queue\Connectors\RedisConnector;
use Illuminate\Redis\Database;

class RedisQueue extends \CApplicationComponent
{
    protected static $instance;

    public $config = [];
    public $encryptionKey = '<BIG-RANDOM-KEY>';
    public $queue = 'default';

    public function init()
    {
        $queue = new Queue;

        $queue->getContainer()->singleton('Illuminate\Contracts\Container\Container', function () use ($queue) {
            return $queue->getContainer();
        });
        $queue->getContainer()->bind('Illuminate\Contracts\Bus\Dispatcher', 'Illuminate\Bus\Dispatcher');
        $queue->getContainer()->bind('encrypter', function () {
            return new Encrypter($this->encryptionKey);
        });
//        $queue->getContainer()->bind('app', \Yii::app());

        // Configure the connection to Redis
        // Note: The second parameter is the connection name
        $queue->addConnection([
            'driver' => 'redis',
            'queue' => $this->queue,
        ], 'default');

        $manager = $queue->getQueueManager();
        $manager->addConnector('redis', function () {
            $database = new Database($this->config);

            return new RedisConnector($database);
        });

        // Make this Capsule instance available globally via static methods (Queue::method)
        $queue->setAsGlobal();

        self::$instance = $queue;
    }

    /**
     * @return \Illuminate\Queue\QueueManager
     */
    public function getQueueManager()
    {
        return self::$instance->getQueueManager();
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string $job
     * @param  mixed $data
     * @param  string $queue
     * @param  string $connection
     * @return mixed
     */
    public static function push($job, $data = '', $queue = null, $connection = null)
    {
        return self::$instance->connection($connection)->push($job, $data, $queue);
    }
}