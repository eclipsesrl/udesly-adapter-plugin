<?php

namespace Udesly\Utils;
// Security Check
if (!defined('WPINC') || !defined('ABSPATH')) {
    exit;
}


/**
 * Class Udesly\Utils\Test
 *
 * class with static method to test
 */
class Test
{

    /**
     * Function to benchmark function execution time in milliseconds
     *
     * @param callable $func Function to test
     */
    static function benchmark( $func ) {
        $start_time = microtime(TRUE);

        $func();

        $end_time = microtime(TRUE);

        echo $end_time - $start_time;
        echo '<br/>';

    }

    /**
     * Dump and die
     *
     * @param $var mixed
     */
    static function dd($var) {
        if(is_array($var)) {
            print_r($var);
            die();
        }
        var_dump($var);
        die();
    }

}