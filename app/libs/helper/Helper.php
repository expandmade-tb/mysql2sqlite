<?php

namespace helper;

use Exception;

class Helper {
    public static function setup() {
        try {
            require_once APP.'/'.SCRIPT.'_config.php';
            $vars = get_defined_vars();
            foreach ($vars as $var => $value) $_ENV[$var] = $value;
        } catch (\Throwable $th) {
            self::writeln('config file missing');
            exit;
        }
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