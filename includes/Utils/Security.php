<?php

namespace Udesly\Utils;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class Security
{
    /**
     * Prevents file execution out of WordPress context
     */
    public static function check_abspath()
    {
        if (!defined('WPINC') || !defined('ABSPATH')) {
            die;
        }
    }
}