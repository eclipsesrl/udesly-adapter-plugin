<?php

namespace Udesly\Theme;

use mysql_xdevapi\Exception;
use Udesly\FrontendEditor\FrontendEditorType;
if (!defined('WPINC') || !defined('ABSPATH')) {
    die;
}

class DataManager
{

    public static function get_theme_data_folder_path()
    {
        return get_stylesheet_directory() . '/data/';
    }

    public static function get_theme_udesly_data_path()
    {
        return self::get_theme_data_folder_path() . 'udesly-data.json';
    }

    public static function get_options_udesly_data_path()
    {
        return self::get_theme_data_folder_path() . 'udesly-customizer-options.json';
    }

    public static function is_udesly_theme_active()
    {
        return file_exists(self::get_theme_udesly_data_path());
    }

    public static function admin_hooks()
    {
        add_action("wp_ajax_udesly_import_missing_data", array(self::class, "udesly_import_missing_data"));
        add_action("wp_ajax_udesly_delete_pages", array(self::class, "udesly_delete_pages"));
        add_action("wp_ajax_udesly_delete_frontend_editor_data", array(self::class, "udesly_delete_frontend_editor_data"));
        add_action("wp_ajax_udesly_clear_frontend_editor_transient", array(self::class, "udesly_clear_frontend_editor_transient"));
        add_action("admin_notices", array(self::class, "udesly_check_data"));
        add_action("activated_plugin", array(self::class, "udesly_delete_check_data"));
        add_action("deactivated_plugin", array(self::class, "udesly_delete_check_data"));
        add_action("save_post", array(self::class, "udesly_delete_check_data"));
        add_action("delete_post", array(self::class, "udesly_delete_check_data"));
    }

    public static function public_hooks() {
        add_action( 'customize_register', array(self::class, 'register_customizer') );
    }

    public static function register_customizer( $wp_customize ) {
        //All our sections, settings, and controls will be added here

        $path = self::get_options_udesly_data_path();
        if (!file_exists($path)) {
            return;
        }
        try {
           $file = file_get_contents($path);
           $options = json_decode($file);
           if (!$options) {
               return;
           }
          $options = (array) $options;

           if (count($options) == 0) {
               return;
           }

            $wp_customize->add_panel('udesly_panel', array(
                'title'=>'Your Theme',
                'description'=> 'Theme Options',
                'priority'=> 10,
            ));

           $section_id = 'udesly_theme_section';
            $wp_customize->add_section( $section_id , array(
                'title'      => __( 'Theme Options', 'udesly' ),
                'priority'   => 30,
                'panel' => 'udesly_panel'
            ) );

           foreach ($options as $key => $option) {
               switch ($option->type) {
                   case "text":
                       $wp_customize->add_setting($option->slug, array('default' => $option->default));
                       $wp_customize->add_control(
                           $option->slug,
                           array(
                               'label'          => $option->label,
                               'section'        => $section_id,
                               'settings'       => $option->slug,
                               'type'           => 'text'
                           )
                       );
                       break;
                   case "textarea":
                       $wp_customize->add_setting($option->slug, array('default' => $option->default));
                       $wp_customize->add_control(
                           $option->slug,
                           array(
                               'label'          => $option->label,
                               'section'        => $section_id,
                               'settings'       => $option->slug,
                               'type'           => 'textarea'
                           )
                       );
                       break;
                   case "url":
                       $wp_customize->add_setting($option->slug, array('default' => $option->default));
                       $wp_customize->add_control(
                           $option->slug,
                           array(
                               'label'          => $option->label,
                               'section'        => $section_id,
                               'settings'       => $option->slug,
                               'type'           => 'url'
                           )
                       );
                       break;
                   case "number":
                       $wp_customize->add_setting($option->slug, array('default' => $option->default));
                       $wp_customize->add_control(
                           $option->slug,
                           array(
                               'label'          => $option->label,
                               'section'        => $section_id,
                               'settings'       => $option->slug,
                               'type'           => 'number'
                           )
                       );
                       break;
                   case "image":
                       $wp_customize->add_setting($option->slug, array('default' => $option->default));
                       $wp_customize->add_control(
                           new \WP_Customize_Image_Control(
                               $wp_customize,
                               $option->slug,
                               array(
                                   'label'      => $option->label,
                                   'section'    => $section_id,
                                   'settings'   => $option->slug,
                               )
                           )
                       );
                       break;
                   case "color":
                   $wp_customize->add_setting($option->slug, array('default' => $option->default));
                   $wp_customize->add_control( new \WP_Customize_Color_Control( $wp_customize,  $option->slug, array(
                       'label'      => $option->label,
                       'section'    => $section_id,
                       'settings'   => $option->slug,
                   ) ) );
                   break;
               }
           }

        } catch(\Exception $e) {
            return;
        }
    }

    public static function udesly_delete_check_data() {
        delete_transient("_udesly_last_checked_data");
        delete_transient("_udesly_cached_checked_data");
    }

    public static function udesly_check_data()
    {

        $data = self::udesly_get_missing_data();
        if (count($data['pages']) > 0) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><?php _e('There are missing pages you have to import from Udesly > Webflow Data', UDESLY_TEXT_DOMAIN); ?></p>
            </div>
            <?php
        }
        if (count($data['plugins']) > 0) {
            foreach ($data['plugins'] as $plugin) :
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php printf( __( 'Your theme uses %s, please install it!', UDESLY_TEXT_DOMAIN ), $plugin ); ?></p>
                </div>
            <?php
            endforeach;
        }
        if (count($data['post_types']) > 0) {
            foreach ($data['post_types'] as $post_type) :
                ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php printf( __( 'Your theme uses a custom post type called: <b>%s</b>, please create it!', UDESLY_TEXT_DOMAIN ), $post_type ); ?></p>
                </div>
            <?php
            endforeach;
        }
        if($data['count'] > 0) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e('There is missing data. Please check Udesly > Webflow Data - Theme Report', UDESLY_TEXT_DOMAIN); ?></p>
            </div>
            <?php
        }

    }

    private static function udesly_get_missing_data()
    {
        $filemtime = UdeslyThemeData::get_filemtime();
        $transient = get_transient("_udesly_last_checked_data");
        if (!$transient) {
            // check data and set cache
            $data = self::udesly_create_missing_data();
            set_transient("_udesly_last_checked_data", time(), 0);
            set_transient("_udesly_cached_checked_data", $data);
            return $data;
        } else {
            if ($transient > $filemtime) {
                // use cache
                $cache = get_transient("_udesly_cached_checked_data");
                if ($cache) {
                    return $cache;
                } else {
                    $data = self::udesly_create_missing_data();
                    set_transient("_udesly_last_checked_data", time(), 0);
                    set_transient("_udesly_cached_checked_data", $data);
                    return $data;
                }
            } else {
                $data = self::udesly_create_missing_data();
                set_transient("_udesly_last_checked_data", time(), 0);
                set_transient("_udesly_cached_checked_data", $data);
                return $data;
            }
        }

    }

    private static function udesly_create_missing_data()
    {
        $theme_data = UdeslyThemeData::getInstance();
        return array(
            "pages" => $theme_data->get_missing_pages(),
            "plugins" => $theme_data->get_missing_plugins(),
            "post_types" => $theme_data->get_missing_post_types(),
            "count" => $theme_data->adapter_data->get_missing_data()["count"],
        );
    }

    public static function udesly_clear_frontend_editor_transient()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_import_missing_data')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        FrontendEditorType::delete_fe_transients();
        wp_send_json_success();
        wp_die();
    }

    public static function udesly_delete_frontend_editor_data()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_import_missing_data')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        FrontendEditorType::delete_fe_transients();
        $allposts = get_posts(array('post_type' => 'udesly_fe_data', 'numberposts' => -1));
        foreach ($allposts as $eachpost) {
            wp_delete_post($eachpost->ID, true);
        }
        wp_send_json_success();
        wp_die();
    }


    public static function udesly_delete_pages()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_import_missing_data')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        $udesly_pages = get_pages(array(
            "meta_key" => "_udesly_page",
            "meta_value" => 1
        ));
        if ($udesly_pages) {
            foreach ($udesly_pages as $page) {
                wp_delete_post($page->ID, true);
            }
        }

        $theme_data = UdeslyThemeData::getInstance();
        $pages = $theme_data->get_missing_pages();
        wp_send_json_success(array("missingPages" => count($pages)));
        wp_die();
    }

    public static function udesly_import_missing_data()
    {
        if (!wp_verify_nonce($_POST['security'], 'udesly_import_missing_data')) {
            wp_send_json_error("security_check", 403);
            wp_die();
        }
        $theme_data = UdeslyThemeData::getInstance();
        $pages = $theme_data->get_missing_pages();
        foreach ($pages as $page_slug) {
            if (!self::create_page($page_slug)) {
                wp_send_json_error("Failed to create page $page_slug", 500);
                wp_die();
            }
        }
        if ($theme_data->frontend_editor_enabled) {
            FrontendEditorType::delete_fe_transients();
            $fe_paths = $theme_data->get_frontend_editor_pages_path();
            foreach ($fe_paths as $fe_path) {
                if (!self::import_fe_data_from_file($fe_path)) {
                    $page_name = str_replace('.json', '', basename($fe_path));
                    wp_send_json_error("Failed to import fe data $page_name", 500);
                    wp_die();
                }
            }
        }

        wp_send_json_success(array(
            "pages" => count($pages),
        ));
        wp_die();
    }

    private static function import_fe_data_from_file($fe_path)
    {
        $page_name = str_replace('.json', '', basename($fe_path));
        return FrontendEditorType::upsert_frontend_editor_page_data($page_name, json_decode(file_get_contents($fe_path)));
    }

    private static function create_page($page_slug)
    {

        $page_title = str_replace("_", " ", $page_slug);
        $page_title = ucwords(str_replace("-", " ", $page_title));

        $post_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_name' => $page_slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'meta_input' => array(
                "_udesly_page" => 1
            )
        ));

        return $post_id != 0;
    }

}