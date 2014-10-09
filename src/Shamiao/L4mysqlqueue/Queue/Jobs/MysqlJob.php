<?php namespace Shamiao\L4mysqlqueue\Queue\Jobs;

use Closure;
use Exception;
use DateTime;
use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\Jobs\Job;

class MysqlJob extends Job {

    /**
     * Name of queue table.
     *
     * @var string
     */
    protected $table;

    /**
     * Database id of the job.
     *
     * @var int
     */
    protected $id;
    
    /**
     * Full database record values of the job.
     *
     * @var array
     */
    protected $record;

    /**
     * The class name of the job.
     *
     * @var string
     */
    protected $job;

    /**
     * Raw data parameter of the job.
     *
     * @var mixed
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param  \Illuminate\Container\Container  $container
     * @param  int  $id
     * @param  \stdClass|null  $record
     * @return void
     */
    public function __construct(Container $container, $id, $record = null)
    {
        $this->table = \Illuminate\Support\Facades\
            Config::get('queue.connections.mysql.table', 'queue');

        $this->container = $container;
        $this->id = $id;
        if ($record === null) { 
            $this->record = get_object_vars(DB::table($this->table)->find($id)); 
        } else { $this->record = get_object_vars($record); }

        $this->queue = $this->record['queue_name'];
        $payload_raw = json_decode($this->record['payload'], true);
        $this->job = $payload_raw['job'];
        $this->data = $payload_raw['data'];

        unset($this->deleted); // get rid of this fxxking property
    }

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire()
    {
        $this->record['attempts'] += 1;
        $this->record['status'] = 'running';
        DB::table($this->table)->where('ID', $this->id)->update([
            'attempts' => $this->record['attempts'],
            'status' => $this->record['status'],
        ]);

        $this->resolveAndFire(array('job' => $this->job, 'data' => $this->data));

        /** 
         * Auto delete is implemented. 
         * However, WTF is this AUTO DELETE ??? 
         * None of laravel builtin Job class implements it, 
         * nor any document mentions it.
         */
        if ($this->autoDelete()) { $this->delete(); return; } 

        /**
         * Auto release if job was not explicitly deleted/released. 
         */
        if ($this->record['status'] == 'running') { $this->release(); }
    }

    /**
     * Release the job back into the queue.
     *
     * @param  int   $delay
     * @return void
     */
    public function release($delay = 0)
    {
        $this->record['status'] = 'pending';
        $this->record['fireon'] = Carbon::now()->addSeconds($delay)->getTimestamp();
        DB::table($this->table)->where('ID', $this->id)->update([
            'status' => $this->record['status'],
            'fireon' => $this->record['fireon'],
        ]);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts()
    {
        return $this->record['attempts'];
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody()
    {
        return $this->record['payload'];
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete()
    {
        $this->record['status'] = 'deleted';
        DB::table($this->table)->where('ID', $this->id)->update([
            'status' => $this->record['status'],
        ]);
    }

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->record['status'] == 'deleted' ? true : false;
    }

    /**
     * Get the job identifier.
     *
     * @return int
     */
    public function getJobId()
    {
        return $this->id;
    }

}
