<?php

namespace Udesly\Theme;

if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class UdeslyThemeData
{

    public $pages;
    public $plugins;
    public $frontend_editor_enabled;
    public $page_reports = [];
    public $custom_post_types = [];
    public $adapter_data;

    private static $instance = null;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new UdeslyThemeData();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $data_path = DataManager::get_theme_udesly_data_path();
        $content = file_get_contents($data_path);
        $raw_data = json_decode($content);

        $this->pages = isset($raw_data->pages) ? $raw_data->pages : [];

        $this->plugins = isset($raw_data->plugins) ? $raw_data->plugins : [];

        $this->custom_post_types = isset($raw_data->cpt) ? $raw_data->cpt : [];

        $this->frontend_editor_enabled = isset($raw_data->frontendEditor) ? (bool)$raw_data->frontendEditor : false;

        $this->adapter_data = new IntegrationData();

        if (isset($raw_data->data)) {
            foreach ($raw_data->data as $page_name => $data) {
                $empty = true;
                foreach ($data as $key => $value) {
                    if (count($value)) {
                        $empty = false;
                        break;
                    }
                }
                if (!$empty) {
                    $this->page_reports[$page_name] = IntegrationData::from_obj($data);
                    $this->adapter_data->merge($data);
                }
            }
        }
    }

    private function is_active($plugin) {
        switch ($plugin) {
            case "advanced-custom-fields":
                return function_exists("get_field");
            case "woocommerce":
                if (is_multisite()) {
                    return true;
                }
                return is_plugin_active( 'woocommerce/woocommerce.php');
            default:
                return false;
        }
    }

    public function get_missing_plugins() {
        $plugins = [];
        foreach ($this->plugins as $plugin) {

            if( !$this->is_active($plugin)) {
                $plugins[] = $plugin;
            }
        }
        return $plugins;
    }

    public function get_missing_post_types() {
        $cpt = [];
        foreach ($this->custom_post_types as $custom_post_type) {
            if (!post_type_exists($custom_post_type)) {
                $cpt[] = $custom_post_type;
            }
        }
        return $cpt;
    }

    public static function get_filemtime() {
        return filemtime(DataManager::get_theme_udesly_data_path());
    }

    public function get_missing_pages()
    {
        $data = [];
        foreach ($this->pages as $page_slug) {
            if (!udesly_get_post_by_slug($page_slug)) {
                $data[] = $page_slug;
            }
        }
        return $data;
    }

    public function get_frontend_editor_pages_path()
    {
        $folder_path = trailingslashit(DataManager::get_theme_data_folder_path()) . 'frontend-editor/';

        return glob($folder_path . '*.json');
    }
}