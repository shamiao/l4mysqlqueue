<?php namespace Shamiao\L4mysqlqueue\Queue\Connectors;

use Illuminate\Queue\Connectors\ConnectorInterface;
use Shamiao\L4mysqlqueue\Queue\MysqlQueue;

class MysqlConnector implements ConnectorInterface {

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Queue\QueueInterface
     */
    public function connect(array $config)
    {
        if (array_key_exists('queue', $config)) {
            return new MysqlQueue($config['queue']);
        } else {
            return new MysqlQueue();
        }
        
    }

}
