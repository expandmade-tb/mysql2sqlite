<?php

use helper\Autoloader;
use helper\Helper;
use helper\Transporter;

define('SCRIPT',basename(__FILE__, '.php'));
define('VERSION','1.0.0'); // version of this app
define('BASEPATH', realpath(__DIR__.'/../'));
define('APP', BASEPATH.'/app');
define('IS_CLI_SRV', php_sapi_name() === 'cli');

require_once APP.'/libs/helper/Autoloader.php';
Autoloader::instance();
Helper::setup();
Helper::writeln(SCRIPT.' '.VERSION.' (transfer a mysql database to sqlite3)');
Helper::writeln();
$transporter = new Transporter(Helper::env('db_host'), Helper::env('db_name'), Helper::env('db_user'), Helper::env('db_password'));

$timeStart = microtime(true);
$tables = $transporter->get_Tables();

foreach ($tables as $key => $value) {
    $table = $value["Tables_in_dbs620698"];
    Helper::writeln("TABLE: $table", ' ');
    $rows = $transporter->transport($table);
    Helper::writeln("- $rows ROWS inserted");
}

Helper::writeln();
Helper::writeln('Loaded in '.Helper::timer_diff($timeStart).' secs');
