<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQueueTable extends Migration {

    /**
     * Name of queue table.
     *
     * @var string
     */
    protected $table;

    /**
     * Initialize the migration instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->table = \Illuminate\Support\Facades\
            Config::get('queue.connections.mysql.table', 'queue');
    }

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        /**
         * Since L4 doesn't support specifying collation for tables/text fields, 
         * An empty table (only contains ID) is created first. 
         */
        Schema::create($this->table, function($table)
        {
            $table->bigIncrements('ID');
        });

        /**
         * Table collation is specified with raw MySQL SQL statement. 
         */
        $table_w_prefix = DB::getTablePrefix() . $this->table;
        DB::statement("ALTER TABLE `{$table_w_prefix}`
            DEFAULT CHARACTER SET ascii COLLATE ascii_general_ci;");

        /**
         * ... Then all the actual data fields are added. 
         */
        Schema::table($this->table, function($table)
		{
		    $table->string('queue_name');
            $table->enum('status', ['deleted', 'pending', 'running']);
		    $table->integer('attempts')->unsigned();
		    $table->longText('payload');
		    $table->bigInteger('fireon');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists($this->table);
	}

}
