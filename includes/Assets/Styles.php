<?php

namespace Udesly\Assets;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}
/**
 * Class Styles
 * @package Udesly\Assets
 */
class Styles
{

    private static function get_external_vue_style_paths($libName)
    {
        $libPath = UDESLY_ADAPTER_PLUGIN_DIRECTORY_PATH . "externals/$libName/dist/";

        $scripts = [];
        foreach (glob($libPath. "css/*.css") as $script) {
            $scripts[] = UDESLY_ADAPTER_PLUGIN_DIRECTORY_URL . "externals/$libName/dist/css/" . basename($script);
        }
        return $scripts;

    }

    public static function enqueue_vue_styles($lib_name) {
        foreach (self::get_external_vue_style_paths($lib_name) as $index => $style) {
            wp_enqueue_style("$lib_name-$index", $style, array(), UDESLY_ADAPTER_VERSION, 'all');
        }
        wp_enqueue_style("font-awesome-udesly", "https://use.fontawesome.com/releases/v5.8.1/css/all.css", array(), "5.8.1", 'all');
    }

}