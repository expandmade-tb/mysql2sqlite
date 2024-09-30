#!/usr/bin/env php

<?php

// debug at command line: export XDEBUG_MODE=debug XDEBUG_SESSION=1

use helper\Autoloader;
use helper\Helper;
use helper\Transporter;

define('SCRIPT',basename(__FILE__, '.php'));
define('VERSION','1.2.0'); // version of this app
define('BASEPATH', realpath(__DIR__.'/../'));
define('APP', BASEPATH.'/app');
define('IS_CLI_SRV', php_sapi_name() === 'cli');

require_once APP.'/libs/helper/Autoloader.php';
Autoloader::instance();
Helper::writeln(SCRIPT.' '.VERSION.' (transfer a mysql database to sqlite3)');
$usage = $argv[1]??'';

if ( $usage === '?' ) {
    Helper::writeln('usage:');
    Helper::writeln('   -h host');
    Helper::writeln('   -d database');
    Helper::writeln('   -u user');
    Helper::writeln('   -p password');
    exit;
}

$options = getopt('h::d::u::p::',['host','database','user','password']);

if ($options === false)
    Helper::setup();
else
    Helper::setup($options);

Helper::writeln();
$transporter = new Transporter(Helper::env('db_host'), Helper::env('db_name'), Helper::env('db_user'), Helper::env('db_password'));

$timeStart = microtime(true);
$tables = $transporter->get_Tables();
$tables_index = $table_index = 'Tables_in_'.Helper::env('db_name');

foreach ($tables as $key => $value) {
    $table = $value[$tables_index];
    Helper::writeln("...TABLE   : $table", ' ');    
    $rows = $transporter->transport($table);

    if ( is_int($rows) )
        Helper::writeln("-> $rows ROWS inserted");
    else
        Helper::writeln("-> error occured: $rows");
}

Helper::writeln();
Helper::writeln('Loaded in '.Helper::timer_diff($timeStart).' secs');
