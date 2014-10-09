<?php namespace Shamiao\L4mysqlqueue\Queue;

use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\Queue;
use Illuminate\Queue\QueueInterface;
use ErrorException;

class MysqlQueue extends Queue implements QueueInterface {

    /**
     * Name of queue table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of queue. 
     *
     * @var string
     */
    protected $queue;

    /**
     * Create a new Mysql queue instance.
     *
     * @param  string  $queue_name
     * @return void
     */
    public function __construct($queue = null)
    {
        $this->table = \Illuminate\Support\Facades\
            Config::get('queue.connections.mysql.table', 'queue');

        if ($queue === null) { $queue = 'default'; }
        $this->queue = $queue;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function push($job, $data = '', $queue = null)
    {
        if ($queue === null) { $queue = $this->queue; }
        return $this->pushRaw($this->createPayload($job, $data), $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = array())
    {
        if ($queue === null) { $queue = $this->queue; }
        $jobId = $this->insertJobRecord($payload, Carbon::now(), $queue);
        return 0;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTime|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        if ($queue === null) { $queue = $this->queue; }
        if ($delay instanceof DateTime) {
            $time = $delay;
        } elseif (is_int($delay)) {
            $time = Carbon::now()->addSeconds($delay);
        } else {
            throw new ErrorException('DateTime or int $delay required. ');
        }
        $jobId = $this->insertJobRecord($this->createPayload($job, $data), 
            $time, $queue);
        return 0;
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string  $queue
     * @return \Illuminate\Queue\Jobs\Job|null
     */
    public function pop($queue = null) {
        if ($queue === null) { $queue = $this->queue; }
        $query = DB::table($this->table)->where('queue_name', $queue)
            ->where('status', 'pending')
            ->where('fireon', '<', time())
            ->orderBy('fireon', 'asc');
        if ($query->count() == 0) {return null;}
        $record = $query->first();
        return new Jobs\MysqlJob($this->container, $record->ID, $record);
    }

    /**
     * Insert a job record into database. 
     *
     * @param  string   $payload Payload string. 
     * @param  DateTime $time    Exact firing time of the job.
     * @param  string   $queue   Queue name of the job. 
     * @return int               ID of new record inserted. 
     */
    private function insertJobRecord($payload, $time, $queue)
    {
        if (!$time instanceof DateTime) {
            throw new ErrorException('An explicit DateTime value $time is required. ');
        }
        $jobId = DB::table($this->table)->insertGetId([
            'queue_name' => $queue, 
            'payload'  => $payload, 
            'status'   => 'pending', 
            'attempts' => 1, 
            'fireon'   => $time->getTimestamp(), 
        ]);
        return $jobId; 
    }
}
