<?php

namespace Udesly\Assets;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class Libraries
{

    /**
     * Adds scripts and styles from vue external library
     *
     * @param $lib_name
     * @param string $object_name
     * @param null $object_data
     */
    public static function enqueue_vue_library($lib_name, $object_name = '', $object_data = null) {
        Scripts::enqueue_vue_script($lib_name, $object_name, $object_data);
        Styles::enqueue_vue_styles($lib_name);
    }

}