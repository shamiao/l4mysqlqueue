l4mysqlqueue
============

A laravel 4 queue driver using MySQL database, developed for small websites on shared hosting. 

Fully functional and 100% native. No special artisan commands for firing jobs required or provided. 

Features
------------

 - Support native `queue:listen`, `queue:work` and other artisan commands. 
 - Attempts count are recorded. 
 - Support multiple queues. 

 - Only use 1 table, and table name is customizable.
 - Works correctly with any database table prefix, collation and charset. 
 - MySQL only. (small changes may need for other types of database) 

Installation
------------

Add dependency to your `composer.json` and run `composer update`: 

```
"shamiao/l4mysqlqueue": "~1.0"
```

Add service provider to configuration value `app.providers` in `config/app.php`: 

```
'Shamiao\L4mysqlqueue\L4mysqlqueueServiceProvider'
```

Setup `mysql` driver in `config/queue.php`:

```
    'default' => 'mysql', // ... or any connection name you like

    'mysql' => array(
        'driver' => 'mysql',
        'queue'  => 'default', // Optional
        'table'  => 'queue','  // Optional
    ),
```

Run database migrations of this package before using it: 

```
php artisan migrate --package="shamiao/l4mysqlqueue"
```   


Configuration values
------------

### Default queue name

 - Configuration value: `queue.connections.<connname>.queue`
 - Optional, assumes `default` if not specified. 
 - Omitting this value is recommended. 

### Table name

 - Configuration value: `queue.connections.<connname>.table`
 - Optional, assumes `queue` if not specified.
 - Omitting this value is recommended. 
 - No prefix (if your app uses table name prefix feature). 

Firing jobs
------------

Just use `queue:listen` and `queue:work` artisan commands as [Queues page of Laravel Documentations](http://laravel.com/docs/queues) suggests. 

On a shared hosting without shell access, consider adding following command to your cron jobs list to fire one job every minute: 

```
* * * * * ( cd /home/username/your/laravel/dir; php artisan queue:work --tries=3; )
```

Notes
------------

 - You may need SSH access or cron jobs privileges in your cPanel/DirectAdmin control panel to run `composer` or `php artisan` console commands. 
 - Jobs are soft-deleted (only marking status as 'deleted' instead of SQL DELETE). Consider making your own schedule to delete deprecated jobs from database manually. 
