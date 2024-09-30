<?php

namespace helper;

use Exception;

class Helper {
    public static function setup(array $options=[]) {
        // get configuration from config file
        try {
            require_once APP.'/'.SCRIPT.'_config.php';
            $vars = get_defined_vars();
            foreach ($vars as $var => $value) $_ENV[$var] = $value;
        } catch (\Throwable $th) {
            self::writeln('config file missing');
            exit;
        }

        // overwrite configuration with command line params
        if ( !empty($options) ) {
            switch (true) {
                case $options['h']??false !== false:
                    $_ENV['db_host'] = $options['h'];
                case $options['d']??false !== false:
                    $dbname = $options['d'];
                    $_ENV['db_name'] = $dbname;
                    $_ENV['sqlite_db'] = "/storage/$dbname.sqlite"; 
                case $options['u']??false !== false:
                    $_ENV['db_user'] = $options['u'];
                case $options['p']??false !== false:
                    $_ENV['db_password'] = $options['p'];
                case $options['s']??false !== false:
                    $_ENV['db_password'] = $options['p'];
            }
        }

        self::writeln();
        self::writeln("HOST -> ".self::env('db_host'));
        self::writeln("DATABASE -> ".self::env('db_name'));
        self::writeln("USER -> ".self::env('db_user'));
        self::writeln("PASS -> " .self::env('db_password'));
        self::writeln("SQLite -> ".self::env('sqlite_db'));
    }

    public static function writeln (string $text='', string $new_line=PHP_EOL) {
        echo $text, $new_line;
    }
    
    public static function env(string $var, mixed $default=null): string {
        if ( isset($_ENV[$var]) )
            return $_ENV[$var];
        else
            if ( isset($default) )
                return $default;
            else
                throw new Exception("environment var {$var} unknown");
    }

    public static function timer_diff(string|float $timeStart) : string {
        return number_format((microtime(true) - floatval($timeStart)), 3);
    } 
}