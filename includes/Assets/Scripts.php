<?php

namespace Udesly\Assets;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

/**
 * Class Scripts
 * @package Udesly\Assets
 */
class Scripts
{

    /**
     * Includes Admin Scripts
     * @param $hook string
     */
    public static function admin_scripts($hook)
    {

    }

    /**
     * Admin Hooks
     */
    public static function admin_hooks() {
        add_action('admin_enqueue_scripts', 'Udesly\Assets\Scripts::admin_scripts');
    }

    /**
     * Public Hooks
     */
    public static function public_hooks() {

    }

    /**
     * Includes Public Scripts
     * @param $hook string
     */
    public static function public_scripts($hook)
    {

    }

    /**
     * Gets External Vue Script Paths
     * @param $libName
     * @return array
     */
    private static function get_external_vue_script_paths($libName)
    {
        $libPath = UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . "externals/$libName/dist/";

        $scripts = [];
        foreach (glob($libPath. "js/*.js") as $script) {
            $scripts[] = UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "externals/$libName/dist/js/" . basename($script);
        }
        if (file_exists(UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . "externals/$libName/dist/app.js")) {
            $scripts[] = UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "externals/$libName/dist/app.js";
        }
        return $scripts;

    }



    public static function enqueue_vue_script( $libName, $objectName = '', $data = null ) {
        foreach (self::get_external_vue_script_paths($libName) as $index => $script) {
            if ($index == 0) {
                wp_enqueue_script($libName, $script, array(), UDESLY_ADAPTER_VERSION, true);
                if ($data && $objectName) {
                    wp_localize_script($libName, $objectName, $data);
                }
            } else {
                wp_enqueue_script("$libName-$index", $script, array(), UDESLY_ADAPTER_VERSION, true);
            }
        }


    }


}